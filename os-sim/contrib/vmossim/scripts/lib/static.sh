#!/bin/bash

ACT_PRO=`cat /etc/vmossim-profile`
VMOSSIM_VER="VMOSSIM 0704"

DIALOG=dialog

# colors
black='\E[30;47m'
red='\E[31;47m'
green='\E[32;47m'
yellow='\E[33;47m'
blue='\E[34;47m'
magenta='\E[35;47m'
cyan='\E[36;47m'
white='\E[37;47m'

# Retrieves the current runlevel
CURRENT_RUNLEVEL=`runlevel | cut -f2 -d\ `

### Profiles config
AVAIL_PROFILES="all-in-one sensor server"
# Services: same as init script in debian etch
ALLSERVICES="mysql mysql-ndb-mgm mysql-ndb ossim-framework ossim-server nagios2 ossim-agent apache2 nessusd osirismd osirisd ntop snort"
SERV_ALLINONE="mysql-ndb-mgm mysql mysql-ndb apache2 ossim-agent ossim-server ossim-framework nagios2 nessusd osirismd osirisd ntop snort"
SERV_SENSOR="ossim-agent nessusd osirisd ntop snort"
SERV_SERVER="mysql-ndb-mgm mysql mysql-ndb apache2 ossim-server ossim-framework nagios2 osirismd osirisd"

### Network configuration

TMP_NET="/tmp/netconfig.tmp$$"
NETDEVICES="$(cat /proc/net/dev | awk -F: '/eth.:|tr.:|wlan.:/{print $1}')"

TMP_CVS_DIR="/tmp/cvs-ossim"
PCK_DST="/root/packages"

TMP_OSV_DIR="/tmp/osvdb"
OSVDB_DB_URL="http://osvdb.org/exports/xmlDumpByID-Current.xml.bz2"

RULES_DIR="/etc/snort/rules/"
