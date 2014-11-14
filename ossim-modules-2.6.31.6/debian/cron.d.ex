#
# Regular cron jobs for the ossim-modules-2.6.31.6 package
#
0 4	* * *	root	[ -x /usr/bin/ossim-modules-2.6.31.6_maintenance ] && /usr/bin/ossim-modules-2.6.31.6_maintenance
