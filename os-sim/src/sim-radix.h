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

#ifndef __SIM_RADIX_H__
#define __SIM_RADIX_H__

#include <inttypes.h>

#include "sim-unittesting.h"

// Helper macros
#define BITS_TO_BYTES(bits)           (((bits) % 8 == 0)? ((bits) / 8) : ((bits) / 8) + 1)
#define GET_PATH(byte)                (((byte) >> 7) & 0x01)
#define SET_BIT_ARRAY(byte, bit)      (byte[bit / 8] |= (0x01 << (7 - (bit % 8))));
#define SET_BIT(byte, bit)            (byte |= (0x01 << (7 - (bit % 8))));
// GET_BIT index is reversed here. bit 0 points to the highest order bit (7 the lowest)
// so the bit 0 of a byte with value 128 is 1 
#define GET_BIT(byte,bit)             ((byte >> (7 - ((bit) % 8)) ) & 0x01)

// Key structure
typedef struct _SimRadixKey {
    uint8_t *keydata;
    uint8_t keylen;
} SimRadixKey;

// Node structure
typedef struct _SimRadixNode {
    SimRadixKey *key;
    struct _SimRadixNode *left;
    struct _SimRadixNode *right;
    void *user_data;
} SimRadixNode;

// Radix tree structure
typedef struct _SimRadix {
    SimRadixNode *root;
    void (*ud_free)(void *);
    void (*ud_print)(void *);
    void (*ud_update)(void *prev_data, void *new_data);
    void *(*ud_clone)(void *ud);
} SimRadix;

SimRadixKey *sim_radix_key_new(void);
SimRadixNode *sim_radix_node_new(void);
SimRadixKey *sim_radix_key_create(uint8_t *, uint8_t);
SimRadixKey *sim_radix_dup_key (SimRadixKey *);
void sim_radix_key_destroy(SimRadixKey *);

SimRadixNode *sim_radix_node_new();
void sim_radix_node_destroy(SimRadix *, SimRadixNode *);

SimRadix *sim_radix_new(void (*ud_free)(void *), void (*ud_print)(void*), void (*ud_update)(void*, void*), void *(*ud_clone)(void *ud));
SimRadixNode *sim_radix_insert_keyval(SimRadix *, SimRadixKey *, void *);
void* sim_radix_search_best_key(SimRadix *, SimRadixKey *);
void* sim_radix_search_exact_key(SimRadix *, SimRadixKey *);
void sim_radix_destroy(SimRadix *);
void sim_radix_node_print_ud(SimRadix *radix, SimRadixNode *node, int level, int bitlen, uint8_t ud);
void sim_radix_print(SimRadix *radix, uint8_t ud); 

gint sim_radix_tree_size(SimRadix *);
void sim_radix_foreach_node (SimRadix *, void *, void (*)(SimRadixNode *, void *));
gboolean sim_radix_foreach_node_check (SimRadix *, void *, gboolean (*)(SimRadixNode *, void *));
SimRadix *sim_radix_clone(SimRadix *);

void sim_radix_print (SimRadix *radix, uint8_t ud);
void sim_radix_preorder_foreach_node (SimRadixNode *, void *, void (*fptr)(SimRadixNode *, void *));
gboolean sim_radix_preorder_foreach_node_check (SimRadixNode *, void *, gboolean (*fptr)(SimRadixNode *, void *));
gint sim_radix_preorder_tree_size(SimRadixNode *);
void sim_radix_preorder_clone(SimRadix *, SimRadixNode *, gboolean, SimRadixNode **);

// Helper function
void printKey(SimRadixKey *);

#ifdef USE_UNITTESTS
void sim_radix_register_tests(SimUnittesting *);
#endif

#endif
// vim: set tabstop=2:

