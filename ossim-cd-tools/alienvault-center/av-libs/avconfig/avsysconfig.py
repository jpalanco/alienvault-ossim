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

import imp
import os
import re
import subprocess

from utils import is_ipv4
from netinterfaces import get_network_interfaces
from IPy import IP
import augeas
from avconfigparsererror import AVConfigParserErrors
from avsysconfigtriggerlaunch import AVSysConfigTriggerLaunch


class AVSysConfig(object):
    def __init__(self, system_ip=None, system_id=None, system_type=None):
        """
        Initialize this object with non system related data, like the OSSIM administration IP address.
        """
        self.__system_ip = system_ip if is_ipv4(system_ip) else None
        self.__system_id = system_id
        self.__system_type = system_type

        # augeas = imp.load_source('augeas', '/usr/share/alienvault/api_core/lib/python2.6/site-packages/augeas.py')
        self.__augeas_iface = augeas.Augeas(flags=augeas.Augeas.SAVE_BACKUP)
        self.__augeas_host = augeas.Augeas(flags=augeas.Augeas.SAVE_BACKUP)
        self.__augeas_vpn = augeas.Augeas(flags=augeas.Augeas.SAVE_BACKUP)

        # Load extra files into Augeas...
        self.__augeas_vpn.set('/augeas/load/Puppet/lens', 'Puppet.lns')
        try:
            self.__augeas_vpn.set('/augeas/load/Puppet/incl', '/etc/alienvault/network/vpn.conf')
        except ValueError:
            self.__augeas_vpn.set('/augeas/load/Puppet/incl[1]', '/etc/alienvault/network/vpn.conf')
        self.__augeas_vpn.load()

        self.__augeas_iface.set('/augeas/load/Puppet/lens', 'Puppet.lns')
        try:
            self.__augeas_iface.set('/augeas/load/Puppet/incl', '/etc/alienvault/network/interfaces.conf')
        except ValueError:
            self.__augeas_iface.set('/augeas/load/Puppet/incl[1]', '/etc/alienvault/network/interfaces.conf')
        self.__augeas_iface.load()

        # Get some interesting information from ossim_setup.conf
        with open('/etc/ossim/ossim_setup.conf', 'r') as o:
            ossim_setup_data = o.read()

        try:
            self.__os_interface = re.findall(r"^interface=(\S+)$", ossim_setup_data, re.MULTILINE)[0]
            self.__os_admin_ip = re.findall(r"^admin_ip=(\S+)$", ossim_setup_data, re.MULTILINE)[0]
        except:
            pass

        # Load the trigger object.
        self.__trigger_launch = AVSysConfigTriggerLaunch()

        self.__pending = {}

        # System data
        self.__hosts_entries = {}

        # AV data
        self.__net_ifaces = {}
        self.__avvpn_entries = {}

        # Initialize pure system data.
        self.__reload_config__()

    #
    # Public methods
    #
    def is_pending(self):
        """Are there pending changes?
        """
        return bool(self.__pending)

    def get_pending(self):
        """Get which changes are pending
        """
        return self.__pending

    def get_pending_str(self):
        """Like get_pending(), but in human format (no need for paths)
        """
        data = ''
        for key, (path, desc) in self.__pending.iteritems():
            data += '\n[%s]\n%s' % (key, desc if desc != 'TBD' else 'not set')
        return data

    def apply_changes(self):
        """Apply pending changes and reload configuration.
        """
        if not self.is_pending():
            return AVConfigParserErrors.ALL_OK

        try:
            self.__augeas_iface.save()
            self.__augeas_vpn.save()
        except IOError, msg:
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.CANNOT_SAVE_AVSYSCONFIG, str(msg))

        # Launch triggers, if needed.
        paths = [path for key, (path, desc) in self.__pending.iteritems()]
        (ret, message) = self.__trigger_launch.run(paths=paths)

        if not ret:
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.CANNOT_LAUNCH_TRIGGERS, str(message))

        self.__pending = {}
        self.__reload_config__()
        return AVConfigParserErrors.ALL_OK

    # Related to /etc/network/alienvault/interfaces.conf

    def get_net_iface_config_all(self, include_unconfigured=True, include_lo=False):
        """
        Return a dict with all network interface configurations,
        in the form {'iface name': 'configuration parameters'}
        """
        net_ifaces = self.__net_ifaces

        if not include_unconfigured:
            net_ifaces = dict([(x, y) for (x, y) in net_ifaces.items() if y['address'] != ''] and x != '#comment')
        if not include_lo:
            net_ifaces = dict([(x, y) for (x, y) in net_ifaces.items() if x != 'lo' and x != '#comment'])

        return net_ifaces

    def get_net_iface_config(self, iface):
        """
        Return a dict with the network interface name 'iface' as key, and its configuration attributes as values.
        """
        return {str(iface): self.__net_ifaces.get(str(iface)) or {}}

    def set_net_iface_config(self, iface, address=None,
                             netmask=None, gateway=None,
                             dns_search=None, dns_nameservers=None,
                             broadcast=None, network=None,
                             is_administration=None, is_log_management=None, is_monitor=None):
        """Set the network configuration for the interface 'iface'.
        """
        iface_path = "/files/etc/alienvault/network/interfaces.conf/{}".format(iface)
        is_new = self.__augeas_iface.match(iface_path) == []

        # Bare minimum to set up an interface (only for log_management interfaces).
        if is_log_management == 'yes':
            minimum = ['address', 'netmask']
            args = locals()
            bad_args = filter(lambda x: args[x] is None and x in minimum, args)
            if is_new and bad_args:
                return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.INCOMPLETE_NETWORK_ENTRY,
                                                          additional_message=', '.join(bad_args))

        # Set the interface role, regarding the following constraints:
        #   * Only one interface can be the administration interface.
        if is_administration == 'yes' and iface != self.__os_interface:
            admin_iface_path = "/files/etc/alienvault/network/interfaces.conf/%s" % self.__os_interface

            self.__augeas_iface.set(iface_path + '/address', self.__augeas_iface.get(admin_iface_path + '/address'))
            self.__augeas_iface.set(iface_path + '/netmask', self.__augeas_iface.get(admin_iface_path + '/netmask'))
            self.__augeas_iface.set(iface_path + '/network', self.__augeas_iface.get(admin_iface_path + '/network'))
            self.__augeas_iface.set(iface_path + '/broadcast', self.__augeas_iface.get(admin_iface_path + '/broadcast'))
            self.__augeas_iface.set(iface_path + '/gateway', self.__augeas_iface.get(admin_iface_path + '/gateway'))
            self.__augeas_iface.set(iface_path + '/dns-nameservers',
                                    self.__augeas_iface.get(admin_iface_path + '/dns-nameservers'))
            self.__augeas_iface.set(iface_path + '/dns-search',
                                    self.__augeas_iface.get(admin_iface_path + '/dns-search'))
            self.__augeas_iface.set(iface_path + '/administration', 'yes')
            self.__augeas_iface.set(iface_path + '/log_management',
                                    self.__augeas_iface.get(admin_iface_path + '/log_management'))
            self.__augeas_iface.set(iface_path + '/monitor', self.__augeas_iface.get(admin_iface_path + '/monitor'))
            self.__augeas_iface.set(iface_path + '/enabled', 'yes')

            self.__augeas_iface.set(admin_iface_path + '/address', 'TBD')
            self.__augeas_iface.set(admin_iface_path + '/netmask', 'TBD')
            self.__augeas_iface.set(admin_iface_path + '/network', 'TBD')
            self.__augeas_iface.set(admin_iface_path + '/broadcast', 'TBD')
            self.__augeas_iface.set(admin_iface_path + '/gateway', 'TBD')
            self.__augeas_iface.set(admin_iface_path + '/dns-nameservers', 'TBD')
            self.__augeas_iface.set(admin_iface_path + '/dns-search', 'TBD')
            self.__augeas_iface.set(admin_iface_path + '/log_management', 'no')
            self.__augeas_iface.set(admin_iface_path + '/monitor', 'no')
            self.__augeas_iface.set(admin_iface_path + '/administration', 'no')
            self.__augeas_iface.set(admin_iface_path + '/enabled', 'no')

            self.__os_interface = iface

        if is_log_management is not None:
            log_management_path = iface_path + '/log_management'
            self.__augeas_iface.set(log_management_path, is_log_management)
            if is_administration is None or is_administration is 'no':
                self.__pending['Network interface %s log management' % iface] = (log_management_path, is_log_management)

        if is_monitor is not None:
            monitor_path = iface_path + '/monitor'
            self.__augeas_iface.set(monitor_path, is_monitor)
            if is_administration is None or is_administration is 'no':
                self.__pending['Network interface %s monitor' % iface] = (monitor_path, is_monitor)

        # Remember: don't allow re-configuring the admin interface, or using the admin ip address
        if address is not None:
            if address == self.__os_admin_ip and iface != self.__os_interface:
                return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.CANNOT_OVERWRITE_ADMIN_IP, additional_message='')
            elif (address == '0.0.0.0' and \
                 (self.__augeas_iface.get(iface_path + '/log_management') in [None, 'TBD', 'no'] and \
                  self.__augeas_iface.get(iface_path + '/administration') in [None, 'TBD', 'no'])) or \
                  address != '0.0.0.0':
                # This will be setted as a monitoring interface, setting the IP address only if it does not have one.
                address_path = iface_path + '/address'
                self.__augeas_iface.set(address_path, address)
                self.__pending['Network interface %s address' % iface] = (address_path, address)

        if netmask is not None:
            netmask_path = iface_path + '/netmask'
            self.__augeas_iface.set(netmask_path, netmask)
            self.__pending['Network interface %s netmask' % iface] = (netmask_path, netmask)
        if gateway is not None:
            gateway_path = iface_path + '/gateway'
            self.__augeas_iface.set(gateway_path, gateway)
            self.__pending['Network interface %s gateway' % iface] = (gateway_path, gateway)
        if dns_search is not None:
            dns_search_path = iface_path + '/dns-search'
            self.__augeas_iface.set(dns_search_path, dns_search)
            self.__pending['Network interface %s domain' % iface] = (dns_search_path, dns_search)
        if dns_nameservers is not None:
            # DNS nameservers is a comma separated list
            # Blank space separated list in av network config file
            dns_nameservers_path = iface_path + '/dns-nameservers'
            self.__augeas_iface.set(dns_nameservers_path, dns_nameservers.replace(',', ' '))
            self.__pending['Network interface %s nameserver(s)' % iface] = (dns_nameservers_path, dns_nameservers)
        if broadcast is not None:
            broadcast_path = iface_path + '/broadcast'
            self.__augeas_iface.set(broadcast_path, broadcast)
        if network is not None:
            network_path = iface_path + '/network'
            self.__augeas_iface.set(network_path, network)

        # Well, I need to fix here the broadcast and network address
        local_ip = self.__augeas_iface.get(iface_path + '/address')
        local_netmask = self.__augeas_iface.get(iface_path + '/netmask')

        if local_ip not in (None, 'TBD') and local_netmask not in (None, 'TBD'):
            # Only set the broadcast / network in this case
            net_iface_ip_and_mask = IP(local_ip).make_net(local_netmask)
            net_iface_broadcast = str(net_iface_ip_and_mask.broadcast())
            net_iface_network = str(net_iface_ip_and_mask.net())
            if broadcast is None:
                net_iface_broadcast_path = iface_path + '/broadcast'
                self.__augeas_iface.set(net_iface_broadcast_path, net_iface_broadcast)
            if network is None:
                net_iface_network_path = iface_path + '/network'
                self.__augeas_iface.set(net_iface_network_path, net_iface_network)

        # Lastly, for now, all interfaces are enabled by default if they have a role.
        if self.__augeas_iface.get(iface_path + '/administration') in [None, 'TBD', 'no'] and \
                        self.__augeas_iface.get(iface_path + '/log_management') in [None, 'TBD', 'no'] and \
                        self.__augeas_iface.get(iface_path + '/monitor') in [None, 'TBD', 'no']:
            enabled = 'no'
        else:
            enabled = 'yes'

        enabled_path = iface_path + '/enabled'
        self.__augeas_iface.set(enabled_path, enabled)

        return AVConfigParserErrors.ALL_OK

    ### Related to /etc/hosts

    def get_hosts_config_all(self):
        """
        Return a dict with all entries in /etc/hosts, in the form {'entry': 'configuration parameters'}
        """
        return self.__hosts_entries

    def get_hosts_config(self, entry):
        """
        Return a dict with the /etc/hosts entry 'entry' as key, and its configuration attributes as values.
        """
        return {str(entry): self.__hosts_entries.get(str(entry)) or {}}

    def set_hosts_config(self, entry="2",
                         ipaddr=None, canonical=None, aliases=None):
        """
        Set the configuration for a /etc/hosts entry.
        ToDo: be able to set new values.
        """
        if aliases is None:
            aliases = []
        hosts_entry_path = "/files/etc/hosts/%s" % entry
        hosts_entry_list = self.__augeas_host.match(hosts_entry_path)

        if not hosts_entry_list:
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.HOSTS_ENTRY_NOT_FOUND,
                                                      additional_message=str(entry))

        if ipaddr is not None:
            ipaddr_path = hosts_entry_path + '/ipaddr'
            self.__augeas_host.set(ipaddr_path, ipaddr)
            self.__pending['Host %s address' % entry] = (ipaddr_path, ipaddr)
        if canonical is not None:
            canonical_path = hosts_entry_path + '/canonical'
            self.__augeas_host.set(canonical_path, canonical)
            self.__pending['Host %s canonical name' % entry] = (canonical_path, canonical)
        if aliases != []:
            for counter, alias in enumerate(aliases, start=1):
                alias_path = hosts_entry_path + '/alias[%d]' % counter
                self.__augeas_host.set(alias_path, alias)
                self.__pending['Host %s alias[%d]' % (entry, counter)] = (alias_path, alias)

        return AVConfigParserErrors.ALL_OK

    ### Related to /etc/alienvault/network/vpn.conf

    def get_avvpn_config_all(self):
        """
        Return a dict with all entries in /etc/alienvault/network/vpn.conf, in the form {iface: configuration parameters}
        """
        return self.__avvpn_entries

    def get_avvpn_config(self, iface='tun0'):
        """
        Return a dict with the /etc/alienvault/network/vpn.conf entry 'iface' as key, and its configuration attributes as values.
        """
        return {str(iface): self.__avvpn_entries.get(str(iface)) or {}}

    def set_avvpn_config(self, iface,
                         role=None, config_file=None,
                         network=None, netmask=None, port=None,
                         ca=None, cert=None, key=None, dh=None,
                         enabled=None):
        """
        Set the AlienVault VPN configuration under /etc/alienvault/network/vpn.conf
        """

        entry_path = '/files/etc/alienvault/network/vpn.conf/%s' % iface

        # Run a check, just in case someone is trying to set up a VPN without all the needed parameters.
        is_new = self.__augeas_vpn.match(entry_path) == []

        args = locals()
        bad_args = filter(lambda x: args[x] is None, args)
        if is_new and bad_args != []:
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.INCOMPLETE_AVVPN_ENTRY,
                                                      additional_message=', '.join(bad_args))

        # If we are setting a path, just check it.
        paths = [config_file, ca, cert, key, dh]
        bad_paths = filter(lambda x: not (x is None or os.path.isfile(x)), paths)
        if bad_paths:
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.INVALID_AVVPN_ENTRY_FIELD,
                                                      additional_message=', '.join(bad_paths))

        if role is not None:
            if role not in ['server', 'client']:
                return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.INVALID_AVVPN_ENTRY_FIELD,
                                                          additional_message=str(role))

            role_path = entry_path + '/role'
            self.__augeas_vpn.set(role_path, role)
            self.__pending['VPN interface %s role' % iface] = (role_path, role)

        if config_file is not None:
            config_file_path = entry_path + '/config_file'
            self.__augeas_vpn.set(config_file_path, config_file)
            self.__pending['VPN interface %s config_file' % iface] = (config_file_path, config_file)

        if network is not None:
            try:
                network_check = IP(network)
            except:
                return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.INVALID_AVVPN_ENTRY_FIELD,
                                                          additional_message=str(network))

            network_path = entry_path + '/network'
            self.__augeas_vpn.set(network_path, network)
            self.__pending['VPN interface %s network' % iface] = (network_path, network)

        if netmask is not None:
            try:
                netmask_check = IP(netmask)
            except:
                return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.INVALID_AVVPN_ENTRY_FIELD,
                                                          additional_message=str(netmask))

            netmask_path = entry_path + '/netmask'
            self.__augeas_vpn.set(netmask_path, netmask)
            self.__pending['VPN interface %s netmask' % iface] = (netmask_path, netmask)

        if port is not None:
            try:
                port_int = int(port)
                if port_int < 1 or port_int > 65535:
                    return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.INVALID_AVVPN_ENTRY_FIELD,
                                                              additional_message=str(port))
            except:
                return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.INVALID_AVVPN_ENTRY_FIELD,
                                                          additional_message=str(port))

            port_path = entry_path + '/port'
            self.__augeas_vpn.set(port_path, port)
            self.__pending['VPN interface %s port' % iface] = (port_path, port)

        if ca is not None:
            ca_path = entry_path + '/ca'
            self.__augeas_vpn.set(ca_path, ca)
            self.__pending['VPN interface %s ca' % iface] = (ca_path, ca)

        if cert is not None:
            cert_path = entry_path + '/cert'
            self.__augeas_vpn.set(cert_path, cert)
            self.__pending['VPN interface %s cert' % iface] = (cert_path, cert)

        if key is not None:
            key_path = entry_path + '/key'
            self.__augeas_vpn.set(key_path, key)
            self.__pending['VPN interface %s key' % iface] = (key_path, key)

        if dh is not None:
            dh_path = entry_path + '/dh'
            self.__augeas_vpn.set(dh_path, dh)
            self.__pending['VPN interface %s dh' % iface] = (dh_path, dh)

        if enabled is not None:
            if enabled not in ['yes', 'no']:
                return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.INVALID_AVVPN_ENTRY_FIELD,
                                                          additional_message=str(enabled))

            enabled_path = entry_path + '/enabled'
            self.__augeas_vpn.set(enabled_path, enabled)

            if 'no' == enabled:
                subprocess.call('echo "update system set vpn_ip=NULL;" | ossim-db', shell=True)

            self.__pending['VPN interface %s enabled' % iface] = (enabled_path, enabled)

        return AVConfigParserErrors.ALL_OK

    def is_net_iface_address_set(self, iface):
        """ Return whether a iface address is set
        """
        try:
            address = self.__augeas_iface.get("/files/etc/alienvault/network/interfaces.conf/%s/address" % iface)
        except Exception:
            address = 'TBD'
        return address != 'TBD'

    #
    # Private methods
    #

    def __reload_config__(self):
        self.__net_ifaces = self.__get_net_iface_config_all__()
        self.__hosts_entries = self.__get_hosts_config_all__()
        self.__avvpn_entries = self.__get_avvpn_config_all__()

    # Related to /etc/alienvault/network/interfaces.conf

    def __get_net_iface_config_all__(self):
        """
        Return a dict with all the network interface names as keys,
        and their configuration attributes as values.
        """
        # Get all the configured and unconfigured interfaces
        all_ifaces = get_network_interfaces()
        configured_ifaces = self.__augeas_iface.match("/files/etc/alienvault/network/interfaces.conf/*")

        # Build the response dictionary.
        response = {}
        for iface_path in configured_ifaces:
            name = str(os.path.basename(iface_path))
            address = self.__augeas_iface.get("%s/address" % iface_path)
            netmask = self.__augeas_iface.get("%s/netmask" % iface_path)
            gateway = self.__augeas_iface.get("%s/gateway" % iface_path)
            dns_search = self.__augeas_iface.get("%s/dns-search" % iface_path)
            dns_nameservers = self.__augeas_iface.get("%s/dns-nameservers" % iface_path)
            broadcast = self.__augeas_iface.get("%s/broadcast" % iface_path)
            network = self.__augeas_iface.get("%s/network" % iface_path)
            administration = self.__augeas_iface.get("%s/administration" % iface_path)
            log_management = self.__augeas_iface.get("%s/log_management" % iface_path)
            monitor = self.__augeas_iface.get("%s/monitor" % iface_path)
            enabled = self.__augeas_iface.get("%s/enabled" % iface_path)
            response[name] = {'address': address if address is not None else '',
                              'netmask': netmask if netmask is not None else '',
                              'gateway': gateway if gateway is not None else '',
                              'dns_search': dns_search if dns_search is not None else '',
                              'dns_nameservers': dns_nameservers if dns_nameservers is not None else '',
                              'broadcast': broadcast if broadcast is not None else '',
                              'network': network if network is not None else '',
                              'administration': administration if administration is not None else '',
                              'log_management': log_management if log_management is not None else '',
                              'monitor': monitor if monitor is not None else '',
                              'enabled': enabled if enabled is not None else '',
                              }

        for iface in all_ifaces:
            if iface.name not in response.keys():
                response[iface.name] = {'address': '', 'netmask': '',
                                        'gateway': '', 'dns_search': '',
                                        'dns_nameservers': '', 'broadcast': '',
                                        'network': ''}

        return response

    # Related to /etc/hosts

    def __get_hosts_config_all__(self):
        """
        Return a dict with all the entries in /etc/hosts as keys, and their configuration attributes as values.
        """
        # Get all the configured and unconfigured interfaces
        configured_hosts = self.__augeas_host.match("/files/etc/hosts/*")

        # Build the response dictionary.
        response = {}
        for counter, entry_path in enumerate(configured_hosts, start=1):
            ipaddr = self.__augeas_host.get("%s/ipaddr" % entry_path)
            canonical = self.__augeas_host.get("%s/canonical" % entry_path)
            if self.__augeas_host.match("%s/alias" % entry_path) is not None:
                aliases = [self.__augeas_host.get(x) for x in self.__augeas_host.match("%s/alias" % entry_path)]
            else:
                aliases = []
            response[str(counter)] = {'ipaddr': ipaddr if ipaddr is not None else '',
                                      'canonical': canonical if canonical is not None else '',
                                      'aliases': aliases
                                      }

        return response

    # Related to /etc/alienvault/network/vpn.conf

    def __get_avvpn_config_all__(self):
        """
        Return a dict with all the entries in /etc/alienvault/network/vpn.conf as keys,
        and their configuration attributes as values.
        """

        configured_avvpn = self.__augeas_vpn.match("/files/etc/alienvault/network/vpn.conf/*")

        # Build the response dictionary.
        response = {}
        for entry_path in configured_avvpn:
            iface = str(os.path.basename(entry_path))
            role = self.__augeas_vpn.get("%s/role" % entry_path)
            config_file = self.__augeas_vpn.get("%s/config_file" % entry_path)
            network = self.__augeas_vpn.get("%s/network" % entry_path)
            netmask = self.__augeas_vpn.get("%s/netmask" % entry_path)
            port = self.__augeas_vpn.get("%s/port" % entry_path)
            ca = self.__augeas_vpn.get("%s/ca" % entry_path)
            cert = self.__augeas_vpn.get("%s/cert" % entry_path)
            key = self.__augeas_vpn.get("%s/key" % entry_path)
            dh = self.__augeas_vpn.get("%s/dh" % entry_path)
            enabled = self.__augeas_vpn.get("%s/enabled" % entry_path)

            response[iface] = {'role': role if role in ['server', 'client'] else '',
                               'config_file': config_file if config_file is not None else '',
                               'network': network if network is not None else '',
                               'netmask': netmask if netmask is not None else '',
                               'port': port if port is not None else '',
                               'ca': ca if ca is not None else '',
                               'cert': cert if cert is not None else '',
                               'key': key if key is not None else '',
                               'dh': dh if dh is not None else '',
                               'enabled': enabled if enabled in ['yes', 'no'] else 'no'
                               }

        return response
