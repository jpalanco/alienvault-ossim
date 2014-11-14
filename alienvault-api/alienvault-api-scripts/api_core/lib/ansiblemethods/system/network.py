# -*- coding: utf-8 -*-
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

import copy
import os
import re
import traceback
import ipaddress
import time
from xml.dom.minidom import parseString

import api_log
from ansiblemethods.ansiblemanager import Ansible, PLAYBOOKS
from ansiblemethods.helper import read_file, \
    ansible_is_valid_response, \
    ansible_is_valid_playbook_response
from ansiblemethods.sensor.network import get_sensor_interfaces,\
    set_sensor_interfaces
from ansiblemethods.system.system import get_av_config, set_av_config
from ansiblemethods.system.system import ansible_run_async_reconfig

ansible = Ansible()


def get_iface_list(system_ip):
    """Returns the interface list for a given ip"""
    dresult = {}
    host_list = []
    host_list.append(system_ip)
    response = ansible.run_module(host_list, "av_setup", "")

    if system_ip in response['dark']:
        return (False, "Error getting interfaces: " + response['dark'][system_ip]['msg'])
    else:
        # Get admin network information.
        data_to_retrieve = {'general_interface': '', 'sensor_interfaces': ''}

        success, av_config_response = get_av_config(system_ip, data_to_retrieve)

        if not success:
            return False, "Error trying to read administrative interface: %s" % str(av_config_response)
        admin_interface = av_config_response.get('general_interface', '')
        sensor_interfaces = av_config_response.get('sensor_interfaces', '')
        # Check for the promisc flag
        for iface in response['contacted'][system_ip]['ansible_facts']['ansible_interfaces']:
            #This only works on Linux
            iface_data = response['contacted'][system_ip]['ansible_facts']['ansible_' + iface]
            dresult[iface] = {'promisc': iface_data['promisc']}
            if iface_data.has_key('ipv4'):
                dresult[iface]['ipv4'] = copy.deepcopy(iface_data['ipv4'])

            dresult[iface]['role'] = 'disabled'

            if iface != 'lo':
                # Is this the admin interface?
                if iface == admin_interface:
                    dresult[iface]['role'] = 'admin'
                # Is this a monitoring interface?
                elif iface in sensor_interfaces:
                    dresult[iface]['role'] = 'monitoring'
                # Is this a log management interface?
                elif iface_data['active'] == True and 'ipv4' in iface_data: 
                    dresult[iface]['role'] = 'log_management'

        return (True, dresult)


def get_iface_stats(system_ip):
    """
    Return  dictionary key => iface, value =  (rxbytes,txbytes)
    e.g.: { "lo": (1000,2000)}
    """
    dresult = {}
    response = ansible.run_module([system_ip], "av_setup","filter=ansible_interfaces")
    if system_ip in response ['dark'] :
        return(False, "get_iface_list " + response['dark'][system_ip]['msg'])
    else:
        for iface in  response['contacted'][system_ip]['ansible_facts']['ansible_interfaces']:
            devpath = "/sys/class/net/" + iface
            (rrx, rxcontent) = read_file(system_ip, devpath + "/statistics/rx_bytes")
            (rtx, txcontent) = read_file(system_ip, devpath + "/statistics/tx_bytes")
            if rrx == True and rtx == True:
                rx = int(rxcontent.rstrip(os.linesep))
                tx = int(txcontent.rstrip(os.linesep))
            dresult[iface] = {"RX":rx, "TX":tx}
    return(True,dresult)


def set_iface_promisc_status(system_ip, iface, status):
    """ Calls Ansible to set the promisc flag of a interface """
    if status:
        flag = "promisc up"
    else:
        flag = "promisc"

    response = ansible.run_module([system_ip], 'command','/sbin/ifconfig %s %s' % (iface, flag))
    # Check error
    if system_ip in response['dark']:
        return(False, "Error setting interface promisc status: " + response['dark'][system_ip]['msg'])
    # Verify the rc code in response
    if response['contacted'][system_ip]['rc'] != 0:
        return(False, "Error setting interface promisc status: " + response['contacted'][system_ip]['stderr'])

    return(True, 'ok')


def get_iface_traffic(sensor_ip, sensor_iface,timeout=2):
    """
    @param sensor_ip: The sensor IP
    @param sensor_iface: Interface machine to check
    @param timeout: Timeout to tshark. Default 2 seconds
    """
    try:
        ansible_iface = "ansible_%s" % sensor_iface
        response = ansible.run_module ([sensor_ip], 'av_setup',"filter=" + ansible_iface)
        if sensor_ip in response['dark']:
            return (False,"check_iface_traffic : " +  response['dark'][sensor_ip]['msg'])
        # Get the MAC to cosntruct the filter
        ifacert = response['contacted'][sensor_ip]['ansible_facts'].get(ansible_iface,None)
        if ifacert is None:
            return (False,"get_iface_traffic: interface '%s' doesn't exists" % sensor_iface)
        
        mac = ifacert.get('macaddress',None)
        if mac is None:
            return (False,"get_iface_traffic: interface '%s' doesn't have mac address" % sensor_iface)

        # Construct the params
        params = "-i %s -T psml -f \"not broadcast and not multicast and not ether dst %s and not ether src %s \" -a duration:%d" % \
                 (sensor_iface,mac,mac,timeout)
        response = ansible.run_module([sensor_ip],"command","/usr/bin/tshark " + params)
        # Check result
        if sensor_ip in response['dark']:
            return (False,"check_iface_traffic : " +  response['dark'][sensor_ip]['msg'])
        # Parse the response
        packets = parseString (response['contacted'][sensor_ip]['stdout'])
        if packets.documentElement.tagName != "psml":
            return (False,"check_iface_traffic : Bad XML response\n" + packets.toprettyxml())
        #Check if we have packets inside
        for children in packets.documentElement.childNodes:
            if children.nodeType == children.ELEMENT_NODE and children.tagName == "packet":
                return (True,{'has_traffic': True})
        return (True,{'has_traffic': False})
    except Exception as e:
        return (False,"Ansible Error: " + str (e) +"\n" + traceback.format_exc())


def get_conf_network_interfaces(system_ip, root=None,store_path = False):
    """
    Return a dict index by interface name with a value dictionary with netmask, address and gateway
    >>> from ansiblemgr import Ansible
    >>> a = Ansible()
    >>> a.get_conf_network_interfaces("192.168.60.5")
    (True, {u'lo': {}, u'bond0': {u'netmask': u'255.255.255.0', u'address': u'10.0.0.1'}, u'eth1': {u'netmask': u'255.255.255.0', u'address': u'192.168.100.5'}, u'eth0': {u'netmask': u'255.255.255.0', u'gateway': u'192.168.60.1', u'address': u'192.168.60.5'}})
    """
    rt = True
    r = re.compile(r'(/files/etc/network/interfaces/iface\[\d+\])/(netmask|address|gateway)')
    try:
        response = ansible.run_module(host_list=[system_ip], module="av_augeas",args="commands='match /files/etc/network/interfaces/iface/*  match /files/etc/network/interfaces/iface' validate_filepath=no")
        if system_ip in response['dark']:
            return (False,"get_conf_network_interfaces " + response['dark'][system_ip]['msg'])
        else:
            jsonresponse =  response['contacted'][system_ip]['result']
            # Now I have to process each entry an return a dict of "iface": {address:'',netmask:'',gateway:''}"
            # Verify the componentes
            ifaces = {}
            for entry in jsonresponse[0][1]:
                m = r.match(entry['label'])
                if m is not None:
                    t = ifaces.get(m.group(1),{})
                    t[m.group(2)] = entry['value']
                    ifaces[m.group(1)] = t
            result = {}
            for entry in jsonresponse[1][1]:
                key = entry['label']
                t = ifaces.get(key,{})
                if store_path:
                    t['path'] = key
                result[entry['value']] = t
    except Exception,e:
        rt = False
        result = "Can't obtain network configuracion: " + str(traceback.format_exc())
    return (rt, result)


def conf_update_iface(system_ip,iface,path,ipaddr,netmask,gateway,gw_paths=[]):
    cmd = ""
    if ipaddr:
        cmd = cmd + " set %s/address %s" % (path,ipaddr)
    if netmask:
        cmd = cmd + " set %s/netmask %s" % (path,netmask)
    if gateway:
        cmd = cmd + " set %s/gateway %s" % (path,gateway)
        for (k,v) in gw_paths:
            if k != iface:
                cmd = cmd + " rm %s/gateway " % v

    response = ansible.run_module(host_list=[system_ip],
                                  module="av_augeas",
                                  args="commands='%s' validate_filepath=no" % cmd)
    if system_ip in response['dark'] or response['contacted'][system_ip].get('failed',False) == True:
        return (False, response['dark'][system_ip]['msg'])
    else:
        return (True,'')


def conf_new_iface(system_ip,iface,ipaddr,netmask,gateway,gw_paths=[]):
    cmd = "set /files/etc/network/interfaces/auto[last()+1]/1 %s " % iface
    cmd = cmd + "set /files/etc/network/interfaces/iface[last()+1] %s " % iface
    cmd = cmd + "set /files/etc/network/interfaces/iface[.=\\\"%s\\\"]/family inet " % iface
    cmd = cmd + "set /files/etc/network/interfaces/iface[.=\\\"%s\\\"]/method static " % iface
    cmd = cmd + "set /files/etc/network/interfaces/iface[.=\\\"%s\\\"]/address %s " % (iface,ipaddr)
    cmd = cmd + "set /files/etc/network/interfaces/iface[.=\\\"%s\\\"]/netmask %s " % (iface,netmask)
    if gateway is not None:
        cmd = cmd + "set /files/etc/network/interfaces/iface[.=\\\"%s\\\"]/gateway %s " %(iface,gateway)
        # I want this op atomic. I'm going to make the "rm"
        for (k,v) in gw_paths:
            if k != iface:
                cmd = cmd + " rm %s/gateway " % v

    response = ansible.run_module(host_list=[system_ip],
        module="av_augeas",args="commands='%s' validate_filepath=no" % cmd)

    if system_ip in response['dark'] or response['contacted'][system_ip].get('failed',False) == True:
        return (False, response['dark'][system_ip])
    else:
        return (True,'')


def _gw_verify(iface,ipaddr=None,mask=None,gw=None,ifaces={}):
    entry = {'address': ipaddr, 'netmask': mask, 'gateway': gw}
    # Now I need to verify severals test here
    confiface = ifaces.get(iface)
    if confiface is None:
        if not (entry.get('address') or entry.get('netmask') or entry.get('gateway')):
            return False # The entry MUST BE full
    else:
        if entry.get('address') is None:
            entry['address'] = confiface.get('address')
        if entry.get('netmask') is None:
            entry['netmask'] = confiface.get('netmask')
    if not (entry.get('address') or entry.get('netmask') or entry.get('gateway')):
        return False # The entry MUST BE full
    # Now , construct the network and verify the IP
    ipiface = ipaddress.ip_interface(unicode("%s/%s" % (entry['address'],entry['netmask'])))
    if ipaddress.ip_address(unicode(entry.get('gateway'))) in ipiface.network:
        return True
    else:
        return False


def set_conf_iface(system_ip,iface,ipaddr=None,netmask=None,gateway=None):
    """
    Create / modify a interface setting
    """

    rc = True
    msg = ''
    try:
        gw_ip = None
        if (ipaddr or netmask or gateway) is None:
            raise Exception("Not parameter to change")
        if netmask:
            ipaddress.ip_address(unicode(netmask))
        if ipaddr:
            ip = ipaddress.ip_address(unicode(ipaddr))
        if gateway:
            gw_ip = ipaddress.ip_address(unicode(gateway))

        # First, load all the configuration
        # Then create / update the entry
        # Verify that only one gateway is present
        # Commit changes
        (result,response ) = get_conf_network_interfaces (system_ip,store_path=True)
        # Here I need to verify that the gateway is correctly configured
        if gateway:
            rc = _gw_verify(iface,ipaddr,netmask,gateway,response)
        if rc == False:
            raise Exception("We can't reach the gateway with the current configuration")
        # Iterate to search the path to gateway
        gatewaypath = []
        for (k,v)  in response.items():
            if v.get('gateway') is not None:
                gatewaypath.append((k,v.get('path')))

        if result == False:
            raise Exception(response)
        # Now, I need to test if the iface exists => has a entry in response
        # or I need to create another entry under the tree.
        # Also, remember the "auto" entry
        if response.has_key(iface):
            (rc,msg) = conf_update_iface (system_ip,iface,response[iface]['path'],ipaddr,netmask,gateway, gatewaypath)
        else:
            (rc,msg) = conf_new_iface (system_ip, iface,ipaddr,netmask,gateway, gatewaypath)
        if rc != True:
            raise Exception("Can't configure %s iface with ipaddr => %s netmask => %s msg:%s" % (ipaddr,netmask,msg))
    except Exception,e:
        rc =  False
        msg = "Can't configure interface: " + str(traceback.format_exc ())
    return (rc, msg)

def delete_conf_iface(system_ip,iface,ifacepath=None):
    """ Delte a iface in /etc/network/interfaces
    """
    if ifacepath is None:
        # Obtain all interfaces with path
        (success,result) = get_conf_network_interfaces(system_ip,store_path=True)
        if not success:
            return (False,"Can't obtain configures ifaces msg: " + result)
        if iface not in result.keys():
            return (False,"Interface '%s' doens't exists" % iface)
        else:
            ifacepath = result[iface]['path']
    # Prepare command and exec
    # I also need the auto section. Well obtain the iface index:
    ifindex = re.match(r'/files/etc/network/interfaces/iface\[(\d+)\]',ifacepath).group(1)
    response=ansible.run_module([system_ip],module="av_augeas",args="commands='rm %s  rm /files/etc/network/interfaces/auto/*[.=\\\"%s\\\"] rm /files/etc/network/interfaces/auto[count(*)=0]'  validate_filepath=no" % (ifacepath,iface))
    if system_ip in response['dark']:
        return (False, response['dark'][system_ip]['msg'])
    elif response['contacted'][system_ip].get('failed',False) == True:
        return (False, response['contacted'][system_ip])

        
        
    else:
        return (True,"iface %s deleted from /etc/network/interfaces" % iface)

def resolve_dns_name(system_ip, dns_name):
    """
    @param system_ip: The system IP
    @param dns_name: name to resolve
    @return A tuple (sucess|error, data|msgerror)
    """
    response = ansible.run_module(host_list=[system_ip],
                                  module="shell",
                                  args="executable=/bin/bash host %s" % dns_name)
    if system_ip in response['dark']:
        api_log.error("resolve_dns_name:  %s" % response['dark'])
        return (False, "Error connecting to %s" % system_ip)

    data = (response['contacted'][system_ip]['rc'] == 0)
    return (True, data)

def make_tunnel(system_ip, local_server_id, password=""):
    """
    Make a tunnel with system_ip
    """
    host = '127.0.0.1'
    src='/etc/openvpn/nodes/%s.tar.gz' % system_ip
    dst='/tmp/'
    rt = True
    end_points = None
    try:
        end_points = {}
        if not os.path.exists(src):
            response = ansible.run_module(host_list=[host],
                                          module='av_vpn',
                                          args={'system_ip':system_ip})
            # 1 - Create the server configuration
            print "Creating node vpn configuration..."
            success, msg = ansible_is_valid_response(host, response)
            if not success:
                return False, msg
            end_points = response['contacted'][host]['data']
        else:#VPN configuration for the given node already exists
            with open("/etc/openvpn/ccd/%s" % system_ip, "r") as client_file:
                for line in client_file.readlines():
                    matchobj = re.match("ifconfig-push (?P<client_ip>\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}) (?P<client_ip2>)\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}", line)
                    if matchobj is not None:
                        end_points['client_end_point1'] = matchobj.groupdict()['client_ip']
                        end_points['client_end_point2'] = matchobj.groupdict()['client_ip2']
        if 'client_end_point1' not in end_points:
            return False, "End points are empty"
            _
        # Restart the openvpn server
        print "Restarting openvpn server..."
        response = ansible.run_module(host_list=[host], module="service", args="name=openvpn state=restarted")
        success, msg = ansible_is_valid_response(host, response)
        if not success:
            return False, msg
        print "Retrieving the local vpn server ip..."
        # 2- Retrieve the openvpn server ip
        response = ansible.run_module(host_list=[host],module="av_system_info",args="")
        success,msg = ansible_is_valid_response(host, response)
        if not success:
            return False, "[make_tunnel] Cannot retrieve the current vpn server ip: %s" % str(msg)
        server_vpn_ip = None
        frameworkd_vpn_ip = None
        try:
            server_vpn_ip = response['contacted'][host]['data']['vpn_ip']
            frameworkd_vpn_ip = server_vpn_ip
        except:
            return False,"[make_tunnel] tun0 doesn't exists. <%s>" % str(response)
        # 3 - Copy the cliente configuration to its destination
        print "Copying the openvpn configuration to the node "
        args = {'src': src, 'dest': dst}
        response = ansible.run_module(host_list=[system_ip], module='copy',args=args, ans_remote_pass=password, ans_remote_user="root")
        success, msg = ansible_is_valid_response(system_ip, response)
        if not success:
            return False, msg
        print "Uncompress the node configuration..."
        evars = {"tar_file": "%s.tar.gz" % system_ip,
                 "target": "%s" % system_ip}
        response =  ansible.run_playbook(playbook=PLAYBOOKS['UNTAR_VPN_AND_START'],
                                    host_list=[system_ip],
                                    extra_vars=evars,
                                    ans_remote_pass=password, ans_remote_user="root")

        success, msg = ansible_is_valid_playbook_response(system_ip,response)
        if not success:
            return False, msg

        # 4 - Set the ossim_setup.conf variables
        #ossim_setup_values = {'server_server_ip':server_vpn_ip,
        #                      'framework_framework_ip':frameworkd_vpn_ip}
        #print "Setting the server_ip and framework ip node values..."
        #success, msg = set_av_config(system_ip,ossim_setup_values)
        #if not success:
        #    return False, "Error setting the vpn values on the remote host: %s" % msg
        #print "Reconfiguring the node..."
        ## 5 - Run alienvault reconfig in a asynchrnous way
        #success, msg = ansible_run_async_reconfig(system_ip)

        #if not success:
        #    return False, "Error running alienvault-reconfigure after the vpn changes %s" % str(msg)
        print "Restarting remote openvpn service..."
        response = ansible.run_module(host_list=[system_ip], module="service", args="name=openvpn state=restarted",ans_remote_pass=password , ans_remote_user="root")
        success, msg = ansible_is_valid_response(system_ip, response)
        if not success:
            return False, msg
        # Retrieve remote system information. We need to know the remote system profile
        response = ansible.run_module(host_list=[system_ip], module="av_system_info", args="",ans_remote_pass=password, ans_remote_user="root")
        success, msg = ansible_is_valid_response(system_ip, response)
        if not success:
            return False, msg
        try:
            remote_profiles = response['contacted'][system_ip]['data']['profile']
            remote_server_id = None
            if 'server_id' in response['contacted'][system_ip]['data'] and response['contacted'][system_ip]['data']['server_id'] is not None:
                remote_server_id = response['contacted'][system_ip]['data']['server_id']
                remote_server_id = remote_server_id.replace('-','')

        except Exception as err:
            return False, "Error getting the remote profile:  %s" % str(err)

        # UPDATE LOCAL SERVER TABLE: Set the local vpn ip
        cmd = """echo \"update alienvault.server set ip=inet6_pton('%s') where id=unhex('%s');\" | ossim-db""" % (server_vpn_ip,local_server_id.upper())
        response = ansible.run_module(host_list=[host], module="shell", args=cmd)
        success, msg = ansible_is_valid_response(host, response)
        if not success:
            return False, msg
        if response['contacted'][host]['rc'] !=0:
            return False, response['contacted'][host]['stderr']


        if "server" in remote_profiles:
            # IF SERVER PROFILE, UPDATE LOCAL SERVER TABLE AS WELL
            cmd = """echo \"update alienvault.server set ip=inet6_pton('%s') where id=unhex('%s');\" | ossim-db""" % (end_points['client_end_point1'],remote_server_id.upper())
            response = ansible.run_module(host_list=[host], module="shell", args=cmd)
            success, msg = ansible_is_valid_response(host, response)
            if not success:
                return False, msg

            if response['contacted'][host]['rc'] !=0:
                return False, response['contacted'][host]['stderr']
            
            # UPDATE REMOTE SERVER TABLE
            print "Remote profile server found... configuring it"
            print "Set vpn server ip on remote db..."
            cmd = """echo \"update alienvault.server set ip=inet6_pton('%s') where id=unhex('%s');\" | ossim-db""" % (server_vpn_ip,local_server_id.upper())
            response = ansible.run_module(host_list=[system_ip], module="shell", args=cmd,ans_remote_pass=password, ans_remote_user="root")
            success, msg = ansible_is_valid_response(system_ip, response)
            if not success:
                return False, msg

            if response['contacted'][system_ip]['rc'] !=0:
                return False, response['contacted'][system_ip]['stderr']
            print "Set local vpn ip on remote db ..."
            cmd = """echo \"update alienvault.server set ip=inet6_pton('%s') where id=unhex('%s');\" | ossim-db""" % (end_points['client_end_point1'],remote_server_id.upper())
            response = ansible.run_module(host_list=[system_ip], module="shell", args=cmd,ans_remote_pass=password, ans_remote_user="root")
            success, msg = ansible_is_valid_response(system_ip, response)
            if not success:
                return False, msg

            if response['contacted'][system_ip]['rc'] !=0:
                return False, response['contacted'][system_ip]['stderr']


            # UPDATE REMOTE SYSTEM TABLE
            cmd = """echo \"update alienvault.system set vpn_ip=inet6_pton('%s') where server_id=unhex('%s');\" | ossim-db""" % (server_vpn_ip,local_server_id.upper())
            response = ansible.run_module(host_list=[system_ip], module="shell", args=cmd,ans_remote_pass=password, ans_remote_user="root")
            success, msg = ansible_is_valid_response(system_ip, response)
            if not success:
                return False, msg

            if response['contacted'][system_ip]['rc'] !=0:
                return False, response['contacted'][system_ip]['stderr']

            print "Set local vpn ip on remote db (systems)..."
            cmd = """echo \"update alienvault.system set vpn_ip=inet6_pton('%s') where server_id=unhex('%s');\" | ossim-db""" % (end_points['client_end_point1'],remote_server_id.upper())
            response = ansible.run_module(host_list=[system_ip], module="shell", args=cmd,ans_remote_pass=password, ans_remote_user="root")
            success, msg = ansible_is_valid_response(system_ip, response)
            if not success:
                return False, msg

            if response['contacted'][system_ip]['rc'] !=0:
                return False, response['contacted'][system_ip]['stderr']

            # RESTART SERVICES ON REMOTE: ossim-server and alienvault-forward
            print "Restarting remote alienvault-forward service..."
            response = ansible.run_module(host_list=[system_ip], module="service", args="name=alienvault-forward state=restarted",ans_remote_pass=password, ans_remote_user="root")
            success, msg = ansible_is_valid_response(system_ip, response)
            if not success:
                return False, msg
            print "Restarting remote ossim-server service..."
            response = ansible.run_module(host_list=[system_ip], module="service", args="name=ossim-server state=restarted",ans_remote_pass=password, ans_remote_user="root")
            success, msg = ansible_is_valid_response(system_ip, response)
            if not success:
                return False, msg

        print "Restarting ossim-server"
        response = ansible.run_module(host_list=[host], module="service", args="name=ossim-server state=restarted")
        success, msg = ansible_is_valid_response(host, response)
        if not success:
            return False, msg


    except Exception as err:
        return rt, "Something wrong happened while building the vpn tunnel! %s" % str(err)

    return True, end_points


