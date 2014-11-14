/*
  License:

  Copyright (c) 2012 AlienVault
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

#include "sim-inet.h"
#ifdef USE_UNITTESTS
#include "sim-unittesting.h"
#endif

#ifndef __SIM_GEOIP_H__
#define __SIM_GEOIP_H__ 1

G_BEGIN_DECLS

void            sim_geoip_new                 (void);
const gchar *   sim_geoip_lookup              (SimInet *inet);
void            sim_geoip_free                (void);

#ifdef USE_UNITTESTS
void            sim_geoip_register_tests      (SimUnittesting *engine);
#endif

G_END_DECLS

#endif /* __SIM_GEOIP_H__ */
