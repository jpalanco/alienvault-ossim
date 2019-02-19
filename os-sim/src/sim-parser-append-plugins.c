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


#include <errno.h>
#include <bson.h>

#include "sim-parser.h"
#include "sim-command.h"
#include "sim-enums.h"
#include "sim-parser-private.h"
static const SimCommandFields
session_append_plugin_fields[]=
{
  {"id",        BSON_TYPE_INT32,  BSON_SUBTYPE_BINARY, offsetof (SimCommand, id),                                    sizeof (guint),       FALSE,  NULL},
  {"plugin_id", BSON_TYPE_INT32,  BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.session_append_plugin.id),         sizeof (guint),       FALSE,  NULL},
  {"enabled",   BSON_TYPE_BOOL,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.session_append_plugin.enabled),    sizeof (gboolean),    FALSE,  NULL},
  {"state",     BSON_TYPE_INT32,  BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.session_append_plugin.state),      sizeof (gint),        FALSE,  NULL},
};

SimCommand *
sim_parser_bson_session_append_plugin   (SimParser * self, bson_iter_t * piter)
{
  g_return_val_if_fail (self != NULL, NULL);
  g_return_val_if_fail (piter != NULL, NULL);
  g_return_val_if_fail (SIM_IS_PARSER (self), NULL);
  SimCommand * cmd;
  gboolean error = FALSE;
  if ((cmd = sim_command_new ()) == NULL)
    return NULL;
  cmd->type = SIM_COMMAND_TYPE_SESSION_APPEND_PLUGIN;
  while (bson_iter_next (piter) && !error)
  {
    const char *key = bson_iter_key (piter);
    const SimCommandFields *append_info;
    if ((append_info = g_hash_table_lookup (self->priv->session_append_plugin_fields, (gpointer)key)) != NULL)
    {
      /* First check type */
      if (bson_iter_type (piter) == append_info->type)
      {
        /* We have the offset from the table */
        SimCopyData data_info;  
        data_info.size = append_info->size;
        data_info.type = append_info->type;
        data_info.offset = append_info->offset; 
        data_info.subtype = append_info->subtype;
        data_info.pfilter = NULL;
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
sim_parser_session_append_plugin_init (SimParser * self)
{
  g_return_if_fail (self != NULL);
  g_return_if_fail (SIM_IS_PARSER (self));
  guint i;
  self->priv->session_append_plugin_fields = g_hash_table_new (g_str_hash, g_str_equal);
  for (i = 0; i < G_N_ELEMENTS (session_append_plugin_fields); i++)
    g_hash_table_insert (self->priv->session_append_plugin_fields, (gpointer)session_append_plugin_fields[i].key, (gpointer)&session_append_plugin_fields[i]);
    
}

void
sim_parser_session_append_plugin_finalize (SimParser * self)
{
  g_return_if_fail (self != NULL);
  g_return_if_fail (SIM_IS_PARSER (self));
  g_hash_table_destroy (self->priv->session_append_plugin_fields);
}
