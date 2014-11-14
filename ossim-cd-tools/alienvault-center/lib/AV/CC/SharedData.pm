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

package AV::CC::SharedData;

use v5.10;
use strict;
use warnings;

#use diagnostics;

use Carp;
use IPC::Shareable;

my %thrstat;
my $thrstat_handle;

sub _new {
    state $already = 0;
    return if $already;
    $already++;

    # do init stuff here

    my $class = shift;
    my $glue  = 'avcenter';

    $thrstat_handle = tie %thrstat, 'IPC::Shareable', $glue,
        {
        create    => 'yes',
        exclusive => 0,
        mode      => 0644,
        destroy   => 0,
        }
        or croak 'server: tie failed';

    set_values_to_empty();
    return;
}

sub set_values_to_empty {
    my @keys = qw(
        AvCacheStatus
        avconfigd
        AvReconfig
        AvRserversSync
        AvUpdateDbCenter
        AvUpdateSystemInfo
        update
        update_last_date
		AvSyncasec
    );

    $thrstat_handle->shlock();
    for my $key (@keys) {
        $thrstat{$key} //= 'empty';
    }
    $thrstat_handle->shunlock();

    return;
}

sub set {
    my ( $class, $key, $msg ) = @_;

    $thrstat_handle->shlock();
    $thrstat{$key} = $msg;
    $thrstat_handle->shunlock();

    return;
}

sub get {
    $thrstat_handle->shlock();
    my %snapshot = %thrstat;
    $thrstat_handle->shunlock();

    return %snapshot;
}

sub get_value_of {
    my ( $class, $key ) = @_;

    $thrstat_handle->shlock();
    my $value = $thrstat{$key};
    $thrstat_handle->shunlock();

    return $value;
}

sub assert_state {
    my ( $class, $key, $state );

    return get_value_of($key) eq $state;
}

sub lock_if_empty_fail_otherwise {
    my ( $class, $key, $msg ) = @_;

    my $lock_secured = 0;
    $thrstat_handle->shlock();
    if ( $thrstat{$key} eq 'empty' ) {
        $thrstat{$key} = $msg;
        $lock_secured = 1;
    }
    $thrstat_handle->shunlock();

    return $lock_secured;
}

sub _init {
    _new();
    return;
}

_init();

1;
