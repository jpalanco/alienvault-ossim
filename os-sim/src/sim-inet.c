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

#include "config.h"

#include "sim-inet.h"

#include <string.h>
#include <stdlib.h>
#include <limits.h>
#include <gnet.h>

#include "sim-object.h"
#include "sim-net.h" //needed to know in which SimNet is stored this SimInet
#include "sim-util.h"
#include "sim-unittesting.h"
#include "sim-log.h"

#define MASK_HOST_IPV4 32
#define MASK_HOST_IPV6 128

#define NUM_BYTES_IPV4 4
#define NUM_BYTES_IPV6 16 // == GNET_INETADDR_MAX_LEN

typedef union
{
  guint8  addr8[16];
  guint16 addr16[8];
  guint32 addr32[4];
  guint64 addr64[2];
  gchar   str[GNET_INETADDR_MAX_LEN];

} sim_inet_ip;

struct _SimInetPrivate
{
  GInetAddr   *address;
  guint        mask;
  sim_inet_ip  bytes;

  SimNet      *parent_sim_net;

  gchar       *db_str;
  SimRadixKey *radix_key;

  gboolean     is_none;

  gboolean     is_in_homenet;
  gboolean     homenet_checked;
};

#define SIM_INET_GET_PRIVATE(object) \
  (G_TYPE_INSTANCE_GET_PRIVATE ((object), SIM_TYPE_INET, SimInetPrivate))

SIM_DEFINE_TYPE (SimInet, sim_inet, G_TYPE_OBJECT, NULL)

static void       _sim_inet_make_db_string               (SimInet     *inet);
static void       _sim_inet_make_radix_key               (SimInet     *inet);

/* GType Functions */

static void
sim_inet_finalize (GObject  *gobject)
{
  SimInet *inet = SIM_INET (gobject);

  if (inet->priv->address)
  {
    gnet_inetaddr_unref (inet->priv->address);
    inet->priv->address = NULL;
  }

  if (inet->priv->parent_sim_net)
  {
    g_object_unref (inet->priv->parent_sim_net);
    inet->priv->parent_sim_net = NULL;
  }

  if (inet->priv->db_str)
  {
    g_free (inet->priv->db_str);
    inet->priv->db_str = NULL;
  }

  if (inet->priv->radix_key)
  {
    sim_radix_key_destroy (inet->priv->radix_key);
    inet->priv->radix_key = NULL;
  }

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_inet_class_init (SimInetClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_ref (G_TYPE_OBJECT);

  object_class->finalize = sim_inet_finalize;

  g_type_class_add_private (class, sizeof (SimInetPrivate));
}

static void
sim_inet_instance_init (SimInet *inet)
{
  inet->priv = SIM_INET_GET_PRIVATE (inet);

  inet->priv->address = NULL;
  inet->priv->parent_sim_net = NULL;
  inet->priv->mask = 0;
  inet->priv->db_str = NULL;
  inet->priv->radix_key = NULL;
  inet->priv->bytes.addr64[0] = 0;
  inet->priv->bytes.addr64[1] = 0;
  inet->priv->is_none = FALSE;
  inet->priv->is_in_homenet = FALSE;
  inet->priv->homenet_checked = FALSE;
}

/* Public Methods */

/**
 * sim_inet_new:
 * @ia: pointer to GInetAddr
 * @mask: guint inet mask.
 *
 * Creates a #SimInet object, which can contains a host or a network depending on mask.
 *
 * Returns: new #SimInet object
 */
SimInet *
sim_inet_new (GInetAddr  *ia,
              guint       mask)
{
  SimInet *inet;

  g_return_val_if_fail (ia, NULL);

  inet = SIM_INET (g_object_new (SIM_TYPE_INET, NULL));

  gnet_inetaddr_ref (ia);

  inet->priv->parent_sim_net = NULL;
  inet->priv->mask = mask;
  inet->priv->address = ia;

  if (mask == SIM_INET_HOST)
  {
    if (gnet_inetaddr_is_ipv4 (ia))
      inet->priv->mask = MASK_HOST_IPV4; //it's a ipv4 host (32 mask)
    else if (gnet_inetaddr_is_ipv6 (ia))
      inet->priv->mask = MASK_HOST_IPV6; //it's a ipv6 host (128 mask)
  }

  /* Create db string and radix key */
  _sim_inet_make_db_string (inet);
  _sim_inet_make_radix_key (inet);

  return inet;
}

/**
 * sim_inet_new_from_string:
 * @hostname_ip: gchar * ip string
 *
 * Transforms something like: "192.168.0.1/24" or "192.168.8.9" into a #SimInet object.
 *
 * Returns: new #SimInet object
 */
SimInet *
sim_inet_new_from_string (const gchar *hostname_ip)
{
  SimInet    *inet;
  GInetAddr  *address;
  gchar     **host_split;  // IP/MASK (ie 10.0.0.1/32  or ::10:1/128)
  guint8      mask = SIM_INET_HOST;

  g_return_val_if_fail (hostname_ip, NULL);
  g_return_val_if_fail (strcmp (hostname_ip, "") != 0, NULL);

  /* spilt hostname_ip in ip address and mask */
  host_split = g_strsplit (hostname_ip, SIM_DELIMITER_SLASH, 2);
  if (host_split[0] == NULL)
  {
    ossim_debug ("%s: Error hostname %s not valid", __func__, hostname_ip);
    g_strfreev (host_split);
    return NULL;
  }

  address = gnet_inetaddr_new_nonblock (host_split[0], 0);
  if (address == NULL)
  {
    ossim_debug ("%s: Error hostname %s not valid", __func__, hostname_ip);
    g_strfreev (host_split);
    return NULL;
  }

  if (host_split[1])
  {
    mask = strtol (host_split[1], NULL, 10);
  }

  inet = sim_inet_new (address, mask);

  g_strfreev (host_split);
  gnet_inetaddr_unref (address);

  return inet;
}

/**
 * sim_inet_new_from_db_binary:
 * @db_str: database binary
 *
 * Creates a #SimInet object from database binary.
 *
 * Returns: new #SimInet object
 */
SimInet *
sim_inet_new_from_db_binary (const guchar *db_str, glong size)
{
  SimInet   *inet;
  GInetAddr *addr;

  g_return_val_if_fail (db_str != NULL, NULL);

  addr = gnet_inetaddr_new_bytes ((const gchar *) db_str, size);
  inet = sim_inet_new (addr, SIM_INET_HOST);

  gnet_inetaddr_unref (addr);

  return inet;
}

/**
 * sim_inet_new_none:
 *
 * Creates a new #SimInet object without previous info.
 *
 * Returns: new #SimInet object
 */
SimInet *
sim_inet_new_none ()
{
  SimInet   *inet;
  GInetAddr *addr;

  addr = gnet_inetaddr_new_nonblock (SIM_IN_ADDR_NONE_IP_STR, 0);
  inet = sim_inet_new (addr, SIM_INET_HOST);
  gnet_inetaddr_unref (addr);

  inet->priv->is_none = TRUE;

  return inet;
}

/**
 * sim_inet_clone:
 * @inet: #SimInet object
 *
 * Returns: cloned #SimInet object.
 */
SimInet*
sim_inet_clone (SimInet  *inet)
{
  SimInet *new_inet;

  g_return_val_if_fail (SIM_IS_INET (inet), NULL);

  new_inet = SIM_INET (g_object_new (SIM_TYPE_INET, NULL));
  new_inet->priv->address = gnet_inetaddr_clone (inet->priv->address);
  new_inet->priv->mask = inet->priv->mask;
  new_inet->priv->db_str = g_strdup (inet->priv->db_str);
  new_inet->priv->radix_key = sim_radix_dup_key (inet->priv->radix_key);
  new_inet->priv->bytes.addr64[0] = inet->priv->bytes.addr64[0];
  new_inet->priv->bytes.addr64[1] = inet->priv->bytes.addr64[1];
  new_inet->priv->is_none = inet->priv->is_none;
  new_inet->priv->is_in_homenet = inet->priv->is_in_homenet;
  new_inet->priv->homenet_checked = inet->priv->homenet_checked;

  // Parent Net Reference
  if (new_inet->priv->parent_sim_net != NULL)
    new_inet->priv->parent_sim_net = g_object_ref (inet->priv->parent_sim_net);
  else
    new_inet->priv->parent_sim_net = NULL;

  return new_inet;
}

/**
 * sim_inet_get_mask:
 * @inet: @SimInet object
 *
 * Returns: the @inet mask
 */
gint
sim_inet_get_mask (SimInet  *inet)
{
  g_return_val_if_fail (SIM_IS_INET (inet), -1);

  return inet->priv->mask;
}

/**
 * sim_inet_set_mask:
 * @inet: @SimInet object
 * @mask: guint mask
 *
 * Sets the @inet mask
 */
void
sim_inet_set_mask (SimInet  *inet,
                   guint     mask)
{
  g_return_if_fail (SIM_IS_INET (inet));

  inet->priv->mask = mask;
}

/**
 * sim_inet_set_parent_net:
 * @inet: #SimInet object
 * @net: #SimNet object
 *
 * Sets the @net as the parent net of @inet
 */
void
sim_inet_set_parent_net (SimInet  *inet,
                         SimNet   *net)
{
  g_return_if_fail (SIM_IS_INET (inet));
  g_return_if_fail (SIM_IS_NET (net));

  inet->priv->parent_sim_net = g_object_ref (net);
}

/**
 * sim_inet_get_parent_net:
 * @inet: #SimInet object
 *
 * Returns the parent #SimNet object of @inet
 */
SimNet *
sim_inet_get_parent_net (SimInet  *inet)
{
  g_return_val_if_fail (SIM_IS_INET (inet), NULL);

  return inet->priv->parent_sim_net;
}

/**
 * sim_inet_get_address:
 * @inet: #SimInet object
 *
 * Returns: @inet address
 */
GInetAddr *
sim_inet_get_address (SimInet *inet)
{
  g_return_val_if_fail (SIM_IS_INET (inet), NULL);

  return inet->priv->address;
}

/**
 * sim_inet_noport_equal:
 * @inet1: #SimInet object
 * @inet2: #SimInet object
 *
 * Returns: %TRUE if @inet1 and @inet2 have the same address
 * ignoring the port.
 */
gboolean
sim_inet_noport_equal (SimInet   *inet1,
                       SimInet   *inet2)
{
  g_return_val_if_fail (SIM_IS_INET (inet1), FALSE);
  g_return_val_if_fail (SIM_IS_INET (inet2), FALSE);

  if (inet1->priv->bytes.addr64[0] == inet2->priv->bytes.addr64[0] &&
      inet1->priv->bytes.addr64[1] == inet2->priv->bytes.addr64[1])
  {
    return TRUE;
  }
  else
  {
    return FALSE;
  }
}

/**
 * sim_inet_is_none:
 * @inet: #SimInet object
 *
 * Returns: %TRUE if @inet address is NONE
 */
gboolean
sim_inet_is_none (SimInet *inet)
{
  g_return_val_if_fail (SIM_IS_INET (inet), FALSE);

  return inet->priv->is_none;
}

/**
 * sim_inet_is_loopback:
 * @inet: #SimInet object
 *
 * Returns: %TRUE if @inet address is loopback
 */
gboolean
sim_inet_is_loopback (SimInet *inet)
{
  g_return_val_if_fail (SIM_IS_INET (inet), FALSE);

  return (gnet_inetaddr_is_loopback (inet->priv->address));
}

/**
 * sim_inet_get_canonical_name:
 * @inet: #SimInet object
 *
 * Returns: @inet canonical name. It must be freed outside
 */
gchar *
sim_inet_get_canonical_name (SimInet  *inet)
{
  g_return_val_if_fail (SIM_IS_INET (inet), NULL);

  return (gnet_inetaddr_get_canonical_name (inet->priv->address));
}

/**
 * sim_inet_get_cidr:
 * @inet: #SimInet object
 *
 * Returns: @inet cidr string address/mask (i.e. "192.168.1.1/24")
 * It must be freed outside.
 */
gchar *
sim_inet_get_cidr (SimInet  *inet)
{
  gchar *ret = NULL;
  gchar *canonical_name;

  g_return_val_if_fail (SIM_IS_INET (inet), NULL);

  canonical_name = gnet_inetaddr_get_canonical_name (inet->priv->address);
  ret = g_strdup_printf ("%s/%d", canonical_name, inet->priv->mask);
  g_free (canonical_name);

  return ret;
}

/**
 * sim_inet_debug_print:
 * @inet: #SimInet object
 *
 * Prints @inet content
 */
void
sim_inet_debug_print (SimInet   *inet)
{
  g_return_if_fail (SIM_IS_INET (inet));

  gchar *temp = sim_inet_get_cidr (inet);
  ossim_debug ("%s", temp);
  g_free(temp);
}

/**
 * sim_inet_get_in_addr:
 * @inet: #SimInet object
 *
 * Returns: address bytes
 */
uint8_t *
sim_inet_get_in_addr (SimInet *inet)
{
  gchar *buffer = g_new0 (gchar, GNET_INETADDR_MAX_LEN);

  g_return_val_if_fail (SIM_IS_INET (inet), NULL);

  gnet_inetaddr_get_bytes (inet->priv->address, buffer);

  return (uint8_t *) buffer;
}

/**
 * sim_inet_set_port:
 * @inet: #SimInet object
 * @port: a port number
 *
 */
void
sim_inet_set_port (SimInet *inet, gint port)
{
  g_return_if_fail (SIM_IS_INET (inet));
  gnet_inetaddr_set_port (inet->priv->address, port);
}


/**
 * sim_inet_get_port:
 * @inet: #SimInet object
 *
 * Returns: port number
 */
gint
sim_inet_get_port (SimInet *inet)
{
  g_return_val_if_fail (SIM_IS_INET (inet), 0);

  return (gnet_inetaddr_get_port (inet->priv->address));
}

/**
 * sim_inet_hash:
 * @inet: #SimInet object
 *
 * Returns: guint hash value
 */
guint
sim_inet_hash (gconstpointer a)
{
  SimInet * inet = SIM_INET (a);
  guint hash = 0;
  gint i;

  for (i = 0; i < 4; i++)
    hash ^= inet->priv->bytes.addr32[i];

  return (hash);
}

/**
 * sim_inet_equal:
 * @a: #SimInet object
 * @b: #SimInet object
 *
 * Returns: %TRUE if they're equal, %FALSE otherwise.
 */
gboolean
sim_inet_equal (gconstpointer a, gconstpointer b)
{
  SimInet * inet_a = SIM_INET (a);
  SimInet * inet_b = SIM_INET (b);

  return ((inet_a) && (inet_b) && (inet_a->priv->address) && (inet_b->priv->address) &&
          (gnet_inetaddr_noport_equal (inet_a->priv->address, inet_b->priv->address)));
}


/**
 * sim_inet_get_db_string:
 * @inet: #SimInet object
 *
 * Returns: @inet database string
 */
const gchar *
sim_inet_get_db_string (SimInet *inet)
{
  if (SIM_IS_INET (inet))
    return (inet->priv->db_str);
  else
    return "NULL";
}

/**
 * sim_inet_get_radix_key:
 * @inet: #SimInet object
 *
 * Returns: SimRadixKey for @inet
 */
SimRadixKey *
sim_inet_get_radix_key (SimInet *inet)
{
  g_return_val_if_fail (SIM_IS_INET (inet), NULL);

  return (inet->priv->radix_key);
}


/**
 * sim_inet_is_ipv4:
 * @inet: #SimInet object
 *
 * Returns: %TRUE if @inet address is ipv4
 */
gboolean
sim_inet_is_ipv4 (SimInet *inet)
{
  g_return_val_if_fail (SIM_IS_INET (inet), FALSE);

  return gnet_inetaddr_is_ipv4 (inet->priv->address);
}

/**
 * sim_inet_is_ipv6:
 * @inet: #SimInet object
 *
 * Returns: %TRUE if @inet address is ipv6
 */
gboolean
sim_inet_is_ipv6 (SimInet *inet)
{
  g_return_val_if_fail (SIM_IS_INET (inet), FALSE);

  return gnet_inetaddr_is_ipv6 (inet->priv->address);
}

/**
 * sim_inet_is_reserved:
 * @inet: #SimInet object
 *
 * Returns: %TRUE if @inet address is reserved
 */
gboolean
sim_inet_is_reserved (SimInet *inet)
{
  g_return_val_if_fail (SIM_IS_INET (inet), FALSE);

  return gnet_inetaddr_is_reserved (inet->priv->address);
}

/**
 * sim_inet_is_host:
 * @inet: #SimInet object
 *
 * Returns: %TRUE if @inet address is a host
 */
gboolean
sim_inet_is_host (SimInet *inet)
{
  guint mask;

  g_return_val_if_fail (SIM_IS_INET (inet), FALSE);

  if (gnet_inetaddr_is_ipv4 (inet->priv->address))
    mask = MASK_HOST_IPV4;
  else
    mask = MASK_HOST_IPV6;

  return (mask == inet->priv->mask);
}

/**
 * sim_inet_is_in_homenet:
 * @inet: #SimInet object
 *
 * Returns: %TRUE if @inet is included in 'HOME_NET'
 */
gboolean
sim_inet_is_in_homenet (SimInet *inet)
{
  g_return_val_if_fail (SIM_IS_INET (inet), FALSE);

  if (!inet->priv->homenet_checked)
    g_message ("ERROR Bad usage %s without search home_net before", __func__);

  return inet->priv->is_in_homenet;
}

/**
 * sim_inet_set_is_in_homenet:
 * @inet: #SimInet object
 * @found: if @inet has ben found in 'HOME_NET'
 *
 * Sets if @inet has been found in 'HOME_NET'
 * and mark @inet as been searched in 'HOME_NET'
 */
void
sim_inet_set_is_in_homenet (SimInet *inet,
                            gboolean found)
{
  g_return_if_fail (SIM_IS_INET (inet));

  inet->priv->homenet_checked = TRUE;
  inet->priv->is_in_homenet = found;
}

/**
 * sim_inet_is_homenet_checked:
 * @inet: #SimInet object
 *
 * Returns: %TRUE if @inet has been searched before in 'HOME_NET'
 */
gboolean
sim_inet_is_homenet_checked (SimInet *inet)
{
  g_return_val_if_fail (SIM_IS_INET (inet), FALSE);

  return inet->priv->homenet_checked;
}

/**
 * _sim_inet_make_db_string:
 * @inet: #SimInet object
 *
 *  IPV4 DB String format:
 *
 *  0x00112233
 *
 *  IPV6 DB String format:
 *
 *   0x00112233445566778899AABBCCDDEEFF
 *
 * Make the address bytes string to store in database
 */
static void
_sim_inet_make_db_string (SimInet *inet)
{
  sim_inet_ip buf;

  /* Get bytes */
  gnet_inetaddr_get_bytes (inet->priv->address, inet->priv->bytes.str);

  buf = inet->priv->bytes;

  if (gnet_inetaddr_is_ipv4 (inet->priv->address))
  {
    inet->priv->db_str = g_strdup_printf ("0x%02x%02x%02x%02x",
                                          buf.addr8[0], buf.addr8[1], buf.addr8[2], buf.addr8[3]);
  }
  else if (gnet_inetaddr_is_ipv6 (inet->priv->address))
  {
    inet->priv->db_str = g_strdup_printf ("0x%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x",
                                          buf.addr8[0], buf.addr8[1], buf.addr8[2], buf.addr8[3],
                                          buf.addr8[4], buf.addr8[5], buf.addr8[6], buf.addr8[7],
                                          buf.addr8[8], buf.addr8[9], buf.addr8[10], buf.addr8[11],
                                          buf.addr8[12], buf.addr8[13], buf.addr8[14], buf.addr8[15]);
  }
}

static void
_sim_inet_make_radix_key (SimInet *inet)
{
  gchar *ip_addr = g_new0 (gchar, GNET_INETADDR_MAX_LEN);
  gnet_inetaddr_get_bytes (inet->priv->address, ip_addr);

  inet->priv->radix_key = sim_radix_key_create ((uint8_t *)ip_addr, (uint8_t)inet->priv->mask);
}


#ifdef USE_UNITTESTS
/*************************************************************
 *******************      Unit tests      ********************
 *************************************************************/

void     sim_inet_test_print_object (SimInet *inet);
gboolean sim_inet_test1             (void);
gboolean sim_inet_test2             (void);
gboolean sim_inet_test3             (void);

/* List of ips for testing */
static gchar *ip[] = {"192.168.5.120",
                      "0.0.0.0",
                      "192.168.2.3",
                      "192.168.5.100",
                      "::",
                      "::192.168.5.100",
                      "::0001",
                      "202:A0::192.168.5.100",
                      "2001:470:9798:5:a288:b4ff:fe7d:3eb8",
                      "FFFF:FFFF:FFFF:FFFF:FFFF:FFFF:FFFF:FFFF",
                      "FFFF:0000:FFFF:0000::FFFF",
                      "192.168.10.1",
                      "fe80::da30:62ff:fe18:6681",
                      NULL};

/* Test Aux functions */
void
sim_inet_test_print_object (SimInet *inet)
{
  gchar *cidr;

  g_return_if_fail (SIM_IS_INET (inet));

  cidr = sim_inet_get_cidr (inet);

  g_print ("IPV%s :CIDR=%s, DB=%s\n",
           sim_inet_is_ipv4 (inet) ? "4" : sim_inet_is_ipv6 (inet) ? "6" : "-ERROR-",
           cidr,
           sim_inet_get_db_string (inet));

  g_free (cidr);
}

/* Unit tests begin here */

// Test IPV4 1
gboolean
sim_inet_test1 ()
{
  SimInet    *inet;
  gchar      *aux;
  gboolean    success = TRUE;

  inet = sim_inet_new_from_string ("10.0.0.1");
  sim_inet_test_print_object (inet);
  if (sim_inet_get_mask (inet) != 32) success = FALSE;
  aux = sim_inet_get_canonical_name (inet);
  if (strcmp (aux, "10.0.0.1") != 0) success = FALSE;
  g_free (aux);
  aux = sim_inet_get_cidr (inet);
  if (strcmp (aux, "10.0.0.1/32") != 0) success = FALSE;
  g_free (aux);

  if (!success)
    return FALSE;

  g_object_unref (inet);

  inet = sim_inet_new_from_string ("0.0.0.1");
  sim_inet_test_print_object (inet);
  g_object_unref (inet);

  inet = sim_inet_new_from_string ("0.0.1.0");
  sim_inet_test_print_object (inet);
  g_object_unref (inet);

  inet = sim_inet_new_from_string ("0.1.0.0");
  sim_inet_test_print_object (inet);
  g_object_unref (inet);

  inet = sim_inet_new_from_string ("1.0.0.0");
  sim_inet_test_print_object (inet);
  g_object_unref (inet);

  inet = sim_inet_new_from_string ("10.0.0.1/16");
  sim_inet_test_print_object (inet);
  g_object_unref (inet);

  inet = sim_inet_new_from_string ("0.0.0.0/32");
  sim_inet_test_print_object (inet);
  g_object_unref (inet);

  return TRUE;
}

/* TEST 2 IPV6 */
gboolean
sim_inet_test2 ()
{
  SimInet *inet;

  inet = sim_inet_new_from_string ("::0/128");
  sim_inet_test_print_object (inet);
  g_object_unref (inet);

  inet = sim_inet_new_from_string ("0::/32");
  sim_inet_test_print_object (inet);
  g_object_unref (inet);

  inet = sim_inet_new_from_string ("0::0/16");
  sim_inet_test_print_object (inet);
  g_object_unref (inet);

  inet = sim_inet_new_from_string ("::10:0:0:1");
  sim_inet_test_print_object (inet);
  g_object_unref (inet);

  inet = sim_inet_new_from_string ("10:0::0:1/64");
  sim_inet_test_print_object (inet);
  g_object_unref (inet);

  inet = sim_inet_new_from_string ("FFFF:FFFF:FFFF:FFFF:FFFF:FFFF:FFFF:FFFF/16");
  sim_inet_test_print_object (inet);
  g_object_unref (inet);

  inet = sim_inet_new_from_string ("0:0:0:0:0:0:0.0.0.1");
  sim_inet_test_print_object (inet);
  g_object_unref (inet);

  inet = sim_inet_new_from_string ("0:0:0:1:0:0:0:0/64");
  sim_inet_test_print_object (inet);
  g_object_unref (inet);

  inet = sim_inet_new_from_string ("0:0:0:1:0:0:0:0/63");
  sim_inet_test_print_object (inet);
  g_object_unref (inet);

  inet = sim_inet_new_from_string ("0:0:0:1:0:0:0:0/65");
  sim_inet_test_print_object (inet);
  g_object_unref (inet);

  inet = sim_inet_new_from_string ("0:0:0:1:0:0:0:0/127");
  sim_inet_test_print_object (inet);
  g_object_unref (inet);

  inet = sim_inet_new_from_string ("FFFF:FFFF:FFFF:FFFF:0:0:0:0/64");
  sim_inet_test_print_object (inet);
  g_object_unref (inet);

  inet = sim_inet_new_from_string ("FFFF:FFFF:FFFF:FFFF:0:0:0:0/63");
  sim_inet_test_print_object (inet);
  g_object_unref (inet);

  inet = sim_inet_new_from_string ("FFFF:FFFF:FFFF:FFFF:0:0:0:0/65");
  sim_inet_test_print_object (inet);
  g_object_unref (inet);

  inet = sim_inet_new_from_string ("FFFF:FFFF:FFFF:FFFF:0:0:0:0/96");
  sim_inet_test_print_object (inet);
  g_object_unref (inet);

  inet = sim_inet_new_from_string ("2001:0DB8:0:CD30::/60");
  sim_inet_test_print_object (inet);
  g_object_unref (inet);

  return TRUE;
}

/* TEST 3 Clone, db_string */
gboolean
sim_inet_test3 ()
{
  gboolean     success = TRUE;
  SimInet     *addr, *addr2;
  gchar       *bytes, *bytes2;
  gchar       *name, *name2;
  gint         i;

  /* Clone */
  g_print ("\nClone, equal, noport_equal, hash\n");
  i = 0;
  while (ip[i] != NULL)
  {
    guint hash, hash2;

    addr = sim_inet_new_from_string (ip[i]);
    name = sim_inet_get_canonical_name (addr);
    bytes = (gchar *)sim_inet_get_in_addr (addr);
    hash = sim_inet_hash (addr);
    addr2 = sim_inet_clone (addr);
    name2 = sim_inet_get_canonical_name (addr);
    bytes2 = (gchar *)sim_inet_get_in_addr (addr);
    hash2 = sim_inet_hash (addr2);

    if (strcmp (name, name2) != 0)
      success = FALSE;
    if (strcmp (bytes, bytes2) != 0)
      success = FALSE;
    if (!sim_inet_equal (addr, addr2))
      success = FALSE;
    if (!sim_inet_noport_equal (addr, addr2))
      success = FALSE;
    if (hash != hash2)
      success = FALSE;

    g_print ("IP %s -- %s\n", ip[i], (success) ? "OK" : "FAILED");

    i++;

    if (addr != NULL)
      g_object_unref (addr);
    if (addr2 != NULL)
      g_object_unref (addr2);
    if (name != NULL)
      g_free (name);
    if (bytes != NULL)
      g_free (bytes);
    if (name2 != NULL)
      g_free (name2);
    if (bytes2 != NULL)
      g_free (bytes2);
  }

  g_print ("\nDB string\n");
  i = 0;
  while (ip[i] != NULL)
  {
    const gchar *db_str, *db_str2;
    guint len;
    addr = sim_inet_new_from_string (ip[i]);
    name = sim_inet_get_canonical_name (addr);
    db_str = sim_inet_get_db_string (addr);
    bytes = (gchar *)sim_inet_get_in_addr (addr);
    len = (sim_inet_is_ipv4 (addr)) ? NUM_BYTES_IPV4 : NUM_BYTES_IPV6;
    addr2 = sim_inet_new_from_db_binary ((guchar *)bytes, len);
    name2 = sim_inet_get_canonical_name (addr2);
    db_str2 = sim_inet_get_db_string (addr2);

    if (strcmp (name, name2) != 0)
      success = FALSE;
    if (strcmp (db_str, db_str2) != 0)
      success = FALSE;
    if (!sim_inet_equal (addr, addr2))
      success = FALSE;
    g_print ("IP %s -- %s\n", ip[i], (success) ? "OK" : "FAILED");

    sim_inet_test_print_object (addr);

    i++;

    if (addr != NULL)
      g_object_unref (addr);
    if (addr2 != NULL)
      g_object_unref (addr2);
    if (name != NULL)
      g_free (name);
    if (name2 != NULL)
      g_free (name2);
  }

  return success;
}

void
sim_inet_register_tests (SimUnittesting *engine)
{
  sim_unittesting_append (engine, "sim_inet_test1 - sim_inet_new IPV4", sim_inet_test1, TRUE);
  sim_unittesting_append (engine, "sim_inet_test2 - sim_inet_new IPV6", sim_inet_test2, TRUE);
  sim_unittesting_append (engine, "sim_inet_test3 - clone, db_string", sim_inet_test3, TRUE);
}
#endif //USE_UNITTESTS

// vim: set tabstop=2:
