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

#include "sim-engine.h"

#include <glib.h>
#include <string.h>
#include <errno.h>
#include <sys/types.h>
#include <sys/stat.h>

#include "sim-object.h"
#include "os-sim.h"
#include "sim-policy.h"
#include "sim-directive.h"
#include "sim-xml-directive.h"
#include "sim-util.h"
#include "sim-db-command.h"
#include "sim-log.h"

struct _SimEnginePrivate
{
  SimUuid        *id;               // Mssp Engine id
  gchar          *name;             // Engine name in UTF-8

  GPtrArray      *contexts;         // Contexts array

  gchar          *default_directive_file; // Default directives XML.
  gchar          *directive_file;   // directives XML filename
  gchar          *disabled_file;    // disabled directives filename

  SimDatabase    *database;         // SimDatabase for load engine data

  gint            events_in_queue;  // Number of events in queue to organizer
  guint           total_events;     // Total of events received
  guint           last_total;       // Last Total events for eps calc

  GList          *temp_directives;  // Directives loaded without rules expanded
  GHashTable     *directives;       // Directives grouped by plugin_id
  GPtrArray      *backlogs;         // Backlogs array

  GHashTable     *group_alarms;     // Alarms groupped
  GHashTable     *otx_data;         //Use to see if we have add the "ghosts" directives 

  /* Mutex */
  GMutex         mutex_plugins;
  GMutex         mutex_plugin_sids;
  GMutex         mutex_directives;
  GMutex         mutex_backlogs;
  GMutex         mutex_group_alarms;

};

#define SIM_ENGINE_GET_PRIVATE(self) (G_TYPE_INSTANCE_GET_PRIVATE ((self), SIM_TYPE_ENGINE, SimEnginePrivate))

SIM_DEFINE_TYPE (SimEngine, sim_engine, G_TYPE_OBJECT, NULL)

/* Prototypes */
static gint     sim_engine_load_directives                   (SimEngine     *engine);
static GList *  sim_engine_load_disabled_directives          (const gchar    *filename);

static void     sim_engine_expand_directive_taxonomy_product (SimEngine     *engine,
                                                              SimDirective  *directive,
                                                              gint           product_id);
static gboolean sim_engine_expand_directive_rule             (GNode         *rule_node,
                                                              gpointer       _engine);
static void     sim_engine_expand_directive_rule_ips         (SimEngine     *engine,
                                                              GList         *assets_names,
                                                              SimRule       *rule,
                                                              gboolean       is_negated,
                                                              gboolean       is_src);
static void     sim_engine_expand_directive_rule_sensors     (GList         *sensors_names,
                                                              SimRule       *rule,
                                                              gboolean       is_negated);
static void     sim_engine_expand_directive_rule_entities    (GList         *entities_names,
                                                              SimRule       *rule,
                                                              gboolean       is_negated);
static void     sim_engine_append_directive                  (SimEngine     *engine,
                                                              SimDirective  *directive);
static gboolean _sim_engine_group_alarm_is_timeout           (gpointer       key,
                                                              gpointer       value,
                                                              gpointer       user_data);

/**
 * sim_engine_class_init:
 * @klass: Pointer to SimEngineClass
 *
 * Init the class struct
 */
static void
sim_engine_class_init (SimEngineClass *klass)
{
  GObjectClass *selfclass = G_OBJECT_CLASS (klass);

  g_type_class_add_private (klass, sizeof (SimEnginePrivate));
  parent_class = g_type_class_ref (G_TYPE_OBJECT);

  selfclass->finalize = sim_engine_finalize;
}

/**
 * sim_engine_instance_init:
 * @self: a #SimEngine
 *
 * This function initialice a instance of SimEngine
 */
static void
sim_engine_instance_init (SimEngine *self)
{
  self->priv = SIM_ENGINE_GET_PRIVATE (self);

  self->priv->id = NULL;
  self->priv->name = NULL;

  self->priv->database = NULL;

  self->priv->contexts = g_ptr_array_new_with_free_func ((GDestroyNotify) g_object_unref);

  self->priv->temp_directives = NULL;
  self->priv->directives = g_hash_table_new_full (g_direct_hash, g_direct_equal,
                                                  NULL, (GDestroyNotify) g_ptr_array_unref);

  self->priv->backlogs = g_ptr_array_new_with_free_func ((GDestroyNotify) g_object_unref);

  self->priv->events_in_queue = 0;
  self->priv->total_events = 0;
  self->priv->last_total = 0;

  self->priv->group_alarms = g_hash_table_new_full (g_str_hash, g_str_equal,
                                                    g_free, g_object_unref);

  /* Mutex */
  g_mutex_init(&self->priv->mutex_plugins);
  g_mutex_init(&self->priv->mutex_plugin_sids);
  g_mutex_init(&self->priv->mutex_backlogs);
  g_mutex_init(&self->priv->mutex_directives);
  g_mutex_init(&self->priv->mutex_group_alarms);
  /* Used for otx */

  self->priv->otx_data = g_hash_table_new_full (g_str_hash, g_str_equal, g_free, NULL);
}

/**
 * sim_engine_finalize:
 * @self: a #SimEngine
 *
 * This function finalize a instance of SimEngine
 */
static void
sim_engine_finalize (GObject *self)
{
  SimEnginePrivate *priv = SIM_ENGINE_GET_PRIVATE (self);

  if (priv->id)
  {
    g_object_unref (priv->id);
    priv->id = NULL;
  }

  if (priv->name)
  {
    g_free (priv->name);
    priv->name = NULL;
  }

  if (priv->contexts)
  {
    g_ptr_array_unref (priv->contexts);
    priv->contexts = NULL;
  }

  if (priv->default_directive_file)
  {
    g_free (priv->default_directive_file);
    priv->default_directive_file = NULL;
  }

  if (priv->directive_file)
  {
    g_free (priv->directive_file);
    priv->directive_file = NULL;
  }

  if (priv->disabled_file)
  {
    g_free (priv->disabled_file);
    priv->disabled_file = NULL;
  }

  if (priv->temp_directives)
  {
    g_list_foreach (priv->temp_directives, (GFunc) g_object_unref, NULL);
    g_list_free (priv->temp_directives);
    priv->temp_directives = NULL;
  }

  if (priv->directives)
  {
    g_hash_table_unref (priv->directives);
    priv->directives = NULL;
  }

  if (priv->backlogs)
  {
    g_ptr_array_unref (priv->backlogs);
    priv->backlogs = NULL;
  }

  if (priv->group_alarms)
  {
    g_hash_table_unref (priv->group_alarms);
    priv->group_alarms = NULL;
  }
  if (priv->otx_data)
  {
    g_hash_table_unref (priv->otx_data);
    priv->otx_data = NULL;
  }

  g_mutex_clear(&priv->mutex_plugins);
  g_mutex_clear(&priv->mutex_plugin_sids);
  g_mutex_clear(&priv->mutex_directives);
  g_mutex_clear(&priv->mutex_backlogs);
  g_mutex_clear(&priv->mutex_group_alarms);

  G_OBJECT_CLASS (parent_class)->finalize (self);
}

/**
 * sim_engine_new:
 * @uuiid: uuid_t engine id
 *
 * Returns: new #SimEngine object with @id
 */
SimEngine *
sim_engine_new (SimUuid     *id,
                const gchar *name)
{
  SimEngine *engine;
  gchar *default_directive_file;
  gchar *directive_file;
  gchar *disabled_file;

  g_return_val_if_fail (SIM_IS_UUID (id), NULL);

  default_directive_file = g_strdup_printf ("/etc/ossim/server/directives.xml");
  directive_file = g_strdup_printf ("/etc/ossim/server/%s/directives.xml", sim_uuid_get_string (id));
  disabled_file = g_strdup_printf ("/etc/ossim/server/%s/disabled_directives.data", sim_uuid_get_string (id));

  engine = sim_engine_new_full (id, name, default_directive_file, directive_file, disabled_file, NULL);

  g_free (default_directive_file);
  g_free (directive_file);
  g_free (disabled_file);

  return engine;
}

/**
 * sim_engine_new_full:
 * @uuid: uuid_t engine id
 * @name: const gchar* engine name
 * @default_directive_file: default directive xml filename.
 * @directive_file: const gchar* directive file name
 * @disabled_file: const gchar* disabled directive file name
 *
 * Returns: new #SimEngine object with params
 */
SimEngine *
sim_engine_new_full (SimUuid     *id,
                     const gchar *name,
                     const gchar *default_directive_file,
                     const gchar *directive_file,
                     const gchar *disabled_file,
                     SimDatabase *database)
{
  SimEngine *engine;

  g_return_val_if_fail (SIM_IS_UUID (id), NULL);

  engine = SIM_ENGINE (g_object_new (SIM_TYPE_ENGINE, NULL));

  engine->priv->id = g_object_ref (id);

  if (name != NULL)
    engine->priv->name = g_utf8_normalize (name, -1, G_NORMALIZE_DEFAULT);
  else
    engine->priv->name = g_strdup ("");

  if (default_directive_file != NULL)
    engine->priv->default_directive_file = g_strdup (default_directive_file);
  if (directive_file != NULL)
    engine->priv->directive_file = g_strdup (directive_file);
  if (disabled_file != NULL)
    engine->priv->disabled_file = g_strdup (disabled_file);

  engine->priv->database = database;

  return engine;
}

/**
 * sim_engine_get_id:
 * @engine: a #SimEngine object.
 *
 * Returns the id of a engine.
 */
SimUuid *
sim_engine_get_id (SimEngine *engine)
{
  g_return_val_if_fail (SIM_IS_ENGINE (engine), NULL);

  return (engine->priv->id);
}

/**
 * sim_engine_add_context:
 * @engine: #SimEngine object.
 * @context: a #SimContext
 *
 * Adds #context to @engine.
 */
void
sim_engine_add_context (SimEngine  *engine,
                        SimContext *context)
{
  g_return_if_fail (SIM_IS_ENGINE (engine));
  g_return_if_fail (SIM_IS_CONTEXT (context));

  g_ptr_array_add (engine->priv->contexts, context);
}


/**
 * sim_engine_set_directive_file:
 * @engine: #SimEngine object
 * @filename: const gchar* directive file name
 *
 * Sets directive @filename in @engine.
 */
void
sim_engine_set_directive_file (SimEngine  *engine,
                               const gchar *filename)
{
  g_return_if_fail (SIM_IS_ENGINE (engine));

  if (engine->priv->directive_file != NULL)
    g_free (engine->priv->directive_file);

  engine->priv->directive_file = g_strdup (filename);
}

/**
 * sim_engine_set_disabled_file:
 * @engine: #SimEngine object
 * @filename: const gchar* disabled directives file name
 *
 * Sets disabled directives @filename in @engine.
 */
void
sim_engine_set_disabled_file (SimEngine  *engine,
                              const gchar *filename)
{
  g_return_if_fail (SIM_IS_ENGINE (engine));

  if (engine->priv->disabled_file != NULL)
    g_free (engine->priv->disabled_file);

  engine->priv->disabled_file = g_strdup (filename);
}

/**
 * sim_engine_get_name:
 *  @brief Return the engine name in UTF-8 format
 *
 *  @param self Pointer to a correlation object
 */
const gchar *
sim_engine_get_name (SimEngine *self)
{
  g_return_val_if_fail (SIM_IS_ENGINE (self), NULL);

  return self->priv->name;
}

/**
 * sim_engine_set_database:
 * @engine: a #SimEngine
 * @database: #SimDatabase object
 *
 * Sets @database for @engine.
 */
void
sim_engine_set_database (SimEngine   *engine,
                         SimDatabase *database)
{
  g_return_if_fail (SIM_IS_ENGINE (engine));
  g_return_if_fail (SIM_IS_DATABASE (database));

  if (engine->priv->database)
    g_object_unref (engine->priv->database);

  engine->priv->database = g_object_ref (database);
}

// Loaders

/**
 * sim_engine_load_all:
 * @engine: a #SimEngine
 *
 * Load all engine db data and directives
 */
void
sim_engine_load_all (SimEngine  *engine)
{
  gint   loaded;
  gchar * name;
  const gchar * default_engine_id = sim_uuid_get_string(ossim.config->default_engine_id);
  gchar * directive_dir = NULL;

  g_return_if_fail (SIM_IS_ENGINE (engine));
  g_return_if_fail (SIM_IS_DATABASE (engine->priv->database));

  name = engine->priv->name;

  g_message ("Loading Engine %s '%s'", sim_uuid_get_string (engine->priv->id), name);

  // Create user defined directive directory, just in case.
  directive_dir = g_strdup_printf("/etc/ossim/server/%s", default_engine_id);
  if (!(g_file_test (directive_dir, G_FILE_TEST_IS_DIR)))
  {
    if (g_mkdir_with_parents (directive_dir, 0700) == 0)
     {
      // Change permissions.
      if (chmod (directive_dir, S_IRWXU|S_IRWXG|S_IROTH|S_IXOTH) != 0)
      {
        g_warning ("Cannot change the engine directory permissions");
      }

      GError * error = NULL;
      gchar * dst_directives_xml_file = g_strdup_printf("%s/directives.xml", directive_dir);
      gchar * dst_user_xml_file = g_strdup_printf("%s/user.xml", directive_dir);
      const gchar * src_directives_xml_file = "/usr/share/alienvault-directives-free/d_clean/templates/directives.xml";
      const gchar * src_user_xml_file = "/usr/share/alienvault-directives-free/d_clean/templates/user.xml";
      GFile * src_directives = g_file_new_for_path (src_directives_xml_file);
      GFile * src_user = g_file_new_for_path (src_user_xml_file);
      GFile * dst_directives = g_file_new_for_path ((const gchar *)dst_directives_xml_file);
      GFile * dst_user = g_file_new_for_path ((const gchar *)dst_user_xml_file);

      if (!g_file_copy (src_directives, dst_directives, G_FILE_COPY_NONE, NULL, NULL, NULL, &error))
      {
        g_warning ("User defined directives XML file cannot be created: %s", error->message);
        g_error_free(error);
        error = NULL;
      }
      else
      {
        if (chmod (dst_directives_xml_file, S_IRUSR|S_IWUSR|S_IRGRP|S_IWGRP) != 0)
        {
          g_warning ("Cannot change user defined directives XML file permissions");
        }
      }

      if (!g_file_copy (src_user, dst_user, G_FILE_COPY_NONE, NULL, NULL, NULL, &error))
      {
        g_warning ("User defined user XML file cannot be created: %s", error->message);
        g_error_free(error);
        error = NULL;
      }
      else
      {
        if (chmod (dst_user_xml_file, S_IRUSR|S_IWUSR|S_IRGRP|S_IWGRP) != 0)
        {
          g_warning ("Cannot change user XML file permissions");
        }
      }

      g_free(dst_directives_xml_file);
      g_free(dst_user_xml_file);
      g_object_unref(src_directives);
      g_object_unref(src_user);
      g_object_unref(dst_directives);
      g_object_unref(dst_user);
    }
    else
      g_warning ("User defined directive directory cannot be created");
  }
  g_free (directive_dir);

  g_message ("Engine '%s': Loading directives", name);
  loaded = sim_engine_load_directives (engine);
  g_message ("Engine '%s': %d directives loaded", name, loaded);
  if(loaded)
    sim_db_update_taxonomy_info(engine->priv->database);
}

/**
 * sim_engine_reload_all:
 * @engine: a #SimEngine
 *
 * ReLoad all engine db data and directives
 */
void
sim_engine_reload_all (SimEngine *engine)
{
  gchar *name;

  g_return_if_fail (SIM_IS_ENGINE (engine));
  g_return_if_fail (SIM_IS_DATABASE (engine->priv->database));

  name = engine->priv->name;

  g_message ("Reloading Engine %s '%s'", sim_uuid_get_string (engine->priv->id), name);

  g_message ("Engine %s: Reloading directives", engine->priv->name);
  sim_engine_reload_directives (engine);
}

/*
 * Directives
 */

/**
 * sim_engine_get_directives_by_plugin_id:
 * @engine: #SimEngine object
 * @plugin_id : gint plugin id
 *
 * Returns pointer to GPtrArray of directives with @plugin_id
 * No thread safe!
 */
GPtrArray *
sim_engine_get_directives_by_plugin_id (SimEngine *engine,
                                        gint        plugin_id)
{
  GPtrArray *directives = NULL;

  g_return_val_if_fail (SIM_IS_ENGINE (engine), NULL);

  directives = g_hash_table_lookup (engine->priv->directives, GINT_TO_POINTER (plugin_id));

  /* Add directives that match with ANY */
  if (directives)
    g_ptr_array_ref (directives);

  return directives;
}

/**
 * sim_engine_append_directive:
 * @engine: #SimEngine object
 * @directive: a #SimDirective
 *
 * Insert @directive in @engine
 *
 * Use plugin id (root node) from directive as key for the hash table
 * each hash entry has a GPtrArray with directives:
 *     ----
 *    |1234| -> directive1 -> directive2
 *    |2345| -> directive3
 *     ----
 *
 * Because of taxonomy it is possible that a directive is in multiple GPtrArray like:
 *     ----
 *    |1234| -> directive1 -> directive2
 *    |2345| -> directive1 -> directive3
 *     ----
 *
 * No thread safe!
 */
static void
sim_engine_append_directive (SimEngine   *engine,
                             SimDirective *directive)
{
  GHashTable *plugin_id;
  GPtrArray *array;
  GHashTable *product_id;

  plugin_id = sim_directive_get_root_plugin_ids (directive);
  product_id = sim_directive_get_root_taxonomy_product (directive);

  if (plugin_id)
  {
    GHashTableIter iter;
    gpointer key, value;

    g_hash_table_iter_init (&iter, plugin_id);
    while (g_hash_table_iter_next (&iter, &key, &value))
    {
      array = (GPtrArray *)g_hash_table_lookup (engine->priv->directives, key);
      if (array == NULL)
      {
        /* New entry */
        array = g_ptr_array_new_with_free_func (g_object_unref);
        g_hash_table_insert (engine->priv->directives, key, array);
      }

      g_ptr_array_add (array, g_object_ref (directive));
    }
  }
  else if (product_id)
  {
    GHashTableIter iter;
    gpointer key, value;

    g_hash_table_iter_init (&iter, product_id);
    while (g_hash_table_iter_next (&iter, &key, &value))
      sim_engine_expand_directive_taxonomy_product (engine, directive, GPOINTER_TO_INT (key));
  }
  else
  {
    g_message ("Directive %d without plugin_id or taxonomy_product", sim_directive_get_id (directive));
  }
}

static void
sim_engine_expand_directive_taxonomy_product (SimEngine    *engine,
                                              SimDirective *directive,
                                              gint          product_id)
{
  GList *plugin_ids;
  GPtrArray *array;
  guint i;

  /* Get common plugins */
  plugin_ids = sim_container_get_taxonomy_product (ossim.container, product_id);
  while (plugin_ids)
  {
    array = (GPtrArray *) g_hash_table_lookup (engine->priv->directives,
                                               plugin_ids->data);
    if (array == NULL)
    {
      /* New entry */
      array = g_ptr_array_new_with_free_func (g_object_unref);
      g_hash_table_insert (engine->priv->directives, plugin_ids->data, array);
    }

    g_ptr_array_add (array, g_object_ref (directive));

    plugin_ids = g_list_next (plugin_ids);
  }

  /* Get custom plugins */
  for (i = 0; i < engine->priv->contexts->len; i++)
  {
    SimContext *context;

    context = (SimContext *) g_ptr_array_index (engine->priv->contexts, i);

    plugin_ids = sim_context_get_taxonomy_product (context, product_id);
    while (plugin_ids)
    {
      array = (GPtrArray *) g_hash_table_lookup (engine->priv->directives,
                                                 plugin_ids->data);
      if (array == NULL)
      {
        /* New entry */
        array = g_ptr_array_new_with_free_func (g_object_unref);
        g_hash_table_insert (engine->priv->directives, plugin_ids->data, array);
      }

      g_ptr_array_add (array, g_object_ref (directive));

      plugin_ids = g_list_next (plugin_ids);
    }
  }
}

/**
 * sim_engine_load_directives:
 * @engine: a #SimEngine
 *
 * Load all directives in directive_file except disabled directives.
 *
 * Inserts the directives with REPLACE INTO into DB (to be sure that they're updated when the user modifies them).
 * Later, when the plugin_sids are loaded, the directives are loaded in the same way that all the other plugin_sid's.
 *
 * Directives are loaded always from file, not from master servers
 * The directive's file in the master server MUST contain all the directives in children server's.
 * It doesn't matters if a children server doesn't have all the directives from its master.
 * The directives which are not loaded in children server will be just another plugin_sid memory entry in engine, without any effect
 * (because directive are not loaded) other than a little memory waste (probably a few Kbs, no worries).
 *
 * Thread safe
 */
static gint
sim_engine_load_directives (SimEngine  * engine)
{
  SimXmlDirective *default_xml_directive;
  SimXmlDirective *xml_directive;
  GList           *list                    = NULL;
  GList           *default_directives_list = NULL;
  GList           *directives_list         = NULL;
  GList           *disabled_list           = NULL;
  gint             previous                = 0;
  gint             directive_num           = 0;
  gboolean         dup_directive           = FALSE;
  GList           *iter                    = NULL;

  disabled_list = sim_engine_load_disabled_directives (engine->priv->disabled_file);

  previous = xmlSubstituteEntitiesDefault (1);

  /* Load default and user defined directives from file */
  default_xml_directive = sim_xml_directive_new ();
  sim_xml_directive_load_file (default_xml_directive, engine->priv->default_directive_file);
  default_directives_list = list = sim_xml_directive_get_directives (default_xml_directive);

  xml_directive = sim_xml_directive_new ();
  sim_xml_directive_load_file (xml_directive, engine->priv->directive_file);
  directives_list = sim_xml_directive_get_directives (xml_directive);

  // Check for duplicated directives (directives with the same ID).
  // Default directives will be always prevalent.
  for (;list; list = list->next)
  {
    GList * found = g_list_find_custom (directives_list, (gconstpointer)list->data, (GCompareFunc)sim_directive_compare_id);
    if (found)
    {
      SimDirective * dir_found = SIM_DIRECTIVE(found->data);
      ossim_debug ("Duplicated directive with id %d won't be loaded", sim_directive_get_id (dir_found));
      g_object_unref (dir_found);
      directives_list = g_list_delete_link (directives_list, found);
      dup_directive |= TRUE;
    }
  }

  // Make the customer aware that he may double included the default directives,
  // this could be because default directives are included in the engine directives.xml file.
  // If we modified xml_directive internally reassign it.
  if (dup_directive)
  {
    sim_xml_directive_set_directives (xml_directive, directives_list);
    ossim_debug ("Engine %s has duplicated directives, check its directives.xml file", sim_uuid_get_string (engine->priv->id));
  }

  //list = g_list_concat (default_directives_list, directives_list);
  // DONT USER CONCAT. It don't duplicated list
  list = NULL;
  iter = g_list_first (default_directives_list);
  while (iter)
  {
    list = g_list_append (list, iter->data);
    iter = g_list_next (iter);
  }
  iter = g_list_first (directives_list);
  while (iter)
  {
    list = g_list_append (list, iter->data);
    iter = g_list_next (iter);
  }


  g_mutex_lock (&engine->priv->mutex_directives);
  iter = list;
  while (iter)
  {
    SimDirective *directive = (SimDirective *) iter->data;

    // Update plugin_sid db table
    sim_db_update_plugin_sid (engine->priv->database, directive, engine->priv->id);

    // If the directive is in the disabled directives file then ignore it
    if (sim_directive_is_in_list (directive, disabled_list))
    {
      ossim_debug ("Directive Disabled directive_id=%d", sim_directive_get_id (directive));
      iter = g_list_next (iter);
      continue;
    }

    engine->priv->temp_directives = g_list_prepend (engine->priv->temp_directives, g_object_ref (directive));

    directive_num ++;
    iter = g_list_next (iter);
  }
  g_list_free (list);
  list = NULL;
  g_mutex_unlock (&engine->priv->mutex_directives);

  xmlSubstituteEntitiesDefault (previous);

  g_list_free (disabled_list);
  g_object_unref (default_xml_directive);
  g_object_unref (xml_directive);

  return directive_num;
}

void
sim_engine_reload_directives (SimEngine  *engine)
{
  // unused parameter
  (void) engine;

//TODO
}




/**
 * sim_engine_load_disabled_directives:
 *
 * @filename:  const gchar * filename disabled directives
 *
 * Reads the directive_id form the first words of each line in the file
 * and load them into the list.
 *
 * file format:
 *    <directive_id>;<directive filename>
 *
 * Return value: GList with the id of the disabled directives
 */
static GList *
sim_engine_load_disabled_directives (const gchar *filename)
{
  GList  *disabled = NULL;
  GError *error    = NULL;
  gchar  *contents;
  gsize   length;

  g_return_val_if_fail (filename != NULL, NULL);

  // This file is not mandatory
  if (!g_file_test (filename, G_FILE_TEST_EXISTS))
    return NULL;

  // Read the disabled directives file
  if (g_file_get_contents (filename, &contents, &length, &error))
  {
    gchar **all_lines = g_strsplit (contents, "\n", -1);

    gint i = 0;
    while (all_lines[i] != NULL)
    {
      gchar **line = g_strsplit (all_lines[i], ";", 2);

      // The directive Id is the first field
      if (line[0] != NULL)
      {
        guint64 directive_id = g_ascii_strtoull (line[0], NULL, 10);
        disabled = g_list_prepend (disabled, (gpointer)directive_id);
      }
      g_strfreev (line);
      i ++;
    }
    g_strfreev (all_lines);
  }
  else
  {
    g_message ("%s: Error Reading disabled directives file %s", __func__, filename);
  }

  if (contents != NULL)
    g_free (contents);
  if (error != NULL)
    g_error_free (error);

  return disabled;
}

/**
 * sim_engine_expand_directives:
 *
 * @engine:
 * @directives: list of directives to expand
 *
 * Expands values in the rules that depends on the engine. This is
 * used to decouple XML parsing and engines.
 *
 * This must be the last step when loading a engine to ensure that
 * all values to expand are loaded in memory.
 */

void
sim_engine_expand_directives (SimEngine *engine)
{
  GNode *root_node;
  GList *list;

  g_mutex_lock (&engine->priv->mutex_directives);

  for (list = engine->priv->temp_directives; list; list = list->next)
  {
    root_node = sim_directive_get_root_node (list->data);
    g_node_traverse (root_node, G_IN_ORDER, G_TRAVERSE_ALL, -1, sim_engine_expand_directive_rule, engine);
    sim_engine_append_directive (engine, list->data);
  }

  g_mutex_unlock (&engine->priv->mutex_directives);
}

static gboolean
sim_engine_expand_directive_rule (GNode *rule_node, gpointer _engine)
{
  SimEngine *engine;
  SimRule *rule;

  engine = _engine;
  rule = rule_node->data;

  g_return_val_if_fail (SIM_IS_RULE (rule), FALSE);
  g_return_val_if_fail (SIM_IS_ENGINE (engine), FALSE);

  sim_engine_expand_directive_rule_ips (engine, sim_rule_get_expand_src_assets_names (rule), rule, FALSE, TRUE);
  sim_engine_expand_directive_rule_ips (engine, sim_rule_get_expand_src_assets_names_not (rule), rule, TRUE, TRUE);
  sim_engine_expand_directive_rule_ips (engine, sim_rule_get_expand_dst_assets_names (rule), rule, FALSE, FALSE);
  sim_engine_expand_directive_rule_ips (engine, sim_rule_get_expand_dst_assets_names_not (rule), rule, TRUE, FALSE);
  sim_engine_expand_directive_rule_sensors (sim_rule_get_expand_sensors_names (rule), rule, FALSE);
  sim_engine_expand_directive_rule_sensors (sim_rule_get_expand_sensors_names_not (rule), rule, TRUE);
  sim_engine_expand_directive_rule_entities (sim_rule_get_expand_entities (rule), rule, FALSE);
  sim_engine_expand_directive_rule_entities (sim_rule_get_expand_entities_not (rule), rule, TRUE);

  sim_rule_free_expand_items (rule);

  /* Traverse all nodes */
  return FALSE;
}

static void
sim_engine_expand_directive_rule_ips (SimEngine *engine,
                                      GList     *assets_names,
                                      SimRule   *rule,
                                      gboolean   is_negated,
                                      gboolean   is_src)
{
  gboolean expanded;
  GList *asset;
  guint i;
  SimContext *context;

  asset = assets_names;
  while (asset)
  {
    gchar *asset_name = (gchar *) asset->data;

    expanded = FALSE;

    /* Lookup name in all context hosts and nets */
    for (i = 0; i < engine->priv->contexts->len; i++)
    {
      context = (SimContext *) g_ptr_array_index (engine->priv->contexts, i);

      expanded = sim_context_expand_directive_rule_ips (context, asset_name, rule, is_negated, is_src);
      if (expanded)
      {
        ossim_debug ("Asset %s found in context %s for engine %s",
                     asset_name,
                     sim_uuid_get_string (sim_context_get_id (context)),
                     sim_uuid_get_string (engine->priv->id));
        break;
      }
    }

    if (!expanded)
      g_message ("Bad Directive: Asset %s Not found for engine %s.", asset_name, engine->priv->name);

    asset = g_list_next (asset);
  }
}

static void
sim_engine_expand_directive_rule_sensors (GList     *sensors_names,
                                          SimRule   *rule,
                                          gboolean   is_negated)
{
  SimSensor *sensor;

  for (; sensors_names; sensors_names = sensors_names->next)
  {
    if (sim_uuid_is_valid_string (sensors_names->data))
    {
      SimUuid *sensor_id = sim_uuid_new_from_string (sensors_names->data);
      sensor = sim_container_get_sensor_by_id (ossim.container, sensor_id);
      g_object_unref (sensor_id);
    }
    else
    {
      sensor = sim_container_get_sensor_by_name (ossim.container, sensors_names->data);
    }

    if (sensor)
      if (is_negated)
        sim_rule_add_sensor_not (rule, sensor);
      else
        sim_rule_add_sensor (rule, sensor);
    else
      g_message ("%s: unknown asset \"%s\"", __func__, (gchar *) sensors_names->data);
  }
}

static void
sim_engine_expand_directive_rule_entities (GList     *entities_names,
                                           SimRule   *rule,
                                           gboolean   is_negated)
{
  SimContext *context;

  for (; entities_names; entities_names = entities_names->next)
  {
    if (sim_uuid_is_valid_string (entities_names->data))
    {
      SimUuid *context_id = sim_uuid_new_from_string (entities_names->data);
      context = sim_container_get_context (ossim.container, context_id);
      g_object_unref (context_id);
    }
    else
    {
      context = sim_container_get_context_by_name (ossim.container, entities_names->data);
    }

    if (context)
      if (is_negated)
        sim_rule_add_entity_not (rule, context);
      else
        sim_rule_add_entity (rule, context);
    else
      g_message ("Error %s: Unknown entity \"%s\"", __func__, (gchar *) entities_names->data);
  }
}

/*
 * Events in queue
 */

/**
 * sim_engine_init_events_in_queue:
 * @engine: #SimEngine object
 *
 * Initialize the number of events in queue
 * Thread safe
 */
void
sim_engine_init_events_in_queue (SimEngine *engine)
{
  g_return_if_fail (SIM_IS_ENGINE (engine));

  g_atomic_int_set (&engine->priv->events_in_queue, 0);
}

/**
 * sim_engine_get_events_in_queue:
 * @engine: #SimEngine object
 *
 * Returns number of events in queue
 * Thread safe
 */
gint
sim_engine_get_events_in_queue (SimEngine *engine)
{
  g_return_val_if_fail (SIM_IS_ENGINE (engine), 0);

  return (g_atomic_int_get (&engine->priv->events_in_queue));
}

/**
 * sim_engine_has_events_in_queue:
 * @engine: #SimEngine object
 *
 * Returns %TRUE if @engine has events in queue
 * Thread safe
 */
gboolean
sim_engine_has_events_in_queue (SimEngine *engine)
{
  g_return_val_if_fail (SIM_IS_ENGINE (engine), FALSE);

  return (g_atomic_int_get (&engine->priv->events_in_queue) > 0);
}

/**
 * sim_engine_inc_events_in_queue:
 * @engine: #SimEngine object
 *
 * Increments by one events in queue in @engine
 * Thread safe
 */
void
sim_engine_inc_events_in_queue (SimEngine *engine)
{
  g_return_if_fail (SIM_IS_ENGINE (engine));

  g_atomic_int_inc (&engine->priv->events_in_queue);
}

/**
 * sim_engine_dec_events_in_queue:
 * @engine: #SimEngine object
 *
 * Decrements by one events in queue in @engine
 * Thread safe
 */
void
sim_engine_dec_events_in_queue (SimEngine *engine)
{
  g_return_if_fail (SIM_IS_ENGINE (engine));

  (void)g_atomic_int_dec_and_test (&engine->priv->events_in_queue);
}

/*
 * Backlogs
 */

/**
 * sim_engine_lock_backlogs:
 * @engine: #SimEngine object
 *
 * Locks backlogs mutex for @engine
 */
void
sim_engine_lock_backlogs (SimEngine *engine)
{
  g_return_if_fail (SIM_IS_ENGINE (engine));

  g_mutex_lock (&engine->priv->mutex_backlogs);
}

/**
 * sim_engine_un&lock_backlogs:
 * @engine: #SimEngine object
 *
 * Unlocks backlogs mutex for @engine
 */
void
sim_engine_unlock_backlogs (SimEngine *engine)
{
  g_return_if_fail (SIM_IS_ENGINE (engine));

  g_mutex_unlock (&engine->priv->mutex_backlogs);
}

/**
 * sim_engine_get_&backlogs_ul:
 * @engine: #SimEngine object
 *
 * Returns pointer to backlogs array
 */
GPtrArray *
sim_engine_get_backlogs_ul (SimEngine *engine)
{
  g_return_val_if_fail (SIM_IS_ENGINE (engine), NULL);

  return (engine->priv->backlogs);
}

/**
 * sim_engine_get_backlogs:
 * @engine: #SimEngine object
 *
 * Returns pointer to backlogs array
 * Thread safe
 */
GPtrArray *
sim_engine_get_backlogs (SimEngine  *engine)
{
  GPtrArray *backlogs = NULL;

  g_return_val_if_fail (SIM_IS_ENGINE (engine), NULL);

  g_mutex_lock (&engine->priv->mutex_backlogs);
  backlogs = engine->priv->backlogs;
  g_mutex_unlock (&engine->priv->mutex_backlogs);

  return backlogs;
}

/**
 * sim_engine_append_backlog_ul:
 * @engine: #SimEngine object
 * @backlog: a #SimDirective
 *
 * Append @backlog into @engine backlog array
 */
void
sim_engine_append_backlog_ul (SimEngine   *engine,
                              SimDirective *backlog)
{
  g_return_if_fail (SIM_IS_ENGINE (engine));
  g_return_if_fail (SIM_IS_DIRECTIVE (backlog));

  g_ptr_array_add (engine->priv->backlogs, g_object_ref (backlog));
}

/**
 * sim_engine_append_backlog:
 * @engine: #SimEngine object
 * @backlog: a #SimDirective
 *
 * Append @backlog into @engine backlog array (With mutex)
 * Thread safe
 */
void
sim_engine_append_backlog (SimEngine   *engine,
                           SimDirective *backlog)
{
  g_return_if_fail (SIM_IS_ENGINE (engine));
  g_return_if_fail (SIM_IS_DIRECTIVE (backlog));

  g_mutex_lock (&engine->priv->mutex_backlogs);
  g_ptr_array_add (engine->priv->backlogs, g_object_ref (backlog));
  g_mutex_unlock (&engine->priv->mutex_backlogs);
}

/**
 * sim_engine_get_backlogs_len:
 * @engine: #SimEngine object
 *
 * Returns @engine backlog array length
 * Thread safe
 */
gint
sim_engine_get_backlogs_len (SimEngine *engine)
{
  g_return_val_if_fail (SIM_IS_ENGINE (engine), 0);

  return (g_atomic_int_get (&engine->priv->backlogs->len));
}

/**
 * sim_engine_has_backlogs:
 * @engine: #SimEngine object
 *
 * Returns %TRUE if @engine hash any backlog
 * Thread safe
 */
gboolean
sim_engine_has_backlogs (SimEngine *engine)
{
  g_return_val_if_fail (SIM_IS_ENGINE (engine), 0);

  return (g_atomic_int_get (&engine->priv->backlogs->len) > 0);
}

/**
 * sim_engine_delete_backlogs_ul:
 * @engine: a #SimEngine object.
 *
 * Deletes all backlogs that are marked to be removed.
 * Not thread safe version.
 */
void
sim_engine_delete_backlogs_ul (SimEngine *engine)
{
  g_return_if_fail (SIM_IS_ENGINE (engine));

  guint i = 0;

  while (i < engine->priv->backlogs->len)
  {
    SimDirective *backlog = (SimDirective *)g_ptr_array_index (engine->priv->backlogs, i);
    if (sim_directive_backlog_get_deleted (backlog))
    {
      gchar *alarm_stats = sim_directive_alarm_stats_generate (backlog);
      gchar *message = g_strdup_printf ("xxxxxxxxxxxxxxxx%s", alarm_stats);
      memcpy (message, sim_uuid_get_uuid (sim_directive_get_backlog_id (backlog)), 16);
      g_free (alarm_stats);

      sim_container_push_delete_backlog_from_db (ossim.container, message);

      g_ptr_array_remove_index_fast (engine->priv->backlogs, i);
    }
    else
      i++;
  }
}

/**
 * sim_engine_free_backlogs:
 * @engine: a #SimEngine object.
 *
 * Deletes all backlogs in @engine.
 * Thread safe.
 */
void
sim_engine_free_backlogs (SimEngine *engine)
{
  GPtrArray *old_data;

  g_return_if_fail (SIM_IS_ENGINE (engine));

  g_mutex_lock (&engine->priv->mutex_backlogs);

  old_data = engine->priv->backlogs;

  engine->priv->backlogs = g_ptr_array_new_with_free_func ((GDestroyNotify) g_object_unref);

  g_mutex_unlock (&engine->priv->mutex_backlogs);

  /* Delete old data */
  g_ptr_array_unref (old_data);
}

void
sim_engine_remove_expired_backlogs (SimEngine *engine)
{
  SimDirective *backlog;

  g_return_if_fail (SIM_IS_ENGINE (engine));

  // Check first if there are backlogs.
  if (sim_engine_has_backlogs (engine))
  {
    g_mutex_lock (&engine->priv->mutex_backlogs);

    // There are no events waiting to be processed, so we need to check the array
    // here because the correlation process is stopped.
    if (!sim_engine_has_events_in_queue (engine))
    {
      gint i;
      for (i = 0; i < g_atomic_int_get (&engine->priv->backlogs->len); i++)
      {
        backlog = (SimDirective *)g_ptr_array_index (engine->priv->backlogs, i);
        if (sim_directive_backlog_is_expired (backlog))
        {
          sim_directive_backlog_set_deleted (backlog, TRUE);
        }
      }
    }

    // Clean backlogs marked to be removed.
    sim_engine_delete_backlogs_ul (engine);

    g_mutex_unlock (&engine->priv->mutex_backlogs);
  }
}

/*
 * Group Alarms
 */

/**
 * sim_engine_append_group_alarm:
 * @engine: a #SimEngine object.
 * @group_alarm: #SimGroupAlarm to append
 * @key: gchar * with key
 *
 * Appends @group_alarm in @engine using @key
 * Thread safe
 */
void
sim_engine_append_group_alarm (SimEngine    *engine,
                               SimGroupAlarm *group_alarm,
                               gchar         *key)
{
  g_return_if_fail (SIM_IS_ENGINE (engine));
  g_return_if_fail (SIM_IS_GROUP_ALARM (group_alarm));
  g_return_if_fail (key != NULL);

  g_mutex_lock (&engine->priv->mutex_group_alarms);
  g_hash_table_insert (engine->priv->group_alarms, g_strdup (key), g_object_ref (group_alarm));
  g_mutex_unlock (&engine->priv->mutex_group_alarms);
}

/**
 * sim_engine_lookup_group_alarm:
 * @engine: a #SimEngine object.
 * @key: gchar * with key
 *
 * If group_alarm with @key is 'timeout' then deletes it
 * and returns %NULL.
 *
 * Return group_alarm in @engine with @key, or %NULL
 * Thread safe
 */
SimGroupAlarm *
sim_engine_lookup_group_alarm (SimEngine *engine,
                               gchar      *key)
{
  SimGroupAlarm *group_alarm = NULL;

  g_return_val_if_fail (SIM_IS_ENGINE (engine), NULL);
  g_return_val_if_fail (key != NULL, NULL);

  g_mutex_lock (&engine->priv->mutex_group_alarms);

  group_alarm = g_hash_table_lookup (engine->priv->group_alarms, key);
  if (group_alarm)
  {
    /* If group alarm is 'timeout' deletes it */
    if (sim_group_alarm_is_timeout (group_alarm))
    {
      g_hash_table_remove (engine->priv->group_alarms, (gconstpointer)key);
      group_alarm = NULL;
    }
    else
    {
      /* Only returns the group alarm when it's not 'timeout' */
      g_object_ref (group_alarm);
    }
  }

  g_mutex_unlock (&engine->priv->mutex_group_alarms);

  return group_alarm;
}

/**
 * sim_engine_remove_expired_group_alarm:
 * @engine: a #SimEngine object.
 *
 * Deletes all expired #SimGroupAlarms in @engine
 * Thread safe
 */
void
sim_engine_remove_expired_group_alarms (SimEngine *engine)
{
  g_return_if_fail (SIM_IS_ENGINE (engine));

  g_mutex_lock (&engine->priv->mutex_group_alarms);

  g_hash_table_foreach_remove (engine->priv->group_alarms,
                               _sim_engine_group_alarm_is_timeout, NULL);

  g_mutex_unlock (&engine->priv->mutex_group_alarms);
}

/**
 * _sim_engine_group_alarm_is_timeout:
 *
 * Returns if group_alarm is 'timeout'
 */
static gboolean
_sim_engine_group_alarm_is_timeout (gpointer key,
                                    gpointer value,
                                    gpointer user_data)
{
  // unused parameter
  (void) key;
  (void) user_data;

  return sim_group_alarm_is_timeout ((SimGroupAlarm *)value);
}

/*
 * Stats Data
 */

/**
 * sim_engine_get_stats:
 * @engine: a #SimEngine object.
 * @elapsed_time: time elapsed in seconds
 *
 * Returns gchar * with @engine stats.
 * It must be freed outside.
 */
gchar *
sim_engine_get_stats (SimEngine *engine,
                      glong      elapsed_time)
{
  gchar *stats;
  gint   queue;
  gint   total;
  gint   eps;
  gint   backlogs;

  g_return_val_if_fail (SIM_IS_ENGINE (engine), NULL);

  queue = MAX (sim_engine_get_events_in_queue (engine), 0);
  total = engine->priv->total_events;
  eps = (total - engine->priv->last_total) / elapsed_time;
  backlogs = g_atomic_int_get (&engine->priv->backlogs->len);

  stats = g_strdup_printf ("[EN %s q:%u, popped:%u, eps:%u, backlogs:%u]",
                           engine->priv->name, queue, total, eps, backlogs);

  engine->priv->last_total = total;

  return stats;
}
/**
  * @brief Return the stats in json format
  * @param engine a #SimEngine object
  * @param elapsed_tine time elapsed in seconds
  * Return a gchar *  with stats. It must be freed outside
*/
gchar *
sim_engine_get_stats_json (SimEngine *engine,
                      glong      elapsed_time)
{
  gchar *stats;
  gint   queue;
  gint   total;
  gint   eps;
  gint   backlogs;

  g_return_val_if_fail (SIM_IS_ENGINE (engine), NULL);

  queue = MAX (sim_engine_get_events_in_queue (engine), 0);
  total = engine->priv->total_events;
  eps = (total - engine->priv->last_total) / elapsed_time;
  backlogs = engine->priv->backlogs->len;

  stats = g_strdup_printf ("{\"engine_name\":\"%s\",\n" 
                           "\"q\":\"%u\",\n"
                           "\"popped\":\"%u\",\n"
                           "\"eps\":\"%u\",\n"
                           "\"backlogs\":\"%u\"\n}",
                           engine->priv->name,
                           queue,
                           total,
                           eps,
                           backlogs);
  engine->priv->last_total = total;

  return stats;
}
/**
 * sim_engine_inc_total_events:
 * @engine: a #SimEngine object.
 *
 * Increments total events number in @engine
 */
void
sim_engine_inc_total_events (SimEngine *engine)
{
  g_return_if_fail (SIM_IS_ENGINE (engine));

  engine->priv->total_events++;
}

gboolean
sim_engine_add_otx_data (SimEngine     *engine, SimContext *ctx, GHashTable    *otx_data)
{
  g_return_val_if_fail (engine != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_ENGINE (engine), FALSE);
  g_return_val_if_fail (otx_data != NULL, FALSE);
  GHashTableIter iter;
  gchar *key;
  gpointer value;
  g_mutex_lock (&engine->priv->mutex_directives);
  /* I need to verify that each pulse_id is not added */
  g_hash_table_iter_init (&iter, otx_data);
  while (g_hash_table_iter_next (&iter, (gpointer*)&key, &value))
  {
    if (!g_hash_table_lookup (engine->priv->otx_data, (gpointer)key))
    {
      SimDirective * ghost = sim_directive_create_pulse_backlog ((gchar*) key);
      SimPluginSid * sid = sim_plugin_sid_new_from_data (1505, SIM_DIRECTIVE_PULSE_ID, 10, 5, "Rule pulse_match");
      /*ctx = sim_container_get_context (ossim.container,
                                                  sim_engine_get_id (engine));*/
      /* Must be the ENGINED contxt */
      ctx = sim_container_get_engine_ctx (ossim.container);
      sim_context_add_plugin_sid (ctx, sid);
      g_object_unref (sid);
      sim_engine_append_directive (engine, ghost);
      sim_db_update_plugin_sid (engine->priv->database, ghost, engine->priv->id);
      g_hash_table_insert (engine->priv->otx_data, g_strdup (key),  GINT_TO_POINTER (GENERIC_VALUE));
    } 
  }
  
  g_mutex_unlock (&engine->priv->mutex_directives);
  return TRUE;
}
#ifdef USE_UNITTESTS

/*************************************************************
 *******************      Unit tests      ********************
 *************************************************************/

gboolean sim_engine_test1 (void);
gboolean sim_engine_test2 (void);
gboolean sim_engine_test3 (void);

gboolean
sim_engine_test1 (void)
{
  gboolean success = TRUE;

  SimEngine *engine;
  SimUuid *id;

  id = sim_uuid_new ();

  engine = sim_engine_new (id, "test");
  g_object_unref (engine);

  engine = sim_engine_new_full (id, "test", NULL, NULL, NULL, NULL);
  g_object_unref (engine);

  return success;
}

gboolean
sim_engine_test2 (void)
{
  gboolean success = TRUE;

  SimEngine *engine;
  SimDirective *directive;
  SimUuid *id;
  GPtrArray *directives_by_plugin;
  GHashTable *plugin_ids;

  id = sim_uuid_new ();

  engine = sim_engine_new (id, "test");

  directive = sim_new_test_directive ();

  g_return_val_if_fail (SIM_IS_DIRECTIVE (directive), FALSE);

  sim_engine_append_directive (engine, directive);
  sim_engine_append_directive (engine, directive);
  sim_engine_append_directive (engine, directive);

  plugin_ids = sim_directive_get_root_plugin_ids (directive);
  if (plugin_ids)
  {
    GHashTableIter iter;
    gpointer key, value;

    g_hash_table_iter_init (&iter, plugin_ids);
    while (g_hash_table_iter_next (&iter, &key, &value))
    {
      directives_by_plugin = sim_engine_get_directives_by_plugin_id (engine, GPOINTER_TO_INT (key));
      if (!directives_by_plugin)
      {
        g_print ("Error getting directives array by plugin");
        success = FALSE;
      }
      else if (directives_by_plugin->len != 3)
      {
        g_print ("Error append directives");
        success = FALSE;
      }
    }
  }

  g_object_unref (directive);
  g_object_unref (engine);

  return success;
}

gboolean
sim_engine_test3 (void)
{
  gboolean success = TRUE;

  SimEngine *engine;
  SimDirective *backlog;
  SimDirective *backlog_copy;
  gint backlog_len = 0;
  SimUuid *id;

  id = sim_uuid_new ();

  engine = sim_engine_new (id, "test");

  backlog = sim_new_test_directive ();
  sim_directive_set_backlog_id (backlog, id);

  g_return_val_if_fail (SIM_IS_DIRECTIVE (backlog), FALSE);

  /* Get */
  if (sim_engine_get_backlogs (engine) == NULL)
    success = FALSE;

  if (sim_engine_has_backlogs (engine))
    success = FALSE;

  /* Append */
  sim_engine_append_backlog (engine, backlog);
  backlog_len++;
  sim_engine_append_backlog (engine, backlog);
  backlog_len++;
  sim_engine_append_backlog (engine, backlog);
  backlog_len++;

  if (!sim_engine_has_backlogs (engine))
    success = FALSE;

  if (backlog_len != sim_engine_get_backlogs_len (engine))
    success = FALSE;

  backlog_copy = sim_directive_clone (backlog, engine);
  sim_directive_set_backlog_id (backlog_copy, id);

  sim_engine_append_backlog (engine, backlog_copy);
  backlog_len++;
  sim_engine_append_backlog (engine, backlog_copy);
  backlog_len++;

  if (!sim_engine_has_backlogs (engine))
    success = FALSE;

  if (backlog_len != sim_engine_get_backlogs_len (engine))
    success = FALSE;

  /* delete */
  sim_directive_backlog_set_deleted (backlog_copy, TRUE);

  sim_engine_delete_backlogs_ul (engine);
  backlog_len -= 2;

  if (backlog_len != sim_engine_get_backlogs_len (engine))
    success = FALSE;

  /* free */
  sim_engine_free_backlogs (engine);
  backlog_len = 0;

  if (sim_engine_has_backlogs (engine))
    success = FALSE;

  if (backlog_len != sim_engine_get_backlogs_len (engine))
    success = FALSE;

  /* Append after free */
  sim_engine_append_backlog (engine, backlog);
  backlog_len++;

  if (!sim_engine_has_backlogs (engine))
    success = FALSE;

  if (backlog_len != sim_engine_get_backlogs_len (engine))
    success = FALSE;

  g_object_unref (backlog);
  g_object_unref (backlog_copy);

  g_object_unref (engine);

  return success;
}

void
sim_engine_register_tests (SimUnittesting *engine)
{
  sim_unittesting_append (engine, "sim_engine_test1 - New", sim_engine_test1, TRUE);
  sim_unittesting_append (engine, "sim_engine_test2 - Directives", sim_engine_test2, TRUE);
  sim_unittesting_append (engine, "sim_engine_test3 - Backlogs", sim_engine_test3, TRUE);
}
#endif /* USE_UNITTESTS */

// vim: set tabstop=2:

