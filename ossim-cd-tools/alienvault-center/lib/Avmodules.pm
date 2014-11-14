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

package Avmodules;

use v5.10;
use strict;
use warnings;
#use diagnostics;

use threads;

use lib '/usr/share/alienvault-center/lib/modules';

use AV::Log::File;
use AV::ConfigParser;

our $nurse_thr;

our %avthreads;

sub _run_modules {

    my @threads_v;
    my $modules_dir = '/usr/share/alienvault-center/lib/modules';

    if ( -d $modules_dir ) {

        verbose_log_file("Loading modules from $modules_dir");
    LOOP:
        for my $module ( glob "$modules_dir/*.pm" ) {

			my %config = AV::ConfigParser::current_config;
			my @profiles = split( ',', $config{'profile'} );
			if ( /Framework/ ~~ @profiles ) {
				my $authorize=`grep "^# Profiles:Framework" $module`;
				if ( $authorize eq "" ){
					verbose_log_file("Module not authorized :  $module");
					next LOOP;
				}
			}else{
					verbose_log_file("Not framework profile : not loading  $module");
					next LOOP;

			}
            verbose_log_file("Loading $module");

            #eval "require '$module'";
            my @module_w = split '/', $module;
            my $m = pop @module_w;
            $m =~ s/\.pm//g;
            console_log_file("use $m");
            eval "use $m";
            if ($@) {
                console_log_file(
                    "Could not load module source file $module: $@");
            }
            else {
                my $thread_up = $m . '_thread';
                if ( defined $avthreads{$thread_up}
                    && $avthreads{$thread_up}->is_running() )
                {
                    next LOOP;
                }
                if ( defined $avthreads{$thread_up}
                    && !$avthreads{$thread_up}->is_running() )
                {
                    delete $avthreads{$thread_up};
                    console_log_file("'$thread_up' was dead.");
                }
                console_log_file("Starting '$thread_up'...");
                my $thr = threads->create($thread_up);
                $thr->detach();
                $avthreads{$thread_up} = $thr;
            }
        }
    }
}

sub nurse_modules {
    if ( defined $nurse_thr
        && $nurse_thr->is_running() )
    {
        return 0;
    }
    $nurse_thr = threads->create(
        sub {
            while (1) {
                _run_modules();
                sleep 3600;
            }
        }
    );
    $nurse_thr->detach();
}

1;
