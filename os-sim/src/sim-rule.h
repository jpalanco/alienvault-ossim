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

#ifndef __SIM_RULE_H__
#define __SIM_RULE_H__ 1

#include <glib.h>
#include <glib-object.h>
#include <gnet.h>

G_BEGIN_DECLS

#define SIM_TYPE_RULE                  (sim_rule_get_type ())
#define SIM_RULE(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_RULE, SimRule))
#define SIM_RULE_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_RULE, SimRuleClass))
#define SIM_IS_RULE(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_RULE))
#define SIM_IS_RULE_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_RULE))
#define SIM_RULE_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_RULE, SimRuleClass))

typedef struct _SimRule         SimRule;
typedef struct _SimRuleClass    SimRuleClass;
typedef struct _SimRulePrivate  SimRulePrivate;
typedef struct _SimRuleVar      SimRuleVar;

#include "sim-enums.h"
#include "sim-event.h"
#include "sim-inet.h"
#include "sim-sensor.h"
#include "sim-host.h"
#include "sim-network.h"
#include "sim-context.h"

struct _SimRule {
  GObject parent;

  SimEventType      type;

  SimRulePrivate  *_priv;
};

struct _SimRuleClass {
  GObjectClass parent_class;
};

struct _SimRuleVar {
  SimRuleVarType   type;  //ie.: in the "from" in directives, you can put n:SRC_IP or n:DST_IP. This variable stores wich one is the right
  SimRuleVarType   attr;  //this is used to know wich field is referenced in directives ("from", "to", "src_ip"...)
  gint             level;
  gboolean          negated;  //if this is YES, then the field referenced will be stored in the negated fields (ie. src_ports_not, plugin_sids_not...)
};

GType             sim_rule_get_type                        (void);
SimRule*          sim_rule_new                             (void);



gint              sim_rule_get_level                       (SimRule     *rule);
void              sim_rule_set_level                       (SimRule     *rule,
                                                            gint         level);
gboolean          sim_rule_get_sticky                      (SimRule     *rule);
void              sim_rule_set_sticky                      (SimRule     *rule,
                                                            gboolean     sticky);
SimRuleVarType    sim_rule_get_sticky_different            (SimRule     *rule);
void              sim_rule_set_sticky_different            (SimRule     *rule,
                                                            SimRuleVarType  sticky_different);

gint              sim_rule_get_protocol                    (SimRule     *rule);
void              sim_rule_set_protocol                    (SimRule     *rule,
                                                            gint       protocol);
gboolean          sim_rule_get_not                         (SimRule     *rule);
void              sim_rule_set_not                         (SimRule     *rule,
                                                            gboolean     not);
gchar*            sim_rule_get_name                        (SimRule     *rule);
void              sim_rule_set_name                        (SimRule     *rule,
                                                            const gchar *name);

gint              sim_rule_get_priority                    (SimRule     *rule);
void              sim_rule_set_priority                    (SimRule     *rule,
                                                            gint         priority);
gint              sim_rule_get_reliability                 (SimRule     *rule);
void              sim_rule_set_reliability                 (SimRule     *rule,
                                                            gint         reliability);
gboolean          sim_rule_get_rel_abs                     (SimRule     *rule);
void              sim_rule_set_rel_abs                     (SimRule     *rule,
                                                            gboolean     rel_abs);
time_t             sim_rule_get_time_out                    (SimRule     *rule);
void              sim_rule_set_time_out                    (SimRule     *rule,
                                                            time_t        time_out);
void              sim_rule_set_time_last                  (SimRule   *rule,
                                                           time_t      time_last);
gboolean          sim_rule_is_time_out                    (SimRule      *rule);
gboolean          sim_rule_is_taxonomy_rule               (SimRule      *rule);

gint              sim_rule_get_occurrence                  (SimRule     *rule);
void              sim_rule_set_occurrence                  (SimRule     *rule,
                                                            gint         occurrence);
gint              sim_rule_get_count                       (SimRule     *rule);
void              sim_rule_set_count                       (SimRule     *rule,
                                                            gint         count);

SimConditionType  sim_rule_get_condition                   (SimRule     *rule);
void              sim_rule_set_condition                   (SimRule           *rule,
                                                            SimConditionType   condition);
gchar*            sim_rule_get_value                       (SimRule     *rule);
void              sim_rule_set_value                       (SimRule     *rule,
                                                            const gchar *value);
gint              sim_rule_get_interval                    (SimRule     *rule);
void              sim_rule_set_interval                    (SimRule     *rule,
                                                            gint         interval);
gboolean          sim_rule_get_absolute                    (SimRule     *rule);
void              sim_rule_set_absolute                    (SimRule     *rule,
                                                            gboolean     absolute);

gint              sim_rule_get_plugin_id                   (SimRule     *rule);
void              sim_rule_set_plugin_id                   (SimRule     *rule,
                                                            gint         plugin_id);
gint              sim_rule_get_plugin_sid                  (SimRule     *rule);
void              sim_rule_set_plugin_sid                  (SimRule     *rule,
                                                            gint         plugin_sid);
void              sim_rule_set_not_data                     (SimRule      *rule);

SimInet *         sim_rule_get_src_ia                      (SimRule     *rule);
void              sim_rule_set_src_ia                      (SimRule     *rule,
                                                            SimInet     *ia);
SimInet *         sim_rule_get_dst_ia                      (SimRule     *rule);
void              sim_rule_set_dst_ia                      (SimRule     *rule,
                                                            SimInet     *ia);

SimUuid *         sim_rule_get_src_host_id                 (SimRule     *rule);
void              sim_rule_set_src_host_id                 (SimRule     *rule,
                                                            SimUuid     *src_host_id);

SimUuid *         sim_rule_get_dst_host_id                 (SimRule     *rule);
void              sim_rule_set_dst_host_id                 (SimRule     *rule,
                                                            SimUuid     *dst_host_id);

SimInet *         sim_rule_get_sensor                      (SimRule     *rule);
void              sim_rule_set_sensor                      (SimRule     *rule,
                                                            SimInet     *ia);

gint              sim_rule_get_product                     (SimRule     *rule);
gint              sim_rule_get_category                    (SimRule     *rule);
gint              sim_rule_get_subcategory                 (SimRule     *rule);

gint              sim_rule_get_src_port                    (SimRule     *rule);
void              sim_rule_set_src_port                    (SimRule     *rule,
                                                            gint         src_port);
gint              sim_rule_get_dst_port                    (SimRule     *rule);
void              sim_rule_set_dst_port                    (SimRule     *rule,
                                                            gint         dst_port);
gint              sim_rule_get_src_max_asset               (SimRule     *rule);
gint              sim_rule_get_dst_max_asset               (SimRule     *rule);

void              sim_rule_set_from_rep                    (SimRule     *rule,
                                                            gboolean     from_rep);
void              sim_rule_set_to_rep                      (SimRule     *rule,
                                                            gboolean     to_rep);
void              sim_rule_set_from_rep_min_rel            (SimRule     *rule,
                                                            gint         from_rep_min_rel);
void              sim_rule_set_to_rep_min_rel              (SimRule     *rule,
                                                            gint        to_rep_min_rel);
void              sim_rule_set_from_rep_min_pri            (SimRule     *rule,
                                                            gint         from_rep_min_pri);
void              sim_rule_set_to_rep_min_pri              (SimRule     *rule,
                                                            gint         to_rep_min_pri);

void              sim_rule_append_plugin_sid               (SimRule     *rule,
                                                            gint         plugin_sid);
void              sim_rule_remove_plugin_sid               (SimRule     *rule,
                                                            gint         plugin_sid);

GHashTable*       sim_rule_get_plugin_ids                  (SimRule     *rule);
gboolean          sim_rule_has_plugin_ids                  (SimRule     *rule);
GHashTable*       sim_rule_get_plugin_sids                 (SimRule     *rule);
GHashTable*       sim_rule_get_products                    (SimRule     *rule);
GHashTable*       sim_rule_get_categories                  (SimRule     *rule);
GHashTable*       sim_rule_get_subcategories               (SimRule     *rule);

// From:
void              sim_rule_add_src_host_id                 (SimRule     *rule,
                                                            SimUuid     *host_id);
void              sim_rule_add_src_inet                    (SimRule     *rule,
                                                            SimInet     *inet);
void              sim_rule_add_src_net                     (SimRule     *rule,
                                                            SimNet      *net);
void              sim_rule_add_expand_src_asset_name       (SimRule     *rule,
                                                            gchar       *name);
void              sim_rule_remove_src_inet                 (SimRule     *rule,
                                                            SimInet     *inet);
SimNetwork *      sim_rule_get_src_inets                   (SimRule     *rule);
GList *           sim_rule_get_expand_src_assets_names     (SimRule     *rule);

// To:
void              sim_rule_add_dst_host_id                 (SimRule     *rule,
                                                            SimUuid     *host_id);
void              sim_rule_add_dst_inet                    (SimRule     *rule,
                                                            SimInet     *inet);
void              sim_rule_add_dst_net                     (SimRule     *rule,
                                                            SimNet      *net);
void              sim_rule_add_expand_dst_asset_name       (SimRule     *rule,
                                                            gchar       *name);
void              sim_rule_remove_dst_inet                 (SimRule     *rule,
                                                            SimInet     *inet);
SimNetwork *      sim_rule_get_dst_inets                   (SimRule     *rule);
GList *           sim_rule_get_expand_dst_assets_names     (SimRule     *rule);

void              sim_rule_append_src_port                 (SimRule     *rule,
                                                            gint         src_port);
void              sim_rule_remove_src_port                 (SimRule     *rule,
                                                            gint         src_port);
GHashTable*       sim_rule_get_src_ports                   (SimRule     *rule);

void              sim_rule_append_dst_port                 (SimRule     *rule,
                                                            gint         dst_port);
void              sim_rule_remove_dst_port                 (SimRule     *rule,
                                                            gint         dst_port);
GHashTable*       sim_rule_get_dst_ports                   (SimRule     *rule);

void              sim_rule_append_protocol                 (SimRule     *rule,
                                                            SimProtocolType  protocol);
void              sim_rule_remove_protocol                 (SimRule     *rule,
                                                            SimProtocolType  protocol);
GHashTable*       sim_rule_get_protocols                   (SimRule     *rule);

void              sim_rule_append_sensor                   (SimRule     *rule,
                                                            SimSensor   *sensor);
void              sim_rule_remove_sensor                   (SimRule     *rule,
                                                            SimSensor   *sensor);
GHashTable*       sim_rule_get_sensors                     (SimRule     *rule);
GList *           sim_rule_get_expand_sensors_names        (SimRule     *rule);

gchar*            sim_rule_get_filename                     (SimRule   *rule);
void              sim_rule_set_filename                     (SimRule   *rule,
                                                             gchar     *filename);
gchar*            sim_rule_get_username                     (SimRule   *rule);
void              sim_rule_set_username                     (SimRule   *rule,
                                                             gchar     *username);
gchar*            sim_rule_get_password                     (SimRule   *rule);
void              sim_rule_set_password                     (SimRule   *rule,
                                                             gchar     *password);
gchar*            sim_rule_get_userdata1                    (SimRule   *rule);
void              sim_rule_set_userdata1                    (SimRule   *rule,
                                                             gchar     *userdata1);
gchar*            sim_rule_get_userdata2                    (SimRule   *rule);
void              sim_rule_set_userdata2                    (SimRule   *rule,
                                                             gchar     *userdata2);
gchar*            sim_rule_get_userdata3                    (SimRule   *rule);
void              sim_rule_set_userdata3                    (SimRule   *rule,
                                                             gchar     *userdata3);
gchar*            sim_rule_get_userdata4                    (SimRule   *rule);
void              sim_rule_set_userdata4                    (SimRule   *rule,
                                                             gchar     *userdata4);
gchar*            sim_rule_get_userdata5                    (SimRule   *rule);
void              sim_rule_set_userdata5                    (SimRule   *rule,
                                                             gchar     *userdata5);
gchar*            sim_rule_get_userdata6                    (SimRule   *rule);
void              sim_rule_set_userdata6                    (SimRule   *rule,
                                                             gchar     *userdata6);
gchar*            sim_rule_get_userdata7                    (SimRule   *rule);
void              sim_rule_set_userdata7                    (SimRule   *rule,
                                                             gchar     *userdata7);
gchar*            sim_rule_get_userdata8                    (SimRule   *rule);
void              sim_rule_set_userdata8                    (SimRule   *rule,
                                                             gchar     *userdata8);
gchar*            sim_rule_get_userdata9                    (SimRule   *rule);
void              sim_rule_set_userdata9                    (SimRule   *rule,
                                                             gchar     *userdata9);



void              sim_rule_set_pulse_id                     (SimRule *rule,
                                                             const gchar *pulse_id);

const gchar *     sim_rule_get_pulse_id                     (SimRule *rule);

GList *           sim_rule_get_generic                      (SimRule        *rule,
                                                             SimRuleVarType  field_type);

void              sim_rule_append_var                      (SimRule     *rule,
                                                            SimRuleVar  *var);
GList*            sim_rule_get_vars                        (SimRule     *rule);
GPtrArray *       sim_rule_get_events                      (SimRule * rule);

void              sim_rule_add_event                       (SimRule  * rule,
                                                            SimEvent * event);

SimRule*          sim_rule_clone                           (SimRule     *rule);

void              sim_rule_set_event_data                  (SimRule     *rule,
                                                            SimEvent    *event);
gboolean          sim_rule_match_by_event                  (SimRule     *rule,
                                                            SimEvent    *event);

void              sim_rule_print                           (SimRule     *rule);

gchar*            sim_rule_to_string                       (SimRule     *rule);

gboolean          sim_rule_is_not_invalid                   (SimRule      *rule);

gboolean          find_gint_value                         (GList      *values, gint val);

void              sim_rule_add_secure_src_inet             (SimRule     *rule,
                                                            SimInet     *inet);
void              sim_rule_add_secure_dst_inet             (SimRule     *rule,
                                                            SimInet     *inet);
void              sim_rule_add_src_host_id_not             (SimRule     *rule,
                                                            SimUuid     *host_id);
void              sim_rule_add_dst_host_id_not             (SimRule     *rule,
                                                            SimUuid     *host_id);
void              sim_rule_add_secure_src_inet_not         (SimRule     *rule,
                                                            SimInet     *inet);
void              sim_rule_add_src_net_not                 (SimRule     *rule,
                                                            SimNet      *net);
void              sim_rule_add_secure_dst_inet_not         (SimRule     *rule,
                                                            SimInet     *inet);
void              sim_rule_add_dst_net_not                 (SimRule     *rule,
                                                            SimNet      *net);
void              sim_rule_add_expand_entity               (SimRule     *rule,
                                                            gchar       *name);
void              sim_rule_add_expand_entity_not           (SimRule     *rule,
                                                            gchar       *name);
void              sim_rule_add_entity                      (SimRule     *rule,
                                                            SimContext  *context);
void              sim_rule_add_entity_not                  (SimRule     *rule,
                                                            SimContext  *context);
SimContext *      sim_rule_get_entity                      (SimRule     *rule);
GList *           sim_rule_get_expand_entities             (SimRule     *rule);
GList *           sim_rule_get_expand_entities_not         (SimRule     *rule);

time_t           sim_rule_get_time_last (SimRule   *rule);
void             sim_rule_add_plugin_id (SimRule   *rule, gint       plugin_id);
void             sim_rule_add_plugin_sid (SimRule   *rule, gint       plugin_sid);
void             sim_rule_add_src_port (SimRule   *rule, gint       src_port);
void             sim_rule_add_dst_port (SimRule   *rule, gint       dst_port);
void             sim_rule_add_protocol (SimRule   *rule, SimProtocolType  protocol);
void             sim_rule_add_sensor (SimRule    *rule, SimSensor  *sensor);
void             sim_rule_add_product (SimRule *rule, gint product);
void             sim_rule_add_category (SimRule *rule, gint category);
void             sim_rule_add_subcategory (SimRule *rule, gint subcategory);
void             sim_rule_add_suppress (SimRule *rule, gint plugin_id, gint plugin_sid);
void             sim_rule_add_expand_sensor_name (SimRule *rule, gchar *name);
void             sim_rule_append_generic_text (SimRule        *rule, gchar  *data, SimRuleVarType field_type);
void             sim_rule_remove_generic_text (SimRule        *rule, gchar            *data, SimRuleVarType field_type);
void             sim_rule_remove_generic_text (SimRule        *rule, gchar            *data,
                                               SimRuleVarType  field_type);
void            sim_rule_add_src_inet_not (SimRule *rule, SimInet *src_inet);
void            sim_rule_add_expand_src_asset_name_not (SimRule *rule, gchar *name);
void            sim_rule_add_dst_inet_not (SimRule *rule, SimInet *dst_inet);
void            sim_rule_add_expand_dst_asset_name_not (SimRule *rule, gchar *name);
void            sim_rule_add_src_port_not (SimRule *rule, gint  src_port);
void            sim_rule_add_dst_port_not (SimRule *rule, gint  dst_port);
void            sim_rule_add_plugin_id_not (SimRule *rule, gint plugin_id);
void            sim_rule_add_plugin_sid_not (SimRule *rule, gint plugin_sid);
void            sim_rule_add_protocol_not (SimRule *rule, gint protocol);
void            sim_rule_add_sensor_not (SimRule *rule, SimSensor *sensor);
void            sim_rule_add_product_not (SimRule *rule, gint product);
void            sim_rule_add_category_not (SimRule *rule, gint category);
void            sim_rule_add_subcategory_not (SimRule *rule, gint subcategory);
void            sim_rule_add_expand_sensor_name_not (SimRule *rule, gchar *name);
void            sim_rule_remove_src_inet_not (SimRule *rule, SimInet *src_inet);
void            sim_rule_remove_dst_inet_not (SimRule *rule, SimInet *dst_inet);
void            sim_rule_remove_src_port_not (SimRule *rule, gint   src_port);
void            sim_rule_remove_dst_port_not (SimRule *rule, gint dst_port);
void            sim_rule_remove_plugin_sid_not (SimRule *rule, gint plugin_sid);
void            sim_rule_remove_protocol_not (SimRule *rule, gint protocol);
void            sim_rule_remove_sensor_not (SimRule *rule, SimSensor *sensor);
SimNetwork *    sim_rule_get_src_inets_not (SimRule *rule);
GList *         sim_rule_get_expand_src_assets_names_not (SimRule *rule);
GHashTable*     sim_rule_get_src_ports_not (SimRule *rule);
SimNetwork *    sim_rule_get_dst_inets_not (SimRule *rule);
GList *         sim_rule_get_expand_dst_assets_names_not (SimRule *rule);
GHashTable*     sim_rule_get_dst_ports_not (SimRule *rule);
GHashTable*     sim_rule_get_plugin_ids_not (SimRule *rule);
GHashTable*     sim_rule_get_plugin_sids_not (SimRule *rule);
GHashTable*     sim_rule_get_protocols_not (SimRule *rule);
GHashTable*     sim_rule_get_sensors_not (SimRule *rule);
GList *         sim_rule_get_expand_sensors_names_not (SimRule     *rule);
void            sim_rule_append_generic_text_not  (SimRule          *rule,
                                                   gchar            *data,
                                                   SimRuleVarType    field_type);
void            sim_rule_remove_generic_text_not  (SimRule          *rule,
                                                   gchar            *data,
                                                   SimRuleVarType    field_type);
GList *         sim_rule_get_generic_text_not (SimRule        *rule,
                                               SimRuleVarType field_type);
gint            sim_rule_get_reliability_relative (GNode   *rule_node);

gboolean        _check_is_reserved                  (SimRadixNode   *node,
                                                     void           *ud);
void            sim_rule_set_src_is_home_net        (SimRule        *rule,
                                                     gboolean        yes_no);
void            sim_rule_set_src_is_home_net_not    (SimRule        *rule,
                                                     gboolean        yes_no);
void            sim_rule_set_dst_is_home_net        (SimRule        *rule,
                                                     gboolean        yes_no);
void            sim_rule_set_dst_is_home_net_not    (SimRule        *rule,
                                                     gboolean        yes_no);
void            sim_rule_free_expand_items          (SimRule        *rule);

#ifdef USE_UNITTESTS
void            sim_rule_register_tests          (SimUnittesting *engine);
#endif


G_END_DECLS

#endif /* __SIM_RULE_H__ */
// vim: set tabstop=2:
