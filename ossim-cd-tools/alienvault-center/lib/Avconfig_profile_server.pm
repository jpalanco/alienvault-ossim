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

package Avconfig_profile_server;

use v5.10;
use strict;
use warnings;
no warnings 'experimental::smartmatch';
#use diagnostics;
use AV::ConfigParser;
use AV::Log;

use Config::Tiny;
use Perl6::Slurp;


use DateTime;

my $script_msg
    = "# Automatically generated for Alienvault-reconfig scripts. DO NOT TOUCH!";
my $VERSION       = 1.00;
my $servercfg     = "/etc/ossim/server/config.xml";
my $monit_file    = "/etc/monit/alienvault/avserver.monitrc";
my $add_hosts     = "yes";

my $profile_sensor = 0;
my $profile_framework = 0;
my $profile_database = 0;

my $server_uuid = `/usr/bin/alienvault-system-id`;

my %config;
my %config_last;


# FIXME Unknown source variables
my $server_port;
my $server_hostname;
my $server_ip;
my $framework_host;
my $framework_port;
my $db_pass;
my $db_pass_last;
my $db_host;
# FIXME
my ( $stdout, $stderr ) = ( q{}, q{} );


sub config_profile_server() {

    # Configure :
    # 	$servercfg
    #   monit (part)
    # 	iptables (part)
    #	add sever host into alienvault.host
    #   Remember restart ossim-server, monit and iptables.



    %config      = AV::ConfigParser::current_config;
    %config_last = AV::ConfigParser::last_config;

    $server_hostname = $config{'hostname'};
    $server_port     = "40001";
    $server_ip       = $config{'server_ip'};
    $framework_port  = $config{'framework_port'};
    $framework_host  = $config{'framework_ip'};
    $db_host         = $config{'database_ip'};
    $db_pass         = $config{'database_pass'};
    $db_pass_last    = $config_last{'database_pass'};
    my $ha_ip        = $config{'ha_virtual_ip'};

    # HA slave case
    if ($config{'ha_heartbeat_start'} eq 'yes' && $ha_ip =~ /\d+\.\d+\.\d+\.\d+/) {
        my $current_uuid = `echo "select LOWER(CONCAT(LEFT(hex(id), 8), '-', MID(hex(id), 9,4), '-', MID(hex(id), 13,4), '-', MID(hex(id), 17,4), '-', RIGHT(hex(id), 12))) as uuid from alienvault.server where inet6_ntoa(ip)='$ha_ip'" | ossim-db | tail -1 | tr -d '\n'`;
        $server_uuid = $current_uuid if ($current_uuid);
    }

    my @profiles_arr;

    if ( $config{'profile'} eq "all-in-one" ) {
        @profiles_arr = ( "Server", "Database", "Framework", "Sensor" );
    }
    else {
        @profiles_arr = split( /,\s*/, $config{'profile'} );
    }

    foreach my $profile (@profiles_arr) {

        given ($profile) {
            when ( m/Sensor/ )    { $profile_sensor = 1; }
            when ( m/Framework/ ) { $profile_framework = 1; }
            when ( m/Database/ )  { $profile_database = 1; }
        }

    }

    console_log(
        "-------------------------------------------------------------------------------"
    );
    console_log("Configuring Server Profile");
    dp("Configuring Server Profile");

    verbose_log("System UUID: $server_uuid");

    configure_server_reputation();
    configure_server_database();
    configure_server_monit();
    configure_server_add_host();
    configure_server_reputation_cron();

# FIXME my $profile_framework
    # Disable munin server when framework is not installed with server
    if ( ( $profile_framework != 1 ) && ( -f "/etc/cron.d/munin" ) ) {
        unlink("/etc/cron.d/munin");
    }
# FIXME my %reset;

    my %reset;
    # Remember reset
    $reset{'ossim-server'} = 1;
    $reset{'monit'}        = 1;
    $reset{'iptables'}     = 1;

    return %reset;

}

###################################################

sub configure_server_reputation(){

        my $reputation_p = `cat $servercfg | grep "<reputation filename"`;
        $reputation_p =~ s/\n//g;
        if ( $config{'alienvault_ip_reputation'} eq "enabled" ) {
            if ( $reputation_p eq "" ) {
                my $command
                    = "sed -i \'s:</config>:<reputation filename=\\\"/etc/ossim/server/reputation.data\\\"/>\\n</config>:\' $servercfg";
                debug_log("$command");
                system($command);
            }
        }
        else {
            if ( $reputation_p ne "" ) {
                my $command
                    = "sed -i \'s:<reputation filename=\\\"/etc/ossim/server/reputation.data\\\"/>::\' $servercfg";
                debug_log("$command");
                system($command);
            }
        }

    }
sub configure_server_database(){

    # -- config table
    debug_log(
        "Server Profile: Updating alienvault.config table (server_id)");
    my $command
        = "echo \"INSERT IGNORE INTO config (conf, value) VALUES ('server_id', \'$server_uuid\');\" | ossim-db";
    debug_log($command);
    system($command);

    debug_log(
        "Server Profile: Updating default policy");
    $command
        = "echo \"UPDATE policy_target_reference SET target_id = UNHEX(REPLACE('$server_uuid','-','')) WHERE target_id = 0x00000000000000000000000000000000;\" | ossim-db";
    debug_log($command);
    system($command);

    my $tmz = `cat /etc/timezone`;
    $tmz =~ s/\n//g;
    $tmz =~ s/ //g;

    verbose_log(
        "Server Profile: Updating acl_entities table");
    $command
        = "echo \"UPDATE acl_entities SET server_id = (SELECT UNHEX(REPLACE (value, '-', '')) FROM config WHERE conf LIKE 'server_id'), timezone = '$tmz' WHERE id = (SELECT UNHEX(REPLACE (value, '-', '')) FROM config WHERE conf LIKE 'default_engine_id');\" | ossim-db";
    debug_log($command);
    system($command);

    $command
        = "echo \"UPDATE acl_entities SET server_id = (SELECT UNHEX(REPLACE (value, '-', '')) FROM config WHERE conf LIKE 'server_id'), timezone = '$tmz' WHERE id = (SELECT UNHEX(REPLACE (value, '-', '')) FROM config WHERE conf LIKE 'default_context_id');\" | ossim-db";
    debug_log($command);
    system($command);

    # -- server
    # n fwd servers could be inserted before local server entry, by avcenter, so we need to search for local uuid
    my $nentry = `echo "SELECT count(*) FROM alienvault.server WHERE (REPLACE(\'$server_uuid\','-','')) = hex(id);" | ossim-db | grep -v count`;
    $nentry =~ s/\n//;

    if ( $nentry eq "0" ) {

        # -- server_role
        verbose_log("Server Profile: Updating server_role");
        $command = "echo \"REPLACE INTO alienvault.server_role (server_id) VALUES (UNHEX(REPLACE('$server_uuid','-','')));\" | ossim-db";
        debug_log($command);
        system($command);

        verbose_log("Server Profile: no entry found for uuid $server_uuid in alienvault.server. Inserting");
        my $command
            = "echo \"REPLACE INTO alienvault.server (name, ip, port, id) VALUES (\'$server_hostname\', inet6_aton(\'$config{'admin_ip'}\'), \'$server_port\', UNHEX(REPLACE(\'$server_uuid\',\'-\',\'\')));\" | ossim-db $stdout $stderr";
        debug_log($command);
        system($command);
        
    }
    
    # check if system entries exists
    verbose_log("Server Profile: System update");

    my $s_uuid = `/usr/bin/alienvault-system-id | tr -d '-'`;

    my $profiles = 'Server';
    $profiles .= ',Framework' if ($profile_framework);
    $profiles .= ',Sensor' if ($profile_sensor);
    $profiles .= ',Database' if ($profile_database);

    my $admin_ip    = $config{'admin_ip'};

    $command = "echo \"CALL system_update(\'$s_uuid\',\'$server_hostname\',\'$admin_ip\',\'\',\'$profiles\',\'\',\'$server_hostname\',\'\',\'\',\'$server_uuid\')\" | ossim-db $stdout $stderr";
    debug_log($command);
    system($command);
        
}

sub configure_server_monit() {
    #
    # Modify if totalmem > 90% then restart depends on installed profiles
    # DB <= 16 -> 70%
    # DB > 16 -> 45%
    # No DB -> 90%
    
    my $ram = `grep MemTotal /proc/meminfo | awk '{print \$2}' | tr -d '\n'`;
    my $threshold = "90";
    if ($profile_database) {
        $threshold = ($ram <= 16000000) ? "70" : "45";
    }
    
    verbose_log("Server Profile: Configuring monit memory threshold to $threshold%");
    my $command = "sed -i 's:totalmem > .* then:totalmem > $threshold% then:' $monit_file";
    debug_log("$command");
    system($command);
}


sub configure_server_add_host(){

    if ( "$add_hosts" eq "yes" ) {
        ## add server host in db

        if ( "$config{'admin_ip'}" ne "$config_last{'admin_ip'}" ) {

            verbose_log(
                "Server Profile: Updating admin ip (old=$config_last{'admin_ip'} new=$config{'admin_ip'}) update alienvault.host table"
            );
            my $command
                = "echo \"UPDATE alienvault.host_ip SET ip = inet6_aton(\'$config{'admin_ip'}\') WHERE inet6_ntoa(ip) = \'$config_last{'admin_ip'}\'\" | ossim-db   $stdout $stderr ";
            debug_log($command);
            system($command);

        }else{

            # -- host (server)
            debug_log(
                "Server Profile: Inserting into alienvault.host table");

            if ( "$config{'hostname'}" ne "$config_last{'hostname'}" ){
                    my $command
                        = "echo \"UPDATE alienvault.host SET hostname = \'$config{'hostname'}\' WHERE hostname = \'$config_last{'hostname'}\'\"| ossim-db $stdout $stderr ";
                    debug_log($command);
                    system($command);
            }else{
                my $nentry
                = `echo "SELECT COUNT(*) FROM alienvault.host WHERE hostname = \'$config{'hostname'}\';" | ossim-db | grep -v COUNT`; $nentry =~ s/\n//;
                debug_log("Server Profile: nentry: $nentry");

                if ( $nentry eq "0" && $profile_sensor == 0) {
                        verbose_log("Server Profile: Inserting into host, host_ip");
                        my $command
                            = "echo \"SET \@uuid\:= UNHEX(REPLACE(UUID(),'-','')); INSERT IGNORE INTO alienvault.host (id,ctx,hostname,asset,threshold_c,threshold_a,alert,persistence,nat,rrd_profile,descr,lat,lon,av_component) VALUES (\@uuid,(SELECT UNHEX(REPLACE(value,'-','')) FROM alienvault.config WHERE conf = 'default_context_id'),\'$server_hostname\',\'2\',\'30\',\'30\',\'0\',\'0\',\'\',\'\',\'\',\'0\',\'0\',1); INSERT IGNORE INTO alienvault.host_ip (host_id,ip) VALUES (\@uuid,inet6_aton(\'$config{'admin_ip'}\'));\" | ossim-db $stdout $stderr ";
                        debug_log($command);
                        system($command);
                }else{
                    debug_log("Server Profile: (already inserted)");
                }
            }

        }
    }

}

sub configure_server_reputation_cron(){

    if ( $config{'alienvault_ip_reputation'} eq "enabled" ) {

        system(
            "echo \"15 * * * * root /usr/share/ossim-installer/update_reputation.py \" > /etc/cron.d/alienvault_ip_reputation"
        );
        system(
            "echo \"30 * * * * root /usr/share/ossim/scripts/send_reputation_feedback.py \" > /etc/cron.d/alienvault_ip_reputation_feedback"
        );

    }
    else {

        system("rm -rf /etc/cron.d/alienvault_ip_reputation");
        system("rm -rf /etc/cron.d/alienvault_ip_reputation_feedback");

    }


}

1;
