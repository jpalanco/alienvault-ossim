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

use ossim_conf;
use DBI;
use POSIX;

use strict;
use warnings;

local $ENV{XML_SIMPLE_PREFERRED_PARSER} = "XML::Parser";
use XML::Simple;

$| = 1;


my $nessus = $ossim_conf::ossim_data->{"nessus_path"};
my $nessus_user = $ossim_conf::ossim_data->{"nessus_user"};
my $nessus_pass = $ossim_conf::ossim_data->{"nessus_pass"};
my $nessus_host = $ossim_conf::ossim_data->{"nessus_host"};
my $nessus_port = $ossim_conf::ossim_data->{"nessus_port"};


my @plugin_rel_db = ();
my %plugin_rel_hash = ();
my %plugin_prio_hash = ();
my $index;
my $key;
my $id;

if ($nessus !~ /omp\s*$/) {
    open (PLUGINS, "$nessus -qxp $nessus_host $nessus_port $nessus_user $nessus_pass|");

    while(<PLUGINS>){
        my @plugin_data = split(/\|/,$_);
        if ($#plugin_data > 3) {
            $id = $plugin_data[0];
            my $temp_risk = $plugin_data[$#plugin_data];
            my $risk_level = 2;
            my $rel = $plugin_data[2];
            if ($id =~ /\./){
                my @tmp = split(/\./, $id);
                $id = $tmp[$#tmp];
            }
            $plugin_rel_hash{$id} = $rel;
            my $temp_plugin_id = $id;

                if ($temp_risk =~ /Risk factor :\W*(\w*)/) {
                my $risk=$1; 
                $risk =~ s/ \(.*|if.*//g; 
                $risk =~ s/ //g;        
                if ($risk eq "Verylow/none") { $risk_level = 1 }
                if ($risk eq "Low") { $risk_level = 1 }
                if ($risk eq "Low/Medium") { $risk_level = 2 }
                if ($risk eq "Medium/Low") { $risk_level = 2 }
                if ($risk eq "Medium") { $risk_level = 3 }
                if ($risk eq "Medium/High") { $risk_level = 3 }
                if ($risk eq "High/Medium") { $risk_level = 4 }
                if ($risk eq "High") { $risk_level = 4 }
                if ($risk eq "Veryhigh") { $risk_level = 5 }
                }

            $plugin_prio_hash{$temp_plugin_id} = $risk_level; 
        }
    }
}
else {
    
    my @items = ();
    my $omp_plugins = "/usr/share/ossim/www/vulnmeter/tmp/plugins.xml";
    
    unlink $omp_plugins if -e $omp_plugins;
    
    my $cmd = "$nessus -h $nessus_host -p $nessus_port -u $nessus_user -w $nessus_pass -iX \"<GET_NVTS details='1'/>\" > $omp_plugins";
    
    my $imp = system ( $cmd );
    
    if ( $imp != 0 ) { print("updateplugins: Failed Dump Plugins"); }
    
    my $xml = eval {XMLin($omp_plugins, keyattr => [])};
    
    unlink $omp_plugins if -e $omp_plugins; # delete plugins file
    
    if ($@ ne "") { print( "Cant' read XML $omp_plugins" ); }
    if ($xml->{'status'} !~ /20\d/) {
        my $status = $xml->{'status'};
        my $status_text = $xml->{'status_text'};
        print( "Error: status = $status, status_text = '$status_text' ($omp_plugins)", 2 );
    }

    if (ref($xml->{'nvt'}) eq 'ARRAY') {
        @items = @{$xml->{'nvt'}};
    } else {
        push(@items,$xml->{'nvt'});
    }
    
    foreach my $nvt (@items) {
        my $risk_level = 0;
        my $id = $nvt->{'oid'}; 
        $id =~ s/.*\.//;
        
        $plugin_rel_hash{$id} = $nvt->{'name'};
        
        if (ref($nvt->{'cvss_base'}) eq 'HASH')
        {
            $risk_level = 0;
        }
        elsif ($nvt->{'cvss_base'} ne "")
        {
            $risk_level = int($nvt->{'cvss_base'} / 2);
        }
        else
        {
            my $risk    = $nvt->{'risk_factor'};        
 
            if ($risk eq "None")     { $risk_level = 0 }
            if ($risk eq "")         { $risk_level = 0 }        
            if ($risk eq "Low")      { $risk_level = 1 }
            if ($risk eq "Medium")   { $risk_level = 3 }
            if ($risk eq "High")     { $risk_level = 4 }
            if ($risk eq "Critical") { $risk_level = 5 }
        }

        $plugin_prio_hash{$id} = $risk_level; 
    }
}


close(PLUGINS);
print "plugins fetched\n";

my $dsn = "dbi:mysql:".$ossim_conf::ossim_data->{"ossim_base"}.":".$ossim_conf::ossim_data->{"ossim_host"}.":".$ossim_conf::ossim_data->{"ossim_port"};
my $dbh = DBI->connect($dsn, $ossim_conf::ossim_data->{"ossim_user"},
$ossim_conf::ossim_data->{"ossim_pass"})
    or die "Can't connect to DBI\n";

my $query = "SELECT * from plugin_sid where plugin_id = 3001;";

my $sth = $dbh->prepare($query); 
$sth->execute();

my $row;

while($row = $sth->fetchrow_hashref){
if(exists($plugin_rel_hash{$row->{sid}})){
delete $plugin_rel_hash{$row->{sid}};
delete $plugin_prio_hash{$row->{sid}};
}
}

$query = "INSERT INTO plugin_sid(plugin_id, sid, category_id, class_id, priority, reliability, name) VALUES ";

if(keys %plugin_rel_hash){
print "Updating...\n";
foreach $key (keys %plugin_rel_hash){
print "Script id:$key, Name:$plugin_rel_hash{$key}, Priority:$plugin_prio_hash{$key}\n";
#$plugin_rel_hash{$key} =~ s/'/''/; 
$plugin_rel_hash{$key} =~ s/'/\\'/gs;
$plugin_rel_hash{$key} =~ s/"/\\"/gs;

my $sid = $key;
if ($key =~ /\./){
    my @tmp = split(/\./, $key);
    $sid = $tmp[$#tmp];
}

$query .= "(3001, $sid, NULL, NULL, $plugin_prio_hash{$key}, 7, 'nessus: $plugin_rel_hash{$key}'),";
}

chop($query);
$query .= ";";

$sth = $dbh->prepare($query);
$sth->execute();
} else {
print "\nDB is up to date\n";
}


