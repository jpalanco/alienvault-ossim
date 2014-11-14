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

#ifndef __SIM_MINI_OBJECT_H__
#define __SIM_MINI_OBJECT_H__

#include <glib-object.h>

/*****************************************/
/* G_DEFINE_BOXED_TYPE() since glib 2.26 */
/*****************************************/

#define G_DEFINE_BOXED_TYPE(TypeName, type_name, copy_func, free_func) G_DEFINE_BOXED_TYPE_WITH_CODE (TypeName, type_name, copy_func, free_func, {})

#define G_DEFINE_BOXED_TYPE_WITH_CODE(TypeName, type_name, copy_func, free_func, _C_) _G_DEFINE_BOXED_TYPE_BEGIN (TypeName, type_name, copy_func, free_func) {_C_;} _G_DEFINE_TYPE_EXTENDED_END()

#if !defined (__cplusplus) && (__GNUC__ > 2 || (__GNUC__ == 2 && __GNUC_MINOR__ >= 7))
#define _G_DEFINE_BOXED_TYPE_BEGIN(TypeName, type_name, copy_func, free_func) \
GType \
type_name##_get_type (void) \
{ \
  static volatile gsize g_define_type_id__volatile = 0; \
  if (g_once_init_enter (&g_define_type_id__volatile))  \
    { \
      GType (* _g_register_boxed) \
        (const gchar *, \
         union \
           { \
             TypeName * (*do_copy_type) (TypeName *); \
             TypeName * (*do_const_copy_type) (const TypeName *); \
             GBoxedCopyFunc do_copy_boxed; \
           } __attribute__((__transparent_union__)), \
         union \
           { \
             void (* do_free_type) (TypeName *); \
             GBoxedFreeFunc do_free_boxed; \
           } __attribute__((__transparent_union__)) \
        ) = g_boxed_type_register_static; \
      GType g_define_type_id = \
        _g_register_boxed (g_intern_static_string (#TypeName), copy_func, free_func); \
      { /* custom code follows */
#else
#define _G_DEFINE_BOXED_TYPE_BEGIN(TypeName, type_name, copy_func, free_func) \
GType \
type_name##_get_type (void) \
{ \
  static volatile gsize g_define_type_id__volatile = 0; \
  if (g_once_init_enter (&g_define_type_id__volatile))  \
    { \
      GType g_define_type_id = \
        g_boxed_type_register_static (g_intern_static_string (#TypeName), \
                                      (GBoxedCopyFunc) copy_func, \
                                      (GBoxedFreeFunc) free_func); \
      { /* custom code follows */
#endif /* __GNUC__ */

#define _G_DEFINE_TYPE_EXTENDED_END() \
        /* following custom code */ \
      }         \
      g_once_init_leave (&g_define_type_id__volatile, g_define_type_id); \
    }         \
  return g_define_type_id__volatile;  \
} /* closes type_name##_get_type() */

/*****************************************/
/* G_DEFINE_BOXED_TYPE() since glib 2.26 */
/*****************************************/

#define SIM_DEFINE_MINI_OBJECT_TYPE(TypeName, type_name) \
  G_DEFINE_BOXED_TYPE(TypeName, type_name,          \
    (GBoxedCopyFunc) sim_mini_object_ref,          \
    (GBoxedFreeFunc) sim_mini_object_unref)

#define SIM_MINI_OBJECT_TYPE(obj)          (SIM_MINI_OBJECT_CAST(obj)->type)
#define SIM_IS_MINI_OBJECT_TYPE(obj,type)  ((obj) && SIM_MINI_OBJECT_TYPE(obj) == (type))
#define SIM_MINI_OBJECT_CAST(obj)          ((SimMiniObject*)(obj))
#define SIM_MINI_OBJECT_CONST_CAST(obj)    ((const SimMiniObject*)(obj))
#define SIM_MINI_OBJECT(obj)               (SIM_MINI_OBJECT_CAST(obj))

typedef struct _SimMiniObject SimMiniObject;

typedef SimMiniObject * (*SimMiniObjectCopyFunction)     (const SimMiniObject *obj);
typedef gboolean        (*SimMiniObjectDisposeFunction)  (SimMiniObject *obj);
typedef void            (*SimMiniObjectFreeFunction)     (SimMiniObject *obj);
typedef void            (*SimMiniObjectCallbackFunction) (SimMiniObject *obj, gpointer data);

struct _SimMiniObject {
  GType    type;
  gint     refcount;
  gboolean ref_callback;

  SimMiniObjectCopyFunction copy;
  SimMiniObjectDisposeFunction dispose;
  SimMiniObjectFreeFunction free;
  SimMiniObjectCallbackFunction callback;
};

void            sim_mini_object_init  (SimMiniObject *mini_object, GType type);
SimMiniObject * sim_mini_object_copy  (const SimMiniObject * mini_object);
void            sim_mini_object_add_callback (SimMiniObject * mini_object,
                                              SimMiniObjectCallbackFunction callback);
SimMiniObject * sim_mini_object_ref   (SimMiniObject *mini_object);
void            sim_mini_object_unref (SimMiniObject *mini_object);

#endif

