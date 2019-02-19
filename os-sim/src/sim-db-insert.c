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

#include <glib.h>
#include <string.h>
#include <uuid/uuid.h>
#include <errno.h>
#include <netdb.h>

#include "os-sim.h"
#include "sim-host.h"
#include "sim-policy.h"
#include "sim-directive.h"
#include "sim-util.h"
#include "sim-log.h"
#include "sim-inet.h"
#include "sim-engine.h"
#include "sim-uuid.h"
//#include "sim-idm-entry.h"

#include "sim-db-insert.h"

// This defines a network endpoint, e.g. source or destination
typedef enum
{
  SOURCE = 0,
  DEST = 1
}
netpoint;

typedef gpointer (*DMFunc) (GdaDataModel *dm, gint row);

// Prototipes
static void         sim_db_execute_query                    (SimDatabase    *database,
                                                             gchar          *query);

GHashTable *
sim_db_load_policy_role_rservers (SimDatabase *database,
                                  SimPolicy   *policy)
{
  (void) database;
  (void) policy;

  return NULL;
}

GHashTable *
sim_db_load_forward_servers (SimDatabase  * database,
                             SimUuid      * server_id)
{
  (void) database;
  (void) server_id;

  return NULL;
}

void
sim_db_insert_generic (SimDatabase  * database,
                       const gchar  * buffer)
{
  (void) database;
  (void) buffer;

  return;
}


static void
sim_db_execute_query (SimDatabase *database,
                      gchar       *query)
{
  sim_database_execute_no_query (database, query);
}

void
sim_db_update_host_properties (SimDatabase        *database,
                               SimUuid            *context_id,
                               SimUuid            *sensor_id,
                               SimIdmEntry        *entry,
                               SimIdmEntryChanges *changes,
                               gboolean            is_ip_update)
{
  gchar *query;
  gchar *values;
  gchar *property, *e_property;
  const gchar *host_id_str;
  const gchar *ip_str;

  host_id_str = sim_uuid_get_db_string (sim_idm_entry_get_host_id (entry));
  ip_str = sim_inet_get_db_string (sim_idm_entry_get_ip (entry));

  // 'host' and 'host_sensor_reference' table
  if (changes->host_id)
  {
    query = g_strdup_printf ("INSERT IGNORE INTO host (id, ctx, asset, threshold_c, threshold_a) VALUES (%s, %s, %d, %d, %d)", host_id_str, sim_uuid_get_db_string (context_id), 2, 30, 30);
    sim_database_execute_no_query (database, query);
    g_free (query);


    query = g_strdup_printf ("INSERT IGNORE INTO host_sensor_reference (host_id, sensor_id) VALUES (%s, %s)", host_id_str, sim_uuid_get_db_string (sensor_id));
    sim_database_execute_no_query (database, query);
    g_free (query);
  }

  if (changes->hostname)
  {
    query = g_strdup_printf ("UPDATE host SET hostname = '%s' WHERE id = %s AND ctx = %s", sim_idm_entry_get_hostname (entry), host_id_str, sim_uuid_get_db_string (context_id));
    sim_database_execute_no_query (database, query);
    g_free (query);

  }

  if (changes->fqdns)
  {
    query = g_strdup_printf ("UPDATE host SET fqdns = '%s' WHERE id = %s AND ctx = %s", sim_idm_entry_get_fqdns (entry), host_id_str, sim_uuid_get_db_string (context_id));
    sim_database_execute_no_query (database, query);
    g_free (query);
  }

  query = NULL;

  // 'host_ip' table
  if (changes->ip || changes->mac)
  {
    const gchar *mac_text;
    gchar *mac_bin;

    mac_text = sim_idm_entry_get_mac (entry);

    if (is_ip_update)
    {
      if (mac_text)
      {
        mac_bin = sim_mac_to_db_string (mac_text);

        query = g_strdup_printf ("UPDATE host_ip SET ip=%s, mac=%s "
                                 "WHERE host_id = %s",
                                 ip_str,
                                 mac_bin,
                                 host_id_str);

        g_free (mac_bin);
      }
      else
      {
        query = g_strdup_printf ("UPDATE host_ip SET ip=%s "
                                 "WHERE host_id = %s",
                                 ip_str,
                                 host_id_str);
      }
    }
    else
    {
      if (mac_text)
      {
        mac_bin = sim_mac_to_db_string (mac_text);
        query = g_strdup_printf ("REPLACE host_ip (host_id, ip, mac) VALUES (%s, %s, %s)", host_id_str, ip_str, mac_bin);
        g_free (mac_bin);
      }
      else
      {
        query = g_strdup_printf ("REPLACE host_ip (host_id, ip) VALUES (%s, %s)", host_id_str, ip_str);
      }
    }
  }

  if (query)
  {
    sim_database_execute_no_query (database, query);
    g_free (query);
  }

  // 'host_properties' table
  if (changes->username)
  {
    property = (gchar *)sim_idm_entry_get_username (entry);
    /* Delete old usernames */
    query = g_strdup_printf ("DELETE FROM host_properties WHERE host_id = %s AND property_ref = %d", host_id_str, SIM_HOST_PROP_USERNAME);
    sim_database_execute_no_query (database, query);
    g_free (query);
    if (property != NULL && strlen(property) > 0)
    {
      /* Here, I need to SPLIT the user name. I need a row for each one */
      gchar **usernames = NULL;
      gchar **username_loop= NULL;
      usernames = username_loop  = g_strsplit (property, ",", -1);
      while (*username_loop)
      {
        e_property = sim_database_str_escape (database, *username_loop, 0);
        query = g_strdup_printf ("REPLACE host_properties (host_id, property_ref, source_id, value) VALUES (%s, %d, %d, '%s')", host_id_str, SIM_HOST_PROP_USERNAME, sim_idm_entry_get_source_id (entry), e_property);
        sim_database_execute_no_query (database, query);
        g_free (query);
        g_free (e_property);
        username_loop++;
      }
      g_strfreev (usernames);
    }
  }
    

  if (changes->os)
  {

   //ENG-99163 We cannot use replace here, becuase value is part of the primary key.
   // We only should allow one os per host_id.
   // At this point we know that the revelance of the property>=old property relevance.

    e_property = sim_database_str_escape (database, sim_idm_entry_get_os (entry), 0);

    query = g_strdup_printf ("DELETE FROM host_properties WHERE host_id = %s and property_ref=%d", host_id_str, SIM_HOST_PROP_OS);
    sim_database_execute_no_query (database, query);
    g_free (query);


    query = g_strdup_printf ("REPLACE host_properties (host_id, property_ref, source_id, value) VALUES (%s, %d, %d, '%s')", host_id_str, SIM_HOST_PROP_OS, sim_idm_entry_get_source_id (entry), e_property);
    sim_database_execute_no_query (database, query);
    g_free (query);
    g_free (e_property);
  }

  if (changes->cpu)
  {
    e_property = sim_database_str_escape (database, sim_idm_entry_get_cpu (entry), 0);
    query = g_strdup_printf ("REPLACE host_properties (host_id, property_ref, source_id, value) VALUES (%s, %d, %d, '%s')", host_id_str, SIM_HOST_PROP_CPU, sim_idm_entry_get_source_id (entry), e_property);
    sim_database_execute_no_query (database, query);
    g_free (query);
    g_free (e_property);
  }

  if (changes->memory)
  {
    query = g_strdup_printf ("REPLACE host_properties (host_id, property_ref, source_id, value) VALUES (%s, %d, %d, '%d')", host_id_str, SIM_HOST_PROP_MEMORY, sim_idm_entry_get_source_id (entry),  sim_idm_entry_get_memory (entry));
    sim_database_execute_no_query (database, query);
    g_free (query);
  }

  if (changes->video)
  {
    e_property = sim_database_str_escape (database, sim_idm_entry_get_video (entry), 0);
    query = g_strdup_printf ("REPLACE host_properties (host_id, property_ref, source_id, value) VALUES (%s, %d, %d, '%s')", host_id_str, SIM_HOST_PROP_VIDEO, sim_idm_entry_get_source_id (entry), e_property);
    sim_database_execute_no_query (database, query);
    g_free (query);
    g_free (e_property);
  }

  if (changes->state)
  {
    e_property = sim_database_str_escape (database, sim_idm_entry_get_state(entry), 0);
    query = g_strdup_printf ("REPLACE host_properties (host_id, property_ref, source_id, value) VALUES (%s, %d, %d, '%s')", host_id_str, SIM_HOST_PROP_STATE, sim_idm_entry_get_source_id (entry), e_property);
    sim_database_execute_no_query (database, query);
    g_free (query);
    g_free (e_property);
  }

  // 'host_services' table
  if (changes->service)
  {
#if 0
    // Currently disabled
    query = g_strdup_printf ("DELETE FROM host_services WHERE host_id = %s AND nagios = 0", host_id_str);
    sim_database_execute_no_query (database, query);
    g_free (query);
#endif

    values = sim_idm_entry_service_get_string_db_insert (entry, database);
    if (values)
    {
      query = g_strdup_printf ("INSERT INTO host_services (host_id, host_ip, port, protocol, service, version, source_id) VALUES %s ON DUPLICATE KEY UPDATE service = VALUES(service), source_id = VALUES(source_id)", values);
      sim_database_execute_no_query (database, query);
      g_free (query);
      g_free (values);
    }
  }

  // 'host_software' table
  if (changes->software)
  {
    values = sim_idm_entry_software_get_string_db_insert (entry, database);
    if (values)
    {
      query = g_strdup_printf ("INSERT INTO host_software (host_id, cpe, banner, source_id) VALUES %s ON DUPLICATE KEY UPDATE banner = VALUES(banner), source_id = VALUES(source_id)", values);
      sim_database_execute_no_query (database, query);
      g_free (query);
      g_free (values);
    }
  }

  // Specific code for the web interface
  if (changes->ip)
  {
    // These queries mitigate performance problems with many hosts/nets.
    // Probably could be resolved with radix trees in the web
    if (is_ip_update)
    {
      query = g_strdup_printf ("DELETE FROM host_net_reference WHERE host_id = %s",
                               host_id_str);
      sim_database_execute_no_query (database, query);
      g_free (query);
    }

    query = g_strdup_printf ("REPLACE INTO host_net_reference SELECT host.id, net_id FROM host, host_ip, net_cidrs "
                             "WHERE host.id = host_ip.host_id AND host_ip.ip >= net_cidrs.begin AND host_ip.ip <= net_cidrs.end AND host_id = %s",
                              host_id_str);
    sim_database_execute_no_query (database, query);
    g_free (query);
  }

  if (changes->ip || changes->username || changes->hostname || changes->mac || changes->os || changes->cpu || changes->memory || changes->video || changes->service || changes->software || changes->state)
  {
    // This query is exclusively used to notify the web server about changes on hosts/nets
    //
    // This could be executed in fewer cases by not caching some asset trees on the web
    sim_database_execute_no_query (database, "REPLACE INTO config (conf, value) VALUES ('latest_asset_change', utc_timestamp())");

  }
  // Specific code for the web interface
}

/**
 * sim_db_update_server_version:
 * @database: #SimDatabase object
 * @version: gchar * server version
 *
 * Update server @version in @database
 */
void
sim_db_update_server_version (SimDatabase *database,
                              gchar       *version)
{
  gchar *query;

  g_return_if_fail (SIM_IS_DATABASE (database));

  query = g_strdup_printf ("REPLACE INTO config(conf,value) VALUES (\"ossim_server_version\",\"%s\")", version);
  sim_database_execute_no_query (database, query);

  g_free (query);
}

/**
 * sim_db_update_server_version:
 * @database: #SimDatabase object
 * @config_key: gchar * config key
 * @value: gchar * config value
 *
 * Update @config_key with @value in @database 'config' table
 */
void
sim_db_update_config_value (SimDatabase *database,
                            gchar       *config_key,
                            gchar       *value)
{
  gchar *query;

  g_return_if_fail (SIM_IS_DATABASE (database));

  query = g_strdup_printf ("REPLACE INTO config(conf,value) VALUES (\"%s\",\"%s\")", config_key, value);
  sim_database_execute_no_query (database, query);

  g_free (query);
}

/**
 * sim_db_update_plugin_sid:
 * @database: #SimDatabase object
 * @directive: a #SimDirective
 *
 */

void
sim_db_update_plugin_sid (SimDatabase  *database,
                          SimDirective *directive,
                          SimUuid      *uuid)
{
  SimPluginSid    *plugin_sid = NULL;
  gchar           *query;

  g_return_if_fail (SIM_IS_DATABASE (database));
  g_return_if_fail (SIM_IS_DIRECTIVE (directive));
  g_return_if_fail (SIM_IS_UUID (uuid));

  plugin_sid = sim_plugin_sid_new_from_data (SIM_PLUGIN_ID_DIRECTIVE,
                                             sim_directive_get_id (directive),
                                             1,
                                             sim_directive_get_priority (directive),
                                             sim_directive_get_name (directive));

  query = sim_plugin_sid_get_insert_clause (plugin_sid, uuid);
  ossim_debug ("%s: %s", __func__, query);
  sim_database_execute_no_query (database, query);
  g_object_unref (plugin_sid);
  g_free (query);
}

/*
 * Inserts
 */

/**
 * sim_db_insert_event:
 * @database: a #SimDatabase
 * @event: a #SimEvent to insert
 *
 * This function gets an event-> id and insert the event into DB.
 */
void
sim_db_insert_event (SimDatabase *database,
                     SimEvent    *event)
{
  gchar *query = NULL;

  g_return_if_fail (SIM_IS_DATABASE (database));
  g_return_if_fail (SIM_IS_EVENT (event));

  if (event->is_stored)
  {
    ossim_debug ("%s: Duplicate insert event->id: %s", __func__, sim_uuid_get_string (event->id));
    return;
  }


  ossim_debug ("%s: Storing event->id = %s event->is_stored = %u", __func__,
               sim_uuid_get_string (event->id), event->is_stored);

  query = sim_event_get_insert_clause (event);
  ossim_debug ("%s: query= %s", __func__, query);
  sim_database_execute_no_query (database, query);
  g_free (query);

  if (event->src_username || event->dst_username)
  {
    query = sim_event_idm_get_insert_clause (sim_database_get_conn (database), event);
    ossim_debug ("%s: idm_data query_values= %s", __func__, query);
    sim_database_execute_no_query (database, query);
    g_free (query);
  }

  if (event->data || event->log || event->binary_data)
  {
    query = sim_event_extra_get_insert_clause (sim_database_get_conn (database), event);
    ossim_debug ("%s: extra_data query_values= %s", __func__, query);
    sim_database_execute_no_query (database, query);
    g_free (query);
  }
  if (g_hash_table_size (event->otx_data) > 0)
  {
    query = sim_event_pulses_get_insert_clause (sim_database_get_conn (database), event);
    ossim_debug ("%s: otx_data query_values= %s", __func__, query);
    sim_database_execute_no_query (database, query);
    g_free (query);
  }

  event->is_stored = TRUE;
}

void sim_db_insert_alarm_view_tables (SimDatabase *database,
                                      SimEvent *event)
{
  g_return_if_fail (event->backlog_id);

  gchar   *insert, *src_values, *dst_values;
  SimUuid *context_id = sim_context_get_id(event->context);

  if(context_id)
  {
    insert = g_strdup_printf("INSERT IGNORE INTO alarm_ctxs (id_alarm, id_ctx) VALUES (%s, %s) ",
                             sim_uuid_get_db_string(event->backlog_id),
                             sim_uuid_get_db_string(context_id));
    sim_database_execute_no_query (database, insert);
    g_free (insert);
  }

  if(event->src_id || event->dst_id)
  {
    if(event->src_id)
      src_values = g_strdup_printf("(%s, %s)", sim_uuid_get_db_string(event->backlog_id), sim_uuid_get_db_string(event->src_id));
    else
      src_values = NULL;

    if(event->dst_id)
      dst_values = g_strdup_printf("(%s, %s)", sim_uuid_get_db_string(event->backlog_id), sim_uuid_get_db_string(event->dst_id));
    else
      dst_values = NULL;

    insert =  g_strdup_printf("INSERT IGNORE INTO alarm_hosts (id_alarm, id_host) VALUES %s%c%s ",
                              src_values ? src_values : "",
                              (src_values && dst_values) ? ',' : ' ', 
                              dst_values ? dst_values : "");
    sim_database_execute_no_query (database, insert);
    g_free (insert);
    g_free (src_values);
    g_free (dst_values);
  }

  if(event->src_net || event->dst_net)
  {
    if(event->src_net)
      src_values = g_strdup_printf("(%s, %s)", sim_uuid_get_db_string(event->backlog_id), sim_uuid_get_db_string (sim_net_get_id (event->src_net)));
    else
      src_values = NULL;

    if(event->dst_net)
      dst_values = g_strdup_printf("(%s, %s)", sim_uuid_get_db_string(event->backlog_id), sim_uuid_get_db_string (sim_net_get_id (event->dst_net)));
    else
      dst_values = NULL;

    insert =  g_strdup_printf("INSERT IGNORE INTO alarm_nets (id_alarm, id_net) VALUES %s%c%s ",
                              src_values ? src_values : "",
                              (src_values && dst_values) ? ',' : ' ', 
                              dst_values ? dst_values : "");
    sim_database_execute_no_query (database, insert);
    g_free (insert);
  }
}




/**
 * sim_db_insert_alarm:
 * @database: #SimDatabase
 * @event: event alarm
 * @removable: if the alarm is removable or not (it'll be removable only if it's finished/reached timeout).
 *
 * This is usefull if the event has the "alarm" flag. This can occur for example if the event has
 * priority&reliability very high and it has been converted automatically into an alarm. Also, this can occur
 * if the event is a directive_event which has been re-inserted into container from sim_correlation.
 *
 * we also assign here an event->id (if it hasn't got anyone, like the first time the event arrives).
 * event->id is just needed to know if that event belongs to a specific backlog_id (a directive), so if
 * an event is not part of an alarm, it hasn't got any sense to fill event->id.
 *
 */
void
sim_db_insert_alarm (SimDatabase *database,
                     SimEvent    *event,
                     gboolean     removable)
{
  gchar *insert;

  g_return_if_fail (SIM_IS_DATABASE (database));
  g_return_if_fail (SIM_IS_EVENT (event));

  ossim_debug ("%s with id %s", __func__, sim_uuid_get_string (event->id));

  insert = sim_event_get_alarm_insert_clause (database, event, removable);
  sim_database_execute_no_query (database, insert);
  g_free (insert);

  return;
}

/**
 * sim_db_insert_generic_event_plugin_sid:
 * @database: a #SimDatabase
 *
 * Insert a generic event plugind sid with sid = 2000000000 in @database
 */
void
sim_db_insert_generic_event_plugin_sid (SimDatabase *database)
{
  gchar *query = "INSERT IGNORE INTO plugin_sid "
    "(plugin_ctx, plugin_id, sid, priority, reliability, name) "
    "(SELECT ctx, id, 2000000000, 2, 2, CONCAT(name, ': Generic event') FROM plugin);";

  g_return_if_fail (SIM_IS_DATABASE (database));

  if (sim_database_execute_no_query (database, query) < 0)
    g_message ("%s: could not insert generic events plugin sids", __func__);
}

/**
 * sim_db_insert_demo_event_plugin_sid:
 * @database: a #SimDatabase
 *
 * Insert a demo event plugin sid with sid = 20000000 in @database
 */
void
sim_db_insert_demo_event_plugin_sid (SimDatabase *database)
{
  gchar *query = "INSERT IGNORE INTO plugin_sid "
    "(plugin_ctx, plugin_id, sid, priority, reliability, name) "
    "(SELECT ctx, id, 20000000, 2, 2, CONCAT(name, ': Generic event') "
    "FROM plugin);";

  g_return_if_fail (SIM_IS_DATABASE (database));

  if (sim_database_execute_no_query (database, query) < 0)
    g_message ("%s: could not insert demo events plugin sids", __func__);
}

/**
 * sim_db_insert_event_plugin_sid:
 * @database: a #SimDatabase
 * @plugin_sid: a #SimPluginSid
 * @context_id: unique context id
 *
 * Insert @plugin_sid for @context in @database
 */
void
sim_db_insert_plugin_sid (SimDatabase  *database,
                          SimPluginSid *plugin_sid,
                          SimUuid      *context_id)
{
  GString *insert;
  GString *values;
  gchar   *query;

  insert = g_string_new ("REPLACE INTO plugin_sid (");
  values = g_string_new (" VALUES (");

  g_string_append (insert, "plugin_id");
  g_string_append_printf (values, "%d", sim_plugin_sid_get_plugin_id (plugin_sid));

  g_string_append (insert, ", sid");
  g_string_append_printf (values, ", %d", sim_plugin_sid_get_plugin_id (plugin_sid));

  g_string_append (insert, ", reliability");
  g_string_append_printf (values, ", %d", sim_plugin_sid_get_reliability (plugin_sid));

  g_string_append (insert, ", priority");
  g_string_append_printf (values, ", %d", sim_plugin_sid_get_priority (plugin_sid));

  g_string_append (insert, ", name)");
  g_string_append_printf (values, ", '%s')", sim_plugin_sid_get_name (plugin_sid));

  g_string_append (insert, ", ctx)");
  g_string_append_printf (values, ", %s)", sim_uuid_get_db_string (context_id));

  g_string_append (insert, values->str);
  g_string_free (values, TRUE);
  query = g_string_free (insert, FALSE);

  if (sim_database_execute_no_query (database, query) < 0)
    g_message ("%s: Error inserting plugin sid", __func__);

  g_free (query);
}

/**
 * sim_db_insert_backlog:
 * @database: a #SimDatabase
 * @backlog: a #SimDirective
 *
 * Insert @backlog in @database
 */
void
sim_db_insert_backlog (SimDatabase  *database,
                       SimDirective *backlog)
{
  gchar *query = NULL;

  g_return_if_fail (SIM_IS_DATABASE (database));
  g_return_if_fail (SIM_IS_DIRECTIVE (backlog));

  query = sim_directive_backlog_get_insert_clause (backlog);
  ossim_debug ("%s: query= %s", __func__, query);
  sim_database_execute_no_query (database, query);
  g_free (query);
}


/**
 * sim_db_insert_backlog_event:
 * @database: a #SimDatabase
 * @backlog: a #SimDirective
 * @event: a #SimEvent
 *
 * Insert @backlog @event in @databse
 */
void
sim_db_insert_backlog_event (SimDatabase  *database,
                             SimDirective *backlog,
                             SimEvent     *event,
                             gint          level)
{
  gchar *query;

  g_return_if_fail (SIM_IS_DATABASE (database));
  g_return_if_fail (SIM_IS_DIRECTIVE (backlog));
  g_return_if_fail (SIM_IS_EVENT (event));

  query = sim_directive_backlog_event_get_insert_clause (backlog, event, level);
  ossim_debug ("%s: query= %s", __func__, query);
  sim_database_execute_no_query (database, query);
  g_free (query);
}

/**
 * sim_db_insert_host_os:
 *
 * Insert 'host os' event in @databse
 */
void
sim_db_insert_host_os (SimDatabase  *database,
                       SimInet      *inet,
                       gchar        *date,
                       SimInet      *sensor,
                       gchar        *interface,
                       gchar        *os,
                       SimUuid      *context_id)
{
  gchar        *query = NULL;
  gchar        *os_escaped;

  g_return_if_fail (SIM_IS_DATABASE (database));
  g_return_if_fail (SIM_IS_INET (inet));
  g_return_if_fail (date);
  g_return_if_fail (os);

  os_escaped = g_strescape (os, NULL);

  query = g_strdup_printf ("INSERT INTO host_os (id, ctx, sensor, date, os, interface) "
                           "SELECT id, %s, %s, '%s', '%s', '%s' FROM host "
                           "WHERE ip = %s and ctx = %s LIMIT 1",
                           sim_uuid_get_db_string (context_id),
                           sensor ? sim_inet_get_db_string (sensor) : "NULL",
                           date,
                           os_escaped,
                           interface,
                           sim_inet_get_db_string (inet),
                           sim_uuid_get_db_string (context_id));

  sim_db_execute_query (database, query);

  g_free (os_escaped);
}

/**
 * sim_db_insert_host_mac:
 *
 * Insert 'host mac' event in @databse
 */
void
sim_db_insert_host_mac (SimDatabase *database,
                        SimInet     *inet,
                        gchar       *date,
                        gchar       *mac,
                        gchar       *vendor,
                        gchar       *interface,
                        SimInet     *sensor,
                        SimUuid     *context_id)
{
  gchar   *query;
  gchar   *vendor_esc;

  g_return_if_fail (SIM_IS_DATABASE (database));
  g_return_if_fail (SIM_IS_INET (inet));
  g_return_if_fail (date);
  g_return_if_fail (mac);
  g_return_if_fail (interface);
  g_return_if_fail (sensor);

//  we want to insert only the hosts defined in Policy->hosts or inside a network from policy->networks
//  if((sim_container_get_host_by_ia(container,ia) == NULL) && (sim_container_get_nets_has_ia(container,ia) == NULL))
//    return;

  vendor_esc = g_strescape (vendor, NULL);


  query = g_strdup_printf ("INSERT INTO host_mac (id, ctx, sensor, date, mac, vendor, interface) "
                           "SELECT id, %s, %s, '%s', '%s', '%s', '%s' FROM host WHERE ip = %s and ctx = %s LIMIT 1",
                           sim_uuid_get_db_string (context_id),
                           sensor ? sim_inet_get_db_string (sensor) : "NULL",
                           date,
                           mac,
                           (vendor_esc) ? vendor_esc : "",
                           interface,
                           sim_inet_get_db_string (inet),
                           sim_uuid_get_db_string (context_id));

  g_free (vendor_esc);

  ossim_debug ("%s: query: %s", __func__, query);

  sim_db_execute_query (database, query);

  g_free (query);
}

/**
 * sim_db_insert_host_service:
 *
 * Insert 'host service' in @databse
 */
void
sim_db_insert_host_service (SimDatabase   *database,
                            SimInet       *inet,
                            gchar         *date,
                            gint           port,
                            gint           protocol,
                            SimInet       *sensor,
                            gchar         *interface,
                            gchar         *service,
                            gchar         *application,
                            SimUuid       *context_id)
{
  gchar           *query;
  gint             plugin_id;
  struct servent  *temp_serv  = NULL;
  struct protoent *temp_proto = NULL;

  g_return_if_fail (SIM_IS_DATABASE (database));
  g_return_if_fail (SIM_IS_INET (inet));
  g_return_if_fail (date);
  g_return_if_fail (port >= 0); /* Needed for ints */
  g_return_if_fail (protocol >= 0);
  g_return_if_fail (sensor);
  g_return_if_fail (service);
  g_return_if_fail (application);


  temp_proto = getprotobynumber (protocol);
  if (temp_proto->p_name == NULL)
    return; /* Since we don't know the proto we wont insert a service without a protocol */

  temp_serv = getservbyport (port, temp_proto->p_name);

  query = g_strdup_printf ("INSERT INTO host_services "
                           "(id, date, port, protocol, service, service_type, version, origin, sensor, interface, ctx) "
                           "SELECT id, '%s', %u, %u, '%s', '%s', '%s', 0, %s, '%s', %s "
                           "FROM host WHERE ip = %s and ctx = %s LIMIT 1",
                           date,
                           port,
                           protocol,
                           (temp_serv != NULL) ? temp_serv->s_name : "unknown",
                           service,
                           application,
                           sim_inet_get_db_string (sensor),
                           interface,
                           sim_uuid_get_db_string (context_id),
                           sim_inet_get_db_string (inet),
                           sim_uuid_get_db_string (context_id));

  sim_db_execute_query (database, query);

  g_free (query);

  plugin_id = SIM_PLUGIN_SERVICE;

  sim_db_insert_host_plugin_sid (database, inet, plugin_id, port, context_id);
}


/**
 * sim_db_insert_host_plugin_sid:
 *
 * Insert host plugin sid in @database
 */
void
sim_db_insert_host_plugin_sid (SimDatabase *database,
                               SimInet     *inet,
                               gint         plugin_id,
                               gint         plugin_sid,
                               SimUuid     *context_id)
{
  gchar *query;

  g_return_if_fail (SIM_IS_DATABASE (database));
  g_return_if_fail (SIM_IS_INET (inet));

  // this is a plugin_sid which comes from an special event, (the plugin_id)
  query = g_strdup_printf ("REPLACE INTO host_plugin_sid (host_ip, plugin_id, plugin_sid, ctx) "
                           "VALUES (%s, %d, %d, %s)",
                           sim_inet_get_db_string (inet),
                           plugin_id,
                           plugin_sid,
                           sim_uuid_get_db_string (context_id));

  sim_db_execute_query (database, query);

  g_free (query);
}

/**
 * sim_db_update_backlog:
 * @database: a #SimDatabase
 * @backlog: a #SimDirective
 *
 * Updates @backlog in @databse
 */
void
sim_db_update_backlog (SimDatabase  *database,
                       SimDirective *backlog)
{
  gchar *query = NULL;

  query = sim_directive_backlog_get_update_clause (backlog);
  ossim_debug ("%s: query= %s", __func__, query);
  sim_database_execute_no_query (database, query);
  g_free (query);
}

/**
 * sim_db_insert_sensor:
 * @database: a #SimDatabase
 * @sensor: a #SimSensor
 *
 * Inserts a new #SimSensor in our database.
 */
void
sim_db_insert_sensor (SimDatabase  * database,
		      SimSensor    * sensor)
{
  gchar * query;

  g_return_if_fail (SIM_IS_DATABASE (database));
  g_return_if_fail (SIM_IS_SENSOR (sensor));

  query = g_strdup_printf ("INSERT INTO sensor (id, name, ip, port, connect, tzone) "
                           "VALUES (%s, '%s', %s, %d, %d, '%4.2f') "
                           "ON DUPLICATE KEY UPDATE "
                           "ip = %s, port = %d, connect = %d, tzone = '%4.2f'",
                           sim_uuid_get_db_string (sim_sensor_get_id (sensor)),
                           sim_sensor_get_name (sensor),
                           sim_inet_get_db_string (sim_sensor_get_ia (sensor)),
                           sim_sensor_get_port (sensor),
                           sim_sensor_is_connect (sensor),
                           sim_sensor_get_tzone (sensor),
                           sim_inet_get_db_string (sim_sensor_get_ia (sensor)),
                           sim_sensor_get_port (sensor),
                           sim_sensor_is_connect (sensor),
                           sim_sensor_get_tzone (sensor));

  sim_db_execute_query (database, query);
  g_free (query);

  return;
}


/**
 * sim_db_insert_sensor_properties:
 * @database: a #SimDatabase
 * @sensor: a #SimSensor
 *
 * Inserts sensor properties from #SimSensor in our database.
 */
void
sim_db_insert_sensor_properties (SimDatabase  * database,
		                             SimSensor    * sensor)
{
  SimVersion *version;

  g_return_if_fail (SIM_IS_DATABASE (database));
  g_return_if_fail (SIM_IS_SENSOR (sensor));

  version = sim_sensor_get_version (sensor);

  if (version)
  {
    gchar *query;

    query = g_strdup_printf("INSERT INTO sensor_properties (sensor_id, version, has_nagios) "
                            "VALUES (%s, '%d.%d.%d', 1) ON DUPLICATE KEY UPDATE version = '%d.%d.%d'",
                             sim_uuid_get_db_string (sim_sensor_get_id (sensor)),
                             version->major, version->minor, version->micro,
                             version->major, version->minor, version->micro);

    sim_db_execute_query (database, query);
    g_free (query);
  }
}

/**
 * sim_db_update_sensor_by_ia:
 * @database: a #SimDatabase
 * @sensor: a #SimSensor
 *
 * Updates a #SimSensor using its network address.
 */
void
sim_db_update_sensor_by_ia (SimDatabase  * database,
                            SimSensor    * sensor)
{
  gchar * query;

  g_return_if_fail (SIM_IS_DATABASE (database));
  g_return_if_fail (SIM_IS_SENSOR (sensor));

  query = g_strdup_printf ("UPDATE sensor SET id=%s, name='%s', port=%d, connect=%d "
                           "WHERE ip=%s",
                           sim_uuid_get_db_string (sim_sensor_get_id (sensor)),
                           sim_sensor_get_name (sensor),
                           sim_sensor_get_port (sensor),
                           sim_sensor_is_connect (sensor),
                           sim_inet_get_db_string (sim_sensor_get_ia (sensor)));

  sim_db_execute_query (database, query);
  g_free (query);

  return;
}

/**
 * sim_db_insert_dummy_backlog:
 * @database: a #SimDatabase
 * @event: a #SimEvent
 * @backlog_uuid: a #SimUuid
 *
 */
void
sim_db_insert_dummy_backlog (SimDatabase  *database,
                             SimEvent     *event)
{
  gchar *query, *values;

  g_return_if_fail (SIM_IS_DATABASE (database));
  g_return_if_fail (SIM_IS_EVENT (event));

  values = sim_directive_dummy_backlog_get_values_clause (event);
  query = g_strdup_printf("INSERT INTO backlog VALUES %s", values);
  ossim_debug ("%s: query= %s", __func__, query);
  sim_database_execute_no_query (database, query);
  g_free (values);
  g_free (query);
}

/**
 * sim_db_insert_dummy_backlog_event:
 * @database: a #SimDatabase
 * @event: a #SimEvent
 *
 */
void
sim_db_insert_dummy_backlog_event (SimDatabase  *database,
                                   SimEvent     *event)
{
  gchar *query, *values;

  g_return_if_fail (SIM_IS_DATABASE (database));
  g_return_if_fail (SIM_IS_EVENT (event));

  values = sim_directive_dummy_backlog_event_get_values_clause (event);
  query = g_strdup_printf("INSERT INTO backlog_event VALUES %s", values);
  ossim_debug ("%s: query = %s", __func__, query);
  sim_database_execute_no_query (database, query);
  g_free (values);
  g_free (query);
}

