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

use strict 'vars';
$0 = "rrd_plugin.pl";
# we are daemon so set autoflush
$| = 1;
# Script for the plugin rrd_threshold & rrd_anomaly
#
# 2004-02-11 Fabio Ospitia Trujillo <fot@ossim.net>
# 2006-03-10 Igor Indyk <rootik@mail.ru>
# Changes:
# * debug code
# * RRDs perl module instead of standalone rrdtool (up to 5 times performance boost)
# * accurate last update computation
# * benchmarking
# * switch to AVERAGE instead of MAX

my $DEBUG = 0;
use POSIX qw(setsid);
use DBI;
use RRDs;
use File::Find;
use Getopt::Std;
if ($DEBUG) {use Benchmark ':hireswallclock'};

#Configure this variables to match your installation:
my $pidfile = "/var/run/rrd_plugin.pid";
my $rrd_interval = 300;
my $rrd_sleep = $rrd_interval;
my $rrd_range = "1H";


my $ERROR;
my $current;
my ($ds_type,$ds_host,$ds_name,$ds_user,$ds_pass,$ds_port);
my ($td,$t0,$t1,$count)if $DEBUG;

sub usage {
    print "Usage:\n";
    print "rrd_plugin.pl [-d dsn][-i interfaces][-o logfile]\n";
    print "Options:\n";
    print "    -d dsn         Set database connection options string\n";
    print "                   dbtype:host:dbname:user:pass(:port)\n";
    print "    -i interfaces  Set ntop's monitored interfaces names\n";
    print "                   comma separated\n";
    print "    -o logfile     Set output file for logs\n";
    print "                   (default: /var/log/ossim/rrd_plugin.log)\n";
    print "    -v             (debug mode)\n";
    exit 0
}

sub daemonize {
    print "$0: forking into background...\n";
    chdir '/'                 or die "Can't chdir to /: $!";
    open STDIN, '/dev/null'   or die "Can't read /dev/null: $!";
    open STDOUT, '>>/dev/null' or die "Can't write to /dev/null: $!";
    open STDERR, '>>/dev/null' or die "Can't write to /dev/null: $!";
    defined(my $pid = fork)   or die "Can't fork: $!";
    exit if $pid;
    setsid                    or die "Can't start a new session: $!";
    umask 0;
}

sub die_clean {
    unlink $pidfile;
    exit;
}

open(PID, ">$pidfile") or die "Unable to open $pidfile\n";
print PID $$;
close(PID);

my %options=();
getopts("i:d:o:v",\%options);

if (defined $options{d}) {
    ($ds_type, $ds_host, $ds_name, $ds_user, $ds_pass, $ds_port) =
        split (/:/, $options{d});
} else {
  print "ERROR: Database connection DSN not defined\n";
  usage()
}

my $interfaces;

if (defined $options{i})
    {$interfaces = $options{i}}
else {
  print "ERROR: Monitored interfaces not defined\n";
  usage()
}

my $rrd_log = "/var/log/ossim/rrd_plugin.log";
if (defined $options{o}) {
	$rrd_log = $options{o};
}

if (defined $options{v}) {
	$DEBUG = 1;
}

my $dsn = join ":","dbi",$ds_type,$ds_name,$ds_host,$ds_port;

my $conn = DBI->connect($dsn,$ds_user,$ds_pass)
  or die "Can't connect to Database\n";

my $query = "SELECT value FROM config WHERE conf = 'rrdpath_ntop'";
my $stm = $conn->prepare($query);
$stm->execute();
my $res = $stm->fetch;
$stm->finish();
$conn->disconnect();
my $rrd_ntop = @$res[0];
foreach my $interface (split (",", $interfaces)) {
  my $folder = "$rrd_ntop/interfaces/$interface";
  die "ERROR: Directory does not exist: $folder\n" unless (-d $folder)
}

&daemonize unless $DEBUG;


sub rrd_fetch_hwpredict_by_time {
    my ($file, $stime, $etime) = @_;
    my ($start,$step,$names,$result) = RRDs::fetch ($file,"HWPREDICT","-s",$stime,"-e",$etime);
    print "ERROR: $ERROR\n" if (($ERROR = RRDs::error) && $DEBUG);
    print $names->[0].":HWPREDICT($etime): ".$result->[0][0]."\n" if $DEBUG;
    return 0 unless defined $result->[0][0];
    return $result->[0][0];
}

sub rrd_fetch_devpredict_by_time {
    my ($file, $stime, $etime) = @_;
    my ($start,$step,$names,$result) = RRDs::fetch ($file,"DEVPREDICT","-s",$stime,"-e",$etime);
    print "ERROR: $ERROR\n" if (($ERROR = RRDs::error) && $DEBUG);
    print $names->[0].":DEVPREDICT($etime): ".$result->[0][0]."\n" if $DEBUG;
    return 0 unless defined $result->[0][0];
    return $result->[0][0];
}

sub rrd_fetch_average_by_time {
    my ($file, $stime, $etime) = @_;
    my ($start,$step,$names,$result) = RRDs::fetch ($file,"AVERAGE","-s",$stime,"-e",$etime);
    print "ERROR: $ERROR\n" if (($ERROR = RRDs::error) && $DEBUG);
    print $names->[0].":AVERAGE($etime): ".$result->[0][0]."\n" if $DEBUG;
    return 0 unless defined $result->[0][0];
    return $result->[0][0];
}

# Return the last faliure interval
sub rrd_fetch_last_failure {
    my ($file, $range, $current) = @_;
    my @result;
    my $empty = 0;

    my ($start,$step,$names,$data) = RRDs::fetch ($file,"FAILURES","-s","$current-$range","-e",$current);
    print "ERROR: $ERROR\n" if (($ERROR = RRDs::error) && $DEBUG);    
    
    foreach my $line (@$data) {
      unless ($line->[0] == 1) {
              $empty = 1;
              next;
      }
      @result = () if ($empty);
      push (@result, $start);
      $start += $step;
      $empty = 0;
    }
    if ($DEBUG && ($#result > 0)) {print "Observed failures at:\n";foreach (@result) {print; print "\n"}};
    return @result;    
}

# Return true if is anomaly
sub rrd_anomaly {
    my ($ip, $interface, $att, $priority, $file, $persistence, $current) = @_;

    my @failure = rrd_fetch_last_failure ($file, $rrd_range, $current);
    return 0 unless (@failure);

    my $first_time = $current - ($persistence * $rrd_interval);

    my $first_failure = $failure[$#failure - $persistence];
    my $last_failure = $failure[$#failure];

    return 0 if (($last_failure != $current) || ($first_failure != $first_time));

    my $hwpredict = rrd_fetch_hwpredict_by_time ($file, $last_failure - $rrd_interval, $last_failure);
    if ($hwpredict < 0) {$hwpredict = 0}
    my $devpredict = rrd_fetch_devpredict_by_time ($file, $last_failure - $rrd_interval, $last_failure);
    if ($devpredict < 0) {$devpredict = abs($devpredict)}
    my $average = rrd_fetch_average_by_time ($file, $last_failure - $rrd_interval, $last_failure);

    # If average is by excess
    return 0 unless ($average > ($hwpredict + ($devpredict*2)));
    print "Average=$average, Predicted: ".($hwpredict + ($devpredict*2)).", Diff: ".($average-($hwpredict + (2 * $devpredict)))."\n" if $DEBUG;
    print OUTPUT "rrd_anomaly: ".time()." $ip $interface $att $priority $last_failure\n";
    print "rrd_anomaly: ".time()." $ip $interface $att $priority $last_failure\n" if $DEBUG;
    return 1;
}

# Return true if is threshold
sub rrd_threshold {
    my ($ip, $interface, $att, $priority, $file, $threshold, $current) = @_;
    my $res = rrd_fetch_average_by_time ($file, ($current-$rrd_interval), $current);
    print "Value: $res, Threshold: $threshold\n" if $DEBUG;
    return 0 unless ($res > $threshold);

    print OUTPUT "rrd_threshold: $current $ip $interface $att $priority ".($res - $threshold)."\n";
    print "rrd_threshold: $current $ip $interface $att $priority ".($res - $threshold)."\n" if $DEBUG;
    return 1;
}

sub ip2long {
    my ($ip) = @_;
    my @ips = split (/\./, $ip);
    my $long = ($ips[0]<<24) + ($ips[1]<<16) + ($ips[2]<<8) + $ips[3];
    return $long;
}

sub rrd_config {
    my ($interface) = @_;
    $count = 0;
    # GLOBAL RRDs
    my $query = "SELECT rrd_attrib, threshold, priority, persistence FROM rrd_config WHERE profile = 'GLOBAL' AND enable = 1";
    my $stm = $conn->prepare($query);
    $stm->execute();
    while (my $row = $stm->fetchrow_hashref) {
	my $att = $row->{rrd_attrib};
	my $threshold = $row->{threshold};
	my $priority = $row->{priority};
	my $persistence = $row->{persistence};

	my $file = "$rrd_ntop/interfaces/$interface/$att.rrd";
	next unless (-e $file);
        print "Processing: $file\n" if $DEBUG;
	$count++;
        my $last = RRDs::last($file);
	print "ERROR: $ERROR\n" if (($ERROR = RRDs::error) && $DEBUG);
	my $first = RRDs::first($file);	
        print "ERROR: $ERROR\n" if (($ERROR = RRDs::error) && $DEBUG);
        $current = $first+(int(($last-$first)/$rrd_interval)*$rrd_interval);
        my $shouldbe = int(time()/$rrd_interval)*$rrd_interval;
        print "RRD's last update: $current, Current time (RRD boundary): $shouldbe\n" if $DEBUG;
	unless ((time()-$last)<(2*$rrd_interval))
	 {
          print "WARNING! RRD has not been updated for a ".(time() - $last)." secs.\n" if $DEBUG;		     
	  next;
	 }
	rrd_threshold ("GLOBAL", $interface, $att, $priority, $file, $threshold, $current);
	rrd_anomaly ("GLOBAL", $interface, $att, $priority, $file, $persistence, $current);
    }
    
    # HOST RRDs
    my %files = ();
    find ({wanted => sub{
	    return unless (-d);
            my @tmp = split ("/", $_);
	    next unless ($tmp[$#tmp - 4] == 'hosts');
	    my $ip = "$tmp[$#tmp - 3].$tmp[$#tmp - 2].$tmp[$#tmp - 1].$tmp[$#tmp]";
            if ($ip =~ /\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/) {
		$files{$ip} = $ip;
	    }
	    return
	   }, no_chdir => 1}, "$rrd_ntop/interfaces/$interface/hosts");
	   
    my $ip;
    foreach $ip (keys %files) { 
	my $query = "SELECT rrd_profile FROM host WHERE ip = '$ip'";
	my $stm = $conn->prepare($query);
	$stm->execute();
	my $row = $stm->fetchrow_hashref;
	my $profile = $row->{rrd_profile};

	if (!$profile) {
	    my $mymask = 0;
	    $query = "SELECT ips, rrd_profile FROM net";
	    $stm = $conn->prepare($query);
	    $stm->execute();
	    while ($row = $stm->fetchrow_hashref) {
		my $ips =  $row->{ips};
		my @list =  split (",", $ips);
		my $myip;
		foreach $myip (@list) {
		    my @data = split ("/", $myip);
		    my $mask = $data[1];
		    my $val1 = ip2long($data[0]);
		    my $val2 = ip2long($ip);
		    
		    if (($val1 >> (32 - $mask)) == ($val2 >> (32 - $mask))) {
			if ($mask > $mymask) {
			    $profile = $row->{rrd_profile};
			    $mymask = $mask;
			}
		    }
		}
	    }
	}

	next unless ($profile);

        print "Found profile '$profile' for host $ip\n" if $DEBUG;		     
	$query = "SELECT rrd_attrib, threshold, priority, persistence FROM rrd_config WHERE profile = '$profile' AND enable = 1";
	$stm = $conn->prepare($query);
	$stm->execute();
	while (my $row = $stm->fetchrow_hashref) {
	    my $att = $row->{rrd_attrib};
	    my $threshold = $row->{threshold};
	    my $priority = $row->{priority};
	    my $persistence = $row->{persistence};

	    my $dir = $ip;
	    $dir =~ s/\./\//g;

	    my $file = "$rrd_ntop/interfaces/$interface/hosts/$dir/$att.rrd";
	    next unless (-e $file);
            print "File: $file\nOSSIM RRD profile: $profile\n" if $DEBUG;
	    $count++;
            my $last = RRDs::last($file);
	    print "ERROR: $ERROR\n" if (($ERROR = RRDs::error) && $DEBUG);
	    my $first = RRDs::first($file);
            print "ERROR: $ERROR\n" if (($ERROR = RRDs::error) && $DEBUG);
	    $current = $first+(int(($last-$first)/$rrd_interval)*$rrd_interval);
            my $shouldbe = int(time()/$rrd_interval)*$rrd_interval;
            print "RRD's last update: $current, Current time (RRD boundary): $shouldbe\n" if $DEBUG;
            unless ((time()-$last)<(2*$rrd_interval))
		{
		    print "WARNING! RRD has not been updated for a ".(time() - $last)." secs.\n" if $DEBUG;
		    next
		}
	    rrd_threshold ($ip, $interface, $att, $priority, $file, $threshold, $current);
	    rrd_anomaly ($ip, $interface, $att, $priority, $file, $persistence, $current);
	}
    }
}

# The Main Function
sub rrd_main {
    while (1) {
	if ($DEBUG) {$t0 = new Benchmark};
        print "Running at ".localtime(time)." (".time.")\n" if $DEBUG;
	$conn = DBI->connect($dsn, $ds_user, $ds_pass) or die "Can't connect to Database\n";
	open (OUTPUT, ">>$rrd_log") or die "Can't open file log";
	foreach my $interface (split (",", $interfaces)) {
	    rrd_config ($interface);
	}
	close (OUTPUT);
	$conn->disconnect;
	if ($DEBUG) {
		$t1 = new Benchmark;
		$td = timediff($t1, $t0);
		print "Processed $count files\n"; 
		print "The code took: ",timestr($td),"\n";
		exit
	}
	sleep ($rrd_sleep);
    }
}

rrd_main ();
