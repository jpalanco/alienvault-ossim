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

#include "config.h"

#include "sim-radix.h"

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <inttypes.h>

#include "sim-log.h"
#include "sim-unittesting.h"

static void padding (int i);
static void printBin(uint8_t *key, uint8_t keylen);
static void sim_radix_ud_update_generic (void *old,
                                         void *new_data);
/*
 * Helper functions for printing node info
 */
void padding(int i) {
  int j = 0;
  for (j = 0; j < i; j++)
    printf("..");
}

/*
 * Helper functions for printing node info
 */
void printBin(uint8_t *key, uint8_t keylen) {
  uint8_t i = 0;
  //printf("0x%x=", key);
  for (; i < keylen; i++) {
    if (i % 4 == 0)
      printf(" ");
    printf("%"PRIu8, GET_BIT(key[i / 8], i % 8) & 0x01);
  }
  printf(" [%d] ", keylen);
}

/*
 * Helper functions for printing node info
 */
void printKey(SimRadixKey *key) {
  if (key)
    printBin(key->keydata, key->keylen);
}

/*
 * creates a new key and returns it
 */
SimRadixKey *sim_radix_key_new(void) {
  SimRadixKey *key = (SimRadixKey *) malloc(sizeof(SimRadixKey));
  if (!key) {
		ossim_debug ( "ERROR with malloc!\n");
    return NULL;
  }
  memset(key, 0, sizeof(SimRadixKey));
  return key;
}

/*
 * creates a new key, sets the data passed and returns it
 */
SimRadixKey *sim_radix_key_create(uint8_t *keydata, uint8_t keylen) {
  SimRadixKey *key = sim_radix_key_new();
  if (!key)
    return NULL;
  key->keydata = keydata;
  key->keylen = keylen;
  return key;
}

/*
 * duplicates a key and returns it
 */
inline SimRadixKey *sim_radix_dup_key(SimRadixKey *orig_key) {
  SimRadixKey *key = sim_radix_key_new(); 
  if (!key)
    return NULL;

  key->keylen = orig_key->keylen;
  if (!orig_key->keylen || !orig_key->keydata)
    return key;

  int i = 0;
  int limit = BITS_TO_BYTES(orig_key->keylen);

  key->keydata = (uint8_t *) malloc(sizeof(uint8_t) * limit);
  for (; i < limit; i++)
    key->keydata[i] = orig_key->keydata[i];

  return key;
}

/*
 * destroys a key
 */
void sim_radix_key_destroy(SimRadixKey *key) {
  if (key == NULL)
    return;
  if (key->keydata != NULL)
    free(key->keydata);
  free(key);
}

/*
 * creates a new node
 */
SimRadixNode *sim_radix_node_new(void) {
  SimRadixNode *node = (SimRadixNode *)malloc(sizeof(SimRadixNode));
  if (!node) {
		ossim_debug ( "ERROR with malloc!\n");
    return NULL;
  }
  memset(node, 0, sizeof(SimRadixNode));
  return node;
}

/*
 *  destroys a new node
 */
void sim_radix_node_destroy(SimRadix *radix, SimRadixNode *node) {
  if (!node)
    return;

  if (node->left != NULL)
    sim_radix_node_destroy(radix, node->left);
  if (node->right != NULL)
    sim_radix_node_destroy(radix, node->right);

  if (node->key != NULL)
    sim_radix_key_destroy(node->key);
  if (node->user_data != NULL && radix->ud_free != NULL)
    radix->ud_free(node->user_data);
  free(node);
}

/*
 * Helper function, prints user data recursively with indentation accumulated from the tree level
 */
void sim_radix_node_print_ud(SimRadix *radix, SimRadixNode *node, int level, int bitlen, uint8_t ud) {

  printf("\n");

  padding(level);
  if (node->key == NULL)
    return;

  printKey(node->key);
  printf("KLen: %d", bitlen + (int)node->key->keylen);
  if (node->user_data) printf("[Y]");
  else printf("[N]");

  if (ud) {
    printf(" UD: ");
    if (node->user_data)
      radix->ud_print(node->user_data);
    else {
      printf("Empty");
    }
  }

  if (node->left != NULL)
    sim_radix_node_print_ud(radix, node->left, level + 4, bitlen + node->key->keylen, ud);

  if (node->right != NULL)
    sim_radix_node_print_ud(radix, node->right, level + 4, bitlen + node->key->keylen, ud);
}

/*
 * Helper function, prints user data
 */
void sim_radix_print(SimRadix *radix, uint8_t ud) {
  int level = 1;

  if (radix == NULL || radix->root == NULL) {
    printf("Empty\n");
    return;
  }
    
  padding(level);
  printf("ROOT: ");

  if (ud) {
    if (radix->root->user_data != NULL)
      radix->ud_print(radix->root->user_data);
    else {
      printf("Empty");
    }
  }

  if (radix->root->left)
    sim_radix_node_print_ud(radix, radix->root->left, level + 4, radix->root->key->keylen, ud);

  if (radix->root->right)
    sim_radix_node_print_ud(radix, radix->root->right, level + 4, radix->root->key->keylen, ud);
  printf("\n");
}

/*
 * Creates a new radix tree, associating functions related to user_data entries, in case we need to use them
 */
SimRadix *sim_radix_new(void (*ud_free)(void *), void (*ud_print)(void *), void (*ud_update)(void*, void*), void *(*ud_clone)(void *ud)) {
  SimRadix *radix = (SimRadix *) malloc(sizeof(SimRadix));
  if (!radix) {
		ossim_debug ( "ERROR with malloc!\n");
    return NULL;
  }
  memset(radix, 0, sizeof(SimRadix));
  radix->ud_free = ud_free;
  radix->ud_print = ud_print;

  if (ud_update != NULL)
    radix->ud_update = ud_update;
  else
    radix->ud_update = *sim_radix_ud_update_generic;

  radix->ud_clone = ud_clone;
  return radix;
}

static void
sim_radix_ud_update_generic (void *old,
                             void *new_data)
{
  if (!old || !new_data)
    return;

  SimRadixNode *old_node = old;
  uint8_t *new_user_data = new_data;

  // Replace for static memory data
  old_node->user_data = new_user_data;
}


/*
 * Inserts a new user data associated to the key passed. The key is not used, so
 * developers are responsible to free that keys
 * Keys can be of "any size" (but this will be probable used for CIDR data keys(from 0 to 32 ~ 128 depending on IPv4 or IPv6 respectively))
 */
SimRadixNode *sim_radix_insert_keyval(SimRadix *radix, SimRadixKey *key, void *ud) {
  // If there's no root node, create an empty one.
  if (!radix->root) {
    radix->root = sim_radix_node_new();
    if (!radix->root) {
			ossim_debug ( "ERROR with malloc!\n");
      return NULL;
    }
    // The root node has ALWAYS a 0 keylen and keydata = NULL
    // user_data can be != NULL (as a generic user_data for any key
    // (equivalent to CIDR 0.0.0.0/0 in network numbers)
    radix->root->key = sim_radix_key_create(NULL, 0);
    if (!key || !key->keydata || key->keylen == 0) {
      if (radix->root->user_data)
        radix->ud_update(radix->root, ud);
      else
        radix->root->user_data = ud;
      return radix->root;
    }
  } 

  // Look the key goes to the root node
  if (key->keylen == 0) {
    if (radix->root->user_data == NULL) {
      radix->root->user_data = ud;
      return radix->root;
    } else {
      // else already use, so we free the old user_data if any and reassign it
      radix->ud_update(radix->root, ud);
      return radix->root;
    }
  }

  //sim_radix_print(radix, 1);

  // Else look the first bit of the key to see which path/pointer to follow
  // 0 == left ptr
  // 1 == right ptr
  SimRadixNode *iter = NULL;
  if ( GET_PATH(key->keydata[0]) == 0) {
    iter = radix->root->left;
  } else {
    iter = radix->root->right;
  }

  // store the reference to parent node just in case we need to update something
  SimRadixNode *prev_iter = radix->root;
  uint8_t cnt = 0;
  uint8_t keyiter = 0;

  // We are going to select each node as "iter", walking the tree node by node
  // checking each key data and bit length
  // If needed, we will be splitting the current "iter" key (and possibly the key we are inserting)
  // to update the paths to reach the keys
  while (cnt < key->keylen && iter != NULL) {
    keyiter = 0;
    //ossim_debug ( "Cur keylen %"PRIu8"\n", iter->key->keylen);
    for (; keyiter < iter->key->keylen && cnt < key->keylen; keyiter++, cnt++) {
			//ossim_debug ( "%"PRIu8" : %"PRIu8"\n", GET_BIT(iter->key->keydata[keyiter / 8], keyiter % 8), GET_BIT(key->keydata[cnt / 8], cnt % 8));
			//ossim_debug ( "%c : %c\n", iter->key->keydata[keyiter / 8], key->keydata[cnt / 8]);

      if ( GET_BIT(iter->key->keydata[keyiter / 8], keyiter % 8) != GET_BIT(key->keydata[cnt / 8], cnt % 8) )  {
        // Case 1: key chunks are different. We need to split here the iter node into common prefix (of the iter and the new key) and different suffix.
        // We also need to splitt the current key to substract the common prefix and insert it after the old iter (after the common prefix)
        // Something like:
        // In radix, iter points to key:               [110100111111] -> old_user_data
        // Trying to insert a key like:                [1101001100000] -> new_user_data
        // So After splitting the iter, we would have:
        // iter:                                   [11010011] -> becomes NULL
        // new sufix from key and new suffix from iter:     new_user_data <- [00000]  [1111] -> old_user_data
        // iter will point to [00000] from the left pointer,
        // and to [1111] from the right pointer.

//ossim_debug ( "Different bit at Cnt %"PRIu8" Keyiter:%"PRIu8", iterbit:%d keybit:%d\n", cnt, keyiter, GET_BIT(iter->key->keydata[keyiter / 8], keyiter % 8), GET_BIT(key->keydata[cnt / 8], cnt % 8));
// ossim_debug ( "iterlen:%d keylen:%d\n", iter->key->keylen, key->keylen);


        // Create the new sufix from the end of the iter keydata bits 
        uint8_t *keydata = NULL;
        int size = BITS_TO_BYTES(iter->key->keylen - keyiter);
        keydata = (uint8_t *) malloc (sizeof(uint8_t) * size);
        memset(keydata, 0, sizeof(uint8_t) * size);

        // Copy the new sufix bits starting from the keyiter bit offset
        // (where the difference begins)
        int i = keyiter;
        int ni = 0;
        for (; i < iter->key->keylen; i++, ni++) {
          if ( GET_BIT(iter->key->keydata[i / 8], i % 8) == 0x01) {
            SET_BIT_ARRAY(keydata, ni);
          }
        }
  
        // Create a key that will hold the new sufix
        SimRadixKey *k = sim_radix_key_create(keydata, iter->key->keylen - (keyiter));
        if ( !k) {
					ossim_debug ( "Failed to create key!\n");
          return NULL;
        }
  
        // Create a node that will hold the new sufix
        SimRadixNode *n = sim_radix_node_new();
        if ( !n) {
					ossim_debug ( "Failed to create node (splitting iter node)!\n");
          sim_radix_key_destroy (k);
          return NULL;
        }

        // Assign the key to the new node
        n->key = k;

        // Copy reference to user data and remove the old reference
        n->user_data = iter->user_data;
        iter->user_data = NULL;

        // Update pointers 
        n->left = iter->left;
        n->right = iter->right;
  
        if ( GET_BIT(keydata[0], 0) == 0) {
          iter->left = n;
          iter->right = NULL;
        } else {
          iter->right = n;
          iter->left = NULL;
        }

        // Update / cut the old key of the iter
        size = BITS_TO_BYTES(keyiter);
        keydata = (uint8_t *) malloc (sizeof(uint8_t) * size);
        memset(keydata, 0, sizeof(uint8_t) * size);

        // Copy keyiter bits of the old key starting from 0 offset
        i = 0;
        for (; i < keyiter; i++) {
          if ( GET_BIT(iter->key->keydata[i / 8], i % 8) == 0x01)
            SET_BIT_ARRAY(keydata, i);
        }

        // Update the len of the iter with keyiter
        iter->key->keylen = keyiter;

        // Free the old key and update the pointer to new keydata with the common prefix
        free(iter->key->keydata);
        iter->key->keydata = keydata;

        // We have splitted the iter into 2 chunks (common prefix and new sufix)
        // Now let's split the new key to get the unique sufix where the new user_data will be inserted

        size = BITS_TO_BYTES(key->keylen - cnt);
        keydata = (uint8_t *) malloc (sizeof(uint8_t) * size);
        memset(keydata, 0, sizeof(uint8_t) * size);
  
        // Copy the new sufix bits from index cnt (where the difference begins) to the end of the key
        i = cnt;
        ni = 0;
        for (; i < key->keylen; i++, ni++) {
          if ( GET_BIT(key->keydata[i / 8], i % 8) == 0x01)
            SET_BIT_ARRAY(keydata, ni);
        }

        // Create the key that will hold the new keydata sufix
        k = sim_radix_key_create(keydata, key->keylen - cnt);
        if ( !k) {
					ossim_debug ( "Failed to create key!\n");
          return NULL;
        }
  
        // Create the node that will hold the new key
        n = sim_radix_node_new();
        if ( !n) {
          sim_radix_key_destroy (k);
					ossim_debug ( "Failed to create node (splitting iter node)!\n");
          return NULL;
        }
        n->key = k;

        // Update the iter to point to this sufix aswell
        if ( GET_BIT(key->keydata[cnt / 8], cnt % 8) == 0) {
          iter->left = n;
        } else {
          iter->right = n;
        }
  
        // Set the user data
        n->user_data = ud;

        // And return the new node :D
        return n;
      } else {
// ossim_debug ( "Equal\n");
      }
    }

    // If we are here then we didn't insert it yet. That should be because of 3 possible reaseons:
    // 1. We are just on the node of the same key
    // 2. We don't need to split any of the already inserted nodes, just need to append
    // 3. We just need to append the new key sufix because we consumed the already inserted paths/keys
    // 4. We need to walk more nodes

    if (keyiter >= iter->key->keylen && cnt >= key->keylen) {
      // Both conditions mean that we are exactly on a node with the same key
      if ( !iter->user_data) {
        iter->user_data = ud;
      } else {
        radix->ud_update(iter, ud);
      }
      return iter;

    } else if (keyiter >= iter->key->keylen && cnt < key->keylen) {
      // If we matched all the iter key, look if we have to jump to the next iter (otherwise break the loop to append the next key chunk)
      prev_iter = iter;
      if ( GET_BIT(key->keydata[cnt / 8], cnt % 8)  == 0x00) 
      {
        if (iter->left)
          iter = iter->left;
        else
// ossim_debug ("NO PATH TO LEFT, Need to append!\n");
          break;
      }
      else if ( GET_BIT(key->keydata[cnt / 8], cnt % 8)  == 0x01)
      {
        if (iter->right)
          iter = iter->right;
        else
// ossim_debug ("NO PATH TO RIGHT, Need to append!\n");
          break;
      }
      // Continue walking the iter
      continue;

    } else if (cnt >= key->keylen && keyiter < iter->key->keylen) {
      // If we matched all the key but not the iter at all, AND we didn't find any different bit, then
      // we are inserting a subkey (ex: a subnet) So we need to split the node up to common prefix and difference
      // Look at the remaining bits in the iter key and split it

      uint8_t *keydata = NULL;
      uint8_t size = BITS_TO_BYTES(iter->key->keylen - keyiter);
      keydata = (uint8_t *) malloc (sizeof(uint8_t) * size);
      memset(keydata, 0, sizeof(uint8_t) * size);

      // Copy the new sufix
      int i = keyiter;
      int ni = 0;
      for (; i < iter->key->keylen; i++, ni++) {
        if ( GET_BIT(iter->key->keydata[i / 8], i % 8) == 0x01)
          SET_BIT_ARRAY(keydata, ni);
      }

      // Create key for the new sufix
      SimRadixKey *k = sim_radix_key_create(keydata, iter->key->keylen - keyiter);
      if ( !k) {
        ossim_debug ( "Failed to create key!\n");
        return NULL;
      }

      // Create node for the new key
      SimRadixNode *n = sim_radix_node_new();
      if ( !n) {
        sim_radix_key_destroy (k);
        ossim_debug ( "Failed to create node (splitting iter node)!\n");
        return NULL;
      }

      // Update pointers
      n->left = iter->left;
      n->right = iter->right;
      n->key = k;
      if (GET_PATH(keydata[0]) == 0) {
        iter->left = n;
        iter->right = NULL;
      } else {
        iter->right = n;
        iter->left = NULL;
      }

      // Copy reference to user data to the new sufix
      n->user_data = iter->user_data;
      // Set the new user data and return the new node :)
      iter->user_data = ud;

      // OK, now update the old iter key len
      // Update / cut the old key
      size = BITS_TO_BYTES(keyiter);
      keydata = (uint8_t *) malloc (sizeof(uint8_t) * size);
      memset(keydata, 0, sizeof(uint8_t) * size);

      // Copy keyiter bits of the old key
      i = 0;
      for (; i < keyiter; i++) {
        if ( GET_BIT(iter->key->keydata[i / 8], i % 8) == 0x01)
          SET_BIT_ARRAY(keydata, i);
      }

      // Free the old key and update the pointer to new keydata
      free(iter->key->keydata);
      iter->key->keydata = keydata;

      // Update len of the keyiter
      iter->key->keylen = keyiter;

      return n;
    } else {
// ossim_debug ( "We should not be here\n");
    }

    // Else try to jump to the next branch and continue, or break to append
    if ( GET_BIT(key->keydata[cnt / 8], cnt % 8) == 0)
      if (iter->left == NULL)
        break;
      else {
        prev_iter = iter;
        iter = iter->left;
      }
    else
      if (iter->right == NULL)
        break;
      else {
        prev_iter = iter;
        iter = iter->right;
      }
  }

  // If we reach this code, it means that we just need to append a new node without splitting anything
  if (cnt < key->keylen) {
    iter = prev_iter;
    if ( GET_BIT(key->keydata[cnt / 8], cnt % 8) == 0 && iter->left == NULL) {
  
      uint8_t size = BITS_TO_BYTES(key->keylen - cnt);
      uint8_t *keydata = (uint8_t *) malloc (sizeof(uint8_t) * size);

      memset(keydata, 0, sizeof(uint8_t) * size);

      // Copy the new sufix bits from index cnt (where the difference begins) to the end of the key
      int i = cnt;
      int ni = 0;
      for (; i < key->keylen; i++, ni++) {
        if ( GET_BIT(key->keydata[i / 8], i % 8) == 0x01)
          SET_BIT_ARRAY(keydata, ni);
      }

      SimRadixNode *node = sim_radix_node_new();
      if (!node) {
        ossim_debug ( "ERROR with malloc!\n");
        free (keydata);
        return NULL;
      }
  
      node->key = sim_radix_key_create(keydata, key->keylen - cnt);
      if (!node->key) {
        ossim_debug ( "ERROR with malloc!\n");
        free (keydata);
        return NULL;
      }
  
      node->user_data = ud;
      iter->left = node;
      return node;

    } else if ( GET_BIT(key->keydata[cnt / 8], cnt % 8) == 1 && iter->right == NULL) {
  
      uint8_t size = BITS_TO_BYTES(key->keylen - cnt);
      uint8_t *keydata = (uint8_t *) malloc (sizeof(uint8_t) * size);

      memset(keydata, 0, sizeof(uint8_t) * size);

      // Copy the new sufix bits from index cnt (where the difference begins) to the end of the key
      int i = cnt;
      int ni = 0;
      for (; i < key->keylen; i++, ni++) {
        if ( GET_BIT(key->keydata[i / 8], i % 8) == 0x01)
          SET_BIT_ARRAY(keydata, ni);
      }
  
      SimRadixNode *node = sim_radix_node_new();
      if (!node) {
        ossim_debug ( "ERROR with malloc!\n");
        free (keydata);
        return NULL;
      }
  
      node->key = sim_radix_key_create(keydata, key->keylen - cnt);
      if (!node->key) {
        ossim_debug ( "ERROR with malloc!\n");
        free (keydata);
        return NULL;
      }
  
      node->user_data = ud;
      iter->right = node;
      return node;

    }
		ossim_debug ( "Error on radix (1)\n");
  } else {
		ossim_debug ( "Error on radix (2)\n");
  }
	ossim_debug ( "Error on radix (3)\n");

  return NULL;
}

/*
 * destroys the radix tree nodes and its linked keys and user datas as well as the radix object
 */
void sim_radix_destroy(SimRadix *radix) {
  if (!radix)
    return;
  sim_radix_node_destroy(radix, radix->root);
  free(radix);
}

/*
 * search a key recursively and returns the user data. Do not use this function directly
 */
static inline void* sim_radix_search_ud(SimRadixNode *node, SimRadixKey *key, int index, int exact) {
  int i = 0;

  if (node == NULL)
    return NULL;

  /* If the key is bigger than the one which we are searching then stop */
  if ((index + node->key->keylen) > key->keylen)
    return NULL;

  for (; i < node->key->keylen && index < key->keylen; i++, index++) {
    if (GET_BIT(node->key->keydata[i / 8], i % 8) != GET_BIT(key->keydata[index / 8], index % 8))
      return NULL;
  }

  // Only returns the node when all the key is checked
  if ((index == key->keylen) && (i == node->key->keylen))
    return node->user_data;

  void *ret = NULL;
  if (GET_BIT(key->keydata[index / 8], index % 8) == 0)
    ret = sim_radix_search_ud(node->left, key, index, exact);
  else
    ret = sim_radix_search_ud(node->right, key, index, exact);

  // If we are returning NULL from recursion and we are searching 
  // the value of a key or it's parent prefix, return the first not empty user_data
  // otherwise return NULL

  // With this we are creating the ability of "inheritance" between common prefixes and keys
  // For example for inserting a net /16 with user_data that will be returned in case we
  // search a host of that net that's not present as a complete key.
  if (exact == 0 && ret == NULL && node->user_data != NULL)
    return node->user_data;
  
  return ret;
}

/*
 * Search a key and return the associated user data.
 * If the key is not found, it will return the user data associated
 * to the longest common prefix of the key present in the tree
 */
void* sim_radix_search_best_key(SimRadix *radix, SimRadixKey *key) {
  if (radix->root == NULL)
    return NULL;

  if (key->keylen == 0)
    return radix->root->user_data;

  void *ret = NULL;
  if (GET_PATH(key->keydata[0]) == 0)
    ret = sim_radix_search_ud(radix->root->left, key, 0, 0);
  else
    ret = sim_radix_search_ud(radix->root->right, key, 0, 0);
  
  if (ret == NULL && radix->root->user_data != NULL)
    return radix->root->user_data;
  
  return ret;
}

/*
 * Search a key and return the associated user data.
 * If the key is not found, it will return NULL
 */
void* sim_radix_search_exact_key(SimRadix *radix, SimRadixKey *key) {

  if (radix->root == NULL)
    return NULL;

  if (key->keylen == 0)
    return radix->root->user_data;
  if (GET_PATH(key->keydata[0]) == 0)
    return sim_radix_search_ud(radix->root->left, key, 0, 1);
  else
    return sim_radix_search_ud(radix->root->right, key, 0, 1);
}

/*
 *  Pre-order iteration in SimRadix tree, executing fptr function for each node
 */
void sim_radix_preorder_foreach_node (SimRadixNode *node, void *ud, void (*fptr)(SimRadixNode *, void *))
{
  if(node)
  {
    void (*fp)(SimRadixNode *, void *) = fptr;
    fp(node, ud);
    sim_radix_preorder_foreach_node(node->left, ud, fptr);
    sim_radix_preorder_foreach_node(node->right, ud, fptr);
  }
}

void sim_radix_foreach_node (SimRadix *tree, void *ud, void (*fptr)(SimRadixNode *, void *))
{
  g_return_if_fail (tree);
  g_return_if_fail (fptr);

  sim_radix_preorder_foreach_node (tree->root, ud, fptr);
}

/*
 *  Pre-order iteration in SimRadix tree, executing fptr function for each node while FALSE is returned
 */
gboolean sim_radix_preorder_foreach_node_check (SimRadixNode *node, void *ud, gboolean (*fptr)(SimRadixNode *, void *))
{
  if(node)
  {
    gboolean (*fp)(SimRadixNode *, void *) = fptr;
    if(fp(node, ud))
      return TRUE;
    
    if(sim_radix_preorder_foreach_node_check(node->left, ud, fptr))
      return TRUE;
    if(sim_radix_preorder_foreach_node_check(node->right, ud, fptr))
      return TRUE;
  }
  return FALSE;
}

gboolean sim_radix_foreach_node_check (SimRadix *tree, void *ud, gboolean (*fptr)(SimRadixNode *, void *))
{
  g_return_val_if_fail (tree, FALSE);
  g_return_val_if_fail (fptr, FALSE);

  return sim_radix_preorder_foreach_node_check (tree->root, ud, fptr);
}


/*
 *  Pre-order iteration in SimRadix tree, counting the number of nodes
 */
gint sim_radix_preorder_tree_size(SimRadixNode *node)
{
  gint nodes;
  
  if(node)
  {
    nodes = 1;
    nodes += sim_radix_preorder_tree_size(node->left);
    nodes += sim_radix_preorder_tree_size(node->right);
    return nodes;
  }
  else
    return 0;
}


gint sim_radix_tree_size(SimRadix *tree)
{
  g_return_val_if_fail (tree, -1);

  return sim_radix_preorder_tree_size(tree->root);  
}

/*
 * SimRadix tree clonation
 */
void sim_radix_preorder_clone(SimRadix *new_tree, SimRadixNode *node, gboolean is_root, SimRadixNode **origin)
{
  SimRadixNode *curr_node;

  if(is_root)
  {
    sim_radix_insert_keyval(new_tree, NULL, NULL);
    curr_node = new_tree->root;
  }
  else if(node)
  {
    SimRadixNode  *new_node = sim_radix_node_new();
    new_node->key = sim_radix_dup_key(node->key);
    if(node->user_data)
      new_node->user_data = new_tree->ud_clone(node->user_data);

    if(origin)
      *origin = new_node;
    curr_node = new_node;
  }
  else
    return;

  sim_radix_preorder_clone(new_tree, node->left, FALSE, &curr_node->left);
  sim_radix_preorder_clone(new_tree, node->right, FALSE, &curr_node->right);
}

SimRadix *
sim_radix_clone (SimRadix *tree)
{
  SimRadix *new_tree = sim_radix_new (tree->ud_free, tree->ud_print, tree->ud_update, tree->ud_clone);
  if (new_tree && tree->root)
    sim_radix_preorder_clone (new_tree, tree->root, TRUE, NULL);

  return new_tree;
}

#ifdef USE_UNITTESTS

/*************************************************************
***************      Unit tests      ***************
**************************************************************/

void update           (void *old,
                       void *new_data);
void pr               (void *ud);
int  sim_radix_test1  (void);
int  sim_radix_test2  (void);
int  sim_radix_test3  (void);
int  sim_radix_test4  (void);
int  sim_radix_test10 (void);
int  sim_radix_test11 (void);
int  sim_radix_test12 (void);
int  sim_radix_test13 (void);

/*
 * Helper functions for testing
 */
void update(void *old, void *new_data) {
  if (!old || !new_data)
    return;
  SimRadixNode *old_node = old;
  uint8_t *new_user_data = new_data;

  // Now we should do whatever kind of update we need
  // In this example we will replace the previous userdata, just like a dictionary..
  if (old_node->user_data)
    free(old_node->user_data);
  old_node->user_data = new_user_data;
}

/*
 * Helper functions for testing
 */
void pr(void *ud) {
  if (!ud)
    return;

  char *u = (char *) ud;
  printf("%s", u);
}

/* Unit tests begin here */

// Test Macros 1
int sim_radix_test1() {
  // Testing GET_BIT macro
  uint8_t byte = 0xAA;
  if (GET_BIT(byte, 0) != 1)
    return 0;
  if (GET_BIT(byte, 1) != 0)
    return 0;
  if (GET_BIT(byte, 2) != 1)
    return 0;
  if (GET_BIT(byte, 3) != 0)
    return 0;
  if (GET_BIT(byte, 4) != 1)
    return 0;
  if (GET_BIT(byte, 5) != 0)
    return 0;
  if (GET_BIT(byte, 6) != 1)
    return 0;
  if (GET_BIT(byte, 7) != 0)
    return 0;

  return 1;
}

// Test Macros 2
int sim_radix_test2() {
  // Testing BITS_TO_BYTES macro
  if (BITS_TO_BYTES(1)  != 1) return 0;
  if (BITS_TO_BYTES(8)  != 1) return 0;
  if (BITS_TO_BYTES(9)  != 2) return 0;
  if (BITS_TO_BYTES(16) != 2) return 0;
  if (BITS_TO_BYTES(17) != 3) return 0;
  if (BITS_TO_BYTES(24) != 3) return 0;
  if (BITS_TO_BYTES(25) != 4) return 0;
  if (BITS_TO_BYTES(32) != 4) return 0;

  return 1;
}

// Test Macros 3
int sim_radix_test3() {
  // Testing BITS_TO_BYTES macro
  if (BITS_TO_BYTES(1)  != 1) return 0;
  if (BITS_TO_BYTES(8)  != 1) return 0;
  if (BITS_TO_BYTES(9)  != 2) return 0;
  if (BITS_TO_BYTES(16) != 2) return 0;
  if (BITS_TO_BYTES(17) != 3) return 0;
  if (BITS_TO_BYTES(24) != 3) return 0;
  if (BITS_TO_BYTES(25) != 4) return 0;
  if (BITS_TO_BYTES(32) != 4) return 0;

  return 1;
}

// Test Macros 4
int sim_radix_test4() {
  uint8_t byte = 0;
  uint8_t result = 128;
  int index = 0;

  byte = 0x00;
  SET_BIT(byte, 0);
  printBin(&byte, 8);
  if (byte != result) return 0;
  result /= 2;
  index++;

  byte = 0x00;
  SET_BIT(byte, 1);
  printBin(&byte, 8);
  if (byte != result) return 0;
  result /= 2;
  index++;

  byte = 0x00;
  SET_BIT(byte, 2);
  printBin(&byte, 8);
  if (byte != result) return 0;
  result /= 2;
  index++;

  byte = 0x00;
  SET_BIT(byte, 3);
  printBin(&byte, 8);
  if (byte != result) return 0;
  result /= 2;
  index++;


  byte = 0x00;
  SET_BIT(byte, 4);
  printBin(&byte, 8);
  if (byte != result) return 0;
  result /= 2;
  index++;

  byte = 0x00;
  SET_BIT(byte, 5);
  printBin(&byte, 8);
  if (byte != result) return 0;
  result /= 2;
  index++;

  byte = 0x00;
  SET_BIT(byte, 6);
  printBin(&byte, 8);
  if (byte != result) return 0;
  result /= 2;
  index++;

  byte = 0x00;
  SET_BIT(byte, 7);
  printBin(&byte, 8);
  if (byte != result) return 0;
  result /= 2;
  index++;
  printf("\n");


  // ---- Round 2
  result = 128;
  index = 0;
  // ----
  byte = 0xAA;
  SET_BIT(byte, 0);
  printBin(&byte, 8);
  if (byte != (result | 0xAA)) return 0;
  result /= 2;
  index++;

  byte = 0xAA;
  SET_BIT(byte, 1);
  printBin(&byte, 8);
  if (byte != (result | 0xAA)) return 0;
  result /= 2;
  index++;

  byte = 0xAA;
  SET_BIT(byte, 2);
  printBin(&byte, 8);
  if (byte != (result | 0xAA)) return 0;
  result /= 2;
  index++;

  byte = 0xAA;
  SET_BIT(byte, 3);
  printBin(&byte, 8);
  if (byte != (result | 0xAA)) return 0;
  result /= 2;
  index++;


  byte = 0xAA;
  SET_BIT(byte, 4);
  printBin(&byte, 8);
  if (byte != (result | 0xAA)) return 0;
  result /= 2;
  index++;

  byte = 0xAA;
  SET_BIT(byte, 5);
  printBin(&byte, 8);
  if (byte != (result | 0xAA)) return 0;
  result /= 2;
  index++;

  byte = 0xAA;
  SET_BIT(byte, 6);
  printBin(&byte, 8);
  if (byte != (result | 0xAA)) return 0;
  result /= 2;
  index++;

  byte = 0xAA;
  SET_BIT(byte, 7);
  printBin(&byte, 8);
  if (byte != (result | 0xAA)) return 0;
  result /= 2;
  index++;
  printf("\n");


  // ---- Round 3
  result = 128;
  index = 0;
  // ----
  byte = 0x55;
  SET_BIT(byte, 0);
  printBin(&byte, 8);
  if (byte != (result | 0x55)) return 0;
  result /= 2;
  index++;

  byte = 0x55;
  SET_BIT(byte, 1);
  printBin(&byte, 8);
  if (byte != (result | 0x55)) return 0;
  result /= 2;
  index++;

  byte = 0x55;
  SET_BIT(byte, 2);
  printBin(&byte, 8);
  if (byte != (result | 0x55)) return 0;
  result /= 2;
  index++;

  byte = 0x55;
  SET_BIT(byte, 3);
  printBin(&byte, 8);
  if (byte != (result | 0x55)) return 0;
  result /= 2;
  index++;


  byte = 0x55;
  SET_BIT(byte, 4);
  printBin(&byte, 8);
  if (byte != (result | 0x55)) return 0;
  result /= 2;
  index++;

  byte = 0x55;
  SET_BIT(byte, 5);
  printBin(&byte, 8);
  if (byte != (result | 0x55)) return 0;
  result /= 2;
  index++;

  byte = 0x55;
  SET_BIT(byte, 6);
  printBin(&byte, 8);
  if (byte != (result | 0x55)) return 0;
  result /= 2;
  index++;

  byte = 0x55;
  SET_BIT(byte, 7);
  printBin(&byte, 8);
  if (byte != (result | 0x55)) return 0;
  result /= 2;
  index++;
  printf("\n");


  return 1;
}

/*
 * Test insertions
 */
int sim_radix_test10() {
  int result = 1;
  SimRadix *radix = sim_radix_new(free, pr, update, NULL);

  uint8_t *keydata1 = (uint8_t *)malloc(sizeof(uint8_t) * 1);
  uint8_t *keydata2 = (uint8_t *)malloc(sizeof(uint8_t) * 2);
  uint8_t *keydata3 = (uint8_t *)malloc(sizeof(uint8_t) * 3);
  uint8_t *keydata4 = (uint8_t *)malloc(sizeof(uint8_t) * 4);
  uint8_t *keydata5 = (uint8_t *)malloc(sizeof(uint8_t) * 4);
  uint8_t *keydata6 = (uint8_t *)malloc(sizeof(uint8_t) * 4);
  uint8_t *keydata7 = (uint8_t *)malloc(sizeof(uint8_t) * 4);

  memcpy(keydata1, "\xFF\x00\x00\x00", 1);
  memcpy(keydata2, "\xFF\xA5\x00\x00", 2);
  memcpy(keydata3, "\xFF\xA5\x2F\x00", 3);
  memcpy(keydata4, "\xFF\xA5\x2F\xFA", 4);

  memcpy(keydata5, "\xF4\xA5\x2F\xFA", 4);
  memcpy(keydata6, "\xFF\xA5\x5F\xFA", 4);
  memcpy(keydata7, "\xFF\xA5\x2F\xFB", 4);

  SimRadixKey *key1 = sim_radix_key_create(keydata1, 1 * 8);
  SimRadixKey *key2 = sim_radix_key_create(keydata2, 2 * 8);
  SimRadixKey *key3 = sim_radix_key_create(keydata3, 3 * 8);
  SimRadixKey *key4 = sim_radix_key_create(keydata4, 4 * 8);
  SimRadixKey *key5 = sim_radix_key_create(keydata5, 4 * 8);
  SimRadixKey *key6 = sim_radix_key_create(keydata6, 4 * 8);
  SimRadixKey *key7 = sim_radix_key_create(keydata7, 4 * 8);

  sim_radix_insert_keyval(radix, NULL, (void *)strdup("Generic user data for all the tree!"));
  // Test that any key will return the generic user data of the tree using best_match
  uint8_t *result1 = sim_radix_search_best_key(radix, key1);
  if (memcmp(result1, "Generic user data for all the tree!", strlen("Generic user data for all the tree!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key2), "Generic user data for all the tree!", strlen("Generic user data for all the tree!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key3), "Generic user data for all the tree!", strlen("Generic user data for all the tree!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key4), "Generic user data for all the tree!", strlen("Generic user data for all the tree!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key5), "Generic user data for all the tree!", strlen("Generic user data for all the tree!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key6), "Generic user data for all the tree!", strlen("Generic user data for all the tree!")) != 0) result = 0;


  sim_radix_insert_keyval(radix, key1, (void *)strdup("key 1 data!"));
  // Test that any key will return the generic user data of the tree using best_match
  if (memcmp(sim_radix_search_best_key(radix, key1), "key 1 data!", strlen("key 1 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key2), "key 1 data!", strlen("key 1 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key3), "key 1 data!", strlen("key 1 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key4), "key 1 data!", strlen("key 1 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key5), "Generic user data for all the tree!", strlen("Generic user data for all the tree!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key6), "key 1 data!", strlen("key 1 data!")) != 0) result = 0;

  sim_radix_insert_keyval(radix, key2, (void *)strdup("key 2 data!"));
  // Test that any key will return the generic user data of the tree using best_match
  if (memcmp(sim_radix_search_best_key(radix, key1), "key 1 data!", strlen("key 1 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key2), "key 2 data!", strlen("key 2 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key3), "key 2 data!", strlen("key 2 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key4), "key 2 data!", strlen("key 2 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key5), "Generic user data for all the tree!", strlen("Generic user data for all the tree!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key6), "key 2 data!", strlen("key 2 data!")) != 0) result = 0;

  sim_radix_insert_keyval(radix, key3, (void *)strdup("key 3 data!"));
  // Test that any key will return the generic user data of the tree using best_match
  if (memcmp(sim_radix_search_best_key(radix, key1), "key 1 data!", strlen("key 1 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key2), "key 2 data!", strlen("key 2 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key3), "key 3 data!", strlen("key 3 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key4), "key 3 data!", strlen("key 3 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key5), "Generic user data for all the tree!", strlen("Generic user data for all the tree!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key6), "key 2 data!", strlen("key 2 data!")) != 0) result = 0;

  sim_radix_insert_keyval(radix, key4, (void *)strdup("key 4 data!"));
  // Test that any key will return the generic user data of the tree using best_match
  if (memcmp(sim_radix_search_best_key(radix, key1), "key 1 data!", strlen("key 1 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key2), "key 2 data!", strlen("key 2 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key3), "key 3 data!", strlen("key 3 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key4), "key 4 data!", strlen("key 4 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key5), "Generic user data for all the tree!", strlen("Generic user data for all the tree!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key6), "key 2 data!", strlen("key 2 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key7), "key 3 data!", strlen("key 3 data!")) != 0) result = 0;

  if (result == 0) {
    printf("Printing the current tree. Something went wrong.\n");
  }

  sim_radix_print(radix, 1);

  // Free the tree
  sim_radix_destroy(radix);

  // Free the keys used to perform the insertions
  sim_radix_key_destroy(key1);
  sim_radix_key_destroy(key2);
  sim_radix_key_destroy(key3);
  sim_radix_key_destroy(key4);
  sim_radix_key_destroy(key5);
  sim_radix_key_destroy(key6);
  sim_radix_key_destroy(key7);

  return result;
}

/*
 * Generic test with key strings
 */
int sim_radix_test11() {
  int result = 1;
  SimRadix *radix = sim_radix_new(free, pr, update, NULL);
  SimRadixKey *key1 = sim_radix_key_create ((uint8_t *)strdup ("Hola"), 4 * 8);
  SimRadixKey *key2 = sim_radix_key_create ((uint8_t *)strdup ("Hoca"), 4 * 8);
  SimRadixKey *key3 = sim_radix_key_create ((uint8_t *)strdup ("Hocb"), 4 * 8);
  SimRadixKey *key4 = sim_radix_key_create ((uint8_t *)strdup ("Hpcb"), 2 * 8);
  SimRadixKey *key5 = sim_radix_key_create ((uint8_t *)strdup ("Hppp"), 4 * 8);

  sim_radix_insert_keyval(radix, key4, (void *)strdup("key 4 data!"));
  sim_radix_insert_keyval(radix, key3, (void *)strdup("key 3 data!"));
  sim_radix_insert_keyval(radix, key2, (void *)strdup("key 2 data!"));
  sim_radix_insert_keyval(radix, key1, (void *)strdup("key 1 data!"));
  // Key 5 will not be loaded, so later we will search for it's best match
  // (closest prefix with user data, that's key4 or at least should be key4)

  if (strcmp(sim_radix_search_exact_key(radix, key1), "key 1 data!") != 0) result = 0;
  if (strcmp(sim_radix_search_exact_key(radix, key2), "key 2 data!") != 0) result = 0;
  if (strcmp(sim_radix_search_exact_key(radix, key3), "key 3 data!") != 0) result = 0;
  if (strcmp(sim_radix_search_exact_key(radix, key4), "key 4 data!") != 0) result = 0;

  // Best search for key 5 (we know that it's not in the tree)
  if (strcmp(sim_radix_search_best_key(radix, key5), "key 4 data!") != 0) result = 0;

  if (result == 0) {
    printf("Printing the current tree. Something went wrong.\n");
  }
  sim_radix_print(radix, 1);

  // Free the tree
  sim_radix_destroy(radix);

  // Free the keys used to perform the insertions
  sim_radix_key_destroy(key1);
  sim_radix_key_destroy(key2);
  sim_radix_key_destroy(key3);
  sim_radix_key_destroy(key4);
  sim_radix_key_destroy(key5);

  return result;
}

/*
 * Test best match functionality
 */
int sim_radix_test12() {
  int result = 1;
  SimRadix *radix = sim_radix_new(free, pr, update, NULL);

  uint8_t *keydata1 = (uint8_t *)malloc(sizeof(uint8_t) * 1);
  uint8_t *keydata2 = (uint8_t *)malloc(sizeof(uint8_t) * 2);
  uint8_t *keydata3 = (uint8_t *)malloc(sizeof(uint8_t) * 3);
  uint8_t *keydata4 = (uint8_t *)malloc(sizeof(uint8_t) * 4);
  uint8_t *keydata5 = (uint8_t *)malloc(sizeof(uint8_t) * 4);
  uint8_t *keydata6 = (uint8_t *)malloc(sizeof(uint8_t) * 4);
  uint8_t *keydata7 = (uint8_t *)malloc(sizeof(uint8_t) * 4);

  memcpy(keydata1, "\xFF\x00\x00\x00", 1);
  memcpy(keydata2, "\xFF\xA5\x00\x00", 2);
  memcpy(keydata3, "\xFF\xA5\x2F\x00", 3);
  memcpy(keydata4, "\xFF\xA5\x2F\xFA", 4);

  memcpy(keydata5, "\xF4\xA5\x2F\xFA", 4);
  memcpy(keydata6, "\xFF\xA5\x5F\xFA", 4);
  memcpy(keydata7, "\xFF\xA5\x2F\xFB", 4);

  SimRadixKey *key1 = sim_radix_key_create(keydata1, 6);
  SimRadixKey *key2 = sim_radix_key_create(keydata2, 14);
  SimRadixKey *key3 = sim_radix_key_create(keydata3, 22);
  SimRadixKey *key4 = sim_radix_key_create(keydata4, 28);
  SimRadixKey *key5 = sim_radix_key_create(keydata5, 30);
  SimRadixKey *key6 = sim_radix_key_create(keydata6, 31);
  SimRadixKey *key7 = sim_radix_key_create(keydata7, 32);

  sim_radix_insert_keyval(radix, NULL, (void *)strdup("Generic user data for all the tree!"));
  // Test that any key will return the generic user data of the tree using best_match
  uint8_t *result1 = sim_radix_search_best_key(radix, key1);
  if (memcmp(result1, "Generic user data for all the tree!", strlen("Generic user data for all the tree!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key2), "Generic user data for all the tree!", strlen("Generic user data for all the tree!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key3), "Generic user data for all the tree!", strlen("Generic user data for all the tree!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key4), "Generic user data for all the tree!", strlen("Generic user data for all the tree!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key5), "Generic user data for all the tree!", strlen("Generic user data for all the tree!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key6), "Generic user data for all the tree!", strlen("Generic user data for all the tree!")) != 0) result = 0;

  if (result == 0)
    return 0;

  sim_radix_insert_keyval(radix, key1, (void *)strdup("key 1 data!"));
  // Test that any key will return the generic user data of the tree using best_match
  if (memcmp(sim_radix_search_best_key(radix, key1), "key 1 data!", strlen("key 1 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key2), "key 1 data!", strlen("key 1 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key3), "key 1 data!", strlen("key 1 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key4), "key 1 data!", strlen("key 1 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key5), "Generic user data for all the tree!", strlen("Generic user data for all the tree!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key6), "key 1 data!", strlen("key 1 data!")) != 0) result = 0;

  if (result == 0)
    return 0;

  sim_radix_insert_keyval(radix, key2, (void *)strdup("key 2 data!"));
  // Test that any key will return the generic user data of the tree using best_match
  if (memcmp(sim_radix_search_best_key(radix, key1), "key 1 data!", strlen("key 1 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key2), "key 2 data!", strlen("key 2 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key3), "key 2 data!", strlen("key 2 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key4), "key 2 data!", strlen("key 2 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key5), "Generic user data for all the tree!", strlen("Generic user data for all the tree!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key6), "key 2 data!", strlen("key 2 data!")) != 0) result = 0;

  if (result == 0)
    return 0;

  sim_radix_insert_keyval(radix, key3, (void *)strdup("key 3 data!"));
  // Test that any key will return the generic user data of the tree using best_match
  if (memcmp(sim_radix_search_best_key(radix, key1), "key 1 data!", strlen("key 1 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key2), "key 2 data!", strlen("key 2 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key3), "key 3 data!", strlen("key 3 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key4), "key 3 data!", strlen("key 3 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key5), "Generic user data for all the tree!", strlen("Generic user data for all the tree!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key6), "key 2 data!", strlen("key 2 data!")) != 0) result = 0;

  if (result == 0)
    return 0;

  sim_radix_insert_keyval(radix, key4, (void *)strdup("key 4 data!"));
  // Test that any key will return the generic user data of the tree using best_match
  if (memcmp(sim_radix_search_best_key(radix, key1), "key 1 data!", strlen("key 1 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key2), "key 2 data!", strlen("key 2 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key3), "key 3 data!", strlen("key 3 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key4), "key 4 data!", strlen("key 4 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key5), "Generic user data for all the tree!", strlen("Generic user data for all the tree!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key6), "key 2 data!", strlen("key 2 data!")) != 0) result = 0;
  if (memcmp(sim_radix_search_best_key(radix, key7), "key 4 data!", strlen("key 4 data!")) != 0) result = 0;

  if (result == 0)
    return 0;

  if (result == 0) {
    printf("Printing the current tree. Something went wrong.\n");
  }

  sim_radix_print(radix, 1);

  // Free the tree
  sim_radix_destroy(radix);

  // Free the keys used to perform the insertions
  sim_radix_key_destroy(key1);
  sim_radix_key_destroy(key2);
  sim_radix_key_destroy(key3);
  sim_radix_key_destroy(key4);
  sim_radix_key_destroy(key5);
  sim_radix_key_destroy(key6);
  sim_radix_key_destroy(key7);

  return result;
}

int sim_radix_test13() {

  gint result = 1;
  SimRadix *radix = sim_radix_new(free, pr, update, NULL);

  uint8_t *keydata1 = (uint8_t *)malloc(sizeof(uint8_t) * 1);
  uint8_t *keydata2 = (uint8_t *)malloc(sizeof(uint8_t) * 2);
  uint8_t *keydata3 = (uint8_t *)malloc(sizeof(uint8_t) * 3);
  uint8_t *keydata = (uint8_t *)malloc(sizeof(uint8_t) * 4);

  memcpy(keydata1, "\x0A\x00\x00\x00", 1);
  memcpy(keydata2, "\xC0\xA8\x00\x00", 2);
  memcpy(keydata3, "\xAC\x10\x00\x00", 3);
  memcpy(keydata, "\xC0\xA8\x02\x1A", 4);

  SimRadixKey *key1 = sim_radix_key_create(keydata1, 8);
  SimRadixKey *key2 = sim_radix_key_create(keydata2, 16);
  SimRadixKey *key3 = sim_radix_key_create(keydata3, 12);
  SimRadixKey *key = sim_radix_key_create(keydata, 32);

  sim_radix_insert_keyval(radix, NULL, (void *)strdup("Generic user data for all the tree!"));
  sim_radix_insert_keyval(radix, key1, (void *)strdup("10.0.0.0/8"));
  sim_radix_insert_keyval(radix, key2, (void *)strdup("192.168.0.0/16"));
  sim_radix_insert_keyval(radix, key3, (void *)strdup("172.16.0.0/12"));


  gchar *rc = (gchar*) sim_radix_search_best_key(radix, key);
  if(rc)
    printf("\nBest key found: %s\n", rc);
  else
    printf("\nNo key found!\n");


  sim_radix_print(radix, 1);

  // Free the tree
  sim_radix_destroy(radix);

  // Free the keys used to perform the insertions
  sim_radix_key_destroy(key1);
  sim_radix_key_destroy(key2);
  sim_radix_key_destroy(key3);
  sim_radix_key_destroy(key);

  return result;
}


void sim_radix_register_tests(SimUnittesting *engine) {
  sim_unittesting_append(engine, "sim_radix_test1 - Macros", sim_radix_test1, 1);
  sim_unittesting_append(engine, "sim_radix_test2 - Macros", sim_radix_test2, 1);
  sim_unittesting_append(engine, "sim_radix_test3 - Macros", sim_radix_test3, 1);
  sim_unittesting_append(engine, "sim_radix_test4 - Macros", sim_radix_test4, 1);
  sim_unittesting_append(engine, "sim_radix_test10 - insertion/search", sim_radix_test10, 1);
  sim_unittesting_append(engine, "sim_radix_test11 - insertion/search", sim_radix_test11, 1);
  sim_unittesting_append(engine, "sim_radix_test12 - insertion/search", sim_radix_test12, 1);
  sim_unittesting_append(engine, "sim_radix_test13 - insertion/search", sim_radix_test13, 1);

}
#endif

// vim: set tabstop=2:
