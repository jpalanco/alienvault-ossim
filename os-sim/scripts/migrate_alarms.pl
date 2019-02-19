#!/usr/bin/perl
#
# License:
#
#  Copyright (c) 2003-2006 ossim.net
#  Copyright (c) 2007-2014 AlienVault
#  All rights reserved.
#
#  This package is free software; you can redistribute it and/or modify
#  it under the terms of the GNU General Public License as published by
#  the Free Software Foundation; version 2 dated June, 1991.
#  You may not use, modify or distribute this program under any other version
#  of the GNU General Public License.
#
#  This package is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.
#
#  You should have received a copy of the GNU General Public License
#  along with this package; if not, write to the Free Software
#  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#  MA  02110-1301  USA
#
#
#  On Debian GNU/Linux systems, the complete text of the GNU General
#  Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
#  Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#

$|=1;

use lib "/usr/share/ossim/include";
use ossim_conf;
use DBI;
use Data::Dumper;

my $opened = $ARGV[0];
if ($opened ne "" && $opened ne "opened") {
	print "USAGE: migrate_alarms.pl [opened]*\n";
	exit;
}
$opened = ($opened eq "opened") ? 1 : 0;

# Data Source 
my $ossim_type = $ossim_conf::ossim_data->{"ossim_type"};
my $ossim_name = $ossim_conf::ossim_data->{"ossim_base"};
my $ossim_host = $ossim_conf::ossim_data->{"ossim_host"};
my $ossim_port = $ossim_conf::ossim_data->{"ossim_port"};
my $ossim_user = $ossim_conf::ossim_data->{"ossim_user"};
my $ossim_pass = $ossim_conf::ossim_data->{"ossim_pass"};

# Get first count
my $dsn = "dbi:" . $ossim_type . ":" . $ossim_name . ":" . $ossim_host . ":" . $ossim_port;
my $conn = DBI->connect($dsn, $ossim_user, $ossim_pass) or die "Can't connect to Database\n";

my $query = "SELECT count(*) AS total FROM ossim.event";
my $stm = $conn->prepare($query);
$stm->execute();
$row = $stm->fetchrow_hashref;
$count = $row->{total};
$stm->finish();

print "* Found $count forensic events into ossim.event table\n";

# Get sensor sids hash
my %sensor_uuids = ();
my $query = "SELECT HEX(id) AS id, INET6_NTOA(ip) AS ip FROM alienvault.sensor";
my $stm = $conn->prepare($query);
$stm->execute();
while (my $row = $stm->fetchrow_hashref) {
	$sensor_uuids{$row->{ip}} = $row->{id};
}
$stm->finish();

# ALIENVAULT.HOSTS load
my $query = "SELECT DISTINCT HEX(host_id) AS id, INET6_NTOA(ip) AS ip FROM alienvault.host_ip";
my $stm = $conn->prepare($query);
$stm->execute();
my %host = ();
while (my $row = $stm->fetchrow_hashref) {
	$host{$row->{ip}} = $row->{id};
}
$stm->finish();

# DEFAULT CTX
my $query = "select value from alienvault.config where conf like 'default_context_id'";
my $stm = $conn->prepare($query);
$stm->execute();
my $default_ctx = "";
if (my $row = $stm->fetchrow_hashref) {
	$default_ctx = uc($row->{value});
	$default_ctx =~ s/-//g;
}
$stm->finish();

my $query = "select value from alienvault.config where conf like 'default_engine_id'";
my $stm = $conn->prepare($query);
$stm->execute();
my $default_engine = "";
if (my $row = $stm->fetchrow_hashref) {
	$default_engine = uc($row->{value});
	$default_engine =~ s/-//g;
}
$stm->finish();

# Update bg_tasks variable
$conn->do("insert into alienvault.config values ('bg_tasks','1') on duplicate key update value=value+1");

# ALARMS
$where = ($opened) ? "WHERE status = 'open'" : "";
$sql = "INSERT IGNORE INTO alarm SELECT UNHEX(lpad(hex(backlog_id),32,'0')) as backlog_id, UNHEX(lpad(hex(event_id),32,'0')) as event_id,unhex('$default_engine'),timestamp,status,plugin_id,plugin_sid,protocol,inet6_aton(inet_ntoa(src_ip)) as src_ip,inet6_aton(inet_ntoa(dst_ip)) as dst_ip,src_port,dst_port,risk,efr,similar,'',1,0 FROM ossim.alarm $where";
$conn->do($sql);

# BACKLOG
$where = ($opened) ? "WHERE id IN (SELECT DISTINCT backlog_id FROM ossim.alarm WHERE status='open')" : "";
$sql = "INSERT IGNORE INTO backlog SELECT UNHEX(lpad(hex(id),32,'0')) as id,unhex('$default_engine'),directive_id,timestamp,timestamp,matched FROM ossim.backlog $where";
$conn->do($sql);

# BACKLOG_EVENT
$where = ($opened) ? "WHERE backlog_id IN (SELECT DISTINCT backlog_id FROM ossim.alarm WHERE status='open')" : "";
$sql = "INSERT IGNORE INTO backlog_event SELECT UNHEX(lpad(hex(backlog_id),32,'0')) as backlog_id, UNHEX(lpad(hex(event_id),32,'0')) as event_id,time_out,occurrence,rule_level,matched FROM ossim.backlog_event $where";
$conn->do($sql);

# EVENT
$where = ($opened) ? "WHERE id in (SELECT backlog_event.event_id as id FROM ossim.alarm, ossim.backlog_event WHERE alarm.backlog_id = backlog_event.backlog_id AND alarm.status = 'open')" : "";
for ($cc=0;$cc<$count;$cc+=5000) {
	print "* Importing alarms ".($cc*100/$count)."%...\n";
    my $conn = DBI->connect($dsn, $ossim_user, $ossim_pass) or die "Can't connect to Database\n";
    my $query = "SELECT *,lpad(hex(id),32,'0') as id, INET_NTOA(src_ip) as ip_src,INET_NTOA(dst_ip) as ip_dst FROM ossim.event $where LIMIT $cc,10000";
    my $stm = $conn->prepare($query);
    $stm->execute();
    while (my $row = $stm->fetchrow_hashref) {
        $row->{filename} =~ s/'/\\'/g;
        $row->{username} =~ s/'/\\'/g;
        $row->{password} =~ s/'/\\'/g;
        $row->{userdata1} =~ s/'/\\'/g;
        $row->{userdata2} =~ s/'/\\'/g;
        $row->{userdata3} =~ s/'/\\'/g;
        $row->{userdata4} =~ s/'/\\'/g;
        $row->{userdata5} =~ s/'/\\'/g;
        $row->{userdata6} =~ s/'/\\'/g;
        $row->{userdata7} =~ s/'/\\'/g;
        $row->{userdata8} =~ s/'/\\'/g;
        $row->{userdata9} =~ s/'/\\'/g;
        $row->{src_hostname} =~ s/'/\\'/g;
        $row->{dst_hostname} =~ s/'/\\'/g;
        $row->{src_username} =~ s/'/\\'/g;
        $row->{dst_username} =~ s/'/\\'/g;
        $row->{src_domain} =~ s/'/\\'/g;
        $row->{dst_domain} =~ s/'/\\'/g;
        my $sensor = $sensor_uuids{$row->{sensor}};
    	my $event = "INSERT IGNORE INTO event VALUES (UNHEX('".$row->{id}."'),UNHEX('$default_ctx'),'".$row->{timestamp}."','".$row->{tzone}."',UNHEX('$sensor'),'".$row->{interface}."','".$row->{ossim_type}."','".$row->{plugin_id}."','".$row->{plugin_sid}."','".$row->{protocol}."',INET6_ATON('".$row->{ip_src}."'),INET6_ATON('".$row->{ip_dst}."'),'".$row->{src_port}."','".$row->{dst_port}."','".$row->{event_condition}."','".$row->{value}."','".$row->{time_interval}."','".$row->{absolute}."','".$row->{priority}."','".$row->{reliability}."','".$row->{asset_src}."','".$row->{asset_dst}."','".$row->{risk_c}."','".$row->{risk_a}."','".$row->{alarm}."','".$row->{filename}."','".$row->{username}."','".$row->{password}."','".$row->{userdata1}."','".$row->{userdata2}."','".$row->{userdata3}."','".$row->{userdata4}."','".$row->{userdata5}."','".$row->{userdata6}."','".$row->{userdata7}."','".$row->{userdata8}."','".$row->{userdata9}."','".$row->{rulename}."','".$row->{rep_prio_src}."','".$row->{rep_prio_dst}."','".$row->{rep_rel_src}."','".$row->{rep_rel_dst}."','".$row->{rep_act_src}."','".$row->{rep_act_dst}."','".$row->{src_hostname}."','".$row->{dst_hostname}."','".$row->{src_mac}."','".$row->{dst_mac}."',UNHEX('".$host{$row->{ip_src}}."'),UNHEX('".$host{$row->{ip_dst}}."'),'','','')";
    	# IDM_DATA
    	my $idm_data_src = "";
    	if ($row->{src_username} ne '' || $row->{src_domain} ne '') {
        	$idm_data_src = "INSERT INTO idm_data VALUES (UNHEX('".$row->{id}."'),'".$row->{src_username}."','".$row->{src_domain}."',1)";
    	}
    	my $idm_data_dst = "";
    	if ($row->{dst_username} ne '' || $row->{dst_domain} ne '') {
        	$idm_data_dst = "INSERT INTO idm_data VALUES (UNHEX('".$row->{id}."'),'".$row->{dst_username}."','".$row->{dst_domain}."',0)";
    	}
    	#
    	$conn->do($event);
    	$conn->do($idm_data_src) if ($idm_data_src ne "");
    	$conn->do($idm_data_dst) if ($idm_data_dst ne "");
    }
    $stm->finish();
    $conn->disconnect();
}

# Update bg_tasks variable
my $conn = DBI->connect($dsn, $ossim_user, $ossim_pass) or die "Can't connect to Database\n";
$conn->do("update alienvault.config set value=value-1 where conf='bg_tasks' and value>0");
$conn->disconnect();

print "* Importing alarms 100%...\n";
print "ALL DONE\n";
