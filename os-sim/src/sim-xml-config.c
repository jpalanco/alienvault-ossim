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

#include "sim-xml-config.h"

#include <string.h>
#include <uuid/uuid.h>

#include "sim-util.h"

struct _SimXmlConfigPrivate {
  SimConfig     *config;
};

#define OBJECT_CONFIG           "config"
#define OBJECT_LOG              "log"
#define OBJECT_DATASOURCES      "datasources"
#define OBJECT_DATASOURCE       "datasource"
#define OBJECT_DIRECTIVE        "directive"
#define OBJECT_REPUTATION       "reputation"
#define OBJECT_SCHEDULER        "scheduler"
#define OBJECT_SERVER           "server"
#define OBJECT_RSERVERS         "rservers"
#define OBJECT_RSERVER          "rserver"
#define OBJECT_NOTIFIES         "notifies"
#define OBJECT_NOTIFY           "notify"
#define OBJECT_SMTP             "smtp"
#define OBJECT_FRAMEWORK	"framework"
#define OBJECT_FORENSIC_STORAGE "forensic_storage"
#define OBJECT_IDM              "idm"
#define OBJECT_CONTEXT          "context"

#define PROPERTY_ID             "id"
#define PROPERTY_NAME           "name"
#define PROPERTY_IP             "ip"
#define PROPERTY_INTERFACE      "interface"
#define PROPERTY_FILENAME       "filename"
#define PROPERTY_PROVIDER       "provider"
#define PROPERTY_DSN            "dsn"
#define PROPERTY_INTERVAL       "interval"
#define PROPERTY_PORT           "port"
#define PROPERTY_EMAILS         "emails"
#define PROPERTY_ALARM_RISKS    "alarm_risks"
#define PROPERTY_HOST           "host"
#define PROPERTY_PROGRAM        "program"
#define PROPERTY_HA_IP				  "HA_ip"
#define PROPERTY_HA_PORT				"HA_port"
#define PROPERTY_HA_ROLE				"HA_role"
#define PROPERTY_PRIMARY				"primary"				//primary master server? The primary master server is from the initial data is loaded.
#define PROPERTY_LOCAL_DB				"local_DB"			//this and the following, needed to know where to connect if the DB is not local
#define PROPERTY_PRIORITY       "priority"      // Establishes a resend priority order between rservers.
#define PROPERTY_RSERVER_NAME		"rserver_name"	//
#define PROPERTY_CONTEXT_ID     "context_id"    // Context id associated to Rserver
#define PROPERTY_MSSP           "mssp"

//Forensic Storage Properties
#define PROPERTY_FORENSIC_STORAGE_PATH "path"
#define PROPERTY_FORENSIC_STORAGE_SIG_TYPE "signature_type"
#define PROPERTY_FORENSIC_STORAGE_SIG_CIPHER "signature_cipher"
#define PROPERTY_FORENSIC_STORAGE_SIG_BIT "signature_bit_length"
#define PROPERTY_FORENSIC_STORAGE_ENC_TYPE "encryption_type"
#define PROPERTY_FORENSIC_STORAGE_ENC_CIPHER "encryption_cipher"
#define PROPERTY_FORENSIC_STORAGE_ENC_BIT "encryption_bit_length"
#define PROPERTY_FORENSIC_STORAGE_KEY_SOURCE "key_source"
#define PROPERTY_FORENSIC_STORAGE_SIG_PRV_KEY_PATH "sig_prv_key_path"
#define PROPERTY_FORENSIC_STORAGE_SIG_PASS "sig_pass"
#define PROPERTY_FORENSIC_STORAGE_SIG_PUB_KEY_PATH "sig_pub_key_path"
#define PROPERTY_FORENSIC_STORAGE_ENC_PRV_KEY_PATH "enc_prv_key_path"
#define PROPERTY_FORENSIC_STORAGE_ENC_PASS "enc_pass"
#define PROPERTY_FORENSIC_STORAGE_ENC_PUB_KEY_PATH "enc_pub_key_path"
#define PROPERTY_FORENSIC_STORAGE_CERT_PATH "enc_cert_path"

