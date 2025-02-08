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

#Check if it is an IP Address or a CIDR
function is_ip_address()
{
    local ip=`echo $1 | tr a-z A-Z`
    local stat=1

    if [ "$ip" == "ANY" ] || [ "$ip" == "0.0.0.0/0" ]; then
        return $stat
    fi

    #It's a CIDR
    if [[ $ip =~ '/' ]]; then
        cidr=`echo ${ip} | cut -d'/' -f 2`
        [[ ${cidr} -eq 32 ]]
        stat=$?
    else
        #It's an IP address
        stat=0
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

#Agent name
AGENT_NAME=$1

#Check if agent name already exists
N_AGENTS=`/var/ossec/bin/agent_control -l | egrep "Name: ${AGENT_NAME}," | wc -l`

if [[ $N_AGENTS -gt 0 ]]; then
    echo -e "Error!"
    echo "Agent name already exists.  Check your asset inventory"
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


#Check if agent IP already exists
if is_ip_address $2; then
  #Remove /32 to check if the IP address exists
  _AGENT_IP=`echo ${2} | sed -E 's/\/32+$//'`
  N_AGENTS=`/var/ossec/bin/agent_control -l | egrep "IP: ${_AGENT_IP}," | wc -l`

  if [[ $N_AGENTS -gt 0 ]]; then
      echo -e "Error!"
      echo "Agent IP already exists."
      exit 1
  fi
fi

#Create agent
/var/ossec/bin/manage_agents -a ${AGENT_IP} -n ${AGENT_NAME} >/dev/null 2>&1

if [[ $? -ne 0 ]]; then
    echo -e "Error!"
    echo "Agent not added"
    exit $?
fi


#Set monitored OSSEC process
/usr/share/ossim/scripts/ossec_set_plugin_config.sh

#return Agent ID
AGENT_ID=`cat /var/ossec/etc/client.keys | cut -d' ' -f1 | sort -n | tail -1`
echo $AGENT_ID
