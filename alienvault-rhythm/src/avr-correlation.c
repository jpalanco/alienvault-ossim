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

#include "avr-correlation.h"

#include <unistd.h>
#include <fcntl.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <stdio.h>
#include <string.h>

#include <gio/gio.h>
#include <json-glib/json-glib.h>
#include <glib/gstdio.h>

#include "avr-db.h"
#include "radix-tree.h"

struct _AvrCorrelationPrivate
{
  AvrType        type;
  AvrDb *        db;
  AvrLog *       event_log;
  AvrTld *       domains;

  gint           lines_parsed;
  gint           lines_matched;
  GAsyncQueue*   queue;
};

struct _AvrCorrelationReaderPrivate
{
  gchar *        file_path;
  GIOChannel *   file_channel;
  goffset        start_position;
  AvrCorrelation ** correlations;
};

static gpointer parent_class = NULL;
static gpointer parent_reader_class = NULL;
static const gchar * avr_type_names[] = {"IP Address", "File Hash", "Domain", "Hostname", NULL};

//
// Static declarations.
//
static gpointer         _avr_correlation_loop              (gpointer);
static gpointer         _avr_correlation_reader_loop       (gpointer);
static JsonReader *     _avr_correlation_get_reader        (AvrCorrelationReader * correlation_reader, const gchar * line_str, gsize line_len);
static GPtrArray *      _avr_correlation_parse_line        (AvrCorrelation *, JsonReader * reader, const gchar * line_str, gsize line_len);
static gchar *          _avr_correlation_match_ip_address  (AvrCorrelation *, GPtrArray *);
static gchar *          _avr_correlation_match_string      (AvrCorrelation *, GPtrArray *);
static GString *        _avr_correlation_build_list_string (GHashTable     *, GPtrArray *);

// GType Functions

static void
avr_correlation_impl_dispose (GObject * gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void
avr_correlation_impl_finalize (GObject * gobject)
{
  AvrCorrelation * correlation = AVR_CORRELATION (gobject);

  if (correlation->_priv->db)
    g_object_unref (correlation->_priv->db);

  if (correlation->_priv->domains)
    g_object_unref (correlation->_priv->domains);

  if (correlation->_priv->event_log)
  {
    g_object_unref (correlation->_priv->event_log);
  }

  g_async_queue_unref (correlation->_priv->queue);

  g_free (correlation->_priv);

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
avr_correlation_class_init (AvrCorrelationClass * class)
{
  GObjectClass * object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_peek_parent (class);

  object_class->dispose = avr_correlation_impl_dispose;
  object_class->finalize = avr_correlation_impl_finalize;
}

static void
avr_correlation_instance_init (AvrCorrelation * correlation)
{
  correlation->_priv = g_new0 (AvrCorrelationPrivate, 1);

  return;
}

GType
avr_correlation_get_type (void)
{
  static GType object_type = 0;

  if (!object_type)
  {
    static const GTypeInfo type_info = {
      sizeof (AvrCorrelationClass),
      NULL,
      NULL,
      (GClassInitFunc) avr_correlation_class_init,
      NULL,
      NULL,                       /* class data */
      sizeof (AvrCorrelation),
      0,                          /* number of pre-allocs */
      (GInstanceInitFunc) avr_correlation_instance_init,
      NULL                        /* value table */
    };

    object_type = g_type_register_static (G_TYPE_OBJECT, "AvrCorrelation", &type_info, 0);
  }
  return object_type;
}

/**
 * avr_correlation_init:
 *
 *
 */
void
avr_correlation_init (void)
{

}

/**
 * avr_correlation_clear:
 *
 *
 */
void
avr_correlation_clear (void)
{
}

//
//
//

static void
avr_correlation_reader_impl_dispose (GObject * gobject)
{
  G_OBJECT_CLASS (parent_reader_class)->dispose (gobject);
}

static void
avr_correlation_reader_impl_finalize (GObject * gobject)
{
  AvrCorrelationReader * correlation_reader = AVR_CORRELATION_READER (gobject);

  if (correlation_reader->_priv->file_path)
    g_free(correlation_reader->_priv->file_path);

  if (correlation_reader->_priv->file_channel)
    g_io_channel_unref(correlation_reader->_priv->file_channel);

  g_free (correlation_reader->_priv);

  G_OBJECT_CLASS (parent_reader_class)->finalize (gobject);
}

static void
avr_correlation_reader_class_init (AvrCorrelationReaderClass * class)
{
  GObjectClass * object_class = G_OBJECT_CLASS (class);

  parent_reader_class = g_type_class_peek_parent (class);

  object_class->dispose = avr_correlation_reader_impl_dispose;
  object_class->finalize = avr_correlation_reader_impl_finalize;
}

static void
avr_correlation_reader_instance_init (AvrCorrelationReader * correlation_reader)
{
  correlation_reader->_priv = g_new0 (AvrCorrelationReaderPrivate, 1);

  return;
}

GType
avr_correlation_reader_get_type (void)
{
  static GType object_type = 0;

  if (!object_type)
  {
    static const GTypeInfo type_info = {
      sizeof (AvrCorrelationReaderClass),
      NULL,
      NULL,
      (GClassInitFunc) avr_correlation_reader_class_init,
      NULL,
      NULL,                       /* class data */
      sizeof (AvrCorrelation),
      0,                          /* number of pre-allocs */
      (GInstanceInitFunc) avr_correlation_reader_instance_init,
      NULL                        /* value table */
    };

    object_type = g_type_register_static (G_TYPE_OBJECT, "AvrCorrelationReader", &type_info, 0);
  }
  return object_type;
}

/**
 * avr_correlation_reader_new:
 * @void
 *
 *
 * Returns:
 */
AvrCorrelationReader *
avr_correlation_reader_new (AvrCorrelation ** correlations)
{
  AvrCorrelationReader * correlation_reader = NULL;
  const gchar * file_path = "/var/log/suricata/eve.json";  // TODO: make this parametric.
  gint file_fd = 0;
  GError * error = NULL;

  correlation_reader = AVR_CORRELATION_READER (g_object_new (AVR_TYPE_CORRELATION_READER, NULL));

  if (!(g_file_test(file_path, G_FILE_TEST_IS_REGULAR)))
  {
    g_message ("File \"%s\" not found, so it will be created", file_path);
    if ((file_fd = open (file_path, O_CREAT)) < 0)
    {
      g_critical ("Cannot create file \"%s\"", file_path);
      g_object_unref (correlation_reader);
      return (NULL);
    }
    close(file_fd);

    if (chmod(file_path, S_IRUSR | S_IWUSR | S_IRGRP | S_IWGRP | S_IROTH) != 0)
    {
      g_critical ("Cannot change permissions for file \"%s\"", file_path);
      g_object_unref (correlation_reader);
      return (NULL);
    }

    // Change owner & group.
    if (chown(file_path, 0, 0) != 0)
    {
      g_critical ("Cannot change \"%s\" owner and group", file_path);
      g_object_unref (correlation_reader);
      return (NULL);
    }
  }

  correlation_reader->_priv->file_path = g_strdup_printf("%s", file_path);

  correlation_reader->_priv->file_channel = g_io_channel_new_file((const gchar *)correlation_reader->_priv->file_path, "r", &error);
  if (error != NULL)
  {
    g_critical ("File \"%s\" cannot be opened: %s", correlation_reader->_priv->file_path, error->message);
    g_object_unref (correlation_reader);
    g_error_free (error);
    return (NULL);
  }

  correlation_reader->_priv->correlations = correlations;

  return (correlation_reader);
}

/**
 * avr_correlation_new:
 * @void
 *
 *
 * Returns:
 */
AvrCorrelation *
avr_correlation_new (AvrType type, AvrLog *event_log, const gchar *db_socket_path, AvrTld *tld)
{
  g_return_val_if_fail (type < AVR_TYPES, NULL);
  g_return_val_if_fail (AVR_IS_LOG(event_log), NULL);

  AvrCorrelation * correlation = NULL;

  correlation = AVR_CORRELATION(g_object_new (AVR_TYPE_CORRELATION, NULL));
  correlation->_priv->type = type;

  correlation->_priv->db = avr_db_new_unix(correlation->_priv->type, db_socket_path);
  if (correlation->_priv->db == NULL)
  {
    g_object_unref (correlation);
    return (NULL);
  }

  correlation->_priv->event_log = g_object_ref (event_log);

  correlation->_priv->lines_matched = 0;
  correlation->_priv->lines_parsed = 0;

  if (tld != NULL)
  {
    correlation->_priv->domains = g_object_ref (tld);
  }

  correlation->_priv->queue = g_async_queue_new ();

  return (correlation);
}

goffset
avr_correlation_reader_run (AvrCorrelationReader *correlation_reader, goffset current_position)
{
  g_return_val_if_fail (AVR_IS_CORRELATION_READER(correlation_reader), G_MINOFFSET);

  FILE *fd;

  if (current_position == G_MINOFFSET)
  {
    if (g_file_test (correlation_reader->_priv->file_path, G_FILE_TEST_EXISTS) == FALSE)
    {
      current_position = 0;
    }
    else
    {
      fd = g_fopen (correlation_reader->_priv->file_path, "r");
      if (fd == NULL)
      {
        g_critical ("Cannot open file %s", correlation_reader->_priv->file_path);
        return G_MINOFFSET;
      }

      fseek (fd, 0, SEEK_END);
      current_position = ftell(fd);
    }
  }

  correlation_reader->_priv->start_position = current_position;

  (void)g_thread_new("_avr_correlation_reader_loop", (GThreadFunc)_avr_correlation_reader_loop, (gpointer)correlation_reader);

  return current_position;
}

/**
 * avr_correlation_run:
 * @void
 *
 *
 * Returns:
 */
void
avr_correlation_run (AvrCorrelation *correlation)
{
  g_return_val_if_fail (AVR_IS_CORRELATION(correlation), G_MINOFFSET);

  (void)g_thread_new("_avr_correlation_loop", (GThreadFunc)_avr_correlation_loop, (gpointer)correlation);
}

//
// Private methods
//

/**
 * _avr_correlation_reader_loop:
 * @void
 *
 *
 * Returns:
 */
static gpointer
_avr_correlation_reader_loop (gpointer avr_correlation_reader_ptr)
{
  AvrCorrelationReader *correlation_reader = AVR_CORRELATION_READER (avr_correlation_reader_ptr);
  time_t last_time = 0;
  time_t current_time = 0;
  GIOStatus file_status = G_IO_STATUS_NORMAL;
  struct stat file_stat;
  gchar *line_str = NULL;
  gsize line_len = 0, line_term = 0, processed_len = 0;
  gsize utf8_line_len = 0;
  GError *error = NULL;
  guint64 skipped = 0;
  guint64 discarded = 0;
  guint64 discarded_empty = 0;
  guint64 errors = 0;
  guint64 retries = 0;
  gint64 new_retry_pos, prev_retry_pos = -1;
  gint i;
  GPtrArray *p_array = NULL;
  JsonReader *reader = NULL;
  gint line_count = 0;

  // Set cursor at the end.
  g_message ("Starting reader thread: at eve position %lld", (long long int)correlation_reader->_priv->start_position);
  memset (&file_stat, 0, sizeof (struct stat));

  if ((file_status = g_io_channel_seek_position (correlation_reader->_priv->file_channel, correlation_reader->_priv->start_position, G_SEEK_SET, &error)) != G_IO_STATUS_NORMAL)
  {
    if (error != NULL)
    {
      g_warning ("Cannot set pointer at the end of \"%s\": %s", correlation_reader->_priv->file_path, error->message);
      g_error_free(error);
    }
    else
    {
      g_warning ("Cannot set pointer at the end of \"%s\"", correlation_reader->_priv->file_path);
    }
    return (NULL);
  }

  while (TRUE)
  {
    // Show some fancy stats.
    current_time = time(NULL);
    if ((current_time != last_time) && ((current_time % 10) == 0))
    {
      for (i = 0; i < AVR_TYPES; i++)
      {
        AvrCorrelation *correlation = AVR_CORRELATION (correlation_reader->_priv->correlations[i]);
        g_message ("Type: %-10s; Events processed: %d; IoC matched: %3d; Eve pos: %ld; Last read eve size: %ld; Skipped: %ld; Discarded: %ld/%ld, Errors: %ld/%ld",
                   avr_type_names[correlation->_priv->type],
                   g_atomic_int_get (&correlation->_priv->lines_parsed),
                   g_atomic_int_get (&correlation->_priv->lines_matched),
                   correlation_reader->_priv->start_position + processed_len,
                   file_stat.st_size,
                   skipped,
                   discarded,
                   discarded_empty,
                   errors,
                   retries);
      }
      last_time = current_time;
    }

    // Read the file, line by line.
    file_status = g_io_channel_read_line (correlation_reader->_priv->file_channel, &line_str, &line_len, &line_term, &error);

    if (!(file_status & (G_IO_STATUS_NORMAL | G_IO_STATUS_EOF)) || (error != NULL))
    {
      if (error != NULL)
      {
        g_warning ("Cannot read file \"%s\": %s", correlation_reader->_priv->file_path, error->message);
        g_error_free(error);
      }
      else
      {
        g_warning ("Cannot read file \"%s\"", correlation_reader->_priv->file_path);
      }

      return (NULL);
    }

    if ((file_status == G_IO_STATUS_EOF))
    {
      if (line_str != NULL)
      {
        g_free (line_str);
        line_str = NULL;
      }

      // Has this file been truncated? Let us see...
      if (stat(correlation_reader->_priv->file_path, &file_stat) == 0)
      {
        // ENG-106023 alienvault-rhythm stops processing events from eve.json upon being restarted by logrotate
        if (file_stat.st_size < (off_t) (correlation_reader->_priv->start_position + processed_len))
        {
          // Increment "flush buffer" counter (and perform actual flush when last thread notices the truncation)
          g_message ("Reader thread: File has been truncated. New size=%ld; Processed eve size=%ld", file_stat.st_size, processed_len);

          //Send 0 elements array to correlation threads - means log rotation detected
          for (i = 0; i < AVR_TYPES; i++)
          {
            AvrCorrelation *correlation = AVR_CORRELATION (correlation_reader->_priv->correlations[i]);
            p_array = g_ptr_array_new();
            g_async_queue_push (correlation->_priv->queue, p_array);
          }

          // Set cursor at the start of the new file.
          if ((file_status = g_io_channel_seek_position (correlation_reader->_priv->file_channel, 0, G_SEEK_SET, &error)) != G_IO_STATUS_NORMAL)
          {
            if (error != NULL)
            {
              g_warning ("Cannot set pointer at the start of \"%s\": %s", correlation_reader->_priv->file_path, error->message);
              g_error_free(error);
            }
            else
            {
              g_warning ("Cannot set pointer at the start of \"%s\"", correlation_reader->_priv->file_path);
            }
            return (NULL);
          }

          correlation_reader->_priv->start_position = 0;
          processed_len = 0;
          line_count = 0;
          g_message ("Reader thread: Line index has been reset.");
        }
      }
      else
      {
        g_warning ("Reader thread: Cannot stat file \"%s\"", correlation_reader->_priv->file_path);
      }

      // Do not stop reading at the end of the file, as it should keep growing.
      // Give it some time, though.
      g_usleep(100000);
      continue;
    }

    // Empty lines are not detected by g_io_channel_read_line(). Relying on the fact that suricata does not produce empty lines
    //Fix: ENG-104018 alienvault-rhythm appears to be matching incorrectly
    processed_len += line_len;

    // Ignore line if there is no otx data loaded
    if (avr_db_has_otx_data() == FALSE)
    {
      if (line_str != NULL)
      {
        g_free (line_str);
        line_str = NULL;
      }
      g_usleep(100);
      ++ skipped;
      continue;
    }

    // Discard the line if it is not a valid event
    if (line_len < 10)
    {
      if (line_str != NULL)
      {
        g_free (line_str);
        line_str = NULL;

        ++ discarded;
      }
      else
        ++ discarded_empty;
      continue;
    }

    utf8_line_len = g_utf8_strlen (line_str, -1);

    // Parse a line, match a line.
    reader = _avr_correlation_get_reader (correlation_reader, line_str, utf8_line_len);

    if (reader == NULL)
    {
      ++ errors;

      if (stat(correlation_reader->_priv->file_path, &file_stat) == 0)
      {
        // ENG-106023 alienvault-rhythm stops processing events from eve.json upon being restarted by logrotate
        if ((off_t) (correlation_reader->_priv->start_position + processed_len) < file_stat.st_size)
        {
          // Set cursor to retry position.
          new_retry_pos = correlation_reader->_priv->start_position + processed_len - line_len;
          if (new_retry_pos > prev_retry_pos)
          {
            ++ retries;
            prev_retry_pos = new_retry_pos;
            processed_len -= line_len;
            g_message ("Warning: Retrying to read again the line that parser failed to parse with seek position = %ld, line_len=%ld", new_retry_pos, line_len);
            if ((file_status = g_io_channel_seek_position (correlation_reader->_priv->file_channel, new_retry_pos, G_SEEK_SET, &error)) != G_IO_STATUS_NORMAL)
            {
              if (error != NULL)
              {
                g_warning ("Cannot set pointer at the start of \"%s\": %s", correlation_reader->_priv->file_path, error->message);
                g_error_free (error);
              }
              else
              {
                g_warning ("Cannot set pointer at the start of \"%s\"", correlation_reader->_priv->file_path);
              }
              return (NULL);
            }
          }
          else
          {
            g_message ("Warning: Skipping retrying for the same fail. seek position = %ld, line_len=%ld", new_retry_pos, line_len);
          }
        }
      }
    }
    else
    {
      ++ line_count;

      for (i = 0; i < AVR_TYPES; i++)
      {
        AvrCorrelation *correlation = AVR_CORRELATION (correlation_reader->_priv->correlations[i]);
        if (i > 0)
          reader = _avr_correlation_get_reader (correlation_reader, line_str, utf8_line_len);
        p_array = g_ptr_array_new();
        g_ptr_array_add (p_array, (gpointer) reader);
        g_ptr_array_add (p_array, (gpointer) g_strdup (line_str));
        g_ptr_array_add (p_array, GINT_TO_POINTER (line_count));
        g_async_queue_push (correlation->_priv->queue, p_array);
      }
      g_free (line_str);
    }

    reader = NULL;
    p_array = NULL;
  }
}

/**
 * _avr_correlation_loop:
 * @void
 *
 *
 * Returns:
 */
static gpointer
_avr_correlation_loop (gpointer avr_correlation_ptr)
{
  AvrCorrelation *correlation = AVR_CORRELATION (avr_correlation_ptr);
  JsonReader *reader;
  gchar *header_str = NULL;
  GPtrArray *parsed_array = NULL;
  gchar *result_str = NULL;
  gsize utf8_line_len = 0;
  gchar *line_str = NULL;
  GPtrArray *p_array = NULL;
  gint index;

  while (TRUE)
  {
    p_array = (GPtrArray*) g_async_queue_pop (correlation->_priv->queue);

    // A failure parsing a line is still considered as a parsed line.
    // Two or more threads will be concurrently reading the same file,
    // so to avoid mixing lines that cannot be parsed by one thread
    // with the same line parsed successfully in another, just take this
    // for granted.
    g_atomic_int_inc (&correlation->_priv->lines_parsed);

    if (p_array != NULL)
    {
      if (p_array->len)
      {
        reader = (JsonReader *)g_ptr_array_index (p_array, 0);
        if (reader != NULL)
        {
          line_str = (gchar *)g_ptr_array_index (p_array, 1);
          if (line_str != NULL)
          {
            index = GPOINTER_TO_INT (g_ptr_array_index (p_array, 2));
            utf8_line_len = g_utf8_strlen (line_str, -1);

            parsed_array = _avr_correlation_parse_line (correlation, reader, line_str, utf8_line_len);

            // No need to trigger an awful error message here, just return.
            if (parsed_array != NULL)
            {
              if (parsed_array->len > 0)
              {
                switch (correlation->_priv->type)
                {
                case IP_ADDRESS:
                  result_str = _avr_correlation_match_ip_address (correlation, parsed_array);
                  break;

                case FILE_HASH:
                case DOMAIN:
                case HOSTNAME:
                  result_str = _avr_correlation_match_string(correlation, parsed_array);
                  break;

                default:
                  g_warning ("Invalid data type with thread: %-10s in correlation", avr_type_names[correlation->_priv->type]);
                }

                if( result_str )
                  g_debug ("Thread: %-10s match: result=%s", avr_type_names[correlation->_priv->type], result_str);

                // Get the event header info.
                header_str = (gchar *)g_ptr_array_index (parsed_array, 0);

                // Insert given thread's correlation result into log buffer htable (key = line index);
                // buffer is dumped when all threads have provided their results for given line index
                avr_log_write_buffer (correlation->_priv->event_log, header_str, result_str, index);

                if (result_str != NULL)
                {
                  g_atomic_int_inc (&correlation->_priv->lines_matched);
                  g_free (result_str);
                }
              }
              else
              {
                g_warning ("Error: parsed_array = NULL in the thread: %-10s", avr_type_names[correlation->_priv->type]);
              }

              g_ptr_array_unref (parsed_array);
              parsed_array = NULL;
            }

            g_free (line_str);
            line_str = NULL;
          }
          else
          {
            g_warning ("Error: NULL line_str passed to the thread: %-10s", avr_type_names[correlation->_priv->type]);
          }

          g_object_unref (reader);
          reader = NULL;
        }
        else
        {
          g_warning ("Error: NULL reader passed to the thread: %-10s", avr_type_names[correlation->_priv->type]);
        }
      }
      else
      {
        avr_log_flush_buffer (correlation->_priv->event_log);
      }
      g_ptr_array_unref (p_array);
      p_array = NULL;
    }
    else
    {
      g_warning ("Error: NULL array passed to the thread: %-10s", avr_type_names[correlation->_priv->type]);
    }
  }

  return (NULL);
}

static JsonReader *
_avr_correlation_get_reader (AvrCorrelationReader * correlation_reader, const gchar * line_str, gsize line_len)
{
  g_return_val_if_fail (AVR_IS_CORRELATION_READER(correlation_reader), NULL);
  g_return_val_if_fail (line_str, NULL);
  g_return_val_if_fail (line_len > 10, NULL);

  JsonReader * reader = NULL;
  JsonParser * parser = NULL;
  GError * error = NULL;

  parser = json_parser_new ();
  if (json_parser_load_from_data (parser, line_str, line_len, &error) != TRUE)
  {
    g_object_unref (parser);
    parser = NULL;

    if (error != NULL)
    {
      // This could happen in, at least, two different situations
      //  1 - when the file is opened to be read, and it's being written very fast by an external process,
      //      when you set the cursor to the end of the file, you could be setting the cursor in the middle of the line
      //
      //  2 - When suricata stop writing logs and the latest line is not fully written, this causes the latest line to be an invalid json line
      g_message("Error: Reader thread: Cannot parse line from file %s | Error:%s | Line:%s", correlation_reader->_priv->file_path, error->message, line_str);
      g_error_free(error);
    }
    else
    {
      g_warning("Warning: Reader thread: Cannot parse line from file \"%s\"", correlation_reader->_priv->file_path);
    }

    return (NULL);
  }

  reader = json_reader_new (json_parser_get_root (parser));
  g_object_unref (parser);
  parser = NULL;

  return reader;
}

/**
 * _avr_correlation_parse_line:
 * @void
 *
 *
 * Returns:
 */
static GPtrArray *
_avr_correlation_parse_line (AvrCorrelation * correlation, JsonReader * reader, const gchar * line_str, gsize line_len)
{
  g_return_val_if_fail (AVR_IS_CORRELATION(correlation), NULL);

  GPtrArray * parsed_array = NULL;
  const gchar * header_fields [] = {"timestamp", "src_ip", "src_port", "dest_ip", "dest_port", "proto", NULL};
  gint i = 0;
  GString * header_str = NULL;
  gchar * value = NULL;
  const GError *gerror = NULL;

  parsed_array = g_ptr_array_new_with_free_func ((GDestroyNotify)g_free);

  // Reader event header info first.
  header_str = g_string_new ("{");
  for (i = 0; header_fields[i] != NULL; i++)
  {
    if (json_reader_read_member (reader, header_fields[i]))
    {
      JsonNode * node = json_reader_get_value (reader);
      switch (json_node_get_value_type (node))
      {
       case G_TYPE_STRING:
           g_string_append_printf (header_str, "\"%s\": \"%s\",", header_fields[i], json_reader_get_string_value (reader));
           break;
         case G_TYPE_INT64:
           g_string_append_printf (header_str, "\"%s\": %ld,", header_fields[i], json_reader_get_int_value (reader));
           break;
         case G_TYPE_DOUBLE:
           g_string_append_printf (header_str, "\"%s\": %f,", header_fields[i], json_reader_get_double_value (reader));
           break;
         case G_TYPE_BOOLEAN:
           g_string_append_printf (header_str, "\"%s\": %s,", header_fields[i], json_reader_get_boolean_value (reader) ? "true":"false");
           break;
         default:
          g_warning("'%d' Unexpected value type", (gint) json_node_get_value_type (node));
       }
    }
    json_reader_end_member (reader);
  }

  // Append the whole log.
  g_string_append_printf (header_str, "\"log\": ");
  g_string_append_len (header_str, line_str, line_len - 1);
  g_string_append (header_str, ",");

  gchar* index = g_string_free (header_str, FALSE);
  g_ptr_array_add (parsed_array, (gpointer)index);

  switch(correlation->_priv->type)
  {
  case IP_ADDRESS:
    // Read "src_ip" and "dst_ip"
    if (json_reader_read_member (reader, "src_ip"))
    {
      value = g_strdup_printf ("%s", json_reader_get_string_value (reader));
      g_ptr_array_add (parsed_array, (gpointer)value);
    }
    else
    {
      gerror = json_reader_get_error(reader);
      if (gerror->code != JSON_READER_ERROR_INVALID_MEMBER)
        g_message("Error: FILE_HASH: json_reader_read_member('src_ip'): %s", gerror->message);
    }
    json_reader_end_member (reader);

    if (json_reader_read_member (reader, "dest_ip"))
    {
      value = g_strdup_printf ("%s", json_reader_get_string_value (reader));
      g_ptr_array_add (parsed_array, (gpointer)value);
    }
    else
    {
      gerror = json_reader_get_error(reader);
      if (gerror->code != JSON_READER_ERROR_INVALID_MEMBER)
        g_message("Error: FILE_HASH: json_reader_read_member('dest_ip'): %s", gerror->message);
    }
    json_reader_end_member (reader);

    break;
  case FILE_HASH:
    // Read "md5" under "fileinfo"
    if (json_reader_read_member (reader, "fileinfo"))
    {
      if (json_reader_read_member (reader, "sha1"))
      {
        value = g_utf8_strdown(json_reader_get_string_value (reader), -1);
        g_ptr_array_add (parsed_array, (gpointer)value);
        json_reader_end_member(reader);// back to the previous node
      }

      if (json_reader_read_member (reader, "sha256"))
      {
        value = g_utf8_strdown(json_reader_get_string_value (reader), -1);
        g_ptr_array_add (parsed_array, (gpointer)value);
        json_reader_end_member(reader);// back to the previous node
      }

      if (json_reader_read_member (reader, "md5"))
      {
        value = g_utf8_strdown(json_reader_get_string_value (reader), -1);
        g_ptr_array_add (parsed_array, (gpointer)value);
        json_reader_end_member(reader);// back to the previous node
      }

    }
    else
    {
      gerror = json_reader_get_error(reader);
      if (gerror->code != JSON_READER_ERROR_INVALID_MEMBER)
        g_message("Error: FILE_HASH: json_reader_read_member('fileinfo'): %s", gerror->message);
    }
    json_reader_end_member (reader);

    break;
  case DOMAIN:
    // Read "rrname" under "dns", if and only if it the type "answer" is present.
    if (json_reader_read_member (reader, "dns"))
    {
      if (json_reader_read_member (reader, "type"))
      {
        if (!g_ascii_strncasecmp ("answer", json_reader_get_string_value (reader), 6))
        {
          json_reader_end_member(reader);
          if (json_reader_read_member (reader, "rrname"))
          {
            value = g_utf8_strdown(json_reader_get_string_value (reader), -1);
            g_ptr_array_add (parsed_array, (gpointer)value);

            value = avr_tld_get_domain(correlation->_priv->domains, value);
            if (value != NULL)
            {
              g_ptr_array_add (parsed_array, (gpointer)value);
              g_debug ("Stripped Domain: %s", value);
            }
          }
          else
          {
            gerror = json_reader_get_error(reader);
            if (gerror->code != JSON_READER_ERROR_INVALID_MEMBER)
              g_message("Error: DOMAIN: json_reader_read_member('rrname'): %s", gerror->message);
          }
        }
      }
      else
      {
        gerror = json_reader_get_error(reader);
        if (gerror->code != JSON_READER_ERROR_INVALID_MEMBER)
          g_message("Error: DOMAIN: json_reader_read_member('type'): %s", gerror->message);
      }
    }
    else
    {
      gerror = json_reader_get_error(reader);
      if (gerror->code != JSON_READER_ERROR_INVALID_MEMBER)
        g_message("Error: DOMAIN: json_reader_read_member('dns'): %s", gerror->message);
    }
    json_reader_end_member (reader);

    if (json_reader_read_member (reader, "http"))
    {
      if (json_reader_read_member (reader, "hostname"))
      {
        value = g_utf8_strdown(json_reader_get_string_value(reader), -1);
        g_ptr_array_add (parsed_array, (gpointer)value);

        value = avr_tld_get_domain(correlation->_priv->domains, value);
        if (value != NULL)
        {
          g_ptr_array_add (parsed_array, (gpointer)value);
          g_debug ("Stripped Domain: %s", value);
        }
      }
      else
      {
        gerror = json_reader_get_error(reader);
        if (gerror->code != JSON_READER_ERROR_INVALID_MEMBER)
          g_message("Error: DOMAIN: json_reader_read_member('hostname'): %s", gerror->message);
      }
    }
    else
    {
      gerror = json_reader_get_error(reader);
      if (gerror->code != JSON_READER_ERROR_INVALID_MEMBER)
        g_message( "Error: DOMAIN: json_reader_read_member('http'): %s", gerror->message );
    }
    json_reader_end_member (reader);

    break;
  case HOSTNAME:
    // Read "rrname" under "dns"
    if (json_reader_read_member (reader, "dns"))
    {
      if (json_reader_read_member (reader, "type"))
      {
        if (!g_ascii_strncasecmp ("answer", json_reader_get_string_value (reader), 6))
        {
          json_reader_end_member (reader);
          if (json_reader_read_member (reader, "rrname"))
          {
            value = g_utf8_strdown(json_reader_get_string_value (reader), -1);
            g_ptr_array_add (parsed_array, (gpointer)value);
          }
          else
          {
            gerror = json_reader_get_error(reader);
            if (gerror->code != JSON_READER_ERROR_INVALID_MEMBER)
              g_message( "Error: HOSTNAME: json_reader_read_member('rrname'): %s", gerror->message );
          }
        }
      }
      else
      {
        gerror = json_reader_get_error(reader);
        if (gerror->code != JSON_READER_ERROR_INVALID_MEMBER)
          g_message("Error: HOSTNAME: json_reader_read_member('type'): %s", gerror->message);
      }
    }
    else
    {
      gerror = json_reader_get_error(reader);
      if (gerror->code != JSON_READER_ERROR_INVALID_MEMBER)
        g_message("Error: HOSTNAME: json_reader_read_member('dns'): %s", gerror->message);
    }
    json_reader_end_member (reader);

    // Read "hostname" under "http".
    if (json_reader_read_member (reader, "http"))
    {
      if (json_reader_read_member (reader, "hostname"))
      {
        value = g_utf8_strdown(json_reader_get_string_value (reader), -1);
        g_ptr_array_add (parsed_array, (gpointer)value);
      }
      else
      {
        gerror = json_reader_get_error(reader);
        if (gerror->code != JSON_READER_ERROR_INVALID_MEMBER)
          g_message("Error: HOSTNAME: json_reader_read_member('hostname'): %s", gerror->message);
      }
    }
    else
    {
      gerror = json_reader_get_error(reader);
      if (gerror->code != JSON_READER_ERROR_INVALID_MEMBER)
        g_message("Error: HOSTNAME: json_reader_read_member('http'): %s", gerror->message);
    }
    json_reader_end_member (reader);

    break;
  default:
    g_warning("Invalid data type, cannot parse line");
  }

  return (parsed_array);
}

/**
 * _avr_correlation_match_ip_address:
 * @void
 *
 *
 * Returns:
 */
static gchar *
_avr_correlation_match_ip_address (AvrCorrelation * correlation, GPtrArray * parsed_array)
{
  g_return_val_if_fail (AVR_IS_CORRELATION(correlation), NULL);
  g_return_val_if_fail (parsed_array->len > 0, NULL);

  gpointer data = NULL;
  guint i = 0, j = 0;
  GInetAddress * value_inet_addr = NULL;
  GPtrArray * found_array = NULL;
  GHashTable * result_htable = NULL;
  GPtrArray * value_array = NULL;
  GString * result_str = NULL;
  GString * key_str = NULL, * value_str = NULL;

  if ((data = avr_db_ref_data (correlation->_priv->db)) == NULL)
    return (NULL);

  // Start at position 1, since position 0 is the header.
  for (i = 1; i < parsed_array->len; i++)
  {
    // Use '32' for the key mask, as we will deal with IP addresses in IPv4 only.
    value_inet_addr = g_inet_address_new_from_string((gchar *)g_ptr_array_index(parsed_array, i));

    if (value_inet_addr)
    {
      found_array = radix_tree_lookup((RadixTree *)data, g_inet_address_to_bytes(value_inet_addr), 32, NULL);

      if (found_array != NULL)
      {
        if (found_array->len > 0)
        {
          g_debug("Matched IP '%s'", (gchar *)g_ptr_array_index(parsed_array, i));
          if (result_htable == NULL)
            result_htable = g_hash_table_new_full ((GHashFunc)g_str_hash, (GEqualFunc)g_str_equal, (GDestroyNotify)g_free, (GDestroyNotify)g_ptr_array_unref);

          for (j = 1; j < found_array->len; j++)
          {
            key_str = (GString *)g_ptr_array_index (found_array, j);
            if ((value_array = g_hash_table_lookup (result_htable, key_str->str)) != NULL)
            {
              // If a previous pulse was found, replace the value.
              value_str = (GString *)g_ptr_array_index(found_array, 0);
              g_ptr_array_add((GPtrArray *)value_array, (gpointer)g_strdup_printf("%s", value_str->str));
            }
            else
            {
              // If not, add the new one.
              value_array = g_ptr_array_new_with_free_func ((GDestroyNotify)g_free);
              value_str = (GString *)g_ptr_array_index(found_array, 0);
              g_ptr_array_add((GPtrArray *)value_array, (gpointer)g_strdup_printf("%s", value_str->str));
              g_hash_table_insert (result_htable, (gpointer)g_strdup_printf("%s", key_str->str), (gpointer)value_array);
            }
          }
        }

        g_ptr_array_unref (found_array);
      }

      g_object_unref(value_inet_addr);
    }
  }

  avr_db_unref_data (correlation->_priv->db, data);

  if (result_htable != NULL)
  {
    result_str = _avr_correlation_build_list_string(result_htable, value_array);

    g_hash_table_unref (result_htable);
    g_debug("Matched Pulse '%s'", result_str->str);
  }

  return (result_str ? g_string_free (result_str, FALSE) : NULL);
}


/**
 * _avr_correlation_match_string:
 * @void
 * Matched FileHash or Host name or Domain
 *
 * Returns:
 */
static gchar *
_avr_correlation_match_string (AvrCorrelation *correlation, GPtrArray *parsed_array)
{
  g_return_val_if_fail (AVR_IS_CORRELATION(correlation), NULL);
  g_return_val_if_fail (parsed_array->len > 0, NULL);

  gpointer data = NULL;
  guint i = 0, j = 0;
  GPtrArray * found_array = NULL;
  GPtrArray * value_array = NULL;
  GHashTable * result_htable = NULL;
  GString * result_str = NULL;
  gchar * key_str = NULL, * value_str = NULL;

  if ((data = avr_db_ref_data (correlation->_priv->db)) == NULL)
    return (NULL);

  // Start at position 1, since position 0 is the event header.
  for (i = 1; i < parsed_array->len; i++)
  {
    // Remember, this is a pointer to the original value, never free it!
    gpointer parsed_array_1 = g_ptr_array_index(parsed_array, i);
    //search in redis DB(type) for data
    found_array = (GPtrArray *)g_hash_table_lookup ((GHashTable *)data, parsed_array_1);

    if (found_array != NULL)
    {
      if (found_array->len > 0)
      {
        if (result_htable == NULL)
          result_htable = g_hash_table_new_full ((GHashFunc)g_str_hash, (GEqualFunc)g_str_equal, (GDestroyNotify)g_free, (GDestroyNotify)g_ptr_array_unref);

        for (j = 1; j < found_array->len; j++)
        {
          key_str = (gchar *)g_ptr_array_index (found_array, j);
          g_debug("key: %s", key_str);
          if ((value_array = g_hash_table_lookup (result_htable, key_str)) != NULL)
          {
            // If a previous pulse was found, replace the value.
            value_str = (gchar *)g_ptr_array_index(found_array, 0);
            g_ptr_array_add((GPtrArray *)value_array, (gpointer)g_strdup_printf("%s", value_str));
          }
          else
          {
            // If not, add the new one.
            value_array = g_ptr_array_new_with_free_func ((GDestroyNotify)g_free);
            value_str = (gchar *)g_ptr_array_index(found_array, 0);
            g_ptr_array_add((GPtrArray *)value_array, (gpointer)g_strdup_printf("%s", value_str));
            g_hash_table_insert (result_htable, (gpointer)g_strdup_printf("%s", key_str), (gpointer)value_array);
          }
        }
      }
    }
  }

  avr_db_unref_data (correlation->_priv->db, data);

  if (result_htable != NULL)
  {
    result_str = _avr_correlation_build_list_string(result_htable, value_array);

    g_hash_table_unref (result_htable);
    g_debug("Matched Pulse '%s'", result_str->str);
  }

  return (result_str ? g_string_free (result_str, FALSE) : NULL);
}

/**
 * _avr_correlation_build_list_string:
 * @void
 *
 *
 * Returns:
 */
static GString *
_avr_correlation_build_list_string(GHashTable *result_htable, GPtrArray *value_array)
{
  GHashTableIter result_htable_iter;
  GString * result_str = NULL;
  gpointer key = NULL;
  gpointer value = NULL;
  guint j = 0;

  g_hash_table_iter_init (&result_htable_iter, result_htable);
  while (g_hash_table_iter_next (&result_htable_iter, &key, &value))
  {
    if (result_str == NULL)
      result_str = g_string_new (NULL);
    else
      g_string_append_printf (result_str, ",");

    // Set the Pulse ID.
    g_string_append_printf (result_str, "\"%s\": [", (gchar *)key);

    // Append the IoC list.
    value_array = (GPtrArray *)value;
    for (j = 0; j < value_array->len; j++)
    {
      g_string_append_printf (result_str, "\"%s\"%s", (gchar *)g_ptr_array_index(value_array, j), (j + 1) >= value_array->len ? "" : ",");
    }

    // Close the list.
    g_string_append_printf (result_str, "]");
  }

  // Avoid delivering an unnecessary comma at the end.
  if ((result_str) && (result_str->str[result_str->len - 1] == ','))
    result_str = g_string_truncate(result_str, result_str->len - 1);

  return result_str;
}
