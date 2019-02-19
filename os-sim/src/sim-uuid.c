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

#include <config.h>

#include "sim-uuid.h"

#include <string.h>
#include <uuid/uuid.h>
#include <glib.h>
#include <glib/gprintf.h>

#define SIM_UUID_FORMAT "0x%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x%02x"
// the type of the argument of this macro must be uuid_t
#define SIM_UUID_FORMAT_STR(uuid) ((unsigned char *)uuid)[0], ((unsigned char *)uuid)[1], ((unsigned char *)uuid)[2], ((unsigned char *)uuid)[3], \
                                  ((unsigned char *)uuid)[4], ((unsigned char *)uuid)[5], ((unsigned char *)uuid)[6], ((unsigned char *)uuid)[7], \
                                  ((unsigned char *)uuid)[8], ((unsigned char *)uuid)[9], ((unsigned char *)uuid)[10], ((unsigned char *)uuid)[11], \
                                  ((unsigned char *)uuid)[12], ((unsigned char *)uuid)[13], ((unsigned char *)uuid)[14], ((unsigned char *)uuid)[15]

// This is only to help in conversions.
typedef union
{
  guchar   u[16];
  guint32  i[4];
}
uuid_int;

struct _SimUuidPrivate
{
  uuid_t id;
  gchar  db_string[35];  // Db insert string: 0x00112233445566778899AABBCCDDEEFF
  gchar  hex_string[37]; // Uuid hex string: 00112233-4455-6677-8899-AABBCCDDEEFF
};

#define SIM_UUID_GET_PRIVATE(self) (G_TYPE_INSTANCE_GET_PRIVATE ((self), SIM_TYPE_UUID, SimUuidPrivate))

SIM_DEFINE_TYPE (SimUuid, sim_uuid, G_TYPE_OBJECT, NULL)

/*
 * Private API
 */

/**
 * sim_uuid_class_init:
 * @klass: Pointer to SimUuidClass
 *
 * Init the class struct
 */
static void
sim_uuid_class_init (SimUuidClass *klass)
{
  GObjectClass *selfclass = G_OBJECT_CLASS (klass);

  g_type_class_add_private (klass, sizeof (SimUuidPrivate));
  parent_class = g_type_class_ref (G_TYPE_OBJECT);

  selfclass->finalize = sim_uuid_finalize;
}

/**
 * sim_uuid_instance_init:
 * @self: a #SimUuid
 *
 * This function initialice a instance of SimUuid
 */
static void
sim_uuid_instance_init (SimUuid *self)
{
  self->priv = SIM_UUID_GET_PRIVATE (self);

  memset (self->priv, 0, sizeof (SimUuidPrivate));
}

/**
 * sim_uuid_finalize:
 * @self: a #SimUuid
 *
 * This function finalize a instance of SimUuid
 */
static void
sim_uuid_finalize (GObject *self)
{
  G_OBJECT_CLASS (parent_class)->finalize (self);
}

static void
sim_uuid_create_db_string (SimUuid *uuid)
{
  g_sprintf (uuid->priv->db_string, SIM_UUID_FORMAT, SIM_UUID_FORMAT_STR (uuid->priv->id));
}

static void
sim_uuid_create_hex_string (SimUuid *uuid)
{
  uuid_unparse_lower (uuid->priv->id, uuid->priv->hex_string);
}

static void
sim_uuid_swap_bytes (SimUuid *uuid)
{
  guchar aux[4];

  /* Swap the 4 MSB with the 4 LSB */
  memcpy ((void *) aux, (const void *) &uuid->priv->id[0], 4);
  memcpy ((void *) &uuid->priv->id[0], (const void *) &uuid->priv->id[12], 4);
  memcpy ((void *) &uuid->priv->id[12], (const void *) aux, 4);
}


/*
 * Public API
 */

/**
 * sim_uuid_new:
 *
 * Returns: new #SimUuid object using uuid_generate_time() function.
 * This is less random and *could leak* info about your MAC
 * address, but it may hurt less the DB with indexes.
 */
SimUuid *
sim_uuid_new (void)
{
  SimUuid *uuid;

  uuid = SIM_UUID (g_object_new (SIM_TYPE_UUID, NULL));

  while ((uuid_generate_time_safe (uuid->priv->id)) != 0)
    g_usleep (10);

  /* Optimize for DB indexes */
  sim_uuid_swap_bytes (uuid);

  sim_uuid_create_db_string (uuid);
  sim_uuid_create_hex_string (uuid);

  return uuid;
}

/**
 * sim_uuid_new_from_bin:
 * @str: a pointer to a #guchar array.
 *
 * Allocates dynamic memory for those uuids that need it and
 * copies @str to it.
 */
SimUuid *
sim_uuid_new_from_bin (const guchar * str)
{
  SimUuid *uuid;
  gint i;

  g_return_val_if_fail (str != NULL, NULL);

  uuid = SIM_UUID (g_object_new (SIM_TYPE_UUID, NULL));

  for (i = 0; i < 16; i++)
    ((unsigned char *) uuid->priv->id)[i] = str[i];

  sim_uuid_create_db_string (uuid);
  sim_uuid_create_hex_string (uuid);

  return uuid;
}

SimUuid *
sim_uuid_new_from_blob (const GdaBlob *blob)
{
  SimUuid *uuid;

  g_return_val_if_fail (blob != NULL, NULL);

  uuid = SIM_UUID (g_object_new (SIM_TYPE_UUID, NULL));

  uuid_copy (uuid->priv->id, blob->data.data);

  sim_uuid_create_db_string (uuid);
  sim_uuid_create_hex_string (uuid);

  return uuid;
}

/**
 * sim_uuid_new_from_string:
 * @str: a string representation of a uuid.
 *
 * Returns a pointer to a new #SimUuid
 * using @str.
 */
SimUuid *
sim_uuid_new_from_string (const gchar * str)
{
  g_return_val_if_fail (str, NULL);

  SimUuid *uuid;

  uuid = SIM_UUID (g_object_new (SIM_TYPE_UUID, NULL));

  if (uuid_parse (str, uuid->priv->id) != 0)
  {
    g_object_unref (uuid);
    g_message ("Error parsing UUID: %s", str);

    return NULL;
  }

  sim_uuid_create_db_string (uuid);
  sim_uuid_create_hex_string (uuid);

  return uuid;
}

/**
 * sim_uuid_new_from_uuid:
 * @old_uuid: an uuid_t.
 *
 * Returns an uuid_t wrapped into a #SimUuid
 * using @old_uuid.
 */
SimUuid *
sim_uuid_new_from_uuid (uuid_t *old_uuid)
{
  g_return_val_if_fail (old_uuid, NULL);

  SimUuid *uuid;

  uuid = SIM_UUID (g_object_new (SIM_TYPE_UUID, NULL));

  uuid_copy (uuid->priv->id, *old_uuid);

  sim_uuid_create_db_string (uuid);
  sim_uuid_create_hex_string (uuid);

  return uuid;
}

/**
 * sim_uuid_to_str:
 * @id: a uuid_t array.
 *
 * Returns the hexadecimal value of an uuid_t.
 */
const gchar *
sim_uuid_get_string (SimUuid *id)
{
  g_return_val_if_fail (SIM_IS_UUID (id), NULL);

  return (id->priv->hex_string);
}

/**
 * sim_uuid_to_str:
 * @id: a uuid_t array.
 *
 * Returns the hexadecimal value of an uuid_t.
 */
const gchar *
sim_uuid_get_db_string (SimUuid *id)
{
  if (SIM_IS_UUID (id))
    return (id->priv->db_string);
  else
    return "NULL";
}

uuid_t *
sim_uuid_get_uuid (SimUuid *id)
{
  g_return_val_if_fail (SIM_IS_UUID (id), NULL);

  return &(id->priv->id);
}

/**
 * sim_uuid_hash:
 *
 */
guint
sim_uuid_hash (gconstpointer v)
{
  SimUuid *uuid = SIM_UUID (v);
  guint hash = 0;
  uuid_int key;
  guchar *id = (guchar *) uuid->priv->id;
  guint i;

  for (i = 0; i < 16; i++)
    key.u[i] = id[i];
  hash = key.i[0];
  hash ^= key.i[1];
  hash ^= key.i[2];
  hash ^= key.i[3];

  return (hash);
}

/**
 * sim_uuid_equal:
 *
 */
gboolean
sim_uuid_equal (gconstpointer v1, gconstpointer v2)
{
  SimUuid *uuid1 = (SimUuid *) v1;
  SimUuid *uuid2 = (SimUuid *) v2;

  g_return_val_if_fail (SIM_IS_UUID (uuid1), FALSE);
  g_return_val_if_fail (SIM_IS_UUID (uuid2), FALSE);

  return ((gboolean) !(uuid_compare (uuid1->priv->id, uuid2->priv->id)));
}

/**
 * sim_uuid_is_valid_string:
 * @srt
 *
 */
gboolean
sim_uuid_is_valid_string (const gchar *str)
{
  uuid_t id;

  g_return_val_if_fail (str, FALSE);

  if (uuid_parse (str, id) == 0)
    return TRUE;
  else
    return FALSE;
}

gchar *
sim_uuid_to_base64(SimUuid *id)
{
  g_return_val_if_fail (SIM_IS_UUID(id), NULL);
  return g_base64_encode ((guchar*)id->priv->id, 16);
}



