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

#include "sim-config.h"

#include "sim-connect.h"

#include <signal.h>
#include <glib.h>
#include <gnet.h>
#include <unistd.h>

#include "sim-util.h"
#include "os-sim.h"
#include "sim-policy.h"

extern SimMain ossim;

//static gpointer  sim_connect_send_alarm      (gpointer data);
static gboolean sigpipe_received = FALSE;

static SimConfig *config=NULL;

// Actually not used
void
pipe_handler(int signum)
{
  // unused parameter
  (void) signum;

  sigpipe_received = TRUE;
  ossim_debug ( "sim_connect_send_alarm: Broken Pipe (connection with framework broken). Reseting socket");
  sim_connect_send_alarm(NULL);
}


gpointer
sim_connect_send_alarm(gpointer data)
{
  if(!config)
    if(data)
      config=(SimConfig*)data;

  SimEvent* event=NULL;
  GTcpSocket* socket = NULL;
  GIOChannel* iochannel = NULL;

  GIOCondition conds;

  gchar *buffer = NULL;
  gchar *aux = NULL;

  gsize n;

  gint  risk;

  gchar *ip_src = NULL;
  gchar *ip_dst = NULL;
  gchar *ip_sensor = NULL;

  gchar *hostname;
  gint port;

  GInetAddr* addr = NULL;
  hostname = g_strdup("127.0.0.1");
  port = config->framework.port;
  gint iter=0;
	guint inx = 0;

	gint base64_len;

  for(;;) //Pop events for ever
  {
    GString *st;
    gchar    timebuff[TIMEBUF_SIZE];
    gchar   *timestamp = timebuff;

    event=(SimEvent*)sim_container_pop_ar_event(ossim.container);
    if (!event)
    {
      ossim_debug ( "sim_connect_send_alarm: No event");
      continue;
    }
    struct {
      gchar *key;
      gchar *base64data;
    } base64_params[] = {
      {"filename", (event->filename && (base64_len = strlen (event->filename))) ? g_base64_encode ((guchar *)event->filename, base64_len): NULL},
      {"username", (event->username  && (base64_len = strlen (event->username))) ? g_base64_encode ((guchar *)event->username, base64_len): NULL},
      {"password", (event->password  && (base64_len = strlen (event->password))) ? g_base64_encode ((guchar *)event->password, base64_len): NULL},
      {"userdata1", (event->userdata1 && (base64_len = strlen (event->userdata1))) ? g_base64_encode ((guchar *)event->userdata1, base64_len): NULL},
      {"userdata2", (event->userdata2 && (base64_len = strlen (event->userdata2))) ? g_base64_encode ((guchar *)event->userdata2, base64_len): NULL},
      {"userdata3", (event->userdata3 && (base64_len = strlen (event->userdata3))) ? g_base64_encode ((guchar *)event->userdata3, base64_len): NULL},
      {"userdata4", (event->userdata4 && (base64_len = strlen (event->userdata4))) ? g_base64_encode ((guchar *)event->userdata4, base64_len): NULL},
      {"userdata5", (event->userdata5 && (base64_len = strlen (event->userdata5))) ? g_base64_encode ((guchar *)event->userdata5, base64_len): NULL},
      {"userdata6", (event->userdata6 && (base64_len = strlen (event->userdata6))) ? g_base64_encode ((guchar *)event->userdata6, base64_len): NULL},
      {"userdata7", (event->userdata7 && (base64_len = strlen (event->userdata7))) ? g_base64_encode ((guchar *)event->userdata7, base64_len): NULL},
      {"userdata8", (event->userdata8 && (base64_len = strlen (event->userdata8))) ? g_base64_encode ((guchar *)event->userdata8, base64_len): NULL},
      {"userdata9", (event->userdata9 && (base64_len = strlen (event->userdata9))) ? g_base64_encode ((guchar *)event->userdata9, base64_len): NULL},
      {"rep_act_src", (event->str_rep_act_src && (base64_len = strlen (event->str_rep_act_src))) ? g_base64_encode ((guchar *)event->str_rep_act_src, base64_len): NULL},
      {"rep_act_dst", (event->str_rep_act_dst && (base64_len = strlen (event->str_rep_act_dst))) ? g_base64_encode ((guchar *)event->str_rep_act_dst, base64_len): NULL}
    };

  // Send max risk
  // i.e., to avoid risk=0 when destination is 0.0.0.0
  if (event->risk_a > event->risk_c)
    risk = event->risk_a;
  else
    risk = event->risk_c;

	/* String to be sent */
  if(event->time_str)
    timestamp = event->time_str;
  else
    sim_time_t_to_str (timestamp, event->time);


  if (event->src_ia)
    ip_src = sim_inet_get_canonical_name (event->src_ia);
  else
    ip_src = g_strdup_printf("0.0.0.0");

  if (event->dst_ia)
    ip_dst = sim_inet_get_canonical_name (event->dst_ia);
  else
    ip_dst = g_strdup_printf("0.0.0.0");

  if (event->sensor)
    ip_sensor = sim_inet_get_canonical_name (event->sensor);
  else
    ip_sensor = g_strdup_printf("0.0.0.0");

  SimUuid * context_id = event->context ? sim_context_get_id (event->context) : NULL;

  if (event->policy)
  {
    SimUuid * policy_id = sim_policy_get_id(event->policy);
    aux = g_strdup_printf ("event date=\"%s\" plugin_id=\"%d\" plugin_sid=\"%d\" risk=\"%d\" priority=\"%d\" reliability=\"%d\" event_id=\"%s\" backlog_id=\"%s\" context_id=\"%s\" src_ip=\"%s\" src_port=\"%d\" dst_ip=\"%s\" dst_port=\"%d\" protocol=\"%d\" sensor=\"%s\" actions=\"%d\" policy_id=\"%s\" rep_prio_src=\"%d\" rep_prio_dst=\"%d\" rep_rel_src=\"%d\" rep_rel_dst=\"%d\"",
                           timestamp, event->plugin_id, event->plugin_sid, risk,
                           event->priority, event->reliability, event->id ? sim_uuid_get_string (event->id) : "",
                           event->backlog_id ? sim_uuid_get_string (event->backlog_id) : "",
                           context_id ? sim_uuid_get_string (context_id) : "",
                           ip_src, event->src_port, ip_dst, event->dst_port, event->protocol,
                           ip_sensor, sim_policy_get_has_actions(event->policy), sim_uuid_get_string (policy_id), event->rep_prio_src,event->rep_prio_dst, event->rep_rel_src, event->rep_rel_dst);
  }
  else //If there aren't any policy associated, the policy and the action number will be 0
  {
    aux = g_strdup_printf ("event date=\"%s\" plugin_id=\"%d\" plugin_sid=\"%d\" risk=\"%d\" priority=\"%d\" reliability=\"%d\" event_id=\"%s\" backlog_id=\"%s\" context_id=\"%s\" src_ip=\"%s\" src_port=\"%d\" dst_ip=\"%s\" dst_port=\"%d\" protocol=\"%d\" sensor=\"%s\" actions=\"%d\" policy_id=\"%d\" rep_prio_src=\"%d\" rep_prio_dst=\"%d\" rep_rel_src=\"%d\" rep_rel_dst=\"%d\"",
                           timestamp, event->plugin_id, event->plugin_sid, risk, event->priority,
                           event->reliability, sim_uuid_get_string (event->id),
                           event->backlog_id ? sim_uuid_get_string (event->backlog_id) : "",
                           context_id ? sim_uuid_get_string (context_id) : "",
                           ip_src, event->src_port, ip_dst, event->dst_port, event->protocol, ip_sensor, 0, 0,
                           event->rep_prio_src,event->rep_prio_dst, event->rep_rel_src, event->rep_rel_dst );
  }
  g_free (ip_src);
  g_free (ip_dst);
  g_free (ip_sensor);
	st = g_string_new (aux);	
	for (inx = 0;inx<G_N_ELEMENTS(base64_params);inx++){
		ossim_debug ("%s: %u:%s %p",__FUNCTION__,inx,base64_params[inx].base64data,base64_params[inx].base64data);
		if (base64_params[inx].base64data != NULL){
			g_string_append_printf(st," %s=\"%s\"",base64_params[inx].key,base64_params[inx].base64data!=NULL ? base64_params[inx].base64data :  "");
			g_free (base64_params[inx].base64data); /* we dont't need the data, anymore, so free it*/
		}
	}
	g_string_append(st,"\n");
	buffer = g_string_free (st,FALSE);
/*
  buffer = g_strconcat (aux,
    event->filename  ? " filename=\"" : "", event->filename  ? event->filename  : "", event->filename ? "\"" : "",
    event->username  ? " username=\"" : "", event->username  ? event->username  : "", event->username ? "\"" : "",
    event->password  ? " password=\"" : "", event->password  ? event->password  : "", event->password ? "\"" : "",
    event->userdata1 ? " userdata1=\"" : "",event->userdata1 ? event->userdata1 : "",event->userdata1 ? "\"" : "",
    event->userdata2 ? " userdata2=\"" : "",event->userdata2 ? event->userdata2 : "",event->userdata2 ? "\"" : "",
    event->userdata3 ? " userdata3=\"" : "",event->userdata3 ? event->userdata3 : "",event->userdata3 ? "\"" : "",
    event->userdata4 ? " userdata4=\"" : "",event->userdata4 ? event->userdata4 : "",event->userdata4 ? "\"" : "",
    event->userdata5 ? " userdata5=\"" : "",event->userdata5 ? event->userdata5 : "",event->userdata5 ? "\"" : "",
    event->userdata6 ? " userdata6=\"" : "",event->userdata6 ? event->userdata6 : "",event->userdata6 ? "\"" : "",
    event->userdata7 ? " userdata7=\"" : "",event->userdata7 ? event->userdata7 : "",event->userdata7 ? "\"" : "",
    event->userdata8 ? " userdata8=\"" : "",event->userdata8 ? event->userdata8 : "",event->userdata8 ? "\"" : "",
    event->userdata9 ? " userdata9=\"" : "",event->userdata9 ? event->userdata9 : "",event->userdata9 ? "\"" : "",
    "\n", NULL);
	*/
	ossim_debug ( "sim_connect_send_alarm: buffer: %s", buffer);
  if (!buffer)
  {
    ossim_debug ( "sim_connect_send_alarm: message error");
		sim_event_unref (event);
    g_free (aux);
    continue;  
  }
  g_free (aux);
	aux = NULL;

  //old way was creating a new socket and giochannel for each alarm.
  //now a persistent giochannel is used.
  //iochannel = gnet_tcp_socket_get_io_channel (socket);

	if(iochannel)
  conds=g_io_channel_get_buffer_condition(iochannel);
  if(!iochannel||sigpipe_received||(conds&G_IO_HUP)||(conds&G_IO_ERR))
  //Loop to get a connection
  do
	{
		if(sigpipe_received)
		{
   		if(socket)
			  gnet_tcp_socket_delete (socket);
			sigpipe_received=FALSE;
			iochannel=FALSE;
		}

	// if not, create socket and iochannel from config and store to get a persistent connection.
		ossim_debug ( "sim_connect_send_alarm: invalid iochannel.(%d)",iter);
		ossim_debug ( "sim_connect_send_alarm: trying to create a new iochannel.(%d)",iter);
		if (!hostname)
		{
			//FIXME: may be that this host hasn't got any frameworkd. If the event is forwarded to other server, it will be sended to the
			//other server framework (supposed it has a defined one).
			ossim_debug ( "sim_connect_send_alarm: Hostname error, reconnecting in 3secs (%d)",iter);
			hostname = g_strdup(config->framework.host);
   		sleep(3); //FIXME ??????????????
			continue;
		}
		if(addr)
			g_free(addr);

		addr = gnet_inetaddr_new_nonblock (hostname, port);
		if (!addr)
		{
			ossim_debug ( "sim_connect_send_alarm: Error creating the address, trying in 3secs(%d)",iter);
	    		sleep(3);
			continue;
  	}

 		socket = gnet_tcp_socket_new (addr);
  	if (!socket)
		{
			ossim_debug ( "sim_connect_send_alarm: Error creating socket(1), reconnecting in 3 secs..(%d)",iter);
			iochannel=NULL;
			socket=NULL;
			sleep(3);
			continue;
		}
		else
		{
	  		iochannel = gnet_tcp_socket_get_io_channel (socket);
		  	if (!iochannel)
			{
			  ossim_debug ( "sim_connect_send_alarm: Error creating iochannel, reconnecting in 3 secs..(%d)",iter);
    			  if(socket)
			  gnet_tcp_socket_delete (socket);
			  socket=NULL;
			  iochannel=NULL;
			  sleep(3);
			  continue;
			}
			else
			{
				sigpipe_received=FALSE;
				ossim_debug ("%s: new iochannel created. Returning %lx (%d)", __func__, (glong)iochannel, iter);
			}
		}

  	iter++;
  } while(!iochannel);

  //g_return_val_if_fail (iochannel != NULL, NULL);

  n = strlen(buffer);
  ossim_debug ("%s: Message to send: %s, (len=%ld)", __func__, buffer, n);

//signals actually not used
//  old_action=signal(SIGPIPE, pipe_handler);

	//Old sending
  //error = gnet_io_channel_writen (iochannel, buffer, n, &n);

  GIOStatus status;
  GError    *err = NULL;

  status = g_io_channel_write_chars (iochannel, buffer, strlen(buffer), &n, &err);

  if (status != G_IO_STATUS_NORMAL)
  {
    //back to the queue so we dont loose the action/response
		//FIXME: AR uncomment when this don't crash. 2009/06/04
  //  g_object_ref(event);
  //  sim_container_push_ar_event (ossim.container, event);

    ossim_debug ( "sim_connect_send_alarm: message could not be sent.. reseting"); 
  }

	//--
	//FIXME: Now, all the events sent to frameworkd opens and closes his own connection (bad performance). This is only a temporary fix. 
	//Its needed to add a persistent connection, because the socket was not controlled by ossim
  gnet_tcp_socket_delete (socket);
  iochannel = NULL;
	//--
	
  //error = gnet_io_channel_readn (iochannel, buffer, n, &n);
  //fwrite(buffer, n, 1, stdout);
/*
  if (error != G_IO_ERROR_NONE)
  { 
    //back to the queue so we dont loose the action/response
    sim_container_push_ar_event (ossim.container, event);
    ossim_debug ( "sim_connect_send_alarm: message could not be sent.. reseting"); 
    if(buffer)
	    g_free (buffer);

    g_free (aux);
    gnet_tcp_socket_delete (socket);
    iochannel=NULL;
  }else
    ossim_debug("sim_connect_send_alarm: message sent succesfully: %s", buffer);
*/
  //Cose conn

  g_free (buffer);
  buffer=NULL;

  //iochannel=NULL;
  //gnet_tcp_socket_delete (socket);

 	if(event)
	    sim_event_unref (event);
  }
	g_free (hostname);  
}

// vim: set tabstop=2:

