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

import ansible.runner
import ansible.playbook
import ansible.constants as AnsibleConstants
import ansible.callbacks as ans_callbacks

# DEFAULT_ANSIBLE_CONFIGURATION_FILE = "/etc/ansible/ansible.cfg"
PLAYBOOKS = {
    'REMOVE_OLD_FILES': '/etc/ansible/playbooks/maintenance/remove_files_older_than.yml',
    'AGENT_STATS_PLAYBOOK': '/etc/ansible/playbooks/agent_stats.yml',
    'OSSEC_WIN_DEPLOY': '/etc/ansible/playbooks/ossec_win_deploy/main.yml',
    'UNTAR_VPN_AND_START': '/etc/ansible/playbooks/untar_vpn_and_start.yml',
    'SET_CRYPTO_FILES': '/etc/ansible/playbooks/auth/set_crypto_files.yml',
    'ASYNC_RECONFIG': '/etc/ansible/playbooks/common/async_reconfig.yml',
    'ASYNC_UPDATE': '/etc/ansible/playbooks/common/async_update.yml',
    'ENABLE_TUNNEL': '/etc/ansible/playbooks/maintenance/reverse_tunnel.yml',
    'DISABLE_TUNNEL': '/etc/ansible/playbooks/maintenance/reverse_tunnel_close.yml',
}
EVENTS = []
CONFIG_FILE = "/etc/ossim/ossim_setup.conf"


class Singleton(type):
    '''
    Class Singleton.
    No need to explain, credits entirely to:
    http://natefactorial.com/2009/11/19/python-singletons-a-cool-way/
    '''

    def __init__(self, name, bases, dict):
        super(Singleton, self).__init__(name, bases, dict)
        self.instance = None

    def __call__(self, *args, **kw):
        if self.instance is None:
            self.instance = super(Singleton, self).__call__(*args, **kw)

        return self.instance


class AVAnsibleCallbacks(object):
    # using same callbacks class for both runner and playbook

    def __init__(self):
        self._lasterror = ''
        pass

    @property
    def lasterror(self):
        return self._lasterror

    def set_playbook(self, playbook):
        self.playbook = playbook

    def on_no_hosts_remaining(self):
        pass

    def on_no_hosts_matched(self):
        print "No host matched"

    def on_start(self):
        EVENTS.append('start')

    def on_skipped(self, host, item=None):
        EVENTS.append(['skipped', [host]])

    def on_import_for_host(self, host, filename):
        EVENTS.append(['import', [host, filename]])

    def on_error(self, host, msg):
        EVENTS.append(['stderr', [host, msg]])

    def on_not_import_for_host(self, host, missing_filename):
        pass

    def on_notify(self, host, handler):
        EVENTS.append(['notify', [host, handler]])

    def on_task_start(self, name, is_conditional):
        EVENTS.append(['task start', [name, is_conditional]])
        self._lasterror = ''

    def on_failed(self, host, results, ignore_errors):
        self._lasterror = {host: results}
        EVENTS.append(['failed', [host, results, ignore_errors]])

    def on_ok(self, host, result):
        # delete certain info from host_result to make test comparisons easier
        host_result = result.copy()
        for k in ['ansible_job_id', 'results_file', 'md5sum', 'delta', 'start', 'end']:
            if k in host_result:
                del host_result[k]
        for k in host_result.keys():
            if k.startswith('facter_') or k.startswith('ohai_'):
                del host_result[k]
        EVENTS.append(['ok', [host, host_result]])

    def on_play_start(self, pattern):
        EVENTS.append(['play start', [pattern]])

    def on_async_ok(self, host, res, jid):
        EVENTS.append(['async ok', [host]])

    def on_async_poll(self, host, res, jid, clock):
        EVENTS.append(['async poll', [host]])

    def on_async_failed(self, host, res, jid):
        EVENTS.append(['async failed', [host]])

    def on_unreachable(self, host, msg):
        EVENTS.append(['failed/dark', [host, msg]])

    def on_setup(self):
        pass

    def on_no_hosts(self):
        pass


class Ansible(object):
    __metaclass__ = Singleton

    """Ansible manager"""

    # https://github.com/ansible/ansible/blob/devel/lib/ansible/runner/__init__.py
    def __init__(self, username='avapi'):
        self.__username = username
        self.__host_list = AnsibleConstants.DEFAULT_HOST_LIST
        self.callbacks = AVAnsibleCallbacks()

    def run_module(self, host_list, module, args, timeout=AnsibleConstants.DEFAULT_TIMEOUT,
                   forks=1, ans_remote_user=AnsibleConstants.DEFAULT_REMOTE_USER,
                   ans_remote_pass=AnsibleConstants.DEFAULT_REMOTE_PASS, use_sudo=True, local=False):
        """Runs an ansible module and returns its results
        :rtype : The result of the ansible execution
        """
        use_transport = AnsibleConstants.DEFAULT_TRANSPORT
        if local or use_transport == 'local' or host_list == ['127.0.0.1']:
            use_transport = "local"
            host_list = ["127.0.0.1"]

            # From: http://www.ansibleworks.com/docs/playbooks2.html#id20
            # To run an entire playbook locally, just set the "hosts:" line to "hosts:127.0.0.1" and then run the playbook like so:
            # ansible-playbook playbook.yml --connection=local
        runner = ansible.runner.Runner(host_list=host_list if host_list != [] else self.__host_list,
                                       module_name=module,
                                       module_args=args,
                                       transport=use_transport,
                                       remote_user=ans_remote_user,
                                       remote_pass=ans_remote_pass,
                                       sudo=use_sudo,
                                       timeout=timeout
                                       )
        data = runner.run()
        return data

    def run_playbook(self,
                     playbook,
                     host_list=None,
                     use_sudo=True,
                     local=False,
                     extra_vars={},
                     ans_remote_user=AnsibleConstants.DEFAULT_REMOTE_USER,
                     ans_remote_pass=AnsibleConstants.DEFAULT_REMOTE_PASS,
                     only_tags=None,
                     skip_tags=None):
        """Runs an ansible playbook

        From ansible doc:
        lib/ansible/__init__.py

        playbook:         path to a playbook file
        host_list:        path to a file like /etc/ansible/hosts
        module_path:      path to ansible modules, like /usr/share/ansible/
        forks:            desired level of paralellism
        timeout:          connection timeout
        remote_user:      run as this user if not specified in a particular play
        remote_pass:      use this remote password (for all plays) vs using SSH keys
        sudo_pass:        if sudo==True, and a password is required, this is the sudo password
        remote_port:      default remote port to use if not specified with the host or play
        transport:        how to connect to hosts that don't specify a transport (local, paramiko, etc)
        callbacks         output callbacks for the playbook
        runner_callbacks: more callbacks, this time for the runner API
        stats:            holds aggregate data about events occuring to each host
        sudo:             if not specified per play, requests all plays use sudo mode
        inventory:        can be specified instead of host_list to use a pre-existing inventory object
        check:            don't change anything, just try to detect some potential changes

        only_tags:        List of tags to include. Only run task of tasks in the include list.
        skip_tags:        List of tags to skip. Run all task but tagged in the skip list.
        """
        use_transport = AnsibleConstants.DEFAULT_TRANSPORT
        if local:
            use_transport = "local"
            host_list = []
            host_list.append("127.0.0.1")
        playbook = ansible.playbook.PlayBook(playbook=playbook,
                                             host_list=host_list if host_list != [] else self.__host_list,
                                             stats=ans_callbacks.AggregateStats(),
                                             callbacks=self.callbacks,
                                             runner_callbacks=self.callbacks,
                                             transport=use_transport,
                                             sudo=use_sudo,
                                             extra_vars=extra_vars,
                                             remote_user=ans_remote_user,
                                             remote_pass=ans_remote_pass,
                                             only_tags=only_tags,
                                             skip_tags=skip_tags)
        playbook.SETUP_CACHE.clear()
        result = playbook.run()
        # The result is a dict. I'm going to add
        # The "alienvault" key with our "internal"
        # values
        result['alienvault'] = {'lasterror': self.callbacks.lasterror}
        return result
