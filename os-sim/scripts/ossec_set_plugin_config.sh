#!/bin/bash -e

nagent=0
prcss=unk

if [ -x /var/ossec/bin/manage_agents ]; then
	nagent=`/var/ossec/bin/manage_agents -l|grep "ID:"| wc -l`
#else
#	echo "Command (/var/ossec/bin/manage_agents) not executable or not found"
fi

if [ ! -z $nagent ]; then
	if [ $nagent -gt 0 ]; then
		prcss=ossec-remoted
	else
		prcss=ossec-logcollector
	fi
	# echo "Process: $prcss"
	if [ $prcss = "ossec-remoted" ] || [ $prcss = "ossec-logcollector" ]; then
		find /etc/ossim/agent/plugins/ -maxdepth 1 -type f -name ossec\*.cfg -exec sed -i "s:^process=.*:process=$prcss:" {} \;
		#if [ -x /var/ossec/bin/ossec-control ]; then
		#	/var/ossec/bin/ossec-control restart
		#fi
	fi
#else
#	echo "Couldn't compute how many agents has the server"
fi

