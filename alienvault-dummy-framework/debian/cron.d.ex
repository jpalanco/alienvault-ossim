#
# Regular cron jobs for the alienvault-dummy-framework package
#
0 4	* * *	root	[ -x /usr/bin/alienvault-dummy-framework_maintenance ] && /usr/bin/alienvault-dummy-framework_maintenance
