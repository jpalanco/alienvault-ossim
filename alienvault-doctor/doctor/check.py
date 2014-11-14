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

import subprocess
import re
import hashlib

from os import path

from output import Output

SEVERITY = ['High', 'Medium', 'Low']

'''
Check class.
Defines a checkpoint for a plugin.
'''
class Check:
  def __init__ (self, plugin, section, verbose):

    # 'check' properties.
    self.__name = ''
    self.__type = ''
    self.__category = ''
    self.__warning = ''
    self.__advice = ''
    self.__plugin = None

    # 'file' type checks only.
    self.__checksums = []

    # 'file' and 'command' checks.
    self.__regex = None

    # 'db' type checks.
    self.__query = ''
    self.__pivot = False

    self.__fail_if_empty = True
    self.__severity = 'Medium'
    self.__not = False
    self.__conditions = {'set':[], 'data':[]}
    self.__actions = []

    config_file = plugin.get_config_file ()

    self.__name = section
    self.__plugin = plugin
    self.__verbose = verbose > 1

    # Parse section options.
    # Different sections or check 'types' are mutually exclusive.
    items = config_file.items (section)

    try:
      for (name, value) in items:
        if name == 'checksum':
          self.__type = name
          self.__checksums = [tuple (x.split(':')) for x in value.split(';')]
        elif name == 'pattern':
          self.__type = name
          self.__regex = re.compile (value, re.MULTILINE)
        elif name == 'query':
          self.__type = name
          if value.startswith("pivot:"):
            self.__query = value[6:]
            self.__pivot = True
          else:
            self.__query = value
        elif name == 'category':
          self.__category = value
        elif name == 'fail_if_empty':
          if value in ['True', 'False']:
            self.__fail_if_empty = eval(value)
        elif name == 'severity':
          if value in SEVERITY:
            self.__severity = value
        elif name == 'conditions':
          self.__init_conditions__ (value)
        elif name == 'actions':
          self.__init_actions__ (value)
        elif name == 'warning':
          self.__warning = value
        elif name == 'advice':
          self.__advice = value
        else:
          Output.warning ('Unknown field in check "%s": %s' % (self.__name, name))
    except PluginError:
      raise
    except Exception, msg:
      Output.error ('Cannot parse check "%s" in plugin "%s": %s' % (self.__name, self.__plugin.get_name(), msg))
      raise

  def __init_conditions__ (self, value):
    # Check first if there are @set and other conditions in the same rule.
    # This is not allowed because standalone data type checks rely on order,
    # while @set tries to match with every field of the resulting regex/db query
    # regardless the order.
    if ('@set' in value) and \
        ('@int' in value or '@float' in value or '@string' in value or '@char' in value):
      raise PluginError ('Forbidden "@set" and any other datatype combination in rule "%s" for plugin "%s"' % (self.__name, self.__plugin.get_name()))

    for condition in value.split(';'):
      matches = re.findall('^(@\w+):?(\S+)?$', condition)
      cond_type, cond_str = matches[0]

      if cond_type == '@set':
        matches = re.findall('(@\w+@)?([^a-zA-Z0-9_\\:\."\'\\/]+)(\S+)', cond_str)
        cond_neg, cond_op, cond_set = matches[0]

        # Permit a @not@ in @set comparison.
        self.__not = bool(cond_neg)

        # For sets defined in files.
        if ',' in cond_set:
          items = cond_set.split(',')
        elif path.isfile (cond_set):
          desc = open (cond_set, 'r')
          items = desc.read().splitlines()
        else:
          Output.warning ('Not recognized set type for check "%s" in plugin "%s"' % (self.__name, self.__plugin.get_name()))
          continue

        content = set()
        for item in items:
          splitted_item = item.split('|')
          if len(splitted_item) > 1:
            content.add(tuple(splitted_item))
          else:
            content.add(item)

        self.__conditions['set'].append(cond_op + str(content))
      elif cond_type in ['@string', '@char', '@int', '@float', '@info']:
        self.__conditions['data'].append((cond_type, cond_str.rsplit('@') if cond_str != None and cond_str != '' else None))

      else:
        Output.warning ('Type "%s" not recognized for check "%s" in plugin "%s"' % (cond_type, self.__name, self.__plugin.get_name()))
        continue

  def __init_actions__ (self, value):
    self.__actions = [tuple (x.split(':')) for x in value.split(';')]

  # Getter methods.
  def get_name (self):
    return self.__name

  def get_type (self):
    return self.__type

  def get_category (self):
    return self.__category

  def get_severity (self):
    return self.__severity

  def get_warning (self):
    return self.__warning

  def get_advice (self):
    return self.__advice

  # Test if the severity match with the check ones.
  def check_severity (self, severities):
    # Treat the empty list as 'all'
    if severities == []:
      return True

    # Search for the 'all' wildcard.
    if 'all' in severities:
      return True

    if self.__severity in severities:
      return True

    return False

  # Run the check logic and return a boolean.
  def run (self):
    if self.__type == 'checksum':
      return self.__run_checksum__ ()
    elif self.__type == 'pattern':
      return self.__run_pattern__ ()
    elif self.__type == 'query':
      return self.__run_query__ ()
    else:
      return (False, 'Unknown check type "%s"' % self.__name)

  # Run a checksum against a file.
  def __run_checksum__ (self):
    for (func, checksum) in self.__checksums:
      h = hashlib.new (func[1:])
      h.update(self.__plugin.get_data())
      if h.hexdigest() == checksum:
        self.__run_actions__ ()
      else:
        return (False, 'Checksum "%s" failed!' % func[1:])

    return (True, '')

  # Check against a regular expression.
  def __run_pattern__ (self):
    matches = self.__regex.findall (self.__plugin.get_data())

    if matches != []:
      return self.__check_conditions__ (matches)
    else:
      return (False if self.__fail_if_empty else True, 'Empty match set for pattern "%s"' % self.__regex.pattern)

  # Run a db query and parse the result.
  def __run_query__ (self):
    ret = True
    results = self.__plugin.run_query (self.__query, result = True)
    if len(results) > 0:
      if not self.__pivot:
        for result in results:
          check_res, msg = self.__check_conditions__ (list(result))
          if (ret & check_res) == False:
            return (False, msg)
      else:
        # Pivot results.
        pivoted = [[] for x in range(len(results))]

        for result in results:
          for i in range(len(result)):
            pivoted[i].append(result[i])

      return (ret, self.__warning)
    else:
      return (False if self.__fail_if_empty else True, 'Empty result for query "%s"' % self.__query)

  # Check conditions against a set of 'values'.
  def __check_conditions__ (self, values):
    (ret, warn) = self.__check_set_conditions__ (values, self.__conditions['set'])
    if not ret:
      return (ret, warn)
    else:
      return self.__check_data_conditions__ (values, self.__conditions['data'])

  # Check against a 'set' type.
  def __check_set_conditions__ (self, values, set_conditions):
    if set_conditions == []:
      return (True, '')

    ret = True
    values_set = set(values)
    values_set_str = str(values_set)

    for condition in set_conditions:
      try:
        if self.__not:
          if bool(eval(values_set_str + condition)):
            if self.__verbose:
              Output.warning ('Set condition does not met!')
            ret &= False
        else:
          if not bool(eval(values_set_str + condition)):
            if self.__verbose:
              Output.warning ('Set condition does not met!')
            ret &= False

      except SyntaxError, msg:
        Output.warning ('Invalid rule syntax in check "%s"' % self.__name)
        return (False, 'Check "%s" failed because of invalid syntax' % self.__name)

    return (ret, self.__warning)

  def __check_data_conditions__ (self, values, data_conditions):
    if data_conditions == []:
      return (True, '')

    ret = True
    info = []

    for value in values:
      if type(value) == tuple:
        value = enumerate(value)
      else:
        value = [(0, value)]

      for j, data in value:
        try:
          datatype, condition = data_conditions[j]
        except IndexError:
          return (False, 'A pattern for check "%s" does not have the same size as the values set' % (self.__name))
        except ValueError:
          datatype, = data_conditions[j]
          condition = None

        # Check type.
        if datatype == '@info':
          info = data
          continue
        elif datatype == '@set':
          continue
        elif datatype == '@int':
          try:
            if data != '':
              data = int(data)
            else:
              data = 0
          except:
            return (False, 'Condition datatype is marked as "int" but is not an integer')
        elif datatype == '@float':
          try:
            if data != '':
              data = float(data)
            else:
              data = 0.0
          except:
            return (False, 'Condition datatype is marked as "float" but is not an floating point integer')
        elif datatype == '@char' and not data.isalpha():
          return (False, 'Condition datatype is marked as "char" but is not a character')

        # Sometimes, conditions are only for checking the match type, so they have
        # only a type, e.g. '@string;@int:==1'
        if condition == None:
          continue

        # Two methods here: pattern matching or simple operator ('>=<') match.
        # Pattern matching does not allow logical operations such as 'and' or 'or'.
        if condition[0].startswith('~'):
          regex = re.compile (condition[0][1:])
          partial = bool(regex.match (data))
          ret &= partial

          # Notify the user if a condition doesn't match
          if not partial and self.__verbose:
            Output.warning ('Pattern "%s" does not match' % condition[0][1:])
        else:
          eval_str = ''

          for item in condition:
            # This condition may have a logical connector ('something'=='anything' or 'whatever')
            if item in ['and', 'or', 'not']:
              eval_str = eval_str + ' ' + item
            else:
              # There are other conditions or 'wildcards' that may be used here.
              # 'position' accepts an integer that represents the position variable in the match tuple.
              # 'count' accepts an integer to compare with the match count in this position.
              position_pattern = '(?P<position>(?P<pos_operator>(?:==|<|>))position\[(?P<pos_value>\d+)\])'
              count_pattern = '(?P<count>(?P<count_operator>(?:==|<|>))count\[(?P<count_value>\d+|position\[(?P<pos_count>\d+)\]|even|odd)\])'
              pattern = position_pattern + '|' + count_pattern

              wildcards = re.search (pattern, item)
              single_cond = item

              if wildcards != None:
                # 'position' wildcard.
                if wildcards.group('position') != None:
                  if datatype == '@int' or datatype == '@float':
                    pos_value = values[j][int(wildcards.group('pos_value'))]
                  elif datatype == '@char' or datatype == '@string':
                    pos_value = '"' + values[j][int(wildcards.group('pos_value'))] + '"'
                  else:
                    pos_value = ''
                  single_cond = single_cond.replace(wildcards.group('position'), wildcards.group('pos_operator') + pos_value)

                # 'count' wildcard.
                # This uses a pityful trick, because it doesn't check the actual 'data' value but the occurrence count.
                # TODO: this is poorly implemented. It should not check the value every time, only once is enough.
                if wildcards.group('count') != None:
                  matched_value_count = len([x for x in values if x[j] != ''])
                  subs_cond = '== "%s" and ' % str(data)

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
                    if self.__verbose:
                      Output.warning ('Condition "%s" is invalid' % item)

                  single_cond = single_cond.replace(wildcards.group('count'), subs_cond)

              if datatype == '@int' or datatype == '@float':
                eval_str = eval_str +  ' ' + str(data) + single_cond
              elif datatype == '@char' or datatype == '@string':
                eval_str = eval_str + ' "' + data + '" ' + single_cond
              else:
                if self.__verbose:
                  Output.warning ('Condition datatype "%s" is invalid' % datatype)
                eval_str = 'False'

          try:
            partial = bool(eval(eval_str))
          except Exception as e:
            Output.warning ('Could not evaluate "%s" in check "%s": %s' % (eval_str, self.__name, e))
            partial = False

          ret &= partial
          if not partial:
            if self.__verbose:
              if not info:
                Output.warning ('Condition "%s" failed!' % eval_str.lstrip())
              else:
                Output.warning ('Condition "%s" failed for "%s"' % (eval_str.lstrip(), info))
          elif self.__verbose:
            Output.info ('Condition "%s" passed!' % eval_str.lstrip())

    if ret:
      self.__run_actions__ ()

    return (ret, self.__warning)


  # Run actions related to this check.
  def __run_actions__ (self):
    for (action_type, action_data) in self.__actions:
      if action_type == '@command':
        (cmd, args) = action_data.split (' ', 1)
        # TODO: use 'output' to do thingies.
        try:
          proc = subprocess.Popen([cmd, args], shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
          output, err = proc.communicate()
        except:
          raise

      elif action_type == '@db':
        self.__plugin.run_query (action_data)

      else:
        raise PluginError ('Unknown action type: "%s"', action_type)
