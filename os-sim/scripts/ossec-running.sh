#!/bin/bash

#Patterns
PATTERN_OS="ossec-analysisd is running|ossec-syscheckd is running|ossec-remoted is running|ossec-monitord is running"
PATTERN_DB="ossec-dbd is running"
PATTERN_CS="ossec-csyslogd is running"
PATTERN_AL="ossec-agentlessd is running"
PATTERN_DBG="ossec-analysisd -d|ossec-syscheckd -d|ossec-monitord -d|ossec-remoted -d"


N_AGENTS=0
if [ -x /var/ossec/bin/manage_agents ]; then
	N_AGENTS=`/var/ossec/bin/manage_agents -l|grep "ID:"| wc -l`
fi


if [ $N_AGENTS -lt 1 ]; then
    PATTERN_OS="ossec-analysisd is running|ossec-syscheckd is running|ossec-logcollector is running|ossec-monitord is running"
    PATTERN_DBG="ossec-analysisd -d|ossec-syscheckd -d|ossec-monitord -d|ossec-logcollector -d"
fi

STATUS=`/var/ossec/bin/ossec-control status` 

S_OS=`echo "$STATUS" | egrep "$PATTERN_OS" | wc -l`
S_DB=`echo "$STATUS" | egrep "$PATTERN_DB" | wc -l`
S_CS=`echo "$STATUS" | egrep "$PATTERN_CS" | wc -l`
S_AL=`echo "$STATUS" | egrep "$PATTERN_AL" | wc -l`
S_DBG=`ps -ef | grep -v "grep" | egrep "$PATTERN_DBG" | wc -l`


#Ossec service status
if [ $S_OS -ge 4 ]; then
    S_OS='up'
else
    S_OS='down'
fi   


#ossec-dbd status
if [ $S_DB -ge 1 ]; then
    S_DB='up'
else
    S_DB='down'
fi

#ossec-csyslogd status
if [ $S_CS -ge 1 ]; then
    S_CS='up'
else
    S_CS='down'
fi

#ossec-agentlessd status
if [ $S_AL -ge 1 ]; then
    S_AL='up'
else
    S_AL='down'
fi

#debug mode status
if [ $S_DBG -ge 1 ]; then
    S_DBG='up'
else
    S_DBG='down'
fi


echo -e "
[GENERAL_STATUS]
status=\"$STATUS\"
[STATUS_BY_MODULE]
database=\"$S_DB\"
syslog=\"$S_CS\"
debug=\"$S_DBG\"
agentless=\"$S_AL\"
service=\"$S_OS\""