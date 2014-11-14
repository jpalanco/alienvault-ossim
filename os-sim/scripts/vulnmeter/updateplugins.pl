#!/usr/bin/perl
#
###############################################################################
#
#    License:
#
#   Copyright (c) 2003-2006 ossim.net
#   Copyright (c) 2007-2013 AlienVault
#   All rights reserved.
#
#   This package is free software; you can redistribute it and/or modify
#   it under the terms of the GNU General Public License as published by
#   the Free Software Foundation; version 2 dated June, 1991.
#   You may not use, modify or distribute this program under any other version
#   of the GNU General Public License.
#
#   This package is distributed in the hope that it will be useful,
#   but WITHOUT ANY WARRANTY; without even the implied warranty of
#   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#   GNU General Public License for more details.
#
#   You should have received a copy of the GNU General Public License
#   along with this package; if not, write to the Free Software
#   Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
#   MA  02110-1301  USA
#
#
# On Debian GNU/Linux systems, the complete text of the GNU General
# Public License can be found in `/usr/share/common-licenses/GPL-2'.
#
# Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
#
###############################################################################
#                          Last update: 07.5.2008                             #
#-----------------------------------------------------------------------------#
#                                 Inprotect                                   #
#-----------------------------------------------------------------------------#
# Copyright (C) 2008 Inprotect.net                                            #
#                                                                             #
# This program is free software; you can redistribute it and/or modify it     #
# under the terms of version 2 of the GNU General Public License as published #
# by the Free Software Foundation.                                            #
#                                                                             #
# This program is distributed in the hope that it will be useful, but WITHOUT #
# ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or       #
# FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for    #
# more details.                                                               #
#                                                                             #
# You should have received a copy of the GNU General Public License along     #
# with this program; if not, write to the Free Software Foundation, Inc.,     #
# 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA                       #
#                                                                             #
# Contact Information:                                                        #
# inprotect-devel@lists.sourceforge.net                                       #
# http://www.inprotect.net                                                    #
###############################################################################
# See the README.txt and/or help files for more information on how to use &   #
# configuration.                                                              #
# See the LICENSE.txt file for more information on the License this software  #
# is distributed under.                                                       #
#                                                                             #
# This program is intended for use in an authorized manner only, and the      #
# author can not be held liable for anything done with this program, code, or #
# items discovered with this program's use.                                   #
###############################################################################
##
##    PRGM AUTHOR: Various
##   PROGRAM NAME: updatePlugin
##   PROGRAM DATE: 02/23/2009
##  PROGM VERSION: 1.1
##  REVISION HIST:
##        04/23/2008 -  FIRST VERSION OF RECODE TO ADD DEBUG AND WORK OUT TYPICAL
##                      FLAWS THAT ARE AFFECTING PROFILES CONFIGS
##      06/29/2008 -    Bug fix now was created/modified/deleted being populated by $now as was not defined
##      01/01/2009 -    Significant Recode to vastly improve Import / Update time.  ( Removed all the unnecessary
##                      queries that contstantly were touching the DB to use a hash to track/compare the numerous tables.
##      02/23/2009 -    More bug fixes ( hopefully this should be the last of the issues going back as far as two years.
##                      per nessus_settings_plugins records being created for each profile.
##      04/07/2009 -    Major fix to handle backslashes, single-quotes, etc in name, summary, description fields
$| = 1;

use Getopt::Std;
use Data::Dumper;

my %loginfo;         # plot information hash
   $loginfo{'1'} = "FATAL";
   $loginfo{'2'} = "ERROR";
   $loginfo{'3'} = "WARN";
   $loginfo{'4'} = "";
   $loginfo{'5'} = "DEBUG";
   
my $debug            = 0;
my $log_level        = 4;

#check machine profile

#Declare constants
use constant TRUE => 1;
use constant FALSE => 0;

my $machine_profile = "";
my $is_sensor       = FALSE;
my $is_framework    = FALSE;


if (-e "/etc/ossim/ossim_setup.conf") {
    $machine_profile = `grep ^profile /etc/ossim/ossim_setup.conf | cut -f 2 -d "=" | head -1`; chomp($machine_profile);
}

if($machine_profile =~ /^sensor$/i ) {
    $is_sensor = TRUE;
}
else {
    my @mps = split(/,/, $machine_profile);

    foreach my $mp (@mps) {
        $mp =~ s/^\s+//; # clean spaces
        $mp =~ s/\s+$//;
    
        if ($mp =~ /^framework$/i ) {
            $is_framework = 1;
        }
    }
}

if (!$is_sensor && !$is_framework) {
    die("Neither sensor profile nor framework profile has been found.\n");
}
elsif ($is_sensor) {
    logwriter( "Sensor profile has been found...", 4 );
    # Update OpenVAS plugins
    if (-e "/usr/sbin/openvas-nvt-sync" ) {
        logwriter( "Updating OpenVAS plugins...", 4 );
        my $ocommand = system ("sudo /usr/sbin/openvas-nvt-sync --wget >> /tmp/update_scanner_plugins.log 2>&1");

        my $fcommand = system ("sudo /usr/share/ossim/scripts/vulnmeter/fix_openvas_plugins.sh >> /tmp/update_scanner_plugins.log 2>&1");
        
        if ( -e "/etc/init.d/openvas-manager" && $ocommand == 0 ) {
           logwriter( "Rebuilding OpenVAS NVT cache...", 4 );
           system ("sudo /usr/share/ossim/scripts/vulnmeter/openvas_rebuild.sh >> /tmp/rebuild_nvt_cache.log 2>&1") == 0 or logwriter( "Can not rebuid the NVT cache", 3 ); 
        }
        
        if($ocommand == 1) {
            logwriter( "updateplugins: No new plugins installed for OpenVAS", 3 );
        }
    }
    # Update Nessus plugins
    if ( -e "/usr/sbin/nessus-update-plugins" ) {
        logwriter( "Updating Nessus plugins...", 4 );
        system ("sudo /usr/sbin/nessus-update-plugins >> /tmp/update_scanner_plugins.log 2>&1") == 0 or logwriter( "updateplugins: No new plugins installed for Nessus", 3 );
    }
    print "\n";
    exit;
}
else {
    logwriter( "Framework profile has been found...", 4 );
}

if(!$is_sensor && $is_framework) {
    eval q{ use ossim_conf; };
    eval q{ use DBI; }; 
    eval q{ use XML::Simple; };
    local $ENV{XML_SIMPLE_PREFERRED_PARSER} = "XML::Parser";
}

#use vars qw/%CONFIG/;

#&load_configs("/etc/inprotect.cfg");

my %CONFIG = ();

my $check_command            = 0; # to display errors when executing omp binary

my $dbhost = `grep ^ossim_host= /etc/ossim/framework/ossim.conf | cut -f 2 -d "="`; chomp($dbhost);
$dbhost = "localhost" if ($dbhost eq "");
my $dbuser = `grep ^ossim_user= /etc/ossim/framework/ossim.conf | cut -f 2 -d "="`; chomp($dbuser);
my $dbpass = `grep ^ossim_pass= /etc/ossim/framework/ossim.conf | cut -f 2 -d "="`; chomp($dbpass);

my $uuid = "";
if (-e "/etc/ossim/framework/db_encryption_key") {
	$uuid = `grep "^key=" /etc/ossim/framework/db_encryption_key | awk 'BEGIN { FS = "=" } ; {print \$2}'`; chomp($uuid);
} else {
	$uuid = uc(`/usr/bin/alienvault-system-id`);
}

my $custom = FALSE;

$CONFIG{'DATABASENAME'} = "alienvault";
$CONFIG{'DATABASEHOST'} = $dbhost;

if(defined($ARGV[0])) {
    $CONFIG{'UPDATEPLUGINS'} = ($ARGV[0] eq "update") ? 1 : 0;
    $CONFIG{'MIGRATEDB'} = ($ARGV[0] eq "migrate") ? 1 : 0;
}
else {
    $CONFIG{'UPDATEPLUGINS'} = 1;
}
$CONFIG{'SYNCHRONIZATIONMETHOD'} = (defined($ARGV[1]) && $ARGV[1] ne "") ? $ARGV[1]:"";

if ($ARGV[1] eq "custom" && $ARGV[0] eq "update")
{
    $custom = TRUE;
    $CONFIG{'SYNCHRONIZATIONMETHOD'} = "";
}

$CONFIG{'DATABASEDSN'} = "DBI:mysql";
$CONFIG{'DATABASEUSER'} = $dbuser;
$CONFIG{'DATABASEPASSWORD'} = $dbpass;

my $vervose = (defined($ARGV[2]) && $ARGV[2] eq "1") ? $ARGV[2] : "";

my ( $dbh, $sth_sel, $sql );   #DATABASE HANDLE TO BE USED THROUGHOUT PROGRAM
my %nessus_vars = ();
$dbh = conn_db();
$sql = qq{ select *,AES_DECRYPT(value,'$uuid') as dvalue from config where conf like 'nessus%' };
$sth_sel=$dbh->prepare( $sql );
$sth_sel->execute;
while ( my ($conf, $value, $dvalue) = $sth_sel->fetchrow_array ) {
    if(!defined($dvalue)) {
        $dvalue = "";
    }
   $nessus_vars{$conf} = ($dvalue ne "") ? $dvalue : $value;
}

# Quick and dirty test to see if this should run
$tmp_sql = qq{ select count(*) from vuln_jobs};
eval {
$dbh->do( $tmp_sql );
};

#print "Tables not created yet, please upgrade from the web interface and run again.\n"; 
#;

#$CONFIG{'SERVERID'} = 2;
$CONFIG{'CHECKINTERVAL'} = 300;

if (-e $nessus_vars{'nessus_updater_path'}) {
    $CONFIG{'NESSUSUPDATEPLUGINSPATH'} = $nessus_vars{'nessus_updater_path'};
}
else {
    $CONFIG{'NESSUSUPDATEPLUGINSPATH'} = ($nessus_vars{'nessus_path'} =~ /nessus/) ? "/usr/sbin/nessus-update-plugins" : "/usr/sbin/openvas-nvt-sync";
}
$binary_location = $nessus_vars{'nessus_path'};

$CONFIG{'NESSUSHOST'} = $nessus_vars{'nessus_host'};
$CONFIG{'NESSUSUSER'} = $nessus_vars{'nessus_user'};
$CONFIG{'NESSUSPASSWORD'} = $nessus_vars{'nessus_pass'};
$CONFIG{'NESSUSPORT'} = $nessus_vars{'nessus_port'};
$CONFIG{'MYSQLPATH'} = "/usr/bin/mysql";

$CONFIG{'ROOTDIR'} = $nessus_vars{'nessus_rpt_path'};

$mysqlpath = "$CONFIG{'MYSQLPATH'}";                               #PATH TO MYSQL EXECUTABLE
$omp_plugins = $CONFIG{'ROOTDIR'}."tmp/plugins.xml";                #Temp OpenVas Manager plugins file
$xml_output = $CONFIG{'ROOTDIR'}."tmp/tmp.xml";                     #Temp OpenVas Manager output
$openvas_nessus_plugins = $CONFIG{'ROOTDIR'}."tmp/plugins.sql";     #Temp OpenVas/Nessus plugins file

my %profiles = ();
# $profiles{'PortScan|PortScan|F|admin|4|4'} = "Port scanners";
# $profiles{'Mac|MACOSX Test|F|admin|4|4'} = "MacOS X Local Security Checks|Mac OS X Local Security Checks";
# $profiles{'Firewalls|Firewalls Tests|F|admin|4|4'} = "Firewalls";
# $profiles{'Linux|Linux Test|F|admin|1|1'} = "Databases|Debian Local Security Checks|Default Unix Accounts|Finger abuses|FTP|Gain a shell remotely|Gain root remotely|General|Gentoo Local Security Checks|Port scanners|Red Hat Local Security Checks|Remote file access|RPC|Service detection|SLAD|SMTP problems|SNMP|Useless services|Web Servers";
# $profiles{'CISCO|Cisco Test|F|admin|4|1'} = "CISCO";
# $profiles{'UNIX|UNIX Test|F|admin|4|4'} = "AIX Local Security Checks|Default Unix Accounts|Finger abuses|FTP|Gain a shell remotely|Gain root remotely|MacOS X Local Security Checks|Mac OS X Local Security Checks|RPC|Service detection|SMTP problems|Useless services|Web Servers";
# $profiles{'Perimeter|External Perimeter Scan|F|admin|1|1'} = "Backdoors|CGI abuses|CGI abuses : XSS|CISCO|Databases|Finger abuses|Firewalls|FTP|Gain a shell remotely|Gain root remotely|General|Netware|NIS|Port scanners|Remote file access|RPC|Service detection|SMTP problems|SNMP|Useless services|Web Servers|Windows|Windows : Microsoft Bulletins|Windows : User management";
# $profiles{'Mail||F|admin|1|1'} = "SMTP problems";
# $profiles{'Windows||F|0|1|1'} = "Windows|Windows : Microsoft Bulletins|Windows : User management";
# $profiles{'Database||F|admin|1|1'} = "Databases";
# $profiles{'Info||C|admin|1|1'} = "infos|settings";
# $profiles{'DOS|Denial of Service|C|admin|1|1'} = "denial|destructive_attack|flood|kill_host";
# $profiles{'Web Scan||F|admin|1|1'} = "CGI abuses|CGI abuses : XSS|Web Servers";
# $profiles{'Stealth||C|admin|1|1'} = "infos|scanner|settings";
$profiles{'Default|Non destructive Full and Fast scan|C|0|2|2'} = "attack|end|infos|init|mixed|scanner|settings";
$profiles{'Deep|Non destructive Full and Slow scan|C|0|2|2'} = "attack|end|infos|init|mixed|scanner|settings";
$profiles{'Ultimate|Full and Fast scan including Destructive tests|C|0|2|2'} = "attack|end|infos|init|mixed|scanner|settings";

if ($custom == FALSE)
{
    #Load sensor to update profiles
    
    my @sensors = ();
    
    if ($binary_location =~ /omp\s*$/) {
        $sql = qq{ select inet6_ntop(ip) as sIP, vns.port as port, vns.user, AES_DECRYPT(PASSWORD,'$uuid') as dpass, PASSWORD AS pass from sensor s, vuln_nessus_servers vns 
                    WHERE HEX(s.id)=vns.hostname AND vns.enabled=1 };
        $sth_sel=$dbh->prepare( $sql );
        $sth_sel->execute;
        while ( my ($sIP, $port, $user, $dpass, $pass) = $sth_sel->fetchrow_array ) {
            if( noEmpty($dpass) ) {
                $dpass =~ s/'/'"'"'/g;
                $user  =~ s/'/'"'"'/g;
    
                if (check_openvas_sensor($sIP, $port, $user, $dpass)){
                    push (@sensors, "$sIP|$port|$user|$dpass");
                }
                else {
                    logwriter( "Skipping ip $sIP", 4 );
                }
            }
            else {
                $pass =~ s/'/'"'"'/g;
                $user  =~ s/'/'"'"'/g;
                
                if (check_openvas_sensor($sIP, $port, $user, $pass)){
                    push (@sensors, "$sIP|$port|$user|$pass");
                }
                else {
                    logwriter( "Skipping ip $sIP", 4 );
                }
            }
        }
    
        die("\nNo sensors found") if($#sensors == -1);
    }
}


my $updateplugins="$CONFIG{'UPDATEPLUGINS'}";

my @disabled_plugins = ("11219", "10335", "14663", "11840", "14272", "14274", "10796", "80000", "80009", "80001", "80002", "80112");

#my ( $serverid );

my ( $dsn);        #DATABASE HANDLE TO BE USED THROUGHOUT PROGRAM

my ( $nessus, $nessus_user, $nessus_pass, $nessus_host, $nessus_port, $openvas_manager_common);

getopts("dh?",\%options);

main( );
disconn_db($dbh);

exit;

sub main {

    my ( $sth_sel, $sql );

    if( $options{d} ) {                #ENABLE DEBUGGING
        print "Debugging mode\n";
        $debug = 1;
    }

    $nessus      = $binary_location;
    $nessus_user = $CONFIG{'NESSUSUSER'};
    $nessus_pass = $CONFIG{'NESSUSPASSWORD'};
    $nessus_host = $CONFIG{'NESSUSHOST'};
    $nessus_port = $CONFIG{'NESSUSPORT'};

    $nessus_pass =~ s/'/'"'"'/g;
    $nessus_user =~ s/'/'"'"'/g;
    
    $openvas_manager_common = "$nessus -h $nessus_host -p $nessus_port -u '$nessus_user' -w '$nessus_pass' -iX";

    #load_db_configs ( );

    logwriter( "host=$nessus_host, port=$nessus_port, user=$nessus_user, pass=$nessus_pass", 5 );

    if ($nessus =~ /omp\s*$/ && $custom == FALSE) { delete_all_tasks(); }
    
    #PROCEED WITH FORCE NESSUS TO UPDATE PLUGINS
    if ($custom == TRUE)
    {
        logwriter( "updateplugins: configured to update custom profiles", 4 );
    }
    elsif ($updateplugins == TRUE) {
        logwriter( "updateplugins: executing update-plugins", 4 );
        perform_update( );
    }
    else {
        logwriter( "updateplugins: configured to not updateplugins", 4 );
    }
    
    if(defined($CONFIG{'MIGRATEDB'}) && $CONFIG{'MIGRATEDB'}==1) {
        logwriter( "updateplugins: configured to migrate DB", 4 ); 
        
        $sql = qq{TRUNCATE vuln_nessus_category};
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
            
        $sql = qq{TRUNCATE vuln_nessus_family}; 
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
           
        $sql = qq{TRUNCATE vuln_nessus_plugins};
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
            
        $sql = qq{TRUNCATE vuln_nessus_preferences};
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
            
        $sql = qq{TRUNCATE vuln_nessus_preferences_defaults};
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
        
        $sql = qq{TRUNCATE vuln_nessus_settings};
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
           
        $sql = qq{TRUNCATE vuln_nessus_settings_category};
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
            
        $sql = qq{TRUNCATE vuln_nessus_settings_family};
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
            
        $sql = qq{TRUNCATE vuln_nessus_settings_plugins};
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
            
        $sql = qq{TRUNCATE vuln_nessus_settings_preferences};
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
        
        if ($nessus =~ /omp\s*$/) {
            delete_configs();
        }
    }
    else {
        logwriter( "updateplugins: configured to not migrate DB", 4 );
    }

    if ($custom == FALSE)
    {    
        disconn_db($dbh);
        $dbh = conn_db();
        dump_plugins();
    
        disconn_db($dbh);
        $dbh = conn_db();
        import_plugins( );
    
        disconn_db($dbh);
        $dbh = conn_db();
        update_categories( );
        
        disconn_db($dbh);
        $dbh = conn_db();
        update_families( );

        disconn_db($dbh);
        $dbh = conn_db();
        if( $nessus =~ /omp\s*$/ ) {
            update_openvas_plugins();
        }
        else {
            update_nessus_plugins();
        }
    }
    
    disconn_db($dbh);
    $dbh = conn_db();
    update_settings_plugins($custom);
        
    if ($custom == FALSE)
    {
        disconn_db($dbh);
        $dbh = conn_db();
        update_preferences();
        
        disconn_db($dbh);
        $dbh = conn_db();
        generate_profiles(\%profiles);
    
        if( $nessus =~ /omp\s*$/ ) {
            disconn_db($dbh);
            $dbh = conn_db();
            enable_port_scanner_plugins();
        }
    
        disconn_db($dbh);
        $dbh = conn_db();
        $sql = qq{ DROP TABLE `vuln_plugins`; };
        safe_db_write( $sql, 5 );
    }
    
    disconn_db($dbh);
    
    if ($custom == FALSE)
    {
        #
        print "\n\nUpdating plugin_sid vulnerabilities scanner ids\n";
        system("perl /usr/share/ossim/scripts/vulnmeter/update_nessus_ids.pl");
    }
    
    #end of main
    exit;

}

sub perform_update {

    logwriter( "BEGIN  - PERFORM UPDATE", 4 );
    my $time_start = time();

    if ( -e $CONFIG{'NESSUSUPDATEPLUGINSPATH'} ) { 
    
       if ( $CONFIG{'SYNCHRONIZATIONMETHOD'} eq "offline" ) {
           logwriter( "updateplugins: doing an offline update", 4 );
       } else {
           if ($CONFIG{'SYNCHRONIZATIONMETHOD'} eq "wget" && $nessus_vars{'nessus_path'} !~ /nessus/) {
                $CONFIG{'NESSUSUPDATEPLUGINSPATH'} .= " --wget";
           }
           logwriter( "$CONFIG{'NESSUSUPDATEPLUGINSPATH'} >> /tmp/update_scanner_plugins_rsync.log", 4 );
           system ("sudo $CONFIG{'NESSUSUPDATEPLUGINSPATH'} >> /tmp/update_scanner_plugins_rsync.log 2>&1") == 0 or logwriter( "updateplugins: No new plugins installed", 3 ); 

            if (-e "/etc/init.d/openvas-manager" && $nessus_vars{'nessus_path'} =~ /omp\s*$/) {
                #Rebuild the NVT cache
                logwriter( "Fixing OpenVAS Plugins...", 4 );
                system ("sudo /usr/share/ossim/scripts/vulnmeter/fix_openvas_plugins.sh >> /tmp/update_scanner_plugins.log 2>&1");

                logwriter( "Rebuilding NVT cache...", 4 );
                system ("sudo /usr/share/ossim/scripts/vulnmeter/openvas_rebuild.sh >> /tmp/rebuild_nvt_cache.log 2>&1") == 0 or logwriter( "Can not rebuid the NVT cache", 3 ); 
            }
        
            if ($nessus_vars{'nessus_path'} !~ /omp\s*$/) {
                logwriter( "updateplugins: sleeping for 120sec to allow nessus to restart", 4 );
                sleep 120;
            }
       }
   } else {
      logwriter( "INVALID PATH/FILE update-plugins named \"$CONFIG{'NESSUSUPDATEPLUGINSPATH'}\"", 3);
      logwriter( "Sensor profile has not been found on local machine, you have to run '/usr/share/ossim/scripts/vulnmeter/updateplugins.pl update' on the remote sensor", 3);
   }

   my $time_run = time() - $time_start;
   logwriter( "FINISH - PERFORM UPDATE [ Process took $time_run seconds ]\n", 4 );
}

sub dump_plugins {

    logwriter( "BEGIN  - DUMP PLUGINS", 4 );
    my $time_start = time();
    
    my $cmd = "";
    my $file_omp_command = "";
    
    if ($nessus =~ /omp\s*$/) {
    
        #Delete existing temporary file
        unlink $omp_plugins if -e $omp_plugins;

        $file_omp_command = "/usr/share/ossim/www/tmp/omp_command_$$".int(rand(1000000)).".xml";

        system("echo \"<GET_NVTS details='1'/>\" > '$file_omp_command'");
            
        $cmd = "$openvas_manager_common - < ".$file_omp_command." > $omp_plugins 2>&1";

        #logwriter( "$cmd", 4 );
        
        my $imp = system ( $cmd );
        
        if(defined($debug) && $debug == 0 ) { unlink $file_omp_command if -e $file_omp_command; }

        if ( $imp != 0 ) { logwriter( "updateplugins: Failed Dump Plugins", 2 ); }
        
        my $xml = eval {XMLin($omp_plugins, keyattr => [])};
        
        #print Dumper($xml);
        
        if ($@ ne "") { logwriter( "Cant' read XML $omp_plugins" ); }
        if ($xml->{'status'} !~ /20\d/) {
            my $status = $xml->{'status'};
            my $status_text = $xml->{'status_text'};
            logwriter( "Error: status = $status, status_text = '$status_text' ($omp_plugins)", 2 );
        }
    }
    else {
    
        #Delete existing temporary file
        unlink $openvas_nessus_plugins if -e $openvas_nessus_plugins;

        #Dump Nessus plugins info into a file
        $cmd = "$binary_location -xpS -q $nessus_host $nessus_port '$nessus_user' '$nessus_pass' | perl /usr/share/ossim/scripts/vulnmeter/nessus_filter.pl > $openvas_nessus_plugins";

        #print "$cmd\n"; 
        logwriter( "$cmd", 5 );
        my $imp = system ( $cmd );

        if ( $imp != 0 ) { logwriter( "updateplugins: Failed Dump Plugins", 2 ); }
        
    }
    
    my $time_run = time() - $time_start;
    logwriter( "FINISH - DUMP PLUGINS [ Process took $time_run seconds ]\n", 4 );
    return 1;
}

sub import_plugins {

    logwriter( "BEGIN  - IMPORT PLUGINS", 4 );
    
    my $nplugins = 0;
    my $time_start = time();

#    $sql = qq{ TRUNCATE TABLE `vuln_plugins`; };
#    safe_db_write( $sql, 5 );

    
    if ($nessus =~ /omp\s*$/) {
    
        my @items=();
        
        disconn_db($dbh); # disconnect from the database

        my $xml = eval {XMLin($omp_plugins, keyattr => [])};
        
        #print Dumper($xml);
        
        if ($@ ne "") {  logwriter( "Cant' read XML $omp_plugins", 2); }
        if ($xml->{'status'} !~ /20\d/) {
            my $status = $xml->{'status'};
            my $status_text = $xml->{'status_text'};
             logwriter( "Error: status = $status, status_text = '$status_text' ($omp_plugins)", 2);
        }
        
        $dbh = conn_db(); # connected to the database
    
        my $sql = qq{ DROP TABLE IF EXISTS vuln_plugins };
        safe_db_write( $sql, 5 );
        
        $sql =  qq{CREATE TABLE vuln_plugins (
                    id int NOT NULL,
                    oid varchar(50) NOT NULL,
                    name varchar(255),
                    family varchar(255),
                    category varchar(255),
                    copyright varchar(255),
                    summary varchar(255),
                    description blob,
                    version varchar(255),
                    cve_id varchar(255),
                    bugtraq_id varchar(255),
                    xref blob,
                    cvss_base varchar(50) NOT NULL,
                    primary key (id))};
        safe_db_write( $sql, 5 );

        if (ref($xml->{'nvt'}) eq 'ARRAY') {
            @items = @{$xml->{'nvt'}};
        } else {
            push(@items,$xml->{'nvt'});
        }
        
        foreach my $nvt (@items) {
                my $name = $nvt->{'name'};
                my $oid = $nvt->{'oid'}; 
                my $id = $oid; $id =~ s/.*\.//;
                my $family = $nvt->{'family'};
                my $category = $nvt->{'category'};
                
                my $cvss_base = "";
                if (ref($nvt->{"cvss_base"}) ne 'HASH') {
                    $cvss_base = $nvt->{'cvss_base'};
                }
                
                my $copyright = $nvt->{'copyright'};
                my $summary = $nvt->{'summary'};
                $summary =~ s/\"/\'/g; 
                my $description = $nvt->{'description'};
                $description =~ s/\"/\'/g;
                my $version = $nvt->{'version'};
                my $cve_id = $nvt->{'cve_id'};
                my $bugtraq_id = $nvt->{'bugtraq_id'};
                my $xref = $nvt->{'xrefs'};

                $sql = qq{INSERT IGNORE INTO vuln_plugins VALUES ('$id','$oid',"$name",'$family','$category',"$copyright","$summary","$description",'$version','$cve_id','$bugtraq_id', '$xref', '$cvss_base')};
                #print "$sql\n";
                $sth_sel = $dbh->prepare( $sql );
                $sth_sel->execute;
                
                $nplugins++;
                #print "\r$nplugins";
        }
        print "\n";
    }
    
    else {
        #import Nessus plugins from a file
        my $cmd = "$mysqlpath --force --user=$CONFIG{'DATABASEUSER'} --password=$CONFIG{'DATABASEPASSWORD'} --host=$CONFIG{'DATABASEHOST'} $CONFIG{'DATABASENAME'} < $openvas_nessus_plugins";
        logwriter( "$cmd", 5 );
        my $imp = system ( $cmd );
        if ( $imp != 0 ) { logwriter( "updateplugins: Failed Import Plugins", 2 ); }
    
    }
    $sql = qq{ UPDATE vuln_plugins SET family='Others' WHERE family=''};
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;
    
    $sql = qq{ UPDATE vuln_plugins SET category='Others' WHERE category=''};
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;

    #Delete existing temporary file
    unlink $omp_plugins if -e $omp_plugins;
    unlink $openvas_nessus_plugins if -e $openvas_nessus_plugins;

    my $time_run = time() - $time_start;
    logwriter( "FINISH - IMPORT PLUGINS [ $nplugins plugins - Process took $time_run seconds ]\n", 4 );
    return 1;

}

sub update_categories {
    my ( $sth_sel, $sth_selc, $sth_ins, $sth_insc, $sql );

    logwriter( "BEGIN  - UPDATE CATEGORIES", 4 );
    my $time_start = time();

    #Updating family and category tables
    $sql = qq{ select distinct vuln_plugins.category from vuln_plugins left join vuln_nessus_category on 
        vuln_plugins.category = vuln_nessus_category.name where vuln_nessus_category.id is null order by category };
    logwriter( "$sql", 5 );
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;

    while (my ($categoryname) = $sth_sel->fetchrow_array ) {
        if ($categoryname ne "") {
            $sql = qq{ insert into vuln_nessus_category (name) values('$categoryname') };
            safe_db_write( $sql, 5 );

            $sql = qq{ select id from vuln_nessus_category where name='$categoryname' };
            logwriter( "$sql", 5 );
            $sth_selc=$dbh->prepare( $sql );
            $sth_selc->execute;
            ($catid)=$sth_selc->fetchrow_array;

            $sql = qq{ select id, 1 from vuln_nessus_settings }; # Force "Set all autoenable categories" to Enable all
            logwriter( "$sql", 5 );
            $sth_selc=$dbh->prepare( $sql );
            $sth_selc->execute;
            while (($setid,$status) =$sth_selc->fetchrow_array ) {
                $sql = qq{ insert into vuln_nessus_settings_category (sid, cid, status) values ($setid, $catid, $status) on duplicate key update status=$status };
                safe_db_write( $sql, 5 );
            }
            $sth_selc->finish();
        }
    }

    my $time_run = time() - $time_start;
    logwriter( "FINISH - UPDATE CATEGORIES [ Process took $time_run seconds ]\n", 4 );

}

sub update_families {
    my ( $sth_sel, $sth_self, $sth_ins, $sth_insf, $sql );

    logwriter( "BEGIN  - UPDATE FAMILIES", 4 );
    my $time_start = time();

    #Updating family and category tables
    $sql = qq{ select distinct vuln_plugins.family from vuln_plugins left join vuln_nessus_family on 
            vuln_plugins.family = vuln_nessus_family.name where vuln_nessus_family.id is null order by family };
    logwriter( "$sql", 5 );
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;

    while (my ($familyname) = $sth_sel->fetchrow_array ) {
        if ($familyname ne "") {
            $sql = qq{ insert into vuln_nessus_family (name) values('$familyname') };
            safe_db_write( $sql, 5 );

            $sql = qq{ select id from vuln_nessus_family where name='$familyname' };
            logwriter( "$sql", 5 );
            $sth_self=$dbh->prepare( $sql );
            $sth_self->execute;

            ($famid)=$sth_self->fetchrow_array;

            $sql = qq{ select id, 1 from vuln_nessus_settings }; # Force "Set all autoenable families" to Enable all
            logwriter( "$sql", 5 );
            $sth_self=$dbh->prepare( $sql );
            $sth_self->execute;
            while (($setid,$status) =$sth_self->fetchrow_array ) {
                $sql = qq{ insert into vuln_nessus_settings_family (sid, fid, status) values ($setid, $famid, $status) on duplicate key update status=$status };
                safe_db_write( $sql, 5 );
            }
            $sth_self->finish();
        }
    }

    my $time_run = time() - $time_start;
    logwriter( "FINISH - UPDATE FAMILIES [ Process took $time_run seconds ]\n", 4 );

}
sub update_nessus_plugins {

    logwriter( "BEGIN  - UPDATE NESSUS_PLUGINS", 4 );
    my $time_start = time();

    my ( $sth_sel, $sth_sel2, $sth_sel3, $sth_ins, $sql );
    my $now = genScanTime();

    #USE TO MAKE SURE PLUGINS TABLE IS NOT EMPTY (OTHERWISE LATER CODE WOULD FLAG ALL NESSUS_PLUGINS DELETED )
    my $plugin_count = 0;

    #ANOTHER REWRITE TO CLEANUP UNNECESSARY DB HEAVY LIFTING
    #FIST LESTS PROCESS ALL RECORDS PER THE PLUGINS TABLE TO SEE
    #	1.  ALL PLUGINS THAT NEED ADDED
    #	2.  ALL PLUGINS THAT EXIST TO BE UPDATED

    #THEN NEED A FOLLOWUP RUN AGAINST ALL PLUGINS THAT NEED FLAGGED DELETED ( IF ANY )

    $sql = qq{ SELECT t1.id, t1.oid, t1.name, t3.id, t4.id, t1.copyright, t1.summary, t1.description,
	t1.version, t2.id, t2.version, t2.custom_risk, t1.cve_id, t1.bugtraq_id, t1.xref
            FROM vuln_plugins t1
	    LEFT JOIN vuln_nessus_plugins t2 on t1.id=t2.id
            LEFT JOIN vuln_nessus_family t3 ON t1.family = t3.name
	    LEFT JOIN vuln_nessus_category t4 ON t1.category = t4.name
    };

    logwriter( "$sql", 5 );
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;
    while ( my ( $pid, $oid, $pname, $pfamily, $pcategory, $pcopyright, $psummary, $pdescription, $pversion,
	$pluginid, $pluginversion, $plugin_crisk, $pcve_id, $p_bug, $p_xref )= $sth_sel->fetchrow_array ) {  
    
    if ($pname ne "" && $pfamily ne "" && $pcategory ne "") {
        
        #$pcve_id =~ s/(\d+\-\d+)/CVE-$1/g  if ( ($pcve_id !~ /^CVE/) && ($pcve_id !~ /^CAN/) );
        
        $pcve_id =~ s/CAN\-CVE/CVE/g;
        $pcve_id =~ s/CAN\-(\d+)/CVE-$1/g;
        
        my @pcve_ids = split(/,/, $pcve_id);
        my @pcve_tmp=();
        foreach (@pcve_ids){
            s/^ *| *$//g;
            s/(\d+\-\d+)/CVE-$1/ if ($_ !~ /^CVE/);
            push @pcve_tmp,$_;
        }
        $pcve_id = join(", ", @pcve_tmp);
        
        $pcve_id = ""  if ( $pcve_id !~ /-/);
        $pcve_id = ""  if ($pcve_id =~ /NOCVE/); 
        $p_bug = ""  if ($p_bug =~ /NOBID/); 
        #print "pid: $pid\n";
        #print "name: $pname\n";
        
    	$pname =~ s/'/\\'/g;
    	$psummary =~ s/'/\\'/g;
            $pdescription =~ s/\\/\\\\/g;
    	$pdescription =~ s/'/\\'/g;

    	if ( !defined( $plugin_crisk ) || $plugin_crisk eq "" ) { $plugin_crisk = "NULL"; }

            my $risk=7;
            $risk=1 if ($pdescription =~ m/Risk [fF]actor\s*:+;*\s*(\\n)*Serious/s);
            $risk=1 if ($pdescription =~ m/Risk [fF]actor\s*:+;*\s*(\\n)*Critical/s);
            $risk=2 if ($pdescription =~ m/Risk [fF]actor\s*:+;*\s*(\\n)*High/s);
            $risk=3 if ($pdescription =~ m/Risk [fF]actor\s*:+;*\s*(\\n)*Medium/s);
            $risk=3 if ($pdescription =~ m/Risk [fF]actor\s*:+;*\s*(\\n)*Medium\/Low/s);
            $risk=3 if ($pdescription =~ m/Risk [fF]actor\s*:+;*\s*(\\n)*Low\/Medium/s);
            $risk=6 if ($pdescription =~ m/Risk [fF]actor\s*:+;*\s*(\\n)*Low/s);
            $risk=7 if ($pdescription =~ m/Risk [fF]actor\s*:+;*\s*(\\n)*Info/s);
            $risk=7 if ($pdescription =~ m/Risk [fF]actor\s*:+;*\s*(\\n)*[nN]one/s);

    	if ( !defined( $pluginid ) || $pluginid eq "" ) {
    	    $plugins{$pid}{'do'} = "insert";
    	    $sql = qq{ INSERT INTO vuln_nessus_plugins ( id, oid, name, copyright, summary, description, cve_id, bugtraq_id, 
    		xref, enabled, version, created, modified, deleted, category, family, risk, custom_risk ) VALUES
    		( '$pid', '$oid', '$pname', '$pcopyright', '$psummary', '$pdescription', '$pcve_id', '$p_bug', '$p_xref',
                      'Y','$pversion', '$now', null, null, '$pcategory', '$pfamily', '$risk', NULL ); };
                safe_db_write( $sql, 4 );
            #print "[$sql]\n"; 

    	} else {
    	    $plugins{$pid}{'do'} = "update";
                if ($pluginversion ne $pversion) {
                    $sql = qq{ UPDATE vuln_nessus_plugins SET enabled='Y', version='$pversion', risk='$risk', modified='$now', 
                        description='$pdescription', cve_id='$pcve_id', bugtraq_id='$p_bug'
    		    WHERE id='$pluginid' };
                    safe_db_write( $sql, 5 );

                }
    	}
    	$plugin_count +=1;
        }
    }

    #UPDATE RISK WITH CUSTOM VALUE AS NEEDED
    $sql = qq{ UPDATE vuln_nessus_plugins SET risk=custom_risk WHERE custom_risk IS NOT NULL AND custom_risk > 0 }; 
    safe_db_write( $sql, 3 );

    #UPDATE DELETED PLUGINS
    if ( $plugin_count > 25000 ) {	    #MAKE SURE SOMETHING REALLY NEEDS DELETED
        $sql = qq{ SELECT t1.id FROM vuln_nessus_plugins t1
	    LEFT JOIN vuln_plugins t2 on t1.id=t2.id
	    WHERE t1.enabled='Y' AND t2.id IS NULL 
        };

	logwriter( "$sql", 5 );
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
	while ( my ( $pluginid )= $sth_sel->fetchrow_array ) { 
    
	    $sql = qq{ UPDATE vuln_nessus_plugins SET enabled='N', deleted='$now' WHERE id='$pluginid' };
            safe_db_write( $sql, 5 );

	    $sql = qq{ DELETE FROM vuln_nessus_settings_plugins WHERE id='$pluginid' };
            safe_db_write( $sql, 5 );

        }
    }

    my $time_run = time() - $time_start;
    print "\n";
    logwriter( "FINISH - UPDATE NESSUS_PLUGINS [ Process took $time_run seconds ]\n", 4 );

}
sub update_openvas_plugins {

    logwriter( "BEGIN  - UPDATE OPENVAS_PLUGINS", 4 );
    my $time_start = time();

    my ( $sth_sel, $sth_sel2, $sth_sel3, $sth_ins, $sql );
    my $now = genScanTime();

    #USE TO MAKE SURE PLUGINS TABLE IS NOT EMPTY (OTHERWISE LATER CODE WOULD FLAG ALL NESSUS_PLUGINS DELETED )
    my $plugin_count = 0;

    #ANOTHER REWRITE TO CLEANUP UNNECESSARY DB HEAVY LIFTING
    #FIST LESTS PROCESS ALL RECORDS PER THE PLUGINS TABLE TO SEE
    #	1.  ALL PLUGINS THAT NEED ADDED
    #	2.  ALL PLUGINS THAT EXIST TO BE UPDATED

    #THEN NEED A FOLLOWUP RUN AGAINST ALL PLUGINS THAT NEED FLAGGED DELETED ( IF ANY )

    $sql = qq{ SELECT t1.id, t1.oid, t1.name, t3.id, t4.id, t1.copyright, t1.summary, t1.description,
	t1.version, t2.id, t2.version, t2.custom_risk, t1.cve_id, t1.bugtraq_id, t1.xref, t1.cvss_base
            FROM vuln_plugins t1
	    LEFT JOIN vuln_nessus_plugins t2 on t1.id=t2.id
            LEFT JOIN vuln_nessus_family t3 ON t1.family = t3.name
	    LEFT JOIN vuln_nessus_category t4 ON t1.category = t4.name
    };

    logwriter( "$sql", 5 );
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;
    while ( my ( $pid, $oid, $pname, $pfamily, $pcategory, $pcopyright, $psummary, $pdescription, $pversion,
	$pluginid, $pluginversion, $plugin_crisk, $pcve_id, $p_bug, $p_xref, $cvss_base )= $sth_sel->fetchrow_array ) {  
    
    if ($pname ne "" && $pfamily ne "" && $pcategory ne "") {
        
        #$pcve_id =~ s/(\d+\-\d+)/CVE-$1/g  if ( ($pcve_id !~ /^CVE/) && ($pcve_id !~ /^CAN/) );
        
        $pcve_id =~ s/CAN\-CVE/CVE/g;
        $pcve_id =~ s/CAN\-(\d+)/CVE-$1/g;
        
        my @pcve_ids = split(/,/, $pcve_id);
        my @pcve_tmp=();
        foreach (@pcve_ids){
            s/^ *| *$//g;
            s/(\d+\-\d+)/CVE-$1/ if ($_ !~ /^CVE/);
            push @pcve_tmp,$_;
        }
        $pcve_id = join(", ", @pcve_tmp);
        
        $pcve_id = ""  if ( $pcve_id !~ /-/);
        $pcve_id = ""  if ($pcve_id =~ /NOCVE/); 
        $p_bug = ""  if ($p_bug =~ /NOBID/); 
        #print "pid: $pid\n";
        #print "name: $pname\n";
        
    	$pname =~ s/'/\\'/g;
    	$psummary =~ s/'/\\'/g;
            $pdescription =~ s/\\/\\\\/g;
    	$pdescription =~ s/'/\\'/g;

        my $risk=7;
        
        if ( $cvss_base eq "" ) {
            $risk = 7;
        }
        elsif (int($cvss_base) >= 8 ) {
            $risk = 1;
        }
        elsif( int($cvss_base) >= 5 && int($cvss_base) < 8 ) {
            $risk = 2;
        }
        elsif( int($cvss_base) >= 2 && int($cvss_base) < 5 ) {
            $risk = 3;
        }
        elsif( int($cvss_base) >= 0 && int($cvss_base) < 2 ) {
            $risk = 6;
        }
        #logwriter( " $oid [$cvss_base] $risk .", 4 );
    	if ( !defined( $pluginid ) || $pluginid eq "" ) {
    	    $plugins{$pid}{'do'} = "insert";
    	    $sql = qq{ INSERT INTO vuln_nessus_plugins ( id, oid, name, copyright, summary, description, cve_id, bugtraq_id, 
    		xref, enabled, version, created, modified, deleted, category, family, risk, custom_risk ) VALUES
    		( '$pid', '$oid', '$pname', '$pcopyright', '$psummary', '$pdescription', '$pcve_id', '$p_bug', '$p_xref',
                      'Y','$pversion', '$now', null, null, '$pcategory', '$pfamily', '$risk', NULL ); };
                safe_db_write( $sql, 4 );
            #print "[$sql]\n"; 

    	} else {
    	    $plugins{$pid}{'do'} = "update";
                if ($pluginversion ne $pversion) {
                    $sql = qq{ UPDATE vuln_nessus_plugins SET enabled='Y', version='$pversion', risk='$risk', modified='$now', 
                        description='$pdescription', cve_id='$pcve_id', bugtraq_id='$p_bug'
    		    WHERE id='$pluginid' };
                    safe_db_write( $sql, 5 );

                }
    	}
    	$plugin_count +=1;
        }
    }

    #UPDATE RISK WITH CUSTOM VALUE AS NEEDED
    $sql = qq{ UPDATE vuln_nessus_plugins SET risk=custom_risk WHERE custom_risk IS NOT NULL AND custom_risk > 0 }; 
    safe_db_write( $sql, 3 );

    #UPDATE DELETED PLUGINS
    if ( $plugin_count > 25000 ) {	    #MAKE SURE SOMETHING REALLY NEEDS DELETED
        $sql = qq{ SELECT t1.id FROM vuln_nessus_plugins t1
	    LEFT JOIN vuln_plugins t2 on t1.id=t2.id
	    WHERE t1.enabled='Y' AND t2.id IS NULL 
        };

	logwriter( "$sql", 5 );
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
	while ( my ( $pluginid )= $sth_sel->fetchrow_array ) { 
    
	    $sql = qq{ UPDATE vuln_nessus_plugins SET enabled='N', deleted='$now' WHERE id='$pluginid' };
            safe_db_write( $sql, 5 );

	    $sql = qq{ DELETE FROM vuln_nessus_settings_plugins WHERE id='$pluginid' };
            safe_db_write( $sql, 5 );

        }
    }

    my $time_run = time() - $time_start;
    print "\n";
    logwriter( "FINISH - UPDATE OPENVAS_PLUGINS [ Process took $time_run seconds ]\n", 4 );

}

sub update_settings_plugins {

    my $custom = shift;
    
    my $profiles_filter1 = ($custom == TRUE) ? 'WHERE id NOT IN (1, 2, 3)' : '';
    my $profiles_filter2 = ($custom == TRUE) ? 'WHERE sid NOT IN (1, 2, 3)' : '';
    

    logwriter( "BEGIN  - UPDATE SETTINGS_PLUGINS", 4 );
    my $time_start = time();

    my ( $sth_sel, $sth_sel2, $sth_sel3, $sth_ins, $sql );
    my $now = genScanTime();

    my %autoenable;
    my %autofam;
    my %autocat;
    my %settings;
    my %msids;
    my $profile_count = 0;

    #CREATE ARRAY OF AUTOENABLE PER PROFILES
    $sql = qq{ SELECT id, autoenable FROM vuln_nessus_settings $profiles_filter1 };
    logwriter( "$sql", 5 );
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;
    while ( my ($sid, $value)=$sth_sel->fetchrow_array) { 
        $autoenable->{$sid} = $value;
        #print "sid=$sid\tvalue=$value\tautocat=" . $autoenable->{$sid} ."\n";
        $profile_count = $profile_count + 1;
    }

    #CREATE AUTOENABLE CATEGORY ARRAY
    $sql = qq{ select sid, cid, status from vuln_nessus_settings_category $profiles_filter2 };
    logwriter( "$sql", 5 );
    $sth_sel=$dbh->prepare( $sql );
    $sth_sel->execute;
    while ( my ($sid, $cid, $status) = $sth_sel->fetchrow_array ) {
       $autocat{$sid}->{$cid} = $status;
       #print "sid=$sid\tcid=$cid\tstatus=$status\tautocat=" . $autocat{$sid}{$cid} ."\n";
    }

    #CREATE AUTOENABLE FAMILY ARRAY
    $sql = qq{ select sid, fid, status from vuln_nessus_settings_family $profiles_filter2 };
    logwriter( "$sql", 5 );
    $sth_sel=$dbh->prepare( $sql );
    $sth_sel->execute;
    while ( my ($sid, $fid, $status) = $sth_sel->fetchrow_array ) {
       $autofam{$sid}->{$fid} = $status;
       #print "sid=$sid\tfid=$fid\tstatus=$status\tautofam=" . $autofam{$sid}{$fid} ."\n";
    }

    #POPULATE A SETTING HASH ARRAY TO OFFLOAD HEAVY LIFTING FROM THE DB.
    $sql = qq{ SELECT id, sid, enabled, category, family FROM vuln_nessus_settings_plugins $profiles_filter2 };
    logwriter( "$sql", 5 );
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;

    while ( my ($pid, $sid, $enabled, $pcategory, $pfamily ) = $sth_sel->fetchrow_array ) {
	$settings{$pid}->{$sid}->{'enabled'} = $enabled;
	$settings{$pid}->{$sid}->{'category'} = $pcategory;
	$settings{$pid}->{$sid}->{'family'} = $pfamily;
        $settings{$pid}->{$sid}->{'count'} += 1;
    }

    $sql = qq{ SELECT id, category, family FROM vuln_nessus_plugins WHERE enabled='Y' };
    logwriter( "$sql", 5 );
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;

    while ( my ($pid, $pcategory, $pfamily ) = $sth_sel->fetchrow_array ) {
	foreach my $sid (sort(keys(%{$autoenable}))) {
            my $task = "";
            my $cfStatus = "-1";
	    my $statusvalue = "";
            $sql2 = "";
	    #THrew this in there to handle issue where it may have been populated ""
            $statusvalue = $settings{$pid}{$sid}{'enabled'};

	    if ( $autoenable->{$sid} eq "C" ) {
                $cfStatus = $autocat{$sid}{$pcategory};
	    } elsif ( $autoenable->{$sid} eq "F" ) {
	        $cfStatus = $autofam{$sid}{$pfamily};
            }

            if ( $cfStatus eq "1" ) { $statusvalue = "Y"; } #SET THEM ENABLED ( ALL ONLY )
            if ( $cfStatus eq "3" ) { $statusvalue = "N"; } #SET THEM ENABLED ( ALL ONLY )

            if ( !defined( $settings{$pid}{$sid}{'enabled'} ) || $settings{$pid}{$sid}{'enabled'} eq "" ) {
                if ( $cfStatus eq "2" ) { $statusvalue = "Y"; } #SET THEM ENABLED ( NEW )
                if ( $cfStatus eq "4" ) { $statusvalue = "N"; } #SET THEM DISABLED ( NEW )
                if ( $cfStatus eq "5" ) { $statusvalue = "N"; } #SET THEM DISABLED ( NO AUTOENABLE FOR NEW )
                $task="create";
            }

            if ( $task eq "" && ( $cfStatus eq "1" || $cfStatus eq "4" ) && $settings{$pid}{$sid}{'enabled'} ne $statusvalue ) {
                $task="update";
            }

            if ( defined($settings{$pid}{$sid}{'count'}) && int($settings{$pid}{$sid}{'count'}) > 1 ) {
                my $scount = int($settings{$pid}{$sid}{'count'});
                my $limit = $scount - 1;
                print "something is wrong: check sid=$sid\tcount=$scount\n";
                print "removing duplicates:\n";
                $sql2 = qq{ DELETE FROM vuln_nessus_settings_plugins WHERE id='$pid' AND sid='$sid' };
                safe_db_write( $sql2, 3 );
            }
            
            
            if ( $task eq "create" ) {
                $sql2 = qq{ INSERT INTO vuln_nessus_settings_plugins (id, sid, enabled, category, family ) VALUES 
                    ('$pid', '$sid', '$statusvalue', '$pcategory', '$pfamily' ); };
                safe_db_write( $sql2, 4 );
                 
                if ($nessus =~ /omp\s*$/) { $msids{$sid}++; }
                
            }
            elsif ( $task eq "update" && !in_array(\@disabled_plugins,$pid)) {
                $sql2 = qq{ UPDATE vuln_nessus_settings_plugins SET enabled='$statusvalue' 
                        WHERE id='$pid' AND sid='$sid' };
                
                safe_db_write( $sql2, 4 );
                
                if ($nessus =~ /omp\s*$/) { $msids{$sid}++; }
            #} else {
            #    logwriter( "no update for record pid=$pid\tsid=$sid\n", 2);
            }
            #print "sid=$sid\tpid=$pid\tcfstatus=$cfStatus\tvalue=$statusvalue\ttask=$task\n";
            #print "sql2=$sql2\n";
	}
    }

    if ($nessus =~ /omp\s*$/) {
        my @sids_to_modify = keys %msids;
        
        if($#sids_to_modify!= -1) {
            
            # update configs openvas-manager configs
            
            my $sids = join("', '",keys %msids);
            
            my $sql_sids = qq{ SELECT id, name, owner FROM vuln_nessus_settings WHERE id IN ('$sids') };
            
            my $cmd = "";

            my $sth_sids=$dbh->prepare( $sql_sids );
            $sth_sids->execute;
            while (my ($psid, $pname, $powner) =$sth_sids->fetchrow_array ) {
                # Special case, disable plugins 11219(synscan), 10335(tcp_scanner), 80009(portscan_strobe), 80001(pnscan), 80002(portbunny) for all profiles
                #$sql = qq{ update vuln_nessus_settings_plugins set enabled='N' where (id=11219 or id=10335 or id=80009 or id=80001 or id=80002) and sid=$psid };
                #$sth_sel = $dbh->prepare($sql);
                #$sth_sel->execute;
                #$sth_sel->finish();
                # end disabled
                
                # Special case, enable plugins 14259(Nmap - NASL wrapper), 100315(Ping Host)
                #$sql = qq{ update vuln_nessus_settings_plugins set enabled='Y' where (id=14259 or id=100315) and sid=$psid };
                #$sth_sel = $dbh->prepare($sql);
                #$sth_sel->execute;
                #$sth_sel->finish();
                # end enable
                
                $openvas_manager_common_conf_main = $openvas_manager_common;

                foreach my $sensor_data (@sensors) {

                    my @sd = split(/\|/, $sensor_data);
                    
                    $openvas_manager_common = "$binary_location -h ".$sd[0]." -p ".$sd[1]." -u '".$sd[2]."' -w '".$sd[3]."' -iX"; # other sensors
            
                    my $id_config = get_config_id($pname, $powner);
                    
                    if($id_config ne "") {
                    
                        # Disable all families
                        my @openvas_manager_families = get_openvas_manager_families();
                        
                        foreach my $om_family(@openvas_manager_families) {
                            my $file_omp_command = "";   

                            $file_omp_command = "/usr/share/ossim/www/tmp/omp_command_$$".int(rand(1000000)).".xml";

                            system("echo \"<modify_config config_id='$id_config'><nvt_selection><family>$om_family</family></nvt_selection></modify_config>\" > '$file_omp_command'");
                                
                            $cmd = "$openvas_manager_common - < ".$file_omp_command." > $xml_output 2>&1";
                                
                            $imp = system ( $cmd );
                            
                            if(defined($debug) && $debug == 0 ) { unlink $file_omp_command if -e $file_omp_command; }
                            
                            $xml = eval {XMLin($xml_output, keyattr => [])};
                        
                            if ($@ ne "") {  logwriter( "Cant' read XML $xml_output", 2); }
                            if ($xml->{'status'} !~ /20\d/) {
                                my $status = $xml->{'status'};
                                my $status_text = $xml->{'status_text'};
                                 logwriter( "Error: status = $status, status_text = '$status_text' ($xml_output)", 2);
                            }
                        
                            if ( $imp != 0 ) { logwriter( "updateplugins: Cant' disable family '$om_family' for config '$name'", 2 ); }
                            
                        }
                    
                        logwriter("Config $pname for $powner will be updated...",4);
                        my $sql = qq{ SELECT f.name, p.oid
                                                FROM vuln_nessus_settings_plugins AS sp
                                                LEFT JOIN vuln_nessus_plugins AS p ON sp.id = p.id
                                                LEFT JOIN vuln_nessus_family AS f ON sp.family = f.id
                                                WHERE sp.enabled =  'Y'
                                                AND sp.sid =  '$psid' };
                        #logwriter($sql,4);

                        my %familyHash;
                        my $sth_self=$dbh->prepare( $sql );
                        $sth_self->execute;

                        while (my ($family, $oid) =$sth_self->fetchrow_array ) {
                            $familyHash{$family}{$oid}++;
                        }

                        $sth_self->finish(); 
                    
                        # update config
                        foreach my $family ( keys %familyHash ) {
                            my $file_omp_command = "";
                            
                            $file_omp_command = "/usr/share/ossim/www/tmp/omp_command_$$".int(rand(1000000)).".xml";
                            open (OF,">$file_omp_command");
                            
                            print OF "<modify_config config_id='$id_config'><nvt_selection><family>$family</family>\n";
                                
                            $i = 0;
                            foreach my $oid ( keys %{$familyHash{$family}} ) {
                                print OF "<nvt oid='$oid'/>\n";
                                $i++;
                            }
                            print OF "</nvt_selection></modify_config>";
                                
                            close(OF);
                                
                            $cmd = " - < ".$file_omp_command." > $xml_output 2>&1";
                                
                            if ($vervose eq '1')
                            {
                                logwriter("Updating family '$family'...", 4);
                                logwriter("$i plugins", 4);
                            }
                            
                            $imp = system ( $openvas_manager_common.$cmd );
                            
                            if(defined($debug) && $debug == 0 ) { unlink $file_omp_command if -e $file_omp_command; }
                    
                            $xml = eval {XMLin($xml_output, keyattr => [])};
                    
                            if ($@ ne "") {  logwriter( "Cant' read XML $xml_output", 2); }
                            if ($xml->{'status'} !~ /20\d/) {
                                my $status = $xml->{'status'};
                                my $status_text = $xml->{'status_text'};
                                 logwriter( "Error: status = $status, status_text = '$status_text' ($xml_output)", 2);
                            }
                    
                            if ( $imp != 0 and $check_command) { logwriter( "updateplugins: Cant' modify Config $name", 2 ); }
                          
                        }
                    }
                }
                
                $openvas_manager_common = $openvas_manager_common_conf_main;
            }
            $sth_sids->finish();
        
        }
    }
    my $time_run = time() - $time_start;
    logwriter( "FINISH - UPDATE SETTINGS_PLUGINS [ Process took $time_run seconds ]\n", 4 );
}

sub update_preferences {

    logwriter( "BEGIN  - UPDATE NESSUS_PREFERENCES", 4 );
    my $time_start = time();

    my ( $sql, $sth_sel, $sth_upd );
    
    my @items = ();
    my @preferences = ();

    my $now = genScanTime();

    # Create a table the first time we run this program if needed
    $sql = qq{show tables like "vuln_nessus_preferences_defaults"};
    logwriter( $sql, 4 );
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;

    $foo=$sth_sel->fetchrow_array;
    if (!$foo) {
        $sql = qq{ 
CREATE TABLE `vuln_nessus_preferences_defaults` (
  `nessus_id` varchar(255) NOT NULL default '',
  `nessusgroup` varchar(255) default NULL,
  `type` varchar(255) default NULL,
  `field` varchar(255) default NULL,
  `value` varchar(255) default NULL,
  `category` varchar(255) default NULL,
  `flag` char(1) default NULL,
  PRIMARY KEY  (`nessus_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

};
        safe_db_write( $sql, 3 );
    }

    $sql = qq{ update vuln_nessus_preferences_defaults set flag=null };
    safe_db_write( $sql, 5 );

    my ($cmd);
    my ($f0, $f1, $f2, $f3, $f4, $rhs, $rhs2);

    logwriter( "updateprefs: Getting plugin preferences", 4 );
    
    if ($nessus =~ /omp\s*$/) {
        my $file_omp_command = "";
        
        $file_omp_command = "/usr/share/ossim/www/tmp/omp_command_$$".int(rand(1000000)).".xml";
        system("echo \"<get_preferences/>\" > '$file_omp_command'");
        
        $cmd = "$openvas_manager_common - < ".$file_omp_command." > $xml_output 2>&1";
        
        #logwriter( "$cmd", 4 );
        
        my $imp = system ( $cmd );
        
        if(defined($debug) && $debug == 0 ) { unlink $file_omp_command if -e $file_omp_command; }

        if ( $imp != 0 ) { logwriter( "updateplugins: Failed Get Preferences", 2 ); }
        
        my $xml = eval {XMLin($xml_output, keyattr => [])};
        
        
        
        if ($@ ne "") {  logwriter( "Cant' read XML $xml_output", 2); }
        if ($xml->{'status'} !~ /20\d/) {
            my $status = $xml->{'status'};
            my $status_text = $xml->{'status_text'};
            logwriter( "Error: status = $status, status_text = '$status_text' ($xml_output)", 2);
        }
        
        if (ref($xml->{'preference'}) eq 'ARRAY') {
            @items = @{$xml->{'preference'}};
        } else {
            push(@items,$xml->{'preference'});
        }
        
        foreach my $preference (@items) {
            #print Dumper($preference);
            if (ref($preference->{'value'}) eq 'HASH') {
                $preference->{'value'} = ""; 
            }
            push(@preferences, $preference->{'name'}." = ".$preference->{'value'});
            #print "\n[".$preference->{'name'}." = ".$preference->{'value'}."]";
        }
        foreach (@preferences) {
            if (/\]:/) {
                # PLUGINS_PREFS
                $f5 = "PLUGINS_PREFS";
                ($f1,$rhs) = split(/\[/);
                ($f2,$rhs2) = split(/\]:/,$rhs);
                ($f3,$f4) = split(/=/, $rhs2);
                 $f3 =~ s/\s+$//;    # Remove trailing whitespace 
                $f4 =~ s/^ //;        # Remove leading whitespace
                $f4 =~ s/\n$//;        # Remove trailing newline

                $f0 = $f1."[".$f2."]:".$f3;
                $f2 =~ s/entry/T/;        # Text box
                $f2 =~ s/radio/R/;        # Radio button
                $f2 =~ s/checkbox/C/;        # Checkbox
                $f2 =~ s/password/P/;        # Password
                $f2 =~ s/file/T/;        # File

            } else {
                # SERVER_PREFS
                $f5 = "SERVER_PREFS";

                $f1 = "ServerPrefs";
                ($f3,$f4) = split(/=/);
                $f3 =~ s/\s+$//;    # Remove trailing whitespace
                $f4 =~ s/\n$//;        # Remove trailing newline
                $f4 =~ s/^ //;        # Remove leading whitespace
                $f2 = "T";
                $f0 = $f3;
            }

            # Does the current record exist? If not
            $sql = qq{ SELECT count(*) from vuln_nessus_preferences_defaults WHERE nessus_id = "$f0" };
            logwriter( $cmd, 5 );
            $sth_sel = $dbh->prepare( $sql );
            $sth_sel->execute;

            $foo=$sth_sel->fetchrow_array;
            if ($foo == 0) {
                $sql = qq{insert into vuln_nessus_preferences_defaults (nessus_id, nessusgroup, type, field, 
                    value, category,flag) values ("$f0", "$f1", "$f2", "$f3", "$f4", "$f5","T" );};
            } else {
                $sql = qq{UPDATE vuln_nessus_preferences_defaults SET nessusgroup="$f1", type="$f2", field="$f3",
                    value="$f4", category="$f5", flag="T" WHERE nessus_id = "$f0" };
            }
            safe_db_write( $sql, 5 );

        }
    }
    else {
        $cmd = qq{$binary_location -qxP $CONFIG{'NESSUSHOST'} $CONFIG{'NESSUSPORT'} '$CONFIG{'NESSUSUSER'}' '$CONFIG{'NESSUSPASSWORD'}'};
        logwriter( $cmd, 5 );
        open(PROC, "$cmd |") or die "failed to fork :$!\n";
        while (<PROC>){
            if (/\]:/) {
                # PLUGINS_PREFS
                $f5 = "PLUGINS_PREFS";
                ($f1,$rhs) = split(/\[/);
                ($f2,$rhs2) = split(/\]:/,$rhs);
                ($f3,$f4) = split(/=/, $rhs2);
                 $f3 =~ s/\s+$//;    # Remove trailing whitespace 
                $f4 =~ s/^ //;        # Remove leading whitespace
                $f4 =~ s/\n$//;        # Remove trailing newline

                $f0 = $f1."[".$f2."]:".$f3;
                $f2 =~ s/entry/T/;        # Text box
                $f2 =~ s/radio/R/;        # Radio button
                $f2 =~ s/checkbox/C/;        # Checkbox
                $f2 =~ s/password/P/;        # Password
                $f2 =~ s/file/T/;        # File

            } else {
                # SERVER_PREFS
                $f5 = "SERVER_PREFS";

                $f1 = "ServerPrefs";
                ($f3,$f4) = split(/=/);
                $f3 =~ s/\s+$//;    # Remove trailing whitespace
                $f4 =~ s/\n$//;        # Remove trailing newline
                $f4 =~ s/^ //;        # Remove leading whitespace
                $f2 = "T";
                $f0 = $f3;
            }

            # Does the current record exist? If not
            $sql = qq{ SELECT count(*) from vuln_nessus_preferences_defaults WHERE nessus_id = "$f0" };
            logwriter( $cmd, 5 );
            $sth_sel = $dbh->prepare( $sql );
            $sth_sel->execute;

            $foo=$sth_sel->fetchrow_array;
            if ($foo == 0) {
                $sql = qq{insert into vuln_nessus_preferences_defaults (nessus_id, nessusgroup, type, field, 
                    value, category,flag) values ("$f0", "$f1", "$f2", "$f3", "$f4", "$f5","T" );};
            } else {
                $sql = qq{UPDATE vuln_nessus_preferences_defaults SET nessusgroup="$f1", type="$f2", field="$f3",
                    value="$f4", category="$f5", flag="T" WHERE nessus_id = "$f0" };
            }
            safe_db_write( $sql, 5 );
        }
    }
    $sql = "UPDATE vuln_nessus_preferences_defaults set type = 'C' WHERE nessusgroup = 'ServerPrefs' and value in ('yes', 'no')";
    safe_db_write( $sql, 5 );

    $sql = "DELETE FROM vuln_nessus_preferences_defaults where flag is null";
    safe_db_write( $sql, 5 );

    my $time_run = time() - $time_start;
    print "\n";
    logwriter( "FINISH - UPDATE NESSUS_PREFERENCES [ Process took $time_run seconds ]\n", 4 );

}

sub genScanTime {
    # VER: 1.0 MODIFIED: 3/29/07 13:03
    my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
    $year+=1900;
    $mon++;
    return sprintf("%04d%02d%02d%02d%02d%02d",$year, $mon, $mday, $hour, $min, $sec);
}

sub is_number{
    # VER: 1.0 MODIFIED: 3/29/07 13:03
    my($n)=@_;

    if ( $n ) { 
        return ($n=~/^\d+$/);
    } else {
        return;
    }
}

#read settings from db (overrides settings in file)
sub load_db_configs {
    # VER: 1.0 MODIFIED: 4/1/08 12:39
    my ($sth_sel);

    my $sql = qq{ SELECT settingName, settingValue FROM vuln_settings };
    logwriter( $sql, 5 );
    $sth_sel=$dbh->prepare( $sql );
    $sth_sel->execute;

    while ( my ( $name,$value) = $sth_sel->fetchrow_array ) {
       if ( $name eq "mailSignature" ) { $value =~ s/&lt;br&gt;/\n/g; }
       if ( $name ne "") { $CONFIG{$name}=$value; }
    }

    $sth_sel->finish;
    return;
}

sub load_configs {
    # VER: 1.1 MODIFIED: 4/12/07 9:17
    my ( $configfile ) = @_;

    my $noconfig=0;
    open(CONF,"<$configfile") || $noconfig++;
    my @CONFILE=<CONF>;
    close(CONF);
    if ($noconfig) { print localtime(time)." port_scan: No config.txt file found.\n"; }
    foreach my $line (@CONFILE) {
        $line =~ s/\#.*$//;
        chomp($line);
        $line =~ s/\s+$//;
        $line =~ s/^\s+//;
        if ($line eq "") { next; }
        my @temp=split(/=/,$line,2);
        if ($temp[0] ne "") { $CONFIG{$temp[0]}=$temp[1]; }
    }
    return;
}

sub safe_db_write {
    # VER: 1.0 MODIFIED: 1/16/08 7:36
    my ( $sql_insert, $specified_level ) = @_;

    #logwriter( $sql_insert, $specified_level );
    logwriter( ".", $specified_level );
    
    eval {
        $dbh->do( $sql_insert );
    };
    warn "FAILED - $sql_insert\n" . $dbh->errstr . "\n\n" if ($@);

    if ( $@ ) { return 0; }

}

sub safe_db_query {
    # VER: 1.0 MODIFIED: 1/16/08 7:36
    my ( $sql, $specified_level ) = @_;

    logwriter( $sql, $specified_level );

    my ( $sth_sel );

    my @data = ();

    eval {
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;

        @data= $sth_sel->fetchrow_array;
    };

    $sth_sel->finish;

    return @data;

}

sub trim {
    my $string = @_;
    $string =~ s/^\s+//;
    $string =~ s/\s+$//;
    return $string;
}


sub check_dbOK {
    # VER: 1.1 MODIFIED: 11/26/07 10:08
    my $sql = "SELECT count( hostname ) FROM vuln_nessus_servers WHERE 1";

    eval {
            $dbh->do( $sql );
    };

    warn "FAILED - Connection Test\n" . $dbh->errstr . "\n\n" if ($@);

    if ( $@ ) { return 0; }

    return 1;

}

sub logwriter {
   # VER: 1.0 MODIFIED: 4/21/08 20:19
    my ( $message, $specified_level ) = @_;

    if ( !defined($specified_level) || $specified_level eq "" ) { $specified_level = 5; }
    
    $specified_level = int($specified_level);

    my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
    $year+=1900;
    $mon++;
    my $now = sprintf("%04d-%02d-%02d %02d:%02d:%02d",$year, $mon, $mday, $hour, $min, $sec);
    
    if($message ne "."){
        $message = "$now  ".$loginfo{$specified_level}." $message";

        if ( $debug || int($log_level) ge int($specified_level) )  { print "\n".$message; }
    
    }
    # else {  print ".";  }

}

sub conn_db {
    # VER: 2.0 MODIFIED: 9/26/08 9:47

    if ( !defined($CONFIG{'DATABASEPORT'}) || $CONFIG{'DATABASEPORT'} eq "" ) { $CONFIG{'DATABASEPORT'} = "3306"; }
    if ( !defined($CONFIG{'DATABASESOCKET'}) || $CONFIG{'DATABASESOCKET'} eq "" ) { $CONFIG{'DATABASESOCKET'} = "/var/lib/mysql/mysql.sock"; }
    $dbh = DBI->connect( "$CONFIG{'DATABASEDSN'}:$CONFIG{'DATABASENAME'};host=$CONFIG{'DATABASEHOST'};"
        ."port=$CONFIG{'DATABASEPORT'};socket=$CONFIG{'DATABASESOCKET'}",
        "$CONFIG{'DATABASEUSER'}","$CONFIG{'DATABASEPASSWORD'}", {
        PrintError => 0,
        RaiseError => 1,
        AutoCommit => 1 } ) or die("Failed to connect : $DBI::errstr\n");
    return $dbh;
}

sub disconn_db {
    # VER: 1.0 MODIFIED: 3/29/07 13:03
    my ( $dbh ) = @_;
    $dbh->disconnect or die("Failed to disconnect : $DBI::errstr\n");
}

sub generate_profiles {
    my (%profiles) = %{$_[0]};

    foreach my $nd (keys %profiles) {
        disconn_db($dbh);
        $dbh = conn_db();
        my @tmp = split(/\|/,$nd);
        my @values = split(/\|/,$profiles{$nd});

        $sql = qq{SELECT id from vuln_nessus_settings where name like '$tmp[0]' and (owner like '$tmp[3]' or owner like 'admin' )};
        
        $sth_sel = $dbh->prepare($sql);
        $sth_sel->execute();
        my ($id) = $sth_sel->fetchrow_array;
        $sth_sel->finish;

        if (!defined($id) || $id eq "") {
            if($tmp[0] eq 'Default' && $tmp[3] eq '0' && $nessus =~ /omp\s*$/) { # Default profile for user 0 is the "Full and fast" in OpenVAS manager
                # get config from OpenVas manager
                $tmp[2] = 'F';
                my $config_families = get_config_families('Full and fast');
                
                @values = ();
                @values = split(/\|/,$config_families);
            }
            if($tmp[0] eq 'Deep' && $tmp[3] eq '0' && $nessus =~ /omp\s*$/) { # Deep profile for user 0 is the "Full and very deep" in OpenVAS manager
                # get config from OpenVas manager
                $tmp[2] = 'F';
                my $config_families = get_config_families('Full and very deep');
                
                @values = ();
                @values = split(/\|/,$config_families);
            }
            if($tmp[0] eq 'Ultimate' && $tmp[3] eq '0' && $nessus =~ /omp\s*$/) { # Ultimate profile for user 0 is the "Full and very deep ultimate" in OpenVAS manager
                # get config from OpenVas manager
                $tmp[2] = 'F';
                my $config_families = get_config_families('Full and very deep ultimate');
                
                @values = ();
                @values = split(/\|/,$config_families);
            }

            print "\nCreating $tmp[0] profile...\n";
            $sql = qq{INSERT INTO vuln_nessus_settings (name, description, autoenable, owner, auto_cat_status, auto_fam_status)
                    values('$tmp[0]', '$tmp[1]', '$tmp[2]', '$tmp[3]', '$tmp[4]', '$tmp[5]')};

            $sth_sel = $dbh->prepare($sql);
            $sth_sel->execute;
            $sth_sel->finish;
            
            $sql = qq{SELECT LAST_INSERT_ID() as lastid};
            $sth_sel = $dbh->prepare($sql);
            $sth_sel->execute;
            my ($idprofile) = $sth_sel->fetchrow_array;
            $sth_sel->finish;
            
            # category
            print "Filling categories...";
            
            $sql = qq{ select id, name from vuln_nessus_category };
            $sth_self=$dbh->prepare( $sql );
            $sth_self->execute;
            while (my ($idcategory, $namecategory) =$sth_self->fetchrow_array ) {
                $namecategory =~ s/\t+//g;
                print ".";
                if($tmp[2] eq "F" || ($tmp[2] eq "C" && !in_array(\@values,$namecategory))) { #category off
                    $sql = qq{ insert into vuln_nessus_settings_category (sid, cid, status) values ($idprofile, $idcategory, 4)};
                }
                else { # category on
                    $sql = qq{ insert into vuln_nessus_settings_category (sid, cid, status) values ($idprofile, $idcategory, 1)};
                }
                $sth_sel = $dbh->prepare($sql);
                $sth_sel->execute;
                $sth_sel->finish();
            }
            $sth_self->finish();
            print " Done\n";
            
            # family
            print "Filling families...";
            
            $sql = qq{ select id, name from vuln_nessus_family };
            $sth_self=$dbh->prepare( $sql );
            $sth_self->execute;
            while (my ($idfamily, $namefamily) =$sth_self->fetchrow_array ) {
                $namefamily =~ s/\t+//g;
                print ".";
                if($tmp[2] eq "C" || ($tmp[2] eq "F" && !in_array(\@values,$namefamily))) { #family off
                    $sql = qq{ insert into vuln_nessus_settings_family (sid, fid, status) values ($idprofile, $idfamily, 4)};
                }
                else { # family on
                    $sql = qq{ insert into vuln_nessus_settings_family (sid, fid, status) values ($idprofile, $idfamily, 1)};
                }
                $sth_sel = $dbh->prepare($sql);
                $sth_sel->execute;
                $sth_sel->finish();
            }
            $sth_self->finish();
            print " Done\n";
            
            # plugins
            print "Filling plugins...";
            $sql = qq{ select id, category, family from vuln_nessus_plugins };
            $sth_self=$dbh->prepare( $sql );
            $sth_self->execute;
            while (my ($idplugin, $idcategory, $idfamily) =$sth_self->fetchrow_array ) {
                #print ".";
                $sqlc = qq{SELECT status as statusc from vuln_nessus_settings_category where sid='$idprofile' and cid = '$idcategory' };
                $sth_sc = $dbh->prepare($sqlc);
                $sth_sc->execute;
                my ($statusc) = $sth_sc->fetchrow_array;
                $sth_sc->finish;

                $sqlf = qq{SELECT status as statusf from vuln_nessus_settings_family where sid='$idprofile' and fid = '$idfamily' };
                $sth_sf = $dbh->prepare($sqlf);
                $sth_sf->execute;
                my ($statusf) = $sth_sf->fetchrow_array;
                $sth_sf->finish;

                if($statusc eq "1" || $statusf eq "1") { #plugin on
                    $sql = qq{ insert into vuln_nessus_settings_plugins (id, sid, enabled, category, family) 
                               values ($idplugin, $idprofile, 'Y', $idcategory, $idfamily) };
                }
                else { # plugin off
                    $sql = qq{ insert into vuln_nessus_settings_plugins (id, sid, enabled, category, family) 
                               values ($idplugin, $idprofile, 'N', $idcategory, $idfamily) };
                }
                $sth_sel = $dbh->prepare($sql);
                $sth_sel->execute;
                $sth_sel->finish();
            }
            
            my $dplugins = join("', '", @disabled_plugins);
            # Special case, disable plugins for all profiles
            $sql = qq{ UPDATE vuln_nessus_settings_plugins SET enabled='N'
                        WHERE id IN ('$dplugins') AND sid=$idprofile };
            $sth_sel = $dbh->prepare($sql);
            $sth_sel->execute;
            $sth_sel->finish();
            # end disabled
            
            $sth_self->finish();
            print " Done\n";
            
            if ($nessus !~ /omp\s*$/) {
                # preferences
                print "Filling preferences...\n";
                
                # special case Ping Host[checkbox]
                $ping = 0;
                $sql = qq{ select id, nessus_id, value, category, type from vuln_nessus_preferences };
                $sth_self=$dbh->prepare( $sql );
                $sth_self->execute;
                while (my ($idp, $nessus_idp, $valuep, $categoryp, $typep) =$sth_self->fetchrow_array ) {
                    print ".";
                    if ($nessus_idp =~ /Ping Host.*Mark unrechable Hosts as dead/) {
                        $valuep = "yes";
                        $ping = 1;
                    }

                    $nessus_idp = quotemeta $nessus_idp;
                    $sql = qq{ insert into vuln_nessus_settings_preferences (sid, id, nessus_id, value, category, type) 
                            values ('$idprofile', '$idp', '$nessus_idp', '$valuep', '$categoryp', '$typep') };
                    $sth_sel = $dbh->prepare($sql);
                    $sth_sel->execute;
                    $sth_sel->finish();
                }
                $sth_self->finish();
                if (!$ping) {
                    $sql = qq{ INSERT INTO vuln_nessus_settings_preferences (sid, id, nessus_id, value, category, type) 
                        VALUES($idprofile, NULL, 'Ping Host[checkbox]:Mark unrechable Hosts as dead (not scanning)', 'yes', 'PLUGINS_PREFS', 'C') };
                    $sth_sel = $dbh->prepare($sql);
                    $sth_sel->execute;
                    $sth_sel->finish();
                }
                
                # update nessus preferences for Deep, Ultimate, Default
                
                # Default (Non destructive Full and Fast scan)
                # safe_checks=YES, optimize_test=YES

                # Deep (Non destructive Full and Slow scan)
                # safe_checks=YES, optimize_test=NO

                # Ultimate (Full and Fast scan including Destructive tests)
                # safe_checks=NO, optimize_test=NO 
                
                $sql = qq{ SELECT id, name FROM vuln_nessus_settings WHERE name IN ('Default', 'Deep', 'Ultimate') ORDER BY name };

                $sth_sel = $dbh->prepare( $sql );
                $sth_sel->execute;

                while (my ($sid, $name) = $sth_sel->fetchrow_array ) {
                
                    if($name eq "Default") {
                        $safe_checks    = 'yes';
                        $optimized_test = 'yes';
                    }
                    elsif($name eq "Deep") {
                        $safe_checks    = 'yes';
                        $optimized_test = 'no';
                    }
                    elsif($name eq "Utimate") {
                        $safe_checks    = 'no';
                        $optimized_test = 'no';
                    }
                    
                    $sql = qq{ UPDATE vuln_nessus_settings_preferences SET value='$safe_checks' WHERE sid=$sid AND nessus_id='safe_checks'; };
                    safe_db_write( $sql, 4 );
                
                    $sql = qq{ UPDATE vuln_nessus_settings_preferences SET value='$optimized_test' WHERE sid=$sid AND nessus_id='optimized_test'; };
                    safe_db_write( $sql, 4 );
                    
                }
            }
            else { #OMP
                # $tmp[0] -> name, $tmp[2] -> C of F, $tmp[3] -> user
                my $id_ff = ""; # use conf-> main sensor

                $openvas_manager_common_conf_main = $openvas_manager_common;

                foreach my $sensor_data (@sensors) {

                    my @sd = split(/\|/, $sensor_data);
                    
                    $openvas_manager_common = "$binary_location -h ".$sd[0]." -p ".$sd[1]." -u '".$sd[2]."' -w '".$sd[3]."' -iX"; # other sensors

                    $id_ff = create_profile($tmp[0], $tmp[2], $tmp[3], $profiles{$nd});

                }

                $openvas_manager_common = $openvas_manager_common_conf_main;
                
                # preferences
                print "\nFilling preferences in Alienvault DB...\n";
                
                fill_preferences($idprofile, $id_ff); # id_ff = ff profile, deep profile and ultimate profile

                print "\nDone...\n";

                # update plugins to clone OpenVAS config
                if($tmp[0] eq 'Default' && $tmp[3] eq '0') {   # Default profile for user 0 is the "Full and fast" in OpenVAS manager
                    clone_settings_plugins($idprofile, "Full and fast");
                }
                if($tmp[0] eq 'Deep' && $tmp[3] eq '0') {      # Deep profile for user 0 is the "Full and very deep" in OpenVAS manager
                    clone_settings_plugins($idprofile, "Full and very deep");
                }
                if($tmp[0] eq 'Ultimate' && $tmp[3] eq '0') {  # Ultimate profile for user 0 is the "Full and very deep ultimate" in OpenVAS manager
                    clone_settings_plugins($idprofile, "Full and very deep ultimate");
                }
            }
                        
            print "\n$tmp[0] profile for user $tmp[3] inserted\n";
        }
        else {
            if($tmp[3] ne "admin") {
                print "\n$tmp[0] profile for admin or $tmp[3] user already exists\n";
            }
            else {
                print "\n$tmp[0] profile for user $tmp[3] already exists\n";
            }
        }
    }   # end foreach
}

sub in_array {
    my @arr = @{$_[0]};
    my $search_for = $_[1];
    
    foreach my $value (@arr) {
        if ($value eq $search_for) {
            return 1;
        }
    }
    return 0;
}

sub create_profile {

    my $name = $_[0];
    my $type = $_[1];
    my $user = $_[2];
    my $value = $_[3];

   
    my $cmd;
    my $i;
    my @tmp;
    my $id_ff;
    
    my $file_omp_command = "";


    my $profile_to_clone = "Full and fast";


    if($name eq 'Deep' && $user eq '0') {      
        $profile_to_clone = "Full and very deep";
    }
    elsif($name eq 'Ultimate' && $user eq '0') {
        $profile_to_clone = "Full and very deep ultimate";
    }
    
    $file_omp_command = "/usr/share/ossim/www/tmp/omp_command_$$".int(rand(1000000)).".xml";

    system("echo \"<get_configs />\" > '$file_omp_command'");
            
    $cmd = "$openvas_manager_common - < ".$file_omp_command." > $xml_output 2>&1";
    
    logwriter( "$cmd", 4 );
    my $imp = system ( $cmd );
    
    if(defined($debug) && $debug == 0 ) { unlink $file_omp_command if -e $file_omp_command; }

    if ( $imp != 0 ) { logwriter( "updateplugins: Failed Get Configs", 2 ); }

    my $xml = eval {XMLin($xml_output, keyattr => [])};
    
    #print Dumper($xml);
    
    if ($@ ne "") { logwriter( "Cant' read XML $xml_output", 2); }
    if ($xml->{'status'} !~ /20\d/) {
        my $status = $xml->{'status'};
        my $status_text = $xml->{'status_text'};
        logwriter( "Error: status = $status, status_text = '$status_text' ($xml_output)", 2);
    }
    
    if (ref($xml->{'config'}) eq 'ARRAY') {
        @items = @{$xml->{'config'}};
    } else {
        push(@items,$xml->{'config'});
    }
    
    foreach my $profile (@items) {
        if ($profile->{'name'} eq $profile_to_clone) {
            $id_ff = $profile->{'id'};
        }
    }
    
    #### copy config ####
    
    $file_omp_command = "/usr/share/ossim/www/tmp/omp_command_$$".int(rand(1000000)).".xml";

    system("echo \"<create_config><copy>$id_ff</copy><name>$name</name><comment>$user</comment></create_config>\" > '$file_omp_command'");
        
    $cmd = "$openvas_manager_common - < ".$file_omp_command." > $xml_output 2>&1";
        
    logwriter( "$cmd", 4 );

    $imp = system ( $cmd );
    
    if(defined($debug) && $debug == 0 ) { unlink $file_omp_command if -e $file_omp_command; }

    $xml = eval {XMLin($xml_output, keyattr => [])};
    
    if ($@ ne "") { logwriter( "Cant' read XML $xml_output", 2); }
    if ($xml->{'status'} !~ /20\d/) {
        my $status = $xml->{'status'};
        my $status_text = $xml->{'status_text'};
        logwriter( "Error: status = $status, status_text = '$status_text' ($xml_output)", 2);
    }

    $new_config_id = $xml->{'id'}; # new config id

    if ( $imp != 0 ) { logwriter( "updateplugins: Failed Create Config $name", 2 ); }

    
    if( ($name eq "Default" || $name eq "Deep" || $name eq "Ultimate") && $user eq "0" ) { return $id_ff; } 
    

    #### modify config ####
    
    # Disable all families
    my @openvas_manager_families = get_openvas_manager_families();
    
    foreach my $om_family(@openvas_manager_families) {

        $file_omp_command = "/usr/share/ossim/www/tmp/omp_command_$$".int(rand(1000000)).".xml";

        system("echo \"<modify_config config_id='$new_config_id'><nvt_selection><family>$om_family</family></nvt_selection></modify_config>\" > '$file_omp_command'");
            
        $cmd = " - < ".$file_omp_command." > $xml_output 2>&1";

        $openvas_manager_common_conf_main = $openvas_manager_common;

        foreach my $sensor_data (@sensors) {

            my @sd = split(/\|/, $sensor_data);
    
            $openvas_manager_common = "$binary_location -h ".$sd[0]." -p ".$sd[1]." -u '".$sd[2]."' -w '".$sd[3]."' -iX"; # other sensors
            
            $imp = system ( $openvas_manager_common.$cmd );
            
            if ( $imp != 0 ) { logwriter( "updateplugins: Cant' disable family '$om_family' for config '$name'", 2 ); }
            
            if(defined($debug) && $debug == 0 ) { unlink $file_omp_command if -e $file_omp_command; }
            
            $xml = eval {XMLin($xml_output, keyattr => [])};
        
            if ($@ ne "") { logwriter( "Cant' read XML $xml_output", 2); }
            if ($xml->{'status'} !~ /20\d/) {
                my $status = $xml->{'status'};
                my $status_text = $xml->{'status_text'};
                logwriter( "Error: status = $status, status_text = '$status_text' ($xml_output)", 2);
            }
        }

        $openvas_manager_common = $openvas_manager_common_conf_main;
    
    }
    
    if($type eq "F") { # Families
        @tmp = split(/\|/,$value);
        

        $cmd = "<modify_config config_id='$new_config_id'><family_selection>";
        
        foreach my $family (@tmp) {
            $cmd .= "<family><name>$family</name><growing>1</growing><all>1</all></family>";
            logwriter("Updating family '$family', growing=1 and all=1", 4);
        }
        $cmd .= "</family_selection></modify_config>";
        
        $file_omp_command = "/usr/share/ossim/www/tmp/omp_command_$$".int(rand(1000000)).".xml";

        system("echo \"$cmd\" > '$file_omp_command'");
        
        $cmd = " - < ".$file_omp_command." > $xml_output 2>&1";
            
        $openvas_manager_common_conf_main = $openvas_manager_common;

        foreach my $sensor_data (@sensors) {

            my @sd = split(/\|/, $sensor_data);
    
            $openvas_manager_common = "$binary_location -h ".$sd[0]." -p ".$sd[1]." -u '".$sd[2]."' -w '".$sd[3]."' -iX"; # other sensors
            
            $imp = system ( $openvas_manager_common.$cmd );
            
            if ( $imp != 0 && $check_command ) { logwriter( "updateplugins: Cant' modify Config $name", 2 ); }
            
            if(defined($debug) && $debug == 0 ) { unlink $file_omp_command if -e $file_omp_command; }
            
            $xml = eval {XMLin($xml_output, keyattr => [])};
        
            if ($@ ne "") { logwriter( "Cant' read XML $xml_output", 2); }
            if ($xml->{'status'} !~ /20\d/) {
                my $status = $xml->{'status'};
                my $status_text = $xml->{'status_text'};
                logwriter( "Error: status = $status, status_text = '$status_text' ($xml_output)", 2);
            }

        }

        $openvas_manager_common = $openvas_manager_common_conf_main;
    
        my %familyHash;
        
        $value =~ s/\|/\',\'/g;
        
        my $dplugins = join("', '", @disabled_plugins);
        
        # Special case, disable plugins for all profiles
        my $sql = qq{ SELECT f.name, p.oid FROM vuln_nessus_plugins AS p, vuln_nessus_family AS f
                                 WHERE p.family = f.id AND p.id NOT IN ('$dplugins')
                                                       AND p.family IN (SELECT id FROM vuln_nessus_family WHERE name IN ('$value')) ORDER BY f.name };
                                                       
        #logwriter($sql,4);

        my $sth_self=$dbh->prepare( $sql );
        $sth_self->execute;
        while (my ($family, $oid) =$sth_self->fetchrow_array ) {
            $familyHash{$family}{$oid}++;
        }
        $sth_self->finish(); 
    
        foreach my $family ( keys %familyHash ) {

            $file_omp_command = "/usr/share/ossim/www/tmp/omp_command_$$".int(rand(1000000)).".xml";
               
            open (OF,">$file_omp_command");
                
            print OF "<modify_config config_id='$new_config_id'><nvt_selection><family>$family</family>\n";

            if ($vervose eq '1')
            {
                logwriter("Updating family '$family'...", 4);
            }
            
            $i = 0;
            foreach my $oid ( keys %{$familyHash{$family}} ) {
                print OF "<nvt oid='$oid'/>\n";
                $i++;
            }

            print OF "</nvt_selection></modify_config>";
            close(OF);

            $cmd = " - < ".$file_omp_command." > $xml_output 2>&1";
                
            logwriter("$i plugins", 4);

            $openvas_manager_common_conf_main = $openvas_manager_common;

            foreach my $sensor_data (@sensors) {

                my @sd = split(/\|/, $sensor_data);
        
                $openvas_manager_common = "$binary_location -h ".$sd[0]." -p ".$sd[1]." -u '".$sd[2]."' -w '".$sd[3]."' -iX"; # other sensors
                
                $imp = system ( $openvas_manager_common.$cmd );
                
                if ( $imp != 0 && $check_command)  { logwriter( "updateplugins: Cant' modify Config $name", 2 ); }
                
                if(defined($debug) && $debug == 0 ) { unlink $file_omp_command if -e $file_omp_command; }
            
                $xml = eval {XMLin($xml_output, keyattr => [])};
        
                if ($@ ne "") { logwriter( "Cant' read XML $xml_output", 2); }
                
                if ($xml->{'status'} !~ /20\d/) {
                    my $status = $xml->{'status'};
                    my $status_text = $xml->{'status_text'};
                    logwriter( "Error: status = $status, status_text = '$status_text' ($xml_output)", 2);
                }

            }

            $openvas_manager_common = $openvas_manager_common_conf_main;
        }
    }
    else { # Categories
        my %familyHash;
        
        my $dplugins = join("', '", @disabled_plugins);
        
        $value =~ s/\|/\',\'/g;
        # Special case, disable plugins for all profiles
        my $sql = qq{ SELECT f.name, p.oid FROM vuln_nessus_plugins AS p, vuln_nessus_family AS f
                                 WHERE p.family = f.id AND p.id NOT IN ('$dplugins')
                                                       AND p.category IN (SELECT id FROM vuln_nessus_category WHERE name IN ('$value')) ORDER BY f.name };
                                                       
        #logwriter($sql,4);

        my $sth_self=$dbh->prepare( $sql );
        $sth_self->execute;
        while (my ($family, $oid) =$sth_self->fetchrow_array ) {
            $familyHash{$family}{$oid}++;
        }
        $sth_self->finish(); 
    
        foreach my $family ( keys %familyHash ) {

            $file_omp_command = "/usr/share/ossim/www/tmp/omp_command_$$".int(rand(1000000)).".xml";
            
            open (OF,">$file_omp_command");
            print OF "<modify_config config_id='$new_config_id'><nvt_selection><family>$family</family>\n";
            
            if ($vervose eq '1')
            {
                logwriter("Updating family '$family'...", 4);
            }
            
            $i = 0;
            foreach my $oid ( keys %{$familyHash{$family}} ) {
                print OF "<nvt oid='$oid'/>\n";
                $i++;
            }
            
            print OF "</nvt_selection></modify_config>";
            close(OF);

            $cmd = " - < ".$file_omp_command." > $xml_output 2>&1";

            logwriter("$i plugins", 4);

            $openvas_manager_common_conf_main = $openvas_manager_common;

            foreach my $sensor_data (@sensors) {

                my @sd = split(/\|/, $sensor_data);
        
                $openvas_manager_common = "$binary_location -h ".$sd[0]." -p ".$sd[1]." -u '".$sd[2]."' -w '".$sd[3]."' -iX"; # other sensors
                
                $imp = system ( $openvas_manager_common.$cmd );
                
                if ( $imp != 0 and $check_command ) { logwriter( "updateplugins: Cant' modify Config $name", 2 ); }
                
                if(defined($debug) && $debug == 0 ) { unlink $file_omp_command if -e $file_omp_command; }
        
                $xml = eval {XMLin($xml_output, keyattr => [])};
        
                if ($@ ne "") { logwriter( "Cant' read XML $xml_output", 2); }
                if ($xml->{'status'} !~ /20\d/) {
                    my $status = $xml->{'status'};
                    my $status_text = $xml->{'status_text'};
                    logwriter( "Error: status = $status, status_text = '$status_text' ($xml_output)", 2);
                }
            }

            $openvas_manager_common = $openvas_manager_common_conf_main;
                
        }
    }
    return $id_ff;
}

sub get_config_id {
    my $name = $_[0];
    my $user = $_[1];
    
    my $result = "";
    my @items=();
    
    my $file_omp_command = "";
    my $cmd              = "";
    

    $file_omp_command = "/usr/share/ossim/www/tmp/omp_command_$$".int(rand(1000000)).".xml";

    system("echo \"<get_configs />\" > '$file_omp_command'");
        
    $cmd = "$openvas_manager_common - < ".$file_omp_command." > $xml_output 2>&1";

    
    #logwriter( "$cmd", 4 );
    my $imp = system ( $cmd );
    
    if(defined($debug) && $debug == 0 ) { unlink $file_omp_command if -e $file_omp_command; }

    if ( $imp != 0 ) { logwriter( "updateplugins: Failed Get Configs", 2 ); }

    my $xml = eval {XMLin($xml_output, keyattr => [])};
    
    #print Dumper($xml);
    
    if ($@ ne "") { logwriter( "Cant' read XML $xml_output", 2); }
    if ($xml->{'status'} !~ /20\d/) {
        my $status = $xml->{'status'};
        my $status_text = $xml->{'status_text'};
        logwriter( "Error: status = $status, status_text = '$status_text' ($xml_output)", 2);
    }
    
    if (ref($xml->{'config'}) eq 'ARRAY') {
        @items = @{$xml->{'config'}};
    } else {
        push(@items,$xml->{'config'});
    }
    
    foreach my $profile (@items) {
        if ($profile->{'name'} eq $name && $profile->{'comment'} eq $user) {
            $result = $profile->{'id'};
        } 
        elsif ($profile->{'name'} eq $name && $profile->{'in_use'} eq "1") { # to search in OpenVas configs
            $result = $profile->{'id'};
        }
    }
    # if ($result eq "") { # search for full & fast
        # $name = "Full and fast";
        # foreach my $profile (@items) {
          # if ($profile->{'name'} eq $name && $profile->{'comment'} eq $user) {
                # $result = $profile->{'id'};
          # } 
          # elsif ($profile->{'name'} eq $name && $profile->{'in_use'} eq "1") { # to search in OpenVas configs
                # $result = $profile->{'id'};
          # }
        # }    
    # }
    return $result;
}

sub fill_preferences {
    my $idprofile = $_[0];
    my $id_ff = $_[1];
    my $sql;
    
    my @items=();
    my @preferences=();
    
    my $file_omp_command = "";
    my $cmd              = "";
    
    $file_omp_command = "/usr/share/ossim/www/tmp/omp_command_$$".int(rand(1000000)).".xml";

    system("echo \"<get_preferences config_id='$id_ff'/>\" > '$file_omp_command'");
        
    $cmd = "$openvas_manager_common - < ".$file_omp_command." > $xml_output 2>&1";
        
    logwriter( "$cmd", 4 );
    
    my $imp = system ( $cmd );
    
    if(defined($debug) && $debug == 0 ) { unlink $file_omp_command if -e $file_omp_command; }

    if ( $imp != 0 ) { logwriter( "updateplugins: Failed Get Preferences", 2 ); }
    
    my $xml = eval {XMLin($xml_output, keyattr => [])};
    
    
    if ($@ ne "") { logwriter( "Cant' read XML $xml_output", 2); }
    if ($xml->{'status'} !~ /20\d/) {
        my $status = $xml->{'status'};
        my $status_text = $xml->{'status_text'};
        logwriter( "Error: status = $status, status_text = '$status_text' ($xml_output)", 2);
    }
    
    if (ref($xml->{'preference'}) eq 'ARRAY') {
        @items = @{$xml->{'preference'}};
    } else {
        push(@items,$xml->{'preference'});
    }
    
    foreach my $preference (@items) {
        #print Dumper($preference);
        if (ref($preference->{'value'}) eq 'HASH') {
            $preference->{'value'} = ""; 
        }
        push(@preferences, $preference->{'name'}." = ".$preference->{'value'});
    }
    
    #open(PROC, "$cmd |") or die "failed to fork :$!\n";
    foreach (@preferences) {
        if (/\]:/) {
            # PLUGINS_PREFS
            $f5 = "PLUGINS_PREFS";
            ($f1,$rhs) = split(/\[/);
            ($f2,$rhs2) = split(/\]:/,$rhs);
            ($f3,$f4) = split(/=/, $rhs2);
             $f3 =~ s/\s+$//;    # Remove trailing whitespace 
            $f4 =~ s/^ //;        # Remove leading whitespace
            $f4 =~ s/\n$//;        # Remove trailing newline

            $f0 = $f1."[".$f2."]:".$f3;
            $f2 =~ s/entry/T/;        # Text box
            $f2 =~ s/radio/R/;        # Radio button
            $f2 =~ s/checkbox/C/;        # Checkbox
            $f2 =~ s/password/P/;        # Password
            $f2 =~ s/file/T/;        # File

        } else {
            # SERVER_PREFS
            $f5 = "SERVER_PREFS";

            $f1 = "ServerPrefs";
            ($f3,$f4) = split(/=/);
            $f3 =~ s/\s+$//;    # Remove trailing whitespace
            $f4 =~ s/\n$//;        # Remove trailing newline
            $f4 =~ s/^ //;        # Remove leading whitespace
            $f2 = "T";
            $f0 = $f3;
        }

        $sql = qq{insert into vuln_nessus_settings_preferences (sid, nessus_id, type, 
                value, category) values ($idprofile,"$f0", "$f2", "$f4", "$f5");};
                
        safe_db_write( $sql, 5 );
    }

}

sub delete_configs {

    my $file_omp_command_get = "/usr/share/ossim/www/tmp/omp_command_$$".int(rand(1000000)).".xml";
    my $cmd_get              = " - < ".$file_omp_command_get." > $xml_output 2>&1";
    
    system("echo \"<get_configs />\" > '$file_omp_command_get'");

    $openvas_manager_common_conf_main = $openvas_manager_common;

    foreach my $sensor_data (@sensors) {

        my @sd = split(/\|/, $sensor_data);

        $openvas_manager_common = "$binary_location -h ".$sd[0]." -p ".$sd[1]." -u '".$sd[2]."' -w '".$sd[3]."' -iX"; # other sensors
        
        
        $imp = system ( $openvas_manager_common.$cmd_get );

        if ( $imp != 0 ) { logwriter( "updateplugins: Failed Get Configs", 2 ); }

        my $xml = eval {XMLin($xml_output, keyattr => [])};
        
        #print Dumper($xml);
        
        if ($@ ne "") { logwriter( "Cant' read XML $xml_output", 2); }
        if ($xml->{'status'} !~ /20\d/) {
            my $status = $xml->{'status'};
            my $status_text = $xml->{'status_text'};
            logwriter( "Error: status = $status, status_text = '$status_text' ($xml_output)", 2);
        }
        
        if (ref($xml->{'config'}) eq 'ARRAY') {
            @items = @{$xml->{'config'}};
        } else {
            push(@items,$xml->{'config'});
        }
    
        foreach my $profile (@items) {
            if ($profile->{'in_use'} eq "0") {

                logwriter( "Deleting... ".$profile->{'name'}." profile ( ".$profile->{'id'}." ) from ".$sd[0], 4);
                
                my $id_delete = $profile->{'id'};
                
                $file_omp_command_delete = "/usr/share/ossim/www/tmp/omp_command_$$".int(rand(1000000)).".xml";

                system("echo \"<delete_config config_id='$id_delete' />\" > '$file_omp_command_delete'");
                    
                $cmd_delete = " - < ".$file_omp_command_delete." > $xml_output 2>&1";

                $imp = system ( $openvas_manager_common.$cmd_delete );
                
                if(defined($debug) && $debug == 0 ) { unlink $file_omp_command_delete if -e $file_omp_command_delete; }

                if ( $imp != 0 ) { logwriter( "updateplugins: Failed Delete Config ".$profile->{'name'}." (".$profile->{'comment'}.")", 2 ); }

                my $xml = eval {XMLin($xml_output, keyattr => [])};
                
                if ($@ ne "") { logwriter( "Cant' read XML $xml_output", 2); }
                if ($xml->{'status'} !~ /20\d/) {
                    my $status = $xml->{'status'};
                    my $status_text = $xml->{'status_text'};
                    logwriter( "Deleting ".$profile->{'name'}." profile ( $id_delete ) from ".$sd[0]." status = $status, status_text = '$status_text' ($xml_output)", 2);
                }
            }
        }
        @items = ();
    }
    
    if(defined($debug) && $debug == 0 ) { unlink $file_omp_command_get if -e $file_omp_command_get; }

    $openvas_manager_common = $openvas_manager_common_conf_main;

}

sub get_openvas_manager_families {

    my @families=();
    my @items=();
    
    my $cmd = "";
    my $file_omp_command = "";
    

    $file_omp_command = "/usr/share/ossim/www/tmp/omp_command_$$".int(rand(1000000)).".xml";

    system("echo \"<get_nvt_families />\" > '$file_omp_command'");
        
    $cmd = "$openvas_manager_common - < ".$file_omp_command." > $xml_output 2>&1";
        
    my $imp = system ( $cmd );
    
    if(defined($debug) && $debug == 0 ) { unlink $file_omp_command if -e $file_omp_command; }

    if ( $imp != 0 ) { logwriter( "updateplugins: Failed Get Families", 2 ); }

    my $xml = eval {XMLin($xml_output, keyattr => [])};
    
    #print Dumper($xml);
    
    if ($@ ne "") { logwriter( "Cant' read XML $xml_output", 2); }
    if ($xml->{'status'} !~ /20\d/) {
        my $status = $xml->{'status'};
        my $status_text = $xml->{'status_text'};
        logwriter( "Error: status = $status, status_text = '$status_text' ($xml_output)", 2);
    }
    
    if (ref($xml->{'families'}->{'family'}) eq 'ARRAY') {
        @items = @{$xml->{'families'}->{'family'}};
    } else {
        push(@items,$xml->{'families'}->{'family'});
    }
   
    #print Dumper(@items);
   
    foreach my $family ( @items) {
        push(@families, $family->{'name'});
    }

    return(@families);
}

sub delete_all_tasks {
    my @items = ();
    my $task_id = "";


    $openvas_manager_common_conf_main = $openvas_manager_common;

    foreach my $sensor_data (@sensors) {

        my @sd = split(/\|/, $sensor_data);

        $openvas_manager_common = "$binary_location -h ".$sd[0]." -p ".$sd[1]." -u '".$sd[2]."' -w '".$sd[3]."' -iX"; # other sensors

        logwriter( "Deleting all tasks in ".$sd[0]." ...", 4 );
    
        my $xml = execute_omp_command("<get_tasks />");
        
        if (ref($xml->{'task'}) eq 'ARRAY') {
            @items = @{$xml->{'task'}};
        } else {
            push(@items,$xml->{'task'});
        }
        
        foreach my $task (@items) {
            $task_id = $task->{'id'};
            if(defined($task->{'id'})) {
                execute_omp_command("<stop_task task_id='$task_id' />");
                execute_omp_command("<delete_task task_id='$task_id' />");
            }
        }
        
        execute_omp_command("<empty_trashcan />");
    }

    $openvas_manager_common = $openvas_manager_common_conf_main;

}

sub execute_omp_command {
    my ($cmd, $host, $port, $user, $pass, $exit) = @_;
    
    $host = ""   if (Empty( $host ) );
    $port = ""   if (Empty( $port ) );
    $user = ""   if (Empty( $user ) );
    $pass = ""   if (Empty( $pass ) );
    $exit = TRUE if (Empty( $exit ) );

    if($host ne "" && $port ne "" && $user ne "" && $pass ne "") {
        $openvas_manager_common_conf_main = $openvas_manager_common;
        $openvas_manager_common = "$binary_location -h ".$host." -p ".$port." -u '".$user."' -w '".$pass."' -iX";
    }

    my $file_omp_command = "";
    my $aux = "Executing: $openvas_manager_common \"$cmd\"";

    if($debug) {
        logwriter($cmd, 5);
    }
    
    $file_omp_command = "/usr/share/ossim/www/tmp/omp_command_$$".int(rand(1000000)).".xml";

    system("echo \"$cmd\" > '$file_omp_command'");
    
    $cmd = "$openvas_manager_common - < ".$file_omp_command." > $xml_output 2>&1";

    my $imp = system ($cmd);

    $openvas_manager_common = $openvas_manager_common_conf_main if($host ne "" && $port ne "" && $user ne "" && $pass ne "");

    if(defined($debug) && $debug == 0 ) { unlink $file_omp_command if -e $file_omp_command; }
    
    if ( $imp != 0 && $exit) {
        logwriter( $aux, 4 );
        logwriter( "Failed to connect\n", 4 );
        exit(1);
    }

    my $xml = eval {XMLin($xml_output, keyattr => [])};
    
    unlink $xml_output if -e $xml_output;
    
    return $xml;
}
sub get_config_families {
    my $config = shift;
    my @fn     = ();
    
    my $config_id = get_config_id($config,"");
    my $xml = execute_omp_command("<get_configs config_id='$config_id' families='1'/>");

    if (ref($xml->{'config'}{'families'}{'family'}) eq 'ARRAY') {
        @items = @{$xml->{'config'}{'families'}{'family'}};
    } else {
        push(@items,$xml->{'config'}{'families'}{'family'});
    }

    foreach my $family (@items) {
       if($family->{'name'} ne "") {
            push(@fn, $family->{'name'});
       }
    }
    return join('|',@fn);
}

sub clone_settings_plugins {
    my $ossim_profile = $_[0];
    my $omp_profile   = $_[1];
    
    my $config_id = get_config_id($omp_profile,"");
    my $xml = execute_omp_command("<get_configs config_id='$config_id' families='1'/>");

    if (ref($xml->{'config'}{'families'}{'family'}) eq 'ARRAY') {
        @items = @{$xml->{'config'}{'families'}{'family'}};
    } else {
        push(@items,$xml->{'config'}{'families'}{'family'});
    }

    foreach my $family (@items) {
       if($family->{'nvt_count'} ne $family->{'max_nvt_count'}) {
            $fname = $family->{'name'};
            my $sql = qq{ UPDATE vuln_nessus_settings_plugins SET enabled='N' WHERE family in (SELECT id FROM vuln_nessus_family WHERE name LIKE '$fname') AND sid=$ossim_profile; };
            safe_db_write( $sql, 4 );
            my $xml = execute_omp_command("<get_nvts family='$fname' config_id='$config_id' />");

            if (ref($xml->{'nvt'}) eq 'ARRAY') {
                @nitems = @{$xml->{'nvt'}};
            } else {
                push(@nitems,$xml->{'nvt'});
            }
            foreach my $nvt (@nitems) {
                $nvt->{'oid'} =~ s/.*\.//;
                $id = $nvt->{'oid'};
                $sql = qq{ UPDATE vuln_nessus_settings_plugins SET enabled='Y' WHERE id=$id AND sid=$ossim_profile; };
                safe_db_write( $sql, 4 );
            }
        }
    }

}
sub enable_port_scanner_plugins {
    my ( $sth_sel, $sql );

    logwriter( "BEGIN  - UPDATE PORT SCANNER ('Nmap - NASL wrapper' AND 'Ping Host') ", 4 );
    my $time_start = time();
    
    $sql = qq{ select oid from vuln_nessus_plugins WHERE id=14259 };

    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;
    
    my($oid1) = $sth_sel->fetchrow_array;
    
    
    $sql = qq{ select oid from vuln_nessus_plugins WHERE id=100315 };

    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;
    
    my($oid2) = $sth_sel->fetchrow_array;
    
    $sql = qq{ select oid from vuln_nessus_plugins WHERE id=10335 };

    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;
    
    my($oid3) = $sth_sel->fetchrow_array;
    

    $sql = qq{ select distinct id, name, owner from vuln_nessus_settings };

    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;

    while (my ($sid, $name, $user) = $sth_sel->fetchrow_array ) {
        $sql = qq{ UPDATE vuln_nessus_settings_plugins SET enabled='N' WHERE sid=$sid AND family in (select id from vuln_nessus_family WHERE name='Port scanners'); };
        safe_db_write( $sql, 4 );
    
        $sql = qq{ UPDATE vuln_nessus_settings_plugins SET enabled='Y' WHERE sid=$sid AND ( id=14259 OR id=100315 OR id=10335 ); };
        safe_db_write( $sql, 4 );
        
        $config_id = get_config_id($name, $user);
        
        $cmd = "<modify_config config_id='$config_id'><nvt_selection><family>Port scanners</family>";
        $cmd .= "<nvt oid='$oid1' />";
        $cmd .= "<nvt oid='$oid2' />";
        $cmd .= "<nvt oid='$oid3' />";
        $cmd .= "</nvt_selection></modify_config>";

        $openvas_manager_common_conf_main = $openvas_manager_common;

        foreach my $sensor_data (@sensors) {

            my @sd = split(/\|/, $sensor_data);
        
            $openvas_manager_common = "$binary_location -h ".$sd[0]." -p ".$sd[1]." -u '".$sd[2]."' -w '".$sd[3]."' -iX"; # other sensors
    
            execute_omp_command($cmd);

        }

        $openvas_manager_common = $openvas_manager_common_conf_main;
    }
    my $time_run = time() - $time_start;
    
    logwriter( "FINISH - UPDATE PORT SCANNER [ Process took $time_run seconds ]\n", 4 );
}

sub debug_omp_file {
    my ($file_omp_command) = $_[0];

    open(F,"<$file_omp_command");
    @content = <F>;
    close(F);
    
    logwriter(join("\n",@content), 5);
}

sub Empty {
    my $var  = shift;
    if (defined($var) && $var ne "") {
        return 0;
    }
    else {
        return 1;
    }
}

sub noEmpty {
    my $var  = shift;
    if (defined($var) && $var ne "") {
        return 1;
    }
    else {
        return 0;
    }
}

sub check_openvas_sensor {

    my $ip     = shift;
    my $port   = shift;
    my $user   = shift;
    my $pass   = shift;

    my $result = FALSE;

    my $xml = execute_omp_command("<help />", $ip, $port, $user, $pass, FALSE);

    $result = TRUE if ($xml->{'status_text'} eq "OK");

    return $result;

}
