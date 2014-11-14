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

package AvExecReconfig;

use v5.10;
use strict;
use warnings;
#use diagnostics;

use POSIX qw/ strftime /;
use DateTime;
use DateTime::Format::Flexible;
use Perl6::Slurp;
use Data::Dumper;

use Linux::APT;

use AV::CC::SharedData;
use AV::uuid;
use AV::ConfigParser;
use Avrepository;
use AV::Log;
use AV::Log::File;
my $systemuuid = `/usr/bin/alienvault-system-id`;

sub system_reconfig {
    my @out = ($systemuuid);
    
    if ( AV::CC::SharedData->lock_if_empty_fail_otherwise('AvReconfig', 'starting reconfig') ) {
        push @out, 'Wake up thread for system reconfig in background' if $0 =~ /av-centerd/;
        console_log_file('Starting system reconfig');
        reconfig_thread( @_ ) and exit unless fork;
    }
    else {
        push @out, 'reconfig in progress...';
    }
    return @out;
}

sub reconfig_thread {
    my $interval = 2;

    my $i = 1;

    #system("echo > /var/log/alienvault-reconfig.log");
    open( F, 'alienvault-reconfig --center -c -v |' );

    while (<F>) {
        chomp;
        AV::CC::SharedData->set( 'AvReconfig', $_ );

        #system("echo $_ >> /var/log/alienvault-center_update.log");
        console_log_file("Thread Alienvault-reconfig -> $_");
        console_log("Thread Alienvault-reconfig -> $_") if $0 !~ /av-centerd/ ;
    }

    console_log_file("Finished system reconfig, kill thread.");
    AV::CC::SharedData->set('AvReconfig', 'empty');
}

1;
