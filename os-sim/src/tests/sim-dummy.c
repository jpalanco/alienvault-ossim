/* (c) AlienVault Inc. 2012-2013
 * All rights reserved
 *
 * This code is protected by copyright and distributed under licenses
 * restricting its use, copying, distribution, and decompilation. It may
 * only be reproduced or used under license from Alienvault Inc. or its
 * authorised licensees.
 */

/**
  Only links to UNITTEST CODe
*/
#include "sim-dummy.h"
#include <glib.h>
#include "os-sim.h"
#include "sim-util.h"
#include <string.h>
SimMain ossim;
SimCmdArgs  simCmdArgs;
#if 0
gint
sim_database_execute_no_query (SimDatabase * database, const gchar * buffer)
{
  (void) database;
  (void) buffer;
  return 0;
}

gchar *           sim_str_escape                          (const gchar         *source,
                                                           GdaConnection       *connection,
                                                           gsize                can_have_nulls)
{
  (void)connection;
  (void)can_have_nulls;
  return g_strdup(source);
}

GdaConnection*    sim_database_get_conn                         (SimDatabase  *database)
{
  (void)database;
  return NULL;
}
#endif


void
init_dummy (void)
{
  memset (&ossim, 0, sizeof (SimMain));
}

