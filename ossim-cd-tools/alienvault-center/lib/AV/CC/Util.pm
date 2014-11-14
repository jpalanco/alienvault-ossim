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

package AV::CC::Util;

use v5.10;
use strict;
use warnings;
#use diagnostics;

use Config::Tiny;
use File::Basename;
use File::Path;
use File::Copy;
use URI::Escape;

use AV::Log::File;
use AV::CC::SharedData;
use AV::ConfigParser;
use Avstatistic;
use AV::uuid;
use Avrepository;
use Avtools;
use Avnetwork;
use AV::Debian::Netifaces;
use AV::CC::Client::Collector;
use AV::CC::Validate;

use AvupdateSystem;
use AvExecReconfig;

my $config_file = '/etc/ossim/ossim_setup.conf';
my $systemuuid  = `/usr/bin/alienvault-system-id`;





sub get_status {

    my ( $funcion_llamada, $nombre, $uuid, $admin_ip, $hostname ) = @_;

    verbose_log_file(
        "GET STATUS    : Received call from $uuid : ip source = $admin_ip, hostname = $hostname:($funcion_llamada,$nombre)"
    );

    my $system_time = Avtools::get_system_time;

    #console_log("Time on system $hs");

    my $system_uptime = Avtools::get_system_uptime;

    #console_log("System uptime $hs");

    my @his             = Avtools::list_processes;
    my $number_proccess = scalar(@his);

    #console_log("Running processes $pr");

    #my @hs = Avtools::get_current_cpu_temps;
    #foreach(@hs){
    #	console_log("CPU Temp $_");
    #}

    my $cpu_cores = Avtools::get_cpu_cores_info;

    my ( $loadaverage, $current_sessions )
        = split( ';', Avtools::get_cpu_info() );

    #console_log("CPU load averages $hs");
    verbose_log_file(
        "GET STATUS    : Received call from $uuid : ip source = $admin_ip, hostname = $hostname:($funcion_llamada,$nombre)"
    );
    my %hs = Avtools::get_memory_info;

    my $memtotal              = Avtools::nice_size( $hs{'memtotal'} * 1024 );
    my $memfree               = Avtools::nice_size( $hs{'memfree'} * 1024 );
    my $memused               = $hs{'memtotal'} - $hs{'memfree'};
    my $percent_memused       = ( ( $memused * 100 ) / $hs{'memtotal'} );
    my $percent_memused_print = sprintf( '%.2f', $percent_memused );
    my $memused_format        = Avtools::nice_size( $memused * 1024 );
    my $virtualmem            = Avtools::nice_size( $hs{'swaptotal'} * 1024 );
    my $virtualmemfree        = Avtools::nice_size( $hs{'swapfree'} * 1024 );
    my $virtualmemused        = $hs{'swaptotal'} - $hs{'swapfree'};
    my $virtualmemused_format = Avtools::nice_size( $virtualmemused * 1024 );
    my $percent_virtualmemused
        = ( ( $virtualmemused * 100 ) / $hs{'memtotal'} );
    my $percent_virtualmemused_print
        = sprintf( '%.2f', $percent_virtualmemused );
    my $disk_usage = Avtools::get_disk_usage_info;

    #my $current_sessions = Avtools::get_current_sessions;

    # profiles cond.

    my %config                = AV::ConfigParser::current_config();
    my @profiles_arr          = split( /,\s*/, $config{'profile'} );
    my @sensor_interfaces_ary = split /,\s*/, $config{'sensor_interfaces'};

    # sensor
    my $plugins_enabled          = "unavailable";
    my $sensor_profile_installed = 0;
    my $sniffing_interfaces      = "unavailable";
    my $network_monitored        = "unavailable";
    my $netflow                  = "unavailable";

    # database

    my $database_profile_installed = 0;

    #my $event_in_database = "unavailable";
    my $dbs_sizes = "unavailable";

    # logger

    my $logger_profile_installed = 0;
    my $logger_events_last_day   = "unavailable";
    my $logger_events_last_week  = "unavailable";
    my $logger_events_last_month = "unavailable";
    my $logger_events_last_years = "unavailable";

    # server

    my $server_profile_installed = 0;
    my $entity_total             = "unavailable";
    my $entity_enabled           = "unavailable";
    my $total_directives         = "unavailable";

    if ( map( /Server/, @profiles_arr ) ) {

        $server_profile_installed = 1;

        $entity_total
            = `cat /etc/ossim/server/directives.xml | grep '<!ENTITY' | wc -l`;
        $entity_total =~ s/\n//g;
        $entity_enabled
            = `cat /etc/ossim/server/directives.xml | grep '&.*\;' | wc -l`;
        $entity_enabled =~ s/\n//g;
        $total_directives = `rgrep "directive id" /etc/ossim/server/ | wc -l`;
        $total_directives =~ s/\n//g;

    }

    if ( map( /Database/, @profiles_arr ) ) {

        $database_profile_installed = 1;

        my @dbcontent = Avtools::mysql_size_databases;
        $dbs_sizes = join( "\n", @dbcontent );

    }

    #if ( map( /Framework/, @profiles_arr ) ) { }
    if ( map( /Sensor/, @profiles_arr ) ) {

        $sensor_profile_installed = 1;
        my @sensor_total_arr;
        my @sensor_detector_arr
            = split( /,\s*/, $config{'sensor_detectors'} );
        # DEBUG:
        my @sensor_monitor_arr = split( /,\s*/, $config{'sensor_monitors'} );
        push( @sensor_total_arr, @sensor_detector_arr );
        push( @sensor_total_arr, @sensor_monitor_arr );

        if ( $#sensor_total_arr < 0 ) {
            $plugins_enabled = 0;
        }
        else {
            $plugins_enabled = $#sensor_total_arr + 1;
        }

        if ( $#sensor_interfaces_ary < 0 ) {
            $sniffing_interfaces = "no";
        }
        else {
            $sniffing_interfaces = "yes";

        }

        $network_monitored = $config{'sensor_networks'};
        $netflow           = $config{'netflow'};
    }
    if ( map( /Logger/, @profiles_arr ) ) {
        $logger_profile_installed = 1;

    }

    my @interfacess = Avnetwork::get_interface_defs;
    my $system_dns  = Avnetwork::system_dns;
    my @sniffing_interfaces = @{ AV::Debian::Netifaces::network_sniffing_interfaces() };

    my @ifout;
    foreach (@interfacess) {
        my ( $name, $addrfam, $method, $options ) = @$_;
        push(@ifout,"interfaces[]=$name");
    }
    for ( @sniffing_interfaces ) {
        push @ifout, "interfaces[]=$_";
    }
    foreach (@interfacess) {
        my @ifacestring_properties;
        my ( $name, $addrfam, $method, $options ) = @$_;
        #push( @ifacestring_properties, "\n[$name]\n");
        push( @ifacestring_properties, "$name\[name\]=$name" );
        push( @ifacestring_properties, "$name\[addrfam\]=$addrfam" );
        push( @ifacestring_properties, "$name\[method\]=$method" );

        foreach my $option (@$options) {
            my ( $param, $value ) = @$option;

            push( @ifacestring_properties, "$name\[$param\]=$value" );
        }

        my $stat = Avnetwork::iface_statistic($name);
        push( @ifacestring_properties, $stat );

        my $ifoutstring = join( "\n", @ifacestring_properties );
        push( @ifout, $ifoutstring );

        #console_log("$ifout");
    }
    for ( @sniffing_interfaces ) {
        my @ifacestring_properties;
        my $name = $_;
        my $vpn_stat = q{};
        push @ifacestring_properties, "$name\[name\]=$name";
        push @ifacestring_properties, "$name\[addrfam\]=inet";

        if ( $name =~ /tun/ ) {
            push @ifacestring_properties, "$name\[method\]=vpn";
            $vpn_stat = AV::Debian::Netifaces::vpn_tun_network_stats($name);
            push @ifacestring_properties, @$vpn_stat;
        }
        else {
            push @ifacestring_properties, "$name\[method\]=sensor";
        }

        my $stat = Avnetwork::iface_statistic($name);
        push @ifacestring_properties, $stat;

        my $ifoutstring = join( "\n", @ifacestring_properties );
        push( @ifout, $ifoutstring );

    }
    my $ifouttotal = join( "\n", @ifout );

    my @todo = (
        "$systemuuid",
        "[System Status]",
        "status=UP",
        "system_time=$system_time",
        "system_uptime=$system_uptime",
        "running_proc=$number_proccess",
        "loadaverage=$loadaverage",
        "memtotal=$memtotal",
        "memused=$memused_format",
        "memfree=$memfree",
        "percent_memused=$percent_memused_print",
        "virtualmem=$virtualmem",
        "virtualmemused=$virtualmemused_format",
        "virtualmemfree=$virtualmemfree",
        "percent_virtualmemused=$percent_virtualmemused_print",
        "current_sessions=$current_sessions", "",
        "$cpu_cores",
        "$disk_usage",
        "[Alienvault Status]",
        "sensor_profile_installed=$sensor_profile_installed",
        "sensor_plugins_enabled=$plugins_enabled",
        "sensor_sniffing_interfaces=$config{'sensor_interfaces'}",
        "sensor_network_monitored=$network_monitored",
        "sensor_netflow=$netflow",
        "database_profile_installed=$database_profile_installed",
        "$dbs_sizes",
        "logger_profile_installed=$logger_profile_installed",
        "logger_events_last_day=$logger_events_last_day",
        "logger_events_last_week=$logger_events_last_week",
        "logger_events_last_month=$logger_events_last_month",
        "logger_events_last_years=$logger_events_last_years",
        "server_profile_installed=$server_profile_installed",
        "server_entity_total=$entity_total",
        "server_entity_enabled=$entity_enabled",
        "server_total_directives=$total_directives",
        "\n[Network Status]",
        "general[firewall_active]=$config{'firewall_active'}",
        "general[internet_connection]=yes",
        "general[dns_servers]=$system_dns",
        "general[vpn_access]=$config{'vpn_infraestructure'}",
        "$ifouttotal"

    );

    return @todo;

}

