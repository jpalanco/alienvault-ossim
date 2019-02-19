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
#include <glib.h>

#include "sim-parser.h"
#include "sim-command.h"
#include "sim-enums.h"
#include "sim-parser-private.h"

static struct _main_parser
{
  const gchar * mainkey;
  SimCommand * (*pfParse)(SimParser *, bson_iter_t *); 
} main_parser[]={
  {"event",  sim_parser_bson_event },
  {"connect", sim_parser_bson_connect},
  {"pong", sim_parser_bson_pong},
  {"plugin-process-started", sim_parser_bson_plugin_process_started},
  {"plugin-process-stopped", sim_parser_bson_plugin_process_stopped},
  {"plugin-process-unknown", sim_parser_bson_plugin_process_unknown},
  {"plugin-enable", sim_parser_bson_plugin_enable},
  {"plugin-disable", sim_parser_bson_plugin_disable},
  {"agent-date", sim_parser_bson_agent_date},
  {"session-append-plugin", sim_parser_bson_session_append_plugin},
  {"idm-event", sim_parser_bson_idm_event}
};




SIM_DEFINE_TYPE (SimParser, sim_parser, G_TYPE_OBJECT, NULL)
#define SIM_PARSER_GET_PRIVATE(self) (G_TYPE_INSTANCE_GET_PRIVATE ((self), SIM_TYPE_PARSER, SimParserPrivate))
#if 0
static void *
bson_calloc(size_t count, size_t size)
{
  return g_malloc (count * size);
}
#endif
const bson_mem_vtable_t vtable = {malloc, calloc, realloc, free,{0,0,0,0}};

static void
sim_parser_class_init (SimParserClass *klass)
{
  GObjectClass *selfclass = G_OBJECT_CLASS (klass);

  g_type_class_add_private (klass, sizeof (SimParserPrivate));
  parent_class = g_type_class_ref (G_TYPE_OBJECT);
  /* Init the bson memory routines */
  bson_mem_set_vtable (&vtable);
  selfclass->finalize = sim_parser_finalize;
}

/**
 * sim_parser_instance_init:
 * @self: a #SimParser
 *
 * This function initialice a instance of SimParser
 */
static void
sim_parser_instance_init (SimParser *self)
{
  guint i;
  self->priv = SIM_PARSER_GET_PRIVATE (self);
  self->priv->main_parser = g_hash_table_new (g_str_hash, g_str_equal);
  for (i = 0; i < G_N_ELEMENTS (main_parser); i++)
    g_hash_table_insert (self->priv->main_parser, (gpointer)main_parser[i].mainkey, (gpointer)&main_parser[i]);
  sim_parser_connect_init (self);
  sim_parser_event_init (self);
  sim_parser_plugin_init (self);
  sim_parser_session_append_plugin_init (self);
  sim_parser_idm_event_init (self);
}

/**
 * sim_parser_finalize:
 * @self: a #SimParser
 *
 * This function finalize a instance of SimParser
 */
static void
sim_parser_finalize (GObject * self)
{
  SimParserPrivate *priv = SIM_PARSER_GET_PRIVATE (self);
  sim_parser_connect_finalize ((SimParser * )self);
  sim_parser_event_finalize ((SimParser * )self);
  sim_parser_plugin_finalize ((SimParser * )self);
  sim_parser_session_append_plugin_finalize ((SimParser *)self);
  sim_parser_idm_event_finalize ((SimParser *)self);
  g_hash_table_destroy (priv->main_parser);
  G_OBJECT_CLASS (parent_class)->finalize (self);
}

/**
 * sim_parser_new
 * @self a #SimParser
 */

SimParser *
sim_parser_new (void)
{
  SimParser * parser;
  parser = SIM_PARSER (g_object_new (SIM_TYPE_PARSER, NULL));
  return parser;
}

static gboolean
sim_parser_filter (gpointer key, gpointer value, gpointer user_data)
{
  gchar **list = (gchar **)user_data;
  (void)value;
  while (*list)
  {
    if (strcmp (*list, (gchar*)key) == 0)
      return FALSE;
    list++;
  }
  return TRUE;
}

void
sim_parser_reset_filter (SimParser * self)
{
  guint i;
  for (i = 0; i < G_N_ELEMENTS (main_parser); i++)
    g_hash_table_insert (self->priv->main_parser, (gpointer)main_parser[i].mainkey, (gpointer)&main_parser[i]);
}

/**
 * Add a filter list to the main parser
 * This function is not thread safe, I assume that you don't
 * change the parser list on fly
*/
void
sim_parser_set_filter (SimParser * self, gchar **list)
{
  g_return_if_fail (self != NULL);
  g_return_if_fail (SIM_IS_PARSER (self));
  g_return_if_fail (list != NULL);
  g_hash_table_foreach_remove (self->priv->main_parser, sim_parser_filter, (gpointer)list);
}


SimCommand *
sim_parser_bson (SimParser *self, const guint8 *bsondata, gsize bsonsize)
{
  g_return_val_if_fail (SIM_IS_PARSER (self), NULL);
  g_return_val_if_fail (bsondata != NULL, NULL);
  SimCommand *cmd = NULL;
  size_t err_offset;
  bson_t bson;
  bson_iter_t iter;
  //bson_iter_t key_iter;
  if (bson_init_static (&bson, bsondata, bsonsize))
  {
    if (bson_validate (&bson, BSON_VALIDATE_NONE, &err_offset))
    {
      bson_iter_init (&iter, &bson); 
      while (bson_iter_next (&iter))
      {
        struct _main_parser *p;
        if ((p = g_hash_table_lookup (self->priv->main_parser, (gpointer)bson_iter_key (&iter))) != NULL)
        {
          /* Make a iterator for the lower doc */
          if (BSON_ITER_HOLDS_DOCUMENT(&iter))
          {
            /* Get the document, generate a iterator over it */
            bson_t b_document;
            const uint8_t *document_data;
            uint32_t document_len;
            bson_iter_t document_iter;
            bson_iter_document (&iter, &document_len, &document_data);
            bson_init_static (&b_document, document_data, document_len);
            bson_iter_init (&document_iter, &b_document);
            if ((cmd = p->pfParse (self, &document_iter)) == NULL)
              g_message("key: '%s' not supported in BSON message", bson_iter_key (&iter));
            bson_destroy (&b_document);
          }
          else
          {
            g_message ("Bad BSON structure in key %s", bson_iter_key (&iter));
          }
        }
        else
        {
          g_message ("main parser discard key: %s", bson_iter_key (&iter));
        }
        
      }
      if (!cmd)
      {
        size_t len;
        char *str;
        str = bson_as_json(&bson, &len);
        g_message ("Debug: BSON message %s:" , str);
        bson_free (str);
      }
    }
    else
    {
      g_message ("Bad BSON message");
    }
    bson_destroy (&bson);
  }
  else
  {
    g_message ("Bad BSON message");
  }
  return cmd;
}

gboolean
sim_parse_gen_uuid_from_bin (bson_iter_t * piter, SimCommand * cmd_base, unsigned long offset)
{
  bson_subtype_t    subtype;
  uint32_t          lendata;
  const uint8_t           *bindata;
  SimUuid *uuid;
  uint8_t       * cmd = (uint8_t *)cmd_base;
 
  bson_iter_binary (piter, &subtype, &lendata, &bindata);
  if (( uuid =  sim_uuid_new_from_bin (bindata)) != NULL)
  {
    * (SimUuid **)(cmd + offset) = uuid;
    return TRUE;
  } 
  return FALSE; 
}


