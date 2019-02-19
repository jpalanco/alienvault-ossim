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

#ifndef __SIM_UUID_H__
#define __SIM_UUID_H__ 1

#include <glib.h>
#include <libgda.h>
#include <uuid/uuid.h>

G_BEGIN_DECLS

typedef struct _SimUuid        SimUuid;
typedef struct _SimUuidClass   SimUuidClass;
typedef struct _SimUuidPrivate SimUuidPrivate;

#include "sim-object.h"
#include "sim-unittesting.h"

#define SIM_TYPE_UUID                   (sim_uuid_get_type ())
#define SIM_UUID(obj)                   (G_TYPE_CHECK_INSTANCE_CAST ((obj), SIM_TYPE_UUID, SimUuid))
#define SIM_IS_UUID(obj)                (G_TYPE_CHECK_INSTANCE_TYPE ((obj), SIM_TYPE_UUID))
#define SIM_UUID_CLASS(kclass)          (G_TYPE_CHECK_CLASS_CAST ((kclass), SIM_TYPE_UUID, SimUuidClass))
#define SIM_UUID_IS_UUID_CLASS(kclass)  (G_TYPE_CHECK_CLASS_TYPE ((kclass), SIM_TYPE_UUID))
#define SIM_UUID_GET_CLASS(obj)         (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_UUID, SimUuidClass))

struct _SimUuid
{
  GObject parent;
  SimUuidPrivate *priv;
};

struct _SimUuidClass
{
  GObjectClass  parent_class;
};

GType           sim_uuid_get_type                     (void);
void            sim_uuid_register_type                (void);

SimUuid *       sim_uuid_new                          (void);
SimUuid *       sim_uuid_new_from_bin                 (const guchar  *str);
SimUuid *       sim_uuid_new_from_blob                (const GdaBlob *blob);
SimUuid *       sim_uuid_new_from_string              (const gchar   *str);
SimUuid *       sim_uuid_new_from_uuid                (uuid_t        *old_uuid);

const gchar *   sim_uuid_get_string                   (SimUuid       *id);
const gchar *   sim_uuid_get_db_string                (SimUuid       *id);
uuid_t *        sim_uuid_get_uuid                     (SimUuid       *id);

// Hash utility functions.
guint           sim_uuid_hash                         (gconstpointer  v);
gboolean        sim_uuid_equal                        (gconstpointer  v1,
                                                       gconstpointer  v2);

gboolean        sim_uuid_is_valid_string              (const gchar   *str);
gchar *         sim_uuid_to_base64                    (SimUuid *id);


#ifdef USE_UNITTESTS
void            sim_uuid_register_tests               (SimUnittesting *unit_tests);
#endif

#endif /* __SIM_UUID_H__ */
