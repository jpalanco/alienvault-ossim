#
# Regular cron jobs for the alienvault-dummy-sensor package
#
0 4	* * *	root	[ -x /usr/bin/alienvault-dummy-sensor_maintenance ] && /usr/bin/alienvault-dummy-sensor_maintenance
