#!/bin/bash

#Validate IP or CIDR
function valid_ip_cidr()
{
    local ip=`echo $1 | tr a-z A-Z`
    local stat=1
    
    if [ "$ip" == "ANY" ]; then
        return 0
    fi
   
    if [[ $ip =~ ^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}(\/([0-9]|[0-2][0-9]|3[0-2]))?$ ]]; then
        OIFS=$IFS
        IFS='.'
        ip=($ip)
        IFS=$OIFS

        ip[3]=`echo ${ip[3]} | sed -E 's/\/[0-9]+$//'`

        [[ ${ip[0]} -le 255 && ${ip[1]} -le 255 && ${ip[2]} -le 255 && ${ip[3]} -le 255 ]]
        stat=$?
    fi
          
    return $stat
}


#Check agent name
AGENT_NAME=`echo "$1" | /bin/egrep -o "[^a-zA-Z0-9_\-]+"`

if [ -z "$1" -o -n "$AGENT_NAME" ]; then
	echo -e "Error!"
	echo "Invalid agent name. Entered agent name: $1"
	exit 1
fi


#Agent IP
if valid_ip_cidr $2; then
    AGENT_IP=$2
else 
    echo -e "Error!" 
	echo "Invalid Agent IP. Format allowed: nnn.nnn.nnn.nnn. Entered IP: $2"
	exit 1
fi

#Agent name
AGENT_NAME=$1

#Agent ID

if [ -f /var/ossec/etc/client.keys ]; then
    AGENT_ID=`cat /var/ossec/etc/client.keys | cut -d' ' -f1 | sort -n | tail -1 | awk '{sum+=$1+1} END { print sum }'`
else
    AGENT_ID="001"
fi

#When the first agent is created, the file client.keys is empty and the agent is empty
if [ -z "$AGENT_ID" ]; then
    AGENT_ID="001"
fi

#Create agent
echo 'A'$'\n'${AGENT_NAME}$'\n'${AGENT_IP}$'\n'${AGENT_ID}$'\n''y'$'\n''Q'$'\n' | /var/ossec/bin/manage_agents >/dev/null 2>&1

if [[ $? -ne 0 ]]; then
    echo -e "Error!" 
    echo "Agent not added"
    exit $?
fi


#Set monitored OSSEC process
/usr/share/ossim/scripts/ossec_set_plugin_config.sh

#return Agent ID
echo $AGENT_ID
