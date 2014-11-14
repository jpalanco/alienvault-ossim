#!/bin/bash

set -x
BUILD_DIR=/tmp

if [ "`id -u`" != "0" ]; then
    echo "You need to be root.."
    exit
fi

if [ "$1" != "" ]; then
    CHECKOUT="cvs -d:ext:$1@os-sim.cvs.sourceforge.net:/cvsroot/os-sim co"
else
    CHECKOUT="cvs -d:pserver:anonymous@os-sim.cvs.sourceforge.net:/cvsroot/os-sim co"
fi

# ensure that /etc/apt/sources.list includes ossim sources
if ! grep -q "www.ossim.net" /etc/apt/sources.list; then
	cat >> /etc/apt/sources.list <<EOF 

## OSSIM repository ##
deb http://www.ossim.net/download/ debian/
deb-src http://www.ossim.net/download/ debian/
# deb http://www.ossim.net/download/ lenny/
# deb-src http://www.ossim.net/download/ lenny/

EOF
fi

apt-get update -q
apt-get install build-essential -yq
apt-get build-dep ossim-server ossim-agent -y

cd $BUILD_DIR
rm -rf os-sim
eval "$CHECKOUT os-sim"
cd os-sim/
find . | grep CVS$ | xargs rm -rf
dpkg-buildpackage



cd $BUILD_DIR
rm -f ossim-agent* # remove old ossim-agent package

rm -rf agent
eval "$CHECKOUT agent"
cd agent/
find . | grep CVS$ | xargs rm -rf
dpkg-buildpackage
cd -

