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

/* 
  Only 4. I prefer using a list, don't want hash 
  overhead in this case 
*/
static const SimCommandFields plugin_fields[]=
{
  {"id",        BSON_TYPE_INT32,  BSON_SUBTYPE_BINARY, offsetof (SimCommand, id),  sizeof (guint),       FALSE,  NULL},
  {"plugin_id", BSON_TYPE_INT32,  BSON_SUBTYPE_BINARY, 0,  sizeof (guint),       FALSE,  NULL},

};
struct plugin_data
{
  SimCommandType type;
  guint offset;
};

static SimCommand *
sim_parse_bson_plugin_generic (SimParser * self, bson_iter_t * piter, gpointer data)
{
  g_return_val_if_fail (self != NULL && piter != NULL, NULL);
  g_return_val_if_fail (SIM_IS_PARSER (self), NULL);
  SimCommand * cmd;
  struct plugin_data * p_data = (struct plugin_data *)data;
  gboolean error = FALSE;
  if ((cmd = sim_command_new ()) == NULL)
    return NULL;
  cmd->type = p_data->type;
  while (bson_iter_next (piter) && !error)
  {
    const char * key = bson_iter_key (piter);
    SimCommandFields *ptr;
    SimCopyData data_info;

    if ((ptr = g_hash_table_lookup (self->priv->plugin_fields, key)) != NULL)
    {
      data_info.size = ptr->size;
      data_info.type = ptr->type;
      data_info.offset = p_data->offset; 
      data_info.subtype = ptr->subtype;
      data_info.pfilter = NULL;
      if (!sim_parser_copy_data_generic (piter, cmd, &data_info))
      {
        g_message ("Can't copy data from BSON");
        error = TRUE;
      }
    }
    else
    {
      g_message ("Bad key '%s' in BSON", key);
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

/* 
  Quick hack. I don't want a table for each entry that use the same plugin_id and there aren't more
  fields. I can code a generic code, but 
    1/ I don't have enought time
    2/ The protocol doesn't need more info
*/
SimCommand *
sim_parser_bson_plugin_process_started (SimParser * self, bson_iter_t * piter)
{
  struct plugin_data data;
  data.offset = offsetof (SimCommand, data.plugin_state_started.plugin_id);
  data.type = SIM_COMMAND_TYPE_PLUGIN_STATE_STARTED;
  return sim_parse_bson_plugin_generic (self, piter, (gpointer)&data);
}

SimCommand *
sim_parser_bson_plugin_process_stopped (SimParser * self, bson_iter_t * piter)
{
  struct plugin_data data;
  data.offset = offsetof (SimCommand, data.plugin_state_stopped.plugin_id);
  data.type = SIM_COMMAND_TYPE_PLUGIN_STATE_STOPPED;
  return sim_parse_bson_plugin_generic (self, piter, (gpointer)&data);
}

SimCommand *
sim_parser_bson_plugin_process_unknown (SimParser * self, bson_iter_t * piter)
{
  struct plugin_data data;
  data.offset = offsetof (SimCommand, data.plugin_state_unknown.plugin_id);
  data.type = SIM_COMMAND_TYPE_PLUGIN_STATE_UNKNOWN;
  return sim_parse_bson_plugin_generic (self, piter, (gpointer)&data);
}
SimCommand *
sim_parser_bson_plugin_enable (SimParser * self, bson_iter_t * piter)
{
  struct plugin_data data;
  data.offset = offsetof (SimCommand, data.plugin_enabled.plugin_id);
  data.type = SIM_COMMAND_TYPE_PLUGIN_ENABLED;
  return sim_parse_bson_plugin_generic (self, piter, (gpointer)&data);
}

SimCommand *
sim_parser_bson_plugin_disable (SimParser * self, bson_iter_t * piter)
{
  struct plugin_data data;
  data.offset = offsetof (SimCommand, data.plugin_disabled.plugin_id);
  data.type = SIM_COMMAND_TYPE_PLUGIN_DISABLED;
  return sim_parse_bson_plugin_generic (self, piter, (gpointer)&data);
}

void
sim_parser_plugin_init(SimParser * self)
{
  g_return_if_fail (self != NULL);
  g_return_if_fail (SIM_IS_PARSER (self));
  guint i;
  self->priv->plugin_fields = g_hash_table_new (g_str_hash, g_str_equal);
  for (i = 0; i < G_N_ELEMENTS (plugin_fields); i++)
  {
    g_hash_table_insert (self->priv->plugin_fields,(gpointer) plugin_fields[i].key, (gpointer) &plugin_fields[i]);
  }


}
void
sim_parser_plugin_finalize (SimParser * self)
{
  g_return_if_fail (self != NULL);
  g_return_if_fail (SIM_IS_PARSER (self));
  g_hash_table_destroy (self->priv->plugin_fields);
}

