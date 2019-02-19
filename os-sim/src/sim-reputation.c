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

#include "config.h"

#include "sim-reputation.h"

#include <glib.h>
#include <glib-object.h>
#include <gio/gio.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <regex.h>

#include "sim-db-command.h"
#include "sim-log.h"
#include "os-sim.h"

extern SimMain  ossim;

#define REP_FILE_ENTRY    "^(\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}\\.\\d{1,3})#(\\d{1,2})#(\\d{1,2})#([^;]+)(;([^;]+))*#(.*)#(.*)#(.*)#([\\d]+)(;([\\d]+))*$"
#define REP_FILE_COMMENT  "^\\s*#.*|^\\s*$" 

/*
 * SimReputation contains a ipv4tree and ipv6tree of type radix
 */
struct _SimReputationPrivate {
  GFile        * file;
  GFileMonitor * monitor;

  SimRadix *ipv4tree;
  SimRadix *ipv6tree;

  GRWLock update_lock;

  GHashTable  *db_activities; // Activities stored in db ordered by name

  GRegex      *rep_file_entry;
  GRegex      *rep_file_comment;
};

static gpointer parent_class = NULL;

// Static declarations.
// Static declarations.
static gboolean
sim_reputation_load_file    (SimReputation *reputation);
static void
_sim_reputation_change_data (GFileMonitor * monitor,
                             GFile * file1,
                             GFile * file2,
                             GFileMonitorEvent event,
                             gpointer data);
SimReputationData *sim_reputation_search_best_inet  (SimReputation *reputation,
                                                     SimInet       *inet);
SimReputationData *sim_reputation_search_exact_inet (SimReputation *reputation,
                                                     SimInet       *inet);

/* GType Functions */

static void 
sim_reputation_impl_dispose (GObject  *gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void
sim_reputation_impl_finalize (GObject  *gobject)
{
  SimReputation *reputation = SIM_REPUTATION (gobject);

  if (reputation->_priv->ipv4tree)
    sim_radix_destroy(reputation->_priv->ipv4tree);

  if (reputation->_priv->ipv6tree)
    sim_radix_destroy(reputation->_priv->ipv6tree);

  if (reputation->_priv->db_activities)
    g_hash_table_destroy(reputation->_priv->db_activities);

  if (reputation->_priv->rep_file_entry)
    g_regex_unref(reputation->_priv->rep_file_entry);

  if (reputation->_priv->rep_file_comment)
    g_regex_unref(reputation->_priv->rep_file_comment);

  g_free (reputation->_priv);

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_reputation_class_init (SimReputationClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_peek_parent (class);

  object_class->dispose = sim_reputation_impl_dispose;
  object_class->finalize = sim_reputation_impl_finalize;
}


void sim_reputation_data_destroy(SimReputationData *data)
{
  if (data == NULL)
    return;

  if (data->str_activities != NULL)
    g_free(data->str_activities);
  if (data->activities != NULL)
    g_hash_table_destroy (data->activities);

  free (data);
}

/*
 * Helper functions for testing
 */
void sim_reputation_user_data_update(void *old, void *new_data) {
  if (!old || !new_data)
    return;
  SimRadixNode *old_node = old;
  SimReputationData *new_user_data = new_data;

  // Now we should do whatever kind of update we need
  // In this example we will replace the previous userdata, just like a dictionary..
  if (old_node->user_data)
    sim_reputation_data_destroy(old_node->user_data);
  old_node->user_data = new_user_data;
}

/*
 * Helper functions for testing
 */
void sim_reputation_user_data_print(void *ud) {
  if (!ud)
    return;

  GHashTableIter iter;
  gpointer key, value;

  gboolean          flag_first = TRUE;
  SimReputationData *srd = (SimReputationData *) ud;
  printf("rel:%u, prio:%u, acts:", srd->reliability, srd->priority);

  g_hash_table_iter_init (&iter, srd->activities);
  while (g_hash_table_iter_next (&iter, &key, &value)) 
  {
    if(flag_first)
    {
      printf("%d", GPOINTER_TO_INT(key));
      flag_first = FALSE;
    }
    else
      printf(",%d", GPOINTER_TO_INT(key));
  }
}

static void
sim_reputation_instance_init (SimReputation *reputation)
{
  GError *err = NULL;

  // We don't need to  set ptr's to NULL, since g_new0 will make that for us
  reputation->_priv = g_new0 (SimReputationPrivate, 1);
  reputation->_priv->ipv4tree = sim_radix_new((void *)sim_reputation_data_destroy, sim_reputation_user_data_print, sim_reputation_user_data_update, NULL);
  reputation->_priv->ipv6tree = sim_radix_new((void *)sim_reputation_data_destroy, sim_reputation_user_data_print, sim_reputation_user_data_update, NULL);
  reputation->_priv->rep_file_entry = g_regex_new(REP_FILE_ENTRY, 0, 0, &err);
  if(err)
    g_message("%s: ERROR compiling regular expression: %s", __func__, REP_FILE_ENTRY);
  reputation->_priv->rep_file_comment = g_regex_new(REP_FILE_COMMENT, 0, 0, &err);
  if(err)
    g_message("%s: ERROR compiling regular expression: %s", __func__, REP_FILE_COMMENT);
}

/* Public Methods */

GType
sim_reputation_get_type (void)
{
  static GType object_type = 0;
 
  if (!object_type)
  {
    static const GTypeInfo type_info = {
              sizeof (SimReputationClass),
              NULL,
              NULL,
              (GClassInitFunc) sim_reputation_class_init,
              NULL,
              NULL,                       /* class data */
              sizeof (SimReputation),
              0,                          /* number of pre-allocs */
              (GInstanceInitFunc) sim_reputation_instance_init,
              NULL                        /* priority table */
    };


    object_type = g_type_register_static (G_TYPE_OBJECT, "SimReputation", &type_info, 0);
  }

  return object_type;
}


struct in_addr *sim_reputation_get_IPV4_addr(const gchar *ascii_ip)
{
    struct in_addr *addr = NULL;

    if ( (addr = (struct in_addr *) malloc(sizeof(struct in_addr))) == NULL) {
        ossim_debug ( "Invalid IPv4 Address\n");
        return NULL;
    }    

    if (inet_pton(AF_INET, ascii_ip, addr) <= 0) { 
        free(addr);
        return NULL;
    }    

    return addr;
}

struct in6_addr *sim_reputation_get_IPV6_addr(const gchar *ascii_ip)
{
    struct in6_addr *addr = NULL;

    if ( (addr = (struct in6_addr *) malloc(sizeof(struct in6_addr))) == NULL) {
        ossim_debug ( "Invalid IPv6 Address\n");
        return NULL;
    }

    if (inet_pton(AF_INET6, ascii_ip, addr) <= 0) {
        free(addr);
        return NULL;
    }

    return addr;
}


gboolean
sim_reputation_to_IPV4(gchar *ip) {
    if (g_regex_match_simple("^\\s*0*([1-9]?\\d|1\\d\\d|2[0-4]\\d|25[0-5])\\.0*([1-9]?\\d|1\\d\\d|2[0-4]\\d|25[0-5])\\.0*([1-9]?\\d|1\\d\\d|2[0-4]\\d|25[0-5])\\.0*([1-9]?\\d|1\\d\\d|2[0-4]\\d|25[0-5])\\s*(/([0-9]|3[0-2]|[0-2][0-9]))?\\s*$", ip, 0, 0))
        return TRUE;
    
    return FALSE;
}

gboolean
sim_reputation_to_IPV6(gchar *ip) {
    if (g_regex_match_simple("^\\s*(([\\da-f]{0,4}:)+([\\da-f]{1,4})?)\\s*",
                                ip, G_REGEX_CASELESS, 0))
        return TRUE;
    
    return FALSE;
}

SimReputationData *
sim_reputation_data_create (gchar   *reliability,
                            gchar   *priority,
                            gchar   *activities,
                            gchar   *act_ids,
                            GHashTable *db_acts)
{

  gchar         **split_acts, **split_acts_ids, *query;
  gint          i, aux_int;
  gpointer      aux_activity = NULL;

  SimReputationData* rd = (SimReputationData *) malloc(sizeof(SimReputationData));
  if (rd == NULL) {
    ossim_debug ( "Error creating reputation reputation data entry\n");
    return NULL;
  }

  memset(rd, 0, sizeof(SimReputationData));

  rd->reliability = g_ascii_strtoull(reliability, NULL, 10);
  rd->priority = g_ascii_strtoull(priority, NULL, 10);
  rd->str_activities = g_strdup(activities);
  rd->activities = g_hash_table_new_full(g_direct_hash, g_direct_equal, NULL, NULL);

  split_acts = g_strsplit (activities, SIM_DELIMITER_ELEMENT_LIST, 0);
  split_acts_ids = g_strsplit (act_ids, SIM_DELIMITER_ELEMENT_LIST, 0);

  for (i = 0; split_acts[i] != NULL && split_acts_ids[i] != NULL; i++)
  {
    if (db_acts)
      aux_activity = g_hash_table_lookup(db_acts, split_acts[i]);

    if(aux_activity)
      g_hash_table_insert(rd->activities, aux_activity, GINT_TO_POINTER(GENERIC_VALUE));
    else
    {
      // Insert new row in database and a new entry in the hash tables
      query = g_strdup_printf("INSERT INTO reputation_activities VALUES (%s, '%s')", split_acts_ids[i], split_acts[i]);
      sim_database_execute_no_query (ossim.dbossim, query);
      g_free(query);
      aux_int = atoi(split_acts_ids[i]);
      g_hash_table_insert(rd->activities, GINT_TO_POINTER(aux_int), GINT_TO_POINTER(GENERIC_VALUE));

      if (db_acts)
        g_hash_table_insert(db_acts, g_strdup(split_acts[i]), GINT_TO_POINTER(aux_int));
    }
  }

  g_strfreev(split_acts);
  g_strfreev(split_acts_ids);

  return rd;
}

gboolean
sim_reputation_add_entry (SimReputation *reputation,
                          gchar         *ip,
                          gchar         *reliability,
                          gchar         *priority,
                          gchar         *activities,
                          gchar         *act_ids)
{

  SimReputationData *srd;

  SimInet *inet = sim_inet_new_from_string (ip);
  if (!inet)
    return FALSE;

  srd = sim_reputation_data_create (reliability, priority, activities, act_ids, reputation->_priv->db_activities);
  if (srd == NULL)
  {
    g_object_unref (inet);
    return FALSE;
  }

  if (sim_inet_is_ipv4 (inet))
  {
    SimRadixKey *key = sim_inet_get_radix_key (inet);
    if (!sim_radix_insert_keyval (reputation->_priv->ipv4tree, key, srd))
    {
      ossim_debug ("Failed while adding reputation entry!\n");
      sim_reputation_data_destroy (srd);
      g_object_unref (inet);
      return FALSE;
    }
  }
  else if (sim_inet_is_ipv6 (inet))
  {
    SimRadixKey *key = sim_inet_get_radix_key (inet);
    if (!sim_radix_insert_keyval (reputation->_priv->ipv6tree, key, srd))
    {
      ossim_debug ("Failed while adding reputation entry!");
      sim_reputation_data_destroy (srd);
      g_object_unref (inet);
      return FALSE;
    }
  }
  else
  {
    ossim_debug ( "Invalid Entry: %s#%s#%s#%s\n", ip, reliability, priority, activities);
    sim_reputation_data_destroy (srd);
    g_object_unref (inet);
    return FALSE;
  }

  g_object_unref (inet);

  return TRUE;
}

/**
 * _sim_reputation_change_data:
 * @monitor: a GFileMonitor that watches for file changes.
 * @file1: a GFile.
 * @file2: another GFile.
 * @event: the event that triggers this callback.
 * @data: user defined data.
 *
 * Callback triggered when the reputation file is altered in
 * some way.
 */
static void
_sim_reputation_change_data (GFileMonitor * monitor,
                             GFile * file1,
                             GFile * file2,
                             GFileMonitorEvent event,
                             gpointer data)
{
  // unused parameter
  (void) monitor;
  (void) file1;
  (void) file2;

  // GLib calls *twice* this function, first to notify a file change, then
  // to "notify" that the change is done. Odd...
  if (event != G_FILE_MONITOR_EVENT_CHANGES_DONE_HINT)
    return;

  g_return_if_fail (SIM_IS_REPUTATION (data));

  SimReputation * reputation = (SimReputation *) data;
  SimRadix      * ipv4tree, * ipv6tree;

  g_rw_lock_writer_lock (&reputation->_priv->update_lock);

  ipv4tree = reputation->_priv->ipv4tree;
  reputation->_priv->ipv4tree = sim_radix_new((void *)sim_reputation_data_destroy, sim_reputation_user_data_print, sim_reputation_user_data_update, NULL);
  ipv6tree = reputation->_priv->ipv6tree;
  reputation->_priv->ipv6tree = sim_radix_new((void *)sim_reputation_data_destroy, sim_reputation_user_data_print, sim_reputation_user_data_update, NULL);

  if (!sim_reputation_load_file (reputation))
  {
    g_warning ("Couldn't update reputation file, falling back to previous data...");
    sim_radix_destroy (reputation->_priv->ipv4tree);
    sim_radix_destroy (reputation->_priv->ipv6tree);
    reputation->_priv->ipv4tree = ipv4tree;
    reputation->_priv->ipv6tree = ipv6tree;
  }
  else
  {
    g_message ("Reputation data updated");
    sim_radix_destroy (ipv4tree);
    sim_radix_destroy (ipv6tree);
  }

  g_rw_lock_writer_unlock (&reputation->_priv->update_lock);

  return;
}

/**
 * sim_reputation_load_file:
 * @reputation: a SimReputation object.
 *
 */
static gboolean
sim_reputation_load_file (SimReputation *reputation)
{
  gchar *file_path;
  gchar ** tokens;

  file_path = g_file_get_path (reputation->_priv->file);
  if (!file_path)
  {
    g_message ("Error opening reputation data file");
    return FALSE;
  }

  GIOChannel *rep_file = g_io_channel_new_file (file_path, "r", NULL);
  if (rep_file == NULL)
  {
    ossim_debug ("%s: Problems with file \"%s\"\n", __func__, file_path);
    g_free (file_path);
    return FALSE;
  }
  g_free (file_path);

  // Process the file

  gchar *buf = NULL;
  gsize len = 0;
  gsize pos = 0;
  GError *err = NULL;
  GIOStatus status = G_IO_STATUS_NORMAL;
  GMatchInfo *match_info = NULL;

  gchar *delim = "#";
  gint  line = 0;

  while (status != G_IO_STATUS_ERROR && status != G_IO_STATUS_EOF) {
    tokens = NULL;
    status = g_io_channel_read_line(rep_file, &buf, &len, &pos, &err);
    if (buf != NULL) {
      line++;

      // Skip comment
      if(g_regex_match (reputation->_priv->rep_file_comment, buf, 0, &match_info))
      {
        ossim_debug ("Skipped comment at line %d", line);
        g_free(buf);
        continue;
      }
      g_match_info_free(match_info);

      if(g_regex_match (reputation->_priv->rep_file_entry, buf, 0, &match_info))
      {
        // By default, we wont need more than 100 tokes per line (ip info)
        tokens = g_strsplit((const gchar *)buf, (const gchar *)delim, 100);
        if(!sim_reputation_add_entry(reputation, tokens[0], tokens[1], tokens[2], tokens[3], tokens[7]))
          g_message("ERROR: Adding entry of reputation data file. There was an error loading information at line %d", line);
      }
      else
        g_message("ERROR: Reputation data file bad format. There was an error loading information at line %d", line);
      g_match_info_free(match_info);
        
      if (tokens != NULL)
        g_strfreev(tokens);
      g_free(buf);
    }
  }
  g_io_channel_unref(rep_file);
	return TRUE;
}

/*
 *
 * Create a new reputation object, reading a file to fill the ipv4tree
 *
 */
SimReputation*
sim_reputation_new (gchar *filedata)
{
  SimReputation *reputation;

  reputation = SIM_REPUTATION (g_object_new (SIM_TYPE_REPUTATION, NULL));

  if (filedata)
  {
    reputation->_priv->db_activities = g_hash_table_new_full(g_str_hash, g_str_equal, g_free, NULL);
    sim_db_load_reputation_activities (ossim.dbossim, reputation);

    reputation->_priv->file = g_file_new_for_path (filedata);

    g_rw_lock_init (&reputation->_priv->update_lock);

    if (!sim_reputation_load_file (reputation))
    {
        g_object_unref(reputation);
        return NULL;
    }

    // Add monitoring of file events to allow changing reputation data
    // without stopping the server.
    reputation->_priv->monitor = g_file_monitor_file (reputation->_priv->file, 0, NULL, NULL);
    g_signal_connect (reputation->_priv->monitor, "changed", G_CALLBACK (_sim_reputation_change_data), reputation);
  }

  return (reputation);
}

/*
 *
 *
 */
SimRadix*
sim_reputation_get_ipv6tree (SimReputation  *reputation)
{
  g_return_val_if_fail (reputation, 0);
  return reputation->_priv->ipv6tree;
}

/*
 *
 *
 */
void
sim_reputation_set_ipv6tree (SimReputation *reputation,
                        SimRadix *ipv6tree)
{
  g_return_if_fail (reputation);
  reputation->_priv->ipv6tree = ipv6tree;
}


/*
 *
 *
 */
SimRadix*
sim_reputation_get_ipv4tree (SimReputation  *reputation)
{
  g_return_val_if_fail (reputation, 0);
  return reputation->_priv->ipv4tree;
}

/*
 *
 *
 */
GHashTable*
sim_reputation_get_db_activities (SimReputation  *reputation)
{
  g_return_val_if_fail (reputation, NULL);
  return reputation->_priv->db_activities;
}


/*
 *
 *
 */
void
sim_reputation_set_ipv4tree (SimReputation *reputation,
                        SimRadix *ipv4tree)
{
  g_return_if_fail (reputation);
  reputation->_priv->ipv4tree = ipv4tree;
}

void
sim_reputation_print(SimReputation *reputation) {
  printf("\nIPV4 tree start\n");
  sim_radix_print(reputation->_priv->ipv4tree, 1);
  printf("IPV4 end\n");
  printf("\nIPV6 tree start\n");
  sim_radix_print(reputation->_priv->ipv6tree, 1);
  printf("IPV6 end\n");
}

SimReputationData*
sim_reputation_search_best_key_IPV6(SimReputation *reputation, SimRadixKey *key) {
  return (SimReputationData *) sim_radix_search_best_key(reputation->_priv->ipv6tree, key);
}

SimReputationData*
sim_reputation_search_exact_key_IPV6(SimReputation *reputation, SimRadixKey *key) {
  return (SimReputationData *) sim_radix_search_exact_key(reputation->_priv->ipv6tree, key);
}

SimReputationData*
sim_reputation_search_best_key_IPV4(SimReputation *reputation, SimRadixKey *key) {
  return (SimReputationData *) sim_radix_search_best_key(reputation->_priv->ipv4tree, key);
}

SimReputationData*
sim_reputation_search_exact_key_IPV4(SimReputation *reputation, SimRadixKey *key) {
  return (SimReputationData *) sim_radix_search_exact_key(reputation->_priv->ipv4tree, key);
}

/**
 * sim_reputation_search_best_inet:
 * @reputation: #SimReputation object
 * @inet: #SimInet object
 *
 * Search best key for @inet in the proper tree
 *
 * Returns: data associated to @inet or %NULL
 */
SimReputationData *
sim_reputation_search_best_inet (SimReputation *reputation,
                                 SimInet       *inet)
{
  SimRadix          *tree;
  SimRadixKey       *key;
  SimReputationData *data = NULL;

  if (sim_inet_is_ipv4 (inet))
    tree = reputation->_priv->ipv4tree;
  else if (sim_inet_is_ipv6 (inet))
    tree = reputation->_priv->ipv6tree;
  else
    return NULL;

  key = sim_inet_get_radix_key (inet);
  data = (SimReputationData *)sim_radix_search_best_key (tree, key);

  return data;
}

/**
 * sim_reputation_search_exact_inet:
 * @reputation: #SimReputation object
 * @inet: #SimInet object
 *
 * Search exact key for @inet in the proper tree
 *
 * Returns: data associated to @inet or %NULL
 */
SimReputationData *
sim_reputation_search_exact_inet (SimReputation *reputation,
                                  SimInet       *inet)
{
  SimRadix          *tree;
  SimRadixKey       *key;
  SimReputationData *data;

  if (sim_inet_is_ipv4 (inet))
    tree = reputation->_priv->ipv4tree;
  else if (sim_inet_is_ipv6 (inet))
    tree = reputation->_priv->ipv6tree;
  else
    return NULL;

  key = sim_inet_get_radix_key (inet);
  data = (SimReputationData *)sim_radix_search_exact_key (tree, key);

  return data;
}


/**
 * sim_reputation_match_event:
 *
 * Search the event src/dst at the reputation trees. If we have a match, then
 * update the event reliability and set the needed fields.
 */
void
sim_reputation_match_event (SimReputation * reputation, SimEvent *event)
{
  g_return_if_fail (SIM_IS_EVENT (event));

  // SKip events generated by reputation and correlation
  // TODO: make reputation as a new role of the server, specifying which plugin id's it should try to match/inspect
  if (event->plugin_id == SIM_REPUTATION_PLUGIN_ID || event->plugin_id == SIM_PLUGIN_ID_DIRECTIVE)
    return;

  g_rw_lock_reader_lock (&reputation->_priv->update_lock);
  SimReputationData *data_src = sim_reputation_search_best_inet (reputation, event->src_ia);
  SimReputationData *data_dst = sim_reputation_search_best_inet (reputation, event->dst_ia);

  if (data_src)
    g_hash_table_ref (data_src->activities);
  if (data_dst)
    g_hash_table_ref (data_dst->activities);
  g_rw_lock_reader_unlock (&reputation->_priv->update_lock);

  // Update event reliability.
  if (data_dst && data_src)
  {
    event->reliability = data_dst->reliability > data_src->reliability ? data_dst->reliability : data_src->reliability;
    event->correlation |= EVENT_MATCH_REPUTATION;
  }
  else if (data_dst)
  {
    event->reliability = data_dst->reliability;
    event->correlation |= EVENT_MATCH_REPUTATION;
  }
  else if (data_src)
  {
    event->reliability = data_src->reliability;
    event->correlation |= EVENT_MATCH_REPUTATION;
  }

  if (data_src)
  {
    event->rep_prio_src = data_src->priority;
    event->rep_rel_src = data_src->reliability;
    event->rep_act_src = g_hash_table_ref (data_src->activities);
    event->str_rep_act_src = g_strdup(data_src->str_activities);
  }

  if (data_dst)
  {
    event->rep_prio_dst = data_dst->priority;
    event->rep_rel_dst = data_dst->reliability;
    event->rep_act_dst = g_hash_table_ref (data_dst->activities);
    event->str_rep_act_dst = g_strdup(data_dst->str_activities);
  }

  if (data_src)
    g_hash_table_unref (data_src->activities);
  if (data_dst)
    g_hash_table_unref (data_dst->activities);
}

#ifdef USE_UNITTESTS
/*************************************************************
***************            Unit tests          ***************
**************************************************************/

int sim_reputation_test1 (void);
int sim_reputation_test2 (void);

// Test load and search of ipv4 reputation data
int sim_reputation_test1() {
  SimReputation *reputation = sim_reputation_new(NULL);
  // sim_reputation_load_file(reputation, "data.csv");
  // Instead of loading a file, we are going to insert entries one by one

  if (sim_reputation_add_entry(reputation, "46.175.8.41", "2", "2", "RBN", "9") == FALSE)
    return 0;
  if (sim_reputation_add_entry(reputation, "91.200.240.30", "2", "2", "Malicious Host", "3") == FALSE)
    return 0;
  if (sim_reputation_add_entry(reputation, "91.200.240.31", "2", "2", "Malicious Host", "3") == FALSE)
    return 0;
  if (sim_reputation_add_entry(reputation, "46.108.225.46", "2", "2", "Malicious Host", "3") == FALSE)
    return 0;
  if (sim_reputation_add_entry(reputation, "91.223.82.128", "2", "2", "Malicious Host", "3") == FALSE)
    return 0;
  if (sim_reputation_add_entry(reputation, "10.10.10.10", "2", "2", "Malicious Host;Malware distribution", "3;5") == FALSE)
    return 0;
  if (sim_reputation_add_entry(reputation, "64.237.99.108", "2", "2", "Malicious Host", "3") == FALSE)
    return 0;
  if (sim_reputation_add_entry(reputation, "69.89.31.164", "2", "2", "Malicious Host", "3") == FALSE)
    return 0;
  if (sim_reputation_add_entry(reputation, "174.133.38.146", "2", "2", "Malicious Host", "3") == FALSE)
    return 0;
  if (sim_reputation_add_entry(reputation, "209.173.141.162", "2", "2", "Malicious Host", "3") == FALSE)
    return 0;
  if (sim_reputation_add_entry(reputation, "203.104.22.22", "2", "2", "Malicious Host", "3") == FALSE)
    return 0;
  if (sim_reputation_add_entry(reputation, "85.214.32.165", "2", "2", "Malicious Host", "3") == FALSE)
    return 0;
  if (sim_reputation_add_entry(reputation, "74.63.237.175", "2", "2", "Malware distribution", "5") == FALSE)
    return 0;
  if (sim_reputation_add_entry(reputation, "66.228.40.45", "2", "2", "Malware distribution", "5") == FALSE)
    return 0;
  if (sim_reputation_add_entry(reputation, "67.228.244.195", "2", "2", "Malware distribution", "5") == FALSE)
    return 0;
  if (sim_reputation_add_entry(reputation, "208.87.242.108", "2", "2", "Malware distribution", "5") == FALSE)
    return 0;
  if (sim_reputation_add_entry(reputation, "69.89.18.29", "2", "2", "Malware distribution", "5") == FALSE)
    return 0;
  if (sim_reputation_add_entry(reputation, "67.220.197.2", "2", "2", "Malware distribution", "5") == FALSE)
    return 0;
  if (sim_reputation_add_entry(reputation, "72.29.78.93", "2", "2", "Malware distribution", "5") == FALSE)
    return 0;
  if (sim_reputation_add_entry(reputation, "70.32.43.130", "2", "2", "Malware distribution", "5") == FALSE)
    return 0;

  struct in_addr *addr = NULL;
  SimReputationData *data = NULL;
  SimInet *inet = NULL;

  if ( (addr = (struct in_addr *) malloc(sizeof(struct in_addr))) == NULL) {
      printf("Invalid IPv4 Address\n");
      return 0;
  }

  SimRadixKey *key = sim_radix_key_new();
  if (key == NULL) {
    free(addr);
    printf("Couldn't create key\n");
    return 0;
  }
  key->keylen = 32;

  if (inet_pton(AF_INET, "46.175.8.41", addr) <= 0) {
    free(addr);
    return 0;
  }
  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV4(reputation, key);
  inet = sim_inet_new_from_string ("46.175.8.41");
  if (data == NULL) {
    printf("Failed getting rep data of 46.175.8.41");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET, "91.200.240.30", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV4(reputation, key);
  inet = sim_inet_new_from_string ("91.200.240.30");
  if (data == NULL) {
    printf("Failed getting rep data of 91.200.240.30");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET, "91.200.240.31", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV4(reputation, key);
  inet = sim_inet_new_from_string ("91.200.240.31");
  if (data == NULL) {
    printf("Failed getting rep data of 91.200.240.31");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET, "46.108.225.46", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV4(reputation, key);
  inet = sim_inet_new_from_string ("46.108.225.46");
  if (data == NULL) {
    printf("Failed getting rep data of 46.108.225.46");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET, "91.223.82.128", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV4(reputation, key);
  inet = sim_inet_new_from_string ("91.223.82.128");
  if (data == NULL) {
    printf("Failed getting rep data of 91.223.82.128");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET, "10.10.10.10", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV4(reputation, key);
  inet = sim_inet_new_from_string ("10.10.10.10");
  if (data == NULL) {
    printf("Failed getting rep data of 10.10.10.10");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET, "64.237.99.108", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV4(reputation, key);
  inet = sim_inet_new_from_string ("64.237.99.108");
  if (data == NULL) {
    printf("Failed getting rep data of 64.237.99.108");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET, "69.89.31.164", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV4(reputation, key);
  inet = sim_inet_new_from_string ("69.89.31.164");
  if (data == NULL) {
    printf("Failed getting rep data of 69.89.31.164");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET, "174.133.38.146", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV4(reputation, key);
  inet = sim_inet_new_from_string ("174.133.38.146");
  if (data == NULL) {
    printf("Failed getting rep data of 174.133.38.146");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET, "209.173.141.162", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV4(reputation, key);
  inet = sim_inet_new_from_string ("209.173.141.162");
  if (data == NULL) {
    printf("Failed getting rep data of 209.173.141.162");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET, "203.104.22.22", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV4(reputation, key);
  inet = sim_inet_new_from_string ("203.104.22.22");
  if (data == NULL) {
    printf("Failed getting rep data of 203.104.22.22");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET, "85.214.32.165", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV4(reputation, key);
  inet = sim_inet_new_from_string ("85.214.32.165");
  if (data == NULL) {
    printf("Failed getting rep data of 85.214.32.165");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET, "74.63.237.175", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV4(reputation, key);
  inet = sim_inet_new_from_string ("74.63.237.175");
  if (data == NULL) {
    printf("Failed getting rep data of 74.63.237.175");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET, "66.228.40.45", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV4(reputation, key);
  inet = sim_inet_new_from_string ("66.228.40.45");
  if (data == NULL) {
    printf("Failed getting rep data of 66.228.40.45");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET, "67.228.244.195", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV4(reputation, key);
  inet = sim_inet_new_from_string ("67.228.244.195");
  if (data == NULL) {
    printf("Failed getting rep data of 67.228.244.195");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET, "208.87.242.108", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV4(reputation, key);
  inet = sim_inet_new_from_string ("208.87.242.108");
  if (data == NULL) {
    printf("Failed getting rep data of 208.87.242.108");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET, "69.89.18.29", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV4(reputation, key);
  inet = sim_inet_new_from_string ("69.89.18.29");
  if (data == NULL) {
    printf("Failed getting rep data of 69.89.18.29");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET, "67.220.197.2", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV4(reputation, key);
  inet = sim_inet_new_from_string ("67.220.197.2");
  if (data == NULL) {

    printf("Failed getting rep data of 67.220.197.2");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET, "72.29.78.93", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV4(reputation, key);
  inet = sim_inet_new_from_string ("72.29.78.93");
  if (data == NULL) {
    printf("Failed getting rep data of 72.29.78.93");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET, "70.32.43.130", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV4(reputation, key);
  inet = sim_inet_new_from_string ("70.32.43.130");
  if (data == NULL) {
    printf("Failed getting rep data of 70.32.43.130");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET, "1.2.3.4", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV4(reputation, key);
  if (data != NULL) {
    printf("Failed 1.2.3.4 should not return any data\n");
    return 0;
  }

  sim_reputation_print(reputation);
  g_object_unref(reputation);
  return 1;
}

// Test load and search of ipv6 reputation data
int sim_reputation_test2() {
  SimReputation *reputation = sim_reputation_new(NULL);
  // Instead of loading a file, we are going to insert entries one by one

  if (sim_reputation_add_entry(reputation, "04d7:04a0:4aa0:51cd:4eb9:4efc:78f9:652d", "2", "2", "Malicious Host;RBN;Spamming", "3;9;12") == FALSE)
    return 0;

  if (sim_reputation_add_entry(reputation, "0bde:13b1:3233:122f:24e8:56f1:188f:479e", "2", "2", "Malicious Host;RBN", "3;9") == FALSE)
    return 0;

  if (sim_reputation_add_entry(reputation, "1aa7:442e:0dce:1bbc:3869:407c:0c5d:2c02", "2", "2", "Malware distribution", "5") == FALSE)
    return 0;

  if (sim_reputation_add_entry(reputation, "1f02:46a4:673f:3c94:749e:6907:1cba:5386", "2", "2", "RBN", "9") == FALSE)
    return 0;

  if (sim_reputation_add_entry(reputation, "4549:1539:27c1:4bd1:603b:7559:7c24:1c13", "2", "2", "Malware distribution", "5") == FALSE)
    return 0;

  if (sim_reputation_add_entry(reputation, "3de5:0b32:197a:4d0a:3abc:6113:60ca:5bce", "2", "2", "Malicious Host", "3") == FALSE)
    return 0;

  if (sim_reputation_add_entry(reputation, "32c8:6c6e:18fc:03d2:49eb:7e33:4e02:2c99", "2", "2", "Malware distribution", "5") == FALSE)
    return 0;

  if (sim_reputation_add_entry(reputation, "6cf2:5cea:76c5:7737:575d:6493:0dd2:278f", "2", "2", "RBN", "9") == FALSE)
    return 0;

  if (sim_reputation_add_entry(reputation, "29ee:483d:08c1:6546:5df4:5168:5a69:1089", "2", "2", "Malware distribution", "5") == FALSE)
    return 0;

  if (sim_reputation_add_entry(reputation, "2155:391c:6bc5:2684:675c:76c6:37a5:719d", "2", "2", "APT", "1") == FALSE)
    return 0;

  if (sim_reputation_add_entry(reputation, "3a48:20d5:7514:0174:1990:3b18:7c8d:269c", "2", "2", "Spamming", "12") == FALSE)
    return 0;

  if (sim_reputation_add_entry(reputation, "70f7:0ed6:53dc:6e16:7454:68ab:0cd5:5891", "2", "2", "Malware distribution", "5") == FALSE)
    return 0;

  if (sim_reputation_add_entry(reputation, "6c1f:5825:62b8:4964:3ebb:4d22:4260:6947", "2", "2", "Malicious Host", "3") == FALSE)
    return 0;

  if (sim_reputation_add_entry(reputation, "4285:2ee7:292e:78d5:24a1:4901:06b7:4ec2", "2", "2", "Malware distribution", "5") == FALSE)
    return 0;

  if (sim_reputation_add_entry(reputation, "707f:29dc:0d30:7ddc:042f:4f24:1c95:5ec3", "2", "2", "RBN", "9") == FALSE)
    return 0;

  if (sim_reputation_add_entry(reputation, "7d6a:3bdf:349c:7967:6b5c:5447:195d:0a46", "2", "2", "Malware distribution", "5") == FALSE)
    return 0;

  if (sim_reputation_add_entry(reputation, "5132:1b68:16be:1f63:4568:2e7e:0ea0:0905", "2", "2", "Spamming", "12") == FALSE)
    return 0;

  if (sim_reputation_add_entry(reputation, "39d0:1a0c:3dcb:1a35:3861:64bc:6399:74f5", "2", "2", "Malware distribution", "5") == FALSE)
    return 0;

  if (sim_reputation_add_entry(reputation, "20c5:6bfa:3865:5e40:32a0:6e4f:5eb3:55c5", "2", "2", "RBN", "9") == FALSE)
    return 0;

  if (sim_reputation_add_entry(reputation, "70a2:5f7e:3b1c:6d64:284b:6260:5f00:1c65", "2", "2", "Malicious Host", "3") == FALSE)
    return 0;


  struct in6_addr *addr = NULL;
  SimReputationData *data = NULL;
  SimInet *inet = NULL;

  if ( (addr = (struct in6_addr *) malloc(sizeof(struct in6_addr))) == NULL) {
      printf("Invalid IPv4 Address\n");
      return 0;
  }

  SimRadixKey *key = sim_radix_key_new();
  if (key == NULL) {
    free(addr);
    printf("Couldn't create key\n");
    return 0;
  }
  key->keylen = 128;

  if (inet_pton(AF_INET6, "04d7:04a0:4aa0:51cd:4eb9:4efc:78f9:652d", addr) <= 0) {
    free(addr);
    return 0;
  }
  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV6(reputation, key);
  inet = sim_inet_new_from_string ("04d7:04a0:4aa0:51cd:4eb9:4efc:78f9:652d");
  if (data == NULL) {
    printf("Failed getting rep data of 04d7:04a0:4aa0:51cd:4eb9:4efc:78f9:652d");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET6, "0bde:13b1:3233:122f:24e8:56f1:188f:479e", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV6(reputation, key);
  inet = sim_inet_new_from_string ("0bde:13b1:3233:122f:24e8:56f1:188f:479e");
  if (data == NULL) {
    printf("Failed getting rep data of 0bde:13b1:3233:122f:24e8:56f1:188f:479e");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET6, "1aa7:442e:0dce:1bbc:3869:407c:0c5d:2c02", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV6(reputation, key);
  inet = sim_inet_new_from_string ("1aa7:442e:0dce:1bbc:3869:407c:0c5d:2c02");
  if (data == NULL) {
    printf("Failed getting rep data of 1aa7:442e:0dce:1bbc:3869:407c:0c5d:2c02");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET6, "1f02:46a4:673f:3c94:749e:6907:1cba:5386", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV6(reputation, key);
  inet = sim_inet_new_from_string ("1f02:46a4:673f:3c94:749e:6907:1cba:5386");
  if (data == NULL) {
    printf("Failed getting rep data of 1f02:46a4:673f:3c94:749e:6907:1cba:5386");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET6, "4549:1539:27c1:4bd1:603b:7559:7c24:1c13", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV6(reputation, key);
  inet = sim_inet_new_from_string ("4549:1539:27c1:4bd1:603b:7559:7c24:1c13");
  if (data == NULL) {
    printf("Failed getting rep data of 4549:1539:27c1:4bd1:603b:7559:7c24:1c13");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET6, "3de5:0b32:197a:4d0a:3abc:6113:60ca:5bce", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV6(reputation, key);
  inet = sim_inet_new_from_string ("3de5:0b32:197a:4d0a:3abc:6113:60ca:5bce");
  if (data == NULL) {
    printf("Failed getting rep data of 3de5:0b32:197a:4d0a:3abc:6113:60ca:5bce");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET6, "32c8:6c6e:18fc:03d2:49eb:7e33:4e02:2c99", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV6(reputation, key);
  inet = sim_inet_new_from_string ("32c8:6c6e:18fc:03d2:49eb:7e33:4e02:2c99");
  if (data == NULL) {
    printf("Failed getting rep data of 32c8:6c6e:18fc:03d2:49eb:7e33:4e02:2c99");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET6, "6cf2:5cea:76c5:7737:575d:6493:0dd2:278f", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV6(reputation, key);
  inet = sim_inet_new_from_string ("6cf2:5cea:76c5:7737:575d:6493:0dd2:278f");
  if (data == NULL) {
    printf("Failed getting rep data of 6cf2:5cea:76c5:7737:575d:6493:0dd2:278f");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET6, "29ee:483d:08c1:6546:5df4:5168:5a69:1089", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);
  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV6(reputation, key);
  inet = sim_inet_new_from_string ("29ee:483d:08c1:6546:5df4:5168:5a69:1089");
  if (data == NULL) {
    printf("Failed getting rep data of 29ee:483d:08c1:6546:5df4:5168:5a69:1089");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET6, "2155:391c:6bc5:2684:675c:76c6:37a5:719d", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV6(reputation, key);
  inet = sim_inet_new_from_string ("2155:391c:6bc5:2684:675c:76c6:37a5:719d");
  if (data == NULL) {
    printf("Failed getting rep data of 2155:391c:6bc5:2684:675c:76c6:37a5:719d");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET6, "3a48:20d5:7514:0174:1990:3b18:7c8d:269c", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV6(reputation, key);
  inet = sim_inet_new_from_string ("3a48:20d5:7514:0174:1990:3b18:7c8d:269c");
  if (data == NULL) {
    printf("Failed getting rep data of 3a48:20d5:7514:0174:1990:3b18:7c8d:269c");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET6, "70f7:0ed6:53dc:6e16:7454:68ab:0cd5:5891", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV6(reputation, key);
  inet = sim_inet_new_from_string ("70f7:0ed6:53dc:6e16:7454:68ab:0cd5:5891");
  if (data == NULL) {
    printf("Failed getting rep data of 70f7:0ed6:53dc:6e16:7454:68ab:0cd5:5891");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET6, "6c1f:5825:62b8:4964:3ebb:4d22:4260:6947", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV6(reputation, key);
  inet = sim_inet_new_from_string ("6c1f:5825:62b8:4964:3ebb:4d22:4260:6947");
  if (data == NULL) {
    printf("Failed getting rep data of 6c1f:5825:62b8:4964:3ebb:4d22:4260:6947");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET6, "4285:2ee7:292e:78d5:24a1:4901:06b7:4ec2", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV6(reputation, key);
  inet = sim_inet_new_from_string ("4285:2ee7:292e:78d5:24a1:4901:06b7:4ec2");
  if (data == NULL) {
    printf("Failed getting rep data of 4285:2ee7:292e:78d5:24a1:4901:06b7:4ec2");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET6, "707f:29dc:0d30:7ddc:042f:4f24:1c95:5ec3", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV6(reputation, key);
  inet = sim_inet_new_from_string ("707f:29dc:0d30:7ddc:042f:4f24:1c95:5ec3");
  if (data == NULL) {
    printf("Failed getting rep data of 707f:29dc:0d30:7ddc:042f:4f24:1c95:5ec3");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET6, "7d6a:3bdf:349c:7967:6b5c:5447:195d:0a46", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV6(reputation, key);
  inet = sim_inet_new_from_string ("7d6a:3bdf:349c:7967:6b5c:5447:195d:0a46");
  if (data == NULL) {
    printf("Failed getting rep data of 7d6a:3bdf:349c:7967:6b5c:5447:195d:0a46");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET6, "5132:1b68:16be:1f63:4568:2e7e:0ea0:0905", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV6(reputation, key);
  inet = sim_inet_new_from_string ("5132:1b68:16be:1f63:4568:2e7e:0ea0:0905");
  if (data == NULL) {
    printf("Failed getting rep data of 5132:1b68:16be:1f63:4568:2e7e:0ea0:0905");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET6, "39d0:1a0c:3dcb:1a35:3861:64bc:6399:74f5", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV6(reputation, key);
  inet = sim_inet_new_from_string ("39d0:1a0c:3dcb:1a35:3861:64bc:6399:74f5");
  if (data == NULL) {
    printf("Failed getting rep data of 39d0:1a0c:3dcb:1a35:3861:64bc:6399:74f5");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET6, "20c5:6bfa:3865:5e40:32a0:6e4f:5eb3:55c5", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV6(reputation, key);
  inet = sim_inet_new_from_string ("20c5:6bfa:3865:5e40:32a0:6e4f:5eb3:55c5");
  if (data == NULL) {
    printf("Failed getting rep data of 20c5:6bfa:3865:5e40:32a0:6e4f:5eb3:55c5");
    g_object_unref (inet);
    return 0;
  }
  if (inet_pton(AF_INET6, "70a2:5f7e:3b1c:6d64:284b:6260:5f00:1c65", addr) <= 0) {
    free(addr);
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV6(reputation, key);
  inet = sim_inet_new_from_string ("70a2:5f7e:3b1c:6d64:284b:6260:5f00:1c65");
  if (data == NULL) {
    printf("Failed getting rep data of 70a2:5f7e:3b1c:6d64:284b:6260:5f00:1c65");
    g_object_unref (inet);
    return 0;
  }
  g_object_unref (inet);

  key->keydata = (uint8_t *) addr;
  data = sim_reputation_search_exact_key_IPV4(reputation, key);
  if (data != NULL) {
    printf("Failed 1.2.3.4 should not return any data\n");
    return 0;
  }

  sim_reputation_print(reputation);
  g_object_unref(reputation);
  return 1;
}


/*
int sim_reputation_test3() {
  SimReputation *reputation = sim_reputation_new(NULL);
  sim_reputation_load_file(reputation, "reputation.data");
  sim_reputation_print(reputation);
  g_object_unref(reputation);
  return 1;
}
*/

void sim_reputation_register_tests(SimUnittesting *engine) {
  sim_unittesting_append(engine, "sim_reputation_test1", sim_reputation_test1, 1);
  sim_unittesting_append(engine, "sim_reputation_test2", sim_reputation_test2, 1);
}
#endif

// vim: set tabstop=2:
