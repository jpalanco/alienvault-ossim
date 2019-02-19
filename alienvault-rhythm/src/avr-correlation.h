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

#ifndef __AVR_CORRELATION_H__
#define __AVR_CORRELATION_H__

#include <glib-object.h>
#include <glib.h>

#include "avr-db.h"
#include "avr-log.h"
#include "avr-tld.h"

G_BEGIN_DECLS

typedef struct _AvrCorrelation              AvrCorrelation;
typedef struct _AvrCorrelationClass         AvrCorrelationClass;
typedef struct _AvrCorrelationPrivate       AvrCorrelationPrivate;

typedef struct _AvrCorrelationReader        AvrCorrelationReader;
typedef struct _AvrCorrelationReaderClass   AvrCorrelationReaderClass;
typedef struct _AvrCorrelationReaderPrivate AvrCorrelationReaderPrivate;

#define AVR_TYPE_CORRELATION                  (avr_correlation_get_type ())
#define AVR_CORRELATION(obj)                  (G_TYPE_CHECK_INSTANCE_CAST (obj, AVR_TYPE_CORRELATION, AvrCorrelation))
#define AVR_CORRELATION_CLASS(klass)          (G_TYPE_CHECK_CLASS_CAST (klass, AVR_TYPE_CORRELATION, AvrCorrelationClass))
#define AVR_IS_CORRELATION(obj)               (G_TYPE_CHECK_INSTANCE_TYPE (obj, AVR_TYPE_CORRELATION))
#define AVR_IS_CORRELATION_CLASS(klass)       (G_TYPE_CHECK_CLASS_TYPE ((klass), AVR_TYPE_CORRELATION))
#define AVR_CORRELATION_GET_CLASS(obj)        (G_TYPE_INSTANCE_GET_CLASS ((obj), AVR_TYPE_CORRELATION, AvrCorrelationClass))

#define AVR_TYPE_CORRELATION_READER           (avr_correlation_reader_get_type ())
#define AVR_CORRELATION_READER(obj)           (G_TYPE_CHECK_INSTANCE_CAST (obj, AVR_TYPE_CORRELATION_READER, AvrCorrelationReader))
#define AVR_IS_CORRELATION_READER(obj)        (G_TYPE_CHECK_INSTANCE_CAST (obj, AVR_TYPE_CORRELATION_READER, AvrCorrelationReader))

struct _AvrCorrelation {
  GObject parent;
  AvrCorrelationPrivate *_priv;
};

struct _AvrCorrelationReader {
  GObject parent;
  AvrCorrelationReaderPrivate *_priv;
};

struct _AvrCorrelationClass {
  GObjectClass parent_class;
};

struct _AvrCorrelationReaderClass {
  GObjectClass parent_class;
};

void                    avr_correlation_init                    (void);
void                    avr_correlation_clear                   (void);

GType                   avr_correlation_get_type                (void);

AvrCorrelation *        avr_correlation_new                     (AvrType,
                                                                 AvrLog *,
                                                                 const gchar *,
                                                                 AvrTld *);

gboolean                avr_correlation_load_data               (AvrCorrelation *);
void                    avr_correlation_run                     (AvrCorrelation *);

GType                   avr_correlation_reader_get_type         (void);

AvrCorrelationReader *  avr_correlation_reader_new              (AvrCorrelation ** correlations);

gboolean                avr_correlation_reader_load_data        (AvrCorrelation *);
goffset                 avr_correlation_reader_run              (AvrCorrelationReader *,
                                                                 goffset);

G_END_DECLS

#endif
