# License:
#
#  Copyright (c) 2011-2014 AlienVault
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

# OSSIM CD Installer
# Some functions for the updater
#

package ossim_cd_updater;
require Exporter;

our @ISA=qw(Exporter);
our @EXPORT=qw(sort_vers do_backup cmp_ver);
our $VERSION=1.00;


sub sort_vers
{
	my @arr=@_;
	@vers=sort { cmp_ver($a,$b); } @arr;
	return @vers;
}

sub cmp_ver
{
	my $a=shift;
	my $b=shift;

	@v1=split(/\./,$a);
	@v2=split(/\./,$b);

	$na=@v1;
	$nb=@v2;

	$n=0;

	while($n<$na && $n<$nb)
	{
	#	print "while\n";
		$k=$v1[$n];
		$q=$v2[$n];
		if(($k cmp $q)>0)
		{
			return 1;
		}

		if(($k cmp $q)<0)
		{
			return -1;
		}
		$n++;
	}

	if($n<$na)
	{
		return -1;
	}

	if($n<$nb)
	{
		return 1;
	}

	if($n==$na&&$n==$nb)
	{
		return 0;
	}
}

###

1;
