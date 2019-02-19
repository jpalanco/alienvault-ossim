AC_PREREQ(2.52)
AC_INIT(src/main.c)

AM_CONFIG_HEADER(config.h)

OS_SIM_MAJOR_VERSION=4
OS_SIM_MINOR_VERSION=5
OS_SIM_MICRO_VERSION=0
OS_SIM_VERSION=$OS_SIM_MAJOR_VERSION.$OS_SIM_MINOR_VERSION.$OS_SIM_MICRO_VERSION
if test -f debian/changelog ; then
	OS_SIM_VERSION=`dpkg-parsechangelog |grep Version:| cut -d ' ' -f2- |cut -d '-' -f1|cut -d ':'  -f2`
else
	OS_SIM_VERSION=$OS_SIM_MAJOR_VERSION.$OS_SIM_MINOR_VERSION.$OS_SIM_MICRO_VERSION
fi


AC_ARG_WITH([server-api],
	AS_HELP_STRING([--with-server-api=port],[Define the port where the server listen API calls. Default 40009]),
	AC_DEFINE_UNQUOTED([SERVER_API_PORT], $with_server_api,[Port where the servere listen for API calls]),
	AC_DEFINE([SERVER_API_PORT], [40009],[Port where the servere listen for API calls]))


export PKG_CONFIG_PATH="/usr/lib/x86_64-linux-gnu/pkgconfig/:$PKG_CONFIG_PATH"
	

#AC_DEFINE([SERVER_API_PORT], [40009],[Port where the servere listen for API calls])
AC_SUBST(SERVER_API_PORT)

AC_SUBST(OS_SIM_MAJOR_VERSION)
AC_SUBST(OS_SIM_MINOR_VERSION)
AC_SUBST(OS_SIM_MICRO_VERSION)
AC_SUBST(OS_SIM_VERSION)

AM_INIT_AUTOMAKE(os-sim, $OS_SIM_VERSION)

AM_MAINTAINER_MODE

AC_ISC_POSIX
AC_PROG_CC
AC_PROG_CXX
AC_STDC_HEADERS
#AM_PROG_LIBTOOL
AC_PROG_INSTALL
AM_PROG_CC_C_O
AC_CANONICAL_HOST
AC_PROG_RANLIB

# Support silent build rules, requires at least automake-1.11. Disable
# by either passing --disable-silent-rules to configure or passing V=1
# to make
m4_ifdef([AM_SILENT_RULES], [AM_SILENT_RULES([yes])])

case "$host" in
    *darwin*)
    LDFLAGS="${LDFLAGS} -bind_at_load"
    ;;
esac
# CFlags
CFLAGS="$CFLAGS -Wmissing-prototypes -Wall -Wextra -Werror -fstack-protector --param=ssp-buffer-size=4 -D_FORTIFY_SOURCE=2 -Wformat -Wformat-security"
LDFLAGS="$LDFLAGS -Wl,-z,relro,-z,now -Wl,--hash-style=gnu"

# Set PKG_CONFIG_PATH so it can detect wheezy libraries (like gnutls),
# currently we mix wheezy with squeeze because of openvas dependencies
export PKG_CONFIG_PATH="/usr/lib/x86_64-linux-gnu/pkgconfig/:$PKG_CONFIG_PATH"

#
# Check for structures
AC_CHECK_MEMBER([struct sockaddr.sa_len],
                AC_DEFINE(HAVE_SOCKADDR_SA_LEN, 1,
                  [Define if struct sockaddr has sa_len field]),,
                [#include <sys/types.h>
                 #include <sys/socket.h>])

AC_MSG_CHECKING([for struct sockaddr_storage])
AC_TRY_COMPILE([ #include <sys/types.h>
#include <sys/socket.h>], [struct sockaddr_storage s; s],
  have_sockaddr_storage=yes, have_sockaddr_storage=no)
if test "$have_sockaddr_storage" = "yes" ; then
  AC_DEFINE(HAVE_SOCKADDR_STORAGE,1,[Whether you have struct sockaddr_storage])
  AC_MSG_RESULT(yes)
else
  AC_MSG_RESULT(no)
fi

# Check for IPv6 support
AC_MSG_CHECKING([for IPv6 availability])
AC_TRY_LINK([ #include <sys/types.h>
#include <sys/socket.h>
#include <netinet/in.h>], [struct sockaddr_in6 s; struct in6_addr t=in6addr_any; int i=AF_INET6; s; t.s6_addr[0] = 0;],
  [
    AC_DEFINE(HAVE_IPV6, 1, [Define if IPv6 is potentially available])
    AC_MSG_RESULT(yes)
  ],
  [
    AC_MSG_RESULT(no)
  ]
)

PKG_CHECK_MODULES(GLIB, glib-2.0 >= 2.2 gobject-2.0 >= 2.2 gthread-2.0 >= 2.2 gio-2.0)
PKG_CHECK_MODULES(GNET, gnet-2.0 > 2.0)
PKG_CHECK_MODULES(LIBXML, libxml-2.0)
PKG_CHECK_MODULES(OPENSSL, openssl >= 0.9.7)
PKG_CHECK_MODULES([LIBGDA], [libgda-4.0 >= 4.2.11],, [
                            PKG_CHECK_MODULES([LIBGDA], [libgda-5.0 >= 5.2.2])])
PKG_CHECK_MODULES(LIBUUID, uuid)
PKG_CHECK_MODULES(LIBPCRE, [libpcre >= 3.0])
PKG_CHECK_MODULES(JSON_GLIB, [json-glib-1.0 >= 0.10.2])
PKG_CHECK_MODULES(LIBGEOIP, [geoip >= 1.4.7])
PKG_CHECK_MODULES(LIBSOUP, [libsoup-2.4])
PKG_CHECK_MODULES(LIBBSON, [libbson-1.0])
PKG_CHECK_MODULES(LIBZLIB, zlib)
AC_CHECK_HEADER([uuid/uuid.h],
		AC_CHECK_LIB([uuid], [uuid_generate],
			AC_DEFINE([ENABLE_UUID], 1,
			[Define this to 1 if you want to enable generation of
			DCE compliant UUIDs.])
			LIBUUID_LIBS="-luuid",
			AC_MSG_WARN([Linux-NTFS DCE compliant UUID generation code requires the uuid library.]),
		),
		AC_MSG_WARN([Linux-NTFS DCE compliant UUID generation code requires the uuid library.]),
	)

AC_CHECK_HEADER([tidy/tidy.h],
	AC_CHECK_LIB([tidy], [tidyCreate],LIBTIDY_LIBS="-ltidy",
		AC_MSG_WARN([Tidy library not found.]),
	),
	AC_MSG_WARN([Tidy library not found.]),
)

# Debug mode
AC_MSG_CHECKING([Checking for --enable-debug])
AC_ARG_ENABLE(debug,
              AC_HELP_STRING([--enable-debug],
                             [turn on debugging [default=no]]),
              [],[enable_debug="no"])
AC_MSG_RESULT([$enable_debug])

if test "x$enable_debug" = "xyes"; then
   AC_DEFINE([DEBUG_ENABLED], [1], [Debug enabled])
   CFLAGS="$CFLAGS -ggdb3 -O0"
fi

# Unit test mode
AC_MSG_CHECKING([Checking for --enable-unittest])
AC_ARG_ENABLE(unittest,
              AC_HELP_STRING([--enable-unittest],
                             [turn on unit tests [default=no]]),
              [],[enable_unittest="no"])
AC_MSG_RESULT([$enable_unittest])

if test "x$enable_unittest" = "xyes"; then
   AC_DEFINE([USE_UNITTESTS], [1], [Unit tests enabled])
fi

case $(pwd) in
	/tmp/tmp*)
		version=$(head -1 debian/changelog | cut -f2 -d' ')
		;;
	*)
		version=$(git log --pretty=oneline -n1 | cut -f1 -d' ')
		if test "x$version" = "x"
		then
			version="no-git-commit"
		fi
		version="custom_build-"$version
		;;

esac
AC_DEFINE_UNQUOTED([SERVER_VERSION], ["$version"], [Version that identifies the server])




AC_SUBST(GLIB_CFLAGS)
AC_SUBST(GLIB_LIBS)
AC_SUBST(LIBGDA_CFLAGS)
AC_SUBST(LIBGDA_LIBS)
AC_SUBST(LIBXML_CFLAGS)
AC_SUBST(LIBXML_LIBS)
AC_SUBST(GNET_CFLAGS)
AC_SUBST(GNET_LIBS)
AC_SUBST(OPENSSL_CFLAGS)
AC_SUBST(OPENSSL_LIBS)
AC_SUBST(LIBUUID_LIBS)
AC_SUBST(LIBPCRE_LIBS)
AC_SUBST(LIBTIDY_LIBS)
AC_SUBST(LIBSOUP_CFLAGS)
AC_SUBST(LIBSOUP_LIBS)
AC_SUBST(LIBBSON_CFLAGS)
AC_SUBST(LIBBSON_LIBS)
AC_SUBST(LIBZLIB_CFLAGS)
AC_SUBST(LIBZLIB_LIBS)
AC_SUBST(CFLAGS)

AC_OUTPUT([
Makefile
src/Makefile
src/tests/Makefile
etc/Makefile
etc/framework/Makefile
etc/cron.d/Makefile
etc/cron.daily/Makefile
etc/init.d/Makefile
etc/logrotate.d/Makefile
db/Makefile
pixmaps/Makefile
include/Makefile
www/Makefile
www/action/Makefile
www/conf/Makefile
www/control_panel/Makefile
www/directives/Makefile
www/incidents/Makefile
www/nagios/Makefile
www/panel/Makefile
www/policy/Makefile
www/port/Makefile
www/report/Makefile
www/report/graphs/Makefile
www/report/jgraphs/Makefile
www/sensor/Makefile
www/server/Makefile
www/session/Makefile
www/vulnmeter/Makefile
www/backup/Makefile
www/java/Makefile
www/java/images/Makefile
www/js/Makefile
www/js/rico/Makefile
www/js/scriptaculous/Makefile
www/upgrade/Makefile
www/userlog/Makefile
scripts/Makefile
scripts/correlation/Makefile
scripts/extract_nessus/Makefile
scripts/extract_osvdb/Makefile
scripts/extract_osvdb/OS/Makefile
locale/zh_TW/LC_MESSAGES/Makefile
locale/zh_TW/Makefile
locale/es/LC_MESSAGES/Makefile
locale/es/Makefile
locale/fr/LC_MESSAGES/Makefile
locale/fr/Makefile
locale/zh_CN/LC_MESSAGES/Makefile
locale/zh_CN/Makefile
locale/Makefile
locale/ru/LC_MESSAGES/Makefile
locale/ru/Makefile
locale/en/LC_MESSAGES/Makefile
locale/en/Makefile
locale/de/LC_MESSAGES/Makefile
locale/de/Makefile
locale/ja/LC_MESSAGES/Makefile
locale/ja/Makefile
locale/pt_BR/LC_MESSAGES/Makefile
locale/pt_BR/Makefile
])
