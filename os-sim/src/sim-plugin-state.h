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

#ifndef __SIM_PLUGIN_STATE_H__
#define __SIM_PLUGIN_STATE_H__ 1

#include <glib.h>
#include <glib-object.h>

#include "sim-plugin.h"

G_BEGIN_DECLS

#define SIM_TYPE_PLUGIN_STATE                  (sim_plugin_state_get_type ())
#define SIM_PLUGIN_STATE(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_PLUGIN_STATE, SimPluginState))
#define SIM_PLUGIN_STATE_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_PLUGIN_STATE, SimPluginStateClass))
#define SIM_IS_PLUGIN_STATE(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_PLUGIN_STATE))
#define SIM_IS_PLUGIN_STATE_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_PLUGIN_STATE))
#define SIM_PLUGIN_STATE_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_PLUGIN_STATE, SimPluginStateClass))

typedef struct _SimPluginState         SimPluginState;
typedef struct _SimPluginStateClass    SimPluginStateClass;
typedef struct _SimPluginStatePrivate  SimPluginStatePrivate;

struct _SimPluginState {
  GObject parent;

  SimPluginStatePrivate  *_priv;
};

struct _SimPluginStateClass {
  GObjectClass parent_class;
};

GType             sim_plugin_state_get_type                        (void);
SimPluginState*   sim_plugin_state_new                             (void);
SimPluginState*   sim_plugin_state_new_from_data                   (SimPlugin        *plugin,
								    gint              plugin_id,
								    gint              state,
								    gboolean          enable);

SimPlugin*        sim_plugin_state_get_plugin                      (SimPluginState   *plugin_state);
void              sim_plugin_state_set_plugin                      (SimPluginState   *plugin_state,
								    SimPlugin        *plugin);
gint              sim_plugin_state_get_state                       (SimPluginState   *plugin_state);
void              sim_plugin_state_set_state                       (SimPluginState   *plugin_state,
								    gint              state);
gboolean          sim_plugin_state_get_enabled                     (SimPluginState   *plugin_state);
void              sim_plugin_state_set_enabled                     (SimPluginState   *plugin_state,
								    gboolean          enabled);
gint							 sim_plugin_state_get_plugin_id (SimPluginState   *plugin_state);
void							 sim_plugin_state_set_plugin_id (SimPluginState   *plugin_state,
				gint              plugin_id);


G_END_DECLS

#endif /* __SIM_PLUGIN_STATE_H__ */
// vim: set tabstop=2:
