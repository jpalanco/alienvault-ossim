#
# Regular cron jobs for the alienvault-framework-documentation package
#
0 4	* * *	root	[ -x /usr/bin/alienvault-framework-documentation_maintenance ] && /usr/bin/alienvault-framework-documentation_maintenance
