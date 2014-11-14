#!/bin/bash
RESTART=$1
USER=`grep ^user= /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`
PASS=`grep ^pass= /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`
HOST=`grep ^db_ip= /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`
ADMIN_IP=`grep ^admin_ip= /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`
OK=`mysqldump -h $HOST -u $USER -p$PASS -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers alienvault server_forward_role | grep -c "server_forward_role"`
#
if [ $OK -eq 0 ]; then
	echo "No values at server_forward_role."
	exit;
fi
if [ ! -d /var/lib/alienvault-center/db/ ]; then
        echo "Needed target directory /var/lib/alienvault-center/db/ doesn't exists."
        exit;
fi

echo -n "Generation assets dump file:"
TMPFILE='/var/tmp/asset_dump'
rm -f $TMPFILE

if [ "${RESTART}" = "restart" ]; then
    echo "-- RESTART OSSIM-SERVER" >> $TMPFILE
fi

echo "SET AUTOCOMMIT=0;" >> $TMPFILE
echo "SET @disable_calc_perms=1;" >> $TMPFILE

# 
# System ID
SYSID=`echo "select concat('0x',hex(id)) as id from alienvault.system where admin_ip=inet6_pton('$ADMIN_IP')"|ossim-db|sed -e '1,${ /^id/d }'`
mysqldump -h $HOST -u $USER -p$PASS -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers -w "id not in  ($SYSID)" alienvault system >> $TMPFILE
#
# ENTITIES
CTXS=`echo "select concat('0x',hex(id)) as ctx from acl_entities where entity_type in ('context','engine')"|ossim-db|sed -e '1,${ /^ctx/d }'|xargs|sed 's/ /,/g'`
ONLYCTXS=`echo "select concat('0x',hex(id)) as ctx from acl_entities where entity_type in ('context')"|ossim-db|sed -e '1,${ /^ctx/d }'|xargs|sed 's/ /,/g'`
ONLYENGINES=`echo "select concat('0x',hex(id)) as ctx from acl_entities where entity_type in ('engine')"|ossim-db|sed -e '1,${ /^ctx/d }'|xargs|sed 's/ /,/g'`
SEIDS=`echo "select concat('0x',hex(server_id)) as ctx from acl_entities where entity_type in ('engine')"|ossim-db|sed -e '1,${ /^ctx/d }'|xargs|sed 's/ /,/g'`
mysqldump -h $HOST -u $USER -p$PASS -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers -w "entity_type in ('context','engine')" alienvault acl_entities >> $TMPFILE
echo "select unhex(replace(value,'-','')) into @ctx from config where conf='default_context_id';" >> $TMPFILE
echo "update acl_entities set parent_id=@ctx where hex(parent_id)='00000000000000000000000000000000' and id in ($CTXS);" >> $TMPFILE
echo "delete from corr_engine_contexts where event_ctx in ($CTXS);" >> $TMPFILE
mysqldump -h $HOST -u $USER -p$PASS -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers -w "event_ctx in ($CTXS)" alienvault corr_engine_contexts >> $TMPFILE
echo "select unhex(replace(value,'-','')) into @engine from config where conf='default_engine_id';" >> $TMPFILE
echo "replace into corr_engine_contexts (engine_ctx, event_ctx, descr) select @engine,id,'' from acl_entities where id in ($ONLYCTXS) and id not in (select c.event_ctx from corr_engine_contexts c,acl_entities a where a.id=c.event_ctx and a.server_id not in ($SEIDS));" >> $TMPFILE
echo "replace into corr_engine_contexts (engine_ctx, event_ctx, descr) select @engine,id,'' from acl_entities where id in ($ONLYENGINES) and id not in (select c.event_ctx from corr_engine_contexts c,acl_entities a where a.id=c.event_ctx and a.server_id not in ($SEIDS));" >> $TMPFILE
#
# SERVERS
SERVERS=`echo "select concat('0x',hex(id)) as server from server where id in (select server_id from acl_entities where id in ($CTXS))"|ossim-db|sed -e '1,${ /^server/d }'|xargs|sed 's/ /,/g'`
echo "delete from server where id in ($SERVERS);" >> $TMPFILE
mysqldump -h $HOST -u $USER -p$PASS -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers -w "id in ($SERVERS)" alienvault server >> $TMPFILE
echo "delete from server_role where server_id in ($SERVERS);" >> $TMPFILE
mysqldump -h $HOST -u $USER -p$PASS -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers -w "server_id in ($SERVERS)" alienvault server_role >> $TMPFILE
echo "delete from server_forward_role where server_src_id in ($SERVERS) OR server_dst_id in ($SERVERS);" >> $TMPFILE
mysqldump -h $HOST -u $USER -p$PASS -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers -w "server_src_id in ($SERVERS) OR server_dst_id in ($SERVERS)" alienvault server_forward_role >> $TMPFILE
echo "delete from server_hierarchy where child_id in ($SERVERS) OR parent_id in ($SERVERS);" >> $TMPFILE
mysqldump -h $HOST -u $USER -p$PASS -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers -w "child_id in ($SERVERS) OR parent_id in ($SERVERS)" alienvault server_hierarchy >> $TMPFILE
#
# TAGS ALARM
echo "
DROP PROCEDURE IF EXISTS create_tag_alarm;
DELIMITER \$\$
CREATE PROCEDURE create_tag_alarm()
BEGIN
  DECLARE done        INT DEFAULT 0;
  DECLARE engine_uuid VARCHAR(255);

  DECLARE cur1 CURSOR FOR select hex(id) from acl_entities where id in ($ONLYENGINES);
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

  OPEN cur1;

  REPEAT
    FETCH cur1 INTO engine_uuid;
    IF NOT done THEN
		SET @engine = unhex(engine_uuid);
		select s.name into @server from acl_entities a,server s where a.server_id=s.id and hex(a.id)=engine_uuid;
		IF NOT EXISTS
		  (SELECT id FROM tags_alarm WHERE hex(ctx)=engine_uuid)
		THEN
		    INSERT INTO tags_alarm (ctx,name,bgcolor,fgcolor,italic,bold) VALUES (@engine,@server,'dee5f2','5a6986',0,0);
		ELSE
			SELECT id into @tag FROM tags_alarm WHERE hex(ctx)=engine_uuid;
			UPDATE tags_alarm SET name=@server WHERE id=@tag;
		END IF;
    END IF;
  UNTIL done END REPEAT;

  CLOSE cur1;
END;
\$\$
DELIMITER ;
CALL create_tag_alarm();
DROP PROCEDURE create_tag_alarm;
" >> $TMPFILE
#
# ALARM TAXONOMY
mysqldump -h $HOST -u $USER -p$PASS -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers -w "engine_id in ($ONLYENGINES)" alienvault alarm_taxonomy >> $TMPFILE
#
# SENSORS
SENSORS=`echo "select concat('0x',hex(id)) as sensor from sensor where name != '(null)'"|ossim-db|sed -e '1,${ /^sensor/d }'|xargs|sed 's/ /,/g'`
echo "delete sensor.* from sensor,acl_sensors where sensor.id=acl_sensors.sensor_id and acl_sensors.entity_id in ($CTXS);" >> $TMPFILE
mysqldump -h $HOST -u $USER -p$PASS -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers alienvault sensor -w "name != '(null)'" >> $TMPFILE
echo "delete from acl_sensors where sensor_id in ($SENSORS);" >> $TMPFILE
mysqldump -h $HOST -u $USER -p$PASS -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers alienvault acl_sensors >> $TMPFILE
echo "delete from sensor_properties where sensor_id in ($SENSORS);" >> $TMPFILE
mysqldump -h $HOST -u $USER -p$PASS -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers alienvault sensor_properties >> $TMPFILE
echo "delete from locations where ctx in ($CTXS);" >> $TMPFILE
mysqldump -h $HOST -u $USER -p$PASS -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers alienvault locations >> $TMPFILE
echo "delete from location_sensor_reference where sensor_id in ($SENSORS);" >> $TMPFILE
mysqldump -h $HOST -u $USER -p$PASS -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers alienvault location_sensor_reference >> $TMPFILE
#
# REPAIR SENSOR_ID NULL IN ALIENVAULT.SYSTEM
echo "
DROP PROCEDURE IF EXISTS fix_sensor_id;
DELIMITER \$\$
CREATE PROCEDURE fix_sensor_id()
BEGIN
  DECLARE done       INT DEFAULT 0;
  DECLARE _system_id VARCHAR(64);
  DECLARE _admin_ip  VARCHAR(64);
  DECLARE _vpn_ip    VARCHAR(64);

  DECLARE cur1 CURSOR FOR select hex(id), inet6_ntop(admin_ip), inet6_ntop(vpn_ip) from alienvault.system where sensor_id is null;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

  OPEN cur1;

  REPEAT
    FETCH cur1 INTO _system_id, _admin_ip, _vpn_ip;
    IF NOT done THEN
		UPDATE alienvault.system SET sensor_id=(SELECT sensor.id FROM sensor WHERE sensor.ip=inet6_pton(_admin_ip) OR sensor.ip=inet6_pton(_vpn_ip) LIMIT 1) WHERE id=UNHEX(_system_id);
    END IF;
  UNTIL done END REPEAT;

  CLOSE cur1;
END;
\$\$
DELIMITER ;
CALL fix_sensor_id();
DROP PROCEDURE fix_sensor_id;
" >> $TMPFILE
#
# HOSTS
echo "CREATE TEMPORARY TABLE IF NOT EXISTS tmp_delete_assets (id binary(16) NOT NULL, PRIMARY KEY (id));" >> $TMPFILE
echo "insert into tmp_delete_assets select id from host where ctx in ($CTXS);" >> $TMPFILE
echo "delete from host where ctx in ($CTXS);" >> $TMPFILE
echo "delete ht FROM host_types ht            LEFT JOIN tmp_delete_assets h ON ht.host_id=h.id WHERE h.id is not null;" >> $TMPFILE
echo "delete ht FROM host_sensor_reference ht LEFT JOIN tmp_delete_assets h ON ht.host_id=h.id WHERE h.id is not null;" >> $TMPFILE
echo "delete ht FROM host_ip ht               LEFT JOIN tmp_delete_assets h ON ht.host_id=h.id WHERE h.id is not null;" >> $TMPFILE
echo "delete ht FROM host_net_reference ht    LEFT JOIN tmp_delete_assets h ON ht.host_id=h.id WHERE h.id is not null;" >> $TMPFILE
echo "delete ht FROM host_properties ht       LEFT JOIN tmp_delete_assets h ON ht.host_id=h.id WHERE h.id is not null;" >> $TMPFILE
echo "delete ht FROM host_services ht         LEFT JOIN tmp_delete_assets h ON ht.host_id=h.id WHERE h.id is not null;" >> $TMPFILE
echo "delete ht FROM host_software ht         LEFT JOIN tmp_delete_assets h ON ht.host_id=h.id WHERE h.id is not null;" >> $TMPFILE
echo "delete ht FROM host_types ht            LEFT JOIN tmp_delete_assets h ON ht.host_id=h.id WHERE h.id is not null;" >> $TMPFILE
mysqldump -h $HOST -u $USER -p$PASS -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers alienvault host >> $TMPFILE
mysqldump -h $HOST -u $USER -p$PASS -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers alienvault host_ip >> $TMPFILE
mysqldump -h $HOST -u $USER -p$PASS -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers alienvault host_sensor_reference >> $TMPFILE
mysqldump -h $HOST -u $USER -p$PASS -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers alienvault host_types >> $TMPFILE
mysqldump -h $HOST -u $USER -p$PASS -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers alienvault host_services >> $TMPFILE
mysqldump -h $HOST -u $USER -p$PASS -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers alienvault host_properties >> $TMPFILE
mysqldump -h $HOST -u $USER -p$PASS -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers alienvault host_software >> $TMPFILE
mysqldump -h $HOST -u $USER -p$PASS -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers alienvault host_types >> $TMPFILE
#
# NETS
echo "delete net_sensor_reference.* from net,net_sensor_reference where net.id=net_sensor_reference.net_id and net.ctx in ($CTXS);" >> $TMPFILE
echo "delete net_cidrs.* from net,net_cidrs where net.id=net_cidrs.net_id and net.ctx in ($CTXS);" >> $TMPFILE
echo "delete host_net_reference.* from net,host_net_reference where host_net_reference.net_id=net.id and net.ctx in ($CTXS);" >> $TMPFILE
echo "delete from net where ctx in ($CTXS);" >> $TMPFILE
mysqldump -h $HOST -u $USER -p$PASS -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers alienvault net >> $TMPFILE
mysqldump -h $HOST -u $USER -p$PASS -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers alienvault net_sensor_reference >> $TMPFILE
mysqldump -h $HOST -u $USER -p$PASS -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers alienvault net_cidrs >> $TMPFILE
mysqldump -h $HOST -u $USER -p$PASS -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers alienvault host_net_reference >> $TMPFILE
#
# PLUGIN SIDS
mysqldump -h $HOST -u $USER -p$PASS -t --replace --hex-blob --complete-insert --single-transaction --compact --skip-triggers alienvault plugin_sid >> $TMPFILE
echo "REPLACE INTO plugin_sid (plugin_ctx,plugin_id,sid,class_id,reliability,priority,name,aro,subcategory_id,category_id) SELECT plugin_ctx,plugin_id,sid,class_id,reliability,priority,name,aro,subcategory_id,category_id FROM plugin_sid_changes;" >> $TMPFILE
#
# LATEST ASSET REFRESH
echo "replace into config values ('latest_asset_change',UTC_TIMESTAMP());" >> $TMPFILE
echo "SET @disable_calc_perms=NULL;" >> $TMPFILE
echo "CALL update_all_users();" >> $TMPFILE
echo "COMMIT;" >> $TMPFILE
#
FILENAME=/var/lib/alienvault-center/db/sync.sql
FILENAME_MD5=/var/lib/alienvault-center/db/sync.md5
rm -f /var/lib/alienvault-center/db/sync*
mv $TMPFILE $FILENAME
md5sum $FILENAME | sed 's/ .*//' > $FILENAME_MD5
gzip $FILENAME
chown www-data:www-data /var/lib/alienvault-center/db/sync*
echo "$FILENAME.gz and $FILENAME_MD5 generated successfully."
