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

#include "sim-log.h"

#include <glib.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <unistd.h>
#include <fcntl.h>
#include <time.h>

/****debug****/
#include <stdio.h>
/****debug****/

#include "sim-util.h"
#include "os-sim.h"
/* The extern code */
extern SimMain  ossim;
/* Static prototypes */
static guint  sim_log_external_reopen              (void);
static time_t sim_log_timestamp                    (char *timebuf);
static gchar *sim_log_build_msg                    (const gchar     *timestamp,
                                                    const gchar     *log_domain,
                                                    GLogLevelFlags   log_level,
                                                    const gchar     *message);
static void   sim_log_handler                      (const gchar     *log_domain,
                                                   GLogLevelFlags   log_level,
                                                   const gchar     *message,
                                                   gpointer         data);


int ossim_log_flag = 0;

/*
 * This function is useful if its needed to reopen the log file (logrotate and so on)
 */
guint
sim_log_reopen(void)
{
  gboolean ok=TRUE;

  if ((ossim.log.fd = open (ossim.log.filename, O_WRONLY|O_CREAT|O_APPEND, S_IRUSR|S_IWUSR|S_IRGRP|S_IWGRP|S_IROTH|S_IWOTH))< 0)
  {
    ok=FALSE;
		gchar *msg = g_strdup_printf ("Log File %s: Can't create", ossim.log.filename);
		g_log_default_handler (G_LOG_DOMAIN, G_LOG_LEVEL_MASK | G_LOG_FLAG_FATAL | G_LOG_FLAG_RECURSION, msg, NULL);
		g_free(msg);
  }

	return ok;
}

/*
 * This function is useful if its needed to reopen the log external file
 */
static guint
sim_log_external_reopen (void)
{
  gboolean ok = TRUE;

  /* External log file */
  if ((ossim.log.fd_external = open (ossim.log.filename_external, O_WRONLY|O_CREAT|O_APPEND, S_IRUSR|S_IWUSR|S_IRGRP|S_IWGRP|S_IROTH|S_IWOTH)) < 0)
  {
    ok = FALSE;
    gchar *msg = g_strdup_printf ("Log External File %s: Can't create", ossim.log.filename_external);
    g_log_default_handler (G_LOG_DOMAIN, G_LOG_LEVEL_MASK | G_LOG_FLAG_FATAL | G_LOG_FLAG_RECURSION, msg, NULL);
    g_free (msg);
  }

  return ok;
}

/*
 * Return the number of seconds since epoch and
 * a log timestamp in a char *. Die if case of errors
 * The user must deallocate the string
 */


static time_t
sim_log_timestamp(char *timebuf)
{
   time_t t;
  struct tm ltime;
  if ((t = time(NULL))==(time_t)-1){
       g_message("OSSIM-Critical: can't obtain current time in %s:%d",__FILE__,__LINE__);
    exit(EXIT_FAILURE);
  }
  if (localtime_r(&t,&ltime)==NULL){
     g_message("OSSIM-Critical: can't obtain local time in %s:%d",__FILE__,__LINE__);
    exit(EXIT_FAILURE);
  }

  if (strftime(timebuf,TIMEBUF_SIZE,"%F %T",&ltime)==0){
   g_message("OSSIM-Critical: can't generate timestamp in %s:%d",__FILE__,__LINE__);
   exit(EXIT_FAILURE);
  }

  return t;
}
/*
*
 * Helper function to sim_log_handler. Try to write in the log.
 */
inline void
sim_log_write (gchar *msg, const gchar *log_domain)
{
  struct stat tmp;
  ssize_t write_ret;

  if (log_domain != NULL)
  {
    if (stat (ossim.log.filename, &tmp) == 0)  // check if the file log exists or not.
    {
      write_ret = write (ossim.log.fd, msg, strlen (msg));
      if (write_ret == -1)
        g_message ("Can't write to log");
    }
    else
    {
      if (sim_log_reopen ())
      {
        write_ret = write (ossim.log.fd, msg, strlen (msg));
        if (write_ret == -1)
          g_message ("Can't write to log");
      }
    }
  }
  else
  {
    /* NULL log_domain are written on external file log */
    if (stat (ossim.log.filename_external, &tmp) == 0)  // check if the file log exists or not.
    {
      write_ret = write (ossim.log.fd_external, msg, strlen (msg));
      if (write_ret == -1)
        g_message ("Can't write to log");
    }
    else
    {
      if (sim_log_external_reopen ())
      {
        write_ret = write (ossim.log.fd_external, msg, strlen (msg));
        if (write_ret == -1)
          g_message ("Can't write to log");
      }
    }
  }
}

static gchar *
sim_log_build_msg (const gchar     *timestamp,
                   const gchar     *log_domain,
                   GLogLevelFlags   log_level,
                   const gchar     *message)
{
  gchar *msg;

  switch (log_level)
  {
  case G_LOG_LEVEL_CRITICAL:
    msg = g_strdup_printf ("%s %s-Critical: %s\n",timestamp, log_domain, message);
    break;
  case G_LOG_LEVEL_WARNING:
    msg = g_strdup_printf ("%s %s-Warning: %s\n", timestamp, log_domain, message);
    break;
  case G_LOG_LEVEL_MESSAGE:
    msg = g_strdup_printf ("%s %s-Message: %s\n", timestamp, log_domain, message);
    break;
  case G_LOG_LEVEL_INFO:
    msg = g_strdup_printf ("%s %s-Info: %s\n", timestamp, log_domain, message);
    break;
  case G_LOG_LEVEL_DEBUG:
    msg = g_strdup_printf ("%s %s-Debug: %s\n", timestamp, log_domain, message);
    break;
  case G_LOG_LEVEL_ERROR: /*A G_LOG_LEVEL_ERROR is always a FATAL error. FIXME?.  */
  default:
    msg = g_strdup_printf ("%s %s-Error: %s\n", timestamp, log_domain, message);
    break;
  }

  return msg;
}

/*
 *
 * Log handler called each time an event occurs.
 *
 *
 */
static void
sim_log_handler (const gchar     *log_domain,
                 GLogLevelFlags   log_level,
                 const gchar     *message,
                 gpointer         data)
{
  time_t  t;
  gchar   *msg, *msgr;
  gchar   timestamp[TIMEBUF_SIZE+1];

  // Duplicate message handling
  static GStaticMutex mutex = G_STATIC_MUTEX_INIT;
  static time_t last_t = 0;
  static guint count = 0;
  static GLogLevelFlags last_level;
  static gchar *last_domain = NULL;
  static gchar *last_message = NULL;

  g_return_if_fail (message);
  g_return_if_fail (ossim.log.fd);

  // unused parameter
  (void) data;

  if (ossim.log.level < log_level)
    return;

  g_static_mutex_lock (&mutex);
  t = sim_log_timestamp (timestamp);

  if ((last_level == log_level)
      && (last_t == t)
      && last_message && message
      && ! strcmp (last_message, message))
  {
    count++;
    g_static_mutex_unlock (&mutex);
    return;
  }

  if (count > 0)
  {
    msgr = g_strdup_printf ("Last message repeated %d times: %s", count, last_message);
    msg = sim_log_build_msg (timestamp, last_domain, last_level, msgr);
    sim_log_write (msg, last_domain);
    g_free (msgr);
    count = 0;
  }

  last_t = t;
  g_free (last_domain);
  g_free (last_message);
  last_level = log_level;
  last_domain = g_strdup (log_domain);
  last_message = g_strdup (message);

  msg = sim_log_build_msg (timestamp, log_domain, log_level, message);
  sim_log_write (msg, log_domain);
  g_free (msg);

  g_static_mutex_unlock (&mutex);
}


/*
 *
 *  Starts logging and pass all the messages from the Glib (thanks to G_LOG_LEVEL_MASK)
 *      to the ossim logger.
 *
 */
void
sim_log_init (void)
{
  /* Init */
  ossim.log.filename = NULL;
  ossim.log.filename_external = NULL;
  ossim.log.fd = 0;
  ossim.log.fd_external = 0;

  ossim.log.handler[0] = 0;
  ossim.log.handler[1] = 0;
  ossim.log.handler[2] = 0;

  ossim.log.level = G_LOG_LEVEL_MESSAGE;

  /* File Logs */
  if (ossim.config->log.filename)
  {
    ossim.log.filename = g_strdup (ossim.config->log.filename);
  }
  else
  {
    /* Verify Directory */
    if (!g_file_test (OS_SIM_LOG_DIR, G_FILE_TEST_IS_DIR))
      g_error ("Log Directory %s: Is invalid", OS_SIM_LOG_DIR);

    ossim.log.filename = g_strdup_printf ("%s/%s", OS_SIM_LOG_DIR, SIM_LOG_FILE);
  }

  /* External logs File */
  ossim.log.filename_external = g_strdup_printf ("%s.external", ossim.log.filename);

  switch (simCmdArgs.debug)
  {
    case 0:
      ossim.log.level = 0;
      break;
    case 1:
      ossim.log.level = G_LOG_LEVEL_ERROR;
      break;
    case 2:
      ossim.log.level = G_LOG_LEVEL_CRITICAL;
      break;
    case 3:
      ossim.log.level = G_LOG_LEVEL_WARNING;
      break;
    case 4:
      ossim.log.level = G_LOG_LEVEL_MESSAGE;
      break;
    case 5:
      ossim.log.level = G_LOG_LEVEL_INFO;
      break;
    case 6:
      ossim.log.level = G_LOG_LEVEL_DEBUG;
      break;
    default:
      ossim.log.level = 0;
      break;
  }

  sim_log_reopen (); //well, in this case this is not a reopen, just an open :)
  sim_log_external_reopen ();

  sim_log_set_handlers (); //set the handlers
}

/*
 *
 *
 *
 */
void
sim_log_free (void)
{
  g_free (ossim.log.filename);
  close (ossim.log.fd);
}

/*
 *
 */
void 
sim_log_set_handlers()
{
 /* Log Handler. We store it so we can do a g_log_remove_handler in case the logging fails sometime.*/
  ossim.log.handler[0] = g_log_set_handler (NULL, G_LOG_LEVEL_MASK | G_LOG_FLAG_FATAL
                     | G_LOG_FLAG_RECURSION, sim_log_handler, NULL);

  ossim.log.handler[1] = g_log_set_handler ("GLib", G_LOG_LEVEL_MASK | G_LOG_FLAG_FATAL
                     | G_LOG_FLAG_RECURSION, sim_log_handler, NULL);

  ossim.log.handler[2] = g_log_set_handler (G_LOG_DOMAIN, G_LOG_LEVEL_MASK | G_LOG_FLAG_FATAL
                     | G_LOG_FLAG_RECURSION, sim_log_handler, NULL);

}



// vim: set tabstop=2:
