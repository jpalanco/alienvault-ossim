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

# Snort maintenance script
#
# snort_maintenance.pl [repair|clear]
# 
use lib "/usr/share/ossim/include";
use ossim_conf;
use DBI;

# Conection
if ($ARGV[0] ne "repair" && $ARGV[0] ne "clear") {
	print "USAGE: snort_maintenance.pl [repair|clear]\n";
	exit;
}

my $schema = "/usr/share/ossim/www/forensics/scripts/schema.sql";

# Data Source 
my $snort_type = $ossim_conf::ossim_data->{"ossim_type"};
my $snort_name = "alienvault_siem";
my $snort_host = $ossim_conf::ossim_data->{"ossim_host"};
my $snort_port = $ossim_conf::ossim_data->{"ossim_port"};
my $snort_user = $ossim_conf::ossim_data->{"ossim_user"};
my $snort_pass = $ossim_conf::ossim_data->{"ossim_pass"};

my $cmdline = "mysql -u$snort_user -p$snort_pass -h$snort_host -P$snort_port '$snort_name'";


# REPAIR
if ($ARGV[0] eq "repair") {
	if (-e $schema) {
		print "Repairing SNORT BBDD ...";
		system("$cmdline < $schema > /var/tmp/repair_snort_schema_log");
	} else {
		print "$schema NOT FOUND.\n";
	}
}

# CLEAR
if ($ARGV[0] eq "clear") {
	my $snort_dsn = "dbi:" . $snort_type . ":" . $snort_name . ":" . $snort_host . ":" . $snort_port;
	my $snort_conn = DBI->connect($snort_dsn, $snort_user, $snort_pass) or die "Can't connect to Database\n";
	@querys = ("TRUNCATE acid_event","TRUNCATE device","TRUNCATE extra_data","TRUNCATE reputation_data","TRUNCATE otx_data","TRUNCATE idm_data","TRUNCATE ac_acid_event","TRUNCATE po_acid_event");
	foreach	$q (@querys) {
		print "$q\n";
    	my $stm = $snort_conn->prepare($q);
    	$stm->execute();
    }
	$snort_conn->disconnect();
}

# Restaring ossim-server
system('/etc/init.d/ossim-server restart > /dev/null 2>&1 &');
print "Done.\n";
