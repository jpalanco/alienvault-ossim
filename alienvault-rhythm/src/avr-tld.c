// vim: ts=2:sw=2:sts=2:expandtab
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

#include "config.h"

#include "avr-tld.h"

#define AVR_TLD_FILE_PATH "/etc/alienvault/rhythm/effective_tld_names.dat"

struct _AvrTldPrivate
{
    GHashTable *tld_htable;
};

static gpointer parent_class = NULL;

#define AVR_TLD_GET_PRIVATE(self) (G_TYPE_INSTANCE_GET_PRIVATE ((self), AVR_TYPE_TLD, AvrTldPrivate))


static gboolean _avr_tld_load_file (AvrTld *);

static void
avr_tld_finalize (GObject *object)
{
  AvrTldPrivate *priv = AVR_TLD_GET_PRIVATE (object);

  if (priv->tld_htable != NULL)
      g_hash_table_unref(priv->tld_htable);

  G_OBJECT_CLASS (parent_class)->finalize (object);
}

static void
avr_tld_class_init (AvrTldClass *klass)
{
  GObjectClass *object_class = G_OBJECT_CLASS (klass);

  object_class->finalize = avr_tld_finalize;

  parent_class = g_type_class_peek_parent (klass);

  g_type_class_add_private (klass, sizeof (AvrTldPrivate));
}

static void
avr_tld_instance_init (AvrTld *self)
{
  self->priv = AVR_TLD_GET_PRIVATE (self);

  self->priv->tld_htable = g_hash_table_new_full (g_str_hash,
                                                 g_str_equal,
                                                 g_free,
                                                 NULL);
}

GType
avr_tld_get_type (void)
{
  static GType object_type = 0;

  if (!object_type)
  {
    static const GTypeInfo type_info = {
      sizeof (AvrTldClass),
      NULL,
      NULL,
      (GClassInitFunc) avr_tld_class_init,
      NULL,
      NULL,                       /* class data */
      sizeof (AvrTld),
      0,                          /* number of pre-allocs */
      (GInstanceInitFunc) avr_tld_instance_init,
      NULL                        /* value table */
    };

    object_type = g_type_register_static (G_TYPE_OBJECT, "AvrTld", &type_info, 0);
  }
  return object_type;
}

/*
 * PUBLIC API
 */

AvrTld *
avr_tld_new()
{
  AvrTld * tld = NULL;

  tld = AVR_TLD(g_object_new (AVR_TYPE_TLD, NULL));
  _avr_tld_load_file(tld);

  return tld;
}


static gboolean
_avr_tld_load_file (AvrTld *tld)
{
  gchar *buf = NULL;
  gsize len = 0;
  gsize pos = 0;
  gint total = 0;
  GError *err = NULL;
  GIOStatus status = G_IO_STATUS_NORMAL;

  GIOChannel *tld_file = g_io_channel_new_file (AVR_TLD_FILE_PATH, "r", NULL);
  if (tld_file == NULL)
  {
    g_warning ("Cannot open file '%s'", AVR_TLD_FILE_PATH);
    return FALSE;
  }

  // Process the file
  while (status != G_IO_STATUS_ERROR && status != G_IO_STATUS_EOF)
  {
    status = g_io_channel_read_line(tld_file, &buf, &len, &pos, &err);
    if (buf != NULL)
    {
      if (len < 3 || (buf[0] == '/' && buf[1] == '/'))
      {
          g_free(buf);
          continue;
      }

      g_hash_table_insert (tld->priv->tld_htable, g_strndup(buf, len - 1), GINT_TO_POINTER (1));
      total ++;
      g_free(buf);
    }
  }
  g_io_channel_unref(tld_file);

  g_message("TLD: %d Domains loaded", total);

  return TRUE;
}

/**
 * avr_tld_get_domain:
 * @gchar *
 *
 * Returns: the 2nd level domain of the full_name or
 * NULL when the name is not valid or full_name is a
 * 2nd level domain itself
 */
gchar *
avr_tld_get_domain(AvrTld *tld,
                   const gchar *full_name)
{
  g_return_val_if_fail(tld != NULL, NULL);
  g_return_val_if_fail(full_name != NULL, NULL);

  gchar *iterator = NULL;
  gchar *last_dot = NULL;
  gchar *prev_dot = NULL;

  iterator = (gchar *)full_name;
  last_dot = iterator;
  prev_dot = iterator;

  if (g_hash_table_lookup(tld->priv->tld_htable, iterator))
      return NULL;

  while (*iterator++)
  {
    if (*iterator == '.')
    {
      prev_dot = last_dot;
      last_dot = iterator + 1;
      if (g_hash_table_lookup(tld->priv->tld_htable, last_dot))
          break;
    }
  }

  if (prev_dot == full_name)
    return NULL;

  return g_strdup(prev_dot);
}
