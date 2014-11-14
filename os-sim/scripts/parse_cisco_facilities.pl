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
$0 [sql|translation]
USAGE

if(!$ARGV[0]){
print $usage;
exit;
}

if($ARGV[0] eq "sql"){
$mode = "sql";
} else {
$mode = "translation";
}


# Expects the source code of an html page like this: http://www.cisco.com/en/US/docs/ios/12_2sr/system/messages/sm2srovr.html
# Convert to unix previously using dos2unix

$i = 1;

$cisco_plugin_id = 1510;

if($mode eq "sql"){
print "DELETE FROM plugin WHERE id = '$cisco_plugin_id';\n";
print "DELETE FROM plugin_sid where plugin_id = '$cisco_plugin_id';\n";
print "INSERT INTO plugin (id, type, name, description) VALUES ($cisco_plugin_id, 1, 'cisco-router', 'Cisco router');
\n";
} else {
print "[translation]\n";
}

@sev_lvls = ();
$sev_lvls[0] = "Emergency";
$sev_lvls[1] = "Alert";
$sev_lvls[2] = "Critical";
$sev_lvls[3] = "Error";
$sev_lvls[4] = "Warning";
$sev_lvls[5] = "Notification";
$sev_lvls[6] = "Informational";
$sev_lvls[7] = "Debugging";

while(<STDIN>){
	if(/left.*top.*pB1_Body1/){
		$sid_short  = <STDIN>;
		chop($sid_short);
		while(<STDIN>){
			if(/^<td.*name.*class.*pB1_Body1/){
				$sid_name = <STDIN>;
				chop($sid_name);
				if($mode eq "sql"){
				$i = &generate_sid_sql($sid_short, $sid_name, $i);
				} else {
				$i = &generate_translation($sid_short, $sid_name, $i);
				}
				last;
			}
		}	
	}
}

sub generate_sid_sql(){
  $sid_short = shift;
  $sid_name = shift;
  $sid_id = shift;
  for($a = 0; $a < 8; $a++){
     $reliability  = 8 - $a;
     print "INSERT INTO plugin_sid (plugin_id, sid, category_id, class_id, name, priority, reliability) VALUES ($cisco_plugin_id, $sid_id, NULL, NULL, 'Cisco-$sid_short: $sid_name " . $sev_lvls[$a]  .  " Event', 1, $reliability);\n";
     $sid_id++;
  }
  return $sid_id;
}

sub generate_translation(){
  $sid_short = shift;
  $sid_name = shift;
  $sid_id = shift;
  for($a = 0; $a < 8; $a++){
     $reliability  = 8 - $a;
     print "\%$sid_short-$a=$sid_id\n";
     $sid_id++;
  }
  return $sid_id;
}
