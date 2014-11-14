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

#include "sim-idm-ilocal.h"

#include <sys/stat.h>
#include <uuid/uuid.h>
#include <string.h>
#include <stdlib.h>
#include <time.h>
#include <mqueue.h>
#include <unistd.h>
#include <errno.h>
#include <glib.h>

#include "sim-session.h"
#include "sim-command.h"
#include "sim-log.h"

#define TAG_SIZE 14
#define CTX_SIZE 16

static gpointer sim_idm_ilocal_run (gpointer data);

/*
 * Called from server process
 */
void
sim_idm_ilocal_new (SimIdm *idm)
{
  struct mq_attr attr;
  mode_t omask;

  /* initialize the queue attributes */
  attr.mq_flags = 0;
  attr.mq_maxmsg = 1;
  attr.mq_msgsize = SIM_MQ_MAXSIZE;
  attr.mq_curmsgs = 0;

  /* create the message queue */
  omask = umask(0);
  idm->mqr = mq_open(SIM_IDM_MQ_NAME_RCV, O_CREAT | O_RDONLY, S_IRUSR|S_IWUSR|S_IRGRP|S_IWGRP, &attr);
  if (idm->mqr == (mqd_t)-1)
    g_message ("%s: error opening receive queue: errno %d", __func__, errno);

  idm->mqs = mq_open(SIM_IDM_MQ_NAME_SND, O_CREAT | O_WRONLY, S_IRUSR|S_IWUSR|S_IRGRP|S_IWGRP, &attr);
  if (idm->mqs == (mqd_t)-1)
    g_message ("%s: error opening send queue: errno %d", __func__, errno);
  (void)umask(omask);

  idm->qmutex = g_mutex_new ();
}

void
sim_idm_ilocal_new_child (void)
{
  g_thread_create (sim_idm_ilocal_run, NULL, FALSE, NULL);
}

/*
 * Called from IDM process
 */
static gpointer
sim_idm_ilocal_run (gpointer data)
{
  mqd_t mqs, mqr;
  gchar *src_value, *dst_value, *aux_value;
  gchar mqi_buffer[SIM_MQ_MAXSIZE];
  ssize_t bytes_read;
  struct timespec tm;
  SimInet *src_ip, *dst_ip;

  // unused parameter
  (void) data;

  mqs = mq_open(SIM_IDM_MQ_NAME_RCV, O_WRONLY);
  if (mqs == (mqd_t)-1)
  {
    g_message ("%s: error opening receive queue: errno %d", __func__, errno);
    g_message ("%s: launch the server with IDM enabled", __func__);
    exit (EXIT_FAILURE);
  }

  mqr = mq_open(SIM_IDM_MQ_NAME_SND, O_RDONLY);
  if (mqs == (mqd_t)-1)
  {
    g_message ("%s: error opening send queue: errno %d", __func__, errno);
    g_message ("%s: launch the server with IDM enabled", __func__);
    exit (EXIT_FAILURE);
  }

  g_message ("%s: IDM local interface is running", __func__);

  for (;;)
  {
    if ((bytes_read = mq_receive (mqr, mqi_buffer, SIM_MQ_MAXSIZE, NULL)) < 0)
    {
      ossim_debug ("%s: answer receive error, is the message queue created?: errno %d", __func__, errno);
      sleep (10);
      continue;
    }

    mqi_buffer[bytes_read] = '\0';

    aux_value = strchr (mqi_buffer + TAG_SIZE + CTX_SIZE, '-');
    dst_ip = sim_inet_new_from_string (aux_value + 1);
    *aux_value = '\0';
    src_ip = sim_inet_new_from_string (mqi_buffer + TAG_SIZE + CTX_SIZE);

    if (src_ip)
    {
      src_value = sim_idm_get ((uuid_t *) (mqi_buffer + TAG_SIZE), src_ip);
      g_object_unref (src_ip);
    }
    else
      src_value = g_strdup ("-\n");

    if (dst_ip)
    {
      dst_value = sim_idm_get ((uuid_t *) (mqi_buffer + TAG_SIZE), dst_ip);
      g_object_unref (dst_ip);
    }
    else
      dst_value = g_strdup ("-\n");

    strcpy (mqi_buffer + TAG_SIZE + CTX_SIZE, src_value);
    aux_value = strchr (mqi_buffer + TAG_SIZE + CTX_SIZE, '\n');
    strcpy (aux_value + 1, dst_value);

    clock_gettime (CLOCK_REALTIME, &tm);
    tm.tv_nsec += SIM_MQ_TIMEOUT;
    if (tm.tv_nsec >= 1000000000) /* 1 second */
    {
      tm.tv_sec  += 1;
      tm.tv_nsec -= 1000000000;
    }

    if (mq_timedsend (mqs, mqi_buffer, TAG_SIZE + CTX_SIZE + strlen (src_value) + strlen (dst_value) + 1, 0, &tm) < 0)
      ossim_debug ("%s: answer send error, is the server alive?: errno %d", __func__, errno);

    g_free (src_value);
    g_free (dst_value);
  }

  return NULL;
}

/*
 * Called from server process
 */
SimCommand **
sim_idm_ilocal_get (SimIdm *idm, SimSession *session, SimUuid *context, gchar *src_ip, gchar *dst_ip)
{
  uuid_t *context_uuid;
  gchar *tid;
  gint keylen;
  gchar *aux_value;
  gchar mqs_buffer[SIM_MQ_MAXSIZE];
  gchar *session_ip;
  ssize_t bytes_read;
  struct timespec tm;
  SimCommand **ret = NULL;
  int i;

  g_return_val_if_fail (idm, NULL);
  g_return_val_if_fail (SIM_IS_SESSION (session), NULL);
  g_return_val_if_fail (SIM_IS_UUID (context), NULL);

  tid = g_strdup_printf ("%14p", g_thread_self ());

  context_uuid = sim_uuid_get_uuid (context);
  keylen = g_snprintf (mqs_buffer, SIM_MQ_MAXSIZE, "%sxxxxxxxxxxxxxxxx%s-%s", tid, src_ip ? src_ip : "X", dst_ip ? : "X");
  for (i = 0; i < 16; i++)
    mqs_buffer[TAG_SIZE + i] = ((unsigned char *) context_uuid)[i];

  g_mutex_lock (idm->qmutex);

  clock_gettime (CLOCK_REALTIME, &tm);
  tm.tv_nsec += SIM_MQ_TIMEOUT;
  if (tm.tv_nsec >= 1000000000) /* 1 second */
  {
    tm.tv_sec += 1;
    tm.tv_nsec -= 1000000000;
  }

  if (mq_timedsend (idm->mqs, mqs_buffer, keylen, 0, &tm) < 0)
    ossim_debug ("%s: petition send error, is IDM alive?: errno %d", __func__, errno);
  else
  {
    /* read until we get our reply or we timed out */
    for (;;)
    {
      clock_gettime (CLOCK_REALTIME, &tm);
      tm.tv_nsec += SIM_MQ_TIMEOUT_RCV;
      if (tm.tv_nsec >= 1000000000)     /* 1 second */
      {
        tm.tv_sec += 1;
        tm.tv_nsec -= 1000000000;
      }

      if ((bytes_read = mq_timedreceive (idm->mqr, mqs_buffer, SIM_MQ_MAXSIZE, NULL, &tm)) < 0)
        ossim_debug ("%s: petition receive error, is IDM alive?: errno %d", __func__, errno);
      else
      {
        mqs_buffer[bytes_read] = '\0';

        if (strncmp (mqs_buffer, tid, TAG_SIZE) != 0)
          continue;

        ret = (SimCommand **) g_malloc0 (sizeof (SimCommand *) * 2);

        aux_value = strchr (mqs_buffer + TAG_SIZE + CTX_SIZE, '\n');
        if (g_strcmp0 (aux_value + 1, "-\n") == 0)
          g_debug ("%s: MISS - ctx %s dst ip %s", __func__, sim_uuid_get_string (context), dst_ip);
        else
        {
          session_ip = sim_inet_get_canonical_name (sim_session_get_ia (session));
          ret[1] = sim_command_new_from_buffer (aux_value + 1, sim_command_get_default_scan(), session_ip);
          g_free (session_ip);
          g_debug ("%s: HIT - ctx %s dst ip %s", __func__, sim_uuid_get_string (context), dst_ip);
        }

        *(aux_value + 1) = '\0';

        if (g_strcmp0 (mqs_buffer + TAG_SIZE + CTX_SIZE, "-\n") == 0)
          g_debug ("%s: MISS - ctx %s src ip %s", __func__, sim_uuid_get_string (context), src_ip);
        else
        {
          session_ip = sim_inet_get_canonical_name (sim_session_get_ia (session));
          ret[0] = sim_command_new_from_buffer (mqs_buffer + TAG_SIZE + CTX_SIZE, sim_command_get_default_scan(), session_ip);
          g_free (session_ip);
          g_debug ("%s: HIT - ctx %s src ip %s", __func__, sim_uuid_get_string (context), src_ip);
        }
      }

      break;
    }
  }

  g_mutex_unlock (idm->qmutex);

  g_free (tid);

  return ret;
}

