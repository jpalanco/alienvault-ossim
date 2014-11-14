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

#include "sim-xml-directive.h"

#include <string.h>

#include "os-sim.h"
#include "sim-object.h"
#include "sim-util.h"
#include "sim-net.h"
#include "sim-timetable.h"
#include "sim-log.h"
#include "sim-inet.h"
#include "sim-server.h"
#include "sim-directive.h"
#include "sim-rule.h"
#include "sim-uuid.h"

extern SimMain ossim;

struct _SimXmlDirectivePrivate
{
  GList *directives;
  GList *groups;
  GList *timetables;
};

#define OBJECT_DIRECTIVES "directives"
#define OBJECT_DIRECTIVE  "directive"
#define OBJECT_RULE       "rule"
#define OBJECT_RULES      "rules"
#define OBJECT_ACTION     "action"
#define OBJECT_ACTIONS    "actions"

#define PROPERTY_ID                             "id"
#define PROPERTY_NAME                           "name"
#define PROPERTY_STICKY                         "sticky"
#define PROPERTY_STICKY_DIFFERENT               "sticky_different"
#define PROPERTY_NOT                            "not"
#define PROPERTY_TYPE                           "type"
#define PROPERTY_PRIORITY                       "priority"
#define PROPERTY_RELIABILITY                    "reliability"
#define PROPERTY_REL_ABS                        "rel_abs"
#define PROPERTY_CONDITION                      "condition"
#define PROPERTY_VALUE                          "value"
#define PROPERTY_INTERVAL                       "interval"
#define PROPERTY_ABSOLUTE                       "absolute"
#define PROPERTY_TIME_OUT                       "time_out"
#define PROPERTY_OCCURRENCE                     "occurrence"
#define PROPERTY_SRC_IP                         "from"
#define PROPERTY_DST_IP                         "to"
#define PROPERTY_SRC_PORT                       "port_from"
#define PROPERTY_DST_PORT                       "port_to"
#define PROPERTY_PROTOCOL                       "protocol"
#define PROPERTY_PLUGIN_ID                      "plugin_id"
#define PROPERTY_PLUGIN_SID                     "plugin_sid"
#define PROPERTY_SENSOR                         "sensor"
#define PROPERTY_FILENAME                       "filename"
#define PROPERTY_USERNAME                       "username"        //the following variables won't be used by every sensor
#define PROPERTY_PASSWORD                       "password"
#define PROPERTY_PRODUCT                        "product"
#define PROPERTY_CATEGORY                       "category"
#define PROPERTY_SUBCATEGORY                    "subcategory"
#define PROPERTY_SUPPRESS                       "suppress"
#define PROPERTY_FROM_REP                       "from_rep"
#define PROPERTY_TO_REP                         "to_rep"
#define PROPERTY_FROM_REP_MIN_REL               "from_rep_min_rel"
#define PROPERTY_TO_REP_MIN_REL                 "to_rep_min_rel"
#define PROPERTY_FROM_REP_MIN_PRI               "from_rep_min_pri"
#define PROPERTY_TO_REP_MIN_PRI                 "to_rep_min_pri"
#define PROPERTY_USERDATA1                      "userdata1"
#define PROPERTY_USERDATA2                      "userdata2"
#define PROPERTY_USERDATA3                      "userdata3"
#define PROPERTY_USERDATA4                      "userdata4"
#define PROPERTY_USERDATA5                      "userdata5"
#define PROPERTY_USERDATA6                      "userdata6"
#define PROPERTY_USERDATA7                      "userdata7"
#define PROPERTY_USERDATA8                      "userdata8"
#define PROPERTY_USERDATA9                      "userdata9"
#define PROPERTY_TIMETABLE                      "timetable"
#define PROPERTY_ENTITY                         "entity"
#define PROPERTY_GROUP_ALARM_BY                 "groupalarmby"
#define PROPERTY_GROUP_ALARM_TIME_OUT           "groupalarmtimeout"
#define PROPERTY_CTX_NAME                       "contextname"
#define PROPERTY_FROM_PROPERTY                  "from_property"
#define PROPERTY_TO_PROPERTY                    "to_property"



#define OBJECT_GROUPS           "groups"
#define OBJECT_GROUP            "group"
#define OBJECT_APPEND_DIRECTIVE "append-directive"
#define PROPERTY_DIRECTIVE_ID   "directive_id"

#define OBJECT_TIMETABLES "timetables"
#define OBJECT_TIMETABLE  "timetable"
#define OBJECT_TIMERULE   "timerule"
#define PROPERTY_DATESPEC "datespec"
#define PROPERTY_TIMESPEC "timespec"


#define SIM_XML_DIRECTIVE_GET_PRIVATE(object) \
  (G_TYPE_INSTANCE_GET_PRIVATE ((object), SIM_TYPE_XML_DIRECTIVE, SimXmlDirectivePrivate))

SIM_DEFINE_TYPE (SimXmlDirective, sim_xml_directive, G_TYPE_OBJECT, NULL)
     gboolean sim_xml_directive_new_timetables_from_node (SimXmlDirective * xml, xmlNodePtr node);

     static gboolean sim_xml_directive_set_rule_entity (SimXmlDirective * xmldirect, SimRule * rule, gchar * value);


/*
 * SimXmlDirective object signals
 */
     enum
     {
       SIM_XML_DIRECTIVE_CHANGED,
       SIM_XML_DIRECTIVE_LAST_SIGNAL
     };

     static gint xmldirect_signals[SIM_XML_DIRECTIVE_LAST_SIGNAL] = { 0, };

gboolean sim_xml_directive_new_groups_from_node (SimXmlDirective * xmldirect, xmlNodePtr node);
/*
 * SimXmlDirective class interface
 */

static void
sim_xml_directive_class_init (SimXmlDirectiveClass * klass)
{
  GObjectClass *object_class = G_OBJECT_CLASS (klass);

  parent_class = g_type_class_ref (G_TYPE_OBJECT);

  xmldirect_signals[SIM_XML_DIRECTIVE_CHANGED] = g_signal_new ("changed", G_TYPE_FROM_CLASS (object_class), G_SIGNAL_RUN_LAST, G_STRUCT_OFFSET (SimXmlDirectiveClass, changed), NULL, NULL, g_cclosure_marshal_VOID__VOID, G_TYPE_NONE, 0);

  object_class->finalize = sim_xml_directive_finalize;
  klass->changed = NULL;

  g_type_class_add_private (klass, sizeof (SimXmlDirectivePrivate));
}

static void
sim_xml_directive_instance_init (SimXmlDirective * xml_directive)
{
  xml_directive->_priv = SIM_XML_DIRECTIVE_GET_PRIVATE (xml_directive);

  xml_directive->_priv->directives = NULL;
  xml_directive->_priv->groups = NULL;
  xml_directive->_priv->timetables = NULL;
}

static void
sim_xml_directive_finalize (GObject *gobject)
{
  SimXmlDirective *xmldirect = SIM_XML_DIRECTIVE (gobject);
  SimDirective *directive;
  GList *list;

  for (list = xmldirect->_priv->directives; list; list = g_list_next (list))
  {
    directive = SIM_DIRECTIVE (list->data);
    g_object_unref (directive);
  }
  g_list_free (xmldirect->_priv->directives);

  G_OBJECT_CLASS (parent_class)->finalize (gobject);
}

/*
 * Used to get the variable type from properties in the directive
 */
SimRuleVarType
sim_xml_directive_get_rule_var_from_property (const gchar * var)
{
  if (!strcmp (var, PROPERTY_PLUGIN_ID))
    return SIM_RULE_VAR_PLUGIN_ID;
  else if (!strcmp (var, PROPERTY_PLUGIN_SID))
    return SIM_RULE_VAR_PLUGIN_SID;
  else if (!strcmp (var, PROPERTY_PRODUCT))
    return SIM_RULE_VAR_PRODUCT;
  else if (!strcmp (var, PROPERTY_ENTITY))
    return SIM_RULE_VAR_ENTITY;
  else if (!strcmp (var, PROPERTY_CATEGORY))
    return SIM_RULE_VAR_CATEGORY;
  else if (!strcmp (var, PROPERTY_SUBCATEGORY))
    return SIM_RULE_VAR_SUBCATEGORY;
  else if (!strcmp (var, PROPERTY_FILENAME))
    return SIM_RULE_VAR_FILENAME;
  else if (!strcmp (var, PROPERTY_USERNAME))
    return SIM_RULE_VAR_USERNAME;
  else if (!strcmp (var, PROPERTY_PASSWORD))
    return SIM_RULE_VAR_PASSWORD;
  else if (!strcmp (var, PROPERTY_USERDATA1))
    return SIM_RULE_VAR_USERDATA1;
  else if (!strcmp (var, PROPERTY_USERDATA2))
    return SIM_RULE_VAR_USERDATA2;
  else if (!strcmp (var, PROPERTY_USERDATA3))
    return SIM_RULE_VAR_USERDATA3;
  else if (!strcmp (var, PROPERTY_USERDATA4))
    return SIM_RULE_VAR_USERDATA4;
  else if (!strcmp (var, PROPERTY_USERDATA5))
    return SIM_RULE_VAR_USERDATA5;
  else if (!strcmp (var, PROPERTY_USERDATA6))
    return SIM_RULE_VAR_USERDATA6;
  else if (!strcmp (var, PROPERTY_USERDATA7))
    return SIM_RULE_VAR_USERDATA7;
  else if (!strcmp (var, PROPERTY_USERDATA8))
    return SIM_RULE_VAR_USERDATA8;
  else if (!strcmp (var, PROPERTY_USERDATA9))
    return SIM_RULE_VAR_USERDATA9;

  return SIM_RULE_VAR_NONE;
}



/*
 *
 *
 *
 *
 */
SimDirective *
find_directive (SimXmlDirective * xmldirect, gint id)
{
  GList *list;

  g_return_val_if_fail (SIM_IS_XML_DIRECTIVE (xmldirect), NULL);

  if (!id)
    return NULL;

  list = xmldirect->_priv->directives;
  while (list)
  {
    SimDirective *directive = (SimDirective *) list->data;
    gint cmp = sim_directive_get_id (directive);

    if (cmp == id)
      return directive;

    list = list->next;
  }

  return NULL;
}

/**
 * sim_xml_directive_new
 *
 * Creates a new #SimXmlDirective object, which can be used to describe
 * a directive which will then be loaded by a provider to create its
 * defined structure
 */
SimXmlDirective *
sim_xml_directive_new (void)
{
  SimXmlDirective *xmldirect;
  xmldirect = g_object_new (SIM_TYPE_XML_DIRECTIVE, NULL);
  return xmldirect;
}

/**
 * sim_xml_directive_new_from_file
 */
SimXmlDirective *
sim_xml_directive_new_from_file (const gchar * file)
{
  SimXmlDirective *xmldirect;   //here will be stored all the directives, and all the groups
  xmlParserCtxtPtr ctx;
  xmlErrorPtr error;
  xmlDocPtr doc;
  xmlNodePtr root;
  xmlNodePtr node;
  GList *list;
  xmlSchemaParserCtxtPtr parserCtxt = NULL;
  xmlSchemaPtr schema = NULL;
  xmlSchemaValidCtxtPtr validCtxt = NULL;
  xmlDocPtr xmlSchema = NULL;
  int xsderror = 0;


  g_return_val_if_fail (file != NULL, NULL);

  ctx = xmlNewParserCtxt ();
  if (!ctx)
  {
    ossim_debug ("Could not create XML Parser for file %s", file);
    return NULL;
  }
  // Set error handler for this context.
  xmlSetGenericErrorFunc (ctx, sim_log_xml_handler);
  /* Verify if tjhe directive.xsd file is present */
  if (g_file_test (OS_SIM_GLOBAL_WSD_FILE, G_FILE_TEST_EXISTS | G_FILE_TEST_IS_REGULAR))
  {
    /* I have to load and parse the directive.xsd file */
    parserCtxt = xmlSchemaNewParserCtxt (OS_SIM_GLOBAL_WSD_FILE);
    if (parserCtxt != NULL)
    {
      schema = xmlSchemaParse (parserCtxt);
      if (schema != NULL)
      {
        /* Crear contexto de validación */
        validCtxt = xmlSchemaNewValidCtxt (schema);
        if (validCtxt == NULL)
        {
          error = xmlGetLastError ();
          g_warning ("%s: Can't parse XSD schema: %s. We don't use it", __FUNCTION__, error->message);

        }
      }
      else
      {
        error = xmlGetLastError ();
        g_warning ("%s: Can't parse XSD schema: %s. We don't use it", __FUNCTION__, error->message);
      }
    }
    else
    {
      error = xmlGetLastError ();
      g_warning ("%s: Can't parse XSD schema: %s. We don't use it", __FUNCTION__, error->message);
    }

  }

  /* parse an validate de XML directives.xml file */
  doc = xmlCtxtReadFile (ctx, file, NULL, XML_PARSE_DTDVALID | XML_PARSE_NOENT | XML_PARSE_RECOVER | XML_PARSE_NOERROR | XML_PARSE_NOWARNING);
  if (validCtxt != NULL)
  {
    if (doc != NULL)
    {
      if (xmlSchemaValidateDoc (validCtxt, doc) != 0)
      {
        error = xmlGetLastError ();
        
        g_warning ("Could not parse file at %s. Error:%s", file, error->message);
        xsderror = -1;
      }
      else
      {
        xsderror = 0;
      }
    }
    /* Clean up */
    if (parserCtxt)
    {
      xmlSchemaFreeParserCtxt (parserCtxt);
      parserCtxt = NULL;
    }
    if (schema)
    {
      xmlSchemaFree (schema);
      schema = NULL;
    }
    if (validCtxt)
    {
      xmlSchemaFreeValidCtxt (validCtxt);
      validCtxt = NULL;
    }
    if (xmlSchema)
    {
      xmlFreeDoc (xmlSchema);
      xmlSchema = NULL;
    }

  }
  if (xsderror)
  {
    xmlFreeDoc (doc);
    xmlFreeParserCtxt (ctx);
    return NULL;                /* Don't like return in the middle of a function */
  }

  if (!doc)
  {
    error = xmlGetLastError ();
    xmlFreeParserCtxt (ctx);
    g_warning ("Could not parse file at %s. Error:%s", file, error->message);
    return (NULL);
  }
  if (!ctx->valid)
  {
    error = xmlGetLastError ();
    xmlFreeDoc (doc);
    xmlFreeParserCtxt (ctx);
    g_warning ("Validate error in file: %s. Error %s", file, error->message);
    return (NULL);
  }

  xmlFreeParserCtxt (ctx);

  xmldirect = g_object_new (SIM_TYPE_XML_DIRECTIVE, NULL);

  /* parse the file */
  root = xmlDocGetRootElement (doc);    //we need to know the first element in the tree
  if (strcmp ((gchar *) root->name, OBJECT_DIRECTIVES))
  {
    ossim_debug ("Invalid XML directive file '%s'", file);
    xmlFreeDoc (doc);
    g_object_unref (G_OBJECT (xmldirect));
    return NULL;
  }

  node = root->xmlChildrenNode;
  while (node)                  //while
  {
    if (!strcmp ((gchar *) node->name, OBJECT_DIRECTIVE))       //parse each one of the directives and store it in xmldirect
      sim_xml_directive_new_directive_from_node (xmldirect, node);

    if (!strcmp ((gchar *) node->name, OBJECT_GROUPS))
      sim_xml_directive_new_groups_from_node (xmldirect, node); // the same with directive groups
    if (!strcmp ((gchar *) node->name, OBJECT_TIMETABLES))
      sim_xml_directive_new_timetables_from_node (xmldirect, node);

    node = node->next;
  }
  GList *lt = xmldirect->_priv->timetables;
  while (lt)
  {
    SimTimetable *tb = lt->data;
    sim_timetable_dump (tb);
    /* assign timetables to directives */
    GList *ld = xmldirect->_priv->directives;
    while (ld)
    {
      SimDirective *directive = (SimDirective *) ld->data;
      if (sim_directive_get_timetable_name (directive) != NULL && strcmp (sim_directive_get_timetable_name (directive), sim_timetable_get_name (tb)) == 0)
      {
        ossim_debug ("Setting timetable %s to directive %s", sim_timetable_get_name (tb), sim_directive_get_name (directive));
        sim_directive_set_timetable (directive, tb);
      }
      ld = ld->next;
    }

    lt = lt->next;
  }
  //now we have all the directives, and all the groups. But is needed to tell to each directive if it's is inside
  //a group
  list = xmldirect->_priv->groups;
  while (list)
  {
    SimDirectiveGroup *group = (SimDirectiveGroup *) list->data;
    GList *ids = sim_directive_group_get_ids (group);

    while (ids)
    {
      gint id = GPOINTER_TO_INT (ids->data);
      SimDirective *directive = find_directive (xmldirect, id);

      if (directive)
        sim_directive_append_group (directive, group);

      ids = ids->next;
    }

    list = list->next;
  }
  xmlFreeDoc (doc);
  /* Free the elements */
  if (parserCtxt)
  {
    xmlSchemaFreeParserCtxt (parserCtxt);
  }
  if (schema)
  {
    xmlSchemaFree (schema);
  }
  if (validCtxt)
  {
    xmlSchemaFreeValidCtxt (validCtxt);
  }
  if (xmlSchema)
  {
    xmlFreeDoc (xmlSchema);
  }

  return xmldirect;
}

/**
 * sim_xml_directive_new_full:
 * @context: a #SimContext
 * @file: const gchar * filename
 *
 
 * Returns: new #SimXmlDirective object.
 */
int
sim_xml_directive_load_file (SimXmlDirective * xml_directive, const gchar * file)
{
  xmlParserCtxtPtr ctx;
  xmlErrorPtr error;
  xmlDocPtr doc;
  xmlNodePtr root;
  xmlNodePtr node;
  GList *list;
  xmlSchemaParserCtxtPtr parserCtxt = NULL;
  xmlSchemaPtr schema = NULL;
  xmlSchemaValidCtxtPtr validCtxt = NULL;
  xmlDocPtr xmlSchema = NULL;
  int xsderror = 0;

  g_return_val_if_fail (SIM_IS_XML_DIRECTIVE (xml_directive), -1);
  g_return_val_if_fail (file != NULL, -1);

  ctx = xmlNewParserCtxt ();
  if (!ctx)
  {
    g_message ("Could not create XML Parser for file %s", file);
    return -1;                  /* I don't like return in the middle on the function body */
  }
  // Set error handler for this context.
  xmlSetGenericErrorFunc (ctx, sim_log_xml_handler);
  /* Ok, the magic to use the xsd file here */
  if (g_file_test (OS_SIM_GLOBAL_WSD_FILE, G_FILE_TEST_EXISTS | G_FILE_TEST_IS_REGULAR))
  {
    /* I have to load and parse the directive.xsd file */
    parserCtxt = xmlSchemaNewParserCtxt (OS_SIM_GLOBAL_WSD_FILE);
    if (parserCtxt != NULL)
    {
      schema = xmlSchemaParse (parserCtxt);
      if (schema != NULL)
      {
        /* Crear contexto de validación */
        validCtxt = xmlSchemaNewValidCtxt (schema);
        if (validCtxt == NULL)
        {
          error = xmlGetLastError ();
          g_warning ("%s: Can't parse XSD schema: %s. We don't use it", __FUNCTION__, error->message);

        }
      }
      else
      {
        error = xmlGetLastError ();
        g_warning ("%s: Can't parse XSD schema: %s. We don't use it", __FUNCTION__, error->message);
      }
    }
    else
    {
      error = xmlGetLastError ();
      g_warning ("%s: Can't parse XSD schema: %s. We don't use it", __FUNCTION__, error->message);
    }

  }


  /* parse an validate de XML directives.xml file */
  doc = xmlCtxtReadFile (ctx, file, NULL, XML_PARSE_DTDVALID | XML_PARSE_NOENT | XML_PARSE_RECOVER | XML_PARSE_NOERROR | XML_PARSE_NOWARNING);
  if (validCtxt != NULL)
  {
    if (doc != NULL)
    {
      if (xmlSchemaValidateDoc (validCtxt, doc) != 0)
      {
        error = xmlGetLastError ();
        g_warning ("Could not parse file at %s. Error:%s", file, error->message);
        xsderror = -1;
      }
      else
      {
        xsderror = 0;
      }
    }
    /* Clean up */
    if (parserCtxt)
    {
      xmlSchemaFreeParserCtxt (parserCtxt);
      parserCtxt = NULL;
    }
    if (schema)
    {
      xmlSchemaFree (schema);
      schema = NULL;
    }
    if (validCtxt)
    {
      xmlSchemaFreeValidCtxt (validCtxt);
      validCtxt = NULL;
    }
    if (xmlSchema)
    {
      xmlFreeDoc (xmlSchema);
      xmlSchema = NULL;
    }

  }
  if (!doc)
  {
    error = xmlGetLastError ();
    xmlFreeParserCtxt (ctx);
    g_warning ("Could not parse file at %s. Error:%s", file, error->message);
    return -1;
  }
  if (!ctx->valid)
  {
    error = xmlGetLastError ();
    xmlFreeDoc (doc);
    xmlFreeParserCtxt (ctx);
    g_warning ("Validate error in file: %s. Error %s", file, error->message);
    return -1;                  /* Don't like return in the middle of a function */
  }
  if (xsderror)
  {
    xmlFreeDoc (doc);
    xmlFreeParserCtxt (ctx);
    return -1;                  /* Don't like return in the middle of a function */
  }

  xmlFreeParserCtxt (ctx);

  /* parse the file */
  root = xmlDocGetRootElement (doc);    //we need to know the first element in the tree
  if (strcmp ((gchar *) root->name, OBJECT_DIRECTIVES))
  {
    ossim_debug ("Invalid XML directive file '%s'", file);
    xmlFreeDoc (doc);
    return -1;
  }

  node = root->xmlChildrenNode;
  while (node)                  //while
  {
    if (strcmp ((gchar *) node->name, OBJECT_DIRECTIVE) == 0)   //parse each one of the directives and store it in xml_directive
      sim_xml_directive_new_directive_from_node (xml_directive, node);
    if (strcmp ((gchar *) node->name, OBJECT_GROUPS) == 0)
      sim_xml_directive_new_groups_from_node (xml_directive, node);     // the same with directive groups
    if (strcmp ((gchar *) node->name, OBJECT_TIMETABLES) == 0)
      sim_xml_directive_new_timetables_from_node (xml_directive, node);

    node = node->next;
  }

  GList *lt = xml_directive->_priv->timetables;
  while (lt)
  {
    SimTimetable *tb = lt->data;
    sim_timetable_dump (tb);
    /* assign timetables to directives */
    GList *ld = xml_directive->_priv->directives;
    while (ld)
    {
      SimDirective *directive = (SimDirective *) ld->data;
      gchar *name = sim_directive_get_timetable_name (directive);
      if (name != NULL && strcmp (name, sim_timetable_get_name (tb)) == 0)
      {
        ossim_debug ("Setting timetable %s to directive %s", sim_timetable_get_name (tb), name);
        sim_directive_set_timetable (directive, tb);
      }
      ld = ld->next;
    }

    lt = lt->next;
  }

  //now we have all the directives, and all the groups. But is needed to tell to each directive if it's is inside
  //a group
  list = xml_directive->_priv->groups;
  while (list)
  {
    SimDirectiveGroup *group = (SimDirectiveGroup *) list->data;
    GList *ids = sim_directive_group_get_ids (group);

    while (ids)
    {
      gint id = GPOINTER_TO_INT (ids->data);
      SimDirective *directive = find_directive (xml_directive, id);

      if (directive)
        sim_directive_append_group (directive, group);

      ids = ids->next;
    }

    list = list->next;
  }

  xmlFreeDoc (doc);
  /* Free the elements */
  if (parserCtxt)
  {
    xmlSchemaFreeParserCtxt (parserCtxt);
  }
  if (schema)
  {
    xmlSchemaFree (schema);
  }
  if (validCtxt)
  {
    xmlSchemaFreeValidCtxt (validCtxt);
  }
  if (xmlSchema)
  {
    xmlFreeDoc (xmlSchema);
  }

  return 0;
}

/**
 * sim_xml_directive_changed
 * @xmldirect: XML directive
 *
 * Emit the "changed" signal for the given XML directive
 */
void
sim_xml_directive_changed (SimXmlDirective * xmldirect)
{
  g_return_if_fail (SIM_IS_XML_DIRECTIVE (xmldirect));
  g_signal_emit (G_OBJECT (xmldirect), xmldirect_signals[SIM_XML_DIRECTIVE_CHANGED], 0);
}

/**
 * sim_xml_directive_reload
 * @xmldirect: XML directive.
 *
 * Reload the given XML directive from its original place, discarding
 * all changes that may have happened.
 */
void
sim_xml_directive_reload (SimXmlDirective * xmldirect)
{
  // unused parameter
  (void) xmldirect;

  /* FIXME: implement */
}

/**
 * sim_xml_directive_save
 * @xmldirect: XML directive.
 * @file: FILE to save the XML directive to.
 *
 * Save the given XML directive to disk.
 */
gboolean
sim_xml_directive_save (SimXmlDirective * xmldirect, const gchar * file)
{
  gchar *xml;
  gboolean result;

  g_return_val_if_fail (SIM_IS_XML_DIRECTIVE (xmldirect), FALSE);

  xml = sim_xml_directive_to_string (xmldirect);
  if (xml)
  {
    result = sim_file_save (file, xml, strlen ((char *) xml));
    g_free (xml);
  }
  else
    result = FALSE;

  return result;
}

/**
 * sim_xml_directive_to_string
 * @xmldirect: a #SimXmlDirective object.
 *
 * Get the given XML directive contents as a XML string.
 *
 * Returns: the XML string representing the structure and contents of the
 * given #SimXmlDirective object. The returned value must be freed when no
 * longer needed.
 */
gchar *
sim_xml_directive_to_string (SimXmlDirective * xmldirect)
{
  xmlDocPtr doc;
  xmlNodePtr root;
  xmlChar *xml;
  gint size;
  gchar *retval;

  g_return_val_if_fail (SIM_IS_XML_DIRECTIVE (xmldirect), NULL);

  /* create the top node */
  doc = xmlNewDoc ((xmlChar *) "1.0");
  root = xmlNewDocNode (doc, NULL, (xmlChar *) OBJECT_DIRECTIVES, NULL);
  xmlDocSetRootElement (doc, root);

  /* save to memory */
  xmlDocDumpMemory (doc, &xml, &size);
  xmlFreeDoc (doc);
  if (!xml)
  {
    g_message ("Could not dump XML file to memory");
    return NULL;
  }

  retval = g_strdup ((gchar *) xml);
  free (xml);

  return retval;
}

/*
 *
 * Parameter node is the same that a single directive inside the directives.xml. Its needed to extract
 * all the data from node and insert it into a SimDirective object to be
 * able to return it.
 *
 * http://xmlsoft.org/html/libxml-tree.html#xmlNode
 *
 * Returns NULL on error and don't load the directive at all.
 */
SimDirective *
sim_xml_directive_new_directive_from_node (SimXmlDirective * xmldirect, xmlNodePtr node)
{
  SimDirective *directive;
  GNode *rule_root = NULL;
  xmlNodePtr children;
  gchar *name = NULL;
  gchar *value = NULL;
  gchar *aux = NULL;
  gchar **values;
  gint priority = 0;
  gint id = 0;
  gint i;                       //loop iterator.

  //Group Alarm needed variables.
  gint group_alarm_timeout = 0;
  gboolean group_alarm_store_backlog = FALSE;
  gboolean group_alarm_by_src_ip = FALSE;
  gboolean group_alarm_by_src_port = FALSE;
  gboolean group_alarm_by_dst_ip = FALSE;
  gboolean group_alarm_by_dst_port = FALSE;

  g_return_val_if_fail (SIM_IS_XML_DIRECTIVE (xmldirect), NULL);
  g_return_val_if_fail (node != NULL, NULL);

  if (strcmp ((gchar *) node->name, (gchar *) OBJECT_DIRECTIVE))
  {
    g_message ("Invalid directive node %s", node->name);
    return NULL;
  }

  aux = (char *) xmlGetProp (node, (xmlChar *) PROPERTY_ID);    //get the id of that directive
  if (aux)
  {
    id = atoi (aux);
    g_free (aux);
    ossim_debug ("Loading directive: %d", id);
  }

  aux = (char *) xmlGetProp (node, (xmlChar *) PROPERTY_NAME);
  if (aux)
  {
    name = g_strdup_printf ("directive_event: %s", aux);
    g_free (aux);
  }

  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_PRIORITY)))
  {
    priority = strtol (value, (char **) NULL, 10);
    xmlFree (value);
  }

  // Alarm group modification. -- INIT
  // group alarm store backlogs
#if 0
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_GROUP_ALARM_STORE_BACKLOG)))
  {
    if (!g_ascii_strcasecmp (value, "TRUE"))
      group_alarm_store_backlog = TRUE;
//    g_message("CRG - group_alarm_store_backlog SET TO :%d directive:%d",group_alarm_store_backlog,sim_directive_get_id(directive));
    xmlFree (value);
  }
#endif
  //group alarm timeout
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_GROUP_ALARM_TIME_OUT)))
  {
    group_alarm_timeout = strtol (value, (char **) NULL, 10);
    //g_message("CRG - group_alarm_timeout SET TO :%d directive:%d",group_alarm_timeout,sim_directive_get_id(directive));
    xmlFree (value);
  }
  //group alarm group by.

  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_GROUP_ALARM_BY)))
  {
    values = g_strsplit (value, SIM_DELIMITER_LIST, 0);
    for (i = 0; values[i] != NULL; i++)
    {
      if (g_ascii_strcasecmp (values[i], "src_ip") == 0)
        group_alarm_by_src_ip = TRUE;
      if (g_ascii_strcasecmp (values[i], "src_port") == 0)
        group_alarm_by_src_port = TRUE;
      if (g_ascii_strcasecmp (values[i], "dst_ip") == 0)
        group_alarm_by_dst_ip = TRUE;
      if (g_ascii_strcasecmp (values[i], "dst_port") == 0)
        group_alarm_by_dst_port = TRUE;
    }
/*    g_message("CRG - group by: src_ip:%d, src_port:%d, dst_ip:%d, dst_port:%d -  directive:%d",group_alarm_by_src_ip,group_alarm_by_src_port,\
      group_alarm_by_dst_ip,group_alarm_by_dst_port,sim_directive_get_id(directive));*/
    xmlFree (value);
    g_strfreev (values);
  }
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_CTX_NAME)))
  {
    ossim_debug ("contexname not implemented: valud =>%s", value);

    xmlFree (value);

  }
  
  directive = sim_directive_new ();
  sim_directive_set_id (directive, id);
  sim_directive_set_name (directive, name);
  if (name)
    g_free (name);
  sim_directive_set_priority (directive, priority);
  sim_directive_set_group_alarm_store_backlog (directive, group_alarm_store_backlog);
  sim_directive_set_group_alarm_timeout (directive, group_alarm_timeout);
  sim_directive_set_group_alarm_by (directive, group_alarm_by_src_ip, group_alarm_by_src_port, group_alarm_by_dst_ip, group_alarm_by_dst_port);

  /* set the timetable name */
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_TIMETABLE)))
  {
    sim_directive_set_timetable_name (directive, value);
    xmlFree (value);
  }

  children = node->xmlChildrenNode;     // xmlChildrenNode is a #define to children. It's the same to do node->children
  while (children)
  {
    if (!strcmp ((gchar *) children->name, OBJECT_RULE))
    {
      rule_root = sim_xml_directive_new_rule_from_node (xmldirect, children, NULL, 1, id);      //pass all the directive to the
      //function and (separate && store) it
      //into individual rules
      if (!rule_root)
      {
        g_message ("Error: There is a problem in directive: %d. Aborting load of that directive", id);
        return NULL;
      }
    }
    children = children->next;
  }

  /* The time out of the first rule is set to directive time out
   * if the rule have occurence > 1, otherwise is set to 0.
   */
  if (rule_root)
  {
    SimRule *rule = (SimRule *) rule_root->data;
    gint time_out = sim_rule_get_time_out (rule);
    gint occurrence = sim_rule_get_occurrence (rule);
    if (occurrence > 1)
      sim_directive_set_time_out (directive, time_out);
    else
      sim_directive_set_time_out (directive, 0);
  }
  sim_directive_set_root_node (directive, rule_root);

  xmldirect->_priv->directives = g_list_append (xmldirect->_priv->directives, directive);

  return directive;
}

/*
 *  We will group the following keywords in this function:
 *  filename, username, password, userdata1, userdata2.....userdata9
 */
static gboolean
sim_xml_directive_set_rule_generic_text (SimRule * rule, gchar * value, gchar * field_type)     // field_type =PROPERTY_FILENAME, PROPERTY_SRC_IP....
{
  gchar **values;
  gchar **level;
  gint i;
  gboolean field_neg = FALSE;
  gchar *token_value;           //this will be each one of the strings, between "," and "," from value.

  g_return_val_if_fail (SIM_IS_RULE (rule), FALSE);
  g_return_val_if_fail (value != NULL, FALSE);

  values = g_strsplit (value, SIM_DELIMITER_LIST, 0);
  for (i = 0; values[i] != NULL; i++)
  {
    if (values[i][0] == '!')
    {
      field_neg = TRUE;
      token_value = values[i] + 1;      //removing the "!"...
    }
    else
    {
      field_neg = FALSE;
      token_value = values[i];
    }

    if (strstr (token_value, SIM_DELIMITER_LEVEL))      //if this token doesn't refers to the 1st rule level...
    {
      SimRuleVar *var = g_new0 (SimRuleVar, 1);

      level = g_strsplit (token_value, SIM_DELIMITER_LEVEL, 0); //separate into 2 tokens

      var->type = sim_get_rule_var_from_char (level[1]);
      var->attr = sim_xml_directive_get_rule_var_from_property (field_type);
      if (var->attr == SIM_RULE_VAR_NONE)
      {
        g_strfreev (values);
        return FALSE;
      }

      if (sim_string_is_number (level[0], 0))
      {
        var->level = atoi (level[0]);
      }
      else                      // It can be something like "http://foo.bar", so it does not refer to another level
      {
        g_strfreev (level);
        g_free (var);

        if (field_neg)
          sim_rule_append_generic_text_not (rule, g_strdup (token_value), sim_xml_directive_get_rule_var_from_property (field_type));
        else
          sim_rule_append_generic_text (rule, g_strdup (token_value), sim_xml_directive_get_rule_var_from_property (field_type));

        continue;
      }

      if (field_neg)
        var->negated = TRUE;
      else
        var->negated = FALSE;

      sim_rule_append_var (rule, var);
      g_strfreev (level);
    }
    else if (!strcmp (token_value, SIM_WILDCARD_ANY))
    {
      if (field_neg)            //we can't negate "ANY"
      {
        g_strfreev (values);
        return FALSE;
      }
      //this "generic" function is valid only for the keywords filename, username, userdata1...userdata9
      sim_rule_append_generic_text (rule, g_strdup (token_value), sim_xml_directive_get_rule_var_from_property (field_type));
    }
    else                        // this token IS the 1st level
    {
      if (field_neg)
        sim_rule_append_generic_text_not (rule, g_strdup (token_value), sim_xml_directive_get_rule_var_from_property (field_type));
      else
        sim_rule_append_generic_text (rule, g_strdup (token_value), sim_xml_directive_get_rule_var_from_property (field_type));
    }
  }

  g_strfreev (values);
  return TRUE;
}

static gboolean
sim_xml_directive_set_generic_list (SimRule * rule, gchar * value, gchar * field_type)
{
  gchar **values;
  gchar **level;
  gint i;
  gboolean number_neg = FALSE;  //if the number is negated, this will be YES (just for that number, not the others).
  gchar *token_value;           //this will be each one of the strings, between "," and "," from value.

  g_return_val_if_fail (SIM_IS_RULE (rule), FALSE);
  g_return_val_if_fail (value != NULL, FALSE);

  values = g_strsplit (value, SIM_DELIMITER_LIST, 0);   // separate each of the individual number is delimited with ","
  for (i = 0; values[i] != NULL; i++)
  {
    if (values[i][0] == '!')    // each number could be negated. We'll store it in other place.
    {
      number_neg = TRUE;
      token_value = values[i] + 1;      // removing the "!"...
    }
    else
    {
      number_neg = FALSE;
      token_value = values[i];
    }

    if (strstr (token_value, SIM_DELIMITER_LEVEL))      //if this token doesn't refers to the 1st rule level...
    {
      SimRuleVar *var = g_new0 (SimRuleVar, 1); //here is stored the level to which this number make
      //reference and what kind of token is (src_ia, protocol...)

      level = g_strsplit (token_value, SIM_DELIMITER_LEVEL, 0); //separate the (ie) 1:"<const string>" into 2 tokens

      var->type = sim_get_rule_var_from_char (level[1]);        //level[1] = "<const string>"
      var->attr = sim_xml_directive_get_rule_var_from_property (field_type);
      if (sim_string_is_number (level[0], 0))
        var->level = atoi (level[0]);
      else
      {
        g_strfreev (level);
        g_free (var);
        return FALSE;
      }

      if (number_neg)
        var->negated = TRUE;
      else
        var->negated = FALSE;

      sim_rule_append_var (rule, var);  //we don't need to call to sim_rule_append_xxx()
      g_strfreev (level);       //because we aren't going to store nothing as we will read
    }                           //the "xxx" from other level.
    else
    {
      if (strcmp (token_value, SIM_WILDCARD_ANY))
      {
        if (sim_string_is_number (token_value, 0))
        {
          switch (sim_xml_directive_get_rule_var_from_property (field_type))
          {
            case SIM_RULE_VAR_PLUGIN_ID:
              if (number_neg)
                sim_rule_add_plugin_id_not (rule, atoi (token_value));
              else
                sim_rule_add_plugin_id (rule, atoi (token_value));
              break;
            case SIM_RULE_VAR_PLUGIN_SID:
              if (number_neg)
                sim_rule_add_plugin_sid_not (rule, atoi (token_value));
              else
                sim_rule_add_plugin_sid (rule, atoi (token_value));
              break;
            case SIM_RULE_VAR_PRODUCT:
              if (number_neg)
                sim_rule_add_product_not (rule, atoi (token_value));
              else
                sim_rule_add_product (rule, atoi (token_value));
              break;
            case SIM_RULE_VAR_CATEGORY:
              if (number_neg)
                sim_rule_add_category_not (rule, atoi (token_value));
              else
                sim_rule_add_category (rule, atoi (token_value));
              break;
            case SIM_RULE_VAR_SUBCATEGORY:
              if (number_neg)
                sim_rule_add_subcategory_not (rule, atoi (token_value));
              else
                sim_rule_add_subcategory (rule, atoi (token_value));
              break;
            default:
              g_return_val_if_fail (0, FALSE);
              break;
          }
        }
        else
        {
          g_strfreev (values);
          return FALSE;
        }
      }
    }
  }
  g_strfreev (values);

  return TRUE;
}

/*
 *
 *
 *
 *
 */
static gboolean
sim_xml_directive_set_rule_ips (SimRule * rule, gchar * value, gboolean are_src_ips)
{
  gchar **values;
  gchar *token_value;           //this will be each one of the strings, between "," and "," from value.
  gboolean addr_neg;            //if the address is negated, this will be YES (just for that address, not the others).
  gchar **level;
  gint i;

  g_return_val_if_fail (SIM_IS_RULE (rule), FALSE);
  g_return_val_if_fail (value != NULL, FALSE);

  values = g_strsplit (value, SIM_DELIMITER_LIST, 0);   //split into ","...
  for (i = 0; values[i] != NULL; i++)
  {
    if (values[i][0] == '!')
    {
      addr_neg = TRUE;
      token_value = values[i] + 1;
    }
    else
    {
      addr_neg = FALSE;
      token_value = values[i];
    }

    /* Check if not in the first level. Must find also SRC_IP or DST_IP to be sure it isn't an ipv6 address */
    if (strstr (token_value, SIM_DELIMITER_LEVEL) && (strstr (token_value, SIM_SRC_IP_CONST) || strstr (token_value, SIM_DST_IP_CONST)))
    {
      SimRuleVar *var = g_new0 (SimRuleVar, 1);

      level = g_strsplit (token_value, SIM_DELIMITER_LEVEL, 0);

      var->type = sim_get_rule_var_from_char (level[1]);
      var->attr = are_src_ips ? SIM_RULE_VAR_SRC_IA : SIM_RULE_VAR_DST_IA;
      if (sim_string_is_number (level[0], 0))
      {
        var->level = atoi (level[0]);
      }
      else
      {
        g_strfreev (level);
        g_free (var);
        return FALSE;
      }

      if (addr_neg)
        var->negated = TRUE;
      else
        var->negated = FALSE;

      sim_rule_append_var (rule, var);

      g_strfreev (level);
    }
    else if (strcmp (token_value, SIM_WILDCARD_ANY) == 0)       // "ANY"
    {
      if (addr_neg)             //we can't negate "ANY" address!
      {
        g_strfreev (values);
        return FALSE;
      }
      /* "ANY" match with anything so add nothing to rule */
    }
    else if (strcmp (token_value, SIM_HOME_NET_CONST) == 0)     //usually, "HOME_NET"
    {
      if (addr_neg)
        if (are_src_ips)
          sim_rule_set_src_is_home_net_not (rule, TRUE);
        else
          sim_rule_set_dst_is_home_net_not (rule, TRUE);
      else if (are_src_ips)
        sim_rule_set_src_is_home_net (rule, TRUE);
      else
        sim_rule_set_dst_is_home_net (rule, TRUE);
    }
    else
    {
      SimInet *inet = sim_inet_new_from_string (token_value);
      if (inet)
      {
        if (addr_neg)
          if (are_src_ips)
            sim_rule_add_secure_src_inet_not (rule, inet);
          else
            sim_rule_add_secure_dst_inet_not (rule, inet);
        else if (are_src_ips)
          sim_rule_add_secure_src_inet (rule, inet);
        else
          sim_rule_add_secure_dst_inet (rule, inet);

        g_object_unref (inet);
      }
      else                      // ossim also accepts network names and host names defined in assets.
      {
        if (addr_neg)
          if (are_src_ips)
            sim_rule_add_expand_src_asset_name_not (rule, token_value);
          else
            sim_rule_add_expand_dst_asset_name_not (rule, token_value);
        else if (are_src_ips)
          sim_rule_add_expand_src_asset_name (rule, token_value);
        else
          sim_rule_add_expand_dst_asset_name (rule, token_value);
      }
    }
  }
  g_strfreev (values);

  return TRUE;
}

/*
 *
 *
 *
 *
 */
static gboolean
sim_xml_directive_set_rule_ports (SimRule * rule, gchar * value, gboolean are_src_ports)
{
  gchar **values;
  gchar **level;
  gchar **range;
  gint i;
  gchar *token_value;
  gboolean port_neg;

  g_return_val_if_fail (SIM_IS_RULE (rule), FALSE);
  g_return_val_if_fail (value != NULL, FALSE);

  values = g_strsplit (value, SIM_DELIMITER_LIST, 0);
  for (i = 0; values[i] != NULL; i++)
  {
    if (values[i][0] == '!')
    {
      port_neg = TRUE;
      token_value = values[i] + 1;
    }
    else
    {
      port_neg = FALSE;
      token_value = values[i];
    }

    if (strstr (token_value, SIM_DELIMITER_LEVEL))
    {
      SimRuleVar *var = g_new0 (SimRuleVar, 1);

      level = g_strsplit (token_value, SIM_DELIMITER_LEVEL, 0);

      var->type = sim_get_rule_var_from_char (level[1]);
      var->attr = are_src_ports ? SIM_RULE_VAR_SRC_PORT : SIM_RULE_VAR_DST_PORT;
      if (sim_string_is_number (level[0], 0))
        var->level = atoi (level[0]);
      else
      {
        g_strfreev (level);
        g_strfreev (values);
        g_free (var);
        return FALSE;
      }

      if (port_neg)
        var->negated = TRUE;
      else
        var->negated = FALSE;

      ossim_debug ("%s: rule name: %s", __func__, sim_rule_get_name (rule));
      ossim_debug ("%s: type: %d", __func__, var->type);
      ossim_debug ("%s: attr: %d", __func__, var->attr);
      ossim_debug ("%s: negated: %d", __func__, var->negated);

      sim_rule_append_var (rule, var);

      g_strfreev (level);
    }
    else
    {
      if (strcmp (token_value, SIM_WILDCARD_ANY))
      {
        if (strstr (token_value, SIM_DELIMITER_RANGE))  //multiple ports in a range. "1-5"
        {
          gint start, end, j = 0;

          range = g_strsplit (token_value, SIM_DELIMITER_RANGE, 0);
          if (!sim_string_is_number (range[0], 0) || !sim_string_is_number (range[1], 0))
          {
            g_strfreev (range);
            g_strfreev (values);
            return FALSE;
          }

          start = atoi (range[0]);
          end = atoi (range[1]);

          for (j = start; j <= end; j++)
          {
            if (port_neg)       //if ports are ie. !1-5, all the ports in that range will be negated.
              if (are_src_ports)
                sim_rule_add_src_port_not (rule, j);
              else
                sim_rule_add_dst_port_not (rule, j);
            else if (are_src_ports)
              sim_rule_add_dst_port_not (rule, j);
            else
              sim_rule_add_dst_port (rule, j);
          }
          g_strfreev (range);
        }
        else
        {
          if (sim_string_is_number (token_value, 0))
          {
            if (port_neg)
              if (are_src_ports)
                sim_rule_add_src_port_not (rule, atoi (token_value));
              else
                sim_rule_add_dst_port_not (rule, atoi (token_value));
            else if (are_src_ports)
              sim_rule_add_src_port (rule, atoi (token_value));
            else
              sim_rule_add_dst_port (rule, atoi (token_value));
          }
          else
          {
            g_strfreev (values);
            return FALSE;
          }
        }
      }
    }
  }
  g_strfreev (values);
  return TRUE;
}

/*
 *
 *
 *
 *
 */
static gboolean
sim_xml_directive_set_rule_protocol (SimRule * rule, gchar * value)
{
  gchar **values;
  gchar **level;
  gint i;
  gchar *token_value;
  gboolean proto_neg;

  g_return_val_if_fail (SIM_IS_RULE (rule), FALSE);
  g_return_val_if_fail (value != NULL, FALSE);

  values = g_strsplit (value, SIM_DELIMITER_LIST, 0);
  for (i = 0; values[i] != NULL; i++)
  {
    if (values[i][0] == '!')
    {
      proto_neg = TRUE;
      token_value = values[i] + 1;
    }
    else
    {
      proto_neg = FALSE;
      token_value = values[i];
    }

    if (strstr (token_value, SIM_DELIMITER_LEVEL))
    {
      SimRuleVar *var = g_new0 (SimRuleVar, 1);

      level = g_strsplit (token_value, SIM_DELIMITER_LEVEL, 0);

      var->type = sim_get_rule_var_from_char (level[1]);
      var->attr = SIM_RULE_VAR_PROTOCOL;
      if (sim_string_is_number (level[0], 0))
        var->level = atoi (level[0]);
      else
      {
        g_strfreev (level);
        g_strfreev (values);
        g_free (var);
        return FALSE;
      }

      if (proto_neg)
        var->negated = TRUE;
      else
        var->negated = FALSE;

      sim_rule_append_var (rule, var);

      g_strfreev (level);
    }
    else if (strcmp (token_value, SIM_WILDCARD_ANY))
    {
      if (sim_string_is_number (token_value, 0))
      {
        if (proto_neg)
          sim_rule_add_protocol_not (rule, atoi (token_value));
        else
          sim_rule_add_protocol (rule, atoi (token_value));
      }
      else
      {
        int proto = sim_protocol_get_type_from_str (token_value);
        if (proto != SIM_PROTOCOL_TYPE_NONE)
        {
          if (proto_neg)
            sim_rule_add_protocol_not (rule, proto);
          else
            sim_rule_add_protocol (rule, proto);
        }
        else
        {
          g_strfreev (values);
          return FALSE;
        }
      }
    }
  }
  g_strfreev (values);
  return TRUE;
}

/*
 *
 *
 *
 *
 */
static gboolean
sim_xml_directive_set_rule_sensors (SimXmlDirective * xmldirect, SimRule * rule, gchar * value)
{
  SimSensor *sensor;
  gchar **values;
  gchar **level;
  gint i;
  gchar *token_value;
  gboolean sensor_neg;

  g_return_val_if_fail (SIM_IS_XML_DIRECTIVE (xmldirect), FALSE);
  g_return_val_if_fail (SIM_IS_RULE (rule), FALSE);
  g_return_val_if_fail (value != NULL, FALSE);

  values = g_strsplit (value, SIM_DELIMITER_LIST, 0);   //split into ","...
  for (i = 0; values[i] != NULL; i++)
  {
    if (values[i][0] == '!')
    {
      sensor_neg = TRUE;
      token_value = values[i] + 1;
    }
    else
    {
      sensor_neg = FALSE;
      token_value = values[i];
    }

    /* Check if not in the first level. Must find "SENSOR" to be sure it isn't an ipv6 address */
    if (strstr (token_value, SIM_DELIMITER_LEVEL) && strstr (token_value, SIM_SENSOR_CONST))
    {
      SimRuleVar *var = g_new0 (SimRuleVar, 1);

      level = g_strsplit (token_value, SIM_DELIMITER_LEVEL, 0);

      var->type = sim_get_rule_var_from_char (level[1]);
      var->attr = SIM_RULE_VAR_SENSOR;
      if (sim_string_is_number (level[0], 0))
        var->level = atoi (level[0]);
      else
      {
        g_strfreev (level);
        g_strfreev (values);
        g_free (var);
        return FALSE;
      }

      if (sensor_neg)
        var->negated = TRUE;
      else
        var->negated = FALSE;

      sim_rule_append_var (rule, var);

      g_strfreev (level);
    }
    else if (strcmp (token_value, SIM_WILDCARD_ANY))
    {
      sensor = sim_sensor_new_from_hostname (token_value);
      if (sensor)
      {
        if (sensor_neg)
          sim_rule_add_sensor_not (rule, sensor);
        else
          sim_rule_add_sensor (rule, sensor);
      }
      else
      {
        if (sensor_neg)
          sim_rule_add_expand_sensor_name_not (rule, token_value);
        else
          sim_rule_add_expand_sensor_name (rule, token_value);
      }
    }
  }

  g_strfreev (values);
  return TRUE;
}

static gboolean
sim_xml_directive_set_rule_entity (SimXmlDirective * xmldirect, SimRule * rule, gchar * value)
{
  gchar **values;
  gchar **level;
  gint i;
  gchar *token_value;
  gboolean negated;

  g_return_val_if_fail (SIM_IS_XML_DIRECTIVE (xmldirect), FALSE);
  g_return_val_if_fail (SIM_IS_RULE (rule), FALSE);
  g_return_val_if_fail (value != NULL, FALSE);

  values = g_strsplit (value, SIM_DELIMITER_LIST, 0);   //split into ","...
  for (i = 0; values[i] != NULL; i++)
  {
    if (values[i][0] == '!')
    {
      negated = TRUE;
      token_value = values[i] + 1;
    }
    else
    {
      negated = FALSE;
      token_value = values[i];
    }

    /* Check if not in the first level. Must find "SENSOR" to be sure it isn't an ipv6 address */
    if (strstr (token_value, SIM_DELIMITER_LEVEL))
    {
      SimRuleVar *var = g_new0 (SimRuleVar, 1);

      level = g_strsplit (token_value, SIM_DELIMITER_LEVEL, 0);

      var->type = sim_get_rule_var_from_char (level[1]);
      var->attr = SIM_RULE_VAR_ENTITY;
      if (sim_string_is_number (level[0], 0))
        var->level = atoi (level[0]);
      else
      {
        g_strfreev (level);
        g_strfreev (values);
        g_free (var);
        return FALSE;
      }

      if (negated)
        var->negated = TRUE;
      else
        var->negated = FALSE;

      sim_rule_append_var (rule, var);

      g_strfreev (level);
    }
    else if (strcmp (token_value, SIM_WILDCARD_ANY))
    {
      if (negated)
        sim_rule_add_expand_entity_not (rule, token_value);
      else
        sim_rule_add_expand_entity (rule, token_value);
    }
  }

  g_strfreev (values);

  return TRUE;
}


static gboolean
sim_xml_directive_set_rule_suppress (SimXmlDirective * xmldirect, SimRule * rule, gchar * value)
{
  gchar **values_id, **values_sid;
  gint plugin_id;
  gint i, j;

  g_return_val_if_fail (xmldirect != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_XML_DIRECTIVE (xmldirect), FALSE);
  g_return_val_if_fail (rule != NULL, FALSE);
  g_return_val_if_fail (SIM_IS_RULE (rule), FALSE);
  g_return_val_if_fail (value != NULL, FALSE);

  values_id = g_strsplit (value, SIM_DELIMITER_ELEMENT_LIST, 0);
  for (i = 0; values_id[i] != NULL; i++)
  {
    values_sid = g_strsplit (values_id[i], SIM_DELIMITER_LIST, 0);
    if (values_sid[0] == NULL || !sim_string_is_number (values_sid[0], 0))
    {
      g_strfreev (values_sid);
      g_strfreev (values_id);
      return FALSE;
    }

    plugin_id = atoi (values_sid[0]);

    for (j = 1; values_sid[j] != NULL; j++)
    {
      if (sim_string_is_number (values_sid[j], 0))
      {
        sim_rule_add_suppress (rule, plugin_id, atoi (values_sid[j]));
      }
      else
      {
        g_strfreev (values_sid);
        g_strfreev (values_id);
        return FALSE;
      }
    }
    g_strfreev (values_sid);
  }
  g_strfreev (values_id);

  return TRUE;
}

//FIXME: create this function to alert when a directive is bad-formed
//sim_xml_directive_check_rule (Gnode *rule)
//{
//
//}

/*
 * Create a GNode element
 *
 * Returns NULL on error.
 *
 * GNode *root: first  time this should be called with NULL. after that, recursively
 * it will pass the pointer to the node.
 *
 * FIXME: I don't like this (an other) function(s). Each time I want to add a new keyword is necessary to modify
 * lots of thins. This could be done with some kind of table where is just needed to add a new keyword and
 * it's type to get it inserted in directives. The major absurdity is the needed to use 9 (9!!!) functions to
 * store the values from userdata1, userdata2... instead a single function and table where it points to the right variable.
 */
GNode *
sim_xml_directive_new_rule_from_node (SimXmlDirective * xmldirect, xmlNodePtr node, GNode * root, gint level, gint directive_id)
{
  SimEventType type = SIM_EVENT_TYPE_NONE;
  SimRule *rule;
  GNode *rule_node;
  xmlNodePtr children;
  xmlNodePtr children_rules;
  xmlAttrPtr attr;
  const gchar *attr_name;
  gchar *value;
  SimConditionType condition = SIM_CONDITION_TYPE_NONE;
  gchar *par_value = NULL;
  gint interval = 0;
  gboolean absolute = FALSE;
  gint sticky_different = SIM_RULE_VAR_NONE;
  gboolean not = FALSE;
  gint priority = 1;
  gint reliability = 1;
  gboolean rel_abs = TRUE;
  gint time_out = 0;
  gint occurrence = 1;
  GHashTable *rule_ids, *rule_ids_not, *rule_sids;

  g_return_val_if_fail (SIM_IS_XML_DIRECTIVE (xmldirect), NULL);
  g_return_val_if_fail (node != NULL, NULL);

  if (strcmp ((gchar *) node->name, OBJECT_RULE))       //This must be a "rule" always.
  {
    g_message ("Invalid rule node %s", node->name);
    return NULL;
  }

  rule = sim_rule_new ();

  //now we're going to extract all the data from the node and store it into variables so we can later return it.
  //This node can be all the rules inside directive (the first time it enters in this function) or just an
  //internal "rule" thanks to the recursive programming
  for (attr = node->properties; attr; attr = attr->next)
  {
    value = (gchar *) attr->children->content;
    attr_name = (const gchar *) attr->name;

    if (!strcmp (attr_name, PROPERTY_TYPE))
    {
      if (!g_ascii_strcasecmp (value, "detector"))
        type = SIM_EVENT_TYPE_DETECTOR;
      else if (!g_ascii_strcasecmp (value, "monitor"))
        type = SIM_EVENT_TYPE_MONITOR;
      else
        type = SIM_EVENT_TYPE_NONE;

      if (type == SIM_EVENT_TYPE_NONE)
      {
        g_message ("Error. there is a problem at the 'type' field in the directive");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_STICKY_DIFFERENT))
    {
      sticky_different = sim_get_rule_var_from_char (value);
    }
    else if (!strcmp (attr_name, PROPERTY_NOT))
    {
      if (!g_ascii_strcasecmp (value, "TRUE"))
        not = TRUE;
    }
    else if (!strcmp (attr_name, PROPERTY_NAME))
    {
      sim_rule_set_name (rule, value);
    }
    else if (!strcmp (attr_name, PROPERTY_PRIORITY))
    {
      if (sim_string_is_number (value, 0))
        priority = strtol (value, (char **) NULL, 10);
      else
      {
        g_message ("Error. there is a problem at the Priority field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_RELIABILITY))
    {
      gboolean aux = TRUE;
      gchar *tempi = value;     //we don't wan't to loose the pointer.....
      if (value[0] == '+')
      {
        rel_abs = FALSE;
        value++;                // ++ to the pointer so now "value" points to the number string and we can check it.
        if (sim_string_is_number (value, 0))
          reliability = atoi (value);
        else
          aux = FALSE;
      }
      else
      {
        if (sim_string_is_number (value, 0))
          reliability = atoi (value);
        else
          aux = FALSE;
      }

      value = tempi;
      if (aux == FALSE)
      {
        g_message ("Error. there is a problem at the Reliability field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_CONDITION))
    {
      condition = sim_condition_get_type_from_str (value);
    }
    else if (!strcmp (attr_name, PROPERTY_VALUE))
    {
      par_value = g_strdup (value);
    }
    else if (!strcmp (attr_name, PROPERTY_INTERVAL))
    {
      if (sim_string_is_number (value, 0))
      {
        interval = strtol (value, (char **) NULL, 10);
      }
      else
      {
        g_message ("Error. there is a problem at the Interval field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_ABSOLUTE))
    {
      if (!g_ascii_strcasecmp (value, "TRUE"))
        absolute = TRUE;
    }
    else if (!strcmp (attr_name, PROPERTY_TIME_OUT))
    {
      if (sim_string_is_number (value, 0))
      {
        time_out = strtol (value, (char **) NULL, 10);
      }
      else
      {
        g_message ("Error. there is a problem at the 'Absolute' field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_OCCURRENCE))
    {
      if (sim_string_is_number (value, 0))
      {
        occurrence = strtol (value, (char **) NULL, 10);

        // Only allow single occurrences in first level.
        if ((occurrence != 1) && (level == 1))
        {
          g_message ("No single occurrence constraint satisfied in first level for directive %d, I'll fix that for you Dave", directive_id);
          occurrence = 1;
        }
      }
      else
      {
        g_message ("Error. there is a problem at the Occurrence field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_PLUGIN_ID))
    {
      if (!sim_xml_directive_set_generic_list (rule, value, PROPERTY_PLUGIN_ID))
      {
        g_message ("Error. there is a problem at the Plugin_id field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_PLUGIN_SID))
    {
      if (!sim_xml_directive_set_generic_list (rule, value, PROPERTY_PLUGIN_SID))
      {
        g_message ("Error. there is a problem at the Plugin_sid field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_SRC_IP))
    {
      if (!sim_xml_directive_set_rule_ips (rule, value, TRUE))
      {
        g_message ("Error. there is a problem at the src_ip field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_DST_IP))
    {
      if (!sim_xml_directive_set_rule_ips (rule, value, FALSE))
      {
        g_message ("Error. there is a problem at the dst_ip field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_SRC_PORT))
    {
      if (!sim_xml_directive_set_rule_ports (rule, value, TRUE))
      {
        g_message ("Error. there is a problem at the src_port field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_DST_PORT))
    {
      if (!sim_xml_directive_set_rule_ports (rule, value, FALSE))
      {
        g_message ("Error. there is a problem at the dst_port field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_PROTOCOL))
    {
      if (!sim_xml_directive_set_rule_protocol (rule, value))
      {
        g_message ("Error. there is a problem at the Protocol field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_SENSOR))
    {
      if (!sim_xml_directive_set_rule_sensors (xmldirect, rule, value))
      {
        g_message ("Error. there is a problem at the Sensor field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_ENTITY))
    {
      if (!sim_xml_directive_set_rule_entity (xmldirect, rule, value))
      {
        g_message ("Error. there is a problem at the Entity field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_PRODUCT))
    {
      if (!sim_xml_directive_set_generic_list (rule, value, PROPERTY_PRODUCT))
      {
        g_message ("Error. there is a problem at the Product field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_CATEGORY))
    {
      if (!sim_xml_directive_set_generic_list (rule, value, PROPERTY_CATEGORY))
      {
        g_message ("Error. there is a problem at the Category field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_SUBCATEGORY))
    {
      if (!sim_xml_directive_set_generic_list (rule, value, PROPERTY_SUBCATEGORY))
      {
        g_message ("Error. there is a problem at the Subcategory field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_SUPPRESS))
    {
      if (!sim_xml_directive_set_rule_suppress (xmldirect, rule, value))
      {
        g_message ("Error. there is a problem at the Suppress field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_FROM_REP))
    {
      if (!g_ascii_strcasecmp (value, "TRUE"))
        sim_rule_set_from_rep (rule, TRUE);
    }
    else if (!strcmp (attr_name, PROPERTY_TO_REP))
    {
      if (!g_ascii_strcasecmp (value, "TRUE"))
        sim_rule_set_to_rep (rule, TRUE);
    }
    else if (!strcmp (attr_name, PROPERTY_FROM_REP_MIN_REL))
    {
      if (sim_string_is_number (value, 0))
        sim_rule_set_from_rep_min_rel (rule, atoi (value));
      else
      {
        g_message ("Error. there is a problem at the From reputation minimum reliability field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_TO_REP_MIN_REL))
    {
      if (sim_string_is_number (value, 0))
        sim_rule_set_to_rep_min_rel (rule, atoi (value));
      else
      {
        g_message ("Error. there is a problem at the To reputation minimum reliability field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_FROM_REP_MIN_PRI))
    {
      if (sim_string_is_number (value, 0))
        sim_rule_set_from_rep_min_pri (rule, atoi (value));
      else
      {
        g_message ("Error. there is a problem at the From reputation minimum priority field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_TO_REP_MIN_PRI))
    {
      if (sim_string_is_number (value, 0))
        sim_rule_set_to_rep_min_pri (rule, atoi (value));
      else
      {
        g_message ("Error. there is a problem at the To reputation minimum priority field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_FILENAME))
    {
      if (!sim_xml_directive_set_rule_generic_text (rule, value, PROPERTY_FILENAME))
      {
        g_message ("Error: there is a problem at the Filename field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_USERNAME))
    {
      if (!sim_xml_directive_set_rule_generic_text (rule, value, PROPERTY_USERNAME))
      {
        g_message ("Error: there is a problem at the Username field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_PASSWORD))
    {
      if (!sim_xml_directive_set_rule_generic_text (rule, value, PROPERTY_PASSWORD))
      {
        g_message ("Error. there is a problem at the Password field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_USERDATA1))
    {
      if (!sim_xml_directive_set_rule_generic_text (rule, value, PROPERTY_USERDATA1))
      {
        g_message ("Error. there is a problem at the Userdata1 field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_USERDATA2))
    {
      if (!sim_xml_directive_set_rule_generic_text (rule, value, PROPERTY_USERDATA2))
      {
        g_message ("Error. there is a problem at the Userdata2 field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_USERDATA3))
    {
      if (!sim_xml_directive_set_rule_generic_text (rule, value, PROPERTY_USERDATA3))
      {
        g_message ("Error. there is a problem at the Userdata3 field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_USERDATA4))
    {
      if (!sim_xml_directive_set_rule_generic_text (rule, value, PROPERTY_USERDATA4))
      {
        g_message ("Error. there is a problem at the Userdata4 field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_USERDATA5))
    {
      if (!sim_xml_directive_set_rule_generic_text (rule, value, PROPERTY_USERDATA5))
      {
        g_message ("Error. there is a problem at the Userdata5 field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_USERDATA6))
    {
      if (!sim_xml_directive_set_rule_generic_text (rule, value, PROPERTY_USERDATA6))
      {
        g_message ("Error. there is a problem at the Userdata6 field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_USERDATA7))
    {
      if (!sim_xml_directive_set_rule_generic_text (rule, value, PROPERTY_USERDATA7))
      {
        g_message ("Error. there is a problem at the Userdata7 field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_USERDATA8))
    {
      if (!sim_xml_directive_set_rule_generic_text (rule, value, PROPERTY_USERDATA8))
      {
        g_message ("Error. there is a problem at the Userdata8 field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_USERDATA9))
    {
      if (!sim_xml_directive_set_rule_generic_text (rule, value, PROPERTY_USERDATA9))
      {
        g_message ("Error. there is a problem at the Userdata9 field");
        return NULL;
      }
    }
    else if (!strcmp (attr_name, PROPERTY_FROM_PROPERTY))
    {
      ossim_debug ("%s: Not implemented", PROPERTY_FROM_PROPERTY);
      continue;
    }
    else if (!strcmp (attr_name,  PROPERTY_TO_PROPERTY))
    {
      ossim_debug ("%s: Not implemented", PROPERTY_TO_PROPERTY);
      continue;
    }
    else if (strcmp (attr_name, PROPERTY_STICKY))
    {
      g_message ("Error. unknown property '%s'", attr->name);
    }
  }

  rule->type = type;
  if (sticky_different)
    sim_rule_set_sticky_different (rule, sticky_different);
  if (not)
    sim_rule_set_not (rule, not);
  sim_rule_set_level (rule, level);
  sim_rule_set_priority (rule, priority);
  sim_rule_set_reliability (rule, reliability);
  sim_rule_set_rel_abs (rule, rel_abs);
  sim_rule_set_condition (rule, condition);
  if (par_value)
  {
    sim_rule_set_value (rule, par_value);
    g_free (par_value);
  }
  if (interval > 0)
    sim_rule_set_interval (rule, interval);
  if (absolute)
    sim_rule_set_absolute (rule, absolute);
  sim_rule_set_time_out (rule, time_out);
  sim_rule_set_occurrence (rule, occurrence);

  // Check rule semantics with ids and sids
  rule_ids = sim_rule_get_plugin_ids (rule);
  rule_ids_not = sim_rule_get_plugin_ids_not (rule);
  rule_sids = sim_rule_get_plugin_sids (rule);
  if (rule_sids && ((rule_ids && g_hash_table_size (sim_rule_get_plugin_ids (rule)) > 1) || (rule_ids_not && g_hash_table_size (sim_rule_get_plugin_ids_not (rule)) > 1)))
  {
    g_message ("Error. It is not possible to specify more that one plugin_id AND one or more sids");
    return NULL;
  }

  if (!root)                    //ok, this is the first  rule, the root node...
    rule_node = g_node_new (rule);      //..so we have to create the first GNode.
  else
    rule_node = g_node_append_data (root, rule);        //if it's a child node, we append it to the root.

  children = node->xmlChildrenNode;
  while (children)              //if the node has more nodes (rules), we have to do the same than this function again
  {                             //so we can call this recursively.
    /* Gets Rules Node */
    if (!strcmp ((gchar *) children->name, OBJECT_RULES))
    {
      children_rules = children->xmlChildrenNode;
      while (children_rules)
      {
        /* Recursive call */
        if (!strcmp ((gchar *) children_rules->name, OBJECT_RULE))
        {
          if (!sim_xml_directive_new_rule_from_node (xmldirect, children_rules, rule_node, level + 1, directive_id))
          {
            return NULL;
          }
        }

        children_rules = children_rules->next;
      }
    }

    children = children->next;
  }

  return rule_node;
}

/*
 *
 *
 *
 *
 */
GList *
sim_xml_directive_get_directives (SimXmlDirective * xmldirect)
{
  g_return_val_if_fail (SIM_IS_XML_DIRECTIVE (xmldirect), NULL);

  return xmldirect->_priv->directives;
}

void
sim_xml_directive_set_directives (SimXmlDirective * xmldirect, GList *directives)
{
  g_return_if_fail (SIM_IS_XML_DIRECTIVE (xmldirect));

  xmldirect->_priv->directives = directives;
}

/*
 *
 *
 *
 *
 */
void
sim_xml_directive_new_append_directive_from_node (SimXmlDirective * xmldirect, xmlNodePtr node, SimDirectiveGroup * group)
{
  xmlChar *value;
  gint id;

  g_return_if_fail (SIM_IS_XML_DIRECTIVE (xmldirect));

  if (strcmp ((gchar *) node->name, OBJECT_APPEND_DIRECTIVE))
  {
    g_message ("Invalid append directive node %s", node->name);
    return;
  }
  if ((value = xmlGetProp (node, (xmlChar *) PROPERTY_DIRECTIVE_ID)))
  {
    if (sim_string_is_number ((gchar *) value, 0))
    {
      id = strtol ((gchar *) value, (char **) NULL, 10);
      sim_directive_group_append_id (group, id);
    }
    else
      g_message ("There is an error in directive groups. The directives ID may be wrong");
    xmlFree (value);
  }
}


/*
 *
 *
 *
 *
 */
gboolean
sim_xml_directive_new_group_from_node (SimXmlDirective * xmldirect, xmlNodePtr node)
{
  SimDirectiveGroup *group;
  xmlNodePtr children;
  xmlChar *value;

  g_return_val_if_fail (SIM_IS_XML_DIRECTIVE (xmldirect), FALSE);

  if (strcmp ((gchar *) node->name, OBJECT_GROUP))
  {
    g_message ("Invalid group node %s", node->name);
    return FALSE;
  }

  group = sim_directive_group_new ();

  if ((value = xmlGetProp (node, (xmlChar *) PROPERTY_NAME)))
  {
    sim_directive_group_set_name (group, (const gchar *) value);
    xmlFree (value);
  }

  if ((value = xmlGetProp (node, (xmlChar *) PROPERTY_STICKY)))
  {
    if (!g_ascii_strcasecmp ((gchar *) value, "true"))
      sim_directive_group_set_sticky (group, TRUE);
    else
      sim_directive_group_set_sticky (group, FALSE);
    xmlFree (value);
  }

  children = node->xmlChildrenNode;
  while (children)
  {
    if (!strcmp ((gchar *) children->name, OBJECT_APPEND_DIRECTIVE))
      sim_xml_directive_new_append_directive_from_node (xmldirect, children, group);

    children = children->next;
  }

  xmldirect->_priv->groups = g_list_append (xmldirect->_priv->groups, group);
  return TRUE;
}


/*
 *
 *
 *
 */
gboolean
sim_xml_directive_new_groups_from_node (SimXmlDirective * xmldirect, xmlNodePtr node)
{
  xmlNodePtr children;

  g_return_val_if_fail (SIM_IS_XML_DIRECTIVE (xmldirect), FALSE);

  if (strcmp ((gchar *) node->name, OBJECT_GROUPS))
  {
    g_message ("Invalid groups node %s", node->name);
    return FALSE;
  }

  children = node->xmlChildrenNode;
  while (children)
  {
    if (!strcmp ((gchar *) children->name, OBJECT_GROUP))
      sim_xml_directive_new_group_from_node (xmldirect, children);

    children = children->next;
  }

  return TRUE;
}

gboolean
sim_xml_directive_new_timetables_from_node (SimXmlDirective * xml, xmlNodePtr node)
{
  xmlNodePtr children;
  gchar *name = NULL;
  gboolean error = FALSE;
  SimTimetable *timetable;
  g_return_val_if_fail (node != NULL, FALSE);
  node = node->xmlChildrenNode;
  while (node)
  {
    if (strcmp ((gchar *) node->name, (gchar *) OBJECT_TIMETABLE))
    {
      g_message ("Invalid timetable node %s", node->name);
      error = TRUE;
      break;
    }
    name = (char *) xmlGetProp (node, (xmlChar *) PROPERTY_NAME);
    ossim_debug ("Loading timetable: %s", name);
    timetable = g_object_new (SIM_TYPE_TIMETABLE, NULL);
    sim_timetable_set_name (timetable, name);
    children = node->xmlChildrenNode;
    /* Read the timerule */
    while (children && !error)
    {
      gchar *sztimespec = NULL, *szdatespec = NULL;
      if (strcmp ((gchar *) children->name, OBJECT_TIMERULE))
      {
        g_message ("Error: Timetable %s, bad timerule", name);
      }
      /* timrule attributes */
      sztimespec = (gchar *) xmlGetProp (children, (xmlChar *) PROPERTY_TIMESPEC);
      szdatespec = (gchar *) xmlGetProp (children, (xmlChar *) PROPERTY_DATESPEC);
      error = sim_timetable_append_timerule (timetable, szdatespec, sztimespec);
      if (sztimespec)
        xmlFree (sztimespec);
      if (szdatespec)
        xmlFree (szdatespec);
      children = children->next;
    }                           /* End of while */
    if (name)
      xmlFree (name);
    xml->_priv->timetables = g_list_append (xml->_priv->timetables, timetable);
    node = node->next;
  }
  if (error)
  {
    GList *lt = xml->_priv->timetables;
    while (lt)
    {
      timetable = (SimTimetable *) lt->data;
      lt = g_list_remove (lt, timetable);
      g_object_unref (timetable);
    }
    xml->_priv->timetables = lt;
  }
  return error;
}

#ifdef USE_UNITTESTS

/*************************************************************
 ***************      Unit tests      ***************
 **************************************************************/

SimXmlDirective *sim_xml_directive_new_from_string (const gchar * buff);
gint sim_xml_directive_test1 (void);

/* Unit tests begin here */

/* Data for testing */
gchar *xml_data1 = "<?xml version=\"1.0\" encoding=\"UTF-8\"?> \n \
 <directive id=\"50021\" name=\"AV SSH brute force authentication attack against DST_IP (SiteProtector)\" priority=\"4\">\n \
   <rule type=\"detector\" name=\"SSH Authentication failure\" reliability=\"1\"\n \
   occurrence=\"1\" from=\"ANY\" to=\"ANY\" port_from=\"ANY\" port_to=\"ANY\"\n \
   time_out=\"10\" plugin_id=\"1611\" plugin_sid=\"2110069\">\n \
      <rules>\n \
         <rule type=\"detector\" name=\"SSH Authentication failure (5 times)\"\n \
          reliability=\"+1\" occurrence=\"2\" from=\"1:SRC_IP\" to=\"1:DST_IP\"\n \
          port_from=\"ANY\" time_out=\"40\" port_to=\"ANY\"\n \
          plugin_id=\"1611\" plugin_sid=\"2110069\" sticky=\"true\">\n \
         </rule>\n \
         <rule type=\"detector\" name=\"SSH Authentication failure (10 times)\"\n \
          reliability=\"+1\" occurrence=\"10\" from=\"1:SRC_IP\" to=\"1:DST_IP\"\n \
          port_from=\"ANY\" time_out=\"60\" port_to=\"ANY\"\n \
          plugin_id=\"1611\" plugin_sid=\"2110069\" sticky=\"true\">\n \
         </rule>\n \
      </rules>\n \
  </rule>\n \
</directive>";

gchar *xml_data2 = "<?xml version=\"1.0\" encoding=\"UTF-8\"?> \n \
 <directive id=\"50021\" name=\"AV SSH brute force authentication attack against DST_IP (SiteProtector)\" priority=\"4\">\n \
   <rule type=\"detector\" name=\"SSH Authentication failure\" reliability=\"1\"\n \
   occurrence=\"1\" from=\"ANY\" to=\"ANY\" port_from=\"ANY\" port_to=\"ANY\"\n \
   time_out=\"10\" plugin_id=\"1611\" plugin_sid=\"2110069\">\n \
      <rules>\n \
         <rule type=\"detector\" name=\"SSH Authentication failure (5 times)\"\n \
          reliability=\"+1\" occurrence=\"2\" from=\"ANY\" to=\"1:DST_IP\"\n \
          port_from=\"ANY\" time_out=\"40\" port_to=\"ANY\"\n \
          plugin_id=\"1611\" plugin_sid=\"2110069\" sticky=\"true\">\n \
         </rule>\n \
         <rule type=\"detector\" name=\"SSH Authentication failure (10 times)\"\n \
          reliability=\"+1\" occurrence=\"10\" from=\"1:SRC_IP\" to=\"1:DST_IP\"\n \
          port_from=\"ANY\" time_out=\"60\" port_to=\"ANY\"\n \
          plugin_id=\"1611\" plugin_sid=\"2110069\" sticky=\"true\">\n \
         </rule>\n \
      </rules>\n \
  </rule>\n \
</directive>";

gchar *xml_data3 = "<?xml version=\"1.0\" encoding=\"UTF-8\"?> \n \
 <directive id=\"50021\" name=\"AV SSH brute force authentication attack against DST_IP (SiteProtector)\" priority=\"4\">\n \
   <rule type=\"detector\" name=\"SSH Authentication failure\" reliability=\"1\"\n \
   occurrence=\"1\" from=\"ANY\" to=\"ANY\" port_from=\"ANY\" port_to=\"ANY\"\n \
   time_out=\"10\" plugin_id=\"1611\" plugin_sid=\"2110069\">\n \
      <rules>\n \
         <rule type=\"detector\" name=\"SSH Authentication failure (5 times)\"\n \
          reliability=\"+1\" occurrence=\"2\" from=\"ANY\" to=\"1:DST_IP\"\n \
          port_from=\"ANY\" time_out=\"40\" port_to=\"ANY\"\n \
          plugin_id=\"1611\" plugin_sid=\"2110069\" sticky=\"true\">\n \
         </rule>\n \
         <rule type=\"detector\" name=\"SSH Authentication failure (10 times)\"\n \
          reliability=\"+1\" occurrence=\"10\" from=\"ANY\" to=\"1:DST_IP\"\n \
          port_from=\"ANY\" time_out=\"60\" port_to=\"ANY\"\n \
          plugin_id=\"1611\" plugin_sid=\"2110069\" sticky=\"true\">\n \
         </rule>\n \
      </rules>\n \
  </rule>\n \
</directive>";

/* Helper functions for testing */

SimDirective *
sim_new_test_directive ()
{
  SimXmlDirective *xml_directive;
  GList *directives_list;

  xml_directive = sim_xml_directive_new_from_string (xml_data1);
  directives_list = sim_xml_directive_get_directives (xml_directive);

  return (SimDirective *) directives_list->data;
}

/**
 * sim_xml_directive_new_from_string
 */
SimXmlDirective *
sim_xml_directive_new_from_string (const gchar * buff)
{
  SimXmlDirective *xmldirect;   //here will be stored all the directives, and all the groups
  xmlParserCtxtPtr ctx;
  xmlErrorPtr error;
  xmlDocPtr doc;
  xmlNodePtr root;
  xmlNodePtr node;
  GList *list;

  g_return_val_if_fail (buff != NULL, NULL);

  ctx = xmlNewParserCtxt ();
  if (!ctx)
  {
    ossim_debug ("Could not create XML Parser");
    return NULL;
  }

  // Set error handler for this context.
  xmlSetGenericErrorFunc (ctx, sim_log_xml_handler);

  /* parse an validate de XML directives.xml file */
  doc = xmlCtxtReadMemory (ctx, buff, strlen (buff), NULL, NULL, XML_PARSE_NOENT | XML_PARSE_RECOVER | XML_PARSE_NOERROR | XML_PARSE_NOWARNING);

  if (!doc)
  {
    error = xmlGetLastError ();
    ossim_debug ("Could not parse string. Error:%s", error->message);
    return NULL;
  }

  if (!ctx->valid)
  {
    error = xmlGetLastError ();
    ossim_debug ("Validate error. Error %s", error->message);
  }
  xmlFreeParserCtxt (ctx);

  xmldirect = g_object_new (SIM_TYPE_XML_DIRECTIVE, NULL);

  /* parse the doc */
  root = xmlDocGetRootElement (doc);    //we need to know the first element in the tree
  node = root;
  if (!strcmp ((gchar *) node->name, OBJECT_DIRECTIVE)) //parse each one of the directives and store it in xmldirect
    sim_xml_directive_new_directive_from_node (xmldirect, node);

  if (!strcmp ((gchar *) node->name, OBJECT_GROUPS))
    sim_xml_directive_new_groups_from_node (xmldirect, node);   // the same with directive groups
  if (!strcmp ((gchar *) node->name, OBJECT_TIMETABLES))
    sim_xml_directive_new_timetables_from_node (xmldirect, node);


  GList *lt = xmldirect->_priv->timetables;
  while (lt)
  {
    SimTimetable *tb = lt->data;
    sim_timetable_dump (tb);

    /* assign timetables to directives */
    GList *ld = xmldirect->_priv->directives;
    while (ld)
    {
      SimDirective *directive = (SimDirective *) ld->data;
      if (sim_directive_get_timetable_name (directive) != NULL && strcmp (sim_directive_get_timetable_name (directive), sim_timetable_get_name (tb)) == 0)
      {
        ossim_debug ("Setting timetable %s to directive %s", sim_timetable_get_name (tb), sim_directive_get_name (directive));
        sim_directive_set_timetable (directive, tb);
      }
      ld = ld->next;
    }

    lt = lt->next;
  }

  list = xmldirect->_priv->groups;
  while (list)
  {
    SimDirectiveGroup *group = (SimDirectiveGroup *) list->data;
    GList *ids = sim_directive_group_get_ids (group);

    while (ids)
    {
      gint id = GPOINTER_TO_INT (ids->data);
      SimDirective *directive = find_directive (xmldirect, id);

      if (directive)
        sim_directive_append_group (directive, group);

      ids = ids->next;
    }

    list = list->next;
  }

  xmlFreeDoc (doc);
  return xmldirect;
}


// Test 1: Check if all children rules have SRC_IP var
gint
sim_xml_directive_test1 ()
{
  SimXmlDirective *xml_directive;
  GList *directives_list;
  GNode *node_root;
  SimDirective *directive;

  /* Thread Init */
  if (!g_thread_supported ())
    g_thread_init (NULL);

  // Two sibling nodes with 1:SRC_IP in from
  xml_directive = sim_xml_directive_new_from_string (xml_data1);
  directives_list = sim_xml_directive_get_directives (xml_directive);

  directive = (SimDirective *) directives_list->data;
  node_root = sim_directive_get_root_node (directive);

  if (!sim_directive_all_children_have_src_ia (node_root))
  {
    g_object_unref (xml_directive);
    return 0;
  }
  g_object_unref (xml_directive);

  // Two sibling nodes, one of them with 1:SRC_IP in from
  xml_directive = sim_xml_directive_new_from_string (xml_data2);
  directives_list = sim_xml_directive_get_directives (xml_directive);

  directive = (SimDirective *) directives_list->data;
  node_root = sim_directive_get_root_node (directive);

  if (sim_directive_all_children_have_src_ia (node_root))
  {
    g_object_unref (xml_directive);
    return 0;
  }
  g_object_unref (xml_directive);

  // Two sibling nodes without 1:SRC_IP in from
  xml_directive = sim_xml_directive_new_from_string (xml_data3);
  directives_list = sim_xml_directive_get_directives (xml_directive);

  directive = (SimDirective *) directives_list->data;
  node_root = sim_directive_get_root_node (directive);

  if (sim_directive_all_children_have_src_ia (node_root))
  {
    g_object_unref (xml_directive);
    return 0;
  }
  g_object_unref (xml_directive);

  return 1;
}

void
sim_xml_directive_register_tests (SimUnittesting * engine)
{
  sim_unittesting_append (engine, "sim_xml_directive_test1 - Children with 1:SRC_IP var", sim_xml_directive_test1, 1);
}


#endif

// vim: set tabstop=2:
