# -*- coding: utf-8 -*-
#
# © Alienvault Inc. 2012
# All rights reserved
#
# This code is protected by copyright and distributed under licenses
# restricting its use, copying, distribution, and decompilation. It may
# only be reproduced or used under license from Alienvault Inc. or its
# authorised licensees.


class AVConfigParserErrors():
    """Error codes class
    """
    SUCCESS = 0
    EXCEPTION = 1
    READ_ONLY = 2
    INVALID_BOOLEAN_VALUE = 3
    EXCEPTION_INVALID_ERROR_CODE = 4
    INVALID_UUID_VALUE = 5
    FILE_NOT_EXIST = 1000
    CANT_SAVE_DUE_TO_ERRORS = 1001
    CANT_SAVE_FILE_LOCKED = 1002
    CANT_SAVE_CANT_LOCK = 1003
    CANT_SAVE_FILE_HAS_CHANGED = 1004
    CANT_SET_ADMIN_GATEWAY_INVALID_ADMIN_INTERFACE = 1005
    CANT_SET_ADMIN_NETMASK_INVALID_ADMIN_INTERFACE = 1006
    INVALID_NETMASK = 1007
    CANT_SET_DOMAIN_INVALID_VALUE = 1008
    CANT_SET_EMAIL_NOTIFY_INVALID_VALUE = 1009
    CANT_SET_HOSTNAME_INVALID_VALUE = 1010
    SENSOR_INTERFACES_INVALID_VALUE = 1011
    CANT_SET_MAILSERVERRELAY_PORT_INVALID_VALUE = 1012
    CANT_SET_NTP_SERVER_INVALID_VALUE = 1013
    DATABASE_IP_CANT_BE_CHANGED_PROFILE_IS_DATABASE = 1014
    CANT_SET_DATABASE_IP_INVALID_VALUE = 1015
    INVALID_DATABASE_PASSWORD = 1016
    DETECTOR_PLUGIN_NOT_FOUND = 1017
    MONITOR_PLUGIN_NOT_FOUND = 1018
    INVALID_MSERVER_TUPLE = 1019
    INVALID_SENSOR_NAME = 1020
    CANT_SET_NETFLOW_REMOTE_COLLECTOR_PORT_INVALID_VALUE = 1021
    INVALID_NET = 1022
    INVALID_ALIENVAULT_REPUTATION_IP_VALUE = 1023
    SNMPD_INVALID_VALUE = 1024
    SNMPDTRAP_INVALID_VALUE = 1025
    SNMP_COMMUNITY_VALUE_INVALID = 1026
    UPDATE_PROXY_DNS_NOT_VALID = 1027
    UPDATE_PROXY_PASS_NOT_VALID = 1028
    UPDATE_PROXY_USER_NOT_VALID = 1029
    UPDATE_PROXY_PORT_NOT_VALID = 1030
    UPDATE_PROXY_NOT_VALID = 1031
    INVALID_DATABASE_USER = 1032
    INVALID_ADMIN_INTERFACE = 1033
    INVALID_EMAIL_RELAY = 1034
    INVALID_EMAIL_RELAY_PASS = 1035
    INVALID_EMAIL_RELAY_PORT = 1036
    INVALID_EMAIL_RELAY_USER = 1037
    INVALID_FRAMEWORK_HTTP_CERT = 1038
    INVALID_FRAMEWORK_HTTP_KEY = 1039
    FRAMEWORK_IP_CANT_BE_CHANGED_PROFILE_IS_FRAMEWORK = 1040
    ALMOST_ONE_DETECTOR_PLUGIN_IS_NEEDED = 1041
    DETECTOR_LIST_SHOULD_BE_A_COMMA_SEPARATED_STRING = 1042
    MONITOR_LIST_SHOULD_BE_A_COMMA_SEPARATED_STRING = 1043
    SENSOR_IP_CANT_BE_CHANGED_PROFILE_IS_SENSOR = 1044
    SERVER_IP_CANT_BE_CHANGED_PROFILE_IS_SERVER = 1045
    INVLAID_SENSOR_IP = 1046
    INVALID_VPN_NET = 1047
    INVALID_VPN_PORT = 1048
    VPN_SETTINGS_CANT_BE_CHANGED_PROFILE_NOT_SERVER = 1049
    INVALID_SERVER_IP_NOT_IN_LOCAL_IPS = 1050
    INVALID_FRAMEWORK_IP_NOT_IN_LOCAL_IPS = 1051
    CANT_SET_MAX_RETRIES_INVALID_VALUE = 1052
    FILE_CANT_BE_LOADED_CANNOT_STAT = 2000
    FILE_CANT_BE_LOADED = 2001
    FILE_NOT_LOADED = 2002
    FILE_IS_DIRTY = 2003
    INCOMPLETE_NETWORK_ENTRY = 2004

    # Non ossim_setup.conf related
    UNKNOWN_AVSYSCONFIG_ERROR = 3000
    CANNOT_SAVE_AVSYSCONFIG = 3001
    INVALID_NETWORK_INTERFACE = 3002
    INCOMPLETE_NETWORK_INTERFACE = 3003
    HOSTS_ENTRY_NOT_FOUND = 3004
    INCOMPLETE_AVVPN_ENTRY = 3005
    INVALID_AVVPN_ENTRY_FIELD = 3006
    CANNOT_LAUNCH_TRIGGERS = 3007
    CANNOT_OVERWRITE_ADMIN_IP = 3008
    CANNOT_OVERWRITE_ADMIN_IFACE = 3009

    # setup fields errors:
    VALUE_NOT_VALID_IP = 5000
    ERROR_CODE_MAP_STR = {
        0: "Success.",
        1: "Exception:",
        2: "Read only value!",
        3: "Invalid value. Please enter yes or no",
        4: "Exception (KeyError), Invalid error code",
        5: "Invalid value, Please enter a valid UUID (xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx)",
        1000: "Error. Configuration file not found",
        1001: "Error. The configuration can not be saved due to errors",
        1002: "Error. The configuration  can not be saved (File is locked)",
        1003: "Error. The configuration can not be saved (File lock failed)",
        1004: "Error. The configuration can not be be saved. File has changed while running this tool",
        1005: "Admin gateway can't be setted!. Invalid admin interface",
        1006: "Error. Please enter a valid IP address (xxx.xxx.xxx.xxx)",
        1007: "Error. Please enter a valid gateway (xxx.xxx.xxx.xxx)",
        1008: "Error. Please enter a valid domain",
        1009: "Error. Please enter a valid e-mail address",
        1010: "Error. Please enter a valid hostname",
        1011: "Erorr. Sensor interface  has an invalid value",
        1012: "Error. Please enter a valid email server relay port",
        1013: "Error. Please enter a valid ntp server (IPV4 or fqdn)",
        1014: "Error. Database IP can not be changed when running an All-in-one or Database only installation profile",
        1015: "Error. Please enter a valid IP address for the database",
        1016: "Error. Please enter a valid value for db_pass ",
        1017: "Error. Invalid detector plugin!. Detector plugin not found ",
        1018: "Error. Invalid monitor plugin! Monitor plugin not found ",
        1019: "Error. Invalid mserver string.",
        1020: "Error, Please enter a valid sensor name",
        1021: "netflow remote collector port can't be setted. Invalid value",
        1022: "Error. Invalid CIDR",
        1023: "Invalid Alienvault Reputation IP value",
        1024: "Error. Invalid snmpd value",
        1025: "Error. Invalid snmptrap value",
        1026: "Error. Invalid snmp community string",
        1027: "Error. Invalid proxy dns value. Allowed values are(disable, an IPv4 or a valid hostname)",
        1028: "Error. Invalid proxy password value.Allowed values ASCII characters {8,16}",
        1029: "Error. Invalid proxy user value.Allowed values ASCII characters {4,16} (whitespaces not allowed)",
        1030: "Error. Invalid proxy port value.Allowed values [0,65535]",
        1031: "Error. Invalid update proxy value. Allowed values: [disabled, manual, alienvault-proxy]",
        1032: "Error. Invalid database username.Allowed values ASCII characters {8,16}",
        1033: "Error. Invalid admin interface.",
        1034: "Error. Please enter a valid email relay server. ",
        1035: "Error. Please enter a valid email relay password. ",
        1036: "Error. Please enter a valid email relay port. ",
        1037: "Error. Please enter a valid email relay user. ",
        1038: "Invalid framework http certificate file",
        1039: "Invalid framework http certificate key",
        1040: "Error. Framework IP can not be changed when running an All-in-one or Framework only installation profile",
        1041: "Almost one detector plugin is needed!",
        1042: "Detector list should be a valid comma-separated string",
        1043: "Monitor list should be a valid comma-separated string",
        1044: "Error. Sensor IP can not be changed when running an All-in-one or Sensor only installation profile",
        1045: "Error. Server IP can not be changed when running an All-in-one or Server only installation profile",
        1046: "Error. Please enter a valid IP address for the sensor",
        1047: "Error.Please enter a vpn net (xxx.xxx.xxx) ",
        1048: "Error. Please enter a valid vpn port",
        1049: "Error. VPN settings can not be changed when running an Non Server profile",
        1050: "Error. Invalid Server IP value. The given IP is not a valid local IP",
        1051: "Error. Invalid Framework IP value. The given IP is not a valid local IP",
        1052: "Error. Invalid number of retries. The given value is not a valid integer",
        2000: "Cannot stat over the file.",
        2001: "File can not be loaded",
        2002: "File not loaded!",
        2004: "Incomplete network entry",
        # Non ossim_setup.conf related
        3000: "Unknown sysconfig error",
        3001: "Cannot save sysconfig",
        3002: "Invalid network interface",
        3003: "Network interface configuration is incomplete",
        3004: "Invalid entry in /etc/hosts",
        3005: "Incomplete entry in /etc/alienvault/network/vpn.conf",
        3006: "Invalid field value for entry in /etc/alienvault/network/vpn.conf",
        3007: "Cannot launch configuration triggers",
        3008: "Cannot overwrite administrator IP address",
        3009: "Cannot overwrite administrator interface",
        5000: "Please enter a valid IP address"
    }

    ALL_OK = (SUCCESS, ERROR_CODE_MAP_STR[SUCCESS])

    @staticmethod
    def get_str(error_code):
        """Returns the error code string
        """
        error_str = ""
        try:
            error_str = AVConfigParserErrors.ERROR_CODE_MAP_STR[error_code]
        except KeyError:
            error_str = AVConfigParserErrors.ERROR_CODE_MAP_STR[AVConfigParserErrors.EXCEPTION_INVALID_ERROR_CODE]
        return error_str

    @staticmethod
    def get_str_on_exception(error_code, exception):
        """Returns the error code string
        """
        return AVConfigParserErrors.get_str(error_code) + " Exception: %s" % exception

    @staticmethod
    def exit_error(code):
        """Exits and prints an error code"""
        print "=" * 100
        print "Error: " + AVConfigParserErrors.get_str(code)
        print "=" * 100
        exit(code)

    @staticmethod
    def exit_error_on_exception(code, exception_str):
        """Exits and prints an error code and its associated exception"""
        print "=" * 100
        print "Error: " + AVConfigParserErrors.get_str(code)
        print "Exception: " + exception_str
        print "=" * 100
        exit(code)

    @staticmethod
    def get_error(code):
        return code, AVConfigParserErrors.get_str(code)

    @staticmethod
    def get_error_msg(code, additional_message=""):
        """Returns a tuple (code,message)
        """
        if additional_message != "":
            return code, AVConfigParserErrors.get_str(code) + "<%s>" % additional_message
        return code, AVConfigParserErrors.get_str(code)

# if __name__ == "__main__":
#     print "Test error class"
#     print AVConfigParserErrors.get_error("hhh")
