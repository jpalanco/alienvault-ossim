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

#include "sim-plugin-state.h"

struct _SimPluginStatePrivate {
  SimPlugin   *plugin;

  gint         plugin_id;
  gint         state;
  gboolean     enabled;
};

static gpointer parent_class = NULL;

/* GType Functions */

static void 
sim_plugin_state_impl_dispose (GObject  *gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void 
sim_plugin_state_impl_finalize (GObject  *gobject)
{
  //SimPluginState *plugin = SIM_PLUGIN_STATE (gobject);

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_plugin_state_class_init (SimPluginStateClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_ref (G_TYPE_OBJECT);

  object_class->dispose = sim_plugin_state_impl_dispose;
  object_class->finalize = sim_plugin_state_impl_finalize;
}

static void
sim_plugin_state_instance_init (SimPluginState *plugin)
{
  plugin->_priv = g_new0 (SimPluginStatePrivate, 1);

  plugin->_priv->plugin_id = 0;
  plugin->_priv->plugin = NULL;
  plugin->_priv->state = 0;
  plugin->_priv->enabled = FALSE;
}

/* Public Methods */

GType
sim_plugin_state_get_type (void)
{
  static GType object_type = 0;
 
  if (!object_type)
  {
    static const GTypeInfo type_info = {
              sizeof (SimPluginStateClass),
              NULL,
              NULL,
              (GClassInitFunc) sim_plugin_state_class_init,
              NULL,
              NULL,                       /* class data */
              sizeof (SimPluginState),
              0,                          /* number of pre-allocs */
              (GInstanceInitFunc) sim_plugin_state_instance_init,
              NULL                        /* value table */
    };
    
                                                                                                                             
    object_type = g_type_register_static (G_TYPE_OBJECT, "SimPluginState", &type_info, 0);
  }

  return object_type;
}

/*
 *
 *
 *
 *
 */
SimPluginState*
sim_plugin_state_new (void)
{
  SimPluginState *plugin_state = NULL;

  plugin_state = SIM_PLUGIN_STATE (g_object_new (SIM_TYPE_PLUGIN_STATE, NULL));

  return plugin_state;
}

/*
 *
 *
 *
 *
 */
SimPluginState*
sim_plugin_state_new_from_data (SimPlugin    *plugin,
				gint          plugin_id,
				gint          state,
				gboolean      enabled)
{
  SimPluginState *plugin_state = NULL;

  g_return_val_if_fail (plugin, NULL);
  g_return_val_if_fail (SIM_IS_PLUGIN (plugin), NULL);
  g_return_val_if_fail (state >= 0, NULL);

  plugin_state = SIM_PLUGIN_STATE (g_object_new (SIM_TYPE_PLUGIN_STATE, NULL));
  plugin_state->_priv->plugin = plugin;
  plugin_state->_priv->plugin_id = plugin_id;
  plugin_state->_priv->state = state;
  plugin_state->_priv->enabled = enabled;

  return plugin_state;
}

/*
 *
 *
 *
 *
 */
SimPlugin*
sim_plugin_state_get_plugin (SimPluginState   *plugin_state)
{
  g_return_val_if_fail (plugin_state, NULL);
  g_return_val_if_fail (SIM_IS_PLUGIN_STATE (plugin_state), NULL);

  return plugin_state->_priv->plugin;
}

/*
 *
 *
 *
 *
 */
void
sim_plugin_state_set_plugin (SimPluginState   *plugin_state,
                             SimPlugin        *plugin)
{
  g_return_if_fail (SIM_IS_PLUGIN_STATE (plugin_state));
  g_return_if_fail (SIM_IS_PLUGIN (plugin));

  plugin_state->_priv->plugin = g_object_ref (plugin);
}

/*
 *
 *
 *
 *
 */
gint
sim_plugin_state_get_plugin_id (SimPluginState   *plugin_state)
{
  g_return_val_if_fail (plugin_state, 0);
  g_return_val_if_fail (SIM_IS_PLUGIN_STATE (plugin_state), 0);

  return plugin_state->_priv->plugin_id;
}

/*
 *
 *
 *
 *
 */
void
sim_plugin_state_set_plugin_id (SimPluginState   *plugin_state,
				gint              plugin_id)
{
  g_return_if_fail (plugin_state);
  g_return_if_fail (SIM_IS_PLUGIN_STATE (plugin_state));

  plugin_state->_priv->plugin_id = plugin_id;
}

/*
 *
 *
 *
 *
 */
gint
sim_plugin_state_get_state (SimPluginState   *plugin_state)
{
  g_return_val_if_fail (plugin_state, 0);
  g_return_val_if_fail (SIM_IS_PLUGIN_STATE (plugin_state), 0);

  return plugin_state->_priv->state;
}

/*
 *
 *
 *
 *
 */
void
sim_plugin_state_set_state (SimPluginState   *plugin_state,
			    gint              state)
{
  g_return_if_fail (plugin_state);
  g_return_if_fail (SIM_IS_PLUGIN_STATE (plugin_state));
  g_return_if_fail (state >= 0);

  plugin_state->_priv->state = state;
}

/*
 *
 *
 *
 *
 */
gboolean
sim_plugin_state_get_enabled (SimPluginState   *plugin_state)
{
  g_return_val_if_fail (plugin_state, FALSE);
  g_return_val_if_fail (SIM_IS_PLUGIN_STATE (plugin_state), FALSE);

  return plugin_state->_priv->enabled;
}

/*
 *
 *
 *
 *
 */
void
sim_plugin_state_set_enabled (SimPluginState   *plugin_state,
			      gboolean          enabled)
{
  g_return_if_fail (plugin_state);
  g_return_if_fail (SIM_IS_PLUGIN_STATE (plugin_state));

  plugin_state->_priv->enabled = enabled;
}

// vim: set tabstop=2:
