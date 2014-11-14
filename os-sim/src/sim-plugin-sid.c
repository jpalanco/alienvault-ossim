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

#include "sim-plugin-sid.h"

#include "os-sim.h"
#include "sim-util.h"
#include "sim-log.h"

extern SimMain  ossim;

enum
{
  DESTROY,
  LAST_SIGNAL
};

struct _SimPluginSidPrivate {
  gint     plugin_id;
  gint     sid;
  guint    cantor_key;
  gint     reliability;
  gint     priority;
  gchar   *name;
  gint     category;
  gint     subcategory;
};

static gpointer parent_class = NULL;
/* Disable signals
static gint sim_plugin_sid_signals[LAST_SIGNAL] = { 0 };
*/

/* GType Functions */

static void 
sim_plugin_sid_impl_dispose (GObject  *gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void 
sim_plugin_sid_impl_finalize (GObject  *gobject)
{
  SimPluginSid *plugin = SIM_PLUGIN_SID (gobject);

  if (plugin->_priv->name)
	{
  	ossim_debug ( "sim_plugin_sid_impl_finalize: %s",plugin->_priv->name);
    g_free (plugin->_priv->name);
	}
  ossim_debug ( "sim_plugin_sid_impl_finalize");

  g_free (plugin->_priv);

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_plugin_sid_class_init (SimPluginSidClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_ref (G_TYPE_OBJECT);

  object_class->dispose = sim_plugin_sid_impl_dispose;
  object_class->finalize = sim_plugin_sid_impl_finalize;
}

static void
sim_plugin_sid_instance_init (SimPluginSid *plugin)
{
  plugin->_priv = g_new0 (SimPluginSidPrivate, 1);

  plugin->_priv->plugin_id = 0;
  plugin->_priv->sid = 0;
  plugin->_priv->cantor_key = 0;
  plugin->_priv->reliability = 1;
  plugin->_priv->priority = 1;
  plugin->_priv->name = NULL;
  plugin->_priv->category = 0;
  plugin->_priv->subcategory = 0;
}

/* Public Methods */

GType
sim_plugin_sid_get_type (void)
{
  static GType object_type = 0;
 
  if (!object_type)
  {
    static const GTypeInfo type_info = {
              sizeof (SimPluginSidClass),
              NULL,
              NULL,
              (GClassInitFunc) sim_plugin_sid_class_init,
              NULL,
              NULL,                       /* class data */
              sizeof (SimPluginSid),
              0,                          /* number of pre-allocs */
              (GInstanceInitFunc) sim_plugin_sid_instance_init,
              NULL                        /* value table */
    };
    
    g_type_init ();
                                                                                                                             
    object_type = g_type_register_static (G_TYPE_OBJECT, "SimPluginSid", &type_info, 0);
  }

  return object_type;
}

/*
 *
 *
 *
 *
 */
SimPluginSid*
sim_plugin_sid_new (void)
{
  SimPluginSid *plugin_sid = NULL;

  plugin_sid = SIM_PLUGIN_SID (g_object_new (SIM_TYPE_PLUGIN_SID, NULL));

  return plugin_sid;
}

/*
 *
 *
 *
 *
 */
SimPluginSid*
sim_plugin_sid_new_from_data (gint          plugin_id,
												      gint          sid,
												      gint          reliability,
												      gint          priority,
												      const gchar  *name)
{
  SimPluginSid *plugin_sid = NULL;

  plugin_sid = SIM_PLUGIN_SID (g_object_new (SIM_TYPE_PLUGIN_SID, NULL));
  plugin_sid->_priv->plugin_id = plugin_id;
  plugin_sid->_priv->sid = sid;
  plugin_sid->_priv->cantor_key = CANTOR_KEY(plugin_id, sid);
  plugin_sid->_priv->reliability = reliability;
  plugin_sid->_priv->priority = priority;
  plugin_sid->_priv->name = g_strdup (name);  

  return plugin_sid;
}

/*
 * This is probably the slowest function, as it has to load tons of data.
 * I try to speed it up as much as possible.
 *
 */
inline SimPluginSid*
sim_plugin_sid_new_from_dm (GdaDataModel *dm, gint row)
{
  SimPluginSid  *plugin_sid;
  const GValue  *value;

  plugin_sid = SIM_PLUGIN_SID (g_object_new (SIM_TYPE_PLUGIN_SID, NULL));

  value = gda_data_model_get_value_at (dm, 0, row, NULL);
  if (!gda_value_is_null (value))
    plugin_sid->_priv->plugin_id = g_value_get_int (value);

  value = gda_data_model_get_value_at (dm, 1, row, NULL);
  if (!gda_value_is_null (value))
    plugin_sid->_priv->sid = g_value_get_int (value);
  
  plugin_sid->_priv->cantor_key = CANTOR_KEY(plugin_sid->_priv->plugin_id, plugin_sid->_priv->sid);

  value = gda_data_model_get_value_at (dm, 2, row, NULL);
  if (!gda_value_is_null (value))
	{
    plugin_sid->_priv->reliability = g_value_get_int (value);
		if (plugin_sid->_priv->reliability > 10)
			plugin_sid->_priv->reliability = 10;
	}
  
  value = gda_data_model_get_value_at (dm, 3, row, NULL);
  if (!gda_value_is_null (value))
	{
    plugin_sid->_priv->priority = g_value_get_int (value);
		if (plugin_sid->_priv->priority > 5)
			plugin_sid->_priv->priority = 5;
	}
  
  value = gda_data_model_get_value_at (dm, 4, row, NULL);
  if (!gda_value_is_null (value))
    plugin_sid->_priv->name = g_value_dup_string (value);

  value = gda_data_model_get_value_at (dm, 5, row, NULL);
  if (!gda_value_is_null (value))
    plugin_sid->_priv->category = g_value_get_int (value);

  value = gda_data_model_get_value_at (dm, 6, row, NULL);
  if (!gda_value_is_null (value))
    plugin_sid->_priv->subcategory = g_value_get_int (value);

	sim_string_substitute_char (plugin_sid->_priv->name, '\\', ' ');
	sim_string_substitute_char (plugin_sid->_priv->name, ';', ' ');
	sim_string_substitute_char (plugin_sid->_priv->name, '\'', ' ');
	sim_string_substitute_char (plugin_sid->_priv->name, '\"', ' ');
	sim_string_substitute_char (plugin_sid->_priv->name, '\r', ' ');
	sim_string_substitute_char (plugin_sid->_priv->name, '\n', ' ');
	
  return plugin_sid;
}

/*
 *
 * Returns the plugin id which is the "owner" of the plugin_sid given.
 *
 *
 */
inline gint
sim_plugin_sid_get_plugin_id (SimPluginSid  *plugin_sid)
{
  g_return_val_if_fail (SIM_IS_PLUGIN_SID (plugin_sid), 0);

  return plugin_sid->_priv->plugin_id;
}

/*
 *
 *
 *
 *
 */
void
sim_plugin_sid_set_plugin_id (SimPluginSid  *plugin_sid,
			      gint           plugin_id)
{
  g_return_if_fail (plugin_sid);
  g_return_if_fail (SIM_IS_PLUGIN_SID (plugin_sid));
  g_return_if_fail (plugin_id > 0);

  plugin_sid->_priv->plugin_id = plugin_id;
  plugin_sid->_priv->cantor_key = CANTOR_KEY(plugin_sid->_priv->plugin_id, plugin_sid->_priv->sid);
}

/*
 *
 * gets the sid from the object plugin_sid
 *
 *
 */
gint
sim_plugin_sid_get_sid (SimPluginSid  *plugin_sid)
{
  g_return_val_if_fail (plugin_sid, 0);
  g_return_val_if_fail (SIM_IS_PLUGIN_SID (plugin_sid), 0);

  return plugin_sid->_priv->sid;
}

/*
 *
 *
 *
 *
 */
void
sim_plugin_sid_set_sid (SimPluginSid  *plugin_sid,
			gint           sid)
{
  g_return_if_fail (plugin_sid);
  g_return_if_fail (SIM_IS_PLUGIN_SID (plugin_sid));
  g_return_if_fail (sid > 0);

  plugin_sid->_priv->sid = sid;
  plugin_sid->_priv->cantor_key = CANTOR_KEY(plugin_sid->_priv->plugin_id, plugin_sid->_priv->sid);
}

/*
 *
 *
 *
 *
 */
gint
sim_plugin_sid_get_reliability (SimPluginSid  *plugin_sid)
{
  g_return_val_if_fail (plugin_sid, -1);
  g_return_val_if_fail (SIM_IS_PLUGIN_SID (plugin_sid), -1);

  return plugin_sid->_priv->reliability;
}

/*
 *
 *
 *
 *
 */
void
sim_plugin_sid_set_reliability (SimPluginSid  *plugin_sid,
			      gint           reliability)
{
  g_return_if_fail (plugin_sid);
  g_return_if_fail (SIM_IS_PLUGIN_SID (plugin_sid));

  plugin_sid->_priv->reliability = reliability;
}

/*
 *
 *
 *
 *
 */
gint
sim_plugin_sid_get_priority (SimPluginSid  *plugin_sid)
{
  g_return_val_if_fail (plugin_sid, -1);
  g_return_val_if_fail (SIM_IS_PLUGIN_SID (plugin_sid), -1);

  return plugin_sid->_priv->priority;
}

/*
 *
 *
 *
 *
 */
void
sim_plugin_sid_set_priority (SimPluginSid  *plugin_sid,
			      gint           priority)
{
  g_return_if_fail (plugin_sid);
  g_return_if_fail (SIM_IS_PLUGIN_SID (plugin_sid));

  plugin_sid->_priv->priority = priority;
}


/*
 *
 *
 *
 *
 */
gchar*
sim_plugin_sid_get_name (SimPluginSid  *plugin_sid)
{
  g_return_val_if_fail (plugin_sid, NULL);
  g_return_val_if_fail (SIM_IS_PLUGIN_SID (plugin_sid), NULL);

  return plugin_sid->_priv->name;
}

/*
 *
 *
 *
 *
 */
void
sim_plugin_sid_set_name (SimPluginSid  *plugin_sid,
			 gchar         *name)
{
  g_return_if_fail (plugin_sid);
  g_return_if_fail (SIM_IS_PLUGIN_SID (plugin_sid));
  g_return_if_fail (name);

  if (plugin_sid->_priv->name)
    g_free (plugin_sid->_priv->name);

  plugin_sid->_priv->name = name;
}

gint
sim_plugin_sid_get_category (SimPluginSid *plugin_sid)
{
  g_return_val_if_fail (plugin_sid, 0);
  g_return_val_if_fail (SIM_IS_PLUGIN_SID (plugin_sid), 0);

  return plugin_sid->_priv->category;
}

gint
sim_plugin_sid_get_subcategory (SimPluginSid *plugin_sid)
{
  g_return_val_if_fail (plugin_sid, 0);
  g_return_val_if_fail (SIM_IS_PLUGIN_SID (plugin_sid), 0);

  return plugin_sid->_priv->subcategory;
}

/*
 *
 *
 *
 *
 */
gchar*
sim_plugin_sid_get_insert_clause (SimPluginSid  *plugin_sid,
                                  SimUuid       *plugin_ctx)
{
  GString  *insert;
  GString  *values;

  g_return_val_if_fail (plugin_sid, NULL);
  g_return_val_if_fail (SIM_IS_PLUGIN_SID (plugin_sid), NULL);
  g_return_val_if_fail (plugin_sid->_priv->plugin_id > 0, NULL);
  g_return_val_if_fail (plugin_sid->_priv->sid > 0, NULL);
  g_return_val_if_fail (plugin_sid->_priv->name, NULL);

  insert = g_string_new ("REPLACE INTO plugin_sid (");
  values = g_string_new (" VALUES (");

  g_string_append (insert, "plugin_id");
  g_string_append_printf (values, "%d", plugin_sid->_priv->plugin_id);

  g_string_append (insert, ", sid");
  g_string_append_printf (values, ", %d", plugin_sid->_priv->sid);

  g_string_append (insert, ", reliability");
  g_string_append_printf (values, ", %d", plugin_sid->_priv->reliability);
  g_string_append (insert, ", priority");
  g_string_append_printf (values, ", %d", plugin_sid->_priv->priority);

  g_string_append (insert, ", name");
  g_string_append_printf (values, ", '%s'", plugin_sid->_priv->name);

  g_string_append (insert, ", plugin_ctx)");
  g_string_append_printf (values, ", %s)", sim_uuid_get_db_string (plugin_ctx));

  g_string_append (insert, values->str);

  g_string_free (values, TRUE);

  return g_string_free (insert, FALSE);
}

/*
 *
 * Debug function
 *
 *
 */
void
sim_plugin_sid_debug_print (SimPluginSid  *plugin_sid)
{

  ossim_debug ( "sim_plugin_sid_print_internal_data:");

  ossim_debug ( "       Name: %s", plugin_sid->_priv->name);
  ossim_debug ( "       plugin_id: %d", plugin_sid->_priv->plugin_id);
  ossim_debug ( "       sid: %d", plugin_sid->_priv->sid);
  ossim_debug ( "       Cantor key: %d", plugin_sid->_priv->cantor_key);
  ossim_debug ( "       reliability: %d", plugin_sid->_priv->reliability);
  ossim_debug ( "       priority: %d", plugin_sid->_priv->priority);
}

/*
 *
 * gets the cantor key from the object plugin_sid
 *
 *
 */
guint
sim_plugin_sid_get_cantor_key (SimPluginSid  *plugin_sid)
{
  g_return_val_if_fail (plugin_sid, 0);
  g_return_val_if_fail (SIM_IS_PLUGIN_SID (plugin_sid), 0);

  return plugin_sid->_priv->cantor_key;
}

// vim: set tabstop=2:
