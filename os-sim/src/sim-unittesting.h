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

// This macro has the higher priority! leave it wrapping ALL the content of sim-unittesting.{c,h}

#ifndef __SIM_UNITTESTING_H__
#define __SIM_UNITTESTING_H__ 1

#include <glib.h>
#include <glib-object.h>

G_BEGIN_DECLS

typedef struct _SimUnittest {
    gchar *name;
    gboolean (*func)(void);
    gint expected;
} SimUnittest;


#define SIM_TYPE_UNITTESTING                  (sim_unittesting_get_type ())
#define SIM_UNITTESTING(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_UNITTESTING, SimUnittesting))
#define SIM_UNITTESTING_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_UNITTESTING, SimUnittestingClass))
#define SIM_IS_UNITTESTING(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_UNITTESTING))
#define SIM_IS_UNITTESTING_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_UNITTESTING))
#define SIM_UNITTESTING_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_UNITTESTING, SimUnittestingClass))

typedef struct _SimUnittesting        SimUnittesting;
typedef struct _SimUnittestingClass   SimUnittestingClass;
typedef struct _SimUnittestingPrivate SimUnittestingPrivate;

struct _SimUnittesting {
  GObject parent;

  SimUnittestingPrivate *_priv;
};

struct _SimUnittestingClass {
  GObjectClass parent_class;
};

GType                    sim_unittesting_get_type    (void);
SimUnittesting*          sim_unittesting_new         (void);

void                     sim_unittesting_append(SimUnittesting *, gchar *, gboolean (*func)(void), guint);

gboolean                 sim_unittesting_execute_unittests(SimUnittesting *);

void                     sim_unittesting_set_regex(SimUnittesting *, gchar *);

G_END_DECLS

#endif /* __SIM_UNITTESTING_H__ */

// vim: set tabstop=2:

