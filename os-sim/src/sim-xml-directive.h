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

#if !defined(__sim_xml_directive_h__)
#  define __sim_xml_directive_h__

#include <libxml/parser.h>
#include <libxml/tree.h>
#include <libxml/valid.h>
#include <libxml/xmlschemas.h>

#include <glib-object.h>

G_BEGIN_DECLS

#define SIM_TYPE_XML_DIRECTIVE            (sim_xml_directive_get_type())
#define SIM_XML_DIRECTIVE(obj)            (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_XML_DIRECTIVE, SimXmlDirective))
#define SIM_XML_DIRECTIVE_CLASS(klass)    (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_XML_DIRECTIVE, SimXmlDirectiveClass))
#define SIM_IS_XML_DIRECTIVE(obj)         (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_XML_DIRECTIVE))
#define SIM_IS_XML_DIRECTIVE_CLASS(klass) (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_XML_DIRECTIVE))

typedef struct _SimXmlDirective        SimXmlDirective;
typedef struct _SimXmlDirectiveClass   SimXmlDirectiveClass;
typedef struct _SimXmlDirectivePrivate SimXmlDirectivePrivate;

#include "sim-rule.h"
#include "sim-directive.h"
#include "sim-container.h"
#include "sim-context.h"
#include "sim-unittesting.h"


struct _SimXmlDirective {
	GObject object;
	SimXmlDirectivePrivate *_priv;
};

struct _SimXmlDirectiveClass {
	GObjectClass parent_class;

	/* signals */
	void (*changed) (SimXmlDirective * xmldb);
};

GType            sim_xml_directive_get_type                (void);
void             sim_xml_directive_register_type           (void);

SimXmlDirective* sim_xml_directive_new                     (void);
SimXmlDirective* sim_xml_directive_new_from_file           (const gchar     *file);

int             sim_xml_directive_load_file               (SimXmlDirective *xml_directive,
                                                            const gchar     *file);

void             sim_xml_directive_set_context             (SimXmlDirective *xmldirect,
                                                            SimContext      *context);

void             sim_xml_directive_changed                 (SimXmlDirective *xmldirect);
void             sim_xml_directive_reload                  (SimXmlDirective *xmldirect);
gboolean         sim_xml_directive_save                    (SimXmlDirective *xmldirect,
                                                            const gchar     *file);
gchar*           sim_xml_directive_to_string               (SimXmlDirective *xmldirect);

SimDirective*    sim_xml_directive_new_directive_from_node (SimXmlDirective *xmldirect,
                                                            xmlNodePtr       node);
GNode*           sim_xml_directive_new_rule_from_node      (SimXmlDirective *xmldirect,
                                                            xmlNodePtr       node,
                                                            GNode           *root,
                                                            gint             level,
                                                            gint             directive_id);

GList*           sim_xml_directive_get_directives          (SimXmlDirective *xmldirect);
void             sim_xml_directive_set_directives          (SimXmlDirective *xmldirect,
                                                            GList           *directives);

SimDirective *   find_directive                            (SimXmlDirective *xmldirect,
                                                            gint             id);

gboolean         sim_xml_directive_new_group_from_node     (SimXmlDirective *xmldirect,
                                                            xmlNodePtr       node);
gboolean         sim_xml_directive_load_ctx_groups         (SimXmlDirective *self,
                                                            SimDatabase     *database);

SimRuleVarType   sim_xml_directive_get_rule_var_from_property (const gchar  *var);

void             sim_xml_directive_new_append_directive_from_node (SimXmlDirective   *xmldirect,
                                                                   xmlNodePtr         node,
                                                                   SimDirectiveGroup *group);

#ifdef USE_UNITTESTS
SimDirective *   sim_new_test_directive                    (void);
void             sim_xml_directive_register_tests          (SimUnittesting *);
#endif

G_END_DECLS

#endif
// vim: set tabstop=2:
