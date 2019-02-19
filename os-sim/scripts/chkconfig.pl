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

# Use all the "used". This makes sure our perl environment is sane. If some of
# these complain then some of the other perl scripts won't work for you.

use lib '/usr/share/ossim/include';
use ossim_conf;
use DBI;
use POSIX;
use lib $ossim_conf::ossim_data->{"rrdtool_lib_path"};
use RRDs;
use CGI;
use File::Temp;
use Compress::Zlib;
use IO::Socket;
use Socket;
use Sys::Syslog;
use sigtrap qw(handler die_clean normal-signals);
use strict;
use warnings;

die("Please check the source, remove this line and verify all configuration file locations");

my $version = "0.9.6";
my $available_drivers = "mysql"; #could be "mysql,pgsql,oracle" i.e.
my $agent_config = "/etc/ossim/agent/config.xml";
my $server_config = "/etc/ossim/server/config.xml";
my $server_directives = "/etc/ossim/server/directives.xml";
my $framework_conf = "/etc/ossim/framework/ossim.conf";
my $php_ini = "/etc/php.ini";
my $httpd_conf = "/etc/httpd/httpd.conf";
my @server_errors;
my @agent_errors;
my @framework_errors;
my $verbose = 0;

if($ARGV[0]) {
    if($ARGV[0] =~ /-v/){$verbose = 1;}
    if($ARGV[0] =~ /-h/){print "Usage: $0 [-v]\n\t-v\tverbose mode on\n)"; exit;}
}
          
# Numeric
my @numeric_vars = (
"backup_day",
"has_ip",
"nessus_distributed",
"server_port"
);

# Executable Files
my @executables = (
"arpwatch_path",
"mail_path",
"nessus_path",
"nmap_path",
"p0f_path",
"touch_path",
"wget_path",
);

# Files
my @regular_files = (
"font_path"
);

# Directories
my @directories = (
"acid_path",
"adodb_path",
"backup_dir",
"base_dir",
"data_dir",
"jpgraph_path",
"mrtg_path",
"mrtg_rrd_files_path",
"nessus_rpt_path",
"phpgacl_path",
"rrdpath_global",
"rrdpath_host",
"rrdpath_net",
"rrdtool_lib_path",
"rrdtool_path",
"snort_path",
"snort_rules_path"
);

# Link
# Still have to check theese as they could include an url or not.
my @links = (
"acid_link",
"graph_link",
"nagios_link",
"ossim_link"
);

# Merely defined
my @just_defined = (
"email_alert",
"ossim_interface",
"server_address"
);

# Acid UI
my @acid_ui = (
"acid_pass",
"acid_user",
"ossim_web_user",
"ossim_web_pass"
);

# Nessus client
my @nessus_client = (
"nessus_host",
"nessus_pass",
"nessus_port",
"nessus_user"
);

# Backupdb
my @backup_db = (
"backup_type",
"backup_base",
"backup_host",
"backup_port",
"backup_user",
"backup_pass"
);

# Opennms DB
my @opennms_db = (
"opennms_type",
"opennms_base",
"opennms_host",
"opennms_port",
"opennms_user",
"opennms_pass"
);

# Ossim DB
my @ossim_db = (
"ossim_type",
"ossim_base",
"ossim_host",
"ossim_port",
"ossim_user",
"ossim_pass"
);

# Snort DB
my @snort_db = (
"snort_type",
"snort_base",
"snort_host",
"snort_port",
"snort_user",
"snort_pass"
);

sub blank{
if($verbose){
print "\n" x 50;
}
}

sub push_error{
my $env = shift;
my $msg = shift;

if(lc($env) =~ /server/){
push(@server_errors, $msg);
}
if(lc($env) =~ /agent/){
push(@agent_errors, $msg);
}
if(lc($env) =~ /framework/){
push(@framework_errors, $msg);
}

}

sub check_for_file {
my $what = shift;
my $location = shift;
my $env = shift;
my $empty = shift;
my $failed = 0;

if($verbose){
print "You can safely ignore the following error(s) if not checking on $env\n";
}
if(!$empty){
    if (!-s $location){ 
if($verbose){print("$what file missing at $location, please verify (shouldn't be empty)\n");} 
        &push_error($env, "$what file empty at $location\n");
        $failed = 1;
    };
} elsif ($empty == 2) {
    if (!-x $location){ 
if($verbose){
        print("$what perms wrong at $location, please verify (must be executable\n"); 
        }
        &push_error($env, "$what file not executable at $location\n");
        $failed = 1;
    };
} else {
    if (!-e $location){ 
if($verbose){
        print("$what file missing at $location, please verify\n"); 
        }
        &push_error($env, "$what file missing at $location\n");
        $failed = 1;
    };
}
if (!-r $location){ 
if($verbose){
    print("$what perms wrong at $location, please verify (need read access)\n"); 
    }
    &push_error($env, "$what file missing at $location\n");
    $failed = 1;
};

if(!$failed){
if($verbose){
print "- $what\t\tOK\n";
}
}

if($verbose){
print "\n";
}

}

sub check_for_directory{
my $what = shift;
my $location = shift;
my $env = shift;
my $empty = shift;
my $failed = 0;

if($verbose){
print "You can safely ignore the following error(s) if not checking on $env\n";
}
if($empty){
if (!-d $location ){ 
if($verbose){
    print("$what perms wrong at $location, should be a directory and writable\n"); 
    }
    $failed = 1;
    &push_error($env,"$what directory wrong at $location\n");
    };
}

if(!$failed){
if($verbose){
print "- $what: $location\t\tOK\n";
}
}

if($verbose){
print "\n";
}

}



sub check_paths {
my $temp;

check_for_file("Agent config", $agent_config, "agent", 0);
check_for_file("Server config", $server_config, "ossim-server", 0);
check_for_file("Server directives", $server_directives, "ossim-server", 1);
check_for_file("Framework config", $framework_conf, "agent or ossim-server", 0);
check_for_file("PHP ini", $php_ini, "framework server", 0);
check_for_file("Httpd conf", $httpd_conf, "framework server", 0);

&wait_for_key;

}

sub wait_for_key {
if($verbose){
print "Done. Press any key to continue\n";
my $temp = <STDIN>;
}
}

sub check_agent_config {
my $type;
my $name;
my $host;
my $port;
my $user;
my $pass;
my $comment = 0;

open(TEMP_AGENT,"<$agent_config");

while(<TEMP_AGENT>){
if(/<!--/){
$comment = 1;
next;
}
if(/-->/){
$comment = 0;
}
if($comment){next};
    if(/<location>([^:]+):([^:]+):([^:]+):([^:]+):([^<]*)<\/location>/){
    $type = $1;
    $name = $3;
    $host = $2;
    $user = $4;
    $pass = $5;
    if(/must/){next;};
       check_db("ossim-agent", $type, $name, $host, "", $user, $pass);
    } elsif(/<location>.*:\d+<\/location>/){
    next;
    } elsif(/<location>([^<]+)<\/location>/) {
        check_for_file("Plugin location", $1, "agent", 1);
    }
}

close(TEMP_AGENT);
&wait_for_key;

}

sub check_db{
my ($title, $type, $name, $host, $port, $user, $pass) = @_;
my $temp_dsn = "dbi:" . $type . ":" . $name . ":" . $host .  ":" . $port . ":";
my $stm;
if($available_drivers =~ /$type/){
my $conn = DBI->connect($temp_dsn, $user, $pass) or (print "Unable to connect with $title using: \nUser: $user\nPass: $pass\nDsn: $temp_dsn\n" && &push_error($title,"Unable to connect to $title using: $user:$pass dsn:$temp_dsn\n") && return);
if($verbose){
print "Connection with $title: $type,$host,$name succeeded\n";
}
} else {
if($verbose){
print "Couldn't find driver $type, please make sure it's available. If it is, please add it to the list at the top of this script\n";
&push_error($title, "$title: DB Driver $type missing\n");
}
}
}

sub check_server_config {
my $type;
my $name;
my $host;
my $port;
my $user;
my $pass;
my $comment = 0;

open(TEMP_SERVER,"<$server_config");

while(<TEMP_SERVER>){
    if(/<!--/){ $comment = 1; next;}
    if(/-->/){ $comment = 0;}
    if($comment){next};
    if(/<datasource\s+name="([^"]+)"\s+provider="([^"]+)"\s+dsn="PORT=([^;]+);USER=([^;]+);PASSWORD=([^;]+);DATABASE=([^;]+);HOST=([^;]+)".*/){ 
        $type = lc($2);
        $port = $3;
        $user = $4;
        $pass = $5;
        $name = $6;
        $host = $7;
        if(/must/){next;};
        if($available_drivers =~ /$type/){ check_db("ossim-server", $type, $name, $host, $port, $user, $pass); }
    } elsif (/<log filename="(.*)\/[^"]+"\s*\/>/){
            check_for_directory("Server logdir", $1, "ossim-server", 1);
    } elsif (/<directive filename="([^"]+)"\s*\/>/){
            check_for_file("Directive file", $1, "ossim-server", 1);
    }
}

close(TEMP_SERVER);
&wait_for_key;
}

sub check_framework_config{
my $index;

open(TEMP_FRAMEWORK,"<$framework_conf");
while(<TEMP_FRAMEWORK>){
    if(/([^=]+)=$/){
if($verbose){
    print "Warning: $1 empty. Are you sure that's ok ?\n";
    }
    &push_error("framework", "$1 variable empty\n");
    }
}

close(TEMP_FRAMEWORK);
if($verbose){
print "\n";
}
&wait_for_key;
&check_numeric;
foreach $index (0 .. $#executables){
    &check_for_file("Framework executables", &get_config($executables[$index]), "framework", 2);
}
foreach $index (0 .. $#regular_files){
    if(!&get_config($regular_files[$index])){
    &check_for_file("Framework $regular_files[$index]", &get_config($regular_files[$index]), "framework", 0);
    }
}
#AAAAAAAAA
foreach $index (0 .. $#directories){
    &check_for_directory("Framework directories", &get_config($directories[$index]), "framework", 1);
}
foreach $index (0 .. $#just_defined){
    &get_config($just_defined[$index]);
}

&check_db("framework-backup-db", &get_config($backup_db[0]), &get_config($backup_db[1]), &get_config($backup_db[2]), &get_config($backup_db[3]), &get_config($backup_db[4]), &get_config($backup_db[5]));
&check_db("framework-opennms-db", &get_config($opennms_db[0]), &get_config($opennms_db[1]), &get_config($opennms_db[2]), &get_config($opennms_db[3]), &get_config($opennms_db[4]), &get_config($opennms_db[5]));
&check_db("framework-ossim-db", &get_config($ossim_db[0]), &get_config($ossim_db[1]), &get_config($ossim_db[2]), &get_config($ossim_db[3]), &get_config($ossim_db[4]), &get_config($ossim_db[5]));
&check_db("framework-snort-db", &get_config($snort_db[0]), &get_config($snort_db[1]), &get_config($snort_db[2]), &get_config($snort_db[3]), &get_config($snort_db[4]), &get_config($snort_db[5]));
&wait_for_key;
&blank;

&check_acid();
}

sub get_config{
my $conf_key = shift;
    open FILE, "$framework_conf" or die "Can't open logfile: $!";
    while ($_ = <FILE>) {
        if(!(/^#/)) {
            if(/^$conf_key=(.*)$/) {
            return $1;
            }
        }
    }
    close(FILE);
if($verbose){
    print "$conf_key is missing !!\n";
    }
    &push_error("framework", "$conf_key config variable is missing\n");
    &wait_for_key;
}

sub check_numeric {
my $index;

foreach $index (0 .. $#numeric_vars){
    if (!(&get_config($numeric_vars[$index]) =~ /\d+/)){
if($verbose){
            print "$numeric_vars[$index] (" .  &get_config($numeric_vars[$index]) . ") should be numeric\n"; 
            }
    }
}
&wait_for_key;
}

sub check_acid {
my $acid_ip = "";
my $ossim_ip = "";
my $acid_link = &get_config("acid_link");
my $ossim_link = &get_config("ossim_link");
my $acid_user = &get_config("acid_user");
my $acid_pass = &get_config("acid_pass");
my $ossim_web_user = &get_config("ossim_web_user");
my $ossim_web_pass = &get_config("ossim_web_pass");
my $wget = &get_config("wget_path");
my $acid_url1;

if($acid_link =~  m/(\w+:\/\/)(.*)/){
$acid_ip = $1;
$acid_link = $2;
}

# ossim ip is the important one and overwrites acid.
if($ossim_link =~  m/(\w+:\/\/)(.*)/){
$ossim_ip = $1;
$ossim_link = $2;
} else {
$ossim_ip = "127.0.0.1";
}

if($ossim_link eq ""){ $ossim_link = "/ossim/";}
if($ossim_web_user eq ""){ $ossim_web_user = "admin";}
if($ossim_web_pass eq ""){ $ossim_web_pass = "admin";}
if($acid_link eq ""){ $acid_link = "/acid/";}

if ($acid_user eq ""){
$acid_url1 = "http://$ossim_ip" . $ossim_link .  "/session/login.php?dest=" . $acid_link . "/acid_update_db.php&user=" . $ossim_web_user .  "&pass=" . $ossim_web_pass;
} else {
$acid_url1 = "http://". $acid_user . ":" . $acid_pass . "@" .  $ossim_ip . $ossim_link .  "/session/login.php?dest=" . $acid_link .  "/acid_update_db.php&user=" . $ossim_web_user .  "&pass=" .  $ossim_web_pass;
}

# TODO: Auto check

`$wget -O /dev/null $acid_url1`;

print "Check the above message for any acid related errors\n";
&wait_for_key;
}

sub report{
my $index;

print "\n" x 80;
print "Configuration check done.\n";

print "\nErrors found regarding agent:\n";
foreach $index (0 .. $#agent_errors){
    print "+ $agent_errors[$index]";
}

print "\nErrors found regarding server:\n";
foreach $index (0 .. $#server_errors){
    print "+ $server_errors[$index]";
}

print "\nErrors found regarding framework:\n";
foreach $index (0 .. $#framework_errors){
    print "+ $framework_errors[$index]";
}

}

# Begin program execution

&blank;

print "Configuration check for ossim $version\n";
if($verbose){
print "+ Checking paths\n";
print "--------------------------------------\n\n";
}
&check_paths;
&blank;
if($verbose){
print "+ Checking Agent\n";
print "--------------------------------------\n\n";
}
&check_agent_config;
&blank;
if($verbose){
print "+ Checking Server\n";
print "--------------------------------------\n\n";
}
&check_server_config;
&blank;
if($verbose){
print "+ Checking Framework\n";
print "--------------------------------------\n\n";
}
&check_framework_config;

if(!$verbose){&report()};
