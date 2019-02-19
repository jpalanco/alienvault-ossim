# -*- coding: utf-8 -*-
#
# License:
#
#    Copyright (c) 2003-2006 ossim.net
#    Copyright (c) 2007-2011 AlienVault
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
import os
import pwd
import grp
#
# LOCAL IMPORTS
#

from OssimConf import OssimConf
from OssimConf import OssimMiniConf
from Logger import Logger
from DBConstantNames import *
logger = Logger.logger

class nagios_host:
    _name=""
    _alias=""
    _address=""
    _use="generic-host"
    _parents=""
    __conf=None

    def __init__(self, ip, hostname, sensor, conf=None):
        self._name=ip
        self._alias=hostname
        self._address=ip
        if sensor != "":
            self._parents=sensor
        self.__conf=conf
    def debug(self, msg):
        print __name__," : ",msg

    def file_host(self):
        if self.__conf is None:
            self.__conf = OssimConf ()
            logger.debug("Getting new ossim config for %s " % self._name)
        return os.path.join(self.__conf[VAR_NAGIOS_CFG], "hosts", self._address + ".cfg")

    def write(self):
        cfg_text = "define host{\n"
        cfg_text += "\thost_name " + self._alias + "\n"
        cfg_text += "\talias " + self._alias + "\n"
        cfg_text += "\taddress " + self._address + "\n"
        cfg_text += "\tuse " + self._use + "\n"
        cfg_text += "\t}\n"
        try:
            f=open(self.file_host(), "w")
            f.write(cfg_text)
            logger.debug("host configuration checked for %s " % self._name)
        except Exception, e:
            logger.error(e)
            return False

    def host_in_nagios(self):
        return os.path.exists(self.file_host())

    def delete_host(self):
        if self.host_in_nagios():
            os.remove(self.file_host())
        else:
            logger.error("Error: File %s does NOT exist!" % self.file_host())


class nagios_host_service:
    _host_names=""
    _descr=""
    _check_cmd=""
    _use="generic-service"
    _notif=""
    _port=""
    _allow_protocols = [0,1,6]
    def __init__(self,service_type,protocol, hostnames, port, notif_interval="",conf=None):
        self._host_names=hostnames
        self._descr=''
        self._protocol = protocol
        self._port=port
        self._check_cmd=''
        self.__conf=conf
        if notif_interval != "":
            self._notif=notif_interval
        self._service_type = service_type
        self.select_command()

    def debug(self, msg):
        print __name__," : ", msg

    def file_host_service(self):
        if self.__conf is None:
            self.__conf = OssimConf (Const.CONFIG_FILE)
            logger.debug ("Checking nagios services for %s " % self._host_names)
        return os.path.join(self.__conf['nagios_cfgs'],"host-services",self._host_names + self._descr + ".cfg")

    def write(self):
        if self._protocol not in self._allow_protocols:
            return
        cfg_text = "define service{\n"
        cfg_text += "\thost_name " + self._host_names + "\n"
        cfg_text += "\tservice_description " + self._descr + "\n"
        cfg_text += "\tcheck_command " + self._check_cmd + "\n"
        cfg_text += "\tuse " + self._use + "\n"
        if self._notif != "":
            cfg_text += "\tnotification_interval " + self._notif + "\n"
        cfg_text += "\t}\n"
        try:
            f=open(self.file_host_service(), "w")
            f.write(cfg_text)
            logger.debug("service configuration checked for %s " % self._host_names)
            f.close()
        except Exception, e:
            logger.error(str(e))
            return False

    def file_host_service_in_nagios(self):
        return os.path.exists(self.file_host_service())

    def delete(self):
        if self.file_host_service_in_nagios():
            os.remove(self.file_host_service())
        else:
            logger.debug ("Error: File %s does NOT exist!" % self.file_host_service())

    def select_command(self):
        if self._protocol not in self._allow_protocols:
            return
        if self._service_type in  ['ping','PING']:
            self._check_cmd = "check_ping!100.0,20%!500,60%"
            self._descr='PING'
        else:
            port=self._port
            if port == 21 and self._service_type in ['ftp','FTP']:
                self._check_cmd="check_ftp"
                self._descr="FTP"
            elif port == 22 and self._service_type in ['ssh','SSH']:
                self._check_cmd="check_ssh"
                self._descr="SSH"
            elif port == 23 and self._service_type in ['telnet','TELNET']:
                self._check_cmd="check_tcp!23"
                self._descr="TELNET"
            elif port == 25 and self._service_type in ['smtp','SMTP']:
                self._check_cmd="check_smtp"
                self._descr="SMTP"
            elif port == 80  and self._service_type in ['http','HTTP']:
                self._check_cmd="check_http"
                self._descr="HTTP"
            elif port == 161  and self._service_type in ['snmp','SNMP']:
                self._check_cmd="check_snmp"
                self._descr="SNMP"
            elif port == 389  and self._service_type in ['ldap','LDAP']:
                self._check_cmd="check_ldap"
                self._descr="LDAP"
            elif port == 3306  and self._service_type in ['mysql', 'MYSQL']:
                db_ip = self.__conf.__getitem__('ossim_host')
                db_user = self.__conf.__getitem__('ossim_user')
                db_pass = self.__conf.__getitem__('ossim_pass')
                mysql_conf = '/etc/nagios/mysql.cnf'
                nagios_dir = os.path.dirname(mysql_conf)
                try:
                    os.stat(nagios_dir)
                except:
                    os.mkdir(nagios_dir)
                f = open(mysql_conf, 'w+')
                f.write('[client]\nuser = ' + db_user + '\npassword = ' + db_pass + '\n')
                f.close()
                uid = pwd.getpwnam("root").pw_uid
                gid = grp.getgrnam("nagios").gr_gid
                os.chown(mysql_conf, uid, gid)
                os.chmod(mysql_conf, 0640)
                self._check_cmd = "av_check_mysql!" + db_ip
                self._descr = "MYSQL"
            else:
                self._check_cmd="check_tcp!%d" % self._port
                self._descr="GENERIC_TCP_%d" % self._port
                # To search in /etc/services !!!

    def add_host(self,host):
        if self._host_names != "":
            self._host_names+=","
            self._host_names+=host

class nagios_host_group:
    _name=""
    _alias=""
    _members=""
    __conf=None

    def __init__(self, name, alias, members, conf=None):
        self._name=name
        self._alias=alias
        self._members=members
        self.__conf=conf

    def debug(self, msg):
        print __name__," : ",msg

    def file_host_group(self):
        if self.__conf is None:
            self.__conf = OssimConf ()
            logger.debug ("Checking nagios config for %s " % self._name)
        return os.path.join(self.__conf[VAR_NAGIOS_CFG], "hostgroups", self._name + ".cfg")

    def write(self):
        cfg_text = "define hostgroup{\n"
        cfg_text += "\thostgroup_name " + self._name + "\n"
        cfg_text += "\talias " + self._alias + "\n"
        cfg_text += "\tmembers " + self._members+ "\n"
        cfg_text += "\t}\n"
        try:
            f=open(self.file_host_group(), "w")
            f.write(cfg_text)
            logger.debug("hostgroup configuration checked for %s " % self._name)
        except Exception, e:
            logger.error(e)
            return False

    def host_group_in_nagios(self):
        return os.path.exists(self.file_host_group())

    def delete_host_group(self):
        if self.host_group_in_nagios():
            os.remove(self.file_host_group())
        else:
            logger.error ("Error: File %s does NOT exist!" % self.file_host_group())


class nagios_host_group_service:
    _name=""
    _alias=""
    _members=""
    __conf=None

    def __init__(self, name, alias, members, conf=None):
        self._name=name
        self._alias=alias
        self._members=members
        self.__conf=conf

    def file_host_group(self):
        if self.__conf is None:
            self.__conf = OssimConf ()
            logger.debug ("Checking nagios config for %s (%s)" % (self._name,self._alias))
        return (self.__conf[VAR_NAGIOS_CFG] + "hostgroup-services/" + self._name+ ".cfg")

    def write(self):
        cfg_text = "define hostgroup{\n"
        cfg_text += "\thostgroup_name " + self._name + "\n"
        cfg_text += "\talias " + self._alias + "\n"
        cfg_text += "\tmembers " + self._members+ "\n"
        cfg_text += "\t}\n"
        try:
            f=open(self.file_host_group(), "w")
            f.write(cfg_text)
            logger.debug("hostgroup configuration checked for %s (%s)" % (self._name,self._alias))
        except Exception, e:
            logger.debug(e)
            return False

    def host_group_in_nagios(self):
        return os.path.exists(self.file_host_group())

    def delete_host_group(self):
        if self.host_group_in_nagios():
            os.remove(self.file_host_group())
        else:
            logger.debug ("Error: File %s does NOT exist!" % self.file_host_group())


