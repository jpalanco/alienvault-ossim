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

#if !defined(__sim_xml_config_h__)
#  define __sim_xml_config_h__


#include "sim-container.h"
#include "sim-config.h"

#include <libxml/parser.h>
#include <libxml/tree.h>
#include <glib-object.h>

G_BEGIN_DECLS

#define SIM_TYPE_XML_CONFIG            (sim_xml_config_get_type())
#define SIM_XML_CONFIG(obj)            (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_XML_CONFIG, SimXmlConfig))
#define SIM_XML_CONFIG_CLASS(klass)    (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_XML_CONFIG, SimXmlConfigClass))
#define SIM_IS_XML_CONFIG(obj)         (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_XML_CONFIG))
#define SIM_IS_XML_CONFIG_CLASS(klass) (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_XML_CONFIG))

typedef struct _SimXmlConfig        SimXmlConfig;
typedef struct _SimXmlConfigClass   SimXmlConfigClass;
typedef struct _SimXmlConfigPrivate SimXmlConfigPrivate;

struct _SimXmlConfig {
	GObject object;

	SimXmlConfigPrivate *_priv;
};

struct _SimXmlConfigClass {
	GObjectClass parent_class;

	/* signals */
	void (*changed) (SimXmlConfig * xmldb);
};

GType            sim_xml_config_get_type (void);

SimXmlConfig*    sim_xml_config_new (void);
SimXmlConfig*    sim_xml_config_new_from_file (const gchar *file);

void             sim_xml_config_changed (SimXmlConfig *xmlconfig);
void             sim_xml_config_reload (SimXmlConfig *xmlconfig);
gboolean         sim_xml_config_save (SimXmlConfig *xmlconfig,
				      const gchar *file);
gchar*           sim_xml_config_to_string (SimXmlConfig *xmlconfig);

SimConfig*       sim_xml_config_new_config_from_node (SimXmlConfig *xmlconfig,
																								      xmlNodePtr       node);

SimConfig*       sim_xml_config_get_config (SimXmlConfig  *xmlconfig);
void						 sim_xml_config_set_confi(SimXmlConfig  *xmlconfig,
												       SimConfig     *config,
												       xmlNodePtr     node);
void						 sim_xml_config_set_config_framework (SimXmlConfig  *xmlconfig,
				 										 SimConfig     *config,
														  xmlNodePtr     node);
void						 sim_xml_config_set_config_datasource (SimXmlConfig  *xmlconfig,
																      SimConfig     *config,
																      xmlNodePtr     node);
void 						sim_xml_config_set_config_datasources (SimXmlConfig  *xmlconfig,
										       SimConfig     *config,
				    						   xmlNodePtr     node);
void						 sim_xml_config_set_config_directive (SimXmlConfig  *xmlconfig,
				    							 SimConfig     *config,
				   							  xmlNodePtr     node);
void						 sim_xml_config_set_config_reputation (SimXmlConfig  *xmlconfig,
				   					  SimConfig     *config,
				   					  xmlNodePtr     node);
void						 sim_xml_config_set_config_server (SimXmlConfig  *xmlconfig,
									  SimConfig     *config,
				 						 xmlNodePtr     node);
void						 sim_xml_config_set_config_notify (SimXmlConfig  *xmlconfig,
								  SimConfig     *config,
								  xmlNodePtr     node);
void						 sim_xml_config_set_config_notifies (SimXmlConfig  *xmlconfig,
				  					  SimConfig     *config,
				   					 xmlNodePtr     node);
void					 sim_xml_config_set_config_smtp (SimXmlConfig  *xmlconfig,
									SimConfig     *config,
									xmlNodePtr     node);
void					 sim_xml_config_set_config_rserver (SimXmlConfig  *xmlconfig,
																  SimConfig     *config,
																  xmlNodePtr     node);


void						 sim_xml_config_set_config_rservers (SimXmlConfig  *xmlconfig,
																		SimConfig     *config,
																		xmlNodePtr     node);
void						 sim_xml_config_set_config_forensic_storage (SimXmlConfig  *xmlconfig,
											                      SimConfig     *config,
																			      xmlNodePtr     node);

void						 sim_xml_config_set_config_idm (SimXmlConfig  *xmlconfig,
											                      SimConfig     *config,
																			      xmlNodePtr     node);

void sim_xml_config_set_config_log (SimXmlConfig  *xmlconfig, SimConfig     *config, xmlNodePtr     node);


G_END_DECLS

#endif
// vim: set tabstop=2:
