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

package Avconfigd;

use v5.10;
use strict;
use warnings;
#use diagnostics;

use AV::Log::File;

use parent 'Exporter';

our @EXPORT = qw(
    readconfig
    %configd
);
our @EXPORT_OK = qw();

our %configd = (
    config_file => '/etc/alienvault-center/alienvault-center.conf',
);

sub trimstr {
    my @str = @_;

    for (@str) {
        chomp;
        s/^[\t\s]+//;
        s/[\t\s]+$//;
    }

    return @str;
}

sub readconfig {
    my $configfile = $configd{config_file};

    open my $fh, $configfile
        or die "Can't read $configfile\n";
    while (<$fh>) {
        next if /^[\t\w]+#/;
        s/#.*//;

        my ( $key, $val ) = trimstr split '=', $_, 2;
        next unless defined $val;

        $configd{$key} = $val;
    }
    close $fh;

    # Check
    for my $key (qw(wd pidfile logfile regdir internet_available)) {
        my $prefixed_key = "daemon.$key";
        die "Missing property: $prefixed_key\n"
            unless exists $configd{$prefixed_key};
    }
    $AV::Log::File::LOGFILE = $configd{'daemon.logfile'};
    logmsg("Reading $configfile complete");

    return;
}

sub _init {
    return;
}

_init();

1;
