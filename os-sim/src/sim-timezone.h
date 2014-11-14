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

#ifndef __SIM_TIMEZONE_H__
#define __SIM_TIMEZONE_H__ 1

#include <time.h>
#include <glib.h>
#include <glib-object.h>

typedef struct _SimTimezone        SimTimezone;
typedef struct _SimTimezoneClass   SimTimezoneClass;
typedef struct _SimTimezonePrivate SimTimezonePrivate;

#define SIM_TYPE_TIMEZONE                  (sim_timezone_get_type ())
#define SIM_TIMEZONE(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_TIMEZONE, SimTimezone))
#define SIM_TIMEZONE_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_TIMEZONE, SimTimezoneClass))
#define SIM_IS_TIMEZONE(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_TIMEZONE))
#define SIM_IS_TIMEZONE_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_TIMEZONE))
#define SIM_TIMEZONE_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_TIMEZONE, SimTimezoneClass))

G_BEGIN_DECLS

struct _SimTimezone {
  GObject parent;
  SimTimezonePrivate *_priv;
};

struct _SimTimezoneClass {
  GObjectClass parent_class;
};

GType               sim_timezone_get_type                (void);
SimTimezone *       sim_timezone_new                     (const gchar * identifier);
gint32              sim_timezone_get_offset              (SimTimezone * tz, time_t now);

G_END_DECLS

#endif
