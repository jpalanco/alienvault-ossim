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

#ifndef __SIM_OBJECT_H__
#define __SIM_OBJECT_H__

#define SIM_DEFINE_TYPE(TypeName, type_name, TYPE_PARENT, flags)        \
                                                                        \
  static void     type_name##_class_init        (TypeName##Class *klass); \
  static void     type_name##_instance_init     (TypeName        *self); \
  static void     type_name##_finalize          (GObject         *self); \
  GType           type_name##_get_type          (void);                 \
  static gpointer parent_class = NULL;                                  \
  static GType    object_type = G_TYPE_NONE;                            \
  static void     type_name##_class_intern_init (gpointer klass)        \
  {                                                                     \
    parent_class = g_type_class_peek_parent (klass);                    \
    type_name##_class_init ((TypeName##Class*) klass);                  \
  }                                                                     \
                                                                        \
                                                                        \
  GType                                                                 \
  type_name##_get_type (void)                                           \
  {                                                                     \
    return (object_type);                                               \
  }                                                                     \
                                                                        \
  void                                                                  \
  type_name##_register_type (void)                                      \
  {                                                                     \
    object_type = g_type_register_static_simple (TYPE_PARENT,           \
                                                 g_intern_static_string (#TypeName), \
                                                 sizeof (TypeName##Class), \
                                                 (GClassInitFunc) type_name##_class_intern_init, \
                                                 sizeof (TypeName),     \
                                                 (GInstanceInitFunc) type_name##_instance_init, \
                                                 (GTypeFlags) flags);   \
                                                                        \
  }

#endif
