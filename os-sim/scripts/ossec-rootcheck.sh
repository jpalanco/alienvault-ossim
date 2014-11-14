#!/bin/bash -e

#Agent ID
AGENT_ID=`echo "$1" | /bin/egrep "^[0-9]{1,4}$"`

if [ -z "$AGENT_ID" ]; then
	echo -e "Error!"
	echo "Invalid agent ID. Entered agent ID: $1"
	exit 1
fi


TMP_CONTROL_FILE=`tempfile`

/var/ossec/bin/rootcheck_control -s -i ${AGENT_ID} &> $TMP_CONTROL_FILE

if [[ $? -ne 0 ]]; then
    echo -e "Error!"
	rm -f $TMP_CONTROL_FILE
	exit 1
else
	cat $TMP_CONTROL_FILE
	rm -f $TMP_CONTROL_FILE 
	exit 0
fi

