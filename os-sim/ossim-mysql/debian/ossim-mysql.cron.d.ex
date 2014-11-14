#
# Regular cron jobs for the ossim-mysql package
#
0 4	* * *	root	[ -x /usr/bin/ossim-mysql_maintenance ] && /usr/bin/ossim-mysql_maintenance
