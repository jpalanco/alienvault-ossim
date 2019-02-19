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

#ifndef __SIM_CONFIG_H__
#define __SIM_CONFIG_H__ 1

#include <glib.h>
#include <glib-object.h>
#include <gnet.h>

typedef struct _SimConfig            SimConfig;
typedef struct _SimConfigClass       SimConfigClass;
typedef struct _SimConfigDS          SimConfigDS;
typedef struct _SimConfigNotify      SimConfigNotify;
typedef struct _SimConfigMsspContext SimConfigMsspContext;

#include "sim-enums.h"
#include "sim-command.h"
#include "sim-database.h"
#include "sim-role.h"
#include "sim-uuid.h"

G_BEGIN_DECLS

#define SIM_TYPE_CONFIG                  (sim_config_get_type ())
#define SIM_CONFIG(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, SIM_TYPE_CONFIG, SimConfig))
#define SIM_CONFIG_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, SIM_TYPE_CONFIG, SimConfigClass))
#define SIM_IS_CONFIG(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, SIM_TYPE_CONFIG))
#define SIM_IS_CONFIG_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), SIM_TYPE_CONFIG))
#define SIM_CONFIG_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), SIM_TYPE_CONFIG, SimConfigClass))


struct _SimConfig
{
  GObject parent;

  GList   *datasources;
  GList   *notifies;

  SimUuid * component_id;
  SimUuid * default_context_id;
  SimUuid * default_engine_id;

  gchar   *notify_prog;

  gboolean copy_siem_events;  //copy events from acid_event_input to acid_event

  struct {
    gchar    *filename;
  } log;

/*  struct {
    gchar    *name;
    gchar    *ip;
    gchar    *interface;
  } sensor;
*/
  struct {
    gchar    *filename;
  } directive;

  struct {
    gchar    *filename;
  } reputation;

  struct {
    SimUuid * id;
    gchar   * name;
    gchar   * ip;
    gint      port;
    SimRole	* role;
    gchar	* HA_ip;
    gint	  HA_port;
  } server;

  struct {
    gchar    *host;
    gint      port;
  } smtp;

  struct {
    gchar    *name;
    gchar    *host;
    gint      port;
  } framework;
 /*
	#define PROPERTY_FORENSIC_STORAGE_PATH "path"
	#define PROPERTY_FORENSIC_STORAGE_SIG_TYPE "signature_type"
	#define PROPERTY_FORENSIC_STORAGE_SIG_CIPHER "signature_cipher"
	#define PROPERTY_FORENSIC_STORAGE_SIG_BIT "signature_bit_length"
	#define PROPERTY_FORENSIC_STORAGE_ENC_TYPE "encryption_type"
	#define PROPERTY_FORENSIC_STORAGE_ENC_CIPHER "encryption_cipher"
	#define PROPERTY_FORENSIC_STORAGE_ENC_BIT "encryption_bit_length"
	#define PROPERTY_FORENSIC_STORAGE_KEY_SOURCE "key_source"
	#define PROPERTY_FORENSIC_STORAGE_SIG_PRV_KEY_PATH "sig_prv_key_path"
	#define PROPERTY_FORENSIC_STORAGE_SIG_PUB_KEY_PATH "sig_pub_key_path"
	#define PROPERTY_FORENSIC_STORAGE_ENC_PRV_KEY_PATH "enc_prv_key_path"
	#define PROPERTY_FORENSIC_STORAGE_ENC_PUB_KEY_PATH "enc_pub_key_path"
	*/
  struct {
	gchar	*path;
	gchar *sig_type;
	gchar	*sig_cipher;
	gint	sig_bit;
	gchar *enc_type;
	gchar	*enc_cipher;
	gint	enc_bit;
	gchar	*key_source;
	gchar	*sig_prv_key_path;
	gchar *sig_pass;
	gchar *sig_pub_key_path;
	gchar *enc_prv_key_path;
	gchar *enc_pass;
	gchar *enc_pub_key_path;
	gboolean	sem_activated; //This is different from the SimRole. This depends on license; if the machine hasn't got SEM license or if the SEM stanzas are not in the config file, this will be false
  gchar *enc_cert_path;
  } forensic_storage;	

  struct {
    gboolean activated;
    gboolean mssp;
    gchar *ip;
    gint port;
  } idm;
};

struct _SimConfigClass {
  GObjectClass parent_class;
};

struct _SimConfigDS
{
  gchar    *name;
  gchar    *provider;
  gchar    *dsn;

  // DSN splitted
  gchar    *port;
  gchar    *username;
  gchar    *password;
  gchar    *database;
  gchar    *host;
  gchar    *unix_socket;

  gboolean  local_DB;     //if False: database queries are executed against other ossim server in other machine.
  gchar		 *rserver_name;     //if local_DB=False, this is the server where we have to connect to.
};

struct _SimConfigNotify {
  gchar    *emails;
  GList    *alarm_risks;
};

GType             sim_config_get_type                        (void);
SimConfig *       sim_config_new                             (void);
SimConfigDS *     sim_config_ds_new                          (void);
void              sim_config_ds_free                         (SimConfigDS  *ds);
void              sim_config_ds_set_dsn_string               (SimConfigDS  *ds,
                                                              gchar        *dsn_string);

SimConfigDS *     sim_config_get_ds_by_name                  (SimConfig    *config,
                                                              const gchar  *name);

SimConfigNotify * sim_config_notify_new                      (void);
void              sim_config_notify_free                     (SimConfigNotify *notify);

void              sim_config_set_data_role                  (SimConfig   *config,
                                                             SimCommand  *cmd);
SimRole *         sim_config_get_server_role                (SimConfig   *config);
void              sim_config_set_server_role                (SimConfig   *config,
                                                             SimRole     *role);

SimConfigDS *     sim_config_ds_clone                       (SimConfigDS *);

gboolean          sim_config_load_server_ids                (SimConfig   *config,
                                                             SimDatabase *database);

G_END_DECLS

#endif /* __SIM_CONFIG_H__ */

// vim: set tabstop=2:

