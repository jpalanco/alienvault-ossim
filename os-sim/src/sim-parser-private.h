/*
  License:

  Copyright (c) 2003-2006 ossim.net
  Copyright (c) 2007-2015 AlienVault
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


/* WARNING WARNING WARNING */
/* Use only AT sim-parser-*.c This is A PRIVATE AND INTERNAL USE for class SimParser and friends
*/
#ifndef _SIM_PARSER_PRIVATE_H 
#define _SIM_PARSER_PRIVATE_H 1
#include <glib.h>
#include <bson.h>
#include "sim-parser.h"
#include "sim-command.h"

typedef struct _SimCommandFields{
  const char *    key;
  bson_type_t     type;
  bson_subtype_t  subtype;
  unsigned long   offset;
  gsize           size;
  gboolean        mandatory;
  gboolean        (*pfilter)(bson_iter_t * piter, SimCommand * cmd, unsigned long offset); 
}SimCommandFields;

struct _SimParserPrivate
{
  GHashTable *connect_fields;
  gboolean connect_required[5];
  GHashTable * event_fields;
  GHashTable * event_additional_fields;
  GHashTable * main_parser;
  GHashTable * plugin_fields;
  GHashTable * session_append_plugin_fields;
  GHashTable * idm_event_fields;
};
typedef struct _copy_data{
  guint offset;
  bson_type_t type;
  bson_subtype_t subtype;
  gsize size;
  gboolean        (*pfilter)(bson_iter_t * piter, SimCommand * cmd, unsigned long offset);
} SimCopyData;


void            sim_parser_connect_init                 (SimParser * self);
void            sim_parser_connect_finalize             (SimParser * self);
void            sim_parser_event_init                   (SimParser * self);
void            sim_parser_event_finalize               (SimParser * self);
void            sim_parser_plugin_init                  (SimParser * self);
void            sim_parser_plugin_finalize              (SimParser * self);

void            sim_parser_session_append_plugin_init   (SimParser * self);
void            sim_parser_session_append_plugin_finalize (SimParser * self);
void            sim_parser_idm_event_init               (SimParser * self);
void            sim_parser_idm_event_finalize           (SimParser * self);

SimCommand *    sim_parser_bson_idm_event               (SimParser * self,
                                                         bson_iter_t * piter);


SimCommand *    sim_parser_bson_connect                 (SimParser * self,
                                                         bson_iter_t *piter);

SimCommand *    sim_parser_bson_event                   (SimParser * self,
                                                         bson_iter_t * piter);
SimCommand *    sim_parser_bson_pong                    (SimParser * self,
                                                         bson_iter_t * piter);
SimCommand *    sim_parser_bson_plugin_process_started  (SimParser * self,
                                                         bson_iter_t * piter);

SimCommand *    sim_parser_bson_plugin_process_stopped  (SimParser * self,
                                                         bson_iter_t * piter);

SimCommand *    sim_parser_bson_plugin_process_unknown  (SimParser * self,
                                                         bson_iter_t * piter);

SimCommand *    sim_parser_bson_plugin_enable           (SimParser * self, 
                                                         bson_iter_t * piter);

SimCommand *    sim_parser_bson_plugin_disable          (SimParser * self, 
                                                         bson_iter_t * piter);

SimCommand *    sim_parser_bson_agent_date              (SimParser * self,
                                                         bson_iter_t * piter);


gboolean        sim_parser_copy_data_generic            (bson_iter_t * piter,
                                                         SimCommand * cmd_base,
                                                         SimCopyData * ptr_data);

gboolean        sim_parse_gen_uuid_from_bin             (bson_iter_t * piter,
                                                         SimCommand * cmd_base,
                                                         unsigned long offset);

SimCommand *    sim_parser_bson_session_append_plugin   (SimParser * self,
                                                         bson_iter_t * piter);






#ifdef USE_UNITTESTS
void            sim_parser_register_connect_tests       (SimUnittesting *);
void            sim_parser_register_event_tests         (SimUnittesting *);
#endif



#endif 
