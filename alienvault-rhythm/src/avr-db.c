/*
  License:

  Copyright (c) 2015 AlienVault
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

#include "avr-db.h"

#include <signal.h>
#include <hiredis/hiredis.h>

struct _AvrDbPrivate
{
  AvrType              type;

  gchar *              hostname;
  gint                 port;
  gchar *              socket;

  redisContext *       ctx;
  redisContext *       subscriber_ctx;

  gpointer             data;
};

static gpointer parent_class = NULL;

/* OTX data loaded. Greater than 0 when there is nothing in the redis DB */
static gint otx_data_loaded = 0;

// Private declarations
// static gchar * _avr_db_get_string (AvrDb *, gchar *);
static GPtrArray * _avr_db_get_array (AvrDb *, gchar *);
static gboolean _avr_db_load_data (AvrDb *);
static gboolean _avr_db_load_ip_addresses_in_rtree (AvrDb *);
static gboolean _avr_db_load_strings_in_htable (AvrDb *);
static gboolean _avr_db_connect (AvrDb *);
static gpointer _avr_db_subscribe (gpointer);

// GType Functions

static void
avr_db_impl_dispose (GObject * gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void
avr_db_impl_finalize (GObject * gobject)
{
  AvrDb * db = AVR_DB (gobject);

  if (db->_priv->hostname)
    g_free(db->_priv->hostname);

  if (db->_priv->socket)
    g_free(db->_priv->socket);

  if (db->_priv->ctx)
    redisFree(db->_priv->ctx);

  if (db->_priv->data)
    avr_db_unref_data (db, db->_priv->data);

  g_free (db->_priv);

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
avr_db_class_init (AvrDbClass * class)
{
  GObjectClass * object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_peek_parent (class);

  object_class->dispose = avr_db_impl_dispose;
  object_class->finalize = avr_db_impl_finalize;
}

static void
avr_db_instance_init (AvrDb * db)
{
  db->_priv = g_new0 (AvrDbPrivate, 1);

  return;
}

GType
avr_db_get_type (void)
{
  static GType object_type = 0;

  if (!object_type)
  {
    static const GTypeInfo type_info = {
      sizeof (AvrDbClass),
      NULL,
      NULL,
      (GClassInitFunc) avr_db_class_init,
      NULL,
      NULL,                       /* class data */
      sizeof (AvrDb),
      0,                          /* number of pre-allocs */
      (GInstanceInitFunc) avr_db_instance_init,
      NULL                        /* value table */
    };

    object_type = g_type_register_static (G_TYPE_OBJECT, "AvrDb", &type_info, 0);
  }
  return object_type;
}

//
// Public methods
//

/**
 * avr_db_init:
 *
 *
 */
void
avr_db_init (void)
{

}

/**
 * avr_db_clear:
 *
 *
 */
void
avr_db_clear (void)
{
}


//
// TODO: refactor this.
//

/**
 * avr_db_new_tcp:
 * @void
 *
 *
 * Returns:
 */
AvrDb *
avr_db_new_tcp (AvrType type, gchar * hostname, gint port)
{
  g_return_val_if_fail (hostname, NULL);
  g_return_val_if_fail ((port > 0) && (port < 65536), NULL);
  g_return_val_if_fail ((port > -1) && (port < 16), NULL);

  AvrDb * db = NULL;

  db = AVR_DB (g_object_new (AVR_TYPE_DB, NULL));
  db->_priv->type = type;
  db->_priv->hostname = g_strdup_printf("%s", hostname);
  db->_priv->port = port;

  if (!(_avr_db_load_data (db)))
  {
    g_object_unref (db);
    return (NULL);
  }

  // Subscribe for messages
  (void)g_thread_new("_avr_db_subscribe", (GThreadFunc)_avr_db_subscribe, (gpointer)db);

  return (db);
}

/**
 * avr_db_new_unix:
 * @void
 *
 *
 * Returns:
 */
AvrDb *
avr_db_new_unix (AvrType type, const gchar * socket)
{
  g_return_val_if_fail (socket, NULL);

  AvrDb * db = NULL;

  db = AVR_DB (g_object_new (AVR_TYPE_DB, NULL));
  db->_priv->type = type;
  db->_priv->socket = g_strdup_printf("%s", socket);

  if (!(_avr_db_load_data (db)))
  {
    g_object_unref (db);
    return (NULL);
  }

  // Subscribe for messages
  (void)g_thread_new("_avr_db_subscribe", (GThreadFunc)_avr_db_subscribe, (gpointer)db);

  return (db);
}

/**
 * avr_db_ref_data:
 * @void
 *
 *
 * Returns:
 */
gpointer
avr_db_ref_data (AvrDb * db)
{
  g_return_val_if_fail (AVR_IS_DB(db), NULL);

  switch (db->_priv->type)
  {
  case IP_ADDRESS:
    return (radix_tree_ref (db->_priv->data));

  case FILE_HASH:
  case DOMAIN:
  case HOSTNAME:
    return (g_hash_table_ref (db->_priv->data));

  default: ;
  }

  return (NULL);
}


/**
 * avr_db_unref_data:
 * @void
 *
 *
 * Returns:
 */
void
avr_db_unref_data (AvrDb * db, gpointer data)
{
  g_return_if_fail (AVR_IS_DB(db));

  switch (db->_priv->type)
  {
  case IP_ADDRESS:
    radix_tree_unref (data);
    break;

  case FILE_HASH:
  case DOMAIN:
  case HOSTNAME:
    g_hash_table_unref (data);
    break;

  default: ;
  }

  return;
}


/**
 * avr_db_has_otx_data:
 * @void
 *
 *
 * Returns: TRUE if there is any OTX IoC in the DB
 */
gboolean
avr_db_has_otx_data ()
{
  return (g_atomic_int_get(&otx_data_loaded) > 0);
}

//
// Private methods
//

/**
 * _avr_db_load_data:
 * @void
 *
 *
 * Returns:
 */
static gboolean
_avr_db_load_data (AvrDb * db)
{
  g_return_val_if_fail (AVR_IS_DB(db), FALSE);

  signal(SIGPIPE, SIG_IGN);

  redisReply * reply = NULL;

  if (db->_priv->ctx)
  {
    reply = redisCommand(db->_priv->ctx, "PING");
    if (db->_priv->ctx->err == 0)
    {
      freeReplyObject(reply);
    }
    else
    {
      // Reconnect.
      if (!(_avr_db_connect (db)))
        return (FALSE);
    }
  }
  else
  {
    // Reconnect.
    if (!(_avr_db_connect (db)))
      return (FALSE);
  }

  switch (db->_priv->type)
  {
  case IP_ADDRESS:
    return (_avr_db_load_ip_addresses_in_rtree (db));

  case FILE_HASH:
  case DOMAIN:
  case HOSTNAME:
    return (_avr_db_load_strings_in_htable (db));

  default: ;
  }

  return (FALSE);
}


/**
 * _avr_db_get_array:
 * @void
 *
 *
 * Returns:
 */
static GPtrArray *
_avr_db_get_array (AvrDb * db, gchar * key)
{
  g_return_val_if_fail (AVR_IS_DB(db), NULL);
  g_return_val_if_fail (key, NULL);

  redisReply * reply = NULL;
  guint i = 0;
  GPtrArray * value_array = NULL;

  reply = redisCommand(db->_priv->ctx, "SMEMBERS %s", key);
  if (reply)
  {
    switch (reply->type)
    {
    case REDIS_REPLY_ARRAY:
      value_array = g_ptr_array_new_with_free_func ((GDestroyNotify)g_free);

      // First element is always the key.
      g_ptr_array_add (value_array, g_strdup_printf ("%s", key));

      for (i = 0; i < reply->elements; i++)
        g_ptr_array_add (value_array, g_strdup_printf ("%s", reply->element[i]->str));

      break;
    case REDIS_REPLY_NIL:
      break;
    case REDIS_REPLY_ERROR:
      g_warning("Error in query for key \"%s\": %s", key, reply->str);
      break;
    default:
      g_warning("Invalid type on query for key \"%s\"", key);
    }
    freeReplyObject(reply);
  }
  else
  {
    if (db->_priv->ctx->errstr)
      g_warning("Error on query for key \"%s\": %s", key, db->_priv->ctx->errstr);
    else
      g_warning("Error on query for key \"%s\"", key);
  }

  return (value_array);
}

/**
 * _avr_db_load_ip_addresses_in_rtree:
 * @void
 *
 *
 * Returns:
 */
static gboolean
_avr_db_load_ip_addresses_in_rtree (AvrDb * db)
{
  g_return_val_if_fail (AVR_IS_DB(db), FALSE);
  g_return_val_if_fail (db->_priv->type == IP_ADDRESS, FALSE);

  redisReply * keys = NULL;
  RadixTree * tree = NULL;
  gpointer old_tree = NULL;

  GInetAddress * address = NULL;
  gchar * address_str = NULL;
  gchar ** address_str_splitted = NULL;
  guint8 * address_bytes = NULL;

  guint i = 0;
  guint total = 0;

  keys = redisCommand(db->_priv->ctx, "KEYS *");
  if (keys)
  {
    switch (keys->type)
    {
    case REDIS_REPLY_ARRAY:
      tree = radix_tree_new_with_destroy_func((GDestroyNotify)g_ptr_array_unref);

      for (i = 0; i < keys->elements; i++)
      {
        address_str = keys->element[i]->str;
        address_str_splitted = g_strsplit((const gchar *)address_str, "/", 2);
        if (address_str_splitted[1] != NULL)
        {
          // This is a CIDR.
          address = g_inet_address_new_from_string(address_str_splitted[0]);
          if (address != NULL)
          {
            address_bytes = (guint8 *)g_inet_address_to_bytes(address);

            // Add to the tree.
            radix_tree_insert(tree, (const guint8 *)address_bytes, (guint)g_ascii_strtoull(address_str_splitted[1], NULL, 0), (gpointer)_avr_db_get_array(db, keys->element[i]->str));

            g_object_unref (address);
            total ++;
          }
          else
            g_warning ("Value \"%s\" is not a valid CIDR", address_str);
        }
        else
        {
          // This is a regular IP address.
          address = g_inet_address_new_from_string(address_str);
          if (address != NULL)
          {
            address_bytes = (guint8 *)g_inet_address_to_bytes(address);

            // Add to the tree.
            radix_tree_insert(tree, (const guint8 *)address_bytes, 32, (gpointer)_avr_db_get_array(db, keys->element[i]->str));

            g_object_unref (address);
            total ++;
          }
          else
            g_warning ("Value \"%s\" is not a valid IP address", address_str);
        }

        g_strfreev(address_str_splitted);

      }
      if (total > 0)
      {
        // add the type mask to the value
        (void) __sync_or_and_fetch(&otx_data_loaded, (db->_priv->type + 1));
      }
      else
      {
        // Remove the type mask to the value
        (void) __sync_and_and_fetch(&otx_data_loaded, ~(db->_priv->type + 1));
      }

      g_message("Loaded %d keys from database %d", total, db->_priv->type);
      break; // REDIS_REPLY_ARRAY

    case REDIS_REPLY_NIL:
      break;
    case REDIS_REPLY_ERROR:
      g_warning("Error in query for all keys");
      break;
    default:
      g_warning("Invalid type on query for all keys");
    }
    freeReplyObject(keys);
  }
  else
  {
    if (db->_priv->ctx->errstr)
      g_warning("Error on query for all keys: %s", db->_priv->ctx->errstr);
    else
      g_warning("Error on query for all keys");
  }

  old_tree = __sync_lock_test_and_set (&db->_priv->data, tree);
  if (old_tree != NULL)
    radix_tree_unref (old_tree);

  return (TRUE);
}

/**
 * _avr_db_load_strings_in_htable:
 * @void
 *
 *
 * Returns:
 */
static gboolean
_avr_db_load_strings_in_htable (AvrDb * db)
{
  g_return_val_if_fail (AVR_IS_DB(db), FALSE);
  g_return_val_if_fail ((db->_priv->type == FILE_HASH) ||
                        (db->_priv->type == DOMAIN) ||
                        (db->_priv->type == HOSTNAME)
                        , FALSE);

  redisReply * keys = NULL;
  GHashTable * table = NULL;
  gpointer old_table = NULL;
  gchar * key = NULL;
  GPtrArray * value_array = NULL;
  guint i = 0;
  guint total = 0;

  keys = redisCommand(db->_priv->ctx, "KEYS *");
  if (keys)
  {
    switch (keys->type)
    {
    case REDIS_REPLY_ARRAY:
      table = g_hash_table_new_full(g_str_hash, g_str_equal, (GDestroyNotify)g_free, (GDestroyNotify)g_ptr_array_unref);

      for (i = 0; i < keys->elements; i++)
      {
        key = g_strdup_printf("%s", keys->element[i]->str);
        value_array = _avr_db_get_array (db, key);
        g_hash_table_insert(table, (gpointer)key, (gpointer)value_array);
        total ++;
      }
      if (total > 0)
      {
        // Add the type mask to the value
        (void) __sync_or_and_fetch(&otx_data_loaded, (db->_priv->type + 1));
      }
      else
      {
        // Remove the type mask to the value
        (void) __sync_and_and_fetch(&otx_data_loaded, ~(db->_priv->type + 1));
      }

      g_message("Loaded %d keys from database %d", total, db->_priv->type);
      break; // REDIS_REPLY_ARRAY

    case REDIS_REPLY_NIL:
      break;
    case REDIS_REPLY_ERROR:
      g_warning("Error in query for all keys");
      break;
    default:
      g_warning("Invalid type on query for all keys");
    }
    freeReplyObject(keys);
  }
  else
  {
    if (db->_priv->ctx->errstr)
      g_warning("Error on query for all keys: %s", db->_priv->ctx->errstr);
    else
      g_warning("Error on query for all keys");
  }

  old_table = __sync_lock_test_and_set (&db->_priv->data, table);
  if (old_table != NULL)
    g_hash_table_unref (old_table);

  return (TRUE);
}

/**
 * _avr_db_connect:
 * @void
 *
 *
 * Returns:
 */
static gboolean
_avr_db_connect (AvrDb * db)
{
  g_return_val_if_fail (AVR_IS_DB(db), FALSE);

  // Ignore SIGPIPE.
  signal(SIGPIPE, SIG_IGN);

  redisReply * reply = NULL;

  // Connect both contexts.
  if (db->_priv->socket)
    db->_priv->ctx = redisConnectUnix(db->_priv->socket);
  else
    db->_priv->ctx = redisConnect(db->_priv->hostname, db->_priv->port);

  if ((db->_priv->ctx == NULL) || (db->_priv->ctx->err))
  {
    if (db->_priv->ctx->err)
    {
      g_warning ("Cannot create connection to database: %s", db->_priv->ctx->errstr);
    }
    else
    {
      g_warning ("Cannot create connection to database: can't allocate redis context");
    }

    return (FALSE);
  }

  // Select the database.
  reply = redisCommand(db->_priv->ctx, "SELECT %d", db->_priv->type);
  if (reply)
  {
    switch (reply->type)
    {
    case REDIS_REPLY_ERROR:
      g_warning ("Error selecting database: %s", reply->str);
      return (FALSE);
    default:
      break;
    }
    freeReplyObject(reply);
  }
  else
  {
    if (db->_priv->ctx->errstr)
      g_warning ("Error selecting database: %s", db->_priv->ctx->errstr);
    else
      g_warning ("Error selecting database");

    return (FALSE);
  }

  return (TRUE);
}


/**
 * _avr_db_subscribe:
 * @void
 *
 *
 * Returns:
 */
static gpointer
_avr_db_subscribe (gpointer avr_db_pointer)
{
  g_return_val_if_fail (avr_db_pointer != NULL, NULL);

  // Ignore SIGPIPE.
  signal(SIGPIPE, SIG_IGN);

  AvrDb * db = AVR_DB (avr_db_pointer);
  redisReply * reply = NULL;
  gchar * command = NULL;
  const gchar * avr_type_names[] = {"IP Address", "File Hash", "Domain", "Hostname", NULL};
  gboolean reconnect = FALSE;

  while (TRUE)
  {
    // Initialize the subscriber context.
    if ((db->_priv->hostname != NULL) && (db->_priv->port != 0))
    {
      db->_priv->subscriber_ctx = redisConnect(db->_priv->hostname, db->_priv->port);
      if ((db->_priv->subscriber_ctx == NULL) || (db->_priv->subscriber_ctx->errstr))
      {
        if (db->_priv->subscriber_ctx)
          g_warning("Cannot create subscriber connection to \"%s:%d\": %s", db->_priv->hostname, db->_priv->port, db->_priv->subscriber_ctx->errstr);
        else
          g_warning("Cannot create subscriber connection to \"%s:%d\"", db->_priv->hostname, db->_priv->port);
      }
    }
    else
    {
      db->_priv->subscriber_ctx = redisConnectUnix(db->_priv->socket);
      if ((db->_priv->subscriber_ctx == NULL) || (db->_priv->subscriber_ctx->err))
      {
        if (db->_priv->subscriber_ctx->err)
          g_warning("Cannot create subscriber connection using socket \"%s\": %s", db->_priv->socket, db->_priv->subscriber_ctx->errstr);
        else
          g_warning("Cannot create subscriber connection using socket \"%s\": can't allocate redis context", db->_priv->socket);
      }
    }

    if (db->_priv->subscriber_ctx->err != 0)
    {
      // Could not connect. It surely needs to be reconnected, then.
      g_usleep (G_USEC_PER_SEC * 10);
      reconnect = TRUE;
    }
    else
    {
      g_message("Subscriber connection created for data type \"%s\"", avr_type_names[db->_priv->type]);

      // If this is have been trying to reconnect before, reload data first.
      if (reconnect)
      {
        (void)_avr_db_load_data (db);
        reconnect = FALSE;
      }

      command = g_strdup_printf("PSUBSCRIBE __keyspace@%d__:cmd", db->_priv->type);
      reply = redisCommand (db->_priv->subscriber_ctx, command);
      freeReplyObject (reply);
      g_free (command);

      while(redisGetReply (db->_priv->subscriber_ctx, (gpointer *)&reply) == REDIS_OK)
      {
        if ((reply != NULL) && (reply->elements == 4))
        {
          if (g_ascii_strncasecmp ("sync", reply->element[3]->str, 4) == 0)
          {
            if (!(_avr_db_load_data (db)))
              g_warning ("Cannot reload OTX data");
            else
              g_message ("OTX %s data reloaded", avr_type_names[db->_priv->type]);
          }
          else if (g_ascii_strncasecmp ("ping", reply->element[3]->str, 4) == 0)
          {
            g_message ("Received ping");
          }
          else
          {
            g_warning ("Unknown command: \"%s\"", reply->element[3]->str);
          }
        }
        freeReplyObject(reply);
      }
    }
  }

  return (NULL);
}
