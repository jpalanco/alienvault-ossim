# -*- coding: utf-8 -*-
#
#  License:
#
#  Copyright (c) 2013-2015 AlienVault
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

import subprocess
import re


class RegexDict(dict):
    def get_match(self, event):
        return (self[key] for key in self if re.match(key, event))


class AVSysConfigTriggerLaunch(object):
    def __init__(self):

        # Create a RegexDict that associates augeas paths to triggers.
        self.__triggers = RegexDict(
            {'/files/etc/alienvault/network/interfaces.conf/eth\d/[a-z]*': 'alienvault-network-interfaces-config',
             '/files/etc/alienvault/network/vpn.conf/tun\d/network': 'alienvault-network-vpn-net-config',
             '/files/etc/alienvault/network/vpn.conf/tun\d/netmask': 'alienvault-network-vpn-net-config',
             '/files/etc/alienvault/network/vpn.conf/tun\d/port': 'alienvault-network-vpn-net-config',
             '/files/etc/alienvault/network/vpn.conf/tun\d/ca': 'alienvault-network-vpn-crypto-config',
             '/files/etc/alienvault/network/vpn.conf/tun\d/cert': 'alienvault-network-vpn-crypto-config',
             '/files/etc/alienvault/network/vpn.conf/tun\d/key': 'alienvault-network-vpn-crypto-config',
             '/files/etc/alienvault/network/vpn.conf/tun\d/dh': 'alienvault-network-vpn-crypto-config',
             '/files/etc/alienvault/network/vpn.conf/tun\d/enabled': 'alienvault-network-vpn-enabled'}
        )

    def run(self, paths=None):
        if paths is None:
            paths = []
        triggered = set([])
        for path in paths:
            triggered |= set(self.__triggers.get_match(path))

        if triggered:
            for trigger in triggered:
                try:
                    proc = subprocess.Popen(
                        'dpkg-trigger --no-await %s' % trigger,
                        shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE
                    )
                    out, err = proc.communicate()
                except Exception, e:
                    return False, 'Error: %s; Output message: %s' % (str(e), str(err))

        # After saving the configuration files, a reconfig should run and it will trigger the pending changes

        return True, ''
