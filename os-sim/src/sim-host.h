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

#ifndef __SIM_HOST_H__
#define __SIM_HOST_H__ 1

#include <glib.h>
#include <glib-object.h>
#include <libgda/libgda.h>

G_BEGIN_DECLS

typedef struct _SimHost        SimHost;
typedef struct _SimHostClass   SimHostClass;
typedef struct _SimHostPrivate SimHostPrivate;

#include "sim-object.h"
#include "sim-inet.h"
#include "sim-database.h"
#include "sim-uuid.h"

#define SIM_TYPE_HOST                  (sim_host_get_type ())
#define SIM_HOST(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_HOST, SimHost))
#define SIM_HOST_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_HOST, SimHostClass))
#define SIM_IS_HOST(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_HOST))
#define SIM_IS_HOST_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_HOST))
#define SIM_HOST_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_HOST, SimHostClass))

struct _SimHost {
  GObject parent;

  SimHostPrivate *_priv;
};

struct _SimHostClass {
  GObjectClass parent_class;
};

GType             sim_host_get_type                        (void);
void              sim_host_register_type                   (void);

SimHost *         sim_host_new                             (SimInet          *inet,
                                                            SimUuid          *id,
                                                            const gchar      *name,
                                                            gint              asset,
                                                            gdouble           c,
                                                            gdouble           a);
SimHost *         sim_host_new_from_dm                     (GdaDataModel     *dm,
                                                            gint row);
SimHost *         sim_host_clone                           (SimHost          *host);

SimUuid *         sim_host_get_id                          (SimHost          *host);

GPtrArray *       sim_host_get_inets                       (SimHost          *host);
void              sim_host_add_inet                        (SimHost          *host,
                                                            SimInet          *inet);

gchar*            sim_host_get_name                        (SimHost          *host);
void              sim_host_set_name                        (SimHost          *host,
                                                            const gchar      *name);

gint              sim_host_get_asset                       (SimHost          *host);
void              sim_host_set_asset                       (SimHost          *host,
                                                            gint              asset);

gboolean          sim_host_get_external                    (SimHost         * host);

void              sim_host_set_external                    (SimHost         * host,
                                                            gboolean          external);


void							sim_host_debug_print											(SimHost					*host);

gdouble						sim_host_get_a														(SimHost					*host);
gdouble						sim_host_get_c														(SimHost					*host);
void							sim_host_set_a														(SimHost					*host, gdouble a);
void							sim_host_set_c														(SimHost					*host, gdouble c);
void 							sim_host_update_c													(SimHost *self,gdouble value);
void 							sim_host_update_a													(SimHost *self,gdouble value);
gboolean 					sim_host_load_qualification 							(SimHost *self, SimDatabase *data);
gboolean					sim_host_level_set_recovery               (SimHost  *host, gint recovery);
gboolean          sim_host_level_is_zero                    (SimHost *host);
gchar*						sim_host_level_get_insert_clause 				  (SimHost *host);
gchar*						sim_host_level_get_update_clause 				  (SimHost *host);
gchar*						sim_host_level_get_delete_clause 				  (SimHost *host);

gboolean          sim_host_is_loaded_from_db                (SimHost *host);

G_END_DECLS

#endif /* __SIM_HOST_H__ */
// vim: set tabstop=2:
