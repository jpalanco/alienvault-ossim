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

use DBI;

require "/var/ossim/kismet/kismet_sites.pl" if (-e '/var/ossim/kismet/kismet_sites.pl');

our %sites;

$numproc = &num_processes;
if ($numproc>1){
  print "$0 already running, exit.\n";
  exit(0);
}

sub num_processes {
    my $count=0;
    while (!$count) {
        $count = `ps ax | grep fetch_kismet | grep -v grep | grep -v vi | grep -v 'sh -c' | wc -l`;
        $count =~ s/\s*//g;
    }
    return $count;
}



# database connect
my $dbhost = `grep ^ossim_host= /etc/ossim/framework/ossim.conf | cut -f 2 -d "="`; chomp($dbhost);
$dbhost = "localhost" if ($dbhost eq "");
my $dbuser = `grep ^ossim_user= /etc/ossim/framework/ossim.conf | cut -f 2 -d "="`; chomp($dbuser);
my $dbpass = `grep ^ossim_pass= /etc/ossim/framework/ossim.conf | cut -f 2 -d "="`; chomp($dbpass);
#my $dbh = DBI->connect("DBI:mysql:ossim:$dbhost", $dbuser,$dbpass, { PrintError => 0, RaiseError => 1, AutoCommit => 1 } ) or die("Failed to connect : $DBI::errstr\n");

$home = "/var/ossim/kismet";
$logdir = "/var/log";
$syslog = "/var/log/syslog";

foreach $ip (keys %sites) {
   print "Pinging $ip...";
   $pingresult = `ping -q -w 5 $ip | grep transmitted | awk '{ print \$4 }'`; chomp($pingresult);
   print "Got $pingresult packets back\n";
   if ($pingresult eq '0') {
	print "ERROR: $ip is unpingable. Skipping...\n";
	next;
   }

   $location = $sites{$ip};
   $old_dir = "$home/parsed/$ip";
   $work_dir = "$home/work/$ip";
   mkdir $old_dir, 0755;
   mkdir $work_dir, 0755;
   print "old:$old_dir, work=$work_dir\n";

   $last_filename = `ls -ltr '$old_dir/'*'.xml' | awk '{print \$9}' | perl -npe 's/.*\\///g' | head -n 1`; chomp($last_filename);

   $filename=`ssh -o StrictHostKeyChecking=no $ip "cd $location; ls -ltr *.xml;" | awk '{ print \$9 }'`; chomp($filename);
   @removefiles = split(/\n/,$filename);
   $lastremote = $removefiles[$#removefiles];

   print "Copying from $ip:$location\n";
   system("scp -o StrictHostKeyChecking=no -p $ip:$location/$ip*.xml $work_dir");

   $now = localtime; $now =~ s/\s+/_/g;

   my $dbh = DBI->connect("DBI:mysql:alienvault:$dbhost", $dbuser,$dbpass, { PrintError => 0, RaiseError => 1, AutoCommit => 1 } ) or die("Failed to connect : $DBI::errstr\n");
   $sql = qq{ update wireless_sensors set last_scraped = now() where sensor in (select name from sensor where ip = inet6_aton('$ip')) };
   $sth_selm=$dbh->prepare( $sql );
   $sth_selm->execute;
   $sth_selm->finish;
   $dbh->disconnect or die("Failed to disconnect : $DBI::errstr\n");

   print "Importing xmls from $work_dir\n";
   system ("perl /usr/share/ossim/www/wireless/kismet_import.pl");

   foreach $filename (@removefiles) {
      # delete from server if not last remote file or last local file
      #print "------\n"; 
      #print "file:$filename lastremote:$lastremote lastlocal:$last_filename\n"; 
      if ($filename ne $lastremote) {
        if ($filename ne $last_filename) {
          print "Deleting remote file:$filename\n";
          system("ssh $ip \"cd $location; rm -f $filename\"");
        }
      }
   }

}
print "Done.\n";
