SUBDIRS= action \
	backup \
	conf \
	control_panel \
	directives \
	incidents \
	java \
	js \
	nagios \
	panel \
	policy \
	port \
	report \
	sensor \
	server \
	session \
	upgrade \
	userlog \
	vulnmeter
	
wwwdir = $(datadir)/ossim/www
www_DATA = index.php
	

EXTRA_DIST   = $(www_DATA)

install-data-hook:
	mkdir -p $(localstatedir)/lib/ossim/rrd/event_stats
	   
DISTCLEANFILES = Makefile.in
