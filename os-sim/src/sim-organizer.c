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

#include "sim-organizer.h"

#include <omp.h>
#include <math.h>
#include <time.h>
#include <config.h>
#include <syslog.h>
#include <glib/gprintf.h>
#include <unistd.h>

#include "os-sim.h"
#include "sim-enums.h"
#include "sim-connect.h"
#include "sim-server.h"
#include "sim-host.h"
#include "sim-net.h"
#include "sim-plugin-sid.h"
#include "sim-policy.h"
#include "sim-rule.h"
#include "sim-directive-group.h"
#include "sim-directive.h"
#include "sim-container.h"
#include "sim-debug.h"
#include "sim-util.h"
#include "sim-groupalarm.h"
#include "sim-db-command.h"
#include "sim-correlation.h"
#include "sim-alarm-stats.h"
#include "sim-uuid.h"

extern SimMain ossim;

#define WAIT_FOR_ALARMS_TIME  3 // in secs

enum
{
  DESTROY, LAST_SIGNAL
};

/*
 * Private Data
 */
struct _SimOrganizerPrivate
{
  SimConfig *config;

  /* Data for sim_scheduler_show_stats */
  gint  events_pushed;
  guint events_popped;
};

static gpointer parent_class = NULL;


/*
 *  Private functions
 */
static gpointer   sim_organizer_thread_monitor_rule             (gpointer      data);

/* Run Role */
static void       sim_organizer_run_role                        (SimOrganizer *organizer,
                                                                 SimEvent     *event);
static void       sim_organizer_store_event                     (SimOrganizer *organizer,
                                                                 SimEvent     *event);

/* Risk levels */
static void       sim_organizer_risk_levels                     (SimEvent     *event);

/* insert functions */
static void sim_organizer_snort_idm_data_insert                 (SimDatabase  *db_snort,
                                                                 SimEvent     *event);
static void sim_organizer_snort_pulses_insert                   (SimDatabase *db_snort,
                                                                 SimEvent * event);
/* GType Functions */

static void
sim_organizer_impl_dispose(GObject *gobject)
{
  ossim_debug ("Ending organizer2");
  G_OBJECT_CLASS(parent_class)->dispose(gobject);
}

static void
sim_organizer_impl_finalize(GObject *gobject)
{
  ossim_debug ("Ending organizer");

  SimOrganizer *organizer = SIM_ORGANIZER (gobject);

  g_free (organizer->_priv);

  G_OBJECT_CLASS(parent_class)->finalize(gobject);
}

static void
sim_organizer_class_init(SimOrganizerClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS(class);

  parent_class = g_type_class_peek_parent(class);

  object_class->dispose = sim_organizer_impl_dispose;
  object_class->finalize = sim_organizer_impl_finalize;
}

static void
sim_organizer_instance_init(SimOrganizer *organizer)
{
  organizer->_priv = g_new0 (SimOrganizerPrivate, 1);

  organizer->_priv->config = NULL;

  /* queues counters */
  organizer->_priv->events_pushed = 0;
  organizer->_priv->events_popped = 0;
}

/* Public Methods */

GType
sim_organizer_get_type(void)
{
  static GType object_type = 0;

  if (!object_type)
  {
    static const GTypeInfo type_info =
      { sizeof(SimOrganizerClass), NULL, NULL,
        (GClassInitFunc) sim_organizer_class_init, NULL, NULL, /* class data */
        sizeof(SimOrganizer),

        0, /* number of pre-allocs */
        (GInstanceInitFunc) sim_organizer_instance_init, NULL /* value table */
      };


    object_type = g_type_register_static(G_TYPE_OBJECT, "SimOrganizer",
                                         &type_info, 0);
  }

  return object_type;
}

/*
 *
 *
 *
 *
 */
SimOrganizer*
sim_organizer_new(SimConfig *config)
{
  SimOrganizer *organizer = NULL;

  g_return_val_if_fail(SIM_IS_CONFIG (config), NULL);

  organizer = SIM_ORGANIZER (g_object_new (SIM_TYPE_ORGANIZER, NULL));
  organizer->_priv->config = config;

  return organizer;
}

/*
 * Send the monitor rules to the agent
 */
static gpointer
sim_organizer_thread_monitor_rule(gpointer data)
{
  SimRule *rule;

  // unused parameter
  (void) data;

  while (TRUE)
  {
    rule = (SimRule *) sim_container_pop_monitor_rule(ossim.container);//get and remove the last monitor rule in queue
    ossim_debug(
      "sim_organizer_thread_monitor_rule: %p", rule);
    //    sim_rule_print (rule);

    if (!rule)
      continue;

    // TODO: with taxonomy we can have multiple plugin ids or ANY
    SIM_WHILE_HASH_TABLE (sim_rule_get_plugin_ids (rule))
      sim_server_push_session_plugin_command(ossim.server, SIM_SESSION_TYPE_SENSOR, GPOINTER_TO_INT (_key), rule);
  }

  return NULL;
}

//Delete the DB backlogs
static gpointer
sim_organizer_thread_delete_backlogs (gpointer data)
{
  SimUuid *backlog_id;
  gchar   *backlog_id_alarm_stats;
  gchar   *alarm_stats;

  // unused parameter
  (void) data;

  while (TRUE)
  {
    backlog_id_alarm_stats = (gchar *) sim_container_pop_delete_backlog_from_db (ossim.container);//get and remove the last backlog in queue
    backlog_id = sim_uuid_new_from_uuid ((uuid_t *) backlog_id_alarm_stats);
    alarm_stats = backlog_id_alarm_stats + 16;

    sleep (WAIT_FOR_ALARMS_TIME); // ...to wait for pending alarms to be inserted

    sim_directive_delete_database_backlog (backlog_id, alarm_stats);

    g_object_unref (backlog_id);
    g_free (backlog_id_alarm_stats);
  }

  return NULL;
}

//FIXME: Execute this in a separate thread?
/*
 * This will store alarms in syslog
 */
void
sim_organizer_send_alarms_to_syslog(SimEvent *event)
{
  gchar *srcip = sim_inet_get_canonical_name (event->src_ia);
  gchar *dstip = sim_inet_get_canonical_name (event->dst_ia);

  openlog("Alienvault", LOG_NDELAY, LOG_LOCAL7);

  SimPluginSid *pluginsid;
  gchar *aux = NULL;
  gchar *aux2 = NULL;
  pluginsid = sim_context_get_plugin_sid (event->context, event->plugin_id, event->plugin_sid);

  if (event->log)
  {
    // FIXME: this won't print full strings with embedded '\0' characters
    aux = g_strdup(event->log->str);
    if (event->data)
    {
      aux2 = g_strconcat(aux, event->data, NULL);
      syslog(LOG_INFO, "%s -- SRC_IP: %s , DST_IP: %s, Alarm: %s",
             sim_plugin_sid_get_name(pluginsid), srcip, dstip, aux2);
      g_free(aux2);
    }
    else
    {
      syslog(LOG_INFO, "%s -- SRC_IP: %s , DST_IP: %s, Alarm: %s",
             sim_plugin_sid_get_name(pluginsid), srcip, dstip, aux);
    }
    g_free(aux);
  }
  else if (event->data)
    syslog(LOG_INFO, "%s -- SRC_IP: %s , DST_IP: %s, Alarm: %s",
           sim_plugin_sid_get_name(pluginsid), srcip, dstip, event->data);

  g_object_unref (pluginsid);
  closelog();
  g_free(srcip);
  g_free(dstip);

}

/**
 * sim_organizer_run:
 *
 *
 */
void
sim_organizer_run (SimOrganizer *organizer)
{
  SimEvent *event = NULL;

  GThread *thread;
  GThread *ar_thread;

//  int total = 0;

  g_return_if_fail (SIM_IS_ORGANIZER (organizer));

  // New thread for the Monitor requests. Rules will be inserted into a queue, and then extracted in this thread
  thread = g_thread_new("sim_organizer_thread_monitor_rule", sim_organizer_thread_monitor_rule, NULL);
  g_return_if_fail(thread);

  ar_thread = g_thread_new("sim_connect_send_alarm", sim_connect_send_alarm, organizer->_priv->config);
  g_return_if_fail(ar_thread);

  //Thread to delete backlogs
  thread = g_thread_new("sim_organizer_thread_monitor_rule",sim_organizer_thread_delete_backlogs, NULL);

  g_return_if_fail(thread);


  //  ossim_debug ( "Snort database storage Thread is at %x", thread_db_snort);
  ossim_debug ("%p:OSSIM alarms database storage Thread is at %p", g_thread_self(), thread);

  //Update idm status
//  sim_db_update_config_value (ossim.dbossim, "enable_idm", ossim.config->id)
  gchar *query = g_strdup_printf("REPLACE INTO config(conf,value) VALUES (\"enable_idm\",\"%d\")", ossim.config->idm.activated);
  sim_database_execute_no_query (ossim.dbossim, query);
  g_free (query);

  //Main Loop
  while (TRUE)
  {
    event = sim_container_pop_event (ossim.container); //gets and remove the last event in queue

    //FIXME & WARNING!!!! : When ossim-server is receiving events at a really high speed, sometimes "event" structure is corrupt.
    //here we loose some memory bytes with each one, but the process doesn't crash. This MUST be fixed ASAP
    if (!SIM_IS_EVENT (event))
    {
      ossim_debug ("%s: Error SIM_IS_EVENT norl", __func__);
      continue;
    }

    ossim_debug ("%s: Pop-event: id = %s context = %s is_stored = %u plugin_id = %u plugin_sid = %u alarm = %u",
                 __func__, sim_uuid_get_string (event->id), sim_uuid_get_string (sim_context_get_id (event->context)),
                 event->is_stored, event->plugin_id,
                 event->plugin_sid, event->alarm);

    if (event->type == SIM_EVENT_TYPE_NONE)
    {
      ossim_debug ("%s: OSSIM SIM_EVENT_TYPE_NONE !", __func__);
      sim_event_unref (event);
      continue;
    }

    ossim_debug ("%s: event: %lx data: %s rel: %d prio %d", __func__,
                 (glong)event, event->data, event->reliability, event->priority);

    /* Push event to Run Role queues */
    sim_organizer_run_role (organizer, event);
  }

  return;
}

/*
 *
 * Update the reliability of all the plugin_sids of the dst_ia from the event. *
 * also, if the event has plugin_sids associated, the event is transformed into an alarm.
 * this function has sense just with events with a defined dst. and those events has to have some relationship
 * with others (see sim_container_db_host_get_plugin_sids_ul())
 */
void
sim_organizer_correlation_plugin (SimOrganizer *organizer,
                                  SimEvent     *event)
{
  SimPluginSid * plugin_sid;
  gint           plugin_id;
  gboolean       aux_nessus = FALSE;
  gboolean       aux_generic = FALSE;

  g_return_if_fail (SIM_IS_ORGANIZER (organizer));
  g_return_if_fail (SIM_IS_EVENT (event));

  if (!event->dst_ia)
    return;

  plugin_sid = sim_context_get_event_host_plugin_sid (event->context, event);
  if (!plugin_sid)
  {
    ossim_debug ("%s: Cannot retrieve host_plugin_sids.", __func__);
    return;
  }

  plugin_id = sim_plugin_sid_get_plugin_id (plugin_sid);

  //nessus plugin id defined in sim-event.h
  if (plugin_id == SIM_PLUGIN_ID_NESSUS) //match nessus attack
  {
    event->reliability = 10;
    event->is_reliability_set = TRUE;
    event->correlation |= EVENT_MATCH_CROSS_CORR;
    ossim_debug ("%s: Match! Nessus vuln found. new reliability: 10", __func__);
    aux_nessus = TRUE;
  }
  else //this is a "generic" type. This will match any new type that the user defines.
  { //For example, a user may want to do cross-correlation between 2 new plugins, say 25000 and 25001.
    //He need to insert plugin_id 25001 and plugin_sid 1 (i.e.) into host_plugin_sid. After that he needs to fill the
    //plugin_reference table wiuth data like (25000, 1, 25001, 22), so plugin sid 1 has a relationship with plugin_sid 22.
    //If the correlation is done in this way, we set the reliability to 10.

    event->reliability += sim_plugin_sid_get_reliability (plugin_sid);
    event->is_reliability_set = TRUE;
    event->correlation |= EVENT_MATCH_CROSS_CORR;
    ossim_debug ("%s: Match! plugin id: %d. Succesfull attack. new reliability: %d",
                 __func__, plugin_id, event->reliability);

    aux_generic = TRUE;
  }

  if (aux_nessus || aux_generic) //we know that it's a real attack thanks to nessus or generic cross-correlation.
  {
    ossim_debug ("%s: aux_nessus || aux_generic", __func__);
  }

#if 0
  if ((!aux_os) && aux_os_tested) //if the OS doesn't matches, nothing else matters
  {
    ossim_debug ("%s: aux_OS", __func__);
    break;
  }
#endif

  if (event->reliability > 10)
    event->reliability = 10;

  ossim_debug ("%s: end", __func__);
}

/*
 * 1.- Always modifies the priority if the event belongs to a policy
 * 2.- Modify priority/realibility if it is not set
 *
 * Remember that event could be a directive_event. In that case the plugin_id will be 1505 and the
 * plugin_sid will be the directive id that matched. That directive_id is inserted into plugin_sid table when the
 * server starts.
 * This function returns 0 on error.
 */
gboolean
sim_organizer_reprioritize(SimOrganizer *organizer, SimEvent *event,
                           SimPolicy *policy)
{
  gint aux;
  SimPluginSid *plugin_sid = NULL;

  g_return_val_if_fail(SIM_IS_ORGANIZER (organizer), FALSE);
  g_return_val_if_fail(SIM_IS_EVENT (event), FALSE);

  //get plugin-sid objects from the plugin_id and plugin_sid of the event
  plugin_sid = sim_context_get_plugin_sid (event->context, event->plugin_id, event->plugin_sid);
  
  if (!event->plugin_id)
  {
    g_message ("Error: Plugin %d, PluginSid %d nonexistent. "
               "Please insert info for that plugin into DB. Event rejected",
               event->plugin_id, event->plugin_sid);
    return FALSE;
  }

  //if the event has been prioritized in the children server, the master server can't modify it. Apart, the master server just
  //can modify the Priority in case the event matches with Policy.
  if (policy)
  {
    if ((aux = sim_policy_get_priority (policy)) == -1) //-1 in policy means that it won't affect to the priority
    {
      if (!plugin_sid || (aux = sim_plugin_sid_get_priority (plugin_sid)) == -1) //if -1 (return value), priority doesn't exists.
      {
        g_message ("Error: Unable to fetch priority for plugin id %d, plugin sid %d",
                   event->plugin_id, event->plugin_sid);
        return FALSE;
      }
    }

    event->priority = aux >= 0 ? aux : 0;
    event->is_priority_set = TRUE;

    ossim_debug ("%s: Policy Match. new priority: %d", __func__, event->priority);
  }
  else //set the priority from plugin DB (if not prioritized early in other server down in architecture).
  {
    if ((!event->is_priority_set))
    {
      if (!plugin_sid || (aux = sim_plugin_sid_get_priority (plugin_sid)) == -1)
      {
        g_message ("Error: Unable to fetch priority for plugin id %d, plugin sid %d", event->plugin_id, event->plugin_sid);
        return 0;
      }

      event->priority = aux;
      event->is_priority_set = TRUE;

      ossim_debug ("%s: Policy Doesn't match, priority: %d", __func__, event->priority);
    }
  }

  // Get the reliability of the plugin sid. There is a reliability inside the directive, but the
  // reliability from the plugin is more important. So we usually try to get the plugin reliability from DB
  // and assign it to the event. If the event is prioritized we can't change the Reliability.
  if ((event->plugin_id != SIM_PLUGIN_ID_DIRECTIVE) && (!event->is_reliability_set))
  {
    if ((aux = sim_plugin_sid_get_reliability(plugin_sid)) != -1)
    {
      event->reliability = aux;
      event->is_reliability_set = TRUE;
    }
  }

  ossim_debug ("%s: reliability: %d", __func__, event->reliability);

  g_object_unref (plugin_sid);

  return TRUE;

  //FIXME: When the event is a directive_event (plugin_id 1505), inside the event->data appears (inserted in sim_organizer_correlation)
  //with the "old" priority. Its needed to re-write the data and modify the priority (if the policy modifies it, of course).
}


/**
 * sim_organizer_risk_levels:
 * @organizer: #SimOrganizer
 * @event: a #SimEvent
 *
 * Calculate Risk. If Risk >= 1 then transform the event into an alarm
 */
static void
sim_organizer_risk_levels (SimEvent *event)
{
  /* Compromise risk */
  event->risk_c = floor (((double) (event->priority * event->asset_src * event->reliability)) / 25);
  event->risk_c = CLAMP (event->risk_c, 0, 10); // Ensures risk is between 0 and 10

  /* Alarm check */
  if (event->risk_c >= 1)
    event->alarm = TRUE;

  ossim_debug ("%s: priority:%d asset:%d reliability:%d risk_c:%f", __func__,
               event->priority, event->asset_src, event->reliability, event->risk_c);

  /* Attack risk */
  event->asset_dst = sim_context_get_inet_asset (event->context, event->dst_ia);

  event->risk_a = floor (((double) (event->priority * event->asset_dst * event->reliability)) / 25);
  event->risk_a = CLAMP (event->risk_a, 0, 10); // Ensures risk is between 0 and 10

  /* Alarm check */
  if (event->risk_a >= 1)
    event->alarm = TRUE;

  ossim_debug ("%s: priority:%d asset:%d reliability:%d risk_a:%f", __func__,
               event->priority, event->asset_dst, event->reliability, event->risk_a);
}

/*
 * This is called from sim_organizer_snort. It can be called with a snort event(plugin_name will be NULL)
 * or another event (plugin_name will be something like: "arp_watch: New Mac".
 *
 *
 */
gint
sim_organizer_snort_sensor_get_sid (SimDatabase *db_snort,
                                    SimUuid     *sensor_id,
                                    SimEvent    *event)
{
  GdaDataModel *dm;
  const GValue *value;
  gchar *tmp, *e_interface;
  guint sid = 0;
  gchar *query;
  gchar *insert;

  g_return_val_if_fail (SIM_IS_DATABASE (db_snort), 0);
  g_return_val_if_fail (SIM_IS_UUID (sensor_id), 0);

  if (event->interface)
  {
    tmp = sim_database_str_escape (db_snort, event->interface, 0);
    e_interface = g_strdup_printf ("'%s'", tmp);
    g_free (tmp);
  }
  else
  {
    e_interface = NULL;
  }

  if (event->device && e_interface)
    query = g_strdup_printf ("SELECT id FROM device WHERE sensor_id = %s AND interface LIKE %s AND device_ip = %s",
                             sim_uuid_get_db_string (sensor_id),
                             e_interface,
                             sim_inet_get_db_string (event->device));
  else if (event->device)
    query = g_strdup_printf ("SELECT id FROM device WHERE sensor_id = %s AND device_ip = %s",
                             sim_uuid_get_db_string (sensor_id),
                             sim_inet_get_db_string (event->device));
  else
    query = g_strdup_printf ("SELECT id FROM device WHERE sensor_id = %s AND device_ip IS NULL",
                             sim_uuid_get_db_string (sensor_id));

  dm = sim_database_execute_single_command (db_snort, query);
  if (dm)
  {
    if (gda_data_model_get_n_rows (dm))
    {
      value = gda_data_model_get_value_at (dm, 0, 0, NULL);
      sid = g_value_get_int (value);
      ossim_debug("%s: sid1: %u. Query: %s", __func__, sid, query);
    }
    else //if it's the first time that this kind of event is saw
    {
      insert = g_strdup_printf ("INSERT INTO device (sensor_id, interface, device_ip) "
                                "VALUES (%s, %s, %s)",
                                sim_uuid_get_db_string (sensor_id),
                                e_interface,
                                sim_inet_get_db_string (event->device));

      gint ret = sim_database_execute_no_query (db_snort, insert);

      ossim_debug ("%s: sid2:ret: %d -  %u. Query: %s", __func__, ret, sid, insert);
      sid = sim_organizer_snort_sensor_get_sid (db_snort, sensor_id, event);
      ossim_debug ("%s: sid3: %u. Query: %s", __func__, sid, insert);

      g_free (insert);
    }

    g_object_unref (dm);
  }
  else
    g_message ("SENSOR SID DATA MODEL ERROR");

  g_free (query);
  g_free (e_interface);

  return sid;
}

/*
 * update acid_event cache, making the terrible cache joins useless
 */

void
sim_organizer_snort_event_update_acid_event(SimDatabase *db_snort,
                                            SimEvent *event)
{
  g_return_if_fail(SIM_IS_DATABASE (db_snort));
  g_return_if_fail (event->is_snort_stored == FALSE);

  gchar *timestamp = NULL;
  gchar *query;
  gchar *values;
  SimUuid *ctx;
  GdaConnection *conn;
  gchar *e_src_hostname = NULL;
  gchar *e_dst_hostname = NULL;
  gchar *src_mac = NULL, *dst_mac = NULL;

  conn = sim_database_get_conn (db_snort);

  if (event->time_str)
  {
    timestamp = sim_str_escape(event->time_str, conn, 0);
  }
  else
  {
    timestamp = g_new0 (gchar, TIMEBUF_SIZE);
    sim_time_t_to_str (timestamp, event->time);
  }

  ctx = sim_context_get_id (event->context);

  if (event->src_hostname)
    e_src_hostname = sim_str_escape (event->src_hostname, conn, 0);
  if (event->dst_hostname)
    e_dst_hostname = sim_str_escape (event->dst_hostname, conn, 0);

  if (event->src_mac)
    src_mac = sim_mac_to_db_string (event->src_mac);
  if (event->dst_mac)
    dst_mac = sim_mac_to_db_string (event->dst_mac);

  values
      = g_strdup_printf(
          "(%s, %u, %s, '%s', %s, %s, %s, %s, %d, %u, %u, %u, %u, %u, %u, %d, %d, %d, %d, %d, %4.2f, '%s','%s', %s, %s, %s, %s)",
          sim_uuid_get_db_string (event->id),
          event->device_id,
          sim_uuid_get_db_string (ctx),
          timestamp,
          sim_inet_get_db_string (event->src_ia),
          sim_inet_get_db_string (event->dst_ia),
          event->src_net ? sim_uuid_get_db_string (sim_net_get_id (event->src_net)) : "NULL",
          event->dst_net ? sim_uuid_get_db_string (sim_net_get_id (event->dst_net)) : "NULL",
          event->protocol,
          event->src_port, event->dst_port,
          event->priority, event->reliability,
          event->asset_src, event->asset_dst,
          MAX ((gint) event->risk_c, 0),
          MAX ((gint) event->risk_a, 0),
          event->correlation,
          event->plugin_id, event->plugin_sid, event->tzone,
          event->src_hostname ? e_src_hostname : "",
          event->dst_hostname ? e_dst_hostname : "",
          (src_mac) ? src_mac : "NULL",
          (dst_mac) ? dst_mac : "NULL",
          sim_uuid_get_db_string (event->src_id),
          sim_uuid_get_db_string (event->dst_id));

  if (e_src_hostname)
    g_free(e_src_hostname);
  if (e_dst_hostname)
    g_free(e_dst_hostname);

  if (src_mac)
    g_free (src_mac);
  if (dst_mac)
    g_free (dst_mac);

  //printf("r_a: %f r_c: %f\n", rint(event->risk_a), rint(event->risk_c)); for debug...

  query = g_strdup_printf ("INSERT INTO acid_event "
                           "(id, device_id, ctx, timestamp, ip_src, ip_dst, src_net, dst_net, ip_proto, layer4_sport, layer4_dport, "
                           "ossim_priority, ossim_reliability, ossim_asset_src, ossim_asset_dst, ossim_risk_c, ossim_risk_a, "
                           "ossim_correlation, plugin_id, plugin_sid, tzone, src_hostname, dst_hostname, src_mac, dst_mac, src_host, dst_host) "
                           "VALUES %s", values);
  sim_database_execute_no_query(db_snort, query);
  ossim_debug ("%s: query: %s ", __func__, query);
  g_free (query);
  g_free (values);

  // Add this event to the event counter.
  sim_container_inc_events_count (ossim.container);

  g_free (timestamp);
  event->is_snort_stored = TRUE;
}

/*
 *
 * This calls to sim_organizer_snort_ossim_event_insert, so the events are stored in the snort DB.
 * This inserts into event (to identify it with cid&sid), into other tables like iphdr to store
 * the data from the event and emulate snort events,  and into ossim_event to insert specific ossim data.
 *
 */
void
sim_organizer_snort_event_insert(SimDatabase *db_snort, SimEvent *event,
                                 guint sid, guint sig_id)
{
  g_return_if_fail(db_snort);
  g_return_if_fail(SIM_IS_DATABASE (db_snort));
  g_return_if_fail(event);
  g_return_if_fail(SIM_IS_EVENT (event));
  g_return_if_fail(sid > 0);

  // unused parameter
  (void) sig_id;

  ossim_debug ("%s: payload: %s", __func__, event->data);

  sim_organizer_snort_extra_data_insert(db_snort, event);
  sim_organizer_snort_reputation_data_insert(ossim.dbsnort, event);
  sim_organizer_snort_idm_data_insert (ossim.dbsnort, event);
  sim_organizer_snort_pulses_insert (ossim.dbsnort, event);

  //sim_organizer_snort_ossim_event_insert (db_snort, event, sid, cid);
}

void
sim_organizer_snort_extra_data_insert (SimDatabase *db_snort,
                                       SimEvent    *event)
{
  gchar *query = NULL;
  gchar *values = NULL;
  gchar *e_filename = NULL;
  gchar *e_username = NULL, *e_password = NULL;
  gchar *e_userdata1 = NULL, *e_userdata2 = NULL, *e_userdata3 = NULL;
  gchar *e_userdata4 = NULL, *e_userdata5 = NULL, *e_userdata6 = NULL;
  gchar *e_userdata7 = NULL, *e_userdata8 = NULL, *e_userdata9 = NULL;
  gchar *payload = NULL;
  gchar *binary_data = NULL;
  GdaConnection *conn;

  g_return_if_fail (SIM_IS_DATABASE (db_snort));
  g_return_if_fail (SIM_IS_EVENT (event));

  conn = sim_database_get_conn (db_snort);

  if (event->filename)
    e_filename = sim_str_escape (event->filename, conn, 0);
  if (event->username)
    e_username = sim_str_escape (event->username, conn, 0);
  if (event->password)
    e_password = sim_str_escape (event->password, conn, 0);

  // Special cases: snort events have a pcap in userdata1 and its length in userdata2.
  // We don't want this in DB.
  if ((event->userdata1) && !((event->plugin_id >= 1001) && (event->plugin_id < 1500)))
    e_userdata1 = sim_str_escape (event->userdata1, conn, 0);
  if ((event->userdata2) && !((event->plugin_id >= 1001) && (event->plugin_id < 1500)))
    e_userdata2 = sim_str_escape (event->userdata2, conn, 0);

  if (event->userdata3)
    e_userdata3 = sim_str_escape (event->userdata3, conn, 0);
  if (event->userdata4)
    e_userdata4 = sim_str_escape (event->userdata4, conn, 0);
  if (event->userdata5)
    e_userdata5 = sim_str_escape (event->userdata5, conn, 0);
  if (event->userdata6)
    e_userdata6 = sim_str_escape (event->userdata6, conn, 0);
  if (event->userdata7)
    e_userdata7 = sim_str_escape (event->userdata7, conn, 0);
  if (event->userdata8)
    e_userdata8 = sim_str_escape (event->userdata8, conn, 0);
  if (event->userdata9)
    e_userdata9 = sim_str_escape (event->userdata9, conn, 0);


  /* If event->log has something use it as payload instead of event->data */
  if (event->log)
    payload = sim_str_escape (event->log->str, conn, event->log->len);
  else
    payload = sim_str_escape (event->data, conn, 0);

  if (event->binary_data)
    binary_data = sim_str_escape (event->binary_data, conn, 0);

  values = g_strdup_printf ("(%s,'%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s', %s%s)",
                            sim_uuid_get_db_string (event->id),
                            e_filename ? e_filename : "",
                            e_username ? e_username : "",
                            e_password ? e_password : "",
                            e_userdata1 ? e_userdata1 : "",
                            e_userdata2 ? e_userdata2 : "",
                            e_userdata3 ? e_userdata3 : "",
                            e_userdata4 ? e_userdata4 : "",
                            e_userdata5 ? e_userdata5 : "",
                            e_userdata6 ? e_userdata6 : "",
                            e_userdata7 ? e_userdata7 : "",
                            e_userdata8 ? e_userdata8 : "",
                            e_userdata9 ? e_userdata9 : "",
                            payload ? payload : "",
                            binary_data ? "0x" : "",
                            binary_data ? binary_data : "NULL");

  query = g_strdup_printf ("INSERT IGNORE INTO extra_data "
                           "(event_id, filename, username, password, userdata1, userdata2, userdata3, userdata4, "
                           "userdata5, userdata6, userdata7, userdata8, userdata9, data_payload, binary_data) "
                           "VALUES %s", values);
  sim_database_execute_no_query (db_snort, query);
  g_free (query);
  g_free (values);

  if (e_filename)
    g_free (e_filename);
  if (e_username)
    g_free (e_username);
  if (e_password)
    g_free (e_password);
  if (e_userdata1)
    g_free (e_userdata1);
  if (e_userdata2)
    g_free (e_userdata2);
  if (e_userdata3)
    g_free (e_userdata3);
  if (e_userdata4)
    g_free (e_userdata4);
  if (e_userdata5)
    g_free (e_userdata5);
  if (e_userdata6)
    g_free (e_userdata6);
  if (e_userdata7)
    g_free (e_userdata7);
  if (e_userdata8)
    g_free (e_userdata8);
  if (e_userdata9)
    g_free (e_userdata9);
  if (payload)
    g_free (payload);
  if (binary_data)
    g_free (binary_data);
}

void
sim_organizer_snort_reputation_data_insert (SimDatabase *db_snort,
                                            SimEvent    *event)
{
  GdaConnection *conn;
  gchar *e_rep_act_src = NULL;
  gchar *e_rep_act_dst = NULL;
  gchar *query, *values;

  g_return_if_fail(SIM_IS_DATABASE (db_snort));
  g_return_if_fail(SIM_IS_EVENT (event));

  if (!event->rep_act_src && !event->rep_act_dst && !event->rep_prio_src &&
      !event->rep_prio_dst && !event->rep_rel_src && !event->rep_rel_dst)
  {
    return;
  }

  conn = sim_database_get_conn (db_snort);

  if (event->str_rep_act_src)
    e_rep_act_src = sim_str_escape (event->str_rep_act_src, conn, 0);
  if (event->str_rep_act_dst)
    e_rep_act_dst = sim_str_escape (event->str_rep_act_dst, conn, 0);

  values = g_strdup_printf("(%s, %s,%s,%u,%u,%u,%u,'%s','%s')",
                           sim_uuid_get_db_string (event->id),
                           event->rep_prio_src ? sim_inet_get_db_string (event->src_ia) : "0",
                           event->rep_prio_dst ? sim_inet_get_db_string (event->dst_ia) : "0",
                           event->rep_prio_src,
                           event->rep_prio_dst,
                           event->rep_rel_src,
                           event->rep_rel_dst,
                           event->str_rep_act_src ? e_rep_act_src : "",
                           event->str_rep_act_dst ? e_rep_act_dst : "");

  if (e_rep_act_src)
    g_free(e_rep_act_src);
  if (e_rep_act_dst)
    g_free(e_rep_act_dst);

  query = g_strdup_printf ("INSERT IGNORE INTO reputation_data "
                           "(event_id, rep_ip_src, rep_ip_dst, rep_prio_src, rep_prio_dst, "
                           "rep_rel_src, rep_rel_dst, rep_act_src, rep_act_dst) "
                           "VALUES %s", values);

  sim_database_execute_no_query (db_snort, query);

  g_free (query);
  g_free (values);
}

