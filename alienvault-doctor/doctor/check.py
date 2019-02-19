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

import subprocess
import re
import hashlib

from os import path

from netaddr import IPNetwork

import default
from output import Output, log_debug
from wildcard import Wildcard
from error import CheckError


class Check:
    '''
    Check class.
    Defines a checkpoint for a plugin.
    '''
    def __init__(self, plugin, section):

        # 'check' properties.
        self.__name = ''
        self.__type = ''
        self.__pattern = ''
        self.__category = ''
        self.__description = ''
        self.__summary_passed = ''
        self.__summary_failed = ''
        self.__remediation = ''
        self.__plugin = None

        # 'file' type checks only.
        self.__checksums = []

        # 'file' and 'command' checks.
        self.__regex = None

        # 'db' type checks.
        self.__query = ''
        self.__pivot = False

        self.__introduced = ''
        self.__output = ''
        self.__formatted_output = ''
        self.__appliance_type = []
        self.__fail_if_empty = True
        self.__fail_if_empty_output = ''
        self.__fail_only_if_all_failed = False
        self.__split_by_comma = False
        self.__ha_dependant = False
        self.__severity = 'Warning'
        self.__conditions = {'basic': [], 'set': []}
        self.__actions = []
        self.__aux_data = {}
        self.__strike_zone = False
        self.__version_type = ''

        config_file = plugin.get_config_file()

        self.__name = section
        self.__plugin = plugin

        # Parse section options.
        # Different sections or check 'types' are mutually exclusive.
        items = config_file.items(section)

        try:
            # Retrieve first the formatted_output field
            for (name, value) in items:
                if name == 'formatted_output':
                    self.__formatted_output = value.replace("{nl}", "\n")
                    items.remove((name, value))
                    break

            # Now the rest
            for (name, value) in items:
                if name == 'checksum':
                    self.__type = name
                    self.__checksums = [tuple(x.split(':')) for x in value.split(';')]
                elif name == 'pattern':
                    self.__type = name
                    self.__pattern = str(value)
                    value = Wildcard.av_config(value, escape=True)
                    self.__regex = re.compile(value, re.MULTILINE)
                elif name == 'query':
                    self.__type = name
                    if value.startswith("@pivot@:"):
                        self.__query = value[8:]
                        self.__pivot = True
                    else:
                        self.__query = value
                    self.__query = Wildcard.av_config(self.__query, escape=True)
                elif name == 'hardware':
                    self.__type = name
                    self.__hw_list = value
                elif name == 'category':
                    self.__category = value
                elif name == 'fail_if_empty':
                    if value in ['True', 'False']:
                        self.__fail_if_empty = eval(value)
                elif name == 'fail_if_empty_output':
                    self.__fail_if_empty_output = value
                elif name == 'fail_only_if_all_failed':
                    if value in ['True', 'False']:
                        self.__fail_only_if_all_failed = eval(value)
                elif name == 'split_by_comma':
                    if value in ['True', 'False']:
                        self.__split_by_comma = eval(value)
                elif name == 'ha_dependant':
                    if value in ['True', 'False']:
                        self.__ha_dependant = eval(value)
                elif name == 'version_type':
                    self.__version_type = value
                elif name == 'severity':
                    if value in default.severity:
                        self.__severity = value
                elif name == 'min_doctor_version':
                    self.__min_doctor_version = value
                elif name == 'appliance_type':
                    for x in value.split(','):
                        self.__appliance_type += Wildcard.appliance_exec(x.strip())
                elif name == 'conditions':
                    self.__init_conditions__(value)
                elif name == 'actions':
                    self.__init_actions__(value)
                elif name == 'description':
                    self.__description = value
                elif name == 'summary_passed':
                    self.__summary_passed = value
                elif name == 'summary_failed':
                    self.__summary_failed = value
                elif name == 'remediation':
                    self.__remediation = value
                elif name == 'affects_strike_zone':
                    if value in ['True', 'False']:
                        self.__strike_zone = eval(value)
                else:
                    Output.warning('Unknown field in check "%s": %s' % (self.__name, name))
        except CheckError:
            raise
        except Exception, msg:
            Output.error('Cannot parse check "%s" in plugin "%s": %s' % (self.__name, self.__plugin.get_name(), msg))
            raise

    def __init_conditions__(self, value):
        # Check first if there are @set@ and other conditions in the same rule.
        # This is not allowed because standalone data type checks rely on order,
        # while @set tries to match with every field of the resulting regex/db query
        # regardless the order.
        if ('@set@' in value) and \
           ('@int@' in value or '@float@' in value or '@string@' in value or '@char@' in value or '@ipaddr@' in value):
            raise CheckError('Forbidden "@set@" and any other datatype combination in rule "%s" for plugin "%s"' % (self.__name, self.__plugin.get_name()), self.__name)

        conditions = filter(bool, value.split(';'))
        for condition in conditions:
            matches = re.findall(r'^(@[a-zA-Z_]+@)(?:\:(.*))?$', condition)
            if not matches:
                raise CheckError('Condition "%s" for check "%s" in plugin "%s" is invalid' % (condition, self.__name, self.__plugin.get_name()), self.__name)
            cond_type, cond_str = matches[0]

            # 'Basic' type conditions
            if cond_type in ['@string@', '@char@', '@int@', '@float@', '@info@', '@ipaddr@']:
                # Translate first, append later.
                if cond_type in ['@ipaddr@']:
                    # Do not encapsulate in quotes, as this is an object comparison.
                    cond_str = Wildcard.av_config(cond_str, encapsulate_str=False)
                    cond_str = Wildcard.ipaddr_operation(cond_str)
                else:
                    key = re.findall(r'.*(@[a-zA-Z_]+@).*', cond_str)
                    cond_str = Wildcard.av_config(cond_str, encapsulate_str=True)
                    if key:
                        self.__aux_data[key[0]] = Wildcard.av_config(key[0], encapsulate_str=False)
                        self.__formatted_output = self.__formatted_output.replace(key[0],
                                                                                  self.__aux_data[key[0]])

                self.__conditions['basic'].append((cond_type, cond_str.rsplit('@') if cond_str != None and cond_str != '' else None))

            # 'Set' type conditions
            elif cond_type == '@set@':
                matches = re.findall(r'^(@[a-zA-Z_]+@)(\S+)', cond_str)
                if not matches:
                    raise CheckError('Set condition "%s" for check "%s" in plugin "%s" is invalid' % (condition, self.__name, self.__plugin.get_name()), self.__name)

                cond_op, cond_set = matches[0]

                key = re.findall(r'.*(@[a-zA-Z_]+@).*', cond_set)
                cond_set = Wildcard.av_config(cond_set)
                if key:
                    self.__aux_data[key[0]] = Wildcard.av_config(key[0], encapsulate_str=False)
                    self.__formatted_output = self.__formatted_output.replace(key[0],
                                                                              self.__aux_data[key[0]])

                if path.isfile(cond_set):
                    # For sets defined in files.
                    desc = open(cond_set, 'r')
                    items = desc.read().splitlines()
                else:
                    items = cond_set.split(',')

                content = set()
                items = filter(None, items)
                for item in items:
                    splitted_item = item.split('|')
                    if len(splitted_item) > 1:
                        content.add(tuple(splitted_item))
                    else:
                        content.add(item)

                self.__conditions['set'].append(cond_op + str(content))

            else:
                raise CheckError('Type "%s" not recognized for check "%s" in plugin "%s"' % (cond_type, self.__name, self.__plugin.get_name()), self.__name)

    def __init_actions__(self, value):
        self.__actions = [tuple(x.split(':')) for x in value.split(';')]

    # Getter methods.
    def get_name(self):
        return self.__name

    def get_type(self):
        return self.__type

    def get_category(self):
        return self.__category

    def get_severity(self):
        return self.__severity

    def get_description(self):
        return self.__description

    def get_summary_passed(self):
        return self.__summary_passed

    def get_summary_failed(self):
        return self.__summary_failed

    def get_remediation(self):
        return self.__remediation

    def get_pattern(self):
        return self.__pattern

    def get_query(self):
        return self.__query

    def get_output(self):
        return self.__output

    def get_strike_zone(self):
        return self.__strike_zone

    def get_ha_dependant(self):
        return self.__ha_dependant

    # Test if the severity match with the check ones.
    def check_severity(self, severities):
        # Treat the empty list as 'all'
        if severities == []:
            return True

        # Search for the 'all' wildcard.
        if 'all' in severities:
            return True

        if self.__severity in severities:
            return True

        return False

    def check_appliance_type(self, hw_profile, appliance_types, ignore_dummy_platform):
        # Treat the empty list as 'current'

        if ignore_dummy_platform:
            return True
        else:
            if not appliance_types:
                if hw_profile.lower() in self.__appliance_type:
                    return True

            if 'current' in appliance_types:
                if hw_profile.lower() in self.__appliance_type:
                    return True

            if hw_profile.lower() in appliance_types and hw_profile.lower() in self.__appliance_type:
                return True
        return False

    def check_version(self, version):

        check_version = self.__min_doctor_version.split('-')
        aux_main_version = check_version[0].split('.')

        if len(aux_main_version) == 2:
            if len(aux_main_version[1]) == 1:
                aux_main_version[1] = "0%s" % str(aux_main_version[1])
            check_version[0] = '.'.join([aux_main_version[0],
                                         "%s" % str(aux_main_version[1]),
                                         "00"])
        elif len(aux_main_version) == 3:
            second_p, third_p = aux_main_version[1:3]
            if len(second_p) == 1:
                second_p = "0%s" % str(second_p)
            if len(third_p) == 1:
                third_p = "0%s" % str(third_p)
            check_version[0] = '.'.join([aux_main_version[0],
                                         "%s" % second_p,
                                         "%s" % third_p])

        main_version = int(check_version[0].replace('.', ''))
        aux_version = int(check_version[1].replace('.', '')) if len(check_version) > 1 else 0

        appliance_version = version.split('-')
        aux_main_version = appliance_version[0].split('.')
        if len(aux_main_version) == 2:
            if len(aux_main_version[1]) == 1:
                aux_main_version[1] = "0%s" % str(aux_main_version[1])
            appliance_version[0] = '.'.join([aux_main_version[0],
                                             "%s" % str(aux_main_version[1]),
                                             "00"])
        elif len(aux_main_version) == 3:
            second_p, third_p = aux_main_version[1:3]
            if len(second_p) == 1:
                second_p = "0%s" % str(second_p)
            if len(third_p) == 1:
                third_p = "0%s" % str(third_p)
            appliance_version[0] = '.'.join([aux_main_version[0],
                                             "%s" % second_p,
                                             "%s" % third_p])
        main_appliance_version = int(appliance_version[0].replace('.', ''))
        aux_appliance_version = int(appliance_version[1].replace('.', '')) if len(appliance_version) > 1 else 0

        if main_version > main_appliance_version:
            return False
        elif main_version == main_appliance_version:
            if aux_version > aux_appliance_version:
                return False
        else:
            return True

        return True

    # Checks for versiontype match
    def check_version_type(self):

        if self.__version_type:
            if self.__plugin.get_alienvault_config()['versiontype'] in self.__version_type.split(','):
                return True
            else:
                return False
        else:
            return True

    # Run the check logic and return a boolean.
    def run(self):
        if self.__type == 'checksum':
            return self.__run_checksum__()
        elif self.__type == 'pattern':
            return self.__run_pattern__()
        elif self.__type == 'query':
            return self.__run_query__()
        elif self.__type == 'hardware':
            return self.__run_hw__()
        else:
            fo = 'Unknown check type %s' % self.__name
            return False, 'Unknown check type "%s"' % self.__name, fo

    # Run a checksum against a file.
    def __run_checksum__(self):
        (outcome, description, fo) = (True, '', '')

        for (func, checksum) in self.__checksums:
            crypto_func = func[1:-1]
            h = hashlib.new(crypto_func)
            h.update(self.__plugin.get_data())
            if h.hexdigest() != checksum:
                description = '\n\tChecksum "%s" over "%s" failed' % (crypto_func, self.__plugin.get_filename())
                fo = '\n\tChecksum "%s" over "%s" failed' % (crypto_func, self.__plugin.get_filename())
                outcome = False
                return outcome, description, fo

        description = '\n\tAll checksums over "%s" succeeded' % self.__plugin.get_filename()
        fo = '\n\tAll checksums over %s succeeded' % self.__plugin.get_filename()
        outcome = True
        return outcome, description, fo

    # Check against a regular expression.
    def __run_pattern__(self):
        (outcome, description, fo) = (True, '', '')

        matches = self.__regex.findall(self.__plugin.get_data())
        self.__output = str(matches)

        if matches:
            (outcome, description, fo) = self.__check_conditions__(matches)
        else:
            description = '\n\tEmpty match set for pattern "%s" in check "%s"' % (self.__regex.pattern, self.__name)
            if self.__plugin.get_alienvault_config()['has_ha'] and self.__ha_dependant:
                description += '. This failure was caused by having HA enabled. Ignoring it'
            outcome = False if self.__fail_if_empty else True
            if self.__fail_if_empty_output:
                fo = self.__fail_if_empty_output
            else:
                fo = "\n\tEmpty match set for pattern %s in check %s" % (self.__regex.pattern, self.__name)

        return outcome, description, fo

    # Run a db query and parse the result.
    def __run_query__(self):
        (outcome, description, fo) = (True, '', '')

        results = self.__plugin.run_query(self.__query, result=True)
        self.__output = str(results)

        if len(results) > 0:
            if not self.__pivot:
                outcome, partial_description, partial_fo = self.__check_conditions__(results)
                description += partial_description
                fo += partial_fo
            else:
                # Pivot results.
                pivoted = [tuple(x) for x in zip(*results)]
                outcome, partial_description, partial_fo = self.__check_conditions__(pivoted)
                description += partial_description
                fo += partial_fo
        else:
            outcome = False if self.__fail_if_empty else True
            description = '\n\tEmpty result for query "%s"' % self.__query
            fo = '\n\tEmpty result for query %s' % self.__query

        return outcome, description, fo

    def __run_hw__(self):
        (outcome, description, fo) = (True, '', '')

        # Check hardware requirements.
        for hw_req in self.__hw_list.split(','):

            (hw_req_pretty, eval_str) = Wildcard.hw_config(hw_req)
            if not eval(eval_str):
                outcome = False
                description += "\n\t %s" % hw_req_pretty
                if hw_req == '@is_vm@':
                    fo += "%s; " % hw_req_pretty
                    break
                else:
                    aux = re.findall(r'(\S+)(>=|==|<=)(\S+)', eval_str)
                    # fo += "%s: Condition (available vs expected) --> %s;" % (hw_req_pretty, eval_str)
                    fo += "%s: Expected a value %s %s, but %s found; " % (hw_req_pretty,
                                                                          aux[0][1],
                                                                          aux[0][2],
                                                                          aux[0][0])

        return outcome, description, fo

    # Check conditions against a set of 'values'.
    def __check_conditions__(self, values):
        fo = ''
        if self.__split_by_comma:
            values = re.split(',', ','.join(values))

        if self.__conditions['set']:
            (outcome, description, fo) = self.__check_set_conditions__(values)

        elif self.__conditions['basic']:
            (outcome, description, fo) = self.__check_basic_conditions__(values)

        else:
            raise CheckError(msg='There are no conditions to use in check "%s"' % self.__name,
                             plugin=self.__name)

        return outcome, description, fo

    # Check regular 'basic' type conditions.
    def __check_basic_conditions__(self, values):
        (outcome, description, final_fo) = (True, '', '')
        # (outcome, description, fo) = (True, '', '')

        partial = []
        aux_final_fo = ''
        for value in values:
            data = ''
            info = ''
            regex = ''
            include_final_fo = False

            if type(value) == tuple:
                value = list(enumerate(value))
            else:
                value = [(0, value)]

            fo = self.__formatted_output

            for j, data in value:
                try:
                    datatype, condition = self.__conditions['basic'][j]
                except IndexError:
                    raise CheckError(
                        msg='One of the patterns in check "{0}" does not have the same size as the values set'.format(
                            self.__name),
                        plugin=self.__plugin.get_name()
                    )
                except ValueError:
                    datatype, = self.__conditions['basic'][j]
                    condition = None

                #
                # First, check data type retrieved.
                #
                if datatype == '@info@':
                    info = data if info == '' else info + '/' + data
                    fo = fo.replace("@info@", info, 1)
                    continue

                elif datatype == '@set@':
                    continue

                elif datatype == '@int@':
                    try:
                        if data != '':
                            data = int(data)
                        else:
                            data = 0
                        fo = fo.replace(datatype, str(data), 1)
                    except:
                        raise CheckError(
                            msg='Condition datatype is marked as "int" but "%s" is not an integer' % str(data),
                            plugin=self.__plugin.get_name())

                elif datatype == '@float@':
                    try:
                        if data != '':
                            data = float(data)
                        else:
                            data = 0.0
                        fo = fo.replace(datatype, str(data), 1)
                    except:
                        raise CheckError(
                            msg='Condition datatype is marked as "float" but "%s" is not a float' % str(data),
                            plugin=self.__plugin.get_name()
                        )

                elif datatype == '@char@' and not data.isalpha():
                    raise CheckError(
                        msg='Condition datatype is marked as "char" but "%s" is not a character' % str(data),
                        plugin=self.__plugin.get_name()
                    )

                elif datatype == '@string@':
                    try:
                        data = data.replace('\r', '\\r').replace('\n', '\\n').replace('"', '\\"').replace(
                            "'", "\\'").replace(r"\\", r"\\\\")
                        fo = fo.replace(datatype, data, 1)
                    except:
                        raise CheckError(msg='Cannot escape quotes in condition "%s"' % str(data),
                                         plugin=self.__plugin.get_name())

                elif datatype == '@ipaddr@':
                    try:
                        fo.replace(datatype, data, 1)
                        data = repr(IPNetwork(data))
                    except:
                        raise CheckError(
                            msg='Condition datatype is marked as "ipaddr" but "%s" is not an IP Address' % str(data),
                            plugin=self.__plugin.get_name()
                        )

                if self.__severity == 'Debug':
                    aux_final_fo += "\n\t" + fo
                    continue
                #
                # Check the second part of the operation (e.g. the condition)
                #

                # Sometimes, conditions are only for checking the match type, so they have
                # only a type, e.g. '@string@;@int@:==1'

                if condition is None:
                    aux_final_fo += "\n\t" + fo
                    continue

                # Two methods here: pattern matching or simple operator ('>=<') match.
                # Pattern matching does not allow logical operations such as 'and' or 'or'.
                if condition[0].startswith('~'):
                    regex = re.compile(condition[0][1:])
                    partial.append(bool(regex.match(data)))

                    # Notify the user if a condition doesn't match
                    if not partial[-1]:
                        description += '\n\tPattern "%s" does not match against "%s"\n' % (condition[0][1:], str(data))
                else:
                    eval_str = ''

                    for item in condition:
                        # This condition may have a logical connector ('something'=='anything' or 'whatever')
                        if item in ['and', 'or', 'not']:
                            eval_str = eval_str + ' ' + item + ' '
                        else:
                            # There are other conditions or 'wildcards' that may be used here.
                            # 'position' accepts an integer that represents the position variable in the match tuple.
                            # 'count' accepts an integer to compare with the match count in this position.
                            position_pattern = '(?P<position>(?P<pos_operator>(?:==|<|>))position\[(?P<pos_value>\d+)\])'
                            count_pattern = '(?P<count>(?P<count_operator>(?:==|<|>))count\[(?P<count_value>\d+|position\[(?P<pos_count>\d+)\]|even|odd)\])'
                            pattern = position_pattern + '|' + count_pattern

                            wildcards = re.search(pattern, item)
                            single_cond = item

                            if wildcards != None:
                                # 'position' wildcard.
                                if wildcards.group('position') != None:
                                    if int(wildcards.group('pos_value')) > (len(value) - 1):
                                        raise CheckError(msg='Could not evaluate positional argument in check "%s"' % self.__name,
                                                         plugin=self.__plugin.get_name())

                                    if datatype == '@int@' or datatype == '@float@':
                                        pos_value = value[int(wildcards.group('pos_value'))][1]
                                    elif datatype == '@char@' or datatype == '@string@':
                                        pos_value = '"' + value[int(wildcards.group('pos_value'))][1] + '"'
                                    else:
                                        pos_value = ''
                                    single_cond = single_cond.replace(wildcards.group('position'), wildcards.group('pos_operator') + str(pos_value))

                                # 'count' wildcard.
                                # This uses a pityful trick, because it doesn't check the actual 'data' value but the occurrence count.
                                # TODO: this is poorly implemented. It should not check the value every time, only once is enough.
                                if wildcards.group('count') != None:
                                    matched_value_count = len([x for x in values if x[j] != ''])
                                    subs_cond = '==%s and ' % str(data) if datatype in ['@int@', '@float@'] else '"%s"' % str(data)

                                    if wildcards.group('count_value').isdigit():
                                        subs_cond += str(matched_value_count) + wildcards.group('count_operator') + str(wildcards.group('count_value'))
                                    elif wildcards.group('count_value').startswith('position'):
                                        pos_count = int(wildcards.group('pos_count'))
                                        pos_matched_value_count = len([x for x in values if x[pos_count] != ''])
                                        subs_cond += str(matched_value_count) + wildcards.group('count_operator') + str(pos_matched_value_count)

                                    # 'even' and 'odd' keywords only work with the equality operator.
                                    elif wildcards.group('count_value') == 'even' and wildcards.group('count_operator') == '==':
                                        subs_cond += str(matched_value_count) + ' % 2 == 0'
                                    elif wildcards.group('count_value') == 'odd' and wildcards.group('count_operator') == '==':
                                        subs_cond += str(matched_value_count) + ' % 2 != 0'
                                    else:
                                        raise CheckError(msg='Condition "%s" is invalid' % (wildcards.group('count_value')),
                                                         plugin=self.__name)

                                    single_cond = single_cond.replace(wildcards.group('count'), subs_cond)

                            #
                            # Now, finally evaluate the data with the condition
                            #

                            if datatype == '@int@' or datatype == '@float@':
                                eval_str = eval_str + ' ' + str(data) + single_cond

                            elif datatype == '@char@' or datatype == '@string@':
                                eval_str = eval_str + '"' + data + '"' + single_cond

                            elif datatype == '@ipaddr@':
                                eval_str = eval_str + data + single_cond

                            else:
                                raise CheckError(msg='Condition data type "%s" is invalid' % datatype,
                                                 plugin=self.__plugin.get_name())

                    try:
                        partial.append(bool(eval(eval_str)))
                    except Exception as e:
                        raise CheckError(msg='Could not evaluate "%s": %s' % (eval_str, e),
                                         plugin=self.__plugin.get_name())

                    if not partial[-1]:
                        include_final_fo = True
                        if self.__severity == "Info":
                            if not info:
                                description += '\n\tCondition "%s" failed (this is just an information check)' % eval_str.lstrip()
                            else:
                                description += '\n\tCondition "%s" failed for "%s" (this is just an information check)' % (eval_str.lstrip(), info)
                        elif self.__plugin.get_alienvault_config()['has_ha'] and self.__ha_dependant:
                            if not info:
                                description += '\n\tCondition "%s" failed because HA is enabled. Disregard this check' % eval_str.lstrip()
                            else:
                                description += '\n\tCondition "%s" failed for "%s" because HA is enabled. Disregard this check' % (eval_str.lstrip(), info)
                        else:
                            if not info:
                                description += "\n\tCondition '%s' failed" % eval_str.lstrip()
                            else:
                                description += "\n\tCondition '%s' failed for '%s'" % (eval_str.lstrip(), info)
                            aux_final_fo += "\n\t" + fo
                    else:
                        if not info:
                            description += '\n\tCondition "%s" passed' % eval_str.lstrip()
                        else:
                            description += '\n\tCondition "%s" passed for "%s"' % (eval_str.lstrip(), info)
                        aux_final_fo += "\n\t" + fo

            if include_final_fo:
                final_fo += '\n\t' + aux_final_fo.split('\n\t')[-1]

        if self.__severity != 'Debug':
            if partial:
                outcome = reduce(lambda x, y: x | y if self.__fail_only_if_all_failed else x & y,
                                 partial)
            else:
                raise CheckError(msg='No conditions to evaluate',
                                 plugin=self.__plugin.get_name())

        return outcome, description, final_fo

    # Check against a 'set' type.
    def __check_set_conditions__(self, values):
        (outcome, description, fo) = (True, '', '')

        values = filter(None, values)
        values_set = set(values)
        values_set_str = str(values_set)

        for condition in self.__conditions['set']:
            (operation, parsed_condition) = Wildcard.set_operation(condition)
            if operation is None or parsed_condition is None:
                raise CheckError(msg='Unknown set operation: "%s"' % str(condition),
                                 plugin=self.__plugin.get_name())

            try:
                diff = eval(values_set_str + parsed_condition)
                if diff != set():
                    set_list = ", ".join(str(elem) for elem in diff if elem != '')
                    if self.__plugin.get_alienvault_config()['has_ha'] and self.__ha_dependant:
                        description += '\n\tCondition "%s: %s" failed because HA is enabled. Ignoring it' % (str(operation), set_list)
                        fo = self.__formatted_output.replace('@set_list@', set_list)
                    else:
                        description += '\n\tOffending values for operation "%s": %s\n' % (str(operation), set_list)
                        fo = self.__formatted_output.replace('@set_list@', set_list)
                    outcome = False

            except SyntaxError, msg:
                raise CheckError(msg='Invalid syntax',
                                 plugin=self.__plugin.get_name())

        return outcome, description, fo

    # Run actions related to this check.
    def __run_actions__(self):
        for (action_type, action_data) in self.__actions:
            if action_type == '@command':
                (cmd, args) = action_data.split(' ', 1)
                # TODO: use 'output' to do thingies.
                try:
                    proc = subprocess.Popen([cmd, args], shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
                    output, err = proc.communicate()
                except:
                    raise

            elif action_type == '@db':
                self.__plugin.run_query(action_data)

            else:
                raise CheckError(msg='Unknown action type: "%s"' % action_type,
                                 plugin=self.__name)
