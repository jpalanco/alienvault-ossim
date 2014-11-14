/*
#
#  License:
#
#  Copyright (c) 2013 AlienVault
#  All rights reserved.
#
#  This package is free software; you can redistribute it and/or modify
#  it under the terms of the GNU General Public License as published by
#  the Free Software Foundation; version 2 dated June, 1991.
#  You may not use, modify or distribute this program under any other version
#  of the GNU General Public License.
#
#  This package is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.
#
#  You should have received a copy of the GNU General Public License
#  along with this package; if not, write to the Free Software
#  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#  MA  02110-1301  USA
#
#
#  On Debian GNU/Linux systems, the complete text of the GNU General
#  Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
#  Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#
*/
#ifndef __SIM_API_H__
#define __SIM_API_H__

#include <glib.h>

G_BEGIN_DECLS

typedef struct _SimApi        SimApi;
typedef struct _SimApiClass   SimApiClass;
typedef struct _SimApiPrivate SimApiPrivate;

#define SIM_TYPE_API            (sim_api_get_type())
#define SIM_IS_API(obj)         (G_TYPE_CHECK_INSTANCE_TYPE ((obj), SIM_TYPE_API))
#define SIM_IS_API_CLASS(klass) (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_API))
#define SIM_API_GET_CLASS(obj)  (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_API, SimApiClass))
#define SIM_API(obj)            (G_TYPE_CHECK_INSTANCE_CAST ((obj), SIM_TYPE_API, SimApi))
#define SIM_API_CLASS(klass)    (G_TYPE_CHECK_CLASS_CAST ((klass), SIM_TYPE_API, SimApiClass))
#define SIM_API_CAST(obj)       ((SimApi*)(obj))

void      sim_api_initialization ();
SimApi *  sim_api_new (void);
void      sim_api_free (SimApi *sim_api);
void      sim_api_login (SimApi *sim_api, const char *ip, guint port, const char *username, const char *password);
gchar *   sim_api_request (SimApi *sim_api, const gchar *request);

G_END_DECLS

#endif /* __SIM_API_H__ */
