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
"""
    Several methods to manage a ossec deployment
"""
import StringIO
import os
import re
import traceback
from collections import namedtuple
from tempfile import NamedTemporaryFile

from passlib.hash import nthash, lmhash

import api_log
from ansiblemethods.ansiblemanager import Ansible, PLAYBOOKS
from ansiblemethods.helper import ansible_is_valid_response
from apimethods.utils import is_valid_ipv4, set_ossec_file_permissions

# See PEP8 http://legacy.python.org/dev/peps/pep-0008/#global-variable-names
# Pylint thinks that a variable at module scope is a constant
_ansible = Ansible()  # pylint: disable-msg=C0103


DEFAULT_ERR_MSG = 'Something wrong happened while running ansible command'


def get_ossec_agent_data(host_list=None, args=None):
    """
        Return the ossec agent data
        @param host_list: List of ip whose data we need
    """
    if host_list is None:
        host_list = []
    if args is None:
        args = {}
    return _ansible.run_module(host_list, 'ossec_agent', args)


def ossec_win_deploy(sensor_ip, agent_name, windows_ip, windows_username, windows_domain, windows_password):
    """
    @param sensor_ip: The sensor IP from where to deploy the ossec agent
    @agent_name:
    @windows_ip:
    @windows_username:
    @windows_domain:
    @windows_password:
    @return: A tuple (success, data).
    """

    response = None

    try:
        # Create temporary files outside playbook
        auth_file_samba = NamedTemporaryFile(delete=False)
        agent_config_file = NamedTemporaryFile(delete=False)
        agent_key_file = NamedTemporaryFile(delete=False)

        # Auth string for `wmiexec` tool: e.g. domain/username or username
        domain_str = "%s/" % windows_domain if windows_domain else ""
        windows_auth_sting = "%s%s" % (domain_str, windows_username)

        evars = {"target": "%s" % sensor_ip,
                 "auth_file_samba": "%s" % auth_file_samba.name,
                 "agent_config_file": "%s" % agent_config_file.name,
                 "agent_key_file": "%s" % agent_key_file.name,
                 "agent_name": "%s" % agent_name,
                 "windows_ip": "%s" % windows_ip,
                 "windows_domain": "%s" % windows_domain,
                 "windows_username": "%s" % windows_username,
                 "windows_password": "%s" % windows_password,
                 "auth_str": "%s" % windows_auth_sting,
                 "hashes": "%s:%s" % (lmhash.hash(windows_password), nthash.hash(windows_password))}

        response = _ansible.run_playbook(playbook=PLAYBOOKS['OSSEC_WIN_DEPLOY'],
                                         host_list=[sensor_ip],
                                         extra_vars=evars)

        # Remove temporary files
        os.remove(auth_file_samba.name)
        os.remove(agent_config_file.name)
        os.remove(agent_key_file.name)

    except Exception, exc:
        trace = traceback.format_exc()
        api_log.error("Ansible Error: An error occurred while running an windows OSSEC agent deployment:"
                      "%s \n trace: %s" % (exc, trace))
    return response


def get_ossec_rule_filenames(sensor_ip):
    """
        Get the ossec rule file names
    """
    try:
        command = "/usr/bin/find /var/ossec/alienvault/rules/*.xml -type f -printf \"%f\n\""
        response = _ansible.run_module(host_list=[sensor_ip], module="shell", args=command)
        if sensor_ip in response['dark'] or 'unreachable' in response:
            return False, make_err_message('[get_ossec_rule_filenames]', DEFAULT_ERR_MSG, str(response))
        if 'failed' in response['contacted'][sensor_ip]:
            return False, make_err_message('[get_ossec_rule_filenames]', DEFAULT_ERR_MSG, str(response))
        file_list = response['contacted'][sensor_ip]['stdout'].split('\n')
    except Exception, exc:
        api_log.error("Ansible Error: An error occurred while running ossec_rule_filenames: %s" % str(exc))
        return False, str(exc)
    return True, file_list


