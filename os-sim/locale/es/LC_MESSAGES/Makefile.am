lcmessagesdir   = $(datadir)/locale/es/LC_MESSAGES
lcmessages_DATA =  ossim.mo ossim.po

EXTRA_DIST   = $(lcmessages_DATA)
DISTCLEANFILES = Makefile.in ossim.mo

ossim.mo: ossim.po
	msgfmt -c -v -o ossim.mo ossim.po

