#
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

package AV::CC::Client::Interface;

use v5.10;
use strict;
use warnings;

#use diagnostics;

use Perl6::Slurp;

use AV::uuid;
use AV::ConfigParser;

use AV::Gel;
use AV::CC::Util;

sub run {
    my $func           = shift;
    my $siem_component = shift;    # discarded; useless here

    my %config     = AV::ConfigParser::current_config();
    my $systemuuid = `/usr/bin/alienvault-system-id`;
    my @params     = ( 'All', $systemuuid, @config{qw(admin_ip hostname)}, @_ );

    return AV::Gel->new( AV::CC::Util->$func(@params) );
}

sub run_local {
    my $func           = shift;
    my $siem_component = shift;    # discarded; useless here

    my %config     = AV::ConfigParser::current_config();
    my $systemuuid = `/usr/bin/alienvault-system-id`;
    my @params     = ( 'All', $systemuuid, @config{qw(admin_ip hostname)}, @_ );

    return AV::Gel->new( AV::CC::Util->$func(@params) );
}

sub _init {
    eval 'use AV::CC::Client::NWO';
    return;
}

_init();

1;
