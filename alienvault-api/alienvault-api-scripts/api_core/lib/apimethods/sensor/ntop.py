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
"""
API methods to manage Ntop
"""
from db.methods.sensor import get_sensor_properties, get_sensor_ip_from_sensor_id
from ansiblemethods.sensor.ntop import configure_ntop as ans_configure_ntop

def configure_ntop (sensor_id, force=False):
    """
    Set the Ntop configuration in a Sensor profile.
    @param sensor_id: Sensor id
    """
    # Do nothing if ntop is already configured in this sensor
    (success, properties) = get_sensor_properties (sensor_id)
    if not success:
        return (False, properties)
    if properties['has_ntop'] and not force:
        return (True, 'ntop already configured')

    (success, sensor_ip) = get_sensor_ip_from_sensor_id(sensor_id)
    if not success:
        return (False, "Bad sensor id: %s" % str(sensor_id))
    return ans_configure_ntop(sensor_ip)
