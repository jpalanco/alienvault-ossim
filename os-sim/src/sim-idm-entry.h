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

#ifndef __SIM_IDM_ENTRY_H__
#define __SIM_IDM_ENTRY_H__

#include <glib.h>
#include <glib-object.h>

#include "sim-context.h"
#include "sim-command.h"
#include "sim-database.h"
#include "sim-inet.h"
#include "sim-uuid.h"

#define SIM_TYPE_IDM_ENTRY             (sim_idm_entry_get_type ())
#define SIM_IDM_ENTRY(obj)             (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_IDM_ENTRY, SimIdmEntry))
#define SIM_IDM_ENTRY_CLASS(klass)     (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_IDM_ENTRY, SimIdmEntryClass))
#define SIM_IS_IDM_ENTRY(obj)          (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_IDM_ENTRY))
#define SIM_IS_IDM_ENTRY_CLASS(klass)  (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_IDM_ENTRY))
#define SIM_IDM_ENTRY_GET_CLASS(obj)   (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_IDM_ENTRY, SimIdmEntryClass))

typedef struct _SimIdmEntryChanges SimIdmEntryChanges;
typedef struct _SimIdmEntry        SimIdmEntry;
typedef struct _SimIdmEntryClass   SimIdmEntryClass;
typedef struct _SimIdmEntryPrivate SimIdmEntryPrivate;

struct _SimIdmEntryChanges {
  gboolean host_id;
  gboolean ip;
  gboolean username;
  gboolean hostname;
  gboolean fqdns;
  gboolean mac;
  gboolean os;
  gboolean cpu;
  gboolean memory;
  gboolean video;
  gboolean service;
  gboolean software;
  gboolean state;
};

struct _SimIdmEntry {
  GObject parent;

  SimIdmEntryPrivate *_priv;
};

struct _SimIdmEntryClass {
  GObjectClass parent_class;
};

GType             sim_idm_entry_get_type                      (void);
void              sim_idm_entry_register_type                 (void);
SimIdmEntry *     sim_idm_entry_new_from_command              (SimCommand *command);
SimIdmEntry *     sim_idm_entry_new_from_dm                   (SimUuid *host_id, SimInet *ip, gchar *mac, gchar *hostname, gchar *fqdns);
void              sim_idm_entry_add_properties_from_dm        (GHashTable *entry_table, GdaDataModel *dm_host_properties);
void              sim_idm_entry_add_services_from_dm          (GHashTable *entry_table, GdaDataModel *dm_host_services);
void              sim_idm_entry_add_software_from_dm          (GHashTable *entry_table, GdaDataModel *dm_host_software);
void              sim_idm_entry_finish_from_dm                (GHashTable *entry_table);
void              sim_idm_entry_merge                         (SimIdmEntry *old_entry, SimIdmEntry *new_entry, SimIdmEntryChanges *changes, SimUuid *context_id, SimSensor *sensor);
void              sim_idm_entry_propagate_host                (SimIdmEntry *old_entry, SimIdmEntry *new_entry, SimIdmEntryChanges *changes);
gchar *           sim_idm_entry_property_get_string_json      (const gchar *property);
gchar *           sim_idm_entry_username_get_string_json      (SimIdmEntry *entry);
gchar *           sim_idm_entry_service_get_string_json       (SimIdmEntry *entry);
gchar *           sim_idm_entry_software_get_string_json      (SimIdmEntry *entry);
gchar *           sim_idm_entry_service_get_string_db_insert  (SimIdmEntry *entry, SimDatabase *database);
gchar *           sim_idm_entry_software_get_string_db_insert (SimIdmEntry *entry, SimDatabase *database);
SimInet *         sim_idm_entry_get_ip                        (SimIdmEntry *entry);
const gchar *     sim_idm_entry_get_username                  (SimIdmEntry *entry);
const gchar *     sim_idm_entry_get_hostname                  (SimIdmEntry *entry);
const gchar *     sim_idm_entry_get_fqdns                     (SimIdmEntry *entry);
void              sim_idm_entry_set_hostname                  (SimIdmEntry *entry, const gchar *hostname);
const gchar *     sim_idm_entry_get_mac                       (SimIdmEntry *entry);
void              sim_idm_entry_set_mac                       (SimIdmEntry *entry, const gchar *mac);
const gchar *     sim_idm_entry_get_os                        (SimIdmEntry *entry);
const gchar *     sim_idm_entry_get_cpu                       (SimIdmEntry *entry);
gint              sim_idm_entry_get_memory                    (SimIdmEntry *entry);
const gchar *     sim_idm_entry_get_video                     (SimIdmEntry *entry);
const gchar *     sim_idm_entry_get_service                   (SimIdmEntry *entry);
const gchar *     sim_idm_entry_get_software                  (SimIdmEntry *entry);
const gchar *     sim_idm_entry_get_state                     (SimIdmEntry *entry);
gint8             sim_idm_entry_get_source_id                 (SimIdmEntry *entry);
void              sim_idm_entry_set_source_id                 (SimIdmEntry *entry, gint8 source_id);
SimUuid *         sim_idm_entry_get_host_id                   (SimIdmEntry *entry);
void              sim_idm_entry_set_host_id                   (SimIdmEntry *entry, SimUuid *host_id);
const gchar *     sim_idm_entry_get_string                    (SimIdmEntry *entry);
void              sim_idm_entry_debug_print                   (SimIdmEntry *entry);

#endif
