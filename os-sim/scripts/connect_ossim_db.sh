#!/bin/bash

if test -z "$1"; then
#	DB="ossim"
	DB="alienvault"
else
	DB="$1"
fi

if [ ! -f "/etc/ossim/ossim_setup.conf" ];then
        >&2 echo "ossim_setup.conf not found"
        exit 0
fi

HOST=`grep ^db_ip= /etc/ossim/ossim_setup.conf | cut -f 2 -d "=" | sed '/^$/d'`
USER=`grep ^user= /etc/ossim/ossim_setup.conf | cut -f 2 -d "=" | sed '/^$/d'`
PASS=`grep ^pass= /etc/ossim/ossim_setup.conf | cut -f 2 -d "=" | sed '/^$/d'`

if test -z "$HOST"; then
	HOST=localhost
fi

sshpass -p $PASS mysql --default-character-set=utf8 -A -u $USER -h $HOST $DB -p -e "exit" &>/dev/null

if [ $? -ne 0  ]; then
	>&2 echo "Access denied. Trying old settings..."

	if [ ! -f /etc/ossim/ossim_setup.conf_last ]; then
        	>&2 echo "ossim_setup.conf_last not found"
        	exit 0
	fi

	HOST=`grep ^db_ip= /etc/ossim/ossim_setup.conf_last | cut -f 2 -d "=" | sed '/^$/d'`
	USER=`grep ^user= /etc/ossim/ossim_setup.conf_last | cut -f 2 -d "=" | sed '/^$/d'`
	PASS=`grep ^pass= /etc/ossim/ossim_setup.conf_last | cut -f 2 -d "=" | sed '/^$/d'`
fi

sshpass -p $PASS mysql --default-character-set=utf8 -A -u $USER -h $HOST $DB -p
