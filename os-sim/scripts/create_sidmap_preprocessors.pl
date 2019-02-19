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

if(!$ARGV[0])
{
	print STDERR "\n-- Usage: $0 gen-msg.map\n\n";
	print STDERR "-- This file is used to extract all the plugin sids from snort preprocessors. Insert the results into the OSSIM DB. You may do something like:\n";
	print STDERR "-- # ./create_sidmap_preprocessors.pl /tmp/snort/snort-2.6.1.5/etc/gen-msg.map | ossim-db\n\n";
	exit();
}

open(IN,"<$ARGV[0]") or die "Can't open $ARGV[0]";

if($ARGV[0] =~ /\/([^\/]*)$/)
{
	print "---- $1\n";
}

while(<IN>)
{ 
	#line example:
	#100 || 1 || spp_portscan: Portscan Detected
	if(/(\d\d\d)\s\|\|\s(\d+)\s\|\|(.+)/)
	{
		$plugin_id = $1;
		$plugin_sid = $2;
		$str = $3;
		$str =~ s/\'/\\\'/g;
		print "INSERT IGNORE INTO plugin_sid (plugin_id, plugin_ctx, sid, category_id, class_id, name) VALUES (1$plugin_id, 0x0, $plugin_sid, NULL, NULL, '$str');\n";
	}
}
close IN;


