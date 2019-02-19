/*
 * Copyright (C) 2014  Pablo Arroyo loma
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the
 * Free Software Foundation, Inc., 59 Temple Place - Suite 330,
 * Boston, MA 02111-1307, USA.
 */

#ifndef __RADIX_TREE_H__
#define __RADIX_TREE_H__

#include <glib.h>

G_BEGIN_DECLS

typedef struct _RadixTree RadixTree;

typedef gboolean (*RadixTraverseFunc) (const guint8 *key,
                                       guint         key_mask,
                                       gpointer      value,
                                       gpointer      data);


RadixTree *                     radix_tree_new                          (void);
RadixTree *                     radix_tree_new_with_destroy_func        (GDestroyNotify        value_destroy_func);
RadixTree *                     radix_tree_ref                          (RadixTree            *tree);
void                            radix_tree_unref                        (RadixTree            *tree);
void                            radix_tree_insert                       (RadixTree            *tree,
                                                                         const guint8         *key,
                                                                         guint                 key_mask,
                                                                         gpointer              value);
void                            radix_tree_replace                      (RadixTree            *tree,
                                                                         const guint8         *key,
                                                                         guint                 key_mask,
                                                                         gpointer              value);
gboolean                        radix_tree_remove                       (RadixTree            *tree,
                                                                         const guint8         *key,
                                                                         guint                 key_mask);
GPtrArray *                     radix_tree_lookup                       (RadixTree            *tree,
                                                                         const guint8         *key,
                                                                         guint                 key_mask,
                                                                         guint                *key_mask_found);
GPtrArray *                     radix_tree_exact_lookup                 (RadixTree            *tree,
                                                                         const guint8         *key,
                                                                         guint                 key_mask);
void                            radix_tree_foreach                      (RadixTree            *tree,
                                                                         RadixTraverseFunc     func,
                                                                         gpointer              user_data);
guint                           radix_tree_nnodes                       (RadixTree            *tree);

G_END_DECLS

#endif /* __RADIX_TREE_H__ */
