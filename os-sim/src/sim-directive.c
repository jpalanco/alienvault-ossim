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

#include "sim-directive.h"

#include <gnet.h>
#include <time.h>
#include <uuid/uuid.h>

#include "sim-rule.h"
#include "sim-inet.h"
#include "sim-xml-directive.h"
#include "sim-db-command.h"
#include "sim-alarm-stats.h"
#include "os-sim.h"


struct _SimDirectivePrivate
{
  gint           id;
  gchar         *name;

  SimUuid       *backlog_id;
  SimEngine     *engine;

  gint           priority;

  gboolean       matched;        //this is filled in the last level of the directive
  gboolean       deleted;
  gboolean       loaded_from_file;

  time_t         time_out;
  gint64         time;
  time_t         time_last;
  time_t         first_event;
  time_t         last_event;

  GNode         *rule_root;      //this is tested in sim_rule_match_by_event. It's a SimRule.
  GNode         *rule_curr;

  GList         *groups;

  SimTimetable  *timetable;
  gchar         *timetable_name;

  gboolean       groupalarm;
  gboolean       group_alarm_by[SIM_DIRECTIVE_GROUP_ALARM_BY_LAST];
  gboolean       group_alarm_store_backlog;
  gint           group_alarm_timeout;

  GHashTable    *backlog_refs;   // Backlogs Array References

  SimAlarmStats *alarm_stats;
};

static gpointer parent_class = NULL;

extern SimMain ossim;

static void sim_directive_db_delete_backlog_by_id_ul (SimUuid *backlog_id);
static void sim_directive_set_rule_var_host_id (SimUuid    *host_id,
                                                SimRule    *rule,
                                                SimRuleVar *var);
static void sim_directive_set_rule_var_inet (SimInet    *inet,
                                             SimRule    *rule,
                                             SimRuleVar *var);

/* GType Functions */

static void
sim_directive_impl_dispose (GObject  *gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void
sim_directive_impl_finalize (GObject  *gobject)
{
  SimDirective *directive = SIM_DIRECTIVE (gobject);

  ossim_debug ("sim_directive_impl_finalize: Id %d, Name %s, BacklogId %s, Match %d",
               directive->_priv->id, directive->_priv->name,
               sim_uuid_get_string (directive->_priv->backlog_id), directive->_priv->matched);

  if (directive->_priv->name)
    g_free (directive->_priv->name);

  if (directive->_priv->backlog_id)
    g_object_unref (directive->_priv->backlog_id);

  if (directive->_priv->engine)
    g_object_unref (directive->_priv->engine);

  sim_directive_node_data_destroy (directive->_priv->rule_root);
  g_node_destroy (directive->_priv->rule_root);

  if (directive->_priv->timetable)
    g_object_unref (G_OBJECT (directive->_priv->timetable));

  if (directive->_priv->timetable_name)
    g_free (directive->_priv->timetable_name);


  if (directive->_priv->backlog_refs)
    g_hash_table_unref (directive->_priv->backlog_refs);

  if (directive->_priv->alarm_stats)
    sim_alarm_stats_unref (directive->_priv->alarm_stats);

  g_free (directive->_priv);
  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_directive_class_init (SimDirectiveClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_ref (G_TYPE_OBJECT);

  object_class->dispose = sim_directive_impl_dispose;
  object_class->finalize = sim_directive_impl_finalize;
}

static void
sim_directive_instance_init (SimDirective *directive)
{
  directive->_priv = g_new0 (SimDirectivePrivate, 1);

  directive->_priv->id = 0;
  directive->_priv->name = NULL;

  directive->_priv->backlog_id = NULL;
  directive->_priv->engine = NULL;

  directive->_priv->time_out = 300;
  directive->_priv->time = 0;
  directive->_priv->time_last = 0;
  directive->_priv->first_event = 2147472000;  // 01-19-2038
  directive->_priv->last_event = 0;

  directive->_priv->priority = 0;
  directive->_priv->matched = FALSE;
  directive->_priv->deleted = FALSE;
  directive->_priv->loaded_from_file = FALSE;

  directive->_priv->rule_root = NULL;
  directive->_priv->rule_curr = NULL;

  directive->_priv->groups = NULL;
  directive->_priv->timetable = NULL;
  directive->_priv->groupalarm = FALSE;

  directive->_priv->backlog_refs = g_hash_table_new (g_direct_hash, g_direct_equal);

  directive->_priv->alarm_stats = sim_alarm_stats_new ();

  return;
}

/* Public Methods */

GType
sim_directive_get_type (void)
{
  static GType object_type = 0;
 
  if (!object_type)
  {
    static const GTypeInfo type_info = {
              sizeof (SimDirectiveClass),
              NULL,
              NULL,
              (GClassInitFunc) sim_directive_class_init,
              NULL,
              NULL,                       /* class data */
              sizeof (SimDirective),
              0,                          /* number of pre-allocs */
              (GInstanceInitFunc) sim_directive_instance_init,
              NULL                        /* value table */
    };
    
    g_type_init ();
                                                                                                                             
    object_type = g_type_register_static (G_TYPE_OBJECT, "SimDirective", &type_info, 0);
  }

  return object_type;
}

/*
 *
 *
 *
 *
 */
SimDirective*
sim_directive_new (void)
{
  SimDirective *directive = NULL;

  directive = SIM_DIRECTIVE (g_object_new (SIM_TYPE_DIRECTIVE, NULL));

  directive->_priv->loaded_from_file = TRUE; // Only TRUE on directives loaded from xml

  return directive;
}

/*
 *
 *
 *
 *
 */
gint
sim_directive_get_id (SimDirective   *directive)
{
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), 0);

  return directive->_priv->id;
}

/*
 *
 *
 *
 *
 */
void sim_directive_set_id (SimDirective   *directive,
			   gint            id)
{
  g_return_if_fail (SIM_IS_DIRECTIVE (directive));
  g_return_if_fail (id > 0);

  directive->_priv->id = id;
}

/**
 * sim_directive_compare_id:
 * @a: a pointer to a directive.
 * @b: a pointer to another directive.
 *
 * A #GCompareFunc to compare two directives by id.
 */
gint
sim_directive_compare_id (gconstpointer a, gconstpointer b)
{
  if (!a || !b)
    return (-1);

  SimDirective * d1 = (SimDirective *)a;
  SimDirective * d2 = (SimDirective *)b;

  return (d1->_priv->id - d2->_priv->id);
}

/**
 * sim_directive_get_engine:
 * @directive: a #SimDirective object.
 *
 * Returns the #SimEngine of this @directive.
 */
SimEngine *
sim_directive_get_engine (SimDirective *directive)
{
  return (directive->_priv->engine);
}

/*
 *
 *
 *
 *
 */
void
sim_directive_append_group (SimDirective	*directive,
			    SimDirectiveGroup	*group)
{
  g_return_if_fail (SIM_IS_DIRECTIVE (directive));
  g_return_if_fail (SIM_IS_DIRECTIVE_GROUP (group));

  directive->_priv->groups = g_list_append (directive->_priv->groups, group);
}

/*
 *
 *
 *
 *
 */
void
sim_directive_remove_group (SimDirective	*directive,
			    SimDirectiveGroup	*group)
{
  g_return_if_fail (SIM_IS_DIRECTIVE (directive));
  g_return_if_fail (SIM_IS_DIRECTIVE_GROUP (group));

  directive->_priv->groups = g_list_remove (directive->_priv->groups, group);
}

/*
 *
 *
 *
 *
 */
void
sim_directive_free_groups (SimDirective		*directive)
{
  GList	*list;

  g_return_if_fail (SIM_IS_DIRECTIVE (directive));

  list = directive->_priv->groups;
  while (list)
    {
      SimDirectiveGroup	*group = (SimDirectiveGroup *) list->data;
      g_object_unref (group);
      list = list->next;
    }
  g_list_free (directive->_priv->groups);
}

/*
 *
 *
 *
 *
 */
GList*
sim_directive_get_groups (SimDirective		*directive)
{
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), NULL);

  return directive->_priv->groups;
}

/*
 *
 *
 *
 *
 */
gboolean
sim_directive_has_group	(SimDirective		*directive,
			 SimDirectiveGroup	*group)
{
  GList	*list;

  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), FALSE);
  g_return_val_if_fail (SIM_IS_DIRECTIVE_GROUP (group), FALSE);

  list = directive->_priv->groups;
  while (list)
    {
      SimDirectiveGroup *cmp = (SimDirectiveGroup *) list->data;

      if (cmp == group)
	return TRUE;

      list = list->next;
    }

  return FALSE;
}

/**
 * sim_directive_get_backlog_id:
 * @backlog: a #SimDirective.
 *
 * Returns a pointer to this backlog uuid_t.
 */
SimUuid *
sim_directive_get_backlog_id (SimDirective * backlog)
{
  g_return_val_if_fail (SIM_IS_DIRECTIVE (backlog), NULL);

  return (backlog->_priv->backlog_id);
}

/**
 * sim_directive_set_backlog_id:
 * @backlog: a #SimDirective.
 * @backlog_id: a #uuid_t.
 *
 * Sets this @backlog unique id.
 */
void
sim_directive_set_backlog_id (SimDirective * backlog,
                              SimUuid      * backlog_id)
{
  g_return_if_fail (SIM_IS_DIRECTIVE (backlog));
  g_return_if_fail (SIM_IS_UUID (backlog_id));

  backlog->_priv->backlog_id = g_object_ref (backlog_id);
}

/**
 * sim_directive_get_backlog_id_str:
 * @backlog: a #SimDirective.
 *
 * Returns a pointer to this backlog uuid_t.
 */
const gchar *
sim_directive_get_backlog_id_str (SimDirective * backlog)
{
  g_return_val_if_fail (SIM_IS_DIRECTIVE (backlog), NULL);

  if (backlog->_priv->backlog_id)
    return sim_uuid_get_string (backlog->_priv->backlog_id);
  else
    return "";
}


/*
 *
 *
 *
 *
 */
gchar*
sim_directive_get_name (SimDirective   *directive)
{
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), NULL);

  return directive->_priv->name;
}

/*
 *
 *
 *
 *
 */
void sim_directive_set_name (SimDirective   *directive,
			     const gchar    *name)
{
  g_return_if_fail (SIM_IS_DIRECTIVE (directive));
  g_return_if_fail (name);

  if (directive->_priv->name)
    g_free (directive->_priv->name);

  directive->_priv->name = g_strdup (name);
}

/*
 *
 *
 *
 *
 */
gint
sim_directive_get_priority (SimDirective   *directive)
{
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), 0);

  if (directive->_priv->priority <= 0)
    return 0;
  if (directive->_priv->priority >= 5)
    return 5;

  return directive->_priv->priority;
}

/*
 *
 *
 *
 *
 */
void sim_directive_set_priority (SimDirective   *directive,
				 gint            priority)
{
  g_return_if_fail (SIM_IS_DIRECTIVE (directive));

  if (priority < 0)
    directive->_priv->priority = 0;
  else if (priority > 5)
    directive->_priv->priority = 5;
  else
    directive->_priv->priority = priority;
}

/*
 *
 *
 *
 *
 */
time_t
sim_directive_get_time_out (SimDirective   *directive)
{
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), 0);

  return directive->_priv->time_out;
}

/*
 *
 *
 *
 *
 */
void 
sim_directive_set_time_out (SimDirective   *directive,
			    time_t           time_out)
{
  g_return_if_fail (SIM_IS_DIRECTIVE (directive));
  g_return_if_fail (time_out >= 0);

  directive->_priv->time_out = time_out;
}

/*
 *
 *
 *
 *
 */
time_t
sim_directive_get_time_last (SimDirective   *directive)
{
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), 0);

  return directive->_priv->time_last;
}

/*
 *
 *
 *
 *
 */
void sim_directive_set_time_last (SimDirective   *directive,
				  time_t           time_last)
{
  g_return_if_fail (SIM_IS_DIRECTIVE (directive));
  g_return_if_fail (time_last >= 0);

  directive->_priv->time_out = time_last;
}

/*
 *
 *
 *
 *
 */
GNode*
sim_directive_get_root_node (SimDirective  *directive)
{
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), NULL);

  return directive->_priv->rule_root;
}

/*
 *
 *
 *
 *
 */
void
sim_directive_set_root_node (SimDirective  *directive,
			     GNode         *root_node)
{
  g_return_if_fail (SIM_IS_DIRECTIVE (directive));
  g_return_if_fail (root_node);

  directive->_priv->rule_root = root_node;
}

/*
 *
 *
 *
 *
 */
GNode*
sim_directive_get_curr_node (SimDirective  *directive)
{
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), NULL);

  return directive->_priv->rule_curr;
}

/*
 *
 *
 *
 *
 */
void
sim_directive_set_curr_node (SimDirective  *directive,
			     GNode         *curr_node)
{
  g_return_if_fail (SIM_IS_DIRECTIVE (directive));
  g_return_if_fail (curr_node);

  directive->_priv->rule_curr = curr_node;
}

/*
 *
 *
 *
 *
 */
SimRule*
sim_directive_get_root_rule (SimDirective  *directive)
{
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), NULL);
  g_return_val_if_fail (directive->_priv->rule_root, NULL);
  g_return_val_if_fail (directive->_priv->rule_root->data, NULL);

  return (SimRule *) directive->_priv->rule_root->data;
}

/*
 *
 *
 *
 *
 */
SimRule*
sim_directive_get_curr_rule (SimDirective  *directive)
{
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), NULL);
  g_return_val_if_fail (directive->_priv->rule_curr, NULL);
  g_return_val_if_fail (directive->_priv->rule_curr->data, NULL);

  return (SimRule *) directive->_priv->rule_curr->data;
}

/*
 *
 *
 *
 *
 */
time_t
sim_directive_get_rule_curr_time_out_max (SimDirective  *directive)
{
  GNode  *node;
  time_t  time_out = 0;

  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), 0);
  g_return_val_if_fail (directive->_priv->rule_curr, 0);

  node = directive->_priv->rule_curr->children;

  while (node)
  {
    SimRule *rule = (SimRule *) node->data;
    time_t   time = sim_rule_get_time_out (rule);

    if (!time)
      return 0;

    if (time > time_out)
      time_out = time;

    node = node->next;
  }

  return time_out;
}

/*
 *
 *
 *
 *
 */
gint
sim_directive_get_rule_level (SimDirective   *directive)
{
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), 0);
  g_return_val_if_fail (directive->_priv->rule_curr, 0);

  return g_node_depth (directive->_priv->rule_curr);
}

/**
 * sim_directive_get_engine_id:
 * @directive: a #SimDirective
 *
 * Returns the unique id of this @directive engine.
 */
SimUuid *
sim_directive_get_engine_id (SimDirective * directive)
{
  return (sim_engine_get_id (directive->_priv->engine));
}

/*
 *
 * We want to know if the directive match with the root node directive.
 * We only check this against the root node. Here we don't check the children nodes of the directive
 *
 */
gboolean
sim_directive_match_by_event (SimDirective  *directive,
												      SimEvent      *event)
{
  gboolean match;

  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), FALSE);
  g_return_val_if_fail (!directive->_priv->matched, FALSE);
  g_return_val_if_fail (directive->_priv->rule_root, FALSE);
  g_return_val_if_fail (SIM_IS_RULE (directive->_priv->rule_root->data), FALSE);
  g_return_val_if_fail (SIM_IS_EVENT (event), FALSE);

  SimRule *rule = (SimRule *)directive->_priv->rule_root->data;

  match = sim_rule_match_by_event (rule, event);

  return match;
}

/*
 *
 * This will check if an event can match with some of the data in backlog. the backlog is in fact
 * one directive with data from events.
 *
 * Each backlog entry is a tree with all the rules from a directive (is a directive clone). And
 * each one of those rules (SimRule) contains also the data from the event that matched with the rule.
 */
gboolean
sim_directive_backlog_match_by_event (SimDirective  *directive,
																      SimEvent      *event)
{
  GNode      *node = NULL;

  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), FALSE);
  g_return_val_if_fail (!directive->_priv->matched, FALSE);
  g_return_val_if_fail (directive->_priv->rule_curr, FALSE);
  g_return_val_if_fail (SIM_IS_EVENT (event), FALSE);

  node = directive->_priv->rule_curr->children;
  while (node)		//we have to check the event against all the rule nodes from backlog 
									//(except the root_node because it's checked in sim_directive_match_by_event 
									//which is called from sim_organizer_correlation).
  {
    SimRule *rule = (SimRule *) node->data;
    
    if (sim_rule_match_by_event (rule, event))
		{
			ossim_debug ( "sim_rule_match_by_event: True");
		  time_t time_last = time (NULL);
			directive->_priv->rule_curr = node;		//each time that the event matches, the directive goes down one level to 
																						//the node that matched. next time, the event will be checked against this level
																						//FIXME: may be that there are a memory leak in the parent node? 
		  directive->_priv->time_last = time_last;
		  directive->_priv->time_out = sim_directive_get_rule_curr_time_out_max (directive);

			sim_rule_set_event_data (rule, event);		//here we asign the data from event to the fields in the rule,
																								//so each time we enter into the rule we can see the event that matched
		  sim_rule_set_time_last (rule, time_last);

		  if (!G_NODE_IS_LEAF (node))
	    {
	      GNode *children = node->children;
	      while (children)
				{
				  SimRule *rule_child = (SimRule *) children->data;

				  sim_rule_set_time_last (rule_child, time_last);

				  sim_directive_set_rule_vars (directive, children);
				  children = children->next;
				}
			}
		  else
		  {
			  directive->_priv->matched = TRUE;
		  }

		  return TRUE;
		}

	  node = node->next;
	}

  return FALSE;
}

/*
 * Check all the nodes (rules) in the directive to see if.......
 *
 *
 */
gboolean
sim_directive_backlog_match_by_not (SimDirective  *directive)
{
  GNode      *node = NULL;
  GNode      *children = NULL;

  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), FALSE);
  g_return_val_if_fail (!directive->_priv->matched, FALSE);
  g_return_val_if_fail (directive->_priv->rule_curr, FALSE);

  node = directive->_priv->rule_curr->children;

  while (node) 
  {
    SimRule *rule = (SimRule *) node->data;
		//if the rule is timeouted &&       
    if ((sim_rule_is_time_out (rule)) && (sim_rule_get_not (rule)) && (!!!sim_rule_is_not_invalid (rule))) 
		{
		  time_t time_last = time (NULL);
	  	directive->_priv->rule_curr = node;
		  directive->_priv->time_last = time_last;
		  directive->_priv->time_out = sim_directive_get_rule_curr_time_out_max (directive);

	  	sim_rule_set_not_data (rule);

		  if (!G_NODE_IS_LEAF (node)) //this isn't the last node, it has some children
	    {
	      children = node->children;
	      while (children)
				{
		  		SimRule *rule_child = (SimRule *) children->data;

				  sim_rule_set_time_last (rule_child, time_last);

				  sim_directive_set_rule_vars (directive, children);
				  children = children->next;
				}
	    }
	  	else //last node!
	    {
	      directive->_priv->matched = TRUE;
	    }
	  
	  	return TRUE;
		}
    node = node->next;
  }

  return FALSE;
}

/*
 * backlog & directives is almost the same: backlog is where directives are stored and filled with data from events.
 * 
 * The "node" function parameter is a children node. We need to add to that node the src_ip, port, etc from the
 * node whose level is referenced. ie. if "node" parameter is the children2 in root_node->children1->children2, and we
 * have something like 1:PLUGIN_SID in children2, we have to add the plugin_sid from root_node to children2
 *
 */
void
sim_directive_set_rule_vars (SimDirective     *directive,
												     GNode            *node)
{
  SimRule         *rule;
  SimRule         *rule_up;
  GNode           *node_up;
  GList           *vars;
  SimInet         *ia = NULL;
  SimUuid         *host_id = NULL;
  SimInet         *sensor;
  SimSensor      * sensor_obj;
  gint             port;
  gint             id;
  gint             sid;
  SimProtocolType  protocol;
  SimContext      *entity;
	gchar           *aux = NULL;

  g_return_if_fail (SIM_IS_DIRECTIVE (directive));
  g_return_if_fail (node);
  g_return_if_fail (g_node_depth (node) > 1);

  rule = (SimRule *) node->data;
  vars = sim_rule_get_vars (rule);	

  while (vars)	//just in case there are vars (ie. 1:PLUGIN_SID or 2:SRC_IP) in the rule.
  {
    SimRuleVar *var = (SimRuleVar *) vars->data;

		//now we need to know the node to which is referencing the level in the SimRuleVar. 
		node_up = sim_directive_get_node_branch_by_level (directive, node, var->level); 
    if (!node_up)
		{
		  vars = vars->next;
			continue;
		}

		rule_up = (SimRule *) node_up->data;

    ossim_debug ( "sim_directive_set_rule_vars: rule name: %s",sim_rule_get_name(rule));					
		ossim_debug ( "sim_directive_set_rule_vars: type: %d",var->type);
		ossim_debug ( "sim_directive_set_rule_vars: attr: %d",var->attr);
		ossim_debug ( "sim_directive_set_rule_vars: negated: %d",var->negated);

		//"node" function parameter is a children node. We need to add to that node the src_ip, port, etc from the
		//node whose level is referenced. ie. if this is the children2 in root_node->children1->children2, and we
		//have something like 1:PLUGIN_SID in children2, we have to add the plugin_sid from root_node to children2
		switch (var->type)
		{
      case SIM_RULE_VAR_SRC_IA:
        host_id = sim_rule_get_src_host_id (rule_up);

        // Monitor rules work only with IP addresses, as they trigger a command that's sent to the agent.
        if (host_id && (rule->type != SIM_EVENT_TYPE_MONITOR))
        {
          sim_directive_set_rule_var_host_id (host_id, rule, var);
        }
        else
        {
          ia = sim_rule_get_src_ia (rule_up);
          sim_directive_set_rule_var_inet (ia, rule, var);
        }
        break;

      case SIM_RULE_VAR_DST_IA:
        host_id = sim_rule_get_dst_host_id (rule_up);
        if (host_id  && (rule->type != SIM_EVENT_TYPE_MONITOR))
        {
          sim_directive_set_rule_var_host_id (host_id, rule, var);
        }
        else
        {
          ia = sim_rule_get_dst_ia (rule_up);
          sim_directive_set_rule_var_inet (ia, rule, var);
        }
        break;

			case SIM_RULE_VAR_SRC_PORT:
						port = sim_rule_get_src_port (rule_up);

						switch (var->attr)
						{
							case SIM_RULE_VAR_SRC_PORT:
	                  if (var->negated)																			
								sim_rule_add_src_port_not (rule, port);
										else
								sim_rule_add_src_port (rule, port);
										break;
							case SIM_RULE_VAR_DST_PORT:
                    if (var->negated)
								sim_rule_add_dst_port_not (rule, port);
										else											
								sim_rule_add_dst_port (rule, port);
										break;
							default:
										break;
						}
						break;
	
			case SIM_RULE_VAR_DST_PORT:
						port = sim_rule_get_dst_port (rule_up);
						
/*-------------
ossim_debug ( "sim_directive_set_rule_var1");
sim_rule_print(rule);
						ossim_debug ( "sim_directive_set_rule_vars: negated: %d",var->negated);
-------------*/
			
						switch (var->attr)
						{
							case SIM_RULE_VAR_SRC_PORT:
                    if (var->negated)
								sim_rule_add_src_port_not (rule, port);
										else
								sim_rule_add_src_port (rule, port);
										break;
							case SIM_RULE_VAR_DST_PORT:
                    if (var->negated)
								sim_rule_add_dst_port_not (rule, port);
										else											
								sim_rule_add_dst_port (rule, port);
										break;
							default:
										break;
						}
						break;

/*-------------
ossim_debug ( "sim_directive_set_rule_var2");
sim_rule_print(rule);
-------------*/
			case SIM_RULE_VAR_PLUGIN_ID:
				id = sim_rule_get_plugin_id (rule_up);
        if (var->negated)
					sim_rule_add_plugin_id_not (rule, id);
				else
				  sim_rule_add_plugin_id (rule, id);
				break;

			case SIM_RULE_VAR_PLUGIN_SID:
						sid = sim_rule_get_plugin_sid (rule_up);
            if (var->negated)
						sim_rule_add_plugin_sid_not (rule, sid);
						else
						sim_rule_add_plugin_sid (rule, sid);
						break;

			case SIM_RULE_VAR_PROTOCOL:
						protocol = sim_rule_get_protocol (rule_up);
            if (var->negated)
						sim_rule_add_protocol_not (rule, protocol);
						else
						sim_rule_add_protocol (rule, protocol);
						break;

      case SIM_RULE_VAR_SENSOR:
            sensor = sim_rule_get_sensor (rule_up);
            aux = sim_inet_get_canonical_name (sensor);
            sensor_obj = sim_sensor_new_from_hostname (aux);

            if (sensor_obj)
            {
              if (var->negated)
                sim_rule_add_sensor_not (rule, sensor_obj);
              else
                sim_rule_add_sensor (rule, sensor_obj);
            }
            else
              g_message ("Invalid sensor address for ip %s", aux ? aux : "(null)");

            g_free (aux);
            break;

      case SIM_RULE_VAR_PRODUCT:
        if (var->negated)
          sim_rule_add_product_not (rule, sim_rule_get_product (rule_up));
        else
          sim_rule_add_product (rule, sim_rule_get_product (rule_up));
        break;

      case SIM_RULE_VAR_ENTITY:
        entity = sim_rule_get_entity (rule_up);
        if (var->negated)
          sim_rule_add_entity_not (rule, entity);
        else
          sim_rule_add_entity (rule, entity);
        break;

      case SIM_RULE_VAR_CATEGORY:
        if (var->negated)
          sim_rule_add_category_not (rule, sim_rule_get_category (rule_up));
        else
          sim_rule_add_category (rule, sim_rule_get_category (rule_up));
        break;

      case SIM_RULE_VAR_SUBCATEGORY:
        if (var->negated)
          sim_rule_add_subcategory_not (rule, sim_rule_get_category (rule_up));
        else
          sim_rule_add_subcategory (rule, sim_rule_get_category (rule_up));
        break;

			case SIM_RULE_VAR_FILENAME:
            aux = g_strdup (sim_rule_get_filename (rule_up));												
            if (var->negated)
							sim_rule_append_generic_text_not (rule, aux, SIM_RULE_VAR_FILENAME);
						else
							sim_rule_append_generic_text (rule, aux, SIM_RULE_VAR_FILENAME);
            break;
						
			case SIM_RULE_VAR_USERNAME:
            aux = g_strdup (sim_rule_get_username (rule_up));												
            if (var->negated)
							sim_rule_append_generic_text_not (rule, aux, SIM_RULE_VAR_USERNAME);
						else
							sim_rule_append_generic_text (rule, aux, SIM_RULE_VAR_USERNAME);
            break;

			case SIM_RULE_VAR_PASSWORD:
            aux = g_strdup (sim_rule_get_password (rule_up));												
            if (var->negated)
							sim_rule_append_generic_text_not (rule, aux, SIM_RULE_VAR_PASSWORD);
						else
							sim_rule_append_generic_text (rule, aux, SIM_RULE_VAR_PASSWORD);
            break;

			case SIM_RULE_VAR_USERDATA1:
            aux = g_strdup (sim_rule_get_userdata1 (rule_up));												
            if (var->negated)
							sim_rule_append_generic_text_not (rule, aux, SIM_RULE_VAR_USERDATA1);
						else
							sim_rule_append_generic_text (rule, aux, SIM_RULE_VAR_USERDATA1);
            break;

			case SIM_RULE_VAR_USERDATA2:
            aux = g_strdup (sim_rule_get_userdata2 (rule_up));												
            if (var->negated)
							sim_rule_append_generic_text_not (rule, aux, SIM_RULE_VAR_USERDATA2);
						else
							sim_rule_append_generic_text (rule, aux, SIM_RULE_VAR_USERDATA2);
            break;

			case SIM_RULE_VAR_USERDATA3:
            aux = g_strdup (sim_rule_get_userdata3 (rule_up));												
            if (var->negated)
							sim_rule_append_generic_text_not (rule, aux, SIM_RULE_VAR_USERDATA3);
						else
							sim_rule_append_generic_text (rule, aux, SIM_RULE_VAR_USERDATA3);
            break;

			case SIM_RULE_VAR_USERDATA4:
            aux = g_strdup (sim_rule_get_userdata4 (rule_up));												
            if (var->negated)
							sim_rule_append_generic_text_not (rule, aux, SIM_RULE_VAR_USERDATA4);
						else
							sim_rule_append_generic_text (rule, aux, SIM_RULE_VAR_USERDATA4);
            break;

			case SIM_RULE_VAR_USERDATA5:
            aux = g_strdup (sim_rule_get_userdata5 (rule_up));												
            if (var->negated)
							sim_rule_append_generic_text_not (rule, aux, SIM_RULE_VAR_USERDATA5);
						else
							sim_rule_append_generic_text (rule, aux, SIM_RULE_VAR_USERDATA5);
            break;

			case SIM_RULE_VAR_USERDATA6:
            aux = g_strdup (sim_rule_get_userdata6 (rule_up));												
            if (var->negated)
							sim_rule_append_generic_text_not (rule, aux, SIM_RULE_VAR_USERDATA6);
						else
							sim_rule_append_generic_text (rule, aux, SIM_RULE_VAR_USERDATA6);
            break;

			case SIM_RULE_VAR_USERDATA7:
            aux = g_strdup (sim_rule_get_userdata7 (rule_up));												
            if (var->negated)
							sim_rule_append_generic_text_not (rule, aux, SIM_RULE_VAR_USERDATA7);
						else
							sim_rule_append_generic_text (rule, aux, SIM_RULE_VAR_USERDATA7);
            break;

			case SIM_RULE_VAR_USERDATA8:
            aux = g_strdup (sim_rule_get_userdata8 (rule_up));												
            if (var->negated)
							sim_rule_append_generic_text_not (rule, aux, SIM_RULE_VAR_USERDATA8);
						else
							sim_rule_append_generic_text (rule, aux, SIM_RULE_VAR_USERDATA8);
            break;

			case SIM_RULE_VAR_USERDATA9:
            aux = g_strdup (sim_rule_get_userdata9 (rule_up));												
            if (var->negated)
							sim_rule_append_generic_text_not (rule, aux, SIM_RULE_VAR_USERDATA9);
						else	
							sim_rule_append_generic_text (rule, aux, SIM_RULE_VAR_USERDATA9);
            break;
			default:
						break;
		}

    vars = vars->next;
  }
}

