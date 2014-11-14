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

#ifndef __SIM_ROLE_H__
#define __SIM_ROLE_H__

#include <glib.h>
#include <glib-object.h>

G_BEGIN_DECLS

typedef struct _SimRole        SimRole;
typedef struct _SimRoleClass   SimRoleClass;
typedef struct _SimRolePrivate SimRolePrivate;

#include "sim-mini-object.h"
#include "sim-unittesting.h"
#include "sim-database.h"

extern GType _sim_role_type;

#define SIM_TYPE_ROLE      (_sim_role_type)
#define SIM_IS_ROLE(obj)   (SIM_IS_MINI_OBJECT_TYPE(obj, SIM_TYPE_ROLE))
#define SIM_ROLE_CAST(obj) ((SimRole *)(obj))
#define SIM_ROLE(obj)      (SIM_ROLE_CAST(obj))

//SimPolicy is each one of the "lines" in the policy. It has one or more sources, one or more destinations, a time range, and so on.

struct _SimRole      //this hasn't got any data from sensor associated.
{
  SimMiniObject mini_object;

  SimRolePrivate *priv;
};

struct _SimRoleClass
{
  GObjectClass parent_class;
};

void                _priv_sim_role_initialize           (void);
GType               sim_role_get_type                   (void);
SimRole *           sim_role_ref                        (SimRole     *role);
void                sim_role_unref                      (SimRole     *role);

SimRole *           sim_role_copy                       (SimRole     *role);
SimRole *           sim_role_new                        (void);
SimRole *           sim_role_new_full                   (gboolean     correlate,
                                                         gboolean     cross_correlate,
                                                         gboolean     reputation,
                                                         gboolean     store,
                                                         gboolean     qualify,
                                                         gboolean     sim,
                                                         gboolean     sem,
                                                         gboolean     sign,
                                                         gboolean     forward_event,
                                                         gboolean     forward_alarm,
                                                         gboolean     alarms_to_syslog);
SimRole *           sim_role_new_from_dm                (GdaDataModel *dm,
                                                         gint          row);
void                sim_role_print                      (SimRole     *role);
gchar *             sim_role_get_string                 (SimRole     *role);
gboolean            sim_role_correlate                  (SimRole     *role);
void                sim_role_set_correlate              (SimRole     *role,
                                                         gboolean     new_value);
gboolean            sim_role_cross_correlate            (SimRole     *role);
void                sim_role_set_cross_correlate        (SimRole     *role,
                                                         gboolean     new_value);
gboolean            sim_role_reputation                 (SimRole     *role);
void                sim_role_set_reputation             (SimRole     *role,
                                                         gboolean     new_value);
gboolean            sim_role_store                      (SimRole     *role);
void                sim_role_set_store                  (SimRole     *role,
                                                         gboolean     new_value);
gboolean            sim_role_qualify                    (SimRole     *role);
void                sim_role_set_qualify                (SimRole     *role,
                                                         gboolean     new_value);
gboolean            sim_role_sim                        (SimRole     *role);
void                sim_role_set_sim                    (SimRole     *role,
                                                         gboolean     new_value);
gboolean            sim_role_sem                        (SimRole     *role);
void                sim_role_set_sem                    (SimRole     *role,
                                                         gboolean     new_value);
gboolean            sim_role_sign                       (SimRole     *role);
void                sim_role_set_sign                   (SimRole     *role,
                                                         gboolean     new_value);
gboolean            sim_role_forward_event              (SimRole     *role);
void                sim_role_set_forward_event          (SimRole     *role,
                                                         gboolean     new_value);
gboolean            sim_role_forward_alarm              (SimRole     *role);
void                sim_role_set_forward_alarm          (SimRole     *role,
                                                         gboolean     new_value);
gboolean            sim_role_alarms_to_syslog           (SimRole     *role);
void                sim_role_set_alarms_to_syslog       (SimRole     *role,
                                                         gboolean     new_value);
gint                sim_role_logger_if_priority         (SimRole     *role);
void                sim_role_set_logger_if_priority     (SimRole     *role,
                                                         gboolean     new_value);
GHashTable *        sim_role_get_rservers               (SimRole      *role);
void                sim_role_set_rservers               (SimRole      *role,
                                                         GHashTable   *rservers);

#ifdef USE_UNITTESTS
void                sim_role_register_tests             (SimUnittesting *engine);
#endif

G_END_DECLS

#endif /* __SIM_ROLE_H__ */

// vim: set tabstop=2:

