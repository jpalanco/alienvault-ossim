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

package AvSyncasec;
use File::Basename;
use File::Copy;
use Perl6::Slurp;
use AV::uuid;
#use Data::Dumper;

use vars qw(@ISA @EXPORT @EXPORT_OK $VERSION);
use Exporter;
@ISA       = qw(Exporter);
@EXPORT    = qw(AvSyncasec_thread);
@EXPORT_OK = qw();
my $module_name      = "AvSyncasec";
my $VERSION          = 1.00;
my $binary_collector = "/usr/bin/alienvault-center-collector";
my $assec_plugins_dir = "/var/lib/asec/plugins";

use threads;
use AV::Log::File;
use AV::CC::SharedData;
#use AV::Module::Log;
use AV::ConfigParser;

sub AvSyncasec_thread() {
    my %config = AV::ConfigParser::current_config;
    my @profiles = split(',', $config{'profile'} );
    if ( not /Framework/ ~~ @profiles ) {
		AV::CC::SharedData->set($module_name, 'Not framework profile');
        console('Not framework profile');
        threads->exit();
    }

    my $interval = 180;

    while (1) {

	  if (  -f "$assec_plugins_dir/sync" ) {
			
        my @s_ins_tot;
        my @s_ins_tot_uuid;
        my %config = AV::ConfigParser::current_config;

        my $conn = Avtools::get_database();


		#####
		# Localiza en la bbdd avcenter todos los sensores que estan conectados al propio server
		# select admin_ip from current_local where server_ip = "$config{'admin_ip'}" and profile like '%Sensor%';
		#
		# FIX -> falta añadirse a el mismo en el caso de ser un sensor tambien
		#
		#        push @s_ins_tot, $config{'admin_ip'};
		#
		#
		# up new plugin in remote sensor

		my $ip_p = $config{'admin_ip'};
        my @querys = (
            "select LOWER(CONCAT(LEFT(hex(system.id), 8), '-', MID(hex(system.id), 9,4), '-', MID(hex(system.id), 13,4), '-', MID(hex(system.id), 17,4), '-', RIGHT(hex(system.id), 12))) as uuid from alienvault.system, alienvault.server where server.id=system.server_id and server.ip = inet6_pton('$ip_p') and system.profile like '%Sensor%'",
        );
		 
        for my $query (@querys) {
            my $sth = $conn->prepare($query);
            $sth->execute();
            while ( my $system_installed = $sth->fetchrow_arrayref ) {
                push @s_ins_tot_uuid, $system_installed->[0];
            }
            $sth->finish();
        }

#        push (@s_ins_tot_uuid,"67da869c-f87c-314b-9d8b-74847b58d905");
        my $systemuuid = `/usr/bin/alienvault-system-id`;
        push (@s_ins_tot_uuid,$systemuuid);
		for (@s_ins_tot_uuid){
			console("$_");


		}
        
		
		my @system_update_need;
        for my $plugins ( glob "$assec_plugins_dir/*.cfg" ) {
			
#				console("===============================================================================================");
				if ( ! -f "${plugins}.sql" ){
				   console("Warning: ${plugins}.sql not found !! . Ignoring. ");	
				   next;
				}

				my $plugin_name = basename $plugins;
#				console("Proccess -> $plugins- $plugin_name");
				for my $remote_system(@s_ins_tot_uuid) {
				
#						verbose("check $remote_system  ");

						if ( -f "/usr/share/alienvault-center/regdir/$remote_system/___etc___ossim___agent___plugins___${plugin_name}" ){
							my 	$md5asec=`md5sum $plugin_name`;
							my  $md5local=`md5sum /usr/share/alienvault-center/regdir/$remote_system/___etc___ossim___agent___plugins___${plugin_name}`;
							next if ( $md5asec == $md5local ); 

						}
						    	
						verbose("--> Plugin not found in remote system ($remote_system) ");
						verbose("set $plugin_name to $remote_system");
						debug("copy $plugins to /usr/share/alienvault-center/regdir/$remote_system/___etc___ossim___agent___plugins___$plugin_name");
						copy("$plugins","/usr/share/alienvault-center/regdir/$remote_system/___etc___ossim___agent___plugins___$plugin_name");
					    my $command = "$binary_collector --server=$remote_system --set_file=___etc___ossim___agent___plugins___$plugin_name";
						debug($command); 
						system($command);
						my $systemuuid = $remote_system;
					
						my @sql_inserts_for_plugins;
					
						verbose("Read ${plugins}.sql");	
						open FILE, "< ${plugins}.sql";
							while(<FILE>){
								s/\n//g;
								push ( @sql_inserts_for_plugins,$_);

							}
						close (FILE);

					    for my $query (@sql_inserts_for_plugins) {                         
								next if ( $query =~  m/^--/ );	
								next if ( $query =~  m/^$/ );	
								#verbose("execute query $query");	 
								my $sth = $conn->prepare($query);              
								$sth->execute();                               
								$sth->finish();                                
						}        

						# up new plugin in remote sensor
						my $query = qq(select sensor_detectors from avcenter.current_local where uuid = '$systemuuid');
						#debug("$query") ;
						my $sth = $conn->prepare($query);         
						$sth->execute();                          
						my $detector =  $sth->fetchrow_array() ;
						$sth->finish();     
						my @to_detect = split(",",$detector);
						$plugin_name =~ s/\.cfg//g;
						    if ( not /$plugin_name/ ~~ @to_detect ) {
								
								push ( @to_detect, $plugin_name );
								my $anex_plug = join(",",@to_detect);
								my $query = qq(update avcenter.current_local set sensor_detectors='$anex_plug'  where uuid = '$systemuuid');
								
								debug("$query") ;                                                                      
								my $sth = $conn->prepare($query);                                                        
								$sth->execute();                                                                         
								my $detector =  $sth->fetchrow_array() ;                                                 
								$sth->finish();                                         



								#debug("                                  |-> new command a updatear ($query) ");
								push (@system_update_need,"$binary_collector --server=$remote_system --set");
								push (@system_update_need,"$binary_collector --server=$remote_system --get");
								push (@system_update_need,"$binary_collector --server=$remote_system --service=ossim-agent --action=stop &&
							                 			   $binary_collector --server=$remote_system --service=ossim-agent --action=start");

							}

	        	    
	        	}
	        
        }                   


		console("Send remote command");
		my @system_update_need_ins;
		my %visto_antes;
		foreach my $caracter ( @system_update_need) {
			 push @system_update_need_ins, $caracter if not $visto_antes{$caracter}++;
		}

		my $server_reboot = 0;	
	    for (@system_update_need_ins){ $server_reboot=1; }
		system("/etc/init.d/ossim-server restart")	if $server_reboot == 1 ;

		for (@system_update_need_ins){

			verbose("exec $_");
			system("$_");

		}
         $conn->disconnect
          || verbose("Disconnect error.\nError: $DBI::errstr");

        AV::CC::SharedData->set($module_name, "sleep for $interval second.");

		unlink("$assec_plugins_dir/sync");
	   } ## end file sync
        AV::CC::SharedData->set($module_name, "sleep for $interval second.");
#		console("sleep for $interval");
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
