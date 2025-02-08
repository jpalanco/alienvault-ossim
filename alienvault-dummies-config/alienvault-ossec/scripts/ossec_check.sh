#!/bin/bash

CLIENT_KEYS_PATH='/var/ossec/etc/client.keys'

#Check Agent name
AGENT_ID=$2
TEST_AGENT_ID=`echo "$AGENT_ID" | /bin/egrep -o "^[0-9]{1,4}$"`

if [ ! $TEST_AGENT_ID ] ; then
    echo -e "Error!"
    echo "Invalid agent name. Entered agent name: $2"
    exit 1
fi

#Agent name

if [ "$AGENT_ID" = "000" ] || [ "$AGENT_ID" = "0" ]; then
    ROOTCHECK_FILE="/var/ossec/queue/rootcheck/rootcheck"
else
    AGENT_NAME=`grep "$AGENT_ID" $CLIENT_KEYS_PATH | cut -d' ' -f2`
    ROOTCHECK_FILE="/var/ossec/queue/rootcheck/($AGENT_NAME)*"
fi



if [ "$1" == "lastscan" ]; then
    ST_DATE=`egrep -osh '\!([0-9]+)!([0-9]+) Starting syscheck scan' ${ROOTCHECK_FILE} | egrep -o [0-9]+ | sort -r | head -1`

    if [ -n "$ST_DATE" ]; then
        ST_DATE=$(date -d @$ST_DATE)
    fi

    RT_DATE=`egrep -osh '\!([0-9]+)!([0-9]+) Starting rootcheck scan' ${ROOTCHECK_FILE} | egrep -o [0-9]+ | sort -r | head -1`

    if [ -n "$RT_DATE" ]; then
        RT_DATE=$(date -d @$RT_DATE)
    fi

    echo -e "Last syscheck scan started at: $ST_DATE"
    echo -e "Last rootcheck scan started at: $RT_DATE"
fi

if [ "$1" == "lastip" ]; then
    if [ "$AGENT_ID" = "000" ] || [ "$AGENT_ID" = "0" ]; then
        echo -e "127.0.0.1"
    else
        ls -lt '/var/ossec/queue/rootcheck/' | grep "($AGENT_NAME)" | perl -npe 's/.* (\d+\.\d+\.\d+\.\d+).*/$1/' | grep "^[0-9]" | head -n1
    fi
fi
