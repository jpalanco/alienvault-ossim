// vim: ts=2:sw=2:sts=2:expandtab
/* -*- Mode: C; c-set-style: linux indent-tabs-mode: nil; c-basic-offset: 2; tab-width: 2 -*- */
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

#ifndef _AVR_TLD_H_
#define _AVR_TLD_H_

#include <glib-object.h>
#include <glib.h>
#include <gio/gio.h>

G_BEGIN_DECLS

typedef struct _AvrTld        AvrTld;
typedef struct _AvrTldClass   AvrTldClass;
typedef struct _AvrTldPrivate AvrTldPrivate;

#define AVR_TYPE_TLD            (avr_tld_get_type ())
#define AVR_TLD(obj)            (G_TYPE_CHECK_INSTANCE_CAST ((obj), AVR_TYPE_TLD, AvrTld))
#define AVR_IS_TLD(obj)         (G_TYPE_CHECK_INSTANCE_TYPE ((obj), AVR_TYPE_TLD))
#define AVR_TLD_CLASS(klass)    (G_TYPE_CHECK_CLASS_CAST ((klass), AVR_TYPE_TLD, AvrTldClass))
#define AVR_IS_TLD_CLASS(klass) (G_TYPE_CHECK_CLASS_TYPE ((klass), AVR_TYPE_TLD))
#define AVR_TLD_GET_CLASS(obj)  (G_TYPE_INSTANCE_GET_CLASS ((obj), AVR_TYPE_TLD, AvrTldClass))

struct _AvrTld
{
  GObject parent;
  AvrTldPrivate *priv;
};

struct _AvrTldClass
{
  GObjectClass parent_class;
};


/*
 * Prototypes
 */
GType           avr_tld_get_type                      (void);
void            avr_tld_register_type                 (void);
GType           avr_tld_get_type                      (void);

AvrTld *        avr_tld_new                           (void);

gchar *         avr_tld_get_domain                    (AvrTld *,
                                                       const gchar *);


G_END_DECLS

#endif /* _AVR_TLD_H_ */
