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

#include "sim-idm.h"

#include <unistd.h>
#include <string.h>
#include <glib.h>
#include <gio/gio.h>

#include "os-sim.h"
#include "sim-idm-entry.h"
#include "sim-xml-config.h"
#include "sim-xml-directive.h"
#include "sim-db-command.h"
#include "sim-server.h"
#include "sim-command.h"
#include "sim-context.h"
#include "sim-sensor.h"
#include "sim-network.h"
#include "sim-event.h"
#include "sim-inet.h"
#include "sim-util.h"
#include "sim-uuid.h"
#include "sim-session.h"

SimMain ossim;
SimCmdArgs    simCmdArgs;

typedef struct _SimIdmContextInfo
{
  SimContext *context;
  GStaticRecMutex     context_mutex;
  // This cond + mutex is just used for the initialization of sim_idm_snapshot_run thread
  GCond      *snapshot_cond;
  GMutex     *snapshot_mutex;
  // WARNING: the agent and the IDM cannot know if two different IPs belongs to
  //          the same host. So at the moment we consider that every IP belongs
  //          to an unique host.
  GHashTable *index_host_id;
  GHashTable *index_mac;
  GHashTable *index_ip;

} SimIdmContextInfo;

static gchar *  sim_idm_new_hostname_ip (SimInet *host_ip);
static void sim_idm_context_info_load (SimContext *context);
static void sim_idm_context_info_reload (void);

/* INVENTORY DATA STORE */
static SimIdmContextInfo *context_info_store;

/* AUTOMATIC HOSTNAME CHECK */
static GRegex* autohostname;

// DEBUG CODE /////////////////////
//static SimUuid *uuid_const[10];
//static gint uuid_count = 0;
///////////////////////////////////

void
sim_idm_put (SimSensor *sensor, SimCommand *command)
{
  g_return_if_fail(SIM_IS_COMMAND (command));
  g_return_if_fail(command->type == SIM_COMMAND_TYPE_IDM_EVENT);

  if (!command->data.idm_event.ip && !command->data.idm_event.host_id)
  {
    g_message ("%s: discarding idm event without ip or host_id", __func__);
    return;
  }

  sim_idm_process (sensor, command);
}

void
sim_idm_process (SimSensor *sensor, SimCommand *command)
{
  SimIdmEntry *entry_new, *entry_old, *entry_old_mac;
  SimIdmEntryChanges changes = { FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE };
  SimIdmEntryChanges zero_changes = { FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE };

  SimInet *inet_new;
  SimUuid *host_id_new;
  gboolean create_host = FALSE;
  guint delete_old_ip = 0;
  gchar *delete_old_mac = NULL;

  GPtrArray *interface_list;

  entry_new = sim_idm_entry_new_from_command (command);

  inet_new = sim_idm_entry_get_ip (entry_new);
  host_id_new = sim_idm_entry_get_host_id (entry_new);

  g_static_rec_mutex_lock (&context_info_store->context_mutex);

  /* inventory */

  if (!sim_context_is_inet_in_homenet (context_info_store->context, inet_new))
  {
    gchar *aux_cmd = sim_command_get_string (command);
    g_message ("Dropping event because it is not in homenet: %s", aux_cmd);
    g_free (aux_cmd);
    goto exit;
  }

  /* IP/MAC index processing */
  entry_old = g_hash_table_lookup (context_info_store->index_ip, GUINT_TO_POINTER (sim_inet_hash (sim_idm_entry_get_ip (entry_new))));

  if (entry_old)
  {
    // KNOWN IP

    if (sim_idm_entry_get_mac (entry_new))
    {
      // THERE IS MAC
      entry_old_mac = g_hash_table_lookup (context_info_store->index_mac, sim_idm_entry_get_mac (entry_new));
      if (entry_old_mac)
      {
        if (entry_old != entry_old_mac)
        {
          gchar *ip1, *ip2;
          gchar *aux_cmd;

          ip1 = sim_inet_get_canonical_name (sim_idm_entry_get_ip (entry_old));
          ip2 = sim_inet_get_canonical_name (sim_idm_entry_get_ip (entry_old_mac));
          aux_cmd = sim_command_get_string (command);
          g_message ("Dropping event because of conflicting MAC %s between hosts with IP %s and IP %s: %s", sim_idm_entry_get_mac (entry_new), ip1, ip2, aux_cmd);
          g_free (aux_cmd);
          g_free (ip2);
          g_free (ip1);

          goto exit;
        }

        // KNOWN MAC (DHCP change)
        if (!sim_inet_equal (sim_idm_entry_get_ip (entry_old), sim_idm_entry_get_ip (entry_old_mac)))
          delete_old_ip = sim_inet_hash (sim_idm_entry_get_ip (entry_old));

        sim_idm_entry_merge (entry_old, entry_new, &changes, command->context_id, sensor);
      }
      else
      {
        // UNKNOWN MAC
        delete_old_mac = g_strdup (sim_idm_entry_get_mac (entry_old));

        sim_idm_entry_merge (entry_old, entry_new, &changes, command->context_id, sensor);
      }
    }
    else
    {
      // THERE IS NO MAC
      sim_idm_entry_merge (entry_old, entry_new, &changes, command->context_id, sensor);
    }
  }
  else
  {
    // UNKNOWN IP
    if (sim_idm_entry_get_mac (entry_new))
    {
      // THERE IS MAC
      entry_old_mac = g_hash_table_lookup (context_info_store->index_mac, sim_idm_entry_get_mac (entry_new));
      if (entry_old_mac)
      {
        // KNOWN MAC (DHCP change)
        const gchar *hostname_old;
        gchar *tmp_hostname;

        delete_old_ip = sim_inet_hash (sim_idm_entry_get_ip (entry_old_mac));

        hostname_old = sim_idm_entry_get_hostname (entry_old_mac);
        if (!sim_idm_entry_get_hostname (entry_new) && hostname_old && g_regex_match (autohostname, hostname_old, 0, NULL))
        {
          tmp_hostname = sim_idm_new_hostname_ip (sim_idm_entry_get_ip (entry_new));
          sim_idm_entry_set_hostname (entry_new, tmp_hostname);
          g_free (tmp_hostname);
        }

        sim_idm_entry_merge (entry_old_mac, entry_new, &changes, command->context_id, sensor);
        entry_old = entry_old_mac;
      }
      else
      {
        // UNKNOWN MAC
        create_host = TRUE;
      }
    }
    else
    {
      // THERE IS NO MAC
      create_host = TRUE;
    }
  }

  if (create_host)
  {
    SimHost *new_host;

    if (host_id_new)
      sim_idm_entry_set_host_id (entry_new, host_id_new);
    else
    {
      host_id_new = sim_uuid_new ();
      sim_idm_entry_set_host_id (entry_new, host_id_new);
      g_object_unref (host_id_new);
    }

    changes.host_id = TRUE;
    changes.ip = ! !(sim_idm_entry_get_ip (entry_new));
    changes.username = ! !(sim_idm_entry_get_username (entry_new));
    if (!sim_idm_entry_get_hostname (entry_new))
    {
      gchar *tmp_hostname;

      tmp_hostname = sim_idm_new_hostname_ip (sim_idm_entry_get_ip (entry_new));
      sim_idm_entry_set_hostname (entry_new, tmp_hostname);
      g_free (tmp_hostname);
    }

    changes.hostname = TRUE;
    changes.fqdns    = !! (sim_idm_entry_get_fqdns (entry_new));
    changes.mac      = !! (sim_idm_entry_get_mac (entry_new));
    changes.os       = !! (sim_idm_entry_get_os (entry_new));
    changes.cpu      = !! (sim_idm_entry_get_cpu (entry_new));
    changes.memory   = !! (sim_idm_entry_get_memory (entry_new));
    changes.video    = !! (sim_idm_entry_get_video (entry_new));
    changes.service  = !! (sim_idm_entry_get_service (entry_new));
    changes.software = !! (sim_idm_entry_get_software (entry_new));
    changes.state    = !! (sim_idm_entry_get_state (entry_new));

    // DEBUG CODE /////////////////////
    //sim_idm_entry_set_host_id (entry_new, uuid_const[uuid_count++]);
    ///////////////////////////////////

    if (g_hash_table_lookup (context_info_store->index_host_id, sim_idm_entry_get_host_id (entry_new)))
      g_message ("Warning there has been a collision in the host UUID creation");

    interface_list = g_ptr_array_new_with_free_func ((GDestroyNotify) g_object_unref);
    g_ptr_array_add (interface_list, g_object_ref (entry_new));
    g_hash_table_insert (context_info_store->index_host_id, g_object_ref (host_id_new), interface_list);

    entry_old = entry_new;

    new_host = sim_host_new (sim_idm_entry_get_ip (entry_old), sim_idm_entry_get_host_id (entry_old), sim_idm_entry_get_hostname (entry_old), DEFAULT_ASSET, 0, 0);
    sim_context_append_host (context_info_store->context, new_host);
  }

  if (changes.ip)
    g_hash_table_insert (context_info_store->index_ip, GUINT_TO_POINTER (sim_inet_hash (sim_idm_entry_get_ip (entry_new))), g_object_ref (entry_old));
  if (changes.mac)
    g_hash_table_insert (context_info_store->index_mac, g_strdup (sim_idm_entry_get_mac (entry_old)), g_object_ref (entry_old));

  if (delete_old_ip)
    g_hash_table_remove (context_info_store->index_ip, GUINT_TO_POINTER (delete_old_ip));
  if (delete_old_mac)
  {
    g_hash_table_remove (context_info_store->index_mac, delete_old_mac);
    g_free (delete_old_mac);
  }

  // Propagate host changes to other entries
  if (changes.username || changes.hostname || changes.fqdns || changes.os || changes.cpu || changes.memory || changes.video || changes.software || changes.state)
  {
    guint i;

    interface_list = g_hash_table_lookup (context_info_store->index_host_id, sim_idm_entry_get_host_id (entry_old));
    if (interface_list->len > 1)
    {
      for (i = 0; i < interface_list->len; i++)
      {
        SimIdmEntry *entry_old_by_uuid = g_ptr_array_index (interface_list, i);

        if (!sim_inet_equal (sim_idm_entry_get_ip (entry_old_by_uuid), sim_idm_entry_get_ip (entry_old)))
          sim_idm_entry_propagate_host (entry_old_by_uuid, entry_old, &changes);
      }
    }
  }

  if (memcmp (&zero_changes, &changes, sizeof (SimIdmEntryChanges)))
  {
    // When the entries are restored from the database they don't have
    // a 'source_id', so just to be sure that it is setted we override it
    sim_idm_entry_set_source_id (entry_old, sim_idm_entry_get_source_id (entry_new));

    sim_db_update_host_properties (ossim.dbossim, sim_context_get_id (context_info_store->context), sim_sensor_get_id (sensor), entry_old, &changes, delete_old_ip);
  }

exit:

  g_static_rec_mutex_unlock (&context_info_store->context_mutex);

  g_object_unref (entry_new);
}

