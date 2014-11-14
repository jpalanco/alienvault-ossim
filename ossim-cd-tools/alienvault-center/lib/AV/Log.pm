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

package AV::Log;

use v5.10;
use strict;
use warnings;
#use diagnostics;

use vars qw(@ISA @EXPORT @EXPORT_OK $VERSION);
use Exporter;
@ISA = qw(Exporter);
@EXPORT = qw(
    set_output_descriptors_to_dev_null
    restore_orig_output_descriptors
    console_log_line
    console_log
    always_log
    verbose_log
    debug_log
    error
    warning
    dp
    $percent
    $dialog_bin
    $dialog_active $CONSOLELOG $DEBUGLOG $VERBOSELOG
);
@EXPORT_OK = qw();
$VERSION   = 1.00;

our $CONSOLELOG    = 0;
our $VERBOSELOG    = 0;
our $DEBUGLOG      = 0;
our $WARNING       = 0;
our $dialog_active = 0;
our $dialog_bin    = 'dialog';
our $percent       = 0;

my ( $stdout_fh, $stderr_fh );

sub _save_orig_output_descriptors {
    state $already_saved = 0;
    return if $already_saved;
    $already_saved = 1;

    # See http://stackoverflow.com/a/6297156 for an explanation
    open $stdout_fh, '>&', STDOUT or die "Cannot dup STDOUT: $!";
    open $stderr_fh, '>&', STDERR or die "Cannot dup STDERR: $!";
}

sub set_output_descriptors_to_dev_null {
    open STDOUT, q{>}, q{/dev/null}
        or die "Cannot open /dev/null for output: $!";
    open STDERR, q{>}, q{/dev/null}
        or die "Cannot open /dev/null for output: $!";
}

sub restore_orig_output_descriptors {
    open STDOUT, q{>&}, $stdout_fh
        or die "Cannot dup \$stdout_fh: $!";
    open STDERR, q{>&}, $stderr_fh
        or die "Cannot dup \$stderr_fh: $!";
}

sub console_log_line {
    my $len = shift || 80;

    console_log(q{-} x $len);
}

sub console_log {
    if ($CONSOLELOG) {
        my $TIME = localtime( time() );
        my $LOG  = shift;
        # Limit to 200 chars.
        $LOG = length($LOG) >= 200 ? substr($LOG, 0, 200) . "..." : $LOG;
        say {$stdout_fh} "$TIME $LOG";
    }
}

sub always_log {
    say {$stdout_fh} @_;
}

sub dp {
    # FIXME: proposed interfase
    # use AV::Log;
    # AV::Log::setup( {
    #   BACKTITLE => 'AlienVault Reconfigure Script',
    #   TITLE     => 'AlienVault Reconfig',
    #   },
    # );
    #
    my $locate = shift;
    if ($dialog_active) {
        # system(q{$dialog_bin --gauge "$locate" 10 50 $percent});
        my $BACKTITLE = q{ "AlienVault Setup "};
        my $TITLE     = q{AlienVault Reconfig};
        my $BODY      = q{Please be patient.};
        my $professional = `dpkg -l alienvault-professional`;
        my $dlg
            = qq{echo $percent | $dialog_bin --stdout --title "$TITLE" --backtitle $BACKTITLE --gauge "$locate" 10 50};
        my ($rslt)   = qx{ $dlg }; # FIXME: value captured and then discarded
        # my $status = $? >> 8;  # FIXME: not used at all!!
        $percent += 2;
    }
    return;
}

sub error {
    my $LOG = shift;
    say {$stderr_fh} "ERROR: $LOG Exiting.";
    exit 0;
}

sub warning {
    if ($WARNING) {
        my $LOG = shift;
        say {$stderr_fh} "WARNING: $LOG";
        undef $LOG;
    }
}

sub verbose_log {
    if ($VERBOSELOG) {
        my $TIME = localtime( time() );
        my $LOG  = shift;
        say {$stdout_fh} "$TIME + $LOG";
    }
}

sub debug_log {
    if ($DEBUGLOG) {
        my $TIME = localtime( time() );
        my $LOG  = shift;
        say {$stderr_fh} "$TIME ++ $LOG";
    }
}

sub _init {
    _save_orig_output_descriptors();
    #set_output_descriptors_to_dev_null();
    return;
}

_init();

1;
