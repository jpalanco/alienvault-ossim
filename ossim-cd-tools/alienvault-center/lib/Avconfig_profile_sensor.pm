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
no warnings 'experimental::smartmatch';

use AV::ConfigParser;
use AV::Log;

use Config::Tiny;
use Time::Local;
use Perl6::Slurp;
use File::Touch;

my %config_agent_orig;
my $script_msg
    = "# Automatically generated for ossim-reconfig scripts. DO NOT TOUCH!";
my $agentcfg            = "/etc/ossim/agent/config.cfg";
my $agentcfg_last       = "/etc/ossim/agent/config.cfg_last";
my $asset_plugins       = "/etc/ossim/agent/config.yml";
my $asset_plugins_last  = "/etc/ossim/agent/config.yml_last";
my $plugins_cfg_basedir = '/etc/ossim/agent/plugins';
my $custom_plugins_cfg_basedir
                        = '/etc/alienvault/plugins/custom';
my $fprobeconfig        = "/etc/default/fprobe";
my $monit_file          = "/etc/monit/alienvault/avsensor.monitrc";
my $agentcfgorig        = "/etc/ossim/agent/config.cfg.orig";
my $config_file         = '/etc/ossim/ossim_setup.conf';

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

    foreach my $var ( split( /,\s*/, $config{'sensor_detectors'} ) ) {
        my $plugin_path = "$plugins_cfg_basedir/$var.cfg";
        my $custom_plugin_path = "$custom_plugins_cfg_basedir/$var.cfg";
        # Check if file exists and if it's custom plugin rewrite the path.
        # Currently all our custom plugins have type=detectors.
        if (! -f $plugin_path) {
            if (-f $custom_plugin_path) {
                $plugin_path = $custom_plugin_path;
            }
        }

	# Do not enable simple plugin for these when multiple interfaces selected
        $Config_plugins->{plugins}->{$var}
            = $Config_plugins_orig->{plugins}->{$var}
            // "$plugin_path";
    }

    foreach my $var ( split( /,\s*/, $config{'sensor_monitors'} ) ) {
        $Config_plugins->{plugins}->{$var} = $Config_plugins_orig->{plugins}->{$var}
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

    foreach my $var ( split( /,\s*/, $config{'sensor_detectors'} ) ) {


     # Do not enable simple plugin for these when multiple interfaces selected
     #	next;

    }

    verbose_log("Sensor Profile: Config IDS rules flow control");

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
                = "echo \"UPDATE alienvault.sensor SET ip = inet6_aton(\'$config{'admin_ip'}\') WHERE inet6_ntoa(ip) = \'$config_last{'admin_ip'}\'\" | ossim-db   $stdout $stderr ";
            debug_log($command);
            system($command);

            # Fix for broken ha sensor-system relationships (prev. 4.7)
            if ( ( $config{'ha_heartbeat_start'} // "" ) eq "yes" )
            {
                my $sip = $config{'ha_local_node_ip'};
                my $command
                    = "echo \"UPDATE alienvault.system SET sensor_id=(SELECT sensor.id FROM sensor WHERE sensor.ip=inet6_aton(\'$sip\') OR sensor.ip=inet6_aton(\'$config{'admin_ip'}\') LIMIT 1) WHERE sensor_id is null AND inet6_ntoa(admin_ip) = \'$config_last{'admin_ip'}\'\" | ossim-db   $stdout $stderr ";
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
                = `echo "SELECT count(id) FROM sensor WHERE ip = inet6_aton('$admin_ip') OR id in (SELECT sensor_id FROM system WHERE admin_ip=inet6_aton('$admin_ip'));" | ossim-db | grep -vw count $stdout $stderr`;
            $ldsname =~ s/\n//g;
            debug_log("local (default) sensor name count: $ldsname");

            if ( $ldsname eq "0" )
            {

                verbose_log("Sensor Profile: Add new sensor");

                my $ids   = ($config{'sensor_detectors'} =~ /suricata/) ? '1' : '0';
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
                = `echo "SELECT HEX(id) FROM sensor WHERE ip = inet6_aton('$admin_ip') LIMIT 1;" | ossim-db | grep -vw HEX $stdout $stderr`;
            $ldshexid =~ s/\s+//g; chomp($ldshexid);
            debug_log("local (default) sensor hex(id): $ldshexid");

            # check if system entries exists
            verbose_log("Sensor Profile: System update");

            my $s_uuid = `/usr/bin/alienvault-system-id | tr -d '-'`;

            my $profiles = 'Sensor';
            $profiles .= ',Framework' if ($profile_framework);
            $profiles .= ',Server' if ($profile_server);
            $profiles .= ',Database' if ($profile_database);

            my $sip    =  $config{'admin_ip'};
            my $command = "echo \"CALL system_update(\'$s_uuid\',\'$server_hostname\',\'$sip\',\'\',\'$profiles\',\'\',\'$server_hostname\',\'\',\'$ldshexid\',\'\')\" | ossim-db $stdout $stderr";
            debug_log($command);
            system($command);

        }

    }

    #monit

    # Custom monit files, and split monit files by service:
    if ( !-d "/etc/monit/conf.d/" || !-d "/etc/monit/alienvault/" ) {
        system("mkdir -p /etc/monit/conf.d/ >/dev/null 2>&1 &");
        system("mkdir -p /etc/monit/alienvault/ >/dev/null 2>&1 &");
    }

    verbose_log("Sensor Profile: updating monitrc");
    # monit in fprobe

    if ( $config{'netflow'} eq "yes" ) {

        my $check_fprobe = "dpkg -l fprobe |grep ^ii >/dev/null";
        system($check_fprobe);
        if ( $? == 0 ) {
            verbose_log("Sensor Profile: Updating monitrc fprobe");
            open MONITFILE, "> $monit_file";
            print MONITFILE <<EOF;
	check process fprobe with pidfile /var/run/fprobe.pid
	start program = "/etc/init.d/fprobe start"
	stop  program = "/etc/init.d/fprobe stop"
	if 20 restarts within 20 cycles then alert

EOF
            close(MONITFILE);
        }

    }

    # Check if ossim-agent conf.cfg or conf.yml were modified;
    # config.yml might be unavailable if per-asset plugins are not enabled;
    my $agent_conf_changed = check_conf_diff_and_update_copy($agentcfg, $agentcfg_last);
    my $agent_yml_changed = check_conf_diff_and_update_copy($asset_plugins, $asset_plugins_last);
    my $restart_ossim_agent = $agent_conf_changed || $agent_yml_changed;

    # Remember to reset
    $reset{'sensors'}     = 1;
    $reset{'iptables'}    = 1;
    $reset{'monit'}       = 1;
    $reset{'fprobe'}      = 1;
    $reset{'ossim-agent'} = $restart_ossim_agent;

#    $reset{'nfsen'} = 1;
#    $reset{'openvas'} = 1;

    return %reset;
}


sub check_conf_diff_and_update_copy {
    my $current_conf = @_[0];
    my $prev_conf = @_[1];
    my $config_differs = 0;

    # Check if current config is available, if not - skip this check.
    unless(-e $current_conf) {
        debug_log("$current_conf not found. Skipping...");
        return $config_differs;
    }

    # Check if previous config exists.
    unless(-e $prev_conf) {
        debug_log("$prev_conf not found. Setting ossim-agent to restart because unable to detect changes.");
        $config_differs = 1;

    } else {
        # Check if config was modified and set a flag to restart ossim-agent.
        my $conf_diff = `diff $current_conf $prev_conf`;
        if ($conf_diff ne "") {
            debug_log("There is a difference between $current_conf and $prev_conf:");
            debug_log($conf_diff);
            debug_log("Need to restart ossim-agent!");
            $config_differs = 1;
        }
    }

    # Create a copy of existing version of config for further usage (checking the diff between two copies).
    debug_log("Creating a copy of existing $current_conf to $prev_conf...");
    my $agentcnf_from_to = "$current_conf $prev_conf";
    $command = "cp $agentcnf_from_to; chmod --reference=$agentcnf_from_to; chown --reference=$agentcnf_from_to";
    debug_log($command);
    system($command);

    return $config_differs;
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

    # FIXME Cleaner implementation
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

        $Config_tmp->write($plugin_interface_filename);
        my $Config_plugins = Config::Tiny->read($agentcfg);
        $Config_plugins->{plugins}->{ $plugin . "_" . $tmp_interface }
            = $plugin_interface_filename;
        $Config_plugins->write($agentcfg);
    }

    #    }

}

1;