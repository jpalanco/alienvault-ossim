#
# Regular cron jobs for the alienvault-dummy-database package
#
0 4	* * *	root	[ -x /usr/bin/alienvault-dummy-database_maintenance ] && /usr/bin/alienvault-dummy-database_maintenance
