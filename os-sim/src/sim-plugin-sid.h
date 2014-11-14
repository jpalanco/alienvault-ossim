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

#ifndef __SIM_PLUGIN_SID_H__
#define __SIM_PLUGIN_SID_H__ 1

#include <glib.h>
#include <glib-object.h>
#include <libgda/libgda.h>

G_BEGIN_DECLS

#define SIM_TYPE_PLUGIN_SID                  (sim_plugin_sid_get_type ())
#define SIM_PLUGIN_SID(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_PLUGIN_SID, SimPluginSid))
#define SIM_PLUGIN_SID_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_PLUGIN_SID, SimPluginSidClass))
#define SIM_IS_PLUGIN_SID(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_PLUGIN_SID))
#define SIM_IS_PLUGIN_SID_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_PLUGIN_SID))
#define SIM_PLUGIN_SID_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_PLUGIN_SID, SimPluginSidClass))

typedef struct _SimPluginSid         SimPluginSid;
typedef struct _SimPluginSidClass    SimPluginSidClass;
typedef struct _SimPluginSidPrivate  SimPluginSidPrivate;

#include "sim-enums.h"
#include "sim-uuid.h"

struct _SimPluginSid {
  GObject parent;

  SimPluginSidPrivate  *_priv;
};

struct _SimPluginSidClass {
  GObjectClass parent_class;
};

GType             sim_plugin_sid_get_type                        (void);
SimPluginSid*     sim_plugin_sid_new                             (void);
SimPluginSid*     sim_plugin_sid_new_from_data                   (gint           plugin_id,
								  gint           sid,
								  gint           reliability,
								  gint           priority,
								  const gchar   *name);
SimPluginSid*     sim_plugin_sid_new_from_dm                     (GdaDataModel  *dm,
                                                                  gint           row);
gint              sim_plugin_sid_get_plugin_id                   (SimPluginSid  *plugin_sid);
void              sim_plugin_sid_set_plugin_id                   (SimPluginSid  *plugin_sid,
								  gint           plugin_id);
gint              sim_plugin_sid_get_sid                         (SimPluginSid  *plugin_sid);
void              sim_plugin_sid_set_sid                         (SimPluginSid  *plugin_sid,
								  gint           sid);
gint              sim_plugin_sid_get_reliability                 (SimPluginSid  *plugin_sid);
void              sim_plugin_sid_set_reliability                 (SimPluginSid  *plugin_sid,
								  gint           reliability);
gint              sim_plugin_sid_get_priority                    (SimPluginSid  *plugin_sid);
void              sim_plugin_sid_set_priority                    (SimPluginSid  *plugin_sid,
								  gint           priority);
gchar*            sim_plugin_sid_get_name                        (SimPluginSid  *plugin_sid);
void              sim_plugin_sid_set_name                        (SimPluginSid  *plugin_sid,
								  gchar         *name);
gint              sim_plugin_sid_get_category                    (SimPluginSid *plugin_sid);
gint              sim_plugin_sid_get_subcategory                 (SimPluginSid *plugin_sid);
void							sim_plugin_sid_debug_print				 						 (SimPluginSid  *plugin_sid); //debug function

gchar*            sim_plugin_sid_get_insert_clause               (SimPluginSid  *plugin_sid,
                                                                  SimUuid       *plugin_ctx);
guint             sim_plugin_sid_get_cantor_key                  (SimPluginSid  *plugin_sid);

G_END_DECLS

#endif /* __SIM_PLUGIN_SID_H__ */
// vim: set tabstop=2:
