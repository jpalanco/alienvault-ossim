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

#ifndef _SIM_DB_COMMAND_H_
#define _SIM_DB_COMMAND_H_

#include <glib.h>

#include "sim-database.h"
#include "sim-directive.h"
#include "sim-server.h"
#include "sim-role.h"
#include "sim-uuid.h"
#include "sim-db-insert.h"

G_BEGIN_DECLS

/* Prototypes */

// Loaders
GHashTable *    sim_db_load_software_cpe                    (SimDatabase *database);
GList *         sim_db_load_common_plugins                  (SimDatabase  *database);
GList *         sim_db_load_engines                         (SimDatabase  *database,
                                                             SimUuid      *server_id);
GList *         sim_db_load_local_contexts                  (SimDatabase  *database,
                                                             SimUuid      *server_id);
GList *         sim_db_load_external_contexts               (SimDatabase  *database,
                                                             SimUuid      *server_id);
GList *         sim_db_load_plugins                         (SimDatabase  *database,
                                                             SimUuid      *context_id);
GList *         sim_db_load_common_plugin_sids              (SimDatabase  *database);
GList *         sim_db_load_plugin_sids                     (SimDatabase  *database,
                                                             SimUuid      *context_id);
GList *         sim_db_load_hosts                           (SimDatabase  *database,
                                                             SimUuid      *context_id);
GList *         sim_db_load_nets                            (SimDatabase  *database,
                                                             SimUuid      *context_id);
GList *         sim_db_load_sensors                         (SimDatabase  *database);
GList *         sim_db_load_servers                         (SimDatabase  *database);
SimRole *       sim_db_load_server_role                     (SimDatabase  *database,
                                                             SimUuid      *server_id);
GHashTable *    sim_db_load_idm_entries                     (SimDatabase  *database,
                                                             SimUuid      *context_id);
GList *         sim_db_load_policies                        (SimDatabase  *database,
                                                             SimUuid      *context_id);
GList *         sim_db_load_host_plugin_sids                (SimDatabase  *database,
                                                             SimUuid      *context_id);
GList *         sim_db_load_taxonomy_products               (SimDatabase  *database);
GList *         sim_db_load_plugin_ids_with_product         (SimDatabase  *database,
                                                             gint          product,
                                                             SimUuid      *context_id);
GList *         sim_db_load_ligth_events_from_alarm         (SimDatabase  *database,
                                                             SimUuid      *backlog_id);

// Get
guint           sim_db_get_last_cid                         (SimDatabase   *database);
gboolean        sim_db_get_config_bool                      (SimDatabase   *database,
                                                             const gchar   *config_key);
gint            sim_db_get_config_int                       (SimDatabase   *database,
                                                             const gchar   *config_key);
SimUuid *       sim_db_get_config_uuid                      (SimDatabase   *database,
                                                             const gchar   *config_key);
gint            sim_db_get_config_logger_if_priority        (SimDatabase   *database);

GList *         sim_db_load_plugin_references               (SimDatabase  * database,
                                                             SimUuid      * context_id);
GList *         sim_db_get_reference_sid                    (SimDatabase   *database,
                                                             gint           reference_id,
                                                             gint           plugin_id,
                                                             gint           plugin_sid);
GList *         sim_db_get_contexts_for_engine              (SimDatabase   *database,
                                                             SimUuid       *engine_id);
GList *         sim_db_get_host_plugin_sid_hosts            (SimDatabase  * database,
                                                             SimUuid      * context_id);

GList *         sim_db_get_removable_alarms                 (SimDatabase   *database);

// Delete
void            sim_db_delete_directives                    (SimDatabase * database,
                                                             SimUuid     * engine_id);
void            sim_db_load_policy_reputation_info          (SimDatabase *database,
                                                             SimPolicy   *policy);

void            sim_db_load_reputation_activities           (SimDatabase *database,
                                                             SimReputation *reputation);

void            sim_db_clean_siem_tables                    (SimDatabase *db);
void            sim_db_kill_previous_sql                    (SimDatabase *database, gchar *sql);
void            sim_db_clean_siem_table                     (SimDatabase *db, gchar *table);
void            sim_db_update_taxonomy_info                 (SimDatabase *database);
void            sim_db_update_context_stats                 (SimDatabase *database,
                                                             SimUuid *context_id,
                                                             gfloat stat);
G_END_DECLS

#endif /*_SIM_DB_COMMAND_H_*/
