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

package AV::ConfigParser;

use v5.10;
use strict;
use warnings;
#use diagnostics;

use Config::Tiny;

use AV::Log;
use AV::Config::Hostname;

my $VERSION = 1.00;

my $config_file      = "/etc/ossim/ossim_setup.conf";
my $config_file_tmp  = "/etc/ossim/ossim_setup.conf.tmp";
my $config_file_last = "/etc/ossim/ossim_setup.conf_last";
my $fprobeconfig     = "/etc/default/fprobe";
my $servercfg        = "/etc/ossim/server/config.xml";
my ( %config, %config_last );
my $pars_conf;
my $pars_conf_last;

sub current_config {

	my $write_allow = shift // 0;
    my @systemDev;
    my @profiles_arr;
    my @rservers_arr;
    my @sensor_interfaces_ary;

    #console_log("Get current values in $config_file");

    # current linux distibution
    #verbose_log("Get Distro");
    if ( -f "/etc/debian_version" ) {
        $config{'distro_version'} = `cat /etc/debian_version`;
        $config{'distro_version'} =~ s/\n//g;
        $config{'distro_type'} = "debian";

        #verbose_log("Found Debian.");
    }
    else {
        console_log("Not a debian distribution, not implemented, sorry");
        exit 0;

    }

    # implemented

    # autodetect ifaces in kernel
    # almacenamos en el array @systemDev

    if ( -f "/proc/net/dev" ) {
        open NETDEV, "< /proc/net/dev" or warning "Not showrunning $!";
        my @varnetdev = <NETDEV>;
        close(NETDEV);

        foreach my $netDevices (@varnetdev) {

            $netDevices =~ s/\n//g;
            $netDevices =~ s/\s//g;
            my @valor_netDevices = split( /:/, $netDevices );
            if ( $valor_netDevices[0] !~ m/face+|Inter+/ ) {
                push( @systemDev, "$valor_netDevices[0]" );

                #debug_log("Found interface: $valor_netDevices[0]");
            }

        }

    }
    else {

        warning("File /proc/net/dev not exist!");
    }

    $pars_conf = Config::Tiny->read($config_file);

    if ( !defined $pars_conf ) {
        error(
            "Couldn't read/parse $config_file:'$Config::Tiny->strerr', install ossim (apt-get install ossim)"
        );
        console_log("Exiting...");
        exit 0;
    }

    my $is_ha_present = ( $pars_conf->write_string() =~ m{\[ha\]} );

    sub getprop {
        my $section  = shift;
        my $property = shift;

        return $pars_conf->{$section}->{$property};
    }

    #general
    $config{'profile'} = getprop( "_", "profile" );
    if ( $config{'profile'} eq "all-in-one" ) {
        @profiles_arr = ( "Server", "Database", "Framework", "sensor" );
    }
    else {
        @profiles_arr = split( /,\s*/, $config{'profile'} );
    }

    $config{'interface'}      = getprop( "_", "interface" );
    $config{'language'}       = 'en';

    $config{'hostname'}         = getprop( "_", "hostname" );
    warning("Wrong hostname, use allowed characters only (see RFC 1035)")
        unless AV::Config::Hostname->is_valid( $config{'hostname'} );
    $config{'domain'}           = getprop( "_", "domain" );
    $config{'admin_ip'}         = getprop( "_", "admin_ip" );
    $config{'admin_netmask'}    = getprop( "_", "admin_netmask" );
    $config{'admin_gateway'}    = getprop( "_", "admin_gateway" );
    $config{'admin_dns'}        = getprop( "_", "admin_dns" );
    if ( -f '/etc/ossim/.first_init' ) {
	$config{'first_init'}       = 'yes';
	}
    else {
	$config{'first_init'}       = 'no';
    }
    $config{'upgrade'}          = 'no';
    $config{'ntp_server'}       = getprop( "_", "ntp_server" );
    $config{'email_notify'}     = getprop( "_", "email_notify" );
    $config{'mailserver_relay'} = getprop( "_", "mailserver_relay" );
    $config{'mailserver_relay_port'}
        = getprop( "_", "mailserver_relay_port" );
    $config{'mailserver_relay_user'}
        = getprop( "_", "mailserver_relay_user" );
    $config{'mailserver_relay_passwd'}
        = getprop( "_", "mailserver_relay_passwd" );

    #database
    $config{'database_port'} = '3306';
    $config{'database_type'} = 'mysql';
    $config{'database_user'} = getprop( "database", "user" );
    $config{'database_pass'} = getprop( "database", "pass" );
    if ( $config{'database_pass'} !~ /^[a-zA-Z0-9]+$/ ) {
        if ( map( /Server/, @profiles_arr ) ) {
            error("Passwd wrong ,Use only characters allowed A-Z,a-z,0-9");
        }
        if ( map( /Database/, @profiles_arr ) ) {
            error("Passwd wrong ,Use only characters allowed A-Z,a-z,0-9");
        }
        if ( map( /Framework/, @profiles_arr ) ) {
            error("Passwd wrong ,Use only characters allowed A-Z,a-z,0-9");
        }
    }
    $config{'database_event'} = 'alienvault_siem';
    $config{'database_ossim'} = 'alienvault';
    $config{'database_acl'}   = 'ossim_acl';
    $config{'database_osvdb'} = 'osvdb';

    $config{'database_ip'}     = getprop( "database", "db_ip" );

    if ($config{'profile'} eq "Database")
    {
        $config{'database_ip'} = '127.0.0.1';
    }

    $config{'rebuild_database'} = 'no';
    $config{'innodb'}          = getprop( "database", "innodb" );

    #server
    $config{'server_ip'}            = getprop( "server", "server_ip" );
    $config{'server_port'}          = '40001';
    $config{'fixed_server_plugins'} = getprop( "server", "server_plugins" );

    $config{'server_pro'}     = getprop( "server", "server_pro" );
    $config{'server_license'} = 'no';
    $config{'rservers'}       = getprop( "server", "rservers" );
    @rservers_arr = split( /;\s*/, $config{'rservers'} ) if (defined($config{'rservers'}));

    $config{'alienvault_ip_reputation'}
        = getprop( "server", "alienvault_ip_reputation" );
    $config{'idm_mssp'} = getprop( "server", "idm_mssp" );

    #sensor
    $config{'sensor_ctx'}        = getprop( "sensor", "sensor_ctx" );
    $config{'sensor_ip'}         = getprop( "sensor", "ip" );
    $config{'sensor_name'}       = getprop( "sensor", "name" );
    $config{'sensor_detectors'}  = getprop( "sensor", "detectors" );
    $config{'sensor_monitors'}   = getprop( "sensor", "monitors" );

	if ( map( /Ids/, @profiles_arr ) ) {

		if ( ! map( /Sensor/, @profiles_arr ) ){

		    $config{'sensor_monitors'} = "";
		    $config{'sensor_detectors'} = "suricata";
		}

        }



	
    $config{'sensor_interfaces'} = getprop( "sensor", "interfaces" );
    ### FIXME:  this should go in a different hash, otherwise it ends in the
    #database, where only strings are stored for each key.
    #@sensor_interfaces_ary       = split( /,\s*/, $config{'sensor_interfaces'} );
    #$config{'sensor_interfaces_ary_ref'} = [ @sensor_interfaces_ary ];

    $config{'sensor_priority'}   = getprop( "sensor", "priority" );

    $config{'sensor_networks'} = getprop( "sensor", "networks" );
    $config{'sensor_tzone'}    = getprop( "sensor", "tzone" );
    $config{'override_sensor'} = 'False';
    $config{'rsyslogdns_disable'} = 'yes';
    $config{'netflow'} = getprop( "sensor", "netflow" );
    $config{'netflow_remote_collector_port'}
        = getprop( "sensor", "netflow_remote_collector_port" );
    $config{'ids_rules_flow_control'}
        = getprop( "sensor", "ids_rules_flow_control" );
    $config{'mservers'}
        = getprop( "sensor", "mservers" );
    $config{'asec'}
        = getprop( "sensor", "asec" );

    #framework
    $config{'framework_ip'}    = getprop( "framework", "framework_ip" );
    $config{'framework_port'}  = '40003';
    $config{'framework_https'} = 'yes';
    $config{'framework_https_key'}
        = getprop( "framework", "framework_https_key" );
    $config{'framework_https_cert'}
        = getprop( "framework", "framework_https_cert" );

    #snmp
    $config{'snmpd'}         = getprop( "snmp", "snmpd" );
    $config{'snmptrap'}      = getprop( "snmp", "snmptrap" );
    $config{'snmp_comunity'} = getprop( "snmp", "community" );

    # firewall
    $config{'firewall_active'} = getprop( "firewall", "active" );

    #vpn
    $config{'vpn_net'}             = getprop( "vpn", "vpn_net" );
    $config{'vpn_netmask'}         = getprop( "vpn", "vpn_netmask" );
    $config{'vpn_infraestructure'} = getprop( "vpn", "vpn_infraestructure" );
    $config{'vpn_port'}            = getprop( "vpn", "vpn_port" );
    $config{'vpn_ip'}			   = getprop( "vpn", "vpn_ip" );

    #update
    $config{'update_proxy'}      = getprop( "update", "update_proxy" );
    $config{'update_proxy_user'} = getprop( "update", "update_proxy_user" );
    $config{'update_proxy_pass'} = getprop( "update", "update_proxy_pass" );
    $config{'update_proxy_port'} = getprop( "update", "update_proxy_port" );
    $config{'update_proxy_dns'}  = getprop( "update", "update_proxy_dns" );

    #ha

    if ($is_ha_present) {
        $config{'ha_heartbeat_start'} = getprop( "ha", "ha_heartbeat_start" );
        $config{'ha_role'}            = getprop( "ha", "ha_role" );
        $config{'ha_device'}          = getprop( "ha", "ha_device" );
        $config{'ha_virtual_ip'}      = getprop( "ha", "ha_virtual_ip" );
        $config{'ha_local_node_ip'}   = getprop( "ha", "ha_local_node_ip" );
        $config{'ha_other_node_ip'}   = getprop( "ha", "ha_other_node_ip" );
        $config{'ha_other_node_name'} = getprop( "ha", "ha_other_node_name" );
        $config{'ha_password'}        = getprop( "ha", "ha_password" );
        $config{'ha_keepalive'}       = getprop( "ha", "ha_keepalive" );
        $config{'ha_deadtime'}        = getprop( "ha", "ha_deadtime" );
        $config{'ha_log'}             = getprop( "ha", "ha_log" );
        $config{'ha_autofailback'}    = getprop( "ha", "ha_autofailback" );
        $config{'ha_heartbeat_comm'}  = getprop( "ha", "ha_heartbeat_comm" );
        $config{'ha_ping_node'}       = getprop( "ha", "ha_ping_node" );
    }


    # Compatibility

    my $cw = 0;

    my $profile_database  = 0;
    my $profile_server    = 0;
    my $profile_framework = 0;
    my $profile_sensor    = 0;

    foreach my $profile (@profiles_arr) {

        given ($profile) {
            when ( m/Database/ )  { $profile_database  = 1; }
            when ( m/Server/ )    { $profile_server    = 1; }
            when ( m/Framework/ ) { $profile_framework = 1; }
            when ( m/Sensor/ )    { $profile_sensor    = 1; }
        }

    }

    #my $ConfigFile_compatibility = Config::Tiny->new();
    #$ConfigFile_compatibility = Config::Tiny->read($config_file);

    my $ConfigFile_compatibility = $pars_conf;

	if ( ( $config{'admin_dns'} // "" ) eq "" ) {
        $config{'admin_dns'}
            = `dns="";for i in \$(grep ^nameserver /etc/resolv.conf | awk -F' +' '{print \$2}');do if [ -z \$dns ];then dns=\$i;else dns="\$dns,\$i";fi;done;echo \$dns`;
        $config{'admin_dns'} =~ s/\n//g;

        $ConfigFile_compatibility->{_}->{admin_dns} = $config{'admin_dns'};
        $cw = 1;
    }

	if ( ( $config{'admin_gateway'} // "" ) eq "" ) {
        $config{'admin_gateway'}
            = `/sbin/route -n | grep $config{'interface'} |grep UG| awk '{print\$2}'`;
        $config{'admin_gateway'} =~ s/\n//g;

        $ConfigFile_compatibility->{_}->{admin_gateway}
            = $config{'admin_gateway'};
        $cw = 1;
    }

#    if ( $config{'admin_ip'} eq "" ) {
	#
	#		my $ip;

	#		open( INFILE, "</etc/network/interfaces" );

	#		while (<INFILE>) {
	#    		if (/^iface $interface/) {
	#        			while (<INFILE>) {
	#            			if (/\s+address\s+(\d+\.\d+\.\d+\.\d+)/) {
	#                				$ip = $1;
	#                				last;
	#            			}
	#        			}
	#        			last;
	#    		}
	#		}
	#
	#		close INFILE;
	#    $config{'admin_ip'} = "$ip";
	#
	#	 $ConfigFile_compatibility->{_}->{admin_ip} = $config{'admin_ip'};
	#    $cw = 1;
#    console_log("-----------------------_>>>>>>>>>>>>>>>>>>>>>>>>>XXXXXXXXXXXXXXXXXXXXX 7");
	#}

    if ( ( $config{'admin_netmask'} // "" ) eq "" ) {
        $config{'admin_netmask'}
            = `/sbin/ifconfig $config{'interface'} |grep Mask |awk -F'Mask:' '{print\$2}'`;
        $config{'admin_netmask'} =~ s/\n//g;

        $ConfigFile_compatibility->{_}->{admin_netmask}
            = $config{'admin_netmask'};
        $cw = 1;
    }


    if ( ( $config{'domain'} // q{} ) eq "" ) {
        $config{'domain'} = `cat /etc/mailname | cut -d"." -f2`;
        $config{'domain'} =~ s/\n//g;

        $ConfigFile_compatibility->{_}->{domain} = $config{'domain'};
        $cw = 1;

    }

	#FIX EMAIL_NOTIFY

    if ( $config{'first_init'} ) {
        delete $ConfigFile_compatibility->{_}->{first_init};
        $cw = 1;
    }

    if ( ( $config{'hostname'} // q{} ) eq "" ) {
        $config{'hostname'} = `cat /etc/hostname`;
        $config{'hostname'} =~ s/\n//g;

        $ConfigFile_compatibility->{_}->{hostname} = $config{'hostname'};
        $cw = 1;

    }

	#FIX INTERFACE
	#FIX LANGUAGE

    if ( $config{'mailserver_relay'} eq "" ) {
        $config{'mailserver_relay'} = "no";

        $ConfigFile_compatibility->{_}->{mailserver_relay}
            = $config{'mailserver_relay'};
        $cw = 1;

    }

	if ( $config{'mailserver_relay_passwd'} eq "" ) {
        $config{'mailserver_relay_passwd'} = "unconfigured";

        $ConfigFile_compatibility->{_}->{mailserver_relay_passwd}
            = $config{'mailserver_relay_passwd'};
        $cw = 1;

    }
    if ( $config{'mailserver_relay_port'} eq "" ) {
        $config{'mailserver_relay_port'} = "25";

        $ConfigFile_compatibility->{_}->{mailserver_relay_port}
            = $config{'mailserver_relay_port'};
        $cw = 1;

    }

    if ( $config{'mailserver_relay_user'} eq "" ) {
        $config{'mailserver_relay_user'} = "unconfigured";

        $ConfigFile_compatibility->{_}->{mailserver_relay_user}
            = $config{'mailserver_relay_user'};
        $cw = 1;

    }

    if ( $config{'ntp_server'} eq "" ) {
        $config{'ntp_server'} = "no";

        $ConfigFile_compatibility->{_}->{ntp_server} = $config{'ntp_server'};
        $cw = 1;

    }



    if ( $config{'profile'} eq "all-in-one" ) {
        $config{'profile'} = "Server, Database, Framework, Sensor";

        $ConfigFile_compatibility->{_}->{profile} = $config{'profile'};
        $cw = 1;

    }

    if ( $config{'profile'} eq "developer" ) {
        $config{'profile'} = "Server, Database, Framework, Sensor";

        $ConfigFile_compatibility->{_}->{profile} = $config{'profile'};
        $cw = 1;

    }

    if ( $config{'profile'} eq "database" ) {
        $config{'profile'} = "Database";

        $ConfigFile_compatibility->{_}->{profile} = $config{'profile'};

        $cw = 1;

    }

    if ( $config{'profile'} eq "sensor" ) {
        $config{'profile'} = "Sensor";

        $ConfigFile_compatibility->{_}->{profile} = $config{'profile'};

        $cw = 1;

    }

    if ( $config{'upgrade'} ) {
        delete $ConfigFile_compatibility->{_}->{upgrade};
        $cw = 1;
    }


    delete $ConfigFile_compatibility->{_}->{version};
    delete $ConfigFile_compatibility->{_}->{language};

	# FIX VERSION

	#
	# [ database ]


	# FIX ACL_DB

    if ( $config{'database_ip'} eq "" ) {
        $config{'database_ip'} = "localhost";

        $ConfigFile_compatibility->{database}->{db_ip}
            = $config{'database_ip'};

        $cw = 1;

    }

	# FIX DB_PORT
	# FIX EVENT_DB
	# FIX OCS_DB
	# FIX OSSIM_DB
	# FIC OSVDB_DB
	# FIX PASS

    #if ( $config{'rebuild_database'} eq "" ) {
    #   $config{'rebuild_database'} = "no";

    #   $ConfigFile_compatibility->{database}->{rebuild_database}
    #        = $config{'rebuild_database'};
    #    $cw = 1;

    #}

    if ( $config{'rebuild_database'} ) {
        delete $ConfigFile_compatibility->{database}->{rebuild_database};
        $cw = 1;
    }
    if ( $config{'database_type'} ) {
        delete $ConfigFile_compatibility->{database}->{type};
        $cw = 1;
    }
    if ( $config{'database_port'} ) {
        delete $ConfigFile_compatibility->{database}->{db_port};
        $cw = 1;
    }
    if ( $config{'database_osvdb'} ) {
        delete $ConfigFile_compatibility->{database}->{osvdb_db};
        $cw = 1;
    }
    if ( $config{'database_ossim'} ) {
        delete $ConfigFile_compatibility->{database}->{ossim_db};
        $cw = 1;
    }
    delete $ConfigFile_compatibility->{database}->{ocs_db};
    if ( $config{'database_event'} ) {
        delete $ConfigFile_compatibility->{database}->{event_db};
        $cw = 1;
    }
    if ( $config{'database_acl'} ) {
        delete $ConfigFile_compatibility->{database}->{acl_db};
        $cw = 1;
    }

	# FIX TYPE
	# FIX USER

	#
	# [expert]
	#
	# FIX PROFILE
    if ( $config{'expert_profile'} ) {
        delete $ConfigFile_compatibility->{expert};
        $cw = 1;
    }
	#
	# [firewall]
	#

   	if ( $config{'firewall_active'} eq "" ) {
        $config{'firewall_active'} = "yes";

        $ConfigFile_compatibility->{firewall}->{active}
            = $config{'firewall_active'};
        $cw = 1;

    }

	#
	# [framework ]
	#

    if ( $config{'framework_https'} ) {
        delete $ConfigFile_compatibility->{framework}->{framework_https};
        $cw = 1;
    }
    if ( $config{'framework_https_cert'} eq "" ) {
        $config{'framework_https_cert'} = "default";

        $ConfigFile_compatibility->{framework}->{framework_https_cert}
            = $config{'framework_https_cert'};
        $cw = 1;

    }
    if ( $config{'framework_https_key'} eq "" ) {
        $config{'framework_https_key'} = "default";

        $ConfigFile_compatibility->{framework}->{framework_https_key}
            = $config{'framework_https_key'};
        $cw = 1;

    }

    if ( $config{'framework_ip'} eq "" ) {
        $config{'framework_ip'} = $config{'admin_ip'};

        $ConfigFile_compatibility->{framework}->{framework_ip}
            = $config{'framework_ip'};
        $cw = 1;

    }
    if ( $config{'framework_port'} ) {
        delete $ConfigFile_compatibility->{framework}->{framework_port};
        $cw = 1;
    }

	#
	# [sensor]
	#


	# FIX DETECTORS

    if ( $config{'ids_rules_flow_control'} eq "" ) {
        $config{'ids_rules_flow_control'} = "yes";

        $ConfigFile_compatibility->{sensor}->{ids_rules_flow_control}
            = $config{'ids_rules_flow_control'};
        $cw = 1;

    }


	# FIX INTERFACES
	# FIX IP
	# FIX MONITORS

	if ( ( $config{'mservers'} // "" ) eq "" ) {
        $config{'mservers'} = "no";

        $ConfigFile_compatibility->{sensor}->{mservers}
            = $config{'mservers'};
        $cw = 1;

    }

    if ( !defined($config{'asec'}) || $config{'asec'} eq "" ) {
        $config{'asec'} = "no";

        $ConfigFile_compatibility->{sensor}->{asec}
            = $config{'asec'};
        $cw = 1;

    }

    if ( ( $config{'sensor_ctx'} // "" ) eq "" ) {
        $config{'sensor_ctx'} = "";

        $ConfigFile_compatibility->{sensor}->{sensor_ctx}
            = $config{'sensor_ctx'};
        $cw = 1;

    }

	# FIX NAME

    if ( $config{'netflow'} eq "" ) {
        $config{'netflow'} = "yes";

        $ConfigFile_compatibility->{sensor}->{netflow} = $config{'netflow'};
        $cw = 1;

    }

    if ( $config{'netflow_remote_collector_port'} eq "" ) {

        my $curnrcp;

        # autoconfig from final config if exists
        if ( -f "$fprobeconfig" ) {
            $curnrcp
                = `grep "^FLOW_COLLECTOR" $fprobeconfig | awk -F ':' '{print\$2}'| awk -F'\"' '{print\$1}'`;
            $curnrcp =~ s/\n//g;
        }
        else {
            $curnrcp = "555";
        }

        # if exists final config, but its value for collector, is empty
        if ( -z "$curnrcp" ) {
            $curnrcp = "555";
        }
        $config{'netflow_remote_collector_port'} = "$curnrcp";

        $ConfigFile_compatibility->{sensor}->{netflow_remote_collector_port}
            = $config{'netflow_remote_collector_port'};
        $cw = 1;

    }


    if ( $config{'sensor_networks'} eq "" ) {
        $config{'sensor_networks'}
            = "192.168.0.0/16,172.16.0.0/12,10.0.0.0/8";

        $ConfigFile_compatibility->{sensor}->{networks}
            = $config{'sensor_networks'};
        $cw = 1;

    }
    if ( $config{'override_sensor'} ) {
        delete $ConfigFile_compatibility->{sensor}->{override_sensor};
        $cw = 1;
    }
    if ( $config{'pci_express'} ) {
        delete $ConfigFile_compatibility->{sensor}->{pci_express};
        $cw = 1;
    }

	# FIX PCI_EXPRESS

    if ( $config{'rsyslogdns_disable'} eq "" ) {
        $config{'rsyslogdns_disable'} = "yes";

        $ConfigFile_compatibility->{sensor}->{rsyslog_dnslookups_disable}
            = $config{'rsyslogdns_disable'};
        $cw = 1;

    }
    if ( $config{'rsyslogdns_disable'} ) {
        delete $ConfigFile_compatibility->{sensor}->{rsyslog_dnslookups_disable};
        $cw = 1;
    }

    my $agenttzone;

    $agenttzone = `cat /etc/timezone`;
    $agenttzone =~ s/\n//g;

    if ( $config{'sensor_tzone'} ne $agenttzone ) {

        $config{'sensor_tzone'} = "$agenttzone";

        $ConfigFile_compatibility->{sensor}->{tzone}
            = $config{'sensor_tzone'};
        $cw = 1;

    }


	#
	# [ server ]
	#

	if ( $config{'alienvault_ip_reputation'} eq "" ) {
        $config{'alienvault_ip_reputation'} = "enabled";
        console_log("Compatibility mode: Server reputation");
        $ConfigFile_compatibility->{server}->{alienvault_ip_reputation}
            = $config{'alienvault_ip_reputation'};
        $cw = 1;

    }

    if ( $config{'server_ip'} eq "" ) {
        $config{'server_ip'} = "$config{'admin_ip'}";

        $ConfigFile_compatibility->{server}->{server_ip}
            = $config{'server_ip'};
        $cw = 1;

    }

    if ( $config{'server_license'} ) {
        delete $ConfigFile_compatibility->{server}->{server_license};
        $cw = 1;
    }
    if ( $config{'server_port'} ) {
        delete $ConfigFile_compatibility->{server}->{server_port};
        $cw = 1;
    }

	# FIX SERVER_PLUGINS
	# FIX SERVER_PORT

    if (   ( $profile_server == 1 )
        || ( $profile_database == 1 )
        || ( $profile_framework == 1 ) )
    {
        if (   ( $config{'server_pro'} eq "" )
            || ( $config{'server_pro'} eq "no" ) )
        {
            my $server_dbvalue
                = `echo "select value from alienvault.config where conf='ossim_server_version';"| ossim-db |grep -i "pro"`;

            # system("echo \"$server_dbvalue\" >> /tmp/tstdbval4srv");
            if ( $server_dbvalue eq "" ) {
                $config{'server_pro'} = "no";

            }
            else {
                $config{'server_pro'} = "yes";
                $cw = 1;

            }
            $ConfigFile_compatibility->{server}->{server_pro}
                = $config{'server_pro'};

        }

    }


   if ( $config{'server_pro'} eq "yes" ) {
        if ( ( $config{'rservers'} // "" ) eq "" ) {

            $config{'rservers'} = "no";
            $ConfigFile_compatibility->{server}->{rservers}
                = $config{'rservers'};
            $cw = 1;

        }
    }

	    if ( $config{'server_pro'} eq "yes" ) {
        if ( ( $config{'idm_mssp'} // "" ) eq "" ) {
            $config{'idm_mssp'} = "no";
            console_log("Compatibility mode: Server IDM MSSP");
            $ConfigFile_compatibility->{server}->{idm_mssp}
                = $config{'idm_mssp'};
            $cw = 1;

        }
    }


	#
	# [snmp]
	#

	 if ( $config{'snmp_comunity'} eq "" ) {
        $config{'snmp_comunity'} = "public";

        $ConfigFile_compatibility->{snmp}->{community}
            = $config{'snmp_comunity'};

        $cw = 1;

    }


    if ( $config{'snmpd'} eq "" ) {
        $config{'snmpd'} = "yes";

        $ConfigFile_compatibility->{snmp}->{snmpd} = $config{'snmpd'};
        $cw = 1;

    }
    if ( $config{'snmptrap'} eq "" ) {
        $config{'snmptrap'} = "yes";

        $ConfigFile_compatibility->{snmp}->{snmptrap} = $config{'snmptrap'};
        $cw = 1;

    }

	#
	# [update]
	#
    if ( $config{'update_proxy'} eq "" ) {
        $config{'update_proxy'} = "disabled";
        $ConfigFile_compatibility->{update}->{update_proxy}
            = $config{'update_proxy'};
        $cw = 1;

    }

	if ( $config{'update_proxy_dns'} eq "" ) {
        $config{'update_proxy_dns'} = "my.proxy.com";
        $ConfigFile_compatibility->{update}->{update_proxy_dns}
            = $config{'update_proxy_dns'};
        $cw = 1;

    }

   	if ( $config{'update_proxy_pass'} eq "" ) {
        $config{'update_proxy_pass'} = "disabled";
        $ConfigFile_compatibility->{update}->{update_proxy_pass}
            = $config{'update_proxy_pass'};
        $cw = 1;

    }

	if ( $config{'update_proxy_port'} eq "" ) {
        $config{'update_proxy_port'} = "disabled";
        $ConfigFile_compatibility->{update}->{update_proxy_port}
            = $config{'update_proxy_port'};
        $cw = 1;

    }

    if ( $config{'update_proxy_user'} eq "" ) {
        $config{'update_proxy_user'} = "disabled";
        $ConfigFile_compatibility->{update}->{update_proxy_user}
            = $config{'update_proxy_user'};
        $cw = 1;

    }

	#
	# [vpn]
	#

	# FIX VPN_INFRAESTRUCTURE
	# FIX VPN_NET

    if ( ( $config{'vpn_net'} // "" ) eq "" ) {
		$config{'vpn_net'} = "10.67.68";
		$ConfigFile_compatibility->{vpn}->{vpn_net}
			= $config{'vpn_net'};
    	$cw = 1;
    }

	if ( ( $config{'vpn_netmask'} // "" ) eq "" ) {
        $config{'vpn_netmask'} = "255.255.255.0";
        $ConfigFile_compatibility->{vpn}->{vpn_netmask}
            = $config{'vpn_netmask'};
        $cw = 1;
    }
           
	if ( ( $config{'vpn_port'} // "" ) eq "" ) {
		$config{'vpn_port'} = "33800";
        $ConfigFile_compatibility->{vpn}->{vpn_port}
			= $config{'vpn_port'};
        $cw = 1;
    }

	my $vpn_ip = `ip a | grep inet | grep tun | grep $config{'vpn_net'}| awk '{print \$2}'` // q{};
	my $tun_iface = `ip a | grep inet | grep tun | grep $config{'vpn_net'}| awk '{print \$NF}'` // q{};
	$vpn_ip =~ s/\n//g;
	$tun_iface =~ s/\n//g;
	if ( ! $vpn_ip eq "" ){

		my $tun_iface_up = `ip a | grep "$tun_iface:" | grep ",UP,"`;

		if ( ! $tun_iface_up eq "" ){
	
			if ( $vpn_ip ne ( $config{'vpn_ip'} // q{} ) ){
				$config{'vpn_ip'} = "$vpn_ip";
				$ConfigFile_compatibility->{vpn}->{vpn_ip}
					= $config{'vpn_ip'};
				$cw = 1;
			}
		}

	}else{

		if ( ! $config{'vpn_ip'} eq "" ){
			$config{'vpn_ip'} = "$vpn_ip";                                                                                                                        
			$ConfigFile_compatibility->{vpn}->{vpn_ip}
				= $config{'vpn_ip'};
			$cw = 1;
		}

	}

	# FIX VPN PORT


	#
	# [ha]
	#

    if ( $config{'server_pro'} eq "yes" ) {

        if ( ( $config{'ha_heartbeat_start'} // "" ) eq "" ) {
            $config{'ha_heartbeat_start'} = "no";
            $ConfigFile_compatibility->{ha}->{ha_heartbeat_start}
                = $config{'ha_heartbeat_start'};
            $cw = 1;
        }

        if ( ( $config{'ha_role'} // "" ) eq "" ) {
            $config{'ha_role'}                         = "master";
            $ConfigFile_compatibility->{ha}->{ha_role} = $config{'ha_role'};
            $cw                                        = 1;
        }

        if ( ( $config{'ha_device'} // "" ) eq "" ) {
            $config{'ha_device'} = $config{'interface'};
            $ConfigFile_compatibility->{ha}->{ha_device}
                = $config{'ha_device'};
            $cw = 1;
        }

        if ( ( $config{'ha_virtual_ip'} // "" ) eq "" ) {
            $config{'ha_virtual_ip'} = "unconfigured";
            $ConfigFile_compatibility->{ha}->{ha_virtual_ip}
                = $config{'ha_virtual_ip'};
            $cw = 1;
        }

        if ( ( $config{'ha_local_node_ip'} // "" ) eq "" ) {
            $config{'ha_local_node_ip'} = $config{'admin_ip'};
            $ConfigFile_compatibility->{ha}->{ha_local_node_ip}
                = $config{'ha_local_node_ip'};
            $cw = 1;
        }

        if ( ( $config{'ha_other_node_ip'} // "" ) eq "" ) {
            $config{'ha_other_node_ip'} = "unconfigured";
            $ConfigFile_compatibility->{ha}->{'ha_other_node_ip'}
                = $config{'ha_other_node_ip'};
            $cw = 1;
        }

        if ( ( $config{'ha_other_node_name'} // "" ) eq "" ) {
            $config{'ha_other_node_name'} = "unconfigured";
            $ConfigFile_compatibility->{ha}->{ha_other_node_name}
                = $config{'ha_other_node_name'};
            $cw = 1;
        }

        if ( ( $config{'ha_password'} // "" ) eq "" ) {
            $config{'ha_password'} = "unconfigured";
            $ConfigFile_compatibility->{ha}->{ha_password}
                = $config{'ha_password'};
            $cw = 1;
        }

        if ( ( $config{'ha_keepalive'} // "" ) eq "" ) {
            $config{'ha_keepalive'} = "3";
            $ConfigFile_compatibility->{ha}->{ha_keepalive}
                = $config{'ha_keepalive'};
            $cw = 1;
        }

        if ( ( $config{'ha_deadtime'} // "" ) eq "" ) {
            $config{'ha_deadtime'} = "10";
            $ConfigFile_compatibility->{ha}->{ha_deadtime}
                = $config{'ha_deadtime'};
            $cw = 1;
        }

        if ( ( $config{'ha_log'} // "" ) eq "" ) {
            $config{'ha_log'}                         = "no";
            $ConfigFile_compatibility->{ha}->{ha_log} = $config{'ha_log'};
            $cw                                       = 1;
        }

        if ( ( $config{'ha_autofailback'} // "" ) eq "" ) {
            $config{'ha_autofailback'} = "no";
            $ConfigFile_compatibility->{ha}->{ha_autofailback}
                = $config{'ha_autofailback'};
            $cw = 1;
        }

        if ( ( $config{'ha_heartbeat_comm'} // "" ) eq "" ) {
            $config{'ha_heartbeat_comm'} = "bcast";
            $ConfigFile_compatibility->{ha}->{ha_heartbeat_comm}
                = $config{'ha_heartbeat_comm'};
            $cw = 1;
        }
        if ( ( $config{'ha_ping_node'} // "" ) eq "" ) {
            $config{'ha_ping_node'} = "default";
            $ConfigFile_compatibility->{ha}->{ha_ping_node}
                = $config{'ha_ping_node'};
            $cw = 1;
        }

    }
#    else {
#        if ($is_ha_present) {
#            delete( $ConfigFile_compatibility->{ha} );
#            $cw = 1;
#        }
#    }


       # Compatibility ends



    # Write T hash if $cw=1:
    if ( $cw == 1 ) {

		if ( $write_allow == 1 ){
		    $ConfigFile_compatibility->write($config_file_tmp);
		    system("mv $config_file_tmp $config_file");
# next cp is already done at the end of the execution flow (foother). see Avconfig_profile_common.pm
# #           system("cp -rf $config_file $config_file_last");
#

		}
        $cw = 0;
    }

    if ( defined( $config{'ha_heartbeat_start'} ) ) {
        if ( $config{'ha_heartbeat_start'} eq "yes" ) {
            if ( $config{'ha_virtual_ip'} ne "unconfigured" ) {
                $config{'admin_ip'} = $config{'ha_virtual_ip'};
                if ( $profile_framework == 1 ) {
                    $config{'framework_ip'} = $config{'ha_virtual_ip'};
                }
                if ( $profile_server == 1 ) {
                    $config{'server_ip'} = $config{'ha_virtual_ip'};
                }
                #if ( $profile_sensor == 1 ) {
                #    $config{'sensor_ip'} = $config{'ha_virtual_ip'};
                #}
            }
        }
    }

    return %config;
}

sub last_config() {

    #verbose_log("Get last values");

    if ( !-f $config_file_last ) {
        system("cp -rf $config_file $config_file_last");
    }

    $pars_conf_last = Config::Tiny->read($config_file_last);
    if ( !defined $pars_conf_last ) {
        error(
            "Couldn't read $config_file_last: '$Config::Tiny->strerr', install ossim (apt-get install ossim)"
        );
    }

    sub getprop2 {
        my $section  = shift;
        my $property = shift;

        return $pars_conf_last->{$section}->{$property};
    }

    $config_last{'profile'} = getprop2( "_", "profile" );

    #@profiles_arr_last = split( /,\s*/, $config_last{'profile'} );
    $config_last{'interface'}      = getprop2( "_", "interface" );
    $config_last{'language'}       = 'en';
    $config_last{'hostname'}       = getprop2( "_", "hostname" );
    $config_last{'domain'}         = getprop2( "_", "domain" );
    $config_last{'admin_ip'}       = getprop2( "_", "admin_ip" );
    $config_last{'admin_netmask'}  = getprop2( "_", "admin_netmask" );
    $config_last{'admin_gateway'}  = getprop2( "_", "admin_gateway" );
    $config_last{'admin_dns'}      = getprop2( "_", "admin_dns" );
    $config_last{'first_init'}     = 'no';
    $config_last{'upgrade'}        = 'no';
    $config_last{'ntp_server'}     = getprop2( "_", "ntp_server" );

    $config_last{'database_ip'} = getprop2( "database", "db_ip" );

    $config_last{'database_port'}  = '3306';
    $config_last{'database_type'}  = 'mysql';
    $config_last{'database_user'}  = getprop2( "database", "user" );
    $config_last{'database_pass'}  = getprop2( "database", "pass" );
    $config_last{'database_event'} = 'alienvault_siem';
    $config_last{'database_ossim'} = 'alienvault';
    $config_last{'database_acl'}   = 'ossim_acl';
    $config_last{'database_osvdb'} = 'osvdb';
    $config_last{'rebuild_database'} = 'no';
    $config_last{'innodb'}         = getprop2( "database", "innodb" );
    $config_last{'server_ip'}      = getprop2( "server",   "server_ip" );
    $config_last{'server_port'}    = '40001';
    $config_last{'server_pro'}     = getprop2( "server",   "server_pro" );
    $config_last{'server_license'} = 'no';
    $config_last{'rservers'}       = getprop2( "server",   "rservers" );

    #@rservers_arr_last = split( /;\s*/, $config_last{'rservers'} );
    $config_last{'alienvault_ip_reputation'}
        = getprop2( "server", "alienvault_ip_reputation" );
    $config_last{'idm_mssp'} = getprop2( "server", "idm_mssp" );

    $config_last{'fixed_server_plugins'}
        = getprop2( "server", "server_plugins" );
    $config_last{'sensor_ctx'}         = getprop2( "sensor", "sensor_ctx" );
    $config_last{'sensor_ip'}         = getprop2( "sensor", "ip" );
    $config_last{'sensor_name'}       = getprop2( "sensor", "name" );
    $config_last{'sensor_detectors'}  = getprop2( "sensor", "detectors" );
    $config_last{'sensor_monitors'}   = getprop2( "sensor", "monitors" );
    $config_last{'sensor_interfaces'} = getprop2( "sensor", "interfaces" );
    $config_last{'sensor_networks'}   = getprop2( "sensor", "networks" );

    #@sensor_interfaces_arr_last =
    # split( /,\s*/, $config_last{'sensor_interfaces'} );
    $config_last{'sensor_priority'} = getprop2( "sensor", "priority" );
    $config_last{'sensor_tzone'}    = getprop2( "sensor", "tzone" );
    $config_last{'override_sensor'} = 'False';
    $config_last{'rsyslogdns_disable'} = 'yes';
    $config_last{'netflow'} = getprop2( "sensor", "netflow" );
    $config_last{'netflow_remote_collector_port'}
        = getprop2( "sensor", "netflow_remote_collector_port" );

    $config_last{'framework_ip'} = getprop2( "framework", "framework_ip" );
    $config_last{'framework_port'} = '40003';
    $config_last{'framework_https'} = 'yes';
    $config_last{'framework_https_key'}
        = getprop2( "framework", "framework_https_key" );
    $config_last{'framework_https_cert'}
        = getprop2( "framework", "framework_https_cert" );

    $config_last{'snmpd'}         = getprop2( "snmp", "snmpd" );
    $config_last{'snmptrap'}      = getprop2( "snmp", "snmptrap" );
    $config_last{'snmp_comunity'} = getprop2( "snmp", "community" );

    $config_last{'firewall_active'} = getprop2( "firewall", "active" );

    $config_last{'vpn_net'} = getprop2( "vpn", "vpn_net" );
    $config_last{'vpn_netmask'} = getprop2( "vpn", "vpn_netmask" );
    $config_last{'vpn_infraestructure'}
        = getprop2( "vpn", "vpn_infraestructure" );
    $config_last{'vpn_port'} = getprop2( "vpn", "vpn_port" );

    #update
    $config_last{'update_proxy'} = getprop2( "update", "update_proxy" );
    $config_last{'update_proxy_user'}
        = getprop2( "update", "update_proxy_user" );
    $config_last{'update_proxy_pass'}
        = getprop2( "update", "update_proxy_pass" );
    $config_last{'update_proxy_port'}
        = getprop2( "update", "update_proxy_port" );
    $config_last{'update_proxy_dns'}
        = getprop2( "update", "update_proxy_dns" );

    $config_last{'ha_heartbeat_start'}
        = getprop2( "ha", "ha_heartbeat_start" );
    $config_last{'ha_role'}          = getprop2( "ha", "ha_role" );
    $config_last{'ha_device'}        = getprop2( "ha", "ha_device" );
    $config_last{'ha_virtual_ip'}    = getprop2( "ha", "ha_virtual_ip" );
    $config_last{'ha_local_node_ip'} = getprop2( "ha", "ha_local_node_ip" );
    $config_last{'ha_other_node_ip'} = getprop2( "ha", "ha_other_node_ip" );
    $config_last{'ha_other_node_name'}
        = getprop2( "ha", "ha_other_node_name" );
    $config_last{'ha_password'}       = getprop2( "ha", "ha_password" );
    $config_last{'ha_keepalive'}      = getprop2( "ha", "ha_keepalive" );
    $config_last{'ha_deadtime'}       = getprop2( "ha", "ha_deadtime" );
    $config_last{'ha_log'}            = getprop2( "ha", "ha_log" );
    $config_last{'ha_autofailback'}   = getprop2( "ha", "ha_autofailback" );
    $config_last{'ha_heartbeat_comm'} = getprop2( "ha", "ha_heartbeat_comm" );
    $config_last{'ha_ping_node'}      = getprop2( "ha", "ha_ping_node" );

    return %config_last;
}

1;