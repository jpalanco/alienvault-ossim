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

#ifndef _SIM_ENGINE_H
#define _SIM_ENGINE_H 1

#include <glib.h>

G_BEGIN_DECLS

typedef struct _SimEngine        SimEngine;
typedef struct _SimEngineClass   SimEngineClass;
typedef struct _SimEnginePrivate SimEnginePrivate;

#include "sim-context.h"
#include "sim-policy.h"
#include "sim-event.h"
#include "sim-server.h"
#include "sim-directive.h"
#include "sim-plugin-sid.h"
#include "sim-groupalarm.h"
#include "sim-database.h"
#include "sim-uuid.h"

#define SIM_TYPE_ENGINE                     (sim_engine_get_type ())
#define SIM_ENGINE(obj)                     (G_TYPE_CHECK_INSTANCE_CAST ((obj), SIM_TYPE_ENGINE,SimEngine))
#define SIM_IS_ENGINE(obj)                  (G_TYPE_CHECK_INSTANCE_TYPE ((obj),SIM_TYPE_ENGINE))
#define SIM_ENGINE_CLASS(kclass)            (G_TYPE_CHECK_CLASS_CAST ((kclass),SIM_TYPE_ENGINE,SimEngineClass))
#define SIM_ENGINE_IS_ENGINE_CLASS(kclass)  (G_TYPE_CHECK_CLASS_TYPE ((kclass),SIM_TYPE_ENGINE))
#define SIM_ENGINE_GET_CLASS(obj)           (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_ENGINE,SimEngineClass))

#define SIM_ENGINE_DEFAULT 1

struct _SimEngine
{
  GObject parent;
  SimEnginePrivate *priv;
};

struct _SimEngineClass
{
  GObjectClass  parent_class;
};

/* Prototypes */
GType           sim_engine_get_type                     (void);
void            sim_engine_register_type                (void);

SimEngine *     sim_engine_new                          (SimUuid       *id,
                                                         const gchar   *name);
SimEngine *     sim_engine_new_full                     (SimUuid       *id,
                                                         const gchar   *name,
                                                         const gchar   *default_directive_file,
                                                         const gchar   *directive_file,
                                                         const gchar   *disabled_file,
                                                         SimDatabase   *database);
SimUuid *       sim_engine_get_id                       (SimEngine     *engine);
const gchar *   sim_engine_get_name                     (SimEngine     *self);

// Contexts
void            sim_engine_add_context                  (SimEngine     *engine,
                                                         SimContext    *context);

// Directives
GPtrArray *     sim_engine_get_directives_by_plugin_id  (SimEngine     *engine,
                                                         gint           plugin_id);

void            sim_engine_set_directive_file           (SimEngine     *engine,
                                                         const gchar   *filename);
void            sim_engine_set_disabled_file            (SimEngine     *engine,
                                                         const gchar   *filename);
void            sim_engine_set_database                 (SimEngine     *engine,
                                                         SimDatabase   *database);
void            sim_engine_expand_directives            (SimEngine     *engine);

// Loaders
void            sim_engine_load_all                     (SimEngine     *engine);
void            sim_engine_reload_all                   (SimEngine     *engine);
void            sim_engine_reload_sensors               (SimEngine     *engine);
void            sim_engine_reload_host_plugin_sids      (SimEngine     *engine);
void            sim_engine_reload_directives            (SimEngine     *engine);
void            sim_engine_reload_hierarchy             (SimEngine     *engine);

// Events in queue
void            sim_engine_init_events_in_queue         (SimEngine     *engine);
gint            sim_engine_get_events_in_queue          (SimEngine     *engine);
gboolean        sim_engine_has_events_in_queue          (SimEngine     *engine);
void            sim_engine_inc_events_in_queue          (SimEngine     *engine);
void            sim_engine_dec_events_in_queue          (SimEngine     *engine);


// Backlogs
void            sim_engine_lock_backlogs                (SimEngine     *engine);
void            sim_engine_unlock_backlogs              (SimEngine     *engine);
GPtrArray *     sim_engine_get_backlogs_ul              (SimEngine     *engine);
GPtrArray *     sim_engine_get_backlogs                 (SimEngine     *engine);
void            sim_engine_append_backlog_ul            (SimEngine     *engine,
                                                         SimDirective  *backlog);
void            sim_engine_append_backlog               (SimEngine     *engine,
                                                         SimDirective  *backlog);
void            sim_engine_delete_backlogs_ul           (SimEngine     *engine);
void            sim_engine_free_backlogs                (SimEngine     *engine);
gint            sim_engine_get_backlogs_len             (SimEngine     *engine);
gboolean        sim_engine_has_backlogs                 (SimEngine     *engine);
void            sim_engine_remove_expired_backlogs      (SimEngine     *engine);

// Remote backlogs
void            sim_engine_add_remote_backlog           (SimEngine     *engine,
                                                         SimEvent      *event);
void            sim_engine_del_remote_backlog           (SimEngine     *engine,
                                                         SimEvent      *event);
void            sim_engine_add_remote_backlog_event     (SimEngine     *engine,
                                                         SimEvent      *event);
guint           sim_engine_get_remote_backlog_id        (SimEngine     *engine,
                                                         SimEvent      *event);
// Host Plugin sids
void            sim_engine_lock_host_plugin_sids        (SimEngine     *engine);
void            sim_engine_unlock_host_plugin_sids      (SimEngine     *engine);
SimPluginSid *  sim_engine_get_event_plugin_sid         (SimEngine     *engine,
                                                         SimEvent      *event);
void            sim_engine_check_host_plugin_sids       (SimEngine     *engine);


// Group Alarm
void            sim_engine_append_group_alarm           (SimEngine     *engine,
                                                         SimGroupAlarm *group_alarm,
                                                         gchar         *key);
SimGroupAlarm * sim_engine_lookup_group_alarm           (SimEngine     *engine,
                                                         gchar         *key);
void            sim_engine_remove_expired_group_alarms  (SimEngine     *engine);

// Stats
gchar *         sim_engine_get_stats                    (SimEngine     *engine,
                                                         glong          elapsed_time);
gchar *         sim_engine_get_stats_json               (SimEngine     *engine,
                                                         glong         elapsed_time);

void            sim_engine_inc_total_events             (SimEngine     *engine);

#ifdef USE_UNITTESTS
void            sim_engine_register_tests               (SimUnittesting *engine);
#endif

G_END_DECLS

#endif /* _SIM_ENGINE_H */
