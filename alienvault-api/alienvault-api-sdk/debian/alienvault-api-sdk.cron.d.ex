#
# Regular cron jobs for the alienvault-api-sdk package
#
0 4	* * *	root	[ -x /usr/bin/alienvault-api-sdk_maintenance ] && /usr/bin/alienvault-api-sdk_maintenance
