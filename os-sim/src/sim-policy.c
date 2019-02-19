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

#include "sim-policy.h"

/*****/
#include <time.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <arpa/inet.h>
#include <string.h>
#include <stdlib.h>
#include <limits.h>

#ifdef BSD
#define KERNEL
#include <netinet/in.h>
#endif
/*****/

#include "sim-timezone.h"
#include "sim-sensor.h"
#include "sim-inet.h"
#include "sim-event.h"
#include "os-sim.h"

extern SimMain ossim;

enum
{
  DESTROY,
  LAST_SIGNAL
};

#define TIME_WILDCARD 0

struct _SimPolicyPrivate
{
  SimUuid *id;
  SimUuid *context_id;
  gint     priority;
  gchar   *description;

  gushort minute_start;
  gushort minute_end;
  GDateWeekday week_day_start;
  GDateWeekday week_day_end;
  GDateDay month_day_start;
  GDateDay month_day_end;
  GDateMonth month_start;
  GDateMonth month_end;
  SimTimezone * timezone;

  gint    has_actions;
  gint    has_alarm_actions;

  SimRole *role;        //this is not intended to match. This is the behaviour of the events that matches with this policy

  SimNetwork  *src;   // SimInet objects
  SimNetwork  *dst;
  GHashTable  *src_hosts;
  GHashTable  *dst_hosts;

  GList  *ports_src;		//port & protocol list, SimPortProtocol object.
  GList  *ports_dst;
  GList  *sensors; 			//gchar* sensor's ip (i.e. "1.1.1.1")
  GList  *plugin_ids; 	//(guint *) list with each one of the plugin_id's
  GList  *plugin_sids;	//
  GList  *plugin_groups;	// *Plugin_PluginSid structs

  GList    *risks;      // List with SimPolicyRisk

  gboolean  any_src;
  gboolean  any_dst;

  // Reputation data.
  GList *reputation_src;
  GList *reputation_dst;

  // Taxonomy data.
  GHashTable * taxonomy;
};


static gpointer parent_class = NULL;
/* we don't use signals
static gint sim_policy_signals[LAST_SIGNAL] = { 0 };
*/


/* GType Functions */

static void 
sim_policy_impl_dispose (GObject  *gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void 
sim_policy_impl_finalize (GObject  *gobject)
{
  SimPolicy  *policy = SIM_POLICY (gobject);

  if (policy->_priv->context_id)
    g_object_unref (policy->_priv->context_id);

  if (policy->_priv->description)
    g_free (policy->_priv->description);

  if (policy->_priv->src != NULL)
    g_object_unref (policy->_priv->src);

  if (policy->_priv->dst != NULL)
    g_object_unref (policy->_priv->dst);

  if (policy->_priv->src_hosts)
    g_hash_table_destroy (policy->_priv->src_hosts);

  if (policy->_priv->dst_hosts)
    g_hash_table_destroy (policy->_priv->dst_hosts);

  sim_policy_free_ports (policy);
  sim_policy_free_sensors (policy);
	//FIXME: sim_policy_free_plugin_id y sid.

  if (policy->_priv->role)
    sim_role_unref (policy->_priv->role);

  if (policy->_priv->risks)
  {
    g_list_foreach (policy->_priv->risks, (GFunc)g_free, NULL);
    g_list_free (policy->_priv->risks);
    policy->_priv->risks = NULL;
  }
  
  if (policy->_priv->reputation_src)
    sim_policy_free_reputation_src (policy);

  if (policy->_priv->reputation_dst)
    sim_policy_free_reputation_dst (policy);

  // Taxonomy
  if (policy->_priv->taxonomy)
    g_hash_table_destroy (policy->_priv->taxonomy);

  if (policy->_priv->timezone)
    g_object_unref (policy->_priv->timezone);

  g_free (policy->_priv);

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_policy_class_init (SimPolicyClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_peek_parent (class);

  object_class->dispose = sim_policy_impl_dispose;
  object_class->finalize = sim_policy_impl_finalize;
}

static void
sim_policy_instance_init (SimPolicy *policy)
{
  policy->_priv = g_new0 (SimPolicyPrivate, 1);

  policy->_priv->id = NULL;
  policy->_priv->context_id = NULL;
  policy->_priv->priority = 1;
  policy->_priv->description = NULL;

  policy->_priv->minute_start = 0;
  policy->_priv->minute_end = 1439;
  policy->_priv->week_day_start = G_DATE_BAD_WEEKDAY;
  policy->_priv->week_day_end = G_DATE_BAD_WEEKDAY;
  policy->_priv->month_day_start = G_DATE_BAD_DAY;
  policy->_priv->month_day_end = G_DATE_BAD_DAY;
  policy->_priv->month_start = G_DATE_BAD_MONTH;
  policy->_priv->month_end = G_DATE_BAD_MONTH;
  policy->_priv->timezone = NULL;

  policy->_priv->src = NULL;
  policy->_priv->any_src = FALSE;
  policy->_priv->dst = NULL;
  policy->_priv->any_dst = FALSE;
  policy->_priv->src_hosts = NULL; 
  policy->_priv->dst_hosts = NULL;
  policy->_priv->ports_src = NULL;
  policy->_priv->ports_dst = NULL;
  policy->_priv->sensors = NULL;
  policy->_priv->plugin_ids = NULL;
  policy->_priv->plugin_sids = NULL;
  policy->_priv->plugin_groups = NULL;
  policy->_priv->risks = NULL;

  policy->_priv->has_actions= 0;
  policy->_priv->has_alarm_actions= 0;

  policy->_priv->role = NULL;
}

/* Public Methods */

GType
sim_policy_get_type (void)
{
  static GType object_type = 0;
 
  if (!object_type)
  {
    static const GTypeInfo type_info = {
              sizeof (SimPolicyClass),
              NULL,
              NULL,
              (GClassInitFunc) sim_policy_class_init,
              NULL,
              NULL,                       /* class data */
              sizeof (SimPolicy),
              0,                          /* number of pre-allocs */
              (GInstanceInitFunc) sim_policy_instance_init,
              NULL                        /* value table */
    };
    
                                                                                                                             
    object_type = g_type_register_static (G_TYPE_OBJECT, "SimPolicy", &type_info, 0);
  }
                                                                                                                             
  return object_type;
}

/*
 *
 *
 *
 *
 */
SimPolicy*
sim_policy_new (void)
{
  SimPolicy *policy;

  policy = SIM_POLICY (g_object_new (SIM_TYPE_POLICY, NULL));

  return policy;
}

/*
 *
 *
 *
 */
SimPolicy*
sim_policy_new_from_dm (GdaDataModel  *dm,
			gint           row)
{
  SimPolicy    *policy;
  const GValue *value;
  GValue  smallint = { 0, {{0}, {0}} };
  gushort hour_start, hour_end;
  gchar * timezone;

  g_return_val_if_fail (GDA_IS_DATA_MODEL (dm), NULL);

  policy = SIM_POLICY (g_object_new (SIM_TYPE_POLICY, NULL));

  value = gda_data_model_get_value_at (dm, 0, row, NULL);
  policy->_priv->id = sim_uuid_new_from_blob (gda_value_get_blob (value));

  if (!(policy->_priv->id))
  {
    g_object_unref (policy);
    return (NULL);
  }

  value = gda_data_model_get_value_at (dm, 1, row, NULL);
  policy->_priv->context_id = sim_uuid_new_from_blob (gda_value_get_blob (value));
  if (!(policy->_priv->context_id))
  {
    g_object_unref (policy);
    return (NULL);
  }

  value = gda_data_model_get_value_at (dm, 2, row, NULL);
  // FIXME: temporal fix until GDA reads smallints properly
  g_value_init (&smallint, GDA_TYPE_SHORT);
  g_value_transform (value, &smallint);
  policy->_priv->priority = gda_value_get_short (&smallint);

  value = gda_data_model_get_value_at (dm, 3, row, NULL);
  policy->_priv->description = g_value_dup_string (value);

  value = gda_data_model_get_value_at (dm, 4, row, NULL);
  if ((policy->_priv->minute_start = g_value_get_int (value)) > 59)
  {
    g_object_unref (policy);
    return (NULL);
  }

  value = gda_data_model_get_value_at (dm, 5, row, NULL);
  if ((policy->_priv->minute_end = g_value_get_int (value)) > 59)
  {
    g_object_unref (policy);
    return (NULL);
  }

  value = gda_data_model_get_value_at (dm, 6, row, NULL);
  hour_start = g_value_get_int (value);
  if (hour_start > 23)
  {
    g_object_unref (policy);
    return (NULL);
  }

  policy->_priv->minute_start += hour_start * 60;

  value = gda_data_model_get_value_at (dm, 7, row, NULL);
  hour_end = g_value_get_int (value);
  if (hour_end > 23)
  {
    g_object_unref (policy);
    return (NULL);
  }

  policy->_priv->minute_end += hour_end * 60;

  value = gda_data_model_get_value_at (dm, 8, row, NULL);
  policy->_priv->week_day_start = g_value_get_int (value);
  if ((policy->_priv->week_day_start != TIME_WILDCARD) && (!g_date_valid_weekday (policy->_priv->week_day_start)))
  {
    g_object_unref (policy);
    return (NULL);
  }

  value = gda_data_model_get_value_at (dm, 9, row, NULL);
  policy->_priv->week_day_end = g_value_get_int (value);
  if ((policy->_priv->week_day_end != TIME_WILDCARD) && (!g_date_valid_weekday (policy->_priv->week_day_end)))
  {
    g_object_unref (policy);
    return (NULL);
  }

  value = gda_data_model_get_value_at (dm, 10, row, NULL);
  policy->_priv->month_day_start = g_value_get_int (value);
  if ((policy->_priv->month_day_start != TIME_WILDCARD) && (!g_date_valid_day (policy->_priv->month_day_start)))
  {
    g_object_unref (policy);
    return (NULL);
  }

  value = gda_data_model_get_value_at (dm, 11, row, NULL);
  policy->_priv->month_day_end = g_value_get_int (value);
  if ((policy->_priv->month_day_end != TIME_WILDCARD) && (!g_date_valid_day (policy->_priv->month_day_end)))
  {
    g_object_unref (policy);
    return (NULL);
  }

  value = gda_data_model_get_value_at (dm, 12, row, NULL);
  policy->_priv->month_start = g_value_get_int (value);
  if ((policy->_priv->month_start != TIME_WILDCARD) && (!g_date_valid_month (policy->_priv->month_start)))
  {
    g_object_unref (policy);
    return (NULL);
  }

  value = gda_data_model_get_value_at (dm, 13, row, NULL);
  policy->_priv->month_end = g_value_get_int (value);
  if ((policy->_priv->month_end != TIME_WILDCARD) && (!g_date_valid_month (policy->_priv->month_end)))
  {
    g_object_unref (policy);
    return (NULL);
  }

  value = gda_data_model_get_value_at (dm, 14, row, NULL);
  timezone = g_value_dup_string (value);
  policy->_priv->timezone = sim_timezone_new (timezone);
  g_free (timezone);

  return policy;
}

/*
 *
 *
 *
 */
SimUuid *
sim_policy_get_id (SimPolicy* policy)
{
  g_return_val_if_fail (SIM_IS_POLICY (policy), 0);

  return policy->_priv->id;
}

/*
 *
 *
 *
 */
void
sim_policy_set_id (SimPolicy* policy,
                   SimUuid  * id)
{
  g_return_if_fail (SIM_IS_POLICY (policy));

  policy->_priv->id = id;
}

/**
 * sim_policy_get_context_id:
 *
 */
SimUuid *
sim_policy_get_context_id (SimPolicy* policy)
{
  g_return_val_if_fail (SIM_IS_POLICY (policy), 0);

  return policy->_priv->context_id;
}

/**
 * sim_policy_set_context_id:
 *
 */
void
sim_policy_set_context_id (SimPolicy *policy,
                           SimUuid   *context_id)
{
  g_return_if_fail (SIM_IS_POLICY (policy));

  if (policy->_priv->context_id)
    g_object_unref (policy->_priv->context_id);

  policy->_priv->context_id = g_object_ref (context_id);
}

/*
 *
 *
 *
 */
gint
sim_policy_get_priority (SimPolicy* policy)
{
  g_return_val_if_fail (SIM_IS_POLICY (policy), 0);

  if (policy->_priv->priority < -1) //-1 means "don't change priority"
    return 0;
  if (policy->_priv->priority > 5)
    return 5;

  return policy->_priv->priority;
}

/*
 *
 *
 *
 */
void
sim_policy_set_priority (SimPolicy* policy,
												 gint       priority)
{
  g_return_if_fail (SIM_IS_POLICY (policy));

  if (priority < -1)
    policy->_priv->priority = 0;
  else if (priority > 5)
    policy->_priv->priority = 5;
  else policy->_priv->priority = priority;
}

/*
 *
 *
 * If the policy has actions, return it*
 */
gint
sim_policy_get_has_actions (SimPolicy* policy)
{
  g_return_val_if_fail (SIM_IS_POLICY (policy), 0);

  return policy->_priv->has_actions;
}

/*
 *
 *
 * Set if the policy has actions
 */
void
sim_policy_set_has_actions (SimPolicy* policy, gint actions)
{
  g_return_if_fail (SIM_IS_POLICY (policy));

  policy->_priv->has_actions=actions;
}

/**
 * sim_policy_get_has_alarm_actions:
 *
 */
gint
sim_policy_get_has_alarm_actions (SimPolicy* policy)
{
  g_return_val_if_fail (SIM_IS_POLICY (policy), 0);

  return policy->_priv->has_alarm_actions;
}

/**
 * sim_policy_set_has_alarm_actions:
 *
 */
void
sim_policy_set_has_alarm_actions (SimPolicy* policy, gint alarm_actions)
{
  g_return_if_fail (SIM_IS_POLICY (policy));

  policy->_priv->has_alarm_actions = alarm_actions;

  return;
}


/**
 *  sim_policy_append_src:
 *  @policy: SimPolicy object
 *  @src: SimInet object
 *
 *  Adds the SimInet object into the src tree.
 *
 *  SimInet objects can store hosts or networks, so we'll use it in the policy.
 */
void
sim_policy_append_src (SimPolicy *policy,
								       SimInet   *src) 
{
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (SIM_IS_INET (src));

  if (policy->_priv->src == NULL)
    policy->_priv->src = sim_network_new ();

  sim_network_add_inet (policy->_priv->src, src);
}

/**
 *  sim_policy_get_any_src:
 *  @policy: SimPolicy object
 *
 *  Return value: any_src gboolean
 */
gboolean
sim_policy_get_any_src (SimPolicy *policy)
{
  g_return_val_if_fail (SIM_IS_POLICY (policy), FALSE);

  return policy->_priv->any_src;
}

/**
 *  sim_policy_get_any_src:
 *  @policy: SimPolicy object
 *  @true_false: gboolean value
 *
 *  Return value: void
 */
void sim_policy_set_any_src (SimPolicy     *policy, gboolean true_false)
{
  g_return_if_fail (SIM_IS_POLICY (policy));

  policy->_priv->any_src = true_false;
}


/**
 *  sim_policy_get_src:
 *  @policy: SimPolicy object
 *
 *  Return value: src SimNetwork
 */
SimNetwork *
sim_policy_get_src (SimPolicy *policy)
{
  g_return_val_if_fail (SIM_IS_POLICY (policy), NULL);

  return policy->_priv->src;
}

/**
 *  sim_policy_append_dst:
 *  @policy: SimPolicy object
 *  @dst: SimInet object
 *
 *  Adds the SimInet object into the dst tree.
 *
 *  SimInet objects can store hosts or networks, so we'll use it in the policy.
 */
void
sim_policy_append_dst (SimPolicy *policy,
                       SimInet   *dst)
{
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (SIM_IS_INET (dst));

  if (policy->_priv->dst == NULL)
    policy->_priv->dst = sim_network_new ();

  sim_network_add_inet (policy->_priv->dst, dst);
}

/**
 *  sim_policy_get_src:
 *  @policy: SimPolicy object
 *
 *  Return value: src SimNetwork
 */
SimNetwork *
sim_policy_get_dst (SimPolicy *policy)
{
  g_return_val_if_fail (SIM_IS_POLICY (policy), NULL);

  return policy->_priv->dst;
}

/**
 *  sim_policy_get_any_dst:
 *  @policy: SimPolicy object
 *
 *  Return value: any_dst gboolean
 */
gboolean
sim_policy_get_any_dst (SimPolicy *policy)
{
  g_return_val_if_fail (SIM_IS_POLICY (policy), FALSE);

  return policy->_priv->any_dst;
}

/**
 *  sim_policy_set_any_dst:
 *  @policy: SimPolicy object
 *  @true_false: gboolean value
 *
 *  Return value: void
 */
void sim_policy_set_any_dst (SimPolicy *policy, gboolean true_false)
{
  g_return_if_fail (SIM_IS_POLICY (policy));

  policy->_priv->any_dst = true_false;
}

/**
 * sim_policy_append_port_src:
 *
 */
void
sim_policy_append_port_src (SimPolicy        *policy,
                            SimPortProtocol  *pp)
{
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (pp);

  policy->_priv->ports_src = g_list_append (policy->_priv->ports_src, pp);
}

/**
 * sim_policy_remove_port_src:
 *
 */
void
sim_policy_remove_port_src (SimPolicy        *policy,
                            SimPortProtocol  *pp)
{
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (pp);

  policy->_priv->ports_src = g_list_remove (policy->_priv->ports_src, pp);
}

/**
 * sim_policy_get_ports_src:
 *
 */
GList*
sim_policy_get_ports_src (SimPolicy* policy)
{
  g_return_val_if_fail (SIM_IS_POLICY (policy), NULL);

  return policy->_priv->ports_src;
}

/**
 * sim_policy_append_port_dst:
 *
 */
void
sim_policy_append_port_dst (SimPolicy        *policy,
                            SimPortProtocol  *pp)
{
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (pp);

  policy->_priv->ports_dst = g_list_append (policy->_priv->ports_dst, pp);
}

/**
 * sim_policy_remove_port_dst:
 *
 */
void
sim_policy_remove_port_dst (SimPolicy        *policy,
                            SimPortProtocol  *pp)
{
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (pp);

  policy->_priv->ports_dst = g_list_remove (policy->_priv->ports_dst, pp);
}

/**
 * sim_policy_get_ports_dst:
 *
 */
GList*
sim_policy_get_ports_dst (SimPolicy* policy)
{
  g_return_val_if_fail (SIM_IS_POLICY (policy), NULL);

  return policy->_priv->ports_dst;
}

/*
 *
 *
 *
 */
void
sim_policy_free_ports (SimPolicy* policy)
{
  g_return_if_fail (SIM_IS_POLICY (policy));

  g_list_foreach (policy->_priv->ports_src, (GFunc)g_free, NULL);
  g_list_free (policy->_priv->ports_src);
  g_list_foreach (policy->_priv->ports_dst, (GFunc)g_free, NULL);
  g_list_free (policy->_priv->ports_dst);

  return;
}



/*
 *
 *
 *
 */
void
sim_policy_append_sensor (SimPolicy        *policy,
								          gchar            *sensor)
{
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (sensor);

  policy->_priv->sensors = g_list_append (policy->_priv->sensors, sensor);
}

/*
 *
 *
 *
 */
void
sim_policy_remove_sensor (SimPolicy        *policy,
								           gchar            *sensor)
{
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (sensor);

  policy->_priv->sensors = g_list_remove (policy->_priv->sensors, sensor);
}


/*
 *
 *
 *
 */
GList*
sim_policy_get_sensors (SimPolicy* policy)
{
  g_return_val_if_fail (SIM_IS_POLICY (policy), NULL);

  return policy->_priv->sensors;
}

/*
 *
 *
 *
 */
void
sim_policy_free_sensors (SimPolicy* policy)
{
  GList   *list;

  g_return_if_fail (SIM_IS_POLICY (policy));

  list = policy->_priv->sensors;
  while (list)
  {
    gchar *sensor = (gchar *) list->data;
    g_free (sensor);
    list = list->next;
  }
  g_list_free (policy->_priv->sensors);
}


/*
 *
 */
void
sim_policy_append_plugin_id (SimPolicy        *policy,
		                         guint            *plugin_id)
{
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (plugin_id);
	
  policy->_priv->plugin_ids = g_list_append (policy->_priv->plugin_ids, plugin_id);
}

/*
 * 
 */
void
sim_policy_remove_plugin_id (SimPolicy        *policy,
                             guint            *plugin_id)
{
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (plugin_id);

  policy->_priv->plugin_ids = g_list_remove (policy->_priv->plugin_ids, plugin_id);
}

/*
 *
 */
GList*
sim_policy_get_plugin_ids (SimPolicy* policy)
{
  g_return_val_if_fail (SIM_IS_POLICY (policy), NULL);

  return policy->_priv->plugin_ids;
}

/*
 *
 *
 *
 */
void
sim_policy_free_plugin_ids (SimPolicy* policy)
{
  GList   *list;

  g_return_if_fail (SIM_IS_POLICY (policy));

  list = policy->_priv->plugin_ids;
  while (list)
  {
    guint *plugin_id = (guint *) list->data;
    g_free (plugin_id);
    list = list->next;
  }
  g_list_free (policy->_priv->plugin_ids);
}


/*
 *
 */
void
sim_policy_append_plugin_sid (SimPolicy        *policy,
		                      		guint            *plugin_sid)
{
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (plugin_sid);

  policy->_priv->plugin_sids = g_list_append (policy->_priv->plugin_sids, plugin_sid);
}

/*
 * 
 */
void
sim_policy_remove_plugin_sid (SimPolicy        *policy,
	                            guint            *plugin_sid)
{
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (plugin_sid);

  policy->_priv->plugin_sids = g_list_remove (policy->_priv->plugin_sids, plugin_sid);
}

/*
 *
 */
GList*
sim_policy_get_plugin_sids (SimPolicy* policy)
{
  g_return_val_if_fail (SIM_IS_POLICY (policy), NULL);

  return policy->_priv->plugin_sids;
}

/*
 *
 *
 *
 */
void
sim_policy_free_plugin_sids (SimPolicy* policy)
{
  GList   *list;

  g_return_if_fail (SIM_IS_POLICY (policy));

  list = policy->_priv->plugin_sids;
  while (list)
  {
    guint *plugin_sid = (guint *) list->data;
    g_free (plugin_sid);
    list = list->next;
  }
  g_list_free (policy->_priv->plugin_sids);
}

/*
 *
 */
void
sim_policy_append_plugin_group (SimPolicy					 *policy,
																Plugin_PluginSid   *plugin_group)
{
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (plugin_group);

  policy->_priv->plugin_groups = g_list_append (policy->_priv->plugin_groups, plugin_group);
}

/*
 * 
 */
void
sim_policy_remove_plugin_group (SimPolicy        *policy,
																Plugin_PluginSid   *plugin_group)
{
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (plugin_group);

  policy->_priv->plugin_groups = g_list_remove (policy->_priv->plugin_groups, plugin_group);
}

/*
 *
 */
GList*
sim_policy_get_plugin_groups (SimPolicy* policy)
{
  g_return_val_if_fail (SIM_IS_POLICY (policy), NULL);

  return policy->_priv->plugin_groups;
}

/*
 *
 *
 *
 */
void
sim_policy_free_plugin_groups (SimPolicy* policy)
{
  GList   *list;
  GList   *list2;

  g_return_if_fail (SIM_IS_POLICY (policy));

  list = policy->_priv->plugin_groups;
  while (list)
  {
    Plugin_PluginSid *plugin_group = (Plugin_PluginSid *) list->data;
		list2 = plugin_group->plugin_sid;
		while (list2)
		{
			gint *plugin_sid = (gint *) list2->data;
			g_free (plugin_sid);
			list2 = list2->next;
		}			
    g_free (plugin_group);
    list = list->next;
  }
  g_list_free (policy->_priv->plugin_groups);
}

/*
 * Risks
 */

/**
 * sim_policy_append_risk:
 * @policy: a #SimPolicy
 *
 */
void
sim_policy_append_risk (SimPolicy *policy,
                        gint       priority,
                        gint       reliability)
{
  SimPolicyRisk *risk;

  g_return_if_fail (SIM_IS_POLICY (policy));

  risk = g_new0 (SimPolicyRisk, 1);
  risk->priority = priority;
  risk->reliability = reliability;

  policy->_priv->risks = g_list_append (policy->_priv->risks, risk);
}

/**
 * sim_policy_match:
 *
 *
 */
gboolean
sim_policy_match (SimPolicy       * policy,
                  SimEvent        * event,
                  SimPortProtocol * src_pp,
                  SimPortProtocol * dst_pp)
{
  GList     *list;
  gboolean  match = FALSE;

  g_return_val_if_fail (SIM_IS_POLICY (policy), FALSE);

  ossim_debug ( "sim_policy_match, Policy ID: %s", sim_uuid_get_string (policy->_priv->id));
#ifdef POLICY_DEBUG
  sim_policy_debug_print (policy);
#endif

  // Date.
  time_t cur_epoch_time = time (NULL);
  struct tm cur_time;
  cur_epoch_time += sim_timezone_get_offset (policy->_priv->timezone, cur_epoch_time);
  gmtime_r (&cur_epoch_time, &cur_time);
  gint wday = cur_time.tm_wday == 0 ? 7 : cur_time.tm_wday;
  gint month = cur_time.tm_mon + 1;
  gint mday = cur_time.tm_mday;

  // Compare week days.
  if ((policy->_priv->week_day_start != TIME_WILDCARD) && (policy->_priv->week_day_end != TIME_WILDCARD))
  {
    // If day_start is ahead of day_end...
    if (policy->_priv->week_day_start > policy->_priv->week_day_end)
    {
      if (!((((int) policy->_priv->week_day_start <= wday) &&
             ((int) policy->_priv->week_day_end < wday)) ||
            (((int) policy->_priv->week_day_end >= wday) &&
             ((int) policy->_priv->week_day_start > wday))))
      {
        return (FALSE);
      }
    }
    else
      if (!(((int) policy->_priv->week_day_start <= wday) &&
            ((int) policy->_priv->week_day_end >= wday)))
      {
        return (FALSE);
      }
  }

  // Compare months.
  guint month_start = policy->_priv->month_start ? policy->_priv->month_start : (guint)month;
  guint month_end = policy->_priv->month_end ? policy->_priv->month_end : (guint)month;

  if (month_start > month_end)
  {
    if (!((((int) month_start <= month) &&
           ((int) month_end < month)) ||
          (((int) month_end >= month) &&
           ((int) month_start > month))))
    {
      return (FALSE);
    }
  }
  else
    if (!(((int) month_start <= month) &&
          ((int) month_end >= month)))
    {
      return (FALSE);
    }

  // Compare month days.
  if ((policy->_priv->month_day_start != TIME_WILDCARD) && (policy->_priv->month_day_end != TIME_WILDCARD))
  {
    guint month_day_start = sim_parse_month_day (policy->_priv->month_day_start, month_start, cur_time.tm_year + 1900);
    guint month_day_end = sim_parse_month_day (policy->_priv->month_day_end, month_end, cur_time.tm_year + 1900);

    // If day_start is ahead of day_end...
    if (month_day_start > month_day_end)
    {
      if (!((((int) month_day_start <= mday) &&
             ((int) month_day_end < mday)) ||
            (((int) month_day_end >= mday) &&
             ((int) month_day_start > mday))))
      {
        return (FALSE);
      }
    }
    else
      if (!(((int) month_day_start <= mday) &&
            ((int) month_day_end >= mday)))
      {
        return (FALSE);
      }
  }

  guint cur_min = (cur_time.tm_hour * 60) + cur_time.tm_min;

  // If day_start is ahead of day_end...
  if (policy->_priv->minute_start > policy->_priv->minute_end)
  {
    if (!((((int) policy->_priv->minute_start <= cur_min) &&
           ((int) policy->_priv->minute_end < cur_min)) ||
          (((int) policy->_priv->minute_end >= cur_min) &&
           ((int) policy->_priv->minute_start > cur_min))))
    {
      return (FALSE);
    }
  }
  else
    if (!(((int) policy->_priv->minute_start <= cur_min) &&
          ((int) policy->_priv->minute_end >= cur_min)))
    {
      return (FALSE);
    }


#ifdef POLICY_DEBUG
  ossim_debug ( "DATE OK");
#endif

  /* Find source net*/
  if (policy->_priv->src)
    match = sim_network_has_inet (policy->_priv->src, event->src_ia);

  if(!match)
  {
    /* Find source host*/
    if ((policy->_priv->src_hosts == NULL) && (policy->_priv->src == NULL))
      match = TRUE;
    else if (event->src_id && policy->_priv->src_hosts && g_hash_table_lookup(policy->_priv->src_hosts, event->src_id))
      match = TRUE;
  }

#ifdef POLICY_DEBUG
  gchar *ip_temp = sim_inet_get_canonical_name (event->src_ia);
  if (match)
    ossim_debug ("       src_ip: %s OK!; Match with policy: %s", ip_temp, sim_uuid_get_string (policy->_priv->id));
  else
    ossim_debug ("       src_ip: %s Doesn't match with any", ip_temp);
  g_free (ip_temp);
#endif

  if (!match)
    return FALSE;

  match = FALSE;

  /* Find destination ip */
  if (policy->_priv->dst)
    match = sim_network_has_inet (policy->_priv->dst, event->dst_ia);

  if(!match)
  {
    /* Find destination host*/
    if ((policy->_priv->dst_hosts == NULL) && (policy->_priv->dst == NULL))
      match = TRUE;
    else if(event->dst_id && policy->_priv->dst_hosts && g_hash_table_lookup(policy->_priv->dst_hosts, event->dst_id))
      match  =TRUE;
  }

#ifdef POLICY_DEBUG
  ip_temp = sim_inet_get_canonical_name (event->dst_ia);
  if (match)
    ossim_debug ("       dst_ip: %s OK!; Match with policy: %s", ip_temp, sim_uuid_get_string (policy->_priv->id));
  else
    ossim_debug ("       dst_ip: %s Doesn't match with any", ip_temp);
  g_free (ip_temp);
#endif

  if (!match)
    return FALSE;

  /* Find port & protocol */
  list = policy->_priv->ports_src;
  if (list)
    match = FALSE;

  while (list)
  {
    SimPortProtocol *cmp = (SimPortProtocol *) list->data;

    if (sim_port_protocol_equal (cmp, src_pp))
    {
      match = TRUE;
#ifdef POLICY_DEBUG
      ossim_debug ( "       port MATCH");
#endif
      break;
    }
    list = list->next;
  }
  if (!match)
  {
#ifdef POLICY_DEBUG
    ossim_debug ( "       port NO MATCH");
#endif
    return FALSE;
  }

  list = policy->_priv->ports_dst;
  if (list)
    match = FALSE;

  while (list)
  {
    SimPortProtocol *cmp = (SimPortProtocol *) list->data;

    if (sim_port_protocol_equal (cmp, dst_pp))
    {
      match = TRUE;
#ifdef POLICY_DEBUG
      ossim_debug ( "       port MATCH");
#endif
      break;
    }
    list = list->next;
  }
  if (!match)
  {
#ifdef POLICY_DEBUG
    ossim_debug ( "       port NO MATCH");
#endif
    return FALSE;
  }

  /* Find sensor */
  list = policy->_priv->sensors;
  if (list)
    match = FALSE;

  gchar *ip_sensor = sim_inet_get_canonical_name (event->sensor);

  while (list)
  {
    gchar *cmp = (gchar *) list->data;

    ossim_debug ( "       event sensor: -%s-", ip_sensor);
    ossim_debug ( "       policy sensor:-%s-", cmp);

    if (!strcmp (ip_sensor, cmp) || !strcmp (cmp, "0")) //if match
    {
      match = TRUE;
      ossim_debug ( "       sensor MATCH");
      break;
    }

    list = list->next;
  }

  g_free (ip_sensor);

  if (!match)
  {
#ifdef POLICY_DEBUG
    ossim_debug ( "       sensor NOT MATCH");
#endif
    return FALSE;
  }

  /* Find plugin_groups */
  list = policy->_priv->plugin_groups;
  match = FALSE;

  if (!list)
    match = TRUE;

  while (list)
  {
    Plugin_PluginSid *plugin_group = (Plugin_PluginSid *) list->data;

    if (event->plugin_id == plugin_group->plugin_id) //if match
    {
      GList *list2 = plugin_group->plugin_sid;
  	  while (list2)
      {
        gint *aux_plugin_sid = (gint *) list2->data;
        if ((*aux_plugin_sid == event->plugin_sid) || (*aux_plugin_sid == 0)) //match!
        {
          match = TRUE;
          break;
        }
      	list2 = list2->next;
      }
    }
  	list = list->next;
  }
#ifdef POLICY_DEBUG
  ossim_debug ( "       plugin group %s", match ? "MATCHED" : "NOT MATCHED");
#endif

  if (!match)
    return FALSE;

  /* event risk (priority and reliability) */
  if (policy->_priv->risks)
    match = sim_policy_match_risk (policy, event);

#ifdef POLICY_DEBUG
  ossim_debug ( "       event risk %s", match ? "MATCHED" : "NOT MATCHED");
#endif

  if (!match)
    return FALSE;

  /* Reputation data */

  // Source
  list = policy->_priv->reputation_src;
  match = TRUE;
  while ((list) && (match))
  {
    SimPolicyRep * pol_rep = (SimPolicyRep *) list->data;

    // SRC Priority
    if ((pol_rep->priority != SIM_POLICY_REP_ANY_PRIO) && (pol_rep->priority != event->rep_prio_src))
    {
      match = FALSE;
      continue;
    }

    // SRC Reliability
    if ((pol_rep->reliability != SIM_POLICY_REP_ANY_REL) && (pol_rep->reliability != event->rep_rel_src))
    {
      match = FALSE;
      continue;
    }

    // SRC activities
    if ((event->rep_act_src != NULL) && (!g_hash_table_lookup (event->rep_act_src, GINT_TO_POINTER (pol_rep->activity))))
    {
      match = FALSE;
      continue;
    }

    list = list->next;
  }
#ifdef POLICY_DEBUG
  ossim_debug ( "       reputation src %s", match ? "MATCHED" : "NOT MATCHED");
#endif

  if (!match)
    return FALSE;

 // Destination
  list = policy->_priv->reputation_dst;
  match = TRUE;
  while ((list) && (match))
  {
    SimPolicyRep * pol_rep = (SimPolicyRep *) list->data;

    // SRC Priority
    if ((pol_rep->priority != SIM_POLICY_REP_ANY_PRIO) && (pol_rep->priority != event->rep_prio_dst))
    {
      match = FALSE;
      continue;
    }

    // SRC Reliability
    if ((pol_rep->reliability != SIM_POLICY_REP_ANY_REL) && (pol_rep->reliability != event->rep_rel_dst))
    {
      match = FALSE;
      continue;
    }

    // SRC activities
    if ((event->rep_act_dst != NULL) && (!g_hash_table_lookup (event->rep_act_dst, GINT_TO_POINTER (pol_rep->activity))))
    {
      match = FALSE;
      continue;
    }

    list = list->next;
  }
#ifdef POLICY_DEBUG
  ossim_debug ( "       reputation dst %s", match ? "MATCHED" : "NOT MATCHED");
#endif

  if (!match)
    return FALSE;

  // Taxonomy.
  if ((policy->_priv->taxonomy) && (g_hash_table_size (policy->_priv->taxonomy) > 0))
  {
    GHashTable * products = policy->_priv->taxonomy;
    GHashTable * categories, * subcategories;

    // Check first exact matches, then for ANY.
    if ((categories = g_hash_table_lookup (products, GINT_TO_POINTER (event->tax_product))) ||
        (categories = g_hash_table_lookup (products, GINT_TO_POINTER (0))))
    {
      if ((subcategories = g_hash_table_lookup (categories, GINT_TO_POINTER (event->tax_category))) ||
          (subcategories = g_hash_table_lookup (categories, GINT_TO_POINTER (0))))
      {
        if ((!g_hash_table_lookup (subcategories, GINT_TO_POINTER (event->tax_subcategory))) &&
             (!g_hash_table_lookup (subcategories, GINT_TO_POINTER (0))))
        {
          return (FALSE);
        }
      }
      else
      {
        return (FALSE);
      }
    }
    else
    {
      return (FALSE);
    }
  }

  return (match);
}

/**
 * sim_policy_match_risk:
 * @policy: a #SimPolicy
 * @event: #SimEvent to match
 *
 * Returns: %TRUE if event priority and reliability matches
 * with policy, %FALSE otherwise
 */
gboolean
sim_policy_match_risk (SimPolicy *policy,
                       SimEvent  *event)
{
  GList *list;

  list = policy->_priv->risks;
  while (list)
  {
    SimPolicyRisk *risk = (SimPolicyRisk *)list->data;

    /* Match if the event has the same priority and reliability (SIM_POLICY_RISK_ANY matches always) */
    if (((risk->priority == SIM_POLICY_RISK_ANY) || (event->priority == risk->priority)) &&
        ((risk->reliability == SIM_POLICY_RISK_ANY) || (event->reliability == risk->reliability)))
    {
      return TRUE;
    }

    list = g_list_next (list);
  }

  return FALSE;
}

void sim_policy_debug_print (SimPolicy  *policy)
{
  GList *list;

  ossim_debug ( "sim_policy_debug_print       : policy %p",policy);
  ossim_debug ( "                               id: %s", sim_uuid_get_string (policy->_priv->id));
  ossim_debug ( "                               context: %s", sim_uuid_get_string (policy->_priv->context_id));
  ossim_debug ( "                               description: %s",policy->_priv->description);
  ossim_debug ( "                               minute_start:    %d",policy->_priv->minute_start);
  ossim_debug ( "                               minute_end:      %d",policy->_priv->minute_end);
//FIXME: not _priv members
//  ossim_debug ( "                               hour_start:      %d",policy->_priv->hour_start);
//  ossim_debug ( "                               hour_end:        %d",policy->_priv->hour_end);
  ossim_debug ( "                               week_day_start:  %d",policy->_priv->week_day_start);
  ossim_debug ( "                               week_day_end:    %d",policy->_priv->week_day_end);
  ossim_debug ( "                               month_day_start: %d",policy->_priv->month_day_start);
  ossim_debug ( "                               month_day_end:   %d",policy->_priv->month_day_end);
  ossim_debug ( "                               month_start:     %d",policy->_priv->month_start);
  ossim_debug ( "                               month_end:       %d",policy->_priv->month_end);
  ossim_debug ( "                               src:         %p",policy->_priv->src);
  ossim_debug ( "                               dst:         %p",policy->_priv->dst);
  ossim_debug ( "                               ports_src:   %p",policy->_priv->ports_src);
  ossim_debug ( "                               ports_dst:   %p",policy->_priv->ports_dst);
  ossim_debug ( "                               sensors:     %p",policy->_priv->sensors);
  ossim_debug ( "                               plugin_groups: %p",policy->_priv->plugin_groups);
  ossim_debug ( "                               priority: %d",policy->_priv->priority);

  SimRole *role = sim_policy_get_role (policy);
  if (role)
    sim_role_print (role);

  if (policy->_priv->src != NULL)
    sim_network_print (policy->_priv->src);

  if (policy->_priv->dst != NULL)
    sim_network_print (policy->_priv->dst);

  list = policy->_priv->ports_src;
  while (list)
  {
    SimPortProtocol *pp = (SimPortProtocol *) list->data;
    ossim_debug ( "                               port:        %d/%d",pp->port, pp->protocol);
    list = list->next;
  }

  list = policy->_priv->ports_dst;
  while (list)
  {
    SimPortProtocol *pp = (SimPortProtocol *) list->data;
    ossim_debug ( "                               port:        %d/%d",pp->port, pp->protocol);
    list = list->next;
  }

  list = policy->_priv->sensors;
  while (list)
  {
    gchar *s = (gchar *) list->data;
    ossim_debug ( "                               sensor:        %s",s);
    list = list->next;
  }


  list = policy->_priv->plugin_groups;
  while (list)
  {
    Plugin_PluginSid *plugin_group = (Plugin_PluginSid *) list->data;
    ossim_debug ( "                               plugin_id: %d", plugin_group->plugin_id);
    GList *list2 = plugin_group->plugin_sid;
    while (list2)
    {
      gint *plugin_sid = (gint *) list2->data;
      ossim_debug ( "                               plugin_sids: %d",*plugin_sid);
      list2 = list2->next;
    }
    list = list->next;
  }

  list = policy->_priv->risks;
  while (list)
  {
    SimPolicyRisk *risk = (SimPolicyRisk *)list->data;
    ossim_debug ( "                               priority:      %d", risk->priority);
    ossim_debug ( "                               reliability:   %d", risk->reliability);

    list = g_list_next (list);
  }
}

/*
 * Given a specific policy, it returns the role associated to it.
 */
SimRole *
sim_policy_get_role	(SimPolicy *policy)
{
  g_return_val_if_fail (SIM_IS_POLICY (policy), NULL);

  if (policy->_priv->role)
    sim_role_ref (policy->_priv->role);

  return policy->_priv->role;
}

void
sim_policy_set_role	(SimPolicy *policy,
										 SimRole   *role)
{
  g_return_if_fail (SIM_IS_POLICY (policy));

  if (policy->_priv->role)
    sim_role_unref (policy->_priv->role);

	policy->_priv->role = role;
}

gboolean
sim_policy_has_role (SimPolicy *policy)
{
  g_return_val_if_fail (SIM_IS_POLICY (policy), FALSE);

  return (policy->_priv->role != NULL);
}

/**
 * sim_policy_add_reputation_src:
 *
 *
 */
void
sim_policy_add_reputation_src (SimPolicy     * policy,
                               SimPolicyRep  * pol_rep)
{
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (pol_rep);

  policy->_priv->reputation_src = g_list_prepend (policy->_priv->reputation_src, pol_rep);
}

/**
 * sim_policy_add_reputation_dst:
 *
 *
 */
void
sim_policy_add_reputation_dst (SimPolicy     * policy,
                               SimPolicyRep  * pol_rep)
{
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (pol_rep);

  policy->_priv->reputation_dst = g_list_prepend (policy->_priv->reputation_dst, pol_rep);
}

/**
 * sim_policy_free_reputation_src:
 *
 *
 */
void
sim_policy_free_reputation_src (SimPolicy* policy)
{
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_list_foreach (policy->_priv->reputation_src, (GFunc)g_free, NULL);
  g_list_free (policy->_priv->reputation_src);

  return;
}

/**
 * sim_policy_free_reputation_dst:
 *
 *
 */
void
sim_policy_free_reputation_dst (SimPolicy* policy)
{
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_list_foreach (policy->_priv->reputation_dst, (GFunc)g_free, NULL);
  g_list_free (policy->_priv->reputation_dst);

  return;
}

/**
 * sim_policy_set_taxonomy:
 *
 */
void
sim_policy_set_taxonomy (SimPolicy * policy,
                         GHashTable * taxonomy)
{
  g_return_if_fail (SIM_IS_POLICY (policy));
  policy->_priv->taxonomy = taxonomy;
  return;
}

/**
 *  sim_policy_add_src_host:
 *  @policy: SimPolicy object
 *  @src: SimUuid object
 *
 *  Adds the SimUuid object into the src hosts hash table.
 *
 */
void
sim_policy_add_src_host (SimPolicy *policy,
								         SimUuid   *src) 
{
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (SIM_IS_UUID (src));

  if (policy->_priv->src_hosts == NULL)
    policy->_priv->src_hosts = g_hash_table_new_full (sim_uuid_hash, sim_uuid_equal, g_object_unref, NULL);

  g_hash_table_insert (policy->_priv->src_hosts, src, GINT_TO_POINTER(GENERIC_VALUE));
}

/**
 *  sim_policy_add_dst_host:
 *  @policy: SimPolicy object
 *  @dst: SimUuid object
 *
 *  Adds the SimUuid object into the dst hosts hash table.
 *
 */
void
sim_policy_add_dst_host (SimPolicy *policy,
								         SimUuid   *dst) 
{
  g_return_if_fail (SIM_IS_POLICY (policy));
  g_return_if_fail (SIM_IS_UUID (dst));

  if (policy->_priv->dst_hosts == NULL)
    policy->_priv->dst_hosts = g_hash_table_new_full (sim_uuid_hash, sim_uuid_equal, g_object_unref, NULL);

  g_hash_table_insert (policy->_priv->dst_hosts, dst, GINT_TO_POINTER(GENERIC_VALUE));
}


// vim: set tabstop=2:
