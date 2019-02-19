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

#ifndef __AVR_LOG_H__
#define __AVR_LOG_H__

#include <glib-object.h>
#include <glib.h>

G_BEGIN_DECLS

typedef struct _AvrLog        AvrLog;
typedef struct _AvrLogClass   AvrLogClass;
typedef struct _AvrLogPrivate AvrLogPrivate;

#define AVR_TYPE_LOG                  (avr_log_get_type ())
#define AVR_LOG(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, AVR_TYPE_LOG, AvrLog))
#define AVR_LOG_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, AVR_TYPE_LOG, AvrLogClass))
#define AVR_IS_LOG(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, AVR_TYPE_LOG))
#define AVR_IS_LOG_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), AVR_TYPE_LOG))
#define AVR_LOG_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), AVR_TYPE_LOG, AvrLogClass))

struct _AvrLog {
  GObject parent;
  AvrLogPrivate *_priv;
};

struct _AvrLogClass {
  GObjectClass parent_class;
};

void                avr_log_init                    (void);
void                avr_log_clear                   (void);

GType               avr_log_get_type                (void);

AvrLog *            avr_log_new                     (const gchar *,
                                                     gint);
void                avr_log_set_level               (AvrLog *,
                                                     gint);

void                avr_log_set_handler             (AvrLog *);
gint                avr_log_get_lines_written       (AvrLog *);
gint                avr_log_get_buffer_len          (AvrLog *);
void                avr_log_inc_buffer_len          (AvrLog *);
void                avr_log_dec_buffer_len          (AvrLog *);
void                avr_log_set_buffer_len          (AvrLog *,
                                                     gint);

void                avr_log_write                   (AvrLog *,
                                                     const gchar *);
void                avr_log_flush_buffer            (AvrLog *);
void                avr_log_write_buffer            (AvrLog *,
                                                     const gchar *,
                                                     const gchar *,
                                                     gint);

inline gint         avr_log_get_lines_written       (AvrLog *);
inline void         avr_log_inc_lines_written       (AvrLog *);
inline void         avr_log_set_lines_written       (AvrLog *,
                                                     gint);

G_END_DECLS

#endif
