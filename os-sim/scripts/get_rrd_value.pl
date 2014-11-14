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
use strict;
use warnings;

$| = 1;

sub usage {

print "$0 start_time end_time rrd_file ";
print "[compromise|attack|ntop] [MAX|MIN|AVERAGE]\n";
print "time can be: relative, using N-1H, N-2H, etc...\n";
print "or using AT style syntax\n";
exit 0;
}

usage() if !(exists $ARGV[4]);

my $start = $ARGV[0];
my $end = $ARGV[1];
my $rrd_file = $ARGV[2];
my $what = $ARGV[3];
my $type = $ARGV[4];

$what = "ds0" if $what eq "compromise";
$what = "ds1" if $what eq "attack";
$what = "counter" if $what eq "ntop";

my @result= `$ossim_conf::ossim_data->{"rrdtool_path"}/rrdtool graph /dev/null -s $start -e $end -X 2 DEF:obs=$rrd_file:$what:AVERAGE PRINT:obs:$type:%lf`;

print "$result[1]";

exit 0;

