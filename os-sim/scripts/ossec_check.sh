#!/bin/bash

#Validate IP
function valid_ip()
{
    local  ip=$1
    local  stat=1

    if [[ $ip =~ ^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$ ]]; then
        OIFS=$IFS
        IFS='.'
        ip=($ip)
        IFS=$OIFS
        [[ ${ip[0]} -le 255 && ${ip[1]} -le 255 \
            && ${ip[2]} -le 255 && ${ip[3]} -le 255 ]]
        stat=$?
    fi
    return $stat
}



if [ "$1" == "lastscan" ]; then
    #Check Agent IP
    if valid_ip $2; then
        AGENT_IP=$2
    else 
        echo -e "Error!" 
        echo "Invalid Agent IP. Format allowed: nnn.nnn.nnn.nnn. Entered IP: $2"
        exit 1
    fi
else
    #Check Agent name
    AGENT_NAME=`echo "$2" | /bin/egrep -o "[^a-zA-Z0-9_\-]+"`
        
    if [ -z "$2" -o -n "$AGENT_NAME" ]; then
        echo -e "Error!"
        echo "Invalid agent name. Entered agent name: $2"
    exit 1
    fi
fi


if [ "$1" == "lastscan" ]; then
    grep 'Starting syscheck scan' '/var/ossec/queue/rootcheck/'*$2*
    grep 'Starting rootcheck scan' '/var/ossec/queue/rootcheck/'*$2*
fi

if [ "$1" == "lastip" ]; then       
    ls -lt '/var/ossec/queue/rootcheck/' | grep "($2)" | perl -npe 's/.* (\d+\.\d+\.\d+\.\d+).*/$1/' | grep "^[0-9]" | head -n1
fi
