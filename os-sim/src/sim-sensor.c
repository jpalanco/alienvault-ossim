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

#include "sim-sensor.h"

#include <string.h>

#include "sim-uuid.h"
#include "sim-log.h"

enum
{
  DESTROY,
  LAST_SIGNAL
};

struct _SimSensorPrivate
{
  SimUuid     *id;
  gchar       *name;

  SimInet     *ia;
  gint         port;

  gboolean     connect;
  gboolean     compress;
  gboolean     ssl;

  GHashTable  *plugins; //SimPlugin

  SimVersion  * ver;
  gfloat      tzone;
};

static gpointer parent_class = NULL;
/* no signals
static gint sim_inet_signals[LAST_SIGNAL] = { 0 };
*/

/* GType Functions */

static void
sim_sensor_impl_dispose (GObject  *gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void
sim_sensor_impl_finalize (GObject  *gobject)
{
  SimSensor *sensor = SIM_SENSOR (gobject);

  if (sensor->_priv->id)
    g_object_unref (sensor->_priv->id);

  if (sensor->_priv->ia)
    g_object_unref (sensor->_priv->ia);

  if (sensor->_priv->plugins)
    g_hash_table_destroy (sensor->_priv->plugins);

  g_free (sensor->_priv->ver);
  g_free (sensor->_priv);

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_sensor_class_init (SimSensorClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_ref (G_TYPE_OBJECT);

  object_class->dispose = sim_sensor_impl_dispose;
  object_class->finalize = sim_sensor_impl_finalize;
}

static void
sim_sensor_instance_init (SimSensor *sensor)
{
  sensor->_priv = g_new0 (SimSensorPrivate, 1);

  sensor->_priv->id = NULL;
  sensor->_priv->name = NULL;

  sensor->_priv->ia = NULL;
  sensor->_priv->port = 0;

  sensor->_priv->connect = FALSE;
  sensor->_priv->compress = FALSE;
  sensor->_priv->ssl = FALSE;
  sensor->_priv->ver = NULL;
  sensor->_priv->tzone = 0.0;

  sensor->_priv->plugins = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, g_object_unref);
}

/* Public Methods */
SimVersion *
sim_sensor_get_version (SimSensor *sensor)
{
  g_return_val_if_fail (SIM_IS_SENSOR (sensor), NULL);

  return g_atomic_pointer_get (&sensor->_priv->ver);
}

void
sim_sensor_set_version (SimSensor  *sensor,
                        SimVersion *ver)
{
  SimVersion *new_version;

  g_return_if_fail (SIM_IS_SENSOR (sensor));

  if (sensor->_priv->ver)
    g_free (sensor->_priv->ver);

  new_version = g_memdup (ver, sizeof(SimVersion));

  g_atomic_pointer_set (&sensor->_priv->ver, new_version);
}

GType
sim_sensor_get_type (void)
{
  static GType object_type = 0;

  if (!object_type)
  {
    static const GTypeInfo type_info = {
              sizeof (SimSensorClass),
              NULL,
              NULL,
              (GClassInitFunc) sim_sensor_class_init,
              NULL,
              NULL,                       /* class data */
              sizeof (SimSensor),
              0,                          /* number of pre-allocs */
              (GInstanceInitFunc) sim_sensor_instance_init,
              NULL                        /* value table */
    };


    object_type = g_type_register_static (G_TYPE_OBJECT, "SimSensor", &type_info, 0);
  }

  return object_type;
}

/*
 *
 *
 *
 *
 */
SimSensor*
sim_sensor_new (void)
{
  SimSensor *sensor = NULL;

  sensor = SIM_SENSOR (g_object_new (SIM_TYPE_SENSOR, NULL));

  sensor->_priv->id = sim_uuid_new ();

  return sensor;
}

SimSensor*
sim_sensor_new_from_hostname (gchar *sensor_ip)
{
  SimSensor *sensor = NULL;

  SimInet * inet = sim_inet_new_from_string (sensor_ip);

  if (inet)
  {
    sensor = SIM_SENSOR (g_object_new (SIM_TYPE_SENSOR, NULL));
    sensor->_priv->ia = inet;
    sensor->_priv->id = sim_uuid_new ();
  }
  else
  {
    return (NULL);
  }

  return sensor;
}

SimSensor *
sim_sensor_new_from_ia (SimInet *ia)
{
  SimSensor * sensor = NULL;

  g_return_val_if_fail (SIM_IS_INET (ia), NULL);

  sensor =  SIM_SENSOR (g_object_new (SIM_TYPE_SENSOR, NULL));
  if (sensor)
  {
    sensor->_priv->id = sim_uuid_new ();
    sensor->_priv->ia = sim_inet_clone (ia);
  }
  return sensor;
}

/**
 * sim_sensor_new_from_dm:
 *
 *
 */
SimSensor*
sim_sensor_new_from_dm (GdaDataModel  *dm, gint row)
{
  SimSensor    *sensor;
  const GValue *value;
  GValue  smallint = { 0, {{0}, {0}} };
  const GdaBinary * binary;

  g_return_val_if_fail (GDA_IS_DATA_MODEL (dm), NULL);

  sensor = SIM_SENSOR (g_object_new (SIM_TYPE_SENSOR, NULL));

  value = gda_data_model_get_value_at (dm, 0, row, NULL);
  sensor->_priv->id = sim_uuid_new_from_blob (gda_value_get_blob (value));

  value = gda_data_model_get_value_at (dm, 1, row, NULL);
  sensor->_priv->name = g_value_dup_string (value);

  value = gda_data_model_get_value_at (dm, 2, row, NULL);
  if (!gda_value_is_null (value))
  {
    binary = (GdaBinary *) gda_value_get_blob (value);
    sensor->_priv->ia = sim_inet_new_from_db_binary (binary->data, binary->binary_length);
  }

  if (!sensor->_priv->ia)
  {
    g_message ("Invalid address for sensor %s", sensor->_priv->name);
    g_object_unref (sensor);
    return (NULL);
  }

  value = gda_data_model_get_value_at (dm, 3, row, NULL);
  sensor->_priv->port = g_value_get_int (value);
  sim_inet_set_port (sensor->_priv->ia, sensor->_priv->port);

  value = gda_data_model_get_value_at (dm, 4, row, NULL);
  // FIXME: temporal fix until GDA reads smallints properly
  g_value_init (&smallint, GDA_TYPE_SHORT);
  g_value_transform (value, &smallint);
  sensor->_priv->connect = gda_value_get_short (&smallint);

  value = gda_data_model_get_value_at (dm, 5, row, NULL);
  sensor->_priv->tzone = g_value_get_float (value);

  value = gda_data_model_get_value_at (dm, 6, row, NULL);
  if (!gda_value_is_null (value) && g_value_get_string (value))
  {
    sensor->_priv->ver = g_new0 (SimVersion, 1);
    sim_version_parse (g_value_get_string (value),
                       &(sensor->_priv->ver->major),
                       &(sensor->_priv->ver->minor),
                       &(sensor->_priv->ver->micro),
                       &(sensor->_priv->ver->nano));
  }

  return sensor;
}

/*
 *
 *
 *
 *
 */
SimSensor*
sim_sensor_clone (SimSensor *sensor)
{
  SimSensor				*new_sensor;
	GHashTableIter	iter;
	gpointer 				key, value;
  
  g_return_val_if_fail (SIM_IS_SENSOR (sensor), NULL);

  new_sensor = SIM_SENSOR (g_object_new (SIM_TYPE_SENSOR, NULL));
  new_sensor->_priv->name = g_strdup (sensor->_priv->name);
  new_sensor->_priv->ia = (sensor->_priv->ia) ? sim_inet_clone (sensor->_priv->ia) : NULL;
  new_sensor->_priv->port = sensor->_priv->port;
  new_sensor->_priv->connect = sensor->_priv->connect;
  new_sensor->_priv->compress = sensor->_priv->compress;
  new_sensor->_priv->ssl = sensor->_priv->ssl;
  new_sensor->_priv->ver = g_memdup (sensor->_priv->ver, sizeof(SimVersion));
  new_sensor->_priv->tzone = sensor->_priv->tzone;

  new_sensor->_priv->plugins = g_hash_table_new_full (g_direct_hash, g_direct_equal, NULL, g_object_unref);
	g_hash_table_iter_init(&iter, sensor->_priv->plugins);
  while (g_hash_table_iter_next(&iter, &key, &value))
		g_hash_table_insert(new_sensor->_priv->plugins, key, sim_plugin_clone((SimPlugin*) value));

  return new_sensor;
}

/**
 * sim_sensor_get_id:
 * @sensor: a #SimSensor object.
 *
 * Returns the id number of @sensor.
 */
SimUuid *
sim_sensor_get_id (SimSensor * sensor)
{
  g_return_val_if_fail (SIM_IS_SENSOR (sensor), 0);

  return (sensor->_priv->id);
}

/**
 * sim_sensor_set_id:
 * @sensor: a #SimSensor object.
 *
 * Sets the id number of @sensor.
 */
void
sim_sensor_set_id (SimSensor * sensor,
                   SimUuid   * id)
{
  g_return_if_fail (SIM_IS_SENSOR (sensor));

  SimUuid * old_id = sensor->_priv->id;

  g_atomic_pointer_set (&sensor->_priv->id, g_object_ref (id));
  g_object_unref (old_id);

  return;
}


/*
 *
 *
 *
 *
 */
gchar*
sim_sensor_get_name (SimSensor  *sensor)
{
  g_return_val_if_fail (SIM_IS_SENSOR (sensor), NULL);

  return sensor->_priv->name;
}

/*
 *
 *
 *
 *
 */
void
sim_sensor_set_name (SimSensor  *sensor,
                     gchar      *name)
{
  g_return_if_fail (SIM_IS_SENSOR (sensor));
  g_return_if_fail (name);

  if (sensor->_priv->name)
    g_free (sensor->_priv->name);

  sensor->_priv->name = g_strdup (name);
}

/*
 *
 *
 *
 *
 */
SimInet *
sim_sensor_get_ia (SimSensor  *sensor)
{
  g_return_val_if_fail (SIM_IS_SENSOR (sensor), NULL);

  return sensor->_priv->ia;
}

/*
 *
 *
 *
 *
 */
void
sim_sensor_set_ia (SimSensor   *sensor,
                   SimInet     *ia)
{
  g_return_if_fail (SIM_IS_SENSOR (sensor));
  g_return_if_fail (SIM_IS_INET (ia));

  SimInet * old_ia = sensor->_priv->ia;
  g_atomic_pointer_set (&sensor->_priv->ia, ia);

  if (old_ia)
    g_object_unref (old_ia);
}

/*
 *
 *
 *
 *
 */
gint
sim_sensor_get_port (SimSensor  *sensor)
{
  g_return_val_if_fail (SIM_IS_SENSOR (sensor), 0);

  return sensor->_priv->port;
}

/*
 *
 *
 *
 *
 */
void
sim_sensor_set_port (SimSensor  *sensor,
                     gint        port)
{
  g_return_if_fail (SIM_IS_SENSOR (sensor));
  g_return_if_fail (port > 0);

  sensor->_priv->port = port;
}

/*
 *
 *
 *
 *
 */
gboolean
sim_sensor_is_connect (SimSensor  *sensor)
{
  g_return_val_if_fail (SIM_IS_SENSOR (sensor), FALSE);

  return sensor->_priv->connect;
}

/*
 *
 *
 *
 *
 */
void
sim_sensor_set_connect (SimSensor  *sensor,
		       gboolean   connect)
{
  g_return_if_fail (SIM_IS_SENSOR (sensor));

  sensor->_priv->connect = connect;
}

/*
 *
 *
 *
 *
 */
gboolean
sim_sensor_is_compress (SimSensor  *sensor)
{
  g_return_val_if_fail (SIM_IS_SENSOR (sensor), FALSE);

  return sensor->_priv->compress;
}

/*
 *
 *
 *
 *
 */
void
sim_sensor_set_compress (SimSensor  *sensor,
                         gboolean   compress)
{
  g_return_if_fail (SIM_IS_SENSOR (sensor));

  sensor->_priv->compress = compress;
}

/*
 *
 *
 *
 *
 */
gboolean
sim_sensor_is_ssl (SimSensor  *sensor)
{
  g_return_val_if_fail (SIM_IS_SENSOR (sensor), FALSE);

  return sensor->_priv->ssl;
}

/*
 *
 *
 *
 *
 */
void
sim_sensor_set_ssl (SimSensor  *sensor,
		   gboolean   ssl)
{
  g_return_if_fail (SIM_IS_SENSOR (sensor));

  sensor->_priv->ssl = ssl;
}

/*
 *
 *
 *
 *
 */
void
sim_sensor_insert_plugin (SimSensor   *sensor,
                          SimPlugin   *plugin)
{
  SimPlugin   *tmp;
  gint         key;

  g_return_if_fail (SIM_IS_SENSOR (sensor));
  g_return_if_fail (SIM_IS_PLUGIN (plugin));
  g_return_if_fail (sim_plugin_get_id (plugin) > 0);

  key = sim_plugin_get_id (plugin);
  if ((tmp = g_hash_table_lookup (sensor->_priv->plugins, GINT_TO_POINTER (key))))
    {
      g_object_unref (tmp);
      g_hash_table_replace (sensor->_priv->plugins, GINT_TO_POINTER (key), plugin);
    }
  else
    {
      g_hash_table_insert (sensor->_priv->plugins, GINT_TO_POINTER (key), plugin);
    }
}

/*
 *
 *
 *
 *
 */
void
sim_sensor_remove_plugin (SimSensor   *sensor,
                          SimPlugin   *plugin)
{
  gint         key;

  g_return_if_fail (SIM_IS_SENSOR (sensor));
  g_return_if_fail (SIM_IS_PLUGIN (plugin));
  g_return_if_fail (sim_plugin_get_id (plugin) > 0);

  key = sim_plugin_get_id (plugin);
  g_hash_table_remove (sensor->_priv->plugins, GINT_TO_POINTER (key));
}

/*
 *
 *
 *
 *
 */
SimPlugin*
sim_sensor_get_plugin_by_id (SimSensor    *sensor,
                             gint         id)
{
  g_return_val_if_fail (SIM_IS_SENSOR (sensor), NULL);
  g_return_val_if_fail (id > 0, NULL);
  
  return (SimPlugin *) g_hash_table_lookup (sensor->_priv->plugins, GINT_TO_POINTER (id));
}

/*
 *
 *
 *
 *
 */
static void
append_plugin_to_list (gpointer key, gpointer value, gpointer user_data)
{
  GList **list = (GList **) user_data;

  // unused parameter
  (void) key;
  
  *list = g_list_append (*list, value);
}

/*
 *
 *
 *
 *
 */
GList*
sim_sensor_get_plugins (SimSensor    *sensor)
{
  GList *list = NULL;

  g_return_val_if_fail (SIM_IS_SENSOR (sensor), NULL);
  g_return_val_if_fail (sensor->_priv->plugins, NULL);

  g_hash_table_foreach (sensor->_priv->plugins, (GHFunc) append_plugin_to_list, &list);

  return list;
}

/*
 *
 *
 *
 *
 */
gboolean
sim_sensor_has_plugin_by_type (SimSensor       *sensor,
			      SimPluginType   type)
{
  GList     *list;
  GList     *node;
  gboolean   found = FALSE;

  g_return_val_if_fail (SIM_IS_SENSOR (sensor), FALSE);

  list = sim_sensor_get_plugins (sensor);
  node = list;
  while (node)
  {
    SimPlugin *plugin = (SimPlugin *) node->data;

    if (plugin->type == type)
    {
      found = TRUE;
      break;
    }

    node = node->next;
  }
  g_list_free (list);

  return found;
}

gfloat
sim_sensor_get_tzone (SimSensor *sensor)
{
  g_return_val_if_fail (SIM_IS_SENSOR (sensor), 0.0);

  return sensor->_priv->tzone;
}

void
sim_sensor_set_tzone (SimSensor *sensor, gfloat tzone)
{
  g_return_if_fail (SIM_IS_SENSOR (sensor));

  sensor->_priv->tzone = tzone;
}

// vim: set tabstop=2:
