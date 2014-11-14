/*
  License:

  Copyright (c) 2012-2013 AlienVault
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

#include "sim-geoip.h"

#include <GeoIP.h>
#include <gnet.h>

#include "sim-inet.h"

#ifdef USE_UNITTESTS
#include <string.h>

#include "sim-unittesting.h"
#endif

static GeoIP *geoip_db = NULL;
static GeoIP *geoipV6_db = NULL;

void
sim_geoip_new (void)
{
  geoip_db = GeoIP_open("/usr/share/geoip/GeoIP.dat", GEOIP_MEMORY_CACHE);
  geoipV6_db = GeoIP_open("/usr/share/geoip/GeoIPv6.dat", GEOIP_MEMORY_CACHE);
}

const gchar *
sim_geoip_lookup (SimInet *inet)
{
  uint8_t *inet_addr;
  const gchar *ret;

  g_return_val_if_fail (SIM_IS_INET (inet), 0);

  inet_addr = sim_inet_get_in_addr (inet);

  if (sim_inet_is_ipv4 (inet))
  {
    if (inet_addr[0] || inet_addr[1] || inet_addr[2] || inet_addr[3])
    {
      unsigned long r_addr;

      r_addr = inet_addr[0] << 24 | inet_addr[1] << 16 | inet_addr[2] << 8 | inet_addr[3];
      ret = GeoIP_code_by_id (GeoIP_id_by_ipnum (geoip_db, r_addr));

    }
    else
    {
      ret = "--";
    }
  }
  else
  {
    geoipv6_t r_addr;
    memcpy (r_addr.__in6_u.__u6_addr8, inet_addr, sizeof (geoipv6_t));

    ret = GeoIP_code_by_id (GeoIP_id_by_ipnum_v6 (geoipV6_db, r_addr));
  }

  g_free (inet_addr);

  return ret;
}

void
sim_geoip_free (void)
{
  GeoIP_delete (geoip_db);
  GeoIP_delete (geoipV6_db);
}

#ifdef USE_UNITTESTS

/**************************************************************
 ****************          Unit tests          ****************
 **************************************************************/

static int sim_geoip_test1 (void);

static int
sim_geoip_test1 (void)
{
  SimInet *inet;
  gint ret = 1;

  sim_geoip_new ();

  inet = sim_inet_new_from_string ("8.8.8.8");
  if (strcmp (sim_geoip_lookup (inet), "US"))
  {
    ret = 0;
    goto exit;
  }

  g_object_unref (inet);

  inet = sim_inet_new_from_string ("0.0.0.0");
  if (strcmp (sim_geoip_lookup (inet), "--"))
  {
    ret = 0;
    goto exit;
  }

  g_object_unref (inet);

  inet = sim_inet_new_from_string ("2001:4860:4860::8888");
  if (strcmp (sim_geoip_lookup (inet), "US"))
  {
    ret = 0;
    goto exit;
  }

  g_object_unref (inet);

  inet = sim_inet_new_from_string ("::");
  if (strcmp (sim_geoip_lookup (inet), "--"))
  {
    ret = 0;
    goto exit;
  }

exit:
  g_object_unref (inet);

  sim_geoip_free ();

  return ret;
}

void
sim_geoip_register_tests (SimUnittesting *engine)
{
  sim_unittesting_append(engine, "sim_geoip_test1 - lookup", sim_geoip_test1, 1);
}

#endif
