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

#ifndef _SIM_DB_INSERT_H_
#define _SIM_DB_INSERT_H_

#include <glib.h>

#include "sim-database.h"
#include "sim-directive.h"
#include "sim-server.h"
#include "sim-role.h"
#include "sim-uuid.h"
#include "sim-idm-entry.h"

G_BEGIN_DECLS

GHashTable *    sim_db_load_policy_role_rservers            (SimDatabase   *database,
                                                             SimPolicy     *policy);
GHashTable *    sim_db_load_forward_servers                 (SimDatabase   *database,
                                                             SimUuid       *server_id);
void            sim_db_insert_generic                       (SimDatabase  * database,
                                                             const gchar  * buffer);

// Update
void            sim_db_update_config_value                  (SimDatabase   *database,
                                                             gchar         *config_key,
                                                             gchar         *value);
void            sim_db_update_server_version                (SimDatabase   *database,
                                                             gchar         *version);
void            sim_db_update_plugin_sid                    (SimDatabase   *database,
                                                             SimDirective  *directive,
                                                             SimUuid       *uuid);
void            sim_db_update_host_properties               (SimDatabase   *database,
                                                             SimUuid       *context_id,
                                                             SimUuid       *sensor_id,
                                                             SimIdmEntry   *entry,
                                                             SimIdmEntryChanges *changes,
                                                             gboolean       is_ip_update);
void            sim_db_update_host_risk_level               (SimDatabase   *database,
                                                             SimHost       *host);
// Insert
void            sim_db_insert_event                         (SimDatabase   *database,
                                                             SimEvent      *event);
void            sim_db_insert_alarm                         (SimDatabase   *database,
                                                             SimEvent      *event,
                                                             gboolean       removable);
void            sim_db_insert_plugin_sid                    (SimDatabase   *database,
                                                             SimPluginSid  *plugin_sid,
                                                             SimUuid       *context_id);
void            sim_db_insert_host_plugin_sid               (SimDatabase   *database,
                                                             SimInet       *inet,
                                                             gint           plugin_id,
                                                             gint           plugin_sid,
                                                             SimUuid     *  context_id);
void            sim_db_insert_host_os                       (SimDatabase   *database,
                                                             SimInet       *inet,
                                                             gchar         *date,
                                                             SimInet       *sensor,
                                                             gchar         *interface,
                                                             gchar         *os,
                                                             SimUuid       *context_id);
void            sim_db_insert_host_mac                      (SimDatabase   *database,
                                                             SimInet       *inet,
                                                             gchar         *date,
                                                             gchar         *mac,
                                                             gchar         *vendor,
                                                             gchar         *interface,
                                                             SimInet       *sensor,
                                                             SimUuid       *context_id);
void            sim_db_insert_host_service                  (SimDatabase   *database,
                                                             SimInet       *inet,
                                                             gchar         *date,
                                                             gint           port,
                                                             gint           protocol,
                                                             SimInet       *sensor,
                                                             gchar         *interface,
                                                             gchar         *service,
                                                             gchar         *application,
                                                             SimUuid       *context_id);
void            sim_db_insert_generic_event_plugin_sid      (SimDatabase   *database);
void            sim_db_insert_demo_event_plugin_sid         (SimDatabase   *database);
void            sim_db_insert_backlog                       (SimDatabase   *database,
                                                             SimDirective  *backlog);
void            sim_db_update_backlog                       (SimDatabase   *database,
                                                             SimDirective  *backlog);
void            sim_db_insert_backlog_event                 (SimDatabase   *database,
                                                             SimDirective  *backlog,
                                                             SimEvent      *event,
                                                             gint           level);
void            sim_db_insert_remote_backlog                (SimDatabase   *database,
                                                             SimCommand    *cmd);
void            sim_db_insert_remote_backlog_event          (SimDatabase   *database,
                                                             SimCommand    *cmd);

// Delete
void            sim_db_delete_host_plugin_sid_host          (SimDatabase * database,
                                                             SimUuid     * context_id,
                                                             gchar       * host_ip);

// Insert/update configuration tables.
void            sim_db_insert_sensor                        (SimDatabase  * database,
                                                             SimSensor    * sensor);
void            sim_db_insert_sensor_properties             (SimDatabase  * database,
		                                                         SimSensor    * sensor);
void            sim_db_update_sensor_by_ia                  (SimDatabase  * database,
                                                             SimSensor    * sensor);
void            sim_db_insert_alarm_view_tables             (SimDatabase *database,
                                                             SimEvent *event);
void            sim_db_insert_dummy_backlog                 (SimDatabase  *database,
                                                             SimEvent     *event);
void            sim_db_insert_dummy_backlog_event           (SimDatabase  *database,
                                                             SimEvent     *event);

G_END_DECLS

#endif /*_SIM_DB_COMMAND_H_*/
