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

package AvUpdateSystemInfo;
#
# Profiles:Framework
use v5.10;
use strict;
use warnings;
#use diagnostics;

#use File::Basename;
#use File::Copy;
#use Perl6::Slurp;
#use Data::Dumper;

use vars qw(@ISA @EXPORT @EXPORT_OK $VERSION);
use Exporter;
@ISA       = qw(Exporter);
@EXPORT    = qw(AvUpdateSystemInfo_thread);
@EXPORT_OK = qw();
my $module_name      = "AvUpdateSystemInfo";
my $VERSION          = 1.00;
my $binary_collector = "/usr/bin/alienvault-center-collector";

use AV::Log::File;
#use AV::Module::Log;
use AV::CC::SharedData;
use AV::ConfigParser;
use Avconfigd;
use threads;



sub AvUpdateSystemInfo_thread() {
    my %config = AV::ConfigParser::current_config;
    my @profiles = split(',', $config{'profile'} );
    if ( not /Framework/ ~~ @profiles ) {
        my $msg = 'Not framework profile';
		AV::CC::SharedData->set($module_name, $msg);
        console($msg);
        threads->exit();
    }

    my $interval = 86400;

    while (1) {
        my @s_ins_tot;
        my %config = AV::ConfigParser::current_config;

        my $conn = Avtools::get_database();
        my $query = 'select ifnull(inet6_ntop(vpn_ip),'') as vpn_ip, inet6_ntop(admin_ip) as admin_ip from alienvault.system';

        my $sth = $conn->prepare($query);
        $sth->execute();
        while ( my $system_installed = $sth->fetchrow_arrayref ) {
            if ( ! ($system_installed->[0] == '') ) {
                push @s_ins_tot, $system_installed->[0];
            }
            else
            {
                push @s_ins_tot, $system_installed->[1];
            }
        }
        $sth->finish();

        $conn->disconnect
            || verbose("Disconnect error.\nError: $DBI::errstr");

        my @s_ins = keys %{ { map { $_ => 1 } @s_ins_tot } };

        for (@s_ins) {
			AV::CC::SharedData->set($module_name, "get $_");

            my $command = "$binary_collector --server_ip=$_ --update_system_info";
            debug($command);
            system($command);
        }
        AV::CC::SharedData->set($module_name, "sleep for $interval second.");
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

sub _init {
	
	AV::CC::SharedData->set($module_name, 'empty');
}
_init();

1;