sub get_system_status {

    my ( $funcion_llamada, $nombre, $uuid, $admin_ip, $hostname ) = @_;

    verbose_log_file(
        "GET SYSTEM STATUS    : Received call from $uuid : ip source = $admin_ip, hostname = $hostname:($funcion_llamada,$nombre)"
    );

    my $system_time = Avtools::get_system_time;

    #console_log("Time on system $hs");

    my $system_uptime = Avtools::get_system_uptime;

    #console_log("System uptime $hs");

    my @his             = Avtools::list_processes;
    my $number_proccess = scalar(@his);

    #console_log("Running processes $pr");

    #my @hs = Avtools::get_current_cpu_temps;
    #foreach(@hs){
    #	console_log("CPU Temp $_");
    #}

    my $cpu_cores = Avtools::get_cpu_cores_info;

    my ( $loadaverage, $current_sessions )
        = split( ';', Avtools::get_cpu_info() );

    #console_log("CPU load averages $hs");

    my %hs = Avtools::get_memory_info;

    my $memtotal              = Avtools::nice_size( $hs{'memtotal'} * 1024 );
    my $memfree               = Avtools::nice_size( $hs{'memfree'} * 1024 );
    my $memused               = $hs{'memtotal'} - $hs{'memfree'};
    my $percent_memused       = ( ( $memused * 100 ) / $hs{'memtotal'} );
    my $percent_memused_print = sprintf( '%.2f', $percent_memused );
    my $memused_format        = Avtools::nice_size( $memused * 1024 );
    my $virtualmem            = Avtools::nice_size( $hs{'swaptotal'} * 1024 );
    my $virtualmemfree        = Avtools::nice_size( $hs{'swapfree'} * 1024 );
    my $virtualmemused        = $hs{'swaptotal'} - $hs{'swapfree'};
    my $virtualmemused_format = Avtools::nice_size( $virtualmemused * 1024 );
    my $percent_virtualmemused
        = ( ( $virtualmemused * 100 ) / $hs{'memtotal'} );
    my $percent_virtualmemused_print
        = sprintf( '%.2f', $percent_virtualmemused );
    my $disk_usage = Avtools::get_disk_usage_info;

    #my $current_sessions = Avtools::get_current_sessions;

    my $hastatus = Avtools::ha_status();

    # profiles cond.

 
	
    my @todo = (
		"$systemuuid",
        "[System Status]",
        "status=UP",
        "HAstatus=$hastatus",
        "system_time=$system_time",
        "system_uptime=$system_uptime",
        "running_proc=$number_proccess",
        "loadaverage=$loadaverage",
        "memtotal=$memtotal",
        "memused=$memused_format",
        "memfree=$memfree",
        "percent_memused=$percent_memused_print",
        "virtualmem=$virtualmem",
        "virtualmemused=$virtualmemused_format",
        "virtualmemfree=$virtualmemfree",
        "percent_virtualmemused=$percent_virtualmemused_print",
        "current_sessions=$current_sessions", "",
        "$cpu_cores",
        "$disk_usage"
    

    );

    return \@todo;

}

