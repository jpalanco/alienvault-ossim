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

#include "sim-plugin.h"

#include "os-sim.h"	//log
#include "sim-util.h"

enum
{
  DESTROY,
  LAST_SIGNAL
};

struct _SimPluginPrivate {
  gint                 id;
  gchar               *name;
  gchar               *description;
  gint                 product;
};

static gpointer parent_class = NULL;
/* We don't use signals */
/*
static gint sim_plugin_signals[LAST_SIGNAL] = { 0 };
*/

/* GType Functions */

static void 
sim_plugin_impl_dispose (GObject  *gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void 
sim_plugin_impl_finalize (GObject  *gobject)
{
  SimPlugin *plugin = SIM_PLUGIN (gobject);

  if (plugin->_priv->name)
    g_free (plugin->_priv->name);
  if (plugin->_priv->description)
    g_free (plugin->_priv->description);

  g_free (plugin->_priv);

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_plugin_class_init (SimPluginClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_ref (G_TYPE_OBJECT);

  object_class->dispose = sim_plugin_impl_dispose;
  object_class->finalize = sim_plugin_impl_finalize;
}

static void
sim_plugin_instance_init (SimPlugin *plugin)
{
  plugin->_priv = g_new0 (SimPluginPrivate, 1);

  plugin->type = SIM_PLUGIN_TYPE_NONE;

  plugin->_priv->id = 0;
  plugin->_priv->name = NULL;
  plugin->_priv->description = NULL;
  plugin->_priv->product = 0;
}

/* Public Methods */

GType
sim_plugin_get_type (void)
{
  static GType object_type = 0;
 
  if (!object_type)
  {
    static const GTypeInfo type_info = {
              sizeof (SimPluginClass),
              NULL,
              NULL,
              (GClassInitFunc) sim_plugin_class_init,
              NULL,
              NULL,                       /* class data */
              sizeof (SimPlugin),
              0,                          /* number of pre-allocs */
              (GInstanceInitFunc) sim_plugin_instance_init,
              NULL                        /* value table */
    };
    
                                                                                                                             
    object_type = g_type_register_static (G_TYPE_OBJECT, "SimPlugin", &type_info, 0);
  }

  return object_type;
}

/*
 *
 *
 *
 *
 */
SimPlugin*
sim_plugin_new (void)
{
  SimPlugin *plugin = NULL;

  plugin = SIM_PLUGIN (g_object_new (SIM_TYPE_PLUGIN, NULL));

  return plugin;
}

/*
 *
 *
 *
 */
SimPlugin*
sim_plugin_new_from_dm (GdaDataModel  *dm, gint row)
{
  SimPlugin    *plugin;
  const GValue *value;
  GValue  smallint = { 0, {{0}, {0}} };

  g_return_val_if_fail (dm, NULL);
  g_return_val_if_fail (GDA_IS_DATA_MODEL (dm), NULL);

  plugin = SIM_PLUGIN (g_object_new (SIM_TYPE_PLUGIN, NULL));

  value = gda_data_model_get_value_at (dm, 0, row, NULL);
  plugin->_priv->id = g_value_get_int (value);
  
  value = gda_data_model_get_value_at (dm, 1, row, NULL);
  g_value_init (&smallint, GDA_TYPE_SHORT);
  g_value_transform (value, &smallint);
  plugin->type = gda_value_get_short (&smallint);
  
  value = gda_data_model_get_value_at (dm, 2, row, NULL);
  plugin->_priv->name = g_value_dup_string (value);

  value = gda_data_model_get_value_at (dm, 3, row, NULL);
  plugin->_priv->description = g_value_dup_string (value);

  value = gda_data_model_get_value_at (dm, 4, row, NULL);
  plugin->_priv->product = g_value_get_int (value);

  return plugin;
}

/*
 *
 *
 *
 *
 */
SimPlugin*
sim_plugin_clone (SimPlugin *plugin)
{
  SimPlugin *new_plugin;
  
  g_return_val_if_fail (plugin, NULL);
  g_return_val_if_fail (SIM_IS_PLUGIN (plugin), NULL);

  new_plugin = SIM_PLUGIN (g_object_new (SIM_TYPE_PLUGIN, NULL));
  new_plugin->type = plugin->type;
  new_plugin->_priv->id = plugin->_priv->id;
  new_plugin->_priv->name = (plugin->_priv->name) ? g_strdup (plugin->_priv->name) : NULL;
  new_plugin->_priv->description = (plugin->_priv->description) ? g_strdup (plugin->_priv->description) : NULL;

  return new_plugin;
}

/*
 *
 *
 *
 *
 */
gint
sim_plugin_get_id (SimPlugin  *plugin)
{
  g_return_val_if_fail (plugin, 0);
  g_return_val_if_fail (SIM_IS_PLUGIN (plugin), 0);

  return plugin->_priv->id;
}

/*
 *
 *
 *
 *
 */
void
sim_plugin_set_id (SimPlugin  *plugin,
		   gint        id)
{
  g_return_if_fail (plugin);
  g_return_if_fail (SIM_IS_PLUGIN (plugin));
  g_return_if_fail (id > 0);

  plugin->_priv->id = id;
}

/*
 *
 *
 *
 *
 */
gchar*
sim_plugin_get_name (SimPlugin  *plugin)
{
  g_return_val_if_fail (plugin, NULL);
  g_return_val_if_fail (SIM_IS_PLUGIN (plugin), NULL);

  return plugin->_priv->name;
}

/*
 *
 *
 *
 *
 */
void
sim_plugin_set_name (SimPlugin  *plugin,
		     gchar      *name)
{
  g_return_if_fail (plugin);
  g_return_if_fail (SIM_IS_PLUGIN (plugin));
  g_return_if_fail (name);

  if (plugin->_priv->name)
    g_free (plugin->_priv->name);

  plugin->_priv->name = name;
}

/*
 *
 *
 *
 *
 */
gchar*
sim_plugin_get_description (SimPlugin  *plugin)
{
  g_return_val_if_fail (plugin, NULL);
  g_return_val_if_fail (SIM_IS_PLUGIN (plugin), NULL);

  return plugin->_priv->description;
}

/*
 *
 *
 *
 *
 */
void
sim_plugin_set_description (SimPlugin  *plugin,
			    gchar      *description)
{
  g_return_if_fail (plugin);
  g_return_if_fail (SIM_IS_PLUGIN (plugin));
  g_return_if_fail (description);

  if (plugin->_priv->description)
    g_free (plugin->_priv->description);

  plugin->_priv->description = description;
}

gint
sim_plugin_get_product (SimPlugin *plugin)
{
  g_return_val_if_fail (plugin, 0);
  g_return_val_if_fail (SIM_IS_PLUGIN (plugin), 0);

  return plugin->_priv->product;
}

/*
 * This function should be called sim_plugin_set_type, but there is other "get" function called with that name.
 */
void
sim_plugin_set_sim_type (SimPlugin  		*plugin,
											   SimPluginType  type)
{
  g_return_if_fail (plugin);
  g_return_if_fail (SIM_IS_PLUGIN (plugin));

  plugin->type = type;
}

/*
 * This function should be called sim_plugin_get_type, but there is another function called with that name.
 */
SimPluginType
sim_plugin_get_sim_type (SimPlugin  		*plugin)
{
  g_return_val_if_fail (plugin, SIM_PLUGIN_TYPE_NONE);
  g_return_val_if_fail (SIM_IS_PLUGIN (plugin), SIM_PLUGIN_TYPE_NONE);

  return plugin->type;
}

/*
 * Debug function.
 */
void sim_plugin_debug_print (SimPlugin	*plugin)
{
  g_return_if_fail (plugin);
  g_return_if_fail (SIM_IS_PLUGIN (plugin));

   ossim_debug ( "sim_plugin_debug_print:");
   ossim_debug ( "         type: %d", plugin->type);
   ossim_debug ( "         description: %s", plugin->_priv->description);
   ossim_debug ( "         name: %s", plugin->_priv->name);
   ossim_debug ( "         id: %d", plugin->_priv->id);


}

// vim: set tabstop=2:
