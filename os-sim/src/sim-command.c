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

#define _XOPEN_SOURCE

#include "config.h"

#include "sim-command.h"

#include <gnet.h>
#include <time.h>
#include <string.h>
#include <zlib.h>
#include <errno.h>
#include <netinet/in.h>

#include "sim-session.h"
#include "sim-rule.h"
#include "sim-util.h"
#include "os-sim.h"
#include "sim-scanner-tokens.h"
#include "sim-sensor.h"
#include "sim-radix.h"
#include "sim-inet.h"
#include "sim-context.h"
#include "sim-network.h"

#define  DEFAULT_UNZIPLEN  32768  //1024 * 32 should be more than enough

extern SimMain  ossim;


SIM_DEFINE_TYPE (SimCommand, sim_command, G_TYPE_OBJECT, NULL)

/*
 * Remember that when the server sends something, the keywords are written in
 * sim_command_get_string(), not here. This command_symbols are just the
 * commands that the server receives
 */
static const struct
{
  gchar *name;
  guint token;
} command_symbols[] = {
  { "connect", SIM_COMMAND_SYMBOL_CONNECT },
  { "session-append-plugin", SIM_COMMAND_SYMBOL_SESSION_APPEND_PLUGIN },
  { "session-remove-plugin", SIM_COMMAND_SYMBOL_SESSION_REMOVE_PLUGIN },
  { "server-get-sensors", SIM_COMMAND_SYMBOL_SERVER_GET_SENSORS },
  { "sensor", SIM_COMMAND_SYMBOL_SENSOR },
  { "server-get-servers", SIM_COMMAND_SYMBOL_SERVER_GET_SERVERS },
  { "server", SIM_COMMAND_SYMBOL_SERVER },
  { "server-get-sensor-plugins", SIM_COMMAND_SYMBOL_SERVER_GET_SENSOR_PLUGINS },
  { "server-set-data-role", SIM_COMMAND_SYMBOL_SERVER_SET_DATA_ROLE },
  { "sensor-plugin", SIM_COMMAND_SYMBOL_SENSOR_PLUGIN },
  { "sensor-plugin-start", SIM_COMMAND_SYMBOL_SENSOR_PLUGIN_START },
  { "sensor-plugin-stop", SIM_COMMAND_SYMBOL_SENSOR_PLUGIN_STOP },
  { "sensor-plugin-enable", SIM_COMMAND_SYMBOL_SENSOR_PLUGIN_ENABLE },
  { "sensor-plugin-disable", SIM_COMMAND_SYMBOL_SENSOR_PLUGIN_DISABLE },
  { "sensor-get-events", SIM_COMMAND_SYMBOL_SENSOR_GET_EVENTS },
  { "plugin-process-started", SIM_COMMAND_SYMBOL_PLUGIN_STATE_STARTED },
  { "plugin-process-unknown", SIM_COMMAND_SYMBOL_PLUGIN_STATE_UNKNOWN },
  { "plugin-process-stopped", SIM_COMMAND_SYMBOL_PLUGIN_STATE_STOPPED },
  { "plugin-enabled", SIM_COMMAND_SYMBOL_PLUGIN_ENABLED },
  { "plugin-disabled", SIM_COMMAND_SYMBOL_PLUGIN_DISABLED },
  { "event", SIM_COMMAND_SYMBOL_EVENT },
  { "idm-event", SIM_COMMAND_SYMBOL_IDM_EVENT },
  { "reload-plugins", SIM_COMMAND_SYMBOL_RELOAD_PLUGINS },
  { "reload-sensors", SIM_COMMAND_SYMBOL_RELOAD_SENSORS },
  { "reload-hosts", SIM_COMMAND_SYMBOL_RELOAD_HOSTS },
  { "reload-nets", SIM_COMMAND_SYMBOL_RELOAD_NETS },
  { "reload-policies", SIM_COMMAND_SYMBOL_RELOAD_POLICIES },
  { "reload-directives", SIM_COMMAND_SYMBOL_RELOAD_DIRECTIVES },
  { "reload-servers", SIM_COMMAND_SYMBOL_RELOAD_SERVERS },
  { "reload-all", SIM_COMMAND_SYMBOL_RELOAD_ALL },
  { "reload-post-correlation-sids", SIM_COMMAND_SYMBOL_RELOAD_POST_CORRELATION_SIDS },
  { "host-os-event", SIM_COMMAND_SYMBOL_HOST_OS_EVENT },
  { "host-mac-event", SIM_COMMAND_SYMBOL_HOST_MAC_EVENT },
  { "host-service-event", SIM_COMMAND_SYMBOL_HOST_SERVICE_EVENT },
  { "agent-date", SIM_COMMAND_SYMBOL_AGENT_DATE},
  { "ok", SIM_COMMAND_SYMBOL_OK },
  { "pong", SIM_COMMAND_SYMBOL_PONG },
  { "error", SIM_COMMAND_SYMBOL_ERROR },
  { "database-query", SIM_COMMAND_SYMBOL_DATABASE_QUERY }, // DEPRECATED - kept for backward compatibility
  { "database-answer", SIM_COMMAND_SYMBOL_DATABASE_ANSWER }, // DEPRECATED - kept for backward compatibility
  {"snort-event", SIM_COMMAND_SYMBOL_SNORT_EVENT},
  {"backlog-event",SIM_COMMAND_SYMBOL_BACKLOG},
  {"server-get-db",SIM_COMMAND_SYMBOL_FRMK_GETDB},
  {"server-get-framework", SIM_COMMAND_SYMBOL_SENSOR_GET_FRAMEWORK },
  {"server-ping", SIM_COMMAND_SYMBOL_SENSOR_PING }
};

static const struct
{
  gchar *name;
  guint token;
} connect_symbols[] = {
  { "id", SIM_COMMAND_SYMBOL_ID },
  { "type", SIM_COMMAND_SYMBOL_TYPE },
  { "version", SIM_COMMAND_SYMBOL_AGENT_VERSION },
  { "tzone", SIM_COMMAND_SYMBOL_AGENT_TZONE },
  { "hostname", SIM_COMMAND_SYMBOL_HOSTNAME },	//this is the name of the server or the agent connected. Just mandatory in server conns.
  { "username", SIM_COMMAND_SYMBOL_USERNAME },
  { "password", SIM_COMMAND_SYMBOL_PASSWORD },
  { "sensor_id", SIM_COMMAND_SYMBOL_SENSOR_ID }
};

static const struct
{
  gchar *name;
  guint token;
} session_append_plugin_symbols[] = {
  { "id", SIM_COMMAND_SYMBOL_ID },
  { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID },
  { "type", SIM_COMMAND_SYMBOL_TYPE },
  { "name", SIM_COMMAND_SYMBOL_NAME },
  { "state", SIM_COMMAND_SYMBOL_STATE },
  { "enabled", SIM_COMMAND_SYMBOL_ENABLED }
};

static const struct
{
  gchar *name;
  guint token;
} session_remove_plugin_symbols[] = {
  { "id", SIM_COMMAND_SYMBOL_ID },
  { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID },
  { "type", SIM_COMMAND_SYMBOL_TYPE },
  { "name", SIM_COMMAND_SYMBOL_NAME },
  { "state", SIM_COMMAND_SYMBOL_STATE },
  { "enabled", SIM_COMMAND_SYMBOL_ENABLED }
};

static const struct
{
  gchar *name;
  guint token;
} server_get_sensors_symbols[] = {
  { "id", SIM_COMMAND_SYMBOL_ID },
  { "servername", SIM_COMMAND_SYMBOL_SERVERNAME }	//this is the server's name involved.
};

static const struct
{
  gchar *name;
  guint token;
} reload_post_correlation_sids_symbols[] = {
  { "id", SIM_COMMAND_SYMBOL_ID },
  { "sids", SIM_COMMAND_SYMBOL_POST_CORRELATION_SIDS }
};

static const struct
{
  gchar *name;
  guint token;
} server_get_servers_symbols[] = {
  { "id", SIM_COMMAND_SYMBOL_ID },
  { "servername", SIM_COMMAND_SYMBOL_SERVERNAME }	//this is the server's name involved.
};

static const struct
{
  gchar *name;
  guint token;
} server_set_data_role_symbols[] = {
  { "id", SIM_COMMAND_SYMBOL_ID },
  { "servername", SIM_COMMAND_SYMBOL_SERVERNAME },	//this is the server's name involved.
  { "role_correlate", SIM_COMMAND_SYMBOL_ROLE_CORRELATE },
  { "role_cross_correlate", SIM_COMMAND_SYMBOL_ROLE_CROSS_CORRELATE },
  { "role_reputation", SIM_COMMAND_SYMBOL_ROLE_REPUTATION },
	{ "role_logger_if_priority", SIM_COMMAND_SYMBOL_ROLE_LOGGER_IF_PRIORITY },
  { "role_store", SIM_COMMAND_SYMBOL_ROLE_STORE },
  { "role_qualify", SIM_COMMAND_SYMBOL_ROLE_QUALIFY }
};


static const struct
{
  gchar *name;
  guint token;
} sensor_symbols[] = {
  { "id", SIM_COMMAND_SYMBOL_ID },
  { "host", SIM_COMMAND_SYMBOL_HOST },
  { "state", SIM_COMMAND_SYMBOL_STATE },
  { "servername", SIM_COMMAND_SYMBOL_SERVERNAME }	//this is the server's name to which the sensor is attached
};

static const struct
{
  gchar *name;
  guint token;
} server_symbols[] = {									//answer to server-get-servers
  { "id", SIM_COMMAND_SYMBOL_ID },
  { "host", SIM_COMMAND_SYMBOL_HOST },	//this is the answer; this is one server attached to servername.
  { "servername", SIM_COMMAND_SYMBOL_SERVERNAME }	//this is the server's name to which the server is attached
};



static const struct
{
  gchar *name;
  guint token;
} server_get_sensor_plugins_symbols[] = {
  { "id", SIM_COMMAND_SYMBOL_ID },
  { "servername", SIM_COMMAND_SYMBOL_SERVERNAME }	//from what server should the sensor plugins be asked for?
};

static const struct
{
  gchar *name;
  guint token;
} sensor_plugin_symbols[] = {
  { "id", SIM_COMMAND_SYMBOL_ID },
  { "servername", SIM_COMMAND_SYMBOL_SERVERNAME },	//server name to send plugin data (multiserver architecture)
  { "sensor", SIM_COMMAND_SYMBOL_SENSOR },
  { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID },
  { "state", SIM_COMMAND_SYMBOL_STATE },
  { "enabled", SIM_COMMAND_SYMBOL_ENABLED }
};

static const struct
{
  gchar *name;
  guint token;
} sensor_plugin_start_symbols[] = {
  { "id", SIM_COMMAND_SYMBOL_ID },
  { "servername", SIM_COMMAND_SYMBOL_SERVERNAME },	//server name to send plugin commands to. (multiserver)
  { "sensor", SIM_COMMAND_SYMBOL_SENSOR },
  { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID }
};


static const struct
{
  gchar *name;
  guint token;
} sensor_plugin_stop_symbols[] = {
  { "id", SIM_COMMAND_SYMBOL_ID },
  { "servername", SIM_COMMAND_SYMBOL_SERVERNAME },	//server name to send plugin data
  { "sensor", SIM_COMMAND_SYMBOL_SENSOR },
  { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID }
};

static const struct
{
  gchar *name;
  guint token;
} sensor_plugin_enable_symbols[] = {
  { "id", SIM_COMMAND_SYMBOL_ID },
  { "servername", SIM_COMMAND_SYMBOL_SERVERNAME },
  { "sensor", SIM_COMMAND_SYMBOL_SENSOR },
  { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID }
};

static const struct
{
  gchar *name;
  guint token;
} sensor_plugin_disable_symbols[] = {
  { "id", SIM_COMMAND_SYMBOL_ID },
  { "servername", SIM_COMMAND_SYMBOL_SERVERNAME },
  { "sensor", SIM_COMMAND_SYMBOL_SENSOR },
  { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID }
};

static const struct
{
  gchar *name;
  guint token;
} sensor_get_events_symbols[] = {
  { "id", SIM_COMMAND_SYMBOL_ID }
};

static const struct
{
  gchar *name;
  guint token;
} plugin_state_started_symbols[] = {
  { "id", SIM_COMMAND_SYMBOL_ID },
  { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID }
};

static const struct
{
  gchar *name;
  guint token;
} plugin_state_unknown_symbols[] = {
  { "id", SIM_COMMAND_SYMBOL_ID },
  { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID }
};



static const struct
{
  gchar *name;
  guint token;
} plugin_state_stopped_symbols[] = {
  { "id", SIM_COMMAND_SYMBOL_ID },
  { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID }
};

static const struct
{
  gchar *name;
  guint token;
} plugin_enabled_symbols[] = {
  { "id", SIM_COMMAND_SYMBOL_ID },
  { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID }
};

static const struct
{
  gchar *name;
  guint token;
} plugin_disabled_symbols[] = {
  { "id", SIM_COMMAND_SYMBOL_ID },
  { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID }
};

static const struct
{
  gchar *name;
  guint token;
} event_symbols[] = {
  { "type", SIM_COMMAND_SYMBOL_TYPE },	//="detector" / ="monitor"
  { "event_id", SIM_COMMAND_SYMBOL_EVENT_ID }, // Event UUID
  { "id", SIM_COMMAND_SYMBOL_ID }, 
  { "ctx", SIM_COMMAND_SYMBOL_CTX },    // Context ID.
  { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID },
  { "plugin_sid", SIM_COMMAND_SYMBOL_PLUGIN_SID },
  { "date", SIM_COMMAND_SYMBOL_DATE },
  { "fdate", SIM_COMMAND_SYMBOL_DATE_STRING },
  { "tzone", SIM_COMMAND_SYMBOL_DATE_TZONE },
  { "sensor", SIM_COMMAND_SYMBOL_SENSOR },
  { "sensor_id", SIM_COMMAND_SYMBOL_SENSOR_ID },
  { "device", SIM_COMMAND_SYMBOL_DEVICE },
  { "device_id", SIM_COMMAND_SYMBOL_DEVICE_ID },
  { "servername", SIM_COMMAND_SYMBOL_SERVERNAME },
  { "server", SIM_COMMAND_SYMBOL_SERVER },
  { "interface", SIM_COMMAND_SYMBOL_INTERFACE },
  { "priority", SIM_COMMAND_SYMBOL_PRIORITY },
  { "protocol", SIM_COMMAND_SYMBOL_PROTOCOL },
  { "src_ip", SIM_COMMAND_SYMBOL_SRC_IP },
  { "src_port", SIM_COMMAND_SYMBOL_SRC_PORT },
  { "dst_ip", SIM_COMMAND_SYMBOL_DST_IP },
  { "dst_port", SIM_COMMAND_SYMBOL_DST_PORT },
  { "src_net", SIM_COMMAND_SYMBOL_SRC_NET },
  { "dst_net", SIM_COMMAND_SYMBOL_DST_NET },
  { "condition", SIM_COMMAND_SYMBOL_CONDITION },
  { "value", SIM_COMMAND_SYMBOL_VALUE },
  { "interval", SIM_COMMAND_SYMBOL_INTERVAL },
  { "backlog_type", SIM_COMMAND_SYMBOL_BACKLOG_EVENT_TYPE },
  { "directive_id", SIM_COMMAND_SYMBOL_BACKLOG_DIRECTIVE_ID },
  { "data", SIM_COMMAND_SYMBOL_DATA },
  { "log", SIM_COMMAND_SYMBOL_LOG },
  { "snort_sid", SIM_COMMAND_SYMBOL_SNORT_SID },
  { "snort_cid", SIM_COMMAND_SYMBOL_SNORT_CID },
  { "asset_src", SIM_COMMAND_SYMBOL_ASSET_SRC },
  { "asset_dst", SIM_COMMAND_SYMBOL_ASSET_DST },
  { "risk_a", SIM_COMMAND_SYMBOL_RISK_A },
  { "risk_c", SIM_COMMAND_SYMBOL_RISK_C },
  { "alarm", SIM_COMMAND_SYMBOL_ALARM },
  { "reliability", SIM_COMMAND_SYMBOL_RELIABILITY },
  { "filename", SIM_COMMAND_SYMBOL_FILENAME },
  { "username", SIM_COMMAND_SYMBOL_USERNAME },
  { "password", SIM_COMMAND_SYMBOL_PASSWORD },
  { "userdata1", SIM_COMMAND_SYMBOL_USERDATA1 },
  { "userdata2", SIM_COMMAND_SYMBOL_USERDATA2 },
  { "userdata3", SIM_COMMAND_SYMBOL_USERDATA3 },
  { "userdata4", SIM_COMMAND_SYMBOL_USERDATA4 },
  { "userdata5", SIM_COMMAND_SYMBOL_USERDATA5 },
  { "userdata6", SIM_COMMAND_SYMBOL_USERDATA6 },
  { "userdata7", SIM_COMMAND_SYMBOL_USERDATA7 },
  { "userdata8", SIM_COMMAND_SYMBOL_USERDATA8 },
  { "userdata9", SIM_COMMAND_SYMBOL_USERDATA9 },
  { "src_id", SIM_COMMAND_SYMBOL_SRC_ID },
  { "dst_id", SIM_COMMAND_SYMBOL_DST_ID },
  { "src_username", SIM_COMMAND_SYMBOL_SRC_USERNAME },
  { "dst_username", SIM_COMMAND_SYMBOL_DST_USERNAME },
  { "src_hostname", SIM_COMMAND_SYMBOL_SRC_HOSTNAME },
  { "dst_hostname", SIM_COMMAND_SYMBOL_DST_HOSTNAME },
  { "src_mac", SIM_COMMAND_SYMBOL_SRC_MAC },
  { "dst_mac", SIM_COMMAND_SYMBOL_DST_MAC },
  { "rep_prio_src", SIM_COMMAND_SYMBOL_REP_PRIO_SRC },
  { "rep_prio_dst", SIM_COMMAND_SYMBOL_REP_PRIO_DST },
  { "rep_rel_src", SIM_COMMAND_SYMBOL_REP_REL_SRC },
  { "rep_rel_dst", SIM_COMMAND_SYMBOL_REP_REL_DST },
  { "rep_act_src", SIM_COMMAND_SYMBOL_REP_ACT_SRC },
  { "rep_act_dst", SIM_COMMAND_SYMBOL_REP_ACT_DST },
  { "belongs_to_alarm", SIM_COMMAND_SYMBOL_BELONGS_TO_ALARM },
  { "is_remote", SIM_COMMAND_SYMBOL_IS_REMOTE },
  { "uuid",SIM_COMMAND_SYMBOL_UUID },
  { "uuid_backlog",SIM_COMMAND_SYMBOL_UUID_BACKLOG},
  { "backlog_id", SIM_COMMAND_SYMBOL_BACKLOG_ID },
  { "level", SIM_COMMAND_SYMBOL_LEVEL},
  { "binary_data", SIM_COMMAND_SYMBOL_BINARY_DATA}
};

static const struct
{
  gchar *name;
  guint token;
} reload_plugins_symbols[] = {
  { "id", SIM_COMMAND_SYMBOL_ID },
  { "servername", SIM_COMMAND_SYMBOL_SERVERNAME }
};

static const struct
{
  gchar *name;
  guint token;
} reload_sensors_symbols[] = {
  { "id", SIM_COMMAND_SYMBOL_ID },
  { "servername", SIM_COMMAND_SYMBOL_SERVERNAME }
};

static const struct
{
  gchar *name;
  guint token;
} reload_hosts_symbols[] = {
  { "id", SIM_COMMAND_SYMBOL_ID },
  { "ctx", SIM_COMMAND_SYMBOL_CTX },
  { "servername", SIM_COMMAND_SYMBOL_SERVERNAME }
};

static const struct
{
  gchar *name;
  guint token;
} reload_nets_symbols[] = {
  { "id", SIM_COMMAND_SYMBOL_ID },
  { "ctx", SIM_COMMAND_SYMBOL_CTX },
  { "servername", SIM_COMMAND_SYMBOL_SERVERNAME }
};

static const struct
{
  gchar *name;
  guint token;
} reload_policies_symbols[] = {
  { "id", SIM_COMMAND_SYMBOL_ID },
  { "ctx", SIM_COMMAND_SYMBOL_CTX },
  { "servername", SIM_COMMAND_SYMBOL_SERVERNAME }
};

static const struct
{
  gchar *name;
  guint token;
} reload_directives_symbols[] = {
  { "id", SIM_COMMAND_SYMBOL_ID },
  { "ctx", SIM_COMMAND_SYMBOL_CTX },
  { "servername", SIM_COMMAND_SYMBOL_SERVERNAME }
};

static const struct
{
  gchar *name;
  guint token;
} reload_servers_symbols[] = {
  { "id", SIM_COMMAND_SYMBOL_ID },
  { "ctx", SIM_COMMAND_SYMBOL_CTX },
  { "servername", SIM_COMMAND_SYMBOL_SERVERNAME }
};

static const struct
{
  gchar *name;
  guint token;
} reload_all_symbols[] = {
  { "id", SIM_COMMAND_SYMBOL_ID },
  { "ctx", SIM_COMMAND_SYMBOL_CTX },
  { "servername", SIM_COMMAND_SYMBOL_SERVERNAME }
};

static const struct
{
  gchar *name;
  guint token;
} host_os_event_symbols[] = {
  { "date", SIM_COMMAND_SYMBOL_DATE },
  { "fdate", SIM_COMMAND_SYMBOL_DATE_STRING },
  { "tzone", SIM_COMMAND_SYMBOL_DATE_TZONE },
  { "id", SIM_COMMAND_SYMBOL_ID },	//event it, not the message id.	
  { "host", SIM_COMMAND_SYMBOL_HOST },
  { "os", SIM_COMMAND_SYMBOL_OS },
  { "sensor", SIM_COMMAND_SYMBOL_SENSOR },
  { "servername", SIM_COMMAND_SYMBOL_SERVERNAME },
  { "interface", SIM_COMMAND_SYMBOL_INTERFACE },
  { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID },
  { "plugin_sid", SIM_COMMAND_SYMBOL_PLUGIN_SID },
  { "log", SIM_COMMAND_SYMBOL_LOG },
  { "src_ip", SIM_COMMAND_SYMBOL_SRC_IP },
  { "dst_ip", SIM_COMMAND_SYMBOL_DST_IP },
  { "ctx", SIM_COMMAND_SYMBOL_CTX },
  { "event_id", SIM_COMMAND_SYMBOL_EVENT_ID }

};

static const struct
{
  gchar *name;
  guint token;
} host_mac_event_symbols[] = {
  { "date", SIM_COMMAND_SYMBOL_DATE },
  { "fdate", SIM_COMMAND_SYMBOL_DATE_STRING },
  { "tzone", SIM_COMMAND_SYMBOL_DATE_TZONE },
  { "id", SIM_COMMAND_SYMBOL_ID },		
  { "host", SIM_COMMAND_SYMBOL_HOST },
  { "mac", SIM_COMMAND_SYMBOL_MAC },
  { "vendor", SIM_COMMAND_SYMBOL_VENDOR },
  { "sensor", SIM_COMMAND_SYMBOL_SENSOR },
  { "servername", SIM_COMMAND_SYMBOL_SERVERNAME },
  { "interface", SIM_COMMAND_SYMBOL_INTERFACE },
  { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID },
  { "plugin_sid", SIM_COMMAND_SYMBOL_PLUGIN_SID },
  { "log", SIM_COMMAND_SYMBOL_LOG },
  { "src_ip", SIM_COMMAND_SYMBOL_SRC_IP },
  { "dst_ip", SIM_COMMAND_SYMBOL_DST_IP },
  { "ctx", SIM_COMMAND_SYMBOL_CTX },
  { "event_id", SIM_COMMAND_SYMBOL_EVENT_ID }
};

static const struct
{
  gchar *name;
  guint token;
} host_service_event_symbols[] = {
  { "date", SIM_COMMAND_SYMBOL_DATE },
  { "fdate", SIM_COMMAND_SYMBOL_DATE_STRING },
  { "tzone", SIM_COMMAND_SYMBOL_DATE_TZONE },
  { "id", SIM_COMMAND_SYMBOL_ID },		
  { "host", SIM_COMMAND_SYMBOL_HOST },
  { "port", SIM_COMMAND_SYMBOL_PORT },
  { "sensor", SIM_COMMAND_SYMBOL_SENSOR },
  { "servername", SIM_COMMAND_SYMBOL_SERVERNAME },
  { "protocol", SIM_COMMAND_SYMBOL_PROTOCOL },
  { "service", SIM_COMMAND_SYMBOL_SERVICE },
  { "application", SIM_COMMAND_SYMBOL_APPLICATION },
  { "interface", SIM_COMMAND_SYMBOL_INTERFACE },
  { "plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID },
  { "plugin_sid", SIM_COMMAND_SYMBOL_PLUGIN_SID },
  { "log", SIM_COMMAND_SYMBOL_LOG },
  { "src_ip", SIM_COMMAND_SYMBOL_SRC_IP },
  { "dst_ip", SIM_COMMAND_SYMBOL_DST_IP },
  { "ctx", SIM_COMMAND_SYMBOL_CTX },
  { "event_id", SIM_COMMAND_SYMBOL_EVENT_ID }
};

static const struct
{
  gchar *name;
  guint token;
} ok_symbols[] = {
  { "id", SIM_COMMAND_SYMBOL_ID }
};

static const struct
{
  gchar *name;
  guint token;
} error_symbols[] = {
  { "id", SIM_COMMAND_SYMBOL_ID }
};

static const struct
{
  gchar *name;
  guint token;
} database_query_symbols[] = {
  { "id", SIM_COMMAND_SYMBOL_ID },		
  { "database-element-type", SIM_COMMAND_SYMBOL_DATABASE_ELEMENT_TYPE },
  { "servername", SIM_COMMAND_SYMBOL_SERVERNAME },
  { "sensorname", SIM_COMMAND_SYMBOL_SENSORNAME }
};

static const struct
{
  gchar *name;
  guint token;
} database_answer_symbols[] = {
  { "id", SIM_COMMAND_SYMBOL_ID },		
  { "answer", SIM_COMMAND_SYMBOL_ANSWER },
  { "database-element-type", SIM_COMMAND_SYMBOL_DATABASE_ELEMENT_TYPE },
  { "servername", SIM_COMMAND_SYMBOL_SERVERNAME }
};

static const struct
{
  gchar *name;
  guint token;
} snort_event_symbols[]={
  {"sensor",SIM_COMMAND_SYMBOL_SNORT_EVENT_SENSOR},
  {"server", SIM_COMMAND_SYMBOL_SERVER},
  {"servername", SIM_COMMAND_SYMBOL_SERVERNAME },
  {"interface",SIM_COMMAND_SYMBOL_SNORT_EVENT_IF},
  {"unziplen",SIM_COMMAND_SYMBOL_UNZIPLEN},
  {"gzipdata",SIM_COMMAND_SYMBOL_GZIPDATA},
  {"binary_data",SIM_COMMAND_SYMBOL_BINARY_DATA},
  {"event_type",SIM_COMMAND_SYMBOL_SNORT_EVENT_TYPE},
  {"uuid",SIM_COMMAND_SYMBOL_UUID},
  {"belongs_to_alarm", SIM_COMMAND_SYMBOL_BELONGS_TO_ALARM },
  {"plugin_id", SIM_COMMAND_SYMBOL_PLUGIN_ID },
  {"type", SIM_COMMAND_SYMBOL_TYPE },
  {"date", SIM_COMMAND_SYMBOL_DATE },
  {"fdate", SIM_COMMAND_SYMBOL_DATE_STRING },
  {"tzone", SIM_COMMAND_SYMBOL_DATE_TZONE },
  {"src_ip", SIM_COMMAND_SYMBOL_SRC_IP },
  {"dst_ip", SIM_COMMAND_SYMBOL_DST_IP },
  {"ipv6", SIM_COMMAND_SYMBOL_IPV6}

};

static const struct 
{
	gchar *name;
	guint token;
} snort_event_data_symbols[]={
	{"type",SIM_COMMAND_SYMBOL_SNORT_EVENT_DATA_TYPE},
	{"date",SIM_COMMAND_SYMBOL_SNORT_EVENT_DATE},
	{"fdate", SIM_COMMAND_SYMBOL_SNORT_EVENT_DATE_STRING },
  {"tzone", SIM_COMMAND_SYMBOL_DATE_TZONE },
	{"snort_gid",SIM_COMMAND_SYMBOL_SNORT_EVENT_GID},
	{"snort_sid",SIM_COMMAND_SYMBOL_SNORT_EVENT_SID},
	{"snort_rev",SIM_COMMAND_SYMBOL_SNORT_EVENT_REV},
	{"snort_classification",SIM_COMMAND_SYMBOL_SNORT_EVENT_CLASSIFICATION},
	{"snort_priority",SIM_COMMAND_SYMBOL_SNORT_EVENT_PRIORITY},
	{"packet_type",SIM_COMMAND_SYMBOL_SNORT_EVENT_PACKET_TYPE},
  // IPV6 symbols
  {"sensor_id", SIM_COMMAND_SYMBOL_SNORT_SENSOR_ID},
  {"event_id", SIM_COMMAND_SYMBOL_SNORT_EVENT_ID},
  {"event_second", SIM_COMMAND_SYMBOL_SNORT_EVENT_SECOND},
  {"event_microsecond", SIM_COMMAND_SYMBOL_SNORT_EVENT_MICROSECOND},
  {"signature_id", SIM_COMMAND_SYMBOL_SNORT_SIGNATURE_ID},
  {"generator_id", SIM_COMMAND_SYMBOL_SNORT_GENERATOR_ID},
  {"signature_revision", SIM_COMMAND_SYMBOL_SNORT_SIGNATURE_REVISION},
  {"classification_id", SIM_COMMAND_SYMBOL_SNORT_CLASSIFICATION_ID},
  {"priority_id", SIM_COMMAND_SYMBOL_SNORT_PRIORITY_ID},
  {"source_ip", SIM_COMMAND_SYMBOL_SNORT_SOURCE_IP},
  {"destination_ip", SIM_COMMAND_SYMBOL_SNORT_DESTINATION_IP},
  {"source_port_itype", SIM_COMMAND_SYMBOL_SNORT_SOURCE_PORT_ITYPE},
  {"dest_port_itype", SIM_COMMAND_SYMBOL_SNORT_DEST_PORT_ITYPE},
  {"protocol", SIM_COMMAND_SYMBOL_SNORT_PROTOCOL},
  {"impact_flag", SIM_COMMAND_SYMBOL_SNORT_IMPACT_FLAG},
  {"impact", SIM_COMMAND_SYMBOL_SNORT_IMPACT},
  {"blocked", SIM_COMMAND_SYMBOL_SNORT_BLOCKED}
};

static const struct
{
  gchar *name;
  guint token;
} agent_date_symbols[] = {                  //date from agents
  { "agent_date", SIM_COMMAND_SYMBOL_AGENT__DATE },
  { "tzone", SIM_COMMAND_SYMBOL_DATE_TZONE }
};

static const struct{
	gchar *name;
	gint token;
} snort_event_packet_raw_symbols[]={
{"raw_packet",SIM_COMMAND_SYMBOL_SNORT_EVENT_PACKET_RAW}
};

static const struct
{
	gchar *name;
	guint token;
} snort_event_packet_ip_symbols[]={
  {"ip_ver",SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_VER},
	{"ip_tos",SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_TOS},
	{"ip_id",SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_ID},
	{"ip_offset",SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_OFFSET},
	{"ip_hdrlen",SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_HDRLEN},
	{"ip_len",SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_LEN},
	{"ip_ttl",SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_TTL},
	{"ip_proto",SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_PROTO},
	{"ip_csum",SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_CSUM},
	{"ip_src",SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_SRC},
	{"ip_dst",SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_DST},
	{"ip_optnum",SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_OPTNUM},
	{"ip_optcode",SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_OPTCODE},
	{"ip_optlen",SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_OPTLEN},
	{"ip_optpayload",SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_OPTPAYLOAD},
	{"ip_ippayload",SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_PAYLOAD},
  {"ip_traffic", SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_TRAFFIC},         // IPV6
  {"ip_flowlabel", SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_FLOWLABEL},     // IPV6
  {"ip_payload_len", SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_PAYLOAD_LEN}, // IPV6
  {"ip_hoplimit", SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_HOPLIMIT}        // IPV6
};

static const struct 
{
	gchar *name;
	guint token;
} snort_event_packet_icmp_symbols[]={
	{"icmp_type",SIM_COMMAND_SYMBOL_SNORT_EVENT_ICMP_TYPE},
	{"icmp_code",SIM_COMMAND_SYMBOL_SNORT_EVENT_ICMP_CODE},
	{"icmp_csum",SIM_COMMAND_SYMBOL_SNORT_EVENT_ICMP_CSUM},
	{"icmp_id",SIM_COMMAND_SYMBOL_SNORT_EVENT_ICMP_ID},
	{"icmp_seq",SIM_COMMAND_SYMBOL_SNORT_EVENT_ICMP_SEQ},
	{"icmp_payload",SIM_COMMAND_SYMBOL_SNORT_EVENT_ICMP_PAYLOAD}
};

static const struct
{
	gchar *name;
	guint token;
	
} snort_event_packet_udp_symbols[]={
	{"udp_sport",SIM_COMMAND_SYMBOL_SNORT_EVENT_UDP_SPORT},
	{"udp_dport",SIM_COMMAND_SYMBOL_SNORT_EVENT_UDP_DPORT},
	{"udp_len",SIM_COMMAND_SYMBOL_SNORT_EVENT_UDP_LEN},
	{"udp_csum",SIM_COMMAND_SYMBOL_SNORT_EVENT_UDP_CSUM},
	{"udp_payload",SIM_COMMAND_SYMBOL_SNORT_EVENT_UDP_PAYLOAD}
};

static const struct
{
	gchar *name;
	guint token;
} snort_event_packet_tcp_symbols[]={
	{"tcp_sport",SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_SPORT},
	{"tcp_dport",SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_DPORT},
	{"tcp_seq",SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_SEQ},
	{"tcp_ack",SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_ACK},
	{"tcp_flags",SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_FLAGS},
	{"tcp_offset",SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_OFFSET},
	{"tcp_window",SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_WINDOW},
	{"tcp_csum",SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_CSUM},
	{"tcp_urgptr",SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_URGPTR},
	{"tcp_optnum",SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_OPTNUM},
	{"tcp_optcode",SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_OPTCODE},
	{"tcp_optlen",SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_OPTLEN},
	{"tcp_optpayload",SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_OPTPAYLOAD},
	{"tcp_payload",SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_PAYLOAD}
};

static const struct{
	gchar *name;
	guint token;
}backlog_event_symbols[]={
	{"directive_id",SIM_COMMAND_SYMBOL_BACKLOG_DIRECTIVE_ID},
	{"ctx",SIM_COMMAND_SYMBOL_BACKLOG_CTX},
	{"uuid",SIM_COMMAND_SYMBOL_BACKLOG_UUID},
	{"uuid_event",SIM_COMMAND_SYMBOL_BACKLOG_UUID_EVENT},
	{"level",SIM_COMMAND_SYMBOL_BACKLOG_LEVEL},
	{"matched",SIM_COMMAND_SYMBOL_BACKLOG_MATCHED},
	{"occurrence", SIM_COMMAND_SYMBOL_BACKLOG_OCCURRENCE},
	{"type", SIM_COMMAND_SYMBOL_TYPE}
};

static const struct{
  gchar *name;
  guint token;
}idm_event_symbols[]={
  {"ip",SIM_COMMAND_SYMBOL_IP},
  {"login",SIM_COMMAND_SYMBOL_LOGIN},
  {"username",SIM_COMMAND_SYMBOL_USERNAME},
  {"hostname",SIM_COMMAND_SYMBOL_HOSTNAME},
  {"domain",SIM_COMMAND_SYMBOL_DOMAIN},
  {"mac",SIM_COMMAND_SYMBOL_MAC},
  {"os",SIM_COMMAND_SYMBOL_OS},
  {"cpu",SIM_COMMAND_SYMBOL_CPU},
  {"memory",SIM_COMMAND_SYMBOL_MEMORY},
  {"video",SIM_COMMAND_SYMBOL_VIDEO},
  {"service",SIM_COMMAND_SYMBOL_SERVICE},
  {"software",SIM_COMMAND_SYMBOL_SOFTWARE},
  {"state",SIM_COMMAND_SYMBOL_STATE},
  {"inventory_source",SIM_COMMAND_SYMBOL_INVENTORY_SOURCE},
  {"ctx",SIM_COMMAND_SYMBOL_CTX},
  {"host",SIM_COMMAND_SYMBOL_HOST}
};

static const struct{
  gchar *name;
  guint token;
}idm_reload_context_symbols[]={
  {"id",SIM_COMMAND_SYMBOL_ID},
  {"ctx",SIM_COMMAND_SYMBOL_CTX}
};

static gboolean sim_command_scan												(SimCommand    *command,
																												 const gchar   *buffer,
                                                         gboolean (*pf_scan)(SimCommand *, GScanner *),
                                                         const gchar   *session_ip_str);
static gboolean sim_command_connect_scan								(SimCommand    *command,
																										      GScanner      *scanner,
																										      gchar* session_ip_str);
static gboolean sim_command_session_append_plugin_scan	(SimCommand    *command,
																											    GScanner      *scanner,
																											    gchar* session_ip_str);
static gboolean sim_command_session_remove_plugin_scan	(SimCommand    *command,
																													GScanner      *scanner,
																													gchar* session_ip_str);

static gboolean sim_command_server_get_sensors_scan			(SimCommand    *command,
																													 GScanner      *scanner,
																													 gchar* session_ip_str);
static gboolean sim_command_sensor_scan									(SimCommand    *command,
																	                        GScanner      *scanner,
																	                        gchar* session_ip_str);
static gboolean sim_command_server_get_servers_scan     (SimCommand    *command,
                                                           GScanner      *scanner,
                                                           gchar* session_ip_str);
static gboolean sim_command_server_scan									(SimCommand    *command,
																	                        GScanner      *scanner,
																	                        gchar* session_ip_str);
static gboolean sim_command_server_get_sensor_plugins_scan (SimCommand    *command,
																														GScanner      *scanner,
																														gchar* session_ip_str);

static gboolean sim_command_server_set_data_role_scan		(SimCommand    *command,
																													GScanner      *scanner,
																													gchar* session_ip_str);
static gboolean sim_command_sensor_plugin_scan					(SimCommand    *command,
																											    GScanner      *scanner,
																											    gchar* session_ip_str);
static gboolean sim_command_sensor_plugin_start_scan		(SimCommand    *command,
																												  GScanner      *scanner,
																												  gchar* session_ip_str);
static gboolean sim_command_sensor_plugin_stop_scan			(SimCommand    *command,
																												 GScanner      *scanner,
																												 gchar* session_ip_str);
static gboolean sim_command_sensor_plugin_enable_scan	(SimCommand    *command,
																											   GScanner      *scanner,
																											   gchar* session_ip_str);
static gboolean sim_command_sensor_plugin_disable_scan (SimCommand    *command,
																										     GScanner      *scanner,
																										     gchar* session_ip_str);
static gboolean sim_command_plugin_state_started_scan						(SimCommand    *command,
																											   GScanner      *scanner,
																											   gchar* session_ip_str);
static gboolean sim_command_plugin_state_unknown_scan					(SimCommand    *command,
																											   GScanner      *scanner,
																											   gchar * session_ip_str);
static gboolean sim_command_plugin_state_stopped_scan						(SimCommand    *command,
																												  GScanner      *scanner,
																												  gchar* session_ip_str);
static gboolean sim_command_plugin_enabled_scan					(SimCommand    *command,
																										     GScanner      *scanner,
																										     gchar* session_ip_str);
static gboolean sim_command_plugin_disabled_scan				(SimCommand    *command,
																										      GScanner      *scanner,
																										      gchar* session_ip_str);
static gboolean sim_command_sensor_get_events_scan (SimCommand    *command,
																	  								GScanner      *scanner,
																	  								gchar* session_ip_str);
static gboolean sim_command_event_scan_base64						(SimCommand    *command,
																											    GScanner      *scanner);

static gboolean sim_command_reload_plugins_scan					(SimCommand    *command,
																										     GScanner      *scanner,
																										     gchar* session_ip_str);
static gboolean sim_command_reload_sensors_scan					(SimCommand    *command,
																										     GScanner      *scanner,
																										     gchar* session_ip_str);
static gboolean sim_command_reload_hosts_scan						(SimCommand    *command,
																											   GScanner      *scanner,
																											   gchar* session_ip_str);
static gboolean sim_command_reload_nets_scan						(SimCommand    *command,
																												  GScanner      *scanner,
																												  gchar* session_ip_str);
static gboolean sim_command_reload_policies_scan				(SimCommand    *command,
																										      GScanner      *scanner,
																										      gchar* session_ip_str);
static gboolean sim_command_reload_directives_scan			(SimCommand    *command,
																													GScanner      *scanner,
																													gchar* session_ip_str);
static gboolean sim_command_reload_servers_scan         (SimCommand    *command,
                                                         GScanner      *scanner,
                                                         gchar         *session_ip_str);
static gboolean sim_command_reload_all_scan							(SimCommand    *command,
																												 GScanner      *scanner,
																												 gchar* session_ip_str);
static gboolean sim_command_reload_post_correlation_sids (SimCommand    *command,
                                                          GScanner      *scanner,
                                                          gchar* session_ip_str);
static gboolean sim_command_host_os_event_scan					(SimCommand    *command,
																										     GScanner      *scanner,
																										     gchar* session_ip_str);
static gboolean sim_command_host_mac_event_scan					(SimCommand    *command,
																										      GScanner      *scanner,
																										      gchar * session_ip_str);
static gboolean sim_command_host_service_event_scan			(SimCommand    *command,
																										      GScanner      *scanner,
																										      gchar* session_ip_str);
static gboolean sim_command_ok_scan											(SimCommand    *command,
																										      GScanner      *scanner,
																										      gchar* session_ip_str);
static gboolean sim_command_error_scan									(SimCommand    *command,
																										      GScanner      *scanner,
																										      gchar* session_ip_str);
static gboolean sim_command_pong_scan                   (SimCommand    *command,
                                                          GScanner      *scanner,
                                                          gchar* session_ip_str);
static gboolean sim_command_database_query_scan         (SimCommand    *command,
                                                          GScanner      *scanner,
                                                          gchar* session_ip_str);
static gboolean sim_command_database_answer_scan        (SimCommand    *command,
                                                          GScanner      *scanner,
                                                          gchar* session_ip_str);
static gboolean sim_command_agent_date_scan             (SimCommand    *command,
                                                          GScanner      *scanner,
                                                          gchar* session_ip_str);
static gboolean sim_command_backlog_event_scan					(SimCommand		*command,
																													GScanner		*scanner,
																													gchar* session_ip_str);
static gboolean sim_command_idm_event_scan     					(SimCommand		*command,
																													GScanner		*scanner,
																													gchar* session_ip_str);

static gboolean sim_command_frmk_getdb_scan(SimCommand *command, GScanner *scanner,gchar* session_ip_str);
static gboolean sim_command_sensor_getframeworkconnexion_scan(SimCommand* command, GScanner *scanner,gchar* session_ip_str);
static gboolean sim_command_sensor_ping_scan(SimCommand* command, GScanner *scanner,gchar* session_ip_str);
static GPrivate *privScanner=NULL;

static void sim_command_init_command_event_struct(SimCommand *command);

static gboolean sim_command_snort_ipv4_packet_scan (GScanner *scanner, SimCommand *command);
static gboolean sim_command_snort_ipv6_packet_scan (GScanner *scanner, SimCommand *command);
static gboolean sim_command_snort_ipv4_header_scan (GScanner *scanner, SimCommand *command);
static gboolean sim_command_snort_ipv6_header_scan (GScanner *scanner, SimCommand *command);
static gboolean sim_command_snort_event_packet_udp_scan (GScanner *scanner, SimCommand *command);
static gboolean sim_command_snort_event_packet_tcp_scan (GScanner *scanner, SimCommand *command);
static gboolean sim_command_snort_event_packet_icmp_scan (GScanner *scanner, SimCommand *command);
static gboolean sim_command_snort_protocol_scan (GScanner *scanner, SimCommand *command, guint32 protocol);
static void sim_command_snort_event_skip_ip_opt_scan (GScanner *scanner);
static void sim_command_snort_event_skip_tcp_opt_scan (GScanner *scanner);

/*
 * Init de TLS system for all the threads
 * must be called AFTER g_thread_init()
 * The thread local variable store the pointer to the lexical scanner
 */
 
 void sim_command_init_tls(void){
                privScanner = g_private_new((GDestroyNotify)g_scanner_destroy);
 }



/* GType Functions */

static void
sim_command_finalize (GObject  *gobject)
{
  SimCommand *cmd = SIM_COMMAND (gobject);

  ossim_debug ( "sim_command_impl_finalize");

  g_free (cmd->buffer);

  if (cmd->context_id)
    g_object_unref (cmd->context_id);

  switch (cmd->type)
  {
  case SIM_COMMAND_TYPE_CONNECT:
    g_free (cmd->data.connect.username);
    g_free (cmd->data.connect.password);
    g_free (cmd->data.connect.hostname);
    g_free (cmd->data.connect.sensor_ver);
    break;

  case SIM_COMMAND_TYPE_SERVER_SET_DATA_ROLE:
    g_free (cmd->data.server_set_data_role.servername);
    break;

  case SIM_COMMAND_TYPE_SERVER_GET_SENSORS:
    g_free (cmd->data.server_get_sensors.servername);
    break;

  case SIM_COMMAND_TYPE_SERVER_GET_SENSOR_PLUGINS:
    g_free (cmd->data.server_get_sensor_plugins.servername);
    break;
  case SIM_COMMAND_TYPE_SESSION_APPEND_PLUGIN:
    g_free (cmd->data.session_append_plugin.name);
    break;
  case SIM_COMMAND_TYPE_SESSION_REMOVE_PLUGIN:
    g_free (cmd->data.session_remove_plugin.name);
    break;
  case SIM_COMMAND_TYPE_SNORT_EVENT:
  case SIM_COMMAND_TYPE_EVENT:
    if (cmd->data.event.id)
      g_object_unref (cmd->data.event.id);
    g_free (cmd->data.event.type);
    g_free (cmd->data.event.date_str);

    if (cmd->data.event.sensor_id)
      g_object_unref (cmd->data.event.sensor_id);

    g_free (cmd->data.event.sensor);
    g_free (cmd->data.event.device);
    g_free (cmd->data.event.server);
    g_free (cmd->data.event.servername);
    g_free (cmd->data.event.interface);

    g_free (cmd->data.event.protocol);
    g_free (cmd->data.event.src_ip);
    g_free (cmd->data.event.dst_ip);

    if (cmd->data.event.src_net)
      g_object_unref (cmd->data.event.src_net);

    if (cmd->data.event.dst_net)
      g_object_unref (cmd->data.event.dst_net);

    g_free (cmd->data.event.condition);
    g_free (cmd->data.event.value);

    g_free (cmd->data.event.data);
    if (cmd->data.event.log)
      g_string_free (cmd->data.event.log, TRUE);

    g_free (cmd->data.event.filename);
    g_free (cmd->data.event.username);
    g_free (cmd->data.event.password);
    g_free (cmd->data.event.userdata1);
    g_free (cmd->data.event.userdata2);
    g_free (cmd->data.event.userdata3);
    g_free (cmd->data.event.userdata4);
    g_free (cmd->data.event.userdata5);
    g_free (cmd->data.event.userdata6);
    g_free (cmd->data.event.userdata7);
    g_free (cmd->data.event.userdata8);
    g_free (cmd->data.event.userdata9);
    if (cmd->data.event.src_id)
      g_object_unref (cmd->data.event.src_id);
    if (cmd->data.event.dst_id)
      g_object_unref (cmd->data.event.dst_id);
    g_free (cmd->data.event.src_username);
    g_free (cmd->data.event.dst_username);
    g_free (cmd->data.event.src_hostname);
    g_free (cmd->data.event.dst_hostname);
    g_free (cmd->data.event.src_mac);
    g_free (cmd->data.event.dst_mac);
    g_free (cmd->data.event.rep_act_src);
    g_free (cmd->data.event.rep_act_dst);

    if (cmd->data.event.saqqara_backlog_id)
      g_object_unref (cmd->data.event.saqqara_backlog_id);

    if (cmd->data.event.event)
      sim_event_unref (cmd->data.event.event);
    break;

  case SIM_COMMAND_TYPE_SENSOR:
    g_free (cmd->data.sensor.host);
    g_free (cmd->data.sensor.servername);
    break;

  case SIM_COMMAND_TYPE_SENSOR_PLUGIN:
    g_free (cmd->data.sensor_plugin.sensor);
    g_free (cmd->data.sensor_plugin.servername);
    break;
  case SIM_COMMAND_TYPE_SENSOR_PLUGIN_START:
    g_free (cmd->data.sensor_plugin_start.sensor);
    g_free (cmd->data.sensor_plugin_start.servername);
    break;
  case SIM_COMMAND_TYPE_SENSOR_PLUGIN_STOP:
    g_free (cmd->data.sensor_plugin_stop.sensor);
    g_free (cmd->data.sensor_plugin_stop.servername);
    break;
  case SIM_COMMAND_TYPE_SENSOR_PLUGIN_ENABLE:
    g_free (cmd->data.sensor_plugin_enable.sensor);
    g_free (cmd->data.sensor_plugin_enable.servername);
    break;
  case SIM_COMMAND_TYPE_SENSOR_PLUGIN_DISABLE:
    g_free (cmd->data.sensor_plugin_disable.sensor);
    g_free (cmd->data.sensor_plugin_disable.servername);
    break;

  case SIM_COMMAND_TYPE_WATCH_RULE:
    g_free (cmd->data.watch_rule.str);
    break;

  case SIM_COMMAND_TYPE_HOST_OS_EVENT:
    g_free (cmd->data.host_os_event.date_str);
    g_free (cmd->data.host_os_event.host);
    g_free (cmd->data.host_os_event.os);
    g_free (cmd->data.host_os_event.sensor);
    g_free (cmd->data.host_os_event.interface);
    if (cmd->data.host_os_event.id)
      g_object_unref (cmd->data.host_os_event.id);
    break;

  case SIM_COMMAND_TYPE_HOST_MAC_EVENT:
    g_free (cmd->data.host_mac_event.date_str);
    g_free (cmd->data.host_mac_event.host);
    g_free (cmd->data.host_mac_event.mac);
    g_free (cmd->data.host_mac_event.vendor);
    g_free (cmd->data.host_mac_event.sensor);
    g_free (cmd->data.host_mac_event.interface);
    if (cmd->data.host_mac_event.id)
      g_object_unref (cmd->data.host_mac_event.id);
    break;

  case SIM_COMMAND_TYPE_HOST_SERVICE_EVENT:
    g_free (cmd->data.host_service_event.date_str);
    g_free (cmd->data.host_service_event.host);
    g_free (cmd->data.host_service_event.service);
    g_free (cmd->data.host_service_event.application);
    g_free (cmd->data.host_service_event.log);
    g_free (cmd->data.host_service_event.sensor);
    g_free (cmd->data.host_service_event.interface);
    if (cmd->data.host_service_event.id)
      g_object_unref (cmd->data.host_service_event.id);
    break;

  case SIM_COMMAND_TYPE_SENSOR_GET_FRAMEWORK:
    g_free(cmd->data.framework_data.framework_host);
    g_free(cmd->data.framework_data.framework_name);
    g_free(cmd->data.framework_data.server_host);
    g_free(cmd->data.framework_data.server_name);
    break;
  case SIM_COMMAND_TYPE_FRMK_GETDB:
    g_free(cmd->data.snort_database_data.dbhost);
    g_free(cmd->data.snort_database_data.dbname);
    g_free(cmd->data.snort_database_data.dbpassword);
    g_free(cmd->data.snort_database_data.dbuser);
    break;
  case SIM_COMMAND_TYPE_AGENT_PING:
    break;
  case SIM_COMMAND_TYPE_IDM_EVENT:
    if (cmd->data.idm_event.ip)
      g_object_unref(cmd->data.idm_event.ip);
    g_free(cmd->data.idm_event.username);
    g_free(cmd->data.idm_event.hostname);
    g_free(cmd->data.idm_event.domain);
    g_free(cmd->data.idm_event.mac);
    g_free(cmd->data.idm_event.os);
    g_free(cmd->data.idm_event.cpu);
    g_free(cmd->data.idm_event.video);
    g_free(cmd->data.idm_event.service);
    g_free(cmd->data.idm_event.software);
    g_free(cmd->data.idm_event.state);
    if (cmd->data.idm_event.host_id)
      g_object_unref (cmd->data.idm_event.host_id);
    break;
  case SIM_COMMAND_TYPE_RELOAD_POST_CORRELATION_SIDS:
    g_free (cmd->data.reload_post_correlation_sids.sids);
    break;
  case SIM_COMMAND_TYPE_NOACK:
    g_free (cmd->data.noack.your_sensor_id);
    break;

  default:
    break;
  }

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_command_class_init (SimCommandClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_ref (G_TYPE_OBJECT);

  object_class->finalize = sim_command_finalize;
}

static void
sim_command_instance_init (SimCommand *command)
{
  command->type = SIM_COMMAND_TYPE_NONE;
  command->buffer = NULL;
  command->signature = 0xbebecaca;
}

/* Public Methods */

/*
 *
 *
 *
 *
 */
SimCommand*
sim_command_new (void)
{
  SimCommand *command = NULL;

  command = SIM_COMMAND (g_object_new (SIM_TYPE_COMMAND, NULL));
	if (command)
		command->pf_event_scan = sim_command_event_scan_base64;

  return command;
}

/*
 *
 *
 *
 *
 */
SimCommand*
sim_command_new_from_buffer (const gchar *buffer, gboolean (*pf_scan)(SimCommand *, GScanner *), const gchar *session_ip_str)
{
  g_return_val_if_fail (buffer, NULL);

  SimCommand *command = NULL;

  command = SIM_COMMAND (g_object_new (SIM_TYPE_COMMAND, NULL));
  g_return_val_if_fail (command, NULL);
  ossim_debug ("New command created at address:%p", command);

	/* Check for current version */
	/* Default parsing functions, must be changed later*/
	command->pf_event_scan = sim_command_event_scan_base64;	

  if (!sim_command_scan (command, buffer, pf_scan, session_ip_str))
	{
		ossim_debug ( "sim_command_new_from_buffer: error scanning command");
		if (SIM_IS_COMMAND (command))
		{
			g_object_unref(command);
		}
		return NULL;
	}

	command->buffer = g_strdup (buffer); //store the original buffer to be able to resend it later without any overcharge
  return command;
}

/*
 *
 *
 *
 *
 */
SimCommand*
sim_command_new_from_type (SimCommandType  type)
{
  SimCommand *command = NULL;

  command = SIM_COMMAND (g_object_new (SIM_TYPE_COMMAND, NULL));
  command->type = type;

  return command;
}

/*
 *
 *
 *
 *
 */
void sim_command_append_inets(SimRadixNode *node, void *string)
{
  g_return_if_fail(node);

  if(node->user_data)
  {
    gchar   *ip;
    GString *str = (GString*) string;

    if(str->len > 0)
      str = g_string_append (str, SIM_DELIMITER_LIST);
      
    SimInet *inet = (SimInet *) node->user_data;
    ip = sim_inet_get_canonical_name (inet);
    str = g_string_append (str, ip);
    g_free (ip);
  }
}

SimCommand*
sim_command_new_from_rule (SimRule  *rule)
{
  SimCommand       *command;
  GString          *str   = NULL;
  GList            *list  = NULL;
  GString          * data = NULL; // For data fields that should be encoded.
  gint              interval;
  gboolean          absolute;
  SimConditionType  condition;
  gchar            *value;
  GHashTable       *table = NULL;
  GHashTableIter    iter;
  gpointer          hash_key, hash_value;
  gboolean          flag_first;
  SimNetwork       *network;

  g_return_val_if_fail (SIM_IS_RULE (rule), NULL);

  command = SIM_COMMAND (g_object_new (SIM_TYPE_COMMAND, NULL));
  command->type = SIM_COMMAND_TYPE_WATCH_RULE;

  str = g_string_new ("watch-rule ");

  /* Plugin ID */
  // TODO: with taxonomy we can have multiple plugin ids or ANY
  table = sim_rule_get_plugin_ids (rule);
  if (table)
  {
    g_hash_table_iter_init(&iter, table);
    g_hash_table_iter_next(&iter, &hash_key, &hash_value);

    g_string_append_printf (str, "plugin_id=\"%d\" ", GPOINTER_TO_INT(hash_key));
  }

  /* Plugin SID */
  table = sim_rule_get_plugin_sids (rule);
  if (table)
  {
	  g_hash_table_iter_init(&iter, table);
  	g_hash_table_iter_next(&iter, &hash_key, &hash_value);

    g_string_append_printf (str, "plugin_sid=\"%d\" ", GPOINTER_TO_INT(hash_key));
  }

  /* Condition */
  condition = sim_rule_get_condition (rule);
  if (condition != SIM_CONDITION_TYPE_NONE)
    {
      value = sim_condition_get_str_from_type (condition);
      g_string_append_printf (str, "condition=\"%s\" ", value);
      g_free (value);
    }

  /* Value */
  value = sim_rule_get_value (rule);
  if (value)
    {
      g_string_append_printf (str, "value=\"%s\" ", value);
    }

	/* Interval */
  interval = sim_rule_get_interval (rule);
  if (interval > 0)
    {
      g_string_append_printf (str, "interval=\"%d\" ", interval);
    }

  /* Absolute */
  absolute = sim_rule_get_absolute (rule);
	if (interval > 0)
	{
		if (absolute)
      str = g_string_append (str, "absolute=\"true\" ");
		else
      str = g_string_append (str, "absolute=\"false\" ");
	}
	else	//if interval is 0, that implies that absolute is true, as we don't have any time to compare with it. We only are able to
				//know when the "value" as been reached (ie. when somebody has reached 100 network packets), but it can spend as much time as it wants.
	  str = g_string_append (str, "absolute=\"true\" ");


  /* PORT FROM */
  table = sim_rule_get_src_ports (rule);
	if (table)
  {
		str = g_string_append (str, "port_from=\"");

		flag_first = TRUE;
		g_hash_table_iter_init(&iter, table);
  	while (g_hash_table_iter_next(&iter, &hash_key, &hash_value))
  	{
    	if(flag_first)
				flag_first = FALSE;
      else 
	      str = g_string_append (str, ",");
      g_string_append_printf (str, "%d", GPOINTER_TO_INT(hash_key));
    }
	str = g_string_append (str, "\" ");
  }

  /* PORT TO  */
  table = sim_rule_get_dst_ports (rule);
  if (table)
	{
    str = g_string_append (str, "port_to=\"");

		flag_first = TRUE;
		g_hash_table_iter_init(&iter, table);
  	while (g_hash_table_iter_next(&iter, &hash_key, &hash_value))
    {
    	if(flag_first)
				flag_first = FALSE;
			else
				str = g_string_append (str, ",");

      g_string_append_printf (str, "%d", GPOINTER_TO_INT(hash_key));
		}
	  str = g_string_append (str, "\" ");
  }

  /* SRC IAS */
  network = sim_rule_get_src_inets (rule);
  if (!sim_network_is_empty (network))
  {
    gchar *inet_list = sim_network_to_string (network);
    g_string_append_printf (str, "from=\"%s\" ", inet_list);
    g_free (inet_list);
  }

  /* DST IAS */
  network = sim_rule_get_dst_inets (rule);
  if (!sim_network_is_empty (network))
  {
    gchar *inet_list = sim_network_to_string (network);
    g_string_append_printf (str, "to=\"%s\" ", inet_list);
    g_free (inet_list);
  }

  /* Filename */
  list = sim_rule_get_generic (rule, SIM_RULE_VAR_FILENAME);
  if (list)
  {
    str = g_string_append (str, "filename=\"");
    data = g_string_new ("");
  }

  while (list)
  {
    gchar *s = (gchar *) list->data;

    data = g_string_append (data, s);

    if (list->next)
      data = g_string_append (data, ",");
    else
    {
      gint data_str_len = data->len;
      guchar * data_str = (guchar *)g_string_free (data, FALSE);
      gchar * data_b64_str = g_base64_encode (data_str, data_str_len);
      str = g_string_append (str, data_b64_str);
      str = g_string_append (str, "\" ");
      g_free (data_str);
      g_free (data_b64_str);
    }

    list = list->next;
  }

  /* Username */
  list = sim_rule_get_generic (rule, SIM_RULE_VAR_USERNAME);
  if (list)
  {
    str = g_string_append (str, "username=\"");
    data = g_string_new ("");
  }

  while (list)
  {
    gchar *s = (gchar *) list->data;

    data = g_string_append (data, s);

    if (list->next)
      data = g_string_append (data, ",");
    else
    {
      gint data_str_len = data->len;
      guchar * data_str = (guchar *)g_string_free (data, FALSE);
      gchar * data_b64_str = g_base64_encode (data_str, data_str_len);
      str = g_string_append (str, data_b64_str);
      str = g_string_append (str, "\" ");
      g_free (data_str);
      g_free (data_b64_str);
    }

    list = list->next;
  }

  /* password */
  list = sim_rule_get_generic (rule, SIM_RULE_VAR_PASSWORD);
  if (list)
  {
    str = g_string_append (str, "password=\"");
    data = g_string_new ("");
  }

  while (list)
  {
    gchar *s = (gchar *) list->data;

    data = g_string_append (data, s);

    if (list->next)
      data = g_string_append (data, ",");
    else
    {
      gint data_str_len = data->len;
      guchar * data_str = (guchar *)g_string_free (data, FALSE);
      gchar * data_b64_str = g_base64_encode (data_str, data_str_len);
      str = g_string_append (str, data_b64_str);
      str = g_string_append (str, "\" ");
      g_free (data_str);
      g_free (data_b64_str);
    }

    list = list->next;
  }

  /* Userdata1 */
  list = sim_rule_get_generic (rule, SIM_RULE_VAR_USERDATA1);
  if (list)
  {
    str = g_string_append (str, "userdata1=\"");
    data = g_string_new ("");
  }

  while (list)
  {
    gchar *s = (gchar *) list->data;

    data = g_string_append (data, s);

    if (list->next)
      data = g_string_append (data, ",");
    else
    {
      gint data_str_len = data->len;
      guchar * data_str = (guchar *)g_string_free (data, FALSE);
      gchar * data_b64_str = g_base64_encode (data_str, data_str_len);
      str = g_string_append (str, data_b64_str);
      str = g_string_append (str, "\" ");
      g_free (data_str);
      g_free (data_b64_str);
    }

    list = list->next;
  }

  /* Userdata2 */
  list = sim_rule_get_generic (rule, SIM_RULE_VAR_USERDATA2);
  if (list)
  {
    str = g_string_append (str, "userdata2=\"");
    data = g_string_new ("");
  }

  while (list)
  {
    gchar *s = (gchar *) list->data;

    data = g_string_append (data, s);

    if (list->next)
      data = g_string_append (data, ",");
    else
    {
      gint data_str_len = data->len;
      guchar * data_str = (guchar *)g_string_free (data, FALSE);
      gchar * data_b64_str = g_base64_encode (data_str, data_str_len);
      str = g_string_append (str, data_b64_str);
      str = g_string_append (str, "\" ");
      g_free (data_str);
      g_free (data_b64_str);
    }

    list = list->next;
  }

  /* Userdata3 */
  list = sim_rule_get_generic (rule, SIM_RULE_VAR_USERDATA3);
  if (list)
  {
    str = g_string_append (str, "userdata3=\"");
    data = g_string_new ("");
  }

  while (list)
  {
    gchar *s = (gchar *) list->data;

    data = g_string_append (data, s);

    if (list->next)
      data = g_string_append (data, ",");
    else
    {
      gint data_str_len = data->len;
      guchar * data_str = (guchar *)g_string_free (data, FALSE);
      gchar * data_b64_str = g_base64_encode (data_str, data_str_len);
      str = g_string_append (str, data_b64_str);
      str = g_string_append (str, "\" ");
      g_free (data_str);
      g_free (data_b64_str);
    }

    list = list->next;
  }

  /* Userdata4 */
  list = sim_rule_get_generic (rule, SIM_RULE_VAR_USERDATA4);
  if (list)
  {
    str = g_string_append (str, "userdata4=\"");
    data = g_string_new ("");
  }

  while (list)
  {
    gchar *s = (gchar *) list->data;

    data = g_string_append (data, s);

    if (list->next)
      data = g_string_append (data, ",");
    else
    {
      gint data_str_len = data->len;
      guchar * data_str = (guchar *)g_string_free (data, FALSE);
      gchar * data_b64_str = g_base64_encode (data_str, data_str_len);
      str = g_string_append (str, data_b64_str);
      str = g_string_append (str, "\" ");
      g_free (data_str);
      g_free (data_b64_str);
    }

    list = list->next;
  }

  /* Userdata5 */
  list = sim_rule_get_generic (rule, SIM_RULE_VAR_USERDATA5);
  if (list)
  {
    str = g_string_append (str, "userdata5=\"");
    data = g_string_new ("");
  }

  while (list)
  {
    gchar *s = (gchar *) list->data;

    data = g_string_append (data, s);

    if (list->next)
      data = g_string_append (data, ",");
    else
    {
      gint data_str_len = data->len;
      guchar * data_str = (guchar *)g_string_free (data, FALSE);
      gchar * data_b64_str = g_base64_encode (data_str, data_str_len);
      str = g_string_append (str, data_b64_str);
      str = g_string_append (str, "\" ");
      g_free (data_str);
      g_free (data_b64_str);
    }

    list = list->next;
  }

  /* Userdata6 */
  list = sim_rule_get_generic (rule, SIM_RULE_VAR_USERDATA6);
  if (list)
  {
    str = g_string_append (str, "userdata6=\"");
    data = g_string_new ("");
  }

  while (list)
  {
    gchar *s = (gchar *) list->data;

    data = g_string_append (data, s);

    if (list->next)
      data = g_string_append (data, ",");
    else
    {
      gint data_str_len = data->len;
      guchar * data_str = (guchar *)g_string_free (data, FALSE);
      gchar * data_b64_str = g_base64_encode (data_str, data_str_len);
      str = g_string_append (str, data_b64_str);
      str = g_string_append (str, "\" ");
      g_free (data_str);
      g_free (data_b64_str);
    }

    list = list->next;
  }

  /* Userdata7 */
  list = sim_rule_get_generic (rule, SIM_RULE_VAR_USERDATA7);
  if (list)
  {
    str = g_string_append (str, "userdata7=\"");
    data = g_string_new ("");
  }

  while (list)
  {
    gchar *s = (gchar *) list->data;

    data = g_string_append (data, s);

    if (list->next)
      data = g_string_append (data, ",");
    else
    {
      gint data_str_len = data->len;
      guchar * data_str = (guchar *)g_string_free (data, FALSE);
      gchar * data_b64_str = g_base64_encode (data_str, data_str_len);
      str = g_string_append (str, data_b64_str);
      str = g_string_append (str, "\" ");
      g_free (data_str);
      g_free (data_b64_str);
    }

    list = list->next;
  }

  /* Userdata8 */
  list = sim_rule_get_generic (rule, SIM_RULE_VAR_USERDATA8);
  if (list)
  {
    str = g_string_append (str, "userdata8=\"");
    data = g_string_new ("");
  }

  while (list)
  {
    gchar *s = (gchar *) list->data;

    data = g_string_append (data, s);

    if (list->next)
      data = g_string_append (data, ",");
    else
    {
      gint data_str_len = data->len;
      guchar * data_str = (guchar *)g_string_free (data, FALSE);
      gchar * data_b64_str = g_base64_encode (data_str, data_str_len);
      str = g_string_append (str, data_b64_str);
      str = g_string_append (str, "\" ");
      g_free (data_str);
      g_free (data_b64_str);
    }

    list = list->next;
  }

  /* Userdata9 */
  list = sim_rule_get_generic (rule, SIM_RULE_VAR_USERDATA9);
  if (list)
  {
    str = g_string_append (str, "userdata9=\"");
    data = g_string_new ("");
  }

  while (list)
  {
    gchar *s = (gchar *) list->data;

    data = g_string_append (data, s);

    if (list->next)
      data = g_string_append (data, ",");
    else
    {
      gint data_str_len = data->len;
      guchar * data_str = (guchar *)g_string_free (data, FALSE);
      gchar * data_b64_str = g_base64_encode (data_str, data_str_len);
      str = g_string_append (str, data_b64_str);
      str = g_string_append (str, "\" ");
      g_free (data_str);
      g_free (data_b64_str);
    }

    list = list->next;
  }

  str = g_string_append (str, "\n");

  command->data.watch_rule.str = g_string_free (str, FALSE); //free the GString object and returns the string

  return command;
}

GScanner *
sim_command_start_scanner(void)
{
  GScanner *scanner =  NULL;
  guint         i;

  /* Create scanner */
  scanner = g_scanner_new (NULL);

  /* Config scanner */
  scanner->config->cset_identifier_nth = (G_CSET_a_2_z ":._-0123456789" G_CSET_A_2_Z);
  scanner->config->case_sensitive = TRUE;
  scanner->config->symbol_2_token = TRUE;

  /* Added command symbols */
  for (i = 0; i < G_N_ELEMENTS (command_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_COMMAND, command_symbols[i].name, GINT_TO_POINTER (command_symbols[i].token));
  
  /* Added connect symbols */
  for (i = 0; i < G_N_ELEMENTS (connect_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_CONNECT, connect_symbols[i].name, GINT_TO_POINTER (connect_symbols[i].token));

  /* Added append plugin symbols */
  for (i = 0; i < G_N_ELEMENTS (session_append_plugin_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_SESSION_APPEND_PLUGIN, session_append_plugin_symbols[i].name, GINT_TO_POINTER (session_append_plugin_symbols[i].token));

  /* Added remove plugin symbols */
  for (i = 0; i < G_N_ELEMENTS (session_remove_plugin_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_SESSION_REMOVE_PLUGIN, session_remove_plugin_symbols[i].name, GINT_TO_POINTER (session_remove_plugin_symbols[i].token));

  /* Added server get sensors symbols */
  for (i = 0; i < G_N_ELEMENTS (server_get_sensors_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_SERVER_GET_SENSORS, server_get_sensors_symbols[i].name, GINT_TO_POINTER (server_get_sensors_symbols[i].token));

	/* Added sensor symbols */
  for (i = 0; i < G_N_ELEMENTS (sensor_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_SENSOR, sensor_symbols[i].name, GINT_TO_POINTER (sensor_symbols[i].token));

	/* Added server symbols */
  for (i = 0; i < G_N_ELEMENTS (server_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_SERVER, server_symbols[i].name, GINT_TO_POINTER (server_symbols[i].token));

  /* Added server get servers symbols */
  for (i = 0; i < G_N_ELEMENTS (server_get_servers_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_SERVER_GET_SERVERS, server_get_servers_symbols[i].name, GINT_TO_POINTER (server_get_servers_symbols[i].token));

  /* Added server get sensor plugins symbols */
  for (i = 0; i < G_N_ELEMENTS (server_get_sensor_plugins_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_SERVER_GET_SENSOR_PLUGINS, server_get_sensor_plugins_symbols[i].name, GINT_TO_POINTER (server_get_sensor_plugins_symbols[i].token));

  /* Added server set Data role symbols. Role is the role of each server ( */
  for (i = 0; i < G_N_ELEMENTS (server_set_data_role_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_SERVER_SET_DATA_ROLE, server_set_data_role_symbols[i].name, GINT_TO_POINTER (server_set_data_role_symbols[i].token));

  /* Added sensor plugin symbols */
  for (i = 0; i < G_N_ELEMENTS (sensor_plugin_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_SENSOR_PLUGIN, sensor_plugin_symbols[i].name, GINT_TO_POINTER (sensor_plugin_symbols[i].token));

  /* Added sensor plugin start symbols */
  for (i = 0; i < G_N_ELEMENTS (sensor_plugin_start_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_SENSOR_PLUGIN_START, sensor_plugin_start_symbols[i].name, GINT_TO_POINTER (sensor_plugin_start_symbols[i].token));

  /* Added sensor plugin stop symbols */
  for (i = 0; i < G_N_ELEMENTS (sensor_plugin_stop_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_SENSOR_PLUGIN_STOP, sensor_plugin_stop_symbols[i].name, GINT_TO_POINTER (sensor_plugin_stop_symbols[i].token));

  /* Added sensor plugin enabled symbols */
  for (i = 0; i < G_N_ELEMENTS (sensor_plugin_enable_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_SENSOR_PLUGIN_ENABLE, sensor_plugin_enable_symbols[i].name, GINT_TO_POINTER (sensor_plugin_enable_symbols[i].token));

  /* Added sensor plugin disabled symbols */
  for (i = 0; i < G_N_ELEMENTS (sensor_plugin_disable_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_SENSOR_PLUGIN_DISABLE, sensor_plugin_disable_symbols[i].name, GINT_TO_POINTER (sensor_plugin_disable_symbols[i].token));

/* Added sensor get events (a message from the sensor to the server asking how many events has it received from this session) symbols */
  for (i = 0; i < G_N_ELEMENTS (sensor_get_events_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_SENSOR_GET_EVENTS, sensor_get_events_symbols[i].name, GINT_TO_POINTER (sensor_get_events_symbols[i].token));

  /* Added plugin start symbols */
  for (i = 0; i < G_N_ELEMENTS (plugin_state_started_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_PLUGIN_STATE_STARTED, plugin_state_started_symbols[i].name, GINT_TO_POINTER (plugin_state_started_symbols[i].token));

  /* Added plugin unknown symbols */
  for (i = 0; i < G_N_ELEMENTS (plugin_state_unknown_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_PLUGIN_STATE_UNKNOWN, plugin_state_unknown_symbols[i].name, GINT_TO_POINTER (plugin_state_unknown_symbols[i].token));

  /* Added plugin stop symbols */
  for (i = 0; i < G_N_ELEMENTS (plugin_state_stopped_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_PLUGIN_STATE_STOPPED, plugin_state_stopped_symbols[i].name, GINT_TO_POINTER (plugin_state_stopped_symbols[i].token));

  /* Added plugin enabled symbols */
  for (i = 0; i < G_N_ELEMENTS (plugin_enabled_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_PLUGIN_ENABLED, plugin_enabled_symbols[i].name, GINT_TO_POINTER (plugin_enabled_symbols[i].token));

  /* Added plugin disabled symbols */
  for (i = 0; i < G_N_ELEMENTS (plugin_disabled_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_PLUGIN_DISABLED, plugin_disabled_symbols[i].name, GINT_TO_POINTER (plugin_disabled_symbols[i].token));

  /* Added event symbols */
  for (i = 0; i < G_N_ELEMENTS (event_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_EVENT, event_symbols[i].name, GINT_TO_POINTER (event_symbols[i].token));
  
  /* Added reload plugins symbols */
  for (i = 0; i < G_N_ELEMENTS (reload_plugins_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_RELOAD_PLUGINS, reload_plugins_symbols[i].name, GINT_TO_POINTER (reload_plugins_symbols[i].token));

  /* Added reload sensors symbols */
  for (i = 0; i < G_N_ELEMENTS (reload_sensors_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_RELOAD_SENSORS, reload_sensors_symbols[i].name, GINT_TO_POINTER (reload_sensors_symbols[i].token));

  /* Added reload hosts symbols */
  for (i = 0; i < G_N_ELEMENTS (reload_hosts_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_RELOAD_HOSTS, reload_hosts_symbols[i].name, GINT_TO_POINTER (reload_hosts_symbols[i].token));

  /* Added reload nets symbols */
  for (i = 0; i < G_N_ELEMENTS (reload_nets_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_RELOAD_NETS, reload_nets_symbols[i].name, GINT_TO_POINTER (reload_nets_symbols[i].token));

  /* Added reload policies symbols */
  for (i = 0; i < G_N_ELEMENTS (reload_policies_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_RELOAD_POLICIES, reload_policies_symbols[i].name, GINT_TO_POINTER (reload_policies_symbols[i].token));

  /* Added reload directives symbols */
  for (i = 0; i < G_N_ELEMENTS (reload_directives_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_RELOAD_DIRECTIVES, reload_directives_symbols[i].name, GINT_TO_POINTER (reload_directives_symbols[i].token));

  /* Added reload servers symbols */
  for (i = 0; i < G_N_ELEMENTS (reload_servers_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_RELOAD_SERVERS, reload_servers_symbols[i].name, GINT_TO_POINTER (reload_servers_symbols[i].token));

  /* Added reload all symbols */
  for (i = 0; i < G_N_ELEMENTS (reload_all_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_RELOAD_ALL, reload_all_symbols[i].name, GINT_TO_POINTER (reload_all_symbols[i].token));

  /* Added reload post correlation symbols */
  for (i = 0; i < G_N_ELEMENTS (reload_post_correlation_sids_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_RELOAD_POST_CORRELATION_SIDS, reload_post_correlation_sids_symbols[i].name, GINT_TO_POINTER (reload_post_correlation_sids_symbols[i].token));

  /* Added host os event symbols */
  for (i = 0; i < G_N_ELEMENTS (host_os_event_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_HOST_OS_EVENT, host_os_event_symbols[i].name, GINT_TO_POINTER (host_os_event_symbols[i].token));

  /* Added host mac event symbols */
  for (i = 0; i < G_N_ELEMENTS (host_mac_event_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_HOST_MAC_EVENT, host_mac_event_symbols[i].name, GINT_TO_POINTER (host_mac_event_symbols[i].token));

  /* Add host service event symbols */
  for (i = 0; i < G_N_ELEMENTS (host_service_event_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_HOST_SERVICE_EVENT, host_service_event_symbols[i].name, GINT_TO_POINTER (host_service_event_symbols[i].token));

  /* Add OK symbols */
  for (i = 0; i < G_N_ELEMENTS (ok_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_OK, ok_symbols[i].name, GINT_TO_POINTER (ok_symbols[i].token));

  /* Add ERROR symbols */
  for (i = 0; i < G_N_ELEMENTS (error_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_ERROR, error_symbols[i].name, GINT_TO_POINTER (error_symbols[i].token));

	/* Add Database Query symbols (remote DB) */
  for (i = 0; i < G_N_ELEMENTS (database_query_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_DATABASE_QUERY, database_query_symbols[i].name, GINT_TO_POINTER (database_query_symbols[i].token));

	/* Add Database Answer symbols (remote DB) */
  for (i = 0; i < G_N_ELEMENTS (database_answer_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_DATABASE_ANSWER, database_answer_symbols[i].name, GINT_TO_POINTER (database_answer_symbols[i].token));
  /* Add snort event symbols */
  for (i = 0; i < G_N_ELEMENTS (snort_event_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_SNORT_EVENT, snort_event_symbols[i].name, GINT_TO_POINTER (snort_event_symbols[i].token));	
		/* Add snort data symbols*/
for (i = 0; i < G_N_ELEMENTS (snort_event_data_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_SNORT_EVENT_DATA, snort_event_data_symbols[i].name, GINT_TO_POINTER (snort_event_data_symbols[i].token));	
/* Add raw  symbools */
	for (i = 0; i < G_N_ELEMENTS (snort_event_packet_raw_symbols);i++)
		g_scanner_scope_add_symbol (scanner,SIM_COMMAND_SCOPE_SNORT_EVENT_PACKET_RAW, snort_event_packet_raw_symbols[i].name, GINT_TO_POINTER (snort_event_packet_raw_symbols[i].token));

    /* Add agent-date symbols*/
  for (i = 0; i < G_N_ELEMENTS (agent_date_symbols); i++)
    g_scanner_scope_add_symbol (scanner, SIM_COMMAND_SCOPE_AGENT_DATE, agent_date_symbols[i].name, GINT_TO_POINTER (agent_date_symbols[i].token));

	/* Add ip symbools */
	for (i = 0; i < G_N_ELEMENTS (snort_event_packet_ip_symbols);i++)
		g_scanner_scope_add_symbol (scanner,SIM_COMMAND_SCOPE_SNORT_EVENT_IP, snort_event_packet_ip_symbols[i].name, GINT_TO_POINTER (snort_event_packet_ip_symbols[i].token));
	/* Add icmp symbols */
	for (i = 0; i < G_N_ELEMENTS (snort_event_packet_icmp_symbols); i++)
		g_scanner_scope_add_symbol (scanner,SIM_COMMAND_SCOPE_SNORT_EVENT_ICMP,snort_event_packet_icmp_symbols[i].name, GINT_TO_POINTER (snort_event_packet_icmp_symbols[i].token));
	/* Add udp symbols */
	for (i = 0; i < G_N_ELEMENTS (snort_event_packet_udp_symbols); i++)
		g_scanner_scope_add_symbol (scanner,SIM_COMMAND_SCOPE_SNORT_EVENT_UDP,snort_event_packet_udp_symbols[i].name, GINT_TO_POINTER (snort_event_packet_udp_symbols[i].token));
	/* Add tcp symbols */
	for (i = 0; i < G_N_ELEMENTS (snort_event_packet_tcp_symbols);i++)
		g_scanner_scope_add_symbol (scanner,SIM_COMMAND_SCOPE_SNORT_EVENT_TCP,snort_event_packet_tcp_symbols[i].name, GINT_TO_POINTER (snort_event_packet_tcp_symbols[i].token));
	/* Add snort data symbols */
	for (i = 0; i < G_N_ELEMENTS (snort_event_data_symbols);i++)
		g_scanner_scope_add_symbol (scanner,SIM_COMMAND_SCOPE_SNORT_EVENT_TCP,snort_event_data_symbols[i].name, GINT_TO_POINTER (	snort_event_data_symbols[i].token));

	for (i = 0; i< G_N_ELEMENTS (backlog_event_symbols);i++)
		g_scanner_scope_add_symbol (scanner,SIM_COMMAND_SCOPE_BACKLOG,backlog_event_symbols[i].name, GINT_TO_POINTER(backlog_event_symbols[i].token));

  /* Add idm symbols */
  for (i = 0; i < G_N_ELEMENTS (idm_event_symbols);i++)
    g_scanner_scope_add_symbol (scanner,SIM_COMMAND_SCOPE_IDM_EVENT,idm_event_symbols[i].name, GINT_TO_POINTER (idm_event_symbols[i].token));

  /* Add idm reload context symbols */
  for (i = 0; i < G_N_ELEMENTS (idm_reload_context_symbols);i++)
    g_scanner_scope_add_symbol (scanner,SIM_COMMAND_SCOPE_IDM_RELOAD_CONTEXT,idm_reload_context_symbols[i].name, GINT_TO_POINTER (idm_reload_context_symbols[i].token));

	return scanner;

}


/*
 *
 * If the command analyzed has some field incorrect, the command will be rejected.
 * The 'command' parameter is filled inside this function and not returned, outside
 * this function you'll be able to access to it directly.
 */
static gboolean
sim_command_scan (SimCommand    *command,
								  const gchar   *buffer,
                  gboolean (*pf_scan)(SimCommand *, GScanner *),
									const gchar		*session_ip_str)
{
  GScanner    *scanner;
	gboolean OK=TRUE; //if a problem appears in the command scanning, we'll return.

  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (buffer != NULL, FALSE);
	if ((scanner = (GScanner*)g_private_get(privScanner))==NULL){
	                   scanner = sim_command_start_scanner();
	                   g_private_set(privScanner,scanner);
	          ossim_debug("Scanner: %p, thread: %p",scanner,g_thread_self ());
	}
	 
	gchar * session_ip_str_dup;

  /* TODO: don't make a dup and remove all g_free's in command_scan functions
   * Yes, this is non sense but i prefer to make a dup than removing all the g_free's */
  session_ip_str_dup = g_strdup (session_ip_str);

  /* Sets input text */
  g_scanner_input_text (scanner, buffer, strlen (buffer));
  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_COMMAND);
  do
  {
    g_scanner_get_next_token (scanner);
 
    switch ((SimCommandSymbolType) scanner->token)
    {
	    case SIM_COMMAND_SYMBOL_CONNECT:
					  if (!sim_command_connect_scan (command, scanner,session_ip_str_dup))
							OK=FALSE;
        	  break;

			/*Commands from frameworkd OR Master servers */

			case SIM_COMMAND_SYMBOL_SERVER_GET_SENSORS:
					  if (!sim_command_server_get_sensors_scan (command, scanner,session_ip_str_dup))
							OK=FALSE;
	          break;
			case SIM_COMMAND_SYMBOL_SERVER_GET_SERVERS:
					  if (!sim_command_server_get_servers_scan (command, scanner,session_ip_str_dup))
							OK=FALSE;
	          break;
      case SIM_COMMAND_SYMBOL_SERVER_GET_SENSOR_PLUGINS:
					  if (!sim_command_server_get_sensor_plugins_scan (command, scanner,session_ip_str_dup))
							OK=FALSE;
          break;
		  case SIM_COMMAND_SYMBOL_SERVER_SET_DATA_ROLE:
					  if (!sim_command_server_set_data_role_scan (command, scanner,session_ip_str_dup))
							OK=FALSE;
	          break;
      case SIM_COMMAND_SYMBOL_SENSOR_PLUGIN: //answer to SIM_COMMAND_SYMBOL_SERVER_GET_SENSOR_PLUGINS
					  if (!sim_command_sensor_plugin_scan (command, scanner,session_ip_str_dup))
							OK=FALSE;
          	break;
	    case SIM_COMMAND_SYMBOL_SENSOR_PLUGIN_START:
					  if (!sim_command_sensor_plugin_start_scan (command, scanner,session_ip_str_dup))
							OK=FALSE;
          	break;
      case SIM_COMMAND_SYMBOL_SENSOR_PLUGIN_STOP:
					  if (!sim_command_sensor_plugin_stop_scan (command, scanner,session_ip_str_dup))
							OK=FALSE;
	          break;
      case SIM_COMMAND_SYMBOL_SENSOR_PLUGIN_ENABLE:
					  if (!sim_command_sensor_plugin_enable_scan (command, scanner,session_ip_str_dup))
							OK=FALSE;
          	break;
      case SIM_COMMAND_SYMBOL_SENSOR_PLUGIN_DISABLE:
					  if (!sim_command_sensor_plugin_disable_scan (command, scanner,session_ip_str_dup))
							OK=FALSE;
        	  break;
      case SIM_COMMAND_SYMBOL_RELOAD_PLUGINS:
					  if (!sim_command_reload_plugins_scan (command, scanner,session_ip_str_dup))
							OK=FALSE;
        	  break;
      case SIM_COMMAND_SYMBOL_RELOAD_SENSORS:
					  if (!sim_command_reload_sensors_scan (command, scanner,session_ip_str_dup))
							OK=FALSE;
        	  break;
			case SIM_COMMAND_SYMBOL_RELOAD_HOSTS:
					  if (!sim_command_reload_hosts_scan (command, scanner,session_ip_str_dup))
							OK=FALSE;
        	  break;
      case SIM_COMMAND_SYMBOL_RELOAD_NETS:
					  if (!sim_command_reload_nets_scan (command, scanner,session_ip_str_dup))
							OK=FALSE;
        	  break;
      case SIM_COMMAND_SYMBOL_RELOAD_POLICIES:
					  if (!sim_command_reload_policies_scan (command, scanner,session_ip_str_dup))
							OK=FALSE;
        	  break;
      case SIM_COMMAND_SYMBOL_RELOAD_DIRECTIVES:
					  if (!sim_command_reload_directives_scan (command, scanner,session_ip_str_dup))
							OK=FALSE;
        	  break;
      case SIM_COMMAND_SYMBOL_RELOAD_SERVERS:
            if (!sim_command_reload_servers_scan (command, scanner, session_ip_str_dup))
              OK = FALSE;
		        break;
      case SIM_COMMAND_SYMBOL_RELOAD_ALL:
					  if (!sim_command_reload_all_scan (command, scanner,session_ip_str_dup))
							OK=FALSE;
		        break;
	    case SIM_COMMAND_SYMBOL_RELOAD_POST_CORRELATION_SIDS:
			      if (!sim_command_reload_post_correlation_sids (command, scanner,session_ip_str_dup))
			        OK=FALSE;
      			break;
 
			/*Commands from Sensors*/
			
			case SIM_COMMAND_SYMBOL_SESSION_APPEND_PLUGIN:
					  if (!sim_command_session_append_plugin_scan (command, scanner,session_ip_str_dup))
							OK=FALSE;
        	  break;
      case SIM_COMMAND_SYMBOL_SESSION_REMOVE_PLUGIN:
					  if (!sim_command_session_remove_plugin_scan (command, scanner,session_ip_str_dup))
							OK=FALSE;
        	  break;
		  case SIM_COMMAND_SYMBOL_PLUGIN_STATE_STARTED:
					  if (!sim_command_plugin_state_started_scan (command, scanner,session_ip_str_dup))
							OK=FALSE;
        	  break;
      case SIM_COMMAND_SYMBOL_PLUGIN_STATE_UNKNOWN:
					  if (!sim_command_plugin_state_unknown_scan (command, scanner,session_ip_str_dup))
							OK=FALSE;
			      break;
      case SIM_COMMAND_SYMBOL_PLUGIN_STATE_STOPPED:
					  if (!sim_command_plugin_state_stopped_scan (command, scanner,session_ip_str_dup))
							OK=FALSE;
		        break;
      case SIM_COMMAND_SYMBOL_PLUGIN_ENABLED:
					  if (!sim_command_plugin_enabled_scan (command, scanner,session_ip_str_dup))
							OK=FALSE;
        	  break;
      case SIM_COMMAND_SYMBOL_PLUGIN_DISABLED:
					  if (!sim_command_plugin_disabled_scan (command, scanner,session_ip_str_dup))
							OK=FALSE;
        	  break;
	    case SIM_COMMAND_SYMBOL_SENSOR_GET_EVENTS: //May be that this is called also from framework or master servers in a future?
					  if (!sim_command_sensor_get_events_scan (command, scanner,session_ip_str_dup))
							OK=FALSE;
          	break;

			/*Commands from sensors or Children Servers*/
						
      case SIM_COMMAND_SYMBOL_EVENT:
						if(pf_scan == NULL)
						{
              pf_scan = sim_command_get_default_scan();
							ossim_debug ("%s: pf_scan function is NULL using default function", __func__);
						}
						if (!pf_scan(command, scanner))
							OK = FALSE;
						break;
      case SIM_COMMAND_SYMBOL_HOST_OS_EVENT:
					  if (!sim_command_host_os_event_scan (command, scanner,session_ip_str_dup))
							OK=FALSE;
        	  break;
      case SIM_COMMAND_SYMBOL_HOST_MAC_EVENT:
					  if (!sim_command_host_mac_event_scan (command, scanner,session_ip_str_dup))
							OK=FALSE;
        	  break;
      case SIM_COMMAND_SYMBOL_HOST_SERVICE_EVENT:
					  if (!sim_command_host_service_event_scan (command, scanner,session_ip_str_dup))
							OK=FALSE;
	          break;
			case SIM_COMMAND_SYMBOL_BACKLOG:
						if (!sim_command_backlog_event_scan(command,scanner,session_ip_str_dup))
							OK = FALSE;
						break;
		
    /*Commands from Children Servers; answer to a previous query from this (or an upper) server */
    case SIM_COMMAND_SYMBOL_SENSOR: //answer to SIM_COMMAND_SYMBOL_SERVER_GET_SENSORS query made in this server to a children server.
					  if (!sim_command_sensor_scan (command, scanner,session_ip_str_dup))
							OK=FALSE;
	          break;
	  case SIM_COMMAND_SYMBOL_SERVER: //answer to SIM_COMMAND_SYMBOL_SERVER_GET_SERVERS query made in this server to a children server.
					  if (!sim_command_server_scan (command, scanner,session_ip_str_dup))
							OK=FALSE;
	          break;
	
      case SIM_COMMAND_SYMBOL_OK:
						if (!sim_command_ok_scan (command, scanner,session_ip_str_dup))
							OK = FALSE;
	          break;
      case SIM_COMMAND_SYMBOL_PONG:
            if (!sim_command_pong_scan (command, scanner, session_ip_str_dup))
              OK = FALSE;
            break;
      case SIM_COMMAND_SYMBOL_ERROR:
            if (!sim_command_error_scan (command, scanner, session_ip_str_dup))
              OK = FALSE;
	          break;
      case SIM_COMMAND_SYMBOL_DATABASE_QUERY:
            // DEPRECATED - kept for backward compatibility
            if (!sim_command_database_query_scan (command, scanner,session_ip_str_dup))
              OK=FALSE;
            break;
      case SIM_COMMAND_SYMBOL_DATABASE_ANSWER:
            // DEPRECATED - kept for backward compatibility
            if (!sim_command_database_answer_scan (command, scanner,session_ip_str_dup))
              OK=FALSE;
            break;
			case SIM_COMMAND_SYMBOL_SNORT_EVENT:
			     if (!sim_command_snort_event_scan(command,scanner,session_ip_str_dup))
					    OK=FALSE;
			     if(session_ip_str_dup)
			    	 g_free(session_ip_str_dup);
					 return OK; /* all the process is in sim_command_snort_event_scan */
					 break;
      case SIM_COMMAND_SYMBOL_AGENT_DATE:
           if (!sim_command_agent_date_scan(command,scanner,session_ip_str_dup))
              OK=FALSE;
           break;
      case SIM_COMMAND_SYMBOL_FRMK_GETDB://web request or database information
          if (!sim_command_frmk_getdb_scan(command,scanner,session_ip_str_dup))
            {
              OK =FALSE;
            }
        break;
      case SIM_COMMAND_SYMBOL_SENSOR_GET_FRAMEWORK://agent request to get framework connection data
          if(!sim_command_sensor_getframeworkconnexion_scan(command,scanner,session_ip_str_dup))
            {
              OK = FALSE;
            }
        break;
      case SIM_COMMAND_SYMBOL_SENSOR_PING:
        if(!sim_command_sensor_ping_scan(command,scanner,session_ip_str_dup))
        {
          OK = FALSE;
        }
        break;
      case SIM_COMMAND_SYMBOL_IDM_EVENT:
        if(!sim_command_idm_event_scan(command,scanner,session_ip_str_dup))
          OK = FALSE;
        break;
      default:
					  if (scanner->token == G_TOKEN_EOF)
					    break;
						ossim_debug ( "sim_command_scan: error command unknown; Buffer from command: [%s]",buffer);
				    if(session_ip_str_dup)
				    	g_free(session_ip_str_dup);
						return FALSE;
    }
  }
  while(scanner->token != G_TOKEN_EOF);
	if(session_ip_str_dup)
		g_free(session_ip_str_dup);

//  g_scanner_destroy (scanner);
	return OK; //well... ok... or not!
}

/*
 *
 *
 *
 */
static gboolean
sim_command_connect_scan (SimCommand    *command,
												  GScanner      *scanner,
												  gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner != NULL, FALSE);

  gsize base64len;
  gchar *aux;

  command->type = SIM_COMMAND_TYPE_CONNECT;
  command->data.connect.username = NULL;
  command->data.connect.password = NULL;
  command->data.connect.hostname = NULL;
  command->data.connect.sensor_ver = NULL;
  command->data.connect.type = SIM_SESSION_TYPE_NONE;
  command->data.connect.tzone = 0;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_CONNECT);
  do
  {
    g_scanner_get_next_token (scanner);

    switch ((SimCommandSymbolType) scanner->token)
    {
	    case SIM_COMMAND_SYMBOL_ID:
					  g_scanner_get_next_token (scanner); /* = */
					  g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
	
            if (sim_string_is_number (scanner->value.v_string, 0))
              command->id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: connect event incorrect. Please check the symbol_id issued from the agent:-> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
	
						break;
						
			case SIM_COMMAND_SYMBOL_USERNAME:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
            command->data.connect.username = (gchar*) g_base64_decode (scanner->value.v_string, &base64len);
            aux = sim_util_substite_problematic_chars (command->data.connect.username, base64len);
            if (aux)
            {
              g_free (command->data.connect.username);
              command->data.connect.username = aux;
            }


						break;
						
			case SIM_COMMAND_SYMBOL_PASSWORD:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
						command->data.connect.password = (gchar*) g_base64_decode (scanner->value.v_string, &base64len);
            aux = sim_util_substite_problematic_chars (command->data.connect.password, base64len);
            if (aux)
            {
              g_free (command->data.connect.password);
              command->data.connect.password = aux;
            }

						break;
						
			case SIM_COMMAND_SYMBOL_HOSTNAME:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
						command->data.connect.hostname = g_strdup (scanner->value.v_string);
						break;
						
			case SIM_COMMAND_SYMBOL_TYPE:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
				
						if (!g_ascii_strcasecmp (scanner->value.v_string, "SERVER")) 
						{
							command->data.connect.type = SIM_SESSION_TYPE_SERVER_DOWN;
						}
						else 
						if (!g_ascii_strcasecmp (scanner->value.v_string, "SENSOR")) 
						{
							command->data.connect.type = SIM_SESSION_TYPE_SENSOR;
						}
						else
						if (!g_ascii_strcasecmp (scanner->value.v_string, "WEB")) 
						{
							command->data.connect.type = SIM_SESSION_TYPE_WEB;
						}
						else
						if (!g_ascii_strcasecmp (scanner->value.v_string, "FRAMEWORKD"))
						{
							command->data.connect.type = SIM_SESSION_TYPE_FRAMEWORKD;
						}
						else
						{
							command->data.connect.type = SIM_SESSION_TYPE_NONE;
						}
						
						break;

				case SIM_COMMAND_SYMBOL_AGENT_VERSION:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;

            command->data.connect.sensor_ver = g_new0 (SimVersion, 1);
						sim_version_parse (scanner->value.v_string,
                               &(command->data.connect.sensor_ver->major),
                               &(command->data.connect.sensor_ver->minor),
                               &(command->data.connect.sensor_ver->micro));
						break;


				case SIM_COMMAND_SYMBOL_SENSOR_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;

                        if (!(command->data.connect.sensor_id = sim_uuid_new_from_string (scanner->value.v_string)))
                        {
                          g_message("Error: connect event incorrect. Please check the sensor id issued from the agent: %s", scanner->value.v_string);
                          return FALSE;
                        }

						break;


				case SIM_COMMAND_SYMBOL_AGENT_TZONE:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;

						if (sim_string_is_number (scanner->value.v_string, TRUE))
							command->data.connect.tzone = g_ascii_strtod(scanner->value.v_string, (gchar**)NULL);
						else
							g_message("Error: Please check the tzone value: %s. Assumed tzone = 0. Session ip: %s", scanner->value.v_string,session_ip_str);
						break;
	
						
			default:
						if (scanner->token == G_TOKEN_EOF)
					    break;
					  ossim_debug ( "sim_command_connect_scan: error symbol unknown. Session ip: %s",session_ip_str);
						return FALSE;
    }
  }
  while(scanner->token != G_TOKEN_EOF);

	return TRUE;
}
/*
 * Scan method to retraive frmk get db arguments.
 * @return True if all ok or False is there is a problem
 */
static gboolean sim_command_frmk_getdb_scan
                                (SimCommand *command, GScanner *scanner,gchar* session_ip_str)
{
  g_return_val_if_fail(SIM_IS_COMMAND(command), FALSE);
  g_return_val_if_fail(scanner!=NULL, FALSE);

  // unused parameter
  (void) session_ip_str;

  command->type = SIM_COMMAND_TYPE_FRMK_GETDB;
  //Nothing to scan, the command has no arguments
  return TRUE;

}
/*
 * Scan function for scan server-get framework connection data
 * @return True if all ok or False is there is a problem
 */
static gboolean
sim_command_sensor_getframeworkconnexion_scan(SimCommand* command, GScanner *scanner,gchar* session_ip_str)
{
  g_return_val_if_fail(SIM_IS_COMMAND(command), FALSE);
  g_return_val_if_fail(scanner != NULL, FALSE);

  // unused parameter
  (void) session_ip_str;

  command->type = SIM_COMMAND_TYPE_SENSOR_GET_FRAMEWORK;
  //Nothing to scan, the command has no arguments
  return TRUE;

}
/*
 * Scan function for scan server-get framework connection data
 * @return True if all ok or False is there is a problem
 */
static gboolean
sim_command_sensor_ping_scan(SimCommand* command, GScanner *scanner,gchar* session_ip_str)
{
  g_return_val_if_fail(command != NULL, FALSE);
  g_return_val_if_fail(SIM_IS_COMMAND(command), FALSE);
  g_return_val_if_fail(scanner != NULL, FALSE);

  // unused parameter
  (void) session_ip_str;

  command->type = SIM_COMMAND_TYPE_AGENT_PING;
  //Nothing to scan, the command has no arguments
  return TRUE;
}
/*
 *
 *
 *
 */
static gboolean
sim_command_session_append_plugin_scan (SimCommand    *command,
																				GScanner      *scanner,
																				gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner != NULL, FALSE);

  command->type = SIM_COMMAND_TYPE_SESSION_APPEND_PLUGIN;
  command->data.session_append_plugin.id = 0;
  command->data.session_append_plugin.type = SIM_PLUGIN_TYPE_NONE;
  command->data.session_append_plugin.name = NULL;
  command->data.session_append_plugin.state = 0;
  command->data.session_append_plugin.enabled = FALSE;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_SESSION_APPEND_PLUGIN);
  do
  {
    g_scanner_get_next_token (scanner);
 
    switch ((SimCommandSymbolType) scanner->token)
    {
			case SIM_COMMAND_SYMBOL_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
						break;

            if (sim_string_is_number (scanner->value.v_string, 0))
              command->id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: append plugin event incorrect. Please check the id issued from the agent-> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;
			case SIM_COMMAND_SYMBOL_PLUGIN_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
            if (sim_string_is_number (scanner->value.v_string, 0))
							command->data.session_append_plugin.id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: append plugin event incorrect. Please check the plugin_id issued from the agent-> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }

						break;
			case SIM_COMMAND_SYMBOL_TYPE:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;

            if (sim_string_is_number (scanner->value.v_string, 0))
              command->data.session_append_plugin.type = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: append plugin event incorrect. Please check the type issued from the agent-> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;
						
			case SIM_COMMAND_SYMBOL_NAME:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
						command->data.session_append_plugin.name = g_strdup (scanner->value.v_string);
						break;
			
			case SIM_COMMAND_SYMBOL_STATE:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;

						if (!g_ascii_strcasecmp (scanner->value.v_string, "start"))
							command->data.session_append_plugin.state = 1;
						else 
						if (!g_ascii_strcasecmp (scanner->value.v_string, "stop"))
							command->data.session_remove_plugin.state = 2;
						break;
						
			case SIM_COMMAND_SYMBOL_ENABLED:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;

						if (!g_ascii_strcasecmp (scanner->value.v_string, "true"))
							command->data.session_append_plugin.enabled = TRUE;
						else
						if (!g_ascii_strcasecmp (scanner->value.v_string, "false"))
							command->data.session_remove_plugin.enabled = FALSE;
						break;

			default:
					  if (scanner->token == G_TOKEN_EOF)
					    break;
						ossim_debug ( "sim_command_session_append_plugin_scan: error symbol unknown. Session ip:%s",session_ip_str);
	          return FALSE;
        }
    }
  while(scanner->token != G_TOKEN_EOF);
	return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_session_remove_plugin_scan (SimCommand    *command,
																				GScanner      *scanner,
																				gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner != NULL, FALSE);

  command->type = SIM_COMMAND_TYPE_SESSION_REMOVE_PLUGIN;
  command->data.session_remove_plugin.id = 0;
  command->data.session_remove_plugin.type = SIM_PLUGIN_TYPE_NONE;
  command->data.session_remove_plugin.name = NULL;
  command->data.session_remove_plugin.state = 0;
  command->data.session_remove_plugin.enabled = FALSE;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_SESSION_REMOVE_PLUGIN);
  do
  {
    g_scanner_get_next_token (scanner);
 
    switch ((SimCommandSymbolType) scanner->token)
    {
      case SIM_COMMAND_SYMBOL_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
            if (sim_string_is_number (scanner->value.v_string, 0))
              command->id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: Remove plugin event incorrect. Please check the id issued from the agent:-> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;

			case SIM_COMMAND_SYMBOL_PLUGIN_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;

            if (sim_string_is_number (scanner->value.v_string, 0))
							command->data.session_remove_plugin.id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: Remove plugin event incorrect. Please check the plugin_id issued from the agent-> value: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;
						
			case SIM_COMMAND_SYMBOL_TYPE:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;

            if (sim_string_is_number (scanner->value.v_string, 0))
							command->data.session_remove_plugin.type = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: Remove plugin event incorrect. Please check the type issued from the agent: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;

			case SIM_COMMAND_SYMBOL_NAME:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;

						command->data.session_remove_plugin.name = g_strdup (scanner->value.v_string);
						break;
		
			case SIM_COMMAND_SYMBOL_STATE:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;

						if (!g_ascii_strcasecmp (scanner->value.v_string, "start"))
							command->data.session_remove_plugin.state = 1;
						else
						if (!g_ascii_strcasecmp (scanner->value.v_string, "stop"))
							command->data.session_remove_plugin.state = 2;
						break;

			case SIM_COMMAND_SYMBOL_ENABLED:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;

						if (!g_ascii_strcasecmp (scanner->value.v_string, "true"))
							command->data.session_remove_plugin.enabled = TRUE;
						else 
						if (!g_ascii_strcasecmp (scanner->value.v_string, "false"))
							command->data.session_remove_plugin.enabled = FALSE;
						break;

			default:
					  if (scanner->token == G_TOKEN_EOF)
					    break;
					  ossim_debug ( "sim_command_session_remove_plugin_scan: error symbol unknown. Session ip: %s",session_ip_str);
        	  return FALSE;
    }
  }
  while(scanner->token != G_TOKEN_EOF);
	return TRUE;
}


/*
 *
 *
 *
 */
static gboolean
sim_command_server_get_sensors_scan (SimCommand    *command,
																     GScanner      *scanner,
																     gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner != NULL, FALSE);

  command->type = SIM_COMMAND_TYPE_SERVER_GET_SENSORS;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_SERVER_GET_SENSORS);
  do
  {
    g_scanner_get_next_token (scanner);
 
    switch ((SimCommandSymbolType) scanner->token)
    {
      case SIM_COMMAND_SYMBOL_ID:
					  g_scanner_get_next_token (scanner); /* = */
					  g_scanner_get_next_token (scanner); /* value */

					  if (scanner->token != G_TOKEN_STRING)
					    break;

            if (sim_string_is_number (scanner->value.v_string, 0))
					  	command->id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: get sensors event incorrect. Please check the id issued from the frameworkd or a master server: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
	          break;
	
			case SIM_COMMAND_SYMBOL_SERVERNAME:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
            {
              command->type = SIM_COMMAND_TYPE_NONE;
              break;
            }

						if (scanner->value.v_string)
	            command->data.server_get_sensors.servername = g_strdup (scanner->value.v_string);
						else
						{
              g_message("Error: get sensors; Server Name incorrect. Please check the server name issued from the frameworkd or a master server: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
						}
            break;

					
      default:
					  if (scanner->token == G_TOKEN_EOF)
					    break;
	  				ossim_debug ( "sim_command_server_get_sensors_scan: error symbol unknown");
	          return FALSE;
    }
  }
  while(scanner->token != G_TOKEN_EOF);
  
  ossim_debug ( "sim_command_server_get_sensors_scan: id: %d",command->id);
	return TRUE;
}

/*
 *
 */
static gboolean
sim_command_server_get_servers_scan (SimCommand    *command,
																     GScanner      *scanner,
																     gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner != NULL, FALSE);

  command->type = SIM_COMMAND_TYPE_SERVER_GET_SERVERS;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_SERVER_GET_SERVERS);
  do
  {
    g_scanner_get_next_token (scanner);
 
    switch ((SimCommandSymbolType) scanner->token)
    {
      case SIM_COMMAND_SYMBOL_ID:
					  g_scanner_get_next_token (scanner); /* = */
					  g_scanner_get_next_token (scanner); /* value */

					  if (scanner->token != G_TOKEN_STRING)
					    break;

            if (sim_string_is_number (scanner->value.v_string, 0))
					  	command->id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: get servers event incorrect. Please check the id issued from the frameworkd or the master server: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
	          break;
	
			case SIM_COMMAND_SYMBOL_SERVERNAME:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
            {
              command->type = SIM_COMMAND_TYPE_NONE;
              break;
            }

						if (scanner->value.v_string)
	            command->data.server_get_servers.servername = g_strdup (scanner->value.v_string);
						else
						{
              g_message("Error: get servers; Server Name incorrect. Please check the server name issued from the frameworkd or a master server: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
						}
            break;

					
      default:
					  if (scanner->token == G_TOKEN_EOF)
					    break;
	  				ossim_debug ( "sim_command_server_get_servers_scan: error symbol unknown. Session ip: %s",session_ip_str);
	          return FALSE;
    }
  }
  while(scanner->token != G_TOKEN_EOF);
  
  ossim_debug ( "sim_command_server_get_servers_scan: id: %d",command->id);
	return TRUE;
}


/*
 *
 *
 *
 */
static gboolean
sim_command_server_get_sensor_plugins_scan (SimCommand    *command,
																				    GScanner      *scanner,
																				    gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner != NULL, FALSE);

  command->type = SIM_COMMAND_TYPE_SERVER_GET_SENSOR_PLUGINS;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_SERVER_GET_SENSOR_PLUGINS);
  do
  {
    g_scanner_get_next_token (scanner);
 
    switch ((SimCommandSymbolType) scanner->token)
    {
      case SIM_COMMAND_SYMBOL_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;

            if (sim_string_is_number (scanner->value.v_string, 0))
							command->id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: get sensor plugin event incorrect. Please check the id issued from the agent: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;
						
				case SIM_COMMAND_SYMBOL_SERVERNAME:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
            {
              command->type = SIM_COMMAND_TYPE_NONE;
              break;
            }

						if (scanner->value.v_string)
	            command->data.server_get_sensor_plugins.servername = g_strdup (scanner->value.v_string);
						else
						{
              g_message("Error: get sensor plugins; Server Name incorrect. Please check the server name issued from the frameworkd or a master server:-> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
						}
            break;
						
			default:
						if (scanner->token == G_TOKEN_EOF)
							break;
					  ossim_debug ( "sim_command_server_get_sensor_plugins_scan: error symbol unknown. Session ip:%s",session_ip_str);
						return FALSE;
          break;
    }
  }
	while(scanner->token != G_TOKEN_EOF);
	return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_server_set_data_role_scan (SimCommand    *command,
																	     GScanner      *scanner,
																	     gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner != NULL, FALSE);

  command->type = SIM_COMMAND_TYPE_SERVER_SET_DATA_ROLE;

  ossim_debug ( "sim_command_server_set_data_role_scan command->type: %d", command->type);
  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_SERVER_SET_DATA_ROLE);
  do
  {
    g_scanner_get_next_token (scanner);
 
    switch ((SimCommandSymbolType) scanner->token)
    {
      case SIM_COMMAND_SYMBOL_ID:
					  g_scanner_get_next_token (scanner); /* = */
					  g_scanner_get_next_token (scanner); /* value */

					  if (scanner->token != G_TOKEN_STRING)
					    break;

            if (sim_string_is_number (scanner->value.v_string, 0))
					  	command->id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: set data role event incorrect. Please check the id issued from the server: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
	          break;
					
      case SIM_COMMAND_SYMBOL_SERVERNAME:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
            {
              command->type = SIM_COMMAND_TYPE_NONE;
              break;
            }

						if (scanner->value.v_string)
	            command->data.server_set_data_role.servername = g_strdup (scanner->value.v_string);
						else
						{
              g_message("Error: set data role event incorrect. Please check the host issued from the server: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
						}
            break;

      case SIM_COMMAND_SYMBOL_ROLE_CORRELATE:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
            {
              command->type = SIM_COMMAND_TYPE_NONE;
              break;
            }

						if (!g_ascii_strcasecmp (scanner->value.v_string, "true"))
							command->data.server_set_data_role.correlate = TRUE;
						else
							command->data.server_set_data_role.correlate = FALSE;				
            break;

			case SIM_COMMAND_SYMBOL_ROLE_CROSS_CORRELATE:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
            {
              command->type = SIM_COMMAND_TYPE_NONE;
              break;
            }

						if (!g_ascii_strcasecmp (scanner->value.v_string, "true"))
							command->data.server_set_data_role.cross_correlate = TRUE;
						else
							command->data.server_set_data_role.cross_correlate = FALSE;				
            break;

      case SIM_COMMAND_SYMBOL_ROLE_REPUTATION:
        g_scanner_get_next_token (scanner); /* = */
        g_scanner_get_next_token (scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
        {
          command->type = SIM_COMMAND_TYPE_NONE;
          break;
        }

        if (!g_ascii_strcasecmp (scanner->value.v_string, "true"))
          command->data.server_set_data_role.reputation = TRUE;
        else
          command->data.server_set_data_role.reputation = FALSE;
        break;

		case SIM_COMMAND_SYMBOL_ROLE_LOGGER_IF_PRIORITY:
			g_scanner_get_next_token (scanner); /* = */
			g_scanner_get_next_token (scanner); /* value */

			if (scanner->token != G_TOKEN_STRING)
			{
				command->type = SIM_COMMAND_TYPE_NONE;
				break;
			} 

			if (sim_string_is_number (scanner->value.v_string, 0))
				command->data.server_set_data_role.logger_if_priority = strtol (scanner->value.v_string, (char **) NULL, 10);
			else
			{
				/* Use a default value. */
				ossim_debug ( "sim_command_server_set_data_role_scan: error parsing logger_if_priority, setting default. Session ip: %s",session_ip_str);
				command->data.server_set_data_role.logger_if_priority = 0;
			}
			break;	

      case SIM_COMMAND_SYMBOL_ROLE_STORE:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
            {
              command->type = SIM_COMMAND_TYPE_NONE;
              break;
            }

						if (!g_ascii_strcasecmp (scanner->value.v_string, "true"))
							command->data.server_set_data_role.store = TRUE;
						else
							command->data.server_set_data_role.store = FALSE;				
            break;

      case SIM_COMMAND_SYMBOL_ROLE_QUALIFY:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
            {
              command->type = SIM_COMMAND_TYPE_NONE;
              break;
            }

						if (!g_ascii_strcasecmp (scanner->value.v_string, "true"))
							command->data.server_set_data_role.qualify = TRUE;
						else
							command->data.server_set_data_role.qualify = FALSE;				
            break;

      default:
					  if (scanner->token == G_TOKEN_EOF)
					    break;
	  				ossim_debug ( "sim_command_server_set_data_role_scan: error symbol unknown. Session ip:%s",session_ip_str);
	          return FALSE;
    }
  }
  while(scanner->token != G_TOKEN_EOF);
  
  ossim_debug ( "sim_command_server_set_data_role_scan: id: %d",command->id);
	return TRUE;
}


/*
 *
 *
 *
 */
static gboolean
sim_command_sensor_plugin_scan (SimCommand    *command,
																GScanner      *scanner,
																gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner != NULL, FALSE);

  command->type = SIM_COMMAND_TYPE_SENSOR_PLUGIN;
  command->data.sensor_plugin.sensor = NULL;
  command->data.sensor_plugin.plugin_id = 0;
  command->data.sensor_plugin.state = 0;
  command->data.sensor_plugin.enabled = FALSE;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_SENSOR_PLUGIN);
  do
  {
    g_scanner_get_next_token (scanner);
 
    switch ((SimCommandSymbolType) scanner->token)
    {
      case SIM_COMMAND_SYMBOL_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;

            if (sim_string_is_number (scanner->value.v_string, 0))
							command->id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: sensor plugin event incorrect. Please check the id issued from the agent:-> value: %s,agent:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }

						break;

			case SIM_COMMAND_SYMBOL_SENSOR:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
            if (gnet_inetaddr_is_canonical (scanner->value.v_string))
              command->data.sensor_plugin.sensor = g_strdup (scanner->value.v_string);
            else
            {
              g_message("Error: Sensor plugin event incorrect. Please check the sensor ip issued from the agent: -> value: %s,agent:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }

						break;

			case SIM_COMMAND_SYMBOL_PLUGIN_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
	
	          if (sim_string_is_number (scanner->value.v_string, 0))
							command->data.sensor_plugin.plugin_id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: sensor plugin event incorrect. Please check the plugin_id issued from the agent:-> value: %s,agent:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;

			case SIM_COMMAND_SYMBOL_STATE:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;

						if (g_ascii_strcasecmp (scanner->value.v_string, "start"))
							command->data.sensor_plugin.state = 1;
						else if (g_ascii_strcasecmp (scanner->value.v_string, "stop"))
							command->data.sensor_plugin.state = 2;
						else if (g_ascii_strcasecmp (scanner->value.v_string, "unknown"))
							command->data.sensor_plugin.state = 3;
						break;

			case SIM_COMMAND_SYMBOL_ENABLED:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;

						if (g_ascii_strcasecmp (scanner->value.v_string, "true"))
							command->data.sensor_plugin.enabled = TRUE;
						else if (g_ascii_strcasecmp (scanner->value.v_string, "false"))
							command->data.sensor_plugin.enabled = FALSE;

						break;

			default:
						if (scanner->token == G_TOKEN_EOF)
							break;
					  ossim_debug ( "sim_command_sensor_plugin_scan: error symbol unknown.Session ip: %s",session_ip_str);
						return FALSE;
      }
  }
  while(scanner->token != G_TOKEN_EOF);
	return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_sensor_plugin_start_scan (SimCommand    *command,
																      GScanner      *scanner,
																      gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner != NULL, FALSE);

  command->type = SIM_COMMAND_TYPE_SENSOR_PLUGIN_START;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_SENSOR_PLUGIN_START);
  do
  {
    g_scanner_get_next_token (scanner);
 
    switch ((SimCommandSymbolType) scanner->token)
    {
      case SIM_COMMAND_SYMBOL_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;

	          if (sim_string_is_number (scanner->value.v_string, 0))
              command->id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: sensor plugin start event incorrect. Please check the id issued from the frameworkd or a master server:-> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;
						
				case SIM_COMMAND_SYMBOL_SERVERNAME:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
            {
              command->type = SIM_COMMAND_TYPE_NONE;
              break;
            }

						if (scanner->value.v_string)
	            command->data.sensor_plugin_start.servername = g_strdup (scanner->value.v_string);
						else
						{
              g_message("Error: sensor plugin start; Server Name incorrect. Please check the server name issued from the frameworkd or a master server: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
						}
            break;
	
			case SIM_COMMAND_SYMBOL_SENSOR:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
            if (gnet_inetaddr_is_canonical (scanner->value.v_string))
              command->data.sensor_plugin_start.sensor = g_strdup (scanner->value.v_string);
            else
            {
              g_message("Error: Sensor plugin start. Please check the sensor ip issued from the frameworkd or a master server: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;

			case SIM_COMMAND_SYMBOL_PLUGIN_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
  	        if (sim_string_is_number (scanner->value.v_string, 0))
							command->data.sensor_plugin_start.plugin_id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: sensor plugin start event incorrect. Please check the plugin_id issued from the frameworkd or a master server: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;

			default:
						if (scanner->token == G_TOKEN_EOF)
							break;
						ossim_debug ( "sim_command_sensor_plugin_start_scan: error symbol unknown.Session ip: %s",session_ip_str);
						return FALSE;
    }
  }
  while(scanner->token != G_TOKEN_EOF);
	return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_sensor_plugin_stop_scan (SimCommand    *command,
																     GScanner      *scanner,
																     gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner != NULL, FALSE);

  command->type = SIM_COMMAND_TYPE_SENSOR_PLUGIN_STOP;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_SENSOR_PLUGIN_STOP);
  do
  {
    g_scanner_get_next_token (scanner);

    switch ((SimCommandSymbolType) scanner->token)
    {
      case SIM_COMMAND_SYMBOL_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
	
	          if (sim_string_is_number (scanner->value.v_string, 0))
							command->id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: sensor plugin stop event incorrect. Please check the id issued from the agent:-> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;

			case SIM_COMMAND_SYMBOL_SERVERNAME:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
            {
              command->type = SIM_COMMAND_TYPE_NONE;
              break;
            }

						if (scanner->value.v_string)
	            command->data.sensor_plugin_stop.servername = g_strdup (scanner->value.v_string);
						else
						{
              g_message("Error: sensor plugin stop; Server Name incorrect. Please check the server name issued from the frameworkd or a master server: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
						}
            break;

			case SIM_COMMAND_SYMBOL_SENSOR:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
            if (gnet_inetaddr_is_canonical (scanner->value.v_string))
              command->data.sensor_plugin_stop.sensor = g_strdup (scanner->value.v_string);
            else
            {
              g_message("Error: Sensor plugin stop. Please check the sensor ip issued from the frameworkd or a master server:-> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;

			case SIM_COMMAND_SYMBOL_PLUGIN_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;

	          if (sim_string_is_number (scanner->value.v_string, 0))
							command->data.sensor_plugin_stop.plugin_id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: sensor plugin event incorrect. Please check the plugin_id issued from the agent:-> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;

			default:
						if (scanner->token == G_TOKEN_EOF)
							break;
						ossim_debug ( "sim_command_sensor_plugin_stop_scan: error symbol unknown.Session ip: %s",session_ip_str);
						return FALSE;
        }
    }
  while(scanner->token != G_TOKEN_EOF);
	return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_sensor_plugin_enable_scan (SimCommand    *command,
																				GScanner      *scanner,
																				gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner != NULL, FALSE);

  command->type = SIM_COMMAND_TYPE_SENSOR_PLUGIN_ENABLE;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_SENSOR_PLUGIN_ENABLE);
  do
  {
    g_scanner_get_next_token (scanner);
 
    switch ((SimCommandSymbolType) scanner->token)
    {
      case SIM_COMMAND_SYMBOL_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;

	          if (sim_string_is_number (scanner->value.v_string, 0))
              command->id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: sensor plugin enable event incorrect. Please check the id issued from the agent: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;

			case SIM_COMMAND_SYMBOL_SERVERNAME:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
            {
              command->type = SIM_COMMAND_TYPE_NONE;
              break;
            }

						if (scanner->value.v_string)
	            command->data.sensor_plugin_enable.servername = g_strdup (scanner->value.v_string);
						else
						{
              g_message("Error: sensor plugin enable; Server Name incorrect. Please check the server name issued from the frameworkd or a master server: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
						}
            break;

			case SIM_COMMAND_SYMBOL_SENSOR:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
            if (gnet_inetaddr_is_canonical (scanner->value.v_string))
              command->data.sensor_plugin_enable.sensor = g_strdup (scanner->value.v_string);
            else
            {
              g_message("Error: Sensor plugin enable. Please check the sensor ip issued from the frameworkd or a master server: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }

						command->data.sensor_plugin_enable.sensor = g_strdup (scanner->value.v_string);
						break;

			case SIM_COMMAND_SYMBOL_PLUGIN_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;

	          if (sim_string_is_number (scanner->value.v_string, 0))
							command->data.sensor_plugin_enable.plugin_id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: sensor plugin enable event incorrect. Please check the plugin_id issued from the agent: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }


						break;

			default:
						if (scanner->token == G_TOKEN_EOF)
							break;
						ossim_debug ( "sim_command_sensor_plugin_enable_scan: error symbol unknown. Session ip: %s",session_ip_str);
						return FALSE;
    }
  }
  while(scanner->token != G_TOKEN_EOF);
	return TRUE;
}

/*
 *
 _*
 *
 */
static gboolean
sim_command_sensor_plugin_disable_scan (SimCommand    *command,
																				 GScanner      *scanner,
																				 gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner != NULL, FALSE);

  command->type = SIM_COMMAND_TYPE_SENSOR_PLUGIN_DISABLE;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_SENSOR_PLUGIN_DISABLE);
  do
  {
    g_scanner_get_next_token (scanner);
 
    switch ((SimCommandSymbolType) scanner->token)
    {
      case SIM_COMMAND_SYMBOL_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;

  	        if (sim_string_is_number (scanner->value.v_string, 0))
	            command->id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: sensor plugin disable event incorrect. Please check the id issued from the agent: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;
						
				case SIM_COMMAND_SYMBOL_SERVERNAME:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
            {
              command->type = SIM_COMMAND_TYPE_NONE;
              break;
            }

						if (scanner->value.v_string)
	            command->data.sensor_plugin_disable.servername = g_strdup (scanner->value.v_string);
						else
						{
              g_message("Error: sensor plugin disable; Server Name incorrect. Please check the server name issued from the frameworkd or a master server: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
						}
            break;

			case SIM_COMMAND_SYMBOL_SENSOR:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
            if (gnet_inetaddr_is_canonical (scanner->value.v_string))
              command->data.sensor_plugin_disable.sensor = g_strdup (scanner->value.v_string);
            else
            {
              g_message("Error: Sensor plugin disable. Please check the sensor ip issued from the frameworkd or a master server: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;

			case SIM_COMMAND_SYMBOL_PLUGIN_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;

            if (sim_string_is_number (scanner->value.v_string, 0))
							command->data.sensor_plugin_disable.plugin_id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: sensor plugin disable event incorrect. Please check the plugin_id issued from the frameworkd or a master server: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }

						break;

			default:
						if (scanner->token == G_TOKEN_EOF)
							break;
						ossim_debug ( "sim_command_sensor_plugin_disable_scan: error symbol unknown.Session ip:%s ",session_ip_str);
						return FALSE;
    }
  }
  while(scanner->token != G_TOKEN_EOF);
	return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_plugin_state_started_scan (SimCommand    *command,
												       GScanner      *scanner,gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner != NULL, FALSE);

  command->type = SIM_COMMAND_TYPE_PLUGIN_STATE_STARTED;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_PLUGIN_STATE_STARTED);
  do
  {
    g_scanner_get_next_token (scanner);
 
    switch ((SimCommandSymbolType) scanner->token)
    {
      case SIM_COMMAND_SYMBOL_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;

            if (sim_string_is_number (scanner->value.v_string, 0))
              command->id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: sensor plugin start event incorrect. Please check the id issued from the agent:-> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;

			case SIM_COMMAND_SYMBOL_PLUGIN_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;

            if (sim_string_is_number (scanner->value.v_string, 0))
							command->data.plugin_state_started.plugin_id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: sensor plugin start event incorrect. Please check the plugin_id issued from the agent: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }

						break;

			default:
						if (scanner->token == G_TOKEN_EOF)
							break;
						ossim_debug ( "sim_command_plugin_start_scan: error symbol unknown. Session ip:%s",session_ip_str);
						return FALSE;
    }
  }
  while(scanner->token != G_TOKEN_EOF);
	return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_plugin_state_unknown_scan (SimCommand    *command,
													       GScanner      *scanner,gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner != NULL, FALSE);

  command->type = SIM_COMMAND_TYPE_PLUGIN_STATE_UNKNOWN;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_PLUGIN_STATE_UNKNOWN);
  do
  {
    g_scanner_get_next_token (scanner);
 
    switch ((SimCommandSymbolType) scanner->token)
    {
      case SIM_COMMAND_SYMBOL_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;

            if (sim_string_is_number (scanner->value.v_string, 0))
              command->id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: sensor plugin unknown event incorrect. Please check the id issued from the agent: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;

			case SIM_COMMAND_SYMBOL_PLUGIN_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
            if (sim_string_is_number (scanner->value.v_string, 0))
							command->data.plugin_state_unknown.plugin_id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: sensor plugin unknown event incorrect. Please check the plugin_id issued from the agent: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;

			default:
						if (scanner->token == G_TOKEN_EOF)
							break;
						ossim_debug ( "sim_command_plugin_unknown_scan: error symbol unknown. Session ip: %s", session_ip_str);
						return FALSE;
    }
  }
  while(scanner->token != G_TOKEN_EOF);
	return TRUE;
}



/*
 *
 *
 *
 */
static gboolean
sim_command_plugin_state_stopped_scan (SimCommand    *command,
												      GScanner      *scanner,
												      gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner != NULL, FALSE);

  command->type = SIM_COMMAND_TYPE_PLUGIN_STATE_STOPPED;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_PLUGIN_STATE_STOPPED);
  do
  {
    g_scanner_get_next_token (scanner);
 
    switch ((SimCommandSymbolType) scanner->token)
    {
      case SIM_COMMAND_SYMBOL_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
            if (sim_string_is_number (scanner->value.v_string, 0))
              command->id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: sensor plugin stop event incorrect. Please check the id issued from the agent:-> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;

			case SIM_COMMAND_SYMBOL_PLUGIN_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
            if (sim_string_is_number (scanner->value.v_string, 0))
							command->data.plugin_state_stopped.plugin_id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: sensor plugin stop event incorrect. Please check the plugin_id issued from the agent: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;

			default:
						if (scanner->token == G_TOKEN_EOF)
							break;
						ossim_debug ( "sim_command_plugin_stop_scan: error symbol unknown. Session ip:%s",session_ip_str);
						return FALSE;
    }
  }
  while(scanner->token != G_TOKEN_EOF);
	return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_plugin_enabled_scan (SimCommand    *command,
																 GScanner      *scanner,
																 gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner != NULL, FALSE);

  command->type = SIM_COMMAND_TYPE_PLUGIN_ENABLED;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_PLUGIN_ENABLED);
  do
  {
    g_scanner_get_next_token (scanner);
 
    switch ((SimCommandSymbolType) scanner->token)
    {
      case SIM_COMMAND_SYMBOL_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
            if (sim_string_is_number (scanner->value.v_string, 0))
              command->id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: sensor plugin enabled event incorrect. Please check the id issued from the agent: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;

			case SIM_COMMAND_SYMBOL_PLUGIN_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;

            if (sim_string_is_number (scanner->value.v_string, 0))
							command->data.plugin_enabled.plugin_id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: sensor plugin enabled event incorrect. Please check the plugin_id issued from the agent:-> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;

			default:
						if (scanner->token == G_TOKEN_EOF)
							break;
						ossim_debug ( "sim_command_plugin_enabled_scan: error symbol unknown. Session ip: %s",session_ip_str);
						return FALSE;
    }
  }
  while(scanner->token != G_TOKEN_EOF);
	return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_plugin_disabled_scan (SimCommand    *command,
																  GScanner      *scanner,
																  gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner != NULL, FALSE);

  command->type = SIM_COMMAND_TYPE_PLUGIN_DISABLED;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_PLUGIN_DISABLED);
  do
  {
    g_scanner_get_next_token (scanner);
 
    switch ((SimCommandSymbolType) scanner->token)
    {
      case SIM_COMMAND_SYMBOL_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;

            if (sim_string_is_number (scanner->value.v_string, 0))
              command->id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: sensor plugin disabled event incorrect. Please check the id issued from the agent:-> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;

			case SIM_COMMAND_SYMBOL_PLUGIN_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
            if (sim_string_is_number (scanner->value.v_string, 0))
							command->data.plugin_disabled.plugin_id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: sensor plugin disabled event incorrect. Please check the plugin_id issued from the agent: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;

			default:
						if (scanner->token == G_TOKEN_EOF)
							break;
						ossim_debug ( "sim_command_plugin_disabled_scan: error symbol unknown. Session ip:%s",session_ip_str);
						return FALSE;
    }
  }
  while(scanner->token != G_TOKEN_EOF);
	return TRUE;
}
/*
 * BASE64 VERSION of sim_command_event_scan
 */
static gboolean
sim_command_event_scan_base64 (SimCommand    *command,
                               GScanner      *scanner)
{
  gsize   base64len;

  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner != NULL, FALSE);

  sim_command_init_command_event_struct(command);

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_EVENT);
  do
  {
    g_scanner_get_next_token (scanner);

    switch ((SimCommandSymbolType) scanner->token)
    {
    case SIM_COMMAND_SYMBOL_TYPE:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      command->data.event.type = g_strdup (scanner->value.v_string);
      break;

    case SIM_COMMAND_SYMBOL_ID:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
        break;
      if (sim_string_is_number (scanner->value.v_string, 0))
        command->data.event.id_transaction = strtol (scanner->value.v_string, (char **) NULL, 10);
      else
      {
        g_message("Error: event incorrect. Please check the id issued from the remote server: %s", scanner->value.v_string);
        return FALSE;
      }

      break;
    case SIM_COMMAND_SYMBOL_CTX:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
        break;
      if ((command->context_id = sim_uuid_new_from_string (scanner->value.v_string)) == NULL)
      {
        g_message("Error: event incorrect. Please check the 'ctx' issued from the remote server: %s", scanner->value.v_string);
        return FALSE;
      }
      break;

    case SIM_COMMAND_SYMBOL_PLUGIN_ID:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */
	  
      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }

      if (sim_string_is_number (scanner->value.v_string, 0))
        command->data.event.plugin_id = strtol (scanner->value.v_string, (char **) NULL, 10);
      else
      {
        g_message("Error: event incorrect. Please check the plugin_id issued from the agent: %s", scanner->value.v_string);
        return FALSE;
      }

      break;
						
    case SIM_COMMAND_SYMBOL_PLUGIN_SID:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */
	  
      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      if (sim_string_is_number (scanner->value.v_string, 0))
        command->data.event.plugin_sid = strtol (scanner->value.v_string, (char **) NULL, 10);
      else
      {
        g_message("Error: event incorrect. Please check the plugin_sid issued from the agent: %s", scanner->value.v_string);
        return FALSE;
      }

      break;
						
    case SIM_COMMAND_SYMBOL_DATE:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      if (sim_string_is_number (scanner->value.v_string, 1))
        command->data.event.date = strtol(scanner->value.v_string,(char **)NULL,10);
      else
      {
        g_message("Error: event incorrect. Please check the date issued from the agent: %s", scanner->value.v_string);
        return FALSE;														 
      }
      break;

    case SIM_COMMAND_SYMBOL_DATE_STRING:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }

      command->data.event.date_str = g_strdup (scanner->value.v_string);
      break;

    case SIM_COMMAND_SYMBOL_DATE_TZONE:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      if (sim_string_is_number (scanner->value.v_string, TRUE))
        command->data.event.tzone = g_ascii_strtod(scanner->value.v_string, (gchar**)NULL);
      else
        g_message(" %s: Error: Please check the tzone value: %s. Assumed tzone = 0.", __func__, scanner->value.v_string);
      break;

    case SIM_COMMAND_SYMBOL_SENSOR:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      if (gnet_inetaddr_is_canonical (scanner->value.v_string))
        command->data.event.sensor = g_strdup (scanner->value.v_string);
      else
      {
        g_message("Error: event incorrect. Please check the sensor issued from the agent: %s", scanner->value.v_string);
        return FALSE;
      }
      break;

    case SIM_COMMAND_SYMBOL_SENSOR_ID:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }

      command->data.event.sensor_id = sim_uuid_new_from_string (scanner->value.v_string);
      break;

    case SIM_COMMAND_SYMBOL_DEVICE:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      if (gnet_inetaddr_is_canonical (scanner->value.v_string))
        command->data.event.device = g_strdup (scanner->value.v_string);
      else
      {
        g_message("Error: event incorrect. Please check the device received: %s", scanner->value.v_string);
        return FALSE;
      }
      break;

    case SIM_COMMAND_SYMBOL_DEVICE_ID:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      if (sim_string_is_number (scanner->value.v_string, 0))
        command->data.event.device_id = strtol (scanner->value.v_string, (char **) NULL, 10);
      else
      {
        g_message("Error: event incorrect. Please check the device_id received: %s", scanner->value.v_string);
        return FALSE;
      }
      break;
						
    case SIM_COMMAND_SYMBOL_INTERFACE:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }	
      command->data.event.interface = g_strdup (scanner->value.v_string);
      break;
						
    case SIM_COMMAND_SYMBOL_PRIORITY:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      if (sim_string_is_number (scanner->value.v_string, 0))
      {
        command->data.event.priority = strtol (scanner->value.v_string, (char **) NULL, 10);
        command->data.event.is_priority_set = TRUE;
      }
      else
      {
        g_message("Error: event incorrect. Please check the priority issued from the agent: %s", scanner->value.v_string);
        return FALSE;
      }

      break;
						
    case SIM_COMMAND_SYMBOL_PROTOCOL:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      command->data.event.protocol = g_strdup (scanner->value.v_string);
      break;
						
    case SIM_COMMAND_SYMBOL_SRC_IP:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      if (gnet_inetaddr_is_canonical (scanner->value.v_string))
        command->data.event.src_ip = g_strdup (scanner->value.v_string);
      else
      {
        g_message("Error: event incorrect. Please check the src ip issued from the agent: %s", scanner->value.v_string);
        return FALSE;
      }
      break;
						
    case SIM_COMMAND_SYMBOL_SRC_PORT:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      if (sim_string_is_number (scanner->value.v_string, 0))
        command->data.event.src_port = strtol (scanner->value.v_string, (char **) NULL, 10);
      else
      {
        g_message("Error: event incorrect. Please check the src_port issued from the agent: %s", scanner->value.v_string);
        return FALSE;
      }

      break;
						
    case SIM_COMMAND_SYMBOL_DST_IP:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      if (gnet_inetaddr_is_canonical (scanner->value.v_string))
        command->data.event.dst_ip = g_strdup (scanner->value.v_string);
      else
      {
        g_message("Error: event incorrect. Please check the dst ip issued from the agent: %s", scanner->value.v_string);
        return FALSE;
      }
      break;

    case SIM_COMMAND_SYMBOL_DST_PORT:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      if (sim_string_is_number (scanner->value.v_string, 0))
        command->data.event.dst_port = strtol (scanner->value.v_string, (char **) NULL, 10);
      else
      {
        g_message("Error: event incorrect. Please check the dst_port issued from the agent: %s", scanner->value.v_string);
        return FALSE;
      }
      break;

    case SIM_COMMAND_SYMBOL_SRC_NET:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */
      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }

      command->data.event.src_net = sim_uuid_new_from_string (scanner->value.v_string);
      break;

    case SIM_COMMAND_SYMBOL_DST_NET:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */
      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }

      command->data.event.dst_net = sim_uuid_new_from_string (scanner->value.v_string);
      break;

    case SIM_COMMAND_SYMBOL_CONDITION:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      command->data.event.condition = g_strdup (scanner->value.v_string);
      break;
						
    case SIM_COMMAND_SYMBOL_VALUE:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      command->data.event.value = g_strdup (scanner->value.v_string);
      break;
						
    case SIM_COMMAND_SYMBOL_INTERVAL:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      if (sim_string_is_number (scanner->value.v_string, 0))
        command->data.event.interval = strtol (scanner->value.v_string, (char **) NULL, 10);
      else
      {
        g_message("Error: event incorrect. Please check the interval issued from the agent: %s", scanner->value.v_string);
        return FALSE;
      }

      break;

    case SIM_COMMAND_SYMBOL_DATA:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      command->data.event.data = (gchar*)g_base64_decode( scanner->value.v_string,&base64len);

      break;
					
    case SIM_COMMAND_SYMBOL_LOG:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      if (!scanner->value.v_string [0] == '\0')
      {
        command->data.event.log = g_string_new (NULL);
        g_free (command->data.event.log->str);
        command->data.event.log->str = (gchar*) g_base64_decode (scanner->value.v_string,&base64len);
        command->data.event.log->len = base64len;
        command->data.event.log->allocated_len = base64len;
      }
      break;

    case SIM_COMMAND_SYMBOL_SNORT_SID:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      if (sim_string_is_number (scanner->value.v_string, 0))
        command->data.event.snort_sid = strtol (scanner->value.v_string, (char **) NULL, 10);
      else
      {
        g_message("Error: event incorrect. Please check the snort_sid issued from the agent: %s", scanner->value.v_string);
        return FALSE;
      }
      break;

    case SIM_COMMAND_SYMBOL_SNORT_CID:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      if (sim_string_is_number (scanner->value.v_string, 0))
        command->data.event.snort_cid = strtol (scanner->value.v_string, (char **) NULL, 10);
      else
      {
        g_message("Error: event incorrect. Please check the snort_cid issued from the agent: %s", scanner->value.v_string);
        return FALSE;
      }
      break;

    case SIM_COMMAND_SYMBOL_ASSET_SRC:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      if (sim_string_is_number (scanner->value.v_string, 0))
        command->data.event.asset_src = strtol (scanner->value.v_string, (char **) NULL, 10);
      else
      {
        g_message("Error: event incorrect. Please check the asset src issued from the agent: %s", scanner->value.v_string);
        return FALSE;
      }
      break;
		
    case SIM_COMMAND_SYMBOL_ASSET_DST:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      if (sim_string_is_number (scanner->value.v_string, 0))
        command->data.event.asset_dst = strtol (scanner->value.v_string, (char **) NULL, 10);
      else
      {
        g_message("Error: event incorrect. Please check the asset dst issued from the agent: %s", scanner->value.v_string);
        return FALSE;
      }
      break;
						
    case SIM_COMMAND_SYMBOL_RISK_A:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      if (sim_string_is_number (scanner->value.v_string, 1)) //this can be float...
        command->data.event.risk_a = strtod (scanner->value.v_string, (char **) NULL);
      else
      {
        g_message("Error: event incorrect. Please check the Risk_A issued from the agent: %s", scanner->value.v_string);
        return FALSE;
      }
      break;

    case SIM_COMMAND_SYMBOL_RISK_C:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      if (sim_string_is_number (scanner->value.v_string, 1)) //this can be float
        command->data.event.risk_c = strtod (scanner->value.v_string, (char **) NULL);
      else
      {
        g_message("Error: event incorrect. Please check the Risk_C issued from the agent: %s", scanner->value.v_string);
        return FALSE;
      }

      break;

    case SIM_COMMAND_SYMBOL_RELIABILITY:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      if (sim_string_is_number (scanner->value.v_string, 0))
      {
        command->data.event.reliability = strtol (scanner->value.v_string, (char **) NULL, 10);
        command->data.event.is_reliability_set = TRUE;
      }
      else
      {
        g_message("Error: event incorrect. Please check the reliability issued from the agent: %s", scanner->value.v_string);
        return FALSE;
      }

      break;

    case SIM_COMMAND_SYMBOL_ALARM:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }

      if (!g_ascii_strcasecmp (scanner->value.v_string, "TRUE"))
        command->data.event.alarm = TRUE;
      else
        if (sim_string_is_number (scanner->value.v_string, 0))
          command->data.event.alarm = strtod (scanner->value.v_string, (char **) NULL);

      break;
    case SIM_COMMAND_SYMBOL_BELONGS_TO_ALARM:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }

      if (sim_string_is_number (scanner->value.v_string, 0)) 
        command->data.event.belongs_to_alarm_from_child= strtod (scanner->value.v_string, (char **) NULL);
      else
      {
        g_message("Error: event incorrect. Please check the belongs_to_alarm_from_child value: %s", scanner->value.v_string);
        return FALSE;
      }
      break;
    case SIM_COMMAND_SYMBOL_IS_REMOTE:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }

      if (sim_string_is_number (scanner->value.v_string, 0))
        command->data.event.is_remote = strtod (scanner->value.v_string, (char **) NULL);
      else
      {
        g_message("Error: event incorrect. Please check the 'is_remote' value: %s", scanner->value.v_string);
        return FALSE;
      }
      break;

    case SIM_COMMAND_SYMBOL_BACKLOG_ID:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
        break;
      if ((command->data.event.saqqara_backlog_id = sim_uuid_new_from_string (scanner->value.v_string)) == NULL)
      {
        g_message("Error: event incorrect. Please check the 'backlog_id' issued from the remote server: %s", scanner->value.v_string);
        return FALSE;
      }
      break;

    case SIM_COMMAND_SYMBOL_LEVEL:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }

      if (sim_string_is_number (scanner->value.v_string, 0))
        command->data.event.level = strtod (scanner->value.v_string, (char **) NULL);
      else
      {
        g_message("Error: event incorrect. Please check the 'level' value: %s", scanner->value.v_string);
        return FALSE;
      }
      break;

    case SIM_COMMAND_SYMBOL_FILENAME:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      command->data.event.filename = (gchar*)g_base64_decode (scanner->value.v_string,&base64len);
      break;

    case SIM_COMMAND_SYMBOL_USERNAME:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      command->data.event.username = (gchar*)g_base64_decode (scanner->value.v_string,&base64len);
      break;

    case SIM_COMMAND_SYMBOL_PASSWORD:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      command->data.event.password = (gchar*)g_base64_decode (scanner->value.v_string,&base64len);
      break;

    case SIM_COMMAND_SYMBOL_USERDATA1:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      command->data.event.userdata1 = (gchar*)g_base64_decode (scanner->value.v_string,&base64len);
      break;

    case SIM_COMMAND_SYMBOL_USERDATA2:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      command->data.event.userdata2 = (gchar*)g_base64_decode (scanner->value.v_string,&base64len);
      break;

    case SIM_COMMAND_SYMBOL_USERDATA3:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      command->data.event.userdata3 = (gchar*)g_base64_decode (scanner->value.v_string,&base64len);
      break;

    case SIM_COMMAND_SYMBOL_USERDATA4:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      command->data.event.userdata4 = (gchar*)g_base64_decode (scanner->value.v_string,&base64len);
      break;

    case SIM_COMMAND_SYMBOL_USERDATA5:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      command->data.event.userdata5 = (gchar*)g_base64_decode (scanner->value.v_string,&base64len);
      break;

    case SIM_COMMAND_SYMBOL_USERDATA6:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      command->data.event.userdata6 = (gchar*)g_base64_decode (scanner->value.v_string,&base64len);
      break;

    case SIM_COMMAND_SYMBOL_USERDATA7:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      command->data.event.userdata7 = (gchar*)g_base64_decode (scanner->value.v_string,&base64len);
      break;

    case SIM_COMMAND_SYMBOL_USERDATA8:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      command->data.event.userdata8 = (gchar*)g_base64_decode (scanner->value.v_string,&base64len);
      break;

    case SIM_COMMAND_SYMBOL_USERDATA9:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      command->data.event.userdata9 = (gchar*)g_base64_decode (scanner->value.v_string,&base64len);
      break;

    case SIM_COMMAND_SYMBOL_SRC_ID:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }

      command->data.event.src_id = sim_uuid_new_from_string (scanner->value.v_string);
      break;

    case SIM_COMMAND_SYMBOL_DST_ID:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }

      command->data.event.dst_id = sim_uuid_new_from_string (scanner->value.v_string);
      break;

    case SIM_COMMAND_SYMBOL_SRC_USERNAME:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      command->data.event.src_username = (gchar *)g_base64_decode (scanner->value.v_string,&base64len);

      /* ignore "NULL" usernames */
      if (g_ascii_strcasecmp (command->data.event.src_username, "NULL") == 0)
      {
        g_free (command->data.event.src_username);
        command->data.event.src_username = NULL;
      }
      break;

    case SIM_COMMAND_SYMBOL_DST_USERNAME:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      command->data.event.dst_username = (gchar *)g_base64_decode (scanner->value.v_string,&base64len);

      /* ignore "NULL" usernames */
      if (g_ascii_strcasecmp (command->data.event.dst_username, "NULL") == 0)
      {
        g_free (command->data.event.dst_username);
        command->data.event.dst_username = NULL;
      }
      break;

    case SIM_COMMAND_SYMBOL_SRC_HOSTNAME:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }

      /* ignore "NULL" hostnames */
      if (g_ascii_strcasecmp (scanner->value.v_string, "NULL") != 0)
      {
        command->data.event.src_hostname = g_strdup (scanner->value.v_string);
      }
      break;

    case SIM_COMMAND_SYMBOL_DST_HOSTNAME:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }

      /* ignore "NULL" hostnames */
      if (g_ascii_strcasecmp (scanner->value.v_string, "NULL") != 0)
      {
        command->data.event.dst_hostname = g_strdup (scanner->value.v_string);
      }
      break;

    case SIM_COMMAND_SYMBOL_SRC_MAC:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }

      /* ignore "NULL" macs */
      if (g_ascii_strcasecmp (scanner->value.v_string, "NULL") != 0)
      {
        command->data.event.src_mac = g_strdup (scanner->value.v_string);
      }
      break;

    case SIM_COMMAND_SYMBOL_DST_MAC:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }

      /* ignore "NULL" macs */
      if (g_ascii_strcasecmp (scanner->value.v_string, "NULL") != 0)
      {
        command->data.event.dst_mac = g_strdup (scanner->value.v_string);
      }
      break;

    case SIM_COMMAND_SYMBOL_REP_PRIO_SRC:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }

      if (sim_string_is_number (scanner->value.v_string, 0))
        command->data.event.rep_prio_src = strtod (scanner->value.v_string, (char **) NULL);
      else
      {
        g_message("Error: event incorrect. Please check the rep_prio_src value: %s", scanner->value.v_string);
        return FALSE;
      }
      break;

    case SIM_COMMAND_SYMBOL_REP_PRIO_DST:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }

      if (sim_string_is_number (scanner->value.v_string, 0))
        command->data.event.rep_prio_dst = strtod (scanner->value.v_string, (char **) NULL);
      else
      {
        g_message("Error: event incorrect. Please check the rep_prio_dst value: %s", scanner->value.v_string);
        return FALSE;
      }
      break;

    case SIM_COMMAND_SYMBOL_REP_REL_SRC:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }

      if (sim_string_is_number (scanner->value.v_string, 0))
        command->data.event.rep_rel_src = strtod (scanner->value.v_string, (char **) NULL);
      else
      {
        g_message("Error: event incorrect. Please check the rep_rel_src value: %s", scanner->value.v_string);
        return FALSE;
      }
      break;

    case SIM_COMMAND_SYMBOL_REP_REL_DST:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }

      if (sim_string_is_number (scanner->value.v_string, 0))
        command->data.event.rep_rel_dst = strtod (scanner->value.v_string, (char **) NULL);
      else
      {
        g_message("Error: event incorrect. Please check the rep_rel_dst value: %s", scanner->value.v_string);
        return FALSE;
      }
      break;


    case SIM_COMMAND_SYMBOL_REP_ACT_SRC:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      command->data.event.rep_act_src = (gchar *)g_base64_decode (scanner->value.v_string,&base64len);
      break;

    case SIM_COMMAND_SYMBOL_REP_ACT_DST:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      command->data.event.rep_act_dst = (gchar *)g_base64_decode (scanner->value.v_string,&base64len);
      break;

    case SIM_COMMAND_SYMBOL_EVENT_ID:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      command->data.event.id = sim_uuid_new_from_string(scanner->value.v_string);
      break;

    case SIM_COMMAND_SYMBOL_BINARY_DATA:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }

      command->data.event.binary_data = g_strdup (scanner->value.v_string);
      break;

    case SIM_COMMAND_SYMBOL_UUID:
    case SIM_COMMAND_SYMBOL_UUID_BACKLOG:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      // Deprecated
      break;

    default:
      if (scanner->token == G_TOKEN_EOF)
        break;
      ossim_debug ("%s: error symbol unknown; Symbol number:%d. Event Rejected.", __func__, scanner->token);
      return FALSE; //we will return with the first rare token
    }
  }
  while(scanner->token != G_TOKEN_EOF);

  return TRUE;
}

/*
 *
 * 
 *
 */
gboolean
sim_command_sensor_get_events_scan (SimCommand    *command,
																	  GScanner      *scanner,
																	  gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner != NULL, FALSE);

  command->type = SIM_COMMAND_TYPE_SENSOR_GET_EVENTS;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_SENSOR_GET_EVENTS);
  do
  {
    g_scanner_get_next_token (scanner);
 
    switch ((SimCommandSymbolType) scanner->token)
    {
      case SIM_COMMAND_SYMBOL_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						ossim_debug ( "sim_command_sensor_get_events_scan: scanning id...");
						if (scanner->token != G_TOKEN_STRING)
							break;

            if (sim_string_is_number (scanner->value.v_string, 0))
              command->id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: sensor get events incorrect. Please check the id issued from the agent: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;

			default:
						if (scanner->token == G_TOKEN_EOF)
							break;
						ossim_debug ( "sim_command_sensor_get_events_scan: error symbol unknown. Session ip:%s",session_ip_str);
						return FALSE;
    }
  }
  while(scanner->token != G_TOKEN_EOF);
	return TRUE;
}


/*
 *
 *
 *
 */
static gboolean
sim_command_reload_plugins_scan (SimCommand    *command,
																 GScanner      *scanner,
																 gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner, FALSE);

  command->type = SIM_COMMAND_TYPE_RELOAD_PLUGINS;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_RELOAD_PLUGINS);
  do
  {
    g_scanner_get_next_token (scanner);
 
    switch ((SimCommandSymbolType) scanner->token)
    {
      case SIM_COMMAND_SYMBOL_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
            if (sim_string_is_number (scanner->value.v_string, 0))
							command->id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: Reload plugins event incorrect. Please check the id issued from the agent: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;

			case SIM_COMMAND_SYMBOL_SERVERNAME:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
            {
              command->type = SIM_COMMAND_TYPE_NONE;
              break;
            }

						if (scanner->value.v_string)
	            command->data.reload_plugins.servername = g_strdup (scanner->value.v_string);
						else
						{
              g_message("Error: reload plugins; Server Name incorrect. Please check the server name issued from the frameworkd or a master server:-> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
						}
            break;

			default:
           	if (scanner->token == G_TOKEN_EOF)
							break;
					 
						ossim_debug ( "sim_command_reload_plugins_scan: error symbol unknown. Session ip:%s",session_ip_str);
          return FALSE;
    }
  }
  while(scanner->token != G_TOKEN_EOF);
	return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_reload_sensors_scan (SimCommand    *command,
																 GScanner      *scanner,
																 gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner, FALSE);

  command->type = SIM_COMMAND_TYPE_RELOAD_SENSORS;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_RELOAD_SENSORS);
  do
  {
    g_scanner_get_next_token (scanner);
 
    switch ((SimCommandSymbolType) scanner->token)
    {
      case SIM_COMMAND_SYMBOL_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
            if (sim_string_is_number (scanner->value.v_string, 0))
              command->id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: Reload sensors event incorrect. Please check the id issued from the agent: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;

			case SIM_COMMAND_SYMBOL_SERVERNAME:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
            {
              command->type = SIM_COMMAND_TYPE_NONE;
              break;
            }

						if (scanner->value.v_string)
	            command->data.reload_sensors.servername = g_strdup (scanner->value.v_string);
						else
						{
              g_message("Error: reload sensors; Server Name incorrect. Please check the server name issued from the frameworkd or a master server: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
						}
            break;

			default:
						if (scanner->token == G_TOKEN_EOF)
					    break;
					  ossim_debug ( "sim_command_reload_sensors_scan: error symbol unknown. Session ip: %s",session_ip_str);
						return FALSE;
    }
  }
  while(scanner->token != G_TOKEN_EOF);
	return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_reload_hosts_scan (SimCommand    *command,
												       GScanner      *scanner,
												       gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner, FALSE);

  command->type = SIM_COMMAND_TYPE_RELOAD_HOSTS;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_RELOAD_HOSTS);
  do
  {
    g_scanner_get_next_token (scanner);
 
    switch ((SimCommandSymbolType) scanner->token)
    {
      case SIM_COMMAND_SYMBOL_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
            if (sim_string_is_number (scanner->value.v_string, 0))
              command->id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: Reload hosts event incorrect. Please check the id issued from the agent: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;
      case SIM_COMMAND_SYMBOL_CTX:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
              break;
            if ((command->context_id = sim_uuid_new_from_string (scanner->value.v_string)) == NULL)
            {
              g_message("Error: event incorrect. Please check the 'ctx' issued from the remote server: %s", scanner->value.v_string);
              return FALSE;
            }
            break;
			case SIM_COMMAND_SYMBOL_SERVERNAME:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
            {
              command->type = SIM_COMMAND_TYPE_NONE;
              break;
            }

						if (scanner->value.v_string)
	            command->data.reload_hosts.servername = g_strdup (scanner->value.v_string);
						else
						{
              g_message("Error: reload_hosts; Server Name incorrect. Please check the server name issued from the frameworkd or a master server: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
						}
            break;

			default:
						if (scanner->token == G_TOKEN_EOF)
							break;
						ossim_debug ( "sim_command_reload_host_scan: error symbol unknown. Session ip:%s",session_ip_str);
						return FALSE;
    }
  }
  while(scanner->token != G_TOKEN_EOF);
	return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_reload_nets_scan (SimCommand    *command,
															GScanner      *scanner,
															gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner, FALSE);

  command->type = SIM_COMMAND_TYPE_RELOAD_NETS;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_RELOAD_NETS);
  do
  {
    g_scanner_get_next_token (scanner);
 
    switch ((SimCommandSymbolType) scanner->token)
    {
      case SIM_COMMAND_SYMBOL_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
            if (sim_string_is_number (scanner->value.v_string, 0))
              command->id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: Reload inets event incorrect. Please check the id issued from the agent: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;

      case SIM_COMMAND_SYMBOL_CTX:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
              break;
            if ((command->context_id = sim_uuid_new_from_string (scanner->value.v_string)) == NULL)
            {
              g_message("Error: event incorrect. Please check the 'ctx' issued from the remote server: %s", scanner->value.v_string);
              return FALSE;
            }
            break;

			case SIM_COMMAND_SYMBOL_SERVERNAME:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
            {
              command->type = SIM_COMMAND_TYPE_NONE;
              break;
            }

						if (scanner->value.v_string)
	            command->data.reload_nets.servername = g_strdup (scanner->value.v_string);
						else
						{
              g_message("Error: reload nets; Server Name incorrect. Please check the server name issued from the frameworkd or a master server: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
						}
            break;


			default:
						if (scanner->token == G_TOKEN_EOF)
							break;
						ossim_debug ( "sim_command_reload_nets_scan: error symbol unknown. Session ip:%s",session_ip_str);
						return FALSE;
    }
  }
  while(scanner->token != G_TOKEN_EOF);
	return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_reload_policies_scan (SimCommand    *command,
				    GScanner      *scanner,gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner, FALSE);

  command->type = SIM_COMMAND_TYPE_RELOAD_POLICIES;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_RELOAD_POLICIES);
  do
  {
    g_scanner_get_next_token (scanner);
 
    switch ((SimCommandSymbolType) scanner->token)
    {
	    case SIM_COMMAND_SYMBOL_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
            if (sim_string_is_number (scanner->value.v_string, 0))
              command->id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: Reload policies event incorrect. Please check the id issued from the agent: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;

      case SIM_COMMAND_SYMBOL_CTX:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
              break;
            if ((command->context_id = sim_uuid_new_from_string (scanner->value.v_string)) == NULL)
            {
              g_message("Error: event incorrect. Please check the 'ctx' issued from the remote server: %s", scanner->value.v_string);
              return FALSE;
            }
            break;

				case SIM_COMMAND_SYMBOL_SERVERNAME:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
            {
              command->type = SIM_COMMAND_TYPE_NONE;
              break;
            }

						if (scanner->value.v_string)
	            command->data.reload_policies.servername = g_strdup (scanner->value.v_string);
						else
						{
              g_message("Error: reload policies; Server Name incorrect. Please check the server name issued from the frameworkd or a master server:-> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
						}
            break;

		default:
						if (scanner->token == G_TOKEN_EOF)
							break;
						ossim_debug ( "sim_command_reload_policies_scan: error symbol unknown. Session ip:%s",session_ip_str);
						return FALSE;
    }
  }
  while(scanner->token != G_TOKEN_EOF);
	return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_reload_directives_scan (SimCommand    *command,
																    GScanner      *scanner,
																    gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner, FALSE);

  command->type = SIM_COMMAND_TYPE_RELOAD_DIRECTIVES;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_RELOAD_DIRECTIVES);
  do
  {
    g_scanner_get_next_token (scanner);
 
    switch ((SimCommandSymbolType) scanner->token)
    {
      case SIM_COMMAND_SYMBOL_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
            if (sim_string_is_number (scanner->value.v_string, 0))
              command->id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: Reload directives event incorrect. Please check the id issued from the agent: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;

      case SIM_COMMAND_SYMBOL_CTX:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
              break;
            if ((command->context_id = sim_uuid_new_from_string (scanner->value.v_string)) == NULL)
            {
              g_message("Error: event incorrect. Please check the 'ctx' issued from the remote server: %s", scanner->value.v_string);
              return FALSE;
            }
            break;

				case SIM_COMMAND_SYMBOL_SERVERNAME:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
            {
              command->type = SIM_COMMAND_TYPE_NONE;
              break;
            }

						if (scanner->value.v_string)
	            command->data.reload_directives.servername = g_strdup (scanner->value.v_string);
						else
						{
              g_message("Error: reload directives; Server Name incorrect. Please check the server name issued from the frameworkd or a master server: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
						}
            break;

		default:
						if (scanner->token == G_TOKEN_EOF)
							break;
						ossim_debug ( "sim_command_reload_directives_scan: error symbol unknown. Session ip: %s", session_ip_str);
						return FALSE;
    }
  }
  while(scanner->token != G_TOKEN_EOF);
	return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_reload_servers_scan (SimCommand *command,
                                 GScanner   *scanner,
                                 gchar      *session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner, FALSE);

  command->type = SIM_COMMAND_TYPE_RELOAD_SERVERS;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_RELOAD_SERVERS);
  do
  {
    g_scanner_get_next_token (scanner);

    switch ((SimCommandSymbolType) scanner->token)
    {
    case SIM_COMMAND_SYMBOL_ID:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
        break;

      if (sim_string_is_number (scanner->value.v_string, 0))
      {
        command->id = strtol (scanner->value.v_string, (char **) NULL, 10);
      }
      else
      {
        g_message ("%s Error: event incorrect. Please check the id issued from the session: "
                   "value: %s,session_ip: %s",
                   __func__, scanner->value.v_string,session_ip_str);
        return FALSE;
      }
      break;

    case SIM_COMMAND_SYMBOL_CTX:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
        break;

      if ((command->context_id = sim_uuid_new_from_string (scanner->value.v_string)) == NULL)
      {
        g_message ("%s Error: event incorrect. Please check the 'ctx' issued from the web: %s",
                   __func__, scanner->value.v_string);
        return FALSE;
      }
      break;

    case SIM_COMMAND_SYMBOL_SERVERNAME:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }

      if (scanner->value.v_string)
        command->data.reload_servers.servername = g_strdup (scanner->value.v_string);
      else
      {
        g_message ("%s: Error: reload all; Server Name incorrect. "
                   "Please check the server name issued: value: %s, session_ip: %s",
                   __func__, scanner->value.v_string, session_ip_str);
        return FALSE;
      }
      break;

		default:
      if (scanner->token == G_TOKEN_EOF)
        break;

      ossim_debug ("%s: error symbol unknown.Session ip: %s", __func__, session_ip_str);
      return FALSE;
    }
  }

  while (scanner->token != G_TOKEN_EOF);

	return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_reload_all_scan (SimCommand    *command,
												     GScanner      *scanner,
												     gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner, FALSE);

  command->type = SIM_COMMAND_TYPE_RELOAD_ALL;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_RELOAD_ALL);
  do
  {
    g_scanner_get_next_token (scanner);
 
    switch ((SimCommandSymbolType) scanner->token)
    {
      case SIM_COMMAND_SYMBOL_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
            if (sim_string_is_number (scanner->value.v_string, 0))
              command->id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: Reload all event incorrect. Please check the id issued from the agent: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;

      case SIM_COMMAND_SYMBOL_CTX:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
              break;
            if ((command->context_id = sim_uuid_new_from_string (scanner->value.v_string)) == NULL)
            {
              g_message("Error: event incorrect. Please check the 'ctx' issued from the remote server: %s", scanner->value.v_string);
              return FALSE;
            }
            break;

				case SIM_COMMAND_SYMBOL_SERVERNAME:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
            {
              command->type = SIM_COMMAND_TYPE_NONE;
              break;
            }

						if (scanner->value.v_string)
	            command->data.reload_all.servername = g_strdup (scanner->value.v_string);
						else
						{
              g_message("Error: reload all; Server Name incorrect. Please check the server name issued from the frameworkd or a master server: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
						}
            break;

		default:
						if (scanner->token == G_TOKEN_EOF)
							break;
						ossim_debug ( "sim_command_reload_all_scan: error symbol unknown.Session ip: %s", session_ip_str);
						return FALSE;
    }
  }
  while(scanner->token != G_TOKEN_EOF);
	return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_reload_post_correlation_sids (SimCommand    *command,
                                          GScanner      *scanner,
                                          gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner, FALSE);

  command->type = SIM_COMMAND_TYPE_RELOAD_POST_CORRELATION_SIDS;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_RELOAD_POST_CORRELATION_SIDS);
  do
  {
    g_scanner_get_next_token (scanner);

    switch ((SimCommandSymbolType) scanner->token)
    {
      case SIM_COMMAND_SYMBOL_POST_CORRELATION_SIDS:
        g_scanner_get_next_token (scanner); /* = */
        g_scanner_get_next_token (scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
        {
          command->type = SIM_COMMAND_TYPE_NONE;
          break;
        }
        if (scanner->value.v_string)
          command->data.reload_post_correlation_sids.sids = g_strdup (scanner->value.v_string);
        break;

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        ossim_debug ("%s: error symbol unknown. Session ip:%s", __func__, session_ip_str);
      return FALSE;
    }
  }
  while(scanner->token != G_TOKEN_EOF);
  return TRUE;
}



/*
 *
 *
 *
 */
static gboolean
sim_command_host_os_event_scan (SimCommand    *command,
																 GScanner      *scanner,
																 gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner, FALSE);

  gsize base64len;
  gchar *aux;

  command->type = SIM_COMMAND_TYPE_HOST_OS_EVENT;
  command->data.host_os_event.date = 0;
  command->data.host_os_event.date_str = NULL;
  command->data.host_os_event.tzone = 0;
  command->data.host_os_event.id_transaction = 0;
  command->data.host_os_event.host = NULL;
  command->data.host_os_event.os = NULL;
  command->data.host_os_event.sensor = NULL;
  command->data.host_os_event.server = NULL;
  command->data.host_os_event.interface = NULL;
  command->data.host_os_event.plugin_id = 0;
  command->data.host_os_event.plugin_sid = 0;
  command->data.host_os_event.log = NULL;
  command->data.host_os_event.id = NULL;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_HOST_OS_EVENT);
  do
  {
    g_scanner_get_next_token (scanner);
 
    switch ((SimCommandSymbolType) scanner->token)
    {
      case SIM_COMMAND_SYMBOL_DATE:
					  g_scanner_get_next_token (scanner); /* = */
					  g_scanner_get_next_token (scanner); /* value */

	  				if (scanner->token != G_TOKEN_STRING)
		    		{
					    command->type = SIM_COMMAND_TYPE_NONE;
	  	  		  break;
		  		  }
            if (sim_string_is_number (scanner->value.v_string, 1))
		  				command->data.host_os_event.date = strtol(scanner->value.v_string,(char **)NULL,10);
						else
						{
              g_message("Error: date field is not in seconds. event incorrect. Please check the date issued from the agent: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
						}
		  			break;

      case SIM_COMMAND_SYMBOL_DATE_STRING:
					  g_scanner_get_next_token (scanner); /* = */
					  g_scanner_get_next_token (scanner); /* value */

	  				if (scanner->token != G_TOKEN_STRING)
		    		{
					    command->type = SIM_COMMAND_TYPE_NONE;
	  	  		  break;
		  		  }

		  			command->data.host_os_event.date_str = g_strdup (scanner->value.v_string);

		  			break;

      case SIM_COMMAND_SYMBOL_DATE_TZONE:
					  g_scanner_get_next_token (scanner); /* = */
					  g_scanner_get_next_token (scanner); /* value */

	  				if (scanner->token != G_TOKEN_STRING)
		    		{
					    command->type = SIM_COMMAND_TYPE_NONE;
	  	  		  break;
		  		  }
            if (sim_string_is_number (scanner->value.v_string, TRUE))
		  				command->data.host_os_event.tzone = g_ascii_strtod(scanner->value.v_string, (gchar**)NULL);
						else
						{
              g_message(" %s: Error: Please check the tzone value: %s. Assumed tzone = 0. Session ip:%s", __func__, scanner->value.v_string,session_ip_str);
							//g_message("Error: date zone is not right. event incorrect. Please check the date tzone issued from the agent: %s", scanner->value.v_string);
              //return FALSE;
						}
		  			break;

			case SIM_COMMAND_SYMBOL_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
            if (sim_string_is_number (scanner->value.v_string, 0))
							command->data.host_os_event.id_transaction = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: Host_OS event incorrect. Please check the id issued from the remote server:-> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;

      case SIM_COMMAND_SYMBOL_CTX:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
              break;
            if ((command->context_id = sim_uuid_new_from_string (scanner->value.v_string)) == NULL)
            {
              g_message("Error: event incorrect. Please check the 'ctx' issued from the remote server: %s", scanner->value.v_string);
              return FALSE;
            }
            break;

      case SIM_COMMAND_SYMBOL_HOST:
	 					g_scanner_get_next_token (scanner); /* = */
			  		g_scanner_get_next_token (scanner); /* value */

			  		if (scanner->token != G_TOKEN_STRING)
				    {
			  		  command->type = SIM_COMMAND_TYPE_NONE;
			    	  break;
				    }
            if (gnet_inetaddr_is_canonical (scanner->value.v_string))
              command->data.host_os_event.host = g_strdup (scanner->value.v_string);
            else
            {
              g_message("Error: Host OS event incorrect. Please check the host ip issued from the agent:-> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
		  			break;

      case SIM_COMMAND_SYMBOL_OS:
					  g_scanner_get_next_token (scanner); /* = */
					  g_scanner_get_next_token (scanner); /* value */

	  				if (scanner->token != G_TOKEN_STRING)
				    {
				      command->type = SIM_COMMAND_TYPE_NONE;
	  	  		  break;
				    }
            command->data.host_os_event.os = (gchar*) g_base64_decode (scanner->value.v_string, &base64len);
            aux = sim_util_substite_problematic_chars (command->data.host_os_event.os, base64len);
            if (aux)
            {
              g_free (command->data.host_os_event.os);
              command->data.host_os_event.os = aux;
            }
						break;

      case SIM_COMMAND_SYMBOL_PLUGIN_ID:
					  g_scanner_get_next_token (scanner); /* = */
					  g_scanner_get_next_token (scanner); /* value */	
	  
	  				if (scanner->token != G_TOKEN_STRING)
				    {
					    command->type = SIM_COMMAND_TYPE_NONE;
	    			  break;
				    }
            if (sim_string_is_number (scanner->value.v_string, 0))
						  command->data.host_os_event.plugin_id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: Host_OS event incorrect. Please check the plugin_id issued from the agent:-> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
			      break;

      case SIM_COMMAND_SYMBOL_SENSOR:
      		  g_scanner_get_next_token (scanner); /* = */
		        g_scanner_get_next_token (scanner); /* value */

    		    if (scanner->token != G_TOKEN_STRING)
        		{
		          command->type = SIM_COMMAND_TYPE_NONE;
    		      break;
        		}
            if (gnet_inetaddr_is_canonical (scanner->value.v_string))
		       		command->data.host_os_event.sensor = g_strdup (scanner->value.v_string);
            else
            {
              g_message("Error: Host OS event incorrect. Please check the sensor issued from the agent: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
    			  break;

      case SIM_COMMAND_SYMBOL_SERVER:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
            {
              command->type = SIM_COMMAND_TYPE_NONE;
              break;
            }
            if (gnet_inetaddr_is_canonical (scanner->value.v_string))
              command->data.host_os_event.server = g_strdup (scanner->value.v_string);
            else
            {
              g_message("Error: Host OS event incorrect. Please check the sensor issued from the server:-> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
            break;


      case SIM_COMMAND_SYMBOL_INTERFACE:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
            {
              command->type = SIM_COMMAND_TYPE_NONE;
              break;
            }

            command->data.host_os_event.interface = g_strdup (scanner->value.v_string);
            break;

      case SIM_COMMAND_SYMBOL_PLUGIN_SID:
					  g_scanner_get_next_token (scanner); /* = */
					  g_scanner_get_next_token (scanner); /* value */
	  
					  if (scanner->token != G_TOKEN_STRING)
				    {
      				command->type = SIM_COMMAND_TYPE_NONE;
				      break;
    				}
	           if (sim_string_is_number (scanner->value.v_string, 0))
						  command->data.host_os_event.plugin_sid = strtol (scanner->value.v_string, (char **) NULL, 10);
    	       else
      	     {
        	     g_message("Error: Host_OS event incorrect. Please check the plugin_sid issued from the agent:-> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
          	   return FALSE;
	           }
  	     		break;

      case SIM_COMMAND_SYMBOL_LOG:
					  g_scanner_get_next_token (scanner); /* = */
					  g_scanner_get_next_token (scanner); /* value */

					  if (scanner->token != G_TOKEN_STRING)
				    {
				      command->type = SIM_COMMAND_TYPE_NONE;
      				break;
				    }
            command->data.host_os_event.log = (gchar*) g_base64_decode (scanner->value.v_string,&base64len);
            aux = sim_util_substite_problematic_chars (command->data.host_os_event.log, base64len);
            if (aux)
            {
              g_free (command->data.host_os_event.log);
              command->data.host_os_event.log = aux;
            }
			      break;
      case SIM_COMMAND_SYMBOL_SRC_IP:
      case SIM_COMMAND_SYMBOL_DST_IP:
			  g_scanner_get_next_token (scanner); /* = */
			  g_scanner_get_next_token (scanner); /* value */
			  break;

      case SIM_COMMAND_SYMBOL_EVENT_ID:
        g_scanner_get_next_token (scanner); /* = */
        g_scanner_get_next_token (scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
        {
          command->type = SIM_COMMAND_TYPE_NONE;
          break;
        }
        command->data.host_os_event.id = sim_uuid_new_from_string(scanner->value.v_string);
        break;

      default:
			  if (scanner->token == G_TOKEN_EOF)
			    break;
			  ossim_debug ( "sim_command_host_os_event_scan: error symbol unknown. Session ip:%s",session_ip_str);
				return FALSE;
		}
 	}
  while(scanner->token != G_TOKEN_EOF);
	return TRUE;
}

/*
 *
 *
 *
 */
static gboolean
sim_command_host_mac_event_scan (SimCommand    *command,
																	GScanner      *scanner,
																	gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner, FALSE);

  gsize base64len;
  gchar *aux;

  command->type = SIM_COMMAND_TYPE_HOST_MAC_EVENT;
  command->data.host_mac_event.date = 0;
  command->data.host_mac_event.date_str = NULL;
  command->data.host_mac_event.tzone = 0;
  command->data.host_mac_event.id_transaction = 0;
  command->data.host_mac_event.host = NULL;
  command->data.host_mac_event.mac = NULL;
  command->data.host_mac_event.vendor = NULL;
  command->data.host_mac_event.sensor = NULL;
  command->data.host_mac_event.server = NULL;
  command->data.host_mac_event.interface = NULL;
  command->data.host_mac_event.plugin_id = 0;
  command->data.host_mac_event.plugin_sid = 0;
  command->data.host_mac_event.log = NULL;
  command->data.host_mac_event.id = NULL;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_HOST_MAC_EVENT);
  do
  {
    g_scanner_get_next_token (scanner);
 
    switch ((SimCommandSymbolType) scanner->token)
    {
      case SIM_COMMAND_SYMBOL_DATE:
					  g_scanner_get_next_token (scanner); /* = */
					  g_scanner_get_next_token (scanner); /* value */

	  				if (scanner->token != G_TOKEN_STRING)
		    		{
					    command->type = SIM_COMMAND_TYPE_NONE;
	  	  		  break;
		  		  }
            if (sim_string_is_number (scanner->value.v_string, 1))
		  				command->data.host_mac_event.date = strtol(scanner->value.v_string,(char **)NULL,10);
						else
						{
              g_message("Error: date field is not in seconds. event incorrect. Please check the date issued from the agent: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;														 
						}
		  			break;

      case SIM_COMMAND_SYMBOL_DATE_STRING:
					  g_scanner_get_next_token (scanner); /* = */
					  g_scanner_get_next_token (scanner); /* value */

	  				if (scanner->token != G_TOKEN_STRING)
		    		{
					    command->type = SIM_COMMAND_TYPE_NONE;
	  	  		  break;
		  		  }

		  			command->data.host_mac_event.date_str = g_strdup (scanner->value.v_string);

		  			break;

      case SIM_COMMAND_SYMBOL_DATE_TZONE:
					  g_scanner_get_next_token (scanner); /* = */
					  g_scanner_get_next_token (scanner); /* value */

	  				if (scanner->token != G_TOKEN_STRING)
		    		{
					    command->type = SIM_COMMAND_TYPE_NONE;
	  	  		  break;
		  		  }
            if (sim_string_is_number (scanner->value.v_string, TRUE))
		  				command->data.host_mac_event.tzone = g_ascii_strtod(scanner->value.v_string, (gchar**)NULL);
						else
						{
              g_message(" %s: Error: Please check the tzone value: %s. Assumed tzone = 0. Session ip:%s", __func__, scanner->value.v_string,session_ip_str);
							//g_message("Error: date zone is not right. event incorrect. Please check the date tzone issued from the agent: %s", scanner->value.v_string);
              //return FALSE;
						}
		  			break;


			case SIM_COMMAND_SYMBOL_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
            if (sim_string_is_number (scanner->value.v_string, 0))
							command->data.host_mac_event.id_transaction = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: Host MAC event incorrect. Please check the id issued from the remote server: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;
       case SIM_COMMAND_SYMBOL_CTX:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
              break;
            if ((command->context_id = sim_uuid_new_from_string (scanner->value.v_string)) == NULL)
            {
              g_message("Error: event incorrect. Please check the 'ctx' issued from the remote server: %s", scanner->value.v_string);
              return FALSE;
            }
            break;
	
      case SIM_COMMAND_SYMBOL_HOST:
					  g_scanner_get_next_token (scanner); /* = */
					  g_scanner_get_next_token (scanner); /* value */

					  if (scanner->token != G_TOKEN_STRING)
				    {
	    			  command->type = SIM_COMMAND_TYPE_NONE;
				      break;
				    }
            if (gnet_inetaddr_is_canonical (scanner->value.v_string))
	 						command->data.host_mac_event.host = g_strdup (scanner->value.v_string);
            else
            {
              g_message("Error: Host MAC event incorrect. Please check the host ip issued from the agent: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;

      case SIM_COMMAND_SYMBOL_MAC:
				  	g_scanner_get_next_token (scanner); /* = */
					  g_scanner_get_next_token (scanner); /* value */

					  if (scanner->token != G_TOKEN_STRING)
				    {
				      command->type = SIM_COMMAND_TYPE_NONE;
	    			  break;
				    }

					  command->data.host_mac_event.mac = g_strdup (scanner->value.v_string);
					  break;

      case SIM_COMMAND_SYMBOL_VENDOR:
					  g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
						{
							command->type = SIM_COMMAND_TYPE_NONE;
							break;
						}

						command->data.host_mac_event.vendor = g_strdup (scanner->value.v_string);
						break;

			case SIM_COMMAND_SYMBOL_SENSOR:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
						{
							command->type = SIM_COMMAND_TYPE_NONE;
							break;
						}
            if (gnet_inetaddr_is_canonical (scanner->value.v_string))
							command->data.host_mac_event.sensor = g_strdup (scanner->value.v_string);
            else
            {
              g_message("Error: Host MAC event incorrect. Please check the sensor issued from the agent:-> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }

						break;

      case SIM_COMMAND_SYMBOL_SERVER:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
            {
              command->type = SIM_COMMAND_TYPE_NONE;
              break;
            }
            if (gnet_inetaddr_is_canonical (scanner->value.v_string))
              command->data.host_mac_event.server = g_strdup (scanner->value.v_string);
            else
            {
              g_message("Error: Host MAC event incorrect. Please check the sensor issued from the server:-> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }

            break;

			case SIM_COMMAND_SYMBOL_PLUGIN_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */
				
						if (scanner->token != G_TOKEN_STRING)
						{
							command->type = SIM_COMMAND_TYPE_NONE;
							break;
						}
            if (sim_string_is_number (scanner->value.v_string, 0))
							command->data.host_mac_event.plugin_id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: Host_MAC event incorrect. Please check the plugin_id issued from the agent:-> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }

						break;
			case SIM_COMMAND_SYMBOL_PLUGIN_SID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */
				
						if (scanner->token != G_TOKEN_STRING)
						{
							command->type = SIM_COMMAND_TYPE_NONE;
							break;
						}
            if (sim_string_is_number (scanner->value.v_string, 0))
							command->data.host_mac_event.plugin_sid = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: Host_MAC event incorrect. Please check the plugin_sid issued from the agent:-> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }

						break;

			case SIM_COMMAND_SYMBOL_INTERFACE:
					  g_scanner_get_next_token (scanner); /* = */
					  g_scanner_get_next_token (scanner); /* value */

			  		if (scanner->token != G_TOKEN_STRING)
				    {
	 			    	command->type = SIM_COMMAND_TYPE_NONE;
	   				  break;
				    }	
	
			 			command->data.host_mac_event.interface = g_strdup (scanner->value.v_string);
						break;


			case SIM_COMMAND_SYMBOL_LOG:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
						{
							command->type = SIM_COMMAND_TYPE_NONE;
							break;
						}
						command->data.host_mac_event.log = (gchar*) g_base64_decode (scanner->value.v_string,&base64len);
            aux = sim_util_substite_problematic_chars (command->data.host_mac_event.log, base64len);
            if (aux)
            {
              g_free (command->data.host_mac_event.log);
              command->data.host_mac_event.log = aux;
            }
						break;
			case SIM_COMMAND_SYMBOL_SRC_IP:
			case SIM_COMMAND_SYMBOL_DST_IP:
				g_scanner_get_next_token (scanner); /* = */
				g_scanner_get_next_token (scanner); /* value */
				break;

      case SIM_COMMAND_SYMBOL_EVENT_ID:
        g_scanner_get_next_token (scanner); /* = */
        g_scanner_get_next_token (scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
        {
          command->type = SIM_COMMAND_TYPE_NONE;
          break;
        }
        command->data.host_mac_event.id = sim_uuid_new_from_string(scanner->value.v_string);
        break;

			default:
				if (scanner->token == G_TOKEN_EOF)
					break;

				g_message ("sim_command_host_mac_event_scan: error symbol unknown. Symbol:%s Session ip:%s",scanner->value.v_string,session_ip_str);
				return FALSE;
    }
  }
  while(scanner->token != G_TOKEN_EOF);
	return TRUE;
}

/*
 *
 * Host service new
 *
 */
static gboolean
sim_command_host_service_event_scan (SimCommand    *command,
																		  GScanner      *scanner,
																		  gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner, FALSE);

  gsize base64len;
  gchar *aux;

  command->type = SIM_COMMAND_TYPE_HOST_SERVICE_EVENT;

  command->data.host_service_event.id_transaction = 0;
  command->data.host_service_event.date = 0;
  command->data.host_service_event.date_str = NULL;
  command->data.host_service_event.tzone = 0;
  command->data.host_service_event.host = NULL;
  command->data.host_service_event.port = 0;
  command->data.host_service_event.protocol = 0;
  command->data.host_service_event.service = NULL;
  command->data.host_service_event.application = NULL;
  command->data.host_service_event.sensor = NULL;
  command->data.host_service_event.server = NULL;
  command->data.host_service_event.interface = NULL;
  command->data.host_service_event.plugin_id = 0;
  command->data.host_service_event.plugin_sid = 0;
  command->data.host_service_event.log = NULL;
  command->data.host_service_event.id = NULL;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_HOST_SERVICE_EVENT);
  do
  {
    g_scanner_get_next_token (scanner);
 
    switch ((SimCommandSymbolType) scanner->token)
    {

      case SIM_COMMAND_SYMBOL_DATE:
					  g_scanner_get_next_token (scanner); /* = */
					  g_scanner_get_next_token (scanner); /* value */

	  				if (scanner->token != G_TOKEN_STRING)
		    		{
					    command->type = SIM_COMMAND_TYPE_NONE;
	  	  		  break;
		  		  }
            if (sim_string_is_number (scanner->value.v_string, 1))
		  				command->data.host_service_event.date = strtol(scanner->value.v_string,(char **)NULL,10);
						else
						{
              g_message("Error: date field is not in seconds. event incorrect. Please check the date issued from the agent:-> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;														 
						}
		  			break;

      case SIM_COMMAND_SYMBOL_DATE_STRING:
					  g_scanner_get_next_token (scanner); /* = */
					  g_scanner_get_next_token (scanner); /* value */

	  				if (scanner->token != G_TOKEN_STRING)
		    		{
					    command->type = SIM_COMMAND_TYPE_NONE;
	  	  		  break;
		  		  }

		  			command->data.host_service_event.date_str = g_strdup (scanner->value.v_string);

		  			break;

      case SIM_COMMAND_SYMBOL_DATE_TZONE:
					  g_scanner_get_next_token (scanner); /* = */
					  g_scanner_get_next_token (scanner); /* value */

	  				if (scanner->token != G_TOKEN_STRING)
		    		{
					    command->type = SIM_COMMAND_TYPE_NONE;
	  	  		  break;
		  		  }
            if (sim_string_is_number (scanner->value.v_string, TRUE))
		  				command->data.host_service_event.tzone = g_ascii_strtod(scanner->value.v_string, (gchar**)NULL);
						else
						{
              g_message(" %s: Error: Please check the tzone value: %s. Assumed tzone = 0.Session ip:%s", __func__, scanner->value.v_string,session_ip_str);
							//g_message("Error: date zone is not right. event incorrect. Please check the date tzone issued from the agent: %s", scanner->value.v_string);
              //return FALSE;
						}
		  			break;

			case SIM_COMMAND_SYMBOL_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
            if (sim_string_is_number (scanner->value.v_string, 0))
							command->data.host_service_event.id_transaction = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: Service event incorrect. Please check the id issued from the remote server: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;

      case SIM_COMMAND_SYMBOL_CTX:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
              break;
            if ((command->context_id = sim_uuid_new_from_string (scanner->value.v_string)) == NULL)
            {
              g_message("Error: event incorrect. Please check the 'ctx' issued from the remote server: %s", scanner->value.v_string);
              return FALSE;
            }
            break;

			case SIM_COMMAND_SYMBOL_HOST:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
						{
							command->type = SIM_COMMAND_TYPE_NONE;
							break;
						}

            if (gnet_inetaddr_is_canonical (scanner->value.v_string))
							command->data.host_service_event.host = g_strdup (scanner->value.v_string);
            else
            {
              g_message("Error: event incorrect. Please check the host issued from the agent: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;

			case SIM_COMMAND_SYMBOL_PORT:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */
						
						if (scanner->token != G_TOKEN_STRING)
						{
							command->type = SIM_COMMAND_TYPE_NONE;
							break;
						}
						if (sim_string_is_number (scanner->value.v_string, 0))
							command->data.host_service_event.port = strtol (scanner->value.v_string, (char **) NULL, 10);
						else
						{
							g_message("Error: Host service event incorrect. Please check the port issued from the agent: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
							return FALSE;
						}
						break;

			case SIM_COMMAND_SYMBOL_PROTOCOL:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */
						
						if (scanner->token != G_TOKEN_STRING)
						{
							command->type = SIM_COMMAND_TYPE_NONE;
							break;
						}

	          if (sim_string_is_number (scanner->value.v_string, 0))
              command->data.host_service_event.protocol = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: host service event incorrect. Please check the protocol issued from the agent: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;

			case SIM_COMMAND_SYMBOL_SERVICE:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
						{
							command->type = SIM_COMMAND_TYPE_NONE;
							break;
						}
						command->data.host_service_event.service = (gchar*) g_base64_decode (scanner->value.v_string, &base64len);
            aux = sim_util_substite_problematic_chars (command->data.host_service_event.service, base64len);
            if (aux)
            {
              g_free (command->data.host_service_event.service);
              command->data.host_service_event.service = aux;
            }
						break;

			case SIM_COMMAND_SYMBOL_APPLICATION:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
						{
							command->type = SIM_COMMAND_TYPE_NONE;
							break;
						}
						command->data.host_service_event.application = g_strdup (scanner->value.v_string);
						break;

      case SIM_COMMAND_SYMBOL_SENSOR:
 						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
						{
							command->type = SIM_COMMAND_TYPE_NONE;
							break;
						}

            if (gnet_inetaddr_is_canonical (scanner->value.v_string))
			        command->data.host_service_event.sensor = g_strdup (scanner->value.v_string);
            else
            {
              g_message("Error: event incorrect. Please check the sensor issued from the agent: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
    			  break;

      case SIM_COMMAND_SYMBOL_SERVER:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
            {
              command->type = SIM_COMMAND_TYPE_NONE;
              break;
            }

            if (gnet_inetaddr_is_canonical (scanner->value.v_string))
              command->data.host_service_event.server = g_strdup (scanner->value.v_string);
            else
            {
              g_message("Error: event incorrect. Please check the sensor issued from the server: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
            break;


      case SIM_COMMAND_SYMBOL_INTERFACE:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
            {
              command->type = SIM_COMMAND_TYPE_NONE;
              break;
            }

            command->data.host_service_event.interface = g_strdup (scanner->value.v_string);
            break;


			case SIM_COMMAND_SYMBOL_LOG:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
						{
							command->type = SIM_COMMAND_TYPE_NONE;
							break;
						}
            command->data.host_service_event.log = (gchar*) g_base64_decode (scanner->value.v_string,&base64len);
            aux = sim_util_substite_problematic_chars (command->data.host_service_event.log, base64len);
            if (aux)
            {
              g_free (command->data.host_service_event.log);
              command->data.host_service_event.log = aux;
            }
						break;


			case SIM_COMMAND_SYMBOL_PLUGIN_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */
						
						if (scanner->token != G_TOKEN_STRING)
						{
							command->type = SIM_COMMAND_TYPE_NONE;
							break;
						}
            if (sim_string_is_number (scanner->value.v_string, 0))
							command->data.host_service_event.plugin_id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: Host_service event incorrect. Please check the plugin_id issued from the agent:-> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }

						break;
				
			case SIM_COMMAND_SYMBOL_PLUGIN_SID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */
						
						if (scanner->token != G_TOKEN_STRING)
						{
							command->type = SIM_COMMAND_TYPE_NONE;
							break;
						}
            if (sim_string_is_number (scanner->value.v_string, 0))
							command->data.host_service_event.plugin_sid = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: host service event incorrect. Please check the plugin_sid issued from the agent: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
						break;
			case SIM_COMMAND_SYMBOL_SRC_IP:
			case SIM_COMMAND_SYMBOL_DST_IP:
				g_scanner_get_next_token (scanner); /* = */
				g_scanner_get_next_token (scanner); /* value */
				break;

      case SIM_COMMAND_SYMBOL_EVENT_ID:
        g_scanner_get_next_token (scanner); /* = */
        g_scanner_get_next_token (scanner); /* value */

        if (scanner->token != G_TOKEN_STRING)
        {
          command->type = SIM_COMMAND_TYPE_NONE;
          break;
        }
        command->data.host_service_event.id = sim_uuid_new_from_string(scanner->value.v_string);
        break;


			default:
						if (scanner->token == G_TOKEN_EOF)
							break;
						ossim_debug ( "sim_command_host_service_event_scan: error symbol unknown.value:%s Session ip:%s",scanner->value.v_string,session_ip_str);
						return FALSE;
    }
  }
  while(scanner->token != G_TOKEN_EOF);
	return TRUE;
}

/*
 * This is an answer from a children server to a SIM_COMMAND_SYMBOL_SERVER_GET_SENSORS query made in this server (or in a master server and
 * resended here) and sended to children. This is only needed to resend it to a master server or the
 * frameworkd
 */
static gboolean
sim_command_sensor_scan (SimCommand    *command,
												GScanner      *scanner,
												gchar * session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner != NULL, FALSE);

  command->type = SIM_COMMAND_TYPE_SENSOR;
  command->data.sensor.host = NULL;
  command->data.sensor.state = 0;
  command->data.sensor.servername = NULL;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_SENSOR);
  do
  {
    g_scanner_get_next_token (scanner);
 
    switch ((SimCommandSymbolType) scanner->token)
    {
      case SIM_COMMAND_SYMBOL_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;

            if (sim_string_is_number (scanner->value.v_string, 0))
							command->id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: sensor event incorrect. Please check the id issued from the children server: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }

						break;

			case SIM_COMMAND_SYMBOL_HOST:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
						command->data.sensor.host = g_strdup (scanner->value.v_string);
						break;

			//FIXME: not used
			case SIM_COMMAND_SYMBOL_STATE:
						g_scanner_get_next_token (scanner); 
						g_scanner_get_next_token (scanner); 

						if (scanner->token != G_TOKEN_STRING)
							break;
/*
						if (g_ascii_strcasecmp (scanner->value.v_string, "start"))
							command->data.sensorgin.state = 1;
						else if (g_ascii_strcasecmp (scanner->value.v_string, "stop"))
							command->data.sensor_plugin.state = 2;
						else if (g_ascii_strcasecmp (scanner->value.v_string, "unknown"))
							command->data.sensor_plugin.state = 3;
		*/
						break;

      case SIM_COMMAND_SYMBOL_SERVERNAME:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
            {
              command->type = SIM_COMMAND_TYPE_NONE;
              break;
            }

            if (scanner->value.v_string)
              command->data.sensor.servername = g_strdup (scanner->value.v_string);
            else
            {
              g_message("Error: sensor; Server Name incorrect. Please check the server name issued from the children server:-> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
            break;

			default:
						if (scanner->token == G_TOKEN_EOF)
							break;
					  ossim_debug ( "sim_command_sensor_scan: error symbol unknown. Session ip:%s",session_ip_str);
						return FALSE;
      }
  }
  while(scanner->token != G_TOKEN_EOF);
	return TRUE;
}

/*
 * This is an answer from a children server to a SIM_COMMAND_SYMBOL_SERVER_GET_SERVERS query made in this server (or in a master server and
 * resended here) and sended to children. This is only needed to resend it to a master server or the
 * frameworkd
 */
static gboolean
sim_command_server_scan (SimCommand    *command,
												GScanner      *scanner,
												gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner != NULL, FALSE);

  command->type = SIM_COMMAND_TYPE_SERVER;
  command->data.server.host = NULL;
  command->data.server.servername = NULL;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_SERVER);
  do
  {
    g_scanner_get_next_token (scanner);
 
    switch ((SimCommandSymbolType) scanner->token)
    {
      case SIM_COMMAND_SYMBOL_ID:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;

            if (sim_string_is_number (scanner->value.v_string, 0))
							command->id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: server answer incorrect. Please check the id issued from the children server: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }

						break;

			case SIM_COMMAND_SYMBOL_HOST:
						g_scanner_get_next_token (scanner); /* = */
						g_scanner_get_next_token (scanner); /* value */

						if (scanner->token != G_TOKEN_STRING)
							break;
						command->data.server.host = g_strdup (scanner->value.v_string);
						break;

      case SIM_COMMAND_SYMBOL_SERVERNAME:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
            {
              command->type = SIM_COMMAND_TYPE_NONE;
              break;
            }

            if (scanner->value.v_string)
              command->data.server.servername = g_strdup (scanner->value.v_string);
            else
            {
              g_message("Error: server; Server Name incorrect. Please check the server name issued from the children server: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
            break;

			default:
						if (scanner->token == G_TOKEN_EOF)
							break;
					  ossim_debug ( "sim_command_server_scan: error symbol unknown. Session ip:%s ", session_ip_str);
						return FALSE;
      }
  }
  while(scanner->token != G_TOKEN_EOF);
	return TRUE;
}



/*
 * OK response
 *
 */
static gboolean
sim_command_ok_scan (SimCommand    *command,
                     GScanner      *scanner,
                     gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner, FALSE);

  command->type = SIM_COMMAND_TYPE_OK;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_OK);
  do
  {
    g_scanner_get_next_token (scanner);

    switch ((SimCommandSymbolType) scanner->token)
    {
      case SIM_COMMAND_SYMBOL_ID:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            if (scanner->token != G_TOKEN_STRING)
              break;
            if (sim_string_is_number (scanner->value.v_string, 0))
              command->id = strtol (scanner->value.v_string, (char **) NULL, 10);
            else
            {
              g_message("Error: OK event incorrect. Please check the id issued from the remote machine:-> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
              return FALSE;
            }
            break;

      default:
            if (scanner->token == G_TOKEN_EOF)
              break;
            ossim_debug ( "sim_command_ok_scan: error symbol unknown.Session ip:%s",session_ip_str);
            return FALSE;
    }
  }
  while(scanner->token != G_TOKEN_EOF);
  return TRUE;
}

/*
 * ERROR response
 *
 */
static gboolean
sim_command_error_scan (SimCommand    *command,
                        GScanner      *scanner,
                        gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner, FALSE);

  command->type = SIM_COMMAND_TYPE_ERROR;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_ERROR);
  do
  {
    g_scanner_get_next_token (scanner);

    switch ((SimCommandSymbolType) scanner->token)
    {
		case SIM_COMMAND_SYMBOL_ID:
			g_scanner_get_next_token (scanner); /* = */
			g_scanner_get_next_token (scanner); /* value */

			if (scanner->token != G_TOKEN_STRING)
				break;
			if (sim_string_is_number (scanner->value.v_string, 0))
				command->id = strtol (scanner->value.v_string, (char **) NULL, 10);
			else
			{
				g_message("Error: ERROR event incorrect. Please check the id issued from the remote machine:-> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
				return FALSE;
			}
			break;

		default:
			if (scanner->token == G_TOKEN_EOF)
				break;
			ossim_debug ("%s: error symbol unknown.Session ip:%s", __func__, session_ip_str);
			return FALSE;
    }
  }
  while(scanner->token != G_TOKEN_EOF);
  return TRUE;
}


static gboolean
sim_command_pong_scan (SimCommand *command,
                       GScanner   *scanner,
								       gchar      *session_ip_str)
{
  // unused parameter
  (void) scanner;
  (void) session_ip_str;

  command->type = SIM_COMMAND_TYPE_PONG;

  return TRUE;
}

/*
 * DEPRECATED - kept for backward compatibility
 * Scan and store the query which has arrived to this server. May be executed here or in an upper server (depending on serv
 */
static gboolean
sim_command_database_query_scan (SimCommand    *command,
                                 GScanner      *scanner,
                                 gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner != NULL, FALSE);

  command->type = SIM_COMMAND_TYPE_DATABASE_QUERY;
  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_DATABASE_QUERY);
  do
  {
    g_scanner_get_next_token (scanner);

    switch ((SimCommandSymbolType) scanner->token)
    {
      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token (scanner);     /* = */
        g_scanner_get_next_token (scanner);     /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (sim_string_is_number (scanner->value.v_string, 0))
          command->id = strtol (scanner->value.v_string, (char **) NULL, 10);
        else
        {
          g_message ("Error: database query event incorrect. Please check the symbol_id issued from the other server:-> value: %s,session_ip:%s", scanner->value.v_string, session_ip_str);
          return FALSE;
        }

        break;

      case SIM_COMMAND_SYMBOL_DATABASE_ELEMENT_TYPE:
        g_scanner_get_next_token (scanner);     /* = */
        g_scanner_get_next_token (scanner);     /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        if (sim_string_is_number (scanner->value.v_string, 0))
          command->data.database_query.database_element_type = strtol (scanner->value.v_string, (char **) NULL, 10);
        else
        {
          g_message ("Error: Database query event incorrect. Please check the id issued from the remote machine: -> value: %s,session_ip:%s", scanner->value.v_string, session_ip_str);
          return FALSE;
        }

        break;

      case SIM_COMMAND_SYMBOL_SERVERNAME:
      case SIM_COMMAND_SYMBOL_SENSORNAME:      //we will use the servername variable to store the name of the connected machine, regardless
//its a server or a sensor. The sensor must be able to ask only for its Policy

        g_scanner_get_next_token (scanner);     /* = */
        g_scanner_get_next_token (scanner);     /* value */

        if (scanner->token != G_TOKEN_STRING)
        {
          command->type = SIM_COMMAND_TYPE_NONE;
          break;
        }

        if (scanner->value.v_string)
          command->data.database_query.servername = g_strdup (scanner->value.v_string);
        else
        {
          g_message ("Error: Database query; Server Name incorrect. Please check the server name issued from the children server:-> value: %s,session_ip:%s", scanner->value.v_string, session_ip_str);
          return FALSE;
        }
        break;
/*                                             
                       case SIM_COMMAND_SYMBOL_QUERY:
                                               g_scanner_get_next_token (scanner); 
                                               g_scanner_get_next_token (scanner); 

                                               if (scanner->token != G_TOKEN_STRING)
                                                       break;
                                               command->data.database_query.query = g_strdup (scanner->value.v_string);
                                               break;
                                               */

      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        ossim_debug ("sim_command_database_query_scan: error symbol unknown: -> value: %s,session_ip:%s", scanner->value.v_string, session_ip_str);
        return FALSE;
    }
  }
  while (scanner->token != G_TOKEN_EOF);

  return TRUE;
}

/*
 * DEPRECATED - kept for backward compatibility
 * Scan and store the query answer which has arrived here from a master server.
 */
static gboolean
sim_command_database_answer_scan (SimCommand    *command,
                                  GScanner      *scanner,
                                  gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner != NULL, FALSE);

  command->type = SIM_COMMAND_TYPE_DATABASE_ANSWER;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_DATABASE_ANSWER);
  do
  {
    g_scanner_get_next_token (scanner);

    switch ((SimCommandSymbolType) scanner->token)
    {
      case SIM_COMMAND_SYMBOL_ID:
        g_scanner_get_next_token (scanner);     /* = */
        g_scanner_get_next_token (scanner);     /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;

        if (sim_string_is_number (scanner->value.v_string, 0))
          command->id = strtol (scanner->value.v_string, (char **) NULL, 10);
        else
        {
          g_message ("Error: database answer event incorrect. Please check the symbol_id issued from the other server: -> value: %s,session_ip:%s", scanner->value.v_string, session_ip_str);
          return FALSE;
        }
        break;

      case SIM_COMMAND_SYMBOL_DATABASE_ELEMENT_TYPE:
        g_scanner_get_next_token (scanner);     /* = */
        g_scanner_get_next_token (scanner);     /* value */

        if (scanner->token != G_TOKEN_STRING)
          break;
        if (sim_string_is_number (scanner->value.v_string, 0))
          command->data.database_answer.database_element_type = strtol (scanner->value.v_string, (char **) NULL, 10);
        else
        {
          g_message ("Error: Database answer event incorrect. Please check the id issued from the remote machine: -> value: %s,session_ip:%s", scanner->value.v_string, session_ip_str);
          return FALSE;
        }

        break;
      case SIM_COMMAND_SYMBOL_SERVERNAME:
        g_scanner_get_next_token (scanner);     /* = */
        g_scanner_get_next_token (scanner);     /* value */

        if (scanner->token != G_TOKEN_STRING)
        {
          command->type = SIM_COMMAND_TYPE_NONE;
          break;
        }

        if (scanner->value.v_string)
          command->data.database_answer.servername = g_strdup (scanner->value.v_string);
        else
        {
          g_message ("Error: Database answer; Server Name incorrect. Please check the server name issued from the master server: -> value: %s,session_ip:%s", scanner->value.v_string, session_ip_str);
          return FALSE;
        }
        break;

      case SIM_COMMAND_SYMBOL_ANSWER:
        g_scanner_get_next_token (scanner);     /* = */
        g_scanner_get_next_token (scanner);     /* value */

        if (scanner->token != G_TOKEN_STRING)
        {
          command->type = SIM_COMMAND_TYPE_NONE;
          break;
        }

        if (scanner->value.v_string)
          command->data.database_answer.answer = g_strdup (scanner->value.v_string);
        else
        {
          g_message ("Error: Database answer; No answer. Please check the answer issued from the master server: -> value: %s,session_ip:%s", scanner->value.v_string, session_ip_str);
          return FALSE;
        }
        break;
      default:
        if (scanner->token == G_TOKEN_EOF)
          break;
        ossim_debug ("sim_command_database_query_scan: error symbol unknown.Session ip: %s", session_ip_str);
        return FALSE;
    }
  }
  while (scanner->token != G_TOKEN_EOF);

  return TRUE;
}

/*
 *
 */
static gboolean
sim_command_agent_date_scan (SimCommand    *command,
                              GScanner      *scanner,
                              gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner != NULL, FALSE);

  command->type = SIM_COMMAND_TYPE_AGENT_DATE;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_AGENT_DATE);
  do
  {
    g_scanner_get_next_token (scanner);

    switch ((SimCommandSymbolType) scanner->token)
    {
      case SIM_COMMAND_SYMBOL_AGENT__DATE:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */


            break;

      case SIM_COMMAND_SYMBOL_DATE_TZONE:
            g_scanner_get_next_token (scanner); /* = */
            g_scanner_get_next_token (scanner); /* value */

            break;

      default:
            if (scanner->token == G_TOKEN_EOF)
              break;
            ossim_debug ( "sim_command_agent_date_scan: error symbol unknown.Session ip:%s ",session_ip_str);
            return FALSE;
    }
  }
  while(scanner->token != G_TOKEN_EOF);

  return TRUE;
}


/*
 *
 *
 *
 */
gchar*
sim_command_get_string (SimCommand    *command)
{
  GString  *gstr;
  gchar    *str = NULL;
  gchar    *value = NULL;
  gchar    *state;
  gchar    *ip_str;
  gchar    *base64;

  g_return_val_if_fail (SIM_IS_COMMAND (command), NULL);

  switch (command->type)
  {
    case SIM_COMMAND_TYPE_OK:
		      str = g_strdup_printf ("ok id=\"%d\"\n", command->id);
				  break;

    case SIM_COMMAND_TYPE_ERROR:
		      str = g_strdup_printf ("error id=\"%d\"\n", command->id);
				  break;

    case SIM_COMMAND_TYPE_PING:
          str = g_strdup ("ping\n");
          break;

  case SIM_COMMAND_TYPE_NOACK:
    str = g_strdup_printf ("noack id=\"%d\" your_sensor_id=\"%s\"\n", command->id, command->data.noack.your_sensor_id);
    break;

    case SIM_COMMAND_TYPE_CONNECT:
		      switch (command->data.connect.type)
					{
						case SIM_SESSION_TYPE_SERVER_UP:
						case SIM_SESSION_TYPE_SERVER_DOWN:
								  value = g_strdup ("server");
								  break;
						case SIM_SESSION_TYPE_SENSOR:
								  value = g_strdup ("sensor");
								  break;
						case SIM_SESSION_TYPE_FRAMEWORKD:
									value = g_strdup ("frameworkd");
									break;
						case SIM_SESSION_TYPE_WEB:
								  value = g_strdup ("web");
								  break;
						default:
								  value = g_strdup ("none");
					}

		      str = g_strdup_printf ("connect id=\"%d\" type=\"%s\" hostname=\"%s\" version=\"%d.%d.%d\"\n", command->id, value, command->data.connect.hostname,command->data.connect.sensor_ver->major,command->data.connect.sensor_ver->minor,command->data.connect.sensor_ver->micro);
				  g_free (value);
		      break;

		case SIM_COMMAND_TYPE_SERVER_SET_DATA_ROLE:
			str = g_strdup_printf ("server-set-data-role id=\"%d\" servername=\"%s\" role_correlate=\"%d\" role_cross_correlate=\"%d\" role_reputation=\"%d\" role_logger_if_priority=\"%d\" role_store=\"%d\" role_qualify=\"%d\"\n",
                                   command->id,
                                   command->data.server_set_data_role.servername,
                                   command->data.server_set_data_role.correlate, 
                                   command->data.server_set_data_role.cross_correlate,
                                   command->data.server_set_data_role.reputation,
                                   command->data.server_set_data_role.logger_if_priority,
                                   command->data.server_set_data_role.store,
                                   command->data.server_set_data_role.qualify);
					break;

    case SIM_COMMAND_TYPE_EVENT:
				  str = sim_event_to_string (command->data.event.event);
		      break;

    case SIM_COMMAND_TYPE_WATCH_RULE:
				  if (!command->data.watch_rule.str)
						break;

		      str = g_strdup (command->data.watch_rule.str);
		      break;

    case SIM_COMMAND_TYPE_SENSOR:
				  str = g_strdup_printf ("sensor host=\"%s\" state=\"%s\" servername=\"%s\" id=\"%d\"\n", 
															   command->data.sensor.host,
														     (command->data.sensor.state) ? "on" : "off",
                                 command->data.sensor.servername,
																 command->id);
		      break;

    case SIM_COMMAND_TYPE_SERVER:
				  str = g_strdup_printf ("server host=\"%s\" servername=\"%s\" id=\"%d\"\n", 
															   command->data.server.host,
                                 command->data.server.servername,
																 command->id);
		      break;


    case SIM_COMMAND_TYPE_SENSOR_PLUGIN:
				  switch (command->data.sensor_plugin.state)
					{
						case 1:
						  state = g_strdup ("start");
						  break;
						case 2:
						  state = g_strdup ("stop");
						  break;
						case 3:
						  state = g_strdup ("unknown");
						  break;
						default:
						  state = g_strdup ("unknown");
					}

		      str = g_strdup_printf ("sensor-plugin sensor=\"%s\" plugin_id=\"%d\" state=\"%s\" enabled=\"%s\"\n",
															     command->data.sensor_plugin.sensor,
															     command->data.sensor_plugin.plugin_id,
																   state,
																	 (command->data.sensor_plugin.enabled) ? "true" : "false");

		      g_free (state);
				  break;
    case SIM_COMMAND_TYPE_SENSOR_PLUGIN_START:
		      str = g_strdup_printf ("sensor-plugin-start plugin_id=\"%d\"\n", command->data.sensor_plugin_start.plugin_id);
				  break;
    case SIM_COMMAND_TYPE_SENSOR_PLUGIN_STOP:
		      str = g_strdup_printf ("sensor-plugin-stop plugin_id=\"%d\"\n", command->data.sensor_plugin_stop.plugin_id);
				  break;
    case SIM_COMMAND_TYPE_SENSOR_PLUGIN_ENABLE:
		      str = g_strdup_printf ("sensor-plugin-enable plugin_id=\"%d\"\n", command->data.sensor_plugin_enable.plugin_id);
				  break;
    case SIM_COMMAND_TYPE_SENSOR_PLUGIN_DISABLE:
		      str = g_strdup_printf ("sensor-plugin-disable plugin_id=\"%d\"\n", command->data.sensor_plugin_disable.plugin_id);
				  break;
	  case SIM_COMMAND_TYPE_SENSOR_GET_EVENTS:
		      str = g_strdup_printf ("sensor-get-events id=\"%u\" num_events=\"%u\"\n", command->id, command->data.sensor_get_events.num_events); 
				  break;
    case SIM_COMMAND_TYPE_DATABASE_QUERY:
          g_message ("%s: SIM_COMMAND_TYPE_DATABASE_QUERY is deprecated, cannot get the string", __func__);
          break;
    case SIM_COMMAND_TYPE_DATABASE_ANSWER:
          g_message ("%s: SIM_COMMAND_TYPE_DATABASE_ANSWER is deprecated, cannot get the string", __func__);
          break;
		case SIM_COMMAND_TYPE_BACKLOG:
					str = g_strdup_printf ("backlog-event uuid=\"00000000-0000-0000-0000-000000000000\" uuid_event=\"00000000-0000-0000-0000-000000000000\"");
					break;
		case SIM_COMMAND_TYPE_FRMK_GETDB:
		      str
		          = g_strdup_printf(
		              "server-get-db: HOST=\"%s\" PORT=\"%d\" DBNAME=\"%s\" USER=\"%s\" PASSWORD=\"%s\" \n",
		              command->data.snort_database_data.dbhost,
		              command->data.snort_database_data.dbport,
		              command->data.snort_database_data.dbname,
		              command->data.snort_database_data.dbuser,
		              command->data.snort_database_data.dbpassword);
		  break;
		case SIM_COMMAND_TYPE_SENSOR_GET_FRAMEWORK:
		      str
		          = g_strdup_printf(
		              "server_ip=\"%s\" server_name=\"%s\" server_port=\"%d\" framework_ip=\"%s\" framework_name=\"%s\" framework_port=\"%d\" \n",
		              command->data.framework_data.server_host,
		              command->data.framework_data.server_name,
		              command->data.framework_data.server_port,
		              command->data.framework_data.framework_host,
		              command->data.framework_data.framework_name,
		              command->data.framework_data.framework_port);
					break;
    case SIM_COMMAND_TYPE_IDM_EVENT:
      gstr = g_string_new("idm-event");
      if (command->context_id)
        g_string_append_printf(gstr, " ctx=\"%s\" ", sim_uuid_get_string (command->context_id));
      if (command->data.idm_event.ip)
      {
        ip_str = sim_inet_get_canonical_name (command->data.idm_event.ip);
        g_string_append_printf(gstr, " ip=\"%s\"", ip_str);
        g_free (ip_str);
      }
      if (command->data.idm_event.username)
      {
        base64 = g_base64_encode ((const guchar *) command->data.idm_event.username, strlen (command->data.idm_event.username));
        g_string_append_printf(gstr, " username=\"%s\"", base64);
        g_free (base64);

        g_string_append_printf(gstr, " login=\"%d\"", command->data.idm_event.is_login);
      }
      if (command->data.idm_event.hostname)
        g_string_append_printf(gstr, " hostname=\"%s\"", command->data.idm_event.hostname);
      if (command->data.idm_event.mac)
        g_string_append_printf(gstr, " mac=\"%s\"", command->data.idm_event.mac);
      if (command->data.idm_event.os)
      {
        base64 = g_base64_encode ((const guchar *) command->data.idm_event.os, strlen (command->data.idm_event.os));
        g_string_append_printf(gstr, " os=\"%s\"", base64);
        g_free (base64);
      }
      if (command->data.idm_event.cpu)
      {
        base64 = g_base64_encode ((const guchar *) command->data.idm_event.cpu, strlen (command->data.idm_event.cpu));
        g_string_append_printf(gstr, " cpu=\"%s\"", base64);
        g_free (base64);
      }
      if (command->data.idm_event.memory)
        g_string_append_printf(gstr, " memory=\"%d\"", command->data.idm_event.memory);
      if (command->data.idm_event.video)
      {
        base64 = g_base64_encode ((const guchar *) command->data.idm_event.video, strlen (command->data.idm_event.video));
        g_string_append_printf(gstr, " video=\"%s\"", base64);
        g_free (base64);
      }
      if (command->data.idm_event.service)
      {
        base64 = g_base64_encode ((const guchar *) command->data.idm_event.service, strlen (command->data.idm_event.service));
        g_string_append_printf(gstr, " service=\"%s\"", base64);
        g_free (base64);
      }
      if (command->data.idm_event.state)
        g_string_append_printf(gstr, " state=\"%s\"", command->data.idm_event.state);
      if (command->data.idm_event.inventory_source)
        g_string_append_printf(gstr, " inventory_source=\"%d\"", command->data.idm_event.inventory_source);
      if (command->data.idm_event.host_id)
        g_string_append_printf(gstr, " host=\"%s\"", sim_uuid_get_string (command->data.idm_event.host_id));

      g_string_append_printf(gstr, "\n");

      str = g_string_free(gstr, FALSE);
      break;
		case SIM_COMMAND_TYPE_PLUGIN_DISABLED:
		      str = g_strdup_printf ("plugin-disabled plugin_id=\"%d\"\n", command->data.plugin_disabled.plugin_id);
				  break;
		case SIM_COMMAND_TYPE_AGENT_PING:
	      str = g_strdup_printf ("server pong\n");
    default:
		      ossim_debug ( "sim_command_get_string: error command unknown");
				  break;
	}

  return str;
}

/*
 * Transforms the data received in a new event object. Returns it.
 * TODO: Pass the pointer of the event and make the other tasks in the Event struct inside the command and prevent the finalize function to destroy that pointer to event by creating a flag.
 *  That flag should be only enabled if the event is pushed to the pool.
 *
 */
SimEvent*
sim_command_get_event (SimCommand     *command)
{
  SimEventType   type;
  SimEvent      *event;
  struct tm      tm;
  SimPluginSid * plugin_sid;
  gchar        * plugin_sid_name;
  gchar        * aux_chr;
  gsize          aux_size;
  GString      * aux;
  SimInet      * inet;
  SimNetwork   * home_net;

  g_return_val_if_fail (SIM_IS_COMMAND (command), NULL);

  if (command->type != SIM_COMMAND_TYPE_EVENT &&
      command->type != SIM_COMMAND_TYPE_SNORT_EVENT)
  {
    return NULL;
  }

  g_return_val_if_fail (command->data.event.type, NULL);

  type = sim_event_get_type_from_str (command->data.event.type); //monitor or detector?

  if (type == SIM_EVENT_TYPE_NONE)
    return NULL;

  // creates a new event.
  event = sim_event_new_full (type, command->data.event.id, command->context_id);

  if (command->data.event.date)
  {
    event->time = command->data.event.date;
    ossim_debug ( "sim_command_get_event event->time= %lu", (unsigned long)event->time);
    event->diff_time = (time (NULL) > event->time) ? (time (NULL) - event->time) : 0;
    ossim_debug ( "sim_command_get_event event->diff_time= %lu", (unsigned long)event->diff_time);
  }
  else
  {
    if (command->data.event.date_str)
    {
      if (strptime (command->data.event.date_str, "%Y-%m-%d %H:%M:%S", &tm))
        event->time = mktime (&tm);
    }
    else
    {
      g_message ("Event %s without date, setting local time", sim_uuid_get_string (event->id));
      event->time = time (NULL);
    }
  }

  if (command->data.event.date_str)
  {
    event->time_str = command->data.event.date_str;
    command->data.event.date_str = NULL;
  }

  if (command->data.event.tzone)
    event->tzone = command->data.event.tzone;

  if (command->data.event.sensor &&
      strcmp (command->data.event.sensor, "None") &&
      strcmp (command->data.event.sensor, "none"))
  {
    event->sensor = sim_inet_new_from_string (command->data.event.sensor);
  }
  else
  {
    event->sensor = sim_inet_new_from_string ("0.0.0.0");
  }

  if (command->data.event.sensor_id)
    event->sensor_id = g_object_ref (command->data.event.sensor_id);

  if (command->data.event.device)
    event->device = sim_inet_new_from_string (command->data.event.device);

  event->device_id = command->data.event.device_id;

  if (command->data.event.server)
  {
    event->server = command->data.event.server;
    command->data.event.server = NULL;
  }

  if (command->data.event.servername)
  {
    event->servername = command->data.event.servername;
    command->data.event.servername = NULL;
  }

  if (command->data.event.interface)
  {
    event->interface = command->data.event.interface;
    command->data.event.interface = NULL;

    ossim_debug ("%s: Interface: %s", __func__, event->interface);
  }
  else //FIXME: this is a piece of shit. event->interface must be removed from all the code. In the meantime, this silly "fix" is used.
  {
    event->interface = g_strdup_printf ("none");
  }

  if (command->data.event.plugin_id)
  {
    SimPlugin *plugin = sim_context_get_plugin (event->context, command->data.event.plugin_id);

    // Test first if this event has a correct plugin id.
    if (plugin)
    {
      g_object_unref (plugin);
    }
    else
    {
      g_message ("Event with plugin id unknown: %d", command->data.event.plugin_id);
      sim_context_add_new_plugin (event->context, command->data.event.plugin_id);
    }

    event->plugin_id = command->data.event.plugin_id;
  }
  else
  {
    g_warning ("Event discarded by lack of plugin id: %s",
               command->data.event.log ? command->data.event.log->str : "");
    sim_event_unref (event);
    return NULL;
  }

  // Test if this plugin sid exists. If it doesn't, change it for a generic value and modify the log event field.
  if (!(plugin_sid = sim_context_get_plugin_sid (event->context, event->plugin_id, command->data.event.plugin_sid)))
  {
    g_message ("Event with plugin id %d with unknown plugin sid %d", event->plugin_id, command->data.event.plugin_sid);

    aux = g_string_new ("[ Unknown plugin sid: ");
    g_string_append_printf (aux, "%d ]", command->data.event.plugin_sid);
    if (command->data.event.log)
    {
      g_string_append_c (aux, ' ');
      g_string_append_len (aux, command->data.event.log->str, command->data.event.log->len);
      g_string_free (command->data.event.log, TRUE);
      command->data.event.log = NULL;
    }
    event->log = aux;
    event->plugin_sid = SIM_PLUGIN_SID_NONEXISTENT;
  }
  else
  {
    event->plugin_sid = command->data.event.plugin_sid;
  }

  if (command->data.event.protocol)
  {
    event->protocol = sim_protocol_get_type_from_str (command->data.event.protocol);

    if (event->protocol == SIM_PROTOCOL_TYPE_NONE)
    {
      if (sim_string_is_number (command->data.event.protocol, 0))
        event->protocol = (SimProtocolType) atoi(command->data.event.protocol);
      else
      {
        event->protocol = SIM_PROTOCOL_TYPE_OTHER;
      }
    }
  }
  else
    //If no protocol is defined use TCP, this allow using port filters in base
    //forensics console
    event->protocol = SIM_PROTOCOL_TYPE_TCP;

  // Set source and destination addresses, or generic ones if they're not valid.
  if ((event->src_ia = sim_inet_new_from_string (command->data.event.src_ip)) == NULL)
    event->src_ia = sim_inet_new_none ();

  if ((event->dst_ia = sim_inet_new_from_string (command->data.event.dst_ip)) == NULL)
    event->dst_ia = sim_inet_new_none ();

  // Search for source and destination networks.
  home_net = sim_context_get_home_net (event->context);

  if (command->data.event.src_net)
  {
    event->src_net = sim_net_new_void (command->data.event.src_net);
  }
  else
  {
    if ((inet = sim_network_search_inet (home_net, event->src_ia)))
      event->src_net = g_object_ref (sim_inet_get_parent_net (inet));
  }

  if (command->data.event.dst_net)
  {
    event->dst_net = sim_net_new_void (command->data.event.dst_net);
  }
  else
  {
    if ((inet = sim_network_search_inet (home_net, event->dst_ia)))
      event->dst_net = g_object_ref (sim_inet_get_parent_net (inet));
  }

  if (command->data.event.src_port)
    event->src_port = command->data.event.src_port;
  if (command->data.event.dst_port)
    event->dst_port = command->data.event.dst_port;

  if (command->data.event.condition)
    event->condition = sim_condition_get_type_from_str (command->data.event.condition);

  if (command->data.event.value)
  {
    event->value = command->data.event.value;
    command->data.event.value = NULL;
  }
  if (command->data.event.interval)
    event->interval = command->data.event.interval;

  if (command->data.event.data)
  {
    event->data = command->data.event.data;
    command->data.event.data = NULL;
  }

  ossim_debug ( "sim_command_get_event data1: %s", event->data);
  ossim_debug ( "sim_command_get_event data2: %s", command->data.event.data);

  if (command->data.event.log)
  {
    event->log = command->data.event.log;
    command->data.event.log = NULL;
  }

  event->asset_src = command->data.event.asset_src;
  event->asset_dst = command->data.event.asset_dst;

  event->reliability = command->data.event.reliability;
  event->risk_a = command->data.event.risk_a;
  event->risk_c = command->data.event.risk_c;
  event->alarm = command->data.event.alarm;
  event->belongs_to_alarm=FALSE;  //This is set to TRUE only in correlation
  event->is_priority_set = command->data.event.is_priority_set;
  event->is_reliability_set = command->data.event.is_reliability_set;


  if (command->data.event.priority)
  {
    if (command->data.event.priority < 0)
      event->priority = 0;
    else if (command->data.event.priority > 5)
      event->priority = 5;
    else
      event->priority = command->data.event.priority;
  }

  if (command->data.event.filename)
  {
    event->filename = command->data.event.filename;
    command->data.event.filename = NULL;
  }
  if (command->data.event.username)
  {
    event->username = command->data.event.username;
    command->data.event.username = NULL;
  }
  if (command->data.event.password)
  {
    event->password = command->data.event.password;
    command->data.event.password = NULL;
  }
  if (command->data.event.userdata1)
  {
    event->userdata1 = command->data.event.userdata1;
    command->data.event.userdata1 = NULL;
  }
  if (command->data.event.userdata2)
  {
    event->userdata2 = command->data.event.userdata2;
    command->data.event.userdata2 = NULL;
  }
  if (command->data.event.userdata3)
  {
    event->userdata3 = command->data.event.userdata3;
    command->data.event.userdata3 = NULL;
  }
  if (command->data.event.userdata4)
  {
    event->userdata4 = command->data.event.userdata4;
    command->data.event.userdata4 = NULL;
  }
  if (command->data.event.userdata5)
  {
    event->userdata5 = command->data.event.userdata5;
    command->data.event.userdata5 = NULL;
  }
  if (command->data.event.userdata6)
  {
    event->userdata6 = command->data.event.userdata6;
    command->data.event.userdata6 = NULL;
  }
  if (command->data.event.userdata7)
  {
    event->userdata7 = command->data.event.userdata7;
    command->data.event.userdata7 = NULL;
  }
  if (command->data.event.userdata8)
  {
    event->userdata8 = command->data.event.userdata8;
    command->data.event.userdata8 = NULL;
  }
  if (command->data.event.userdata9)
  {
    event->userdata9 = command->data.event.userdata9;
    command->data.event.userdata9 = NULL;
  }

  // IDM data
  if (command->data.event.src_id)
    event->src_id = g_object_ref (command->data.event.src_id);

  if (command->data.event.dst_id)
    event->dst_id = g_object_ref (command->data.event.dst_id);

  if (command->data.event.src_username)
  {
    event->src_username = sim_command_idm_event_parse_username (command->data.event.src_username);
    event->src_username_raw = command->data.event.src_username;
    command->data.event.src_username = NULL;
  }
  if (command->data.event.dst_username)
  {
    event->dst_username = sim_command_idm_event_parse_username (command->data.event.dst_username);
    event->dst_username_raw = command->data.event.dst_username;
    command->data.event.dst_username = NULL;
  }
  if (command->data.event.src_hostname)
  {
    event->src_hostname = command->data.event.src_hostname;
    command->data.event.src_hostname = NULL;
  }
  if (command->data.event.dst_hostname)
  {
    event->dst_hostname = command->data.event.dst_hostname;
    command->data.event.dst_hostname = NULL;
  }
  if (command->data.event.src_mac)
  {
    event->src_mac = command->data.event.src_mac;
    command->data.event.src_mac = NULL;
  }
  if (command->data.event.dst_mac)
  {
    event->dst_mac = command->data.event.dst_mac;
    command->data.event.dst_mac = NULL;
  }

  // Reputation data
  event->rep_prio_src = command->data.event.rep_prio_src;
  event->rep_prio_dst = command->data.event.rep_prio_dst;
  event->rep_rel_src = command->data.event.rep_rel_src;
  event->rep_rel_dst = command->data.event.rep_rel_dst;
  if (command->data.event.rep_act_src)
  {
    gchar **split_acts;
    gint  i;

    event->str_rep_act_src = g_strdup (command->data.event.rep_act_src);
    event->rep_act_src = g_hash_table_new_full(g_direct_hash, g_direct_equal, NULL, NULL);
    split_acts = g_strsplit (command->data.event.rep_act_src, SIM_DELIMITER_LIST, 0);
    for (i = 0; split_acts[i] != NULL; i++)
      g_hash_table_insert(event->rep_act_src, GINT_TO_POINTER(atoi(split_acts[i])), GINT_TO_POINTER(GENERIC_VALUE));
    g_strfreev(split_acts);
  }
  if (command->data.event.rep_act_dst)
  {
    gchar **split_acts;
    gint  i;

    event->str_rep_act_dst = g_strdup (command->data.event.rep_act_dst);
    event->rep_act_dst = g_hash_table_new_full(g_direct_hash, g_direct_equal, NULL, NULL);
    split_acts = g_strsplit (command->data.event.rep_act_dst, SIM_DELIMITER_LIST, 0);
    for (i = 0; split_acts[i] != NULL; i++)
      g_hash_table_insert(event->rep_act_dst, GINT_TO_POINTER(atoi(split_acts[i])), GINT_TO_POINTER(GENERIC_VALUE));
    g_strfreev(split_acts);
  }

  // Saqqara specific.
  if (command->data.event.saqqara_backlog_id)
  {
    event->saqqara_backlog_id = g_object_ref (command->data.event.saqqara_backlog_id);
    event->level = command->data.event.level;
    event->belongs_to_alarm = TRUE;

    // Saqqara directive events should have its backlog_id setted.
    if (event->plugin_id == 1505)
      event->backlog_id = g_object_ref (command->data.event.saqqara_backlog_id);
  }

  //we need this to resend data to other servers, or to send
  //events that matched with policy to frameworkd (future implementation)
  if (command->buffer)
  {
    event->buffer = command->buffer;
    command->buffer = NULL;
  }

  /* Binary data */
  if (command->data.event.binary_data)
  {
    event->binary_data = command->data.event.binary_data;
    command->data.event.binary_data = NULL;
  }

  /* if snort_event, copy snort data*/
  if ((command->type == SIM_COMMAND_TYPE_SNORT_EVENT) || ((event->plugin_id >= 1001) && (event->plugin_id < 1500) && event->userdata1 && event->userdata2))
  {
    plugin_sid = sim_context_get_plugin_sid (event->context, event->plugin_id, event->plugin_sid);
    plugin_sid_name = sim_plugin_sid_get_name (plugin_sid);

    // Check first for generic events.
    if ((event->plugin_sid == SIM_PLUGIN_SID_NONEXISTENT)
        && (event->log)
        && (aux_chr = strchr (event->log->str, ']')))
    {
      aux_size = (gsize) (aux_chr - event->log->str) + 1;
      aux = g_string_new_len (event->log->str + aux_size, event->log->len - aux_size);
      g_string_append_printf (aux, "%s, src: %s dst: %s", plugin_sid_name, command->data.event.src_ip, command->data.event.dst_ip);

      g_string_free (event->log, TRUE);
      event->log = aux;
    }
    else
    {
      if (event->log)
        g_string_free (event->log, TRUE);
      event->log = g_string_new (NULL);
      g_string_append_printf (event->log, "%s, src: %s dst: %s", plugin_sid_name, command->data.event.src_ip, command->data.event.dst_ip);
    }

    g_object_unref (plugin_sid);
  }

  /* is remote (forwarded event) */
  if (command->data.event.is_remote)
    event->is_remote = TRUE;

  return event;
}

/*
 *
 * FIXME: This function is not called from anywhere
 *
 */
gboolean
sim_command_is_valid (SimCommand      *cmd)
{
  g_return_val_if_fail (cmd, FALSE);
  g_return_val_if_fail (SIM_IS_COMMAND (cmd), FALSE);

  switch (cmd->type)
  {
    case SIM_COMMAND_TYPE_CONNECT:
					break;
    case SIM_COMMAND_TYPE_EVENT:
					break;
    case SIM_COMMAND_TYPE_SESSION_APPEND_PLUGIN:
					break;
    case SIM_COMMAND_TYPE_SESSION_REMOVE_PLUGIN:
					break;
    case SIM_COMMAND_TYPE_WATCH_RULE:
					break;
    default:
					return FALSE;			
		      break;
  }
  return TRUE;
}

/*
 * Scan the backlog_event command
 */

gboolean
sim_command_backlog_event_scan(SimCommand *command, GScanner *scanner,gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner != NULL, FALSE);
  command->type = SIM_COMMAND_TYPE_BACKLOG;
  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_BACKLOG);
  do
  {
    g_scanner_get_next_token (scanner);

    switch ((SimCommandSymbolType) scanner->token)
    {
    case SIM_COMMAND_SYMBOL_BACKLOG_DIRECTIVE_ID:
    case SIM_COMMAND_SYMBOL_BACKLOG_CTX:
    case SIM_COMMAND_SYMBOL_BACKLOG_UUID:
    case SIM_COMMAND_SYMBOL_BACKLOG_UUID_EVENT:
    case SIM_COMMAND_SYMBOL_BACKLOG_LEVEL:
    case SIM_COMMAND_SYMBOL_BACKLOG_OCCURRENCE:
    case SIM_COMMAND_SYMBOL_BACKLOG_MATCHED:
    case SIM_COMMAND_SYMBOL_TYPE:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */
      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      break;

    default:
      if (scanner->token == G_TOKEN_EOF)
        break;
      ossim_debug ("%s: error symbol unknown,%s.Session ip: %s",__FUNCTION__,scanner->value.v_string,session_ip_str);
      return FALSE;
    }
  } while(scanner->token != G_TOKEN_EOF);
  return TRUE;
}

static gboolean
sim_command_idm_event_scan (SimCommand    *command,
                            GScanner      *scanner,
                            gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner, FALSE);

  command->type = SIM_COMMAND_TYPE_IDM_EVENT;
  command->data.idm_event.ip               = NULL;
  command->data.idm_event.is_login         = TRUE;
  command->data.idm_event.username         = NULL;
  command->data.idm_event.hostname         = NULL;
  command->data.idm_event.domain           = NULL;
  command->data.idm_event.mac              = NULL;
  command->data.idm_event.os               = NULL;
  command->data.idm_event.cpu              = NULL;
  command->data.idm_event.memory           = 0;
  command->data.idm_event.video            = NULL;
  command->data.idm_event.service          = NULL;
  command->data.idm_event.software         = NULL;
  command->data.idm_event.state            = NULL;
  command->data.idm_event.inventory_source = 0;
  command->data.idm_event.host_id          = NULL;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_IDM_EVENT);
  do
  {
	  gsize base64len;

    g_scanner_get_next_token (scanner);

    switch ((SimCommandSymbolType) scanner->token)
    {
    case SIM_COMMAND_SYMBOL_IP:
			g_scanner_get_next_token (scanner); /* = */
			g_scanner_get_next_token (scanner); /* value */

			if (scanner->token != G_TOKEN_STRING)
			{
				command->type = SIM_COMMAND_TYPE_NONE;
				break;
			}
			if (!(command->data.idm_event.ip = sim_inet_new_from_string (scanner->value.v_string)))
      {
        g_message("Error: event incorrect. Please check the ip value: %s", scanner->value.v_string);
        return FALSE;
      }

			break;
    case SIM_COMMAND_SYMBOL_LOGIN:
			g_scanner_get_next_token (scanner); /* = */
			g_scanner_get_next_token (scanner); /* value */

			if (scanner->token != G_TOKEN_STRING)
			{
				command->type = SIM_COMMAND_TYPE_NONE;
				break;
			}

      if (sim_string_is_number (scanner->value.v_string, 0))
        command->data.idm_event.is_login = strtod (scanner->value.v_string, (char **) NULL);
      else
      {
        g_message("Error: event incorrect. Please check the login value: %s", scanner->value.v_string);
        return FALSE;
      }

      break;
    case SIM_COMMAND_SYMBOL_USERNAME:
			g_scanner_get_next_token (scanner); /* = */
			g_scanner_get_next_token (scanner); /* value */

			if (scanner->token != G_TOKEN_STRING)
			{
				command->type = SIM_COMMAND_TYPE_NONE;
				break;
			}

			command->data.idm_event.username = (gchar *) g_base64_decode (scanner->value.v_string, &base64len);

      /* ignore "NULL" usernames */
      if (g_ascii_strcasecmp (command->data.idm_event.username, "NULL") == 0)
      {
        g_free (command->data.idm_event.username);
        command->data.idm_event.username = NULL;
      }

			break;
    case SIM_COMMAND_SYMBOL_HOSTNAME:
			g_scanner_get_next_token (scanner); /* = */
			g_scanner_get_next_token (scanner); /* value */

			if (scanner->token != G_TOKEN_STRING)
			{
				command->type = SIM_COMMAND_TYPE_NONE;
				break;
			}

      /* ignore "NULL" hostnames */
      if (g_ascii_strcasecmp (scanner->value.v_string, "NULL") != 0)
      {
        command->data.idm_event.hostname = g_strdup (scanner->value.v_string);
      }
      break;
    case SIM_COMMAND_SYMBOL_DOMAIN:
			g_scanner_get_next_token (scanner); /* = */
			g_scanner_get_next_token (scanner); /* value */

			if (scanner->token != G_TOKEN_STRING)
			{
				command->type = SIM_COMMAND_TYPE_NONE;
				break;
			}

			command->data.idm_event.domain = (gchar *) g_base64_decode (scanner->value.v_string, &base64len);

      /* ignore "NULL" domains */
      if (g_ascii_strcasecmp (command->data.idm_event.domain, "NULL") == 0)
      {
        g_free (command->data.idm_event.domain);
        command->data.idm_event.domain = NULL;
      }

			break;
    case SIM_COMMAND_SYMBOL_MAC:
			g_scanner_get_next_token (scanner); /* = */
			g_scanner_get_next_token (scanner); /* value */

			if (scanner->token != G_TOKEN_STRING)
			{
				command->type = SIM_COMMAND_TYPE_NONE;
				break;
			}

      /* ignore "NULL" macs */
      if (g_ascii_strcasecmp (scanner->value.v_string, "NULL") != 0)
      {
        command->data.idm_event.mac = g_strdup (scanner->value.v_string);
      }
	    break;
    case SIM_COMMAND_SYMBOL_OS:
			g_scanner_get_next_token (scanner); /* = */
			g_scanner_get_next_token (scanner); /* value */

			if (scanner->token != G_TOKEN_STRING)
			{
				command->type = SIM_COMMAND_TYPE_NONE;
				break;
			}

			command->data.idm_event.os = (gchar *) g_base64_decode (scanner->value.v_string, &base64len);
      break;
    case SIM_COMMAND_SYMBOL_CPU:
			g_scanner_get_next_token (scanner); /* = */
			g_scanner_get_next_token (scanner); /* value */

			if (scanner->token != G_TOKEN_STRING)
			{
				command->type = SIM_COMMAND_TYPE_NONE;
				break;
			}

			command->data.idm_event.cpu = (gchar *) g_base64_decode (scanner->value.v_string, &base64len);
      break;
    case SIM_COMMAND_SYMBOL_MEMORY:
			g_scanner_get_next_token (scanner); /* = */
			g_scanner_get_next_token (scanner); /* value */

			if (scanner->token != G_TOKEN_STRING)
			{
				command->type = SIM_COMMAND_TYPE_NONE;
				break;
			}

      if (sim_string_is_number (scanner->value.v_string, 0))
        command->data.idm_event.memory = strtol (scanner->value.v_string, (char **) NULL, 10);
      else
      {
        g_message("Error: event incorrect. Please check the memory issued from the agent: %s", scanner->value.v_string);
        return FALSE;
      }
      break;
    case SIM_COMMAND_SYMBOL_VIDEO:
			g_scanner_get_next_token (scanner); /* = */
			g_scanner_get_next_token (scanner); /* value */

			if (scanner->token != G_TOKEN_STRING)
			{
				command->type = SIM_COMMAND_TYPE_NONE;
				break;
			}

			command->data.idm_event.video = (gchar *) g_base64_decode (scanner->value.v_string, &base64len);
      break;
    case SIM_COMMAND_SYMBOL_SERVICE:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }

			command->data.idm_event.service = (gchar *) g_base64_decode (scanner->value.v_string, &base64len);
      break;
    case SIM_COMMAND_SYMBOL_SOFTWARE:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }

			command->data.idm_event.software = (gchar *) g_base64_decode (scanner->value.v_string, &base64len);
      break;
    case SIM_COMMAND_SYMBOL_STATE:
			g_scanner_get_next_token (scanner); /* = */
			g_scanner_get_next_token (scanner); /* value */

			if (scanner->token != G_TOKEN_STRING)
			{
				command->type = SIM_COMMAND_TYPE_NONE;
				break;
			}

      command->data.idm_event.state = g_strdup (scanner->value.v_string);
      break;
    case SIM_COMMAND_SYMBOL_INVENTORY_SOURCE:
			g_scanner_get_next_token (scanner); /* = */
			g_scanner_get_next_token (scanner); /* value */

			if (scanner->token != G_TOKEN_STRING)
  		{
				command->type = SIM_COMMAND_TYPE_NONE;
				break;
			}
      if (sim_string_is_number (scanner->value.v_string, FALSE))
				command->data.idm_event.inventory_source = strtol (scanner->value.v_string, (char **) NULL, 10);
      else
      {
        g_message("Error: idm-event incorrect. Please check the inventory_source issued from the agent: %s", scanner->value.v_string);
        return FALSE;
      }

      break;
    case SIM_COMMAND_SYMBOL_CTX:
			g_scanner_get_next_token (scanner); /* = */
			g_scanner_get_next_token (scanner); /* value */

			if (scanner->token != G_TOKEN_STRING)
			{
				command->type = SIM_COMMAND_TYPE_NONE;
				break;
			}

      if ((command->context_id = sim_uuid_new_from_string (scanner->value.v_string)) == NULL)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }

      break;

    case SIM_COMMAND_SYMBOL_HOST:
			g_scanner_get_next_token (scanner); /* = */
			g_scanner_get_next_token (scanner); /* value */

			if (scanner->token != G_TOKEN_STRING)
			{
				command->type = SIM_COMMAND_TYPE_NONE;
				break;
			}

      command->data.idm_event.host_id = sim_uuid_new_from_string (scanner->value.v_string);

      break;

    default:
			if (scanner->token == G_TOKEN_EOF)
				break;
			ossim_debug ("%s: error symbol unknown,%s.Session ip: %s",__FUNCTION__,scanner->value.v_string,session_ip_str);
			return FALSE;
    }
  } while (scanner->token != G_TOKEN_EOF);

  return TRUE;
}

/*
 *  This function decides which one is the correct parser to scan the agent log; BASE64 or standard human readable.
 */
gboolean (*sim_command_get_remote_server_scan(void))(SimCommand*,GScanner*)
{
  return sim_command_event_scan_base64;
}

gboolean (*sim_command_get_agent_scan(void))(SimCommand*,GScanner*)
{
  return sim_command_event_scan_base64;
}

gboolean (*sim_command_get_default_scan(void))(SimCommand*,GScanner*){
	return &sim_command_event_scan_base64;
}

static void sim_command_init_command_event_struct(SimCommand *command)
{
  command->type = SIM_COMMAND_TYPE_EVENT;
  command->data.event.type = NULL;
  command->data.event.id_transaction = 0;
  command->data.event.id = NULL;
  command->data.event.date = 0;
  command->data.event.date_str = NULL; //be carefull, if you insert some event without this parameter, you'll get unix date: 1970/01/01
  command->data.event.tzone = 0;
  command->data.event.sensor = NULL;
  command->data.event.sensor_id = NULL;
  command->data.event.device = NULL;
  command->data.event.device_id = 0;
  command->data.event.server = NULL;
  command->data.event.servername = NULL;
  command->data.event.interface = NULL;

  command->data.event.plugin_id = 0;
  command->data.event.plugin_sid = 0;

  command->data.event.protocol = NULL;
  command->data.event.src_ip = NULL;
  command->data.event.src_port = 0;
  command->data.event.dst_ip = NULL;
  command->data.event.dst_port = 0;

  command->data.event.condition = NULL;
  command->data.event.value = NULL;
  command->data.event.interval = 0;

  command->data.event.data = NULL;
  command->data.event.log = NULL;
  command->data.event.snort_sid = 0;
  command->data.event.snort_cid = 0;

  command->data.event.priority = 0;
  command->data.event.reliability = 0;
  command->data.event.asset_src = VOID_ASSET;
  command->data.event.asset_dst = VOID_ASSET;
  command->data.event.risk_a = DEFAULT_RISK;
  command->data.event.risk_c = DEFAULT_RISK;
  command->data.event.alarm = FALSE;
  command->data.event.event = NULL;

  command->data.event.filename = NULL;
  command->data.event.username = NULL;
  command->data.event.password = NULL;
  command->data.event.userdata1 = NULL;
  command->data.event.userdata2 = NULL;
  command->data.event.userdata3 = NULL;
  command->data.event.userdata4 = NULL;
  command->data.event.userdata5 = NULL;
  command->data.event.userdata6 = NULL;
  command->data.event.userdata7 = NULL;
  command->data.event.userdata8 = NULL;
  command->data.event.userdata9 = NULL;
  command->data.event.binary_data = NULL;
  command->data.event.src_id = NULL;
  command->data.event.dst_id = NULL;
  command->data.event.src_username = NULL;
  command->data.event.dst_username = NULL;
  command->data.event.src_hostname = NULL;
  command->data.event.dst_hostname = NULL;
  command->data.event.src_mac = NULL;
  command->data.event.dst_mac = NULL;
  command->data.event.rep_prio_src = 0;
  command->data.event.rep_prio_dst = 0;
  command->data.event.rep_rel_src = 0;
  command->data.event.rep_rel_dst = 0;
  command->data.event.rep_act_src = NULL;
  command->data.event.rep_act_dst = NULL;
  
  command->data.event.belongs_to_alarm = FALSE;
  command->data.event.belongs_to_alarm_from_child= FALSE;
}

/******************************** Old snort event scan ****************************/
/*
 * Scan and decompress the Snort Event
 *
 */

/**
 * sim_command_snort_event_scan:
 * @command: #SimCommand object
 * @scanner: #Gscanner object
 * @session_ip_str: sensor IP
 *
 * Returns: %TRUE if the snort event was scanned sucesfully
 */

gboolean
sim_command_snort_event_scan (SimCommand	*command,GScanner *scanner,
                              gchar* session_ip_str)
{
  g_return_val_if_fail (SIM_IS_COMMAND (command), FALSE);
  g_return_val_if_fail (scanner != NULL, FALSE);

  gchar    * gzipdata = NULL;
  guchar    *unzipdata;
  gboolean  ipv6 = FALSE;
  /* from zlib.h */
  uLongf  size;
  guint   lenzip = 0;
  gint    errorzip = 0;
  gint64  unziplen = DEFAULT_UNZIPLEN;

  sim_command_init_command_event_struct(command);
  command->type = SIM_COMMAND_TYPE_SNORT_EVENT;

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_SNORT_EVENT);
  do
  {
    g_scanner_get_next_token(scanner);

    switch ((SimCommandSymbolType) scanner->token)
    {

    case SIM_COMMAND_SYMBOL_IPV6:
      ossim_debug ("%s: ipv6 packet", __func__);
      ipv6 = TRUE;
    case SIM_COMMAND_SYMBOL_SERVER:
    case SIM_COMMAND_SYMBOL_SERVERNAME:
    case SIM_COMMAND_SYMBOL_SNORT_EVENT_TYPE:
      g_scanner_get_next_token (scanner);
      g_scanner_get_next_token (scanner);
      break; 

    case SIM_COMMAND_SYMBOL_TYPE: 
      g_scanner_get_next_token (scanner);
      g_scanner_get_next_token (scanner);
      if (scanner->token != G_TOKEN_STRING)
        command->type = SIM_COMMAND_TYPE_NONE;
      else
        command->data.event.type = g_strdup (scanner->value.v_string);
      break;

    case SIM_COMMAND_SYMBOL_SNORT_EVENT_SENSOR:
      g_scanner_get_next_token(scanner); /* = */
      g_scanner_get_next_token(scanner); /* value */
      if (scanner->token == G_TOKEN_STRING)
      {
        //Later we will try to assign the ip of the sensor (maybe of a firewall/router...)
        if (gnet_inetaddr_is_canonical (scanner->value.v_string))
          command->data.event.sensor = g_strdup (scanner->value.v_string);
        else
        {
          g_message("Error: %s: Please check the sensor issued from the agent: -> value: %s,session_ip:%s",
                    __func__, scanner->value.v_string,session_ip_str);
          return FALSE;
        }

        if(!command->data.event.sensor)
          command->data.event.sensor = g_strdup_printf ("0.0.0.0");
        else if(!strcmp(command->data.event.sensor,"None")||!strcmp(command->data.event.sensor,""))
        {
          g_free(command->data.event.sensor);
          command->data.event.sensor = g_strdup_printf ("0.0.0.0");
        }
      }
      break;

    case SIM_COMMAND_SYMBOL_SNORT_EVENT_IF:
      g_scanner_get_next_token(scanner); /* = */
      g_scanner_get_next_token(scanner); /* value */
      if (scanner->token == G_TOKEN_STRING)
      {
        command->data.event.interface = g_strdup(scanner->value.v_string);
        ossim_debug("%s: Interface -> value: %s,session_ip:%s", __func__, scanner->value.v_string,session_ip_str);
      }
      break;

      // Old event style.
    case SIM_COMMAND_SYMBOL_GZIPDATA:
      g_scanner_get_next_token(scanner); /* = */
      g_scanner_get_next_token(scanner); /* value */
      if (scanner->token==G_TOKEN_STRING &&
          ((gzipdata = (gchar*)sim_hex2bin(scanner->value.v_string)))!=NULL)
      {
        lenzip = strlen(scanner->value.v_string)/2;
        ossim_debug("%s: Gzipdata -> value: %s,session_ip:%s", __func__, scanner->value.v_string,session_ip_str);
      }
      break;

      // New event style.
    case SIM_COMMAND_SYMBOL_BINARY_DATA:
      g_scanner_get_next_token(scanner); /* = */
      g_scanner_get_next_token(scanner); /* value */
      if (scanner->token==G_TOKEN_STRING &&
          ((command->data.event.binary_data = (gchar*)sim_hex2bin(scanner->value.v_string)))!=NULL)
      {
        lenzip = strlen(scanner->value.v_string)/2;
        ossim_debug("%s: Gzipdata -> value: %s,session_ip:%s", __func__, scanner->value.v_string,session_ip_str);
      }
      break;

    case SIM_COMMAND_SYMBOL_UNZIPLEN:
      g_scanner_get_next_token(scanner); /* = */
      g_scanner_get_next_token(scanner); /* value */
      if (scanner->token== G_TOKEN_STRING &&
      	  sim_string_is_number(scanner->value.v_string,0))
      {
        unziplen = g_ascii_strtoll(scanner->value.v_string,(char**)NULL,10);
        if (errno!=ERANGE)
        {
          if (unziplen <=0 || unziplen > DEFAULT_UNZIPLEN)
            unziplen = DEFAULT_UNZIPLEN;
          command->data.event.binary_data_len = unziplen;
        }
      }
      break;

    case SIM_COMMAND_SYMBOL_UUID:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */
      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
      /* Transform the string to a uuid*/
      command->data.event.id = sim_uuid_new_from_string (scanner->value.v_string);
      if (!command->data.event.id)
      {
        g_message ("Error: %s: but uuid in event. Discarting it and stopping parsing -> value: %s,session_ip:%s",
                   __func__, scanner->value.v_string,session_ip_str);
        return FALSE;
      }
      break;

    case SIM_COMMAND_SYMBOL_BELONGS_TO_ALARM:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }
						
      if (sim_string_is_number (scanner->value.v_string, 0)) 
        command->data.event.belongs_to_alarm_from_child= strtod (scanner->value.v_string, (char **) NULL);
      else
      {
        g_message("Error: event incorrect. Please check the belongs_to_alarm_from_child value:-> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
        return FALSE;
      }
      break;
			
    case SIM_COMMAND_SYMBOL_PLUGIN_ID:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }

      if (sim_string_is_number(scanner->value.v_string,0)){
        guint32 plugin_id = strtoul(scanner->value.v_string,(char**)NULL,10);
        if (errno!=ERANGE && errno!=EINVAL)
          command->data.event.plugin_id = plugin_id;
      }
      else
      {
        g_message("Error: event incorrect. Please check the plugin_id value: -> value: %s,session_ip:%s", scanner->value.v_string,session_ip_str);
        return FALSE;
      }
      break;

    case SIM_COMMAND_SYMBOL_DATE:
      g_scanner_get_next_token(scanner); /* = */
      g_scanner_get_next_token(scanner); /* Date */
			
      if (scanner->token == G_TOKEN_STRING)
      {
        command->data.event.date =  strtol (scanner->value.v_string, (char **) NULL, 10);
        ossim_debug ("sim_command_snort_event_scan: getting date");
      }
      else
        command->type = SIM_COMMAND_TYPE_NONE;
      break;

    case SIM_COMMAND_SYMBOL_DATE_STRING:
      g_scanner_get_next_token(scanner); /* = */
      g_scanner_get_next_token(scanner); /* Date */

      if (scanner->token == G_TOKEN_STRING )
      {
        command->data.event.date_str = g_strdup(scanner->value.v_string);
        ossim_debug ("sim_command_snort_event_scan: getting date string");
      }
      else
        command->type = SIM_COMMAND_TYPE_NONE;
      break;

    case SIM_COMMAND_SYMBOL_DATE_TZONE:
      g_scanner_get_next_token(scanner); /* = */
      g_scanner_get_next_token(scanner); /* tzone */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }

      if (sim_string_is_number(scanner->value.v_string,1))
      {
        gfloat tzone = strtod(scanner->value.v_string,(char**)NULL);
        if (errno!=ERANGE && errno!=EINVAL)
          command->data.event.tzone = tzone;
      }
      else
      {
        g_message("%s: Error: Please check the tzone value: %s. Assumed tzone = 0. Session ip:%s", __func__, scanner->value.v_string,session_ip_str);
        command->data.event.tzone = 0;
      }
      break;

    case SIM_COMMAND_SYMBOL_SRC_IP:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }

      if (gnet_inetaddr_is_canonical (scanner->value.v_string))
        command->data.event.src_ip = g_strdup (scanner->value.v_string);
      else
      {
        g_message("Error: event incorrect. Please check the src ip issued from the agent: %s", scanner->value.v_string);
        return FALSE;
      }
      break;

    case SIM_COMMAND_SYMBOL_DST_IP:
      g_scanner_get_next_token (scanner); /* = */
      g_scanner_get_next_token (scanner); /* value */

      if (scanner->token != G_TOKEN_STRING)
      {
        command->type = SIM_COMMAND_TYPE_NONE;
        break;
      }

      if (gnet_inetaddr_is_canonical (scanner->value.v_string))
        command->data.event.dst_ip = g_strdup (scanner->value.v_string);
      else
      {
        g_message("Error: event incorrect. Please check the dst ip issued from the agent: %s", scanner->value.v_string);
        return FALSE;
      }
      break;

    default:
      break;
    }
  }
  while(scanner->token != G_TOKEN_EOF);

  /* OK, know unzip the data and decode it */
  size = 1024 + unziplen;

  unzipdata = g_new(guchar, size);
  if(unzipdata !=NULL)
  {
    gboolean r;

    // Old event style.
    if (gzipdata)
    {
      errorzip = uncompress(unzipdata, (uLongf*) &size, (guint8 *)gzipdata, lenzip);
      if (errorzip != Z_OK)
      {
        ossim_debug( "%s: Error inflated data %u", __func__, errorzip);
        g_free(unzipdata);
        return FALSE;
      }
      else
      {
        gchar *new_payload;

        unzipdata[size]='\0';
        g_free(gzipdata);

        if (command->data.event.log)
        {
          g_string_append_printf (command->data.event.log, " gzipdata: %s", unzipdata);
        }
        else
        {
          new_payload = g_strconcat (command->data.event.data, " gzipdata: ", unzipdata, NULL);
          g_free (command->data.event.data);
          command->data.event.data = new_payload;
        }

        ossim_debug ("%s: Unzipped event %s", __func__, unzipdata);
        g_scanner_input_text (scanner, (gchar *)unzipdata, size);
        g_scanner_set_scope (scanner,SIM_COMMAND_SCOPE_SNORT_EVENT_DATA);

        if (ipv6)
          r = sim_command_snort_ipv6_packet_scan (scanner,command);
        else
          r = sim_command_snort_ipv4_packet_scan (scanner,command);
      }
      return (r);
    }
    else
      // New event style.
      if (command->data.event.binary_data)
      {
        errorzip = uncompress(unzipdata, (uLongf*) &size, (guint8 *) command->data.event.binary_data, lenzip);
        if (errorzip != Z_OK)
        {
          ossim_debug( "%s: Error inflated data %u", __func__, errorzip);
          g_free(unzipdata);
          return FALSE;
        }
        else
        {
          unzipdata[size]='\0';
          g_free(command->data.event.binary_data);
          command->data.event.binary_data = (gchar *) unzipdata;
          ossim_debug ("%s: Unzipped event %s", __func__, unzipdata);
          g_scanner_input_text (scanner, (gchar *)unzipdata, size);
          g_scanner_set_scope (scanner,SIM_COMMAND_SCOPE_SNORT_EVENT_DATA);

          if (ipv6)
            r = sim_command_snort_ipv6_packet_scan (scanner, command);
          else
            r = sim_command_snort_ipv4_packet_scan (scanner,command);
        }
        return r;
      }
  }
  else
  {
    ossim_debug( "%s: Cannot alloc memory", __func__);
    return FALSE;
  }

  return TRUE;
}

/*
 * Scan the snort data. Must be in order
 * date
 * snort_gid
 * snort_sid
 * snort_rev
 * snort_classification
 * snort_priority
 */

guint ipv4Packet[] =
{
  SIM_COMMAND_SYMBOL_SNORT_EVENT_DATA_TYPE,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_DATE,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_DATE_STRING,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_GID,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_SID,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_REV,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_CLASSIFICATION,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_PRIORITY,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_PACKET_TYPE,
  0
};

gboolean sim_command_snort_ipv4_packet_scan (GScanner *scanner, SimCommand *command)
{
	gint i;

	g_scanner_set_scope(scanner,SIM_COMMAND_SCOPE_SNORT_EVENT_DATA);

  for(i = 0; ipv4Packet[i] != 0; i++)
  {
  	g_scanner_get_next_token(scanner);
    if(scanner->token != ipv4Packet[i])
      return FALSE;

  	g_scanner_get_next_token(scanner); // =
		g_scanner_get_next_token(scanner); // value 
		
		if (scanner->token == G_TOKEN_STRING)
    {
			if (ipv4Packet[i] == SIM_COMMAND_SYMBOL_SNORT_EVENT_DATA_TYPE)
      {
        command->data.event.type = g_strdup(scanner->value.v_string);
        ossim_debug ("%s: getting data type", __func__);
      }

      if (ipv4Packet[i] == SIM_COMMAND_SYMBOL_SNORT_EVENT_SID)
      {
        if(sim_string_is_number(scanner->value.v_string,0))
        {
			    guint32 snort_sid = strtoul(scanner->value.v_string,(char**)NULL,10);
			    if (errno != ERANGE && errno != EINVAL)
				    command->data.event.plugin_sid = snort_sid;
     		  ossim_debug ("%s: getting event sid", __func__);
        }
        else
          return FALSE;
	  	}

      if (ipv4Packet[i] == SIM_COMMAND_SYMBOL_SNORT_EVENT_PACKET_TYPE)
      {
			  /* now we haven't raw packects logs. Discart it, but the Snort event is created*/
        if (!strcmp(scanner->value.v_string,"raw"))
				{
          do{
        		g_scanner_get_next_token(scanner);
        	}while(scanner->token!=G_TOKEN_EOF);
          return FALSE;
        }
				else if (!strcmp(scanner->value.v_string,"ip"))
				{
          ossim_debug ("%s: Scan ipv4 header", __func__);
					return sim_command_snort_ipv4_header_scan (scanner,command);
				}
				else 
				{
          ossim_debug ("%s: unknown type to scan (not IP, not RAW, not VLAN)", __func__);
					return FALSE;
				}
			}
    }
    else
      return FALSE;
  }
	
  if(ipv4Packet[i] == 0)
  	return TRUE;
  else
    return FALSE;
}



