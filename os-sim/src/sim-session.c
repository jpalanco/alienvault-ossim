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

#define _XOPEN_SOURCE 1

#include "config.h"

#include "sim-session.h"

#include <sys/socket.h>
#include <gnet.h>
#include <string.h>
#include <signal.h>
#include <unistd.h>

#include "sim-config.h"	//server role.
#include "os-sim.h"
#include "sim-rule.h"
#include "sim-directive.h"
#include "sim-plugin-sid.h"
#include "sim-plugin.h"
#include "sim-container.h"
#include "sim-sensor.h"
#include "sim-command.h"
#include "sim-plugin-state.h"
#include "sim-server.h"
#include "sim-debug.h"
#include "sim-db-command.h"
#include "sim-geoip.h"

G_LOCK_EXTERN (s_mutex_config);
G_LOCK_EXTERN (s_mutex_plugins);
G_LOCK_EXTERN (s_mutex_plugin_sids);
G_LOCK_EXTERN (s_mutex_inets);
G_LOCK_EXTERN (s_mutex_host_levels);
G_LOCK_EXTERN (s_mutex_net_levels);
G_LOCK_EXTERN (s_mutex_events);
G_LOCK_EXTERN (s_mutex_servers);

extern SimMain    ossim;
extern guint sem_total_events_popped;
extern gboolean	ActivateDemo;

int aux_created_events = 0;
int aux_recvd_msgs = 0;

//extern guint sem_total_events_popped;


struct _SimSessionPrivate {
  GTcpSocket	*socket;

  SimServer		*server;
  SimConfig		*config;

  SimSensor		*sensor;
  GList				*plugins;
  GList				*plugin_states;

  GIOChannel	*io;
  gint 				g_io_hup;

  gsize				ptr; // pointer to the current position to the agent private buffer
  gsize				cur_len; // Current length of the last buffer read from gnet
  gchar				ring[RING_SIZE]; // private buffer for the agent, (ring buffer)

	guint			received; // Number of received events

	//FIXME: ONE: I have to delete the following variable, since are not really used nor needed
	gulong			broken; //Number of broken lines

  SimInet  		*ia;
	gint				port;
  gchar 			*ip_str;
  gint				seq;
  gboolean		close;
	gboolean		connect;

	gchar				*hostname;	//name of the machine connected. This can be a server name (it can be up or down in the architecture)
													//, a sensor name or even a frameworkd name
  guint       watch; 

  gboolean    is_initial;	//When the server doesn't uses a local DB, it has to take the data from a master server.
													//But at the moment that Container tries to load the data, still there aren't
													//any active session, and we must accept ONLY data from master server and ONLY data with
													//information from DB. We can't accept other events because they obviously will crash the server. 
													//is_initial=TRUE if this is the initial session where data are loaded. (this happens in sim_container_new())
													
	gboolean		fully_stablished; //If this server hasn't got local DB, the container needs to know when can
																//ask for data to master servers. The connection will be fully_stablished when the children server (this
																//server) had been sent a message to master server, and the master server answers with an OK.
	GCond				*initial_cond;		//condition & mutex to control fully_stablished var.
	GMutex			*initial_mutex;		
	GMutex			*socket_mutex;		

	gint				id;			//this id is not used always. It's used to know what is the identification of the master server or
											//frameworkd that sent a msg to this server, asking for data in a children server. I.e. server1->server2->server3. 
											//server1 asks to server2 for the sensors connected to server3. We store in the session id the same id that server1
											//sent us, and it will be kept during all the messages.
	gboolean (*agent_scan_fn)(SimCommand *,GScanner*);
	gboolean (*remote_server_scan_fn)(SimCommand *,GScanner *);
	gboolean (*default_scan_fn)(SimCommand*,GScanner*);

	// Used for multiple sensors config
	GHashTable	*hashSensors;
	time_t last_data_timestamp;
  time_t last_event_timestamp;
};

SIM_DEFINE_TYPE (SimSession, sim_session, G_TYPE_OBJECT, NULL)

/* static prototipes*/
static void sim_session_config_agent (SimSession *session,SimCommand *command);
static void sim_session_config_frameworkd (SimSession *session,SimCommand *command);
static void sim_session_config_default (SimSession *session,SimCommand *command);
static void sim_session_cmd_agent_ping(SimSession *session, SimCommand *command);
static void sim_session_cmd_idm_event(SimSession *session, SimCommand *command);
static gboolean sim_session_write_final (SimSession *session, gchar *buffer, gssize count, gsize *bytes_writtenp);
static void sim_session_update_last_event_timestamp (SimSession * session);

/* GType Functions */

static void
sim_session_finalize (GObject  *gobject)
{
  SimSession *session = SIM_SESSION (gobject);
	g_message ("Session %p received %u events/commands.", session->_priv->io, session->_priv->received);
	g_message ("Session %p received %ld broken events/commands.", session->_priv->io, session->_priv->broken);

  if (session->_priv->socket)
    gnet_tcp_socket_delete (session->_priv->socket);

	if (sim_session_is_sensor (session))
		g_message ("Session Sensor : REMOVED");
	else
	if (sim_session_is_web (session))
		g_message ("Session Web: REMOVED");
	else
	if (sim_session_is_master_server (session))
		g_message ("Session Master server: REMOVED");
	else
	if (sim_session_is_children_server (session))
		g_message ("Session Children Server: REMOVED");
	
	g_message ("              Removed IP: %s", session->_priv->ip_str);

  if (session->_priv->ia)
    g_object_unref (session->_priv->ia);

	if (session->_priv->hostname)
		g_free (session->_priv->hostname);
	if (session->_priv->ip_str)
		g_free (session->_priv->ip_str);

	if (session->_priv->hashSensors)
		g_hash_table_destroy (session->_priv->hashSensors);

  if (session->_priv->sensor)
    g_object_unref (session->_priv->sensor);

  if (session->_priv->server)
    g_object_unref (session->_priv->server);

	g_cond_free (session->_priv->initial_cond);
	g_mutex_free (session->_priv->initial_mutex);
	g_mutex_free (session->_priv->socket_mutex);

  if(session->_priv)
  	g_free (session->_priv);

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_session_class_init (SimSessionClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_ref (G_TYPE_OBJECT);

  object_class->finalize = sim_session_finalize;
}

static void
sim_session_instance_init (SimSession *session)
{
  session->_priv = g_new0 (SimSessionPrivate, 1);

  session->type = SIM_SESSION_TYPE_NONE;

  session->_priv->socket = NULL;

  session->_priv->config = NULL;
  session->_priv->server = NULL;

  session->_priv->sensor = NULL;
  session->_priv->plugins = NULL;

  session->_priv->plugin_states = NULL;

  session->_priv->received= 0;
  session->_priv->broken= 0;

  session->_priv->ptr= 0;
  session->_priv->cur_len= 0;


  session->_priv->io = NULL;
  session->_priv->ia = NULL;
	session->_priv->port = 0;
  session->_priv->seq = 0;

  session->_priv->connect = FALSE;

 	session->_priv->hostname = NULL;
 	session->_priv->ip_str = NULL;


	session->_priv->is_initial = FALSE;

	//mutex initial session init. In fact we only need the condition.
	session->_priv->fully_stablished = FALSE;
	session->_priv->initial_cond = g_cond_new();
	session->_priv->initial_mutex = g_mutex_new();

	//To prevent more than 1 thread writting to the socket
	session->_priv->socket_mutex = g_mutex_new();

	session->_priv->id = 0;
	// Init de scannner to NULL
	session->_priv->agent_scan_fn = NULL;
	session->_priv->remote_server_scan_fn = NULL;
	session->_priv->default_scan_fn = sim_command_get_default_scan ();
	session->type = SIM_SESSION_TYPE_NONE;
	session->_priv->hashSensors = g_hash_table_new_full (g_str_hash,g_str_equal,g_free,NULL);
}

/* Public Methods */

/*
 *
 *
 *
 *
 */
SimSession *
sim_session_new (GObject       *object,
                 SimConfig     *config,
                 GTcpSocket    *socket)
{
  SimServer  *server  = (SimServer *) object;
  SimSession *session = NULL;
  GInetAddr  *ia      = NULL;
  gchar      *ip;

  g_return_val_if_fail (SIM_IS_SERVER (server), NULL);
  g_return_val_if_fail (SIM_IS_CONFIG (config), NULL);
  if (!(session = SIM_SESSION (g_object_new (SIM_TYPE_SESSION, NULL))))
  {
    if (!socket)
      return NULL;

    ia = gnet_tcp_socket_get_remote_inetaddr (socket);
    if (ia != NULL)
    {
      ip = gnet_inetaddr_get_canonical_name (ia);
      g_message ("%s: Cannot create new session for remote IP %s", __func__, ip);
      g_free (ip);
    }
    else
      g_message ("%s: Cannot create new session for remote IP", __func__);

    gnet_inetaddr_unref (ia);
    return (NULL);
  }

  session->_priv->config = config;
  session->_priv->server = g_object_ref (server);
  session->_priv->close = FALSE;

  if (socket)
    sim_session_set_socket (session, socket);
  else
    session->_priv->close = TRUE;

  g_message ("New session %s socket created", socket ? "with" : "without");
  return session;
}

void 
sim_session_handle_HUP(GIOChannel *io,GIOCondition condition, gpointer data)
{
	SimSession *session=(SimSession*)data;

	// unused parameter
	(void) io;

	if(condition&G_IO_HUP)
	{
		//g_mutex_lock(session->_priv->socket_mutex);
		ossim_debug ( "sim_session_handle_HUP: Caught in:  %s", session->_priv->ip_str);
		 
		session->_priv->g_io_hup=1;
		//g_mutex_unlock(session->_priv->socket_mutex);
	}
}

void 
sim_session_set_g_io_hup(SimSession *session)
{
	session->_priv->g_io_hup=1;
}

/*
 *
 *
 *
 */
SimInet  *
sim_session_get_ia (SimSession *session)
{
  g_return_val_if_fail (SIM_IS_SESSION (session), NULL);

  return session->_priv->ia;
}

/**
 * @sim_session_set_ia
 */
void
sim_session_set_ia (SimSession *session,
                    SimInet    *ia)
{
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (SIM_IS_INET (ia));

  if (SIM_IS_INET (session->_priv->ia))
    g_object_unref (session->_priv->ia);

  session->_priv->ia = g_object_ref (ia);
}

gint
sim_session_get_port (SimSession * session)
{
  g_return_val_if_fail (session, 0);

	return session->_priv->port;
}

/**
 * sim_session_get_ip_str:
 *
 */
const gchar *
sim_session_get_ip_str (SimSession * session)
{
  g_return_val_if_fail (session, NULL);

  return ((const gchar *)session->_priv->ip_str);
}


/*
 * The hostname in a session means the name of the connected machine. This can be i.e. the hostname of a server or a sensor one.
 * This has nothing to do with the FQDN of the machine, this is the OSSIM name.
 */
void
sim_session_set_hostname (SimSession *session,
													gchar *hostname)
{
  g_return_if_fail (session);
  g_return_if_fail (SIM_IS_SESSION (session));

	session->_priv->hostname = g_strdup (hostname);
}

gboolean
sim_session_set_socket (SimSession *session, GTcpSocket *socket)
{
  GInetAddr *ia;

	g_return_val_if_fail (socket, FALSE);

  if (session->_priv->socket)
    gnet_tcp_socket_delete (session->_priv->socket);

  if (session->_priv->ia)
    g_object_unref (session->_priv->ia);

  if (session->_priv->ip_str)
    g_free (session->_priv->ip_str);

  session->_priv->socket = socket;
  session->_priv->close = FALSE;
	session->_priv->port = gnet_inetaddr_get_port  (gnet_tcp_socket_get_local_inetaddr(socket));

  ia = gnet_tcp_socket_get_remote_inetaddr (socket);
  session->_priv->ia = sim_inet_new (ia, SIM_INET_HOST);

	if ((session->_priv->ip_str = sim_inet_get_canonical_name (session->_priv->ia)))
	  ossim_debug ("sim_session_new: remote IP/port: %s/%d", session->_priv->ip_str, sim_inet_get_port (session->_priv->ia));
	else
	{
	  ossim_debug ( "%s: cannot get remote IP", __func__);
		session->_priv->close = TRUE;
		return FALSE;
	}
		
  if (gnet_inetaddr_is_loopback (ia)) //if the agent is in the same host than the server, we should get the real ip.
  {
		GInetAddr *aux = gnet_inetaddr_get_host_addr ();
		if (aux)
		{
			/* Copy source port */
			gnet_inetaddr_set_port (aux,gnet_inetaddr_get_port (ia));
      if (SIM_IS_INET (session->_priv->ia))
        g_object_unref (session->_priv->ia);
	    session->_priv->ia = sim_inet_new (aux, SIM_INET_HOST);
      gnet_inetaddr_unref (aux);

			if (session->_priv->ip_str)
				g_free (session->_priv->ip_str);

		  if ((session->_priv->ip_str = sim_inet_get_canonical_name (session->_priv->ia)))
				ossim_debug ("sim_session_new Remote address is loopback, applying new address: %s ", session->_priv->ip_str);
  		else
			{
				ossim_debug ( "%s: cannot get IP for loopback", __func__);
				session->_priv->close = TRUE;
				return FALSE;
			}
		}
		else
			ossim_debug ( "sim_session_new: Warning: we will maintain the 127.0.0.1 address. Please check your /etc/hosts file to include the real IP");
  }

  gnet_inetaddr_unref (ia);

  //add watch for broken pipes...
  //GIOCondition condition;
  //condition=G_IO_HUP;

  session->_priv->io = gnet_tcp_socket_get_io_channel (session->_priv->socket);
	if (!session->_priv->io) //FIXME: Why does this happens?
  {
    ossim_debug ( "sim_session_new Error: channel with IP %s has been closed (NULL value)", session->_priv->ip_str);
		session->_priv->close = TRUE;
    return FALSE;
  }

  ossim_debug ("%s: session->_priv->io: %lx", __func__, (glong)session->_priv->io);

	return TRUE;
}

/*
 */
gchar*
sim_session_get_hostname (SimSession *session)
{
  g_return_val_if_fail (session, NULL);
  g_return_val_if_fail (SIM_IS_SESSION (session), NULL);

	return session->_priv->hostname;
}

/*
 *
 *
 *
 */
static void
sim_session_cmd_connect (SimSession  *session,
                         SimCommand  *command)
{
  SimCommand  *cmd;
  SimSensor   *sensor = NULL;
  SimUuid    * id = NULL;
  SimVersion sensor_four = {4, 0, 0};

  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (SIM_IS_COMMAND (command));

  switch (command->data.connect.type)
  {
  case SIM_SESSION_TYPE_SENSOR:
    // Test for sensor UUID.

    // Test for v4 sensors. They should have an uuid.
    if (sim_version_match (command->data.connect.sensor_ver, &sensor_four))
    {
      // Connect string hasn't an uuid, close session.
      if (!(command->data.connect.sensor_id))
      {
        g_warning ("Agent from %s is at v4 but no ID was received, closing connection...", session->_priv->ip_str);
        sim_session_close (session);
        return;
      }

      // Search for this sensor uuid.
      if (!(sensor = sim_container_get_sensor_by_id (ossim.container, command->data.connect.sensor_id)))
      {
        // Search for this sensor inet address.
        if (!(sensor = sim_container_get_sensor_by_inet (ossim.container, session->_priv->ia)))
        {
          // We didn't found it. This is a new v4 agent, insert.
          sensor = sim_sensor_new_from_ia (session->_priv->ia);
          sim_sensor_set_id (sensor, command->data.connect.sensor_id);
					sim_sensor_set_name (sensor, "(null)");
				  sim_sensor_set_tzone (sensor, command->data.connect.tzone);
          sim_container_add_sensor_to_hash_table (ossim.container, sensor);
          sim_db_insert_sensor (ossim.dbossim, sensor);
          g_message ("Added a new unknown sensor with IP %s", sim_inet_get_canonical_name (session->_priv->ia));
        }
        else
        {
          // Found it. Has it a configured uuid already?
          if ((id = sim_sensor_get_id (sensor)))
          {
            // Tell the sensor it should use our uuid.
            cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_NOACK);
            cmd->id = command->id;
            cmd->data.noack.your_sensor_id = g_strdup((gchar *)sim_uuid_get_string (id));
            sim_session_write (session, cmd);
            g_object_unref (cmd);
            g_object_unref (sensor);
            sim_session_close (session);
            return;
          }
          else
          {
            // We haven't an id, use the issued one.
            sim_sensor_set_id (sensor, command->data.connect.sensor_id);
            sim_container_set_sensor_by_id (ossim.container, sensor);
            sim_db_update_sensor_by_ia (ossim.dbossim, sensor);
          }
        }
      }
      else
      {
        // Check if this sensor has a new ip address and update it.
        SimInet * sensor_ia = sim_sensor_get_ia (sensor);
        if (!(sim_inet_equal (session->_priv->ia, sensor_ia)))
        {
          sim_sensor_set_ia (sensor, session->_priv->ia);
          sim_db_insert_sensor (ossim.dbossim, sensor);
        }
      }
      // End testing for v4 sensors.
    }
    else
    {
      // Test for v3 sensors.
      if (!(sensor = sim_container_get_sensor_by_inet (ossim.container, session->_priv->ia)))
      {
        // We didn't found it. This is a new v3 agent, insert.
        sensor = sim_sensor_new_from_ia (session->_priv->ia);
				sim_sensor_set_name (sensor, "(null)");
				sim_sensor_set_tzone (sensor, command->data.connect.tzone);
        sim_container_add_sensor_to_hash_table (ossim.container, sensor);
        sim_db_insert_sensor (ossim.dbossim, sensor);
      }
    }
    // End testing for v3 sensors.

		sim_sensor_set_version (sensor, command->data.connect.sensor_ver);
		sim_db_insert_sensor_properties (ossim.dbossim, sensor);

    session->_priv->sensor = sensor;

    session->type = SIM_SESSION_TYPE_SENSOR;
    sim_session_config_agent (session, command);

    break;
  case SIM_SESSION_TYPE_FRAMEWORKD:
    sim_session_config_frameworkd (session,command);
    session->type = SIM_SESSION_TYPE_FRAMEWORKD;
    break;
  case SIM_SESSION_TYPE_WEB:
    session->type = SIM_SESSION_TYPE_WEB;
    break;
  default:
    session->type = SIM_SESSION_TYPE_NONE;
    sim_session_config_default (session,command);
    break;
  }

  if (command->data.connect.hostname&&strcmp(command->data.connect.hostname,"None"))
    sim_session_set_hostname (session, command->data.connect.hostname);
  else
    sim_session_set_hostname (session, session->_priv->ip_str);

  if (session->type != SIM_SESSION_TYPE_NONE)
  {
    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
    cmd->id = command->id;

    sim_session_write (session, cmd);
    g_object_unref (cmd);
    session->_priv->connect = TRUE;
  }
  else
  {
    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
    cmd->id = command->id;

    sim_session_write (session, cmd);
    g_object_unref (cmd);

    g_message("Received a strange session type. Clossing connection....");
    sim_session_close (session);
  }

  return;
}

/*
 * This command add one to the session plugin count in the server.
 *
 * If the plugin is a Monitor plugin, and it matches with a root node directive,
 * a msg is sent to the agent to test if it matches.
 *
 */
static void
sim_session_cmd_session_append_plugin (SimSession  *session,
                                       SimCommand  *command)
{
//  SimCommand     *cmd;
  SimPlugin      *plugin = NULL;
  SimPluginState *plugin_state;
  SimContext     *context;

  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (SIM_IS_COMMAND (command));

  session->type = SIM_SESSION_TYPE_SENSOR;  //FIXME: This will be desappear. A session always must be initiated
                                            //with a "connect" command

  context = sim_container_get_context (ossim.container, NULL);

  plugin = sim_context_get_plugin (context, command->data.session_append_plugin.id);
  if (plugin)
  {
    plugin_state = sim_plugin_state_new_from_data (plugin,
                                                   command->data.session_append_plugin.id,
                                                   command->data.session_append_plugin.state,
                                                   command->data.session_append_plugin.enabled);

    session->_priv->plugin_states = g_list_append (session->_priv->plugin_states, plugin_state);
/*
  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
  cmd->id = command->id;

  sim_session_write (session, cmd);
  g_object_unref (cmd);
*/
    /* Directives with root rule type MONITOR */
		/* FIXME: delete this code in January 2013 if no one has complained
    if (plugin->type == SIM_PLUGIN_TYPE_MONITOR)
    {
      SimEngine *engine;
      GPtrArray *directives = NULL;

      engine = sim_container_get_engine_for_context (ossim.container, NULL);

			// TODO: with taxonomy there are directives with plugin_id ANY
      directives = sim_engine_get_directives_by_plugin_id (engine,
                                                           command->data.session_append_plugin.id);
      if (directives)
      {
        guint i;
        for (i = 0; i < directives->len; i++)
        {
          SimDirective *directive = (SimDirective *)g_ptr_array_index (directives, i);
          SimRule *rule = sim_directive_get_root_rule (directive);

					SIM_WHILE_HASH_TABLE (sim_rule_get_plugin_ids (rule))
	          if (GPOINTER_TO_INT (_key) == command->data.session_append_plugin.id)
		        {
			        cmd = sim_command_new_from_rule (rule);
				      sim_session_write (session, cmd);
					    g_object_unref (cmd);
						}
        }
				g_ptr_array_unref (directives);
      }
    }
		*/

    g_object_unref (plugin);
  }
  else
  {/*
     cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
     cmd->id = command->id;

    sim_session_write (session, cmd);
    g_object_unref (cmd);
    */
  }
}

/*
 *
 *
 *
 */
static void
sim_session_cmd_session_remove_plugin (SimSession  *session,
				       SimCommand  *command)
{
  SimCommand  *cmd;
  SimPlugin   *plugin = NULL;
  SimContext  *context;

  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (SIM_IS_COMMAND (command));

  context = sim_container_get_context (ossim.container, NULL);

  plugin = sim_context_get_plugin (context, command->data.session_remove_plugin.id);
  if (plugin)
  {
    session->_priv->plugins = g_list_remove (session->_priv->plugins, plugin);

    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
    cmd->id = command->id;

    sim_session_write (session, cmd);
    g_object_unref (cmd);
    g_object_unref (plugin);
  }
  else
  {
    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
    cmd->id = command->id;

    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }
}

/*
 * Send to the session connected (master server or frameworkd) a list with all the sensors connected.
 */
static void
sim_session_cmd_server_get_sensors (SimSession  *session,
																    SimCommand  *command)
{
  SimCommand  *cmd;
  GList       *list, *listf;
	gboolean		 for_this_server;

  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

  ossim_debug ("WEB:  --- get sensors... ");
	if (sim_session_is_master_server (session) ||	// a little identity check
			sim_session_is_web (session))
	{
		ossim_debug ( "sim_session_cmd_server_get_sensors Inside");
    SimServer *server = session->_priv->server;
		
		//Check if the message is for this server....
    if ((!command->data.server_get_sensors.servername) || //FIXME: If the server name isn't specified, for backwards compatibility
																													//we will assume that this is the dst server. This should be removed in 
																													//a near future. All the commands from frameworkd should issue a server name.
        (!g_ascii_strcasecmp (sim_server_get_name (server), command->data.server_get_sensors.servername)))
    	for_this_server = TRUE;
		else
    	for_this_server = FALSE;
					
		ossim_debug ( "sim_session_cmd_server_get_sensors: %s, %s", sim_server_get_name (server), command->data.server_get_sensors.servername);

		list = sim_server_get_sessions (server);
		listf = list;
	  while (list)	//list of the sessions connected to the server
		{
			gchar *canonicalip = NULL;
			SimSession *sess = (SimSession *) list->data;
			canonicalip =  sim_inet_get_canonical_name (sess->_priv->ia);
			//check if canonicalip!=NULL and hashSenosor !=NULL
			if (canonicalip == NULL ||  session->_priv->hashSensors == NULL )
			{
				if (canonicalip == NULL)
				{
					if (sess->_priv->ia == NULL)
                      g_warning ("sim_session_cmd_server_get_sensors: canonical ip  NULL sess->_priv->ia: NULL");
                    else
                      g_warning ("sim_session_cmd_server_get_sensors: canonical ip  NULL,sess->_priv->ia");
				}
				if (session->_priv->hashSensors == NULL)
					g_warning ("sim_session_cmd_server_get_sensors: Sensor hash table is NULL");
				continue;
			}
			if (g_hash_table_lookup (session->_priv->hashSensors,canonicalip) != NULL){
				g_free (canonicalip);
				list = list->next;
				continue;
			}
			g_hash_table_insert (session->_priv->hashSensors,canonicalip,sess);
			if (for_this_server)	//execute the command in this server
		  {
				ossim_debug ( "sim_session_cmd_server_get_sensors Inside 2");
				if (sim_session_is_sensor (sess))	
				{
				  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_SENSOR);
					cmd->id = command->id;	//the original query sim_session_cmd_server_get_sensors has originated an id. This id is needed to know
																	//where to send the answer. I.e. server1/server0->server2->server3. If we're server3, we need to say to
																	//server2 which is the server1 where we want to send data, server0 or server1.
					cmd->data.sensor.host = g_strdup(canonicalip);
					/* Ok, we use the host the key for the hashSensor*/
					
					cmd->data.sensor.state = TRUE;	//FIXME: check this and why is it used. Not filled in sim_command_sensor_scan() by now.
					cmd->data.sensor.servername = g_strdup (sim_server_get_name (server));
					ossim_debug ("WEB: Sensor: %s, state:%d",cmd->data.sensor.host,	cmd->data.sensor.state);
					sim_session_write (session, cmd);	//write the sensor info in the server master or web session
					g_object_unref (cmd);
				}
			}
			else	//resend the command buffer to the children servers whose name match.
			{
				if (sim_session_is_children_server (sess) && 
						!g_ascii_strcasecmp (command->data.server_get_sensors.servername, sim_session_get_hostname (sess)) )
				{
					sim_session_set_id (session, command->id);	//when the answer has arrived again here, we need to know to what session must
																											//write data. The id is unique for each session, and the session machine connected
																											//must wait to the answer before send another query. 
																											//Ie. frameworkd->server1->server2. 
																											//       server0/     <----server0 connected also to server1
																											// if frameworkd sends a server-get-sensors command issued to server2, when the
																											// answer from server2 arrives to server1, server1 must know if the answer goes
																											// to server0 or to frameworkd. The session id tells who issued the query, but
																											// if it has issued another message, the id will be changed and this will fail.
																											// FIXME?: if we don't want to wait to send another query, the frameworkd
																											// can send with each command his name (not implemented in server), so its uniq.
					sim_session_write_from_buffer (sess, command->buffer);
				}
			}
			list = list->next;
		}
		g_hash_table_remove_all (session->_priv->hashSensors);
		
	  g_list_free (listf);
			
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
		cmd->id = command->id;
  
	  sim_session_write (session, cmd);
	  ossim_debug ("WEB: --- get sensors... Envio respuesta.. OK");
		g_object_unref (cmd);
	}
	else
	{
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
		cmd->id = command->id;
  
	  sim_session_write (session, cmd);
	  ossim_debug ("WEB: --- get sensors... Envio respuesta.. ERROR");
		g_object_unref (cmd);
	}
}

/*
 * Receives from a children server the sensors connected to it, or from other
 * children server (this->children->children i.e.)  down in the architecture.
 *
 * NOTE: this is a bit different from other msgs. This message is originated
 * thanks to a query from this server (originated in a master server or a
 * frmaeworkd). And the query usually will be redirected up.
 */
static void
sim_session_cmd_sensor (SimSession  *session,
											  SimCommand  *command)
{
  SimCommand  *cmd;
  GList       *list, *listf;

  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

	if (sim_session_is_children_server (session))
	{
    SimServer *server = session->_priv->server;
		
		ossim_debug ( "sim_session_cmd_sensor: %s, %s", sim_server_get_name (server), command->data.sensor.servername);

		list = sim_server_get_sessions (server);
		listf = list;
	  while (list)	//list of the sessions connected to the server
		{
			SimSession *sess = (SimSession *) list->data;
			
			if (sim_session_is_master_server (sess) ||
					sim_session_is_web (sess))	
			{
				if (sim_session_get_id (sess) == command->id ) //send data only to the machine that asked for it.
					sim_session_write (sess, command);	//write the sensor info in the server master or web session
			}
			list = list->next;
		}
		
	  g_list_free (listf);
	}
	else
	{
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
		cmd->id = command->id;
  
	  sim_session_write (session, cmd);
		g_object_unref (cmd);
	}
}


/*
 * Send to the session connected (master server or frameworkd) a list with the name of all the children servers connected.
 */
static void
sim_session_cmd_server_get_servers (SimSession  *session,
																    SimCommand  *command)
{
  SimCommand  *cmd;
  GList       *list, *listf;
	gboolean		 for_this_server;

  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

	if (sim_session_is_master_server (session) ||
			sim_session_is_web (session))
	{
		ossim_debug ( "sim_session_cmd_server_get_servers Inside");
    SimServer *server = session->_priv->server;
		
		//Check if the message is for this server....
    if ((!command->data.server_get_servers.servername) || //FIXME: If the server name isn't specified, for backwards compatibility
																													//we will assume that this is the dst server. This should be removed in 
																													//a near future. All the commands from frameworkd should issue a server name.
        (!g_ascii_strcasecmp (sim_server_get_name (server), command->data.server_get_servers.servername)))
    	for_this_server = TRUE;
		else
    	for_this_server = FALSE;
					
			ossim_debug ( "sim_session_cmd_server_get_servers: %s, %s", sim_server_get_name (server), command->data.server_get_servers.servername);

		list = sim_server_get_sessions (server);
		listf = list;
	  while (list)	//list of the sessions connected to the server
		{
			SimSession *sess = (SimSession *) list->data;
			
			if (for_this_server)	//execute the command in this server
		  {
				ossim_debug ( "sim_session_cmd_server_get_servers Inside 2");
				if (sim_session_is_children_server (sess))	
				{
				  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_SERVER);
					cmd->id = command->id;	//see sim_session_cmd_server_get_sensors() to understand this.
					cmd->data.server.host = sim_inet_get_canonical_name (sess->_priv->ia);
					cmd->data.server.servername = g_strdup (sim_session_get_hostname (sess));
						
					sim_session_write (session, cmd);	//write the server info in the server master or web session
					g_object_unref (cmd);
				}
			}
			else	//resend the command buffer to the children servers whose name match.
			{
				if (sim_session_is_children_server (sess) && 
						!g_ascii_strcasecmp (command->data.server_get_servers.servername, sim_session_get_hostname (sess)) )
				{
					sim_session_set_id (session, command->id);	//see sim_session_cmd_server_get_sensors() to understand this.
					sim_session_write_from_buffer (sess, command->buffer);
				}
			}
			list = list->next;
		}
		
	  g_list_free (listf);
			
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
		cmd->id = command->id;
  
	  sim_session_write (session, cmd);
		g_object_unref (cmd);
	}
	else
	{
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
		cmd->id = command->id;
  
	  sim_session_write (session, cmd);
		g_object_unref (cmd);
	}
}

/*
 * Receives from a children server the servers connected to it, or from other
 * children server (this->children->children i.e.)  down in the architecture.
 *
 * NOTE: this is a bit different from other msgs. This message is originated
 * thanks to a query from this server (originated in a master server or a
 * frmaeworkd). And the query usually will be redirected up.
 */
static void
sim_session_cmd_server (SimSession  *session,
											  SimCommand  *command)
{
  SimCommand  *cmd;
  GList       *list, *listf;

  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

	if (sim_session_is_children_server (session))
	{
    SimServer *server = session->_priv->server;
		
		ossim_debug ( "sim_session_cmd_server: %s, %s", sim_server_get_name (server), command->data.server.servername);

		list = sim_server_get_sessions (server);
		listf = list;
	  while (list)	//list of the sessions connected to the server
		{
			SimSession *sess = (SimSession *) list->data;
			
			if (sim_session_is_master_server (sess) ||
					sim_session_is_web (sess))	
			{
				if (sim_session_get_id (sess) == command->id ) //send data only to the machine that asked for it.
					sim_session_write (sess, command);	//write the server info in the server master or web session
			}
			list = list->next;
		}
		
	  g_list_free (listf);
	}
	else
	{
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
		cmd->id = command->id;
  
	  sim_session_write (session, cmd);
		g_object_unref (cmd);
	}
}


/*
 * Send to frameworkd or to a master server the plugins from a specific sensor
 *
 * The state of the plugins, and if they are enabled or not, are "injected" to
 * the server each watchdog.interval seconds with the command SIM_COMMAND_TYPE_PLUGIN_STATE_STARTED
 * or SIM_COMMAND_TYPE_PLUGIN_ENABLED.
 * 
 */
static void
sim_session_cmd_server_get_sensor_plugins (SimSession  *session,
																				   SimCommand  *command)
{
  SimCommand  *cmd;
  GList       *list, *listf;
  GList       *plugin_states;
	gboolean 		for_this_server;
	GHashTable	*sensorPlugins;
	GHashTable	*sensorHash;
	gchar 			*canonicalip;

  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));
	sensorHash = g_hash_table_new_full (g_str_hash,g_str_equal,g_free,(GDestroyNotify)g_hash_table_destroy);

	if (sim_session_is_master_server (session) ||
			sim_session_is_web (session))
	{
    SimServer *server = session->_priv->server;
		
		//Check if the message is for this server....
    if ((!command->data.server_get_sensor_plugins.servername) || //FIXME: If the server name isn't specified, for backwards compatibility
																													//we will assume that this is the dst server. This should be removed in 
																													//a near future. All the commands from frameworkd should issue a server name.
        (!g_ascii_strcasecmp (sim_server_get_name (server), command->data.server_get_sensor_plugins.servername)))
    	for_this_server = TRUE;
		else
    	for_this_server = FALSE;
					
			ossim_debug ( "sim_session_cmd_server_get_sensor_plugins: %s, %s", sim_server_get_name (server), command->data.server_get_sensor_plugins.servername);

		list = sim_server_get_sessions (server);
		listf = list;
	  while (list)	//list of the sessions connected to the server
		{
			SimSession *sess = (SimSession *) list->data;
			/* Get the IP */
			canonicalip = sim_inet_get_canonical_name (sess->_priv->ia);
			if (canonicalip == NULL)
			{
			  g_message("WEB - GetSensorPlugins - canonical ip NULL");
			  continue;
			}
			if ((sensorPlugins = g_hash_table_lookup (sensorHash,canonicalip))==NULL){
				sensorPlugins = g_hash_table_new (g_direct_hash,g_direct_equal);
				g_hash_table_insert(sensorHash,g_strdup (canonicalip),sensorPlugins);
			}
			/* If table == NULL we must create and ADD the table*/

      ossim_debug ("%s: Session : %lx", __func__, (glong)sess);

			if (for_this_server)	//execute the command in this server
		  {
				if (sim_session_is_sensor (sess))	
				{
		      plugin_states = sess->_priv->plugin_states;
		      while (plugin_states)
		      {
    	      SimPluginState  *plugin_state = (SimPluginState *) plugin_states->data;
						SimPlugin  *plugin = sim_plugin_state_get_plugin (plugin_state);
						if (!g_hash_table_lookup(sensorPlugins,GUINT_TO_POINTER(sim_plugin_get_id(plugin)))){
			        cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_SENSOR_PLUGIN);
 	 	  		    cmd->data.sensor_plugin.plugin_id = sim_plugin_get_id (plugin);
			        cmd->data.sensor_plugin.sensor = sim_inet_get_canonical_name (sess->_priv->ia); //if this is not defined
    			    cmd->data.sensor_plugin.state = sim_plugin_state_get_state (plugin_state);
        			cmd->data.sensor_plugin.enabled = sim_plugin_state_get_enabled (plugin_state);
        			ossim_debug ("WEB: get sensor plugins.... Plugin:%d  State:%d Enable:%d",cmd->data.sensor_plugin.plugin_id,cmd->data.sensor_plugin.state,cmd->data.sensor_plugin.enabled );
							sim_session_write (session, cmd);	//write the sensor info in the server master or web session
	        		g_object_unref (cmd);
							ossim_debug ("%s: Plugin:%u State:%u Enabled:%u,", __FUNCTION__,sim_plugin_get_id(plugin),
								sim_plugin_state_get_state (plugin_state),
								sim_plugin_state_get_enabled (plugin_state));
							
							g_hash_table_insert (sensorPlugins, GUINT_TO_POINTER(sim_plugin_get_id(plugin)), plugin_state);
						}else{
							ossim_debug ("%s: Info about plugin:%u already sent",__FUNCTION__,sim_plugin_get_id (plugin));
						}
		        plugin_states = plugin_states->next;
      		}
				}
			}
			else	//resend the command buffer to the children servers whose name match.
			{
				if (sim_session_is_children_server (sess) && 
						!g_ascii_strcasecmp (command->data.server_get_sensor_plugins.servername, sim_session_get_hostname (sess)) )
				{
					sim_session_write_from_buffer (sess, command->buffer);
				}
			}
			list = list->next;
			//FIXME: Carlos, why was done the below function???. Also, "plugins" hasn't got any value
			//g_hash_table_replace (sensorPlugins,g_strdup (canonicalip),plugins);

			g_free (canonicalip);	    
		}
	  g_list_free (listf);
		/* Iterate throught has table*/
			
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
		cmd->id = command->id;
		ossim_debug ("WEB:  get sensor plugins.... web --- ok");
	  sim_session_write (session, cmd);
		g_object_unref (cmd);
	}
	else
	{
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
		cmd->id = command->id;
		ossim_debug ("WEB:  get sensor plugins.... web -- fail");
	  sim_session_write (session, cmd);
		g_object_unref (cmd);
	}
	g_hash_table_destroy (sensorHash);

}

/*
 * tell to a specific server what should be done with the events that it receives
 *
 */
static void
sim_session_cmd_server_set_data_role (SimSession  *session,
                                    	SimCommand  *command)
{
  SimCommand  *cmd;
  GList       *list, *listf;

  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

	if (sim_session_is_master_server (session)) //check if the remote server has rights to send data to this server
	{	
		SimServer *server = session->_priv->server;
	  
		ossim_debug ( "sim_session_cmd_server_set_data_role: servername: %s; set to server: %s", sim_server_get_name (server), command->data.server_set_data_role.servername);
		
		//Check if the command is regarding this server to get the data and store it in memory & database
		if (!g_ascii_strcasecmp (sim_server_get_name (server), command->data.server_set_data_role.servername))
		{
			sim_server_set_data_role (server, command);	

		}
		else
		{
			//send the data to other servers down in the architecture
		  list = sim_server_get_sessions (session->_priv->server);
			listf = list;
			while (list)
			{
      	SimSession *sess = (SimSession *) list->data;

	      gboolean is_server = sim_session_is_children_server (sess);
	
  	    if (is_server)
    	  {
					gchar *hostname = sim_session_get_hostname (sess);
					if (!g_ascii_strcasecmp (hostname, command->data.server_set_data_role.servername))
					{

	  	    	cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_SERVER_SET_DATA_ROLE);

						cmd->data.server_set_data_role.servername = g_strdup (command->data.server_set_data_role.servername);
						cmd->data.server_set_data_role.store = command->data.server_set_data_role.store;
						cmd->data.server_set_data_role.correlate = command->data.server_set_data_role.correlate;
						cmd->data.server_set_data_role.cross_correlate = command->data.server_set_data_role.cross_correlate;
						cmd->data.server_set_data_role.reputation = command->data.server_set_data_role.reputation;
						cmd->data.server_set_data_role.logger_if_priority = command->data.server_set_data_role.logger_if_priority;
						cmd->data.server_set_data_role.qualify = command->data.server_set_data_role.qualify;

					  sim_session_write (sess, cmd);
						g_object_unref (cmd);
						break; //just one server per message plz...
					}
				}
				
				list = list->next;

			}
	    g_list_free (listf); //FIXME Add mutexes to sessions?
		
		}

	}
	else
	{
		SimInet   *ia;
		ia = sim_session_get_ia (session);
	  gchar *ip_temp = sim_inet_get_canonical_name (ia);
		g_message ("Error: Warning, %s is trying to send server role without rights!", ip_temp);
		g_free (ip_temp);
	
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
		cmd->id = command->id;
  
	  sim_session_write (session, cmd);
		g_object_unref (cmd);
	

	}
}

/*
 * Tell to a sensor that it must start a specific plugin
 */
static void
sim_session_cmd_sensor_plugin_start (SimSession  *session,
																     SimCommand  *command)
{
  SimCommand  *cmd;
  GList       *list, *listf;
  SimInet     *ia;
	gboolean 		for_this_server;

  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (SIM_IS_COMMAND (command));

	if (sim_session_is_master_server (session) ||
			sim_session_is_web (session))
	{
    SimServer *server = session->_priv->server;
		
		//Check if the message is for this server....
    if ((!command->data.sensor_plugin_start.servername) || //FIXME: If the server name isn't specified, for backwards compatibility
																													//we will assume that this is the dst server. This should be removed in 
																													//a near future. All the commands from frameworkd should issue a server name.
        (!g_ascii_strcasecmp (sim_server_get_name (server), command->data.sensor_plugin_start.servername)))
    	for_this_server = TRUE;
		else
    	for_this_server = FALSE;
					
			ossim_debug ( "sim_session_cmd_sensor_plugin_start: %s, %s", sim_server_get_name (server), command->data.sensor_plugin_start.servername);

  	ia = sim_inet_new_from_string (command->data.sensor_plugin_start.sensor); //FIXME: Remember to check this as soon as event arrive!!

		list = sim_server_get_sessions (server);
		listf = list;
	  while (list)	//list of the sessions connected to the server
		{
			SimSession *sess = (SimSession *) list->data;
			
			if (for_this_server)	//execute the command in this server
		  {
				if (sim_session_is_sensor (sess))	
				{
	  	  	if (sim_inet_noport_equal (sess->_priv->ia, ia)) //FIXME:when agent support send names, this should be changed with sensor name
					{
		//				cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_PLUGIN_START); //this is the command isssued TO sensor
						//Now we take the data from the command issued from web (in
						//cmd->data.sensor_plugin_start struct) and we copy it to resend it to the sensor in
						//cmd->data.plugin_start struct)
			//		  cmd->data.plugin_start.plugin_id = command->data.sensor_plugin_start.plugin_id;						
						sim_session_write (sess, command); //	we pass the same command we received so we can extract the query directly.
				//		g_object_unref (cmd);
					}
				}
			}
			else	//resend the command buffer to the children servers whose name match.
			{
				if (sim_session_is_children_server (sess) && 
						!g_ascii_strcasecmp (command->data.sensor_plugin_start.servername, sim_session_get_hostname (sess)) )
				{
					sim_session_write_from_buffer (sess, command->buffer);
				}
			}
			list = list->next;
		}
		
	  g_list_free (listf);
			
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
		cmd->id = command->id;
  
	  sim_session_write (session, cmd);
		g_object_unref (cmd);
    g_object_unref (ia);
	}
  else
  {
    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
    cmd->id = command->id;

    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }

}

static void
sim_session_cmd_sensor_plugin_stop (SimSession  *session,
																    SimCommand  *command)
{
  SimCommand  *cmd;
  GList       *list, *listf;
  SimInet     *ia;
	gboolean 		for_this_server;

  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (SIM_IS_COMMAND (command));

	if (sim_session_is_master_server (session) ||
			sim_session_is_web (session))
	{
    SimServer *server = session->_priv->server;
		ossim_debug ( "%s: %s, %s", __func__, sim_server_get_name (server), command->data.sensor_plugin_stop.servername);
		
		//Check if the message is for this server....
    if ((!command->data.sensor_plugin_stop.servername) || //FIXME: If the server name isn't specified, for backwards compatibility
																													//we will assume that this is the dst server. This should be removed in 
																													//a near future. All the commands from frameworkd should issue a server name.
        (!g_ascii_strcasecmp (sim_server_get_name (server), command->data.sensor_plugin_stop.servername)))
    	for_this_server = TRUE;
		else
    	for_this_server = FALSE;
					

  	ia = sim_inet_new_from_string (command->data.sensor_plugin_stop.sensor);
		list = sim_server_get_sessions (server);
		listf = list;
	  while (list)	//list of the sessions connected to the server
		{
			SimSession *sess = (SimSession *) list->data;
			
			if (for_this_server)	//execute the command in this server
		  {
				ossim_debug ( "sim_session_cmd_server_get_sensors Inside 2");
				if (sim_session_is_sensor (sess))	
				{
		      if (sim_inet_noport_equal (sess->_priv->ia, ia))
    		  {
//		        cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_PLUGIN_STOP);
//    		    cmd->data.plugin_stop.plugin_id = command->data.sensor_plugin_stop.plugin_id;
        		sim_session_write (sess, command);
//		        g_object_unref (cmd);
    		  }
				}
			}
			else	//resend the command buffer to the children servers whose name match.
			{
				if (sim_session_is_children_server (sess) && 
						!g_ascii_strcasecmp (command->data.sensor_plugin_stop.servername, sim_session_get_hostname (sess)) )
				{
					sim_session_write_from_buffer (sess, command->buffer);
				}
			}
			list = list->next;
		}
		
	  g_list_free (listf);
			
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
		cmd->id = command->id;
  
	  sim_session_write (session, cmd);
		g_object_unref (cmd);
    g_object_unref (ia);
	}
  else
  {
    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
    cmd->id = command->id;

    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }

}

/*
 * This command can arrive from the web or a master server. It says that a
 * specific plugin must be enabled in a specific sensor.
 */
static void
sim_session_cmd_sensor_plugin_enable (SimSession  *session,
				     SimCommand  *command)
{
  SimCommand  *cmd;
  GList       *list, *listf;
  SimInet     *ia;
	gboolean 		for_this_server;

  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (SIM_IS_COMMAND (command));

	if (sim_session_is_master_server (session) ||
			sim_session_is_web (session))
	{
    SimServer *server = session->_priv->server;
		ossim_debug ( "sim_session_cmd_sensor_plugin_enable: %s, %s", sim_server_get_name (server), command->data.sensor_plugin_enable.servername);
		
		//Check if the message is for this server....
    if ((!command->data.sensor_plugin_enable.servername) || //FIXME: If the server name isn't specified, for backwards compatibility
																													//we will assume that this is the dst server. This should be removed in 
																													//a near future. All the commands from frameworkd should issue a server name.
        (!g_ascii_strcasecmp (sim_server_get_name (server), command->data.sensor_plugin_enable.servername)))
    	for_this_server = TRUE;
		else
    	for_this_server = FALSE;					

  	ia = sim_inet_new_from_string (command->data.sensor_plugin_enable.sensor);
		list = sim_server_get_sessions (server);
		listf = list;
	  while (list)	//list of the sessions connected to the server
		{
			SimSession *sess = (SimSession *) list->data;
			
			if (for_this_server)	//execute the command in this server
		  {
				if (sim_session_is_sensor (sess))	
				{
 			  	if (sim_inet_noport_equal (sess->_priv->ia, ia))
					{
//					  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_PLUGIN_ENABLED);
//						cmd->data.plugin_enabled.plugin_id = command->data.sensor_plugin_enabled.plugin_id;
					  sim_session_write (sess, command);
//					  g_object_unref (cmd);
    		  }
				}
			}
			else	//resend the command buffer to the children servers whose name match.
			{
				if (sim_session_is_children_server (sess) && 
						!g_ascii_strcasecmp (command->data.sensor_plugin_enable.servername, sim_session_get_hostname (sess)) )
				{
					sim_session_write_from_buffer (sess, command->buffer);
				}
			}
			list = list->next;
		}
	  g_list_free (listf);
			
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
		cmd->id = command->id;
  
	  sim_session_write (session, cmd);
		g_object_unref (cmd);
    g_object_unref (ia);
	}
  else
  {
    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
    cmd->id = command->id;

    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }

}

static void
sim_session_cmd_sensor_plugin_disable (SimSession  *session,
                                       SimCommand  *command)
{
  SimCommand  *cmd;
  GList       *list, *listf;
  SimInet     *ia;
	gboolean 		for_this_server;

  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (SIM_IS_COMMAND (command));

	if (sim_session_is_master_server (session) ||
			sim_session_is_web (session))
	{
    SimServer *server = session->_priv->server;
		ossim_debug ( "sim_session_cmd_sensor_plugin_disable: %s, %s", sim_server_get_name (server), command->data.sensor_plugin_disable.servername);
		
		//Check if the message is for this server....
    if ((!command->data.sensor_plugin_disable.servername) || //FIXME: If the server name isn't specified, for backwards compatibility
																													//we will assume that this is the dst server. This should be removed in 
																													//a near future. All the commands from frameworkd should issue a server name.
        (!g_ascii_strcasecmp (sim_server_get_name (server), command->data.sensor_plugin_disable.servername)))
    	for_this_server = TRUE;
		else
    	for_this_server = FALSE;					

  	ia = sim_inet_new_from_string (command->data.sensor_plugin_disable.sensor);
		list = sim_server_get_sessions (server);
		listf = list;
	  while (list)	//list of the sessions connected to the server
		{
			SimSession *sess = (SimSession *) list->data;
			
			if (for_this_server)	//execute the command in this server
		  {
				if (sim_session_is_sensor (sess))	
				{
 			  	if (sim_inet_noport_equal (sess->_priv->ia, ia))
					{
//		        cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_PLUGIN_DISABLED);
 //   		    cmd->data.plugin_disabled.plugin_id = command->data.sensor_plugin_disabled.plugin_id;
					  sim_session_write (sess, command);
//					  g_object_unref (cmd);
    		  }
				}
			}
			else	//resend the command buffer to the children servers whose name match.
			{
				if (sim_session_is_children_server (sess) && 
						!g_ascii_strcasecmp (command->data.sensor_plugin_disable.servername, sim_session_get_hostname (sess)) )
				{
					sim_session_write_from_buffer (sess, command->buffer);
				}
			}
			list = list->next;
		}
		
	  g_list_free (listf);
			
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
		cmd->id = command->id;
  
	  sim_session_write (session, cmd);
		g_object_unref (cmd);
    g_object_unref (ia);
	}
  else
  {
    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
    cmd->id = command->id;

    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }

}

/*
 * This info has been sended already to the server in the first message, the
 * "session-append-plugin". But now we need to remember it each certain time.
 * The sensor sends this information each (agent) watchdog.interval seconds, 
 * so the server learn it perodically and never is able to ask for it in a
 * specific message.
 *
 */
static void
sim_session_cmd_plugin_state_started (SimSession  *session,
			      SimCommand  *command)
{
  GList       *list;
  
  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

  list = session->_priv->plugin_states;
  while (list)
  {
    SimPluginState  *plugin_state = (SimPluginState *) list->data;
    SimPlugin  *plugin = sim_plugin_state_get_plugin (plugin_state);
    gint id = sim_plugin_get_id (plugin);

    if (id == command->data.plugin_state_started.plugin_id)
			sim_plugin_state_set_state (plugin_state, 1);

    list = list->next;
  }
}

static void
sim_session_cmd_plugin_state_unknown (SimSession  *session,
			      SimCommand  *command)
{
  GList       *list;
  
  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

  list = session->_priv->plugin_states;
  while (list)
    {
      SimPluginState  *plugin_state = (SimPluginState *) list->data;
      SimPlugin  *plugin = sim_plugin_state_get_plugin (plugin_state);
      gint id = sim_plugin_get_id (plugin);

      if (id == command->data.plugin_state_unknown.plugin_id)
	sim_plugin_state_set_state (plugin_state, 3);

      list = list->next;
    }
}



/*
 *
 *
 *
 */
static void
sim_session_cmd_plugin_state_stopped (SimSession  *session,
			      SimCommand  *command)
{
  GList       *list;
  
  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

  list = session->_priv->plugin_states;
  while (list)
    {
      SimPluginState  *plugin_state = (SimPluginState *) list->data;
      SimPlugin  *plugin = sim_plugin_state_get_plugin (plugin_state);
      gint id = sim_plugin_get_id (plugin);

      if (id == command->data.plugin_state_stopped.plugin_id)
	sim_plugin_state_set_state (plugin_state, 2);

      list = list->next;
    }
}

/*
 *
 * Enabled means that the process is actively sending msgs to the server
 *
 */
static void
sim_session_cmd_plugin_enabled (SimSession  *session,
				SimCommand  *command)
{
  GList       *list;
  
  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

	list = session->_priv->plugin_states;
  while (list)
  {
    SimPluginState  *plugin_state = (SimPluginState *) list->data;
    SimPlugin  *plugin = sim_plugin_state_get_plugin (plugin_state);
    gint id = sim_plugin_get_id (plugin);

    if (id == command->data.plugin_enabled.plugin_id)
	    sim_plugin_state_set_enabled (plugin_state, TRUE);

    list = list->next;
	}
}

/*
 *
 *
 *
 */
static void
sim_session_cmd_plugin_disabled (SimSession  *session,
				 SimCommand  *command)
{
  GList       *list;
  
  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

  list = session->_priv->plugin_states;
  while (list)
  {
    SimPluginState  *plugin_state = (SimPluginState *) list->data;
    SimPlugin  *plugin = sim_plugin_state_get_plugin (plugin_state);
    gint id = sim_plugin_get_id (plugin);

    if (id == command->data.plugin_disabled.plugin_id)
     	sim_plugin_state_set_enabled (plugin_state, FALSE);

    list = list->next;
  }
}


// This will return the number of events that the server has received in this session
static void
sim_session_cmd_sensor_get_events (SimSession  *session,
                                   SimCommand  *command)
{
  SimCommand  *cmd;

  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (SIM_IS_COMMAND (command));

  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_SENSOR_GET_EVENTS);
  cmd->id = command->id;

  gpointer aux = g_hash_table_lookup (sim_server_get_individual_sensors (session->_priv->server), sim_session_get_ia (session));

  cmd->data.sensor_get_events.num_events = GPOINTER_TO_INT (aux);
  ossim_debug ( "sim_session_cmd_sensor_get_events: %u",  GPOINTER_TO_INT (aux));
  sim_session_write (session, cmd);
  g_object_unref (cmd);
}

/*
 *
 *
 *
 */
static void
sim_session_cmd_event (SimSession	*session,
								       SimCommand	*command)
{
  SimEvent      *event = NULL;

  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (SIM_IS_COMMAND (command));

	SimCommand *cmd;
	cmd = command;

  ossim_debug ( "sim_session_cmd_event: Inside1");

	if ((!cmd->data.event.src_ip)||(!gnet_inetaddr_is_canonical (cmd->data.event.src_ip)))
	{
		if(!cmd->data.event.userdata1) cmd->data.event.userdata1=g_strdup_printf("Warning: Malformed SRC_IP: %s",cmd->data.event.src_ip);
		else if(!cmd->data.event.userdata2) cmd->data.event.userdata2=g_strdup_printf("Warning: Malformed SRC_IP: %s",cmd->data.event.src_ip);
		else if(!cmd->data.event.userdata3) cmd->data.event.userdata3=g_strdup_printf("Warning: Malformed SRC_IP: %s",cmd->data.event.src_ip);
		else if(!cmd->data.event.userdata4) cmd->data.event.userdata4=g_strdup_printf("Warning: Malformed SRC_IP: %s",cmd->data.event.src_ip);
		else if(!cmd->data.event.userdata5) cmd->data.event.userdata5=g_strdup_printf("Warning: Malformed SRC_IP: %s",cmd->data.event.src_ip);
		else if(!cmd->data.event.userdata6) cmd->data.event.userdata6=g_strdup_printf("Warning: Malformed SRC_IP: %s",cmd->data.event.src_ip);
		else if(!cmd->data.event.userdata7) cmd->data.event.userdata7=g_strdup_printf("Warning: Malformed SRC_IP: %s",cmd->data.event.src_ip);
		else if(!cmd->data.event.userdata8) cmd->data.event.userdata8=g_strdup_printf("Warning: Malformed SRC_IP: %s",cmd->data.event.src_ip);
		else if(!cmd->data.event.userdata9) cmd->data.event.userdata9=g_strdup_printf("Warning: Malformed SRC_IP: %s",cmd->data.event.src_ip);

		g_free (cmd->data.event.src_ip);
		cmd->data.event.src_ip = g_strdup_printf("0.0.0.0");
	}

	if ((!cmd->data.event.dst_ip)||(!gnet_inetaddr_is_canonical (cmd->data.event.dst_ip)))
	{
		if(!cmd->data.event.userdata1) cmd->data.event.userdata1=g_strdup_printf("Warning: Malformed DST_IP: %s",cmd->data.event.dst_ip);
		else if(!cmd->data.event.userdata2) cmd->data.event.userdata2=g_strdup_printf("Warning: Malformed DST_IP: %s",cmd->data.event.dst_ip);
		else if(!cmd->data.event.userdata3) cmd->data.event.userdata3=g_strdup_printf("Warning: Malformed DST_IP: %s",cmd->data.event.dst_ip);
		else if(!cmd->data.event.userdata4) cmd->data.event.userdata4=g_strdup_printf("Warning: Malformed DST_IP: %s",cmd->data.event.dst_ip);
		else if(!cmd->data.event.userdata5) cmd->data.event.userdata5=g_strdup_printf("Warning: Malformed DST_IP: %s",cmd->data.event.dst_ip);
		else if(!cmd->data.event.userdata6) cmd->data.event.userdata6=g_strdup_printf("Warning: Malformed DST_IP: %s",cmd->data.event.dst_ip);
		else if(!cmd->data.event.userdata7) cmd->data.event.userdata7=g_strdup_printf("Warning: Malformed DST_IP: %s",cmd->data.event.dst_ip);
		else if(!cmd->data.event.userdata8) cmd->data.event.userdata8=g_strdup_printf("Warning: Malformed DST_IP: %s",cmd->data.event.dst_ip);
		else if(!cmd->data.event.userdata9) cmd->data.event.userdata9=g_strdup_printf("Warning: Malformed DST_IP: %s",cmd->data.event.dst_ip);

		g_free (cmd->data.event.dst_ip);
		cmd->data.event.dst_ip = g_strdup_printf("0.0.0.0");
}

	//If no sensor is given, use the session info 
	if(!command->data.event.sensor||!strcmp(command->data.event.sensor,"0.0.0.0")||!strcmp(command->data.event.sensor,"None"))
	{
		g_free(command->data.event.sensor);
    if (session->_priv->sensor)
      command->data.event.sensor=g_strdup(sim_sensor_get_name(session->_priv->sensor));
    else
      command->data.event.sensor=g_strdup(ossim.config->framework.host); //this will be used for events from frameworkd (usually, plugin_id 2505, post correlation)
	}

	//If there's no src_ip, we know at least the sensor
	if(!command->data.event.src_ip||!strcmp(command->data.event.src_ip,"None"))
	{
		g_free(command->data.event.src_ip);
		command->data.event.src_ip = g_strdup(command->data.event.sensor);
	}


  //if (sem_total_events_popped <= 500000)
	event = sim_command_get_event (command); //generates an event from the command received
	if (!event)
  {
		ossim_debug ( "sim_session_cmd_event: Error Event NULL");
		//Continue with the next event
		return;
	}

    // Only special events in our database will be processed.
    if ((sim_event_is_special (event)) &&
        (!sim_context_has_inet (event->context, event->src_ia)))
    {
      sim_event_unref (event);
      return;
    }

  //else
  //  event = sim_command_get_event_demo (command); //generates a demo command

  // Check for sensor or sensor_id
  if ((!event->sensor_id) || (!event->sensor))
  {
    SimSensor *sensor = sim_session_get_sensor (session);
    if (sensor)
    {
      // Free these value "for the flies"
      if (event->sensor)
        g_object_unref (event->sensor);
      if (event->sensor_id)
        g_object_unref (event->sensor_id);

      event->sensor = g_object_ref (sim_sensor_get_ia (sensor));
      event->sensor_id = g_object_ref (sim_sensor_get_id (sensor));
    }
  }

  // v3 agents only send 'sensor' fields. Use them to fill 'device' fields if needed.
  if (!event->device)
    event->device = g_object_ref (event->sensor);

  ossim_debug ( "sim_session_cmd_event: Event address: %p", event);
  ossim_debug ( "sim_session_cmd_event: Event received: %s", event->buffer);

	if (session->_priv->received==G_MAXDOUBLE) 
		session->_priv->received=0;
	session->_priv->received++; 

  ossim_debug ( "sim_session_cmd_event: Inside2");
  if (event->type == SIM_EVENT_TYPE_NONE)
  {
  	ossim_debug ( "Error. sim_session_cmd_event: event type=none");
    sim_event_unref (event);
    return;
  }

	sim_session_prepare_and_insert (session, event);

  return;
}

/*
 *
 *
 *
 */
static void
sim_session_cmd_reload_plugins (SimSession  *session,
																SimCommand  *command)
{
  SimCommand  *cmd;
	GList				*list, *listf;
	gboolean		for_this_server;

  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (SIM_IS_COMMAND (command));

	if (sim_session_is_master_server (session) ||
			sim_session_is_web (session))
	{
    SimServer *server = session->_priv->server;
		
		//Check if the message is for this server....
    if ((!command->data.reload_plugins.servername) || //FIXME: If the server name isn't specified, for backwards compatibility
																													//we will assume that this is the dst server. This should be removed in 
																													//a near future. All the commands from frameworkd should issue a server name.
        (!g_ascii_strcasecmp (sim_server_get_name (server), command->data.reload_plugins.servername)))
    	for_this_server = TRUE;
		else
    	for_this_server = FALSE;
					
		ossim_debug ( "sim_session_cmd_reload_plugins: %s, %s", sim_server_get_name (server), command->data.reload_plugins.servername);
		
		if (for_this_server)	//execute the command in this server
	  {
      //FIXME: Reload context plugins and/or common plugins...?
		  //sim_container_free_plugins (ossim.container);
		  //sim_container_db_load_plugins (ossim.container, ossim.dbossim);
			g_message ("Plugins reloaded");
		}
		else	//resend the command buffer to the children servers whose name match.
		{
			list = sim_server_get_sessions (server);
			listf = list;
		  while (list)	//list of the sessions connected to the server
			{
				SimSession *sess = (SimSession *) list->data;
				if (sim_session_is_children_server (sess) && 
						!g_ascii_strcasecmp (command->data.reload_plugins.servername, sim_session_get_hostname (sess)) )
				{
					sim_session_write_from_buffer (sess, command->buffer);
				}
				list = list->next;
			}
	  	g_list_free (listf);
		}
	
	  cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
		cmd->id = command->id;
	  sim_session_write (session, cmd);
		g_object_unref (cmd);
	}
  else
  {
    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
    cmd->id = command->id;

    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }

}

/*
 *
 *
 *
 */
static void
sim_session_cmd_reload_sensors (SimSession  *session,
																SimCommand  *command)
{
  SimCommand  *cmd;
  GList       *list, *listf;
  gboolean    for_this_server;

  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (SIM_IS_COMMAND (command));

  if (sim_session_is_master_server (session) ||
      sim_session_is_web (session))
  {
    SimServer *server = session->_priv->server;

    //Check if the message is for this server....
    if ((!command->data.reload_sensors.servername) || //FIXME: If the server name isn't specified, for backwards compatibility
        //we will assume that this is the dst server. This should be removed in
        //a near future. All the commands from frameworkd should issue a server name.
        (!g_ascii_strcasecmp (sim_server_get_name (server), command->data.reload_sensors.servername)))
      for_this_server = TRUE;
    else
      for_this_server = FALSE;

    ossim_debug ("%s: %s, %s", __func__,
                 sim_server_get_name (server), command->data.reload_sensors.servername);

    if (for_this_server)  //execute the command in this server
    {
      sim_container_reload_sensors (ossim.container);
      g_message ("Sensors reloaded");
    }
    else  //resend the command buffer to the children servers whose name match.
    {
      list = sim_server_get_sessions (server);
      listf = list;
      while (list)  //list of the sessions connected to the server
      {
        SimSession *sess = (SimSession *) list->data;
        if (sim_session_is_children_server (sess) &&
            !g_ascii_strcasecmp (command->data.reload_sensors.servername, sim_session_get_hostname (sess)) )
        {
          sim_session_write_from_buffer (sess, command->buffer);
        }
        list = list->next;
      }
      g_list_free (listf);
    }

    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
    cmd->id = command->id;
    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }
  else
  {
    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
    cmd->id = command->id;

    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }
}

/*
 *
 *
 *
 */
static void
sim_session_cmd_reload_hosts (SimSession  *session,
                              SimCommand  *command)
{
  SimCommand  *cmd;
  GList       *list, *listf;
  gboolean    for_this_server;

  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (SIM_IS_COMMAND (command));

  if (sim_session_is_master_server (session) ||
      sim_session_is_web (session))
  {
    SimServer *server = session->_priv->server;

    //Check if the message is for this server....
    if ((!command->data.reload_hosts.servername) || //FIXME: If the server name isn't specified, for backwards compatibility
        //we will assume that this is the dst server. This should be removed in
        //a near future. All the commands from frameworkd should issue a server name.
        (!g_ascii_strcasecmp (sim_server_get_name (server), command->data.reload_hosts.servername)))
      for_this_server = TRUE;
    else
      for_this_server = FALSE;

    ossim_debug ("%s: %s, %s", __func__, sim_server_get_name (server), command->data.reload_hosts.servername);

    if (for_this_server)  //execute the command in this server
    {
      SimContext *context = sim_container_get_context (ossim.container, NULL);
      sim_context_reload_hosts (context);
      sim_context_check_host_plugin_sids (context);

      g_message ("Host reloaded");
    }
    else  //resend the command buffer to the children servers whose name match.
    {
      list = sim_server_get_sessions (server);
      listf = list;
      while (list)  //list of the sessions connected to the server
      {
        SimSession *sess = (SimSession *) list->data;
        if (sim_session_is_children_server (sess) &&
            !g_ascii_strcasecmp (command->data.reload_hosts.servername, sim_session_get_hostname (sess)) )
        {
          sim_session_write_from_buffer (sess, command->buffer);
        }
        list = list->next;
      }
      g_list_free (listf);
    }

    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
    cmd->id = command->id;
    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }
  else
  {
    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
    cmd->id = command->id;

    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }
}

/*
 *
 *
 *
 */
static void
sim_session_cmd_reload_nets (SimSession  *session,
                             SimCommand  *command)
{
  SimCommand  *cmd;
  GList       *list, *listf;
  gboolean    for_this_server;

  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (SIM_IS_COMMAND (command));

  if (sim_session_is_master_server (session) ||
      sim_session_is_web (session))
  {
    SimServer *server = session->_priv->server;

    //Check if the message is for this server....
    if ((!command->data.reload_nets.servername) || //FIXME: If the server name isn't specified, for backwards compatibility
        //we will assume that this is the dst server. This should be removed in
        //a near future. All the commands from frameworkd should issue a server name.
        (!g_ascii_strcasecmp (sim_server_get_name (server), command->data.reload_nets.servername)))
      for_this_server = TRUE;
    else
      for_this_server = FALSE;

    ossim_debug ("%s: %s, %s", __func__, sim_server_get_name (server), command->data.reload_nets.servername);

    if (for_this_server)  //execute the command in this server
    {
      SimContext *context = sim_container_get_context (ossim.container, NULL);
      sim_context_reload_nets (context);
      sim_context_check_host_plugin_sids (context);

      g_message ("Nets reloaded");
    }
    else  //resend the command buffer to the children servers whose name match.
    {
      list = sim_server_get_sessions (server);
      listf = list;
      while (list)  //list of the sessions connected to the server
      {
        SimSession *sess = (SimSession *) list->data;
        if (sim_session_is_children_server (sess) &&
            !g_ascii_strcasecmp (command->data.reload_nets.servername, sim_session_get_hostname (sess)) )
        {
          sim_session_write_from_buffer (sess, command->buffer);
        }
        list = list->next;
      }
      g_list_free (listf);
    }

    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
    cmd->id = command->id;
    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }
  else
  {
    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
    cmd->id = command->id;

    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }

}

/*
 *
 *
 *
 */
static void
sim_session_cmd_reload_policies (SimSession  *session,
                                 SimCommand  *command)
{
  SimCommand  *cmd;
  GList       *list, *listf;
  gboolean    for_this_server;

  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (SIM_IS_COMMAND (command));

  if (sim_session_is_master_server (session) ||
      sim_session_is_web (session))
  {
    SimServer *server = session->_priv->server;

    //Check if the message is for this server....
    if ((!command->data.reload_policies.servername) || //FIXME: If the server name isn't specified, for backwards compatibility
        //we will assume that this is the dst server. This should be removed in
        //a near future. All the commands from frameworkd should issue a server name.
        (!g_ascii_strcasecmp (sim_server_get_name (server), command->data.reload_policies.servername)))
      for_this_server = TRUE;
    else
      for_this_server = FALSE;

    ossim_debug ("sim_session_cmd_reload_policies: %s, %s", sim_server_get_name (server), command->data.reload_policies.servername);

    if (for_this_server)  //execute the command in this server
    {
      SimContext *context = sim_container_get_context (ossim.container, NULL);
      sim_context_reload_policies (context);

			context = sim_container_get_engine_ctx (ossim.container);
			sim_context_reload_policies (context);
			g_object_unref (context);
    }
    else  //resend the command buffer to the children servers whose name match.
    {
      list = sim_server_get_sessions (server);
      listf = list;
      while (list)  //list of the sessions connected to the server
      {
        SimSession *sess = (SimSession *) list->data;
        if (sim_session_is_children_server (sess) &&
            !g_ascii_strcasecmp (command->data.reload_policies.servername, sim_session_get_hostname (sess)) )
        {
          sim_session_write_from_buffer (sess, command->buffer);
        }
        list = list->next;
      }
      g_list_free (listf);
    }

    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
    cmd->id = command->id;
    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }
  else
  {
    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
    cmd->id = command->id;

    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }

}

/*
 *
 *
 *
 */
static void
sim_session_cmd_reload_directives (SimSession  *session,
                                   SimCommand  *command)
{
  SimCommand  *cmd;
  GList       *list, *listf;
  gboolean    for_this_server;

  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (SIM_IS_COMMAND (command));

  if (sim_session_is_master_server (session) ||
      sim_session_is_web (session))
  {
    SimServer *server = session->_priv->server;

    //Check if the message is for this server....
    if ((!command->data.reload_directives.servername) || //FIXME: If the server name isn't specified, for backwards compatibility
        //we will assume that this is the dst server. This should be removed in
        //a near future. All the commands from frameworkd should issue a server name.
        (!g_ascii_strcasecmp (sim_server_get_name (server), command->data.reload_directives.servername)))
      for_this_server = TRUE;
    else
      for_this_server = FALSE;

    ossim_debug ( "sim_session_cmd_reload_directives: %s, %s", sim_server_get_name (server), command->data.reload_directives.servername);

    if (for_this_server)  //execute the command in this server
    {
      SimEngine *engine = sim_container_get_engine (ossim.container, NULL);

// Directives are replaced in db if they exist !
//      sim_container_db_delete_plugin_sid_directive_ul (ossim.container, ossim.dbossim);

//      sim_container_db_delete_backlogs_ul (ossim.container, ossim.dbossim);

      sim_engine_free_backlogs (engine);

      sim_engine_reload_directives (engine);
    }
    else  //resend the command buffer to the children servers whose name match.
    {
      list = sim_server_get_sessions (server);
      listf = list;
      while (list)  //list of the sessions connected to the server
      {
        SimSession *sess = (SimSession *) list->data;
        if (sim_session_is_children_server (sess) &&
            !g_ascii_strcasecmp (command->data.reload_directives.servername, sim_session_get_hostname (sess)) )
        {
          sim_session_write_from_buffer (sess, command->buffer);
        }
        list = list->next;
      }
      g_list_free (listf);
    }

    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
    cmd->id = command->id;
    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }
  else
  {
    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
    cmd->id = command->id;

    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }

}

/*
 *
 *
 *
 */
static void
sim_session_cmd_reload_servers (SimSession *session,
                                SimCommand *command)
{
  SimCommand  *cmd;
  gboolean    for_this_server;

  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (SIM_IS_COMMAND (command));

  if (sim_session_is_master_server (session) ||
      sim_session_is_web (session))
  {
    SimServer *server = session->_priv->server;

    //Check if the message is for this server....
    if ((!command->data.reload_servers.servername) ||
        (!g_ascii_strcasecmp (sim_server_get_name (server), command->data.reload_servers.servername)))
    {
      for_this_server = TRUE;
    }
    else
    {
      for_this_server = FALSE;
    }

    ossim_debug ("%s: %s, %s", __func__, sim_server_get_name (server), command->data.reload_servers.servername);

    if (for_this_server)  //execute the command in this server
    {
      /* Reload servers */
      sim_container_reload_servers (ossim.container, ossim.dbossim);
      g_message ("Server reloaded");
    }
    else  // Do nothing
    {
      return;
    }

    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
    cmd->id = command->id;
    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }
  else
  {
    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
    cmd->id = command->id;

    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }
}

/*
 *
 *
 *
 */
static void
sim_session_cmd_reload_all (SimSession  *session,
                            SimCommand  *command)
{
  SimCommand  *cmd;
  GList       *list, *listf;
  gboolean    for_this_server;

  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

  if (sim_session_is_master_server (session) ||
      sim_session_is_web (session))
  {
    SimServer *server = session->_priv->server;

    //Check if the message is for this server....
    if ((!command->data.reload_all.servername) || //FIXME: If the server name isn't specified, for backwards compatibility
                                                  //we will assume that this is the dst server. This should be removed in
                                                  //a near future. All the commands from frameworkd should issue a server name.
        (!g_ascii_strcasecmp (sim_server_get_name (server), command->data.reload_all.servername)))
      for_this_server = TRUE;
    else
      for_this_server = FALSE;

    ossim_debug ( "sim_session_cmd_reload_all: %s, %s", sim_server_get_name (server), command->data.reload_all.servername);

    if (for_this_server)  //execute the command in this server
    {
      SimContext *context = sim_container_get_context (ossim.container, NULL);
      SimEngine *engine = sim_container_get_engine_for_context (ossim.container, NULL);

      //FIXME: Reload context plugins and/or common plugins...?
      // sim_container_free_plugin_sids (ossim.container);
      // sim_container_free_plugins (ossim.container);

      sim_engine_free_backlogs (engine);

      // sim_container_db_load_plugins (ossim.container, ossim.dbossim);
      // sim_container_db_load_plugin_sids (ossim.container, ossim.dbossim);

      /* Reload all data in context */
      sim_engine_reload_all (engine);
      sim_context_reload_all (context);

      sim_server_reload (session->_priv->server, context);
    }
    else  //resend the command buffer to the children servers whose name match.
    {
      list = sim_server_get_sessions (server);
      listf = list;
      while (list)  //list of the sessions connected to the server
      {
        SimSession *sess = (SimSession *) list->data;
        if (sim_session_is_children_server (sess) &&
            !g_ascii_strcasecmp (command->data.reload_all.servername, sim_session_get_hostname (sess)) )
        {
          sim_session_write_from_buffer (sess, command->buffer);
        }
        list = list->next;
      }
      g_list_free (listf);
    }

    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_OK);
    cmd->id = command->id;
    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }
  else
  {
    cmd = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
    cmd->id = command->id;

    sim_session_write (session, cmd);
    g_object_unref (cmd);
  }
}

/*
 *	This function stores the following:
 *	Userdata1: OS
 *
 */
void
sim_session_cmd_host_os_event (SimSession  *session,
                               SimCommand  *command)
{
  SimEvent  *event;
  SimInet   *ia     = NULL;
  struct tm  tm;

  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (SIM_IS_COMMAND (command));
  g_return_if_fail (command->data.host_os_event.date);
  g_return_if_fail (command->data.host_os_event.host);
  g_return_if_fail (command->data.host_os_event.os);
  g_return_if_fail (command->data.host_os_event.interface);
  g_return_if_fail (command->data.host_os_event.plugin_id > 0);
  g_return_if_fail (command->data.host_os_event.plugin_sid > 0);

  if ((ia = sim_inet_new_from_string (command->data.host_os_event.host)))
  {
    event = sim_event_new_full (SIM_EVENT_TYPE_DETECTOR, command->data.host_os_event.id, command->context_id);

    // We only want first word (OS name)
    if (command->data.host_os_event.os)
    {
      gchar **os_event_split = NULL;
      os_event_split = g_strsplit (command->data.host_os_event.os, " ", 2);
      g_free (command->data.host_os_event.os);
      command->data.host_os_event.os = g_strdup (os_event_split?os_event_split[0]:NULL);
      g_strfreev(os_event_split);
    }

    event->alarm = FALSE;
    event->protocol = SIM_PROTOCOL_TYPE_HOST_OS_EVENT;
    event->plugin_id = command->data.host_os_event.plugin_id;
    event->plugin_sid = command->data.host_os_event.plugin_sid;

    if (!event->sensor_id)
    {
      SimSensor *sensor = sim_session_get_sensor (session);
      if (sensor)
      {
        if (event->sensor)
          g_object_unref (event->sensor);
        if (event->sensor_id)
          g_object_unref (event->sensor_id);

        event->sensor = g_object_ref (sim_sensor_get_ia (sensor));
        event->sensor_id = g_object_ref (sim_sensor_get_id (sensor));
      }
    }

    if (command->data.host_os_event.server)
      event->server = g_strdup (command->data.host_os_event.server);

    event->interface = g_strdup (command->data.host_os_event.interface);
    if(command->data.host_os_event.date)
      event->time=command->data.host_os_event.date;
    else
      if(command->data.host_os_event.date_str)
        if (strptime (command->data.host_os_event.date_str, "%Y-%m-%d %H:%M:%S", &tm))
          event->time =  mktime (&tm);

    if(!event->time)
      event->time = time (NULL);

    gchar *ip_temp = sim_inet_get_canonical_name (ia);
    if (ip_temp)
    {
      event->src_ia = ia;
      g_free (ip_temp);
    }
    else
      event->src_ia = sim_inet_new_none ();

    //we want to process only the hosts defined in Policy->hosts or inside a network from policy->networks
    if (!sim_context_has_inet (event->context, event->src_ia))
      return;

    event->dst_ia = sim_inet_new_none ();

    // Assign asset values.
    if (event->asset_src == VOID_ASSET)
    {
      event->asset_src = DEFAULT_ASSET;
      if (event->src_id)
        event->asset_src = sim_context_get_host_asset (event->context, event->src_id);
      else
        if (event->src_ia)
          event->asset_src = sim_context_get_inet_asset (event->context, event->src_ia);
    }
    if (event->asset_dst == VOID_ASSET)
    {
      event->asset_dst = DEFAULT_ASSET;
      if (event->dst_id)
        event->asset_dst = sim_context_get_host_asset (event->context, event->dst_id);
      else
        if (event->dst_ia)
          event->asset_dst = sim_context_get_inet_asset (event->context, event->dst_ia);
    }

    event->data = g_strdup (command->data.host_os_event.log);

    //this is used to pass the event data to sim-organizer, so it can insert it into database
    event->userdata1 = g_strdup (command->data.host_os_event.os); //needed for correlation

    event->buffer = g_strdup (command->buffer); //we need this to resend data to other servers, or to send
                                                //events that matched with policy to frameworkd (future implementation)
                                                //

    event->tzone = command->data.host_os_event.tzone;

    if (session->_priv->received == G_MAXDOUBLE)
      session->_priv->received = 0;
    session->_priv->received++;

    sim_session_prepare_and_insert (session, event);
  }
  else
    g_message("Error: Data sent from agent; host OS event wrong src IP %s",command->data.host_os_event.host);

}

/*
 *	This function also stores the following:
 *	Userdata1: MAC
 *	Userdata2: Vendor
 *
 */
static void
sim_session_cmd_host_mac_event (SimSession  *session,
                                SimCommand  *command)
{
  SimEvent   *event;
  SimInet    *ia     = NULL;
  struct tm   tm;

  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (SIM_IS_COMMAND (command));
  g_return_if_fail (command->data.host_mac_event.date);
  g_return_if_fail (command->data.host_mac_event.host);
  g_return_if_fail (command->data.host_mac_event.mac);
  g_return_if_fail (command->data.host_mac_event.vendor);
  g_return_if_fail (command->data.host_mac_event.plugin_id > 0);
  g_return_if_fail (command->data.host_mac_event.plugin_sid > 0);

  // Normalize MAC address (usefull for comparaisons)
  gchar *aux = sim_normalize_host_mac (command->data.host_mac_event.mac);
  if (aux == NULL)
    return;
  g_free(command->data.host_mac_event.mac);
  command->data.host_mac_event.mac = aux;

  ossim_debug ( "sim_session_cmd_host_mac_event: command->data.host_mac_event.mac: %s",command->data.host_mac_event.mac);

  if ((ia = sim_inet_new_from_string (command->data.host_mac_event.host)))
  {
    event = sim_event_new_full (SIM_EVENT_TYPE_DETECTOR, command->data.host_mac_event.id, command->context_id);

    event->alarm = FALSE;
    event->plugin_id = command->data.host_mac_event.plugin_id;
    event->plugin_sid = command->data.host_mac_event.plugin_sid;
    event->protocol=SIM_PROTOCOL_TYPE_HOST_ARP_EVENT;

    if (!event->sensor_id)
    {
      SimSensor *sensor = sim_session_get_sensor (session);
      if (sensor)
      {
        if (event->sensor)
          g_object_unref (event->sensor);
        if (event->sensor_id)
          g_object_unref (event->sensor_id);

        event->sensor = g_object_ref (sim_sensor_get_ia (sensor));
        event->sensor_id = g_object_ref (sim_sensor_get_id (sensor));
      }
    }

    if(event->interface)
      event->interface = g_strdup (command->data.host_mac_event.interface);

    if(command->data.host_mac_event.date)
      event->time=command->data.host_mac_event.date;
    else
      if(command->data.host_mac_event.date_str)
        if (strptime (command->data.host_mac_event.date_str, "%Y-%m-%d %H:%M:%S", &tm))
          event->time =  mktime (&tm);

    if(!event->time)
      event->time = time (NULL);

    gchar *ip_temp = sim_inet_get_canonical_name (ia);
    if (ip_temp)
    {
      event->src_ia = ia;
      g_free(ip_temp);
    }
    else
    {
      event->src_ia = sim_inet_new_none ();
      g_message("Error: Data sent from agent; host MAC event wrong IP %s",command->data.host_mac_event.host);
    }

    event->dst_ia = sim_inet_new_none ();
    event->data = g_strdup (command->data.host_mac_event.log);
    event->buffer = g_strdup (command->buffer); //we need this to resend data to other servers, or to send
                                                //events that matched with policy to frameworkd (future implementation)

    // Assign asset values.
    if (event->asset_src == VOID_ASSET)
    {
      event->asset_src = DEFAULT_ASSET;
      if (event->src_id)
        event->asset_src = sim_context_get_host_asset (event->context, event->src_id);
      else
        if (event->src_ia)
          event->asset_src = sim_context_get_inet_asset (event->context, event->src_ia);
    }
    if (event->asset_dst == VOID_ASSET)
    {
      event->asset_dst = DEFAULT_ASSET;
      if (event->dst_id)
        event->asset_dst = sim_context_get_host_asset (event->context, event->dst_id);
      else
        if (event->dst_ia)
          event->asset_dst = sim_context_get_inet_asset (event->context, event->dst_ia);
    }

    event->userdata1 = g_strdup (command->data.host_mac_event.mac); //needed for correlation
    if (command->data.host_mac_event.vendor)
      event->userdata2 = g_strdup (command->data.host_mac_event.vendor);

    event->tzone = command->data.host_mac_event.tzone;

    if (session->_priv->received==G_MAXDOUBLE)
      session->_priv->received=0;
    session->_priv->received++;
    sim_session_prepare_and_insert (session, event);

    ossim_debug ( "sim_session_cmd_host_mac_event: TYPE: %d",event->plugin_sid);
  }
  else
    g_message("Error: Data sent from agent; host MAC event wrong IP %s",command->data.host_mac_event.host);

  ossim_debug ( "command->data.host_mac_event.date: %s",command->data.host_mac_event.date_str);
}

/*
 * PADS plugin (or redirect to MAC plugin)
 * This function also stores the following:
 * userdata1: application
 * userdata2: service
 *
 */
static void
sim_session_cmd_host_service_event (SimSession  *session,
                                    SimCommand  *command)
{
  SimEvent    *event;
  SimInet     *ia                      = NULL;
  struct tm    tm;

  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (SIM_IS_COMMAND (command));
  g_return_if_fail (command->data.host_service_event.date);
  g_return_if_fail (command->data.host_service_event.host);
  g_return_if_fail (command->data.host_service_event.service);
  g_return_if_fail (command->data.host_service_event.interface);
  g_return_if_fail (command->data.host_service_event.plugin_id > 0);
  g_return_if_fail (command->data.host_service_event.plugin_sid > 0);

  if (!command->data.host_service_event.application)
  {
    ossim_debug ( "sim_session_cmd_host_service_event: empty field \"application\".");
  }

  // We don't use icmp. Maybe useful for a list of active hosts....
  if (command->data.host_service_event.protocol == 1)
    return;

  if ((ia = sim_inet_new_from_string (command->data.host_service_event.host)))
  {
    event = sim_event_new_full (SIM_EVENT_TYPE_DETECTOR, command->data.host_service_event.id, command->context_id);

    if (!event->sensor_id)
    {
      SimSensor *sensor = sim_session_get_sensor (session);
      if (sensor)
      {
        if (event->sensor)
          g_object_unref (event->sensor);
        if (event->sensor_id)
          g_object_unref (event->sensor_id);

        event->sensor = g_object_ref (sim_sensor_get_ia (sensor));
        event->sensor_id = g_object_ref (sim_sensor_get_id (sensor));
      }
    }

    if (command->data.host_service_event.server)
      event->server = g_strdup (command->data.host_service_event.server);

    event->alarm = FALSE;
    event->plugin_id = command->data.host_service_event.plugin_id;
    event->plugin_sid = command->data.host_service_event.plugin_sid;
    event->protocol = SIM_PROTOCOL_TYPE_HOST_SERVICE_EVENT;

    event->interface = g_strdup (command->data.host_service_event.interface);

    if (command->data.host_service_event.date)
    {
      event->time=command->data.host_service_event.date;
    }
    else
    {
      if (command->data.host_service_event.date_str)
      {
        if (strptime (command->data.host_service_event.date_str, "%Y-%m-%d %H:%M:%S", &tm))
          event->time =  mktime (&tm);
      }
    }

    if(!event->time)
      event->time = time (NULL);

    gchar *ip_temp = sim_inet_get_canonical_name (ia);
    if (ip_temp)
    {
      event->src_ia = ia;
      g_free (ip_temp);
    }
    else
    {
      event->src_ia = sim_inet_new_none ();
      g_message("Error: Data sent from agent; host Service event wrong IP %s",command->data.host_service_event.host);
    }

    //we want to process only the hosts defined in Policy->hosts or inside a network from policy->networks
    if (!sim_context_has_inet (event->context, event->src_ia))
      return;

    event->dst_ia = sim_inet_new_none ();

    // Assign asset values.
    if (event->asset_src == VOID_ASSET)
    {
      event->asset_src = DEFAULT_ASSET;
      if (event->src_id)
        event->asset_src = sim_context_get_host_asset (event->context, event->src_id);
      else
        if (event->src_ia)
          event->asset_src = sim_context_get_inet_asset (event->context, event->src_ia);
    }
    if (event->asset_dst == VOID_ASSET)
    {
      event->asset_dst = DEFAULT_ASSET;
      if (event->dst_id)
        event->asset_dst = sim_context_get_host_asset (event->context, event->dst_id);
      else
        if (event->dst_ia)
          event->asset_dst = sim_context_get_inet_asset (event->context, event->dst_ia);
    }

    event->data = g_strdup (command->data.host_service_event.log);

    //this is used to pass the event data to sim-organizer, so it can insert it into database
    event->userdata1 = g_strdup (command->data.host_service_event.application); //may be needed in correlation
    event->userdata2 = g_strdup (command->data.host_service_event.service);
    event->userdata3 = g_strdup_printf ("%d", command->data.host_service_event.port);
    event->userdata4 = g_strdup_printf ("%d", command->data.host_service_event.protocol);

    event->buffer = g_strdup (command->buffer); //we need this to resend data to other servers, or to send
                                                  //events that matched with policy to frameworkd (future implementation)

    event->tzone = command->data.host_service_event.tzone;

    if (session->_priv->received==G_MAXDOUBLE) 
		  session->_priv->received=0;
  	session->_priv->received++;

    sim_session_prepare_and_insert (session, event);
  }
  else
  {
    g_message("Error: Data sent from agent; host MAC or OS event wrong IP %s",command->data.host_service_event.host);
  }
}

/*
 *
 *
 *
 */
static void
sim_session_cmd_ok (SimSession  *session,
								    SimCommand  *command)
{
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (SIM_IS_COMMAND (command));

}

/*
 *
 *
 *
 */
static void
sim_session_cmd_error (SimSession  *session,
		       SimCommand  *command)
{
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (SIM_IS_COMMAND (command));
}

static void
sim_session_cmd_pong (SimSession  *session,
										  SimCommand  *command)
{
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (SIM_IS_COMMAND (command));
}

/*
 *
 * @param session: SimSession* object representing the session
 *      where the command is received.
 * @param commnad: SimCommand* object to build.
 * Gets framework connetion data and fills the command
 * with this data.
 */
static void sim_session_cmd_session_get_framework(SimSession *session, SimCommand *command)
{
  SimCommand *cmd;

  g_return_if_fail(session != NULL);
  g_return_if_fail(SIM_IS_SESSION (session));
  g_return_if_fail(command != NULL);
  g_return_if_fail(SIM_IS_COMMAND (command));

  cmd = sim_command_new_from_type(SIM_COMMAND_TYPE_SENSOR_GET_FRAMEWORK);
  cmd->data.framework_data.framework_host = g_strdup(
      session->_priv->config->framework.host);
  cmd->data.framework_data.framework_name = g_strdup(
      session->_priv->config->framework.name);
  cmd->data.framework_data.framework_port
      = session->_priv->config->framework.port;
  cmd->data.framework_data.server_name = g_strdup(
      session->_priv->config->server.name);
  cmd->data.framework_data.server_host = g_strdup(session->_priv->ip_str);
  cmd->data.framework_data.server_port = session->_priv->config->server.port;
//  gchar* str = sim_command_get_string(cmd);
//  ossim_debug ("sim_session_cmd_session_get_framework Command: %s",str);
//  g_free(str);
  sim_session_write(session, cmd);
  g_object_unref(cmd);
}
/*
 *
 * @param session: SimSession* object representing the session
 *      where the command is received.
 * @param commnad: SimCommand* object to build.
 * ping response.
 */
static void sim_session_cmd_agent_ping(SimSession *session, SimCommand *command)
{
  SimCommand *cmd;
  g_return_if_fail(session != NULL);
  g_return_if_fail(SIM_IS_SESSION (session));
  g_return_if_fail(command != NULL);
  g_return_if_fail(SIM_IS_COMMAND (command));
  cmd = sim_command_new_from_type(SIM_COMMAND_TYPE_AGENT_PING);
  sim_session_write(session, cmd);
  g_object_unref(cmd);
}

/*
 *
 * @param session: SimSession* object representing the session
 *      where the command is received.
 * @param commnad: SimCommand* object to build.
 * Stores IDM event in the data store.
 */
static void sim_session_cmd_idm_event(SimSession *session, SimCommand *command)
{
  g_return_if_fail(SIM_IS_SESSION (session));
  g_return_if_fail(SIM_IS_COMMAND (command));

  if (command->data.idm_event.mac) // Normalize MAC address (usefull for comparaisons)
  {
    gchar *aux = sim_normalize_host_mac (command->data.idm_event.mac);
    if (aux == NULL)
		{
			g_message ("Discarding idm event with bad mac address: %s", command->data.idm_event.mac);
      return;
		}
    g_free(command->data.idm_event.mac);
    command->data.idm_event.mac = aux;
  }

  sim_idm_put (sim_session_get_sensor (session), command);
}

/*
 *
 * @param session: SimSession* object representing the session
 *      where the command is received.
 * @param commnad: SimCommand* object to build.
 * Gets snort database connection data and fills the command
 * with this data.
 */
static void sim_session_cmd_session_get_frmk_getdb(SimSession *session, SimCommand *command)
{
  SimCommand *cmd;
  g_return_if_fail(session != NULL);
  g_return_if_fail(SIM_IS_SESSION (session));
  g_return_if_fail(command != NULL);
  g_return_if_fail(SIM_IS_COMMAND (command));

  /*
   * ossim.dbsnort->_priv->dsn -> readed from xml config file:
   * example:
   * dsn="PORT=3306;USER=root;PASSWORD=passwd;DATABASE=snort;HOST=localhost"
   */

  gchar *dnsstr = NULL;
  cmd = sim_command_new_from_type(SIM_COMMAND_TYPE_FRMK_GETDB);
  dnsstr = g_strdup( sim_database_get_dsn(ossim.dbsnort));

  if(sim_util_parse_snort_db_dns(cmd, dnsstr))
    {
      g_free(dnsstr);
      //Now, we send the response to the framework
      sim_session_write(session, cmd);
    }
  else
    {
      g_message("Error: sim_session_cmd_session_get_frmk_getdb - parse dns error! ");
    }

  g_free (dnsstr);
  g_object_unref(cmd);

}

/*
 * Reload and insert the post correlation sids into DB
 */
static void
sim_session_cmd_reload_post_correlation_sids (SimSession *session, SimCommand *command)
{
  g_return_if_fail(SIM_IS_SESSION (session));
  g_return_if_fail(SIM_IS_COMMAND (command));

  gchar **values;
  gchar **values2;
  gint i;
  SimPluginSid *plugin_sid = NULL;
  SimContext *context;

  context = sim_container_get_context (ossim.container, NULL);

  if (command->data.reload_post_correlation_sids.sids)
  {
    /* separate each of the individual plugin_sid chunk (id:name:priority:reliability) delimited with ";" */
    values = g_strsplit (command->data.reload_post_correlation_sids.sids, SIM_DELIMITER_ELEMENT_LIST, 0);
    for (i = 0; values[i] != NULL; i++)
    {
      values2 = g_strsplit (values[i],SIM_DELIMITER_LEVEL,0);

      if ((!sim_string_is_number (values2[0], 0)) ||
          (!sim_string_is_number (values2[2], 0)) ||
          (!sim_string_is_number (values2[3], 0)))
      {
        g_message("Error: sid number incorrect. Please check the post correlation sids issued by frameworkd: %s", values[i]);
        g_strfreev (values);
        g_strfreev (values2);
        return;
      }

      //sim_rule_add_plugin_sid_not (rule, atoi(values[i]));
      plugin_sid = sim_plugin_sid_new_from_data (SIM_PLUGIN_ID_POST_CORRELATION,
                                                 atoi (values2[0]),
                                                 atoi (values2[3]),
                                                 atoi (values2[2]),
                                                 values2[1]);

      sim_db_insert_plugin_sid (ossim.dbossim, plugin_sid, command->context_id);

      sim_context_add_plugin_sid (context, plugin_sid);

      g_strfreev (values2);
    }

    g_strfreev (values);
  }
}

GIOStatus sim_session_read_event(SimSession *session, gchar *buffer, gsize *n)  //n = received bytes
{
  GIOStatus status=G_IO_STATUS_NORMAL;

  gsize     event_len = 0;
  gboolean  got_event = FALSE;
	GError		*gerror = NULL;

	ossim_debug ( "sim_session_read_event: Entering Main Loop. Session: %p", session);

	while(event_len < BUFFER_SIZE - 2 && !got_event && session->_priv->ptr < RING_SIZE)
	{
		//ossim_debug ( "sim_session_read_event: Iteration %d. Session: %x", iteration, session);
		if( session->_priv->cur_len>0 && session->_priv->ptr < session->_priv->cur_len && session->_priv->ptr < RING_SIZE)
		{
			//ossim_debug ( "sim_session_read_event: Reading from cache. Session: %x", session);
			buffer[event_len++] = session->_priv->ring[session->_priv->ptr++];

			if(buffer[event_len-1]=='\n')
			{
				ossim_debug ( "sim_session_read_event: returning one event (ok). Session: %p", session);
				buffer[event_len]='\n';
				buffer[event_len+1]='\0';
				got_event=TRUE;
			}
		}
		else
		{
			session->_priv->ptr=0;
			gerror = NULL;
			status= g_io_channel_read_chars(session->_priv->io, session->_priv->ring, RING_SIZE - 1, &session->_priv->cur_len, &gerror);
			if(gerror && status==G_IO_STATUS_ERROR)
			{
				switch(gerror->code)
				{
					case G_IO_CHANNEL_ERROR_INVAL:
					case G_IO_CHANNEL_ERROR_IO:
					case G_IO_CHANNEL_ERROR_NOSPC:
					case G_IO_CHANNEL_ERROR_OVERFLOW:
					case G_IO_CHANNEL_ERROR_PIPE:
					case G_IO_CHANNEL_ERROR_FAILED:
						g_message ("%s: %s", __func__, gerror->message);
						g_error_free (gerror);
						return status;

					case G_IO_CHANNEL_ERROR_FBIG:
						g_message("sim_session_read_event: File too large.");
						g_error_free (gerror);
						break;

					case G_IO_CHANNEL_ERROR_ISDIR:
						g_message("sim_session_read_event: File is a directory.");
						g_error_free (gerror);
						break;

					case G_IO_CHANNEL_ERROR_NXIO:
						g_message("sim_session_read_event: No such device or address.");
						g_error_free (gerror);
						break;

					default:
						if (gerror->message)
							g_message("%s: %s", __func__, gerror->message);
						else
							g_message("%s: Unknown socket error", __func__);
						g_error_free (gerror);
						return status;
				}

        ossim_debug ("%s: Error from gnet in g_io_channel_read_chars. "
                     "Check your networking configuration. Session: %lx",
                     __func__, (glong)session);
			}
			else if (status==G_IO_STATUS_ERROR)
			{
				ossim_debug ("%s: Error from gnet in g_io_channel_read_chars. "
                     "Check your networking configuration. Session: %lx",
                     __func__, (glong)session);
			}
			else
			{
				if(status==G_IO_STATUS_EOF&&session->_priv->cur_len<=0)
				{
					ossim_debug ( "sim_session_read_event: EOF received in Session: %p; Closing connection.", session);
					session->_priv->close=TRUE;
					return status;
				}

				if(session->_priv->cur_len<=0)
				{
					ossim_debug ( "sim_session_read_event: no data available from gnet. Cur_len %zu Session: %p;", session->_priv->cur_len, session);
					session->_priv->cur_len=0;
					*n=0;
				}
				else
					ossim_debug ( "sim_session_read_event: %zu bytes readed from gnet. Session: %p;", session->_priv->cur_len, session);
			}
		}
	}

	//"Converting" offset as number of bytes (it will be returned and must be == strlen(buffer)
	*n=++event_len;

	//If we do not have an event, clear the event buffer (to prevent corrupted data..)
	if(!got_event&& status==G_IO_STATUS_NORMAL)
	{
		ossim_debug ( "sim_session_read_event: Clearing the buffer. Session: %p", session);
		memset(buffer, 0, BUFFER_SIZE);
	}

	ossim_debug ( "event(%zu)[status:%d]: %s ",*n,status,buffer);

	return status;
}


/*
 * This function set to 0 the event count of this specific sensor. This will be updated by any sensor instances sensor with the same IP (so, they're the same sensor)
 */
void
sim_session_initialize_count (SimSession  *session)
{
  g_return_if_fail (SIM_IS_SESSION (session));
  g_hash_table_replace (sim_server_get_individual_sensors (session->_priv->server), g_object_ref (sim_session_get_ia (session)), GINT_TO_POINTER (0));
}

/*
 * This function increases the event count of this specific sensor. This is shared by all the sensor threads (it depends on the IP)
 */
void
sim_session_increase_count (SimSession  *session)
{
  g_return_if_fail (SIM_IS_SESSION (session));

  gpointer aux = g_hash_table_lookup (sim_server_get_individual_sensors (session->_priv->server), sim_session_get_ia (session));
  guint aux2 = GPOINTER_TO_INT (aux);
  aux2++;
  g_hash_table_replace	(sim_server_get_individual_sensors (session->_priv->server), g_object_ref (sim_session_get_ia (session)), GINT_TO_POINTER (aux2));
}

gboolean
sim_session_check_iochannel_status (SimSession  * session, GIOStatus status)
{

  g_return_val_if_fail (session != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_SESSION (session), FALSE);

    //sanity checks...
    if (status != G_IO_STATUS_NORMAL)
    {
      switch(status)
  		{
		    case   G_IO_STATUS_AGAIN:
				      g_message ("sim_session_check_iochannel_status: Socket temporarily unavailable in session %p ", session);
				      sleep(1);
							return TRUE;
							break; //not needed hehe O:)
		    case  G_IO_STATUS_EOF:
    				  g_message ("sim_session_check_iochannel_status: socket closed in session %p: Stopping session", session);
				      return FALSE;
							break;
    		case  G_IO_STATUS_ERROR:
				      g_message ("sim_session_check_iochannel_status: read operation failed. Socket disconnected or timed out%p: Stopping session", session);
				      return FALSE;
							break;
		    default:
    				  g_message("sim_session_check_iochannel_status: Unknown state %d", status);
				      return FALSE;
		  }	
    }
		return TRUE;
}

/*
 *
 *
 *
 */
gboolean
sim_session_read (SimSession  *session)
{
  SimCommand  *cmd = NULL;
  SimCommand  *res;
  GIOStatus	status=G_IO_STATUS_AGAIN;
  gchar        buffer[BUFFER_SIZE+1];
  gsize		     n;
  gchar 			*buf;
  gchar       *session_ip_str;
  gboolean  break_conn;

  session->_priv->received = 0;  //set the initial number of received events to 0

  g_return_val_if_fail (session != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_SESSION (session), FALSE);

  

  //Let's ensure that the buffer is really clean before reading.. (this shouldn't be needed..)
  while ((memset(buffer, 0, BUFFER_SIZE+1)||1)&& (!session->_priv->close) && status!=G_IO_STATUS_EOF && (status= sim_session_read_event(session, buffer, &n)) == G_IO_STATUS_NORMAL&& (n>0) && (!session->_priv->close) )
  {
		ossim_debug ( "sim_session_read: Session : Entering while. Session %p", session);
		ossim_debug ( "sim_session_read: strlen(buffer)=%zu; n=%zu", strlen(buffer),n);
		ossim_debug ( "sim_session_read: Session (%p): Buffer: %s", session, buffer);
		
    aux_recvd_msgs++;

    //sanity checks...
    if (status != G_IO_STATUS_NORMAL)
		{
			break_conn = sim_session_check_iochannel_status (session, status);
			if (break_conn)
				continue;
			else
				return FALSE;
		}
		
		//FIXME: This not a OSSIM fixme, IMHO this is a GLib fixme. If strlen(buffer) > n, gscanner will crash
		//This can be easily reproduced commenting the "if" below, and doing a telnet to the server port, and sending one event. After that, do
		//a CTRL-C, and a quit. Next event will crash the server, and gdb will show:
		//(gdb) bt
		//#0  0xb7d8765e in g_scanner_scope_add_symbol () from /usr/lib/libglib-2.0.so.0
		//#1  0xb7d88a52 in g_scanner_get_next_token () from /usr/lib/libglib-2.0.so.0
		//#2  0x0807e840 in sim_command_scan (command=0x8397980,
		//Also, scanner->buffer is not 0 in the next iteration. If we set it to 0, it still crashes.
		//I'll be very glad is someone has some time to check what's happening) :)
		if (strlen(buffer) != n)
		{
		  g_message ("Received error. Inconsistent data entry, closing socket. Received:%zu Buffer lenght: %zu", n, strlen(buffer));
	  	return FALSE;
		}

    if (buffer == NULL)
		{
    	ossim_debug ( "sim_session_read: Buffer NULL");
			return FALSE;
		}

		//FIXME: WHY the F*CK this happens?? strlen(buffer) sometimes is =1!!!
		//g_message("Data received: -%s- Count: %d  n: %d",buffer,sim_strnlen(buffer,BUFFER_SIZE),n);	 
		if (strlen (buffer) <= 2) 
		{
	    ossim_debug ( "sim_session_read: Buffer <= 2 bytes");
			memset(buffer, 0, BUFFER_SIZE);
			continue;
		}
		
	  ossim_debug ( "sim_session_read: buffer address before:%s", buffer);
		buf = sim_buffer_sanitize (buffer);		
		
	  ossim_debug ( "sim_session_read: buffer address after:%s", buffer);
      session_ip_str = sim_inet_get_canonical_name (sim_session_get_ia (session));
      cmd = sim_command_new_from_buffer (buf, sim_session_get_event_scan_fn (session), session_ip_str); //this gets the command and all of the parameters associated.
      g_free (session_ip_str);
      g_free(buf);

		if (!cmd)
		{
		  g_message ("sim_session_read: error command null. Buffer: %s", buffer);
			continue;
		}
    ossim_debug ( "sim_session_read: Command from buffer type:%d ; id=%d",cmd->type,cmd->id);

		if (session->_priv->sensor
				&& sim_sensor_get_name (session->_priv->sensor)
				&& ! strcmp (sim_sensor_get_name (session->_priv->sensor), "(null)"))
		{
			g_object_unref (cmd);
			continue;
		}
      
    if (cmd->type == SIM_COMMAND_TYPE_NONE)
		{
	  	ossim_debug ( "sim_session_read: error command type none");
		  g_object_unref (cmd);
			return FALSE;
		}

		if (sim_session_get_is_initial (session))		//is this the session started in sim_container_new();?
		{
			ossim_debug ( "sim_session_read: This is a initial session load");
			if	(cmd->type == SIM_COMMAND_TYPE_OK)	// 
			{ 
				ossim_debug ( "sim_session_read: Mutex lock in OK");
				//this will permit to load data the first time the server gets data from rservers.
				//Take a look at sim-container
				if (session->_priv->fully_stablished == FALSE)	//we only need to do the mutex the first time, when we are not sure that the
					sim_session_set_fully_stablished (session);		//connection is open
				
				ossim_debug ( "sim_session_read: Mutex unlock in OK");
			}
			else
			{
				g_message ("Error: someone has tried to connect to the server when it still hasn't loaded everything needed");
        res = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
        res->id = cmd->id;

        sim_session_write (session, res);
        g_object_unref (res);
				g_object_unref (cmd);
				return FALSE;
			}
			memset(buffer, 0, BUFFER_SIZE);
			g_object_unref (cmd);
			continue; //we only want to listen database answer events.
		}

/*
		//-- FIXI 
		//No leak up until here!!
		g_object_unref (cmd);
		continue;
		//--
*/
    sim_session_update_last_data_timestamp (session);

		//this messages can arrive from other servers (up in the architecture -a master server-, down in the
		//architecture -a children server-, or at the same level -HA server-), from some sensor (an agent) or from the frameworkd.
    switch (cmd->type)
		{
	
			case SIM_COMMAND_TYPE_CONNECT:															//from children server / frameworkd / sensor
						sim_session_cmd_connect (session, cmd);
						sim_session_initialize_count (session);
						break;
			case SIM_COMMAND_TYPE_SERVER_GET_SENSORS:										//from frameworkd / master server
						sim_session_cmd_server_get_sensors (session, cmd);
						break;
			case SIM_COMMAND_TYPE_SENSOR:																// [from children server]-> To Master server / frameworkd 
						sim_session_cmd_sensor (session, cmd);
						break;	
			case SIM_COMMAND_TYPE_SERVER_GET_SERVERS:										//from frameworkd / master server
						sim_session_cmd_server_get_servers (session, cmd);
						break;	
			case SIM_COMMAND_TYPE_SERVER:																// [from children server]-> To Master server / frameworkd 
						sim_session_cmd_server (session, cmd);
						break;	
			case SIM_COMMAND_TYPE_SERVER_GET_SENSOR_PLUGINS:						//from frameworkd / master server
						sim_session_cmd_server_get_sensor_plugins (session, cmd);
						break;
			case SIM_COMMAND_TYPE_SERVER_SET_DATA_ROLE:									//from frameworkd / master server
						sim_session_cmd_server_set_data_role (session, cmd);
						break;
			case SIM_COMMAND_TYPE_SENSOR_PLUGIN_START:									//from frameworkd / master server
						sim_session_cmd_sensor_plugin_start (session, cmd);
						break;
			case SIM_COMMAND_TYPE_SENSOR_PLUGIN_STOP:										//from frameworkd / master server
						sim_session_cmd_sensor_plugin_stop (session, cmd);
						break;
			case SIM_COMMAND_TYPE_SENSOR_PLUGIN_ENABLE:								//from frameworkd / master server
						sim_session_cmd_sensor_plugin_enable (session, cmd);
						break;
			case SIM_COMMAND_TYPE_SENSOR_PLUGIN_DISABLE:								//from frameworkd / master server
						sim_session_cmd_sensor_plugin_disable (session, cmd);
						break;
			case SIM_COMMAND_TYPE_RELOAD_PLUGINS:
						sim_session_cmd_reload_plugins (session, cmd);				// from frameworkd / master server
						break;
			case SIM_COMMAND_TYPE_RELOAD_SENSORS:												// from frameworkd / master server
						sim_session_cmd_reload_sensors (session, cmd);
						break;
			case SIM_COMMAND_TYPE_RELOAD_HOSTS:													// from frameworkd / master server
						sim_session_cmd_reload_hosts (session, cmd);
						break;
			case SIM_COMMAND_TYPE_RELOAD_NETS:													// from frameworkd / master server
						sim_session_cmd_reload_nets (session, cmd);
						break;
			case SIM_COMMAND_TYPE_RELOAD_POLICIES:											// from frameworkd / master server
						sim_session_cmd_reload_policies (session, cmd);
						break;
			case SIM_COMMAND_TYPE_RELOAD_DIRECTIVES:										// from frameworkd / master server
						sim_session_cmd_reload_directives (session, cmd);
						break;
      case SIM_COMMAND_TYPE_RELOAD_SERVERS:                       // from frameworkd / master server
            sim_session_cmd_reload_servers (session, cmd);
            break;
			case SIM_COMMAND_TYPE_RELOAD_ALL:														// from frameworkd / master server
						sim_session_cmd_reload_all (session, cmd);
						break;
			case SIM_COMMAND_TYPE_SESSION_APPEND_PLUGIN:								//from sensor
						sim_session_cmd_session_append_plugin (session, cmd);
						break;
			case SIM_COMMAND_TYPE_SESSION_REMOVE_PLUGIN:								//from sensor
						sim_session_cmd_session_remove_plugin (session, cmd);
						break;
			case SIM_COMMAND_TYPE_PLUGIN_STATE_STARTED:									//from sensor (just information for the server)
						sim_session_cmd_plugin_state_started (session, cmd);
						break;
			case SIM_COMMAND_TYPE_PLUGIN_STATE_UNKNOWN:									//from sensor
						sim_session_cmd_plugin_state_unknown (session, cmd);
						break;
			case SIM_COMMAND_TYPE_PLUGIN_STATE_STOPPED:									//from sensor
						sim_session_cmd_plugin_state_stopped (session, cmd);
						break;
			case SIM_COMMAND_TYPE_PLUGIN_ENABLED:												//from sensor
						sim_session_cmd_plugin_enabled (session, cmd);
						break;
			case SIM_COMMAND_TYPE_PLUGIN_DISABLED:											//from sensor
						sim_session_cmd_plugin_disabled (session, cmd);
						break;
			case SIM_COMMAND_TYPE_SENSOR_GET_EVENTS:					    			//from sensor
						sim_session_cmd_sensor_get_events (session, cmd);
						break;
						
			case SIM_COMMAND_TYPE_EVENT:																//from sensor / server children
						sim_session_cmd_event (session, cmd);
                        sim_session_update_last_event_timestamp (session);
						break;
					
			case SIM_COMMAND_TYPE_HOST_OS_EVENT:								        // from sensor / children server
						sim_session_increase_count (session);
						sim_session_cmd_host_os_event (session, cmd);
						break;
			case SIM_COMMAND_TYPE_HOST_MAC_EVENT:												// from sensor / children server	
						sim_session_increase_count (session);
						sim_session_cmd_host_mac_event (session, cmd);
						break;
			case SIM_COMMAND_TYPE_HOST_SERVICE_EVENT:										// from sensor / children server
						sim_session_increase_count (session);
						sim_session_cmd_host_service_event (session, cmd);
						break;
			case SIM_COMMAND_TYPE_OK:																		//from *
						sim_session_cmd_ok (session, cmd);
						break;
			case SIM_COMMAND_TYPE_ERROR:																//from *
						sim_session_cmd_error (session, cmd);
						break;
			case SIM_COMMAND_TYPE_PONG:
						sim_session_cmd_pong (session, cmd);									// from sensor
						break;
      case SIM_COMMAND_TYPE_DATABASE_QUERY:                       // from children server
						ossim_debug ( "%s: SIM_COMMAND_TYPE_DATABASE_QUERY deprecated", __func__);
				    break;                                                  
	    case SIM_COMMAND_TYPE_DATABASE_ANSWER:                      // from master server
						ossim_debug ( "%s: SIM_COMMAND_TYPE_DATABASE_ANSWER deprecated", __func__);
            break;
			case SIM_COMMAND_TYPE_SNORT_EVENT:
						sim_session_increase_count (session);
					  sim_session_cmd_event (session, cmd);
		   		break;
      case SIM_COMMAND_TYPE_AGENT_DATE:                           //from sensor. still it doesn't do nothing
          break;
			case SIM_COMMAND_TYPE_SENSOR_GET_FRAMEWORK:
			  sim_session_cmd_session_get_framework(session,cmd);
			  break;
			case SIM_COMMAND_TYPE_FRMK_GETDB:
			  sim_session_cmd_session_get_frmk_getdb(session,cmd);
					break;
			case SIM_COMMAND_TYPE_AGENT_PING:
        sim_session_cmd_agent_ping (session,cmd);
				break;
      case SIM_COMMAND_TYPE_IDM_EVENT:
	      sim_session_cmd_idm_event(session,cmd);
	      break;
	    case SIM_COMMAND_TYPE_RELOAD_POST_CORRELATION_SIDS:
  	    sim_session_cmd_reload_post_correlation_sids (session,cmd);
				break;
			default:
						ossim_debug ( "sim_session_read: error command unknown type");
						res = sim_command_new_from_type (SIM_COMMAND_TYPE_ERROR);
						res->id = cmd->id;

						sim_session_write (session, res);
						g_object_unref (res);
						break;

		}

    g_object_unref (cmd);
		cmd = NULL;

		n=0;
  	memset(buffer, 0, BUFFER_SIZE);

		aux_created_events++;
		ossim_debug ( "sim_session_read: %d", aux_created_events);

	}
	session->_priv->close=TRUE;
	ossim_debug ( "sim_session_read: exiting function in session: %p", session);

	//if(n==0)
		//return FALSE;
  return TRUE;
}

/*
 * Send the command specified (usually it will be a SIM_COMMAND_TYPE_EVENT or something like that) 
 * to all the master servers (servers UP in the architecture).
 */
void 
sim_session_resend_command (SimSession *session,	//FIXME: is this function deprecated?
														SimCommand	*command)
{
  g_return_if_fail (session != NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (command != NULL);
  g_return_if_fail (SIM_IS_COMMAND (command));

	SimServer *server = session->_priv->server;

	GList *list = sim_server_get_sessions (server);
	GList *listf = list;
	while (list)
	{
		SimSession *session = (SimSession *) list->data;
		if (sim_session_is_master_server (session))
			sim_session_write (session, command);	//FIXME: use another thread ,this will block a lot.

		list = list->next;
	}
	g_list_free (listf);


}

void
sim_session_debug_channel_status(SimSession *session)
{
  GIOCondition conds;
  GIOFlags flags;

  flags = g_io_channel_get_flags(session->_priv->io);
  conds = g_io_channel_get_buffer_condition(session->_priv->io);

  g_debug ("*** Channel status START ***\n"
           "Before write at session with %s\n"
           "WR %d\n"
           "RD %d\n"
           "IO_HUP %d\n"
           "IO_ERR %d\n"
           "IO_IN %d\n"
           "IO_NVAL %d\n"
           "IO_OUT %d\n"
           "Is buffered %d\n"
           "Buffer size %ld\n"
           "Enc: %s\n"
           "***Channel status END ***",
           session->_priv->ip_str,
           flags & G_IO_FLAG_IS_WRITEABLE,
           flags & G_IO_FLAG_IS_READABLE,
           conds & G_IO_HUP,
           conds & G_IO_ERR,
           conds & G_IO_IN,
           conds & G_IO_NVAL,
           conds & G_IO_OUT,
           g_io_channel_get_buffered(session->_priv->io),
           g_io_channel_get_buffer_size(session->_priv->io),
           (g_io_channel_get_encoding(session->_priv->io)) ? g_io_channel_get_encoding(session->_priv->io) : "N/A"
  );
}


/*
 * This function may be used to send data to sensors, to other servers, or to the frameworkd (if needed)
 *
 *
 */
gint
sim_session_write (SimSession  *session,
                   SimCommand  *command)
{
  gchar *str;
  gsize n;

  str = sim_command_get_string (command);
  if (!str)
    return 0;

  n = sim_session_write_from_buffer (session, str);

  g_free (str);

  return n;
}

gint
sim_session_write_from_buffer (SimSession *session,
                               gchar      *buffer)
{
  gboolean ok;
  gsize n = 0; //gsize = unsigned integer 32 bits

  g_return_val_if_fail (SIM_IS_SESSION (session), 0);

  //To prevent monitor threads writting at the same time as sessions
  g_mutex_lock (session->_priv->socket_mutex);

  ok = sim_session_write_final (session, buffer, strlen(buffer), &n);

  if (!ok)
  {
    session->_priv->close = TRUE;
    g_message ("%s: send buffer unsuccesful: %s", __func__, buffer);
  }

  g_mutex_unlock (session->_priv->socket_mutex);

  return n;
}

static gboolean
sim_session_write_final (SimSession *session, gchar *buffer, gssize count, gsize *bytes_writtenp)
{
  GIOCondition conds;
  GIOStatus status;
  GError *err = NULL;
  gboolean break_conn;

  g_return_val_if_fail (session->_priv->io != NULL, 0);

  g_debug ("%s: session %s: %s %ld", __func__, session->_priv->ip_str, buffer, (glong)count);

  conds = g_io_channel_get_buffer_condition(session->_priv->io);

  if ((conds&G_IO_HUP) || session->_priv->g_io_hup || session->_priv->close) //with this condition should be enought
    return FALSE;

  sim_util_block_signal (SIGPIPE);
  status = g_io_channel_write_chars (session->_priv->io, buffer, count, bytes_writtenp, &err);
  sim_util_unblock_signal (SIGPIPE);

  if (status != G_IO_STATUS_NORMAL)
    goto beach;

  status = g_io_channel_flush(session->_priv->io, &err);

  if (status != G_IO_STATUS_NORMAL)
    goto beach;

  return TRUE;

beach:
  if (status == G_IO_STATUS_ERROR)
  {
    g_debug ("%s: %s", __func__, err->message);
    g_error_free (err);
  }

  break_conn = sim_session_check_iochannel_status (session, status);
  if (!break_conn)
    session->_priv->close = TRUE;

  return FALSE;
}

/*
 *
 *
 *
 */
gboolean
sim_session_has_plugin_type (SimSession     *session,  SimPluginType   type)
{
  GList  *list;
  gboolean  found = FALSE;

  g_return_val_if_fail (session != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_SESSION (session), FALSE);
  
  list = session->_priv->plugins;
  while (list)
    {
      SimPlugin *plugin = (SimPlugin *) list->data;

      if (plugin->type == type)
	{
	  found = TRUE;
	  break;
	}

      list = list->next;
    }

  return found;
}

/*
 *
 *
 *
 */
gboolean
sim_session_has_plugin_id (SimSession     *session,
												   gint            plugin_id)
{
  GList  *list;
  gboolean  found = FALSE;

  g_return_val_if_fail (session != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_SESSION (session), FALSE);
  
  list = session->_priv->plugin_states;
  while (list)
  {
    SimPluginState  *plugin_state = (SimPluginState *) list->data;
    SimPlugin  *plugin = sim_plugin_state_get_plugin (plugin_state);

    if (sim_plugin_get_id (plugin) == plugin_id)
		{
		  found = TRUE;
	  	break;
		}

    list = list->next;
  }

  return found;
}


/*
 *
 *
 *
 */
void
sim_session_reload (SimSession *session,
                    SimContext *context)
{
  GList  *list;
  list = session->_priv->plugin_states;
  while (list)
  {
    SimPluginState  *plugin_state = (SimPluginState *) list->data;
    gint plugin_id = sim_plugin_state_get_plugin_id (plugin_state);

    SimPlugin *plugin = sim_context_get_plugin (context, plugin_id);

    sim_plugin_state_set_plugin (plugin_state, plugin);
    g_object_unref (plugin);

    list = list->next;
  }
}

/*
 *
 *
 *
 */
SimSensor*
sim_session_get_sensor (SimSession *session)
{
  g_return_val_if_fail (session, NULL);
  g_return_val_if_fail (SIM_IS_SESSION (session), NULL);

  return session->_priv->sensor;
}

/*
 * Returns the server associated with this session (this server);
 */
gpointer
sim_session_get_server (SimSession *session)
{
  g_return_val_if_fail (session, NULL);
  g_return_val_if_fail (SIM_IS_SESSION (session), NULL);

  return session->_priv->server;
}


/*
 *Is the session from a sensor ?
 */
gboolean
sim_session_is_sensor (SimSession *session)
{
  g_return_val_if_fail (session, FALSE);
  g_return_val_if_fail (SIM_IS_SESSION (session), FALSE);

  if (session->type == SIM_SESSION_TYPE_SENSOR) 
		return TRUE;

  return FALSE;
}

/*
 * Is the session from a master server? (a server which is "up" in the architecture)
 */
gboolean
sim_session_is_master_server (SimSession *session)
{
  g_return_val_if_fail (session, FALSE);
  g_return_val_if_fail (SIM_IS_SESSION (session), FALSE);

  if (session->type == SIM_SESSION_TYPE_SERVER_UP)
    return TRUE;

  return FALSE;
}

/*
 * Is the session from a children server? (a server which is "down" in the architecture)
 */
gboolean
sim_session_is_children_server (SimSession *session)
{
  g_return_val_if_fail (session, FALSE);
  g_return_val_if_fail (SIM_IS_SESSION (session), FALSE);

  if (session->type == SIM_SESSION_TYPE_SERVER_DOWN)
    return TRUE;

  return FALSE;
}


/*
Is the session from the web ? FIXME: soon this will be from the frameworkd
*/
gboolean
sim_session_is_web (SimSession *session)
{
  g_return_val_if_fail (session, FALSE);
  g_return_val_if_fail (SIM_IS_SESSION (session), FALSE);

  if (session->type == SIM_SESSION_TYPE_WEB)
    return TRUE;

  return FALSE;
}


/*
 *
 *
 *
 */
gboolean
sim_session_is_connected (SimSession *session)
{
  g_return_val_if_fail (session, FALSE);
  g_return_val_if_fail (SIM_IS_SESSION (session), FALSE);

  return session->_priv->connect; 
} 

/*
 *
 *
 *
 */
void
sim_session_close (SimSession *session)
{
  g_return_if_fail (session);
  g_return_if_fail (SIM_IS_SESSION (session));
  
  session->_priv->close = TRUE;
			
	ossim_debug ( "sim_session_close: closing session: %p",session);
		
}

/*
 *
 *
 *
 */
gboolean
sim_session_must_close (SimSession *session)
{
  g_return_val_if_fail (session, TRUE);
  g_return_val_if_fail (SIM_IS_SESSION (session), TRUE);
  
  return session->_priv->close;
}

/*
 *
 */
void
sim_session_set_is_initial (SimSession *session,
														gboolean tf)
{
  g_return_if_fail (session);
  g_return_if_fail (SIM_IS_SESSION (session));

	session->_priv->is_initial = tf;
}

gboolean
sim_session_get_is_initial (SimSession *session)
{
  g_return_val_if_fail (session, FALSE);
  g_return_val_if_fail (SIM_IS_SESSION (session), FALSE);

	return session->_priv->is_initial;
}

/*
 * If this function is called, that means that this is a children server without DB.
 * Wait until we sent in the initial session with the primary rserver the "connect" and we receive the "OK" msg,
 * so we know that we are connected and we can ask for things.
 */
void
sim_session_wait_fully_stablished (SimSession *session)
{
  g_return_if_fail (session);
  g_return_if_fail (SIM_IS_SESSION (session));

	g_mutex_lock (session->_priv->initial_mutex);

	while (!session->_priv->fully_stablished)	//this is set in sim_session_read().
		g_cond_wait (session->_priv->initial_cond, session->_priv->initial_mutex);

	g_mutex_unlock (session->_priv->initial_mutex);

}

/*
 * first session with a primary rserver is ok. This server is the children server; it
 * has sent a "connect" to the primary master server, and the master server answer with ok,
 * so the session is fully stablished.
 */
void
sim_session_set_fully_stablished (SimSession *session)
{
  g_return_if_fail (session);
  g_return_if_fail (SIM_IS_SESSION (session));

	g_mutex_lock (session->_priv->initial_mutex);
	session->_priv->fully_stablished = TRUE;	
	g_cond_signal(session->_priv->initial_cond);
	g_mutex_unlock (session->_priv->initial_mutex);

}

void
sim_session_set_id (SimSession *session, gint id)
{
  g_return_if_fail (session);
  g_return_if_fail (SIM_IS_SESSION (session));

  session->_priv->id = id; 
} 

gint
sim_session_get_id (SimSession *session)
{
  g_return_val_if_fail (session, -1);
  g_return_val_if_fail (SIM_IS_SESSION (session), -1);

  return session->_priv->id; 
}
void sim_session_set_sensor(SimSession *session,SimSensor *sensor){
	g_return_if_fail (session!=NULL);
	g_return_if_fail (sensor!=NULL);
	g_return_if_fail (SIM_IS_SESSION (session));
	g_return_if_fail (SIM_IS_SENSOR (sensor));
	session->_priv->sensor = sensor;
}

void							sim_session_set_event_scan_fn								(SimSession *session,gboolean (*pf)(SimCommand *,GScanner*)){
	g_return_if_fail (session!=NULL);
	g_return_if_fail (SIM_IS_SESSION (session));
	g_return_if_fail (pf!=NULL);
	session->_priv->agent_scan_fn = pf;

}
gboolean				  (*sim_session_get_event_scan_fn             (SimSession *session))(SimCommand *,GScanner*){
	g_return_val_if_fail (session!=NULL, FALSE);
	g_return_val_if_fail (SIM_IS_SESSION (session), FALSE);
	return session->_priv->agent_scan_fn;

}

guint
sim_session_get_received (SimSession *session)
{
  g_return_val_if_fail (session, 0);
  g_return_val_if_fail (SIM_IS_SESSION (session), 0);

  return session->_priv->received; 
} 

/*
 * This will be used by directive_events only.
 */
void
sim_session_prepare_and_insert_non_block(SimEvent *event)
{
	//FIXME: This NEVER should occur
	if (!SIM_IS_EVENT (event))
	{
		ossim_debug	("sim_session_cmd_event: Error SIM_IS_EVENT norl");
		return;
	}

	sim_event_set_role_and_policy (event);

  if (!event->device_id && event->sensor_id)
  {
    if (!sim_event_set_sid (event)) //this will insert data also in alienvault_siem.device table
    {
      g_message ("%s: Error: event discarded: problems in sid", __func__);
      sim_event_unref (event);
      return;
    }
  }

  //SIM correlation/storage/forward call.
  if (sim_role_sim (event->role))
  {
    ossim_debug ("%s: inserting into SIM... event->id = %s", __func__, sim_uuid_get_string (event->id));
    sim_container_push_event_noblock (ossim.container, event); //push the event in the queue
  }
}


// Anomalies events from IDM come with session NULL
void
sim_session_prepare_and_insert (SimSession *session, SimEvent *event)
{
  SimPlugin *plugin;
  SimPluginSid *plugin_sid;

  g_return_if_fail (SIM_IS_EVENT(event));

  if (ossim.config->idm.activated)
  {
    sim_event_enrich_idm (event);
  }

  sim_event_set_asset_value (event);

  // Fill taxonomy - product
  plugin = sim_context_get_plugin (event->context, event->plugin_id);
  if (plugin)
  {
    event->tax_product = sim_plugin_get_product (plugin);
    g_object_unref (plugin);
  }

  // Fill taxonomy - category, subcategory
  plugin_sid = sim_context_get_plugin_sid (event->context, event->plugin_id, event->plugin_sid);
	if (plugin_sid)
	{
	  event->tax_category = sim_plugin_sid_get_category (plugin_sid);
	  event->tax_subcategory = sim_plugin_sid_get_subcategory (plugin_sid);
		g_object_unref (plugin_sid);
	}

  // Check reputation for this event.
	if (session)
	{
	  SimReputation *reputation = sim_server_get_reputation(session->_priv->server);
	  if(reputation)
	    sim_reputation_match_event (reputation, event);
	}

	// GeoIP information
	event->src_country = sim_geoip_lookup (event->src_ia);
	event->dst_country = sim_geoip_lookup (event->dst_ia);

  sim_event_set_role_and_policy (event);

  if (!event->device_id && event->sensor_id)
  {
    if (!sim_event_set_sid (event)) //this will insert data also in alienvault_siem.device table
    {
      g_message ("%s: Error: event discarded: problems in sid", __func__);
      sim_event_unref (event);
      return;
    }
  }

  //SIM correlation call.
  if (sim_role_sim (event->role))
  {
    if (sim_container_get_events_in_queue (ossim.container)<10000)
    {
      ossim_debug	("%s: inserting into SIM...event->id = %s", __func__, sim_uuid_get_string (event->id));
      sim_container_push_event (ossim.container, event); //push the event in the queue
    }
    else
      sim_container_inc_discarded_events (ossim.container);
  }

  sim_event_unref (event);

  return;
}

static void
sim_session_config_agent (SimSession *session,SimCommand *command){
	g_return_if_fail (session!=NULL);
	g_return_if_fail (command!=NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (SIM_IS_COMMAND (command));
	g_return_if_fail (command->data.connect.type==SIM_SESSION_TYPE_SENSOR);
	session->_priv->agent_scan_fn = sim_command_get_agent_scan(); //which parser whould we use? Assign it to the function pointer
}

static void
sim_session_config_frameworkd (SimSession *session,SimCommand *command){
  g_return_if_fail (session!=NULL);
  g_return_if_fail (command!=NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (SIM_IS_COMMAND (command));
  g_return_if_fail (command->data.connect.type==SIM_SESSION_TYPE_FRAMEWORKD);
  session->_priv->agent_scan_fn = sim_command_get_agent_scan(); //which parser whould we use? Assign it to the function pointer
}

static void 
sim_session_config_default (SimSession *session,SimCommand *command){
	g_return_if_fail (session!=NULL);
	g_return_if_fail (command!=NULL);
  g_return_if_fail (SIM_IS_SESSION (session));
  g_return_if_fail (SIM_IS_COMMAND (command));
	g_return_if_fail (command->data.connect.type==SIM_SESSION_TYPE_NONE);
	session->_priv->default_scan_fn = sim_command_get_default_scan();
}

gboolean (*sim_session_get_event_scan(SimSession *session))(SimCommand*,GScanner*){
	g_return_val_if_fail (session!=NULL,NULL);
	g_return_val_if_fail (SIM_IS_SESSION (session),NULL);
	switch (session->type){
		case SIM_SESSION_TYPE_SERVER_DOWN:
			return session->_priv->remote_server_scan_fn;
			break;
		case SIM_SESSION_TYPE_SENSOR:
	  case SIM_SESSION_TYPE_FRAMEWORKD:
			return session->_priv->agent_scan_fn;
			break;
		case SIM_SESSION_TYPE_NONE:
			return session->_priv->default_scan_fn;
		default:
          g_warning ("Unknown session type. Aborting program");
          g_return_val_if_fail (0, NULL);
			
	}
	return NULL; //never reached
}


void sim_session_update_last_data_timestamp(SimSession * session)
{
  g_return_if_fail (SIM_IS_SESSION (session));
  time(&session->_priv->last_data_timestamp);

}

time_t sim_session_get_last_data_timestamp(SimSession * session)
{
  g_return_val_if_fail (SIM_IS_SESSION (session), 0);
  return session->_priv->last_data_timestamp;
}

static
void
sim_session_update_last_event_timestamp (SimSession * session)
{
  g_return_if_fail (SIM_IS_SESSION (session));
  session->_priv->last_event_timestamp = session->_priv->last_data_timestamp;

  return;
}

time_t
sim_session_get_last_event_timestamp (SimSession * session)
{
  g_return_val_if_fail (SIM_IS_SESSION (session), 0);

  return (session->_priv->last_event_timestamp);
}


// vim: set tabstop=2 sts=2 noexpandtab:


