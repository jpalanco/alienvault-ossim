import getpass
import sys

from ansiblemethods.ansiblemanager import Ansible, PLAYBOOKS
from db.methods.system import db_get_systems, get_system_id_from_local
from ansiblemethods.system.system import ansible_remove_key_from_known_host_file, ansible_add_system
from avconfig.ossimsetupconfig import AVOssimSetupConfigHandler

ossim_setup = AVOssimSetupConfigHandler()
ansible = Ansible()


def add_system_with_new_key(local_system_id, remote_system_ip):
    number_of_tries = 0
    status = False

    while not status and number_of_tries < 3:
        number_of_tries += 1
        msg = 'Please enter root password for {}:\n '.format(remote_system_ip)
        password = getpass.getpass(msg)
        status, result = ansible_add_system(local_system_id, remote_system_ip, password)
        if not status:
            print(result)

    return status


def confirm(prompt='Confirm', default=False):
    """ Prompts for yes or no response from the user. Returns True for yes and False for no.

    Args:
        prompt: (str) Prompt message
        default: (bool) Default value

    """
    prompt = '%s (%s/%s): ' % (prompt, 'Y', 'n') if default else (prompt, 'N', 'y')

    while True:
        ans = raw_input(prompt)
        if not ans:
            return default
        if ans not in ('y', 'Y', 'n', 'N'):
            print 'please enter y or n.'
            continue
        return ans.lower() == 'y'


def main():
    profile = ossim_setup.get_general_profile()
    if profile == 'Database':
        return

    try:
        status, ip_list = db_get_systems()
    except AssertionError:
        # Show additional notification on sensors.
        print("Warning: Please reset the AlienVault API key on connected server to avoid connectivity issues!\n")
        return

    if not status or not ip_list:
        return

    (success, local_system_id) = get_system_id_from_local()
    if not success:
        return

    local_ip = ossim_setup.get_general_admin_ip(refresh=True)

    # There is no need to add system itself - remove it from list.
    if local_ip in ip_list:
        ip_list.remove(local_ip)

    last_asset = ip_list[-1] if ip_list else None

    for remote_system_ip in ip_list:
        if not add_system_with_new_key(local_system_id, remote_system_ip):
            # Skip this step if current asset is the last in the list.
            if remote_system_ip != last_asset and not confirm('Do you want to continue with other components?',
                                                              default=True):
                    sys.exit('Failed to add remote system {} with a new API key. Exiting...'.format(remote_system_ip))


main()
