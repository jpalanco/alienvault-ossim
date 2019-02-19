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

#include "avr-log.h"
#include "avr-db.h"
#include "config.h"

#include <glib/gstdio.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <unistd.h>
#include <time.h>

// Define the minimum buffer length (refcount + header + NULL)
#define TIMEBUF_SIZE  20


struct _AvrLogPrivate
{
  GMutex          file_mutex;
  GMutex          buffer_mutex;
  gchar *         log_path;
  gsize           size_limit;
  GIOChannel *    log_channel;
  gint            buffer_flushed;

  guint           handler_id;
  GLogLevelFlags  level;
  gint            lines_written;

  GHashTable *    buffer_htable;
};

static gpointer parent_class = NULL;

//
// Static declarations.
//
static gboolean _avr_log_open_file (AvrLog *);
static void _avr_log_handler (const gchar *, GLogLevelFlags, const gchar *, gpointer);
static void _avr_log_destroy_buffer (gpointer);
static void _avr_log_timestamp (char *);
static gchar * _avr_log_build_message (const gchar *, GLogLevelFlags, const gchar *);
static void _avr_log_compose_and_write (AvrLog *, gchar **);


// GType Functions

static void
avr_log_impl_dispose (GObject * gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void
avr_log_impl_finalize (GObject * gobject)
{
  AvrLog * log = AVR_LOG (gobject);

  if (log->_priv->buffer_htable)
  {
    g_mutex_lock (&log->_priv->buffer_mutex);
    g_hash_table_unref (log->_priv->buffer_htable);
    g_mutex_unlock (&log->_priv->buffer_mutex);
  }

  g_mutex_clear (&log->_priv->buffer_mutex);
  g_mutex_clear (&log->_priv->file_mutex);

  if (log->_priv->log_path)
    g_free (log->_priv->log_path);

  log->_priv->size_limit = 0;

  if (log->_priv->log_channel)
    g_io_channel_unref (log->_priv->log_channel);

  log->_priv->buffer_flushed = 0;
  log->_priv->handler_id = 0;
  log->_priv->lines_written = 0;

  g_free (log->_priv);

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
avr_log_class_init (AvrLogClass * class)
{
  GObjectClass * object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_peek_parent (class);

  object_class->dispose = avr_log_impl_dispose;
  object_class->finalize = avr_log_impl_finalize;
}

static void
avr_log_instance_init (AvrLog * log)
{
  log->_priv = g_new0 (AvrLogPrivate, 1);

  return;
}

GType
avr_log_get_type (void)
{
  static GType object_type = 0;

  if (!object_type)
  {
    static const GTypeInfo type_info = {
      sizeof (AvrLogClass),
      NULL,
      NULL,
      (GClassInitFunc) avr_log_class_init,
      NULL,
      NULL,                       /* class data */
      sizeof (AvrLog),
      0,                          /* number of pre-allocs */
      (GInstanceInitFunc) avr_log_instance_init,
      NULL                        /* value table */
    };

    object_type = g_type_register_static (G_TYPE_OBJECT, "AvrLog", &type_info, 0);
  }
  return object_type;
}

/**
 * avr_log_init:
 *
 *
 */
void
avr_log_init (void)
{

}

/**
 * avr_log_clear:
 *
 * 
 */
void
avr_log_clear (void)
{
  
}

/**
 * avr_log_new:
 * @void
 *
 * 
 * Returns: 
 */
AvrLog *
avr_log_new (const gchar * log_path, gint size_limit)
{
  g_return_val_if_fail (log_path != NULL, NULL);
  g_return_val_if_fail (size_limit >= 0, NULL);

  AvrLog * log = NULL;

  log = AVR_LOG(g_object_new (AVR_TYPE_LOG, NULL));

  g_mutex_init (&log->_priv->file_mutex);
  g_mutex_init (&log->_priv->buffer_mutex);

  log->_priv->log_path = g_strdup_printf ("%s", log_path);
  log->_priv->size_limit = size_limit;

  if (!(_avr_log_open_file (log)))
  {
    g_object_unref (log);
    return (NULL);
  }

  // This indicates how many threads, of those using this object, have "flushed" the buffers.
  log->_priv->buffer_flushed = 0;

  // The "handler_id" and "level" variables are only needed
  // when this is writing app logs, not event logs. We
  // initialize it anyway, just in case.
  log->_priv->handler_id = 0;
  log->_priv->level = G_LOG_LEVEL_INFO;

  log->_priv->lines_written = 0;

  log->_priv->buffer_htable = g_hash_table_new_full ((GHashFunc)g_direct_hash, (GEqualFunc)g_direct_equal, NULL, (GDestroyNotify)_avr_log_destroy_buffer);

  return (log);
}

/**
 * avr_log_set_level:
 * @void
 *
 * 
 * Returns: 
 */
void
avr_log_set_level (AvrLog * log, gint level)
{
  g_return_if_fail (level >= G_LOG_LEVEL_ERROR);
  g_return_if_fail (level <= G_LOG_LEVEL_DEBUG);

  log->_priv->level = level;

  return;
}

/**
 * avr_log_set_handler:
 * @void
 *
 * 
 * Returns: 
 */
void
avr_log_set_handler (AvrLog * log)
{
  g_return_if_fail (AVR_IS_LOG(log));

  log->_priv->handler_id = g_log_set_handler (G_LOG_DOMAIN, G_LOG_LEVEL_MASK | G_LOG_FLAG_FATAL | G_LOG_FLAG_RECURSION,
                                              (GLogFunc)_avr_log_handler,
                                              (gpointer)log);
  return;
}

/**
 * avr_log_get_lines_written:
 * @void
 *
 * 
 * Returns: 
 */
inline gint
avr_log_get_lines_written (AvrLog * log)
{
  g_return_val_if_fail (AVR_IS_LOG(log), 0);

  return (g_atomic_int_get (&log->_priv->lines_written));
}

/**
 * avr_log_inc_lines_written:
 * @void
 *
 * 
 * Returns: 
 */
inline void
avr_log_inc_lines_written (AvrLog * log)
{
  g_return_if_fail (AVR_IS_LOG(log));

  g_atomic_int_inc (&log->_priv->lines_written);

  return;
}


/**
 * avr_log_set_lines_written:
 * @void
 *
 * 
 * Returns: 
 */
inline void
avr_log_set_lines_written (AvrLog * log, gint written)
{
  g_return_if_fail (AVR_IS_LOG(log));
  g_return_if_fail (written >= 0);

  g_atomic_int_set (&log->_priv->lines_written, written);

  return;
}


/**
 * avr_log_write:
 * @void
 *
 * 
 * Returns: 
 */
void
avr_log_write (AvrLog * log, const gchar * line)
{
  g_return_if_fail (AVR_IS_LOG(log));
  g_return_if_fail (line != NULL);

  GIOStatus status =  G_IO_STATUS_NORMAL;
  GError * error = NULL;
  gchar * dumb_line = NULL;

  if (!(_avr_log_open_file (log)))
    return;

  dumb_line = g_strdup_printf ("%s\n", line);

  g_mutex_lock (&log->_priv->file_mutex);
  status = g_io_channel_write_chars (log->_priv->log_channel, dumb_line, -1, NULL, &error);
  g_mutex_unlock (&log->_priv->file_mutex);

  g_free (dumb_line);

  if ((status != G_IO_STATUS_NORMAL) || (error != NULL))
  {
    if (error != NULL)
    {
      g_critical ("Cannot write to file \"%s\": %s", log->_priv->log_path, error->message);
      g_error_free(error);
    }
    else
    {
      g_critical ("Cannot write to file \"%s\"", log->_priv->log_path);
    }
  }

  g_mutex_lock (&log->_priv->file_mutex);
  status = g_io_channel_flush (log->_priv->log_channel, &error);
  g_mutex_unlock (&log->_priv->file_mutex);

  if ((status != G_IO_STATUS_NORMAL) || (error != NULL))
  {
    if (error != NULL)
    {
      g_warning ("Cannot flush buffers to file \"%s\": %s", log->_priv->log_path, error->message);
      g_error_free(error);
    }
    else
    {
      g_warning ("Cannot flush buffers to file \"%s\"", log->_priv->log_path);
    }
  }


  return;
}


/**
 * avr_log_flush_buffer:
 * @void
 *
 * 
 * Returns: 
 */
void
avr_log_flush_buffer (AvrLog * log)
{
  g_return_if_fail (AVR_IS_LOG(log));

  GList * buffer_keys_list = NULL, * buffer_keys_head = NULL;
  gint key = 0;
  gint lines_written = 0;
  gchar ** buffer = NULL;
  guint table_size = 0;

  // Lock the buffer here, so we make sure that no other thread is working on it.
  g_mutex_lock (&log->_priv->buffer_mutex);

  lines_written = avr_log_get_lines_written (log);

  // buffer_flushed op protected by buffer_mutex
  if ((++ log->_priv->buffer_flushed) >= AVR_TYPES)
  {
    table_size = g_hash_table_size (log->_priv->buffer_htable);
    g_message ("Input log file rotated, flushing pending events...");
    if (table_size > 0)
    {
      //Almost never gets here
      g_message("Input log file rotated, found %d pending event(s), lines_written: %d", table_size, lines_written);
      // Iterate over all pending buffered records
      buffer_keys_head = buffer_keys_list = g_hash_table_get_keys (log->_priv->buffer_htable);
      do
      {
        key = GPOINTER_TO_INT (buffer_keys_list->data);
        //Write all pending records where position/_lines_parsed > wrote records ...
        //Should be up to AVR_TYPES(=4) records pending, if no parsing errors took place
        if (key > lines_written)
        {
          g_message("Input log file rotated, flushing pending event key: %d", key);
          buffer = (gchar **)g_hash_table_lookup (log->_priv->buffer_htable, GINT_TO_POINTER(key));
          _avr_log_compose_and_write (log, buffer);

          (void)g_hash_table_remove (log->_priv->buffer_htable, GINT_TO_POINTER(key));
        }
      }
      while ((buffer_keys_list = g_list_next (buffer_keys_list)));
      g_list_free (buffer_keys_head);
    }

    table_size = g_hash_table_size (log->_priv->buffer_htable);
    g_message ("Input log file rotated. Pending events left in table: %d", table_size);
    log->_priv->buffer_flushed = 0;
    avr_log_set_lines_written (log, 0);
  }

  g_mutex_unlock (&log->_priv->buffer_mutex);

  return;
}


/**
 * avr_log_write_buffer:
 * @void
 *
 * 
 * Returns: 
 *
 * {"timestamp": "2015-04-22T03:26:50.595168","src_ip": "192.168.7.188","src_port": "(null)","dest_ip": "192.168.1.1","dest_port": "(null)","proto": "UDP","log": {"timestamp":"2015-04-22T03:26:50.595168","event_type":"dns","src_ip":"192.168.7.188","src_port":41156,"dest_ip":"192.168.1.1","dest_port":53,"proto":"UDP","dns":{"type":"query","id":49155,"rrname":"24.74.221.111.in-addr.arpa","rrtype":"PTR"}}, "pulses": {}}
 */
void
avr_log_write_buffer (AvrLog * log, const gchar * header, const gchar * data, gint position)
{
  g_return_if_fail (AVR_IS_LOG(log));
  g_return_if_fail (header);
  g_return_if_fail (position > 0);

  gpointer * buffer = NULL, * new_buffer = NULL;
  gint ref = 0;

  g_mutex_lock (&log->_priv->buffer_mutex);

  buffer = g_hash_table_lookup (log->_priv->buffer_htable, GINT_TO_POINTER(position));

  if (buffer == NULL)
  {
    // Insert a new buffer.
    new_buffer = buffer = g_new0 (gpointer, AVR_TYPES + 2);

    new_buffer[0] = GINT_TO_POINTER(2);
    new_buffer[1] = header ? (gpointer)g_strdup_printf ("%s", header) : (gpointer)header;
    new_buffer[2] = data ? (gpointer)g_strdup_printf ("%s", data) : (gpointer)data;

    (void)g_hash_table_insert (log->_priv->buffer_htable, GINT_TO_POINTER(position), new_buffer);
  }
  else
  {
    // Add the string to the buffer.
    ref = GPOINTER_TO_INT(buffer[0]);
    buffer[0] = GINT_TO_POINTER(++ref);
    buffer[ref] = data ? (gpointer)g_strdup_printf ("%s", data) : (gpointer)data;
  }

  // Is the buffer completed? Can we dump it?
  if (GPOINTER_TO_INT(buffer[0]) == AVR_TYPES + 1)
  {
    _avr_log_compose_and_write (log, (gchar **)buffer);
    (void)g_hash_table_remove (log->_priv->buffer_htable, GINT_TO_POINTER(position));
  }

  g_mutex_unlock (&log->_priv->buffer_mutex);

  return;
}


//
// Private methods
//

/**
 * _avr_log_open_file:
 * @void
 *
 * 
 * Returns: 
 */
static gboolean
_avr_log_open_file (AvrLog * log)
{
  g_return_val_if_fail (AVR_IS_LOG(log), FALSE);

  struct stat buf;
  gchar * backup_log_path = NULL;
  gsize file_size = 0;
  GError * error = NULL;

  if (log->_priv->log_channel != NULL)
  {
    // This is a file size check.
    if (stat(log->_priv->log_path, &buf) != 0)
    {
      g_warning ("Cannot stat log file \"%s\"", log->_priv->log_path);
      return (FALSE);
    }

    file_size = (gsize)(buf.st_size / 1048576);
    if (file_size >= log->_priv->size_limit)
    {
      // File needs to be rotated.
      g_mutex_lock (&log->_priv->file_mutex);

      g_io_channel_unref (log->_priv->log_channel);
      backup_log_path = g_strdup_printf ("%s.old", log->_priv->log_path);

      // If it exists already, delete it.
      if (g_file_test(backup_log_path, G_FILE_TEST_IS_REGULAR))
        (void)g_unlink(backup_log_path);

      // Rename log file.
      if (g_rename(log->_priv->log_path, backup_log_path) != 0)
      {
        g_critical ("Cannot rename log file \"%s\" to \"%s\", deleting it...", log->_priv->log_path, backup_log_path);
        (void)g_unlink(log->_priv->log_path);
      }

      // Change permissions to backup log file
      if (chmod(backup_log_path, S_IRUSR | S_IWUSR | S_IRGRP) != 0)
      {
        g_warning ("Cannot change permissions to log file \"%s\"", backup_log_path);
      }

      // No matter what happens, open the file again.
      log->_priv->log_channel = g_io_channel_new_file((const gchar *)log->_priv->log_path, "a+", &error);
      g_mutex_unlock (&log->_priv->file_mutex);
    }
  }
  else
  {
    // Just the initialization, then.
    log->_priv->log_channel = g_io_channel_new_file((const gchar *)log->_priv->log_path, "a+", &error);
  }

  // Change permissions to log file
  if (chmod(log->_priv->log_path, S_IRUSR | S_IWUSR | S_IRGRP) != 0)
  {
    g_warning ("Cannot change permissions to log file \"%s\"", log->_priv->log_path);
  }

  if (error != NULL)
  {
    g_critical ("File \"%s\" cannot be opened: %s", log->_priv->log_path, error->message);
    g_error_free(error);
    return (FALSE);
  }

  return (TRUE);
}

/**
 * _avr_log_handler:
 * @void
 *
 *
 * Returns:
 */
static void
_avr_log_handler (const gchar    *log_domain,
                  GLogLevelFlags  log_level,
                  const gchar    *line,
                  gpointer        avr_log)
{
  g_return_if_fail (line);
  g_return_if_fail (avr_log);

  gchar *message;
  AvrLog * log = AVR_LOG (avr_log);

  if (log->_priv->level < log_level)
    return;

  message = _avr_log_build_message(log_domain, log_level, line);
  avr_log_write (log, message);
  g_free(message);

  return;
}


static void
_avr_log_timestamp(char *timebuf)
{
  time_t t;
  struct tm ltime;
  if ((t = time(NULL))==(time_t)-1)
  {
    g_message("Critical: can't obtain current time in %s:%d",__FILE__,__LINE__);
  }
  if (localtime_r(&t,&ltime)==NULL)
  {
    g_message("Critical: can't obtain local time in %s:%d",__FILE__,__LINE__);
  }
  if (strftime(timebuf,TIMEBUF_SIZE,"%F %T",&ltime)==0)
  {
    g_message("Critical: can't generate timestamp in %s:%d",__FILE__,__LINE__);
  }

  return;
}


/**
 * _avr_log_build_message:
 * @void
 *
 *
 * Returns:
 */
static gchar *
_avr_log_build_message (const gchar     *log_domain,
                        GLogLevelFlags   log_level,
                        const gchar     *message)
{
  gchar *msg;
  gchar timestamp[TIMEBUF_SIZE+1];

  _avr_log_timestamp (timestamp);

  switch (log_level)
  {
  case G_LOG_LEVEL_CRITICAL:
    msg = g_strdup_printf ("%s %s-Critical: %s",timestamp, log_domain, message);
    break;
  case G_LOG_LEVEL_WARNING:
    msg = g_strdup_printf ("%s %s-Warning: %s", timestamp, log_domain, message);
    break;
  case G_LOG_LEVEL_MESSAGE:
    msg = g_strdup_printf ("%s %s-Message: %s", timestamp, log_domain, message);
    break;
  case G_LOG_LEVEL_INFO:
    msg = g_strdup_printf ("%s %s-Info: %s", timestamp, log_domain, message);
    break;
  case G_LOG_LEVEL_DEBUG:
    msg = g_strdup_printf ("%s %s-Debug: %s", timestamp, log_domain, message);
    break;
  case G_LOG_LEVEL_ERROR: /*A G_LOG_LEVEL_ERROR is always a FATAL error. */
  default:
    msg = g_strdup_printf ("%s %s-Error: %s", timestamp, log_domain, message);
    break;
  }

  return msg;
}


/**
 * _avr_log_destroy_buffer:
 * @void
 *
 * 
 * Returns: 
 */
static void
_avr_log_destroy_buffer (gpointer data)
{
  g_return_if_fail (data);

  gint i = 0;
  gpointer * buffer = (gpointer *)data;

  for (i = 1; i < (GPOINTER_TO_INT(buffer[0]) + 1); i++)
  {
    if (buffer[i])
      g_free (buffer[i]);
  }

  g_free (data);

  return;
}

/**
 * _avr_log_compose_and_write:
 * @void
 *
 * 
 * Returns: 
 */
/* e.g.:
  {
    "timestamp": "2016-08-22T06:58:20.853189","src_ip": "152.68.146.11","src_port": 53,"dest_ip": "141.146.40.227","dest_port": 48965,"proto": "UDP",
    "log": {"timestamp":"2016-08-22T06:58:20.853189","event_type":"dns","vlan":40,"src_ip":"152.68.146.11","src_port":53,"dest_ip":"141.146.40.227","dest_port":48965,"proto":"UDP", "dns":{"type":"answer","id":19014,"rrname":"clients14-google.com","rrtype":"SOA","ttl":10800} },
    "pulses": {"57afef900fee2901359fe75b": ["clients14-google.com"], {...} }
  }
    buffer[0] = Array size
    buffer[1] = header
    buffer[2 .. AVR_TYPES+2] = pulses
 */
static void
_avr_log_compose_and_write (AvrLog * log, gchar ** buffer)
{
  g_return_if_fail (AVR_IS_LOG(log));
  g_return_if_fail (buffer);

  GString * joined_string = NULL;
  gint i = 0;

  for (i = 2; i < AVR_TYPES + 2; i++)
  {
    if ((buffer[i] != NULL) && (buffer[i][0] != '\0'))
    {
      if (joined_string == NULL)
      {
        joined_string = g_string_new (" \"pulses\": {");
        g_string_append_printf (joined_string, "%s", buffer[i]);
      }
      else
        g_string_append_printf (joined_string, ",%s", buffer[i]);
    }
  }

  // Add the header and the final bracket.
  if (joined_string != NULL)
  {
    joined_string = g_string_prepend (joined_string, buffer[1]);
    g_string_append_printf (joined_string, "}}");

    avr_log_write (log, (const gchar *)joined_string->str);
    (void)g_string_free (joined_string, TRUE);

    avr_log_inc_lines_written (log);
  }

  return;
}
