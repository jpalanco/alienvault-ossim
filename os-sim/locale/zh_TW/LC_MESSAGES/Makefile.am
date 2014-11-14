lcmessagesdir   = $(datadir)/locale/zh_TW/LC_MESSAGES
lcmessages_DATA =  ossim.mo ossim.po

EXTRA_DIST   = $(lcmessages_DATA)
DISTCLEANFILES = Makefile.in ossim.mo

ossim.mo: ossim.po
	php conv.php ossim.po | msgfmt - -o ossim.mo

