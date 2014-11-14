AUTOMAKE_OPTIONS = subdir-objects

libapi_includedir = $(includedir)/api/
libapi_include_HEADERS = \
	sim-api.h

lib_LTLIBRARIES = libalienvault_api_sdk.la
libalienvault_api_sdk_la_SOURCES = sim-api.c
libalienvault_api_sdk_la_CFLAGS = $(GLIB_CFLAGS) $(LIBSOUP_CFLAGS)
libalienvault_api_sdk_la_LDFLAGS = $(GLIB_LIBS) $(LIBSOUP_LIBS)
