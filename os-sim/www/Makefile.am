SUBDIRS= action \
	backup \
	conf \
	control_panel \
	directives \
	incidents \
	java \
	js \
	nagios \
	netscan \
	ntop \
	panel \
	policy \
	port \
	report \
	riskmeter \
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
	mkdir -p $(localstatedir)/lib/ossim/rrd/host_qualification
	mkdir -p $(localstatedir)/lib/ossim/rrd/net_qualification
	mkdir -p $(localstatedir)/lib/ossim/rrd/global_qualification
	mkdir -p $(localstatedir)/lib/ossim/rrd/level_qualification
	   
DISTCLEANFILES = Makefile.in
