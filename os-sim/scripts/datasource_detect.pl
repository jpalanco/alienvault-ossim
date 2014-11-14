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

# \
# \n

if(!$ARGV[0]){
print "Usage: $0 log_source_ip [plugin_to_enable]\n";
exit;
}

$plugin = "";
$ip = $ARGV[0];
$logfile = "/var/log/ossim/$ip.log";
$tmp_logfile = "/tmp/logfile" . "_$ip" . "_$$";
$debug = 1;

if($ARGV[1]){
$plugin = $ARGV[1];
}

# Validate IP
if($debug){print "[+] Validating IP\n";}

# Enable source in rsyslog
if($debug){print "[+] Enabling source in rsyslog\n";}

system("rm -f /etc/rsyslog.d/$ip.conf");
system("echo ':FROMHOST, isequal, \"$ip\" -$logfile' >> /etc/rsyslog.d/$ip.conf");
system("echo '& ~' >> /etc/rsyslog.d/$ip.conf");

# Restart syslog
if($debug){print "[+] Restarting syslog\n";}

system("/etc/init.d/rsyslog restart 2>&1>/dev/null");

# Sleep 10 seconds, test if target file is non-zero, wait another 10 seconds up to 60 seconds and stop if no logs are coming in.

$sleep_count = 0;
$sleep_time = 1;
if($debug){print "[+] Sleeping $sleep_time second(s) \n";}
sleep($sleep_time);

while(1){
if(-s $logfile){
last;
}
if($debug){print "[+] Sleeping $sleep_time second(s) \n";}
if($sleep_count > 4){
print "Breaking";
last;
}
sleep($sleep_time) unless -s $logfile;
$sleep_count++;
}

# Copy 100 lines of file to tmp
if($debug){print "[+] Copying last 100 log lines to $tmp_logfile\n";}
system("rm -f $tmp_logfile");
system("tail -n 100 $logfile > $tmp_logfile");


# Count how many lines have been copied
@num_lines = `wc -l $tmp_logfile | cut -f 1 -d " "`;
$actual_lines = chomp($num_lines[0]);
if($debug){print "[+] Copied $actual_lines log lines\n";}

# Run regexp.py on file with all standard plugins
if($debug){print "[+] Testing log file \n";}

my %plugins_matched = ();

$some_dir = "/etc/ossim/agent/plugins/";
opendir(DIR, $some_dir) || die "can't opendir $some_dir: $!";
@plugin_files = grep { /.*.cfg$/ && -f "$some_dir/$_" } readdir(DIR);
closedir DIR;

foreach $plugin_file (sort(@plugin_files)){
if($plugin_file =~ /eth/){next} # skip specific interface files
if($plugin_file =~ /-monitor.cfg/){next} # skip monitor files
if($plugin_file =~ /wmi.*logger.cfg/){next} # skip wmi logger regexps
if($plugin_file =~ /post_correlation/){next} # skip false matching ones
if($plugin_file =~ /forensics-db-1/){next} # skip false matching ones
print "[+]\tTesting $plugin_file\n";
	open(TEST_PLUGIN, "python /usr/share/ossim/scripts/regexp.py $tmp_logfile $some_dir/$plugin_file q|");
	while(<TEST_PLUGIN>){
		if(/Matched\s+(\d+)\s+lines/){
			$plugins_matched{$plugin_file} = $1;
		}
	}
	close TEST_PLUGIN;
}

if($debug){print "\n";}

# Output top 5 matching plugins
if($debug){print "[+] Top 5 matching plugins: \n";}


foreach $key (sort keys %plugins_matched){
if($plugins_matched{$key} > 0){
print "\tPlugin $key: Matched $plugins_matched{$key}\n";
}
}


# Enable specified plugin
if($debug){print "[+] Enabling plugin $plugin \n";}

# Cleanup tmp file
if($debug){print "[+] Cleaning tmp log file \n";}
#system("rm -f $tmp_logfile");



# Restart agent
if($debug){print "[+] Restarting agent \n";}

