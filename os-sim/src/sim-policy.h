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

#ifndef __SIM_POLICY_H__
#define __SIM_POLICY_H__ 1

#include <glib.h>
#include <glib-object.h>
#include <gnet.h>
#include <libgda/libgda.h>

typedef struct _SimPolicy        SimPolicy;
typedef struct _SimPolicyClass   SimPolicyClass;
typedef struct _SimPolicyPrivate SimPolicyPrivate;

#include "sim-enums.h"
#include "sim-util.h"
#include "sim-inet.h"
#include "sim-role.h"
#include "sim-network.h"
#include "sim-reputation.h"
#include "sim-uuid.h"

G_BEGIN_DECLS

#define SIM_TYPE_POLICY                  (sim_policy_get_type ())
#define SIM_POLICY(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_POLICY, SimPolicy))
#define SIM_POLICY_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_POLICY, SimPolicyClass))
#define SIM_IS_POLICY(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_POLICY))
#define SIM_IS_POLICY_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_POLICY))
#define SIM_POLICY_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_POLICY, SimPolicyClass))

//SimPolicy is each one of the "lines" in the policy. It has one or more sources, one or more destinations, a time range, and so on.


struct _SimPolicy {
  GObject parent;

  SimPolicyPrivate *_priv;
};

struct _SimPolicyClass {
  GObjectClass parent_class;
};


/* Risk level data */
typedef struct _SimPolicyRisk SimPolicyRisk;

#define SIM_POLICY_RISK_ANY -1

struct _SimPolicyRisk {
  gint priority;
  gint reliability;
};


// Reputation data.
typedef struct _SimPolicyRep
{
  gint priority;
  gint reliability;
  gint activity;
}
SimPolicyRep;

#define SIM_POLICY_REP_ANY_PRIO -1
#define SIM_POLICY_REP_ANY_REL  -1
#define SIM_POLICY_REP_ANY_ACT  -1

/* Prototypes */
GType             sim_policy_get_type                        (void);

SimPolicy*        sim_policy_new                             (void);
SimPolicy*        sim_policy_new_from_dm                     (GdaDataModel     *dm,
                                                              gint              row);

SimUuid *         sim_policy_get_id                          (SimPolicy        *policy);
void              sim_policy_set_id                          (SimPolicy        *policy,
                                                              SimUuid         * id);

SimUuid *         sim_policy_get_context_id                  (SimPolicy       * policy);
void              sim_policy_set_context_id                  (SimPolicy       * policy,
                                                              SimUuid         * context_id);

gint              sim_policy_get_priority                    (SimPolicy        *policy);
void              sim_policy_set_priority                    (SimPolicy        *policy,
                                                              gint              priority);

gint              sim_policy_get_begin_day                   (SimPolicy        *policy);
void              sim_policy_set_begin_day                   (SimPolicy        *policy,
                                                              gint              begin_day);

gint              sim_policy_get_end_day                     (SimPolicy        *policy);
void              sim_policy_set_end_day                     (SimPolicy        *policy,
                                                              gint              end_day);

gint              sim_policy_get_begin_hour                  (SimPolicy        *policy);
void              sim_policy_set_begin_hour                  (SimPolicy        *policy,
                                                              gint              begin_hour);

gint              sim_policy_get_end_hour                    (SimPolicy        *policy);
void              sim_policy_set_end_hour                    (SimPolicy        *policy,
                                                              gint              end_hour);

gboolean          sim_policy_get_store                       (SimPolicy        *policy);
void              sim_policy_set_store                       (SimPolicy        *policy, gboolean store);

/* Sources Inet Address */
void              sim_policy_append_src                      (SimPolicy        *policy,
                                                              SimInet          *src);
SimNetwork *      sim_policy_get_src                         (SimPolicy        *policy);

/* Destination Inet Address */
void              sim_policy_append_dst                      (SimPolicy        *policy,
                                                              SimInet          *dst);
SimNetwork *      sim_policy_get_dst                         (SimPolicy        *policy);

/* Ports */
void              sim_policy_append_port_src              (SimPolicy        *policy,
                                                           SimPortProtocol  *pp);
void              sim_policy_remove_port_src              (SimPolicy        *policy,
                                                           SimPortProtocol  *pp);
GList*            sim_policy_get_ports_src                (SimPolicy        *policy);
void              sim_policy_append_port_dst              (SimPolicy        *policy,
                                                           SimPortProtocol  *pp);
void              sim_policy_remove_port_dst              (SimPolicy        *policy,
                                                           SimPortProtocol  *pp);
GList*            sim_policy_get_ports_dst                (SimPolicy        *policy);

void              sim_policy_free_ports                   (SimPolicy        *policy);

/* Sensors */
GList*            sim_policy_get_sensors                     (SimPolicy        *policy);
void              sim_policy_free_sensors                    (SimPolicy        *policy);
void              sim_policy_append_sensor                   (SimPolicy        *policy,
									                          						      gchar            *sensor);


gboolean          sim_policy_match                           (SimPolicy        *policy,
                                                              SimEvent        * event,
                                                              SimPortProtocol  *src_pp,
                                                              SimPortProtocol  *dst_pp);

/* Plugin_ids */
void              sim_policy_append_plugin_id							     (SimPolicy        *policy,
																													     guint            *plugin_id);
void              sim_policy_remove_plugin_id								   (SimPolicy        *policy,
																													     guint            *plugin_id);
GList*            sim_policy_get_plugin_ids										 (SimPolicy        *policy);
void              sim_policy_free_plugin_ids		               (SimPolicy        *policy);

/* Plugin_sids */
void              sim_policy_append_plugin_sid							   (SimPolicy        *policy,
																													     guint            *plugin_sid);
void              sim_policy_remove_plugin_sid                 (SimPolicy        *policy,
																													     guint            *plugin_sid);
GList*            sim_policy_get_plugin_sids			             (SimPolicy        *policy);
void              sim_policy_free_plugin_sids				           (SimPolicy        *policy);


/* Plugin groups */
void              sim_policy_append_plugin_group	             (SimPolicy        *policy,
																														    Plugin_PluginSid	*plugin_group);
void              sim_policy_remove_plugin_group               (SimPolicy        *policy,
																														    Plugin_PluginSid	*plugin_group);
GList*            sim_policy_get_plugin_groups                 (SimPolicy        *policy);
void              sim_policy_free_plugin_groups                (SimPolicy        *policy);


void							sim_policy_debug_print												(SimPolicy				*policy);

SimRole *         sim_policy_get_role                           (SimPolicy          *policy);
void		          sim_policy_set_role                           (SimPolicy          *policy,
                                                                 SimRole            *role);
gboolean          sim_policy_has_role                           (SimPolicy          *policy);

/* Servers */
void              sim_policy_append_risk                       (SimPolicy        *policy,
                                                                gint              priority,
                                                                gint              reliability);

gint              sim_policy_get_has_actions 	    		   (SimPolicy* policy);
void			  sim_policy_set_has_actions 				   (SimPolicy* policy, gint actions);
gint 			  sim_policy_get_has_alarm_actions 			   (SimPolicy* policy);
void			  sim_policy_set_has_alarm_actions 			   (SimPolicy* policy, gint alarm_actions);

void              sim_policy_remove_sensor                      (SimPolicy        *policy,
                                                                 gchar            *sensor);

gboolean          sim_policy_get_any_src                   (SimPolicy *);
void              sim_policy_set_any_src                   (SimPolicy *, gboolean);
gboolean          sim_policy_get_any_dst                   (SimPolicy *);
void              sim_policy_set_any_dst                   (SimPolicy *, gboolean);
void              sim_policy_add_reputation_src            (SimPolicy *, SimPolicyRep *);
void              sim_policy_add_reputation_dst            (SimPolicy *, SimPolicyRep *);
void              sim_policy_free_reputation_src           (SimPolicy *);
void              sim_policy_free_reputation_dst           (SimPolicy *);
void              sim_policy_set_taxonomy                  (SimPolicy * policy,
                                                            GHashTable * taxonomy);
void              sim_policy_add_src_host                  (SimPolicy *, SimUuid *);
void              sim_policy_add_dst_host                  (SimPolicy *, SimUuid *);
/* For unit testing */
gboolean          sim_policy_match_risk                     (SimPolicy *policy, SimEvent  *event);


#ifdef USE_UNITTESTS
void sim_policy_register_tests (SimUnittesting *engine);
#endif

G_END_DECLS

#endif /* __SIM_POLICY_H__ */
// vim: set tabstop=2:
