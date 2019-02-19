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


#ifndef _SIM_PARSER_H
#define _SIM_PARSER_H 1

#include <glib.h>
#include <glib-object.h>
#include "sim-object.h"
#include "sim-command.h"
#include "config.h"
G_BEGIN_DECLS
typedef struct _SimParser        SimParser;
typedef struct _SimParserClass   SimParserClass;
typedef struct _SimParserPrivate SimParserPrivate;
#define SIM_TYPE_PARSER                     (sim_parser_get_type ())
#define SIM_PARSER(obj)                     (G_TYPE_CHECK_INSTANCE_CAST ((obj), SIM_TYPE_PARSER,SimParser))
#define SIM_IS_PARSER(obj)                  (G_TYPE_CHECK_INSTANCE_TYPE ((obj),SIM_TYPE_PARSER))
#define SIM_PARSER_CLASS(kclass)            (G_TYPE_CHECK_CLASS_CAST ((kclass),SIM_TYPE_PARSER,SimParserClass))
#define SIM_PARSER_IS_PARSER_CLASS(kclass)  (G_TYPE_CHECK_CLASS_TYPE ((kclass),SIM_TYPE_PARSER))
#define SIM_PARSER_GET_CLASS(obj)           (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_PARSER,SimParserClass))

struct _SimParser
{
  GObject parent;
  SimParserPrivate *priv;
};
struct _SimParserClass
{
  GObjectClass  parent_class;
};

GType           sim_parser_get_type                     (void);
void            sim_parser_register_type                (void);

SimParser *     sim_parser_new                          (void);
SimCommand *    sim_parser_bson                         (SimParser *self,
                                                         const guint8 *bson,
                                                         gsize bsonsize);
void            sim_parser_reset_filter                 (SimParser * self);
void            sim_parser_set_filter                   (SimParser * self,
                                                         gchar **list);


#ifdef USE_UNITTESTS
void sim_parser_register_tests (SimUnittesting *engine);
#endif
G_END_DECLS
#endif /* _SIM_PARSER_H */
