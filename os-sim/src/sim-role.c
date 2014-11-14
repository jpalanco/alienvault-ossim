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

#include "sim-role.h"

#include "os-sim.h"
#include "sim-debug.h"

struct _SimRolePrivate
{
  gboolean correlate;
  gboolean cross_correlate;
  gboolean reputation;
  gboolean store;
  gboolean qualify;
  gboolean alarms_to_syslog;
  gboolean sim;
  gboolean sem;
  gboolean sign;
  gboolean forward_event;
  gboolean forward_alarm;

  gint     logger_if_priority;

  GHashTable *rservers; // Remote servers where this event can be forwarded to.
};


static void        sim_role_init             (SimRole  *role);
static SimRole *  _sim_role_copy             (SimRole  *event);
static void       _sim_role_free             (SimRole  *event);

/* GType Functions */

GType _sim_role_type = 0;

SIM_DEFINE_MINI_OBJECT_TYPE (SimRole, sim_role);

void
_priv_sim_role_initialize (void)
{
  _sim_role_type = sim_role_get_type ();
}

static void
sim_role_init (SimRole *role)
{
  sim_mini_object_init (SIM_MINI_OBJECT_CAST (role), _sim_role_type);

  role->mini_object.copy = (SimMiniObjectCopyFunction) _sim_role_copy;
  role->mini_object.dispose = NULL;
  role->mini_object.free = (SimMiniObjectFreeFunction) _sim_role_free;

  role->priv = g_slice_new0 (SimRolePrivate);
}

SimRole *
sim_role_new (void)
{
  SimRole *role;

  role = g_slice_new0 (SimRole);

  sim_role_init (role);

  return role;
}

SimRole *
sim_role_new_full (gboolean correlate,
                   gboolean cross_correlate,
                   gboolean reputation,
                   gboolean store,
                   gboolean qualify,
                   gboolean alarms_to_syslog,
                   gboolean sim,
                   gboolean sem,
                   gboolean sign,
                   gboolean forward_event,
                   gboolean forward_alarm)
{
  SimRole *role;

  role = sim_role_new ();

  role->priv->correlate = correlate;
  role->priv->cross_correlate = cross_correlate;
  role->priv->reputation = reputation;
  role->priv->store = store;
  role->priv->qualify = qualify;
  role->priv->alarms_to_syslog = alarms_to_syslog;
  role->priv->sim = sim;
  role->priv->sem = sem;
  role->priv->sign = sign;
  role->priv->forward_event = forward_event;
  role->priv->forward_alarm = forward_alarm;

  return role;
}

SimRole *
sim_role_new_from_dm (GdaDataModel *dm,
                      gint          row)
{
  SimRole *role;
  const GValue *value;

  g_return_val_if_fail (GDA_IS_DATA_MODEL (dm), NULL);

  role = sim_role_new ();

  value = gda_data_model_get_value_at (dm, 0, row, NULL);
  role->priv->correlate = (gboolean) g_value_get_int (value);

  value = gda_data_model_get_value_at (dm, 1, row, NULL);
  role->priv->cross_correlate = (gboolean) g_value_get_int (value);

  value = gda_data_model_get_value_at (dm, 2, row, NULL);
  role->priv->store = (gboolean) g_value_get_int (value);

  value = gda_data_model_get_value_at (dm, 3, row, NULL);
  role->priv->qualify = (gboolean) g_value_get_int (value);

  value = gda_data_model_get_value_at (dm, 4, row, NULL);
  role->priv->forward_alarm = (gboolean) g_value_get_int (value);

  value = gda_data_model_get_value_at (dm, 5, row, NULL);
  role->priv->forward_event = (gboolean) g_value_get_int (value);

  value = gda_data_model_get_value_at (dm, 6, row, NULL);
  role->priv->sign = (gboolean) g_value_get_int (value);

  value = gda_data_model_get_value_at (dm, 7, row, NULL);
  role->priv->sim = (gboolean) g_value_get_int (value);

  value = gda_data_model_get_value_at (dm, 8, row, NULL);
  role->priv->sem = (gboolean) g_value_get_int (value);

  value = gda_data_model_get_value_at (dm, 9, row, NULL);
  role->priv->alarms_to_syslog = (gboolean) g_value_get_int (value);

  value = gda_data_model_get_value_at (dm, 10, row, NULL);
  role->priv->reputation = (gboolean) g_value_get_int (value);

  return role;
}

SimRole *
sim_role_ref (SimRole *role)
{
  return (SimRole *)sim_mini_object_ref (SIM_MINI_OBJECT_CAST (role));
}

void
sim_role_unref (SimRole *role)
{
  SimMiniObject * mini_object = SIM_MINI_OBJECT_CAST (role);

  sim_mini_object_unref (mini_object);
}

static void
_sim_role_free (SimRole *role)
{
  if (role->priv->rservers)
    g_hash_table_unref (role->priv->rservers);

  g_slice_free (SimRolePrivate, role->priv);
}

SimRole *
sim_role_copy (SimRole *role)
{
  return (SimRole *)sim_mini_object_copy (SIM_MINI_OBJECT_CAST (role));
}

static SimRole *
_sim_role_copy (SimRole *role)
{
  SimRole *new_role;

  new_role = sim_role_new ();

  new_role->priv->correlate = role->priv->correlate;
  new_role->priv->cross_correlate = role->priv->cross_correlate;
  new_role->priv->reputation = role->priv->reputation;
  new_role->priv->store = role->priv->store;
  new_role->priv->qualify = role->priv->qualify;
  new_role->priv->sim = role->priv->sim;
  new_role->priv->sem = role->priv->sem;
  new_role->priv->sign = role->priv->sign;
  new_role->priv->forward_event = role->priv->forward_event;
  new_role->priv->forward_alarm = role->priv->forward_alarm;

  new_role->priv->rservers = role->priv->rservers;

  return new_role;
}

gboolean
sim_role_correlate (SimRole *role)
{
  g_return_val_if_fail (SIM_IS_ROLE (role), FALSE);

  return role->priv->correlate;
}

void
sim_role_set_correlate (SimRole  *role,
                        gboolean  new_value)
{
  g_return_if_fail (SIM_IS_ROLE (role));

  role->priv->correlate = new_value;
}

gboolean
sim_role_cross_correlate (SimRole *role)
{
  g_return_val_if_fail (SIM_IS_ROLE (role), FALSE);

  return role->priv->cross_correlate;
}

void
sim_role_set_cross_correlate (SimRole  *role,
                              gboolean  new_value)
{
  g_return_if_fail (SIM_IS_ROLE (role));

  role->priv->cross_correlate = new_value;
}

gboolean
sim_role_reputation (SimRole *role)
{
  g_return_val_if_fail (SIM_IS_ROLE (role), FALSE);

  return role->priv->reputation;
}

void
sim_role_set_reputation (SimRole  *role,
                         gboolean  new_value)
{
  g_return_if_fail (SIM_IS_ROLE (role));

  role->priv->reputation = new_value;
}

gboolean
sim_role_store (SimRole *role)
{
  g_return_val_if_fail (SIM_IS_ROLE (role), FALSE);

  return role->priv->store;
}

void
sim_role_set_store (SimRole  *role,
                    gboolean  new_value)
{
  g_return_if_fail (SIM_IS_ROLE (role));

  role->priv->store = new_value;
}

gboolean
sim_role_qualify (SimRole *role)
{
  g_return_val_if_fail (SIM_IS_ROLE (role), FALSE);

  return role->priv->qualify;
}

void
sim_role_set_qualify (SimRole  *role,
                      gboolean  new_value)
{
  g_return_if_fail (SIM_IS_ROLE (role));

  role->priv->qualify = new_value;
}

gboolean
sim_role_alarms_to_syslog (SimRole *role)
{
  g_return_val_if_fail (SIM_IS_ROLE (role), FALSE);

  return role->priv->alarms_to_syslog;
}

void
sim_role_set_alarms_to_syslog (SimRole  *role,
                               gboolean  new_value)
{
  g_return_if_fail (SIM_IS_ROLE (role));

  role->priv->alarms_to_syslog = new_value;
}

gboolean
sim_role_sim (SimRole *role)
{
  g_return_val_if_fail (SIM_IS_ROLE (role), FALSE);

  return role->priv->sim;
}
void
sim_role_set_sim (SimRole  *role,
                  gboolean  new_value)
{
  g_return_if_fail (SIM_IS_ROLE (role));

  role->priv->sim = new_value;
}

gboolean
sim_role_sem (SimRole *role)
{
  g_return_val_if_fail (SIM_IS_ROLE (role), FALSE);

  return role->priv->sem;
}

void
sim_role_set_sem (SimRole  *role,
                  gboolean  new_value)
{
  g_return_if_fail (SIM_IS_ROLE (role));

  role->priv->sem = new_value;
}

gboolean
sim_role_sign (SimRole *role)
{
  g_return_val_if_fail (SIM_IS_ROLE (role), FALSE);

  return role->priv->sign;
}

void
sim_role_set_sign (SimRole  *role,
                   gboolean  new_value)
{
  g_return_if_fail (SIM_IS_ROLE (role));

  role->priv->sign = new_value;
}

gboolean
sim_role_forward_event (SimRole *role)
{
  g_return_val_if_fail (SIM_IS_ROLE (role), FALSE);

  return role->priv->forward_event;
}

void
sim_role_set_forward_event (SimRole  *role,
                            gboolean  new_value)
{
  g_return_if_fail (SIM_IS_ROLE (role));

  role->priv->forward_event = new_value;
}

gboolean
sim_role_forward_alarm (SimRole *role)
{
  g_return_val_if_fail (SIM_IS_ROLE (role), FALSE);

  return role->priv->forward_alarm;
}

void
sim_role_set_forward_alarm (SimRole  *role,
                            gboolean  new_value)
{
  g_return_if_fail (SIM_IS_ROLE (role));

  role->priv->forward_alarm = new_value;
}

gint
sim_role_logger_if_priority (SimRole *role)
{
  g_return_val_if_fail (SIM_IS_ROLE (role), 0);

  return role->priv->logger_if_priority;
}

void
sim_role_set_logger_if_priority (SimRole *role,
                                 gint     new_value)
{
  g_return_if_fail (SIM_IS_ROLE (role));

  role->priv->logger_if_priority = new_value;
}


GHashTable *
sim_role_get_rservers (SimRole *role)
{
  g_return_val_if_fail (SIM_IS_ROLE (role), NULL);

  return g_atomic_pointer_get (&role->priv->rservers);
}

void
sim_role_set_rservers (SimRole    *role,
                       GHashTable *rservers)
{
  GHashTable *old_rservers;

  g_return_if_fail (SIM_IS_ROLE (role));

  old_rservers = role->priv->rservers;

  g_atomic_pointer_set (&role->priv->rservers, rservers);

  if (old_rservers)
    g_hash_table_unref (old_rservers);
}

void
sim_role_print (SimRole *role)
{
  gchar *role_str;

  g_return_if_fail (SIM_IS_ROLE (role));

  role_str = sim_role_get_string (role);
  ossim_debug ("%s", role_str);

  g_free (role_str);
}

gchar *
sim_role_get_string (SimRole *role)
{
  GString *aux;

  g_return_val_if_fail (SIM_IS_ROLE (role), NULL);

  aux = g_string_new ("SimRole:\n");
  g_string_append_printf (aux, "  correlate:          %d\n", role->priv->correlate);
  g_string_append_printf (aux, "  cross correlate:    %d\n", role->priv->cross_correlate);
  g_string_append_printf (aux, "  reputation:         %d\n", role->priv->reputation);
  g_string_append_printf (aux, "  store:              %d\n", role->priv->store);
  g_string_append_printf (aux, "  qualify:            %d\n", role->priv->qualify);
  g_string_append_printf (aux, "  forward_event:      %d\n", role->priv->forward_event);
  g_string_append_printf (aux, "  forward_alarm:      %d\n", role->priv->forward_alarm);
  g_string_append_printf (aux, "  sign:               %d\n", role->priv->sign);
  g_string_append_printf (aux, "  sem:                %d\n", role->priv->sem);
  g_string_append_printf (aux, "  sim:                %d\n", role->priv->sim);
  g_string_append_printf (aux, "  alarms_to_syslog:   %d\n", role->priv->alarms_to_syslog);
  g_string_append_printf (aux, "  logger if priority: %d\n", role->priv->logger_if_priority);

  return g_string_free (aux, FALSE);
}


#ifdef USE_UNITTESTS

/*************************************************************
 *******************      Unit tests      ********************
 *************************************************************/

gboolean sim_role_test1 (void);

gboolean
sim_role_test1 (void)
{
  gboolean success = TRUE;

  SimRole *role;

  role = sim_role_new ();
  sim_role_unref (role);


  role = sim_role_new_full (TRUE, TRUE, TRUE, TRUE,
                            TRUE, TRUE, TRUE, TRUE,
                            TRUE, TRUE, TRUE);

  if (!sim_role_correlate (role) ||
      !sim_role_cross_correlate (role) ||
      !sim_role_reputation (role) ||
      !sim_role_store (role) ||
      !sim_role_qualify (role) ||
      !sim_role_alarms_to_syslog (role) ||
      !sim_role_sim (role) ||
      !sim_role_sem (role) ||
      !sim_role_forward_event (role) ||
      !sim_role_forward_alarm (role))
  {
    g_print ("Error New Full (TRUE)\n");
    success = FALSE;
  }

  role = sim_role_new_full (FALSE, FALSE, FALSE, FALSE,
                            FALSE, FALSE, FALSE, FALSE,
                            FALSE, FALSE, FALSE);

  if (sim_role_correlate (role) ||
      sim_role_cross_correlate (role) ||
      sim_role_reputation (role) ||
      sim_role_store (role) ||
      sim_role_qualify (role) ||
      sim_role_alarms_to_syslog (role) ||
      sim_role_sim (role) ||
      sim_role_sem (role) ||
      sim_role_forward_event (role) ||
      sim_role_forward_alarm (role))
  {
    g_print ("Error New Full (FALSE)\n");
    success = FALSE;
  }

  sim_role_ref (role);

  sim_role_unref (role);
  sim_role_unref (role);

  return success;
}

void
sim_role_register_tests (SimUnittesting *engine)
{
  sim_unittesting_append (engine, "sim_role_test1 - New", sim_role_test1, TRUE);
}
#endif /* USE_UNITTESTS */

// vim: set tabstop=2:

