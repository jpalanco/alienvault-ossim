# -*- coding: utf-8 -*-
#
#  License:
#
#  Copyright (c) 2014 AlienVault
#  All rights reserved.
#
#  This package is free software; you can redistribute it and/or modify
#  it under the terms of the GNU General Public License as published by
#  the Free Software Foundation; version 2 dated June, 1991.
#  You may not use, modify or distribute this program under any other version
#  of the GNU General Public License.
#
#  This package is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.
#
#  You should have received a copy of the GNU General Public License
#  along with this package; if not, write to the Free Software
#  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#  MA  02110-1301  USA
#
#
#  On Debian GNU/Linux systems, the complete text of the GNU General
#  Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
#  Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#

import time

from celerymethods.tasks import celery_instance
from db.methods.sensor import get_sensor_ip_from_sensor_id
from ansiblemethods.system.network import get_iface_stats, get_iface_list

@celery_instance.task
def check_traffic_get_rx_stats (sensor_id,ifaces,delay):
  # First I need the sensor ip
  (result,admin_ip) = get_sensor_ip_from_sensor_id (sensor_id)
  dresult = {}
  msg = "Internal Error"
  r = False
  if result == True:
    (r,data) = get_iface_list (admin_ip)
    if r == True:
      if ifaces == []:
        l = data.keys()
      else:
        l = ifaces
      # Now check traffic
      (r,statsfirst) = get_iface_stats (admin_ip)
      if r == True:
        time.sleep (delay)
        (r,statslast ) = get_iface_stats (admin_ip)
        # Now, check each result:
        for iface in l:
          first = statsfirst.get(iface)
          last = statslast.get(iface)

          if first is not None and last is not None:
            if ((last['RX']-first['RX'])> 0):
              dresult[iface] = 'yes'
            else:
              dresult[iface] = 'no'
        msg = "OK"
        r = True
      else:
        r = False
        msg = "Error obtains iface stats"
    else:
      r = False
      msg = "Can't obtain iface admin ip => " + str(admin_ip)
  else:
     r = False
     msg = "No admin ip for uuid " + sensor_id

  return (r, msg,dresult)