/**
 * sim_directive_set_rule_var_inet:
 * @inet: a #SimInet
 * @rule: a #SimRule
 * @var:  a #SimRuleVar
 *
 * Set the @inet for the @rule based on @var
 */
static void
sim_directive_set_rule_var_inet (SimInet    *inet,
                                 SimRule    *rule,
                                 SimRuleVar *var)
{
  if (var->attr == SIM_RULE_VAR_SRC_IA)
  {
    if (var->negated)
      sim_rule_add_src_inet_not (rule, inet);
    else
    {
      sim_rule_add_src_inet (rule, inet);
    }
  }
  else if (var->attr == SIM_RULE_VAR_DST_IA)
  {
    if (var->negated)
      sim_rule_add_dst_inet_not (rule, inet);
    else
      sim_rule_add_dst_inet (rule, inet);
  }
}

/**
 * sim_directive_set_rule_var_host_id:
 * @host_id: the host id #SimUuid
 * @rule: a #SimRule
 * @var:  a #SimRuleVar
 *
 * Set the @host_id for the @rule based on @var
 */
static void
sim_directive_set_rule_var_host_id (SimUuid    *host_id,
                                    SimRule    *rule,
                                    SimRuleVar *var)
{
  if (var->attr == SIM_RULE_VAR_SRC_IA)
  {
    if (var->negated)
      sim_rule_add_src_host_id_not (rule, host_id);
    else
      sim_rule_add_src_host_id (rule, host_id);
  }
  else if (var->attr == SIM_RULE_VAR_DST_IA)
  {
    if (var->negated)
      sim_rule_add_dst_host_id_not (rule, host_id);
    else
      sim_rule_add_dst_host_id (rule, host_id);
  }
}

/*
 * This function returns the node to which is referencing the directive level when you say something like "1:SRC_IP".
 * Take for example: root_node->children1->children2. If the "node" parameter in the function is children2, and the
 * level is 1, then this will return the root_node, as it's the 1st level of the children.
 */
GNode*
sim_directive_get_node_branch_by_level (SimDirective     *directive,
																				GNode            *node,
																				gint              level)
{
  GNode  *ret;
  gint    up_level;
  gint    i;

  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), NULL);
  g_return_val_if_fail (node, NULL);

  up_level = g_node_depth (node) - level;	//The root node has a depth of 1.For the children of the root node the depth is 2
  if (up_level < 1)
    return NULL;

  ret = node;
  for (i = 0; i < up_level; i++)
  {
    ret = ret->parent;
  }

  return ret;
}

/*
 *
 *
 *
 */
inline
void
sim_directive_backlog_set_matched (SimDirective     *directive,
			   gboolean          matched)
{
  g_return_if_fail (SIM_IS_DIRECTIVE (directive));

  directive->_priv->matched = matched;
}


/*
 *
 *
 *
 */
gboolean
sim_directive_backlog_get_matched (SimDirective     *directive)
{
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), FALSE);

  return directive->_priv->matched;
}

/**
 * sim_directive_backlog_is_expired:
 * @directive: a #SimDirective
 *
 * Check if backlog is matched, if has set time and if it's 'timeout'
 *
 * Return %TRUE if @directive is expired
 */
gboolean
sim_directive_backlog_is_expired (SimDirective *directive)
{
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), FALSE);

  if (directive->_priv->matched)
    return TRUE;

  if ((!directive->_priv->time_out) || (!directive->_priv->time_last))  //if directive hasn't got any time, this
    return FALSE;                                                       //is the 1st time it enteres here, so no timeout.

  return (time (NULL) > (directive->_priv->time_last + directive->_priv->time_out));
}

/**
 * sim_directive_backlog_set_deleted:
 *
 */
void
sim_directive_backlog_set_deleted (SimDirective * directive,
                                   gboolean       deleted)
{
  g_return_if_fail (SIM_IS_DIRECTIVE (directive));
  directive->_priv->deleted = deleted;
  return;
}

/**
 * sim_directive_backlog_get_deleted:
 *
 */
gboolean
sim_directive_backlog_get_deleted (SimDirective * directive)
{
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), FALSE);
  return (directive->_priv->deleted);
}

/**
 * sim_directive_backlog_time_out:
 * @directive: a #SimDirective.
 *
 * Look if the #SimDirective is time out
 *
 * Returns: TRUE if is time out, FALSE otherwise.
 */
gboolean
sim_directive_backlog_time_out (SimDirective     *directive)
{
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), FALSE);
  g_return_val_if_fail (!directive->_priv->matched, FALSE);

  if ((!directive->_priv->time_out) || (!directive->_priv->time_last))	//if directive hasn't got any time, this
    return FALSE;																												//is the 1st time it enteres here, so no timeout.

  if (time (NULL) > (directive->_priv->time_last + directive->_priv->time_out))
    return TRUE;

  return FALSE;
}

/*
 *
 *
 *
 */
GNode*
sim_directive_node_data_clone (GNode *node)
{
  SimRule  *new_rule;
  GNode    *new_node;
  GNode    *child;

  g_return_val_if_fail (node, NULL);
  g_return_val_if_fail (SIM_IS_RULE (node->data), NULL);

  new_rule = sim_rule_clone ((SimRule *)node->data);
  new_node = g_node_new (new_rule);

  for (child = g_node_last_child (node); child; child = child->prev)
    g_node_prepend (new_node, sim_directive_node_data_clone (child));

  return new_node;
}

/*
 *
 *
 *
 */
void
sim_directive_node_data_destroy (GNode *node)
{
  GNode   *child;

  g_return_if_fail (node);
  g_return_if_fail (SIM_IS_RULE (node->data));

  g_object_unref ((SimRule *) node->data);

  for (child = g_node_last_child (node); child; child = child->prev)
    sim_directive_node_data_destroy (child);
}

/**
 * sim_directive_clone:
 * @directive: a #SimDirective object.
 * @engine_id: a #SimEngine object.
 *
 * Creates a new directive from an existing one,
 * and assigns it to a engine.
 */
SimDirective*
sim_directive_clone (SimDirective * directive,
                     SimEngine    * engine)
{
  SimDirective     *new_directive;
  GTimeVal          curr_time;
  int i;

  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), NULL);

  g_get_current_time (&curr_time);

  new_directive = SIM_DIRECTIVE (g_object_new (SIM_TYPE_DIRECTIVE, NULL));

  new_directive->_priv->id = directive->_priv->id;
  new_directive->_priv->name = g_strdup (directive->_priv->name);

  if (directive->_priv->backlog_id)
    new_directive->_priv->backlog_id = g_object_ref (directive->_priv->backlog_id);

  if (engine)
    new_directive->_priv->engine = g_object_ref (engine);

  new_directive->_priv->priority = directive->_priv->priority;

  new_directive->_priv->rule_root = sim_directive_node_data_clone (directive->_priv->rule_root);
  new_directive->_priv->rule_curr = new_directive->_priv->rule_root;

  new_directive->_priv->time_out = directive->_priv->time_out;
  new_directive->_priv->time = ((gint64) curr_time.tv_sec * (gint64) G_USEC_PER_SEC) + (gint64) curr_time.tv_usec;
  new_directive->_priv->time_last = curr_time.tv_sec;
  new_directive->_priv->time_out = sim_directive_get_rule_curr_time_out_max (new_directive);

  new_directive->_priv->matched = directive->_priv->matched;
  new_directive->_priv->groupalarm = directive->_priv->groupalarm;
  new_directive->_priv->group_alarm_timeout = directive->_priv->group_alarm_timeout;
  for (i = 0; i < SIM_DIRECTIVE_GROUP_ALARM_BY_LAST;i++){
    new_directive->_priv->group_alarm_by[i] = directive->_priv->group_alarm_by [i];
  }

  return new_directive;
}

/*
 *
 *
 *
 */
gchar *
sim_directive_backlog_get_insert_clause (SimDirective *directive)
{
  gchar *query;
  gchar *values;

  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), NULL);

  values = sim_directive_backlog_get_values_clause (directive);

  query = g_strdup_printf ("INSERT INTO backlog %s VALUES %s",
                           sim_directive_backlog_get_header_clause (),
                           values);
  g_free (values);

  return query;
}

const gchar *
sim_directive_backlog_get_header_clause (void)
{
  return "(id, corr_engine_ctx, directive_id, timestamp, last, matched)";
}

gchar *
sim_directive_backlog_get_values_clause (SimDirective *directive)
{
  gchar    timestamp[TIMEBUF_SIZE], last[TIMEBUF_SIZE];
  gchar    *query;
  SimUuid  *engine_id;

  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), NULL);

  strftime (timestamp, TIMEBUF_SIZE, "%Y-%m-%d %H:%M:%S", gmtime (&directive->_priv->first_event));
  strftime (last, TIMEBUF_SIZE, "%Y-%m-%d %H:%M:%S", gmtime (&directive->_priv->last_event));
  engine_id = sim_engine_get_id (directive->_priv->engine);

  query = g_strdup_printf ("(%s, %s, %d, '%s', '%s', %d)",
                           sim_uuid_get_db_string (directive->_priv->backlog_id),
                           sim_uuid_get_db_string (engine_id),
                           directive->_priv->id,
                           timestamp,
                           last,
                           directive->_priv->matched);

  return query;
}

/*
 *
 *
 */
gchar*
sim_directive_backlog_get_update_clause (SimDirective *directive)
{
  gchar timestamp[TIMEBUF_SIZE], last[TIMEBUF_SIZE];
  gchar *query;

  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), NULL);

  strftime (timestamp, TIMEBUF_SIZE, "%Y-%m-%d %H:%M:%S", gmtime (&directive->_priv->first_event));
  strftime (last, TIMEBUF_SIZE, "%Y-%m-%d %H:%M:%S", gmtime (&directive->_priv->last_event));
  query = g_strdup_printf ("UPDATE backlog SET matched = %d, timestamp = '%s', last = '%s' WHERE backlog.id = %s",
                           directive->_priv->matched,
                           timestamp, last,
                           sim_uuid_get_db_string (directive->_priv->backlog_id));

  return query;
}


/*
 *
 *
 *
 */
gchar*
sim_directive_backlog_get_delete_clause (SimDirective *directive)
{
  gchar   *query;

  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), NULL);

  query = g_strdup_printf ("DELETE FROM backlog WHERE id = %s",
                           sim_uuid_get_db_string (directive->_priv->backlog_id));

  return query;
}

/*
 *
 *
 *
 */
gchar*
sim_directive_backlog_event_get_insert_clause (SimDirective *directive,
                                               SimEvent     *event,
                                               gint          level)
{
  gchar  *query;

  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), NULL);
  g_return_val_if_fail (SIM_IS_EVENT (event), NULL);

  query = g_strdup_printf ("INSERT INTO backlog_event"
                           " (backlog_id, event_id, time_out, occurrence, rule_level, matched)"
                           " VALUES (%s, %s, %lu, %d, %d, %d)",
                           sim_uuid_get_db_string (directive->_priv->backlog_id),
                           sim_uuid_get_db_string (event->id),
                           (unsigned long)directive->_priv->time_out,
                           event->count,
                           level,
                           event->directive_matched);
  return query;
}

gchar*
sim_directive_backlog_event_get_insert_clause_values (SimDirective *directive,
                                                      SimEvent     *event,
                                                      gint          level)
{
  gchar *query;

  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), NULL);
  g_return_val_if_fail (SIM_IS_EVENT (event), NULL);

  query = g_strdup_printf ("(%s, %s, %lu, %d, %d, %d)",
                           sim_uuid_get_db_string (directive->_priv->backlog_id),
                           sim_uuid_get_db_string (event->id),
                           (unsigned long)directive->_priv->time_out,
                           event->count,
                           level,
                           event->directive_matched);
  return query;
}

/*
 *
 *
 *
 */
void
sim_directive_print (SimDirective  *directive)
{
  g_return_if_fail (SIM_IS_DIRECTIVE (directive));

  g_print ("DIRECTIVE: name=\"%s\"\n", directive->_priv->name);
}

/*
 *
 *
 *
 */
gchar*
sim_directive_backlog_to_string (SimDirective  *directive)
{
  GString  *str, *vals;
  GNode    *node;
  gchar    *val;

  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), NULL);
  g_return_val_if_fail (directive->_priv->rule_curr, NULL);

  str = g_string_sized_new (0);
  g_string_append_printf (str, "%s, Priority: %d ",
                          directive->_priv->name,
                          directive->_priv->priority);

  vals = g_string_sized_new (0);
  node = directive->_priv->rule_curr;
  while (node)
  {
    SimRule *rule = (SimRule *) node->data;

    if ((val = sim_rule_to_string (rule)))
		{
		  g_string_prepend (vals, val);
	  	g_free (val);
		}

    node = node->parent;
  }

  g_string_append (str, vals->str);
  g_string_free (vals, TRUE);

  return g_string_free (str, FALSE);
}

/**
 * sim_directive_backlog_id_generate:
 * @backlog: a #SimDirective.
 *
 * Generates a new backlog_id based in time.
 */
void
sim_directive_backlog_id_generate (SimDirective * backlog)
{
  g_return_if_fail (SIM_IS_DIRECTIVE (backlog));

  if (backlog->_priv->backlog_id)
    g_object_unref (backlog->_priv->backlog_id);

  backlog->_priv->backlog_id = sim_uuid_new ();
}

/*
 * Public API
 */

void
sim_directive_set_timetable(SimDirective *directive,SimTimetable *timetable )
{
  g_return_if_fail (SIM_IS_DIRECTIVE (directive));
	directive->_priv->timetable = g_object_ref(timetable);
}
/*
 *
 */

SimTimetable*   
sim_directive_get_timetable(SimDirective *directive){
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), NULL);
	return directive->_priv->timetable;
}
/*
 * Public API
 */
void
sim_directive_set_timetable_name(SimDirective *directive,gchar *name){
	g_return_if_fail (SIM_IS_DIRECTIVE (directive));
	directive->_priv->timetable_name = g_strdup(name);
}
/*
 * Public API
 */
gchar *
sim_directive_get_timetable_name(SimDirective *directive){
	g_return_val_if_fail(SIM_IS_DIRECTIVE(directive),NULL);
	return directive->_priv->timetable_name;
}
/*
 * Public API
 */
gboolean
sim_directive_check_timetable_restriction(SimDirective *directive, SimEvent *event){
	g_return_val_if_fail (SIM_IS_DIRECTIVE(directive),FALSE);
	gboolean ok = FALSE;
	struct tm *t;

	if (directive->_priv->timetable!=NULL){
		ossim_debug("Checking timetable in directive %s", directive->_priv->name);
		/* Local time*/
		//t = localtime(&epoch);
		t = gmtime (&event->time);
		ok = !sim_timetable_check_timetable (directive->_priv->timetable, t, event);
	}
	return ok;
}

void
sim_directive_delete_database_backlog (SimUuid *backlog_id, gchar *alarm_stats)
{
  gchar *query;
  GdaDataModel *dm;

  g_return_if_fail (SIM_IS_UUID (backlog_id));

  query = g_strdup_printf ("SELECT backlog_id FROM alarm WHERE backlog_id = %s",
                           sim_uuid_get_db_string (backlog_id));

  dm = sim_database_execute_single_command (ossim.dbossim, query);
  if (dm)
  {
    if (!gda_data_model_get_n_rows (dm))
      sim_directive_db_delete_backlog_by_id_ul (backlog_id);
    else
      sim_directive_set_alarm_as_removable (backlog_id, alarm_stats);

    g_object_unref(dm);
  }
  else
    g_message ("BACKLOG DELETE DATA MODEL ERROR");

  g_free (query);
}


static void
sim_directive_db_delete_backlog_by_id_ul (SimUuid *backlog_id)
{
  GdaDataModel  *dm;
  GdaDataModel  *dm1=NULL;
  const GValue  *value;
  gchar   *query0;
  gchar   *query1;
  gchar   *query2;
  SimUuid *event_id;
  gint    row;
  glong   count;

  query0 =  g_strdup_printf ("SELECT event_id FROM backlog_event WHERE backlog_id = %s",
                             sim_uuid_get_db_string (backlog_id));
  dm = sim_database_execute_single_command (ossim.dbossim, query0);
  if (dm)
  {
    for (row = 0; row < gda_data_model_get_n_rows (dm); row++)
    {
      value = gda_data_model_get_value_at (dm, 0, row, NULL);
      event_id = sim_uuid_new_from_blob (gda_value_get_blob (value));

      query1 = g_strdup_printf ("SELECT COUNT(event_id) FROM backlog_event WHERE event_id = %s",
                                sim_uuid_get_db_string (event_id));

      dm1 = sim_database_execute_single_command (ossim.dbossim, query1);
      if (dm1)
      {
        value = gda_data_model_get_value_at (dm1, 0, 0, NULL);
        count = g_value_get_long (value);

        if (count == 1)
        {
          query2 = g_strdup_printf ("DELETE FROM event WHERE id = %s AND alarm = 0",
                                    sim_uuid_get_db_string (event_id));
          sim_database_execute_no_query (ossim.dbossim, query2);
          g_free (query2);
        }

        g_object_unref(dm1);
      }
      else
        g_message("Error: problem executing the following command in the DB: %s",query1);
      g_free (query1);

      g_object_unref (event_id);
    }
    g_object_unref(dm);
  }
  g_free (query0);

  query0 = g_strdup_printf ("DELETE FROM backlog_event WHERE backlog_id = %s", sim_uuid_get_db_string (backlog_id));
  sim_database_execute_no_query (ossim.dbossim, query0);
  g_free (query0);

  query0 = g_strdup_printf ("DELETE FROM backlog WHERE id = %s", sim_uuid_get_db_string (backlog_id));
  sim_database_execute_no_query (ossim.dbossim, query0);
  g_free (query0);
}


/*
 * Deletes info stored in db without alarm asociated (backlogs and events)
 */

void sim_directive_purge_db_backlogs(SimDatabase *db)
{
  gchar *query;
  GList *backlog_id_list, *list;
  GdaConnection *conn;

  g_message ("Optimizing event and backlog rows in database");

  // Deleting rows in backlog table
	ossim_debug	("%s: Deleting rows from backlog table without alarm", __FUNCTION__);
  sim_database_execute_no_query (db, "DELETE b FROM backlog b LEFT JOIN alarm a ON b.id = a.backlog_id "
                                     " WHERE a.backlog_id IS NULL");

	// Deleting rows in backlog_event
	ossim_debug	("%s: Deleting rows from backlog_event table without alarm", __FUNCTION__);
  sim_database_execute_no_query (db, "DELETE be FROM backlog_event be LEFT JOIN backlog b ON be.backlog_id = b.id "
                                     " WHERE b.id IS NULL");

	// Deleting rows in event
	ossim_debug	("%s: Deleting rows from event table without alarm", __FUNCTION__);
  sim_database_execute_no_query (db, "DELETE e FROM event e LEFT JOIN backlog_event be ON e.id = be.event_id "
                                     " WHERE be.event_id IS NULL");

  // Deleting rows in extra_data
  ossim_debug ("%s: Deleting rows from extra_data table without event", __FUNCTION__);
  sim_database_execute_no_query (db, "DELETE ed FROM extra_data ed LEFT JOIN backlog_event be ON ed.event_id = be.event_id "
                                     " WHERE be.event_id IS NULL");

  // Deleting rows in idm_data
  ossim_debug ("%s: Deleting rows from idm_data table without event", __FUNCTION__);
  sim_database_execute_no_query (db, "DELETE id FROM idm_data id LEFT JOIN backlog_event be ON id.event_id = be.event_id "
                                     " WHERE be.event_id IS NULL");

  // Deleting rows from alarm_ctxs without alarm
	ossim_debug	("%s: Deleting rows from alarm_ctxs table without alarm", __FUNCTION__);
  sim_database_execute_no_query (db, "DELETE ac FROM alarm_ctxs ac LEFT JOIN alarm a ON ac.id_alarm = a.backlog_id "
                                     " WHERE a.backlog_id IS NULL");

  // Deleting rows from alarm_hosts without alarm
	ossim_debug	("%s: Deleting rows from alarm_hosts table without alarm", __FUNCTION__);
  sim_database_execute_no_query (db, "DELETE ac FROM alarm_hosts ac LEFT JOIN alarm a ON ac.id_alarm = a.backlog_id "
                                     " WHERE a.backlog_id IS NULL");

  // Deleting rows from alarm_nets without alarm
	ossim_debug	("%s: Deleting rows from alarm_nets table without alarm", __FUNCTION__);
  sim_database_execute_no_query (db, "DELETE ac FROM alarm_nets ac LEFT JOIN alarm a ON ac.id_alarm = a.backlog_id "
                                     " WHERE a.backlog_id IS NULL");

  // Updating rows from alarm (set them as removable) and update alarm stats
  ossim_debug	("%s: Setting rows from alarm to removable and updating alarm stats", __FUNCTION__);

  conn = sim_database_get_conn (db);

  backlog_id_list = sim_db_get_removable_alarms (db);
  for (list = backlog_id_list; list; list = list->next)
  {
    GList *event_list, *elist;
    gchar *alarm_stats, *e_alarm_stats;
    SimUuid *backlog_id;

    backlog_id = list->data;

    event_list = sim_db_load_ligth_events_from_alarm (db, backlog_id);
    alarm_stats = sim_alarm_stats_recalculate (event_list);
    e_alarm_stats = sim_str_escape (alarm_stats, conn, 0);

    query = g_strdup_printf ("UPDATE alarm SET removable = 1, stats = '%s' WHERE backlog_id = %s",
                             e_alarm_stats, sim_uuid_get_db_string (backlog_id));
    sim_database_execute_no_query (db, query);
    g_free (query);
    g_free (e_alarm_stats);
    g_free (alarm_stats);

    for (elist = event_list; elist; elist = elist->next)
      sim_event_unref (elist->data);
    g_list_free (event_list);

    g_object_unref (backlog_id);
  }

  g_list_free (backlog_id_list);
}


gint	sim_directive_get_group_alarm_timeout(SimDirective *directive)
{
  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), 0);

  if (directive->_priv->group_alarm_timeout <= 0)
  {
  	return 0;
  }
  if (directive->_priv->group_alarm_timeout >=SIM_DIRECTIVE_MAX_GROUP_ALARM_TIMEOUT)
  {
  	return SIM_DIRECTIVE_MAX_GROUP_ALARM_TIMEOUT;
  }

  return directive->_priv->group_alarm_timeout;
}
void	sim_directive_set_group_alarm_timeout(SimDirective *directive,gint value)
{
  g_return_if_fail (SIM_IS_DIRECTIVE (directive));

  if (value < 0)
  {
  	g_warning("Invalid group_alarm_timeout value (%d), in directive %d, set timeout to 0",value,sim_directive_get_id(directive));
  	directive->_priv->group_alarm_timeout = 0;
  }
  else if (value > SIM_DIRECTIVE_MAX_GROUP_ALARM_TIMEOUT)
  {
  	g_warning("Invalid group_alarm_timeout value (%d), in directive %d, set timeout to %d",value,sim_directive_get_id(directive),SIM_DIRECTIVE_MAX_GROUP_ALARM_TIMEOUT);
  	directive->_priv->group_alarm_timeout = SIM_DIRECTIVE_MAX_GROUP_ALARM_TIMEOUT;
  }
  else
  {
  	directive->_priv->group_alarm_timeout = value;
  }
}

gboolean*	sim_directive_get_group_alarm_by(SimDirective* directive)
{
	g_return_val_if_fail (directive, NULL);
	g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), NULL);
	return directive->_priv->group_alarm_by;
}

void	sim_directive_set_group_alarm_by(SimDirective *directive, gboolean group_by_scr_ip,gboolean group_by_scr_port,\
		gboolean group_by_dst_ip,gboolean group_by_dst_port)
{
	g_return_if_fail (SIM_IS_DIRECTIVE (directive));
	directive->_priv->group_alarm_by[SIM_DIRECTIVE_GROUP_ALARM_BY_SRC_IP] = group_by_scr_ip;
	directive->_priv->group_alarm_by[SIM_DIRECTIVE_GROUP_ALARM_BY_SRC_PORT] = group_by_scr_port;
	directive->_priv->group_alarm_by[SIM_DIRECTIVE_GROUP_ALARM_BY_DST_IP] = group_by_dst_ip;
	directive->_priv->group_alarm_by[SIM_DIRECTIVE_GROUP_ALARM_BY_DST_PORT] = group_by_dst_port;
	if (group_by_scr_ip||group_by_scr_port||group_by_dst_ip||group_by_dst_port)
		directive->_priv->groupalarm = TRUE;

}

gboolean	sim_directive_get_group_alarm_store_backlog(SimDirective* directive)
{
	g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), FALSE);
	return directive->_priv->group_alarm_store_backlog;
}
void 	sim_directive_set_group_alarm_store_backlog(SimDirective* directive,gboolean value)
{
	g_return_if_fail (SIM_IS_DIRECTIVE (directive));
	directive->_priv->group_alarm_timeout = value;
}

gboolean sim_directive_get_group_alarm_by_src_ip(SimDirective* directive)
{
	g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), FALSE);
	return directive->_priv->group_alarm_by[SIM_DIRECTIVE_GROUP_ALARM_BY_SRC_IP];

}
gboolean sim_directive_get_group_alarm_by_src_port(SimDirective* directive)
{
	g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), FALSE);
	return directive->_priv->group_alarm_by[SIM_DIRECTIVE_GROUP_ALARM_BY_SRC_PORT];
}
gboolean sim_directive_get_group_alarm_by_dst_ip(SimDirective* directive)
{
	g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), FALSE);
	return directive->_priv->group_alarm_by[SIM_DIRECTIVE_GROUP_ALARM_BY_DST_IP];
}
gboolean sim_directive_get_group_alarm_by_dst_port(SimDirective* directive)
{
	g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), FALSE);
	return directive->_priv->group_alarm_by[SIM_DIRECTIVE_GROUP_ALARM_BY_DST_PORT];
}
/**

	@brief This function get the info about the groupalarm flag in a directive / backlog. 
	
	@param directive Pointer to a directive.

	@return the flag or FALSE if there is an error
	
	



*/
inline 
gboolean sim_directive_get_groupalarm ( SimDirective *directive){
	g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), FALSE);
	return directive->_priv->groupalarm;

}

/*
 *
 *
 *
 *
 */
gboolean
sim_directive_all_children_have_src_ia(GNode *parent)
{
  g_return_val_if_fail (parent, FALSE);
   
  GNode *children = parent->children;
  while(children)
  {
    SimRule *rule = children->data;

    GList *vars = sim_rule_get_vars (rule);
    while (vars)
    {
      SimRuleVar *var = (SimRuleVar *) vars->data;
      if(var->type == SIM_RULE_VAR_SRC_IA && var->attr == SIM_RULE_VAR_SRC_IA && var->level == 1 && !var->negated)
        break;

      vars = vars->next;
    }
    if(!vars) return FALSE;

    children = children->next;
  }

  return TRUE;
}

/**
 * sim_directive_get_root_plugin_ids:
 * @directive: a #SimDirective
 *
 * Returns: plugin_id of @directive root node
 */
GHashTable *
sim_directive_get_root_plugin_ids (SimDirective *directive)
{
	GHashTable *plugin_ids;
	GNode	*root;

  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), 0);

	root = sim_directive_get_root_node (directive);
	plugin_ids = sim_rule_get_plugin_ids ((SimRule *)root->data);

	return plugin_ids;
}

/**
 * sim_directive_get_root_taxonomy_product:
 * @directive: a #SimDirective
 *
 * Returns: taxonomy product of @directive root node
 */
GHashTable *
sim_directive_get_root_taxonomy_product (SimDirective *directive)
{
	GNode	*root;

  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), 0);

	root = sim_directive_get_root_node (directive);

  return sim_rule_get_products ((SimRule *)root->data);
}

/**
 * sim_directive_is_in_list:
 * @directive: SimDirective object
 * @disabled_list: GList with the disabled id
 *
 * Reads the directive_id and search it in the list
 *
 * Return value: %TRUE if the directive_id is in the list
 */
gboolean
sim_directive_is_in_list (SimDirective *directive,
                          GList        *disabled_list)
{
  GList *node = disabled_list;
  guint64 id;

  id = sim_directive_get_id (directive);

  while (node != NULL)
  {
    if (id == (guint64) node->data)
      return TRUE;

    node = g_list_next (node);
  }
  return FALSE;
}

/**
 * sim_directive_set_alarm_as_removable:
 * @backlog_id: PK value in alarm table
 *
 * Updates alarm table, setting a concrete alarm as removable
 *
 * Return value: void
 */
void
sim_directive_set_alarm_as_removable (SimUuid *backlog_id, gchar *alarm_stats)
{
  gchar *query;

  g_return_if_fail (SIM_IS_UUID (backlog_id));

  ossim_debug("%s: Updating removable field in alarm table", __func__);

  query = g_strdup_printf("UPDATE alarm SET removable = 1, stats = '%s' WHERE backlog_id = %s",
                          alarm_stats,
                          sim_uuid_get_db_string (backlog_id));
  sim_database_execute_no_query (ossim.dbossim, query);

  g_free (query);
}


/**
 * sim_directive_backlog_get_plugin_ids:
 * @directive: a #SimDirective
 *
 * Returns: plugin_id of @directive root node
 */
GHashTable *
sim_directive_backlog_get_plugin_ids (SimDirective *backlog)
{
  GHashTable *plugin_ids;
  GNode *child_node;

  g_return_val_if_fail (SIM_IS_DIRECTIVE (backlog), NULL);

  plugin_ids = g_hash_table_new (g_direct_hash, g_direct_equal);

  child_node = backlog->_priv->rule_curr->children;
  while (child_node)
  {
    SimRule *rule = (SimRule *) child_node->data;

    if (sim_rule_has_plugin_ids (rule))
    {
      GHashTable *rule_plugin_ids = sim_rule_get_plugin_ids (rule);
      SIM_WHILE_HASH_TABLE (rule_plugin_ids)
      {
        g_hash_table_insert (plugin_ids, _key, _key);
      }
    }
    else // Taxonomy product in rule vars
    {
      //Not supported yet
    }

    child_node = child_node->next;
  }

  return plugin_ids;
}

/*
 * Backlog references
 */

/**
 * sim_directive_backlog_update_ref:
 *
 */
GHashTable *
sim_directive_backlog_get_refs (SimDirective *backlog)
{
  g_return_val_if_fail (SIM_IS_DIRECTIVE (backlog), NULL);

  if (backlog->_priv->backlog_refs)
    return g_hash_table_ref (backlog->_priv->backlog_refs);
  else
    return NULL;
}

/**
 * sim_directive_backlog_remove_ref:
 *
 */
void
sim_directive_backlog_remove_refs (SimDirective *backlog)
{
  g_return_if_fail (SIM_IS_DIRECTIVE (backlog));

  if (backlog->_priv->backlog_refs)
    g_hash_table_unref (backlog->_priv->backlog_refs);

  backlog->_priv->backlog_refs = g_hash_table_new (g_direct_hash, g_direct_equal);
}

/**
 * sim_directive_backlog_update_ref:
 *
 */
void
sim_directive_backlog_update_ref (SimDirective *backlog,
                                  gint          plugin_id,
                                  gint          index)
{
  g_return_if_fail (SIM_IS_DIRECTIVE (backlog));

  g_hash_table_replace (backlog->_priv->backlog_refs,
                        GINT_TO_POINTER (plugin_id),
                        GINT_TO_POINTER (index));
}

/**
 * sim_directive_update_backlog_first_last_ts:
 *
 */
gboolean
sim_directive_update_backlog_first_last_ts (SimDirective *backlog,
                                            SimEvent *event)
{
  gboolean  change = FALSE;

  if(backlog->_priv->first_event > event->time)
  {
    backlog->_priv->first_event = event->time;
    change = TRUE;
  }

  if(backlog->_priv->last_event < event->time)
  {
    backlog->_priv->last_event = event->time;
    change = TRUE;
  }

  return change;
}

gchar *
sim_directive_dummy_backlog_get_values_clause (SimEvent *event)
{
  gchar    *query, timestamp[TIMEBUF_SIZE];
  SimUuid  *engine_id;

  g_return_val_if_fail (SIM_IS_EVENT (event), NULL);

  engine_id = sim_engine_get_id (event->engine);

  if (!(event->time_str))
    strftime (timestamp, TIMEBUF_SIZE, "%Y-%m-%d %H:%M:%S", gmtime (&event->time));

  query = g_strdup_printf ("(%s, %s, 0, '%s', '%s', 0)",
                           sim_uuid_get_db_string (event->backlog_id),
                           sim_uuid_get_db_string (engine_id),
                           event->time_str ? event->time_str : timestamp,
                           event->time_str ? event->time_str : timestamp);

  return query;
}

gchar*
sim_directive_dummy_backlog_event_get_values_clause (SimEvent  *event)
{
  gchar *query;

  g_return_val_if_fail (SIM_IS_EVENT (event), NULL);
   
  query = g_strdup_printf ("(%s, %s, 0, %d, 0, %d)",
                           sim_uuid_get_db_string (event->backlog_id),
                           sim_uuid_get_db_string (event->id),
                           event->count,
                           event->directive_matched);
  return query;
}

void
sim_directive_alarm_stats_update (SimDirective *directive, SimEvent *event)
{
  sim_alarm_stats_update (directive->_priv->alarm_stats, event);
}

gchar *
sim_directive_alarm_stats_generate (SimDirective *directive)
{
  return sim_alarm_stats_to_json (directive->_priv->alarm_stats);
}

#ifdef USE_UNITTESTS

/*************************************************************
 *******************      Unit tests      ********************
 *************************************************************/

gboolean sim_directive_test1 (void);
gboolean sim_directive_test2 (void);

static const struct
{
  gint  plugin;
  gint  index;

} refs[] = {{1501, 1},
            {1502, 2},
            {1503, 3},
            {2000, 10}
};


gboolean
sim_directive_test1 (void)
{
  gboolean success = TRUE;

  SimDirective *backlog;
  GHashTable *backlog_refs;
  guint i;

  backlog = sim_new_test_directive ();

  /* Append refs */
  for (i = 0; i < G_N_ELEMENTS (refs); i++)
  {
    sim_directive_backlog_update_ref (backlog, refs[i].plugin, refs[i].index);
  }

  /* Get and chek the refs*/
  backlog_refs = sim_directive_backlog_get_refs (backlog);
  for (i = 0; i < G_N_ELEMENTS (refs); i++)
  {
    gint index = GPOINTER_TO_INT (g_hash_table_lookup (backlog_refs, GINT_TO_POINTER (refs[i].plugin)));
    if (index != refs[i].index)
    {
      g_print ("Error index for plugin %d is %d and it must be %d", refs[i].plugin, index,refs[i].index);
      success = FALSE;
    }
  }
  g_hash_table_unref (backlog_refs);

  /* Update and check again */
  for (i = 0; i < G_N_ELEMENTS (refs); i++)
  {
    sim_directive_backlog_update_ref (backlog, refs[i].plugin, refs[i].index + 1);
  }

  backlog_refs = sim_directive_backlog_get_refs (backlog);

  if (g_hash_table_size (backlog_refs) != G_N_ELEMENTS (refs))
  {
    g_print ("Error hash_table_size is %u and it must be %lu", g_hash_table_size (backlog_refs), G_N_ELEMENTS (refs));
    success = FALSE;
  }

  for (i = 0; i < G_N_ELEMENTS (refs); i++)
  {
    gint index = GPOINTER_TO_INT (g_hash_table_lookup (backlog_refs, GINT_TO_POINTER (refs[i].plugin)));
    if (index != refs[i].index + 1)
    {
      g_print ("Error index for plugin %d is %d and it must be %d", refs[i].plugin, index,refs[i].index + 1);
      success = FALSE;
    }
  }
  g_hash_table_unref (backlog_refs);

  /* Remove refs */
  sim_directive_backlog_remove_refs (backlog);
  backlog_refs = sim_directive_backlog_get_refs (backlog);

  if (g_hash_table_size (backlog_refs) != 0)
  {
    g_print ("Error hash_table_size is %ul and it must be 0", g_hash_table_size (backlog_refs));
    success = FALSE;
  }
  g_hash_table_unref (backlog_refs);

  g_object_unref (backlog);

  return success;
}

gboolean
sim_directive_test2 (void)
{
  gboolean success = TRUE;

  SimDirective *backlog;
  GHashTable *plugin_ids;

  backlog = sim_new_test_directive ();

  sim_directive_set_curr_node (backlog, sim_directive_get_root_node (backlog));

  plugin_ids = sim_directive_backlog_get_plugin_ids (backlog);

  SIM_WHILE_HASH_TABLE (plugin_ids)
  {
    gint plugin_id = GPOINTER_TO_INT (value);

    if (plugin_id != 1611)
    {
      g_print ("Error backlog plugin id %d and it must be %d", plugin_id, 1611);
      success = FALSE;
    }
  }

  g_hash_table_unref (plugin_ids);

  return success;
}

void
sim_directive_register_tests (SimUnittesting *engine)
{
  sim_unittesting_append (engine, "sim_directive_test1 - Backlog Refs", sim_directive_test1, TRUE);
  sim_unittesting_append (engine, "sim_directive_test2 - Backlog get plugins", sim_directive_test2, TRUE);
}
#endif /* USE_UNITTESTS */

// vim: set tabstop=2:
