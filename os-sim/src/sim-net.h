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

#ifndef __SIM_NET_H__
#define __SIM_NET_H__ 1

#include <glib.h>
#include <glib-object.h>
#include <gnet.h>
#include <libgda/libgda.h>


G_BEGIN_DECLS

#define SIM_TYPE_NET                  (sim_net_get_type ())
#define SIM_NET(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_NET, SimNet))
#define SIM_NET_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_NET, SimNetClass))
#define SIM_IS_NET(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_NET))
#define SIM_IS_NET_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_NET))
#define SIM_NET_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_NET, SimNetClass))

/* 
 * SimNet is one of the strings in the DB, table net, column ips (Policy -> networks)
 * It could contain multiple networks, thanks to the SimInet Objects that it stores in _priv->inets
 */
typedef struct _SimNet          SimNet;
typedef struct _SimNetClass     SimNetClass;
typedef struct _SimNetPrivate   SimNetPrivate;

#include "sim-uuid.h"

struct _SimNet {
  GObject parent;

  SimNetPrivate *_priv;
};

struct _SimNetClass {
  GObjectClass parent_class;
};

GType             sim_net_get_type                        (void);
SimNet*           sim_net_new                             (const gchar      *name,
                                                           const gchar      *ips,
                                                           gint              asset);
SimNet*           sim_net_new_from_dm                     (GdaDataModel     *dm,
                                                           gint              row);
SimNet *          sim_net_new_void                        (SimUuid         * id);

SimUuid *         sim_net_get_id                          (SimNet          * net);

gchar*            sim_net_get_name                        (SimNet           *net);
void              sim_net_set_name                        (SimNet           *net,
                                                           const gchar      *name);

gint              sim_net_get_asset                       (SimNet           *net);
void              sim_net_set_asset                       (SimNet           *net,
                                                           gint              asset);
gboolean          sim_net_get_external                    (SimNet          * net);
void              sim_net_set_external                    (SimNet          * net,
                                                           gboolean          external);

gdouble           sim_net_get_a                           (SimNet          * net);
void              sim_net_plus_a                          (SimNet          * net,
                                                           gdouble           a);
gdouble           sim_net_get_c                           (SimNet          * net);
void              sim_net_plus_c                          (SimNet          * net,
                                                           gdouble           c);
gboolean          sim_net_level_is_zero                   (SimNet          * net);

void              sim_net_level_set_recovery              (SimNet          * net,
                                                           gint              recovery);

gchar *           sim_net_level_get_update_clause         (SimNet          * net);
gchar *           sim_net_level_get_delete_clause         (SimNet          * net);


#ifndef __SIM_INET_H__
#include "sim-inet.h"

void              sim_net_append_inet                     (SimNet           *net,
                                                           SimInet          *inet);
void              sim_net_remove_inet                     (SimNet           *net,
                                                           SimInet          *inet);
#endif

GList*            sim_net_get_inets                       (SimNet           *net);
void              sim_net_set_inets                       (SimNet           *net,
                                                           GList            *list);
void              sim_net_free_inets                      (SimNet           *net);


gchar*						sim_net_get_ips													(SimNet						*net);
void							sim_net_set_ips													(SimNet						*net,
                                                           gchar            *ips); //string with multiple IPs
void							sim_net_debug_print											(SimNet						*net);

G_END_DECLS

#endif /* __SIM_NET_H__ */
// vim: set tabstop=2:
