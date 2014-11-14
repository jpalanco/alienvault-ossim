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

#ifndef __SIM_PLUGIN_H__
#define __SIM_PLUGIN_H__ 1

#include <glib.h>
#include <glib-object.h>
#include <libgda/libgda.h>

#include "sim-enums.h"

#ifdef __cplusplus
extern "C" {
#endif /* __cplusplus */

#define SIM_TYPE_PLUGIN                  (sim_plugin_get_type ())
#define SIM_PLUGIN(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_PLUGIN, SimPlugin))
#define SIM_PLUGIN_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_PLUGIN, SimPluginClass))
#define SIM_IS_PLUGIN(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_PLUGIN))
#define SIM_IS_PLUGIN_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_PLUGIN))
#define SIM_PLUGIN_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_PLUGIN, SimPluginClass))

G_BEGIN_DECLS

typedef struct _SimPlugin         SimPlugin;
typedef struct _SimPluginClass    SimPluginClass;
typedef struct _SimPluginPrivate  SimPluginPrivate;

struct _SimPlugin {
  GObject parent;

  SimPluginType      type;

  SimPluginPrivate  *_priv;
};

struct _SimPluginClass {
  GObjectClass parent_class;
};

GType               sim_plugin_get_type                        (void);
SimPlugin*          sim_plugin_new                             (void);
SimPlugin*          sim_plugin_new_from_dm                     (GdaDataModel  *dm,
                                                                gint           row);
SimPlugin*          sim_plugin_clone                           (SimPlugin     *plugin);

gint                sim_plugin_get_id                          (SimPlugin     *plugin);
void                sim_plugin_set_id                          (SimPlugin     *plugin,
																																gint           id);
gchar*              sim_plugin_get_name                        (SimPlugin     *plugin);
void                sim_plugin_set_name                        (SimPlugin     *plugin,
																																gchar         *name);
gchar*              sim_plugin_get_description                 (SimPlugin     *plugin);
void                sim_plugin_set_description                 (SimPlugin     *plugin,
																																gchar         *description);
gint                sim_plugin_get_product                     (SimPlugin *plugin);
void								sim_plugin_set_sim_type											(SimPlugin      *plugin,
																				                         SimPluginType  type);
SimPluginType				sim_plugin_get_sim_type											(SimPlugin      *plugin);
void								 sim_plugin_debug_print (SimPlugin	*plugin);	
G_END_DECLS

#ifdef __cplusplus
}
#endif /* __cplusplus */

#endif /* __SIM_PLUGIN_H__ */
// vim: set tabstop=2:
