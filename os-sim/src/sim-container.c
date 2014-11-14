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

#include "sim-container.h"

#include <netdb.h>
#include <glib/gstdio.h>
#include <glib.h>

#include "os-sim.h"
#include "sim-xml-directive.h"
#include "sim-sensor.h"
#include "sim-enums.h"
#include "sim-config.h"
#include "sim-util.h"
#include "sim-net.h"
#include "sim-inet.h"
#include "sim-directive.h"
#include "sim-network.h"
#include "sim-db-command.h"
#include "sim-debug.h"
#include "sim-uuid.h"

G_LOCK_DEFINE (s_mutex_config);
G_LOCK_DEFINE (s_mutex_inets);
G_LOCK_DEFINE (s_mutex_events);

extern guint sem_total_events_popped;

/* Prototypes */
static void       sim_container_add_sensor_to_hash_table_ul  (SimContainer * container,
                                                              SimSensor    * sensor);
static void       sim_container_free_servers              (SimContainer     *container);
static void       sim_container_load_taxonomy_products    (SimContainer     *container);


extern SimMain  ossim;


struct _SimContainerPrivate
{
  GList *common_plugins;
  GList *common_plugin_sids;

  GList      *servers;          // SimServer objects.
  GHashTable *sensors;          // SimSensor Hash Table
  GHashTable *sensor_ids;
  GMutex     *mutex_sensors;

  GAsyncQueue *delete_backlogs; // Queue to delete backlogs that are timeout'ed. Each element is a list of some backlogs.

  GHashTable *sensor_sid;
  GHashTable *signatures_to_id; //A Signature cache, to prevent massive sql select statements in the event storage process

  GList *plugin_references;     //cross correlation. Relations between one and another plugins.(snort/nessus ie.)

  guint max_sid;                //Store the max sid os snort.sensor table. Used to insert directly new sensors without a second query to view the assigned id (sensor id aka sid)

  GAsyncQueue *ar_events;       // This queue is for action/response events

  GAsyncQueue *events;          // This one for received events from the agent sessions
  gint         events_len;
  guint        events_count;     // Approximate count of acid_event rows.
  gint         discarded_events; // Number of discarded events.

  GCond  *cond_ar_events;       //  For action responses queue
  GMutex *mutex_ar_events;      // For action responses queue

  GSemaphore *recv_sema;        // Semaphore for the reception queue

  guint event_seq;              //Cached last id
  guint backlog_seq;            //Cached last id

  GQueue *monitor_rules;
  GCond  *cond_monitor_rules;
  GMutex *mutex_monitor_rules;

  SimEngine  *engine;
  SimContext *context;
  SimContext *engine_ctx;

  // Taxonomy products
  GHashTable *taxonomy_products;
};

typedef
struct _SimContainerHostSourceRef
{
  gchar * name;
  gint relevance;
}
  SimContainerHostSourceRef;

static gpointer parent_class = NULL;


/* GType Functions */

static void
sim_container_impl_dispose (GObject  *gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void
sim_container_impl_finalize (GObject  *gobject)
{
  SimContainer  *container = SIM_CONTAINER (gobject);

  if (container->_priv->common_plugins)
  {
    g_list_foreach (container->_priv->common_plugins, (GFunc)g_object_unref, NULL);
    g_list_free (container->_priv->common_plugins);
    container->_priv->common_plugins = NULL;
  }
  if (container->_priv->common_plugin_sids)
  {
    g_list_foreach (container->_priv->common_plugin_sids, (GFunc)g_object_unref, NULL);
    g_list_free (container->_priv->common_plugin_sids);
    container->_priv->common_plugin_sids = NULL;
  }

  sim_container_free_events (container);

  sim_container_free_servers (container);

  g_hash_table_destroy(container->_priv->sensor_sid);
  sim_container_free_signatures_to_id(container); //A Signature cache, to prevent massive sql select statements in the event storage process

  g_cond_free (container->_priv->cond_ar_events);
  g_mutex_free (container->_priv->mutex_ar_events);

  g_cond_free (container->_priv->cond_monitor_rules);
  g_mutex_free (container->_priv->mutex_monitor_rules);

  g_semaphore_free(container->_priv->recv_sema); // Semaphore for the reception queue

  if (container->_priv->engine)
    g_object_unref (container->_priv->engine);
  if (container->_priv->context)
    g_object_unref (container->_priv->context);
  if (container->_priv->engine_ctx)
    g_object_unref (container->_priv->engine_ctx);

  if (container->_priv->taxonomy_products)
    g_hash_table_destroy (container->_priv->taxonomy_products);

  g_free (container->_priv);

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_container_class_init (SimContainerClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_ref (G_TYPE_OBJECT);

  object_class->dispose = sim_container_impl_dispose;
  object_class->finalize = sim_container_impl_finalize;
}


static void
sim_container_instance_init (SimContainer *container)
{
  container->_priv = g_new0 (SimContainerPrivate, 1);

  container->_priv->common_plugins = NULL;
  container->_priv->common_plugin_sids = NULL;

  container->_priv->delete_backlogs = g_async_queue_new ();
  container->_priv->ar_events = g_async_queue_new ();

  container->_priv->sensor_sid = g_hash_table_new_full (g_str_hash, g_str_equal, g_free, NULL);

  container->_priv->events = g_async_queue_new_full (g_object_unref);
  container->_priv->events_len = 0;
  container->_priv->events_count = 0;
  container->_priv->discarded_events = 0;

  container->_priv->servers = NULL;
  container->_priv->sensors = g_hash_table_new_full (sim_inet_hash, sim_inet_equal,
                                                     NULL, g_object_unref);
  container->_priv->sensor_ids = g_hash_table_new_full (sim_uuid_hash, sim_uuid_equal,
                                                        NULL, g_object_unref);
  container->_priv->mutex_sensors = g_mutex_new ();

  /* For action responses mutex and cond */
  container->_priv->cond_ar_events = g_cond_new ();
  container->_priv->mutex_ar_events = g_mutex_new ();

 // Semaphore for the reception queue
  container->_priv->recv_sema = g_semaphore_new_with_value (MAX_RECEPTION_QUEUE_LENGTH);

  // Mutex Monitor rules Init
  container->_priv->monitor_rules = g_queue_new ();
  container->_priv->cond_monitor_rules = g_cond_new ();
  container->_priv->mutex_monitor_rules = g_mutex_new ();

  container->_priv->event_seq = 0;
  container->_priv->backlog_seq = 0;

  container->_priv->engine = NULL;
  container->_priv->context = NULL;
  container->_priv->engine_ctx = NULL;

  container->_priv->taxonomy_products = g_hash_table_new_full (g_direct_hash, g_direct_equal,
                                                               NULL, (GDestroyNotify) g_list_free);

}

/* Public Methods */

guint
sim_container_get_backlog_id(SimContainer *container)
{
  return container->_priv->backlog_seq;
}

void
sim_container_set_backlog_id(SimContainer *container, guint a)
{
  container->_priv->backlog_seq=a;
}

guint
sim_container_next_backlog_id(SimContainer *container)
{
  return ++container->_priv->backlog_seq;
}

guint
sim_container_get_event_id(SimContainer *container)
{
  return container->_priv->event_seq;
}

void
sim_container_set_event_id(SimContainer *container, guint a)
{
  container->_priv->event_seq=a;
}

guint
sim_container_next_event_id(SimContainer *container)
{
  return ++container->_priv->event_seq;
}

GType
sim_container_get_type (void)
{
  static GType object_type = 0;

  if (!object_type)
  {
    static const GTypeInfo type_info = {
      sizeof (SimContainerClass),
      NULL,
      NULL,
      (GClassInitFunc) sim_container_class_init,
      NULL,
      NULL,                       /* class data */
      sizeof (SimContainer),
      0,                          /* number of pre-allocs */
      (GInstanceInitFunc) sim_container_instance_init,
      NULL                        /* value table */
    };

    g_type_init ();

    object_type = g_type_register_static (G_TYPE_OBJECT, "SimContainer", &type_info, 0);
  }

  return object_type;
}

/*
 *
 *
 *
 *
 */
SimContainer*
sim_container_new ()
{
  SimContainer *container = NULL;

  container = SIM_CONTAINER (g_object_new (SIM_TYPE_CONTAINER, NULL));

  return container;
}

/**
 * sim_container_init:
 * @container: a #SimContainer
 * @config: a #SimConfig
 * @database: a #SimDatabase
 *
 * Loads all data from @config and @database in @container
 */
gboolean
sim_container_init (SimContainer *container,
                    SimConfig    *config,
                    SimDatabase  *database)
{
  g_return_val_if_fail (SIM_IS_CONTAINER (container), FALSE);
  g_return_val_if_fail (SIM_IS_CONFIG (config), FALSE);
  g_return_val_if_fail (SIM_IS_DATABASE (database), FALSE);

  sim_db_update_server_version (database, ossim.version);



  g_message ("Loading common plugins");
  container->_priv->common_plugins = sim_db_load_common_plugins (database);

  g_message ("Loading common plugin sids");
  container->_priv->common_plugin_sids = sim_db_load_common_plugin_sids (database);

  // Loads servers
  if (!sim_container_load_servers (container, database))
    return FALSE;

  // Load sensors.
  sim_container_load_sensors (container);

  /* Loads engine */
  sim_container_load_engine (container, config);

  /* Loads Taxonomy products */
  sim_container_load_taxonomy_products (container);

  /* Loads context */
  sim_container_load_context (container, config);

  // Loads the event count.
  sim_container_db_load_events_count (container);

  //sim_container_db_load_plugin_references (container, database); //used for cross correlation

  config->copy_siem_events = sim_db_get_config_bool (database, "copy_siem_events");

  sim_container_debug_print_servers (container);

  ossim_debug ("%s: End loading data.", __func__);

  return TRUE;
}

/*
 *
 *
 *
 *
 */
void
sim_container_db_delete_plugin_sid_directive_ul (SimContainer  *container,
                                                 SimDatabase   *database)
{
  gchar         *query = "DELETE FROM plugin_sid WHERE plugin_id = 1505";

  g_return_if_fail (SIM_IS_CONTAINER (container));
  g_return_if_fail (SIM_IS_DATABASE (database));

  sim_database_execute_no_query (database, query);
}

/*
 * Here we check if in the plugin_reference table, once provided a reference_id and a reference_sid,
 * it's the same than the values inside plugin_reference.
 */
gboolean
sim_container_db_plugin_reference_match (SimContainer  *container,
                                         SimDatabase   *database,
                                         gint           plugin_id,
                                         gint           plugin_sid,
                                         gint           reference_id,
                                         gint           reference_sid)
{
  GdaDataModel *dm;
  const GValue *value;
  gchar        *query;
  gint          row;
  gint          cmp_plugin_id;
  gint          cmp_plugin_sid;

  g_return_val_if_fail (SIM_IS_CONTAINER (container), 0);
  g_return_val_if_fail (SIM_IS_DATABASE (database), 0);

  query = g_strdup_printf ("SELECT plugin_id, plugin_sid FROM plugin_reference WHERE reference_id = %d AND reference_sid = %d",
                           reference_id, reference_sid);


  dm = sim_database_execute_single_command (database, query);
  if (dm)
  {
    for (row = 0; row < gda_data_model_get_n_rows (dm); row++)
    {
      value = gda_data_model_get_value_at (dm, 0, row, NULL);
      cmp_plugin_id = g_value_get_int (value);

      value = gda_data_model_get_value_at (dm, 1, row, NULL);
      cmp_plugin_sid = g_value_get_int (value);

      if ((cmp_plugin_id == plugin_id) && (cmp_plugin_sid == plugin_sid))
      {
        g_free (query);
        g_object_unref(dm);
        return TRUE;
      }
    }
    g_object_unref(dm);
  }
  else
    g_message ("OSVDB DATA MODEL ERROR");

  g_free (query);
  return FALSE;
}

/*
 * Given a osvdb_id, returns a list with all the OSVDB version_name ("7.3.4 Rc3" i.e.)
 */
GList*
sim_container_db_get_osvdb_version_name (SimDatabase   *database,
                                         gint           osvdb_id)
{
  GdaDataModel  *dm;
  gint           row;
  GList         *list = NULL;
  const GValue  *value;
  gchar         *query;
  gchar         *version_name = NULL;

  g_return_val_if_fail (SIM_IS_DATABASE (database), NULL);

  query = g_strdup_printf ("SELECT version_name FROM object_version "
                           "LEFT JOIN (object_correlation, object) "
                           "ON (object_version.version_id = object_correlation.version_id "
                           "AND object_correlation.corr_id = object.corr_id) "
                           "WHERE object.osvdb_id= %d", osvdb_id);

  ossim_debug ("sim_container_db_get_osvdb_version_name query: %s", query);

  dm = sim_database_execute_single_command (database, query);
  if (dm)
  {
    for (row = 0; row < gda_data_model_get_n_rows (dm); row++)
    {
      value = gda_data_model_get_value_at (dm, 0, row, NULL);
      version_name = g_value_dup_string (value);
      if (version_name)
        list = g_list_append (list, version_name);
    }
    g_object_unref(dm);
  }
  else
    g_message ("OSVDB DATA MODEL ERROR");

  g_free (query);

  return list;
}

/*
 * Given a osvdb_id, returns a list with all the possible OSVDB base_name ("wu-ftpd" i.e.)
 */
GList*
sim_container_db_get_osvdb_base_name (SimDatabase   *database,
                                      gint           osvdb_id)
{
  GdaDataModel  *dm;
  gint          row;
  GList         *list = NULL;
  const GValue  *value;
  gchar         *query;
  gchar         *base_name = NULL;

  g_return_val_if_fail (SIM_IS_DATABASE (database), NULL);

  query = g_strdup_printf ("SELECT base_name FROM object_base "
                           "LEFT JOIN (object_correlation, object) "
                           "ON (object_base.base_id = object_correlation.base_id "
                           "AND object_correlation.corr_id = object.corr_id) "
                           "WHERE object.osvdb_id= %d", osvdb_id);

  ossim_debug ( "sim_container_db_get_osvdb_base_name query: %s", query);

  dm = sim_database_execute_single_command (database, query);
  if (dm)
  {
    for (row = 0; row < gda_data_model_get_n_rows (dm); row++)
    {
      value = gda_data_model_get_value_at (dm, 0, row, NULL);
      base_name = g_value_dup_string (value);

      ossim_debug ( "sim_container_db_get_osvdb_base_name base_name: -%s-", base_name);

      if (base_name)
        list = g_list_append (list, base_name);
    }
    g_object_unref(dm);
  }
  else
    g_message ("OSVDB DATA MODEL ERROR");

  g_free (query);

  return list;
}

/**
 * sim_container_get_storage_type:
 * @container: a #SimContainer object.
 *
 * Dumb method.
 */
gint
sim_container_get_storage_type (SimContainer * container)
{
  (void)container;
  return (1);
}


/**
 * sim_container_get_engine:
 * @container: #SimContainer object
 *
 * This adds a reference to the engine, so it must be unref
 * outside.
 *
 * Returns the #SimEngine object with @engine_id
 */
SimEngine *
sim_container_get_engine (SimContainer *container,
                          SimUuid      *engine_id)
{
  g_return_val_if_fail (SIM_IS_CONTAINER (container), NULL);

  (void) engine_id;

  return g_object_ref (container->_priv->engine);
}


/**
 * sim_container_get_engine_for_context:
 * @container: #SimContainer object
 * @engine_id: context unique id.
 *
 * This adds a reference to the engine, so it must be unref
 * outside.
 *
 * Returns the #SimEngine object with @engine_id
 */
SimEngine *
sim_container_get_engine_for_context (SimContainer *container,
                                      SimUuid      *context_id)
{
  g_return_val_if_fail (SIM_IS_CONTAINER (container), NULL);

  (void) context_id;

  return g_object_ref (container->_priv->engine);
}

/**
 * sim_container_load_engine:
 * @container: #SimContainer object
 * @config: server configuration #SimConfig object
 *
 * Create the #SimEngine object
 */
void
sim_container_load_engine (SimContainer *container,
                           SimConfig    *config)
{
  g_message ("Loading engine");
  container->_priv->engine = sim_engine_new (config->default_engine_id,
                                             "DEFAULT_ENGINE");
  sim_engine_set_database (container->_priv->engine, ossim.dbossim);

  /* Delete directives (1505 plugins) from plugin_sid table */
  sim_db_delete_directives (ossim.dbossim, sim_engine_get_id (container->_priv->engine));

  sim_engine_load_all (container->_priv->engine);
}

/**
 * sim_container_get_context:
 * @container: #SimContainer object
 * @context_id: context unique id.
 *
 * This adds a reference to the context, so it must be unref
 * outside.
 *
 * Returns the #SimContext object with @context_id
 */
SimContext *
sim_container_get_context (SimContainer *container,
                           SimUuid      *context_id)
{
  g_return_val_if_fail (SIM_IS_CONTAINER (container), NULL);

  (void) context_id;

  return g_object_ref (container->_priv->context);
}

/**
 * sim_container_get_context_by_name:
 * @container: #SimContainer object
 * @context_name: gchar * with context name
 *
 * This adds a reference to the context, so it must be unref
 * outside.
 *
 * Returns the #SimContext object with @context_name
 */
SimContext *
sim_container_get_context_by_name (SimContainer *container,
                                   gchar        *context_name)
{
  g_return_val_if_fail (SIM_IS_CONTAINER (container), NULL);

  if (strcmp (context_name, sim_context_get_name (container->_priv->context)) == 0)
    return g_object_ref (container->_priv->context);

  return NULL;
}

/**
 * sim_container_get_engine_ctx:
 * @container: #SimContainer object
 *
 * Adds a reference to the context, so it must be unref
 * outside.
 *
 * Returns the #SimContext object with @context_name
 */
SimContext *
sim_container_get_engine_ctx (SimContainer *container)
{
  g_return_val_if_fail (SIM_IS_CONTAINER (container), NULL);

  return g_object_ref (container->_priv->engine_ctx);
}


/**
 * sim_container_load_context:
 * @container: #SimContainer object
 * @config: server configuration #SimConfig object
 *
 * Create the #SimContext object
 */
void
sim_container_load_context (SimContainer *container,
                            SimConfig    *config)
{
  g_message ("Loading  context");
  container->_priv->context = sim_context_new_full (config->default_context_id,
                                                    "DEFAULT_CONTEXT",
                                                    ossim.dbossim);
  sim_context_load_all (container->_priv->context);

  sim_engine_add_context (container->_priv->engine, container->_priv->context);

  /* Load engine directives plugin_sids in context plugin_sids */
  sim_context_load_directive_plugin_sids (container->_priv->context,
                                          sim_engine_get_id (container->_priv->engine));

  /* Now we can expand the engine directives */
  sim_engine_expand_directives (container->_priv->engine);

  container->_priv->engine_ctx = sim_context_new_full (config->default_engine_id,
                                                    "DEFAULT_ENGINE",
                                                    ossim.dbossim);
  sim_context_external_load_all (container->_priv->engine_ctx);
}


/**
 * sim_container_get_common_plugins:
 * @container: a #SimContainer
 *
 * Get the list of common plugins
 *
 * Returns: GList with all common #SimPlugins
 */
GList *
sim_container_get_common_plugins (SimContainer *container)
{
  g_return_val_if_fail (SIM_IS_CONTAINER (container), NULL);

  return container->_priv->common_plugins;
}

/**
 * sim_container_get_common_plugin_sids:
 * @container: the #SimContainer
 *
 * Returns: the list with all common plugin_sids
 */
GList *
sim_container_get_common_plugin_sids (SimContainer  *container)
{
  g_return_val_if_fail (SIM_IS_CONTAINER (container), NULL);

  return container->_priv->common_plugin_sids;
}

/*
 *
 *
 *
 *
 */
//FIXME: (Ticket #2707) Remove this when the agent send the plugin sids for these events
SimPluginSid*
sim_container_get_plugin_sid_by_name (SimContainer  *container,
                                      gint           plugin_id,
                                      const gchar   *name)
{
  SimPluginSid *plugin_sid = NULL;
  GList        *list;

  g_return_val_if_fail (SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail (plugin_id > 0, NULL);
  g_return_val_if_fail (name, NULL);

  list = container->_priv->common_plugin_sids;
  while (list)
  {
    plugin_sid = (SimPluginSid *)list->data;

    if ((sim_plugin_sid_get_plugin_id (plugin_sid) == plugin_id) &&
        (!strcmp (name, sim_plugin_sid_get_name (plugin_sid))))
    {
      return plugin_sid;
    }

    list = g_list_next (list);
  }

  return NULL;
}

/**
 * sim_container_load_servers:
 * @container: a #SimContainer
 * @database: a #SimDatabase
 *
 */
gboolean
sim_container_load_servers (SimContainer *container,
                            SimDatabase  *database)
{
  GList *server_list;
  SimRole *server_role;
  gint server_num = 0;

  g_message ("Loading servers");

  server_list = sim_db_load_servers (database);
  while (server_list)
  {
    SimServer *server = (SimServer *) server_list->data;

    container->_priv->servers = g_list_append (container->_priv->servers, server);
    server_num++;

    /* Assign local server */
    if (sim_server_is_local (server))
      ossim.server = server;

    server_list = g_list_next (server_list);
  }

  if (!ossim.server)
  {
    g_critical ("Error loading local server. You should check the server configuration in database.");
    return FALSE;
  }

  /* Load local server role */
  server_role = sim_db_load_server_role (database,
                                         sim_server_get_id (ossim.server));
  sim_config_set_server_role (ossim.config, server_role);

  g_message ("%d servers loaded", server_num);

  return TRUE;
}

/**
 * sim_container_reload_servers:
 * @container: a #SimContainer
 * @database: a #SimDatabase
 *
 */
gboolean
sim_container_reload_servers (SimContainer *container,
                              SimDatabase  *database)
{
  GList *server_list;
  GList *new_servers = NULL;
  GList *list;
  GList *old_data;

  g_message ("Reloading server");

  old_data = container->_priv->servers;

  server_list = sim_db_load_servers (database);
  list = server_list;

  /* Search for local server */
  while (list)
  {
    SimServer *server = (SimServer *) list->data;

    if (sim_server_is_local (server))
    {
      /* Update name, ip address, port and role */
      sim_server_set_name (ossim.server, sim_server_get_name (server));
      sim_server_set_ip (ossim.server, sim_server_get_ip (server));
      sim_server_set_port (ossim.server, sim_server_get_port (server));
      sim_server_reload_role (ossim.server);

      g_object_unref (server);

      new_servers = g_list_prepend (new_servers, g_object_ref (ossim.server));
    }
    else
    {
      new_servers = g_list_prepend (new_servers, server);
    }

    list = g_list_next (list);
  }

  g_atomic_pointer_set (&container->_priv->servers, new_servers);

  /* Remove previous data */
  if (old_data)
  {
    g_list_foreach (old_data, (GFunc)g_object_unref, NULL);
    g_list_free (old_data);
  }

  return TRUE;
}


/**
 * sim_container_free_servers:
 * @container: a #SimContainer
 *
 */
static void
sim_container_free_servers (SimContainer  *container)
{
  if (container->_priv->servers)
  {
    g_list_foreach (container->_priv->servers, (GFunc) g_object_unref, NULL);
    g_list_free (container->_priv->servers);
    container->_priv->servers = NULL;
  }
}

/*
 * Sensors.
 */

/**
 * sim_container_load_sensors:
 * @container: a #SimContainer.
 *
 * Load sensors in @container from database
 * Thread safe
 */
void
sim_container_load_sensors (SimContainer * container)
{
  GList *sensor_list;
  GList *node;
  gint   sensor_num = 0;

  g_message ("Loading sensors");
  sensor_list = sim_db_load_sensors (ossim.dbossim);
  node = sensor_list;

  g_mutex_lock (container->_priv->mutex_sensors);
  while (node)
  {
    SimSensor *sensor = (SimSensor *) node->data;
    sim_container_add_sensor_to_hash_table_ul (container, sensor);
    g_object_unref (sensor);

    sensor_num ++;
    node = g_list_next (node);
  }
  g_mutex_unlock (container->_priv->mutex_sensors);

  g_list_free (sensor_list);

  g_message ("%d sensors loaded", sensor_num);
  return;
}

/**
 * sim_container_reload_sensors:
 * @container: a #SimContainer
 *
 * ReLoad sensors in @container from database.
 * Thread safe.
 */
void
sim_container_reload_sensors (SimContainer * container)
{
  GList *sensor_list;
  GList *node;
  GHashTable *new_data;
  GHashTable *new_data_id;
  GHashTable *previous_data;
  GHashTable *previous_data_id;
  SimInet *inet;
  gint sensor_num = 0;

  g_return_if_fail (SIM_IS_CONTAINER (container));

  g_message ("Reloading sensors");
  sensor_list = sim_db_load_sensors (ossim.dbossim);
  node = sensor_list;

  new_data = g_hash_table_new_full (sim_inet_hash, sim_inet_equal,
                                    NULL, g_object_unref);
  new_data_id = g_hash_table_new_full (sim_uuid_hash, sim_uuid_equal,
                                       NULL, g_object_unref);

  while (node)
  {
    SimSensor *sensor = (SimSensor *) node->data;
    inet = sim_sensor_get_ia (sensor);
    g_hash_table_insert (new_data, inet, g_object_ref(sensor));
    g_hash_table_insert (new_data_id, sim_sensor_get_id (sensor), g_object_ref(sensor));
    g_object_unref(sensor); // Freeing sensor_list memory correctly

    node = g_list_next (node);
    sensor_num++;
  }
  g_list_free (sensor_list);

  g_mutex_lock (container->_priv->mutex_sensors);
  previous_data = container->_priv->sensors;
  previous_data_id = container->_priv->sensor_ids;
  container->_priv->sensors = new_data;
  container->_priv->sensor_ids = new_data_id;
  g_mutex_unlock (container->_priv->mutex_sensors);

  /* Remove previous data */
  if (previous_data)
    g_hash_table_destroy (previous_data);

  if (previous_data_id)
    g_hash_table_destroy (previous_data_id);

  g_message ("%d sensors reloaded", sensor_num);
  return;
}

/**
 * sim_container_add_sensor_to_hash_table_ul:
 * @container: #SimContainer object
 * @hash_table: a #GHashTable
 *
 * Inserts @sensor in @hash_table using sensor inet as hash.
 */
static void
sim_container_add_sensor_to_hash_table_ul (SimContainer * container,
                                           SimSensor    * sensor)
{
  SimInet * inet = sim_sensor_get_ia (sensor);

  g_return_if_fail (SIM_IS_SENSOR (sensor));
  g_return_if_fail (inet);

  /* Insert sensor into hash table */
  g_hash_table_insert (container->_priv->sensors, inet, g_object_ref (sensor));
  g_hash_table_insert (container->_priv->sensor_ids, sim_sensor_get_id (sensor), g_object_ref (sensor));
}

/**
 * sim_container_add_sensor_to_hash_table:
 * @container: #SimContainer object
 * @hash_table: a #GHashTable
 *
 * Inserts @sensor in @hash_table using sensor inet as hash.
 * Thread safe.
 */
void
sim_container_add_sensor_to_hash_table (SimContainer * container,
					SimSensor    * sensor)
{
  g_mutex_lock (container->_priv->mutex_sensors);
  sim_container_add_sensor_to_hash_table_ul (container, sensor);
  g_mutex_unlock (container->_priv->mutex_sensors);
}


/**
 * sim_container_get_sensor_by_inet:
 * @container: a #SimContainer
 * @inet: a #SimInet
 *
 * Check every sensor defined previously (they're inside the container)
 * to see if the ia (internet address) matches with it. Then returns the sensor
 * as an object.
 *
 * This adds a reference to the sensor so, it must be unref outside
 * when is no longer in use
 *
 * Returns: #SimSensor object with @inet if it's in @container
 *          or %NULL otherwise.
 * Thread safe
 */
SimSensor *
sim_container_get_sensor_by_inet (SimContainer  *container,
                                  SimInet     *inet)
{
  SimSensor *sensor = NULL;

  g_return_val_if_fail (SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail (SIM_IS_INET (inet), NULL);

  g_mutex_lock (container->_priv->mutex_sensors);

  sensor = g_hash_table_lookup (container->_priv->sensors, inet);
  if (sensor)
    g_object_ref (sensor);

  g_mutex_unlock (container->_priv->mutex_sensors);

  return sensor;
}

/**
 * sim_container_get_sensor_by_name:
 * @container: a #SimContainer
 * @name: const gchar* with the sensor name
 *
 * This adds a reference to the sensor so, it must be unref outside
 * when is no longer in use
 *
 * Returns: #SimNet object with the sensor name if it's in the @container
 *  or %NULL otherwise.
 * Thread safe
 */
SimSensor *
sim_container_get_sensor_by_name (SimContainer  *container,
                                  const gchar *name)
{
  SimSensor *sensor_matched = NULL;
  GHashTableIter iter;
  gpointer key, value;

  g_return_val_if_fail (SIM_IS_CONTAINER (container), NULL);
  g_return_val_if_fail (name, NULL);

  g_mutex_lock (container->_priv->mutex_sensors);

  g_hash_table_iter_init (&iter, container->_priv->sensors);
  while (g_hash_table_iter_next (&iter, &key, &value))
  {
    SimSensor *sensor = (SimSensor *)value;
    if (g_ascii_strcasecmp (sim_sensor_get_name (sensor), name) == 0)
    {
      sensor_matched = g_object_ref (sensor);
      break;
    }
  }

  g_mutex_unlock (container->_priv->mutex_sensors);

  return sensor_matched;
}

/**
 * sim_container_set_sensor_by_id:
 * @container: a #SimContainer
 * @sensor: a #SimSensor.
 *
 * Adds @sensor to the sensor id table.
 * Thread safe
 */
void
sim_container_set_sensor_by_id (SimContainer * container,
                                SimSensor    * sensor)
{
  g_mutex_lock (container->_priv->mutex_sensors);
  g_hash_table_insert (container->_priv->sensor_ids, sim_sensor_get_id (sensor), g_object_ref (sensor));
  g_mutex_unlock (container->_priv->mutex_sensors);

  return;
}

/**
 * sim_container_get_sensor_by_id:
 * @container: a #SimContainer
 * @id: a #SimUuid object.
 *
 * Returns: #SimSensor identified by @id
 * or %NULL otherwise.
 * Thread safe
 */
SimSensor *
sim_container_get_sensor_by_id (SimContainer * container,
                                SimUuid      * id)
{
  SimSensor * sensor = NULL;
  if (!id)
    return (NULL);

  g_mutex_lock (container->_priv->mutex_sensors);
  if ((sensor = g_hash_table_lookup (container->_priv->sensor_ids, id)))
    sensor = g_object_ref (sensor);
  g_mutex_unlock (container->_priv->mutex_sensors);
  return (sensor);
}

/*
 * Taxonomy products
 */

/**
 * sim_container_load_taxonomy_products:
 * @container: a #SimContainer.
 *
 * Load taxonomy products in @container from database
 */
static void
sim_container_load_taxonomy_products (SimContainer *container)
{
  GList *products;
  GList *plugin_ids;
  SimUuid *common_context_id;

  common_context_id = sim_uuid_new_from_string (SIM_CONTEXT_COMMON);

  products = sim_db_load_taxonomy_products (ossim.dbossim);
  while (products)
  {
    gint product_id = GPOINTER_TO_INT (products->data);

    plugin_ids = sim_db_load_plugin_ids_with_product (ossim.dbossim,
                                                      product_id,
                                                      common_context_id);
    g_hash_table_insert (container->_priv->taxonomy_products,
                         GINT_TO_POINTER (product_id),
                         plugin_ids);

    products = g_list_next (products);
  }
}

/**
 * sim_container_get_taxonomy_product:
 * @container: a #SimContainer.
 * @product_id: Taxonomy product id
 *
 * Returns GList * with plugin_ids with taxonomy @product_id in @container
 */
GList *
sim_container_get_taxonomy_product (SimContainer *container,
                                    gint          product_id)
{
  GList *plugin_ids;

  g_return_val_if_fail (SIM_IS_CONTAINER (container), NULL);

  plugin_ids = (GList *) g_hash_table_lookup (container->_priv->taxonomy_products,
                                              GINT_TO_POINTER (product_id));

  return plugin_ids;
}

/*
 * Events.
 */

/**
 * sim_container_push_event:
 * @container: a SimContainer object.
 * @event: a SimEvent object.
 *
 * Push the event to the organizer extract queue.
 */
void
sim_container_push_event (SimContainer * container,
                          SimEvent     * event)
{
  g_return_if_fail (SIM_IS_CONTAINER (container));

  g_semaphore_down(container->_priv->recv_sema);
  g_async_queue_push (container->_priv->events, sim_event_ref (event));
  g_atomic_int_inc (&container->_priv->events_len);

  sim_engine_inc_events_in_queue (container->_priv->engine);
}

/**
 * sim_container_get_events_in_queue:
 * @container: #SimContainer object
 *
 * Returns number of events in queue
 * Thread safe
 */
gint
sim_container_get_events_in_queue (SimContainer *container)
{
  return sim_engine_get_events_in_queue (container->_priv->engine);
}

void
sim_container_push_ar_event (SimContainer  *container,
                             SimEvent    *event)
{
  g_return_if_fail (SIM_IS_CONTAINER (container));
  g_return_if_fail (SIM_IS_EVENT (event));

  ossim_debug ( "sim_container_push_ar_event: pushed event %p ", event);

  //g_mutex_lock (container->_priv->mutex_ar_events);
  g_async_queue_push (container->_priv->ar_events, sim_event_ref(event));
  //g_mutex_unlock (container->_priv->mutex_ar_events);
}

void
sim_container_push_delete_backlog_from_db (SimContainer * container,
                                           gchar        * backlog_id_alarm_stats)
{
  g_return_if_fail (SIM_IS_CONTAINER (container));

  g_async_queue_push (container->_priv->delete_backlogs, backlog_id_alarm_stats);
}

/**
 * sim_container_push_event_noblock:
 * @container: a SimContainer object.
 * @event: a SimEvent object.
 *
 * Needed for the correlation, which inserts here directive_events without stopping the sessions.
 */
void
sim_container_push_event_noblock (SimContainer * container,
                                  SimEvent     * event)
{
  g_return_if_fail (SIM_IS_CONTAINER (container));

  g_async_queue_push (container->_priv->events, sim_event_ref (event));
  g_atomic_int_inc (&container->_priv->events_len);

  sim_engine_inc_events_in_queue (event->engine);
}

gchar *
sim_container_pop_delete_backlog_from_db (SimContainer  *container)
{
  g_return_val_if_fail (SIM_IS_CONTAINER (container), NULL);

  return (gchar *) g_async_queue_pop (container->_priv->delete_backlogs);
}

/**
 * sim_container_pop_event:
 * @container: a SimContainer object.
 *
 * Return value: a SimEvent object from the event queue.
 */
SimEvent *
sim_container_pop_event (SimContainer * container)
{
  SimEvent * event = NULL;

  g_return_val_if_fail (SIM_IS_CONTAINER (container), NULL);

  ossim_debug ( "sim_container_pop_event: Extracting");
  event = (SimEvent *) g_async_queue_pop (container->_priv->events);
  (void)g_atomic_int_dec_and_test (&container->_priv->events_len);
  g_semaphore_up(container->_priv->recv_sema);

  ossim_debug ("%s: Pop event from queue event->id: %s",__func__, sim_uuid_get_string (event->id));

  sim_engine_dec_events_in_queue (container->_priv->engine);
  return (event);
}

SimEvent*
sim_container_pop_ar_event (SimContainer  *container)
{
  SimEvent *event;
  static gulong total=0;

  g_return_val_if_fail (SIM_IS_CONTAINER (container), NULL);

//  g_mutex_lock (container->_priv->mutex_ar_events);

  if (total++%50000==0)
  {
    ossim_debug ( "sim_container_pop_ar_event: Popped event %lu", total);
    ossim_debug ( "sim_container_pop_ar_event: Events in the event queue %u",
                  g_async_queue_length(container->_priv->ar_events));
  }

  if (total>=G_MAXULONG-1)
  {
    g_message("sim_container_pop_ar_event: Restarting event counter since it reached its max value (ok) %lu", total);
    total=0;
  }

  event = (SimEvent *) g_async_queue_pop (container->_priv->ar_events);
//  g_mutex_unlock (container->_priv->mutex_ar_events);

  return event;
}

/**
 * sim_container_get_events_to_organizer:
 * @container: a SimContainer object.
 *
 * Return value: the length of the events queue.
 */
gint
sim_container_get_events_to_organizer (SimContainer * container)
{
  gint len;

  // This may be inaccurate.
  if ((len = g_atomic_int_get (&container->_priv->events_len)) < 0)
  {
    g_atomic_int_set (&container->_priv->events_len, 0);
    return (0);
  }
  else
    return (len);
}

/**
 * sim_container_inc_discarded_events:
 * @container: a SimContainer object.
 *
 * Return value: increase the value of discarded events.
 */
void
sim_container_inc_discarded_events (SimContainer * container)
{
  g_return_if_fail (SIM_IS_CONTAINER (container));
  g_atomic_int_inc (&container->_priv->discarded_events);
  return;
}

/**
 * sim_container_get_discarded_events:
 * @container: a SimContainer object.
 *
 * Return value: number of discarded events.
 */
gint
sim_container_get_discarded_events (SimContainer * container)
{
  g_return_val_if_fail (SIM_IS_CONTAINER (container), 10000);
  return (g_atomic_int_get (&container->_priv->discarded_events));
}

/**
 * sim_container_db_load_events_count:
 * @container: a SimContainer object.
 *
 * Reads the event count from snort.acid_event.
 */
void
sim_container_db_load_events_count (SimContainer * container)
{
  const GValue * value;
  GdaDataModel * dm;
  gchar        * aux1, * aux2, * query, query_real [128];
  guint          n;

  if (ossim.config->copy_siem_events)
  {
    //uuencoded "SELECT COUNT(*) FROM acid_event_input"
    aux1 = g_strdup ("U0VMRUNUIENPVU5UKCopIEZST00gYWNpZF");
    aux2 = g_strdup ("9ldmVudF9pbnB1dAo=");
  }
  else
  {
    //uuencoded "SELECT COUNT(*) from acid_event"
    aux1 = g_strdup ("U0VMRUNUIENPVU5UKCopIGZy");
    aux2 = g_strdup ("b20gYWNpZF9ldmVudAo=");
  }

  query = g_strconcat (aux1, aux2, NULL);
  memset (query_real, 0, 128);
  sim_base64_decode (query, strlen (query), query_real, &n);

  dm = sim_database_execute_single_command (ossim.dbsnort, query_real);
  if (dm)
  {
    value = gda_data_model_get_value_at (dm, 0, 0, NULL);
    container->_priv->events_count = g_value_get_long (value);
    g_object_unref (G_OBJECT(dm));
  }

  g_free (aux1);
  g_free (aux2);
  g_free (query);

  return;
}

/**
 * sim_container_get_events_count:
 * @container: a SimContainer object.
 *
 * Return value: approximate count of processed events.
 */
gint
sim_container_get_events_count (SimContainer * container)
{
  return (g_atomic_int_get (&container->_priv->events_count));
}

/**
 * sim_container_inc_events_count:
 * @container: a SimContainer object.
 *
 * Increments events_count by one atomically.
 */
void
sim_container_inc_events_count (SimContainer * container)
{
  g_atomic_int_inc ((gint *)&container->_priv->events_count);
  return;
}

/**
 * sim_container_free_events:
 * @container: a SimContainer object.
 *
 * Free the events queue and create a new one.
 */
void
sim_container_free_events (SimContainer  *container)
{
  g_return_if_fail (SIM_IS_CONTAINER (container));

  g_async_queue_unref (container->_priv->events);
  container->_priv->events = g_async_queue_new_full (g_object_unref);
  g_atomic_int_set (&container->_priv->events_len, 0);

  sim_engine_init_events_in_queue (container->_priv->engine);
}

/*
 * //FIXME: working here. This will insert a monitor rule in a queue
 *
 */
void
sim_container_push_monitor_rule (SimContainer  *container,
                                 SimRule    *rule)
{
  g_return_if_fail (SIM_IS_CONTAINER (container));
  g_return_if_fail (SIM_IS_RULE (rule));

  g_mutex_lock (container->_priv->mutex_monitor_rules);
  g_queue_push_head (container->_priv->monitor_rules, rule);
  g_cond_signal (container->_priv->cond_monitor_rules);
  g_mutex_unlock (container->_priv->mutex_monitor_rules);
  ossim_debug ( "sim_container_push_monitor_rule: pushed");
}

/*
 * //FIXME: Working here. This will extract the monitor rules from the queue
 *
 */
SimRule*
sim_container_pop_monitor_rule (SimContainer  *container)
{
  SimRule *rule;

  g_return_val_if_fail (SIM_IS_CONTAINER (container), NULL);

  g_mutex_lock (container->_priv->mutex_monitor_rules);

  while (!g_queue_peek_tail (container->_priv->monitor_rules)) //We stop until some element appears in the event queue.
    g_cond_wait (container->_priv->cond_monitor_rules, container->_priv->mutex_monitor_rules);

  rule = (SimRule *) g_queue_pop_tail (container->_priv->monitor_rules);

  if (!g_queue_peek_tail (container->_priv->monitor_rules)) //if there are more events in the queue, don't do nothing
  {
    g_cond_free (container->_priv->cond_monitor_rules);
    container->_priv->cond_monitor_rules = g_cond_new ();
  }
  g_mutex_unlock (container->_priv->mutex_monitor_rules);

  return rule;
}

/*
 *
 *
 */
void
sim_container_free_monitor_rules (SimContainer  *container)
{
  g_return_if_fail (SIM_IS_CONTAINER (container));

  g_mutex_lock (container->_priv->mutex_monitor_rules);
  while (!g_queue_is_empty (container->_priv->monitor_rules))
  {
    SimRule *rule = (SimRule *) g_queue_pop_head (container->_priv->monitor_rules);
    g_object_unref (rule);
  }
  g_queue_free (container->_priv->monitor_rules);
  container->_priv->monitor_rules = g_queue_new ();
  g_mutex_unlock (container->_priv->mutex_monitor_rules);
}

/*
 *
 */
gboolean
sim_container_is_empty_monitor_rules (SimContainer  *container)
{
  gboolean empty;

  g_return_val_if_fail (SIM_IS_CONTAINER (container), TRUE);

  g_mutex_lock (container->_priv->mutex_monitor_rules);
  empty = g_queue_is_empty (container->_priv->monitor_rules);
  g_mutex_unlock (container->_priv->mutex_monitor_rules);

  return empty;
}

/*
 *
 *
 */
gint
sim_container_length_monitor_rules (SimContainer  *container)
{
  gint length;

  g_return_val_if_fail (SIM_IS_CONTAINER (container), 0);

  g_mutex_lock (container->_priv->mutex_monitor_rules);
  length = container->_priv->monitor_rules->length;
  g_mutex_unlock (container->_priv->mutex_monitor_rules);

  return length;
}

void
sim_container_debug_print_all (SimContainer *container)
{
  g_return_if_fail (SIM_IS_CONTAINER (container));
  sim_container_debug_print_plugins (container);
  sim_container_debug_print_plugin_sids (container);
  sim_container_debug_print_servers (container);
}


void
sim_container_debug_print_plugins (SimContainer *container)
{
  GList *plugins;

  ossim_debug ("%s", __func__);

  plugins = container->_priv->common_plugins;
  while (plugins)
  {
    sim_plugin_debug_print (SIM_PLUGIN (plugins->data));
    plugins = g_list_next (plugins);
  }
}

void
sim_container_debug_print_plugin_sids (SimContainer *container)
{
  GList *plugin_sids;

  ossim_debug ("%s", __func__);

  plugin_sids = container->_priv->common_plugin_sids;
  while (plugin_sids)
  {
    sim_plugin_sid_debug_print (SIM_PLUGIN_SID (plugin_sids->data));
    plugin_sids = g_list_next (plugin_sids);
  }
}

void
sim_container_debug_print_servers (SimContainer *container)
{
  ossim_debug ("sim_container_debug_print_servers");
  GList *list = container->_priv->servers;
  while (list)
  {
    SimServer *s = (SimServer *) list->data;
    sim_server_debug_print (s);
    list = list->next;
  }
}

/*
 *
 *  Hashtables related
 *
 */
void
sim_container_add_sensor_sid (SimContainer *container, gchar *sensor_device, guint sid)
{
  g_hash_table_insert(container->_priv->sensor_sid, sensor_device, GINT_TO_POINTER (sid));
}

guint
sim_container_get_sensor_sid (SimContainer *container, gchar *sensor_device) //Snort & ossim massive events db storage related
{
  return GPOINTER_TO_INT (g_hash_table_lookup (container->_priv->sensor_sid, sensor_device));
}

void
sim_container_get_snort_max_sid (SimContainer *container)
{
  gchar         *query;
  GdaDataModel  *dm;
  const GValue  *value;

  //query snort to get the stored sensors+interfaces & last_cid
  query = g_strdup_printf("SELECT max(sid) FROM sensor");
  dm = sim_database_execute_single_command (ossim.dbsnort, query);
  if (dm&&gda_data_model_get_n_rows (dm))
  {
    value = gda_data_model_get_value_at (dm, 0, 0, NULL);
    if (!gda_value_is_null (value))
      container->_priv->max_sid = g_value_get_uint(value);
    else
      container->_priv->max_sid = 0;
    ossim_debug ( "sim_container_get_snort_max_sid: max_sid value %u", container->_priv->max_sid);
  }
  if(dm)
    g_object_unref(dm);

  g_free (query);

}

void
sim_container_set_snort_max_sid(SimContainer *container, guint val)
{
  container->_priv->max_sid=val;
  ossim_debug ( "sim_container_set_snort_max_sid: max_sid value %u", container->_priv->max_sid);
}

//A Signature cache, to prevent massive sql select statements in the event storage process
void
sim_container_init_signatures_to_id (SimContainer *container)
{
  g_return_if_fail (SIM_IS_CONTAINER (container));
  g_return_if_fail (SIM_IS_DATABASE (ossim.dbsnort));

  gchar         *query;
  GdaDataModel  *dm;
  const GValue  *value;

  guint         nresults=0;

  gchar         *name=NULL;
  guint         *sig_id=NULL;
  guint         i;

  // New hashtable
  container->_priv->signatures_to_id=g_hash_table_new_full(g_str_hash, g_str_equal, g_free, g_free);
  ossim_debug ( "sim_container_init_signatures_to_id: Generating new hash table.");

  //TODO: fetch the most used signatures by a sql query?

  //query snort to get the 1000 most viewed signatures/id's
  query = g_strdup_printf("select sig_id, sig_name from signature, event where sig_id=signature group by(signature) order by count(signature) desc LIMIT 0,1000");

  dm = sim_database_execute_single_command (ossim.dbsnort, query);
  if (dm)
  {
    nresults=gda_data_model_get_n_rows (dm);
    for(i=0; i<nresults;i++)
    {
      sig_id=(guint*)g_malloc(sizeof(guint));

      value = gda_data_model_get_value_at (dm, 0, i, NULL);

      *sig_id= g_value_get_uint(value);

      value = gda_data_model_get_value_at (dm, 1, i, NULL);
      name= gda_value_stringify(value);

      g_hash_table_insert(container->_priv->signatures_to_id, name, sig_id);
    }
    g_object_unref(dm);
  }
  g_free (query);
}

void
sim_container_free_signatures_to_id(SimContainer *container)
{
  g_return_if_fail (SIM_IS_CONTAINER (container));
  g_hash_table_destroy(container->_priv->signatures_to_id);
}

void
sim_container_add_signatures_to_id(SimContainer *container, gchar *sig_name, guint *sig_id) //Snort & ossim massive events db storage related
{
  ossim_debug ("sim_container_add_signatures_to_id: sig_id: %u signature: %s", *sig_id, sig_name);
  g_hash_table_insert(container->_priv->signatures_to_id, sig_name, sig_id);
}

guint*
sim_container_get_signatures_to_id(SimContainer *container, gchar *sig_name) //Snort & ossim massive events db storage related
{
  return g_hash_table_lookup(container->_priv->signatures_to_id,sig_name);
}

/**
 * sim_container_update_recovery:
 * @container: #SimContainer object
 * @database: #SimDatabase object
 *
 * Updates recovery in all context
 */
void
sim_container_update_recovery (SimContainer *container,
                               SimDatabase  *database)
{
  gint recovery;

  g_return_if_fail (SIM_IS_CONTAINER (container));
  g_return_if_fail (SIM_IS_DATABASE (database));

  recovery = sim_db_get_config_int (database, "recovery");

  sim_context_update_host_level_recovery (container->_priv->context, recovery);
  sim_context_update_net_level_recovery (container->_priv->context, recovery);
}

/**
 * sim_container_get_total_backlogs:
 * @container: #SimContainer object
 *
 * Returns: sum of all context backlogs
 */
guint
sim_container_get_total_backlogs (SimContainer *container)
{
  guint sum = 0;

  g_return_val_if_fail (SIM_IS_CONTAINER (container), 0);

  sum += sim_engine_get_backlogs_len (container->_priv->engine);

  return sum;
}

/**
 * sim_container_remove_expired_backlogs:
 * @container: #SimContainer object
 *
 * Remove expired backlogs in all context
 */
void
sim_container_remove_expired_backlogs (SimContainer *container)
{
  g_return_if_fail (SIM_IS_CONTAINER (container));

  sim_engine_remove_expired_backlogs (container->_priv->engine);
}

/**
 * sim_container_remove_expired_group_alarms:
 * @container: #SimContainer object
 *
 * Remove expired group alarms in all context
 */
void
sim_container_remove_expired_group_alarms (SimContainer *container)
{
  g_return_if_fail (SIM_IS_CONTAINER (container));

  sim_engine_remove_expired_group_alarms (container->_priv->engine);
}

/**
 * sim_container_get_contexts_stats:
 * @container: #SimContainer object
 * @elapsed_time: elapsed time in seconds
 *
 * Returns gchar * with context stats
 * It must be freed outside
 */
gchar *
sim_container_get_context_stats (SimContainer *container,
                                 glong         elapsed_time)
{
  guint    context_stats;
  gfloat     context_stats_5_minutes;

  g_return_val_if_fail (SIM_IS_CONTAINER (container), NULL);

  context_stats = sim_context_get_stats (container->_priv->context, elapsed_time);
  context_stats_5_minutes = sim_context_get_stats_5_minutes (container->_priv->context, elapsed_time);
  if (context_stats_5_minutes >= 0)
    sim_db_update_context_stats (ossim.dbossim, sim_context_get_id (container->_priv->context), context_stats_5_minutes);

  return g_strdup_printf ("[%s %u]", sim_context_get_name (container->_priv->context), context_stats);
}

gchar *
sim_container_get_engine_stats_json (SimContainer *container,
                                glong         elapsed_time)
{
  GString *stats;
  gchar   *engine_stats;
  SimEngine *engine = NULL;
  int flag = 0;
  g_return_val_if_fail (SIM_IS_CONTAINER (container), NULL);

  stats = g_string_new ("");
  engine = sim_container_get_engine (container, NULL);
    engine_stats = sim_engine_get_stats_json ((SimEngine *)engine, elapsed_time);
    if (engine_stats)
    {
      flag = 1;
      g_string_append (stats, engine_stats);
      
      g_free (engine_stats);
    }

  // liberate the GString object, but not the string itself (gchar*) so we can return it.
  if (!flag)
  {
    g_string_printf (stats,"null");
  }
  if (engine != NULL)
    g_object_unref (engine);
  return g_string_free (stats, FALSE);
}

/**
 * sim_container_reload_host_plugin_sids:
 * @container: #SimContainer object
 *
 * Reload all host plugin sids in all context
 */
void
sim_container_reload_host_plugin_sids (SimContainer *container)
{
  g_return_if_fail (SIM_IS_CONTAINER (container));

  sim_context_reload_host_plugin_sids (container->_priv->context);
}

// vim: set tabstop=2:

