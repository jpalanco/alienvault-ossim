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
#use diagnostics;
use AV::ConfigParser;
use AV::Log;

use Config::Tiny;
use Perl6::Slurp;

use AV::uuid;

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

my $host_uuid = `echo "select value from config where conf=\'server_id\'" | ossim-db | grep -v value | tr -d '\n'`;
$host_uuid = `/usr/bin/alienvault-system-id` if ($host_uuid eq '');

my %config;
my %config_last;


# FIXME Unknown source variables
my $server_port;
my $server_hostname;
my $server_ip;
my $framework_host;
my $framework_port;
my $ossim_user;
my $db_pass;
my $db_pass_last;
my $db_host;
my $snort_user;
my $osvdb_user;
my @rservers_arr;
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

    $ossim_user = "root";
    $snort_user = "root";
    $osvdb_user = "root";

    my @profiles_arr;

    if ( $config{'profile'} eq "all-in-one" ) {
        @profiles_arr = ( "Server", "Database", "Framework", "Sensor" );
    }
    else {
        @profiles_arr = split( /,\s*/, $config{'profile'} );
    }

    @rservers_arr = split( /;\s*/, ( $config{'rservers'} // q{} ) );

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

    verbose_log("System UUID: $host_uuid");

    configure_server_crtkey();
    build_server_crtkey();
    configure_server_config_file();
    configure_server_reputation();
    configure_server_database();
    configure_server_new_path();
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

## cross correlate :
## INSERT IGNORE INTO alienvault.server_role values ('server',0,0,1,0,0,0,0,1,0);
}

###################################################

sub configure_server_crtkey(){

	if ( $db_pass ne $db_pass_last ) {
		console_log("Server profile: detected database password change");

        my $currentdt = DateTime->now( time_zone => 'local' );
		#verbose_log($currentdt);

		if ( ! -d "/var/ossim/keys/history/$currentdt" ) {
			my $command=qq{mkdir -p /var/ossim/keys/history/$currentdt};
			debug_log($command);
			system($command);
			$command=qq{mv /var/ossim/keys/rsa* /var/ossim/keys/history/$currentdt};
			debug_log($command);
			system($command);
		}
	}
}

sub build_server_crtkey() {

    if ( !-f "/var/ossim/keys/rsaprv.pem" ) {
        console_log("Server profile: rebuild server crt and key");

        my $command = <<'END_OF_COMMAND';
mkdir -p /var/ossim/keys
openssl genrsa -des3 -passout pass:`grep "^pass=" /etc/ossim/ossim_setup.conf | cut -f 2 -d "=" | grep -v ^$` -out /var/ossim/keys/rsaprv.pem 1024;
openssl rsa  -passout pass:`grep "^pass=" /etc/ossim/ossim_setup.conf | cut -f 2 -d "=" | grep -v ^$`  -passin pass:`grep "^pass=" /etc/ossim/ossim_setup.conf | cut -f 2 -d "=" | grep -v ^$`  -in /var/ossim/keys/rsaprv.pem -pubout -out /var/ossim/keys/rsapub.pem;
yes | openssl req -new -key /var/ossim/keys/rsaprv.pem -out /var/ossim/keys/rsacsr.csr -batch -passin pass:`grep "^pass=" /etc/ossim/ossim_setup.conf | cut -f 2 -d "=" | grep -v ^$`;
cp /var/ossim/keys/rsaprv.pem /var/ossim/keys/rsaprv.pem.orig;
openssl rsa -in /var/ossim/keys/rsaprv.pem.orig -out /var/ossim/keys/rsaprv.pem -passin pass:`grep "^pass=" /etc/ossim/ossim_setup.conf | cut -f 2 -d "=" | grep -v ^$`;
openssl x509 -req -days 365 -in /var/ossim/keys/rsacsr.csr -signkey /var/ossim/keys/rsaprv.pem -out /var/ossim/keys/rsacrt.crt;
END_OF_COMMAND
        debug_log($command);
        system($command);
    }
}

sub configure_server_config_file(){

    # TODO: this can be removed when ossim-server.postinst handles db configuration triggers, hostname trigger 
    if ( -f $servercfg ) {
        my $command
            = "sed -i \"s:<server .*:<server port=\\\"$server_port\\\" name=\\\"$server_hostname\\\" ip=\\\"0.0.0.0\\\" id=\\\"$host_uuid\\\"\/>:\" $servercfg";
        debug_log("$command");
        system($command);
        $command
            = "sed -i \"s:<framework .*:<framework name=\\\"$server_hostname\\\"  ip=\\\"$framework_host\\\" port=\\\"$framework_port\\\"\/>:\" $servercfg";
        debug_log("$command");
        system($command);

        $command
            = "sed -i \"s:<datasource name=\\\"ossimDS\\\" .*:<datasource name=\\\"ossimDS\\\" provider=\\\"MySQL\\\" dsn=\\\"PORT=3306;USER=$ossim_user;PASSWORD=$db_pass;DATABASE=alienvault;HOST=$db_host\\\"\/>:\" $servercfg";
        debug_log("$command");
        system($command);

        $command
            = "sed -i \"s:<datasource name=\\\"snortDS\\\" .*:<datasource name=\\\"snortDS\\\" provider=\\\"MySQL\\\" dsn=\\\"PORT=3306;USER=$snort_user;PASSWORD=$db_pass;DATABASE=alienvault_siem;HOST=$db_host\\\"\/>:\" $servercfg";
        debug_log("$command");
        system($command);

        $command
            = "sed -i \"s:<datasource name=\\\"osvdbDS\\\" .*:<datasource name=\\\"osvdbDS\\\" provider=\\\"MySQL\\\" dsn=\\\"PORT=3306;USER=$osvdb_user;PASSWORD=$db_pass;DATABASE=alienvault_siem;HOST=$db_host\\\"\/>:\" $servercfg";
        debug_log("$command");
        system($command);

        $command
            = "sed -i \"s:sig_pass=.*:sig_pass=\\\"$db_pass\\\":\" $servercfg";
        debug_log("$command");
        system($command);

        `cat $servercfg | grep -v rserver | grep -v "</config>" > /tmp/tmp.rserver.xml`;

        if ( $config{'server_pro'} eq "yes" ) {

            if ( $config{'rservers'} ne "no" ) {

                if (@rservers_arr) {
                    `echo "  <rservers>" >> /tmp/tmp.rserver.xml`;
                    foreach (@rservers_arr) {
                        my @cm = split( /;\s*/, $_ );
                        foreach (@cm) {
                            my @cms = split( /,\s*/, $_ );
                            my ( $name, $ip, $port, $primary, $priority )
                                = @cms;
                            my $msg
                                = "<rserver name=\\\"$name\\\" ip=\\\"$ip\\\" port=\\\"$port\\\" primary=\\\"$primary\\\" priority=\\\"$priority\\\"/>";
                            system(
                                "echo \"    $msg\" >> /tmp/tmp.rserver.xml");
                            debug_log("$msg");
                        }

                    }
                    `echo "  </rservers>" >> /tmp/tmp.rserver.xml`;
                }
            }
        }

        `echo "</config>" >> /tmp/tmp.rserver.xml ; mv /tmp/tmp.rserver.xml $servercfg`;


		}

		#my $idm_p = `cat $servercfg | grep "<idm "`;
		#if ( $idm_p eq "" ){
		#	my $command="sed -i \"s:</config>:<idm port=\\\"40002\\\" ip=\\\"0.0.0.0\\\"/>\\n</config>:\" $servercfg";
		#	debug_log("$command");
		#	system($command);
		#}

}

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
        = "echo \"INSERT IGNORE INTO config (conf, value) VALUES ('server_id', \'$host_uuid\');\" | ossim-db";
    debug_log($command);
    system($command);

#    verbose_log(
#        "Server Profile: Updating alienvault.config table (default_context_id)");
#    my $command
#        = "echo \"INSERT IGNORE INTO config (conf, value) VALUES ('default_context_id', UUID());\" | ossim-db";
#    debug_log($command);
#    system($command);

#    verbose_log(
#        "Server Profile: Updating alienvault.config table (default_engine_id)");
#    my $command
#        = "echo \"INSERT IGNORE INTO config (conf, value) VALUES ('default_engine_id', UUID());\" | ossim-db";
#    debug_log($command);
#    system($command);


# host_uuid , actualizar server_id en 2 registros de acl_entities:

    # -- acl_entities
#    verbose_log(
#        "Server Profile: Updating acl_entities table");
#    my $command
#        = "echo \"INSERT IGNORE INTO acl_entities VALUES ((SELECT UNHEX(REPLACE (value, '-', '')) from config where conf like 'default_context_id'), (SELECT UNHEX(REPLACE (value, '-', '')) from config where conf like 'server_id'), 'My Company', 'admin', NULL, 'GMT', NULL, 'context');\" | ossim-db";
#    debug_log($command);
#    system($command);
#
#    my $command
#        = "echo \"INSERT IGNORE INTO acl_entities VALUES ((SELECT UNHEX(REPLACE (value, '-', '')) from config where conf like 'default_engine_id'), (SELECT UNHEX(REPLACE (value, '-', '')) from config where conf like 'server_id'), 'Default Engine', 'admin', NULL, 'GMT', NULL, 'engine');\" | ossim-db";
#    debug_log($command);
#    system($command);



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

    # -- corr_engine_contexts
#    verbose_log(
#        "Server Profile: Updating corr_engine_contexts");
#    my $command
#        = "echo \"INSERT IGNORE INTO corr_engine_contexts VALUES ((SELECT UNHEX(REPLACE (value, '-', '')) from config where conf like 'default_engine_id'), (SELECT UNHEX(REPLACE (value, '-', '')) from config where conf like 'default_context_id'), 'Default');\" | ossim-db";
#    debug_log($command);
#    system($command);


    # -- server
    # n fwd servers could be inserted before local server entry, by avcenter, so we need to search for local uuid
    my $nentry = `echo "SELECT count(*) FROM alienvault.server WHERE (REPLACE(\'$host_uuid\','-','')) = hex(id);" | ossim-db | grep -v count`;
    $nentry =~ s/\n//;

    if ( $nentry eq "0" ) {

        # -- server_role
        verbose_log("Server Profile: Updating server_role");
        $command = "echo \"REPLACE INTO alienvault.server_role (server_id) VALUES (UNHEX(REPLACE('$host_uuid','-','')));\" | ossim-db";
        debug_log($command);
        system($command);

        verbose_log("Server Profile: no entry found for uuid $host_uuid in alienvault.server. Inserting");
        my $command
            = "echo \"REPLACE INTO alienvault.server (name, ip, port, id) VALUES (\'$server_hostname\', inet6_pton(\'$config{'admin_ip'}\'), \'$server_port\', UNHEX(REPLACE(\'$host_uuid\',\'-\',\'\')));\" | ossim-db $stdout $stderr";
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

    my $sip    = ( ( $config{'ha_heartbeat_start'} // "" ) eq "yes" ) ? $config{'ha_local_node_ip'} : $config{'admin_ip'};
    my $haip   = ( ( $config{'ha_heartbeat_start'} // "" ) eq "yes" ) ? $config{'ha_virtual_ip'} : '';
    my $harole = ( ( $config{'ha_heartbeat_start'} // "" ) eq "yes" ) ? $config{'ha_role'} : '';        
    #my $command = "echo \"REPLACE INTO alienvault.system (id,name,admin_ip,vpn_ip,ha_ip,profile) VALUES (UNHEX(\'$s_uuid\'),\'$server_hostname\',inet6_pton(\'$sip\'),NULL,inet6_pton(\'$haip\'),\'$profiles\');\" | ossim-db $stdout $stderr";

    $command = "echo \"CALL system_update(\'$s_uuid\',\'$server_hostname\',\'$sip\',\'\',\'$profiles\',\'$haip\',\'$server_hostname\',\'$harole\',\'\',\'$host_uuid\')\" | ossim-db $stdout $stderr";
    debug_log($command);
    system($command);
        
}

# config new path for directives and user, disable some entities...
sub configure_server_new_path(){

   my $new_engine_id = `echo "select value from config where conf like 'default_engine_id'"| ossim-db | tail -1 $stdout $stderr`; $new_engine_id =~ s/\n//g;

    system("mkdir -p /etc/ossim/server/$new_engine_id") if ( ! -d "/etc/ossim/server/$new_engine_id" ) ;

    if ( ! -f "/etc/ossim/server/$new_engine_id/user.xml" ) {
			my $ucont = '<?xml version=\"1.0\" encoding=\"UTF-8\"?>';

			system ("echo \"$ucont\" > /etc/ossim/server/$new_engine_id/user.xml");

	}

    if ( ! -f "/etc/ossim/server/$new_engine_id/directives.xml" ) {
	    if ( -f "/usr/share/alienvault-directives-free/d_clean/templates/directives.xml" ) {
		system ("cp -af /usr/share/alienvault-directives-free/d_clean/templates/directives.xml /etc/ossim/server/$new_engine_id/");
	    } elsif ( -f "/usr/share/alienvault-directives-pro/d_clean/templates/directives.xml" ) {
		system ("cp -af /usr/share/alienvault-directives-pro/d_clean/templates/directives.xml /etc/ossim/server/$new_engine_id/");
	    } else {
		verbose_log("Server Profile: directives.xml template not found");
	    }
    }
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
                = "echo \"UPDATE alienvault.host_ip SET ip = inet6_pton(\'$config{'admin_ip'}\') WHERE inet6_ntop(ip) = \'$config_last{'admin_ip'}\'\" | ossim-db   $stdout $stderr ";
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

                if ( $nentry == "0" ) {
                        verbose_log("Server Profile: Inserting into host, host_ip");
                        my $command
                            = "echo \"SET \@uuid\:= UNHEX(REPLACE(UUID(),'-','')); INSERT IGNORE INTO alienvault.host (id,ctx,hostname,asset,threshold_c,threshold_a,alert,persistence,nat,rrd_profile,descr,lat,lon,av_component) VALUES (\@uuid,(SELECT UNHEX(REPLACE(value,'-','')) FROM alienvault.config WHERE conf = 'default_context_id'),\'$server_hostname\',\'2\',\'30\',\'30\',\'0\',\'0\',\'\',\'\',\'\',\'0\',\'0\',1); INSERT IGNORE INTO alienvault.host_ip (host_id,ip) VALUES (\@uuid,inet6_pton(\'$config{'admin_ip'}\'));\" | ossim-db $stdout $stderr ";
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
