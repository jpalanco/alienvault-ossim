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

#ifndef __SIM_DEBUG_H__
#define __SIM_DEBUG_H__ 1

#include <glib.h>

G_BEGIN_DECLS

#define       SIM_DEBUG_ERR_FILE        OS_SIM_LOG_DIR "server.err"
#define		  SIM_BACKLOG_DUMP_FILE     OS_SIM_LOG_DIR "bk.backlog"

void          sim_debug_init_signals    (void);
void          sim_debug_terminate       (gint signum, gboolean core);
#define sim_debug(format,...) sim_debug_output (format,g_thread_self(),__FILE__,__FUNCTION__,__LINE__,##__VA_ARGS__);
void sim_debug_output (const char *format,void *p,const char *file,const char *func,unsigned int line, ...);
void sim_debug_print_bt (void);
void sim_debug_print_backlogs_data(GPtrArray *backlogs, GIOChannel *channel);

G_END_DECLS

#endif
