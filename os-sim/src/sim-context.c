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

#include "sim-context.h"

#include <glib.h>
#include <uuid/uuid.h>
#include <string.h>
#include <errno.h>

#include "sim-object.h"
#include "os-sim.h"
#include "sim-host.h"
#include "sim-network.h"
#include "sim-policy.h"
#include "sim-directive.h"
#include "sim-xml-directive.h"
#include "sim-util.h"
#include "sim-db-command.h"
#include "sim-log.h"
#include "sim-uuid.h"
#include "sim-engine.h"

extern SimMain ossim;

struct _SimContextPrivate
{
  SimUuid        *id;               // Mssp Context id
  gchar          *utf8_name;

  SimDatabase    *database;         // SimDatabase for load context data

  GHashTable     *plugins;          // Plugin ids
  GHashTable     *plugin_sids;      // Plugin sids

  GHashTable     *hosts;            // Hash table to the Host list
  GHashTable    * host_ids;         // Hash table for host ids.
  GHashTable    * plugin_references; // Hash table for plugin_reference.
  GHashTable     *host_plugin_sids; // SimPluginSids Hash Table

  GHashTable    * nets;             // SimNet HashTable (key = net name)
  GHashTable    * net_ids;          // SimNet hashtable (key = uuid)
  SimNetwork     *home_net;         // SimNetwork with internal inets
  SimNetwork     *all_nets;         // SimNetwork with all inets

  GList          *policies;         // Policies list

  guint           total_events;     // Total of events received
  guint           last_total;       // Last Total events for eps calc
  glong           elapsed_5_minutes;
  guint           total_5_minutes;

  GHashTable       *taxonomy_products;

  /* Mutex */
  GStaticRWLock   sem_policies;
  GMutex         *mutex_plugins;
  GMutex         *mutex_plugin_sids;
  GMutex         *mutex_hosts_nets;
  GStaticRWLock   mutex_host_plugin_sids;
};

#define SIM_CONTEXT_GET_PRIVATE(self) (G_TYPE_INSTANCE_GET_PRIVATE ((self), SIM_TYPE_CONTEXT, SimContextPrivate))

SIM_DEFINE_TYPE (SimContext, sim_context, G_TYPE_OBJECT, NULL)

/* Prototypes */

static gint     sim_context_load_plugins                      (SimContext     *context);
static gint     sim_context_load_plugin_sids                  (SimContext     *context);
static void     sim_context_load_taxonomy_products            (SimContext     *context);
static gint     sim_context_load_hosts                        (SimContext     *context);
static gint     sim_context_load_nets                         (SimContext     *context);
static gint     sim_context_load_plugin_references            (SimContext    * context);
static gint     sim_context_load_host_plugin_sids             (SimContext     *context);
static gint     sim_context_load_policies                     (SimContext     *context);
static void     sim_context_append_net                        (SimContext     *context,
                                                               SimNet         *net);
static gboolean sim_context_has_inet_in_nets                  (SimContext     *context,
                                                               SimInet        *inet);
static void     sim_context_append_host_ul                    (SimContext     *context,
                                                               SimHost        *host);
static void     sim_context_host_plugin_sid_free              (gpointer        data);

/**
 * sim_context_class_init:
 * @klass: Pointer to SimContextClass
 *
 * Init the class struct
 */
static void
sim_context_class_init (SimContextClass *klass)
{
  GObjectClass *selfclass = G_OBJECT_CLASS (klass);

  g_type_class_add_private (klass, sizeof (SimContextPrivate));
  parent_class = g_type_class_ref (G_TYPE_OBJECT);

  selfclass->finalize = sim_context_finalize;
}

/**
 * sim_context_instance_init:
 * @self: a #SimContext
 *
 * This function initialice a instance of SimContext
 */
static void
sim_context_instance_init (SimContext *self)
{
  self->priv = SIM_CONTEXT_GET_PRIVATE (self);

  self->priv->id = NULL;

  self->priv->database = NULL;

  self->priv->plugins = g_hash_table_new_full (g_direct_hash, g_direct_equal,
                                               NULL, g_object_unref);
  self->priv->plugin_sids = g_hash_table_new_full (g_direct_hash, g_direct_equal,
                                                   NULL, g_object_unref);

  self->priv->hosts = g_hash_table_new_full (sim_inet_hash, sim_inet_equal,
                                             g_object_unref, g_object_unref);

  self->priv->host_ids = g_hash_table_new_full (sim_uuid_hash, sim_uuid_equal,
                                                g_object_unref, g_object_unref);

  self->priv->nets = g_hash_table_new_full (g_str_hash, g_str_equal,
                                            g_free, g_object_unref);

  self->priv->net_ids = g_hash_table_new_full (sim_uuid_hash, sim_uuid_equal,
                                               g_object_unref, g_object_unref);

  self->priv->home_net = sim_network_new ();
  self->priv->all_nets = sim_network_new ();

  self->priv->policies = NULL;

  self->priv->total_events = 0;
  self->priv->last_total = 0;

  self->priv->host_plugin_sids = g_hash_table_new_full (g_direct_hash, g_direct_equal,
                                                        NULL, (GDestroyNotify)sim_context_host_plugin_sid_free);

  self->priv->taxonomy_products = g_hash_table_new_full (g_direct_hash, g_direct_equal,
                                                         NULL, (GDestroyNotify) g_list_free);

  /* Mutex */
  g_static_rw_lock_init (&self->priv->sem_policies);

  self->priv->mutex_plugins = g_mutex_new ();
  self->priv->mutex_plugin_sids = g_mutex_new ();
  self->priv->mutex_hosts_nets = g_mutex_new ();
  g_static_rw_lock_init (&self->priv->mutex_host_plugin_sids);
}

/**
 * sim_context_finalize:
 * @self: a #SimContext
 *
 * This function finalize a instance of SimContext
 */
static void
sim_context_finalize (GObject *self)
{
  SimContextPrivate *priv = SIM_CONTEXT_GET_PRIVATE (self);

  if (priv->id)
  {
    g_object_unref (priv->id);
    priv->id = NULL;
  }

  if (priv->utf8_name)
  {
    g_free (priv->utf8_name);
    priv->utf8_name = NULL;
  }
  if (priv->plugins)
  {
    g_hash_table_unref (priv->plugins);
    priv->plugins = NULL;
  }
  if (priv->plugin_sids)
  {
    g_hash_table_unref (priv->plugin_sids);
    priv->plugin_sids = NULL;
  }
  if (priv->nets)
  {
    g_hash_table_unref (priv->nets);
    priv->nets = NULL;
  }

  if (priv->net_ids)
  {
    g_hash_table_unref (priv->net_ids);
    priv->net_ids = NULL;
  }

  if (priv->hosts)
  {
    g_hash_table_unref (priv->hosts);
    priv->hosts = NULL;
  }

  if (priv->host_ids)
  {
    g_hash_table_unref (priv->host_ids);
    priv->host_ids = NULL;
  }

  if (priv->home_net)
  {
    g_object_unref (priv->home_net);
    priv->home_net = NULL;
  }
  if (priv->all_nets)
  {
    g_object_unref (priv->all_nets);
    priv->home_net = NULL;
  }

  if (priv->host_plugin_sids)
  {
    g_hash_table_unref (priv->host_plugin_sids);
    priv->host_plugin_sids = NULL;
  }

  if (priv->policies)
  {
    g_list_foreach (priv->policies, (GFunc)g_object_unref, NULL);
    g_list_free (priv->policies);
    priv->policies = NULL;
  }

  if (priv->database)
  {
    g_object_unref (priv->database);
    priv->database = NULL;
  }

  if (priv->taxonomy_products)
  {
    g_hash_table_destroy (priv->taxonomy_products);
    priv->taxonomy_products = NULL;
  }

  g_static_rw_lock_free (&priv->sem_policies);

  g_mutex_free (priv->mutex_plugins);
  g_mutex_free (priv->mutex_plugin_sids);
  g_mutex_free (priv->mutex_hosts_nets);
  g_static_rw_lock_free (&priv->mutex_host_plugin_sids);

  G_OBJECT_CLASS (parent_class)->finalize (self);
}

/**
 * sim_context_new:
 * @id: #SimUuid context id
 *
 * Returns: new #SimContext object with @id
 */
SimContext *
sim_context_new (SimUuid *id)
{
  SimContext *context;

  context = sim_context_new_full (id, NULL, NULL);
  return context;
}

/**
 * sim_context_new_ful:
 * @id: guint context id
 * @name: const gchar* context name
 *
 * Returns: new #SimContext object with params
 */
SimContext *
sim_context_new_full (SimUuid     *id,
                      const gchar *name,
                      SimDatabase *database)
{
  SimContext *context;

  g_return_val_if_fail (SIM_IS_UUID (id), NULL);

  context = SIM_CONTEXT (g_object_new (SIM_TYPE_CONTEXT, NULL));

  context->priv->id = g_object_ref (id);

  if (name != NULL)
    context->priv->utf8_name = g_utf8_normalize (name, -1, G_NORMALIZE_DEFAULT);
  else
    context->priv->utf8_name = g_strdup ("");

  context->priv->database = database;

  return context;
}

/**
 * sim_context_get_id:
 * @context: a #SimContext object.
 *
 * Returns a pointer to the id of a context.
 */
SimUuid *
sim_context_get_id (SimContext * context)
{
  g_return_val_if_fail (SIM_IS_CONTEXT (context), NULL);

  return context->priv->id;
}

