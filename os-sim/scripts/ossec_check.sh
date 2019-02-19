#!/bin/bash

#Check Agent name
AGENT_NAME=`echo "$2" | sed 's/(server)//g'`
TEST_AGENT_NAME=`echo "$AGENT_NAME" | /bin/egrep -o "[^a-zA-Z0-9_\-]+"`
    
if [ -z "$2" -o -n "$TEST_AGENT_NAME" ]; then
    echo -e "Error!"
    echo "Invalid agent name. Entered agent name: $2"
exit 1
fi

if [ "$1" == "lastscan" ]; then    
    ST_DATE=`egrep -osh '\!([0-9]+) Starting syscheck scan' '/var/ossec/queue/rootcheck/('$AGENT_NAME')'* | egrep -o [0-9]+ | sort -r | head -1`
    RT_DATE=`egrep -osh '\!([0-9]+) Starting rootcheck scan' '/var/ossec/queue/rootcheck/('$AGENT_NAME')'* | egrep -o [0-9]+ | sort -r | head -1`
    echo -e "Last syscheck scan started at: $ST_DATE"
    echo -e "Last rootcheck scan started at: $RT_DATE"
fi

if [ "$1" == "lastip" ]; then       
    ls -lt '/var/ossec/queue/rootcheck/' | grep "($AGENT_NAME)" | perl -npe 's/.* (\d+\.\d+\.\d+\.\d+).*/$1/' | grep "^[0-9]" | head -n1
fi
