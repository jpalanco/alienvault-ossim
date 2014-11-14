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

#ifndef __SIM_COMMAND_H__
#define __SIM_COMMAND_H__ 1

#include <glib.h>
#include <glib-object.h>
#include <gnet.h>
#include <uuid/uuid.h>

G_BEGIN_DECLS

#define SIM_TYPE_COMMAND                  (sim_command_get_type ())
#define SIM_COMMAND(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_COMMAND, SimCommand))
#define SIM_COMMAND_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_COMMAND, SimCommandClass))
#define SIM_IS_COMMAND(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_COMMAND))
#define SIM_IS_COMMAND_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_COMMAND))
#define SIM_COMMAND_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_COMMAND, SimCommandClass))

typedef struct _SimCommand        SimCommand;
typedef struct _SimCommandClass   SimCommandClass;

#include "sim-enums.h"
#include "sim-event.h"
#include "sim-rule.h"
#include "sim-radix.h"
#include "sim-uuid.h"
#include "sim-inet.h"
#include "sim-util.h"

struct _SimCommand {
  GObject parent;
	guint signature;

  SimCommandType      type;
  gint                id;
  SimUuid            *context_id; // Context identifier
  gchar              *buffer;     // here will be stored the original buffer received so we can resend it later
  gint                agentVersion;
  /* Funtion pointer*/
  gboolean (*pf_event_scan)(SimCommand *,GScanner *scanner);  
  union {
    struct {
      gchar          *username;
      gchar          *password;
      gchar          *hostname; //Used only in server conns. Not needed for sensors.
      SimVersion    * sensor_ver;
      SimUuid       * sensor_id;
      gfloat						tzone;
      SimSessionType  type;
    } connect;

    struct {												//command sent from server to frameworkd or to other servers
      gchar           *host;        //ip, not name. This is the children server connected to server "servername"
      gchar           *servername;    // OSSIM name.
    } server;


		struct {
      gint            id;
			gchar						*servername; //OSSIM name, no FQDN. Tells the name of the server from where we want to know the sensors connected.
    } server_get_sensors;

    struct {
      gint            id;
			gchar						*servername; //OSSIM server name, no FQDN
    } server_get_servers;


    struct {
      gint            id;
			gchar						*servername; //OSSIM server name, no FQDN
    } server_get_sensor_plugins;

    struct {
      gint            id;
			gchar						*servername;	//sever name to which send data to. 
			gboolean				store;
			gboolean				correlate;
			gboolean				cross_correlate;
      gboolean        reputation;
			gint            logger_if_priority;
			gboolean				qualify;
			gboolean				forward_alarm;
			gboolean				forward_event;
    } server_set_data_role;

    struct {												//command sent from server to frameworkd
      gchar           *host;        //ip, not name
			guint						sport;
      gboolean        state;
      gchar           *servername;  //this info is inserted by the server. This is the server to which is attached the sensor
    } sensor;

    struct {
      gint            id;
			gchar						*servername; 
      guint           num_events;
    } sensor_get_events;

    struct {
      gint            id;
			gchar						*servername; 
      gchar          *sensor;
      gint            plugin_id;
      gboolean        enabled;
      gint            state;
    } sensor_plugin;

    struct {
      gint            id;
			gchar						*servername; 
      gchar          *sensor;
      gint            plugin_id;
    } sensor_plugin_start;

    struct {
      gint            id;
			gchar						*servername; 
      gchar          *sensor;
      gint            plugin_id;
    } sensor_plugin_stop;

    struct {
      gint            id;
			gchar						*servername; 
      gchar          *sensor;
      gint            plugin_id;
    } sensor_plugin_enable;

    struct {
      gint            id;
			gchar						*servername; 
      gchar          *sensor;
      gint            plugin_id;
    } sensor_plugin_disable;

		//we could use just one struct to store all the "reload *" servername's,
		//but I prefer to use multiple structs to not break the common usage (sig...).
		//Oh, and may be in the future more fields needs to be added.
		struct {
			gchar						*servername;
		} reload_plugins;
		
		struct {
			gchar						*servername;
		} reload_sensors;
		
		struct {
			gchar						*servername;
		} reload_hosts;
		
		struct {
			gchar						*servername;
		} reload_nets;
		
		struct {
			gchar						*servername;
		} reload_policies;
		
		struct {
			gchar						*servername;
		} reload_directives;

    struct {
      gchar	          *servername;
    } reload_servers;

		struct {
			gchar						*servername;
		} reload_all;

    struct {
        gchar           *sids;
    } reload_post_correlation_sids;
		
		struct {
      gint            id;
      SimPluginType   type;
      gchar          *name;
      gboolean        enabled;
      gint            state;
    } session_append_plugin;

    struct {
      gint            id;
      SimPluginType   type;
      gchar          *name;
      gboolean        enabled;
      gint            state;
    } session_remove_plugin;


    struct {
      gint            id;
      gint            plugin_id;
    } plugin_state_started;

    struct {
      gint            id;
      gint            plugin_id;
    } plugin_state_unknown;

    struct {
      gint            id;
      gint            plugin_id;
    } plugin_state_stopped;

    struct {
      gint            id;
      gint            plugin_id;
    } plugin_enabled;

    struct {
      gint            id;
      gint            plugin_id;
    } plugin_disabled;

    struct {
      /* Event Info */
      gchar             *type;
      SimUuid           *id;     // This is used to identify each event.
                                 // We need to know with event are resonsable of generate each alarm,
                                 // because we need to show it in the interface
      guint              id_transaction;
      time_t             date;
      gchar             *date_str;
      gfloat             tzone;
      gchar             *sensor;
      SimUuid           *sensor_id;
      gchar             *device;
      guint              device_id;
      gchar             *server; //the server where the event was generated 
      gchar             *servername; //the server where the event was generated 
      gchar             *interface;

      /* Plugin Info */
      gint               plugin_id;
      gint               plugin_sid;

      /* Plugin Type Detector */
      gchar             *protocol;
      gchar             *src_ip;
      gchar             *dst_ip;
      gint               src_port;
      gint               dst_port;
      SimUuid          * src_net;
      SimUuid          * dst_net;

      /* Plugin Type Monitor */
      gchar             *condition;
      gchar             *value;
      gint               interval;

      gchar             *data;
      GString           *log;

      guint            snort_sid;
      guint            snort_cid;

      gint               reliability;
      gint               priority;
      gboolean           is_remote;
      gboolean           is_reliability_set;
      gboolean           is_priority_set;
      gint               asset_src;
      gint               asset_dst;
      gdouble            risk_c;
      gdouble            risk_a;
      gboolean					 alarm;

      SimEvent          *event;

			gchar							*filename;	//this variables are duplicated, here and inside the above "event" object
			gchar							*username;
			gchar							*password;
			gchar							*userdata1;
			gchar							*userdata2;
			gchar							*userdata3;
			gchar							*userdata4;
			gchar							*userdata5;
			gchar							*userdata6;
			gchar							*userdata7;
			gchar							*userdata8;
			gchar							*userdata9;
      gchar             *binary_data;
      guint              binary_data_len;

      // IDM info
      SimUuid           *src_id;
      SimUuid           *dst_id;
      gchar             *src_username;
      gchar             *dst_username;
      gchar             *src_hostname;
      gchar             *dst_hostname;
      gchar             *src_mac;
      gchar             *dst_mac;

      // Reputation data.
      guint   rep_prio_src;
      guint   rep_prio_dst;
      guint   rep_rel_src;
      guint   rep_rel_dst;
      gchar * rep_act_src;
      gchar * rep_act_dst;

      gboolean					belongs_to_alarm; //This variable is used for multiserver resend, to tell the upper servers that an event is part of an alarm
      gboolean					belongs_to_alarm_from_child;	//This variable is used for multiserver, in the upper servers, if some event arrives with this information, it will be inserted into ossim.event

      // Saqqara specific.
      SimUuid * saqqara_backlog_id;
      gint      level;
    } event;

    struct {
      gchar             *str;
    } watch_rule;


    struct {
      gint							id_transaction;
      gchar             *host;
      gchar             *os;
      gchar             *sensor;
      gchar             *server;
      gchar             *interface;

			gfloat							tzone;
      gchar             *date_str;
      time_t            date;
      gint               plugin_id;
      gint               plugin_sid;

      gchar             *log;
      SimUuid           *id;
    } host_os_event;

    struct {
      gint							id_transaction;
      gchar             *host;
      gchar             *mac;
      gchar             *vendor;
      gchar             *sensor;
      gchar             *server;
      gchar             *interface;

			gfloat							tzone;
      gchar             *date_str;
      time_t            date;
      gint               plugin_id;
      gint               plugin_sid;

      gchar             *log;
      SimUuid           *id;
    } host_mac_event;

    struct {
      gint							id_transaction;
      gchar             *host;
      gint               port;
      gint               protocol;
      gchar             *service;
      gchar             *sensor;
      gchar             *server;
      gchar             *interface;
      gchar             *application;

			gfloat							tzone;
      gchar             *date_str;
      time_t            date;

      gint               plugin_id;
      gint               plugin_sid;

      gchar             *log;
      SimUuid           *id;
    } host_service_event;

		struct {
      gint							id;	//Not used at this moment.
			SimDBElementType	database_element_type; //is this a Host query, or a network query, or a directive query....
			gchar							*servername;	//the master server to which is sended this query, has to know where does the msg come from.
																			//This is the server who originated the query.
		} database_query;
	
		struct {
      gint							id;
			gchar							*answer;
			SimDBElementType	database_element_type; //is this a Host answer, or a network answer, or a directive answer....
			gchar							*servername;	//children server to which is sended the answer
		} database_answer;

		//get framework command data
		struct
		{
		  gchar* framework_name;
		  gchar* framework_host;
		  gint framework_port;
		  gchar* server_host;
		  gchar* server_name;
		  gint server_port;
		} framework_data;
		//snort database data
		struct
		{
		  gchar *dbname;
		  gint dbport;
		  gchar *dbhost;
		  gchar *dbuser;
		  gchar *dbpassword;
		}snort_database_data;
    struct {
      SimInet    *ip;
      gboolean    is_login;
      gchar      *username;
      gchar      *hostname;
      gchar      *domain;
      gchar      *mac;
      gchar      *os;
      gchar      *cpu;
      gint        memory;
      gchar      *video;
      gchar      *service;
      gchar      *software;
      gchar      *state;
      gint        inventory_source;
      SimUuid    *host_id;
    } idm_event;
    struct {
      gint    id;
      gchar * your_sensor_id;
    } noack;

  } data;
};

struct _SimCommandClass {
  GObjectClass parent_class;
};

/* Prototypes */
GType             sim_command_get_type                        (void);
void              sim_command_register_type                   (void);

SimCommand*       sim_command_new                             (void);
SimCommand*       sim_command_new_from_buffer                 (const gchar     *buffer,
                                                               gboolean       (*pf_scan)(SimCommand *, GScanner *),
                                                               const gchar     *session_ip_str);
SimCommand*       sim_command_new_from_type                   (SimCommandType   type);
SimCommand*       sim_command_new_from_rule                   (SimRule         *rule);

void              sim_command_init_tls                        (void);

gchar*            sim_command_get_string                      (SimCommand      *command);

SimEvent*         sim_command_get_event                       (SimCommand      *command);

gboolean          sim_command_is_valid                        (SimCommand      *command);

gboolean (*sim_command_get_remote_server_scan(void))(SimCommand*,GScanner*);
gboolean (*sim_command_get_agent_scan(void))(SimCommand*,GScanner*);
gboolean (*sim_command_get_default_scan(void))(SimCommand*,GScanner*);
//gboolean sim_command_sensor_get_events_scan (SimCommand    *command, GScanner      *scanner,gchar* session_ip_str);
void sim_command_append_inets (SimRadixNode *node, void *string);
GScanner *sim_command_start_scanner (void);

gboolean sim_command_snort_event_scan (SimCommand	*command, GScanner *scanner, gchar* session_ip_str);
GHashTable *sim_command_idm_event_parse_username(const gchar *username);

G_END_DECLS

#endif /* __SIM_COMMAND_H__ */

// vim: set tabstop=2:


