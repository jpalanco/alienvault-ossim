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
  print "Example usage: $0 cve_bugtraq.txt xmlDumpByID-2007-02-28.xml\n";
  exit();
}

#stderr to see data although there are output redirection
($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst)=localtime(time);
printf STDERR "Starting time: %4d-%02d-%02d %02d:%02d:%02d\n",$year+1900,$mon+1,$mday,$hour,$min,$sec;
print STDERR "Starting conversion, please be patient and take one or two coffees......";

open(IN1,"<$ARGV[0]") or die "Can't open $ARGV[0]";

open(IN2,"<$ARGV[1]") or die "Can't open $ARGV[1]";

$aux_bug = 0;
$aux_cve = 0;

while($line = <IN1>)
{
	if ($line =~ /bugtraq\s(\d+),\s(\d+)/)
	{
		$snort_sid = $1;
		$bugtraq = $2;
		$aux_bug = 1;
	}

	if ($line =~ /cve\s(\d+),\s(\d+)-(\d+)/)
	{
		$snort_sid2 = $1;
		$cve = $2."-".$3;
		$aux_cve = 1;
	}

	while (<IN2>)
	{

	  if(m/vuln osvdb_id="(\d+)"/)
		{
			$osvdb_id = $1;
		  $bugtraq_id = 0;
			$cve_id = 0;
			while($temp  = <IN2>)
			{
				if ($aux_bug == 1) #if this has bugtraq references...
				{
#					if($temp =~ m/base_name>.*(windows).*/i)
#						{$aux_win = 1;}

					if($temp =~ m/Bugtraq.*indirect=.*"\>(\d+)\<.*/)
					{
						$bugtraq_id = $1;
						if ($bugtraq == $bugtraq_id)
						{
							print "INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, $snort_sid, 5003, $osvdb_id);\n";
#							if ($aux_win == 1) {print "YES"};
							$jump = 1;
						}
					}
					$aux_win = 0;
				}
				if ($aux_cve == 1)
				{
					if($temp =~ m/CVE\sID.*indirect=.*"\>(\d+)-(\d+)\<.*/)
					{
						$cve_id = $1."-".$2;
						if ($cve eq $cve_id)
						{
							print "INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, $snort_sid2, 5003, $osvdb_id);\n";
							$jump = 1;

						}
					}
				}
				


				if($temp =~ m/\<\/ext_refs\>/)
				{
			    last;
				}
			}


		}
	
	}
	$aux_bug = 0;
	$aux_cve = 0;
	seek (IN2,0,0);
}

#now rules with snort sid. Some of them may be repeated.
seek (IN1,0,0);
seek (IN2,0,0);
while($line = <IN1>)
{	
  while (<IN2>)
  {
    if(m/vuln osvdb_id="(\d+)"/)
    {
		  $osvdb_id = $1;
			while ($temp = <IN2>)
			{
        if(<IN2> =~ m/Snort Signature ID.*"\>(\d+)\<.*/)
        {
          $snort_sid3 = $1;
          print "INSERT INTO plugin_reference (plugin_id, plugin_sid, reference_id, reference_sid) VALUES (1001, $snort_sid3, 5003, $osvdb_id);\n";
        }
			}
		}
    if($temp =~ m/\<\/ext_refs\>/)
    {
	    last;
    }

	}
}


print STDERR "Ended conversion at: ";
($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst)=localtime(time);
printf STDERR "%4d-%02d-%02d %02d:%02d:%02d\n",$year+1900,$mon+1,$mday,$hour,$min,$sec;
