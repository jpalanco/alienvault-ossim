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

#include "sim-debug.h"

#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>
#include <unistd.h>
#include <signal.h>
#include <execinfo.h>
#include <glib.h>
#include <glib/gstdio.h>

#include "sim-log.h"
#include "os-sim.h"

// Global variables
extern SimMain ossim;
extern SimCmdArgs simCmdArgs;
static void sim_debug_on_signal (gint signum, siginfo_t * siginfo, gpointer context);

/**
 * sim_debug_init_signals:
 *
 * System signal handlers
 */
void
sim_debug_init_signals (void)
{
  struct sigaction callback;

  memset (&callback, '\0', sizeof(callback));
  callback.sa_sigaction = &sim_debug_on_signal;
  callback.sa_flags = SA_SIGINFO;

  sigaction (SIGINT, &callback, NULL);
  sigaction (SIGHUP, &callback, NULL);
  sigaction (SIGQUIT, &callback, NULL);
  sigaction (SIGABRT, &callback, NULL);
  sigaction (SIGILL, &callback, NULL);
  sigaction (SIGBUS, &callback, NULL);
  sigaction (SIGFPE, &callback, NULL);
  sigaction (SIGSEGV, &callback, NULL);
  sigaction (SIGTERM, &callback, NULL);
  sigaction (SIGPIPE, &callback, NULL);
  sigaction (45, &callback, NULL);
  sigaction (46, &callback, NULL);
  sigaction (47, &callback, NULL);
  sigaction (48, &callback, NULL);
  sigaction (49, &callback, NULL);
}

/**
 * sim_debug_print_bt:
 *
 */
void
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
 * sim_debug_sim_debug_on_signal:
 * @signum:
 *
 */
static void
sim_debug_on_signal (gint signum, siginfo_t * siginfo, gpointer context)
{
  (void)context;
  g_message ("Received signal %d from PID %d, UID %d",
             signum, (gint)siginfo->si_pid, (gint)siginfo->si_uid);

  switch (signum)
  {
	case SIGHUP: //FIXME: reload directives, policy, and so on.
		// reopen log file
		sim_log_reopen();
		break;
	case SIGPIPE:
		g_message ("Error: SIGPIPE in comms");
		break;
	case SIGFPE:
	case SIGILL:
	case SIGABRT:
	case SIGSEGV:
		sim_debug_print_bt ();
		sim_debug_terminate (signum, TRUE);
		break;
	case SIGQUIT:
		sim_debug_terminate (signum, TRUE);
		break;
	case SIGTERM:
      (void)signum; /* Gcc stupidy workaround*/
      (void)siginfo;
      g_main_loop_quit (ossim.main_loop);
	case SIGINT:
		sim_debug_terminate (signum, FALSE);
		break;
	case SIGBUS:
		break;
	case 45: //devel signal! (debug on)
		simCmdArgs.dvl = 666;
		ossim_log_flag = 1;
		break;
	case 46: //devel signal! (debug off)
		simCmdArgs.dvl = 0;
		ossim_log_flag = 0;
		break;
	case 47: //debug on (-D6)
		ossim.log.level = G_LOG_LEVEL_DEBUG;
		ossim_log_flag = 1;
		break;
	case 48: //-D6 off
		ossim.log.level = G_LOG_LEVEL_MESSAGE;
		ossim_log_flag = 0;
		break;
  }
}

/**
 * sim_debug_terminate:
 *
 */
void
sim_debug_terminate (gint signum, gboolean core)
{
  unlink(OS_SIM_RUN_FILE);
  
  if (core)
	{
		signal(signum, SIG_DFL);
    kill(getpid(), signum);
	}
	else{
		g_main_loop_quit (ossim.main_loop);
	}
}

/**
 * sim_debug_output
 *
 * print a trace log
*/

#define TXT_BUF_SIZE 4096

void sim_debug_output (const char *format,void *p,const char *file,const char *func,unsigned int line, ...){
	va_list args;
	int len,len1;
	char tempbuf[TXT_BUF_SIZE];
	char *buf;
	char *pbuf = NULL;
	if	 (ossim.log.level == G_LOG_LEVEL_DEBUG){
	 	 va_start (args, line);
	 	 len = snprintf (NULL,0,"%p:%s:%s:%u => ",p,file,func,line);
   	 len1 =vsnprintf (NULL,0,format,args);
	 	 va_end (args);
	 	 if ((len+len1+2)<(TXT_BUF_SIZE)){
	 	 	buf = tempbuf;
	 	 }else{
	 	 	if (( pbuf = buf = malloc (len+len1+2)) == NULL){
	 	 		g_warning ("Can't allocate memory to send log message\n");
	 	 		abort ();
	 	 	}
	 	 }
	 	 if (buf != NULL){
	 	 	len = snprintf (buf,len+1,"%p:%s:%s:%u => ", p, file, func, line);
	 	 	va_start (args,line);
	 	 	len1 = vsnprintf (&buf[len],len1+1,format,args);
	 	 	//g_message (">>>> len:%u len1:%u",len,len1);
	 	 	//buf[len1+len] = '\0';
	 	 	ossim_debug ("%s", buf);
	 	 }
	 	 if (pbuf)
	 	 	free (pbuf);

	 	 va_end (args);
		}
}

void sim_debug_print_backlogs_data (GPtrArray * backlogs, GIOChannel *channel)
{
  guint i;
  SimDirective *backlog;
  gchar * timestamp, *buff;
  SimRule *rule;

  for (i = 0; i < backlogs->len; i++)
  {
    backlog = (SimDirective*) g_ptr_array_index (backlogs, i);
    g_io_channel_write_chars(channel,"===========================================================\n",-1,NULL,NULL);

    buff = g_strdup_printf("Directive_id=%d\n", sim_directive_get_id(backlog));
    g_io_channel_write_chars(channel,buff,-1,NULL,NULL);
    g_free(buff);

    buff = g_strdup_printf("Backlog_id=%s\n", sim_directive_get_backlog_id_str(backlog));
    g_io_channel_write_chars(channel,buff,-1,NULL,NULL);
    g_free(buff);

    buff = g_strdup_printf("Directive Name=%s\n", sim_directive_get_name(backlog));
    g_io_channel_write_chars(channel,buff,-1,NULL,NULL);
    g_free(buff);

    buff = g_strdup_printf("Priority=%d\n", sim_directive_get_priority(backlog));
    g_io_channel_write_chars(channel,buff,-1,NULL,NULL);
    g_free(buff);

    buff = g_strdup_printf("Matched=%d\n", sim_directive_backlog_get_matched(backlog));
    g_io_channel_write_chars(channel,buff,-1,NULL,NULL);
    g_free(buff);

    buff = g_strdup_printf("Is time out=%d\n", sim_directive_backlog_time_out(backlog));
    g_io_channel_write_chars(channel,buff,-1,NULL,NULL);
    g_free(buff);

    timestamp = g_new0 (gchar, 26);
    time_t backlog_timelast = sim_directive_get_time_last(backlog);
    strftime (timestamp, TIMEBUF_SIZE, "%Y-%m-%d %H:%M:%S", localtime ((time_t*)&backlog_timelast));

    buff = g_strdup_printf("time_last=%s\n", timestamp);
    g_io_channel_write_chars(channel,buff,-1,NULL,NULL);
    g_free(timestamp);
    g_free(buff);

    timestamp = g_new0 (gchar, 26);
    time_t backlog_timeout = backlog_timelast + sim_directive_get_time_out(backlog);
    strftime (timestamp, TIMEBUF_SIZE, "%Y-%m-%d %H:%M:%S", localtime ((time_t*)&backlog_timeout));

    buff = g_strdup_printf("time_out=%s\n", timestamp);
    g_io_channel_write_chars(channel,buff,-1,NULL,NULL);
    g_free(timestamp);
    g_free(buff);

    rule = sim_directive_get_root_rule (backlog);
    if(rule)
    {
      g_io_channel_write_chars(channel,"Current Rule:\n",-1,NULL,NULL);
      buff = g_strdup_printf("\t name=%s\n", sim_rule_get_name(rule));
      g_io_channel_write_chars(channel,buff,-1,NULL,NULL);
      g_free(buff);

      buff = g_strdup_printf("\t level=%d\n", sim_rule_get_level(rule));
      g_io_channel_write_chars(channel,buff,-1,NULL,NULL);
      g_free(buff);

      buff = g_strdup_printf("\t reliability=%d\n", sim_rule_get_reliability(rule));
      g_io_channel_write_chars(channel,buff,-1,NULL,NULL);
      g_free(buff);

      timestamp = g_new0 (gchar, 26);
      time_t rule_timelast = sim_rule_get_time_last(rule);
      strftime (timestamp, TIMEBUF_SIZE, "%Y-%m-%d %H:%M:%S", localtime ((time_t*)&rule_timelast));

      buff = g_strdup_printf("\ttime_last=%s\n", timestamp);
      g_io_channel_write_chars(channel,buff,-1,NULL,NULL);
      g_free(timestamp);
      g_free(buff);

      timestamp = g_new0 (gchar, 26);
      time_t rule_timeout = rule_timelast + sim_rule_get_time_out(rule);
      strftime (timestamp, TIMEBUF_SIZE, "%Y-%m-%d %H:%M:%S", localtime ((time_t*)&rule_timeout));

      buff = g_strdup_printf("\ttime_out=%s\n", timestamp);
      g_io_channel_write_chars(channel,buff,-1,NULL,NULL);
      g_free(timestamp);
      g_free(buff);

      buff = g_strdup_printf("\toccurence=%d\n", sim_rule_get_occurrence(rule));
      g_io_channel_write_chars(channel,buff,-1,NULL,NULL);
      g_free(buff);

      buff = g_strdup_printf("\tplugins_sid=%d\n", sim_rule_get_plugin_sid(rule));
      g_io_channel_write_chars(channel,buff,-1,NULL,NULL);
      g_free(buff);

      // TODO: with taxonomy we can have multiple plugin ids or ANY
      buff = g_strdup_printf("\tplugind_id=%d\n", sim_rule_get_plugin_id(rule));
      g_io_channel_write_chars(channel,buff,-1,NULL,NULL);
      g_free(buff);
    }
  }
}

