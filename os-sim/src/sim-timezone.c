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

/*
 * This is totally ripped off from GLib >= 2.26. Frankly, I don't understand why I'm doing this.
 */

#include <stdlib.h>
#include <string.h>
#include "sim-timezone.h"

enum
{
  DESTROY,
  LAST_SIGNAL
};

/* unaligned */
typedef struct { gchar bytes[8]; } gint64_be;
typedef struct { gchar bytes[4]; } gint32_be;
typedef struct { gchar bytes[4]; } guint32_be;

static inline gint64 gint64_from_be (const gint64_be be) {
  gint64 tmp; memcpy (&tmp, &be, sizeof tmp); return GINT64_FROM_BE (tmp);
}

static inline gint32 gint32_from_be (const gint32_be be) {
  gint32 tmp; memcpy (&tmp, &be, sizeof tmp); return GINT32_FROM_BE (tmp);
}

static inline guint32 guint32_from_be (const guint32_be be) {
  guint32 tmp; memcpy (&tmp, &be, sizeof tmp); return GUINT32_FROM_BE (tmp);
}

typedef enum
{
  TIME_TYPE_STANDARD,
  TIME_TYPE_DAYLIGHT,
  TIME_TYPE_UNIVERSAL
} time_type;

struct tzhead
{
  gchar      tzh_magic[4];
  gchar      tzh_version;
  guchar     tzh_reserved[15];

  guint32_be tzh_ttisgmtcnt;
  guint32_be tzh_ttisstdcnt;
  guint32_be tzh_leapcnt;
  guint32_be tzh_timecnt;
  guint32_be tzh_typecnt;
  guint32_be tzh_charcnt;
};

struct ttinfo
{
  gint32_be tt_gmtoff;
  guint8    tt_isdst;
  guint8    tt_abbrind;
};

struct _SimTimezonePrivate
{
  gchar   *name;

  GMappedFile *zoneinfo;

  const struct tzhead *header;
  const struct ttinfo *infos;
  const gint64_be     *trans;
  const guint8        *indices;
  const gchar         *abbrs;
  gint                 timecnt;
};

static gpointer parent_class = NULL;

// Static declarations.
static gint
sim_timezone_find_interval        (SimTimezone * tz,
                                   time_type  type,
                                   gint64     time);

inline static gint64
sim_timezone_interval_local_start (SimTimezone * tz,
                                   gint        interval);

inline static gint64
sim_timezone_interval_local_end   (SimTimezone * tz,
                                   gint        interval);

inline static gboolean
sim_timezone_interval_isdst       (SimTimezone *tz,
                                   gint       interval);

inline static gint64
sim_timezone_interval_start       (SimTimezone * tz,
                                   gint        interval);

inline static gint64
sim_timezone_interval_end         (SimTimezone * tz,
                                   gint        interval);

inline static gint32
sim_timezone_interval_offset      (SimTimezone *tz,
                                   gint       interval);

inline static const struct ttinfo *
sim_timezone_interval_info        (SimTimezone *tz,
                                   gint       interval);


/* GType Functions */

static void
sim_timezone_impl_dispose (GObject  *gobject)
{
  G_OBJECT_CLASS (parent_class)->dispose (gobject);
}

static void
sim_timezone_impl_finalize (GObject  *gobject)
{
  SimTimezone * timezone = SIM_TIMEZONE (gobject);

  g_free (timezone->_priv->name);
  g_mapped_file_unref (timezone->_priv->zoneinfo);

  g_free (timezone->_priv);
  G_OBJECT_CLASS (parent_class)->finalize (gobject);
  return;
}

static void
sim_timezone_class_init (SimTimezoneClass * class)
{
  GObjectClass * object_class = G_OBJECT_CLASS (class);

  parent_class = g_type_class_peek_parent (class);

  object_class->dispose = sim_timezone_impl_dispose;
  object_class->finalize = sim_timezone_impl_finalize;
  return;
}

static void
sim_timezone_instance_init (SimTimezone * timezone)
{
  timezone->_priv = g_new0 (SimTimezonePrivate, 1);
  return;
}

