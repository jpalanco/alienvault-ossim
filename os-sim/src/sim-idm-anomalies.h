/*
  License:

  Copyright (c) 2012-2013 AlienVault
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

#ifndef __SIM_IDM_ANOMALIES_H__
#define __SIM_IDM_ANOMALIES_H__

#include "sim-event.h"
#include "sim-sensor.h"
#include "sim-idm-entry.h"

#define SIM_IDM_ANOMALIES_PLUGIN_ID 5004

typedef enum {
  SIM_IDM_ANOMALIES_SERVICE = 1,
  SIM_IDM_ANOMALIES_OS,
  SIM_IDM_ANOMALIES_IP,
  SIM_IDM_ANOMALIES_HOSTNAME,
  SIM_IDM_ANOMALIES_STATE,
  SIM_IDM_ANOMALIES_CPU,
  SIM_IDM_ANOMALIES_MEMORY,
  SIM_IDM_ANOMALIES_VIDEO
} SimIdmAnomaliesType; // Used for plugin sid

void sim_idm_anomalies_send (SimIdmAnomaliesType type, gchar *old_value, gchar *new_value, SimIdmEntry *entry, SimUuid *context_id, SimSensor *sensor);

#endif