guint ipv4Header[] =
{
  SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_VER,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_HDRLEN,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_TOS,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_LEN,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_ID,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_OFFSET,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_TTL,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_PROTO,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_CSUM,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_SRC,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_DST,
  0
};

/**
 * sim_snort_ipv4_header_scan:
 * @scanner: #Gscanner object
 * @command: #SimCommand object
 *
 * Returns: %TRUE if the IP header and data for ipv4 was scanned sucesfully
 */
static gboolean
sim_command_snort_ipv4_header_scan (GScanner   *scanner,
                                    SimCommand *command)
{
  gint      i;
  guint32   protocol = 256;
  gboolean  r = TRUE;
  gboolean  options = FALSE;

  ossim_debug ("Scanning snort IPV4 header - IP");

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_SNORT_EVENT_IP);

  for(i = 0; ipv4Header[i] != 0; i++)
  {
  	g_scanner_get_next_token(scanner);
    if(scanner->token != ipv4Header[i])
    {
      r = FALSE;
      break;
    }

  	g_scanner_get_next_token(scanner); // =
		g_scanner_get_next_token(scanner); // value 
		
		if (scanner->token == G_TOKEN_STRING)
    {
			if (ipv4Header[i] == SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_PROTO)
      {
        if(sim_string_is_number (scanner->value.v_string, 0))
        {
          protocol = strtol (scanner->value.v_string, NULL, 10);
          if (errno != ERANGE && protocol < 256)
            command->data.event.protocol = g_strdup (scanner->value.v_string);
          else
          {
            r = FALSE;
            break;
          }
        }
        else
        {
          r = FALSE;
          break;
        }
      }

      if (ipv4Header[i] == SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_HDRLEN)
      {
        if(sim_string_is_number (scanner->value.v_string, 0))
        {
          guint32 hdrlen = strtol (scanner->value.v_string, NULL, 10);
          if (errno != ERANGE && hdrlen < 16)
          {
            if(hdrlen > 5)
              options = TRUE;
          }
          else
          {
            r = FALSE;
            break;
          }
        }
        else
        {
          r = FALSE;
          break;
        }
      }

      if (ipv4Header[i] == SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_SRC && !command->data.event.src_ip)
      {
        if (gnet_inetaddr_is_canonical (scanner->value.v_string))
          command->data.event.src_ip = g_strdup (scanner->value.v_string);
        else
        {
          g_message("Error: event incorrect. Please check the src ip issued from the agent: %s", scanner->value.v_string);
          return FALSE;
        }
      }


      if (ipv4Header[i] == SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_DST && !command->data.event.dst_ip)
      {
        if (gnet_inetaddr_is_canonical (scanner->value.v_string))
          command->data.event.dst_ip = g_strdup (scanner->value.v_string);
        else
        {
          g_message("Error: event incorrect. Please check the dst ip issued from the agent: %s", scanner->value.v_string);
          return FALSE;
        }
      }

    }
  } 


  if (r && options)
    sim_command_snort_event_skip_ip_opt_scan (scanner);

  if(ipv4Header[i] != 0)
    return FALSE;

  switch (protocol)
  {
    case IPPROTO_UDP:
      r = sim_command_snort_event_packet_udp_scan(scanner,command);
      break;
    case IPPROTO_TCP:
      r = sim_command_snort_event_packet_tcp_scan(scanner,command);
      break;
    case IPPROTO_ICMP:
      r = sim_command_snort_event_packet_icmp_scan(scanner,command);
      break;
    default:
      g_scanner_get_next_token(scanner);
      r = FALSE;
      if ((SimCommandSymbolType) scanner->token == SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_PAYLOAD)
      {
        g_scanner_get_next_token(scanner); /* = */
        g_scanner_get_next_token(scanner); /* value */
        r = TRUE;
      }
  }
	
  do
  {
    g_scanner_get_next_token (scanner);
    ossim_debug ("%s: EXTRA TOKEN!", __func__);
  } while (scanner->token != G_TOKEN_EOF);

  return r;
}

guint ipv6Packet[] =
{
  SIM_COMMAND_SYMBOL_SNORT_EVENT_DATA_TYPE,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_DATE,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_DATE_STRING,
  SIM_COMMAND_SYMBOL_SNORT_SENSOR_ID,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_ID,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_SECOND,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_MICROSECOND,
  SIM_COMMAND_SYMBOL_SNORT_SIGNATURE_ID,
  SIM_COMMAND_SYMBOL_SNORT_GENERATOR_ID,
  SIM_COMMAND_SYMBOL_SNORT_SIGNATURE_REVISION,
  SIM_COMMAND_SYMBOL_SNORT_CLASSIFICATION_ID,
  SIM_COMMAND_SYMBOL_SNORT_PRIORITY_ID,
  SIM_COMMAND_SYMBOL_SNORT_SOURCE_IP,
  SIM_COMMAND_SYMBOL_SNORT_DESTINATION_IP,
  SIM_COMMAND_SYMBOL_SNORT_SOURCE_PORT_ITYPE,
  SIM_COMMAND_SYMBOL_SNORT_DEST_PORT_ITYPE,
  SIM_COMMAND_SYMBOL_SNORT_PROTOCOL,
  SIM_COMMAND_SYMBOL_SNORT_IMPACT_FLAG,
  SIM_COMMAND_SYMBOL_SNORT_IMPACT,
  SIM_COMMAND_SYMBOL_SNORT_BLOCKED,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_PACKET_TYPE,
  0
};
/**
 * sim_command_snort_ipv6_packet_scan:
 * @scanner: #GScanner object
 * @command: #SimCommand object
 *
 * Returns: %TRUE if the packet ipv6 was scanned sucesfully
 */
gboolean
sim_command_snort_ipv6_packet_scan (GScanner   *scanner,
                                    SimCommand *command)
{
  gboolean  ip_packet = FALSE;
  gint      i;
  guint32   protocol = 256;

  ossim_debug ("Scanning snort IPV6 packet");

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_SNORT_EVENT_DATA);

  for(i = 0; ipv6Packet[i] != 0; i++)
  {
  	g_scanner_get_next_token(scanner);
    if(scanner->token != ipv6Packet[i])
      return FALSE;

  	g_scanner_get_next_token(scanner); // =
		g_scanner_get_next_token(scanner); // value 
		
		if (scanner->token == G_TOKEN_STRING)
    {
      if (ipv6Packet[i] == SIM_COMMAND_SYMBOL_SNORT_EVENT_DATA_TYPE)
        command->data.event.type = g_strdup(scanner->value.v_string);

      if (ipv6Packet[i] == SIM_COMMAND_SYMBOL_SNORT_SIGNATURE_ID)
      {
        if(sim_string_is_number (scanner->value.v_string, 0))
        {
          guint32 plugin_sid = strtoul (scanner->value.v_string, NULL, 10);
          if (errno != ERANGE && errno != EINVAL)
            command->data.event.plugin_sid = plugin_sid;
          else
            return FALSE;
        }
        else
          return FALSE;
      }

      if (ipv6Packet[i] == SIM_COMMAND_SYMBOL_SNORT_PROTOCOL)
      {
        protocol = strtol (scanner->value.v_string, NULL, 10);
        if (errno != ERANGE && protocol < 256)
          command->data.event.protocol = g_strdup (scanner->value.v_string);
        else
          return FALSE;
      }

      if (ipv6Packet[i] == SIM_COMMAND_SYMBOL_SNORT_EVENT_PACKET_TYPE)
      {
         if (!strcmp (scanner->value.v_string, "ipv6"))
          ip_packet = TRUE;
      }
    }
  }

  if(ipv6Packet[i] != 0)
    return FALSE;

  if (ip_packet)
  {
    // Scan IPv6 Header
    if(!sim_command_snort_ipv6_header_scan (scanner, command))
      return FALSE;

    // Scan Protocol
    return sim_command_snort_protocol_scan (scanner, command, protocol);
  }

  return TRUE;
}


guint ipv6Header[] =
{
  SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_VER,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_TRAFFIC,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_FLOWLABEL,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_PAYLOAD_LEN,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_PROTO,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_HOPLIMIT,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_SRC,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_DST,
  0
};

/**
 * sim_snort_ipv6_header_scan:
 * @scanner: #Gscanner object
 * @command: #SimCommand object
 *
 * Returns: %TRUE if the ipv6 header was scanned sucesfully
 */
gboolean sim_command_snort_ipv6_header_scan (GScanner *scanner,
                                             SimCommand *command)
{
  (void)command;

  gint      i;

  ossim_debug ("Scanning snort IPV6 Header");

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_SNORT_EVENT_IP);

  for(i = 0; ipv6Header[i] != 0; i++)
  {
  	g_scanner_get_next_token(scanner);
    if(scanner->token != ipv6Header[i])
      return FALSE;

  	g_scanner_get_next_token(scanner); // =
		g_scanner_get_next_token(scanner); // value 
		
		if (scanner->token != G_TOKEN_STRING)
      return FALSE;
  }

  return TRUE;
}

/**
 * sim_snort_protocol_scan:
 * @scanner: #GScanner object
 * @command: #SimCommand object
 *
 * Returns: %TRUE if the protocol was scanned sucesfully
 */
gboolean sim_command_snort_protocol_scan (GScanner *scanner, SimCommand *command, guint32 protocol)
{
  gboolean r = FALSE;

  switch (protocol)
  {
    case IPPROTO_UDP:
      ossim_debug ("%s: scanning udp", __func__);
      r = sim_command_snort_event_packet_udp_scan (scanner, command);
      break;

    case IPPROTO_TCP:
      ossim_debug ("%s: scanning tcp", __func__);
      r = sim_command_snort_event_packet_tcp_scan (scanner, command);
      break;

    case IPPROTO_ICMP:
      ossim_debug ("%s: scanning icmp", __func__);
      r = sim_command_snort_event_packet_icmp_scan (scanner, command);
      break;

    default: // ip packet doesn't correspond
      ossim_debug ("%s: scanning payload", __func__);
      g_scanner_get_next_token (scanner);
      r = FALSE;
      if ((SimCommandSymbolType) scanner->token == SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_PAYLOAD)
      {
        g_scanner_get_next_token (scanner); /* = */
        g_scanner_get_next_token (scanner); /* value */
        r = TRUE;
      }
  }

  return r;
}


guint udpPacket[] =
{
  SIM_COMMAND_SYMBOL_SNORT_EVENT_UDP_SPORT,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_UDP_DPORT,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_UDP_LEN,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_UDP_CSUM,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_UDP_PAYLOAD,
  0
};

/**
 * sim_command_snort_event_packet_udp_scan:
 * @scanner: #Gscanner object
 * @command: #SimCommand object
 *
 * Returns: %TRUE if the UDP packet was scanned sucesfully
 */
gboolean sim_command_snort_event_packet_udp_scan(GScanner *scanner,SimCommand *command)
{
	gboolean  r = TRUE;
  gint      i;

  ossim_debug ("Scanning snort UDP Packet");

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_SNORT_EVENT_UDP);

  for(i = 0; udpPacket[i] != 0; i++)
  {
  	g_scanner_get_next_token(scanner);
    if(scanner->token != udpPacket[i])
      return FALSE;

  	g_scanner_get_next_token(scanner); // =
		g_scanner_get_next_token(scanner); // value 
		
		if (scanner->token == G_TOKEN_STRING)
    {
      if (udpPacket[i] == SIM_COMMAND_SYMBOL_SNORT_EVENT_UDP_SPORT)
      {
        if (sim_string_is_number(scanner->value.v_string,0))
        {
          guint32 sport = strtoul(scanner->value.v_string,(char**)NULL,10);
          if (errno != ERANGE && errno != EINVAL && sport < 65536)
            command->data.event.src_port = sport;
          else
          {
            r = FALSE;
            break;
          }
        }
        else
        {
          r = FALSE;
          break;
        }
      }

      if (udpPacket[i] == SIM_COMMAND_SYMBOL_SNORT_EVENT_UDP_DPORT)
      {
        if (sim_string_is_number(scanner->value.v_string,0))
        {
          guint32 dport = strtoul(scanner->value.v_string,(char**)NULL,10);
          if (errno != ERANGE && errno != EINVAL && dport < 65536)
            command->data.event.dst_port = dport;
          else
          {
            r = FALSE;
            break;
          }
        }
        else
        {
          r = FALSE;
          break;
        }
			}
		}

	}

  if(udpPacket[i] != 0)
    r = FALSE;

	do{
		g_scanner_get_next_token(scanner);
		if (scanner->token!=G_TOKEN_EOF)
			r = FALSE;
	}
	while (scanner->token!=G_TOKEN_EOF);

	return r;
}


guint icmpPacket[] =
{
  SIM_COMMAND_SYMBOL_SNORT_EVENT_ICMP_TYPE,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_ICMP_CODE,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_ICMP_CSUM,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_ICMP_ID,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_ICMP_SEQ,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_ICMP_PAYLOAD,
  0
};

/**
 * sim_command_snort_event_packet_icmp_scan:
 * @scanner: #Gscanner object
 * @command: #SimCommand object
 *
 * Returns: %TRUE if the ICMP packet was scanned sucesfully
 */
gboolean sim_command_snort_event_packet_icmp_scan(GScanner *scanner,SimCommand *command){

  gint      i;
	gboolean  r = TRUE;

  (void)command;

  ossim_debug ("Scanning snort ICMP Packet");

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_SNORT_EVENT_ICMP);

  for(i = 0; icmpPacket[i] != 0; i++)
  {
  	g_scanner_get_next_token(scanner);
    if(scanner->token != icmpPacket[i])
    {
      r = FALSE;
      break;
    }

  	g_scanner_get_next_token(scanner); // =
		g_scanner_get_next_token(scanner); // value 
		
		if (scanner->token != G_TOKEN_STRING)
    {
      r = FALSE;
      break;
    }
  }

  if(icmpPacket[i] != 0)
    r = FALSE;

	/* find eof */
	do
  {
		g_scanner_get_next_token(scanner);
		if (scanner->token!=G_TOKEN_EOF)
			r = FALSE;
	} while (scanner->token!=G_TOKEN_EOF);

	return r;
}

guint tcpPacket[] =
{
  SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_SPORT,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_DPORT,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_SEQ,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_ACK,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_OFFSET,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_FLAGS,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_WINDOW,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_CSUM,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_URGPTR,
  SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_PAYLOAD,
  0
};

/**
 * sim_command_snort_event_packet_tcp_scan:
 * @scanner: #Gscanner object
 * @command: #SimCommand object
 *
 * Returns: %TRUE if the TCP packet was scanned sucesfully
 */
gboolean sim_command_snort_event_packet_tcp_scan(GScanner *scanner,SimCommand *command)
{
  gint      i;
	gboolean  options = FALSE;
  gboolean  r = TRUE;

  ossim_debug ("Scanning snort TCP Packet");

  g_scanner_set_scope (scanner, SIM_COMMAND_SCOPE_SNORT_EVENT_TCP);

  for(i = 0; tcpPacket[i] != 0; i++)
  {
  	g_scanner_get_next_token(scanner);
    if(scanner->token != tcpPacket[i])
    {
      r = FALSE;
      break;
    }

  	g_scanner_get_next_token(scanner); // =
    g_scanner_get_next_token(scanner); // value

    if (scanner->token == G_TOKEN_STRING)
    {
      if (tcpPacket[i] == SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_SPORT)
      {
        if (sim_string_is_number(scanner->value.v_string,0))
        {
          guint32 sport = strtoul(scanner->value.v_string,(char**)NULL,10);
          if (errno != ERANGE && errno != EINVAL && sport < 65536)
            command->data.event.src_port = sport;
          else
          {
            r = FALSE;
            break;
          }
        }
        else
        {
          r = FALSE;
          break;
        }
      }

      if (tcpPacket[i] == SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_OFFSET)
      {
        if (sim_string_is_number(scanner->value.v_string,0))
        {
          guint32 offset = strtoul(scanner->value.v_string,(char**)NULL,10);
          if (errno != ERANGE && errno != EINVAL && offset < 256)
          {
            if(offset > 5)
              options = TRUE;
          }
          else
          {
            r = FALSE;
            break;
          }
        }
        else
        {
          r = FALSE;
          break;
        }
      }

      if (tcpPacket[i] == SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_DPORT)
      {
        if (sim_string_is_number(scanner->value.v_string,0))
        {
          guint32 dport = strtoul(scanner->value.v_string,(char**)NULL,10);
          if (errno != ERANGE && errno != EINVAL && dport < 65536)
            command->data.event.dst_port = dport;
          else
          {
            r = FALSE;
            break;
          }
        }
        else
        {
          r = FALSE;
          break;
        }
      }

      if (tcpPacket[i] == SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_URGPTR)
      {
      	if (r && options)
          sim_command_snort_event_skip_tcp_opt_scan(scanner);	
      }
    }
  }

  if(tcpPacket[i] != 0)
    r = FALSE;
	
  /* check for eof */
  do
  {
    g_scanner_get_next_token(scanner);
    if (scanner->token!=G_TOKEN_EOF)
      r = FALSE;
  }while(scanner->token!=G_TOKEN_EOF);

  return r;
}

/**
 * sim_command_snort_skip_ip_opt_scan:
 * @scanner: #Gscanner object
 * @command: #SimCommand object
 *
 */
void sim_command_snort_event_skip_ip_opt_scan(GScanner *scanner)
{
 	do{
    switch((SimCommandSymbolType) g_scanner_peek_next_token(scanner))
    {
      case  SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_OPTNUM:
      case  SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_OPTCODE:
      case  SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_OPTLEN:
      case  SIM_COMMAND_SYMBOL_SNORT_EVENT_IP_OPTPAYLOAD:
        g_scanner_get_next_token(scanner); /* token */
        break;
      default:
        return;
    }
		g_scanner_get_next_token(scanner); /* = */
    g_scanner_get_next_token(scanner); /* value */
	}while(scanner->token!=G_TOKEN_EOF);
}

/**
 * sim_command_snort_event_skip_tcp_opt_scan:
 * @scanner: #Gscanner object
 * @command: #SimCommand object
 *
 */
void sim_command_snort_event_skip_tcp_opt_scan(GScanner *scanner)
{
 	do{
    switch((SimCommandSymbolType) g_scanner_peek_next_token(scanner))
    {
      case  SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_OPTNUM:
      case  SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_OPTCODE:
      case  SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_OPTLEN:
      case  SIM_COMMAND_SYMBOL_SNORT_EVENT_TCP_OPTPAYLOAD:
        g_scanner_get_next_token(scanner); /* token */
        break;
      default:
        return;
    }
		g_scanner_get_next_token(scanner); /* = */
    g_scanner_get_next_token(scanner); /* value */
	}while(scanner->token!=G_TOKEN_EOF);
}

GHashTable *
sim_command_idm_event_parse_username (const gchar *username)
{
  GHashTable *ret;
  gchar **split_list, **split;
  gchar **i;

  ret = g_hash_table_new_full (g_str_hash, g_str_equal, g_free, g_free);

  split_list = g_strsplit (username, ",", 0);
  for (i = split_list; *i; i++)
  {
    split = g_strsplit (*i, "|", 0);

    g_hash_table_insert (ret, g_strdup (*split), g_strdup (*(split + 1)));

    g_strfreev (split);
  }

  g_strfreev (split_list);

  return ret;
}

// vim: set tabstop=2:

