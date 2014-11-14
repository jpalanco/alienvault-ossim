SUBDIRS = src \
	etc \
	db \
	pixmaps \
	include \
	www \
	scripts \
	contrib \
	locale

update-po:
	rm -f .filenames
	find www include -name "*.php" >> .filenames
	find www include -name "*.inc" >> .filenames
	xgettext -d ossim -s -o .ossim.po -f .filenames
	for lang in `ls locale/ | grep -v CVS | grep -v Makefile`; do \
		msgmerge -N -s -U locale/$$lang/LC_MESSAGES/ossim.po .ossim.po; \
	done
	rm -f .filenames .ossim.po

DISTCLEANFILES = install-sh mkinstalldirs missing config.guess config.sub aclocal.* config.h* configure Makefile \
	Makefile.in \
	ltmain.sh libtool
distclean-local:
	test -z autom4te.cache || rm -rf autom4te.cache
install-data-hook:
	mkdir -p $(localstatedir)/lib/ossim/backup
	mkdir -p $(localstatedir)/log/ossim
