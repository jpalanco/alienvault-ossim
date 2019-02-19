#!/bin/sh

AUTORECONF=`which autoreconf`
if test -z $AUTORECONF; then
        echo "*** No autoreconf found, please install it ***"
        exit 1
fi

rm -rf autom4te.cache

autoreconf --force --install --verbose || exit $?