/**
 * sim_context_set_database:
 * @context: a #SimContext
 * @database: #SimDatabase object
 *
 * Sets @database for @context.
 */
void
sim_context_set_database (SimContext  *context,
                          SimDatabase *database)
{
  g_return_if_fail (SIM_IS_CONTEXT (context));
  g_return_if_fail (SIM_IS_DATABASE (database));

  if (context->priv->database)
    g_object_unref (context->priv->database);

  context->priv->database = g_object_ref (database);
}

/**
 * sim_context_get_name:
 *  @brief Return the context name in UTF-8 format
 *
 *  @param self Pointer to a correlation object
 */
gchar *
sim_context_get_name (SimContext *self)
{
  g_return_val_if_fail (SIM_IS_CONTEXT (self), NULL);

  return self->priv->utf8_name;
}

// Loaders

/**
 * sim_context_load_all:
 * @context: a #SimContext
 *
 * Load all context db data and directives
 */
void
sim_context_load_all (SimContext  *context)
{
  gint   loaded;
  gchar *name;

  g_return_if_fail (SIM_IS_CONTEXT (context));
  g_return_if_fail (SIM_IS_DATABASE (context->priv->database));

  name = context->priv->utf8_name;

  g_message ("Loading context %s '%s'", sim_uuid_get_string (context->priv->id), name);

  g_message ("Context '%s': Loading plugins", name);
  loaded = sim_context_load_plugins (context);
  g_message ("Context '%s': %d plugins loaded", name, loaded);

  g_message ("Context '%s': Loading plugin sids", name);
  loaded = sim_context_load_plugin_sids (context);
  g_message ("Context '%s': %d plugin sids loaded", name, loaded);

  sim_context_load_taxonomy_products (context);

  g_message ("Context '%s': Loading hosts", name);
  loaded = sim_context_load_hosts (context);
  g_message ("Context '%s': %d hosts loaded", name, loaded);

  g_message ("Context '%s': Loading plugin references", name);
  loaded = sim_context_load_plugin_references (context);
  g_message ("Context '%s': %d plugin references loaded", name, loaded);

  g_message ("Context '%s': Loading host_plugin_sids", name);
  loaded = sim_context_load_host_plugin_sids (context);
  g_message ("Context '%s': %d hosts plugin sids loaded", name, loaded);

  g_message ("Context '%s': Loading nets", name);
  loaded = sim_context_load_nets (context);
  g_message ("Context '%s': %d nets loaded", name, loaded);

  g_message ("Context '%s': Loading policies", name);
  loaded = sim_context_load_policies (context);
  g_message ("Context '%s': %d policies loaded", name, loaded);
}

/**
 * sim_context_external_load_all:
 * @context: a #SimContext
 *
 * Load all context db data and directives
 */
void
sim_context_external_load_all (SimContext *context)
{
  gint   loaded;
  gchar *name;

  g_return_if_fail (SIM_IS_CONTEXT (context));
  g_return_if_fail (SIM_IS_DATABASE (context->priv->database));

  name = context->priv->utf8_name;

  g_message ("Loading context %s '%s'", sim_uuid_get_string (context->priv->id), name);

  g_message ("Context '%s': Loading plugins", name);
  loaded = sim_context_load_plugins (context);
  g_message ("Context '%s': %d plugins loaded", name, loaded);

  g_message ("Context '%s': Loading plugin sids", name);
  loaded = sim_context_load_plugin_sids (context);
  g_message ("Context '%s': %d plugin sids loaded", name, loaded);

  g_message ("Context '%s': Loading hosts", name);
  loaded = sim_context_load_hosts (context);
  g_message ("Context '%s': %d hosts loaded", name, loaded);

  g_message ("Context '%s': Loading nets", name);
  loaded = sim_context_load_nets (context);
  g_message ("Context '%s': %d nets loaded", name, loaded);

  g_message ("Context '%s': Loading policies", name);
  loaded = sim_context_load_policies (context);
  g_message ("Context '%s': %d policies loaded", name, loaded);
}

/**
 * sim_context_reload_all:
 * @context: a #SimContext
 *
 * ReLoad all context db data and directives
 */
void
sim_context_reload_all (SimContext *context)
{
  gchar *name;

  g_return_if_fail (SIM_IS_CONTEXT (context));
  g_return_if_fail (SIM_IS_DATABASE (context->priv->database));

  name = context->priv->utf8_name;

  g_message ("Reloading context %s '%s'", sim_uuid_get_string (context->priv->id), name);

  g_message ("Context '%s': Reloading hosts", name);
  sim_context_reload_hosts (context);
  g_message ("Context '%s': Reloading plugin references", name);
  sim_context_load_plugin_references (context);
  g_message ("Context '%s': Reloading host_plugin_sids", name);
  sim_context_reload_host_plugin_sids (context);
  g_message ("Context '%s': Reloading nets", name);
  sim_context_reload_nets (context);

  g_message ("Context %s: Reloading policies", name);
  sim_context_reload_policies (context);
}

/*
 * Plugins
 */

/**
 * sim_context_get_plugin:
 * @context: a #SimContext
 * @id: plugin id number
 *
 * Search plugin with @id in @context
 *
 * Returns: #SimPlugin with @id if found in @context
 * or %NULL otherwise
 *
 * Thread safe
 */
SimPlugin *
sim_context_get_plugin (SimContext *context,
                        gint        id)
{
  SimPlugin *plugin = NULL;

  g_return_val_if_fail (SIM_IS_CONTEXT (context), NULL);
  g_return_val_if_fail (id > 0, NULL);

  g_mutex_lock (context->priv->mutex_plugins);

  plugin = g_hash_table_lookup (context->priv->plugins, GUINT_TO_POINTER (id));
  if (plugin)
    g_object_ref (plugin);

  g_mutex_unlock (context->priv->mutex_plugins);

  return plugin;
}

/**
 * sim_context_add_new_plugin:
 * @context: a #SimContext
 * @id: plugin id number
 *
 * Adds a new plugin identified by @id to the plugin hash table.
 *
 * Thread safe
 */
void
sim_context_add_new_plugin (SimContext * context,
                            gint         id)
{
  SimPlugin * plugin = sim_plugin_new ();
  gchar * name = g_strdup_printf ("Unknown plugin: %d", id);

  sim_plugin_set_id (plugin, id);
  sim_plugin_set_name (plugin, name);

  g_mutex_lock (context->priv->mutex_plugins);
  g_hash_table_insert (context->priv->plugins, GINT_TO_POINTER (id), plugin);
  g_mutex_unlock (context->priv->mutex_plugins);

  return;
}

/**
 * sim_context_load_plugins:
 * @context: a #SimContext
 *
 * Load plugins in @context from database.
 *
 * Returns: number of plugins loaded in @context
 *
 * Thread safe
 */
static gint
sim_context_load_plugins (SimContext *context)
{
  GList *plugins;
  gint   counter = 0;

  g_mutex_lock (context->priv->mutex_plugins);

  /* Adds a reference to common plugins */
  plugins = sim_container_get_common_plugins (ossim.container);
  while (plugins)
  {
    SimPlugin *plugin = SIM_PLUGIN (plugins->data);
    g_hash_table_insert (context->priv->plugins, GINT_TO_POINTER (sim_plugin_get_id (plugin)), g_object_ref (plugin));

    counter ++;
    plugins = g_list_next (plugins);
  }

  /* Adds the context plugins */
  plugins = sim_db_load_plugins (context->priv->database, context->priv->id);
  while (plugins)
  {
    SimPlugin *plugin = SIM_PLUGIN (plugins->data);
    g_hash_table_replace (context->priv->plugins, GINT_TO_POINTER (sim_plugin_get_id (plugin)), plugin);

    counter ++;
    plugins = g_list_next (plugins);
  }

  g_mutex_unlock (context->priv->mutex_plugins);

  return counter;
}

/*
 * Plugin Sids
 */

/**
 * sim_context_add_plugin_sid:
 * @context: a #SimContext
 *
 * Adds @plugin_sid in @context
 * If there is any plugin_sid with the same sid and plugin id in @context
 * then unref the previous plugin sids and insert the new one
 *
 * Thread safe
 */
void
sim_context_add_plugin_sid (SimContext   *context,
                            SimPluginSid *plugin_sid)
{
  guint cantor_key;

  g_return_if_fail (SIM_IS_CONTEXT (context));
  g_return_if_fail (SIM_IS_PLUGIN_SID (plugin_sid));

  cantor_key = sim_plugin_sid_get_cantor_key (plugin_sid);

  g_mutex_lock (context->priv->mutex_plugin_sids);

  g_hash_table_replace (context->priv->plugin_sids,
                        GUINT_TO_POINTER (cantor_key),
                        g_object_ref (plugin_sid));

  g_mutex_unlock (context->priv->mutex_plugin_sids);
}

/**
 * sim_context_get_plugin_sid:
 * @context: a #SimContext
 * @id: plugin id number
 * @sid: plugin sid number
 *
 * Search plugin_sid with @id and @sid in @context
 *
 * Returns: #SimPluginSid with @id and @sid if it found in @context
 * or %NULL otherwise
 *
 * Thread safe
 */
SimPluginSid *
sim_context_get_plugin_sid (SimContext *context,
                            gint        id,
                            gint        sid)
{
  SimPluginSid *plugin_sid = NULL;
  guint         cantor_key;

  g_return_val_if_fail (SIM_IS_CONTEXT (context), NULL);
  g_return_val_if_fail (id > 0, NULL);

  cantor_key = CANTOR_KEY (id, sid);

  g_mutex_lock (context->priv->mutex_plugin_sids);

  plugin_sid = g_hash_table_lookup (context->priv->plugin_sids, GUINT_TO_POINTER (cantor_key));
  if (plugin_sid)
    g_object_ref (plugin_sid);

  g_mutex_unlock (context->priv->mutex_plugin_sids);

  return plugin_sid;
}

/**
 * sim_context_load_plugin_sids:
 * @context: a #SimContext
 *
 * Load plugin sids in @context from database.
 *
 * Must be called after sim_engine_load_directives() because
 * when the directives are loaded a plugin sid with id 1505
 * and sid equal to the directive id is inserted in the database.
 *
 * Returns: the number of plugins sid loaded in @context
 *
 * Thread safe
 */
static gint
sim_context_load_plugin_sids (SimContext *context)
{
  GList *plugin_sids;
  gint   counter = 0;

  g_mutex_lock (context->priv->mutex_plugin_sids);

  /* Adds a reference to common plugin sids */
  plugin_sids = sim_container_get_common_plugin_sids (ossim.container);
  while (plugin_sids)
  {
    SimPluginSid *plugin_sid = SIM_PLUGIN_SID (plugin_sids->data);
    g_hash_table_insert (context->priv->plugin_sids,
                         GUINT_TO_POINTER (sim_plugin_sid_get_cantor_key (plugin_sid)),
                         g_object_ref (plugin_sid));

    counter ++;
    plugin_sids = g_list_next (plugin_sids);
  }

  /* Adds the context plugin sids */
  plugin_sids = sim_db_load_plugin_sids (context->priv->database, context->priv->id);
  while (plugin_sids)
  {
    SimPluginSid *plugin_sid = SIM_PLUGIN_SID (plugin_sids->data);
    g_hash_table_replace (context->priv->plugin_sids,
                          GUINT_TO_POINTER (sim_plugin_sid_get_cantor_key (plugin_sid)),
                          plugin_sid);

    counter ++;
    plugin_sids = g_list_next (plugin_sids);
  }

  g_mutex_unlock (context->priv->mutex_plugin_sids);

  return counter;
}

/**
 * sim_context_load_directives_plugin_sids:
 * @context: a #SimContext
 * @plugin_ctx: a #SimUuid
 *
 * Load plugin sids with @plugin_ctx and plugin 1505
 * in @context from database.
 *
 * Thread safe
 */
void
sim_context_load_directive_plugin_sids (SimContext *context,
                                        SimUuid    *plugin_ctx)
{
  GList *plugin_sids;

  g_return_if_fail (SIM_IS_CONTEXT (context));
  g_return_if_fail (SIM_IS_UUID (plugin_ctx));

  g_mutex_lock (context->priv->mutex_plugin_sids);

  /* Add directives plugin sids (1505) */
  plugin_sids = sim_db_load_plugin_sids (context->priv->database, plugin_ctx);
  while (plugin_sids)
  {
    SimPluginSid *plugin_sid = SIM_PLUGIN_SID (plugin_sids->data);
    g_hash_table_replace (context->priv->plugin_sids,
                          GUINT_TO_POINTER (sim_plugin_sid_get_cantor_key (plugin_sid)),
                          plugin_sid);

    plugin_sids = g_list_next (plugin_sids);
  }

  g_mutex_unlock (context->priv->mutex_plugin_sids);
}

/*
 *  Host Risk levels
 */

/**
 * sim_context_host_risk_level_is_zero:
 *
 * Returns %TRUE if host_level is zezo
 */
static gboolean
sim_context_host_risk_level_is_zero (gpointer key,
                                     gpointer value,
                                     gpointer user_data)
{
  SimHost *host = SIM_HOST (value);

  // unused parameter
  (void) key;
  (void) user_data;

  // Do not delete host loaded from db
  if (sim_host_is_loaded_from_db (host))
    return FALSE;
  else
    return (sim_host_level_is_zero (host));
}

/**
 * sim_context_update_host_level_recovery:
 * @context: #SimContext object
 * @recovery: gint recovery value
 *
 * Decrements @recovery in @context host_risk_levels,
 * updates database, and delete nets with zero C and A.
 */
void
sim_context_update_host_level_recovery (SimContext  *context,
                                        gint         recovery)
{
  g_return_if_fail (SIM_IS_CONTEXT (context));
  g_return_if_fail (recovery >= 0);

  g_mutex_lock (context->priv->mutex_hosts_nets);

  SIM_WHILE_HASH_TABLE (context->priv->hosts)
  {
    SimHost *host = (SimHost *) value;

    if (sim_host_level_set_recovery (host, recovery) && sim_host_is_loaded_from_db (host))
    {
      sim_db_update_host_risk_level (context->priv->database, host);
    }
  }

  /* Delete  */
  g_hash_table_foreach_remove (context->priv->hosts,
                               sim_context_host_risk_level_is_zero,
                               NULL);
  g_hash_table_foreach_remove (context->priv->host_ids,
                               sim_context_host_risk_level_is_zero,
                               NULL);

  g_mutex_unlock (context->priv->mutex_hosts_nets);
}

/*
 * Net Risk levels
 */

/**
 * sim_context_update_net_level_recovery:
 * @context: #SimContext object
 * @recovery: gint recovery value
 *
 * Decrements @recovery in @context net_risk_levels,
 * updates @database, and delete nets with zero C and A.
 */
void
sim_context_update_net_level_recovery (SimContext  *context,
                                       gint         recovery)
{
  g_return_if_fail (SIM_IS_CONTEXT (context));
  g_return_if_fail (SIM_IS_DATABASE (context->priv->database));
  g_return_if_fail (recovery >= 0);

  g_mutex_lock (context->priv->mutex_hosts_nets);

  SIM_WHILE_HASH_TABLE (context->priv->nets)
  {
    SimNet * net = (SimNet *)value;

    sim_net_level_set_recovery (net, recovery);
  }

  g_mutex_unlock (context->priv->mutex_hosts_nets);
}

/*
 * Hosts
 */

/**
 * sim_context_has_host_with_inet:
 * @context: a #SimContext
 * @inet: a #SimInet
 *
 * Returns: %TRUE if there is any host with @inet in the @context
 *  or %FALSE otherwise.
 * Thread safe.
 */
gboolean
sim_context_has_host_with_inet (SimContext *context,
                                SimInet    *inet)
{
  gboolean found = FALSE;
  SimHost *host = NULL;

  g_return_val_if_fail (SIM_IS_CONTEXT (context), FALSE);
  g_return_val_if_fail (SIM_IS_INET (inet), FALSE);

  g_mutex_lock (context->priv->mutex_hosts_nets);

  host = g_hash_table_lookup (context->priv->hosts, inet);
  if (host)
    found = TRUE;

  g_mutex_unlock (context->priv->mutex_hosts_nets);

  return found;
}

/**
 * sim_context_append_host:
 * @context: a #SimContext
 * @host: a #SimHosLevel
 *
 * This adds a reference to the SimHost so, it must be unref outside
 * when not in use
 *
 * Appends @host to @context
 * Thread safe
 */
void sim_context_append_host (SimContext     *context,
                              SimHost        *host)
{
  g_return_if_fail (SIM_IS_CONTEXT (context));

  g_mutex_lock (context->priv->mutex_hosts_nets);
  sim_context_append_host_ul (context, host);
  g_mutex_unlock (context->priv->mutex_hosts_nets);
}

/**
 * sim_context_get_host_by_inet:
 * @context: a #SimContext
 * @inet: a #SimInet
 *
 * Returns: #SimHost object with @inet if it's in the @context
 *  or %NULL otherwise.
 * Thread safe.
 */
SimHost *
sim_context_get_host_by_inet (SimContext *context,
                              SimInet    *inet)
{
  SimHost *host = NULL;

  g_return_val_if_fail (SIM_IS_CONTEXT (context), NULL);

  g_mutex_lock (context->priv->mutex_hosts_nets);

  host = g_hash_table_lookup (context->priv->hosts, inet);

  if (host)
    g_object_ref (host);

  g_mutex_unlock (context->priv->mutex_hosts_nets);

  return host;
}

/**
 * sim_context_get_host_by_name:
 * @context: a #SimContext
 * @name: name of the host
 *
 * Returns: #SimHost object with
 *
 * Thread safe.
 */
SimHost *
sim_context_get_host_by_name (SimContext *context,
                              gchar *name)
{
  SimHost *host_matched = NULL;
  GHashTableIter iter;
  gpointer key, value;

  g_mutex_lock (context->priv->mutex_hosts_nets);

  g_hash_table_iter_init (&iter, context->priv->hosts);
  while (g_hash_table_iter_next (&iter, &key, &value))
  {
    SimHost *host = (SimHost *) value;
    if (g_ascii_strcasecmp (sim_host_get_name (host), name) == 0)
    {
      host_matched = g_object_ref (host);
      break;
    }
  }

  g_mutex_unlock (context->priv->mutex_hosts_nets);

  return host_matched;
}

/**
 * sim_context_get_host_by_id:
 * @context: a #SimContext
 * @id: a #SimUuid
 *
 * Returns: #SimHost object identified by @id or %NULL otherwise.
 * Thread safe.
 */
SimHost *
sim_context_get_host_by_id (SimContext *context,
                            SimUuid   * id)
{
  SimHost *host = NULL;

  g_return_val_if_fail (SIM_IS_CONTEXT (context), NULL);

  g_mutex_lock (context->priv->mutex_hosts_nets);

  host = g_hash_table_lookup (context->priv->host_ids, (gconstpointer)id);

  if (host)
    g_object_ref (host);

  g_mutex_unlock (context->priv->mutex_hosts_nets);

  return host;
}

/**
 * sim_context_load_hosts:
 * @context: a #SimContext
 *
 * Loads hosts in @context from database
 * Thread safe
 */
static gint
sim_context_load_hosts (SimContext  *context)
{
  GList *host_list;
  GList *node;
  gint   host_num = 0;

  host_list = sim_db_load_hosts (context->priv->database, context->priv->id);
  node = host_list;

  g_mutex_lock (context->priv->mutex_hosts_nets);
  while (node)
  {
    SimHost *host = (SimHost *)node->data;
    sim_context_append_host_ul (context, host);
    host_num ++;

    node = g_list_next (node);
  }
  g_mutex_unlock (context->priv->mutex_hosts_nets);

  g_list_free (host_list);

  return host_num;
}

/**
 * sim_context_append_host_ul:
 * @context: #SimContext object
 * @host: a #Simhost
 *
 * Inserts @host in @context
 */
static void
sim_context_append_host_ul (SimContext *context,
                            SimHost    *host)
{
  GPtrArray *inets;
  guint i;

  g_return_if_fail (SIM_IS_HOST (host));

  /* host ips */
  inets = sim_host_get_inets (host);
  for (i = 0; i < inets->len; i ++)
  {
    SimInet *inet = (SimInet *) g_ptr_array_index (inets, i);
    g_hash_table_insert (context->priv->hosts, g_object_ref (inet), g_object_ref (host));
  }

  /* host id */
  SimUuid *host_id = sim_host_get_id (host);
  g_hash_table_insert (context->priv->host_ids, g_object_ref (host_id), g_object_ref (host));
}

/**
 * sim_context_reload_hosts:
 * @context: a #SimContext
 *
 * Delete previous hosts data and
 * ReLoad hosts in @context from database
 */
void
sim_context_reload_hosts (SimContext  *context)
{
  GList *host_list;
  GList *node;
  GHashTable *old_hosts, * old_host_ids;

  g_return_if_fail (SIM_IS_CONTEXT (context));
  g_return_if_fail (SIM_IS_DATABASE (context->priv->database));

  host_list = sim_db_load_hosts (context->priv->database, context->priv->id);
  node = host_list;

  g_mutex_lock (context->priv->mutex_hosts_nets);

  old_hosts = context->priv->hosts;
  old_host_ids = context->priv->host_ids;

  context->priv->hosts = g_hash_table_new_full (sim_inet_hash, sim_inet_equal,
                                                NULL, g_object_unref);
  context->priv->host_ids = g_hash_table_new_full (sim_uuid_hash, sim_uuid_equal,
                                                NULL, g_object_unref);
  /* Load Host in DB */
  while (node)
  {
    SimHost *host = (SimHost *) node->data;
    sim_context_append_host_ul (context, host);

    node = g_list_next (node);
  }

  g_mutex_unlock (context->priv->mutex_hosts_nets);

  /* Remove previous host data*/
  if (old_hosts != NULL)
    g_hash_table_destroy (old_hosts);
  if (old_host_ids != NULL)
    g_hash_table_destroy (old_host_ids);

  g_list_free (host_list);
}

/*
 * Nets
 */

/**
 * sim_context_reload_nets:
 * @context: a #SimContext
 *
 * Delete previous nets data and
 * ReLoad nets in @context from database
 */
void
sim_context_reload_nets (SimContext  *context)
{
  GList *net_list;
  GList *node;
  GHashTable *old_nets, * old_net_ids;
  SimNetwork *old_home_net;
  SimNetwork *old_all_nets;

  g_return_if_fail (SIM_IS_CONTEXT (context));
  g_return_if_fail (SIM_IS_DATABASE (context->priv->database));

  /* Get nets from db */
  net_list = sim_db_load_nets (context->priv->database, context->priv->id);
  node = net_list;

  g_mutex_lock (context->priv->mutex_hosts_nets);

  old_nets = context->priv->nets;
  old_net_ids = context->priv->net_ids;
  old_home_net = context->priv->home_net;
  old_all_nets = context->priv->all_nets;

  context->priv->nets = g_hash_table_new_full (g_str_hash, g_str_equal,
                                               g_free, g_object_unref);
  context->priv->net_ids = g_hash_table_new_full (sim_uuid_hash, sim_uuid_equal,
                                                  g_object_unref, g_object_unref);

  context->priv->home_net = sim_network_new ();
  context->priv->all_nets = sim_network_new ();

  /* Load db nets*/
  while (node)
  {
    SimNet *net = (SimNet *) node->data;
    sim_context_append_net (context, net);
    node = g_list_next (node);
  }

  g_mutex_unlock (context->priv->mutex_hosts_nets);

  /* Remove previous nets data */
  if (old_nets != NULL)
    g_hash_table_destroy (old_nets);
  if (old_net_ids != NULL)
    g_hash_table_destroy (old_net_ids);
  if (old_home_net != NULL)
    g_object_unref (old_home_net);
  if (old_all_nets != NULL)
    g_object_unref (old_all_nets);

  g_list_free (net_list);
}

/**
 * sim_context_get_net_by_name:
 * @context: a #SimContext
 * @name: const gchar* with the net name
 *
 * Returns: #SimNet object with the net name if it's in the @context
 *  or %NULL otherwise.
 * Thread safe.
 */
SimNet *
sim_context_get_net_by_name (SimContext  *context,
                             const gchar *name)
{
  SimNet *net;

  g_return_val_if_fail (SIM_IS_CONTEXT (context), NULL);
  g_return_val_if_fail (name, NULL);

  g_mutex_lock (context->priv->mutex_hosts_nets);

  net = g_hash_table_lookup (context->priv->nets, (gpointer)name);
  if (net)
    g_object_ref (net);

  g_mutex_unlock (context->priv->mutex_hosts_nets);

  return net;
}

/**
 * sim_context_get_net_by_id:
 * @context: a #SimContext
 * @id: a #SimUuid object.
 *
 * Returns: #SimNet object with the net @id or %NULL otherwise.
 * Thread safe.
 */
SimNet *
sim_context_get_net_by_id (SimContext  *context,
                           SimUuid * id)
{
  SimNet *net;

  g_return_val_if_fail (SIM_IS_CONTEXT (context), NULL);
  g_return_val_if_fail (SIM_IS_UUID(id), NULL);

  g_mutex_lock (context->priv->mutex_hosts_nets);

  net = g_hash_table_lookup (context->priv->net_ids, (gpointer)id);
  if (net)
    g_object_ref (net);

  g_mutex_unlock (context->priv->mutex_hosts_nets);

  return net;
}


/**
 * sim_context_db_load_nets:
 * @context: #SimContext object
 *
 * Loads the @context networks from database.
 */
static gint
sim_context_load_nets (SimContext *context)
{
  GList *net_list;
  GList *node;
  gint   net_num = 0;

  net_list = sim_db_load_nets (context->priv->database, context->priv->id);
  node = net_list;

  g_mutex_lock (context->priv->mutex_hosts_nets);

  while (node)
  {
    SimNet *net = (SimNet *) node->data;
    sim_context_append_net (context, net);
    net_num ++;
    node = g_list_next (node);
  }

  g_mutex_unlock (context->priv->mutex_hosts_nets);

  g_list_free (net_list);

  return net_num;
}

/**
 * sim_context_append_net:
 * @context: #SimContext object
 * @net: a #SimNet
 *
 * Inserts @net in @context:
 * - appends @net in @context nets hash table
 * - appends all inets in @net into @context home_net #SimNetwork
 * - appends all inets in @net into @context asset #SimRadix tree
 */
static void
sim_context_append_net (SimContext *context,
                        SimNet     *net)
{
  GList *list;
  gchar   * net_name;
  SimUuid * net_id;
  gboolean external;

  g_return_if_fail (SIM_IS_NET (net));

  net_name = g_strdup (sim_net_get_name (net));
  net_id = g_object_ref (sim_net_get_id (net));
  external = sim_net_get_external (net);

  g_hash_table_insert (context->priv->nets, net_name, g_object_ref (net));
  g_hash_table_insert (context->priv->net_ids, net_id, g_object_ref (net));

  list = sim_net_get_inets (net);
  while (list)
  {
    SimInet *inet = (SimInet *) list->data;

    // Loads inets ins all_nets
    sim_network_add_inet (context->priv->all_nets, inet);

    // Loads inets in home_net except if the net is marked as 'external'
    if (!external)
    {
      if (!sim_network_has_exact_inet (context->priv->home_net, inet))
      {
        ossim_debug ("%s: net not found, loading it...", __func__);
        sim_network_add_inet (context->priv->home_net, inet);
      }
      else
      {
        gchar *str_aux = sim_inet_get_canonical_name (inet);
        g_message ("%s Error: There is a duplicated network. "
                   "It may has different name, but it has the same IP, "
                   "please check your assets for: %s. Net %s not loaded. "
                   "Policies or directives using this network won't work",
                   __func__,
                   str_aux,
                   sim_net_get_name (net));
        g_free (str_aux);
        list = list->next;
        continue;
      }
    }

    list = list->next;
  } // while inet

  return;
}

/**
 * sim_context_has_inet_in_nets:
 * @context: #SimContext object
 * @inet: a #SimInet
 *
 * Returns : %TRUE if @inet is in @context all_nets
 * Thread safe
 */
static gboolean
sim_context_has_inet_in_nets (SimContext  *context,
                              SimInet     *inet)
{
  gboolean found = FALSE;

  // Search in all_nets
  g_mutex_lock (context->priv->mutex_hosts_nets);
  found = sim_network_has_inet (context->priv->all_nets, inet);
  g_mutex_unlock (context->priv->mutex_hosts_nets);

  return found;
}

/**
 * sim_context_is_inet_in_homenet:
 * @context: #SimContext object
 * @inet: a #SimInet
 *
 * Returns : %TRUE if @inet is in @context home_net
 * Thread safe
 */
gboolean
sim_context_is_inet_in_homenet (SimContext  *context,
                                SimInet     *inet)
{
  g_return_val_if_fail (SIM_IS_CONTEXT (context), FALSE);
  g_return_val_if_fail (SIM_IS_INET (inet), FALSE);

  // Check if the inet was previously search in HOME_NET
  if (!sim_inet_is_homenet_checked (inet))
  {
    // Search inet in HOME_NET only once
    g_mutex_lock (context->priv->mutex_hosts_nets);
    gboolean found = sim_network_has_inet (context->priv->home_net, inet);
    g_mutex_unlock (context->priv->mutex_hosts_nets);

    sim_inet_set_is_in_homenet (inet, found);
  }

  return sim_inet_is_in_homenet (inet);
}

/**
 * sim_context_get_home_net:
 * @context: #SimContext object
 *
 * Returns a #SimNetwork object defining our home net.
 */
SimNetwork *
sim_context_get_home_net (SimContext *context)
{
  return (context->priv->home_net);
}


/**
 * sim_context_get_homenet_net:
 * @context: #SimContext object
 * @inet: a #SimInet
 *
 * This adds a reference to the SimInet so, it must be unref outside
 * when not in use
 *
 * Returns : a #SimInet Net if @inet is in @context home_net
 * Thread safe
 */
SimInet *
sim_context_get_homenet_inet (SimContext  *context,
                              SimInet     *inet)
{
  SimInet *homenet_inet;

  g_return_val_if_fail (SIM_IS_CONTEXT (context), NULL);
  g_return_val_if_fail (SIM_IS_INET (inet), NULL);

  /* The inet must be in homenet */
  if (sim_inet_is_homenet_checked (inet) && !sim_inet_is_in_homenet (inet))
    return NULL;

  g_mutex_lock (context->priv->mutex_hosts_nets);

  homenet_inet = sim_network_search_inet (context->priv->home_net, inet);
  if (homenet_inet)
    g_object_ref (homenet_inet);

  g_mutex_unlock (context->priv->mutex_hosts_nets);

  return homenet_inet;
}


/**
 * sim_context_has_inet:
 * @context: #SimContext object
 * @inet: a #SimInet
 *
 * Returns : %TRUE if @inet is defined in @context hosts or home_net
 * Thread safe
 */
gboolean
sim_context_has_inet (SimContext  *context,
                      SimInet     *inet)
{
  gboolean has_inet = FALSE;

  if (sim_context_has_host_with_inet (context, inet) ||
      sim_context_has_inet_in_nets (context, inet))
  {
    has_inet = TRUE;
  }

  return has_inet;
}

/**
 * sim_context_get_inet_asset:
 * @context: #SimContext object
 * @inet: a #SimInet
 *
 * Returns the asset for @inet.
 */
gint
sim_context_get_inet_asset (SimContext *context,
                            SimInet    *inet)
{
  gint asset = DEFAULT_ASSET;
  SimInet *inet_found;

  g_return_val_if_fail (SIM_IS_CONTEXT (context), -1);
  g_return_val_if_fail (SIM_IS_INET (inet), -1);

  inet_found = sim_network_search_inet (context->priv->all_nets, inet);
  if (inet_found)
  {
    SimNet *net;
    net = sim_inet_get_parent_net (inet_found);
    if (net)
      asset = sim_net_get_asset (net);
  }

  return asset;
}

/**
 * sim_context_get_host_asset:
 * @context: a #SimContext object.
 * @id: a #SimUuid object.
 *
 * Returns the asset for a host identified by @id.
 */
gint
sim_context_get_host_asset (SimContext * context,
                            SimUuid    * id)
{
  g_return_val_if_fail (SIM_IS_CONTEXT (context), -1);
  g_return_val_if_fail (SIM_IS_UUID (id), -1);

  SimHost * host = g_hash_table_lookup (context->priv->host_ids, id);
  gint asset = DEFAULT_ASSET;

  if (host)
    return (sim_host_get_asset (host));

  return (asset);
}

/**
 * sim_context_expand_directive_rule_ips:
 *
 */
gboolean
sim_context_expand_directive_rule_ips (SimContext *context,
                                       gchar      *asset_name,
                                       SimRule    *rule,
                                       gboolean    is_negated,
                                       gboolean    is_src)
{
  SimUuid *asset_id = NULL;
  SimHost *host;
  SimInet *inet;
  GList *inets;
  SimNet *net;

  g_return_val_if_fail (SIM_IS_CONTEXT (context), FALSE);

  if (sim_uuid_is_valid_string (asset_name))
    asset_id = sim_uuid_new_from_string (asset_name);

  /* Try to resolv host */
  if (asset_id)
    host = sim_context_get_host_by_id (context, asset_id);
  else
    host = sim_context_get_host_by_name (context, asset_name);

  if (host != NULL)
  {
    SimUuid *host_id = sim_host_get_id (host);

    if (is_negated)
      if (is_src)
        sim_rule_add_src_host_id_not (rule, host_id);
      else
        sim_rule_add_dst_host_id_not (rule, host_id);
    else
      if (is_src)
        sim_rule_add_src_host_id (rule, host_id);
      else
        sim_rule_add_dst_host_id (rule, host_id);

    g_object_unref (host);

    if (asset_id)
      g_object_unref (asset_id);

    return TRUE;
  }
  else
  {
    if (asset_id)
      net = sim_context_get_net_by_id (context, asset_id);
    else
      net = sim_context_get_net_by_name (context, asset_name);

    if (net != NULL)
    {
      for (inets = sim_net_get_inets (net); inets; inets = inets->next)
      {
        inet = (SimInet *) inets->data;
        if (is_negated)
          if (is_src)
            sim_rule_add_secure_src_inet_not (rule, inet);
          else
            sim_rule_add_secure_dst_inet_not (rule, inet);
        else
          if (is_src)
            sim_rule_add_secure_src_inet (rule, inet);
          else
            sim_rule_add_secure_dst_inet (rule, inet);

        g_object_unref (inet);
      }

      g_object_unref (net);
      if (asset_id)
        g_object_unref (asset_id);

      return TRUE;
    }
    else
    {
      return FALSE;
    }
  }
}

/*
 * Plugin references (for cross correlation)
 */

/**
 * sim_context_load_plugin_references:
 * @context: a #SimContext object.
 *
 * Loads contents from plugin_references table (thread safe).
 * Can work as a 'reload' function.
 */
static gint
sim_context_load_plugin_references (SimContext * context)
{
  GHashTable * plugin_references = NULL, * old_plugin_references;
  GList * plugin_ref_list;
  gint    plugin_references_num = 0;
  SimPluginSid * sid, * ref_sid;

  plugin_ref_list = sim_db_load_plugin_references (context->priv->database, context->priv->id);

  if (plugin_ref_list)
    plugin_references = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, g_free);

  while (plugin_ref_list)
  {
    SimPluginReference * plugin_reference;
    guint key;

    plugin_reference = (SimPluginReference *) plugin_ref_list->data;

    sid = sim_context_get_plugin_sid (context,
                                      plugin_reference->plugin_id,
                                      plugin_reference->plugin_sid);
    ref_sid = sim_context_get_plugin_sid (context,
                                      plugin_reference->reference_id,
                                      plugin_reference->reference_sid);


    if ((sid) && (ref_sid))
    {
      key = CANTOR_KEY (plugin_reference->plugin_id, plugin_reference->plugin_sid);
      g_hash_table_insert (plugin_references, GUINT_TO_POINTER (key), plugin_reference);
      plugin_references_num ++;
    }
    else
      g_free(plugin_reference);

    plugin_ref_list = g_list_delete_link (plugin_ref_list, plugin_ref_list);
  }

  old_plugin_references = context->priv->plugin_references;
  g_atomic_pointer_set (&context->priv->plugin_references, plugin_references);

  if (old_plugin_references)
    g_hash_table_destroy (old_plugin_references);

  return (plugin_references_num);
}

/*
 * Host plugin sids (for cross correlation)
 */

/**
 * sim_context_lock_host_plugin_sids_r:
 * @context: a #SimContext object.
 *
 * Lock the host_plugin_sids mutex in @context for reading.
 */
void
sim_context_lock_host_plugin_sids_r (SimContext *context)
{
  g_return_if_fail (SIM_IS_CONTEXT (context));
  g_static_rw_lock_reader_lock (&context->priv->mutex_host_plugin_sids);
}

/**
 * sim_context_unlock_host_plugin_sids_r:
 * @context: a #SimContext object.
 *
 * Unlock the host_plugin_sids mutex in @context for reading.
 */
void
sim_context_unlock_host_plugin_sids_r (SimContext *context)
{
  g_return_if_fail (SIM_IS_CONTEXT (context));
  g_static_rw_lock_reader_unlock (&context->priv->mutex_host_plugin_sids);
}

/**
 * sim_context_lock_host_plugin_sids_w:
 * @context: a #SimContext object.
 *
 * Lock the host_plugin_sids mutex in @context for writing.
 */
void
sim_context_lock_host_plugin_sids_w (SimContext *context)
{
  g_return_if_fail (SIM_IS_CONTEXT (context));
  g_static_rw_lock_writer_lock (&context->priv->mutex_host_plugin_sids);
}

/**
 * sim_context_unlock_host_plugin_sids_w:
 * @context: a #SimContext object.
 *
 * Unlock the host_plugin_sids mutex in @context for writing.
 */
void
sim_context_unlock_host_plugin_sids_w (SimContext *context)
{
  g_return_if_fail (SIM_IS_CONTEXT (context));
  g_static_rw_lock_writer_unlock (&context->priv->mutex_host_plugin_sids);
}

/**
 * sim_context_host_plugin_sid_free:
 * @data: a pointer to a #SimHostPluginSid struct.
 *
 * Frees the @host_plugin_sid struct.
 */
static void
sim_context_host_plugin_sid_free (gpointer data)
{
  g_return_if_fail (data);

  SimHostPluginSid * host_plugin_sid = (SimHostPluginSid *) data;

  g_object_unref (host_plugin_sid->host_ip);
  g_object_unref (host_plugin_sid->context);
  g_free (host_plugin_sid);

  return;
}

/**
 * sim_context_get_event_host_plugin_sid:
 * @context: a #SimContext object.
 * @event: a #SimEvent
 *
 * Search in host_plugin_sid hash table for the key formed by
 *  [ @event dst_ip : @event plugin_id : @event plugin_sid ]
 *
 * Returns a #SimPluginSids if there is any plugin sid
 * in @context asociated to @event
 * or %NULL otherwise.
 * Thread safe
 */
SimPluginSid *
sim_context_get_event_host_plugin_sid (SimContext *context,
                                       SimEvent   *event)
{
  SimHostPluginSid * host_plugin_sid = NULL;
  SimPluginSid *plugin_sid = NULL;
  guint key;

  g_return_val_if_fail (SIM_IS_CONTEXT (context), NULL);
  g_return_val_if_fail (SIM_IS_EVENT (event), NULL);

  // Aggregated cantor key. This is possible because cantor functions keep up with
  // the associative property.
  key = CANTOR_KEY (sim_inet_hash (event->dst_ia),
                      CANTOR_KEY (sim_uuid_hash (sim_context_get_id (event->context)),
                                  CANTOR_KEY (event->plugin_id, event->plugin_sid)));

  g_static_rw_lock_reader_lock (&context->priv->mutex_host_plugin_sids);

  host_plugin_sid = (SimHostPluginSid *)g_hash_table_lookup (context->priv->host_plugin_sids, GUINT_TO_POINTER (key));
  if (host_plugin_sid)
    plugin_sid = sim_context_get_plugin_sid (context, host_plugin_sid->reference_id, host_plugin_sid->reference_sid);

  if(plugin_sid)
    g_object_ref (plugin_sid);

  g_static_rw_lock_reader_unlock (&context->priv->mutex_host_plugin_sids);

  return plugin_sid;
}

/**
 * sim_context_get_host_plugin_sid_list:
 * @context: a #SimContext object.
 *
 * Retrieves every #SimHostPluginSid of this @context in a list.
 */
GList *
sim_context_get_host_plugin_sid_list (SimContext *context)
{
  GList * host_plugin_sids = NULL;

  g_static_rw_lock_reader_lock (&context->priv->mutex_host_plugin_sids);
  SIM_WHILE_HASH_TABLE (context->priv->host_plugin_sids)
  {
    host_plugin_sids = g_list_prepend (host_plugin_sids, (gpointer) value);
  }
  g_static_rw_lock_reader_unlock (&context->priv->mutex_host_plugin_sids);

  return (host_plugin_sids);
}

/**
 * sim_context_try_set_host_plugin_sid:
 * @context: a #SimContext object.
 * @event: a #SimEvent object.
 *
 * Adds a new #SimHostPluginSid to our table, if applicable.
 */
gboolean
sim_context_try_set_host_plugin_sid (SimContext * context,
                                     SimEvent   * event)
{
  if (!(context->priv->plugin_references))
    return (FALSE);

  GHashTable * plugin_references = g_hash_table_ref (context->priv->plugin_references);
  SimPluginReference * plugin_reference = NULL;
  SimHostPluginSid * host_plugin_sid = NULL;
  guint key;
  gboolean try = FALSE;

  key = CANTOR_KEY (event->plugin_id, event->plugin_sid);
  if ((plugin_reference = g_hash_table_lookup (plugin_references, GUINT_TO_POINTER (key))))
  {
    // Add a new item in the host_plugin_sids table.
    key = CANTOR_KEY (sim_inet_hash (event->dst_ia),
                      CANTOR_KEY (sim_uuid_hash (sim_context_get_id (context)),
                                  CANTOR_KEY (event->plugin_id, event->plugin_sid)));

    host_plugin_sid = g_new0 (SimHostPluginSid, 1);
    host_plugin_sid->host_ip = g_object_ref (event->dst_ia);
    host_plugin_sid->context = g_object_ref (event->context);
    host_plugin_sid->plugin_id = plugin_reference->plugin_id;
    host_plugin_sid->plugin_sid = plugin_reference->plugin_sid;
    host_plugin_sid->reference_id = plugin_reference->reference_id;
    host_plugin_sid->reference_sid = plugin_reference->reference_sid;

    g_static_rw_lock_writer_lock (&context->priv->mutex_host_plugin_sids);
    g_hash_table_insert (context->priv->host_plugin_sids, GUINT_TO_POINTER (key), host_plugin_sid);
    g_static_rw_lock_writer_unlock (&context->priv->mutex_host_plugin_sids);

    try = TRUE;
  }

  g_hash_table_unref (plugin_references);
  return (try);
}

/**
 * sim_context_load_host_plugin_sids:
 * @context: a #SimContext object.
 *
 * Loads host plugin sids in @context from database
 * Thread safe
 */
static gint
sim_context_load_host_plugin_sids (SimContext *context)
{
  GList *node;
  GList *host_plugin_sid_list;
  gint   host_plugin_sid_num = 0;

  host_plugin_sid_list = sim_db_load_host_plugin_sids (context->priv->database, context->priv->id);
  node = host_plugin_sid_list;

  while (node)
  {
    SimHostPluginSid * host_plugin_sid;
    guint key;
    SimPluginSid *sid;

    host_plugin_sid = (SimHostPluginSid *) node->data;
    host_plugin_sid->in_database = TRUE;

    // Key: host_ip + context id + plugin id + plugin sid
    key = CANTOR_KEY (sim_inet_hash (host_plugin_sid->host_ip),
                      CANTOR_KEY (sim_uuid_hash (sim_context_get_id (host_plugin_sid->context)),
                                  CANTOR_KEY (host_plugin_sid->plugin_id, host_plugin_sid->plugin_sid)));

    sid = sim_context_get_plugin_sid (context,
                                      host_plugin_sid->reference_id,
                                      host_plugin_sid->reference_sid);

    if (sid)
    {
      g_static_rw_lock_writer_lock (&context->priv->mutex_host_plugin_sids);
      g_hash_table_insert (context->priv->host_plugin_sids, GUINT_TO_POINTER (key), host_plugin_sid);
      host_plugin_sid_num ++;
      g_static_rw_lock_writer_unlock (&context->priv->mutex_host_plugin_sids);
    }

    node = g_list_next (node);
  }

  g_list_free (host_plugin_sid_list);

  return host_plugin_sid_num;
}

void
sim_context_reload_host_plugin_sids (SimContext *context)
{
  GList *node;
  GList *host_plugin_sid_list;
  GHashTable *new_data;
  GHashTable *old_data;

  g_return_if_fail (SIM_IS_CONTEXT (context));

  new_data = g_hash_table_new_full (g_direct_hash, g_direct_equal,
                                    NULL, (GDestroyNotify)sim_context_host_plugin_sid_free);

  /* Load new data from db */
  host_plugin_sid_list = sim_db_load_host_plugin_sids (context->priv->database, context->priv->id);
  node = host_plugin_sid_list;
  while (node)
  {
    SimHostPluginSid *host_plugin_sid;
    guint key;
    SimPluginSid *sid;

    host_plugin_sid = (SimHostPluginSid *) node->data;
    host_plugin_sid->in_database = TRUE;

    key = CANTOR_KEY (sim_inet_hash (host_plugin_sid->host_ip),
                      CANTOR_KEY (sim_uuid_hash (sim_context_get_id (host_plugin_sid->context)),
                                  CANTOR_KEY (host_plugin_sid->plugin_id, host_plugin_sid->plugin_sid)));

    sid = sim_context_get_plugin_sid (context,
                                      host_plugin_sid->reference_id,
                                      host_plugin_sid->reference_sid);
    if (sid)
      g_hash_table_insert (new_data, GUINT_TO_POINTER(key), host_plugin_sid);

    node = g_list_next (node);
  }

  /* Replace data */
  g_static_rw_lock_writer_lock (&context->priv->mutex_host_plugin_sids);

  old_data = context->priv->host_plugin_sids;
  context->priv->host_plugin_sids = new_data;

  g_static_rw_lock_writer_unlock (&context->priv->mutex_host_plugin_sids);

  /* Unref old data */
  g_hash_table_unref (old_data);

  g_list_free (host_plugin_sid_list);
}

/**
 * sim_context_check_host_plugin_sids:
 * @context: a #SimContext object.
 *
 * Checks if hosts in host_plugin_sids are in
 * the host list or belong to a network.
 */
void
sim_context_check_host_plugin_sids (SimContext * context)
{
  GList       * host_list;

  // Get the list of IP in host plugin sid.
  host_list = sim_db_get_host_plugin_sid_hosts (context->priv->database, context->priv->id);

  for (; host_list; host_list = g_list_delete_link (host_list, host_list))
  {
    SimInet * host = sim_inet_new_from_string ((gchar *)host_list->data);
    if (!(host))
      continue;

    // Check in the hosts list.
    if (!sim_context_get_host_by_inet (context, host))
    {
      // Check in the net list.
      if (!sim_context_is_inet_in_homenet (context, host))
      {
        // Remove from model.
        sim_db_delete_host_plugin_sid_host (context->priv->database, context->priv->id, host_list->data);
      }
    }
    g_free (host_list->data);
    g_object_unref (host);
  }

  // Reload host plugin sids.
  sim_context_reload_host_plugin_sids (context);

  return;
}

/*
 * Policies
 */

/**
 * sim_context_get_policies:
 * @context: a #SimContext
 *
 * Returns the policies list in @context
 */
GList *
sim_context_get_policies (SimContext *context)
{
  g_return_val_if_fail (SIM_IS_CONTEXT (context), NULL);

  return context->priv->policies;
}

/**
 * sim_context_get_event_policy:
 * @context: a #SimContext
 * @SimEvent: a #SimEvent
 *
 * Searchs if event matchs with any context policy
 * Thread safe
 *
 * Returns policy in @context which matches with @event
 * Thread safe
 */
SimPolicy *
sim_context_get_event_policy (SimContext *context,
                              SimEvent   *event)
{
  SimPolicy *policy_found = NULL;
  gboolean found = FALSE;
  SimPortProtocol * src_port_protocol = NULL, * dst_port_protocol = NULL;
  GList *list;

  g_return_val_if_fail (SIM_IS_CONTEXT (context), NULL);
  g_return_val_if_fail (SIM_IS_EVENT (event), NULL);

  // Check event data
  g_return_val_if_fail (SIM_IS_INET (event->src_ia), NULL);
  g_return_val_if_fail (SIM_IS_INET (event->dst_ia), NULL);
  g_return_val_if_fail (event->sensor != NULL, NULL);

  // Get the port/protocol used to obtain the policy that matches.
  src_port_protocol = sim_port_protocol_new (event->src_port, event->protocol);
  dst_port_protocol = sim_port_protocol_new (event->dst_port, event->protocol);
  if ((src_port_protocol == NULL) || (dst_port_protocol == NULL))
  {
    g_free (src_port_protocol);
    g_free (dst_port_protocol);

    return NULL;
  }

  // check if some policy applies
  g_static_rw_lock_reader_lock (&context->priv->sem_policies);
  list = context->priv->policies;
  while (list)
  {
    SimPolicy *policy = (SimPolicy *)list->data;
    found = sim_policy_match (policy,
                              event,
                              src_port_protocol,
                              dst_port_protocol);

    if (found)
    {
      policy_found = g_object_ref (policy);
      break;
    }

    list = list->next;
  }
  g_static_rw_lock_reader_unlock (&context->priv->sem_policies);

  g_free (src_port_protocol);
  g_free (dst_port_protocol);

  if (found)
  {
    SimUuid * policy_id = sim_policy_get_id (policy_found);
    ossim_debug ("%s: Policy %s MATCH", __func__, sim_uuid_get_string (policy_id));
  }
  else
    ossim_debug ("%s: Policy No MATCH", __func__);

  return policy_found;
}

/**
 * sim_context_load_policies:
 * @context: a #SimContext
 *
 * Load policies in @context from database.
 * Thread safe
 */
static gint
sim_context_load_policies (SimContext *context)
{
  GList *policies;

  policies = sim_db_load_policies (context->priv->database, context->priv->id);

  g_static_rw_lock_writer_lock (&context->priv->sem_policies);
  context->priv->policies = policies;
  g_static_rw_lock_writer_unlock (&context->priv->sem_policies);

  return (g_list_length (policies));
}

/**
 * sim_context_reload_policies:
 * @context: a #SimContext
 *
 * ReLoad policies in @context from database.
 * Thread safe
 */
void
sim_context_reload_policies (SimContext *context)
{
  GList *new_list;
  GList *previous_list;

  g_return_if_fail (SIM_IS_CONTEXT (context));
  g_return_if_fail (SIM_IS_DATABASE (context->priv->database));

  new_list = sim_db_load_policies (context->priv->database,
                                   context->priv->id);

  g_static_rw_lock_writer_lock (&context->priv->sem_policies);
  previous_list = context->priv->policies;
  context->priv->policies = new_list;
  g_static_rw_lock_writer_unlock (&context->priv->sem_policies);

  /* Remove previous data */
  if (previous_list != NULL)
  {
    g_list_foreach (previous_list, (GFunc)g_object_unref, NULL);
    g_list_free (previous_list);
  }
}


/*
 * Taxonomy products
 */

/**
 * sim_context_load_taxonomy_products:
 * @container: a #SimContainer.
 *
 * Load taxonomy products in @context from database
 */
static void
sim_context_load_taxonomy_products (SimContext *context)
{
  GList *products;
  GList *plugin_ids;

  products = sim_db_load_taxonomy_products (ossim.dbossim);
  while (products)
  {
    gint product_id = GPOINTER_TO_INT (products->data);

    plugin_ids = sim_db_load_plugin_ids_with_product (ossim.dbossim,
                                                      product_id,
                                                      context->priv->id);
    g_hash_table_insert (context->priv->taxonomy_products,
                         GINT_TO_POINTER (product_id),
                         plugin_ids);

    products = g_list_next (products);
  }
}

/**
 * sim_context_get_taxonomy_product:
 * @context: a #SimContext.
 * @product_id: Taxonomy product id
 *
 * Returns GList * with plugin_ids with taxonomy @product_id in @context
 */
GList *
sim_context_get_taxonomy_product (SimContext *context,
                                  gint        product_id)
{
  GList *plugin_ids;

  g_return_val_if_fail (SIM_IS_CONTEXT (context), NULL);

  plugin_ids = (GList *) g_hash_table_lookup (context->priv->taxonomy_products,
                                              GINT_TO_POINTER (product_id));

  return plugin_ids;
}

/*
 * Stats Data
 */

/**
 * sim_context_get_stats:
 * @context: a #SimContext object.
 * @elapse_time: elapsed time in seconds
 *
 * Returns guint with @context stats.
 */
guint
sim_context_get_stats (SimContext *context,
                       glong       elapsed_time)
{
  guint total;
  guint eps;

  g_return_val_if_fail (SIM_IS_CONTEXT (context), 0);

  total = context->priv->total_events;
  eps = (total - context->priv->last_total) / elapsed_time;

  context->priv->last_total = total;

  return eps;
}

/**
 * sim_context_get_stats_5_minutes:
 * @context: a #SimContext object.
 * @elapse_time: elapsed time in seconds
 *
 * Returns guint with @context stats for the last 5 minutes.
 */
gfloat
sim_context_get_stats_5_minutes (SimContext *context,
                                 glong       elapsed_time)
{
  gfloat ret = -1;

  g_return_val_if_fail (SIM_IS_CONTEXT (context), 0);

  if (elapsed_time > 3000)
    goto exit;

  // Would be better an sliding window but this is simpler
  context->priv->elapsed_5_minutes += elapsed_time;
  if (context->priv->elapsed_5_minutes > 300)
  {
    ret = (context->priv->last_total - context->priv->total_5_minutes) / 300.0;
    context->priv->elapsed_5_minutes = elapsed_time;
    context->priv->total_5_minutes = context->priv->last_total;
  }

exit:
  return ret;
}

/**
 * sim_context_get_stats:
 * @context: a #SimContext object.
 *
 * Increments total events number in @context
 */
void
sim_context_inc_total_events (SimContext *context)
{
  g_return_if_fail (SIM_IS_CONTEXT (context));

  context->priv->total_events++;
}


/*
 * Debug print
 */

#if DEBUG_ENABLED

/**
 * sim_context_debug_print_all:
 * @context: a #SimContext object.
 *
 */
void
sim_context_debug_print_all (SimContext *context)
{
  g_return_if_fail (SIM_IS_CONTEXT (context));

  sim_context_debug_print_hosts (context);
  sim_context_debug_print_nets (context);
  sim_context_debug_print_policies (context);
}

/**
 * sim_context_debug_print_hosts:
 * @context: a #SimContext object.
 *
 */
void
sim_context_debug_print_hosts (SimContext *context)
{
  g_return_if_fail (SIM_IS_CONTEXT (context));

  ossim_debug ("Context %s %s -- Hosts:",
               sim_uuid_get_string (context->priv->id),
               context->priv->utf8_name);

  SIM_WHILE_HASH_TABLE (context->priv->hosts)
  {
    sim_host_debug_print (value);
  }
}

/**
 * sim_context_debug_print_nets:
 * @context: a #SimContext object.
 *
 */
void
sim_context_debug_print_nets (SimContext *context)
{
  g_return_if_fail (SIM_IS_CONTEXT (context));

  ossim_debug ("Context %s %s -- Nets:",
               sim_uuid_get_string (context->priv->id),
               context->priv->utf8_name);

  SIM_WHILE_HASH_TABLE (context->priv->nets)
  {
    sim_net_debug_print (value);
  }
}

/**
 * sim_context_debug_print_policies:
 * @context: a #SimContext object.
 *
 */
void
sim_context_debug_print_policies (SimContext *context)
{
  GList *node;

  g_return_if_fail (SIM_IS_CONTEXT (context));

  ossim_debug ("Context %s %s -- Policies:",
               sim_uuid_get_string (context->priv->id),
               context->priv->utf8_name);

  node = context->priv->policies;
  while (node)
  {
    SimPolicy *policy = SIM_POLICY (node->data);
    sim_policy_debug_print (policy);
    node = g_list_next (node);
  }
}


#endif /* DEBUG_ENABLED */


#ifdef USE_UNITTESTS

/*************************************************************
 *******************      Unit tests      ********************
 *************************************************************/

gboolean sim_context_test1 (void);
gboolean sim_context_test2 (void);
gboolean sim_context_test3 (void);

gboolean
sim_context_test1 (void)
{
  gboolean success = TRUE;

  SimUuid *id;
  SimContext *context;

  id = sim_uuid_new ();
  context = sim_context_new (id);
  g_object_unref (context);

  context = sim_context_new_full (id, "test", NULL);
  g_object_unref (context);

  g_object_unref (id);

  return success;
}

gboolean
sim_context_test2 (void)
{
  gboolean success = TRUE;
  SimContext *context;
  SimUuid *id;
  guint i;

  // Data for hosts
  struct
  {
    gchar  *ip;
    gchar  *name;
    gint    asset;
    gdouble c;
    gdouble a;
    SimUuid *id;

  } h_data[] = {
    {"192.168.1.1", "host1", 2, 1.0, 2.0, NULL},
    {"192.168.5.5", "host2", 3, 3.0, 4.0, NULL}};

  // Data for nets
  struct
  {
    gchar   *ip;
    gchar   *name;
    gint     asset;

  } n_data[] = {
    {"192.168.0.0/16,10.0.0.0/24", "net1", 5},
    {"10.0.0.0/16", "net2", 4}
  };

  // Ips in nets
  struct
  {
    gchar *ip;
    gint   asset;

  } n_ip[] = {
    {"10.0.120.100", 4},
    {"10.0.0.5", 5},
    {"192.168.200.2", 5}
  };

  // External ips
  gchar *ext_ip[] = {
    "192.10.10.10",
    "213.10.30.1"
  };

  id = sim_uuid_new ();
  context = sim_context_new (id);

  /* Append hosts */
  for (i = 0; i < G_N_ELEMENTS (h_data); i++)
  {
    SimInet *inet = sim_inet_new_from_string (h_data[i].ip);
    SimHost *host = sim_host_new (inet, NULL, h_data[i].name, h_data[i].asset, h_data[i].c, h_data[i].a);
    h_data[i].id = sim_host_get_id (host);
    g_object_unref (inet);

    sim_context_append_host (context, host);
  }

  /* Get host by inet */
  for (i = 0; i < G_N_ELEMENTS (h_data); i++)
  {
    SimInet *inet = sim_inet_new_from_string (h_data[i].ip);

    if (sim_context_get_host_by_inet (context, inet) == NULL)
    {
      g_print ("HOST NOT FOUND for %s\n", h_data[i].ip);

      success = FALSE;
    }
  }

  /* Append nets */
  for (i = 0; i < G_N_ELEMENTS (n_data); i++)
  {
    SimNet *net = sim_net_new (n_data[i].name, n_data[i].ip, n_data[i].asset);
    sim_context_append_net (context, net);
  }

  /* asset host find search */
  for (i = 0; i < G_N_ELEMENTS (h_data); i++)
  {
    gint asset;
    asset = sim_context_get_host_asset (context, h_data[i].id);
    if (h_data[i].asset != asset)
    {
      g_print ("ASSET ERROR for %s : context has %d and it must be %d\n",
               h_data[i].ip, asset, h_data[i].asset);

      success = FALSE;
    }
  }

  /* asset net ip find search */
  for (i = 0; i < G_N_ELEMENTS (n_ip); i++)
  {
    gint asset;
    SimInet *inet = sim_inet_new_from_string (n_ip[i].ip);

    asset = sim_context_get_inet_asset (context, inet);
    if (n_ip[i].asset != asset)
    {
      gchar *str_aux = sim_inet_get_canonical_name (inet);
      g_print ("ASSET ERROR for %s : context has %d and it must be %d\n",
               str_aux, asset, n_ip[i].asset);
      g_free (str_aux);

      success = FALSE;
    }
    g_object_unref (inet);
  }

  /* assert for external ips */
  for (i = 0; i < G_N_ELEMENTS (ext_ip); i++)
  {
    gint asset;
    SimInet *inet = sim_inet_new_from_string (ext_ip[i]);

    asset = sim_context_get_inet_asset (context, inet);
    if (asset != DEFAULT_ASSET)
    {
      gchar *str_aux = sim_inet_get_canonical_name (inet);
      g_print ("ASSET ERROR for %s : context has %d and it must be %d\n",
               str_aux, asset, DEFAULT_ASSET);
      g_free (str_aux);
      success = FALSE;
    }
    g_object_unref (inet);
  }

  g_object_unref (context);
  g_object_unref (id);

  return success;
}

gboolean
sim_context_test3 (void)
{
  gboolean success = TRUE;
  guint i;

  /* Plugin data */
  struct
  {
    SimPluginType type;
    gint          id;
    gchar        *name;
    gchar        *description;

  } plugin_data[] = {
    {SIM_PLUGIN_TYPE_NONE, 1, "plugin1", "plugin description"},
    {SIM_PLUGIN_TYPE_DETECTOR, 2, "plugin2", "plugin description"},
    {SIM_PLUGIN_TYPE_MONITOR, 3, "plugin3", "plugin description"},
    {SIM_PLUGIN_TYPE_DETECTOR, 4, "plugin4", "plugin description"}
  };

  /* Plugin sid data */
  struct
  {
    gint         id;
    gint         sid;
    gint         reliability;
    gint         priority;
    const gchar *name;

  } plugin_sid_data[] = {
    {1, 1, 1, 1, "pluginSid1-1"},
    {1, 2, 1, 2, "pluginSid1-2"},
    {1, 3, 3, 1, "pluginSid1-3"},
    {2, 1, 2, 2, "pluginSid2-1"},
    {3, 1, 4, 5, "pluginSid3-1"}
  };

  SimContext *context;
  SimPlugin *plugin;
  SimPluginSid *plugin_sid;
  SimUuid *id = sim_uuid_new ();

  context = sim_context_new (id);

  /* Search empty */
  plugin = sim_context_get_plugin (context, 1);
  if (plugin)
  {
    g_print ("ERROR searching plugin on empty context");
    success = FALSE;
    g_object_unref (plugin);
  }

  plugin_sid = sim_context_get_plugin_sid (context, 1, 1);
  if (plugin_sid)
  {
    g_print ("ERROR searching plugin sid on empty context");
    success = FALSE;
    g_object_unref (plugin_sid);
  }

  /* Add */
  for (i = 0; i < G_N_ELEMENTS (plugin_data); i++)
  {
    plugin = sim_plugin_new ();
    sim_plugin_set_sim_type (plugin, plugin_data[i].type);
    sim_plugin_set_id (plugin, plugin_data[i].id);
    sim_plugin_set_name (plugin, plugin_data[i].name);
    sim_plugin_set_description (plugin, plugin_data[i].description);

    g_hash_table_insert (context->priv->plugins, GINT_TO_POINTER (sim_plugin_get_id (plugin)), g_object_ref (plugin));
  }

  for (i = 0; i < G_N_ELEMENTS (plugin_sid_data); i++)
  {
    plugin_sid = sim_plugin_sid_new_from_data (plugin_sid_data[i].id,
                                               plugin_sid_data[i].sid,
                                               plugin_sid_data[i].reliability,
                                               plugin_sid_data[i].priority,
                                               plugin_sid_data[i].name);

    sim_context_add_plugin_sid (context, plugin_sid);
  }

  /* Search match */
  for (i = 0; i < G_N_ELEMENTS (plugin_data); i++)
  {
    plugin = sim_context_get_plugin (context, plugin_data[i].id);

    if (plugin)
    {
      g_object_unref (plugin);
    }
    else
    {
      g_print ("ERROR plugin %s not found in context", plugin_data[i].name);
      success = FALSE;
    }
  }

  for (i = 0; i < G_N_ELEMENTS (plugin_sid_data); i++)
  {
    plugin_sid = sim_context_get_plugin_sid (context,
                                             plugin_sid_data[i].id,
                                             plugin_sid_data[i].sid);

    if (plugin_sid)
    {
      g_object_unref (plugin_sid);
    }
    else
    {
      g_print ("ERROR plugin sid %s not found in context", plugin_sid_data[i].name);
      success = FALSE;
    }
  }

  /* free */
  g_object_unref (context);

  g_object_unref (id);

  return success;
}

void
sim_context_register_tests (SimUnittesting *engine)
{
  sim_unittesting_append (engine, "sim_context_test1 - New", sim_context_test1, TRUE);
  sim_unittesting_append (engine, "sim_context_test2 - Host, Nets, asset", sim_context_test2, TRUE);
  sim_unittesting_append (engine, "sim_context_test3 - Plugins and Plugin Sids", sim_context_test3, TRUE);
}
#endif /* USE_UNITTESTS */

// vim: set tabstop=2:

