#!/bin/bash 

#Number of rows in log files
NUM_ROWS=`echo "$2" | /bin/egrep "^[0-9]{1,5}$"`

if [ -z "$NUM_ROWS" ]; then
	echo -e "Error!"
	echo "Number of rows must be an integer. Entered number: $2"
	exit 1
fi


if [ "$1" == "ossec" ]; then
    tail -n$NUM_ROWS /var/ossec/logs/ossec.log
fi

if [ "$1" == "alert" ]; then
    tail -n$NUM_ROWS /var/ossec/logs/alerts/alerts.log
fi