SimIdmEntry *
sim_idm_get (SimUuid *context_id, SimInet *ip)
{
  SimIdmEntry *entry;

  // unused parameter
  (void) context_id;

  g_static_rec_mutex_lock (&context_info_store->context_mutex);

  entry = g_hash_table_lookup (context_info_store->index_ip, GUINT_TO_POINTER (sim_inet_hash (ip)));
  if (entry)
    g_object_ref (entry);
  else
  {
    entry = NULL;
  }

  g_static_rec_mutex_unlock (&context_info_store->context_mutex);

  return entry;
}

static gchar *
sim_idm_new_hostname_ip (SimInet *host_ip)
{
  gchar *ip, *ret;
  size_t i, ip_len;

  ip = sim_inet_get_canonical_name (host_ip);
  ip_len = strlen (ip);
  for (i = 0; i < ip_len; i++)
    if (ip[i] == '.')
      ip[i] = '-';

  ret = g_strdup_printf ("Host-%s", ip);

  g_free (ip);

  return ret;
}

void
sim_idm_context_init (void)
{
  SimContext *context;

  autohostname = g_regex_new ("Host-([0-9]{1,3}-){3}[0-9]{1,3}", 0, 0, NULL);

  context = sim_container_get_context (ossim.container, NULL);
  sim_idm_context_info_load (context);
}

static void
sim_idm_context_info_load (SimContext *context)
{
  context_info_store = g_new0 (SimIdmContextInfo, 1);
  context_info_store->context = g_object_ref (context);
  g_static_rec_mutex_init (&context_info_store->context_mutex);
  context_info_store->snapshot_cond = g_cond_new ();
  context_info_store->snapshot_mutex = g_mutex_new ();

  sim_idm_context_info_reload ();
}

static void
sim_idm_context_info_reload (void)
{
  SimUuid *ctx_id;

  g_static_rec_mutex_lock (&context_info_store->context_mutex);

  if (context_info_store->index_host_id)
    g_hash_table_unref (context_info_store->index_host_id);
  if (context_info_store->index_mac)
    g_hash_table_unref (context_info_store->index_mac);
  if (context_info_store->index_ip)
    g_hash_table_unref (context_info_store->index_ip);

  context_info_store->index_mac = g_hash_table_new_full (g_str_hash, g_str_equal, g_free, g_object_unref);
  context_info_store->index_ip = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, g_object_unref);

  ctx_id = sim_context_get_id (context_info_store->context);

  sim_context_reload_nets (context_info_store->context);

  // Load known info about the hosts
  context_info_store->index_host_id = sim_db_load_idm_entries (ossim.dbossim, ctx_id);
  SIM_WHILE_HASH_TABLE (context_info_store->index_host_id)
  {
    SimIdmEntry *entry_old;
    GPtrArray *interface_list;
    guint i;
    const gchar *mac_old;
    SimInet *ip_old;

    interface_list = value;

    for (i = 0; i < interface_list->len; i++)
    {
      entry_old = interface_list->pdata[i];

      mac_old = sim_idm_entry_get_mac (entry_old);
      if (mac_old)
        g_hash_table_insert (context_info_store->index_mac, g_strdup (mac_old), g_object_ref (entry_old));
      ip_old = sim_idm_entry_get_ip (entry_old);
      if (ip_old)
        g_hash_table_insert (context_info_store->index_ip, GUINT_TO_POINTER (sim_inet_hash (ip_old)), g_object_ref (entry_old));
    }
  }

  g_static_rec_mutex_unlock (&context_info_store->context_mutex);
}
