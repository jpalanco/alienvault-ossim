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

#ifndef __SIM_SERVER_H__
#define __SIM_SERVER_H__ 1

#include <glib.h>

G_BEGIN_DECLS

#define SIM_TYPE_SERVER                  (sim_server_get_type ())
#define SIM_SERVER(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_SERVER, SimServer))
#define SIM_SERVER_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_SERVER, SimServerClass))
#define SIM_IS_SERVER(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_SERVER))
#define SIM_IS_SERVER_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_SERVER))
#define SIM_SERVER_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_SERVER, SimServerClass))

typedef struct _SimServer        SimServer;
typedef struct _SimServerClass   SimServerClass;
typedef struct _SimServerPrivate SimServerPrivate;

#include "sim-session.h"
#include "sim-rule.h"
#include "sim-config.h"
#include "sim-database.h"
#include "sim-reputation.h"
#include "sim-command.h"
#include "sim-enums.h"
#include "sim-inet.h"
#include "sim-role.h"
#include "sim-uuid.h"

struct _SimServer {
  GObject parent;

  SimServerPrivate *_priv;
};

struct _SimServerClass {
  GObjectClass parent_class;
};

GType             sim_server_get_type                      (void);
SimServer*        sim_server_new                           (SimConfig       *config);

SimServer*				sim_server_new_from_dm									(GdaDataModel  *dm,
																		                        gint row);
void              sim_server_listen_run                    (SimServer       *server,
                                                            gboolean         is_server);
void              sim_server_master_run                    (SimServer       *server);

gboolean          sim_server_is_local                      (SimServer      * server);

void              sim_server_append_session                (SimServer       *server,
																												    SimSession      *session);
gint              sim_server_remove_session                (SimServer       *server,
																												    SimSession      *session);
GList*            sim_server_get_sessions                  (SimServer       *server);

void              sim_server_push_session_command          (SimServer       *server,
																												    SimSessionType   type,
																												    SimCommand      *command);
void              sim_server_push_session_plugin_command   (SimServer       *server,
																												    SimSessionType   session_type,
																												    gint             plugin_id,
																												    SimRule					*rule);
gpointer					sim_server_thread_monitor_requests				(gpointer data);
	

void							sim_server_debug_print										(SimServer		*server);
void              sim_server_debug_print_sessions           (SimServer    *server); //debug function
SimReputation *   sim_server_get_reputation                 (SimServer   *server);
SimInet *         sim_server_get_ip                         (SimServer   *server);
gchar *           sim_server_get_name                       (SimServer   *server);
SimUuid *         sim_server_get_id                         (SimServer   *server);
gint							sim_server_get_port												(SimServer   *server);
void							sim_server_set_port												(SimServer   *server,
                                                             gint         port);

GHashTable*				sim_server_get_individual_sensors 				(SimServer   *server);

SimConfig*				sim_server_get_config											(SimServer   *server);
void							sim_server_load_role											(SimServer *server); //loads from ossim.config in DB the role of local server
void              sim_server_reload_role                    (SimServer *server);
void              sim_server_load_rservers                  (SimServer *server);

void							sim_server_set_role												(SimServer *server,
																														 SimRole   *role);

SimRole *         sim_server_get_role                       (SimServer *server);

guint             sim_server_get_session_count              (SimServer      *server);
guint             sim_server_get_session_count_active       (SimServer      *server);
void              sim_server_reload                         (SimServer      *server,
                                                             SimContext     *context);
void              sim_server_set_data_role                  (SimServer      *server,
                                                             SimCommand     *command);

SimSession*				sim_server_get_sensor_by_ia_port 			  	(SimServer      *server,
                                                             SimInet        *ia,
                                                             gint            port);

void              sim_server_set_name                       (SimServer      *server,
                                                             gchar          *name);
void              sim_server_set_ip                         (SimServer      *server,
                                                             SimInet        *ip);
gboolean          sim_server_get_sensor_uuids_unique            (SimServer *server,
                                                            GPtrArray **pparray);
/* CURRENTLY UNUSED
SimSession*       sim_server_get_session_by_ia             (SimServer       *server,
																														SimSessionType   session_type,
																														GInetAddr       *ia);
SimSession*       sim_server_get_session_by_sensor         (SimServer   *server,
                                                            SimSensor   *sensor);
*/

G_END_DECLS

#endif /* __SIM_SERVER_H__ */
// vim: set tabstop=2:
