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

#ifndef __SIM_IDM_H__
#define __SIM_IDM_H__

#include <mqueue.h>
#include <glib.h>

#include "sim-command.h"
#include "sim-container.h"
#include "sim-sensor.h"
#include "sim-inet.h"
#include "sim-idm-entry.h"

void          sim_idm_context_init (void);
void          sim_idm_put (SimSensor *sensor, SimCommand *command);
SimIdmEntry * sim_idm_get (SimUuid *context_id, SimInet *ip);
void          sim_idm_process (SimSensor *sensor, SimCommand *command);
void          sim_idm_reload_context (void);

#endif
