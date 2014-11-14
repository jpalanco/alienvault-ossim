#
# Regular cron jobs for the alienvault-center package
#
0 4	* * *	root	[ -x /usr/bin/alienvault-center_maintenance ] && /usr/bin/alienvault-center_maintenance
