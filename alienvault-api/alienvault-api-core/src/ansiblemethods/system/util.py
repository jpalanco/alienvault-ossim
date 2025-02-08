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

from ansiblemethods.ansiblemanager import Ansible, PLAYBOOKS, CONFIG_FILE
from ansiblemethods.helper import ansible_is_valid_response

ansible = Ansible()


def send_email(host, port, sender, recipients, subject, body, user, passwd, use_ssl, attachments):
    """
    Send an email.
    """
    args = "host=%s \
    port=%s \
    sender=%s \
    recipients=%s \
    subject=\"%s\" \
    body=\"%s\" \
    user=%s \
    passwd=%s \
    use_ssl=%s \
    attach=%s" % (host, port, sender, recipients, subject, body, user, passwd, use_ssl, attachments)

    data = ansible.run_module(
        host_list=[],
        module="av_mail",
        args=args,
        use_sudo=False,
        local=True
    )
    result = True
    if 'failed' in data:
        result = False
    return result, data


def ping(host):
    """
    Ping the Ansible connection to a host.
    """
    return ansible.run_module(
        host_list=[host],
        module="ping",
        args="",
        use_sudo=False
    )


def fetch_if_changed(remote_ip, remote_file_path, local_ip, local_file_path):
    """ Fetch remote file if it's not the same than the local one or local one does not exist
    :param remote_ip: The system ip where the remote file is
    :param remote_file_path: Path to remote file
    :param local_ip: The local system ip
    :param local_file_path: Path to local file
    :returns True if the file was fetched, False elsewhere
    """
    # Check parameters
    if not local_ip or not remote_ip or not remote_file_path or not local_file_path:
        return False, "Invalid parameters"

    local_md5, remote_md5 = None, None

    # Get local file md5
    try:
        response = ansible.run_module(
            host_list=[local_ip],
            module='stat',
            args="path=" + local_file_path
        )
    except Exception, exc:
        return False, "Ansible Error: An error occurred while running stat module: %s" % str(exc)

    (success, msg) = ansible_is_valid_response(local_ip, response)
    if success:
        if not response['contacted'][local_ip]['stat']['exists']:
            local_md5 = 0
        else:
            local_md5 = response['contacted'][local_ip]['stat']['md5']

    # Get remote file md5
    try:
        response = ansible.run_module(
            host_list=[remote_ip],
            module='stat',
            args="path=" + remote_file_path
        )
    except Exception, exc:
        return False, "Ansible Error: An error occurred while running stat module: %s" % str(exc)

    (success, msg) = ansible_is_valid_response(remote_ip, response)
    if success:
        if not response['contacted'][remote_ip]['stat']['exists']:
            return False, "Remote files does not exist"
        else:
            remote_md5 = response['contacted'][remote_ip]['stat']['md5']

    if local_md5 and remote_md5 and local_md5 == remote_md5:
        return False, "Files already in sync"
    else:
        try:
            fetch_args = "src={} dest={} flat=yes validate_md5=yes".format(remote_file_path, local_file_path)
            response = ansible.run_module(
                host_list=[remote_ip],
                module='fetch',
                args=fetch_args
            )
        except Exception as e:
            return False, "Ansible Error: An error occurred while running fetch module: {}".format(e)

        (success, msg) = ansible_is_valid_response(remote_ip, response)
        if success:
            return success, "File retrieved"
        else:
            return success, msg


def rsync(local_ip, src, dest):
    """ Rsync pull remote file to local path
    :param remote_ip: The system ip where the remote file is
    :param remote_file_path: Path to remote file
    :param local_ip: The local system ip
    :param local_file_path: Path to local file
    :returns True if the file was fetched, False elsewhere
    """
    # Check parameters
    if not all((local_ip, src, dest)):
        return False, "Invalid parameters: {}".format(locals())

    ssh_key_file = '/var/ossim/ssl/local/private/cakey_avapi.pem'
    # Use -i option to know if the file has changed
    # To avoid warning massage in ansible output "-q" key has been added:
    # u'stderr': u"Warning: Permanently added '192.168.87.198' (RSA) to the list of known hosts.",
    rsync_command = 'rsync -aizPe "ssh -q -o UserKnownHostsFile=/dev/null ' \
                    '-o StrictHostKeyChecking=no -i {}" {} {}'.format(ssh_key_file, src, dest)

    # Rsync pull remote file
    try:
        response = ansible.run_module(
            host_list=[local_ip],
            module='command',
            args=rsync_command,
            use_sudo=False
        )
    except Exception as e:
        return False, "Ansible Error: An error occurred while rsyncing file(s): {}".format(e)

    success, msg = ansible_is_valid_response(local_ip, response)
    if not success or response['contacted'][local_ip]['stderr'] != '':
        return success, "Could't retrieve file"
    elif response['contacted'][local_ip]['stdout'] == '':
        return False, "File(s) already in sync"
    else:
        return success, "File(s) synced"


def rsync_pull(remote_ip, remote_file_path, local_ip, local_file_path):
    """ Rsync pull remote file to local path
    :param remote_ip: The system ip where the remote file is
    :param remote_file_path: Path to remote file
    :param local_ip: The local system ip
    :param local_file_path: Path to local file
    :returns True if the file(s) was fetched, False elsewhere
    """
    # Check parameters
    if not all((remote_ip, remote_file_path, local_ip, local_file_path)):
        return False, "Invalid parameters: {}".format(locals())
    src = "{}:{}".format(remote_ip, remote_file_path)
    destination = local_file_path
    return rsync(local_ip=local_ip, src=src, dest=destination)


def rsync_push(local_ip, local_file_path, remote_ip, remote_file_path):
    """Rsync pull remote file to local path
    :param local_ip: The local system ip
    :param local_file_path: Path to local file(s)
    :param remote_ip: The target system ip
    :param remote_file_path: target path

    :returns True if the file(s) was pushed, False elsewhere
    """
    # Check parameters
    if not all((local_ip, local_file_path, remote_ip, remote_file_path)):
        return False, "Invalid parameters: {}".format(locals())
    src = local_file_path
    destination = "{}:{}".format(remote_ip, remote_file_path)
    return rsync(local_ip=local_ip, src=src, dest=destination)
