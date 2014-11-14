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

#ifndef __SIM_EVENT_H__
#define __SIM_EVENT_H__ 1

#include <glib.h>
#include <glib-object.h>
#include <gnet.h>

G_BEGIN_DECLS

extern GType _sim_event_type;

#define SIM_TYPE_EVENT          (_sim_event_type)
#define SIM_IS_EVENT(obj)       (SIM_IS_MINI_OBJECT_TYPE(obj, SIM_TYPE_EVENT))
#define SIM_EVENT_CAST(obj)     ((SimEvent *)(obj))
#define SIM_EVENT(obj)          (SIM_EVENT_CAST(obj))

typedef struct _SimEvent        SimEvent;
typedef struct _SimEventClass   SimEventClass;

#include "sim-policy.h"
#include "sim-enums.h"
#include "sim-plugin.h"
#include "sim-plugin-sid.h"
#include "sim-text-fields.h"
#include "sim-inet.h"
#include "sim-context.h"
#include "sim-engine.h"
#include "sim-mini-object.h"
#include "sim-role.h"
#include "sim-uuid.h"
#include "sim-sensor.h"

/* Fixes Plugin id */
#define SIM_PLUGIN_ID_NESSUS          3001
#define SIM_PLUGIN_ID_OS              5001
#define SIM_PLUGIN_SERVICE            5002

#define SIM_PLUGIN_ID_DIRECTIVE_EVENT 1505

/* Needed to know which correlation process matched with a event. */
enum
{
  EVENT_MATCH_NOTHING              = 1 << 0,
  EVENT_MATCH_DIRECTIVE_CORR       = 1 << 1,
  EVENT_MATCH_CROSS_CORR           = 1 << 2,
  EVENT_MATCH_REPUTATION           = 1 << 3
};

//SimPolicy is each one of the "lines" in the policy. It has one or more sources, one or more destinations, a time range, and so on.

typedef struct _SimHostServices	SimHostServices;	//used only for cross_correlation at this moment.
struct _SimHostServices
{
	gchar	*ip;
	gint port;
	gint protocol;
	gchar	*service;
	gchar *version;
	gchar	*date;
	gchar	*sensor;
};


struct _SimEvent {
  SimMiniObject      mini_object;
  guint				 signature; // Always here AFTER parent. For event if has the value 0xdeadbeef
  gchar				*sql_text_fields; // DONT FREE THIS. Is a pointer to a atrea reserver in the class structure
  guint              id_transaction; // old 'id' field
  SimUuid           *id;

  SimEventType       type;

  /* Event Info */
  time_t              time; // time in seconds
  gchar								*time_str; // time as string
  time_t              diff_time; //as soon as the event arrives, this is setted. Here is stored the difference between the parsed time from agent log
  //line, and the time when the event arrives to server.
  gfloat								tzone;

  SimInet           *sensor;
  SimUuid           *sensor_id;
  SimInet           *device;
  guint              device_id; // from table alienvault_siem.device
  gchar             *interface;
  gchar 						*server;	//the event comes from this server (IP)
  gchar 						*servername;	//the event comes from this server (name?)
//  gchar             *hostname; //this identifies the server/sensor from where the event comes. If the event has been received from a child server, this will take that server's name. If the event is received from a sensor, it will use the sensor's name

  /* Plugin Info */
  gint               plugin_id;
  gint               plugin_sid;

  /* Plugin Type Detector */
  SimProtocolType    protocol;
  SimInet           *src_ia;
  SimInet           *dst_ia;
  SimNet           * src_net;
  SimNet           * dst_net;
  gint               src_port;
  gint               dst_port;
  const gchar       *src_country;
  const gchar       *dst_country;

  /* Plugin Type Monitor */
  SimConditionType   condition;
  gchar             *value;
  gint               interval;

  /* Extra data */
  gboolean           alarm;
  gint               priority;
  gint               reliability;
  gint               asset_src;
  gint               asset_dst;
  gdouble            risk_c;
  gdouble            risk_a;

  gchar             *data;
  GString           *log;
  gchar             *alarm_stats;

  /* Directives */
  gboolean           rule_matched;  // TRUE if this has been matched the rule in sim_rule_match_by_event()
  gboolean           directive_matched; // TRUE if this event matches with any directive/backlog.
  gint               count;
  SimUuid           *backlog_id;

  /* Used for alarms*/
  gboolean					is_stored;
  gboolean					is_snort_stored;
  gboolean					is_remote;

  /* replication  server */
  gboolean           rserver;

  gchar							**data_storage; // This variable must be used ONLY to pass data between the sim-session and 
  //sim-organizer, where the event is stored in DB.
  gboolean					store_in_DB;		//variable used to know if this specific event should be stored in DB or not. Used in Policy.
  gboolean          can_delete;     //variable used to know if the other thread (organizer||database_storage) is done with this event
  gchar             *buffer;				//used to speed up the resending events so it's not needed to turn it again into a string

  gboolean					is_correlated;	//Just needed for MAC, OS, Service and HIDS events.
  // Take an example: server1 resend data to server2. We have correlated in server1 a MAC event.
  // Then we resend the event to server2 in both ways: "host_mac_event...." and "event...". Obviously,
  // "event..." is the event correlated, with priority, risk information and so on. But we don't want
  // to re-correlate "host_mac_event...", because the correlation information is in "event...". So in 
  // sim_organizer_correlation() we check this variable. Also, in this way, we are able to correlate
  // the event with another event which arrives to server2.

  gint              correlation;    /* Needed to know which correlation mechanism has matched this event.
                                     * Valid values:
                                     * EVENT_MATCH_NOTHING : 0             (didn't matched)
                                     * EVENT_MATCH_DIRECTIVE_CORR : 1      (matched with directive in correlation)
                                     * EVENT_MATCH_CROSS_CORR : 2          (matched in cross correlation process)
                                     * EVENT_MATCH_DIRECTIVE_AND_CROSS : 3 (matched with directive and cross correlation)
                                     */

  gboolean          is_priority_set;        // Needed to know if the event was received with priority
  gboolean          is_reliability_set; // Needed to know if the event was received with realiability
  gboolean			belongs_to_alarm; //This variable is used for multiserver resend, to tell the upper servers that an event is part of an alarm
  SimRole 			*role;
  SimPolicy			*policy;				//This is the policy that matches with an event (if any). This memory MUSTN'T be released as it belong
  //to the Container.

  /* additional data (not necessary used) */
  gchar							*filename;
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

  gchar             *rulename;

  // IDM info
  SimUuid           *src_id; // host UUID
  SimUuid           *dst_id;
  GHashTable        *src_username; // key: gchar *username, value: gchar *domain
  gchar             *src_username_raw;
  GHashTable        *dst_username;
  gchar             *dst_username_raw;
  gchar             *src_hostname;
  gchar             *dst_hostname;
  gchar             *src_mac;
  gchar             *dst_mac;
  
  // Reputation data.
  GHashTable  *rep_act_src;
  gchar       *str_rep_act_src;
  GHashTable  *rep_act_dst;
  gchar       *str_rep_act_dst;
  gint       rep_prio_src;
  gint       rep_prio_dst;
  gint       rep_rel_src;
  gint       rep_rel_dst;

  gint   tax_product;
  gint   tax_category;
  gint   tax_subcategory;

  /* SHA1 need for the group alarm */
  gchar  *groupalarmsha1; /* The sha1 is a signature of 40 bytes + 1 for end of string */

  // Saqqara specific
  SimUuid    * saqqara_backlog_id;
  gint         level;

  SimContext  *context;
  SimEngine   *engine;
};

struct _SimEventClass {
  GObjectClass parent_class;
	gchar *sql_text_fields;
};

void       _priv_sim_event_initialize       (void);
SimEvent * sim_event_ref                    (SimEvent *event);
void       sim_event_unref_null             (SimEvent *event);
void       sim_event_unref                  (SimEvent *event);
SimEvent * sim_event_copy                   (SimEvent *event);

GType			sim_event_get_type								(void);
SimEvent*	sim_event_new											(void);
SimEvent*	sim_event_new_from_type						(SimEventType	 type);
SimEvent* sim_event_new_full                (SimEventType  type,
                                             SimUuid      *event_id,
                                             SimUuid      *context_id);
SimEvent* sim_event_light_new_from_dm       (GdaDataModel *dm,
                                             gint row);

SimEvent*	sim_event_clone										(SimEvent	*event);

gchar *   sim_event_get_insert_clause        (SimEvent *event);
gchar *   sim_event_get_insert_clause_values (SimEvent *event);
gchar *   sim_event_get_insert_clause_header (void);
gchar *       sim_event_idm_get_insert_clause           (GdaConnection *conn,
                                                         SimEvent *event);
const gchar * sim_event_idm_get_insert_clause_header    (void);
gchar *       sim_event_idm_get_insert_clause_values    (GdaConnection *conn,
                                                         SimEvent *event);
gchar *       sim_event_extra_get_insert_clause         (GdaConnection *conn,
                                                         SimEvent *event);
const gchar * sim_event_extra_get_insert_clause_header  (void);
gchar *       sim_event_extra_get_insert_clause_values    (GdaConnection *conn,
                                                         SimEvent *event);
gchar *       sim_event_get_alarm_insert_clause         (SimDatabase *db_ossim,
                                                         SimEvent *event,
                                                         gboolean  removable);

const gchar * sim_event_get_acid_event_insert_clause_header (void);

gchar*		sim_event_to_string								(SimEvent	*event);

gchar*		sim_event_get_msg									(SimEvent	*event);
gboolean	sim_event_is_special							(SimEvent *event);

gchar*    sim_event_get_str_from_type       (SimEventType type);
gboolean	sim_event_set_sid   							(SimEvent *event);
void      sim_event_enrich_idm              (SimEvent *event);
void      sim_event_set_asset_value         (SimEvent *event);
gboolean	sim_event_set_role_and_policy 		(SimEvent *event);
const gchar *sim_event_get_sql_fields (void);
SimEventType sim_event_get_type_from_str (const gchar *str);

void sim_event_sanitize (SimEvent *event);
gchar *sim_event_get_text_escape_fields_values (SimEvent *event);


void      sim_event_set_src_host_properties (SimEvent *event,
                                             SimHost  *host);
void      sim_event_set_dst_host_properties (SimEvent *event,
                                             SimHost  *host);
void       sim_event_set_context_and_engine (SimEvent *event,
                                             SimUuid  *context_id);

G_END_DECLS

#endif /* __SIM_EVENT_H__ */

// vim: set tabstop=2:

