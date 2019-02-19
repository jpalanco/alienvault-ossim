#!/bin/bash

CLIENT_KEYS_PATH='/var/ossec/etc/client.keys'
QUEUE_PATH='/var/ossec/queue/'

#Agent ID
AGENT_ID=`echo "$1" | /bin/egrep "^[0-9]{1,4}$"`

if [ -z "$AGENT_ID" ]; then
	echo -e "Error!"
	echo "Invalid agent ID. Entered agent ID: $1"
	exit 1
fi

#Agent name
AGENT_NAME=`grep "$AGENT_ID" $CLIENT_KEYS_PATH | cut -d' ' -f2`

#Agent IP
AGENT_IP=`grep "$AGENT_ID" $CLIENT_KEYS_PATH | cut -d' ' -f3`
        
#Delete agent information from var/ossec/queue/agent-info
rm -f $QUEUE_PATH"agent-info/$AGENT_NAME-$AGENT_IP"*

#Delete agent information from var/ossec/queue/rids
rm -f $QUEUE_PATH"rids/"$AGENT_ID

#Delete agent information from var/ossec/queue/syscheck
rm -f $QUEUE_PATH"syscheck/($AGENT_NAME) $AGENT_IP"* 

#Delete agent information from var/ossec/queue/rootcheck
rm -f $QUEUE_PATH"rootcheck/($AGENT_NAME) $AGENT_IP"*

#Delete agent information from var/ossec/queue/diff
rm -rf $QUEUE_PATH"diff/$AGENT_NAME"    

echo 'R'$'\n'${AGENT_ID}$'\n''y'$'\n''Q'$'\n' | /var/ossec/bin/manage_agents >/dev/null 2>&1

if [[ $? -ne 0 ]]; then
    echo -e "Error!"
    echo "Agent not deleted"
    exit $?
fi

          
#Delete agent from var/ossec/etc/client.key 
sed -i "/^$AGENT_ID\s/d" $CLIENT_KEYS_PATH


#Set monitored OSSEC process
/usr/share/ossim/scripts/ossec_set_plugin_config.sh