def iface_up(system_ip,ifacelist=[]):
    """ Bring up the interface list. 
    
        We use the --force param to ignore de current status info
    """
    if len(ifacelist) == 0:
        return (True,"")

    ifscommand = "/sbin/ifup --force " +  " ".join(ifacelist)
    response = ansible.run_module(host_list=[system_ip],module="command",
                        args=ifscommand,use_sudo=True)
    #print "up: response => " + str(response)
    #
    if system_ip in response['dark']:
        return (False, "Can't connect to '%s' to change iface status" % system_ip)
    elif response['contacted'][system_ip].get('Failed',False) == True:
        return (False,"Error changing interfaces status msg: " + str( response['contacted'][system_ip]))
    elif response['contacted'][system_ip].get('rc',0) != 0:
        return (False,"Error changing interfaces status msg: "  + str( response['contacted'][system_ip]))
    else:
        return (True,"Interfaces %s down" % " ".join(ifacelist))


def iface_debian_down(system_ip,ifacelist=[]):
    """ Bring down the interface list. 
    
        We use the --force param to ignore de current status info
    """

    # First generate de command line
    if len(ifacelist) == 0:
        return (True,"")
    # XXX this is slow. I need to check the form of 
    # make this by only one call
    for iface in ifacelist:
        ifscommand = "/sbin/ifdown --force  " + iface
        response = ansible.run_module(host_list=[system_ip],module="command",
                        args=ifscommand,use_sudo=True)
        if system_ip in response['dark']:
            return (False, "Can't connect to '%s' to change iface status" % system_ip)
        elif response['contacted'][system_ip].get('Failed',False) == True:
            return (False,"Error changing interfaces status msg: " + str( response['contacted'][system_ip]))
        elif response['contacted'][system_ip].get('rc',0) != 0:
            return (False,"Error changing interfaces status msg:" +  str( response['contacted'][system_ip]))
        ifscommand = "/sbin/ip addr flush dev " + iface
        response = ansible.run_module(host_list=[system_ip],module="command",
                        args=ifscommand,use_sudo=True)
        if system_ip in response['dark']:
            return (False, "Can't connect to '%s' to change iface status" % system_ip)
        elif response['contacted'][system_ip].get('Failed',False) == True:
            return (False,"Error changing interfaces status msg: " + str( response['contacted'][system_ip]))
        elif response['contacted'][system_ip].get('rc',0) != 0:
            return (False,"Error changing interfaces status msg:" +  str( response['contacted'][system_ip]))
    return (True,"Interfaces %s down" % " ".join(ifacelist))


def iface_down(system_ip,ifacelist=[]):
    """ Bring down the interface list. 
    
        We use the --force param to ignore de current status info
    """

    # First generate de command line
    if len(ifacelist) == 0:
        return (True,"")
    # XXX this is slow. I need to check the form of 
    # make this by only one call, with a module 
    for iface in ifacelist:
        ifscommand = "/sbin/ifconfig " + iface + " down"
        response = ansible.run_module(host_list=[system_ip],module="command",
                        args=ifscommand,use_sudo=True)
        if system_ip in response['dark']:
            return (False, "Can't connect to '%s' to change iface status" % system_ip)
        elif response['contacted'][system_ip].get('Failed',False) == True:
            return (False,"Error changing interfaces status msg: " + str( response['contacted'][system_ip]))
        elif response['contacted'][system_ip].get('rc',0) != 0:
            return (False,"Error changing interfaces status msg:" +  str( response['contacted'][system_ip]))
        # 
        ifscommand = "/sbin/ip addr flush dev " + iface
        response = ansible.run_module(host_list=[system_ip],module="command",
                        args=ifscommand,use_sudo=True)
        if system_ip in response['dark']:
            return (False, "Can't connect to '%s' to change iface status" % system_ip)
        elif response['contacted'][system_ip].get('Failed',False) == True:
            return (False,"Error changing interfaces status msg: " + str( response['contacted'][system_ip]))
        elif response['contacted'][system_ip].get('rc',0) != 0:
            return (False,"Error changing interfaces status msg:" +  str( response['contacted'][system_ip]))

        
    return (True,"Interfaces %s down" % " ".join(ifacelist))
    

def set_interfaces_roles(system_ip,interface_roles):
    """ Check the role of subset of intefaces in the system
    
        @param system_ip      The system IP where we're going to operate
        @param inteface_roles  A json describing each interface we're going
                              to touch
        interface_role format:
        { "iface" : {"role":<role>, "ipaddress":<ipaddress>, "netmask":<netmask>}, ...}
        
        The possibles roles and params
            monitoring => no ipaddress and no netmask
            log_management => ipaddress and netmask must be present
            disable => no ipaddress and no netmask
        iface is the name of the network interface as configures (eth0, eth1, etc)
        iface SHOULD NOT BE the admin interface
                               
    """
    def get_admin_interface_from_current_status(current_status):
        for interface, interface_data in current_status.iteritems():
            if interface_data['role'] == 'admin':
                return interface
        return None

    # check params
    if system_ip == "":
        return False, "The system_ip should be a valid IP Address"
    if not isinstance(interface_roles, dict):
        return False, "The interface_roles should be a dictionary"
    if len(interface_roles)<=0:
        return False, "Empty interface roles"
    # Retrieve the current status.
    rc, net_current_status = get_iface_list(system_ip)

    if not rc:
        return False, "We can't retrieve the current status of the network configuration: %s" % net_current_status

    # The management interface can't be set.
    admin_interface = get_admin_interface_from_current_status(net_current_status)
    if admin_interface is not None:
        if admin_interface in interface_roles.keys():
            return False, "'%s' is the admin interface. You can't set the role" % admin_interface

    # Retrieve the network interface list from ansible facts
    response = ansible.run_module([system_ip],
                                  module="av_setup",
                                  args="filter=ansible_interfaces",
                                  use_sudo=True)

    if system_ip in response['dark']:
        return False, "We can't retrieve the current network interface list: %s" % response['dark'][system_ip]

    if response['contacted'][system_ip].get('Failed',False) is True:
        return False, "We can't retrieve the current network interface list: %s" % response['contacted'][system_ip]

    # Ok, now in response we have all systems interfaces returned  by ansible
    # u'ansible_facts': {u'ansible_interfaces': [u'lo', u'bond0', u'eth2', u'eth1', u'eth0']}}}} 
    # First verify that the admin iface is in the list
    system_interfaces = response['contacted'][system_ip]['ansible_facts']['ansible_interfaces']
    if admin_interface not in system_interfaces:
        return False, "Internal error admin iface '%s' not in system interfaces '%s'" % \
                     (admin_interface, str(system_interfaces))

    # Check that all ifaces are included in system_ifaces
    if not set(interface_roles.keys()).issubset(set(system_interfaces)):
        return False, "There are interfaces in the request that are not present in the system"

    # Retrieves the current [sensor]interfaces from ossim_setup.conf
    (success, sensor_ifaces) = get_sensor_interfaces(system_ip)
    if not success:
        return False, "Can't get current sensor interfaces"
    sensor_ifaces = sensor_ifaces['sensor_interfaces']
    # Ok, now we must check that each param obeys the constrains

    # Retrieve the system configured interfaces
    (success,system_configured_ifaces) = get_conf_network_interfaces(system_ip, store_path=True)
    if not success:
        return False, "Can't retrieve the current configured interfaces"

    # Build a hash table with key=ethx and value False
    result_ifaces = dict([(x, False) for x in interface_roles.keys()])

    old_sensor_ifaces = sensor_ifaces[:]  # CLone, python use refs
    removed_interfaces = []
    added_interfaces = []

    # Before attempting to make changes we have to check if the result of the operation would be consistent
    future_net_status = net_current_status.copy()
    for iface, conf in interface_roles.items():
        role = conf.get('role', None)
        netmask = conf.get('netmask', None)
        address = conf.get('ipaddress', None)
        if future_net_status.has_key(iface):
            if future_net_status[iface]['role'] != role:
                future_net_status[iface].pop('ipv4', None) # Clear the old IPv4 because we have change roles
            future_net_status[iface]['role'] = role
            # We need to clear all the info if we changed the role
            future_net_status[iface]['promisc'] = False
            if role == 'monitoring':
                future_net_status[iface]['promisc'] = True
            if role == 'log_management':
                ipconf = {'network': "", 'netmask': netmask, 'address': address}
                future_net_status[iface]['ipv4'] = ipconf

    admin_interfaces_future_net_status = [iface for iface, data in future_net_status.iteritems() if
                                          data['role'] is 'admin']

    if len(admin_interfaces_future_net_status) > 1:
        return False, "The admin interface is: %s and it's not allowed to configure more than one %s" % (
        admin_interface, admin_interfaces_future_net_status)

    ip_interfaces = [data['ipv4']['address'] for iface, data in future_net_status.iteritems() if
                     'ipv4' in data and data['ipv4']['address'] is not None and data['role'] is not 'disabled' and data['role'] is not 'monitoring']

    if len(ip_interfaces) > len(set(ip_interfaces)):
        return False, "It's not allowed to have more than one interface with the same ip"

    for iface, conf in interface_roles.items():
        role = conf.get('role', None)
        if role == "log_management":
            iface_netmask = conf.get('netmask', None)
            iface_address = conf.get('ipaddress', None)

            if iface_address is None:
                result_ifaces[iface] = (False,
                                        "In order to configure the given interface (%s) as a log management "
                                        "interface we need an IP address(%s)" % (
                                            iface, iface_address))
                continue
            if iface_netmask is None:
                result_ifaces[iface] = (False,
                                        "In order to configure the given interface (%s) as a log management "
                                        "interface we need a valid netmask (%s)" % (
                                            iface, iface_netmask))
                continue

            (success, result) = set_conf_iface(system_ip, iface, iface_address, iface_netmask)
            if not success:
                api_log.error("Can't configure iface '%s' msg: %s " % (iface, str(result)))
                result_ifaces[iface] = (False, "Can't configure iface '%s' msg: %s" % (iface, str(result)))
                continue
            result_ifaces[iface] = (True, "Configured in /etc/network/interfaces")
            added_interfaces.append(iface)
            if iface in sensor_ifaces:
                sensor_ifaces.remove(iface)

        elif role == 'disabled' or role == 'monitoring':
            # Check if the iface is in the
            if iface in system_configured_ifaces.keys():
                # Down iface
                (success,result) = iface_debian_down(system_ip,[iface])
                if not success:
                    api_log.error("Can't bring down configured iface '%s' " % iface)
                    result_ifaces[iface] = False,"Can't bring down configured iface '%s' " % iface
                    continue
                (success,result) = delete_conf_iface (system_ip, iface)
                if not success:
                    result_ifaces[iface] = (False,
                                            "Can't delete iface from /etc/network/interfaces msg: %s" % str(result))
                    continue

                removed_interfaces.append(iface)
                result_ifaces[iface] = (True, "Removed from /etc/network/interfaces")
            else:
                result_ifaces[iface] = (True, "Not in /etc/network/interfaces")

            if role == 'disabled':
                removed_interfaces.append(iface)
                if iface in sensor_ifaces:
                    sensor_ifaces.remove(iface)
            else:
                added_interfaces.append(iface)
                if iface not in sensor_ifaces:
                    sensor_ifaces.append(iface)
        else:
            return False, "Invalid Role (%s) for the interface %s" % (role, iface)

    # Here the code must be OK
    # How can we make and atomic "configuration" of this code

    # Now, check if we have to change the [sensor]interfaces
    # First, now ifdown
    (success,msg) = iface_down(system_ip, removed_interfaces)
    if not success:
        return False, "Something wrong has happened while setting down the interfaces %s" % msg
    # Give me up
    (success, msg) = iface_up(system_ip, added_interfaces)
    if not success:
        return False, "Something wrong has happened while setting up the interfaces %s" % msg

    if set(sensor_ifaces) != set(old_sensor_ifaces):
        # Set the ne sensors
        (success,msg) = set_sensor_interfaces(system_ip,",".join(sensor_ifaces))
        if not success:
            return False, result_ifaces

    return True, result_ifaces
