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
  gdouble     a;
  gdouble     c;
  gboolean    external;
  gboolean    loaded_from_db;
  GMutex     *mutex;
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
	if (host->_priv->mutex)
		g_mutex_free (host->_priv->mutex);

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
  host->_priv->a = host->_priv->c = 0.0f;
  host->_priv->external = TRUE;
  host->_priv->loaded_from_db = FALSE;
  host->_priv->mutex = g_mutex_new ();
  g_return_if_fail (host->_priv->mutex != NULL);
}

/* Public Methods */

/**
 *  sim_host_new:
 *  @inet: SimInet object
 *  @id: host uuid, if NULL a random one will be created
 *  @name: string host name
 *  @asset: host asset
 *  @c: host compromise level
 *  @a: host attack level
 *
 * Note the query we used is this:
 * SELECT h.id, h.hostname, h.asset, hi.ip, hq.compromise, hq.attack, h.external_host FROM host h
 * LEFT JOIN host_qualification hq ON h.id = hq.host_id, host_ip hi WHERE ctx = context_id AND h.id = hi.host_id
 *
 *  Return value: new SimHost object
 */
SimHost*
sim_host_new (SimInet     *inet,
              SimUuid     *id,
              const gchar *name,
              gint         asset,
              gdouble      c,
              gdouble      a)
{
  SimHost *host;

  g_return_val_if_fail (SIM_IS_INET (inet), NULL);
  g_return_val_if_fail (name, NULL);

  host = SIM_HOST (g_object_new (SIM_TYPE_HOST, NULL));

  host->_priv->id = id ? g_object_ref (id) : sim_uuid_new ();
  g_ptr_array_add (host->_priv->inets, g_object_ref (inet));
  host->_priv->name = g_strdup (name);
  host->_priv->asset = asset;
  host->_priv->c = c;
  host->_priv->a = a;
  host->_priv->loaded_from_db = FALSE;
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

  host->_priv->loaded_from_db = TRUE;

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
  host->_priv->c = gda_value_is_null (value) ? 0 : g_value_get_int (value);

  value = gda_data_model_get_value_at (dm, 4, row, NULL);
  host->_priv->a = gda_value_is_null (value) ? 0 : g_value_get_int (value);

  value = gda_data_model_get_value_at (dm, 5, row, NULL);
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
  new_host->_priv->a = host->_priv->a;
  new_host->_priv->c = host->_priv->c;
  new_host->_priv->external = host->_priv->external;
  new_host->_priv->loaded_from_db = host->_priv->loaded_from_db;

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
  ossim_debug ("     a: %f", host->_priv->a);
  ossim_debug ("     c: %f", host->_priv->c);
}

/**
	@brief Return the attack value of a Host
	
	@param host Pointer to a SimHost object
	@return A double with the attack  value
*/

inline
gdouble
sim_host_get_a (SimHost  *host)
{
  g_return_val_if_fail (host, 0);
  g_return_val_if_fail (SIM_IS_HOST (host), 0);

  return host->_priv->a;
}
/**
	@brief Return the compromise value of a Host
	
	@param host Pointer to a SimHost object
	@return A double with the compromise value
*/
inline
gdouble
sim_host_get_c (SimHost  *host)
{
  g_return_val_if_fail (SIM_IS_HOST (host), 0);

  return host->_priv->c;
}
/**
	@brief Set the attack value of a Host
	
	@param host Pointer to a SimHost object
	@param a Value of attack
*/

inline
void
sim_host_set_a (SimHost  *host,gdouble a)
{
  g_return_if_fail (SIM_IS_HOST (host));
	g_mutex_lock (host->_priv->mutex);
  host->_priv->a = a;
	g_mutex_unlock (host->_priv->mutex);
}
/**
	@brief Set the compromise value of a Host
	
	@param host Pointer to a SimHost object
	@param c Value of compromise
*/
inline
void
sim_host_set_c (SimHost  *host, gdouble c)
{
  g_return_if_fail (SIM_IS_HOST (host));
	g_mutex_lock (host->_priv->mutex);
  host->_priv->c = c;
	g_mutex_unlock (host->_priv->mutex);
}

/**	
	@brief Update the Compromise value of the host
	
	@param host Pointer to the SimHost object to be modified
*/
inline 
void sim_host_update_c (SimHost *self,gdouble value){
	g_return_if_fail (SIM_IS_HOST (self));
	g_mutex_lock (self->_priv->mutex);
	self->_priv->c += value;
	g_mutex_unlock (self->_priv->mutex);
}
/**	
	@brief Update the Attack value of the host
	
	@param host Pointer to the SimHost object to be modified
*/
inline 
void sim_host_update_a (SimHost *self,gdouble value){
	g_return_if_fail (SIM_IS_HOST (self));
	g_mutex_lock (self->_priv->mutex);
	self->_priv->a += value;
	g_mutex_unlock (self->_priv->mutex);
}


