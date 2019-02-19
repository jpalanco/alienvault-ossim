#!/usr/bin/env python
# -*- coding: utf-8 -*-

import os
import sys
import argparse
import subprocess

sys.path.append("/usr/share/alienvault-center/av-libs/avconfig")
from ossimsetupconfig import AVOssimSetupConfigHandler


parser = argparse.ArgumentParser(description='Install exe generator')
parser.add_argument("--agent_id", required=True, type=str,
                    help="./gen_install_exe.py --agent_id 001")
parser.add_argument("--debug", action='store_true',
                    help="./gen_install_exe.py --agent_id 001 --debug")
args = parser.parse_args()
if args.debug:
    print "Arguments: ", args


licence = "(c) Alienvault LLC 2010, this is not free software, "
licence += "if unsure you're not allowed to reproduce it"
print licence

if not os.path.exists('./installer'):
    sys.exit("Need to have OSSEC installer files under ./installer")

if not os.path.exists("/var/ossec/"):
    sys.exit("Local OSSEC installation required under /var/ossec/, exiting")


def fetch_data_from_client_keys_file():
    """String example:
    001 Host-192-168-96-102 192.168.96.102 516bfea034e29145ef7677f1bb21750b44f
    """
    fetched_data = {
        'id': args.agent_id
    }
    with open('/var/ossec/etc/client.keys') as keys_file:
        for line in keys_file:
            if line.startswith(args.agent_id + " "):
                fetched_data['line'] = line     # whole line
                line = line.split()
                fetched_data['host'] = line[1]  # host
                fetched_data['ip'] = line[2]    # ip address
                fetched_data['key'] = line[3]   # key
                break

    if args.debug:
        print "Fetched_data: ", fetched_data
    return fetched_data


def fetch_server_ip(raw_ip):
    if raw_ip == '0.0.0.0/0':
        # Fetch ip address of the server from ossim_setup.conf
        # when client uses DHCP
        config_parser = AVOssimSetupConfigHandler()
        server_ip = config_parser.get_general_admin_ip()
        if args.debug:
            print "Client uses DHCP: {}".format(raw_ip)
            print "Fetched ip from ossim_conf: {}".format(server_ip)
        return server_ip
    command = "ip route get {} | head -1 | tr -s ' '".format(raw_ip)
    server_ip = subprocess.check_output(
        command, shell=True, stderr=subprocess.STDOUT
        )
    if args.debug:
        print "command: ", command
        print "command output: ", server_ip.strip()
    return server_ip.strip().split()[-1]


def create_client_keys_file(to_write):
    with open("installer/client.keys", "w") as keys_file:
        keys_file.write(to_write + "\n")


def run_command(command):
    temp = subprocess.check_output(
        command, shell=True, stderr=subprocess.STDOUT
        )
    if args.debug:
        print "Trying to run: ", command
        print "Output: ", temp


data = fetch_data_from_client_keys_file()
data['server_ip'] = fetch_server_ip(data['ip'])

create_client_keys_file(data['line'])
run_command("/usr/share/ossec-generator/make_exe.sh {} {}".format(
    data['server_ip'], data['id']
))

print "Congratulations. Your unattended OSSEC installer " \
      "is waiting at ossec_installer_{}.exe".format(data['id'])
