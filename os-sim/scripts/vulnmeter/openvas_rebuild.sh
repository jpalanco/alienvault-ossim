#!/bin/bash
/etc/init.d/openvas-manager stop
/etc/init.d/openvas-scanner stop
/etc/init.d/openvas-scanner start
while [ `netstat -putan | grep -c openvassd` -eq 0 ]
do
  echo "Waiting 30 seconds to openvas-scanner...";
  sleep 30
done
/etc/init.d/openvas-manager rebuild
/etc/init.d/openvas-manager start
