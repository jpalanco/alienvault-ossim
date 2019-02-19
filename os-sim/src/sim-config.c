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

#include <gnet.h>
#include <stdlib.h>

#include "os-sim.h"
#include "sim-event.h"
#include "sim-container.h"
#include "sim-uuid.h"
#include "sim-db-command.h"

#include "sim-config.h"

extern SimMain  ossim; //needed to be able to access to ossim.dbossim directly in sim_config_set_data_role()

enum
{
  DESTROY,
  LAST_SIGNAL
};

static gpointer parent_class = NULL;
/* we don't use signals */
//static gint sim_config_signals[LAST_SIGNAL] = { 0 };


static void         sim_config_ds_dsn_split                   (const gchar  *dsn_string,
                                                               gchar       **out_port,
                                                               gchar       **out_username,
                                                               gchar       **out_password,
                                                               gchar       **out_database,
                                                               gchar       **out_host,
                                                               gchar       **out_unix_socket);


/* GType Functions */

static void
sim_config_impl_dispose (GObject  *gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void
sim_config_impl_finalize (GObject  *gobject)
{
  SimConfig  *config = (SimConfig *) gobject;
  GList      *list;

  list = config->datasources;
  while (list)
  {
    SimConfigDS *ds = (SimConfigDS *) list->data;
    sim_config_ds_free (ds);
    list = list->next;
  }

  list = config->notifies;
  while (list)
  {
    SimConfigNotify *notify = (SimConfigNotify *) list->data;
    sim_config_notify_free (notify);
    list = list->next;
  }

  if (config->component_id)
    g_object_unref (config->component_id);

  if (config->default_context_id)
    g_object_unref (config->default_context_id);

  if (config->default_engine_id)
    g_object_unref (config->default_engine_id);

  if (config->log.filename)
    g_free (config->log.filename);

  if (config->directive.filename)
    g_free (config->directive.filename);

  if (config->reputation.filename)
    g_free (config->reputation.filename);

  if (config->server.id)
    g_object_unref (config->server.id);

  if (config->server.name)
    g_free (config->server.name);

  if (config->server.ip)
    g_free (config->server.ip);

  if (config->server.role)
    sim_role_unref (config->server.role);

  if (config->notify_prog)
    g_free (config->notify_prog);

  if (config->server.HA_ip)
    g_free (config->server.HA_ip);

  if (config->smtp.host)
    g_free (config->smtp.host);

  if (config->framework.name)
    g_free (config->framework.name);

  if (config->framework.host)
    g_free (config->framework.host);

  //Forensic Storage
  if (config->forensic_storage.path)
    g_free (config->forensic_storage.path);

  if (config->forensic_storage.sig_type)
    g_free (config->forensic_storage.sig_type);

  if (config->forensic_storage.sig_cipher)
    g_free (config->forensic_storage.sig_cipher);

  if (config->forensic_storage.enc_type)
    g_free (config->forensic_storage.enc_type);

  if (config->forensic_storage.enc_cipher)
    g_free (config->forensic_storage.enc_cipher);

  if (config->forensic_storage.key_source)
    g_free (config->forensic_storage.key_source);

  if (config->forensic_storage.sig_prv_key_path)
    g_free (config->forensic_storage.sig_prv_key_path);

  if (config->forensic_storage.sig_pub_key_path)
    g_free (config->forensic_storage.sig_pub_key_path);

  if (config->forensic_storage.enc_prv_key_path)
    g_free (config->forensic_storage.enc_prv_key_path);

  if (config->forensic_storage.enc_pass)
    g_free (config->forensic_storage.enc_pass);

  if (config->forensic_storage.enc_pub_key_path)
    g_free (config->forensic_storage.enc_pub_key_path);
  if (config->forensic_storage.enc_cert_path)
    g_free (config->forensic_storage.enc_cert_path);


  if (config->idm.ip)
    g_free (config->idm.ip);

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_config_class_init (SimConfigClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_peek_parent (class);

  object_class->dispose = sim_config_impl_dispose;
  object_class->finalize = sim_config_impl_finalize;
}

static void
sim_config_instance_init (SimConfig *config)
{
  config->log.filename = NULL;

  config->datasources = NULL;
  config->notifies = NULL;

  config->component_id = NULL;
  config->default_context_id = NULL;
  config->default_engine_id = NULL;

  config->notify_prog = NULL;

  config->directive.filename = NULL;

  config->server.id = NULL;
  config->server.name = NULL;
  config->server.ip = NULL;
  config->server.port = 0;
  config->server.role = sim_role_new ();

  config->smtp.host = NULL;
  config->smtp.port = 0;

  config->framework.name = NULL;
  config->framework.host = NULL;
  config->framework.port = 0;

  config->forensic_storage.path = NULL;
  config->forensic_storage.sig_type = NULL;
  config->forensic_storage.sig_cipher = NULL;
  config->forensic_storage.sig_bit = 0;
  config->forensic_storage.enc_type = NULL;
  config->forensic_storage.enc_cipher = NULL;
  config->forensic_storage.enc_bit = 0;
  config->forensic_storage.key_source = NULL;
  config->forensic_storage.sig_prv_key_path = NULL;
  config->forensic_storage.sig_pass = NULL;
  config->forensic_storage.sig_pub_key_path = NULL;
  config->forensic_storage.enc_prv_key_path = NULL;
  config->forensic_storage.enc_pass = NULL;
  config->forensic_storage.enc_pub_key_path = NULL;
  config->forensic_storage.enc_cert_path = NULL;

  config->idm.activated = FALSE;
  config->idm.mssp = TRUE;
  config->idm.ip = NULL;
  config->idm.port = 0;
}

/* Public Methods */

GType
sim_config_get_type (void)
{
  static GType object_type = 0;

  if (!object_type)
  {
    static const GTypeInfo type_info = {
      sizeof (SimConfigClass),
      NULL,
      NULL,
      (GClassInitFunc) sim_config_class_init,
      NULL,
      NULL,                       /* class data */
      sizeof (SimConfig),
      0,                          /* number of pre-allocs */
      (GInstanceInitFunc) sim_config_instance_init,
      NULL                        /* value table */
    };

    object_type = g_type_register_static (G_TYPE_OBJECT, "SimConfig", &type_info, 0);
  }

  return object_type;
}

/*
 *
 *
 *
 */
SimConfig*
sim_config_new (void)
{
  SimConfig *config = NULL;

  config = SIM_CONFIG (g_object_new (SIM_TYPE_CONFIG, NULL));

  return config;
}

/*
 *
 *
 *
 */
SimConfigDS*
sim_config_ds_new (void)
{
  SimConfigDS *ds;

  ds = g_new0 (SimConfigDS, 1);

  ds->name = NULL;
  ds->provider = NULL;
  ds->dsn = NULL;
  ds->local_DB = TRUE;
  ds->rserver_name = NULL;

  // DSN Splitted
  ds->port = NULL;
  ds->username = NULL;
  ds->password = NULL;
  ds->database = NULL;
  ds->host = NULL;
  ds->unix_socket = NULL;

  return ds;
}

/*
 *
 *
 *
 */

SimConfigDS*
sim_config_ds_clone (SimConfigDS* ds)
{
  SimConfigDS *dsnew;

  g_return_val_if_fail (ds,NULL);

  dsnew=sim_config_ds_new();
  dsnew->name=g_strdup(ds->name);
  dsnew->provider=g_strdup(ds->provider);
  dsnew->dsn=g_strdup(ds->dsn);
  dsnew->local_DB=ds->local_DB;
  dsnew->rserver_name=g_strdup(ds->rserver_name);

  if (ds->port)
    dsnew->port = g_strdup (ds->port);
  if (ds->username)
    dsnew->username = g_strdup (ds->username);
  if (ds->password)
    dsnew->password = g_strdup (ds->password);
  if (ds->database)
    dsnew->database = g_strdup (ds->database);
  if (ds->host)
    dsnew->host = g_strdup (ds->host);
  if (ds->unix_socket)
    dsnew->unix_socket = g_strdup (ds->unix_socket);

  return dsnew;
}

/*
 *
 *
 *
 */
void
sim_config_ds_free (SimConfigDS *ds)
{
  g_return_if_fail (ds);

  if (ds->name)
    g_free (ds->name);
  if (ds->provider)
    g_free (ds->provider);
  if (ds->dsn)
    g_free (ds->dsn);
  if (ds->rserver_name)
    g_free (ds->rserver_name);

  // DSN splitted
  if (ds->port)
    g_free (ds->port);
  if (ds->username)
    g_free (ds->username);
  if (ds->password)
    g_free (ds->password);
  if (ds->database)
    g_free (ds->database);
  if (ds->host)
    g_free (ds->host);
  if (ds->unix_socket)
    g_free (ds->unix_socket);

  g_free (ds);
}

void
sim_config_ds_set_dsn_string (SimConfigDS *ds,
                              gchar       *dsn_string)
{
  ds->dsn = dsn_string;

  sim_config_ds_dsn_split (dsn_string,
                           &ds->port,
                           &ds->username,
                           &ds->password,
                           &ds->database,
                           &ds->host,
                           &ds->unix_socket);
}


/*
 * This function doesn't returns anything, it stores directly the data into config parameter.
 */
void
sim_config_set_data_role (SimConfig   *config,
                          SimCommand  *cmd)
{
  g_return_if_fail (SIM_IS_CONFIG (config));
  g_return_if_fail (SIM_IS_COMMAND (cmd));

  sim_role_set_correlate (config->server.role, cmd->data.server_set_data_role.correlate);
  sim_role_set_cross_correlate (config->server.role, cmd->data.server_set_data_role.cross_correlate);
  sim_role_set_reputation (config->server.role, cmd->data.server_set_data_role.reputation);
  sim_role_set_store (config->server.role, cmd->data.server_set_data_role.store);
  sim_role_set_qualify (config->server.role, cmd->data.server_set_data_role.qualify);

  //Also store in DB the configuration
  gchar *query;
  query = g_strdup_printf ("REPLACE INTO server_role (name, correlate, cross_correlate, reputation, store, qualify)"
                           " VALUES ('%s', %d, %d, %d, %d, %d)",
                           cmd->data.server_set_data_role.servername ? cmd->data.server_set_data_role.servername : "",
                           cmd->data.server_set_data_role.correlate,
                           cmd->data.server_set_data_role.cross_correlate,
                           cmd->data.server_set_data_role.reputation,
                           cmd->data.server_set_data_role.store,
                           cmd->data.server_set_data_role.qualify);

  sim_database_execute_no_query (ossim.dbossim, query);
  g_free (query);

}

/*
 *
 *
 *
 */
SimConfigDS*
sim_config_get_ds_by_name (SimConfig    *config,
                           const gchar  *name)
{
  GList  *list;

  g_return_val_if_fail (config, NULL);
  g_return_val_if_fail (SIM_IS_CONFIG (config), NULL);
  g_return_val_if_fail (name, NULL);

  list = config->datasources;
  while (list)
  {
    SimConfigDS *ds = (SimConfigDS *) list->data;

    if (!g_ascii_strcasecmp (ds->name, name))
      return ds;

    list = list->next;
  }

  return NULL;
}

/*
 *
 *
 *
 */
SimConfigNotify*
sim_config_notify_new (void)
{
  SimConfigNotify *notify;

  notify = g_new0 (SimConfigNotify, 1);
  notify->emails = NULL;
  notify->alarm_risks = NULL;

  return notify;
}

/*
 *
 *
 *
 */
void
sim_config_notify_free (SimConfigNotify *notify)
{
  GList *list;

  g_return_if_fail (notify);

  if (notify->emails)
    g_free (notify->emails);

  list = notify->alarm_risks;
  while (list)
  {
    gint *level = (gint *) list->data;
    g_free (level);
    list = list->next;
  }

  g_free (notify);
}

SimRole *
sim_config_get_server_role (SimConfig *config)
{
  g_return_val_if_fail (SIM_IS_CONFIG (config), NULL);

  if (config->server.role)
    sim_role_ref (config->server.role);

  return config->server.role;
}

void
sim_config_set_server_role (SimConfig *config,
                            SimRole   *role)
{
  g_return_if_fail (SIM_IS_CONFIG (config));
  g_return_if_fail (SIM_IS_ROLE (role));

  if (config->server.role)
    sim_role_unref (config->server.role);

  config->server.role = sim_role_ref (role);
}

/**
 * sim_config_load_server_ids
 * @config: a #SimConfig
 * @database: the #SimDatabase
 * @component_id_file: a file containing this component id.
 *
 * Loads 'server', 'component', 'default_context', and 'default_engine' ids
 * in @config from config table and the alienvault center file.
 *
 * Returns %TRUE if loads all ids correclty or %FALSE otherwise
 */
gboolean
sim_config_load_server_ids (SimConfig   *config,
                            SimDatabase *database)
{
  gboolean success = TRUE;

#ifndef SIM_USE_LICENSE
  gchar * system_id = NULL;
  const gchar * avsystem_uuid_cmd = "sudo /usr/bin/alienvault-system-id | tr -d '\n'";
#endif

  g_return_val_if_fail (SIM_IS_CONFIG (config), FALSE);
  g_return_val_if_fail (SIM_IS_DATABASE (database), FALSE);

  // Load the local server id
  config->server.id = sim_db_get_config_uuid (ossim.dbossim, "server_id");
  if (!config->server.id)
    success = FALSE;

  // Load component id.
#ifdef SIM_USE_LICENSE
  if (!(ossim.system_id))
    success = FALSE;
  else
    config->component_id = sim_uuid_new_from_uuid (&ossim.system_id);
#else
  system_id = g_new (gchar, 37);
  if ((success = g_spawn_command_line_sync (avsystem_uuid_cmd, &system_id, NULL, NULL, NULL)))
  {
    system_id[36] = '\0';
    config->component_id = sim_uuid_new_from_string (system_id);
    g_free (system_id);
  }
#endif

  // Load default_context
  config->default_context_id = sim_db_get_config_uuid (ossim.dbossim, "default_context_id");
  if (!config->default_context_id)
    success = FALSE;

  // Load default_engine
  config->default_engine_id = sim_db_get_config_uuid (ossim.dbossim, "default_engine_id");
  if (!config->default_engine_id)
    success = FALSE;

  return success;
}

/**
 * sim_config_ds_dsn_split:
 * @dns_string: a dns string
 * @out_cnc_params: a place to store the new string containing the &lt;connection_params&gt; part
 * @out_provider: a place to store the new string containing the &lt;provider&gt; part
 * @out_username: a place to store the new string containing the &lt;username&gt; part
 * @out_password: a place to store the new string containing the &lt;password&gt; part
 *
 * Extract the provider, connection parameters, username and password from @string.
 * in @string, the various parts are strings
 * which are expected to be encoded using an RFC 1738 compliant encoding. If they are specified,
 * the returned provider, username and password strings are correctly decoded.
 *
 */
static void
sim_config_ds_dsn_split (const gchar  *dsn_string,
                         gchar       **out_port,
                         gchar       **out_username,
                         gchar       **out_password,
                         gchar       **out_database,
                         gchar       **out_host,
                         gchar       **out_unix_socket)
{
  gint i;
  gchar **dsn_split;
  gchar **token_split;
  GHashTable *token_hash;

	g_return_if_fail (dsn_string);

	g_return_if_fail (out_port);
	g_return_if_fail (out_username);
	g_return_if_fail (out_password);
	g_return_if_fail (out_database);
	g_return_if_fail (out_host);
	g_return_if_fail (out_unix_socket);

	*out_port = NULL;
	*out_username = NULL;
	*out_password = NULL;
	*out_database = NULL;
	*out_host = NULL;
	*out_unix_socket = NULL;

  token_hash = g_hash_table_new_full (g_str_hash, g_str_equal, g_free, NULL);

  dsn_split = g_strsplit (dsn_string, ";", 0);
  for (i = 0; dsn_split[i] != NULL; i++)
  {
    token_split = g_strsplit (dsn_split[i], "=", 0);
    if (token_split[0])
    {
      g_hash_table_insert (token_hash, g_strdup(token_split[0]), g_strdup(token_split[1] ? token_split[1] : ""));
    }
    g_strfreev(token_split);
  }

  g_strfreev (dsn_split);

  *out_port = g_hash_table_lookup (token_hash, "PORT");
  *out_username = g_hash_table_lookup (token_hash, "USERNAME");
  *out_password = g_hash_table_lookup (token_hash, "PASSWORD");
  *out_database = g_hash_table_lookup (token_hash, "DB_NAME");
  *out_host = g_hash_table_lookup (token_hash, "HOST");
  *out_unix_socket = g_hash_table_lookup (token_hash, "UNIX_SOCKET");

  g_hash_table_destroy (token_hash);
}

// vim: set tabstop=2:

