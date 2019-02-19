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

# RRD get date
use lib "/usr/share/ossim/include";
use ossim_conf;
use strict;
use warnings;

my $rrdtool = "$ossim_conf::ossim_data->{\"rrdtool_path\"}/rrdtool";

sub usage{
print "$0 IP RANGE [compromise|attack] [host|net|global]\n";
exit(1);
}

if (!$ARGV[3]) {
   usage();
}


my $ip = $ARGV[0];
my $range = $ARGV[1];
my $what = $ARGV[2];
my $type = $ARGV[3];
my $rrdpath;

if($type eq "host"){
$rrdpath = $ossim_conf::ossim_data->{rrdpath_host};
} elsif($type eq "net"){
$rrdpath = $ossim_conf::ossim_data->{rrdpath_net};
} elsif($type eq "global"){
$rrdpath = $ossim_conf::ossim_data->{rrdpath_global};
}




$what = "1" if $what eq "compromise";   # First column
$what = "2" if $what eq "attack";       # Second column

my $date = 0;
my $temp = "";
my $greatest = "";
my $major = 0;
my $medium = 0;
my $minor = 0;

open(INPUT,"$rrdtool fetch $rrdpath/$ip.rrd MAX -s N-$range -e N|") or die "Can't execute..";
while(<INPUT>){
    if(/^(\d+):\s(\d+)\.(\d+)e\+(\d+)\s(\d+)\.(\d+)e\+(\d+)$/){
        if($_ =~ /nan/){next;};
        if($what eq "1"){
        $temp = "$4|$2|$3";
        if($temp gt $greatest){
        $greatest = $temp;
        $major = $4;
        $medium = $2;
        $minor = $3;
        $date = $1;
        }
        } elsif ($what eq "2"){
        $temp = "$7|$5|$6";
        if($temp gt $greatest){
        $greatest = $temp;
        $major = $7;
        $medium = $5;
        $minor = $6;
        $date = $1;
        }
        }
    }
}
close(INPUT);

printf("$date\n");


exit 0;
