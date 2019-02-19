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

/* Static prototipes */
static gboolean sim_parse_gen_g_string_from_bin (bson_iter_t * piter, SimCommand * cmd, unsigned long offset);
static gboolean sim_parse_reliability (bson_iter_t * piter, SimCommand * cmd, unsigned long offset);
static gboolean sim_parse_validate_pulse (bson_iter_t * piter, SimCommand * cmd, unsigned long offset);

/***
  WARNING 
  I assume that sizeof(gint) == 4  :)
**/
static const SimCommandFields event_fields[]=
{
  {"type",                BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.type),            sizeof (gchar *),     TRUE,   NULL},
  {"event_id",            BSON_TYPE_BINARY, BSON_SUBTYPE_UUID,   offsetof (SimCommand, data.event.id),              sizeof (SimUuid *),   FALSE,  sim_parse_gen_uuid_from_bin},
  {"id",                  BSON_TYPE_INT32,  BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.id_transaction),  sizeof (guint),       FALSE,  NULL},
  {"ctx",                 BSON_TYPE_BINARY, BSON_SUBTYPE_UUID,   offsetof (SimCommand, context_id ),                sizeof (SimUuid *),   FALSE,  sim_parse_gen_uuid_from_bin},
  {"plugin_id",           BSON_TYPE_INT32,  BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.plugin_id),       sizeof (gint),        FALSE,  NULL},
  {"plugin_sid",          BSON_TYPE_INT32,  BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.plugin_sid),      sizeof (gint),        FALSE,  NULL},
  {"date",                BSON_TYPE_INT64,  BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.date),            sizeof (time_t),      FALSE,  NULL},
  {"fdate",               BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.date_str),        sizeof (gchar *),     FALSE,  NULL},
  {"tzone",               BSON_TYPE_DOUBLE, BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.tzone),           sizeof (gdouble),     FALSE,  NULL},
  {"sensor",              BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.sensor),          sizeof (gchar *),     FALSE,  NULL}, 
  {"sensor_id",           BSON_TYPE_BINARY, BSON_SUBTYPE_UUID,   offsetof (SimCommand, data.event.sensor_id),       sizeof (SimUuid *),   FALSE,  sim_parse_gen_uuid_from_bin},
  {"device",              BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.device),          sizeof (gchar *),     FALSE,  NULL},
  {"device_id",           BSON_TYPE_INT32,  BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.device_id),       sizeof (gint),        FALSE,  NULL},
/*{"servername",          sim_parser_event_servername,      FALSE, 13}, */
/* {"server",              sim_parser_event_server,          FALSE, 14}, */
  {"interface",           BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.interface),       sizeof (gchar *),     FALSE,  NULL}, 
  {"priority",            BSON_TYPE_INT32,  BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.priority),        sizeof (gint),        FALSE,  NULL},
  {"protocol",            BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.protocol),        sizeof (gchar *),     FALSE,  NULL},
  {"src_ip",              BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.src_ip),          sizeof (gchar *),     FALSE,  NULL},
  {"src_port",            BSON_TYPE_INT32,  BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.src_port),        sizeof (gint),        FALSE,  NULL},
  {"dst_ip",              BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.dst_ip),          sizeof (gchar *),     FALSE,  NULL},
  {"dst_port",            BSON_TYPE_INT32,  BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.dst_port),        sizeof (gint),        FALSE,  NULL},
  {"src_net",             BSON_TYPE_BINARY, BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.src_net),         sizeof (SimUuid *),     FALSE,  sim_parse_gen_uuid_from_bin},
  {"dst_net",             BSON_TYPE_BINARY, BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.dst_net),         sizeof (SimUuid *),     FALSE,  sim_parse_gen_uuid_from_bin},
  {"condition",           BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.condition),       sizeof (gchar *),     FALSE,  NULL}, 
  {"value",               BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.value),           sizeof (gchar *),     FALSE,  NULL},
  {"interval",            BSON_TYPE_INT32,  BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.interval),        sizeof (gint),        FALSE,  NULL},
/*{"backlog_type",        sim_parser_event_backlog_type,    FALSE, 27}, */
/*{"directive_id",        sim_parser_event_directive_id,    FALSE, 28}, */
  {"log",                 BSON_TYPE_BINARY, BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.log),             sizeof (gchar *),     FALSE, sim_parse_gen_g_string_from_bin},
  {"snort_sid",           BSON_TYPE_INT32,  BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.snort_sid),       sizeof (gint),        FALSE,  NULL},
  {"snort_cid",           BSON_TYPE_INT32,  BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.snort_cid),       sizeof (gint),        FALSE,  NULL},
  {"asset_src",           BSON_TYPE_INT32,  BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.asset_src),       sizeof (gint),        FALSE,  NULL},
  {"asset_dst",           BSON_TYPE_INT32,  BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.asset_dst),       sizeof (gint),        FALSE,  NULL},
  {"risk_a",              BSON_TYPE_DOUBLE, BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.risk_a),          sizeof (gdouble),     FALSE,  NULL},
  {"risk_c",              BSON_TYPE_DOUBLE, BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.risk_c),          sizeof (gdouble),     FALSE,  NULL},
  {"reliability",         BSON_TYPE_INT32,  BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.asset_dst),       sizeof (gint),        FALSE,  sim_parse_reliability},
  {"alarm",               BSON_TYPE_BOOL,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.alarm),           sizeof (gboolean),    FALSE,  NULL},
  {"filename",            BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.filename),        sizeof (gchar *),     FALSE,  NULL},        
  {"username",            BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.username),        sizeof (gchar *),     FALSE,  NULL},           
  {"password",            BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.password),        sizeof (gchar *),     FALSE,  NULL},            
  {"userdata1",           BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.userdata1),       sizeof (gchar *),     FALSE,  NULL},            
  {"userdata2",           BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.userdata2),       sizeof (gchar *),     FALSE,  NULL},            
  {"userdata3",           BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.userdata3),       sizeof (gchar *),     FALSE,  NULL},            
  {"userdata4",           BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.userdata4),       sizeof (gchar *),     FALSE,  NULL},            
  {"userdata5",           BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.userdata5),       sizeof (gchar *),     FALSE,  NULL},            
  {"userdata6",           BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.userdata6),       sizeof (gchar *),     FALSE,  NULL},            
  {"userdata7",           BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.userdata7),       sizeof (gchar *),     FALSE,  NULL},            
  {"userdata8",           BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.userdata8),       sizeof (gchar *),     FALSE,  NULL},       
  {"userdata9",           BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.userdata9),       sizeof (gchar *),     FALSE,  NULL},          
  {"src_id",              BSON_TYPE_BINARY, BSON_SUBTYPE_UUID,   offsetof (SimCommand, data.event.src_id),          sizeof (SimUuid *),   FALSE,  sim_parse_gen_uuid_from_bin},
  {"dst_id",              BSON_TYPE_BINARY, BSON_SUBTYPE_UUID,   offsetof (SimCommand, data.event.dst_id),          sizeof (SimUuid *),   FALSE,  sim_parse_gen_uuid_from_bin},
  {"src_username",        BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.src_username),    sizeof (gchar *),     FALSE,  NULL},  
  {"dst_username",        BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.dst_username),    sizeof (gchar *),     FALSE,  NULL},   
  {"src_hostname",        BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.src_hostname),    sizeof (gchar *),     FALSE,  NULL},   
  {"dst_hostname",        BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.dst_hostname),    sizeof (gchar *),     FALSE,  NULL},  
  {"src_mac",             BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.src_mac),         sizeof (gchar *),     FALSE,  NULL},   
  {"dst_mac",             BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.dst_mac),         sizeof (gchar *),     FALSE,  NULL},    
  {"rep_prio_src",        BSON_TYPE_INT32,  BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.rep_prio_src),    sizeof (gint),        FALSE,  NULL},
  {"rep_prio_dst",        BSON_TYPE_INT32,  BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.rep_prio_dst),    sizeof (gint),        FALSE,  NULL},
  {"rep_rel_src",         BSON_TYPE_INT32,  BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.rep_rel_src),     sizeof (gint),        FALSE,  NULL},
  {"rep_rel_dst",         BSON_TYPE_INT32,  BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.rep_rel_dst),     sizeof (gint),        FALSE,  NULL},
  {"rep_act_src",         BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.rep_act_src),     sizeof (gchar *),     FALSE,  NULL}, 
  {"rep_act_dst",         BSON_TYPE_UTF8,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.rep_act_dst),     sizeof (gchar *),     FALSE,  NULL}, 
  {"belongs_to_alarm",    BSON_TYPE_BOOL,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.belongs_to_alarm),sizeof (gboolean),    FALSE,  NULL},
  {"is_remote",           BSON_TYPE_BOOL,   BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.is_remote),       sizeof (gboolean),    FALSE,  NULL},
/*{"uuid",                sim_parser_event_uuid,            FALSE, 59}, */
/*{"uuid_backlog",        sim_parser_event_uuid_backlog,    FALSE, 60}, */
  {"backlog_id",          BSON_TYPE_BINARY, BSON_SUBTYPE_UUID,   offsetof (SimCommand, data.event.saqqara_backlog_id),sizeof (SimUuid *),   FALSE,  sim_parse_gen_uuid_from_bin},
  {"level",               BSON_TYPE_INT32,  BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.level),           sizeof (gint),        FALSE,  NULL},
  {"binary_data",         BSON_TYPE_UTF8, BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.binary_data),     sizeof (gchar *),     FALSE,  NULL},
  {"pulses",              BSON_TYPE_DOCUMENT, BSON_SUBTYPE_BINARY, offsetof (SimCommand, data.event.pulses),        sizeof (bson_t *),    FALSE, sim_parse_validate_pulse},
};

static gboolean
sim_parse_reliability (bson_iter_t * piter, SimCommand * cmd, unsigned long offset)
{
  /* This case is a PITA. I need to set to fields */
  (void) offset;
  cmd->data.event.reliability = bson_iter_int32 (piter);
  cmd->data.event.is_reliability_set = TRUE;
  return TRUE; 
}

static gboolean
sim_parse_gen_g_string_from_bin (bson_iter_t * piter, SimCommand * cmd_base, unsigned long offset)
{
  GString *       st = g_string_new ("");
  bson_subtype_t subtype;
  const uint8_t * bindata;
  uint32_t        lendata;
  uint8_t       * cmd = (uint8_t *)cmd_base;
  bson_iter_binary (piter, &subtype, &lendata, &bindata);
  g_string_append_len (st, (const gchar *)bindata, lendata);
  *(GString **) (cmd + offset) = st;
  return TRUE;
}



void  sim_parser_event_init (SimParser * self)
{
  g_return_if_fail (self != NULL);
  g_return_if_fail (SIM_IS_PARSER (self));
  /* Create the hash table and load the params */
  guint i;
  self->priv->event_fields = g_hash_table_new (g_str_hash, g_str_equal);
  /* This must be full */
  self->priv->event_additional_fields = g_hash_table_new_full (g_str_hash, g_str_equal, g_free, g_free);
  for (i = 0; i < G_N_ELEMENTS (event_fields); i++)
  {
    g_hash_table_insert (self->priv->event_fields,(gpointer) event_fields[i].key, (gpointer) &event_fields[i]);
  }
}

void sim_parser_event_finalize (SimParser * self)
{
  g_return_if_fail (self != NULL);
  g_return_if_fail (SIM_IS_PARSER (self));
  g_hash_table_destroy (self->priv->event_fields);
  g_hash_table_destroy (self->priv->event_additional_fields);
}

SimCommand * sim_parser_bson_event(SimParser * self, bson_iter_t * piter)
{
  g_return_val_if_fail (self != NULL, NULL);
  g_return_val_if_fail (piter !=  NULL, NULL);
  g_return_val_if_fail (SIM_IS_PARSER (self), NULL);
  SimCommand *cmd;
  gboolean error = FALSE;
  if ((cmd = sim_command_new ()) == NULL)
    return NULL;
  cmd->type = SIM_COMMAND_TYPE_EVENT;
  cmd->data.event.asset_src = VOID_ASSET;
  cmd->data.event.asset_dst = VOID_ASSET;
  cmd->data.event.risk_a = DEFAULT_RISK;
  cmd->data.event.risk_c = DEFAULT_RISK;
  cmd->data.event.generic_fields = g_hash_table_new_full (g_str_hash, g_str_equal, g_free, g_free);
  while (bson_iter_next (piter) && !error)
  {
    const char *key = bson_iter_key (piter);
    const SimCommandFields *event_info;
    if (( event_info = g_hash_table_lookup (self->priv->event_fields, (gpointer)key)) != NULL)
    {
      /* First check type */
      if (bson_iter_type (piter) == event_info->type)
      {
        /* We have the offset from the table */
        SimCopyData data_info;  
        data_info.size = event_info->size;
        data_info.type = event_info->type;
        data_info.offset = event_info->offset; 
        data_info.subtype = event_info->subtype;
        data_info.pfilter = event_info->pfilter;
 
        error = !sim_parser_copy_data_generic (piter, cmd, &data_info);
        if (error)
        {
          g_message ("Can't paarse key => %s", key);
        }
         
      }
      else
      {
        g_message ("Bad BSON type key '%s'", key);
        error = TRUE;
      }
    }
    else
    {
      /* We need to be sure what values we used here */
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

gboolean
sim_parse_validate_pulse (bson_iter_t * piter, SimCommand * cmd_base, unsigned long offset)
{
  gboolean result = FALSE;
  bson_t *subdoc = NULL;
  const uint8_t *bsondata = NULL;
  uint32_t bson_len;
  bson_iter_t iter;
  uint8_t *cmd = (uint8_t*)cmd_base;
  bson_iter_document (piter, &bson_len, &bsondata);
  subdoc = bson_new_from_data (bsondata, bson_len);
  if (subdoc && bson_iter_init (&iter, subdoc))
  {
    result = TRUE;
    while (bson_iter_next (&iter) && result)
    {
      const gchar *key;
      key = bson_iter_key(&iter);
      if (sim_util_is_pulse_id (key))
      {
        /* Now we must have array and > 0 is len */
        if (BSON_ITER_HOLDS_ARRAY(&iter))
        {
          bson_iter_t iterarray;
          guint count = 0;
          if (bson_iter_recurse (&iter, &iterarray))
          {
            while (bson_iter_next (&iterarray))
            {
              bson_iter_key (&iterarray);
              uint32_t len;
              bson_iter_utf8(&iterarray, &len);
              count++;
            }
            if (count == 0)
              result = FALSE;
            else
              result = TRUE; 
          }
          else
          {
            g_message ("Can't recurse into array\n");
            result = FALSE;
          }
          
        }
        else
        {
          result = FALSE;
        }
      }
      else
      {
        result = FALSE;
      } 
    }
  }
  if (subdoc)
  {
    bson_destroy (subdoc);
  }
  if (result)
  {
    /* Copy data */
    const uint8_t *subdoc;
    uint32_t doclen;
    bson_t *b;
    bson_iter_document (piter, &doclen, &subdoc);
    if ((b = bson_new_from_data (subdoc,doclen)) != NULL)
    {
      *(bson_t **)(cmd + offset) = b;
      result = TRUE;
    }
    else
      result = FALSE;
  }
  return result;
}
#ifdef USE_UNITTESTS
static bson_t * gen_generic_event (void)
{
  uuid_t   uuid_event;
  uuid_t   uuid_ctx;
  bson_t *event;
  bson_t child;
  char buf[255];
  time_t   t = time (NULL);
  struct tm tm;
  const char *log =  "AV - Alert - \"1431429426\" --> RID: \"502\"; RL: \"3\"; RG: \"ossec,\"; RC: \"Ossec server started.\"; USER: \"None\"; SRCIP: \"None\"; HOSTNAME: \"VirtualUSMAllInOne\"; LOCATION: \"ossec-monitord\"; EVENT: \"[INIT]ossec: Ossec started.[END]\";";
  memset (buf, 0, 255);
  uuid_generate_time (uuid_event);
  uuid_generate_time (uuid_ctx);
  event = bson_new ();
  bson_append_document_begin (event, "event", -1, &child);
  BSON_APPEND_UTF8 (&child, "type", "detector");
  BSON_APPEND_INT64 (&child, "date", t);
  BSON_APPEND_UTF8 (&child, "device", "192.168.1.240");
  BSON_APPEND_UTF8 (&child, "src_ip", "192.168.1.1");
  BSON_APPEND_UTF8 (&child, "dst_ip", "192.168.1.10");
  BSON_APPEND_INT32 (&child, "src_port", 1000);
  BSON_APPEND_INT32 (&child, "dst_port", 2000);  
  BSON_APPEND_INT32 (&child, "plugin_id", 7007);
  BSON_APPEND_INT32 (&child, "plugin_sid", 502);
  BSON_APPEND_UTF8 (&child, "username", "drizzt");
  BSON_APPEND_BINARY(&child, "log", BSON_SUBTYPE_BINARY, (const uint8_t *)log, strlen(log));
  BSON_APPEND_BINARY (&child, "ctx", BSON_SUBTYPE_UUID, uuid_ctx, 16);
  BSON_APPEND_BINARY (&child, "event_id", BSON_SUBTYPE_UUID, uuid_event, 16);
  gmtime_r (&t, &tm);
  strptime (buf, "%Y-%m-%d %H:%M:%S", &tm);
  BSON_APPEND_UTF8 (&child, "fdate", buf);
  bson_append_document_end (event, &child);
  return event;
}
static gboolean
sim_parser_event_test1 (void)
{
  /*
  event type="detector" date="1431429426" device="192.168.1.240" interface="any" plugin_id="7007" plugin_sid="502" src_ip="192.168.1.240" dst_ip="0.0.0.0" username="Tm9uZQ==" log="QVYgLSBBbGVydCAtICIxNDMxNDI5NDI2IiAtLT4gUklEOiAiNTAyIjsgUkw6ICIzIjsgUkc6ICJvc3NlYywiOyBSQzogIk9zc2VjIHNlcnZlciBzdGFydGVkLiI7IFVTRVI6ICJOb25lIjsgU1JDSVA6ICJOb25lIjsgSE9TVE5BTUU6ICJWaXJ0dWFsVVNNQWxsSW5PbmUiOyBMT0NBVElPTjogIm9zc2VjLW1vbml0b3JkIjsgRVZFTlQ6ICJbSU5JVF1vc3NlYzogT3NzZWMgc3RhcnRlZC5bRU5EXSI7IA==" fdate="2015-05-12 11:17:06" tzone="-4.0" ctx="20f3da04-edc0-11e4-a9e8-000c29d946de" event_id="f89811e4-9de2-000c-29d9-46de6c25a736"
  */
  gboolean result = FALSE;
#if 0
  struct in_addr in_src;
  struct in_addr in_dst;
  struct in_addr in_device;
#endif
  bson_t *event;
  SimParser * parser;
  SimCommand * cmd = NULL;

   /* Set up ip */
  event = gen_generic_event ();
  do{
    if ((parser = sim_parser_new ()) == NULL)
    {
      result = FALSE;
      break;
    }
    if ((cmd = sim_parser_bson (parser, bson_get_data (event), event->len)) == NULL)
    {
      g_message ("Can't parse BSON data");
      result = FALSE;
      break;
    }
    /* Now check fields */
    if (cmd->data.event.plugin_id != 7007)
    {
      g_message ("Test: Bad plugin_id");
      result = FALSE;
      break;
    }
    if (cmd->data.event.plugin_sid != 502)
    {
      g_message ("Test: Bad plugin_sid");
      result = FALSE;
      break;
    }
    if (strcmp (cmd->data.event.username, "drizzt") != 0)
    {
      g_message ("Test: Bad username");
      result = FALSE;
      break;
    }
    if (strcmp (cmd->data.event.device, "192.168.1.240") != 0)
    {
      g_message ("Test: Bad device");
      result = FALSE;
      break;
    }
    if (strcmp (cmd->data.event.src_ip, "192.168.1.1") != 0)
    {
      g_message ("Test: Bad src_ip");
      result = FALSE;
      break;
    }
    if (strcmp (cmd->data.event.dst_ip, "192.168.1.10") != 0)
    {
      g_message ("Test: Bad dst_ip");
      result = FALSE;
      break;
    }
    if (cmd->data.event.src_port != 1000)
    {
      g_message ("Test: Bad src_port");
      result = FALSE;
      break;
    }
    if (cmd->data.event.dst_port != 2000)
    {
      g_message ("Test: Bad dst_port");
      result = FALSE;
      break;
    }
    result = TRUE;
  } while (0);
  
  bson_destroy (event);
  g_object_unref (parser);
  if (cmd)
    g_object_unref (cmd);

  return result;
}

static gboolean
sim_parser_event_test_pulse (gchar *pulsestr)
{
  bson_t *event = NULL;
  bson_t *pulse = NULL;
  bson_t *subdoc;
  gboolean result = FALSE;
  SimParser     *parser = NULL;
  SimCommand    *cmd = NULL;
  gchar *json;
  bson_iter_t iter;
  const uint8_t *bsondata;
  uint32_t bsonlen;
  bson_error_t bson_error;
  event = gen_generic_event ();
  /* Pulse string */
  do{
    if ((pulse = bson_new_from_json ((const uint8_t *)pulsestr, strlen(pulsestr), &bson_error)) == NULL)
    {
      result = FALSE;
      break;
    }
    /* Get reference to event */
    bson_iter_init (&iter, event);
    /* Get The "event" subdocument */
    if (!bson_iter_find (&iter, "event"))
    {
      result = FALSE;
      break;
    }
    bson_iter_document (&iter, &bsonlen, &bsondata);
    subdoc = bson_new_from_data (bsondata, bsonlen);
    if (!bson_append_document(subdoc, "pulses", -1 , pulse))
    {
      result = FALSE;
      break;  
    }
    bson_reinit (event);
    if (!bson_append_document (event, "event", -1,  subdoc))
    {
      result = FALSE;
      break;
    }
    if ((parser = sim_parser_new ()) == NULL)
    {
      result = FALSE;
      break;
    }
    if ((cmd = sim_parser_bson (parser, bson_get_data (event), event->len)) == NULL)
    {
      g_message ("Can't parse BSON data");
      result = FALSE;
      break;
    }
    /* Dumps the bson */
    if (cmd->data.event.pulses == NULL)
    {
      g_message ("Can't parse pulses");
      result = FALSE;
      break;
    }
    json = bson_as_json (event, NULL);
    g_message ("BSON:%s",json);
    g_free (json);
    result = TRUE;
  }while (0);
 
  if (event) 
    bson_destroy (event);
  if (pulse)
    bson_destroy (pulse);
  if (cmd)
    g_object_unref (cmd);
  if (parser)
    g_object_unref (parser);
  return result;
}
static gboolean 
sim_parser_event_test3(void)
{
  return sim_parser_event_test_pulse ("{'e06562c13e078df58ad64554':[]'");

}
static gboolean 
sim_parser_event_test2(void)
{
  return sim_parser_event_test_pulse ("{\"e06562c13e078df58ad64554\":[\"uno\", \"dos\", \"tres\", \"cuatro\"]}");
}
void
sim_parser_register_event_tests (SimUnittesting * engine)
{
  sim_unittesting_append (engine, "SimParse Event - correct", sim_parser_event_test1, TRUE);
  sim_unittesting_append (engine, "SimParse Event - Pulse" , sim_parser_event_test2, TRUE);
  sim_unittesting_append (engine, "SimParse Event - Pulse Error" , sim_parser_event_test3, FALSE); 
}
#endif