gboolean
sim_host_level_set_recovery (SimHost  *host,
                             gint      recovery)
{
  g_return_val_if_fail (SIM_IS_HOST (host), FALSE);
  g_return_val_if_fail (recovery >= 0, FALSE);

  if ((recovery == 0) || (host->_priv->c == 0 && host->_priv->a == 0))
    return (FALSE);

  g_mutex_lock (host->_priv->mutex);
  if (host->_priv->c > recovery)
    host->_priv->c -= recovery;
  else
    host->_priv->c = 0;

  if (host->_priv->a > recovery)
    host->_priv->a -= recovery;
  else
    host->_priv->a = 0;
  g_mutex_unlock (host->_priv->mutex);

  return (TRUE);
}

/**
 * sim_host_level_is_zero:
 * @host: #SimHost object
 *
 * Returns %TRUE if 'C' and 'A' are 0, %FALSE otherwise
 */
gboolean
sim_host_level_is_zero (SimHost *host)
{
  g_return_val_if_fail (SIM_IS_HOST (host), FALSE);

  return (host->_priv->c == 0 && host->_priv->a == 0);
}

/*
 *
 * Insert the level of a host into host_qualification table. Returns NULL on error.
 *
 */
gchar*
sim_host_level_get_insert_clause (SimHost *host)
{
  gchar *query;
  gint   c;
  gint   a;

  g_return_val_if_fail (SIM_IS_HOST (host), NULL);

  c = rint (host->_priv->c);
  a = rint (host->_priv->a);

  query = g_strdup_printf ("INSERT INTO host_qualification (host_id, compromise, attack) VALUES (%s, %d, %d)",
                           sim_uuid_get_db_string (host->_priv->id), c, a);

  return query;
}

/*
 *
 *
 *
 */
gchar*
sim_host_level_get_update_clause (SimHost *host)
{
  gchar *query;
  gint   c;
  gint   a;

  g_return_val_if_fail (SIM_IS_HOST (host), NULL);

  c = rint (host->_priv->c);
  a = rint (host->_priv->a);

  query = g_strdup_printf ("REPLACE INTO host_qualification (host_id, compromise, attack) VALUES(%s, %d, %d) ",
                           sim_uuid_get_db_string (host->_priv->id), c, a);

	return query;
}

/*
 *
 *
 *
 */
gchar*
sim_host_level_get_delete_clause (SimHost  *host)
{
  gchar *query;

  g_return_val_if_fail (SIM_IS_HOST (host), NULL);

  query = g_strdup_printf ("DELETE FROM host_qualification WHERE host_id = %s",
                           sim_uuid_get_db_string (host->_priv->id));

  return query;
}

/**
	@brief This function load from the database the compromisse and attack value.

	@param self	Pointer to the SimHost Object
	@param database Pointer to the databasee Object to make calls
	
	@return TRUE if we can load the qualification and FALSE in case of error

	Remak: If there isn't any row in the host_qualification table, we use the default
	values of 1 for attack and compromisse
*/

gboolean sim_host_load_qualification (SimHost *self, SimDatabase *database)
{
  gboolean result = FALSE;
  GdaDataModel *dm = NULL;
  guint rows;
  const GValue *value;
  gchar *query;

  g_return_val_if_fail (SIM_IS_HOST (self), FALSE);
  g_return_val_if_fail (SIM_IS_DATABASE (database), FALSE);

  query = g_strdup_printf ("SELECT compromise, attack FROM host_qualification WHERE host_id = %s",
                           sim_uuid_get_db_string (self->_priv->id));

  dm = sim_database_execute_single_command (database, query);
  g_free (query);
  rows = gda_data_model_get_n_rows(dm);
  switch (rows)
  {
  case 0: /* No values, load default in SimHost */
    self->_priv->c = self->_priv->a = 1;
    result = TRUE;
    break;
  case 1: /* Load Values */
    value = gda_data_model_get_value_at (dm, 0, 0, NULL); /* Compromise */
    self->_priv->c = g_value_get_int (value);
    value = gda_data_model_get_value_at (dm, 0, 1, NULL); /* attack */
    self->_priv->a = g_value_get_int (value);
    result = TRUE;
    break;
  default:
    g_warning ("DATA MODEL ERROR: More than one record for host qualification id = %s",
               sim_uuid_get_string (self->_priv->id));
    self->_priv->c = self->_priv->a = 1;
    result = FALSE;

  }

  if (dm)
    g_object_unref (dm);

  return result;
}

/**
 * sim_host_is_loaded_from_db:
 * @host: a #SimHost
 *
 * returns %TRUE if the host has been loaded from db,
 *         %FALSE otherwise
 */
gboolean
sim_host_is_loaded_from_db (SimHost *host)
{
  g_return_val_if_fail (SIM_IS_HOST (host), FALSE);

  return host->_priv->loaded_from_db;
}

// vim: set tabstop=2:
