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


PASSLIST_PATH='/var/ossec/agentless/.passlist'

QUEUE_PATH='/var/ossec/queue/'

#Agentless IP
if valid_ip $1; then
    AGENTLESS_IP=$1
else 
    echo -e "Error!" 
    echo "Invalid Agentless IP. Format allowed: nnn.nnn.nnn.nnn. Entered IP: $1"
    exit 1
fi

#Delete all the files with the ip of the agentless deleted
rm -f $QUEUE_PATH"agentless/"*"$AGENTLESS_IP"   


AGENTLESS_USER=`grep "$AGENTLESS_IP" $PASSLIST_PATH | cut -d'|' -f1`

if [ -n "$AGENTLESS_USER" ]; then

    #Delete agentless information from var/ossec/queue/syscheck
    rm -f $QUEUE_PATH"syscheck/"*"$AGENTLESS_USER"* 

    #Delete agentless information from var/ossec/queue/rootcheck
    rm -f $QUEUE_PATH"rootcheck/"*"$AGENTLESS_USER"*
fi

#Delete agentless from .passlist file
sed -i /$AGENTLESS_IP/d $PASSLIST_PATH
