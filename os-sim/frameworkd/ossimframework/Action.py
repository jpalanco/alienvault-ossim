# -*- coding: utf-8 -*-
#
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

#
# GLOBAL IMPORTS
#
import datetime
import threading
import re
import socket
import base64
import uuid
import time
from OssimConf import OssimMiniConf
ossim_setup = OssimMiniConf(config_file='/etc/ossim/ossim_setup.conf')

#
# LOCAL IMPORTS
#
from ActionMail import ActionMail
from ActionExec import ActionExec
from Logger import Logger
from OssimConf import OssimConf
from OssimDB import OssimDB
from DBConstantNames import *
from DoWS import *
import Util
import re
#
# GLOBAL VARIABLES
#
logger = Logger.logger

class Action(threading.Thread):
    base64field = ["username", \
                   "password", "filename", \
                   "userdata1", "userdata2", "userdata3", \
                   "userdata4", "userdata5", "userdata6", \
                   "userdata7", "userdata8", "userdata9",
                   "rep_act_src", "rep_act_dst"]

    def __init__(self, request):

        self.__request = self.parseRequest(request)
        self.__responses = {}
        self.__conf = OssimConf()
        self.__db = OssimDB(self.__conf[VAR_DB_HOST], \
                            self.__conf[VAR_DB_SCHEMA], \
                            self.__conf[VAR_DB_USER],
                            self.__conf[VAR_DB_PASSWORD]
                            )
        self.__component_id = None
        self.__email_server_relay_enabled = False

        self.__email_server = ""
        self.__email_server_port = 0
        self.__email_server_user = ""
        self.__email_server_passwd = ""

        threading.Thread.__init__(self)

    def get_mail_server_data(self):
        """Retrieves the email server configuration from the database.
        """
        try:
            server = ossim_setup['mailserver_relay']                #data['mailserver_relay']
            server_port = ossim_setup['mailserver_relay_port']      #data['mailserver_relay_port']
            server_user = ossim_setup['mailserver_relay_user']      #data['mailserver_relay_user']
            server_passwd = ossim_setup['mailserver_relay_passwd']  #data['mailserver_relay_passwd']
            print server,server_passwd,server_port,server_user
            if server == "no":
                logger.warning("Email server relay not configured -> mailserver_relay = no")
                self.__email_server_relay_enabled = False
                return
            self.__email_server = server

            try:
                self.__email_server_port = int(server_port)
            except ValueError:
                logger.warning("Invalid server port: %s" % server_port)
                self.__email_server_relay_enabled = False
                return
            self.__email_server_user = server_user
            self.__email_server_passwd = server_passwd
            self.__email_server_relay_enabled = True
        except Exception, e:
            self.__email_server_relay_enabled = False
            import traceback
            traceback.print_exc()
            logger.error("Error getting the email server configuration: %s" % str(e))

    def parseRequest(self, request):
        """Builds a hash with the request info

             request example:

             event date="2005-06-16 13:06:18" plugin_id="1505" plugin_sid="4"
             risk="8" priority="4" reliability="10" event_id="297179"
             backlog_id="13948" src_ip="192.168.1.10" src_port="1765"
             dst_ip="192.168.1.11" dst_port="139" protocol="6"
             sensor="192.168.6.64"
        """

        request_hash = {}

        try:
            request_hash['type'] = request.split()[0]
        except IndexError:
            request_hash['type'] = 'unknown'
            logger.warning("Sorry, unknown request type received: %s" % request)
            return {}

        result = re.findall('(\w+)="([^"]+)"', request)
        for i in result:
            if i[0] in Action.base64field:
                try:
                    request_hash[i[0]] = base64.b64decode(i[1])
                except TypeError:
                    logger.warning("Field not in base64: %s = %s" % (i[0], i[1]))
                    request_hash[i[0]] = i[1]
            else:
                request_hash[i[0]] = i[1]
        return request_hash

    def getActions(self, policy_id):
        '''
        get matched actions from db
        '''

        actions = []

        #
        # ANY: for strings  :'ANY'
        #      for integers : 0
        #

        query = "SELECT hex(action_id) as action_id FROM policy_actions " \
                "WHERE policy_id = unhex(%s)"
        action_info = self.__db.exec_query(query, (policy_id,))

        for action in action_info:
            action_id = action['action_id']
            if actions.count(action_id) == 0:
                actions.append(action_id)

        return actions

    def __transformDateToTimeZone(self, date, policy_id="", email_to=""):
        """Private function to transform a date in UTC to policy_id time zone

             params date    date in UTC format
             params policy_id   policy_id to find the correct time zone
             params email_to   user email
        """
        to_zone = "UTC+0"
        if email_to != "":
            query = "SELECT timezone FROM users WHERE email = %s"
            result = self.__db.exec_query(query, (email_to,))
            if result:
                to_zone = result[0]['timezone']
                date = Util.change_datetime_timezone(date, 'UTC', to_zone)
                return "%s [%s]" % (date, to_zone)

        if policy_id != "":
            policy_id = policy_id.replace('-', '')
            query = "SELECT timezone FROM policy_time_reference, policy " \
                "WHERE policy.id = policy_time_reference.policy_id " \
                "AND policy.id = UNHEX(%s)"
            result = self.__db.exec_query(query, (policy_id,))
            if result:
                to_zone = result[0]['timezone']
                date = Util.change_datetime_timezone(date, 'UTC', to_zone)

        return "%s [%s]" % (date, to_zone)

    def requestRepr(self, request, email_to):
        temp_str = " Alert detail: \n"
        for key, value in request.iteritems():
            if 'date' == key:
                value = self.__transformDateToTimeZone(value, self.__request.get('policy_id', ''), email_to)

            temp_str += " * %s: \t%s\n" % (key, value)

        return temp_str

    def getHostnameFromIP(self, hostip):
        hostname = ""
        query = "select hostname from host,host_ip where host.id=host_ip.host_id and host_ip.ip=inet6_aton(%s)"
        data = self.__db.exec_query(query, (hostip,))
        if data:
            hostname = data[0]['hostname']
        return hostname

    def doAction(self, action_id):
        src_hostname = self.getHostnameFromIP(self.__request.get('src_ip', ''))
        dst_hostname = self.getHostnameFromIP(self.__request.get('dst_ip', ''))
        plugin_id = int(self.__request['plugin_id'])
        plugin_sid = int(self.__request['plugin_sid'])

        protocol = Util.getProtoByNumber(self.__request.get('protocol', ''))
        self.__request['protocol'] = protocol
        replaces = {
            'DATE':         self.__transformDateToTimeZone(self.__request.get('date', ''), self.__request.get('policy_id', '')),
            'PLUGIN_ID':    self.__request.get('plugin_id', ''),
            'PLUGIN_SID':   self.__request.get('plugin_sid', ''),
            'RISK':         self.__request.get('risk', ''),
            'PRIORITY':     self.__request.get('priority', ''),
            'RELIABILITY':  self.__request.get('reliability', ''),
            'SRC_IP':       self.__request.get('src_ip', ''),
            'SRC_PORT':     self.__request.get('src_port', ''),
            'DST_IP':       self.__request.get('dst_ip', ''),
            'DST_PORT':     self.__request.get('dst_port', ''),
            'PROTOCOL':     self.__request.get('protocol', ''),
            'SENSOR':       self.__request.get('sensor', ''),
            'PLUGIN_NAME':  self.__request.get('plugin_id', ''),
            'SID_NAME':     self.__request.get('plugin_sid', ''),
            'USERDATA1':    self.__request.get('userdata1', ''),
            'USERDATA2':    self.__request.get('userdata2', ''),
            'USERDATA3':    self.__request.get('userdata3', ''),
            'USERDATA4':    self.__request.get('userdata4', ''),
            'USERDATA5':    self.__request.get('userdata5', ''),
            'USERDATA6':    self.__request.get('userdata6', ''),
            'USERDATA7':    self.__request.get('userdata7', ''),
            'USERDATA8':    self.__request.get('userdata8', ''),
            'USERDATA9':    self.__request.get('userdata9', ''),
            'FILENAME':     self.__request.get('filename', ''),
            'USERNAME':     self.__request.get('username', ''),
            'PASSWORD':     self.__request.get('password', ''),
            'BACKLOG_ID':   self.__request.get('backlog_id', ''),
            'EVENT_ID':     self.__request.get('event_id', ''),
            'SRC_IP_HOSTNAME': src_hostname,
            'DST_IP_HOSTNAME': dst_hostname,
        }

        # Fields with integer values
        int_fields = ["PLUGIN_ID", "PLUGIN_SID", "RISK", "PRIORITY", "RELIABILITY", "SRC_PORT", "DST_PORT"]

        query = "SELECT * FROM plugin WHERE id = %s"

        for plugin in self.__db.exec_query(query, (plugin_id,)):
            # should only yield one result anyway
            replaces["PLUGIN_NAME"] = plugin['name']

        query = "SELECT * FROM plugin_sid WHERE plugin_id = %s AND sid = %s"
        for psid in self.__db.exec_query(query, (plugin_id, plugin_sid)):
            # should only yield one result anyway
            replaces["SID_NAME"] = psid['name']

        query = "SELECT a.id as id,a.ctx as ctx,a.action_type as action_type ,\
                a.cond as cond,a.on_risk as on_risk,a.descr as descr,\
                at.name as name, a.name as action_name FROM action a, action_type at \
                WHERE id = unhex(%s) and a.action_type = at.type"
        for action in self.__db.exec_query(query, (action_id,)):

            ####################################################################
            # Condition
            ####################################################################

            # get the condition expression
            condition = action['cond']

            # authorized operators
            operators = [
                "+", "-", "*", "/", "%",
                "==", "<=", "<", ">=", ">",
                " and ", " or ", "(", ")",
                " True ", "False", "!=",
            ]

            # only operators and characters in [A-Z0-9_ ]
            # condition = '"' + condition +'"'
            condition_tmp = " %s " % condition
            for operator in operators:
                condition_tmp = condition_tmp.replace(operator, " ")
            logger.debug ("Condition after op %s before op %s " % (condition_tmp, condition))

            if not re.match("^[A-Za-z0-9_\'\"\. ]+$", condition_tmp):
                logger.warning(": Illegal character in condition: %s - Allowed characters (A-Za-z0-9_ ' \".)" % condition)
                condition = "False"

            # no function call
            if re.search("[A-Za-z0-9_]+\s*\(\.", condition):
                logger.warning(": Illegal function call in condition: %s" % condition)
                condition = "False"

            # replacements
            for key in replaces:
                if key in int_fields:
                    condition = condition.replace(key, replaces[key])
                else:
                    condition = condition.replace(key, "'" + replaces[key] + "'")

            # condition evaluation
            try:
                logger.debug(": condition = '%s'" % condition)
                condition = eval(condition)
            except Exception, e:
                logger.debug(": Condition evaluation failed: %s --> %s" % (condition, str(e)))
                condition = False
            logger.debug(": eval(condition) = %s" % condition)

            # is the condition True?
            if not condition: continue

            # is the action based on risk increase?
            if int(action['on_risk']) == 1:
                backlog_id = self.__request.get('backlog_id', '')
                risk_old = 0
                risk_new = int(self.__request.get('risk', ''))

                # get the old risk value
                query = "SELECT * FROM action_risk WHERE action_id = unhex(%s) AND backlog_id = unhex(%s)"
                for action_risk in self.__db.exec_query(query, (action_id, backlog_id)):
                    # should only yield one result anyway
                    risk_old = int(action_risk['risk'])
                    break
                else:
                    query = self.__db.format_query(
                        "INSERT INTO action_risk VALUES (%s, %s, %s)",
                        (int(action_id), int(backlog_id), int(risk_new))
                    )
                    logger.debug(": %s" % query)
                    self.__db.exec_query(query)

                # is there a risk increase?
                logger.debug(": risk_new > risk_old = %s" % (risk_new > risk_old))
                if risk_new <= risk_old: continue

                # save the new risk value
                query = self.__db.format_query(
                    "UPDATE action_risk SET risk = %s WHERE action_id = unhex(%s) AND backlog_id = unhex(%s)",
                    (int(risk_new), action_id, backlog_id)
                )
                logger.debug(": %s" % query)
                self.__db.exec_query(query)

                # cleanup the action_risk table
                query = "DELETE FROM action_risk WHERE backlog_id NOT IN (SELECT id FROM backlog)"
                logger.debug(": %s" % query)
                self.__db.exec_query(query)

            ####################################################################

            # email notification
            logger.info("Successful Response with action: %s" % action['descr'])
            if action['name'] == 'email':
                self.get_mail_server_data()
                if not self.__email_server_relay_enabled:
                    logger.warning("Email server relay not enabled. Using local postfix..")
                query = "SELECT * FROM action_email WHERE action_id = unhex(%s)"
                for action_email in self.__db.exec_query(query, (action_id,)):
                    email_from = action_email['_from']
                    email_to = action_email['_to'].split(',')
                    if len(email_to) == 1:
                        email_to = action_email['_to'].split(';')
                    email_subject = action_email['subject']
                    email_message = action_email['message']
                    """
                    When we are handling an email action, the date replacement must be translate in the next way:
                        * if email is in users table DATE will be shown in user's timezone
                        * if not, DATE will be shown in policy time zone
                        * if not exists policy time zone DATE will be display in UTC timezone.
                    For this reason we will replace the DATE for other replacement string in order to be replaced later
                    for the DATE in the correct timezone depends on the email. 
                    """
                    replaces["DATE"] = '##DATE##'
                    for replace in replaces:
                        if replaces[replace]:
                            email_from = email_from.replace(replace, str(replaces[replace]))
                            for to_mail in email_to:
                                to_mail = to_mail.strip()
                                to_mail = to_mail.replace(replace, \
                                                          str(replaces[replace]))
                            replace_variable = r'\b%s\b' % replace
                            value_to_replace = str(replaces[replace]).encode('string_escape')
                            email_subject = re.sub(replace_variable, value_to_replace, email_subject)
                            email_message = re.sub(replace_variable, value_to_replace, email_message)
                    use_local_server = not self.__email_server_relay_enabled
                    m = ActionMail(self.__email_server, self.__email_server_port, self.__email_server_user,
                                   self.__email_server_passwd, use_local_server)

                    for mail in email_to:
                        new_email_message = email_message

                        # Transforming DATE in mail time zone according to the previous rules
                        value_to_replace= self.__transformDateToTimeZone(self.__request.get('date', ''), self.__request.get('policy_id', ''), mail)
                        value_to_replace = str(value_to_replace).encode('string_escape')
                        email_subject = re.sub('##DATE##', value_to_replace, email_subject)
                        new_email_message = re.sub('##DATE##', value_to_replace, new_email_message)

                        if action_email['message_suffix'] == 1:
                            new_email_message += "\n\n" + self.requestRepr(self.__request, mail)
                        m.sendmail(email_from,
                                   mail,
                                   email_subject,
                                   new_email_message)
                    del(m)

            # execute external command
            elif action['name'] == 'exec':
                query = "SELECT * FROM action_exec WHERE action_id = unhex(%s)"
                for action_exec in self.__db.exec_query(query, (action_id,)):
                    action = action_exec['command']
                    for replace in replaces:
                        replace_variable = r'\b%s\b' % replace
                        action = re.sub(replace_variable, replaces[replace].encode('string_escape'), action)
                    c = ActionExec()
                    c.execCommand(action)
                    del(c)

            elif action['name'] == 'ticket':
                descr = action['descr']
                title = 'Automatic Incident Ticket '
                namequery = "SELECT name FROM plugin_sid WHERE plugin_id=%(plugin_id)s and sid=%(sid)s;"
                data = self.__db.exec_query(namequery, {"plugin_id": plugin_id, "sid": plugin_sid})
                if data != []:
                    title = data[0]['name']
                title = title.replace("directive_event: ", "")

                out = descr.split("##@##")
                descr = out[0]
                in_charge = out[1] if len(out) == 2 else 'admin'

                priority = int(self.__request.get('priority', '')) * 2
                incident_uuid = "%s" % uuid.uuid4()
                incident_uuid = incident_uuid.replace('-', '')
                ctx = self.__request.get('context_id', '')
                for replace in replaces:
                    if replaces[replace]:
                        replace_variable = r'\b%s\b' % replace
                        descr = re.sub(replace_variable, replaces[replace].encode('string_escape'), descr)


                ctx = ctx.replace('-', '')
                insert_query = "insert into incident (uuid,ctx,title,date,ref,type_id,priority,status,last_update," \
                               "in_charge,submitter,event_start,event_end) values (unhex(%(uuid)s)," \
                               "unhex(%(ctx)s),%(title)s,utc_timestamp(),'Event','Generic',%(priority)s," \
                               "'Open',utc_timestamp(),%(in_charge)s,'admin',utc_timestamp(),utc_timestamp());"
                params = {
                    "uuid": incident_uuid,
                    "ctx": ctx,
                    "title": title,
                    "priority": priority,
                    "in_charge": in_charge
                }
                insert_query = self.__db.format_query(insert_query, params)
                self.__db.exec_query(insert_query)
                logger.debug("Query: %s" % insert_query)

                src_ip = self.__request.get('src_ip', '')
                src_port = int(self.__request.get('src_port', ''))
                dst_ip = self.__request.get('dst_ip', '')
                dst_port = int(self.__request.get('dst_port', ''))
                #    last_id_ie = data[0]['id'] +1
                """ We need the last id from incident in order to insert it. """
                get_last_id_query = "select max(id) as id from incident;"
                data = self.__db.exec_query(get_last_id_query)
                if data != []:
                    last_id = data[0]['id']

                insert_incident_event_query = "INSERT INTO incident_event (incident_id,src_ips,src_ports,dst_ips,dst_ports) values (%s,%s,%s,%s,%s);"
                insert_subscription_query = "REPLACE INTO incident_subscrip(login, incident_id) VALUES('admin', %s)"
                self.__db.exec_query(insert_incident_event_query, (last_id, src_ip, src_port, dst_ip, dst_port))
                self.__db.exec_query(insert_subscription_query, (last_id,))

                data = self.__db.exec_query("select max(id)+1 as id from incident_ticket;")
                newid = '0'
                if data != []:
                    newid = data[0]['id']

                risk = int(self.__request.get('risk', ''))
                #Tickets from vulnerabilities come from a trigger, so we should only deal with events/alarms here
                if risk >= 0:
                    alarm_check_query = "select hex(backlog_id) as id from alarm where (backlog_id = unhex('%s') or event_id = unhex('%s')) LIMIT 1" % (self.__request.get('backlog_id', '').replace('-',''), self.__request.get('event_id', '').replace('-',''))
                    data = self.__db.exec_query(alarm_check_query)
                    if data != []:
                        backlog = data[0]['id']
                        logger.info("Backlog ID '%s' found in the database" % (backlog))
                    else:
                        logger.info("Backlog ID '%s' not found in the database" % (self.__request.get('backlog_id', '')))
                        backlog = self.__request.get('backlog_id', '').upper().replace('-','')

                    alarm_url = "<a target=\"_blank\" href=\"https://%s/ossim/#analysis/alarms/alarms-%s\">Link to Alarm</a>" % (ossim_setup['framework_ip'], backlog)
                    descr = "Ticket created automatically by an action (" + action["action_name"] + "):\n\n " + descr + "<br>" + alarm_url

                insert_ticket_query = "insert into incident_ticket(id,incident_id,date,status,priority,description," \
                                      "in_charge,users) values (%(id)s,%(incident_id)s,utc_timestamp(),'Open'," \
                                      "%(priority)s,%(description)s,%(in_charge)s,'admin');"
                insert_ticket_params = {
                    "id": newid,
                    "incident_id": last_id,
                    "priority": priority,
                    "description": descr,
                    "in_charge": in_charge
                }
                self.__db.exec_query(insert_ticket_query, insert_ticket_params)

                # email notification
                insert_ticket_communication = "REPLACE INTO incident_tmp_email (incident_id, ticket_id, type, subscribers)" \
                                              "VALUES (%(incident_id)s, %(ticket_id)s, %(email_nt)s, %(username)s);"
                insert_ticket_communication_params = {
                    "incident_id": last_id,
                    "ticket_id": newid,
                    "email_nt": "CREATE_INCIDENT",
                    "username": in_charge
                }
                self.__db.exec_query(insert_ticket_communication, insert_ticket_communication_params)

                # Check if this context has an IRS webservice linked.
                ticket_data = {'type': '', 'op': 'INSERT', 'incident_id': last_id, 'date': time.asctime(), 'in_charge': in_charge, 'description': descr, 'status': 'Open'}
                type_params = ["%s" for _ in xrange(len(IRS_TYPES))]
                ws_query = "SELECT id, type FROM webservice WHERE ctx = UNHEX(%s) AND type IN (" + \
                           ",".join(type_params) + ")"
                ws_data = self.__db.exec_query(ws_query, tuple([ctx] + IRS_TYPES))
                for item in ws_data:
                    ticket_data['type'] = ws_data['type']
                    # Create webservices, if available.
                    handler = WSHandler (self.__conf, item['id'])
                    if handler is not None:
                        ret = handler.process_db (ticket_data)
            else:
                logger.error("Invalid action_type: '%s'" % action['action_type'])

    def run(self):
        """Entry point for the thread.
        """
        if self.__request != {}:
            self.__db.connect()

            try:
                actions = self.getActions(self.__request['policy_id'].replace('-', ''))
                for action in actions:
                    self.doAction(action)
            except Exception, e:
                logger.error("Action can't be executed: %s" % str(e))

# vim:ts=4 sts=4 tw=79 expandtab:
