#!/bin/bash

if test -z "$1"; then
#	DB="ossim"
	DB="alienvault"
else
	DB="$1"
fi

if [ ! -f "/etc/ossim/ossim_setup.conf" ];then
        echo "ossim_setup.conf not found"
        exit 0
fi

HOST=`grep db_ip /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`

if test -z "$HOST"; then
	HOST=localhost
fi

mysql -A -u `grep ^user= /etc/ossim/ossim_setup.conf | cut -f 2 -d "=" | sed '/^$/d' ` -h $HOST  -p`grep ^pass= /etc/ossim/ossim_setup.conf | cut -f 2 -d "=" | sed '/^$/d'` $DB
