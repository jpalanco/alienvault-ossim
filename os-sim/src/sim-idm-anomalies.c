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


#include "config.h"

#include "sim-idm-anomalies.h"

#include <glib.h>

#include "sim-idm-entry.h"
#include "sim-event.h"
#include "sim-sensor.h"
#include "sim-session.h"

void
sim_idm_anomalies_send (SimIdmAnomaliesType type, gchar *old_value, gchar *new_value, SimIdmEntry *entry, SimUuid *context_id, SimSensor *sensor)
{
  SimEvent *event;

  event = sim_event_new ();
  event->type = SIM_EVENT_TYPE_DETECTOR;
  event->src_ia = g_object_ref (sim_idm_entry_get_ip (entry));
  event->dst_ia = g_object_ref (sim_idm_entry_get_ip (entry));
  event->id = sim_uuid_new ();
  event->time_str = g_new0 (gchar, TIMEBUF_SIZE);
  event->time = time (NULL);
  strftime(event->time_str, TIMEBUF_SIZE, "%Y-%m-%d %H:%M:%S", gmtime(&event->time));
  event->tzone = sim_sensor_get_tzone (sensor);
  event->sensor = g_object_ref (sim_sensor_get_ia (sensor));
  event->sensor_id = g_object_ref (sim_sensor_get_id (sensor));
  event->protocol = SIM_PROTOCOL_TYPE_TCP;
  event->plugin_id = SIM_IDM_ANOMALIES_PLUGIN_ID;
  event->plugin_sid = type;
  event->userdata1 = g_strdup (old_value);
  event->userdata2 = g_strdup (new_value);

  sim_event_set_context_and_engine (event, context_id);

  // Enriching the anomalies event will enrich it with the old value
  sim_session_prepare_and_insert (NULL, event);
}
