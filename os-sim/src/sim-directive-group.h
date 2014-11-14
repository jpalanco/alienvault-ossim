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

#ifndef __SIM_DIRECTIVE_GROUP_H__
#define __SIM_DIRECTIVE_GROUP_H__ 1

#include <glib.h>
#include <glib-object.h>

G_BEGIN_DECLS

#define SIM_TYPE_DIRECTIVE_GROUP		(sim_directive_group_get_gtype ())
#define SIM_DIRECTIVE_GROUP(obj)		(G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_DIRECTIVE_GROUP, SimDirectiveGroup))
#define SIM_DIRECTIVE_GROUP_CLASS(klass)	(G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_DIRECTIVE_GROUP, SimDirectiveGroupClass))
#define SIM_IS_DIRECTIVE_GROUP(obj)		(G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_DIRECTIVE_GROUP))
#define SIM_IS_DIRECTIVE_GROUP_CLASS(klass)	(G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_DIRECTIVE_GROUP))
#define SIM_DIRECTIVE_GROUP_GET_CLASS(obj)	(G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_DIRECTIVE_GROUP, SimDirectiveGroupClass))

typedef struct _SimDirectiveGroup		SimDirectiveGroup;
typedef struct _SimDirectiveGroupClass		SimDirectiveGroupClass;
typedef struct _SimDirectiveGroupPrivate	SimDirectiveGroupPrivate;

struct _SimDirectiveGroup {
  GObject	parent;

  SimDirectiveGroupPrivate	*_priv;
};

struct _SimDirectiveGroupClass {
  GObjectClass	parent_class;
};

GType			sim_directive_group_get_gtype		(void);
SimDirectiveGroup*	sim_directive_group_new			(void);

gchar*			sim_directive_group_get_name		(SimDirectiveGroup	*dg);
void			sim_directive_group_set_name		(SimDirectiveGroup	*dg,
								 const gchar			*name);
gboolean		sim_directive_group_get_sticky		(SimDirectiveGroup	*dg);
void			sim_directive_group_set_sticky		(SimDirectiveGroup	*dg,
								 gboolean		sticky);

void			sim_directive_group_append_id		(SimDirectiveGroup	*dg,
								 gint			id);
void			sim_directive_group_remove_id		(SimDirectiveGroup	*dg,
								 gint			id);
GList*			sim_directive_group_get_ids		(SimDirectiveGroup	*dg);

G_END_DECLS

#endif /* __SIM_DIRECTIVE_GROUP_H__ */

// vim: set tabstop=2:

