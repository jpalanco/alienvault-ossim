#!/bin/sh

cat /var/tmp/$1 | /usr/bin/ossim-db alienvault_siem;echo 'flush_all' | /bin/nc -q 2 127.0.0.1 11211;
