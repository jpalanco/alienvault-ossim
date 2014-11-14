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

my $usage = << "USAGE";
Usage:
------

$0 [sql file to read previous id's from] 

###
# (If you specify "1", it will start from scratch)
###
USAGE

if(!$ARGV[0]){
print $usage;
exit;
}



use ossim_conf;
use DBI;

$| = 1;

my $dsn = "dbi:mysql:".$ossim_conf::ossim_data->{"ossim_base"}.":".$ossim_conf::ossim_data->{"ossim_host"}.":".$ossim_conf::ossim_data->{"ossim_port"};
my $dbh = DBI->connect($dsn, $ossim_conf::ossim_data->{"ossim_user"},
$ossim_conf::ossim_data->{"ossim_pass"})
    or die "Can't connect to DBI\n";

$netscreen_id = 1595; 
$ns_url = "https://services.netscreen.com/restricted/sigupdates/nsm-updates/HTML/index.html";

my $last_sid = 1;

my @plugin_rel_db = ();
my %plugin_rel_hash = ();
my %plugin_id_hash = ();

my $plugin_cfg = "";
my $plugin_sql = "";

$plugin_sql .= << "END";
-- Netscreen NSM IDP
-- plugin_id: $netscreen_id;
DELETE FROM plugin WHERE id = "$netscreen_id";
DELETE FROM plugin_sid where plugin_id = "$netscreen_id";

INSERT INTO plugin (id, type, name, description) VALUES ($netscreen_id, 1, 'netscreen-nsm-idp', 'Netscreen NSM IDP');
END


$plugin_cfg .= << "END";
;; netscreen-nsm (IDP)
;; plugin_id: $netscreen_id
;; 
;; Log format tested with NSM v2008.2r2a. Thanks Alex & Christopher.

[DEFAULT]
plugin_id=$netscreen_id

[config]
type=detector
enable=yes

source=log
location=/var/log/netscreen.log
create_file=true

process=
start=no
stop=no
startup=
shutdown=

END

print "Fetching updated NSM listing\n";

open(INPUT,"wget -qO- --no-check-certificate $ns_url|");
#open(INPUT,"<ns.txt");

while(<INPUT>){
#<a href="WORM%3ASLAMMER:INFECT-ATTEMPT.html" class="text">WORM:SLAMMER:INFECT-ATTEMPT</a> - WORM: SQLSlammer Worm Infection Attempt<br />
if(/a href="[^"]+\.html" class="text">([^<]+)\S+\s+-\s+(.*)<br\s*\/>/){
$plugin_rel_hash{"Netscreen IDP: $2"} = $1;
}

}

close INPUT;


######### Start old sql code
#my $query = "SELECT * from plugin_sid where plugin_id = $netscreen_id;";
#my $sth = $dbh->prepare($query);
#$sth->execute();
#my $row;
#while($row = $sth->fetchrow_hashref){
##print "$row->{name} = $row->{sid}\n";
#$plugin_rel_db{$row->{name}} = $row->{sid};
#if($row->{sid} > $last_sid){
#$last_sid = $row->{sid};
#}
#}
######### End old sql code

######### Start new code
if($ARGV[0] ne "1"){
open(INSQL, "<$ARGV[0]");
while(<INSQL>){
#INSERT IGNORE INTO plugin_sid(plugin_id, sid, category_id, class_id, priority, reliability, name) VALUES (1595, 19, NULL, NULL, 1, 5, 'Netscreen IDP: TROJAN: BackOrifice Connection');
if(/INSERT IGNORE INTO plugin_sid.*VALUES\s+\([^,]+,([^,]+),[^,]+,[^,]+,[^,]+,[^,]+,\s*'(.*)'\);/){
$plugin_rel_db{$2} = $1;
if($1 != 99999){
if($1 > $last_sid){
$last_sid = $1;
}
}
}
}
}
close INSQL;
######### End new code



print "Writing PY file\n";
open (PY, ">netscreen-nsm.py");

print PY "def netscreen_idp_sid(message):\n";
print PY "    translation = {\n";


if(keys %plugin_rel_hash){
foreach $key (keys %plugin_rel_hash){
$query = "INSERT IGNORE INTO plugin_sid(plugin_id, sid, category_id, class_id, priority, reliability, name) VALUES ";
#$plugin_rel_hash{$key} =~ s/'/''/; 
$key =~ s/\\//gs;
$key =~ s/'/\\'/gs;
$key =~ s/"/\\"/gs;
if($plugin_rel_db{$key} < 1){
$sid = $last_sid++;
if($sid == 0){$sid = 1;}
$plugin_rel_db{$key} = $sid;
print "New entry. $key:$plugin_rel_db{$key}\n";
} else {
$sid = $plugin_rel_db{$key};
}

$query .= "($netscreen_id, $sid, NULL, NULL, 1, 5, '$key');";
$plugin_sql .= "$query\n";


if(exists($plugin_rel_hash{$key})){
$translate = $plugin_rel_hash{$key};
#$translate =~ s/-/%-/gs;
#$translate =~ s/:/%:/gs;
#$plugin_cfg .= "%" . $translate . "=$plugin_rel_db{$key}\n";
print PY "        '$translate': '$plugin_rel_db{$key}',\n";
}
}
}

print PY "    }\n";
print PY "    if translation.has_key(message):\n";
print PY "        return translation[message]\n";

print PY "    # missing sid\n";
print PY "    return '99999'\n\n\n\n";

close PY;

#$plugin_cfg .= "_DEFAULT_=99999\n";
$plugin_sql .= "INSERT IGNORE INTO plugin_sid(plugin_id, sid, category_id, class_id, priority, reliability, name) VALUES (1595, 99999, NULL, NULL, 1, 5, 'Netscreen IDP: Unknown event, please check the payload for more information.');\n";


$plugin_cfg .= << "END";

[netscreen-manager-event]
# This pattern is for syslog output from the Netscreen Manager,
# NOT netscreen devices. They are different formats.
#
# NSM uses a CSV output with the following format
#
# Log Day Id, Log Record Id, Time Received (UTC), Time Generated (UTC),
# Device Domain, Device Domain Version, Device Name, Category, Sub-Category,
# Src Zone, Src Intf, Src Addr, Src Port, NAT Src Addr, NAT Src Port, Dst Zone,
# Dst Intf, Dst Addr, Dst Port, NAT Dst Addr, NAT Dst Port, Protocol,
# Policy Domain, Policy Domain Version, Policy, Rulebase, Rule Number, Action,
# Severity, Is Alert, Details, User, App, URI, Elapsed Secs, Bytes In,
# Bytes Out, Bytes Total, Packets In, Packets Out, Packets Total, Repeat Count,
# Has Packet Data, Var Data Enum

event_type=event
regexp=\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*),\\s*([^,]*)
date={normalize_date(\$3)}
plugin_sid={netscreen_idp_sid(\$10)}
sensor={\$8}
src_ip={\$13}
src_port={\$14}
dst_ip={\$19}
dst_port={\$20}
interface={\$12}
protocol={\$23}
username={\$31}
userdata1={\$29}
userdata2={\$8}
userdata3={\$7}
END

open(PLUGIN_SQL,">netscreen-nsm.sql");
open(PLUGIN_CFG,">netscreen-nsm.cfg");
print "Writing SQL file\n";
print PLUGIN_SQL "$plugin_sql";
print "Writing Cfg file\n";
print PLUGIN_CFG "$plugin_cfg";
close PLUGIN_SQL;
close PLUGIN_CFG;
print "Done. Check:\n";
print "\tnetscreen-nsm.cfg\n\tnetscreen-nsm.sql\n\tnetscreen-nsm.py\n";
