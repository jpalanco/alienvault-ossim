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

package Avconfig_profile_sensor;

use v5.10;
#use strict;
#use warnings;
#use diagnostics;

use AV::ConfigParser;
use AV::Log;
use AV::Debian::Suricata;

use Config::Tiny;
use Time::Local;
use Perl6::Slurp;
use File::Touch;

my %config_agent_orig;
my $script_msg
    = "# Automatically generated for ossim-reconfig scripts. DO NOT TOUCH!";
my $agentcfg            = "/etc/ossim/agent/config.cfg";
my $plugins_cfg_basedir = '/etc/ossim/agent/plugins';
my $snort_cfg           = "/etc/snort/snort.conf";
my $snort_debian_cfg    = "/etc/snort/snort.debian.conf";
my $snort_unified_cfg   = "/etc/ossim/agent/plugins/snortunified.cfg";
my $fprobeconfig        = "/etc/default/fprobe";
my $rsyslogdefault_file = "/etc/default/rsyslog";
my $monit_file          = "/etc/monit/alienvault/avsensor.monitrc";
my $agentcfgorig        = "/etc/ossim/agent/config.cfg.orig";
my $prads_default_file  = '/etc/default/prads';
my $snort_default_debian_file = "/etc/default/snort";
my $config_file         = '/etc/ossim/ossim_setup.conf';
my $prads_config_file   = "/etc/prads/prads.conf";

#FIXME Unknown source variables
my $server_hostname;
my $server_ip;
my $admin_ip;
my $framework_port;
my $framework_host;
my $db_pass;
my @profiles_arr;
my ( $stdout, $stderr ) = ( q{}, q{} );
my $conn;
my @sensor_interfaces_arr;
my @sensor_interfaces_arr_last;
my $tmp_interface = q{};
# FIXME: $tmp_interface is an empty string at this point!
my $snort_conf_name = "/etc/snort/snort.$tmp_interface.conf";
my $profile_framework;
my %reset;
my @mservers_arr;
my $trimmed_sensor_networks;


sub config_profile_sensor() {

    my %config      = AV::ConfigParser::current_config;
    my %config_last = AV::ConfigParser::last_config;

    $server_hostname = $config{'hostname'};
    $server_ip       = $config{'server_ip'};
    $admin_ip        = $config{'admin_ip'};
    $framework_port  = $config{'framework_port'};
    $framework_host  = $config{'framework_ip'};
    $db_pass         = $config{'database_pass'};
    $trimmed_sensor_networks = $config{'sensor_networks'};
    $trimmed_sensor_networks =~ s/\s+//g;

    if ( $config{'profile'} eq "all-in-one" ) {
        @profiles_arr = ( "Server", "Database", "Framework", "Sensor" );
    }
    else {
        @profiles_arr = split( /,\s*/, $config{'profile'} );
    }

    @sensor_interfaces_arr = split( /,\s*/, $config{'sensor_interfaces'} );
    @profiles_arr          = split( /,\s*/, $config{'profile'} );

    @sensor_interfaces_arr_last
        = split( /,\s*/, $config_last{'sensor_interfaces'} );

    @mservers_arr = split( /;\s*/, ( $config{'mservers'} // q{} ) );

    my $profile_server = 0;
    my $profile_framework = 0;
    my $profile_database = 0;

    foreach my $profile (@profiles_arr) {

        given ($profile) {
            when ( m/Server/ )    { $profile_server = 1; }
            when ( m/Framework/ ) { $profile_framework = 1; }
            when ( m/Database/ )  { $profile_database = 1; }
        }

    }

    console_log(
        "-------------------------------------------------------------------------------"
    );
    console_log("Config Sensor Profile");
    dp("Config Sensor Profile");

    console_log("Update OSSEC plugin reference");
    dp("Update OSSEC plugin reference");

    my @ossec_config=slurp {chomp => 1}, $config_file;
    my @detectors=grep ( /detectors/, @ossec_config );
    my @detector_list = split /,\s*/, $detectors[0];
    $detector_list[0] =~ s/detectors=//g;

    # Compatibily ossec plugin mode
    for my $item ( @detector_list ) {
        given ($item) {
            when ( m/ossec-idm/ ) { $item = 'ossec-idm-single-line'; }
            when ( m/ossec/ )     { $item = 'ossec-single-line'; }
        }
    }
    my %d_list=();
    for my $item ( @detector_list ) {
        $d_list{$item}++;
    }
    my $new_detector_list = join ', ', keys(%d_list);
    
    system (qq{sed -i "s:^detectors=.*:detectors=$new_detector_list:g" $config_file});

    # prads initscript, support for -l, source LOGFILE from default conffile
    if ( -f "/etc/init.d/prads" ) {
        console_log("Sensor Profile: prads initscript: add -l (LOGFILE) configuration support and quiet");
        my $command="sed -i 's:HOME_NETS}\\\"}.*:HOME_NETS}\\\"} \${LOGFILE\\\:+-l \\\"\${LOGFILE}\\\"} > /dev/null 2>\\\&1:' /etc/init.d/prads";
        debug_log($command);
        system($command);

	my $command_q="sed -i 's:^DAEMON_OPTS=\\\"-D:DAEMON_OPTS=\\\"-q -D:' /etc/init.d/prads";
	debug_log($command_q);
	system($command_q);
    }
    else {
        verbose_log("Sensor Profile: (initscript prads not found)");
    }

    console_log("Sensor Profile: Update PRADS network configuration");
    verbose_log("Sensor Profile: Update PRADS network configuration (HOME_NETS)");
    if ( -f $prads_default_file ) {
        my $command = qq{sed -i 's:^HOME_NETS.*\$:HOME_NETS="$trimmed_sensor_networks":' $prads_default_file};
        debug_log($command);
        system($command);
    }
    else {
        verbose_log("$prads_default_file not found.");
    }

    if ( -f $prads_config_file ){

	my $command = qq{sed -i 's:^#arp=1:arp=1:' $prads_config_file};
        debug_log($command);
        system($command);

    }


    verbose_log("Sensor Profile: Update Agent interfaces");

    debug_log("Sensor Profile: Update interfaces");
    if ( scalar(@sensor_interfaces_arr) > 1 ) {
        $tmp_interface = "any";
    }
    else {
        $tmp_interface = $sensor_interfaces_arr[0];
    }
    my $command
        = "sed -i \"s/interface[ =].*/interface=$tmp_interface/\" $agentcfg";
    debug_log("$command");
    system($command);

    debug_log("Sensor Profile: Update sensor ip");

    $command
        = "sed -i \"s:sensor[ =].*:sensor = $config{'admin_ip'}:\" $agentcfg";
    debug_log("$command");
    system($command);

    debug_log("Sensor Profile: Update server ip");

    $command = "sed -i \"s:ip[ =].*:ip=$server_ip:\" $agentcfg";
    debug_log("$command");
    system($command);

    debug_log("Sensor Profile: Update sensor ctx");

    $command = "sed -i \"s:ctx[ =].*:ctx=$config{'sensor_ctx'}:\" $agentcfg";
    debug_log("$command");
    system($command);



    if ( $profile_server == 1 ) {
        if ( $server_ip eq "127.0.0.1" ) {
            debug_log(
                "Sensor Profile: 'Server' profile found in the same host and server_ip is 127.0.0.1"
            );
            debug_log(
                "Sensor Profile: Setting $config{'admin_ip'} for connecting to server"
            );

            $command
                = "sed -i \"s:ip[ =].*:ip=$config{'admin_ip'}:\" $agentcfg";
            debug_log("$command");
            system($command);
        }
    }



#	if ( -f "/etc/init.d/snort" ){
#
#			my $avfunc = `grep /etc/alienvaultfunctions /etc/init.d/snort`; $avfunc =~ s/\n//g;
#			if ( $avfunc eq "" ){
#
#					system("sed -i \"s:/lib/lsb/init-functions:/lib/lsb/init-functions ; \\\. /etc/alienvaultfunctions:g\" /etc/init.d/snort");
#
#			}
#
#
#
#	}

    debug_log("Sensor Profile: Update rrd plugin conection");

    $command
        = "sed -i \"s/ossim_dsn[ =].*/ossim_dsn=mysql:$db_host:alienvault:root:$db_pass/\" $agentcfg";
    debug_log("$command");
    system($command);

    debug_log("Sensor Profile: Update default tzone");

    $command
        = "sed -i \"s:tzone[ =].*:tzone=$config{'sensor_tzone'}:\" $agentcfg";
    debug_log("$command");
    system($command);

    # add compatibility for distributed scanner

    verbose_log("Sensor Profile: Parse agent config file");

    my $pars_conf_comp = Config::Tiny->read($agentcfg);
    if ( !defined $pars_conf_comp ) {
        error(
            "Couldn't read/parse $agentcfg: '$Config::Tiny->strerr', install ossim-agent (apt-get install ossim-agent)"
        );
    }

    my $getagentprop = sub {
        my $section  = shift;
        my $property = shift;

        return $pars_conf_comp->{$section}->{$property};
    };

    $config_agent_orig{'enable'} = &$getagentprop( "control-framework", "enable" );
    $config_agent_orig{'id'}   = &$getagentprop( "control-framework", "id" );
    $config_agent_orig{'ip'}   = &$getagentprop( "control-framework", "ip" );
    $config_agent_orig{'port'} = &$getagentprop( "control-framework", "port" );
    $config_agent_orig{'send_events'} = &$getagentprop( "output-server", "send_events" );

    my $chsection         = "control-framework";
    my $servsection       = "output-server";
    my $plugdefsection    = "plugin-defaults";
    my $outputservsection = "output-server-list";
    my $watchdogsection   = "watchdog";
    my $asecsection       = "asec";

    my $ConfigAgentFile_compatibility = Config::Tiny->read($agentcfg);

    if ( $config_agent_orig{'enable'} eq "" ) {
        verbose_log(
            "Sensor Profile: Compatibility Agent distributed mode: Agent enable framework control not found set True"
        );
        $ConfigAgentFile_compatibility->{$chsection}->{enable} = "True";
    }

    if ( $config_agent_orig{'id'} ne $config{'hostname'} ) {
        verbose_log(
            "Sensor Profile: Compatibility Agent distributed mode: Agent uniq id(hostname) not found set ($config{'hostname'})"
        );
        $ConfigAgentFile_compatibility->{$chsection}->{id}
            = $config{'hostname'};
    }

    if ( $config_agent_orig{'ip'} ne $config{'framework_ip'} ) {
        verbose_log(
            "Sensor Profile: Compatibility Agent distributed mode: Agent framework ip not found set $config{'framework_ip'}"
        );
        $ConfigAgentFile_compatibility->{$chsection}->{ip}
            = $config{'framework_ip'};
    }

    if ( $config_agent_orig{'port'} eq "" ) {
        verbose_log(
            "Sensor Profile: Compatibility Agent distributed mode: Agent framework port not found set $config{'framework_port'}"
        );
        $ConfigAgentFile_compatibility->{$chsection}->{port}
            = $config{'framework_port'};
    }

    if ( $config_agent_orig{'send_events'} eq "" ) {
        $config_agent_orig{'send_events'} = "True";
        verbose_log(
            "Sensor Profile: Compatibility Agent distributed mode: Agent framework send-events not found set True"
        );
        $ConfigAgentFile_compatibility->{$servsection}->{send_events}
            = "True";
    }

    if ( ( $config_agent_orig{'override_sensor'} // q{} ) ne ( $config{'override_sensor'} // q{} ) )
    {
        verbose_log(
            "Sensor Profile: Compatibility Agent distributed mode: Default Sensor Overrride not found, set $config{'override_sensor'}"
        );
        $ConfigAgentFile_compatibility->{$plugdefsection}->{override_sensor}
            = $config{'override_sensor'};
    }

    # Nomenclature (agent config): servername=SERVER_IP;PORT;SEND_EVENTS(True/False);ALLOW_FRMK_DATA(True/False);PRIORITY (0-5);FRMK_IP;FRMK_PORT
    # Example: ossimB=192.168.2.22;40001;True;True;1;192.168.2.22;40003
    delete $ConfigAgentFile_compatibility->{$outputservsection};
    my $mservers_num = 1;
    if ( $config{'mservers'} ne "no" ) {
        if (@mservers_arr) {
            foreach (@mservers_arr) {
                my @cm = split( /;\s*/, $_ );
                foreach (@cm) {
                    my @cms = split( /,\s*/, $_ );
                    my ($mserver_sip,        $mserver_sport,
                        $mserver_sendevents, $mserver_allowfdata,
                        $mserver_prio,       $mserver_fip,
                        $mserver_fport
                    ) = @cms;
                    my $msg
                        = "$mserver_sip;$mserver_sport;$mserver_sendevents;$mserver_allowfdata;$mserver_prio;$mserver_fip;$mserver_fport";
                    debug_log("Sensor Profile: writing multiserver entry: $msg");
                    $ConfigAgentFile_compatibility->{$outputservsection}->{"server$mservers_num"} = $msg;
                    ++$mservers_num;
                }
            }
        }
        $ConfigAgentFile_compatibility->{$servsection}->{enable} = "False";
        $ConfigAgentFile_compatibility->{$chsection}->{enable} = "False";
    }else{
        $ConfigAgentFile_compatibility->{$servsection}->{enable} = "True";
        $ConfigAgentFile_compatibility->{$chsection}->{enable} = "True";
    }

    my $config_watchdog_orig = Config::Tiny->read($agentcfgorig);
    delete $ConfigAgentFile_compatibility->{$watchdogsection};
    $ConfigAgentFile_compatibility->{$watchdogsection}->{enable} = $config_watchdog_orig->{$watchdogsection}->{'enable'};
    $ConfigAgentFile_compatibility->{$watchdogsection}->{interval} = $config_watchdog_orig->{$watchdogsection}->{'interval'};
    $ConfigAgentFile_compatibility->{$watchdogsection}->{restart_interval} = $config_watchdog_orig->{$watchdogsection}->{'restart_interval'};

    # simplify and don't use specific sections names in vars, don't reread from config...
    my $config_asec_orig = Config::Tiny->read($agentcfgorig);
    delete $ConfigAgentFile_compatibility->{$asecsection};
#    $ConfigAgentFile_compatibility->{$asecsection}->{ip} = $config_asec_orig->{$asecsection}->{'ip'};
    $ConfigAgentFile_compatibility->{$asecsection}->{ip} = $framework_host;
 #   $ConfigAgentFile_compatibility->{$asecsection}->{port} = $config_asec_orig->{$asecsection}->{'port'};
    $ConfigAgentFile_compatibility->{$asecsection}->{port} = "40005";
    if ( $config{'asec'} eq "yes" ) {
        $ConfigAgentFile_compatibility->{$asecsection}->{enable} = "True";
    }else{
        $ConfigAgentFile_compatibility->{$asecsection}->{enable} = "False";
    }

    #$ConfigAgentFile_compatibility->{output-server}->{ip} = $config{'server_ip'};
    #$ConfigAgentFile_compatibility->{output-server-pro}->{ip} = $config{'server_ip'};

    $ConfigAgentFile_compatibility->write($agentcfg);

    system("sed -i \"s/\\[0\\]/\[control-framework\]/\" $agentcfg");

    debug_log("Sensor Profile: Update Plugins");

    my $Config_plugins      = Config::Tiny->read($agentcfg);
    my $Config_plugins_orig = Config::Tiny->read($agentcfgorig);
    delete $Config_plugins->{plugins};

    # Normalize detector list, only allows either suricata or snort to be
    # enabled at once.
    my @list_input = split /,\s*/, $config{'sensor_detectors'};
    my @list_intermediate = ();
    my $found = 0;

    for my $item (@list_input) {
        if ( $item eq 'suricata' || $item eq 'snortunified' ) {
                next if $found;
                console_log("Sensor Profile: $item disabled. Suricata and Snort cannot be enabled at the same time");
                $found = 1;
        }
        push @list_intermediate, $item;
    }
    $config{'sensor_detectors'} = join ', ', @list_intermediate;

    foreach my $var ( split( /,\s*/, $config{'sensor_detectors'} ) ) {

	# Do not enable simple plugin for these when multiple interfaces selected
        if (   $var eq "snortunified" 
	    || $var eq "prads")
        {
            next;
        }

        $Config_plugins->{plugins}->{$var}
            = $Config_plugins_orig->{plugins}->{$var}
            // "$plugins_cfg_basedir/$var.cfg";
    }

    $config{database}{pass} = $db_pass;

    if (exists($Config_plugins->{plugins})){
        debug_log("Sensor Profile: plugins section found");
    }else{
        debug_log("Sensor Profile: plugins section not found, building an empty section");
        $Config_plugins->{plugins}->{";no_plugin_selected"} = "/path/to/plugin.cfg";
    }

    $Config_plugins->write($agentcfg);


    verbose_log("Sensor Profile: Config snort.conf");
    $command="sed -i \"s:^var[[:space:]]HOME_NET.*:var HOME_NET \[$trimmed_sensor_networks\]:\" $snort_cfg";
    debug_log("$command");
    system($command);

	if ( -d "/opt/rightscale"){
		verbose_log("Sensor Profile: Config snort default file");
		$command
		    = "sed -i \"s:PARAMS=.*:PARAMS=\\\"-m 027 -D -d \\\":\" $snort_default_debian_file";
		debug_log("$command");
		system($command);
	}else{
		verbose_log("Sensor Profile: Config snort default file");
		$command
		= "sed -i \"s:PARAMS=.*:PARAMS=\\\"-m 027 -D -d --daq-dir=/usr/lib/daq --daq pfring --daq-mode passive \\\":\" $snort_default_debian_file";
		debug_log("$command");
		system($command);
	}
	verbose_log("Sensor Profile: Config user for snort");

	$command
		= "sed -i \"s:COMMON=\\\"\\\$PARAMS -l \\\$LOGDIR -u \\\$SNORTUSER -g \\\$SNORTGROUP\\\":COMMON=\\\"\\\$PARAMS -l \\\$LOGDIR -u root -g \\\$SNORTGROUP\\\":\" /etc/init.d/snort";
	debug_log("$command");
    system($command);


    verbose_log("Sensor Profile: Config snort.debian.conf");
    $command
        = "sed -i \"s:DEBIAN_SNORT_HOME_NET=.*:DEBIAN_SNORT_HOME_NET=\\\"$trimmed_sensor_networks\\\":\" $snort_debian_cfg";
    debug_log("$command");
    system($command);

    verbose_log("Sensor Profile: Config $snort_conf_name --");

    my $snver = `cat /etc/snort/snort.conf|grep -v ^#|grep unified`;
    if ( $snver eq "" ) {
        my $command
            = "sed -i \"s/# output log_unified: filename snort.log, limit 128/output unified2: filename snort, limit 128/\" /etc/snort/snort.conf";
        debug_log("$command");
        system($command);
    }

    verbose_log("Sensor Profile: snort: check local.rules");
    if ( ! -d "/etc/snort/rules/" ) {
        $command="mkdir -p /etc/snort/rules";
        debug_log("$command");
        system($command);
    }
    if ( ! -f "/etc/snort/rules/local.rules" ) {
        $command="touch /etc/snort/rules/local.rules";
        debug_log("$command");
        system($command);
    }

    foreach my $var ( split( /,\s*/, $config{'sensor_detectors'} ) ) {

        #if ( scalar(@sensor_interfaces_arr) > 1){

        given ($var) {

            #		when ( m/snortunified/ ) { update_plugin_interfaces("$var"); }
            when ( m/prads/ )     { update_plugin_interfaces("$var"); }
            when ( m/suricata/ ) { AV::Debian::Suricata::update_config; }
        }

        #}

        given ($var) {
            when ( m/snortunified/ ) { update_plugin_interfaces("$var"); }

        }

     # Do not enable simple plugin for these when multiple interfaces selected
     #	next;

    }

    verbose_log("Sensor Profile: Config IDS rules flow control");

    foreach my $var ( split( /,\s*/, $config{'sensor_detectors'} ) ) {
        if ( $var eq "snortunified" ) {
            my $idsrfcauxs
                = "/usr/share/ossim-installer/auxscripts/ids_rules_flow_control.sh";
            if ( -f "$idsrfcauxs" ) {
                my $command = "/bin/bash $idsrfcauxs >/dev/null 2>&1";
                debug_log("$command");
                system($command);
            }
            else {
                verbose_log("$idsrfcauxs not found");
            }
        }
    }

    #verbose_log("Sensor Profile: Update logrotate");
    #	#my @detector_plugins_list;
    #	my @files=`ls /etc/ossim/agent/plugins/`;
    #	sub getprop3 {
    #		   my $section  = shift;
    #			my $property = shift;
    #
    #			return $pars_conf->{$section}->{$property};
    #	}
    #
    #	my %logrotateconfig;
    #
    #	foreach my $file (@files) {
    #
    #			my $ConfigAgentPluginFile      = Config::Tiny->new();
    #			$ConfigAgentPluginFile = Config::Tiny->read($file);
    #			$logrotateconfig{'source'} = getprop( "config", "source" );
    #			$logrotateconfig{'location'} = getprop( "config", "location" );
    #
    #			if ($logrotateconfig{'source'} eq "log"){
    #				push(@files_log,"$logrotateconfig{'location'}");
    #
    #			}
    #
    #    		#@plug=split( /\./,$file);
    #   			#push(@detector_plugins_list,"$plug[0]") if $file !~ m/-monitor/o ;
    #	}

    # fprobe

    my $dg            = system ('dpkg -l | grep alienvault-10g-tools > /dev/null');
    $dg >>= 8;

    if ( $dg != 0 ) {
        verbose_log("Sensor Profile: Config fprobe");
        my $fprobeinfaceis;
        if ( scalar(@sensor_interfaces_arr) > 1 ) {
            $fprobeinfaceis = "any";
        }
        else {
            $fprobeinfaceis = "$sensor_interfaces_arr[0]";
        }

        open FPROBEDEFAULTFILE, "> $fprobeconfig"
            or warning("Error open file: fprobe");
        print FPROBEDEFAULTFILE "$script_msg\n\n";
        print FPROBEDEFAULTFILE "#fprobe default configuration file\n";
        print FPROBEDEFAULTFILE "INTERFACE=\"$fprobeinfaceis\"\n";
        print FPROBEDEFAULTFILE
            "FLOW_COLLECTOR=\"$framework_host:$config{'netflow_remote_collector_port'}\"\n";
        print FPROBEDEFAULTFILE
            "#fprobe can't distinguish IP packet from other (e.g. ARP)\n";
        print FPROBEDEFAULTFILE "OTHER_ARGS=\"-fip\"\n";

        close(FPROBEDEFAULTFILE);

        verbose_log("Sensor Profile: Config $snort_conf_name --");

        $snver = `cat /etc/snort/snort.conf|grep -v ^#|grep unified`;
        if ( $snver eq "" ) {
            my $command
                = "sed -i \"s/# output log_unified: filename snort.log, limit 128/output unified2: filename snort, limit 128/\" /etc/snort/snort.conf";
            debug_log("$command");
            system($command);
        }
    }


    if ( $profile_server == 1 )
    {
        verbose_log("Sensor Profile: Add sensor to db.");


        my $tmp_change_sensor_info = 0;

        # change admin_ip

        if ( "$config{'admin_ip'}" ne "$config_last{'admin_ip'}" )
        {

            console_log(
                "Sensor Profile: Change admin ip (old=$config_last{'admin_ip'} new=$config{'admin_ip'}) update tables"
            );
            verbose_log("Sensor Profile: Update sensor and system tables");
            my $command
                = "echo \"UPDATE alienvault.sensor SET ip = inet6_pton(\'$config{'admin_ip'}\') WHERE inet6_ntop(ip) = \'$config_last{'admin_ip'}\'\" | ossim-db   $stdout $stderr ";
            debug_log($command);
            system($command);
            
            if ( ( $config{'ha_heartbeat_start'} // "" ) ne "yes" )
            {
                my $command
                    = "echo \"UPDATE alienvault.system SET admin_ip = inet6_pton(\'$config{'admin_ip'}\') WHERE inet6_ntop(admin_ip) = \'$config_last{'admin_ip'}\'\" | ossim-db  $stdout $stderr ";
                debug_log($command);
                system($command);
            }
            else
            {
                my $sip  = ( ( $config{'ha_heartbeat_start'} // "" ) eq "yes" ) ? $config{'ha_local_node_ip'} : $config{'admin_ip'};
                my $command
                    = "echo \"UPDATE alienvault.system SET sensor_id=(SELECT sensor.id FROM sensor WHERE sensor.ip=inet6_pton(\'$sip\') OR sensor.ip=inet6_pton(\'$config{'admin_ip'}\') LIMIT 1) WHERE sensor_id is null AND inet6_ntop(admin_ip) = \'$config_last{'admin_ip'}\'\" | ossim-db   $stdout $stderr ";
                debug_log($command);
                system($command);            
            }
            
            $tmp_change_sensor_info = 1;

        }

        #change hostname

        if ( "$config{'hostname'}" ne "$config_last{'hostname'}" )
        {

            console_log(
                "Sensor Profile: Change hostname (old=$config_last{'hostname'} new=$config{'hostname'}) update  tables"
            );
            verbose_log("Sensor Profile: Update sensor table");
            my $command
                = "echo \"update alienvault.sensor set name = \'$config{'hostname'}\' where name = \'$config_last{'hostname'}\'\" | ossim-db   $stdout $stderr ";
            debug_log($command);
            system($command);

            if ( ( $config{'ha_heartbeat_start'} // "" ) ne "yes" )
            {
                my $command
                    = "echo \"UPDATE alienvault.system SET name = \'$config{'hostname'}\' WHERE name = \'$config_last{'hostname'}\'\" | ossim-db   $stdout $stderr ";
                debug_log($command);
                system($command);
            }

            $tmp_change_sensor_info = 1;

        }

        if ( $tmp_change_sensor_info == 0 )
        {

            my @t = localtime(time);
            my $gmt_offset_in_seconds = timegm(@t) - timelocal(@t);
            my $gmt_offset_in_hours   = $gmt_offset_in_seconds / 3600;
            my $tzone                 = slurp { chomp => 1 }, '/etc/timezone';
            verbose_log(
                "Sensor Profile: Computed sensor (local) timezone (\'$tzone\') offset: \'$gmt_offset_in_hours\'"
            );

            # -- alienvault.sensor, alienvault.sensor_properties, alienvault.sensor_stats, alienvault.net_sensor_reference, alienvault.sensor_interfaces, alienvault.task_inventory, alienvault.acl_sensors
            my $ldsname
                = `echo "SELECT count(id) FROM sensor WHERE ip = inet6_pton('$admin_ip') OR id in (SELECT sensor_id FROM system WHERE admin_ip=inet6_pton('$admin_ip'));" | ossim-db | grep -vw count $stdout $stderr`;
            $ldsname =~ s/\n//g;
            debug_log("local (default) sensor name count: $ldsname");

            if ( $ldsname eq "0" )
            {

                verbose_log("Sensor Profile: Add new sensor");
                
                my $ids   = ($config{'sensor_detectors'} =~ /snort|suricata/) ? '1' : '0';
                my $prads = ($config{'sensor_detectors'} =~ /prads/) ? '1' : '0';
                my $nflow = ($config{'netflow'} =~ /yes/) ? '1' : '0';
                
                my $vs    = `dpkg -l | grep ossim-cd-tools | awk '{print \$3}' | awk -F'-' '{ print \$1 }'`;
                $vs =~ s/\n//g;
                
                my $command = "echo \"CALL sensor_update (\'admin\',\'\',\'$admin_ip\',\'$server_hostname\',5,40001,\'$gmt_offset_in_hours\',\'\',\'\',\'$vs\',1,1,1,0,$ids,$prads,$nflow);\" | ossim-db $stdout $stderr";
                debug_log($command);
                system($command);

                verbose_log("Sensor Profile: Update host_net_reference");
                my $command = "echo \"INSERT IGNORE INTO host_net_reference SELECT host.id,net_id FROM host,host_ip,net_cidrs WHERE host.id=host_ip.host_id AND host_ip.ip>=net_cidrs.begin AND host_ip.ip<=net_cidrs.end;\" | ossim-db $stdout $stderr";
                debug_log($command);
                system($command);

            }
            else
            {
                debug_log("Sensor Profile: (already inserted)");
            }

            my $ldshexid
                = `echo "SELECT HEX(id) FROM sensor WHERE ip = inet6_pton('$admin_ip') LIMIT 1;" | ossim-db | grep -vw HEX $stdout $stderr`;
            $ldshexid =~ s/\s+//g; chomp($ldshexid);
            debug_log("local (default) sensor hex(id): $ldshexid");

            # check if system entries exists
            verbose_log("Sensor Profile: System update");
        
            my $s_uuid = `/usr/bin/alienvault-system-id | tr -d '-'`;
        
            my $profiles = 'Sensor';
            $profiles .= ',Framework' if ($profile_framework);
            $profiles .= ',Server' if ($profile_server);
            $profiles .= ',Database' if ($profile_database);
        
            my $sip    = ( ( $config{'ha_heartbeat_start'} // "" ) eq "yes" ) ? $config{'ha_local_node_ip'} : $config{'admin_ip'};
            my $haip   = ( ( $config{'ha_heartbeat_start'} // "" ) eq "yes" ) ? $config{'ha_virtual_ip'} : '';
            my $harole = ( ( $config{'ha_heartbeat_start'} // "" ) eq "yes" ) ? $config{'ha_role'} : '';        
        
            my $command = "echo \"CALL system_update(\'$s_uuid\',\'$server_hostname\',\'$sip\',\'\',\'$profiles\',\'$haip\',\'$server_hostname\',\'$harole\',\'$ldshexid\',\'\')\" | ossim-db $stdout $stderr";
            debug_log($command);
            system($command);

        }

    }

    ## rsyslog disables DNS lookups on messages received with -r (jmlorenzo-daniel)

    if ( "$config{'rsyslogdns_disable'}" eq "yes" ) {
        if ( -f "$rsyslogdefault_file" ) {
            open RSYSLOGDEFAULTFILE, "> $rsyslogdefault_file"
                or die "Error open file: rsyslog";
            print RSYSLOGDEFAULTFILE "$script_msg\n\n";
            print RSYSLOGDEFAULTFILE
                "# -m 0 disables 'MARK' messages (deprecated, only used in compat mode < 3)\n";
            print RSYSLOGDEFAULTFILE
                "# -r enables logging from remote machines (deprecated, only used in compat mode < 3)\n";
            print RSYSLOGDEFAULTFILE
                "# -x disables DNS lookups on messages received with -r\n";
            print RSYSLOGDEFAULTFILE "# -c compatibility mode\n";
            print RSYSLOGDEFAULTFILE "RSYSLOGD_OPTIONS=\"-c3 -x\"\n";

            close(RSYSLOGDEFAULTFILE);

        }

    }

    # rsyslog listen.
    # udp

    $command
        = "sed -i \"s/^#\\\$ModLoad imudp/\\\$ModLoad imudp/\" /etc/rsyslog.conf";
    debug_log("$command");
    system($command);

    $command
        = "sed -i \"s/#\\\$UDPServerRun/\\\$UDPServerRun/\" /etc/rsyslog.conf";
    debug_log("$command");
    system($command);


	my @rsyslogconffile = `cat /etc/rsyslog.conf`;
	if (map(/zasec\.conf/,@rsyslogconffile)) {
		verbose_log("Sensor Profile: rsyslog assec filter already set");
	}else{
		verbose_log("Sensor Profile: Setting rsyslog assec filter");
		open RSYSLOGCONFFILE, ">> /etc/rsyslog.conf" or warn "Could not open file";
		print RSYSLOGCONFFILE "\n# rsyslog zasec.conf\n# logs not from 127.0.0.1\nif not (\$fromhost-ip == '127.0.0.1') then -/var/log/ossim/asec_unk.log\nif not (\$fromhost-ip == '127.0.0.1') then ~\n\n";
		close(RSYSLOGCONFFILE);
	}


#    if ( !-f "/etc/rsyslog.d/alienvault.conf" ) {
#
#        system(
#            "echo \"\\\$MaxMessageSize 64k\" > /etc/rsyslog.d/alienvault.conf"
#        );
#    }

    # munin-node
    if ( -f "/etc/munin/munin-node.conf" ) {
        verbose_log("Sensor Profile: Configuring Munin-node");

# Allow from all, limit with iptables, for 1498.
#my ($one,$two,$tree,$four) = split(/\./,$config{'framework_ip'});
#$autorized = "^" . $one . "\\\." . $two . "\\\." . $tree ."\\\." . $four . "\$";

        open MUNINNODEFILE, "> /etc/munin/munin-node.conf";
        print MUNINNODEFILE<<EOF;
$script_msg

log_level 4
log_file /var/log/munin/munin-node.log
pid_file /var/run/munin/munin-node.pid
background 1
setseid 1
user root
group root
setsid yes

# Regexps for files to ignore

ignore_file ~\$
ignore_file \\.bak\$
ignore_file \%\$
ignore_file \\.dpkg-(tmp|new|old|dist)\$
ignore_file \\.rpm(save|new)\$
ignore_file \\.pod\$

# Set this if the client doesn't report the correct hostname when
# telnetting to localhost, port 4949
#
#host_name localhost.localdomain

# A list of addresses that are allow ^192.168.1.2\$
# regular expression, due to brain damage in Net::Server, which
# doesn't understand CIDR-style network notation.  You may repeat

Allow ^.*\$

# Which address to bind to;
host *
# host 127.0.0.1

# And which port
port 4949

EOF
        close(MUNINNODEFILE);

        # Disable munin server when framework is not installed with sensor
        if ( ( $profile_framework != 1 ) && ( -f "/etc/cron.d/munin" ) ) {
            unlink("/etc/cron.d/munin");
        }

        console_log("Restarting munin-node");
        system("/etc/init.d/munin-node restart $stdout $stderr");
    }

    #monit

    # Custom monit files, and split monit files by service:
    if ( !-d "/etc/monit/conf.d/" || !-d "/etc/monit/alienvault/" ) {
        system("mkdir -p /etc/monit/conf.d/ >/dev/null 2>&1 &");
        system("mkdir -p /etc/monit/alienvault/ >/dev/null 2>&1 &");
    }

    verbose_log("Sensor Profile: updating monitrc");
    open MONITFILE, "> $monit_file";
    print MONITFILE <<EOF;

# Agent
	check process ossim-agent with pidfile /var/run/ossim-agent.pid
	group agent
	start program = "/etc/init.d/ossim-agent start"
	stop program = "/etc/init.d/ossim-agent stop"
	#if children > 1 for 2 cycles then restart
	if totalmem > 90% for 2 cycles then restart
	if 20 restart within 20 cycles then alert

    check file agent.log with path /var/log/alienvault/agent/agent.log
    if timestamp > 5 minutes then exec "/etc/init.d/ossim-agent restart"

EOF
    close(MONITFILE);
    # monit in fprobe

    if ( $config{'netflow'} eq "yes" ) {

        my $check_fprobe = "dpkg -l fprobe |grep ^ii >/dev/null";
        system($check_fprobe);
        if ( $? == 0 ) {
            verbose_log("Sensor Profile: Updating monitrc fprobe");
            open MONITFILE, ">> $monit_file";
            print MONITFILE <<EOF;
	check process fprobe with pidfile /var/run/fprobe.pid
	start program = "/etc/init.d/fprobe start"
	stop  program = "/etc/init.d/fprobe stop"
	if 20 restarts within 20 cycles then alert

EOF
            close(MONITFILE);
        }

    }

    # Remember reset

    $reset{'sensors'}     = 1;
    $reset{'iptables'}    = 1;
    $reset{'ossim-agent'} = 1;
    $reset{'monit'}       = 1;
    $reset{'rsyslog'}     = 1;

    #$reset{'nfsen'} = 1;
    $reset{'fprobe'}  = 1;
#    $reset{'openvas'} = 1;

    return %reset;

}

sub update_plugin_interfaces {
    my $plugin = shift;
    $plugin =~ s/\n//g;
    $plugin =~ s/"//g;

    my %config      = AV::ConfigParser::current_config;
    my %config_last = AV::ConfigParser::last_config;

    $server_hostname = $config{'hostname'};
    $server_ip       = $config{'server_ip'};
    $framework_port  = $config{'framework_port'};
    $framework_host  = $config{'framework_ip'};
    $db_pass         = $config{'database_pass'};




    if ( $config{'profile'} eq "all-in-one" ) {
        @profiles_arr = ( "Server", "Database", "Framework", "Sensor" );
    }
    else {
        @profiles_arr = split( /,\s*/, $config{'profile'} );
    }

    @sensor_interfaces_arr = split( /,\s*/, $config{'sensor_interfaces'} );
    @profiles_arr          = split( /,\s*/, $config{'profile'} );

    @sensor_interfaces_arr_last
        = split( /,\s*/, $config_last{'sensor_interfaces'} );

    verbose_log("Sensor Profile: Config $plugin");

    my $plugin_filename = "/etc/ossim/agent/plugins/" . $plugin . ".cfg";

    system(
        "egrep -v \"$plugin.*eth.*cfg\" /etc/ossim/agent/config.cfg >> /etc/ossim/agent/config.cfg.new; mv /etc/ossim/agent/config.cfg.new /etc/ossim/agent/config.cfg"
    );

    #
    #
    #

    #if ( scalar(@sensor_interfaces_arr) > 1 ) {

    # FIXME Cleaner implementation
    my ( $location_bin, $destination_bin );
    for my $tmp_interface (@sensor_interfaces_arr) {

        my $tmp_interface_clean = $tmp_interface;
        $tmp_interface_clean =~ s/_/\@/g;
        my @tmp_interface_master = split( /_/, $tmp_interface );

        debug_log(
            "Sensor Profile: Enabling $tmp_interface for plugin $plugin");
        my $plugin_interface_filename
            = "/etc/ossim/agent/plugins/"
            . $plugin . "_"
            . $tmp_interface . ".cfg";
        system("cp $plugin_filename $plugin_interface_filename");
        my $Config_tmp = Config::Tiny->read($plugin_interface_filename);
        $Config_tmp->{config}->{interface} = "$tmp_interface";

        if ( $tmp_interface !~ /.*_.*/ ) {

            # Plugin specific stuff per interface, enable before config write.
            my $dg            = system ('dpkg -l | grep alienvault-10g-tools > /dev/null');
            $dg >>= 8;

            if ( $dg != 0 ) {

                if ( $plugin eq "prads" ) {
                    my $init_source     = "/etc/init.d/prads";
                    my $init_dest       = "/etc/init.d/prads_" . $tmp_interface;
                    system("cp -rf $init_source $init_dest");
                    system("sed -i \"s:NAME=.*:NAME=\\\"prads_$tmp_interface\\\":\" $init_dest");
                    system("sed -i \"s/# Provides:.*/# Provides:          prads_$tmp_interface/\" $init_dest");
                    system("sed -i \"s/prads.pid/prads_${tmp_interface}.pid/\" $init_dest");

                    my $default_config_file_init  = "/etc/default/prads";
                    my $default_config_file_dest = "/etc/default/prads_" . $tmp_interface;
                    system("cp -rf $default_config_file_init  $default_config_file_dest");
                    system("sed -i \"s:^HOME_NETS=.*:HOME_NETS=\\\"$trimmed_sensor_networks\\\":\" $default_config_file_dest") ;
                    system("sed -i \"s:# LOGFILE=.*:LOGFILE=/var/log/ossim/prads-${tmp_interface}.log:\" $default_config_file_dest") ;
                    system("sed -i \"s:# INTERFACE=.*:INTERFACE=\\\"${tmp_interface}\\\":\" $default_config_file_dest") ;

                    $location_bin = "/usr/bin/" . $plugin;
                    my $location_filename
                        = "/var/log/ossim/prads-" . $tmp_interface . ".log";
                    $Config_tmp->{config}->{location} = "$location_filename";
                    $destination_bin = "/usr/bin/" . $plugin . "_" . $tmp_interface;
                    $destination_bin2 =  $plugin . "_" . $tmp_interface;

                    #system("cp $location_bin /usr/bin/$destination_bin");
                    $Config_tmp->{config}->{process} = "$destination_bin2";
                }
            }
        }

        if ( $plugin eq "snortunified" ) {
            my $tmp_interfaces
            = join( " ", split( /,\s*/, $config{'sensor_interfaces'} ) );
            my $Config_snort = Config::Tiny->read($snort_debian_cfg);
            $Config_snort->{_}->{DEBIAN_SNORT_INTERFACE} = "\"$tmp_interfaces\"";
            $Config_snort->write($snort_debian_cfg);
            if ( scalar(@sensor_interfaces_arr) == 1 ) {
                $tmp_interface = $sensor_interfaces_arr[0];

# If we've got only one interface let's see if we have to change the linklayer type within snort
                if ( $sensor_interfaces_arr[0] eq "any" ) {
                    debug_log("Setting linklayer to cooked\n");
                    $Config_tmp->{config}->{linklayer} = "cookedlinux";
                }
                else {
                    debug_log("Setting linklayer to ethernet\n");
                    $Config_tmp->{config}->{linklayer} = "ethernet";
                }
                $Config_tmp->write($plugin_interface_filename);
                $snort_conf_name = "/etc/snort/snort.$tmp_interface.conf";
                system("cp $snort_cfg $snort_conf_name");
            }

            my $init_source     = "/etc/init.d/snort";
            my $init_dest       = "/etc/init.d/snort_" . $tmp_interface;
            $location_bin    = "/usr/sbin/snort";
            $destination_bin = "/usr/sbin/snort" . "_" . $tmp_interface;
            my $destination_bin_escaped
                = "\\/usr\\/sbin\\/snort" . "_" . $tmp_interface;
            $snort_conf_name = "/etc/snort/snort.$tmp_interface.conf";
            my $prefix_name     = "snort_" . $tmp_interface;
            $Config_tmp->{config}->{prefix}  = "$prefix_name";
            $Config_tmp->{config}->{process} = "snort_$tmp_interface";

            #                system("cp $location_bin $destination_bin");
            system("cp -rf $init_source $init_dest");

            # prevent LSB problems when provided 'same' initscript:
            system(
                "sed -i 's/Provides:.*/Provides: snort_$tmp_interface/' $init_dest"
            );
            system("cp -rf /etc/snort/snort.conf $snort_conf_name");

            # create snort.debian.$iface.conf
            system(
                "cp $snort_debian_cfg /etc/snort/snort.debian.$tmp_interface.conf"
            );
            my $command
                = "sed -i \"s:DEBIAN_SNORT_HOME_NET=.*:DEBIAN_SNORT_HOME_NET=\\\"$trimmed_sensor_networks\\\":\" /etc/snort/snort.debian.$tmp_interface.conf";
            debug_log("$command");
            system($command);

            verbose_log("Sensor Profile: Config $snort_conf_name");

            my $snver = `cat /etc/snort/snort.conf|grep -v ^#|grep unified`;
            if ( $snver eq "" ) {
                my $command
                    = "sed -i \"s/# output log_unified: filename snort.log, limit 128/output unified2: filename snort, limit 128/\" /etc/snort/snort.conf";
                debug_log("$command");
                system($command);
            }

            $command
                = "sed -i \"s:DEBIAN_SNORT_INTERFACE=.*:DEBIAN_SNORT_INTERFACE=\\\"$tmp_interface_master[0]\\\":\" /etc/snort/snort.debian.$tmp_interface.conf";
            debug_log("$command");
            system($command);

            $command
                = "sed -i \"s:CONFIG=.*:CONFIG=/etc/snort/snort.debian.$tmp_interface.conf:\" $init_dest";
            debug_log("$command");
            system($command);

            ##########
            verbose_log("Sensor Profile: Config $init_dest daemon");
            $command
                = "sed -i \"s:DAEMON=.*:DAEMON=$destination_bin_escaped:\" $init_dest";
            debug_log("$command");
            system($command);

            verbose_log("Sensor Profile: Config $init_dest interface");
            $command
                = "sed -i \"s/interfaces=\\\".*\\\"/interfaces=\\\"$tmp_interface_master[0]\\\"/\" $init_dest";
            debug_log("$command");
            system($command);

            verbose_log("Sensor Profile: Config $init_dest interface");
            $command
                = "sed -i \"s:-i\\s*\$interface.*:-i $tmp_interface_clean >/dev/null:\" $init_dest";
            debug_log("$command");
            system($command);

            verbose_log("Sensor Profile: Config $init_dest interface");
            $command
                = "sed -i \"s:CONFIGFILE=/etc/snort/snort.*:CONFIGFILE=/etc/snort/snort.$tmp_interface.conf:\" $init_dest";
            debug_log("$command");
            system($command);

            verbose_log("Sensor Profile: Config $init_dest name");
            $command
                = "sed -i \"s:NAME=.*:NAME=snort_$tmp_interface:\" $init_dest";
            debug_log("$command");
            system($command);

            verbose_log("Sensor Profile: Config $snort_conf_name");
            $command
                = "sed -i \"s/output.*unified.*filename.*limit/output unified2: filename $prefix_name, limit/\" $snort_conf_name";
            debug_log("$command");
            system($command);

            verbose_log("Sensor Profile: Config $snort_conf_name");
            $command
                = "sed -i \"s/output.*unified2.*filename.*limit/output unified2: filename $prefix_name, limit/\" $snort_conf_name";
            debug_log("$command");
            system($command);

            verbose_log("Sensor Profile: set +x $init_dest");
            system("chmod +x $init_dest");

            debug_log("Sensor Profile: Include user defined parameters for $prefix_name");
            open my $snort_conf_name_fh, '>>', $snort_conf_name;
            print $snort_conf_name_fh <<EOF;

# include user defined parameters for snort at $tmp_interface
include snortuser.$tmp_interface.conf

EOF
            close($snort_conf_name_fh);
            my $path = "/etc/snort/snortuser.$tmp_interface.conf";
            my $update = touch ($path);
        }

        $Config_tmp->write($plugin_interface_filename);
        my $Config_plugins = Config::Tiny->read($agentcfg);
        $Config_plugins->{plugins}->{ $plugin . "_" . $tmp_interface }
            = $plugin_interface_filename;
        $Config_plugins->write($agentcfg);

        system("cp -rf $location_bin $destination_bin");
    }

    #    }

}

1;
