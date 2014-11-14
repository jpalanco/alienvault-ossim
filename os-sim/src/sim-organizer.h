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

#ifndef __SIM_ORGANIZER_H__
#define __SIM_ORGANIZER_H__ 1

#include <glib.h>
#include <glib-object.h>
#include <gnet.h>

typedef struct _SimOrganizer        SimOrganizer;
typedef struct _SimOrganizerClass   SimOrganizerClass;
typedef struct _SimOrganizerPrivate SimOrganizerPrivate;

#include "sim-container.h"
#include "sim-config.h"
#include "sim-event.h"
#include "sim-uuid.h"

G_BEGIN_DECLS

#define SIM_TYPE_ORGANIZER            (sim_organizer_get_type ())
#define SIM_ORGANIZER(obj)            (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_ORGANIZER, SimOrganizer))
#define SIM_ORGANIZER_CLASS(klass)    (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_ORGANIZER, SimOrganizerClass))
#define SIM_IS_ORGANIZER(obj)         (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_ORGANIZER))
#define SIM_IS_ORGANIZER_CLASS(klass) (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_ORGANIZER))
#define SIM_ORGANIZER_GET_CLASS(obj)  (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_ORGANIZER, SimOrganizerClass))

/* max time that the events could be in the agent without send it to the server.
   If this time is exceeded the event won't update the C & A, and won't enter into correlation.
   (it still will qualify, generate alarms..) */
#define MAX_DIFF_TIME 60


struct _SimOrganizer
{
  GObject parent;

  SimOrganizerPrivate *_priv;
};

struct _SimOrganizerClass
{
  GObjectClass parent_class;
};

GType             sim_organizer_get_type                        (void);
SimOrganizer*     sim_organizer_new                             (SimConfig     *config);

void              sim_organizer_run                             (SimOrganizer  *organizer);

void              sim_organizer_correlation_plugin              (SimOrganizer  *organizer,
                                                                 SimEvent      *event);

void              sim_organizer_mac_os_change                   (SimOrganizer  *organizer,
                                                                 SimEvent      *event);
SimPolicy*        sim_organizer_get_policy                      (SimOrganizer  *organizer,
                                                                 SimEvent      *event);

/* Priority Function */
gboolean          sim_organizer_reprioritize                    (SimOrganizer  *organizer,
                                                                 SimEvent      *event,
                                                                 SimPolicy     *policy);

/* Snort Functions */
void              sim_organizer_snort                           (SimOrganizer  *organizer,
                                                                 SimEvent      *event);
guint             sim_organizer_snort_signature_get_id          (SimDatabase   *db_snort,
                                                                 gchar         *name);
void              sim_organizer_snort_extra_data_insert         (SimDatabase   *db_snort,
                                                                 SimEvent      *event);
void              sim_organizer_snort_reputation_data_insert    (SimDatabase   *db_snort,
                                                                 SimEvent      *event);
void              sim_organizer_update_siem_ac_tables           (SimDatabase   *db,
                                                                 SimEvent      *event,
                                                                 gchar         *timestamp);
void              sim_organizer_snort_event_update_acid_event   (SimDatabase   *db_snort,
                                                                 SimEvent      *event);
void              sim_organizer_snort_event_insert              (SimDatabase   *db_snort,
                                                                 SimEvent      *event,
                                                                 guint          sid,
                                                                 guint          sig_id);

/* For statistics */
void              sim_organizer_increase_total_events           (SimOrganizer  *organizer);
guint             sim_organizer_get_total_events                (SimOrganizer  *organizer);
guint             sim_organizer_get_events_in_queue             (void);


void              sim_organizer_send_alarms_to_syslog           (SimEvent *event);

gint              sim_organizer_snort_sensor_get_sid            (SimDatabase   *db_snort,
                                                                 SimUuid       *sensor_id,
                                                                 SimEvent      *event);


G_END_DECLS

#endif /* __SIM_ORGANIZER_H__ */
// vim: set tabstop=2:
