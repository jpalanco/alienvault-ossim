# -*- coding: utf-8 -*-
#
# © Alienvault Inc. 2012
# All rights reserved
#
# This code is protected by copyright and distributed under licenses
# restricting its use, copying, distribution, and decompilation. It may
# only be reproduced or used under license from Alienvault Inc. or its
# authorised licensees.

import socket
import hashlib
import re
import commands
import os
import subprocess
from scp import SCPClient
from functools import partial
import fcntl
import struct
import paramiko
import tarfile
DEFAULT_FPROBE_CONFIGURATION_FILE = "/etc/default/fprobe"
DEFAULT_NETFLOW_REMOTE_PORT = 555
SIOCGIFNETMASK = 0x891b
MSERVER_REGEX = re.compile("(?P<server_ip>(?:[\d]{1,3})\.(?:[\d]{1,3})\.(?:[\d]{1,3})\.(?:[\d]{1,3})),(?P<server_port>[0-9]{1,5}),(?P<send_events>True|False|Yes|No),(?P<allow_frmk_data>True|False|Yes|No),(?P<server_priority>[0-5]),(?P<frmk_ip>(?:[\d]{1,3})\.(?:[\d]{1,3})\.(?:[\d]{1,3})\.(?:[\d]{1,3})),(?P<frmk_port>[0-9]{1,5})")
FPROBE_PORT_REGEX = re.compile("FLOW_COLLECTOR=\"\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}:(?P<port>\d+)\"")
EMAIL_REGEX = re.compile("^[-!#$%&'*+/0-9=?A-Z^_a-z{|}~](\.?[-!#$%&'*+/0-9=?A-Z^_a-z{|}~])*@[a-zA-Z](-?[a-zA-Z0-9])*(\.[a-zA-Z](-?[a-zA-Z0-9])*)+$")
VPN_NET_REGEX = re.compile("^(?P<vpnnet>\d{1,3}\.\d{1,3}\.\d{1,3})$")
def is_ipv6(string_ip):
    """Check whether the given string is an valid ip v6
    """
    ipv6 = True
    try:
        socket.inet_pton(socket.AF_INET6, string_ip)
    except:
        ipv6 = False
    return ipv6


def is_ipv4(string_ip):
    """Check whether the given string is an valid ip v4
    """
    ipv4 = True
    try:
        socket.inet_pton(socket.AF_INET, string_ip)
    except:
        ipv4 = False
    return ipv4

def is_valid_ip_address(value):
    """Check whether an internet address is valid
    """
    return (is_ipv4(value) or is_ipv6(value))


def is_valid_CIDR(value):
    """Check if a CIDR is valid.(only for ipv4)
    """
    ip_addr = ""
    netlen = 0
    if not value:
        return False
    if "/" in value:
        ip_addr, netlen = value.split("/", 1)
        try:
            netlen = int(netlen)
        except ValueError:
            netlen = 0
            return False
    else:
        ip_addr, netlen = value, 0
    if is_ipv4(ip_addr) and netlen in range(0, 33):
        return True
    return False


def md5sum(filename):
    if not filename:
        return None
    if not os.path.isfile(filename):
        return None
    with open(filename, mode='rb') as f:
        d = hashlib.md5()
        for buf in iter(partial(f.read, 128), b''):
            d.update(buf)
    return d.hexdigest()


def ipv4_cidr_to_netmask(bits):
    """ Convert CIDR bits to netmask
    Full credit to this function is for:
    http://www.zoobey.com/index.php/resources/all-articles-list/555-python-validate-ipv4-netmasks
 
    """
    netmask = ''
    for i in range(4):
        if i:
            netmask += '.'
        if bits >= 8:
            netmask += '%d' % (2 ** 8 - 1)
            bits -= 8
        else:
            netmask += '%d' % (256 - 2 ** (8 - bits))
            bits = 0
    return netmask


def is_net_mask(value):
    """Check whether a given string is a valid netmask
    Full credit to this function is for:
    http://www.zoobey.com/index.php/resources/all-articles-list/555-python-validate-ipv4-netmasks
    """
    result = (value in map(lambda x: ipv4_cidr_to_netmask(x), range(0, 33)))
    return result


def is_valid_domain(value):
    """Check whether a string is a valid domain name
    ftp://ftp.rfc-editor.org/in-notes/rfc1034.txt
    an IP address is a valid domain name, but shall we allow it? No
    
    """
    if not value:
        return False
    if is_ipv4(value):
        return False
    if value == "":
        return False
    if value.startswith(" "):
        return False
    if len(value) > 63:
        return False
    if not is_ascii_characters(value):
        return False
    if re.match('[a-zA-Z\d-]{,63}(\.[a-zA-Z\d-]{1,63})*', value):
        return True
    return False

def is_valid_email(email):
    """Validate an email. 
    http://en.wikipedia.org/wiki/Email_address
    Do not allow this:
    Special characters are allowed with restrictions. They are:
    Space and "(),:;<>@[\] (ASCII: 32, 34, 40, 41, 44, 58, 59, 60, 62, 64, 91–93)
    The restrictions for special characters are that they must only be used when 
    contained between quotation marks, and that 2 of them (the backslash \ and 
    quotation mark " (ASCII: 32, 92, 34)) must also be preceded by a backslash \ (e.g. "\\\"").
    """
    max_total_len = 256
    if not email:
        return False
    if not is_ascii_characters(email):
        return False
    if len(email) > max_total_len:
        return False
    if len(email) > 7:
        data = EMAIL_REGEX.match(email)
        if data:
            return True
    return False


def is_valid_hostname_rfc1123(hostname):
    """Validate a hostname
        By RFC1123
    """
    if not is_ascii_characters(hostname):
        return False
    if not hostname:
        return False
    if len(hostname) > 63:
        return False
    #allowed = re.compile("(?!-)[A-Z\d-]{1,63}(?<!-)$", re.IGNORECASE)
    allowed = re.compile(r'^[a-zA-Z0-9](([a-zA-Z0-9\-]*[a-zA-Z0-9]+)*)$',re.IGNORECASE)
    return allowed.match(hostname) is not None



def is_valid_dns_hostname(hostname):
    """Validate a hostname
    Full credit for this function:
    http://stackoverflow.com/questions/2532053/validate-a-hostname-string
    man set_hostname:
    SUSv2 guarantees that "Host names are limited to 255 bytes".  POSIX.1-2001 guarantees that "Host names (not including the terminating null byte) are limited
    to HOST_NAME_MAX bytes".  On Linux, HOST_NAME_MAX is defined with the value 64, which has been the limit since Linux 1.0 (earlier kernels imposed a limit of
    8 bytes).

    """
    if not is_ascii_characters(hostname):
        return False
    if not hostname:
        return False
    if len(hostname) > 63:
        return False
    if hostname[-1:] == ".":
        hostname = hostname[:-1]  # strip exactly one dot from the right, if present
    #allowed = re.compile("(?!-)[A-Z\d-]{1,63}(?<!-)$", re.IGNORECASE)
    allowed = re.compile(r'^[a-zA-Z0-9](([a-zA-Z0-9\-]*[a-zA-Z0-9]+)*)$',re.IGNORECASE)
    return all(allowed.match(x) for x in hostname.split("."))


def is_valid_port(value):
    """Checks if the value could be a valid port
    """
    if not value:
        return False
    result = True
    try:
        port = int(value)
        if port < 0 or port > 65535:
            result = False
    except:
        result = False
    return result


def is_valid_vpn_net(value):
    if not value:
        return False
    if value == "":
        return False
    data = VPN_NET_REGEX.match(value)
    if data:
        if data.groupdict().has_key('vpnnet'):
            return True
    return False

def is_ascii_characters(value):
    """Checks whether a string is compound only by ascii characters 
    """
    if not value:
        return False
    data = re.match('^([\x00-\x7F]+)$', value)
    if data:
        return True
    return False


def is_allowed_password(value, minsize=8, maxsize=16):
    """Checks whether the given database password is allowed
    ASCII characters.
    Length: 8 - 16
    """

    if not value:
        return False
    if ' ' in value:
        return False
    size = len(value)
    if is_ascii_characters(value) and size in range(minsize, maxsize + 1):
        return True
    return False

def is_allowed_username(user, minsize=4, maxsize=16):
    """Checks whether the given user, it's a valid
    database user.
    """
    if not user:
        return False
    if ' ' in user:
        return False
    size = len(user)
    if is_ascii_characters(user) and size in range(minsize, maxsize + 1):
        return True
    return False


def is_sensor_allowed_name(name, minsize=4, maxsize=16):
    """Checks whether the given name, it's a valid
    sensor name
    """
    if not name:
        return False
    if ' ' in name:
        return False
    size = len(name)
    if is_ascii_characters(name) and size in range(minsize, maxsize + 1):
        return True
    return False


def is_snmp_community_allowed(value, minsize=4, maxsize=16):
    """Checks whether the given value is a valid snmp communty string
    ASCII characters excetp @
    """
    if not value:
        return False
    size = len(value)
    if is_ascii_characters(value) and '@' not in value and size in range(minsize, maxsize + 1):
        return True
    return False

def is_boolean(s):
    """Checks whether the given string is a valid boolean 
    value
    """
    if not  isinstance(s, basestring):
        s = "%s" % s
    if not s:
        return False
    s = s.lower()
    allowed_boolean = ["1", "yes", "true", "si", "on", "0", "false", "off", "no", "enabled", "disabled"]
    if s in allowed_boolean:
        return True
    return False

def get_current_nameserver():
    """Returns the current nameserver"""
    current_nameserver = ""
    try:
        resolv_config = open("/etc/resolv.conf", "r")
        for line in  resolv_config.readlines():
            data = re.match('nameserver\s+(?P<nameserver_ip>\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}).*', line)
            if data:
                try:
                    if current_nameserver != "":
                        current_nameserver += ","
                    current_nameserver += data.groupdict()['nameserver_ip']
                except:
                    current_nameserver = ""
    except:
        pass
    return current_nameserver


def get_current_gateway(interface):
    """Returns the current gateway for a given interface"""
    cmd = "ip route list dev " + interface + " | awk ' /^default/ {print $3}'"
    result = ""
    try:
        status, output = commands.getstatusoutput(cmd)
        if status == 0:
            result = output
    except:
        pass
    return result


def get_network_mask_for_iface(ifname):
    """Retrieves the netmask for a given interface
    """
    netmask = ""
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        netmask = fcntl.ioctl(s, SIOCGIFNETMASK, struct.pack('256s', ifname))[20:24]
        netmask = socket.inet_ntoa(netmask)
    except:
        pass
    return netmask


def get_current_domain():
    """Returns the current domain"""
    current_domain = ""
    try:
        domainfile = open("/etc/mailname", "r")
        current_domain = domainfile.readline().rsplit()[0]
        domainfile.close()
    except:
        pass
    return current_domain


def get_current_hostname():
    """Returns the current domain"""
    current_hostname = ""
    try:
        hostname_file = open("/etc/hostname", "r")
        current_hostname = hostname_file.readline().rsplit()[0]
        hostname_file.close()
    except:
        pass
    return current_hostname


def get_current_plugins_by_type(plugin_type):
    cmd = "rgrep  \"type=%s\"  /etc/ossim/agent/plugins/*.cfg  | cut -d: -f1 |awk {'print $1'}" % plugin_type
    plugin_list = []
    try:
        #print cmd
        status, output = commands.getstatusoutput(cmd)
        if status == 0:
            for line in output.split('\n'):
                basename = os.path.basename(line)
                if re.match("([0-9\w\-]+\.cfg)", basename):
                    pname = os.path.splitext(basename)[0]  
                    plugin_list.append(pname)
    except Exception, e:
        print "error: %s" % str(e)
    return plugin_list


def get_current_detector_plugin_list():
    """Retrieves the plugin detector list by reading 
    the plugin folder
    """
    plist = get_current_plugins_by_type("detector")
    final_plist =[]
    for pname in plist:
        pname = re.sub("_eth\d+", "", pname)
        if pname not in final_plist:
            final_plist.append(pname)
    return final_plist


def get_current_monitor_plugin_list_clean():
    """Retrieves the plugin detector list by reading 
    the plugin folder
    Do not remove the -monitor keyword
    """
    return get_current_plugins_by_type("monitor")


def get_current_monitor_plugin_list():
    """Retrieves the plugin detector list by reading 
    the plugin folder
    """
    plist = get_current_plugins_by_type("monitor")
    final_plist =[]
    for pname in plist:
        pname = re.sub("-monitor", "", pname)
        if pname not in final_plist:
            final_plist.append(pname)
    return final_plist
        
def check_mserver_string(value):
    """
    SERVER_IP;PORT;SEND_EVENTS(True/False);ALLOW_FRMK_DATA(True/False);PRIORITY (0-5);FRMK_IP;FRMK_PORT
    192.168.2.22,40001,True,True,1,192.168.2.22,40003
    """
    if MSERVER_REGEX.match(value):
        return True
    return False


def get_default_netflow_remote_port():
    """Retrieves the defualt netflow remote port 
    """
    default_port = DEFAULT_NETFLOW_REMOTE_PORT
    if os.path.isfile(DEFAULT_FPROBE_CONFIGURATION_FILE):
        frobe_config = open(DEFAULT_FPROBE_CONFIGURATION_FILE, 'r')
        for line in  frobe_config.readlines():
            data = FPROBE_PORT_REGEX.match(line)
            if data:
                default_port = data.groupdict()['port']
    return default_port


def get_is_professional():
    """Check if the current version is pro
    """
    #cmd = "export PERL5LIB=/usr/share/alienvault-center/lib ; perl -M\"Avrepository 'get_current_repository_info'\" -e 'my %sysconf=Avrepository::get_current_repository_info() ; print $sysconf{'distro'}'"
    cmd = "dpkg -l alienvault-professional | grep \"^ii\""
    rtvalue = False
    try:
        status, output = commands.getstatusoutput(cmd)
        if status == 0:
            rtvalue = True
#        if output:
#            if re.match("([\S]+\-pro)", output):
#                rtvalue = True
    except Exception, e:
        print "error: %s" % str(e)
    return rtvalue

def get_systems_without_vpn():
    """Get the list with the systems without vpn ip addresss
    """
    cmd = "alienvault-api get_registered_systems --list"
    systems = []
    try:
        status, output = commands.getstatusoutput(cmd)
        if status == 0:
            for line in output.split('\n'):
                splitted = line.split(";")
                if splitted[3] == '':
                    systems.append(splitted[1] + '-' + splitted[2])
    except Exception, e:
        print "error: %s" % str(e)

    return systems

def createSSHClient(server, port, user, password):
    """Full credit for this function:
    http://stackoverflow.com/questions/250283/how-to-scp-in-python
    """
    client = paramiko.SSHClient()
    client.load_system_host_keys()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(server, port, user, password)
    return client


def get_remote_file_using_ssh(remote_ip, remote_port, remote_user, remote_pass, remote_file, local_file):
    """Gets a remote file usin ssh protocol
    """
    ssh = createSSHClient(remote_ip, int(remote_port), remote_user, remote_pass)
    scp = SCPClient(ssh.get_transport())
    scp.get(remote_path=remote_file, local_path=local_file)

def configure_vpn(server_ip, server_ssh_port, server_user, server_pass, local_ip):
    """
    cmd=alienvault-reconfig --add_vpnnode=192.168.2.23
    fichero generado /etc/openvpn/nodes/192.168.2.23.tar.gz
    destino: /etc/openvpn/ y descomprimir.
    """
    rt = False
    try:
        cmd = "alienvault-reconfig --add_vpnnode=%s " % local_ip
        tmp_dir = "/tmp/"
        end_vpnfilename = "%s.tar.gz" % local_ip
        vpnfile = "/etc/openvpn/nodes/%s" % end_vpnfilename
        dir_to_extract = "/etc/openvpn/"
        if subprocess.call(cmd, shell=True) == 0:#success
            get_remote_file_using_ssh(server_ip, server_ssh_port, server_user, server_pass, vpnfile, tmp_dir)
            if os.path.isfile(tmp_dir + end_vpnfilename):
                tfile = tarfile.open(tmp_dir + end_vpnfilename, 'r:gz')
                tfile.extractall(dir_to_extract)
                os.remove(tmp_dir + end_vpnfilename)
                rt = True
    except Exception, e:
        print str(e)
    return rt


#if __name__ == "__main__":
    #configure_vpn("192.168.2.22", 22, "root", "alien4ever", "192.168.2.25")
    #get_remote_file_using_ssh("192.168.2.22", "22", "root", "alien4ever", "/etc/openvpn/nodes/192.168.2.23.tar.gz", "/tmp/")
