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
# Profiles:Framework
#
use v5.10;
use strict;
use warnings;
#use diagnostics;

#use File::Basename;
#use File::Copy;
#use Perl6::Slurp;
#use Data::Dumper;

package AvCacheStatus;
use vars qw(@ISA @EXPORT @EXPORT_OK $VERSION);
use Exporter;
@ISA       = qw(Exporter);
@EXPORT    = qw(AvCacheStatus_thread);
@EXPORT_OK = qw();
my $module_name      = "AvCacheStatus";
my $VERSION          = 1.00;
my $binary_collector = "/usr/bin/alienvault-center-collector";

use threads;
use AV::Log::File;
use AV::CC::SharedData;
#use AV::Module::Log;
use AV::ConfigParser;

sub AvCacheStatus_thread() {
    my %config = AV::ConfigParser::current_config;
    my @profiles = split(',', $config{'profile'} );
    if ( not /Framework/ ~~ @profiles ) {
		AV::CC::SharedData->set($module_name, 'Not framework profile');
        console('Not framework profile');
        threads->exit();
    }

    my $interval = 180;

    while (1) {
        my @s_ins_tot;
        my %config = AV::ConfigParser::current_config;

        push @s_ins_tot, $config{'admin_ip'};
        push @s_ins_tot, $config{'server_ip'};
        push @s_ins_tot, $config{'database_ip'};
        push @s_ins_tot, $config{'framework_ip'};

        my $conn = Avtools::get_database();

        my @querys = (
            'select inet6_ntop(ip) from alienvault.sensor where name != "(null)"',
            'select inet6_ntop(ip) from alienvault.server',
            'select inet6_ntop(ip) from alienvault.databases',
        );
        
        for my $query (@querys) {
            my $sth = $conn->prepare($query);
            $sth->execute();
            while ( my $system_installed = $sth->fetchrow_arrayref ) {
                push @s_ins_tot, $system_installed->[0];
            }
            $sth->finish();
        }

        $conn->disconnect
            || verbose("Disconnect error.\nError: $DBI::errstr");

        my @s_ins = keys %{ { map { $_ => 1 } @s_ins_tot } };

        for (@s_ins) {
			AV::CC::SharedData->set($module_name, "get $_");
			
            my $command = "$binary_collector --server_ip=$_ --system_status >/dev/null 2>&1";
            debug($command);
            system($command);
            
            $command = "$binary_collector --server_ip=$_ --alienvault_status >/dev/null 2>&1";
            debug($command);
            system($command);
            
            $command = "$binary_collector --server_ip=$_ --network_status >/dev/null 2>&1";
            debug($command);
            system($command);
            # We also need the  dpkg
            $command = "$binary_collector --server_ip=$_ --get_dpkg >/dev/null 2>&1";
            debug($command);
            system($command);
            $command = "$binary_collector --server_ip=$_ --update_system_info >/dev/null 2>&1";
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

_init;

1;