sub get_alienvault_status {

    my ( $funcion_llamada, $nombre, $uuid, $admin_ip, $hostname ) = @_;

    verbose_log_file(
        "GET AV STATUS    : Received call from $uuid : ip source = $admin_ip, hostname = $hostname:($funcion_llamada,$nombre)"
    );


    # profiles cond.

    my %config                = AV::ConfigParser::current_config();
    my @profiles_arr          = split /,\s*/, $config{'profile'};
    my @sensor_interfaces_ary = split /,\s*/, $config{'sensor_interfaces'};

    # sensor
    my $plugins_enabled          = "unavailable";
    my $sensor_profile_installed = 0;
    my $sniffing_interfaces      = "unavailable";
    my $network_monitored        = "unavailable";
    my $netflow                  = "unavailable";

    # database

    my $database_profile_installed = 0;

    #my $event_in_database = "unavailable";
    my $dbs_sizes = "unavailable";

    # logger

    my $logger_profile_installed = 0;
    my $logger_events_last_day   = "unavailable";
    my $logger_events_last_week  = "unavailable";
    my $logger_events_last_month = "unavailable";
    my $logger_events_last_years = "unavailable";

    # server

    my $server_profile_installed = 0;
    my $entity_total             = "unavailable";
    my $entity_enabled           = "unavailable";
    my $total_directives         = "unavailable";

    if ( map( /Server/, @profiles_arr ) ) {

        $server_profile_installed = 1;

        $entity_total
            = `cat /etc/ossim/server/directives.xml | grep '<!ENTITY' | wc -l`;
        $entity_total =~ s/\n//g;
        $entity_enabled
            = `cat /etc/ossim/server/directives.xml | grep '&.*\;' | wc -l`;
        $entity_enabled =~ s/\n//g;
        $total_directives = `rgrep "directive id" /etc/ossim/server/ | wc -l`;
        $total_directives =~ s/\n//g;

    }

    if ( map( /Database/, @profiles_arr ) ) {

        $database_profile_installed = 1;

        my @dbcontent = Avtools::mysql_size_databases;
        $dbs_sizes = join( "\n", @dbcontent );

    }

    #if ( map( /Framework/, @profiles_arr ) ) { }

    my $hastatus = Avtools::ha_status();

    if ( map( /Sensor/, @profiles_arr ) ) {

        $sensor_profile_installed = 1;
        my @sensor_total_arr;
        my @sensor_detector_arr
            = split( /,\s*/, $config{'sensor_detectors'} );
        my @sensor_monitor_arr = split /,\s*/, $config{'sensor_monitors'};
        push( @sensor_total_arr, @sensor_detector_arr );
        push( @sensor_total_arr, @sensor_monitor_arr );

        if ( $#sensor_total_arr < 0 ) {
            $plugins_enabled = 0;
        }
        else {
            $plugins_enabled = $#sensor_total_arr + 1;
        }

        if ( $#sensor_interfaces_ary < 0 ) {
            $sniffing_interfaces = "no";
        }
        else {
            $sniffing_interfaces = "yes";

        }

        $network_monitored = $config{'sensor_networks'};
        $netflow           = $config{'netflow'};
    }
    if ( map( /Logger/, @profiles_arr ) ) {
        $logger_profile_installed = 1;

    }

    my @todo = (
		"$systemuuid",
        "[Alienvault Status]",
        "status=UP",
        "HAstatus=$hastatus",
        "sensor_profile_installed=$sensor_profile_installed",
        "sensor_plugins_enabled=$plugins_enabled",
        "sensor_sniffing_interfaces=$config{'sensor_interfaces'}",
        "sensor_network_monitored=$network_monitored",
        "sensor_netflow=$netflow",
        "database_profile_installed=$database_profile_installed",
        "$dbs_sizes",
        "logger_profile_installed=$logger_profile_installed",
        "logger_events_last_day=$logger_events_last_day",
        "logger_events_last_week=$logger_events_last_week",
        "logger_events_last_month=$logger_events_last_month",
        "logger_events_last_years=$logger_events_last_years",
        "server_profile_installed=$server_profile_installed",
        "server_entity_total=$entity_total",
        "server_entity_enabled=$entity_enabled",
        "server_total_directives=$total_directives"

    );

    return \@todo;

}


sub get_network_status {

    my ( $funcion_llamada, $nombre, $uuid, $admin_ip, $hostname ) = @_;

    verbose_log_file(
        "GET NETWORK   : Received call from $uuid : ip source = $admin_ip, hostname = $hostname:($funcion_llamada,$nombre)"
    );

    my %config      = AV::ConfigParser::current_config();
    my @interfacess = Avnetwork::get_interface_defs;
    my $system_dns  = Avnetwork::system_dns;
    my @sniffing_interfaces = @{ AV::Debian::Netifaces::network_sniffing_interfaces() };

    my @ifout;
    foreach (@interfacess) {
		my ( $name, $addrfam, $method, $options ) = @$_;
		push(@ifout,"interfaces[]=$name");
	}
    for ( @sniffing_interfaces ) {
        push @ifout, "interfaces[]=$_";
    }
    foreach (@interfacess) {
        my @ifacestring_properties;
        my ( $name, $addrfam, $method, $options ) = @$_;
        #push( @ifacestring_properties, "\n[$name]\n");
        push( @ifacestring_properties, "$name\[name\]=$name" );
        push( @ifacestring_properties, "$name\[addrfam\]=$addrfam" );
        push( @ifacestring_properties, "$name\[method\]=$method" );

        foreach my $option (@$options) {
            my ( $param, $value ) = @$option;

            push( @ifacestring_properties, "$name\[$param\]=$value" );

        }
        my $stat = Avnetwork::iface_statistic($name);
        push( @ifacestring_properties, $stat );

        my $ifoutstring = join( "\n", @ifacestring_properties );
        push( @ifout, $ifoutstring );

        #console_log("$ifout");
    }
    for ( @sniffing_interfaces ) {
        my @ifacestring_properties;
        my $name = $_;
        my $vpn_stat = q{};
        push @ifacestring_properties, "$name\[name\]=$name";
        push @ifacestring_properties, "$name\[addrfam\]=inet";

        if ( $name =~ /tun/ ) {
            push @ifacestring_properties, "$name\[method\]=vpn";
            $vpn_stat = AV::Debian::Netifaces::vpn_tun_network_stats($name);
            push @ifacestring_properties, @$vpn_stat;
        }
        else {
            push @ifacestring_properties, "$name\[method\]=sensor";
        }

        my $stat = Avnetwork::iface_statistic($name);
        push @ifacestring_properties, $stat;

        my $ifoutstring = join( "\n", @ifacestring_properties );
        push( @ifout, $ifoutstring );

    }
    my $ifouttotal = join( "\n", @ifout );

    my @todo = (
		"$systemuuid",
        "[Network Status]",
        "status=UP",
        "general[firewall_active]=$config{'firewall_active'}",
        "general[internet_connection]=yes",
        "general[dns_servers]=$system_dns",
        "general[vpn_access]=$config{'vpn_infraestructure'}",
        "$ifouttotal"
    );

    return \@todo;

}


sub get_network {

    my ( $funcion_llamada, $nombre, $uuid, $admin_ip, $hostname ) = @_;

    verbose_log_file(
        "GET NETWORK   : Received call from $uuid : ip source = $admin_ip, hostname = $hostname:($funcion_llamada,$nombre)"
    );

    my %config      = AV::ConfigParser::current_config();
    my @interfacess = Avnetwork::get_interface_defs;
    my $system_dns  = Avnetwork::system_dns;
    my @sniffing_interfaces = @{ AV::Debian::Netifaces::network_sniffing_interfaces() };

    my @ifout;
    foreach (@interfacess) {
        my @ifacestring_properties;
        my ( $name, $addrfam, $method, $options ) = @$_;
        push( @ifacestring_properties, "\n[Network;$name]\nname=$name" );
        push( @ifacestring_properties, "addrfam=$addrfam" );
        push( @ifacestring_properties, "method=$method" );

        foreach my $option (@$options) {
            my ( $param, $value ) = @$option;

            push( @ifacestring_properties, "$param=$value" );

        }

        my $stat = Avnetwork::iface_statistic($name);
        push( @ifacestring_properties, $stat );

        my $ifoutstring = join( "\n", @ifacestring_properties );
        push( @ifout, $ifoutstring );

        #console_log("$ifout");
    }

    for ( @sniffing_interfaces ) {
        my @ifacestring_properties;
        my $name = $_;
        my $vpn_stat = q{};
        push @ifacestring_properties, "$name\[name\]=$name";
        push @ifacestring_properties, "$name\[addrfam\]=inet";

        if ( $name =~ /tun/ ) {
            push @ifacestring_properties, "$name\[method\]=vpn";
            $vpn_stat = AV::Debian::Netifaces::vpn_tun_network_stats($name);
            push @ifacestring_properties, @$vpn_stat;
        }
        else {
            push @ifacestring_properties, "$name\[method\]=sensor";
        }

        my $stat = Avnetwork::iface_statistic($name);
        push @ifacestring_properties, $stat;

        my $ifoutstring = join( "\n", @ifacestring_properties );
        push( @ifout, $ifoutstring );

    }


    my $ifouttotal = join( "\n", @ifout );

    my @todo = (
		"$systemuuid",
        "[general]",
        "status=UP",
        "firewall_active=$config{'firewall_active'}",
        "internet_connection=yes",
        "dns_servers=$system_dns",
        "vpn_access=$config{'vpn_infraestructure'}",
        "$ifouttotal"
    );

    return @todo;

}

sub get_statistics {

    my ( $funcion_llamada, $nombre, $uuid, $admin_ip, $hostname ) = @_;

    verbose_log_file(
        "GET STATISTICS: Received call from $uuid : ip source = $admin_ip, hostname = $hostname:($funcion_llamada,$nombre)"
    );

    my %config = AV::ConfigParser::current_config();

    my @p_database;
    my @p_server;
    my @p_framework;
    my @p_sensor;
    my @profiles_arr;

    if ( $config{'profile'} eq "all-in-one" ) {
        @profiles_arr = ( "Server", "Database", "Framework", "sensor" );
    }
    else {
        @profiles_arr = split( /,\s*/, $config{'profile'} );
    }

    foreach my $profile (@profiles_arr) {

        given ($profile) {
            when ( m/Database/ ) { @p_database = ("mysql"); }
            when ( m/Server/ )   { @p_server   = "ossim-server"; }
            when ( m/Framework/ ) {
                @p_framework = (
                    "ossim-framework", "perl -w /usr/nfsen/bin/nfsend",
                    "nfsend-comm"
                );
            }
            when ( m/Sensor/ ) {
                @p_sensor = ( "ossim-agent", "snort", "fprobe" );
            }
        }

    }
    my @process;
    push( @process, @p_database );
    push( @process, @p_server );
    push( @process, @p_framework );
    push( @process, @p_sensor );

    my %sysconf = Avstatistic::get_iostat_pstat(@process);

    my $content;
    for my $key ( keys %sysconf ) {
        my $value = $sysconf{$key};
        $content = " $key => $value" . "---" . $content;

    }

    return "$content\n";

}

sub get_log_line {
	my ( $funcion_llamada, $nombre, $uuid, $admin_ip, $hostname, $r_file, $number_lines )
        = @_;
        
    verbose_log_file(
        "GET LOG LINE  : Received call from $uuid : ip source = $admin_ip, hostname = $hostname :($funcion_llamada,$r_file)"
    );
    
    my @ret = ("$systemuuid");
    
    if ( $r_file =~ /\.\./ ){
			push(@ret,"File not auth");
			return \@ret;
	}
	
	$number_lines = int($number_lines);
	if ( $number_lines <= 0 ) {
			push(@ret,"Error in number lines");				
			return \@ret;
	}
	
    if (( $r_file =~ /^\/var\/log\// ) or ( $r_file =~ /^\/var\/ossec\/alerts\// ) or ( $r_file =~ /^\/var\/ossec\/logs\// )){
			if (! -f "$r_file" ){
				push(@ret,"File not found");				
				return \@ret;
			}
			push(@ret,"ready");			
			
			my $command = "tail -$number_lines $r_file";
			#push(@ret,"$command");
			#my @content = `tail -$number_lines $r_file`;
			my @content = `$command`;
			push(@ret,@content);
			return \@ret;
	}
    else {
		push(@ret,"path not auth");
		return \@ret;
	}
}

sub get_service {
	my ( $funcion_llamada, $nombre, $uuid, $admin_ip, $hostname, $service) = @_;
    my @ret = ( $uuid );
	
	verbose_log_file(
        "GET SERVICE  : Received call from $uuid : ip source = $admin_ip, hostname = $hostname :($funcion_llamada,$service)"
    );
    my @service_list;

    if ( $service eq q{all} ) {
        @service_list = ( qw{ prads snort ntop ossec suricata } );
    }
    else {
        if ( /$service/ ~~ [ qw{ all ntop ossec prads snort suricata ossim-agent} ] ) {
            @service_list = ( $service );
        }
        else {
            push @ret, 'invalid service (only services allowed: ntop ossec prads snort suricata';
            return \@ret;
        }
    }
    #given ($action) {
    #    when ('status') { 
            for my $srv ( @service_list ) {
                my @stat = qx{ ps aux | grep $srv | egrep -v grep };
                my $status = ( @stat > 0 ) ? 'UP' : 'DOWN';
                push @ret, "$srv=$status";
            }
    #    }
    #}
    return \@ret;
}







sub get_dpkg {

    my ( $funcion_llamada, $nombre, $uuid, $admin_ip, $hostname ) = @_;

    verbose_log_file(
        "GET DPKG      : Received call from $uuid : ip source = $admin_ip, hostname = $hostname:($funcion_llamada,$nombre)"
    );
    my @out = ("$systemuuid");

    my @dpkg_content = `dpkg -l`;

    foreach (@dpkg_content) {
        push( @out, "$_" );
    }

    #my @ret=("$dpkg_content","$systemuuid");
    #my @ret=`dpkg -l`;
    return \@out;

}

sub get_repository {

    my ( $funcion_llamada, $nombre, $uuid, $admin_ip, $hostname ) = @_;

    verbose_log_file(
        "GET REPOSITORY : Received call from $uuid : ip source = $admin_ip, hostname = $hostname:($funcion_llamada,$nombre)"
    );

    my %sysconf = Avrepository::get_current_repository_info();

    #my $content;
    my @ret = ("$systemuuid");
    for my $key ( keys %sysconf ) {
        my $value = $sysconf{$key};

        #$content = " $key => $value" . "---" . $content;
        if ( $key eq "distro" ) {
            push( @ret, $value );
        }
        if ( $key eq "code" ) {
            push( @ret, $value );
        }

    }

    #return "$content";
    return \@ret;
}

sub update_system_info() {

    my ( $funcion_llamada, $nombre, $uuid, $admin_ip, $hostname ) = @_;
    verbose_log_file(
        "GET UPDATE-INFO : Received call from $uuid : ip source = $admin_ip, hostname = $hostname:($funcion_llamada,$nombre)"
    );

    my @ret = AvupdateSystem::system_update_proccess_info();
    
    #foreach(@ret){
	
	#		verbose_log_file("Devuelve : $_");
		
	#}
    return \@ret;

}

sub update_system_info_debian_package() {

    my ( $funcion_llamada, $nombre, $uuid, $admin_ip, $hostname, $debian_pkg )
        = @_;
    verbose_log_file(
        "GET UPDATE-INFO-DEBIAN-PACKAGE : Received call from $uuid : ip source = $admin_ip, hostname = $hostname:($funcion_llamada,$nombre)"
    );

    if (!AV::CC::Validate::ossim_valid($debian_pkg, 'OSS_SCORE,OSS_DOT,OSS_ALPHA'))
    {
        console_log_file("GET UPDATE-INFO-DEBIAN-PACKAGE : Not allowed value $debian_pkg\n");
        my @ret = ("Error");
        return \@ret;
    }

    verbose_log_file("-> update debian package info in progress");
    my $content = `/usr/bin/aptitude changelog $debian_pkg `;
    my @ret = ( "$content", "$systemuuid" );

    return \@ret;

}

sub update_system() {

    my ( $funcion_llamada, $nombre, $uuid, $admin_ip, $hostname, $feed ) = @_;
    verbose_log_file(
        "GET UPDATE : Received call from $uuid : ip source = $admin_ip, hostname = $hostname:($funcion_llamada,$nombre,$feed)"
    );

    if (!AV::CC::Validate::ossim_valid($feed, 'OSS_SCORE,OSS_LETTER,OSS_NULLABLE'))
    {
        console_log_file("GET UPDATE : Not allowed value $feed\n");
        my @ret = ("Error");
        return \@ret;        
    }
    
	my @ret = AvupdateSystem::system_update( $feed );
    return \@ret;

    my $lckfile = "/usr/share/alienvault-center/run/avsoap_update.lck";
    my $status  = 0;
    my @out;

    # apt-get update
    if ( -f $lckfile ) {

        verbose_log_file("-> Update proccess current in exec, abort.");

    }
    else {

        system("touch $lckfile");
        @out    = `apt-get update 2>&1 ; echo \$? > $lckfile`;
        $status = `cat $lckfile`;
        $status =~ s/\n//g;

        if ( $status == 0 ) {

            system("rm $lckfile");
        }
        else {
            $status = `cat $lckfile`;
            $status =~ s/\n//g;
            system("rm $lckfile");

            return \@out;

        }

    }

    # apt-get dist-upgrade

    if ( $status == 0 ) {

        system("touch $lckfile");
        verbose_log_file("-> dist-upgrade in progress");

#my $content = `apt-get dist-upgrade --assume-yes  --allow-unauthenticated  ; echo \$? > $lckfile`;
        my $content = `alienvault-update -v ; echo \$? > $lckfile`;
        $status = `cat $lckfile`;
        $status =~ s/\n//g;
        my @ret = ( "$content", "$systemuuid" );

        if ( $status == 0 ) {
            verbose_log_file("-> dist-upgrade success");
            system("rm $lckfile");

            #@out = ("success");
            return \@ret;
        }
        else {
            verbose_log_file("-> dist-upgrade error");
            $status = `cat $lckfile`;
            $status =~ s/\n//g;
            system("rm $lckfile");
            return \@ret;

        }

    }

}

sub upgrade_pro_web() {
    my ( $funcion_llamada, $nombre, $uuid, $admin_ip, $hostname, $pro_key ) = @_;
    verbose_log_file(
        "UPGRADE PRO WEB : Received call from $uuid : ip source = $admin_ip, hostname = $hostname:($funcion_llamada,$nombre,$pro_key)"
    );

    if (!AV::CC::Validate::ossim_valid($pro_key, 'OSS_SCORE,OSS_ALPHA'))
    {
        console_log_file("UPGRADE PRO WEB : Not allowed value $pro_key\n");
        my @ret = ("Error");
        return \@ret;        
    }
    
    my @ret = AvupdateSystem::system_update( "-uc=$pro_key" );
    return \@ret;
}

sub upgrade_system() {

    my ( $funcion_llamada, $nombre, $uuid, $admin_ip, $hostname ) = @_;
    verbose_log_file(
        "GET UPDATE : Received call from $uuid : ip source = $admin_ip, hostname = $hostname:($funcion_llamada,$nombre)"
    );

	my @ret = AvupdateSystem::system_update();
    return \@ret;

}

sub reconfig_system() {
    my ( $funcion_llamada, $nombre, $uuid, $admin_ip, $hostname ) = @_;
    verbose_log_file(
        "GET RECONFIG : Received call from $uuid : ip source = $admin_ip, hostname = $hostname:($funcion_llamada,$nombre)"
    );

	my @ret = AvExecReconfig::system_reconfig();
    return \@ret;	
	
}

sub get_current_task() {
    my ( $funcion_llamada, $nombre, $uuid, $admin_ip, $hostname ) = @_;

    verbose_log_file(
        "GET CURRENT TASK : Received call from $uuid :"
        . " ip source = $admin_ip, "
        . " hostname = $hostname:($funcion_llamada,$nombre)"
    );

    # Sometimes $thrstat{update} remains in '... Finished' instead of 'empty'
    # Maybe related to $SIG{CHLD} not being 'IGNORE' when calling child
    # FIXME: find a solution to this problem
    # Idea: $SIG{CHLD} = 'IGNORE'; ...call child... $SIG{CHLD} = 'DEFAULT';

    if ( AV::CC::SharedData->get_value_of('update') =~ m{Finished} ) {
        debug_log_file('corrected: update = (empty), was: ... Finished');
        AV::CC::SharedData->set('update', 'empty');
    }

    my @out;
    my %thrstat = AV::CC::SharedData->get(); 

    while ( my ( $key, $value ) = each %thrstat ) {
        debug_log_file("$key = ($value)");
        push @out, "$key=$value";
    }

    return \@out;
}

sub get_last_update_task() {
    my ( $funcion_llamada, $nombre, $uuid, $admin_ip, $hostname ) = @_;
    verbose_log_file(
        "GET LAST UPDATE TASK : Received call from $uuid : ip source = $admin_ip, hostname = $hostname:($funcion_llamada,$nombre)"
    );

    my @out;
    if ( AV::CC::SharedData->get_value_of('update') eq 'empty' ) {
        push @out, 'update in progress...';
        return \@out;
    }

    my $logfile = '/var/log/alienvault-center_update.log';
    if ( -r $logfile ) {
        open my $fh, q{<}, $logfile;
        push @out, <$fh>;
        close $fh;
    }
    else {
        push @out, 'no update registered';
    }
    return \@out;
}


# Sync local database using data from a child server.




1;
