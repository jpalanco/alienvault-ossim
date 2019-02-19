#!/usr/bin/env bash

# Script takes the following arguments:
# $1 - server ip
# $2 - agent id

# Commands from old file: gen_install_exe.pl
cp installer/default-ossec.conf installer/ossec.conf
sed -i s/INSERT_HERE_SERVER_IP/$1/g installer/ossec.conf

mkdir -p /usr/share/ossec-generator/agents
cd /usr/share/ossec-generator/installer/
sh make.sh
mv ossec-agent-alienvault-installer.exe /usr/share/ossec-generator/agents/ossec_installer_$2.exe

# Commands from perms.sh
chown www-data.ossec /var/ossec/etc/client.keys
chmod 660 /var/ossec/etc/client.keys