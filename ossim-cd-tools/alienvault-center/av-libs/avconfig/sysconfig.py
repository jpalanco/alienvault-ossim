# -*- coding: utf-8 -*-
#
#  License:
#
#  Copyright (c) 2013 AlienVault
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

from augeas import Augeas
from utils import is_ipv4
from netinterfaces import get_network_interfaces
from configparsererror import AVConfigParserErrors

class SysConfig (object):
    def __init__ (self, system_ip = None, system_id = None, system_type = None):
        """
        Initialize this object with non system related data, like the OSSIM administration IP address.
        """
        self.__system_ip = system_ip if is_ipv4(system_ip) else None
        self.__system_id = system_id
        self.__system_type = system_type

        self.__augeas = Augeas()

        self.__pending = {}

        # System data
        self.__net_ifaces = {}
        self.__hosts_entries = {}

        # Initialize pure system data.
        self.__reload_config__ ()

    #
    # Public methods
    #
    def is_pending (self):
        """
        Are there pending changes?
        """
        return self.__pending != {}

    def get_pending (self):
        """
        Get which changes are pending
        """
        return self.__pending

    def get_pending_str (self):
        """
        Same as get_pending(), but in human format.
        """
        data = ''
        for key, value in self.__pending.iteritems():
            data += '\n[%s]\n%s' % (key, value)
        return data

    def apply_changes (self):
        """
        Apply pending changes and reload configuration.
        """
        if not self.is_pending():
            return AVConfigParserErrors.ALL_OK

        try:
            self.__augeas.save()
        except IOError, msg:
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.CANNOT_SAVE_SYSCONFIG, str(msg))

        self.__pending = {}
        self.__reload_config__ ()
        return AVConfigParserErrors.ALL_OK

    ### Related to /etc/network/interfaces

    def get_net_iface_config_all (self, include_unconfigured = True, include_lo = False):
        """
        Return a dict with all network interface configurations, in the form {'iface name': 'configuration parameters'}
        """
        net_ifaces = self.__net_ifaces

        if not include_unconfigured:
            net_ifaces = dict([(x, y) for (x, y) in net_ifaces.items() if y['address'] != ''])
        if not include_lo:
            net_ifaces = dict([(x, y) for (x, y) in net_ifaces.items() if x != 'lo'])

        return net_ifaces

    def get_net_iface_config (self, iface):
        """
        Return a dict with the network interface name 'iface' as key, and its configuration attributes as values.
        """
        return {iface: self.__net_ifaces.get(iface)}

    def set_net_iface_config (self, iface, address = None, netmask = None, gateway = None, \
                              dns_search= None, dns_nameservers = None, \
                              broadcast = None, network = None, \
                              is_new = True):
        """
        Set the network configuration for the interface 'iface'.
        """
        iface_path_list = self.__augeas.match("/files/etc/network/interfaces/iface[. = '%s']" % iface)

        if iface_path_list == []:
            if is_new:
                self.__augeas.set("/files/etc/network/interfaces/iface[last() + 1]", iface)
                self.__augeas.set("/files/etc/network/interfaces/auto[last() + 1]/1", iface)
                iface_path = "/files/etc/network/interfaces/iface[last()]"

                self.__augeas.set(iface_path + '/family', 'inet')
                self.__augeas.set(iface_path + '/method', 'static')
                self.__pending['%s family' % iface] = 'inet'
                self.__pending['%s method' % iface] = 'static'
            else:
                return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.NETWORK_INTERFACE_DOWN, additional_message=str(iface))
        else:
            iface_path = iface_path_list[0]

        if address != None:
            self.__augeas.set(iface_path + '/address', address)
            self.__pending['%s address' % iface] = address
        if netmask != None:
            self.__augeas.set(iface_path + '/netmask', netmask)
            self.__pending['%s netmask' % iface] = netmask
        if gateway != None:
            self.__augeas.set(iface_path + '/gateway', gateway)
            self.__pending['%s gateway' % iface] = gateway
        if dns_search != None:
            self__augeas.set(iface_path + '/dns-search', dns_search)
            self.__pending['%s domain' % iface] = dns_search
        if dns_nameservers != None:
            self.__augeas.set(iface_path + '/dns-nameservers', dns_nameservers)
            self.__pending['%s nameserver(s)' % iface] = dns_nameservers
        if broadcast != None:
            self.__augeas.set(iface_path + '/broadcast', broadcast)
            self.__pending['%s broadcast' % iface] = broadcast
        if network != None:
            self.__augeas.set(iface_path + '/network', network)
            self.__pending['%s network' % iface] = network

        return AVConfigParserErrors.ALL_OK

    ### Related to /etc/hosts

    def get_hosts_config_all (self):
        """
        Return a dict with all entries in /etc/hosts, in the form {'entry': 'configuration parameters'}
        """
        return self.__hosts_entries

    def get_hosts_config (self, entry):
        """
        Return a dict with the /etc/hosts entry 'entry' as key, and its configuration attributes as values.
        """
        return {str(entry): self.__hosts_entries.get(str(entry))}

    def set_hosts_config (self, entry = "2", \
                          ipaddr = None, canonical = None, aliases = [], \
                          is_new = True):
        """
        Set the configuracion for a /etc/hosts entry.
        ToDo: be able to set new values.
        """
        hosts_entry_path = "/files/etc/hosts/%s" % entry
        hosts_entry_list = self.__augeas.match(hosts_entry_path)

        if hosts_entry_list == []:
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.HOSTS_ENTRY_NOT_FOUND, additional_message=str(entry))

        if ipaddr != None:
            self.__augeas.set(hosts_entry_path + '/ipaddr', ipaddr)
            self.__pending['host %s address' % entry] = ipaddr
        if canonical != None:
            self.__augeas.set(hosts_entry_path + '/canonical', canonical)
            self.__pending['host %s canonical name' % entry] = canonical
        if aliases != []:
            for counter, alias in enumerate(aliases, start = 1):
                self.__augeas.set(hosts_entry_path + '/alias[%d]' % counter, alias)
                self.__pending['host %s alias[%d]' % (entry, counter)] = alias

        return AVConfigParserErrors.ALL_OK

    #
    # Private methods
    #
    def __get_net_iface_config_all__ (self):
        """
        Return a dict with all the network interface names as keys, and their configuration attributes as values.
        """
        # Get all the configured and unconfigured interfaces
        configured_ifaces = self.__augeas.match("/files/etc/network/interfaces/iface[*]")
        all_ifaces = get_network_interfaces()

        # Build the response dictionary.
        response = {}
        for iface_path in configured_ifaces:
            name = self.__augeas.get(iface_path)
            address = self.__augeas.get("%s/address" % iface_path)
            netmask = self.__augeas.get("%s/netmask" % iface_path)
            gateway = self.__augeas.get("%s/gateway" % iface_path)
            dns_search = self.__augeas.get("%s/dns-search" % iface_path)
            dns_nameservers = self.__augeas.get("%s/dns-nameservers" % iface_path)
            broadcast = self.__augeas.get("%s/broadcast" % iface_path)
            network = self.__augeas.get("%s/network" % iface_path)
            response[name] = {'address': address if address != None else '',
                              'netmask': netmask if netmask != None else '',
                              'gateway': gateway if gateway != None else '',
                              'dns_search': dns_search if dns_search != None else '',
                              'dns_nameservers': dns_nameservers if dns_nameservers != None else '',
                              'broadcast': broadcast if broadcast != None else '',
                              'network': network if network != None else ''
            }

        for iface in all_ifaces:
            if iface.name not in response.keys():
                response[iface.name] = {'address': '', 'netmask': '', 'gateway': '', 'dns_search': '', 'dns_nameservers': '', 'broadcast': '', 'network': ''}

        return response

    def __get_hosts_config_all__ (self):
        """
        Return a dict with all the entries in /etc/hosts as keys, and their configuration attributes as values.
        """
        # Get all the configured and unconfigured interfaces
        configured_hosts = self.__augeas.match("/files/etc/hosts/*")

        # Build the response dictionary.
        response = {}
        for counter, entry_path in enumerate(configured_hosts, start = 1):
            ipaddr = self.__augeas.get("%s/ipaddr" % entry_path)
            canonical = self.__augeas.get("%s/canonical" % entry_path)
            if self.__augeas.match("%s/alias" % entry_path) != None:
                aliases = [self.__augeas.get(x) for x in self.__augeas.match("%s/alias" % entry_path)]
            else:
                aliases = []
            response[str(counter)] = {'ipaddr': ipaddr if ipaddr != None else '',
                                      'canonical': canonical if canonical != None else '',
                                      'aliases': aliases
            }

        return response

    def __reload_config__ (self):
        self.__net_ifaces = self.__get_net_iface_config_all__ ()
        self.__hosts_entries = self.__get_hosts_config_all__ ()
