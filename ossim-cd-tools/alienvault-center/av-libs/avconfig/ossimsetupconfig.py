# -*- coding: utf-8 -*-
#
# © Alienvault Inc. 2012
# All rights reserved
#
# This code is protected by copyright and distributed under licenses
# restricting its use, copying, distribution, and decompilation. It may
# only be reproduced or used under license from Alienvault Inc. or its
# authorised licensees.

import subprocess
import json
import traceback
import shutil
import glob
import time
from lockfile import FileLock, AlreadyLocked, LockFailed
from netinterfaces import get_network_interfaces, get_local_ip_addresses_list
from logger import Logger
from utils import *
import os, stat, grp
from avconfigparser import AVConfigParser
from avconfigparsererror import AVConfigParserErrors
from avsysconfig import AVSysConfig
from uuid import UUID

logger = Logger.logger

BACKUP_FOLDER = "/var/lib/ossim/backup/"
# BACKUP_FOLDER="/tmp/backup/"
MAX_BACKUP_FILES = 5
LOG_FILE = "/var/log/alienvault/av_config/av_configlib.log"


class AVOssimSetupConfigHandler():
    """Class to manage the ossim-setup.conf 
    """
    # SECTION NAMES
    NO_SECTION_NAME = "GENERAL"
    FILE_SECTION = "FILE_ERRORS"
    FILE_SECTION_OPTION = "file"

    DATABASE_SECTION_NAME = "database"
    EXPERT_SECTION_NAME = "expert"
    FRAMEWORK_SECTION_NAME = "framework"
    FIREWALL_SECTION_NAME = "firewall"
    SENSOR_SECTION_NAME = "sensor"
    SERVER_SECTION_NAME = "server"
    SNMP_SECTION_NAME = "snmp"
    UPDATE_SECTION_NAME = "update"
    HA_SECTION_NAME = "ha"

    # NO SECTION OPTIONS
    NO_SECTION_NAME_ADMIN_DNS = "admin_dns"
    NO_SECTION_NAME_ADMIN_GATEWAY = "admin_gateway"
    NO_SECTION_NAME_ADMIN_IP = "admin_ip"
    NO_SECTION_NAME_ADMIN_NETMASK = "admin_netmask"
    NO_SECTION_NAME_DOMAIN = "domain"
    NO_SECTION_NAME_EMAIN_NOTIFY = "email_notify"
    NO_SECTION_NAME_MAX_RETRIES = "max_retries"
    NO_SECTION_NAME_HOSTNAME = "hostname"
    NO_SECTION_NAME_INTERFACE = "interface"
    NO_SECTION_NAME_MAILSERVER_RELAY = "mailserver_relay"
    NO_SECTION_NAME_MAILSERVER_RELAY_PASSWD = "mailserver_relay_passwd"
    NO_SECTION_NAME_MAILSERVER_RELAY_PORT = "mailserver_relay_port"
    NO_SECTION_NAME_MAILSERVER_RELAY_USER = "mailserver_relay_user"
    NO_SECTION_NAME_NTP_SERVER = "ntp_server"
    NO_SECTION_NAME_PROFILE = "profile"

    # DATABASE SECTION OPTIONS
    SECTION_DATABASE_IP = "db_ip"
    SECTION_DATABASE_PASSWORD = "pass"
    SECTION_DATABASE_USER = "user"

    # PROFILE SECTION OPTIONS
    SECTION_EXPERT_PROFILE = "profile"

    # FIREWALL SECTION OPTIONS
    SECTION_FIREWALL_ACTIVE = "active"

    # FRAMWEORK SECTION OPTIONS
    SECTION_FRAMEWORK_HTTPS_CERT = "framework_https_cert"
    SECTION_FRAMEWORK_HTTPS_KEY = "framework_https_key"
    SECTION_FRAMEWORK_IP = "framework_ip"

    # SENSOR SECTION OPTIONS
    SECTION_SENSOR_DETECTORS = "detectors"
    SECTION_SENSOR_IDS_RULES_FLOW_CONTROL = "ids_rules_flow_control"
    SECTION_SENSOR_INTERFACES = "interfaces"
    SECTION_SENSOR_IP = "ip"
    SECTION_SENSOR_MONITORS = "monitors"
    SECTION_SENSOR_MSERVER = "mservers"
    SECTION_SENSOR_NAME = "name"
    SECTION_SENSOR_NETFLOW = "netflow"
    SECTION_SENSOR_NETFLOW_REMOTE_COLLECTOR_PORT = "netflow_remote_collector_port"
    SECTION_SENSOR_NETWORKS = "networks"
    SECTION_SENSOR_PCI_EXPRESS = "pci_express"
    SECTION_SENSOR_TZONE = "tzone"
    SECTION_SENSOR_ASEC = "asec"
    SECTION_SENSOR_CTX = "sensor_ctx"

    # SERVER SECTION OPTIONS
    SECTION_SERVER_ALIENVAULT_IP_REPUTATION = "alienvault_ip_reputation"
    SECTION_SERVER_IP = "server_ip"
    SECTION_SERVER_PLUGINS = "server_plugins"
    SECTION_SERVER_PRO = "server_pro"

    # SNMP SECTION OPTIONS
    SECTION_SNMP_COMMUNITY = "community"
    SECTION_SNMP_SNMP_COMMUNITY = "snmp_comunity"
    SECTION_SNMP_SNMPD = "snmpd"
    SECTION_SNMP_SNMPTRAP = "snmptrap"

    # UPDATE SECTION OPTIONS
    SECTION_UPDATE_PROXY = "update_proxy"
    SECTION_UPDATE_PROXY_DNS = "update_proxy_dns"
    SECTION_UPDATE_PROXY_PASSWORD = "update_proxy_pass"
    SECTION_UPDATE_PROXY_PORT = "update_proxy_port"
    SECTION_UPDATE_PROXY_USER = "update_proxy_user"

    # HA SECTION OPTIONS
    """
    ha_autofailback=no
    ha_deadtime=10
    ha_device=eth0
    ha_heartbeat_comm=bcast
    ha_heartbeat_start=no
    ha_keepalive=3
    ha_local_node_ip=192.168.200.2
    ha_log=no
    ha_other_node_ip=unconfigured
    ha_other_node_name=unconfigured
    ha_password=unconfigured
    ha_ping_node=default
    ha_role=master
    ha_virtual_ip=unconfigured"""
    # We only need ha_role and ha_virtual_ip
    SECTION_HA_HA_AUTOFAILBACK = "ha_autofailback"
    SECTION_HA_HA_DEADTIME = "ha_deadtime"
    SECTION_HA_HA_DEVICE = "ha_device"
    SECTION_HA_HA_HEARTBEAT_COMM = "ha_heartbeat_comm"
    SECTION_HA_HA_HEARTBEAT_START = "ha_heartbeat_start"
    SECTION_HA_HA_KEEPALIVE = "ha_keepalive"
    SECTION_HA_HA_LOCAL_NODE_IP = "ha_local_node_ip"
    SECTION_HA_HA_LOG = "ha_log"
    SECTION_HA_HA_OTHER_NODE_IP = "ha_other_node_ip"
    SECTION_HA_HA_OTHER_NODE_NAME = "ha_other_node_name"
    SECTION_HA_HA_PASSWORD = "ha_password"
    SECTION_HA_HA_PING_NODE = "ha_ping_node"
    SECTION_HA_HA_ROLE = "ha_role"
    SECTION_HA_HA_VIRTUAL_IP = "ha_virtual_ip"

    # NON OSSIM_SETUP.CONF OPTIONS
    INTERFACES_FILE = 'network/interfaces'
    INTERFACES_FILE_ADDRESS = 'address'
    INTERFACES_FILE_NETMASK = 'netmask'

    # DEFAULT VALUES
    DEFAULT_VALUES = {NO_SECTION_NAME: {NO_SECTION_NAME_MAILSERVER_RELAY: "no",
                                        NO_SECTION_NAME_MAILSERVER_RELAY_PASSWD: "unconfigured",
                                        NO_SECTION_NAME_MAILSERVER_RELAY_PORT: "25",
                                        NO_SECTION_NAME_MAILSERVER_RELAY_USER: "unconfigured",
                                        NO_SECTION_NAME_NTP_SERVER: "no"},
                      FIREWALL_SECTION_NAME: {SECTION_FIREWALL_ACTIVE: "yes"},
                      FRAMEWORK_SECTION_NAME: {SECTION_FRAMEWORK_HTTPS_CERT: "default",
                                               SECTION_FRAMEWORK_HTTPS_KEY: "default"},
                      SENSOR_SECTION_NAME: {SECTION_SENSOR_IDS_RULES_FLOW_CONTROL: "yes",
                                            SECTION_SENSOR_MSERVER: "no",
                                            SECTION_SENSOR_NETFLOW: "yes",
                                            SECTION_SENSOR_NETWORKS: "192.168.0.0/16,172.16.0.0/12,10.0.0.0/8",
                                            SECTION_SENSOR_ASEC: "no"},
                      SERVER_SECTION_NAME: {SECTION_SERVER_ALIENVAULT_IP_REPUTATION: "enabled"},
                      SNMP_SECTION_NAME: {SECTION_SNMP_COMMUNITY: "public",
                                          SECTION_SNMP_SNMPD: "yes",
                                          SECTION_SNMP_SNMPTRAP: "yes", },
                      UPDATE_SECTION_NAME: {SECTION_UPDATE_PROXY: "disabled",
                                            SECTION_UPDATE_PROXY_DNS: "my.proxy.com",
                                            SECTION_UPDATE_PROXY_PASSWORD: "disabled",
                                            SECTION_UPDATE_PROXY_PORT: "disabled",
                                            SECTION_UPDATE_PROXY_USER: "disabled"},
                      HA_SECTION_NAME: {
                          SECTION_HA_HA_VIRTUAL_IP: "unconfigured",
                      }
                      }
    PROFILE_NAME_DATABASE = "Database"
    PROFILE_NAME_SENSOR = "Sensor"
    PROFILE_NAME_SERVER = "Server"
    PROFILE_NAME_FRAMEWORK = "Framework"
    ALLOWED_PROFILES = [PROFILE_NAME_DATABASE, PROFILE_NAME_SENSOR, PROFILE_NAME_SERVER, PROFILE_NAME_FRAMEWORK]
    YES_NO_CHOICES = ["yes", "no"]
    ENABLE_DISABLE_CHOICES = ["enabled", "disabled"]
    PROXY_VALUES = ["disabled", "manual", "alienvault-proxy"]
    PROXY_VALUES_NO_PRO = ["disabled", "manual"]
    ALLOWED_HA_ROLES = ["master", "slave"]

    def __init__(self, filename="/etc/ossim/ossim_setup.conf", logfile=LOG_FILE):
        """Constructor
        """
        self.__ossim_setup_file = filename
        self.__ossim_setup_stat = None
        self.__avconfig_setup = None
        self.__lockFile = FileLock(filename)
        self.__ossim_setup_md5 = None
        self.__interfaces = []
        self.__registered_systems = []

        # Indicates is pending changes.
        self.__file_dirty = False
        self.__modified_values = {}
        self.__errors = {}  # list table to stored errors

        # Load data
        self.__load_net_interface_names()
        self.__avconfig_loaded_ok = False
        self.__logfile = logfile
        self.__init_logger(logfile)
        self.__load_config()

        # Non ossim-setup.conf related.
        self.__sysconfig = AVSysConfig()

    def __init_logger(self, logfile):
        """Initiate the logger. """

        # open file handlers (main and error logs)
        Logger.add_file_handler(logfile)
        Logger.remove_console_handler()

        if os.geteuid() == 0:
            gid = grp.getgrnam("alienvault").gr_gid
            currMode = os.stat(logfile).st_mode
            os.chown(logfile, -1, gid)
            os.chmod(logfile, currMode | stat.S_IWGRP)


            ###############################################################################
            #           PRIVATE API
            ###############################################################################

    def get_default_value(self, section, option):
        """Returns the default value for the given section, option.
        whether the default value doesn't exists it returns None
        """
        if section in self.DEFAULT_VALUES:
            if option in self.DEFAULT_VALUES[section]:
                return self.DEFAULT_VALUES[section][option]
        return None

    def __set_default_value_if_needed(self, section, option, value):
        """Check whether the given value needs to be set to default"""
        default_value = self.get_default_value(section, option)
        if value is None or value == "" and default_value:
            value = default_value
        return value

    def __is_default(self, section, option, value):
        """Checks whether a value it's a default value
        """
        if section in self.DEFAULT_VALUES and option in self.DEFAULT_VALUES[section]:
            if value == self.DEFAULT_VALUES[section][option]:
                return True
        return False

    def __set_option(self, section, option, value):
        """Establishes the value for an option 
        Set the file as dirty
        Remove errors. 
        """
        self.__remove_error(section, option)
        old_value = self.__avconfig_setup.get_option(section, option)
        if old_value == value:
            return
        self.__avconfig_setup.set(section, option, value)
        self.__add_dirty_option(section, option, value)
        if section == "":
            section = self.NO_SECTION_NAME

    def __add_error(self, section, option, tuple_error):
        """Add an error to the hash
        errors[section] = {}
        
        No section -> GENERAL
        
        """
        logger.error("add_error: [%s]->%s %s" % (section, option, str(tuple_error)))
        if section not in self.__errors:
            self.__errors[section] = {}
        self.__errors[section][option] = tuple_error

    def __remove_error(self, section, option):
        """Remove the option errors.
        """
        if section in self.__errors and option in self.__errors[section]:
                del self.__errors[section][option]

    def __add_dirty_option(self, section, option, value):
        """Set the file as dirty and add the option to the 
        modified value list 
        """
        self.__file_dirty = True
        if section not in self.__modified_values:
            self.__modified_values[section] = {}
        self.__modified_values[section][option] = value

    def __load_net_interface_names(self):
        """Load a list of system network interfaces.
        Exclude lo
        """
        interfaces = get_network_interfaces()
        for interface in interfaces:
            if interface.name == "lo":
                continue
            self.__interfaces.append(interface.name)

    def __check_file_stat(self):
        """Get the file stat
        """
        result = True
        try:
            self.__ossim_setup_md5 = md5sum(self.__ossim_setup_file)
            self.__ossim_setup_stat = os.stat(self.__ossim_setup_file)
        except Exception:
            result = False
        return result

    def __load_config(self):
        """Loads the configuration file.
        """
        result = True
        try:
            logger.info("Loading ossim-setup file... %s" % self.__ossim_setup_file)
            del self.__avconfig_setup
            self.__errors.clear()
            self.__modified_values.clear()
            self.__file_dirty = False
            self.__avconfig_setup = None
            if self.__check_file_stat():
                self.__avconfig_setup = AVConfigParser(default_section_for_values_without_section=self.NO_SECTION_NAME)
                ret = self.__avconfig_setup.read(self.__ossim_setup_file)
                if ret[0] == AVConfigParserErrors.SUCCESS:
                    self.__avconfig_loaded_ok = True
                    self.validate_config_file()
                else:
                    logger.error("ossim-setup file can't be read")
                    error = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_CANT_BE_LOADED, ret[1])
                    self.__add_error(self.FILE_SECTION, self.FILE_SECTION_OPTION, error)
            else:
                result = False
                # FILE_CANT_BE_LOADED_CANNOT_STAT
                logger.error("ossim-setup cannot stat")
                error = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_CANT_BE_LOADED_CANNOT_STAT,
                                                           "File can't be readed")
                self.__add_error(self.FILE_SECTION, self.FILE_SECTION_OPTION, error)
        except Exception, e:
            logger.error("Exception loading ossim-setup: %s" % str(e))
            error = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_CANT_BE_LOADED, str(e))
            self.__add_error(self.FILE_SECTION, self.FILE_SECTION_OPTION, error)
            result = False
        return result

    def __check_md5(self):
        """Checks whether the md5sum of the file has changed
        Returns true on success (md5sum hasn't changed), otherwise false
        """

        tmpsum = md5sum(self.__ossim_setup_file)
        if tmpsum != self.__ossim_setup_md5:
            return False
        return True

    def __get_variable_value(self, section, option):
        """Returns the variable value.
        @param section: section
        @param option: option
        """
        if not self.__avconfig_setup:
            self.__load_config()
        if self.__avconfig_loaded_ok:
            return self.__avconfig_setup.get_option(section, option)
        return None

    def __should_validate_database_section(self):
        """Check whether the database section should be validated.
         - Whether my profile it's Sensor -> validate Sensor, Framework, Server
         - Whether my profile it's Server -> validate Database, Framework, Server
         - Whether my profile it's Framework -> validate Database, Framework, Server
         - Whether my profile it's Database -> validate Database, Framework, Server
         
        """
        if self.__is_profile_only_sensor():
            return False
        return True

    def __should_validate_sensor_section(self):
        """Check whether the sensor section should be validated.
         - Whether my profile it's Sensor -> validate Sensor, Framework, Server
         - Whether my profile it's Server -> validate Database, Framework, Server
         - Whether my profile it's Framework -> validate Database, Framework, Server
         - Whether my profile it's Database -> validate Database, Framework, Server
        """
        profiles = self.get_general_profile_list()
        if self.PROFILE_NAME_SENSOR in profiles:
            return True
        return False

    def __should_validate_server_section(self):
        """Check whether the server section should be validated.
         - Whether my profile it's Sensor -> validate Sensor, Framework, Server
         - Whether my profile it's Server -> validate Database, Framework, Server
         - Whether my profile it's Framework -> validate Database, Framework, Server
         - Whether my profile it's Database -> validate Database, Framework, Server
        """
        return True

    def __should_validate_framework_section(self):
        """Check whether the framework section should be validated.
         - Whether my profile it's Sensor -> validate Sensor, Framework, Server
         - Whether my profile it's Server -> validate Database, Framework, Server
         - Whether my profile it's Framework -> validate Database, Framework, Server
         - Whether my profile it's Database -> validate Database, Framework, Server
        """
        return True

    def __is_profile_only_sensor(self):
        """Check whether the framework section should be validated.
         - Whether my profile it's Sensor -> validate Sensor, Framework, Server
         - Whether my profile it's Server -> validate Database, Framework, Server
         - Whether my profile it's Framework -> validate Database, Framework, Server
         - Whether my profile it's Database -> validate Database, Framework, Server
        """
        profiles = self.get_general_profile_list()
        if len(profiles) == 1 and self.PROFILE_NAME_SENSOR in profiles:
            return True
        return False

    def __is_profile_all_in_one(self):
        """Check whether the framework section should be validated.
         - Whether my profile it's Sensor -> validate Sensor, Framework, Server
         - Whether my profile it's Server -> validate Database, Framework, Server
         - Whether my profile it's Framework -> validate Database, Framework, Server
         - Whether my profile it's Database -> validate Database, Framework, Server
        """
        profiles = self.get_general_profile_list()
        if self.PROFILE_NAME_SENSOR in profiles and self.PROFILE_NAME_DATABASE in profiles \
                and self.PROFILE_NAME_FRAMEWORK in profiles and self.PROFILE_NAME_SERVER:
            return True
        return False

    @staticmethod
    def __get_list_value(value):
        """Returns the list value in the correct format.
        @param value: list of comma separated values.
        @return a string of comman separated values (using whitespaces after each comma)
        """
        value = value.replace(' ', '')
        data = value.split(',')
        return_string = ', '.join(data)
        return return_string

    ###############################################################################
    #           ACCESSORS
    # Nomenclature used for both get and set methods:
    # get_<section>_<variable>
    # set_<section>_<variable>
    # When section is None -> use section=general
    ###############################################################################

    def refresh(func):
        """ Decorator for get methods
        Force refresh the configuration from file if the parameter refresh=True
        and the file has changed in the filesystem.
        """

        def refresh_wrapper(self, *args, **kwargs):
            if 'refresh' in kwargs:
                if kwargs['refresh'] is True:
                    if not self.__check_md5():
                        self.__load_config()
                del kwargs['refresh']
            return func(self, *args, **kwargs)

        return refresh_wrapper

    @refresh
    def get_general_admin_dns(self):
        """Returns the 'admin_dns' field
        """
        return self.__get_variable_value(self.NO_SECTION_NAME, self.NO_SECTION_NAME_ADMIN_DNS)

    @refresh
    def get_general_admin_gateway(self):
        """Returns the 'admin_gateway' field
        """
        return self.__get_variable_value(self.NO_SECTION_NAME, self.NO_SECTION_NAME_ADMIN_GATEWAY)

    @refresh
    def get_general_admin_ip(self):
        """Returns the 'admin_ip' field
        """
        return self.__get_variable_value(self.NO_SECTION_NAME, self.NO_SECTION_NAME_ADMIN_IP)

    @refresh
    def get_general_admin_netmask(self):
        """Returns the 'admin_netmask' field
        """
        return self.__get_variable_value(self.NO_SECTION_NAME, self.NO_SECTION_NAME_ADMIN_NETMASK)

    @refresh
    def get_general_domain(self):
        """Returns the 'domain' field
        """
        return self.__get_variable_value(self.NO_SECTION_NAME, self.NO_SECTION_NAME_DOMAIN)

    @refresh
    def get_general_email_notify(self):
        """Returns the 'email_notify' field
        """
        return self.__get_variable_value(self.NO_SECTION_NAME, self.NO_SECTION_NAME_EMAIN_NOTIFY)

    @refresh
    def get_max_retries(self):
        result = subprocess.check_output("redis-cli get max_retries", shell=True)
        try:
            return int(result) or '0\n'
        except ValueError:
            return 10

    @refresh
    def get_general_hostname(self):
        """Returns the 'hostname' field
        """
        return self.__get_variable_value(self.NO_SECTION_NAME, self.NO_SECTION_NAME_HOSTNAME)

    @refresh
    def get_general_interface(self):
        """Returns the 'interface' field
        """
        return self.__get_variable_value(self.NO_SECTION_NAME, self.NO_SECTION_NAME_INTERFACE)

    @refresh
    def get_general_mailserver_relay(self):
        """Returns the 'mailserver_relay' field
        """
        return self.__get_variable_value(self.NO_SECTION_NAME, self.NO_SECTION_NAME_MAILSERVER_RELAY)

    @refresh
    def get_general_mailserver_relay_passwd(self):
        """Returns the 'mailserver_relay_passwd' field
        """
        return self.__get_variable_value(self.NO_SECTION_NAME, self.NO_SECTION_NAME_MAILSERVER_RELAY_PASSWD)

    @refresh
    def get_general_mailserver_relay_port(self):
        """Returns the 'mailserver_relay_port' field
        """
        return self.__get_variable_value(self.NO_SECTION_NAME, self.NO_SECTION_NAME_MAILSERVER_RELAY_PORT)

    @refresh
    def get_general_mailserver_relay_user(self):
        """Returns the 'mailserver_relay_user' field
        """
        return self.__get_variable_value(self.NO_SECTION_NAME, self.NO_SECTION_NAME_MAILSERVER_RELAY_USER)

    @refresh
    def get_general_ntp_server(self):
        """Returns the 'ntp_server' field
        """
        return self.__get_variable_value(self.NO_SECTION_NAME, self.NO_SECTION_NAME_NTP_SERVER)

    @refresh
    def get_general_profile(self):
        """Returns the 'profile' field
        """
        return self.__get_variable_value(self.NO_SECTION_NAME, self.NO_SECTION_NAME_PROFILE)

    @refresh
    def get_general_profile_list(self):
        """Returns the 'profile' field
        """
        profiles = self.__get_variable_value(self.NO_SECTION_NAME, self.NO_SECTION_NAME_PROFILE)
        if profiles:
            profiles = profiles.replace(' ', '')
            profiles = profiles.split(',')
        return profiles

    @refresh
    def get_database_db_ip(self):
        """Returns the '[database]->db_ip' field
        """
        return self.__get_variable_value(self.DATABASE_SECTION_NAME, self.SECTION_DATABASE_IP)

    @refresh
    def get_database_pass(self):
        """Returns the '[database]->pass' field
        """
        return self.__get_variable_value(self.DATABASE_SECTION_NAME, self.SECTION_DATABASE_PASSWORD)

    @refresh
    def get_database_user(self):
        """Returns the '[database]->user' field
        """
        return self.__get_variable_value(self.DATABASE_SECTION_NAME, self.SECTION_DATABASE_USER)

    @refresh
    def get_expert_profile(self):
        """Returns the '[expert]->profile' field
        """
        return self.__get_variable_value(self.EXPERT_SECTION_NAME, self.NO_SECTION_NAME_PROFILE)

    @refresh
    def get_firewall_active(self):
        """Returns the '[firewall]->active' field
        """
        return self.__get_variable_value(self.FIREWALL_SECTION_NAME, self.SECTION_FIREWALL_ACTIVE)

    @refresh
    def get_framework_framework_https_cert(self):
        """Returns the '[framework]->framework_https_cert' field
        """
        return self.__get_variable_value(self.FRAMEWORK_SECTION_NAME, self.SECTION_FRAMEWORK_HTTPS_CERT)

    @refresh
    def get_framework_framework_https_key(self):
        """Returns the '[framework]->framework_https_key' field
        """
        return self.__get_variable_value(self.FRAMEWORK_SECTION_NAME, self.SECTION_FRAMEWORK_HTTPS_KEY)

    @refresh
    def get_framework_framework_ip(self):
        """Returns the '[framework]->framework_ip' field
        """
        return self.__get_variable_value(self.FRAMEWORK_SECTION_NAME, self.SECTION_FRAMEWORK_IP)

    @refresh
    def get_sensor_detectors(self):
        """Returns the '[sensor]->detectors' field
        NOTE: Returns a list of elements.
        """
        return self.__get_variable_value(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_DETECTORS)

    @refresh
    def get_sensor_detectors_list(self):
        """Returns the '[sensor]->detectors' field
        NOTE: Returns a list of elements.
        """
        data = self.__get_variable_value(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_DETECTORS)
        if data:
            data = data.replace(' ', '')
            data = data.split(',')
        return data

    @refresh
    def get_sensor_ids_rules_flow_control(self):
        """Returns the '[sensor]->ids_rules_flow_control' field
        """
        return self.__get_variable_value(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_IDS_RULES_FLOW_CONTROL)

    @refresh
    def get_sensor_interfaces(self):
        """Returns the '[sensor]->interfaces' field
        """
        return self.__get_variable_value(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_INTERFACES)

    @refresh
    def get_sensor_interfaces_list(self):
        """Returns the '[sensor]->interfaces' field
        """
        data = self.__get_variable_value(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_INTERFACES)
        if data:
            data = data.replace(' ', '')
            data = data.split(',')
        return data

    @refresh
    def get_sensor_ip(self):
        """Returns the '[sensor]->ip' field
        """
        return self.__get_variable_value(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_IP)

    @refresh
    def get_sensor_monitors(self):
        """Returns the '[sensor]->monitors' field
        NOTE: Returns a list of elements.
        """
        return self.__get_variable_value(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_MONITORS)

    @refresh
    def get_sensor_monitors_list(self):
        """Returns the '[sensor]->monitors' field
        NOTE: Returns a list of elements.
        """
        data = self.__get_variable_value(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_MONITORS)
        if data:
            data = data.replace(' ', '')
            data = data.split(',')
        return data

    @refresh
    def get_sensor_mservers(self):
        """Returns the '[sensor]->mservers' field
        NOTE: Returns a list of elements.
        """
        return self.__get_variable_value(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_MSERVER)

    @refresh
    def get_sensor_name(self):
        """Returns the '[sensor]->name' field
        """
        return self.__get_variable_value(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_NAME)

    @refresh
    def get_sensor_netflow(self):
        """Returns the '[sensor]->netflow' field
        """
        return self.__get_variable_value(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_NETFLOW)

    @refresh
    def get_sensor_netflow_remote_collector_port(self):
        """Returns the '[sensor]->netflow_remote_collector_port' field
        """
        return self.__get_variable_value(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_NETFLOW_REMOTE_COLLECTOR_PORT)

    @refresh
    def get_sensor_networks(self):
        """Returns the '[sensor]->networks' field
        NOTE: Returns a list of elements.
        """
        return self.__get_variable_value(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_NETWORKS)

    @refresh
    def get_sensor_networks_list(self):
        """Returns the '[sensor]->networks' field
        NOTE: Returns a list of elements.
        """
        data = self.__get_variable_value(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_NETWORKS)
        if data:
            data = data.replace(' ', '')
            data = data.split(',')
        return data

    @refresh
    def get_sensor_pci_express(self):
        """Returns the '[sensor]->pci_express' field
        """
        return self.__get_variable_value(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_PCI_EXPRESS)

    @refresh
    def get_sensor_tzone(self):
        """Returns the '[sensor]->tzone' field
        """
        return self.__get_variable_value(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_TZONE)

    @refresh
    def get_sensor_ctx(self):
        """Return the '[sensor]->sensor_ctx' field
        """
        return self.__get_variable_value(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_CTX)

    @refresh
    def get_sensor_asec(self):
        """Returns the '[sensor]->asec' field
        """
        return self.__get_variable_value(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_ASEC)

    @refresh
    def get_server_alienvault_ip_reputation(self):
        """Returns the '[server]->alienvault_ip_reputation' field
        """
        return self.__get_variable_value(self.SERVER_SECTION_NAME, self.SECTION_SERVER_ALIENVAULT_IP_REPUTATION)

    @refresh
    def get_server_server_ip(self):
        """Returns the '[server]->server_ip' field
        """
        return self.__get_variable_value(self.SERVER_SECTION_NAME, self.SECTION_SERVER_IP)

    @refresh
    def get_server_server_plugins(self):
        """Returns the '[server]->server_plugins' field
        """
        return self.__get_variable_value(self.SERVER_SECTION_NAME, self.SECTION_SERVER_PLUGINS)

    @refresh
    def get_server_server_plugins_list(self):
        """Returns the '[server]->server_plugins' field
        """
        data = self.__get_variable_value(self.SERVER_SECTION_NAME, self.SECTION_SERVER_PLUGINS)
        if data:
            data = data.replace(' ', '')
            data = data.split(',')
        return data

    @refresh
    def get_server_server_pro(self):
        """Returns the '[server]->server_pro' field
        """
        return self.__get_variable_value(self.SERVER_SECTION_NAME, self.SECTION_SERVER_PRO)

    @refresh
    def get_snmp_comunity(self):
        """Returns the '[snmp]->community' field
        NOTE: The name has a typo (it should be community) but
        we use the notation set_section_option. And the option is wrong typed in the file.
        """
        return self.__get_variable_value(self.SNMP_SECTION_NAME, self.SECTION_SNMP_COMMUNITY)

    @refresh
    def get_snmp_snmp_comunity(self):
        """Returns the '[snmp]->snmp_comunity' field
        """
        return self.__get_variable_value(self.SNMP_SECTION_NAME, self.SECTION_SNMP_SNMP_COMMUNITY)

    @refresh
    def get_snmp_snmpd(self):
        """Returns the '[snmp]->snmpd' field
        """
        return self.__get_variable_value(self.SNMP_SECTION_NAME, self.SECTION_SNMP_SNMPD)

    @refresh
    def get_snmp_snmptrap(self):
        """Returns the '[snmp]->snmptrap' field
        """
        return self.__get_variable_value(self.SNMP_SECTION_NAME, self.SECTION_SNMP_SNMPTRAP)

    @refresh
    def get_update_update_proxy(self):
        """Returns the '[update]->update_proxy' field
        """
        return self.__get_variable_value(self.UPDATE_SECTION_NAME, self.SECTION_UPDATE_PROXY)

    @refresh
    def get_update_update_proxy_dns(self):
        """Returns the '[update]->update_proxy_dns' field
        """
        return self.__get_variable_value(self.UPDATE_SECTION_NAME, self.SECTION_UPDATE_PROXY_DNS)

    @refresh
    def get_update_update_proxy_pass(self):
        """Returns the '[update]->update_proxy_pass' field
        """
        return self.__get_variable_value(self.UPDATE_SECTION_NAME, self.SECTION_UPDATE_PROXY_PASSWORD)

    @refresh
    def get_update_update_proxy_port(self):
        """Returns the '[update]->update_proxy_port' field
        """
        return self.__get_variable_value(self.UPDATE_SECTION_NAME, self.SECTION_UPDATE_PROXY_PORT)

    @refresh
    def get_update_update_proxy_user(self):
        """Returns the '[update]->update_proxy_user' field
        """
        return self.__get_variable_value(self.UPDATE_SECTION_NAME, self.SECTION_UPDATE_PROXY_USER)

    @refresh
    def get_ha_ha_autofailback(self):
        """Returns the '[ha]->ha_autofailback' field"""
        return self.__get_variable_value(self.HA_SECTION_NAME, self.SECTION_HA_HA_AUTOFAILBACK)

    @refresh
    def get_ha_ha_deadtime(self):
        """Returns the '[ha]->ha_deadtime' field"""
        return self.__get_variable_value(self.HA_SECTION_NAME, self.SECTION_HA_HA_DEADTIME)

    @refresh
    def get_ha_ha_device(self):
        """Returns the '[ha]->ha_device' field"""
        return self.__get_variable_value(self.HA_SECTION_NAME, self.SECTION_HA_HA_DEVICE)

    @refresh
    def get_ha_ha_heartbeat_comm(self):
        """Returns the '[ha]->ha_heartbeat_comm' field"""
        return self.__get_variable_value(self.HA_SECTION_NAME, self.SECTION_HA_HA_HEARTBEAT_COMM)

    @refresh
    def get_ha_ha_heartbeat_start(self):
        """Returns the '[ha]->ha_heartbeat_start' field"""
        return self.__get_variable_value(self.HA_SECTION_NAME, self.SECTION_HA_HA_HEARTBEAT_START)

    @refresh
    def get_ha_ha_keepalive(self):
        """Returns the '[ha]->ha_heartbeat_start' field"""
        return self.__get_variable_value(self.HA_SECTION_NAME, self.SECTION_HA_HA_KEEPALIVE)

    @refresh
    def get_ha_ha_local_node_ip(self):
        """Returns the '[ha]->ha_heartbeat_start' field"""
        return self.__get_variable_value(self.HA_SECTION_NAME, self.SECTION_HA_HA_LOCAL_NODE_IP)

    @refresh
    def get_ha_ha_log(self):
        """Returns the '[ha]->ha_log' field"""
        return self.__get_variable_value(self.HA_SECTION_NAME, self.SECTION_HA_HA_LOG)

    @refresh
    def get_ha_ha_other_node_ip(self):
        """Returns the '[ha]->ha_other_node_ip' field"""
        return self.__get_variable_value(self.HA_SECTION_NAME, self.SECTION_HA_HA_OTHER_NODE_IP)

    @refresh
    def get_ha_ha_other_node_name(self):
        """Returns the '[ha]->ha_other_node_name' field"""
        return self.__get_variable_value(self.HA_SECTION_NAME, self.SECTION_HA_HA_OTHER_NODE_NAME)

    @refresh
    def get_ha_ha_password(self):
        """Returns the '[ha]->ha_password' field"""
        return self.__get_variable_value(self.HA_SECTION_NAME, self.SECTION_HA_HA_PASSWORD)

    @refresh
    def get_ha_ha_ping_node(self):
        """Returns the '[ha]->ha_ping_node' field"""
        return self.__get_variable_value(self.HA_SECTION_NAME, self.SECTION_HA_HA_PING_NODE)

    @refresh
    def get_ha_ha_role(self):
        """Returns the '[ha]->ha_role' field"""
        return self.__get_variable_value(self.HA_SECTION_NAME, self.SECTION_HA_HA_ROLE)

    @refresh
    def get_ha_ha_virtual_ip(self):
        """Returns the '[ha]->ha_virtual_ip' field"""
        return self.__get_variable_value(self.HA_SECTION_NAME, self.SECTION_HA_HA_VIRTUAL_IP)

    def get_dirty(self):
        """Returns whehter the configuration has pending changes.
        """
        return self.__file_dirty

    def get_dirty_tuple(self):
        """Returns whehter the configuration has pending changes.
        """
        if self.__file_dirty:
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_IS_DIRTY, "")
        return AVConfigParserErrors.ALL_OK

    def get_modified_values(self):
        """Returns a list of modified values
        """
        return self.__modified_values

    def get_modified_values_string(self):
        """Returns a humman readable string for the modified values.
        """
        st = ""
        for section, options in self.__modified_values.iteritems():
            st += "[%s]\n" % section
            for optionname, optionvalue in options.iteritems():
                st += "%s\n" % optionname

        # Add the non ossim_setup.conf related values.
        st += self.__sysconfig.get_pending_str()
        return st

    def get_modified_values_string_full(self):
        """Return both modified fields and their values
        """
        st = ""
        for section, options in self.__modified_values.iteritems():
            st += "[%s]\n" % section
            for optionname, optionvalue in options.iteritems():
                st += "%s: %s\n" % (optionname, optionvalue)
        return st

    def get_error_list(self):
        """Returns the list of errors
        """
        return self.__errors

    @staticmethod
    def get_disabled_labels():
        """Returns a list with the negatie boolean values
        """
        boolean_negatives = ["on", "0", "false", "off", "no", "disabled", "unconfigured"]
        return boolean_negatives

    ####################################
    # Non ossim_setup.conf related stuff
    ####################################

    ### /etc/network/interfaces configuration

    def get_net_iface_config_all(self):
        """
        Return a dict with all network interface configurations, in the form {'iface name': 'configuration parameters'}
        """
        return self.__sysconfig.get_net_iface_config_all()

    def get_net_iface_config(self, iface):
        """
        Return a dict with the network interface name 'iface' as key, and its configuration attributes as values.
        """
        return self.__sysconfig.get_net_iface_config(iface)

    def get_net_iface_name(self):
        """
        Return a random default value for an interface name :)
        """
        return self.__sysconfig.get_net_iface_config_all().keys()[0]

    def get_net_iface_ip(self, modifier='eth0'):
        """
        Return the IP address of a interface.
        """
        ip = self.__sysconfig.get_net_iface_config(modifier)[modifier].get('address')
        if ip == 'TBD':
            ip = ''
        return ip

    def get_net_iface_netmask(self, modifier='eth0'):
        """
        Return the network mask of a interface.
        """
        netmask = self.__sysconfig.get_net_iface_config(modifier)[modifier].get('netmask')
        if netmask == 'TBD':
            netmask = ''
        return netmask

    def get_net_iface_gateway(self, modifier='eth0'):
        """
        Return the network mask of a interface.
        """
        gateway = self.__sysconfig.get_net_iface_config(modifier)[modifier].get('gateway')
        if gateway == 'TBD':
            gateway = ''
        return gateway

    ### /etc/hosts configuration

    def get_hosts_config_all(self):
        """
        Return a dict with all entries in /etc/hosts, in the form {'entry': 'configuration parameters'}

        """
        return self.__sysconfig.get_hosts_config_all()

    def get_hosts_config(self, entry):
        """
        Return a dict with entry 'entry' in /etc/hosts, in the form {'entry': 'configuration parameters'}
        """
        return self.__sysconfig.get_hosts_config(entry)

    def get_hosts_config_ipaddr(self, entry):
        """
        Return the ip address for host entry 'entry'
        """
        return self.__sysconfig.get_hosts_config().keys()[0]

    def get_hosts_config_canonical(self, entry):
        """
        Return the canonical name for host entry 'entry'
        """
        return self.__sysconfig.get_hosts_config().keys()[1]

    def get_hosts_config_aliases(self, entry):
        """
        Return the aliases for host entry 'entry'
        """
        return self.__sysconfig.get_hosts_config().keys()[2]

    ### Registered systems configuration

    def get_registered_system(self):
        """
        Return the first registered system in our database.
        """
        proc = subprocess.Popen(
            ['/usr/share/python/alienvault-api-core/bin/alienvault/virtual_env_run', 'get_registered_systems'],
            stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        out, err = proc.communicate()
        data = json.loads(out)
        registered_systems = [(str(data[uuid]['admin_ip']), str(data[uuid]['hostname'])) for uuid in data]

        return registered_systems[0]

    def get_registered_systems_without_vpn(self):
        """Returns the list of systems without vpn"""
        proc = subprocess.Popen(
            ['/usr/share/python/alienvault-api-core/bin/alienvault/virtual_env_run', 'get_registered_systems', '-n'],
            stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        out, err = proc.communicate()
        data = json.loads(out)
        registered_systems = [(str(data[uuid]['admin_ip']), str(data[uuid]['hostname'])) for uuid in data]

        return registered_systems

    ### /etc/alienvault/network/vpn.conf

    def get_avvpn_config_all(self):
        """
        Return a dict with all VPN configurations, in the form {'iface name': 'configuration parameters'}
        """
        return self.__sysconfig.get_avvpn_config_all()

    def get_avvpn_config(self, iface):
        """
        Return a dict with the VPN network interface name 'iface' as key, and its configuration attributes as values.
        """
        return self.__sysconfig.get_avvpn_config(iface=iface)

    def get_avvpn_config_role(self, modifier='tun0'):
        """
        Return the role in a VPN configuration (either 'client' or 'server')
        """
        return self.__sysconfig.get_avvpn_config(modifier).get(modifier).get('role', '')

    def get_avvpn_config_config_file(self, modifier='tun0'):
        """
        Return the configuration file path for a VPN.
        """
        return self.__sysconfig.get_avvpn_config(modifier).get(modifier).get('config_file', '')

    def get_avvpn_config_network(self, modifier='tun0'):
        """
        Return the network of a VPN.
        """
        return self.__sysconfig.get_avvpn_config(modifier).get(modifier).get('network', '')

    def get_avvpn_config_netmask(self, modifier='tun0'):
        """
        Return the netmask in a VPN configuration.
        """
        return self.__sysconfig.get_avvpn_config(modifier).get(modifier).get('netmask', '')

    def get_avvpn_config_port(self, modifier='tun0'):
        """
        Return the port of a VPN configuration.
        """
        return self.__sysconfig.get_avvpn_config(modifier).get(modifier).get('port', '')

    def get_avvpn_config_ca(self, modifier='tun0'):
        """
        Return the CA file path of a VPN.
        """
        return self.__sysconfig.get_avvpn_config(modifier).get(modifier).get('ca', '')

    def get_avvpn_config_cert(self, modifier='tun0'):
        """
        Return the certificate file path of a VPN.
        """
        return self.__sysconfig.get_avvpn_config(modifier).get(modifier).get('cert', '')

    def get_avvpn_config_key(self, modifier='tun0'):
        """
        Return the key file path of a VPN.
        """
        return self.__sysconfig.get_avvpn_config(modifier).get(modifier).get('key', '')

    def get_avvpn_config_dh(self, modifier='tun0'):
        """
        Return the DH file path of a VPN.
        """
        return self.__sysconfig.get_avvpn_config(modifier).get(modifier).get('dh', '')

    def get_avvpn_config_enabled(self, modifier='tun0'):
        """
        Return if the VPN is enabled or not.
        """
        return self.__sysconfig.get_avvpn_config(modifier).get(modifier).get('enabled', 'no')

    ###############################################################################
    #           MUTATORS
    ###############################################################################

    def validate_config_file(self):
        """Check if all the file fields are correct!
        """
        logger.info("validating ossim-setup file...")
        try:
            self.check_general_admin_dns(self.get_general_admin_dns())
            self.check_general_admin_gateway(self.get_general_admin_gateway())
            self.check_general_admin_ip(self.get_general_admin_ip())
            self.check_general_admin_netmask(self.get_general_admin_netmask())
            self.check_general_email_notify(self.get_general_email_notify())
            self.check_general_hostname(self.get_general_hostname())
            self.check_general_interface(self.get_general_interface())
            self.check_general_mailserver_relay(self.get_general_mailserver_relay())
            self.check_general_mailserver_relay_passwd(self.get_general_mailserver_relay_passwd())
            self.check_general_mailserver_relay_port(self.get_general_mailserver_relay_port())
            self.check_general_mailserver_relay_user(self.get_general_mailserver_relay_user())
            self.check_general_ntp_server(self.get_general_ntp_server())

            if self.__should_validate_database_section():
                self.check_database_db_ip(self.get_database_db_ip())
                self.check_database_pass(self.get_database_pass())
                self.check_database_user(self.get_database_user())

            self.check_firewall_active(self.get_firewall_active())

            if self.__should_validate_framework_section():
                self.check_framework_https_cert(self.get_framework_framework_https_cert())
                self.check_framework_https_key(self.get_framework_framework_https_key())
                self.check_framework_ip(self.get_framework_framework_ip())

            if self.__should_validate_sensor_section():
                self.check_sensor_detectors(self.get_sensor_detectors())
                self.check_sensor_ids_rules_flow_control(self.get_sensor_ids_rules_flow_control())
                self.check_sensor_interfaces(self.get_sensor_interfaces())
                self.check_sensor_ip(self.get_sensor_ip())
                self.check_sensor_monitors(self.get_sensor_monitors())
                self.check_sensor_mserver(self.get_sensor_mservers())
                self.check_sensor_name(self.get_sensor_name())
                self.check_sensor_netflow(self.get_sensor_netflow())
                self.check_sensor_netflow_remote_collector_port(self.get_sensor_netflow_remote_collector_port())
                self.check_sensor_networks(self.get_sensor_networks())
                self.check_sensor_pci_express(self.get_sensor_pci_express())
                self.check_sensor_tzone(self.get_sensor_tzone())
                self.check_sensor_ctx(self.get_sensor_ctx())
                self.check_sensor_asec(self.get_sensor_asec())

            if self.__should_validate_server_section():
                self.check_server_alienvault_ip_reputation(self.get_server_alienvault_ip_reputation())
                self.check_server_server_plugins(self.get_server_server_plugins())
                self.check_server_ip(self.get_server_server_ip())
                self.check_server_pro(self.get_server_server_pro())

            self.check_snmp_community(self.get_snmp_comunity())
            self.check_snmp_snmp_community(self.get_snmp_snmp_comunity())
            self.check_snmp_snmpd(self.get_snmp_snmpd())
            self.check_snmp_snmptrap(self.get_snmp_snmptrap())
            self.check_update_update_proxy(self.get_update_update_proxy())
            self.check_update_update_proxy_dns(self.get_update_update_proxy_dns())
            self.check_update_update_proxy_pass(self.get_update_update_proxy_pass())
            self.check_update_update_proxy_port(self.get_update_update_proxy_port())
            self.check_update_update_proxy_user(self.get_update_update_proxy_user())
        except Exception, e:
            traceback.print_exc()
            print str(e)

    def check_general_admin_dns(self, value):
        """Check whether the admin dns is valid
        """
        result = AVConfigParserErrors.ALL_OK
        if value == '':
            return result
        if value is not None:
            for ip in value.split(','):
                if not is_ipv4(ip):
                    logger.warning("Invalid admin_dns ... %s" % ip)
                    result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.VALUE_NOT_VALID_IP, ip)
                    self.__add_error(self.NO_SECTION_NAME, self.NO_SECTION_NAME_ADMIN_DNS, result)
        else:
            logger.warning("Invalid admin_dns ... %s" % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.VALUE_NOT_VALID_IP, value)
            self.__add_error(self.NO_SECTION_NAME, self.NO_SECTION_NAME_ADMIN_DNS, result)

        return result

    def check_general_admin_gateway(self, value):
        """Check whether the admin gateway is valid
        """
        result = AVConfigParserErrors.ALL_OK
        if not is_ipv4(value):
            logger.warning("Invalid admin_gateway ... %s" % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.VALUE_NOT_VALID_IP, value)
            self.__add_error(self.NO_SECTION_NAME, self.NO_SECTION_NAME_ADMIN_GATEWAY, result)
        return result

    def check_general_admin_ip(self, value):
        """Check whether the admin ip is valid
        """
        result = AVConfigParserErrors.ALL_OK
        if not is_ipv4(value):
            logger.warning("Invalid admin_ip ... %s" % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.VALUE_NOT_VALID_IP, value)
            self.__add_error(self.NO_SECTION_NAME, self.NO_SECTION_NAME_ADMIN_IP, result)
        return result

    def check_general_admin_netmask(self, value):
        """Check whether the admin ip is valid
        """
        result = AVConfigParserErrors.ALL_OK
        if not is_net_mask(value):
            logger.warning("Invalid admin_netmask ... %s" % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.INVALID_NETMASK, value)
            self.__add_error(self.NO_SECTION_NAME, self.NO_SECTION_NAME_ADMIN_NETMASK, result)
        return result

    def check_general_domain(self, value):
        """Check whether the domain is valid
        ftp://ftp.rfc-editor.org/in-notes/rfc1034.txt
        ASCII characters
        """
        result = AVConfigParserErrors.ALL_OK
        if not is_valid_domain(value):
            logger.warning("Invalid domain ... %s" % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.CANT_SET_DOMAIN_INVALID_VALUE, value)
            self.__add_error(self.NO_SECTION_NAME, self.NO_SECTION_NAME_DOMAIN, result)
        return result

    def check_general_email_notify(self, value):
        """Check whether the email_notify is valid
        email_notify: it could be empty
        """
        result = AVConfigParserErrors.ALL_OK
        if not is_valid_email(value) and value != "":
            logger.warning("Invalid email notify ... %s" % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.CANT_SET_EMAIL_NOTIFY_INVALID_VALUE, value)
            self.__add_error(self.NO_SECTION_NAME, self.NO_SECTION_NAME_EMAIN_NOTIFY, result)
        return result

    def check_max_retries(self, value):
        """ Check whether the max_retries is valid
            value should be int
        """
        try:
            int(value)
        except ValueError:
            logger.warning("Invalid max_retries ... %s" % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.CANT_SET_MAX_RETRIES_INVALID_VALUE, value)
            self.__add_error(self.NO_SECTION_NAME, self.NO_SECTION_NAME_MAX_RETRIES, result)
        else:
            result = AVConfigParserErrors.ALL_OK
        return result

    def check_general_hostname(self, value):
        """Check whether the hostname is valid
        Do not allow ipv4 as hostname
        """
        result = AVConfigParserErrors.ALL_OK
        if not is_valid_hostname_rfc1123(value) or is_ipv4(value):
            logger.warning("Invalid hostname ... %s" % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.CANT_SET_HOSTNAME_INVALID_VALUE, value)
            self.__add_error(self.NO_SECTION_NAME, self.NO_SECTION_NAME_HOSTNAME, result)
        return result

    def check_general_interface(self, value):
        """Check whether the interface is valid
        It should be a valid system network interface except the lo
        """
        result = AVConfigParserErrors.ALL_OK
        if value not in self.__interfaces:
            logger.warning("Invalid interface ... %s" % value)
            allowed = ','.join(self.__interfaces)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.INVALID_ADMIN_INTERFACE,
                                                        " %s, allowed[%s]" % (value, allowed))
            self.__add_error(self.NO_SECTION_NAME, self.NO_SECTION_NAME_INTERFACE, result)
        return result

    def check_general_mailserver_relay(self, value):
        """Check whether the mailserver_relay is valid
        #allowed:ip,dns, hostname
        """
        result = AVConfigParserErrors.ALL_OK
        is_default = self.__is_default(self.NO_SECTION_NAME, self.NO_SECTION_NAME_MAILSERVER_RELAY, value)
        if not is_default and not is_valid_ip_address(value) and not is_valid_domain(
                value) and not is_valid_dns_hostname(value):
            logger.warning("Invalid mailserver_relay ... %s" % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.INVALID_EMAIL_RELAY, )
            self.__add_error(self.NO_SECTION_NAME, self.NO_SECTION_NAME_MAILSERVER_RELAY, result)
        return result

    def check_general_mailserver_relay_passwd(self, value):
        """Check whether the mailserver_relay is valid
        """
        result = AVConfigParserErrors.ALL_OK
        is_default = self.__is_default(self.NO_SECTION_NAME, self.NO_SECTION_NAME_MAILSERVER_RELAY_PASSWD, value)
        if not is_default and not is_allowed_password(value, minsize=1, maxsize=999):
            logger.warning("Invalid mailserver_relay_passwd ... ")
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.INVALID_EMAIL_RELAY_PASS,
                                                        "Allowed values ASCII characters ")
            self.__add_error(self.NO_SECTION_NAME, self.NO_SECTION_NAME_MAILSERVER_RELAY_PASSWD, result)
        return result

    def check_general_mailserver_relay_port(self, value):
        """Check whether the mailserver_relay_port is valid
        """
        result = AVConfigParserErrors.ALL_OK
        is_default = self.__is_default(self.NO_SECTION_NAME, self.NO_SECTION_NAME_MAILSERVER_RELAY_PORT, value)
        if not is_default and not is_valid_port(value):
            logger.warning("Invalid mailserver_relay_port ... %s" % value)
            result = AVConfigParserErrors.get_error_msg(
                AVConfigParserErrors.CANT_SET_MAILSERVERRELAY_PORT_INVALID_VALUE, value)
            self.__add_error(self.NO_SECTION_NAME, self.NO_SECTION_NAME_MAILSERVER_RELAY_PORT, result)
        return result

    def check_general_mailserver_relay_user(self, value):
        """Check whether the mailserver_relay_user is valid
        """
        result = AVConfigParserErrors.ALL_OK
        is_default = self.__is_default(self.NO_SECTION_NAME, self.NO_SECTION_NAME_MAILSERVER_RELAY_USER, value)
        if not is_default and not is_allowed_username(value, 4, 255):
            logger.warning("Invalid mailserver_relay_user ... %s" % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.INVALID_EMAIL_RELAY_USER,
                                                        "%s Allowed values ASCII characters {4,255}" % value)
            self.__add_error(self.NO_SECTION_NAME, self.NO_SECTION_NAME_MAILSERVER_RELAY_USER, result)
        return result

    def check_general_ntp_server(self, value):
        """Check whether the ntp_server is valid
        """
        result = AVConfigParserErrors.ALL_OK
        default_value = self.get_default_value(self.NO_SECTION_NAME, self.NO_SECTION_NAME_NTP_SERVER)
        if value != default_value and not is_valid_dns_hostname(value) and not is_ipv4(value):
            logger.warning("Invalid ntpserver ... %s" % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.CANT_SET_NTP_SERVER_INVALID_VALUE, value)
            self.__add_error(self.NO_SECTION_NAME, 'ntp_server', result)
        return result

    def check_database_db_ip(self, value):
        """Check whether the db_ip is valid
        """
        result = AVConfigParserErrors.ALL_OK
        if not is_ipv4(value):
            logger.warning("Invalid db_ip ... %s" % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.CANT_SET_DATABASE_IP_INVALID_VALUE, value)
            self.__add_error(self.DATABASE_SECTION_NAME, 'db_ip', result)
        return result

    def check_database_pass(self, value):
        """Check whether the pass is valid
        """
        result = AVConfigParserErrors.ALL_OK
        if not is_allowed_password(value):
            logger.warning("Invalid db_pass ...")
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.INVALID_DATABASE_PASSWORD,
                                                        " Allowed values ASCII characters {8,16}")
            self.__add_error(self.DATABASE_SECTION_NAME, self.SECTION_DATABASE_PASSWORD, result)
        return result

    def check_database_user(self, value):
        """Check whether the user is valid
        """
        result = AVConfigParserErrors.ALL_OK
        if not is_allowed_username(value):
            logger.warning("Invalid db_user ... %s" % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.INVALID_DATABASE_USER,
                                                        "%s Allowed values ASCII characters {4,16}" % value)
            self.__add_error(self.DATABASE_SECTION_NAME, self.SECTION_DATABASE_USER, result)
        return result

    def check_firewall_active(self, value):
        """Check whether the active is valid
        """
        result = AVConfigParserErrors.ALL_OK
        error = False
        try:
            value = value.lower()
            if value not in self.YES_NO_CHOICES:
                error = True
        except AttributeError:  # not a string
            error = True
        if error:
            logger.warning("Invalid firewall active ... %s" % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.INVALID_BOOLEAN_VALUE, value)
            self.__add_error(self.FIREWALL_SECTION_NAME, self.SECTION_FIREWALL_ACTIVE, result)
        return result

    def check_framework_https_cert(self, value):
        """Check whether the framework_https_cert
        this value could be 'default' or a valid certificate file
        """
        result = AVConfigParserErrors.ALL_OK
        is_default = self.__is_default(self.FRAMEWORK_SECTION_NAME, self.SECTION_FRAMEWORK_HTTPS_CERT, value)
        if not value or (not is_default and not os.path.isfile(value)):
            logger.warning("Invalid framework_https_cert ... %s" % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.INVALID_FRAMEWORK_HTTP_CERT, value)
            self.__add_error(self.FRAMEWORK_SECTION_NAME, self.SECTION_FRAMEWORK_HTTPS_CERT, result)
        return result

    def check_framework_https_key(self, value):
        """Check whether the framework_https_key
        this value could be 'default' or a valid certificate file
        """
        result = AVConfigParserErrors.ALL_OK
        is_default = self.__is_default(self.FRAMEWORK_SECTION_NAME, self.SECTION_FRAMEWORK_HTTPS_KEY, value)
        if not value or (not is_default and not os.path.isfile(value)):
            logger.warning("Invalid framework_https_cert ... %s" % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.INVALID_FRAMEWORK_HTTP_KEY, value)
            self.__add_error(self.FRAMEWORK_SECTION_NAME, self.SECTION_FRAMEWORK_HTTPS_KEY, result)
        return result

    def check_framework_ip(self, value):
        """Check whether the framework_ip is valid
        """
        result = AVConfigParserErrors.ALL_OK
        if not is_ipv4(value):
            logger.warning("Invalid framework_ip ... %s" % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.CANT_SET_DATABASE_IP_INVALID_VALUE, value)
            self.__add_error(self.FRAMEWORK_SECTION_NAME, 'framework_ip', result)
        return result

    def check_sensor_detectors(self, value):
        """Check whether the [sensor]-> detectors is valid
        """
        detector_list = get_current_detector_plugin_list()
        result = AVConfigParserErrors.ALL_OK
        if self.PROFILE_NAME_SENSOR not in self.get_general_profile_list():
            logger.warning("Sensor profile not present. Cannot set sensor detectors ... %s" % value)
            return result
        if (not value) or value == "":
            logger.warning("Sensor detectors can't be empty")
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.ALMOST_ONE_DETECTOR_PLUGIN_IS_NEEDED)
            self.__add_error(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_DETECTORS, result)
        elif not isinstance(value, basestring):
            logger.warning("Sensor detectors invalid type: %s -> %s" % (type(value), value))
            result = AVConfigParserErrors.get_error_msg(
                AVConfigParserErrors.DETECTOR_LIST_SHOULD_BE_A_COMMA_SEPARATED_STRING)
            self.__add_error(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_DETECTORS, result)
        else:

            value = value.split(',')
            for plugin in value:
                plugin = plugin.strip()
                if plugin not in detector_list:
                    logger.warning("Sensor detectors %s doesn't exist in sensor folder" % value)
                    result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.DETECTOR_PLUGIN_NOT_FOUND, plugin)
                    self.__add_error(self.SENSOR_SECTION_NAME, 'detectors', result)
                    break
        return result

    def check_sensor_ids_rules_flow_control(self, value):
        """Check whether the [sensor]->ids_rules_flow_control is valid
        It should be a boolean value
        """
        result = AVConfigParserErrors.ALL_OK
        if self.PROFILE_NAME_SENSOR not in self.get_general_profile_list():
            logger.warning("Sensor profile not present. Cannot set sensor ids_rules_flow_control ... %s" % value)
            return result
        if value not in self.YES_NO_CHOICES:
            logger.warning("Invalid value ids_rules_flow_control ... %s" % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.INVALID_BOOLEAN_VALUE, value)
            self.__add_error(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_IDS_RULES_FLOW_CONTROL, result)
        return result

    def check_sensor_interfaces(self, value):
        """Check whether the [sensor]->interfaces is valid
        @param value: a list of interfaces.
        """
        allowed = ','.join(self.__interfaces)
        result = AVConfigParserErrors.ALL_OK
        error = False
        if self.PROFILE_NAME_SENSOR not in self.get_general_profile_list():
            logger.warning("Sensor profile not present. Cannot set sensor ids_rules_flow_control ... %s" % value)
            return result
        if not value or value == "":
            logger.warning("Sensor interfaces can't be empty")
            error = True
        else:
            value = value.replace(' ', '')  # remove whitespaces
            value = value.split(',')
            for iface in value:
                if iface.strip() not in self.__interfaces:
                    logger.warning("Invalid sensor interface. %s not exists in the system interfaces list" % (iface))
                    error = True
                    break
        if error:
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.SENSOR_INTERFACES_INVALID_VALUE,
                                                        "%s, allowed[%s]" % (value, allowed))
            self.__add_error(self.SENSOR_SECTION_NAME, 'interfaces', result)
        return result

    def check_sensor_ip(self, value):
        """Check whether the sensor_ip is valid
        if profile is sensor and ip is empty it's allowed
        """
        result = AVConfigParserErrors.ALL_OK
        if self.PROFILE_NAME_SENSOR not in self.get_general_profile_list():
            logger.warning("Sensor profile not present. Cannot set sensor ids_rules_flow_control ... %s" % value)
            return result
        if not is_ipv4(value) and not (self.is_profile_sensor() and value == ""):
            logger.warning("Invalid sensor ip: %s" % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.INVLAID_SENSOR_IP, value)
            self.__add_error(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_IP, result)
        return result

    def check_sensor_monitors(self, value):
        """Check whether the [sensor]-> detectors is valid
        @param value: A list of monitors.
        """
        result = AVConfigParserErrors.ALL_OK
        if self.PROFILE_NAME_SENSOR not in self.get_general_profile_list():
            logger.warning("Sensor profile not present. Cannot set sensor ids_rules_flow_control ... %s" % value)
            return result
        if value == "":
            return result
        elif not isinstance(value, basestring):
            logger.warning("Sensor monitors invalid type: %s -> %s" % (type(value), value))
            result = AVConfigParserErrors.get_error_msg(
                AVConfigParserErrors.MONITOR_LIST_SHOULD_BE_A_COMMA_SEPARATED_STRING)
            self.__add_error(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_DETECTORS, result)
        else:
            monitors = get_current_monitor_plugin_list_clean()
            value = value.split(',')
            for plugin in value:
                plugin = plugin.strip()
                if plugin not in monitors:
                    logger.warning("Sensor monitor %s doesn't exist in sensor folder" % value)
                    result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.MONITOR_PLUGIN_NOT_FOUND, plugin)
                    self.__add_error(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_MONITORS, result)
                    break
        return result

    def check_sensor_mserver(self, value):
        """Check whether the [sensor]->mservers  is valid
        @param value: A list of mservers.
        Nomenclature:
        SERVER_IPPORT,SEND_EVENTS(True/False),ALLOW_FRMK_DATA(True/False),PRIORITY (0-5),FRMK_IP,FRMK_PORT; another one
        """
        result = AVConfigParserErrors.ALL_OK
        if self.PROFILE_NAME_SENSOR not in self.get_general_profile_list():
            logger.warning("Sensor profile not present. Cannot set sensor ids_rules_flow_control ... %s" % value)
            return result

        if self.__is_default(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_MSERVER, value):
            return result
        error = False
        if not value or value == "":
            logger.warning("Sensor mserver can't be empty")
            error = True
        else:
            server_list = value.split(';')
            for server in server_list:
                if not check_mserver_string(server) and not self.__is_default(self.SENSOR_SECTION_NAME,
                                                                              self.SECTION_SENSOR_MSERVER, value):
                    logger.info("Invalid sensor mserver string %s" % value)
                    error = True
                    break
        if error:
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.INVALID_MSERVER_TUPLE,
                                                        " %s (SERVER_IPPORT,SEND_EVENTS(True/False),ALLOW_FRMK_DATA(True/False),PRIORITY (0-5),FRMK_IP,FRMK_PORT)" % (
                                                            value))
            self.__add_error(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_MSERVER, result)
        return result

    def check_sensor_name(self, value):
        """Check whether the user is valid
        do not allow ip addresses
        """
        result = AVConfigParserErrors.ALL_OK
        if self.PROFILE_NAME_SENSOR not in self.get_general_profile_list():
            logger.warning("Sensor profile not present. Cannot set sensor ids_rules_flow_control ... %s" % value)
            return result
        result = AVConfigParserErrors.ALL_OK
        if is_valid_ip_address(value) or not is_sensor_allowed_name(value):
            logger.error("Invalid sensor name")
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.INVALID_SENSOR_NAME,
                                                        "%s Allowed values ASCII characters {4,16}" % value)
            self.__add_error(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_NAME, result)
        return result

    def check_sensor_netflow(self, value):
        """Check whether the [sensor]->netflow is valid
        """
        result = AVConfigParserErrors.ALL_OK
        if self.PROFILE_NAME_SENSOR not in self.get_general_profile_list():
            logger.warning("Sensor profile not present. Cannot set sensor ids_rules_flow_control ... %s" % value)
            return result
        if value not in self.YES_NO_CHOICES:
            logger.warning("Invalid sensor netflow %s" % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.INVALID_BOOLEAN_VALUE, value)
            self.__add_error(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_NETFLOW, result)
        return result

    def check_sensor_netflow_remote_collector_port(self, value):
        """Check whether the [sensor]->netflow_remote_collector_port is valid"""
        result = AVConfigParserErrors.ALL_OK
        if self.PROFILE_NAME_SENSOR not in self.get_general_profile_list():
            logger.warning("Sensor profile not present. Cannot set sensor ids_rules_flow_control ... %s" % value)
            return result
        if not is_valid_port(value):
            logger.warning("Invaid sensor netflow_remote_collector_port %s" % value)
            result = AVConfigParserErrors.get_error_msg(
                AVConfigParserErrors.CANT_SET_NETFLOW_REMOTE_COLLECTOR_PORT_INVALID_VALUE, value)
            self.__add_error(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_NETFLOW_REMOTE_COLLECTOR_PORT, result)
        return result

    def check_sensor_networks(self, value):
        """Check whether the sensor networks are a valid value"""
        result = AVConfigParserErrors.ALL_OK
        if self.PROFILE_NAME_SENSOR not in self.get_general_profile_list():
            logger.warning("Sensor profile not present. Cannot set sensor ids_rules_flow_control ... %s" % value)
            return result
        if not value or value == "":
            logger.warning("Sensor networks can't be empty")
            return result
        value = value.split(',')
        for net in value:
            if not is_valid_CIDR(net):
                logger.warning("Invalid CIDR: %s" % net)
                result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.INVALID_NET, net)
                self.__add_error(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_NETWORKS, result)
                break
        return result

    def check_sensor_pci_express(self, value):
        """Check whether the [sensor]->pci_express is valid
        TODO: How to validate this value?
        pci_express is a read-only value
        """
        result = AVConfigParserErrors.ALL_OK
        return result

    def check_sensor_tzone(self, value):
        """Check whether the [sensor]->tzone is valid
        TODO: How to validate this value?
        tzone is a read-only value
        """
        result = AVConfigParserErrors.ALL_OK
        return result

    def check_sensor_ctx(self, value):
        """Check whether the [sensor]->sensor_ctx is valid
        """
        result = AVConfigParserErrors.ALL_OK
        if self.PROFILE_NAME_SENSOR not in self.get_general_profile_list():
            logger.warning("Sensor profile not present. Cannot set sensor ctx ... %s" % value)
            return result
        # 11464 Allow empty values on sensor_ctx
        if value == "" or value is None:
            return result
        try:
            UUID(value)
        except ValueError:
            logger.warning("Invalid sensor ctx %s" % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.INVALID_UUID_VALUE, value)
            self.__add_error(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_CTX, result)
        return result

    def check_sensor_asec(self, value):
        """Check whether the [sensor]->asec is valid
        """
        result = AVConfigParserErrors.ALL_OK
        if self.PROFILE_NAME_SENSOR not in self.get_general_profile_list():
            logger.warning("Sensor profile not present. Cannot set sensor asec ... %s" % value)
            return result
        if value not in self.YES_NO_CHOICES:
            logger.warning("Invalid sensor asec %s" % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.INVALID_BOOLEAN_VALUE, value)
            self.__add_error(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_ASEC, result)
        return result

    def check_server_alienvault_ip_reputation(self, value):
        """Check whether the alienvault ip reputation is valid
        """
        result = AVConfigParserErrors.ALL_OK
        if self.PROFILE_NAME_SERVER not in self.get_general_profile_list():
            logger.warning("Server profile not present. Cannot set sensor ids_rules_flow_control ... %s" % value)
            return result
        if value not in self.ENABLE_DISABLE_CHOICES:
            logger.warning("Server alienvault ip reputation invalid value :%s " % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.INVALID_ALIENVAULT_REPUTATION_IP_VALUE,
                                                        "%s allowed values are (enabled/disabled)" % value)
            self.__add_error(self.SERVER_SECTION_NAME, self.SECTION_SERVER_ALIENVAULT_IP_REPUTATION, result)
        return result

    def check_server_ip(self, value):
        """Check whether the server_ip is valid
        """
        result = AVConfigParserErrors.ALL_OK
        if self.PROFILE_NAME_SERVER not in self.get_general_profile_list():
            logger.warning("Server profile not present. Cannot set server ip ... %s" % value)
            return result
        if not is_ipv4(value):
            logger.warning("Invalid server ip %s" % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.VALUE_NOT_VALID_IP, value)
            self.__add_error(self.SERVER_SECTION_NAME, self.SECTION_SERVER_IP, result)
        return result

    def check_server_server_plugins(self, value):
        """Check whether the [sensor]->server_server_plugins is valid
        TODO: How to validate this value?
        server_server_plugins is a read-only value
        """
        result = AVConfigParserErrors.ALL_OK
        return result

    def check_server_pro(self, value):
        """Check whether the [sensor]->server_pro is valid
        TODO: How to validate this value?
        server_pro is a read-only value
        """
        result = AVConfigParserErrors.ALL_OK
        return result

    def check_snmp_community(self, value):
        """Check whether the [sensor]->server_pro is valid
        server_pro is a read-only value
        """
        # default_value = self.get_default_value(self.SNMP_SECTION_NAME, self.SECTION_SNMP_COMMUNITY)
        result = AVConfigParserErrors.ALL_OK
        if not is_snmp_community_allowed(value):
            logger.warning("Invalid snmp community %s" % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.SNMP_COMMUNITY_VALUE_INVALID,
                                                        "%s Allowed characters: ASCII excpet '@'" % value)
            self.__add_error(self.SNMP_SECTION_NAME, self.SECTION_SNMP_COMMUNITY, result)
        return result

    def check_snmp_snmp_community(self, value):
        """Check whether the [snmp]->snmp_comunity is valid
        TODO: How to validate this value?
        snmp_comunity is a read-only value
        """
        result = AVConfigParserErrors.ALL_OK
        return result

    def check_snmp_snmpd(self, value):
        """Check whether the [snmp]->snmpd is valid
        """
        result = AVConfigParserErrors.ALL_OK
        if value not in self.YES_NO_CHOICES:
            logger.warning("Invalid snmp snmpd %s" % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.SNMPD_INVALID_VALUE,
                                                        "%s allowed [yes,no]" % value)
            self.__add_error(self.SERVER_SECTION_NAME, self.SECTION_SNMP_SNMPD, result)
        return result

    def check_snmp_snmptrap(self, value):
        """Check whether the [snmp]->snmptrap is valid
        """
        result = AVConfigParserErrors.ALL_OK
        if value not in ["no", "yes"]:
            logger.warning("Invalid snmp snmptrap %s" % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.SNMPDTRAP_INVALID_VALUE,
                                                        "%s allowed [yes,no]" % value)
            self.__add_error(self.SERVER_SECTION_NAME, self.SECTION_SNMP_SNMPTRAP, result)
        return result

    def check_update_update_proxy(self, value):
        """Check whether [update]->update_proxy is valid
        allowed values: [disabled, manual, alienvault-proxy]
        """
        result = AVConfigParserErrors.ALL_OK
        if value not in self.PROXY_VALUES:
            logger.warning("Invalid update update proxy %s" % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.UPDATE_PROXY_NOT_VALID, value)
            self.__add_error(self.UPDATE_SECTION_NAME, self.SECTION_UPDATE_PROXY, result)
        return result

    def check_update_update_proxy_dns(self, value):
        """Check whether [update]->update_proxy_dns is valid
        allowed_ values: [disabled, valid ip v4 or valid hostname]
        """
        result = AVConfigParserErrors.ALL_OK
        if (not is_ipv4(value) and not
        is_valid_dns_hostname(value) and not
        self.__is_default(self.UPDATE_SECTION_NAME, self.SECTION_UPDATE_PROXY_DNS, value)):
            logger.warning("Invalid update update_proxy_dns %s" % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.UPDATE_PROXY_DNS_NOT_VALID, value)
            self.__add_error(self.UPDATE_SECTION_NAME, self.SECTION_UPDATE_PROXY_DNS, result)
        return result

    def check_update_update_proxy_pass(self, value):
        """Check whether [update]->update_proxy_pass is valid
         allowed values: [disabled, ascii characters {8,16}]
        """
        result = AVConfigParserErrors.ALL_OK
        # Ticket #7833
        if not is_allowed_password(value, minsize=1, maxsize=999):
            logger.warning("Invalid update update_proxy_pass")
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.UPDATE_PROXY_PASS_NOT_VALID, value)
            self.__add_error(self.UPDATE_SECTION_NAME, self.SECTION_UPDATE_PROXY_PASSWORD, result)
        return result

    def check_update_update_proxy_port(self, value):
        """Check whether [update]->update_proxy_port is valid
         valid TPC/IP port (0, 65535)
        """
        result = AVConfigParserErrors.ALL_OK

        if (not self.__is_default(self.UPDATE_SECTION_NAME, self.SECTION_UPDATE_PROXY_PORT, value)
            and not is_valid_port(value)):
            logger.warning("Invalid update update_proxy_port %s" % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.UPDATE_PROXY_PORT_NOT_VALID, value)
            self.__add_error(self.UPDATE_SECTION_NAME, self.SECTION_UPDATE_PROXY_PORT, result)
        return result

    def check_update_update_proxy_user(self, value):
        """Check whether [update]->update_proxy_port is valid
        """
        result = AVConfigParserErrors.ALL_OK
        if not is_allowed_username(value):
            logger.warning("Invalid update update_proxy_user %s" % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.UPDATE_PROXY_USER_NOT_VALID, value)
            self.__add_error(self.UPDATE_SECTION_NAME, self.SECTION_UPDATE_PROXY_USER, result)
        return result

    def check_interface_ip(self, value):
        """Check whether the ip is valid
        """
        result = AVConfigParserErrors.ALL_OK
        if not is_ipv4(value):
            logger.warning("Invalid ip ... %s" % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.VALUE_NOT_VALID_IP, value)
        return result

    def check_interface_netmask(self, value):
        """Check whether the netmask is valid
        """
        result = AVConfigParserErrors.ALL_OK
        if not is_net_mask(value):
            logger.warning("Invalid netmask ... %s" % value)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.INVALID_NETMASK, value)
        return result

    def set_general_admin_dns(self, value):
        """Sets the value for 'admin_dns'
        Requirements:
        1 - Whether admin_dns == "" -> use the system nameserver at /etc/resolv.conf
        2 - It should be a list of valid IPs separated by ','
        """
        if not self.__avconfig_loaded_ok:
            logger.error("set_general_admin_dns -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        if value == "":
            value = get_current_nameserver()
        result = self.check_general_admin_dns(value)
        if result == AVConfigParserErrors.ALL_OK:
            logger.info("set admin_dns = %s" % value)
            self.__set_option(self.NO_SECTION_NAME, self.NO_SECTION_NAME_ADMIN_DNS, value)

            admin_interface = self.get_general_interface()
            self.__sysconfig.set_net_iface_config(admin_interface,
                                                  dns_nameservers=value)

        return result

    def set_general_admin_gateway(self, value):
        """Sets the value for 'admin_gateway'
        Requirements:
        1 - Whether admin_gateway==""-> get the current admin interface gateway
        2 - Whether the admin interface is not set or is not a valid interface,
        this function will return an error. 
        """
        if not self.__avconfig_loaded_ok:
            logger.error("set_general_admin_gateway -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        result = None
        iface = self.get_general_interface()
        if value == "":
            value = get_current_gateway(iface)
        if iface in self.__interfaces:
            result = self.check_general_admin_gateway(value)
            if result == AVConfigParserErrors.ALL_OK:
                self.__set_option(self.NO_SECTION_NAME, self.NO_SECTION_NAME_ADMIN_GATEWAY, value)
                admin_interface = self.get_general_interface()
                self.__sysconfig.set_net_iface_config(admin_interface,
                                                      gateway=value)


        else:
            result = AVConfigParserErrors.get_error_msg(
                AVConfigParserErrors.CANT_SET_ADMIN_GATEWAY_INVALID_ADMIN_INTERFACE, iface)
            self.__add_error(self.NO_SECTION_NAME, self.NO_SECTION_NAME_ADMIN_GATEWAY, result)
        return result

    def set_general_admin_ip(self, value):
        """Sets the value for 'admin_ip'
        Requirements:
        """
        if not self.__avconfig_loaded_ok:
            logger.error("set_general_admin_ip -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        result = self.check_general_admin_ip(value)

        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.NO_SECTION_NAME, self.NO_SECTION_NAME_ADMIN_IP, value)
            admin_interface = self.get_general_interface()
            self.__sysconfig.set_net_iface_config(admin_interface,
                                                  address=value)

        return result

    def set_general_admin_netmask(self, value):
        """Sets the value for 'admin_netmask'
        1 - Whether admin_netmask==""-> get the current admin interface netmask
        """
        if not self.__avconfig_loaded_ok:
            logger.error("set_general_admin_netmask -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        result = AVConfigParserErrors.ALL_OK
        iface = self.get_general_interface()
        if value == "":
            value = get_network_mask_for_iface(iface)
        if iface in self.__interfaces:
            result = self.check_general_admin_netmask(value)
            if result == AVConfigParserErrors.ALL_OK:
                self.__set_option(self.NO_SECTION_NAME, self.NO_SECTION_NAME_ADMIN_NETMASK, value)
                admin_interface = self.get_general_interface()
                self.__sysconfig.set_net_iface_config(admin_interface,
                                                      netmask=value)

        else:
            result = AVConfigParserErrors.get_error_msg(
                AVConfigParserErrors.CANT_SET_ADMIN_NETMASK_INVALID_ADMIN_INTERFACE, iface)
            self.__add_error(self.NO_SECTION_NAME, self.NO_SECTION_NAME_ADMIN_NETMASK, result)
        return result

    def set_general_domain(self, value):
        """Sets the value for 'domain'
        Requirements:
        1 - Whether domain==""-> get the current domain
        """
        if not self.__avconfig_loaded_ok:
            logger.error("set_general_domain -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)

        # get_current_domain
        if value == "":
            value = get_current_domain()
        result = self.check_general_domain(value)
        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.NO_SECTION_NAME, self.NO_SECTION_NAME_DOMAIN, value)
        return result

    def set_general_email_notify(self, value):
        """Sets the email_notify value
        Requirements: only an email allowed
        """
        if not self.__avconfig_loaded_ok:
            logger.error("set_general_email_notify -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        if not value:
            value = ""
        result = self.check_general_email_notify(value)
        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.NO_SECTION_NAME, self.NO_SECTION_NAME_EMAIN_NOTIFY, value)
        return result

    def set_max_retries(self, value):
        """ Sets max retries
        """
        if not self.__avconfig_loaded_ok:
            logger.error("set_general_hostname -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)

        if value == "":
            value = get_current_max_retries()
        result = self.check_max_retries(value)
        if result == AVConfigParserErrors.ALL_OK:
            value, min_v, max_v = int(value), 0, 20
            value = min_v if value < min_v else value
            value = max_v if value > max_v else value
            subprocess.call("redis-cli set max_retries %s" % value, shell=True)
            self.__set_option(self.NO_SECTION_NAME, self.NO_SECTION_NAME_MAX_RETRIES, value)
        return result

    def set_general_hostname(self, value):
        """Sets the hostname value
        Requirements: 
        1 - Whether the hostname value == "" -> get the default value from /etc/hostname
        """
        if not self.__avconfig_loaded_ok:
            logger.error("set_general_hostname -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)

        if value == "":
            value = get_current_hostname()
        result = self.check_general_hostname(value)
        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.NO_SECTION_NAME, self.NO_SECTION_NAME_HOSTNAME, value)
        return result

    def set_general_interface(self, value):
        """Sets the interface value
        """
        if not self.__avconfig_loaded_ok:
            logger.error("set_general_interface -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)

        result = self.check_general_interface(value)
        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.NO_SECTION_NAME, self.NO_SECTION_NAME_INTERFACE, value)
            result = self.set_net_iface_config(value, is_administration='yes')

        return result

    def set_default_values_for_mail_relay(self):
        self.set_general_mailserver_relay_passwd(
            self.get_default_value(self.NO_SECTION_NAME, self.NO_SECTION_NAME_MAILSERVER_RELAY_PASSWD))
        self.set_general_mailserver_relay_port(
            self.get_default_value(self.NO_SECTION_NAME, self.NO_SECTION_NAME_MAILSERVER_RELAY_PORT))
        self.set_general_mailserver_relay_user(
            self.get_default_value(self.NO_SECTION_NAME, self.NO_SECTION_NAME_MAILSERVER_RELAY_USER))

    def set_general_mailserver_relay(self, value):
        """Sets the mailserver_relay value
        """
        if not self.__avconfig_loaded_ok:
            logger.error("set_general_mailserver_relay -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        if value == "":
            value = self.get_default_value(self.NO_SECTION_NAME, self.NO_SECTION_NAME_MAILSERVER_RELAY)
        result = self.check_general_mailserver_relay(value)
        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.NO_SECTION_NAME, self.NO_SECTION_NAME_MAILSERVER_RELAY, value)
            if self.__is_default(self.NO_SECTION_NAME, self.NO_SECTION_NAME_MAILSERVER_RELAY, value):
                self.set_default_values_for_mail_relay()
        return result

    def set_general_mailserver_relay_passwd(self, value):
        """Sets the mailserver_relay_passwd value
        """
        if not self.__avconfig_loaded_ok:
            logger.error("set_general_mailserver_relay_passwd -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        value = self.__set_default_value_if_needed(self.NO_SECTION_NAME, self.NO_SECTION_NAME_MAILSERVER_RELAY_PASSWD,
                                                   value)
        result = self.check_general_mailserver_relay_passwd(value)
        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.NO_SECTION_NAME, self.NO_SECTION_NAME_MAILSERVER_RELAY_PASSWD, value)
        return result

    def set_general_mailserver_relay_port(self, value):
        """Sets the mailserver_relay_port value
        """
        if not self.__avconfig_loaded_ok:
            logger.error("set_general_mailserver_relay_port -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        value = self.__set_default_value_if_needed(self.NO_SECTION_NAME, self.NO_SECTION_NAME_MAILSERVER_RELAY_PORT,
                                                   value)
        result = self.check_general_mailserver_relay_port(value)
        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.NO_SECTION_NAME, self.NO_SECTION_NAME_MAILSERVER_RELAY_PORT, value)
        return result

    def set_general_mailserver_relay_user(self, value):
        """Sets the mailserver_relay_user value
        """
        if not self.__avconfig_loaded_ok:
            logger.error("set_general_mailserver_relay_user -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        value = self.__set_default_value_if_needed(self.NO_SECTION_NAME, self.NO_SECTION_NAME_MAILSERVER_RELAY_USER,
                                                   value)
        result = self.check_general_mailserver_relay_user(value)
        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.NO_SECTION_NAME, self.NO_SECTION_NAME_MAILSERVER_RELAY_USER, value)
        return result

    def set_general_ntp_server(self, value):
        """Sets the ntp_server value
        """
        if not self.__avconfig_loaded_ok:
            logger.error("set_general_ntp_server -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        value = self.__set_default_value_if_needed(self.NO_SECTION_NAME, self.NO_SECTION_NAME_NTP_SERVER, value)
        result = self.check_general_ntp_server(value)
        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.NO_SECTION_NAME, self.NO_SECTION_NAME_NTP_SERVER, value)
        return result

    def set_general_profile(self, value):
        """profile is a read-only value"""
        return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.READ_ONLY)

    def set_database_db_ip(self, value):
        """Sets the  [database] -> db_ip value
        """
        if not self.__avconfig_loaded_ok:
            logger.error("set_database_db_ip -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        if not self.__should_validate_database_section():
            return AVConfigParserErrors.ALL_OK
        result = self.check_database_db_ip(value)
        if result == AVConfigParserErrors.ALL_OK:
            if self.PROFILE_NAME_DATABASE in self.get_general_profile_list():
                result = AVConfigParserErrors.get_error_msg(
                    AVConfigParserErrors.DATABASE_IP_CANT_BE_CHANGED_PROFILE_IS_DATABASE, value)
                self.__remove_error(self.DATABASE_SECTION_NAME, self.SECTION_DATABASE_IP)
            else:
                self.__set_option(self.DATABASE_SECTION_NAME, self.SECTION_DATABASE_IP, value)
        return result

    def set_database_pass(self, value):
        """Sets the [database] - > pass value
        """
        if not self.__avconfig_loaded_ok:
            logger.error("set_database_pass -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        if not self.__should_validate_database_section():
            return AVConfigParserErrors.ALL_OK
        result = self.check_database_pass(value)
        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.DATABASE_SECTION_NAME, self.SECTION_DATABASE_PASSWORD, value)
        return result

    def set_database_user(self, value):
        """Sets the [database]->user value
        Requirenments:
        From mysql doc: http://dev.mysql.com/doc/refman/5.1/en/user-names.html
        - MySQL user names can be up to 16 characters long.
        It is possible to connect to the server regardless of character 
        set settings if the user name and password contain only ASCII characters. 
        To connect when the user name or password contain non-ASCII characters, 
        the client should call the mysql_options() C API function with
        the MYSQL_SET_CHARSET_NAME option and appropriate character 
        set name as arguments. This causes authentication to take place using 
        the specified character set. Otherwise, authentication will fail unless 
        the server default character set is the same as 
        the encoding in the authentication defaults.
        """
        if not self.__avconfig_loaded_ok:
            logger.error("set_database_user -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        if not self.__should_validate_database_section():
            return AVConfigParserErrors.ALL_OK
        result = self.check_database_user(value)
        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.DATABASE_SECTION_NAME, self.SECTION_DATABASE_USER, value)
        return result

    def set_expert_profile(self, value):
        """Sets the [expert]->profile
        """
        return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.READ_ONLY)

    def set_firewall_active(self, value):
        """Sets the [firewall] active value
        """
        if not self.__avconfig_loaded_ok:
            logger.error("set_firewall_active -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        value = self.__set_default_value_if_needed(self.FIREWALL_SECTION_NAME, self.SECTION_FIREWALL_ACTIVE, value)
        result = self.check_firewall_active(value)
        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.FIREWALL_SECTION_NAME, self.SECTION_FIREWALL_ACTIVE, value)
        return result

    def set_framework_framework_https_cert(self, value):
        """Sets the framework_https_cert value
        """
        if not self.__avconfig_loaded_ok:
            logger.error("set_framework_framework_https_cert -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        result = AVConfigParserErrors.ALL_OK
        if not self.__should_validate_framework_section():
            return result
        value = self.__set_default_value_if_needed(self.FRAMEWORK_SECTION_NAME, self.SECTION_FRAMEWORK_HTTPS_CERT,
                                                   value)
        result = self.check_framework_https_cert(value)
        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.FRAMEWORK_SECTION_NAME, self.SECTION_FRAMEWORK_HTTPS_CERT, value)
        return result

    def set_framework_framework_https_key(self, value):
        """Sets the framework_https_key value
        """
        if not self.__avconfig_loaded_ok:
            logger.error("set_framework_framework_https_key -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        result = AVConfigParserErrors.ALL_OK
        if not self.__should_validate_framework_section():
            return result
        value = self.__set_default_value_if_needed(self.FRAMEWORK_SECTION_NAME, self.SECTION_FRAMEWORK_HTTPS_KEY, value)
        result = self.check_framework_https_key(value)
        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.FRAMEWORK_SECTION_NAME, self.SECTION_FRAMEWORK_HTTPS_KEY, value)
        return result

    def set_framework_framework_ip(self, value):
        """Sets the framework_ip value
        """
        result = AVConfigParserErrors.ALL_OK
        if not self.__avconfig_loaded_ok:
            logger.error("set_framework_framework_ip -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        if value == "":
            value = self.get_general_admin_ip()
        if not self.__should_validate_framework_section():
            return result

        result = self.check_framework_ip(value)
        if result == AVConfigParserErrors.ALL_OK:
            if self.PROFILE_NAME_FRAMEWORK in self.get_general_profile_list():
                # Fix  - #8429 - The IP could be changed to our local ip, or to your local VPN IP.
                # 
                local_ips = get_local_ip_addresses_list()
                # Including admin_ip
                local_ips.append(self.get_general_admin_ip())
                # ENG- It could be the famework ip too
                local_ips.append(self.get_ha_ha_virtual_ip())
                if value in local_ips:
                    self.__set_option(self.FRAMEWORK_SECTION_NAME, self.SECTION_FRAMEWORK_IP, value)
                else:
                    result = AVConfigParserErrors.get_error_msg(
                        AVConfigParserErrors.INVALID_FRAMEWORK_IP_NOT_IN_LOCAL_IPS, str(local_ips))
                    self.__remove_error(self.FRAMEWORK_SECTION_NAME, self.SECTION_FRAMEWORK_IP)
                    # result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FRAMEWORK_IP_CANT_BE_CHANGED_PROFILE_IS_FRAMEWORK, value)
            else:
                self.__set_option(self.FRAMEWORK_SECTION_NAME, self.SECTION_FRAMEWORK_IP, value)
        return result

    def set_sensor_detectors(self, value):
        """Sets the [sensor]-> detectors value
        """
        result = AVConfigParserErrors.ALL_OK
        if not self.__avconfig_loaded_ok:
            logger.error("set_sensor_detectors -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        if not self.__should_validate_sensor_section():
            return result
        result = self.check_sensor_detectors(value)
        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_DETECTORS, self.__get_list_value(value))
        return result

    def set_sensor_ids_rules_flow_control(self, value):
        """Sets the [sensor]->ids_rules_flow_control value
        """
        result = AVConfigParserErrors.ALL_OK
        if not self.__avconfig_loaded_ok:
            logger.error("set_sensor_ids_rules_flow_control -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        if not self.__should_validate_sensor_section():
            return result
        value = self.__set_default_value_if_needed(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_IDS_RULES_FLOW_CONTROL,
                                                   value)
        result = self.check_sensor_ids_rules_flow_control(value)
        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_IDS_RULES_FLOW_CONTROL, value)
        return result

    def set_sensor_interfaces(self, value):
        """Sets the [sensor]->interfaces value
        """
        result = AVConfigParserErrors.ALL_OK
        previous_sensor_interfaces = []
        if not self.__avconfig_loaded_ok:
            logger.error("set_sensor_interfaces -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        if not self.__should_validate_sensor_section():
            return result
        result = self.check_sensor_interfaces(value)
        if result == AVConfigParserErrors.ALL_OK:
            previous_sensor_interfaces = self.get_sensor_interfaces_list()
            self.__set_option(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_INTERFACES, self.__get_list_value(value))

            value = value.replace(' ', '')
            new_sensor_interfaces = value.split(',')

            for iface in previous_sensor_interfaces:
                if iface not in new_sensor_interfaces:
                    # Remove promisc mode
                    result = self.set_net_iface_config(iface, is_monitor='no')

            # Set sensor interface in the /etc/alienvault/network/interfaces.conf file.
            for iface in new_sensor_interfaces:
                iface_config = self.get_net_iface_config(iface)[iface]
                if iface_config.get('log_management', 'no') == 'no':
                    result = self.set_net_iface_config(iface, address='0.0.0.0')

                r = self.set_net_iface_config(iface, is_monitor='yes')
                if r != AVConfigParserErrors.ALL_OK:
                    result = r
        return result

    def set_sensor_ip(self, value):
        """Sets the [sensor]->ip value
        """
        result = AVConfigParserErrors.ALL_OK
        if not self.__avconfig_loaded_ok:
            logger.error("set_sensor_ip -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        if not self.__should_validate_sensor_section():
            return result
        result = self.check_sensor_ip(value)
        if result == AVConfigParserErrors.ALL_OK:
            if self.__is_profile_all_in_one() or self.__is_profile_only_sensor():
                result = AVConfigParserErrors.get_error_msg(
                    AVConfigParserErrors.SENSOR_IP_CANT_BE_CHANGED_PROFILE_IS_SENSOR)
                self.__remove_error(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_IP)
            else:
                self.__set_option(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_IP, value)
        return result

    def set_sensor_monitors(self, value):
        """Sets the [sensor]->monitors value
        """
        result = AVConfigParserErrors.ALL_OK
        if not self.__avconfig_loaded_ok:
            logger.error("set_sensor_monitors -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        if not self.__should_validate_sensor_section():
            return result
        if not value:
            value = ""

        result = self.check_sensor_monitors(value)
        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_MONITORS, self.__get_list_value(value))
        return result

    def set_sensor_mservers(self, value):
        """Sets the [sensor]->mservers value
        """
        result = AVConfigParserErrors.ALL_OK
        if not self.__avconfig_loaded_ok:
            logger.error("set_sensor_mservers -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        if not self.__should_validate_sensor_section():
            return result
        value = self.__set_default_value_if_needed(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_MSERVER, value)
        result = self.check_sensor_mserver(value)
        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_MSERVER, value)
        return result

    def set_sensor_name(self, value):
        """Sets the [sensor]->name value
        """
        result = AVConfigParserErrors.ALL_OK
        if not self.__avconfig_loaded_ok:
            logger.error("set_sensor_name -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        if not self.__should_validate_sensor_section():
            return result
        result = self.check_sensor_name(value)
        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_NAME, value)
        return result

    def set_sensor_netflow(self, value):
        """Sets the [sensor]->netflow value
        """
        result = AVConfigParserErrors.ALL_OK
        if not self.__avconfig_loaded_ok:
            logger.error("set_sensor_netflow -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        if not self.__should_validate_sensor_section():
            return result
        value = self.__set_default_value_if_needed(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_NETFLOW, value)
        result = self.check_sensor_netflow(value)
        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_NETFLOW, value)
        return result

    def set_sensor_netflow_remote_collector_port(self, value):
        """Sets the [sensor]->netflow_remote_collector_port value
        """
        result = AVConfigParserErrors.ALL_OK
        if not self.__avconfig_loaded_ok:
            logger.error("set_sensor_netflow_remote_collector_port -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        if not self.__should_validate_sensor_section():
            return result
        value = self.__set_default_value_if_needed(self.SENSOR_SECTION_NAME,
                                                   self.SECTION_SENSOR_NETFLOW_REMOTE_COLLECTOR_PORT, value)
        result = self.check_sensor_netflow_remote_collector_port(value)
        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_NETFLOW_REMOTE_COLLECTOR_PORT, value)
        return result

    def set_sensor_networks(self, value):
        """Sets the [sensor]->networks value
        """
        result = AVConfigParserErrors.ALL_OK
        if not self.__avconfig_loaded_ok:
            logger.error("set_sensor_networks -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        if not self.__should_validate_sensor_section():
            return result
        value = self.__set_default_value_if_needed(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_NETWORKS, value)
        # data = value.split(',')
        result = self.check_sensor_networks(value)
        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_NETWORKS, value)
        return result

    def set_sensor_pci_express(self, value):
        """pci_express is a read-only value"""
        return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.READ_ONLY)

    def set_sensor_tzone(self, value):
        """tzone is a read-only value"""
        result = AVConfigParserErrors.ALL_OK
        if not self.__avconfig_loaded_ok:
            logger.error("set_sensor_networks -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        if not self.__should_validate_sensor_section():
            return result
        self.__set_option(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_TZONE, value)
        return result

    def set_sensor_ctx(self, value):
        """Sets the [sensor]->sensor_ctx value
        """
        result = AVConfigParserErrors.ALL_OK
        if not self.__avconfig_loaded_ok:
            logger.error("set_sensor_ctx -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        if not self.__should_validate_sensor_section():
            return result
        value = self.__set_default_value_if_needed(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_CTX, value)
        result = self.check_sensor_ctx(value)
        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_CTX, value)
        return result

    def set_sensor_asec(self, value):
        """Sets the [sensor]->asec value
        """
        result = AVConfigParserErrors.ALL_OK
        if not self.__avconfig_loaded_ok:
            logger.error("set_sensor_asec -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        if not self.__should_validate_sensor_section():
            return result
        value = self.__set_default_value_if_needed(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_ASEC, value)
        result = self.check_sensor_asec(value)
        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_ASEC, value)
        return result

    def set_server_alienvault_ip_reputation(self, value):
        """Sets the [server]->alienvault_ip_reputation value
        """
        result = AVConfigParserErrors.ALL_OK
        if not self.__avconfig_loaded_ok:
            logger.error("set_server_alienvault_ip_reputation -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        if not self.__should_validate_server_section():
            return result
        value = self.__set_default_value_if_needed(self.SERVER_SECTION_NAME,
                                                   self.SECTION_SERVER_ALIENVAULT_IP_REPUTATION, value)
        result = self.check_server_alienvault_ip_reputation(value)
        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.SERVER_SECTION_NAME, self.SECTION_SERVER_ALIENVAULT_IP_REPUTATION, value)
        return result

    def set_server_server_ip(self, value):
        """Sets the [server]->server_ip value
        """
        result = AVConfigParserErrors.ALL_OK
        if not self.__avconfig_loaded_ok:
            logger.error("set_server_server_ip -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        if not self.__should_validate_server_section():
            return result
        if value == "":
            value = self.get_general_admin_ip()

        result = self.check_server_ip(value)
        if result == AVConfigParserErrors.ALL_OK:
            if self.PROFILE_NAME_SERVER in self.get_general_profile_list():
                # Fix  - #8429 - The IP could be changed to our local ip, or to your local VPN IP.
                local_ips = get_local_ip_addresses_list()
                # Including admin_ip
                local_ips.append(self.get_general_admin_ip())
                # ENG- It can be the HA Virtual IP too. 
                local_ips.append(self.get_ha_ha_virtual_ip())
                if value in local_ips:
                    self.__set_option(self.SERVER_SECTION_NAME, self.SECTION_SERVER_IP, value)
                else:
                    result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.INVALID_SERVER_IP_NOT_IN_LOCAL_IPS,
                                                                str(local_ips))
                    self.__remove_error(self.SERVER_SECTION_NAME, self.SECTION_SERVER_IP)
                    # result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.SERVER_IP_CANT_BE_CHAGED_PROFILE_IS_SERVER)
                    # self.__remove_error(self.SERVER_SECTION_NAME, self.SECTION_SERVER_IP)
            else:
                self.__set_option(self.SERVER_SECTION_NAME, self.SECTION_SERVER_IP, value)
        return result

    def set_server_server_plugins(self, value):
        """Sets the [server]->server_plugins value
        """
        return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.READ_ONLY)

    def set_server_pro(self, value):
        """Sets the [server]->server_pro value
        """
        return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.READ_ONLY)

    def set_snmp_comunity(self, value):
        """Sets the [snmp]->community value
        The RFC specifies there are two types of defualt communities (public and private)
        the other possible values are an OCTEC_STRING.
        Most people have had trouble configuring a snmp community string containing '@' so
        this character is not allowed
        NOTE: The name has a typo (it should be community) but 
        we use the notation set_section_option. And the option is wrong typed in the file.
        """
        if not self.__avconfig_loaded_ok:
            logger.error("set_snmp_comunity -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)

        value = self.__set_default_value_if_needed(self.SNMP_SECTION_NAME, self.SECTION_SNMP_COMMUNITY, value)
        result = self.check_snmp_community(value)
        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.SNMP_SECTION_NAME, self.SECTION_SNMP_COMMUNITY, value)
        return result

    def set_snmp_snmp_comunity(self, value):
        """Sets the [snmp]->snmp_comunity value
        NOTE: read-only at the moment. It will be available in the future
        There's a typo on the option name. 
        """
        return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.READ_ONLY)

    def set_snmp_snmpd(self, value):
        """Sets the [snmp]->snmpd value
        """
        if not self.__avconfig_loaded_ok:
            logger.error("set_snmp_snmpd -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        value = self.__set_default_value_if_needed(self.SNMP_SECTION_NAME, self.SECTION_SNMP_SNMPD, value)
        result = self.check_snmp_snmpd(value)
        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.SNMP_SECTION_NAME, self.SECTION_SNMP_SNMPD, value)
        return result

    def set_snmp_snmptrap(self, value):
        """Sets the [snmp]->snmptrap value
        """
        if not self.__avconfig_loaded_ok:
            logger.error("set_snmp_snmptrap -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        value = self.__set_default_value_if_needed(self.SNMP_SECTION_NAME, self.SECTION_SNMP_SNMPTRAP, value)
        result = self.check_snmp_snmptrap(value)
        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.SNMP_SECTION_NAME, self.SECTION_SNMP_SNMPTRAP, value)
        return result

    def set_default_values_for_update_proxy(self):
        """Sets the default values for proxy section
        """
        self.set_update_update_proxy_dns(
            self.get_default_value(self.UPDATE_SECTION_NAME, self.SECTION_UPDATE_PROXY_DNS))
        self.set_update_update_proxy_pass(
            self.get_default_value(self.UPDATE_SECTION_NAME, self.SECTION_UPDATE_PROXY_PASSWORD))
        self.set_update_update_proxy_port(
            self.get_default_value(self.UPDATE_SECTION_NAME, self.SECTION_UPDATE_PROXY_PORT))
        self.set_update_update_proxy_user(
            self.get_default_value(self.UPDATE_SECTION_NAME, self.SECTION_UPDATE_PROXY_USER))

    def set_update_update_proxy(self, value):
        """Sets the [update]->update_proxy value
        allowed values: [disabled, manual, alienvault-proxy]
        """
        if not self.__avconfig_loaded_ok:
            logger.error("set_update_update_proxy -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        value = self.__set_default_value_if_needed(self.UPDATE_SECTION_NAME, self.SECTION_UPDATE_PROXY, value)
        result = self.check_update_update_proxy(value)
        if result == AVConfigParserErrors.ALL_OK:

            self.__set_option(self.UPDATE_SECTION_NAME, self.SECTION_UPDATE_PROXY, value)
            if self.__is_default(self.UPDATE_SECTION_NAME, self.SECTION_UPDATE_PROXY, value):
                self.set_default_values_for_update_proxy()
        return result

    def set_update_update_proxy_dns(self, value):
        """Sets the [update]->update_proxy_dns value
        allowed_ values: [disabled, valid ip v4 or valid hostname]
        """

        if not self.__avconfig_loaded_ok:
            logger.error("set_update_update_proxy_dns -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        value = self.__set_default_value_if_needed(self.UPDATE_SECTION_NAME, self.SECTION_UPDATE_PROXY_DNS, value)
        result = self.check_update_update_proxy_dns(value)
        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.UPDATE_SECTION_NAME, self.SECTION_UPDATE_PROXY_DNS, value)
        return result

    def set_update_update_proxy_pass(self, value):
        """Sets the [update]->update_proxy_pass value
        allowed values: [disabled, ascii characters {8,16}]
        """
        if not self.__avconfig_loaded_ok:
            logger.error("set_update_update_proxy_pass -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        value = self.__set_default_value_if_needed(self.UPDATE_SECTION_NAME, self.SECTION_UPDATE_PROXY_PASSWORD, value)
        result = self.check_update_update_proxy_pass(value)
        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.UPDATE_SECTION_NAME, self.SECTION_UPDATE_PROXY_PASSWORD, value)
        return result

    def set_update_update_proxy_port(self, value):
        """Sets the [update]->update_proxy_port value
        valid TPC/IP port (0, 65535)
        """
        if not self.__avconfig_loaded_ok:
            logger.error("set_update_update_proxy_port -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        value = self.__set_default_value_if_needed(self.UPDATE_SECTION_NAME, self.SECTION_UPDATE_PROXY_PORT, value)
        result = self.check_update_update_proxy_port(value)
        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.UPDATE_SECTION_NAME, self.SECTION_UPDATE_PROXY_PORT, value)
        return result

    def set_update_update_proxy_user(self, value):
        """Sets the [update]->update_proxy_user value
        allowed values: [disabled, ascii characters {8,16}]
        """
        if not self.__avconfig_loaded_ok:
            logger.error("set_update_update_proxy_user -> File not loaded!")
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.FILE_NOT_LOADED, value)
        value = self.__set_default_value_if_needed(self.UPDATE_SECTION_NAME, self.SECTION_UPDATE_PROXY_USER, value)
        result = self.check_update_update_proxy_user(value)
        if result == AVConfigParserErrors.ALL_OK:
            self.__set_option(self.UPDATE_SECTION_NAME, self.SECTION_UPDATE_PROXY_USER, value)
        return result

    ####################################
    # Non ossim_setup.conf related stuff
    ####################################

    ### /etc/network/interfaces configuration

    def set_net_iface_config(self, iface,
                             address=None,
                             netmask=None,
                             gateway=None,
                             dns_search=None,
                             dns_nameservers=None,
                             broadcast=None,
                             network=None,
                             is_administration=None,
                             is_log_management=None,
                             is_monitor=None):
        """
        Set the network configuration for the interface 'iface'.
        """
        # Take into account that this may be the admin interface...
        # And, very important here, do not change any network option if the admin interface is being set as a monitor interface.
        if self.get_general_interface() == iface and is_monitor is None:
            apply_on = [(x, y) for (x, y) in locals().items() if
                        x in ['address', 'netmask', 'gateway', 'dns_search', 'dns_nameservers'] and y is not None]
            ops = {'address': (self.set_general_admin_ip, self.get_general_admin_ip()),
                   'netmask': (self.set_general_admin_netmask, self.get_general_admin_netmask()),
                   'gateway': (self.set_general_admin_gateway, self.get_general_admin_gateway()),
                   'dns_search': (self.set_general_domain, self.get_general_domain()),
                   'dns_nameservers': (self.set_general_admin_dns, self.get_general_admin_dns())}

            for (counter, (field, value)) in enumerate(apply_on):
                result = ops[field][0](value)
                if result != AVConfigParserErrors.ALL_OK:
                    # Rollback!
                    for (field, value) in apply_on[:(counter + 1)]:
                        result_roll = ops[field][0](ops[field][1])
                    return result

        self.__file_dirty = True
        return self.__sysconfig.set_net_iface_config(iface, address, netmask, gateway, dns_search, dns_nameservers,
                                                     broadcast, network,
                                                     is_administration, is_log_management, is_monitor)

    # Dumb methods to interact with ossimsetup.
    # All methods set the is_log_management flag to 'yes', since this is used to set this interface type.
    def set_net_iface_name(self, whatever):
        return AVConfigParserErrors.ALL_OK

    def disable_log_management_interface(self, interface):
        return self.set_net_iface_config(interface, address='TBD', netmask='TBD', is_log_management='no')

    def set_net_iface_ip(self, value, modifier='eth0'):
        if value == "" and self.get_general_interface() != modifier:
            return self.disable_log_management_interface(modifier)

        check_result = self.check_interface_ip(value)
        if check_result != AVConfigParserErrors.ALL_OK:
            return check_result
        return self.set_net_iface_config(modifier, address=value, is_log_management='yes')

    def set_net_iface_netmask(self, value, modifier='eth0'):
        if (value == "" and self.get_general_interface() != modifier) or \
                not self.__sysconfig.is_net_iface_address_set(modifier):
            return self.disable_log_management_interface(modifier)

        check_result = self.check_interface_netmask(value)
        if check_result != AVConfigParserErrors.ALL_OK:
            return check_result
        return self.set_net_iface_config(modifier, netmask=value, is_log_management='yes')

    def set_net_iface_gateway(self, value, modifier='eth0'):
        return self.set_net_iface_config(modifier, gateway=value, is_log_management='yes')

    ### /etc/hosts configuration

    def set_hosts_config(self, entry, ipaddr=None, canonical=None, aliases=[]):
        """
        Set the configuration values for host entry 'entry'
        """
        result = self.__sysconfig.set_hosts_config(entry, ipaddr, canonical, aliases)
        if result == AVConfigParserErrors.ALL_OK:
            self.__file_dirty = True
        return result

    def set_hosts_ipaddr(self, value, modifier='2'):
        return self.set_hosts_config(modifier, ipaddr=value)

    def set_hosts_canonical(self, value, modifier='2'):
        return self.set_hosts_config(modifier, canonical=value)

    def set_hosts_aliases(self, value, modifier='2'):
        return self.set_hosts_config(modifier, aliases=value)

    ### Registered systems configuration

    def set_registered_system(self, value):
        """
        Dumb method.
        """
        return AVConfigParserErrors.ALL_OK

    def set_registered_systems_without_vpn(self, value):
        """
        Dumb method.
        """
        return AVConfigParserErrors.ALL_OK

    ### /etc/alienvault/network/vpn.conf configuration

    def set_avvpn_config(self, iface,
                         role=None, config_file=None,
                         network=None, netmask=None, port=None,
                         ca=None, cert=None, key=None, dh=None,
                         enabled=None):
        """
        Set the VPN configuration for the interface 'iface'.
        """
        result = self.__sysconfig.set_avvpn_config(iface, role=role, config_file=config_file,
                                                   network=network, netmask=netmask, port=port,
                                                   ca=ca, cert=cert, key=key, dh=dh,
                                                   enabled=enabled)
        if result == AVConfigParserErrors.ALL_OK:
            self.__file_dirty = True
        return result

    def set_avvpn_config_role(self, value, modifier='tun0'):
        return self.set_avvpn_config(modifier, role=value)

    def set_avvpn_config_config_file(self, value, modifier='tun0'):
        return self.set_avvpn_config(modifier, config_file=value)

    def set_avvpn_config_network(self, value, modifier='tun0'):
        return self.set_avvpn_config(modifier, network=value)

    def set_avvpn_config_netmask(self, value, modifier='tun0'):
        return self.set_avvpn_config(modifier, netmask=value)

    def set_avvpn_config_port(self, value, modifier='tun0'):
        return self.set_avvpn_config(modifier, port=value)

    def set_avvpn_config_ca(self, value, modifier='tun0'):
        return self.set_avvpn_config(modifier, ca=value)

    def set_avvpn_config_cert(self, value, modifier='tun0'):
        return self.set_avvpn_config(modifier, cert=value)

    def set_avvpn_config_key(self, value, modifier='tun0'):
        return self.set_avvpn_config(modifier, key=value)

    def set_avvpn_config_dh(self, value, modifier='tun0'):
        return self.set_avvpn_config(modifier, dh=value)

    def set_avvpn_config_enabled(self, value, modifier='tun0'):
        return self.set_avvpn_config(modifier, enabled=value)

    ###############################################################################
    #           PUBLIC API
    ###############################################################################

    def save_ossim_setup_file(self, filename="", abort_on_errors=False, makebackup=True):
        """Save the ossim_setup file.
        @param filename The file name where you want to save the contents.
        @param abort_on_errors boolean abort the saving proccess on errors
        @returns a tuple (code, message)
        """
        if filename == "":
            filename = self.__ossim_setup_file
        result = AVConfigParserErrors.ALL_OK
        if abort_on_errors and self.has_errors():
            return AVConfigParserErrors.get_error_msg(AVConfigParserErrors.CANT_SAVE_DUE_TO_ERRORS)
        if self.__avconfig_setup:
            try:
                if self.__check_md5():
                    if makebackup:
                        result = self.make_backup()
                        if result[0] != 0:
                            # Error, retun result
                            return result
                    self.__lockFile.acquire(timeout=2)
                    result = self.__avconfig_setup.write(filename)
                    self.__modified_values.clear()
                    self.__errors.clear()
                    self.__file_dirty = False
                    self.__lockFile.release()
                else:
                    result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.CANT_SAVE_FILE_HAS_CHANGED)
            except AlreadyLocked, e:
                result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.CANT_SAVE_FILE_LOCKED, str(e))
            except LockFailed, e:
                result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.CANT_SAVE_FILE_LOCKED, str(e))

        if self.__sysconfig.is_pending():
            result = self.__sysconfig.apply_changes()

        return result

    def is_default_general_admin_dns(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.NO_SECTION_NAME, self.NO_SECTION_NAME_ADMIN_DNS, value)

    def is_default_general_admin_gateway(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.NO_SECTION_NAME, self.NO_SECTION_NAME_ADMIN_GATEWAY, value)

    def is_default_general_admin_ip(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.NO_SECTION_NAME, self.NO_SECTION_NAME_ADMIN_IP, value)

    def is_default_general_admin_netmask(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.NO_SECTION_NAME, self.NO_SECTION_NAME_ADMIN_NETMASK, value)

    def is_default_general_domain(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.NO_SECTION_NAME, self.NO_SECTION_NAME_DOMAIN, value)

    def is_default_general_email_notify(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.NO_SECTION_NAME, self.NO_SECTION_NAME_EMAIN_NOTIFY, value)

    def is_default_max_retries(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.NO_SECTION_NAME, self.NO_SECTION_NAME_MAX_RETRIES, value)

    def is_default_general_hostname(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.NO_SECTION_NAME, self.NO_SECTION_NAME_HOSTNAME, value)

    def is_default_general_interface(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.NO_SECTION_NAME, self.NO_SECTION_NAME_INTERFACE, value)

    def is_default_general_mailserver_relay(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.NO_SECTION_NAME, self.NO_SECTION_NAME_MAILSERVER_RELAY, value)

    def is_default_general_mailserver_relay_passwd(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.NO_SECTION_NAME, self.NO_SECTION_NAME_MAILSERVER_RELAY_PASSWD, value)

    def is_default_general_mailserver_relay_port(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.NO_SECTION_NAME, self.NO_SECTION_NAME_MAILSERVER_RELAY_PORT, value)

    def is_default_general_mailserver_relay_user(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.NO_SECTION_NAME, self.NO_SECTION_NAME_MAILSERVER_RELAY_USER, value)

    def is_default_general_ntp_server(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.NO_SECTION_NAME, self.NO_SECTION_NAME_NTP_SERVER, value)

    def is_default_general_profile(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.NO_SECTION_NAME, self.NO_SECTION_NAME_PROFILE, value)

    def is_default_database_db_ip(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.DATABASE_SECTION_NAME, self.SECTION_DATABASE_IP, value)

    def is_default_database_pass(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.DATABASE_SECTION_NAME, self.SECTION_DATABASE_PASSWORD, value)

    def is_default_database_user(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.DATABASE_SECTION_NAME, self.SECTION_DATABASE_USER, value)

    def is_default_expert_profile(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.EXPERT_SECTION_NAME, self.SECTION_EXPERT_PROFILE, value)

    def is_default_firewall_active(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.EXPERT_SECTION_NAME, self.SECTION_EXPERT_PROFILE, value)

    def is_default_framework_framework_https_cert(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.FRAMEWORK_SECTION_NAME, self.SECTION_FRAMEWORK_HTTPS_CERT, value)

    def is_default_framework_framework_https_key(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.FRAMEWORK_SECTION_NAME, self.SECTION_FRAMEWORK_HTTPS_KEY, value)

    def is_default_framework_framework_ip(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.FRAMEWORK_SECTION_NAME, self.SECTION_FRAMEWORK_IP, value)

    def is_default_sensor_detectors(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_DETECTORS, value)

    def is_default_sensor_ids_rules_flow_control(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_IDS_RULES_FLOW_CONTROL, value)

    def is_default_sensor_interfaces(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_INTERFACES, value)

    def is_default_sensor_ip(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_IP, value)

    def is_default_sensor_monitors(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_MONITORS, value)

    def is_default_sensor_mservers(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_MSERVER, value)

    def is_default_sensor_name(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_NAME, value)

    def is_default_sensor_netflow(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_NETFLOW, value)

    def is_default_sensor_netflow_remote_collector_port(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_NETFLOW_REMOTE_COLLECTOR_PORT, value)

    def is_default_sensor_networks(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_NETWORKS, value)

    def is_default_sensor_pci_express(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_PCI_EXPRESS, value)

    def is_default_sensor_tzone(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_TZONE, value)

    def is_default_sensor_ctx(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_CTX, value)

    def is_default_sensor_asec(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.SENSOR_SECTION_NAME, self.SECTION_SENSOR_ASEC, value)

    def is_default_server_alienvault_ip_reputation(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.SERVER_SECTION_NAME, self.SECTION_SERVER_ALIENVAULT_IP_REPUTATION, value)

    def is_default_server_server_ip(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.SERVER_SECTION_NAME, self.SECTION_SERVER_IP, value)

    def is_default_server_server_plugins(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.SERVER_SECTION_NAME, self.SECTION_SERVER_PLUGINS, value)

    def is_default_server_server_pro(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.SERVER_SECTION_NAME, self.SECTION_SERVER_PRO, value)

    def is_default_smmp_community(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.SNMP_SECTION_NAME, self.SECTION_SNMP_COMMUNITY, value)

    def is_default_smmp_snmp_comunity(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.SNMP_SECTION_NAME, self.SECTION_SNMP_SNMP_COMMUNITY, value)

    def is_default_smmp_snmpd(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.SNMP_SECTION_NAME, self.SECTION_SNMP_SNMPD, value)

    def is_default_smmp_snmptrap(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.SNMP_SECTION_NAME, self.SECTION_SNMP_SNMPTRAP, value)

    def is_default_update_update_proxy(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.UPDATE_SECTION_NAME, self.SECTION_UPDATE_PROXY, value)

    def is_default_update_update_proxy_dns(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.UPDATE_SECTION_NAME, self.SECTION_UPDATE_PROXY_DNS, value)

    def is_default_update_update_proxy_pass(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.UPDATE_SECTION_NAME, self.SECTION_UPDATE_PROXY_PASSWORD, value)

    def is_default_update_update_proxy_port(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.UPDATE_SECTION_NAME, self.SECTION_UPDATE_PROXY_PORT, value)

    def is_default_update_update_proxy_user(self, value):
        """Returns whether the value is a default value
        """
        return self.__is_default(self.UPDATE_SECTION_NAME, self.SECTION_UPDATE_PROXY_USER, value)

    ####################################
    # Non ossim_setup.conf related stuff
    # Mostly dumb methods to interact with ossimsetup
    ####################################

    ### /etc/network/interfaces configuration

    def is_default_net_iface_ip(self, value):
        return

    def is_default_net_iface_netmask(self, value):
        return

    def is_default_net_iface_gateway(self, value):
        return

    ### /etc/alienvault/network/vpn.conf

    def is_default_avvpn_config_network(self, value):
        return

    def is_default_avvpn_config_netmask(self, value):
        return

    def is_default_avvpn_config_port(self, value):
        return

    def is_default_avvpn_config_enabled(self, value):
        return

    def has_errors(self):
        """Returns if the current config has errors
        """
        nerrors = 0
        for section, option in self.__errors.iteritems():
            nerrors += len(option)
        if nerrors > 0:
            return True
        return False

    def is_profile_sensor(self):
        """Check whether this machine is a sensor profile
        """
        if "Sensor" in self.get_general_profile():
            return True
        return False

    def is_profile_server(self):
        """Check whether this machine is a server profile
        """
        if "Server" in self.get_general_profile():
            return True
        return False

    def is_profile_framework(self):
        """Check whether this machine is a framework profile
        """
        if "Framework" in self.get_general_profile():
            return True
        return False

    def is_profile_database(self):
        """Check whether this machine is a database profile
        """
        if "Database" in self.get_general_profile():
            return True
        return False

    def get_allowed_values_for_general_interface(self):
        """Returns the allowed values for the admin interface
        """
        return self.__interfaces

    def get_allowed_values_for_general_profile(self):
        """Returns the allowed values for the admin profile
        """
        return self.ALLOWED_PROFILES

    def get_allowed_values_for_firewall_active(self):
        """Returns the allowed values for the firewall active
        """
        return self.YES_NO_CHOICES

    def get_allowed_values_for_sensor_ids_rules_flow_control(self):
        """Returns the allowed values for the sensor ids_rules_flow_control
        """
        return self.YES_NO_CHOICES

    def get_allowed_values_for_sensor_interfaces(self):
        """Returns the allowed values for the sensor interfaces
        """
        return self.__interfaces

    def get_allowed_values_for_sensor_monitors(self):
        """Returns the allowed values for the sensor monitors
        """
        return get_current_monitor_plugin_list_clean()

    def get_allowed_values_for_sensor_detectors(self):
        """Returns the allowed values for the sensor detectors
        """
        return get_current_detector_plugin_list()

    def get_allowed_values_for_sensor_netflow(self):
        """Returns the allowed values for the sensor netflow
        """
        return self.YES_NO_CHOICES

    def get_allowed_values_for_sensor_asec(self):
        """Returns the allowed values for the sensor asec
        """
        return self.YES_NO_CHOICES

    def get_allowed_values_for_snmp_snmpd(self):
        """Returns the allowed values for the sensor snmpd
        """
        return self.YES_NO_CHOICES

    def get_allowed_values_for_snmp_snmptrap(self):
        """Returns the allowed values for the sensor snmptrap
        """
        return self.YES_NO_CHOICES

    def get_allowed_values_for_update_update_proxy(self):
        """Returns the allowed values for the proxy values
        """
        if get_is_professional():
            print "Profesional"
            return self.PROXY_VALUES
        else:
            print "No profesional"
            return self.PROXY_VALUES_NO_PRO

    ####################################
    # Non ossim_setup.conf related stuff
    ####################################
    def get_allowed_values_for_net_iface_name(self):
        """Returns the allowed values for network interfaces
        """
        return self.__sysconfig.get_net_iface_config_all().keys()

    def get_allowed_values_for_registered_system(self):
        """
        Returns all the systems registered in our database.
        """
        proc = subprocess.Popen(
            ['/usr/share/python/alienvault-api-core/bin/alienvault/virtual_env_run', 'get_registered_systems'],
            stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        out, err = proc.communicate()
        data = json.loads(out)
        registered_systems = [(str(data[uuid]['admin_ip']), str(data[uuid]['hostname'])) for uuid in data]

        return registered_systems

    def get_allowed_values_for_registered_systems_without_vpn(self):
        proc = subprocess.Popen(
            ['/usr/share/python/alienvault-api-core/bin/alienvault/virtual_env_run', 'get_registered_systems', '-n'],
            stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        out, err = proc.communicate()
        data = json.loads(out)
        registered_systems = [(str(data[uuid]['admin_ip']), str(data[uuid]['hostname'])) for uuid in data]

        return registered_systems

    def get_allowed_values_for_avvpn_config_enabled(self):
        """Returns the allowed values for the proxy values
        """
        return self.YES_NO_CHOICES

    def make_backup(self):
        """Makes a backup.
        """
        result = AVConfigParserErrors.ALL_OK
        try:
            backup_filename = "%s%s.%s" % (BACKUP_FOLDER, os.path.basename(self.__ossim_setup_file), int(time.time()))
            shutil.copy(self.__ossim_setup_file, backup_filename)
            backup_filter = "%s%s*" % (BACKUP_FOLDER, os.path.basename(self.__ossim_setup_file))
            backup_files = sorted(glob.glob(backup_filter), key=os.path.getctime, reverse=True)
            # remove the old files.
            if len(backup_files) > MAX_BACKUP_FILES:
                files_to_remove = backup_files[MAX_BACKUP_FILES:]
                for f in files_to_remove:
                    os.remove(f)


        except Exception, e:
            # print "%s" % str(e)
            result = AVConfigParserErrors.get_error_msg(AVConfigParserErrors.EXCEPTION, str(e))
        return result


if __name__ == "__main__":
    config = AVOssimSetupConfigHandler("./tests/test_data/ossim_setup1.conf")
    print config.get_sensor_detectors()
