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

#include "sim-unittesting.h"

#include <glib.h>
#include <glib-object.h>
#include <stdio.h>
#include <stdlib.h>

#include "sim-log.h"

#ifdef USE_UNITTESTS

/*
 * SimUnittesting contains a tree and a ptr to the unittesting information object 
 */
struct _SimUnittestingPrivate {
  GList *unittests;
  guint ok;
  guint fail;
  gchar *regex;
  //TODO: Add the ability to execute only unittests whose name match a passed regex
};

static gpointer parent_class = NULL;

gboolean sim_unittesting_execute_test (SimUnittest *test);

/* GType Functions */

static void 
sim_unittesting_impl_dispose (GObject  *gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void
sim_unittesting_impl_finalize (GObject  *gobject)
{
  SimUnittesting *unittesting = SIM_UNITTESTING (gobject);

  if (unittesting->_priv->unittests != NULL) {
    guint items = g_list_length(unittesting->_priv->unittests);
    guint i = 0;

    for (; i < items; i++) {
      SimUnittest *test = g_list_nth_data(unittesting->_priv->unittests, i);
        if (test != NULL) 
          free(test);
    }
    g_list_free(unittesting->_priv->unittests);
  }

  g_free (unittesting->_priv);

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

static void
sim_unittesting_class_init (SimUnittestingClass * class)
{
  GObjectClass *object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_peek_parent (class);

  object_class->dispose = sim_unittesting_impl_dispose;
  object_class->finalize = sim_unittesting_impl_finalize;
}

static void
sim_unittesting_instance_init (SimUnittesting *unittesting)
{
  // We don't need to  set ptr's to NULL, since g_new0 will make that for us
  unittesting->_priv = g_new0 (SimUnittestingPrivate, 1);
}

/* Public Methods */

GType
sim_unittesting_get_type (void)
{
  static GType object_type = 0;
 
  if (!object_type)
  {
    static const GTypeInfo type_info = {
              sizeof (SimUnittestingClass),
              NULL,
              NULL,
              (GClassInitFunc) sim_unittesting_class_init,
              NULL,
              NULL,                       /* class data */
              sizeof (SimUnittesting),
              0,                          /* number of pre-allocs */
              (GInstanceInitFunc) sim_unittesting_instance_init,
              NULL                        /* value table */
    };
    
    g_type_init ();
                                                                                                                             
    object_type = g_type_register_static (G_TYPE_OBJECT, "SimUnittesting", &type_info, 0);
  }
                                                                                                                             
  return object_type;
}

/*
 *
 * Create a new unittesting object
 *
 */
SimUnittesting*
sim_unittesting_new ()
{
  SimUnittesting *unittesting;

  unittesting = SIM_UNITTESTING (g_object_new (SIM_TYPE_UNITTESTING, NULL));

  unittesting->_priv->unittests = g_list_alloc();
  if (unittesting->_priv->unittests == NULL) {
    ossim_debug ( "Uops... problems loading unittests\n");
    return NULL;
  }
  unittesting->_priv->regex = NULL;
  
  return unittesting;
}

/*
 *
 *
 */
void
sim_unittesting_append(SimUnittesting *engine, gchar *name, gboolean (*func)(void), guint expected) {
  SimUnittest *test = (SimUnittest *)calloc(1, sizeof(SimUnittest)); 

  if (test == NULL) {
      ossim_debug ( "Error appending unittest %s to the engine. Could not allocate memory\n", name);
      return;
  }

  test->name = name;
  test->func = func;
  test->expected = expected;

  engine->_priv->unittests = g_list_append(engine->_priv->unittests, test);
}

/*
 *
 *
 */
gboolean
sim_unittesting_execute_test(SimUnittest *test) {
  if (test->func != NULL)
      return (test->func() == test->expected);
  else return 0;
}

/*
 *
 */
gboolean
sim_unittesting_execute_unittests(SimUnittesting *engine) {
  guint items = g_list_length(engine->_priv->unittests);
  guint i = 0;

  for (; i < items; i++) {
    SimUnittest *test = g_list_nth_data(engine->_priv->unittests, i);
    if (test != NULL) {

      if (engine->_priv->regex != NULL)
        if (!g_regex_match_simple(engine->_priv->regex, test->name, 0, 0)) {
          // If there's a regex filter, match it, otherwise skip running this test and continue
          continue;
        }

      printf("[%d] Starting %s:\n", i, test->name);
      int res = sim_unittesting_execute_test(test);
      printf("\n[%d] %s result: %60s (ret:%d)\n", i, test->name, (res) ? "OK" : "FAILED", res);
      if (res)
        engine->_priv->ok++;
      else
        engine->_priv->fail++;

      printf("\n");
    }
  }
  printf("Unittests OK: %d\n", engine->_priv->ok);
  printf("Unittests FAILED: %d\n", engine->_priv->fail);
  printf("Total: %d\n", engine->_priv->fail + engine->_priv->ok);
  if (engine->_priv->regex)
    printf("Filter applied: \"%s\"\n", engine->_priv->regex);
  return (engine->_priv->fail == 0);
}

void sim_unittesting_set_regex(SimUnittesting *engine, gchar *regex) {
  engine->_priv->regex = regex;
}

#endif /* USE_UNITTESTS */

// vim: set tabstop=2:

