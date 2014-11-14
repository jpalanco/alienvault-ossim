#!/bin/sh -e
# /usr/lib/emacsen-common/packages/remove/ossim-modules-2.6.31.6

FLAVOR=$1
PACKAGE=ossim-modules-2.6.31.6

if [ ${FLAVOR} != emacs ]; then
    if test -x /usr/sbin/install-info-altdir; then
        echo remove/${PACKAGE}: removing Info links for ${FLAVOR}
        install-info-altdir --quiet --remove --dirname=${FLAVOR} /usr/share/info/ossim-modules-2.6.31.6.info.gz
    fi

    echo remove/${PACKAGE}: purging byte-compiled files for ${FLAVOR}
    rm -rf /usr/share/${FLAVOR}/site-lisp/${PACKAGE}
fi
