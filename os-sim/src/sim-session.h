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

#ifndef __SIM_SESSION_H__
#define __SIM_SESSION_H__ 1

#include <glib.h>
#include <glib-object.h>
#include <gnet.h>

G_BEGIN_DECLS

#define SIM_TYPE_SESSION                  (sim_session_get_type ())
#define SIM_SESSION(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_SESSION, SimSession))
#define SIM_SESSION_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_SESSION, SimSessionClass))
#define SIM_IS_SESSION(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_SESSION))
#define SIM_IS_SESSION_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_SESSION))
#define SIM_SESSION_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_SESSION, SimSessionClass))

//this define's are usefull for sim_container_set_sensor_event_number(), wich is called when
//an event is issued from the agent. We use here the plugin_id to identify the "special" events. Anyway this
//may change in the future, so is better to keep the numbers controlled with a define
#define SIM_EVENT_EVENT               1
#define SIM_EVENT_HOST_OS_EVENT       1511
#define SIM_EVENT_HOST_MAC_EVENT      1512
#define SIM_EVENT_HOST_SERVICE_EVENT  1516
//#define SIM_EVENT_HOST_IDS_EVENT      4001

//plugin sids to store MAC changes, OS fingerprinting, etc. This plugin_sids are only used from some plugins, and
//they're needed because when the message arrives, we don't know yet the plugin_sid and we have to deduce it.
// #define EVENT_NEW     1
// #define EVENT_CHANGE  2
// #define EVENT_DELETED 3
// #define EVENT_SAME    4
#define EVENT_UNKNOWN 5

// With this configuration we achieve 1000 eps it the IDM goes down
#define SIM_IDM_RETRIES       3
#define SIM_IDM_RETRIES_WAIT  100

typedef struct _SimSession        SimSession;
typedef struct _SimSessionClass   SimSessionClass;
typedef struct _SimSessionPrivate SimSessionPrivate;

#include "sim-sensor.h"
#include "sim-enums.h"
#include "sim-command.h"
#include "sim-config.h"
#include "sim-database.h"
#include "sim-directive.h"
#include "sim-object.h"

struct _SimSession
{
  GObject parent;

  SimSessionType      type;

  SimSessionPrivate  *_priv;
};

struct _SimSessionClass
{
  GObjectClass parent_class;
};

GType             sim_session_get_type                        (void);
void              sim_session_register_type                   (void);

SimSession*       sim_session_new                             (GObject       *server,
                                                               SimConfig     *config,
                                                               GTcpSocket    *socket);

SimInet *         sim_session_get_ia                          (SimSession  *session);
void              sim_session_set_ia                          (SimSession  *session,
                                                               SimInet     *ia);
gint              sim_session_get_port                        (SimSession * session);
const gchar *     sim_session_get_ip_str                      (SimSession * session);
gboolean          sim_session_read                            (SimSession  *session);
gint              sim_session_write                           (SimSession  *session,
                                                               SimCommand  *command);
gint              sim_session_write_cache                     (SimSession  *session,
                                                               SimCommand  *command,
                                                               gboolean     use_cache);
gint              sim_session_write_from_buffer               (SimSession  *session,
                                                               gchar       *buffer);
gint              sim_session_write_from_buffer_cache         (SimSession  *session,
                                                               gchar       *buffer,
                                                               gboolean     use_cache);
gboolean          sim_session_has_plugin_type                 (SimSession  *session,
                                                               SimPluginType type);
gboolean          sim_session_has_plugin_id                   (SimSession  *session,
                                                               gint         plugin_id);
gpointer          sim_session_get_server                      (SimSession  *session);
SimSensor*        sim_session_get_sensor                      (SimSession  *session);
gboolean          sim_session_is_sensor                       (SimSession  *session);
gboolean          sim_session_is_server                       (SimSession  *session);
void              sim_session_close                           (SimSession  *session);
gboolean          sim_session_must_close                      (SimSession  *session);
void              sim_session_debug_channel_status            (SimSession  *session);

void              sim_session_resend_command                  (SimSession  *session,
                                                               SimCommand  *command);
gchar*            sim_session_get_hostname                    (SimSession  *session);
void              sim_session_set_hostname                    (SimSession  *session,
                                                               gchar       *hostname);
gboolean          sim_session_set_socket                      (SimSession  *session,
                                                               GTcpSocket  *socket);
void              sim_session_set_is_initial                  (SimSession  *session,
                                                               gboolean     tf);
gboolean          sim_session_get_is_initial                  (SimSession  *session);
void              sim_session_set_fully_stablished            (SimSession  *session);
void              sim_session_wait_fully_stablished           (SimSession  *session);

void              sim_session_handle_HUP                      (GIOChannel  *io,
                                                               GIOCondition condition,
                                                               gpointer     data);

void              sim_session_set_id                          (SimSession  *session,
                                                               gint         id);
gint              sim_session_get_id                          (SimSession  *session);
guint             sim_session_get_received                    (SimSession  *session);

void              sim_session_initialize_count                (SimSession  *session);
void              sim_session_increase_count                  (SimSession  *session);
void              sim_session_prepare_and_insert              (SimSession *session,
                                                               SimEvent *event);
void              sim_session_prepare_and_insert_non_block    (SimEvent    *event);

void              sim_session_set_event_scan_fn               (SimSession  *session,
                                                               gboolean (*pf)(SimCommand *, GScanner*));
gboolean          (*sim_session_get_event_scan_fn             (SimSession  *session))
                                                              (SimCommand  *,
                                                               GScanner    *);
gboolean          (*sim_session_get_event_scan                (SimSession  *session))
                                                              (SimCommand  *,
                                                               GScanner    *);

void              sim_session_set_g_io_hup                    (SimSession  *session);

void              sim_session_cmd_host_os_event               (SimSession  *session,
                                                               SimCommand  *command);
GIOStatus         sim_session_read_event                      (SimSession  *session,
                                                               gchar       *buffer,
                                                               gsize       *n);
gboolean          sim_session_check_iochannel_status          (SimSession  *session,
                                                               GIOStatus    status);
void              sim_session_reload                          (SimSession  *session,
                                                               SimContext  *context);
gboolean          sim_session_is_master_server                (SimSession  *session);
gboolean          sim_session_is_children_server              (SimSession  *session);
gboolean          sim_session_is_children_server              (SimSession  *session);
gboolean          sim_session_is_connected                    (SimSession  *session);
void              sim_session_set_sensor                      (SimSession  *session,
                                                               SimSensor   *sensor);
gboolean          sim_session_is_web                          (SimSession  *session);
void              sim_session_update_last_data_timestamp      (SimSession  *session);
time_t            sim_session_get_last_data_timestamp         (SimSession  *session);
time_t            sim_session_get_last_event_timestamp        (SimSession * session);

G_END_DECLS

#endif /* __SIM_SESSION_H__ */
// vim: set tabstop=2:
