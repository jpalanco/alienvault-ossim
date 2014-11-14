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

#include "sim-scheduler.h"

#include <unistd.h>
#include <inttypes.h>

#include "os-sim.h"
#include "sim-container.h"
#include "sim-config.h"
#include "sim-directive.h"
#include "sim-command.h"
#include "sim-server.h"
#include "sim-session.h"
#include "sim-enums.h"
#include "sim-database.h"
#include "sim-groupalarm.h"
#include "sim-db-insert.h"

extern SimMain  ossim;
extern int aux_recvd_msgs;

enum
{
  DESTROY,
  LAST_SIGNAL
};

struct _SimSchedulerPrivate
{
  SimConfig *config;
  gint       timer;
  GList     *tasks;
};

static gpointer parent_class = NULL;
/* No signals
   static gint sim_container_signals[LAST_SIGNAL] = { 0 };
*/

static time_t last  = 0;
static time_t timer = 0;

static time_t       unconfigured_sensors_last_sec = 0;

/* Reload host_plugin_sids hash table every 5 minutes. Needed for cross-correlation. */
static time_t host_plugin_sids_last  = 0;
static time_t host_plugin_sids_timer = 300;

// used in sim_scheduler_show_stats()
static time_t db_last = 0;
static guint  events_before = 0;
static guint  sim_before = 0; //store how many events had the sem queue last time

// Static prototypes.
static void       sim_scheduler_task_remove_backlogs            (SimScheduler * scheduler,
                                                                 GTimeVal curr_time);
static void       sim_scheduler_task_calculate                  (SimScheduler  *scheduler,
                                                                 gpointer       data);


static void       sim_scheduler_task_execute_at_interval        (SimScheduler * scheduler,
                                                                 gpointer       data,
                                                                 GTimeVal curr_time);
static void       sim_scheduler_task_insert_host_plugin_sids    (SimScheduler * scheduler,
                                                                 GTimeVal curr_time);
static void       sim_scheduler_show_stats                      (SimScheduler * scheduler,
                                                                 GTimeVal curr_time);
static void       sim_scheduler_unconfigured_sensors            (GTimeVal curr_time);
static void       sim_scheduler_clean_group_alarm               (SimScheduler *scheduler);


/* GType Functions */
static void
sim_scheduler_impl_dispose (GObject  *gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void
sim_scheduler_impl_finalize (GObject  *gobject)
{
  SimScheduler *sch = SIM_SCHEDULER (gobject);

  g_free (sch->_priv);

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_scheduler_class_init (SimSchedulerClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_peek_parent (class);

  object_class->dispose = sim_scheduler_impl_dispose;
  object_class->finalize = sim_scheduler_impl_finalize;
}

static void
sim_scheduler_instance_init (SimScheduler *scheduler)
{
  scheduler->_priv = g_new0 (SimSchedulerPrivate, 1);

  scheduler->_priv->config = NULL;
  scheduler->_priv->tasks = NULL;
  scheduler->_priv->timer = 30;
}

/* Public Methods */

GType
sim_scheduler_get_type (void)
{
  static GType object_type = 0;

  if (!object_type)
  {
    static const GTypeInfo type_info = {
      sizeof (SimSchedulerClass),
      NULL,
      NULL,
      (GClassInitFunc) sim_scheduler_class_init,
      NULL,
      NULL,                       /* class data */
      sizeof (SimScheduler),
      0,                          /* number of pre-allocs */
      (GInstanceInitFunc) sim_scheduler_instance_init,
      NULL                        /* value table */
    };

    g_type_init ();

    object_type = g_type_register_static (G_TYPE_OBJECT, "SimScheduler", &type_info, 0);
  }

  return object_type;
}

/*
 *
 *
 *
 *
 */
SimScheduler*
sim_scheduler_new (SimConfig    *config)
{
  SimScheduler *scheduler = NULL;

  g_return_val_if_fail (config, NULL);
  g_return_val_if_fail (SIM_IS_CONFIG (config), NULL);

  scheduler = SIM_SCHEDULER (g_object_new (SIM_TYPE_SCHEDULER, NULL));
  scheduler->_priv->config = config;

  return scheduler;
}

/*
 * Recover the host and net levels of C and A
 */
static void
sim_scheduler_task_calculate (SimScheduler  *scheduler,
                              gpointer       data)
{
  g_return_if_fail (SIM_IS_SCHEDULER (scheduler));

  // unused parameter
  (void) data;

  sim_container_update_recovery (ossim.container, ossim.dbossim);
}

/*
 * Although this function is executed each second or so, only
 * do its job (executing other functions) each "interval" seconds approximately.
 *
 */
void
sim_scheduler_task_execute_at_interval (SimScheduler  *scheduler,
                                        gpointer       data,
                                        GTimeVal curr_time)
{
  SimConfig     *config;
  g_return_if_fail (SIM_IS_SCHEDULER (scheduler));

  if (curr_time.tv_sec < (last + timer))
    return;

  last = curr_time.tv_sec;
  config = scheduler->_priv->config;

  timer = config->scheduler.interval; //interval is 15 by default in config.xml

  sim_scheduler_task_calculate (scheduler, data);//do the net and host level recovering

  return;
}

/**
 * sim_scheduler_task_insert_host_plugin_sids:
 * @scheduler: a #SimScheduler object.
 *
 * Dumps the contents of the @host_plugin_sids hash table into database every 5 minutes.
 */
static void
sim_scheduler_task_insert_host_plugin_sids (SimScheduler * scheduler,
                                            GTimeVal       curr_time)
{
  if (curr_time.tv_sec < (host_plugin_sids_last + host_plugin_sids_timer))
    return;

  host_plugin_sids_last = curr_time.tv_sec;

  g_return_if_fail (SIM_IS_SCHEDULER (scheduler));

  SimContext * context = sim_container_get_context (ossim.container, NULL);
  GList * host_plugin_sids = NULL;

  sim_context_lock_host_plugin_sids_r (context);

  host_plugin_sids = sim_context_get_host_plugin_sid_list (context);

  while (host_plugin_sids)
  {
    SimHostPluginSid * host_plugin_sid = (SimHostPluginSid *) host_plugin_sids->data;
    // Store only if it wasn't previously.
    if (!(host_plugin_sid->in_database))
    {
      sim_db_insert_host_plugin_sid (ossim.dbossim, host_plugin_sid->host_ip, host_plugin_sid->plugin_id, host_plugin_sid->plugin_sid, sim_context_get_id (host_plugin_sid->context));
      g_atomic_int_set (&host_plugin_sid->in_database, TRUE);
    }

    host_plugin_sids = g_list_delete_link (host_plugin_sids, host_plugin_sids);
  }

  sim_context_unlock_host_plugin_sids_r (context);

  return;
}

/*
 * main scheduler loop which decides what should run in a specific moment
 *
 */
void
sim_scheduler_run (SimScheduler *scheduler)
{
  GTimeVal   curr_time;

  g_return_if_fail (SIM_IS_SCHEDULER (scheduler));

  g_get_current_time (&curr_time);

  if (!last)
    last = curr_time.tv_sec;

  if (!host_plugin_sids_last)
    host_plugin_sids_last = curr_time.tv_sec;

  while (TRUE)
  {
    g_usleep (G_USEC_PER_SEC);
    g_get_current_time (&curr_time);

    sim_scheduler_task_remove_backlogs (scheduler, curr_time); //removes backlog entries when needed
    sim_scheduler_task_insert_host_plugin_sids (scheduler, curr_time); // Needed for cross correlation.
    sim_scheduler_clean_group_alarm (scheduler); // Activate the clean of group alarms
    sim_scheduler_show_stats (scheduler, curr_time); //NOTE: comment or uncomment this if you want to see statistics
    sim_scheduler_unconfigured_sensors (curr_time);

    sim_scheduler_task_execute_at_interval (scheduler, NULL, curr_time);//execute some tasks in the time interval defined in config.xml
  }

  return;
}

/**
 * sim_scheduler_show_stats:
 * @scheduler: a SimScheduler object.
 * @curr_time: the current time.
 *
 */
void
sim_scheduler_show_stats (SimScheduler *scheduler,
                          GTimeVal curr_time)
{
  gint events_now = 0;      // events in DB in this moment
  static gint total_db_old=0;
  glong elapsed_time;

  g_return_if_fail (SIM_IS_SCHEDULER (scheduler));

  if (curr_time.tv_sec < (db_last + STATS_TIME_LAPSE))
    return;

  elapsed_time = curr_time.tv_sec - db_last;
  db_last = curr_time.tv_sec;

  events_now = sim_container_get_events_count (ossim.container);
  if(!total_db_old)
    total_db_old = events_now;

  guint eps = (events_now - events_before) / elapsed_time;
  guint eps_sim = (sim_organizer_get_total_events (ossim.organizer) - sim_before) / elapsed_time;

  //this if needed for the first event
  if (events_before == 0)
    eps = 0;

  gchar *context_stats = sim_container_get_context_stats (ossim.container, elapsed_time);

  if (simCmdArgs.dvl == 666)
  {
    g_message("%d [SIM q: %u, popped: %u, discarded: %u, eps: %u] [DB Inserted/Total DB: %d/%d, eps: %u] [session %u/%u] [backlogs: %u]%s",
              aux_recvd_msgs,
              sim_organizer_get_events_in_queue (),
              (sim_organizer_get_total_events(ossim.organizer) >0? sim_organizer_get_total_events(ossim.organizer):0),
              sim_container_get_discarded_events (ossim.container),
              (eps_sim > 0 ? eps_sim:0),
              ((events_now - total_db_old) > 0 ? (events_now - total_db_old):0),
              (events_now > 0 ?events_now:0) , eps,
              (sim_server_get_session_count(ossim.server) > 0 ?sim_server_get_session_count(ossim.server):0),
              (sim_server_get_session_count_active(ossim.server) > 0?sim_server_get_session_count_active(ossim.server):0 ),
              sim_container_get_total_backlogs (ossim.container),
              context_stats);
  }
  else
  {
    g_message("Events in DB: %d; Discarded events: %d", events_now, sim_container_get_discarded_events (ossim.container));
  }

  g_free (context_stats);

  events_before = events_now;
  sim_before = sim_organizer_get_total_events (ossim.organizer);
}

static void
sim_scheduler_unconfigured_sensors (GTimeVal curr_time)
{
  SimSensor *sensor;

  if (curr_time.tv_sec < (unconfigured_sensors_last_sec + UNCONFIGURED_SENSORS_TIME_LAPSE))
    return;

  unconfigured_sensors_last_sec = curr_time.tv_sec;

  sensor = sim_container_get_sensor_by_name (ossim.container, "(null)");

  if (sensor)
  {
    g_message ("There are unconfigured sensors, please configure them from the UI. Meanwhile information generated by them will be discarded");
    g_object_unref (sensor);
  }
}

/**
 * sim_scheduler_task_remove_backlogs:
 * @scheduler: a SimScheduler object.
 * @curr_time: the current time.
 *
 * Remove backlogs that are mean to, whether they're expired or
 * already matched.
 */
static void
sim_scheduler_task_remove_backlogs (SimScheduler * scheduler,
                                    GTimeVal       curr_time)
{
  g_return_if_fail (SIM_IS_SCHEDULER (scheduler));

  // unused parameter
  (void) curr_time;

  sim_container_remove_expired_backlogs (ossim.container);
}

/**
 * sim_scheduler_clean_group_alarm:
 * @scheduler: a SimScheduler object.
 *
 * This function lock the access to the alarm hash table => it can block the
 * correlation. We are going to run it each five minutes
 */
void
sim_scheduler_clean_group_alarm (SimScheduler *scheduler)
{
  g_return_if_fail (SIM_IS_SCHEDULER (scheduler));

  sim_container_remove_expired_group_alarms (ossim.container);
}

// vim: set tabstop=2:
