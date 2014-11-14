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

#
# Profiles:Framework
use v5.10;
use strict;
use warnings;
#use diagnostics;

use File::Basename;
use File::Copy;
use Perl6::Slurp;
use Data::Dumper;

package avcheck;
use vars qw(@ISA @EXPORT @EXPORT_OK $VERSION);
use Exporter;
@ISA       = qw(Exporter);
@EXPORT    = qw(avcheck_thread);
@EXPORT_OK = qw();
my $module_name = "avcheck";
my $VERSION     = 1.00;
my $regdir      = "/usr/share/alienvault-center/regdir";

use AV::Log::File;
#use AV::Module::Log;
use AV::ConfigParser;
use Avconfigd;
use threads;

sub avcheck_thread() {
    my %config = AV::ConfigParser::current_config;
    my @profiles = split(',', $config{'profile'} );
    if ( not /Framework/ ~~ @profiles ) {
        console('Not framework profile');
        threads->exit();
    }

    my $interval = 60;

    while (1) {
        my @s_ins_tot;
        my $conn = Avtools::get_database();

        my $query = "select LOWER(CONCAT(LEFT(hex(system.id), 8), '-', MID(hex(system.id), 9,4), '-', MID(hex(system.id), 13,4), '-', MID(hex(system.id), 17,4), '-', RIGHT(hex(system.id), 12))) as UUID from alienvault.system;";
        my $sth   = $conn->prepare($query);
        $sth->execute();

        while ( my $system_installed = $sth->fetchrow_arrayref ) {
            push @s_ins_tot, $system_installed->[0];
        }

        $sth->finish();
        $conn->disconnect
            || verbose("Disconnect error.\nError: $DBI::errstr");

        for (@s_ins_tot) {
            my $pkt_num      = 0;
            my $pkt_outdated = 0;
            my $pkt_commits  = 0;

            if ( -f "$regdir/$_/dpkg_total" ) {
                my $pkt_num_c = `cat $regdir/$_/dpkg_total |wc -l`;
                $pkt_num   = $pkt_num_c - 4;
            }

            if ( -f "$regdir/$_/last_dist-upgrade-changes" ) {
                $pkt_outdated
                    = `cat $regdir/$_/last_dist-upgrade-changes |wc -l`;
            }

            if ( -f "$regdir/$_/last_dist-upgrade-changes_extended" ) {
                $pkt_commits
                    = `cat $regdir/$_/last_dist-upgrade-changes_extended |wc -l`;
            }
        }
        sleep $interval;
    }
}

sub console {
    my $msg = shift;
    console_log_file("THREAD->$module_name : $msg");

}

sub verbose {
    my $msg = shift;
    verbose_log_file("THREAD->$module_name : $msg");

}

sub debug {
    my $msg = shift;
    debug_log_file("THREAD->$module_name : $msg");

}

1;
