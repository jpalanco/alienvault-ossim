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

#include "sim-server.h"

#include <gnet.h>
#include <signal.h>
#include <unistd.h>

#include "os-sim.h"
#include "sim-session.h"
#include "sim-sensor.h"
#include "sim-db-command.h"
#include "sim-uuid.h"

extern SimMain    ossim;

typedef struct _monitor_requests monitor_requests;
struct _monitor_requests //this struct will be used to permit the threaded use
												//of monitor requests from sim_server_push_session_plugin_command
{
	SimSession	*session;
	SimCommand	*command;
};

struct _SimServerPrivate
{
  SimUuid       * id;
  SimConfig     * config;

  SimReputation * reputation;
  GTcpSocket    * socket_server;
  GTcpSocket    * socket_idm;

  gint            port;

  GList         * sessions; //may be that there are multiple sessions for each agent (multiple threads each one)

  SimInet		* ip;
  gchar			* name;

  GCond			* sessions_cond;		//condition & mutex to control fully_stablished var.
  GMutex		* sessions_mutex;

  GHashTable  	* individual_sensors; //there will be one of this for each agent. Needed to know the number of events sended by each agent
};

typedef struct {
  SimConfig     *config;
  SimServer     *server;
  GTcpSocket    *socket;
} SimSessionData;

static gpointer sim_server_session (gpointer data);

static gpointer parent_class = NULL;

/* GType Functions */

static void 
sim_server_impl_dispose (GObject  *gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void 
sim_server_impl_finalize (GObject  *gobject)
{
  SimServer *server = SIM_SERVER (gobject);
	g_cond_free (server->_priv->sessions_cond);
	g_mutex_free (server->_priv->sessions_mutex);

  if (server->_priv->id)
    g_object_unref (server->_priv->id);

  if (server->_priv->ip)
    g_object_unref (server->_priv->ip);

	g_hash_table_destroy (server->_priv->individual_sensors);

  g_free (server->_priv);

  G_OBJECT_CLASS (parent_class)->finalize (gobject);

}

static void
sim_server_class_init (SimServerClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_peek_parent (class);

  object_class->dispose = sim_server_impl_dispose;
  object_class->finalize = sim_server_impl_finalize;
}

static void
sim_server_instance_init (SimServer * server)
{
  server->_priv = g_new0 (SimServerPrivate, 1);

  server->_priv->id = NULL;

  server->_priv->config = NULL;
  server->_priv->reputation = NULL;
  server->_priv->socket_server = NULL;
  server->_priv->socket_idm = NULL;

  server->_priv->port = 40001;

  server->_priv->sessions = NULL;

  server->_priv->ip = NULL;
  server->_priv->name = NULL;

  server->_priv->sessions_cond = g_cond_new();
  server->_priv->sessions_mutex = g_mutex_new();

  server->_priv->individual_sensors = g_hash_table_new_full (sim_inet_hash, sim_inet_equal, g_object_unref, NULL);
}

/* Public Methods */

GType
sim_server_get_type (void)
{
  static GType object_type = 0;
 
  if (!object_type)
  {
    static const GTypeInfo type_info = {
              sizeof (SimServerClass),
              NULL,
              NULL,
              (GClassInitFunc) sim_server_class_init,
              NULL,
              NULL,                       /* class data */
              sizeof (SimServer),
              0,                          /* number of pre-allocs */
              (GInstanceInitFunc) sim_server_instance_init,
              NULL                        /* value table */
    };
    
    g_type_init ();
                                                                                                                             
    object_type = g_type_register_static (G_TYPE_OBJECT, "SimServer", &type_info, 0);
  }
                                                                                                                             
  return object_type;
}

/*
 *
 *
 *
 *
 */
SimServer*
sim_server_new (SimConfig  *config)
{
  SimServer *server;

  g_return_val_if_fail (config, NULL);
  g_return_val_if_fail (SIM_IS_CONFIG (config), NULL);

  server = SIM_SERVER (g_object_new (SIM_TYPE_SERVER, NULL));
  server->_priv->config = config;

  if (config->server.name)
    server->_priv->name = g_strdup (config->server.name);

  server->_priv->id = g_object_ref (config->server.id);

  if (simCmdArgs.port > 0)
    server->_priv->port = simCmdArgs.port;
  else
	if (config->server.port > 0) //anti-moron sanity check
      server->_priv->port = config->server.port;

  if (simCmdArgs.ip)
    server->_priv->ip = sim_inet_new_from_string (simCmdArgs.ip);
  else
    if (config->server.ip)
      server->_priv->ip = sim_inet_new_from_string (config->server.ip);

  return server;
}

/**
 * sim_server_new_from_dm:
 *
 */
SimServer*
sim_server_new_from_dm (GdaDataModel  *dm,
                        gint row)
{
  SimServer *server;
  const GValue *value;
  const GdaBinary *binary;

  g_return_val_if_fail (GDA_IS_DATA_MODEL (dm), NULL);

  server = SIM_SERVER (g_object_new (SIM_TYPE_SERVER, NULL));

  value = gda_data_model_get_value_at (dm, 0, row, NULL);
  server->_priv->id = sim_uuid_new_from_blob (gda_value_get_blob (value));

  value = gda_data_model_get_value_at (dm, 1, row, NULL);
  server->_priv->name = g_value_dup_string (value);

  value = gda_data_model_get_value_at (dm, 2, row, NULL);
  if (!gda_value_is_null (value))
  {
    binary = (GdaBinary *) gda_value_get_blob (value);
    server->_priv->ip = sim_inet_new_from_db_binary (binary->data, binary->binary_length);
  }

  value = gda_data_model_get_value_at (dm, 3, row, NULL);
  server->_priv->port = g_value_get_int (value);

  // Check if this is the local server.
  if (sim_uuid_equal (server->_priv->id, ossim.config->server.id))
  {
    // Use config.xml IP instead.
    server->_priv->config = ossim.config;
    if (!ossim.config->server.ip)
    {
      g_message ("Bad config XML file: No server IP entry");
      return NULL;
    }
    server->_priv->ip = sim_inet_new_from_string (ossim.config->server.ip);

    server->_priv->reputation = ossim.reputation;

    if (simCmdArgs.port > 0)
      server->_priv->port = simCmdArgs.port;

    if (simCmdArgs.ip)
    {
      g_free (server->_priv->ip);
      server->_priv->ip = sim_inet_new_from_string (simCmdArgs.ip);
    }
  }
  else
  {
    server->_priv->config = sim_config_new ();
  }

  ossim_debug ("%s: %s", __func__, server->_priv->name);
  sim_server_debug_print(server);

  return server;
}


/*
 * OSSIM has internally in fact two servers; the ossim.server (the "main" server), which
 * stores all the sessions from children and master servers, as well as the sensors and 
 * frameworkd sessions. And the ossim.HA_server, which only contains sessions from an
 * HA server. 
 *
 * This function can be called with ossim.server or ossim.HA_server as parameters. Here
 * is the main loop which accept connections from "main" server or HA server.
 * 
 */
void
sim_server_listen_run (SimServer *server, gboolean is_server)
{
  SimSessionData	*session_data;
  GTcpSocket		*socket = NULL;
  GTcpSocket		*server_socket = NULL;
  GThread				*thread;
	GError 				*error = NULL;
	GInetAddr			*serverip;
  

  g_return_if_fail (SIM_IS_SERVER (server));

  g_message ("Waiting for connections...");

	if (!server->_priv->ip)
		server->_priv->ip = sim_inet_new_from_string("0.0.0.0");
	
	serverip = sim_inet_get_address (server->_priv->ip);
	if (!serverip)
	{
    gchar *ipstr = sim_inet_get_canonical_name (server->_priv->ip);
	  g_message("Error creating server address. Please check that the ip %s has the right format", ipstr);
    g_free (ipstr);
	  exit (EXIT_FAILURE);	
	}

  if (is_server)
  {
    server->_priv->socket_server = gnet_tcp_socket_server_new_full (serverip ,server->_priv->port); //bind in the interface defined
    server_socket = server->_priv->socket_server;
  }
  else
  {
    server->_priv->socket_idm = gnet_tcp_socket_server_new_full (serverip , 40002); //bind in the interface defined HARDCODED
    server_socket = server->_priv->socket_idm;
  }
	
  if (!server_socket)
  {
    g_message("Error in bind; may be another app is running in port %d? You should also check the <server ... ip=\"\"> entry and see if any of your local interfaces has got that ip address.",server->_priv->port);
    exit (EXIT_FAILURE);   
  }

	//Main loop which accept connections
  while ((socket = gnet_tcp_socket_server_accept (server_socket)) != NULL)
  {
    session_data = g_new0 (SimSessionData, 1);
    session_data->config    = server->_priv->config;
    session_data->server    = server;
    session_data->socket    = socket;
    
		/* Session Thread */		
    thread = g_thread_create(sim_server_session, session_data, FALSE, &error);
		
	  if (thread == NULL && error)
    {
			g_message ("thread error %d: %s", error->code, error->message);
			g_error_free (error);
    }
		else
			continue;
  }
}

/**
 * sim_server_is_local:
 * @server: a #SimServer object.
 *
 * Whether @server is local or not.
 */
gboolean
sim_server_is_local (SimServer * server)
{
  return (sim_uuid_equal (server->_priv->id, ossim.config->server.id));
}

/*
 *
 *
 *
 *
 *
 */
static gpointer
sim_server_session (gpointer data)
{
  SimSessionData  *session_data = (SimSessionData *) data;
  SimConfig       *config = session_data->config;
  SimServer       *server = session_data->server;
  GTcpSocket      *socket = session_data->socket;
  SimSession      *session;

  g_return_val_if_fail (SIM_IS_CONFIG (config), NULL);
  g_return_val_if_fail (SIM_IS_SERVER (server), NULL);
  g_return_val_if_fail (socket, NULL);

  ossim_debug ("%s: Trying to do a sim_session_new: pid %d", __func__, getpid());
 
  if (!(session = sim_session_new (G_OBJECT (server), config, socket)))
	{
		g_free (session_data);
		return (NULL);
	}
  
  ossim_debug ("%s: New Session: pid %d; session address: %lx", __func__, getpid(), (glong)session);

	if (!sim_session_must_close (session))
	{
	  sim_server_append_session (server, session);

	  ossim_debug ("%s: Session Append: pid %d; session address: %lx", __func__, getpid(), (gulong)session);
		g_message ("Session Append");

	  sim_session_read (session);

    if (sim_session_is_sensor (session))
		{
      SimInet *ia = sim_sensor_get_ia (sim_session_get_sensor (session));
			if (ia)
			{
				gchar *ip = sim_inet_get_canonical_name (ia);
				g_message ("- Session Sensor %s: REMOVED", ip);
				g_free (ip);
			}
			else
				g_message ("- Session Sensor *: REMOVED" );
		}

	  if (sim_server_remove_session (server, session))
		{/* 
		  if (sim_session_is_sensor (session))
  		{
		    GInetAddr *ia = sim_sensor_get_ia (sim_session_get_sensor (session));
    		gchar *ip = gnet_inetaddr_get_canonical_name (ia);
		    if (sim_session_get_hostname (session)&&ip)
    		  g_message ("- Session Sensor %s %s: REMOVED", sim_session_get_hostname (session), ip);
		    else
    	  g_message ("- Session Sensor %s: REMOVED", ip);
		    g_free (ip);
		  }*/
			//ossim_debug ( "sim_server_session: After remove session: pid %d. session: %x", getpid(),session);
		}			
		else	
			ossim_debug ("%s: Error removing session: %lx", __func__, (gulong)session);
	}
	else
	{
    g_object_unref (session);
    g_message ("Session Removed: error");
    ossim_debug ("%s: Error: after remove session: pid %d. session: %lx", __func__, getpid(), (gulong)session);
	}
  
	g_free (session_data);
     
  return NULL;
}

/*
 *
 *
 *
 *
 *
 */
void
sim_server_append_session (SimServer     *server,
												   SimSession    *session)
{
  g_return_if_fail (server);
  g_return_if_fail (SIM_IS_SERVER (server));
  g_return_if_fail (session);
  g_return_if_fail (SIM_IS_SESSION (session));

  g_mutex_lock (server->_priv->sessions_mutex);
//  while (!server->_priv->sessions_cond)       //if we dont have the condition, g_cond_wait().
//    g_cond_wait (server->_priv->sessions_cond, server->_priv->sessions_mutex);

  server->_priv->sessions = g_list_append (server->_priv->sessions, session);
  g_mutex_unlock (server->_priv->sessions_mutex);

}

/*
 *
 *
 *
 *
 *
 */
gint
sim_server_remove_session (SimServer     *server,
												   SimSession    *session)
{
  g_return_val_if_fail (SIM_IS_SERVER (server), 0);
  g_return_val_if_fail (SIM_IS_SESSION (session), 0);
	  
	void * tmp = session;

  g_mutex_lock (server->_priv->sessions_mutex);
//  while (!server->_priv->sessions_cond)       //if we dont have the condition, g_cond_wait().
//  	g_cond_wait (server->_priv->sessions_cond, server->_priv->sessions_mutex);

	//guint length = g_list_length (server->_priv->sessions);
  server->_priv->sessions = g_list_remove (server->_priv->sessions, tmp);   //and then, the list node itself
	if(session)
	g_object_unref (session);//first, remove the data inside the session

  g_mutex_unlock (server->_priv->sessions_mutex);


	return 1;
/*
	if (length == g_list_length (server->_priv->sessions)) //if the lenght is the same, we didn't removed anything-> error
		return 0;
	else
		return 1;
*/
}

/**
 *
 *
 */
GList *
sim_server_get_sessions (SimServer * server)
{
  g_return_val_if_fail (SIM_IS_SERVER (server), NULL);

  return (g_list_copy (server->_priv->sessions));
}

guint
sim_server_get_session_count(SimServer *server)
{
        return g_list_length (server->_priv->sessions);
}

guint
sim_server_get_session_count_active(SimServer *server)
{
	guint tot=0;
	GList *list;
  list = server->_priv->sessions;
  while (list)
  {
    SimSession *session = (SimSession *) list->data;

    if ((session != NULL) && SIM_IS_SESSION(session)&&!sim_session_must_close(session))
			tot++;
    list = list->next;
	}
        return tot;
}


/*
 * This is called just from sim_organizer_run
 */
void
sim_server_push_session_command (SimServer       *server,
																 SimSessionType   session_type,
																 SimCommand      *command)
{
  GList *list;

  g_return_if_fail (SIM_IS_SERVER (server));
  g_return_if_fail (SIM_IS_COMMAND (command));

   //g_mutex_lock (server->_priv->sessions_mutex);
   //while (!server->_priv->sessions_cond)       //if we dont have the condition, g_cond_wait().
     //g_cond_wait (server->_priv->sessions_cond, server->_priv->sessions_mutex);

  list = server->_priv->sessions;
  while (list)
  {
    SimSession *session = (SimSession *) list->data;

    if ((session != NULL) && SIM_IS_SESSION(session))
      if (session_type == SIM_SESSION_TYPE_ALL || session_type == session->type)
				sim_session_write (session, command); 

    list = list->next;
  }
 // g_mutex_unlock (server->_priv->sessions_mutex);
}

/*
 *
 *	Now, depending on the rule, we'll generate a specific command that will be sent
 *	with the data from the rule to the agent who issued the event that
 *	made match with the alarm.
 *
 *
 */
void
sim_server_push_session_plugin_command (SimServer       *server,
																				SimSessionType   session_type,
																				gint             plugin_id,
																				SimRule					*rule)
{
  GList *list;
					
  g_return_if_fail (SIM_IS_SERVER (server));
  g_return_if_fail (SIM_IS_RULE (rule));
		
  g_mutex_lock (server->_priv->sessions_mutex);
//  while (!server->_priv->sessions_cond)       //if we dont have the condition, g_cond_wait().
//    g_cond_wait (server->_priv->sessions_cond, server->_priv->sessions_mutex);

  list = server->_priv->sessions;
  while (list)
  {
    SimSession *session = (SimSession *) list->data;

		ossim_debug ( "sim_server_push_session_plugin_command");
    if ((session != NULL) && SIM_IS_SESSION (session))
		{	
			ossim_debug ( "sim_server_push_session_plugin_command 2");
      if (session_type == SIM_SESSION_TYPE_ALL || session_type == session->type)
      {
				ossim_debug ( "sim_server_push_session_plugin_command 3");
        if (sim_session_has_plugin_id (session, plugin_id))
				{
          GHashTable *sensors = sim_rule_get_sensors (rule);
          SimInet *sensor_ip = sim_sensor_get_ia (sim_session_get_sensor (session));

          if (!sensors || (sensors && g_hash_table_lookup (sensors, sensor_ip)))
          {
            monitor_requests	*data = g_new0 (monitor_requests, 1);
            GError						*error = NULL;
            GThread *thread;

            ossim_debug ( "sim_server_push_session_plugin_command 4");
            SimCommand *cmd = sim_command_new_from_rule (rule); //this will be freed in sim_server_thread_monitor_requests()
            data->session = session;
            data->command = cmd;
            ossim_debug ( "sim_server_push_session_plugin_command 5");

            thread = g_thread_create (sim_server_thread_monitor_requests, data, FALSE, &error);
            if (thread == NULL && error)
            {
              g_message ("thread error %d: %s", error->code, error->message);
              g_error_free (error);
            }
          }
				}
      }
		}
		else
		{			
		 //avoiding race condition; this happens when the agent disconnect from the server and there aren't any established session. FIXME: this will broke the correlation procedure in this event, I've to check this ASAP.
			ossim_debug ("%s: Error, session %lx is invalid!!", __func__, (gulong)session);
//  		g_mutex_unlock (server->_priv->sessions_mutex);
			break;
		}
      
    list = list->next;
  }
  g_mutex_unlock (server->_priv->sessions_mutex);
}

gpointer 
sim_server_thread_monitor_requests (gpointer data)
{
	monitor_requests  *request = (monitor_requests *) data;

	g_return_val_if_fail (request->command != NULL, 0);
	g_return_val_if_fail (SIM_IS_COMMAND (request->command), 0);
  ossim_debug ( "sim_server_thread_monitor_requests");

	sim_session_write (request->session, request->command);	

	//I don't like to reserve/free memory in different levels of execution, but it's the only way 
	//without change a bit more the code
	g_object_unref (request->command);
	g_free (data);

	return NULL;
}


/*
 *
 *
 *
 *
 *
 */
void
sim_server_reload (SimServer  *server,
                   SimContext *context)
{
  GList *list;

  g_return_if_fail (SIM_IS_SERVER (server));
  g_return_if_fail (SIM_IS_CONTEXT (context));

  g_mutex_lock (server->_priv->sessions_mutex);
//  while (!server->_priv->sessions_cond)       //if we dont have the condition, g_cond_wait().
//    g_cond_wait (server->_priv->sessions_cond, server->_priv->sessions_mutex);

  list = server->_priv->sessions;
  while (list)
  {
    SimSession *session = (SimSession *) list->data;

    if ((session != NULL) && SIM_IS_SESSION (session))
      sim_session_reload (session, context);

    list = list->next;
  }
  g_mutex_unlock (server->_priv->sessions_mutex);
}


/*
 *
 * returns this server's bind IP.
 *
 *
 */
SimInet*
sim_server_get_ip (SimServer   *server)
{
  g_return_val_if_fail (SIM_IS_SERVER (server), NULL);

  return server->_priv->ip;
}

/*
 * returns this server's unique OSSIM name.
 */
gchar*
sim_server_get_name (SimServer   *server)
{
  g_return_val_if_fail (SIM_IS_SERVER (server), NULL);

  return server->_priv->name;
}

/**
 * sim_server_get_id:
 * @server: a #SimServer object.
 *
 * Returns the id of @server.
 */
SimUuid *
sim_server_get_id (SimServer * server)
{
  return (server->_priv->id);
}

/**
 * sim_server_get_reputation:
 *
 * Returns a #SimReputation object related to @server.
 */
SimReputation *
sim_server_get_reputation (SimServer * server)
{
  g_return_val_if_fail (SIM_IS_SERVER (server), NULL);
  return server->_priv->reputation;
}


/*
 * Sets this server's different roles
 *
 */
void
sim_server_set_data_role (SimServer		*server,
													SimCommand	*command)
{
  g_return_if_fail (SIM_IS_SERVER (server));
  g_return_if_fail (SIM_IS_COMMAND (command));
	
	SimConfig *conf = server->_priv->config;
	sim_config_set_data_role (conf, command);
}

/*
 * Same than sim_server_set_data_role, but this only stores data in memory and directly from role.
 */
void
sim_server_set_role (SimServer *server,
                     SimRole   *role)
{
  g_return_if_fail (SIM_IS_SERVER (server));
  g_return_if_fail (SIM_IS_ROLE (role));

  SimConfig *config = server->_priv->config;
  config->server.role = role;
}

SimRole *
sim_server_get_role (SimServer *server)
{
  g_return_val_if_fail (SIM_IS_SERVER (server), NULL);

  SimConfig *config = server->_priv->config;
  if (config->server.role)
    sim_role_ref (config->server.role);

  return config->server.role;
}

SimConfig*
sim_server_get_config (SimServer   *server)
{
  g_return_val_if_fail (SIM_IS_SERVER (server), NULL);

	return server->_priv->config;
}

gint
sim_server_get_port (SimServer   *server)
{
  g_return_val_if_fail (SIM_IS_SERVER (server), 0);

	return server->_priv->port;
}

void
sim_server_set_port (SimServer   *server,
											gint			port)
{
  g_return_if_fail (SIM_IS_SERVER (server));

	server->_priv->port = port;
}

GHashTable*
sim_server_get_individual_sensors (SimServer   *server)
{
  g_return_val_if_fail (SIM_IS_SERVER (server), NULL);

  return server->_priv->individual_sensors;
}

/*
 *
 * Debug function: print the server sessions 
 *
 *
 */
void sim_server_debug_print_sessions (SimServer *server)
{
  ossim_debug ( "sim_server_debug_print_sessions:");
	GList *list;
	int a=0;
	
 	g_mutex_lock (server->_priv->sessions_mutex);
//  while (!server->_priv->sessions_cond)       //if we dont have the condition, g_cond_wait().
//		g_cond_wait (server->_priv->sessions_cond, server->_priv->sessions_mutex);

	list = server->_priv->sessions;
	while (list)
  {
    SimSession *session = (SimSession *) list->data;
    ossim_debug ("session %d: %lx", a, (gulong)session);
		a++;		
		list = list->next;
	}							 
	g_mutex_unlock (server->_priv->sessions_mutex);
		
}

void
sim_server_debug_print  (SimServer *server)
{
  gchar *ipstr = sim_inet_get_canonical_name (sim_server_get_ip (server));

  gchar *aux = g_strdup_printf("%s|%s|%d",  sim_server_get_name (server),
                                            ipstr,
                                            sim_server_get_port (server));

  g_free (ipstr);

  ossim_debug ( "sim_sensor_debug_print: %s", aux);

  g_free (aux);

}

/*
 * Load "this" server info into memory
 */
void
sim_server_load_role (SimServer	*server)
{
  g_return_if_fail (SIM_IS_SERVER (server));

  server->_priv->config->server.role = sim_db_load_server_role (ossim.dbossim,
                                                                server->_priv->id);
}

/**
 * sim_server_reload_role:
 * @server: #SimServer
 *
 * Reload role for @server
 */
void
sim_server_reload_role (SimServer *server)
{
  SimRole *old_role;

  g_return_if_fail (SIM_IS_SERVER (server));

  old_role = server->_priv->config->server.role;

  server->_priv->config->server.role = sim_db_load_server_role (ossim.dbossim,
                                                                server->_priv->id);
  if (old_role)
    sim_role_unref (old_role);
}

void
sim_server_load_rservers(SimServer *server)
{
  GHashTable *rservers;

  g_return_if_fail (SIM_IS_SERVER (server));

  rservers = sim_db_load_forward_servers (ossim.dbossim, server->_priv->id);
  if (rservers)
    sim_role_set_rservers (server->_priv->config->server.role, rservers);
}

SimSession*
sim_server_get_sensor_by_ia_port (SimServer *server,
                                  SimInet   *inet,
                                  gint       port)
{
  GList      *list;
  
  g_return_val_if_fail (SIM_IS_SERVER (server), NULL);
  g_return_val_if_fail (SIM_IS_INET (inet), NULL);

	g_mutex_lock (server->_priv->sessions_mutex);

	list = server->_priv->sessions;
  while (list)
  {
		SimSession *session = (SimSession *) list->data;
    if ((session != NULL) && SIM_IS_SESSION(session) && sim_session_is_sensor(session))
		{
			if (sim_inet_noport_equal (sim_session_get_ia (session), inet)
          && sim_session_get_port (session) == port)
			{	
        g_object_ref (session);
  			g_mutex_unlock (server->_priv->sessions_mutex);
				return session;
			}
  	}
	  list = list->next;
	}
	g_mutex_unlock (server->_priv->sessions_mutex);
	return NULL;
}

/**
 * sim_server_set_name:
 * @server: a #SimServer
 * @name: gchar * server name
 *
 */
void
sim_server_set_name (SimServer *server,
                     gchar     *name)
{
  g_return_if_fail (SIM_IS_SERVER (server));
  g_return_if_fail (name);

  if (server->_priv->name)
    g_free (server->_priv->name);

  server->_priv->name = g_strdup (name);
}

/**
 * sim_server_set_ip:
 * @server: a #SimServer
 * @ip: a #SimInet with the ip
 *
 */
void
sim_server_set_ip (SimServer *server,
                   SimInet   *ip)
{
  g_return_if_fail (SIM_IS_SERVER (server));
  g_return_if_fail (SIM_IS_INET (ip));

  if (server->_priv->ip)
    g_object_unref (server->_priv->ip);

  server->_priv->ip = g_object_ref (ip);
}

static 
void                sim_server_populate_sensor_array                           (gpointer key,
                                                         gpointer value,
                                                         gpointer user_data)
{
  (void)key;
  GPtrArray *ptr = (GPtrArray*)user_data;
  gchar *v = (gchar *)value; 
  g_ptr_array_add  (ptr, g_ascii_strup (&v[2],-1));
}


/**
  *  @brief sim_server_get_sensor_uuids_unique
  *  @param server pointer to a #SimServer object
  *  @param p_array pointer to a pointer where we return an GPtrArray. Each member is a pointer to the internal uuid of the sensor
  *  @returns TRUE if no error, FALSE on error
*/

gboolean
sim_server_get_sensor_uuids_unique (SimServer *server, GPtrArray **pparray)
{
  gboolean result = FALSE;
  g_return_val_if_fail (server != NULL, FALSE);
  g_return_val_if_fail (pparray != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_SERVER (server), FALSE);
  GPtrArray *array = NULL;
  *pparray = NULL;
  GList *list;
  GHashTable *hash = NULL;
  SimSession *session;
  SimSensor *sensor;
  SimInet   *inet_sensor;
  gchar *ipaddr = NULL;
  do{
    /* This is critical. I need to iterate in the SimSessions list, and obtain only sensor sessions and the internal uuid of each sensor */
    if ((array =    g_ptr_array_new_with_free_func (g_free)) == NULL)
    {
      g_message ("%s: Internal error",__FUNCTION__);
      break;
    }
    if ((hash = g_hash_table_new_full (g_str_hash,g_str_equal,g_free, NULL)) == NULL)
    {
      g_message ("%s: Internal error", __FUNCTION__);
      break;
    }
    g_mutex_lock (server->_priv->sessions_mutex);
    list = g_list_first (server->_priv->sessions);
    while (list)
    {
      session = (SimSession*)list->data;
      if (sim_session_is_sensor (session))
      {
        sensor = sim_session_get_sensor (session);
        inet_sensor =   sim_sensor_get_ia (sensor);
        ipaddr =  sim_inet_get_canonical_name (inet_sensor);
        if (g_hash_table_lookup (hash, ipaddr) == NULL)
        {
          g_hash_table_insert (hash,g_strdup (ipaddr),(gpointer) sim_uuid_get_db_string (sim_sensor_get_id (sensor)));
        }
        g_free (ipaddr);
        
      }
      list =  g_list_next (list);
    }
    /* For each key, add the uuid to the array. Now we must duplicate the uuid */
    g_hash_table_foreach (hash,sim_server_populate_sensor_array, (gpointer) array); 
    g_mutex_unlock (server->_priv->sessions_mutex);

    
    result = TRUE;
     
  }while (0);
  if (result == FALSE)
  {
    if (array != NULL)
    {
      g_ptr_array_free (array, TRUE);
      array = NULL;
    }
   
  }
  else
  {
    *pparray = array;
  }
 
  if (hash != NULL)
    g_hash_table_destroy (hash);

  return result;
}

/*
 *
 * This will return the session associated with a specific ia (ip & port).
 * If the parameter "server" is ossim.server, here you'll find the sessions from 
 * other agents, as well as the sessions from this server to its master servers.
 * If the parameter "server" is ossim.HA_server, you'll find the HA server sessions.
 *
 * Although it's a bad idea (and I'm not sure if really interesting),
 * you can do the following: Say you have 2 machines each one with an ossim-server, A and B. 
 * You can connect server A to server B, and configure the agent B to send data 
 * to server A instead to server B.
 *
 */
/* CURRENTLY UNUSED
SimSession*
sim_server_get_session_by_ia (SimServer       *server,
                              SimSessionType   session_type,
                              GInetAddr       *ia)
{
  GList *list;

  g_return_val_if_fail (SIM_IS_SERVER (server), NULL);

  g_mutex_lock (server->_priv->sessions_mutex);
//  while (!server->_priv->sessions_cond)       //if we dont have the condition, g_cond_wait().
//    g_cond_wait (server->_priv->sessions_cond, server->_priv->sessions_mutex);

  list = server->_priv->sessions;
  while (list)
  {
    SimSession *session = (SimSession *) list->data;
    if ((SIM_IS_SESSION (session)) &&
        (session_type == SIM_SESSION_TYPE_ALL || session_type == session->type))
    {
      SimInet *inet = sim_session_get_ia (session);
      GInetAddr *session_ia = sim_inet_get_address (inet);
      if (gnet_inetaddr_equal (session_ia, ia))
      {
        g_mutex_unlock (server->_priv->sessions_mutex);
        return session;
      }
    }
    list = list->next;
  }
  g_mutex_unlock (server->_priv->sessions_mutex);
  return NULL;
}
*/

/*
 *
 * We want to know which is the session which belongs to a specific sensor
 *
 *
 */
/* CURRENTLY UNUSED
SimSession*
sim_server_get_session_by_sensor (SimServer   *server,
																  SimSensor   *sensor)
{
  GList *list;

  g_return_val_if_fail (SIM_IS_SERVER (server), NULL);
  g_return_val_if_fail (SIM_IS_SENSOR (sensor), NULL);

  g_mutex_lock (server->_priv->sessions_mutex);
//  while (!server->_priv->sessions_cond)       //if we dont have the condition, g_cond_wait().
//    g_cond_wait (server->_priv->sessions_cond, server->_priv->sessions_mutex);

  list = server->_priv->sessions;
  while (list)
  {
    SimSession *session = (SimSession *) list->data;
    if ((session != NULL) && SIM_IS_SESSION(session))
      if (sim_session_get_sensor (session) == sensor)
			{	
  			g_mutex_unlock (server->_priv->sessions_mutex);
				return session;
			}

	  list = list->next;
  }

  g_mutex_unlock (server->_priv->sessions_mutex);
  return NULL; //no sessions stablished
}
*/

// vim: set tabstop=2:
