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

$numproc = &num_processes;
if ($numproc>1) {
    print "$0 already running, exit.\n";
    exit(0);
}
#
# KISMET IMPORT
# Debian packages libclass-methodmaker-perl 
#
use DBI;
use Date::Manip;
local $ENV{XML_SIMPLE_PREFERRED_PARSER} = "XML::Parser";
use XML::Simple;
use Time::HiRes;

#skip ssids with clients = 0 and ft == lt
$filter_method_one = 0; # client=0 and firstdate == lastdate
$filter_method_two = 1; # client mac == ap mac
$filter_ssid_characters = 0;
$enable_generate_stats = 1;
# 1 for debuging info
$debug = 0;

print "Starting at ";
system("date");

$max_processes = 16; # max parallel parser threads
$maindir = "/var/ossim/kismet/"; # kismet work dir
$syslog = "/var/log/kismet.log";
$workdir = "$maindir/work";
$parseddir = "$maindir/parsed";
#$dbdir = "$maindir/db";
$sensor = 0;
my %sensors = ();
#0die "Needs xml input directory with files like kismet_XXX.XXX.XXX.XXX_YYYYMMDD.xml\n" if(!$maindir);
die "$maindir is not a directory\n" if (!-d $maindir);

my $t1 = Time::HiRes::time();
my $dbh = db_connect();
my %ip_mac = ();
my %mac_ip = ();
get_ips_macs($dbh);
my %aps_macs = ();
my %clients_macs = ();
get_aps_macs_and_clients($dbh);
# sensors => solved by rsyslog
#@log_files = `ls -1t "$workdir/"*.log 2> /dev/null`;
#foreach $file (@log_files) {
#    $file =~ s/\n|\r|\t//g;
#    if ($file =~ /(\d+\.\d+\.\d+\.\d+).*/) {
#        $sensor = $1;
#        print "Syslog:kismet.log => $file\n";
#        system("cat '$file' >> '$syslog'");
#        system("mkdir -p '$parseddir/$sensor';mv '$file' '$parseddir/$sensor'");
#        $sql = qq{ update alienvault.wireless_sensors set last_scraped = now() where sensor in (select name from alienvault.sensor where ip = '$sensor') };
#        $sth_selm=$dbh->prepare( $sql );
#        $sth_selm->execute;
#        $sth_selm->finish;
#    }
#}
$dbh->disconnect;

$file = "";
@files = `ls -1t $workdir/*/*.xml 2> /dev/null`;
foreach $file (@files) {
    $file =~ s/\n|\r|\t//g;
    if ($file =~ /(\d+\.\d+\.\d+\.\d+).*/) {
        $sensor = $1;
        $sensors{$sensor}++;
        # fork
        $numproc = &num_processes;
        while ($numproc > $max_processes) {
            wait;
            $numproc = &num_processes;
        }
        my $pid=fork();
        if ($pid==0) { # child
            my $dbh = db_connect();
            my $t0 = Time::HiRes::time();
            #
            #insert_sensor($dbh,$sensor);
            eval { # try
                parse_xml($dbh,$file);
                1;
            } or do { # catch
                print "ERROR: $@";
            };
            system("mkdir -p '$parseddir/$sensor';mv '$file' '$parseddir/$sensor'");
            #
            my $elapsed = Time::HiRes::time() - $t0;
            print "$file parsed in $elapsed s.\n";
            $dbh->disconnect;
            exit(0);
        } else { # parent
            print "Processing $file (pid:$pid)\n";
        }
    } else {
        print "Skiping $file: incorrect name format XXX.XXX.XXX.XXX_YYYYMMDD-N.xml\n";
    }
}

# waiting childs dies
$numproc = &num_processes;
while ($numproc > 1) {
    $numproc--;
    print "Waiting childs ($numproc) ...\n";
    wait;
    $numproc = &num_processes;
}

if ($enable_generate_stats) {
    # Generate stats
    my $dbh = db_connect();
    foreach $sensor (keys %sensors) {
        print "Generating stats for $sensor...\n";
        gen_stats($dbh,$sensor);
    }
    $dbh->disconnect;
}
# finishing
my $elapsed = Time::HiRes::time() - $t1;
print "Done in $elapsed s.\n";
print (($file eq "") ? "Nothing to do.\n" : "Done.\n");
#
#
sub parse_xml {
    my $dbh = shift;
    my $file = shift;
    my @items=();
    my $xml = XMLin($file);
    if (ref($xml->{'wireless-network'}) eq 'ARRAY') {
      @items = @{$xml->{'wireless-network'}};
    } else {
      push(@items,$xml->{'wireless-network'});
    }
    foreach my $net (@items) {
        $ssid = $net->{'SSID'};
        #$ssid = "<no ssid>" if ($ssid eq "");
        next if ($ssid =~ /\\/ || $ssid eq "");
        $ssid1 = quotemeta $ssid;
        if ($filter_ssid_characters) {
            next if ($ssid =~ /\[/);
            next if ($ssid =~ /\]/);
            next if ($ssid =~ /\(/);
            next if ($ssid =~ /\`/);
            next if ($ssid =~ /\)/);
            next if ($ssid =~ /\*/);
            next if ($ssid =~ /\,/);
            next if ($ssid =~ /\}/);
            next if ($ssid =~ /\(/);
            next if ($ssid =~ /\{/);
            next if ($ssid =~ /\|/);
            next if ($ssid =~ /eCO`p/);
        }
        print "SSID: $ssid1\n" if ($debug);
        $nettype = $net->{'type'};
        $mac = $net->{'BSSID'};
        $info = "";
        $channel = $net->{'channel'};
        $cloaked = ($net->{'cloaked'} eq 'false') ? "No" : "Yes";
        if (ref($net->{'encryption'}) eq 'ARRAY') {
            $encryption = join(",",@{$net->{'encryption'}});
        } else {
            $encryption = $net->{'encryption'};
        }
        $decrypted = "";
        $maxrate = $net->{'maxrate'};
        $maxseenrate = $net->{'maxseenrate'};
        $beacon = $net->{'packets'}->{'beacon'};
        $llc = $net->{'packets'}->{'LLC'};
        $data = $net->{'packets'}->{'data'};
        $crypt = $net->{'packets'}->{'crypt'};
        $weak = $net->{'packets'}->{'weak'};
        $dupeiv = $net->{'packets'}->{'dupeiv'};
        $total = $net->{'packets'}->{'total'};
        if (ref($net->{'carrier'}) eq 'ARRAY') {
            $carrier = join(",",@{$net->{'carrier'}});
        } else {
            $carrier = $net->{'carrier'};
        }
        if (ref($net->{'encoding'}) eq 'ARRAY') {
            $encoding = join(",",@{$net->{'encoding'}});
        } else {
            $encoding = $net->{'encoding'};
        }
        $datasize = $net->{'datasize'};
        $firsttime = $net->{'first-time'};
        $lasttime = $net->{'last-time'};
        $bestquality = $bestsignal = $bestnoise = "";
        $gpsminlat = $net->{'gps-info'}->{'min-lat'};
        $gpsminlon = $net->{'gps-info'}->{'min-lon'};
        $gpsminalt = $net->{'gps-info'}->{'min-alt'};
        $gpsminspd = $net->{'gps-info'}->{'min-spd'};
        $gpsmaxlat = $net->{'gps-info'}->{'max-lat'};
        $gpsmaxlon = $net->{'gps-info'}->{'max-lon'};
        $gpsmaxalt = $net->{'gps-info'}->{'max-alt'};
        $gpsmaxspd = $net->{'gps-info'}->{'max-spd'};
        $gpbestlat = $gpsbestlon = $gpsbestalt = "";
        $iptype = $net->{'ip-address'}->{'type'};
        $ip = $net->{'ip-address'}->{'ip-range'};
        # filter clients=0 and firsttime==lasttime
        if ($filter_method_one) {
            $ft = ParseDate($firsttime); $ft =~ s/\://g;
            $lt = ParseDate($lasttime); $lt =~ s/\://g;
            if (ref($net->{'wireless-client'}) ne 'ARRAY' && $ft==$lt) {
                print "Skiping $ssid => clients=0 and $ft == $lt\n" if ($debug);
                next;
            }
        }
        $firsttime = ParseDate($firsttime);
        $lasttime = ParseDate($lasttime);
        $firsttime =~ s/^(....)(..)(..)/$1-$2-$3 /;
        $lasttime =~ s/^(....)(..)(..)/$1-$2-$3 /;
        # uopdate duplicate mac 
        if ($aps_macs{$sensor}{$mac}{$ssid}) {
            $sql = qq{ UPDATE wireless_aps SET channel = '$channel', cloaked = '$cloaked', encryption = '$encryption', llc = GREATEST(llc, $llc), data = GREATEST(data, $data), carrier = '$carrier', lasttime = '$lasttime', datasize = GREATEST(datasize, $datasize) WHERE mac = '$mac' and ssid = '$ssid1' and sensor = inet6_pton('$sensor') };
            $sth_selm=$dbh->prepare( $sql );
            $sth_selm->execute;
            $sth_selm->finish;
            print "Updated existing AP, $ssid1, $sensor\n" if ($debug);

        } else {

            $aps_macs{$sensor}{$mac}{$ssid}++;

            # wireless_networks
            print "Insert network $ssid\n" if ($debug);
            $sql_update = qq{ INSERT IGNORE INTO wireless_networks VALUES ('$ssid1',inet6_pton('$sensor'),0,0,'','','','','','Un-Trusted','','') };
            $sth_update = $dbh->prepare( $sql_update );
            $sth_update->execute;
            # wireless_aps
            $sql_update = qq{ INSERT IGNORE INTO wireless_aps VALUES ('$mac','$ssid1',inet6_pton('$sensor'),'$nettype','$info','$channel','$cloaked','$encryption','$decrypted','$maxrate','$maxseenrate','$beacon','$llc','$data','$crypt','$weak','$dupeiv','$total','$carrier','$encoding','$firsttime','$lasttime','$bestquality','$bestsignal','$bestnoise','$gpsminlat','$gpsminlon','$gpsminalt','$gpsminspd','$gpsmaxlat','$gpsmaxlon','$gpsmaxalt','$gpsmaxspd','$gpsbestlat','$gpsbestlon','$gpsbestalt','$datasize','$iptype',inet6_pton('$ip'),'') };
            $sth_update = $dbh->prepare( $sql_update );
            $sth_update->execute;
            $sth_update->finish;
            print "Inserted/updated $mac AP for sensor:$sensor\n" if ($debug);
        }
        #
        # clients
        if (ref($net->{'wireless-client'}) eq 'ARRAY') {
            foreach $cl (@{$net->{'wireless-client'}}) {
                $client_mac = $cl->{'client-mac'};
                if ($filter_method_two && ($mac eq $client_mac)) {
                    print "Skipping client where client_mac == ap_mac, $mac == $client_mac" if ($debug);
                    next;
                }
                $ctype = $cl->{'type'};
                $channel = $cl->{'client-channel'};
                if (ref($cl->{'client-encryption'}) eq 'ARRAY') {
                    $encryption = join(",",@{$cl->{'client-encryption'}});
                } else {
                    $encryption = $cl->{'client-encryption'};
                }
                $plugin_sid = 0;
                $maxrate = $cl->{'client-maxrate'};
                $maxseenrate = $cl->{'client-maxseenrate'};
                $llc = $cl->{'client-packets'}->{'client-LLC'};
                $data = $cl->{'client-packets'}->{'client-data'};
                $crypt = $cl->{'client-packets'}->{'client-crypt'};
                $weak = $cl->{'client-packets'}->{'client-weak'};
                $dupeiv = $cl->{'client-packets'}->{'client-dupeiv'};
                $total = $cl->{'client-packets'}->{'client-total'};
                $encoding = ($cl->{'wep'} eq "false") ? "No" : "Yes";
                $datasize = $cl->{'client-datasize'};
                $firsttime = $cl->{'first-time'};
                $lasttime = $cl->{'last-time'};
                $gpsminlat = $cl->{'client-gps-info'}->{'client-min-lat'};
                $gpsminlon = $cl->{'client-gps-info'}->{'client-min-lon'};
                $gpsminalt = $cl->{'client-gps-info'}->{'client-min-alt'};
                $gpsminspd = $cl->{'client-gps-info'}->{'client-min-spd'};
                $gpsmaxlat = $cl->{'client-gps-info'}->{'client-max-lat'};
                $gpsmaxlon = $cl->{'client-gps-info'}->{'client-max-lon'};
                $gpsmaxalt = $cl->{'client-gps-info'}->{'client-max-alt'};
                $gpsmaxspd = $cl->{'client-gps-info'}->{'client-max-spd'};
                $iptype = $net->{'client-ip-address'}->{'client-type'};
                $ip = $net->{'client-ip-address'}->{'client-ip-range'};
                # resolve ip from mac
                $ip=$mac_ip{$client_mac} if ($ip eq "" && $client_mac ne "");
                # resolve mac from ip
                $client_mac=$ip_mac{$ip} if ($ip ne "" && $client_mac eq "");

                $firsttime = ParseDate($firsttime); $firsttime =~ s/^(....)(..)(..)/$1-$2-$3 /;
                $lasttime = ParseDate($lasttime); $lasttime =~ s/^(....)(..)(..)/$1-$2-$3 /;
                if ($clients_macs{$sensor}{$client_mac}{$ssid}) {
                    $sql = qq{ UPDATE wireless_clients SET ssid = '$ssid1', lasttime = '$lasttime', data = GREATEST(data,$data), datasize = GREATEST(datasize,$datasize) WHERE client_mac = '$client_mac' and sensor = '$sensor' and ssid = '$ssid1' };
                    $sth_selm=$dbh->prepare( $sql );
                    $sth_selm->execute;
                    $sth_selm->finish;
                    print "Updated existing client, $ssid1, $client_mac, $sensor\n" if ($debug);
                } else {
                
                    $clients_macs{$sensor}{$client_mac}{$ssid}++;
                    # wireless_clients
                    $sql_update = qq{ INSERT IGNORE INTO wireless_clients VALUES ('$client_mac','$mac','$ssid1',inet6_pton('$sensor'),'$plugin_sid','$channel','$encryption','$maxrate','$maxseenrate','$llc','$data','$crypt','$weak','$dupeiv','$total','$ctype','$encoding','$firsttime','$lasttime','$gpsminlat','$gpsminlon','$gpsminalt','$gpsminspd','$gpsmaxlat','$gpsmaxlon','$gpsmaxalt','$gpsmaxspd','$datasize','$iptype',inet6_pton('$ip'),'') };
                    $sth_update = $dbh->prepare( $sql_update );
                    $sth_update->execute;
                    $sth_update->finish;
                    print "Inserted/updated $client_mac for $mac AP\n" if ($debug);
                }
            }
            # clients data from extra_data, Kismet plugin 
            $sql = qq{ select device.id from sensor,sensor_properties,alienvault_siem.device where sensor.id=sensor_properties.sensor_id and device.sensor_id=sensor.id and sensor_properties.has_kismet=1 and sensor.ip=inet6_pton('$sensor') };
            $sth_sel=$dbh->prepare( $sql );
            $sth_sel->execute;
            if ( my ( $sid )=$sth_sel->fetchrow_array ) {
                $sql = "select distinct o.plugin_sid,e.userdata2,e.userdata3,e.userdata4 from sensor, alienvault_siem.extra_data e, alienvault_siem.acid_event o where o.id=e.event_id and o.plugin_id=1596 and o.plugin_sid not in (1,2) and o.device_id=$sid and e.userdata2='$mac'";
                $sth_sel=$dbh->prepare( $sql );
                $sth_sel->execute;
                while ( my ( $plugin_sid,$mac_ssid,$client_mac,$ip )=$sth_sel->fetchrow_array ) {
                    # resolve ip from mac
                    $ip=$mac_ip{$client_mac} if ($ip eq "" && $client_mac ne "");
                    # resolve mac from ip
                    $client_mac=$ip_mac{$ip} if ($ip ne "" && $client_mac eq "");
                    $ipc = ($ip ne "") ? ",ip='$ip'" : "";
                    $sql_update = qq{ INSERT INTO wireless_clients VALUES ('$client_mac','$mac','$ssid1',inet6_pton('$sensor'),'$plugin_sid','','','','','','','','','','','','','','','','','','','','','','','','','$ip','') ON DUPLICATE KEY UPDATE plugin_sid='$plugin_sid' $ipc };
                    $sth_update = $dbh->prepare( $sql_update );
                    $sth_update->execute;
                    print "Inserted/updated $client_mac for $mac AP from extra_data\n" if ($debug);
                }
            }
            $sth_sel->finish;
        }
    }
}
#
# accummulate stats
sub gen_stats {
    my $dbh = shift;
    my $sensor = shift;
    # update accumulate info
    $sql = qq{ select ssid from wireless_networks where sensor=inet6_pton('$sensor') };
    $sth_sel=$dbh->prepare( $sql );
    $sth_sel->execute;
    while ( my ($ssid)=$sth_sel->fetchrow_array ) {
        $qssid = quotemeta $ssid;
        # aps
        @addrs = ();
        $sql = qq{ select distinct mac from wireless_aps where ssid='$qssid' and sensor=inet6_pton('$sensor') and nettype='infrastructure' };
        $aps=$dbh->prepare( $sql );
        $aps->execute;
        while ( my ($mac)=$aps->fetchrow_array ) {
            push(@addrs,$mac);
        }
        $num_aps = @addrs;
        $macs = join(",",@addrs);
        $aps->finish;
        # clients
        $num_clients = 0;
        $sql = qq{ select count(distinct client_mac) from wireless_clients where ssid='$qssid' and sensor=inet6_pton('$sensor') };
        $cls=$dbh->prepare( $sql );
        $cls->execute;
        $num_clients=$cls->fetchrow_array;
        $cls->finish;
        # encryption
        %encr = ();
        $sql = qq{ select distinct encryption from wireless_aps where ssid='$qssid' and sensor=inet6_pton('$sensor') };
        $enc=$dbh->prepare( $sql );
        $enc->execute;
        while ( my ($enctype)=$enc->fetchrow_array ) {
            my @types = split(",",$enctype);
            foreach my $t (@types) { $encr{$t}++; }
        }
        $encryption = join(",",keys(%encr));
        $enc->finish;
        $encryption =~ s/^\,//;
        # cloaked
        %clk = ();
        $sql = qq{ select distinct cloaked from wireless_aps where ssid='$qssid' and sensor=inet6_pton('$sensor') };
        $clo=$dbh->prepare( $sql );
        $clo->execute;
        while ( my ($clotype)=$clo->fetchrow_array ) {
            my @types = split(",",$clotype);
            foreach my $t (@types) { $clk{$t}++; }
        }
        $cloaked = join(",",keys(%clk));
        $clo->finish;
        $cloaked =~ s/^\,//;
        # firsttime - lasttime
        $first  = $last = "";
        $sql = qq{ select min(firsttime),max(lasttime) from wireless_aps where ssid='$qssid' and sensor=inet6_pton('$sensor') };
        $tt=$dbh->prepare( $sql );
        $tt->execute;
        my ($first,$last)=$tt->fetchrow_array;
        $tt->finish;
        # update network data
        print "Updating $ssid: aps=$num_aps,clients=$num_clients,encryption='$encryption',cloaked='$cloaked',firsttime='$first',lasttime='$last',macs='$macs'\n" if ($debug);
        $sql = qq{ update wireless_networks set aps=$num_aps,clients=$num_clients,encryption='$encryption',cloaked='$cloaked',firsttime='$first',lasttime='$last',macs='$macs' where ssid='$qssid' and sensor=inet6_pton('$sensor') };
        $upt=$dbh->prepare( $sql );
        $upt->execute;
        $upt->finish;
    }
}
#
#
sub insert_sensor {
    my $dbh = shift;
    my $sensor = shift;
    # check if exists sensor-Kismet
    print "Checking $sensor-kismet\n" if ($debug);
    my $sid = 0;
    my $sql = qq{ select sid from alienvault_siem.sensor where hostname = '$sensor-Kismet'};
    my $sth_selm=$dbh->prepare( $sql );
    $sth_selm->execute;
    $sid=$sth_selm->fetchrow_array;
    $sth_selm->finish;
    if ($sid == 0) {
        # insert sensor-Kismet
        print "Inserting $sensor-Kismet\n" if ($debug);
        my $sql_update = qq{ insert into alienvault_siem.sensor (hostname,interface,encoding,last_cid) values ('$sensor-Kismet','eth0',2,0) on duplicate key update encoding=2 };
        my $sth_update = $dbh->prepare( $sql_update );
        $sth_update->execute;
        $sth_update->finish;
    }
    my $sname = "";
    $sql = qq{ select name from sensor where ip = '$sensor'};
    $sth_selm=$dbh->prepare( $sql );
    $sth_selm->execute;
    $sname=$sth_selm->fetchrow_array;
    $sth_selm->finish;
    if ($sname eq "") {
        # insert sensor
        print "Inserting ossim Sensor_$sensor\n" if ($debug);
        $sql_update = qq{ insert into sensor values ('Sensor_$sensor','$sensor',5,40002,0,'',0) };
        $sth_update = $dbh->prepare( $sql_update );
        $sth_update->execute;
        $sth_update->finish;
        $sql_update = qq{ insert ignore into sensor_properties values ('$sensor','',0,0,0,1) };
        $sth_update = $dbh->prepare( $sql_update );
        $sth_update->execute;
        $sth_update->finish;
        # by cybera
        $sname = "Sensor_$sensor";
    }
    $sql = qq{ select sensor from wireless_sensors where sensor = '$sname'};
    $sth_selm=$dbh->prepare( $sql );
    $sth_selm->execute;
    $sname= "";
    $sname=$sth_selm->fetchrow_array;
    $sth_selm->finish;

    if ($sname eq ""){
        # insert to alienvault.wireless_sensors
        print "Inserting wireless_sensors Sensor_$sensor" if ($debug);
       $sql_update = qq{ insert ignore into wireless_sensors values ('Sensor_$sensor','Default','','','',null,'','',0) };
       $sth_update = $dbh->prepare( $sql_update );
       $sth_update->execute;
       $sth_update->finish;
    }
}

# resolve ip from mac / mac from ip
sub get_ips_macs {
    my $dbh = shift;
    print "Loading macs from hosts\n" if ($debug);
    my $sql = qq{ select inet6_ntop(ip) as ip,hex(mac) as mac from host_ip where mac!='' };
    my $sth_selm=$dbh->prepare( $sql );
    $sth_selm->execute;
    while ( my ($ip,$mac)=$sth_selm->fetchrow_array ) {
        $mac =~ s/(..)(..)(..)(..)(..)(..)/$1:$2:$3:$4:$5:$6/i;
        $ip_mac{$ip} = $mac;
        $mac_ip{$mac} = $ip;
    }
    $sth_selm->finish;
}
# resolve ip from mac / mac from ip
sub get_aps_macs_and_clients {
    my $dbh = shift;
    print "Loading macs from wireless_aps\n" if ($debug);
    $sql = qq{ select distinct mac,ssid,inet6_ntop(sensor) from wireless_aps };
    my $sth_selm=$dbh->prepare( $sql );
    $sth_selm->execute;
    while ( my ($mac,$ssid,$sensor)=$sth_selm->fetchrow_array ) {
        $aps_macs{$sensor}{$mac}{$ssid}=1;
    }
    $sth_selm->finish;
    #
    print "Loading client_mac from wireless_clients\n" if ($debug);
    $sql = qq{ select distinct client_mac,inet6_ntop(sensor),ssid from wireless_clients };
    my $sth_selm=$dbh->prepare( $sql );
    $sth_selm->execute;
    while ( my ($client_mac,$sensor,$ssid)=$sth_selm->fetchrow_array ) {
        $clients_macs{$sensor}{$client_mac}{$ssid}++;
    }
    $sth_selm->finish;
}
# BD Connect
sub db_connect {
    # database connect
    my $dbhost = `grep ^ossim_host= /etc/ossim/framework/ossim.conf | cut -f 2 -d "="`; chomp($dbhost);
    $dbhost = "localhost" if ($dbhost eq "");
    my $dbuser = `grep ^ossim_user= /etc/ossim/framework/ossim.conf | cut -f 2 -d "="`; chomp($dbuser);
    my $dbpass = `grep ^ossim_pass= /etc/ossim/framework/ossim.conf | cut -f 2 -d "="`; chomp($dbpass);
    my $dbh = DBI->connect("DBI:mysql:alienvault:$dbhost", $dbuser,$dbpass, { PrintError => 0, RaiseError => 1, AutoCommit => 1 } ) or die("Failed to connect : $DBI::errstr\n");
    return $dbh;
}

# how many threads?
sub num_processes {
    my $count=0;
    while (!$count) {
        $count = `ps ax | grep kismet_import | grep -v grep | grep -v 'sh -c' | wc -l`;
        $count =~ s/\s*//g;
    }
    return $count;
}