// MSSP Context Properties
#define PROPERTY_DIRECTIVE_FILE "directive_file"
#define PROPERTY_DISABLED_FILE  "disabled_file"


static void sim_xml_config_class_init (SimXmlConfigClass *klass);
static void sim_xml_config_init       (SimXmlConfig *xmlconfig, SimXmlConfigClass *klass);
static void sim_xml_config_finalize   (GObject *object);

/*
 * SimXmlConfig object signals
 */
enum {
  SIM_XML_CONFIG_CHANGED,
  SIM_XML_CONFIG_LAST_SIGNAL
};

static gint xmlconfig_signals[SIM_XML_CONFIG_LAST_SIGNAL] = { 0, };
static GObjectClass *parent_class = NULL;

/*
 * SimXmlConfig class interface
 */

static void
sim_xml_config_class_init (SimXmlConfigClass * klass)
{
  GObjectClass *object_class = G_OBJECT_CLASS (klass);
  
  parent_class = g_type_class_peek_parent (klass);
  
  xmlconfig_signals[SIM_XML_CONFIG_CHANGED] =
    g_signal_new ("changed",
		  G_TYPE_FROM_CLASS (object_class),
		  G_SIGNAL_RUN_LAST,
		  G_STRUCT_OFFSET (SimXmlConfigClass, changed),
		  NULL, NULL,
		  g_cclosure_marshal_VOID__VOID,
		  G_TYPE_NONE, 0);
  
  object_class->finalize = sim_xml_config_finalize;
  klass->changed = NULL;
}

static void
sim_xml_config_init (SimXmlConfig *xmlconfig, SimXmlConfigClass *klass)
{
  g_return_if_fail (SIM_IS_XML_CONFIG (xmlconfig));

  // unused parameter
  (void) klass;
  
  /* allocate private structure */
  xmlconfig->_priv = g_new0 (SimXmlConfigPrivate, 1);
}

static void
sim_xml_config_finalize (GObject *object)
{
  SimXmlConfig *xmlconfig = (SimXmlConfig *) object;
  
  g_free (xmlconfig->_priv);

  /* chain to parent class */
  parent_class->finalize (object);
}

GType
sim_xml_config_get_type (void)
{
  static GType type = 0;
  
  if (!type) {
    static const GTypeInfo info = {
      sizeof (SimXmlConfigClass),
      (GBaseInitFunc) NULL,
      (GBaseFinalizeFunc) NULL,
      (GClassInitFunc) sim_xml_config_class_init,
      NULL,
      NULL,
      sizeof (SimXmlConfig),
      0,
      (GInstanceInitFunc) sim_xml_config_init,
      NULL
    };
    type = g_type_register_static (G_TYPE_OBJECT, "SimXmlConfig", &info, 0);
  }
  return type;
}

/**
 * sim_xml_config_new
 *
 * Creates a new #SimXmlConfig object, which can be used to describe
 * a config which will then be loaded by a provider to create its
 * defined structure
 */
SimXmlConfig *
sim_xml_config_new (void)
{
  SimXmlConfig *xmlconfig;
  
  xmlconfig = g_object_new (SIM_TYPE_XML_CONFIG, NULL);
  return xmlconfig;
}

/**
 * sim_xml_config_new_from_file
 */
SimXmlConfig*
sim_xml_config_new_from_file (const gchar *file)
{
  SimXmlConfig *xmlconfig;
  gchar *body;
  xmlDocPtr doc;
  xmlNodePtr root;
  
  g_return_val_if_fail (file != NULL, NULL);

  /* load the file from the given FILE */
  body = sim_file_load (file);
  if (!body) {
    g_message ("Could not load file at %s", file);
    return NULL;
  }
  
  /* parse the loaded XML file */
  doc = xmlParseMemory (body, strlen (body));
  g_free (body);

  if (!doc) {
    g_message ("Could not parse file at %s", file);
    return NULL;
  }

  xmlconfig = g_object_new (SIM_TYPE_XML_CONFIG, NULL);

  /* parse the file */
  root = xmlDocGetRootElement (doc);
  if (strcmp ((char *) root->name, OBJECT_CONFIG)) 
	{
		g_message ("Invalid XML config file '%s'", file);
    g_object_unref (G_OBJECT (xmlconfig));
    xmlFreeDoc (doc);
    return NULL;
  }

  xmlconfig->_priv->config = sim_xml_config_new_config_from_node (xmlconfig, root);
  xmlFreeDoc (doc);

  return xmlconfig;
}


/**
 * sim_xml_config_changed
 * @xmlconfig: XML config
 *
 * Emit the "changed" signal for the given XML config
 */
void
sim_xml_config_changed (SimXmlConfig * xmlconfig)
{
  g_return_if_fail (SIM_IS_XML_CONFIG (xmlconfig));
  g_signal_emit (G_OBJECT (xmlconfig),
		 xmlconfig_signals[SIM_XML_CONFIG_CHANGED],
		 0);
}

/**
 * sim_xml_config_reload
 * @xmlconfig: XML config.
 *
 * Reload the given XML config from its original place, discarding
 * all changes that may have happened.
 */
void
sim_xml_config_reload (SimXmlConfig *xmlconfig)
{
  // unused parameter
  (void) xmlconfig;

  /* FIXME: implement */
}

/**
 * sim_xml_config_save
 * @xmlconfig: XML config.
 * @file: FILE to save the XML config to.
 *
 * Save the given XML config to disk.
 */
gboolean
sim_xml_config_save (SimXmlConfig *xmlconfig, const gchar *file)
{
  gchar*xml;
  gboolean result;
  
  g_return_val_if_fail (SIM_IS_XML_CONFIG (xmlconfig), FALSE);
  
  xml = sim_xml_config_to_string (xmlconfig);
  if (xml) {
    result = sim_file_save (file, xml, strlen (xml));
    g_free (xml);
  } else
    result = FALSE;

  return result;
}

/**
 * sim_xml_config_to_string
 * @xmlconfig: a #SimXmlConfig object.
 *
 * Get the given XML config contents as a XML string.
 *
 * Returns: the XML string representing the structure and contents of the
 * given #SimXmlConfig object. The returned value must be freed when no
 * longer needed.
 */
gchar *
sim_xml_config_to_string (SimXmlConfig *xmlconfig)
{
  xmlDocPtr doc;
  xmlNodePtr root;
  xmlChar *xml;
  gint size;
  gchar *retval;
  
  g_return_val_if_fail (SIM_IS_XML_CONFIG (xmlconfig), NULL);
  
  /* create the top node */
  doc = xmlNewDoc ((xmlChar *) "1.0");	//xmlChar is a typedef to unsigned char. Needed to avoid stupid warnings
  root = xmlNewDocNode (doc, NULL, (xmlChar *) OBJECT_CONFIG, NULL);
  xmlDocSetRootElement (doc, root);
  
  /* save to memory */
  xmlDocDumpMemory (doc, &xml, &size);
  xmlFreeDoc (doc);
  if (!xml) {
    g_message ("Could not dump XML file to memory");
    return NULL;
  }
  
  retval = g_strdup ((gchar *)xml);
  free (xml);
  
  return retval;
}

/*
 *
 *
 *
 *
 */
void
sim_xml_config_set_config_log (SimXmlConfig  *xmlconfig,
												       SimConfig     *config,
												       xmlNodePtr     node)
{
  gchar  *value;

  g_return_if_fail (SIM_IS_XML_CONFIG (xmlconfig));
  g_return_if_fail (SIM_IS_CONFIG (config));
  g_return_if_fail (node);

  if (strcmp ((gchar *) node->name, OBJECT_LOG))
    {
      g_message ("Invalid config log node %s", node->name);
      return;
    }

  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_FILENAME)))
    {
      config->log.filename = g_strdup (value);
      xmlFree(value);      
    }
}

/*
 *
 *
 *
 *
 *
void
sim_xml_config_set_config_sensor (SimXmlConfig  *xmlconfig,
				  SimConfig     *config,
				  xmlNodePtr     node)
{
  gchar  *value;
  
  g_return_if_fail (xmlconfig);
  g_return_if_fail (SIM_IS_XML_CONFIG (xmlconfig));
  g_return_if_fail (config);
  g_return_if_fail (SIM_IS_CONFIG (config));
  g_return_if_fail (node);

  if (strcmp ((gchar *) node->name, OBJECT_SENSOR))
    {
      g_message ("Invalid sensor log node %s", node->name);
      return;
    }

  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_NAME)))
    {
      config->sensor.name = g_strdup (value);
      xmlFree(value);      
    }
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_IP)))
    {
      config->sensor.ip = g_strdup (value);
      xmlFree(value);      
    }
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_INTERFACE)))
    {
      config->sensor.interface = g_strdup (value);
      xmlFree(value);      
    }
}
*/
/*
 *
 *
 *
 *
 */
void
sim_xml_config_set_config_framework (SimXmlConfig  *xmlconfig,
				  SimConfig     *config,
				  xmlNodePtr     node)
{
  gchar  *value;
  
  g_return_if_fail (SIM_IS_XML_CONFIG (xmlconfig));
  g_return_if_fail (SIM_IS_CONFIG (config));
  g_return_if_fail (node);

  if (strcmp ((gchar *) node->name, OBJECT_FRAMEWORK))
    {
      g_message ("Invalid sensor log node %s", node->name);
      return;
    }

  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_NAME)))
    {
      config->framework.name = g_strdup (value);
      xmlFree(value);      
    }
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_IP)))
    {
      config->framework.host = g_strdup (value);
      xmlFree(value);      
    }
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_PORT)))
    {
      config->framework.port = strtol (value, (char **) NULL, 10);
      xmlFree(value);      
    }
}


/*
 *
 *
 *
 *
 */
void
sim_xml_config_set_config_datasource (SimXmlConfig  *xmlconfig,
																      SimConfig     *config,
																      xmlNodePtr     node)
{
  SimConfigDS  *ds;
  gchar        *value;

  g_return_if_fail (SIM_IS_XML_CONFIG (xmlconfig));
  g_return_if_fail (SIM_IS_CONFIG (config));
  g_return_if_fail (node);

  if (strcmp ((gchar *) node->name, OBJECT_DATASOURCE))
    {
      g_message ("Invalid config datasource node %s", node->name);
      return;
    }

  ds = sim_config_ds_new ();
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_NAME)))
    {
      ds->name = g_strdup (value);
      xmlFree(value);      
    }
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_PROVIDER)))
    {
      ds->provider = g_strdup (value);
      xmlFree(value);      
    }
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_DSN)))
    {
      GString *dsn_new = g_string_new ("");
      gchar **key_value, **split;
      gchar **i;

      /* Adapt the data source name from GDA 2 to GDA 4 */
      key_value = g_strsplit (value, ";", 0);
      for (i = key_value; *i; i++)
      {
        split = g_strsplit (*i, "=", 2);
        if (!strcmp(*split, "USER"))
          g_string_append_printf (dsn_new, "USERNAME=%s;", *(split+1));
        else if (!strcmp(*split, "DATABASE"))
          g_string_append_printf (dsn_new, "DB_NAME=%s;", *(split+1));
        else
          g_string_append_printf (dsn_new, "%s=%s;", *split, *(split+1));

        g_strfreev(split);
      }
      g_strfreev(key_value);

      sim_config_ds_set_dsn_string (ds, g_string_free (dsn_new, FALSE));

      xmlFree(value);
    }
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_LOCAL_DB)))
    {
			if (!g_ascii_strncasecmp (value, "true", 4))
				ds->local_DB = TRUE;
			else
			if (!g_ascii_strncasecmp (value, "false", 4))
				ds->local_DB = FALSE;
			else
			{
				g_message ("Error: Please put a valid value (true/false) in the Local_DB Parameter");
				xmlFree(value);      
				return;
			}
    }
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_RSERVER_NAME)))
    {
      ds->rserver_name = g_strdup (value);
      xmlFree(value);      
    }



  config->datasources = g_list_append (config->datasources, ds);
}

/*
 *
 *
 *
 *
 */
void
sim_xml_config_set_config_datasources (SimXmlConfig  *xmlconfig,
				       SimConfig     *config,
				       xmlNodePtr     node)
{
  xmlNodePtr  children;

  g_return_if_fail (SIM_IS_XML_CONFIG (xmlconfig));
  g_return_if_fail (SIM_IS_CONFIG (config));
  g_return_if_fail (node);

  if (strcmp ((gchar *) node->name, OBJECT_DATASOURCES))
    {
      g_message ("Invalid config datasources node %s", node->name);
      return;
    }

  children = node->xmlChildrenNode;
  while (children) {
    if (!strcmp ((gchar *) children->name, OBJECT_DATASOURCE))
      {
	sim_xml_config_set_config_datasource (xmlconfig, config, children);
      }

    children = children->next;
  }

}

/*
 *
 *
 *
 *
 */
void
sim_xml_config_set_config_directive (SimXmlConfig  *xmlconfig,
				     SimConfig     *config,
				     xmlNodePtr     node)
{
  gchar  *value;

  g_return_if_fail (SIM_IS_XML_CONFIG (xmlconfig));
  g_return_if_fail (SIM_IS_CONFIG (config));
  g_return_if_fail (node);

  if (strcmp ((gchar *) node->name, OBJECT_DIRECTIVE))
    {
      g_message ("Invalid config directive node %s", node->name);
      return;
    }

  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_FILENAME)))
    {
      config->directive.filename = g_strdup (value);
      xmlFree(value);      
    }
  else
    {
      config->directive.filename = g_strdup (OS_SIM_GLOBAL_DIRECTIVE_FILE);
    }
}

/*
 *
 *
 *
 *
 */
void
sim_xml_config_set_config_reputation (SimXmlConfig  *xmlconfig,
				     SimConfig     *config,
				     xmlNodePtr     node)
{
  gchar  *value;

  g_return_if_fail (SIM_IS_XML_CONFIG (xmlconfig));
  g_return_if_fail (SIM_IS_CONFIG (config));
  g_return_if_fail (node);

  if (strcmp ((gchar *) node->name, OBJECT_REPUTATION))
    {
      g_message ("Invalid config reputation node %s", node->name);
      return;
    }

  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_FILENAME)))
    {
      config->reputation.filename = g_strdup (value);
      xmlFree(value);      
    }
  else
    {
      config->reputation.filename = NULL;
    }
}

/*
 *
 *
 *
 *
 */
void
sim_xml_config_set_config_scheduler (SimXmlConfig  *xmlconfig,
				     SimConfig     *config,
				     xmlNodePtr     node)
{
  gchar  *value;

  g_return_if_fail (SIM_IS_XML_CONFIG (xmlconfig));
  g_return_if_fail (SIM_IS_CONFIG (config));
  g_return_if_fail (node);

  if (strcmp ((gchar *) node->name, OBJECT_SCHEDULER))
    {
      g_message ("Invalid config scheduler node %s", node->name);
      return;
    }

  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_INTERVAL)))
    {
      config->scheduler.interval = strtol (value, (char **) NULL, 10);
      xmlFree(value);
    }
}

/*
 *
 *
 *
 *
 */
void
sim_xml_config_set_config_server (SimXmlConfig  *xmlconfig,
				  SimConfig     *config,
				  xmlNodePtr     node)
{
  gchar  *value;

  g_return_if_fail (SIM_IS_XML_CONFIG (xmlconfig));
  g_return_if_fail (SIM_IS_CONFIG (config));
  g_return_if_fail (node);

  if (strcmp ((gchar *) node->name, OBJECT_SERVER))
    {
      g_message ("Invalid config server node %s", node->name);
      return;
    }

  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_PORT)))
    {
      config->server.port = strtol (value, (char **) NULL, 10);
      xmlFree(value);
    }

  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_NAME)))
    {
      config->server.name = g_strdup (value);
      xmlFree(value);
    }

  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_IP)))
    {
      config->server.ip = g_strdup (value);
      xmlFree(value);
    }

	if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_HA_IP)))
    {
      config->server.HA_ip = g_strdup (value);
      xmlFree(value);
    }

	if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_HA_PORT)))
    {
	    if (sim_string_is_number (value, 0))
  	  {
   			config->server.HA_port = atoi (value);
      	xmlFree(value);
		  }
	    else
  	  {
    	  g_message ("Error: May be that you introduced a bad remote HA port in the server's config.xml?");
      	xmlFree(value);
	      return;
  	  }
    }



/*  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_INTERFACE)))
    {
      config->server.interface = g_strdup (value);
      xmlFree(value);
    }
	*/
}

/*
 *
 *
 *
 *
 */
void
sim_xml_config_set_config_notify (SimXmlConfig  *xmlconfig,
				  SimConfig     *config,
				  xmlNodePtr     node)
{
  SimConfigNotify  *notify;
  gchar            *emails;
  gchar            *levels;
  gchar           **values;
  gint              i;

  g_return_if_fail (SIM_IS_XML_CONFIG (xmlconfig));
  g_return_if_fail (SIM_IS_CONFIG (config));
  g_return_if_fail (node);

  if (strcmp ((gchar *) node->name, OBJECT_NOTIFY))
    {
      g_message ("Invalid config notify node %s", node->name);
      return;
    }

  emails = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_EMAILS);
  levels = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_ALARM_RISKS);

  if (!emails || !levels)
    {
       if (emails) xmlFree(emails);
       if (levels) xmlFree(levels);
      return;
    }

  notify = sim_config_notify_new ();
  notify->emails = g_strdup (emails);

  values = g_strsplit (levels, SIM_DELIMITER_LIST, 0);
  for (i = 0; values[i] != NULL; i++)
    {
      SimAlarmRiskType risk = sim_get_alarm_risk_from_char (values[i]);
      if (risk != SIM_ALARM_RISK_TYPE_NONE)
	notify->alarm_risks =  g_list_append (notify->alarm_risks, GINT_TO_POINTER (risk));
    }
  g_strfreev (values);
  xmlFree (emails);
  xmlFree (levels);

  config->notifies = g_list_append (config->notifies, notify);
}

/*
 *
 *
 *
 *
 */
void
sim_xml_config_set_config_notifies (SimXmlConfig  *xmlconfig,
				    SimConfig     *config,
				    xmlNodePtr     node)
{
  gchar  *value;
  xmlNodePtr  children;
  
  g_return_if_fail (SIM_IS_XML_CONFIG (xmlconfig));
  g_return_if_fail (SIM_IS_CONFIG (config));
  g_return_if_fail (node);

  if (strcmp ((gchar *) node->name, OBJECT_NOTIFIES))
    {
      g_message ("Invalid config notifies node %s", node->name);
      return;
    }

  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_PROGRAM)))
    {
      config->notify_prog = g_strdup (value);
      xmlFree(value);      
    }

  children = node->xmlChildrenNode;
  while (children) {
    if (!strcmp ((gchar *) children->name, OBJECT_NOTIFY))
      {
	sim_xml_config_set_config_notify (xmlconfig, config, children);
      }

    children = children->next;
  }
}


/*
 *
 *
 *
 *
 */
void
sim_xml_config_set_config_smtp (SimXmlConfig  *xmlconfig,
				SimConfig     *config,
				xmlNodePtr     node)
{
  gchar  *value;

  g_return_if_fail (SIM_IS_XML_CONFIG (xmlconfig));
  g_return_if_fail (SIM_IS_CONFIG (config));
  g_return_if_fail (node);

  if (strcmp ((gchar *) node->name, OBJECT_SMTP))
    {
      g_message ("Invalid config smtp node %s", node->name);
      return;
    }

  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_HOST)))
    {
      config->smtp.host = g_strdup (value);
      xmlFree(value);      
    }
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_PORT)))
    {
      config->smtp.port = strtol (value, (char **) NULL, 10);
      xmlFree(value);      
    }
}

void
sim_xml_config_set_config_forensic_storage (SimXmlConfig  *xmlconfig,
											                      SimConfig     *config,
																			      xmlNodePtr     node)
{
  //g_message("LOADING FORENSIC STORAGE CONFIGS");
  gchar  *value;

  // Check validity of all needed objects.
  g_return_if_fail (SIM_IS_XML_CONFIG (xmlconfig));
  g_return_if_fail (SIM_IS_CONFIG (config));
  g_return_if_fail (node);

  //Ensure that the current XML object is the correct for this function
  if (strcmp ((gchar *) node->name, OBJECT_FORENSIC_STORAGE))
  {
    g_message ("Invalid sensor log node %s", node->name);
    return;
  }

  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_FORENSIC_STORAGE_PATH)))
  {
    config->forensic_storage.path = g_strdup (value);
    xmlFree(value);
  }
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_FORENSIC_STORAGE_SIG_TYPE)))
  {
    config->forensic_storage.sig_type = g_strdup (value);
    xmlFree(value);
  }
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_FORENSIC_STORAGE_SIG_CIPHER)))
  {
    config->forensic_storage.sig_cipher = g_strdup (value);
    xmlFree(value);
  }
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_FORENSIC_STORAGE_SIG_BIT)))
  {
    config->forensic_storage.sig_bit = strtol (value, (char **) NULL, 10);
    xmlFree(value);
  }
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_FORENSIC_STORAGE_ENC_TYPE)))
  {
    config->forensic_storage.enc_type = g_strdup (value);
    xmlFree(value);
  }
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_FORENSIC_STORAGE_ENC_CIPHER)))
  {
    config->forensic_storage.enc_cipher = g_strdup (value);
    xmlFree(value);
  }
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_FORENSIC_STORAGE_ENC_BIT)))
  {
    config->forensic_storage.enc_bit = strtol (value, (char **) NULL, 10);
    xmlFree(value);
  }
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_FORENSIC_STORAGE_KEY_SOURCE)))
  {
    config->forensic_storage.key_source = g_strdup (value);
    xmlFree(value);
  }
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_FORENSIC_STORAGE_SIG_PRV_KEY_PATH)))
  {
    config->forensic_storage.sig_prv_key_path = g_strdup (value);
    xmlFree(value);
  }
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_FORENSIC_STORAGE_SIG_PASS)))
  {
    config->forensic_storage.sig_pass = g_strdup (value);
    xmlFree(value);
  }
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_FORENSIC_STORAGE_SIG_PUB_KEY_PATH)))
  {
    config->forensic_storage.sig_pub_key_path = g_strdup (value);
    xmlFree(value);
  }
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_FORENSIC_STORAGE_ENC_PUB_KEY_PATH)))
  {
    config->forensic_storage.enc_pub_key_path = g_strdup (value);
    xmlFree(value);
  }
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_FORENSIC_STORAGE_ENC_PASS)))
  {
    config->forensic_storage.enc_pass = g_strdup (value);
    xmlFree(value);
  }
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_FORENSIC_STORAGE_ENC_PRV_KEY_PATH)))
  {
    config->forensic_storage.enc_prv_key_path = g_strdup (value);
    xmlFree(value);
  }
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_FORENSIC_STORAGE_CERT_PATH)))
  {
    config->forensic_storage.enc_cert_path = g_strdup (value);
    xmlFree(value);
  }
}

void
sim_xml_config_set_config_idm (SimXmlConfig  *xmlconfig,
											         SimConfig     *config,
														   xmlNodePtr     node)
{
  gchar *value;

  g_return_if_fail (SIM_IS_XML_CONFIG (xmlconfig));
  g_return_if_fail (SIM_IS_CONFIG (config));
  g_return_if_fail (node);

  if (strcmp ((gchar *) node->name, OBJECT_IDM))
  {
    g_message ("Invalid config idm node %s", node->name);
    return;
  }

  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_MSSP)))
  {
    if (!g_ascii_strncasecmp (value, "true", 4))
      config->idm.mssp = TRUE;
    else if (!g_ascii_strncasecmp (value, "false", 4))
      config->idm.mssp = FALSE;
    else
      g_message ("Error: Please put a valid value (true/false) in the IDM MSSP parameter");

    xmlFree (value);
  }
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_IP)))
  {
    config->idm.ip = g_strdup (value);
    xmlFree (value);
  }
  if ((value = (gchar *) xmlGetProp (node, (xmlChar *) PROPERTY_PORT)))
  {
    config->idm.port = strtol (value, (char **) NULL, 10);
    xmlFree (value);
  }
}

/*
 *	This function takes all its parameters from config file.
 */
SimConfig*
sim_xml_config_new_config_from_node (SimXmlConfig  *xmlconfig,
																     xmlNodePtr     node)
{
  SimConfig     *config;
  xmlNodePtr     children;
	gboolean aux = FALSE;
  
  g_return_val_if_fail (SIM_IS_XML_CONFIG (xmlconfig), NULL);
  g_return_val_if_fail (node != NULL, NULL);
  
  if (strcmp ((gchar *) node->name, OBJECT_CONFIG))
  {
    g_message ("Invalid config node %s", node->name);
    return NULL;
  }
  
  config = sim_config_new ();

	children = node->xmlChildrenNode;
  while (children) 
	{
    if (!strcmp ((gchar *) children->name, OBJECT_LOG))
			sim_xml_config_set_config_log (xmlconfig, config, children);
    if (!strcmp ((gchar *) children->name, OBJECT_DATASOURCES))
			sim_xml_config_set_config_datasources (xmlconfig, config, children);
    if (!strcmp ((gchar *) children->name, OBJECT_DIRECTIVE))
			sim_xml_config_set_config_directive (xmlconfig, config, children);
    if (!strcmp ((gchar *) children->name, OBJECT_REPUTATION))
			sim_xml_config_set_config_reputation (xmlconfig, config, children);
    if (!strcmp ((gchar *) children->name, OBJECT_SCHEDULER))
			sim_xml_config_set_config_scheduler (xmlconfig, config, children);
    if (!strcmp ((gchar *) children->name, OBJECT_SERVER))
			sim_xml_config_set_config_server (xmlconfig, config, children);
    if (!strcmp ((gchar *) children->name, OBJECT_SMTP))
			sim_xml_config_set_config_smtp (xmlconfig, config, children);
    if (!strcmp ((gchar *) children->name, OBJECT_NOTIFIES))
			sim_xml_config_set_config_notifies (xmlconfig, config, children);
    if (!strcmp ((gchar *) children->name, OBJECT_FRAMEWORK))
			sim_xml_config_set_config_framework (xmlconfig, config, children);
	  if (!strcmp ((gchar *) children->name, OBJECT_FORENSIC_STORAGE))
		{
      sim_xml_config_set_config_forensic_storage (xmlconfig, config, children);
			aux = TRUE;	
		}
    if (!strcmp ((gchar *) children->name, OBJECT_IDM))
    {
      sim_xml_config_set_config_idm (xmlconfig, config, children);
      config->idm.activated = TRUE;
    }

    children = children->next;
  }

	if (aux)
		config->forensic_storage.sem_activated = TRUE;
	else
		config->forensic_storage.sem_activated = FALSE;

  return config;
}

/*
 *
 *
 *
 *
 */
SimConfig*
sim_xml_config_get_config (SimXmlConfig  *xmlconfig)
{
  g_return_val_if_fail (SIM_IS_XML_CONFIG (xmlconfig), NULL);

  return xmlconfig->_priv->config;
}

// vim: set tabstop=2:
