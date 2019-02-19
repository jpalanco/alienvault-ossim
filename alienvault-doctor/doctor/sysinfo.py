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

import glob
import sys
import socket
import fcntl
import struct
import array
import time
import math
import json

import platform
import re
import subprocess
import os
from datetime import timedelta

import MySQLdb
import MySQLdb.cursors
import psutil
from netaddr import IPNetwork, IPAddress

from output import *
from singleton import Singleton
import default


class Sysinfo (object):
    __metaclass__ = Singleton

    def __init__(self):
        self.__successful_config = {
            'basic': {'result': True, 'error': ''},
            'network': {'result': True, 'error': ''},
            'dbconf': {'result': True, 'error': ''},
            'server': {'result': True, 'error': ''},
            'sensor': {'result': True, 'error': ''}
        }

        self.__alienvault_config = {
            'version': '',
            'versiontype': '',
            'license': '',
            'licensed_assets': '',
            'admin_dns': [],
            'admin_gateway': '',
            'admin_ip': '',
            'admin_netmask': '',
            'admin_network': '',
            'hostname': '',
            'domain': '',
            'sw_profile': [],
            'hw_profile': '',
            'dbhost': '',
            'dbuser': '',
            'dbpass': '',
            'connected_servers': [],
            'connected_sensors': [],
            'connected_systems': [],
            'monitored_assets': '',
            'registered_users': [],
            'detectors': [],
            'monitors': [],
            'has_ha': False,
            'configured_network_interfaces': [],
            'last_updated': '',
        }

        self.__system_config = {
            'os': '',
            'node': '',
            'kernel': '',
            'arch': '',
        }

        self.__hardware_config = {
            'is_vm': False,
            'cpu': '',
            'cores': 0,
            'installed_mem': 0,
            'available_mem': 0,
            'running_network_interfaces': [],
            'vpn_ip': [],
        }

        self.__system_status = {
            'uptime': '',
            'load': '',
            'server_eps_median': 0,
        }

        self.__parse_alienvault_config__()
        self.__parse_alienvault_server_config__()
        self.__parse_alienvault_sensor_config__()
        self.__parse_system_config__()
        self.__parse_hardware_config__()
        self.__parse_system_status__()

    # Parse alienvault configuration files and stuff.
    def __parse_alienvault_config__(self):
        setup_file = open(default.ossim_setup_file, 'r').read()

        # Find software profile.
        line = setup_file[(setup_file.find('\nprofile=') + 9):]
        sw_profile = [x.strip() for x in line[:line.find('\n')].split(',')]
        self.__alienvault_config['sw_profile'] = filter(
            lambda x: x in ['Server', 'Framework', 'Database', 'Sensor'], sw_profile
        )

        if not self.__alienvault_config['sw_profile']:
            Output.error('There are no defined profile in ossim_setup.conf')
            sys.exit(default.error_codes['undef_software_profile'])

        cmd = ['dpkg', '-l']
        proc = subprocess.Popen(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        output, err = proc.communicate()

        # Find version type (Free, Trial, Pro) and license.
        if os.path.isfile(default.ossim_license_file):
            with open(default.ossim_license_file, 'r') as f:
                content = f.read()
                self.__alienvault_config['versiontype'] = 'TRIAL' if re.findall(
                    r'^expire\=9999-12-31$', content, re.MULTILINE) == [] else 'PRO'
                license = re.findall(r'^key\=(\S+)$', content, re.MULTILINE)
                self.__alienvault_config['license'] = license[0] if len(license) > 0 else 'None'
                if 'Server' in self.__alienvault_config['sw_profile']:
                    devices = re.findall(r'^devices\=(\S+)$', content, re.MULTILINE)
                    if len(devices) > 0:
                        self.__alienvault_config['licensed_assets'] = devices[0] if devices[0] != "0" else "UNLIMITED"
                    else:
                        self.__alienvault_config['licensed_assets'] = "UNLIMITED"
                else:
                    self.__alienvault_config['licensed_assets'] = "N/A"
        else:
            self.__alienvault_config['versiontype'] = 'FREE'
            self.__alienvault_config['licensed_assets'] = "N/A"

        # Find hardware profile.
        if self.__alienvault_config['versiontype'] != 'FREE':
            available_hw_packages = ['alienvault-ami-usm-standard',
                                     'alienvault-ami-aio-6x1gb',
                                     'alienvault-ami-aio-6x1gb-lite',
                                     'alienvault-ami-logger-standard',
                                     'alienvault-ami-sensor-standard-6x1gb',
                                     'alienvault-ami-sensor-remote',
                                     'alienvault-ami-sensor-remote-lite',
                                     'alienvault-hyperv-aio-6x1gb',
                                     'alienvault-hyperv-aio-6x1gb-lite',
                                     'alienvault-hyperv-sensor-remote',
                                     'alienvault-hyperv-sensor-remote-lite',
                                     'alienvault-hyperv-usm-standard',
                                     'alienvault-hyperv-logger-standard',
                                     'alienvault-hyperv-sensor-standard-6x1gb',
                                     'alienvault-vmware-usm-standard',
                                     'alienvault-vmware-aio-6x1gb',
                                     'alienvault-vmware-aio-6x1gb-lite',
                                     'alienvault-vmware-logger-standard',
                                     'alienvault-vmware-sensor-standard-6x1gb',
                                     'alienvault-vmware-sensor-remote',
                                     'alienvault-vmware-sensor-remote-lite',
                                     'alienvault-hw-usm-standard',
                                     'alienvault-hw-usm-enterprise',
                                     'alienvault-hw-usm-database',
                                     'alienvault-hw-logger-standard',
                                     'alienvault-hw-logger-enterprise',
                                     'alienvault-hw-aio-6x1gb',
                                     'alienvault-hw-aio-extended',
                                     'alienvault-hw-aio-niap',
                                     'alienvault-hw-sensor-standard-6x1gb',
                                     'alienvault-hw-sensor-standard-2x10gb',
                                     'alienvault-hw-sensor-remote',
                                     'alienvault-hw-sensor-enterprise',
                                     'alienvault-hw-sensor-enterprise-ids-2x10gb',
                                     'alienvault-hw-sensor-enterprise-ids-6x1gb']

            hw_profiles = list(set(re.findall('^ii\s+(%s)\s+' % '|'.join(available_hw_packages), output, re.MULTILINE)))
            if len(hw_profiles) != 1:
                Output.error('No hardware profile package or more than one installed, detection may be inaccurate')
            self.__alienvault_config['hw_profile'] = hw_profiles[0] if hw_profiles else 'UNKNOWN'
        else:
            self.__alienvault_config['hw_profile'] = "ossim-free"

        # Find the version.
        regexp = '^ii\s+(?:ossim-server|ossim-agent|ossim-framework|ossim-mysql)\s+(?:1|10):(?P<version>\S+)-\S+\s+'
        versions = list(set(re.findall(regexp, output, re.MULTILINE)))
        if len(versions) != 1:
            Output.error('Essential packages %s have different versions' % ', '.join(cmd[2:]))
        self.__alienvault_config['version'] = versions[0] if versions else ''

        # Find ip address, hostname and domain configured in ossim_setup.conf
        try:
            line = setup_file[(setup_file.index('admin_dns=') + 10):]
            self.__alienvault_config['admin_dns'] = line[:line.index('\n')].split(',')
            line = setup_file[(setup_file.index('admin_gateway=') + 14):]
            self.__alienvault_config['admin_gateway'] = line[:line.index('\n')]
            line = setup_file[(setup_file.index('admin_ip=') + 9):]
            self.__alienvault_config['admin_ip'] = line[:line.index('\n')]
            line = setup_file[(setup_file.index('admin_netmask=') + 14):]
            self.__alienvault_config['admin_netmask'] = line[:line.index('\n')]
            line = setup_file[(setup_file.index('hostname=') + 9):]
            self.__alienvault_config['hostname'] = line[:line.index('\n')]
            line = setup_file[(setup_file.index('domain=') + 7):]
            self.__alienvault_config['domain'] = line[:line.index('\n')]
        except ValueError:
            error_msg = 'Missing network configuration bits, check your ossim_setup.conf file'
            Output.error(error_msg)
            self.__alienvault_config['admin_dns'] = 'UNKNOWN (%s)' % error_msg
            self.__alienvault_config['admin_gateway'] = 'UNKNOWN (%s)' % error_msg
            self.__alienvault_config['admin_netmask'] = 'UNKNOWN (%s)' % error_msg
            self.__alienvault_config['hostname'] = 'UNKNOWN (%s)' % error_msg
            self.__alienvault_config['domain'] = 'UNKNOWN (%s)' % error_msg
            # sys.exit(default.error_codes['missing_network_config'])
            self.__successful_config['network'] = {'result': False,
                                                   'error': error_msg}

        try:
            self.__alienvault_config['admin_dns'] = map(lambda x: str(IPAddress(x)), self.__alienvault_config['admin_dns'])
            self.__alienvault_config['admin_gateway'] = str(IPAddress(self.__alienvault_config['admin_gateway']))
            self.__alienvault_config['admin_ip'] = str(IPAddress(self.__alienvault_config['admin_ip']))
            self.__alienvault_config['admin_netmask'] = str(IPAddress(self.__alienvault_config['admin_netmask']))
            self.__alienvault_config['admin_network'] = str(IPNetwork('%s/%s' % (self.__alienvault_config['admin_ip'], self.__alienvault_config['admin_netmask'])))
        except Exception, msg:
            error_msg = 'Invalid network configuration info, check your ossim_setup.conf file: %s' % str(msg)
            Output.error(error_msg)
            self.__alienvault_config['admin_dns'] = 'UNKNOWN (%s)' % error_msg
            self.__alienvault_config['admin_gateway'] = 'UNKNOWN (%s)' % error_msg
            self.__alienvault_config['admin_ip'] = 'UNKNOWN (%s)' % error_msg
            self.__alienvault_config['admin_netmask'] = 'UNKNOWN (%s)' % error_msg
            self.__alienvault_config['admin_network'] = 'UNKNOWN (%s)' % error_msg
            # sys.exit(default.error_codes['invalid_network_config'])
            self.__successful_config['network'] = {'result': False,
                                                   'error': error_msg}

        # Find MySQL properties.
        try:
            line = setup_file[(setup_file.index('\npass=') + 6):]
            self.__alienvault_config['dbpass'] = line[:line.index('\n')]
            line = setup_file[(setup_file.index('\nuser=') + 6):]
            self.__alienvault_config['dbuser'] = line[:line.index('\n')]
            line = setup_file[(setup_file.index('\ndb_ip=') + 7):]
            self.__alienvault_config['dbhost'] = line[:line.index('\n')]
        except ValueError:
            error_msg = 'Missing MySQL configuration field, check your ossim_setup.conf file'
            Output.error(error_msg)
            self.__alienvault_config['dbpass'] = 'UNKNOWN (%s)' % error_msg
            self.__alienvault_config['dbuser'] = 'UNKNOWN (%s)' % error_msg
            self.__alienvault_config['dbhost'] = 'UNKNOWN (%s)' % error_msg
            # sys.exit(default.error_codes['missing_mysql_config'])
            self.__successful_config['dbconf'] = {'result': False,
                                                  'error': error_msg}

        # HA configuration.
        self.__alienvault_config['has_ha'] = (re.findall(r'^ha_heartbeat_start\=no$', setup_file, re.MULTILINE) == [])

        # Configured network interfaces.
        configured_interfaces = re.findall(r'^(?:interface\=|interfaces\=)(.*)$', setup_file, re.MULTILINE)
        self.__alienvault_config['configured_network_interfaces'] = list(reduce(lambda x, y: x | y,
                                                                                map(
                                                                                    lambda x: set(re.sub(r'\s+', '', x).split(',')),
                                                                                    configured_interfaces))) + ['lo']

        # Last updated
        update_files = filter(os.path.isfile, glob.glob("/var/log/alienvault/update/*"))
        update_files.sort(key=lambda x: os.path.getmtime(x), reverse=True)
        for update_file in update_files:
            with open(update_file, 'r') as f:
                if not re.findall('code (?!0)', f.read(), re.MULTILINE):
                    self.__alienvault_config['last_updated'] = os.path.getmtime(update_file)
                    break

    def __parse_alienvault_sensor_config__(self):
        # Sensor configuration.
        if 'Sensor' in self.__alienvault_config['sw_profile']:
            try:
                setup_file = open(default.ossim_setup_file, 'r').read()
                line = setup_file[(setup_file.index('\ndetectors=') + 11):]
                self.__alienvault_config['detectors'] = line[:line.index('\n')].split(',')
                line = setup_file[(setup_file.index('\nmonitors=') + 10):]
                self.__alienvault_config['monitors'] = line[:line.index('\n')].split(',')
            except ValueError:
                error_msg = 'Missing Sensor configuration field, check your ossim_setup.conf file'
                Output.error(error_msg)
                self.__alienvault_config['detectors'] = ['UNKNOWN (%s)' % error_msg]
                self.__alienvault_config['monitors'] = ['UNKNOWN (%s)' % error_msg]
                # sys.exit(default.error_codes['missing_sensor_config'])
                self.__successful_config['sensor'] = {'result': False,
                                                      'error': error_msg}

    def __parse_alienvault_server_config__(self):
        # Server configuration.
        if 'Server' in self.__alienvault_config['sw_profile']:
            try:
                conn = MySQLdb.connect(host=self.__alienvault_config['dbhost'], user=self.__alienvault_config['dbuser'], passwd=self.__alienvault_config['dbpass'], db='alienvault')
                conn.autocommit(True)
            except Exception, msg:
                error_msg = "Cannot connect to database: %s" % str(msg)
                Output.error(error_msg)
                self.__alienvault_config['connected_servers'] = ['UNKNOWN (%s)' % error_msg]
                self.__alienvault_config['connected_sensors'] = ['UNKNOWN (%s)' % error_msg]
                self.__alienvault_config['connected_systems'] = ['UNKNOWN (%s)' % error_msg]
                self.__alienvault_config['monitored_assets'] = 'UNKNOWN (%s)' % error_msg
                self.__alienvault_config['registered_users'] = ['UNKNOWN (%s)' % error_msg]
                # sys.exit(default.error_codes['cannot_connect_db'])
                self.__successful_config['server'] = {'result': False,
                                                      'error': error_msg}

            try:
                cursor = conn.cursor()
                cursor.execute("select inet6_ntoa(IFNULL(vpn_ip, admin_ip)) as remote_server from server_forward_role inner join system on server_src_id = server_id where server_dst_id = (select unhex(replace(value, '-', '')) from config where conf = 'server_id');")
                self.__alienvault_config['connected_servers'] = list(set(x for x, in cursor.fetchall()))

                cursor.execute("select inet6_ntoa(IFNULL(vpn_ip, admin_ip)) as remote_sensor from system inner join sensor on system.sensor_id = sensor.id inner join sensor_properties on sensor.id = sensor_properties.sensor_id where sensor_properties.version is not NULL;")
                self.__alienvault_config['connected_sensors'] = list(set(x for x, in cursor.fetchall()))
                self.__alienvault_config['connected_systems'] = self.__alienvault_config['connected_servers'] + self.__alienvault_config['connected_sensors']

                cursor.execute("select count(id) from host;")
                assets, = cursor.fetchone()
                self.__alienvault_config['monitored_assets'] = int(assets)

                cursor.execute("select count(login) from users;")
                users, = cursor.fetchone()
                self.__alienvault_config['registered_users'] = int(users)
            except:
                pass

    # Parse system configuration and stuff.
    def __parse_system_config__(self):
        (self.__system_config['os'],
         self.__system_config['node'],
         self.__system_config['kernel'],
         _,
         self.__system_config['arch'],
         _) = platform.uname()

    # Parse some hw related configuration data.
    def __parse_hardware_config__(self):
        with open('/proc/cpuinfo', 'r') as f:
            cpuinfo = f.read()
            self.__hardware_config['is_vm'] = (re.findall(r'hypervisor', cpuinfo) != [])

        # self.__hardware_config['cpu'] = platform.processor() or 'Unknown'

        cpuinfo = self.cpuinfo()
        self.__hardware_config['cpu'] = "%s Family %s Model %s Stepping %s" % (cpuinfo['proc0']['model name'],
                                                                               cpuinfo['proc0']['cpu family'],
                                                                               cpuinfo['proc0']['model'],
                                                                               cpuinfo['proc0']['stepping'])
        self.__hardware_config['cores'] = psutil.NUM_CPUS
        self.__hardware_config['available_mem'] = round(psutil.TOTAL_PHYMEM / 1073741824.0, 1)

        # Compute installed memory from dmidecode output
        dmidecode = subprocess.Popen(['dmidecode', '--type', '17'], stdout=subprocess.PIPE)
        dmidecode_output = dmidecode.communicate()[0]
        counter = 0
        for line in dmidecode_output.splitlines():
            if 'Size:' in line:
                size_data = line.lstrip().split()
                try:
                    size = int(size_data[1])
                    if size_data[2] == 'GB':
                        size = size * 1000
                    counter = counter + size
                except ValueError:
                    continue
        self.__hardware_config['installed_mem'] = round(counter/1000, 1)

        # Running network interfaces.
        running_interfaces = self.__get_running_network_interfaces__()
        self.__hardware_config['running_network_interfaces'] = [iface for (iface, addr) in running_interfaces]

        # Find VPN ip, if available.
        self.__hardware_config['vpn_ip'] = [addr for (iface, addr) in running_interfaces if iface.startswith('tun')]

    # Parse some system status variables.
    def __parse_system_status__(self):

        # Get uptime.
        with open('/proc/uptime', 'r') as f:
            uptime_secs = f.read().split()[0]
        uptime_td = timedelta(0, float(uptime_secs))
        self.__system_status['uptime'] = '%d day(s), %02d:%02d' % (uptime_td.days, uptime_td.seconds//3600, (uptime_td.seconds//60) % 60)

        # Get load average.
        try:
            loadavg = os.getloadavg()
            self.__system_status['load'] = ', '.join(map(lambda x: "%.2f" % x, loadavg))
        except OSError:
            self.__system_status['load'] = 'Unknown'

        # Get the server EPS median for the past week.
        eps_log_file = '/var/alienvault/server/stats/eps.log'
        if 'Server' in self.__alienvault_config['sw_profile'] and os.path.isfile(eps_log_file):
            with open(eps_log_file, 'r') as f:
                try:
                    eps_log_lst = json.loads(f.read())
                except:
                    eps_log_lst = []
            try:
                eps_log_lst = filter(lambda x: type(x) == int, eps_log_lst)
            except:
                pass
            else:
                if eps_log_lst:
                    avg = sum(eps_log_lst) // len(eps_log_lst)
                    sn = int(math.sqrt(sum(map(lambda x: math.pow((x - avg), 2), eps_log_lst)) // len(eps_log_lst)))
                    eps_log_lst_filtered = filter(lambda x: math.fabs(avg - sn) <= x <= math.fabs(avg + sn), eps_log_lst)
                    eps_log_lst_filtered.sort()
                    eps_log_lst_filtered_len = len(eps_log_lst_filtered)
                    middle = eps_log_lst_filtered_len // 2
                    if eps_log_lst_filtered:
                        if eps_log_lst_filtered_len == 1:
                            self.__system_status['server_eps_median'] = eps_log_lst_filtered[0]
                        elif eps_log_lst_filtered_len % 2:
                            self.__system_status['server_eps_median'] = (eps_log_lst_filtered[middle] + eps_log_lst_filtered[middle + 1]) // 2
                        else:
                            self.__system_status['server_eps_median'] = eps_log_lst_filtered[middle]
                    else:
                            self.__system_status['server_eps_median'] = 0

    # Get up & running network interfaces.
    # Credits to:
    # http://code.activestate.com/recipes/439093-get-names-of-all-up-network-interfaces-linux-only/#c7
    @staticmethod
    def __get_running_network_interfaces__():
        is_64bits = sys.maxsize > 2**32
        struct_size = 40 if is_64bits else 32
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        max_possible = 8  # initial value
        while True:
            bytes = max_possible * struct_size
            names = array.array('B', '\0' * bytes)
            outbytes = struct.unpack('iL', fcntl.ioctl(
                s.fileno(),
                0x8912,  # SIOCGIFCONF
                struct.pack('iL', bytes, names.buffer_info()[0])
            ))[0]
            if outbytes == bytes:
                max_possible *= 2
            else:
                break
        namestr = names.tostring()
        return [(namestr[i:i+16].split('\0', 1)[0],
                 socket.inet_ntoa(namestr[i+20:i+24]))
                for i in range(0, outbytes, struct_size)]

    def get_alienvault_config(self):
        return self.__alienvault_config

    def get_successful_config(self):
        return self.__successful_config

    def get_system_config(self):
        return self.__system_config

    def get_hardware_config(self):
        return self.__hardware_config

    def get_system_status(self):
        return self.__system_status

    def get_value(self, string):
        if string in self.__alienvault_config.keys():
            return self.__alienvault_config[string]

        if string in self.__system_config.keys():
            return self.__system_config[string]

        if string in self.__hardware_config.keys():
            return self.__hardware_config[string]

        if string in self.__system_status.keys():
            return self.__system_status[string]

        Output.warning('Variable "%s" does not exist as a system information value' % str(string))
        return ''

    def show_platform_info(self, extended=False):
        platform_info = {
            'AlienVault version': self.__alienvault_config['version'] + '-' + self.__alienvault_config['versiontype'],
            'License': self.__alienvault_config['license'],
            'Licensed Assets': self.__alienvault_config['licensed_assets'],
            'Software profile': ', '.join(self.__alienvault_config['sw_profile']),
            'Hardware profile': self.__alienvault_config['hw_profile'],
            'Last updated': time.strftime("%a %b %d %H:%M:%S %Y %Z", time.localtime(self.__alienvault_config['last_updated'])) if self.__alienvault_config['last_updated'] else "Freshly installed",
            'AV Doctor execution time': time.strftime("%a %b %d %H:%M:%S %Y %Z", time.localtime())
        }

        platform_info_extended = {
            'Operating system': self.__system_config['os'],
            'Hostname': self.__system_config['node'],
            'Admin IP address': self.__alienvault_config['admin_ip'],
            'VPN IP address(es)': ','.join(self.__hardware_config['vpn_ip']) if self.__hardware_config['vpn_ip'] else 'None',
            'Kernel version': self.__system_config['kernel'],
            'Architecture': self.__system_config['arch'],
            'Appliance type': 'virtual' if self.__hardware_config['is_vm'] else 'physical',
            'CPU type': self.__hardware_config['cpu'],
            'Number of cores': str(self.__hardware_config['cores']),
            'Installed memory': str(self.__hardware_config['installed_mem']) + 'GB',
            'Available memory': str(self.__hardware_config['available_mem']) + 'GB',
            'Configured network interfaces': ', '.join(self.__alienvault_config['configured_network_interfaces']),
            'Running network interfaces': ', '.join(self.__hardware_config['running_network_interfaces']),
            'Uptime': self.__system_status['uptime'],
            'Load': self.__system_status['load'],
        }

        platform_info_server = {
            'Connected servers': str(len(self.__alienvault_config['connected_servers'])),
            'Sensors': str(len(self.__alienvault_config['connected_sensors'])),
            'Monitored assets': str(self.__alienvault_config['monitored_assets']),
            'Registered users': str(self.__alienvault_config['registered_users']),
            'Server EPS weekly median': str(self.__system_status['server_eps_median']),
        }

        platform_info_sensor = {
            'Sensor detectors': ','.join(self.__alienvault_config['detectors']),
            'Sensor monitors': ','.join(self.__alienvault_config['monitors']),
        }

        if extended:
            platform_info = dict(platform_info, **platform_info_extended)

            if 'Server' in self.__alienvault_config['sw_profile']:
                platform_info = dict(platform_info, **platform_info_server)

            if 'Sensor' in self.__alienvault_config['sw_profile']:
                platform_info = dict(platform_info, **platform_info_sensor)

        first_params_displayed = ['Admin IP address', 'Hostname', 'AlienVault version', 'License',
                                  'Licensed Assets', 'Software profile', 'Hardware profile', 'Last updated',
                                  'CPU type', 'Number of cores', 'Kernel version', 'Installed memory',
                                  'Available memory', 'Operating system', 'Architecture', 'Appliance type',
                                  'Uptime', 'Load']

        for param in first_params_displayed:
            if not extended and param in platform_info_extended.keys():
                continue
            rjustify = 80 - len(param)
            Output.emphasized('     %s: %s' % (param, platform_info[param].rjust(rjustify, ' ')), [platform_info[param]])

        for (field, value) in platform_info.iteritems():
            if field in first_params_displayed:
                continue
            rjustify = 80 - len(field)
            Output.emphasized('     %s: %s' % (field, value.rjust(rjustify, ' ')), [value])

        return platform_info

    def cpuinfo(self):
        """Return the information in /proc/cpuinfo
        as a dictionary in the following format:
        cpu_info['proc0']={...}
        cpu_info['proc1']={...}
        """

        cpuinfo = {}
        procinfo = {}

        nprocs = 0
        with open('/proc/cpuinfo') as f:
            for line in f:
                if not line.strip():
                    # end of one processor
                    cpuinfo['proc%s' % nprocs] = procinfo
                    nprocs = nprocs+1
                    # Reset
                    procinfo = {}
                else:
                    if len(line.split(':')) == 2:
                        procinfo[line.split(':')[0].strip()] = line.split(':')[1].strip()
                    else:
                        procinfo[line.split(':')[0].strip()] = ''

        return cpuinfo
