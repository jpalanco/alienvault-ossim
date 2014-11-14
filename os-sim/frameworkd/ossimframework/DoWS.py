# License:
#
#    Copyright (c) 2003-2006 ossim.net
#    Copyright (c) 2007-2014 AlienVault
#    All rights reserved.
#
#    This package is free software; you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation; version 2 dated June, 1991.
#    You may not use, modify or distribute this program under any other version
#    of the GNU General Public License.
#
#    This package is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with this package; if not, write to the Free Software
#    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#    MA  02110-1301  USA
#
#
# On Debian GNU/Linux systems, the complete text of the GNU General
# Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
# Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#

import re
import uuid
import json
from suds.client import Client
from WSTranslation import *
from WSValid import *
from OssimConf import OssimConf
from DBConstantNames import *
from OssimDB import OssimDB
from Logger import Logger

VALID_PARAMS = ['ws', 'type', 'op']
logger = Logger.logger

class WSHandler:
  def __init__ (self, conf, id):
    self.__id = id
    self.__default = {}
    self.__ops = {}
    logger.info ("Setting up new WS handler for id '%s'" % id)

    # Read the configuration from the database.
    db = OssimDB (conf[VAR_DB_HOST], "alienvault", conf[VAR_DB_USER], conf[VAR_DB_PASSWORD])
    db.connect()
    # Sanitize the id param. Exactly we need 32 hex characters
    if re.match(r'^[a-fA-F0-9]{32}$',self.__id) == None:
        raise Exception('Bad  webservice id')
    data = db.exec_query ("SELECT HEX(id), type, name, url, namespace, user, pass FROM alienvault.webservice WHERE id = UNHEX('%s')" % self.__id)
    if data != []:
      ws_config = data[0]
    else:
      raise Exception('Id %s does not match a valid webservice' % id)

    ws_default = db.exec_query ("SELECT field, value FROM alienvault.webservice_default WHERE ws_id = UNHEX('%s')" % self.__id)
    if ws_default != []:
      for item in ws_default:
        self.__default[item['field']] = item['value']

    ws_oper = db.exec_query ("SELECT op, type, attrs FROM alienvault.webservice_operation WHERE ws_id = UNHEX('%s')" % self.__id)
    if ws_oper == []:
      raise Exception('Id %s does not match a valid webservice' % id)

    for item in ws_oper:
      self.__ops[item['type']] = {'op': item['op'], 'attrs': [x.replace(' ', '') for x in item['attrs'].split(',')]}

    # Connect to the WS.
    self.__server = Client(ws_config['url'])

    # Authenticate if needed (This may be Remedy specific!!!)
    authinfo_field = ''
    username_field = ''
    password_field = ''
    authentication_field = ''
    locale_field = ''
    timezone_field = ''

    try:
      auth_op = self.__ops['auth']
    except KeyError:
      pass
    else:
      authinfo_field = auth_op['op']
      [username_field, password_field, authentication_field, locale_field, timezone_field] = auth_op['attrs']

      token = self.__server.factory.create(authinfo_field)
      token.__setitem__(username_field, ws_config['user'])
      token.__setitem__(password_field, ws_config['pass'])
#      token.__setitem__(authentication_field, ws_config['auth'])
#      token.__setitem__(locale_field, ws_config['locale'])
#      token.__setitem__(timezone_field, ws_config['tz'])

      self.__server.set_options(soapheaders=token)

  def process_json (self, ws_type, data):
    return self.__run_ws__ (ws_type, data)

  def process_db (self, ws_type, data):
    return self.__run_ws__ (ws_type, data)

  def __run_ws__ (self, ws_type, params):
    # All WS should have a valid type (TODO)
    # if not 'type' in params or params['type'] == '':
    #  logger.warning ('WS command does not contain a type attribute')
    #  return None

    # All WS should have a valid operation (TODO)
    #if not 'op' in params or params['op'] == '':
    #  logger.warning ('WS command does not contain an operation field')
    #  return None

    # All operations must be registered in the database (TODO)
    #if not params['op'] in self.__ops.keys():
    #  logger.warning ('WS command does not contain a valid operation field')
    #  return None

    # All operation attributes must belong to this operation. (TODO)
    #for param in params:
    #  if not (param in self.__ops[params['op']] and
    #          param != 'type' and
    #          param != 'op'):
    #    logger.warning ('WS command contains an invalid operation attribute: %s' % param)
    #    return None

    # Select the WS type (mostly used for field name translations)
    #try:
    #  translation = eval(params['type'].upper())
    #except NameError:
    #  # There isn't a valid translation table for this type. Don't worry.
    #  translation = {}
    translation = REMEDY
    calculated_values = REMEDY_CALCULATED_VALUES

    # Build the operation and params strings.
    package = self.__server.service
    operation = 'package.' + self.__ops[ws_type]['op']
    valid_attrs = self.__ops[ws_type]['attrs']
    attrs = ''

    for attr in valid_attrs:
      # Try first with the params issued from the web.
      # The value may be calculated.
      key, value = translation[attr]

      # Keys that don't have a translation.
      if key == '':
        key = attr

      # Special value: @DEFAULT@
      if value == '@DEFAULT@':
        logger.info ("Attribute '%s' has a default value" % str(key))
        value = self.__default[key]

      # This value may be a 'calculated' one.
      elif value == '@CALCULATED@':
        logger.info ("Attribute '%s' has a calculated value" % str(key))
        try:
          formula = calculated_values[attr]
          trans_formula = re.sub('@(?=\S+@)', 'params["', formula)
          trans_formula = re.sub('@', '"]', trans_formula)
          value = eval(trans_formula)
        except KeyError:
          pass
        except Exception, msg:
          logger.warning ('Unknown exception while calculating WS values: %s' % str(msg))
      else:
        try:
          value = params[key]
        except Exception, msg:
          logger.warning ('Unknown exception while assigning WS values: %s' % str(msg))

      attrs += '%s="%s",' % (attr, value)

    ws_command = operation + '(' + attrs.rstrip(',') + ')'
    logger.info ("Executing WS command: %s" % ws_command)
    try:
      ret = eval (ws_command)
    except Exception, msg:
      raise

    logger.info ("WS return value: '%s'" % str(ret))
    return ret
