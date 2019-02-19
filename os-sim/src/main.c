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

#define _GNU_SOURCE

#include "config.h"

#include <getopt.h>
#include <glib.h>
#include <glib/gstdio.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>
#include <unistd.h>
#include <string.h>
#include <signal.h>
#include <libgda/libgda.h>

#include "os-sim.h"
#include "sim-unittesting.h"
#include "sim-reputation.h"
#include "sim-scheduler.h"
#include "sim-organizer.h"
#include "sim-server.h"
#include "sim-session.h"
#include "sim-xml-config.h"
#include "sim-debug.h"
#include "sim-log.h"
#include "sim-xml-directive.h"
#include "sim-util.h"
#include "sim-context.h"
#include "sim-rule.h"
#include "sim-role.h"
#include "sim-idm.h"
#include "sim-db-command.h"
#include "sim-command.h"
#include "sim-idm.h"
#include "sim-host.h"
#include "sim-session.h"
#include "sim-alarm-stats.h"
#include "sim-geoip.h"
#include "sim-server-api.h"
#include "sim-parser.h"


#define OSSIM_CONFIGURATION_FILE_ERROR	2

void sim_pid_init (void);

/* Global Variables */
SimMain     ossim;
GPrivate   *currentgscanner;
SimCmdArgs  simCmdArgs;

/**
 * sim_register_types:
 *
 * Register gobject types *before* using them,
 * to avoid concurrency and glib errors.
 */
static void
sim_register_types (void)
{

  sim_uuid_register_type ();
  sim_context_register_type ();
  sim_engine_register_type ();
  sim_inet_register_type ();
  sim_network_register_type ();
  _priv_sim_event_initialize ();
  sim_xml_directive_register_type ();
  sim_idm_entry_register_type ();
  sim_command_register_type ();
  sim_host_register_type ();
  sim_session_register_type ();
  sim_parser_register_type ();

  return;
}

static gpointer
sim_thread_scheduler (gpointer data)
{
  g_message ("sim_thread_scheduler");

  SimScheduler * scheduler = (SimScheduler *)data;
  sim_scheduler_run (scheduler);

  return NULL;
}

static gpointer
sim_thread_organizer (gpointer data)
{
  g_message ("sim_thread_organizer");

  SimOrganizer *organizer = (SimOrganizer *)data;
  sim_organizer_run (organizer);

  return NULL;
}

static gpointer
sim_thread_server (gpointer data)
{
  g_message ("sim_thread_server");

  SimServer *server = (SimServer *)data;
  sim_server_listen_run (server, TRUE);

  return NULL;
}

static gpointer
sim_thread_idm (gpointer data)
{
  g_message ("sim_thread_idm");

  SimServer *server = (SimServer *) data;
  sim_server_listen_run (server, FALSE);

  return NULL;
}

static gpointer
sim_purge_backlogs_timeout (gpointer user_data)
{
  (void) user_data;

  while (TRUE)
  {
    // sleep 5 minutes
    sleep (300);

    if (ossim.purging_backlogs)
      g_message ("Optimizing, please wait...");
    else
      break;
  }

  return NULL;
}

/*
 *
 *
 *
 */
int
main (int argc, char *argv[])
{
  SimXmlConfig *xmlconfig;
  GThread *thread;
  SimConfigDS *ds;
  gboolean continue_loading = TRUE;

  /* Global variable OSSIM Init */
  ossim.config = NULL;
  ossim.container = NULL;
  ossim.server = NULL;
  ossim.dbossim = NULL;
  ossim.dbsnort = NULL;
  ossim.user_auth = NULL;
  ossim.soup_server = NULL;
  ossim.domain_auth = NULL;
  gchar *aux;
  gchar *aux2;

  aux = g_strdup_printf (OS_SIM_VERSION".free.commit:");
  aux2 = g_strdup_printf (SERVER_VERSION);

  ossim.version = g_strconcat (aux, aux2, NULL);
  g_free (aux);
  g_free (aux2);


  /* Register all class types */
  sim_register_types ();

  /* Command Line Options */
  sim_options (argc, argv);

#ifdef USE_UNITTESTS
  // If the cli option unittest or unittest_regex was set, run the tests and exit
  // Otherwise continue with the normal execution flow
  if (simCmdArgs.unittests == 1 || simCmdArgs.unittest_regex != NULL)
  {
    /* Create DB objects for unittests */
    SimConfigDS *ds = sim_config_ds_new ();
    ossim.dbossim = sim_database_new (ds);
    ossim.dbsnort = sim_database_new (ds);

    /* Create global objs for unittests */
    ossim.container = sim_container_new ();

    SimUnittesting *unittests = sim_unittesting_new ();

    sim_reputation_register_tests(unittests);
    sim_xml_directive_register_tests (unittests);
    sim_inet_register_tests (unittests);
    sim_network_register_tests (unittests);
    sim_context_register_tests (unittests);
    sim_rule_register_tests (unittests);
    //sim_policy_register_tests (unittests);
    sim_role_register_tests (unittests);
    sim_engine_register_tests (unittests);
    sim_uuid_register_tests (unittests);
    sim_directive_register_tests(unittests);
    sim_alarm_stats_register_tests (unittests);
    sim_geoip_register_tests (unittests);

    if (simCmdArgs.unittest_regex != NULL)
      sim_unittesting_set_regex (unittests, simCmdArgs.unittest_regex);

    sim_unittesting_execute_unittests (unittests);
    g_object_unref (unittests);
    g_free (ossim.version);
  }
  else
  {
    g_message ("Server with unit tests enabled can only run unit tests");
  }

  exit (EXIT_SUCCESS);

#endif

  // Init thread vars to avoid concurrency in GScanner
  sim_command_init_tls ();

  /*GNET Init */
  gnet_init ();
  gnet_ipv6_set_policy (GIPV6_POLICY_IPV4_THEN_IPV6);

  /* GDA Init */
  gda_init ();

  /* GeoIP Init */
  sim_geoip_new ();

  /* Config Init */
  if (simCmdArgs.config)
  {
    if (!(xmlconfig = sim_xml_config_new_from_file (simCmdArgs.config)))
    {
      g_print ("Config XML File %s is invalid\n", simCmdArgs.config);
      continue_loading = FALSE;
    }

    if (continue_loading && !(ossim.config = sim_xml_config_get_config (xmlconfig)))
    {
      g_print ("Config is %s invalid\n", simCmdArgs.config);
      continue_loading = FALSE;
    }
  }
  else
  {
    if (!g_file_test (OS_SIM_GLOBAL_CONFIG_FILE, G_FILE_TEST_EXISTS))
    {
      g_print ("Config XML File %s: Not Exists\n", OS_SIM_GLOBAL_CONFIG_FILE);
      continue_loading = FALSE;
    }
    //if init_server is false, configuration file not exist!.
    if (continue_loading && !(xmlconfig = sim_xml_config_new_from_file (OS_SIM_GLOBAL_CONFIG_FILE)))
    {
      g_print ("Config XML File %s is invalid\n", OS_SIM_GLOBAL_CONFIG_FILE);
      continue_loading = FALSE;
    }
    //if init_server is false - Configuration file not exist or is invalid file.
    if (continue_loading && !(ossim.config = sim_xml_config_get_config (xmlconfig)))
    {
      g_print ("Config %s is invalid\n", OS_SIM_GLOBAL_CONFIG_FILE);
      continue_loading = FALSE;
    }
  }

  if(!continue_loading)
  {
    g_print ("****************************************************************\n");
    g_print (" The server can not start, please check your configuration file\n");
    g_print ("****************************************************************\n");
    exit (OSSIM_CONFIGURATION_FILE_ERROR);
  }
  /* Log Init */
  sim_log_init ();

  /* pid init */
  sim_pid_init ();

  /* Catch system signals */
  sim_debug_init_signals ();

  g_message ("Starting OSSIM Server engine. Version: %s", ossim.version);
  ossim_debug ("Starting OSSIM server debug with process id: %d",getpid ());

  /* Database Options */
  ds = sim_config_get_ds_by_name (ossim.config, SIM_DS_OSSIM);
  if (!ds)
  {
    g_print ("Failed to load OSSIM DB XML Config\n");
    ossim_debug ("Failed to load OSSIM DB XML Config");
  }
  ossim.dbossim = sim_database_new (ds);

  ds = sim_config_get_ds_by_name (ossim.config, SIM_DS_SNORT);
  if (!ds)
  {
    g_print ("Failed to load SNORT DB XML Config\n");
    ossim_debug ("Failed to load SNORT DB XML Config");
  }
  ossim.dbsnort = sim_database_new (ds);

  ds = sim_config_get_ds_by_name (ossim.config, SIM_DS_OSVDB);
  if (!ds)
  {
    g_print ("Error / Warning: OSVDB DB XML Config. "
             "If you want to use OSVDB please insert data load into config.xml."
             " If you think that you don't need to use it, or you're running a server "
             "without DB in multiserver mode, just ignore this error.\n");
    ossim_debug ("Error / Warning: OSVDB DB XML Config. If you want to use OSVDB "
                 "please insert data load into config.xml."
                 " If you think that you don't need to use it, or you're running "
                 "a server without DB in multiserver mode, just ignore this error.");
    ossim.dbosvdb = NULL;
  }
  else
  {
    ossim.dbosvdb = sim_database_new (ds);
  }

  if (ossim.config->reputation.filename)
  {
    g_message ("Loading reputation from file: %s", ossim.config->reputation.filename);
    ossim.reputation = sim_reputation_new (ossim.config->reputation.filename);
  }
  else
    g_debug ("%s: no reputation data available", __func__);

  // Purge obsolete backlogs and events in DB
  ossim.purging_backlogs = TRUE;
  (void) g_thread_new ("sim_purge_backlogs_timeout", sim_purge_backlogs_timeout, NULL);
  sim_directive_purge_db_backlogs(ossim.dbossim);
  ossim.purging_backlogs = FALSE;

  // Clean inconsistences in some siem tables
  if(simCmdArgs.check_siem)
    sim_db_clean_siem_tables (ossim.dbsnort);

  /* Create the main loop before any socket is open. It seems that this fixes some errors.*/
  ossim.main_loop = g_main_loop_new (NULL, FALSE);
  GMainContext *mcontext =   g_main_loop_get_context ( ossim.main_loop );
  if (ossim.config->forensic_storage.enc_prv_key_path != NULL &&
      ossim.config->forensic_storage.enc_cert_path != NULL)
  {
    g_message ("API interface using SSL");
    ossim.soup_server = soup_server_new (SOUP_SERVER_PORT,SERVER_API_PORT,SOUP_SERVER_ASYNC_CONTEXT ,mcontext,
        SOUP_SERVER_SSL_CERT_FILE,  ossim.config->forensic_storage.enc_cert_path,
        SOUP_SERVER_SSL_KEY_FILE, ossim.config->forensic_storage.enc_prv_key_path, 
       NULL);

  }
  else
  { 
   ossim.soup_server = soup_server_new (SOUP_SERVER_PORT,SERVER_API_PORT,SOUP_SERVER_ASYNC_CONTEXT ,mcontext, NULL);
    g_message ("API interface using clear text");
  }
  if ((ossim.user_auth = sim_user_auth_new (SimUserAuthDatabase)) == NULL)
  {
    g_message ("Can't init API Auth authorization");
  }
  soup_server_listen_local  (ossim.soup_server, SERVER_API_PORT, 0, NULL);
  g_message ("API port = %u", SERVER_API_PORT);
 
  /* Initializes the listening keywords scanner*/
//  sim_command_start_scanner();

  /* Load Server id, Default correlation engine and context */
  if (!sim_config_load_server_ids (ossim.config, ossim.dbossim))
  {
    g_warning ("ERROR: Cannot start without basic server configuration");
    g_warning ("EXITING...");
    return FALSE;
  }
  if (!sim_server_api_init (ossim.soup_server))
  {
    g_warning ("Can't init API server");
    exit (-1);
  }

  /* Init instances */
  ossim.container = sim_container_new ();

  // Load all the data needed from DB and config.
  if (!sim_container_init (ossim.container, ossim.config, ossim.dbossim))
  {
    g_warning ("ERROR: Can not start without basic server configuration");
    g_warning ("EXITING...");
    return FALSE;
  }

  sim_idm_context_init ();

  ossim.scheduler = sim_scheduler_new (ossim.config);
  ossim.organizer = sim_organizer_new (ossim.config);

  /* Server Thread */
  thread = g_thread_new ("sim_thread_server", sim_thread_server, ossim.server);
  g_return_val_if_fail (thread, EXIT_FAILURE);

  /* IDM Thread */
  thread = g_thread_new ("sim_thread_idm", sim_thread_idm, ossim.server);
  g_return_val_if_fail (thread, EXIT_FAILURE);

  /* Scheduler Thread */
  thread = g_thread_new ("sim_thread_scheduler", sim_thread_scheduler, ossim.scheduler);
  g_return_val_if_fail (thread, EXIT_FAILURE);

  /* Organizer Thread */
  thread = g_thread_new ("sim_thread_organizer", sim_thread_organizer, ossim.organizer);
  g_return_val_if_fail (thread, EXIT_FAILURE);


  g_message ("Server started");

  /* Main Loop */
  g_main_loop_run (ossim.main_loop);
   sim_server_api_stop (ossim.soup_server);
   soup_server_disconnect   (ossim.soup_server);
  g_object_unref (ossim.soup_server);
  if (ossim.user_auth != NULL)
  {
    g_object_unref (ossim.user_auth);
  }
  sim_idm_context_free();


  /* Log Free */
  sim_log_free ();

  exit (EXIT_SUCCESS);
  return 0;
}

// vim: set tabstop=2:
