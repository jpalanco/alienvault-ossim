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

package AV::Log::File;

use v5.10;
use strict;
use warnings;
#use diagnostics;

use parent 'Exporter';

our @EXPORT = qw(
    console_log_file
    verbose_log_file
    debug_log_file
    logmsg
    $CONSOLELOG_FILE
    $VERBOSELOG_FILE
    $DEBUGLOG_FILE
    $LOGFILE
);
our @EXPORT_OK = qw();

our $CONSOLELOG_FILE = 0;
our $VERBOSELOG_FILE = 0;
our $DEBUGLOG_FILE   = 0;

our $LOGFILE = '/var/log/ossim/alienvault.log';

sub console_log_file {
    return unless $CONSOLELOG_FILE;

    my $msg = shift;

    open my $fh, q{>>}, $LOGFILE
        or die "Can't write to logfile ($LOGFILE): $!\n";
    say {$fh} localtime() . " (PID $$): $msg";
    close $fh;
    return;
}

sub verbose_log_file {
    return unless $VERBOSELOG_FILE;

    my $msg = shift;

    open my $fh, q{>>}, $LOGFILE
        or die "Can't write to logfile $LOGFILE: $!\n";
    say {$fh} localtime() . " (PID $$): + $msg";
    close $fh;
    return;
}

sub debug_log_file {
    return unless $DEBUGLOG_FILE;

    my $msg = shift;

    open my $fh, q{>>}, $LOGFILE
        or die "Can't write to logfile $LOGFILE: $!\n";
    say {$fh} localtime() . " (PID $$): ++ $msg";
    close $fh;
    return;
}

# Inconditional logging
sub logmsg {
    my $msg = shift;

    open my $fh, q{>>}, $LOGFILE
        or die "Can't write to logfile $LOGFILE: $!\n";
    say {$fh} localtime() . " (PID $$): $msg";
    close $fh;
    return;
}

sub _init {
    return;
}

_init();

1;
