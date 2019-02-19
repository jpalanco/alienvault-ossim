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


#define _XOPEN_SOURCE 1
#include <errno.h>
#include <bson.h>
#include <stddef.h>
#include <string.h>
#include <time.h>
#include <arpa/inet.h>
#include "sim-parser.h"
#include "sim-command.h"
#include "sim-enums.h"
#include "sim-parser-private.h"
#include "sim-uuid.h"

static gboolean
sim_parse_inet_from_str (bson_iter_t * piter, SimCommand * cmd_base, unsigned long offset);


static const SimCommandFields idm_event_fields[]=
{
  {"ip",                BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.idm_event.ip),          sizeof (SimInet *),   FALSE,   sim_parse_inet_from_str},
  {"logon",             BSON_TYPE_BOOL,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.idm_event.is_logon),    sizeof (gboolean),    FALSE,   NULL},
  {"logoff",            BSON_TYPE_BOOL,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.idm_event.is_logoff),   sizeof (gboolean),    FALSE,   NULL},
  {"username",          BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.idm_event.username),    sizeof (gchar *),     FALSE,   NULL},
  {"hostname",          BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.idm_event.hostname),    sizeof (gchar *),     FALSE,   NULL},
  {"domain",            BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.idm_event.domain),      sizeof (gchar *),     FALSE,   NULL},
  {"mac",               BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.idm_event.mac),         sizeof (gchar *),     FALSE,   NULL},
  {"os",                BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.idm_event.os),          sizeof (gchar *),     FALSE,   NULL},
  {"cpu",               BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.idm_event.cpu),         sizeof (gchar *),     FALSE,   NULL},
  {"memory",            BSON_TYPE_INT32,  BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.idm_event.memory),      sizeof (guint),       FALSE,   NULL}, 
  {"video",             BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.idm_event.video),       sizeof (gchar *),     FALSE,   NULL},
  {"service",           BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.idm_event.service),     sizeof (gchar *),     FALSE,   NULL},
  {"software",          BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.idm_event.software),    sizeof (gchar *),     FALSE,   NULL},
  {"state",             BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.idm_event.state),       sizeof (gchar *),     FALSE,   NULL},
  {"inventory_source",  BSON_TYPE_INT32,  BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.idm_event.inventory_source), sizeof (gint),      FALSE,   NULL},
  {"ctx",               BSON_TYPE_BINARY, BSON_SUBTYPE_UUID,   offsetof (SimCommand, context_id),                sizeof (SimUuid *), FALSE,   sim_parse_gen_uuid_from_bin},
  {"host",              BSON_TYPE_BINARY, BSON_SUBTYPE_UUID,   offsetof (SimCommand, data.idm_event.host_id),    sizeof (SimUuid *), FALSE,   sim_parse_gen_uuid_from_bin},
  {"rule",              BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.idm_event.rule),       sizeof (gchar *),     FALSE,   NULL},
};

static gboolean
sim_parse_inet_from_str (bson_iter_t * piter, SimCommand * cmd_base, unsigned long offset)
{
   guint lendata;
     uint8_t * cmd = (uint8_t *)cmd_base;
   SimInet * inet;
   gboolean result = FALSE;
   const gchar * utf8data;
   utf8data = bson_iter_utf8 (piter, &lendata);
   if ((inet =  sim_inet_new_from_string (utf8data)) != NULL)
   {
    *(SimInet **)(cmd + offset) = inet;
    result = TRUE;
   }
   return result;   
}



SimCommand *
sim_parser_bson_idm_event   (SimParser * self, bson_iter_t * piter)
{
  g_return_val_if_fail (self != NULL, NULL);
  g_return_val_if_fail (piter != NULL, NULL);
  g_return_val_if_fail (SIM_IS_PARSER (self), NULL);
  SimCommand * cmd;
  gboolean error = FALSE;
  if ((cmd = sim_command_new ()) == NULL)
    return NULL;
  cmd->type = SIM_COMMAND_TYPE_IDM_EVENT;
  memset (&cmd->data.idm_event, 0, sizeof (cmd->data.idm_event));
  while (bson_iter_next (piter) && !error)
  {
    const char *key = bson_iter_key (piter);
    const SimCommandFields *idm_event_info;
    if ((idm_event_info = g_hash_table_lookup (self->priv->idm_event_fields, (gpointer)key)) != NULL)
    {
      /* First check type */
      if (bson_iter_type (piter) == idm_event_info->type)
      {
        /* We have the offset from the table */
        SimCopyData data_info;  
        data_info.size = idm_event_info->size;
        data_info.type = idm_event_info->type;
        data_info.offset = idm_event_info->offset; 
        data_info.subtype = idm_event_info->subtype;
        data_info.pfilter = idm_event_info->pfilter;
        error = !sim_parser_copy_data_generic (piter, cmd, &data_info);
      }
      else
      {
        g_message ("Bad BSON type key '%s'", key);
        error = TRUE;
      }
    }
    else
    {
      g_message ("BAD BSON key '%s'", key);
      error = TRUE;  
    }
  }
  if (error)
  {
    g_object_unref (cmd);
    cmd = NULL;
  }
  return cmd;
}
void
sim_parser_idm_event_init (SimParser * self)
{
  g_return_if_fail (self != NULL);
  g_return_if_fail (SIM_IS_PARSER (self));
  guint i;
  self->priv->idm_event_fields = g_hash_table_new (g_str_hash, g_str_equal);
  for (i = 0; i < G_N_ELEMENTS (idm_event_fields); i++)
    g_hash_table_insert (self->priv->idm_event_fields, (gpointer)idm_event_fields[i].key, (gpointer)&idm_event_fields[i]);
    
}

void
sim_parser_idm_event_finalize (SimParser * self)
{
  g_return_if_fail (self != NULL);
  g_return_if_fail (SIM_IS_PARSER (self));
  g_hash_table_destroy (self->priv->idm_event_fields);
}


