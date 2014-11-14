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

#include "sim-rule.h"

#include <time.h>
#include <string.h>
#include <arpa/inet.h>
#include <netinet/in.h>

#include "os-sim.h"
#include "sim-util.h"
#include "sim-network.h"

struct _SimRulePrivate {
  gint        level;
  gchar      *name;
  gboolean    not;
  gboolean    not_invalid;

  gint        priority;
  gint        reliability;
  gboolean    rel_abs;

  time_t       time_out;
  time_t       time_last;
  gint        occurrence;

  SimConditionType   condition;
  gchar             *value;
  gint               interval;
  gboolean           absolute;

  gint        count_occu;

  gint        plugin_id;		//store data from event in this variables
  gint        plugin_sid;
  SimInet    *src_ia;
  SimInet    *dst_ia;
  SimUuid    *src_host_id;
  SimUuid    *dst_host_id;
  gint        src_port;
  gint        dst_port;
  gint        src_max_asset;
  gint        dst_max_asset;
  SimProtocolType    protocol;
  SimInet    *sensor;
  gint        tax_product;
  gint        tax_category;
  gint        tax_subcategory;

  SimContext *entity;

  GList       *entities;
  GList       *entities_not;

  //I call this ev_filename because call to the GLists "userdatas1" doesn't likes to me. 
  //This variables are the event one's inside the rule.
  gchar				*ev_filename;
  gchar				*ev_username;
  gchar				*ev_password;
  gchar				*ev_userdata1;
  gchar				*ev_userdata2;
  gchar				*ev_userdata3;
  gchar				*ev_userdata4;
  gchar				*ev_userdata5;
  gchar				*ev_userdata6;
  gchar				*ev_userdata7;
  gchar				*ev_userdata8;
  gchar				*ev_userdata9;
	
  SimRuleVarType   sticky_different;
  GHashTable      *sticky_int;
  GHashTable      *sticky_str;
  SimNetwork      *sticky_ip;

  //This variables are used to store the data from directives. i.e., the src-inets will store 
  //all the inets which appears in the directives file. But, for example the variable above 
  //"GInetAddr *src_ia" will store the data from event that matches
  GHashTable   *plugin_ids_not;      // gint
  GHashTable   *plugin_sids_not;     // gint
  GHashTable   *src_hosts_not;       // SimHost
  SimNetwork   *src_inets_not;       // SimInet object
  gboolean      src_is_home_net_not;
  GHashTable   *dst_hosts_not;       // SimHost
  SimNetwork   *dst_inets_not;
  gboolean      dst_is_home_net_not;
  GHashTable   *src_ports_not; //gint
  GHashTable   *dst_ports_not;
  GHashTable   *protocols_not; //gint
  GHashTable   *sensors_not; //SimSensor
  GHashTable  * product_not;
  GHashTable   *category_not;
  GHashTable   *subcategory_not;
  GHashTable   *suppress;

  GList	*vars;

  GHashTable   *plugin_ids;
  GHashTable   *plugin_sids;
  GHashTable   *src_hosts;
  SimNetwork   *src_inets; //SimInet
  gboolean      src_is_home_net;
  GHashTable   *dst_hosts;
  SimNetwork   *dst_inets;
  gboolean      dst_is_home_net;
  GHashTable   *src_ports;
  GHashTable   *dst_ports;
  GHashTable   *protocols;
  GHashTable   *sensors;	//SimSensor
  GHashTable  * products;
  GHashTable  *categories;
  GHashTable  *subcategories;

  gboolean    from_rep;
  gboolean    to_rep;
  gint        from_rep_min_rel;
  gint        to_rep_min_rel;
  gint        from_rep_min_pri;
  gint        to_rep_min_pri;

  //additional keywords list. The keywords can be negated also (negated in a list, and non-negated in other list)
  GList				*filename;
  GList				*username;
  GList				*password;
  GList				*userdata1;
  GList				*userdata2;
  GList				*userdata3;
  GList				*userdata4;
  GList				*userdata5;
  GList				*userdata6;
  GList				*userdata7;
  GList				*userdata8;
  GList				*userdata9;
  GList				*filename_not;
  GList				*username_not;
  GList				*password_not;
  GList				*userdata1_not;
  GList				*userdata2_not;
  GList				*userdata3_not;
  GList				*userdata4_not;
  GList				*userdata5_not;
  GList				*userdata6_not;
  GList				*userdata7_not;
  GList				*userdata8_not;
  GList				*userdata9_not;

  // temporal lists where values will be stored until later will be expanded
  // in the container/context. This is necessary to remove the dependency between
  // sim-xml-directive and container/context
  GList       *expand_src_assets_names;
  GList       *expand_src_assets_names_not;
  GList       *expand_dst_assets_names;
  GList       *expand_dst_assets_names_not;
  GList       *expand_sensors_names;
  GList       *expand_sensors_names_not;
  GList       *expand_entities;
  GList       *expand_entities_not;
};

static gpointer parent_class = NULL;

static gboolean   sim_rule_match_src_host                (SimRule    *rule,
                                                          SimEvent   *event);
static gboolean   sim_rule_match_src_host_not            (SimRule    *rule,
                                                          SimEvent   *event);
static gboolean   sim_rule_match_dst_host                (SimRule    *rule,
                                                          SimEvent   *event);
static gboolean   sim_rule_match_dst_host_not            (SimRule    *rule,
                                                          SimEvent   *event);
static gboolean   sim_rule_match_src_ip                  (SimRule    *rule,
                                                          SimEvent   *event);
static gboolean   sim_rule_match_dst_ip                  (SimRule    *rule,
                                                          SimEvent   *event);
static gboolean   _sim_rule_is_time_out                  (SimRule    *rule);


/* GType Functions */

static void 
sim_rule_impl_dispose (GObject  *gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void 
sim_rule_impl_finalize (GObject  *gobject)
{
  SimRule *rule = SIM_RULE (gobject);
  GList   *list;

  ossim_debug ( "sim_rule_impl_finalize: Name %s, Level %d", rule->_priv->name, rule->_priv->level);
//    sim_rule_print(rule);

  if (rule->_priv->name)
    g_free (rule->_priv->name);

  if (rule->_priv->value)
    g_free (rule->_priv->value);

  if (rule->_priv->src_ia)
  {
    g_object_unref (rule->_priv->src_ia);
    rule->_priv->src_ia = NULL;
  }
  if (rule->_priv->dst_ia)
  {
    g_object_unref (rule->_priv->dst_ia);
    rule->_priv->dst_ia = NULL;
  }
  if (rule->_priv->src_host_id)
  {
    g_object_unref (rule->_priv->src_host_id);
    rule->_priv->src_host_id = NULL;
  }
  if (rule->_priv->dst_host_id)
  {
    g_object_unref (rule->_priv->dst_host_id);
    rule->_priv->dst_host_id = NULL;
  }
  if (rule->_priv->sensor)
  {
    g_object_unref (rule->_priv->sensor);
    rule->_priv->sensor = NULL;
  }
  if (rule->_priv->entity)
  {
    g_object_unref (rule->_priv->entity);
    rule->_priv->entity = NULL;
  }

  if (rule->_priv->entities)
  {
    g_list_free (rule->_priv->entities);
    rule->_priv->entities = NULL;
  }

  if (rule->_priv->entities_not)
  {
    g_list_free (rule->_priv->entities_not);
    rule->_priv->entities_not = NULL;
  }

  /* vars */
  list = rule->_priv->vars;
  while (list)
    {
      SimRuleVar *rule_var = (SimRuleVar *) list->data;
      g_free (rule_var);
      list = list->next;
    }
  g_list_free (rule->_priv->vars);

  /* Plugin Ids */
  if(rule->_priv->plugin_ids)
    g_hash_table_destroy (rule->_priv->plugin_ids);

  /* Plugin Sids */
  if(rule->_priv->plugin_sids)
    g_hash_table_destroy (rule->_priv->plugin_sids);

  /* from */
  if (rule->_priv->src_hosts)
  {
    g_hash_table_destroy (rule->_priv->src_hosts);
    rule->_priv->src_hosts = NULL;
  }
  if (rule->_priv->src_inets)
  {
    g_object_unref (rule->_priv->src_inets);
    rule->_priv->src_inets = NULL;
  }
  rule->_priv->src_is_home_net = FALSE;

  /* to */
  if (rule->_priv->dst_hosts)
  {
    g_hash_table_destroy (rule->_priv->dst_hosts);
    rule->_priv->dst_hosts = NULL;
  }
  if (rule->_priv->dst_inets)
  {
    g_object_unref (rule->_priv->dst_inets);
    rule->_priv->dst_inets = NULL;
  }
  rule->_priv->dst_is_home_net = FALSE;

  /* src ports */
  if(rule->_priv->src_ports)
    g_hash_table_destroy (rule->_priv->src_ports);

  /* dst ports */
  if(rule->_priv->dst_ports)
    g_hash_table_destroy (rule->_priv->dst_ports);

  /* sensors */
  if(rule->_priv->sensors)
    g_hash_table_destroy (rule->_priv->sensors);

  /* taxonomy - product */
  if(rule->_priv->products)
    g_hash_table_destroy (rule->_priv->products);

  /* taxonomy - category */
  if(rule->_priv->categories)
    g_hash_table_destroy (rule->_priv->categories);

  /* taxonomy - subcategory */
  if(rule->_priv->subcategories)
    g_hash_table_destroy (rule->_priv->subcategories);

  /* protocols */
  if(rule->_priv->protocols)
    g_hash_table_destroy (rule->_priv->protocols);

  /* stickys */
  if(rule->_priv->sticky_int)
    g_hash_table_destroy (rule->_priv->sticky_int);
  if(rule->_priv->sticky_str)
    g_hash_table_destroy (rule->_priv->sticky_str);
  if (rule->_priv->sticky_ip != NULL)
  {
    g_object_unref (rule->_priv->sticky_ip);
    rule->_priv->sticky_ip = NULL;
  }

  // filename
  list = rule->_priv->filename; 
  while (list) 
  { 
    gchar *filename = (gchar *) list->data; 
    g_free (filename); 
    list = list->next; 
  } 
  g_list_free (rule->_priv->filename); 

  // username
  list = rule->_priv->username; 
  while (list) 
  { 
    gchar *username = (gchar *) list->data; 
    g_free (username); 
    list = list->next; 
  } 
  g_list_free (rule->_priv->username); 

  // password
  list = rule->_priv->password; 
  while (list) 
  { 
    gchar *password = (gchar *) list->data; 
    g_free (password); 
    list = list->next; 
  } 
  g_list_free (rule->_priv->password); 

	gchar *userdata = NULL;	//aux variable
	
	// userdata1
	list = rule->_priv->userdata1; 
	while (list) 
	{ 
		userdata = (gchar *) list->data; 
		g_free (userdata); 
		list = list->next; 
	} 
	g_list_free (rule->_priv->userdata1); 

	// userdata2
	list = rule->_priv->userdata2; 
	while (list) 
	{ 
		userdata = (gchar *) list->data; 
		g_free (userdata); 
		list = list->next; 
	} 
	g_list_free (rule->_priv->userdata2); 

	// userdata3
	list = rule->_priv->userdata3; 
	while (list) 
	{ 
		userdata = (gchar *) list->data; 
		g_free (userdata); 
		list = list->next; 
	} 
	g_list_free (rule->_priv->userdata3); 

	// userdata4
	list = rule->_priv->userdata4; 
	while (list) 
	{ 
		userdata = (gchar *) list->data; 
		g_free (userdata); 
		list = list->next; 
	} 
	g_list_free (rule->_priv->userdata4); 

	// userdata5
	list = rule->_priv->userdata5; 
	while (list) 
	{ 
		userdata = (gchar *) list->data; 
		g_free (userdata); 
		list = list->next; 
	} 
	g_list_free (rule->_priv->userdata5); 

	// userdata6
	list = rule->_priv->userdata6; 
	while (list) 
	{ 
		userdata = (gchar *) list->data; 
		g_free (userdata); 
		list = list->next; 
	} 
	g_list_free (rule->_priv->userdata6); 

	// userdata7
	list = rule->_priv->userdata7; 
	while (list) 
	{ 
		userdata = (gchar *) list->data; 
		g_free (userdata); 
		list = list->next; 
	} 
	g_list_free (rule->_priv->userdata7); 

	// userdata8
	list = rule->_priv->userdata8; 
	while (list) 
	{ 
		userdata = (gchar *) list->data; 
		g_free (userdata); 
		list = list->next; 
	} 
	g_list_free (rule->_priv->userdata8); 

	// userdata9
	list = rule->_priv->userdata9; 
	while (list) 
	{ 
		userdata = (gchar *) list->data; 
		g_free (userdata); 
		list = list->next; 
	} 
	g_list_free (rule->_priv->userdata9); 


  // not's:

  // !from
  if (rule->_priv->src_hosts_not)
  {
    g_hash_table_destroy (rule->_priv->src_hosts_not);
    rule->_priv->src_hosts_not = NULL;
  }
  if (rule->_priv->src_inets_not)
  {
    g_object_unref (rule->_priv->src_inets_not);
    rule->_priv->src_inets_not = NULL;
  }
  rule->_priv->src_is_home_net_not = FALSE;

  // !to
  if (rule->_priv->dst_hosts_not)
  {
    g_hash_table_destroy (rule->_priv->dst_hosts_not);
    rule->_priv->dst_hosts_not = NULL;
  }
  if (rule->_priv->dst_inets_not)
  {
    g_object_unref (rule->_priv->dst_inets_not);
    rule->_priv->dst_inets_not = NULL;
  }
  rule->_priv->dst_is_home_net_not = FALSE;

  // !plugin ids
  if(rule->_priv->plugin_ids_not)
    g_hash_table_destroy (rule->_priv->plugin_ids_not);

  // !plugin_sids
  if(rule->_priv->plugin_sids_not)
    g_hash_table_destroy (rule->_priv->plugin_sids_not); 
 
  // !src ports
  if(rule->_priv->src_ports_not)
    g_hash_table_destroy (rule->_priv->src_ports_not); 
 
  // !dst ports
  if(rule->_priv->dst_ports_not)
    g_hash_table_destroy (rule->_priv->dst_ports_not); 

  // !protocols
  if(rule->_priv->protocols_not)
    g_hash_table_destroy (rule->_priv->protocols_not); 
 
  // !sensors
  if(rule->_priv->sensors_not)
    g_hash_table_destroy (rule->_priv->sensors_not); 

  // taxonomy - !product
  if(rule->_priv->product_not)
    g_hash_table_destroy (rule->_priv->product_not);

  // taxonomy - !category
  if(rule->_priv->category_not)
    g_hash_table_destroy (rule->_priv->category_not);

  // taxonomy - !subcategory
  if(rule->_priv->subcategory_not)
    g_hash_table_destroy (rule->_priv->subcategory_not);

  // taxonomy - suppress
  if(rule->_priv->suppress)
    g_hash_table_destroy (rule->_priv->suppress);

  // !filename
  list = rule->_priv->filename_not; 
  while (list) 
  { 
    gchar *filename = (gchar *) list->data; 
    g_free (filename); 
    list = list->next; 
  } 
  g_list_free (rule->_priv->filename_not); 

  // !username
  list = rule->_priv->username_not; 
  while (list) 
  { 
    gchar *username = (gchar *) list->data; 
    g_free (username); 
    list = list->next; 
  } 
  g_list_free (rule->_priv->username_not); 

  // !password
  list = rule->_priv->password_not; 
  while (list) 
  { 
    gchar *password = (gchar *) list->data; 
    g_free (password); 
    list = list->next; 
  } 
  g_list_free (rule->_priv->password_not); 

  // !userdata1
  list = rule->_priv->userdata1_not; 
  while (list) 
  { 
    userdata = (gchar *) list->data; 
    g_free (userdata); 
    list = list->next; 
  } 
  g_list_free (rule->_priv->userdata1_not); 

  // !userdata2
  list = rule->_priv->userdata2_not; 
  while (list) 
  { 
    userdata = (gchar *) list->data; 
    g_free (userdata); 
    list = list->next; 
  } 
  g_list_free (rule->_priv->userdata2_not); 

  // !userdata3
  list = rule->_priv->userdata3_not; 
  while (list) 
  { 
    userdata = (gchar *) list->data; 
    g_free (userdata); 
    list = list->next; 
  } 
  g_list_free (rule->_priv->userdata3_not); 

  // !userdata4
  list = rule->_priv->userdata4_not; 
  while (list) 
  { 
    userdata = (gchar *) list->data; 
    g_free (userdata); 
    list = list->next; 
  } 
  g_list_free (rule->_priv->userdata4_not); 

  // !userdata5
  list = rule->_priv->userdata5_not; 
  while (list) 
  { 
    userdata = (gchar *) list->data; 
    g_free (userdata); 
    list = list->next; 
  } 
  g_list_free (rule->_priv->userdata5_not); 

  // !userdata6
  list = rule->_priv->userdata6_not; 
  while (list) 
  { 
    userdata = (gchar *) list->data; 
    g_free (userdata); 
    list = list->next; 
  } 
  g_list_free (rule->_priv->userdata6_not); 

  // !userdata7
  list = rule->_priv->userdata7_not; 
  while (list) 
  { 
    userdata = (gchar *) list->data; 
    g_free (userdata); 
    list = list->next; 
  } 
  g_list_free (rule->_priv->userdata7_not); 

  // !userdata8
  list = rule->_priv->userdata8_not; 
  while (list) 
  { 
    userdata = (gchar *) list->data; 
    g_free (userdata); 
    list = list->next; 
  } 
  g_list_free (rule->_priv->userdata8_not); 

  // !userdata9
  list = rule->_priv->userdata9_not; 
  while (list) 
  { 
    userdata = (gchar *) list->data; 
    g_free (userdata); 
    list = list->next; 
  } 
  g_list_free (rule->_priv->userdata9_not); 

  g_free (rule->_priv->ev_filename);
  g_free (rule->_priv->ev_username);
  g_free (rule->_priv->ev_password);
  g_free (rule->_priv->ev_userdata1);
  g_free (rule->_priv->ev_userdata2);
  g_free (rule->_priv->ev_userdata3);
  g_free (rule->_priv->ev_userdata4);
  g_free (rule->_priv->ev_userdata5);
  g_free (rule->_priv->ev_userdata6);
  g_free (rule->_priv->ev_userdata7);
  g_free (rule->_priv->ev_userdata8);
  g_free (rule->_priv->ev_userdata9);

  sim_rule_free_expand_items (rule);

  g_free (rule->_priv);

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_rule_class_init (SimRuleClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_ref (G_TYPE_OBJECT);

  object_class->dispose = sim_rule_impl_dispose;
  object_class->finalize = sim_rule_impl_finalize;
}

static void
sim_rule_instance_init (SimRule *rule)
{
  rule->_priv = g_new0 (SimRulePrivate, 1);

//  ossim_debug ( "sim_rule_instance_init");

  rule->type = SIM_EVENT_TYPE_NONE;

  rule->_priv->level = 0;
  rule->_priv->name = NULL;
  rule->_priv->not = FALSE;
  rule->_priv->not_invalid = FALSE;

  rule->_priv->priority = 0;
  rule->_priv->reliability = 0;
  rule->_priv->rel_abs = TRUE;

  rule->_priv->condition = SIM_CONDITION_TYPE_NONE;
  rule->_priv->value = NULL;
  rule->_priv->interval = 0;
  rule->_priv->absolute = FALSE;

  rule->_priv->time_out = 0;
  rule->_priv->time_last = 0;
  rule->_priv->occurrence = 1;

  rule->_priv->count_occu = 1;

  rule->_priv->plugin_id = 0;
  rule->_priv->plugin_sid = 0;
  rule->_priv->src_ia = NULL;
  rule->_priv->dst_ia = NULL;
  rule->_priv->src_host_id = NULL;
  rule->_priv->dst_host_id = NULL;
  rule->_priv->src_port = 0;
  rule->_priv->dst_port = 0;
  rule->_priv->src_max_asset = 0;
  rule->_priv->dst_max_asset = 0;
  rule->_priv->protocol = SIM_PROTOCOL_TYPE_NONE;
  rule->_priv->sensor = NULL;
  rule->_priv->entity = NULL;

  rule->_priv->tax_product = 0;
  rule->_priv->tax_category = 0;
  rule->_priv->tax_subcategory = 0;

  rule->_priv->entities = NULL;
  rule->_priv->entities_not = NULL;

  rule->_priv->sticky_different = SIM_RULE_VAR_NONE;
  rule->_priv->sticky_int = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
  rule->_priv->sticky_str = g_hash_table_new_full (g_str_hash, g_str_equal, NULL, NULL);
  rule->_priv->sticky_ip = sim_network_new ();

  rule->_priv->plugin_sids_not = NULL;
  rule->_priv->src_hosts_not = NULL;
  rule->_priv->src_inets_not = NULL;
  rule->_priv->dst_hosts_not = NULL;
  rule->_priv->dst_inets_not = NULL;
  rule->_priv->src_ports_not = NULL;
  rule->_priv->dst_ports_not = NULL;
	rule->_priv->protocols_not = NULL;
	rule->_priv->sensors_not = NULL;

  rule->_priv->vars = NULL;
  rule->_priv->plugin_sids = NULL;
  rule->_priv->src_hosts = NULL;
  rule->_priv->src_inets = NULL;
  rule->_priv->dst_hosts = NULL;
  rule->_priv->dst_inets = NULL;
  rule->_priv->src_ports = NULL;
  rule->_priv->dst_ports = NULL;
  rule->_priv->protocols = NULL;
  rule->_priv->sensors = NULL;

  //GList *
  rule->_priv->filename = NULL;
  rule->_priv->username = NULL;
  rule->_priv->password = NULL;
  rule->_priv->userdata1 = NULL;
  rule->_priv->userdata2 = NULL;
  rule->_priv->userdata3 = NULL;
  rule->_priv->userdata4 = NULL;
  rule->_priv->userdata5 = NULL;
  rule->_priv->userdata6 = NULL;
  rule->_priv->userdata7 = NULL;
  rule->_priv->userdata8 = NULL;
  rule->_priv->userdata9 = NULL;

  //gchar *
  rule->_priv->ev_filename = NULL;
  rule->_priv->ev_username = NULL;
  rule->_priv->ev_password = NULL;
  rule->_priv->ev_userdata1 = NULL;
  rule->_priv->ev_userdata2 = NULL;
  rule->_priv->ev_userdata3 = NULL;
  rule->_priv->ev_userdata4 = NULL;
  rule->_priv->ev_userdata5 = NULL;
  rule->_priv->ev_userdata6 = NULL;
  rule->_priv->ev_userdata7 = NULL;
  rule->_priv->ev_userdata8 = NULL;
  rule->_priv->ev_userdata9 = NULL;

  rule->_priv->expand_src_assets_names = NULL;
  rule->_priv->expand_src_assets_names_not = NULL;
  rule->_priv->expand_dst_assets_names = NULL;
  rule->_priv->expand_dst_assets_names_not = NULL;
  rule->_priv->expand_sensors_names = NULL;
  rule->_priv->expand_sensors_names_not = NULL;
  rule->_priv->expand_entities = NULL;
  rule->_priv->expand_entities_not = NULL;

  return;
}

/* Public Methods */
GType
sim_rule_get_type (void)
{
  static GType object_type = 0;
 
  if (!object_type)
  {
    static const GTypeInfo type_info = {
              sizeof (SimRuleClass),
              NULL,
              NULL,
              (GClassInitFunc) sim_rule_class_init,
              NULL,
              NULL,                       /* class data */
              sizeof (SimRule),
              0,                          /* number of pre-allocs */
              (GInstanceInitFunc) sim_rule_instance_init,
              NULL                        /* value table */
    };
    
    g_type_init ();
                                                                                                                             
    object_type = g_type_register_static (G_TYPE_OBJECT, "SimRule", &type_info, 0);
  }

  return object_type;
}

/*
 *
 *
 *
 *
 */
SimRule*
sim_rule_new (void)
{
  SimRule *rule;

  rule = SIM_RULE (g_object_new (SIM_TYPE_RULE, NULL));

  return rule;
}

/*
 *
 *
 *
 *
 */
gint
sim_rule_get_level (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), 0);

  return rule->_priv->level;
}

/*
 *
 *
 *
 *
 */
void
sim_rule_set_level (SimRule   *rule,
		    gint       level)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (level > 0);

  rule->_priv->level = level;
}

/*
 *
 *
 *
 *
 */
gint
sim_rule_get_protocol (SimRule   *rule)
{
  g_return_val_if_fail (rule, SIM_PROTOCOL_TYPE_NONE);
  g_return_val_if_fail (SIM_IS_RULE (rule), SIM_PROTOCOL_TYPE_NONE);

  return rule->_priv->protocol;
}

/*
 *
 *
 *
 *
 */
void
sim_rule_set_protocol (SimRule   *rule,
		       gint       protocol)
{
  g_return_if_fail (SIM_IS_RULE (rule));

  rule->_priv->protocol = protocol;
}


/*
 *
 *
 *
 *
 */
gboolean
sim_rule_get_not (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), FALSE);

  return rule->_priv->not;
}

/*
 *
 *
 *
 *
 */
void
sim_rule_set_not (SimRule   *rule,
		  gboolean   not)
{
  g_return_if_fail (SIM_IS_RULE (rule));

  rule->_priv->not = not;
}

/*
 *
 *
 *
 *
 */
SimRuleVarType
sim_rule_get_sticky_different (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), FALSE);

  return rule->_priv->sticky_different;
}

/*
 *
 *
 *
 *
 */
void
sim_rule_set_sticky_different (SimRule         *rule,
			     SimRuleVarType   sticky_different)
{
  g_return_if_fail (SIM_IS_RULE (rule));

  rule->_priv->sticky_different = sticky_different;
}

/*
 *
 *
 *
 *
 */
gchar*
sim_rule_get_name (SimRule   *rule)
{
  g_return_val_if_fail (rule, NULL);
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->name;
}

/*
 *
 *
 *
 *
 */
void
sim_rule_set_name (SimRule   *rule,
		   const gchar *name)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (name);

  if (rule->_priv->name)
    g_free (rule->_priv->name);

  rule->_priv->name = g_strdup (name);
}

/*
 *
 *
 *
 *
 */
gint
sim_rule_get_priority (SimRule   *rule)
{
  g_return_val_if_fail (rule, 0);
  g_return_val_if_fail (SIM_IS_RULE (rule), 0);

  if (rule->_priv->priority < 0)
    return 0;
  if (rule->_priv->priority > 5)
    return 5;

  return rule->_priv->priority;
}

/*
 *
 *
 *
 *
 */
void
sim_rule_set_priority (SimRule   *rule,
		       gint       priority)
{
  g_return_if_fail (SIM_IS_RULE (rule));

  if (priority < 0)
    rule->_priv->priority = 0;
  else if (priority > 5)
    rule->_priv->priority = 5;
  else 
    rule->_priv->priority = priority;
}

/*
 *
 *
 *
 *
 */
gint
sim_rule_get_reliability (SimRule   *rule)
{
  g_return_val_if_fail (rule, 0);
  g_return_val_if_fail (SIM_IS_RULE (rule), 0);

  if (rule->_priv->reliability <= 0)
    return 0;
  if (rule->_priv->reliability >= 10)
    return 10;

  return rule->_priv->reliability;
}

/*
 *
 *
 *
 *
 */
void
sim_rule_set_reliability (SimRule   *rule,
			  gint       reliability)
{
  g_return_if_fail (SIM_IS_RULE (rule));

  if (reliability < 0)
    rule->_priv->reliability = 0;
  else if (reliability > 10)
    rule->_priv->reliability = 10;
  else 
    rule->_priv->reliability = reliability;
}

/*
 *
 *
 *
 *
 */
gboolean
sim_rule_get_rel_abs (SimRule   *rule)
{
  g_return_val_if_fail (rule, FALSE);
  g_return_val_if_fail (SIM_IS_RULE (rule), FALSE);
  
  return rule->_priv->rel_abs;
}

/*
 *
 *
 *
 *
 */
void
sim_rule_set_rel_abs (SimRule   *rule,
		      gboolean   rel_abs)
{
  g_return_if_fail (SIM_IS_RULE (rule));

  rule->_priv->rel_abs = rel_abs;
}

/*
 *
 *
 *
 *
 */
SimConditionType
sim_rule_get_condition (SimRule   *rule)
{
  g_return_val_if_fail (rule, SIM_CONDITION_TYPE_NONE);
  g_return_val_if_fail (SIM_IS_RULE (rule), SIM_CONDITION_TYPE_NONE);

  return rule->_priv->condition;
}

/*
 *
 *
 *
 *
 */
void
sim_rule_set_condition (SimRule           *rule,
			SimConditionType   condition)
{
  g_return_if_fail (SIM_IS_RULE (rule));

  rule->_priv->condition = condition;
}

/*
 *
 *
 *
 *
 */
gchar*
sim_rule_get_value (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->value;
}

/*
 *
 *
 *
 *
 */
void
sim_rule_set_value (SimRule      *rule,
		    const gchar  *value)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (value);

  if (rule->_priv->value)
    g_free (rule->_priv->value);

  rule->_priv->value = g_strdup (value);
}

/*
 *
 *
 *
 *
 */
gint
sim_rule_get_interval (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), 0);

  return rule->_priv->interval;
}

/*
 *
 *
 *
 *
 */
void
sim_rule_set_interval (SimRule   *rule,
		       gint       interval)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (interval >= 0);

  rule->_priv->interval = interval;
}

/*
 *
 *
 *
 *
 */
gboolean
sim_rule_get_absolute (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), 0);

  return rule->_priv->absolute;
}

/*
 *
 *
 *
 *
 */
void
sim_rule_set_absolute (SimRule   *rule,
		       gboolean   absolute)
{
  g_return_if_fail (SIM_IS_RULE (rule));

  rule->_priv->absolute = absolute;
}

/*
 *
 *
 *
 *
 */
time_t
sim_rule_get_time_out (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), 0);

  return rule->_priv->time_out;
}

/*
 *
 *
 *
 *
 */
void
sim_rule_set_time_out (SimRule   *rule,
		       time_t      time_out)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (time_out >= 0);

  rule->_priv->time_out = time_out;
}

/*
 *
 *
 *
 *
 */
time_t
sim_rule_get_time_last (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), 0);

  return rule->_priv->time_last;
}

/*
 *
 *
 *
 *
 */
void
sim_rule_set_time_last (SimRule   *rule,
								       time_t      time_last)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (time_last >= 0);

  rule->_priv->time_last = time_last;
}

/*
 *
 *
 *
 *
 */
gint
sim_rule_get_occurrence (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), 0);

  return rule->_priv->occurrence;
}

/*
 *
 *
 *
 *
 */
void
sim_rule_set_occurrence (SimRule   *rule,
			 gint       occurrence)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (occurrence > 0);

  rule->_priv->occurrence = occurrence;
}

/*
 *
 *	FIXME: Not used anywhere
 */
gint
sim_rule_get_count (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), 0);

  return rule->_priv->count_occu;
}

/*
 *	FIXME: Not used anywhere
 *
 */
void
sim_rule_set_count (SimRule   *rule,
		    gint       count_occu)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (count_occu > 0);

  rule->_priv->count_occu = count_occu;
}

/*
 *
 *
 *
 *
 */
gint
sim_rule_get_plugin_id (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), 0);

  return rule->_priv->plugin_id;
}

/*
 *
 *
 *
 *
 */
void 
sim_rule_set_plugin_id (SimRule   *rule,
			gint       plugin_id)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (plugin_id >= 0);

  rule->_priv->plugin_id = plugin_id;
}

/*
 *
 *
 *
 *
 */
gint
sim_rule_get_plugin_sid (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), 0);

  return rule->_priv->plugin_sid;
}

/*
 *
 *
 *
 *
 */
void
sim_rule_set_plugin_sid (SimRule   *rule,
												gint       plugin_sid)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (plugin_sid > 0);

  rule->_priv->plugin_sid = plugin_sid;
}


void
sim_rule_set_filename (SimRule		*rule,
												gchar			*filename)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (filename);

  if (rule->_priv->ev_filename)
    g_free (rule->_priv->ev_filename);

  rule->_priv->ev_filename = g_strdup (filename);
}

void
sim_rule_set_username (SimRule		*rule,
												gchar			*username)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (username);

  if (rule->_priv->ev_username)
    g_free (rule->_priv->ev_username);

  rule->_priv->ev_username = g_strdup (username);
}

void
sim_rule_set_password (SimRule		*rule,
												gchar			*password)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (password);

  if (rule->_priv->ev_password)
    g_free (rule->_priv->ev_password);

  rule->_priv->ev_password = g_strdup (password);
}

void
sim_rule_set_userdata1 (SimRule		*rule,
												gchar			*userdata1)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (userdata1);

  if (rule->_priv->ev_userdata1)
    g_free (rule->_priv->ev_userdata1);

  rule->_priv->ev_userdata1 = g_strdup (userdata1);
}

void
sim_rule_set_userdata2 (SimRule		*rule,
												gchar			*userdata2)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (userdata2);

  if (rule->_priv->ev_userdata2)
    g_free (rule->_priv->ev_userdata2);

  rule->_priv->ev_userdata2 = g_strdup (userdata2);
}

void
sim_rule_set_userdata3 (SimRule		*rule,
												gchar			*userdata3)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (userdata3);

  if (rule->_priv->ev_userdata3)
    g_free (rule->_priv->ev_userdata3);

  rule->_priv->ev_userdata3 = g_strdup (userdata3);
}

void
sim_rule_set_userdata4 (SimRule		*rule,
												gchar			*userdata4)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (userdata4);

  if (rule->_priv->ev_userdata4)
    g_free (rule->_priv->ev_userdata4);

  rule->_priv->ev_userdata4 = g_strdup (userdata4);
}

void
sim_rule_set_userdata5 (SimRule		*rule,
												gchar			*userdata5)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (userdata5);

  if (rule->_priv->ev_userdata5)
    g_free (rule->_priv->ev_userdata5);

  rule->_priv->ev_userdata5 = g_strdup (userdata5);
}

void
sim_rule_set_userdata6 (SimRule		*rule,
												gchar			*userdata6)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (userdata6);

  if (rule->_priv->ev_userdata6)
    g_free (rule->_priv->ev_userdata6);

  rule->_priv->ev_userdata6 = g_strdup (userdata6);
}

void
sim_rule_set_userdata7 (SimRule		*rule,
												gchar			*userdata7)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (userdata7);

  if (rule->_priv->ev_userdata7)
    g_free (rule->_priv->ev_userdata7);

  rule->_priv->ev_userdata7 = g_strdup (userdata7);
}

void
sim_rule_set_userdata8 (SimRule		*rule,
												gchar			*userdata8)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (userdata8);

  if (rule->_priv->ev_userdata8)
    g_free (rule->_priv->ev_userdata8);

  rule->_priv->ev_userdata8 = g_strdup (userdata8);
}

void
sim_rule_set_userdata9 (SimRule		*rule,
												gchar			*userdata9)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (userdata9);

  if (rule->_priv->ev_userdata9)
    g_free (rule->_priv->ev_userdata9);

  rule->_priv->ev_userdata9 = g_strdup (userdata9);
}

gchar*
sim_rule_get_filename (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->ev_filename;
}

gchar*
sim_rule_get_username (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->ev_username;
}

gchar*
sim_rule_get_password (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->ev_password;
}

gchar*
sim_rule_get_userdata1 (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->ev_userdata1;
}

gchar*
sim_rule_get_userdata2 (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->ev_userdata2;
}

gchar*
sim_rule_get_userdata3 (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->ev_userdata3;
}

gchar*
sim_rule_get_userdata4 (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->ev_userdata4;
}

gchar*
sim_rule_get_userdata5 (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->ev_userdata5;
}

gchar*
sim_rule_get_userdata6 (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->ev_userdata6;
}

gchar*
sim_rule_get_userdata7 (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->ev_userdata7;
}

gchar*
sim_rule_get_userdata8 (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->ev_userdata8;
}

gchar*
sim_rule_get_userdata9 (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->ev_userdata9;
}



/*
 *
 *
 *
 *
 */
SimInet *
sim_rule_get_src_ia (SimRule *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->src_ia;
}

/*
 *
 *
 *
 *
 */
void 
sim_rule_set_src_ia (SimRule  *rule,
                     SimInet  *src_ia)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (SIM_IS_INET (src_ia));

  if (rule->_priv->src_ia)
    g_object_unref (rule->_priv->src_ia);

  rule->_priv->src_ia = sim_inet_clone (src_ia);
}

/*
 *
 *
 *
 *
 */
SimInet *
sim_rule_get_dst_ia (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->dst_ia;
}

/*
 *
 *
 *
 *
 */
void 
sim_rule_set_dst_ia (SimRule  *rule,
                     SimInet  *dst_ia)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (SIM_IS_INET (dst_ia));

  if (rule->_priv->dst_ia)
    g_object_unref (rule->_priv->dst_ia);

  rule->_priv->dst_ia = sim_inet_clone (dst_ia);
}

/**
 * sim_rule_get_src_host_id:
 * @rule: a #SimRule
 *
 * @return event src host #SimUuid
 */
SimUuid *
sim_rule_get_src_host_id (SimRule *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->src_host_id;
}

/**
 * sim_rule_set_src_host_id:
 * @rule: a #SimRule
 * @src_host_id: event src host #SimUuid
 */
void
sim_rule_set_src_host_id (SimRule *rule,
                          SimUuid *src_host_id)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (SIM_IS_UUID (src_host_id));

  if (rule->_priv->src_host_id)
    g_object_unref (rule->_priv->src_host_id);

  rule->_priv->src_host_id = g_object_ref (src_host_id);
}

/**
 * sim_rule_get_dst_host_id:
 * @rule: a #SimRule
 *
 * @return event dst host #SimUuid
 */
SimUuid *
sim_rule_get_dst_host_id (SimRule *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->dst_host_id;
}

/**
 * sim_rule_set_dst_host_id:
 * @rule: a #SimRule
 * @src_host_id: event dst host #SimUuid
 */
void
sim_rule_set_dst_host_id (SimRule *rule,
                             SimUuid *dst_host_id)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (SIM_IS_UUID (dst_host_id));

  if (rule->_priv->dst_host_id)
    g_object_unref (rule->_priv->dst_host_id);

  rule->_priv->dst_host_id = g_object_ref (dst_host_id);
}

/*
 *
 *
 *
 *
 */
gint
sim_rule_get_src_port (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), 0);

  return rule->_priv->src_port;
}

/*
 *
 *
 *
 *
 */
void sim_rule_set_src_port (SimRule   *rule,
			    gint       src_port)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (src_port >= 0);

  rule->_priv->src_port = src_port;
}

/*
 *
 *
 *
 *
 */
gint
sim_rule_get_dst_port (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), 0);

  return rule->_priv->dst_port;
}

/*
 *
 *
 *
 *
 */
void sim_rule_set_dst_port (SimRule   *rule,
			    gint       dst_port)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (dst_port >= 0);

  rule->_priv->dst_port = dst_port;
}

/**
 * sim_rule_get_src_max_asset:
 * @rule: a #SimRule
 *
 * returns max src asset of events matched
 */
gint
sim_rule_get_src_max_asset (SimRule *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), 0);

  return rule->_priv->src_max_asset;
}

/**
 * sim_rule_get_dst_max_asset:
 * @rule: a #SimRule
 *
 * returns max dst asset of events matched
 */
gint
sim_rule_get_dst_max_asset (SimRule *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), 0);

  return rule->_priv->dst_max_asset;
}

/*
 *
 *
 */
SimInet *
sim_rule_get_sensor (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->sensor;
}

/*
 *
 *
 */
void sim_rule_set_sensor (SimRule    *rule,
												  SimInet    *sensor)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (SIM_IS_INET (sensor));

  if (rule->_priv->sensor)
    g_object_unref (rule->_priv->sensor);

  rule->_priv->sensor = sensor;;
}

gint
sim_rule_get_product (SimRule *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), 0);

  return rule->_priv->tax_product;
}

gint
sim_rule_get_category (SimRule *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), 0);

  return rule->_priv->tax_category;
}

gint
sim_rule_get_subcategory (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), 0);

  return rule->_priv->tax_subcategory;
}

/*
 * Insert a single plugin_id in a GHashTable in a SimRule
 */
void
sim_rule_add_plugin_id (SimRule *rule, gint plugin_id)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (plugin_id >= 0);

	if(rule->_priv->plugin_ids == NULL)
    rule->_priv->plugin_ids = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);

	g_hash_table_insert (rule->_priv->plugin_ids, GINT_TO_POINTER(plugin_id), GINT_TO_POINTER(GENERIC_VALUE));
}

/*
 * Insert a single plugin_sid in a GHashTable in a SimRule
 */
void
sim_rule_add_plugin_sid (SimRule   *rule,
												    gint       plugin_sid)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (plugin_sid >= 0);

	if(rule->_priv->plugin_sids == NULL)
		rule->_priv->plugin_sids = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);

	g_hash_table_insert (rule->_priv->plugin_sids, GINT_TO_POINTER(plugin_sid), GINT_TO_POINTER(GENERIC_VALUE));
}

/*
 *
 *
 *
 *
 */
void
sim_rule_remove_plugin_sid (SimRule   *rule,
			    gint       plugin_sid)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (plugin_sid >= 0);

	g_hash_table_remove (rule->_priv->plugin_sids, GINT_TO_POINTER(plugin_sid));
}

GHashTable*
sim_rule_get_plugin_ids (SimRule   *rule)
{
  g_return_val_if_fail (rule != NULL, NULL);
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->plugin_ids;
}

gboolean
sim_rule_has_plugin_ids (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), FALSE);

  if (rule->_priv->plugin_ids && g_hash_table_size (rule->_priv->plugin_ids) > 0)
    return TRUE;
  else
    return FALSE;
}

/*
 *
 *
 *
 *
 */
GHashTable*
sim_rule_get_plugin_sids (SimRule   *rule)
{
  g_return_val_if_fail (rule != NULL, NULL);
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->plugin_sids;
}

GHashTable *
sim_rule_get_products (SimRule *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->products;
}

GHashTable *
sim_rule_get_categories (SimRule *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->categories;
}

GHashTable *
sim_rule_get_subcategories (SimRule *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->subcategories;
}

/**
 * sim_rule_add_src_host_id:
 * @rule: #SimRule object
 * @host_id: #SimUuid object
 *
 * Adds @host_id to src hosts.
 */
void
sim_rule_add_src_host_id (SimRule *rule,
                          SimUuid *host_id)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (SIM_IS_UUID (host_id));

  if (rule->_priv->src_hosts == NULL)
    rule->_priv->src_hosts = g_hash_table_new_full (sim_uuid_hash,
                                                    sim_uuid_equal,
                                                    g_object_unref,
                                                    NULL);
  g_hash_table_insert (rule->_priv->src_hosts,
                       (gpointer) g_object_ref (host_id),
                       GINT_TO_POINTER (GENERIC_VALUE));
}

/**
 * sim_rule_add_src_inet:
 * @rule: #SimRule object
 * @inet: #SimInet object
 *
 * Adds @inet to src inet tree.
 */
void
sim_rule_add_src_inet (SimRule    *rule,
                       SimInet    *inet)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (SIM_IS_INET (inet));

  if (rule->_priv->src_inets == NULL)
    rule->_priv->src_inets = sim_network_new ();

  sim_network_add_inet (rule->_priv->src_inets, inet);
}

/**
 * sim_rule_add_expand_src_asset_name:
 * @rule: #SimRule object
 * @name: name of the network or host
 *
 * Adds @name to the list of network names and hosts that later
 * will be expanded using information from the context.
 */
void
sim_rule_add_expand_src_asset_name (SimRule *rule,
                                    gchar *name)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (name);

  rule->_priv->expand_src_assets_names = g_list_prepend (rule->_priv->expand_src_assets_names, g_strdup (name));
}

/**
 * sim_rule_add_secure_src_inet:
 * @rule: #SimRule object
 * @inet: #SimInet object
 *
 * Append @inet into src_inets_ #SimNetwork
 * only if @inets wasn't previoulsy added
 */
void
sim_rule_add_secure_src_inet (SimRule *rule,
                              SimInet *inet)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (SIM_IS_INET (inet));

  if (rule->_priv->src_inets == NULL)
  {
    rule->_priv->src_inets = sim_network_new ();
  }
  else //check if inet is in the tree
  {
    SimInet *find = sim_network_search_inet (rule->_priv->src_inets, inet);
    if (find != NULL)
    {
      gchar *ip = sim_inet_get_canonical_name (inet);
      g_message ("Directive Error: %s duplicate src ip %s", rule->_priv->name, ip);
      g_free (ip);
      return;
    }
  }

  sim_network_add_inet (rule->_priv->src_inets, inet);
}

/**
 * sim_rule_add_src_net:
 * @rule: #SimRule object
 * @net: #SimNet object
 *
 * Append all #SimInet in @net into src_inets_ #SimNetwork
 */
void
sim_rule_add_src_net (SimRule *rule,
                      SimNet  *net)
{
  GList *inets;

  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (SIM_IS_NET (net));

  if (rule->_priv->src_inets == NULL)
    rule->_priv->src_inets = sim_network_new ();

  inets = sim_net_get_inets (net);
  while (inets)
  {
    SimInet *inet = (SimInet *) inets->data;
    sim_network_add_inet (rule->_priv->src_inets, inet);

    inets = g_list_next (inets);
  }
}

/**
 * sim_rule_get_src_inets:
 * @rule: ·SimRule object
 *
 * Returns: src ip #SimNetwork.
 */
SimNetwork *
sim_rule_get_src_inets (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->src_inets;
}

/**
 * sim_rule_get_expand_src_assets_names:
 * @rule: #SimRule object
 *
 * Returns: assets names list to expand.
 */
GList *
sim_rule_get_expand_src_assets_names (SimRule *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->expand_src_assets_names;
}

/**
 * sim_rule_add_dst_host_id:
 * @rule: #SimRule object
 * @host_id: #SimUuid object
 *
 * Adds @host_id to dst hosts.
 */
void
sim_rule_add_dst_host_id (SimRule *rule,
                          SimUuid *host_id)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (SIM_IS_UUID (host_id));

  if (rule->_priv->dst_hosts == NULL)
    rule->_priv->dst_hosts = g_hash_table_new_full (sim_uuid_hash,
                                                    sim_uuid_equal,
                                                    g_object_unref,
                                                    NULL);
  g_hash_table_insert (rule->_priv->dst_hosts,
                       (gpointer) g_object_ref (host_id),
                       GINT_TO_POINTER (GENERIC_VALUE));
}

/**
 * sim_rule_add_dst_host_id_not:
 * @rule: #SimRule object
 * @host_id: #SimUuid object
 *
 * Append @host_id into dst_hosts not #GHashTable (defined with "!")
 */
void
sim_rule_add_dst_host_id_not (SimRule *rule,
                              SimUuid *host_id)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (SIM_IS_UUID (host_id));

  if (rule->_priv->dst_hosts_not == NULL)
    rule->_priv->dst_hosts_not = g_hash_table_new_full (sim_uuid_hash,
                                                    sim_uuid_equal,
                                                    g_object_unref,
                                                    NULL);

  g_hash_table_insert (rule->_priv->dst_hosts_not,
                       (gpointer) g_object_ref (host_id),
                       GINT_TO_POINTER (GENERIC_VALUE));
}


/**
 * sim_rule_add_dst_inet:
 * @rule: #SimRule object
 * @inet: #SimInet object
 *
 * Adds @inet to dst inet tree.
 */
void
sim_rule_add_dst_inet (SimRule    *rule,
                       SimInet    *inet)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (SIM_IS_INET (inet));

  if (rule->_priv->dst_inets == NULL)
    rule->_priv->dst_inets = sim_network_new ();

  sim_network_add_inet (rule->_priv->dst_inets, inet);
}

/**
 * sim_rule_add_dst_net:
 * @rule: #SimRule object
 * @net: #SimNet object
 *
 * Append all #SimInet in @net into dst_inets_ #SimNetwork
 */
void
sim_rule_add_dst_net (SimRule *rule,
                      SimNet  *net)
{
  GList *inets;

  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (SIM_IS_NET (net));

  if (rule->_priv->dst_inets == NULL)
    rule->_priv->dst_inets = sim_network_new ();

  inets = sim_net_get_inets (net);
  while (inets)
  {
    SimInet *inet = (SimInet *) inets->data;
    sim_network_add_inet (rule->_priv->dst_inets, inet);

    inets = g_list_next (inets);
  }
}

/**
 * sim_rule_add_expand_dst_asset_name:
 * @rule: #SimRule object
 * @name: name of the network or host
 *
 * Adds @name to the list of network names and hosts that later
 * will be expanded using information from the context.
 */
void
sim_rule_add_expand_dst_asset_name (SimRule *rule,
                                    gchar *name)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (name);

  rule->_priv->expand_dst_assets_names = g_list_prepend (rule->_priv->expand_dst_assets_names, g_strdup (name));
}

/**
 * sim_rule_add_secure_dst_inet:
 * @rule: #SimRule object
 * @inet: #SimInet object
 *
 * Append @inet into dst_inets #SimNetwork
 * only if @inets wasn't previoulsy added
 */
void
sim_rule_add_secure_dst_inet (SimRule *rule,
                              SimInet *inet)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (SIM_IS_INET (inet));

  if (rule->_priv->dst_inets == NULL)
  {
    rule->_priv->dst_inets = sim_network_new ();
  }
  else //check if inet is in the tree
  {
    SimInet *find = sim_network_search_inet (rule->_priv->dst_inets, inet);
    if (find != NULL)
    {
      gchar *ip = sim_inet_get_canonical_name (inet);
      g_message ("Directive Error: %s duplicate dst ip %s", rule->_priv->name, ip);
      g_free (ip);
      return;
    }
  }

  sim_network_add_inet (rule->_priv->dst_inets, inet);
}


/**
 * sim_rule_get_dst_inets:
 * @rule: SimRule object
 *
 * Returns: dst ip #SimNetwork object
 */
SimNetwork *
sim_rule_get_dst_inets (SimRule *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->dst_inets;
}

/**
 * sim_rule_get_expand_dst_assets_names:
 * @rule: #SimRule object
 *
 * Returns: assets names list to expand.
 */
GList *
sim_rule_get_expand_dst_assets_names (SimRule *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->expand_dst_assets_names;
}

/*
 *
 *
 *
 *
 */
void
sim_rule_add_src_port (SimRule   *rule,
												  gint       src_port)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (src_port >= 0 && src_port <= 65535);

	if(rule->_priv->src_ports == NULL)
		rule->_priv->src_ports = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
  g_hash_table_insert (rule->_priv->src_ports, GINT_TO_POINTER(src_port), GINT_TO_POINTER(GENERIC_VALUE));
}

/*
 *
 *
 *
 *
 */
void
sim_rule_remove_src_port (SimRule   *rule,
			  gint       src_port)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (src_port >= 0);

	g_hash_table_remove (rule->_priv->src_ports, GINT_TO_POINTER(src_port));
}

/*
 *
 *
 *
 *
 */
GHashTable*
sim_rule_get_src_ports (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->src_ports;
}

/*
 *
 *
 *
 *
 */
void
sim_rule_add_dst_port (SimRule   *rule,
												  gint       dst_port)
{
  g_return_if_fail (SIM_IS_RULE (rule));
	g_return_if_fail (dst_port >= 0 && dst_port <= 65535);

	if(rule->_priv->dst_ports == NULL)
		rule->_priv->dst_ports = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
  g_hash_table_insert (rule->_priv->dst_ports, GINT_TO_POINTER(dst_port), GINT_TO_POINTER(GENERIC_VALUE));
}

/*
 *
 *
 *
 *
 */
void
sim_rule_remove_dst_port (SimRule   *rule,
			  gint       dst_port)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (dst_port >= 0);

	g_hash_table_remove (rule->_priv->dst_ports, GINT_TO_POINTER(dst_port));
}

/*
 *
 *
 *
 *
 */
GHashTable*
sim_rule_get_dst_ports (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->dst_ports;
}

/*
 *
 *
 *
 *
 */
void
sim_rule_add_protocol (SimRule   *rule,
												  SimProtocolType  protocol)
{
  g_return_if_fail (SIM_IS_RULE (rule));

	if(rule->_priv->protocols == NULL)
		rule->_priv->protocols = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
  g_hash_table_insert (rule->_priv->protocols, GINT_TO_POINTER(protocol), GINT_TO_POINTER(GENERIC_VALUE));
}

/*
 *
 *
 *
 *
 */
void
sim_rule_remove_protocol (SimRule   *rule,
			  SimProtocolType  protocol)
{
  g_return_if_fail (SIM_IS_RULE (rule));

  g_hash_table_remove (rule->_priv->protocols, GINT_TO_POINTER(protocol));
}

/*
 *
 *
 *
 *
 */
GHashTable*
sim_rule_get_protocols (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->protocols;
}

/*
 * Append a sensor to the list of sensors inside the rule. 
 * This is NOT the same that the single sensor which appears in the SimRule.
 */
void
sim_rule_add_sensor (SimRule    *rule,
                     SimSensor  *sensor)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (SIM_IS_SENSOR (sensor));

  if(rule->_priv->sensors == NULL)
    rule->_priv->sensors = g_hash_table_new_full (sim_inet_hash, sim_inet_equal, NULL, g_object_unref);

  g_hash_table_insert (rule->_priv->sensors, sim_sensor_get_ia (sensor), sensor);
}

void
sim_rule_add_product (SimRule   *rule,
                      gint       product)
{
  g_return_if_fail (rule);
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (product >= 0);

  if(rule->_priv->products == NULL)
    rule->_priv->products = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
  g_hash_table_insert (rule->_priv->products, GINT_TO_POINTER(product), GINT_TO_POINTER(GENERIC_VALUE));
}

void
sim_rule_add_category (SimRule   *rule,
                       gint       category)
{
  g_return_if_fail (rule);
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (category >= 0);

	if(rule->_priv->categories == NULL)
		rule->_priv->categories = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
  g_hash_table_insert (rule->_priv->categories, GINT_TO_POINTER(category), GINT_TO_POINTER(GENERIC_VALUE));
}

void
sim_rule_add_subcategory (SimRule   *rule,
												  gint       subcategory)
{
  g_return_if_fail (rule);
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (subcategory >= 0);

	if(rule->_priv->subcategories == NULL)
		rule->_priv->subcategories = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
  g_hash_table_insert (rule->_priv->subcategories, GINT_TO_POINTER(subcategory), GINT_TO_POINTER(GENERIC_VALUE));
}

void
sim_rule_add_suppress (SimRule *rule, gint plugin_id, gint plugin_sid)
{
  g_return_if_fail (rule);
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (plugin_id >= 0);
  g_return_if_fail (plugin_sid >= 0);

	if(rule->_priv->suppress == NULL)
    rule->_priv->suppress = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);

  g_hash_table_insert (rule->_priv->suppress, GINT_TO_POINTER(CANTOR_KEY (plugin_id, plugin_sid)), GINT_TO_POINTER(GENERIC_VALUE));
}

void
sim_rule_set_from_rep (SimRule *rule, gboolean from_rep)
{
  g_return_if_fail (rule);
  g_return_if_fail (SIM_IS_RULE (rule));

  rule->_priv->from_rep = from_rep;
}

void
sim_rule_set_to_rep (SimRule *rule, gboolean to_rep)
{
  g_return_if_fail (rule);
  g_return_if_fail (SIM_IS_RULE (rule));

  rule->_priv->to_rep = to_rep;
}

void
sim_rule_set_from_rep_min_rel (SimRule *rule, gint from_rep_min_rel)
{
  g_return_if_fail (rule);
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (from_rep_min_rel >= 0);

  rule->_priv->from_rep_min_rel = from_rep_min_rel;
}

void
sim_rule_set_to_rep_min_rel (SimRule *rule, gint to_rep_min_rel)
{
  g_return_if_fail (rule);
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (to_rep_min_rel >= 0);

  rule->_priv->to_rep_min_rel = to_rep_min_rel;
}

void
sim_rule_set_from_rep_min_pri (SimRule *rule, gint from_rep_min_pri)
{
  g_return_if_fail (rule);
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (from_rep_min_pri >= 0);

  rule->_priv->from_rep_min_pri = from_rep_min_pri;
}

void
sim_rule_set_to_rep_min_pri (SimRule *rule, gint to_rep_min_pri)
{
  g_return_if_fail (rule);
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (to_rep_min_pri >= 0);

  rule->_priv->to_rep_min_pri = to_rep_min_pri;
}

/**
 * sim_rule_add_expand_sensor_name:
 * @rule: #SimRule object
 * @name: name of the sensor
 *
 * Adds @name to the list of sensor names that later
 * will be expanded using information from the context.
 */
void
sim_rule_add_expand_sensor_name (SimRule *rule,
                                 gchar *name)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (name);

  rule->_priv->expand_sensors_names = g_list_prepend (rule->_priv->expand_sensors_names, g_strdup (name));
}

/*
 *
 *
 *
 *
 */
void
sim_rule_remove_sensor	 (SimRule    *rule,
													SimSensor  *sensor)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (SIM_IS_SENSOR (sensor));

  g_hash_table_remove (rule->_priv->sensors, sim_sensor_get_ia (sensor));
}

/*
 *
 *
 *
 *
 */
GHashTable*
sim_rule_get_sensors (SimRule   *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->sensors;
}

/**
 * sim_rule_get_expand_sensors_names:
 * @rule: #SimRule object
 *
 * Returns: sensors names list to expand.
 */
GList *
sim_rule_get_expand_sensors_names (SimRule *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->expand_sensors_names;
}

/*
 * Entities (contexts)
 */

/**
 * sim_rule_add_expand_entity:
 * @rule: #SimRule object
 * @name: name of the entity
 *
 * Adds @name to the list of entities that later
 * will be expanded using information from the container.
 */
void
sim_rule_add_expand_entity (SimRule *rule,
                            gchar   *name)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (name);

  rule->_priv->expand_entities = g_list_prepend (rule->_priv->expand_entities, g_strdup (name));
}

/**
 * sim_rule_add_expand_entity_not:
 * @rule: #SimRule object
 * @name: name of the entity
 *
 * Adds @name to the list of not entities that later
 * will be expanded using information from the container.
 */
void
sim_rule_add_expand_entity_not (SimRule *rule,
                                gchar   *name)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (name);

  rule->_priv->expand_entities = g_list_prepend (rule->_priv->expand_entities_not, g_strdup (name));
}

/**
 * sim_rule_get_expand_entities:
 * @rule: #SimRule object
 *
 * Returns: entities names list to expand.
 */
GList *
sim_rule_get_expand_entities (SimRule *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->expand_entities;
}

/**
 * sim_rule_get_expand_entities_not:
 * @rule: #SimRule object
 *
 * Returns: negated entities names list to expand.
 */
GList *
sim_rule_get_expand_entities_not (SimRule *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->expand_entities_not;
}

/**
 * sim_rule_add_entity:
 * @rule: #SimRule object
 * @context: #SimContext
 *
 * Adds @context to the list of entities
 */
void
sim_rule_add_entity (SimRule    *rule,
                     SimContext *context)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (SIM_IS_CONTEXT (context));

  rule->_priv->entities = g_list_prepend (rule->_priv->entities,
                                          g_object_ref (context));
}

/**
 * sim_rule_add_entity_not:
 * @rule: #SimRule object
 * @context: #SimContext
 *
 * Adds @context to the list of entities
 */
void
sim_rule_add_entity_not (SimRule    *rule,
                         SimContext *context)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (SIM_IS_CONTEXT (context));

  rule->_priv->entities_not = g_list_prepend (rule->_priv->entities_not,
                                              g_object_ref (context));
}

/**
 * sim_rule_get_entity:
 * @rule: #SimRule object
 *
 * Returns pointer to #SimContext entity
 */
SimContext *
sim_rule_get_entity (SimRule *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->entity;
}

/*
 *
 *
 *
 *
 */
void
sim_rule_append_var (SimRule         *rule,
								     SimRuleVar      *var)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (var);

  rule->_priv->vars = g_list_append (rule->_priv->vars, var);  
}

/*
 *
 * Inside var there is the kind of event (src_ip, protocol, plugin_sid or whatever) and the level to which is 
 *referencing. i.e. if in a directive appears 1:SRC_IP that info is inside the var
 *
 */
GList*
sim_rule_get_vars (SimRule     *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->vars;		//SimRuleVar
}

/*
 * Here we will group some keywords: its a pain to have multiple functions that do exactly the same.
 * //FIXME: In OSSIM v2, I'll change all this with a hash table where the insertion of new keywords
 * will be as easy as define them somewhere
 */
void
sim_rule_append_generic_text	(SimRule				*rule,
												      gchar						*data,
												      SimRuleVarType	field_type)
{
	g_return_if_fail (SIM_IS_RULE (rule));
			
  ossim_debug ( "sim_rule_append_generic: %s", data);
	switch (field_type)
	{
		case	SIM_RULE_VAR_FILENAME:
						rule->_priv->filename = g_list_append (rule->_priv->filename, data);
						break;
		case	SIM_RULE_VAR_USERNAME:
						rule->_priv->username = g_list_append (rule->_priv->username, data);
						break;
		case	SIM_RULE_VAR_PASSWORD:
						rule->_priv->password = g_list_append (rule->_priv->password, data);
					  ossim_debug ( "sim_rule_append_generic: password: %s", data);
						break;
		case	SIM_RULE_VAR_USERDATA1:
						rule->_priv->userdata1 = g_list_append (rule->_priv->userdata1, data);
						break;
		case	SIM_RULE_VAR_USERDATA2:
						rule->_priv->userdata2 = g_list_append (rule->_priv->userdata2, data);
						break;
		case	SIM_RULE_VAR_USERDATA3:
						rule->_priv->userdata3 = g_list_append (rule->_priv->userdata3, data);
						break;
		case	SIM_RULE_VAR_USERDATA4:
					  ossim_debug ( "sim_rule_append_generic: userdata4: %s", data);
						rule->_priv->userdata4 = g_list_append (rule->_priv->userdata4, data);
						break;
		case	SIM_RULE_VAR_USERDATA5:
						rule->_priv->userdata5 = g_list_append (rule->_priv->userdata5, data);
						break;
		case	SIM_RULE_VAR_USERDATA6:
						rule->_priv->userdata6 = g_list_append (rule->_priv->userdata6, data);
						break;
		case	SIM_RULE_VAR_USERDATA7:
						rule->_priv->userdata7 = g_list_append (rule->_priv->userdata7, data);
						break;
		case	SIM_RULE_VAR_USERDATA8:
						rule->_priv->userdata8 = g_list_append (rule->_priv->userdata8, data);
						break;
		case	SIM_RULE_VAR_USERDATA9:
						rule->_priv->userdata9 = g_list_append (rule->_priv->userdata9, data);
						break;
		default:	
						g_return_if_fail (0);
	}
}
			
void
sim_rule_remove_generic_text	(SimRule				*rule,
												      gchar						*data,
												      SimRuleVarType	field_type)
{
	g_return_if_fail (SIM_IS_RULE (rule));
			
	switch (field_type)
	{
		case	SIM_RULE_VAR_FILENAME:
						rule->_priv->filename = g_list_remove (rule->_priv->filename, data);
						break;
		case	SIM_RULE_VAR_USERNAME:
						rule->_priv->username = g_list_remove (rule->_priv->username, data);
						break;
		case	SIM_RULE_VAR_PASSWORD:
						rule->_priv->password = g_list_remove (rule->_priv->password, data);
						break;
		case	SIM_RULE_VAR_USERDATA1:
						rule->_priv->userdata1 = g_list_remove (rule->_priv->userdata1, data);
						break;
		case	SIM_RULE_VAR_USERDATA2:
						rule->_priv->userdata2 = g_list_remove (rule->_priv->userdata2, data);
						break;
		case	SIM_RULE_VAR_USERDATA3:
						rule->_priv->userdata3 = g_list_remove (rule->_priv->userdata3, data);
						break;
		case	SIM_RULE_VAR_USERDATA4:
						rule->_priv->userdata4 = g_list_remove (rule->_priv->userdata4, data);
						break;
		case	SIM_RULE_VAR_USERDATA5:
						rule->_priv->userdata5 = g_list_remove (rule->_priv->userdata5, data);
						break;
		case	SIM_RULE_VAR_USERDATA6:
						rule->_priv->userdata6 = g_list_remove (rule->_priv->userdata6, data);
						break;
		case	SIM_RULE_VAR_USERDATA7:
						rule->_priv->userdata7 = g_list_remove (rule->_priv->userdata7, data);
						break;
		case	SIM_RULE_VAR_USERDATA8:
						rule->_priv->userdata8 = g_list_remove (rule->_priv->userdata8, data);
						break;
		case	SIM_RULE_VAR_USERDATA9:
						rule->_priv->userdata9 = g_list_remove (rule->_priv->userdata9, data);
						break;
		default:
						g_return_if_fail (0);
	}
}

GList *
sim_rule_get_generic	(SimRule				*rule, 
											SimRuleVarType	field_type)
{
	g_return_val_if_fail (SIM_IS_RULE (rule), NULL);
			
	switch (field_type)
	{
		case	SIM_RULE_VAR_FILENAME:
						return rule->_priv->filename;
						break;
		case	SIM_RULE_VAR_USERNAME:
						return rule->_priv->username;
						break;
		case	SIM_RULE_VAR_PASSWORD:
						return rule->_priv->password;
						break;
		case	SIM_RULE_VAR_USERDATA1:
						return rule->_priv->userdata1;
						break;
		case	SIM_RULE_VAR_USERDATA2:
						return rule->_priv->userdata2;
						break;
		case	SIM_RULE_VAR_USERDATA3:
						return rule->_priv->userdata3;
						break;
		case	SIM_RULE_VAR_USERDATA4:
						return rule->_priv->userdata4;
						break;
		case	SIM_RULE_VAR_USERDATA5:
						return rule->_priv->userdata5;
						break;
		case	SIM_RULE_VAR_USERDATA6:
						return rule->_priv->userdata6;
						break;
		case	SIM_RULE_VAR_USERDATA7:
						return rule->_priv->userdata7;
						break;
		case	SIM_RULE_VAR_USERDATA8:
						return rule->_priv->userdata8;
						break;
		case	SIM_RULE_VAR_USERDATA9:
						return rule->_priv->userdata9;
						break;
		default:
						g_return_val_if_fail (0, NULL);
	}
  return NULL;
}

/**
 * sim_ryle_add_src_inet_not:
 * @rule: #SimRule object
 * @inet: #SimInet object
 *
 * Append @inet into src_inets_ not #SimNetwork (defined with "!")
 */
void
sim_rule_add_src_inet_not (SimRule *rule,
                           SimInet *inet)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (SIM_IS_INET (inet));

  if (rule->_priv->src_inets_not == NULL)
    rule->_priv->src_inets_not = sim_network_new ();

  sim_network_add_inet (rule->_priv->src_inets_not, inet);
}

/**
 * sim_rule_add_src_host_id_not:
 * @rule: #SimRule object
 * @host_id: #SimUuid object
 *
 * Append @inet into src_inets_ not #SimNetwork (defined with "!")
 */
void
sim_rule_add_src_host_id_not (SimRule *rule,
                              SimUuid *host_id)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (SIM_IS_UUID (host_id));

  if (rule->_priv->src_hosts == NULL)
    rule->_priv->src_hosts = g_hash_table_new_full (sim_uuid_hash,
                                                    sim_uuid_equal,
                                                    g_object_unref,
                                                    NULL);
  g_hash_table_insert (rule->_priv->src_hosts,
                       (gpointer) g_object_ref (host_id),
                       GINT_TO_POINTER (GENERIC_VALUE));
}

/**
 * sim_rule_add_expand_src_asset_name_not:
 * @rule: #SimRule object
 * @name: name of the network or host
 *
 * Adds @name to the list of network names and hosts that later
 * will be expanded using information from the context.
 */
void
sim_rule_add_expand_src_asset_name_not (SimRule *rule,
                                        gchar *name)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (name);

  rule->_priv->expand_src_assets_names_not = g_list_prepend (rule->_priv->expand_src_assets_names_not, g_strdup (name));
}

/**
 * sim_rule_add_secure_src_inet_not:
 * @rule: #SimRule object
 * @inet: #SimInet object
 *
 * Append @inet into src_inets_ not #SimNetwork (defined with "!")
 * only if @inets wasn't previoulsy added
 */
void
sim_rule_add_secure_src_inet_not (SimRule *rule,
                                  SimInet *inet)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (SIM_IS_INET (inet));

  if (rule->_priv->src_inets_not == NULL)
  {
    rule->_priv->src_inets_not = sim_network_new ();
  }
  else //check if inet is in the tree
  {
    SimInet *find = sim_network_search_inet (rule->_priv->src_inets_not, inet);
    if (find != NULL)
    {
      gchar *ip = sim_inet_get_canonical_name (inet);
      g_message ("Directive Error: %s duplicate !src ip %s", rule->_priv->name, ip);
      g_free (ip);
      return;
    }
  }

  sim_network_add_inet (rule->_priv->src_inets_not, inet);
}

/**
 * sim_rule_add_src_net_not:
 * @rule: #SimRule object
 * @net: #SimNet object
 *
 * Append all #SimInet in @net into src_inets_ not #SimNetwork (defined with "!")
 */
void
sim_rule_add_src_net_not (SimRule *rule,
                          SimNet  *net)
{
  GList *inets;

  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (SIM_IS_NET (net));

  if (rule->_priv->src_inets_not == NULL)
    rule->_priv->src_inets_not = sim_network_new ();

  inets = sim_net_get_inets (net);
  while (inets)
  {
    SimInet *inet = (SimInet *) inets->data;
    sim_network_add_inet (rule->_priv->src_inets_not, inet);

    inets = g_list_next (inets);
  }
}

/**
 * sim_ryle_add_dst_inet_not:
 * @rule: #SimRule object
 * @inet: #SimInet object
 *
 * Append @inet into dst_inets_ not #SimNetwork (defined with "!")
 */
void
sim_rule_add_dst_inet_not (SimRule *rule,
                           SimInet *inet)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (SIM_IS_INET (inet));

  if (rule->_priv->dst_inets_not == NULL)
    rule->_priv->dst_inets_not = sim_network_new ();

  sim_network_add_inet (rule->_priv->dst_inets_not, inet);
}

/**
 * sim_rule_add_dst_net_not:
 * @rule: #SimRule object
 * @net: #SimNet object
 *
 * Append all #SimInet in @net into dst_inets_ not #SimNetwork (defined with "!")
 */
void
sim_rule_add_dst_net_not (SimRule *rule,
                          SimNet  *net)
{
  GList *inets;

  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (SIM_IS_NET (net));

  if (rule->_priv->dst_inets_not == NULL)
    rule->_priv->dst_inets_not = sim_network_new ();

  inets = sim_net_get_inets (net);
  while (inets)
  {
    SimInet *inet = (SimInet *) inets->data;
    sim_network_add_inet (rule->_priv->dst_inets_not, inet);

    inets = g_list_next (inets);
  }
}

/**
 * sim_rule_add_expand_dst_asset_name_not:
 * @rule: #SimRule object
 * @name: name of the network or host
 *
 * Adds @name to the list of network names and hosts that later
 * will be expanded using information from the context.
 */
void
sim_rule_add_expand_dst_asset_name_not (SimRule *rule,
                                        gchar *name)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (name);

  rule->_priv->expand_dst_assets_names_not = g_list_prepend (rule->_priv->expand_dst_assets_names_not, g_strdup (name));
}

/**
 * sim_rule_add_secure_dst_inet_not:
 * @rule: #SimRule object
 * @inet: #SimInet object
 *
 * Append @inet into dst_inets_ not #SimNetwork (defined with "!")
 * only if @inets wasn't previoulsy added
 */
void
sim_rule_add_secure_dst_inet_not (SimRule *rule,
                                  SimInet *inet)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (SIM_IS_INET (inet));

  if (rule->_priv->dst_inets_not == NULL)
  {
    rule->_priv->dst_inets_not = sim_network_new ();
  }
  else //check if inet is in the tree
  {
    SimInet *find = sim_network_search_inet (rule->_priv->dst_inets_not, inet);
    if (find != NULL)
    {
      gchar *ip = sim_inet_get_canonical_name (inet);
      g_message ("Directive Error: %s duplicate !dst ip %s", rule->_priv->name, ip);
      g_free (ip);
      return;
    }
  }

  sim_network_add_inet (rule->_priv->dst_inets_not, inet);
}

/*
 * 
 */
void 
sim_rule_add_src_port_not (SimRule *rule, 
                           gint	    src_port) 
{
	g_return_if_fail (SIM_IS_RULE (rule)); 
	g_return_if_fail (src_port >= 0 && src_port <= 65535);

	if(rule->_priv->src_ports_not == NULL)
		rule->_priv->src_ports_not = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
	g_hash_table_insert (rule->_priv->src_ports_not, GINT_TO_POINTER(src_port), GINT_TO_POINTER(GENERIC_VALUE)); 
}

/*
 * 
 */
void 
sim_rule_add_dst_port_not (SimRule *rule, 
															gint	dst_port) 
{
	g_return_if_fail (SIM_IS_RULE (rule)); 
	g_return_if_fail (dst_port >= 0 && dst_port <= 65535);

	if(rule->_priv->dst_ports_not == NULL)
		rule->_priv->dst_ports_not = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
	g_hash_table_insert (rule->_priv->dst_ports_not, GINT_TO_POINTER(dst_port), GINT_TO_POINTER(GENERIC_VALUE)); 
}

void
sim_rule_add_plugin_id_not (SimRule *rule,
														gint plugin_id)
{
	g_return_if_fail (rule);
	g_return_if_fail (SIM_IS_RULE (rule));
	g_return_if_fail (plugin_id);

	if(rule->_priv->plugin_ids_not == NULL)
    rule->_priv->plugin_ids_not = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
	g_hash_table_insert (rule->_priv->plugin_ids_not, GINT_TO_POINTER(plugin_id), GINT_TO_POINTER(GENERIC_VALUE));
}

/*
 * 
 */
void 
sim_rule_add_plugin_sid_not (SimRule *rule, 
																gint plugin_sid) 
{
	g_return_if_fail (rule); 
	g_return_if_fail (SIM_IS_RULE (rule)); 
	g_return_if_fail (plugin_sid); 

	if(rule->_priv->plugin_sids_not == NULL)
		rule->_priv->plugin_sids_not = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
	g_hash_table_insert (rule->_priv->plugin_sids_not, GINT_TO_POINTER(plugin_sid), GINT_TO_POINTER(GENERIC_VALUE)); 
}
/*
 * 
 */
void 
sim_rule_add_protocol_not (SimRule *rule, 
															gint protocol) 
{
	g_return_if_fail (SIM_IS_RULE (rule)); 
	g_return_if_fail (protocol); 

	if(rule->_priv->protocols_not == NULL)
		rule->_priv->protocols_not = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);

	g_hash_table_insert (rule->_priv->protocols_not, GINT_TO_POINTER(protocol), GINT_TO_POINTER(GENERIC_VALUE)); 
}
/*
 * 
 */
void 
sim_rule_add_sensor_not (SimRule *rule, 
														SimSensor *sensor) 
{
	g_return_if_fail (SIM_IS_RULE (rule)); 
	g_return_if_fail (SIM_IS_SENSOR (sensor)); 

	guint hash = gnet_inetaddr_hash (sim_sensor_get_ia (sensor));
	
	if(rule->_priv->sensors_not)
		rule->_priv->sensors_not = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, g_object_unref);

	g_hash_table_insert (rule->_priv->sensors_not, GUINT_TO_POINTER(hash), sensor);
}

void
sim_rule_add_product_not (SimRule *rule,
                          gint product)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (product);

  if(rule->_priv->product_not == NULL)
    rule->_priv->product_not = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
  g_hash_table_insert (rule->_priv->product_not, GINT_TO_POINTER(product), GINT_TO_POINTER(GENERIC_VALUE));
}

void
sim_rule_add_category_not (SimRule *rule,
                           gint category)
{
	g_return_if_fail (rule);
	g_return_if_fail (SIM_IS_RULE (rule));
	g_return_if_fail (category);

	if(rule->_priv->category_not == NULL)
		rule->_priv->category_not = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
	g_hash_table_insert (rule->_priv->category_not, GINT_TO_POINTER(category), GINT_TO_POINTER(GENERIC_VALUE));
}

void
sim_rule_add_subcategory_not (SimRule *rule,
                              gint subcategory)
{
	g_return_if_fail (rule);
	g_return_if_fail (SIM_IS_RULE (rule));
	g_return_if_fail (subcategory);

	if(rule->_priv->subcategory_not == NULL)
		rule->_priv->subcategory_not = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
  g_hash_table_insert (rule->_priv->subcategory_not, GINT_TO_POINTER(subcategory), GINT_TO_POINTER(GENERIC_VALUE));
}

/**
 * sim_rule_add_expand_sensor_name_not:
 * @rule: #SimRule object
 * @name: name of the sensor
 *
 * Adds @name to the list of sensor names that later
 * will be expanded using information from the context.
 */
void
sim_rule_add_expand_sensor_name_not (SimRule *rule,
                                 gchar *name)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (name);

  rule->_priv->expand_sensors_names_not = g_list_prepend (rule->_priv->expand_sensors_names_not, g_strdup (name));
}

//The following functions remove the not ("!") elements in the rule
/*
 * 
 */
void 
sim_rule_remove_src_port_not (SimRule *rule, 
															gint	  src_port) 
{
	g_return_if_fail (SIM_IS_RULE (rule)); 
	g_return_if_fail (src_port); 

	g_hash_table_remove (rule->_priv->src_ports_not, GINT_TO_POINTER(src_port)); 
}

/*
 * 
 */
void 
sim_rule_remove_dst_port_not (SimRule *rule, 
															gint	dst_port) 
{
	g_return_if_fail (SIM_IS_RULE (rule)); 
	g_return_if_fail (dst_port); 

	g_hash_table_remove (rule->_priv->dst_ports_not, GINT_TO_POINTER(dst_port)); 
}

/*
 * 
 */
void 
sim_rule_remove_plugin_sid_not (SimRule *rule, 
																gint plugin_sid) 
{
	g_return_if_fail (SIM_IS_RULE (rule)); 
	g_return_if_fail (plugin_sid); 

	g_hash_table_remove (rule->_priv->plugin_sids_not, GINT_TO_POINTER(plugin_sid));
}
/*
 * 
 */
void 
sim_rule_remove_protocol_not (SimRule *rule, 
															gint protocol) 
{
	g_return_if_fail (SIM_IS_RULE (rule)); 
	g_return_if_fail (protocol); 

	g_hash_table_remove (rule->_priv->protocols_not, GINT_TO_POINTER(protocol));
}
/*
 * 
 */
void 
sim_rule_remove_sensor_not (SimRule *rule, 
														SimSensor *sensor) 
{
	g_return_if_fail (SIM_IS_RULE (rule)); 
	g_return_if_fail (SIM_IS_SENSOR (sensor)); 

	guint hash = gnet_inetaddr_hash (sim_sensor_get_ia (sensor));
	g_hash_table_remove (rule->_priv->sensors_not, GUINT_TO_POINTER(hash));
}

/**
 * sim_rule_get_src_inets_not:
 * @rule: SimRule object
 *
 * Returns src ip not SimNetwork object
 *
 **/
SimNetwork *
sim_rule_get_src_inets_not (SimRule *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->src_inets_not;
}

/**
 * sim_rule_get_expand_src_assets_names_not:
 * @rule: #SimRule object
 *
 * Returns: assets names list to expand.
 */
GList *
sim_rule_get_expand_src_assets_names_not (SimRule *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->expand_src_assets_names_not;
}

/**
 * sim_rule_get_dst_inets_not:
 * @rule: SimRule object
 *
 * Returns dst ip not SimNetwork object
 *
 **/
SimNetwork *
sim_rule_get_dst_inets_not (SimRule *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->dst_inets_not;
}

/**
 * sim_rule_get_expand_dst_assets_names_not:
 * @rule: #SimRule object
 *
 * Returns: assets names list to expand.
 */
GList *
sim_rule_get_expand_dst_assets_names_not (SimRule *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->expand_dst_assets_names_not;
}

/*
 *
 */
GHashTable* 
sim_rule_get_src_ports_not (SimRule *rule) 
{ 
 g_return_val_if_fail (SIM_IS_RULE (rule), NULL); 
 
 return rule->_priv->src_ports_not; 
} 

/*
 *
 */
GHashTable* 
sim_rule_get_dst_ports_not (SimRule *rule) 
{ 
 g_return_val_if_fail (SIM_IS_RULE (rule), NULL); 
 
 return rule->_priv->dst_ports_not; 
} 

GHashTable *
sim_rule_get_plugin_ids_not (SimRule *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->plugin_ids_not;
}

/*
 *
 */
GHashTable* 
sim_rule_get_plugin_sids_not (SimRule *rule) 
{ 
 g_return_val_if_fail (SIM_IS_RULE (rule), NULL); 
 
 return rule->_priv->plugin_sids_not; 
} 

/*
 *
 */
GHashTable* 
sim_rule_get_protocols_not (SimRule *rule) 
{ 
 g_return_val_if_fail (SIM_IS_RULE (rule), NULL); 
 
 return rule->_priv->protocols_not; 
} 

/*
 *
 */
GHashTable* 
sim_rule_get_sensors_not (SimRule *rule) 
{ 
 g_return_val_if_fail (SIM_IS_RULE (rule), NULL); 
 
 return rule->_priv->sensors_not; 
}

/**
 * sim_rule_get_expand_sensors_names_not:
 * @rule: #SimRule object
 *
 * Returns: sensors names list to expand.
 */
GList *
sim_rule_get_expand_sensors_names_not (SimRule *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  return rule->_priv->expand_sensors_names_not;
}

void
sim_rule_append_generic_text_not	(SimRule				*rule,
														      gchar						*data,
														      SimRuleVarType	field_type)
{
	g_return_if_fail (SIM_IS_RULE (rule));
			
	switch (field_type)
	{
		case	SIM_RULE_VAR_FILENAME:
						rule->_priv->filename_not = g_list_append (rule->_priv->filename_not, data);
						break;
		case	SIM_RULE_VAR_USERNAME:
						rule->_priv->username_not = g_list_append (rule->_priv->username_not, data);
						break;
		case	SIM_RULE_VAR_PASSWORD:
						rule->_priv->password_not = g_list_append (rule->_priv->password_not, data);
						break;
		case	SIM_RULE_VAR_USERDATA1:
						rule->_priv->userdata1_not = g_list_append (rule->_priv->userdata1_not, data);
						break;
		case	SIM_RULE_VAR_USERDATA2:
						rule->_priv->userdata2_not = g_list_append (rule->_priv->userdata2_not, data);
						break;
		case	SIM_RULE_VAR_USERDATA3:
						rule->_priv->userdata3_not = g_list_append (rule->_priv->userdata3_not, data);
						break;
		case	SIM_RULE_VAR_USERDATA4:
						rule->_priv->userdata4_not = g_list_append (rule->_priv->userdata4_not, data);
						break;
		case	SIM_RULE_VAR_USERDATA5:
						rule->_priv->userdata5_not = g_list_append (rule->_priv->userdata5_not, data);
						break;
		case	SIM_RULE_VAR_USERDATA6:
						rule->_priv->userdata6_not = g_list_append (rule->_priv->userdata6_not, data);
						break;
		case	SIM_RULE_VAR_USERDATA7:
						rule->_priv->userdata7_not = g_list_append (rule->_priv->userdata7_not, data);
						break;
		case	SIM_RULE_VAR_USERDATA8:
						rule->_priv->userdata8_not = g_list_append (rule->_priv->userdata8_not, data);
						break;
		case	SIM_RULE_VAR_USERDATA9:
						rule->_priv->userdata9_not = g_list_append (rule->_priv->userdata9_not, data);
						break;
		default:
						g_return_if_fail (0);
	}
}
			
void
sim_rule_remove_generic_text_not	(SimRule				*rule,
														      gchar						*data,
														      SimRuleVarType	field_type)
{
	g_return_if_fail (SIM_IS_RULE (rule));
			
	switch (field_type)
	{
		case	SIM_RULE_VAR_FILENAME:
						rule->_priv->filename_not = g_list_remove (rule->_priv->filename_not, data);
						break;
		case	SIM_RULE_VAR_USERNAME:
						rule->_priv->username_not = g_list_remove (rule->_priv->username_not, data);
						break;
		case	SIM_RULE_VAR_PASSWORD:
						rule->_priv->password_not = g_list_remove (rule->_priv->password_not, data);
						break;
		case	SIM_RULE_VAR_USERDATA1:
						rule->_priv->userdata1_not = g_list_remove (rule->_priv->userdata1_not, data);
						break;
		case	SIM_RULE_VAR_USERDATA2:
						rule->_priv->userdata2_not = g_list_remove (rule->_priv->userdata2_not, data);
						break;
		case	SIM_RULE_VAR_USERDATA3:
						rule->_priv->userdata3_not = g_list_remove (rule->_priv->userdata3_not, data);
						break;
		case	SIM_RULE_VAR_USERDATA4:
						rule->_priv->userdata4_not = g_list_remove (rule->_priv->userdata4_not, data);
						break;
		case	SIM_RULE_VAR_USERDATA5:
						rule->_priv->userdata5_not = g_list_remove (rule->_priv->userdata5_not, data);
						break;
		case	SIM_RULE_VAR_USERDATA6:
						rule->_priv->userdata6_not = g_list_remove (rule->_priv->userdata6_not, data);
						break;
		case	SIM_RULE_VAR_USERDATA7:
						rule->_priv->userdata7_not = g_list_remove (rule->_priv->userdata7_not, data);
						break;
		case	SIM_RULE_VAR_USERDATA8:
						rule->_priv->userdata8_not = g_list_remove (rule->_priv->userdata8_not, data);
						break;
		case	SIM_RULE_VAR_USERDATA9:
						rule->_priv->userdata9_not = g_list_remove (rule->_priv->userdata9_not, data);
						break;
		default:
						g_return_if_fail (0);
	}
}

GList *
sim_rule_get_generic_text_not	(SimRule				*rule,
													    SimRuleVarType	field_type)
{
	g_return_val_if_fail (SIM_IS_RULE (rule), NULL);
			
	switch (field_type)
	{
		case	SIM_RULE_VAR_FILENAME:
						return rule->_priv->filename_not;
						break;
		case	SIM_RULE_VAR_USERNAME:
						return rule->_priv->username_not;
						break;
		case	SIM_RULE_VAR_PASSWORD:
						return rule->_priv->password_not;
						break;
		case	SIM_RULE_VAR_USERDATA1:
						return rule->_priv->userdata1_not;
						break;
		case	SIM_RULE_VAR_USERDATA2:
						return rule->_priv->userdata2_not;
						break;
		case	SIM_RULE_VAR_USERDATA3:
						return rule->_priv->userdata3_not;
						break;
		case	SIM_RULE_VAR_USERDATA4:
						return rule->_priv->userdata4_not;
						break;
		case	SIM_RULE_VAR_USERDATA5:
						return rule->_priv->userdata5_not;
						break;
		case	SIM_RULE_VAR_USERDATA6:
						return rule->_priv->userdata6_not;
						break;
		case	SIM_RULE_VAR_USERDATA7:
						return rule->_priv->userdata7_not;
						break;
		case	SIM_RULE_VAR_USERDATA8:
						return rule->_priv->userdata8_not;
						break;
		case	SIM_RULE_VAR_USERDATA9:
						return rule->_priv->userdata9_not;
						break;
		default:
						g_return_val_if_fail (0, NULL);
	}
  return NULL;
}	

/*
 *
 *
 *
 *
 */
SimRule*
sim_rule_clone (SimRule     *rule)
{
  SimRule         *new_rule;
  GList           *list;
  GHashTableIter  iter;
  gpointer        key, value;

  g_return_val_if_fail (SIM_IS_RULE (rule), FALSE);

  new_rule = SIM_RULE (g_object_new (SIM_TYPE_RULE, NULL));
  new_rule->type = rule->type;
  new_rule->_priv->level = rule->_priv->level;
  new_rule->_priv->name = g_strdup (rule->_priv->name);
  new_rule->_priv->not = rule->_priv->not;

  new_rule->_priv->sticky_different = rule->_priv->sticky_different;

  new_rule->_priv->priority = rule->_priv->priority;
  new_rule->_priv->reliability = rule->_priv->reliability;
  new_rule->_priv->rel_abs = rule->_priv->rel_abs;

  new_rule->_priv->time_out = rule->_priv->time_out;
  new_rule->_priv->occurrence = rule->_priv->occurrence;

  new_rule->_priv->plugin_id = rule->_priv->plugin_id;
  new_rule->_priv->plugin_sid = rule->_priv->plugin_sid;

  new_rule->_priv->src_ia = (rule->_priv->src_ia) ? g_object_ref (rule->_priv->src_ia) : NULL;
  new_rule->_priv->dst_ia = (rule->_priv->dst_ia) ? g_object_ref (rule->_priv->dst_ia) : NULL;
  new_rule->_priv->src_host_id = (rule->_priv->src_host_id) ? g_object_ref (rule->_priv->src_host_id) : NULL;
  new_rule->_priv->dst_host_id = (rule->_priv->dst_host_id) ? g_object_ref (rule->_priv->dst_host_id) : NULL;
  new_rule->_priv->src_port = rule->_priv->src_port;
  new_rule->_priv->dst_port = rule->_priv->dst_port;
  new_rule->_priv->src_max_asset = rule->_priv->src_max_asset;
  new_rule->_priv->dst_max_asset = rule->_priv->dst_max_asset;
  new_rule->_priv->protocol = rule->_priv->protocol;
  new_rule->_priv->sensor = (rule->_priv->sensor) ? g_object_ref (rule->_priv->sensor) : NULL;

  new_rule->_priv->tax_product = rule->_priv->tax_product;
  new_rule->_priv->tax_category = rule->_priv->tax_category;
  new_rule->_priv->tax_subcategory = rule->_priv->tax_subcategory;

  new_rule->_priv->entity = (rule->_priv->entity) ? g_object_ref (rule->_priv->entity) : NULL;

  new_rule->_priv->condition = rule->_priv->condition;
  new_rule->_priv->value = g_strdup (rule->_priv->value);
  new_rule->_priv->interval = rule->_priv->interval;
  new_rule->_priv->absolute= rule->_priv->absolute;

	/*
	new_rule->_priv->filename = g_strdup (rule->_priv->filename);
	new_rule->_priv->username = g_strdup (rule->_priv->username);
	new_rule->_priv->password = g_strdup (rule->_priv->password);
	new_rule->_priv->userdata1 = g_strdup (rule->_priv->userdata1);
	new_rule->_priv->userdata2 = g_strdup (rule->_priv->userdata2);
	new_rule->_priv->userdata3 = g_strdup (rule->_priv->userdata3);
	new_rule->_priv->userdata4 = g_strdup (rule->_priv->userdata4);
	new_rule->_priv->userdata5 = g_strdup (rule->_priv->userdata5);
	new_rule->_priv->userdata6 = g_strdup (rule->_priv->userdata6);
	new_rule->_priv->userdata7 = g_strdup (rule->_priv->userdata7);
	new_rule->_priv->userdata8 = g_strdup (rule->_priv->userdata8);
	new_rule->_priv->userdata9 = g_strdup (rule->_priv->userdata9);
*/
	
  /* vars */
  list = rule->_priv->vars;
  while (list)
  {
    SimRuleVar *rule_var = (SimRuleVar *) list->data;

    SimRuleVar  *new_rule_var = g_new0 (SimRuleVar, 1);
    new_rule_var->type = rule_var->type;
    new_rule_var->attr = rule_var->attr;
    new_rule_var->level = rule_var->level;
    new_rule_var->negated = rule_var->negated;

    new_rule->_priv->vars = g_list_append (new_rule->_priv->vars, new_rule_var);
    list = list->next;
  }

  /* Plugin Ids - plugin_id + taxonomy product */
  if(rule->_priv->plugin_ids)
  {
		new_rule->_priv->plugin_ids = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
		g_hash_table_iter_init(&iter, rule->_priv->plugin_ids);
  	while (g_hash_table_iter_next(&iter, &key, &value))
    	g_hash_table_insert(new_rule->_priv->plugin_ids, key, value);
  }


  /* Plugin Sids */
  if(rule->_priv->plugin_sids)
  {
    new_rule->_priv->plugin_sids = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
    g_hash_table_iter_init(&iter, rule->_priv->plugin_sids);
    while (g_hash_table_iter_next(&iter, &key, &value))
    	g_hash_table_insert(new_rule->_priv->plugin_sids, key, value);
  }

  /* src hosts */
  if(rule->_priv->src_hosts)
  {
    new_rule->_priv->src_hosts = g_hash_table_new_full (sim_uuid_hash, sim_uuid_equal, g_object_unref, NULL);
    g_hash_table_iter_init(&iter, rule->_priv->src_hosts);
    while (g_hash_table_iter_next(&iter, &key, &value))
      g_hash_table_insert(new_rule->_priv->src_hosts, g_object_ref (key), value);
  }

  /* dst hosts */
  if(rule->_priv->dst_hosts)
  {
    new_rule->_priv->dst_hosts = g_hash_table_new_full (sim_uuid_hash, sim_uuid_equal, g_object_unref, NULL);
    g_hash_table_iter_init(&iter, rule->_priv->dst_hosts);
    while (g_hash_table_iter_next(&iter, &key, &value))
      g_hash_table_insert(new_rule->_priv->dst_hosts, g_object_ref (key), value);
  }

  /* src nets */
  if (rule->_priv->src_inets)
    new_rule->_priv->src_inets = sim_network_clone (rule->_priv->src_inets);

  /* dst nets */
  if (rule->_priv->dst_inets)
    new_rule->_priv->dst_inets = sim_network_clone (rule->_priv->dst_inets);

  /* src ports */
  if(rule->_priv->src_ports)
  {
    new_rule->_priv->src_ports = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
    g_hash_table_iter_init(&iter, rule->_priv->src_ports);
    while (g_hash_table_iter_next(&iter, &key, &value))
      g_hash_table_insert(new_rule->_priv->src_ports, key, value);
  }

  /* dst ports */
  if(rule->_priv->dst_ports)
  {
    new_rule->_priv->dst_ports = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
    g_hash_table_iter_init(&iter, rule->_priv->dst_ports);
    while (g_hash_table_iter_next(&iter, &key, &value))
      g_hash_table_insert(new_rule->_priv->dst_ports, key, value);
  }

  /* Protocols */
  if(rule->_priv->protocols)
  {
    new_rule->_priv->protocols = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
    g_hash_table_iter_init(&iter, rule->_priv->protocols);
    while (g_hash_table_iter_next(&iter, &key, &value))
      g_hash_table_insert(new_rule->_priv->protocols, key, value);
  }

  /* sensors */
  if(rule->_priv->sensors)
  {
    new_rule->_priv->sensors = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, g_object_unref);
    g_hash_table_iter_init(&iter, rule->_priv->sensors);
    while (g_hash_table_iter_next(&iter, &key, &value))
      g_hash_table_insert(new_rule->_priv->sensors, key, sim_sensor_clone((SimSensor *) value));
  }

  /* entities */
  list = rule->_priv->entities;
  while (list)
  {
    sim_rule_add_entity (new_rule, (SimContext *) list->data);
    list = list->next;
  }

  /* taxonomy - product */
  if(rule->_priv->products)
  {
    new_rule->_priv->products = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
    g_hash_table_iter_init(&iter, rule->_priv->products);
    while (g_hash_table_iter_next(&iter, &key, &value))
      g_hash_table_insert(new_rule->_priv->products, key, value);
  }

  /* taxonomy - category */
	if(rule->_priv->categories)
  {
		new_rule->_priv->categories = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
		g_hash_table_iter_init(&iter, rule->_priv->categories);
		while (g_hash_table_iter_next(&iter, &key, &value))
			g_hash_table_insert(new_rule->_priv->categories, key, value);
  }

  /* taxonomy - subcategory */
	if(rule->_priv->subcategories)
  {
		new_rule->_priv->subcategories = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
		g_hash_table_iter_init(&iter, rule->_priv->subcategories);
		while (g_hash_table_iter_next(&iter, &key, &value))
			g_hash_table_insert(new_rule->_priv->subcategories, key, value);
  }

  /* reputation */
  new_rule->_priv->from_rep = rule->_priv->from_rep;
  new_rule->_priv->to_rep = rule->_priv->to_rep;
  new_rule->_priv->from_rep_min_rel = rule->_priv->from_rep_min_rel;
  new_rule->_priv->to_rep_min_rel = rule->_priv->to_rep_min_rel;
  new_rule->_priv->from_rep_min_pri = rule->_priv->from_rep_min_pri;
  new_rule->_priv->to_rep_min_pri = rule->_priv->to_rep_min_pri;

  /* filename */
  list = rule->_priv->filename;
  while (list)
  {
    gchar *aux = g_strdup ((gchar *) list->data);
    new_rule->_priv->filename = g_list_append (new_rule->_priv->filename, aux);
    list = list->next;
  }
	
  /* username */
  list = rule->_priv->username;
  while (list)
  {
    gchar *aux = g_strdup ((gchar *) list->data);
    new_rule->_priv->username = g_list_append (new_rule->_priv->username, aux);
    list = list->next;
  }
	
  /* password */
  list = rule->_priv->password;
  while (list)
  {
    gchar *aux = g_strdup ((gchar *) list->data);
    new_rule->_priv->password = g_list_append (new_rule->_priv->password, aux);
    list = list->next;
  }

  /* userdata1 */
  list = rule->_priv->userdata1;
  while (list)
  {
    gchar *aux = g_strdup ((gchar *) list->data);
    new_rule->_priv->userdata1 = g_list_append (new_rule->_priv->userdata1, aux);
    list = list->next;
  }

  /* userdata2 */
  list = rule->_priv->userdata2;
  while (list)
  {
    gchar *aux = g_strdup ((gchar *) list->data);
    new_rule->_priv->userdata2 = g_list_append (new_rule->_priv->userdata2, aux);
    list = list->next;
  }
  
  /* userdata3 */
  list = rule->_priv->userdata3;
  while (list)
  {
    gchar *aux = g_strdup ((gchar *) list->data);
    new_rule->_priv->userdata3 = g_list_append (new_rule->_priv->userdata3, aux);
    list = list->next;
  }

  /* userdata4 */
  list = rule->_priv->userdata4;
  while (list)
  {
    gchar *aux = g_strdup ((gchar *) list->data);
    new_rule->_priv->userdata4 = g_list_append (new_rule->_priv->userdata4, aux);
    list = list->next;
  }
	/* userdata5 */
  list = rule->_priv->userdata5;
  while (list)
  {
    gchar *aux = g_strdup ((gchar *) list->data);
    new_rule->_priv->userdata5 = g_list_append (new_rule->_priv->userdata5, aux);
    list = list->next;
  }

  /* userdata6 */
  list = rule->_priv->userdata6;
  while (list)
  {
    gchar *aux = g_strdup ((gchar *) list->data);
    new_rule->_priv->userdata6 = g_list_append (new_rule->_priv->userdata6, aux);
    list = list->next;
  }

  /* userdata7 */
  list = rule->_priv->userdata7;
  while (list)
  {
    gchar *aux = g_strdup ((gchar *) list->data);
    new_rule->_priv->userdata7 = g_list_append (new_rule->_priv->userdata7, aux);
    list = list->next;
  }

  /* userdata8 */
  list = rule->_priv->userdata8;
  while (list)
  {
    gchar *aux = g_strdup ((gchar *) list->data);
    new_rule->_priv->userdata8 = g_list_append (new_rule->_priv->userdata8, aux);
    list = list->next;
  }

  /* userdata9 */
  list = rule->_priv->userdata9;
  while (list)
  {
    gchar *aux = g_strdup ((gchar *) list->data);
    new_rule->_priv->userdata9 = g_list_append (new_rule->_priv->userdata9, aux);
    list = list->next;
  }


  //"Not" elements:

  /* src hosts not */
  if(rule->_priv->src_hosts_not)
  {
    new_rule->_priv->src_hosts_not = g_hash_table_new_full (sim_uuid_hash, sim_uuid_equal, g_object_unref, NULL);
    g_hash_table_iter_init(&iter, rule->_priv->src_hosts_not);
    while (g_hash_table_iter_next(&iter, &key, &value))
      g_hash_table_insert(new_rule->_priv->src_hosts_not, g_object_ref (key), value);
  }

  /* dst hosts not */
  if(rule->_priv->dst_hosts_not)
  {
    new_rule->_priv->dst_hosts_not = g_hash_table_new_full (sim_uuid_hash, sim_uuid_equal, g_object_unref, NULL);
    g_hash_table_iter_init(&iter, rule->_priv->dst_hosts_not);
    while (g_hash_table_iter_next(&iter, &key, &value))
      g_hash_table_insert(new_rule->_priv->dst_hosts_not, g_object_ref (key), value);
  }

  // src nets not
  if (rule->_priv->src_inets_not)
    new_rule->_priv->src_inets_not = sim_network_clone (rule->_priv->src_inets_not);
  else
    new_rule->_priv->src_inets_not = NULL;

  // dst nets not
  if(rule->_priv->dst_inets_not)
    new_rule->_priv->dst_inets_not = sim_network_clone (rule->_priv->dst_inets_not);
  else
    new_rule->_priv->dst_inets_not = NULL;

  // src ports not 
  if(rule->_priv->src_ports_not)
  {
    new_rule->_priv->src_ports_not = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
    g_hash_table_iter_init(&iter, rule->_priv->src_ports_not);
    while (g_hash_table_iter_next(&iter, &key, &value))
      g_hash_table_insert(new_rule->_priv->src_ports_not, key, value);
  }
 
  // dst ports not 
  if(rule->_priv->dst_ports_not)
  {
    new_rule->_priv->dst_ports_not = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
    g_hash_table_iter_init(&iter, rule->_priv->dst_ports_not);
    while (g_hash_table_iter_next(&iter, &key, &value))
      g_hash_table_insert(new_rule->_priv->dst_ports_not, key, value);
  }

  /* plugin_ids not - plugin_ids + taxonomy product */
  if(rule->_priv->plugin_ids_not)
  {
		new_rule->_priv->plugin_ids_not = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
		g_hash_table_iter_init(&iter, rule->_priv->plugin_ids_not);
  	while (g_hash_table_iter_next(&iter, &key, &value))
    	g_hash_table_insert(new_rule->_priv->plugin_ids_not, key, value);
  }

  // plugin_sids not
  if(rule->_priv->plugin_sids_not)
  {
    new_rule->_priv->plugin_sids_not = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
    g_hash_table_iter_init(&iter, rule->_priv->plugin_sids_not);
    while (g_hash_table_iter_next(&iter, &key, &value))
      g_hash_table_insert(new_rule->_priv->plugin_sids_not, key, value);
  }

  // protocols not
  if(rule->_priv->protocols_not)
  {
    new_rule->_priv->protocols_not = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
    g_hash_table_iter_init(&iter, rule->_priv->protocols_not);
    while (g_hash_table_iter_next(&iter, &key, &value))
      g_hash_table_insert(new_rule->_priv->protocols_not, key, value);
  }

  // sensors not
  if(rule->_priv->sensors_not)
  {
    new_rule->_priv->sensors_not = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, g_object_unref);
    g_hash_table_iter_init(&iter, rule->_priv->sensors_not);
    while (g_hash_table_iter_next(&iter, &key, &value))
      g_hash_table_insert(new_rule->_priv->sensors_not, key, sim_sensor_clone((SimSensor *) value));
  }

  /* entities not */
  list = rule->_priv->entities_not;
  while (list)
  {
    sim_rule_add_entity_not (new_rule, (SimContext *) list->data);
    list = list->next;
  }

  /* taxonomy - !product */
  if(rule->_priv->product_not)
  {
    new_rule->_priv->product_not = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
    g_hash_table_iter_init(&iter, rule->_priv->product_not);
    while (g_hash_table_iter_next(&iter, &key, &value))
      g_hash_table_insert(new_rule->_priv->product_not, key, value);
  }

  /* taxonomy - !category */
	if(rule->_priv->category_not)
  {
		new_rule->_priv->category_not = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
		g_hash_table_iter_init(&iter, rule->_priv->category_not);
		while (g_hash_table_iter_next(&iter, &key, &value))
			g_hash_table_insert(new_rule->_priv->category_not, key, value);
  }

  /* taxonomy - !subcategory */
	if(rule->_priv->subcategory_not)
  {
		new_rule->_priv->subcategory_not = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
		g_hash_table_iter_init(&iter, rule->_priv->subcategory_not);
		while (g_hash_table_iter_next(&iter, &key, &value))
			g_hash_table_insert(new_rule->_priv->subcategory_not, key, value);
  }

  /* taxonomy - suppress */
	if(rule->_priv->suppress)
  {
		new_rule->_priv->suppress = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
		g_hash_table_iter_init(&iter, rule->_priv->suppress);
		while (g_hash_table_iter_next(&iter, &key, &value))
			g_hash_table_insert(new_rule->_priv->suppress, key, value);
  }

  /* filename not */
  list = rule->_priv->filename_not;
  while (list)
  {
    gchar *aux = g_strdup ((gchar *) list->data);
    new_rule->_priv->filename_not = g_list_append (new_rule->_priv->filename_not, aux);
    list = list->next;
  }
	
  /* username not */
  list = rule->_priv->username_not;
  while (list)
  {
    gchar *aux = g_strdup ((gchar *) list->data);
    new_rule->_priv->username_not = g_list_append (new_rule->_priv->username_not, aux);
    list = list->next;
  }
	
  /* password not */
  list = rule->_priv->password_not;
  while (list)
  {
    gchar *aux = g_strdup ((gchar *) list->data);
    new_rule->_priv->password_not = g_list_append (new_rule->_priv->password_not, aux);
    list = list->next;
  }

  /* userdata1 not */
  list = rule->_priv->userdata1_not;
  while (list)
  {
    gchar *aux = g_strdup ((gchar *) list->data);
    new_rule->_priv->userdata1_not = g_list_append (new_rule->_priv->userdata1_not, aux);
    list = list->next;
  }

  /* userdata2 not */
  list = rule->_priv->userdata2_not;
  while (list)
  {
    gchar *aux = g_strdup ((gchar *) list->data);
    new_rule->_priv->userdata2_not = g_list_append (new_rule->_priv->userdata2_not, aux);
    list = list->next;
  }

  /* userdata3 not */
  list = rule->_priv->userdata3_not;
  while (list)
  {
    gchar *aux = g_strdup ((gchar *) list->data);
    new_rule->_priv->userdata3_not = g_list_append (new_rule->_priv->userdata3_not, aux);
    list = list->next;
  }

  /* userdata4 not */
  list = rule->_priv->userdata4_not;
  while (list)
  {
    gchar *aux = g_strdup ((gchar *) list->data);
    new_rule->_priv->userdata4_not = g_list_append (new_rule->_priv->userdata4_not, aux);
    list = list->next;
  }

  /* userdata5 not */
  list = rule->_priv->userdata5_not;
  while (list)
  {
    gchar *aux = g_strdup ((gchar *) list->data);
    new_rule->_priv->userdata5_not = g_list_append (new_rule->_priv->userdata5_not, aux);
    list = list->next;
  }

  /* userdata6 not */
  list = rule->_priv->userdata6_not;
  while (list)
  {
    gchar *aux = g_strdup ((gchar *) list->data);
    new_rule->_priv->userdata6_not = g_list_append (new_rule->_priv->userdata6_not, aux);
    list = list->next;
  }

  /* userdata7 not */
  list = rule->_priv->userdata7_not;
  while (list)
  {
    gchar *aux = g_strdup ((gchar *) list->data);
    new_rule->_priv->userdata7_not = g_list_append (new_rule->_priv->userdata7_not, aux);
    list = list->next;
  }

  /* userdata8 not */
  list = rule->_priv->userdata8_not;
  while (list)
  {
    gchar *aux = g_strdup ((gchar *) list->data);
    new_rule->_priv->userdata8_not = g_list_append (new_rule->_priv->userdata8_not, aux);
    list = list->next;
  }

  /* userdata9 not */
  list = rule->_priv->userdata9_not;
  while (list)
  {
    gchar *aux = g_strdup ((gchar *) list->data);
    new_rule->_priv->userdata9_not = g_list_append (new_rule->_priv->userdata9_not, aux);
    list = list->next;
  }

  // HOME_NET related fields
  new_rule->_priv->src_is_home_net =  rule->_priv->src_is_home_net;
  new_rule->_priv->dst_is_home_net =  rule->_priv->dst_is_home_net;
  new_rule->_priv->src_is_home_net_not =  rule->_priv->src_is_home_net_not;
  new_rule->_priv->dst_is_home_net_not =  rule->_priv->dst_is_home_net_not;

  return new_rule;
}

/*
 * If the reliability is relative, the reliability of that node will be the sum of
 * the reliabilities from parent rules.
 *
 * We know the the reliability is relative because a "+" appears before the number.
 */
gint
sim_rule_get_reliability_relative (GNode   *rule_node)
{
  GNode   *node;
  gint     rel = 0;

  g_return_val_if_fail (rule_node, 0);

  node = rule_node;
  while (node)
  {
    SimRule *rule = (SimRule *) node->data;

    rel += rule->_priv->reliability;
    node = node->parent;
  }
	if (rel > 10)
		rel = 10;
	if (rel < 0)
		rel = 0;
  return rel;
}

/*
 * This is my favourite function, Thanks fabio!
 * returns TRUE if the "not_invalid" inside the rule is not active;
 * :) Traduction: if the rule has a "not" in it, not invalid will be put to not in the 
 */
gboolean
sim_rule_is_not_invalid (SimRule      *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), FALSE);

  return rule->_priv->not_invalid;
}

/**
 * sim_rule_is_time_out:
 * @rule: a #SimRule.
 *
 * Look if a #SimRule is time out.
 *
 * Return: TRUE if is time out, FALSE otherwise.
 */
gboolean
sim_rule_is_time_out (SimRule      *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), FALSE);

  if ((!rule->_priv->time_out) || (!rule->_priv->time_last))
    return FALSE;

  if (time (NULL) > (rule->_priv->time_last + rule->_priv->time_out))
      return TRUE;

  return FALSE;
}

/**
 * sim_rule_is_taxonomy_rule:
 * @rule: a #SimRule.
 *
 * Look if a #SimRule matchs against taxonomy.
 *
 * Return: TRUE if the rule matchs against taxonomy, FALSE otherwise.
 */
gboolean
sim_rule_is_taxonomy_rule (SimRule *rule)
{
  g_return_val_if_fail (SIM_IS_RULE (rule), FALSE);

  return g_hash_table_size (rule->_priv->plugin_ids) > 1;
}

/**
 * _sim_rule_is_time_out:
 * @rule: a #SimRule.
 *
 * Look if a #SimRule is time out.
 *
 * Return: TRUE if is time out, FALSE otherwise.
 */
static gboolean
_sim_rule_is_time_out (SimRule *rule)
{
  /* The first level can not be in 'timeout' */
  if (rule->_priv->level == 1)
    return FALSE;

  if ((!rule->_priv->time_out) || (!rule->_priv->time_last))
    return FALSE;

  if (time (NULL) > (rule->_priv->time_last + rule->_priv->time_out))
    return TRUE;

  return FALSE;
}

/*
 *	NOTE: this is used only for plugin_sid sticky_different
 * check if "val" variable is inside the GList. GList is a gint list.
 *
 */
gboolean
find_gint_value (GList      *values,
						    gint     val)
{
  GList *list;

  if (!values)
    return FALSE;

  list = values;
  while (list)
  {
    gint cmp = GPOINTER_TO_INT (list->data);

		if (cmp == val)
			return TRUE;

    list = list->next;
  }

  return FALSE;
}

gboolean _check_is_reserved(SimRadixNode *node, void * ud)
{  
  // unused parameter
  (void) ud;

  if(node && node->user_data)
  {
    SimInet *inet = (SimInet*) node->user_data;
    return sim_inet_is_none (inet);
  }
  else
    return FALSE;
}

gboolean
sim_rule_match_by_event (SimRule      *rule,
                         SimEvent     *event)
{

  g_return_val_if_fail (SIM_IS_RULE (rule), FALSE);
  g_return_val_if_fail (rule->type != SIM_EVENT_TYPE_NONE, FALSE);
  g_return_val_if_fail (rule->_priv->plugin_id >= 0, FALSE);
  g_return_val_if_fail (SIM_IS_EVENT (event), FALSE);
  g_return_val_if_fail (event->type != SIM_EVENT_TYPE_NONE, FALSE);
  g_return_val_if_fail (event->plugin_id > 0, FALSE);
  g_return_val_if_fail (event->plugin_sid > 0, FALSE);
  g_return_val_if_fail (SIM_IS_INET (event->src_ia), FALSE);

  /* DESIGN EXPLANATION */
  /* Taxonomy products are converted to their plugin_id list equivalent so we
   * can latter combine easily the plugin_id list specified in the plugin_id
   * xml tag and the plugin_id list that comes from the taxonomy product list.
   * Because of that we only match here against a plugin_id list.
   *
   * WARNING: To implement 1:PRODUCT we do the lookup for every event at
   * sim_session_cmd_event, this can be (or cannot be) a performance
   * problem because later that list will be copied with every event copy
   * (the list size depends on how many plugin_id are associated to the
   * event product).
   */

  /* Match Entity */
  if (rule->_priv->entities)
  {
    GList *list = rule->_priv->entities;
    while (list)
    {
      if (event->context == (SimContext *) list->data)
        break; // Match

      if (!(list = g_list_next (list)))
        return FALSE; // All checked, No Match
    }
  }

  /* Match Plugin SIDs */
  if (rule->_priv->plugin_sids)
  {
		if (!g_hash_table_lookup(rule->_priv->plugin_sids, GINT_TO_POINTER(event->plugin_sid)))
      return FALSE;
	}

  /* Time Out */
  if (_sim_rule_is_time_out (rule))
    return FALSE;

  /* Match Type */
  if (rule->type != event->type)
    return FALSE;

  /* Match Plugin ID */
  if (rule->_priv->plugin_ids)
  {
    if (!g_hash_table_lookup(rule->_priv->plugin_ids, GINT_TO_POINTER(event->plugin_id)))
      return FALSE;
  }

  /* ^^^^ VERY IMPORTANT MESSAGE !!! ^^^^
   * This message seems like any other but it can lead to very serious problems.
   * If someone writes an xml directive in which a directive event match a new
   * directive event will be created, this new directive event will match again
   * generating another directive event, etc.
   * This will provoke that the correlation will loop forever filling the
   * database with events.
   */

	// src_ip
  if (!sim_rule_match_src_ip (rule, event))
    return FALSE;

	// dst_ip
  if (!sim_rule_match_dst_ip (rule, event))
    return FALSE;

  // taxonomy - product
  if (rule->_priv->products)
    if (!g_hash_table_lookup(rule->_priv->products, GINT_TO_POINTER(event->tax_product)))
      return FALSE;

  // taxonomy - category
  if (rule->_priv->categories)
    if (!g_hash_table_lookup(rule->_priv->categories, GINT_TO_POINTER(event->tax_category)))
      return FALSE;

  // taxonomy - subcategory
  if (rule->_priv->subcategories)
    if (!g_hash_table_lookup(rule->_priv->subcategories, GINT_TO_POINTER(event->tax_subcategory)))
      return FALSE;

	// Match !src ports
	if (rule->_priv->src_ports_not) 
	{ 
		if(g_hash_table_lookup(rule->_priv->src_ports_not, GINT_TO_POINTER(event->src_port))) //if the ports match, as this is negated, the rule doesn't match 
				return FALSE; 
	} 

 	// Match !dst ports
	if (rule->_priv->dst_ports_not) 
	{ 
		if(g_hash_table_lookup(rule->_priv->dst_ports_not, GINT_TO_POINTER(event->dst_port))) 
				return FALSE; 
	} 

 	// Match !plugin_ids
	if (rule->_priv->plugin_ids_not) 
  { 
		if(g_hash_table_lookup(rule->_priv->plugin_ids_not, GINT_TO_POINTER(event->plugin_id))) 
				return FALSE; 
	} 

 	// Match !plugin_sids
	if (rule->_priv->plugin_sids_not) 
	{ 
		if(g_hash_table_lookup(rule->_priv->plugin_sids_not, GINT_TO_POINTER(event->plugin_sid))) 
				return FALSE; 
	} 
 
 	// Match !protocols
	if (rule->_priv->protocols_not) 
	{ 
		if(g_hash_table_lookup(rule->_priv->protocols_not, GINT_TO_POINTER(event->protocol)))
				return FALSE; 
	} 

  // Match !sensor
  if (rule->_priv->sensors_not)
    if (g_hash_table_lookup (rule->_priv->sensors_not, event->sensor))
      return FALSE;

  // taxonomy - !product
  if (rule->_priv->product_not)
    if (g_hash_table_lookup(rule->_priv->product_not, GINT_TO_POINTER(event->tax_product)))
      return FALSE;

  // taxonomy - !category
  if (rule->_priv->category_not)
    if (g_hash_table_lookup(rule->_priv->category_not, GINT_TO_POINTER(event->tax_category)))
      return FALSE;

  // taxonomy - !subcategory
  if (rule->_priv->subcategory_not)
    if (g_hash_table_lookup(rule->_priv->subcategory_not, GINT_TO_POINTER(event->tax_subcategory)))
      return FALSE;

  // taxonomy - suppress
  if (rule->_priv->suppress)
    if (g_hash_table_lookup(rule->_priv->suppress, GINT_TO_POINTER(CANTOR_KEY(event->plugin_id, event->plugin_sid))))
      return FALSE;

  // reputation
  if (rule->_priv->from_rep)
    if (!event->rep_act_src)
      return FALSE;

  if (rule->_priv->to_rep)
    if (!event->rep_act_dst)
      return FALSE;

  if (rule->_priv->from_rep_min_rel)
    if (event->rep_rel_src < rule->_priv->from_rep_min_rel)
      return FALSE;

  if (rule->_priv->to_rep_min_rel)
    if (event->rep_rel_dst < rule->_priv->to_rep_min_rel)
      return FALSE;

  if (rule->_priv->from_rep_min_pri)
    if (event->rep_prio_src < rule->_priv->from_rep_min_pri)
      return FALSE;

  if (rule->_priv->to_rep_min_pri)
    if (event->rep_prio_dst < rule->_priv->to_rep_min_pri)
      return FALSE;

 	/* Match other things like !filename, !username, 1userdata1...*/
	if (rule->_priv->filename_not)
	{
		if (sim_cmp_list_gchar (rule->_priv->filename_not, event->filename))
			return FALSE;
	}
	if (rule->_priv->username_not)
	{
		if (sim_cmp_list_gchar (rule->_priv->username_not, event->username))
			return FALSE;
	}
	if (rule->_priv->password_not)
	{
		if (sim_cmp_list_gchar (rule->_priv->password_not, event->password))
			return FALSE;
	}
	if (rule->_priv->userdata1_not)
	{
		if (sim_cmp_list_gchar (rule->_priv->userdata1_not, event->userdata1))
			return FALSE;
	}
	if (rule->_priv->userdata2_not)
	{
		if (sim_cmp_list_gchar (rule->_priv->userdata2_not, event->userdata2))
			return FALSE;
	}
	if (rule->_priv->userdata3_not)
	{
		if (sim_cmp_list_gchar (rule->_priv->userdata3_not, event->userdata3))
			return FALSE;
	}
	if (rule->_priv->userdata4_not)
	{
		if (sim_cmp_list_gchar (rule->_priv->userdata4_not, event->userdata4))
			return FALSE;
	}
	if (rule->_priv->userdata5_not)
	{
		if (sim_cmp_list_gchar (rule->_priv->userdata5_not, event->userdata5))
			return FALSE;
	}
	if (rule->_priv->userdata6_not)
	{
		if (sim_cmp_list_gchar (rule->_priv->userdata6_not, event->userdata6))
			return FALSE;
	}
	if (rule->_priv->userdata7_not)
	{
		if (sim_cmp_list_gchar (rule->_priv->userdata7_not, event->userdata7))
			return FALSE;
	}
	if (rule->_priv->userdata8_not)
	{
		if (sim_cmp_list_gchar (rule->_priv->userdata8_not, event->userdata8))
			return FALSE;
	}
	if (rule->_priv->userdata9_not)
	{
		if (sim_cmp_list_gchar (rule->_priv->userdata9_not, event->userdata9))
			return FALSE;
	}

	//match the non-negated elements.

  /* Find src_port */
  if (rule->_priv->src_ports)
  {
    if (!g_hash_table_lookup(rule->_priv->src_ports, GINT_TO_POINTER(0)) &&
			  !g_hash_table_lookup(rule->_priv->src_ports, GINT_TO_POINTER(event->src_port)))
	      return FALSE;
  }

  /* Find dst_port */
  if (rule->_priv->dst_ports)
  {
    if (!g_hash_table_lookup(rule->_priv->dst_ports, GINT_TO_POINTER(0)) &&
        !g_hash_table_lookup(rule->_priv->dst_ports, GINT_TO_POINTER(event->dst_port)))
      return FALSE;
  }

  /* Protocols */
  if (rule->_priv->protocols)
  {
		if (!g_hash_table_lookup(rule->_priv->protocols, GINT_TO_POINTER(0)) && 
			  !g_hash_table_lookup(rule->_priv->protocols, GINT_TO_POINTER(event->protocol)))
	    return FALSE;
  }

  /* Match sensor */
  if (rule->_priv->sensors)
  {
    if (!g_hash_table_lookup(rule->_priv->sensors, event->sensor))
      return FALSE;
  }

	/* Match other things like filename, username, userdata1...*/
	if (rule->_priv->filename)
	{
		if (!sim_cmp_list_gchar (rule->_priv->filename, event->filename))
			return FALSE;
	}
	if (rule->_priv->username)
	{
		if (!sim_cmp_list_gchar (rule->_priv->username, event->username))
			return FALSE;
	}
	if (rule->_priv->password)
	{
		if (!sim_cmp_list_gchar (rule->_priv->password, event->password))
			return FALSE;
	}
	if (rule->_priv->userdata1)
	{
		if (!sim_cmp_list_gchar (rule->_priv->userdata1, event->userdata1))
			return FALSE;
	}
	if (rule->_priv->userdata2)
	{
		if (!sim_cmp_list_gchar (rule->_priv->userdata2, event->userdata2))
			return FALSE;
	}
	if (rule->_priv->userdata3)
	{
		if (!sim_cmp_list_gchar (rule->_priv->userdata3, event->userdata3))
			return FALSE;
	}
	if (rule->_priv->userdata4)
	{
		if (!sim_cmp_list_gchar (rule->_priv->userdata4, event->userdata4))
			return FALSE;
	}
	if (rule->_priv->userdata5)
	{
		if (!sim_cmp_list_gchar (rule->_priv->userdata5, event->userdata5))
			return FALSE;
	}
	if (rule->_priv->userdata6)
	{
		if (!sim_cmp_list_gchar (rule->_priv->userdata6, event->userdata6))
			return FALSE;
	}
	if (rule->_priv->userdata7)
	{
		if (!sim_cmp_list_gchar (rule->_priv->userdata7, event->userdata7))
			return FALSE;
	}
	if (rule->_priv->userdata8)
	{
		if (!sim_cmp_list_gchar (rule->_priv->userdata8, event->userdata8))
			return FALSE;
	}
	if (rule->_priv->userdata9)
	{
		if (!sim_cmp_list_gchar (rule->_priv->userdata9, event->userdata9))
			return FALSE;
	}

  /* Match Condition (Only monitor events)*/
  if ((rule->_priv->condition != SIM_CONDITION_TYPE_NONE) &&
      (event->condition != SIM_CONDITION_TYPE_NONE))
  {
    if (rule->_priv->condition != event->condition)
			return FALSE;

    /* Match Value */
    if ((rule->_priv->value) && (event->value))
		{
			//The event->value must be the same than rule->_priv->value to match. When we ask to
			//an agent a watch_rule, is the agent who compares and test if it's the real value.
			//Then, the agent will return to us an event with the same value that we send to him
			//so we know then that our question has matched
		  if (g_ascii_strcasecmp (rule->_priv->value, event->value))
	  	  return FALSE;
		}
  }

  // From this point the rule has matched, sticky_different is an special attribute
  event->rule_matched = TRUE;

  if ((rule->_priv->occurrence > 1) && (rule->_priv->sticky_different))
  {
    //sticky_different can be assigned only to a single variable
    switch (rule->_priv->sticky_different)
    {
    case SIM_RULE_VAR_PLUGIN_ID:
      if (g_hash_table_lookup (rule->_priv->sticky_int, GINT_TO_POINTER(event->plugin_id)))
        return FALSE;
      g_hash_table_insert (rule->_priv->sticky_int, GINT_TO_POINTER(event->plugin_id), GINT_TO_POINTER (GENERIC_VALUE));
      break;
    case SIM_RULE_VAR_PLUGIN_SID:
      //if we find the plugin_sid from the event inside the stickys list, it returns false because it means that it belongs to another directive
      if (g_hash_table_lookup (rule->_priv->sticky_int, GINT_TO_POINTER(event->plugin_sid)))
        return FALSE;
      g_hash_table_insert (rule->_priv->sticky_int, GINT_TO_POINTER(event->plugin_sid), GINT_TO_POINTER (GENERIC_VALUE));
      break;

    case SIM_RULE_VAR_SRC_IA:
      if (sim_network_has_exact_inet (rule->_priv->sticky_ip, event->src_ia))
        return FALSE;
      sim_network_add_inet (rule->_priv->sticky_ip, event->src_ia);
      break;

    case SIM_RULE_VAR_DST_IA:
      if (sim_network_has_exact_inet (rule->_priv->sticky_ip, event->dst_ia))
        return FALSE;
      sim_network_add_inet (rule->_priv->sticky_ip, event->dst_ia);
      break;

    case SIM_RULE_VAR_SRC_PORT:
      if (g_hash_table_lookup (rule->_priv->sticky_int, GINT_TO_POINTER(event->src_port)))
        return FALSE;
      g_hash_table_insert (rule->_priv->sticky_int, GINT_TO_POINTER(event->src_port), GINT_TO_POINTER (GENERIC_VALUE));
      break;

    case SIM_RULE_VAR_DST_PORT:
      if (g_hash_table_lookup (rule->_priv->sticky_int, GINT_TO_POINTER(event->dst_port)))
        return FALSE;
      g_hash_table_insert (rule->_priv->sticky_int, GINT_TO_POINTER(event->dst_port), GINT_TO_POINTER (GENERIC_VALUE));
      break;

    case SIM_RULE_VAR_PROTOCOL:
      if (g_hash_table_lookup (rule->_priv->sticky_int, GINT_TO_POINTER(event->protocol)))
        return FALSE;
      g_hash_table_insert (rule->_priv->sticky_int, GINT_TO_POINTER(event->protocol), GINT_TO_POINTER (GENERIC_VALUE));
      break;

    case SIM_RULE_VAR_SENSOR:
      if (sim_network_has_exact_inet (rule->_priv->sticky_ip, event->sensor))
        return FALSE;
      sim_network_add_inet (rule->_priv->sticky_ip, event->sensor);
      break;


    case SIM_RULE_VAR_USERNAME:
      if (event->username)
      {
        if (g_hash_table_lookup (rule->_priv->sticky_str, event->username))
          return FALSE;
        g_hash_table_insert (rule->_priv->sticky_str, g_strdup(event->username), GINT_TO_POINTER (GENERIC_VALUE));
      }
      break;

    case SIM_RULE_VAR_USERDATA1:
      if (event->userdata1)
      {
        if (g_hash_table_lookup (rule->_priv->sticky_str, event->userdata1))
          return FALSE;
        g_hash_table_insert (rule->_priv->sticky_str, g_strdup(event->userdata1), GINT_TO_POINTER (GENERIC_VALUE));
      }
      break;
    case SIM_RULE_VAR_USERDATA2:
      if (event->userdata2)
      {
        if (g_hash_table_lookup (rule->_priv->sticky_str, event->userdata2))
          return FALSE;
        g_hash_table_insert (rule->_priv->sticky_str, g_strdup(event->userdata2), GINT_TO_POINTER (GENERIC_VALUE));
      }
      break;
    case SIM_RULE_VAR_USERDATA3:
      if (event->userdata3)
      {
        if (g_hash_table_lookup (rule->_priv->sticky_str, event->userdata3))
          return FALSE;
        g_hash_table_insert (rule->_priv->sticky_str, g_strdup(event->userdata3), GINT_TO_POINTER (GENERIC_VALUE));
      }
      break;
    case SIM_RULE_VAR_USERDATA4:
      if (event->userdata4)
      {
        if (g_hash_table_lookup (rule->_priv->sticky_str, event->userdata4))
          return FALSE;
        g_hash_table_insert (rule->_priv->sticky_str, g_strdup(event->userdata4), GINT_TO_POINTER (GENERIC_VALUE));
      }
      break;
    case SIM_RULE_VAR_USERDATA5:
      if (event->userdata5)
      {
        if (g_hash_table_lookup (rule->_priv->sticky_str, event->userdata5))
          return FALSE;
        g_hash_table_insert (rule->_priv->sticky_str, g_strdup(event->userdata5), GINT_TO_POINTER (GENERIC_VALUE));
      }
      break;
    case SIM_RULE_VAR_USERDATA6:
      if (event->userdata6)
      {
        if (g_hash_table_lookup (rule->_priv->sticky_str, event->userdata6))
          return FALSE;
        g_hash_table_insert (rule->_priv->sticky_str, g_strdup(event->userdata6), GINT_TO_POINTER (GENERIC_VALUE));
      }
      break;
    case SIM_RULE_VAR_USERDATA7:
      if (event->userdata7)
      {
        if (g_hash_table_lookup (rule->_priv->sticky_str, event->userdata7))
          return FALSE;
        g_hash_table_insert (rule->_priv->sticky_str, g_strdup(event->userdata7), GINT_TO_POINTER (GENERIC_VALUE));
      }
      break;
    case SIM_RULE_VAR_USERDATA8:
      if (event->userdata8)
      {
        if (g_hash_table_lookup (rule->_priv->sticky_str, event->userdata8))
          return FALSE;
        g_hash_table_insert (rule->_priv->sticky_str, g_strdup(event->userdata8), GINT_TO_POINTER (GENERIC_VALUE));
      }
      break;
    case SIM_RULE_VAR_USERDATA9:
      if (event->userdata9)
      {
        if (g_hash_table_lookup (rule->_priv->sticky_str, event->userdata9))
          return FALSE;
        g_hash_table_insert (rule->_priv->sticky_str, g_strdup(event->userdata9), GINT_TO_POINTER (GENERIC_VALUE));
      }
      break;
    default:
      break;
    }
  }

  /* Update max assets */
  if (event->asset_src > rule->_priv->src_max_asset)
    rule->_priv->src_max_asset = event->asset_src;
  if (event->asset_dst > rule->_priv->dst_max_asset)
    rule->_priv->dst_max_asset = event->asset_dst;

  /* Match Occurrence */
  if (rule->_priv->occurrence > 1)
  {
    if ((rule->_priv->time_out) && (!rule->_priv->time_last))
      rule->_priv->time_last = time (NULL);


    if (rule->_priv->occurrence != rule->_priv->count_occu)
    {
      rule->_priv->count_occu++;
      event->count = rule->_priv->count_occu - 1;
      return FALSE;
    }
    else
    {
      event->count = rule->_priv->occurrence;
      rule->_priv->count_occu = 1;
    }
  }
  else
  {
    event->count = 1;
  }

  /* Not */
	//If the rule is enterely negated, and after all the checks it matches, we have to return false.
  if (rule->_priv->not)
    {
      rule->_priv->not_invalid = TRUE; //I have to check this statment
      return FALSE;
    }

  if (!rule->_priv->plugin_ids && !rule->_priv->plugin_ids_not && event->plugin_id == 1505)
    g_message ("An event with plugin_id 1505 has matched in a plugin_id ANY rule");

  return TRUE;
}

/*
 *
 * This is needed to set the data from the actual event to the rule.
 * If there is in the directive an element with (ie.) a src_ip = "ANY", we need to know what src_ip has  matched with the "ANY" keyword
 * so rules with 1:SRC_IP knows the value.
 *
 */
void
sim_rule_set_event_data (SimRule      *rule,
												 SimEvent     *event)
{
  g_return_if_fail (SIM_IS_RULE (rule));
  g_return_if_fail (SIM_IS_EVENT (event));

  gchar *ip_src = sim_inet_get_canonical_name (event->src_ia);
  gchar *ip_dst = sim_inet_get_canonical_name (event->dst_ia);
  gchar *ip_sensor = sim_inet_get_canonical_name (event->sensor);
  ossim_debug ("%s: src_ia: %s", __func__, ip_src);
  ossim_debug ("%s: dst_ia: %s", __func__, ip_dst);
  ossim_debug ("%s: sensor: %s", __func__, ip_sensor);
	 
  if (ip_src && ip_dst)
  {
    rule->_priv->src_ia = (event->src_ia) ? g_object_ref (event->src_ia) : NULL;
    rule->_priv->dst_ia = (event->dst_ia) ? g_object_ref (event->dst_ia) : NULL;
    if (event->src_id)
      sim_rule_set_src_host_id(rule, event->src_id);
    if (event->dst_id)
      sim_rule_set_dst_host_id(rule, event->dst_id);

    rule->_priv->src_port = event->src_port;
    rule->_priv->dst_port = event->dst_port;
    rule->_priv->protocol = event->protocol;
    rule->_priv->plugin_id = event->plugin_id;
    rule->_priv->plugin_sid = event->plugin_sid;
    rule->_priv->sensor = (event->sensor) ? g_object_ref (event->sensor) : NULL;
    rule->_priv->tax_product = event->tax_product;
    rule->_priv->entity = g_object_ref (event->context);
    rule->_priv->tax_product = event->tax_product;
    rule->_priv->tax_category = event->tax_category;
    rule->_priv->tax_subcategory = event->tax_subcategory;
    rule->_priv->ev_filename = (event->filename) ? g_strdup (event->filename) : g_strdup (SIM_WILDCARD_EMPTY);
    rule->_priv->ev_username = (event->username) ? g_strdup (event->username) : g_strdup (SIM_WILDCARD_EMPTY);
    rule->_priv->ev_password = (event->password) ? g_strdup (event->password) : g_strdup (SIM_WILDCARD_EMPTY);
    rule->_priv->ev_userdata1 = (event->userdata1) ? g_strdup (event->userdata1) : g_strdup (SIM_WILDCARD_EMPTY);
    rule->_priv->ev_userdata2 = (event->userdata2) ? g_strdup (event->userdata2) : g_strdup (SIM_WILDCARD_EMPTY);
    rule->_priv->ev_userdata3 = (event->userdata3) ? g_strdup (event->userdata3) : g_strdup (SIM_WILDCARD_EMPTY);
    rule->_priv->ev_userdata4 = (event->userdata4) ? g_strdup (event->userdata4) : g_strdup (SIM_WILDCARD_EMPTY);
    rule->_priv->ev_userdata5 = (event->userdata5) ? g_strdup (event->userdata5) : g_strdup (SIM_WILDCARD_EMPTY);
    rule->_priv->ev_userdata6 = (event->userdata6) ? g_strdup (event->userdata6) : g_strdup (SIM_WILDCARD_EMPTY);
    rule->_priv->ev_userdata7 = (event->userdata7) ? g_strdup (event->userdata7) : g_strdup (SIM_WILDCARD_EMPTY);
    rule->_priv->ev_userdata8 = (event->userdata8) ? g_strdup (event->userdata8) : g_strdup (SIM_WILDCARD_EMPTY);
    rule->_priv->ev_userdata9 = (event->userdata9) ? g_strdup (event->userdata9) : g_strdup (SIM_WILDCARD_EMPTY);
  }
  else
    g_message("Error: The src or dst of an event is wrong");

  g_free (ip_src);
  g_free (ip_dst);
  g_free (ip_sensor);
}

/*
 *
 *	
 *
 *
 */
void
sim_rule_set_not_data (SimRule      *rule)
{
  g_return_if_fail (SIM_IS_RULE (rule));

	GHashTableIter iter;
	gpointer key, value;

  if (rule->_priv->plugin_sids)
	{
		g_hash_table_iter_init(&iter, rule->_priv->plugin_sids);
		if(g_hash_table_iter_next (&iter, &key, &value))
	    rule->_priv->plugin_sid = GPOINTER_TO_INT (key);
	}

  // FIXME: There is no function in sim-radix to get one address without searching for it
  // so it ony get the SimInet   if "any" is in the tree
/*  if ((rule->_priv->src_inets) && (sim_network_has_any (rule->_priv->src_inets)))
    rule->_priv->src_ia = sim_inet_new_any ();

  if ((rule->_priv->dst_inets) && (sim_network_has_any (rule->_priv->dst_inets)))
    rule->_priv->dst_ia = sim_inet_new_any ();
*/
  if (rule->_priv->src_ports)
	{
		g_hash_table_iter_init(&iter, rule->_priv->src_ports);
		if(g_hash_table_iter_next (&iter, &key, &value))
			rule->_priv->src_port = GPOINTER_TO_INT (key);
	}

  if (rule->_priv->dst_ports)
	{
		g_hash_table_iter_init(&iter, rule->_priv->dst_ports);
		if(g_hash_table_iter_next (&iter, &key, &value))
			rule->_priv->dst_port = GPOINTER_TO_INT (key);
	}

  if (rule->_priv->sensors)
	{
		g_hash_table_iter_init(&iter, rule->_priv->sensors);
		if(g_hash_table_iter_next (&iter, &key, &value))
			rule->_priv->sensor =  sim_inet_clone (value);//FIXME: wrrrronggggg
	}
}

/*
 *
 * This function is just for debugging, it's not needed to call it from anywhere.
 *
 *
 */
void
sim_rule_print (SimRule      *rule)
{
  GList *list;
  gchar  *ip;
	GHashTableIter iter;
	gpointer key, value;

  g_return_if_fail (SIM_IS_RULE (rule));

  ossim_debug ( "Rule: ");
  ossim_debug ( "sim_rule_impl_finalize: Name %s, Level %d", rule->_priv->name, rule->_priv->level);
  ossim_debug ( "not=%d ", rule->_priv->not);
  ossim_debug ( "name=%s ", rule->_priv->name);
  ossim_debug ( "level=%d ", rule->_priv->level);
  ossim_debug ( "priority=%d ", rule->_priv->priority);
  ossim_debug ( "reliability=%d ", rule->_priv->reliability);
  ossim_debug ( "time_out=%u ", (unsigned int)rule->_priv->time_out);
  ossim_debug ( "occurrence=%d ", rule->_priv->occurrence);
  ossim_debug ( "plugin_id=%d ", rule->_priv->plugin_id);
  ossim_debug ( "plugin_sid=%d ", g_hash_table_size (rule->_priv->plugin_sids));
  g_hash_table_iter_init(&iter, rule->_priv->plugin_sids);
  while (g_hash_table_iter_next(&iter, &key, &value))
    ossim_debug ( " %d ", GPOINTER_TO_INT (key));

  ossim_debug ( "plugin_sids_not=%d ", g_hash_table_size (rule->_priv->plugin_sids_not));

  sim_network_print (rule->_priv->src_inets);
  sim_network_print (rule->_priv->dst_inets);

  ossim_debug ( "src_ports=%d ", g_hash_table_size (rule->_priv->src_ports));
	g_hash_table_iter_init(&iter, rule->_priv->src_ports);
  while (g_hash_table_iter_next(&iter, &key, &value))
    {
    gint port = GPOINTER_TO_INT (key);
      ossim_debug ( " %d ", port);
    }

  ossim_debug ( "dst_ports=%d ", g_hash_table_size (rule->_priv->dst_ports));
	g_hash_table_iter_init(&iter, rule->_priv->dst_ports);
  while (g_hash_table_iter_next(&iter, &key, &value))
    {
    gint port = GPOINTER_TO_INT (key);
      ossim_debug ( " %d ", port);
    }

  ossim_debug ( "sensors=%d ", g_hash_table_size (rule->_priv->sensors));
	g_hash_table_iter_init(&iter, rule->_priv->sensors);
  while (g_hash_table_iter_next(&iter, &key, &value))
    {
  		SimSensor *sensor = (SimSensor *) value;
      ossim_debug ( " %s ", sim_sensor_get_name(sensor));
    }

	ossim_debug ( "protocols=%d ", g_hash_table_size (rule->_priv->protocols));
	g_hash_table_iter_init(&iter, rule->_priv->protocols);
  while (g_hash_table_iter_next(&iter, &key, &value))
    ossim_debug ( " %d ", GPOINTER_TO_INT (key));


	ossim_debug ( "vars=%d ", g_list_length (rule->_priv->vars));
	list = rule->_priv->vars;
	while (list)
	{
		SimRuleVar *var = (SimRuleVar *) list->data;
    ossim_debug ( "    rule name: %s",sim_rule_get_name(rule));
    ossim_debug ( "    type: %d",var->type);
    ossim_debug ( "    attr: %d",var->attr);
    ossim_debug ( "    negated: %d",var->negated);
		list = list->next;
	}

 
  if (rule->_priv->src_ia)
  {
    ip = sim_inet_get_canonical_name (rule->_priv->src_ia);
    ossim_debug ("src_ia=%s ", ip);
    g_free (ip);
  }
  if (rule->_priv->dst_ia)
  {
    ip = sim_inet_get_canonical_name (rule->_priv->dst_ia);
    ossim_debug ("dst_ia=%s ", ip);
    g_free (ip);
  }
  if (rule->_priv->sensor)
  {
    ip = sim_inet_get_canonical_name (rule->_priv->dst_ia);
    ossim_debug ("sensor=%s ", ip);
    g_free (ip);
  }
  ossim_debug ("src_port=%d ", rule->_priv->src_port);
  ossim_debug ("dst_port=%d ", rule->_priv->dst_port);

  sim_network_print (rule->_priv->src_inets_not);
  sim_network_print (rule->_priv->dst_inets_not);

	ossim_debug ( "src_ports_not=%d ", g_hash_table_size (rule->_priv->src_ports_not));
	g_hash_table_iter_init(&iter, rule->_priv->src_ports_not);
  while (g_hash_table_iter_next(&iter, &key, &value))
    ossim_debug ( " %d ", GPOINTER_TO_INT (key));

	ossim_debug ( "dst_ports_not=%d ", g_hash_table_size (rule->_priv->dst_ports_not));
	g_hash_table_iter_init(&iter, rule->_priv->dst_ports_not);
  while (g_hash_table_iter_next(&iter, &key, &value))
    ossim_debug ( " %d ", GPOINTER_TO_INT (key));

	ossim_debug ( "protocols_not=%d ", g_hash_table_size (rule->_priv->protocols_not));
	g_hash_table_iter_init(&iter, rule->_priv->protocols_not);
  while (g_hash_table_iter_next(&iter, &key, &value))
    ossim_debug ( " %d ", GPOINTER_TO_INT (key));

  ossim_debug ( "plugin_sids_not=%d ", g_hash_table_size (rule->_priv->plugin_sids_not));
	g_hash_table_iter_init(&iter, rule->_priv->plugin_sids_not);
  while (g_hash_table_iter_next(&iter, &key, &value))
    ossim_debug ( " %d ", GPOINTER_TO_INT (key));

	ossim_debug ( "sensors_not=%d ", g_hash_table_size (rule->_priv->sensors_not));
	g_hash_table_iter_init(&iter, rule->_priv->sensors_not);
  while (g_hash_table_iter_next(&iter, &key, &value))
  {
    SimSensor *sensor = (SimSensor *) value;
    SimInet *sensor_ia = sim_sensor_get_ia (sensor);
    gchar *ip_sensor = sim_inet_get_canonical_name (sensor_ia);
    ossim_debug (" %s ", ip_sensor);
    g_free (ip_sensor);
  }

	ossim_debug ( "filename=%d ", g_list_length (rule->_priv->filename));
	list = rule->_priv->filename;
  while (list)
  {
    ossim_debug ( " %s ", (gchar *) (list->data));
    list = list->next;
  }

	ossim_debug ( "username=%d ", g_list_length (rule->_priv->username));
	list = rule->_priv->username;
  while (list)
  {
    ossim_debug ( " %s ", (gchar *) (list->data));
    list = list->next;
  }

	ossim_debug ( "password=%d ", g_list_length (rule->_priv->password));
	list = rule->_priv->password;
  while (list)
  {
		gchar *lala = (gchar *)(list->data);
    ossim_debug ( " -%s- ", lala);
    list = list->next;
  }
	ossim_debug ( "userdata1=%d ", g_list_length (rule->_priv->userdata1));
	list = rule->_priv->userdata1;
  while (list)
  {
    ossim_debug ( " %s ", (gchar *) (list->data));
    list = list->next;
  }
	ossim_debug ( "userdata2=%d ", g_list_length (rule->_priv->userdata2));
	list = rule->_priv->userdata2;
  while (list)
  {
    ossim_debug ( " %s ", (gchar *) (list->data));
    list = list->next;
  }
	ossim_debug ( "userdata3=%d ", g_list_length (rule->_priv->userdata3));
	list = rule->_priv->userdata3;
  while (list)
  {
    ossim_debug ( " %s ", (gchar *) (list->data));
    list = list->next;
  }
	ossim_debug ( "userdata4=%d ", g_list_length (rule->_priv->userdata4));
	list = rule->_priv->userdata4;
  while (list)
  {
    ossim_debug ( " %s ", (gchar *) (list->data));
    list = list->next;
  }
	ossim_debug ( "userdata5=%d ", g_list_length (rule->_priv->userdata5));
	list = rule->_priv->userdata5;
  while (list)
  {
    ossim_debug ( " %s ", (gchar *) (list->data));
    list = list->next;
  }
	ossim_debug ( "userdata6=%d ", g_list_length (rule->_priv->userdata6));
	list = rule->_priv->userdata6;
  while (list)
  {
    ossim_debug ( " %s ", (gchar *) (list->data));
    list = list->next;
  }
	ossim_debug ( "userdata7=%d ", g_list_length (rule->_priv->userdata7));
	list = rule->_priv->userdata7;
  while (list)
  {
    ossim_debug ( " %s ", (gchar *) (list->data));
    list = list->next;
  }
	ossim_debug ( "userdata8=%d ", g_list_length (rule->_priv->userdata8));
	list = rule->_priv->userdata8;
  while (list)
  {
    ossim_debug ( " %s ", (gchar *) (list->data));
    list = list->next;
  }
	ossim_debug ( "userdata9=%d ", g_list_length (rule->_priv->userdata9));
	list = rule->_priv->userdata9;
  while (list)
  {
    ossim_debug ( " %s ", (gchar *) (list->data));
    list = list->next;
  }

  
	ossim_debug ( "\n");
}

/*
 *
 *
 *
 */
gchar*
sim_rule_to_string (SimRule      *rule)
{
  GString  *str;
  gchar    *src_name;
  gchar    *dst_name;
  gchar     timestamp[TIMEBUF_SIZE];

  g_return_val_if_fail (rule, NULL);
  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  strftime (timestamp, TIMEBUF_SIZE, "%Y-%m-%d %H:%M:%S", gmtime ((time_t *) &rule->_priv->time_last));

  src_name = (rule->_priv->src_ia) ? sim_inet_get_canonical_name (rule->_priv->src_ia) : NULL;
  dst_name = (rule->_priv->dst_ia) ? sim_inet_get_canonical_name (rule->_priv->dst_ia) : NULL;

  str = g_string_new ("Rule");
  g_string_append_printf (str, " %d [%s]", rule->_priv->level, timestamp);
  g_string_append_printf (str, " [%d:%d]", rule->_priv->plugin_id, rule->_priv->plugin_sid);
  g_string_append_printf (str, " [Rel:%s%d]", (rule->_priv->rel_abs) ? " " : " +", rule->_priv->reliability);
  g_string_append_printf (str, " %s:%d", src_name, rule->_priv->src_port);

  if (rule->_priv->dst_ia)
    g_string_append_printf (str, " -> %s:%d ", dst_name, rule->_priv->dst_port);

  if (src_name) g_free (src_name);
  if (dst_name) g_free (dst_name);

  return g_string_free (str, FALSE); //liberate the GString object, but not the string itself so we can return it.
}

/**
 * sim_rule_set_src_is_home_net:
 * @rule: #SimRule object
 * @yes_no: gboolean to set
 *
 * sets if the rule src list has HOME_NET
 */
void
sim_rule_set_src_is_home_net (SimRule  *rule,
                              gboolean  yes_no)
{
  g_return_if_fail (SIM_IS_RULE (rule));

  rule->_priv->src_is_home_net = yes_no;
}

/**
 * sim_rule_set_src_is_home_net_no:
 * @rule: #SimRule object
 * @yes_no: gboolean to set
 *
 * sets if the rule src list has !HOME_NET
 */
void
sim_rule_set_src_is_home_net_not (SimRule  *rule,
                                  gboolean  yes_no)
{
  g_return_if_fail (SIM_IS_RULE (rule));

  rule->_priv->src_is_home_net_not = yes_no;
}

/**
 * sim_rule_set_dst_is_home_net:
 * @rule: #SimRule object
 * @yes_no: gboolean to set
 *
 * sets if the rule dst list has HOME_NET
 */
void
sim_rule_set_dst_is_home_net (SimRule  *rule,
                              gboolean  yes_no)
{
  g_return_if_fail (SIM_IS_RULE (rule));

  rule->_priv->dst_is_home_net = yes_no;
}

/**
 * sim_rule_set_dst_is_home_net_no:
 * @rule: #SimRule object
 * @yes_no: gboolean to set
 *
 * sets if the rule dst list has !HOME_NET
 */
void
sim_rule_set_dst_is_home_net_not (SimRule  *rule,
                                  gboolean  yes_no)
{
  g_return_if_fail (SIM_IS_RULE (rule));

  rule->_priv->dst_is_home_net_not = yes_no;
}


static gboolean
sim_rule_match_src_host (SimRule *rule,
                         SimEvent *event)
{
  if (rule->_priv->src_hosts && event->src_id)
  {
    if (g_hash_table_lookup (rule->_priv->src_hosts, (gpointer) event->src_id))
      return TRUE;
  }

  return FALSE;
}

static gboolean
sim_rule_match_src_host_not (SimRule *rule,
                             SimEvent *event)
{
  if (rule->_priv->src_hosts_not && event->src_id)
  {
    if (g_hash_table_lookup (rule->_priv->src_hosts_not, (gpointer) event->src_id))
      return TRUE;
  }

  return FALSE;
}

static gboolean
sim_rule_match_src_ip (SimRule *rule, SimEvent *event)
{
  gboolean match = TRUE;
  gint     positive_match = NO_MATCH;
  gint     negated_match  = NO_MATCH;

  /* First check the src uuid in the host src hash */
  if (rule->_priv->src_hosts)
    match = FALSE;

  if (sim_rule_match_src_host (rule, event))
    return TRUE;

  if (sim_rule_match_src_host_not (rule, event))
    return FALSE;

  // src_ip
  if (rule->_priv->src_inets)
  {
    positive_match = sim_network_match_inet (rule->_priv->src_inets, event->src_ia);

    if (positive_match == EXACT_MATCH) // Host matched
      return TRUE;

    if (positive_match == NO_MATCH)
      match = FALSE;
  }

  // !src_ip
  if (rule->_priv->src_inets_not)
  {
    negated_match = sim_network_match_inet (rule->_priv->src_inets_not, event->src_ia);

    if (negated_match == EXACT_MATCH)
      return FALSE;
  }

  /* If there are any matches here, then return the most exact one */
  if (positive_match != NO_MATCH || negated_match != NO_MATCH)
    return (positive_match >= negated_match);

  // HOME_NET
  if (rule->_priv->src_is_home_net)
    return sim_context_is_inet_in_homenet (event->context, event->src_ia);

  // !HOME_NET
  if (rule->_priv->src_is_home_net_not)
    return !(sim_context_is_inet_in_homenet (event->context, event->src_ia));

  return match;
}

static gboolean
sim_rule_match_dst_host (SimRule *rule,
                         SimEvent *event)
{
  if (rule->_priv->dst_hosts && event->dst_id)
  {
    if (g_hash_table_lookup (rule->_priv->dst_hosts, (gpointer) event->dst_id))
      return TRUE;
  }

  return FALSE;
}

static gboolean
sim_rule_match_dst_host_not (SimRule *rule,
                             SimEvent *event)
{
  if (rule->_priv->dst_hosts_not && event->dst_id)
  {
    if (g_hash_table_lookup (rule->_priv->dst_hosts_not, (gpointer) event->dst_id))
      return TRUE;
  }

  return FALSE;
}

static gboolean
sim_rule_match_dst_ip (SimRule *rule, SimEvent *event)
{
  gboolean  match = TRUE;
  gint     positive_match = NO_MATCH;
  gint     negated_match  = NO_MATCH;

  /* First check the src uuid in the host dst hash */
  if (rule->_priv->dst_hosts)
    match = FALSE;

  if (sim_rule_match_dst_host (rule, event))
    return TRUE;

  if (sim_rule_match_dst_host_not (rule, event))
    return FALSE;

  // dst_ip
  if (rule->_priv->dst_inets)
  {
    positive_match = sim_network_match_inet (rule->_priv->dst_inets, event->dst_ia);

    if (positive_match == EXACT_MATCH) // Host matched
      return TRUE;

    if (positive_match == NO_MATCH)
      match = FALSE;
  }

  // !dst_ip
  if (rule->_priv->dst_inets_not)
  {
    negated_match = sim_network_match_inet (rule->_priv->dst_inets_not, event->dst_ia);

    if (negated_match == EXACT_MATCH)
      return FALSE;
  }

  /* If there are any matches here, then return the most exact one */
  if (positive_match != NO_MATCH || negated_match != NO_MATCH)
    return (positive_match >= negated_match);

  // HOME_NET
  if (rule->_priv->dst_is_home_net)
    return (sim_context_is_inet_in_homenet (event->context, event->dst_ia));

  // !HOME_NET
  if (rule->_priv->dst_is_home_net_not)
    return !(sim_context_is_inet_in_homenet (event->context, event->dst_ia));

  return match;
}

/**
 * sim_rule_free_expand_items:
 * @rule: a #SimRule
 *
 * Frees all items stored in order to expand.
 */
void
sim_rule_free_expand_items (SimRule *rule)
{
  g_return_if_fail (SIM_IS_RULE (rule));

  if (rule->_priv->expand_src_assets_names)
  {
    g_list_foreach (rule->_priv->expand_src_assets_names, (GFunc) g_free, NULL);
    g_list_free (rule->_priv->expand_src_assets_names);
    rule->_priv->expand_src_assets_names = NULL;
  }

  if (rule->_priv->expand_src_assets_names_not)
  {
    g_list_foreach (rule->_priv->expand_src_assets_names_not, (GFunc) g_free, NULL);
    g_list_free (rule->_priv->expand_src_assets_names_not);
    rule->_priv->expand_src_assets_names_not = NULL;
  }

  if (rule->_priv->expand_dst_assets_names)
  {
    g_list_foreach (rule->_priv->expand_dst_assets_names, (GFunc) g_free, NULL);
    g_list_free (rule->_priv->expand_dst_assets_names);
    rule->_priv->expand_dst_assets_names = NULL;
  }

  if (rule->_priv->expand_dst_assets_names_not)
  {
    g_list_foreach (rule->_priv->expand_dst_assets_names_not, (GFunc) g_free, NULL);
    g_list_free (rule->_priv->expand_dst_assets_names_not);
    rule->_priv->expand_dst_assets_names_not = NULL;
    
  }

  if (rule->_priv->expand_entities)
  {
    g_list_foreach (rule->_priv->expand_entities, (GFunc) g_free, NULL);
    g_list_free (rule->_priv->expand_entities);
    rule->_priv->expand_entities = NULL;
  }

  if (rule->_priv->expand_entities_not)
  {
    g_list_foreach (rule->_priv->expand_entities_not, (GFunc) g_free, NULL);
    g_list_free (rule->_priv->expand_entities_not);
    rule->_priv->expand_entities_not = NULL;
  }

  if (rule->_priv->expand_sensors_names)
  {
    g_list_foreach (rule->_priv->expand_sensors_names, (GFunc) g_free, NULL);
    g_list_free (rule->_priv->expand_sensors_names);
    rule->_priv->expand_sensors_names = NULL;
  }

  if (rule->_priv->expand_sensors_names_not)
  {
    g_list_foreach (rule->_priv->expand_sensors_names_not, (GFunc) g_free, NULL);
    g_list_free (rule->_priv->expand_sensors_names_not);
    rule->_priv->expand_sensors_names_not = NULL;
  }

  if (rule->_priv->expand_entities)
  {
    g_list_free (rule->_priv->expand_entities);
    rule->_priv->expand_entities = NULL;
  }

  if (rule->_priv->expand_entities_not)
  {
    g_list_free (rule->_priv->expand_entities_not);
    rule->_priv->expand_entities_not = NULL;
  }
}


#ifdef USE_UNITTESTS

/*************************************************************
 *******************      Unit tests      ********************
 *************************************************************/

static gboolean sim_rule_test1 (void);
static gboolean sim_rule_test2 (void);
static gboolean sim_rule_test3 (void);

static const gchar *home_net_ip[] = {"192.168.0.0/16",
                                     NULL};

static const gchar *host_inside[] = {"192.168.1.1",
                                     "192.168.200.200",
                                     "192.168.100.100",
                                     NULL};

static const gchar *host_outside[] = {"10.10.5.100",
                                      "192.167.1.1",
                                      "172.30.130.130",
                                      NULL};

static const gchar *net_inside[] = {"192.168.1.0/24",
                                    "192.168.200.0/24",
                                    "192.168.100.0/24",
                                    NULL};

static const gchar *nested_host[] = {"192.168.1.5",
                                     "10.10.10.10",
                                     "172.30.100.100",
                                     NULL};

static const gchar *nested_net_small[] = {"192.168.1.0/24",
                                          "10.10.0.0/16",
                                          "172.30.100.0/24",
                                          NULL};

static const gchar *nested_net_big[] = {"192.168.0.0/16",
                                        "10.0.0.0/8",
                                        "172.30.0.0/16",
                                        NULL};

/* test 1 */
#define LOAD_SRC(ip) i = 0;                           \
  while (ip[i])                                       \
  {                                                   \
    SimInet *inet = sim_inet_new_from_string (ip[i]); \
    sim_rule_add_src_inet (rule, inet);               \
    g_object_unref (inet);                            \
    i++;                                              \
  }

#define LOAD_NOT_SRC(ip) i = 0;                       \
  while (ip[i])                                       \
  {                                                   \
    SimInet *inet = sim_inet_new_from_string (ip[i]); \
    sim_rule_add_src_inet_not (rule, inet);           \
    g_object_unref (inet);                            \
    i++;                                              \
  }


#define MATCH_HOST_SRC(host,must_match) i = 0;              \
  while (host[i])                                           \
  {                                                         \
    SimInet *inet = sim_inet_new_from_string (host[i]);     \
    event->src_ia = inet;                                   \
    if (sim_rule_match_src_ip (rule, event) != must_match)  \
    {                                                       \
      g_print ("Check error for ip %s\n", host[i]);         \
      success = FALSE;                                      \
    }                                                       \
    g_object_unref (inet);                                  \
    i++;                                                    \
  }


static gboolean
sim_rule_test1 (void)
{
  gboolean success = TRUE;

  SimRule *rule = sim_rule_new ();
  SimEvent *event = sim_event_new ();
  SimUuid *ctx_default = sim_uuid_new ();
  SimContext *context = sim_context_new (ctx_default);

  event->context = g_object_ref (context);

  SimNetwork *home_net = sim_context_get_home_net (context);
  gint i = 0;
  while (home_net_ip[i])
  {
    SimInet *inet = sim_inet_new_from_string (home_net_ip[i]);
    sim_network_add_inet (home_net, inet);
    g_object_unref (inet);

    i++;
 }

  /* Home Net */
  g_print ("Checking home_net...\n");

  sim_rule_set_src_is_home_net (rule, TRUE);

  MATCH_HOST_SRC (host_inside, TRUE)
  MATCH_HOST_SRC (host_outside, FALSE)

  /* !Home Net */
  g_print ("Checking !home_net...\n");

  sim_rule_set_src_is_home_net (rule, FALSE);
  sim_rule_set_src_is_home_net_not (rule, TRUE);

  MATCH_HOST_SRC (host_inside, FALSE)
  MATCH_HOST_SRC (host_outside, TRUE)

  /* Positive host */
  g_print ("Checking host...\n");

  sim_rule_set_src_is_home_net (rule, FALSE);
  sim_rule_set_src_is_home_net_not (rule, FALSE);

  LOAD_SRC (host_inside)

  MATCH_HOST_SRC (host_inside, TRUE)
  MATCH_HOST_SRC (host_outside, FALSE)

  /* Negative host */
  g_print ("Checking !host...\n");

  g_object_unref (rule);
  rule = sim_rule_new ();

  LOAD_NOT_SRC (host_inside)

  MATCH_HOST_SRC (host_inside, FALSE)
  MATCH_HOST_SRC (host_outside, TRUE)

  /* Positive net */
  g_print ("Checking net...\n");

  g_object_unref (rule);
  rule = sim_rule_new ();

  LOAD_SRC (net_inside)

  MATCH_HOST_SRC (host_inside, TRUE)
  MATCH_HOST_SRC (host_outside, FALSE)

  /* Negative net */
  g_print ("Checking !net...\n");

  g_object_unref (rule);
  rule = sim_rule_new ();

  LOAD_NOT_SRC (net_inside)

  MATCH_HOST_SRC (host_inside, FALSE)
  MATCH_HOST_SRC (host_outside, TRUE)

  /* Home Net and !host */
  g_print ("Checking home_net & !host...\n");

  g_object_unref (rule);
  rule = sim_rule_new ();

  sim_rule_set_src_is_home_net (rule, TRUE);
  sim_rule_set_src_is_home_net_not (rule, FALSE);

  LOAD_NOT_SRC (host_inside)

  MATCH_HOST_SRC (host_inside,  FALSE)
  MATCH_HOST_SRC (host_outside, FALSE)

  /* host and !home_net */
  g_print ("Checking host & !home_net...\n");

  g_object_unref (rule);
  rule = sim_rule_new ();

  sim_rule_set_src_is_home_net_not (rule, TRUE);

  LOAD_SRC (host_inside)

  MATCH_HOST_SRC (host_inside,  TRUE)
  MATCH_HOST_SRC (host_outside, TRUE)

  /* home_net and !net */
  g_print ("Checking home_net & !net...\n");

  g_object_unref (rule);
  rule = sim_rule_new ();

  sim_rule_set_src_is_home_net (rule, TRUE);

  LOAD_NOT_SRC (net_inside)

  MATCH_HOST_SRC (host_inside, FALSE)
  MATCH_HOST_SRC (host_outside, FALSE)

  /* net and !home_net */
  g_print ("Checking net & !home_net...\n");

  g_object_unref (rule);
  rule = sim_rule_new ();

  sim_rule_set_src_is_home_net_not (rule, TRUE);

  LOAD_SRC (net_inside)

  MATCH_HOST_SRC (host_inside, TRUE)
  MATCH_HOST_SRC (host_outside, TRUE)

  /* Net and !host */
  g_print ("Checking net & !host...\n");

  g_object_unref (rule);
  rule = sim_rule_new ();

  LOAD_SRC (nested_net_small)
  LOAD_NOT_SRC (nested_host)

  MATCH_HOST_SRC (nested_host, FALSE)

  /* Host and !net */
  g_print ("Checking host & !net...\n");

  g_object_unref (rule);
  rule = sim_rule_new ();

  LOAD_SRC (nested_host)
  LOAD_NOT_SRC (nested_net_small)

  MATCH_HOST_SRC (nested_host, TRUE)

  /* small_net and !big_net */
  g_print ("Checking small_net & !big_net...\n");

  g_object_unref (rule);
  rule = sim_rule_new ();

  LOAD_SRC (nested_net_small)
  LOAD_NOT_SRC (nested_net_big)

  MATCH_HOST_SRC (nested_host, TRUE)

  /* big_net and !small_net */
  g_print ("Checking big_net & !small_net...\n");

  g_object_unref (rule);
  rule = sim_rule_new ();

  LOAD_SRC (nested_net_big)
  LOAD_NOT_SRC (nested_net_small)

  MATCH_HOST_SRC (nested_host, FALSE)

  /* unref */
  event->src_ia = NULL;
  sim_event_unref (event);

  g_object_unref (rule);

 if (context)
   g_object_unref (context);

  return success;
}

/* test2 */
#define LOAD_DST(ip) i = 0;                           \
  while (ip[i])                                       \
  {                                                   \
    SimInet *inet = sim_inet_new_from_string (ip[i]); \
    sim_rule_add_dst_inet (rule, inet);               \
    g_object_unref (inet);                            \
    i++;                                              \
  }

#define LOAD_NOT_DST(ip) i = 0;                       \
  while (ip[i])                                       \
  {                                                   \
    SimInet *inet = sim_inet_new_from_string (ip[i]); \
    sim_rule_add_dst_inet_not (rule, inet);           \
    g_object_unref (inet);                            \
    i++;                                              \
  }


#define MATCH_HOST_DST(host,must_match) i = 0;              \
  while (host[i])                                           \
  {                                                         \
    SimInet *inet = sim_inet_new_from_string (host[i]);     \
    event->dst_ia = inet;                                   \
    if (sim_rule_match_dst_ip (rule, event) != must_match)  \
    {                                                       \
      g_print ("Check error for ip %s\n", host[i]);         \
      success = FALSE;                                      \
    }                                                       \
    g_object_unref (inet);                                  \
    i++;                                                    \
  }

static gboolean
sim_rule_test2 (void)
{
  gboolean success = TRUE;

  SimRule *rule = sim_rule_new ();
  SimEvent *event = sim_event_new ();
  SimUuid *ctx_default = sim_uuid_new ();
  SimContext *context = sim_context_new (ctx_default);

  event->context = g_object_ref (context);

  SimNetwork *home_net = sim_context_get_home_net (context);
  gint i = 0;
  while (home_net_ip[i])
  {
    SimInet *inet = sim_inet_new_from_string (home_net_ip[i]);
    sim_network_add_inet (home_net, inet);
    g_object_unref (inet);

    i++;
  }

  /* Home Net */
  g_print ("Checking home_net...\n");

  sim_rule_set_dst_is_home_net (rule, TRUE);

  MATCH_HOST_DST (host_inside, TRUE)
  MATCH_HOST_DST (host_outside, FALSE)

  /* !Home Net */
  g_print ("Checking !home_net...\n");

  sim_rule_set_dst_is_home_net (rule, FALSE);
  sim_rule_set_dst_is_home_net_not (rule, TRUE);

  MATCH_HOST_DST (host_inside, FALSE)
  MATCH_HOST_DST (host_outside, TRUE)

  /* Positive host */
  g_print ("Checking host...\n");

  sim_rule_set_dst_is_home_net (rule, FALSE);
  sim_rule_set_dst_is_home_net_not (rule, FALSE);

  LOAD_DST (host_inside)

  MATCH_HOST_DST (host_inside, TRUE)
  MATCH_HOST_DST (host_outside, FALSE)

  /* Negative host */
  g_print ("Checking !host...\n");

  g_object_unref (rule);
  rule = sim_rule_new ();

  LOAD_NOT_DST (host_inside)

  MATCH_HOST_DST (host_inside, FALSE)
  MATCH_HOST_DST (host_outside, TRUE)

  /* Positive net */
  g_print ("Checking net...\n");

  g_object_unref (rule);
  rule = sim_rule_new ();

  LOAD_DST (net_inside)

  MATCH_HOST_DST (host_inside, TRUE)
  MATCH_HOST_DST (host_outside, FALSE)

  /* Negative net */
  g_print ("Checking !net...\n");

  g_object_unref (rule);
  rule = sim_rule_new ();

  LOAD_NOT_DST (net_inside)

  MATCH_HOST_DST (host_inside, FALSE)
  MATCH_HOST_DST (host_outside, TRUE)

  /* Home Net and !host */
  g_print ("Checking home_net & !host...\n");

  g_object_unref (rule);
  rule = sim_rule_new ();

  sim_rule_set_dst_is_home_net (rule, TRUE);
  sim_rule_set_dst_is_home_net_not (rule, FALSE);

  LOAD_NOT_DST (host_inside)

  MATCH_HOST_DST (host_inside,  FALSE)
  MATCH_HOST_DST (host_outside, FALSE)

  /* host and !home_net */
  g_print ("Checking host & !home_net...\n");

  g_object_unref (rule);
  rule = sim_rule_new ();

  sim_rule_set_dst_is_home_net_not (rule, TRUE);

  LOAD_DST (host_inside)

  MATCH_HOST_DST (host_inside,  TRUE)
  MATCH_HOST_DST (host_outside, TRUE)

  /* home_net and !net */
  g_print ("Checking home_net & !net...\n");

  g_object_unref (rule);
  rule = sim_rule_new ();

  sim_rule_set_dst_is_home_net (rule, TRUE);

  LOAD_NOT_DST (net_inside)

  MATCH_HOST_DST (host_inside, FALSE)
  MATCH_HOST_DST (host_outside, FALSE)

  /* net and !home_net */
  g_print ("Checking net & !home_net...\n");

  g_object_unref (rule);
  rule = sim_rule_new ();

  sim_rule_set_dst_is_home_net_not (rule, TRUE);

  LOAD_DST (net_inside)

  MATCH_HOST_DST (host_inside, TRUE)
  MATCH_HOST_DST (host_outside, TRUE)

  /* Net and !host */
  g_print ("Checking net & !host...\n");

  g_object_unref (rule);
  rule = sim_rule_new ();

  LOAD_DST (nested_net_small)
  LOAD_NOT_DST (nested_host)

  MATCH_HOST_DST (nested_host, FALSE)

  /* Host and !net */
  g_print ("Checking host & !net...\n");

  g_object_unref (rule);
  rule = sim_rule_new ();

  LOAD_DST (nested_host)
  LOAD_NOT_DST (nested_net_small)

  MATCH_HOST_DST (nested_host, TRUE)

  /* small_net and !big_net */
  g_print ("Checking small_net & !big_net...\n");

  g_object_unref (rule);
  rule = sim_rule_new ();

  LOAD_DST (nested_net_small)
  LOAD_NOT_DST (nested_net_big)

  MATCH_HOST_DST (nested_host, TRUE)

  /* big_net and !small_net */
  g_print ("Checking big_net & !small_net...\n");

  g_object_unref (rule);
  rule = sim_rule_new ();

  LOAD_DST (nested_net_big)
  LOAD_NOT_DST (nested_net_small)

  MATCH_HOST_DST (nested_host, FALSE)

  /* unref */
  event->dst_ia = NULL;
  sim_event_unref (event);

  g_object_unref (rule);
  g_object_unref (context);

  return success;
}

static gboolean
sim_rule_test3 (void)
{
  gboolean success = TRUE;

  SimRule *rule = sim_rule_new ();
  SimEvent *event = sim_event_new ();
  SimInet *src_inet = sim_inet_new_from_string ("0.0.0.0");
  SimUuid *uuid1 = sim_uuid_new ();
  SimUuid *uuid2 = sim_uuid_new ();
  SimContext *context = sim_context_new (uuid1);
  SimContext *context2 = sim_context_new (uuid2);

  event->context = g_object_ref (context);
  event->type = SIM_EVENT_TYPE_DETECTOR;
  event->plugin_id = 1;
  event->plugin_sid = 1;
  event->src_ia = g_object_ref (src_inet);

  rule->type = SIM_RULE_TYPE_DETECTOR;
  sim_rule_set_plugin_id (rule, 1);
  sim_rule_set_plugin_sid (rule, 1);

  g_print ("Match without entity\n");
  if (!sim_rule_match_by_event (rule, event))
  {
    g_print ("!MATCH\n");
    success = FALSE;
  }

  g_print ("Match with different entity\n");
  sim_rule_add_entity (rule, context2);

  if (sim_rule_match_by_event (rule, event))
  {
    g_print ("MATCH\n");
    success = FALSE;
  }

  g_print ("Match with the same entity\n");
  sim_rule_add_entity (rule, context);

  if (!sim_rule_match_by_event (rule, event))
  {
    g_print ("!MATCH\n");
    success = FALSE;
  }

  g_object_unref (context);
  event->context = g_object_ref (context2);

  if (!sim_rule_match_by_event (rule, event))
  {
    g_print ("!MATCH\n");
    success = FALSE;
  }

  g_print ("free\n");

  g_object_unref (uuid1);
  g_object_unref (uuid2);
  g_object_unref (src_inet);
  g_object_unref (context);
  g_object_unref (context2);
  sim_event_unref (event);

  g_object_unref (rule);

  return success;
}

void
sim_rule_register_tests (SimUnittesting *engine)
{
  sim_unittesting_append (engine, "sim_rule_test1 - src_ip Match", sim_rule_test1, TRUE);
  sim_unittesting_append (engine, "sim_rule_test2 - dst_ip Match", sim_rule_test2, TRUE);
  sim_unittesting_append (engine, "sim_rule_test3 - entity Match", sim_rule_test3, TRUE);
}

#endif /* USE_UNITTESTS */

// vim: set tabstop=2:

