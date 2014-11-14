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

#ifndef __OS_SIM_H__
#define __OS_SIM_H__

#include <libos-sim.h>

#include <glib.h>
#include <libsoup/soup.h>

#include "sim-organizer.h"
#include "sim-scheduler.h"
#include "sim-server.h"
#include "sim-idm.h"
#include "sim-user-auth.h"

G_BEGIN_DECLS

typedef struct
{
  gchar     *version;
  SimConfig *config; //this config is passed to server, scheduler, organizer, etc in sim_*_new,
                     //so they can add or remove configuration things individually.

  SimContainer *container;
  SimOrganizer *organizer;
  SimScheduler *scheduler;
  SimServer    *server;
  SimReputation *reputation;

  SimDatabase *dbossim;
  SimDatabase *dbsnort;
  SimDatabase *dbosvdb;

  struct
  {
    gchar *filename;
    gchar *filename_external;
    gint   fd;
    gint   fd_external;
    gint   level;
    guint  handler[3]; //we use 3 handlers because we call 3 times to g_log_set_handler().

  } log;

  GMainLoop *main_loop;
  SoupServer *soup_server;
  SimUserAuth *user_auth;
  SoupAuthDomain *domain_auth;
  gboolean purging_backlogs;

} SimMain;

extern SimMain  ossim;

typedef struct
{
  gchar    *config;
  gboolean  daemon;
  gint      debug;
  gchar    *ip;
  gint      port;
  gint      dvl;
  gboolean  check_siem;

#ifdef USE_UNITTESTS
  gint   unittests;
  gchar *unittest_regex;
#endif

} SimCmdArgs;

extern SimCmdArgs simCmdArgs;

G_END_DECLS

#endif /* __OS_SIM_H__ */
