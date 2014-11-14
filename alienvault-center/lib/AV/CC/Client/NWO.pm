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

#package AV::CC::Client::Interface;
# inherits package from AV::CC::Client::Interface

use v5.10;
use strict;
use warnings;
#use diagnostics;

use SOAP::Lite;

use AV::Log;
use AV::uuid;
use AV::ConfigParser;

my $http_ssl   = 'https';
my $soap_port  = 40007;
my $timeout    = 60;

no warnings 'redefine';

sub run {
    my %config = AV::ConfigParser::current_config();
    my $systemuuid = `/usr/bin/alienvault-system-id`;

    my $func           = shift;
    my $siem_component = shift;

    verbose_log("Call $siem_component $func");

    my $conection_proxy = "$http_ssl://$siem_component:$soap_port/av-centerd";
    my $client          = SOAP::Lite
        ->uri('AV/CC/Util')
        ->proxy(
            $conection_proxy,
            timeout => $timeout,
        )
        ->on_fault(
            sub {
                my ( $soap, $res ) = @_;
                my $error
                    = ref $res ? $res->faultstring : $soap->transport->status;
                verbose_log("Call failed: ERROR: $error");
                return -1;

            }
        );

    my $result;
    eval {
        $result = $client->$func( 
            'All',
            $systemuuid,
            $config{'admin_ip'},
            $config{'hostname'},
            @_,
        );
    };
    die $@ if $@;
    return $result;
}

1;