gboolean
sim_parser_copy_data_generic (bson_iter_t * piter, SimCommand *cmd_base, SimCopyData * ptr_data)
{
  g_return_val_if_fail (piter != NULL, FALSE);
  g_return_val_if_fail (cmd_base != NULL, FALSE);
  g_return_val_if_fail (ptr_data != NULL, FALSE);
 
  bson_subtype_t subtype;
  gboolean result = FALSE;
  const char * utf8data;
  gchar *buffer;
  guint32 lendata;
  const uint8_t  *bindata;
  uint8_t   * u8buffer;
  bson_type_t type =  bson_iter_type (piter);
  uint8_t * cmd = (uint8_t *)cmd_base;
  switch (type)
  {
    case BSON_TYPE_UTF8:
      /*
        XXX Check that bson_iter_utf8 return a string finished with \0
      */
      /* Safety belt I */
      if (ptr_data->pfilter == NULL)
      {
        g_return_val_if_fail (ptr_data->size == sizeof (gchar *), FALSE);
        utf8data = bson_iter_utf8 (piter, &lendata);
        buffer = g_malloc (lendata + 1);
        memmove (buffer, utf8data, lendata + 1);
        /* Safety belt */
        buffer[lendata] = '\0';
        * (gchar **)(cmd + ptr_data->offset) = buffer;
        result = TRUE;
      }
      else
      {
        result = ptr_data->pfilter (piter, cmd_base, ptr_data->offset);
      }
      break;
    case BSON_TYPE_INT32:
      if (ptr_data->pfilter == NULL)
      {
        g_return_val_if_fail (ptr_data->size == sizeof (guint32), FALSE);
        * (guint32 *)(cmd + ptr_data->offset) = bson_iter_int32 (piter);
        result = TRUE;
      }
      else
      {
        result = ptr_data->pfilter (piter, cmd_base, ptr_data->offset);
      }
      break;
    case BSON_TYPE_INT64:
      g_return_val_if_fail (ptr_data->size == sizeof (guint64), FALSE);
      * (guint64 *)(cmd + ptr_data->offset) = bson_iter_int64 (piter);
      result = TRUE;
      break;
    case BSON_TYPE_DOUBLE:
      if (ptr_data->pfilter == NULL)
      {
        g_return_val_if_fail (ptr_data->size == sizeof (double), FALSE);
        * (double *)(cmd + ptr_data->offset) = bson_iter_double (piter);
        result = TRUE;
      }
      else
      {
        result = ptr_data->pfilter (piter, cmd_base, ptr_data->offset);
      }
      break;
    case BSON_TYPE_BINARY:
      /* We can have filters only here */
      if (ptr_data->pfilter == NULL)
      {
        bson_iter_binary (piter, &subtype, &lendata, &bindata);
        g_return_val_if_fail (subtype == ptr_data->subtype, FALSE);
        u8buffer = g_malloc (lendata);
        memmove (u8buffer, bindata, lendata);
        *(uint8_t **)(cmd +  ptr_data->offset) = u8buffer;
        result = TRUE; 
      }
      else
      {
        result = ptr_data->pfilter (piter, cmd_base, ptr_data->offset);
      }
      break;
    case BSON_TYPE_BOOL:
      g_return_val_if_fail (ptr_data->size == sizeof (gboolean), FALSE);
      if (ptr_data->pfilter == NULL)
      {
        if (bson_iter_bool (piter))
          * (gboolean *) (cmd + ptr_data->offset) = TRUE;
        else
          * (gboolean *) (cmd + ptr_data->offset) = FALSE;
        result = TRUE;
      }
      else
      {
        result = ptr_data->pfilter (piter, cmd_base, ptr_data->offset);
      }
      break;
    case BSON_TYPE_DOCUMENT:
      g_return_val_if_fail (ptr_data->size == sizeof (bson_t *), FALSE);
      if (ptr_data->pfilter == NULL)
      {
        const uint8_t *subdoc;
        uint32_t doclen;
        bson_t *b;
        bson_iter_document (piter, &doclen, &subdoc);
        if ((b = bson_new_from_data (subdoc,doclen)) != NULL)
        {
          *(bson_t **)(cmd + ptr_data->offset) = b;
          result = TRUE;
        }
        else
          result = FALSE;
        
      }
      else
        result = ptr_data->pfilter (piter, cmd_base, ptr_data->offset);
      break;
    default:
      g_message ("Unsupported BSON type = %d in message", type);
  }
  if (!result)
  {
    g_message ("Can't copy data from BSON");
  }
  return result;
  
}
#ifdef USE_UNITTESTS
static gboolean
sim_parser_test1 (void)
{
  SimParser *parser;
  if ((parser = sim_parser_new ()) == NULL)
    return FALSE;
  g_object_unref (parser);
  return TRUE;
}
void sim_parser_register_tests (SimUnittesting *engine)
{
  sim_unittesting_append(engine, "SimParse create", sim_parser_test1, TRUE);
  sim_parser_register_connect_tests (engine);
  sim_parser_register_event_tests (engine);
}
#endif 


