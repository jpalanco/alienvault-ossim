from nose.tools import assert_equal,assert_not_equal
import behave
import decorator
import jinja2
import jpath
import json
import os
import purl
import requests
import time
import logging
import ast
import StringIO
import urllib
import random
import string
import re
from subprocess import Popen, PIPE
from db.models.alienvault import Users
from db.models.alienvault import Sensor
from db.models.alienvault import System
from sqlalchemy.orm.exc import NoResultFound,MultipleResultsFound
from behave.log_capture import capture
import uuid
from apimethods.utils import  get_ip_str_from_bytes
import random
import sys
from avconfig.ossimsetupconfig import  AVOssimSetupConfigHandler
import paramiko
from ConfigParser import ConfigParser
import db
from apimethods.utils import get_ip_bin_from_str, get_ip_str_from_bytes, get_bytes_from_uuid, get_uuid_string_from_bytes
# "Defines"

IFF_PROMISC = 0x100

@behave.then('The JSON response is equals to properties of system list')
def then_verify_server_list (context):
    j = json.loads(context.result.getvalue())
    servers = [(
                   unicode(x.serialize.get("uuid")),
                   unicode(x.serialize.get("admin_ip")),
                   unicode(x.serialize.get("hostname")),
                   str(set(map(lambda p: unicode(p),
                               x.serialize.get("profile").split(','))))
               ) for x in db.session.query(System).all()]
    s = [(k,v['admin_ip'],v['hostname'],str(set(v['profile'].split(',')))) for k,v in  j['data']['systems'].items()]
    print ("DB => " + str(servers))
    print ("API => " + str(s))
    assert set(servers) == set(s) and len(s) == len(servers), "The servers list from database different from API"

@behave.given('I select the uuid for random system and store it in variable "{var_name}"')
def then_select_random_server (context,var_name):
    result = db.session.query(System).all()
    server = random.choice (result)
    context.alienvault[var_name] = get_uuid_string_from_bytes(server.id)

# TODO: Extract remote command execution (via popen) to a separate func in apimethods.utils
@behave.then(u'The JSON response has all the interfaces for system in variable "{var_uuid}"')
def then_verify_interfaces(context,var_uuid):
    j = json.loads(context.result.getvalue())
    print (j['data']['interfaces'])
    # Verify the correct json
    # Obtain admin_ip from database
    # Log into system, obtain interfaces list
    # Verify interface lists (copy the file from remote system)
    try:
        if context.alienvault[var_uuid] !='local':
            uuid = get_bytes_from_uuid(context.alienvault[var_uuid])
            admin_ip = db.session.query(System).filter(System.id == uuid).one().serialize.get("admin_ip")
        else:
            admin_ip = '127.0.0.1'

        r = re.compile(r'inet\s(.*?)/(.*?)\sbrd\s(.*?)\sscope')
        
        config = ConfigParser()
        assert config.read ("/etc/ansible/ansible.cfg")[0] == "/etc/ansible/ansible.cfg", "Can\'t load ansible.cfg file"
        sshkey = config.get("defaults","private_key_file")
        ssh = Popen(
            ["ssh", "-i", sshkey, "-l", "avapi", admin_ip, "/bin/cat /proc/net/dev"],
            shell=False, # this protects you against most of the risk associated with piping commands to the shell
            stdout=PIPE,
            stderr=PIPE
        )
        (stdoutdata, stderrdata) = ssh.communicate()
        lines = filter(None, stdoutdata.splitlines())
        iface_set = set ([x.split (':',2)[0].strip() for  x in  lines[2:]])
        iface_response = set (j['data']['interfaces'].keys())
        assert iface_set == iface_response, "Bad interfaces returnes from API"
        # Check the properties (ip etc )
        # Flags
        # obtain the flags
        flags = {}
        for iface in  iface_response:
            ssh = Popen(
                ["ssh", "-i", sshkey, "-l", "avapi", admin_ip, "/bin/cat /sys/class/net/%s/flags" % iface],
                shell=False,
                stdout=PIPE,
                stderr=PIPE
            )
            (stdoutdata, stderrdata) = ssh.communicate()
            flaglines = filter(None, stdoutdata.splitlines())
            flags[iface] = {'promisc':(int(flaglines[0].strip(),16) & IFF_PROMISC) == IFF_PROMISC}
            assert j['data']['interfaces'][iface]['promisc'] == flags[iface]['promisc'],"Bad interface promisc flag  API CALL= %s  System = %s " % (j['data']['interfaces'][iface]['promisc'], flags[iface]) 
            ssh = Popen(
                ["ssh", "-i", sshkey, "-l", "avapi", admin_ip, "/bin/ip addr show dev %s" % iface],
                shell=False,
                stdout=PIPE,
                stderr=PIPE
            )
            (stdoutdata, stderrdata) = ssh.communicate()
            ipaddr_lines = filter(None, stdoutdata.splitlines())
            for line in ipaddr_lines[2:]:
                m = r.match(line.strip())
                if m:
                    flags[iface]['ip'] = m.group(1)
                    v = (2**int(m.group(2)))-1
                    flags[iface]['mask'] = "%u.%u.%u.%u" % ((v & 0xFF), \
                                                          (v & 0xFF00) >> 8, \
                                                          (v & 0xFF0000) >> 16, \
                                                           (v & 0xFF000000) >> 24 )
                                                                                                                       
                    #print flags[iface]['mask']
                
                    flags[iface]['bcast'] = m.group(3)
                    assert j['data']['interfaces'][iface]['ipv4']['address'] == flags[iface]['ip'],"Bad address at iface %s" % iface
                    assert j['data']['interfaces'][iface]['ipv4']['netmask'] == flags[iface]['mask'],"Bad mask at iface %s" % iface
                
            
        #print flags     

        
        
    except NoResultFound:
        assert False,"Can't find sensor with uuid %s in database " % var_uuid
    except MultipleResultsFound, msg:
        assert False,"Multiples result for query. Internal database error uuid => %s" % var_uuid



@behave.given(u'I select a random interface for system with uuid in variable "{s_uuid}" and store in variable "{iface}"')
def given_select_iface (context,s_uuid,iface):
    #Here i used the same API call 
    # Make the API call to obtain the results
    assert hasattr(context,'api_server') == True, "API server must be defined"
    # I used the current login info stored in context.cookies
    context.execute_steps ('When I send a GET request to url "https://'+context.api_server+":40011/av/api/1.0/system/" + context.alienvault[s_uuid] + "/network/interface\"")
    assert context.resultcode == 200, "Can't obtain interfaces list"
    result = json.loads(context.result.getvalue())
    context.alienvault[iface] =  random.choice([x for x in result['data']['interfaces'].keys() if x != 'lo'])

# TODO: Extract remote command execution (via popen) to a separate func in apimethods.utils
# TODO: Extract the logic shared with then_verify_interfaces() to a separate func in apimethods.utils
@behave.then(u'I verify the interface in variable "{var_iface}" of system with uuid in variable "{var_uuid}"')
def then_verify_iface_properties (context,var_iface,var_uuid):
    j = json.loads(context.result.getvalue())
    # Verify the correct json
    # Obtain admin_ip from database
    # Log into system, obtain interfaces list
    # Verify interface lists (copy the file from remote system)
    uuid = get_bytes_from_uuid(context.alienvault[var_uuid])
    r = re.compile(r'inet\s(.*?)/(.*?)\sbrd\s(.*?)\sscope')
    try:
        admin_ip = db.session.query(System).filter(System.id == uuid).one().serialize.get("admin_ip")
        config = ConfigParser()
        assert config.read ("/etc/ansible/ansible.cfg")[0] == "/etc/ansible/ansible.cfg", "Can\'t load ansible.cfg file"
        sshkey = config.get("defaults","private_key_file")
        ssh = Popen(
            ["ssh", "-i", sshkey, "-l", "avapi", admin_ip, "/bin/cat /proc/net/dev"],
            shell=False,
            stdout=PIPE,
            stderr=PIPE
        )
        (stdoutdata, stderrdata) = ssh.communicate()
        lines = filter(None, stdoutdata.splitlines())
        iface_set = set ([x.split (':',2)[0].strip() for  x in  lines[2:]])
        iface_response = set ([context.alienvault[var_iface]])
        assert iface_response.issubset (iface_set), "Bad interface: %s" % context.alienvault[var_iface]
        # Check the properties (ip etc )
        # Flags
        # obtain the flags
        iface = context.alienvault[var_iface]
        ssh = Popen(
            ["ssh", "-i", sshkey, "-l", "avapi", admin_ip, "/bin/cat /sys/class/net/%s/flags" % iface],
            shell=False,
            stdout=PIPE,
            stderr=PIPE
        )
        (stdoutdata, stderrdata) = ssh.communicate()
        flaglines = filter(None, stdoutdata.splitlines())
        flags = {'promisc':(int(flaglines[0].strip(),16) & IFF_PROMISC) == IFF_PROMISC}
        assert j['data']['interface']['promisc'] == flags['promisc'],"Bad interface promisc flag  API CALL= %s  System = %s " % (j['data']['interface']['promisc'], flags) 
        ssh = Popen(
            ["ssh", "-i", sshkey, "-l", "avapi", admin_ip, "/bin/cat /sys/class/net/%s/flags" % iface],
            shell=False,
            stdout=PIPE,
            stderr=PIPE
        )
        (stdoutdata, stderrdata) = ssh.communicate()
        ipaddr_lines = filter(None, stdoutdata.splitlines())
        for line in ipaddr_lines[2:]:
            m = r.match(line.strip())
            if m:
                flags['ip'] = m.group(1)
                v = (2**int(m.group(2)))-1
                flags['mask'] = "%u.%u.%u.%u" % ((v & 0xFF), \
                                                          (v & 0xFF00) >> 8, \
                                                          (v & 0xFF0000) >> 16, \
                                                           (v & 0xFF000000) >> 24 )
                                                                                                                       
                
                flags['bcast'] = m.group(3)
                assert j['data']['interface']['ipv4']['address'] == flags['ip'],"Bad address at iface %s" % var_iface
                assert j['data']['interface']['ipv4']['netmask'] == flags['mask'],"Bad mask at iface %s" % var_iface

        
        
    except NoResultFound:
        assert False,"Can't find sensor with uuid %s in database " % context.alienvault[var_uuid]
    except MultipleResultsFound, msg:
        assert False,"Multiples result for query. Internal database error uuid => %s" % context.alienvault[var_uuid]




@behave.then(u'The JSON response key "{var_key}" has all network interfaces for system with uuid in variable "{var_uuid}"')
def then_check_ifaces (context,var_key,var_uuid):
    j = json.loads(context.result.getvalue())
    for key in context.alienvault[varkey].split('.'):
        j = j[key]
    # This override context.result
    context.execute_steps ('When I send a GET request to url "https://'+context.api_server+":40011/av/api/1.0/system/" + context.alienvault[s_uuid] + "/network/interface\"")
    assert context.resultcode == 200, "Can't obtain interfaces list"
    result = json.loads(context.result.getvalue())
    sysfaces = set (result['data']['interfaces'].keys())
    assert set(j.keys) == sysfaces,"The interfaces returned are not OK"

@behave.then(u'Verify the doctor response')
def step_impl(context):
    j=json.loads (context.result.getvalue())
    f = j['data']['file_uploaded']
    if f == False:
        fname = j['data']['file_name']
        assert os.path.exists (fname) == True, "The doctor file hasn't been downloaded from remote machine"


@behave.given(u'The admin interface in the system with uuid in var "{s_uuid}" is "{var_iface}"')
def given_check_admin_iface(context, var_iface,s_uuid):
    context.execute_steps('When I send a GET request to url "https://'+context.api_server+":40011/av/api/1.0/system/" + context.alienvault[s_uuid] + "/network/interface\"")
    assert context.resultcode == 200, "Can't obtain interfaces list"
    j = json.loads(context.result.getvalue())
    assert j['data']['interfaces'][var_iface]['role'] == 'admin'
    #context.execute_steps('Then I print request result')

@behave.then(u'The interface "{iface}" role is "{role}" in the system with uuid in var "{s_uuid}"')
def then_check_iface_role(context,iface,role,s_uuid):
    # I need to clear url params:
    context.urlparams = {}
    context.execute_steps('When I send a GET request to url "https://'+context.api_server+":40011/av/api/1.0/system/" + context.alienvault[s_uuid] + "/network/interface\"")
    assert context.resultcode == 200, "Can't obtain interfaces list"
    j = json.loads(context.result.getvalue())
    print (j)
    assert j['data']['interfaces'][iface]['role'] == role
@behave.then(u'The interface "{iface}" has ip "{ip}" and netmask "{mask}" in the system with uuid in var "{s_uuid}"')
def then_check_iface_ip (context,iface,ip,mask,s_uuid):
    context.urlparams = {}
    context.execute_steps('When I send a GET request to url "https://'+context.api_server+":40011/av/api/1.0/system/" + context.alienvault[s_uuid] + "/network/interface\"")
    assert context.resultcode == 200, "Can't obtain interfaces list"
    j = json.loads(context.result.getvalue())
    assert j['data']['interfaces'][iface]['ipv4']['address'] == ip
    assert j['data']['interfaces'][iface]['ipv4']['netmask'] ==  mask


    


   
    
   


    


    
    
    
    
    


    
        
