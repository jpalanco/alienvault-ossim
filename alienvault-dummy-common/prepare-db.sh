#!/bin/sh
### BEGIN INIT INFO
# Provides:             prepare-db
# Required-Start:       $local_fs $network
# Required-Stop:        $local_fs $network
# Should-Start:         mysql
# X-Start-Before:       finish-install
# Default-Start:        2 3 4 5
# Default-Stop:         0 1 6
# Short-Description:    Prepares our database for the first time
# Description:
### END INIT INFO

OSSIM_SETUP_CONF_FILE="/etc/ossim/ossim_setup.conf"
OSSIM_SETUP_CONF_LAST_FILE="/etc/ossim/ossim_setup.conf_last"
PREPARE_DB_DONE_FILE="/etc/ossim/.prepare-db-done"
ENCRYPTION_KEY_FILE="/etc/ossim/framework/db_encryption_key"
FRAMEWORK_CONF_FILE="/etc/ossim/framework/ossim.conf"
SERVER_CONF_FILE="/etc/ossim/server/config.xml"
OSSIM_CUSTOM_CONF_FILE="/etc/ossim/.ossim_custom_conf"
DEBUG_MODE=0

log_debug_message() {
    [ $DEBUG_MODE -ne 0 ] && echo "`date -u` -- $1" >> /var/log/alienvault/update/prepare-db.log 2>&1
}

do_start() {
    if [ ! -f "$PREPARE_DB_DONE_FILE" ]; then
        if [ -f "$OSSIM_CUSTOM_CONF_FILE" ]; then
		log_debug_message "LOADING CUSTOM ALIENVAULT VARIABLE FROM $OSSIM_CUSTOM_CONF_FILE"	
                source $OSSIM_CUSTOM_CONF_FILE
	        log_debug_message "BYPASSING MYSQL REMOTE CONFIGURATION, PROVIDE BY CUSTOM CONFIG IP: $db_ip PASS: $db_password"
        fi
        log_debug_message "Begin prepare-db.sh"

        ADMIN_IP=$(grep ^admin_ip= "$OSSIM_SETUP_CONF_FILE" | cut -f 2 -d "=")
        SERVER_IP=$(grep ^server_ip= "$OSSIM_SETUP_CONF_FILE" | cut -f 2 -d "=")
        FRAMEWORK_IP=$(grep ^framework_ip= "$OSSIM_SETUP_CONF_FILE" | cut -f 2 -d "=")
        HOSTNAME=$(grep ^hostname= "$OSSIM_SETUP_CONF_FILE" | cut -f 2 -d "=")
        PROFILE=$(grep ^profile= "$OSSIM_SETUP_CONF_FILE" | head -1 | cut -f 2 -d "=" | tr -d ' ' 2>/dev/null)

        # Request MySQL data if needed
        if [[ "$PROFILE" =~ "Server" && ! "$PROFILE" =~ "Database" ]]; then
            if [[ -z $db_password ]] || [[ -z $db_ip ]]; then
               openvt -s -w -- /usr/sbin/alienvault-setup --menu=menu_mysql.cfg
            else
	       log_debug_message "BYPASSING MYSQL REMOTE CONFIGURATION, PROVIDE BY CUSTOM CONFIG IP: $db_ip PASS: $db_password"
               sed -i "s/^pass=.*/pass=${db_password}/g" $OSSIM_SETUP_CONF_FILE
               sed -i "s/^db_ip=.*/db_ip=${db_ip}/g" $OSSIM_SETUP_CONF_FILE
            fi
            dpkg-trigger --no-await alienvault-config-database-db-ip
            dpkg-trigger --no-await alienvault-config-database-pass
            log_debug_message "MySQL remote configuration done"
        fi

        DBHOST=$(grep ^db_ip= "$OSSIM_SETUP_CONF_FILE" | cut -f 2 -d "=")
        DBUSER=$(grep ^user= "$OSSIM_SETUP_CONF_FILE" | cut -f 2 -d "=")
        DBPASS=$(grep ^pass= "$OSSIM_SETUP_CONF_FILE" | cut -f 2 -d "=")

        # Wait for mysql to start
        if [[ "$PROFILE" =~ "Database" ]]
        then
            retry=0
            until mysqladmin ping >/dev/null 2>&1; do
                log_debug_message "Waiting for MySQL to start"
                if [ 50 -le $retry ]
                then
                    plymouth message --text="Free disk read/write, memory and/or CPU and redeploy again"
                    sleep 9999999
                    exit 1
                fi
                sleep 5

                retry=$(( $retry + 1 ))
            done
        fi

        # First of all, rebuild the database
        #plymouth message --text="Building AlienVault database"
        /usr/share/ossim/cd-tools/alienvault-rebuild_db >> /var/log/alienvault/update/prepare-db.log 2>&1
        log_debug_message "AlienVault database built"

        if [[ -z $db_password ]]; then
           [[ "$PROFILE" =~ "Server" && ! "$PROFILE" =~ "Database" ]] && RNDPASS=$DBPASS || RNDPASS=$(< /dev/urandom tr -dc A-Za-z0-9 | head -c10)
        else
           RNDPASS=$db_password
        fi

        SYSTEM_ID=$(/usr/bin/alienvault-system-id)
        SENSOR_ID=$(echo "SELECT REPLACE(UUID(),'-','') as id" | ossim-db alienvault | grep -v id 2>> /var/log/alienvault/update/prepare-db.log)
        SERVER_ID=$SYSTEM_ID

        plymouth message --text="Configuring basic database parameters"
        zcat /usr/share/doc/ossim-mysql/contrib/01-create_alienvault_data_config.sql.gz | ossim-db alienvault >> /var/log/alienvault/update/prepare-db.log 2>&1
        log_debug_message "Basic AlienVault config added"

        zcat /usr/share/doc/ossim-mysql/contrib/02-create_alienvault_data_data.sql.gz | ossim-db alienvault >> /var/log/alienvault/update/prepare-db.log 2>&1
        log_debug_message "Basic AlienVault data added"

        echo "CALL alarm_taxonomy_populate();" | ossim-db alienvault >> /var/log/alienvault/update/prepare-db.log 2>&1
        log_debug_message "Alarm taxonomy tables populated"

        # Encryption key
        echo "Generating $ENCRYPTION_KEY_FILE"
        echo "[key-value]" > $ENCRYPTION_KEY_FILE
        echo "key=$SYSTEM_ID" >> $ENCRYPTION_KEY_FILE
        chmod 440 $ENCRYPTION_KEY_FILE
        chown www-data:alienvault $ENCRYPTION_KEY_FILE
        ENCRYPTION_KEY="$SYSTEM_ID"

        # Config table
        echo "REPLACE INTO config VALUES ('encryption_key','$ENCRYPTION_KEY'),('snort_host','$DBHOST'),('phpgacl_host','$DBHOST'),('phpgacl_user','root'),('server_address','$SERVER_IP'),('backup_host','$DBHOST'),('osvdb_host','$DBHOST'),('frameworkd_address','$FRAMEWORK_IP'),('frameworkd_port','40003'),('nagios_link','/nagios3/'),('snort_pass',AES_ENCRYPT('$DBPASS','$ENCRYPTION_KEY')),('bi_pass',AES_ENCRYPT('$DBPASS','$ENCRYPTION_KEY')),('osvdb_pass',AES_ENCRYPT('$DBPASS','$ENCRYPTION_KEY')),('backup_pass',AES_ENCRYPT('$DBPASS','$ENCRYPTION_KEY')),('phpgacl_pass',AES_ENCRYPT('$DBPASS','$ENCRYPTION_KEY')),('nessus_pass',AES_ENCRYPT('$DBPASS','$ENCRYPTION_KEY')),('server_id', '$SERVER_ID');" | ossim-db alienvault >> /var/log/alienvault/update/prepare-db.log 2>&1

        if [[ "$PROFILE" =~ "Server,Sensor,Framework,Database" ]]; then
            echo "REPLACE INTO config VALUES ('start_welcome_wizard',1);" | ossim-db alienvault >> /var/log/alienvault/update/prepare-db.log 2>&1
        fi

        plymouth message --text="Adding default values"

	    # DS Groups and Default policies
        zcat /usr/share/doc/ossim-mysql/contrib/03-create_alienvault_data_croscor_snort_nessus.sql.gz | ossim-db alienvault >> /var/log/alienvault/update/prepare-db.log 2>&1
        log_debug_message "DS groups and default policies added"

        # Timezone
        TIMEZONE=$(cat /etc/timezone)
        echo "UPDATE alienvault.users SET timezone='$TIMEZONE' WHERE login='admin';" | ossim-db alienvault >> /var/log/alienvault/update/prepare-db.log 2>&1
        echo "UPDATE acl_entities SET server_id = UNHEX(REPLACE('$SERVER_ID','-','')), timezone = '$TIMEZONE' WHERE id = (SELECT UNHEX(REPLACE (value, '-', '')) FROM config WHERE conf LIKE 'default_engine_id');" | ossim-db alienvault >> /var/log/alienvault/update/prepare-db.log 2>&1
        echo "UPDATE acl_entities SET server_id = UNHEX(REPLACE('$SERVER_ID','-','')), timezone = '$TIMEZONE' WHERE id = (SELECT UNHEX(REPLACE (value, '-', '')) FROM config WHERE conf LIKE 'default_context_id');" | ossim-db alienvault >> /var/log/alienvault/update/prepare-db.log 2>&1
        log_debug_message "Timezone set"

        # Host table
        echo "SELECT REPLACE(UUID(),'-','') into @uuid; INSERT IGNORE INTO alienvault.host (id,ctx,hostname,asset,threshold_c,threshold_a,alert,persistence,nat,rrd_profile,descr,lat,lon,av_component) VALUES (UNHEX(@uuid),(SELECT UNHEX(REPLACE(value,'-','')) FROM alienvault.config WHERE conf = 'default_context_id'), '$HOSTNAME', '2', '30', '30', '0', '0', '', '', '', '0', '0', 1); INSERT IGNORE INTO alienvault.host_ip (host_id,ip) VALUES (UNHEX(@uuid),inet6_aton('$ADMIN_IP'));" | ossim-db alienvault >> /var/log/alienvault/update/prepare-db.log 2>&1
        echo "REPLACE INTO alienvault.host_net_reference SELECT host.id,net_id FROM alienvault.host, alienvault.host_ip, alienvault.net_cidrs WHERE host.id = host_ip.host_id AND host_ip.ip >= net_cidrs.begin AND host_ip.ip <= net_cidrs.end;" | ossim-db alienvault >> /var/log/alienvault/update/prepare-db.log 2>&1
        log_debug_message "Default host data added"

        # Server table
        echo "REPLACE INTO server_role (server_id) VALUES (UNHEX(REPLACE('$SERVER_ID','-','')));" | ossim-db alienvault >> /var/log/alienvault/update/prepare-db.log 2>&1
        echo "REPLACE INTO server (id, name, ip, port) VALUES (UNHEX(REPLACE('$SERVER_ID','-','')), '$HOSTNAME', inet6_aton('$ADMIN_IP'), '40001');" | ossim-db  alienvault >> /var/log/alienvault/update/prepare-db.log 2>&1
        log_debug_message "Default server data added"

        # Sensor table
        DIFF_TZ=$(date +'%:::z'|sed 's:^-0:-:'|sed 's:^+0::')
        VERSION=$(dpkg -l | grep ossim-cd-tools | awk '{print $3}' | awk -F'-' '{ print $1 }')

        if [[ "$PROFILE" =~ "Sensor" ]]; then
            echo "CALL sensor_update ('admin','$SENSOR_ID','$ADMIN_IP','$HOSTNAME',5,40001,'$DIFF_TZ','','','$VERSION',1,1,1,0,1,1,1);" | ossim-db alienvault >> /var/log/alienvault/update/prepare-db.log 2>&1

            # System table
            echo "Add system"
            echo "CALL system_update('$SYSTEM_ID','$HOSTNAME','$ADMIN_IP','','$PROFILE','','','','$SENSOR_ID','$SERVER_ID');" | ossim-db alienvault >> /var/log/alienvault/update/prepare-db.log 2>&1
        else
            # System table
            echo "Add system"
            echo "CALL system_update('$SYSTEM_ID','$HOSTNAME','$ADMIN_IP','','$PROFILE','','','','','$SERVER_ID');" | ossim-db alienvault >> /var/log/alienvault/update/prepare-db.log 2>&1
        fi
        log_debug_message "Default sensor data added"

        # Last step. We need to change the DB password at the end, so ossim-db keeps working.
        sed -i "s:ossim_pass=.*:ossim_pass=$RNDPASS:" "$FRAMEWORK_CONF_FILE" >> /var/log/alienvault/update/prepare-db.log 2>&1
        sed -i "s:ossim_host=.*:ossim_host=$DBHOST:" "$FRAMEWORK_CONF_FILE" >> /var/log/alienvault/update/prepare-db.log 2>&1
        sed -i "s:^pass=.*:pass=$RNDPASS:" "$OSSIM_SETUP_CONF_FILE" >> /var/log/alienvault/update/prepare-db.log 2>&1
        sed -i "s:;PASSWORD=.*;DATABASE:;PASSWORD=$RNDPASS;DATABASE:" "$SERVER_CONF_FILE" >> /var/log/alienvault/update/prepare-db.log 2>&1
        sed -i "s:sig_pass=.*:sig_pass=\"$RNDPASS\":" "$SERVER_CONF_FILE" >> /var/log/alienvault/update/prepare-db.log 2>&1

        # TODO move to another script
        if ! grep -sq idm "$SERVER_CONF_FILE"; then
            sed -i "s:</config>:<idm mssp=\"false\"/>\n</config>:" "$SERVER_CONF_FILE" >> /var/log/alienvault/update/prepare-db.log 2>&1
        fi
        log_debug_message "Default password changed"

        # Launch trigger to change Database password.
        dpkg-trigger --no-await alienvault-mysql-set-grants
        log_debug_message "MySQL grant trigger launched"

        plymouth message --text="Finish building AlienVault database"
        touch "$PREPARE_DB_DONE_FILE"

        log_debug_message "End prepare-db.sh"
    fi
}

do_stop(){
	/bin/echo "Not implemented (nothing to do)"
}

case "$1" in
  start)
        do_start
	;;
  stop)
        do_stop
	;;
  *)
	echo "Usage: $N {start|stop}" >&2
	exit 1
	;;
esac

exit 0
