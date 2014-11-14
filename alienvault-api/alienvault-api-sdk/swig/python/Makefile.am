MAINTAINERCLEANFILES = alienvault_api_sdk.py alienvault_api_sdk_wrap.c Makefile.in _alienvault_api_sdk.so

BUILT_SOURCES = $(srcdir)/alienvault_api_sdk_wrap.c
SWIG_SOURCES = ../alienvault_api_sdk.i

pkgpython_PYTHON = alienvault_api_sdk.py __init__.py
pkgpyexec_LTLIBRARIES = _alienvault_api_sdk.la
_alienvault_api_sdk_la_SOURCES = $(srcdir)/alienvault_api_sdk_wrap.c $(SWIG_SOURCES)
_alienvault_api_sdk_la_CFLAGS = $(AX_SWIG_PYTHON_CPPFLAGS) -I$(top_srcdir)/src
_alienvault_api_sdk_la_LDFLAGS = -module
_alienvault_api_sdk_la_LIBADD = $(CURDIR)/../../src/libalienvault_api_sdk.la

$(srcdir)/alienvault_api_sdk_wrap.c : $(SWIG_SOURCES)
	$(SWIG) $(AX_SWIG_PYTHON_OPT) -I$(top_srcdir) -o $@ $<

clean-local:
	-rm -f _alienvault_api_sdk.so alienvault_api_sdk.py alienvault_api_sdk_wrap.c alienvault_api_sdk.pyc
