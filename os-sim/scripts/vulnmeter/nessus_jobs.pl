#!/usr/bin/perl -w 
#
# License:
#
#  Copyright (c) 2003-2006 ossim.net
#  Copyright (c) 2007-2014 AlienVault
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
##    PRGM AUTHOR: Kenneth Kline
##   PROGRAM NAME: nessusCron TOOL
##   PROGRAM DATE: 01/13/2007
##  PROGM VERSION: 1.76
##  REVISION HIST:
##        02/14/2007 - FIRST VERSION OF NESSUS SUBNET SCANNER
##        02/15/2007 - ADD CRON JOB CONTROL
##        04/09/2005 - PRE 04/09 - CHANGES FOR STAT REPORTING / EMAIL / LOGGING
##        04/11/2007 - 0.5 Prep for rescanning network
##        05/30/2007 - 0.6 Add Host List Scanning
##        06/01/2007 - Scheduler Integration
##        06/04/2007 - Fix Major bug for duplicate Inserts
##        06/18/2007 - Added option to queue all subnets approved for scanning
##        07/03/2007 - Fix Cleanup of Current Scans for Scheduled Scan ( always subtracting 5 )
##        07/18/2007 - ADDED DNS Resolutions by NAMESERVERS
##        07/27/2007 - Additional DNS Resolution CHECK Fails back to nessus plugin test
##        08/02/2007 - 0.9 beta3 Net::Netmask used to split CIDR to hostlist to ensure exluding exceptions
##        08/31/2007 - 0.92 alpha - Implementing NNOC management of Subnets. I3_subnets < NNOC DB
##        10/14/2007 - 0.93 beta - Reschedule Failed Scans Fix due to SSL error connect to nessusd node
##        12/05/2007 - 0.94 beta - Coded Scan Request and restric scan hours.
##        12/27/2007 - 0.94 beta2 - Added Day restriction.
##        01/14/2008 - 0.95    Recode of the backend ( around a new jobs table to handle all job submissions).
##        02/05/2008 - 0.99   First Build to use a single routine to schedual all jobs while obeying
##                            priority, server selection, assigned sites ( nessus scanners ), 
##                            and job type priority
##        02/15/2008 - 0.99B1 Added code to track progress out to nessus_PID.work for job monitoring.
##        02/20/2008 - 0.99B2 Fixed some issues with email, and usage of timestamps
##        04/01/2008 - 0.99B2R1 Loading DB Results Additionally to Config Hash.  Allows storing more config in DB
##        04/05/2008 - 0.99a_alpha Adding Options to scan via Net::Nessus::Scanlite
##        04/21/2008 - 0.99a_B1 Improve Logging Levels and Some Minor Bug Fixes
##        04/30/2008 - 1.00   Recode HostHash routines, added MAC address, full debug. Declared 1.0
##        05/01/2008 - 1.01   Slick Change Track FeedType/PluginDate on Servers Table (Great to confirm current feeds)
##        05/02/2008 - 1.05   Cleanup old methods and document routines purpose
##	  05/07/2008 - 1.10   Recoded Load Results to properly identify hostip/hostname, scanlite returns only one
##                            Signif improvement ip/name resolution w/ (hosts/dns/namerserver pool fallback)
##                            Other Improvements around host tracker for host based statistics
##	  05/23/2008 - 1.11   Implemented additional params to eval to detect ssl error around nessus connect
##                            such as IO::Socket::INET configuration failed
##        06/01/2008 - 1.20   Code to help with run scanlite around scanner error + bug fix host table / report_id
##        06/05/2008 - 1.50   Full Production Test - quite stable
##	  06/11/2008 - 1.50.1 Fixed code around rescheduling job in event scan fails ( Currently having issue with
##                            SCANLITE and nessus 3.2.1 failing to connect to nessus server )
##        06/18/2008 - 1.60   Compliance Audit code working via client mode ( currently will force client mode when
##                            Compliance Audit is specified ).  Scanlite will not upload Audit Files currently
##        06/30/2000 - 1.61   Fixed Manual Import [*.out] file for jobs run by nessus client / client mode.
##	  06/30/2008 - 1.62   Added Compliance Audit code to replace 21156 with a nessus check ID this will
##                            provide ability to report acrosss all reports by custom script_id.
##        07/01/2008 - 1.63   Reworked switches, -q now supports queuing a compliance audit for subnet automation
##                            modified email notifications to indicate compliance audits in the subject line
##	  07/03/2008 - 1.64   Added flag to track scan type Nessus/Compliance/Both/Other Long term may work nmap
##                            Into nessus results table, build out populations of record_type fields, and updated
##                            Code to handle either/both scan types in a single job.
##        08/03/2008 - 1.65   Enabled Net::Nessus::Scanlite per INC
##        08/05/2008 - 1.66   Added Plugin List Overrides to allow custom lists for specific auditing such as ISVM
##        08/08/2008 - 1.67   Fixed Custom Plugins ( Now can Fully Override Plugin Value ) / plus fix to -q option
##        08/11/2008 - 1.68   IP's that belong to a system will not longer be run under a subnet job.
##                            System Scans do not honor scan exclusions
##                            FIX TO Selecting server as feed now "ProfessionalFeed (Direct)"
##	  08/15/2008 - 1.69   PRE 1.70 Release Include option to archive each scan to .nessus
##	  08/15/2008 - 1.70   CLEANUP UNNEEDED Perl Modules/Implement Culmulative Reporting Variable
##	  10/10/2008 - 1.71   ADDED SSH Credential scanning, Improved System/Re-occuring scan code.
##	  10/27/2008 - 1.72   Fix to Audit All subnets Now works ( Feature over-rides Notifications ). This is a
##                            means to do a single report per a plugin such as MS08-067 nessus id - (34476 34477)
##	  12/10/2008 - 1.73   Fix to ensure backslashes are inserte in message body for readability.
##                            Tired of C:WindowsSOMEFILE.TXT needed fixed before 1.0 offical
##        12/26/2008 - 1.75   Major rewrite to better organize the host_update, load_results, report_creation tasks.
##	  01/06/2009 - 1.76   Finished Update to Email Code to cleanup code
##        02/07/2009 - 1.77   Replaced all use of joins to plugins table with nessus_plugins / other where feasible.
##        02/25/2009 - 1.78   Finalizing the Top100 Host Tracking Code
##        03/04/2009 - 1.79   Fix to backslashes in message body.
##	  04/15/2009 - 1.80   Implementing HOST TRACKING CODE ( SOFTWARE/SERVICES/PROCESS/ADMIN GROUPMEMBERS/USERS )

my $version = "1.8";
use strict;


use DBI;
use Crypt::CBC; # apt-get install libcrypt-blowfish-perl
use MIME::Base64;
use Digest::MD5 qw(md5 md5_hex md5_base64);
use Net::IP;
#use Net::Nslookup;
#use Net::Netmask;
use Date::Manip;
use MIME::Lite;
use Date::Calc qw( Delta_DHMS Add_Delta_YMD Days_in_Month );
use Getopt::Std;
use Switch;
use IO::Socket;
use Data::Dumper;
use POSIX qw(strftime);

local $ENV{XML_SIMPLE_PREFERRED_PARSER} = "XML::Parser";
use XML::Simple;

#Declare constants
use constant TRUE => 1;
use constant FALSE => 0;

$|=1;

my %loginfo;                                                             #LOGWRITER RISK VALUES - PREDECLARED FOR EXTENSIVE RE-USE
   $loginfo{'1'} = "FATAL";
   $loginfo{'2'} = "ERROR";
   $loginfo{'3'} = "WARN";
   $loginfo{'4'} = "INFO";
   $loginfo{'5'} = "DEBUG";


# Sanity check
my $log_level = 4;
my $running   = int(`ps ax|grep nessus_jobs|grep perl|grep -v grep|wc -l`);
if ($running > 5)
{
  logwriter("Skip $running instances",4);
  exit;
}

#Read settings from the inprotect.cfg file
#use vars qw/%CONFIG/;
#&load_configs("/etc/inprotect.cfg");                                     #Load Inprotect Settings from File

# vuln_jobs table:

# meth_CPLUGINS -> $task_id in OpenVAS Manager
# meth_CRED     -> host alive
# meth_Ucheck   -> scan localy
# scan_ASSIGNED -> server id
# meth_Wfile    -> to send email
# meth_Wcheck   -> status message (Scan Server Selected, Timeout expired)

my %CONFIG = ();

my $dbhost = `grep ^ossim_host= /etc/ossim/framework/ossim.conf | cut -f 2 -d "="`; chomp($dbhost);
$dbhost = "localhost" if ($dbhost eq "");
my $dbuser = `grep ^ossim_user= /etc/ossim/framework/ossim.conf | cut -f 2 -d "="`; chomp($dbuser);
my $dbpass = `grep ^ossim_pass= /etc/ossim/framework/ossim.conf | cut -f 2 -d "="`; chomp($dbpass);

my $uuid = `/usr/bin/php /usr/share/ossim/scripts/vulnmeter/util.php get_system_uuid`; chomp($uuid);

$CONFIG{'DATABASENAME'} = "alienvault";
$CONFIG{'DATABASEHOST'} = $dbhost;
$CONFIG{'UPDATEPLUGINS'} = 0;
$CONFIG{'DATABASEDSN'} = "DBI:mysql";
$CONFIG{'DATABASEUSER'} = $dbuser;
$CONFIG{'DATABASEPASSWORD'} = $dbpass;
$CONFIG{'nameservers'} = "";

my ( $dbh, $sth_sel, $sql );   #DATABASE HANDLE TO BE USED THROUGHOUT PROGRAM
my %nessus_vars = ();
$dbh = conn_db();
$sql = qq{ select conf, value, AES_DECRYPT(value,'$uuid') as dvalue from config where conf like 'nessus%' or conf = 'vulnerability_incident_threshold' or conf = 'nmap_path'};
$sth_sel=$dbh->prepare( $sql );
$sth_sel->execute;
while ( my ($conf, $value, $dvalue) = $sth_sel->fetchrow_array ) {  
   if(!defined($dvalue)) {  $dvalue="";  }
   $nessus_vars{$conf} = ($dvalue ne "") ? $dvalue : $value;
}
disconn_db($dbh);

$CONFIG{'SERVERID'} = 2;
$CONFIG{'CHECKINTERVAL'} = 300;

$CONFIG{'NESSUSPATH'}     = ( defined($nessus_vars{'nessus_path'}) ) ? $nessus_vars{'nessus_path'} : "";
$CONFIG{'NESSUSHOST'}     = ( defined($nessus_vars{'nessus_host'}) ) ? $nessus_vars{'nessus_host'} : "";
$CONFIG{'NESSUSUSER'}     = ( defined($nessus_vars{'nessus_user'}) ) ? $nessus_vars{'nessus_user'} : "";
$CONFIG{'NESSUSPASSWORD'} = ( defined($nessus_vars{'nessus_pass'}) ) ? $nessus_vars{'nessus_pass'}: "";
$CONFIG{'NESSUSPORT'}     = ( defined($nessus_vars{'nessus_port'}) ) ? $nessus_vars{'nessus_port'}: "";
$CONFIG{'NMAPPATH'}       = ( defined($nessus_vars{'nmap_path'}) ) ? $nessus_vars{'nmap_path'}: "";
$CONFIG{'MYSQLPATH'}      = "/usr/bin/mysql";


$CONFIG{'DBK'} = "UWjiGNlEE0y5BxGk3dJAR7INx8IAf00MS3/3kR1QUVMazTXl4hqNPds/";
$CONFIG{'MAXPORTSCANS'} = 8;
$CONFIG{'ROOTDIR'} = $nessus_vars{'nessus_rpt_path'};



#GLOBAL VARIABLES
my $debug                    = 0;
my $debug_file               = ""; # change to a name to log all xml responses
my $semail                   = "";
my $track_progress           = 0;
my $time_to_die              = 0;
my $use_scanlite             = 0;
my $notify_by_email          = 0;
my $compliance_plugins       = "21156 21157 24760 33814 33929 33930 33931 40472 42083 46689";
my $isComplianceAudit        = FALSE;
my $isNessusScan             = FALSE;
my $isTop100Scan             = FALSE;
my $primaryAuditcheck        = "";
my $vuln_incident_threshold  = $nessus_vars{'vulnerability_incident_threshold'};
my $job_id_to_log            = "";
my $server_slot              = 1;

logwriter("out - threshold = $vuln_incident_threshold",5);

my $dbk = $CONFIG{'DBK'};

my $nessuslog = "/var/log/ossim/nessus_cron.log";       #Redirect output to the log file
#my $messages_dir = "$CONFIG{'ROOTDIR'}/email";   #FORM LETTER DIRECTORIES
my $messages_dir = "$CONFIG{'ROOTDIR'}/tmp";   #FORM LETTER DIRECTORIES
my $outdir = $CONFIG{'ROOTDIR'}."tmp";
my $xml_output = $CONFIG{'ROOTDIR'}."tmp/tmp_nessus_jobs$$.xml";

my $cred_name = "";
my $no_results = FALSE;
my $scan_timeout = FALSE;
my $omp_scan_timeout = FALSE;

my $delete_task = TRUE; # delete task after scan to check configs in use
my $delete_target = TRUE;
my $delete_credentials = TRUE;

my $exclude_hosts = "";
my @vuln_nessus_plugins;
my ($nessushost, $nessusport, $nessususer, $nessuspassword);
my ( $serverid );

my ($outfile, $targetfile, $nessus_cfg, $workfile);
my $txt_meth_wcheck = "";
my $txt_unresolved_names = "";

$outfile = "${outdir}/nessus_s$$.out";
$targetfile = "${outdir}/target_s$$";
$nessus_cfg = "${outdir}/nessus_s$$.cfg";
$workfile = "${outdir}/nessus_s$$.work";
my $nbe_path = "/usr/share/ossim/uploads/nbe/";

my %asset_data=();
my @asset_to_scan=();

# READ ARGUMENTS FROM COMMAND-LINE
my %options=();
getopts("cdij:k:l:no:qr:st:u:v:w:h?",\%options);

clean_old_omp_files( ); # clean old omp files with xml results

main( );
exit;

sub main {
    my ( $work, $outfile, $targets );
    
    if( $options{d} ) {                                              #ENABLE DEBUGGING
        use warnings;
        print "Debugging mode\n";
        $debug = 1;
        $log_level = 5;
    }

    if( $options{c} || $options{s} ) {                              #CHECK / RUN A QUEUED SCAN
        if( $options{s} ) { 
            print "SCANLITE mode\n";
            #use Net::Nessus::ScanLite_I;
            $use_scanlite = 1; 
        } else { print "Client mode\n"; }

        open(LOG,">>$nessuslog") or die "Failed to create $nessuslog: $!\n";
            *STDERR=*LOG;
            *STDOUT=*LOG;

        #GENERATE RANDOM SEED 
        srand(time() ^($$ + ($$ <<15))) ;
        # CONNECT TO DATABASE
        $dbh = conn_db();
        
        fix_vulns_tables( );    # this function is used to apply database patches
        
        # set failed hung jobs
        check_running_scans ( );
        
        #die();
        
        #CHECK FOR JOB STATUS OF "P" Pending Kill 
        check_Kill();

        #CHECK FOR JOBS PAST DUE FOR NEXT RUN AND SCHEDULE
        check_schedule();

        #USE Front End Variables
        load_db_configs();

        #PROCEED WITH JOB SELECTION
        maintenance();
        select_job();
        
        disconn_db($dbh);
        
        if($scan_timeout) {
            system("perl /usr/share/ossim/scripts/vulnmeter/cancel_scan.pl $$"); # kill all
        }
        
        #end of main
        exit;
    } elsif( $options{q} && ( $options{l} || $options{o} || $options{t} ) ) {        #QUEUE SUBNETS BY ORG OR DT LAST SCANNED
        my ( $qlan, $qtime, $winaudit, $notify ) = "";

        $dbh = conn_db();
        load_db_configs ( );        #NEED INPROTECT SETTINGS Loaded to Lookup winAuditDir path

        if ( defined ( $options{l} ) ) {
            if ( $options{l} =~ /org/i ) {
                my $sql = qq{ SELECT org_code, name FROM vuln_orgs };
                my $sth_sel=$dbh->prepare( $sql );
                $sth_sel->execute;
                print "Orgs currently loaded in Inprotect:\n";
                print "\t[ORGCODE]", ' 'x(35-length("\t[ORGCODE]")), "ORG NAME", "\n";
                while ( my ( $org_code, $orgname )=$sth_sel->fetchrow_array ) {
                    # Exclude null nessus_value records
                    print $org_code, ' 'x(35-length($org_code)), $orgname, "\n";
                }
            }elsif ( $options{l} =~ /audit/i ) {
                my $sql = qq{ SELECT name FROM vuln_nessus_audits WHERE check_type='winAuditDir' };
                my $sth_sel=$dbh->prepare( $sql );
                $sth_sel->execute;
                print "Nessus Windows Audit Files currently loaded in Inprotect:\n";
                while ( my ( $file )=$sth_sel->fetchrow_array ) {
                    print "$file\n";
                }
            }
            exit;
        }

        #READ TIME        
        if ( $options{t} =~ /now/ ) {
            $qtime = getCurrentDateTime();
        } elsif ( is_number( $options{t}) ) {
            $qtime = $options{t}
        }

        #READ DISABLE EMAIL NOTIFY
        if ( $options{n} ) { $notify = "-1"; }

        if ( $qlan ne "" || $qtime ne "" ) {
            # CONNECT TO DATABASE
            $winaudit = $options{w};
            if ( defined($winaudit) && $winaudit ne "" ) {
                if ( $winaudit !~ /^\// ) {  $winaudit = "$CONFIG{winAuditDir}/$winaudit"; }
                if ( ! -r $winaudit ) {
                    print "" . localtime(time)." - QUEUE JOBS: Failed to read audit file:\n\t[$winaudit]\n";
                    return 0;
                }
                print "winaudit=[$winaudit]\n";
            }
            #queueWork ( $options{o}, $qtime, $options{u}, $options{v}, $winaudit, $notify );
        } else {
            print "Invalid at least one argument for nessusCron.pl\n\n";
        }
        disconn_db($dbh);
        exit;
    } elsif( $options{r} ) {            #ISSUE AN INPROTECT RESET
        if ( $options{r} =~ /yes/ ) {
            $dbh = conn_db();
            # CHECK CONNECT
            resetBackend ();
            disconn_db($dbh);
        } else {
            print "Inprotect Reset:: usage nessusCron.pl -r yes\n\n";
            exit;
        }

    } elsif( $options{i} && $options{o} ) {        #IMPORT A REPORT

        open(LOG,">>$nessuslog") or die "Failed to create $nessuslog: $!\n";
            *STDERR=*LOG;
            *STDOUT=*LOG;

        #IMPORT REPORT
        if ( !defined( $options{o}) || $options{o} eq "" ) {
            print "" . localtime(time)." - no file specified to import\n";
            exit;
        } elsif ( ! -r $options{o} ) {
            print "" . localtime(time)." - load_results: Failed to read $outfile\n";
            return 0;
        }
        if ( !defined( $options{t}) || $options{t} eq "" ) {
            print "" . localtime(time)." - targetfile was not specified\n";
            exit;
        } elsif ( ! -r $options{t} ) {
            print "" . localtime(time)." - load_results: Failed to read $options{t}\n";
            return 0;
        }
        $targets = read_file( $options{t} );
        $outfile = $options{o};

        # CONNECT TO DATABASE
        $dbh = conn_db();

        $targets  =~ s/'//g;
        $targets  =~ s/"//g;

        print "SUBNET=[$targets]\nFILE.OUT=[$outfile]\n";
        #manually_import_report ( $outfile, $targets, uc( $options{j} ), $options{k} );

        disconn_db($dbh);

    } else {        #DISPLAY USAGE
print "\tUSAGE: ./nessus_jobs.pl [-cdij:k:l:no:qr:st:u:v:w:h?]\n\n";
print "          -d :: Enable Full Debug to Log and Leave Temp Files in Place after scan\n";
print "          -c :: Process Work in Queue via Nessus Client Mode\n";
#print "          -s :: Process Work in Queue via ScanLite Mode\n";
#print "          -r :: Reset Kill all jobs (REQUIRES ARGUMENT 'yes')\n";
#print "               EX: ./nessusCron.pl -r yes\n\n";
#print "          -i :: Import Scan Report in nbe format\n";
#print "                -t : TargetFile /Path/Name\n";
#print "                -o : OutFile /Path/Name\n";
#print "                -j : JobType [C|M|R|S] (Cron/Manual/Request/Scheduler)\n";
#print "                -k : JobName\n";
#print "               EX: ./nessusCron.pl -i -t /tmp/targets -o /tmp/nessus_test.out -j C -k \"My Server Farm\"\n";
#print "          -q :: Queue Subnets for Scanning !!Warning: (gun owners) this is nessus full-auto equiv!!\n";
#print "                -l : [ORG|AUDIT] list available ORG/AUDIT names\n";
#print "                -t : [now|20070101000000] (queue all subnets not scanned since [TIME]\n";
#print "                -o : [ORG] - additional filter subnets queued to only selectED [ORG]\n";
#print "                -n : override default [enabled] scan notifications to [DISABLED]\n";
#print "                -u : [CREDID]  - override default credential id for scanning\n";
#print "                -v : [VSET]    - override defaultVSET for Queue\n";
#print "                -w : [WINAUDIT] /PATH/NAME of Windows Compliance Audit to Run\n";
#print "                      if ( ! \$winaudit =~ /^\// ) { \$winaudit = \"\$config{winAuditDir)/\$winaudit\"; }\n\n";
#print "               EX: ./nessusCron.pl -q -o Finance -t now\n";
#print "               EX: ./nessusCron.pl -q -t now -n -u 31 -v 3 -w FDCC_v90_v2.audit\n\n";
print "          -h :: Get This Help\n\n";
print "        Nessus Cron is the backend interface to managing scans. nessus cron will provide scanner functions.\n";
print "        By default, this should be implemented in client mode in the following format:\n";
print "        */1 * * * * /usr/bin/perl /usr/share/ossim/scripts/vulnmeter/nessus_jobs.pl -c > /dev/null 2>&1\n\n";
#print "        Additional Tasks include:\n";
#print "                Queueing all or individual subnet zones based on dt last scanned\n";
#print "                Inprotect Reset\n";
#print "                Import Reports (Typically due to exceeding long scans  failed jobs  etc )\n\n";
        exit;
    }

    exit;        #PROGRAM NORMAL END
}

#check job queue and select job based on criteria if free scan slots
sub select_job {
    my ( $sql, $sth_sel );

    my $now= getCurrentDateTime();
    
    #my $curTime = getCurrentDateTime("time");
    #my $today = getCurrentDateTime("today");

    # get the list of all scanners
    #Select server attributes
    #$sql = qq{ SELECT id, hostname, TYPE, site_code, server_feedtype FROM vuln_nessus_servers 
    #    WHERE enabled='1' AND status='A' AND ( max_scans - current_scans > 5 )
    #    ORDER BY ( max_scans - current_scans ) DESC };
    
    $sql = qq{ SELECT port, user, PASSWORD, id, hostname, TYPE, site_code, server_feedtype FROM vuln_nessus_servers 
        WHERE enabled='1' AND status='A' };
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute(  );

    my $vuln_nessus_servers = $sth_sel->fetchall_hashref('hostname'); # load the sensor id
    $sth_sel->finish;


    foreach my $serverid (sort(keys(%{$vuln_nessus_servers}))) {
        #logwriter( "DK: Entering serverid foreach for server $serverid", 4 );
        my ( $serv_name, $serv_type, $serv_code, $feed );  

        #Get server attributes
        $serv_name = $vuln_nessus_servers->{$serverid}->{'hostname'};
        $serv_type = $vuln_nessus_servers->{$serverid}->{'TYPE'};
        $serv_code = $vuln_nessus_servers->{$serverid}->{'site_code'};
        $feed = $vuln_nessus_servers->{$serverid}->{'server_feedtype'};
        #ONLY START JOBS ASSIGNED TO SERVER OR NOT ASSIGNED A SERVER
        my $sql_filter = "AND ( t1.scan_ASSIGNED='$serverid' OR t1.scan_ASSIGNED IS Null OR t1.scan_ASSIGNED='Null' ) ";
        #IF NOT A DIRECT/PROFESSIONAL FEED - DO NOT START A COMPLIANCE AUDIT
        if ( $feed !~ /[direct|professional]/i ) {
            #$sql_filter .= "AND ( t1.meth_Wcheck IS NULL AND t1.meth_Wfile IS NULL AND t1.meth_Ucheck IS NULL) ";
        }

        # BUILD QUERY STRING FOR RESTRICTIONS
        if ( $serv_type eq "P" || $serv_code ne "" ) {
            $sql_filter .= "AND t2.site_code='$serv_code' ";
        }

        # CHECK FOR ASSIGNED JOBS TO SERVER / ZONE
        #$sql = qq{  SELECT t1.id, t1.name, t1.job_TYPE, t1.meth_TARGET, t1.scan_ASSIGNED, 
        #    t1.scan_PRIORITY, t2.site_code, failed_attempts
        #    FROM vuln_jobs t1
        #    LEFT JOIN vuln_subnets t2 ON t1.meth_TARGET = t2.CIDR
        #    WHERE t1.status = 'S' $sql_filter
        #            AND ( t2.restrict_start IS NULL OR t2.restrict_start < '$curTime')
        #        AND ( t2.restrict_cutoff IS NULL OR t2.restrict_cutoff > '$curTime')
        #        AND ( t2.restrict_day IS NULL OR t2.restrict_day = '$today' )
        #        AND ( t1.scan_NEXT IS NULL OR t1.scan_NEXT <= '$now' )
        #    ORDER BY t1.scan_PRIORITY, t1.job_TYPE DESC, t1.scan_ASSIGNED DESC LIMIT 1};

        # SORTED BY PRIORITY->JOBTYPE->ASSIGNED
        # PRIORITY 1 FIRST
        # 1) REQUESTS 2) MANUAL 3) CRON
        # ASSIGNED JOBS OVER UNASSIGNED JOBS 

        #logwriter ("Chapu DK, machacando query", 4);
        $sql = "SELECT t1.id,t1.meth_Wfile, t1.name, t1.job_TYPE, t1.meth_TARGET, t1.scan_ASSIGNED, t1.scan_PRIORITY, failed_attempts
                FROM vuln_jobs t1 
                WHERE t1.status = 'S' $sql_filter AND ( t1.scan_NEXT IS NULL OR t1.scan_NEXT <= '$now' ) ORDER BY t1.scan_PRIORITY, t1.job_TYPE DESC, t1.scan_ASSIGNED DESC LIMIT 1;";

        #logwriter( $sql, 4 );

        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute(  );

        my ( $job_id, $meth_Wfile, $job_name, $job_type, $job_targets, $job_assigned, $job_priority, $times_failed ) = $sth_sel->fetchrow_array(  );
        $sth_sel->finish;
        
        if(defined($job_id)) {
            # select server ip to check free slots
            my $used_slots = 0;
            my $scanner    = $CONFIG{'NESSUSPATH'};

            my $sql_server_ip = "SELECT inet6_ntop(s.ip) FROM vuln_nessus_servers vns, sensor s WHERE vns.hostname='$serverid' AND HEX(s.id)=vns.hostname";
            #logwriter( $sql_server_ip, 4 );
            $sth_sel = $dbh->prepare( $sql_server_ip );
            $sth_sel->execute();
            my ($server_ip) = $sth_sel->fetchrow_array();
            $sth_sel->finish;
            
            if($scanner =~ /omp\s*$/) {            
            	$used_slots = get_running_scans($serverid);
            }
            else {
                my $command_output = `ps ax | grep $scanner | grep $server_ip | egrep -v "ps ax"`;
                my @output_lines = split(/\n/, $command_output);

                foreach my $line (@output_lines ) {
                    if($line ne "") {  $used_slots++;  }
                }
            }

            logwriter( "Used slots for sensor $serverid: $used_slots", 4 );

            if( $used_slots != -1 ) {       
                $sql        = qq{ UPDATE vuln_nessus_servers SET current_scans=$used_slots WHERE hostname='$serverid' };
                safe_db_write ( $sql, 4 );
            }
            
            # see the free slots
            my $sql_slots = "SELECT ( max_scans - current_scans) FROM vuln_nessus_servers WHERE hostname='$serverid'";
            #logwriter( "SELECT ( max_scans - current_scans) FROM vuln_nessus_servers WHERE hostname='$serverid'", 4 );
            $sth_sel = $dbh->prepare( $sql_slots );
            $sth_sel->execute();
            my ($free_slots) = $sth_sel->fetchrow_array();
            $sth_sel->finish;
        
            if($free_slots<$server_slot) {
                # launch job after 15 minutes
                $sql = qq{ select NOW() + INTERVAL 15 Minute as next_scan  };

                $sth_sel = $dbh->prepare( $sql );
                $sth_sel->execute(  );
                my ( $next_run ) = $sth_sel->fetchrow_array(  );
                $sth_sel->finish;

                $next_run  =~ s/://g;
                $next_run  =~ s/-//g;
                $next_run  =~ s/\s//g;

                logwriter( "\tNot available scan slot nextscan=$next_run", 4 );
                
                $sql = qq{ UPDATE vuln_jobs SET status="S", scan_NEXT='$next_run', meth_Wcheck=CONCAT(meth_Wcheck, 'Not available scan slots<br />') WHERE id='$job_id' };
                safe_db_write ( $sql, 1 );
            }
            else {
                if ($meth_Wfile eq "1") {
                    $semail = "1"; 
                }
                else {
                    $semail = "";
                }
            
                logwriter( "id=$job_id\tname=$job_name\ttype=$job_type\ttarget=$job_targets\t"
                    ."\tpriority=$job_priority", 5 );
                # FOUND SCHEDULED SCAN

                if ( $times_failed ge 3 ) {                   #FAIL JOB TOO MANY FAILURES
                    
                    if($semail eq "1") {
                        send_error_notifications_by_email($job_id, 'Fail job too many failures'); 
                    }
                    
                    $sql = qq{ UPDATE vuln_jobs SET status='F', scan_END=now(), scan_NEXT=NULL WHERE id='$job_id' };
                    safe_db_write ( $sql, 3 );         #use insert/update routine
                }

                if($vuln_nessus_servers->{$serverid}->{'port'} ne "" && $vuln_nessus_servers->{$serverid}->{'user'} ne "" && $vuln_nessus_servers->{$serverid}->{'PASSWORD'} ne ""){
                    $CONFIG{'NESSUSUSER'} = $vuln_nessus_servers->{$serverid}->{'user'};
                    $CONFIG{'NESSUSPASSWORD'} = $vuln_nessus_servers->{$serverid}->{'PASSWORD'};
                    $CONFIG{'NESSUSHOST'} = $vuln_nessus_servers->{$serverid}->{'hostname'};
                    $CONFIG{'NESSUSPORT'} = $vuln_nessus_servers->{$serverid}->{'port'};
                }

                run_job ( $job_id, $serverid );        #RUN ONE JOB THEN QUIT
                return;
            }
        }
    }
    logwriter( "No work in scan queues to process", 4 );
    #exit;

}

#prep to to run job / call get server credentials, update dashboard, verify host_list, handle notification, setup the scan
sub run_job {
    my ( $job_id, $sel_servid ) = @_;

    my ( $sql, $sth_sel, $sth_upd );

    $serverid = get_server_credentials( $sel_servid );  #GET THE SERVER ID'S FOR WORK PROCESSING
    #if ($serverid == 0 ) {  #CHECK FOR AVAILABLE SCAN SLOTS (AN ID WOULD BE RETURNED)
    #    logwriter( "WARNING: Currently Not Enough Free scan slots to run cron job", 4 );
    #    return;
    #}

    my $startdate = getCurrentDateTime();

    #$sql = qq{ SELECT name, username, fk_name, job_TYPE, meth_TARGET, meth_VSET, meth_TIMEOUT
    $sql = qq{ SELECT name, username, notify, job_TYPE, meth_TARGET, meth_VSET, meth_TIMEOUT, meth_CRED, authorized, resolve_names
        FROM vuln_jobs WHERE id='$job_id' LIMIT 1 };
    logwriter( $sql, 5 );
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute(  );
    
    my ( $Jname, $juser, $jbfk_name, $Jtype, $host_list, $Jvset, $jtimout, $meth_CRED, $scan_locally, $resolve_names) = $sth_sel->fetchrow_array(  );
    
    # hash to manage targets: ctx and ID
    
    my @aux         = split /\n/, $host_list;
    my $default_ctx = get_default_ctx();
    my %ctxs        = get_ctxs_by_ip($job_id);
    my $host_ctx    = "";
    my $host_ip     = "";
    
    # load ctx in vuln_jobs table
    foreach my $ip_in_db (keys %ctxs) {
        $asset_data{$ip_in_db}{'ctx'} = $ctxs{$ip_in_db};
    }

    foreach my $idip (@aux) {
        if ( $idip =~ m/^([a-f\d]{32})#(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}(\/\d{1,2})?)$/i ) { #     host_id#Ip or net_id#CIDR
            $asset_data{$2}{'ctx'} = get_asset_ctx($idip);
            $asset_data{$2}{'id'}  = $1;
            logwriter("Search ctx by ID ".$idip." -> ".get_asset_ctx($idip), 4);
            push(@asset_to_scan, $2);
        }
        elsif( $idip =~ m/^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}(\/\d{1,2})?)$/i ) { # set the default ctx
            $asset_data{$1}{'ctx'} = $default_ctx;
            logwriter("Search default ctx ".$idip." -> ".$default_ctx, 4);
            push(@asset_to_scan, $idip);
        }
        else { # host name
            $idip     =~ s/[|;"']//g;
            $host_ctx = get_asset_ctx($idip);
            $host_ip  = `/usr/bin/dig '$idip' A +short | /usr/bin/tail -1`; chomp($host_ip);
            if( $host_ctx =~ m/^[a-f\d]{32}$/i && $host_ip =~ m/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/) {
                logwriter("Search ctx by name ".$idip." -> ".$host_ctx, 4);
                $asset_data{$host_ip}{'ctx'} = get_asset_ctx($idip);
                push(@asset_to_scan, $idip);
            }
        }
    }
    $host_list = join("\n", @asset_to_scan);
    
    $sth_sel->finish;

    # CHECK SID
    $sql = qq{ SELECT id FROM vuln_nessus_settings WHERE id = '$Jvset' UNION SELECT id FROM vuln_nessus_settings WHERE name = 'Default' LIMIT 1 };
    logwriter( $sql, 5 );
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute( );

    $Jvset = $sth_sel->fetchrow_array(  );
    $sth_sel->finish;
    
    # SET PROFILE ID
    $sql = qq{ UPDATE vuln_jobs SET meth_VSET=$Jvset WHERE id='$job_id' };
    safe_db_write ( $sql, 4 );
    
    
    #CODE TO RUN TOP100 HOST SCANS ( PERFERABLLY DAILY ) WILL NOT IMPORT A FULL REPORT / UPDATE CUMULATIVE ONLY )
    if ( $Jtype =~ /t/i ) {	#TYPE T is top100
	$Jtype = "M";		#RELABEL TO MANUAL (SO NOT OTHER CHANGES ARE NEEDED)
	$isTop100Scan = TRUE;
    }

    if ( $host_list eq "" ) {
        logwriter( "INVALID SCAN CONFIG\t\t( SCAN PUT ON HOLD )", 2 ); 

        $sql = qq{ UPDATE vuln_jobs SET status='H' WHERE id='$job_id' };
        safe_db_write ( $sql, 2 );            #use insert/update routine
        return;
    } elsif ( $host_list =~ /all_live_subnets/i ) {
        $notify_by_email = 0;
    }

    logwriter( "A SCHEDULED SCAN #$job_id WILL BEGIN SHORTLY FOR THE SELECTED HOSTS", 4 );
    logwriter( "\n-----------------\n<HOSTLIST>\n$host_list\n</HOSTLIST>\n", 4 );
    logwriter( "Available Server=$serverid", 4 );

    $sql = qq{ UPDATE vuln_jobs SET status='R', scan_START='$startdate' WHERE id='$job_id' };
    safe_db_write ( $sql, 4 );            #use insert/update routine

    logwriter( "Begin Script Execution: nessusScan", 5 );

    if ( $notify_by_email ) {
        #generate_email ( $job_id, "start" );	    #ie MANUAL/REQUESTS
    }

    my $enddate = setup_scan( $job_id, $Jname, $juser, $Jtype, $host_list, $Jvset, $jtimout, $jbfk_name, $meth_CRED, $scan_locally, $resolve_names);

    if ( $notify_by_email ) {
        #generate_email ( $job_id, "finish" );	    #ie MANUAL/REQUESTS
    }
    logwriter( "End Script Execution", 5 );

}

#build hostlist, remove exceptions, call scanlite, load results,  
sub setup_scan {
    # VER: 1.0 MODIFIED: 3/29/07 13:03
    my ( $job_id, $Jname, $juser, $Jtype, $target, $Jvset, $timeout, $fk_name, $meth_CRED, $scan_locally, $resolve_names) = @_;

    my ( $sql, $sth_sel, $sth_upd, $sth_ins );
    my ( $targetinfo, @results, $job_title, $nessusok, $scantime, $already_marked );
    
    $already_marked = FALSE;

    #INIT DEFAULT VALUES (CRON SCAN DEFAULTS)
    #$fk_name="";

    #UPDATE JOB_ID with SERVER PROCESS INFO
    $sql = qq{ UPDATE vuln_jobs SET scan_SERVER='$serverid', scan_PID=$$ WHERE id='$job_id' };
    safe_db_write ( $sql, 4 );            #use insert/update routine

    $job_title = "$job_id - $Jname";

    if ( $Jtype =~ /c/i ) {
        if ( $target =~ /all_live_subnets/i ) {
           $target = get_live_subnets();
        } else {
           #$fk_name = $target;
           $targetinfo = build_hostlist( $target );            #CODE TO HANDLE SCAN EXCEPTIONS
        }
        #LONG LIST OF HOSTS MAY BE CAUSING INCREASED NESSUS CLIENT LOAD
        if ( $exclude_hosts eq "" ) { $targetinfo = $target; }        #NO IPS FILTERED USE CIDR

        $sql = qq{ UPDATE vuln_jobs SET fk_name='$fk_name' WHERE id='$job_id' };
        safe_db_write ( $sql, 4 );            #use insert/update routine

    } else {

        $target =~ s/\n/\r/g;
        $target =~ s/\r\r/\r/g;

        #ATTEMPT TO GET IP'S INCASE USERS SUPPLIED HOSTNAMES
        my @tmp_hostarr = split /\r/, $target;
        foreach my $line (@tmp_hostarr ) {
            if($resolve_names eq "0") { # do not resolve names
                $targetinfo .= "$line\r";
            }
            elsif($line !~ m/^\!/) {    #
                my $isIP = FALSE;
                my $hostip = "";
                #VALID IP OR ATTEMPT REVERSE NAME TO IP
                if ( $line =~ m/^(\d\d?\d?)\.(\d\d?\d?)\.(\d\d?\d?)\.(\d\d?\d?)/ ){
                    if($1 <= 255 && $2 <= 255 && $3 <= 255 && $4 <= 255) {
                        $hostip=$line;
                        $isIP = TRUE;
                    }
                }

                # DO THE ATTEMPT TO REVERSE NAME
                if ( ! $isIP ) {
                    my $resolved_ip = resolve_name2ip ( $line );
                    if ( $resolved_ip eq "" ) { $resolved_ip = $line; }
                    print "translated NAME=[$line] to [$resolved_ip]\n";
                    $hostip = $resolved_ip;            #EITHER WAY WE AT LEAST TRIED
                }
                $targetinfo .= "$hostip\r";
            }
        }
        
        # check unresolved targets names
        
        if($txt_unresolved_names ne "") {
            $txt_unresolved_names = "Unresolved names:\n".$txt_unresolved_names;
            
            $sql = qq{ UPDATE vuln_jobs SET meth_Wcheck=CONCAT(meth_Wcheck, '$txt_unresolved_names<br />') WHERE id='$job_id' };
            safe_db_write ( $sql, 4 );  #use insert/update routine
        }
        else {
            $sql = qq{ UPDATE vuln_jobs SET meth_Wcheck='' WHERE id='$job_id' };
            safe_db_write ( $sql, 4 );  #use insert/update routine 
        }
        
        #INCASE LOOKUP FAILS MISERABLY
        if ( $targetinfo eq "" ) { $targetinfo = "$target"; }

    }

    #MERGE/FILTER Potential \r\n as \r to ensure proper split
    $targetinfo =~ s/\n/\r/g;
    $targetinfo =~ s/\r\r/\r/g;
    

    my @hostarr = split /\r/, $targetinfo;
    my $nessus_pref = get_prefs( $Jvset, $job_id );
    #MAKE IT GLOBAL FOR USE WITH INCIDENT TRACKER
    @vuln_nessus_plugins = get_plugins( $Jvset, $job_id );

    #if ( $use_scanlite ) {
    #    @results = run_scanlite_nessus($nessus_pref, \@vuln_nessus_plugins, $timeout, $Jname, $juser, \@hostarr, $Jvset, $job_id);
    #} else {
    @results = run_nessus($nessus_pref, \@vuln_nessus_plugins, $timeout, $Jname, $juser, \@hostarr, $Jvset, $job_id, $Jtype, $fk_name, $meth_CRED, $scan_locally, $resolve_names);
    #}

    $scantime = getCurrentDateTime();

    if ( check_dbOK() == "0" ) { $dbh = conn_db(); }
    logwriter("No results: $no_results",4);
    
    #UPDATE SERVER COUNT OF RUNNING SCANS
    $sql = qq{ UPDATE vuln_nessus_servers SET current_scans=current_scans-$server_slot WHERE id=$serverid AND current_scans>=$server_slot};
    safe_db_write ( $sql, 4 );            #use insert/update routine
    
    if ( $no_results == FALSE ) {
        #SUCCESSFUL NESSUS RUN
        logwriter( "[$job_title] Begin SQL Import", 5 );

        my %hostHash = pop_hosthash( \@results );          #PROCESS RESULTS INTO HOSTHAS ARRAY FOR IMPORT
        undef (@results);                                  #FREE RESOURCES FROM ORIGINAL RESULTS ARRAY
       
        if ( process_results( \%hostHash, $job_id, $job_title, $Jtype, $juser, $Jvset, $scantime, $fk_name ) ){
            logwriter( "[$job_title] [ $job_id ] Completed SQL Import, scan_PID=$$", 5 );
            
            if ($omp_scan_timeout == FALSE) {
                $sql = qq{ UPDATE vuln_jobs SET status='C', scan_PID=$$, scan_END=now(), scan_NEXT=NULL WHERE id='$job_id' };
                safe_db_write ( $sql, 4 );  #use insert/update routine
                $already_marked = TRUE;
            }
            $nessusok = TRUE;
        } else {
            logwriter( "[$job_title] Failed SQL Import", 5 );
            $nessusok = FALSE;
        }
    }
    elsif($no_results == TRUE && $txt_meth_wcheck eq "") {
        # MARK SCAN AS COMPLETED
        $sql = qq{ UPDATE vuln_jobs SET status='C', scan_PID=$$, scan_END=now(), scan_NEXT=NULL WHERE id='$job_id' AND status!='T' };
        safe_db_write ( $sql, 4 );            #use insert/update routine
        $already_marked = TRUE;
    }
    
    if (!$nessusok && $already_marked == FALSE) {
        my $retries_allowed = 0;
        if ( $CONFIG{'failedRetries'} ) { $retries_allowed = $CONFIG{'failedRetries'}; }
        if ( $Jtype =~ /c/i ) { $retries_allowed = 0; }

        if ( $retries_allowed eq "0" ) {;
            # MARK SCAN AS FAILED
            $sql = qq{ UPDATE vuln_jobs SET status='F', scan_END=now(), scan_NEXT=NULL WHERE id='$job_id' };
            safe_db_write ( $sql, 4 );            #use insert/update routine

        } else {
            # RESET FOR A RESCAN

            $sql = qq{ SELECT failed_attempts,meth_Wcheck FROM vuln_jobs WHERE id='$job_id' LIMIT 1 };
            logwriter( $sql, 4 );

            $sth_sel = $dbh->prepare( $sql );
            $sth_sel->execute(  );
            my ( $failed_count,$whyfailed ) = $sth_sel->fetchrow_array(  );
            
            if(!defined ( $whyfailed )) {
                $whyfailed = "";
            }
            
            my $tmpStatus = "S";
            if ( $failed_count ge $retries_allowed ) { $tmpStatus = "F"; }
            $failed_count += 1;

            if ( $tmpStatus eq "F" ) {
                $txt_meth_wcheck =~ s/\'/\\'/g;
    
                if($semail eq "1") {
                    send_error_notifications_by_email($job_id, $txt_meth_wcheck);
                }
                
                $sql = qq{ UPDATE vuln_jobs SET status='F', meth_Wcheck=CONCAT(meth_Wcheck, '$txt_meth_wcheck'), scan_END=now(), scan_NEXT=NULL WHERE id='$job_id' }; #MARK FAILED
                safe_db_write ( $sql, 1 );

                #my $rid = create_report ( $job_id, $Jname, $Jtype, $juser, $Jvset, $scantime, $fk_name, "1", 
                #   "SCAN Failed - hosts were unreachable or scan node went down.  Please review hosts scans, try scanning by IP if hostname was used." );

                logwriter( "Marked job [ $job_title ] as Failed", 2 );
                if ( $notify_by_email ) {
                    #generate_email ( $job_id, "scan_failed" );	    #ie MANUAL/REQUESTS
                }
            } else {
                logwriter("ATTEMPT [ $failed_count ] TO HANDLE FAILED SCANS",1);
                reschedule_scan ( $job_id ) if ($whyfailed ne "Timeout expired"); # only if not timeout
                
                if (defined($txt_meth_wcheck) && $txt_meth_wcheck ne "") { # Nmap message
                    
                    $sql = qq{ UPDATE vuln_jobs SET meth_Wcheck=CONCAT(meth_Wcheck, '$txt_meth_wcheck') WHERE id='$job_id' }; #MARK FAILED
                    safe_db_write ( $sql, 1 );
                }
            }

            exit;
        }
    }
    
    if (!$debug) {
            unlink $targetfile if -e $targetfile;
            #unlink $outfile if -e $outfile;
            unlink $workfile if -e $workfile;
            unlink $nessus_cfg if -e $nessus_cfg; 
    }
    
    return $scantime;
}

sub create_profile {
    my (%nes_prefs) = %{$_[0]};
    my (@nes_plugins) = @{$_[1]};
    my ($nessus_cfg) = $_[2];
    my ($username) = $_[3];
    my ($sid) = $_[4];

    my ($sth_sel, $sql, $nessus_id, $nessus_value, $oid);
    my($scantime)=0;

    open(PROFILE,">$nessus_cfg") or die "Failed to create $nessus_cfg: $!\n";

    $sql = qq{ SELECT autoenable FROM vuln_nessus_settings WHERE id='$sid' };
    $sth_sel=$dbh->prepare( $sql );
    $sth_sel->execute;

    my ($autoenable)=$sth_sel->fetchrow_array;

    $sql = qq{ SELECT nessus_id, value, AES_DECRYPT(value,'$uuid') as dvalue FROM vuln_nessus_settings_preferences
        WHERE category IS NULL AND sid=$sid };
    $sth_sel=$dbh->prepare( $sql );
    $sth_sel->execute;

    while (my ($nessus_id, $nessus_value, $nessus_dvalue)=$sth_sel->fetchrow_array ) {
        # Exclude null nessus_value records
        if ($nessus_dvalue) {
            print PROFILE "$nessus_id = $nessus_dvalue\n";
        }
        elsif ($nessus_value) { 
        	print PROFILE "$nessus_id = $nessus_value\n";
        }
        else {
            print PROFILE "$nessus_id\n";
        }
    }

    #$sql = qq{ SELECT t1.id, t3.oid, t1.enabled FROM vuln_nessus_settings_plugins as t1
    #  LEFT JOIN vuln_nessus_category t2 on t1.category=t2.id
    #  LEFT JOIN vuln_nessus_plugins t3 on t1.id=t3.id
    #  WHERE t2.name ='scanner' and t1.sid='$sid' order by id };
      
         $sql = qq{ SELECT t1.id, t3.oid, t1.enabled FROM vuln_nessus_settings_plugins t1
      LEFT JOIN vuln_nessus_category t2 on t1.category=t2.id, vuln_nessus_plugins t3
      WHERE t2.name ='scanner' and t1.sid=$sid and t1.id=t3.id order by id };
      
    $sth_sel=$dbh->prepare( $sql );
    $sth_sel->execute;

    print PROFILE "begin(SCANNER_SET)\n";

    while (my ($nessus_id, $oid, $nessus_value)=$sth_sel->fetchrow_array ) {
        if ($nessus_value eq "N") {
            $nessus_value="no";
        }
        else {
            $nessus_value="yes";
        }
        $nessus_id = $oid if ($oid ne ""); 
        print PROFILE " $nessus_id = $nessus_value\n";
    }
    print PROFILE "end(SCANNER_SET)\n\n";

    print PROFILE "\n\nbegin(PLUGIN_SET)\n";
    #foreach ( @nes_plugins ) {
    #    print PROFILE " $_ = yes\n";
    #}
    $sql = qq{ SELECT t1.id, t3.oid, t1.enabled FROM vuln_nessus_settings_plugins t1
      LEFT JOIN vuln_nessus_category t2 on t1.category=t2.id, vuln_nessus_plugins t3
      WHERE t2.name <>'scanner' and t1.sid=$sid and t1.id=t3.id order by id };
     # logwriter("Consulta:\n$sql",4);
 
    $sth_sel=$dbh->prepare( $sql );
    $sth_sel->execute;
    #print PROFILE "\n\nbegin(PLUGIN_SET)\n";

    while (my ($nessus_id, $oid, $nessus_value)=$sth_sel->fetchrow_array ) {
        if ($nessus_value eq "N") {
        $nessus_value="no";
        } else {
        $nessus_value="yes";
        }
        $nessus_id = $oid if ($oid ne ""); 
        print PROFILE " $nessus_id = $nessus_value\n";
    } #end loop
    print PROFILE "end(PLUGIN_SET)\n\n";
    $sth_sel->finish;

    #PLUGIN PREFS HAVE A COLON
    print PROFILE "begin(PLUGINS_PREFS)\n";
    for my $key ( sort keys %nes_prefs ) {
        if ( $key =~ /:/ ) {
            my $value = $nes_prefs{$key};
            print PROFILE " $key = $value\n";
        } else {
            # Wrong type
        }
    }
    print PROFILE "end(PLUGINS_PREFS)\n\n";

    #SERVER PREFS DOES NOT HAVE A COLON
    print PROFILE "begin(SERVER_PREFS)\n";
    for my $key ( sort keys %nes_prefs ) {
        if ( $key =~ /:/ ) {
            #wrong type
        } else {
            my $value = $nes_prefs{$key};
            print PROFILE " $key = $value\n";
        }
    }
    print PROFILE "end(SERVER_PREFS)\n\n";

}

sub filter_assets {
    my ($all_targets, $target, $targets, $ftargets, $job_id);
    my @filters=();
    my @result=();
    
    $targets = $_[0];
    $job_id  = $_[1];
    
    $sql = qq{ SELECT meth_TARGET FROM vuln_jobs WHERE id='$job_id' };
    my $sthse=$dbh->prepare( $sql );
    $sthse->execute;
    $ftargets = $sthse->fetchrow_array();
    
    my @sexceptions = split(/\n/, $ftargets);
   
    foreach $target (@sexceptions){
        if($target =~ m/^\!\d+\.\d+\.\d+\.\d+\/\d+/) {
            $target =~ s/^\!//;
            my $cmd = qq{/usr/bin/php /usr/share/ossim/scripts/vulnmeter/expand_cidr.php '$target'};
            open(GIPS,"$cmd 2>&1 |") or die "failed to fork :$!\n";
            while(<GIPS>){
                chomp;
                if(/(\d+\.\d+\.\d+\.\d+)/i){
                    push @filters,$1;
                }
            }
            close GIPS;
        }
        elsif($target =~ m/^\!\d+\.\d+\.\d+\.\d+/) {
            $target =~ s/^\!//;
            push(@filters, $target); # in filters all exceptions
        }
    }
    
    my @test_targets = split(/\n/, $targets);

    #print "filters: "; print Dumper(@filters);print "\n\n";
    foreach $target (@test_targets){

        if(!in_array(\@filters,$target)) {
    		#print "target: "; print Dumper($target);print "\n\n";
            push(@result, $target);
        }
    }
    
    $sthse->finish;

    return join("\n",@result);
}

#run scan return issues to setup scan
sub run_nessus {
    my (%nes_prefs)     = %{$_[0]};
    my (@nes_plugins)   = @{$_[1]};
    my ($timeout)       = $_[2];
    my ($jobname)       = $_[3];
    my ($username)      = $_[4];
    my (@hosts)         = @{$_[5]};
    my ($sid)           = $_[6];
    my ($job_id)        = $_[7];
    my ($Jtype)         = $_[8];
    my ($fk_name)       = $_[9];
    my ($meth_CRED)     = $_[10];
    my ($scan_locally)  = $_[11];
    my ($resolve_names) = $_[12];
    
    logwriter("Run Job Id=$job_id",4);

    #my ( $outfile, $targetfile, $nessus_cfg, $workfile );
    my ($cmd, $retval, $toc);
    my ($sth_ins);
    
    my ($target_id, $config_id, $task_id, $info_status, $status, $progress, @arr_status, @issues, $tsleep, $start_time, $current_time, $endScan, %credentials);

    #print "\nConfirmng Host List Join:\n";
    my $targets = join("\n",@hosts);
    #my @targets = @hosts;
    #print "$targets\n";
    $nessushost = $CONFIG{'NESSUSHOST'};
    
    #my $nessushostip = ($fk_name =~ m/^(\d\d?\d?)\.(\d\d?\d?)\.(\d\d?\d?)\.(\d\d?\d?)/) ? $fk_name : $nessushost;
    #
    # choose best server with less load
    #
    my ($sth_sel, $sql);
    if ($fk_name ne "") {
    	$fk_name =~ s/,/','/g;
	    $sql = qq{ SELECT hostname,id FROM vuln_nessus_servers WHERE enabled=1 AND hostname in ('$fk_name') order by  ( current_scans / max_scans ) asc limit 1 };
        
        logwriter("SELECT hostname,id FROM vuln_nessus_servers WHERE enabled=1 AND hostname in ('$fk_name') order by  ( current_scans / max_scans ) asc limit 1", 5);
        
	    $sth_sel=$dbh->prepare( $sql );
	    $sth_sel->execute;
	    my ($best_server,$best_server_id) = $sth_sel->fetchrow_array;
	    $sth_sel->finish;
	    if ($best_server ne "") {
	    	$fk_name = $best_server;
	    	$serverid = $best_server_id;
	        $sql = qq{ UPDATE vuln_jobs SET scan_SERVER='$serverid' WHERE id='$job_id' };
	        safe_db_write ( $sql, 4 );
	    }    
    }
    
    #UPDATE SERVER COUNT OF RUNNING SCANS
    $sql = qq{ UPDATE vuln_nessus_servers SET current_scans=current_scans+$server_slot WHERE id=$serverid };
    safe_db_write ( $sql, 4 );
    
    my $nessushostip = $nessushost;
    my $sensor_ip = "";
    
    logwriter("Sensor ID: $fk_name", 4);
    
    if ($fk_name =~ /^[a-f\d]{32}$/i) {
    
        $sensor_ip      = get_sensor_ip_by_id($fk_name);
        logwriter("Sensor IP: $sensor_ip", 4);
        
        $nessushostip   = $sensor_ip;
        $nessushost     = $sensor_ip;
        my @data        = get_server_data($fk_name);
        $nessusport     = $data[0] if (noEmpty($data[0]));
        $nessususer     = $data[1] if (noEmpty($data[1]));
        $nessuspassword = $data[2] if (noEmpty($data[2]));
        $nessuspassword = $data[3] if (noEmpty($data[3])); # decrypted value has preference
   	}
    logwriter( "NESSUS CLIENT: server=$nessushostip\tport=$nessusport\ntargets=$targets\n", 5 );

    unlink $targetfile if -e $targetfile;
    unlink $workfile if -e $workfile;
    unlink $nessus_cfg if -e $nessus_cfg;

    open(TARGET,">>$targetfile") or die "Failed to create $targetfile: $!\n";

    logwriter("resolve_names: $resolve_names meth_CRED: $meth_CRED scan_locally: $scan_locally fk_name: $fk_name ", 4);
    
    if($resolve_names eq "1") {
        disconn_db($dbh);
        if ($meth_CRED eq "1") {
            if ($scan_locally eq "1") {
                $targets = scan_discover($targets,"");
            } elsif ($fk_name ne "") {
                $targets = scan_discover($targets,$fk_name);
            }
        }
        $dbh = conn_db();
        $targets = filter_assets($targets, $job_id);
    }
    print TARGET "$targets"; 
    close TARGET;
	logwriter("targets: $targets", 4);
    
    if($nessushostip =~ m/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/) {
        $sql = qq{ UPDATE vuln_jobs SET meth_Wcheck=CONCAT(meth_Wcheck, 'Scan Server Selected: $nessushostip<br />') WHERE id='$job_id' };
        safe_db_write ( $sql, 4 );
    }
    
    if($targets ne "") {
        if ($CONFIG{'NESSUSPATH'} !~ /omp\s*$/) {
            logwriter("Selected sid: $sid", 4);
            create_profile( \%nes_prefs, \@nes_plugins, $nessus_cfg, $username, $sid ); 

            # version test
            $cmd = `$CONFIG{'NESSUSPATH'} --version|head -1|awk '{print \$3}'`;
            my $verbose = ($CONFIG{'NESSUSPATH'} =~ /openvas/ && $cmd =~ /^3/) ? "-v" : "-V";
            $cmd = qq{$CONFIG{'NESSUSPATH'} -qx $nessushostip $nessusport $nessususer $nessuspassword $targetfile $outfile $verbose -T nbe -c $nessus_cfg};
            logwriter("Run nessus...", 4);
            logwriter( $cmd, 4 );

            if ( ! $track_progress ) {
                #PRESCAN DISCONNECT
                logwriter( "NO TRACK PROGRESS Disconnect DB until scan completion", 3 );
                disconn_db($dbh);
            }

            $toc=0;
            eval {
                local $SIG{ALRM} = sub {
                    $toc=1;
                    die "NessusTimeout\n"; 
                }; 
                local $SIG{CHLD} = sub { if ( $? && $? > 0 ) { die "Program Exited Funny: $?\n"; } };
                logwriter("Timeout: $timeout", 5);
                alarm $timeout;
                open(LOGFILE,">>$workfile");
                open(PROC,"$cmd 2>&1 |") or die "failed to fork :$!\n";
                my (@arr);
                while(<PROC>){
                    #@arr = split/\|/;
                    #print LOGFILE "$arr[0]|$arr[1]|$arr[2]|$arr[3]";
                    print LOGFILE $_;
                    chomp; s/^ *| *$//g; s/\r//;
                    if (/.*nessus\s\:.*/ || /.*openvas-client\s\:.*/i){
                        my @tmp = split /:/;
                        $tmp[1] =~ s/^ *//g;
                        $txt_meth_wcheck = $txt_meth_wcheck.$tmp[1]."<br>";
                    }
                }
                close PROC;
                close LOGFILE;
                logwriter( "nessus_run: Scan ended\n", 3 );
                system("chown www-data '$outfile'");
                alarm 0;
            };
        }
        else {
            
            $job_id_to_log = "$job_id";
            
            %credentials = generate_credentials($job_id);
            
            logwriter("get_target_id for targets:$targets", 4);
            $target_id = get_target_id($targets, \%credentials);
            
            logwriter("get_config_id for sid:$sid", 4);
            $config_id = get_config_id($sid);
            
            logwriter("create_task for jobname, config_id, target_id: $jobname, $config_id, $target_id", 4);
            $task_id = create_task($jobname, $config_id, $target_id);
            
            $sql = qq{ UPDATE vuln_jobs SET meth_CPLUGINS='$task_id' WHERE id='$job_id' };
            safe_db_write ( $sql, 4 );

            logwriter("play_task $task_id", 4);
            play_task($task_id);
            
            #die();
            
            $tsleep = 20;
            
            $start_time = time;
            
            $endScan = 0;

            do {
                sleep($tsleep);
                $info_status = get_task_status($task_id); 
                @arr_status = split /\|/, $info_status;
                $status = shift(@arr_status);
                
                if ($status eq "Pause Requested" || $status eq "Paused") {  $tsleep = 40;  }
                else {  $tsleep = 20;   }
                
                $progress = shift(@arr_status);
                $progress =~ s/\n|\t|\r|\s+//g;
                
                logwriter("task id='$task_id' $status ($progress%)", 4); 
            
                $current_time = time;
                if( $current_time-$start_time>=$timeout ) {
                    $endScan = 1;
                }
                
            } while (($status eq "Running" || $status eq "Requested" || $status eq "Pause Requested" || $status eq "Paused") && $endScan == 0);
            
            if($endScan==1) {
                $omp_scan_timeout = TRUE;
                stop_task($task_id);
                set_job_timeout($job_id);
            }
        }

        #POST SCAN RECONNECT
        logwriter( "RECONNECT DB after scan", 3 ); 
        $dbh = conn_db();
        #RELOAD CONFIGS IN CASE OF CHANGE
        load_db_configs ( );
        
        if ($CONFIG{'NESSUSPATH'} !~ /omp\s*$/) {
            if($toc) {
                logwriter( "Timeout: get results from file $outfile", 4 );
                my @issues = get_results_from_file( $outfile );
                if ($no_results){
                    set_job_timeout($job_id);
                    logwriter( "Timeout: no results in $outfile", 4 );
                } else {
                    my %hostHash = pop_hosthash( \@issues );
                    my $scantime = getCurrentDateTime();
                    timeout(\%hostHash, $job_id, $jobname, $Jtype, $username, $sid, $scantime, $fk_name );
                }
                #warn "Nessus Scan timed out\n";
                $no_results = TRUE; # force no process results
                $scan_timeout = TRUE;
                return FALSE;
            }
            
            if ($@ ) {
                if ( $@ eq "NessusTimeout\n" ) { 
                    warn "Nessus Scan timed out\n";
                    return FALSE;
                } elsif ($@ =~ /Program Exited Funny:/ ) {
                    my $err_code = $@;
                    $err_code =~ s/Program Exited Funny://;
                    $err_code =~ s/\n//;
                    warn "Nessus Scan Server Problem [ serverid=$serverid name=$nessushost err_code=$err_code ]\n";
                    move_server_offline ( $serverid );

                    return FALSE;
                }
            };
            #logwriter( "\n\nReading the results...\n\n", 4 );
            @issues = get_results_from_file( $outfile );
        }
        else { # get results from OpenVAS Manager
            @issues = get_results_from_xml( $task_id, $target_id, \%credentials, $fk_name);
        }
    }
    else {
        $txt_meth_wcheck = "Nmap: No targets found";
        @issues = ();
        $no_results = TRUE;
    }
    return (@issues);
}

#run scan return issues to setup scan
sub run_scanlite_nessus {
    my (%nes_prefs) = %{$_[0]};
    my (@nes_plugins) = @{$_[1]};
    my ($timeout) = $_[2];
    my ($jobname) = $_[3];
    my ($username) = $_[4];
    my (@hosts) = @{$_[5]};
    my ($sid) = $_[6];
    my ($job_id) = $_[7];

    my ($cmd, $retval);
    my ($sth_ins);

    #print "\nConfirmng Host List Join:\n";
    my $targets = join(',',@hosts);

    logwriter( "SCANLITE: server=$nessushost\tport=$nessusport\ntargets=$targets\n", 5 );

    my $nessus = Net::Nessus::ScanLite->new(
        host        => $nessushost,
        port        => $nessusport,
        timeout     => 5,
        ssl         => 1  # comment or set to 1 out if using ssl
    );

    # set the profile/preferences
    $nessus->preferences( { %nes_prefs } );
    # set the plugins to use
    $nessus->plugin_set( join(';',@nes_plugins) );

    logwriter( "nessus_scan: $jobname - launching attack.", 5 );

    # wrapped in an eval block so we can set up a timer to kill a scan
    # that is running too long.
    eval {
        local $SIG{ALRM} = sub { die "NessusTimeout\n" };
        local $SIG{CHLD} = sub { if ( $? && $? > 0 ) { die "Program Exited Funny: $?\n"; } };
        alarm $timeout;
        # login to the scanner and fire up the scan
        if( $nessus->login($nessususer, $nessuspassword) ) {
            logwriter( "nessus_scan: $jobname - connected to " . $nessus->hostport, 5 );

            # FIRST FIELD IS DESIGNATED FOR LIVE STATS
            my $sth = "";

            $nessus->attack( $targets );
            logwriter( "nessus_scan: $jobname - attack ended.", 5 );
            logwriter( "nessus_scan: $jobname - Holes found: " . $nessus->total_holes, 4 );
            logwriter( "nessus_scan: $jobname - Infos found: " . $nessus->total_info, 4 );
            logwriter( "nessus_scan: $jobname - Notes found: " . $nessus->total_note, 4 );
            logwriter( "nessus_scan: $jobname - Scan Duration: " . $nessus->duration . " secs", 4 );

            alarm 0;

            if( $debug ) {
               #warn Dumper($nessus->nessus2tmpl);
            }
            #RELOAD CONFIGS IN CASE OF CHANGE
            load_db_configs ( );

            if ($@ ) {
                if ( $@ eq "NessusTimeout\n" ) {
                    warn "[$$]\tNessus Scan timed\n";
                    return FALSE;
                } elsif ($@ =~ /Program Exited Funny:/ ) {
                    my $err_code = $@;
                    $err_code =~ s/Program Exited Funny://;
                    $err_code =~ s/\n//;
                    warn "[$$]\tNessus Scan Server Problem [ serverid=$serverid name=$nessushost err_code=$err_code ]\n";

                    move_server_offline ( $serverid );

                    return FALSE;
                }
            } else {
                #A GOOD SCAN ADD UP RESULTS AND RETURN
                if ( $nessus->total_holes + $nessus->total_info + $nessus->total_note eq "0" ) {
                    $no_results = TRUE;
                    return TRUE;        #GOT TO FAKE IT AS WE HAVE NO RESULTS;
                }

                my @issues;
                my @tmp_issues = ($nessus->hole_list, $nessus->info_list, $nessus->note_list);

                #Converting ISSUE to a structure I better undertand and can process per either run client/scanlitwe
                foreach( @tmp_issues ) {
                    my $issue = $_;
                    my ($scanid, $port, $desc, $service, $app, $proto, $host ) = "";

                    #THIS RETURNES EITHER HOSTNAME OR IP IF NESSUS CAN RESOLVE DNS
                    $host = $issue->Host;
                    $scanid = $issue->ScanID;
                    $desc = $issue->Description;
                    $app = $issue->Service;
                    $proto = $issue->Proto;
                    $port = $issue->Port;
                    $desc =~ s/\\/\\\\/g;	#FIX TO BACKSLASHES

                    my $temp = {
                          Port            => $port,
                        Host            => $host,
                        Description     => $desc,
                        Service         => $app,
                        Proto           => $proto,
                        ScanID          => $scanid
                    };

                    push ( @issues, $temp );

                }

                return @issues;
            };
        } else {
            reschedule_scan ( $job_id );
            move_server_offline ( $serverid );
            logwriter( "nessus_scan: $jobname - Error running scan: " . $nessus->error, 2 );
            exit;
            #JOB WILL FAIL IF WE RETURN FROM THIS LOOP
        } # fi nessus->login()
    }; # end eval
}



#called by load_results to populate stats for report
sub update_stats {
    # VER: 1.0 MODIFIED: 3/29/07 13:03
    my ( $job_id, $job_title, $report_id, $scantime ) = @_;
    my ( $sth_sel, $sql );

    my %SCAN = ();
    my %RISKS = ( 1, 0, 2, 0, 3, 0, 4, 0, 5, 0, 6, 0, 7, 0);
    my $hostcnt = 0;
    my $runtime = 0;
    my $trend = 0;

    if ( ! $report_id ) {
        logwriter( "UPDATE STATS: failed to lookup report for scan $scantime", 2 );
        return;
    }

    $sql = qq{ SELECT scan_START, scan_END FROM vuln_jobs WHERE id = '$job_id' };
    logwriter( $sql, 5 );
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;
    my ( $start_dttm, $end_dttm ) = $sth_sel->fetchrow_array;

    $sql = qq{ SELECT count(risk) as count, risk FROM vuln_nessus_results WHERE report_id='$report_id'
        AND falsepositive<>'Y' AND scriptid <> 10180 GROUP BY risk };
    logwriter( $sql, 5 );
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;

    while( my ($count, $risk )=$sth_sel->fetchrow_array) {
        $RISKS{"$risk"} = $count;
    }

    $sql = qq{ SELECT count( distinct hostIP) as count FROM vuln_nessus_results 
        WHERE report_id='$report_id' AND falsepositive<>'Y' AND scriptid <> 10180 };
    logwriter( $sql, 5 );
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;
    ( $hostcnt ) = $sth_sel->fetchrow_array; 
    $sth_sel->finish;

    $runtime = datediff( $start_dttm, $end_dttm, "M" );

    my $update_time = getCurrentDateTime();

    $sql = "INSERT INTO vuln_nessus_report_stats ( report_id, name, iHostCnt, dtLastScanned, iScantime,
        vSerious, vHigh, vMed, vMedLow, vLowMed, vLow, vInfo, trend, dtLastUpdated ) VALUES (
        '$report_id', '$job_title', '$hostcnt', '$scantime', '$runtime', '$RISKS{1}', '$RISKS{2}', 
        '$RISKS{3}', '$RISKS{4}', '$RISKS{5}', '$RISKS{6}', '$RISKS{7}', '$trend', '$update_time' ); ";

    safe_db_write ( $sql, 4 );

}

#called to update host tracker, most recent scan info for hosts
sub update_host_record {
    # VER: 1.7 MODIFIED: 12/29/08 15:18
    my (%RISKS) = %{$_[0]};
    my ($mac_address) = $_[1];
    my ($hostname) = $_[2];
    my ($hostip) = $_[3];
    my ($os) = $_[4];
    my ($workgroup) = $_[5];
    my ($ip_org) = $_[6];
    my ($ip_site) = $_[7];
    my ($report_id) = $_[8];
    my ($scantime) = $_[9];
    my ($localcheck) = $_[10];
    my ($host_rating) = $_[11];
    my ($update_stats) = $_[12];

    my ( $sql, $sql_select, $sth_sel );
    my $scan_state = "Passed";  #SET INITAL STATE TO PASS
    my ( $host_id, $eReportID, $eCReportID ) = "0";
    my $hostText = "";

    #TRICKY PART ( SCAN / COMPLIANCE / BOTH )  
    if ( defined ( $hostname ) && $hostname ne "" ) {
        $sql = qq{ SELECT id, report_id, creport_id FROM vuln_hosts WHERE hostname = '$hostname' LIMIT 1 };
	logwriter( $sql, 5 );
        $sth_sel = $dbh->prepare( $sql );
	$sth_sel->execute;
        ( $host_id, $eReportID, $eCReportID ) = $sth_sel->fetchrow_array;
	$hostText = "'$hostname'";
    } else {
	$hostText = "NULL";
    }

    #CHOSE HOW TO UPDATE/ADD
    if ( $update_stats && $isComplianceAudit ) {
        $eReportID  = $report_id;
        $eCReportID = $report_id;
    } elsif ( $isComplianceAudit ) {
        $eCReportID = $report_id;
    } elsif ( $update_stats ) {
        $eReportID  = $report_id;
    } else {

    }

    $os=~ s/^\s+//;  #MAY HAVE PASSED " " SO IT WOULD NOT BE NULL
    #BUILD STATS ARRAY / GET SCANTIME FROM REPORT RESULTS
    if ( defined($workgroup) && $workgroup ne "" ) {
	$workgroup = "'$workgroup'";
    } else {
	$workgroup = "NULL";
    }


    if ( $ip_org eq "" ) { $ip_org = "unknown"; }
    my $risk_value = $RISKS{1} + $RISKS{2};
    if ( $risk_value > 0 ) { $scan_state = "Pending Fixes"; }

    if ( defined( $host_id ) && $host_id > 0 ) {
        my $os_sql = " ";  #ONLY UPDATE OS FIELD IF FOUND ( SOME PROFILES MAY NOT TEST )
        if ( defined( $os ) && $os ne "" ) { $os_sql = " os='$os',"; }
        $sql = "UPDATE hosts SET hostname='$hostname', hostip='$hostip',$os_sql workgroup=$workgroup,
	    scanstate='$scan_state', site_code='$ip_site', ORG='$ip_org', lastscandate='$scantime',
	    report_id='$eReportID', creport_id='$eCReportID', inactive='0' WHERE id='$host_id';";
        safe_db_write ( $sql, 4 );
    } else {
        $sql = "INSERT INTO hosts ( hostip, hostname, status, os, workgroup, site_code, ORG,
            scanstate, report_id, creport_id, lastscandate, createdate ) VALUES (
            '$hostip', '$hostname', 'Production', '$os', $workgroup, '$ip_site', '$ip_org',
            '$scan_state', '$eReportID', '$eCReportID', '$scantime', '$scantime' );";
        safe_db_write ( $sql, 4 );
    }

    #ADD ENTRY TO VULN_SUMMARY TABLE
    $sql = "INSERT INTO vuln_host_stats ( report_id, host_id, hostip, hostname, scantime, localChecks, access_rating,
        vSerious, vHigh, vMed, vMedLow, vLowMed, vLow, vInfo ) VALUES (
        '$report_id', '$host_id', '$hostip', $hostText, '$scantime', '$localcheck', '$host_rating',
        '$RISKS{1}', '$RISKS{2}', '$RISKS{3}', '$RISKS{4}', '$RISKS{5}', '$RISKS{6}', '$RISKS{7}' );";

    safe_db_write ( $sql, 4 );

}


# extract host info <- assuming msg from plugin #20811 is supplied
sub update_vuln_host_software {
    # VER: 1.0 MODIFIED: 4/15/09 15:43
    my ( $host_id, $scantime, $txt_msg ) = @_;

    #changed $domain to $wgroup ( did not want to confuse with domain field per nessus nbe results

    my %software;

    my ( $sql, $sql2 );
    $sql = qq{ UPDATE vuln_host_software SET inactive='1' WHERE host_id='$host_id'; };
    safe_db_write ( $sql, 4 );    

    $sql = qq{ SELECT vuln_software_name FROM vuln_host_software WHERE host_id='$host_id'; };
    logwriter( "$sql", 5 );
    my $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;

    while ( my ( $name ) = $sth_sel->fetchrow_array ) {
	$software{$name} = "1";
    }

    my $strLen = length ( $txt_msg );
    my $pattern = "The following software are installed on the remote host :";
    my $offset = rindex ( $txt_msg, $pattern ) + length( $pattern );
    $txt_msg = substr ($txt_msg,$offset) ;

    my @arrMSG = split /\\n\\n|\n\n|\r/, $txt_msg;
    foreach my $line (@arrMSG) {
        $line =~ s/\#.*$//;
        chomp($line);
        $line =~ s/\s+$//;
        $line =~ s/^\s+//;
        #logwriter( "nessus_scan: LINE=[$line]", 5 );
	next if $line eq ""; 
	my ( $product, $version ) = "-1";

	if ( $line =~ /\[/ ) {
	    my @temp=split(/\[/,$line,2);
            $temp[0] =~ s/\s+$//;
	    $temp[0] =~ s/^\s+//;
	    $temp[1] =~ s/\s+$//;
	    $temp[1] =~ s/^\s+//;
	    $temp[1] =~ s/version\s//;
	    $temp[1] =~ s/\]//;
	    $product = $temp[0];
	    $version = $temp[1];
	} else {
	    $product=$line;
	    $version="unknown";
	}

	if ( defined ( $software{$product} ) && $software{$product} ne "" ) {
	    $sql2 = qq{ UPDATE vuln_host_software SET version='$version', dtLastSeen='$scantime', inactive='0'
		WHERE host_id='$host_id' and vuln_software_name='$product'; };
	} else {
	    $sql2 = qq{ INSERT INTO vuln_host_software ( host_id, vuln_software_name, version, dtLastSeen, inactive
		) VALUES (
		'$host_id', '$product', '$version', '$scantime', '0' ); };
	}
        safe_db_write( $sql2, 5 );

	#print "product=[$product]\t\tversion=[$version]\n";
        next;
    }

    #print "lenth=$strLen\toffset=$offset\n\ntxt_msg=[$txt_msg]\n";

}

# extract host info <- assuming msg from plugin #20811 is supplied
sub update_host_service {
    # VER: 1.0 MODIFIED: 4/15/09 15:43
    my ( $host_id, $scantime, $txt_msg ) = @_;

    #changed $domain to $wgroup ( did not want to confuse with domain field per nessus nbe results

    my %service;

    my ( $sql, $sql2 );
    $sql = qq{ UPDATE vuln_host_services SET inactive='1' WHERE host_id='$host_id'; };
    safe_db_write ( $sql, 4 );    

    $sql = qq{ SELECT service_name FROM vuln_host_services WHERE host_id='$host_id'; };
    logwriter( "$sql", 5 );
    my $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;

    while ( my ( $name ) = $sth_sel->fetchrow_array ) {
	$service{$name} = "1";
    }

    my $strLen = length ( $txt_msg );
    my $pattern = "Plugin output :";
    my $offset = rindex ( $txt_msg, $pattern ) + length( $pattern );
    $txt_msg = substr ($txt_msg,$offset) ;

    my @arrMSG = split /\\n\\n|\n\n|\r/, $txt_msg;
    foreach my $line (@arrMSG) {
        $line =~ s/\#.*$//;
        chomp($line);
        $line =~ s/\s+$//;
        $line =~ s/^\s+//;
        #logwriter( "nessus_scan: LINE=[$line]", 5 );
	next if $line eq ""; 
	my ( $svc_name, $display_name ) = "-1";

	if ( $line =~ /\[/ ) {
	    my @temp=split(/\[/,$line,2);
            $temp[0] =~ s/\s+$//;
	    $temp[0] =~ s/^\s+//;
	    $temp[1] =~ s/^\s+//;
	    $temp[1] =~ s/\]//;
	    $temp[1] =~ s/\s+$//;
	    $display_name = $temp[0];
	    $svc_name = $temp[1];
	} else {
	    $svc_name=$line;
	    $display_name = $svc_name;
	}

	if ( defined ( $service{$svc_name} ) && $service{$svc_name} ne "" ) {
	    $sql2 = qq{ UPDATE vuln_host_services SET display_name='$display_name', dtLastSeen='$scantime', inactive='0'
		WHERE host_id='$host_id' and service_name='$svc_name'; };
	} else {
	    $sql2 = qq{ INSERT INTO vuln_host_services ( host_id, service_name, display_name, dtLastSeen, inactive
		) VALUES (
		'$host_id', '$svc_name', '$display_name', '$scantime', '0' ); };
	}
        safe_db_write( $sql2, 4 );

	#print "service=[$svc_name]\t\tdisplay_name=[$display_name]\n";
        next;
    }

    #print "lenth=$strLen\toffset=$offset\n\ntxt_msg=[$txt_msg]\n";

}

# extract host info <- assuming msg from plugin #10902 is supplied
sub update_host_admins {
    # VER: 1.0 MODIFIED: 4/15/09 15:43
    my ( $host_id, $scantime, $txt_msg ) = @_;

    #changed $domain to $wgroup ( did not want to confuse with domain field per nessus nbe results

    my %admins;

    my ( $sql, $sql2 );
    $sql = qq{ UPDATE vuln_host_admingroup SET inactive='1' WHERE host_id='$host_id'; };
    safe_db_write ( $sql, 4 );    

    $sql = qq{ SELECT member_domain, member_name FROM vuln_host_admingroup WHERE host_id='$host_id'; };
    logwriter( "$sql", 5 );
    my $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;

    while ( my ( $domain, $name ) = $sth_sel->fetchrow_array ) {
	$admins{"$domain-$name"} = "1";
    }

    my $strLen = length ( $txt_msg );
    my $pattern = " group :";
    my $offset = rindex ( $txt_msg, $pattern ) + length( $pattern );
    $txt_msg = substr ($txt_msg,$offset) ;

    my @arrMSG = split /\\n\\n|\n\n|\r/, $txt_msg;
    foreach my $line (@arrMSG) {
        $line =~ s/\#.*$//;
        chomp($line);
	$line =~ s/^\s+//;
	$line =~ s/\(//;
	$line =~ s/\)//;
	$line =~ s/^-//;
        $line =~ s/\s+$//;
        $line =~ s/^\s+//;
        #logwriter( "nessus_scan: LINE=[$line]", 5 );
	next if $line eq ""; 
	my ( $tmp_name, $accDomain, $accUser, $accType ) = "-1";
	#print "line1=$line\n";

	if ( $line =~ /\\/ ) {
	    my @temp=split(/\\/,$line,2);
	    $accDomain = $temp[0];
	    if ( $temp[1] =~ /\sgroup/i ) {
		$accType = "G";
	    } else {
		$accType = "U";
	    }
	    $temp[1] =~ s/\sgroup//i;
	    $temp[1] =~ s/\suser//i;
	    $temp[1] =~ s/\s+$//;
	    $accUser = $temp[1];

	    $tmp_name = "$accDomain-$accUser";
	}

	#print "'$accDomain', '$accUser', '$accType'\n";

	if ( defined ( $admins{$tmp_name} ) && $admins{$tmp_name} ne "" ) {
	    $sql2 = qq{ UPDATE vuln_host_admingroup SET dtLastSeen='$scantime', inactive='0'
		WHERE host_id='$host_id' and member_domain='$accDomain' AND member_name='$accUser'; };
	} else {
	    $sql2 = qq{ INSERT INTO vuln_host_admingroup ( host_id, member_domain, member_name, member_type,
		dtLastSeen, inactive ) VALUES (
		'$host_id', '$accDomain', '$accUser', '$accType', '$scantime', '0' ); };
	}
        safe_db_write( $sql2, 4 );

	#print "service=[$svc_name]\t\tdisplay_name=[$display_name]\n";
        next;
    }

    #print "lenth=$strLen\toffset=$offset\n\ntxt_msg=[$txt_msg]\n";

}


# extract host info <- assuming msg from plugin #10860 is supplied
sub update_vuln_host_users {
    # VER: 1.0 MODIFIED: 11/15/07 15:43
    my ( $host_id, $scantime, $txt_msg ) = @_;

    #changed $domain to $wgroup ( did not want to confuse with domain field per nessus nbe results

    my %localusers;

    my ( $sql, $sql2 );
    $sql = qq{ UPDATE vuln_host_users SET inactive='1' WHERE host_id='$host_id'; };
    safe_db_write ( $sql, 4 );    

    $sql = qq{ SELECT uid, username FROM vuln_host_users WHERE host_id='$host_id'; };
    logwriter( "$sql", 5 );
    my $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;

    while ( my ( $uid, $name ) = $sth_sel->fetchrow_array ) {
	$localusers{"$uid-$name"} = "1";
    }

    my $strLen = length ( $txt_msg );
    my $pattern = "Plugin output :";
    my $pattern2 = "Note that, ";
    my $offset = rindex ( $txt_msg, $pattern ) + length( $pattern );
    my $datalen = ( index ( $txt_msg, $pattern2 ) - $offset );
    #print "offset=$offset\tpattern=$datalen\n";
    $txt_msg = substr ($txt_msg,$offset,$datalen) ;
    #print "test=[$txt_msg]\n";

    my @arrMSG = split /\\n\\n|\n\n|\r/, $txt_msg;
    foreach my $line (@arrMSG) {
        $line =~ s/\#.*$//;
        chomp($line);
	$line =~ s/^\s+//;
	$line =~ s/\(//;
	$line =~ s/\)//;
	$line =~ s/,//;
	$line =~ s/^-//;
        $line =~ s/\s+$//;
        $line =~ s/^\s+//;
        #logwriter( "nessus_scan: LINE=[$line]", 5 );
	next if $line eq ""; 
	my ( $tmp_name, $accUID, $accUser ) = "0";
	print "line1=$line\n";

	if ( $line =~ /id/ ) {
	    my @temp=split(/id/,$line);
	    $temp[0] =~ s/\s+$//;
	    $temp[1] =~ s/^\s+//;
	    my @temp2=split(/\s/,$temp[1]);
	    $accUID = $temp2[0];
	    $accUser = $temp[0];
	    $tmp_name = "$accUID-$accUser";
	}

	print "'$accUID', '$accUser'\n";

	if ( defined ( $localusers{$tmp_name} ) && $localusers{$tmp_name} ne "" 
	    && defined( $accUID) && $accUID ge 1 ) {
	    $sql2 = qq{ UPDATE vuln_host_users SET dtLastSeen='$scantime', inactive='0'
		WHERE host_id='$host_id' and uid='$accUID' AND username='$accUser'; };
	} else {
	    $sql2 = qq{ INSERT INTO vuln_host_users( host_id, uid, username, disabled,
		dtLastSeen, inactive ) VALUES (
		'$host_id', '$accUID', '$accUser', '0', '$scantime', '0' ); };
	}
        safe_db_write( $sql2, 4 );

	#print "service=[$svc_name]\t\tdisplay_name=[$display_name]\n";
        next;
    }

    #print "lenth=$strLen\toffset=$offset\n\ntxt_msg=[$txt_msg]\n";

}


# extract host info <- assuming msg from plugin #10913 is supplied
sub update_host_disabled_users {
    # VER: 1.0 MODIFIED: 11/15/07 15:43
    my ( $host_id, $scantime, $txt_msg ) = @_;

    #changed $domain to $wgroup ( did not want to confuse with domain field per nessus nbe results

    my %localusers;

    my ( $sql, $sql2 );

    $sql = qq{ SELECT uid, username FROM vuln_host_users WHERE host_id='$host_id'; };
    logwriter( "$sql", 5 );
    my $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;

    while ( my ( $uid, $name ) = $sth_sel->fetchrow_array ) {
	$localusers{"$uid-$name"} = "1";
    }

    my $strLen = length ( $txt_msg );
    my $pattern = "The following local user accounts have been disabled :";
    my $pattern2 = "Other references :";
    my $offset = rindex ( $txt_msg, $pattern ) + length( $pattern );
    my $datalen = ( index ( $txt_msg, $pattern2 ) - $offset );
    #print "offset=$offset\tpattern=$datalen\n";
    $txt_msg = substr ($txt_msg,$offset,$datalen) ;
    #print "test=[$txt_msg]\n";

    my @arrMSG = split /\\n\\n|\n\n|\r/, $txt_msg;
    foreach my $line (@arrMSG) {
        $line =~ s/\#.*$//;
        chomp($line);
	$line =~ s/^\s+//;
	$line =~ s/^-//;
        $line =~ s/\s+$//;
        $line =~ s/^\s+//;
        #logwriter( "nessus_scan: LINE=[$line]", 5 );
	next if $line eq ""; 
	print "line1=$line\n";

	my $accUser = $line;

	if ( defined ( $accUser ) && $accUser ne "" ) {
	    print "USER=[$line]\n";
	    $sql2 = qq{ UPDATE vuln_host_users SET dtLastSeen='$scantime', inactive='0', disabled='1'
		WHERE host_id='$host_id' AND username='$accUser'; };
            safe_db_write( $sql2, 4 );
	}

	#print "service=[$svc_name]\t\tdisplay_name=[$display_name]\n";
        next;
    }

    #print "lenth=$strLen\toffset=$offset\n\ntxt_msg=[$txt_msg]\n";

}


#handle failed scans according to settings
sub reschedule_scan {
    my ( $job_id ) = @_;

    my ( $sql, $sth_sel, $now );
    
    $now = getCurrentDateTime();
    
    my $year  = substr($now,0,4);
    my $month = substr($now,4,2);
    my $day   = substr($now,6,2);
    
    my $h     = substr($now,8,2);
    my $m     = substr($now,10,2);
    my $s     = substr($now,12,2);
    
    #RECODED TO ELIMINATE THE NON-SENSE ( NO RESCAN IMMEDIATELY (HOST LIKELY DEAD / OFFLINE )
    $sql = qq{ SELECT DATE_ADD( '$year-$month-$day $h:$m:$s', INTERVAL 1 HOUR ) };

    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute(  );
    my ( $next_run ) = $sth_sel->fetchrow_array(  );

    $next_run  =~ s/://g;
    $next_run  =~ s/-//g;
    $next_run  =~ s/\s//g;

    logwriter( "\tnextscan=$next_run", 4 );
   
    $sql = qq{ UPDATE vuln_jobs SET scan_SERVER=0, status="S", scan_START=NULL, scan_END=NULL, scan_NEXT='$next_run',
            scan_PID='0', report_id='0', failed_attempts=failed_attempts+1 WHERE id='$job_id' };

    safe_db_write ( $sql, 1 );

}


#handle failed server nodes, to keep database accurate
sub move_server_offline {
    my ( $tmp_server_id ) = @_;

    my $sql = qq{ UPDATE vuln_nessus_servers SET max_scans='0', current_scans='0', enabled='0'
        WHERE id = '$tmp_server_id' LIMIT 1 };

    safe_db_write ( $sql, 2 );

    logwriter( "TAKING SERVER serverid=$serverid name=$nessushost OFFLINE DUE TO SCAN ERROR", 2 );
    logwriter( "\t$nessushost: PLEASE RESTART NESSUSD", 2 );

}

#import a scan run manually
sub manually_import_report {
    my ( $outfile, $targets, $job_type, $job_title ) = @_;

    my ( $sql, $sth_sel, $sth_ins, $Jvset, $juser, $fk_name );

    $Jvset = 1;
    $juser = "Admin";
    if ( !$job_title ) { 
        $job_title = "CRON - $targets";
    }

    # SELECT OLDEST RECORD FROM CRON QUEUE

    if ( $job_type eq "C" ) {
        #$sql = qq{ SELECT id, CIDR, site_code, location
        #    FROM vuln_subnets WHERE CIDR='$targets' LIMIT 1};
        #logwriter( $sql, 5 );
        #$sth_sel = $dbh->prepare( $sql );
        #$sth_sel->execute(  );

        #my ( $Lid, $Lname, $Lsite, $Llocation ) = $sth_sel->fetchrow_array(  );
        #$sth_sel->finish;
        #if ( ! $Lid ) {
        #    logwriter( "Invalid subnet specified [$targets].", 1 );
            return 0;
        #}
        #$fk_name = $Lname;
    }

    $sql = qq{ SELECT id FROM vuln_jobs WHERE status="R" AND name='$job_title' LIMIT 1};
    logwriter( $sql, 5 );
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute(  );
    my ( $job_id ) = $sth_sel->fetchrow_array(  );

    if ( !$job_id ) {
        $sql = qq{ INSERT INTO vuln_jobs ( name, username, job_TYPE, meth_TARGET, 
            meth_VSET, status, authorized ) VALUES ( 
                '$job_title', '$juser', '$job_type', '$targets', 
            '$Jvset', 'I', '1' ); };
        logwriter( $sql, 4 );
        $sth_ins = $dbh->prepare( $sql );
        $sth_ins->execute(  );

        $sql = qq{ SELECT id FROM vuln_jobs WHERE status="I" AND name='$job_title' LIMIT 1};
        logwriter( $sql, 5 );
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute(  );
        $job_id = $sth_sel->fetchrow_array(  );

        if ( ! $job_id ) { 
            logwriter( "ERROR Creating Job.", 4 );
            return 0;
        }
    }

    my @results = get_results_from_file( $outfile );

    my $scantime = getCurrentDateTime();

    if ( $no_results == FALSE ) {  #SUCCESSFUL NESSUS RUN
        logwriter( "[$job_title] Begin SQL Import", 4 );

        my %hostHash = pop_hosthash( \@results );          #PROCESS RESULTS INTO HOSTHAS ARRAY FOR IMPORT
        undef (@results);                                  #FREE RESOURCES FROM ORIGINAL RESULTS ARRAY

        if ( process_results( \%hostHash, $job_id, $job_title, $job_type, $juser, $Jvset, $scantime, $fk_name ) ){
            logwriter( "[$job_title] [ $job_id ] Completed SQL Import", 4 );

            $sql = qq{ UPDATE vuln_jobs SET status='C' WHERE id='$job_id' };
            safe_db_write ( $sql, 4 );                    #use insert/update routine   
        } else {
            logwriter( "[$job_title] Failed SQL Import", 2 );
        }
    }

    $sth_sel->finish;

}

#queue subnets for scanning ( typically used for monthly network audit feeds subnet/site/org/executive summary reports
sub queueWork {
    my ( $LanORG, $sql_time, $useCred, $usevSet, $winaudit, $notify ) = @_;

    my ($sql, $sql1, $sql_filter, $sql_limit, $sth_sel, $sth_upd );

    #OBEY FILTERS IF ( SETUP PER INPROTECT_SETTINGS )
    my $restricted_segments = $CONFIG{'filterSubnetPurpose'};


    #FORCE OVERRIDE CREDENTIAL/PULL DEFAULT FROM DB
    if ( !defined( $useCred ) || $useCred eq "" ) { 
        my $credAudit = $CONFIG{'credAudit'};
        if ( defined ( $credAudit ) && $credAudit >= 1 ) {
            $useCred = "'$credAudit'";
        } else {
            $useCred = "Null";
        }
    }

    if ( !defined( $winaudit ) || $winaudit eq "" ) { 
        $winaudit = "Null";
    } else {
        $winaudit = "'$winaudit'";
    }

    #FORCE OVERRIDE PROFILE/PULL DEFAULT FROM DB
    if ( !defined( $usevSet ) || $usevSet eq "" ) { $usevSet = $CONFIG{'defaultVSet'}; }

    #DISABLE NOTIFICATION
    if ( defined( $notify ) && $notify eq "-1" ) { 
        $notify = "-1";
    } else {
        $notify = "";
    }

    if ( !defined ( $sql_time ) || $sql_time eq "" ) {
        $sql_time = getCurrentDateTime();
    }

    if ( defined ( $LanORG ) && $LanORG ne "" ) { $sql_filter = " AND t1.ORG = '$LanORG'"; } else { $sql_filter = " "; }
    if ( $debug ) { $sql_limit = " LIMIT 1"; } else { $sql_limit = " "; }
    #AND t1.auditable = 'Y'

    $sql = qq{ SELECT t1.id, t1.CIDR, t1.purpose 
        FROM vuln_subnets t1
        LEFT JOIN ( SELECT fk_name, max( scantime ) AS currentscan
            FROM vuln_nessus_reports GROUP BY fk_name ) t2 ON t2.fk_name = t1.CIDR
        LEFT JOIN vuln_nessus_reports t3 on t2.currentscan = t3.scantime
        LEFT JOIN vuln_jobs t4 ON t3.report_id = t4.report_id
        WHERE t1.status != 'available'
            $sql_filter AND t1.tiScanApproval = '1'  AND t1.serial_flag = 'N' 
            AND ( t4.scan_END < '$sql_time' AND ( t4.status = 'C' OR t4.status = 'F' OR t4.status IS NULL ))
            ORDER BY t4.scan_END DESC $sql_limit };

    logwriter($sql, 4 );
    $sth_sel=$dbh->prepare( $sql );
    $sth_sel->execute;

    while( my ( $subID, $subnetName, $purpose ) = $sth_sel->fetchrow_array) {
        if ( is_number( $subID ) && defined($subnetName) ) {

	    if ( defined ( $restricted_segments ) && $restricted_segments =~ /$purpose/i ) {
		logwriter( "\tRESTRICTED [ NOT QUEUED ] subID: $subID\tNAME: $subnetName\n", 2 );
	    } else {
                $sql1 = qq{ INSERT INTO vuln_jobs ( name, username, fk_name, job_TYPE, meth_SCHED, meth_TARGET, meth_CRED,
	            meth_VSET, scan_SUBMIT, meth_Wcheck, status, notify, authorized
                ) VALUES (
                    'CRON - $subnetName', 'Admin', '$subnetName', 'C', 'C', '$subnetName', $useCred, 
                    '$usevSet', '$sql_time', $winaudit, 'S', '$notify', '1' ); };
                safe_db_write ( $sql1, 5 );            #use insert/update routine
                logwriter( "\tQueued subID: $subID\tNAME: $subnetName\n", 3 );
	    }
        }
    }

    $sth_sel->finish;

    #RESET REPORT SENT FOR SITES TABLES
    #$sql = qq{ UPDATE vuln_sites SET report_sent='0' };
    #safe_db_write ( $sql, 5 );            #use insert/update routine

    #$sql = qq{ UPDATE vuln_orgs SET report_sent='0' };
    #safe_db_write ( $sql, 5 );            #use insert/update routine
}

#clean failed jobs from jobs table
sub resetBackend {
    my ( $sql );
    print "WARNING: Inprotect Reset Issued!\n";

    $sql = qq{ UPDATE vuln_jobs SET status = 'C', scan_END=scan_START WHERE status='R' };
    safe_db_write ( $sql, 4 );            #use insert/update routine

    $sql = qq{ UPDATE vuln_nessus_servers SET checkin_time=now(), current_scans=0 };
    safe_db_write ( $sql, 4 );            #use insert/update routine

    my @tmpFileList=(
        "$CONFIG{'ROOTDIR'}/tmp/nessus_s*.out",
        "$CONFIG{'ROOTDIR'}/tmp/nessus_s*.work",
        "$CONFIG{'ROOTDIR'}/tmp/nessus_s*.cfg",
        "$CONFIG{'ROOTDIR'}/tmp/nessus-*",
        "$CONFIG{'ROOTDIR'}/tmp/target_s*");

    foreach my $tmpList (@tmpFileList) {
        my @tmpFiles=glob($tmpList);

        foreach my $tmpFile (@tmpFiles)        {
                unlink($tmpFile);
        }
    }
}

#prep host list move cidr based lists to ip list to check for exceptions
sub build_hostlist {
    my ( $CIDR ) = @_;

    my ( $sql, $sth_sel );

    my $block = new Net::Netmask ( $CIDR );

    my $host_list = "";
    return $host_list; # DELETE
    
    foreach my $ip ( $block->enumerate( ) ){
        $host_list .= "$ip\n";
    }

    #FILTER BASE & BROADCASE ADDRESS
    my $base = $block->base();
    my $bcast = $block->broadcast();
    $host_list =~ s/$base\n//g;
    $host_list =~ s/$bcast\n//g;

    $sql = qq{ SELECT ip_range FROM vuln_scan_exclusions WHERE active = '1' ORDER BY ip_range };
    logwriter( $sql, 5 );
    $sth_sel=$dbh->prepare( $sql );
    $sth_sel->execute;

    while ( my ( $ex_CIDR )=$sth_sel->fetchrow_array ) {
        my $exBlock = new Net::Netmask ( $ex_CIDR );
        if ( defined( $exBlock )) {
            foreach my $ex_ip ( $exBlock->enumerate( ) ) {
                if ( $host_list =~ /$ex_ip\n/ ) {
                    $exclude_hosts .= "$ex_ip\n";
                }
                $host_list =~ s/$ex_ip\n//g;
            }
        }
    }

    $sql = qq{ SELECT distinct( hostip ) FROM vuln_system_hosts
        WHERE INET_ATON(hostip) > INET_ATON('$base') AND INET_ATON(hostip) < INET_ATON('$bcast'); };
    logwriter( $sql, 5 );
    $sth_sel=$dbh->prepare( $sql );
    $sth_sel->execute;

    while ( my ( $ex_ip )=$sth_sel->fetchrow_array ) {
        if ( $host_list =~ /$ex_ip\n/ ) {
           $exclude_hosts .= "$ex_ip\n";
        }
        $host_list =~ s/$ex_ip\n//g;
    }

    $sth_sel->finish;
    logwriter( "EXCLUDING HOSTS [$exclude_hosts]", 4 );

    return $host_list;
}

#pop hosthash will process the results to make the most of the data.  This will improve reporting / tracking of scanned hosts
#this is the heart of the inprotect code ( this feeds host_tracking (culumative/results) database tables ).
sub pop_hosthash {
    my (@issues ) = @{$_[0]};
    
    logwriter("Number of results: ".$#issues, 5);
    

    my ( $sth_sel, $sql, $domain, $hostname, $mac_address, $report_key, $report_type, $record_type );

    # WAITED TO LOAD CUSTOM RISK TO LOAD RESULTS ( IMPORT ROUTINES MAY USE IT )
    my $custom_risks = get_custom_risks( );

    my %hostHash;
    # set up some error counters
    my $errCount = 0;
    my @errMsg;
    my $ctable = {};        #STORE NETBLOCKS FOR ORG LOOKUP

    if ( $no_results ) {
        logwriter( "NO Results to Import or Host offline", 2 );
        return FALSE;
    }

    #$sql = qq{ SELECT id, CIDR FROM vuln_subnets WHERE 1 };
    #$sth_sel = $dbh->prepare( $sql );
    #$sth_sel->execute;
    #while( my ( $subID, $CIDR )=$sth_sel->fetchrow_array){
    #    my $net_table = new2  Net::Netmask($CIDR); 
    #    $net_table->storeNetblock( $ctable );
    #}
    logwriter( "LOADED ALL Netblocks", 5 ); 

    my $ih = 0;

    #GET POPULATE HOSTHASH WITH HOSTNAME /DOMAIN FROM PLUGIN 10150
    logwriter( "nessus_scan: Start Populating HostHash", 5 );
    foreach( @issues ) {
        my $issue = $_;
        my ($scanid, $host, $hostname, $hostip, $service, $app, $port, $proto, $desc,
            $record_type, $domain, $mac_address, $os, $org, $site, $sRating, $sCheck, $sLogin, $risk_factor ) = " ";


        $scanid = $issue->{ScanID};
        $scanid =~ s/.*\.(\d+)$/$1/g;
        $port = $issue->{Port};
        $desc = $issue->{Description};
        $service = $issue->{Service};
        $proto = $issue->{Proto};
        $host = $issue->{Host};
        $risk_factor = $issue->{RiskFactor};
        

        $app = $service;
        if(defined($service) && $service ne "") {
            if(defined($proto) && $proto ne "") {
                $service = "$service ($port/$proto)";
            } else {
                $app = "general";
                $proto = $service;
                $port = "";
                $service = "general/$service";
            }
        }

        if( $host eq "" ) { next; }
        if ( ! $hostHash{$host}{'mac'} ) { $hostHash{$host}{'mac'} = "unknown"; }

	#SET Default for local checks based on if a credential was supplied
	# -1 ( No Credential Used ), 1 ( Credential Used )
	# Then if hits against 21745 ( there was issue with credential such as invalid / etc )
	if ( ! $hostHash{$host}{'checks'} ) {
	    $hostHash{$host}{'rating'} = " ";
	    if ( !defined ( $cred_name ) || $cred_name eq "" ) {
		$hostHash{$host}{'checks'} = "-1";
		logwriter( "nessus_scan: [$host] localchecks = -1 cred_name=[$cred_name]", 4 );
	    } else {
		$hostHash{$host}{'checks'} = "1";
		logwriter( "nessus_scan: [$host] localchecks = 1 cred_name=[$cred_name]", 4 );
	    }
	}

        if ( !exists( $hostHash{$host}{'dns'}) ) {

            #DETERMINE IF IT IS AN IP (CRITICAL STEP AS SCANLITE RETURNS EITHER HOSTIP/HOSTNAME)
            my $isIP = FALSE;
            if ( $host =~ m/^(\d\d?\d?)\.(\d\d?\d?)\.(\d\d?\d?)\.(\d\d?\d?)/ ){
                if($1 <= 255 && $2 <= 255 && $3 <= 255 && $4 <= 255) {
                    $hostip=$host;
                    $isIP = TRUE;
                }
            }

            if ( $isIP == FALSE ) {            #MUST BE A NAME ATTEMPT TO RESOLVE ELSE FAILED MISERABLY
                $hostname=$host;            #LETS AT LEAST SET NAME IN CASE ALL ELSE FAILS
                my $tmp_hostip = resolve_name2ip( $host );
                if ( defined( $tmp_hostip) && $tmp_hostip ne "" ) { $hostip = $tmp_hostip; }
            }

            if ( defined( $hostip ) && $hostip ne "" ) {
                #my $match = findNetblock($hostip, $ctable);
                #if ( $match ) {
                    #$sql = qq{ SELECT id, site_code, ORG FROM vuln_subnets WHERE CIDR = "$match" LIMIT 1 };
                    #$sth_sel = $dbh->prepare( $sql );
                    #$sth_sel->execute;
                    #my ( $subID, $site_code, $ORG) = $sth_sel->fetchrow_array( );
                #    my $subID = ""; my $site_code = ""; my $ORG = "";
                #    $hostHash{$host}{'org'} = "$ORG";                                #FOUND ORG
                #    $hostHash{$host}{'site'} = "$site_code";                    #FOUND SITE
                #    logwriter( "FOUND ORG MATCH FOR hostip=$hostip ORG=$ORG site=$site_code", 5 );
                #}
                #ATTEMPT TO CONSULT VARIOUS DNS SERVERS IN CASE SCANLITE RETURNTED IP
                
                disconn_db($dbh);
                
                my $tmp_hostname = resolve_host( $hostip );
                
                $dbh = conn_db();
                
                if ( defined( $tmp_hostname ) && $tmp_hostname ne "" ) { $hostname = $tmp_hostname; }
            }

            $hostHash{$host}{'ip'} = $hostip; 
            if( defined( $hostname ) && $hostname ne "" ) {
                $hostHash{$host}{'fqdn'} = $hostname;
                $hostHash{$host}{'dns'} = "1";                                  #INDICATE RESOLVED BY NAME WAS SUCCESS
                logwriter( "nessus_scan: successfully looked up name [$host]", 5 );
            } else {
                $hostHash{$host}{'dns'} = "-1";                                 #INDICATE RESOLVED BY NAME FAILED
            }
        } 

        if ( $scanid eq "11936" ) {                                             #OS FINGERPRINT PLUGIN
            my $os = extract_os( $desc );
            $hostHash{$host}{'os'} = $os;
        }

        if ( $scanid eq "10150" ) {                                             #NBTSCAN PLUGIN
            my %hostinfo = extract_hostinfo( $desc );
            $hostHash{$host}{'mac'} = $hostinfo{'mac'};

            if ( $hostHash{$host}{'dns'} eq "-1" && $hostinfo{'dns'} eq "1" ) { #ONLY UPDATE NAME FROM 10150 WHEN DNS FAILS
                $hostHash{$host}{'fqdn'} = $hostinfo{'hostname'};
                $hostHash{$host}{'wgroup'} = $hostinfo{'wgroup'};
                $hostHash{$host}{'dns'} = '1'; 
                logwriter( "nessus_scan: success plugin 10150 to look up name [" . $hostinfo{'hostname'} . "]", 5 );
            }
        }


        #IDENTIFY SCAN ACCESS LEVEL
        if ( $scanid eq "10394" || $scanid eq "12634" ) {
	    #need to check message against known rating texts
	    #10394 WINDOWS 12634 LINUX
	    #STORE TO $Rating UNTIL POST PROCESS ROUTINE TO SEE IF WE HIT ON 21745
	    $hostHash{$host}{'rating'} = check_access( $desc );
	}

	#IDENTIFY IF LOCAL CHECKS FAILED
        if ( $scanid eq "21745" ) {
	    $hostHash{$host}{'checks'} = "0";
	}

        my $risk=-1;
        
        if ($CONFIG{'NESSUSPATH'} !~ /omp\s*$/) {
                
            $risk=1  if ($desc =~ m/Risk [fF]actor\s*:\s*(\\n)*Serious/s);
            $risk=1  if ($desc =~ m/Risk [fF]actor\s*:\s*(\\n)*Critical/s);
            $risk=2  if ($desc =~ m/Risk [fF]actor\s*:\s*(\\n)*High/s);
            $risk=3  if ($desc =~ m/Risk [fF]actor\s*:\s*(\\n)*Medium/s);
            $risk=4  if ($desc =~ m/Risk [fF]actor\s*:\s*(\\n)*Medium\/Low/s);
            $risk=5  if ($desc =~ m/Risk [fF]actor\s*:\s*(\\n)*Low\/Medium/s);
            $risk=6  if ($desc =~ m/Risk [fF]actor\s*:\s*(\\n)*Low/s);
            $risk=7  if ($desc =~ m/Risk [fF]actor\s*:\s*(\\n)*Info/s);
            $risk=7  if ($desc =~ m/Risk [fF]actor\s*:\s*(\\n)*[nN]one/s); 
            #$risk=8 if ($desc =~ m/Risk [fF]actor\s*:\s*(\\n)*Exception/s);       #EXCEPTIONS ARE CALCULATED FROM EXCEPTION DATA NOT BY A STORED RISK VALUE
            $risk=7  if ($desc =~ m/Risk [fF]actor\s*:\s*(\\n)*Passed/s);          #PLAN TO RECLASSIFY Compliance Audit Values
            $risk=3 if ($desc =~ m/Risk [fF]actor\s*:\s*(\\n)*Unknown/s);         #PLAN TO RECLASSIFY Compliance Audit Values
            $risk=2 if ($desc =~ m/Risk [fF]actor\s*:\s*(\\n)*Failed/s);          #PLAN TO RECLASSIFY Compliance Audit Values
            
            if ($risk < 0) {
                $risk=1  if($risk_factor eq "Serious");
                $risk=2  if($risk_factor eq "High");
                $risk=3  if($risk_factor eq "Medium");
                $risk=6  if($risk_factor eq "Low");
                $risk=7  if($risk_factor eq "Info");
            }
            
            #CUSTOM RISK CODE
            if ( $custom_risks->{$scanid} ) { 
                $risk = $custom_risks->{$scanid};
                logwriter( "ASSIGNED PLUGIN: $scanid CUSTOM RISK VALUE $risk", 5 );
            }
        }
        else {
            $risk=1  if($risk_factor eq "Serious");
            $risk=2  if($risk_factor eq "High");
            $risk=3  if($risk_factor eq "Medium");
            $risk=6  if($risk_factor eq "Low");
            $risk=7  if($risk_factor eq "Info");
        }
        
        logwriter("Risk factor $host $scanid $desc $risk", 4); 

        #remove the Risk Factor from the description
        $desc=~ s/Risk [fF]actor\s*:\s*(\\n)*Serious((\\n)+| \/ |$)//s;
        $desc=~ s/Risk [fF]actor\s*:\s*(\\n)*Critical((\\n)+| \/ |$)//s;
        $desc=~ s/Risk [fF]actor\s*:\s*(\\n)*High((\\n)+| \/ |$)//s;
        $desc=~ s/Risk [fF]actor\s*:\s*(\\n)*Medium((\\n)+| \/ |$)//s;
        $desc=~ s/Risk [fF]actor\s*:\s*(\\n)*Medium\/Low((\\n)+| \/ |$)//s;
        $desc=~ s/Risk [fF]actor\s*:\s*(\\n)*Low\/Medium((\\n)+| \/ |$)//s;
        $desc=~ s/Risk [fF]actor\s*:\s*(\\n)*Low((\\n)+| \/ |$)//s;
        $desc=~ s/Risk [fF]actor\s*:\s*(\\n)*Info((\\n)+| \/ |$)//s;
        $desc=~ s/Risk [fF]actor\s*:\s*(\\n)*[nN]one to High((\\n)+|(\s)+| \/ |$)//;
        $desc=~ s/Risk [fF]actor\s*:\s*(\\n)*[nN]one((\\n)+| \/ |$)//s;
        $desc=~ s/Risk [fF]actor\s*:\s*(\\n)*Passed((\\n)+| \/ |$)//s;
        $desc=~ s/Risk [fF]actor\s*:\s*(\\n)*Unknown((\\n)+| \/ |$)//s;
        $desc=~ s/Risk [fF]actor\s*:\s*(\\n)*Failed((\\n)+| \/ |$)//s;


        $service =~ s/(\\n)+$//;
        $desc =~ s/(\\n)+$//;
        $desc =~ s/\\n+$//;
        $desc =~ s/\\+$//;

        #MEANS TO TRACK FILTER ON THE REPORTS
        if ( $scanid >= 60000 ) { $record_type = "C"; } else { $record_type = "N"; }

        $service = htmlspecialchars($service);
        $desc = htmlspecialchars($desc);

        #print "i=$i\n 'scanid' => $scanid, 'port' => $port, 'desc' => $desc, 'service' => $service, 'proto' => $proto \n";
        #my $key = $port.$proto.$scanid;
        my $key = $ih; 
        $hostHash{$host}{'results'}{$key} = { 'scanid' => $scanid, 'port' => $port, 'app' => $app, 'service' => $service,
            'proto' => $proto, 'risk' => $risk, 'record' => $record_type, 'desc' => $desc };
        $ih++;
    }
    logwriter( "nessus_scan: Finished Populating HostHash: $ih", 5 );


    return (%hostHash);
}

sub create_report {
    my ($job_id)    = $_[0];
    my ($job_title) = $_[1];
    my ($scantype)  = $_[2];
    my ($username)  = $_[3];
    my ($sid)       = $_[4];
    my ($scantime)  = $_[5];
    my ($fk_name)   = $_[6];
    my ($failed)    = $_[7];
    my ($note)    = $_[8];

    if ( $failed ne "1" ) { $failed = "0"; }
    if ( !defined ( $note ) || $note eq "" ) { $note = "NULL"; } else { $note = "'$cred_name'"; }
   
    my ( $sth_sel, $sql, $report_id, $report_key, $report_type, $rfield );

    #Build a report_key value to secure reports.
    my @arr = split(/\./, rand() );
    if ( $arr[1] && is_number($arr[1]) ) {
        $report_key = $arr[1];
    } else {
        logwriter( "Failed Report Key generation", 3 );
    }

    if ( !defined ( $cred_name ) || $cred_name eq "" ) { $cred_name = "NULL"; } else { $cred_name = "'$cred_name'"; }

    #CHOSE RECORD TYPE
    if ( $isNessusScan && $isComplianceAudit ) {
        $report_type = "B";
    } elsif ( $isComplianceAudit ) {
        $report_type = "C";
        print "Compliance Audit =TRUE\n";
    } else {        #DEFAULT NESSUS SCAN
        $report_type = "N";
    }
    #logwriter("fk_name: $fk_name $cred_name", 4);
    $sql = qq{ INSERT INTO vuln_nessus_reports ( username, name, fk_name, sid, scantime, report_type, scantype, report_key, cred_used, note, failed ) VALUES (
        '$username', '$job_title', NULL, '$sid', '$scantime', '$report_type', '$scantype', '$report_key', NULL, $note, '$failed' ); };
    safe_db_write ( $sql, 4 );

    $sql = qq{ SELECT report_id FROM vuln_nessus_reports WHERE scantime='$scantime' AND report_key='$report_key' ORDER BY scantime DESC LIMIT 1 };
    logwriter( $sql, 5 );
    $sth_sel=$dbh->prepare( $sql );
    $sth_sel->execute;
    $report_id = $sth_sel->fetchrow_array( );

    return $report_id;

}

sub process_results {
    my (%hostHash) = %{$_[0]};
    my ($job_id)    = $_[1];
    my ($job_title) = $_[2];
    my ($scantype)  = $_[3];
    my ($username)  = $_[4];
    my ($sid)       = $_[5];
    my ($scantime)  = $_[6];
    my ($fk_name)   = $_[7];
    
    my ( $sth_sel, $sql, $sth_sel2, $sql2, $sql_insert, $sql_insert2, $report_id, $report_key, $report_type, $update_stats, $rfield );
    my ( $sth_update, $sql_update, $sth_del, $sql_delete);
    my ( $rpt_key, $sqli, $sth_ins);
    my ( $nname);
    my ( $fp_sel, $fp_service, $fp);
    my %ntargets = ();
    my %acnets = ();

    my $bSInfo = FALSE;		    #TRACK SERVER SCAN INFO WAS SAVED
    if ( $primaryAuditcheck ) { $rfield = "creport_id"; } else { $rfield = "report_id"; } #GET CORRECT FIELD BASED ON AUDIT TYPE
    if ( !defined( $fk_name) || $fk_name eq "" ) { $fk_name = "NULL"; } #else { $fk_name = "'".$fk_name."'"; }
    logwriter("isTop100Scan: $isTop100Scan", 4);
    if ( !$isTop100Scan ) { # GENERATE FULL REPORT WHEN NOT TOP100 AUDIT

        # extract networks from targets
        my $targets = "";
        $sql = qq{ SELECT meth_TARGET FROM vuln_jobs WHERE id='$job_id'};
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
        $targets = $sth_sel->fetchrow_array( );
        # there are networks
        if ($targets =~ /\//) {
            my @anets = split(/\n/,$targets);
            foreach my $anet (@anets) {
                $anet =~ s/\s|\n|\r|\t//g;
                if ($anet =~ m/([a-f\d]{32})#(.*\/.*)/i) {   #       net_id#cidr
                    $ntargets{$2} = $1;
                }
            }
        }


        #CHECK IF PROFILE IS SAFE/FULL AUDIT WHICH ALLOWED TO UPDATE HOST TRACKER STATS
        $sql = qq{ SELECT update_host_tracker FROM vuln_nessus_settings WHERE id='$sid' LIMIT 1 };
        logwriter( $sql, 5 );
        $sth_sel=$dbh->prepare( $sql );
        $sth_sel->execute;
        $update_stats = $sth_sel->fetchrow_array( );

	#MOVING REPORT CREATION OUT TO A NEW SUB ROUTINE / SO WE CAN USE FOR FAILED JOBS TOO.
        $report_id = create_report ( $job_id, $job_title, $scantype, $username, $sid, $scantime, $fk_name, "0", "" );
        logwriter("Report id: $report_id", 4);
        if ( ! $report_id ) {
            logwriter( "failed to lookup report after insert for scan $scantime", 2 );
            return 0;
        }
        
        if (-e $outfile) {
            # save the raw nbe
            system("mkdir -p /usr/share/ossim/uploads/nbe;chown www-data:www-data /usr/share/ossim/uploads/nbe;mv '$outfile' '$nbe_path".$report_id.".nbe';chmod 644 '$nbe_path".$report_id.".nbe'");
        }
        
        #UPDATE ASSOCIATED REPORT_ID FIELDS
        $sql = qq{ UPDATE vuln_jobs SET report_id='$report_id', scan_END='$scantime' WHERE id='$job_id' LIMIT 1};
        safe_db_write ( $sql, 4 );

        if ( $scantype =~ /c/i ) {
            if ( defined( $fk_name ) && $fk_name ne "NULL" ) {
                #$sql = qq{ UPDATE vuln_subnets SET $rfield='$report_id', dtLastScanned='$scantime' WHERE CIDR=$fk_name LIMIT 1 };
                #safe_db_write ( $sql, 4 );            #use insert/update routine
            }
        } elsif ( $scantype =~ /s/i ) {
            if ( defined( $fk_name ) && $fk_name ne "NULL" ) {
                #$sql = qq{ UPDATE vuln_systems SET $rfield='$report_id', dtLastScanned='$scantime', noticeLevel='0', expiredReport='0' WHERE acronym =$fk_name LIMIT 1 };
                #safe_db_write ( $sql, 4 );            #use insert/update routine
            }
        }
    }

    logwriter( "nessus_scan: Start Processing Results", 5 );
    $sql_insert = "";
    my $i = 0;
    my %TOTALRISKS = ( 1, 0, 2, 0, 3, 0, 4, 0, 5, 0, 6, 0, 7, 0);   #TRACK COUNT ALL SCANNED RISKS

    foreach my $host ( sort keys %hostHash ) {
        my ( $hostip, $ctx, $hid, $hostname, $mac_address, $os, $workgroup, $ip_org, $ip_site, $open_issues ) = "";

        my $host_id = "0";
        my $localchecks = "-1";
        my $host_rating = "0";
        my $rating_text = " ";
        
        my %HOSTRISKS = ( 1, 0, 2, 0, 3, 0, 4, 0, 5, 0, 6, 0, 7, 0); #RESET FOR EACH HOST PROCESSED

        if ( $hostHash{$host}{'ip'}     ) {      $hostip  = $hostHash{$host}{'ip'};      }
        
        # get host ctx
        $ctx = get_default_ctx();
        
        if(defined($asset_data{$hostip}{'ctx'})) {
            $ctx = $asset_data{$hostip}{'ctx'};
        }
        else{
            foreach my $cidr (keys %asset_data) { # check if the host in a net
                if($cidr=~/.*\/.*/ && ipinnet($hostip, $cidr)) {
                    $ctx = $asset_data{$cidr}{'ctx'};
                }
            }
        }
        
        # get host id
        $hid = "";
        
        if(defined($asset_data{$hostip}{'id'})) {
            $hid = $asset_data{$hostip}{'id'};
        }
        
        if ( $hostHash{$host}{'fqdn'}   ) {    $hostname  = $hostHash{$host}{'fqdn'};    }
        if ( $hostHash{$host}{'mac'}    ) { $mac_address  = $hostHash{$host}{'mac'};     }
        if ( $hostHash{$host}{'os'}     ) {           $os = $hostHash{$host}{'os'};      }
        if ( $hostHash{$host}{'wgroup'} ) {    $workgroup = $hostHash{$host}{'wgroup'};  }
        if ( $hostHash{$host}{'org'}    ) {       $ip_org = $hostHash{$host}{'org'};     }
        if ( $hostHash{$host}{'site'}   ) {      $ip_site = $hostHash{$host}{'site'};    }
        if ( $hostHash{$host}{'checks'} ) {  $localchecks = $hostHash{$host}{'checks'};  }
        if ( $hostHash{$host}{'rating'} ) {  $rating_text = $hostHash{$host}{'rating'};  }
    
    #before delete extract data
    my $sql_extract_data = qq{SELECT count(risk) as count, risk FROM vuln_nessus_latest_results
                                        WHERE hostIP = '$hostip' and username = '$username' and sid = '$sid'
                                        AND ctx = UNHEX ('$ctx') AND falsepositive='N' GROUP BY risk};
    logwriter( $sql_extract_data, 5 );    
                                        
    my $sth_extract=$dbh->prepare($sql_extract_data); 
    $sth_extract->execute;
    
    my @risks_stats = ("0","0","0","0","0");
    
    while ( my ( $risk_count, $risk )=$sth_extract->fetchrow_array ) {
        if($risk==7) {
            $risks_stats[4] = $risk_count;
        }
        if($risk==6) {
            $risks_stats[3] = $risk_count;
        }
        if($risk==3) {
            $risks_stats[2] = $risk_count; 
        }
        if($risk==2) {
            $risks_stats[1] = $risk_count;
        }
        if($risk==1) {
            $risks_stats[0] = $risk_count;
        }
    }
    my $last_string = join(";",@risks_stats);
    
    #delete vuln_nessus_latest_results results
    $sql_delete = qq{ DELETE FROM vuln_nessus_latest_results WHERE hostIP = '$hostip' and username = '$username' and ctx = UNHEX ('$ctx') and sid = '$sid' };
    logwriter( $sql_delete, 5 );
    $sth_del = $dbh->prepare( $sql_delete );
    $sth_del->execute;

	$hostname = trim( $hostname );	    #INITIALLY SET IT TO " ";
        #LOOKUP HOSTID
        if ( defined ( $hostname ) && $hostname ne "" ) { #LOOKUP HOST_ID IF HOSTNAME IS NOT NULL
            $host_id = get_host_record( $mac_address, $hostname, $hostip );
        }

	#ESTABLISH A RATING ( BASED ON TEXT MAPPING TO PER CHECK_ACCESS PROCESSING OF PLUGIN 10394 )
  	#0  	No Access		    /no access/
	#1 	No Administrator Password   /administrator no password/
	#2 	Blank User Passwords	    /user no password/
	#3 	NULL Access		    /null session/
	#4 	User Access		    /authenticated user/ && localcheck=="0"
	#5 	Admin Access		    /authenticated user/ && localcheck=="1"

	#I ORDERED THEM IN ORDER OF IMPORTANCE

	if ( $rating_text =~ /administrator no password/ ) {
	    $host_rating = 1;
	} elsif ( $rating_text =~ /user no password/  ) {
	    $host_rating = 2;
	} elsif ( $localchecks == "1" && ( $rating_text =~ /authenticated user/ || $rating_text =~ /run linux checks/ )) {
	    $host_rating = 5;
	} elsif ( $localchecks == "0" && ( $rating_text =~ /authenticated user/ || $rating_text =~ /run linux checks/ )) {
	    $host_rating = 4;
	} elsif ( $rating_text =~ /null session/ || $rating_text =~ /authenticated user/ || $rating_text =~ /run linux checks/ ) {
	    $host_rating = 3;
	} elsif ( $rating_text =~ /no access/ ) {
	    $host_rating = 0;
	    if ( $localchecks == "1" ) { $localchecks = "0"; }
	} else {
	    #THROW IN INVALID TO START TRACKING ON SENERIOS THAT MAY NOT MATCH UP
	    $host_rating = -1;
	    if ( $localchecks == "1" ) { $localchecks = "0"; }
            logwriter( "CRITICAL SCRIPT ERROR NO MATCH FOR HOST RATING: [$host_rating]", 2);
	}

	#logwriter( "hostid=[$host_id]\tmac=[$mac_address]\tname=[$hostname]\tip=[$hostip]\tos=[$os]\torg=[$ip_org]", 5 );
    
        # load fps
        my %host_fp = ();
        $sql = qq{ SELECT scriptid,service FROM vuln_nessus_latest_results WHERE hostIP='$hostip' and ctx = UNHEX ('$ctx') and falsepositive='Y' 
                          UNION SELECT scriptid,service FROM vuln_nessus_results WHERE hostIP='$hostip' and ctx = UNHEX ('$ctx') and falsepositive='Y' };
        $fp_sel = $dbh->prepare( $sql );
        $fp_sel->execute;
        while ((my $fp_scriptid,$fp_service) = $fp_sel->fetchrow_array) {
            $host_fp{$fp_scriptid}{$fp_service} = 1;
        }

        my %recordshash = %{$hostHash{$host}{'results'}};
        my %vuln_resume = ();

        foreach my $record ( sort keys %recordshash ) {
            my ( $scanid, $service, $app, $port, $proto, $risk, $domain, $record_type, $desc ) = " ";
            my $isCheck = "0"; #IS A COMPLIANCE CHECK SCRIPTID ( NOT A TENABLE PLUGIN ID )

            $scanid = $hostHash{$host}{'results'}{$record}{'scanid'};
            logwriter("debug1: ".$scanid, 4 ); #DEBUGGG
            $service = $hostHash{$host}{'results'}{$record}{'service'};
            $app = $hostHash{$host}{'results'}{$record}{'app'};
            $proto = $hostHash{$host}{'results'}{$record}{'proto'};
            $port = $hostHash{$host}{'results'}{$record}{'port'};
            $desc = $hostHash{$host}{'results'}{$record}{'desc'};
            $desc =~ s/^ *| *$//g;
            $desc =~ s/^(\\n|\n)+//g;
            $desc =~ s/(\\n|\n)+$//g;
            $risk = $hostHash{$host}{'results'}{$record}{'risk'};
            $domain = $hostHash{$host}{'results'}{$record}{'domain'};
            $record_type = $hostHash{$host}{'results'}{$record}{'record'};
            $open_issues .= "$scanid\n";    #USED TO TRACK ISSUES TO BE CLOSED
            $TOTALRISKS{"$risk"} += 1;	    #USING ASSOC ARRAY TO TRACK SCAN RISK COUNT ON THE FLY
            $HOSTRISKS{"$risk"} += 1;	    #USING ASSOC ARRAY TO TRACK HOST RISK COUNT ON THE FLY

            if ( !$bSInfo && $scanid eq "19506" ) {   #CODE TO UPDATE SERVER FEED VERSION/DATE
                set_serverinfo( $report_id, $desc );  #bSInfo should enable it to be only run once per scan
            }

            logwriter( "record=$record\t 'scanid' => [$scanid], 'port' => [$port], 'record' => [$record_type], 'service' => [$service],"
                ." 'proto' => [$proto], 'risk' => [$risk], 'desc' => [$desc]\n", 4); 

            if ( !$isTop100Scan ) {	#LOAD INTOTO vuln_nessus_results
                if ( !defined( $sql_insert ) || $sql_insert eq "" ) {

                    #FIRST ITERATION OR RESET VARIABLE AFTER IMPORTING 100 RECORDS
                    $sql_insert = "INSERT INTO vuln_nessus_results ( report_id, scantime, hostip, ctx, hostname, record_type, service, port, protocol , app, scriptid, risk, msg, falsepositive )\nVALUES\n";
                    $sql_insert2 = "INSERT INTO vuln_nessus_latest_results ( username, sid, scantime, hostip, ctx, hostname, record_type, service, port, protocol , app, scriptid, risk, msg, falsepositive )\nVALUES\n";
                    #delete host_plugin_sid results
                    
                    my $ip_hex = get_varhex_from_ip($hostip);
                    
                    $sql_delete = qq{ DELETE FROM host_plugin_sid WHERE host_ip = UNHEX('$ip_hex') and ctx=UNHEX('$ctx') and plugin_id = 3001 };
                    logwriter( $sql_delete, 5 );
                    $sth_del = $dbh->prepare( $sql_delete );
                    $sth_del->execute;
                    #delete host_plugin_sid results
                    my @arr = split(/\./, rand() );
                    if ( $arr[1] && is_number($arr[1]) ) { $rpt_key = $arr[1]; }
                    else { $rpt_key = 0; }
                    $sqli = qq{ INSERT INTO vuln_nessus_latest_reports ( hostIP, ctx, username, fk_name, sid, scantime, report_type, scantype, report_key, cred_used, note, failed ) VALUES ('$hostip', UNHEX('$ctx'), '$username', NULL, '$sid', '$scantime', 'N', '$scantype', '$rpt_key', NULL, '0;0;0;0;0','0' ) ON DUPLICATE KEY UPDATE scantime='$scantime', failed=results_sent, note='$last_string' };
                    logwriter( $sqli, 5 );
                    $sth_ins = $dbh->prepare( $sqli );
                    $sth_ins->execute;
                    $i=0;
                }
                $i += 1;
                $fp = (defined($host_fp{$scanid}{$service}) && $host_fp{$scanid}{$service} == 1) ? 'Y' : 'N';
                $sql_insert .= " ('$report_id', '$scantime', '$hostip', UNHEX('$ctx'), '$hostname', '$record_type', '$service', '$port', '$proto', '$app', '$scanid', '$risk', '$desc', '$fp' ),\n";
                $sql_insert2 .= " ('$username', '$sid', '$scantime', '$hostip', UNHEX('$ctx'), '$hostname', '$record_type', '$service', '$port', '$proto', '$app', '$scanid', '$risk', '$desc', '$fp' ),\n";
                    
                if ( $i >= 100 ) {
                    chop($sql_insert);
                    chop($sql_insert);
                    chop($sql_insert2);
                    chop($sql_insert2);
                    $sql_insert .= ";";
                    $sql_insert2 .= ";";
                    #CONNECT DB AND INSERT 100 RECORDS
                    safe_db_write( $sql_insert, 5 );
                    logwriter( "[$job_title] - inserted $i results records", 4 );
                    safe_db_write( $sql_insert2, 5 );
                    logwriter( "[$job_title] - inserted $i latest_results records", 4 );

                    $sql_insert = "";
                    $sql_insert2 = "";
                    $i = 0;
                }
            }
            if(!defined($vuln_resume{"$hostip;$ctx"})) {
                $vuln_resume{"$hostip;$ctx;$hid"} = $risk;
            }
            elsif($risk < $vuln_resume{"$hostip;$ctx"}) {
                $vuln_resume{"$hostip;$ctx;$hid"} = $risk;
            }
            # incidents
            update_ossim_incidents($hostip, $ctx, $hid, $port, $risk, $desc, $scanid, $username, $sid);
        } #END FOR EACH RECORD
        
        #CHECK FOR RECORDS WHICH REMAIN NOT INSERTED FOR HOST  
        if ( !$isTop100Scan ) {          
            if ( defined( $sql_insert ) && $sql_insert ne "" ) {
                chop($sql_insert);
                chop($sql_insert);
                chop($sql_insert2);
                chop($sql_insert2);
                $sql_insert .= ";";
                $sql_insert2 .= ";";
                #CONNECT DB AND INSERT REMAINDER OF RECORDS
                safe_db_write( $sql_insert, 5 );
                logwriter( "[$job_title] - inserted $i results records", 4 );
                safe_db_write( $sql_insert2, 5 );
                logwriter( "[$job_title] - inserted $i latest_results records", 4 );
                $sql_insert = "";
                $sql_insert2 = "";
            }
        }      
        
        my $max_risk = 0;
        
        foreach my $data (keys %vuln_resume) {
            # max_risk is the field risk in vuln_nessus_results table
            my ($hip, $ctx, $hid) = split(/;/, $data);

            $max_risk = $vuln_resume{$data};
            
            if($max_risk<=2)        {  $max_risk = 10;  }
            elsif ($max_risk<=6)    {  $max_risk = 7;   }
            else                    {  $max_risk = 3;   }
            
            $sql = qq{ SELECT scriptid FROM vuln_nessus_latest_results WHERE hostIP='$hip' AND ctx=UNHEX('$ctx') };
            logwriter( $sql, 5 );
            $sth_sel = $dbh->prepare( $sql );
            $sth_sel->execute;

            while ((my $scanid) = $sth_sel->fetchrow_array) {
                #logwriter( "Scan id: $scanid", 5 );
                # plugin_sid
                
                my $ip_hex = get_varhex_from_ip($hip);
                
                $sql_update = qq{ INSERT IGNORE INTO host_plugin_sid (host_ip, ctx, plugin_id, plugin_sid) VALUES (UNHEX('$ip_hex'), UNHEX('$ctx'), 3001, $scanid) };
                logwriter( $sql_update, 5 );
                $sth_update = $dbh->prepare( $sql_update );
                $sth_update->execute;
                #
            }
            # net max risk
            foreach my $anet (keys %ntargets) {
                $ntargets{$anet} =~ s/^\s*|\s*$//g;
                if (ipinnet($hip,$anet)) {
                    if(!defined($acnets{$ntargets{$anet}})) {
                        $acnets{$ntargets{$anet}} = $max_risk ;
                    }
                    elsif($max_risk > $acnets{$ntargets{$anet}}) {
                        $acnets{$ntargets{$anet}} = $max_risk ;
                    }
                }
            }
            # host_vulnerability
            if(defined($hid) && $hid ne "") {
                $sql_update = qq{ INSERT INTO host_vulnerability VALUES (UNHEX('$hid'), '$scantime', $max_risk) ON DUPLICATE KEY UPDATE vulnerability=$max_risk  };
                logwriter( $sql_update, 5 );
                $sth_update = $dbh->prepare( $sql_update );
                $sth_update->execute;
            }
            
            # vulnerabilities 
            $sql_update = qq{SELECT count( * ) AS vulnerability FROM (SELECT DISTINCT hostip, port, protocol, app, scriptid, msg, risk
                        FROM vuln_nessus_latest_results WHERE hostIP='$hip' AND ctx=UNHEX('$ctx') AND falsepositive='N') AS t GROUP BY hostip};
            logwriter( $sql_update, 5 );
            $sth_update=$dbh->prepare( $sql_update );
            $sth_update->execute;
            my $vuln_host = $sth_update->fetchrow_array;
            
            # update vulns into vuln_nessus_latest_reports - sort facility
            $sql_update = qq{ UPDATE vuln_nessus_latest_reports SET results_sent=$vuln_host WHERE hostIP='$hip' AND ctx=UNHEX('$ctx') AND username='$username' };
            logwriter( $sql_update, 5 );
            $sth_update = $dbh->prepare( $sql_update );
            $sth_update->execute;
        }

        foreach my $net_id (keys %acnets) {
            my $nt = $acnets{$net_id};
            $sql_update = qq{ INSERT INTO net_vulnerability VALUES (UNHEX('$net_id'), '$scantime', $nt) ON DUPLICATE KEY UPDATE vulnerability=$nt };
            logwriter( $sql_update, 5 );
            $sth_update = $dbh->prepare( $sql_update );
            $sth_update->execute;
        }


        #UPDATE CLOSED INCIDENTS AS RESOLVED ( per previously update_incidents )
        if ( defined( $hostip ) && defined( $ctx ) ) {
            print "openissues=[$open_issues]\n";
            $sql = qq{ SELECT distinct i.id,iv.nessus_id from
                            incident_vulns iv join incident i on i.id=iv.incident_id
                            where iv.ip='$hostip' and iv.ctx = UNHEX('$ctx') and i.status='Open' and iv.description like '%SID:$sid'};
            logwriter( $sql, 5 );
            $sth_sel = $dbh->prepare( $sql );
            $sth_sel->execute;
            while(my ( $incident_id, $scriptid )=$sth_sel->fetchrow_array) {
                #FAIL SAFE DO MARK ANY PLUGINS NOT TESTED AS RESOLVED
                if ( grep { $_ eq $scriptid } @vuln_nessus_plugins ) { 
                    logwriter( "checking incident [$incident_id] against scriptid [$scriptid]", 4 );
	            if ( $open_issues =~ /$scriptid/ ) {
                        #CURRENTLY NOT RESOLVED
                    } else {
                        #CURRENTLY CREDENTIALS VS NO /CREDENTIALS WILL CAUSE AN INVALID CLEANUP STATE TO BE SET
                        $sql2 = qq{ UPDATE incident SET status='Closed', last_update='$scantime' WHERE id='$incident_id' };
                        safe_db_write ( $sql2, 4 );
                    }
                } else {
                    logwriter( "PLUGIN $scriptid apparrently was not tested", 5 );
                }
            }
        }

        #PER EACH HOST UPDATE HOST RECORD/STATS
        if ( defined ( $hostname ) && $hostname ne "" ) {
                #update_host_record ( \%HOSTRISKS, $mac_address, $hostname, $hostip, $os, $workgroup, $ip_org, $ip_site, $report_id, $scantime, $localchecks, $host_rating, $update_stats );
        }
        undef ( %HOSTRISKS );

    } #END FOREACH HOST LOOP

    if ( !$isTop100Scan ) { #TOTALLY DONE LOOP ( OPTION TO DUMP TO .NESSUS FILE FROM RESULTS DATA )

        logwriter( "Completed SQL Import", 4 );
        update_stats ( $job_id, $job_title, $report_id, $scantime );

        #if ( $CONFIG{'archiveNessus'} eq "1" ) {
        #    my $cmd = "$CONFIG{'ROOTDIR'}/sbin/archive_report.pl -r $report_id";
        #    my $ex = qx{ $cmd };
        #}
    }
    
    if($semail eq "1") {
        logwriter("Sending email notification...", 4);
        my $cmde = qq{/usr/bin/php /usr/share/ossim/scripts/vulnmeter/send_notification.php '$job_id'};
        logwriter("Send email for job_id: $job_id ...", 5);
        open(EMAIL,"$cmde 2>&1 |") or die "failed to fork :$!\n";
        while(<EMAIL>){
            chomp;
            logwriter("send_notification output: $_", 5);
        }
        close EMAIL;
    }
    
    return TRUE;
}


sub set_serverinfo {
    my ( $report_id, $txt_msg ) = @_;

    my ( $sql );
    my %SCAN = ();

    my @arrMSG = split /\\n\\n|\n\n|\r/, $txt_msg;

    $SCAN{'Scanner IP'} = "";
    $SCAN{'Nessus version'} = "";
    $SCAN{'Type of plugin feed'} = "";
    $SCAN{'Plugin feed version'} = "";

    foreach my $line (@arrMSG) {
        $line =~ s/\#.*$//;
        chomp($line);
        $line =~ s/\s+$//;
        $line =~ s/^\s+//;
        if ($line eq "") { next; }
        my @temp=split(/:/,$line,2);
        $temp[0] =~ s/\s+$//;
        $temp[0] =~ s/^\s+//;
        if ( defined( $temp[1]) &&  $temp[1] ne "" ) {
            $temp[1] =~ s/\s+$//;
            $temp[1] =~ s/^\s+//;
        }
        if ($temp[0] ne "") { $SCAN{$temp[0]}=$temp[1]; }
        #print "0=$temp[0] 1=$temp[1]\n";
    }

    my $server_ip = "" . $SCAN{'Scanner IP'};
    my $server_nver = "" . $SCAN{'Nessus version'};
    my $server_feedtype= "" . $SCAN{'Type of plugin feed'};
    my $server_feedversion = "" . $SCAN{'Plugin feed version'};

    if ( defined ( $server_ip ) && $server_ip ne "" ) {
        $sql = qq{ UPDATE vuln_nessus_reports SET server_ip='$server_ip', server_nversion='$server_nver',
            server_feedtype='$server_feedtype', server_feedversion='$server_feedversion' WHERE report_id='$report_id' };
        safe_db_write( $sql, 4 );

        #UPDATE INFO IN THE vuln_nessus_servers TABLE TOO IF FEED DATE CHANGED
        $sql = qq{ UPDATE vuln_nessus_servers SET server_nversion='$server_nver', server_feedtype='$server_feedtype',
            server_feedversion='$server_feedversion' WHERE hostname='$server_ip' AND server_feedversion <= '$server_feedversion' };
        safe_db_write( $sql, 4 );
    }
}

# extract host info <- assuming msg from plugin #10150 is supplied
sub extract_hostinfo {
    # VER: 1.0 MODIFIED: 11/15/07 15:43
    my ( $txt_msg ) = @_;

    #changed $domain to $wgroup ( did not want to confuse with domain field per nessus nbe results
    my ( $hostname, $wgroup, $mac_address ) = "";

    logwriter( "nessus_scan: plugin 10150 data: [[$txt_msg]]", 5 );
    my @arrMSG = split /\\n\\n|\n\n|\r/, $txt_msg;
    foreach my $line (@arrMSG) {
        $line =~ s/\#.*$//;
        chomp($line);
        $line =~ s/\s+$//;
        $line =~ s/^\s+//;
        logwriter( "nessus_scan: LINE=[$line]", 5 ); 
        if ($line =~ /computer\sname/i ) {
            my @temp=split(/=/,$line,2);
            $temp[0] =~ s/\s+$//;
            $temp[0] =~ s/^\s+//;
            $hostname = lc( $temp[0] );
            logwriter( "nessus_scan: hostname=[$hostname]", 5 );
        } elsif ($line =~ /Workgroup/i ) {
            my @temp=split(/=/,$line,2);
            $temp[0] =~ s/\s+$//;
            $temp[0] =~ s/^\s+//;
            $wgroup = lc( $temp[0] );
            logwriter( "nessus_scan: wgroup=[$wgroup]", 5 );
        } elsif ($line =~ /^([0-9a-fA-F][0-9a-fA-F]:){5}([0-9a-fA-F][0-9a-fA-F])$/ ) {
            $mac_address = uc( $line );
            $mac_address =~ s/[:-]//g;
        }
        next;
    }

    if ( ! $mac_address ) { $mac_address = "unknown"; }
    if ( ! $wgroup ) { $wgroup = "unknown"; }
    if ( $hostname =~ /Synopsis:/i ) { $hostname = ""; }

    if ( defined ( $hostname ) && $hostname ne "" ) {
       logwriter ( "my %hostinfo = ( 'dns' => '1', 'hostname' => '$hostname', 'wgroup' => '$wgroup', 'mac' => '$mac_address' );\n", 5 );
       my %hostinfo = ( 'dns' => '1', 'hostname' => $hostname, 'wgroup' => $wgroup, 'mac' => $mac_address );
       return %hostinfo;
    } else {
       logwriter ( "my %hostinfo = ( 'dns' => '-1', 'mac' => $mac_address );\n", 5 );
       my %hostinfo = ( 'dns' => '-1', 'mac' => $mac_address );
       return %hostinfo;
    }
}

# extract os info  <- assuming msg from plugin #11936 is supplied
sub extract_os {
    # VER: 1.0 MODIFIED: 11/15/07 15:43
    my ( $txt_msg ) = @_;
    my $os = "";
    my @arrMSG = split /\\n\\n|\n\n|\r/, $txt_msg;
    foreach my $line (@arrMSG) {
        $line =~ s/\#.*$//;
               chomp($line);
        $line =~ s/\s+$//;
        $line =~ s/^\s+//;
        if ($line =~ "Remote operating system") {
            my @temp=split(/:/,$line,2);
            $os = $temp[1];
            $os =~ s/\(English\)//;
            if ( $os =~ /\\n/ ) {
                @temp=split(/\\n/,$os,2);
                $os =$temp[0];
            }
            $os =~ s/\s+$//;
            $os =~ s/^\s+//;
            logwriter ( "OS=[$os]\n", 5 );
            return $os;
        } else { next; }
    }
    return "";
}

#needed for host updated / load results
sub resolve_host {
    # VER: 2.0 MODIFIED: 5/06/08 15:30
    my ( $hostip ) = @_;

    if ( ! defined ( $hostip) || $hostip eq "" ) { return ""; }

    # ATTEMPT GET HOST BY ADDRESS WILL CHECK FILES/DNS PRIMARY
    my $iaddr = inet_aton( $hostip ); # or whatever address
    my $namer  = gethostbyaddr($iaddr, AF_INET);

    if ( defined($namer ) ) {
        my $thost = lc ( $namer );
        #logwriter( $thost, 5 );
        return $thost;
    } else {

        if ( $CONFIG{'nameservers'} ne "" ) {
            my @nameservers = split /,/, $CONFIG{'nameservers'};
            foreach my $nameserver ( @nameservers ) {
                $nameserver =~ s/\s+//g;
                my $namer = nslookup(host => "$hostip", type => "PTR", server => "$nameserver" );
                if ( defined($namer ) && $namer ne "" ) {
                    my $thost = lc ( $namer );
                    return $thost;
                }
            }
        } 
    }
    logwriter( "REVERSE IP [$hostip] TO NAME FAILED\n", 3 );
    return "";

}

#ENSURE IP'S ARE IN THE BUILDLIST Otherwise Hostnames are returned instead IP's
#THEY GET STUFFED IN THE HOSTIP Field as a result
sub resolve_name2ip {
    # VER: 2.0 MODIFIED: 5/06/08 15:30
    my ( $hostname ) = @_;
    if ( ! defined ( $hostname ) || $hostname eq "" ) { return ""; }

    my $ip = getassetbyname( $hostname );

    if ($ip ne "") {
        return $ip;
    }
    
    disconn_db($dbh);
    
    # ATTEMPT GET HOST BY ADDRESS WILL CHECK FILES/DNS PRIMARY
    my $packed_ip = gethostbyname( $hostname );

    if ( defined( $packed_ip ) ) {
        my $c_ip = inet_ntoa($packed_ip);
        $dbh = conn_db();
        
        return $c_ip;
    }
    elsif ( $CONFIG{'nameservers'} ne "" ) { #TRY OTHER NAMES SERVERS
        my @nameservers = split /,/, $CONFIG{'nameservers'};

        foreach my $nameserver ( @nameservers ) {
            $nameserver =~ s/\s+//g;
            my $namer = nslookup(host => "$hostname", server => "$nameserver" );
            if ( defined($namer ) && $namer ne "" ) {
                my $thost = lc ( $namer );
                $dbh = conn_db();
                
                return $thost;
            }
        }
    }
    
    $dbh = conn_db();
    
    logwriter( "RESOLVE [$hostname] TO IP FAILED\n", 3 );
    return "";
}

#check nessus up
sub check_connect {
    #Check if the Nessus scanners are up
    my ( $sql, $sql1, $sth, $sth1 );
    my($serverid,$n_hostname,$n_port,$tmp_current);
    my($socket, $connect);

    $sql = qq{ SELECT id, hostname, port, current_scans FROM vuln_nessus_servers
        WHERE enabled='1' AND checkin_time+interval 300 second<now() };

    logwriter( $sql, 5 );

    $sth=$dbh->prepare( $sql );
    $sth->execute;

    #Check status of the Nessus servers loop
    while(($serverid,$n_hostname, $n_port, $tmp_current )=$sth->fetchrow_array) {
        $n_hostname = $CONFIG{'NESSUSHOST'}; #   variables of the database
        $n_port = $CONFIG{'NESSUSPORT'};
        
        logwriter( "serverid = $serverid, n_hostname = $n_hostname, n_port = $n_port, tmp_current = $tmp_current", 4 );
        $sql1 = qq{ UPDATE vuln_nessus_servers SET status="C"
            WHERE id="$serverid" };

        safe_db_write ( $sql1, 5 );

        $connect=1;
        #Check if the server is alive
        #print localtime(time)." sched: Checking Nessus server status - $n_hostname:$n_port\n";
        $socket = IO::Socket::INET->new("$n_hostname:$n_port") or $connect=0;

        #If server is dead
        if ($connect==0) {
            logwriter( "\tsched: Nessus server OFFLINE - $n_hostname:$n_port", 2 );
            $sql1 = qq{ UPDATE vuln_nessus_servers SET status="N", checkin_time=now(), current_scans=0
                WHERE id="$serverid" };
            safe_db_write ( $sql1, 3 );

            $sql1 = qq{ UPDATE vuln_jobs SET scan_SERVER=0, status="S", scan_START=NULL, scan_END=NULL
                WHERE scan_SERVER="$serverid" AND status="R" OR scan_SERVER="$serverid" AND status="S" };
            safe_db_write ( $sql1, 2 );
            
        } else {
            #print localtime(time)." sched: Nessus server ONLINE - $n_hostname:$n_port\n";
            close $socket;
            $sql1 = qq{ UPDATE vuln_nessus_servers SET status="A", checkin_time=now() WHERE id="$serverid" };
            safe_db_write ( $sql1, 5 );
        }

        if ( $tmp_current < 0 ) {
            $sql1 = qq{ UPDATE vuln_nessus_servers SET current_scans="0" WHERE id="$serverid" };
            safe_db_write ( $sql1, 3 );
        }
    } #End of loop
    #$sth->finish;
    #print "check_complete\n";
}

# get the job schedule table to setup reoccuring jobs
sub check_schedule {

    my ( $sql, $sth_sel, $sth_sel2, $now );

    $now = getCurrentDateTime();

    $sql = qq{ SELECT id, name, username, fk_name, job_TYPE, schedule_type, day_of_week, day_of_month, time, email,
        meth_TARGET, meth_CRED, meth_VSET, meth_TIMEOUT, scan_ASSIGNED, next_CHECK, meth_Ucheck, resolve_names, IP_ctx, meth_Wfile, credentials
        FROM vuln_job_schedule WHERE enabled != '0' and next_check <= '$now' };
    logwriter( $sql, 5 );
    $sth_sel=$dbh->prepare( $sql );
    $sth_sel->execute;

    while ( my ($jid, $name, $username, $fk_name, $jobTYPE, $schedule_type, $day_of_week, $day_of_month, $time_run, $email,
        $meth_TARGET, $meth_CRED, $meth_VSET, $meth_TIMEOUT, $scan_server, $next_check, $scan_locally, $resolve_names, $IP_ctx, $send_email,
        $credentials) = $sth_sel->fetchrow_array ) {
        
        $scan_locally=0 if ($scan_locally eq "" || !$scan_locally);

        #DECIDED TO DROP TRACKING ON DAY/MONTH/TIME_RUN RELY SOLELY ON A GOOD NEXT_CHECK DATE
        gen_sched_next_scan ( $jid, $schedule_type );

        if ( $jobTYPE eq "S" ) {
           #my $sql = qq{ SELECT hostip from vuln_systems t1
           #  LEFT JOIN vuln_system_hosts t2 on t2.sysID = t1.id
           #  WHERE t1.acronym='$fk_name' };
           #my $sth_sel2=$dbh->prepare( $sql );
           #$sth_sel2->execute;
           #while ( my ( $hostip )=$sth_sel2->fetchrow_array ) {
           #   $meth_TARGET .= "$hostip\n";
           #}
        }
        if ( $fk_name eq "" ) { $fk_name = "NULL"; } else { $fk_name = "'".$fk_name."'"; }
        if ( $scan_server eq "" ) { $scan_server = "NULL"; }

        $sql = qq{ INSERT INTO vuln_jobs ( name, username, fk_name, job_TYPE, meth_TARGET, meth_CRED, meth_VSET,
            meth_TIMEOUT, scan_ASSIGNED, scan_SUBMIT, scan_NEXT,  notify, tracker_id, authorized, resolve_names, author_uname, meth_Wfile, credentials ) VALUES (
            'SCHEDULED - $name', '$username', $fk_name, '$jobTYPE', '$meth_TARGET', '$meth_CRED', '$meth_VSET', 
            '$meth_TIMEOUT', '$scan_server', '$now', '$next_check', '$email', '$jid', '$scan_locally', $resolve_names, '$IP_ctx', $send_email, '$credentials' )  };
        safe_db_write ( $sql, 4 );            #use insert/update routine
    }
}

# get the job schedule table to setup reoccuring jobs
sub check_Kill {

    my ( $sql, $sth_sel, $sth_sel2, $now );

    $now = getCurrentDateTime();

    $sql = qq{ SELECT id, name, scan_PID FROM vuln_jobs WHERE status='P' };
    logwriter( $sql, 5 );
    $sth_sel=$dbh->prepare( $sql );
    $sth_sel->execute;

    while ( my ($kill_jid, $name, $job_pid ) = $sth_sel->fetchrow_array ) {

	open(KILLJOB,"kill -9 $job_pid |") || die "Failed: $!\n";
	while ( <KILLJOB> ) {
	    #-- do something here
	    logwriter( "PID=$job_pid".$_ , 2 );
	}        
	close KILLJOB;

        $sql = qq{ UPDATE vuln_jobs SET status='K' WHERE id='$kill_jid' };
        safe_db_write ( $sql, 2 );            #use insert/update routine
    }
}

sub check_access {
    my ( $txtData ) = @_;

    $txtData =~ s/&#039;/\"/g;
    $txtData =~ s/&amp;#039/\"/g;

    my $txt_output = "";

    if ( $txtData =~ /\"administrator\" account has no password set/ ) {
	$txt_output = "administrator no password";
    }elsif ( $txtData =~ /has no password set/ ) {
    	$txt_output = "user no password";
    }elsif ( $txtData =~ /SMB tests will be done as/ ) {
    	$txt_output = "authenticated user"; #WIN
    }elsif ( $txtData =~ /Local security checks have been enabled for this host/ ) {
    	$txt_output = "run linux checks"; #LINUX
    }elsif ( $txtData =~ /It was not possible to log into the remote host via ssh/ ) {
    	$txt_output = "invalid userpass"; #LINUX
    }elsif ( $txtData =~ /NULL sessions are enabled on the remote host/ ) {
    	$txt_output = "null session";
    } else {
	logwriter( "CHECK ACCESS - NO MATCH FOR ENTRY [$txtData]", 5 );
	$txt_output = "no match";
    }
    logwriter( "access_text=[$txt_output]", 3 );
    return $txt_output;
}

#called to update host tracker, most recent scan info for hosts
sub get_host_record {
    # VER: 1.7 MODIFIED: 12/29/08 15:18
    my ( $mac_address, $hostname, $hostip )  = @_;

    my ( $sql, $sth_sel );
    my ( $host_id ) = "0";

    my $now = getCurrentDateTime();
    #ENSURE HOST_ID IS LOOKED UP AFTER INSERT OR QUIT 
    #LOOKUP HOST_ID AGAIN FOR UPDATE OF MACS / INCIDENT TRACKER CODE
    $sql = qq{ SELECT id FROM vuln_hosts WHERE hostname = '$hostname' LIMIT 1 };
    logwriter( $sql, 5 );
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;
    ( $host_id ) = $sth_sel->fetchrow_array;

    if ( defined( $host_id ) && $host_id > 0 ) {

    } else {  #ADD NEW BARE RECORD FOR HOST
        $sql = "INSERT INTO vuln_hosts ( hostip, hostname, status, lastscandate, createdate ) VALUES (
            '$hostip', '$hostname', 'Production', '$now', '$now' );";
        safe_db_write ( $sql, 4 );

        $sql = qq{ SELECT id FROM vuln_hosts WHERE hostname = '$hostname' LIMIT 1 };
        logwriter( $sql, 5 );
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
        ( $host_id ) = $sth_sel->fetchrow_array;
    }
    return $host_id; # DO NOT USE MAC

    if ( defined( $mac_address ) && $mac_address ne "unknown" && $mac_address ne "" ) { 
        $sql = qq{ SELECT id, host_id FROM vuln_host_macs WHERE mac_address = '$mac_address' LIMIT 1 };
        logwriter( $sql, 5 );
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
        my ( $mac_id, $existing_host_id ) = $sth_sel->fetchrow_array;
        if ( defined( $mac_id ) && $mac_id > 0 ) {
            if ( $existing_host_id ne $host_id ) {
                logwriter( "DUPLICATE MAC: ASSUME EXISTING HOST RECORD OBSOLETE", 3 );

                $sql = qq{ DELETE FROM vuln_hosts WHERE id='$existing_host_id' };
                safe_db_write ( $sql, 3 );

                $sql = qq{ DELETE FROM vuln_host_stats WHERE host_id='$existing_host_id'};
                safe_db_write ( $sql, 4 );

                #$sql = qq{ DELETE FROM vuln_Incidents WHERE host_id='$existing_host_id'};
                #safe_db_write ( $sql, 4 );
            }
            $sql = "UPDATE vuln_host_macs SET host_id='$host_id', hostip='$hostip', LastSeen='$now'
                WHERE id='$mac_id'";
            safe_db_write ( $sql, 4 );
        } else {
            $sql = "INSERT INTO vuln_host_macs ( host_id, hostip, mac_address, LastSeen, createdate ) VALUES (
                '$host_id', '$hostip', '$mac_address',  '$now', '$now' );";
            safe_db_write ( $sql, 4 );
        }
    }

    return $host_id;
}

# returns an array of the plugins enabled for this scan id
sub get_plugins {
    #GETS PLUGINS & COMPLIANCE PLUGINS DIFFERS FROM SAME SUB NAME IN OTHER PER SCRIPT
    my ( $sid, $job_id ) = @_;
    my (@plugins);

    my ($sth_sel, $sth_sel2, $sql, $nessus_id, $nessus_value);

    #LOOKUP AUTOENABLE
    $sql = qq{ SELECT autoenable FROM vuln_nessus_settings WHERE id=$sid };
    $sth_sel=$dbh->prepare( $sql );
    $sth_sel->execute;

    my ($autoenable)=$sth_sel->fetchrow_array;

    #SCANNER SET
    $sql = qq{ SELECT t1.id, t1.enabled from vuln_nessus_settings_plugins t1
	LEFT JOIN vuln_nessus_category t2 ON t1.category=t2.id
        where t2.name ='scanner' and t1.sid='$sid' order by t1.id };
    $sth_sel=$dbh->prepare( $sql );
    $sth_sel->execute;

    while (($nessus_id, $nessus_value)=$sth_sel->fetchrow_array ) {
        if ($nessus_value eq "N") {
        } else {
            push(@plugins,"$nessus_id");
        }
    }

    if ($autoenable eq "C") {
        $sql = qq{ SELECT t1.cid, t1.status FROM vuln_nessus_settings_category t1
            LEFT JOIN vuln_nessus_category t2 ON t2.id=t1.cid
         WHERE t2.name<>"scanner" AND t1.sid=$sid ORDER BY t2.id };

        $sth_sel=$dbh->prepare( $sql );
        $sth_sel->execute;

        while ( my ($catid, $catstatus)=$sth_sel->fetchrow_array ) {
            $sql = qq{ SELECT id, enabled FROM vuln_nessus_settings_plugins
               WHERE sid='$sid' AND category='$catid' ORDER BY id };
            $sth_sel2=$dbh->prepare( $sql );
            $sth_sel2->execute;

            while (($nessus_id, $nessus_value)=$sth_sel2->fetchrow_array ) {
                if ($nessus_value eq "N") {
                } else {
                    push(@plugins,$nessus_id);
                }
            } #end loop
        } #end if
    } elsif ($autoenable eq "F") {

        $sql = qq{ SELECT t1.fid, t1.status FROM vuln_nessus_settings_family t1
            LEFT JOIN vuln_nessus_family t2 ON t2.id=t1.fid
         WHERE t2.name<>'Port scanners' AND t1.sid='$sid' ORDER BY t2.id };

        $sth_sel=$dbh->prepare( $sql );
        $sth_sel->execute;

        while ( my ($famid, $famstatus)=$sth_sel->fetchrow_array ) {

            $sql = qq{ SELECT id, enabled FROM vuln_nessus_settings_plugins
               WHERE sid='$sid' AND family='$famid' ORDER BY id };
            $sth_sel2=$dbh->prepare( $sql );
            $sth_sel2->execute;

            while (($nessus_id, $nessus_value)=$sth_sel2->fetchrow_array ) {
               if ($nessus_value eq "N") {
               } else {
                  push(@plugins,$nessus_id);
               }
            } #end loop
         } #end if

    } else {
        $sql = qq{ SELECT t1.id, t1.enabled from vuln_nessus_settings_plugins t1
	LEFT JOIN vuln_nessus_category t2 ON t1.category=t2.id
        where t2.name ='scanner' and t1.sid='$sid' order by t1.id };

        $sth_sel=$dbh->prepare( $sql );
        $sth_sel->execute;

        while (($nessus_id, $nessus_value)=$sth_sel->fetchrow_array ) {
            if ($nessus_value eq "N") {
            } else {
                push(@plugins,$nessus_id);
            }
        }
    }

    $sth_sel->finish;

    $sql = qq{ SELECT meth_CUSTOM, meth_CPLUGINS FROM vuln_jobs 
        WHERE id= '$job_id' LIMIT 1 };
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute(  );

    my ( $custom_type, $custom_plugins ) = $sth_sel->fetchrow_array(  );

    if ( $custom_type =~ /R/ ) {
        @plugins = (); 
    }
    if ( $custom_type =~ /A|R/ ) {
        my @cplugins = split /\n/, $custom_plugins;
        foreach $nessus_id ( @cplugins) {
            print "FORCE INCLUDE CUSTOM PLUGIN LIST- \"$nessus_id\"\n";
            push(@plugins,$nessus_id);
        }
    }

    #MAKE SURE COMPLIANCE PLUGINS INCLUDED FOR COMPLIANCE AUDIT ( IN CASE PROFILE DOESN'T INCLUDE THEM )
    if ( $isComplianceAudit ) {
        my @cplugins = split /\s/, $compliance_plugins;
        foreach $nessus_id ( @cplugins) {
            print "FORCE INCLUDE COMPLIANCE PLUGIN - \"$nessus_id\"\n";
            push(@plugins,$nessus_id);
        }
    }
    return(@plugins);
}

# get the prefs, both server and plugin
sub get_prefs {
    my ( $sid, $job_id ) = @_;
    my ($sth_sel, $sql, $nessus_id, $nessus_value, $nessus_dvalue);
    my $prefs = {};

    $sql = qq{ SELECT nessus_id, value, AES_DECRYPT(value,'$uuid') as dvalue FROM vuln_nessus_settings_preferences
        WHERE category IS NULL AND sid=$sid };
    $sth_sel=$dbh->prepare( $sql );
    $sth_sel->execute;

    while (($nessus_id, $nessus_value, $nessus_dvalue)=$sth_sel->fetchrow_array ) {
        # Exclude null nessus_value records
        if ($nessus_dvalue) {
            $prefs->{$nessus_id} = $nessus_dvalue;
        }
        elsif ($nessus_value) {
            $prefs->{$nessus_id} = $nessus_value;
        }
    }

   # get SERVER_PREFS
    $sql = qq{ SELECT nessus_id, value, AES_DECRYPT(value,'$uuid') as dvalue FROM vuln_nessus_settings_preferences
        WHERE category='SERVER_PREFS' AND sid=$sid };
    $sth_sel=$dbh->prepare( $sql );
    $sth_sel->execute;

    while (($nessus_id, $nessus_value, $nessus_dvalue)=$sth_sel->fetchrow_array ) {
      if(!defined($nessus_dvalue)) {  $nessus_dvalue = "";  }
      $prefs->{$nessus_id} = (defined($nessus_dvalue) && $nessus_dvalue ne "") ? $nessus_dvalue : $nessus_value;
    }

   #now get the plugin preferences
   ## modified 2/17/07 - hsh - enable storage of sensitive passwords
   ## in an encrypted form (Blowfish)
   ## initialize the encryption routines

   my $cipher = Crypt::CBC->new( {
        'key'                => $dbk,
        'cipher'        => 'Blowfish',
        'iv'                => substr($dbk,12,8),
        'regenerate_key'=> 0,
        'padding'        => 'null',
        'prepend_iv'    => 0 });

    $sql = qq{ SELECT nessus_id, value, AES_DECRYPT(value,'$uuid') as dvalue FROM vuln_nessus_settings_preferences
        WHERE category='PLUGINS_PREFS' AND sid=$sid };
    $sth_sel=$dbh->prepare( $sql );
    $sth_sel->execute;

    while (($nessus_id, $nessus_value, $nessus_dvalue)=$sth_sel->fetchrow_array ) {
      ## check for encrypted values and decrypt accordingly
      if($nessus_value =~ /^ENC\{(.*)\}/) {
         $nessus_value =~ s/^ENC\{//;
         chop($nessus_value );
         my $ciphertext = decode_base64($nessus_value);
         $nessus_value = $cipher->decrypt($ciphertext);
      }

      $prefs->{$nessus_id} = (defined($nessus_dvalue) && $nessus_dvalue ne "") ? $nessus_dvalue : $nessus_value;
   }

    # **** no leemos datos de vulncredentials ****
    #    $sql = qq{ SELECT meth_CRED, meth_Wcheck, meth_Wfile, meth_Ucheck FROM vuln_jobs 
    #        WHERE id= '$job_id' LIMIT 1 };
    #    $sth_sel = $dbh->prepare( $sql );
    #    $sth_sel->execute(  );
	
    #$sql = qq{ select meth_Wcheck, meth_Wfile, meth_Ucheck FROM vuln_jobs 
    #    WHERE id= '$job_id' LIMIT 1 };
    #$sth_sel = $dbh->prepare( $sql );
    #$sth_sel->execute(  );

    #my ( $wchecks, $wfchecks, $uchecks ) = $sth_sel->fetchrow_array(  );
    my ( $wchecks, $wfchecks, $uchecks ) = "";
    
    my $cred_id = 0; # **** valor constante ****
    
    if ( $cred_id != 0) {
        $sql = qq{ SELECT account, password, domain, password_type, ACC_TYPE, STORE_TYPE 
            FROM vuln_credentials WHERE id= '$cred_id' LIMIT 1 };
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute(  );
        my ( $accname, $accpass, $accdomain, $passType, $accType, $storMeth ) = $sth_sel->fetchrow_array(  );

        if ( defined ( $storMeth ) && $storMeth eq "C" ) {
            $sql = qq{ DELETE FROM vuln_credentials WHERE id='$cred_id' LIMIT 1 };
            safe_db_write ( $sql, 4 );            #REMOVE Pass
        }

        if ( defined ( $accname ) ) {
            $cred_name = $accname;        #UPDATE GLOBAL VARIABLE FOR USE IN LOAD_RESULTS
            my $cipher = Crypt::CBC->new( {
                'key'            => $dbk,
                'cipher'            => 'Blowfish',
                'iv'            => substr($dbk,12,8),
                'regenerate_key'=> 0,
                'padding'            => 'null',
                'prepend_iv'    => 0 });

            if($accpass =~ /^ENC\{(.*)\}/) {
               $accpass =~ s/^ENC\{//;
               chop($accpass);
               my $ciphertext = decode_base64($accpass);
               $accpass = $cipher->decrypt($ciphertext);
            }
            if ( $accType =~ /both/i ) {
                $prefs->{'Login configurations[entry]:SMB account :'} = $accname;
                $prefs->{'Login configurations[password]:SMB password :'} = $accpass;
                $prefs->{'Login configurations[entry]:SMB domain (optional):'} = $accdomain;
                $prefs->{'Login configurations[radio]:SMB password type :'} = $passType;
                $prefs->{'SSH settings[entry]:SSH user name :'} = $accname;
                $prefs->{'SSH settings[password]:SSH password (unsafe!) :'} = $accpass;
                $prefs->{'SSH settings[radio]:Elevate privileges with :'} = "Nothing";
            }elsif ( $accType =~ /ssh/i ) {
                $prefs->{'SSH settings[entry]:SSH user name :'} = $accname;
                $prefs->{'SSH settings[password]:SSH password (unsafe!) :'} = $accpass;
                $prefs->{'SSH settings[radio]:Elevate privileges with :'} = "Nothing";
            } else {
                $prefs->{'Login configurations[entry]:SMB account :'} = $accname;
                $prefs->{'Login configurations[password]:SMB password :'} = $accpass;
                $prefs->{'Login configurations[entry]:SMB domain (optional):'} = $accdomain;
                $prefs->{'Login configurations[radio]:SMB password type :'} = $passType;
            }
        }
    }

    my @checks = ();
    if ( $wchecks  ) { push(@checks, "W"); }
    if ( $wfchecks ) { push(@checks, "F"); }
    if ( $uchecks  ) { push(@checks, "U"); }

    foreach ( @checks ) {
        my $txt_name = "";
        my $check_type = "";
        my $txt_data = "";
        if (  $_ ) {
            switch ( $_ ) {
                case "W"        { 
                    $check_type = "W";
                    $txt_name = "Windows Compliance Checks[file]:Policy file #";
                    $txt_data = $wchecks;
                    }
                case "F"        {
                    $check_type = "F";
                    $txt_name = "Windows File Contents Compliance Checks[file]:Policy file #"; 
                    $txt_data = $wfchecks;
                    }
                case "U"        {
                    $check_type = "U";
                    $txt_name = "Unix Compliance Checks[file]:Policy file #"; 
                    $txt_data = $uchecks;
                    }
            }
        }

            my @tmp_check_arr = split /\n/, $txt_data;

        for ( my $i = 0; $i < 5; $i++) {
            if ( $tmp_check_arr[$i] ) {
                my $policy_num = $i + 1;
                if ( $check_type eq "W" && $policy_num == "1" ) { 
                    my $tmp_audit = $tmp_check_arr[$i];
                    $tmp_audit =~ s/$CONFIG{winAuditDir}\///;
                    $primaryAuditcheck = $tmp_audit;        #VERY IMPORT TO UPDATE SCAN_ID WITH vuln_nessus_checks ID
                }
                my $nessus_id = "$txt_name$policy_num :";
                logwriter( "COMPLIANCE AUDIT: [$nessus_id = $tmp_check_arr[$i]]", 4 );
                $use_scanlite = 0;  #MUST FALL BACK TO CLIENT MODE FOR Compliance Scan
                $isComplianceAudit = TRUE;  #Global Variable to flag complaince audits
                $prefs->{$nessus_id} = $tmp_check_arr[$i];
            }
        }
    } 

   $sth_sel->finish;
   return($prefs);
}

#setup next scan field based on job schedule input
sub gen_sched_next_scan {
    my ( $schedid, $schedule_type ) = @_;

    my ( $sth_sel, $sql, $next_run, $time_interval, $day_offset, $week_offset );   

    #RECODED TO ELIMINATE THE NON-SENSE
    
    if ($schedule_type ne "NW") {
        #select time_interval to skip some days or weeks
        if($schedule_type eq "D" || $schedule_type eq "W") {
            $sth_sel = $dbh->prepare( qq{ SELECT time_interval FROM vuln_job_schedule WHERE id='$schedid' } );
            $sth_sel->execute(  );
            $time_interval = $sth_sel->fetchrow_array(  ); 
            
            $day_offset  = $time_interval;
            $week_offset = 7*$time_interval;
        }
        
        if ($schedule_type eq "D") {
            $sql = qq{ SELECT next_CHECK + INTERVAL $day_offset DAY FROM vuln_job_schedule WHERE id='$schedid' };
        } elsif ($schedule_type eq "O") {
            $sql = qq{ DELETE FROM vuln_job_schedule WHERE id='$schedid' };
        } elsif ($schedule_type eq "W") {
            $sql = qq{ SELECT next_CHECK + INTERVAL $week_offset DAY FROM vuln_job_schedule WHERE id='$schedid' };
        } elsif ($schedule_type eq "M") {
            $sql = qq{ SELECT next_CHECK + INTERVAL 1 MONTH FROM vuln_job_schedule WHERE id='$schedid' }; 
        };

        if ($schedule_type ne "O") {
            $sth_sel = $dbh->prepare( $sql );
            $sth_sel->execute(  );
            $next_run = $sth_sel->fetchrow_array(  ); 

            $next_run  =~ s/://g;
            $next_run  =~ s/-//g;
            $next_run  =~ s/\s//g;
        }
        else {
            safe_db_write ( $sql, 4 );
            $next_run = "00000000000000"; 
        }
    }
    else {
        my %day_of_week = ("Su" => "sunday", 
                           "Mo" => "monday",
                           "Tu" => "tuesday",
                           "We" => "wednesday",
                           "Th" => "thursday",
                           "Fr" => "friday", 
                           "Sa" => "saturday");
        
        my %day_of_month = ("1" => "first", 
                            "2" => "second",
                            "3" => "third",
                            "4" => "fourth",
                            "5" => "fifth");
                            
        my %months = ("1" => "january", 
                      "2" => "february",
                      "3" => "march",
                      "4" => "april",
                      "5" => "may",
                      "6" => "june", 
                      "7" => "july",
                      "8" => "august",
                      "9" => "september",
                      "10" => "october",
                      "11" => "november",
                      "12" => "december");
                            

        $sql = qq{ SELECT day_of_week, day_of_month, next_CHECK FROM vuln_job_schedule WHERE id='$schedid' };
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute(  );
        my ( $day_of_week_db, $day_of_month_db, $next_check_db ) = $sth_sel->fetchrow_array(  );

        my ($year, $month) = (localtime(time()))[5,4];
        $year+=1900;
        $month+=1;
        
        $month+=1; #select next month
        if($month==13){
            $month=1; 
            $year++;
        }

        #$month = 1; # to debug

        my $last_date = "";
        my $i     = 1; # first, second, third ...
        my $total = 0;
        do {
            $next_run = ParseDate($day_of_month{$i}." ".$day_of_week{$day_of_week_db}." in ".$months{$month}." ".$year);
            #print $day_of_month{$i}." ".$day_of_week{$day_of_week_db}." in ".$months{$month}." ".$year."\n";
            #logwriter( "Parse date: ".$day_of_month{$i}." ".$day_of_week{$day_of_week_db}." in ".$months{$month}." ".$year, 4 );
            
            if($next_run eq "") {
                $i = 1; # to begin with the first day
                $month+=1;
                if($month==13){
                    $month=1; 
                    $year++;
                }
            }
            elsif($last_date ne $next_run){
                $last_date = $next_run;
                $total++;
                $i++;
            }
            else {
                $i++;
            }
        } while($day_of_month_db != $total);

        $next_check_db =~ s/........(......)/$1/;
        $next_run =~ s/^(........).*/$1/;
        $next_run = $next_run.$next_check_db; # date and time

    }

    logwriter( "\tnextscan=$next_run", 4 );

    if ($schedule_type ne "O") {
        $sql = qq{ UPDATE vuln_job_schedule SET next_CHECK='$next_run' WHERE id='$schedid' };
        safe_db_write ( $sql, 4 );            #use insert/update routine   
    }

    return $next_run; 
}

sub get_live_subnets {
    # VER: 1.0 MODIFIED: 8/05/08 16:
    my ( $sql, $sth_sel );

    my $target_list = "";
    return $target_list; # delete

    $sql = qq{ SELECT t1.CIDR 
        FROM vuln_subnets t1
        WHERE t1.status != 'available'
            AND t1.tiScanApproval = '1' 
            AND t1.serial_flag = 'N'
            AND t1.auditable = 'Y' };

    logwriter($sql, 4 );
    $sth_sel=$dbh->prepare( $sql );
    $sth_sel->execute;

    while( my ( $subnetName ) = $sth_sel->fetchrow_array) {
        if ( defined($subnetName) ) {
           $target_list .= $subnetName . "\n";
        }
    }
    return $target_list;
}

#get server credential / port info from the db to setup connect to nessus node
sub get_server_credentials {
    # VER: 1.1 MODIFIED: 2/01/08 12:33
    my ( $select_id, $weight ) = @_;

    if ( ! is_number( $weight ) ) {
        $weight = $server_slot;                #CRON JOBS USE $server_slot SLOTS PER SCAN
    }

    my ($sql, $sql1, $sth_sel, $sth_upd, $tmpserverid);

    $sql = qq{ SELECT id, max_scans, current_scans FROM vuln_nessus_servers 
        WHERE enabled='1' AND status='A' AND hostname='$select_id' };

    logwriter( $sql, 5 );

    $sth_sel=$dbh->prepare( $sql );
    $sth_sel->execute;
    my ($tmp_serverid, $tmp_max, $tmp_current, $remaining);
    while(($tmp_serverid, $tmp_max, $tmp_current)=$sth_sel->fetchrow_array) {
        logwriter("serverid: $tmp_serverid, max scans: $tmp_max, current scans: $tmp_current", 4);
        if ( is_number($tmp_serverid) && $tmp_max >= $weight ) {
            $remaining = $tmp_max - $tmp_current;        #REQUIRE $server_slot SLOTS FOR CRON SCAN
            if ( $remaining >= $server_slot ) {
                $sql = qq{ SELECT hostname, port, user, password,AES_DECRYPT(password,'$uuid') FROM vuln_nessus_servers WHERE id=$tmp_serverid };
                logwriter( $sql, 5 );
                $sth_sel = $dbh->prepare( $sql );
                $sth_sel->execute;
                my $dnessuspassword = "";
                ($nessushost, $nessusport, $nessususer, $nessuspassword, $dnessuspassword)=$sth_sel->fetchrow_array;
                
                logwriter( "dnessuspassword: $dnessuspassword for sensor_id: $select_id ", 4 );
                
                $nessuspassword = $dnessuspassword if (defined($dnessuspassword) && $dnessuspassword ne "");
                $sth_sel->finish;
                # overlay credentials with ossim conf file 
                #$nessusport = $CONFIG{'NESSUSPORT'}; 
                #$nessususer = $CONFIG{'NESSUSUSER'};
                #$nessuspassword = $CONFIG{'NESSUSPASSWORD'};
                #
                return $tmp_serverid;
            }
        }
    }
    $sth_sel->finish;
    # overlay credentials with ossim conf file
    $nessushost = $CONFIG{'NESSUSHOST'};
    $nessusport = $CONFIG{'NESSUSPORT'}; 
    $nessususer = $CONFIG{'NESSUSUSER'};
    $nessuspassword = $CONFIG{'NESSUSPASSWORD'};
    #
    return 0;

}

# LOOKUP ALL PLUGINS WITH A CUST RISK VALUE ( NO NEED TO RESTRICT TO THIS PROFILE ONLY )
sub get_custom_risks {

    my $plugins = {};

    my $sql = qq{ SELECT id, custom_risk FROM vuln_nessus_plugins
         WHERE custom_risk IS NOT NULL };
    my $sth_sel=$dbh->prepare( $sql );
    $sth_sel->execute;

    while ( my ($plug_id, $plug_risk )=$sth_sel->fetchrow_array ) {
        if ($plug_id) {
            $plugins->{$plug_id} = $plug_risk;
        }
    } #end if
    return($plugins);
}

#reusable routine to get subnet id from CIDR
sub getSubnetID {
    # VER: 1.0 MODIFIED: 3/29/07 13:03
    my ( $subnet ) = @_;
    my ($sql, $sth_sel, $sub_id);

    ### CHECK THE DATABASE FOR A MATCHING SUBNET
    $sql = qq{ SELECT id FROM vuln_subnets WHERE Name='$subnet' LIMIT 1 };

    logwriter( $sql, 5 );
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute(  );
    ### Retrieve the returned rows of data
    while ( my ( $id ) = $sth_sel->fetchrow_array(  ) ) {
        $sth_sel->finish;
        return $id;
    }
}

#get current date/time
sub getCurrentDateTime {
    return strftime "%Y%m%d%H%M%S", gmtime;
}

#read in data from results file <- returns ( array of hashes ) $issues
sub get_results_from_file {
    my ( $outfile ) = @_;

    if ( ! -r $outfile ) { $no_results = TRUE; return FALSE; }

    my @issues;
    my ($rec_type, $domain, $host, $port, $description, $service, $proto, $scan_id, $risk_type );
    my $total_records = 0;
    logwriter("get_results_from_file:Outfile: $outfile", 4);
    # loop through input file and insert into table
    open(INPUT,"<$outfile")|| die("Can't open report file");

     while (<INPUT>) {
        # Initialize field values each time to ensure the are clear
        #
        my ($host, $domain, $scan_id, $description, $service, $app, $port, $proto, $rec_type, $risk_type ) = "";
        ( $rec_type, $domain, $host, $service, $scan_id, $risk_type, $description )=split(/\|/,$_);

        if ( $rec_type =~ /results/ ) {
            if ( $service =~ /general/ ) {
                my @temp = split /\//, $service;
                $app = "general";
                $proto = $temp[1];
                $port = "0";
            } else {
                my @temp = split /\s/, $service;
                $app = $temp[0];
                $temp[1] =~ s/\(//;
                $temp[1] =~ s/\)//;
                my @temp2 = split /\//, $temp[1];
                $port = $temp2[0];
                $proto = $temp2[1];
            }
            if (defined($scan_id)){
                logwriter("get_results_from_file:scan_id:$scan_id", 4); 
            }
            if (defined($compliance_plugins)){
                logwriter("get_results_from_file:compliance_plugins:$compliance_plugins", 4);
            }
            
            my @cplugins = split /\s/, $compliance_plugins;
            
            if ( defined($scan_id) && in_array(\@cplugins,$scan_id) ) {
                #UPDATE SCANID FOR WIN CHECKS #21156
                if ( $scan_id =~ /21156/ ) {
                    my ( $test_name, $test_policy ) = "";
                    my @temp = split(/\\n/, $description);
                    foreach my $line (@temp) {
                        $line =~ s/\#.*$//;
                        chomp($line);
                        $line =~ s/\s+$//;
                        $line =~ s/^\s+//;
                        if ($line eq "") { next; }
                        $line =~ s/"//g;
                        if ( $line =~ /\[[EFP][AR][IRS][OLS][ER]D*\]/ ) {
                            $test_name = $line;
                            $test_name =~ s/\[[EFP][AR][IRS][OLS][ER]D*\]//;
                            $test_name =~ s/\s+$//;
                            $test_name =~ s/^\s+//;
                            $test_name =~ s/:$//;
                        }
                    }
                    if ( defined($test_name) && $test_name ne ""  ) {
                        #my $sql = qq{ SELECT t1.id FROM vuln_nessus_checks t1
                        #    LEFT JOIN vuln_nessus_checks_audits t2 on t1.id=t2.cid
                        #    WHERE t2.auditfile ='$primaryAuditcheck' AND
                        #    t1.name='$test_name' LIMIT 1 };
                        #logwriter( $sql, 5 );
                        #my $sth_sel = $dbh->prepare( $sql );
                        #$sth_sel->execute(  );
                        #my ( $tmp_scan_id ) = $sth_sel->fetchrow_array(  );
                        #if ( defined( $tmp_scan_id) && $tmp_scan_id >= 60000 ) { $scan_id = $tmp_scan_id; }
                    }
                }
                
                my $risk_value = "";
                if ( $description =~ m/\[PASSED\]/ ) {
                    $risk_value = "Risk factor : \n\nPassed\n";
                } elsif ( $description =~ m/\[FAILED\]/ ) {
                    $risk_value = "Risk factor : \n\nFailed\n";
                } else {
                    $risk_value = "Risk factor : \n\nUnknown\n";
                }
                $description .= "$risk_value";
                logwriter("set compliance description: $risk_value",5);
            }

            my $risk_factor = "Info";
            if(defined $risk_type) {
                if ($risk_type =~ /^(LOW|Security.Note)/i) {
                    $risk_factor = "Low";
                }
                elsif ($risk_type =~ /^(MEDIUM|Security.Warning)/i) {
                    $risk_factor = "Medium";
                }
                elsif ($risk_type =~ /^(HIGH|Security.Hole)/i) {
                    $risk_factor = "High";
                }
                elsif ($risk_type =~ /^REPORT/i) {
                    $risk_factor = "High";
                }
                elsif ($risk_type =~ /^Serious/i) {
                    $risk_factor = "Serious";
                }
            }
            
            if ( $description ) {   #ENSURE WE HAVE SOME DATA
                $description =~ s/\\/\\\\/g;	#FIX TO BACKSLASHES
                $description =~ s/\\\\n/\\n/g;	#FIX TO NEWLINE

                my $temp = {
                    Port            => $port,
                    Host            => $host,
                    Description     => $description,
                    Service         => $app,
                    Proto           => $proto,
                    ScanID          => $scan_id,
                    RiskFactor      => $risk_factor
                };
                logwriter ( "my temp = { Port=>$port, Host=>$host, Description=>$description, Service=>$app, Proto=>$proto, ScanID=>$scan_id, RiskFactor=>$risk_factor  };\n", 5);
                push ( @issues, $temp );
                $total_records += 1;
            }
        }
    }

    if ($total_records eq 0 ) { $no_results = TRUE; }

#    for my $href ( @issues ) {
#        print "{ ";
#        for my $role ( keys %$href ) {
#            print "$role=$href->{$role} ";
#        }
#        print "}\n";
#    }

    return @issues;
}


#sent the notification per the build notification process

#sub send_email {
    # VER: 1.0 MODIFIED: 7/18/07 8:35
#    my ( $to, $cc, $subject, $type, $message, $url ) = @_;
#    logwriter( "to=$to\ncc=$cc\nfrom=" . $CONFIG{'mailfrom'} . "\nsub=" . $subject . "\n\nmsg=\n" . $message, 5 );
#    
#
#    if ( $type =~ /html/i ) {
#        my $mailHTML = new MIME::Lite::HTML
#            To      => $to,
#            CC      => $cc,
#            From    => $CONFIG{'mailfrom'},
#            Subject => $subject;
#
#        my $MIMEmail = $mailHTML->parse( $url );
#        $MIMEmail->send;
#    } else {
#        my $msg = MIME::Lite->new(
#            To      => $to,
#            CC      => $cc,
#           From    => $CONFIG{'mailfrom'},
#            Subject => $subject,
#            Data    => $message
#        );
#        $msg->send();
#    }
#}


#needed for report stats
sub datediff {
    # VER: 1.0 MODIFIED: 3/29/07 13:03
    my ( $start_date, $end_date, $unit ) = @_;
    my ( %start, %end );

    if ( ! defined( $start_date ) || ! defined( $end_date ) ) {
        return  -1;
    }

    $start_date =~ s/\///g;
    $start_date =~ s/\s//g;
    $start_date =~ s/-//g;
    $start_date =~ s/://g;

    $start{YEAR} = substr($start_date, 0,4 );
    $start{MO} = substr($start_date, 4,2 );
    $start{D} = substr($start_date, 6,2 );
    $start{H} = substr($start_date, 8,2 );
    $start{M} = substr($start_date, 10,2 );
    $start{S} = substr($start_date, 12,2 );

    $end_date =~ s/\///g;
    $end_date =~ s/\s//g;
    $end_date =~ s/-//g;
    $end_date =~ s/://g;

    $end{YEAR} = substr($end_date, 0,4 );
    $end{MO} = substr($end_date, 4,2 );
    $end{D} = substr($end_date, 6,2 );
    $end{H} = substr($end_date, 8,2 );
    $end{M} = substr($end_date, 10,2 );
    $end{S} = substr($end_date, 12,2 );

    my ($Dd,$Dh,$Dm,$Ds) = Delta_DHMS($start{YEAR},$start{MO},$start{D}, $start{H},$start{M},$start{S},
        $end{YEAR},$end{MO},$end{D}, $end{H},$end{M},$end{S});

    my $diff = 0;

    if ( $Dd ) { $diff += ( $Dd * 216000 ); }
    if ( $Dh ) { $diff += ( $Dh * 3600 ); }
    if ( $Dm ) { $diff += ( $Dm * 60 ); }
    if ( $Ds ) { $diff += $Ds }

    if ( $unit eq "D" ) {
        $diff = ( $diff / 216000 );
    } elsif ( $unit eq "H" ) {
        $diff = ( $diff / 3600 );
    } elsif ( $unit eq "M" ) {
        $diff = ( $diff / 60 );
    } else {
        # Already seconds do not convert; 
    }

    $diff = sprintf("%.2f", $diff);

    return $diff;
}

#is this a num
sub is_number{
    # VER: 1.0 MODIFIED: 3/29/07 13:03
    my($n)=@_;

    if ( $n ) { 
        return ($n=~/^\d+$/);
    } else {
        return;
    }
}

#filter html special characters
sub htmlspecialchars {
    # VER: 1.0 MODIFIED: 3/29/07 13:03
    my $tmpSTRmsg = $_[0];
    $tmpSTRmsg =~ s/&/&amp;/g;
    $tmpSTRmsg =~ s/\'/&#039;/g;
    $tmpSTRmsg =~ s/\"/&quot;/g;
    $tmpSTRmsg =~ s/</&lt;/g;
    $tmpSTRmsg =~ s/>/&gt;/g;
    return $tmpSTRmsg;
}

#read file to a string
sub read_file {
    # VER: 1.0 MODIFIED: 6/27/08 11:36
    my ( $myfile ) = @_;
    my ( $string ) = "";

    open FILE, "$myfile" or die "Couldn't open file: $!"; 
    while (<FILE>){
        $string .= $_;
    }
    close FILE;

    return $string;

}

sub trim {
    my ( $string ) = @_;

    if ( defined ($string) && $string ne "" ) {
	$string =~ s/^\s+//;
	$string =~ s/\s+$//;
	return $string;
    } else {
	return "";
    }
}


#read inprotect_settings from db (overrides settings in file)
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

#read settings from file
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

#safe write code to help prevent complete job failure
sub safe_db_write {
    # VER: 1.0 MODIFIED: 1/16/08 7:36
    my ( $sql_insert, $specified_level ) = @_;

    logwriter( $sql_insert, $specified_level );
    if ( check_dbOK() == "0" ) { $dbh = conn_db(); }
    
    eval {
        $dbh->do( $sql_insert );
    };
    warn "[$$] FAILED - $sql_insert\n" . $dbh->errstr . "\n\n" if ($@);

    if ( $@ ) { return 0; }
}

#safe to use when table structure may not be accurate, or where a select query is problematic
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

#check db is up
sub check_dbOK {
    # VER: 1.1 MODIFIED: 11/26/07 10:08
    my $sql = "SELECT count( hostname ) FROM vuln_nessus_servers WHERE 1";

    eval {
            $dbh->do( $sql );
    };
    
    warn "[$$] FAILED - Connection Test\n" . $dbh->errstr . "\n\n" if ($@);
    if ( $@ ) { return 0; }
    return 1;
}

#routine to do log writing
sub logwriter {
   # VER: 1.0 MODIFIED: 4/21/08 20:19
    my ( $message, $specified_level ) = @_;

    if ( !defined($specified_level) || $specified_level eq "" ) { $specified_level = 5; }

    my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
    $year+=1900;
    $mon++;
    my $now = sprintf("%04d-%02d-%02d %02d:%02d:%02d",$year, $mon, $mday, $hour, $min, $sec);

    $message = "$now [$$] $loginfo{$specified_level} $message";

    if(!defined($log_level)) { $log_level = 0; }
    
    if ( $debug || $log_level ge $specified_level )  { print $message ."\n"; }


}

#connect to db
sub conn_db {
    # VER: 1.0 MODIFIED: 3/29/07 13:03
    $dbh = DBI->connect("$CONFIG{'DATABASEDSN'}:$CONFIG{'DATABASENAME'}:$CONFIG{'DATABASEHOST'}",
        "$CONFIG{'DATABASEUSER'}","$CONFIG{'DATABASEPASSWORD'}", {
        PrintError => 0,
        RaiseError => 1,
        AutoCommit => 1 } ) or die("Failed to connect : $DBI::errstr\n");
        
    $sql = qq{ SET SESSION time_zone='+00:00' };

    safe_db_write ( $sql, 5 );
    
    return $dbh;
}

#disconnect from db
sub disconn_db {
    # VER: 1.0 MODIFIED: 3/29/07 13:03
    my ( $dbh ) = @_;
    $dbh->disconnect or die("Failed to disconnect : $DBI::errstr\n");
}

sub timeout {

    my (%hostHash) = %{$_[0]};
    my ($job_id)    = $_[1];
    my ($job_title) = $_[2];
    my ($scantype)  = $_[3];
    my ($username)  = $_[4];
    my ($sid) = $_[5];
    my ($scantime)  = $_[6];
    my ($fk_name)   = $_[7];
    my ($sql, $serverid, $sth_sel);
    logwriter("Function timeout - Job Id=$job_id", 4);
    

    $sql = qq{ UPDATE vuln_jobs SET status='T', meth_Wcheck=CONCAT(meth_Wcheck, 'Timeout expired<br />'), scan_END=now(), scan_NEXT=NULL WHERE id='$job_id' };
    safe_db_write( $sql, 5);

    $sql = qq{ SELECT scan_SERVER FROM vuln_jobs WHERE id='$job_id' };
    logwriter( $sql, 5 );
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute(  );
    $serverid = $sth_sel->fetchrow_array(  );

    $sql = qq{ UPDATE vuln_nessus_servers SET current_scans=current_scans-$server_slot WHERE id=$serverid AND current_scans>=$server_slot };
    logwriter( $sql, 5 );
    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute(  );

    if ( process_results( \%hostHash, $job_id, $job_title, $scantype, $username, $sid, $scantime, $fk_name ) ){
        logwriter( "[$job_title] [ $job_id ] Completed SQL Import", 4 );
    } else {
        logwriter( "[$job_title] Failed SQL Import", 2 );
    }
}

sub set_job_timeout {
    my ($job_id) = $_[0];
	my ($sth_sel, $sql);
    logwriter("Function set_job_timeout - Job Id=$job_id", 4);

    
    $sql = qq{ UPDATE vuln_jobs SET status='T', meth_Wcheck=CONCAT(meth_Wcheck, 'Timeout expired<br />'), scan_END=now(), scan_NEXT=NULL WHERE id='$job_id' };
    safe_db_write( $sql, 5);
}

sub maintenance {
    my ($sth_sel, $sql, $sth_seli, $sqli);
    my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
    $year+=1900;
    $mon++;
    #logwriter("maintenance-".$year."-".$mon."-".$mday."-".$hour."-".$min."-".$sec,4);
   if ($min==0 && $hour==4) {
        # maintenance jobs
        logwriter("Maintenance Jobs", 4);
        $sql = qq{ SELECT id, status, scan_NEXT, scan_START FROM vuln_jobs };
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
        while( my ($job_id, $status, $scan_next, $scan_start)=$sth_sel->fetchrow_array) {
            if($status eq "R" && !$scan_next && datediff($scan_start, $year."-".$mon."-".$mday." ".$hour.":".$min.":".$sec, 'H')>=48){
                $sqli = qq{ UPDATE vuln_jobs SET status='T' WHERE id='$job_id' };
                logwriter( $sql, 5 );
                $sth_seli = $dbh->prepare( $sqli );
                $sth_seli->execute(  );
            }
       }
       update_scan_status ( );
       remove_dup_hosts ( );
       #remove_dup_incidents ( );
    }
}

sub update_scan_status {
    my $sql;

    logwriter( "BEGIN - UPDATE SCAN STATUS", 4 );
    my $time_start = time();

    logwriter( "CALLED UPDATE SCAN STATUS", 5 );

    #DEFAULT TO INVALID STATE TO MAKE SURE WE DON'T ACCIDENTLY PRUNE ALL RECORDS
    #SHOULD THE WEBFORM HAVE A NON NUMERIC VALUE OR LESS THAT 90
    my $max_age = "-1";

    if ( is_number( $CONFIG{'maxScanAge'} ) && $CONFIG{'maxScanAge'} >= 7 ) {
       $max_age = $CONFIG{'maxScanAge'};
       logwriter( "MAX_AGE=$max_age", 5 );
    }

    if ( $max_age > 0 ) {
       my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(($time_start - ( 86400 * $max_age)));
       $year+=1900;
       $mon++;

       my $maxage_date = sprintf("%04d%02d%02d%02d%02d%02d",$year, $mon, $mday, $hour, $min, $sec);
       logwriter( "MARKING HOSTS PENDING SCAN WITH NO RECENT SCAN AS OF DATE $maxage_date", 4 );

       $sql = qq{ UPDATE vuln_hosts SET scanstate='Pending Scan' WHERE lastscandate <= '$maxage_date' AND inactive=0 };
       safe_db_write( $sql, 4);
    }

    my $time_run = time() - $time_start;
    logwriter( "FINISH - UPDATE SCAN STATUS [ Process took $time_run seconds ]", 4 );

}
sub remove_dup_hosts {
    my ($sql, $sth_sel, $sth_sel2);
    logwriter( "BEGIN - REMOVE DUPLICATE HOSTS", 4 );
    my $time_start = time();

    $sql = qq{ SELECT id, hostname FROM vuln_hosts WHERE inactive = '0' };

    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;

    while( my ($host_id, $hostname )=$sth_sel->fetchrow_array) {

	if ( $hostname =~ /\./ ) {

	    #print "valid fqdn [$hostname]\n";


	} else {

	    $sql = qq{ SELECT count(hostname) FROM vuln_hosts WHERE hostname LIKE '%$hostname%' AND inactive = '0' };
	    print "query=[$sql]\n";
	    $sth_sel2 = $dbh->prepare( $sql );
	    $sth_sel2->execute;
	    my ( $count ) = $sth_sel2->fetchrow_array ( );

	    if ( $hostname ne "" && $count > 1 ) {		
	        print "REMOVING THIS DUPLICATE HOST: [ $hostname ]\n";

	        $sql = qq{ DELETE FROM vuln_hosts WHERE id='$host_id' };		
	        safe_db_write ( $sql, 4 );

			
    	        $sql = qq{ SELECT id FROM vuln_hosts WHERE hostname LIKE '%$hostname%' AND id !='$host_id' LIMIT 1 };
		$sth_sel2 = $dbh->prepare( $sql );
	        $sth_sel2->execute;
	        my ( $replace_id  ) = $sth_sel2->fetchrow_array ( );
	
       	        if ( $replace_id ) {
	
      	    #        $sql = qq{ UPDATE vuln_host_macs SET host_id='$replace_id' WHERE host_id='$host_id'};
		    #safe_db_write ( $sql, 4 );
			
    	  	#    $sql = qq{ UPDATE vuln_host_stats SET host_id='$replace_id' WHERE host_id='$host_id'};
		    #safe_db_write ( $sql, 4 );
			
      		#    $sql = qq{ UPDATE vuln_Incidents SET host_id='$replace_id' WHERE host_id='$host_id'};
		    #safe_db_write ( $sql, 4 );
       		}	
	    }
            $sth_sel2->finish;
	}
    }

    $sth_sel->finish;

    my $time_run = time() - $time_start;
    logwriter( "FINISH - REMOVE DUPLICATE HOSTS [ Process took $time_run seconds ]", 4 );

}
sub remove_dup_incidents {
    my ($sql, $sth_sel, $sth_sel2);

    logwriter( "BEGIN - REMOVE DUPLICATE INCIDENTS", 4 );
    my $time_start = time();

    $sql = qq{ SELECT DISTINCT host_id FROM vuln_Incidents };

    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;

    while( my ($host_id )=$sth_sel->fetchrow_array) {

        $sql = qq{ SELECT count( scriptid ) AS vcount, scriptid, service, id, min( date_open ), max( date_lastseen ), max( datelastupdate )
FROM vuln_Incidents
WHERE host_id = '$host_id'
AND status = 'open'
GROUP BY scriptid, service
ORDER BY vcount DESC };

	$sth_sel2 = $dbh->prepare( $sql );
	$sth_sel2->execute;
	while ( my ( $count, $scriptid, $service, $id, $date_open, $date_lastseen, $date_updated ) = $sth_sel2->fetchrow_array ( ) ) {
            if ( $scriptid ne "" && $count > 1 ) {
	    	#print "query=[$sql]\n";
	        my $limit = ( $count - 1 );
	        print "REMOVING THIS DUPLICATE COUNT $limit OF $count RESULTS FOR HOST: [ $host_id, $scriptid, $service ]\n";

		$sql = qq{ DELETE FROM vuln_Incidents WHERE host_id='$host_id' AND scriptid='$scriptid' AND service='$service' LIMIT $limit };		
	        safe_db_write ( $sql, 4 );
		#print "query=[$sql]\n";
	        $sql = qq{ UPDATE vuln_Incidents SET date_open='$date_open', date_lastseen='$date_lastseen', 
		datelastupdate='$date_updated' WHERE host_id='$host_id' AND scriptid='$scriptid' AND service='$service' LIMIT 1 };
	        safe_db_write ( $sql, 4 );
		#print "query=[$sql]\n";
	    }
	}
        $sth_sel2->finish;
    }

    $sth_sel->finish;

    my $time_run = time() - $time_start;
    logwriter( "FINISH - REMOVE DUPLICATE INCIDENTS [ Process took $time_run seconds ]", 4 );
}

sub ipinnet {
  my $ip = shift;
  my $cidr = shift;

  if ($cidr =~ /(.*)\/(.*)/) {
    my $net = $1; my $bits = $2;
    my $val1 = unpack("N", pack("C4", split(/\./, $ip)));
    my $val2 = unpack("N", pack("C4", split(/\./, $net)));
    my $matchbits = 32 - ($bits || 32);
    return 1 if (($val1 >> $matchbits) == ($val2 >> $matchbits));
  }
  return 0;
}

sub genID {
    my $table = shift;
    my $sth_lastid;
    
    my $sql_genID = qq {UPDATE $table SET id=LAST_INSERT_ID(id+1) };
    safe_db_write( $sql_genID, 4 );
    
    my $last_id_query = qq{SELECT LAST_INSERT_ID() as lastid};
    $sth_lastid = $dbh->prepare($last_id_query);
    $sth_lastid->execute;
    my ($last_id) = $sth_lastid->fetchrow_array;
    $sth_lastid->finish;
    return $last_id;
}

sub calc_priority {
    my $risk = shift;
    my $hostid = shift;
    my $nessusid = shift;
    
    # If it's not set, set it to 1
    my ($sql_inc, $sth_inc, $priority);

    my $risk_value = $risk;
    $risk_value = 7 if ($risk>5);
    $risk_value = 9 if ($risk>6);

    return $risk_value;
    
    #my $risk_value = 1;
    #if ($risk eq "NOTE") {
    #    $risk_value = 0;
    #}
    #elsif ($risk eq "INFO") {
    #    $risk_value = 1;
    #}
    #elsif ($risk eq "Security Note") {
    #    $risk_value = 1;
    #}
    #elsif ($risk eq "LOW") {
    #    $risk_value = 3;
    #}
    #elsif ($risk eq "Security Warning") {
    #}
    #    $risk_value = 3;
    #elsif ($risk eq "MEDIUM") {
    #    $risk_value = 5;
    #}
    #elsif ($risk eq "HIGH") {
    #    $risk_value = 8;
    #}
    #elsif ($risk eq "Security Hole") {
    #    $risk_value = 8;
    #}
    #elsif ($risk eq "REPORT") {
    #    $risk_value = 10;
    #}

    $sql_inc = qq{ SELECT asset FROM host WHERE id = UNHEX('$hostid') };
    $sth_inc = $dbh->prepare($sql_inc);
    $sth_inc->execute();
    my ($asset) = $sth_inc->fetchrow_array;
    $sth_inc->finish;
    
    if ($asset eq "") {
        $asset = 0;
    }
    
    $sql_inc = qq{ SELECT reliability FROM plugin_sid WHERE sid = '$nessusid' };
    $sth_inc = $dbh->prepare($sql_inc);
    $sth_inc->execute();
    my ($reliability) = $sth_inc->fetchrow_array;
    $sth_inc->finish;
    
    if ($reliability eq "") {
        $reliability = 0;
    }
    # FIXME: check this formula once the values are clear. This is most definetivley wrong.
    $priority = int( ($risk_value + $asset + $reliability) / 1.9 );
    $priority = 10 if ($priority>10);
    $priority = 0 if ($priority<0);
    return $priority;
}

sub update_ossim_incidents {
        my $hostip   = shift;
        my $ctx      = shift;
        my $hostid   = shift;
        my $port     = shift;
        my $risk     = shift;
        my $desc     = shift;
        my $scanid   = shift;
        my $username = shift;
        my $sid      = shift;
        
        my ($sql_inc, $sth_inc);
        
        my $id_pending = 65001;
        my $id_false_positive = 65002;
        
        $risk = 8 - $risk; # convert into ossim risk
        #logwriter("update_ossim_incidents - risk = $risk",5);
        #logwriter("update_ossim_incidents - threshold = $vuln_incident_threshold",5);
        
        return if ($vuln_incident_threshold >= $risk);  
        
        #Check if exists a vulnerability already create
        $sql_inc = qq{ SELECT incident_id FROM incident_vulns WHERE ip = '$hostip' AND ctx = UNHEX('$ctx') AND port = '$port' AND nessus_id = '$scanid' };
        $sth_inc = $dbh->prepare($sql_inc);
        $sth_inc->execute();
        my ($id_inc) = $sth_inc->fetchrow_array;
        $sth_inc->finish;

        if ( noEmpty($id_inc) ) {
            $sql_inc = qq{ UPDATE incident SET last_update = now() WHERE id = '$id_inc' };
            safe_db_write( $sql_inc, 4 );
            $sql_inc = qq{ SELECT priority FROM incident WHERE status='Closed' and id = '$id_inc' };
            $sth_inc = $dbh->prepare($sql_inc);
            $sth_inc->execute();
            my ($priority) = $sth_inc->fetchrow_array;
            $sth_inc->finish;
            if ( noEmpty($priority) ) {
                $sql_inc = qq{SELECT incident_id FROM incident_tag WHERE incident_tag.incident_id = '$id_inc' AND incident_tag.tag_id = '$id_false_positive' };
                
                logwriter($sql_inc, 4);
                
                $sth_inc = $dbh->prepare($sql_inc);
                $sth_inc->execute();
                my ($hash_false_incident) = $sth_inc->fetchrow_array;
                $sth_inc->finish;
                if ( Empty($hash_false_incident) ) {
                    $sql_inc = qq{ UPDATE incident SET status = 'Open' WHERE id = '$id_inc' AND in_charge = '$username' };

                    safe_db_write( $sql_inc, 4 );
                    my $id_sql = qq{ select max(id)+1 as ticket_id from incident_ticket };
                    
                    $sth_inc = $dbh->prepare( $id_sql );
                    $sth_inc->execute;
                    my ( $ticket_id ) = $sth_inc->fetchrow_array;
                    my $sql_ticket = qq { INSERT INTO incident_ticket (id, incident_id, date, status, priority, users, description) values ('$ticket_id', '$id_inc', now(), 'Open', '$priority', 'admin','Automatic open of the incident') };
                    
                    $sth_inc = $dbh->prepare($sql_ticket);
                    $sth_inc->execute();
                    $sth_inc->finish;
                    }
            }
        }
        else {
            $sql_inc = qq{SELECT name,reliability,priority FROM plugin_sid where plugin_id = 3001 and sid = '$scanid'};
            $sth_inc = $dbh->prepare($sql_inc);
            $sth_inc->execute();
            my ($name_psid, $reliability_psid, $priority_psid) = $sth_inc->fetchrow_array;
            $sth_inc->finish;
            my $vuln_name = "";
            if ( noEmpty( $name_psid) ) {
                $vuln_name = $name_psid;
            }
            else{
                $vuln_name = "Vulnerability - Unknown detail";
            }
            my $priority = calc_priority($risk, $hostid, $scanid);
            $sql_inc = qq{ INSERT INTO incident(uuid, ctx, title, date, ref, type_id, priority, status, last_update, in_charge, submitter, event_start, event_end)
                            VALUES(UNHEX(REPLACE(UUID(), '-', '')), UNHEX('$ctx'), "$vuln_name", now(), 'Vulnerability', 'Nessus Vulnerability', '$priority', 'Open', now(), '$username', 'nessus', '0000-00-00 00:00:00', '0000-00-00 00:00:00') };
            safe_db_write ($sql_inc, 4);
            # TODO: change this for a sequence
            $sql_inc = qq{ SELECT MAX(id) id from incident };
            $sth_inc = $dbh->prepare($sql_inc);
            $sth_inc->execute();
            my ($incident_id) = $sth_inc->fetchrow_array;
            $sth_inc->finish;
            #sanity check
            $desc =~ s/\"/\'/g;
            $desc =~ s/^ *| *$//g;
            $desc =~ s/^[\n\r\t]*//g;
            $desc .= "\nSID:$sid";
            
            my $id_sql = qq{ select max(id)+1 as new_id from incident_vulns };
            
            $sth_inc = $dbh->prepare( $id_sql );
            $sth_inc->execute;
            my ( $incident_vulns_id ) = $sth_inc->fetchrow_array;
            
            $sql_inc = qq{ INSERT INTO incident_vulns(id, incident_id, ip, ctx, port, nessus_id, risk, description) VALUES('$incident_vulns_id', '$incident_id', '$hostip', UNHEX('$ctx'), '$port', '$scanid', '$risk', \"$desc\") };
            safe_db_write ($sql_inc, 4);
            $sql_inc = qq{ INSERT INTO incident_tag(tag_id, incident_id) VALUES($id_pending, '$incident_id') };
            safe_db_write ($sql_inc, 4);
        }
}

sub get_server_data {
	my $sensor_id = shift;
    my $sql = qq{ SELECT vns.port, vns.user, vns.password, AES_DECRYPT(vns.password,'$uuid'), inet6_ntop(s.ip) 
    				FROM vuln_nessus_servers vns, sensor s
    				WHERE vns.enabled='1' AND vns.hostname='$sensor_id' AND HEX(s.id) = UPPER(vns.hostname) };
    my $sthss=$dbh->prepare( $sql );
    $sthss->execute;
    my @datass=$sthss->fetchrow_array;
    if (scalar(@datass) == 0) { @datass = ('', '', '', '', ''); }
    $sthss->finish;

    $datass[1] =~ s/'/'"'"'/g if(noEmpty($datass[1]));
    $datass[2] =~ s/'/'"'"'/g if(noEmpty($datass[2]));
    $datass[3] =~ s/'/'"'"'/g if(noEmpty($datass[3]));

    return @datass;
}

sub get_sensor_name {
	my $ipse = shift;
    my $sql = qq{ SELECT name FROM sensor WHERE ip='$ipse' };
    my $sthse=$dbh->prepare( $sql );
    $sthse->execute;
    my ($sname)=$sthse->fetchrow_array;
    $sthse->finish;
    return ($sname eq "") ? $ipse : $sname;
}

sub get_sensor_ip_by_id {
	my $id     = shift;
    my $result = "";
    
    $result = `/usr/bin/php /usr/share/ossim/scripts/vulnmeter/util.php get_sensor_ip $id`; chomp($result);
  
    return $result;
}

sub get_varhex_from_ip {
	my $ip     = shift;
    my $result = "";
    
    $result = `/usr/bin/php /usr/share/ossim/scripts/vulnmeter/util.php get_varhex $ip`; chomp($result);
  
    return $result;
}

sub scan_discover {
    my $targets = shift;
    my $sensor = shift;
    my @test_hosts = split /\n/, $targets;
    my $result = "";
    my @hosts_alive=();
    $hosts_alive[0] = "";
    my $hn = "";
    
    $hn = join (" ",@test_hosts);
    #my $cmd = qq{$CONFIG{'NMAPPATH'} -sP -n $hn};
    #if ($sensor =~ /\d+\.\d+\.\d+\.\d+/) {
    	# need name if ip format found
    #	$sensor = get_sensor_name($sensor);
    #}
    my $cmd = qq{/usr/bin/php /usr/share/ossim/scripts/vulnmeter/remote_nmap.php '$hn' '$sensor' 'vulnscan'};
    logwriter("Run nmap for targets:'$hn' remote:$sensor ...", 4);
    open(NMAP,"$cmd 2>&1 |") or die "failed to fork :$!\n";
    while(<NMAP>){
        chomp;
        logwriter("nmap scan line: $_", 4);
        if(/Host (\d+\.\d+\.\d+\.\d+) appears to be up/i){
            if ($hosts_alive[0] eq "") {
                undef @hosts_alive;
            }
            push @hosts_alive,$1;
        }
    }
    close NMAP;
    $result = join("\n",@hosts_alive) if ($hosts_alive[0] ne "");
    return $result;
}

sub get_target_id {
    my $input = $_[0];
    my (%credentials) = %{$_[1]};
    
    my @sorted_hosts = ();
    my ($xml);

    @sorted_hosts = sort(split(/\n/, $input));
    
    my $ls_credentials = "";
    
    if(noEmpty($credentials{'smb_credential'}) && $credentials{'smb_credential'} =~ /^\w+\-\w+\-\w+\-\w+\-\w+$/) {
        $ls_credentials .= "<smb_lsc_credential id='".$credentials{'smb_credential'}."'/>";
        logwriter("smb_lsc_credential: <smb_lsc_credential id='".$credentials{'smb_credential'}."'/>", 4);
    }
    
    if(noEmpty($credentials{'ssh_credential'}) && $credentials{'ssh_credential'} =~ /^\w+\-\w+\-\w+\-\w+\-\w+$/) {
        $ls_credentials .= "<ssh_lsc_credential id='".$credentials{'ssh_credential'}."'/>";
        logwriter("<ssh_lsc_credential id='".$credentials{'ssh_credential'}."'/>", 4);
    }
    
    $xml = execute_omp_command("<create_target><name>target$$</name><hosts>".join(",", @sorted_hosts)."</hosts>".$ls_credentials."</create_target>");

    return $xml->{'id'};
}

sub get_config_id {
    my $sid = shift;
    
    my $sql = qq{ SELECT name, owner FROM vuln_nessus_settings WHERE id=$sid };
    my $sthse=$dbh->prepare( $sql );
    $sthse->execute;
    my ($name, $user) = $sthse->fetchrow_array;
    $sthse->finish;
    
    my $result = "";
    my @items=();
    
    my $xml = execute_omp_command("<get_configs />");
    
    if (ref($xml->{'config'}) eq 'ARRAY') {
        @items = @{$xml->{'config'}};
    } else {
        push(@items,$xml->{'config'});
    }
    
    foreach my $profile (@items) {
        if ($profile->{'name'} eq $name && $profile->{'comment'} eq $user) {
            $result = $profile->{'id'};
            logwriter("Profile ".$name." selected", 4);
        }
    }
    if ($result eq "") {
        $name = "Full and fast";
        foreach my $profile (@items) {
           if ($profile->{'name'} eq $name) {
               $result = $profile->{'id'};
               logwriter("Profile Full and Fast selected", 4);
           }
        }
    }
    
    return $result;
}

sub create_task {
    my $jobname = shift;
    my $config_id = shift;
    my $target_id = shift;
    
    my $xml = execute_omp_command("<create_task><name>$jobname</name><config id='$config_id'></config><target id='$target_id'></target></create_task>");

    return $xml->{'id'};
}
sub play_task {
    my $task_id = shift;

    my $xml = execute_omp_command("<start_task task_id='$task_id' />");
}

sub stop_task {
    my $task_id = shift;

    my $xml = execute_omp_command("<stop_task task_id='$task_id' />");
}
sub get_task_status {
    
    my ($task_id, $sensor_id )  = @_;
    
    my($xml, @items, $task, $status);
    
    $sensor_id = (noEmpty( $sensor_id ) ) ? $sensor_id : '';
    
    $xml = execute_omp_command("<get_tasks task_id='$task_id'/>", $sensor_id);

    if ( $xml->{'status_text'} =~ /Failed to find task/ ) {
        return "NOT_FOUND|NOT_FOUND";
    }
    
    if (ref($xml->{'task'}) eq 'ARRAY') {
        @items = @{$xml->{'task'}};
    } else {
        push(@items,$xml->{'task'});
    }
    
    foreach my $task (@items) {
        if (ref($task->{"progress"}) eq 'HASH') {
            return $task->{"status"}."|".$task->{"progress"}->{'content'}; 
        }
        else {
            return $task->{"status"}."|".$task->{"progress"};
        }
    }
}

sub get_running_scans {
    my $sensor_id = shift;
    
    my($xml, @items, $task, $status, $total, $ts, @server_data, $onessusport, $onessususer, $onessuspassword, $onessushost);
    
    $total           = 0;
    
    
    # save data
    
    $onessusport     = $nessusport;
    $onessususer     = $nessususer;
    $onessuspassword = $nessuspassword;
    $onessushost     = $nessushost;
    
    # get sensor data
    
    @server_data = get_server_data($sensor_id);
    $nessusport      = $server_data[0] if (noEmpty($server_data[0]));
    $nessususer      = $server_data[1] if (noEmpty($server_data[1]));
    $nessuspassword  = $server_data[2] if (noEmpty($server_data[2]));
    $nessuspassword  = $server_data[3] if (noEmpty($server_data[3])); # decrypted value has preference
    $nessushost      = $server_data[4] if (noEmpty($server_data[4]));

    if( $nessusport eq "" || $nessususer eq "" || $nessuspassword eq "" || $nessushost eq "" ) {

        $nessusport      = $onessusport;
        $nessususer      = $onessususer;
        $nessuspassword  = $onessuspassword;
        $nessushost      = $onessushost;

        return -1;
    }
    
    $xml             = execute_omp_command("<get_tasks />");
    
    if (ref($xml->{'task'}) eq 'ARRAY') {
        @items = @{$xml->{'task'}};
    } else {
        push(@items,$xml->{'task'});
    }
    
    foreach my $task (@items) {

    	$ts = ( noEmpty($task->{"status"}) ) ? $task->{"status"} : "";
        	
        $total++ if($ts eq "Running" || $ts eq "Requested" || $ts eq "Pause Requested" || $ts eq "Paused");
    }

    # restore data
    
    $nessusport      = $onessusport;
    $nessususer      = $onessususer;
    $nessuspassword  = $onessuspassword;
    $nessushost      = $onessushost;
    
    return $total;
}

sub execute_omp_command {

    my ($cmd, $sensor_id)     = @_;

    $sensor_id = (noEmpty( $sensor_id ) ) ? $sensor_id : '';

    logwriter("Command: ".$cmd,5);
    logwriter("Sensor id: ".$sensor_id,5);

    my $xml;
    my $retry = 0;
    my $openvas_manager_common = "";

    my(@server_data, $onessusport, $onessususer, $onessuspassword, $onessushost);

    if ( $sensor_id ne "" ) {
        $onessusport     = $nessusport;
        $onessususer     = $nessususer;
        $onessuspassword = $nessuspassword;
        $onessushost     = $nessushost;
    
        # get sensor data

        @server_data = get_server_data($sensor_id);
        $nessusport      = $server_data[0] if (noEmpty($server_data[0]));
        $nessususer      = $server_data[1] if (noEmpty($server_data[1]));
        $nessuspassword  = $server_data[2] if (noEmpty($server_data[2]));
        $nessuspassword  = $server_data[3] if (noEmpty($server_data[3])); # decrypted value has preference
        $nessushost      = $server_data[4] if (noEmpty($server_data[4]));

    }
    
    do {
        $openvas_manager_common = "$CONFIG{'NESSUSPATH'} -h $nessushost -p $nessusport -u '$nessususer' -w '$nessuspassword' -iX";
        
        my $file_omp_command    = "/usr/share/ossim/www/tmp/omp_command_$$".int(rand(1000000)).".xml";
        
        open(OMP_COMMAND, ">$file_omp_command");
        
        binmode(OMP_COMMAND, ":utf8");
        
        print OMP_COMMAND $cmd;
        
        close OMP_COMMAND;
        
        my $imp = system ("$openvas_manager_common - < ".$file_omp_command." > $xml_output 2>&1");
        
        if ($debug_file ne "") {
            system ("echo '$openvas_manager_common - < \"$file_omp_command\"' >> '$debug_file'");
            system ("cat $xml_output >> '$debug_file'");
            system ("echo '' >> '$debug_file'");
        }
        
        unlink $file_omp_command if -e $file_omp_command;
        
        logwriter("$openvas_manager_common - < ".$file_omp_command." > $xml_output 2>&1", 4);

        $xml = eval {XMLin($xml_output, keyattr => [])};
        
        if ($@ ne "") { 
            $retry += 1;
            logwriter( "Failure on OMP request: Sleeping 30 seconds before retrying...", 4 );
            sleep(30);
        }
    
    } while ( $@ ne "" && $retry < 50 );

    if ( $sensor_id ne "" ) {
        # restore data
        
        $nessusport      = $onessusport;
        $nessususer      = $onessususer;
        $nessuspassword  = $onessuspassword;
        $nessushost      = $onessushost;
    }
    

    if ($@ ne "") {
    
        open(INFO, $xml_output);         # Open the file
        my @log_lines = <INFO>;          # Read it into an array
        close(INFO);                     # Close the file
    
        my $error = join(" ", @log_lines);
        if($job_id_to_log ne "") {
            if($semail eq "1") {
                send_error_notifications_by_email($job_id_to_log, "OMP: $error");
            }
            $sql = qq{ UPDATE vuln_jobs SET status='F', meth_Wcheck=CONCAT(meth_Wcheck, '$error<br />') , scan_END=now(), scan_NEXT=NULL WHERE id='$job_id_to_log' }; #MARK FAILED
            safe_db_write ( $sql, 1 );
        }

        unlink $xml_output if -e $xml_output;
        die "Can't read XML $xml_output: $error";
    }
    
    if ($xml->{'status'} !~ /20\d/) {
        my $status = $xml->{'status'};
        my $status_text = $xml->{'status_text'};
        
        if($job_id_to_log ne "") {
            if($semail eq "1") {
                send_error_notifications_by_email($job_id_to_log, "OMP: $status_text");
            }
            $sql = qq{ UPDATE vuln_jobs SET status='F', meth_Wcheck=CONCAT(meth_Wcheck, '$status_text<br />'), scan_END=now(), scan_NEXT=NULL WHERE id='$job_id_to_log' }; #MARK FAILED
            safe_db_write ( $sql, 1 );

            unlink $xml_output if -e $xml_output;
            die "Error: status = $status, status_text = '$status_text'";
        }
    }
    
    unlink $xml_output if -e $xml_output;
    
    return $xml; 
}

sub get_results_from_xml {
    my $task_id     = $_[0];
    my $target_id   = $_[1];
    my %credentials = %{$_[2]};
    my $sensor_id   = $_[3];
    
    my $total_records = 0;
    my (@items, @nvt_data, $host, $service, $scan_id, $description, $app, $proto, $port, $risk_factor, @issues, %hostHash, %resultHash, $report_id, $xml);
    
    %hostHash = ();
    %resultHash = ();
    
    # Search IPs in task details
    
    # my @tmp_hosts = split(/\n/, $targets);
    
    # foreach my $ihost(@tmp_hosts) { 
        # $hostHash{$ihost}++;
    # }
    
    $xml = execute_omp_command("<get_tasks task_id='$task_id' details='1'/>", $sensor_id);
    
    if (ref($xml->{'task'}{'reports'}{'report'}) eq 'ARRAY') {
        @items = @{$xml->{'task'}{'reports'}{'report'}}; 
    } else {
        push(@items,$xml->{'task'}{'reports'}{'report'});
    }
    
    # get latest report id
    foreach my $report (@items) {
        $report_id = $report->{'id'};
    }
    
    logwriter("Get reports for report_id: $report_id",4);
    $xml = execute_omp_command("<get_reports report_id='$report_id'/>", $sensor_id);
    
    # reports format depends on OpenVAS version 
    @items = ();
    
    if(defined($xml->{'report'}{'report'}{'results'}->{'result'})) { # OpenVAS 4
        if (ref($xml->{'report'}{'report'}{'results'}->{'result'}) eq 'ARRAY') {
            @items = @{$xml->{'report'}{'report'}{'results'}->{'result'}}; 
        } elsif(defined($xml->{'report'}{'report'}{'results'}->{'result'})) {
            push(@items,$xml->{'report'}{'report'}{'results'}->{'result'});
        }
    }
    else { # OpenVAS 3
        if (ref($xml->{'report'}{'results'}->{'result'}) eq 'ARRAY') {
            @items = @{$xml->{'report'}{'results'}->{'result'}}; 
        } elsif(defined($xml->{'report'}{'results'}->{'result'})) {
            push(@items,$xml->{'report'}{'results'}->{'result'});
        }
    }

    foreach my $result (@items) {
        #if( defined($hostHash{$result->{"host"}}) ) {
            $host = $result->{"host"};
            logwriter("Save results for $host", 4);
            $service = $result->{"port"};
            $scan_id = $result->{"nvt"}->{"oid"};

            #print Dumper($result->{"nvt"});
            
            #if ($result->{"threat"} ne "") { $risk_factor = $result->{"threat"}; }
            #elsif ($result->{"nvt"}->{"risk_factor"} ne "") { $risk_factor = $result->{"nvt"}->{"risk_factor"}; }
            #else { $risk_factor = "Info"; }
            
            
            #if($risk_factor eq "Log")       { $risk_factor = "Info"; }
            #if($risk_factor eq "None")      { $risk_factor = "Info"; }
            #if($risk_factor eq "Passed")    { $risk_factor = "Info"; }
            #if($risk_factor eq "Unknown")   { $risk_factor = "Medium"; }
            #if($risk_factor eq "Failed")    { $risk_factor = "High"; }
            
            if ($result->{"nvt"}->{"cvss_base"} eq "" || ref($result->{"nvt"}->{"cvss_base"}) eq 'HASH') {
                $risk_factor = "Info";
            }
            elsif (int($result->{"nvt"}->{"cvss_base"}) >= 8 ) {
                $risk_factor = "Serious";
            }
            elsif( int($result->{"nvt"}->{"cvss_base"}) >= 5 && int($result->{"nvt"}->{"cvss_base"}) < 8 ) {
                $risk_factor = "High";
            }
            elsif( int($result->{"nvt"}->{"cvss_base"}) >= 2 && int($result->{"nvt"}->{"cvss_base"}) < 5 ) {
                $risk_factor = "Medium";
            }
            elsif( int($result->{"nvt"}->{"cvss_base"}) >= 0 && int($result->{"nvt"}->{"cvss_base"}) < 2 ) {
                $risk_factor = "Low";
            }

            
            #logwriter("Set risk: ".$result->{"nvt"}->{"cvss_base"}." -> ".$risk_factor, 4);
            
            $description = $result->{"description"};
            
            if ( $service =~ /general/ ) {
                my @temp = split /\//, $service;
                $app = "general";
                $proto = $temp[1];
                $port = "0";
            } else {
                my @temp = split /\s/, $service;
                $app = $temp[0];
                $temp[1] =~ s/\(//;
                $temp[1] =~ s/\)//;
                my @temp2 = split /\//, $temp[1];
                $port = $temp2[0];
                $proto = $temp2[1];
            }
            if (defined($scan_id)){
                logwriter("get_results_from_file:scan_id:$scan_id", 4);
            }
            if (defined($compliance_plugins)){
                logwriter("get_results_from_file:compliance_plugins:$compliance_plugins", 4);
            }
            
            my @cplugins = split /\s/, $compliance_plugins;
            
            if ( defined($scan_id) && in_array(\@cplugins,$scan_id) ) {
                #UPDATE SCANID FOR WIN CHECKS #21156
                if ( $scan_id =~ /21156/ ) {
                    my ( $test_name, $test_policy ) = "";
                    my @temp = split(/\\n/, $description);
                    foreach my $line (@temp) {
                        $line =~ s/\#.*$//;
                        chomp($line);
                        $line =~ s/\s+$//;
                        $line =~ s/^\s+//;
                        if ($line eq "") { next; }
                        $line =~ s/"//g;
                        if ( $line =~ /\[[EFP][AR][IRS][OLS][ER]D*\]/ ) {
                            $test_name = $line;
                            $test_name =~ s/\[[EFP][AR][IRS][OLS][ER]D*\]//;
                            $test_name =~ s/\s+$//;
                            $test_name =~ s/^\s+//;
                            $test_name =~ s/:$//;
                        }
                    }
                    if ( defined($test_name) && $test_name ne ""  ) {
                        #my $sql = qq{ SELECT t1.id FROM vuln_nessus_checks t1
                        #    LEFT JOIN vuln_nessus_checks_audits t2 on t1.id=t2.cid
                        #    WHERE t2.auditfile ='$primaryAuditcheck' AND
                        #    t1.name='$test_name' LIMIT 1 };
                        #logwriter( $sql, 5 );
                        #my $sth_sel = $dbh->prepare( $sql );
                        #$sth_sel->execute(  );
                        #my ( $tmp_scan_id ) = $sth_sel->fetchrow_array(  );
                        #if ( defined( $tmp_scan_id) && $tmp_scan_id >= 60000 ) { $scan_id = $tmp_scan_id; }
                    }
                }
                
                my $risk_value = "";
                if ( $description =~ m/\[PASSED\]/ ) {
                    $risk_value = "Risk factor : \n\nPassed\n";
                } elsif ( $description =~ m/\[FAILED\]/ ) {
                    $risk_value = "Risk factor : \n\nFailed\n";
                } else {
                    $risk_value = "Risk factor : \n\nUnknown\n";
                }
                $description .= "$risk_value";
                logwriter("set compliance description: $risk_value",5);
            }

            logwriter("get_results_from_xml 2 - $host $scan_id $description", 5);
            
            if ( $description ) {   #ENSURE WE HAVE SOME DATA
                $description =~ s/\\/\\\\/g;	#FIX TO BACKSLASHES
                $description =~ s/\\\\n/\\n/g;	#FIX TO NEWLINE
                
                
                
                if( $description !~  m/cvss base score/i ) {
                    if($result->{"nvt"}->{"cvss_base"} ne "" && ref($result->{"nvt"}->{"cvss_base"}) ne 'HASH') {
                        $description .= "\nCVSS Base Score     : ".$result->{"nvt"}->{"cvss_base"};
                    }
                    else {
                        $description .= "\nCVSS Base Score     : -\n";
                    }
                }

                my $temp = {
                    Port            => $port,
                    Host            => $host,
                    Description     => $description,
                    Service         => $app,
                    Proto           => $proto,
                    ScanID          => $scan_id,
                    RiskFactor      => $risk_factor
                };
                logwriter ( "my temp = { Port=>$port, Host=>$host, Description=>$description, Service=>$app, Proto=>$proto, ScanID=>$scan_id, RiskFactor=>$risk_factor };\n", 5);
                
                if ( !exists($resultHash{$port}{$host}{lc $description}{lc $app}{lc $proto}{$scan_id}{lc $risk_factor}) )
                {
                    push (@issues, $temp);
                    $resultHash{$port}{$host}{lc $description}{lc $app}{lc $proto}{$scan_id}{lc $risk_factor}++;
                    $total_records += 1;
                }
            }
       # }
    }

    if ($total_records eq 0 ) { $no_results = TRUE; }
    
    # Deletion order is very important
    
    if ($delete_task == TRUE)        {
    	execute_omp_command("<delete_task task_id='$task_id' />", $sensor_id);
    	logwriter("Deleting task $task_id from $sensor_id", 5);
    }
    if ($delete_target == TRUE)      {
    	execute_omp_command("<delete_target target_id='$target_id' />", $sensor_id);
    	logwriter("Deleting target $target_id from $sensor_id", 5);
    }

    if ($delete_credentials == TRUE) {
        foreach my $tcred ( keys %credentials ) {
            execute_omp_command("<delete_lsc_credential lsc_credential_id='".$credentials{$tcred}."'/>", $sensor_id);
            logwriter("Deleting credential ".$credentials{$tcred}." from ".$sensor_id, 5);
        }
    }

    return @issues;
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

sub getassetbyname {
    my ( $hostname ) = @_;
    my ( $sql, $sth_sel, $cmd );
    my ($ip) = "";
    
    my $resolv = TRUE;
        
    $sql = qq{ SELECT inet6_ntop(hip.ip) AS ip 
                        FROM host h, host_ip hip, vuln_nessus_latest_reports vnlr
                        WHERE h.id = hip.host_id 
                        AND h.hostname = '$hostname'
                        AND vnlr.hostIP = inet6_ntop(hip.ip) };

    $sth_sel = $dbh->prepare( $sql );
    $sth_sel->execute;
    $ip = $sth_sel->fetchrow_array;
    
    if(!defined($ip)) {
        $sql = qq{ SELECT inet6_ntop(hip.ip) as ip 
                            FROM host h, host_ip hip
                            WHERE h.id=hip.host_id 
                            and h.hostname = '$hostname' };

        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
        $ip = $sth_sel->fetchrow_array;
    }
    
    if(!defined($ip)) { $ip = ""; }
    
    if ($ip ne "") { return $ip; }
    elsif ($resolv == TRUE) {
        $cmd = qq{/usr/bin/dig '$hostname' A +short | /usr/bin/tail -1};
        open(RESOLV,"$cmd 2>&1 |") or die "failed to fork :$!\n";
        while(<RESOLV>){
            chomp;
            $ip = $_;
        }
        close RESOLV;
        return $ip;
    }
    else {
        return "";
    }

}

# to set failed the hung jobs
sub check_running_scans {
    my ($sql, $sthse, $running, $completed, $status, $now, $info_status, @arr_status, $scantime, $cpid, $wait);

    $now = strftime "%Y-%m-%d %H:%M:%S", gmtime;

    $sql = qq{ SELECT meth_TARGET, id, scan_PID, meth_Wfile, meth_CPLUGINS, notify, name, job_TYPE, username, meth_VSET FROM vuln_jobs WHERE status='R' AND UNIX_TIMESTAMP('$now') - UNIX_TIMESTAMP(scan_START) > 3600 };
                        
    $sthse=$dbh->prepare( $sql );
    $sthse->execute;
    while ( my($targets, $job_id, $scan_pid, $semail, $task_id, $sensor_id, $job_title, $Jtype, $juser, $Jvset ) = $sthse->fetchrow_array() ) {
    
    	$scantime = getCurrentDateTime();
    
        $running   = 0;
        $completed = 0;
        $wait      = 0;
        
        if ($CONFIG{'NESSUSPATH'} !~ /omp\s*$/) {
            $running =`ps ax | grep $scan_pid | grep nessus_jobs | grep -v "ps ax" | wc -l`;
        }
        else {
            $info_status = get_task_status($task_id, $sensor_id); 
            @arr_status = split /\|/, $info_status;
            $status = shift(@arr_status);
            
            $cpid = `ps -eo pid,cmd | grep nessus_job | grep -v grep | grep $scan_pid`;
            
            chomp($cpid); # search job pid
                
            if ( ($status eq "Running" || $status eq "Requested" || $status eq "Pause Requested" || $status eq "Paused") ) {
                $running   = 1;
            }
            elsif ( ($status eq "Done" || $status eq "Stopped") ) {
            	if( $cpid eq "" ) {
	            	$completed = 1;
	            }
	            else {
	            	$wait      = 1;
	            }
            }

        }
        #print Dumper($completed);
        #print Dumper($cpid);
        #print Dumper($running);
        #print Dumper($completed);
        
        if ($wait==0) {
	        if($completed==1) {
		        logwriter( "nessus_jobs.pl was ended incorrectly", 5 );
		        
		        my (%task_data, @issues, %hostHash);
		        
		        # load ctx info
		        
		        my @aux         = split /\n/, $targets;
			    my $default_ctx = get_default_ctx();
			    my %ctxs        = get_ctxs_by_ip($job_id);
			    my $host_ctx    = "";
			    my $host_ip     = "";
			    
			    # load ctx in vuln_jobs table
			    foreach my $ip_in_db (keys %ctxs) {
			        $asset_data{$ip_in_db}{'ctx'} = $ctxs{$ip_in_db};
			    }
			
			    foreach my $idip (@aux) {
			        if ( $idip =~ m/^([a-f\d]{32})#(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}(\/\d{1,2})?)$/i ) { #     host_id#Ip or net_id#CIDR
			            $asset_data{$2}{'ctx'} = get_asset_ctx($idip);
			            $asset_data{$2}{'id'}  = $1;
			            logwriter("Search ctx by ID ".$idip." -> ".get_asset_ctx($idip), 4);
			        }
			        elsif( $idip =~ m/^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}(\/\d{1,2})?)$/i ) { # set the default ctx
			            $asset_data{$1}{'ctx'} = $default_ctx;
			            logwriter("Search default ctx ".$idip." -> ".$default_ctx, 4);
			        }
			        else { # host name
			            $idip     =~ s/[|;"']//g;
			            $host_ctx = get_asset_ctx($idip);
			            $host_ip  = `/usr/bin/dig '$idip' A +short | /usr/bin/tail -1`; chomp($host_ip);
			            if( $host_ctx =~ m/^[a-f\d]{32}$/i && $host_ip =~ m/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/) {
			                logwriter("Search ctx by name ".$idip." -> ".$host_ctx, 4);
			                $asset_data{$host_ip}{'ctx'} = get_asset_ctx($idip);
			            }
			        }
			    }
		        
		        %task_data = get_task_data($task_id, $sensor_id);
		        
		        @issues = get_results_from_xml( $task_id, $task_data{'target_id'}, $task_data{'credentials'}, $sensor_id);
		        
		        %hostHash = pop_hosthash( \@issues );       #PROCESS RESULTS INTO HOSTHAS ARRAY FOR IMPORT
	        
		        undef (@issues);                            #FREE RESOURCES FROM ORIGINAL RESULTS ARRAY
	       
		        if( process_results( \%hostHash, $job_id, $job_title, $Jtype, $juser, $Jvset, $scantime, $sensor_id ) ) {
	            	logwriter( "[$job_title] [ $job_id ] Completed SQL Import, scan_PID=$$", 4 );
	                $sql = qq{ UPDATE vuln_jobs SET status='C', scan_PID=$$, scan_END=now(), scan_NEXT=NULL WHERE id='$job_id' };
	            }
	            else {
		             logwriter( "Error when importing orphan job $task_id for server $sensor_id", 5 );
		             $sql = qq{ UPDATE vuln_jobs SET status='F', scan_END ='$now', meth_Wcheck=CONCAT(meth_Wcheck, 'Error when importing orphan job<br />') WHERE id='$job_id' };
	            }
		        
	        }
	        elsif($running==0) { # the job is not running in the sersor
	            logwriter( "Job task $task_id was ended incorrectly for server $sensor_id", 5 );
	            $sql = qq{ UPDATE vuln_jobs SET status='F', scan_END ='$now', meth_Wcheck=CONCAT(meth_Wcheck, 'Job task was ended incorrectly<br />') WHERE id='$job_id' };
	        }
        
	        safe_db_write ( $sql, 4 );  #use insert/update routine
        }
    }
    $sthse->finish;
}
sub get_task_data {
	my $task_id    = shift;
	my $sensor_id = shift;
	
	my($xml, %result);
    
    $sensor_id = (noEmpty( $sensor_id ) ) ? $sensor_id : '';
    
    $result{'target_id'}   = 'NOT_FOUND';
    %{$result{'credentials'}} = ("smb_credential", "", "ssh_credential", "");
        
    $xml = execute_omp_command("<get_tasks task_id='$task_id'/>", $sensor_id);
 
    if ( $xml->{'status_text'} =~ /Failed to find task/ ) {        
        return %result; # task not found
    }
    
    # target id
    
    $result{'target_id'} = $xml->{'task'}->{"target"}->{'id'};
    
    # get smb and ssh credentials
    
    $xml = execute_omp_command("<get_targets target_id='".$result{'target_id'}."'/>", $sensor_id);
    
    if ( noEmpty( $xml->{"target"}->{'ssh_lsc_credential'}->{'id'}) ) {
	    $result{'credentials'}{'ssh_credential'} = $xml->{"target"}->{'ssh_lsc_credential'}->{'id'};
    }
    
    # get smb credential
    
    if ( noEmpty( $xml->{"target"}->{'smb_lsc_credential'}->{'id'} ) ) {
	    $result{'credentials'}->{'smb_credential'} = $xml->{"target"}->{'smb_lsc_credential'}->{'id'};
    }

    return %result;
}
sub get_default_ctx { 
    my ($sql, $sthse, $dctx);
    
    $sql   = qq{ SELECT value FROM config WHERE conf='default_context_id' };
    $sthse = $dbh->prepare( $sql );
    $sthse->execute;
    
    $dctx  = $sthse->fetchrow_array;
    $sthse->finish;
    
    $dctx =~ s/\-//g;
    
    return $dctx;
}

sub get_ctxs_by_ip {
    my $job_id  = shift;
    my ($sql, $sthse);
    
    my %ctxs_by_ip;

    $sql   = qq{ SELECT author_uname FROM vuln_jobs WHERE id='$job_id' };
    $sthse = $dbh->prepare( $sql );
    $sthse->execute;
    
    my $ctx_ip  = $sthse->fetchrow_array;
    
    my @lines = split(/\n/, $ctx_ip);
            
    foreach my $line (@lines ) {
        my @data = split(/#/, $line);
        $ctxs_by_ip{$data[1]} = $data[0];
    }
    
    $sthse->finish;
    return (%ctxs_by_ip);
}

sub generate_credentials {
    my $job_id  = shift;
    
    my ($ssh_credential_id, $smb_credential_id, $sql, $sth_sel, $xml, $ssh_credential, $smb_credential , %credentials, $pid);
    
    $pid = $$;
    
    $sql = qq{ SELECT credentials FROM vuln_jobs where id='$job_id' };
    $sth_sel=$dbh->prepare( $sql );
    $sth_sel->execute;
    
    my $credentials  = $sth_sel->fetchrow_array;
    
    $credentials =~ m/((.*)#(.*))?\|((.*)#(.*))?/;
    
    
    # ssh credentials ( $2 = name and $3 = login)
    if( noEmpty($2) && noEmpty($3) ) {
        $sql = qq{ SELECT AES_DECRYPT(UNHEX(value),'$uuid') AS dvalue FROM user_config WHERE category='credentials' AND name='$2' AND login='$3' };
        #logwriter("SELECT AES_DECRYPT(UNHEX(value),'$uuid') AS dvalue FROM user_config WHERE category='credentials' AND name='$2' AND login='$3'", 4);
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
    
        $ssh_credential  = $sth_sel->fetchrow_array;
        
        if( noEmpty($ssh_credential) ) { # Create ssh credentials 
            $ssh_credential =~ s/\<name\>/\<name\>$pid/;
            $xml = execute_omp_command($ssh_credential);
            #logwriter("ssh_credential: $ssh_credential", 4);
            
            if( noEmpty($xml->{'id'}) ) {
                $ssh_credential_id = $xml->{'id'};
            }
            #logwriter("ssh_credential_id $ssh_credential_id", 4);
        }
    }

    # smb credentials ( $5 = name and $6 = login)
    
    if( noEmpty($5) && noEmpty($6) ) {
        $sql = qq{ SELECT AES_DECRYPT(UNHEX(value),'$uuid') AS dvalue FROM user_config WHERE category='credentials' AND name='$5' AND login='$6' };
        $sth_sel = $dbh->prepare( $sql );
        $sth_sel->execute;
    
        $smb_credential  = $sth_sel->fetchrow_array;
        
        if( noEmpty($smb_credential) ) { # Create ssh credentials
            $smb_credential =~ s/\<name\>/\<name\>$pid/;
            $xml = execute_omp_command($smb_credential);
            if( noEmpty($xml->{'id'}) ) {
                $smb_credential_id = $xml->{'id'};
            }
        }
    }
    
    # Return credentials
    if( noEmpty($ssh_credential_id) ) {
        $credentials{'ssh_credential'} = $ssh_credential_id;
    }
    if( noEmpty($smb_credential_id) ) {
        $credentials{'smb_credential'} = $smb_credential_id;
    }
    
    return (%credentials);
}

sub send_error_notifications_by_email {
    my $job_id  = $_[0];
    my $message = $_[1];
    
    logwriter("Send email for job_id: $job_id ...", 5);
            
    my $cmde = qq{ /usr/bin/php /usr/share/ossim/scripts/vulnmeter/send_notification.php '$job_id' '$message' };
    
    open(EMAIL,"$cmde 2>&1 |") or die "failed to fork :$!\n";
    while(<EMAIL>){
        chomp;
        logwriter("send_error_notifications output: $_", 5);
    }
    close EMAIL;
    
}

sub get_asset_ctx {
    my $id_ip  = shift; # asset_id#ip or ip
    my $result = "";
    
    $result = `/usr/bin/php /usr/share/ossim/scripts/vulnmeter/util.php get_ctx $id_ip`; chomp($result);
    
    return $result;
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

sub Empty {
    my $var  = shift;
    if (defined($var) && $var ne "") {
        return 0;
    }
    else {
        return 1;
    }
}

sub clean_old_omp_files {
    my $command_output = `find /usr/share/ossim/www/tmp  -mtime +2 -print | grep -P 'omp_command.{15}\.xml'`;

    my @output_lines = split(/\n/, $command_output);

    foreach my $line (@output_lines ) {
      if (-e $line) {
        unlink($line);
      }
    }

    $command_output = `find /usr/share/ossim/www/tmp  -mtime +2 -print | grep -P 'omp.{15}\.xml'`;

    @output_lines = split(/\n/, $command_output);

    foreach my $line (@output_lines ) {
      if (-e $line) {
        unlink($line);
      }
    }
}

sub fix_vulns_tables {

    my $sql;

    $sql        = qq{ UPDATE vuln_job_schedule v,sensor s SET v.email=hex(s.id) WHERE v.email=s.name };
    safe_db_write($sql, 5);

    $sql        = qq{ UPDATE vuln_jobs v,sensor s SET v.notify=hex(s.id) WHERE v.notify=s.name };
    safe_db_write($sql, 5);

}
