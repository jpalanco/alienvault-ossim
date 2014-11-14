#!/usr/bin/python
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
from ActionSyslog import *
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
                 "userdata7", "userdata8", "userdata9"] 

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
        # #11742 - Use the ossim_setup file instead of the avcenter database.

        # if not self.__component_id:
        #     self.__component_id = Util.get_my_component_id()
        # query = "select mailserver_relay,mailserver_relay_port, mailserver_relay_passwd,\
        #         mailserver_relay_user from avcenter.current_local \
        #         where uuid='%s';" % self.__component_id
        # mail_server_info = self.__db.exec_query(query)
        # if len(mail_server_info) > 1:
        #     self.__email_server_relay_enabled = False
        #     logger.error("Invalid mail server relay configuration, there's more than one configuration \
        #                  for the same uuid: %s" % self.__component_id)
        #     return
        # if len(mail_server_info) == 0:
        #     self.__email_server_relay_enabled = False
        #     logger.error("Invalid mail server relay configuration, there's no configuration \
        #                  for the uuid: %s" % self.__component_id)
        #     return
        try:
            #data = mail_server_info[0]
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

    def getActions(self, id):
        '''
        get matched actions from db
        '''

        actions = []

        #
        # ANY: for strings  :'ANY'
        #      for integers : 0
        #

        query = "SELECT hex(action_id) as action_id FROM policy_actions " + \
                        "WHERE policy_id = unhex('%s')" % re.escape(id)
        action_info = self.__db.exec_query(query)

        for action in action_info:
            action_id = action['action_id']
            if actions.count(action_id) == 0:
                actions.append(action_id)

        return actions


    def requestRepr(self, request, email_to):
        temp_str = " Alert detail: \n"
        for key, value in request.iteritems():
            if 'date' == key:
                query = "SELECT timezone FROM users WHERE email = '%s'" \
                        % email_to
                result = self.__db.exec_query(query)
                if result:
                    to_zone = result[0]['timezone']
                    value = Util.change_datetime_timezone(value, 'UTC', to_zone)
                else:
                    policy_id = self.__request.get('policy_id', '')
                    policy_id = policy_id.replace('-', '')
                    query = "SELECT timezone FROM policy_time_reference, policy " \
                            "WHERE policy.id = policy_time_reference.policy_id " \
                            "AND policy.id = UNHEX('%s')" \
                            % policy_id
                    result = self.__db.exec_query(query)
                    if result:
                        to_zone = result[0]['timezone']
                        value = Util.change_datetime_timezone(value, 'UTC', to_zone)

            temp_str += " * %s: \t%s\n" % (key, value)

        return temp_str

    def getHostnameFromIP(self, hostip):
        hostname = ""
        query = "select hostname from host,host_ip where host.id=host_ip.host_id and host_ip.ip=inet6_pton('%s')" % hostip;
        data = self.__db.exec_query(query)
        if data:            
            hostname = data[0]['hostname']
        return hostname

    def doAction(self, action_id):
        src_hostname = self.getHostnameFromIP(self.__request.get('src_ip', ''))
        dst_hostname = self.getHostnameFromIP(self.__request.get('dst_ip', ''))
        
        protocol = Util.getProtoByNumber(self.__request.get('protocol', ''))
        self.__request['protocol'] = protocol
        replaces = {
                'DATE':         self.__request.get('date', ''),
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
                'SRC_IP_HOSTNAME':src_hostname,
                'DST_IP_HOSTNAME':dst_hostname,
            }

        # Fields with integer values
        int_fields = ["PLUGIN_ID", "PLUGIN_SID", "RISK", "PRIORITY", "RELIABILITY", "SRC_PORT", "DST_PORT"]

        query = "SELECT * FROM plugin WHERE id = %d" % int(self.__request['plugin_id'])

        for plugin in self.__db.exec_query(query):
            # should only yield one result anyway
            replaces["PLUGIN_NAME"] = plugin['name']

        query = "SELECT * FROM plugin_sid WHERE plugin_id = %d AND sid = %d" % \
            (int(self.__request['plugin_id']), int(self.__request['plugin_sid']))
        for plugin_sid in self.__db.exec_query(query):
            # should only yield one result anyway
            replaces["SID_NAME"] = plugin_sid['name']

        query = "SELECT a.id as id,a.ctx as ctx,a.action_type as action_type ,\
                a.cond as cond,a.on_risk as on_risk,a.descr as descr,\
                at.name as name FROM action a, action_type at \
                WHERE id = unhex('%s') and a.action_type = at.type" % (action_id)
        for action in self.__db.exec_query(query):

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
            logger.debug ("Condiction after op %s before op %s " % (condition_tmp, condition))
            if not re.match("^[A-Za-z0-9_\'\" ]+$", condition_tmp):
                logger.warning(": Illegal character in condition: %s - Allowed characters (A-Za-z0-9_ ' \")" % condition)
                condition = "False"

            # no function call
            if re.search("[A-Za-z0-9_]+\s*\(", condition):
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
                query = "SELECT * FROM action_risk WHERE action_id = unhex('%s') AND backlog_id = unhex('%s')" % (action_id, backlog_id)
                for action_risk in self.__db.exec_query(query):
                    # should only yield one result anyway
                    risk_old = int(action_risk['risk'])
                    break
                else:
                    query = "INSERT INTO action_risk VALUES (%d, %d, %d)" % (
                        int(action_id), int(backlog_id), int(risk_new))
                    logger.debug(": %s" % query)
                    self.__db.exec_query(query)

                # is there a risk increase?
                logger.debug(": risk_new > risk_old = %s" % (risk_new > risk_old))
                if risk_new <= risk_old: continue

                # save the new risk value
                query = "UPDATE action_risk SET risk = %d WHERE action_id = unhex('%s') AND backlog_id = unhex('%s')" % (
                    int(risk_new), action_id, backlog_id)
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
                query = "SELECT * FROM action_email WHERE action_id = unhex('%s')" % \
                    (action_id)
                for action_email in self.__db.exec_query(query):
                    email_from = action_email['_from']
                    email_to = action_email['_to'].split(',')
                    if len(email_to) == 1:
                        email_to = action_email['_to'].split(';')
                    email_subject = action_email['subject']
                    email_message = action_email['message']

                    for replace in replaces:
                        if replaces[replace]:
                            email_from = email_from.replace(replace, replaces[replace])
                            for to_mail in email_to:
                                to_mail = to_mail.strip()
                                to_mail = to_mail.replace(replace, \
                                                          replaces[replace])
                            replace_variable = r'\b%s\b' % replace
                            value_to_replace = replaces[replace].encode('string_escape')
                            email_subject = re.sub(replace_variable,value_to_replace , email_subject)
                            # email_subject= email_subject.replace(replace, replaces[replace])
                            if replace == 'DATE':
                                value_to_replace += " (UTC time)"
                            email_message = re.sub(replace_variable, value_to_replace, email_message)
                            # email_message = email_message.replace(replace, replaces[replace])
                    use_local_server = not self.__email_server_relay_enabled
                    m = ActionMail(self.__email_server,self.__email_server_port,self.__email_server_user,
                                   self.__email_server_passwd, use_local_server)
                    # logger.info(email_message)

                    for mail in email_to:
                        m.sendmail(email_from,
                                   mail,
                                   email_subject,
                                   email_message + \
                                   "\n\n" + self.requestRepr(self.__request, mail))
                    del(m)

            # execute external command
            elif action['name'] == 'exec':
                query = "SELECT * FROM action_exec WHERE action_id = unhex('%s')" % \
                    (action_id)
                for action_exec in self.__db.exec_query(query):
                    action = action_exec['command']
                    for replace in replaces:
                        replace_variable = r'\b%s\b' % replace
                        action = re.sub(replace_variable, replaces[replace].encode('string_escape'), action)
                        # action = action.replace(replace, replaces[replace])
                    c = ActionExec()
                    c.execCommand(action)
                    del(c)

            elif action['name'] == 'syslog':
                pass
