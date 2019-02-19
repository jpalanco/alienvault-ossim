/*
  License:

  Copyright (c) 2015 AlienVault
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

#include <glib.h>
#include <glib/gstdio.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>
#include <unistd.h>
#include <string.h>
#include <errno.h>
#include <signal.h>
#include <execinfo.h>


#include "avr-db.h"
#include "avr-log.h"
#include "avr-correlation.h"

#include "avr-tld.h"

#define SIM_DEBUG_ERR_FILE "/var/log/alienvault/rhythm/rhythm.err"
#define PID_FILE "/var/run/alienvault/rhythm.pid"

// Main loop.
static GMainLoop * main_loop = NULL;

//
// Static methods
//

/**
 * init_pid_file:
 * @void
 *
 * 
 * Returns: 
 */
static gboolean
init_pid_file (void)
{
  const gchar * pid_file = PID_FILE;
  gint pid_fd = 0;
  gchar * current_pid = NULL;
  gint current_pid_len = 0;

  if ((pid_fd = g_open (pid_file, O_WRONLY|O_CREAT|O_TRUNC, S_IRUSR|S_IWUSR)) < 0)
  {
    g_critical ("Cannot create pid file \"%s\"", pid_file);
    return (FALSE);
  }
  else
  {
    if (lockf (pid_fd, F_TLOCK, 0) < 0)
    {
      g_critical ("Cannot lock pid file \"%s\"", pid_file);
      return (FALSE);
    }
    else
    {
      current_pid_len = asprintf (&current_pid, "%d", getpid());
      (void)write (pid_fd, current_pid, current_pid_len);
      g_free (current_pid);
      close(pid_fd);
    }
  }
  return (TRUE);
}

/**
 * sim_debug_print_bt:
 *
 */
static void
sim_debug_print_bt ()
{
	gint fd, nptrs;
	gpointer buffer [100];

	fd = g_open (SIM_DEBUG_ERR_FILE, O_RDWR | O_CREAT | O_TRUNC, S_IRUSR | S_IWUSR);
	nptrs = backtrace (buffer, 100);
	backtrace_symbols_fd (buffer, nptrs, fd);
  (void)close (fd);

	return;
}

/**
 * sim_debug_terminate:
 *
 */
static void
sim_debug_terminate (gint signum, gboolean core)
{
  unlink(PID_FILE);
  
  if (core)
	{
		signal(signum, SIG_DFL);
    kill(getpid(), signum);
	}
	else
  {
		g_main_loop_quit (main_loop);
	}
}

/**
 * sim_debug_sim_debug_on_signal:
 * @signum:
 *
 */
static void
sim_debug_on_signal (gint signum, siginfo_t * siginfo, gpointer context)
{
  (void)context;

  switch (signum)
  {
	case SIGPIPE:
		g_message ("Error: SIGPIPE in comms");
		break;
	case SIGFPE:
	case SIGILL:
	case SIGABRT:
	case SIGSEGV:
    g_message ("Signal: SIGSEGV/%d", signum);
		sim_debug_print_bt ();
		sim_debug_terminate (signum, TRUE);
		break;
	case SIGQUIT:
    g_message ("Signal: SIGQUIT");
		sim_debug_terminate (signum, TRUE);
		break;
	case SIGTERM:
    g_message ("Signal: SIGTERM");
    (void)signum;
    (void)siginfo;
    g_main_loop_quit (main_loop);
    break;
	case SIGINT:
    g_message ("Signal: SIGINT");
		sim_debug_terminate (signum, FALSE);
		break;
	case SIGBUS:
		break;
  }
}

/**
 * sim_debug_init_signals:
 *
 * System signal handlers
 */
static void
sim_debug_init_signals (void)
{
  struct sigaction callback;
  struct sigaction ignore;

  memset (&callback, '\0', sizeof(callback));
  callback.sa_sigaction = &sim_debug_on_signal;
  callback.sa_flags = SA_SIGINFO;
  memset (&ignore, '\0', sizeof(ignore));
  ignore.sa_handler = SIG_IGN;

  sigaction (SIGINT, &callback, NULL);
  sigaction (SIGHUP, &ignore, NULL);
  sigaction (SIGQUIT, &callback, NULL);
  sigaction (SIGABRT, &callback, NULL);
  sigaction (SIGILL, &callback, NULL);
  sigaction (SIGBUS, &callback, NULL);
  sigaction (SIGFPE, &callback, NULL);
  sigaction (SIGSEGV, &callback, NULL);
  sigaction (SIGTERM, &callback, NULL);
  sigaction (SIGPIPE, &callback, NULL);
}


/**
 * main:
 * @void
 *
 * 
 * Returns: 
 */
gint
main (gint argc, gchar *argv[])
{
  // Default configuration file
  const gchar * config_file = "/etc/alienvault/rhythm/rhythm.cfg";
  GKeyFile * config_key_file = NULL;
  gchar * output_file = NULL;
  gint output_file_size_limit = 0;
  gchar * db_socket = NULL;

  // Default Command Line Options
  GOptionContext * opt_context = NULL;
  gchar * description = NULL, * help_msg = NULL;
  static gboolean version = FALSE;
  static gboolean debug_mode = FALSE;
  static gboolean daemon_mode = FALSE;

  // file position
  goffset current_position = G_MINOFFSET;

  // Error related.
  GError * error = NULL;
  gchar * error_msg = NULL;

#ifdef USE_UNITTESTS
  gboolean unittests = FALSE;
#endif

    // Command line options
  static GOptionEntry options[] =
  {
    { "version", 'v', 0, G_OPTION_ARG_NONE, &version, "Show version number", NULL },
    { "debug_mode", 'd', 0, G_OPTION_ARG_NONE, &debug_mode, "Run in debug mode", NULL },
    { "daemon_mode", 'D', 0, G_OPTION_ARG_NONE, &daemon_mode, "Run as daemon", NULL },
#ifdef USE_UNITTESTS
    {"unittests", 'u', 0, G_OPTION_ARG_NONE, &unittests, "Run the registered unittests and exit", NULL},
#endif
    { NULL, '\0', 0, G_OPTION_ARG_NONE, NULL, NULL, NULL }
  };

  AvrLog * event_log = NULL;
  AvrLog * message_log = NULL;
  AvrCorrelation ** correlations = NULL;
  AvrCorrelationReader * correlation_reader = NULL;
  gint i = 0;

  // Command line argument parsing.
  opt_context = g_option_context_new ("AlienVault Rhythm");
  description = g_strdup_printf("\nAlienvault Rhythm v%s \n\n \t(c) 2015 AlienVault\n", VERSION);
  g_option_context_set_description(opt_context, description);
  g_free(description);

  g_option_context_set_help_enabled (opt_context, TRUE);
  g_option_context_add_main_entries(opt_context, options, NULL);
  g_option_context_parse(opt_context, &argc, &argv, &error);

  if (error)
  {
    help_msg = g_option_context_get_help (opt_context, FALSE, NULL);
    g_message ("Unknown option.\n");
    g_print ("%s", help_msg);

    g_free (help_msg);
    g_error_free (error);
    g_option_context_free(opt_context);
    return (0);
  }
  g_option_context_free(opt_context);

  sim_debug_init_signals();

  // Initialize main Loop
  main_loop = g_main_loop_new (NULL, FALSE);

  // Initialize message logger.
  if ((message_log = avr_log_new (DEFAULT_LOG_FILE, DEFAULT_LOG_FILE_SIZE)) == NULL)
  {
    return (-9);
  }
  else
  {
    if (debug_mode)
      avr_log_set_level (message_log, G_LOG_LEVEL_DEBUG);

    avr_log_set_handler (message_log);
  }
  g_message ("Starting AlienVault Rhythm...");


  // Show version number
  if (version)
  {
    g_message ("AlienVault Rhythm version : %s (c) 2015 AlienVault\n", VERSION);
    return (0);
  }

  // Read data from the configuration file.
  if (!(g_file_test (config_file, G_FILE_TEST_EXISTS)))
  {
    g_critical ("Default configuration file \"%s\" does not exist", config_file);
    return (-2);
  }

  config_key_file = g_key_file_new ();
  if (!g_key_file_load_from_file(config_key_file, config_file, G_KEY_FILE_NONE, &error))
  {
    if (error != NULL)
    {
      g_critical ("Cannot load configuration file \"%s\": %s", config_file, error->message);
      g_error_free (error);
    }
    else
      g_critical ("Cannot load configuration file \"%s\"", config_file);

    return (-3);
  }

  // Get output file path.
  if (!(output_file = g_key_file_get_string (config_key_file, "Output", "file", &error)))
  {
    if (error != NULL)
    {
      g_critical ("Cannot get output file path: %s", error->message);
      g_error_free (error);
    }
    else
      g_critical ("Cannot get output file path");

    return (-4);
  }

  // Get output file max allowed size.
  output_file_size_limit = g_key_file_get_integer (config_key_file, "Output", "size_limit", &error);
  if (error != NULL)
  {
    g_critical ("Cannot get output file maximum size: %s", error->message);
    g_error_free (error);
    return (-5);
  }

  // Get database socket path.
  if (!(db_socket = g_key_file_get_string (config_key_file, "Database", "socket", &error)))
  {
    if (error != NULL)
    {
      g_critical ("Cannot get database socket path: %s", error->message);
      g_error_free (error);
    }
    else
      g_critical ("Cannot get database socket path");

    return (-6);
  }

  // Free the configuration file struct.
  g_key_file_free (config_key_file);

  // Initialize daemon mode.
  if (daemon_mode)
  {
    g_message ("Initializing %s in daemon mode...", PACKAGE);

    if (daemon(0, 0) != 0)
    {
      error_msg = strerror (errno);
      g_critical ("Cannot spawn daemon: %s", error_msg);
      return (-7);
    }

    if (!(init_pid_file ()))
      return (-8);
  }
  else
  {
    g_message ("Initializing %s...", PACKAGE);
  }


  // Initialize event logger.
  if ((event_log = avr_log_new (output_file, output_file_size_limit)) == NULL)
  {
    return (-10);
  }

  // Initialize TLD (Top Level Domains) Object
  AvrTld *tld;
  tld = avr_tld_new();

  // Initialize correlation objects.
  correlations = g_new0 (AvrCorrelation *, AVR_TYPES);
  for (i = 0; i < AVR_TYPES; i++)
  {
    correlations[i] = avr_correlation_new (i, event_log, (const gchar *)db_socket, tld);
    if (correlations[i] == NULL)
      return (-11);
  }

  correlation_reader = avr_correlation_reader_new (correlations);

  g_message ("Starting correlation threads");

  // Start threads.
  for (i = 0; i < AVR_TYPES; i++)
  {
    avr_correlation_run (correlations[i]);
  }

  avr_correlation_reader_run (correlation_reader, current_position);

  g_message ("AlienVault Rhythm is running");
  // Let's get this running!
  g_main_loop_run (main_loop);

  // Clean-up routines, if any.
  g_message ("Stopping AlienVault Rhythm...");

  return 0;
}
