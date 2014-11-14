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

#ifndef __SIM_SCHEDULER_H__
#define __SIM_SCHEDULER_H__ 1

#include <glib.h>
#include <glib-object.h>

G_BEGIN_DECLS

typedef struct _SimScheduler        SimScheduler;
typedef struct _SimSchedulerClass   SimSchedulerClass;
typedef struct _SimSchedulerPrivate SimSchedulerPrivate;

#include "sim-container.h"
#include "sim-database.h"
#include "sim-config.h"

#define SIM_TYPE_SCHEDULER                  (sim_scheduler_get_type ())
#define SIM_SCHEDULER(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_SCHEDULER, SimScheduler))
#define SIM_SCHEDULER_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_SCHEDULER, SimSchedulerClass))
#define SIM_IS_SCHEDULER(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_SCHEDULER))
#define SIM_IS_SCHEDULER_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_SCHEDULER))
#define SIM_SCHEDULER_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_SCHEDULER, SimSchedulerClass))

struct _SimScheduler
{
  GObject parent;

  SimSchedulerPrivate *_priv;
};

struct _SimSchedulerClass
{
  GObjectClass parent_class;
};

GdaConnection*    sim_database_get_conn                         (SimDatabase  *database);

GType             sim_scheduler_get_type                        (void);
SimScheduler*     sim_scheduler_new                             (SimConfig     *config);

void              sim_scheduler_run                             (SimScheduler  *scheduler);

G_END_DECLS

#endif /* __SIM_SCHEDULER_H__ */
// vim: set tabstop=2:
