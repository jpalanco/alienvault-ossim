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

#ifndef __SIM_INET_H__
#define __SIM_INET_H__

#include <glib.h>
#include <glib-object.h>
#include <gnet.h>
#include <stdint.h>

#include "sim-enums.h"
#include "sim-radix.h"
#include "sim-net.h"
#include "sim-unittesting.h"

G_BEGIN_DECLS

#define SIM_TYPE_INET                  (sim_inet_get_type ())
#define SIM_INET(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_INET, SimInet))
#define SIM_INET_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_INET, SimInetClass))
#define SIM_IS_INET(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_INET))
#define SIM_IS_INET_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_INET))
#define SIM_INET_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_INET, SimInetClass))

#define SIM_INET_HOST 128 // Mask for Host

//A SimInet object defines a single network object. It can be a host or a network.

typedef struct _SimInet        SimInet;
typedef struct _SimInetClass   SimInetClass;
typedef struct _SimInetPrivate SimInetPrivate;

struct _SimInet {
  GObject parent;

  SimInetPrivate *priv;
};

struct _SimInetClass {
  GObjectClass parent_class;
};

GType             sim_inet_get_type                        (void);
void              sim_inet_register_type                   (void);

SimInet *         sim_inet_new                             (GInetAddr        *ia,
                                                            guint             mask);
SimInet *         sim_inet_new_from_string                 (const gchar      *hostname_ip);
SimInet *         sim_inet_new_from_db_binary              (const guchar     *db_str, glong size);
SimInet *         sim_inet_new_none                        (void);

gint              sim_inet_get_mask                        (SimInet          *inet);
void              sim_inet_set_mask                        (SimInet          *inet,
                                                            guint             mask);

SimInet*          sim_inet_clone                           (SimInet          *inet);
gboolean          sim_inet_noport_equal                    (SimInet          *inet1,
                                                            SimInet          *inet2);

GInetAddr *       sim_inet_get_address                     (SimInet          *inet1);

gboolean          sim_inet_is_none                         (SimInet          *inet);
gboolean          sim_inet_is_loopback                     (SimInet          *inet);
gboolean          sim_inet_is_reserved                     (SimInet          *inet);
gboolean          sim_inet_is_host                         (SimInet          *inet);
gboolean          sim_inet_is_in_homenet                   (SimInet          *inet);
void              sim_inet_set_is_in_homenet               (SimInet          *inet,
                                                            gboolean          found);
gboolean          sim_inet_is_homenet_checked              (SimInet          *inet);

const gchar *     sim_inet_get_db_string                   (SimInet          *inet);
gchar *           sim_inet_get_canonical_name              (SimInet          *inet);
gchar *           sim_inet_get_cidr                        (SimInet          *inet);

void              sim_inet_set_parent_net                  (SimInet          *inet,
                                                            SimNet           *net);
SimNet *          sim_inet_get_parent_net                  (SimInet          *inet);

void              sim_inet_debug_print                     (SimInet          *inet);
uint8_t *         sim_inet_get_in_addr                     (SimInet          *inet);
void              sim_inet_set_port                        (SimInet          *inet,
                                                            gint              port);
gint              sim_inet_get_port                        (SimInet          *inet);
SimRadixKey *     sim_inet_get_radix_key                   (SimInet          *inet);


gboolean          sim_inet_is_ipv4                         (SimInet          *inet);
gboolean          sim_inet_is_ipv6                         (SimInet          *inet);

guint             sim_inet_hash                            (gconstpointer     a);
gboolean          sim_inet_equal                           (gconstpointer     a,
                                                            gconstpointer     b);

#ifdef USE_UNITTESTS
void              sim_inet_register_tests                  (SimUnittesting   *engine);
#endif

G_END_DECLS

#endif /* __SIM_INET_H__ */
// vim: set tabstop=2:
