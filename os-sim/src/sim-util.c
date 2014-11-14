/*
  License:

  Copyright (c) 2003-2006 ossim.net
  Copyright (c) 2007-2013 AlienVault
  All rights reserved.

  This package is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; version 2 dated June, 1991.
  You may not use, modify or distribute this program under any other version
  of the GNU General Public License.

  This package is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this package; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
  MA  02110-1301  USA


  On Debian GNU/Linux systems, the complete text of the GNU General
  Public License can be found in `/usr/share/common-licenses/GPL-2'.

  Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
*/

#define _GNU_SOURCE

#include "config.h"

#include "sim-util.h"

#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>
#include <sys/socket.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <unistd.h>
#include <glib.h>
#include <gio/gio.h>
#include <glib/gprintf.h>
#include <gnet.h>
#include <string.h>
#include <stdlib.h>
#include <limits.h>
#include <regex.h>
#include <signal.h>
#include <errno.h>
#include <pcre.h>
#include <inttypes.h>

#ifdef CHINESE_ENCODING
  #include <tidy/tidy.h>
  #include <tidy/buffio.h>
gchar *sim_util_strrepl(gchar *, gchar *, gchar *);
#endif

#include "os-sim.h"
#include "sim-inet.h"
#include "sim-config.h"
#include "sim-container.h"
extern SimMain  ossim;
#define CHAR64(c)           (((c) < 0 || (c) > 127) ? -1 : index_64[(c)])
static gchar     base64_table[] =
"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
static gchar     index_64[128] = {
   -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
   -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
   -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, 62, -1, -1, -1, 63,
   52, 53, 54, 55, 56, 57, 58, 59, 60, 61, -1, -1, -1, -1, -1, -1,
   -1, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14,
   15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, -1, -1, -1, -1, -1,
   -1, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40,
   41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, -1, -1, -1, -1, -1
};
static gchar *hexdigitchar = "0123456789ABCDEF";
gchar *sim_bin2hex(guint8 *data,guint len){
	gchar *d = g_new(gchar,len*2+1);
	guint i;
	if (d!=NULL){
		for (i=0;i<(len);i++){
			d[i*2] = hexdigitchar[(data[i]&0xf0)>>4];
			d[i*2+1] = hexdigitchar[data[i]&0xf];
		}
		d[i*2]='\0';
	}

	return d;
}

guint8 *sim_hex2bin(gchar *data){
	size_t i;
  int j=0;
	size_t l;
	gchar *st=NULL;
	if (data!=NULL){
		  l = strlen(data);
			if (l % 2) return NULL;
			st=g_new(gchar,l/2);
			if (st!=NULL){
				for(i=0;i<l;i+=2){
					if (g_ascii_isxdigit(data[i]) && g_ascii_isxdigit(data[i+1])){
						st[j++] =   g_ascii_xdigit_value(data[i])*16+  g_ascii_xdigit_value(data[i+1]);
					}else{
						g_free(st);
						st = NULL;
						break;
					}
				}
			}
	  }
	return (guint8*)st;
}


/*
 *
 *
 *
 */
SimProtocolType
sim_protocol_get_type_from_str (const gchar  *str)
{
  g_return_val_if_fail (str, SIM_PROTOCOL_TYPE_NONE);

  if (!g_ascii_strcasecmp (str, "ICMP"))
    return SIM_PROTOCOL_TYPE_ICMP;
  else if (!g_ascii_strcasecmp (str, "UDP"))
    return SIM_PROTOCOL_TYPE_UDP;
  else if (!g_ascii_strcasecmp (str, "TCP"))
    return SIM_PROTOCOL_TYPE_TCP;
  else if (!g_ascii_strcasecmp (str, "Host_ARP_Event"))
    return SIM_PROTOCOL_TYPE_HOST_ARP_EVENT;
  else if (!g_ascii_strcasecmp (str, "Host_OS_Event"))
    return SIM_PROTOCOL_TYPE_HOST_OS_EVENT;
  else if (!g_ascii_strcasecmp (str, "Host_Service_Event"))
    return SIM_PROTOCOL_TYPE_HOST_SERVICE_EVENT;
  else if (!g_ascii_strcasecmp (str, "Information_Event"))
    return SIM_PROTOCOL_TYPE_INFORMATION_EVENT;
  else if (!g_ascii_strcasecmp (str, "OTHER"))
    return SIM_PROTOCOL_TYPE_OTHER;
 
  return SIM_PROTOCOL_TYPE_NONE;
}

/*
 *
 *
 */
gchar*
sim_protocol_get_str_from_type (SimProtocolType type)
{
  switch (type)
    {
    case SIM_PROTOCOL_TYPE_ICMP:
      return g_strdup ("ICMP");
    case SIM_PROTOCOL_TYPE_UDP:
      return g_strdup ("UDP");
    case SIM_PROTOCOL_TYPE_TCP:
      return g_strdup ("TCP");
    case SIM_PROTOCOL_TYPE_HOST_ARP_EVENT:
      return g_strdup ("Host_ARP_Event");
    case SIM_PROTOCOL_TYPE_HOST_OS_EVENT:
      return g_strdup ("Host_OS_Event");
    case SIM_PROTOCOL_TYPE_HOST_SERVICE_EVENT:
      return g_strdup ("Host_Service_Event");
    case SIM_PROTOCOL_TYPE_INFORMATION_EVENT:
      return g_strdup ("Information_Event");
    default:
      return g_strdup ("OTHER");
    }
}

/*
 *
 *
 *
 */
SimConditionType
sim_condition_get_type_from_str (const gchar  *str)
{
  g_return_val_if_fail (str, SIM_CONDITION_TYPE_NONE);

  if (!g_ascii_strcasecmp (str, "eq"))
    return SIM_CONDITION_TYPE_EQ;
  else if (!g_ascii_strcasecmp (str, "ne"))
    return SIM_CONDITION_TYPE_NE;
  else if (!g_ascii_strcasecmp (str, "lt"))
    return SIM_CONDITION_TYPE_LT;
  else if (!g_ascii_strcasecmp (str, "le"))
    return SIM_CONDITION_TYPE_LE;
  else if (!g_ascii_strcasecmp (str, "gt"))
    return SIM_CONDITION_TYPE_GT;
  else if (!g_ascii_strcasecmp (str, "ge"))
    return SIM_CONDITION_TYPE_GE;

  return SIM_CONDITION_TYPE_NONE;
}

/*
 *
 *
 *
 */
gchar*
sim_condition_get_str_from_type (SimConditionType  type)
{
  switch (type)
    {
    case SIM_CONDITION_TYPE_EQ:
      return g_strdup ("eq");
    case SIM_CONDITION_TYPE_NE:
      return g_strdup ("ne");
    case SIM_CONDITION_TYPE_LT:
      return g_strdup ("lt");
    case SIM_CONDITION_TYPE_LE:
      return g_strdup ("le");
    case SIM_CONDITION_TYPE_GT:
      return g_strdup ("gt");
    case SIM_CONDITION_TYPE_GE:
      return g_strdup ("ge");
    default:
      return NULL;
    }
}

/*
 *
 *
 *
 */
SimPortProtocol*
sim_port_protocol_new (gint              port,
								       SimProtocolType   protocol)
{
  SimPortProtocol  *pp;

  g_return_val_if_fail (port >= 0, NULL);
  g_return_val_if_fail (protocol >= -1, NULL);

  pp = g_new0 (SimPortProtocol, 1);
  pp->port = port;
  pp->protocol = protocol;

  return pp;
}

/*
 *
 * 1st parameter: Policy value
 * 2nd parameter: Event value
 */
gboolean
sim_port_protocol_equal (SimPortProtocol  *pp1,
												 SimPortProtocol  *pp2)
{
  g_return_val_if_fail (pp1, FALSE);  
  g_return_val_if_fail (pp2, FALSE);  

	#ifdef POLICY_DEBUG
	  ossim_debug ( "Policy port: %d , protocol: %d", pp1->port, pp1->protocol);
  	ossim_debug ( "       port: %d , protocol: %d", pp2->port, pp2->protocol);
	#endif
      
  if (pp1->port == 0)	//if the port defined in policy is "0", its like ANY and all the ports will match
    return TRUE;    
  if ((pp1->port == pp2->port) && (pp1->protocol == pp2->protocol))
    return TRUE;

  return FALSE;
}

/*
 *
 * FIXME:I think that this function is useless until we make a "sim_xml_directive_set_rule_*" function.  
 * This returns the var type of the n level in a rule from a directive
 *
 */
SimRuleVarType
sim_get_rule_var_from_char (const gchar *var)
{
  g_return_val_if_fail (var != NULL, SIM_RULE_VAR_NONE);

  if (!strcmp (var, SIM_SRC_IP_CONST))
    return SIM_RULE_VAR_SRC_IA;
  else if (!strcmp (var, SIM_DST_IP_CONST))
    return SIM_RULE_VAR_DST_IA;
  else if (!strcmp (var, SIM_SRC_PORT_CONST))
    return SIM_RULE_VAR_SRC_PORT;
  else if (!strcmp (var, SIM_DST_PORT_CONST))
    return SIM_RULE_VAR_DST_PORT;
  else if (!strcmp (var, SIM_PROTOCOL_CONST))
    return SIM_RULE_VAR_PROTOCOL;
  else if (!strcasecmp (var, SIM_PLUGIN_ID_CONST))
    return SIM_RULE_VAR_PLUGIN_ID;
  else if (!strcmp (var, SIM_PLUGIN_SID_CONST))
    return SIM_RULE_VAR_PLUGIN_SID;
  else if (!strcmp (var, SIM_SENSOR_CONST))
    return SIM_RULE_VAR_SENSOR;
  else if (!strcasecmp (var, SIM_PRODUCT_CONST))
    return SIM_RULE_VAR_PRODUCT;
  else if (!strcasecmp (var, SIM_ENTITY_CONST))
    return SIM_RULE_VAR_ENTITY;
  else if (!strcasecmp (var, SIM_CATEGORY_CONST))
    return SIM_RULE_VAR_CATEGORY;
  else if (!strcasecmp (var, SIM_SUBCATEGORY_CONST))
    return SIM_RULE_VAR_SUBCATEGORY;
  else if (!strcmp (var, SIM_FILENAME_CONST))
    return SIM_RULE_VAR_FILENAME;
  else if (!strcmp (var, SIM_USERNAME_CONST))
    return SIM_RULE_VAR_USERNAME;
  else if (!strcmp (var, SIM_PASSWORD_CONST))
    return SIM_RULE_VAR_PASSWORD;
  else if (!strcmp (var, SIM_USERDATA1_CONST))
    return SIM_RULE_VAR_USERDATA1;
  else if (!strcmp (var, SIM_USERDATA2_CONST))
    return SIM_RULE_VAR_USERDATA2;
  else if (!strcmp (var, SIM_USERDATA3_CONST))
    return SIM_RULE_VAR_USERDATA3;
  else if (!strcmp (var, SIM_USERDATA4_CONST))
    return SIM_RULE_VAR_USERDATA4;
  else if (!strcmp (var, SIM_USERDATA5_CONST))
    return SIM_RULE_VAR_USERDATA5;
  else if (!strcmp (var, SIM_USERDATA6_CONST))
    return SIM_RULE_VAR_USERDATA6;
  else if (!strcmp (var, SIM_USERDATA7_CONST))
    return SIM_RULE_VAR_USERDATA7;
  else if (!strcmp (var, SIM_USERDATA8_CONST))
    return SIM_RULE_VAR_USERDATA8;
  else if (!strcmp (var, SIM_USERDATA9_CONST))
    return SIM_RULE_VAR_USERDATA9;
	
  return SIM_RULE_VAR_NONE;
}

/*
 * Used to get the variable type from properties in the directive
 */
/*
SimRuleVarType
sim_get_rule_var_from_property (const gchar *var)
{

  if (!strcmp (var, PROPERTY_FILENAME))
    return SIM_RULE_VAR_FILENAME;
  else if (!strcmp (var, PROPERTY_USERNAME))
    return SIM_RULE_VAR_USERNAME;
  else if (!strcmp (var, PROPERTY_PASSWORD))
    return SIM_RULE_VAR_PASSWORD;
  else if (!strcmp (var, PROPERTY_USERDATA1))
    return SIM_RULE_VAR_USERDATA1;
  else if (!strcmp (var, PROPERTY_USERDATA2))
    return SIM_RULE_VAR_USERDATA2;
  else if (!strcmp (var, PROPERTY_USERDATA3))
    return SIM_RULE_VAR_USERDATA3;
  else if (!strcmp (var, PROPERTY_USERDATA4))
    return SIM_RULE_VAR_USERDATA4;
  else if (!strcmp (var, PROPERTY_USERDATA5))
    return SIM_RULE_VAR_USERDATA5;
  else if (!strcmp (var, PROPERTY_USERDATA6))
    return SIM_RULE_VAR_USERDATA6;
  else if (!strcmp (var, PROPERTY_USERDATA7))
    return SIM_RULE_VAR_USERDATA7;
  else if (!strcmp (var, PROPERTY_USERDATA8))
    return SIM_RULE_VAR_USERDATA8;
  else if (!strcmp (var, PROPERTY_USERDATA9))
    return SIM_RULE_VAR_USERDATA9;
	
  return SIM_RULE_VAR_NONE;
}
*/

/*
 *
 *
 *
 */
SimAlarmRiskType
sim_get_alarm_risk_from_char (const gchar *var)
{
  g_return_val_if_fail (var != NULL, SIM_ALARM_RISK_TYPE_NONE);

  if (!g_ascii_strcasecmp (var, "low"))
    return SIM_ALARM_RISK_TYPE_LOW;
  else if (!g_ascii_strcasecmp (var, "medium"))
    return SIM_ALARM_RISK_TYPE_MEDIUM;
  else if (!g_ascii_strcasecmp (var, "high"))
    return SIM_ALARM_RISK_TYPE_HIGH;
  else if (!g_ascii_strcasecmp (var, "all"))
    return SIM_ALARM_RISK_TYPE_ALL;
  else
    return SIM_ALARM_RISK_TYPE_NONE;
}

/*
 *
 *
 *
 */
SimAlarmRiskType
sim_get_alarm_risk_from_risk (gint risk)
{
  if ((risk >= 1) && risk <= 4)
    return SIM_ALARM_RISK_TYPE_LOW;
  else if (risk >= 5 && risk <= 7)
    return SIM_ALARM_RISK_TYPE_MEDIUM;
  else if (risk >= 8 && risk <= 10)
    return SIM_ALARM_RISK_TYPE_HIGH;
  else
    return SIM_ALARM_RISK_TYPE_NONE;
}

/*
 *
 *
 *
 */
GList*
sim_get_ias (const gchar *value)
{
  GInetAddr  *ia;
  GList      *list = NULL;

  g_return_val_if_fail (value != NULL, NULL);

  ia = gnet_inetaddr_new_nonblock (value, 0);

  list = g_list_append (list, ia);

  return list;
}

/*
 *
 * Given a string with network(s) or hosts, it returns a GList of SimInet objects (one network or host each object).
 * The format can be only: or  "192.168.1.0/24" or "192.168.1.1".
 * This function doesn't accepts multiple hosts or nets.
 */
/*
GList*
sim_get_inets (const gchar *value)
{
  SimInet    *inet;
  GList      *list = NULL;

  g_return_val_if_fail (value != NULL, NULL);

  inet = sim_inet_new (value);
  if (inet)
    list = g_list_append (list, inet);
  else
    ossim_debug ("Error: sim_get_inets: %s", value);

  return list;
}
*/
/*
 *
 * Takes any string like "192.168.1.0-40,192.168.1.0/24,192.168.5.6", transform everything into SimInet objects
 * and put them into a GList. If the string has some "ANY", no list is returned and has_any variable is set TRUE.
 */
GList*
sim_get_inets_from_string (const gchar *value, gboolean *has_any)
{
  SimInet *inet;
  GList   *list = NULL;

  g_return_val_if_fail (value != NULL, NULL);

  if (g_strstr_len (value, strlen (value), SIM_WILDCARD_ANY) ||
			g_strstr_len (value, strlen (value), "any")) //if appears "ANY" anywhere in the string
  {
    /*inet = sim_inet_new_any ();
    list = g_list_append (list, inet);*/
    *has_any = TRUE;
		return NULL;
  }
  else
    *has_any = FALSE;

  if (strchr (value, ','))  		//multiple networks or hosts
  {
    gint    i;
    gchar **values = g_strsplit (value, ",", 0);

    for (i = 0; values[i] != NULL; i++)
		{
			ossim_debug ("sim_get_inets_from_string: values[%d] = %s", i, values[i]);

      inet = sim_inet_new_from_string (values[i]);
      if (inet)
        list = g_list_append (list, inet);
      else
        ossim_debug ("Error: sim_get_inets_from_string: %s", values[i]);
		}
		g_strfreev (values);
  }
  else 													//uh, just one network or one host.
	{
    inet = sim_inet_new_from_string (value);
    if (inet)
      list = g_list_append (list, inet);
    else
      ossim_debug ("Error: sim_get_inets_from_string: %s", value);
	}

  return list;
}


/* function called by g_hash_table_foreach to add items to a GList */
static void
add_string_key_to_list (gpointer key, gpointer value, gpointer user_data)
{
	// unused parameter
	(void) value;

        GList **list = (GList **) user_data;

        *list = g_list_append (*list, g_strdup (key));
}

/**
 * sim_string_hash_to_list
 */
GList *
sim_string_hash_to_list (GHashTable *hash_table)
{
	GList *list = NULL;

        g_return_val_if_fail (hash_table != NULL, NULL);

        g_hash_table_foreach (hash_table, (GHFunc) add_string_key_to_list, &list);
        return list;
}

/**
 * sim_file_load
 * @filename: path for the file to be loaded.
 *
 * Loads a file, specified by the given @uri, and returns the file
 * contents as a string.
 *
 * It is the caller's responsibility to free the returned value.
 *
 * Returns: the file contents as a newly-allocated string, or NULL
 * if there is an error.
 */
gchar *
sim_file_load (const gchar *filename)
{
  gchar *retval = NULL;
  gsize length = 0;
  GError *error = NULL;
  
  g_return_val_if_fail (filename != NULL, NULL);
  
  if (g_file_get_contents (filename, &retval, &length, &error))
    return retval;
  
  g_message ("Error while reading %s: %s", filename, error ? error->message : "");
	if (error)
	  g_error_free (error);
  
  return NULL;
}

/**
 * sim_file_save
 * @filename: path for the file to be saved.
 * @buffer: contents of the file.
 * @len: size of @buffer.
 *
 * Saves a chunk of data into a file.
 *
 * Returns: TRUE if successful, FALSE on error.
 */
gboolean
sim_file_save (const gchar *filename, const gchar *buffer, gint len)
{
  gint fd;
  gint res;
  
  g_return_val_if_fail (filename != NULL, FALSE);
  
  fd = open (filename, O_RDWR | O_CREAT, 0644);
  if (fd == -1) {
    g_message ("Could not create file %s", filename);
    return FALSE;
  }
  
  res = write (fd, (const void *) buffer, len);
  close (fd);
  
  return res == -1 ? FALSE : TRUE;
}

/**
 *
 *
 *
 *
 *
 */
gulong
sim_inetaddr_aton (GInetAddr     *ia)
{
  struct   in_addr in;
  gchar   *ip;
  gulong   val = -1;

  g_return_val_if_fail (ia, -1);

  if (!(ip = gnet_inetaddr_get_canonical_name (ia)))
    return -1;

  if (inet_aton (ip, &in)) val = in.s_addr;

  g_free (ip);

  return val;
}

/**
 *
 *
 * Transforms a GInetAddr into an unsigned long.
 *
 *
 */
inline gulong
sim_inetaddr_ntohl (GInetAddr     *ia)
{
  struct   in_addr in;
  gchar   *ip;
  gulong   val = -1;

  g_return_val_if_fail (ia, -1);

  if (!(ip = gnet_inetaddr_get_canonical_name (ia)))
    return -1;

  if (inet_aton (ip, &in))
		val = g_ntohl (in.s_addr);

  g_free (ip);

  return val;
}

/**
 * sim_string_to_inet_db_string:
 * @ip: a string whith ip address.
 *
 * Returns: ip string in db format.
 */
gchar *
sim_string_to_inet_db_string (const gchar *ip)
{
  SimInet *inet;
  gchar   *bytes_str;

  g_return_val_if_fail (ip != NULL, NULL);

  inet = sim_inet_new_from_string (ip);

  bytes_str = g_strdup (sim_inet_get_db_string (inet));

  g_object_unref (inet);

  return bytes_str;
}


/*
 * Transforms a gchar * (i.e. 192.168.1.1) into an unsigned long
 */
inline gulong
sim_ipchar_2_ulong (gchar     *ip)
{
  struct   in_addr in;
  gulong   val = -1;

  if (inet_aton (ip, &in))
		val = g_ntohl (in.s_addr);

  return val;
}

static guint8
hex_to_uint4 (char hex)
{
	return ('0' <= hex && hex <= '9') ? hex - '0' : hex - 'A' + 10;
}

/*
static char
uint4_to_hex (guint8 byte)
{
  return (byte < 10) ? byte + '0' : byte - 10 + 'a';
}
*/

gchar *
sim_mac_to_db_string (const gchar *mac)
{
	return g_strdup_printf ("0x%c%c%c%c%c%c%c%c%c%c%c%c",
													mac[0], mac[1], mac[3], mac[4], mac[6], mac[7],
												 	mac[9], mac[10], mac[12], mac[13], mac[15], mac[16]);
}

guint8 *
sim_mac_to_bin (const gchar *mac)
{
	guint8 *ret;

	ret = g_new (guint8, 6);

	ret[0] = (hex_to_uint4 (g_ascii_toupper (mac[0])) << 4) + hex_to_uint4 (g_ascii_toupper (mac[1]));
	ret[1] = (hex_to_uint4 (g_ascii_toupper (mac[3])) << 4) + hex_to_uint4 (g_ascii_toupper (mac[4]));
	ret[2] = (hex_to_uint4 (g_ascii_toupper (mac[6])) << 4) + hex_to_uint4 (g_ascii_toupper (mac[7]));
	ret[3] = (hex_to_uint4 (g_ascii_toupper (mac[9])) << 4) + hex_to_uint4 (g_ascii_toupper (mac[10]));
	ret[4] = (hex_to_uint4 (g_ascii_toupper (mac[12])) << 4) + hex_to_uint4 (g_ascii_toupper (mac[13]));
	ret[5] = (hex_to_uint4 (g_ascii_toupper (mac[15])) << 4) + hex_to_uint4 (g_ascii_toupper (mac[16]));

	return ret;
}

gchar *
sim_bin_to_mac (const guint8 *bin)
{
	return g_strdup_printf ("%02X:%02X:%02X:%02X:%02X:%02X",
													bin[0], bin[1], bin[2], bin[3], bin[4], bin[5]);
}

/*
 * Check if all the characters in the given string are numbers, so we can transform
 * that string into a number if we want, or whatever.
 * The parameter may_be_float tell us if we have to check also if it's a
 * floating number, checking one "." in the string
 * may_be_float = 0 means no float.
 */
inline gboolean
sim_string_is_number (gchar *string,
                      gboolean may_be_float)
{
	size_t n;
	gboolean ok = FALSE;
  int count = 0;

	if (!string || !strcmp(string,"."))
		return FALSE;

	if (g_str_has_prefix (string, "-") || g_str_has_prefix (string, "+"))
		n = 1;
		//ossim_debug ( "sim_string_is_number: has prefix");
	else
		n = 0;

  //ossim_debug ( "sim_string_is_number string: %s", string);

	for (; n < strlen(string); n++)
	{
    //ossim_debug ( "sim_string_is_number: %c", string[n]);
	  if (g_ascii_isdigit (string[n]))
		{
	    ok=TRUE;
    	//ossim_debug ( "sim_string_is_number: OK 1");
		}
	  else
    if (may_be_float && count == 0 && (string[n] == '.' || string[n]==','))
    {
      count++;
      ok = TRUE;
      //ossim_debug ( "sim_string_is_number: OK 2");
    }
		else
	  {
    	//ossim_debug ( "sim_string_is_number: FALSE");
	    ok = FALSE;
	    break;
	  }
	}
	return ok;
}

/*
 * Check if exists and remove all the appearances of the character from a string.
 * A pointer to the same string is returned to allow nesting (if needed).
 */
inline gchar *
sim_string_remove_char	(gchar *string,
													gchar c)
{
	if (!string)
		return FALSE;

	gchar *s = string;

	while ((s = strchr (s, c)) != NULL)
		memmove (s, s+1, strlen (s));

	return string;
}

/*
 * Check if exists and substitute all the appearances of c_orig in the string,
 * with the character c_dest.
 * A pointer to the same string is returned.
 */
inline gchar *
sim_string_substitute_char(gchar *string, gchar c_orig, gchar c_dest)
{
	if (!string)
		return FALSE;

	gchar *s = string;

	while ((s = strchr (s, c_orig)) != NULL)
		*s = c_dest;

	return string;
}


/*
 * Substitute for g_strv_length() as it's just supported in some environments
 */
guint 
sim_g_strv_length (gchar **str_array)
{
	  guint i = 0;
	  g_return_val_if_fail (str_array != NULL, 0);

	  while (str_array[i])
			++i;

	  return i;
}

/*
 * Check if exists and substitute all the appearances of s_orig in the string,
 * with the s_dest string.
 * A pointer to the same string is returned.
 * FIXME: This is a slow function, this should be done in other place or with other approach
 * MODIFIED
 */
gchar *
sim_string_substitute_with_string (gchar * src, const gchar * s_orig, const gchar * s_dest)
{
  gchar * occur_src   = NULL, * head = src, * new_str = NULL;
  gint    src_len     = 0;
  gint    s_orig_len  = 0;
  gint    s_dest_len  = 0;
  gint    new_str_len = 0;
  gint    offset_new  = 0;

  if ((src) && (s_orig) && (s_dest))
  {
    while ((occur_src = g_strstr_len(head, -1, s_orig)) != NULL)
    {
      if (!new_str)
      {
        src_len = strlen(src);
        s_orig_len = strlen(s_orig);
        s_dest_len = strlen(s_dest);

        /* Allocate enough memory to avoid continuous reallocating. */
        new_str_len = ((src_len / s_orig_len) * s_dest_len) + 1;
        new_str = g_new0(gchar, new_str_len);
      }

      /* Copy src head and substitution string to array. */
      memcpy(new_str + offset_new, head, (occur_src - head));
      offset_new += occur_src - head;
      memcpy(new_str + offset_new, s_dest, s_dest_len);
      offset_new += s_dest_len;

      head = occur_src + s_orig_len;
    }

    if (new_str)
    {
      /* Copy the last part if needed. */
      if ((head - src) < src_len)
      {
        memcpy(new_str + offset_new, head, (src_len - (head - src)) + 1);
        offset_new += src_len - (head - src) + 1;
      }

      /* If there is wasted memory, cut at the end. */
      if (offset_new < new_str_len)
        new_str = g_renew(gchar, new_str, offset_new);
    }
    else
    {
      /* No modifications at all */
      new_str = g_strdup(src);
    }
  }

  return (new_str);
}


gchar *
sim_buffer_sanitize (gchar *buffer)
{
  if (!buffer)
    return NULL;

  gchar * ret = g_strdup (buffer), * aux;
  const gchar * orig [] = {"\\", "%"};
  const gchar * subst [] = {"\\\\\\", "%%"};
  const gint len = 2;
  gint i;

  for (i = 0; i < len; i++)
  {
    aux = sim_string_substitute_with_string (ret, orig[i], subst [i]);
    g_free (ret);
    ret = aux;
  }

  return ret;
}

/*
 * Arguments:
 * GList: list of gchar*
 * string: string to check.
 *
 * this function will take a glist and will check if the string is any of the strings inside the GList
 * If the string is "ANY", any of the strings inside GList will match.
 *
 * Warning: Please, use this function just to check gchar's. Any other use will be very probably a segfault.
 */
gboolean
sim_cmp_list_gchar (GList *list, gchar *string)
{
	gchar *cmp;
	while (list)
	{
      cmp = (gchar *) list->data;
      if ((cmp && string && !g_ascii_strcasecmp (cmp, SIM_WILDCARD_ANY)) ||    // Match any non NULL string
          (cmp && !string && !g_ascii_strcasecmp (cmp, SIM_WILDCARD_EMPTY)) || // Match any NULL string
          (cmp && string && !g_ascii_strcasecmp (cmp, string)))                // Compare two strings.
        return TRUE;							//found!
      list = list->next;
	}
	return FALSE;
	
}

/*
 *
 * 
 *
 *dentro de hostmac:
 * sim_event_counter(event->time, SIM_COMMAND_SYMBOL_HOST_MAC_EVENT, event->sensor);
 */
/*

 * BASE64 encoding to send data over the network.
 * _in: src buffer 
 * inlen: strlen (src buffer)
 * _out: dst buffer (reserved outside this function). This is where the base64 string will be stored.
 * outmax: max size of the dst buffer to avoid overflows
 * outlen: modified bytes (not needed to perform the encode, just information)
 *
 */
//FIXME: Remove outlen and subtitute it with a return?
gboolean sim_base64_encode (gchar *_in, 
														guint inlen,
														gchar *_out,
														guint outmax,
														guint *outlen)
{
	g_return_val_if_fail (_in, FALSE);

  const guchar *in = (const guchar *) _in;
  guchar  *out = (guchar *) _out;
  guchar   oval;
  guint   olen;

	// unused parameter
	(void) outlen;

   olen = (inlen + 2) / 3 * 4;
//   if (outlen)
//       *outlen = olen;
   if (outmax < olen)
       return FALSE;

   while (inlen >= 3)
   {
       *out++ = base64_table[in[0] >> 2];
       *out++ = base64_table[((in[0] << 4) & 0x30) | (in[1] >> 4)];
       *out++ = base64_table[((in[1] << 2) & 0x3c) | (in[2] >> 6)];
       *out++ = base64_table[in[2] & 0x3f];
       in += 3;
       inlen -= 3;
   }
   if (inlen > 0)
   {
       *out++ = base64_table[in[0] >> 2];
       oval = (in[0] << 4) & 0x30;
       if (inlen > 1)
           oval |= in[1] >> 4;
       *out++ = base64_table[oval];
       *out++ = (inlen < 2) ? '=' : base64_table[(in[1] << 2) & 0x3c];
       *out++ = '=';
   }

   if (olen < outmax)
       *out = '\0';

   return TRUE;

}

/*
 * BASE64 decoding to receive data over the network.
 * in: src buffer in BASE64 to decode
 * inlen: strlen (src buffer)
 * out: dst buffer (reserved outside this function). This will contain the data in clear.
 * outlen: number of modified bytes (just information)
 *
 */
gboolean sim_base64_decode(	gchar *in,
														guint inlen, 
														gchar *out, 
														guint *outlen)
{
   guint        len = 0,
                   lup;
   gint            c1,
                   c2,
                   c3,
                   c4;



   if (in[0] == '+' && in[1] == ' ')
       in += 2;

   if (*in == '\0')
       return FALSE; 

   for (lup = 0; lup < inlen / 4; lup++)
   {
       c1 = in[0];
       if (CHAR64(c1) == -1)
           return FALSE;
       c2 = in[1];
       if (CHAR64(c2) == -1)
           return FALSE;
       c3 = in[2];
       if (c3 != '=' && CHAR64(c3) == -1)
           return FALSE;
       c4 = in[3];
       if (c4 != '=' && CHAR64(c4) == -1)
           return FALSE;
       in += 4;
       *out++ = (CHAR64(c1) << 2) | (CHAR64(c2) >> 4);
       ++len;
       if (c3 != '=')
       {
           *out++ = ((CHAR64(c2) << 4) & 0xf0) | (CHAR64(c3) >> 2);
           ++len;
           if (c4 != '=')
           {
               *out++ = ((CHAR64(c3) << 6) & 0xc0) | CHAR64(c4);
               ++len;
           }
       }
   *outlen = len;
   }

   *out = 0;
   return TRUE;

}


// As BSD hasn't got strnlen, we copy here the strnlen from libc

/* Find the length of STRING, but scan at most MAXLEN characters.
   Copyright (C) 1991, 1993, 1997, 2000, 2001 Free Software Foundation, Inc.
   Contributed by Jakub Jelinek <jakub@redhat.com>.

   Based on strlen written by Torbjorn Granlund (tege@sics.se),
   with help from Dan Sahlin (dan@sics.se);
   commentary by Jim Blandy (jimb@ai.mit.edu).

   The GNU C Library is free software; you can redistribute it and/or
   modify it under the terms of the GNU Lesser General Public License as
   published by the Free Software Foundation; either version 2.1 of the
   License, or (at your option) any later version.

   The GNU C Library is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
   Lesser General Public License for more details.

   You should have received a copy of the GNU Lesser General Public
   License along with the GNU C Library; see the file COPYING.LIB.  If not,
   write to the Free Software Foundation, Inc., 59 Temple Place - Suite 330,
   Boston, MA 02111-1307, USA.  */


/* Find the length of S, but scan at most MAXLEN characters.  If no
   '\0' terminator is found in that many characters, return MAXLEN.  */
size_t
sim_strnlen (const char *str, size_t maxlen)
{
  const char *char_ptr, *end_ptr = str + maxlen;
  const unsigned long int *longword_ptr;
  unsigned long int longword, himagic, lomagic;

  if (maxlen == 0)
    return 0;

  if (__builtin_expect (end_ptr < str, 0))
    end_ptr = (const char *) ~0UL;

  /* Handle the first few characters by reading one character at a time.
     Do this until CHAR_PTR is aligned on a longword boundary.  */
  for (char_ptr = str; ((unsigned long int) char_ptr
			& (sizeof (longword) - 1)) != 0;
       ++char_ptr)
    if (*char_ptr == '\0')
      {
	if (char_ptr > end_ptr)
	  char_ptr = end_ptr;
	return char_ptr - str;
      }

  /* All these elucidatory comments refer to 4-byte longwords,
     but the theory applies equally well to 8-byte longwords.  */

  longword_ptr = (unsigned long int *) char_ptr;

  /* Bits 31, 24, 16, and 8 of this number are zero.  Call these bits
     the "holes."  Note that there is a hole just to the left of
     each byte, with an extra at the end:

     bits:  01111110 11111110 11111110 11111111
     bytes: AAAAAAAA BBBBBBBB CCCCCCCC DDDDDDDD

     The 1-bits make sure that carries propagate to the next 0-bit.
     The 0-bits provide holes for carries to fall into.  */
  himagic = 0x80808080L;
  lomagic = 0x01010101L;
  if (sizeof (longword) > 4)
    {
      /* 64-bit version of the magic.  */
      /* Do the shift in two steps to avoid a warning if long has 32 bits.  */
      himagic = ((himagic << 16) << 16) | himagic;
      lomagic = ((lomagic << 16) << 16) | lomagic;
    }
  if (sizeof (longword) > 8)
    abort ();

  /* Instead of the traditional loop which tests each character,
     we will test a longword at a time.  The tricky part is testing
     if *any of the four* bytes in the longword in question are zero.  */
  while (longword_ptr < (unsigned long int *) end_ptr)
    {
      /* We tentatively exit the loop if adding MAGIC_BITS to
	 LONGWORD fails to change any of the hole bits of LONGWORD.

	 1) Is this safe?  Will it catch all the zero bytes?
	 Suppose there is a byte with all zeros.  Any carry bits
	 propagating from its left will fall into the hole at its
	 least significant bit and stop.  Since there will be no
	 carry from its most significant bit, the LSB of the
	 byte to the left will be unchanged, and the zero will be
	 detected.

	 2) Is this worthwhile?  Will it ignore everything except
	 zero bytes?  Suppose every byte of LONGWORD has a bit set
	 somewhere.  There will be a carry into bit 8.  If bit 8
	 is set, this will carry into bit 16.  If bit 8 is clear,
	 one of bits 9-15 must be set, so there will be a carry
	 into bit 16.  Similarly, there will be a carry into bit
	 24.  If one of bits 24-30 is set, there will be a carry
	 into bit 31, so all of the hole bits will be changed.

	 The one misfire occurs when bits 24-30 are clear and bit
	 31 is set; in this case, the hole at bit 31 is not
	 changed.  If we had access to the processor carry flag,
	 we could close this loophole by putting the fourth hole
	 at bit 32!

	 So it ignores everything except 128's, when they're aligned
	 properly.  */

      longword = *longword_ptr++;

      if ((longword - lomagic) & himagic)
	{
	  /* Which of the bytes was the zero?  If none of them were, it was
	     a misfire; continue the search.  */

	  const char *cp = (const char *) (longword_ptr - 1);

	  char_ptr = cp;
	  if (cp[0] == 0)
	    break;
	  char_ptr = cp + 1;
	  if (cp[1] == 0)
	    break;
	  char_ptr = cp + 2;
	  if (cp[2] == 0)
	    break;
	  char_ptr = cp + 3;
	  if (cp[3] == 0)
	    break;
	  if (sizeof (longword) > 4)
	    {
	      char_ptr = cp + 4;
	      if (cp[4] == 0)
		break;
	      char_ptr = cp + 5;
	      if (cp[5] == 0)
		break;
	      char_ptr = cp + 6;
	      if (cp[6] == 0)
		break;
	      char_ptr = cp + 7;
	      if (cp[7] == 0)
		break;
	    }
	}
      char_ptr = end_ptr;
    }

  if (char_ptr > end_ptr)
    char_ptr = end_ptr;
  return char_ptr - str;
}

gchar*
sim_normalize_host_mac (gchar *old_mac)
{
  // if size OK, just put MAC to uppercase
	regex_t compre;
  if (strlen(old_mac) == 17)
  {
    return g_ascii_strup(old_mac, -1);
  }

    if(regcomp(&compre, "^([[:xdigit:]]{1,2}):([[:xdigit:]]{1,2}):([[:xdigit:]]{1,2}):([[:xdigit:]]{1,2}):([[:xdigit:]]{1,2}):([[:xdigit:]]{1,2})$", REG_EXTENDED) != 0)
  {
    ossim_debug ( "sim_normalize_host_mac: Failed regcomp");
    return NULL;
  }

  size_t nmatch = compre.re_nsub + 1;
  regmatch_t *pmatch = g_new(regmatch_t, nmatch);

  int match = regexec(&compre, old_mac, nmatch, pmatch, 0);
  regfree(&compre);

  if (match != 0)
  {
    ossim_debug ( "sim_session_cmd_host_mac_event: Failed match regexp");
    g_free(pmatch);
    return NULL;
  }

  gchar *good_mac = g_malloc(18);
  gchar *mac = good_mac;
  size_t i;
  for(i=1; i<nmatch; i++)
  {
    int start = pmatch[i].rm_so;
    int end = pmatch[i].rm_eo;
    size_t size = end - start;

    if (size == 1) {
      *mac++ = '0';
      *mac++ = g_ascii_toupper(old_mac[start]);
    }
    else {
      *mac++ = g_ascii_toupper(old_mac[start]);
      *mac++ = g_ascii_toupper(old_mac[start+1]);
    }
    if (i < nmatch-1)
      *mac++ = ':';
  }
  *mac = '\0';
  g_free(pmatch);

  return good_mac;
}

//using jenkin's algorithm. if it gives collisions, use http://www.burtleburtle.net/bob/c/lookup3.c
guint32
sim_string_to_hash (guchar *key, size_t key_len)
{
    uint32_t hash = 0;
    size_t i;

    for (i = 0; i < key_len; i++)
    {
        hash += key[i];
        hash += (hash << 10);
        hash ^= (hash >> 6);
    }
    hash += (hash << 3);
    hash ^= (hash >> 11);
    hash += (hash << 15);
    return hash;
}

//Small Semaphore implementation
GSemaphore* g_semaphore_new_with_value (gint value)
{
/*
	if(!g_thread_supported())
	{
		g_message("gthread not supported!!");
		return NULL;
	}
*/      
	GSemaphore *sema = (GSemaphore *) g_new ( GSemaphore, 1);
	sema->value = value;
	sema->access = g_mutex_new();
	sema->sig = g_cond_new();
	return sema;
}

void g_semaphore_free(GSemaphore* sema)
{
	g_return_if_fail (sema != NULL);
	g_mutex_free (sema->access);
	g_cond_free (sema->sig); 
}

void g_semaphore_up(GSemaphore* sema)
{
	g_return_if_fail (sema != NULL);
	//g_print("Try pop(++) on Semaphore %x with remaining value %d .\n", sema, sema->value);

	g_mutex_lock (sema->access);

	sema->value++;
	g_cond_signal (sema->sig);
	g_mutex_unlock (sema->access);
}

void g_semaphore_down(GSemaphore* sema)
{
	g_return_if_fail (sema != NULL);
	//g_print("Try push(--) on Semaphore %x with remaining value %d .\n", sema, sema->value);

	g_mutex_lock (sema->access);

	while (sema->value<1)
		g_cond_wait (sema->sig,sema->access);
	sema->value--;

	g_mutex_unlock (sema->access);
}

gboolean
sim_util_block_signal(int sig){
	sigset_t sigmask;
	sigemptyset(&sigmask);
	sigaddset(&sigmask,sig);
	gboolean result = TRUE;
	if (pthread_sigmask(SIG_BLOCK,&sigmask,NULL)){
      g_warning ("%s: Error blocking signal",__FUNCTION__);
		result = FALSE;
	}
	return result;
}
gboolean
sim_util_unblock_signal(int sig){
	sigset_t sigmask;
	sigemptyset(&sigmask);
	sigaddset(&sigmask,sig);
	gboolean result = TRUE;
	/* Consume any pending SIGNAL*/
	sigset_t pending;
	sigpending(&pending);
	if (sigismember(&pending,sig)){
		struct timespec nowait = {0,0};
		int res;
		do{
			res = sigtimedwait(&sigmask,NULL,&nowait);
		}while (res == -1 && errno == EINTR);
	}
	if (pthread_sigmask(SIG_UNBLOCK,&sigmask,NULL)){
      g_warning ("%s: Error unblocking signal",__FUNCTION__);
		result = FALSE;
	}
	return result;
}

//sim_is_a_great_than_b (gint *a, gint *b)
inline 
gint sim_is_a_great_than_b (gconstpointer aa, gconstpointer bb)
{
	gint a = GPOINTER_TO_INT (aa);
	gint b = GPOINTER_TO_INT (bb);
	gint r;

	if (a == b)
		r = 0;
	else if (a > b)
		r =  1;
	else /* (a < b) */
		r = -1;
	return r;
}

/*
 * Proudly made by Carlos Dominguez. Please don't delete!
 */
gchar* sim_hidden_password_in_text(gchar* text, gchar* match, gchar end){
	gchar* ret = g_strdup(text);
	gchar* aux = g_strrstr(ret, match);
	gchar* auxend = g_strstr_len(aux, -1, &end);
	if((aux)&&(auxend)){
		aux=aux+strlen(match);
		while(aux!=auxend){
			*aux='X';
			aux++;
		}
	}
    else
    {
      g_free (ret);
      return NULL;
    }
	return ret;
}

/*
 * Parse the dns string and fills the cmd with the values.
 * @return True if all ok, else False.
 */
gboolean sim_util_parse_snort_db_dns(SimCommand *cmd, gchar *db_dns)
{
  /*
   * ossim.dbsnort->_priv->dsn -> readed from xml config file:
   * example:
   * dsn="PORT=3306;USER=root;PASSWORD=passwd;DATABASE=snort;HOST=localhost"
   */

  pcre *re;
  const char* error;
  gint error_offset;
  gint ouput_vector[30];
  gint returncode;

  gchar *regex = "PORT=(?<port>\\d{1,5});.*USER=(?<user>\\w+);.*PASSWORD=(?<passwd>\\w+);.*DATABASE=(?<dbname>\\w+);.*HOST=(?<host>\\w.*)";
  re = pcre_compile(regex, 0, &error, &error_offset, NULL);
  if (!re)
    {
      ossim_debug ( "sim_util_parse_snort_db_dns: PCRE compilation failed at expression offset %d: %s\n",
          error_offset, error);
        return FALSE;
    }
  returncode = pcre_exec(re, NULL, db_dns, strlen(db_dns), 0, 0, ouput_vector, 30);
  if (returncode < 0)
    {
      switch (returncode)
        {
      case PCRE_ERROR_NOMATCH:
        ossim_debug ( "sim_util_parse_snort_db_dns: No match found in text\n");
        break;
      default:
        ossim_debug ( "sim_util_parse_snort_db_dns: Match error:%d\n", returncode);
        break;
        }
      return FALSE;
    }
  else
    {
      ossim_debug ( "sim_util_parse_snort_db_dns: Reg expresion compiled sucessfuly!");
    }
  gint port_position = pcre_get_stringnumber(re,"port");
  gint user_position = pcre_get_stringnumber(re,"user");
  gint passwd_position = pcre_get_stringnumber(re,"passwd");
  gint dbname_position = pcre_get_stringnumber(re,"dbname");
  gint host_position = pcre_get_stringnumber(re,"host");


  if (port_position == PCRE_ERROR_NOSUBSTRING)
    {
      ossim_debug ("sim_util_parse_snort_db_dns: <port> doesn't exist!");
      return FALSE;
    }
  if (user_position == PCRE_ERROR_NOSUBSTRING)
    {
      ossim_debug ("sim_util_parse_snort_db_dns: <user> doesn't exist!");
      return FALSE;
    }
  if (passwd_position == PCRE_ERROR_NOSUBSTRING)
    {
      ossim_debug ("sim_util_parse_snort_db_dns: <passwd> doesn't exist!");
      return FALSE;
    }
  if (dbname_position == PCRE_ERROR_NOSUBSTRING)
    {
      ossim_debug ("sim_util_parse_snort_db_dns: <dbname> doesn't exist!");
      return FALSE;
    }
  if (host_position == PCRE_ERROR_NOSUBSTRING)
    {
      ossim_debug ("sim_util_parse_snort_db_dns: <host> doesn't exist!");
      return FALSE;
    }

  //TODO: Check if valid hostname/ip

  const gchar* portstr;
  const gchar* userstr;
  const gchar* passwdstr;
  const gchar* dbnamestr;
  const gchar* hoststr;
	gint dbport;
  pcre_get_substring(db_dns, ouput_vector, returncode, port_position, &portstr);
  pcre_get_substring(db_dns, ouput_vector, returncode, user_position, &userstr);
  pcre_get_substring(db_dns, ouput_vector, returncode, passwd_position, &passwdstr);
  pcre_get_substring(db_dns, ouput_vector, returncode, dbname_position, &dbnamestr);
  pcre_get_substring(db_dns, ouput_vector, returncode, host_position, &hoststr);
  ossim_debug ("sim_util_parse_snort_db_dns: DSN CONFIG - port:%s, dbname: %s, host: %s",
      portstr,dbnamestr,hoststr);
  cmd->data.snort_database_data.dbhost = g_strdup(hoststr);
  cmd->data.snort_database_data.dbname = g_strdup(dbnamestr);
  dbport = atoi(portstr);
  if (dbport == 0)
    ossim_debug ("sim_util_parse_snort_db_dns: invalid port readed: %s",portstr);
  else if (dbport == INT_MAX || dbport == INT_MIN)
    ossim_debug ("sim_util_parse_snort_db_dns: invalid port readed (INT_MAX or INT_MIN): %s",portstr);
  else
    cmd->data.snort_database_data.dbport = dbport;
  cmd->data.snort_database_data.dbpassword = g_strdup(passwdstr);
  cmd->data.snort_database_data.dbuser = g_strdup(userstr);

  pcre_free_substring(portstr);
  pcre_free_substring(userstr);
  pcre_free_substring(passwdstr);
  pcre_free_substring(dbnamestr);
  pcre_free_substring(hoststr);
  pcre_free(re);



  return TRUE;
}

/*
 * Convert an hexadecimal string to gchar.
 * Non-visible characters are showed in hexadecimal.
 * This string should be freed after use!
 */
gchar *
sim_convert_hex_to_char (guint8 * data, guint len)
{
	guint str_len = (len * 2) + 1;
	gchar * str = g_new0 (gchar, str_len);
	guint i = 0;
	gint j = 0;

  while (i < len)
	{
		if (data[i] > 127)
			j += g_sprintf((str + j), "%02x", data[i]);
		else
		{
			str[j] = (gchar)data[i];
			j++;
		}
		i++;
	}

	str[j] = '\0';
	return (str);
}

/**
 * sim_log_xml_handler:
 * @ctx_ptr: a pointer to a xmlParserCtxtPtr structure.
 * @msg: a default error message.
 *
 * Handles errors from xml context processing.
 * FIXME: this does *nothing*, as libxml2 error management
 * is just terrible when it comes to user defined handler functions.
 */
inline
void
sim_log_xml_handler (gpointer ctx_ptr, const gchar * message, ...)
{
	// unused parameter
	(void) ctx_ptr;
	(void) message;

	//xmlParserCtxtPtr ctx = (xmlParserCtxtPtr) ctx_ptr;
	//xmlErrorPtr      error = xmlGetLastError ();

	//g_debug ("%s", message);
	return;
}

/**
	* @brief Check if the string passed is a valid IPv4 or IPv6
	*
	* @param st
	*
	* @return TRUE is valid IP, FALSE otherwise
	*/
inline
gboolean sim_check_is_valid_ip (const gchar *st){
	gboolean result = TRUE;
	struct sockaddr_in saipv4;
	struct sockaddr_in6 saipv6;
	if (inet_pton (AF_INET6, st, &saipv6) != 1){
		if (inet_pton (AF_INET, st,&saipv4) != 1){
			result = FALSE;
		}
	
	}
	return result;
}

/*
 * @brief Check if this is a IPv4

 * @param st Pointer to a string to verify

   @return TRUE is this is a IPv4 false if not
*/
inline
gboolean sim_check_is_ipv4 (const char *st){
	struct sockaddr_in saipv4;
	gboolean result = FALSE;
	if (inet_pton (AF_INET, st, &saipv4) == 1)
		result = TRUE;
	return result;
}

/*
 * @brief Check if this is a IPv6

 * @param st Pointer to a string to verify

   @return TRUE is this is a IPv6 false if not
*/
inline
gboolean sim_check_is_ipv6 (const char *st){
	struct sockaddr_in saipv6;
	gboolean result = FALSE;
	if (inet_pton (AF_INET6, st, &saipv6) == 1)
		result = TRUE;
	return result;
}

/**
	* @brief This functions verify a NULL ending list os IP/mask values. Return TRUE is all
	entries are in the form A.B.C.D/mask
	
	* @param st Array of pointer NULL terminated with IPs's
*/

gboolean sim_util_check_ip_array (gchar **array){
	gboolean result = TRUE;
	gchar *ip;
	gchar *slash; 
	gchar *temp;
	unsigned long mask;
	gchar *end;

  g_return_val_if_fail (array, FALSE);

	while ((ip = *array) != NULL){
		if ( (slash = strchr (ip, '/')) == NULL)
			break;
		*slash = '\0';
		slash++;
		if (!sim_check_is_valid_ip (ip)){
			result = FALSE;
			break;
		}
		/* Verify the mask */	
		temp = slash;
		while (*temp != 0 && result  == TRUE){
				if (!g_ascii_isdigit (*temp))
					result = FALSE;
				temp++;
		}
		if (!result) break;
		/* Ok is a number */
		mask = strtoul (slash, &end,10);
		if (errno != 0 || *end != '\0'){
			result = FALSE;
			break;
		}
		/* Ok verify now the mask len */
		if (sim_check_is_ipv4 (ip)){
			if (mask>32){
				result = FALSE;
				break;
			}
		}else{
			if (mask>128){
				result = FALSE;
				break;
			}
		}
		
		array++;	
	}
	return result;
}

/*
 * Parse command line options.
 */
void
sim_options (int argc, char **argv)
{
  gchar * desc, * help_msg;
  GError * error = NULL;
  GOptionContext * context;

  /* Default Command Line Options */
  static gboolean version = FALSE;

  simCmdArgs.config = NULL;
  simCmdArgs.daemon = FALSE;
  simCmdArgs.debug = 4;
  simCmdArgs.ip = NULL;
#ifdef USE_UNITTESTS
  simCmdArgs.unittests = 0;
  simCmdArgs.unittest_regex = NULL;
#endif
  simCmdArgs.port = 0;
  simCmdArgs.check_siem = FALSE;

  static GOptionEntry options[] = 
  {
    { "version", 'v', 0, G_OPTION_ARG_NONE, &version, "Show version number", NULL },
    { "config", 'c', 0, G_OPTION_ARG_STRING, &simCmdArgs.config, "Default config file is /etc/ossim/server/config.xml", "config file" },
    { "daemon", 'd', 0, G_OPTION_ARG_NONE, &simCmdArgs.daemon, "Run as daemon", NULL },
#ifdef USE_UNITTESTS
    {"unittests", 't', 0, G_OPTION_ARG_NONE, &simCmdArgs.unittests, "Run the registered unittests and exit", NULL},
    {"unittest_regex", 'T', 0, G_OPTION_ARG_STRING, &simCmdArgs.unittest_regex, "Run the registered unittests that match the regex and exit", "unittest_regex"},
#endif
    { "debug", 'D', 0, G_OPTION_ARG_INT, &simCmdArgs.debug, "Run in debug mode (level 6 is very useful)", "L" },
    { "interfaceip", 'i', 0, G_OPTION_ARG_STRING, &simCmdArgs.ip,
			"IP address of the interface connected to agents (where the server should listen)", "ip" },
    { "port", 'p', 0, G_OPTION_ARG_INT, &simCmdArgs.port, "Port number the server will listen on", "port" },
    { "dvl", 'q', 0, G_OPTION_ARG_INT, &simCmdArgs.dvl, "", "num" },
    { "chk_siem", 's', 0, G_OPTION_ARG_NONE, &simCmdArgs.check_siem, "Check inconsistences in SIEM tables", NULL },
    { NULL, '\0', 0, G_OPTION_ARG_NONE, NULL, NULL, NULL }
  };

  context = g_option_context_new (
#ifdef DEMO
    "- Alienvault DEMO SIEM."
#elif defined CHINESE_ENCODING
    "- Alienvault (Encode) SIEM."
#else
    "- Alienvault SIEM."
#endif
    );

  desc = g_strdup_printf(
#ifdef DEMO
    "\nAlienvault DEMO SIEM. version: %s \n\n \t(c) 2007-2013 AlienVault\n",
#elif defined CHINESE_ENCODING
    "Alienvault OSSIM Server (Encode) Version : %s\n  (c) 2007-2013 AlienVault\n",
#else
    "\nAlienvault SIEM. version: %s \n\n \t(c) 2007-2013 AlienVault\n",
#endif
    ossim.version);
  g_option_context_set_description(context, desc);
  g_free(desc);

  g_option_context_set_help_enabled (context, TRUE);
  g_option_context_add_main_entries(context, options, NULL);
  g_option_context_parse(context, &argc, &argv, &error);

  if (error)
  {
		help_msg = g_option_context_get_help (context, FALSE, NULL);
    g_message ("Unknown option, please check your command.\n");
		g_print ("%s", help_msg);

		g_free (help_msg);
		g_option_context_free(context);
    exit (EXIT_FAILURE);
  }
  g_option_context_free(context);

  /* Show version number */
  if (version)
  {
#ifdef DEMO
    g_print ("Alienvault DEMO. OSSIM Server Version : %s\n  (c) 2007-2013 AlienVault\n", ossim.version);
#elif CHINESE_ENCODING
    g_print ("Alienvault OSSIM Server (Encode) Version : %s\n  (c) 2007-2013 AlienVault\n", ossim.version);
#else
    g_print ("Alienvault OSSIM Server Version : %s\n  (c) 2007-2013 AlienVault\n", ossim.version);
#endif
    exit (EXIT_SUCCESS);
  }

  /* Config file settings */
  if ((simCmdArgs.config) && 
      !g_file_test (simCmdArgs.config, G_FILE_TEST_EXISTS))
  {
    g_critical ("Config XML File '%s' does not exist", simCmdArgs.config);
  }

  /* Debug mode */
  if ((simCmdArgs.debug < 0) || 
      (simCmdArgs.debug > 6))
  {
    g_critical ("Debug level %d: Is invalid", simCmdArgs.debug);
    exit (EXIT_FAILURE);
  }

  /* Last but not least, daemonize */
  if (simCmdArgs.daemon) 
  {
    g_message ("Entering daemon mode...");
    //daemon_mode (); 
    if (fork ())
      exit (0);
  }

  return;
}

/*
 *
 * Saves the pid in a hardcoded (brr) place
 *
 */

void
sim_pid_init(void)
{
  int fd_pid;
  if ((fd_pid = open (OS_SIM_RUN_FILE, O_WRONLY|O_CREAT|O_TRUNC, S_IRUSR|S_IWUSR)) < 0)
    g_message ("Can't create %s", OS_SIM_RUN_FILE);
  else
  {
    if (lockf(fd_pid,F_TLOCK,0) < 0 )
      g_message ("Can't lock pid file; may be that another server process is running?");
    else
    {
      int pid_len;
      char *pid_str;
      ssize_t write_ret;

      pid_len = asprintf (&pid_str,"%d\n", getpid());
      if (pid_len > 0)
      {
        write_ret = write (fd_pid, pid_str, pid_len);
        if (write_ret == -1 || write_ret != pid_len)
        {
          g_message ("Can't write to pid file");
        }
        g_free (pid_str);
      }
      else
      {
        g_message ("Can't allocate memory when writting pid file");
      }
    }
    close(fd_pid);
  }
  return;
}

#ifdef CHINESE_ENCODING
gchar *sim_util_strrepl(gchar *string, gchar *substr, gchar *replacement)
{
	gchar *tok = NULL;
	gchar *newstr = NULL;
	gchar *oldstr = NULL;

	/* if either substr or replacement is NULL, duplicate string a let caller handle it */
  if ( substr == NULL || replacement == NULL )
  	return g_strdup (string);

  newstr = g_strdup (string);

  while ( (tok = strstr ( newstr, substr )))
  {
    oldstr = newstr;

    newstr = g_malloc ( strlen ( oldstr ) - strlen ( substr ) + strlen ( replacement ) + 1 );

    /*failed to alloc mem, free old string and return NULL */

    if ( newstr == NULL )
    {
      g_free (oldstr);
      return NULL;
    }

    memcpy ( newstr, oldstr, tok - oldstr );
    memcpy ( newstr + (tok - oldstr), replacement, strlen ( replacement ) );
    memcpy ( newstr + (tok - oldstr) + strlen( replacement ), tok + strlen ( substr ), strlen ( oldstr ) - strlen ( substr ) - ( tok - oldstr ) );
    memset ( newstr + strlen ( oldstr ) - strlen ( substr ) + strlen ( replacement ) , 0, 1 );

    g_free (oldstr);
  }

  return newstr;
}

gchar *sim_util_utf8_to_html(gchar *original)
{
	gchar 			*decoded = NULL;
	gchar				*aux_str = NULL;
	gchar				*aux_str2 = NULL;
	TidyBuffer 	output = {0};
	gint 				rc = -1;

	if(original == NULL)
		return NULL;
	
	TidyDoc tdoc = tidyCreate(); // Initialize "document"

	// Setting options for decoding utf8 -> html
	rc = tidyOptSetBool( tdoc, TidyHtmlOut, yes );
	if(rc)
		rc = tidyOptSetValue( tdoc, TidyBodyOnly, "yes" );
	if(rc)
		rc = tidyOptSetInt( tdoc, TidyWrapLen, 0 );
	if(rc)
		rc = tidyOptSetValue( tdoc, TidyInCharEncoding, "utf8" );
	if(rc)
		rc = tidyOptSetInt( tdoc, TidyShowErrors, 0 );
	if(rc)
		rc = tidyOptSetBool( tdoc, TidyShowWarnings, no );

	if(rc)
	{
		aux_str = sim_util_strrepl(original, "<", "%-@-%");
		aux_str2 = sim_util_strrepl(aux_str, ">", "@-%-@");

		rc = tidyParseString( tdoc, aux_str2 ); // Parse the input

		if(rc >= 0)
	  	rc = tidyCleanAndRepair( tdoc ); // Tidy it up!

		if(rc >= 0)
    	rc = tidySaveBuffer( tdoc, &output ); // Pretty Print

		if(rc >= 0 && output.allocated)
		{
			if(aux_str) g_free(aux_str);
			aux_str = g_strdup((gchar*)output.bp); // Storing result
	  	tidyBufFree( &output );
		}
	}

	tidyRelease( tdoc );

	if(aux_str)
	{
		aux_str[strlen(aux_str) - 1] = '\0';

		if(aux_str2) g_free(aux_str2);
		aux_str2 = sim_util_strrepl(aux_str, "%-@-%", "<");
		decoded = sim_util_strrepl(aux_str2, "@-%-@", ">");
	}

	if(aux_str) g_free(aux_str);
	if(aux_str2) g_free(aux_str2);

	if(decoded)
		return decoded;
	else
	{
		ossim_debug ("%s: error converting string: [%s]", __FUNCTION__, original);
		return original;
	}
}
#endif //CHINESE_ENCODING

/**
 * sim_get_current_date:
 *
 * get current day and current hour
 * calculate date expresion to be able to compare dates
 *
 * for example, fri 21h = ((5 - 1) * 24) + 21 = 117
 *              sat 14h = ((6 - 1) * 24) + 14 = 134
 *
 * tm_wday returns the number of days since Sunday, in the range 0 to 6.
 */
gint
sim_get_current_date (void)
{
  gint date = 0;
  struct tm *loctime;
  time_t curtime;

  curtime = time (NULL);
  loctime = gmtime (&curtime);
  date = (loctime->tm_wday - 1 * 24) + loctime->tm_hour;

  return date;
}
/**
	@brief clean a input string fron \n or \r that can be
	problematic for parsing

	@param bytes String of bytes finished by \0 we're going to modifie

	This function replaces certain bytes in a string by spaces. These are the NULL (0x00), CR (0x0D) and LF (0x0A).
	As both characters have binary cofication  in  UTF-8 and US-ASCII, 

	0x00 => 0x20
	0x0A => 0x20
	0x0D => 0x20
	
	Return a pointer to a new allocated string if there is any substitution
*/	

gchar *
sim_util_substite_problematic_chars (const gchar *p_in, gsize len)
{
	gchar *p;
  gchar *ret = NULL;
  gsize i;

	p = (gchar *) p_in;

  if (p != NULL)
		for (i = 0; i < len; i++)
      if (p[i] == 0x00 || p[i] == 0x0A || p[i] == 0x0D)
      {
        if (ret == NULL)
        {
					ret = g_malloc (len + 1);
					memcpy (ret, p, len);
					ret[len] = '\0';
          p = ret;
        }

        p[i] = 0x20;
      }

  return ret;
}

/**
 * sim_backlog_event_str_from_type:
 *
 */
const gchar *
sim_backlog_event_str_from_type (gint type)
{
  const gchar * SimBacklogEventStr [] = {"none", "start", "end", NULL};
  return (SimBacklogEventStr[type]);
}

/**
 * sim_backlog_event_type_from_str:
 *
 */
gint
sim_backlog_event_type_from_str (gchar * str)
{
  gint i;
  const gchar * SimBacklogEventStr [] = {"none", "start", "end", NULL};
  for (i = 0; (SimBacklogEventStr[i] && g_ascii_strcasecmp (SimBacklogEventStr[i], str)); i++);
  return (SimBacklogEventStr[i] ? i : 0);
}

/**
 * sim_str_escape:
 * @source: const gchar * source string
 * @connection: a GdaConnection
 * @can_have_nulls: positive integer with string length if @source
 *                  can contains NULL characters, 0 otherwise
 *
 * It must be used to escape strings for db inserts
 *
 * Returns: a newly-allocated copy of source with characters escaped.
 */
gchar *
sim_str_escape (const gchar   *source,
                GdaConnection *connection,
								gsize          can_have_nulls)
{
  gchar *escaped_string;

	if (can_have_nulls > 0)
	{
		GString *aux_gstring;
		gchar *auxp, *aux_gchar;
		gsize i;

		aux_gstring = g_string_new ("");
		auxp = (gchar *) source;
		for (i = 0; i < can_have_nulls; i++)
		{
			if (source[i] == '\0')
			{
				aux_gchar = gda_server_provider_escape_string (gda_connection_get_provider (connection), connection, auxp);
				g_string_append (aux_gstring, aux_gchar);
				g_string_append (aux_gstring, "\\0");
				g_free (aux_gchar);
				auxp = (gchar *) source + i + 1;
			}
		}
		aux_gchar = gda_server_provider_escape_string (gda_connection_get_provider (connection), connection, auxp);
		g_string_append (aux_gstring, aux_gchar);
		g_free (aux_gchar);

		escaped_string = g_string_free (aux_gstring, FALSE);
	}
	else
	{
		/* gda_connection_value_to_sql_string is also an option but it returns an quoted string */
		escaped_string = gda_server_provider_escape_string (gda_connection_get_provider (connection), connection, source);
	}

#ifdef CHINESE_ENCODING
	gchar *aux_decoded;

	aux_decoded = sim_util_utf8_to_html (escaped_string);
	g_free (escaped_string);
	escaped_string = aux_decoded;
#endif

  return escaped_string;
}

/**
 * sim_version_match:
 * @a: a #SimVersion structure.
 * @b: a #SimVersion structure.
 *
 * Returns %TRUE if a >= b, %FALSE otherwise.
 */
gboolean
sim_version_match (SimVersion * a, SimVersion * b)
{
  if ((!a) || (!b))
    return (FALSE);

  return ((a->major > b->major) || ((a->major == b->major) && (a->minor > b->minor)) || ((a->major == b->major) && (a->minor == b->minor) && (a->micro >= b->micro)));
}

void
sim_version_parse (const gchar *string, guint8 *major, guint8 *minor, guint8 *micro)
{
  GRegex *regex;
  GMatchInfo *match_info;
  gchar *word;

  *major = 0;
  *minor = 0;
  *micro = 0;

	g_return_if_fail (string);

  regex = g_regex_new ("[0-9]+", 0, 0, NULL);

  g_regex_match (regex, string, 0, &match_info);
  if (g_match_info_matches (match_info))
  {
    word = g_match_info_fetch (match_info, 0);
    *major = g_ascii_strtoull (word, NULL, 10);
    g_free (word);
  }
  else
  {
    goto free;
  }

  g_match_info_next (match_info, NULL);
  if (g_match_info_matches (match_info))
  {
    word = g_match_info_fetch (match_info, 0);
    *minor = g_ascii_strtoull (word, NULL, 10);
    g_free (word);
  }
  else
  {
    goto free;
  }

  g_match_info_next (match_info, NULL);
  if (g_match_info_matches (match_info))
  {
    word = g_match_info_fetch (match_info, 0);
    *micro = g_ascii_strtoull (word, NULL, 10);
    g_free (word);
  }

free:
  g_match_info_free (match_info);
  g_regex_unref (regex);
}

/**
 * sim_parse_month_day:
 * @day: a #guint representating a day of a month.
 * @month: a #guint representating a month.
 * @year: a #guint representating a year.
 *
 * Returns the correct day of the month.
 */
guint
sim_parse_month_day (guint day, guint month, guint year)
{
  static const guint8 days_in_months[2][13] =
    {  /* error, jan feb mar apr may jun jul aug sep oct nov dec */
      {  0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 },
      {  0, 31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 } /* leap year */
    };

  guint leap = (g_date_is_leap_year (year) + 1);

  return (day > days_in_months[leap][month] ? days_in_months[leap][month] : day);
}

gboolean
sim_socket_send_simple (GSocket* socket, const gchar *buffer)
{
	size_t to_send;
	gssize size;
	GError *error = NULL;
	gboolean ret = TRUE;

	g_return_val_if_fail (G_IS_SOCKET (socket), FALSE);
	g_return_val_if_fail (buffer, FALSE);

	to_send = strlen (buffer);
	while (to_send > 0)
	{
		size = g_socket_send (socket, buffer, to_send, NULL, &error);
		if (size == -1 || error)
	  {
			if (error)
			{
				g_message ("%s: g_socket_send error: %s", __func__, error->message);
				g_error_free (error);
			}
			ret = FALSE;
			break;
	  }

		to_send -= size;
		buffer += size;
	}

	return ret;
}

// vim: set tabstop=2 sts=2 noexpandtab:
