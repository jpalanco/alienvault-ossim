#!/bin/bash -e
/usr/bin/nfsen reconfig
dpkg-trigger --no-await alienvault-nfsen-update-firewall
dpkg --pending --configure
