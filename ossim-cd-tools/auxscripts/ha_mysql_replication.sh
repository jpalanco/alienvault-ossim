#!/bin/bash

# L (local) base config
admin_ip=`grep ^admin_ip= /etc/ossim/ossim_setup.conf| awk -F'=' '{print$2}'| xargs`
ha_local_node_ip=`grep ^ha_local_node_ip= /etc/ossim/ossim_setup.conf| awk -F'=' '{print$2}'| xargs`
ha_other_node_ip=`grep ^ha_other_node_ip= /etc/ossim/ossim_setup.conf| awk -F'=' '{print$2}'| xargs`
ha_password=`grep ^ha_password= /etc/ossim/ossim_setup.conf| awk -F'=' '{print$2}'| xargs`
ha_role=`grep ^ha_role= /etc/ossim/ossim_setup.conf| awk -F'=' '{print$2}'`
hostname=`grep ^hostname= /etc/ossim/ossim_setup.conf| awk -F'=' '{print$2}'`

# L and R
dumppath="/var/lib/ossim/backup"
test -d "$dumppath" || mkdir -p "$dumppath"


case $1 in
	sss)
		for S in cron heartbeat ossim-ha; do /etc/init.d/$S stop &>/dev/null; done
	;;

	ssh)
		if [ ! -d /root/.ssh ]; then
			mkdir /root/.ssh
		fi
	;;

	ssh2)
		if [ -f /root/.ssh/authorized_keys ]; then
			cp /root/.ssh/authorized_keys /root/.ssh/authorized_keys-B
		fi
		cat /root/id_rsa.pub >> /root/.ssh/authorized_keys
	;;

	rmy)
		/etc/init.d/mysql restart >/dev/null 2>&1
	;;

	fup)
		if [ -f /root/myrepl_other_node ] ; then
			bash /root/myrepl_other_node 
		else
			echo "Error. Remote command not found"
		fi
	;;

	dsm)
		if [ -f /root/debian.cnf ]; then
			cp -f /root/debian.cnf /etc/mysql/debian.cnf
		else
			echo "Warn. Debian sys maintainer auth data not found"
		fi
	;;

	dbe)
		if [ -f /root/db_encryption_key ]; then
			cp -f /root/db_encryption_key /etc/ossim/framework/
		else
			echo "Warn. db_encryption_key not found"
		fi
	;;

	rps)
		grep ^pass= /etc/ossim/ossim_setup.conf | awk -F'=' '{print$2}'
		;;

	wps)
		if [ ! -z $2 ]; then
			lpw=`grep ^pass= /etc/ossim/ossim_setup.conf | awk -F'=' '{print$2}'`
			sed -i "s/^pass=$lpw/pass=$2/" /etc/ossim/ossim_setup.conf
		else
			echo "Error. missig field for update op."
		fi
	;;

	re1)
		echo "SHOW MASTER STATUS;"|ossim-db mysql |grep "mysql-bin"
	;;

	hbs)
		/etc/init.d/heartbeat stop
	;;

	hbr)
		/etc/init.d/heartbeat restart
	;;

	crr)
		/etc/init.d/cron restart
	;;

	rtf)
		for f in /root/rsconf-temp /root/myrepl_other_node /root/db_encryption_key /root/debian.cnf $2; do
			if [ -f $f ]; then
				rm -f $f
			fi
		done
		if [ -f /root/.ssh/authorized_keys-B ]; then
			mv -f /root/.ssh/authorized_keys-B /root/.ssh/authorized_keys
		fi
	;;

	*)


helpf(){
	echo -e "\n Requires: 2 Database nodes, Configured alienvault4 ha_heartbeat, Unified SIEM"
	echo " Config sample for slave node:"
	echo -e " 
  [ha]
  ha_autofailback=no\t\tdo not touch if you don't know what this means
  ha_deadtime=10
  ha_device=eth0\t\trecommended dedicated and fast device
  ha_heartbeat_comm=bcast
  ha_heartbeat_start=yes
  ha_keepalive=3
  ha_local_node_ip=192.168.62.49
  ha_log=yes
  ha_other_node_ip=192.168.62.47
  ha_other_node_name=aiohamaster
  ha_password=password\t\tfor ha.d authkeys, mysql.user
  ha_ping_node=default
  ha_role=slave
  ha_virtual_ip=192.168.62.48
"
	exit 1
}