static void
sim_organizer_snort_idm_data_insert (SimDatabase *db_snort, SimEvent *event)
{
  gchar *query;

  g_return_if_fail (SIM_IS_DATABASE (db_snort));
  g_return_if_fail (SIM_IS_EVENT (event));

  if (!event->src_username && !event->dst_username)
    return;

  query = sim_event_idm_get_insert_clause (sim_database_get_conn (db_snort), event);
  sim_database_execute_no_query (db_snort, query);
  g_free (query);
}

/*
 * This function returns the number (sig_id) associated with each event kind. When a new
 * event arrives here, we have to insert it's name into "signature" table. Then we can recurse and get the sig_id
 * assigned to the name (this happens thanks to auto_increment in DB).
 *
 */
guint
sim_organizer_snort_signature_get_id(SimDatabase *db_snort, gchar *name)
{
  GdaDataModel *dm;
  const GValue *value;
  guint sig_id;
  guint *sig_id_ptr;
  gchar *query;
  gchar *insert;
  gint ret;

  g_return_val_if_fail(db_snort, 0);
  g_return_val_if_fail(SIM_IS_DATABASE (db_snort), 0);
  g_return_val_if_fail(name, 0);

  /* SID */

  sig_id_ptr = sim_container_get_signatures_to_id(ossim.container, name);

  if (sig_id_ptr)
  {
    ossim_debug(
      "sim_organizer_snort_signature_get_id: From cache: sig_id %u for signature: %s",
      *sig_id_ptr, name);
    return *sig_id_ptr;
  }

  query = g_strdup_printf("SELECT sig_id FROM signature WHERE sig_name = '%s'",
                          name);
  ossim_debug(
    "sim_organizer_snort_signature_get_id: Query: %s", query);

  dm = sim_database_execute_single_command(db_snort, query);
  if (dm)
  {
    if (gda_data_model_get_n_rows(dm)) //if the name of the plugin_sid is in database, get its sig_id (signature id).
    {
      value = gda_data_model_get_value_at(dm, 0, 0, NULL);
      sig_id = g_value_get_uint (value);
      sig_id_ptr = g_malloc(sizeof(guint));
      *sig_id_ptr = sig_id;
      sim_container_add_signatures_to_id(ossim.container, name, sig_id_ptr);
    }
    else
    {
      insert
        = g_strdup_printf(
          "INSERT INTO signature (sig_name, sig_class_id) " "VALUES ('%s', 0)",
          name);

      ret = sim_database_execute_no_query(db_snort, insert);
      g_free(insert);

      if (ret < 0)
        g_critical("ERROR: CANT INSERT INTO SNORT DB");

      sig_id = sim_organizer_snort_signature_get_id(db_snort, name);
    }

    g_object_unref(dm);
  }
  else
  {
    sig_id = 0;
    g_message("SIG ID DATA MODEL ERROR");
  }

  g_free(query);

  return sig_id;
}

/*
 *
 * Insert the snort OR other event into DB
 *
 */
void
sim_organizer_snort(SimOrganizer *organizer, SimEvent *event)
{
  gchar *ip_sensor;

  g_return_if_fail(SIM_IS_ORGANIZER (organizer));
  g_return_if_fail(SIM_IS_EVENT (event));
  g_return_if_fail(event->sensor);

  // event->interface not required
  if (!event->interface)
    event->interface = g_strdup ("");

  ip_sensor = sim_inet_get_canonical_name (event->sensor);
  ossim_debug( "sim_organizer_snort Start: event->sid: %u", event->device_id);
  ossim_debug( "sim_organizer_snort event->sensor: %s ; event->interface: %s", ip_sensor, event->interface);
  ossim_debug( "sim_organizer_snort event->data: -%s-", event->data ? event->data : "");
  ossim_debug( "sim_organizer_snort event->log:-%s-", event->log ? event->log->str : "");
  g_free (ip_sensor);

  if (!event->is_snort_stored && (sim_role_store (event->role)))
  {
    sim_organizer_snort_event_update_acid_event(ossim.dbsnort, event); //insert into acid_event
    sim_organizer_snort_extra_data_insert(ossim.dbsnort, event);
    sim_organizer_snort_reputation_data_insert(ossim.dbsnort, event);
    sim_organizer_snort_idm_data_insert (ossim.dbsnort, event);
    sim_organizer_snort_pulses_insert (ossim.dbsnort, event);
  }
}

//This two functions are needed to know the number of events that sim_organizer_run has popped and processed from the queue.
void
sim_organizer_increase_total_events (SimOrganizer *organizer)
{
  organizer->_priv->events_popped ++;
}

guint
sim_organizer_get_total_events (SimOrganizer *organizer)
{
  return organizer->_priv->events_popped;
}

/**
 * sim_organizer_get_events_in_queue:
 * @organizer: a #SimOrganizer.
 *
 * Returns: the number of events in organizer.
 */
guint
sim_organizer_get_events_in_queue (void)
{
  return sim_container_get_events_to_organizer (ossim.container);
}

static void
sim_organizer_store_event (SimOrganizer *organizer,
                           SimEvent     *event)
{
  ossim_debug ("%s: Pushing event to storage queue = %s", __func__, sim_uuid_get_string (event->id));

  if (event->type == SIM_EVENT_TYPE_NONE)
  {
    ossim_debug ("%s: SIM_EVENT_TYPE_NONE !", __func__);
    return;
  }

  /**/
  if (event->alarm)
  {
    if (event->backlog_id == NULL && event->plugin_id  == 1505)
    {
      ossim_debug ("%s: ERROR alarm without backlog!", __func__);
      return;
    }

    if (event->plugin_id != 1505)
    {
      SimAlarmStats *alarm_stats;

      /* An event by itself causes an alarm */
      if(event->backlog_id)
        g_object_unref(event->backlog_id);
      event->backlog_id = sim_uuid_new();

      alarm_stats = sim_alarm_stats_new ();
      sim_alarm_stats_update (alarm_stats, event);
      event->alarm_stats = sim_alarm_stats_to_json (alarm_stats);
      sim_alarm_stats_unref (alarm_stats);

      sim_db_insert_dummy_backlog (ossim.dbossim, event);
      sim_db_insert_dummy_backlog_event (ossim.dbossim, event);
      sim_db_insert_alarm_view_tables (ossim.dbossim, event);
      sim_db_insert_event (ossim.dbossim, event);
      sim_db_insert_alarm (ossim.dbossim, event, TRUE);
    }
    else
    {
      sim_db_insert_event (ossim.dbossim, event);
      sim_db_insert_alarm (ossim.dbossim, event, FALSE);
    }
  }
  else if (event->belongs_to_alarm)
  {
    sim_db_insert_event (ossim.dbossim, event);
  }

  /* insert the snort or other event into snort db. Events regarding alarms are not stored */
  sim_organizer_snort (organizer, event);
}

/**
 * sim_organizer_run_role:
 *
 * Run the event role
 *
 *  - Cross Correlation
 *  - Update risk levels
 *  - Store alarms in syslog
 *  - Correlation
 *  - DB Storage
 *  - Action / Response
 */
static void
sim_organizer_run_role (SimOrganizer *organizer,
                        SimEvent     *event)
{
  /* Event count */
  sim_context_inc_total_events (event->context);

  /* Cross correlation */
  if (sim_role_cross_correlate (event->role))
  {
    if (!sim_inet_is_reserved (event->dst_ia))
      sim_organizer_correlation_plugin (organizer, event); // Update reliability. Also, event -> alarm.

    // Add to our hash table.
    if (sim_context_try_set_host_plugin_sid (event->context, event))
      ossim_debug ("%s: event with plugin id %d and plugin sid %d added to the host plugin sids table", __func__, event->plugin_id, event->plugin_sid);
  }

  /* Update risk levels */
  if (sim_role_qualify (event->role))
  {
    /* update priority (if match with some policy) */
    if (!sim_organizer_reprioritize (organizer, event, event->policy))
    {
      sim_event_unref (event);
      return;
    }
    sim_organizer_risk_levels (event); // update c&a, event->alarm (get risk)
  }

  /* Store alarm in syslog */
  if (event->alarm && sim_role_alarms_to_syslog (ossim.config->server.role))
    sim_organizer_send_alarms_to_syslog (event); //Store the alarms into local7 syslog

  /* Correlation */
  if ((sim_role_correlate (event->role)))
    sim_correlation (event);

  /* DB store */
  if (sim_role_store (event->role) || event->alarm || event->belongs_to_alarm)
    sim_organizer_store_event (organizer, event);

  /* action/response */
  if (event->policy && sim_policy_get_has_actions (event->policy))
  {
    // Special case:
    // Events that will be tested for RISK>=1, send only and if only
    // they're actually alarms.
    if (sim_policy_get_has_alarm_actions (event->policy) == sim_policy_get_has_actions (event->policy))
    {
      if (event->alarm)
        sim_container_push_ar_event (ossim.container, event);
    }
    else
      sim_container_push_ar_event (ossim.container, event);
  }

  sim_organizer_increase_total_events (organizer);

  sim_event_unref (event);

  return;
}
static void
sim_organizer_snort_pulses_insert (SimDatabase *db_snort, SimEvent *event)
{
  gchar *query = NULL;

  g_return_if_fail (SIM_IS_DATABASE (db_snort));
  g_return_if_fail (SIM_IS_EVENT (event));

  if (g_hash_table_size (event->otx_data) == 0)
    return;
  query = sim_event_pulses_get_insert_clause (sim_database_get_conn (db_snort), event);
  sim_database_execute_no_query (db_snort, query);
  g_free (query);
}
// vim: set tabstop=2:
