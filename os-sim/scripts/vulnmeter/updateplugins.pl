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
use lib "/usr/share/ossim/include";

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

my $custom          = FALSE;
my $machine_profile = "";
my $is_sensor       = FALSE;
my $is_framework    = FALSE;
my %CONFIG          = ();

if(defined($ARGV[0])) {
    $CONFIG{'UPDATEPLUGINS'} = ($ARGV[0] eq "update") ? 1 : 0;
    $CONFIG{'REPAIRDB'} = ($ARGV[0] eq "repair") ? 1 : 0;
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

if(defined($CONFIG{'REPAIRDB'}) && $CONFIG{'REPAIRDB'}==1) {
    logwriter("Repair task.db...", 4 );
    system ("sudo /usr/share/ossim/scripts/vulnmeter/openvas_rebuild.sh repair > /var/tmp/rebuild_task.db.log 2>&1") == 0 or logwriter( "Can not rebuild the task.db", 3 );
    logwriter("Done.", 4 );
}

if (!$is_sensor && !$is_framework) {
    die("Neither sensor profile nor framework profile has been found.\n");
}
elsif ($is_sensor) {
    logwriter( "Sensor profile has been found...", 4 );
    # Update OpenVAS plugins
    if (-e "/usr/sbin/openvas-nvt-sync" ) {
        logwriter( "Updating OpenVAS plugins...", 4 );
        my $ocommand = system ("sudo /usr/sbin/openvas-nvt-sync --wget > /var/tmp/update_scanner_plugins.log 2>&1");

        my $fcommand = system ("sudo /usr/share/ossim/scripts/vulnmeter/fix_openvas_plugins.sh > /var/tmp/update_scanner_plugins.log 2>&1");
        
        if ( -e "/etc/init.d/openvas-manager" && $ocommand == 0 ) {
           logwriter( "Rebuilding OpenVAS NVT cache...", 4 );
           system ("sudo /usr/share/ossim/scripts/vulnmeter/openvas_rebuild.sh > /var/tmp/rebuild_nvt_cache.log 2>&1") == 0 or logwriter( "Can not rebuid the NVT cache", 3 ); 
        }
        
        if($ocommand == 1) {
            logwriter( "updateplugins: No new plugins installed for OpenVAS", 3 );
        }
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

$CONFIG{'DATABASENAME'} = "alienvault";
$CONFIG{'DATABASEHOST'} = $dbhost;
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

my @profiles = ();

$profiles[0] = '1|Deep|Non destructive Full and Slow scan|F|0|1|1';
$profiles[1] = '2|Default|Non destructive Full and Fast scan|F|0|1|1';
$profiles[2] = '3|Ultimate|Full and Fast scan including Destructive tests|F|0|1|1';

my @sensors = ();

#Load sensor to update profiles

if ($binary_location =~ /omp\s*$/) {
    $sql = qq{ select inet6_ntoa(ip) as sIP, vns.port as port, vns.user, AES_DECRYPT(PASSWORD,'$uuid') as dpass, PASSWORD AS pass from sensor s, vuln_nessus_servers vns 
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

my $updateplugins="$CONFIG{'UPDATEPLUGINS'}";

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

    if ($custom == FALSE) { delete_all_tasks(); }
    
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
    
    if(defined($CONFIG{'REPAIRDB'}) && $CONFIG{'REPAIRDB'}==1) {
        logwriter( "updateplugins: configured to repair DB", 4 );
        
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
        
        delete_configs();

    }
    else {
        logwriter( "updateplugins: configured to not repair DB", 4 );
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

        update_openvas_plugins();
    }
    
    if ($custom == TRUE)
    {
        disconn_db($dbh);
        $dbh = conn_db();
        update_settings_plugins();
    }

    if ($custom == FALSE)
    {
        disconn_db($dbh);
        $dbh = conn_db();
        update_preferences();
    
        disconn_db($dbh);
        $dbh = conn_db();
        $sql = qq{ DROP TABLE `vuln_plugins`; };
        safe_db_write( $sql, 5 );

        disconn_db($dbh);
        $dbh = conn_db();
        generate_profiles_in_db(\@profiles);

        disconn_db($dbh);
        $dbh = conn_db();
        enable_port_scanner_plugins();
        
        disconn_db($dbh);
        #
        print "\nUpdating plugin_sid vulnerabilities scanner ids\n";
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
           logwriter( "$CONFIG{'NESSUSUPDATEPLUGINSPATH'} > /var/tmp/update_scanner_plugins_rsync.log", 4 );
           
           mkdir("/var/lib/openvas/plugins/private") unless(-d "/var/lib/openvas/plugins/private");
           
           system ("sudo $CONFIG{'NESSUSUPDATEPLUGINSPATH'} > /var/tmp/update_scanner_plugins_rsync.log 2>&1") == 0 or logwriter( "updateplugins: No new plugins installed", 3 ); 

            if (-e "/etc/init.d/openvas-manager") {
                #Rebuild the NVT cache
                logwriter( "Fixing OpenVAS Plugins...", 4 );
                system ("sudo /usr/share/ossim/scripts/vulnmeter/fix_openvas_plugins.sh > /var/tmp/update_scanner_plugins.log 2>&1");

                logwriter( "Rebuilding NVT cache...", 4 );
                system ("sudo /usr/share/ossim/scripts/vulnmeter/openvas_rebuild.sh > /var/tmp/rebuild_nvt_cache.log 2>&1") == 0 or logwriter( "Can not rebuid the NVT cache", 3 ); 
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

            $sql = qq{INSERT IGNORE INTO vuln_plugins VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)};
            $sth_sel = $dbh->prepare($sql);
            $sth_sel->execute($id,$oid,$name,$family,$category,$copyright,$summary,$description,$version,$cve_id,$bugtraq_id, $xref, $cvss_base);
            #print "$sql\n";
            
            $nplugins++;
            #print "\r$nplugins";
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
        elsif( int($cvss_base) > 0 && int($cvss_base) < 2 ) {
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

    my $time_run = time() - $time_start;
    
    logwriter( "FINISH - UPDATE OPENVAS_PLUGINS [ Process took $time_run seconds ]\n", 4 );

}

sub update_settings_plugins {
    
    my %autofam;
    my %autocat;
    
    my $profiles_filter = "WHERE name NOT IN ('Default', 'Deep', 'Ultimate')";
 
    logwriter( "BEGIN  - UPDATE SETTINGS_PLUGINS", 4 );
    my $time_start = time();

    my ( $sth_sel, $sth_sel2, $sth_sel3, $sth_ins, $sql );
    my $now = genScanTime();
    
    #CREATE AUTOENABLE CATEGORY ARRAY
    $sql = qq{ select sid, cid, status from vuln_nessus_settings_category };
    $sth_sel=$dbh->prepare( $sql );
    $sth_sel->execute;
    while ( my ($sid, $cid, $status) = $sth_sel->fetchrow_array ) {
       $autocat{$sid}->{$cid} = $status;
    }

    #CREATE AUTOENABLE FAMILY ARRAY
    $sql = qq{ select sid, fid, status from vuln_nessus_settings_family };
    $sth_sel=$dbh->prepare( $sql );
    $sth_sel->execute;
    while ( my ($sid, $fid, $status) = $sth_sel->fetchrow_array ) {
       $autofam{$sid}->{$fid} = $status;
    }
 
    $sql = qq{ SELECT id, autoenable FROM vuln_nessus_settings $profiles_filter };
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;
    
    while ( my ($profile_id, $autoenable)=$sth_sel->fetchrow_array) {
        
        $sql = qq{ SELECT id, category, family FROM vuln_nessus_plugins WHERE id NOT IN (SELECT id FROM vuln_nessus_settings_plugins WHERE sid = $profile_id) };
        
        $pl_sel = $dbh->prepare( $sql );
        $pl_sel->execute;

        while ( my ($plugin_id, $category_id, $family_id ) = $pl_sel->fetchrow_array ) {
            
            my $status = 'N';
            
            if ( ($autoenable eq 'F' && $autofam{$profile_id}->{$family_id} eq '2') ||
                 ($autoenable eq 'C' && $autocat{$profile_id}->{$category_id} eq '2'))
            {
                $status = 'Y';
            }
            
            $sqlp = qq{ INSERT INTO vuln_nessus_settings_plugins (id, sid, enabled, category, family ) VALUES 
                        ('$plugin_id', '$profile_id', '$status', '$category_id', '$family_id' ); };
            
            safe_db_write($sqlp, 4);
             
            $msids{$profile_id}++;
        }
    }

    my @sids_to_modify = keys %msids;
    
    if($#sids_to_modify!= -1) {
        
        # update configs openvas-manager configs
        
        my $sids = join("', '",keys %msids);
        
        my $sql_sids = qq{ SELECT id, name, owner FROM vuln_nessus_settings WHERE id IN ('$sids') };
        
        my $cmd = "";

        my $sth_sids=$dbh->prepare( $sql_sids );
        $sth_sids->execute;
        while (my ($psid, $pname, $powner) =$sth_sids->fetchrow_array ) {
            
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
    
    my $file_omp_command = "/usr/share/ossim/www/tmp/omp_command_$$".int(rand(1000000)).".xml";
    
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

        my $alts = '';

        if (ref($preference->{'value'}) eq 'HASH') {
            $preference->{'value'} = ''; 
        }
        
        if (defined($preference->{'alt'}))
        {
            if (ref($preference->{'alt'}) eq 'ARRAY')
            {
               $alts = ';' . join(';', @{$preference->{'alt'}});
            }
            else
            {
               $alts = ';' . join(';', $preference->{'alt'});
            }
        }
        
        if (defined($preference->{'nvt'}->{'name'}) && ref($preference->{'nvt'}->{'name'}) ne 'HASH' && $preference->{'nvt'}->{'name'} ne '')
        {
            $preference->{'name'} = $preference->{'nvt'}->{'name'} . "[" . $preference->{'type'} . "]:" . $preference->{'name'};
        }
        
        push(@preferences, $preference->{'name'}." = ".$preference->{'value'} . $alts);
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

        $f0 =~ s/"/\\"/g;
        $f3 =~ s/"/\\"/g;

        # Does the current record exist? If not
        $sql = qq{ SELECT count(*) from vuln_nessus_preferences_defaults WHERE nessus_id = "$f0" };
        #logwriter( $cmd, 5 );
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

sub generate_profiles_in_db {
    my @profiles = @{$_[0]};

    foreach my $nd (@profiles) {
        disconn_db($dbh);
        $dbh = conn_db();
        my @tmp = split(/\|/,$nd);

        my $p_id       = $tmp[0];
        my $p_name     = $tmp[1];
        my $p_desc     = $tmp[2];
        my $p_auto     = $tmp[3];
        my $p_owner    = $tmp[4];
        my $p_c_status = $tmp[5];
        my $p_f_status = $tmp[6];

        $sql = qq{DELETE FROM vuln_nessus_settings WHERE id = $p_id};
        safe_db_write($sql, 5);

        $sql = qq{DELETE FROM vuln_nessus_settings_category WHERE sid = $p_id};
        safe_db_write($sql, 5);

        $sql = qq{DELETE FROM vuln_nessus_settings_family WHERE sid = $p_id};
        safe_db_write($sql, 5);

        $sql = qq{DELETE FROM vuln_nessus_settings_plugins WHERE sid = $p_id};
        safe_db_write($sql, 5);

        $sql = qq{DELETE FROM vuln_nessus_settings_preferences WHERE sid = $p_id};
        safe_db_write($sql, 5);

        $omp_id = get_config_id($p_name, $p_owner);

        if ($omp_id ne '')
        {
            print "\n";

            logwriter("Creating $p_name profile...", 4);
            $sql = qq{INSERT INTO vuln_nessus_settings (id, name, description, autoenable, owner, auto_cat_status, auto_fam_status)
                    values($p_id, '$p_name', '$p_desc', '$p_auto', '$p_owner', '$p_c_status', '$p_f_status')};

            $sth_sel = $dbh->prepare($sql);
            $sth_sel->execute;
            $sth_sel->finish;
            
            # Force category on
            
            logwriter("Filling categories...", 4);
            
            $sql = qq{ SELECT id, name FROM vuln_nessus_category };
            $sth_self=$dbh->prepare( $sql );
            $sth_self->execute;
            while (my ($idcategory, $namecategory)=$sth_self->fetchrow_array ) {
                $namecategory =~ s/\t+//g;
                
                print ".";
                
                $sql = qq{ INSERT INTO vuln_nessus_settings_category (sid, cid, status) VALUES ($p_id, $idcategory, 2)};
                
                $sth_sel = $dbh->prepare($sql);
                $sth_sel->execute;
                $sth_sel->finish();
            }
            $sth_self->finish();
            logwriter("Done",4);
            
            # Force family on
                            
            logwriter("Filling families...", 4);
            
            $sql = qq{ SELECT id, name FROM vuln_nessus_family };
            $sth_self=$dbh->prepare( $sql );
            $sth_self->execute;
            while (my ($idfamily, $namefamily)=$sth_self->fetchrow_array ) {
                $namefamily =~ s/\t+//g;
                
                print ".";
                
                $sql = qq{ INSERT INTO vuln_nessus_settings_family (sid, fid, status) VALUES ($p_id, $idfamily, 2)};
                
                $sth_sel = $dbh->prepare($sql);
                $sth_sel->execute;
                $sth_sel->finish();
            }
            $sth_self->finish();
            logwriter("Done", 4);
            
            # plugins
            logwriter("Filling plugins...", 4);
            $sql = qq{ SELECT id, category, family FROM vuln_nessus_plugins };
            $sth_self=$dbh->prepare( $sql );
            $sth_self->execute;
            while (my ($idplugin, $idcategory, $idfamily) = $sth_self->fetchrow_array ) {

                $sql = qq{ INSERT INTO vuln_nessus_settings_plugins (id, sid, enabled, category, family) VALUES ($idplugin, $p_id, 'Y', $idcategory, $idfamily) };

                $sth_sel = $dbh->prepare($sql);
                $sth_sel->execute;
                $sth_sel->finish();
            }

            # preferences
            logwriter("Filling preferences in Alienvault DB...", 4);

            fill_preferences($p_id, $omp_id);

            logwriter("Done", 4);
                        
            logwriter("$p_name profile inserted", 4);
        }
        else
        {
            logwriter("$p_name doesn't exist in OpenVAS", 4);
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

    return $result;
}

sub fill_preferences {
    my $db_id  = $_[0];
    my $omp_id = $_[1];
    my $sql;
    
    my @items=();
    my @preferences=();
    
    my $file_omp_command = "";
    my $cmd              = "";
    
    $file_omp_command = "/usr/share/ossim/www/tmp/omp_command_$$".int(rand(1000000)).".xml";

    system("echo \"<get_preferences config_id='$omp_id'/>\" > '$file_omp_command'");
        
    $cmd = "$openvas_manager_common - < ".$file_omp_command." > $xml_output 2>&1";
        
    #logwriter( "$cmd", 4 );
    
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
        my $alts = '';

        if (ref($preference->{'value'}) eq 'HASH') {
            $preference->{'value'} = '';
        }

        if (defined($preference->{'alt'}))
        {
            if (ref($preference->{'alt'}) eq 'ARRAY')
            {
               $alts = ';' . join(';', @{$preference->{'alt'}});
            }
            else
            {
               $alts = ';' . join(';', $preference->{'alt'});
            }
        }

        if (defined($preference->{'nvt'}->{'name'}) && ref($preference->{'nvt'}->{'name'}) ne 'HASH' && $preference->{'nvt'}->{'name'} ne '')
        {
            $preference->{'name'} = $preference->{'nvt'}->{'name'} . "[" . $preference->{'type'} . "]:" . $preference->{'name'};
        }

        push(@preferences, $preference->{'name'}." = ".$preference->{'value'} . $alts);
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

        $f0 =~ s/"/\\"/g;
        $f3 =~ s/"/\\"/g;

        $sql = qq{insert into vuln_nessus_settings_preferences (sid, nessus_id, type, 
                value, category) values ($db_id,"$f0", "$f2", "$f4", "$f5");};
                
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

sub enable_port_scanner_plugins {
    my ( $sth_sel, $sql );

    print "\n";

    logwriter("BEGIN  - UPDATE PORT SCANNER", 4 );
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
    

    $sql = qq{ select distinct id, name, owner from vuln_nessus_settings where id <= 3 };

    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;

    while (my ($sid, $name, $user) = $sth_sel->fetchrow_array ) {
        $sql = qq{ UPDATE vuln_nessus_settings_plugins SET enabled='N' WHERE sid=$sid AND family in (select id from vuln_nessus_family WHERE name='Port scanners'); };
        safe_db_write( $sql, 4 );
    
        $sql = qq{ UPDATE vuln_nessus_settings_plugins SET enabled='Y' WHERE sid=$sid AND ( id=14259 OR id=100315 OR id=10335 ); };
        safe_db_write( $sql, 4 );
        
        $config_id = get_config_id($name, $user);
        
        if ($config_id ne '')
        {
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
