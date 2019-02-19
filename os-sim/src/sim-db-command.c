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

#include "sim-db-command.h"

extern SimMain    ossim;
// This defines a network endpoint, e.g. source or destination
typedef enum
{
  SOURCE = 0,
  DEST = 1
}
netpoint;

#define   DB_BINARY_ANY "0x00000000000000000000000000000000"
#define   UPDATE_TAXONOMY_INFO_FILE "/usr/share/ossim-taxonomy/directives-taxo.sql"

typedef gpointer (*DMFunc) (GdaDataModel *dm, gint row);

// Prototipes
static GList *      sim_db_get_objects                      (SimDatabase    *database,
                                                             gchar          *query,
                                                             DMFunc          new_from_dm);
static SimEngine *  sim_db_new_engine                       (GdaDataModel   *dm,
                                                             gint            row);
static SimContext * sim_db_new_context                      (GdaDataModel   *dm,
                                                             gint            row);
static void         sim_db_load_policy_data                 (SimDatabase    *database,
                                                             SimPolicy      *policy);
static gboolean     sim_db_load_policy_net_entity           (SimDatabase    *database,
                                                             gchar          *query,
                                                             SimPolicy      *policy,
                                                             netpoint        point);
static gboolean     sim_db_load_policy_host_entity          (SimDatabase    *database,
                                                             gchar          *query,
                                                             SimPolicy      *policy,
                                                             netpoint        point);
static void         sim_db_load_policy_ports                (SimDatabase    *database,
                                                             SimPolicy      *policy);
static void         sim_db_load_policy_sensors              (SimDatabase    *database,
                                                             SimPolicy      *policy);
static void         sim_db_load_policy_plugin_groups        (SimDatabase    *database,
                                                             SimPolicy      *policy);
static void         sim_db_load_policy_plugins_from_group   (SimDatabase    *database,
                                                             SimPolicy      *policy,
                                                             SimUuid       * group_id);
static void         sim_db_load_policy_role                 (SimDatabase    *database,
                                                             SimPolicy      *policy);
static void         sim_db_load_policy_risks                (SimDatabase    *database,
                                                             SimPolicy      *policy);
static void         sim_db_load_policy_taxonomy             (SimDatabase   * database,
                                                             SimPolicy     * policy);
static gint         sim_db_get_policy_num_actions           (SimDatabase    *database,
                                                             SimPolicy      *policy);
static gint         sim_db_get_policy_num_alarm_actions     (SimDatabase    *database,
                                                             SimPolicy      *policy);


/**
 * sim_db_get_objects:
 * @database: a SimDatabase
 * @query: gchar * query
 * @new_from_dm: DMFunc new objects function
 *
 * Returns a list of gobjects created from @query
 * with @new_from_dm function
 */
static GList *
sim_db_get_objects (SimDatabase *database,
                    gchar       *query,
                    DMFunc       new_from_dm)
{
  GList *obj_list = NULL;
  GdaDataModel *dm;
  gint i, rows;

  dm = sim_database_execute_single_command (database, query);
  if (dm)
  {
    rows = gda_data_model_get_n_rows (dm);

    for (i = 0; i < rows; i++)
    {
      GObject *obj = (*new_from_dm) (dm, i);

      // Add only non NULL objects.
      if (obj)
        obj_list = g_list_prepend (obj_list, obj);
    }

    g_object_unref (dm);
  }
  else
  {
    g_message ("DATA MODEL ERROR");
  }

  return obj_list;
}

/**
 * sim_db_load_software_cpe:
 * @database: #SimDatabase object
 *
 * Load CPE and the names associated to them from database
 *
 * returns: GHashTable with software_cpe table loaded
 */
GHashTable *
sim_db_load_software_cpe (SimDatabase *database)
{
  GHashTable *software_cpe = NULL;
  GdaDataModel *dm;
  const GValue *value;
  gint i, rows;
  gchar *cpe, *name;

  g_return_val_if_fail (SIM_IS_DATABASE (database), NULL);

  software_cpe = g_hash_table_new_full (g_str_hash, g_str_equal, g_free, g_free);

  dm = sim_database_execute_single_command (database, "SELECT cpe, name FROM software_cpe");

  if (dm)
  {
    rows = gda_data_model_get_n_rows (dm);

    for (i = 0; i < rows; i++)
    {
      value = gda_data_model_get_value_at (dm, 0, i, NULL);
      cpe = g_value_dup_string (value);

      value = gda_data_model_get_value_at (dm, 1, i, NULL);
      name = g_value_dup_string (value);

      g_hash_table_insert (software_cpe, cpe, name);
    }
  }

  return software_cpe;
}

/**
 * sim_db_load_common_plugins:
 * @database: #SimDatabase object
 *
 * Load common plugin from database
 *
 * returns: GList with #SimPlugin loaded
 */
GList *
sim_db_load_common_plugins (SimDatabase *database)
{
  GList *plugin_list;

  SimUuid *id = sim_uuid_new_from_string (SIM_CONTEXT_COMMON);

  plugin_list = sim_db_load_plugins (database, id);

  g_object_unref (id);

  return plugin_list;
}

static SimEngine *
sim_db_new_engine (GdaDataModel *dm,
                   gint          row)
{
  SimEngine *engine;
  const GValue *value;
  SimUuid *id;
  gchar *name;

  g_return_val_if_fail (GDA_IS_DATA_MODEL (dm), NULL);

  value = gda_data_model_get_value_at (dm, 0, row, NULL);
  id = sim_uuid_new_from_blob (gda_value_get_blob (value));

  value = gda_data_model_get_value_at (dm, 1, row, NULL);
  name = value ? gda_value_stringify ((GValue *)value) : g_strdup ("");//g_value_dup_string (value);

  engine = sim_engine_new (id, name);

  g_object_unref (id);
  g_free (name);

  return engine;
}

/**
 * sim_db_load_engines:
 * @database: #SimDatabase object
 * @server_id: unique id for server.
 *
 * Load engines with @server_id from @database
 *
 * returns: GList with #SimEngines loaded
 */
GList *
sim_db_load_engines (SimDatabase *database,
                     SimUuid     *server_id)
{
  GList *engines_list;
  gchar *query;

  g_return_val_if_fail (SIM_IS_DATABASE (database), NULL);

  query = g_strdup_printf ("SELECT id, name FROM acl_entities "
                           "WHERE entity_type = 'engine' "
                           "AND server_id = %s", sim_uuid_get_db_string (server_id));

  engines_list = sim_db_get_objects (database, query, (DMFunc) sim_db_new_engine);

  g_free (query);

  return engines_list;
}

static SimContext *
sim_db_new_context (GdaDataModel *dm,
                    gint          row)
{
  SimContext *context;
  const GValue *value;
  SimUuid *id;
  gchar *name;

  g_return_val_if_fail (GDA_IS_DATA_MODEL (dm), NULL);

  value = gda_data_model_get_value_at (dm, 0, row, NULL);
  id = sim_uuid_new_from_blob (gda_value_get_blob (value));

  value = gda_data_model_get_value_at (dm, 1, row, NULL);
  name = value ? gda_value_stringify ((GValue *)value) : g_strdup ("");//g_value_dup_string (value);

  context = sim_context_new_full (id, name, NULL);

  g_object_unref (id);
  g_free (name);

  return context;
}

/**
 * sim_db_load_local_contexts:
 * @database: #SimDatabase object
 * @server_id: unique id for server.
 *
 * Load contexts with @server_id from @database
 *
 * returns: GList with #SimContext loaded
 */
GList *
sim_db_load_local_contexts (SimDatabase *database,
                            SimUuid     *server_id)
{
  GList *contexts_list;
  gchar *query;

  g_return_val_if_fail (SIM_IS_DATABASE (database), NULL);

  query = g_strdup_printf ("SELECT id, name FROM acl_entities "
                           "WHERE entity_type = 'context' "
                           "AND server_id = %s", sim_uuid_get_db_string (server_id));

  contexts_list = sim_db_get_objects (database, query, (DMFunc) sim_db_new_context);

  g_free (query);

  return contexts_list;
}

/**
 * sim_db_load_local_contexts:
 * @database: #SimDatabase object
 * @server_id: unique id for server.
 *
 * Load contexts with @server_id from @database
 *
 * returns: GList with #SimContext loaded
 */
GList *
sim_db_load_external_contexts (SimDatabase *database,
                               SimUuid     *server_id)
{
  GList *contexts_list;
  gchar *query;

  g_return_val_if_fail (SIM_IS_DATABASE (database), NULL);

  query = g_strdup_printf ("SELECT id, name FROM acl_entities "
                           "WHERE entity_type = 'engine'  OR "
                           "(entity_type = 'context' AND server_id != %s)",
                           sim_uuid_get_db_string (server_id));

  contexts_list = sim_db_get_objects (database, query, (DMFunc) sim_db_new_context);

  g_free (query);

  return contexts_list;
}


/**
 * sim_db_load_plugins:
 * @database: #SimDatabase object
 * @context_id: unique id for context.
 *
 * Load plugin from database
 *
 * returns: GList with #SimPlugin loaded
 */
GList *
sim_db_load_plugins (SimDatabase *database,
                     SimUuid     *context_id)
{
  GList *plugins_list;
  gchar *query;

  g_return_val_if_fail (SIM_IS_DATABASE (database), NULL);

  query = g_strdup_printf ("SELECT id, type, name, description, product_type FROM plugin WHERE ctx = %s", sim_uuid_get_db_string (context_id));

  plugins_list = sim_db_get_objects (database, query, (DMFunc) sim_plugin_new_from_dm);

  g_free (query);

  return plugins_list;
}

/**
 * sim_db_load_plugin_sid:
 * @database: #SimDatabase object
 * @context_id: unique id for context.
 *
 * Load plugin sids from database for @context_id
 *
 * returns: GList with #SimPluginSids loaded
 */
GList *
sim_db_load_plugin_sids (SimDatabase *database,
                         SimUuid     *context_id)
{
  GList *plugins_list;
  gchar *query;

  g_return_val_if_fail (SIM_IS_DATABASE (database), NULL);

  query = g_strdup_printf ("SELECT plugin_id, sid, reliability, priority, name, category_id, subcategory_id FROM plugin_sid WHERE plugin_ctx = %s", sim_uuid_get_db_string (context_id));

  plugins_list = sim_db_get_objects (database, query, (DMFunc) sim_plugin_sid_new_from_dm);

  g_free (query);

  return plugins_list;
}

/**
 * sim_db_load_common_plugin_sids:
 * @database: #SimDatabase object
 *
 * Load common plugin sids into list, and write sids 20000000 (demo event)
 * and 2000000000 (generic event) into db.
 *
 * returns: GList with common plugin sids
 */
GList *
sim_db_load_common_plugin_sids (SimDatabase *database)
{
  GList *plugin_sids = NULL;

  SimUuid *id = sim_uuid_new_from_string (SIM_CONTEXT_COMMON);

  g_return_val_if_fail (SIM_IS_DATABASE (database), NULL);

  plugin_sids = sim_db_load_plugin_sids (database, id);

  sim_db_insert_generic_event_plugin_sid (database);
  sim_db_insert_demo_event_plugin_sid (database);

  g_object_unref (id);

  return plugin_sids;
}

/**
 * sim_db_load_nets:
 * @database: #SimDatabase object
 * @context_id: unique id for context id.
 */
GList *
sim_db_load_nets (SimDatabase *database,
                  SimUuid     *context_id)
{
  GList *net_list = NULL;
  gchar *query;

  g_return_val_if_fail (SIM_IS_DATABASE (database), NULL);

  query = g_strdup_printf ("SELECT id, name, ips, asset, external_net "
                           "FROM net WHERE ctx = %s", sim_uuid_get_db_string (context_id));

  net_list = sim_db_get_objects (database, query, (DMFunc) sim_net_new_from_dm);

  g_free (query);

  return net_list;
}

/**
 * sim_db_load_hosts:
 * @database: #SimDatabase object
 * @context_id: unique context id
 *
 */
GList *
sim_db_load_hosts (SimDatabase *database,
                   SimUuid     *context_id)
{
  GList *host_list = NULL;
  GList *list;
  gchar *query;
  GdaDataModel *dm;
  const GdaBinary *binary;
  const GValue *value;

  g_return_val_if_fail (SIM_IS_DATABASE (database), NULL);

  query = g_strdup_printf ("SELECT h.id, h.hostname, h.asset, h.external_host FROM host h "
                           "WHERE ctx = %s GROUP BY h.id", sim_uuid_get_db_string (context_id));

  host_list = sim_db_get_objects (database, query, (DMFunc) sim_host_new_from_dm);
  g_free (query);

  /* Get inets of each host */
  list = host_list;
  while (list)
  {
    SimHost *host = (SimHost *) list->data;
    SimUuid *host_id = sim_host_get_id (host);

    query = g_strdup_printf ("SELECT ip FROM host_ip WHERE host_id = %s",
                             sim_uuid_get_db_string (host_id));

    dm = sim_database_execute_single_command (database, query);
    if (dm)
    {
      gint row;
      for (row = 0; row < gda_data_model_get_n_rows (dm); row++)
      {
        value = gda_data_model_get_value_at (dm, 0, row, NULL);
        if (!gda_value_is_null (value))
        {
          binary = (GdaBinary *) gda_value_get_blob (value);
          SimInet *inet = sim_inet_new_from_db_binary (binary->data, binary->binary_length);
          sim_host_add_inet (host, inet);
          g_object_unref (inet);
        }
      }

      g_object_unref (dm);
    }

    g_free (query);
    list = g_list_next (list);
  }

  return host_list;
}

/**
 * sim_db_load_sensors:
 * @database: #SimDatabase object
 *
 */
GList *
sim_db_load_sensors (SimDatabase *database)
{
  GList *sensor_list = NULL;
  gchar *query = "SELECT id, name, ip, port, connect, tzone, sensor_properties.version FROM sensor INNER JOIN sensor_properties ON sensor.id = sensor_properties.sensor_id";

  g_return_val_if_fail (SIM_IS_DATABASE (database), NULL);

  sensor_list = sim_db_get_objects (database, query, (DMFunc) sim_sensor_new_from_dm);

  return sensor_list;
}

/**
 * sim_db_load_servers:
 * @database: #SimDatabase object
 *
 */
GList *
sim_db_load_servers (SimDatabase *database)
{
  GList *server_list = NULL;
  gchar *query = "SELECT id, name, ip, port FROM server";

  g_return_val_if_fail (SIM_IS_DATABASE (database), NULL);

  server_list = sim_db_get_objects (database, query, (DMFunc) sim_server_new_from_dm);

  return server_list;
}

/**
 * sim_db_load_host_plugin_sids
 * @database: #SimDatabase object
 * @context_id: unique context id
 *
 */
GList *
sim_db_load_host_plugin_sids (SimDatabase *database,
                              SimUuid     *context_id)
{
  GdaDataModel *dm;
  gchar        *query;
  GList        *list = NULL;

  query = g_strdup_printf ("SELECT host_ip, plugin_reference.ctx, plugin_reference.plugin_id, plugin_reference.plugin_sid, reference_id, reference_sid "
                           "FROM host_plugin_sid INNER JOIN plugin_reference "
                           "ON (host_plugin_sid.plugin_id = plugin_reference.reference_id "
                           "AND host_plugin_sid.plugin_sid = plugin_reference.reference_sid) "
                           "WHERE plugin_reference.ctx = UNHEX('00000000000000000000000000000000') OR "
                           "plugin_reference.ctx = %s "
                           "GROUP BY plugin_reference.plugin_id, plugin_reference.plugin_sid",
                           sim_uuid_get_db_string (context_id));

  dm = sim_database_execute_single_command (database, query);
  if (dm)
  {
    gint row;
    for (row = 0; row < gda_data_model_get_n_rows (dm); row++)
    {
      const GValue *value;
      const GdaBinary *binary;
      SimUuid * context_id;
      SimHostPluginSid *host_plugin_sid;

      host_plugin_sid = g_new0 (SimHostPluginSid, 1);

      /* Build a SimHostPluginSids with values. */
      value = gda_data_model_get_value_at (dm, 0, row, NULL);
      binary = (GdaBinary *) gda_value_get_blob (value);
      host_plugin_sid->host_ip = sim_inet_new_from_db_binary (binary->data, binary->binary_length);

      value = gda_data_model_get_value_at (dm, 1, row, NULL);
      context_id = sim_uuid_new_from_blob (gda_value_get_blob (value));
      host_plugin_sid->context = g_object_ref (sim_container_get_context (ossim.container, context_id));
      g_object_unref (context_id);

      value = gda_data_model_get_value_at (dm, 2, row, NULL);
      host_plugin_sid->plugin_id = g_value_get_int (value);
      value = gda_data_model_get_value_at (dm, 3, row, NULL);
      host_plugin_sid->plugin_sid = g_value_get_int (value);
      value = gda_data_model_get_value_at (dm, 4, row, NULL);
      host_plugin_sid->reference_id = g_value_get_int (value);
      value = gda_data_model_get_value_at (dm, 5, row, NULL);
      host_plugin_sid->reference_sid = g_value_get_int (value);

      /* Insert into the list */
      list = g_list_prepend (list, host_plugin_sid);
    }

    g_object_unref (dm);
  }
  else
  {
    g_message ("%s: DATA MODEL ERROR", __func__);
  }

  g_free (query);

  return list;
}

/**
 * sim_db_load_server_role:
 * @database: #SimDatabase object
 * @server: #SimServer object
 *
 */
SimRole *
sim_db_load_server_role (SimDatabase *database,
                         SimUuid     *server_id)
{
  SimRole *role = NULL;
  gchar *query;
  GdaDataModel *dm;
  gint logger_if_priority;


  g_return_val_if_fail (SIM_IS_DATABASE (database), NULL);
  g_return_val_if_fail (SIM_IS_UUID (server_id), NULL);

  query = g_strdup_printf ("SELECT correlate, cross_correlate, store, qualify, "
                           "resend_alarm, resend_event, sign, sim, sem, "
                           "alarms_to_syslog, reputation "
                           "FROM server_role WHERE server_id = %s",
                           sim_uuid_get_db_string (server_id));

  dm = sim_database_execute_single_command (database, query);
  if (dm)
  {
    if (gda_data_model_get_n_rows (dm) > 0)
    {
      role = sim_role_new_from_dm (dm, 0);

      logger_if_priority = sim_db_get_config_logger_if_priority (database);
      sim_role_set_logger_if_priority (role, logger_if_priority);
    }
  }

  g_free (query);

  return role;
}

/**
 * sim_db_load_taxonomy_products:
 * @database: #SimDatabase object
 * @server: #SimServer object
 *
 */
GList *
sim_db_load_taxonomy_products (SimDatabase *database)
{
  GList        *products = NULL;
  gint          product_id;
  GdaDataModel *dm;
  const GValue *value;
  gchar        *query = "SELECT id FROM product_type";

  g_return_val_if_fail (SIM_IS_DATABASE (database), NULL);

  dm = sim_database_execute_single_command (database, query);
  if (dm)
  {
    gint row;
    for (row = 0; row < gda_data_model_get_n_rows (dm); row++)
    {
      value = gda_data_model_get_value_at (dm, 0, row, NULL);
      product_id = value ? g_value_get_int (value) : 0;

      products = g_list_append (products, GINT_TO_POINTER (product_id));
    }

    g_object_unref (dm);
  }
  else
  {
    g_message ("PRODUCT TYPE DATA MODEL ERROR");
  }

  return products;
}

/**
 * sim_db_load_plugin_ids_with_product:
 * @database: #SimDatabase object
 * @product: product_id
 *
 */
GList *
sim_db_load_plugin_ids_with_product (SimDatabase *database,
                                     gint         product_id,
                                     SimUuid     *context_id)
{
  GList        *plugin_ids = NULL;
  gint          plugin_id;
  GdaDataModel *dm;
  gchar        *query;
  const GValue *value;

  g_return_val_if_fail (SIM_IS_DATABASE (database), NULL);

  query = g_strdup_printf ("SELECT id FROM plugin WHERE ctx = %s AND product_type = %d",
                           sim_uuid_get_db_string (context_id),
                           product_id);

  dm = sim_database_execute_single_command (database, query);
  g_free(query);
  if (dm)
  {
    gint row;
    for (row = 0; row < gda_data_model_get_n_rows (dm); row++)
    {
      value = gda_data_model_get_value_at (dm, 0, row, NULL);
      plugin_id = g_value_get_int (value);

      plugin_ids = g_list_append (plugin_ids, GINT_TO_POINTER (plugin_id));
    }

    g_object_unref (dm);
  }
  else
  {
    g_message ("PLUGIN DATA MODEL ERROR");
  }

  return plugin_ids;
}

/**
 * sim_db_idm_entries:
 * @database: #SimDatabase object
 * @context_id: UUID of the context from which load hosts/entries
 *
 * Returns: a hash table in which each entry is a list of the interfaces
 *          that a single host have. Each list member has the common host
 *          properties plus properties specific to each interface (like running services).
 *
 * TODO: this data structure could be memory optimized by just storing common host information
 *       once, not common information plus specific information for each interface.
 */
GHashTable *
sim_db_load_idm_entries (SimDatabase *database,
                         SimUuid     *context_id)
{
  GHashTable *entry_table;
  GPtrArray *interface_list;
  gchar *query, *query_host_properties, *query_host_services, *query_host_software;
  GdaDataModel *dm;
  const GValue *value;
  const GdaBinary *binary;
  gint i, rows;

  SimIdmEntry *entry;
  SimUuid *host_id;
  SimInet *host_inet;
  gchar *host_mac;
  gchar *host_hostname;
  gchar *host_fqdns;

  g_return_val_if_fail (SIM_IS_DATABASE (database), NULL);

  entry_table = g_hash_table_new_full (sim_uuid_hash, sim_uuid_equal, g_object_unref, (GDestroyNotify) g_ptr_array_unref);

  query = g_strdup_printf (
            "SELECT h.id, hi.ip, hi.mac, h.hostname, h.fqdns FROM host h, host_ip hi "
            "WHERE ctx=%s AND h.id = hi.host_id", sim_uuid_get_db_string (context_id));
  dm = sim_database_execute_single_command (database, query);

  if (dm)
  {
    rows = gda_data_model_get_n_rows (dm);

    for (i = 0; i < rows; i++)
    {
      value = gda_data_model_get_value_at (dm, 0, i, NULL);
      host_id = sim_uuid_new_from_blob (gda_value_get_blob (value));

      value = gda_data_model_get_value_at (dm, 1, i, NULL);
      if (gda_value_is_null (value))
        host_inet = NULL;
      else
      {
        binary = (GdaBinary *) gda_value_get_blob (value);
        host_inet = sim_inet_new_from_db_binary (binary->data, binary->binary_length);
      }

      value = gda_data_model_get_value_at (dm, 2, i, NULL);
      if (gda_value_is_null (value))
        host_mac = NULL;
      else
      {
        binary = (GdaBinary *) gda_value_get_blob (value);
        host_mac = sim_bin_to_mac (binary->data);
      }

      value = gda_data_model_get_value_at (dm, 3, i, NULL);
      host_hostname = gda_value_stringify ((GValue *)value);
      if (!host_hostname)
        host_hostname = g_strdup ("");

      value = gda_data_model_get_value_at (dm, 4, i, NULL);
      host_fqdns = gda_value_stringify ((GValue *)value);
      if (!host_fqdns)
        host_fqdns = g_strdup ("");

      entry = sim_idm_entry_new_from_dm (host_id, host_inet, host_mac, host_hostname, host_fqdns);

      interface_list = g_hash_table_lookup (entry_table, host_id);
      if (!interface_list)
      {
        interface_list = g_ptr_array_new_with_free_func ((GDestroyNotify) g_object_unref);
        g_hash_table_insert (entry_table, g_object_ref (host_id), interface_list);
      }
      g_ptr_array_add (interface_list, entry);

      g_object_unref (host_id);
      if (host_inet)
        g_object_unref (host_inet);
      g_free (host_mac);
      g_free (host_hostname);
    }

    g_object_unref (dm);

    query_host_properties = g_strdup_printf (
            "SELECT h.id, hp.property_ref, hp.source_id, hp.value FROM host h, host_properties hp "
            "WHERE ctx=%s AND h.id = hp.host_id", sim_uuid_get_db_string (context_id));
    dm = sim_database_execute_single_command (database, query_host_properties);

    if (dm)
    {
      sim_idm_entry_add_properties_from_dm (entry_table, dm);

      g_object_unref (dm);
    }
    else
    {
      g_message ("HOST PROPERTIES DATA MODEL ERROR");
    }

    g_free (query_host_properties);

    query_host_services = g_strdup_printf (
            "SELECT h.id, hi.ip, hs.port, hs.protocol, hs.source_id, hs.service, hs.version FROM host h, host_services hs, host_ip hi "
            "WHERE ctx=%s AND h.id = hi.host_id AND hs.host_ip = hi.ip", sim_uuid_get_db_string (context_id));
    dm = sim_database_execute_single_command (database, query_host_services);

    if (dm)
    {
      sim_idm_entry_add_services_from_dm (entry_table, dm);

      g_object_unref (dm);
    }
    else
    {
      g_message ("HOST SERVICES DATA MODEL ERROR");
    }

    g_free (query_host_services);

    query_host_software = g_strdup_printf (
            "SELECT h.id, hs.cpe, hs.banner FROM host h, host_software hs "
            "WHERE ctx=%s AND h.id = hs.host_id", sim_uuid_get_db_string (context_id));
    dm = sim_database_execute_single_command (database, query_host_software);

    if (dm)
    {
      sim_idm_entry_add_software_from_dm (entry_table, dm);

      g_object_unref (dm);
    }
    else
    {
      g_message ("HOST SOFTWARE DATA MODEL ERROR");
    }

    g_free (query_host_software);

    sim_idm_entry_finish_from_dm (entry_table);
  }
  else
  {
    g_message ("HOST/HOST IP DATA MODEL ERROR");
  }

  g_free (query);

  return entry_table;
}

/**
 * sim_db_load_policies:
 * @database: #SimDatabase object
 * @context_id: unique context id
 *
 */
GList *
sim_db_load_policies (SimDatabase *database,
                      SimUuid     *context_id)
{
  GList        *policies = NULL;
  GdaDataModel *dm;
  gchar        *query;

  g_return_val_if_fail (SIM_IS_DATABASE (database), NULL);

  query = g_strdup_printf ("SELECT policy.id, policy.ctx, policy.priority, policy.descr, "
                           "policy_time_reference.minute_start, policy_time_reference.minute_end, policy_time_reference.hour_start, "
                           "policy_time_reference.hour_end, policy_time_reference.week_day_start, policy_time_reference.week_day_end, "
                           "policy_time_reference.month_day_start, policy_time_reference.month_day_end, policy_time_reference.month_start, "
                           "policy_time_reference.month_end, policy_time_reference.timezone "
                           "FROM policy, policy_target_reference INNER JOIN policy_time_reference "
                           "WHERE policy.id = policy_time_reference.policy_id "
                           "AND policy.id = policy_target_reference.policy_id "
                           "AND policy_target_reference.target_id = %s "
                           "AND policy.active != 0 "
                           "AND policy.ctx = %s "
                           "ORDER BY policy.order DESC", // order by DESC due to prepend policies in memory list
                           sim_uuid_get_db_string (sim_server_get_id (ossim.server)),
                           sim_uuid_get_db_string (context_id));

  dm = sim_database_execute_single_command (database, query);
  if (dm)
  {
    gint row;
    for (row = 0; row < gda_data_model_get_n_rows (dm); row++)
    {
      SimPolicy *policy = sim_policy_new_from_dm (dm, row);

      if (policy)
      {
        sim_db_load_policy_data (database, policy);

        // Adds the policy which we have filled to the policies list.
        policies = g_list_prepend (policies, policy);
        sim_policy_debug_print (policy);
      }
      else
        g_message ("Couldn't load policy for context");
    }
    g_object_unref (dm);
  }
  else
  {
    g_message ("POLICY DATA MODEL ERROR");
  }

  g_free (query);

  return policies;
}

/**
 * sim_db_load_policy_data:
 * @database: #SimDatabase object
 * @policy: a #SimPolicy
 *
 */
static void
sim_db_load_policy_data (SimDatabase *database,
                         SimPolicy   *policy)
{
  gint num_actions, num_alarm_actions;
  gchar *query;
  SimUuid *context_id = sim_policy_get_context_id (policy);
  SimUuid * policy_id = sim_policy_get_id (policy);

  //First the source addresses (hosts, nets, and host_groups. All of them are transformed into IP's

  /* Host SRC Inet Addresses */
  query = g_strdup_printf ("SELECT host_id FROM policy_host_reference "
                           " WHERE policy_id = %s AND host_id <> %s AND direction = 'source'",
                           sim_uuid_get_db_string (policy_id), DB_BINARY_ANY);

  if (!sim_db_load_policy_host_entity (database, query, policy, SOURCE))
    g_message ("POLICY HOST SOURCE REFERENCES DATA MODEL ERROR");
  g_free (query);

  /* Host group SRC */
  query = g_strdup_printf ("SELECT host.id FROM "
                           "((policy INNER JOIN policy_host_group_reference ON policy.id = policy_host_group_reference.policy_id) "
                           "INNER JOIN host_group_reference ON policy_host_group_reference.host_group_id = host_group_reference.host_group_id) "
                           "INNER JOIN host ON host_group_reference.host_id = host.id "
                           "WHERE policy.id = %s "
                           "AND policy_host_group_reference.direction = 'source' "
                           "AND host.ctx = policy.ctx",
                           sim_uuid_get_db_string (policy_id));

  if (!sim_db_load_policy_host_entity (database, query, policy, SOURCE))
    g_message ("POLICY HOST GRP SOURCE REFERENCES DATA MODEL ERROR");
  g_free (query);

  /* Net SRC Inet Address */
  query = g_strdup_printf ("SELECT ips FROM net INNER JOIN "
                           "(policy_net_reference INNER JOIN policy "
                           "ON policy_net_reference.policy_id = policy.id) "
                           "ON policy.ctx = net.ctx "
                           "AND policy_net_reference.net_id = net.id "
                           "WHERE policy.id = %s AND policy.ctx = %s AND direction = 'source'",
                           sim_uuid_get_db_string (policy_id), sim_uuid_get_db_string (context_id));

  if (!sim_db_load_policy_net_entity (database, query, policy, SOURCE))
    g_message ("POLICY NET SOURCE REFERENCES DATA MODEL ERROR");
  g_free (query);

  /* Net group SRC Inet Address */
  query = g_strdup_printf ("SELECT ips FROM "
                           "net INNER JOIN "
                           "(net_group_reference INNER JOIN "
                           "(net_group INNER JOIN "
                           "(policy_net_group_reference INNER JOIN policy "
                           "ON policy_net_group_reference.policy_id = policy.id) "
                           "ON net_group.id = policy_net_group_reference.net_group_id) "
                           "ON net_group_reference.net_group_id = net_group.id AND net_group.ctx = policy.ctx) "
                           "ON net.id = net_group_reference.net_id AND net.ctx = net_group.ctx "
                           "WHERE policy.id = %s AND policy.ctx = %s AND direction = 'source'",
                           sim_uuid_get_db_string (policy_id), sim_uuid_get_db_string (context_id));

  if (!sim_db_load_policy_net_entity (database, query, policy, SOURCE))
    g_message ("POLICY NET GRP SOURCE REFERENCES DATA MODEL ERROR");
  g_free (query);

  // Second, we load the destination addresses...

  /* Host DST Inet Address */
  query = g_strdup_printf ("SELECT host_id FROM policy_host_reference "
                           " WHERE policy_id = %s AND host_id <> %s AND direction = 'dest'",
                           sim_uuid_get_db_string (policy_id), DB_BINARY_ANY);

  if (!sim_db_load_policy_host_entity (database, query, policy, DEST))
    g_message ("POLICY HOST DESTINATION REFERENCES DATA MODEL ERROR");
  g_free (query);

  /* Host group DST */
  query = g_strdup_printf ("SELECT host.id FROM "
                           "((policy INNER JOIN policy_host_group_reference ON policy.id = policy_host_group_reference.policy_id) "
                           "INNER JOIN host_group_reference ON policy_host_group_reference.host_group_id = host_group_reference.host_group_id) "
                           "INNER JOIN host ON host_group_reference.host_id = host.id "
                           "WHERE policy.id = %s "
                           "AND policy_host_group_reference.direction = 'dest' "
                           "AND host.ctx = policy.ctx",
                           sim_uuid_get_db_string (policy_id));

  if (!sim_db_load_policy_host_entity (database, query, policy, DEST))
    g_message ("POLICY HOST GRP DESTINATION REFERENCES DATA MODEL ERROR");
  g_free (query);

  /* Net DST Inet Address */
  query = g_strdup_printf ("SELECT ips FROM net INNER JOIN "
                           "(policy_net_reference INNER JOIN policy "
                           "ON policy_net_reference.policy_id = policy.id) "
                           "ON policy.ctx = net.ctx "
                           "AND policy_net_reference.net_id = net.id "
                           "WHERE policy.id = %s AND policy.ctx = %s AND direction = 'dest'",
                           sim_uuid_get_db_string (policy_id), sim_uuid_get_db_string (context_id));

  if (!sim_db_load_policy_net_entity (database, query, policy, DEST))
    g_message ("POLICY NET DESTINATION REFERENCES DATA MODEL ERROR");
  g_free (query);

  /* Net group DST Inet Address */
  query = g_strdup_printf ("SELECT ips FROM "
                           "net INNER JOIN "
                           "(net_group_reference INNER JOIN "
                           "(net_group INNER JOIN "
                           "(policy_net_group_reference INNER JOIN policy "
                           "ON policy_net_group_reference.policy_id = policy.id) "
                           "ON net_group.id = policy_net_group_reference.net_group_id) "
                           "ON net_group_reference.net_group_id = net_group.id AND net_group.ctx = policy.ctx) "
                           "ON net.id = net_group_reference.net_id AND net.ctx = net_group.ctx "
                           "WHERE policy.id = %s AND policy.ctx = %s AND direction = 'dest'",
                           sim_uuid_get_db_string (policy_id), sim_uuid_get_db_string (context_id));

  if (!sim_db_load_policy_net_entity (database, query, policy, DEST))
    g_message ("POLICY NET GRP DESTINATION REFERENCES DATA MODEL ERROR");
  g_free (query);

  /* Ports */
  sim_db_load_policy_ports (database, policy);

  /* Sensors */
  sim_db_load_policy_sensors (database, policy);

  /*Plugin_id/sid groups*/
  sim_db_load_policy_plugin_groups (database, policy);

  /* Load the role of this policy.*/
  sim_db_load_policy_role (database, policy);

  /* Load event risks conditions */
  sim_db_load_policy_risks (database, policy);

  num_actions = sim_db_get_policy_num_actions (database, policy);
  sim_policy_set_has_actions (policy, num_actions);

  num_alarm_actions = sim_db_get_policy_num_alarm_actions (database, policy);
  sim_policy_set_has_alarm_actions (policy, num_alarm_actions);

  /* Reputation info */
  sim_db_load_policy_reputation_info(database, policy);

  /* Taxonomy data. */
  sim_db_load_policy_taxonomy (database, policy);
}

/**
 * sim_db_load_policy_net_entity:
 * @database: #SimDatabase object
 * @query: gchar query to exec in db
 * @policy: a #SimPolicy
 * @point: the netpoint (source or destination)
 *
 */
static gboolean
sim_db_load_policy_net_entity (SimDatabase *database,
                               gchar       *query,
                               SimPolicy   *policy,
                               netpoint     point)
{
  GdaDataModel *dm;

  ossim_debug ("%s: Query: %s", __func__, query);

  dm = sim_database_execute_single_command (database, query);
  if (dm)
  {
    gint row;
    for (row = 0; row < gda_data_model_get_n_rows (dm); row++)
    {
      const GValue *value;
      gchar * ip;
      SimInet * inet;

      value = gda_data_model_get_value_at (dm, 0, row, NULL);
      ip =value ? gda_value_stringify ((GValue *)value) : g_strdup ("");

      if ((inet = sim_inet_new_from_string (ip)))
      {
        if (point == SOURCE)
          sim_policy_append_src (policy, inet);
        else
          sim_policy_append_dst (policy, inet);
        g_object_unref (inet);
      }
      else
      {
        SimUuid * policy_id = sim_policy_get_id (policy);
        g_message ("Invalid IP for policy %s", sim_uuid_get_string (policy_id));
        g_free (ip);
        return (FALSE);
      }

      g_free (ip);
    }

    g_object_unref (dm);
  }
  else
    return FALSE; //meeecs, error!.

  return TRUE;
}

/**
 * sim_db_load_policy_host_entity:
 * @database: #SimDatabase object
 * @query: gchar query to exec in db
 * @policy: a #SimPolicy
 * @point: the hostpoint (source or destination)
 *
 */
static gboolean
sim_db_load_policy_host_entity (SimDatabase *database,
                                gchar       *query,
                                SimPolicy   *policy,
                                netpoint     point)
{
  GdaDataModel *dm;

  ossim_debug ("%s: Query: %s", __func__, query);

  dm = sim_database_execute_single_command (database, query);
  if (dm)
  {
    gint row;
    for (row = 0; row < gda_data_model_get_n_rows (dm); row++)
    {
      const GValue *value;
      SimUuid * host_id;

      value = gda_data_model_get_value_at (dm, 0, row, NULL);
      host_id = sim_uuid_new_from_blob (gda_value_get_blob (value));

      if (host_id)
      {
        if (point == SOURCE)
          sim_policy_add_src_host (policy, host_id);
        else
          sim_policy_add_dst_host (policy, host_id);
      }
      else
      {
        SimUuid * policy_id = sim_policy_get_id (policy);
        g_message ("Invalid IP for policy %s", sim_uuid_get_string (policy_id));

        g_object_unref (host_id);

        return (FALSE);
      }
    }

    g_object_unref (dm);
  }
  else
    return FALSE;

  return TRUE;
}

/**
 * sim_db_load_policy_ports:
 * @database: #SimDatabase object
 * @policy: a #SimPolicy
 *
 * Loads source and destination ports for policies.
 */
