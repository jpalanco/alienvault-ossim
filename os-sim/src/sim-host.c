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

#include "sim-host.h"

#include <uuid/uuid.h>
#include <math.h>

#include "sim-enums.h"
#include "sim-util.h"
#include "sim-log.h"
#include "sim-uuid.h"

struct _SimHostPrivate
{
  SimUuid    *id;
  GPtrArray  *inets;
  gchar      *name;
  gint        asset;
  gboolean    external;
  GMutex     mutex;
};

SIM_DEFINE_TYPE (SimHost, sim_host, G_TYPE_OBJECT, NULL)

/* GType Functions */

static void
sim_host_finalize (GObject  *gobject)
{
  SimHost *host = SIM_HOST (gobject);

  if (host->_priv->id)
    g_object_unref (host->_priv->id);
  if (host->_priv->inets)
    g_ptr_array_unref (host->_priv->inets);
  if (host->_priv->name)
    g_free (host->_priv->name);
  g_mutex_clear (&host->_priv->mutex);

  g_free (host->_priv);

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_host_class_init (SimHostClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_peek_parent (class);

  object_class->finalize = sim_host_finalize;
}

static void
sim_host_instance_init (SimHost *host)
{
  host->_priv = g_new0 (SimHostPrivate, 1);

  host->_priv->id = NULL;
  host->_priv->inets = g_ptr_array_new_with_free_func (g_object_unref);
  host->_priv->name = NULL;
  host->_priv->asset = DEFAULT_ASSET;
  host->_priv->external = TRUE;
  g_mutex_init(&host->_priv->mutex);
}

/* Public Methods */

/**
 *  sim_host_new:
 *  @inet: SimInet object
 *  @id: host uuid, if NULL a random one will be created
 *  @name: string host name
 *  @asset: host asset
 *
 *  Return value: new SimHost object
 */
SimHost*
sim_host_new (SimInet     *inet,
              SimUuid     *id,
              const gchar *name,
              gint         asset)
{
  SimHost *host;

  g_return_val_if_fail (SIM_IS_INET (inet), NULL);
  g_return_val_if_fail (name, NULL);

  host = SIM_HOST (g_object_new (SIM_TYPE_HOST, NULL));

  host->_priv->id = id ? g_object_ref (id) : sim_uuid_new ();
  g_ptr_array_add (host->_priv->inets, g_object_ref (inet));
  host->_priv->name = g_strdup (name);
  host->_priv->asset = asset;
  host->_priv->external = TRUE;   // This is always TRUE because hosts from home network
                                  // shouldn't be added automatically.

  return host;
}

/**
 *  sim_host_new_from_dm:
 *  @dm: DataModel object
 *  @row: query row number
 *
 *  Return value: new SimHost object
 */
SimHost*
sim_host_new_from_dm (GdaDataModel  *dm, gint row)
{
  SimHost      *host;
  const GValue *value;
  GValue        smallint = { 0, {{0}, {0}} };

  g_return_val_if_fail (GDA_IS_DATA_MODEL (dm), NULL);

  host = SIM_HOST (g_object_new (SIM_TYPE_HOST, NULL));

  value = gda_data_model_get_value_at (dm, 0, row, NULL);
  host->_priv->id = sim_uuid_new_from_blob (gda_value_get_blob (value));

  value = gda_data_model_get_value_at (dm, 1, row, NULL);
  host->_priv->name = g_value_dup_string (value);

  value = gda_data_model_get_value_at (dm, 2, row, NULL);
  ossim_debug ("%s: GValue type %s", __func__, G_VALUE_TYPE_NAME (value));
  // FIXME: temporal fix until GDA reads smallints properly
  g_value_init (&smallint, GDA_TYPE_SHORT);
  g_value_transform (value, &smallint);
  host->_priv->asset = gda_value_get_short (&smallint);

  value = gda_data_model_get_value_at (dm, 3, row, NULL);
  host->_priv->external = gda_value_is_null (value) ? FALSE : (gboolean) g_value_get_int (value);

  ossim_debug ( "sim_host_new_from_dm: %s", host->_priv->name);
  sim_host_debug_print (host);

  return host;
}

/**
 *  sim_host_clone:
 *  @host: SimHost object
 *
 *  Return value: new SimHost object
 */
SimHost *
sim_host_clone (SimHost *host)
{
  SimHost *new_host;

  g_return_val_if_fail (SIM_IS_HOST (host), NULL);

  new_host = SIM_HOST (g_object_new (SIM_TYPE_HOST, NULL));

  new_host->_priv->id = g_object_ref (host->_priv->id);
  new_host->_priv->inets = g_ptr_array_ref (host->_priv->inets);
  new_host->_priv->name = g_strdup (host->_priv->name);
  new_host->_priv->asset = host->_priv->asset;
  new_host->_priv->external = host->_priv->external;

  return new_host;
}

/**
 *  sim_host_get_id:
 *  @host: SimHost object
 *
 *  Returns: @host uuid (Must be freed outside)
 */
SimUuid *
sim_host_get_id (SimHost *host)
{
  g_return_val_if_fail (SIM_IS_HOST (host), NULL);

  return host->_priv->id;
}


/**
 *  sim_host_get_inets:
 *  @host: SimHost object
 *
 *  Return value: GPtrArray with #SimInet objects
 */
GPtrArray *
sim_host_get_inets (SimHost *host)
{
  g_return_val_if_fail (SIM_IS_HOST (host), NULL);

  return host->_priv->inets;
}

/**
 *  sim_host_add_inet:
 *  @host: SimHost object
 *  @inet: Inet object to add
 *
 */
void
sim_host_add_inet (SimHost  *host,
                   SimInet  *inet)
{
  g_return_if_fail (SIM_IS_HOST (host));
  g_return_if_fail (SIM_IS_INET (inet));

  g_ptr_array_add (host->_priv->inets, g_object_ref (inet));
}

/**
 *  sim_host_get_name:
 *  @host: SimHost object
 *
 *  Return value: host name
 */
gchar*
sim_host_get_name (SimHost  *host)
{
  g_return_val_if_fail (SIM_IS_HOST (host), NULL);

  return host->_priv->name;
}

/**
 *  sim_host_set_name:
 *  @host: SimHost object
 *  @name: name string to set
 *
 */
void
sim_host_set_name (SimHost      *host,
                   const gchar  *name)
{
  g_return_if_fail (SIM_IS_HOST (host));
  g_return_if_fail (name);

  if (host->_priv->name)
    g_free (host->_priv->name);

  host->_priv->name = g_strdup (name);
}

/**
 *  sim_host_get_asset:
 *  @host: SimHost object
 *
 *  Return value: host asset
 */
gint
sim_host_get_asset (SimHost  *host)
{
  g_return_val_if_fail (SIM_IS_HOST (host), 0);

  if (host->_priv->asset < 0)
    return 0;
  if (host->_priv->asset > 5)
    return 5;

  return host->_priv->asset;
}

/**
 *  sim_host_set_asset:
 *  @host: SimHost object
 *  @asset: asset to set
 *
 */
void
sim_host_set_asset (SimHost  *host,
                    gint      asset)
{
  g_return_if_fail (SIM_IS_HOST (host));

  if (asset < 0)
    host->_priv->asset = 0;
  else if (asset > 5)
    host->_priv->asset = 5;
  else host->_priv->asset = asset;
}

/**
 * sim_host_get_external:
 * @host: #SimHost object
 *
 */
gboolean
sim_host_get_external (SimHost * host)
{
  g_return_val_if_fail (SIM_IS_HOST (host), FALSE);

  return (host->_priv->external);
}

/**
 * sim_host_set_external:
 * @host: #SimHost object
 * @external: gboolean value.
 *
 */
void
sim_host_set_external (SimHost * host,
                       gboolean  external)
{
  g_return_if_fail (SIM_IS_HOST (host));
  host->_priv->external = external;
  return;
}

/**
 *  sim_host_debug_print:
 *  @host: SimHost object
 *
 */
void
sim_host_debug_print (SimHost	*host)
{
  guint i;

  g_return_if_fail (SIM_IS_HOST (host));

  ossim_debug ("sim_host_debug_print");
  ossim_debug ("     name: %s", host->_priv->name);
  for (i = 0; i < host->_priv->inets->len; i ++)
  {
    SimInet *inet = (SimInet *) g_ptr_array_index (host->_priv->inets, i);
    gchar *ip_str = sim_inet_get_canonical_name (inet);
    ossim_debug ("     ip: %s", ip_str);
    g_free (ip_str);
  }
  ossim_debug ("     asset: %d", host->_priv->asset);
}

// vim: set tabstop=2:
