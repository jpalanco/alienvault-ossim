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

package Avconfig_profile_database;

use v5.10;
use strict;
use warnings;
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
#my $monit_file     = "/etc/monit/alienvault/avdatabase.monitrc";
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


    if ($profile_database == 1 && $profile_server == 0 && $profile_framework == 0 && $profile_sensor == 0)
    {
        my $command = "sed -i \"s:^db_ip.*:db_ip=127.0.0.1:\" $config_file";
        debug_log("$command");
        system($command);
    }

    config_database_parameters();
    config_database_grant();
	config_database_postcorrelation();
	#config_database_copying_databases(); # revisar, ya no la queremos
	config_database_rebuild();
	config_database_encryption_key();
	config_database_table_config();
	config_database_checking_privileges();
	config_database_framework_file(); # certain scripts needs fields from this conf, even on DB profile
	#config_database_add_default_networks(); # inside config_database_rebuild now (#8044)
#	config_database_monit();
	config_database_vpn();
	config_database_add_host();
	config_database_high_memory(); ## revisar esto para el percona
	config_database_pro_conf();
    config_database_ha();
	config_database_disable_munin(); ## revisar esto


    # Remember reset

    $reset{'monit'} = 1;

    #$reset{'openvpn'} = 1;
    $reset{'iptables'} = 1;

    return %reset;

}

##############################

sub config_database_parameters(){

	    # enable mysql listen

    verbose_log("Database Profile: Updating MySQL listen ip addreses");


    #my $command="sed -i \"s:bind-address.*:bind-address = $config{'database_ip'}:\" /etc/mysql/my.cnf";
    # we need local loopback too, for certain operations:
    my $command="sed -i \"s:bind-address.*:bind-address = 0.0.0.0:\" /etc/mysql/my.cnf";
    debug_log("$command");
    system($command);

    # mysql 5.1

    verbose_log("Database Profile: Disable skip-bdb");
    $command = "sed -i \"s:skip-bdb::\" /etc/mysql/my.cnf";
    debug_log("$command");
    system($command);

    #verbose_log("Database Profile: Max allow packet");
    #$command
    #    = "sed -i \"s:max_allowed_packet.*:max_allowed_packet=256M:\" /etc/mysql/my.cnf";
    #debug_log("$command");
    #system($command);

    #verbose_log("Database Profile: thread_stack");
    #$command
    #    = "sed -i \"s:thread_stack.*:thread_stack=384K:\" /etc/mysql/my.cnf";
    #debug_log("$command");
    #system($command);

    # 	innodb_lock_wait_timeout=500
    # 	skip_name_resolve
    # 	key_buffer              = 1500M
    #	max_allowed_packet      = 200M
    #	thread_stack            = 128K
    #	thread_cache_size       = 8
    #	max_connections        = 100
    #	table_cache             = 512
    #	thread_concurrency     = 10
    #
    # 	* Query Cache Configuration
    #
    #	query_cache_limit       = 5M
    #	query_cache_size        = 64M
    #	create bbdd's (ossim_reconf dk compatibility)

    my $alienvault_mysql_update = "/etc/mysql/conf.d/zalienvault_update.cnf";

    $reset{'mysql'} = 1 if ( `grep "max_connections" $alienvault_mysql_update` eq "" );

    verbose_log("Database Profile: Tunning mysql updates");
    system("echo \"[mysqld]\" > $alienvault_mysql_update");

    #system("#echo \"lc-messages-dir	= /usr/share/mysql\" >> $alienvault_mysql_update");
    # for percona
    system("echo \"thread_stack = 512K\" >> $alienvault_mysql_update");
    system("echo \"max_allowed_packet = 256M\" >> $alienvault_mysql_update");
    system("echo \"max_connections = 120\"  >> $alienvault_mysql_update");
    system("echo \"transaction-isolation = READ-COMMITTED\"  >> $alienvault_mysql_update");
    system("echo \"binlog-format = mixed\"  >> $alienvault_mysql_update");
    system("echo \"thread_cache_size = 100\" >> $alienvault_mysql_update");
    system("echo \"innodb_doublewrite_file = /var/lib/mysql/ib_doublewrite\"  >> $alienvault_mysql_update");
    system("echo \"innodb_file_per_table = 1\" >> $alienvault_mysql_update");

    if ($reset{'mysql'})
    {
    	verbose_log("Database Profile: Mysql restart");
    	system("/etc/init.d/mysql restart ");
    }
}

sub config_database_postcorrelation(){
    # post corr

    my $alienvault_mysql_scheduler = "/etc/mysql/conf.d/alienvault_scheduler.cnf";

    if ( !-f "$alienvault_mysql_scheduler" )
    {
        verbose_log("Enabling mysql scheduler");
        system("echo \"[mysqld]\" > $alienvault_mysql_scheduler");
        system(
            "echo \"event_scheduler = ON\" >> $alienvault_mysql_scheduler");
    }

}

### MAC: this is no longer used
#sub config_database_copying_databases(){
#    verbose_log("Database Profile: Copying Databases");
#}

sub config_database_rebuild{
    my $rebuild = shift // 'no';

    %config      = AV::ConfigParser::current_config;

    my $profile_server = 0;
    my $profile_framework = 0;
    my $profile_sensor = 0;
    my $profile_database = 0;
    my $db_pass = $config{'database_pass'};

    if ($config{'profile'} eq "all-in-one"){
        @profiles_arr = ("Server","Database","Framework","sensor");
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

    if (( $config{'rebuild_database'} eq "yes" ) || ( $rebuild eq 'yes' )) {

        verbose_log("Database profile: rebuild_database is enabled...");

        # increase thread_stack (for net_cidr and related procedures)
        verbose_log("Database Profile: thread_stack");
        my $command
            = "sed -i \"s:thread_stack.*:thread_stack=384K:\" /etc/mysql/my.cnf";
        debug_log("$command");
        system($command);

        $command = "test -x /etc/init.d/monit && /etc/init.d/monit stop";
        debug_log($command);
        system($command);

        # --
        # test to prevent race condition on vmware issues
        # sensor table is updated from ?(candidates below)
        if ( $profile_server == 1 ) {
            my $command = "/etc/init.d/ossim-server stop";
            debug_log($command);
            system($command);
        }
        if ( $profile_framework == 1 ) {
            my $command = "/etc/init.d/ossim-framework stop";
            debug_log($command);
            system($command);
        }
        if ( $profile_sensor == 1 ) {
            my $command = "/etc/init.d/ossim-agent stop";
            debug_log($command);
            system($command);
        }
        # kill server if still there
        $command = "pgrep ossim-server && pkill -9 ossim-server";
        debug_log($command);
        system($command);
        # --

        if ( $profile_sensor == 1 ) {
            my $command = "/etc/init.d/ossim-agent stop";
            debug_log($command);
            system($command);
        }
        # additional sensor stop:
        $command = "test -x /etc/init.d/ossim-agent && /etc/init.d/ossim-agent stop";
        debug_log($command);
        system($command);
# --

        # we are not using $config{'innodb'} today
        # mysql restart to clean mysql process (db will be rebuilt)
        verbose_log("Database Profile: Restarting MySQL ");
        $command = "/etc/init.d/mysql restart";
        debug_log($command);
        system($command);

        # bind = 0.0.0.0, in mysql conf, becomes next assign in obsolete/unnecessary; anyway it doesn't hurts;
        # it prevents conn. problems in 'Database only' profile and we need mysqld to be LISTEN in admin_ip/db_ip and in local loopback too
#        if ( $profile_database == 1 && $profile_server == 0 && $profile_framework == 0 && $profile_sensor == 0 ) {
#        	$rebuild_db_host="localhost";
#        }else{
#        	$rebuild_db_host="127.0.0.1";
#        }

        console_log("Database Profile: Creating new databases (please wait)...");
        verbose_log("Database Profile: Creating new databases (please wait)...");
		dp("Creating new databases...");

        # alienvault struct (old ossim)
        verbose_log("Database Profile: Create alienvault database");
		dp("Creating alienvault...");
#        system( "echo \"DROP DATABASE IF EXISTS alienvault; CREATE DATABASE alienvault DEFAULT CHARACTER SET utf8;\" | ossim-db mysql" );
#        system( "echo \"DROP DATABASE IF EXISTS alienvault; CREATE DATABASE alienvault DEFAULT CHARACTER SET utf8;\" | mysql -p$db_pass -h 127.0.0.1" );
		system("echo \"DROP DATABASE IF EXISTS alienvault; CREATE DATABASE alienvault DEFAULT CHARACTER SET utf8;\" | mysql -p$db_pass -h $rebuild_db_host");
        system("zcat /usr/share/doc/ossim-mysql/contrib/00-create_alienvault_tbls_mysql.sql.gz | ossim-db alienvault");

        ## ossim_acl (next is temporal)
        #debug_log("Database Profile: Create ossim acl");
        #system(
        #    "echo \"DROP DATABASE IF EXISTS ossim_acl; CREATE DATABASE ossim_acl;\" | mysql -p$db_pass"
        #);
        #system(
        #    "zcat /usr/share/doc/ossim-mysql/contrib/00-create_ossim_acl_tbls_mysql.sql.gz | ossim-db ossim_acl"
        #);

        # alienvault_siem struct (old snort)
        debug_log("Database Profile: Create alienvault_siem");
		dp("Creating alienvault_siem...");
        system(
#            "echo \"DROP DATABASE  IF EXISTS alienvault_siem; CREATE DATABASE alienvault_siem DEFAULT CHARACTER SET utf8;\" | mysql -p$db_pass -h 127.0.0.1"
            "echo \"DROP DATABASE  IF EXISTS alienvault_siem; CREATE DATABASE alienvault_siem DEFAULT CHARACTER SET utf8;\" | mysql -p$db_pass -h $rebuild_db_host"
        );
        system(
            "zcat /usr/share/doc/ossim-mysql/contrib/00-create_alienvault_siem_tbls_mysql.sql.gz | ossim-db alienvault_siem"
        );

        # alienvault_asec
        debug_log("Database Profile: Create alienvault_asec database");
		dp("Creating alienvault_asec...");
		system( "echo \"DROP DATABASE IF EXISTS alienvault_asec; CREATE DATABASE alienvault_asec DEFAULT CHARACTER SET utf8;\" | mysql -p$db_pass -h 127.0.0.1" );

		if ( -f "/usr/share/doc/ossim-mysql/contrib/00-create_alienvault_asec_tbls_mysql.sql.gz" ){
	        system("zcat /usr/share/doc/ossim-mysql/contrib/00-create_alienvault_asec_tbls_mysql.sql.gz | ossim-db alienvault_asec");
		# compressed (dh_compress) if > 4K, else:
		}else{
			if ( -f "/usr/share/doc/ossim-mysql/contrib/00-create_alienvault_asec_tbls_mysql.sql" ){
	            system("cat /usr/share/doc/ossim-mysql/contrib/00-create_alienvault_asec_tbls_mysql.sql | ossim-db alienvault_asec");
			}
		}


        # alienvault_api
        debug_log("Database Profile: Create alienvault_api database");
        dp("Creating alienvault_api...");
        system( "echo \"DROP DATABASE IF EXISTS alienvault_api; CREATE DATABASE alienvault_api DEFAULT CHARACTER SET utf8;\" | mysql -p$db_pass -h 127.0.0.1" );

        if ( -f "/usr/share/doc/ossim-mysql/contrib/00-create_alienvault_api_tbls_mysql.sql.gz" ){
            system("zcat /usr/share/doc/ossim-mysql/contrib/00-create_alienvault_api_tbls_mysql.sql.gz | ossim-db alienvault_api");
        # compressed (dh_compress) if > 4K, else:
        }else{
            if ( -f "/usr/share/doc/ossim-mysql/contrib/00-create_alienvault_api_tbls_mysql.sql" ){
                system("cat /usr/share/doc/ossim-mysql/contrib/00-create_alienvault_api_tbls_mysql.sql | ossim-db alienvault_api");
            }
        }

        if ( -f "/usr/share/doc/ossim-mysql/contrib/01-create_alienvault_api_data.sql.gz" ){
              system("zcat /usr/share/doc/ossim-mysql/contrib/01-create_alienvault_api_data.sql.gz | ossim-db alienvault_api");
          # compressed (dh_compress) if > 4K, else:
        }else{
              if ( -f "/usr/share/doc/ossim-mysql/contrib/01-create_alienvault_api_data.sql" ){
              system("cat /usr/share/doc/ossim-mysql/contrib/01-create_alienvault_api_data.sql | ossim-db alienvault_api");
              }
        }


#        debug_log("Database Profile: Create jasperserver");
#        system("echo \"DROP DATABASE  IF EXISTS jasperserver; CREATE DATABASE jasperserver;\" | mysql -p$db_pass");
#        system("zcat /usr/share/doc/ossim-mysql/contrib/00-create_jasperserver_tbls_mysql.sql.gz | ossim-db jasperserver");

        debug_log("Database Profile: Create datewarehouse");
		dp("Creating datawarehouse...");
        system(
#            "echo \"DROP DATABASE  IF EXISTS datawarehouse; CREATE DATABASE datawarehouse;\" | mysql -p$db_pass -h 127.0.0.1"
            "echo \"DROP DATABASE  IF EXISTS datawarehouse; CREATE DATABASE datawarehouse;\" | mysql -p$db_pass -h $rebuild_db_host"
        );
        system(
            "zcat /usr/share/doc/ossim-mysql/contrib/00-create_datawarehouse_tbls_mysql.sql.gz | ossim-db datawarehouse"
        );

        # alienvault data config
        debug_log("Database Profile: alienvault config data");
		dp("Loading alienvault config data (please wait)...");
        system(
            "zcat /usr/share/doc/ossim-mysql/contrib/01-create_alienvault_data_config.sql.gz | ossim-db alienvault"
        );

        # alienvault data data
        debug_log("Database Profile: alienvault data data");
		dp("Loading alienvault data data (please wait)...");
        system(
            "zcat /usr/share/doc/ossim-mysql/contrib/02-create_alienvault_data_data.sql.gz | ossim-db alienvault"
        );

        # alienvault_siem data
        debug_log("Database Profile: alienvault siem data");
		dp("Loading alienvault_siem data (please wait)...");
        system(
            "zcat /usr/share/doc/ossim-mysql/contrib/01-create_alienvault_siem_data.sql.gz | ossim-db alienvault_siem"
        );

        # alienvault_asec data
        debug_log("Database Profile: alienvault asec data");
		dp("Loading alienvault asec data (please wait)...");
		if ( -f "/usr/share/doc/ossim-mysql/contrib/01-create_alienvault_asec_data.sql.gz" ){
        	system("zcat /usr/share/doc/ossim-mysql/contrib/01-create_alienvault_asec_data.sql.gz | ossim-db alienvault_asec");

		# compressed (dh_compress) if > 4K, else:
		}else{
			if ( -f "/usr/share/doc/ossim-mysql/contrib/01-create_alienvault_asec_data.sql" ){
        		system("cat /usr/share/doc/ossim-mysql/contrib/01-create_alienvault_asec_data.sql | ossim-db alienvault_asec");
			}
		}

        # alienvault-crosscorrelation-free
        debug_log("Database Profile: alienvault croscorrelation data");
		dp("Loading alienvault crosscorrelation data (please wait)...");
        system(
            "zcat /usr/share/doc/ossim-mysql/contrib/03-create_alienvault_data_croscor_snort_nessus.sql.gz | ossim-db alienvault"
        );



	if (-f "/usr/share/doc/ossim-mysql/contrib/00-create_osvdb_tbls_mysql.sql.gz" )
	{
		debug_log("Database Profile: osvdb");
		dp("Creating osvbd...");
		system(
				"echo \"DROP DATABASE  IF EXISTS osvdb; CREATE DATABASE osvdb;\" | mysql -p$db_pass -h $rebuild_db_host"
		      );



		system(
				"zcat /usr/share/doc/ossim-mysql/contrib/00-create_osvdb_tbls_mysql.sql.gz | ossim-db osvdb"
		      );
	}
	else {

		if ( -f "/usr/share/doc/ossim-mysql/contrib/OSVDB-tables.sql.gz" )
		{
			system(
					"zcat /usr/share/doc/ossim-mysql/contrib/OSVDB-tables.sql.gz | ossim-db osvdb"
			      );
		}


	}

	debug_log("Database Profile: alienvault vulnerabilities data");
	dp("Loading alienvault vulnerabilities data (please wait)...");
	if (-f "/usr/share/doc/ossim-mysql/contrib/04-create_alienvault_data_vulnerabilities.sql.gz"
	   )
	{
		system(
				"zcat /usr/share/doc/ossim-mysql/contrib/04-create_alienvault_data_vulnerabilities.sql.gz | ossim-db"
		      );
	}

	if (-f "/usr/share/ossim-installer/ocsweb.snapshot.sql.gz"
	   )
	{
		system(
				"zcat /usr/share/ossim-installer/ocsweb.snapshot.sql.gz | ossim-db"
		      );
	}

	my $tmz = `cat /etc/timezone`;
	$tmz =~ s/\n//g;
	$tmz =~ s/ //g;
	system(
			"echo \"update alienvault.users set timezone='$tmz' where login='admin'\" | ossim-db"
	      );

	debug_log("Database Profile: Update plugins");
	dp("Loading plugins data (please wait)...");

	system(
			"find /usr/share/doc/ossim-mysql/contrib/plugins/ -type f -iname \\*.sql -printf 'INSERT %f \n' -exec sh -c 'cat {}| ossim-db' \\;"
	      );
	system(
			"find /usr/share/doc/ossim-mysql/contrib/plugins/ -type f -iname \\*.sql.gz -printf 'INSERT %f \n' -exec sh -c 'zcat {}| ossim-db' \\;"
	      );


#        if ( -f "/usr/share/ossim-installer/databases/plugin_reference.sql" )
#        {
#            debug_log("Inserting plugins reference");
#            system(
#                "cat /usr/share/ossim-installer/databases/plugin_reference.sql | ossim-db"
#            );
#        }


# ossim-mysql-ext aditional data
	if ( -s "/var/lib/dpkg/info/ossim-mysql-ext.postinst" ) {
		verbose_log("Reconfiguring ossim-mysql-ext");
		system("/bin/bash /var/lib/dpkg/info/ossim-mysql-ext.postinst configure");
	}

# snort rules aditional data
	if ( -s "/var/lib/dpkg/info/snort-rules-default.postinst" ) {
		verbose_log("Updating snort data");
		system("/bin/bash /var/lib/dpkg/info/snort-rules-default.postinst configure");
	}

# suricata rules aditional data
	if ( -s "/var/lib/dpkg/info/suricata-rules-default.postinst" ) {
		verbose_log("Updating suricata data");
		system("/bin/bash /var/lib/dpkg/info/suricata-rules-default.postinst configure");
	}


# compliance aditional data
    if ( -s "/var/lib/dpkg/info/ossim-compliance.postinst" ) {
	    verbose_log("Reconfiguring ossim-compliance");
    	system("dpkg-reconfigure ossim-compliance");
    }

# wizard aditional data
	if ( -f "/var/lib/dpkg/info/alienvault-wizard.postinst" ) {
		verbose_log("Reconfiguring alienvault-wizard");
		system("dpkg-reconfigure alienvault-wizard");
	}

# policies aditional data (obsolete)
# verbose_log("Reconfiguring alienvault-policies");
# system("dpkg-reconfigure alienvault-policies $stdout $stderr");

# KDB, taxonomy after plugin_sid
	debug_log("Database Profile: KDB, Taxonomy");
	system(
			"zcat /usr/share/doc/ossim-mysql/contrib/06-create_alienvault_data_kb_taxonomy.sql.gz | ossim-db alienvault"
	      );

# update fix if present
	if ( -d "/usr/share/doc/alienvault-directives-pro/contrib/" ){
		verbose_log("Update feed pro info");
		system("zcat /usr/share/doc/alienvault-directives-pro/contrib/datawarehouse_category.sql.gz | ossim-db datawarehouse");
#system("zcat /usr/share/doc/alienvault-directives-pro/contrib/PCI.sql.gz | ossim-db PCI");
#system("zcat /usr/share/doc/alienvault-directives-pro/contrib/ISO27001An.sql.gz | ossim-db ISO27001An");
		system("zcat /usr/share/doc/alienvault-directives-pro/contrib/alienvault-kb.sql.gz | ossim-db ");
	}else{

		if ( -d "/usr/share/doc/alienvault-directives-free/contrib/" ){
			verbose_log("Update feed free info");
			system("zcat /usr/share/doc/alienvault-directives-free/contrib/datawarehouse_category.sql.gz | ossim-db datawarehouse");
#system("zcat /usr/share/doc/alienvault-directives-free/contrib/PCI.sql.gz | ossim-db PCI");
#system("zcat /usr/share/doc/alienvault-directives-free/contrib/ISO27001An.sql.gz | ossim-db ISO27001An");
			system("zcat /usr/share/doc/alienvault-directives-free/contrib/alienvault-kb.sql.gz | ossim-db ");
		}
	}



	  if ( ($profile_database == 1 )   &&
		($profile_server == 1 )   &&
            	($profile_framework == 1 ) &&
            	($profile_sensor    == 1 ) ) {

		system("echo \"REPLACE INTO config VALUES('start_welcome_wizard',1);\" | ossim-db");


	}

# being tested into installer instead of here:
#        # ossim-cd-tools
#        if ( -f "/var/lib/dpkg/info/ossim-cd-tools.postinst" ) {
#            verbose_log("Reconfiguring ossim-cd-tools");
#            system("dpkg-reconfigure ossim-cd-tools");
#        }



#        if ( -s "/var/lib/dpkg/info/ossim-taxonomy.postinst" ) {
#            verbose_log("Updating taxonomy data");
#            system("/bin/bash /var/lib/dpkg/info/ossim-taxonomy.postinst configure");
#        }

#        # directives aditional data (KnowledgeDB, etc)
#        if ( -s "/var/lib/dpkg/info/alienvault-directives-free.postinst" ) {
#            verbose_log("Updating directives data");
#            system("/bin/bash /var/lib/dpkg/info/alienvault-directives-free.postinst configure");
#        }
#        if ( -s "/var/lib/dpkg/info/alienvault-directives-pro.postinst" ) {
#            verbose_log("Updating directives (pro) data");
#            system("/bin/bash /var/lib/dpkg/info/alienvault-directives-pro.postinst configure");
#        }

#        # crosscorrelation aditional data
#        if ( -s "/var/lib/dpkg/info/alienvault-crosscorrelation-free.postinst" ) {
#            verbose_log("Updating crosscorrelation data");
#            system("/bin/bash /var/lib/dpkg/info/alienvault-crosscorrelation-free.postinst configure");
#        }
#        if ( -s "/var/lib/dpkg/info/alienvault-crosscorrelation-pro.postinst" ) {
#            verbose_log("Updating crosscorrelation (pro) data");
#            system("/bin/bash /var/lib/dpkg/info/alienvault-crosscorrelation-pro.postinst configure");
#        }


	verbose_log("Database Profile: Set lock create database");
	$command = "sed -i \"s:rebuild_database=yes:rebuild_database=no:\" $config_file";
	debug_log("$command");
	system($command);

	if ( $profile_framework == 1 ) {
		verbose_log("Database Profile (and Framework Profile): remove php sessions from /var/lib/php5 ");
		$command = "[ -d /var/lib/php5 ] && find /var/lib/php5/ -type f -delete";
		debug_log("$command");
		system($command);
        $command = "dpkg-trigger --no-await alienvault-config-system-admin-ip";
		debug_log("$command");
		system($command);
	}

	config_database_add_default_networks();

    }

}

sub config_database_encryption_key(){

### default

#verbose_log("Database Profile: Connecting to the Database");

	my $key_str   = "";
	my $v_key_str = 0;

## Read key ----
# from file: --
#       if ( -f "/etc/ossim/framework/db_encryption_key" ) {
#               $key_str = `cat /etc/ossim/framework/db_encryption_key| grep "^key=" |awk -F'=' '{print \$2}'`;
#               $key_str=~ s/\n//g;
#       }
#--
# from db: --
# WARN ! key for decrypt !
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

#$conn = Avtools::get_database();
#my $query = "REPLACE INTO config VALUES(\"nessus_pass\",AES_ENCRYPT(\'$db_pass\',\'$key_str\'))";
#my $sth   = $conn->prepare($query);
#debug_log("$query");
#$sth->execute();

			my $query
				= "REPLACE INTO config VALUES(\"nessus_pass\",AES_ENCRYPT('$db_pass','$key_str'))";
			Avtools::execute_query_without_return("$query");

		}
		else {

#$conn = Avtools::get_database();
#my $query = "REPLACE INTO config VALUES(\"nessus_pass\",\"$db_pass\")";
#my $sth   = $conn->prepare($query);
#debug_log("$query");
#$sth->execute();

			my $query
				= "REPLACE INTO config VALUES(\"nessus_pass\",\"$db_pass\")";
			Avtools::execute_query_without_return("$query");

		}
	}

#	verbose_log("Database Profile: update dashboard");
#	my $query = "REPLACE INTO `alienvault`.`user_config` (`login` ,`category` ,`name` ,`value`)VALUES ('admin', 'main', 'panel_tabs', 'a:7:{i:1;a:2:{s:8:\"tab_name\";s:9:\"Executive\";s:12:\"tab_icon_url\";s:0:\"\";}i:5;a:2:{s:8:\"tab_name\";s:7:\"Network\";s:12:\"tab_icon_url\";s:0:\"\";}i:6;a:2:{s:8:\"tab_name\";s:7:\"Tickets\";s:12:\"tab_icon_url\";s:0:\"\";}i:7;a:2:{s:8:\"tab_name\";s:8:\"Security\";s:12:\"tab_icon_url\";s:0:\"\";}i:8;a:2:{s:8:\"tab_name\";s:15:\"Vulnerabilities\";s:12:\"tab_icon_url\";s:0:\"\";}i:9;a:2:{s:8:\"tab_name\";s:9:\"Inventory\";s:12:\"tab_icon_url\";s:0:\"\";}i:10;a:2:{s:8:\"tab_name\";s:10:\"Compliance\";s:12:\"tab_icon_url\";s:0:\"\";}}');";
#        		my $sth   = $conn->prepare($query);
#    		$sth->execute();


}
sub config_database_checking_privileges(){



	verbose_log("Database Profile: Checking root privileges");
	my $wentry
		= `echo "SELECT count(*) FROM mysql.user WHERE User='root' AND Host='127.0.0.1' AND Password='';" | ossim-db | grep -v count`;
	$wentry =~ s/\n//;
	if ( $wentry ne "0" ) {
		debug_log(
				"UPDATE mysql.user SET Password = PASSWORD('$db_pass') WHERE User='root' AND Host='127.0.0.1' AND Password=''; FLUSH PRIVILEGES;"
			 );
		`echo "UPDATE mysql.user SET Password = PASSWORD('$db_pass') WHERE User='root' AND Host='127.0.0.1' AND Password=''; FLUSH PRIVILEGES;" | ossim-db`;
	}

# 764 --
	verbose_log("Database Profile: Checking osvdb privileges");
	$wentry
		= `echo "SELECT count(*) FROM mysql.user WHERE User='osvdb' AND Host='%' AND Password='';" | ossim-db | grep -v count`;
	$wentry =~ s/\n//;
	my $nentry
		= `echo "SELECT count(*) FROM mysql.user WHERE User='osvdb';" | ossim-db | grep -v count`;
	$nentry =~ s/\n//;
	if ( ( $wentry ne "0" ) || ( $nentry eq "0" ) ) {

#verbose_log("Database Profile: Delete wrong entries (if any), and set privileges on osvdb for $framework_host and $server_ip");
		my @query_array = (
				"DELETE FROM mysql.user WHERE User='osvdb' AND Host='%' AND Password='';",

#		"GRANT ALL ON osvdb.* to osvdb@\"$framework_host\" IDENTIFIED BY \"$db_pass\";",
#		"GRANT ALL ON osvdb.* to osvdb@\"$server_ip\" IDENTIFIED BY \"$db_pass\";"
				);
		foreach (@query_array) {
			Avtools::execute_query_without_return("$_");
		}
		`echo "GRANT ALL ON osvdb.* to \'osvdb\'@\'$framework_host\' IDENTIFIED BY \'$db_pass\';"| mysql -h localhost -u root -p$db_pass mysql`;
		`echo "GRANT ALL ON osvdb.* to \'osvdb\'@\'$server_ip\' IDENTIFIED BY \'$db_pass\';"| mysql -h localhost -u root -p$db_pass mysql`;
	}

# --

# Update jasperserver JIUser and JIJdbcDatasource
#
#

#    verbose_log(
#        "Database Profile: Updating jasperserver JIUser and JIJdbcDatasource "
#    );
#
#    my @query_array = (
#        "UPDATE jasperserver.JIJdbcDatasource SET connectionUrl = 'jdbc:mysql://$db_host/snort' WHERE username = 'root' AND connectionUrl LIKE 'jdbc:mysql://%/snort';",
#        "UPDATE jasperserver.JIJdbcDatasource SET connectionUrl = 'jdbc:mysql://$db_host/ossim' WHERE username = 'root' AND connectionUrl LIKE 'jdbc:mysql://%/ossim';",
#        "UPDATE jasperserver.JIJdbcDatasource SET connectionUrl = 'jdbc:mysql://$db_host/datawarehouse' WHERE username = 'root' AND connectionUrl LIKE 'jdbc:mysql://%/datawarehouse';"
#    );
#    foreach (@query_array) {
#        Avtools::execute_query_without_return("$_");
#    }

#	if ( $v_key_str == 1 ) {
#		my @query_array = (
#		"UPDATE jasperserver.JIUser SET password = AES_ENCRYPT('$db_pass','$key_str') WHERE username = 'jasperadmin';",
#		"UPDATE jasperserver.JIUser SET password = AES_ENCRYPT('$db_pass','$key_str') WHERE username = 'anonymousUser';",
#		"UPDATE jasperserver.JIJdbcDatasource SET password = AES_ENCRYPT('$db_pass','$key_str') WHERE username = 'root' AND connectionUrl = 'jdbc:mysql://$db_host/datawarehouse';",
#		"UPDATE jasperserver.JIJdbcDatasource SET password = AES_ENCRYPT('$db_pass','$key_str') WHERE username = 'root' AND connectionUrl = 'jdbc:mysql://$db_host/ossim';",
#		"UPDATE jasperserver.JIJdbcDatasource SET password = AES_ENCRYPT('$db_pass','$key_str') WHERE username = 'root' AND connectionUrl = 'jdbc:mysql://$db_host/snort';"
#		);
#		foreach(@query_array){
#			Avtools::execute_query_without_return("$_");
#		}
#	}else{

#    my @query_array = (
#        "UPDATE jasperserver.JIUser SET password = '$db_pass' WHERE username = 'jasperadmin';",
#        "UPDATE jasperserver.JIUser SET password = '$db_pass' WHERE username = 'anonymousUser';",
#        "UPDATE jasperserver.JIJdbcDatasource SET password = '$db_pass' WHERE username = 'root' AND connectionUrl = 'jdbc:mysql://$db_host/datawarehouse';",
#        "UPDATE jasperserver.JIJdbcDatasource SET password = '$db_pass' WHERE username = 'root' AND connectionUrl = 'jdbc:mysql://$db_host/ossim';",
#        "UPDATE jasperserver.JIJdbcDatasource SET password = '$db_pass' WHERE username = 'root' AND connectionUrl = 'jdbc:mysql://$db_host/snort';"
#    );
#    foreach (@query_array) {
#        Avtools::execute_query_without_return("$_");
#    }

#	}

}
sub config_database_framework_file(){
#    if ( "$config{'first_init'}" eq "yes" ) {
#	debug_log("FIRST INIT: database profile");

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

#            $command
#                = "sed -i \"s:phpgacl_host=.*:phpgacl_host=$db_host:\" $framework_file";
#            debug_log("$command");
#            system($command);

#            $command
#                = "sed -i \"s:phpgacl_pass=.*:phpgacl_pass=$db_pass:\" $framework_file";
#            debug_log("$command");
#            system($command);
	}

#system("/usr/share/ossim/scripts/create_sidmap.pl /etc/snort/rules/");
#system("/usr/share/ossim/scripts/create_sidmap_preprocessors.pl /etc/snort/gen-msg.map");
#    }

}
sub config_database_add_default_networks(){


## add default network
	verbose_log("Database Profile: Add default networks");

	my $netnum = `echo "SELECT COUNT(*) FROM net;" |ossim-db | grep -v COUNT`; $netnum =~ s/\n//;

	if ( $netnum eq "0" ) {

		my @query_array = (
				q{SELECT UNHEX(REPLACE(UUID(),'-','')) into @uuid},
				q{INSERT IGNORE INTO net (id,ctx,name,ips,asset,threshold_c,threshold_a,alert,persistence,rrd_profile,descr) VALUES (@uuid,(SELECT UNHEX(REPLACE(value,'-','')) FROM alienvault.config WHERE conf = 'default_context_id'),'Pvt_192','192.168.0.0/16','2','300','300','0','0','NULL','')},
				q{INSERT IGNORE INTO net_qualification (net_id,compromise,attack) VALUES (@uuid,2,2)},
				q{SELECT UNHEX(REPLACE(UUID(),'-','')) into @uuid},
				q{INSERT IGNORE INTO net (id,ctx,name,ips,asset,threshold_c,threshold_a,alert,persistence,rrd_profile,descr) VALUES (@uuid,(SELECT UNHEX(REPLACE(value,'-','')) FROM alienvault.config WHERE conf = 'default_context_id'),'Pvt_172','172.16.0.0/12','2','300','300','0','0','NULL','')},
				q{INSERT IGNORE INTO net_qualification (net_id,compromise,attack) VALUES (@uuid,2,2)},
				q{SELECT UNHEX(REPLACE(UUID(),'-','')) into @uuid},
				q{INSERT IGNORE INTO net (id,ctx,name,ips,asset,threshold_c,threshold_a,alert,persistence,rrd_profile,descr) VALUES (@uuid,(SELECT UNHEX(REPLACE(value,'-','')) FROM alienvault.config WHERE conf = 'default_context_id'),'Pvt_010','10.0.0.0/8','2','300','300','0','0','NULL','')},
				q{INSERT IGNORE INTO net_qualification (net_id,compromise,attack) VALUES (@uuid,2,2)},
				);

		Avtools::execute_query_without_return(@query_array);

        ## net_cidrs
		if ( -f "/usr/share/doc/ossim-mysql/contrib/misc/net_cidrs.sql" ) {
			verbose_log("Database Profile: Inserting into net_cidrs (running net_cidrs.sql)");
			system("cat /usr/share/doc/ossim-mysql/contrib/misc/net_cidrs.sql | ossim-db");
		}else{
			verbose_log("Database Profile: /usr/share/doc/ossim-mysql/contrib/misc/net_cidrs.sql not found");
		}

	}else{
	    verbose_log("Database Profile: Already inserted"); }
}


#sub config_database_monit(){
#
# monit
#
# Custom monit files, and split monit files by service:
#	if ( !-d "/etc/monit/conf.d/" || !-d "/etc/monit/alienvault/" ) {
#		system("mkdir -p /etc/monit/conf.d/ >/dev/null 2>&1 &");
#		system("mkdir -p /etc/monit/alienvault/ >/dev/null 2>&1 &");
#	}
#
#	verbose_log("Database Profile: Updating Monit Configuration");
#	open MONITFILE, "> $monit_file" or die "Error opening file $!";
#	print MONITFILE <<EOF;
#
##Database
#	check process mysqld with pidfile /var/run/mysqld/mysqld.pid
#		group mysql
#		start program = \"/etc/init.d/mysql start\"
#		stop program = \"/etc/init.d/mysql stop\"
### reenable on wheezy -> if failed host $db_host port 3306 protocol mysql for 3 cycles then restart
#		if failed host $db_host port 3306 for 3 cycles then restart
#			if failed unixsocket /var/run/mysqld/mysqld.sock for 2 cycles then exec "/usr/bin/killall mysqld"
#				if totalmem > 90% then restart
#					if 20 restarts within 20 cycles then alert
#						depends on mysql_bin
#							depends on mysql_rc
#
#							check file mysql_bin with path /usr/sbin/mysqld
#							group mysql
#							if failed checksum then alert
#								if failed permission 755 then unmonitor
#									if failed uid root then unmonitor
#										if failed gid root then unmonitor
#
#											check file mysql_rc with path /etc/init.d/mysql
#												group mysql
#												if failed checksum then alert
#													if failed permission 755 then unmonitor
#														if failed uid root then unmonitor
#															if failed gid root then unmonitor
#
#EOF
#
#																	close(MONITFILE);
#}

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
				= "echo \"UPDATE alienvault.host_ip SET ip = inet6_pton(\'$config{'admin_ip'}\') WHERE inet6_ntop(ip) = \'$config_last{'admin_ip'}\'\" | ossim-db";
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

				if ( $nentry eq "0" ) {
					verbose_log("Database Profile: Inserting into host, host_ip");
					my $command
						= "echo \"SET \@uuid\:= UNHEX(REPLACE(UUID(),'-','')); INSERT IGNORE INTO alienvault.host (id,ctx,hostname,asset,threshold_c,threshold_a,alert,persistence,nat,rrd_profile,descr,lat,lon,av_component) VALUES (\@uuid,(SELECT UNHEX(REPLACE(value,'-','')) FROM alienvault.config WHERE conf = 'default_context_id'),\'$server_hostname\',\'2\',\'30\',\'30\',\'0\',\'0\',\'\',\'\',\'\',\'0\',\'0\',1); INSERT IGNORE INTO alienvault.host_ip (host_id,ip) VALUES (\@uuid,inet6_pton(\'$config{'admin_ip'}\'));\" | ossim-db $stdout $stderr ";
					debug_log($command);
					system($command);
				}else{
					debug_log("Database Profile: (already inserted)");
				}
			}

		}

#if ( $v_key_str == 1 ) {
#        my $command
#            = "echo \"insert into alienvault.databases (name,ip,port,user,pass,icon) value (\'$server_hostname\',\'$config{'admin_ip'}\',\'3306\',\'root\',AES_ENCRYPT(\'$db_pass\',\'$key_str\'),\'NULL\')\" | ossim-db  $stdout $stderr ";
#        debug_log($command);
#        system($command);
#}else{
#        my $command
#            = "echo \"insert into alienvault.databases (name,ip,port,user,pass,icon) value ('snort',\'$config{'admin_ip'}\',\'3306\',\'root\',\'$db_pass\',\'NULL\')\" | ossim-db  $stdout $stderr ";
#        debug_log($command);
#        system($command);
#}

	}
}


sub config_database_high_memory(){

	open( MEM, "cat /proc/meminfo|" );

	while (<MEM>) {

		if (/MemTotal:\s+(\d+)\s+kB/) {

# only copy good mysql config file if the host has got more than 5 gigabyte of ram
			if ( $1 > 5000000 ) {
				verbose_log("Database Profile: Enabling highmem my.cnf");
				system(
						"cp /usr/share/ossim-cd-configs/backup/etc/mysql/conf.d/my.cnf /etc/mysql/conf.d/"
				      );

			}
			else {
				verbose_log("Database Profile: Disabling highmem my.cnf");
				system("rm -f /etc/mysql/conf.d/my.cnf");
			}
		}
	}

	close MEM;

}
sub config_database_pro_conf(){

	my $server_dbvalue  = `echo "select value from alienvault.config where conf='ossim_server_version';"| ossim-db |grep -i "pro"`;
	if ( $server_dbvalue ne "" ){

		open( MEM, "cat /proc/meminfo|" );

		while (<MEM>) {

			if (/MemTotal:\s+(\d+)\s+kB/) {

				if ( $1 > 2000000 ) {
					verbose_log("Database Profile: Enabling pro highmem my.cnf");

                                        # 1/2 available RAM if it's only Database profile, otherwise 1/4
                                        my $ram = $1;
					my $ft = ($profile_server || $profile_framework || $profile_sensor) ? 4 : 2;
					$ft = 2 if ($ram>16000000); # With enough memory force 1/2
					chomp ( my $innodb_buffer_pool_size = `echo "($ram/$ft)/1024" | bc` );

					my $pro_config_percona="/etc/mysql/conf.d/zzalienvault-percona.cnf";

					if ( ! -f "$pro_config_percona"){
						verbose_log("Database Profile: Increase innodb_log_file_size to 512M");
						verbose_log("Database Profile: Warning Stop mysql");
						system("/etc/init.d/mysql stop");
						sleep 3;
						verbose_log("Database Profile: Remove ibdata files");
						system("rm -rf /var/lib/mysql/ib_logfile*");
						open MNF, "> $pro_config_percona";
						print MNF "[mysqld]\n";
						print MNF "innodb_buffer_pool_size=${innodb_buffer_pool_size}M\n";  # un 50% si es un AIO, un 70/80% en perfil DB (http://www.mysqlperformanceblog.com/2007/11/03/choosing-innodb_buffer_pool_size/ and http://www.mysqlperformanceblog.com/2007/11/01/innodb-performance-optimization-basics/)
                                                print MNF "innodb_additional_mem_pool_size=128M\n";
						print MNF "innodb_flush_method=O_DIRECT\n";
						print MNF "innodb_log_buffer_size=16M\n";
						print MNF "innodb_thread_concurrency=8\n";
						print MNF "innodb_file_per_table=1\n";
						print MNF "innodb_flush_log_at_trx_commit=2\n";
						print MNF "table_cache=1024\n";
						print MNF "query_cache_size=256M\n";
						print MNF "query_cache_type = 1\n";
						print MNF "innodb_commit_concurrency=0\n";
						print MNF "innodb_log_file_size=512M\n";
						close(MNF);
#$reset{'mysql'} = 1;
#$reset{'conditional_reset_mysql'} = 1;
						verbose_log("Database Profile: Start mysql, please be patient");
						system("/etc/init.d/mysql start");
						sleep 5;

					    my $dbhost = `grep db_ip /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`; chomp($dbhost);
					    my $dbuser = `grep ^user= /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`; chomp($dbuser);
					    my $dbpass = `grep ^pass= /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`; chomp($dbpass);

						my $rtry=0;
						while (1) {
							if ( $rtry < 8 ) {
								console_log("Database Profile: mysql daemon is starting (still not accepting connections, please be patient)");

								sleep 15;
								my @qtst = `echo "show databases"| ossim-db 2>> /tmp/err`;

								if (map(/alienvault_siem*/,@qtst)) {
									last;
								}else{
									$rtry++;
									debug_log("($rtry try)");
									system("mysqlcheck -h $dbhost -p$dbpass -u $dbuser --auto-repair mysql 2>> /tmp/err");
								}
							}else{
								debug_log("limmit reached ($rtry try)");
								last;
							}
						}


					}
                                        else
                                        {
                                            # Change 
                                            verbose_log("Database Profile: Set innodb_buffer_pool_siz to ${innodb_buffer_pool_size}M");
                                            system("sed -i \"s:innodb_buffer_pool_size.*:innodb_buffer_pool_size=${innodb_buffer_pool_size}M:\" $pro_config_percona");
                                        }

				}
			}
		}

		close MEM;

	}


}


sub config_database_ha(){

# ha
	my $config_cnf_ha = "/etc/mysql/conf.d/z99_ha.cnf";
	if ( ( $config{'ha_heartbeat_start'} // q{} ) eq "yes" ) {

	my $server_mysql_id;
	if ( $config{'ha_role'} eq "master" ) { $server_mysql_id = 1; }
	if ( $config{'ha_role'} eq "slave" )  { $server_mysql_id = 2; }

# until innodb_log_file_size becomes an autom.computed value, don't overwrite (!-f) conf file
	if ( !-f "$config_cnf_ha" ) {
		open HACNF, "> $config_cnf_ha";
		print HACNF "[mysqld]\n";
		print HACNF "server-id = $server_mysql_id\n";
		print HACNF "log_bin = /var/log/mysql/mysql-bin.log\n";
		print HACNF "auto_increment_increment = 2\n";
		print HACNF "auto_increment_offset = $server_mysql_id\n";
		print HACNF "bind-address = 0.0.0.0\n";
		print HACNF "#master-host = $config{'ha_other_node_ip'}\n";
		print HACNF "#master-user = replication\n";
		print HACNF "#master-password = $config{'ha_password'}\n";
		print HACNF
			"# innodb_log_file_size must be computed at server peak usage time\n";
		print HACNF "#innodb_log_file_size = 64M\n";
		print HACNF "log-error = /var/log/mysql/error.log\n";
		print HACNF "slave-skip-errors=1062\n";
		print HACNF "log_bin_trust_function_creators = 1\n";
		close(HACNF);
		$reset{'mysql'} = 1;
	}
	else {
		debug_log("$config_cnf_ha already created");
	}
}
else {

# cluster is off. rm mysql config file which contains mysql replication parms
	if ( -f "$config_cnf_ha" ) {
		system("rm -f $config_cnf_ha");
		$reset{'mysql'} = 1;
	}
}

}

sub config_database_disable_munin(){

# Disable munin server when framework is not installed with database
	if ( ( $profile_framework != 1 ) && ( -f "/etc/cron.d/munin" ) ) {
		unlink("/etc/cron.d/munin");
	}

}

sub config_database_grant(){
	my $command = "";

    # need to condition certain operations 'config vs last', when ha is enabled
    # (could check for virtual_ip vs admin_ip, but it is a good idea to check for the value for the ha switch property)
    # even, in this case, an additional condition can be added to the SQL sentence (User 'replication')
    if ( ( $config{'ha_heartbeat_start'} // q{} ) ne "yes" ) {
    # admin_ip siempre es != 127.0.0.1
    	if ( "$config{'admin_ip'}" ne "$config_last{'admin_ip'}" ) {
    		verbose_log("Database Profile: admin_ip change detected (old=$config_last{'admin_ip'} new=$config{'admin_ip'})");
            $command = "echo \"UPDATE mysql.user SET Host = \'$config{'admin_ip'}\' WHERE Host = \'$config_last{'admin_ip'}\' AND User != \'replication\';FLUSH PRIVILEGES;\" | ossim-db";
    		debug_log($command);
    		system($command);
    	}
    }else{
        debug_log("Database Profile: cluster is enabled, keeping both previous GRANT entries for user 'replication'");
    # please note that for (framework, server privileges) entries below, it has to take effect, even with cluster enabled
    }


    # para framework_ip, ahora no lo es pero podria serlo en el futuro.
	if ( "$config{'framework_ip'}" ne "$config_last{'framework_ip'}" ) {
		verbose_log("Database Profile: framework_ip change detected (old=$config_last{'framework_ip'} new=$config{'framework_ip'})");

		if ( "$config_last{'framework_ip'}" eq "127.0.0.1" ) {
			$command = "echo \"GRANT ALL ON *.* to \'root\'@\'$config{'framework_ip'}\' IDENTIFIED BY \'$db_pass\';FLUSH PRIVILEGES;\" | ossim-db";
		}else{
			$command = "echo \"UPDATE mysql.user SET Host = \'$config{'framework_ip'}\' WHERE Host = \'$config_last{'framework_ip'}\' AND User != \'replication\';FLUSH PRIVILEGES;\" | ossim-db";
		}

		debug_log($command);
		system($command);
	}

    # para server_ip ya está siendo 127.0.0.1 cuando es AIO.
    # siendo así, en lugar de update/replace, hay que crear una nueva entrada si se cambia la ip de server_ip, para seguir permitiendo 127.0.0.1.
	if ( "$config{'server_ip'}" ne "$config_last{'server_ip'}" ) {
		verbose_log("Database Profile: server_ip change detected (old=$config_last{'server_ip'} new=$config{'server_ip'})");

		if ( "$config_last{'server_ip'}" eq "127.0.0.1" ) {
			$command = "echo \"GRANT ALL ON *.* to \'root\'@\'$config{'server_ip'}\' IDENTIFIED BY \'$db_pass\';FLUSH PRIVILEGES;\" | ossim-db";
		}else{
			$command = "echo \"UPDATE mysql.user SET Host = \'$config{'server_ip'}\' WHERE Host = \'$config_last{'server_ip'}\' AND User != \'replication\';FLUSH PRIVILEGES;\" | ossim-db";
		}

		debug_log($command);
		system($command);
	}

}


1;
