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

#include "sim-mini-object.h"

#include <glib.h>

void
sim_mini_object_init (SimMiniObject *mini_object, GType type)
{
  mini_object->type = type;
  mini_object->refcount = 1;
  mini_object->ref_callback = 0;
}

SimMiniObject *
sim_mini_object_copy (const SimMiniObject * mini_object)
{
  SimMiniObject *copy;

  g_return_val_if_fail (mini_object != NULL, NULL);

  if (mini_object->copy)
    copy = mini_object->copy (mini_object);
  else
    copy = NULL;

  return copy;
}

SimMiniObject *
sim_mini_object_ref (SimMiniObject *mini_object)
{
  g_return_val_if_fail (mini_object != NULL, NULL);

  g_atomic_int_inc (&mini_object->refcount);

  return mini_object;
}

/**
 * sim_mini_object_add_callback:
 * @mini_object: a #SimMiniObject object.
 * @callback: a function that will be called when the last ref' of @mini_object is reached.
 *
 */
void
sim_mini_object_add_callback (SimMiniObject * mini_object,
                              SimMiniObjectCallbackFunction callback)
{
  g_atomic_pointer_set (&mini_object->callback, callback);
  g_atomic_int_set (&mini_object->ref_callback, 1);
  return;
}

void
sim_mini_object_unref (SimMiniObject *mini_object)
{
  g_return_if_fail (mini_object != NULL);
  g_return_if_fail (mini_object->refcount > 0);

  if (g_atomic_int_dec_and_test (&mini_object->refcount)) {

    // Check for last reference and execute the callback function.
    if (g_atomic_int_compare_and_exchange (&mini_object->ref_callback, 1, 0))
    {
      g_atomic_int_inc (&mini_object->refcount);
      mini_object->callback (mini_object, NULL);
    }
    else
    {
      if (mini_object->dispose)
        mini_object->dispose (mini_object);

      if (mini_object->free)
        mini_object->free (mini_object);
    }
  }
}

