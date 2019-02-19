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

if(!$ARGV[2])
{
  print "\nExample usage: $0 osvdb_snort.txt osvdb_list 1\n";

  print "osvdb_snort.txt = snort<->osvdb sids list, in format '1980, 4523);'\n";
	print "osvdb_list = list of osvdb_ids of a specific platform, in format: '22319'\n";
	print "1: type of OS inserted. These are the possible OS:\n\n";
	print "1: Windows\n";
	print "2: Linux\n";
	print "3: Cisco\n";
	print "4: BSD\n";
	print "5: FreeBSD\n";
	print "6: NetBSD\n";
	print "7: OpenBSD\n";
	print "8: HP_UX\n";
	print "9: Solaris\n";
	print "10: Macos\n";
	print "11: Plan9\n";
	print "12: SCO\n";
	print "13: AIX\n";
	print "14: UNIX\n";

  exit();
}

open(IN1,"<$ARGV[0]") or die "Can't open $ARGV[0]";

open(IN2,"<$ARGV[1]") or die "Can't open $ARGV[1]";

# 1817, 1);
#1001, 1817, 5001, 1

while($line = <IN1>)
{
	if ($line =~ /(\d+),\s(\d+)/)
	{
		$snort_sid = $1;
		$osvdb_id = $2;
	}

	$match = 0;
	while (<IN2>)
	{
	  if (m/(\d+)/)
		{
			$cmp_osvdb_id = $1;
			if ($osvdb_id == $cmp_osvdb_id)
			{
				$match =1;
				last;
			}
		}
	}
	seek (IN2,0,0);

	if ($match == 1) 
	{
		if ($ARGV[2] == 1)
		{
			print "INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, $snort_sid, 5001, 1);\n";
		}
		elsif ($ARGV[2] == 2)
		{
			print "INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, $snort_sid, 5001, 2);\n";
		}
		elsif ($ARGV[2] == 3)
		{
			print "INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, $snort_sid, 5001, 3);\n";
		}
		elsif ($ARGV[2] == 4)
		{
			print "INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, $snort_sid, 5001, 4);\n";
		}
		elsif ($ARGV[2] == 5)
		{
			print "INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, $snort_sid, 5001, 5);\n";
		}
		elsif ($ARGV[2] == 6)
		{
			print "INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, $snort_sid, 5001, 6);\n";
		}
		elsif ($ARGV[2] == 7)
		{
			print "INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, $snort_sid, 5001, 7);\n";
		}
		elsif ($ARGV[2] == 8)
		{
			print "INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, $snort_sid, 5001, 8);\n";
		}
		elsif ($ARGV[2] == 9)
		{
			print "INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, $snort_sid, 5001, 9);\n";
		}
		elsif ($ARGV[2] == 10)
		{
			print "INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, $snort_sid, 5001, 10);\n";
		}
		elsif ($ARGV[2] == 11)
		{
			print "INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, $snort_sid, 5001, 11);\n";
		}
		elsif ($ARGV[2] == 12)
		{
			print "INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, $snort_sid, 5001, 12);\n";
		}
		elsif ($ARGV[2] == 13)
		{
			print "INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, $snort_sid, 5001, 13);\n";
		}
		elsif ($ARGV[2] == 14)
		{
			print "INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, $snort_sid, 5001, 14);\n";
		}
	}
}
