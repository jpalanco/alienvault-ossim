#!/bin/bash

/var/lib/dpkg/info/alienvault-redis-server-otx.postinst configure &>>/dev/null

sleep 10

/etc/network/if-pre-up.d/iptables &>/dev/null

#EOF
