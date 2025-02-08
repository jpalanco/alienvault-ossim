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

sub generate_uuid
{
	my $length_of_randomstring = 32; # the length of 
			 # the random string to generate

	my @chars=('A'..'F','0'..'9');
	my $random_string;
	foreach (1..$length_of_randomstring) 
	{
		# rand @chars will generate a random 
		# number between 0 and scalar @chars
		$random_string.=$chars[rand @chars];
	}
	return $random_string;
}

my $snort_name = ($ARGV[0] ne "") ? $ARGV[0] : "snort";
my $default_ctx = $ARGV[1];
if ($snort_name !~ /^[a-zA-Z0-9_]+$/) {
	print "USAGE: migrate_snort.pl [valid_V3_snort_database_name] [default_ctx]*\n";
	exit;
}
if ($default_ctx ne "" && $default_ctx !~ /^[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}$/) {
	print "USAGE: migrate_snort.pl [valid_V3_snort_database_name] [default_ctx]*\n";
	exit;
} elsif ($default_ctx ne "") {
	$default_ctx = uc($default_ctx);
	$default_ctx =~ s/-//g;
}

# Data Source 
my $snort_type = $ossim_conf::ossim_data->{"ossim_type"}; # $ossim_conf::ossim_data->{"snort_type"};
my $siem_name  = "alienvault_siem";
my $snort_host = $ossim_conf::ossim_data->{"ossim_host"}; #$ossim_conf::ossim_data->{"snort_host"};
my $snort_port = $ossim_conf::ossim_data->{"ossim_port"}; #$ossim_conf::ossim_data->{"snort_port"};
my $snort_user = $ossim_conf::ossim_data->{"ossim_user"}; #$ossim_conf::ossim_data->{"snort_user"};
my $snort_pass = $ossim_conf::ossim_data->{"ossim_pass"}; #$ossim_conf::ossim_data->{"snort_pass"};

my $ossim_type = $ossim_conf::ossim_data->{"ossim_type"};
my $ossim_name = "alienvault";
my $ossim_host = $ossim_conf::ossim_data->{"ossim_host"};
my $ossim_port = $ossim_conf::ossim_data->{"ossim_port"};
my $ossim_user = $ossim_conf::ossim_data->{"ossim_user"};
my $ossim_pass = $ossim_conf::ossim_data->{"ossim_pass"};

# Get first count
my $snort_dsn = "dbi:" . $snort_type . ":" . $snort_name . ":" . $snort_host . ":" . $snort_port;
my $alien_dsn = "dbi:" . $ossim_type . ":" . $ossim_name . ":" . $ossim_host . ":" . $ossim_port;
my $alien_siem_dsn = "dbi:" . $snort_type . ":" . $siem_name . ":" . $snort_host . ":" . $snort_port;
my $snort_conn = DBI->connect($snort_dsn, $snort_user, $snort_pass, { PrintError => 0 }) or die "Can't connect to database '$snort_name'\n";
my $query = "SELECT count(*) AS total FROM acid_event";
my $stm = $snort_conn->prepare($query);
$stm->execute();
$row = $stm->fetchrow_hashref;
$count = $row->{total};
$stm->finish();
$snort_conn->disconnect();

print "* Found $count forensic events into snort database.\n";

# Get sensor sids hash
my %sensor_uuids = ();
my $alien_conn = DBI->connect($alien_dsn, $ossim_user, $ossim_pass) or die "Can't connect to Database\n";
my $query = "SELECT HEX(id) AS id, INET6_NTOA(ip) AS ip FROM alienvault.sensor";
my $stm = $alien_conn->prepare($query);
$stm->execute();
while (my $row = $stm->fetchrow_hashref) {
	$sensor_uuids{$row->{ip}} = $row->{id};
}
$stm->finish();
$alien_conn->disconnect();

# SNORT.SENSOR load
my $snort_conn = DBI->connect($snort_dsn, $snort_user, $snort_pass) or die "Can't connect to Database\n";
my $query = "SELECT DISTINCT sensor, sid, hostname FROM ".$snort_name.".sensor";
my $stm = $snort_conn->prepare($query);
$stm->execute();
my %sids = ();
my %snort_sensors = ();
my $sensors_inserted = 0;
while (my $row = $stm->fetchrow_hashref) {
	# Sensor exists
	my $ip = $row->{sensor};
	if ($ip !~ /\d+\.\d+\.\d+\.\d+/) {
    	$ip = $row->{hostname};
    	$ip =~ s/-.*//;
	}
	if ($ip =~ /\d+\.\d+\.\d+\.\d+/) {
    	if (defined $sensor_uuids{$ip}) {
    		$sids{$row->{sid}}  = $sensor_uuids{$ip};
    		$snort_sensors{$ip} = $sensor_uuids{$ip};
    	# Sensor doesn't exist -> Create it
    	} else {
    		my $new_id = generate_uuid();
    		$query = "INSERT INTO alienvault.sensor VALUES (UNHEX('$new_id'), '(null)', INET6_ATON('$ip'), 5, 40002, 0,'', 2)";
    		my $stm2 = $snort_conn->prepare($query);
    		$stm2->execute();
    		$stm2->finish();
    		$query = "INSERT INTO alienvault_siem.device (device_ip, sensor_id) VALUES (INET6_ATON('$ip'), UNHEX('$new_id'))";
    		my $stm3 = $snort_conn->prepare($query);
    		$stm3->execute();
    		$stm3->finish();
    		$sids{$row->{sid}} = $new_id;
    		$sensor_uuids{$ip} = $new_id;
    		$snort_snsors{$ip} = $new_id;
    		$sensors_inserted++;
    	}	
	}
}
$stm->finish();
print "* $sensors_inserted new sensors inserted.\n";

# ALIENVAULT.DEVICE load
my $query = "SELECT DISTINCT id, HEX(sensor_id) AS sensor_id FROM alienvault_siem.device";
my $stm = $snort_conn->prepare($query);
$stm->execute();
my %device = ();
my $default_device = 0;
while (my $row = $stm->fetchrow_hashref) {
	$device{$row->{sensor_id}} = $row->{id};
}
$stm->finish();

# ALIENVAULT.DEVICE update (insert if doesn't exist yet)
foreach $ip (keys %snort_sensors) {
	if (!defined $device{$sensor_uuids{$ip}}) {
		$query = "INSERT INTO alienvault_siem.device (device_ip, sensor_id) VALUES (INET6_ATON('$ip'), UNHEX('".$sensor_uuids{$ip}."'))";
		my $stm3 = $snort_conn->prepare($query);
		$stm3->execute();
		$stm3->finish();
	}
}

# ALIENVAULT.HOSTS load
my $query = "SELECT DISTINCT HEX(host_id) AS id, INET6_NTOA(ip) AS ip FROM alienvault.host_ip";
my $stm = $snort_conn->prepare($query);
$stm->execute();
my %host = ();
while (my $row = $stm->fetchrow_hashref) {
	$host{$row->{ip}} = $row->{id};
}
$stm->finish();

# DEFAULT CTX
if ($default_ctx !~ /^[A-Z0-9]{32}$/) {
	my $query = "select value from alienvault.config where conf like 'default_context_id'";
	my $stm = $snort_conn->prepare($query);
	$stm->execute();
	if (my $row = $stm->fetchrow_hashref) {
		$default_ctx = uc($row->{value});
		$default_ctx =~ s/-//g;
	}
	$stm->finish();
}

# Update bg_tasks variable
$snort_conn->do("insert into alienvault.config values ('bg_tasks','1') on duplicate key update value=value+1");

$snort_conn->disconnect();

# READ FROM ACID_EVENT
for ($cc=0;$cc<$count;$cc+=10000) {
    print "* Importing events ".($cc*100/$count)."%...\n";
    my $alien_conn = DBI->connect($alien_siem_dsn, $ossim_user, $ossim_pass) or die "Can't connect to Database\n";
    my $query = "SELECT *,INET_NTOA(ip_src) as ip_src,INET_NTOA(ip_dst) as ip_dst FROM ".$snort_name.".acid_event LIMIT $cc,10000";
    my $stm = $alien_conn->prepare($query);
    $stm->execute();
    while (my $row = $stm->fetchrow_hashref) {
    	$row->{src_hostname} =~ s/'/\\'/g;
	    $row->{dst_hostname} =~ s/'/\\'/g;
	    $row->{src_username} =~ s/'/\\'/g;
        $row->{dst_username} =~ s/'/\\'/g;
        $row->{src_domain} =~ s/'/\\'/g;
        $row->{dst_domain} =~ s/'/\\'/g;
        $row->{src_mac} =~ s/\(null\)//i;
        $row->{dst_mac} =~ s/\(null\)/\\'/i;
    	my $sid = $row->{sid};
    	my $cid = $row->{cid};
    	# device
    	my $device = $device{$sids{$row->{sid}}};
    	$device = $default_device if (!defined($device));
    	#
    	my $new_id = generate_uuid();
    	# ACID_EVENT
    	my $acid_event = "INSERT INTO acid_event VALUES (UNHEX('$new_id'),$device,UNHEX('$default_ctx'),'".$row->{timestamp}."',INET6_ATON('".$row->{ip_src}."'),INET6_ATON('".$row->{ip_dst}."'),".$row->{ip_proto}.",".$row->{layer4_sport}.",".$row->{layer4_dport}.",".$row->{ossim_priority}.",".$row->{ossim_reliability}.",".$row->{ossim_asset_src}.",".$row->{ossim_asset_dst}.",".$row->{ossim_risk_c}.",".$row->{ossim_risk_a}.",".$row->{plugin_id}.",".$row->{plugin_sid}.",'".$row->{tzone}."',".$row->{ossim_correlation}.",'".$row->{src_hostname}."','".$row->{dst_hostname}."','".$row->{src_mac}."','".$row->{dst_mac}."',UNHEX('".$host{$row->{ip_src}}."'),UNHEX('".$host{$row->{ip_dst}}."'),'','')";
    	# IDM_DATA
    	my $idm_data_src = "";
    	if ($row->{src_username} ne '' || $row->{src_domain} ne '') {
        	$idm_data_src = "INSERT INTO idm_data VALUES (UNHEX('$new_id'),'".$row->{src_username}."','".$row->{src_domain}."',1)";
    	}
    	my $idm_data_dst = "";
    	if ($row->{dst_username} ne '' || $row->{dst_domain} ne '') {
        	$idm_data_dst = "INSERT INTO idm_data VALUES (UNHEX('$new_id'),'".$row->{dst_username}."','".$row->{dst_domain}."',0)";
    	}
    	# REPUTATION_DATA
    	my $rep_data = "";
        my $query = "SELECT *,INET_NTOA(rep_ip_src) as rep_ip_src,INET_NTOA(rep_ip_dst) as rep_ip_dst FROM ".$snort_name.".idm_data WHERE sid=$sid AND cid=$cid";
        my $stm1 = $alien_conn->prepare($query);
        $stm1->execute();
        if (my $row1 = $stm1->fetchrow_hashref) {
        	$rep_data = "INSERT INTO reputation_data VALUES (UNHEX('$new_id'),INET6_ATON('".$row1->{rep_ip_src}."'),INET6_ATON('".$row1->{rep_ip_dst}."'),'".$row1->{rep_prio_src}."','".$row1->{rep_prio_dst}."','".$row1->{rep_rel_src}."','".$row1->{rep_rel_dst}."','".$row1->{rep_act_src}."','".$row1->{rep_act_dst}."')";            
        }    	
    	$stm1->finish();
    	# EXTRA_DATA
    	my $extra_data = "";
        my $query = "SELECT * FROM ".$snort_name.".extra_data WHERE sid=$sid AND cid=$cid";
        my $stm2 = $alien_conn->prepare($query);
        $stm2->execute();
        if (my $row2 = $stm2->fetchrow_hashref) {
            $row2->{filename} =~ s/'/\\'/g;
            $row2->{username} =~ s/'/\\'/g;
            $row2->{password} =~ s/'/\\'/g;
            $row2->{userdata1} =~ s/'/\\'/g;
            $row2->{userdata2} =~ s/'/\\'/g;
            $row2->{userdata3} =~ s/'/\\'/g;
            $row2->{userdata4} =~ s/'/\\'/g;
            $row2->{userdata5} =~ s/'/\\'/g;
            $row2->{userdata6} =~ s/'/\\'/g;
            $row2->{userdata7} =~ s/'/\\'/g;
            $row2->{userdata8} =~ s/'/\\'/g;
            $row2->{userdata9} =~ s/'/\\'/g;
            $row2->{data_payload} =~ s/'/\\'/g;
        	$extra_data = "INSERT INTO extra_data VALUES (UNHEX('$new_id'),'".$row2->{filename}."','".$row2->{username}."','".$row2->{password}."','".$row2->{userdata1}."','".$row2->{userdata2}."','".$row2->{userdata3}."','".$row2->{userdata4}."','".$row2->{userdata5}."','".$row2->{userdata6}."','".$row2->{userdata7}."','".$row2->{userdata8}."','".$row2->{userdata9}."','".$row2->{data_payload}."',NULL)";
        }    	
    	$stm2->finish();
    	
    	#
    	$alien_conn->do($acid_event);
    	$alien_conn->do($idm_data_src) if ($idm_data_src ne "");
    	$alien_conn->do($idm_data_dst) if ($idm_data_dst ne "");
    	$alien_conn->do($rep_data) if ($rep_data ne "");
    	$alien_conn->do($extra_data) if ($extra_data ne "");
    }
    $stm->finish();
    $alien_conn->disconnect();    
}

# Update bg_tasks variable
my $conn = DBI->connect($alien_siem_dsn, $ossim_user, $ossim_pass) or die "Can't connect to Database\n";
$conn->do("update alienvault.config set value=value-1 where conf='bg_tasks' and value>0");
$conn->disconnect();

print "* Importing events 100%...\n";
print "ALL DONE\n";
