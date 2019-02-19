#!/bin/bash -e
if [ "$1" == "stop" ]; then
	/etc/init.d/monit stop
	/etc/init.d/ossim-agent stop
	/etc/init.d/ossim-server stop
	/etc/init.d/ossim-framework stop
fi
if [ "$1" == "start" ]; then
	/etc/init.d/ossim-server start
	sleep 15
	/etc/init.d/ossim-agent start	
	/etc/init.d/ossim-framework start
	/etc/init.d/monit start
fi
