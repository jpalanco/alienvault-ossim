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

#ifndef __SIM_ENUMS_H__
#define __SIM_ENUMS_H__ 1

#include <glib.h>

G_BEGIN_DECLS

#define GENERIC_VALUE               1

#define SIM_LOG_FILE                "server.log"

#define SIM_DELIMITER_LIST          ","
#define SIM_DELIMITER_ELEMENT_LIST  ";"
#define SIM_DELIMITER_LEVEL         ":"
#define SIM_DELIMITER_RANGE         "-"
#define SIM_DELIMITER_PIPE          "|" /*used to separate data in remote DB loading*/
#define SIM_DELIMITER_SLASH         "/"

#define SIM_DB_IPV4                 '1'
#define SIM_DB_IPV6                 '2'

#define IPV4                         4
#define IPV6                         6

#define SIM_IN_ADDR_NONE_IP_STR      "0.0.0.0"

#define SIM_HOME_NET_CONST          "HOME_NET"

#define SIM_WILDCARD_ANY            "ANY"    // Match anything but empty fields.
#define SIM_WILDCARD_ANY_LOWER      "any"
#define SIM_WILDCARD_EMPTY          "EMPTY"  // Match only fields.
#define SIM_SRC_IP_CONST            "SRC_IP"
#define SIM_DST_IP_CONST            "DST_IP"
#define SIM_SRC_PORT_CONST          "SRC_PORT"
#define SIM_DST_PORT_CONST          "DST_PORT"
#define SIM_PROTOCOL_CONST          "PROTOCOL"
#define SIM_PLUGIN_ID_CONST         "PLUGIN_ID"
#define SIM_PLUGIN_SID_CONST        "PLUGIN_SID"
#define SIM_SENSOR_CONST			      "SENSOR"
#define SIM_PRODUCT_CONST			      "PRODUCT"
#define SIM_ENTITY_CONST            "ENTITY"
#define SIM_CATEGORY_CONST		      "CATEGORY"
#define SIM_SUBCATEGORY_CONST		    "SUBCATEGORY"
#define SIM_FILENAME_CONST			    "FILENAME"
#define SIM_USERNAME_CONST			    "USERNAME"
#define SIM_PASSWORD_CONST			    "PASSWORD"
#define SIM_USERDATA1_CONST			    "USERDATA1"
#define SIM_USERDATA2_CONST			    "USERDATA2"
#define SIM_USERDATA3_CONST			    "USERDATA3"
#define SIM_USERDATA4_CONST			    "USERDATA4"
#define SIM_USERDATA5_CONST			    "USERDATA5"
#define SIM_USERDATA6_CONST			    "USERDATA6"
#define SIM_USERDATA7_CONST			    "USERDATA7"
#define SIM_USERDATA8_CONST			    "USERDATA8"
#define SIM_USERDATA9_CONST			    "USERDATA9"

#define SIM_SRC			    0  
#define SIM_DST			    1

#define SIM_DETECTOR_CONST          "DETECTOR"
#define SIM_MONITOR_CONST           "MONITOR"

/* Max number of Plugin Sids to load in each query */
#define MAX_NSIDS_GROUP 5000

/* Stats time lapse */
#define STATS_TIME_LAPSE 10

/* Remove backlogs time lapse in seconds */
#define REMOVE_BACKLOGS_TIME_LAPSE 10

/* Update vulnerabilities in assets time lapse in seconds */
#define UPDATE_VULN_ASSETS_TIME_LAPSE 60

/* Unconfigured sensors time lapse in seconds */
#define UNCONFIGURED_SENSORS_TIME_LAPSE 60

/**** Performance related ****/

		// Session ring buffer
		#define RING_SIZE                         1048576
		// Session event buffer
		#define BUFFER_SIZE                         65535

		// Reception queue events before organizer (with alarms it can be really bigger than the value specified)
    #define MAX_RECEPTION_QUEUE_LENGTH          100000

		// Event queue before db dispatcher 
		// (before deconstructing the event 
		// in sql statements)
    #define MAX_DB_QUEUE_LENGTH                 100000

    // Max queue engine (run_role thread) length
    #define MAX_ENGINE_QUEUE_LENGTH             100000

		/* * For insertion table threads
		//
		 MAX_DB_CORES accept values 0 or 1*/ 
	  #define MAX_DB_CORES														1
		// Max value records for each 
		// sql big statement
		#define MAX_VALUES                           10000

		// Max sql (prepared) statements 
		// in sql queue before inserting
		#define MAX_STATEMENTS                          5

		// This shouldn't be more than 5 unless you want to admit delays maybe bigger than 10 seconds for some events
		#define MAX_TIME_TO_CHECK_WRITE                 2

		//With at least MIN_VALUE_RECORDS_TO_FORCE_WIRTE in MAX_TIME_TO_CHECK_WRITE we force a write.
		//If MIN_VALUE... is less than this value, a write will be done anyway in 2 * MAX_TIME_TO_CHECK_WRITE as max.
		#define MIN_VALUE_RECORDS_TO_FORCE_WRITE     1000

/**** End performance related ****/


//Wait 60 seconds before creating a new cache file
#define STORAGE_FILE_PERIOD  		10
#define STORAGE_FILE_PATH   	"/var/lib/ossim/cache/"

//This is aproximately 20000 events in the line (they get insert into 7-8 tables more or less depending on the kind of event)
#define MAX_STORAGE_LINES	    120000 

#define TIMEBUF_SIZE                20

#define GENERATOR_SPP_SPADE         104
#define GENERATOR_SPP_SCAN2         117
#define GENERATOR_SNORT_ENGINE      1

#define SNORT_DEFAULT_PRIORITY      2
#define SNORT_MAX_PRIORITY          3

#define FW1_DEFAULT_PRIORITY        1

#define GENERATOR_FW1               200
#define FW1_ACCEPT_TYPE             1
#define FW1_DROP_TYPE               2
#define FW1_REJECT_TYPE             3

#define FW1_ACCEPT_PRIORITY         0
#define FW1_DROP_PRIORITY           1
#define FW1_REJECT_PRIORITY         1

#define RRD_DEFAULT_PRIORITY        5

#define SIM_XML_CONFIG_FILE         "config.xml"
#define SIM_XML_DIRECTIVE_FILE      "directives.xml"

#define SIM_DS_OSSIM                "ossimDS"
#define SIM_DS_SNORT                "snortDS"
#define SIM_DS_OSVDB                "osvdbDS"

#define SIM_PLUGIN_ID_DIRECTIVE         1505
#define SIM_PLUGIN_ID_POST_CORRELATION  20505
#define SIM_PLUGIN_ID_DEMO			   	    20000
#define SIM_PLUGIN_SID_NONEXISTENT  	  2000000000
#define SIM_PLUGIN_ID_ANY               0x7FFFFFFF
// Default asset for hosts/nets if not defined
#define DEFAULT_ASSET			2
#define VOID_ASSET	           -1

/* Default risk */
#define DEFAULT_RISK           -1

#define CANTOR_KEY(x,y) (((x+y)*(x+y+1))/2)+y

typedef enum
{
  SIM_ALARM_RISK_TYPE_NONE,
  SIM_ALARM_RISK_TYPE_LOW,
  SIM_ALARM_RISK_TYPE_MEDIUM,
  SIM_ALARM_RISK_TYPE_HIGH,
  SIM_ALARM_RISK_TYPE_ALL
} SimAlarmRiskType;

typedef enum
{
  SIM_INET_TYPE_NONE,
  SIM_INET_TYPE_INET,
  SIM_INET_TYPE_CIDR
} SimInetType;

// This enums must be related with table host_property_reference initialization
typedef enum
{
  SIM_HOST_PROP_SOFTWARE   = 1,  // unused
  SIM_HOST_PROP_CPU        = 2,
  SIM_HOST_PROP_OS         = 3,
  SIM_HOST_PROP_WORKGROUP  = 4,  // unused
  SIM_HOST_PROP_MEMORY     = 5,
  SIM_HOST_PROP_DEPARTMENT = 6,
  SIM_HOST_PROP_STATE      = 7,
  SIM_HOST_PROP_USERNAME   = 8,
  SIM_HOST_PROP_ACL        = 9,  // unused
  SIM_HOST_PROP_ROUTE      = 10, // unused
  SIM_HOST_PROP_STORAGE    = 11, // unused
  SIM_HOST_PROP_ROLE       = 12, // unused
  SIM_HOST_PROP_VIDEO      = 13
} SimHostProp;

typedef enum
{
  SIM_DATABASE_TYPE_NONE,
  SIM_DATABASE_TYPE_MYSQL,
  SIM_DATABASE_TYPE_PGSQL,
  SIM_DATABASE_TYPE_ORACLE,
  SIM_DATABASE_TYPE_ODBC
} SimDatabaseType;

typedef enum
{
  SIM_CONDITION_TYPE_NONE,
  SIM_CONDITION_TYPE_EQ,
  SIM_CONDITION_TYPE_NE,
  SIM_CONDITION_TYPE_LT,
  SIM_CONDITION_TYPE_LE,
  SIM_CONDITION_TYPE_GT,
  SIM_CONDITION_TYPE_GE  
} SimConditionType;

typedef enum
{
  SIM_BACKLOG_EVENT_TYPE_NONE = 0,
  SIM_BACKLOG_EVENT_TYPE_START,
  SIM_BACKLOG_EVENT_TYPE_END
}
SimBacklogEventType;

typedef enum
{
  SIM_PROTOCOL_TYPE_NONE  = -1,
  SIM_PROTOCOL_TYPE_ICMP  = 1,
  SIM_PROTOCOL_TYPE_TCP   = 6,
  SIM_PROTOCOL_TYPE_UDP   = 17,
	// I know, I know, the "protocols" below are not protocols, but we have to assign something to the "protocol"
	// field into event list with arpwatch, snare, etc. The "protocols" below are unnasigned in /etc/protocols
  SIM_PROTOCOL_TYPE_HOST_ARP_EVENT   			= 134,
  SIM_PROTOCOL_TYPE_HOST_OS_EVENT   			= 135,
  SIM_PROTOCOL_TYPE_HOST_SERVICE_EVENT   	= 136,
  //SIM_PROTOCOL_TYPE_HOST_IDS_EVENT   			= 137,
  SIM_PROTOCOL_TYPE_INFORMATION_EVENT			= 138,
  SIM_PROTOCOL_TYPE_OTHER   							= 139
} SimProtocolType;

typedef enum {
  SIM_PLUGIN_TYPE_NONE,
  SIM_PLUGIN_TYPE_DETECTOR,
  SIM_PLUGIN_TYPE_MONITOR
} SimPluginType;

typedef enum {
  SIM_PLUGIN_STATE_TYPE_NONE,
  SIM_PLUGIN_STATE_TYPE_START,
  SIM_PLUGIN_STATE_TYPE_STOP
} SimPluginStateType;

typedef enum {
  SIM_EVENT_TYPE_NONE,
  SIM_EVENT_TYPE_DETECTOR,
  SIM_EVENT_TYPE_MONITOR,
} SimEventType;

typedef enum {
  SIM_RULE_VAR_NONE,
  SIM_RULE_VAR_SRC_IA,
  SIM_RULE_VAR_DST_IA,
  SIM_RULE_VAR_SRC_PORT,
  SIM_RULE_VAR_DST_PORT,
  SIM_RULE_VAR_PROTOCOL,
  SIM_RULE_VAR_PLUGIN_ID,
  SIM_RULE_VAR_PLUGIN_SID,
  SIM_RULE_VAR_SENSOR,
  SIM_RULE_VAR_PRODUCT,
  SIM_RULE_VAR_ENTITY,
  SIM_RULE_VAR_CATEGORY,
  SIM_RULE_VAR_SUBCATEGORY,
  SIM_RULE_VAR_FILENAME,
  SIM_RULE_VAR_USERNAME,
  SIM_RULE_VAR_PASSWORD,
  SIM_RULE_VAR_USERDATA1,
  SIM_RULE_VAR_USERDATA2,
  SIM_RULE_VAR_USERDATA3,
  SIM_RULE_VAR_USERDATA4,
  SIM_RULE_VAR_USERDATA5,
  SIM_RULE_VAR_USERDATA6,
  SIM_RULE_VAR_USERDATA7,
  SIM_RULE_VAR_USERDATA8,
  SIM_RULE_VAR_USERDATA9,
  SIM_RULE_VAR_PULSE_ID
} SimRuleVarType;

typedef enum {
  SIM_ACTION_TYPE_NONE,
  SIM_ACTION_TYPE_TIME_OUT,
  SIM_ACTION_TYPE_MATCHED
} SimActionType;

typedef enum {
  SIM_ACTION_DO_TYPE_NONE,
  SIM_ACTION_DO_TYPE_MAILTO,
  SIM_ACTION_DO_TYPE_DATBASE
} SimActionDoType;

typedef enum {
  SIM_COMMAND_TYPE_NONE,
  SIM_COMMAND_TYPE_CONNECT,
  SIM_COMMAND_TYPE_SESSION_APPEND_PLUGIN,
  SIM_COMMAND_TYPE_SESSION_REMOVE_PLUGIN,
  SIM_COMMAND_TYPE_SERVER,										//msg to send to frameworkd or to master servers
  SIM_COMMAND_TYPE_SERVER_GET_SENSORS,
  SIM_COMMAND_TYPE_SERVER_GET_SERVERS,
  SIM_COMMAND_TYPE_SERVER_GET_SENSOR_PLUGINS,
  SIM_COMMAND_TYPE_SERVER_SET_DATA_ROLE,
  SIM_COMMAND_TYPE_SENSOR,										
  SIM_COMMAND_TYPE_SENSOR_PLUGIN,							
  SIM_COMMAND_TYPE_SENSOR_PLUGIN_START,				
  SIM_COMMAND_TYPE_SENSOR_PLUGIN_STOP,				
  SIM_COMMAND_TYPE_SENSOR_PLUGIN_ENABLE,			
  SIM_COMMAND_TYPE_SENSOR_PLUGIN_DISABLE,		
  SIM_COMMAND_TYPE_PLUGIN_STATE_STARTED,
  SIM_COMMAND_TYPE_PLUGIN_STATE_UNKNOWN,
  SIM_COMMAND_TYPE_PLUGIN_STATE_STOPPED,
  SIM_COMMAND_TYPE_PLUGIN_ENABLED,
  SIM_COMMAND_TYPE_PLUGIN_DISABLED,
  SIM_COMMAND_TYPE_EVENT,
  SIM_COMMAND_TYPE_MESSAGE,
  SIM_COMMAND_TYPE_RELOAD_PLUGINS,
  SIM_COMMAND_TYPE_RELOAD_SENSORS,
  SIM_COMMAND_TYPE_RELOAD_HOSTS,
  SIM_COMMAND_TYPE_RELOAD_NETS,
  SIM_COMMAND_TYPE_RELOAD_POLICIES,
  SIM_COMMAND_TYPE_RELOAD_DIRECTIVES,
  SIM_COMMAND_TYPE_RELOAD_SERVERS,
  SIM_COMMAND_TYPE_RELOAD_ALL,
  SIM_COMMAND_TYPE_WATCH_RULE,
  SIM_COMMAND_TYPE_HOST_OS_EVENT,
  SIM_COMMAND_TYPE_HOST_MAC_EVENT,
  SIM_COMMAND_TYPE_HOST_SERVICE_EVENT,
  SIM_COMMAND_TYPE_OK,
  SIM_COMMAND_TYPE_ERROR,
  SIM_COMMAND_TYPE_PING,
  SIM_COMMAND_TYPE_PONG,
  SIM_COMMAND_TYPE_DATABASE_QUERY,
  SIM_COMMAND_TYPE_DATABASE_ANSWER,
  SIM_COMMAND_TYPE_SNORT_EVENT,
  SIM_COMMAND_TYPE_AGENT_DATE,
  SIM_COMMAND_TYPE_BACKLOG,
  SIM_COMMAND_TYPE_SENSOR_GET_FRAMEWORK,
  SIM_COMMAND_TYPE_FRMK_GETDB,
  SIM_COMMAND_TYPE_IDM_EVENT,
  SIM_COMMAND_TYPE_IDM_RELOAD_CONTEXT,
  SIM_COMMAND_TYPE_AGENT_PING,
  SIM_COMMAND_TYPE_RELOAD_POST_CORRELATION_SIDS,
  SIM_COMMAND_TYPE_NOACK
} SimCommandType;

typedef enum {
  SIM_SESSION_TYPE_NONE,
  SIM_SESSION_TYPE_SERVER_UP,	//master servers: servers which are more "high" in the architecture. Is possible to fetch data from them
  SIM_SESSION_TYPE_SERVER_DOWN, //servers to send data to (children): send the role (correlate, store...), host data, networks, and so on.
//  SIM_SESSION_TYPE_RSERVER,
  SIM_SESSION_TYPE_SENSOR,
	SIM_SESSION_TYPE_FRAMEWORKD,
  SIM_SESSION_TYPE_WEB,
  SIM_SESSION_TYPE_HA,	//High Availability servers
  SIM_SESSION_TYPE_ALL
} SimSessionType;

typedef enum {
  SIM_SESSION_STATE_NONE,
  SIM_SESSION_STATE_DISCONNECT,
  SIM_SESSION_STATE_CONNECT
} SimSessionState;

typedef enum {
	SIM_DB_ELEMENT_TYPE_PLUGINS			,
	SIM_DB_ELEMENT_TYPE_PLUGIN_SIDS	,
	SIM_DB_ELEMENT_TYPE_PLUGIN_REFERENCES	, //cross correlation
	SIM_DB_ELEMENT_TYPE_HOST_PLUGIN_SIDS	, //cross correlation
	SIM_DB_ELEMENT_TYPE_SENSORS			,
	SIM_DB_ELEMENT_TYPE_HOSTS				,
	SIM_DB_ELEMENT_TYPE_NETS				,
	SIM_DB_ELEMENT_TYPE_POLICIES		,
	SIM_DB_ELEMENT_TYPE_HOST_LEVELS ,
	SIM_DB_ELEMENT_TYPE_NET_LEVELS	,
	SIM_DB_ELEMENT_TYPE_SERVER_ROLE	, //as this is a config parameter it won't be stored in container, it will be stored in server's config.
	SIM_DB_ELEMENT_TYPE_LOAD_COMPLETE //this is not a type of element to load. But we will use it in sim_container_new() to tell that we have ended the data loading msgs
} SimDBElementType;

typedef enum {
	SIM_SCHEDULER_STATE_NORMAL	= 0,
	SIM_SCHEDULER_STATE_INITIAL	= 1
} SimSchedulerState;

typedef enum
{
	SIM_POLICY_ELEMENT_TYPE_GENERAL,
	SIM_POLICY_ELEMENT_TYPE_ROLE,
	SIM_POLICY_ELEMENT_TYPE_SRC,
	SIM_POLICY_ELEMENT_TYPE_DST,
	SIM_POLICY_ELEMENT_TYPE_PORTS,
	SIM_POLICY_ELEMENT_TYPE_SENSORS,
	SIM_POLICY_ELEMENT_TYPE_PLUGIN_IDS,
	SIM_POLICY_ELEMENT_TYPE_PLUGIN_SIDS,
	SIM_POLICY_ELEMENT_TYPE_PLUGIN_GROUPS,
	SIM_POLICY_ELEMENT_TYPE_TARGETS
} SimPolicyElementType;

typedef enum
{
	SIM_DIRECTIVE_GROUP_ALARM_BY_SRC_IP = 0,
	SIM_DIRECTIVE_GROUP_ALARM_BY_DST_IP,
	SIM_DIRECTIVE_GROUP_ALARM_BY_SRC_PORT,
	SIM_DIRECTIVE_GROUP_ALARM_BY_DST_PORT,
	SIM_DIRECTIVE_GROUP_ALARM_BY_LAST
}SimDirectiveGroupAlarmByFields;

typedef enum{
  SIM_SESSION_LEGACY_PROTOCOL = 0,
  SIM_SESSION_BSON_PROTOCOL
} session_type;

/* For use in the pulse directives */
#define SIM_DIRECTIVE_PULSE_ID 29998
/* BSON Max packet 2^16 -1 */
#define SIM_MAX_BSON_SIZE 0xFFFF
G_END_DECLS

#endif /* __SIM_ENUMS_H__ */

// vim: set tabstop=2:

