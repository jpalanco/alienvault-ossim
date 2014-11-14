/*
  License:

  Copyright (c) 2012-2013 AlienVault
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


#include "config.h"

#include "sim-alarm-stats.h"

#include <time.h>
#include <glib.h>
#include <json-glib/json-glib.h>

#include "os-sim.h"
#include "sim-event.h"
#include "sim-geoip.h"
#ifdef USE_UNITTESTS
#include "sim-command.h"
#endif

extern SimMain ossim;

typedef struct _SimAlarmStatsInfoIp
{
  gint         ip_count;
  gint         reputation;
  const gchar *country;
  char        *host_uuid;
} SimAlarmStatsInfoIp;

typedef struct _SimAlarmStatsInfo
{
  GHashTable *info_ip;
  GHashTable *ports;
  gint        reputation_count;
  GHashTable *countries;
} SimAlarmStatsInfo;

struct _SimAlarmStatsPrivate
{
  gint               event_count;
  SimAlarmStatsInfo *info_src;
  SimAlarmStatsInfo *info_dst;
};

static void sim_alarm_stats_init (SimAlarmStats *alarm_stats);
static SimAlarmStatsInfo *sim_alarm_stats_info_new (void);
static SimAlarmStats *_sim_alarm_stats_copy (SimAlarmStats *alarm_stats);
static void sim_alarm_stats_copy_info (SimAlarmStatsInfo *alarm_stats_info_new, SimAlarmStatsInfo *alarm_stats_info);
static void _sim_alarm_stats_free (SimAlarmStats *alarm_stats);
static void sim_alarm_stats_info_free (SimAlarmStatsInfo *alarm_stats_info);
static void sim_alarm_stats_info_ip_free (gpointer data);
static void sim_alarm_stats_update_info (SimAlarmStatsInfo *alarm_stats_info, SimInet *ip, SimUuid *host_uuid, gint port, gboolean reputation, const gchar *country);
static void sim_alarm_stats_update_numbers (GHashTable *numbers, gint number);
static void sim_alarm_stats_update_countries (GHashTable *countries, const gchar *country);
static void sim_alarm_stats_info_to_json (SimAlarmStatsInfo *alarm_stats_info, JsonObject *object, gboolean is_src);
static void sim_alarm_stats_info_ip_to_json (gpointer key, gpointer value, gpointer user_data);
static void sim_alarm_stats_numbers_to_json (gpointer key, gpointer value, gpointer user_data);
static void sim_alarm_stats_countries_to_json (gpointer key, gpointer value, gpointer user_data);

/* GType Functions */

GType _sim_alarm_stats_type = 0;

SIM_DEFINE_MINI_OBJECT_TYPE (SimAlarmStats, sim_alarm_stats)

void
_priv_sim_alarm_stats_initialize (void)
{
  _sim_alarm_stats_type = sim_alarm_stats_get_type ();
}

static void
sim_alarm_stats_init (SimAlarmStats *alarm_stats)
{
  sim_mini_object_init (SIM_MINI_OBJECT_CAST (alarm_stats), _sim_alarm_stats_type);

  alarm_stats->mini_object.copy = (SimMiniObjectCopyFunction) _sim_alarm_stats_copy;
  alarm_stats->mini_object.dispose = NULL;
  alarm_stats->mini_object.free = (SimMiniObjectFreeFunction) _sim_alarm_stats_free;
}

SimAlarmStats *
sim_alarm_stats_new (void)
{
  SimAlarmStats *alarm_stats;

  alarm_stats = g_new (SimAlarmStats, 1);
  sim_alarm_stats_init (alarm_stats);

  alarm_stats->_priv = g_new (SimAlarmStatsPrivate, 1);
  alarm_stats->_priv->event_count = 0;
  alarm_stats->_priv->info_src = sim_alarm_stats_info_new ();
  alarm_stats->_priv->info_dst = sim_alarm_stats_info_new ();

  return alarm_stats;
}

static SimAlarmStatsInfo *
sim_alarm_stats_info_new (void)
{
  SimAlarmStatsInfo *alarm_stats_info;

  alarm_stats_info = g_new (SimAlarmStatsInfo, 1);
  alarm_stats_info->info_ip = g_hash_table_new_full (sim_inet_hash, sim_inet_equal, g_object_unref, sim_alarm_stats_info_ip_free);
  alarm_stats_info->ports = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, g_free);
  alarm_stats_info->reputation_count = 0;
  alarm_stats_info->countries = g_hash_table_new_full (g_str_hash, g_str_equal, NULL, g_free);

  return alarm_stats_info;
}

SimAlarmStats *
sim_alarm_stats_copy (SimAlarmStats *alarm_stats)
{
  return (SimAlarmStats *) sim_mini_object_copy (SIM_MINI_OBJECT_CAST (alarm_stats));
}

static SimAlarmStats *
_sim_alarm_stats_copy (SimAlarmStats *alarm_stats)
{
  SimAlarmStats *new_alarm_stats;

  new_alarm_stats = sim_alarm_stats_new ();
  new_alarm_stats->_priv->event_count = alarm_stats->_priv->event_count;

  sim_alarm_stats_copy_info(new_alarm_stats->_priv->info_src, alarm_stats->_priv->info_src);
  sim_alarm_stats_copy_info(new_alarm_stats->_priv->info_dst, alarm_stats->_priv->info_dst);

  return new_alarm_stats;
}

static void
sim_alarm_stats_copy_info (SimAlarmStatsInfo *alarm_stats_info_new, SimAlarmStatsInfo *alarm_stats_info)
{
  // TODO: implement this if necessary
  g_return_if_fail (NULL);

  (void)alarm_stats_info_new;
  (void)alarm_stats_info;
}

static void
_sim_alarm_stats_free (SimAlarmStats *alarm_stats)
{
  sim_alarm_stats_info_free (alarm_stats->_priv->info_src);
  sim_alarm_stats_info_free (alarm_stats->_priv->info_dst);
  g_free (alarm_stats->_priv);
  g_free (alarm_stats);
}

static void
sim_alarm_stats_info_free (SimAlarmStatsInfo *alarm_stats_info)
{
  g_hash_table_unref (alarm_stats_info->info_ip);
  g_hash_table_unref (alarm_stats_info->ports);
  g_hash_table_unref (alarm_stats_info->countries);
  g_free (alarm_stats_info);
}

static void
sim_alarm_stats_info_ip_free (gpointer data)
{
  SimAlarmStatsInfoIp *info_ip = data;

  g_free (info_ip->host_uuid);
  g_free (info_ip);
}

SimAlarmStats *
sim_alarm_stats_ref (SimAlarmStats *alarm_stats)
{
  return (SimAlarmStats *) sim_mini_object_ref (SIM_MINI_OBJECT_CAST (alarm_stats));
}

void
sim_alarm_stats_unref (SimAlarmStats *alarm_stats)
{
  SimMiniObject *mini_object = SIM_MINI_OBJECT_CAST (alarm_stats);

  sim_mini_object_unref (mini_object);
}

void
sim_alarm_stats_update (SimAlarmStats *alarm_stats, SimEvent *event)
{
  alarm_stats->_priv->event_count++;

  // By definition if an event matches in reputation the minimun priority value is 1
  sim_alarm_stats_update_info (alarm_stats->_priv->info_src, event->src_ia, event->src_id, event->src_port, !!event->rep_prio_src, event->src_country);
  sim_alarm_stats_update_info (alarm_stats->_priv->info_dst, event->dst_ia, event->dst_id, event->dst_port, !!event->rep_prio_dst, event->dst_country);
}

gchar *
sim_alarm_stats_recalculate (GList *event_list)
{
  SimAlarmStats *alarm_stats;
  gchar *ret;

  alarm_stats = sim_alarm_stats_new ();

  for (; event_list; event_list = event_list->next)
  {
    SimEvent *event = event_list->data;

    // Check reputation for this event.
    if (ossim.reputation)
      sim_reputation_match_event (ossim.reputation, event);

    // GeoIP information
    event->src_country = sim_geoip_lookup (event->src_ia);
    event->dst_country = sim_geoip_lookup (event->dst_ia);

    sim_alarm_stats_update (alarm_stats, event);
  }

  ret = sim_alarm_stats_to_json (alarm_stats);

  sim_alarm_stats_unref (alarm_stats);

  return ret;
}

static void
sim_alarm_stats_update_info (SimAlarmStatsInfo *alarm_stats_info, SimInet *ip, SimUuid *host_uuid, gint port, gboolean reputation, const gchar *country)
{
  SimAlarmStatsInfoIp *alarms_stats_info_ip;

  g_return_if_fail (SIM_IS_INET (ip));

  alarms_stats_info_ip = g_hash_table_lookup (alarm_stats_info->info_ip, ip);
  if (alarms_stats_info_ip)
  {
    alarms_stats_info_ip->ip_count++;
    alarms_stats_info_ip->reputation |= reputation;
    if (!alarms_stats_info_ip->host_uuid)
      alarms_stats_info_ip->host_uuid = g_strdup (sim_uuid_get_db_string (host_uuid));
  }
  else
  {
    alarms_stats_info_ip = g_new (SimAlarmStatsInfoIp, 1);
    alarms_stats_info_ip->ip_count = 1;
    alarms_stats_info_ip->reputation = reputation;
    alarms_stats_info_ip->country = country ? country : "--";
    alarms_stats_info_ip->host_uuid = g_strdup (sim_uuid_get_db_string (host_uuid));
    g_hash_table_insert (alarm_stats_info->info_ip, g_object_ref (ip), alarms_stats_info_ip);
  }

  sim_alarm_stats_update_numbers (alarm_stats_info->ports, port);
  sim_alarm_stats_update_countries (alarm_stats_info->countries, country ? country : "--");
}

static void
sim_alarm_stats_update_numbers (GHashTable *numbers, gint number)
{
  gint *number_count;

  number_count = g_hash_table_lookup (numbers, GINT_TO_POINTER (number));
  if (number_count)
  {
    (*number_count)++;
  }
  else
  {
    number_count = g_new (gint, 1);
    *number_count = 1;
    g_hash_table_insert (numbers, GINT_TO_POINTER (number), number_count);
  }
}

static void
sim_alarm_stats_update_countries (GHashTable *countries, const gchar *country)
{
  gint *country_count;

  country_count = g_hash_table_lookup (countries, country);
  if (country_count)
  {
    (*country_count)++;
  }
  else
  {
    country_count = g_new (gint, 1);
    *country_count = 1;
    g_hash_table_insert (countries, (gpointer) country, country_count);
  }
}

gchar *
sim_alarm_stats_to_json (SimAlarmStats *alarm_stats)
{
  gchar *json_data;
  JsonGenerator *generator;
  JsonObject *object;
  JsonNode *node, *root;

  generator = json_generator_new ();
  object = json_object_new ();

  node = json_node_new (JSON_NODE_VALUE);
  json_node_set_int (node, alarm_stats->_priv->event_count);
  json_object_set_member (object, "events", node);

  sim_alarm_stats_info_to_json (alarm_stats->_priv->info_src, object, TRUE);
  sim_alarm_stats_info_to_json (alarm_stats->_priv->info_dst, object, FALSE);

  root = json_node_new (JSON_NODE_OBJECT);
  json_node_take_object (root, object);
  json_generator_set_root (generator, root);
  json_data = json_generator_to_data (generator, NULL);

  json_node_free (root);
  g_object_unref (generator);

  return json_data;
}

static void
sim_alarm_stats_info_to_json (SimAlarmStatsInfo *alarm_stats_info, JsonObject *parent_object, gboolean is_src)
{
  JsonObject *object, *object_ips, *object_ports, *object_countries;
  JsonNode *node;

  object = json_object_new ();

  object_ips = json_object_new ();
  g_hash_table_foreach (alarm_stats_info->info_ip, sim_alarm_stats_info_ip_to_json, object_ips);
  node = json_node_new (JSON_NODE_OBJECT);
  json_node_set_object (node, object_ips);
  json_object_set_member (object, "ip", node);
  json_object_unref (object_ips);

  object_ports = json_object_new ();
  g_hash_table_foreach (alarm_stats_info->ports, sim_alarm_stats_numbers_to_json, object_ports);
  node = json_node_new (JSON_NODE_OBJECT);
  json_node_set_object (node, object_ports);
  json_object_set_member (object, "port", node);
  json_object_unref (object_ports);

  node = json_node_new (JSON_NODE_VALUE);
  json_node_set_int (node, alarm_stats_info->reputation_count);
  json_object_set_member (object, "rep", node);

  object_countries = json_object_new ();
  g_hash_table_foreach (alarm_stats_info->countries, sim_alarm_stats_countries_to_json, object_countries);
  node = json_node_new (JSON_NODE_OBJECT);
  json_node_set_object (node, object_countries);
  json_object_set_member (object, "country", node);
  json_object_unref (object_countries);

  json_object_set_object_member (parent_object, is_src ? "src" : "dst", object);
}

static void
sim_alarm_stats_info_ip_to_json (gpointer key, gpointer value, gpointer user_data)
{
  SimInet *ip;
  SimAlarmStatsInfoIp *info_ip;
  JsonObject *object_ips, *object;
  JsonNode *node;
  gchar *ip_str;

  ip = key;
  info_ip = value;
  object_ips = user_data;

  object = json_object_new ();

  node = json_node_new (JSON_NODE_VALUE);
  json_node_set_int (node, info_ip->ip_count);
  json_object_set_member (object, "count", node);

  node = json_node_new (JSON_NODE_VALUE);
  json_node_set_int (node, info_ip->reputation);
  json_object_set_member (object, "rep", node);

  node = json_node_new (JSON_NODE_VALUE);
  json_node_set_string (node, info_ip->country);
  json_object_set_member (object, "country", node);

  node = json_node_new (JSON_NODE_VALUE);
  json_node_set_string (node, info_ip->host_uuid);
  json_object_set_member (object, "uuid", node);

  ip_str = sim_inet_get_canonical_name (ip);
  json_object_set_object_member (object_ips, ip_str, object);
  g_free (ip_str);
}

static void
sim_alarm_stats_numbers_to_json (gpointer key, gpointer value, gpointer user_data)
{
  gint number;
  gint number_count;
  JsonObject *object_numbers;
  gchar *number_str;
  JsonNode *node;

  number = GPOINTER_TO_INT (key);
  number_count = *(gint *) value;
  object_numbers = user_data;

  node = json_node_new (JSON_NODE_VALUE);
  json_node_set_int (node, number_count);
  number_str = g_strdup_printf ("%d", number);
  json_object_set_member (object_numbers, number_str, node);
  g_free (number_str);
}

static void
sim_alarm_stats_countries_to_json (gpointer key, gpointer value, gpointer user_data)
{
  const gchar *country;
  gint country_count;
  JsonObject *object_countries;
  JsonNode *node;

  country = key;
  country_count = *(gint *) value;
  object_countries = user_data;

  node = json_node_new (JSON_NODE_VALUE);
  json_node_set_int (node, country_count);
  json_object_set_member (object_countries, country, node);
}

#ifdef USE_UNITTESTS

/**************************************************************
 ****************          Unit tests          ****************
 **************************************************************/

int sim_alarm_stats_test1 (void);

int
sim_alarm_stats_test1 (void)
{
  SimAlarmStats *alarm_stats;
  SimEvent *event;
  int ret = 1;

  alarm_stats = sim_alarm_stats_new ();
  event = sim_event_new ();
  event->time = time(NULL);
  event->tzone = 2.0;
  event->src_ia = sim_inet_new_from_string ("0.0.0.0");
  event->src_id = NULL;
  event->dst_ia = sim_inet_new_from_string ("192.168.230.121");
  event->dst_id = sim_uuid_new_from_string ("1a78bf63-c428-3743-bd2b-1878f6d7c1a6");
  event->src_port = 50180;
  event->dst_port = 22;
  event->src_country = "--";
  event->dst_country = "ES";
  event->rep_prio_src = 2;
  event->rep_prio_dst = 2;
  event->rep_rel_src = 1;
  event->rep_rel_dst = 1;

  sim_alarm_stats_update (alarm_stats, event);

//  gchar *x = sim_alarm_stats_to_json (alarm_stats);

//  g_message (x);
//  g_free (x);

  sim_alarm_stats_unref (alarm_stats);

  sim_event_unref (event);

  return ret;
}

void
sim_alarm_stats_register_tests(SimUnittesting *engine) {
  sim_unittesting_append(engine, "sim_alarm_stats_test1 - 1 event", sim_alarm_stats_test1, 1);
}

#endif /* USE_UNITTESTS  */
