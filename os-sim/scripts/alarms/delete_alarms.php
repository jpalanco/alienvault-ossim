<?php
/**
*
* License:
*
* Copyright (c) 2003-2006 ossim.net
* Copyright (c) 2007-2013 AlienVault
* All rights reserved.
*
* This package is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; version 2 dated June, 1991.
* You may not use, modify or distribute this program under any other version
* of the GNU General Public License.
*
* This package is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this package; if not, write to the Free Software
* Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
* MA  02110-1301  USA
*
*
* On Debian GNU/Linux systems, the complete text of the GNU General
* Public License can be found in `/usr/share/common-licenses/GPL-2'.
*
* Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
*
*/
set_include_path('/usr/share/ossim/include');

require_once 'av_init.php';


$conf = $GLOBALS["CONF"];
$days = $conf->get_conf("alarms_lifetime", FALSE);

//$days = 5;
if ($days < 1) 
{
	exit;
}

// $time   = gmdate("U") - $days * 24 * 60 * 60;

// Get first alarm day
$db   = new ossim_db();
$conn = $db->connect();

$time = $conn->GetOne("select unix_timestamp(min(timestamp)) from alarm");
    
$db->close();

$outdir = "/var/lib/ossim/backup_alarm";

if (!is_dir($outdir)) 
{
	mkdir($outdir);
}

$user     = trim(Util::execute_command('grep ^ossim_user= /etc/ossim/framework/ossim.conf | cut -f 2 -d "="', FALSE, 'string'));
$password = trim(Util::execute_command('grep ^ossim_pass= /etc/ossim/framework/ossim.conf | cut -f 2 -d "="', FALSE, 'string'));
$host     = trim(Util::execute_command('grep ^ossim_host= /etc/ossim/framework/ossim.conf | cut -f 2 -d "="', FALSE, 'string'));

// event table backup
for ($t=$time; $t<time(); $t+=86400) 
{
    $current_date = gmdate("Y-m-d", $t);
    $file = "$outdir/alarm_restore_".$current_date.".sql";
    
    if (file_exists($file.".gz")) 
    {
            echo "Skip $file.gz file exists.\n";
            
            continue;
    } 
    elseif (file_exists($file)) 
    {
            echo "Gzip $file.\n";
            
            Util::execute_command('pigz ?', array($file));
            
            continue;
    } 
    else 
    {
            echo "Backup $current_date alarms in $file.\n";
    }

    // event    
    $where  = "id in (SELECT backlog_event.event_id as id FROM alarm, backlog_event WHERE alarm.backlog_id = backlog_event.backlog_id AND alarm.timestamp BETWEEN '$current_date 00:00:00' AND '$current_date 23:59:59')";
    $cmd    = "/usr/bin/mysqldump alienvault event -h ? -u ? -p? -c -n -t -f --hex-blob --skip-comments --skip-triggers --no-autocommit --single-transaction --quick  --insert-ignore -w ? >> ? 2>/dev/null";
    $params = array($host, $user, $password, $where, $file);
    
    Util::execute_command($cmd, $params);

    // extra_data    
    $where  = "event_id in (SELECT backlog_event.event_id as id FROM alarm, backlog_event WHERE alarm.backlog_id = backlog_event.backlog_id AND alarm.timestamp BETWEEN '$current_date 00:00:00' AND '$current_date 23:59:59')";
    $cmd    = "/usr/bin/mysqldump alienvault extra_data -h ? -u ? -p? -c -n -t -f --hex-blob --skip-comments --skip-triggers --no-autocommit --single-transaction --quick  --insert-ignore -w ? >> ? 2>/dev/null";
    $params = array($host, $user, $password, $where, $file);
    
    Util::execute_command($cmd, $params);
        
    // idm_data
    $where  = "event_id in (SELECT backlog_event.event_id as id FROM alarm, backlog_event WHERE alarm.backlog_id = backlog_event.backlog_id AND alarm.timestamp BETWEEN '$current_date 00:00:00' AND '$current_date 23:59:59')";
    $cmd    = "/usr/bin/mysqldump alienvault idm_data -h ? -u ? -p? -c -n -t -f --hex-blob --skip-comments --skip-triggers --no-autocommit --single-transaction --quick  --insert-ignore -w ? >> ? 2>/dev/null";
    $params = array($host, $user, $password, $where, $file);

    // otx_data
    $where  = "event_id in (SELECT backlog_event.event_id as id FROM alarm, backlog_event WHERE alarm.backlog_id = backlog_event.backlog_id AND alarm.timestamp BETWEEN '$current_date 00:00:00' AND '$current_date 23:59:59')";
    $cmd    = "/usr/bin/mysqldump alienvault otx_data -h ? -u ? -p? -c -n -t -f --hex-blob --skip-comments --skip-triggers --no-autocommit --single-transaction --quick  --insert-ignore -w ? >> ? 2>/dev/null";
    $params = array($host, $user, $password, $where, $file);

    Util::execute_command($cmd, $params);

    // backlog_event
    $where  = "backlog_id in (SELECT backlog_event.backlog_id FROM alarm, backlog_event WHERE alarm.backlog_id = backlog_event.backlog_id AND alarm.timestamp BETWEEN '$current_date 00:00:00' AND '$current_date 23:59:59')";
    $cmd    = "/usr/bin/mysqldump alienvault backlog_event -h ? -u ? -p? -c -n -t -f --hex-blob --skip-comments --skip-triggers --no-autocommit --single-transaction --quick  --insert-ignore -w ? >> ? 2>/dev/null";
    $params = array($host, $user, $password, $where, $file);
    
    Util::execute_command($cmd, $params);

    // backlog
    $where  = "id in (SELECT backlog_id as id FROM alarm WHERE timestamp BETWEEN '$current_date 00:00:00' AND '$current_date 23:59:59')";
    $cmd    = "/usr/bin/mysqldump alienvault backlog -h ? -u ? -p? -c -n -t -f --hex-blob --skip-comments --skip-triggers --no-autocommit --single-transaction --quick  --insert-ignore -w ? >> ? 2>/dev/null";
    $params = array($host, $user, $password, $where, $file);
    
    Util::execute_command($cmd, $params);
    
    // alarm
    $where  = "timestamp BETWEEN '$current_date 00:00:00' AND '$current_date 23:59:59'";
    $cmd    = "/usr/bin/mysqldump alienvault alarm -h ? -u ? -p? -c -n -t -f --hex-blob --skip-comments --skip-triggers --no-autocommit --single-transaction --quick  --insert-ignore -w ? >> ? 2>/dev/null";
    $params = array($host, $user, $password, $where, $file);
    
    Util::execute_command($cmd, $params);

    // component_tags, _ctxs, _hosts, _nets
    $where  = "id_component in (SELECT backlog_id FROM alarm WHERE timestamp BETWEEN '$current_date 00:00:00' AND '$current_date 23:59:59')";
    $cmd    = "/usr/bin/mysqldump alienvault component_tags -h ? -u ? -p? -c -n -t -f --hex-blob --skip-triggers --skip-comments --no-autocommit --single-transaction --quick  --insert-ignore -w ? >> ? 2>/dev/null";
    $params = array($host, $user, $password, $where, $file);
    
    Util::execute_command($cmd, $params);
      
    $where  = "id_alarm in (SELECT backlog_id as id FROM alarm WHERE timestamp BETWEEN '$current_date 00:00:00' AND '$current_date 23:59:59')";
    $cmd    = "/usr/bin/mysqldump alienvault alarm_ctxs -h ? -u ? -p? -c -n -t -f --hex-blob --skip-comments --skip-triggers --no-autocommit --single-transaction --quick  --insert-ignore -w ? >> ? 2>/dev/null";
    $params = array($host, $user, $password, $where, $file);
    
    Util::execute_command($cmd, $params);
     
     
    $cmd = "/usr/bin/mysqldump alienvault alarm_nets -h ? -u ? -p? -c -n -t -f --hex-blob --skip-comments --skip-triggers --no-autocommit --single-transaction --quick  --insert-ignore -w ? >> ? 2>/dev/null";
    // Same params
    
    Util::execute_command($cmd, $params);
    
    
    $cmd = "/usr/bin/mysqldump alienvault alarm_hosts -h ? -u ? -p? -c -n -t -f --hex-blob --skip-comments --skip-triggers --no-autocommit --single-transaction --quick  --insert-ignore -w ? >> ? 2>/dev/null";
    // Same params
    
    Util::execute_command($cmd, $params);
    
    
    // GZIP CURRENT
    Util::execute_command('pigz ?', array($file));
}


// DELETES
$db      = new ossim_db();
$conn    = $db->connect();
$date_to = date("Y-m-d H:i:s", time() - $days * 24 * 60 * 60);

echo "Delete with date <= $date_to ... ";

// event
$tmptable = Util::create_tmp_table($conn,"id binary(16) NOT NULL, PRIMARY KEY ( id ))");

$conn->Execute("INSERT IGNORE INTO $tmptable SELECT backlog_event.event_id as id FROM alarm, backlog_event WHERE alarm.backlog_id = backlog_event.backlog_id AND alarm.timestamp <= '$date_to'");	
$conn->Execute("DELETE e FROM event e, $tmptable t WHERE e.id=t.id");

// backlog tables
$conn->Execute("TRUNCATE TABLE $tmptable");
$conn->Execute("INSERT IGNORE INTO $tmptable SELECT backlog_id as id FROM alarm WHERE timestamp <= '$date_to'");		
$conn->Execute("DELETE b FROM backlog b, $tmptable t WHERE b.id=t.id");
$conn->Execute("DROP TABLE $tmptable");

$conn->Execute("DELETE backlog_event.* FROM backlog_event, alarm WHERE alarm.timestamp <= '$date_to' AND backlog_event.backlog_id = alarm.backlog_id AND backlog_event.event_id = alarm.event_id");

// alarm
$conn->Execute("DELETE FROM alarm WHERE timestamp <= '$date_to'");

// orphans
$conn->Execute("DELETE tg FROM component_tags tg LEFT JOIN alarm a ON tg.id_component = a.backlog_id, tag ta WHERE ta.id=tg.id_tag AND ta.type='alarm' AND a.backlog_id IS NULL");
$conn->Execute("DELETE ac FROM alarm_ctxs ac LEFT JOIN alarm a ON ac.id_alarm = a.backlog_id WHERE a.backlog_id IS NULL");
$conn->Execute("DELETE ah FROM alarm_hosts ah LEFT JOIN alarm a ON ah.id_alarm = a.backlog_id WHERE a.backlog_id IS NULL");
$conn->Execute("DELETE an FROM alarm_nets an LEFT JOIN alarm a ON an.id_alarm = a.backlog_id WHERE a.backlog_id IS NULL");
$conn->Execute("DELETE idm FROM idm_data idm LEFT JOIN event e ON idm.event_id = e.id WHERE e.id IS NULL");
$conn->Execute("DELETE otx FROM otx_data otx LEFT JOIN event e ON otx.event_id = e.id WHERE e.id IS NULL");
$conn->Execute("DELETE ed FROM extra_data ed LEFT JOIN event e ON ed.event_id = e.id WHERE e.id IS NULL");

$db->close();

echo "Done.\n";
