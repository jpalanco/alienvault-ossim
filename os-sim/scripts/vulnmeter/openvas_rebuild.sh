#!/bin/bash
/usr/share/ossim/scripts/vulnmeter/openvas_before_start.sh

/etc/init.d/openvas-manager stop
/etc/init.d/openvas-scanner stop
if [ "$1" == "repair" ]
then
   cd /var/lib/openvas/mgr
   mv tasks.db tasks.db.old
fi
openvassd
while [ `netstat -putan | grep -c openvassd` -eq 0 ]
do
  echo "Waiting 30 seconds to openvas-scanner...";
  sleep 30
done
openvasmd --rebuild
/etc/init.d/openvas-manager start

VERSION=`/usr/sbin/openvasmd --version | grep 'OpenVAS Manager' | awk '{print $3}' | awk -F '.' '{print $1}'`

if [ "$1" == "repair" ] && [[ $VERSION -ge 6 ]]; then
  openvasmd --create-user=ossim --role="Admin" && openvasmd --user=ossim --new-password=ossim
  openvasmd --create-user=ovas-super-admin --role="Super Admin" && openvasmd --new-password=ovas-super-admin --user=ovas-super-admin
fi
