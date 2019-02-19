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
   
static const SimSessionType session_types[]=
{
  SIM_SESSION_TYPE_SERVER_DOWN,
  SIM_SESSION_TYPE_SENSOR,
  SIM_SESSION_TYPE_WEB,
  SIM_SESSION_TYPE_FRAMEWORKD
};
/* Static parse func prototypes */
static gboolean sim_parser_connect_id (bson_iter_t *, const char *, SimCommand *);
static gboolean sim_parser_connect_type (bson_iter_t *, const char *key, SimCommand *cmd);
static gboolean sim_parser_connect_version (bson_iter_t *, const char *key, SimCommand *cmd);
static gboolean sim_parser_connect_sensor_id (bson_iter_t *, const char *key, SimCommand *cmd);
static gboolean sim_parser_connect_hostname (bson_iter_t *piter, const char *key, SimCommand *cmd);

/* We use this to parse */
static const struct _connect_fields
{
  const char *key;
  gboolean (*parsefunc)(bson_iter_t *, const char *key, SimCommand *);
  gboolean required;
  guint    index;
} connect_fields[]=
{
  {"id", sim_parser_connect_id, TRUE, 0},
  {"type", sim_parser_connect_type, TRUE, 1},
  {"version", sim_parser_connect_version, TRUE, 2},
  {"sensor_id", sim_parser_connect_sensor_id, TRUE, 3},
  {"hostname", sim_parser_connect_hostname, FALSE, 4}

};

static  gboolean 
sim_parse_check_session_type(SimSessionType type)
{
  gboolean result = FALSE; 
  guint i;
  for (i = 0; i <  G_N_ELEMENTS(session_types) && result == FALSE; i++)
    if (session_types[i] == type)
      result = TRUE;
  return result;
}

static gboolean
sim_parser_connect_id (bson_iter_t *piter, const char *key, SimCommand *cmd)
{
  g_return_val_if_fail (piter != NULL, FALSE);
  g_return_val_if_fail (cmd != NULL, FALSE);
  g_return_val_if_fail (key != NULL, FALSE);
  gboolean result = FALSE;
  if (BSON_ITER_HOLDS_INT32(piter))
  {
    cmd->id = bson_iter_int32(piter);
    result = TRUE; 
  } 
  return result;
}
static gboolean
sim_parser_connect_type (bson_iter_t *piter, const char *key, SimCommand *cmd)
{
  g_return_val_if_fail (piter != NULL, FALSE);
  g_return_val_if_fail (cmd != NULL, FALSE);
  g_return_val_if_fail (key != NULL, FALSE);
  gboolean result = FALSE;
  if (BSON_ITER_HOLDS_INT32(piter))
  {
    cmd->data.connect.type = bson_iter_int32 (piter);
    if (sim_parse_check_session_type (cmd->data.connect.type))
      result = TRUE;
    else
      g_message ("Bad BSON connect message (type)");
  } 
  return result;
}
static gboolean
sim_parser_connect_version (bson_iter_t *piter, const char *key, SimCommand *cmd)
{
  g_return_val_if_fail (piter != NULL, FALSE);
  g_return_val_if_fail (cmd != NULL, FALSE);
  g_return_val_if_fail (key != NULL, FALSE);

  cmd->data.connect.sensor_ver = g_new0 (SimVersion, 1);

  if (BSON_ITER_HOLDS_UTF8(piter))
  {
    /* We need to split the version x.y.z.n */
    const gchar *version = bson_iter_utf8 (piter, NULL);
    if (strlen (version) > 0)
    {
      sim_version_parse (version,
                         &(cmd->data.connect.sensor_ver->major),
                         &(cmd->data.connect.sensor_ver->minor),
                         &(cmd->data.connect.sensor_ver->micro),
                         &(cmd->data.connect.sensor_ver->nano));
    }
  }

  return TRUE;
}

static gboolean
sim_parser_connect_hostname (bson_iter_t *piter, const char *key, SimCommand *cmd)
{
  g_return_val_if_fail (piter != NULL, FALSE);
  g_return_val_if_fail (cmd != NULL, FALSE);
  g_return_val_if_fail (key != NULL, FALSE);
  gboolean result = FALSE;
  if (BSON_ITER_HOLDS_UTF8 (piter))
  {
    cmd->data.connect.hostname = g_strdup (bson_iter_utf8(piter, NULL));
    result = TRUE;
  }

  return result;
}

static gboolean
sim_parser_connect_sensor_id (bson_iter_t *piter, const char *key, SimCommand *cmd)
{
  g_return_val_if_fail (piter != NULL, FALSE);
  g_return_val_if_fail (cmd != NULL, FALSE);
  g_return_val_if_fail (key != NULL, FALSE);
  gboolean result = FALSE;
  bson_subtype_t    subtype;
  uint32_t uuidlen;
  const uint8_t *uuidbin;
  if (BSON_ITER_HOLDS_BINARY (piter))
  {
    bson_iter_binary (piter, &subtype, &uuidlen, &uuidbin);
    if (subtype == BSON_SUBTYPE_UUID)
    {
        cmd->data.connect.sensor_id = sim_uuid_new_from_bin (uuidbin);
        result = TRUE;
    }
  }
  
  if (!result)
    g_message ("Bad BSON connec message (sensor_id)");
  return result;
}

SimCommand *
sim_parser_bson_connect(SimParser *self, bson_iter_t *piter)
{
  g_return_val_if_fail (piter != NULL, NULL);
  SimCommand * cmd = NULL;
  gboolean error = FALSE;
  //int i;
  if ((cmd = sim_command_new ()) == NULL)
  {
    return NULL;
  }
  cmd->type = SIM_COMMAND_TYPE_CONNECT;
  while (bson_iter_next (piter) && !error)
  {
    const char *key = bson_iter_key(piter);
    const struct _connect_fields *connect_info;
    if (( connect_info = g_hash_table_lookup (self->priv->connect_fields, (gpointer)key)) != NULL)
    {
      if (!connect_info->parsefunc (piter, key, cmd))
        error = TRUE;
      else
      {
        if (self->priv->connect_required[connect_info->index] != FALSE )
        {
          error = TRUE;
          g_message ("Bad BSON connect message (duplicated required key)");
        }
        else
        {
          self->priv->connect_required[connect_info->index] = TRUE;
        }
      }
    }
    else
    {
      g_message ("Bad key in connect message '%s'", key); 
    }
    
  }
  /* Check required fields */
#if 0
  i = 0;
  while (!error && i < 4)
  {
    if (!self->priv->connect_required[i])
    {
      error = TRUE;
      g_message ("Bad BSON connect message (missing required key)");
    }
    i++;  
  }
#endif    
  if (error)
  {
    g_message ("Can't parse connect message");
    g_object_unref (cmd);
    cmd = NULL;
  }
  return cmd;
}


void sim_parser_connect_finalize (SimParser * self)
{
  g_return_if_fail (self != NULL);
  g_return_if_fail (SIM_IS_PARSER (self));
  g_hash_table_destroy (self->priv->connect_fields);
}


void sim_parser_connect_init (SimParser *self)
{
  guint i;
  self->priv->connect_fields = g_hash_table_new (g_str_hash, g_str_equal); 
  for (i = 0; i < G_N_ELEMENTS(connect_fields); i++)
  {
    /* We don't need to duplicate. The keys are static, only insert for speed */
    g_hash_table_insert (self->priv->connect_fields,(gpointer) connect_fields[i].key,(gpointer) &connect_fields[i]);
  }
  for (i = 0; i < 5; i++)
    self->priv->connect_required[i] = FALSE;
}

#if USE_UNITTESTS
/* check  version */
static gboolean 
sim_parser_connect_test4 (void)
{
{
  bson_t *bson_connect = bson_new ();
  bson_t child;
  SimParser *parser = NULL;
  SimCommand *cmd = NULL;
  gboolean result = FALSE;
  uint8_t uuid[]={0x07,0x92,0xd6,0x72,0xf4,0xce,0x11,0xe4,0x9d,0xe2,0x00,0x0c,0x29,0xd9,0x46,0xde};
  bson_append_document_begin (bson_connect,"connect", -1, &child);
  BSON_APPEND_UTF8 (&child, "id", "bad id");
  BSON_APPEND_INT32 (&child, "type", SIM_SESSION_TYPE_WEB);
  BSON_APPEND_UTF8 (&child, "version", "x.x.x");
  if (bson_append_binary (&child, "sensor_id", -1, BSON_SUBTYPE_UUID, uuid, 16) == FALSE)
    return FALSE;
  bson_append_document_end (bson_connect, &child);
  do{
    if ((parser = sim_parser_new()) == NULL)
    { 
      result = FALSE;
      break;
    }
    if ((cmd = sim_parser_bson (parser, bson_get_data (bson_connect), bson_connect->len)) != NULL)
    {
      result = FALSE;
      break;
    }
    result = TRUE;
  } while (0);

  return result;
}
}
static gboolean
sim_parser_connect_test3 (void)
{
  bson_t *bson_connect = bson_new ();
  bson_t child;
  SimParser *parser = NULL;
  SimCommand *cmd = NULL;
  gboolean result = FALSE;
  uint8_t uuid[]={0x07,0x92,0xd6,0x72,0xf4,0xce,0x11,0xe4,0x9d,0xe2,0x00,0x0c,0x29,0xd9,0x46,0xde};
  bson_append_document_begin (bson_connect,"connect", -1, &child);
  BSON_APPEND_INT32 (&child, "id", 10);
  BSON_APPEND_INT32 (&child, "type", SIM_SESSION_TYPE_WEB);
  BSON_APPEND_UTF8 (&child, "version", "x.x.x");
  if (bson_append_binary (&child, "sensor_id", -1, BSON_SUBTYPE_UUID, uuid, 16) == FALSE)
    return FALSE;
  bson_append_document_end (bson_connect, &child);
  do{
    if ((parser = sim_parser_new()) == NULL)
    { 
      result = FALSE;
      break;
    }
    if ((cmd = sim_parser_bson (parser, bson_get_data (bson_connect), bson_connect->len)) != NULL)
    {
      result = FALSE;
      break;
    }
    result = TRUE;
  } while (0);

  return result;
}
/* check a  missing mandatory fix */
static gboolean
sim_parser_connect_test2 (void)
{
  bson_t *bson_connect = bson_new ();
  bson_t child;
  SimParser *parser = NULL;
  SimCommand *cmd = NULL;
  gboolean result = FALSE;
  bson_append_document_begin (bson_connect,"connect", -1, &child);
  BSON_APPEND_INT32 (&child, "id", 10);
  BSON_APPEND_INT32 (&child, "type", SIM_SESSION_TYPE_WEB);
  BSON_APPEND_UTF8 (&child, "version", "5.0.1");
  bson_append_document_end (bson_connect, &child);
  do{
    if ((parser = sim_parser_new()) == NULL)
    { 
      result = FALSE;
      break;
    }
    if ((cmd = sim_parser_bson (parser, bson_get_data (bson_connect), bson_connect->len)) != NULL)
    {
      result = FALSE;
      break;
    }
    result = TRUE;
  } while (0);

  return result;
}


/* Check a correct CONNECT message */
static gboolean
sim_parser_connect_test1 (void)
{
  bson_t *bson_connect = bson_new ();
  bson_t child;
  uint8_t uuid[]={0x07,0x92,0xd6,0x72,0xf4,0xce,0x11,0xe4,0x9d,0xe2,0x00,0x0c,0x29,0xd9,0x46,0xde};
  SimParser *parser = NULL;
  SimCommand *cmd = NULL;
  gboolean result = FALSE;
  bson_append_document_begin (bson_connect,"connect", -1, &child);
  BSON_APPEND_INT32 (&child, "id", 10);
  BSON_APPEND_INT32 (&child, "type", SIM_SESSION_TYPE_WEB);
  BSON_APPEND_UTF8 (&child, "version", "5.0.1");
  if (bson_append_binary (&child, "sensor_id", -1, BSON_SUBTYPE_UUID, uuid, 16) == FALSE)
    return FALSE;
  bson_append_document_end (bson_connect, &child);
  /* Check */
  bson_iter_t iter;
  bson_iter_init (&iter, bson_connect);
  do{
    if ((parser = sim_parser_new()) == NULL)
    { 
      result = FALSE;
      break;
    }
    if ((cmd = sim_parser_bson (parser, bson_get_data (bson_connect), bson_connect->len)) == NULL)
    {
      result = FALSE;
      break;
    }
    if (cmd->type !=  SIM_COMMAND_TYPE_CONNECT)
    {
      result = FALSE;
      break;
    }
    if (cmd->data.connect.sensor_ver->major != 5 || cmd->data.connect.sensor_ver->minor != 0 || cmd->data.connect.sensor_ver->micro != 1)
    {
      result = FALSE;
      break;
    }
    if (cmd->data.connect.sensor_id == NULL)
    {
      result = FALSE;
      break;
    }
    /* Check uuid */
    SimUuid * uuidbin =  sim_uuid_new_from_bin (uuid);
    gboolean test =  sim_uuid_equal (uuidbin, cmd->data.connect.sensor_id);
    g_object_unref (uuidbin);
    if (!test)
    {
      result = FALSE;
      break;
    }
    result = TRUE;
  } while (0);
  bson_destroy (bson_connect);
  g_object_unref (parser);
  return result;
}
void 
sim_parser_register_connect_tests (SimUnittesting *engine)
{
    sim_unittesting_append(engine, "SimParse Connect BSON - correct", sim_parser_connect_test1, TRUE);
    sim_unittesting_append(engine, "SimParse Connect BSON - missing sensor_id", sim_parser_connect_test2, TRUE);
    sim_unittesting_append(engine, "SimParse Connect BSON - bad version string", sim_parser_connect_test3, TRUE);
    sim_unittesting_append(engine, "SimParse Connect BSON - bad id type", sim_parser_connect_test4, TRUE);
}
#endif 
