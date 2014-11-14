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

package AV::CC::Server::Listener;

use v5.10;
use strict;
use warnings;

#use diagnostics;
use IO::Socket::SSL;
use SOAP::Transport::HTTP::Daemon::ForkOnAccept;
use AV::Log;
use AV::Log::File;
use AV::uuid;

#use IO::Socket::SSL qw(debug3);

sub soap_listen {
    my $soap_port  = 40007;
    my $systemuuid = `/usr/bin/alienvault-system-id`;

    console_log_file(" UUID : $systemuuid ");
    console_log_file(" Start listening in port : $soap_port");
    my $daemon = SOAP::Transport::HTTP::Daemon::ForkOnAccept->new(
        Listen             => 10,
        LocalPort          => $soap_port,
        ReuseAddr          => 1,
    )->dispatch_to('AV::CC::Util');

    if ($daemon) {
        my $lurl = $daemon->url;

        console_log_file(" Server SOAP LISTEN : $lurl ");
        $daemon->handle;
    }
    else {
        console_log_file(" Server error while binding : $@ ");
        error(" Server Error while binding :$@ ");
    }
}

1;
