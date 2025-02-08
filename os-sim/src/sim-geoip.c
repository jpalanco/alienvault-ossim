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
#include <string.h>
#include <maxminddb.h>
#include <gnet.h>

#include "sim-inet.h"

#ifdef USE_UNITTESTS
#include "sim-unittesting.h"
#endif


MMDB_s geoip_db;
char *return_value = "";
void
sim_geoip_new (void)
{
  int status;

  status = MMDB_open("/usr/share/geoip/GeoLite2-Country.mmdb", MMDB_MODE_MMAP, &geoip_db);
  if (MMDB_SUCCESS != status)
  {
    g_message("Failed to load maxminDB");
  }

}

const gchar *
sim_geoip_lookup (SimInet *inet)
{
  const gchar *ret = "--";
  gchar *return_value = "";
  gchar* ip = NULL;

  g_return_val_if_fail (SIM_IS_INET (inet), 0);
  ip = sim_inet_get_canonical_name (inet);

  int mmdb_error, status, gai_error;
  MMDB_lookup_result_s result;
  MMDB_entry_data_s entry_data;

  result = MMDB_lookup_string(&geoip_db, ip, &gai_error, &mmdb_error);
  if ((mmdb_error == MMDB_SUCCESS) && (result.found_entry)){
          status = MMDB_get_value(&result.entry, &entry_data, "country", "iso_code", NULL);
          if (MMDB_SUCCESS == status){
               ret=entry_data.utf8_string;
          }

  }
  return_value = strndup(ret,2);
  ret = '\0';

  return return_value;
}

void
sim_geoip_free (void)
{
  MMDB_close(&geoip_db);
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
