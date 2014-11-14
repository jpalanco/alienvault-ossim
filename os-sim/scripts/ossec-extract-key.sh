#!/bin/bash

#Check Agent ID
AGENT_ID=`echo "$1" | /bin/egrep "^[0-9]{1,4}$"`

if [ -z "$AGENT_ID" ]; then
	echo -e "Error!"
	echo "Invalid agent ID. Entered agent ID: $1"
	exit 1
fi
/var/ossec/bin/manage_agents -e ${AGENT_ID}
