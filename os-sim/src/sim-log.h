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

#ifndef __SIM_LOG_H__
#define __SIM_LOG_H__ 1

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <glib.h>
#include <gnet.h>
#include <glib-object.h>

#include "sim-config.h"

#define ossim_debug(...)\
	do {\
		if (ossim_log_flag){\
			g_debug  (__VA_ARGS__);\
		}\
	}while (0)


G_BEGIN_DECLS

guint sim_log_reopen(void);

inline void sim_log_write(gchar *msg, const gchar *log_domain);
void sim_log_init (void);
void sim_log_free (void);
void sim_log_set_handlers (void);

extern int ossim_log_flag;



G_END_DECLS

#endif /* __SIM_LOG_H__ */
// vim: set tabstop=2:
