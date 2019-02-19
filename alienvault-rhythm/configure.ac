#
# License:
#
# Copyright (c) 2015 AlienVault
# All rights reserved.
#
# This package is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; version 2 dated June, 1991.
# You may not use, modify or distribute this program under any other version
# of the GNU General Public License.
#
# This package is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this package; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
# MA  02110-1301  USA
#
#
# On Debian GNU/Linux systems, the complete text of the GNU General
# Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
# Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
AC_INIT(src/main.c)

MAJOR_VERSION=5
MINOR_VERSION=1
MICRO_VERSION=0

PACKAGE=alienvault-rhythm
VERSION=${MAJOR_VERSION}.${MINOR_VERSION}.${MICRO_VERSION}
PACKAGE_VERSION="$PACKAGE-$VERSION"
AC_SUBST(PACKAGE)
AC_SUBST(VERSION)
AC_SUBST(PACKAGE_VERSION)

CFLAGS="$CFLAGS -march=core2 -O2 -Wmissing-prototypes -Wall -Wextra -Werror"
LDFLAGS="$LDFLAGS -Wl,-z,relro,-z,now -Wl,--hash-style=gnu"

AM_INIT_AUTOMAKE($PACKAGE, $VERSION)
AM_MAINTAINER_MODE

AC_ISC_POSIX
AC_PROG_CC
AC_PROG_CXX
AC_STDC_HEADERS
AC_PROG_RANLIB
AC_PROG_INSTALL
AM_PROG_CC_C_O
AC_CANONICAL_HOST
AC_PROG_AWK
AC_CONFIG_HEADERS([config.h])

# Debug mode
AC_MSG_CHECKING([for --enable-debug])
AC_ARG_ENABLE(debug,
              AC_HELP_STRING([--enable-debug],
                             [turn on debugging [default=no]]),
              [],[enable_debug="no"])
AC_MSG_RESULT([$enable_debug])

if test "x$enable_debug" = "xyes"; then
   AC_DEFINE([DEBUG_ENABLED], [1], [Debug enabled])
   CFLAGS="$CFLAGS -ggdb3 -O0"
fi

# Just some constants we may need.
AC_DEFINE([DEFAULT_OUTPUT_FILE], ["/var/log/alienvault/rhythm/matches.log"], [Default output log file])
AC_DEFINE([DEFAULT_LOG_FILE], ["/var/log/alienvault/rhythm/rhythm.log"], [Default message log file])
AC_DEFINE([DEFAULT_LOG_FILE_SIZE], [1024], [Default message log file size])
AC_DEFINE([DEFAULT_LOG_DOMAIN], ["AVR"], [Default log domain])
AC_DEFINE([DEFAULT_DB_SOCKET], ["/var/run/redis/redis-server.sock"], [Default DB socket])
AC_DEFINE([DEFAULT_DB_PORT], [6380], [Default DB port])

# Check for dependencies
PKG_CHECK_MODULES([GLIB], [glib-2.0 >= 2.24])
PKG_CHECK_MODULES([GOBJECT], [gobject-2.0 >= 2.24])
PKG_CHECK_MODULES([GIO], [gio-2.0 >= 2.24])
PKG_CHECK_MODULES([JSON_GLIB], [json-glib-1.0 >= 0.10.2])
PKG_CHECK_MODULES([HIREDIS], [hiredis >= 0.10])

# Old, probably not needed anymore.
AC_SUBST(GLIB_CFLAGS)
AC_SUBST(GLIB_LIBS)
AC_SUBST(GOBJECT_CFLAGS)
AC_SUBST(GOBJECT_LIBS)
AC_SUBST(GIO_CFLAGS)
AC_SUBST(GIO_LIBS)
AC_SUBST(JSON_GLIB_CFLAGS)
AC_SUBST(JSON_GLIB_LIBS)
AC_SUBST(HIREDIS_CFLAGS)
AC_SUBST(HIREDIS_LIBS)
AC_SUBST(CFLAGS)
AC_SUBST(LDFLAGS)

# Status summary
echo ""
echo "*************************************************"
echo "*** AlienVault Rhythm is ready to be compiled ***"
echo "*************************************************"
echo ""
echo ""
echo "     Options:"
echo "      * Enable debug       : $enable_debug"
echo ""
echo "Configure successful. Type 'make' to compile"

AC_OUTPUT([
 Makefile
 src/Makefile
])

