/* (c) AlienVault Inc. 2012-2013
 * All rights reserved
 *
 * This code is protected by copyright and distributed under licenses
 * restricting its use, copying, distribution, and decompilation. It may
 * only be reproduced or used under license from Alienvault Inc. or its
 * authorised licensees.
 */


#include <time.h>
#include <glib.h>
#include <libsoup/soup.h>
#include "sim-container.h"
#include "sim-server-api.h"
#include "sim-database.h"
#include "os-sim.h"
#include "sim-server.h"
#include "sim-session.h"
#include "config.h"

extern SimMain ossim;

// Static functions
static gchar * sim_server_api_get_per_session_stats (void);

/* We can use global variables. This functions are called only from main thread in the main loop */

static GTimeVal old_time;
static gchar *old_engine_stats = NULL; /* I must free this in atexit / similar function */
static gchar *old_server_stats = NULL;

static guint  events_before = 0;
static guint  sim_before = 0; //store how many events had the sem queue last time
static gint  total_db_old = 0;
extern guint  sem_total_events_popped;

/** 
  @brief Filter all localhost / 127.0.0.1 queries, with no auth
*/
static
gboolean sim_server_api_auth_filter (SoupAuthDomain *domain,
                                                         SoupMessage *msg,
                                                         gpointer user_data)
{
  gboolean result = TRUE;
  (void) user_data;
  SoupURI *uri = NULL;
  /* Read the mess*/
  if (msg != NULL && domain != NULL)
  {
    if (strcmp (soup_auth_domain_get_realm (domain), "ossim api") == 0)
    {
      if ((uri = soup_message_get_uri (msg)) != NULL)
      {
        if (strcmp (uri->host,"localhost") == 0 || strcmp (uri->host,"127.0.0.1") == 0)
        {
          if (uri->port == SERVER_API_PORT)
          {
                return FALSE;
          }
        }   
      }
          
    }
  }
  return result; 
}

/**
 * sim_server_api_get_per_session_stats:
 *
 */
static
gchar *
sim_server_api_get_per_session_stats (void)
{
  GString * per_session_stats = g_string_new ("[");
  GList * server_sessions = sim_server_get_sessions (ossim.server), * aux_list = NULL;
  SimSession * session = NULL;
  const gchar * SimSessionTypeStr[] = {"None", "Server Up", "Server Down", "Sensor", "Frameworkd", "Web", "HA", "All"};

  for (aux_list = server_sessions; aux_list && (aux_list->data != NULL); aux_list = aux_list->next)
  {
    session = SIM_SESSION (aux_list->data);

    // Session IP
    per_session_stats = g_string_append (per_session_stats, "\n{");

    // Session stats.
    g_string_append_printf (per_session_stats,
                            "\"ip_addr\": \"%s\",\n"
                            "\"type\": \"%s\",\n"
                            "\"last_data_timestamp\": \"%lu\",\n"
                            "\"last_event_timestamp\": \"%lu\"\n",
                            sim_session_get_ip_str (session),
                            SimSessionTypeStr[session->type],
                            (guint64)sim_session_get_last_data_timestamp (session),
                            (guint64)sim_session_get_last_event_timestamp (session));

    g_string_append_printf (per_session_stats, "}%s", aux_list->next ? "," : "\n");
  }

  g_list_free (server_sessions);
  per_session_stats = g_string_append (per_session_stats, "]");
  return (g_string_free (per_session_stats, FALSE));
}

static gchar *
server_api_get_server_stats (GTimeVal *p_current_time)
{
  gchar *result = NULL;
  glong diff;
  gint events_now = 0;    
  diff =  p_current_time->tv_sec - old_time.tv_sec;
  guint total_events = sim_organizer_get_total_events (ossim.organizer);
  if (diff > 0)
  {
    events_now = sim_container_get_events_count (ossim.container);
    events_now = MAX (0, events_now);
  gint eps = (events_now - events_before) / diff ;
  gint eps_sim = (total_events - sim_before) / diff;

  if (!total_db_old)
    total_db_old = events_now;

  eps = MAX (0, eps);
  eps_sim = MAX (0, eps_sim);

  //this if needed for the first event
  if (events_before == 0)
    eps = 0;
  events_before = events_now;
  sim_before = total_events;
  result = g_strdup_printf ("{"
      "\"sim_queue\":\"%u\",\n"
      "\"sim_popped\":\"%u\",\n"
      "\"sim_eps\":\"%u\",\n"
      "\"db_inserted\":\"%u\",\n"
      "\"db_total\":\"%u\",\n"
      "\"db_eps\":\"%u\",\n"
      "\"sessions_total\":\"%u\",\n"
      "\"sessions_active\":\"%u\",\n"
      "\"backlogs\":\"%u\"\n"
      "}",
      sim_organizer_get_events_in_queue (),
      sim_organizer_get_total_events (ossim.organizer),
      eps_sim,
      MAX (0, (events_now - total_db_old)),
      events_now,
      eps,
      sim_server_get_session_count (ossim.server),
      sim_server_get_session_count_active (ossim.server),
      sim_container_get_total_backlogs (ossim.container)
      );
    g_free (old_server_stats);
    old_server_stats = g_strdup (result);
    
     



    
  }
  else
  {
    result = g_strdup (old_server_stats);
  }
  return result;
}
static gchar *
server_api_get_engine_stats (GTimeVal *p_current_time)
{
  gchar *result;
  glong diff;
  diff = p_current_time->tv_sec - old_time.tv_sec;
  if (diff > 0)
  {
    g_free (old_engine_stats);
    result = sim_container_get_engine_stats_json (ossim.container, diff);
    old_engine_stats = g_strdup (result);
  }
  else
    result = g_strdup (old_engine_stats);
 
  return result;
}

static gboolean
server_api_get_info_sensors (GPtrArray ** p_rsensors, GPtrArray ** p_unrsensors, GHashTable ** p_hash)
{
  gboolean result = FALSE;
  GPtrArray *r_array = NULL;
  GPtrArray *unr_array = NULL;
  GdaDataModel *data_model = NULL;
  GdaDataModelIter *iter = NULL;
  GHashTable *hash = NULL;
  GError *error = NULL;
  const GValue *v_av_uuid;
  const GValue *v_name;
  const GValue *v_internal_uuid;
  *p_rsensors = *p_unrsensors = NULL;
  *p_hash = NULL;
  do
  {
    if ((r_array = g_ptr_array_new_with_free_func (g_free)) == NULL)
      break;
    if ((unr_array = g_ptr_array_new_with_free_func (g_free)) == NULL)
      break;
    if ((hash = g_hash_table_new_full (g_str_hash, g_str_equal, g_free, g_free)) == NULL)
    {
      g_message ("%s: Can't create object", __FUNCTION__);
      break;
    }
    if ((data_model =
            sim_database_execute_single_command (ossim.dbossim,
                "SELECT s.name as SENSOR_NAME, HEX(s.id) AS SENSOR_TABLE_UUID, UCASE(HEX(cl.id)) AS SYSTEM_UUID FROM sensor s, system cl WHERE (s.ip=cl.vpn_ip OR s.ip=cl.admin_ip);")) == NULL)
    {
      g_message ("Query error in  server_api_get_info_sensors");
      break;
    }
    if ((iter = gda_data_model_create_iter (data_model)) == NULL)
    {
      g_message ("%s: Can't create data iterator", __FUNCTION__);
      break;
    }
    result = TRUE;
    while (gda_data_model_iter_move_next (iter))
    {

      if ((v_name = gda_data_model_iter_get_value_at_e (iter, 0, &error)) == NULL || !G_VALUE_HOLDS_STRING (v_name))
      {
        result = FALSE;
        break;
      }
      if ((v_internal_uuid = gda_data_model_iter_get_value_at_e (iter, 1, &error)) == NULL || !G_VALUE_HOLDS_STRING (v_internal_uuid))
      {
        result = FALSE;
        break;
      }

      if ((v_av_uuid = gda_data_model_iter_get_value_at_e (iter, 2, &error)) == NULL || !G_VALUE_HOLDS_STRING (v_av_uuid))
      {
        result = FALSE;
        break;
      }
      if (strcmp ("(null)", g_value_get_string (v_name)) == 0)
      {
        g_ptr_array_add (unr_array, g_strdup (g_value_get_string (v_av_uuid)));
      }
      else
      {
        g_ptr_array_add (r_array, g_strdup (g_value_get_string (v_av_uuid)));
      }
      /* Insert the data in the hash table indexed by IP, we need it for the connected sensors */
      /* XXX verify  the next case: New sensor detected, but without av uuid */
      g_hash_table_insert (hash, g_strdup (g_value_get_string (v_internal_uuid)), g_strdup (g_value_get_string (v_av_uuid)));




    }
    if (!result)
      break;

    result = TRUE;
  }
  while (0);
  /* Clean up */
  if (result == FALSE)
  {
    if (r_array != NULL)
      g_ptr_array_unref (r_array);
    if (unr_array != NULL)
      g_ptr_array_unref (unr_array);
    if (hash != NULL)
      g_hash_table_destroy (hash);

  }
  else
  {
    *p_rsensors = r_array;
    *p_unrsensors = unr_array;
    *p_hash = hash;
  }
  if (iter != NULL)
    g_object_unref (iter);
  if (data_model != NULL)
    g_object_unref (data_model);
  if (error != NULL)
    g_error_free (error);

  return result;
}


static void
server_api_status_callback (SoupServer * server, SoupMessage * msg, const char *path, GHashTable * query, SoupClientContext * client, gpointer user_data)
{
  (void) server;
  (void) path;
  (void) query;
  (void) client;
  (void) user_data;
  char *response = NULL;
  GPtrArray *r_array = NULL;
  GPtrArray *unr_array = NULL;
  GPtrArray *connected_sensors = NULL;
  GHashTable *hash;
  GString *st_sensors = NULL;
  GString *st_unrsensors = NULL;
  GString *st_csensors = NULL;
  gchar *per_session_stats = NULL;
  gchar *engine_stats = NULL;
  gchar *server_stats = NULL;
  GTimeVal current_time;
  SoupSocket *sock = NULL;
  SoupAddress *sockaddr = NULL;
  SoupURI *uri = NULL;
  guint i;
  /* Only get methods and no params */
  g_get_current_time (&current_time);
  if (msg->method != SOUP_METHOD_GET || query != NULL)
  {
    soup_message_set_status (msg, SOUP_STATUS_NOT_IMPLEMENTED);
    return;
  }
 /* Verificación anti listos */
  if ((sock = soup_client_context_get_socket (client)) == NULL) 
  {
    soup_message_set_status (msg, SOUP_STATUS_INTERNAL_SERVER_ERROR);
    return;
  }
  /* If host == 127.0.0.1 or host == localhost, verify that the localaddress == 127.0.0.1 */
  if ((uri = soup_message_get_uri (msg)) == NULL)
  {
    soup_message_set_status (msg, SOUP_STATUS_INTERNAL_SERVER_ERROR);
    return;
  }
  if (strcmp (uri->host,"127.0.0.1") == 0 || strcmp (uri->host,"localhost") == 0)
  {
    if ((sockaddr = soup_socket_get_local_address (sock)) == NULL)
    {
      soup_message_set_status (msg, SOUP_STATUS_INTERNAL_SERVER_ERROR);
      return;
    }
    else
    {
      /* Aquí hay un listo */
      if (strcmp ( soup_address_get_physical(sockaddr),"127.0.0.1") != 0)
      {
        soup_message_set_status (msg, SOUP_STATUS_UNAUTHORIZED);
        return;
      }
    }
  }

  if (server_api_get_info_sensors (&r_array, &unr_array, &hash) == FALSE)
  {
    soup_message_set_status (msg, SOUP_STATUS_INTERNAL_SERVER_ERROR);
    return;
  }
/* Load the info of connected sensors */
  if (sim_server_get_sensor_uuids_unique (ossim.server, &connected_sensors) == FALSE)
  {
    soup_message_set_status (msg, SOUP_STATUS_INTERNAL_SERVER_ERROR);
    return;
  }


  if ((st_sensors = g_string_new ("")) == NULL)
  {
    soup_message_set_status (msg, SOUP_STATUS_INTERNAL_SERVER_ERROR);
    return;
  }
  if ((st_unrsensors = g_string_new ("")) == NULL)
  {
    soup_message_set_status (msg, SOUP_STATUS_INTERNAL_SERVER_ERROR);
    return;
  }
  if ((st_csensors = g_string_new ("")) == NULL)
  {
    soup_message_set_status (msg, SOUP_STATUS_INTERNAL_SERVER_ERROR);
    return;
  }


  /* For the register sensors */
  if (r_array->len > 0)
  {

    for (i = 0; i < r_array->len; i++)
    {
      g_string_append_printf (st_sensors, "\"%s\"", (gchar *) g_ptr_array_index (r_array, i));
      if ((i != (r_array->len - 1)))
      {
        g_string_append_c (st_sensors, ',');
      }
    }
    g_string_prepend_c (st_sensors, '[');
    g_string_append_c (st_sensors, ']');
  }
  else
  {
    g_string_printf (st_sensors, "[null]");
  }

  /* For the unregister sensors */
  if (unr_array->len > 0)
  {
    for (i = 0; i < unr_array->len; i++)
    {
      g_string_append_printf (st_unrsensors, "\"%s\"", (gchar *) g_ptr_array_index (unr_array, i));
      if ((i != (unr_array->len - 1)))
      {
        g_string_append_c (st_unrsensors, ',');
      }
    }
    g_string_prepend_c (st_unrsensors, '[');
    g_string_append_c (st_unrsensors, ']');
  }
  else
  {
    g_string_printf (st_unrsensors, "[null]");
  }
  /* The connected sensors */
  if (connected_sensors->len > 0)
  {
    int count = 0;
    for (i = 0; i < connected_sensors->len; i++)
    {
      char *av_uuid;
      if ((av_uuid = g_hash_table_lookup (hash, (gchar *) g_ptr_array_index (connected_sensors, i))) != NULL)
      {
        g_string_append_printf (st_csensors, "\"%s\"", av_uuid);
        count++;
        if ((i != (connected_sensors->len - 1)))
        {
          g_string_append_c (st_csensors, ',');
        }
      }
    }
    if (count > 0)
    {
      g_string_prepend_c (st_csensors, '[');
      g_string_append_c (st_csensors, ']');
    }
    else
    {
      g_string_printf (st_csensors, "[null]");

    }
  }
  else
  {
    g_string_printf (st_csensors, "[null]");
  }




  per_session_stats = sim_server_api_get_per_session_stats ();
  server_stats = server_api_get_server_stats (&current_time);
  engine_stats = server_api_get_engine_stats (&current_time);
  old_time.tv_sec = current_time.tv_sec;

response =
      g_strdup_printf ("{\"result\":{\"request\":\"/server/status\",\n\"timestamp\":\"%lld\",\n\"rsensors\":%s,\n\"unrsensors\":%s,\n\"csensors\":%s,\n\"engine_stats\":[%s],\n\"server_stats\":%s,\n\"per_session_stats\":%s\n},\n\"status\":\"OK\"\n}",
                       (long long) time (NULL),
                       st_sensors->str,
                       st_unrsensors->str,
                       st_csensors->str,
                       engine_stats,
                       server_stats,
                       per_session_stats
        );

  soup_message_set_response (msg, "application/json", SOUP_MEMORY_TAKE, response, strlen (response));
  soup_message_set_status (msg, SOUP_STATUS_OK);
  if (r_array != NULL)
    g_ptr_array_unref (r_array);
  if (unr_array != NULL)
    g_ptr_array_unref (unr_array);
  if (hash != NULL)
    g_hash_table_destroy (hash);
  if (st_sensors != NULL)
  {
    g_string_free (st_sensors, TRUE);
  }
  if (st_unrsensors != NULL)
  {
    g_string_free (st_unrsensors, TRUE);
  }
  if (st_csensors != NULL)
  {
    g_string_free (st_csensors, TRUE);
  }
  if (connected_sensors != NULL)
     g_ptr_array_unref (connected_sensors);
  g_free (per_session_stats);
  g_free (engine_stats);
  g_free (server_stats);
}

static gboolean 
sim_server_auth_callback( SoupAuthDomain *domain, SoupMessage *msg,
           const char *username, const char *password,
           gpointer user_data)
{
  gboolean result = FALSE;
  (void)domain; 
  (void) msg;
  (void) user_data;
  do{
    if (ossim.user_auth == NULL)
      break;
    if (username != NULL && password != NULL)
      result = sim_user_auth_check_login (ossim.user_auth, username, password); 
  }while (0);
  return result;
}


gboolean
sim_server_api_init (SoupServer * server)
{
  gboolean result = FALSE;
  g_return_val_if_fail (server != NULL, FALSE);
  /* Add the auth code */
  
  soup_server_add_handler (server, "/server/status", server_api_status_callback, NULL, NULL);
  if ((ossim.domain_auth = soup_auth_domain_basic_new (
      SOUP_AUTH_DOMAIN_REALM,"ossim api",
      SOUP_AUTH_DOMAIN_BASIC_AUTH_CALLBACK, sim_server_auth_callback,
      SOUP_AUTH_DOMAIN_BASIC_AUTH_DATA, ossim.user_auth,
      SOUP_AUTH_DOMAIN_ADD_PATH,"/server/status",
      NULL)) == NULL)
  {
    result = FALSE;
  }
  else
  {
    soup_auth_domain_set_filter (ossim.domain_auth,sim_server_api_auth_filter , NULL, NULL); 
    soup_server_add_auth_domain (server, ossim.domain_auth);
    result = TRUE;
  }
  if (ossim.domain_auth != NULL)
    g_object_unref (ossim.domain_auth);
  return result;
}

gboolean
sim_server_api_stop (SoupServer * server)
{
  gboolean result = FALSE;
  soup_server_remove_handler (server, "/server/status"); 
  if (ossim.domain_auth != NULL)
    soup_auth_domain_remove_path (ossim.domain_auth, "/server/status");

  g_free (old_engine_stats);
  g_free (old_server_stats);
  old_engine_stats = old_server_stats = NULL;
  result = TRUE; 
  return result;
}
