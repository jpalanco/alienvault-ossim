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

#ifndef _SIM_NETWORK_H_
#define _SIM_NETWORK_H_

#include <glib.h>

typedef struct _SimNetwork        SimNetwork;
typedef struct _SimNetworkClass   SimNetworkClass;
typedef struct _SimNetworkPrivate SimNetworkPrivate;

#include "sim-object.h"
#include "sim-inet.h"

G_BEGIN_DECLS

#define SIM_TYPE_NETWORK            (sim_network_get_type ())
#define SIM_NETWORK(obj)            (G_TYPE_CHECK_INSTANCE_CAST ((obj), SIM_TYPE_NETWORK, SimNetwork))
#define SIM_IS_NETWORK(obj)         (G_TYPE_CHECK_INSTANCE_TYPE ((obj), SIM_TYPE_NETWORK))
#define SIM_NETWORK_CLASS(klass)    (G_TYPE_CHECK_CLASS_CAST ((klass), SIM_TYPE_NETWORK, SimNetworkClass))
#define SIM_IS_NETWORK_CLASS(klass) (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_NETWORK))
#define SIM_NETWORK_GET_CLASS(obj)  (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_NETWORK, SimNetworkClass))

#define  NO_MATCH     0
#define  EXACT_MATCH  128

struct _SimNetwork
{
  GObject parent;
  SimNetworkPrivate *priv;
};

struct _SimNetworkClass
{
  GObjectClass parent_class;
};

/*
 * Prototypes
 */
GType           sim_network_get_type                      (void);
void            sim_network_register_type                 (void);

SimNetwork     *sim_network_new                           (void);
SimNetwork     *sim_network_clone                         (SimNetwork       *network);
void            sim_network_add_inet                      (SimNetwork       *network,
                                                           SimInet          *inet);
gint            sim_network_match_inet                    (SimNetwork       *network,
                                                           SimInet          *inet);
gboolean        sim_network_has_inet                      (SimNetwork       *network,
                                                           SimInet          *inet);
SimInet        *sim_network_search_inet                   (SimNetwork       *network,
                                                           SimInet          *inet);
gboolean        sim_network_has_exact_inet                (SimNetwork       *network,
                                                           SimInet          *inet);
gchar          *sim_network_to_string                     (SimNetwork       *network);
gboolean        sim_network_is_empty                      (SimNetwork       *network);
void            sim_network_print                         (SimNetwork       *network);

#ifdef USE_UNITTESTS
void            sim_network_register_tests                (SimUnittesting *engine);
#endif

G_END_DECLS

#endif /* _SIM_NETWORK_H_ */
