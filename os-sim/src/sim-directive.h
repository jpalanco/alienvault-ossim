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

#ifndef __SIM_DIRECTIVE_H__
#define __SIM_DIRECTIVE_H__ 1

#include <glib.h>
#include <glib-object.h>
#include <uuid/uuid.h>

typedef struct _SimDirective        SimDirective;
typedef struct _SimDirectiveClass   SimDirectiveClass;
typedef struct _SimDirectivePrivate SimDirectivePrivate;

#include "sim-enums.h"
#include "sim-event.h"
#include "sim-rule.h"
#include "sim-directive-group.h"
#include "sim-timetable.h"
#include "sim-database.h"
#include "sim-engine.h"
#include "sim-unittesting.h"

G_BEGIN_DECLS

#define SIM_TYPE_DIRECTIVE                  (sim_directive_get_type ())
#define SIM_DIRECTIVE(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_DIRECTIVE, SimDirective))
#define SIM_DIRECTIVE_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_DIRECTIVE, SimDirectiveClass))
#define SIM_IS_DIRECTIVE(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_DIRECTIVE))
#define SIM_IS_DIRECTIVE_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_DIRECTIVE))
#define SIM_DIRECTIVE_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_DIRECTIVE, SimDirectiveClass))

#define SIM_DIRECTIVE_MAX_GROUP_ALARM_TIMEOUT 3600


struct _SimDirective
{
  GObject parent;

  SimDirectivePrivate  *_priv;
};

struct _SimDirectiveClass
{
  GObjectClass parent_class;
};

GType             sim_directive_get_type                        (void);
SimDirective*     sim_directive_new                             (void);

gint              sim_directive_get_id                          (SimDirective    *directive);
void              sim_directive_set_id                          (SimDirective    *directive,
                                                                 gint             id);
gint              sim_directive_compare_id                      (gconstpointer    a,
                                                                 gconstpointer    b);

SimEngine *       sim_directive_get_engine                      (SimDirective    *directive);
SimUuid *         sim_directive_get_engine_id                   (SimDirective    *directive);
void              sim_directive_purge_db_backlogs               (SimDatabase     *db);
void              sim_directive_append_group                    (SimDirective    *directive,
                                                                 SimDirectiveGroup *group);
void              sim_directive_remove_group                    (SimDirective    *directive,
                                                                 SimDirectiveGroup *group);
void              sim_directive_free_groups                     (SimDirective    *directive);
GList*            sim_directive_get_groups                      (SimDirective    *directive);
gboolean          sim_directive_has_group                       (SimDirective    *directive,
                                                                 SimDirectiveGroup *group);
gint              sim_directive_get_group_alarm_timeout         (SimDirective    *directive);
void              sim_directive_set_group_alarm_timeout         (SimDirective    *directive,
                                                                 gint             value);
gboolean*         sim_directive_get_group_alarm_by              (SimDirective    *directive);
void              sim_directive_set_group_alarm_by              (SimDirective    *directive,
                                                                 gboolean         group_by_scr_ip,
                                                                 gboolean         group_by_scr_port,
                                                                 gboolean         group_by_dst_ip,
                                                                 gboolean         group_by_dst_port);
gboolean          sim_directive_get_group_alarm_store_backlog   (SimDirective    *directive);
void              sim_directive_set_group_alarm_store_backlog   (SimDirective    *directive,
                                                                 gboolean         value);
gboolean          sim_directive_get_group_alarm_by_src_ip       (SimDirective    *directive);
gboolean          sim_directive_get_group_alarm_by_src_port     (SimDirective    *directive);
gboolean          sim_directive_get_group_alarm_by_dst_ip       (SimDirective    *directive);
gboolean          sim_directive_get_group_alarm_by_dst_port     (SimDirective    *directive);
SimUuid *         sim_directive_get_backlog_id                  (SimDirective    * directive);
void              sim_directive_set_backlog_id                  (SimDirective    * directive,
                                                                 SimUuid         * backlog_id);
const gchar *     sim_directive_get_backlog_id_str              (SimDirective    * backlog);

gchar*            sim_directive_get_name                        (SimDirective     *directive);
void              sim_directive_set_name                        (SimDirective     *directive,
                                                                 const gchar      *name);
time_t            sim_directive_get_time_out                    (SimDirective     *directive);
void              sim_directive_set_time_out                    (SimDirective     *directive,
                                                                 time_t            time_out);
time_t            sim_directive_get_time_last                   (SimDirective     *directive);
void              sim_directive_set_time_last                   (SimDirective     *directive,
                                                                 time_t            time_out);

void              sim_directive_set_forwarding                  (SimDirective     *directive,
                                                                 gboolean          forward);
gboolean          sim_directive_get_forwarding                  (SimDirective     *directive);

void              sim_directive_set_rservers                    (SimDirective     *directive,
                                                                 GHashTable       *rservers);
GHashTable *      sim_directive_get_rservers                    (SimDirective     *directive);

GNode *           sim_directive_get_root_node                   (SimDirective     *directive);
void              sim_directive_set_root_node                   (SimDirective     *directive,
                                                                 GNode            *rule_root);
GNode *           sim_directive_get_curr_node                   (SimDirective     *directive);
void              sim_directive_set_curr_node                   (SimDirective     *directive,
                                                                 GNode            *rule_root);

SimRule *         sim_directive_get_root_rule                   (SimDirective     *directive);
SimRule *         sim_directive_get_curr_rule                   (SimDirective     *directive);

gint              sim_directive_get_rule_level                  (SimDirective     *directive);
time_t            sim_directive_get_rule_curr_time_out_max      (SimDirective     *directive);
gint              sim_directive_get_level                       (SimDirective     *directive);

gboolean          sim_directive_match_by_event                  (SimDirective     *directive,
                                                                 SimEvent         *event);
gboolean          sim_directive_backlog_match_by_event          (SimDirective     *directive,
                                                                 SimEvent         *event);
void              sim_directive_set_rule_vars                   (SimDirective     *directive,
                                                                 GNode            *node);
GNode*            sim_directive_get_node_branch_by_level        (SimDirective     *directive,
                                                                 GNode            *node,
                                                                 gint              level);
gboolean          sim_directive_backlog_get_matched             (SimDirective     *directive);
void              sim_directive_backlog_set_matched             (SimDirective     *directive,
                                                                 gboolean          matched);
gboolean          sim_directive_backlog_get_deleted             (SimDirective     *directive);
void              sim_directive_backlog_set_deleted             (SimDirective    * directive,
                                                                 gboolean          deleted);
gboolean          sim_directive_backlog_is_expired              (SimDirective     *directive);
gboolean          sim_directive_backlog_time_out                (SimDirective     *directive);
GNode*            sim_directive_node_data_clone                 (GNode            *node);
void              sim_directive_node_data_destroy               (GNode            *node);

SimDirective*     sim_directive_clone                           (SimDirective     *directive,
                                                                 SimEngine        *engine);
gchar*            sim_directive_backlog_get_insert_clause       (SimDirective     *directive);
gchar*            sim_directive_backlog_get_update_clause       (SimDirective     *directive);
gchar*            sim_directive_backlog_get_delete_clause       (SimDirective     *directive);
gchar*            sim_directive_backlog_to_string               (SimDirective     *directive);
gchar*            sim_directive_backlog_event_get_insert_clause (SimDirective     *directive,
                                                                 SimEvent         *event,
                                                                 gint              level);
gchar*			  sim_directive_backlog_event_get_insert_clause_values (SimDirective *directive,
                                                                        SimEvent     *event,
                                                                        gint          level);

const gchar *     sim_directive_backlog_get_header_clause       (void);
gchar*            sim_directive_backlog_get_values_clause       (SimDirective     *directive);
gchar *           sim_directive_backlog_get_ref_uuid            (SimDirective     *directive);
gboolean          sim_directive_backlog_set_uuid                (SimDirective     *directive);
gboolean          sim_directive_backlog_generate_uuid           (SimDirective     *directive);
void              sim_directive_set_timetable                   (SimDirective     *directive,
                                                                 SimTimetable     *timetable);
SimTimetable*     sim_directive_get_timetable                   (SimDirective     *directive);
void              sim_directive_set_timetable_name              (SimDirective     *directive,
                                                                 gchar            *name);
gchar *           sim_directive_get_timetable_name              (SimDirective     *directive);
gboolean          sim_directive_check_timetable_restriction     (SimDirective     *directive,
                                                                 SimEvent         *event);
gint              sim_directive_get_priority                    (SimDirective     *directive);
void              sim_directive_set_priority                    (SimDirective     *directive,
                                                                 gint              priority);
// Group functions
gboolean          sim_directive_get_groupalarm                  (SimDirective     *directive);
gboolean          sim_directive_get_group_alarm_by_src_ip       (SimDirective     *directive);
gboolean          sim_directive_get_group_alarm_by_src_port     (SimDirective     *directive);
gboolean          sim_directive_get_group_alarm_by_dst_ip       (SimDirective     *directive);
gboolean          sim_directive_get_group_alarm_by_dst_port     (SimDirective     *directive);
gint              sim_directive_get_group_alarm_timeout         (SimDirective     *directive);
void              sim_directive_set_group_alarm_timeout         (SimDirective     *directive,
                                                                 gint              value);
void              sim_directive_set_group_alarm_by              (SimDirective     *directive,
                                                                 gboolean          group_by_scr_ip,
                                                                 gboolean          group_by_scr_port,
                                                                 gboolean          group_by_dst_ip,
                                                                 gboolean          group_by_dst_port);
inline gboolean   sim_directive_get_groupalarm                  (SimDirective     *directive);
int               sim_directive_get_priority                    (SimDirective     *directive);
void              sim_directive_set_priority                    (SimDirective     *directive,
                                                                 gint              priority);
gboolean          sim_directive_backlog_match_by_not            (SimDirective     *directive);
void              sim_directive_set_matched                     (SimDirective     *directive,
                                                                 gboolean          matched);
void              sim_directive_backlog_id_generate             (SimDirective    * backlog);

gboolean          sim_directive_all_children_have_src_ia        (GNode *);

gchar *           sim_directive_backlog_event_get_delete_clause (SimDirective      *directive,
                                                                 SimEvent          *event);
void              sim_directive_print                           (SimDirective      *directive);
gboolean *        sim_directive_get_group_alarm_by              (SimDirective      *directive);
inline void       sim_directive_ctx_group_insert_uuid           (SimDirective      *self,
                                                                 gchar             *uuid);
GHashTable *      sim_directive_get_root_plugin_ids             (SimDirective      *directive);
GHashTable *      sim_directive_get_root_taxonomy_product       (SimDirective      *directive);
gboolean          sim_directive_is_in_list                      (SimDirective      *directive,
                                                                 GList             *disabled_list);
void              sim_directive_delete_database_backlog         (SimUuid           *backlog_id,
                                                                 gchar             *alarm_stats);
void              sim_directive_set_alarm_as_removable          (SimUuid           *backlog_id,
                                                                 gchar             *alarm_stats);

GHashTable *      sim_directive_backlog_get_plugin_ids          (SimDirective      *backlog);

// Backlog references
GHashTable *      sim_directive_backlog_get_refs                (SimDirective      *backlog);
void              sim_directive_backlog_remove_refs             (SimDirective      *backlog);
void              sim_directive_backlog_update_ref              (SimDirective      *backlog,
                                                                 gint               plugin_id,
                                                                 gint               index);
gboolean          sim_directive_update_backlog_first_last_ts    (SimDirective      *backlog,
                                                                 SimEvent          *event);

gchar*            sim_directive_dummy_backlog_get_values_clause (SimEvent  *event);
gchar*            sim_directive_dummy_backlog_event_get_values_clause (SimEvent  *event);

void              sim_directive_alarm_stats_update              (SimDirective *directive, SimEvent *event);
gchar*            sim_directive_alarm_stats_generate            (SimDirective *directive);
const char *      sim_directive_get_uuid                        (SimDirective * directive);
SimDirective *    sim_directive_create_pulse_backlog            (const gchar *pulse_id);

#ifdef USE_UNITTESTS
void              sim_directive_register_tests                  (SimUnittesting    *engine);
#endif /* USE_UNITTESTS */

G_END_DECLS

#endif /* __SIM_DIRECTIVE_H__ */

// vim: set tabstop=2:

