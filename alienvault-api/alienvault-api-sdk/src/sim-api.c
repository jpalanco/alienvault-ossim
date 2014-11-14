/*
#
#  License:
#
#  Copyright (c) 2013 AlienVault
#  All rights reserved.
#
#  This package is free software; you can redistribute it and/or modify
#  it under the terms of the GNU General Public License as published by
#  the Free Software Foundation; version 2 dated June, 1991.
#  You may not use, modify or distribute this program under any other version
#  of the GNU General Public License.
#
#  This package is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.
#
#  You should have received a copy of the GNU General Public License
#  along with this package; if not, write to the Free Software
#  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#  MA  02110-1301  USA
#
#
#  On Debian GNU/Linux systems, the complete text of the GNU General
#  Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
#  Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#
*/
#include <config.h>

#include <glib.h>
#include <libsoup/soup.h>

#include "sim-api.h"

struct _SimApi {
  GObject       object;

  /*< private >*/
  SimApiPrivate *_priv;
};

struct _SimApiClass {
  GObjectClass  object_class;

  /*< private >*/
};

struct _SimApiPrivate {
  SoupSession *session;
  gboolean authenticated;
  gchar *ip;
  guint port;
  gchar *username;
  gchar *password;
};

static void sim_api_authenticate (SoupSession *session, SoupMessage *msg, SoupAuth *auth, gboolean retrying, gpointer data);
static void sim_api_class_init (SimApiClass *klass);
static void sim_api_init (SimApi *sim_api);
static void sim_api_finalize (GObject *object);

G_DEFINE_TYPE (SimApi, sim_api, G_TYPE_OBJECT);

static void
sim_api_class_init (SimApiClass *klass)
{
  G_OBJECT_CLASS (klass)->finalize = sim_api_finalize;

  g_type_class_add_private (klass, sizeof (SimApiPrivate));
}

static void
sim_api_init (SimApi *sim_api)
{
  sim_api->_priv = G_TYPE_INSTANCE_GET_PRIVATE (sim_api, SIM_TYPE_API, SimApiPrivate);

  sim_api->_priv->session = soup_session_sync_new ();
//  soup_session_add_feature (sim_api->_priv->session, soup_logger_new (SOUP_LOGGER_LOG_HEADERS | SOUP_LOGGER_LOG_BODY, 1024));
  sim_api->_priv->authenticated = FALSE;
  sim_api->_priv->ip = NULL;
  sim_api->_priv->port = 0;
  sim_api->_priv->username = NULL;
  sim_api->_priv->password = NULL;

  g_signal_connect (sim_api->_priv->session, "authenticate", G_CALLBACK (sim_api_authenticate), sim_api);
}

static void
sim_api_finalize (GObject *object)
{
  SimApi *sim_api = SIM_API_CAST (object);

  g_object_unref (sim_api->_priv->session);
  g_free (sim_api->_priv->ip);
  g_free (sim_api->_priv->username);
  g_free (sim_api->_priv->password);

  G_OBJECT_CLASS (sim_api_parent_class)->finalize (object);
}

static void
sim_api_authenticate (SoupSession *session, SoupMessage *msg,
                      SoupAuth *auth, gboolean retrying, gpointer data)
{
  SimApi *sim_api = data;

  if (!sim_api->_priv->authenticated)
  {
    soup_auth_authenticate (auth, sim_api->_priv->username, sim_api->_priv->password);
    sim_api->_priv->authenticated = TRUE;
  }
}

void
sim_api_initialization ()
{
  g_type_init ();
}

SimApi *
sim_api_new (void)
{
  SimApi *sim_api;

  sim_api = SIM_API (g_object_new (SIM_TYPE_API, NULL));

  return sim_api;
}

void
sim_api_free (SimApi *sim_api)
{
  g_object_unref (sim_api);
}

void
sim_api_login (SimApi *sim_api, const char *ip, guint port, const char *username, const char *password)
{
  sim_api->_priv->ip = g_strdup (ip);
  sim_api->_priv->port = port;
  sim_api->_priv->username = g_strdup (username);
  sim_api->_priv->password = g_strdup (password);
}

gchar *
sim_api_request (SimApi *sim_api, const gchar *request)
{
  SoupMessage *message;
  gchar *ret;

//  uri = g_strdup_printf ("https://%s:%d/av/api/1.0/%s",
//                         sim_api->_priv->ip,
//                         sim_api->_priv->port,
//                         request);

  message = soup_message_new (SOUP_METHOD_GET, request);
  soup_message_headers_append (message->request_headers, "Accept", "application/json");
  soup_session_send_message (sim_api->_priv->session, message);

  if (!SOUP_STATUS_IS_SUCCESSFUL (message->status_code))
  {
 //   debug_printf (1, "ERROR: %d %s\n", message->status_code, message->reason_phrase);

//    ret = g_strdup ("ERROR");
    ret = g_strdup_printf ("%d", message->status_code);
  }
  else
  {
    ret = g_strdup (message->response_body->data);
  }

  g_object_unref (message);

  return ret;
}
