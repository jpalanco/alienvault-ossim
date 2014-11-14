/* (c) AlienVault Inc. 2012-2013
 * All rights reserved
 *
 * This code is protected by copyright and distributed under licenses
 * restricting its use, copying, distribution, and decompilation. It may
 * only be reproduced or used under license from Alienvault Inc. or its
 * authorised licensees.
 */

#include "config.h"
#include "sim-user-auth.h"
#include "sim-database.h"
#include "os-sim.h"


extern SimMain ossim;

enum
{
  DESTROY,
  LAST_SIGNAL
};

struct _SimUserAuthPrivate
{ 
  SimUserAuthType type;
};

static gpointer parent_class = NULL;

#define SIM_USER_AUTH_GET_PRIVATE(object) \
  (G_TYPE_INSTANCE_GET_PRIVATE ((object), SIM_TYPE_USER_AUTH, SimUserAuthPrivate))



/* GType Functions */

static void
sim_user_auth_impl_dispose (GObject * gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void
sim_user_auth_impl_finalize (GObject * gobject)
{

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
  return;
}

static void
sim_user_auth_class_init (SimUserAuthClass * klass)
{
  GObjectClass * object_class = G_OBJECT_CLASS (klass);

  parent_class = g_type_class_peek_parent (klass);

  object_class->dispose = sim_user_auth_impl_dispose;
  object_class->finalize = sim_user_auth_impl_finalize;
  g_type_class_add_private (klass, sizeof (SimUserAuthPrivate));
  return;
}

static void
sim_user_auth_instance_init (SimUserAuth * userauth)
{
  userauth->_priv = SIM_USER_AUTH_GET_PRIVATE (userauth);
  return;
}

GType
sim_user_auth_get_type (void)
{
  static GType object_type = 0;

  if (!object_type)
  {
    static const GTypeInfo type_info = {
      sizeof (SimUserAuthClass),
      NULL,
      NULL,
      (GClassInitFunc) sim_user_auth_class_init,
      NULL,
      NULL,                       /* class data */
      sizeof (SimUserAuth),
      0,                          /* number of pre-allocs */
      (GInstanceInitFunc) sim_user_auth_instance_init,
      NULL                        /* value table */
    };

    g_type_init ();
    object_type = g_type_register_static (G_TYPE_OBJECT, "SimUserAuth", &type_info, 0);
  }
  return object_type;
}

/**
 * sim_user_auth_new:
 *
 */
SimUserAuth *
sim_user_auth_new (SimUserAuthType type)
{
  SimUserAuth * auth = SIM_USER_AUTH (g_object_new (SIM_TYPE_USER_AUTH, NULL));
  switch (type)
  {
    case SimUserAuthDatabase:
      auth->_priv->type = SimUserAuthDatabase;
      break;
    default:
      g_object_unref (auth);
      auth = NULL;
      g_message ("%s: Unknown auth type", __FUNCTION__);
      
  }
  return (auth);
}
static gboolean
sim_user_auth_check_login_database             (SimUserAuth *auth,const gchar *login, const gchar *password)
{
  gboolean result = FALSE;
  g_return_val_if_fail (auth != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_USER_AUTH (auth), FALSE);
  g_return_val_if_fail (login != NULL, FALSE);
  g_return_val_if_fail (password != NULL, FALSE);
  gchar *escaped_login = NULL;
  gchar *escaped_password = NULL;
  GdaConnection *conn;
  gchar *query = NULL;
  GdaDataModel *dm = NULL; 
  conn = sim_database_get_conn (ossim.dbossim);
  do
  {
    if ((escaped_login = sim_str_escape (login, conn, 0)) == NULL)
    {
      g_message ("%s: Internal error", __FUNCTION__);
      break;
    }
    if ((escaped_password = sim_str_escape (password, conn, 0)) == NULL)
    {
      g_message ("%s: Internal_error", __FUNCTION__);
      break;
    }
    query = g_strdup_printf ("SELECT login FROM users where LOGIN  = '%s' AND LCASE (MD5('%s')) = pass and is_admin = 1", escaped_login, escaped_password);
    if ((dm = sim_database_execute_single_command (ossim.dbossim, query)) == NULL)
    {
      g_message ("%s: Database error", __FUNCTION__);
      break;
    }
    /* Ok, if the dm has one row and is equal to login */
    if (gda_data_model_get_n_rows (dm) != 1)
    {
      g_message ("%s: Login error", __FUNCTION__);
      break;
    }
    result = TRUE;
  }while (0);
  
  g_free (escaped_login);
  g_free (escaped_password);
  g_free (query);
  if (dm != NULL)
    g_object_unref (dm);
  
  return result;
}

/**
 * sim_user_auth_check_login
 *
*/
gboolean
sim_user_auth_check_login             (SimUserAuth *auth,const gchar *login, const gchar *password)
{
  gboolean result = FALSE;
  g_return_val_if_fail (auth != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_USER_AUTH (auth), FALSE);
  g_return_val_if_fail (login != NULL, FALSE);
  g_return_val_if_fail (password != NULL, FALSE);
  switch (auth->_priv->type)
  {
    case  SimUserAuthDatabase:
      result = sim_user_auth_check_login_database (auth, login, password);
      break;
    default:
       g_message ("%s: Unknown auth type", __FUNCTION__);

      
  }
  return result;
}


