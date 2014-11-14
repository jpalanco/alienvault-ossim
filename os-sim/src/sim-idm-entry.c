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

#include "sim-idm-entry.h"

#include <string.h>
#include <glib.h>
#include <json-glib/json-glib.h>
#include <libgda/libgda.h>

#include "os-sim.h"
#include "sim-log.h"
#include "sim-object.h"
#include "sim-inet.h"
#include "sim-command.h"
#include "sim-database.h"
#include "sim-sensor.h"
#include "sim-uuid.h"
#include "sim-idm-anomalies.h"
#include "sim-util.h"

SimMain ossim;

typedef struct _SimIdmServiceAux
{
  gboolean   is_tcp;
  JsonArray *array;
  GString    gstr;
} SimIdmServiceAux;

typedef struct _SimIdmServiceEntry
{
  gint   inventory_source;
  gint   relevance;
  gchar *service;
  gchar *version;
  gboolean gc; // garbage collector for deleting entries not updated in merges
} SimIdmServiceEntry;

// Internally we store the data as we receive it from the agent
// i.e. using ',' and '|' as separators
struct _SimIdmEntryPrivate
{
  SimInet    *ip;
  gboolean    is_login;
  GHashTable *username;
  gchar      *hostname;
  GHashTable *fqdns;
  gchar      *mac;
  gchar      *os;
  gchar      *cpu;
  gint        memory;
  gchar      *video;
  GHashTable *service_tcp; // key: port, value: SimIdmServiceEntry
  GHashTable *service_udp;
  GHashTable *software;
  gchar      *state;
  gint8       inventory_source;
  // WARNING: don't be tempted to use just one relevance field
  //          each field has its own relevance when we merge SimIdmEntry
  gint8       relevance_os;
  gint8       relevance_cpu;
  gint8       relevance_memory;
  gint8       relevance_video;

  SimUuid    *host_id;

  // These fields are used for not recalculating the marshalled strings
  // multiple times and for the getters
  gchar      *username_raw;
  gchar      *fqdns_raw;
  gchar      *service_raw;
  gchar      *software_raw;
  gchar      *entry_raw;
};

#define SIM_IDM_ENTRY_GET_PRIVATE(object) \
    (G_TYPE_INSTANCE_GET_PRIVATE ((object), SIM_TYPE_IDM_ENTRY, SimIdmEntryPrivate))

SIM_DEFINE_TYPE (SimIdmEntry, sim_idm_entry, G_TYPE_OBJECT, NULL)

static gboolean sim_idm_entry_username_merge (SimIdmEntry *entry, const gchar *username, gboolean is_login);
static gboolean sim_idm_entry_fqdns_merge (SimIdmEntry *entry, const gchar *fqdns);
static gboolean sim_idm_entry_service_merge (SimIdmEntry *entry, const gchar *service, gint inventory_source, gint relevance, SimUuid *context_id, SimSensor *sensor);
static gboolean sim_idm_entry_software_merge (SimIdmEntry *entry, const gchar *software, gboolean is_install);
#if 0
static void sim_idm_entry_service_gc_mark (gpointer key, gpointer value, gpointer user_data);
static gboolean sim_idm_entry_service_gc_clean (gpointer key, gpointer value, gpointer user_data);
#endif
static gchar *sim_idm_entry_username_get_string (SimIdmEntry *entry);
static gchar *sim_idm_entry_fqdns_get_string (SimIdmEntry *entry);
static gchar *sim_idm_entry_hash_get_string (GHashTable *hash_table);
static gchar *sim_idm_entry_service_get_string (SimIdmEntry *entry);
static gchar *sim_idm_entry_software_get_string (SimIdmEntry *entry);
static gchar *sim_idm_entry_get_command_string (SimIdmEntry *entry);
static gchar *sim_idm_entry_to_json (GHashTable *hash_table, GHFunc json_serialize_func);
static void sim_idm_entry_username_to_json (gpointer key, gpointer value, gpointer user_data);
static void sim_idm_entry_software_to_json (gpointer key, gpointer value, gpointer user_data);
static gchar *sim_idm_entry_property_from_json (const gchar *property_json);
static gchar *sim_idm_entry_username_from_json (const gchar *username_json);
static void sim_idm_entry_load_host_source_reference (SimDatabase *database);
static gint sim_idm_entry_get_relevance (gint source_id);
static void sim_idm_service_entry_free (SimIdmServiceEntry *entry);

/* INVENTORY 'host_source_reference' table */
static GHashTable *host_source_reference; // key: source_id, value: relevance

// This defines must be related with table host_property_reference initialization
#define SIM_IDM_SOURCE_UNKNOWN 0
#define SIM_IDM_SOURCE_MANUAL_LOCKED 1
#define SIM_IDM_SOURCE_MANUAL 2

/* GType Functions */

static void
sim_idm_entry_finalize (GObject *gobject)
{
  SimIdmEntry *entry = SIM_IDM_ENTRY (gobject);

  if (entry->_priv->ip)
    g_object_unref (entry->_priv->ip);

  if (entry->_priv->username)
    g_hash_table_unref (entry->_priv->username);

  g_free (entry->_priv->hostname);
  if (entry->_priv->fqdns)
    g_hash_table_unref (entry->_priv->fqdns);
  g_free (entry->_priv->mac);
  g_free (entry->_priv->os);
  g_free (entry->_priv->cpu);
  g_free (entry->_priv->video);

  if (entry->_priv->service_tcp)
    g_hash_table_unref (entry->_priv->service_tcp);
  if (entry->_priv->service_udp)
    g_hash_table_unref (entry->_priv->service_udp);

  if (entry->_priv->software)
    g_hash_table_unref (entry->_priv->software);

  g_free (entry->_priv->state);

  if (entry->_priv->host_id)
    g_object_unref (entry->_priv->host_id);

  g_free (entry->_priv->username_raw);
  g_free (entry->_priv->fqdns_raw);
  g_free (entry->_priv->service_raw);
  g_free (entry->_priv->software_raw);
  g_free (entry->_priv->entry_raw);

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_idm_entry_class_init (SimIdmEntryClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_ref (G_TYPE_OBJECT);

  object_class->finalize = sim_idm_entry_finalize;

  g_type_class_add_private (class, sizeof (SimIdmEntryPrivate));

  // Load 'host_source_reference' table
  sim_idm_entry_load_host_source_reference (ossim.dbossim);
}

static void
sim_idm_entry_instance_init (SimIdmEntry *idm_entry)
{
  idm_entry->_priv = SIM_IDM_ENTRY_GET_PRIVATE (idm_entry);

  idm_entry->_priv->ip = NULL;
  idm_entry->_priv->is_login = FALSE;
  idm_entry->_priv->username = g_hash_table_new_full (g_str_hash, g_str_equal, g_free, NULL);
  idm_entry->_priv->hostname = NULL;
  idm_entry->_priv->fqdns = g_hash_table_new_full (g_str_hash, g_str_equal, g_free, NULL);
  idm_entry->_priv->mac = NULL;
  idm_entry->_priv->os = NULL;
  idm_entry->_priv->cpu = NULL;
  idm_entry->_priv->memory = 0;
  idm_entry->_priv->video = NULL;
  idm_entry->_priv->service_tcp = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, (GDestroyNotify) sim_idm_service_entry_free);
  idm_entry->_priv->service_udp = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, (GDestroyNotify) sim_idm_service_entry_free);
  idm_entry->_priv->software = g_hash_table_new_full (g_str_hash, g_str_equal, g_free, g_free);
  idm_entry->_priv->state = NULL;
  idm_entry->_priv->inventory_source = 0;
  idm_entry->_priv->relevance_os = 0;
  idm_entry->_priv->relevance_cpu = 0;
  idm_entry->_priv->relevance_memory = 0;
  idm_entry->_priv->relevance_video = 0;
  idm_entry->_priv->host_id = NULL;
  idm_entry->_priv->username_raw = NULL;
  idm_entry->_priv->fqdns_raw = NULL;
  idm_entry->_priv->service_raw = NULL;
  idm_entry->_priv->software_raw = NULL;
  idm_entry->_priv->entry_raw = NULL;
}

/* Public Methods */

SimIdmEntry *
sim_idm_entry_new_from_command (SimCommand *command)
{
  SimIdmEntry *entry;
  gint relevance;
  gchar *fqdn_aux;

  g_return_val_if_fail (command, NULL);

  entry = SIM_IDM_ENTRY (g_object_new (SIM_TYPE_IDM_ENTRY, NULL));
  
  relevance = sim_idm_entry_get_relevance (command->data.idm_event.inventory_source);

  if (command->data.idm_event.ip)
    entry->_priv->ip = g_object_ref (command->data.idm_event.ip);
  entry->_priv->is_login = command->data.idm_event.is_login;
  if (command->data.idm_event.username)
  {
    // Support for events coming from v3 sensors
    if (!strchr (command->data.idm_event.username, '|'))
    {
      gchar *temp_username;

      temp_username = g_strdup_printf ("%s|", command->data.idm_event.username);
      g_free (command->data.idm_event.username);
      command->data.idm_event.username = temp_username;
    }

    sim_idm_entry_username_merge (entry, command->data.idm_event.username, TRUE);
    entry->_priv->username_raw = sim_idm_entry_username_get_string (entry);
  }

  // Support for events coming from v3 sensors
  if (command->data.idm_event.domain)
  {
    if (g_hash_table_size (entry->_priv->username) == 0)
      g_message ("It is not possible to specify a domain without any user");
    else if (g_hash_table_size (entry->_priv->username) > 1)
      g_message ("It is not possible to specify a domain with more than one user");
    else
    {
      gchar *new_username;
      gchar **split;

      sim_idm_entry_username_merge (entry, command->data.idm_event.username, FALSE);
      g_free (entry->_priv->username_raw);

      split = g_strsplit (command->data.idm_event.username, "|", 2);
      new_username = g_strdup_printf ("%s|%s", *split, command->data.idm_event.domain);
      g_strfreev (split);

      sim_idm_entry_username_merge (entry, new_username, TRUE);
      entry->_priv->username_raw = sim_idm_entry_username_get_string (entry);

      g_free (new_username);
    }
  }

  // Get hostname from FQDN if a FQDN arrives
  if (command->data.idm_event.hostname)
  {
    fqdn_aux = strchr (command->data.idm_event.hostname, '.');
    if (fqdn_aux)
    {
      sim_idm_entry_fqdns_merge (entry, command->data.idm_event.hostname);
      entry->_priv->fqdns_raw = sim_idm_entry_fqdns_get_string (entry);
      *fqdn_aux = '\0';
    }

    entry->_priv->hostname = g_strdup (command->data.idm_event.hostname);

    if (fqdn_aux)
      *fqdn_aux = '.';
  }

  entry->_priv->mac = g_strdup (command->data.idm_event.mac);
  entry->_priv->os = g_strdup (command->data.idm_event.os);
  entry->_priv->cpu = g_strdup (command->data.idm_event.cpu);
  entry->_priv->memory = command->data.idm_event.memory;
  entry->_priv->video = g_strdup (command->data.idm_event.video);
  if (command->data.idm_event.service)
  {
    sim_idm_entry_service_merge (entry, command->data.idm_event.service, command->data.idm_event.inventory_source, relevance, NULL, NULL);
    entry->_priv->service_raw = sim_idm_entry_service_get_string (entry);
  }
  if (command->data.idm_event.software)
  {
    sim_idm_entry_software_merge (entry, command->data.idm_event.software, TRUE);
    entry->_priv->software_raw = sim_idm_entry_software_get_string (entry);
  }
  entry->_priv->state = g_strdup (command->data.idm_event.state);
  entry->_priv->inventory_source = command->data.idm_event.inventory_source;
  if (command->data.idm_event.host_id)
    entry->_priv->host_id = g_object_ref (command->data.idm_event.host_id);

  entry->_priv->relevance_os     = relevance;
  entry->_priv->relevance_cpu    = relevance;
  entry->_priv->relevance_memory = relevance;
  entry->_priv->relevance_video  = relevance;

  entry->_priv->entry_raw = sim_idm_entry_get_command_string (entry);

  return entry;
}

SimIdmEntry *
sim_idm_entry_new_from_dm (SimUuid *host_id, SimInet *ip, gchar *mac, gchar *hostname, gchar *fqdns)
{
  SimIdmEntry *entry;

  // We need to convert some values from JSON because in DB they are stored in that format
  entry = SIM_IDM_ENTRY (g_object_new (SIM_TYPE_IDM_ENTRY, NULL));

  if (ip)
    entry->_priv->ip = g_object_ref (ip);
  if (host_id)
    entry->_priv->host_id = g_object_ref (host_id);
  entry->_priv->mac = g_strdup (mac);
  entry->_priv->hostname = g_strdup (hostname);
  sim_idm_entry_fqdns_merge (entry, fqdns);
  entry->_priv->fqdns_raw = sim_idm_entry_fqdns_get_string (entry);

  return entry;
}

void
sim_idm_entry_add_properties_from_dm (GHashTable *entry_table, GdaDataModel *dm_host_properties)
{
  GPtrArray *interface_list;
  SimIdmEntry *entry;
  gint i, rows;
  guint j;
  const GValue *value;
  SimUuid *host_id;
  gint property_ref, source_id;
  const gchar *property_value;
  gchar *property_value_json;

  // host_properties
  rows = gda_data_model_get_n_rows (dm_host_properties);

  for (i = 0; i < rows; i++)
  {
    value = gda_data_model_get_value_at (dm_host_properties, 0, i, NULL);
    host_id = sim_uuid_new_from_blob (gda_value_get_blob (value));

    interface_list = g_hash_table_lookup (entry_table, host_id);
    if (!interface_list)
    {
      g_object_unref (host_id);
      continue;
    }
    g_object_unref (host_id);

    for (j = 0; j < interface_list->len; j++)
    {
      entry = interface_list->pdata[j];

      value = gda_data_model_get_value_at (dm_host_properties, 1, i, NULL);
      property_ref = g_value_get_int (value);

      value = gda_data_model_get_value_at (dm_host_properties, 2, i, NULL);
      source_id = g_value_get_int (value);

      value = gda_data_model_get_value_at (dm_host_properties, 3, i, NULL);
      property_value = gda_value_is_null (value) ? NULL : g_value_get_string (value);

      if (!property_value)
      {
        g_message ("Bad encoded host property '%d' with NULL value for host id %s", property_ref, sim_uuid_get_string (sim_idm_entry_get_host_id (entry)));
        continue;
      }

      if (property_ref == SIM_HOST_PROP_USERNAME)
        property_value_json = sim_idm_entry_username_from_json (property_value);
      else
        property_value_json = sim_idm_entry_property_from_json (property_value);
      if (!property_value_json)
      {
        g_message ("Bad encoded host property '%d' with value '%s' in host id %s", property_ref, property_value, sim_uuid_get_string (sim_idm_entry_get_host_id (entry)));
        continue;
      }

      switch (property_ref)
      {
        case SIM_HOST_PROP_CPU:
          entry->_priv->cpu = property_value_json;
          entry->_priv->relevance_cpu = sim_idm_entry_get_relevance (source_id);
          break;
        case SIM_HOST_PROP_MEMORY:
          entry->_priv->memory = g_ascii_strtoull (property_value_json, NULL, 10);
          entry->_priv->relevance_memory = sim_idm_entry_get_relevance (source_id);
          g_free (property_value_json);
          break;
        case SIM_HOST_PROP_USERNAME:
          sim_idm_entry_username_merge (entry, property_value_json, TRUE);
          entry->_priv->username_raw = sim_idm_entry_username_get_string (entry);
          g_free (property_value_json);
          break;
        case SIM_HOST_PROP_OS:
          entry->_priv->os = property_value_json;
          entry->_priv->relevance_os = sim_idm_entry_get_relevance (source_id);
          break;
        case SIM_HOST_PROP_STATE:
          entry->_priv->state = property_value_json;
          break;
        case SIM_HOST_PROP_VIDEO:
          entry->_priv->video = property_value_json;
          entry->_priv->relevance_video = sim_idm_entry_get_relevance (source_id);
          break;
        default:
          g_message ("%s: unknown property reference %d", __func__, property_ref);
          g_free (property_value_json);
          break;
      }
    }
  }
}

void
sim_idm_entry_add_services_from_dm (GHashTable *entry_table, GdaDataModel *dm_host_services)
{
  GPtrArray *interface_list;
  SimIdmEntry *entry;
  gint i, rows;
  guint j;
  const GValue *value;
  const GdaBinary *binary;
  SimInet *host_inet;
  SimUuid *host_id;
  SimIdmServiceEntry *service;
  gint port, protocol;

  // host_services
  rows = gda_data_model_get_n_rows (dm_host_services);

  for (i = 0; i < rows; i++)
  {
    value = gda_data_model_get_value_at (dm_host_services, 0, i, NULL);
    host_id = sim_uuid_new_from_blob (gda_value_get_blob (value));

    interface_list = g_hash_table_lookup (entry_table, host_id);
    if (!interface_list)
    {
      g_object_unref (host_id);
      continue;
    }
    g_object_unref (host_id);

    for (j = 0; j < interface_list->len; j++)
    {
      entry = interface_list->pdata[j];

      value = gda_data_model_get_value_at (dm_host_services, 1, i, NULL);
      binary = (GdaBinary *) gda_value_get_blob (value);
      host_inet = sim_inet_new_from_db_binary (binary->data, binary->binary_length);

      if (!sim_inet_equal (host_inet, entry->_priv->ip))
      {
        g_object_unref (host_inet);
        continue;
      }
      g_object_unref (host_inet);

      service = g_new (SimIdmServiceEntry, 1);

      value = gda_data_model_get_value_at (dm_host_services, 2, i, NULL);
      port = g_value_get_int (value);

      value = gda_data_model_get_value_at (dm_host_services, 3, i, NULL);
      protocol = g_value_get_int (value);

      value = gda_data_model_get_value_at (dm_host_services, 4, i, NULL);
      service->inventory_source = g_value_get_int (value);

      service->relevance = sim_idm_entry_get_relevance (service->inventory_source);

      value = gda_data_model_get_value_at (dm_host_services, 5, i, NULL);
      service->service = gda_value_is_null (value) ? NULL : g_value_dup_string (value);

      value = gda_data_model_get_value_at (dm_host_services, 6, i, NULL);
      service->version = gda_value_is_null (value) ? NULL : g_value_dup_string (value);

      service->gc = FALSE;

      if (protocol == SIM_PROTOCOL_TYPE_TCP)
        g_hash_table_insert (entry->_priv->service_tcp, GINT_TO_POINTER (port), service);
      else
        g_hash_table_insert (entry->_priv->service_udp, GINT_TO_POINTER (port), service);
    }
  }
}

void
sim_idm_entry_add_software_from_dm (GHashTable *entry_table, GdaDataModel *dm_host_software)
{
  GPtrArray *interface_list;
  SimIdmEntry *entry;
  gint i, rows;
  guint j;
  const GValue *value;
  SimUuid *host_id;
  gchar *cpe, *banner;

  // host_software

  rows = gda_data_model_get_n_rows (dm_host_software);

  for (i = 0; i < rows; i++)
  {

    value = gda_data_model_get_value_at (dm_host_software, 0, i, NULL);
    host_id = sim_uuid_new_from_blob (gda_value_get_blob (value));

    interface_list = g_hash_table_lookup (entry_table, host_id);
    if (!interface_list)
    {
      g_object_unref (host_id);
      continue;
    }
    g_object_unref (host_id);

    for (j = 0; j < interface_list->len; j++)
    {
      entry = interface_list->pdata[j];

      value = gda_data_model_get_value_at (dm_host_software, 1, i, NULL);
      cpe = gda_value_is_null (value) ? g_strdup ("") : g_value_dup_string (value);

      value = gda_data_model_get_value_at (dm_host_software, 2, i, NULL);
      banner = gda_value_is_null (value) ? g_strdup ("") : g_value_dup_string (value);

      g_hash_table_insert (entry->_priv->software, cpe, banner);
    }
  }
}

void
sim_idm_entry_finish_from_dm (GHashTable *entry_table)
{
  GPtrArray *interface_list;
  SimIdmEntry *entry;
  guint i;

  SIM_WHILE_HASH_TABLE (entry_table)
  {
    interface_list = value;

    for (i = 0; i < interface_list->len; i++)
    {
      entry = interface_list->pdata[i];

      if ((g_hash_table_size (entry->_priv->service_tcp) > 0) || (g_hash_table_size (entry->_priv->service_udp) > 0))
      {
        entry->_priv->service_raw = sim_idm_entry_service_get_string (entry);
      }

      if (g_hash_table_size (entry->_priv->software) > 0)
      {
        entry->_priv->software_raw = sim_idm_entry_software_get_string (entry);
      }

      entry->_priv->entry_raw = sim_idm_entry_get_command_string (entry);
    }
  }
}

void
sim_idm_entry_merge (SimIdmEntry *old_entry, SimIdmEntry *new_entry, SimIdmEntryChanges *changes, SimUuid *context_id, SimSensor *sensor)
{
  gint relevance;
  gboolean any_changes = FALSE;
  gboolean is_manual;

  g_return_if_fail (SIM_IS_IDM_ENTRY (old_entry));
  g_return_if_fail (SIM_IS_IDM_ENTRY (new_entry));

  relevance = sim_idm_entry_get_relevance (new_entry->_priv->inventory_source);
  is_manual = (new_entry->_priv->inventory_source == SIM_IDM_SOURCE_MANUAL) || (new_entry->_priv->inventory_source == SIM_IDM_SOURCE_MANUAL_LOCKED);

  if (new_entry->_priv->ip)
  {
    gchar *new_ip, *old_ip;

    if (old_entry->_priv->ip)
    {
      if (!sim_inet_equal (old_entry->_priv->ip, new_entry->_priv->ip) || is_manual)
      {
        if (!is_manual)
        {
          old_ip = sim_inet_get_canonical_name (old_entry->_priv->ip);
          new_ip = sim_inet_get_canonical_name (new_entry->_priv->ip);
          sim_idm_anomalies_send (SIM_IDM_ANOMALIES_IP, old_ip, new_ip, old_entry, context_id, sensor);
          g_free (old_ip);
          g_free (new_ip);
        }

        g_object_unref (old_entry->_priv->ip);

        old_entry->_priv->ip = g_object_ref (new_entry->_priv->ip);
        changes->ip = TRUE;
        any_changes = TRUE;
      }
    }
    else
    {
      old_entry->_priv->ip = g_object_ref (new_entry->_priv->ip);
      changes->ip = TRUE;
      any_changes = TRUE;
    }
  }

  if (g_hash_table_size (new_entry->_priv->username) > 0)
  {
    if (sim_idm_entry_username_merge (old_entry, sim_idm_entry_get_username (new_entry), new_entry->_priv->is_login) || is_manual)
    {
      g_free (old_entry->_priv->username_raw);
      old_entry->_priv->username_raw = sim_idm_entry_username_get_string (old_entry);
      changes->username = TRUE;
      any_changes = TRUE;
    }
  }

  if (new_entry->_priv->hostname)
  {
    if (old_entry->_priv->hostname)
    {
      if (strcmp (old_entry->_priv->hostname, new_entry->_priv->hostname) || is_manual)
      {
        if (!is_manual)
          sim_idm_anomalies_send (SIM_IDM_ANOMALIES_HOSTNAME, old_entry->_priv->hostname, new_entry->_priv->hostname, old_entry, context_id, sensor);

        g_free (old_entry->_priv->hostname);

        old_entry->_priv->hostname = g_strdup (new_entry->_priv->hostname);
        changes->hostname = TRUE;
        any_changes = TRUE;
      }
    }
    else
    {
      old_entry->_priv->hostname = g_strdup (new_entry->_priv->hostname);
      changes->hostname = TRUE;
      any_changes = TRUE;
    }
  }

  if (g_hash_table_size (new_entry->_priv->fqdns) > 0)
  {
    if (sim_idm_entry_fqdns_merge (old_entry, sim_idm_entry_get_fqdns (new_entry)) || is_manual)
    {
      g_free (old_entry->_priv->fqdns_raw);
      old_entry->_priv->fqdns_raw = sim_idm_entry_fqdns_get_string (old_entry);
      changes->fqdns = TRUE;
      any_changes = TRUE;
    }
  }

  if (new_entry->_priv->mac)
  {
    if (old_entry->_priv->mac)
    {
      if (strcmp (old_entry->_priv->mac, new_entry->_priv->mac))
      {
        g_free (old_entry->_priv->mac);

        old_entry->_priv->mac = g_strdup (new_entry->_priv->mac);
        changes->mac = TRUE;
        any_changes = TRUE;
      }
    }
    else
    {
      old_entry->_priv->mac = g_strdup (new_entry->_priv->mac);
      changes->mac = TRUE;
      any_changes = TRUE;
    }
  }

  if (new_entry->_priv->os && ((new_entry->_priv->relevance_os >= old_entry->_priv->relevance_os) || is_manual))
  {
    if (old_entry->_priv->os)
    {
      if (strcmp (old_entry->_priv->os, new_entry->_priv->os) || is_manual)
      {
        if (!is_manual)
          sim_idm_anomalies_send (SIM_IDM_ANOMALIES_OS, old_entry->_priv->os, new_entry->_priv->os, old_entry, context_id, sensor);

        g_free (old_entry->_priv->os);

        old_entry->_priv->os = g_strdup (new_entry->_priv->os);
        old_entry->_priv->relevance_os = new_entry->_priv->relevance_os;
        changes->os = TRUE;
        any_changes = TRUE;
      }
    }
    else
    {
      old_entry->_priv->os = g_strdup (new_entry->_priv->os);
      old_entry->_priv->relevance_os = new_entry->_priv->relevance_os;
      changes->os = TRUE;
      any_changes = TRUE;
    }
  }

  if (new_entry->_priv->cpu && ((new_entry->_priv->relevance_cpu >= old_entry->_priv->relevance_cpu) || is_manual))
  {
    if (old_entry->_priv->cpu)
    {
      if (strcmp (old_entry->_priv->cpu, new_entry->_priv->cpu) || is_manual)
      {
        if (!is_manual)
          sim_idm_anomalies_send (SIM_IDM_ANOMALIES_CPU, old_entry->_priv->cpu, new_entry->_priv->cpu, old_entry, context_id, sensor);

        g_free (old_entry->_priv->cpu);

        old_entry->_priv->cpu = g_strdup (new_entry->_priv->cpu);
        old_entry->_priv->relevance_cpu = new_entry->_priv->relevance_cpu;
        changes->cpu = TRUE;
        any_changes = TRUE;
      }
    }
    else
    {
      old_entry->_priv->cpu = g_strdup (new_entry->_priv->cpu);
      old_entry->_priv->relevance_cpu = new_entry->_priv->relevance_cpu;
      changes->cpu = TRUE;
      any_changes = TRUE;
    }
  }

  if (new_entry->_priv->memory && ((new_entry->_priv->relevance_memory >= old_entry->_priv->relevance_memory) || is_manual))
  {
    if (old_entry->_priv->memory)
    {
      if ((old_entry->_priv->memory != new_entry->_priv->memory) || is_manual)
      {
        if (!is_manual)
         {
          gchar *old_memory_str = g_strdup_printf ("%d", old_entry->_priv->memory);
          gchar *new_memory_str = g_strdup_printf ("%d", new_entry->_priv->memory);
          sim_idm_anomalies_send (SIM_IDM_ANOMALIES_MEMORY, old_memory_str, new_memory_str, old_entry, context_id, sensor);
          g_free (old_memory_str);
          g_free (new_memory_str);
        }

        old_entry->_priv->memory = new_entry->_priv->memory;
        old_entry->_priv->relevance_memory = new_entry->_priv->relevance_memory;
        changes->memory = TRUE;
        any_changes = TRUE;
      }
    }
    else
    {
      old_entry->_priv->memory = new_entry->_priv->memory;
      old_entry->_priv->relevance_memory = new_entry->_priv->relevance_memory;
      changes->memory = TRUE;
      any_changes = TRUE;
    }
  }

  if (new_entry->_priv->video && ((new_entry->_priv->relevance_video >= old_entry->_priv->relevance_video) || is_manual))
  {
    if (old_entry->_priv->video)
    {
      if (strcmp (old_entry->_priv->video, new_entry->_priv->video) || is_manual)
      {
        if (!is_manual)
          sim_idm_anomalies_send (SIM_IDM_ANOMALIES_VIDEO, old_entry->_priv->video, new_entry->_priv->video, old_entry, context_id, sensor);

        g_free (old_entry->_priv->video);

        old_entry->_priv->video = g_strdup (new_entry->_priv->video);
        old_entry->_priv->relevance_video = new_entry->_priv->relevance_video;
        changes->video = TRUE;
        any_changes = TRUE;
      }
    }
    else
    {
      old_entry->_priv->video = g_strdup (new_entry->_priv->video);
      old_entry->_priv->relevance_video = new_entry->_priv->relevance_video;
      changes->video = TRUE;
      any_changes = TRUE;
    }
  }

  if ((g_hash_table_size (new_entry->_priv->service_tcp) > 0) || (g_hash_table_size (new_entry->_priv->service_udp) > 0))
  {
    if (sim_idm_entry_service_merge (old_entry, sim_idm_entry_get_service (new_entry), new_entry->_priv->inventory_source, relevance, context_id, sensor) || is_manual)
    {
      g_free (old_entry->_priv->service_raw);
      old_entry->_priv->service_raw = sim_idm_entry_service_get_string (old_entry);
      changes->service = TRUE;
      any_changes = TRUE;
    }
  }

  if (g_hash_table_size (new_entry->_priv->software) > 0)
  {
    if (sim_idm_entry_software_merge (old_entry, sim_idm_entry_get_software (new_entry), TRUE) || is_manual)
    {
      g_free (old_entry->_priv->software_raw);
      old_entry->_priv->software_raw = sim_idm_entry_software_get_string (old_entry);
      changes->software = TRUE;
      any_changes = TRUE;
    }
  }

  if (new_entry->_priv->state)
  {
    if (old_entry->_priv->state)
    {
      if (strcmp (old_entry->_priv->state, new_entry->_priv->state) || is_manual)
      {
        if (!is_manual)
          sim_idm_anomalies_send (SIM_IDM_ANOMALIES_STATE, old_entry->_priv->state, new_entry->_priv->state, old_entry, context_id, sensor);

        g_free (old_entry->_priv->state);

        old_entry->_priv->state = g_strdup (new_entry->_priv->state);
        changes->state = TRUE;
        any_changes = TRUE;
      }
    }
    else
    {
      old_entry->_priv->state = g_strdup (new_entry->_priv->state);
      changes->state = TRUE;
      any_changes = TRUE;
    }
  }

  if (any_changes)
  {
    g_free (old_entry->_priv->entry_raw);
    old_entry->_priv->entry_raw = sim_idm_entry_get_command_string (old_entry);
  }
}

void
sim_idm_entry_propagate_host (SimIdmEntry *old_entry, SimIdmEntry *new_entry, SimIdmEntryChanges *changes)
{
  g_return_if_fail (SIM_IS_IDM_ENTRY (old_entry));
  g_return_if_fail (SIM_IS_IDM_ENTRY (new_entry));

  if (changes->username)
  {
    if (old_entry->_priv->username_raw)
    {
      sim_idm_entry_username_merge (old_entry, old_entry->_priv->username_raw, FALSE);
      g_free (old_entry->_priv->username_raw);
      old_entry->_priv->username_raw = NULL;
    }

    if (new_entry->_priv->username_raw)
    {
      sim_idm_entry_username_merge (old_entry, new_entry->_priv->username_raw, TRUE);
      old_entry->_priv->username_raw = sim_idm_entry_username_get_string (old_entry);
    }
  }

  if (changes->hostname)
  {
    g_free (old_entry->_priv->hostname);
    old_entry->_priv->hostname = g_strdup (new_entry->_priv->hostname);
  }

  if (changes->fqdns)
  {
    // We can only add a fqdn, so we don't need to destroy the old fqdns
    g_free (old_entry->_priv->fqdns_raw);

    sim_idm_entry_fqdns_merge (old_entry, new_entry->_priv->fqdns_raw);
    old_entry->_priv->fqdns_raw = sim_idm_entry_fqdns_get_string (old_entry);
  }

  if (changes->os)
  {
    g_free (old_entry->_priv->os);
    old_entry->_priv->os = g_strdup (new_entry->_priv->os);
    old_entry->_priv->relevance_os = new_entry->_priv->relevance_os;
  }

  if (changes->cpu)
  {
    g_free (old_entry->_priv->cpu);
    old_entry->_priv->cpu = g_strdup (new_entry->_priv->cpu);
    old_entry->_priv->relevance_cpu = new_entry->_priv->relevance_cpu;
  }

  if (changes->memory)
  {
    old_entry->_priv->memory = new_entry->_priv->memory;
    old_entry->_priv->relevance_memory = new_entry->_priv->relevance_memory;
  }

  if (changes->video)
  {
    g_free (old_entry->_priv->video);
    old_entry->_priv->video = g_strdup (new_entry->_priv->video);
    old_entry->_priv->relevance_video = new_entry->_priv->relevance_video;
  }

  if (changes->software)
  {
    if (old_entry->_priv->software_raw)
    {
      sim_idm_entry_software_merge (old_entry, old_entry->_priv->software_raw, FALSE);
      g_free (old_entry->_priv->software_raw);
      old_entry->_priv->software_raw = NULL;
    }

    if (new_entry->_priv->software_raw)
    {
      sim_idm_entry_software_merge (old_entry, new_entry->_priv->software_raw, TRUE);
      old_entry->_priv->software_raw = sim_idm_entry_software_get_string (old_entry);
    }
  }

  if (changes->state)
  {
    g_free (old_entry->_priv->state);
    old_entry->_priv->state = g_strdup (new_entry->_priv->state);
  }

  g_free (old_entry->_priv->entry_raw);
  old_entry->_priv->entry_raw = sim_idm_entry_get_command_string (old_entry);
}

gchar *
sim_idm_entry_property_get_string_json (const gchar *property)
{
  JsonGenerator *generator;
  JsonArray *array;
  JsonNode *root, *node;
  gchar *json_data;

  generator = json_generator_new ();
  array = json_array_new ();

  node = json_node_new (JSON_NODE_VALUE);
  json_node_set_string (node, property);
  json_array_add_element (array, node);

  root = json_node_new (JSON_NODE_ARRAY);
  json_node_take_array (root, array);
  json_generator_set_root (generator, root);
  json_data = json_generator_to_data (generator, NULL);

  json_node_free (root);
  g_object_unref (generator);

  return json_data;
}

gchar *
sim_idm_entry_username_get_string_json (SimIdmEntry *entry)
{
  return sim_idm_entry_to_json (entry->_priv->username, sim_idm_entry_username_to_json);
}

gchar *
sim_idm_entry_service_get_string_json (SimIdmEntry *entry)
{
  SimIdmServiceEntry *service_entry;
  GHashTableIter iter;
  gpointer iter_key, iter_value;
  JsonGenerator *generator;
  JsonArray *array;
  JsonNode *root, *node;
  gchar *json_data;
  gchar * str;

  generator = json_generator_new ();

  array = json_array_new ();

  if (g_hash_table_size (entry->_priv->service_tcp) > 0)
  {
    g_hash_table_iter_init (&iter, entry->_priv->service_tcp);
    while (g_hash_table_iter_next (&iter, &iter_key, &iter_value))
    {
      service_entry = iter_value;

      node = json_node_new (JSON_NODE_VALUE);
      if (service_entry->version)
        str = g_strdup_printf ("tcp|%d|%s|%s,", GPOINTER_TO_INT (iter_key), service_entry->service, service_entry->version);
      else
        str = g_strdup_printf ("tcp|%d|%s,", GPOINTER_TO_INT (iter_key), service_entry->service);
      json_node_set_string (node, str);
      json_array_add_element (array, node);
      g_free (str);
    }
  }

  if (g_hash_table_size (entry->_priv->service_udp) > 0)
  {
    g_hash_table_iter_init (&iter, entry->_priv->service_udp);
    while (g_hash_table_iter_next (&iter, &iter_key, &iter_value))
    {
      service_entry = iter_value;

      node = json_node_new (JSON_NODE_VALUE);
      if (service_entry->version)
        str = g_strdup_printf ("udp|%d|%s|%s,", GPOINTER_TO_INT (iter_key), service_entry->service, service_entry->version);
      else
        str = g_strdup_printf ("udp|%d|%s,", GPOINTER_TO_INT (iter_key), service_entry->service);
      json_node_set_string (node, str);
      json_array_add_element (array, node);
      g_free (str);
    }
  }

  root = json_node_new (JSON_NODE_ARRAY);
  json_node_take_array (root, array);
  json_generator_set_root (generator, root);
  json_data = json_generator_to_data (generator, NULL);

  json_node_free (root);
  g_object_unref (generator);

  return json_data;
}

gchar *
sim_idm_entry_software_get_string_json (SimIdmEntry *entry)
{
  return sim_idm_entry_to_json (entry->_priv->software, sim_idm_entry_software_to_json);
}

gchar *
sim_idm_entry_service_get_string_db_insert (SimIdmEntry *entry, SimDatabase *database)
{
  SimIdmServiceEntry *service_entry;
  GHashTableIter iter;
  gpointer iter_key, iter_value;
  GString *gstr;
  gchar *e_service;
  gchar *e_version;
  gchar *ret;

  gstr = g_string_new ("");
  if (g_hash_table_size (entry->_priv->service_tcp) > 0)
  {
    g_hash_table_iter_init (&iter, entry->_priv->service_tcp);
    while (g_hash_table_iter_next (&iter, &iter_key, &iter_value))
    {
      e_service = NULL;
      e_version = NULL;

      service_entry = iter_value;
      if (service_entry->service)
        e_service = sim_database_str_escape (database, service_entry->service, 0);
      if (service_entry->version)
        e_version = sim_database_str_escape (database, service_entry->version, 0);
      g_string_append_printf (gstr, "(%s, %s, %d, %d, '%s', %s%s%s, %d),",
                              sim_uuid_get_db_string (entry->_priv->host_id),
                              sim_inet_get_db_string (entry->_priv->ip),
                              GPOINTER_TO_INT (iter_key),
                              SIM_PROTOCOL_TYPE_TCP,
                              e_service ? e_service : "",
                              e_version ? "'" : "",
                              e_version ? e_version : "NULL",
                              e_version ? "'" : "",
                              entry->_priv->inventory_source);
      g_free (e_service);
      g_free (e_version);
    }
  }

  if (g_hash_table_size (entry->_priv->service_udp) > 0)
  {
    g_hash_table_iter_init (&iter, entry->_priv->service_udp);
    while (g_hash_table_iter_next (&iter, &iter_key, &iter_value))
    {
      e_service = NULL;
      e_version = NULL;

      service_entry = iter_value;
      if (service_entry->service)
        e_service = sim_database_str_escape (database, service_entry->service, 0);
      if (service_entry->version)
        e_version = sim_database_str_escape (database, service_entry->version, 0);
      g_string_append_printf (gstr, "(%s, %s, %d, %d, '%s', %s%s%s, %d),",
                              sim_uuid_get_db_string (entry->_priv->host_id),
                              sim_inet_get_db_string (entry->_priv->ip),
                              GPOINTER_TO_INT (iter_key),
                              SIM_PROTOCOL_TYPE_UDP,
                              e_service ? e_service : "",
                              e_version ? "'" : "",
                              e_version ? e_version : "NULL",
                              e_version ? "'" : "",
                              entry->_priv->inventory_source);
      g_free (e_service);
      g_free (e_version);
    }
  }

  /* remove last ',' */
  if ((g_hash_table_size (entry->_priv->service_tcp) > 0) || (g_hash_table_size (entry->_priv->service_udp) > 0))
    g_string_truncate (gstr, gstr->len - 1);

  ret = g_string_free (gstr, FALSE);

  if (strcmp (ret, ""))
    return ret;
  else
  {
    g_free (ret);
    return NULL;
  }
}

gchar *
sim_idm_entry_software_get_string_db_insert (SimIdmEntry *entry, SimDatabase *database)
{
  GHashTableIter iter;
  gpointer iter_key, iter_value;
  GString *gstr;
  gchar *e_cpe, *e_banner;
  gchar *ret = NULL;

  if (g_hash_table_size (entry->_priv->software) > 0)
  {
    gstr = g_string_new ("");

    g_hash_table_iter_init (&iter, entry->_priv->software);
    while (g_hash_table_iter_next (&iter, &iter_key, &iter_value))
    {
      e_cpe = sim_database_str_escape (database, iter_key, 0);
      e_banner = sim_database_str_escape (database, iter_value, 0);
      g_string_append_printf (gstr, "(%s, '%s', '%s', %d),",
                              sim_uuid_get_db_string (entry->_priv->host_id),
                              e_cpe,
                              e_banner,
                              entry->_priv->inventory_source);
      g_free (e_cpe);
      g_free (e_banner);
    }

    /* remove last ',' */
    g_string_truncate (gstr, gstr->len - 1);

    ret = g_string_free (gstr, FALSE);
  }

  return ret;
}

SimInet *
sim_idm_entry_get_ip (SimIdmEntry *entry)
{
  g_return_val_if_fail (SIM_IS_IDM_ENTRY (entry), NULL);

  return entry->_priv->ip;
}

const gchar *
sim_idm_entry_get_username (SimIdmEntry *entry)
{
  g_return_val_if_fail (SIM_IS_IDM_ENTRY (entry), NULL);

  return entry->_priv->username_raw;
}

const gchar *
sim_idm_entry_get_hostname (SimIdmEntry *entry)
{
  g_return_val_if_fail (SIM_IS_IDM_ENTRY (entry), NULL);

  return entry->_priv->hostname;
}

const gchar *
sim_idm_entry_get_fqdns (SimIdmEntry *entry)
{
  g_return_val_if_fail (SIM_IS_IDM_ENTRY (entry), NULL);

  return entry->_priv->fqdns_raw;
}

void
sim_idm_entry_set_hostname (SimIdmEntry *entry, const gchar *hostname)
{
  g_return_if_fail (SIM_IS_IDM_ENTRY (entry));

  if (entry->_priv->hostname)
    g_free (entry->_priv->hostname);

  entry->_priv->hostname = g_strdup (hostname);

  g_free (entry->_priv->entry_raw);
  entry->_priv->entry_raw = sim_idm_entry_get_command_string (entry);
}

const gchar *
sim_idm_entry_get_mac (SimIdmEntry *entry)
{
  g_return_val_if_fail (SIM_IS_IDM_ENTRY (entry), NULL);

  return entry->_priv->mac;
}

void
sim_idm_entry_set_mac (SimIdmEntry *entry, const gchar *mac)
{
  g_return_if_fail (SIM_IS_IDM_ENTRY (entry));

  if (entry->_priv->mac)
    g_free (entry->_priv->hostname);

  entry->_priv->mac = g_strdup (mac);

  g_free (entry->_priv->entry_raw);
  entry->_priv->entry_raw = sim_idm_entry_get_command_string (entry);
}

const gchar *
sim_idm_entry_get_os (SimIdmEntry *entry)
{
  g_return_val_if_fail (SIM_IS_IDM_ENTRY (entry), NULL);

  return entry->_priv->os;
}

const gchar *
sim_idm_entry_get_cpu (SimIdmEntry *entry)
{
  g_return_val_if_fail (SIM_IS_IDM_ENTRY (entry), NULL);

  return entry->_priv->cpu;
}

gint
sim_idm_entry_get_memory (SimIdmEntry *entry)
{
  g_return_val_if_fail (SIM_IS_IDM_ENTRY (entry), 0);

  return entry->_priv->memory;
}

const gchar *
sim_idm_entry_get_video (SimIdmEntry *entry)
{
  g_return_val_if_fail (SIM_IS_IDM_ENTRY (entry), NULL);

  return entry->_priv->video;
}

const gchar *
sim_idm_entry_get_service (SimIdmEntry *entry)
{
  g_return_val_if_fail (SIM_IS_IDM_ENTRY (entry), NULL);

  return entry->_priv->service_raw;
}

const gchar *
sim_idm_entry_get_software (SimIdmEntry *entry)
{
  g_return_val_if_fail (SIM_IS_IDM_ENTRY (entry), NULL);

  return entry->_priv->software_raw;
}

const char *
sim_idm_entry_get_state (SimIdmEntry *entry)
{
  g_return_val_if_fail (SIM_IS_IDM_ENTRY (entry), FALSE);

  return entry->_priv->state;
}

gint8
sim_idm_entry_get_source_id (SimIdmEntry *entry)
{
  g_return_val_if_fail (SIM_IS_IDM_ENTRY (entry), FALSE);

  return entry->_priv->inventory_source;
}

void
sim_idm_entry_set_source_id (SimIdmEntry *entry, gint8 source_id)
{
  g_return_if_fail (SIM_IS_IDM_ENTRY (entry));

  entry->_priv->inventory_source = source_id;

  g_free (entry->_priv->entry_raw);
  entry->_priv->entry_raw = sim_idm_entry_get_command_string (entry);
}

SimUuid *
sim_idm_entry_get_host_id (SimIdmEntry *entry)
{
  g_return_val_if_fail (SIM_IS_IDM_ENTRY (entry), NULL);

  return entry->_priv->host_id;
}

void
sim_idm_entry_set_host_id (SimIdmEntry *entry, SimUuid *host_id)
{
  g_return_if_fail (SIM_IS_IDM_ENTRY (entry));

  if (entry->_priv->host_id)
    g_object_unref (entry->_priv->host_id);

  entry->_priv->host_id = g_object_ref (host_id);

  g_free (entry->_priv->entry_raw);
  entry->_priv->entry_raw = sim_idm_entry_get_command_string (entry);
}

const gchar *
sim_idm_entry_get_string (SimIdmEntry *entry)
{
  g_return_val_if_fail (SIM_IS_IDM_ENTRY (entry), NULL);

  return entry->_priv->entry_raw;
}

void
sim_idm_entry_debug_print (SimIdmEntry *entry)
{
  gchar *command_str;

  command_str = sim_idm_entry_get_command_string (entry);

  ossim_debug ("%s", __func__);  
  ossim_debug ("    %s", command_str);

  g_free (command_str);
}

/* Private Methods */

static gboolean
sim_idm_entry_username_merge (SimIdmEntry *entry, const gchar *username, gboolean is_login)
{
  gchar **split_list, **i;
  gboolean ret = FALSE;

  g_return_val_if_fail (username, FALSE);
  
  split_list = g_strsplit (username, ",", 0);
  for (i = split_list; *i; i++)
    if (is_login)
    {
      if (!g_hash_table_lookup (entry->_priv->username, *i))
      {
        g_hash_table_insert (entry->_priv->username, g_strdup (*i), GINT_TO_POINTER (GENERIC_VALUE));
        ret = TRUE;
      }
    }
    else
    {
      ret = ret || g_hash_table_remove (entry->_priv->username, *i);
    }

  g_strfreev (split_list);

  return ret;
}

static gboolean
sim_idm_entry_fqdns_merge (SimIdmEntry *entry, const gchar *fqdns)
{
  gchar **split_list, **i;
  gboolean ret = FALSE;

  g_return_val_if_fail (fqdns, FALSE);

  split_list = g_strsplit (fqdns, ",", 0);
  for (i = split_list; *i; i++)
    if (!g_hash_table_lookup (entry->_priv->fqdns, *i))
    {
      g_hash_table_insert (entry->_priv->fqdns, g_strdup (*i), GINT_TO_POINTER (GENERIC_VALUE));
      ret = TRUE;
    }

  g_strfreev (split_list);

  return ret;

}

static gboolean
sim_idm_entry_service_merge (SimIdmEntry *entry, const gchar *service, gint inventory_source, gint relevance, SimUuid *context_id, SimSensor *sensor)
{
  SimIdmServiceEntry *service_new, *service_old;
  gint service_port;
  gchar *service_name, *service_version;
  gchar **split_list, **split;
  gchar **i;
  gboolean ret = FALSE;
  gboolean is_manual;

  g_return_val_if_fail (service, FALSE);

  // Mark all entries with the same 'inventory_source' as garbage collectable
  // Currently disabled
#if 0
  g_hash_table_foreach (entry->_priv->service_tcp, sim_idm_entry_service_gc_mark, &inventory_source);
  g_hash_table_foreach (entry->_priv->service_udp, sim_idm_entry_service_gc_mark, &inventory_source);
#endif

  is_manual = (inventory_source == SIM_IDM_SOURCE_MANUAL) || (inventory_source == SIM_IDM_SOURCE_MANUAL_LOCKED);

  split_list = g_strsplit (service, ",", 0);
  for (i = split_list; *i; i++)
  {
    split = g_strsplit (*i, "|", 0);

    if (sim_string_is_number (*(split + 1), FALSE))
      service_port = strtol (*(split + 1), (char **) NULL, 10);
    else
    {
      g_message ("Error: idm-event incorrect. Please check the service issued from the agent: %s", service);
      g_strfreev (split);
      g_strfreev (split_list);
      return FALSE;
    }

    service_name = *(split + 2);
    service_version = *(split + 3);

    if (!strcmp (*split, "udp") || !strcmp (*split, "17"))
    {
      service_old = g_hash_table_lookup (entry->_priv->service_udp, GINT_TO_POINTER (service_port));
      if (!service_old || (relevance >= service_old->relevance) || is_manual)
      {
        service_new = g_new (SimIdmServiceEntry, 1);
        service_new->inventory_source = inventory_source;
        service_new->relevance = relevance;
        service_new->service = g_strdup (service_name);
        service_new->version = g_strdup (service_version);
        service_new->gc = FALSE;

        if (!service_old
            || (service_old->service && service_name && strcmp (service_old->service, service_name))
            || (service_old->version && service_version && strcmp (service_old->version, service_version))
            || is_manual)
          ret = TRUE;

        if (service_old
            && ((service_old->service && service_name && strcmp (service_old->service, service_name))|| (service_old->version && service_version && strcmp (service_old->version, service_version)))
            && !is_manual)
        {
          char *old_value, *new_value;

          if (service_old->version)
            old_value = g_strdup_printf ("udp|%d|%s|%s", service_port, service_old->service, service_old->version);
          else
            old_value = g_strdup_printf ("udp|%d|%s", service_port, service_old->service);
          if (service_version)
            new_value = g_strdup_printf ("udp|%d|%s|%s", service_port, service_name, service_version);
          else
            new_value = g_strdup_printf ("udp|%d|%s", service_port, service_name);
          sim_idm_anomalies_send (SIM_IDM_ANOMALIES_SERVICE, old_value, new_value, entry, context_id, sensor);
          g_free (old_value);
          g_free (new_value);
        }

        g_hash_table_insert (entry->_priv->service_udp, GINT_TO_POINTER (service_port), service_new);
      }
    }
    else
    {
      if (strcmp (*split, "tcp") && strcmp (*split, "6"))
        g_message ("Received unknown protocol '%s'. Using tcp for service '%s' version '%s' in port %d from source %d", *split, service_name, service_version, service_port, inventory_source);

      service_old = g_hash_table_lookup (entry->_priv->service_tcp, GINT_TO_POINTER (service_port));
      if (!service_old || (relevance >= service_old->relevance) || is_manual)
      {
        service_new = g_new (SimIdmServiceEntry, 1);
        service_new->inventory_source = inventory_source;
        service_new->relevance = relevance;
        service_new->service = g_strdup (service_name);
        service_new->version = g_strdup (service_version);
        service_new->gc = FALSE;

        if (!service_old
            || (service_old->service && service_name && strcmp (service_old->service, service_name))
            || (service_old->version && service_version && strcmp (service_old->version, service_version))
            || is_manual)
          ret = TRUE;

        if (service_old
            && ((service_old->service && service_name && strcmp (service_old->service, service_name))|| (service_old->version && service_version && strcmp (service_old->version, service_version)))
            && !is_manual)
        {
          char *old_value, *new_value;

          if (service_old->version)
            old_value = g_strdup_printf ("tcp|%d|%s|%s", service_port, service_old->service, service_old->version);
          else
            old_value = g_strdup_printf ("tcp|%d|%s", service_port, service_old->service);
          if (service_version)
            new_value = g_strdup_printf ("tcp|%d|%s|%s", service_port, service_name, service_version);
          else
            new_value = g_strdup_printf ("tcp|%d|%s", service_port, service_name);

          sim_idm_anomalies_send (SIM_IDM_ANOMALIES_SERVICE, old_value, new_value, entry, context_id, sensor);
          g_free (old_value);
          g_free (new_value);
        }

        g_hash_table_insert (entry->_priv->service_tcp, GINT_TO_POINTER (service_port), service_new);
      }
    }

    g_strfreev (split);
  }

  g_strfreev (split_list);

  // Delete entries not updated
  // Currently disabled
#if 0
  g_hash_table_foreach_remove (entry->_priv->service_tcp, sim_idm_entry_service_gc_clean, &ret);
  g_hash_table_foreach_remove (entry->_priv->service_udp, sim_idm_entry_service_gc_clean, &ret);
#endif

  return ret;
}

#if 0
static void
sim_idm_entry_service_gc_mark (gpointer key, gpointer value, gpointer user_data)
{
  SimIdmServiceEntry *service = value;
  gint *inventory_source = user_data;

  // unused parameter
  (void) key;

  if (service->inventory_source == *inventory_source)
    service->gc = TRUE;
}

static gboolean
sim_idm_entry_service_gc_clean (gpointer key, gpointer value, gpointer user_data)
{
  gboolean *hash_modified = user_data;

  // unused parameters
  (void) key;

  *hash_modified = *hash_modified || ((SimIdmServiceEntry *) value)->gc;

  return ((SimIdmServiceEntry *) value)->gc;
}
#endif

static gboolean
sim_idm_entry_software_merge (SimIdmEntry *entry, const gchar *software, gboolean is_install)
{
  gchar **split_list, **split;
  gchar **i;
  gchar *cpe, *cpe_banner;
  gchar *banner_new, *banner_old;
  gboolean ret = FALSE;

  g_return_val_if_fail (software, FALSE);

  split_list = g_strsplit (software, ",", 0);
  for (i = split_list; *i; i++)
  {
    split = g_strsplit (*i, "|", 2);

    cpe = *split;
    banner_new = *(split + 1);

    if (is_install)
    {
      if ((banner_old = g_hash_table_lookup (entry->_priv->software, cpe)))
      {
        if (strcmp (banner_old, banner_new))
        {
          g_hash_table_insert (entry->_priv->software, g_strdup (cpe), g_strdup (banner_new));
          ret = TRUE;
        }
      }
      else
      {
        if (strcmp (cpe, ""))
        {
          g_hash_table_insert (entry->_priv->software, g_strdup (cpe), g_strdup (banner_new));
          ret = TRUE;
        }
        else
        {
          cpe = g_compute_checksum_for_data (G_CHECKSUM_SHA1, (const guchar *) banner_new, strlen (banner_new));
          cpe_banner = g_strdup_printf("hash_banner:%s", cpe);
          g_free (cpe);

          if (!g_hash_table_lookup (entry->_priv->software, cpe_banner))
          {
            g_hash_table_insert (entry->_priv->software, cpe_banner, g_strdup (banner_new));
            ret = TRUE;
          }
          else
          {
            g_free (cpe_banner);
          }
        }
      }
    }
    else
    {
      if (strcmp (cpe, ""))
      {
        ret = ret || g_hash_table_remove (entry->_priv->software, cpe);
      }
      else
      {
        cpe = g_compute_checksum_for_data (G_CHECKSUM_SHA1, (const guchar *) banner_new, strlen (banner_new));
        cpe_banner = g_strdup_printf("hash_banner:%s", cpe);
        g_free (cpe);

        ret = ret || g_hash_table_remove (entry->_priv->software, cpe_banner);

        g_free (cpe_banner);
      }
    }

    g_strfreev (split);
  }

  g_strfreev (split_list);

  return ret;
}

static gchar *
sim_idm_entry_username_get_string (SimIdmEntry *entry)
{
  return sim_idm_entry_hash_get_string (entry->_priv->username);
}

static gchar *
sim_idm_entry_fqdns_get_string (SimIdmEntry *entry)
{
  return sim_idm_entry_hash_get_string (entry->_priv->fqdns);
}

static gchar *
sim_idm_entry_hash_get_string (GHashTable *hash_table)
{
  GHashTableIter iter;
  gpointer iter_key, iter_value;
  GString *gstr;
  gchar *ret = NULL;

  if (g_hash_table_size (hash_table) > 0)
  {
    gstr = g_string_new ("");

    g_hash_table_iter_init (&iter, hash_table);
    while (g_hash_table_iter_next (&iter, &iter_key, &iter_value))
      g_string_append_printf (gstr, "%s,", (gchar *) iter_key);

    /* remove last ',' */
    g_string_truncate (gstr, gstr->len - 1);

    ret = g_string_free (gstr, FALSE);
  }

  return ret;
}

static gchar *
sim_idm_entry_service_get_string (SimIdmEntry *entry)
{
  SimIdmServiceEntry *service_entry;
  GHashTableIter iter;
  gpointer iter_key, iter_value;
  GString *gstr;
  gchar *ret;

  gstr = g_string_new ("");
  if (g_hash_table_size (entry->_priv->service_tcp) > 0)
  {
    g_hash_table_iter_init (&iter, entry->_priv->service_tcp);
    while (g_hash_table_iter_next (&iter, &iter_key, &iter_value))
    {
      service_entry = iter_value;
      if (service_entry->version)
        g_string_append_printf (gstr, "tcp|%d|%s|%s,", GPOINTER_TO_INT (iter_key), service_entry->service, service_entry->version);
      else
        g_string_append_printf (gstr, "tcp|%d|%s,", GPOINTER_TO_INT (iter_key), service_entry->service);
    }
  }

  if (g_hash_table_size (entry->_priv->service_udp) > 0)
  {
    g_hash_table_iter_init (&iter, entry->_priv->service_udp);
    while (g_hash_table_iter_next (&iter, &iter_key, &iter_value))
    {
      service_entry = iter_value;
      if (service_entry->version)
        g_string_append_printf (gstr, "udp|%d|%s|%s,", GPOINTER_TO_INT (iter_key), service_entry->service, service_entry->version);
      else
        g_string_append_printf (gstr, "udp|%d|%s,", GPOINTER_TO_INT (iter_key), service_entry->service);
    }
  }

  /* remove last ',' */
  if ((g_hash_table_size (entry->_priv->service_tcp) > 0) || (g_hash_table_size (entry->_priv->service_udp) > 0))
    g_string_truncate (gstr, gstr->len - 1);

  ret = g_string_free (gstr, FALSE);

  if (strcmp (ret, ""))
    return ret;
  else
  {
    g_free (ret);
    return NULL;
  }
}

static gchar *
sim_idm_entry_software_get_string (SimIdmEntry *entry)
{
  GHashTableIter iter;
  gpointer iter_key, iter_value;
  GString *gstr;
  gchar *ret = NULL;

  if (g_hash_table_size (entry->_priv->software) > 0)
  {
    gstr = g_string_new ("");

    g_hash_table_iter_init (&iter, entry->_priv->software);
    while (g_hash_table_iter_next (&iter, &iter_key, &iter_value))
    {
      if (strncmp ((gchar *) iter_key, "hash_banner:", 12)) // strlen ("hash_banner:")
        g_string_append_printf (gstr, "%s|%s,", (gchar *) iter_key, (gchar *) iter_value);
      else
        g_string_append_printf (gstr, "|%s,", (gchar *) iter_value);
    }

    /* remove last ',' */
    g_string_truncate (gstr, gstr->len - 1);

    ret = g_string_free (gstr, FALSE);
  }

  return ret;
}

static gchar *
sim_idm_entry_get_command_string (SimIdmEntry *entry)
{
  SimCommand *command;
  gchar *ret;

  g_return_val_if_fail (SIM_IS_IDM_ENTRY (entry), NULL);

  command = SIM_COMMAND (g_object_new (SIM_TYPE_COMMAND, NULL));
  command->type = SIM_COMMAND_TYPE_IDM_EVENT;

  command->data.idm_event.username = g_strdup (entry->_priv->username_raw);
  command->data.idm_event.hostname = g_strdup (entry->_priv->hostname);
  command->data.idm_event.mac = g_strdup (entry->_priv->mac);
  if (entry->_priv->host_id)
    command->data.idm_event.host_id = g_object_ref (entry->_priv->host_id);
  else
    command->data.idm_event.host_id = NULL;

  ret = sim_command_get_string (command);

  g_object_unref (command);

  return ret;
}

static gchar *
sim_idm_entry_to_json (GHashTable *hash_table, GHFunc json_serialize_func)
{
  JsonGenerator *generator;
  JsonArray *array;
  JsonNode *root;
  gchar *json_data;

  generator = json_generator_new ();
  array = json_array_new ();

  g_hash_table_foreach (hash_table, json_serialize_func, array);

  root = json_node_new (JSON_NODE_ARRAY);
  json_node_take_array (root, array);
  json_generator_set_root (generator, root);
  json_data = json_generator_to_data (generator, NULL);

  json_node_free (root);
  g_object_unref (generator);

  return json_data;

}

static void
sim_idm_entry_username_to_json (gpointer key, gpointer value, gpointer user_data)
{
  JsonArray *array;
  JsonNode *val;

  // unused parameter
  (void) value;

  array = (JsonArray *) user_data;

  val = json_node_new (JSON_NODE_VALUE);
  json_node_set_string (val, key);
  json_array_add_element (array, val);
}

static void
sim_idm_entry_software_to_json (gpointer key, gpointer value, gpointer user_data)
{
  JsonArray *array;
  JsonNode *val;
  gchar *str;

  array = (JsonArray *) user_data;

  val = json_node_new (JSON_NODE_VALUE);
  str = g_strdup_printf ("%s|%s", (gchar *) key, (gchar *) value);
  json_node_set_string (val, str);
  json_array_add_element (array, val);
  g_free (str);
}

static gchar *
sim_idm_entry_property_from_json (const gchar *property_json)
{
  JsonParser *parser;
  JsonNode *root, *node;
  JsonArray *array;
  gchar *ret = NULL;
  GError *error = NULL;

  g_return_val_if_fail (property_json, NULL);

  parser = json_parser_new ();

  if (!json_parser_load_from_data (parser, property_json, strlen (property_json), &error))
  {
    g_message ("%s: cannot parse property from json: %s", __func__, error ? error->message : "");
    if (error)
      g_error_free (error);
  }
  else
  {
    root = json_parser_get_root (parser);
    array = json_node_get_array (root);
    node = json_array_get_element (array, 0);
    ret = g_strdup (json_node_get_string (node));
  }

  g_object_unref (parser);

  return ret;
}

static gchar *
sim_idm_entry_username_from_json (const gchar *username_json)
{
  JsonParser *parser;
  JsonNode *root, *node;
  JsonArray *array;
  guint array_size, i;
  const gchar *username;
  GString *gstr;
  gchar *ret = NULL;
  GError *error = NULL;

  g_return_val_if_fail (username_json, NULL);

  parser = json_parser_new ();

  if (!json_parser_load_from_data (parser, username_json, strlen(username_json), &error))
  {
    g_message ("%s: cannot parse username from json: %s", __func__, error ? error->message : "");
    if (error)
      g_error_free (error);
  }
  else
  {
    gstr = g_string_new ("");

    root = json_parser_get_root (parser);

    array = json_node_get_array (root);
    array_size = json_array_get_length (array);

    // Each entry will have the format "manolo|WORKGROUP", if the domain is empty it will be "pepe|"
    for (i = 0; i < array_size; i++)
    {
      node = json_array_get_element (array, i);
      username = json_node_get_string (node);

      g_string_append_printf (gstr, "%s,", username);
    }

    /* remove last ',' */
    g_string_truncate (gstr, gstr->len - 1);

    ret = g_string_free (gstr, FALSE);
  }

  g_object_unref (parser);

  return ret;
}

static void
sim_idm_entry_load_host_source_reference (SimDatabase *database)
{
  GdaDataModel *dm;
  gchar *query;
  const GValue *value;
  gint i, rows;
  gint source_id, relevance;

  g_return_if_fail (SIM_IS_DATABASE (database));

  query = "SELECT id, relevance FROM host_source_reference";

  dm = sim_database_execute_single_command (database, query);
  if (dm)
  {
    host_source_reference = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, NULL);

    rows = gda_data_model_get_n_rows (dm);

    for (i = 0; i < rows; i++)
    {
      GValue smallint = { 0, {{0}, {0}} };

      value = gda_data_model_get_value_at (dm, 0, i, NULL);
      source_id = g_value_get_int (value);

      value = gda_data_model_get_value_at (dm, 1, i, NULL);
      // FIXME: temporal fix until GDA reads smallints properly
      g_value_init (&smallint, GDA_TYPE_SHORT);
      g_value_transform (value, &smallint);
      relevance = gda_value_get_short (&smallint);

      g_hash_table_insert (host_source_reference, GINT_TO_POINTER (source_id), GINT_TO_POINTER (relevance));
    }
  }
  else
    g_message ("HOST SOURCE REFERENCE DATA MODEL ERROR");
}

static gint
sim_idm_entry_get_relevance (gint source_id)
{
  // In the database source_id 0 is reserved for UNKNOWN
  // This means that when the SimCommand doesn't come with 'inventory_source'
  // we will return the relevance associated with UNKNOWN
  return GPOINTER_TO_INT (g_hash_table_lookup (host_source_reference, GINT_TO_POINTER (source_id)));
}

static void
sim_idm_service_entry_free (SimIdmServiceEntry *entry)
{
  g_free (entry->service);
  g_free (entry->version);
  g_free (entry);
}