OAdb_backup() {
        schemav3r=`echo "select value from alienvault.config where conf='ossim_schema_version';" | ossim-db |grep -v ^value`
        dat3=`date +%F-%H_%M_%S`
        # output dir:
        d1r="/var/lib/ossim/backup/db/$dat3-ossimschema_$schemav3r"
        echo -e "DB BACKUP"
        echo -e "[INFO] Backup will be saved to: $d1r/"
        echo -e "Current devices:"
#        df -h | grep "^/dev/"
#        echo -e "[WARN] Check free disk space" |grep --color "WARN"
#        echo -e "[WARN] ossim-server and ossim-framework will be stopped" |grep --color "WARN"
#        echo -en "(Press Intro to start, Ctrl+c to abort)" && read
#        for S in ossim-server ossim-framework; do invoke-rc.d $S stop; done
        c0mpress=1
        h0st=`grep -i "^db_ip=" /etc/ossim/ossim_setup.conf |awk -F'=' '{print$2}'`
        test -z $h0st && h0st="localhost"
        us3r=`grep "^user=" /etc/ossim/ossim_setup.conf| awk -F= '{print$2}'`
        p4ss=`grep -i "^pass=" /etc/ossim/ossim_setup.conf |awk -F'=' '{print$2}'`

        c0mm="mysqldump --compress --skip-lock-tables -h $h0st -u $us3r -p$p4ss"
        # --insert-ignore sounds good when backups? maybe as optional?
        # db list:
        dbs=`echo "show databases" | ossim-db | grep -v "Database"`
        echo -e "\n-> Wait please."| grep --color "\->"
        for db in $dbs; do
                echo -en "$db "
                test -d $d1r/$db/struct || mkdir -p $d1r/$db/struct
                $c0mm -d $db > $d1r/$db/struct/$db-struct-$dat3.sql
                $c0mm $db > $d1r/$db/$db-$dat3.sql
        done
        $c0mm -d --all-databases  > $d1r/aiof_db-struct-$dat3.sql
        $c0mm --all-databases > $d1r/aiof_db-$dat3.sql
        if [ $c0mpress = 1 ]; then
                echo -e "\ncompressing"
                find $d1r -type f -iname \*.sql -exec gzip {} \;
        fi
        du -sh $d1r
}


if [ "$1" = "--help" ]; then
	helpf
fi

helpor(){
	echo " Found $1 mysqld not properly configured. In $1 node, set ha_ values and run alienvault-reconfig"|grep --color $1
	helpf
}

helpe(){
	echo " Call me ($0) when your heartbeat cluster is configured"
	exit 1
}

testdbconn(){

	if [ "$1" = "R" ] ; then
	# R emote
		rtdbconn=`ssh -o 'StrictHostKeyChecking=no' root@$ha_other_node_ip 'if ! (echo -n "\q" | ossim-db mysql); then echo "db conn. failed"; else echo "Rok"; fi'`
		if [ "$rtdbconn" != "Rok" ]; then
			exit 1
		fi
	else
	# L ocal
		if ! (echo -n "\q" | ossim-db mysql); then echo "db conn. failed"; exit 1; fi
	fi

}

sshcopyid(){
	ID_FILE="${HOME}/.ssh/id_rsa.pub"
#	{ eval "$GET_ID" ; } | ssh ${1%:} "umask 077; test -d .ssh || mkdir .ssh ; cat >> .ssh/authorized_keys" || exit 1
	scp -o 'StrictHostKeyChecking=no' ${ID_FILE} root@$ha_other_node_ip:
	ssh -o 'StrictHostKeyChecking=no' root@$ha_other_node_ip 'av_ha_mysql_replication ssh2'
}


(grep -m1 profile /etc/ossim/ossim_setup.conf| grep -i Database > /dev/null) || helpf
cvalL=`grep ^ha_heartbeat_start= /etc/ossim/ossim_setup.conf| awk -F'=' '{print$2}'`
if [ -z $cvalL ]; then cvalL="no"; fi
if [ $cvalL != "yes" ]; then
	echo " Local not configured or disabled"|grep --color L
	helpf
fi

grep "^ha_" /etc/ossim/ossim_setup.conf|grep "unconfigured" && helpe

test -f "/etc/mysql/conf.d/z99_ha.cnf" || helpor L

echo "INFO: Next process will prompt for passwd (to $ha_other_node_ip) two or three times (first run)"| grep --color "INFO"
echo " (it is not the default keygen/copyid process, save existing data is required, it runs under lshell)"

if [ ! -f /root/.ssh/.ha_001 ]; then
	ssh -o 'StrictHostKeyChecking=no' root@$ha_other_node_ip 'av_ha_mysql_replication ssh'
	> /root/.ssh/.ha_001
fi
if [ ! -f /root/.ssh/id_rsa.pub ]; then
	ssh-keygen -f /root/.ssh/id_rsa
fi
sshcopyid $ha_other_node_ip

# test configured other node ip / get remote config
echo "Getting setup from $ha_other_node_ip"
scp -q root@$ha_other_node_ip:/etc/ossim/ossim_setup.conf /root/rsconf-temp
if [ $? -eq 0 ]; then
	(grep -m1 profile /root/rsconf-temp| grep -i Database > /dev/null) || helpf
	cvalR=`grep ^ha_heartbeat_start= /root/rsconf-temp| awk -F'=' '{print$2}'`
	ha_roleR=`grep ^ha_role= /root/rsconf-temp| awk -F'=' '{print$2}'`
	hostnameR=`grep ^hostname= /root/rsconf-temp| awk -F'=' '{print$2}'`

	if [ $cvalR != "yes" ]; then
		echo " Remote not configured or disabled" |grep --color R
		helpf
	fi

	if [ $ha_roleR = $ha_role ]; then
		echo " ha_role must be different (master/slave)"
		helpe
	fi
	
	if [ "$hostname" = $hostnameR ]; then
		echo -e " ERROR: reconfiguring hostname and HA is not possible in one operation\n hostnames must be set different before use $0"|grep --color "ERROR"
		exit 1
	fi

	grep "^ha_" /root/rsconf-temp|grep "unconfigured" && helpe

else
	echo " couldn't get R config"| grep --color R
	helpf
fi

# Checks end


#echo "WARN: This process rebuilds THIS DB (`hostname` ($admin_ip)) from scratch"| grep --color "WARN"
#echo "WARN: In addition, this process will overwrite 'the other node' DB ($ha_other_node_ip)"| grep --color "WARN"
echo "WARN: This process will overwrite 'the other node' DB ($ha_other_node_ip)"| grep --color "WARN"
#echo "Recommended: use identity_file for ssh keys (ssh-keygen, and write pub key into remote auth file)"
echo "Unattended task. You don't have to run shown commands"
echo "Alienvault4 OSSIM ha_ MySQL replication. Continue? (y/N)"
yn="n"
read yn
if [ "$yn" != y ]; then
        echo " (Aborted by user)"
        exit 127
fi

echo -e "\n This process can take some minutes\n If you want to check remote DB process,\n on the other node, run:\n  watch -n 1 'echo \"show full processlist;\"| ossim-db mysql'"

echo -e "\n-- R (remote) node --"

echo " Stopping cron, ossim-ha and heartbeat (must be correctly stopped, please be patient)"
#ssh root@$ha_other_node_ip 'for S in heartbeat ossim-ha; do /etc/init.d/$S stop &>/dev/null; done'
ssh root@$ha_other_node_ip 'av_ha_mysql_replication sss'

echo " Restarting mysql"
#ssh root@$ha_other_node_ip '/etc/init.d/mysql restart >/dev/null 2>&1'
ssh root@$ha_other_node_ip 'av_ha_mysql_replication rmy'


echo -e "\n-- L (local) node --"

echo " Stopping cron, heartbeat and ossim-ha"
for S in cron heartbeat ossim-ha; do /etc/init.d/$S stop >/dev/null 2>&1; done

echo " Restarting mysql"
/etc/init.d/mysql restart &>/dev/null

#if ! test -e /var/log/mysql/mysql-bin.log; then
#	echo "Binlogging on server not active";
#	exit 255
#fi

#if [ $? -ne 0 ]; then
#	echo " mysql restart couldn't be restarted (exiting)"
#	exit 1
#fi

testdbconn L

## save backup
#echo "Saving DB backup (please wait)... "
#OAdb_backup
#
#if [ $? -eq 0 ]; then
#(rebuild)
#	ossim-reconfig -c --manolo
#else
#	echo "Database backup failed. Aborting"
#	exit 2
#fi

echo " Deleting users ('replication')"
echo "DELETE FROM mysql.user where User='replication';FLUSH PRIVILEGES;"
echo "DELETE FROM mysql.user where User='replication';FLUSH PRIVILEGES;"| ossim-db mysql
echo " Creating users for both nodes and grant replication"
echo "CREATE USER 'replication'@'$ha_local_node_ip' IDENTIFIED BY '$ha_password'; CREATE USER 'replication'@'$ha_other_node_ip' IDENTIFIED BY '$ha_password'; GRANT REPLICATION SLAVE ON *.* TO 'replication'@'$ha_local_node_ip'; GRANT REPLICATION SLAVE ON *.* TO 'replication'@'$ha_other_node_ip'; FLUSH PRIVILEGES;"
echo "CREATE USER 'replication'@'$ha_local_node_ip' IDENTIFIED BY '$ha_password'; CREATE USER 'replication'@'$ha_other_node_ip' IDENTIFIED BY '$ha_password'; GRANT REPLICATION SLAVE ON *.* TO 'replication'@'$ha_local_node_ip'; GRANT REPLICATION SLAVE ON *.* TO 'replication'@'$ha_other_node_ip'; FLUSH PRIVILEGES;"| ossim-db mysql

echo -n "Computing file and log position: "
bulk_status=`echo "FLUSH TABLES WITH READ LOCK; SHOW MASTER STATUS;"| ossim-db mysql`
master_log_file_and_pos=`echo $bulk_status| grep "mysql-bin"| awk -F 'mysql-bin' '{print$2}'`
master_log_filepart=`echo $master_log_file_and_pos |awk -F' ' '{print$1}'`
master_log_file="mysql-bin$master_log_filepart"
master_log_pos=`echo $master_log_file_and_pos |awk -F' ' '{print$2}'`
echo "$master_log_file and $master_log_pos"

echo " Dumping DB"
mysqldump --all-databases --lock-all-tables --master-data -h localhost -u root -p`grep -i ^pass= /etc/ossim/ossim_setup.conf |awk -F'=' '{print$2}'` > "$dumppath/$hostname-dbdump.sql"

echo " Sending DB dump to the other node"
scp $dumppath/$hostname-dbdump.sql root@$ha_other_node_ip:$dumppath/

echo "echo \"STOP SLAVE; SOURCE $dumppath/$hostname-dbdump.sql; CHANGE MASTER TO MASTER_HOST='$ha_local_node_ip', MASTER_USER='replication', MASTER_PASSWORD='$ha_password', MASTER_LOG_FILE='$master_log_file', MASTER_LOG_POS=$master_log_pos;START SLAVE; FLUSH PRIVILEGES\"| ossim-db mysql" > /root/myrepl_other_node
echo " Commands for DB in the other node:"
cat /root/myrepl_other_node
scp /root/myrepl_other_node root@$ha_other_node_ip:


echo -e "\n-- R (remote) node --"

#testdbconn R

#rdbs=`ssh root@$ha_other_node_ip 'echo "show databases" | ossim-db mysql'  | grep -v "Database" | grep -v "information_schema" | grep -v "mysql"| xargs`
#echo -n "echo '" > /root/myrepl_other_node_dropdbs
#for db in $rdbs; do echo -n "DROP DATABASE $db; " >> /root/myrepl_other_node_dropdbs; done
#echo "'| ossim-db mysql" >> /root/myrepl_other_node_dropdbs
#echo " Commands for DB in the other node:"
#cat /root/myrepl_other_node_dropdbs
#scp /root/myrepl_other_node_dropdbs root@$ha_other_node_ip:/tmp/
#
#echo "Drop remote databases"
#ssh root@$ha_other_node_ip 'sh /root/myrepl_other_node_dropdbs'

	echo "Recovering DB dump, stopping slave, changing master (host, user, pass, log file and log position), starting slave; flushing privileges, updating ^pass= in setup if is not already updated"
	#ssh root@$ha_other_node_ip 'sh /root/myrepl_other_node && sh /tmp/myrepl_other_node-update_setup'
	ssh root@$ha_other_node_ip 'av_ha_mysql_replication fup'

	# copy /etc/mysql/debian.cnf

	echo " Checking/Updating ^pass= in setup"
	# commands for remote update setup value for ^pass=
	#other_node_setup_pass=`ssh root@$ha_other_node_ip 'grep ^pass= /etc/ossim/ossim_setup.conf' | awk -F'=' '{print$2}'`
	other_node_setup_pass=`ssh root@$ha_other_node_ip 'av_ha_mysql_replication rps'`
	this_node_setup_pass=`grep ^pass= /etc/ossim/ossim_setup.conf| awk -F'=' '{print$2}'`
	echo "(this node: $this_node_setup_pass, the other node: $other_node_setup_pass)"
	if [ z"$other_node_setup_pass" = z"$this_node_setup_pass" ]; then
		echo "echo 'passwords are equal'" > /root/myrepl_other_node-update_setup
	else
#		echo "sed -i 's/^pass=$other_node_setup_pass/pass=$this_node_setup_pass/' /etc/ossim/ossim_setup.conf" > /root/myrepl_other_node-update_setup
		ssh root@$ha_other_node_ip "av_ha_mysql_replication wps $this_node_setup_pass"
	fi
	#echo " Commands for the other node:"
	#cat /root/myrepl_other_node-update_setup
	#scp /root/myrepl_other_node-update_setup root@$ha_other_node_ip:

	echo " Send debian-sys-maint client config"
	scp /etc/mysql/debian.cnf root@$ha_other_node_ip:
	ssh root@$ha_other_node_ip 'av_ha_mysql_replication dsm'

	if [ -f /etc/ossim/framework/db_encryption_key ]; then
		echo " Sending dbenckey file"
		scp /etc/ossim/framework/db_encryption_key root@$ha_other_node_ip:
		ssh root@$ha_other_node_ip 'av_ha_mysql_replication dbe'
	else
		echo " WARN: /etc/ossim/framework/db_encryption_key not found"| grep --color WARN
	fi


	# --

# remote bulk status...
echo -n "Computing file and log position: "
bulk_status=`ssh root@$ha_other_node_ip 'av_ha_mysql_replication re1'`
master_log_file_and_pos=`echo $bulk_status | awk -F 'mysql-bin' '{print$2}'`
master_log_filepart=`echo $master_log_file_and_pos | awk -F' ' '{print$1}'`
master_log_file="mysql-bin$master_log_filepart"
master_log_pos=`echo $master_log_file_and_pos |awk -F' ' '{print$2}'`
echo "$master_log_file and $master_log_pos"


echo "$2" | awk -F 'mysql-bin' '{print$2}'
echo "$2" | awk -F' ' '{print$1}'



echo -e "\n-- L (local) node --"

testdbconn L

echo " Commands for DB in this node:"
echo "STOP SLAVE; CHANGE MASTER TO MASTER_HOST='$ha_other_node_ip', MASTER_USER='replication', MASTER_PASSWORD='$ha_password', MASTER_LOG_FILE='$master_log_file', MASTER_LOG_POS=$master_log_pos;START SLAVE;"
echo " STOP SLAVE, CHANGE MASTER (HOST, USER, PASS, LOG FILE and LOG POSITION), START SLAVE"
echo "STOP SLAVE; CHANGE MASTER TO MASTER_HOST='$ha_other_node_ip', MASTER_USER='replication', MASTER_PASSWORD='$ha_password', MASTER_LOG_FILE='$master_log_file', MASTER_LOG_POS=$master_log_pos;START SLAVE;"|ossim-db mysql

#echo " Restarting heartbeat"
#/etc/init.d/heartbeat restart >/dev/null 2>&1
#sleep 10


echo -e "\n-- R (remote) node --"

#echo " Restart heartbeat"
#ssh root@$ha_other_node_ip '/etc/init.d/heartbeat restart >/dev/null 2>&1'
echo " Running alienvault-reconfig to update new DB password in configuration files, insert uuid from remote server, and related tasks"
ssh root@$ha_other_node_ip 'alienvault-reconfig -c -v'

ssh root@$ha_other_node_ip 'av_ha_mysql_replication hbs'


echo -e "\n-- L (local) node --"

#alienvault-reconfig -c
# heartbeat is auto started here from reconfig
#sleep 10
/etc/init.d/heartbeat restart


echo -e "\n-- R (remote) node --"

ssh root@$ha_other_node_ip 'av_ha_mysql_replication hbr'

ssh root@$ha_other_node_ip 'av_ha_mysql_replication crr'

rdumpname="$dumppath/$hostname-dbdump.sql"
echo " Removing temporal files"
ssh root@$ha_other_node_ip "av_ha_mysql_replication rtf $rdumpname"


echo -e "\n-- L (local) node --"

/etc/init.d/cron restart

for f in /root/rsconf-temp /root/myrepl_other_node /root/myrepl_other_node-update_setup /root/db_encryption_key /root/debian.cnf $rdumpname; do
	if [ -e $f ]; then
		rm -f $f
	fi
done
if [ -f /root/.ssh/authorized_keys-B ]; then
	mv -f /root/.ssh/authorized_keys-B /root/.ssh/authorized_keys
fi


;;
esac


exit 0


