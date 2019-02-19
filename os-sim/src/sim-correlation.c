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

#include "sim-correlation.h"

#include <math.h>
#include <time.h>

#include "os-sim.h"
#include "sim-enums.h"
#include "sim-directive.h"
#include "sim-event.h"
#include "sim-context.h"
#include "sim-db-command.h"


/**
 * SECTION:sim-correlation
 * @title: Correlation
 * @short_description: Correlation functions
 *
 * Correlation tries to match a #SimEvent with all the backlogs and
 * all the directives with the same plugin_id in the event context.
 *
 * Correlation process:
 * Check all backlog in event context.
 * If the event matches with any backlog then
 * - updates backlog
 * - create new directive event and reinyect it to the server event queue.
 * - updates database tables,
 * - adds backlog directive_id to the backlogs_matches
 *
 * Check all directives with the event plugin_sid in event context and
 * directive_id not in the backlog_matches.
 * If the event matches with a directive then
 * - create a new Backlog
 * - create a new directive event and reinyect it if the event causes an alarm
 * - update database tables
 */

/*
  Prototypes
*/
static void       sim_correlation_match_backlogs            (SimEvent     *event,
                                                             GList       **backlogs_matches);
static void       sim_correlation_match_directives          (SimEvent     *event,
                                                             GList        *backlogs_matches);
static void       sim_correlation_match_directives_array    (SimEvent     *event,
                                                             GList        *backlogs_matches,
                                                             GPtrArray    *plugin_directives);
static SimEvent * sim_correlation_new_directive_event       (SimEvent     *event,
                                                             SimDirective *backlog);
static void       sim_correlation_update_children_nodes     (GNode        *node_root,
                                                             SimDirective *backlog,
                                                             time_t        time_last);
static void       sim_correlation_check_monitor_rule        (GNode        *rule_node);
static gboolean   sim_correlation_causes_alarm              (SimDirective *directive,
                                                             SimRule      *rule,
                                                             SimEvent     *event);
static float      sim_correlation_calculate_risk            (SimEvent     *event);
static void       sim_correlation_group_alarm_process       (SimEvent     *event,
                                                             SimDirective *backlog);

/*
  Public API
 */

/**
 * sim_correlation:
 * @event: a #SimEvent
 *
 * Correlation function.
 * Try to correlate @event with Backlogs and Directives
 * in @event context.
 */
void
sim_correlation (SimEvent *event)
{
  GList *backlogs_matches;

  g_return_if_fail (SIM_IS_EVENT (event));

  /* If the event has been correlated before, we don't want to do it again. */
  if (event->is_correlated) // needed & setted just for OS,MAC,Service and HIDS in multilevel architecture.
    return;

  backlogs_matches = NULL;

  ossim_debug ("%s: event->id: %s event->plugin_id: %u event->plugin_sid: %u",  __func__,
               sim_uuid_get_string (event->id), event->plugin_id, event->plugin_sid);

  if (g_hash_table_size (event->otx_data) > 0)
  {
    sim_engine_add_otx_data (event->engine,event->context, event->otx_data);  
  }
  /* Match Backlogs */
  sim_correlation_match_backlogs (event, &backlogs_matches);

  /* Match Directives */
  sim_correlation_match_directives (event, backlogs_matches);

  g_list_free (backlogs_matches);

  return;
}


/**
 * sim_correlation_match_backlogs:
 * @event: a #SimEvent
 * @backlogs_matches: backlogs matches list
 *
 * Check if @event matches with any backlog in @event context
 */
static void
sim_correlation_match_backlogs (SimEvent   *event,
                                GList     **backlogs_matches)
{
  GPtrArray    *backlogs;
  SimDirective *backlog;
  SimEvent     *directive_event;
  gint          directive_id;
  SimUuid      *backlog_id;
  gchar        *backlog_str;
  guint         i;

  sim_engine_lock_backlogs (event->engine);

  backlogs = sim_engine_get_backlogs_ul (event->engine);
  if (!backlogs->len)
  {
    sim_engine_unlock_backlogs (event->engine);
    return;
  }
  for (i = 0; i < backlogs->len; i++)
  {
    directive_event = NULL;
    backlog = (SimDirective *)g_ptr_array_index (backlogs, i);

    /* Don't check this backlog if it's already marked as removed */
    if (sim_directive_backlog_get_deleted (backlog))
      continue;

    /* Marks backlog to remove if:
      - It's 'matched', so there are no more nodes in it.
      - It has reached its time limit. */
    if (sim_directive_backlog_get_matched (backlog) ||
        sim_directive_backlog_time_out (backlog))
    {
      sim_directive_backlog_set_deleted (backlog, TRUE);
      continue;
    }

    directive_id = sim_directive_get_id (backlog);

    /* Check event match with backlog */
    if (sim_directive_backlog_match_by_event (backlog, event))
    {
      GNode *rule_node = sim_directive_get_curr_node (backlog);

      backlog_str = sim_directive_backlog_to_string (backlog);
      ossim_debug ("%s: matched event->id: %s, id: %d, directive_id: %s, backlog_str: %s ", __func__,
                   sim_uuid_get_string (event->id), directive_id, sim_directive_get_backlog_id_str (backlog), backlog_str);
      g_free (backlog_str);
      event->correlation |= EVENT_MATCH_DIRECTIVE_CORR;

      event->directive_matched = TRUE;
      event->belongs_to_alarm = TRUE;
      SimUuid *backlog_id = sim_directive_get_backlog_id (backlog);
      if(event->backlog_id)
        g_object_unref (event->backlog_id);
      event->backlog_id = g_object_ref (backlog_id);
        sim_directive_update_backlog_first_last_ts(backlog, event);

      /* If the rule is the last node mark the backlog to be deleted,
         and if it has childrens then check if any children node is a MONITOR rule */
      if (G_NODE_IS_LEAF (rule_node))
        sim_directive_backlog_set_deleted (backlog, TRUE);
      else
        sim_correlation_check_monitor_rule (rule_node);

      /* Create new directive event, add it to the rule and re-inyect */
      sim_directive_alarm_stats_update (backlog, event);
      directive_event = sim_correlation_new_directive_event (event, backlog);
      /* Well, especial case: We need to set filename in case of pulse_id */
      if (directive_event->plugin_sid == SIM_DIRECTIVE_PULSE_ID)
      {
        SimRule *rule = sim_directive_get_curr_rule (backlog);
        if (directive_event->filename)
          g_free (directive_event->filename);
        directive_event->filename = g_strdup (sim_rule_get_pulse_id (rule));
      }

      sim_session_prepare_and_insert_non_block (directive_event);

      /* DB Update backlog */
      sim_db_update_backlog (ossim.dbossim, backlog);

      /* DB Inserts backlog event */
      sim_db_insert_backlog_event (ossim.dbossim, backlog, event, sim_directive_get_rule_level (backlog));
      sim_db_insert_alarm_view_tables (ossim.dbossim, event);
      sim_db_insert_backlog_event (ossim.dbossim, backlog, directive_event, sim_directive_get_rule_level (backlog));
      sim_db_insert_alarm_view_tables (ossim.dbossim, directive_event);

      sim_event_unref (directive_event);
    }
    else if (event->rule_matched)
    {
      /* When the ocurrence is > 1 in the rule, the first call to
         sim_directive_backlog_match_by_event (above) will return FALSE, and the event won't be
         inserted in db. So we have to insert it here. */
      backlog_id = sim_directive_get_backlog_id (backlog);
      if(event->backlog_id)
        g_object_unref (event->backlog_id);
      event->backlog_id = g_object_ref (backlog_id);
      event->belongs_to_alarm = TRUE;

      sim_directive_alarm_stats_update (backlog, event);

        if(sim_directive_update_backlog_first_last_ts(backlog, event))
          sim_db_update_backlog(ossim.dbossim, backlog);

      /* The rule points to previous level in order to try match with each of its children,
         so the backlog event belongs to the next level of the rule */
      sim_db_insert_backlog_event (ossim.dbossim, backlog, event, sim_directive_get_rule_level (backlog) + 1);
      sim_db_insert_alarm_view_tables (ossim.dbossim, event);
    }

    /* Insert directive_id into backlogs_matches table if the event matched */
    if (event->rule_matched)
      *backlogs_matches = g_list_append (*backlogs_matches, GINT_TO_POINTER (directive_id));

    event->rule_matched = FALSE;
    event->directive_matched = FALSE;

  } /* backlogs loop */
  sim_engine_unlock_backlogs (event->engine);
}
static void
sim_correlation_match_directives(SimEvent   *event, GList *backlogs_matches)
{
  GPtrArray    *plugin_directives;
  plugin_directives = sim_engine_get_directives_by_plugin_id (event->engine, event->plugin_id);
  if (plugin_directives)
  {
    sim_correlation_match_directives_array (event, backlogs_matches, plugin_directives);
  }
  if (plugin_directives)
    g_ptr_array_unref (plugin_directives);
  plugin_directives = sim_engine_get_directives_by_plugin_id (event->engine, SIM_PLUGIN_ID_ANY);
  if (plugin_directives)
  {
    sim_correlation_match_directives_array (event, backlogs_matches, plugin_directives);
  }
  if (plugin_directives)
    g_ptr_array_unref (plugin_directives);
 

}
/**
 * sim_correlation_match_directives:
 * @event: a #SimEvent
 * @backlogs_matches: backlogs_matches list
 *
 * Check if event matches with all directives with
 *  @event plugin_id in @event context
 */
static void
sim_correlation_match_directives_array (SimEvent   *event,
                                  GList *backlogs_matches,
                                  GPtrArray *plugin_directives)
{
  SimDirective *directive;
  SimEvent     *directive_event;
  gint          directive_id;
  guint         i;

  for (i = 0; i < plugin_directives->len; i++)
  {
    directive_event = NULL;
    directive = (SimDirective *)g_ptr_array_index (plugin_directives, i);
    directive_id = sim_directive_get_id (directive);

    /* Check the backlogs_matches.
       If the event has already matched with a backlog cloned from this directive then ignore it */
    if (g_list_find (backlogs_matches, GINT_TO_POINTER (directive_id)))
      continue;

    /* Check timetable restrictions */
    if (sim_directive_check_timetable_restriction (directive, event))
    {
      ossim_debug ("%s: Ignoring directive %s by timetable", __func__, sim_directive_get_name (directive));
      continue;
    }

    /* Try to match event with directive */
    if (sim_directive_match_by_event (directive, event))
    {
      SimDirective *backlog;
      SimRule      *rule_root;
      GNode        *node_root;
      time_t        time_last = time (NULL); // gets the actual time so we can update the rule

      ossim_debug ("sim_directive_match_by_event TRUE. event->id: %s, directive id: %d",
                   sim_uuid_get_string (event->id), directive_id);

      /* Create a backlog from directive */
      backlog = sim_directive_clone (directive, event->engine);
      sim_directive_backlog_id_generate (backlog);

      /* Gets the root node ad root rule (data) from backlog */
      node_root = sim_directive_get_curr_node (backlog);
      rule_root = sim_directive_get_curr_rule (backlog);

      sim_rule_set_time_last (rule_root, time_last);
        sim_directive_update_backlog_first_last_ts(backlog, event);

      /* Set the event data to the rule_root.
         This will copy some fields from event (src_ip, port..) into the backlog */
      sim_rule_set_event_data (rule_root, event);

      SimUuid *backlog_id = sim_directive_get_backlog_id (backlog);
      if(event->backlog_id)
        g_object_unref (event->backlog_id);
      event->backlog_id = g_object_ref (backlog_id);
      event->belongs_to_alarm = TRUE;
      event->rule_matched = TRUE;
      event->directive_matched = TRUE;

      sim_directive_alarm_stats_update (backlog, event);

      /* If the node hasn't got children then the directive has match (completed) */
      if (G_NODE_IS_LEAF (node_root))
      {
        sim_directive_backlog_set_matched (backlog, TRUE);

        /* Now we need to create a new directive event,
           fill it with data from the directive which made the event match */
        directive_event = sim_correlation_new_directive_event (event, backlog);
      }
      else // the node has some children...
      {
        /* Update data of children nodes */
        sim_correlation_update_children_nodes (node_root, backlog, time_last);

        /* Append new backlog to context */
        sim_engine_append_backlog (event->engine, backlog);

        /* Only create a directive event when the event causes an alarm to avoid
         * re-inyect a lot of directive events.
         * If this directive event matches later with a policy that has qualification
         * (risk assesment) disabled it will not generate an alarm. */
        if (sim_correlation_causes_alarm (directive ,rule_root, event))
          directive_event = sim_correlation_new_directive_event (event, backlog);
      }

      /* Re-inyect new directive event */
      if (directive_event)
      {
        if (directive_event->plugin_sid == SIM_DIRECTIVE_PULSE_ID)
        {
        /* Well, we can't copy the filename member. We need it for pulse_id */
          SimRule *rule = sim_directive_get_curr_rule (backlog);
          if (directive_event->filename)
            g_free (directive_event->filename);
          directive_event->filename = g_strdup (sim_rule_get_pulse_id (rule));
        }

        sim_session_prepare_and_insert_non_block (directive_event);
      }

      /* DB Inserts */
      sim_db_insert_backlog (ossim.dbossim, backlog);
      sim_db_insert_backlog_event (ossim.dbossim, backlog, event, sim_rule_get_level (rule_root));
      sim_db_insert_alarm_view_tables (ossim.dbossim, event);
      if (directive_event)
      {
        sim_db_insert_backlog_event (ossim.dbossim, backlog, directive_event, sim_rule_get_level (rule_root));
        sim_db_insert_alarm_view_tables (ossim.dbossim, directive_event);

        sim_event_unref (directive_event);
      }

      g_object_unref (backlog);

    } /* if event match with directive */

    event->rule_matched = FALSE;
    event->directive_matched = FALSE;

  } /* directives loop */

}

/**
 * sim_correlation_new_directive_event:
 * @event: a #SimEvent
 * @backlog: a #SimDirective backlog
 *
 * Returns: new #SimEvent directive event
 */
static SimEvent *
sim_correlation_new_directive_event (SimEvent     *event,
                                     SimDirective *backlog)
{
  SimEvent *new_event;
  GNode    *rule_node;
  SimRule  *rule_curr;
  SimInet  *inet_aux;
  SimUuid  *backlog_id;
  GHashTableIter iter;
  gpointer key, value;
  const gchar    *pulse_id = NULL;
  rule_node = sim_directive_get_curr_node (backlog);
  rule_curr = sim_directive_get_curr_rule (backlog);

  /* Create new directive event */
  new_event = sim_event_new_from_type (SIM_EVENT_TYPE_DETECTOR);
  new_event->is_correlated = TRUE;

  /* Time */
  new_event->time = time (NULL);
  new_event->tzone = 0.0;
  new_event->time_str = g_new (gchar, TIMEBUF_SIZE);
  sim_time_t_to_str (new_event->time_str, new_event->time);
  sim_directive_update_backlog_first_last_ts(backlog, new_event);

  /* Not alarm */
  new_event->alarm = FALSE;
  new_event->belongs_to_alarm = TRUE;

  /* Context */
  new_event->context = sim_container_get_engine_ctx (ossim.container);

  /* Sensor */
  if (event->sensor)
  {
    new_event->sensor = g_object_ref (event->sensor);
  }
  else
  {
    inet_aux = sim_rule_get_sensor (rule_curr);
    if (inet_aux)
      new_event->sensor = g_object_ref (inet_aux);
  }

  /* Server */
  new_event->server = g_strdup (sim_server_get_name (ossim.server));

  /* interface */
  if (event->interface)
    new_event->interface = g_strdup (event->interface);

  /* Plugin id/sid */
  new_event->plugin_id = SIM_PLUGIN_ID_DIRECTIVE;
  new_event->plugin_sid = sim_directive_get_id(backlog);

  /* Src */
  inet_aux = sim_rule_get_src_ia (rule_curr);
  if (inet_aux)
    new_event->src_ia = g_object_ref (inet_aux);
  if (event->src_id)
    new_event->src_id = g_object_ref (event->src_id);

  /* Dst */
  inet_aux = sim_rule_get_dst_ia (rule_curr);
  if (inet_aux)
    new_event->dst_ia = g_object_ref (inet_aux);
  if (event->dst_id)
    new_event->dst_id = g_object_ref (event->dst_id);

  /* Assets */
  new_event->asset_src = sim_rule_get_src_max_asset (rule_curr);
  new_event->asset_dst = sim_rule_get_dst_max_asset (rule_curr);

  /* Ports and protocol */
  new_event->src_port = sim_rule_get_src_port (rule_curr);
  new_event->dst_port = sim_rule_get_dst_port (rule_curr);
  new_event->protocol = sim_rule_get_protocol (rule_curr);

  /* Data */
  new_event->data = sim_directive_backlog_to_string (backlog);

  /* As the event generated belongs to the directive, the event must know */
  backlog_id = sim_directive_get_backlog_id (backlog);
  new_event->backlog_id = g_object_ref (backlog_id);

  /* Rule reliability */
  if (sim_rule_get_rel_abs (rule_curr))
    new_event->reliability = sim_rule_get_reliability (rule_curr);
  else
    new_event->reliability = sim_rule_get_reliability_relative (rule_node);

  /* Directive Priority */
  new_event->priority = sim_directive_get_priority (backlog);

  /* Copy the event data to the new "directive" event */
  new_event->filename = g_strdup (event->filename);
  new_event->username = g_strdup (event->username);
  new_event->password = g_strdup (event->password);
  new_event->userdata1 = g_strdup (event->userdata1);
  new_event->userdata2 = g_strdup (event->userdata2);
  new_event->userdata3 = g_strdup (event->userdata3);
  new_event->userdata4 = g_strdup (event->userdata4);
  new_event->userdata5 = g_strdup (event->userdata5);
  new_event->userdata6 = g_strdup (event->userdata6);
  new_event->userdata7 = g_strdup (event->userdata7);
  new_event->userdata8 = g_strdup (event->userdata8);
  new_event->userdata9 = g_strdup (event->userdata9);
  new_event->rep_prio_src = event->rep_prio_src;
  new_event->rep_prio_dst = event->rep_prio_dst;
  new_event->rep_rel_src = event->rep_rel_src;
  new_event->rep_rel_dst = event->rep_rel_dst;
  new_event->str_rep_act_src = g_strdup (event->str_rep_act_src);
  new_event->str_rep_act_dst = g_strdup (event->str_rep_act_dst);
  if (event->rep_act_src)
  {
    new_event->rep_act_src = g_hash_table_new_full(g_direct_hash, g_direct_equal, NULL, NULL);
    g_hash_table_iter_init (&iter, event->rep_act_src);
    while (g_hash_table_iter_next (&iter, &key, &value))
      g_hash_table_insert(new_event->rep_act_src, key, value);
  }

  if (event->rep_act_dst)
  {
    new_event->rep_act_dst = g_hash_table_new_full(g_direct_hash, g_direct_equal, NULL, NULL);
    g_hash_table_iter_init (&iter, event->rep_act_dst);
    while (g_hash_table_iter_next (&iter, &key, &value))
      g_hash_table_insert(new_event->rep_act_dst, key, value);
  }

  /* Calculate risk */
  if (sim_directive_get_groupalarm (backlog))
  {
    /* This alarm can be duplicated */
    if (sim_correlation_calculate_risk (new_event) >= 1.0)
        sim_correlation_group_alarm_process (new_event, backlog);
  }

  new_event->alarm_stats = sim_directive_alarm_stats_generate (backlog);
  /* Pulse info */
  /* This is a bit tricky: Only need to copy the IOCs that coincide with the current pulse */
  if ((pulse_id = sim_rule_get_pulse_id (rule_curr)) != NULL)
  {
    /* Copy the IOC from event to directive BUT only the one that match!!! */
    GPtrArray *array;
    if ((array = g_hash_table_lookup (event->otx_data, (gpointer)pulse_id)))
    {
      guint i;
      for (i = 0 ;i < array->len ;i++)
        sim_event_add_ioc (new_event, pulse_id,  g_ptr_array_index (array, i));
    }
  }

  return new_event;
}


/**
 * sim_correlation_update_children_nodes:
 * @node_root: *GNode root
 * @backlog: a #SimDirective backlog
 * @time_last: a time_t
 *
 * Updates time and vars in all rules in @node_root children nodes
 * and checks if any rule has type MONITOR
 *
 */
static void
sim_correlation_update_children_nodes (GNode        *node_root,
                                       SimDirective *backlog,
                                       time_t        time_last)
{
  GNode *children = node_root->children;
  while (children)
  {
    SimRule *rule = children->data;

    /* Update time in all the children */
    sim_rule_set_time_last (rule, time_last);

    /* Fill children data with backlog data from the node level specified */
    sim_directive_set_rule_vars (backlog, children); //this can be done only in children nodes, not in the root one.

    /* Rules with type MONITOR */
    if (rule->type == SIM_EVENT_TYPE_MONITOR)
    {
      ossim_debug("%s: Monitor rule 2 : %p from directive %s at level %d", __func__,
                  rule, sim_directive_get_name (backlog), sim_rule_get_level (rule));

      sim_container_push_monitor_rule (ossim.container, rule);
    }

    children = children->next;
  }
}

/**
 * sim_correlation_check_monitor_rule:
 * @rule_node: pointer to GNode
 *
 * Check if any @rule_node children has type monitor
 * and push them to the monitor rule queue.
 */
static void
sim_correlation_check_monitor_rule (GNode *rule_node)
{
  GNode *children = rule_node->children;
  while (children)
  {
    SimRule *rule = children->data;

    if (rule->type == SIM_EVENT_TYPE_MONITOR)
    {
      ossim_debug ("%s: Monitor rule at level %d", __func__, sim_rule_get_level (rule));

      sim_container_push_monitor_rule (ossim.container, rule);
    }

    children = children->next;
  }
}

/**
 * sim_correlation_causes_alarm:
 * @priority:
 * @reliability:
 * @asset
 *
 * Apply the formula (asset * reliability * priority) / 25
 * and check if it causes an alarm ( >1 )
 *
 * Returns: %TRUE if calculated risk is greater than '1',
 *  %FALSE otherwise
 */
static gboolean
sim_correlation_causes_alarm (SimDirective *directive,
                              SimRule      *rule,
                              SimEvent     *event)
{
  gint priority = sim_directive_get_priority (directive);
  gint reliability = sim_rule_get_reliability (rule);
  gint asset = sim_context_get_inet_asset (event->context, event->dst_ia);

  gdouble risk = ((gdouble)(asset * reliability * priority)) / 25;

  /* If risk >= 1.0 then it causes an alarm */
  return (risk >= 1.0);
}

/**
 * sim_correlation_calculate_risk:
 * @event: a #SimEvent
 *
 * Returns: risk calculated for @event
 */
static float
sim_correlation_calculate_risk (SimEvent *event)
{
  float         risk   = 0.0;
  float         risk_c = 0.0;
  float         risk_a = 0.0;
  unsigned int  asset;

  g_return_val_if_fail (SIM_IS_EVENT (event), 0.0);

  /* Only update the C & A if the event has src and dst ip (MAC or OS event hasn't dst ip) */
  if (!SIM_IS_INET (event->src_ia) ||
      !SIM_IS_INET (event->dst_ia) ||
      sim_inet_is_none (event->dst_ia))
  {
    g_message ("Can't calculate the risk for event, event->id = %s", sim_uuid_get_string (event->id));
    return 0.0;
  }

  /* Compromise Risk */
  asset = sim_context_get_inet_asset (event->context, event->src_ia);
  risk_c = floor (((double) (event->priority * asset * event->reliability)) / 25);
  risk_c = CLAMP (risk_c, 0, 10); // Ensures risk is between 0 and 10

  /* Attack Risk */
  asset = sim_context_get_inet_asset (event->context, event->dst_ia);
  risk_a = floor (((double) (event->priority * asset * event->reliability)) / 25);
  risk_a = CLAMP (risk_a, 0, 10); // Ensures risk is between 0 and 10

  risk = MAX (risk_a, risk_c);
  ossim_debug ("Event %s: risk_a:%f risk_c:%f Risk:%f", sim_uuid_get_string (event->id), risk_a, risk_c, risk);

  return risk;
}

/**
 * sim_correlation_group_alarm_process:
 * @event: a #SimEvent
 * @backlog: a #SimDirective backlog
 *
 */
static void
sim_correlation_group_alarm_process (SimEvent     *event,
                                     SimDirective *backlog)
{
  char *temp;
  GString *key = NULL;
  GString  *alarmkey = NULL;
  gchar *c_key  = NULL;
  GChecksum *sum = NULL;
  gboolean md5 = FALSE;
  SimGroupAlarm *groupalarm;

  key = g_string_new ("");
  alarmkey = g_string_new ("");
  sum = g_checksum_new (G_CHECKSUM_SHA1);

  g_string_printf (key, "plugin_id=%u|plugin_sid=%u", event->plugin_id, event->plugin_sid);

  /* Alloc de MD5 HASH */

  /* src_ip */
  if (sim_directive_get_group_alarm_by_src_ip (backlog))
  {
    temp = sim_inet_get_canonical_name (event->src_ia);
    g_string_append_printf (key, "|src_ip=%s", temp);
    g_free (temp);
    md5 = TRUE;
  }

  /* dst_ip */
  if (sim_directive_get_group_alarm_by_dst_ip (backlog))
  {
    temp = sim_inet_get_canonical_name (event->dst_ia);
    g_string_append_printf (key, "|dst_ip=%s", temp);
    g_free (temp);
    md5 = TRUE;
  }

  /* src_port */
  if (sim_directive_get_group_alarm_by_src_port (backlog))
  {
    g_string_append_printf (key, "|src_port=%u", event->src_port);
    md5 = TRUE;
  }

  /* dst_port */
  if (sim_directive_get_group_alarm_by_dst_port (backlog))
  {
    g_string_append_printf (key, "|dst_port=%u", event->dst_port);
    md5 = TRUE;
  }

  if (md5)
  {
    g_checksum_update (sum, (guchar *)key->str, -1);

    /* Ok, c_key points to the C string of the digest */
    c_key = (gchar*)g_checksum_get_string (sum);

    groupalarm = sim_engine_lookup_group_alarm (event->engine, c_key);
    if (groupalarm)
    {
      ossim_debug ("%s: Incrementing counter", __func__);
      sim_group_alarm_inc_count (groupalarm);

      event->is_correlated = TRUE;
    }
    else
    {
      groupalarm = sim_group_alarm_new (sim_directive_get_group_alarm_timeout (backlog), key->str);
      sim_engine_append_group_alarm (event->engine, groupalarm, c_key);
    }

    event->groupalarmsha1 = sim_group_alarm_get_id (groupalarm);

    g_object_unref (groupalarm);
  }
  else
  {
    g_message ("Called %s without specified fields filter\n", __func__);
  }

  /* Free resources */
  if (key)
    g_string_free (key, TRUE);
  if (alarmkey)
    g_string_free (alarmkey, TRUE);
  if (sum)
    g_checksum_free (sum);
}
