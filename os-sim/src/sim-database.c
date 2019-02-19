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

#include "sim-db-insert.h"
#include "sim-database.h"

#include <libgda/libgda.h>
#include <libgda/sql-parser/gda-sql-parser.h>
#include <unistd.h>

#include "os-sim.h"

#define PROVIDER_MYSQL   "MySQL"
#define PROVIDER_PGSQL   "PostgreSQL"
#define PROVIDER_ORACLE  "Oracle"
#define PROVIDER_ODBC    "odbc"

extern SimMain    ossim;

enum
{
  DESTROY,
  LAST_SIGNAL
};


struct _SimDatabasePrivate {
  GRecMutex mutex;
  GdaConnection   *conn;        /* Connection */

  gchar           *name;        /* DS Name */
  gchar           *provider  ;  /* Data Source */
  gchar           *dsn;         /* User Name */

  gboolean        autocommit;   // some queries will need to set on/off autocommit
};

static gpointer parent_class = NULL;

/* We don't use signals */
//static gint sim_database_signals[LAST_SIGNAL] = { 0 };

/* GType Functions */

static void
sim_database_impl_dispose (GObject  *gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void
sim_database_impl_finalize (GObject  *gobject)
{
  SimDatabase *database = SIM_DATABASE (gobject);

  if (database->_priv->name)
    g_free (database->_priv->name);
  if (database->_priv->provider)
    g_free (database->_priv->provider);
  if (database->_priv->dsn)
    g_free (database->_priv->dsn);

  gda_connection_close (database->_priv->conn);

  g_rec_mutex_clear (&database->_priv->mutex);

  g_free (database->_priv);

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_database_class_init (SimDatabaseClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_peek_parent (class);

  object_class->dispose = sim_database_impl_dispose;
  object_class->finalize = sim_database_impl_finalize;
}

static void
sim_database_instance_init (SimDatabase *database)
{
  database->_priv = g_new0 (SimDatabasePrivate, 1);

  database->type = SIM_DATABASE_TYPE_NONE;

  database->_priv->autocommit = TRUE; //by default, we want to write everything

  g_rec_mutex_init (&database->_priv->mutex);
}

/* Public Methods */

GType
sim_database_get_type (void)
{
  static GType object_type = 0;

  if (!object_type)
  {
    static const GTypeInfo type_info = {
      sizeof (SimDatabaseClass),
      NULL,
      NULL,
      (GClassInitFunc) sim_database_class_init,
      NULL,
      NULL,                       /* class data */
      sizeof (SimDatabase),
      0,                          /* number of pre-allocs */
      (GInstanceInitFunc) sim_database_instance_init,
      NULL                        /* value table */
    };


    object_type = g_type_register_static (G_TYPE_OBJECT, "SimDatabase", &type_info, 0);
  }

  return object_type;
}

/*
 *
 *
 */
SimDatabase*
sim_database_new (SimConfigDS  *config)
{
  SimDatabase *db = NULL;
  GError *error = NULL;

#ifdef USE_UNITTESTS
  /* Unittests don't need database conection */
  db = SIM_DATABASE (g_object_new (SIM_TYPE_DATABASE, NULL));
  return db;
#endif

  g_return_val_if_fail (config, NULL);
  g_return_val_if_fail (config->name, NULL);
  g_return_val_if_fail (config->provider, NULL);
  g_return_val_if_fail (config->dsn, NULL);

  db = SIM_DATABASE (g_object_new (SIM_TYPE_DATABASE, NULL));

  db->_priv->name = g_strdup (config->name);
  db->_priv->provider = g_strdup (config->provider);
  db->_priv->dsn = g_strdup (config->dsn);

  if (!g_ascii_strcasecmp (db->_priv->provider, PROVIDER_MYSQL))
    db->type = SIM_DATABASE_TYPE_MYSQL;
  else if (!g_ascii_strcasecmp (db->_priv->provider, PROVIDER_PGSQL))
    db->type = SIM_DATABASE_TYPE_PGSQL;
  else if (!g_ascii_strcasecmp (db->_priv->provider, PROVIDER_ORACLE))
    db->type = SIM_DATABASE_TYPE_ORACLE;
  else if (!g_ascii_strcasecmp (db->_priv->provider, PROVIDER_ODBC))
    db->type = SIM_DATABASE_TYPE_ODBC;
  else
    db->type = SIM_DATABASE_TYPE_NONE;

  db->_priv->conn = gda_connection_open_from_string (db->_priv->provider,
                                                     db->_priv->dsn,
                                                     NULL,
                                                     GDA_CONNECTION_OPTIONS_THREAD_SAFE, // GDA_CONNECTION_OPTIONS_NONE
                                                     &error);
  if (error)
  {
    g_message (" CONNECTION ERROR\n");
    g_message (" NAME: %s\n", db->_priv->name);
    g_message (" PROVIDER: %s\n", db->_priv->provider);
    g_message (" DSN: Check the in config.xml\n");
    g_message (" We can't open the database connection. Please check that your DB is up.\n");
    g_message (" %s", error->message);
    g_error_free (error);
    error = NULL;
    exit (EXIT_FAILURE);
  }

  if (!gda_connection_is_opened (db->_priv->conn))
  {
    g_message (" CONNECTION ERROR\n");
    g_message (" NAME: %s\n", db->_priv->name);
    g_message (" PROVIDER: %s\n", db->_priv->provider);
    g_message (" DSN: Check the in config.xml\n");
    g_message ("We can't open the database connection. Please check that your DB is up.\n");
    exit (EXIT_FAILURE);
  }

  // Set query cache off to avoid global lock waiting.
  gda_connection_execute_non_select_command (db->_priv->conn, "SET SESSION query_cache_type = OFF", NULL);

  return db;
}

/*
 * Executes a query in the database specified and returns the number of affected rows (-1
 * on error)
 *
 */
gint
sim_database_execute_no_query  (SimDatabase  *database,
                                const gchar  *buffer)
{
  GdaConnection   *conn;
  GError          *error = NULL;
  gint            ret;
  gboolean        recoverable_error = TRUE;

  g_return_val_if_fail (SIM_IS_DATABASE (database), -1);
  g_return_val_if_fail (buffer != NULL, -1);
#ifdef USE_UNITTESTS
  return 0;
#endif

  g_rec_mutex_lock (&database->_priv->mutex);

  // GDA non select is used for normal storage or before massive insertion is initialized
  if (ossim.container && (sim_container_get_storage_type(ossim.container) == 3))
  {
    sim_db_insert_generic (database, buffer);
    ret = 1;
  }
  else
  {
    while (!GDA_IS_CONNECTION (database->_priv->conn) || !gda_connection_is_opened (database->_priv->conn)) //if database connection is not open, try to open it.
    {
      g_message ("Warning (1): DB Connection is closed. Trying to open it again....");
      conn = database->_priv->conn;


      database->_priv->conn = gda_connection_open_from_string (database->_priv->provider,
                                                               database->_priv->dsn,
                                                               NULL,
                                                               GDA_CONNECTION_OPTIONS_THREAD_SAFE, // GDA_CONNECTION_OPTIONS_NONE
                                                               &error);

      if (G_IS_OBJECT (conn))
        g_object_unref (G_OBJECT (conn));

      if (error)
      {
        g_message (" CONNECTION ERROR");
        g_message (" NAME: %s", database->_priv->name);
        g_message (" PROVIDER: %s", database->_priv->provider);
        g_message (" DSN: Check the in config.xml\n");
        g_message (" We can't open the database connection. Please check that your DB is up.");
        g_message (" Waiting 10 seconds until next try....");
        g_message (" %s", error->message);
        g_error_free (error);
        error = NULL;
        sleep(10);  //we'll wait to check if database is availabe again...
      }
      else
      {
        g_message ("DB Connection restored");

        if (!database->_priv->autocommit)
          gda_connection_execute_non_select_command (database->_priv->conn, "SET AUTOCOMMIT = 0", NULL);

        // Set query cache off to avoid global lock waiting.
        gda_connection_execute_non_select_command (database->_priv->conn, "SET SESSION query_cache_type = OFF", NULL);
      }
    }

    error = NULL;
    ret = gda_connection_execute_non_select_command (database->_priv->conn, buffer, &error);
    if (error)
    {
      if (error->domain == GDA_SERVER_PROVIDER_ERROR)
      {
        switch (error->code)
        {
        case GDA_SERVER_PROVIDER_STATEMENT_EXEC_ERROR:
          // Malformed query check.
          if (g_strcmp0 ("MySQL server has gone away", error->message)
              && g_strcmp0 ("Lost connection to MySQL server during query", error->message))
          {
            recoverable_error = FALSE;
            g_message ("Query: %s error: %s", buffer, error->message);
          }
          else
          {
            ossim_debug ("Query: %s message: %s", buffer, error->message);
          }
          break;
        default:
          g_message ("Query: %s error: %s", buffer, error->message);
          break;
        }
      }
      else if(error->domain == GDA_SQL_PARSER_ERROR)
        recoverable_error = FALSE;

      g_error_free (error);

      if (recoverable_error)
      {
        gda_connection_close (database->_priv->conn);
        ret = sim_database_execute_no_query (database, buffer);
      }
    }


  }

  g_rec_mutex_unlock (&database->_priv->mutex);
  return ret;
}

/*
 *
 *
 *
 */
GdaDataModel*
sim_database_execute_single_command (SimDatabase  *database,
                                     const gchar  *buffer)
{
  GdaConnection   *conn;
  GError          *error = NULL;
  GdaDataModel   *model = NULL;
  gboolean        recoverable_error = TRUE;

  g_return_val_if_fail (SIM_IS_DATABASE (database), NULL);
  g_return_val_if_fail (buffer != NULL, NULL);

#ifdef USE_UNITTESTS
  return NULL;
#endif

  g_rec_mutex_lock (&database->_priv->mutex);

  while (!GDA_IS_CONNECTION (database->_priv->conn) || !gda_connection_is_opened (database->_priv->conn))
  {
    g_message ("Warning (2): DB Connection is closed. Trying to open it again....");
    conn = database->_priv->conn;

    database->_priv->conn = gda_connection_open_from_string (database->_priv->provider,
                                                             database->_priv->dsn,
                                                             NULL,
                                                             GDA_CONNECTION_OPTIONS_THREAD_SAFE, // GDA_CONNECTION_OPTIONS_NONE
                                                             &error);

    if (G_IS_OBJECT (conn))
      g_object_unref (G_OBJECT (conn));

		if (error)
    {
      g_message (" CONNECTION ERROR");
      g_message (" NAME: %s", database->_priv->name);
      g_message (" PROVIDER: %s", database->_priv->provider);
      g_message (" DSN: Check the in config.xml\n");
      g_message (" We can't open the database connection. Please check that your DB is up.");
      g_message (" Waiting 10 seconds until next try....");
      g_message (" %s", error->message);
      g_error_free (error);
      error = NULL;
			sleep(10);	//we'll wait to check if database is availabe again...
    }
    else
    {
      g_message ("DB Connection restored");

      if (!database->_priv->autocommit)
        gda_connection_execute_non_select_command (database->_priv->conn, "SET AUTOCOMMIT = 0", NULL);

      // Set query cache off to avoid global lock waiting.
      gda_connection_execute_non_select_command (database->_priv->conn, "SET SESSION query_cache_type = OFF", NULL);
    }
  }

  error = NULL;
  model = gda_connection_execute_select_command (database->_priv->conn, buffer, &error);
  if (error)
  {
    if (error->domain == GDA_SERVER_PROVIDER_ERROR)
    {
      switch (error->code)
      {
        case GDA_SERVER_PROVIDER_STATEMENT_EXEC_ERROR:
          // Malformed query check.
          if (g_strcmp0 ("MySQL server has gone away", error->message)
              && g_strcmp0 ("Lost connection to MySQL server during query", error->message))
          {
            recoverable_error = FALSE;
            g_message ("%s: query: %s error: %s", __func__, buffer, error->message);
          }
          else
          {
            ossim_debug ("%s: query: %s message: %s", __func__, buffer, error->message);
          }
          break;
        default:
          g_message ("%s: query: %s error: %s", __func__, buffer, error->message);
          break;
      }
    }
    else if(error->domain == GDA_SQL_PARSER_ERROR)
      recoverable_error = FALSE;

    g_error_free (error);

    if (recoverable_error)
    {
      gda_connection_close (database->_priv->conn);
      model = sim_database_execute_single_command (database, buffer);
    }
  }

  g_rec_mutex_unlock (&database->_priv->mutex);

  return model;
}

/*
 *
 *
 *
 */
GdaConnection*
sim_database_get_conn (SimDatabase  *database)
{
  g_return_val_if_fail (SIM_IS_DATABASE (database), NULL);

  return database->_priv->conn;
}

/*
 *  Returns the DS name of the database (defined in server's config.xml)
 */
gchar*
sim_database_get_name (SimDatabase  *database)
{
  g_return_val_if_fail (SIM_IS_DATABASE (database), NULL);

  return database->_priv->name;
}


/*
 * This returns from the database and table specified, a sequence number.
 * The number is "reserved".
 * The table specified should be something like blablabla_seq or lalalala_seq, you know ;)
 * Beware! if you write the name of a non-existant table this function will fail and will return 0.
 */
G_LOCK_DEFINE_STATIC (lock_get_id);
guint
sim_database_get_id (SimDatabase  *database, gchar *table_name)
{

  GdaDataModel  *dm;
  const GValue  *value;
  gchar         *query;
  guint         id = 0;
  guint         event_seq=0;
  gboolean      flag_event=FALSE;
  gboolean      flag_backlog=FALSE;

  g_return_val_if_fail (SIM_IS_DATABASE (database), 0);
  g_return_val_if_fail (table_name != NULL, 0);

  G_LOCK (lock_get_id);

  if (!strcmp (table_name, EVENT_SEQ_TABLE))
  {
    event_seq = sim_container_get_event_id (ossim.container);
    if (event_seq == 0)
    {
      flag_event = TRUE;
      //query = g_strdup_printf ("UPDATE event_seq SET id=(select MAX(id) from event)+1;", table_name);
      query = g_strdup_printf ("UPDATE event_seq SET id=(select MAX(id) from event)+1");

    }
    else
    {
      G_UNLOCK (lock_get_id);
      return sim_container_next_event_id (ossim.container);
    }
  }
  else if (!strcmp (table_name, BACKLOG_SEQ_TABLE))
  {
    event_seq = sim_container_get_backlog_id(ossim.container);
    if (event_seq == 0)
    {
      flag_backlog = TRUE;
      query = g_strdup_printf ("UPDATE backlog_seq SET id=(select MAX(id) from backlog)+1");
    }
    else
    {
      G_UNLOCK (lock_get_id);
      return sim_container_next_backlog_id(ossim.container);
    }
  }
  else
  {
    query = g_strdup_printf ("UPDATE %s SET id=LAST_INSERT_ID(id+1)", table_name);
  }

  sim_database_execute_no_query (database, query);
  g_free (query);

  if(!strcmp (table_name, EVENT_SEQ_TABLE) || !strcmp (table_name, BACKLOG_SEQ_TABLE))
    query = g_strdup_printf ("SELECT MAX(id) FROM %s", table_name);
  else
    query = g_strdup_printf ("SELECT LAST_INSERT_ID(id) FROM %s", table_name);

  dm = sim_database_execute_single_command (database, query);
  g_free (query);
  if (dm)
  {
    value = gda_data_model_get_value_at (dm, 0, 0, NULL);
    if (gda_data_model_get_n_rows (dm) !=0)
    {
      if (!gda_value_is_null (value))
        id = g_value_get_long (value);
    }
    else
    {
      id=1;
    }

    g_object_unref (dm);
  }
  else
  {
    g_message ("sim_database_get_id: %s table DATA MODEL ERROR", table_name);
  }

  if (!id)
    id = 1;

  ossim_debug ( "sim_database_get_id: id obtained: %u", id);

  if (flag_event)
    sim_container_set_event_id (ossim.container, id);

  if (flag_backlog)
    sim_container_set_backlog_id (ossim.container, id);

  G_UNLOCK (lock_get_id);

  return id;
}

void
sim_database_set_autocommit (SimDatabase *database, gboolean autocommit)
{
  g_return_if_fail (SIM_IS_DATABASE (database));

  database->_priv->autocommit = autocommit;

  if (autocommit)
    gda_connection_execute_non_select_command (database->_priv->conn, "SET AUTOCOMMIT = 1", NULL);
  else
    gda_connection_execute_non_select_command (database->_priv->conn, "SET AUTOCOMMIT = 0", NULL);
}

gboolean
sim_database_get_autocommit (SimDatabase *database)
{
  g_return_val_if_fail (SIM_IS_DATABASE (database), FALSE);

  return database->_priv->autocommit;
}
gboolean
sim_database_begin_transaction (SimDatabase *database, gchar *name)
{
  return gda_connection_begin_transaction (database->_priv->conn, name, GDA_TRANSACTION_ISOLATION_UNKNOWN, NULL);
}

gboolean
sim_database_commit (SimDatabase *database, gchar *name)
{
  return gda_connection_commit_transaction (database->_priv->conn, name, NULL);
}

/*
 *      Returns the DS dns of the database (defined in server's config.xml)
 */
gchar*
sim_database_get_dsn (SimDatabase  *database)
{
  g_return_val_if_fail (SIM_IS_DATABASE (database), NULL);

  return database->_priv->dsn;
}

gchar *
sim_database_str_escape (SimDatabase *database, const gchar *source, gsize can_have_nulls)
{
  g_return_val_if_fail (SIM_IS_DATABASE (database), NULL);

  return sim_str_escape (source, database->_priv->conn, can_have_nulls);
}

// vim: set tabstop=2:


