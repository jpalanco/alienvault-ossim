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

#include "openssl/ssl.h"
#include "openssl/err.h"

#include "sim-ssl.h"

#include "os-sim.h"
#include "sim-config.h"


enum
{
  DESTROY,
  LAST_SIGNAL
};

struct _SimSslPrivate
{
#if OPENSSL_VERSION_NUMBER < 0x01000000L
  SSL_METHOD       * method;
#else
  const SSL_METHOD * method;
#endif
  SSL_CTX          * ctx;
  SSL              * state;
  gint               sd;
};

extern SimMain ossim;
static gpointer parent_class = NULL;

#define DEFAULT_SSL_PATH "/var/alienvault/ssl"

// Static declarations.
static gint ssl_init = 0;
static GMutex  * mutex_array = NULL;
static guint64 * mutex_count_array = NULL;

#if OPENSSL_VERSION_NUMBER < 0x01000000L
typedef SSL_METHOD *(*method_func) (void);
#else
typedef const SSL_METHOD *(*method_func) (void);
#endif
method_func ssl_method[MAX_SIDES][MAX_METHODS] = {{SSLv3_client_method, TLSv1_client_method}, {SSLv3_server_method, TLSv1_server_method}};

static void sim_ssl_init (void);
static void sim_ssl_clear (void);
static gchar * sim_ssl_error_msg (gint error_code);
static void sim_ssl_lock_init (void);
static void sim_ssl_lock_clear (void);

static guint64 _sim_ssl_thread_id (void);
static void _sim_ssl_lock (gint mode, gint type, gchar * file, gint line);

/* GType Functions */

static void
sim_ssl_impl_dispose (GObject * gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void
sim_ssl_impl_finalize (GObject * gobject)
{
  SimSsl * ssl = SIM_SSL (gobject);

  if (ssl->_priv->state)
    SSL_free (ssl->_priv->state);

  if (ssl->_priv->ctx)
    SSL_CTX_free (ssl->_priv->ctx);

  ssl->_priv->sd = 0;
  g_free (ssl->_priv);

  sim_ssl_clear ();

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
  return;
}

static void
sim_ssl_class_init (SimSslClass * class)
{
  GObjectClass * object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_peek_parent (class);

  object_class->dispose = sim_ssl_impl_dispose;
  object_class->finalize = sim_ssl_impl_finalize;
  return;
}

static void
sim_ssl_instance_init (SimSsl * server)
{
  server->_priv = g_new0 (SimSslPrivate, 1);
  return;
}

GType
sim_ssl_get_type (void)
{
  static GType object_type = 0;

  if (!object_type)
  {
    static const GTypeInfo type_info = {
      sizeof (SimSslClass),
      NULL,
      NULL,
      (GClassInitFunc) sim_ssl_class_init,
      NULL,
      NULL,                       /* class data */
      sizeof (SimSsl),
      0,                          /* number of pre-allocs */
      (GInstanceInitFunc) sim_ssl_instance_init,
      NULL                        /* value table */
    };

    object_type = g_type_register_static (G_TYPE_OBJECT, "SimSsl", &type_info, 0);
  }
  return object_type;
}

/**
 * sim_ssl_init:
 *
 * Initializes the OpenSSL subsystem.
 */
static
void
sim_ssl_init ()
{
  // Initialize subsystems only one time, and keep a reference count.
#if GLIB_CHECK_VERSION(2,30,0)
  if (g_atomic_int_add (&ssl_init, 1) != 0)
#else
  if (g_atomic_int_exchange_and_add (&ssl_init, 1) != 0)
#endif
    return;

  // Load all OpenSSL subsystems (error messaging, IO, algorithms).
  SSL_load_error_strings();
  (void)SSL_library_init ();

  // Prepare for multithreading.
  sim_ssl_lock_init ();

  g_message ("SSL subsystem initialized");

  return;
}

/**
 * sim_ssl_clear:
 *
 * Cleans the SSL subsystem global variables.
 */
static
void
sim_ssl_clear ()
{
#if GLIB_CHECK_VERSION(2,30,0)
  if (g_atomic_int_add (&ssl_init, -1) != 0)
#else
  if (g_atomic_int_exchange_and_add (&ssl_init, -1) != 0)
#endif
    return;

  sim_ssl_lock_clear ();

  return;
}

/**
 * sim_ssl_new_full:
 * @sd: a socket descriptor.
 * @side: a #SIM_SSL_SIDE value, could be #SSL_CLIENT or #SSL_SERVER.
 * @method: a #SIM_SSL_METHOD value, could be #SIM_SSLv3, #SIM_TLSv1 or #SIM_TLSv1_1.
 * @cert_file: path to the local certificate file.
 * @key_file: path to the local private key file.
 * @force_client_cert: force the client side to have a valid certificate (#SSL_SERVER side only)
 *
 * Initialize a new SSL object with the attributes provided.
 * Returns: a new #SimSsl object.
 */
SimSsl *
sim_ssl_new_full (gint sd, SIM_SSL_SIDE side, SIM_SSL_METHOD method,
                  const gchar * cert_file, const gchar * key_file,
                  gboolean force_client_cert)
{
  SimSsl * ssl = NULL;

  g_return_val_if_fail ((sd > 0), NULL);
  g_return_val_if_fail ((side < MAX_SIDES), NULL);
  g_return_val_if_fail ((method < MAX_METHODS), NULL);
  g_return_val_if_fail (cert_file, NULL);
  g_return_val_if_fail (key_file, NULL);

  ssl = SIM_SSL (g_object_new (SIM_TYPE_SSL, NULL));

  // Initialize SSL subsystem and create a new context.
  sim_ssl_init ();
  ssl->_priv->method = ssl_method[side][method]();
  if (!(ssl->_priv->ctx = SSL_CTX_new (ssl->_priv->method)))
  {
    g_object_unref (ssl);
    g_warning ("Cannot create a new SSL context: %s", ERR_error_string (ERR_get_error(), NULL));
    return (NULL);
  }

  // Restrict SSL cipher subset.
  (void)SSL_CTX_set_options (ssl->_priv->ctx, SSL_OP_NO_SSLv2);
  if (SSL_CTX_set_cipher_list (ssl->_priv->ctx, "HIGH:!DSS:!aNULL@STRENGTH") != 1)
  {
    g_object_unref (ssl);
    g_warning ("Cannot set a strong cipher list for a SSL context: %s", ERR_error_string (ERR_get_error(), NULL));
    return (NULL);
  }

  // Set certificate and private key.
  if (SSL_CTX_use_certificate_file (ssl->_priv->ctx, cert_file, SSL_FILETYPE_PEM) <= 0)
  {
    g_object_unref (ssl);
    g_warning ("Cannot load certificate file %s: %s", cert_file, ERR_error_string (ERR_get_error(), NULL));
    return (NULL);
  }

  if (SSL_CTX_use_PrivateKey_file (ssl->_priv->ctx, key_file, SSL_FILETYPE_PEM) <= 0)
  {
    g_object_unref (ssl);
    g_warning ("Cannot load private key file %s: %s", key_file, ERR_error_string (ERR_get_error(), NULL));
    return (NULL);
  }

  if (!SSL_CTX_check_private_key(ssl->_priv->ctx))
  {
    g_object_unref (ssl);
    g_warning ("Private key %s does not match certificate %s: %s", key_file, cert_file, ERR_error_string (ERR_get_error(), NULL));
    return (NULL);
  }

  // Must the client side have a valid certificate?
  if ((side == SSL_SERVER) && (force_client_cert))
  {
    SSL_CTX_set_verify (ssl->_priv->ctx, SSL_VERIFY_PEER | SSL_VERIFY_FAIL_IF_NO_PEER_CERT, NULL);
    SSL_CTX_set_verify_depth (ssl->_priv->ctx, 2);
  }

  // Finally, create the SSL connection state and link the socket descriptor.
  ssl->_priv->sd = sd;

  if (!(ssl->_priv->state = SSL_new (ssl->_priv->ctx)))
  {
    g_object_unref (ssl);
    g_warning ("Cannot create SSL connection state: %s", ERR_error_string (ERR_get_error(), NULL));
    return (NULL);
  }

  if (SSL_set_fd(ssl->_priv->state, ssl->_priv->sd) != 1)
  {
    g_object_unref (ssl);
    g_warning ("Cannot link the socket descriptor to the SSL connection state: %s", ERR_error_string (ERR_get_error(), NULL));
    return (NULL);
  }

  g_debug ("Created a new %s SSL socket with descriptor %d", (side == SSL_CLIENT ? "Client" : "Server"), ssl->_priv->sd);
  return (ssl);
}

/**
 * sim_ssl_server_new:
 * @socket: a #GTcpSocket object.
 *
 * Initialize a new SSL server object with default parameters.
 * Returns: a new #SimSsl object.
 */
SimSsl *
sim_ssl_server_new (GTcpSocket * socket)
{
  SimSsl * ssl = NULL;
  gchar * key_file = NULL;
  gchar * cert_file = NULL;
  GIOChannel * chan = NULL;
  gint sd = 0;

  g_return_val_if_fail (socket, NULL);

  if (!(chan = gnet_tcp_socket_get_io_channel (socket)))
  {
    g_warning ("Could not get IO channel from socket while creating a SSL socket");
    return (NULL);
  }

  if ((sd = g_io_channel_unix_get_fd (chan)) <= 0)
  {
    g_warning ("Could not get a descriptor from the IO channel while creating a SSL socket");
    return (NULL);
  }

  cert_file = g_strdup_printf ("%s/%s/cert_%s.pem", DEFAULT_SSL_PATH,
                               sim_uuid_get_string (ossim.config->component_id),
                               sim_uuid_get_string (ossim.config->component_id));
  key_file = g_strdup_printf ("%s/%s/key_%s.pem", DEFAULT_SSL_PATH,
                              sim_uuid_get_string (ossim.config->component_id),
                              sim_uuid_get_string (ossim.config->component_id));

  // Call sim_ssl_new_full() with default parameters.
  ssl = sim_ssl_new_full (sd, SSL_SERVER, SSL_TLSv1, cert_file, key_file, TRUE);
  g_free (key_file);
  g_free (cert_file);

  return (ssl);
}

/**
 * sim_ssl_client_new:
 * @socket: a #GTcpSocket object.
 *
 * Initialize a new SSL client object with default parameters.
 * Returns: a new #SimSsl object.
 */
SimSsl *
sim_ssl_client_new (GTcpSocket * socket)
{
  SimSsl * ssl = NULL;
  gchar * key_file = NULL;
  gchar * cert_file = NULL;
  GIOChannel * chan = NULL;
  gint sd = 0;

  g_return_val_if_fail (socket, NULL);

  if (!(chan = gnet_tcp_socket_get_io_channel (socket)))
  {
    g_warning ("Could not get IO channel from socket while creating a SSL socket");
    return (NULL);
  }

  if ((sd = g_io_channel_unix_get_fd (chan)) <= 0)
  {
    g_warning ("Could not get a descriptor from the IO channel while creating a SSL socket");
    return (NULL);
  }

  cert_file = g_strdup_printf ("%s/%s/cert_%s.pem", DEFAULT_SSL_PATH,
                               sim_uuid_get_string (ossim.config->component_id),
                               sim_uuid_get_string (ossim.config->component_id));
  key_file = g_strdup_printf ("%s/%s/key_%s.pem", DEFAULT_SSL_PATH,
                              sim_uuid_get_string (ossim.config->component_id),
                              sim_uuid_get_string (ossim.config->component_id));

  // Call sim_ssl_new_full() with default parameters.
  ssl = sim_ssl_new_full (sd, SSL_CLIENT, SSL_TLSv1, key_file, cert_file, FALSE);
  g_free (key_file);
  g_free (cert_file);

  return (ssl);
}

/**
 * sim_ssl_get_remote_certificate:
 * @ssl: a #SimSsl object.
 *
 * Prints info about the remote certificate of the connection
 * linked to @ssl.
 */
void
sim_ssl_get_remote_certificate (SimSsl * ssl)
{
  X509 * cert = NULL;
  char * subject = NULL, * issuer = NULL;

  g_return_if_fail (ssl);
  g_return_if_fail (ssl->_priv->state);

  cert = SSL_get_peer_certificate (ssl->_priv->state);

  if (cert != NULL)
  {
    subject = X509_NAME_oneline (X509_get_subject_name(cert), 0, 0);
    issuer = X509_NAME_oneline (X509_get_issuer_name(cert), 0, 0);

    g_message ("Certificate subject name: %s", subject);
    g_message ("Certificate issuer name: %s", issuer);

    X509_free (cert);
    g_free (subject);
    g_free (issuer);
  }
  else
    g_message ("No remote certificate available");

  return;
}

/**
 * sim_ssl_write:
 * @ssl: a #SimSsl object.
 * @data: the data being sent.
 * @data_len: the length of @data.
 *
 * Writes at most @data_len bytes of @data to the socket.
 */
gint
sim_ssl_write (SimSsl * ssl, gchar * data, gsize data_len)
{
  gint ret = 0;
  gchar * error_msg = NULL;

  g_return_val_if_fail (ssl, -1);
  g_return_val_if_fail (ssl->_priv->state, -1);
  g_return_val_if_fail ((ssl->_priv->sd <= 0), -1);
  g_return_val_if_fail (data, -1);
  g_return_val_if_fail ((data_len <= 0), -1);

  ret = SSL_write (ssl->_priv->state, data, data_len);

  if (ret <= 0)
  {
    // Error that may be related to the SSL socket.
    error_msg = sim_ssl_error_msg (ret);
    g_warning ("Cannot write to SSL socket: %s", error_msg);
    g_free (error_msg);
  }
  else
    g_debug ("Bytes written to SSL socket %d: %d", ssl->_priv->sd, ret);

  return (ret);
}


/**
 * sim_ssl_read:
 * @ssl: a #SimSsl object.
 * @data: a previously allocated buffer to hold received data.
 * @data_len: the length of data being read.
 *
 * Reads at most @data_len bytes of @data from the socket.
 */
gint
sim_ssl_read (SimSsl * ssl, gchar * data, gsize data_len)
{
  gint ret = 0;
  gchar * error_msg = NULL;

  g_return_val_if_fail (ssl, -1);
  g_return_val_if_fail (ssl->_priv->state, -1);
  g_return_val_if_fail ((ssl->_priv->sd <= 0), -1);
  g_return_val_if_fail (data, -1);
  g_return_val_if_fail ((data_len <= 0), -1);

  ret = SSL_read (ssl->_priv->state, data, data_len);

  if (ret <= 0)
  {
    // Error that may be related to the SSL socket.
    error_msg = sim_ssl_error_msg (ret);
    g_warning ("Cannot write to SSL socket: %s", error_msg);
    g_free (error_msg);
  }
  else
    g_debug ("Bytes read from SSL socket %d: %d", ssl->_priv->sd, ret);

  return (ret);
}

/**
 * sim_ssl_accept:
 * @ssl: a #SimSsl object.
 *
 * Blocks the SSL socket until a new connection arrives.
 * Returns: 1 if the connection was successful, 0 if the connection
 * was previously cleanly closed, < 0 otherwise.
 */
gint
sim_ssl_accept (SimSsl * ssl)
{
  gint ret = 0;
  gchar * error_msg = NULL;

  g_return_val_if_fail (ssl, -1);
  g_return_val_if_fail (ssl->_priv->state, -1);
  g_return_val_if_fail ((ssl->_priv->sd <= 0), -1);

  ret = SSL_accept (ssl->_priv->state);

  if (ret != 1)
  {
    error_msg = sim_ssl_error_msg (ret);
    g_warning ("Accept operation on SSL socket failed: %s", error_msg);
    g_free (error_msg);
  }

  return (ret);
}

/**
 * sim_ssl_connect:
 * @ssl: a #SimSsl object.
 *
 * Try to connect and do a handshake with a server.
 * Returns: 1 if the connection was successful, 0 if the connection
 * was previously cleanly closed, < 0 otherwise.
 */
gint
sim_ssl_connect (SimSsl * ssl)
{
  gint ret = 0;
  gchar * error_msg = NULL;

  g_return_val_if_fail (ssl, -1);
  g_return_val_if_fail (ssl->_priv->state, -1);
  g_return_val_if_fail ((ssl->_priv->sd <= 0), -1);

  ret = SSL_connect (ssl->_priv->state);

  if (ret != 1)
  {
    error_msg = sim_ssl_error_msg (ret);
    g_warning ("Connect operation on SSL socket failed: %s", error_msg);
    g_free (error_msg);
  }

  return (ret);
}

/**
 * sim_ssl_shutdown:
 * @ssl: a #SimSsl object.
 *
 * Shuts down a SSL connection.
 * Returns: 1 if the operation was successful, 0 if there is
 * a pending shutdown operation going on, < 0 otherwise.
 */
gint
sim_ssl_shutdown (SimSsl * ssl)
{
  gint ret = 0;
  gchar * error_msg = NULL;

  g_return_val_if_fail (ssl, -1);
  g_return_val_if_fail (ssl->_priv->state, -1);
  g_return_val_if_fail ((ssl->_priv->sd <= 0), -1);

  ret = SSL_shutdown (ssl->_priv->state);

  switch (ret)
  {
  case 0:
    g_message ("There is already a shutdown operation going on socket %d", ssl->_priv->sd);
    break;

  case 1:
    break;

  default:
    error_msg = sim_ssl_error_msg (ret);
    g_warning ("Shutdown operation on SSL socket failed: %s", error_msg);
    g_free (error_msg);
  };

  return (ret);
}

/**
 * sim_ssl_error_msg:
 * @error_code: a #gint denoting a SSL error code.
 *
 * Converts a error code to a human readable string.
 * Returns: a newly allocated string with the error message.
 */
static
gchar *
sim_ssl_error_msg (gint error_code)
{
  gchar * error_msg = NULL;

  switch (error_code)
  {
  case SSL_ERROR_NONE:
    error_msg = g_strdup_printf ("No error");
    break;
  case SSL_ERROR_ZERO_RETURN:
    error_msg = g_strdup_printf ("Connection has already been closed cleanly");
    break;
  case SSL_ERROR_WANT_READ:
    error_msg = g_strdup_printf ("Read operation has not been completed");
    break;
  case SSL_ERROR_WANT_WRITE:
    error_msg = g_strdup_printf ("Write operation has not been completed");
    break;
  case SSL_ERROR_WANT_CONNECT:
    error_msg = g_strdup_printf ("Connect operation has not been completed");
    break;
  case SSL_ERROR_WANT_ACCEPT:
    error_msg = g_strdup_printf ("Accept operation has not been completed");
    break;
  case SSL_ERROR_WANT_X509_LOOKUP:
    error_msg = g_strdup_printf ("I/O operation interrupted by an application callback");
    break;
  case SSL_ERROR_SYSCALL:
    error_msg = g_strdup_printf ("An underlying I/O operation has returned an error: %s", ERR_error_string (ERR_get_error(), NULL));
    break;
  case SSL_ERROR_SSL:
    error_msg = g_strdup_printf ("SSL subsystem has failed: %s", ERR_error_string (ERR_get_error(), NULL));
    break;
  default:
    error_msg = g_strdup_printf ("Unknown error");
  }

  return (error_msg);
}

/**
 * sim_ssl_lock_init:
 *
 * Initializes the SSL thread-safe callback subsystem.
 */
static
void
sim_ssl_lock_init ()
{
  gint i = 0;

  if (g_atomic_int_get (&ssl_init) > 0)
    return;

  mutex_array = OPENSSL_malloc (CRYPTO_num_locks() * sizeof(GMutex));
  mutex_count_array = OPENSSL_malloc (CRYPTO_num_locks() * sizeof(guint64));

  for (i = 0; i < CRYPTO_num_locks(); i++)
  {
    g_mutex_init (&mutex_array[i]);
    mutex_count_array[i] = 0;
  }

  CRYPTO_set_id_callback ((guint64 (*)())_sim_ssl_thread_id);
  CRYPTO_set_locking_callback((void (*)())_sim_ssl_lock);
}

/**
 * sim_ssl_lock_clear:
 *
 * Cleans all the mutex structures used for SSL objects.
 */
static
void
sim_ssl_lock_clear ()
{
  gint i = 0;

  if (g_atomic_int_get (&ssl_init) != 0)
    return;

  CRYPTO_set_locking_callback (NULL);

  for (i = 0; i < CRYPTO_num_locks(); i++)
  {
    g_mutex_clear (&mutex_array[i]);
  }
  OPENSSL_free(mutex_array);
  OPENSSL_free(mutex_count_array);

  return;
}

/**
 * _sim_ssl_thread_id:
 *
 * Returns: a decimal id for this particular thread.
 */
static
guint64
_sim_ssl_thread_id ()
{
  return ((guint64)g_thread_self ());
}


/**
 * _sim_ssl_lock:
 * @mode: the operation being run for this SSL instance.
 * @idx: decimal index of the thread in its array.
 * @file: name of the file where the thread is being executed.
 * @line: line number inside @file.
 *
 * OpenSSL callback to manage locks between threads.
 */
static
void
_sim_ssl_lock (gint mode, gint idx, gchar * file, gint line)
{
  (void)file;
  (void)line;

  if (mode & CRYPTO_LOCK)
  {
    g_mutex_lock (&mutex_array[idx]);
    mutex_count_array[idx]++;
  }
  else
  {
    g_mutex_unlock (&mutex_array[idx]);
    mutex_count_array[idx]--;  // Not sure if this should be here. Delete if it does not work.
  }

  return;
}
