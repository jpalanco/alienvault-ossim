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

#ifndef _SIM_GROUP_ALARM_H 
#define _SIM_GROUP_ALARM_H

#ifndef _XOPEN_SOURCE
#define _XOPEN_SOURCE
#endif

#include <glib-object.h>
#include <glib.h>

#define SIM_TYPE_GROUP_ALARM 										(sim_group_alarm_get_type ())
#define SIM_GROUP_ALARM(obj) 		 								(G_TYPE_CHECK_INSTANCE_CAST ((obj), SIM_TYPE_GROUP_ALARM,SimGroupAlarm))
#define SIM_IS_GROUP_ALARM(obj)    						(G_TYPE_CHECK_INSTANCE_TYPE ((obj),SIM_TYPE_GROUP_ALARM))
#define SIM_GROUP_ALARM_CLASS(kclass) 					(G_TYPE_CHECK_CLASS_CAST ((kclass),SIM_TYPE_GROUP_ALARM,SimGroupAlarmClass))
#define SIM_GROUP_ALARM_IS_GROUP_ALARM_CLASS(kclass)		(G_TYPE_CHECK_CLASS_TYPE ((kclass),SIM_TYPE_GROUP_ALARM))
#define SIM_GROUP_ALARM_GET_CLASS(obj)					(G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_GROUP_ALARM,SimGroupAlarmClass))


G_BEGIN_DECLS

typedef struct _SimGroupAlarm SimGroupAlarm;
typedef struct _SimGroupAlarmClass SimGroupAlarmClass;
typedef struct _SimGroupAlarmPrivate	SimGroupAlarmPrivate;

struct _SimGroupAlarm{
	GObject parent;
	SimGroupAlarmPrivate *_priv;
};

struct _SimGroupAlarmClass{
	GObjectClass	parent_class;
};
/* Prototypes */
GType sim_group_alarm_get_type (void);

void sim_group_alarm_inc_count (SimGroupAlarm *);
unsigned int sim_group_alarm_get_count (SimGroupAlarm *);
gboolean sim_group_alarm_is_timeout (SimGroupAlarm *);
SimGroupAlarm  *sim_group_alarm_new (unsigned int timeout, const gchar *key);
time_t sim_group_alarm_get_tstart (SimGroupAlarm *);
gchar * sim_group_alarm_get_id (SimGroupAlarm *);

G_END_DECLS
#endif
