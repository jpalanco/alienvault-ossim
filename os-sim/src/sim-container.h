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
#ifndef __SIM_CONTAINER_H__
#define __SIM_CONTAINER_H__ 1

#include <glib.h>
#include <glib-object.h>
#include <gnet.h>

typedef struct _SimContainer         SimContainer;
typedef struct _SimContainerClass    SimContainerClass;
typedef struct _SimContainerPrivate  SimContainerPrivate;

#include "sim-enums.h"
#include "sim-database.h"
#include "sim-plugin.h"
#include "sim-plugin-sid.h"
#include "sim-sensor.h"
#include "sim-server.h"
#include "sim-host.h"
#include "sim-net.h"
#include "sim-event.h"
#include "sim-policy.h"
#include "sim-directive.h"
#include "sim-command.h"
#include "sim-util.h"
#include "sim-uuid.h"

G_BEGIN_DECLS

#define SIM_TYPE_CONTAINER            (sim_container_get_type ())
#define SIM_CONTAINER(obj)            (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_CONTAINER, SimContainer))
#define SIM_CONTAINER_CLASS(klass)    (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_CONTAINER, SimContainerClass))
#define SIM_IS_CONTAINER(obj)         (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_CONTAINER))
#define SIM_IS_CONTAINER_CLASS(klass) (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_CONTAINER))
#define SIM_CONTAINER_GET_CLASS(obj)  (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_CONTAINER, SimContainerClass))

#define EVENT_SEQ_TABLE         "event_seq"
#define BACKLOG_SEQ_TABLE       "backlog_seq"

struct _SimContainer
{
  GObject parent;

  SimContainerPrivate  *_priv;
};

struct _SimContainerClass
{
  GObjectClass parent_class;
};

GType             sim_container_get_type                        (void);

SimContainer*     sim_container_new                             (void);
gboolean          sim_container_init                            (SimContainer  *container,
                                                                 SimConfig     *config,
                                                                 SimDatabase   *database);

GList *           sim_container_db_get_osvdb_base_name          (SimDatabase   *database,
                                                                 gint           osvdb_id);
GList *           sim_container_db_get_osvdb_version_name       (SimDatabase   *database,
                                                                 gint           osvdb_id);
gint              sim_container_get_storage_type                (SimContainer * container);

/* Recovery Function */
void              sim_container_db_delete_plugin_sid_directive_ul
                                                                (SimContainer  *container,
                                                                 SimDatabase   *database);
/* Plugins Functions */
GList *           sim_container_get_common_plugins              (SimContainer  *container);

/* Plugin Sids Functions */
GList *           sim_container_get_common_plugin_sids          (SimContainer  *container);
SimPluginSid *    sim_container_get_plugin_sid_by_name          (SimContainer  *container,
                                                                 gint           plugin_id,
                                                                 const gchar   *name);

/* Server functions */
gboolean          sim_container_load_servers                    (SimContainer  *container,
                                                                 SimDatabase   *database);
gboolean          sim_container_reload_servers                  (SimContainer  *container,
                                                                 SimDatabase   *database);

/* Sensors */
void            sim_container_load_sensors                      (SimContainer * container);
void            sim_container_reload_sensors                    (SimContainer  *container);
void            sim_container_add_sensor_to_hash_table          (SimContainer * container,
                                                                 SimSensor    * sensor);
void            sim_container_remove_sensor_from_hash_table     (SimContainer * container,
                                                                 SimSensor * sensor);

SimSensor *     sim_container_get_sensor_by_name                (SimContainer  *container,
                                                                 const gchar   *name);
void            sim_container_set_sensor_by_id                  (SimContainer * container,
                                                                 SimSensor    * sensor);
SimSensor *     sim_container_get_sensor_by_id                  (SimContainer  * container,
                                                                 SimUuid       * id);
SimSensor *     sim_container_get_sensor_by_inet                (SimContainer  *container,
                                                                 SimInet       *inet);
/* Taxonomy Products */
GList *           sim_container_get_taxonomy_product            (SimContainer  *container,
                                                                 gint           product_id);

/* Events Functions */
void              sim_container_push_event                      (SimContainer  *container,
                                                                 SimEvent      *event);
gint              sim_container_get_events_in_queue             (SimContainer  *container);
SimEvent*         sim_container_pop_event                       (SimContainer  *container);

void              sim_container_push_delete_backlog_from_db     (SimContainer  *container,
                                                                 gchar         *backlog_id_alarm_stats);
gchar *           sim_container_pop_delete_backlog_from_db      (SimContainer  *container);
void              sim_container_free_events                     (SimContainer  *container);
void              sim_container_push_event_noblock              (SimContainer  *container,
                                                                 SimEvent      *event);
gint              sim_container_get_events_to_organizer         (SimContainer  *container);
void              sim_container_inc_discarded_events            (SimContainer * container);
gint              sim_container_get_discarded_events            (SimContainer * container);


void              sim_container_db_load_events_count            (SimContainer  *container);
gint              sim_container_get_events_count                (SimContainer  *container);
void              sim_container_inc_events_count                (SimContainer  *container);

gint              sim_container_get_total_events_to_db_dispatcher
                                                                (SimContainer  *container);

/* Monitor rule threads management */
void              sim_container_push_monitor_rule               (SimContainer  *container,
                                                                 SimRule      *rule);
SimRule*          sim_container_pop_monitor_rule                (SimContainer  *container);
void              sim_container_free_monitor_rules              (SimContainer  *container);
gboolean          sim_container_is_empty_monitor_rules          (SimContainer  *container);
gint              sim_container_length_monitor_rules            (SimContainer  *container);

/* Extra funtions for HashTable Caches */
void              sim_container_get_snort_max_sid               (SimContainer  *container);
void              sim_container_set_snort_max_sid               (SimContainer  *container,
                                                                 guint          val);
void              sim_container_add_sensor_sid                  (SimContainer  *container,
                                                                 gchar         *sensor_device,
                                                                 guint          sid);
guint             sim_container_get_sensor_sid                  (SimContainer  *container,
                                                                 gchar         *sensor_device);
guint *           sim_container_get_update_sensor_to_last_cid   (SimContainer  *container,
                                                                 gchar         *hostface);

/* Hash Table signatures to sig_id */
void              sim_container_init_signatures_to_id           (SimContainer  *container);
void              sim_container_free_signatures_to_id           (SimContainer  *container);
void              sim_container_add_signatures_to_id            (SimContainer  *container,
                                                                 gchar         *sig_name,
                                                                 guint         *sig_id);
guint *           sim_container_get_signatures_to_id            (SimContainer  *container,
                                                                 gchar         *sig_name);

/* For ossim.event.id */
guint             sim_container_get_event_id                    (SimContainer  *container);
void              sim_container_set_event_id                    (SimContainer  *container,
                                                                 guint          a);
guint             sim_container_next_event_id                   (SimContainer  *container);

/* For ossim.backlog.id */
guint             sim_container_get_backlog_id                  (SimContainer  *container);
void              sim_container_set_backlog_id                  (SimContainer  *container,
                                                                 guint          a);
guint             sim_container_next_backlog_id                 (SimContainer  *container);

/* For action responses event queue (ar_queue) */
void              sim_container_push_ar_event                   (SimContainer  *container,
                                                                 SimEvent      *event);
SimEvent *        sim_container_pop_ar_event                    (SimContainer  *container);

/* Debug functions */
void              sim_container_debug_print_all                 (SimContainer  *container);
void              sim_container_debug_print_plugins             (SimContainer  *container);
void              sim_container_debug_print_plugin_sids         (SimContainer  *container);
void              sim_container_debug_print_servers             (SimContainer  *container);


gboolean          sim_container_db_plugin_reference_match       (SimContainer  *container,
                                                                 SimDatabase   *database,
                                                                 gint           plugin_id,
                                                                 gint           plugin_sid,
                                                                 gint           reference_id,
                                                                 gint           reference_sid);

/* Context and Engine */
void              sim_container_load_context                    (SimContainer  *container,
                                                                 SimConfig     *config);
void              sim_container_load_engine                     (SimContainer  *container,
                                                                 SimConfig     *config);
SimEngine *       sim_container_get_engine                      (SimContainer  *container,
                                                                 SimUuid       *engine_id);
SimEngine *       sim_container_get_engine_for_context          (SimContainer  *container,
                                                                 SimUuid       *context_id);
SimContext *      sim_container_get_context                     (SimContainer  *container,
                                                                 SimUuid       *context_id);
SimContext *      sim_container_get_context_by_name             (SimContainer  *container,
                                                                 gchar         *context_name);
SimContext *      sim_container_get_engine_ctx                  (SimContainer  *container);
void              sim_container_update_sensor_events            (SimContainer  *container);
guint             sim_container_get_total_backlogs              (SimContainer  *container);
void              sim_container_remove_expired_backlogs         (SimContainer  *container);
void              sim_container_remove_expired_group_alarms     (SimContainer  *container);
gchar *           sim_container_get_context_stats               (SimContainer  *container,
                                                                 glong          elapsed_time);
void              sim_container_reload_host_plugin_sids         (SimContainer  *container);
gchar *
sim_container_get_engine_stats_json (SimContainer *container,
                                glong         elapsed_time);
gint              sim_container_get_proto_by_name               (SimContainer *container, const gchar *name);
const gchar *     sim_container_get_proto_by_number             (SimContainer *container, gint number);
const gchar *     sim_container_get_banner_by_cpe               (SimContainer *container, const gchar *cpe);
G_END_DECLS

#endif /* __SIM_CONTAINER_H__ */

// vim: set tabstop=2:

