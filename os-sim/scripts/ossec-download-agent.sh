#!/bin/bash 

#Agent ID
AGENT_ID=`echo "$1" | /bin/egrep "^[0-9]{1,4}$"`

if [ -z "$AGENT_ID" ]; then
	echo -e "Error!"
	echo "Invalid agent ID. Entered agent ID: $1"
	exit 1
fi

#OSSEC agent for Windows
if [ "$2" == "windows" ]; then

    OSSEC_CLIENT="/usr/share/ossec-generator/agents/ossec_installer_$AGENT_ID.exe"
    
    if [ ! -a $OSSEC_CLIENT ]; then                    
        if [ -x /usr/share/ossec-generator/gen_install_exe.py ]; then
            cd /usr/share/ossec-generator/;
            /usr/share/ossec-generator/gen_install_exe.py --agent_id $AGENT_ID > /dev/null 2>&1
        else
            echo -e "Error!"
            echo "OSSEC agent generator not found"
            exit 1
        fi                
    fi
    
    
    if [ -a $OSSEC_CLIENT ]; then
        echo -e "OK!"
        echo "Preconfigured agent for Windows generated successfully"
        exit 0
    else
        echo -e "Error!"
        echo "Unable to generate preconfigured agent for Windows"
        exit 1
    fi
    
elif [ "$2" == "unix" ]; then #OSSEC client for UNIX
   
   echo -e "Error!"
   echo "Preconfigured agent for UNIX not available"
   exit 1
    
else
   
   echo -e "Error!"
   echo "Operating System not allowed"
   exit 1
    
fi
