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

import re

from output import log_debug
from netaddr import IPNetwork
from sysinfo import Sysinfo


class Wildcard:
    # Wildcards about alienvault configuration files. Values only.
    #   * '@dbhost@'
    #   * '@dbuser@'
    #   * '@dbpass@'
    #   * ...
    @staticmethod
    def av_config(string, encapsulate_str=False, escape=False):
        sysinfo = Sysinfo()
        new_string = string

        try:
            alienvault_config = sysinfo.get_alienvault_config()
            keys = re.findall(r'(@((?!and|or|not)[a-zA-Z_]+)@)', string)
            for delim_key, key in keys:
                new_key = alienvault_config.get(key, delim_key)

                if type(new_key) is list:
                    new_key = ','.join(new_key)

                if (new_key != delim_key) and encapsulate_str:
                    new_key = '"' + new_key + '"'

                if escape:
                    new_key = re.escape(new_key)

                new_string = new_string.replace(delim_key, new_key)
        except Exception, e:
            return string

        if len(keys) > 1 and encapsulate_str:
            new_string = new_string[:new_string.find('"') + 1] + \
                         new_string[new_string.find('"') + 1:new_string.rfind('"') - 1].replace('"', '') + \
                         new_string[new_string.rfind('"') - 1:]

        return new_string

    # Wildcards about hardware configuration, from a semicolon-separated list:
    #   * '@is_vm@'
    #   * '@cores@'
    #   * '@mem@'
    @staticmethod
    def hw_config(string):
        sysinfo = Sysinfo()
        translate = {'is_vm': 'A Virtual Machine is required',
                     'cores': 'Number of CPU cores required is not met',
                     'installed_mem': 'Memory size required is not met'}
        new_string = ''

        try:
            match = re.findall(r'^@([_a-zA-Z]*)@(.*)', string)[0]
            hardware_config = sysinfo.get_hardware_config()
            new_string = str(hardware_config[match[0]]) + match[1]
        except:
            return None, None

        return translate[match[0]], new_string

    # Wildcards for '@set@' operations, in the form of 'a' op 'b', from a semicolon-separated list:
    #   * '@issubset@' returns actually the subset of all the elements in 'a' that are not in 'b'
    #   * '@issuperset@' returns actually the subset of all the elements in 'b' that are not in 'a'
    #   * '@is@' returns returns actually the subset of all elements that are either in 'a' or 'b', but not both.
    #   * '@isdisjoint@' returns actually the subset of all elements that are in both sets.
    @staticmethod
    def set_operation(string):
        ops = {'@issubsetof@': ('Is subset of', '__sub__'),
               '@issupersetof@': ('Is superset of', '__rsub__'),
               '@isequalto@': ('Is equal to', 'symmetric_difference'),
               '@isdisjointto@': ('Is disjoint to', 'intersection')}
        pattern = r'^(%s)(.*?)$' % '|'.join(ops.iterkeys())
        new_string = ''

        try:
            match = re.findall(pattern, string)[0]
            op = ops[match[0]][0]
            new_string += '.' + ops[match[0]][1] + '(' + match[1] + ')'
        except:
            return None, None

        return op, new_string

    # Wildcards for ipaddr operations.
    @staticmethod
    def ipaddr_operation(string):
        ops = {'@in@': ('Is in', ' in '),
               '@notin@': ('Is not in', ' not in ')}
        pattern = r'(%s)((?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)(?:/(?:[0-9]{1,2}))?)' % '|'.join(
            ops.iterkeys())
        new_string = string

        try:
            matches = re.findall(pattern, new_string)
            for match in matches:
                new_string = re.sub(match[0], ops[match[0]][1], new_string)
                new_string = re.sub(match[1], repr(IPNetwork(match[1])), new_string)
        except:
            return new_string

        return new_string

    @staticmethod
    def appliance_exec(string):

        new_string = string
        profiles = {'vmware_sensor_profiles': ['alienvault-vmware-sensor-remote',
                                               'alienvault-vmware-sensor-standard-6x1gb',
                                               'alienvault-vmware-sensor-remote-lite'],
                    'vmware_aio_profiles': ['alienvault-vmware-aio-6x1gb',
                                            'alienvault-vmware-aio-6x1gb-lite'],
                    'vmware_logger_profiles': ['alienvault-vmware-logger-standard'],
                    'vmware_usm_standard_profiles': ['alienvault-vmware-usm-standard'],
                    'vmware_all_profiles': [],
                    'hw_sensor_profiles': ['alienvault-hw-sensor-remote',
                                           'alienvault-hw-sensor-standard-6x1gb',
                                           'alienvault-hw-sensor-standard-2x10gb',
                                           'alienvault-hw-sensor-enterprise-ids-6x1gb',
                                           'alienvault-hw-sensor-enterprise-ids-2x10gb'],
                    'hw_aio_profiles': ['alienvault-hw-aio-6x1gb',
                                        'alienvault-hw-aio-niap'],
                    'hw_logger_profiles': ['alienvault-hw-logger-standard',
                                           'alienvault-hw-logger-enterprise'],
                    'hw_usm_standard_profiles': ['alienvault-hw-usm-standard'],
                    'hw_usm_enterprise_profiles': ['alienvault-hw-usm-enterprise'],
                    'hw_usm_database_profiles': ['alienvault-hw-usm-database'],
                    'hw_aio_extended_profiles': ['alienvault-hw-aio-extended'],
                    'hw_all_profiles': [],
                    'ami_aio_profiles': ['alienvault-ami-aio-6x1gb',
                                         'alienvault-ami-aio-6x1gb-lite'],
                    'ami_sensor_profiles': ['alienvault-ami-sensor-standard-6x1gb',
                                            'alienvault-ami-sensor-remote',
                                            'alienvault-ami-sensor-remote-lite'],
                    'ami_usm_standard_profiles': ['alienvault-ami-usm-standard'],
                    'ami_logger_profiles': ['alienvault-ami-logger-standard'],
                    'ami_all_profiles': [],
                    'hyperv_aio_profiles': ['alienvault-hyperv-aio-6x1gb',
                                            'alienvault-hyperv-aio-6x1gb-lite'],
                    'hyperv_sensor_profiles': ['alienvault-hyperv-sensor-standard-6x1gb',
                                               'alienvault-hyperv-sensor-remote',
                                               'alienvault-hyperv-sensor-remote-lite'],
                    'hyperv_usm_standard_profiles': ['alienvault-hyperv-usm-standard'],
                    'hyperv_logger_profiles': ['alienvault-hyperv-logger-standard'],
                    'hyperv_all_profiles': [],
                    'all_profiles': []
                    }

        subkeys = ['logger',
                   'sensor',
                   'aio',
                   'usm_standard',
                   'usm_database',
                   'usm_enterprise',
                   'aio_extended']

        for platform in ['hw', 'vmware', 'ami', 'hyperv']:
            for subkey in subkeys:
                profiles.setdefault('all_%s_profiles' % subkey, [])
                if "%s_%s_profiles" % (platform, subkey) in profiles.keys():
                    profiles['%s_all_profiles' % platform] += profiles["%s_%s_profiles" % (platform, subkey)]
                    profiles['all_%s_profiles' % subkey] += profiles["%s_%s_profiles" % (platform, subkey)]

            profiles['all_profiles'] += profiles['%s_all_profiles' % platform]

        try:
            match = re.findall(r'^@([_a-zA-Z]*)@.*', string)[0]
            new_string = profiles[match]
        except Exception:
            return [new_string]

        return new_string
