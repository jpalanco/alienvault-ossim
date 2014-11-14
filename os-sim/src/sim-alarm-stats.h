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

#ifndef __SIM_ALARM_STATS_H__
#define __SIM_ALARM_STATS_H__ 1

#include <glib.h>

#include "sim-mini-object.h"
#include "sim-event.h"
#ifdef USE_UNITTESTS
#include "sim-unittesting.h"
#endif

G_BEGIN_DECLS

#define SIM_TYPE_ALARM_STATS      (_sim_event_type)
#define SIM_IS_ALARM_STATS(obj)   (SIM_IS_MINI_OBJECT_TYPE(obj, SIM_TYPE_ALARM_STATS))
#define SIM_ALARM_STATS_CAST(obj) ((SimEvent *)(obj))
#define SIM_ALARM_STATS(obj)            (SIM_ALARM_STATS_CAST(obj))

typedef struct _SimAlarmStats         SimAlarmStats;
typedef struct _SimAlarmStatsClass    SimAlarmStatsClass;
typedef struct _SimAlarmStatsPrivate  SimAlarmStatsPrivate;

struct _SimAlarmStats
{
  SimMiniObject mini_object;

  SimAlarmStatsPrivate *_priv;
};

void            _priv_sim_alarm_stats_initialize    (void);
GType           sim_alarm_stats_get_type            (void);
SimAlarmStats * sim_alarm_stats_ref                 (SimAlarmStats *alarm_stats);
void            sim_alarm_stats_unref               (SimAlarmStats *alarm_stats);

SimAlarmStats * sim_alarm_stats_new                 (void);
SimAlarmStats * sim_alarm_stats_copy                (SimAlarmStats *alarm_stats);
void            sim_alarm_stats_update              (SimAlarmStats *alarm_stats, SimEvent *event);
gchar *         sim_alarm_stats_recalculate         (GList *event_list);
gchar *         sim_alarm_stats_to_json             (SimAlarmStats *alarm_stats);

#ifdef USE_UNITTESTS
void            sim_alarm_stats_register_tests      (SimUnittesting *engine);
#endif

G_END_DECLS

#endif /* __SIM_ALARM_STATS_H__ */