def ossec_extract_agent_key(sensor_ip, agent_id):
    """
        Call the /usr/share/ossim/scripts/ossec-extract-key.sh with an agent id
    """
    try:
        result, msg = is_valid_agent_id(agent_id)
        if not result:
            return False, msg

        command = "/usr/share/ossim/scripts/ossec-extract-key.sh %s" % agent_id
        response = _ansible.run_module(host_list=[sensor_ip], module="shell", args=command, use_sudo=True)
        if sensor_ip in response['dark'] or 'unreachable' in response:
            return False, make_err_message('[ossec_extract_agent_key]', DEFAULT_ERR_MSG, str(response))
        if 'failed' in response['contacted'][sensor_ip]:
            return False, make_err_message('[ossec_extract_agent_key]', DEFAULT_ERR_MSG, str(response))
        # I need exactly the three lines
        stio = StringIO.StringIO(response['contacted'][sensor_ip]['stdout'])
        lines = stio.readlines()
        # The 3º line has the key
        if len(lines) >= 3:
            return True, lines[2]
        else:
            api_log.error("[ossec_extract_agent_key] Bad output from ossec-extract-key.sh")
            return False, "[ossec_extract_agent_key] Bad output from ossec-extract-key.sh"
    except Exception, exc:
        api_log.error("Ansible Error: An error occurred while running ossec_extract_agent_key: %s" % str(exc))
        return False, str(exc)
    return True, ''


def ossec_add_new_agent(system_ip, agent_name, agent_ip):
    """
        Add a new OSSEC Agent
    """
    try:
        command = "/usr/share/ossim/scripts/ossec-create-agent.sh %s %s" % (agent_name, agent_ip)
        response = _ansible.run_module(host_list=[system_ip], module="command", use_sudo="True", args=command)
    except Exception, exc:
        api_log.error("Ansible Error: An error occurred while running ossec_add_new_agent: %s" % str(exc))
        return False, "Ansible Error: An error occurred while running ossec_add_new_agent: %s" % str(exc)

    (success, msg) = ansible_is_valid_response(system_ip, response)
    if success:
        if 'Error' in response['contacted'][system_ip]['stdout']:
            return False, "[ossec_add_new] Create agent script failed: %s" % str(response['contacted'][system_ip]['stdout'])
        return True, response['contacted'][system_ip]['stdout']
    else:
        api_log.error("Ansible Error: An error occurred while running ossec_add_new_agent: {0}".format(msg))
        msg = 'HIDS agent cannot be created'
    return success, msg


def ossec_delete_agent(system_ip, agent_id):
    try:
        command = "/usr/share/ossim/scripts/ossec-delete-agent.sh %s" % (agent_id)
        response = _ansible.run_module(host_list=[system_ip], module="command", use_sudo="True", args=command)
    except Exception, err:
        api_log.error("Ansible Error: An error occurred while running ossec_delete_agent: %s" % str(err))
        return False, "Ansible Error: An error occurred while running ossec_delete_agent: %s" % str(err)

    (success, msg) = ansible_is_valid_response(system_ip, response)
    if success:
        if 'Error' in response['contacted'][system_ip]['stdout']:
            return False, "[ossec_delete_agent] Delete agent script failed: %s" % \
                str(response['contacted'][system_ip]['stdout'])
    return success, msg


def ossec_delete_agentless(system_ip, agent_ip):
    try:
        command = "/usr/share/ossim/scripts/ossec-delete-agentless.sh %s" % (agent_ip)
        response = _ansible.run_module(host_list=[system_ip], module="command", use_sudo="True", args=command)
    except Exception, err:
        api_log.error("Ansible Error: An error occurred while running ossec_delete_agentless: %s" % str(err))
        return False, "Ansible Error: An error occurred while running ossec_delete_agentless: %s" % str(err)

    (success, msg) = ansible_is_valid_response(system_ip, response)
    if success:
        if 'Error' in response['contacted'][system_ip]['stdout']:
            return False, "[ossec_delete_agent] Delete agentless script failed: %s" % \
                str(response['contacted'][system_ip]['stdout'])
    return success, msg


def ossec_get_logs(system_ip, ossec_log, number_of_lines):
    """
        Get lines from ossec_log
        @param system_ip: System ip from we're going to get the logs
        @param ossce_log: alert or ossec , the where we're going to red
        @param number_of_logs: Number of line to read from the logs
    """
    if ossec_log not in ['ossec', 'alert']:
        return (False, "Bad ossec_log value '%s'" % ossec_log)
    try:
        nlogs = int(number_of_lines)
    except ValueError:
        return (False, "Bad number_of_lines, '%s' not a integer" % number_of_lines)
    if nlogs < 0 or nlogs > 5000:
        return (False, "Bad number_of_lines, '%s' negative or more than 5000" % number_of_lines)
    # Now call the Ansible module
    command = "/usr/share/ossim/scripts/ossec-logs.sh %s %s" % (ossec_log, number_of_lines)
    response = _ansible.run_module(host_list=[system_ip], module="shell", args=command, use_sudo=True)
    if system_ip in response['dark'] or 'unreachable' in response:
        return False, "[ossec_get_logs] Something wrong happened while running ansible command %s" % str(response)
    if 'failed' in response['contacted'][system_ip]:
        return False, "[ossec_get_logs]  Something wrong happened while running ansible command %s" % str(response)
    logs = response['contacted'][system_ip]['stdout'].split("\n")
    return (True, logs)


def ossec_create_preconfigured_agent(sensor_ip, agent_id, agent_type="windows", destination_path=""):
    """Creates a preconfigured agent on the given sensor
    :param sensor_ip: The sensor ip where you want to create the preconfigured agent
    :param agent_id: The agent id for which you want to generate a preconfigured executable.
                    It had to be registered previously on ossec-server
    :agent_type: The agent type to be generated (unix, windows)
    :destination_path: Local path where the binary should be copied"""

    generated_agent_path = ""
    if agent_type not in ["unix", "windows"]:
        return False, "Invalid agent type. Allowed values are [unix, windows]"
    if re.match(r"^[0-9]{1,4}$", agent_id) is None:
        return False, "Invalid agent ID. The agent ID has to be 1-4 digital characters"
    try:
        command = "/usr/share/ossim/scripts/ossec-download-agent.sh  %s %s" % (agent_id, agent_type)
        response = _ansible.run_module(host_list=[sensor_ip], module="shell", args=command, use_sudo=True)
        result, msg = ansible_is_valid_response(sensor_ip, response)
        if not result:
            return False, msg
        script_return_code = int(response['contacted'][sensor_ip]['rc'])
        script_stdout = response['contacted'][sensor_ip]['stdout']
        if script_return_code != 0:
            return False, "An error occurred while generating the ossec agent. Script return code is %s. Output: %s" % (script_return_code, script_stdout)
        # unix agent generation is not available. The script should fail before arrive to this point
        if agent_type == "windows":
            generated_agent_path = "/usr/share/ossec-generator/agents/ossec_installer_%s.exe" % agent_id
        # We have to copy the remote binary to our local system.
        if not os.path.exists(destination_path):
            return False, "Destination folder doesn't exists"
        response = _ansible.run_module(host_list=[sensor_ip], module="fetch", args="dest=%s src=%s flat=yes" % (destination_path, generated_agent_path), use_sudo=True)
        result, msg = ansible_is_valid_response(sensor_ip, response)
        if not result:
            return False, "Something wrong happen while fetching the file %s" % msg
    except Exception as err:
        return False, "An error occurred while generating the ossec agent. %s" % str(err)
    return True, "%sossec_installer_%s.exe" % (destination_path, agent_id)


def ossec_rootcheck(system_ip, agent_id=""):
    """
         @param system_ip: System ip from we're going to get the logs
         @param agent_id: Agent_id
    """
    if re.match(r"^[0-9]{1,4}$", agent_id) is None:
        return False, "Invalid agent ID. The agent ID has to be 1-4 digital characters"
    try:
        command = "/usr/share/ossim/scripts/ossec-rootcheck.sh  %s" % agent_id
        response = _ansible.run_module(host_list=[system_ip], module="shell", args=command, use_sudo=True)
        result, msg = ansible_is_valid_response(system_ip, response)
        if not result:
            return False, msg
        script_return_code = int(response['contacted'][system_ip]['rc'])
        script_output = response['contacted'][system_ip]['stdout']
        if script_return_code != 0:
            return False, "[ossec_rootcheck] Something wrong happened while running ansible command %s" % str(response)
    except Exception as err:
        return False, "[ossec_rootcheck] Something wrong happened while running ansible command %s" % str(err)

    #Splitting by char an empty string returns [''] and we need []
    output = script_output.split("\n") if script_output else []
    return (True, output)


def _ossec_parse_agent_list(data):
    """
        Internal function to make beautiful output
    """
    strio = StringIO.StringIO(data)
    # Well I assume that there aren't a lots of sensor.
    lines = strio.readlines()
    # Gen a dict with key the IP"
    result = {}
    for line in lines:
        line = line[:-1] # *coff* *coff* "\n"
        if line != "":
            (agent_id, name, ipaddress, status, tail) = line.split(",")
            # Match agent id
            if re.match(r"^[0-9]{1,4}$", agent_id) is not None:
                result[agent_id] = {'name': name, 'ip': ipaddress, 'status': status}
            else:
                api_log.info("[ossec_get_available_agents] Discarting info about agentless %s" % line)
    return result


def ossec_get_available_agents(system_ip, op_ossec='list_available_agents', agent_id=''):
    """
        @param system_ip:   System ip of the sensor we're going to check
        @param op_ossec: Operation. One in list_available_agents,  list_online_agents,
        restart_agent, integrity_check
        @param agent_id: Agent id, we need it in the restar_agent or integrity_check
    """
    AgentParams = namedtuple('AgentParams', ['agent_id', 'ansible_args', 'proc_func'])
    ops = {
        'list_available_agents': AgentParams(False, 'command=agent_control list_available_agents=true', _ossec_parse_agent_list),
        'list_online_agents': AgentParams(False, 'command=agent_control list_online_agents=true', _ossec_parse_agent_list),
        'restart_agent': AgentParams(True, 'command=agent_control restart_agent=%s', None),
        'integrity_check': AgentParams(True, 'command=agent_control integrity_check=%s', None),
    }
    try:
        if op_ossec not in ops.keys():
            return (False, "[ossec_get_available_agents] Bad op '%s'" % op_ossec)
        ansp = ops[op_ossec]
        if ansp.agent_id:
            if re.match(r"^[0-9]{1,4}$", agent_id) is None:
                return (False, "[ossec_get_available_Agents] Bad agent_id '%s'" % agent_id)
            args = ansp.ansible_args % agent_id
        else:
            args = ansp.ansible_args
        # Run module
        response = _ansible.run_module(host_list=[system_ip], module='ossec_agent', args=args, use_sudo=True)
        success, msg = ansible_is_valid_response(system_ip, response)
        if not success:
            return False, msg
        # Now check the 'rc' field
        ans_rc = response['contacted'][system_ip]['rc']
        if ans_rc != 0:
            return False, "[ossec_get_available_agents] Error: %s" % response['contacted'][system_ip]['data']
        # The msg field doesn't work in this case. The data is in 'data'
        if ansp.proc_func != None:
            data = ansp.proc_func(response['contacted'][system_ip]['data'])
        else:
            data = response['contacted'][system_ip]['data']
        # I need to make some process if list_available_agents or list_online_agents are called
    except Exception as err:
        return False, "[ossec_get_available_agents] Something wrong happened while running ansible command %s" % str(err)
    return True, data


def ossec_get_check(system_ip, check_type, agent_name=""):
    """This function checks whether an ossec check has been made or not"""

    if check_type not in ["lastip", "lastscan"]:
        return False, "Invalid check type. Allowed values are [lastip, syscheck, rootcheck]"

    if re.match(r"[a-zA-Z0-9_\-\(\)]+", agent_name) is None:
        return False, r"Invalid agent name. Allowed characters are [^a-zA-Z0-9_\-()]+"

    data = ''
    try:
        if check_type == "lastscan":
            # We need to exec TWO results
            result_dict = {}
            command = "/usr/share/ossim/scripts/ossec_check.sh %s '%s'" % ("lastscan", agent_name)
            response = _ansible.run_module(host_list=[system_ip], module="shell", args=command, use_sudo=True)
            result, msg = ansible_is_valid_response(system_ip, response)

            if not result:
                return False, msg

            script_return_code = int(response['contacted'][system_ip]['rc'])
            script_output = response['contacted'][system_ip]['stdout'].split("\n")

            if script_return_code != 0:
                return False, "[ossec_get_check] Something wrong happened while running ansible command ->'%s'" % str(response)

            if len(script_output) != 2: #IP not found
                return True, {'syscheck':'','rootcheck':''}

            matched_object = re.match(r"Last syscheck scan started at: (?P<s_time>\d{10})",  script_output[0])
            last_syscheck = ""
            if matched_object is not None:
                last_syscheck = matched_object.groupdict()['s_time']
            result_dict['syscheck'] = last_syscheck

            matched_object = re.match(r"Last rootcheck scan started at: (?P<r_time>\d{10})",  script_output[1])
            last_rootcheck = ""
            if matched_object is not None:
                last_rootcheck = matched_object.groupdict()['r_time']
            result_dict['rootcheck'] = last_rootcheck

            data = result_dict

        if check_type == "lastip":
            command = "/usr/share/ossim/scripts/ossec_check.sh %s '%s'" % (check_type, agent_name)
            response = _ansible.run_module(host_list=[system_ip], module="shell", args=command, use_sudo=True)
            result, msg = ansible_is_valid_response(system_ip, response)

            if not result:
                return False, msg
            script_return_code = int(response['contacted'][system_ip]['rc'])
            script_output = response['contacted'][system_ip]['stdout']

            if script_return_code != 0:
                return False, "[ossec_get_check] Something wrong happened while running ansible command ->'%s'" % str(response)
            if not is_valid_ipv4(script_output):#IP not found
                return True, ""
            data = script_output
    except Exception as err:
        return False, "[ossec_get_check] Something wrong happened while running ansible command ->  '%s'" % str(err)
    return True, data


def ossec_get_status(system_ip):
    try:
        response = _ansible.run_module(host_list=[system_ip], module="av_ossec_status", args="", use_sudo=True)
        result, msg = ansible_is_valid_response(system_ip, response)
        if not result:
            return False, msg
        data = response['contacted'][system_ip]['data']
    except Exception as err:
        return False, "[ossec_get_status] Something wrong happened while running ansible command ->  '%s'" % str(err)
    return True, data


def ossec_control(system_ip, operation, option):
    """Interface with the ossec-control binary"""
    # TODO: This can be implemented as a module as well
    if operation not in ["start", "stop", "restart", "enable", "disable", "status"]:
        return False, "Invalid operation. Allowed values are: ['start','stop','restart','enable','disable','status']"
    if operation == "enable" or operation == "disable":
        if option not in ["client-syslog", "agentless", "debug"]:
            return False, "Invalid option. Allowed values are: ['client-syslog','agentless','debug']"
    try:
        # Note:
        # if you run the following command:
        #  >>> ansible <yourip> -m shell -a "/var/ossec/bin/ossec-control restart "  -s
        # ps output:
        # avapi    12326  1.6  0.1  63308 14512 pts/2    S+   02:17   0:00 /usr/share/alienvault/api_core/bin/python /usr/share/alienvault/api_core/bin/ansible <yourip> -m command -a /var/ossec/bin/ossec-control restart -s
        # root     12349  0.2  0.0      0     0 pts/7    Z+   02:17   0:00 [ossec-control] <defunct>
        #
        # The ossec-control becomes a defunct process. We've to investigate this in deep
        # I think it's something related with the way ossec-control script works with the restart command
        # The workaround is to redirect the standard output and error to /dev/null
        data = {}
        command = "/var/ossec/bin/ossec-control %s" % operation
        if operation in ["enable", "disable"]:
            command = "/var/ossec/bin/ossec-control %s %s" % (operation, option)
        if operation == "restart":
            command += " > /dev/null 2>&1"
        response = _ansible.run_module(host_list=[system_ip], module="shell", args=command, use_sudo=True)

        result, msg = ansible_is_valid_response(system_ip, response)
        if not result:
            return False, msg
        script_return_code = int(response['contacted'][system_ip]['rc'])
        script_output = response['contacted'][system_ip]['stdout']

        #status operation can return !=0. If one of the ossec process is not running the rc >0
        if script_return_code != 0 and operation != "status":
            return False, "[ossec_control] Something wrong happened while running ansible command ->'%s'" % str(response)
        data['stdout'] = script_output
        result, msg = ossec_get_status(system_ip)
        if not result:
            return False, "[ossec-control] Error getting the ossec status -> '%s'" % msg
        data.update(msg)
    except Exception as err:
        return False, "[ossec_control] Something wrong happened while running ansible command ->  '%s'" % str(err)

    if operation in ["status"]:
        # remove ossec string
        data['raw_output_status'] = data['raw_output_status'].replace('ossec-', '')
        data['stdout'] = data['stdout'].replace('ossec-', '')

        for key, value in data['general_status'].items():
            new_key = key.replace('ossec-', '')
            data['general_status'][new_key] = value
            del data['general_status'][key]

    return True, data


def ossec_add_agentless(system_ip, host=None, user=None, password=None, supassword=None):
    """
        Add a agentless monitoring system
        @param system_ip Sensor IP where we're going to modify the ossec configuration
        @param host we're going to add
        @param user user we use to connect to host
        @param password password for user
        @param supassword optional password use.
    """
    if not (host and user and password):
        api_log.error("[ossec_add_agentless] Missing mandatory parameter: Host, user or password (%s, %s, %s)" % (host, user, password))
        return (False, "[ossec_add_agentless] Missing mandatory parameter: Host, user or password (%s, %s, %s)" % (host, user, password))
    try:
        command = "/var/ossec/agentless/register_host.sh add %s@%s %s %s" % (user, host, password, supassword if supassword != None else '')
        response = _ansible.run_module(host_list=[system_ip], module="shell", args=command, use_sudo=True)
        result, msg = ansible_is_valid_response(system_ip, response)
        if not result:
            return False, msg
        script_return_code = int(response['contacted'][system_ip]['rc'])
        script_output = response['contacted'][system_ip]['stdout']
        if script_return_code != 0:
            return False, "[ossec_add_agentless] Something wrong happened while running ansible command ->'%s'" % str(response)
        return True, script_output
    except Exception as err:
        return  False, "[ossec_control] Something wrong happened while running ansible command ->  '%s'" % str(err)


def ossec_get_modified_registry_entries(system_ip, agent_id=""):
    """Retrieves information about the modified registry entries of the given agent id
    :param system_ip: System ip The ip of the sensor we are going to consult
    :param agent_id: Agent_id
    :return (success,data) where success is True on success False otherwise
    """
    if re.match(r"^[0-9]{1,4}$", agent_id) is None:
        return False, "Invalid agent ID. The agent ID has to be 1-4 digital characters"
    try:
        command = "/var/ossec/bin/syscheck_control -s -r -i %s" % agent_id
        response = _ansible.run_module(host_list=[system_ip], module="shell", args=command, use_sudo=True)
        result, msg = ansible_is_valid_response(system_ip, response)
        if not result:
            return False, msg
        script_return_code = int(response['contacted'][system_ip]['rc'])
        script_output = response['contacted'][system_ip]['stdout']
        if script_return_code != 0:
            return False, "[ossec_modified_windows_registry] Something wrong happened while running ansible command %s" % str(response)
        output = {}
        index = 0
        for line in script_output.split("\n"):
            if line != '':
                output[index] = line
                index += 1
    except Exception as err:
        return False, "[ossec_modified_windows_registry] Something wrong happened while running ansible command %s" % str(err)
    return (True,  output)


def ossec_get_configuration_rule(system_ip, rule_filename, destination_path=""):
    #file name validation:
    if not re.match(r'[A-Za-z0-9_\-]+\.xml', rule_filename):
        return False, "Invalid rule filename <%s> " % str(rule_filename)
    try:
        ossec_rule_path = "/var/ossec/alienvault/rules/%s" % rule_filename
        if not os.path.exists(destination_path):
            return False, "Destination folder doesn't exists"
        # From ansible doc: Recursive fetching may be supported in a later release.
        response = _ansible.run_module(host_list=[system_ip], module="fetch", args="dest=%s src=%s flat=yes fail_on_missing=yes" % (destination_path, ossec_rule_path), use_sudo=True)
        result, msg = ansible_is_valid_response(system_ip, response)
        if not result:
            return False,  str(msg)

        success, result = set_ossec_file_permissions(destination_path+rule_filename)
        if not success:
            return False, str(result)

    except Exception as err:
        return False, "[ossec_get_configuration_rule] Something wrong happened while running ansible command %s" % str(err)
    return True,destination_path+rule_filename


def ossec_put_configuration_rule_file(system_ip, local_rule_filename, remote_rule_name):
    try:
        ossec_rule_path = "/var/ossec/alienvault/rules/%s" % remote_rule_name
        cmd_args = "src=%s dest=%s force=yes owner=root group=ossec mode=644" % (local_rule_filename, ossec_rule_path)
        response = _ansible.run_module(host_list=[system_ip], module="copy", args=cmd_args, use_sudo=True)
        result, msg = ansible_is_valid_response(system_ip, response)
        if not result:
            return False,  str(msg)

    except Exception as err:
        return False, "[ossec_get_configuration_rule] Something wrong happened while running ansible command %s" % str(err)
    return True, "Done"


def ossec_verify_server_config_file(system_ip, path):
    try:
        command = "/var/ossec/bin/ossec-logtest -t -c %s" % path
        response = _ansible.run_module(host_list=[system_ip], module="shell", args=command, use_sudo=True)
        if system_ip in response['dark'] or 'unreachable' in response:
            return False, "[ossec_verify_server_config_file] Something wrong happened while running ansible command %s" % str(response)
        if 'failed' in response['contacted'][system_ip]:
            return False, "[ossec_verify_server_config_file] Something wrong happened while running ansible command %s" % str(response)
        if response['contacted'][system_ip]['rc'] != 0:
            api_log.error(response['contacted'][system_ip]['stderr'])
            return False, response['contacted'][system_ip]['stderr']
    except Exception, exc:
        api_log.error("Ansible Error: An error occurred while running ossec_verify_server_config_file: %s" % str(exc))
        return False, str(exc)

    return True, ""


def ossec_verify_agent_config_file(system_ip, path):
    try:
        command = "/var/ossec/bin/verify-agent-conf -f %s" % path
        response = _ansible.run_module(host_list=[system_ip], module="shell", args=command, use_sudo=True)
        if system_ip in response['dark'] or 'unreachable' in response:
            return False, "[ossec_verify_server_config_file] Something wrong happened while running ansible command %s" % str(response)
        if 'failed' in response['contacted'][system_ip]:
            return False, "[ossec_verify_server_config_file] Something wrong happened while running ansible command %s" % str(response)
        if response['contacted'][system_ip]['rc'] != 0:
            api_log.error(response['contacted'][system_ip]['stderr'])
            return False, response['contacted'][system_ip]['stderr']
    except Exception, exc:
        api_log.error("Ansible Error: An error occurred while running ossec_verify_server_config_file: %s" % str(exc))
        return False, str(exc)

    return True, ""


def ossec_get_agentless_passlist(system_ip, destination_path=""):
    try:
        agentless_passfile = "/var/ossec/agentless/.passlist"
        # From ansible doc: Recursive fetching may be supported in a later release.
        response = _ansible.run_module(host_list=[system_ip], module="fetch", args="dest=%s src=%s flat=yes fail_on_missing=yes" % (destination_path, agentless_passfile), use_sudo=True)
        result, msg = ansible_is_valid_response(system_ip, response)
        if not result:
            return False, str(msg)

        success, result = set_ossec_file_permissions(destination_path)
        if not success:
            return False, str(result)
    except Exception as err:
        return False, "[ossec_get_configuration_rule] Something wrong happened while running ansible command %s" % str(err)
    return True, destination_path


def ossec_put_agentless_passlist(system_ip, local_passfile):
    """
        Return the passlist agentless file
    """
    try:
        agentless_passfile = "/var/ossec/agentless/.passlist"
        cmd_args = "src=%s dest=%s force=yes owner=root group=ossec mode=644" % (local_passfile, agentless_passfile)
        response = _ansible.run_module(host_list=[system_ip], module="copy", args=cmd_args, use_sudo=True)
        result, msg = ansible_is_valid_response(system_ip, response)
        if not result:
            return False,  str(msg)

    except Exception as err:
        return False, "[ossec_get_configuration_rule] Something wrong happened while running ansible command %s" % str(err)
    return True, "Done"


def ossec_get_agentless_list(system_ip):
    """Retrieves the list of configured agentless
    :param system_ip: System ip The ip of the sensor we are going to consult
    :return (success,data) where success is True on success False otherwise
    """
    try:
        command = "cat /var/ossec/agentless/.passlist || true"
        response = _ansible.run_module(host_list=[system_ip], module="shell", args=command, use_sudo=True)
        result, msg = ansible_is_valid_response(system_ip, response)
        if not result:
            return False, msg
        script_return_code = int(response['contacted'][system_ip]['rc'])
        script_output = response['contacted'][system_ip]['stdout']
        if script_return_code != 0:
            return False, make_err_message("[ossec_get_agentless_list]", DEFAULT_ERR_MSG, str(response))
        output = {}
        for line in script_output.split("\n"):
            if line != '' and line.find("Available host") < 0:
                parts = line.split('|')
                if len(parts) == 3:
                    output[parts[0]] = {'pass': parts[1], 'ppass': parts[2]}
    except Exception as err:
        return False, make_err_message("[ossec_get_agentless_list]", DEFAULT_ERR_MSG, str(err))
    return True, output


def ossec_get_ossec_agent_detail(system_ip, agent_id):
    """Retrieves information about the given agent id
    :param system_ip: System ip The ip of the sensor we are going to consult
    :param agent_id: Agent_id
    :return (success,data) where success is True on success False otherwise
    """
    if re.match(r"^[0-9]{1,4}$", agent_id) is None:
        return False, "Invalid agent ID. The agent ID has to be 1-4 digital characters"
    try:
        command = "/var/ossec/bin/agent_control -i %s -s" % agent_id
        response = _ansible.run_module(host_list=[system_ip], module="shell", args=command, use_sudo=True)
        result, msg = ansible_is_valid_response(system_ip, response)
        if not result:
            return False, msg
        script_return_code = int(response['contacted'][system_ip]['rc'])
        script_output = response['contacted'][system_ip]['stdout']
        if script_return_code != 0:
            return False, "[ossec_get_ossec_agent_detail] Something wrong happened while running ansible command %s" % str(script_output)
        output = []
        for line in script_output.split("\n"):
            if line != '':
                output.append(line)
    except Exception as err:
        return False, "[ossec_get_ossec_agent_detail] Something wrong happened while running ansible command %s" % str(err)
    return (True, output)


def ossec_get_syscheck(system_ip, agent_id):
    """
        Retrieves the modified files detected by the agent (/var/ossec/bin/syscheck_control -s -i <agent_id>

        :param system_ip: IP of the sensor
        :param agent_id: Agent id, must be \d{1,4}
        :return (success, data) where success is True in success, False otherwise. Data is a list of modified files
    """
    status, msg = is_valid_agent_id(agent_id)
    if not status:
        return False, msg
    try:
        command = "/var/ossec/bin/syscheck_control -s -i %s " % agent_id
        response = _ansible.run_module(host_list=[system_ip], module="shell", args=command, use_sudo=True)
        result, msg = ansible_is_valid_response(system_ip, response)
        if not result:
            return False, msg
        script_return_code = int(response['contacted'][system_ip]['rc'])
        script_output = response['contacted'][system_ip]['stdout']
        if script_return_code != 0:
            return False, "[ossec_get_syscheck] Something wrong happened while running ansible command %s" % str(response['contacted'][system_ip])
        output = {}
        index = 0
        for line in script_output.split("\n"):
            if line != '':
                output[index] = line
                index += 1
    except Exception as err:
        return False, make_err_message("[ossec_get_syscheck]", DEFAULT_ERR_MSG, str(err))

    return True, output  # Ignore the header and return the list


# Todo: replace agent_id validation with this function.
def is_valid_agent_id(agent_id):
    """ Checks if agent ID is valid or not.

    Args:
        agent_id: (str) Agent ID string

    Returns:
        status - True or False
        msg - err message
    """
    if re.match(r"^[0-9]{1,4}$", agent_id) is None:
        return False, 'Invalid agent ID. The agent ID has to be 1-4 digital characters'

    return True, ''


# Todo: replace error formatting with this function.
def make_err_message(func_name, err_msg="", err_reason=""):
    """ Makes unified error message.

    Args:
        func_name: (str) Name of the func which called it.
        err_msg: (str) Err msg itself.
        err_reason: (str) Reason why it happens.

    Returns:
        Formatted err_msg string.
    """
    return "{} {}{} {}".format(func_name, err_msg, ':' if err_msg and err_reason else '', err_reason)
