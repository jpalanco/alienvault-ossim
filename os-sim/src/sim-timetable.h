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

#ifndef __SIM_TIMETABLE_H__
#define __SIM_TIMETABLE_H__ 1

#include <glib.h>
#include <glib-object.h>
#include <gnet.h>

typedef struct _SimTimetable         SimTimetable;
typedef struct _SimTimetableClass    SimTimetableClass;
typedef struct _SimTimetablePrivate  SimTimetablePrivate;

#include "sim-event.h"

G_BEGIN_DECLS

#define SIM_TYPE_TIMETABLE                  (sim_timetable_get_type ())
#define SIM_TIMETABLE(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_TIMETABLE, SimTimetable))
#define SIM_TIMETABLE_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_TIMETABLE, SimTimetableClass))
#define SIM_IS_TIMETABLE(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_TIMETABLE))
#define SIM_IS_TIMETABLE_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_TIMETABLE))
#define SIM_TIMETABLE_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_TIMETABLE, SimTimetableClass))

struct _SimTimetable {
  GObject parent;
  SimTimetablePrivate  *_priv;
};

struct _SimTimetableClass {
  GObjectClass parent_class;
};

GType             sim_timetable_get_type                        (void);
SimTimetable*     sim_timetable_new                             (void);
gboolean 					sim_timetable_check_date(SimTimetable *timetable,time_t time);
gboolean 					sim_timetable_append_timerule(SimTimetable *timetable,gchar *datespec,gchar *timespec);
void							sim_timetable_set_name(SimTimetable *timetable,const gchar *);
void							sim_timetable_dump(SimTimetable *timetable);
gchar *						sim_timetable_get_name(SimTimetable *);
gboolean					sim_timetable_check_timetable(SimTimetable *timetable,struct tm *t, SimEvent *event);

G_END_DECLS

#endif /* __SIM_TIMERULE_H__ */
// vim: set tabstop=2:
