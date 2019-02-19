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

/**
 * SECTION: radix-tree
 * @short_description: Radix tree implementation using glib
 *
 * TODO
 **/

#include "config.h"

#include "radix-tree.h"

#include <string.h>

#define RADIX_TREE_LEFT  0
#define RADIX_TREE_RIGHT 1

typedef struct _RadixTreeNode RadixTreeNode;

/**
 * RadixTree:
 *
 * The <structname>RadixTree</structname> struct is an opaque data
 * structure representing a Radix Binary Tree. It
 * should be accessed only by using the following functions.
 **/
struct _RadixTree
{
  RadixTreeNode  *root;
  GDestroyNotify  value_destroy_func;
  guint           nnodes;
  gint            ref_count;
};

struct _RadixTreeNode
{
  guint8         *key;         /* Key raw data */
  guint           key_mask;    /* Key bit mask */
  gpointer       *value;
  RadixTreeNode  *left;
  RadixTreeNode  *right;
  RadixTreeNode  *parent;
};

static RadixTreeNode *
radix_tree_node_new (const guint8  *key,
                     guint          key_mask,
                     gpointer       value,
                     RadixTreeNode *parent)
{
  RadixTreeNode *node = g_slice_new0 (RadixTreeNode);

  guint key_bytes = key_mask / 8;
  if ((key_mask % 8) != 0)
    key_bytes ++;

  node->key = g_slice_alloc0 (sizeof(guint8) * 16);
  memcpy (node->key, key, key_bytes);

  node->key_mask = key_mask;
  node->value = value;
  node->left = NULL;
  node->right = NULL;
  node->parent = parent;

  return node;
}

#define radix_get_bit(key, bit) ((key[bit / 8] >> (7 - (bit % 8))) & 0x1)

static inline gint
radix_cmp_bit (const guint8 *key1,
               const guint8 *key2,
               guint         bit)
{
  guint bit1 = radix_get_bit (key1, bit);
  guint bit2 = radix_get_bit (key2, bit);

  if (bit1 == bit2)
    return 0;
  if (bit1 > bit2)
    return 1;
  else
    return -1;
}

static inline RadixTreeNode *
radix_tree_first_leaf_node (RadixTreeNode *node)
{
  RadixTreeNode * tmp = NULL;

  if (node == NULL)
    return NULL;

  tmp = node;

  while (tmp->left)
    tmp = tmp->left;

  return tmp;
}

static inline RadixTreeNode *
radix_tree_node_next (RadixTreeNode *node)
{
  RadixTreeNode * tmp = NULL;

  tmp = node->parent;

  if (tmp && tmp->right && tmp->right != node)
    {
      tmp = tmp->right;
      while (tmp->left)
        tmp = tmp->left;
    }

  return tmp;
}

static inline void
radix_tree_free_array_string (gpointer data)
{
  GString * str = NULL;
  str = (GString *)data;

  (void)g_string_free(str, TRUE);

  return;
}

static void
radix_tree_split_node (RadixTreeNode *node,
                       guint          key_mask,
                       gpointer      *value)
{
  RadixTreeNode * new_node = NULL;

  new_node = radix_tree_node_new (node->key,
                                  node->key_mask,
                                  node->value,
                                  node);

  new_node->left = node->left;
  if (new_node->left)
    new_node->left->parent = new_node;
  new_node->right = node->right;
  if (new_node->right)
    new_node->right->parent = new_node;

  node->right = NULL;
  node->left = NULL;
  node->value = value;

  if (radix_get_bit (node->key, key_mask) == 1)
    node->right = new_node;
  else
    node->left = new_node;

  node->key_mask = key_mask;
}

static void
radix_tree_insert_internal (RadixTree     *tree,
                            const guint8  *key,
                            guint          key_mask,
                            gpointer       value,
                            gboolean       replace)
{
  RadixTreeNode * root = NULL;
  RadixTreeNode * node = NULL;
  guint mask = 0;
  gint equal = 0;
  gboolean split_root = FALSE;

  g_return_if_fail (key != NULL);

  root = tree->root;
  node = root;

  /* Iterate to find key position */
  while (node != NULL)
    {
      equal = 0;
      split_root = FALSE;
      root = node;

      while (equal == 0
             && mask < key_mask
             && mask < node->key_mask)
        {
          equal = radix_cmp_bit (key, node->key, mask);
          mask ++;
        }

      if (equal == 0)
        {
          /* node completed */
          if (mask == node->key_mask)
            {
              if (key_mask == node->key_mask)
                {
                  if (node->value == NULL)
                    {
                      node->value = value;
                      tree->nnodes ++;
                    }
                  else if (replace)
                    {
                      if (tree->value_destroy_func)
                        tree->value_destroy_func (node->value);
                      node->value = value;
                    }
                  else
                    {
                      if (tree->value_destroy_func)
                        tree->value_destroy_func (value);
                    }
                  return;
                }
              else
                {
                  /* check next branch */
                  if (radix_get_bit (key, mask) == 1)
                    node = node->right;
                  else
                    node = node->left;
                }
            }
          else
            {
              /* node key includes key */
              radix_tree_split_node (root, mask, value);
              tree->nnodes ++;
              return;
            }
        }
      else
        {
          /* node key and key differs */
          split_root = TRUE;
          node = NULL;
        }
    }

  if (split_root)
    radix_tree_split_node (root, mask - 1, NULL);

  if (equal == 0)
    {
      if (radix_get_bit (key, mask) == 1)
        equal = 1;
      else
        equal = -1;
    }

  if (equal == -1)
    {
      root->left = radix_tree_node_new (key, key_mask, value, root);
      tree->nnodes ++;
    }
  else
    {
      root->right = radix_tree_node_new (key, key_mask, value, root);
      tree->nnodes ++;
    }
}

static GPtrArray *
radix_tree_lookup_internal (RadixTree    *tree,
                            const guint8 *key,
                            guint         key_mask,
                            gboolean      exact_search,
                            guint        *matched_key_mask)
{
  RadixTreeNode * node = NULL;
  GPtrArray * matched_values = NULL, * found_value = NULL;
  GString * matched_keys = NULL;
  guint i = 0;
  guint matched_mask = 0;
  gint equal = 0;
  guint mask = 0;

  node = tree->root;
  while (node != NULL)
    {
      while (equal == 0
             && mask < key_mask
             && mask < node->key_mask)
        {
          equal = radix_cmp_bit (key, node->key, mask);
          mask ++;
        }

      if (equal != 0)
        {
          node = NULL;
        }
      else
        {
          /* node completed */
          if (mask == node->key_mask)
            {
              if (key_mask == node->key_mask)
                {
                  found_value = (GPtrArray *)node->value;

                  if (found_value != NULL)
                  {
                    if (matched_values == NULL)
                    {
                      matched_values = g_ptr_array_new_with_free_func ((GDestroyNotify)radix_tree_free_array_string);
                      matched_keys = g_string_new ((gchar *)g_ptr_array_index (found_value, 0));
                      g_ptr_array_add (matched_values, (gpointer)matched_keys);
                    }
                    else
                    {
                      matched_keys = (GString *)g_ptr_array_index(matched_values, 0);
                      g_string_append_printf (matched_keys, ", %s", (gchar *)g_ptr_array_index (found_value, 0));
                    }

                    for (i = 1; i < found_value->len; i++)
                      g_ptr_array_add (matched_values, (gpointer)g_string_new ((gchar *)g_ptr_array_index (found_value, i)));
                  }

                  matched_mask = node->key_mask;
                  node = NULL;
                }
              else
                {
                  if (!exact_search && node->value != NULL)
                    {
                      found_value = (GPtrArray *)node->value;

                      if (found_value != NULL)
                      {
                        if (matched_values == NULL)
                        {
                          matched_values = g_ptr_array_new_with_free_func ((GDestroyNotify)radix_tree_free_array_string);
                          matched_keys = g_string_new ((gchar *)g_ptr_array_index (found_value, 0));
                          g_ptr_array_add (matched_values, (gpointer)matched_keys);
                        }
                        else
                        {
                          matched_keys = (GString *)g_ptr_array_index(matched_values, 0);
                          g_string_append_printf (matched_keys, ", %s", (gchar *)g_ptr_array_index (found_value, 0));
                        }

                        for (i = 1; i < found_value->len; i++)
                          g_ptr_array_add (matched_values, (gpointer)g_string_new ((gchar *)g_ptr_array_index (found_value, i)));
                      }

                      matched_mask = node->key_mask;
                    }

                  /* check next branch */
                  if (radix_get_bit (key, mask) == 1)
                    node = node->right;
                  else
                    node = node->left;
                }
            }
          else
            {
              /* node key includes key */
              node = NULL;
            }
        }
    }

  if (matched_key_mask != NULL)
    *matched_key_mask = matched_mask;

  return matched_values;
}

static gboolean
radix_tree_remove_node (RadixTree     *tree,
                        RadixTreeNode *node,
                        gint           parent_route)
{
  RadixTreeNode *parent = node->parent;
  gboolean remove_parent = FALSE;

  /* Remove node value */
  if (node->value)
    {
       if (tree->value_destroy_func)
         tree->value_destroy_func (node->value);

       node->value = NULL;
       tree->nnodes --;
    }

  /* Node with two childs */
  if (node->left != NULL && node->right != NULL)
    return FALSE;

  if (node->left != NULL)
    {
      node->left->parent = node->parent;

      if (parent_route == RADIX_TREE_RIGHT)
        node->parent->right = node->left;
      else
        node->parent->left = node->left;
    }
  else if (node->right != NULL)
    {
      node->right->parent = node->parent;

      if (parent_route == RADIX_TREE_RIGHT)
        node->parent->right = node->right;
      else
        node->parent->left = node->right;
    }
  else
    {
      /* leaf node */
      if (parent->value == NULL && parent != tree->root)
        remove_parent = TRUE;

      if (parent_route == RADIX_TREE_RIGHT)
        node->parent->right = NULL;
      else
        node->parent->left = NULL;
    }

  g_slice_free (guint8, node->key);
  g_slice_free (RadixTreeNode, node);

  return remove_parent;
}


static void
radix_tree_remove_leaf_node (RadixTree     *tree,
                             RadixTreeNode *node)
{
  /* Remove node value */
  if (node->value)
    {
       if (tree->value_destroy_func)
         tree->value_destroy_func (node->value);
       tree->nnodes --;
    }

  g_slice_free (guint8, node->key);
  g_slice_free (RadixTreeNode, node);
}

static void
radix_tree_remove_all (RadixTree *tree)
{
  RadixTreeNode *node;
  GQueue *movements;
  movements = g_queue_new ();

  node = tree->root;
  while (node)
    {
      if (node->left)
        {
          node = node->left;
          g_queue_push_tail (movements, GINT_TO_POINTER (RADIX_TREE_LEFT));
        }
      else if (node->right)
        {
          node = node->right;
          g_queue_push_tail (movements, GINT_TO_POINTER (RADIX_TREE_RIGHT));
        }
      else
        {
          RadixTreeNode *parent = node->parent;
          radix_tree_remove_leaf_node (tree, node);
          node = parent;
          if (node != NULL)
            {
              if (GPOINTER_TO_INT (g_queue_pop_tail (movements)) == RADIX_TREE_RIGHT)
                node->right = NULL;
              else
                node->left = NULL;
            }
        }
    }

  g_queue_free (movements);
}

/* public methods */

/**
 * radix_tree_new:
 *
 * Creates a new #RadixTree.
 *
 * Returns: (transfer none): a new #RadixTree
 **/
RadixTree *
radix_tree_new (void)
{
  return radix_tree_new_with_destroy_func (NULL);
}

/**
 * radix_tree_new_with_destoy_func:
 * @value_destroy_func: a #GDestroyNotify function
 *
 * Creates a new #RadixTree.
 *
 * Returns: (transfer none): a new #RadixTree
 **/
RadixTree *
radix_tree_new_with_destroy_func (GDestroyNotify value_destroy_func)
{
  RadixTree *tree = g_slice_new0 (RadixTree);
  guint8 root_key = 0;

  tree->root = radix_tree_node_new (&root_key, 0, NULL, NULL);
  tree->value_destroy_func = value_destroy_func;
  tree->nnodes = 0;
  tree->ref_count = 1;

  return tree;
}

/**
 * radix_tree_ref:
 * @tree: a #RadixTree.
 *
 * Increments the reference count of @tree by one.  It is safe to call
 * this function from any thread.
 *
 * Return value: the passed in #RadixTree.
 **/
RadixTree *
radix_tree_ref (RadixTree *tree)
{
  g_return_val_if_fail (tree != NULL, NULL);
  g_return_val_if_fail (tree->ref_count > 0, NULL);

  g_atomic_int_inc (&tree->ref_count);

  return tree;
}

/**
 * radix_tree_unref:
 * @tree: a #RadixTree.
 *
 * Increments the reference count of @tree by one. It is safe to call
 * this function from any thread.
 *
 * Decrements the reference count of @tree by one.  If the reference count
 * drops to 0, all keys and values will be destroyed (if destroy
 * functions were specified) and all memory allocated by @tree will be
 * released.
 *
 * It is safe to call this function from any thread.
 **/
void
radix_tree_unref (RadixTree *tree)
{
  g_return_if_fail (tree != NULL);
  g_return_if_fail (tree->ref_count > 0);

  if (g_atomic_int_dec_and_test (&tree->ref_count))
    {
      radix_tree_remove_all (tree);
      g_slice_free (RadixTree, tree);
    }

}

/**
 * radix_tree_insert:
 * @tree: a #RadixTree.
 * @key: the key to insert.
 * @key_mask: mask to apply to the key. This is the number of bits of the key.
 * @value: the value correspondig to the key.
 *
 * Inserts a key(with mask)/value pair into a #RadixTree. If the given key
 * already exists in the #RadixTree its corresponding value is set to the new
 * value. If you supplied a @value_destroy_func when creating the #RadixTree,
 * the passed value is freed using that function.
 **/
void
radix_tree_insert (RadixTree    *tree,
                   const guint8 *key,
                   guint         key_mask,
                   gpointer      value)
{
  g_return_if_fail (tree != NULL);
  g_return_if_fail (key_mask > 0);

  radix_tree_insert_internal (tree, key, key_mask, value, FALSE);
}

/**
 * radix_tree_replace:
 * @tree: a #RadixTree.
 * @key: the key to insert.
 * @key_mask: mask to apply to the key. This is the number of bits of the key.
 * @value: the value correspondig to the key.
 *
 * Inserts a key(with mask)/value pair into a #RadixTree. If the given key
 * already exists in the #RadixTree its corresponding value is set to the new
 * value. If you supplied a @value_destroy_func when creating the #RadixTree,
 * the value is freed using that function.
 **/
void
radix_tree_replace (RadixTree    *tree,
                    const guint8 *key,
                    guint         key_mask,
                    gpointer      value)
{
  g_return_if_fail (tree != NULL);
  g_return_if_fail (key != NULL);
  g_return_if_fail (value != NULL);
  g_return_if_fail (key_mask > 0);

  radix_tree_insert_internal (tree, key, key_mask, value, TRUE);
}

/**
 * radix_tree_remove:
 * @tree: a #RadixTree.
 * @key: the key to remove.
 * @key_mask: mask to apply to the key. This is the number of bits of the key.
 *
 * Remove the key and its value from #RadixTree. If the given @key with
 * @key_mask exist in the #RadixTree, its node is removed and if a
 * @key_destroy_func was supplied when creating the #RadixTree
 *
 * Returns: %TRUE if the @key with @key_mask is removed from the @tree.
 * %FALSE otherwise.
 **/
gboolean
radix_tree_remove (RadixTree    *tree,
                   const guint8 *key,
                   guint         key_mask)
{
  g_return_val_if_fail (tree != NULL, FALSE);
  g_return_val_if_fail (key != NULL, FALSE);
  g_return_val_if_fail (key_mask > 0, FALSE);

  RadixTreeNode * node = NULL;
  RadixTreeNode * parent = NULL;
  gint last_movement = 0;
  gint parent_last_movement = 0;
  gint equal = 0;
  guint mask = 0;
  gboolean found = FALSE;
  gboolean remove_parent = FALSE;

  node = tree->root;
  parent = NULL;
  equal = 0;
  mask = 0;
  found = FALSE;
  remove_parent = FALSE;
  last_movement = 0;
  parent_last_movement = 0;

  while (node != NULL && !found)
    {
      while (equal == 0
             && mask < key_mask
             && mask < node->key_mask)
        {
          equal = radix_cmp_bit (key, node->key, mask);
          mask ++;
        }

      if (equal != 0)
        {
          node = NULL;
        }
      else
        {
          /* node completed */
          if (mask == node->key_mask)
            {
              if (key_mask == node->key_mask)
                {
                  if (node->value == NULL)
                    node = NULL;
                  else
                    found = TRUE;
                }
              else
                {
                  parent = node;
                  parent_last_movement = last_movement;

                  /* check next branch */
                  if (radix_get_bit (key, mask) == 1)
                    {
                      node = node->right;
                      last_movement = RADIX_TREE_RIGHT;
                    }
                  else
                    {
                      node = node->left;
                      last_movement = RADIX_TREE_LEFT;
                    }
                }
            }
          else
            {
              /* node key includes key */
              node = NULL;
            }
        }
    }

  if (node == NULL)
    return FALSE;

  remove_parent = radix_tree_remove_node (tree, node, last_movement);
  if (remove_parent)
    radix_tree_remove_node (tree, parent, parent_last_movement);

  return TRUE;
}

/**
 * radix_tree_lookup:
 * @tree: a #RadixTree.
 * @key: the key to lookup for.
 * @key_mask: mask to apply to the key. This is the number of bits of the key.
 * @key_mask_found: key mask found or %NULL.
 *
 * Looks up a key with mask in the #RadixTree. Returns the value associated
 * for the better (nearest) key/mask in the tree that includes the key/mask
 * passed.
 *
 * Returns: associated values found in the #RadixTree, in a #GPtrArray, or %NULL
 * if the key is not found.
 **/
GPtrArray *
radix_tree_lookup (RadixTree    *tree,
                   const guint8 *key,
                   guint         key_mask,
                   guint        *key_mask_found)
{
  g_return_val_if_fail (tree != NULL, FALSE);
  g_return_val_if_fail (key != NULL, FALSE);
  g_return_val_if_fail (key_mask > 0, FALSE);

  return radix_tree_lookup_internal (tree, key, key_mask, FALSE, key_mask_found);
}

/**
 * radix_tree_exact_lookup: *
 * @tree: a #RadixTree.
 * @key: the key to lookup for.
 * @key_mask: mask to apply to the key. This is the number of bits of the key.
 *
 * Looks up a the exactly key with mask in the #RadixTree. Returns the
 * associated value for the key/mask or %NULL if the key/mask is not in the tree.
 *
 * Returns: the associated values found in the #RadixTree in a #GPtrArray, or %NULL
 * if the key is not found.
 **/
GPtrArray *
radix_tree_exact_lookup (RadixTree    *tree,
                         const guint8 *key,
                         guint         key_mask)
{
  g_return_val_if_fail (tree != NULL, FALSE);
  g_return_val_if_fail (key != NULL, FALSE);
  g_return_val_if_fail (key_mask > 0, FALSE);

  return radix_tree_lookup_internal (tree, key, key_mask, TRUE, NULL);
}


/**
 * radix_tree_foreach:
 * @tree: a #RadixTree.
 * @func: the function to call for each element.
 * @user_data: user data to pass to the function.
 *
 * Calls a function for each element of the #RadixTree
 **/
void
radix_tree_foreach (RadixTree         *tree,
                    RadixTraverseFunc  func,
                    gpointer           user_data)
{
  g_return_if_fail (tree != NULL);
  g_return_if_fail (func != NULL);

  RadixTreeNode * node = NULL;

  node = radix_tree_first_leaf_node (tree->root);
  while (node)
    {
      if (node->value != NULL)
        {
          if ((*func) (node->key, node->key_mask, node->value, user_data))
            break;
        }

      node = radix_tree_node_next (node);
    }
}

/**
 * radix_tree_nnodes:
 * @tree: a #RadixTree.
 *
 * Gets the number of nodes in a #RadixTree.
 *
 * Returns: the number of nodes in the #RadixTree.
 **/
guint
radix_tree_nnodes (RadixTree *tree)
{
  g_return_val_if_fail (tree != NULL, 0);

  return tree->nnodes;
}
