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

use strict;
use warnings;
use Sys::Syslog;
use lib "/usr/share/ossim/include";
use DBI;
use ossim_conf;

#NOTE: Deprecated file, please use Tools->Net Scan from the web console to update host_services data

$| = 1;

my $dsn = 'dbi:mysql:'.$ossim_conf::ossim_data->{"ossim_base"}.':'.$ossim_conf::ossim_data->{"ossim_host"}.':'.  $ossim_conf::ossim_data->{"ossim_port"};
my $dbh = DBI->connect($dsn, $ossim_conf::ossim_data->{"ossim_user"}, $ossim_conf::ossim_data->{"ossim_pass"}) or 
    die "Can't connect to DBI\n";


my $nmap = $ossim_conf::ossim_data->{"nmap_path"};
my $SLEEP = 14400; # 4 hours

while(1) {

my $query = "SELECT ip FROM host";
my $sth = $dbh->prepare($query);
$sth->execute();
while (my $row = $sth->fetchrow_hashref) 
{
    my $ip = $row->{ip};

    # delete to update values
    my $query = "DELETE FROM host_services WHERE ip = '$ip'";
    my $sth = $dbh->prepare($query);
    $sth->execute();

    open(NMAP, "$nmap -sV $ip|");

    my $service = '';
    my $version = '';

    while(<NMAP>){
        if (/open\s+([\w\-\_]+)\s+(.*)$/) {
        
            $service = $1;
            $version = $2;
        
            my $query = "INSERT INTO host_services (ip, service, version)
                        VALUES ('$ip', '$service', '$version');";
            my $sth = $dbh->prepare($query);
            $sth->execute();
            next;
        }
    }
    close(NMAP);
}

sleep($SLEEP);
}

$dbh->disconnect;
exit 0;