static void
sim_db_load_policy_ports (SimDatabase *database,
                          SimPolicy   *policy)
{
  gchar        *query;
  GdaDataModel *dm;
  guint         type;
  SimUuid      *context_id = sim_policy_get_context_id (policy);
  SimUuid     * policy_id = sim_policy_get_id (policy);

  for (type = SOURCE; type <= DEST; type++)
  {
    query = g_strdup_printf ("SELECT port_group_reference.port_number, port_group_reference.protocol_name FROM "
                             "port_group_reference INNER JOIN "
                             "(port_group INNER JOIN "
                             "(policy_port_reference "
                             "INNER JOIN policy "
                             "ON policy_port_reference.policy_id = policy.id) "
                             "ON port_group.id = policy_port_reference.port_group_id) "
                             "ON port_group_reference.port_group_id = port_group.id AND port_group.ctx = policy.ctx "
                             "WHERE policy.id = %s AND policy.ctx = %s AND direction = '%s'",
                             sim_uuid_get_db_string (policy_id), sim_uuid_get_db_string (context_id), (type == SOURCE) ? "source" : "dest");

    dm = sim_database_execute_single_command (database, query);
    if (dm)
    {
      gint row;
      for (row = 0; row < gda_data_model_get_n_rows (dm); row++)
      {
        const GValue    *value;
        const gchar     *protocol_name;
        gint             port_num;
        SimPortProtocol *protocol_port;
        SimProtocolType  protocol_type;

        value = gda_data_model_get_value_at (dm, 0, row, NULL);
        port_num = g_value_get_int (value);

        value = gda_data_model_get_value_at (dm, 1, row, NULL);
        protocol_name = g_value_get_string (value);
        protocol_type = sim_protocol_get_type_from_str (protocol_name);
        protocol_port = sim_port_protocol_new (port_num, protocol_type);

        if (type == SOURCE)
          sim_policy_append_port_src (policy, protocol_port);
        else
          sim_policy_append_port_dst (policy, protocol_port);
      }

      g_object_unref (dm);
    }
    else
    {
      g_message ("POLICY %s PORT REFERENCES DATA MODEL ERROR", type == SOURCE ? "SOURCE" : "DESTINATION");
    }

    g_free (query);
  }

  return;
}

/**
 * sim_db_load_policy_sensors:
 * @database: #SimDatabase object
 * @policy: a #SimPolicy
 *
 */
static void
sim_db_load_policy_sensors (SimDatabase *database,
                            SimPolicy   *policy)
{
  gchar        *query;
  GdaDataModel *dm;
  SimUuid      *context_id  = sim_policy_get_context_id (policy);
  SimUuid     * policy_id = sim_policy_get_id (policy);

  query = g_strdup_printf ("SELECT INET6_NTOA(sensor.ip), sensor.name "
                           "FROM sensor INNER JOIN "
                           "(policy_sensor_reference INNER JOIN policy "
                           "ON policy_sensor_reference.policy_id = policy.id) "
                           "ON sensor.id = policy_sensor_reference.sensor_id "
                           "WHERE policy.id = %s AND policy.ctx = %s",
                           sim_uuid_get_db_string (policy_id), sim_uuid_get_db_string (context_id));
  dm = sim_database_execute_single_command (database, query);
  g_free (query);

  if (dm)
  {
    gint row;
    for (row = 0; row < gda_data_model_get_n_rows (dm); row++)
    {
      const GValue *value;
      gchar * name, * ip;

      value = gda_data_model_get_value_at (dm, 0, row, NULL);
      ip = gda_value_stringify (value);
      value = gda_data_model_get_value_at (dm, 1, row, NULL);
      name = gda_value_stringify (value);

      if (!g_ascii_strncasecmp (name, SIM_WILDCARD_ANY, strlen (name)))
      {
        g_free (ip);
        ip = g_strdup_printf ("0"); //okay, this should be something like "0.0.0.0", but I prefer speed in matches
      }
      sim_policy_append_sensor (policy, ip); //append the string with the sensor's ip  (i.e. "1.1.1.1" or "0" if ANY)
    }
    g_object_unref (dm);
  }
  else
    g_message ("POLICY SENSOR REFERENCE DATA MODEL ERROR");

  return;
}


/**
 * sim_db_load_policy_plugin_groups:
 * @database: #SimDatabase object
 * @policy: a #SimPolicy
 *
 */
static void
sim_db_load_policy_plugin_groups (SimDatabase *database,
                                  SimPolicy   *policy)
{
  gchar        *query;
  GdaDataModel *dm;
  SimUuid     * policy_id = sim_policy_get_id (policy);

  query = g_strdup_printf ("SELECT plugin_group_id FROM policy_plugin_group_reference WHERE policy_id = %s",
                           sim_uuid_get_db_string (policy_id));
  dm = sim_database_execute_single_command (database, query);
  if (dm)
  {
    gint row;
    for (row = 0; row < gda_data_model_get_n_rows (dm); row++)
    {
      const GValue *value;
      SimUuid * plugin_group_id;

      value = gda_data_model_get_value_at (dm, 0, row, NULL);

      // Ignore NULL values.
      if (!gda_value_is_null (value))
      {
        plugin_group_id = sim_uuid_new_from_blob (gda_value_get_blob (value));
        sim_db_load_policy_plugins_from_group (database, policy, plugin_group_id);
      }
    }

    g_object_unref (dm);
  }
  else
  {
    g_message ("POLICY PLUGIN_ID REFERENCES DATA MODEL ERROR");
  }

  g_free (query);
}

/**
 * sim_db_load_policy_plugins_from_group:
 * @database: #SimDatabase object
 * @policy: a #SimPolicy
 * @group_id: gint id of plugin group
 *
 */
static void
sim_db_load_policy_plugins_from_group (SimDatabase *database,
                                       SimPolicy   *policy,
                                       SimUuid    * group_id)
{
  gchar        *query;
  GdaDataModel *dm;

  query = g_strdup_printf ("SELECT plugin_id, plugin_sid FROM "
                           "plugin_group INNER JOIN plugin_group_descr ON "
                           "plugin_group.group_ctx = plugin_group_descr.group_ctx AND plugin_group.group_id = plugin_group_descr.group_id "
                           "WHERE plugin_group.group_id = %s",
                           sim_uuid_get_db_string (group_id));
  dm = sim_database_execute_single_command (database, query);
  if (dm)
  {
    gint row;
    for (row = 0; row < gda_data_model_get_n_rows (dm); row++)
    {
      Plugin_PluginSid *plugin_group = g_new0 (Plugin_PluginSid, 1);
      gchar *str_plugin_sids;
      const GValue *value;

      value = gda_data_model_get_value_at (dm, 0, row, NULL); //plugin_id
      plugin_group->plugin_id = g_value_get_int (value);
      value = gda_data_model_get_value_at (dm, 1, row, NULL); //plugin_sid
      str_plugin_sids = g_value_dup_string (value);

      ossim_debug ("%s str_plugin_sids: %s", __func__, str_plugin_sids);

      //at this moment we have all the plugin_sid's from a specific plugin_id. they can have the following format:
      // "101,102,103-107" We've to separate it into individual *gint so we can store it inside
      //the plugin_group struct.
      gchar **uniq_plugin_ids = g_strsplit (str_plugin_sids, ",", 0);
      guint i;
      gint ii;

      for (i=0; i < sim_g_strv_length (uniq_plugin_ids); i++)
      {
        gchar *multiple = NULL;
        multiple = uniq_plugin_ids[i] ? strchr (uniq_plugin_ids[i], '-') : NULL;
        if (multiple) //"103-107"
        {
          gint from,to;
          gchar **individual_plugin_ids;

          individual_plugin_ids = g_strsplit (uniq_plugin_ids[i], "-", 0);

          from = strtol (individual_plugin_ids[0], (char **) NULL, 10);
          to = strtol (individual_plugin_ids[1], (char **) NULL, 10);

          for (ii = 0; ii <= (to - from); ii++)  //transform every plugin_sid into a number to store it.
          {
            gint *uniq = g_new0 (gint, 1);
            *uniq = from + ii;
            plugin_group->plugin_sid = g_list_append (plugin_group->plugin_sid, uniq);
          }
          g_strfreev (individual_plugin_ids);
        }
        else //"101"
        {
          gint *uniq = g_new0 (gint, 1);
          *uniq = strtol (uniq_plugin_ids[i], NULL, 10);
          plugin_group->plugin_sid = g_list_append (plugin_group->plugin_sid, uniq);
        }
      }
      sim_policy_append_plugin_group (policy, plugin_group); //appends the plugin_group.
      g_strfreev (uniq_plugin_ids);
    }
    g_object_unref (dm);
  }
  else
  {
    g_message ("POLICY PLUGIN_GROUP DATA MODEL ERROR");
  }

  g_free (query);
}

/**
 * sim_db_load_policy_role:
 * @database: #SimDatabase object
 * @policy: a #SimPolicy
 *
 */
static void
sim_db_load_policy_role (SimDatabase *database,
                         SimPolicy   *policy)
{
  gchar        *query;
  GdaDataModel *dm;
  SimUuid      *context_id = sim_policy_get_context_id (policy);
  SimUuid     * policy_id = sim_policy_get_id (policy);

  query = g_strdup_printf ("SELECT correlate, cross_correlate, store, qualify, resend_alarm, resend_event, sign, sim, sem, reputation "
                           "FROM policy_role_reference INNER JOIN policy "
                           "ON policy_role_reference.policy_id = policy.id "
                           "WHERE policy.id =%s AND policy.ctx = %s",
                           sim_uuid_get_db_string (policy_id), sim_uuid_get_db_string (context_id));

  dm = sim_database_execute_single_command (database, query);
  if (dm)
  {
    if (gda_data_model_get_n_rows (dm) != 0) //to avoid (null)-Critical first time
    {
      const GValue *value;
      SimRole *role = sim_role_new ();

      // Correlate
      value = gda_data_model_get_value_at (dm, 0, 0, NULL);
      sim_role_set_correlate (role, g_value_get_int (value));

      // Cross correlate
      value = gda_data_model_get_value_at (dm, 1, 0, NULL);
      sim_role_set_cross_correlate (role, g_value_get_int (value));

      // Store events in this policy in DB or not?
      value = gda_data_model_get_value_at (dm, 2, 0, NULL);
      sim_role_set_store (role, g_value_get_int (value));

      // Qualify
      value = gda_data_model_get_value_at (dm, 3, 0, NULL);
      sim_role_set_qualify (role, g_value_get_int (value));

      // Forward alarm
      value = gda_data_model_get_value_at (dm, 4, 0, NULL);
      sim_role_set_forward_alarm (role, g_value_get_int (value));

      // Forward event
      value = gda_data_model_get_value_at (dm, 5, 0, NULL);
      sim_role_set_forward_event (role,g_value_get_int (value));

      // Sign
      value = gda_data_model_get_value_at (dm, 6, 0, NULL);
      sim_role_set_sign (role, g_value_get_int (value));

      // Sim
      value = gda_data_model_get_value_at (dm, 7, 0, NULL);
      sim_role_set_sim (role, g_value_get_int (value));

      // Sem
      value = gda_data_model_get_value_at (dm, 8, 0, NULL);
      sim_role_set_sem (role, g_value_get_int (value));

      // Reputation
      value = gda_data_model_get_value_at (dm, 9, 0, NULL);
      sim_role_set_reputation (role, g_value_get_int (value));

      // Remote servers.
      sim_role_set_rservers (role, sim_db_load_policy_role_rservers (database, policy));

      sim_policy_set_role (policy, role);

      sim_role_print (role);
    }
    else
    {
      //g_message ("Error: May be there is a problem in role table; role load failed!");
      sim_policy_set_role (policy, NULL);
    }
    g_object_unref (dm);
  }
  else
  {
    g_message ("POLICY STORE DATA MODEL ERROR");
  }

  g_free (query);
}

/**
 * sim_db_load_policy_risks:
 * @database: #SimDatabase object
 * @policy: a #SimPolicy
 *
 */
static void
sim_db_load_policy_risks (SimDatabase *database,
                          SimPolicy   *policy)
{
  gchar *query;
  GdaDataModel *dm;
  SimUuid *context_id = sim_policy_get_context_id (policy);
  SimUuid * policy_id = sim_policy_get_id (policy);

  query = g_strdup_printf ("SELECT policy_risk_reference.priority, policy_risk_reference.reliability FROM "
                           "policy_risk_reference INNER JOIN policy "
                           "ON policy_risk_reference.policy_id = policy.id "
                           "WHERE policy.id = %s AND policy.ctx = %s",
                           sim_uuid_get_db_string (policy_id), sim_uuid_get_db_string (context_id));

  dm = sim_database_execute_single_command (database, query);
  if (dm)
  {
    gint row;
    for (row = 0; row < gda_data_model_get_n_rows (dm); row++)
    {
      const GValue *value;
      gint priority = SIM_POLICY_RISK_ANY;
      gint reliability = SIM_POLICY_RISK_ANY;

      value = gda_data_model_get_value_at (dm, 0, row, NULL);
      if (!gda_value_is_null (value))
        priority = g_value_get_int (value);

      value = gda_data_model_get_value_at (dm, 1, row, NULL);
      if (!gda_value_is_null (value))
        reliability = g_value_get_int (value);

      sim_policy_append_risk (policy, priority, reliability);
    }

    g_object_unref (dm);
  }
  else
  {
    g_message ("POLICY RISK REFERENCES DATA MODEL ERROR");
  }

  g_free (query);
}

GList *
sim_db_load_ligth_events_from_alarm (SimDatabase *database, SimUuid *backlog_id)
{
  GList *event_list;
  gchar *query;

  g_return_val_if_fail (SIM_IS_DATABASE (database), NULL);
  g_return_val_if_fail (SIM_IS_UUID (backlog_id), NULL);

  query = g_strdup_printf ("SELECT src_ip, dst_ip, src_host, dst_host, src_port, dst_port, timestamp, tzone FROM backlog_event INNER JOIN event ON backlog_event.event_id = event.id WHERE backlog_id = %s", sim_uuid_get_db_string (backlog_id));
  event_list = sim_db_get_objects (database, query, (DMFunc) sim_event_light_new_from_dm);
  g_free (query);

  return event_list;
}

/**
 * sim_db_get_contexts_for_engine:
 * @database: #SimDatabase object
 * @engine_id: engine SimUuid *
 *
 * Returns: list with event context id SimUuid * asociated to @engine_id
 * These can be contexts ids or child engines ids
 */
GList *
sim_db_get_contexts_for_engine (SimDatabase *database,
                                SimUuid     *engine_id)
{
  GList         *context_id_list = NULL;
  gchar         *query;
  GdaDataModel  *dm;
  const GValue  *value;

  g_return_val_if_fail (SIM_IS_DATABASE (database), NULL);

  query = g_strdup_printf ("SELECT event_ctx FROM corr_engine_contexts "
                           "WHERE engine_ctx = %s",
                           sim_uuid_get_db_string (engine_id));

  dm = sim_database_execute_single_command (database, query);
  if (dm)
  {
    gint row;
    for (row = 0; row < gda_data_model_get_n_rows (dm); row++)
    {
      SimUuid *context_id;

      value = gda_data_model_get_value_at (dm, 0, row, NULL);
      context_id = sim_uuid_new_from_blob (gda_value_get_blob (value));

      context_id_list = g_list_prepend (context_id_list, context_id);
    }

    g_object_unref (dm);
  }

  g_free (query);

  return context_id_list;
}


/**
 * sim_db_get_last_cid:
 * @database: #SimDatabase object
 *
 * Returns: last cid
 */
guint
sim_db_get_last_cid (SimDatabase *database)
{
  const gchar * query = "SELECT MAX(cid) FROM acid_event";
  GdaDataModel *dm;
  const GValue *value;
  guint         last_cid = 0;

  g_return_val_if_fail (SIM_IS_DATABASE (database), 0);

  dm = sim_database_execute_single_command (database, query);
  if (dm)
  {
    if (gda_data_model_get_n_rows (dm))
    {
      value = gda_data_model_get_value_at (dm, 0, 0, NULL);
      if (!gda_value_is_null (value))
        last_cid = g_value_get_int (value);

      ossim_debug ("%s: Last cid %u", __func__, last_cid);
    }

    g_object_unref (dm);
  }


  return last_cid;
}

/**
 * sim_db_get_policy_num_actions:
 * @database: #SimDatabase object
 * @policy: a #SimPolicy
 *
 * Returns: number of @policy actions.
 */
static gint
sim_db_get_policy_num_actions (SimDatabase *database,
                               SimPolicy   *policy)
{
  gint num_actions = 0;
  SimUuid * policy_id = sim_policy_get_id (policy);

  GdaDataModel *dm;
  gchar        *query;

  query = g_strdup_printf ("SELECT policy_id, action_id FROM policy_actions "
                           "WHERE policy_id = %s",
                           sim_uuid_get_db_string (policy_id));
  dm = sim_database_execute_single_command (database, query);
  if (dm)
  {
    num_actions = gda_data_model_get_n_rows (dm);
    g_object_unref (dm);
  }

  g_free (query);

  return num_actions;
}

/**
 * sim_db_get_policy_num_alarm_actions:
 * @database: #SimDatabase object
 * @policy: a #SimPolicy
 *
 * Returns: number of @policy actions that are triggered by alarms.
 * This is a rough copy of the previous method. There are better ways to
 * do this, but this is intended to keep consistency across all the
 * code in this file.
 */
static gint
sim_db_get_policy_num_alarm_actions (SimDatabase *database,
                                     SimPolicy   *policy)
{
  gint num_alarm_actions = 0;
  SimUuid * policy_id = sim_policy_get_id (policy);

  GdaDataModel *dm;
  gchar        *query;

  query = g_strdup_printf ("SELECT policy_actions.policy_id, action.id FROM "
                           "policy_actions INNER JOIN action "
                           "ON policy_actions.action_id = action.id "
                           "WHERE policy_actions.policy_id = %s AND "
                           "(action.cond RLIKE 'RISK>=[1-9]' OR "
                           "action.cond RLIKE 'RISK>[0-9]')",
                           sim_uuid_get_db_string (policy_id));
  dm = sim_database_execute_single_command (database, query);
  if (dm)
  {
    num_alarm_actions = gda_data_model_get_n_rows (dm);
    g_object_unref (dm);
  }

  g_free (query);

  return num_alarm_actions;
}


/**
 * sim_db_load_policy_reputation_info:
 * @database: #SimDatabase object
 * @policy: a #SimPolicy
 *
 */
void
sim_db_load_policy_reputation_info(SimDatabase *database,
                                   SimPolicy   *policy)
{
  gchar        * query;
  GdaDataModel * dm;
  const GValue * value;
  gint           from_src;
  SimPolicyRep * pol_rep;
  SimUuid      * policy_id = sim_policy_get_id (policy);

  query = g_strdup_printf ("SELECT from_src, rep_prio, rep_rel, rep_act "
                           "FROM policy_reputation_reference "
                           "WHERE policy_id = %s",
                           sim_uuid_get_db_string (policy_id));

  dm = sim_database_execute_single_command (database, query);
  if (dm)
  {
    gint row;
    for (row = 0; row < gda_data_model_get_n_rows (dm); row++)
    {
      pol_rep = g_new0 (SimPolicyRep, 1);

      value = gda_data_model_get_value_at (dm, 0, row, NULL);
      from_src = g_value_get_int (value);

      value = gda_data_model_get_value_at (dm, 1, row, NULL);
      pol_rep->priority = g_value_get_int (value);

      value = gda_data_model_get_value_at (dm, 2, row, NULL);
      pol_rep->reliability = g_value_get_int (value);

      value = gda_data_model_get_value_at (dm, 3, row, NULL);
      pol_rep->activity = g_value_get_int (value);

      if (from_src)
      {
        sim_policy_add_reputation_src (policy, pol_rep);
      }
      else
      {
        sim_policy_add_reputation_dst (policy, pol_rep);
      }
    }

    g_object_unref (dm);
  }
  else
  {
    g_message ("POLICY REPUTATION REFERENCES DATA MODEL ERROR");
  }

  g_free (query);
}

/**
 * sim_db_load_policy_taxonomy:
 * @database: #SimDatabase object
 * @policy: a #SimPolicy
 *
 * Load taxonomy data for policy.
 * We're using 0 for ANY wildcard.
 */
static void
sim_db_load_policy_taxonomy (SimDatabase *database,
                             SimPolicy   *policy)
{
  gchar           * query;
  gint              row, product_type, category_id, subcategory_id;
  GdaDataModel    * dm;
  const GValue    * value;
  SimUuid         * policy_id = sim_policy_get_id (policy);
  GHashTable      * products, * categories, * subcategories;

  query = g_strdup_printf ("SELECT product_type_id, category_id, subcategory_id "
                           "FROM policy_taxonomy_reference WHERE policy_id = %s",
                           sim_uuid_get_db_string (policy_id));

  products = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);

  dm = sim_database_execute_single_command (database, query);
  if (dm)
  {
    for (row = 0; row < gda_data_model_get_n_rows (dm); row++)
    {
      value = gda_data_model_get_value_at (dm, 0, row, NULL);
      product_type = g_value_get_int (value);

      value = gda_data_model_get_value_at (dm, 1, row, NULL);
      category_id = g_value_get_int (value);

      value = gda_data_model_get_value_at (dm, 2, row, NULL);
      subcategory_id = g_value_get_int (value);

      // Look for products.
      if ((categories = g_hash_table_lookup (products, GINT_TO_POINTER (product_type))))
      {
        if ((subcategories = g_hash_table_lookup (categories, GINT_TO_POINTER (category_id))))
        {
          if (!(g_hash_table_lookup (subcategories, GINT_TO_POINTER (subcategory_id))))
          {
            g_hash_table_insert (subcategories, GINT_TO_POINTER (subcategory_id), GINT_TO_POINTER (GENERIC_VALUE));
          }
        }
        else
        {
          subcategories = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
          g_hash_table_insert (subcategories, GINT_TO_POINTER (subcategory_id), GINT_TO_POINTER (GENERIC_VALUE));
          g_hash_table_insert (categories, GINT_TO_POINTER (category_id), (gpointer)subcategories);
        }
      }
      else
      {
        subcategories = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
        g_hash_table_insert (subcategories, GINT_TO_POINTER (subcategory_id), GINT_TO_POINTER (GENERIC_VALUE));
        categories = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);
        g_hash_table_insert (categories, GINT_TO_POINTER (category_id), (gpointer)subcategories);
        g_hash_table_insert (products, GINT_TO_POINTER (product_type), (gpointer)categories);
      }
    }

    g_object_unref (dm);
    sim_policy_set_taxonomy (policy, products);
  }
  else
  {
    g_message ("POLICY TAXONOMY REFERENCES DATA MODEL ERROR");
  }

  g_free (query);
  return;
}

/**
 * sim_db_delete_directives:
 * @database: #SimDatabase object
 * @engine_id: #SimUuid object
 *
 * Deletes all 1505 plugins (directives) from the plugin_sid table,
 * in order to avoid that deleted directives still live in our DB.
 */
void
sim_db_delete_directives (SimDatabase * database,
                          SimUuid     * engine_id)
{
  gchar * saqqara_pattern = "%SAQQARA%";
  gchar * query = g_strdup_printf ("DELETE FROM plugin_sid WHERE plugin_id = 1505 AND name NOT LIKE '%s' AND plugin_ctx = %s AND sid != 29998",
                                   saqqara_pattern, sim_uuid_get_db_string (engine_id));

  sim_database_execute_no_query (database, query);
  g_free (query);

  return;
}

/**
 * sim_db_get_config_uuid:
 * @database: a #SimDatabase
 * @config_key: const gchar * conf field
 *
 * Returns a new allocated pointer to a #SimUuid * for
 * value in config table for @config_key
 */
SimUuid *
sim_db_get_config_uuid (SimDatabase *database,
                        const gchar *config_key)
{
  SimUuid      *config_value = NULL;
  gchar        *query;
  GdaDataModel *dm;

  g_return_val_if_fail (SIM_IS_DATABASE (database), FALSE);

  query = g_strdup_printf ("SELECT value FROM config WHERE conf = '%s'", config_key);
  dm = sim_database_execute_single_command (database, query);
  if (dm)
  {
    const GValue *value = gda_data_model_get_value_at (dm, 0, 0, NULL);
    if (value != NULL)
    {
      config_value = sim_uuid_new_from_string (g_value_get_string (value));
    }
    else
    {
      g_message ("ERROR LOAD CONFIG DATA '%s' not found in config", config_key);
    }

    g_object_unref (dm);
  }
  else
  {
    g_message ("LOAD CONFIG ERROR FOR %s", config_key);
  }
  g_free (query);

  return config_value;
}

/**
 * sim_db_get_config_bool:
 * @database: a #SimDatabase
 * @config_key: const gchar * conf field
 *
 * Returns gboolean for value in config table for @config_key
 */
gboolean
sim_db_get_config_bool (SimDatabase *database,
                        const gchar *config_key)
{
  gboolean      config_value = FALSE;
  gchar        *query;
  GdaDataModel *dm;

  g_return_val_if_fail (SIM_IS_DATABASE (database), FALSE);

  query = g_strdup_printf ("SELECT value FROM config WHERE conf = '%s'", config_key);
  dm = sim_database_execute_single_command (database, query);
  if (dm)
  {
    const GValue *value = gda_data_model_get_value_at (dm, 0, 0, NULL);
    if (value != NULL)
    {
      gchar *yesno = g_value_dup_string (value);
      if (yesno)
      {
        if (!strncmp (yesno, "ye", 2))
          config_value = TRUE;
        else
          config_value = FALSE;

        free (yesno);
      }
      else
      {
        g_message ("'%s' variable empty in configuration, reverting to 'no'", config_key);
        config_value = FALSE;
      }
    }
    else
    {
      g_message ("ERROR LOAD CONFIG DATA '%s' not found in config", config_key);
    }

    g_object_unref (dm);
  }
  else
  {
    g_message ("LOAD CONFIG ERROR FOR %s", config_key);
  }
  g_free (query);

  return config_value;
}

/**
 * sim_db_get_config_int:
 * @database: a #SimDatabase
 * @config_key: const gchar * conf field
 *
 * Returns gint for value in config table for @config_key
 */
gint
sim_db_get_config_int (SimDatabase *database,
                       const gchar *config_key)
{
  gint          config_value = FALSE;
  gchar        *query;
  GdaDataModel *dm;

  g_return_val_if_fail (SIM_IS_DATABASE (database), FALSE);

  query = g_strdup_printf ("SELECT value FROM config WHERE conf = '%s'", config_key);
  dm = sim_database_execute_single_command (database, query);
  if (dm)
  {
    const GValue *value = gda_data_model_get_value_at (dm, 0, 0, NULL);
    if (value != NULL)
    {
      gchar *number = g_value_dup_string (value);
      config_value = atoi (number);
      free (number);
    }
    else
    {
      g_message ("ERROR LOAD CONFIG DATA '%s' not found in config", config_key);
    }

    g_object_unref (dm);
  }
  else
  {
    g_message ("LOAD CONFIG ERROR FOR %s", config_key);
  }
  g_free (query);

  return config_value;
}

/**
 * sim_db_get_config_logger_if_priority:
 * @database: a #SimDatabase
 *
 * Returns gint for value of logger_if_priority in config table
 */
gint
sim_db_get_config_logger_if_priority (SimDatabase *database)
{
  gboolean      return_value = 0;
  const gchar  * query = "SELECT value FROM config WHERE conf = 'server_logger_if_priority'";
  GdaDataModel *dm;

  g_return_val_if_fail (SIM_IS_DATABASE (database), FALSE);

  dm = sim_database_execute_single_command (ossim.dbossim, query);
  if (dm)
  {
    gchar *string_value;
    const GValue *value = gda_data_model_get_value_at (dm, 0, 0, NULL);
    ossim_debug ("%s: GValue type %s", __func__, G_VALUE_TYPE_NAME (value));
    string_value = value ? gda_value_stringify ((GValue *)value) : g_strdup ("0");//g_value_dup_string (value);

    /* Take only the first digit if there is one. */
    if(string_value != NULL) 
    {
      if (g_ascii_isdigit (string_value[0]) && (strlen(string_value) > 1))
        string_value[1] = '\0';
      return_value =  (gint)strtol ((char *)string_value, NULL, 10);

      g_free (string_value);
    }
    else
    {
      ossim_debug ("%s: bad logger_if_priority value, reverting to default.", __func__);
      return_value = 0;
    }

    g_object_unref (dm);
  }
  else
    g_message ("LOAD ROLE DATA MODEL ERROR");

  return return_value;
}

/**
 * sim_db_load_plugin_references:
 * @database: a #SimDatabase object.
 * @context_id: a #SimUuid with the current context id.
 *
 * Returns a #GList with all the plugin references for
 * cross correlation.
 */
GList *
sim_db_load_plugin_references (SimDatabase * database,
                              SimUuid     * context_id)
{
  GList * plugin_references = NULL;
  SimPluginReference * plugin_reference;

  GdaDataModel  *dm;
  const GValue  *value;
  gchar         *query;
  gint           row;

  g_return_val_if_fail (SIM_IS_DATABASE (database), 0);

  query = g_strdup_printf ("SELECT plugin_id, plugin_sid, reference_id, reference_sid FROM plugin_reference "
                           "WHERE plugin_reference.ctx = UNHEX('00000000000000000000000000000000') OR "
                           "plugin_reference.ctx = %s",  sim_uuid_get_db_string (context_id));

  dm = sim_database_execute_single_command (database, query);
  g_free (query);

  if (dm)
  {
    for (row = 0; row < gda_data_model_get_n_rows (dm); row++)
    {
      plugin_reference = g_new0 (SimPluginReference, 1);

      value = gda_data_model_get_value_at (dm, 0, row, NULL);
      plugin_reference->plugin_id = g_value_get_int (value);
      value = gda_data_model_get_value_at (dm, 1, row, NULL);
      plugin_reference->plugin_sid = g_value_get_int (value);

      value = gda_data_model_get_value_at (dm, 2, row, NULL);
      plugin_reference->reference_id = g_value_get_int (value);
      value = gda_data_model_get_value_at (dm, 3, row, NULL);
      plugin_reference->reference_sid = g_value_get_int (value);

      plugin_references = g_list_prepend (plugin_references, plugin_reference);
    }

    g_object_unref (dm);
  }
  else
  {
    g_message ("PLUGIN_REFERENCE DATA MODEL ERROR");
  }

  return (plugin_references);
}

/**
 * sim_db_get_reference_sid:
 * @database: a #SimDatabase
 * @reference_id: reference id
 * @plugin_id: plugin id
 * @plugin_sid: plugin sid
 *
 * Returns List with all 'reference_sid' from 'plugin_reference' table
 */
GList *
sim_db_get_reference_sid (SimDatabase *database,
                          gint         reference_id,
                          gint         plugin_id,
                          gint         plugin_sid)
{
  GList         *list = NULL;
  GdaDataModel  *dm;
  const GValue  *value;
  gchar         *query;
  gint           row;
  gint           reference_sid = 0;

  g_return_val_if_fail (SIM_IS_DATABASE (database), 0);

  query = g_strdup_printf ("SELECT reference_sid FROM plugin_reference "
                           "WHERE plugin_id = %d AND plugin_sid = %d AND reference_id = %d",
                           plugin_id, plugin_sid, reference_id);

  dm = sim_database_execute_single_command (database, query);
  if (dm)
  {
    for (row = 0; row < gda_data_model_get_n_rows (dm); row++)
    {
      value = gda_data_model_get_value_at (dm, 0, row, NULL);
      reference_sid = g_value_get_int (value);

      if (reference_sid)
        list = g_list_prepend (list, GINT_TO_POINTER (reference_sid));
    }

    g_object_unref (dm);
  }
  else
  {
    g_message ("PLUGIN_REFERENCE DATA MODEL ERROR");
  }

  g_free (query);

  return list;
}

/**
 * sim_db_get_host_plugin_sid_hosts:
 *
 * Returns a list of hosts in the host_plugin_sids table.
 */
GList *
sim_db_get_host_plugin_sid_hosts (SimDatabase * database,
                                  SimUuid     * context_id)
{
  GdaDataModel * dm;
  const GValue * value;
  gint           row;
  GList        * hosts = NULL;
  gchar        * host;
  gchar        * query;

  query = g_strdup_printf ("SELECT DISTINCT(inet6_ntoa(host_ip)) "
                           "FROM host_plugin_sid "
                           "WHERE ctx = %s",
                           sim_uuid_get_db_string (context_id));

  dm = sim_database_execute_single_command (database, query);
  if (dm)
  {
    for (row = 0; row < gda_data_model_get_n_rows (dm); row++)
    {
      value = gda_data_model_get_value_at (dm, 0, row, NULL);
      host = gda_value_stringify (value);

      hosts = g_list_prepend (hosts, (gpointer)host);
    }

    g_object_unref (dm);
  }
  else
  {
    g_message ("HOST PLUGIN SID DATA MODEL ERROR");
  }
  g_free (query);

  return (hosts);
}

GList *
sim_db_get_removable_alarms (SimDatabase *database)
{
  GList        *backlog_id_list = NULL;
  GdaDataModel *dm;
  const GValue *value;
  gint          row;

  g_return_val_if_fail (SIM_IS_DATABASE (database), NULL);

  dm = sim_database_execute_single_command (database, "SELECT backlog_id FROM alarm WHERE removable = 0");
  if (dm)
  {
    for (row = 0; row < gda_data_model_get_n_rows (dm); row++)
    {
      value = gda_data_model_get_value_at (dm, 0, row, NULL);
      backlog_id_list = g_list_prepend (backlog_id_list, sim_uuid_new_from_blob (gda_value_get_blob (value)));
    }

    g_object_unref (dm);
  }
  else
  {
    g_message ("ALARM DATA MODEL ERROR");
  }

  return backlog_id_list;
}

/**
 * sim_db_delete_host_plugin_sid_host:
 *
 * Deletes every @host_ip row in host_plugin_sid.
 */
void
sim_db_delete_host_plugin_sid_host (SimDatabase * database,
                                    SimUuid     * context_id,
                                    gchar       * host_ip)
{
  gchar * query = g_strdup_printf ("DELETE FROM host_plugin_sid WHERE host_ip = inet6_aton ('%s') AND ctx = %s",
                                   host_ip, sim_uuid_get_db_string (context_id));

  if (sim_database_execute_no_query  (database, query) < 0)
    g_message ("Cannot delete host %s from host_plugin_sid table", host_ip);

  g_free (query);
  return;
}

/**
 * sim_db_load_reputation_activities:
 *
 * Load reputation_activities table in a hash table.
 */
void
sim_db_load_reputation_activities (SimDatabase *database, SimReputation *reputation)
{
  const gchar  * query = "SELECT id, descr FROM reputation_activities ORDER BY id";
  GdaDataModel  *dm;

  dm = sim_database_execute_single_command (database, query);
  if (dm)
  {
    gint  row, id;
    gchar *descr;
    for (row = 0; row < gda_data_model_get_n_rows (dm); row++)
    {
      const GValue *value;
      GValue  smallint = { 0, {{0}, {0}} };

      value = gda_data_model_get_value_at (dm, 0, row, NULL);
      // FIXME: temporal fix until GDA reads smallints properly
      g_value_init (&smallint, GDA_TYPE_SHORT);
      g_value_transform (value, &smallint);
      id = gda_value_get_short (&smallint);
      value = gda_data_model_get_value_at (dm, 1, row, NULL);
      descr = g_value_dup_string (value);

      g_hash_table_insert(sim_reputation_get_db_activities(reputation), descr, GINT_TO_POINTER(id));
    }

    g_object_unref (dm);
  }
}




/**
 * sim_db_clean_siem_tables:
 *
 * Deletes rows from extra_data, reputation_data and idm_data
 * without related info in acid_event
 */
void sim_db_clean_siem_tables (SimDatabase *db)
{
  g_message("Checking SIEM tables for inconsistences.");
  g_message("This may take a long time depending on the number of rows in your SIEM tables. Please, be patient.");
  sim_db_clean_siem_table(db, "extra_data");
  sim_db_clean_siem_table(db, "idm_data");
  sim_db_clean_siem_table(db, "reputation_data");
}

/**
 * sim_db_kill_previous_sql:
 *
 * Kills a SQL instruction running in db
 */
void sim_db_kill_previous_sql(SimDatabase *database, gchar *sql)
{
	GdaDataModel  *dm;
	GValue        *value;
	gint64				process_id;
	gchar 				*process_query, *kill_sql;
	gint					row;
	
	process_query = g_strdup_printf("SELECT id FROM information_schema.processlist WHERE info = '%s'", sql);
	dm = sim_database_execute_single_command (database, process_query);
  if (dm)
  {
    for (row = 0; row < gda_data_model_get_n_rows (dm); row++)
		{
		  value = (GValue *) gda_data_model_get_value_at (dm, 0, row, NULL);
			process_id = g_value_get_int64(value);

			kill_sql = g_strdup_printf("KILL %ld", process_id);
			sim_database_execute_no_query(database, kill_sql);
			g_free(kill_sql);
			ossim_debug ("Killed MySQL process %ld. SQL: %s", process_id, sql);
		}
    g_object_unref(dm);
  }
	g_free(process_query);
}

/**
 * sim_db_clean_siem_table:
 *
 * Deletes rows from a table without related info in acid_event
 */
void sim_db_clean_siem_table(SimDatabase *db, gchar *table)
{
  gchar         *tmp_sql;

  ossim_debug("%s: Cleaning table %s", __func__, table);

  tmp_sql = g_strdup_printf("CREATE TEMPORARY TABLE cleantmptable SELECT event_id FROM %s LEFT OUTER JOIN acid_event a ON %s.event_id = a.id WHERE a.id IS NULL",
                            table, table);
  sim_database_execute_no_query (db, tmp_sql);
  g_free(tmp_sql);
  sim_database_execute_no_query (db, "ALTER TABLE cleantmptable ADD PRIMARY KEY (event_id)");

  tmp_sql = g_strdup_printf("DELETE FROM %s WHERE event_id IN (SELECT event_id FROM cleantmptable)", table);
  sim_db_kill_previous_sql(db, tmp_sql);
  ossim_debug("%s: Deleting rows %s",__func__, tmp_sql);
  sim_database_execute_no_query (db, tmp_sql);
  g_free(tmp_sql);

  sim_database_execute_no_query (db, "DROP TABLE cleantmptable");
}

/**
 * sim_db_update_taxonomy_info:
 * @database: #SimDatabase object
 *
 * Updates taxonomy info for directive event plugin sids
 */
void sim_db_update_taxonomy_info (SimDatabase *database)
{

  gchar *buf = NULL;
  gsize len = 0;
  gsize pos = 0;
  GError *err = NULL;
  GIOStatus status = G_IO_STATUS_NORMAL;
  GIOChannel *taxo_file;

  taxo_file = g_io_channel_new_file(UPDATE_TAXONOMY_INFO_FILE, "r", NULL);
  if (taxo_file == NULL)
  {
    ossim_debug ("Taxonomy info file not found %s\n", UPDATE_TAXONOMY_INFO_FILE);
    return;
  }

  while (status != G_IO_STATUS_ERROR && status != G_IO_STATUS_EOF) {
    status = g_io_channel_read_line(taxo_file, &buf, &len, &pos, &err);
    if (buf != NULL) {
      sim_database_execute_no_query(database, buf); 
      g_free(buf);
    }
  }
  g_io_channel_unref(taxo_file);
}

void
sim_db_update_context_stats (SimDatabase *database, SimUuid *context_id, gfloat stat)
{
  gchar *query;

  g_return_if_fail (SIM_IS_DATABASE (database));

  query = g_strdup_printf ("REPLACE INTO acl_entities_stats (entity_id, ts, stat) VALUES (%s, NOW(), %f)",
                           sim_uuid_get_db_string (context_id),
                           stat);

  sim_database_execute_no_query (database, query);

  g_free (query);
}