#                syslog(self.__request) 
            elif action['name'] == 'ticket':
                descr = action['descr']
                plugin_id = int(self.__request.get('plugin_id', ''))
                plugin_sid = int(self.__request.get('plugin_sid', ''))
                title = 'Automatic Incident Ticket'
                namequery = "select if((select name from plugin_sid where plugin_id='%s' and sid='%s')!='',(select name from plugin_sid where plugin_id='%s' and sid='%s')  , 'Automatic Incident Ticket') as name;" % (plugin_id, plugin_sid, plugin_id, plugin_sid)
                data = self.__db.exec_query(namequery)
                if data != []:
                    title = data[0]['name']
                regexp = re.compile('(?P<data>.*)##@##(?P<username>.*)')
                matches = regexp.search(descr)
                in_charge = 'admin'
                if matches:
                    in_charge = matches.group('username')
                    descr = matches.group('data')

                priority = int(self.__request.get('priority', '')) * 2
                incident_uuid = "%s" % uuid.uuid4()
                incident_uuid = incident_uuid.replace('-', '')
                ctx = self.__request.get('context_id', '')
                for replace in replaces:
                    if replaces[replace]:
                        replace_variable = r'\b%s\b' % replace
                        descr = re.sub(replace_variable, replaces[replace].encode('string_escape'), descr)


                ctx = ctx.replace('-', '')
                insert_query = """insert into incident (uuid,ctx,title,date,ref,type_id,priority,status,last_update,in_charge,submitter,event_start,event_end) values (unhex('%s'),unhex('%s'),'%s',utc_timestamp(),'Event','Generic','%s','Open',utc_timestamp(),'%s','admin',utc_timestamp(),utc_timestamp()); """ % (incident_uuid, ctx, title, priority, in_charge)
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
                insert_incident_event_query = """ insert into incident_event (incident_id,src_ips,src_ports,dst_ips,dst_ports) values ('%s','%s','%s','%s','%s'); """ % (last_id, src_ip, src_port, dst_ip, dst_port) 
                data = self.__db.exec_query("select max(id)+1 as id from incident_ticket;")
                newid = '0'
                if data != []:
                    newid = data[0]['id']

                self.__db.exec_query(insert_incident_event_query)
                insert_ticket_query = """ insert into incident_ticket(id,incident_id,date,status,priority,description,in_charge,users) values ('%s','%s',utc_timestamp(),'Open','%s','%s','%s','');""" % (newid, last_id, priority, descr, in_charge)
                self.__db.exec_query(insert_ticket_query)

                # Check if this context has an IRS webservice linked.
                ticket_data = {'type': '', 'op': 'INSERT', 'incident_id': last_id, 'date': time.asctime(), 'in_charge': in_charge, 'description': descr, 'status': 'Open'}
                ws_query = "SELECT id, type FROM webservice WHERE ctx = UNHEX('%s') AND type IN (%s)" % (ctx, '(' + ','.join(IRS_TYPES) + ')')
                ws_data = self.__db.exec_query(ws_query)
                for item in ws_data:
                    ticket_data['type'] = ws_data['type']
                    # Create webservices, if available.
                    handler = WSHandler (self.__conf, item['id'])
                    if handler != None:
                        ret = handler.process_db (ticket_data)

            else:
                logger.error("Invalid action_type: '%s'" % action['action_type'])


    def mailNotify(self):
        """
        Notify every alarm if email_alert is set
        """
        email = self.__conf[VAR_ALERT_EMAIL]
        emails = self.__conf[VAR_ALERT_EMAIL_SENDER]
        if emails is None or emails == "":
            emails = "ossim@localhost"

        if email is not None and email != "":
            use_local_server = not self.__email_server_relay_enabled

            m = ActionMail(self.__email_server,self.__email_server_port,self.__email_server_user,
                           self.__email_server_passwd, use_local_server)

            for mail in [self.__conf['email_alert']]:
                m.sendmail(self.__conf['email_sender'], mail,
                           "Ossim Alert from server '%s'" % (socket.gethostname()),
                           self.requestRepr(self.__request, mail))

            logger.info("Notification sent from %s to %s" % (emails, (self.__conf['email_alert'])))


    def run(self):
        """Entry point for the thread. 
        """
        if self.__request != {}:
            if self.__request['type'] == "event":
                self.mailNotify()

            self.__db.connect()

            try:
                actions = self.getActions(self.__request['policy_id'].replace('-', ''))
                for action in actions:
                    self.doAction(action)
            except Exception, e:
                logger.error("Action can't be executed: %s" % str(e))

# vim:ts=4 sts=4 tw=79 expandtab:
