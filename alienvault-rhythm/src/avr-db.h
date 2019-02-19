/*
  License:

  Copyright (c) 2015 AlienVault
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

#ifndef __AVR_DB_H__
#define __AVR_DB_H__

#include <glib-object.h>
#include <glib.h>
#include <gio/gio.h>

#include "radix-tree.h"

G_BEGIN_DECLS

typedef struct _AvrDb        AvrDb;
typedef struct _AvrDbClass   AvrDbClass;
typedef struct _AvrDbPrivate AvrDbPrivate;

#define AVR_TYPE_DB                  (avr_db_get_type ())
#define AVR_DB(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, AVR_TYPE_DB, AvrDb))
#define AVR_DB_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, AVR_TYPE_DB, AvrDbClass))
#define AVR_IS_DB(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, AVR_TYPE_DB))
#define AVR_IS_DB_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), AVR_TYPE_DB))
#define AVR_DB_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), AVR_TYPE_DB, AvrDbClass))

typedef enum
{
  IP_ADDRESS = 0,
  FILE_HASH,
  DOMAIN,
  HOSTNAME,
  AVR_TYPES
}
AvrType;


struct _AvrDb {
  GObject parent;
  AvrDbPrivate *_priv;
};

struct _AvrDbClass {
  GObjectClass parent_class;
};

void                avr_db_init                        (void);
void                avr_db_clear                       (void);

GType               avr_db_get_type                    (void);

AvrDb *             avr_db_new_tcp                     (AvrType type,
                                                        gchar * hostname,
                                                        gint port);

AvrDb *             avr_db_new_unix                    (AvrType type,
                                                        const gchar * socket);

gpointer            avr_db_ref_data                    (AvrDb * db);
void                avr_db_unref_data                  (AvrDb * db,
                                                        gpointer data);

gboolean            avr_db_has_otx_data                (void);


G_END_DECLS

#endif

