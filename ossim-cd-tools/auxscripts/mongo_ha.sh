#!/bin/bash

# MongoDB HA script
# Dump mongodb collection in master, copy to slave and restore it
#
HA=`grep ^ha_heartbeat_start= /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`
if [ "$HA" != 'yes' ]; then
    echo "Nothing to do."
    exit
fi

DAY=`date --utc --date='last hour' "+%Y%m%d"`
VIRTUAL_IP=`grep ^ha_virtual_ip= /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`
eval "AM_I_MASTER=\`ip a | grep \"inet $VIRTUAL_IP\" | wc -l\`"

if [ "$AM_I_MASTER" != '1' ]; then
    echo "It's not master HA node."
    exit
fi

OTHER_NODE_IP=`grep ^ha_other_node_ip= /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`

echo "Preparing tmp folder"
DUMPFOLDER="/var/tmp/mongoHAdump"
mkdir -p "$DUMPFOLDER"
rm -rf "$DUMPFOLDER/*"

echo "Dump mongodb historic$DAY"
mongodump --db inventory --collection historic$DAY --out "$DUMPFOLDER"

echo "Syncing historic$DAY"
ssh "root@$OTHER_NODE_IP" "mkdir -p $DUMPFOLDER; rm -rf $DUMPFOLDER/*"
rsync -avzP "$DUMPFOLDER/" "root@$OTHER_NODE_IP:/$DUMPFOLDER/"

echo "Restoring historic$DAY"
ssh "root@$OTHER_NODE_IP" "mongorestore --drop --db inventory --collection historic$DAY $DUMPFOLDER/inventory/historic$DAY.bson"

echo "Cleanning tmp files"
rm -rf "$DUMPFOLDER/*"
ssh "root@$OTHER_NODE_IP" "rm -rf $DUMPFOLDER/*"

echo "Finish!"