GType
sim_timezone_get_type (void)
{
  static GType object_type = 0;

  if (!object_type)
  {
    static const GTypeInfo type_info = {
      sizeof (SimTimezoneClass),
      NULL,
      NULL,
      (GClassInitFunc) sim_timezone_class_init,
      NULL,
      NULL,                       /* class data */
      sizeof (SimTimezone),
      0,                          /* number of pre-allocs */
      (GInstanceInitFunc) sim_timezone_instance_init,
      NULL                        /* value table */
    };

    g_type_init ();
    object_type = g_type_register_static (G_TYPE_OBJECT, "SimTimezone", &type_info, 0);
  }
  return object_type;
}

/**
 * sim_timezone_new:
 * @identifier: the identifier of the timezone in 'Zone/City' format.
 *
 * Returns a new #SimTimezone using its identifier.
 */
SimTimezone *
sim_timezone_new (const gchar * identifier)
{
  if (!identifier)
    return (NULL);

  SimTimezone   * tz = SIM_TIMEZONE (g_object_new (SIM_TYPE_TIMEZONE, NULL));
  gchar       * filename;
  const gchar * tzdir = "/usr/share/zoneinfo"; // Hardcoded to avoid using getenv()

  tz->_priv->name = g_strdup (identifier);

  filename = g_build_filename (tzdir, identifier, NULL);

  tz->_priv->zoneinfo = g_mapped_file_new (filename, FALSE, NULL);
  g_free (filename);

  if (tz->_priv->zoneinfo != NULL)
  {
    const struct tzhead *header = (const struct tzhead *) g_mapped_file_get_contents (tz->_priv->zoneinfo);
    gsize size = g_mapped_file_get_length (tz->_priv->zoneinfo);

    /* we only bother to support version 2 */
    if (size < sizeof (struct tzhead) || memcmp (header, "TZif2", 5))
    {
      g_mapped_file_unref (tz->_priv->zoneinfo);
      tz->_priv->zoneinfo = NULL;
    }
    else
    {
      gint typecnt;

      tz->_priv->header = (const struct tzhead *)
        (((const gchar *) (header + 1)) +
         guint32_from_be(header->tzh_ttisgmtcnt) +
         guint32_from_be(header->tzh_ttisstdcnt) +
         8 * guint32_from_be(header->tzh_leapcnt) +
         5 * guint32_from_be(header->tzh_timecnt) +
         6 * guint32_from_be(header->tzh_typecnt) +
         guint32_from_be(header->tzh_charcnt));

      typecnt = guint32_from_be (tz->_priv->header->tzh_typecnt);
      tz->_priv->timecnt = guint32_from_be (tz->_priv->header->tzh_timecnt);
      tz->_priv->trans   = (gconstpointer) (tz->_priv->header + 1);
      tz->_priv->indices = (gconstpointer) (tz->_priv->trans + tz->_priv->timecnt);
      tz->_priv->infos   = (gconstpointer) (tz->_priv->indices + tz->_priv->timecnt);
      tz->_priv->abbrs   = (gconstpointer) (tz->_priv->infos + typecnt);
    }
  }

  return tz;
}

/**
 * sim_timezone_get_offset:
 * @timezone: a #SimTimezone object.
 * @now: Epoch time.
 *
 * Returns the current zone offset.
 */
gint32
sim_timezone_get_offset (SimTimezone * tz, time_t now)
{
  if (!tz)
    return (0);

  gint interval = sim_timezone_find_interval (tz, TIME_TYPE_UNIVERSAL, (guint64)now);

  return (gint32_from_be (sim_timezone_interval_info (tz, interval)->tt_gmtoff));
}


/**
 * sim_timezone_find_interval:
 *
 */
static gint
sim_timezone_find_interval (SimTimezone * tz,
                            time_type  type,
                            gint64     time)
{
  gint i;

  if (tz->_priv->zoneinfo == NULL)
    return 0;

  for (i = 0; i < tz->_priv->timecnt; i++)
    if (time <= sim_timezone_interval_end (tz, i))
      break;

  if (type == TIME_TYPE_UNIVERSAL)
    return i;

  if (time < sim_timezone_interval_local_start (tz, i))
  {
    if (time > sim_timezone_interval_local_end (tz, --i))
      return -1;
  }

  else if (time > sim_timezone_interval_local_end (tz, i))
  {
    if (time < sim_timezone_interval_local_start (tz, ++i))
      return -1;
  }

  else if (sim_timezone_interval_isdst (tz, i) != (gboolean)type)
  {
    if (i && time <= sim_timezone_interval_local_end (tz, i - 1))
      i--;

    else if (i < tz->_priv->timecnt && time >= sim_timezone_interval_local_start (tz, i + 1))
      i++;
  }

  return i;
}

/**
 * sim_timezone_interval_local_start:
 *
 */
inline static gint64
sim_timezone_interval_local_start (SimTimezone * tz,
                                   gint          interval)
{
  if (interval)
    return (sim_timezone_interval_start (tz, interval) + sim_timezone_interval_offset (tz, interval));

  return (G_MININT64);
}

/**
 * sim_timezone_interval_local_end:
 *
 */
inline static gint64
sim_timezone_interval_local_end (SimTimezone * tz,
                                 gint        interval)
{
  if (interval < tz->_priv->timecnt)
    return (sim_timezone_interval_end (tz, interval) + sim_timezone_interval_offset (tz, interval));

  return (G_MAXINT64);
}

/**
 * sim_timezone_interval_isdst:
 *
 */
inline static gboolean
sim_timezone_interval_isdst (SimTimezone *tz,
                             gint       interval)
{
  return sim_timezone_interval_info (tz, interval)->tt_isdst;
}

/**
 * sim_timezone_interval_start:
 *
 */
inline static gint64
sim_timezone_interval_start (SimTimezone *tz,
                             gint       interval)
{
  if (interval)
    return gint64_from_be (tz->_priv->trans[interval - 1]);

  return G_MININT64;
}

/**
 * sim_timezone_interval_end:
 *
 */
inline static gint64
sim_timezone_interval_end (SimTimezone *tz,
                           gint       interval)
{
  if (interval < tz->_priv->timecnt)
    return gint64_from_be (tz->_priv->trans[interval]) - 1;

  return G_MAXINT64;
}

/**
 * sim_timezone_interval_offset:
 *
 */
inline static gint32
sim_timezone_interval_offset (SimTimezone *tz,
                              gint       interval)
{
  return gint32_from_be (sim_timezone_interval_info (tz, interval)->tt_gmtoff);
}

/**
 * sim_timezone_interval_info:
 *
 *
 */
inline static const struct ttinfo *
sim_timezone_interval_info (SimTimezone *tz,
                            gint       interval)
{
  if (interval)
    return tz->_priv->infos + tz->_priv->indices[interval - 1];

  return tz->_priv->infos;
}


#ifdef USE_UNITTESTS
/*************************************************************
 ********************      Unit tests      *******************
 *************************************************************/

static gboolean sim_timezone_test1 (void);

static gboolean
sim_timezone_test1 (void)
{
  SimTimezone * timezone1 = sim_timezone_new ("Europe/Madrid");
  SimTimezone * timezone2 = sim_timezone_new ("EST");
  SimTimezone * timezone3 = sim_timezone_new ("Etc/Zulu");
  SimTimezone * timezone4 = sim_timezone_new ("Europe/Brussels");

  time_t cur_time = time(NULL);

  if ((sim_timezone_get_offset (timezone1, cur_time)) != (sim_timezone_get_offset (timezone4, cur_time)))
    return (FALSE);

  if ((sim_timezone_get_offset (timezone1, cur_time)) != (sim_timezone_get_offset (timezone2, cur_time) + 14400))
    return (FALSE);

  if ((sim_timezone_get_offset (timezone1, cur_time)) != (sim_timezone_get_offset (timezone3, cur_time) + 3600))
    return (FALSE);

  return (TRUE);
}

void
sim_timezone_register_tests (SimUnittesting *engine)
{
  sim_unittesting_append (engine, "sim_timezone_test1 - Timezones", sim_timezone_test1, TRUE);
}

#endif //USE_UNITTESTS
