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

package Avconfig_profile_database;

use v5.10;
use strict;
use warnings;
no warnings 'experimental::smartmatch';
#use diagnostics;

use Config::Tiny;
use DBI;

use AV::ConfigParser;
use AV::Log;
use Avtools;


my $script_msg
    = "# Automatically generated for ossim-reconfig scripts. DO NOT TOUCH!";
my $VERSION        = 1.00;
my $config_file    = "/etc/ossim/ossim_setup.conf";
my $framework_file = "/etc/ossim/framework/ossim.conf";
my $add_hosts      = "yes";

my %config;
my %config_last;
my @query_array;


my $server_hostname;
my $server_port;
my $server_ip;
my $framework_port;
my $framework_host;
my $db_host;
my $rebuild_db_host = "127.0.0.1";
my $db_pass;

my $ossim_user;
my $snort_user;
my $osvdb_user;

my @profiles_arr;

my $profile_database  = 0;
my $profile_server    = 0;
my $profile_framework = 0;
my $profile_sensor    = 0;


my %reset;

# FIXME: redirect globally $stdout, $stderr
my ($stdout, $stderr);

my $v_key_str = 0;
my $key_str;


sub config_profile_database() {

    %config      = AV::ConfigParser::current_config;
    %config_last = AV::ConfigParser::last_config;
    @query_array = "";

    $server_hostname = $config{'hostname'};
    $server_port     = "40001";
    $server_ip       = $config{'server_ip'};
    $framework_port  = $config{'framework_port'};
    $framework_host  = $config{'framework_ip'};
    $db_host         = $config{'database_ip'};
    $db_pass         = $config{'database_pass'};

    $ossim_user = "root";
    $snort_user = "root";
    $osvdb_user = "root";


    if ($config{'profile'} eq "all-in-one"){
	    @profiles_arr = ("Server","Database","Framework","Sensor");
    }else{
        @profiles_arr = split( /,\s*/, $config{'profile'} );
    }

    for my $profile (@profiles_arr) {
        given ($profile) {
            when ( m/Database/ )  { $profile_database  = 1; }
            when ( m/Server/ )    { $profile_server    = 1; }
            when ( m/Framework/ ) { $profile_framework = 1; }
            when ( m/Sensor/ )    { $profile_sensor    = 1; }
        }
    }


    console_log(
        "-------------------------------------------------------------------------------"
    );
    console_log("Configuring Database Profile");
    dp("Configuring Database Profile");

    my $command = "";

    if ($profile_database == 1 && $profile_server == 0 && $profile_framework == 0 && $profile_sensor == 0)
    {
        my $command = "sed -i \"s:^db_ip.*:db_ip=127.0.0.1:\" $config_file";
        debug_log("$command");
        system($command);
    }

	config_database_encryption_key();
	config_database_table_config();
	config_database_framework_file(); # certain scripts needs fields from this conf, even on DB profile
	config_database_vpn();
	config_database_add_host();
	config_database_disable_munin(); ## revisar esto


    # Remember reset

    $reset{'monit'} = 1;

    #$reset{'openvpn'} = 1;
    $reset{'iptables'} = 1;

    return %reset;

}

##############################


sub config_database_encryption_key(){

### default

	my $key_str   = "";
	my $v_key_str = 0;

#TODO: Use av-centerd SOAP to pass key from framework to remote reconfig command, or run db related only from fw...
	my $conn = Avtools::get_database();
	my $query
		= "SELECT `value` from `alienvault`.`config` WHERE `conf` = 'encryption_key';";
	my $sth = $conn->prepare($query);
	$sth->execute();
	$key_str = $sth->fetchrow_array();
	$sth->finish();

    if ( $key_str eq "" ) {
        $key_str = `cat /etc/ossim/framework/db_encryption_key| grep "^key=" |awk -F'=' '{print \$2}'`;
        $key_str=~ s/\n//g;

        $query
            = "REPLACE INTO `alienvault`.`config` VALUES ('encryption_key', '$key_str');";
        Avtools::execute_query_without_return("$query");
    }

	$conn->disconnect
		|| verbose_log("Disconnect error.\nError: $DBI::errstr");

# FIXME: use tr() for efficiency
	$key_str =~ s/(\n|\e)//g;

#--
# ----

#debug_log("key_str:$key_str");
	if ( $key_str
			=~ m/^[0-9A-Fa-f]{8}[\-][0-9A-Fa-f]{4}[\-][0-9A-Fa-f]{4}[\-][0-9A-Fa-f]{4}[\-][0-9A-Fa-f]{12}$/
	   )
	{
		$v_key_str = 1;
	}
	else {
		$v_key_str = 0;
		verbose_log("Database Profile: key not found");
	}


}
sub config_database_table_config(){


# ossim config table
#
#

	verbose_log("Database Profile: Updating ossim config table");

	my @query_array = (
			"REPLACE INTO config VALUES(\"snort_host\",\"$db_host\")",
			"REPLACE INTO config VALUES(\"phpgacl_host\",\"$db_host\")",
			"REPLACE INTO config VALUES(\"phpgacl_user\",\"root\")",
			"REPLACE INTO config VALUES(\"server_address\",\"$server_ip\")",
			"REPLACE INTO config VALUES(\"backup_host\",\"$db_host\")",
			"REPLACE INTO config VALUES(\"osvdb_host\",\"$db_host\")",
			"REPLACE INTO config VALUES(\"frameworkd_address\",\"$framework_host\")",
			"REPLACE INTO config VALUES(\"frameworkd_port\",\"$framework_port\")",
			"REPLACE INTO config VALUES(\"nagios_link\",\"/nagios3/\")"
			);
	Avtools::execute_query_without_return(@query_array);

	if ( $v_key_str == 1 ) {
		my @query_array = (
				"REPLACE INTO config VALUES(\"snort_pass\",AES_ENCRYPT(\'$db_pass\',\'$key_str\'))",
				"REPLACE INTO config VALUES(\"bi_pass\",AES_ENCRYPT(\'$db_pass\',\'$key_str\'))",
				"REPLACE INTO config VALUES(\"osvdb_pass\",AES_ENCRYPT(\'$db_pass\',\'$key_str\'))",
				"REPLACE INTO config VALUES(\"backup_pass\",AES_ENCRYPT(\'$db_pass\',\'$key_str\'))",
				"REPLACE INTO config VALUES(\"phpgacl_pass\",AES_ENCRYPT(\'$db_pass\',\'$key_str\'))"
				);
		foreach (@query_array) {
			Avtools::execute_query_without_return("$_");
		}
	}
	else {
		my @query_array = (
				"REPLACE INTO config VALUES(\"snort_pass\",\"$db_pass\")",
				"REPLACE INTO config VALUES(\"bi_pass\",\"$db_pass\")",
				"REPLACE INTO config VALUES(\"osvdb_pass\",\"$db_pass\")",
				"REPLACE INTO config VALUES(\"backup_pass\",\"$db_pass\")",
				"REPLACE INTO config VALUES(\"phpgacl_pass\",\"$db_pass\")"
				);
		foreach (@query_array) {
			Avtools::execute_query_without_return("$_");
		}
	}

	my $nessushost
		= `echo "select value from config where conf='nessus_host';" | ossim-db| grep -v value`;
	$nessushost =~ s/\n//;
	my $l1 = "localhost";
	my $l2 = "127.0.0.1";
	my $l3 = $config{'framework_ip'};
	if (   ( $nessushost eq $l1 )
			|| ( $nessushost eq $l2 )
			|| ( $nessushost eq $l3 ) )
	{
		if ( $v_key_str == 1 ) {
			my $query
				= "REPLACE INTO config VALUES(\"nessus_pass\",AES_ENCRYPT('$db_pass','$key_str'))";
			Avtools::execute_query_without_return("$query");

		}
		else {
			my $query
				= "REPLACE INTO config VALUES(\"nessus_pass\",\"$db_pass\")";
			Avtools::execute_query_without_return("$query");
		}
	}
}

sub config_database_framework_file(){

	if ( -f "$framework_file" ) {

		verbose_log("Database Profile: Preconfiguring framework file");
		my $command
			= "sed -i \"s:ossim_pass=.*:ossim_pass=$db_pass:\" $framework_file";
		debug_log("$command");
		system($command);

		$command
			= "sed -i \"s:ossim_host=.*:ossim_host=$db_host:\" $framework_file";
		debug_log("$command");
		system($command);

	}

}


sub config_database_vpn(){

# openvpn

	verbose_log("Database Profile: Configuring VPN");

	my $avkey="/etc/openvpn/av.key";

	if ( -f $avkey ) {
		verbose_log("Database Profile: Generating vpn key");
		system("openvpn --genkey --secret $avkey");
	}
	else {
		verbose_log("Database Profile: Vpn Key found.");
	}

# gen key: openssl  genrsa -out privada1.key 1024

}

sub config_database_add_host(){

	if ( "$add_hosts" eq "yes" ) {
## add database host in db

		if ( "$config{'admin_ip'}" ne "$config_last{'admin_ip'}" ) {

			verbose_log(
					"Database Profile: Updating admin ip (old=$config_last{'admin_ip'} new=$config{'admin_ip'}) update alienvault.host table"
				   );
			my $command
				= "echo \"UPDATE alienvault.host_ip SET ip = inet6_aton(\'$config{'admin_ip'}\') WHERE inet6_ntoa(ip) = \'$config_last{'admin_ip'}\'\" | ossim-db";
			debug_log($command);
			system($command);

		}else{

# -- host (database)
			verbose_log("Database Profile: Inserting into alienvault.host table");

			if ( "$config{'hostname'}" ne "$config_last{'hostname'}" ){
				my $command
					= "echo \"UPDATE alienvault.host SET hostname = \'$config{'hostname'}\' WHERE hostname = \'$config_last{'hostname'}\'\"| ossim-db $stdout $stderr ";
				debug_log($command);
				system($command);

			}else{

				my $nentry
					= `echo "SELECT COUNT(*) FROM alienvault.host WHERE hostname = \'$config{'hostname'}\';" | ossim-db | grep -v COUNT`; $nentry =~ s/\n//;
				debug_log("Database Profile: nentry: $nentry");

                if ( $nentry eq "0" && $profile_sensor == 0) {
					verbose_log("Database Profile: Inserting into host, host_ip");
					my $command
						= "echo \"SET \@uuid\:= UNHEX(REPLACE(UUID(),'-','')); INSERT IGNORE INTO alienvault.host (id,ctx,hostname,asset,threshold_c,threshold_a,alert,persistence,nat,rrd_profile,descr,lat,lon,av_component) VALUES (\@uuid,(SELECT UNHEX(REPLACE(value,'-','')) FROM alienvault.config WHERE conf = 'default_context_id'),\'$server_hostname\',\'2\',\'30\',\'30\',\'0\',\'0\',\'\',\'\',\'\',\'0\',\'0\',1); INSERT IGNORE INTO alienvault.host_ip (host_id,ip) VALUES (\@uuid,inet6_aton(\'$config{'admin_ip'}\'));\" | ossim-db $stdout $stderr ";
					debug_log($command);
					system($command);
				}else{
					debug_log("Database Profile: (already inserted)");
				}
			}

		}

	}
}

sub config_database_disable_munin(){

# Disable munin server when framework is not installed with database
	if ( ( $profile_framework != 1 ) && ( -f "/etc/cron.d/munin" ) ) {
		unlink("/etc/cron.d/munin");
	}

}



1;
