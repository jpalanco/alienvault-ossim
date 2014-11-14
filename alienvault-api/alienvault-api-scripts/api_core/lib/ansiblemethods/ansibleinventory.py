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

from ansible.inventory import Inventory
from ansible.inventory.group import Group
from ansible.inventory.host import Host
from shutil import copyfile
import re
import os


from api_log import *
from apimethods.utils import get_uuid_string_from_bytes, get_ip_str_from_bytes
from db.methods.system import get_systems, get_all_ip_systems


class AnsibleInventoryManagerError(Exception):
    """Base Ansible Inventory Manager exception"""
    def __init__(self,msg,code):
        self.msg = msg
        self.code = code

    def __str__(self):
        return repr("[%s] %s" %(self.code,self.msg))


class AnsibleInventoryManagerFileNotFound(AnsibleInventoryManagerError):
    ANSIBLE_INVENTORY_FILE_NOT_FOUND = 100001

    def __init__(self, msg):
        AnsibleInventoryManagerError.__init__(self, msg, AnsibleInventoryManagerFileNotFound.ANSIBLE_INVENTORY_FILE_NOT_FOUND)


class AnsibleInventoryManager():
    """Class to handle the ansible inventory file.
    This class will allow you to add groups/hosts and remove them from the inventory file"""
    #FROM http://www.ansibleworks.com/docs/patterns.html
    ALLOWED_VARIABLES = ['ansible_ssh_host',
                         'ansible_ssh_port',
                         'ansible_ssh_user',
                         'ansible_ssh_pass',
                         'ansible_connection',
                         'ansible_ssh_private_key_file',
                         'ansible_syslog_facility',
                         'ansible_python_interpreter',
                         ]
    # NOTE:Works for anything such as ruby or perl and works just like
    # ansible_python_interpreter.
    # This replaces shebang of modules which will run on that host.

    def __init__(self, inventory_file="/etc/ansible/hosts"):
        self.__inventory_file = inventory_file
        self.__dirty = False
        if not os.path.exists(self.__inventory_file):
            raise AnsibleInventoryManagerFileNotFound("File: %s Not found or not accessible" % self.__inventory_file)
        self.__inventory = Inventory(inventory_file)

    def get_hosts(self):
        """return the list of hosts
        Returns a list host ips"""
        host_list = [host.name for host in self.__inventory.get_hosts()]
        return host_list

    def get_groups(self):
        """return the groups
        Returns a list of objects: ansible.inventory.group.Group"""
        return self.__inventory.get_groups()

    def get_groups_for_host(self,host):
        """return the groups list where the given host appears"""
        return self.__inventory.groups_for_host(host)

    def get_group(self, groupname):
        """Returns the given group"""
        return self.__inventory.get_group(groupname)

    def delete_host(self,host_ip,group=""):
        """Removes a host from a given group
        if group is empty, removes the host from all the groups
        """
        self.__dirty = True
        groups = []
        if group == "":
            groups = [group.name for group in self.get_groups_for_host(host_ip)]
        else:
            groups.append(group)
        for group in groups:
            grp = self.__inventory.get_group(group)
            new_host_list = [host for host in grp.get_hosts() if host.name != host_ip]
            grp.hosts = new_host_list

    def add_host(self, host_ip, add_to_root=True, group_list=[], var_list={}):
        """Add a host ip to the ansible host file
        This is a simple function to add hosts to
        add_to_root = Adds the host to the root group (unnamed)
        groups_list: List of groupnames where the host should appears.
        var_list: Variable list. see allowed_variables."""
        #root group in unnamed, but in the inventory object
        # is the 'ungrouped' group
        self.__dirty = True
        new_host = Host(host_ip)
        for key,value in var_list.iteritems():
            if self.is_allowed_variable(key):
                new_host.set_variable(key,value)
        if add_to_root:
            if 'ungrouped' not in group_list:
                group_list.append('ungrouped')

        #Check groups. The ansible inventory should contain each of the groups.
        for group in group_list:
            if not self.__inventory.get_group(group):
                new_group= Group(group)
                self.__inventory.add_group(new_group)
            grp = self.__inventory.get_group(group)
            host_names = [host.name for host in grp.get_hosts()]
            if new_host.name not in host_names:
                grp.add_host(new_host)

    def is_dirty(self):
        return self.__dirty

    def is_allowed_variable(self, variable):
        """Checks if the given variable is an allowed variable"""
        if variable in self.ALLOWED_VARIABLES:
            return True
        elif re.match("ansible_(.+)_interpreter", variable):
            return True
        return False

    def save_inventory(self, backup_file=""):
        """Saves the inventory file. If a backup_file is given,
        a backup will be done before re-write the file"""

        try:
            if backup_file != "":
                copyfile(self.__inventory_file, backup_file)
            data = ""
            for group in self.__inventory.get_groups():
                ingroup = False
                if group.name == "all":
                    continue
                elif group.name != "ungrouped":
                    data += "[%s]\n" % group.name
                    ingroup = True
                strvars = ""
                for host in group.get_hosts():
                    for key, value in host.get_variables().iteritems():
                        if key in AnsibleInventoryManager.ALLOWED_VARIABLES:
                            strvars += "%s=%s    " % (key, value)
                    if ingroup:
                        data += "\t%s\t%s\n" % (host.name, strvars)
                    else:
                        data += "%s\t%s\n" % (host.name, strvars)
            ansiblehostfile = open(self.__inventory_file, "w")
            ansiblehostfile.write(data)
            ansiblehostfile.close()
        except Exception, e:
            error("Error doing the %s backup: %s" % (self.__inventory_file, str(e)))


class CheckAnsibleInventory():

    def process(self):
        rt = True
        try:
            aim = AnsibleInventoryManager()
            result, systems = get_all_ip_systems()
            # 1 - Check the systems against our inventory file.
            if result:
                for system_id, system_ips in systems.items():
                    # info("Checking for system_ip" + str(system_ips))
                    for system_ip in system_ips.values():
                        if (system_ip not in aim.get_hosts()): 
                            debug("Ansible host file... adding new sensor %s" % system_ip)
                            aim.add_host(system_ip)
                if aim.is_dirty():
                    aim.save_inventory()
            else:
                info("CheckAnsibleInventory .. no systems")
        except Exception,e:
            error("An error occurred while checking for alienvault systems: %s" % str(e))
            rt = False

        return rt
