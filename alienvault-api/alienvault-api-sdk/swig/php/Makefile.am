BUILT_SOURCES = $(srcdir)/alienvault_api_sdk_wrap.c
SWIG_SOURCES = ../alienvault_api_sdk.i

lib_LTLIBRARIES = libalienvault_api_php.la
libalienvault_api_php_la_SOURCES = $(srcdir)/alienvault_api_sdk_wrap.c $(SWIG_SOURCES)
libalienvault_api_php_la_CFLAGS = -I/usr/include/php5/Zend -I/usr/include/php5/TSRM -I/usr/include/php5/main -I/usr/include/php5 -I$(top_srcdir)/src
libalienvault_api_php_la_LDFLAGS = -shared -shrext .so
libalienvault_api_php_la_LIBADD = $(top_srcdir)/src/libalienvault_api_sdk.la

$(srcdir)/alienvault_api_sdk_wrap.c : $(SWIG_SOURCES)
	$(SWIG) -php -I$(top_srcdir) -o $@ $<

install: all
	sed -i "s/alienvault_api_sdk\./libalienvault_api_php./" alienvault_api_sdk.php
	$(INSTALL) -d "$(DESTDIR)"/`php-config --extension-dir`
	$(INSTALL) -m 644 alienvault_api_sdk.php "$(DESTDIR)"/`php-config --extension-dir`
	$(INSTALL) -m 755 .libs/libalienvault_api_php.so "$(DESTDIR)"/`php-config --extension-dir`
	$(INSTALL) -m 755 ../../src/.libs/libalienvault_api_sdk.so "$(DESTDIR)"/`php-config --extension-dir`

clean-local:
	-rm -f alienvault_api_sdk.php alienvault_api_sdk_wrap.c
