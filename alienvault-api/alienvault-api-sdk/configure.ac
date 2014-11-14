AC_PREREQ(2.13)

AC_INIT([alienvault-api-sdk], [0.0.1])

AC_CONFIG_HEADER([config.h])
AC_CONFIG_SRCDIR([src/sim-api.h])
AC_CONFIG_MACRO_DIR([m4])

AC_PROG_CC

LT_INIT

AM_INIT_AUTOMAKE([1.11 foreign])
dnl AM_SILENT_RULES([yes])
AM_PROG_CC_C_O

export PKG_CONFIG_PATH="/usr/lib/x86_64-linux-gnu/pkgconfig/:$PKG_CONFIG_PATH"

PKG_CHECK_MODULES(GLIB, glib-2.0 >= 2.24.2)
PKG_CHECK_MODULES(LIBSOUP, libsoup-2.4 >= 2.30.2)

AM_PATH_PYTHON
AX_SWIG_PYTHON
AX_PKG_SWIG(1.3.21, [], [ AC_MSG_ERROR([SWIG is not installed]) ])

AC_CONFIG_FILES([
	alienvault-api-sdk.pc
	Makefile
	src/Makefile
	swig/Makefile
	swig/php/Makefile
	swig/python/Makefile
	tests/Makefile
])

AC_OUTPUT
