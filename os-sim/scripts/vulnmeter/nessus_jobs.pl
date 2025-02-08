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


my $version = "11.0";
use 5.010;
use strict;

use DBI;
use Crypt::CBC; # apt-get install libcrypt-blowfish-perl
use MIME::Base64;
use Digest::MD5 qw(md5 md5_hex md5_base64);
use Net::IP;
use Net::Netmask;
use Date::Manip;
use MIME::Lite;
use Date::Calc qw(Delta_DHMS Add_Delta_YMD Days_in_Month);
use Getopt::Std;
use feature "switch";
use IO::Socket;
use Data::Dumper;
use POSIX qw(strftime);
use Try::Tiny;

local $ENV{XML_SIMPLE_PREFERRED_PARSER} = "XML::Parser";
use XML::Simple;

#Declare constants
use constant TRUE => 1;
use constant FALSE => 0;
no if $] >= 5.018, warnings => 'experimental::smartmatch';

$|=1;

#Logwriter Risk Values
my %loginfo;
$loginfo{'1'} = "FATAL";
$loginfo{'2'} = "ERROR";
$loginfo{'3'} = "WARN";
$loginfo{'4'} = "INFO";
$loginfo{'5'} = "DEBUG";

# Sanity check
my $log_level = 4;

my $dbhost = `grep ^db_ip= /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`;
chomp($dbhost);
$dbhost = "localhost" if ($dbhost eq "");
my $dbuser = `grep ^user= /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`;
chomp($dbuser);
my $dbpass = `grep ^pass= /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`;
chomp($dbpass);

my $uuid = `/usr/bin/php /usr/share/ossim/scripts/vulnmeter/util.php get_system_uuid`;
chomp($uuid);

#Certificate to connect to a remote sensor
my $cert_file='/var/ossim/ssl/local/private/cakey_avapi.pem';

my %CONFIG = ();
$CONFIG{'DATABASENAME'} = "alienvault";
$CONFIG{'DATABASEHOST'} = $dbhost;
$CONFIG{'UPDATEPLUGINS'} = 0;
$CONFIG{'DATABASEDSN'} = "DBI:mysql";
$CONFIG{'DATABASEUSER'} = $dbuser;
$CONFIG{'DATABASEPASSWORD'} = $dbpass;

#Database handle to be used throughout the program
my $dbh = conn_db();
my %gvm_vars = get_gvm_vars();

$CONFIG{'GVM_PATH'}  = (defined($gvm_vars{'gvm_path'})) ? $gvm_vars{'gvm_path'} : "/usr/bin/gvm-cli";
$CONFIG{'GVM_HOST'}  = (defined($gvm_vars{'gvm_host'})) ? $gvm_vars{'gvm_host'} : "";
$CONFIG{'ROOT_DIR'}  = $gvm_vars{'gvm_rpt_path'};
$CONFIG{'MAX_HOSTS'} = 1000;

#GLOBAL VARIABLES
my $debug                    = 0;
my $debug_gvm_commands       = ""; # Change to a name to log all xml responses
my $semail                   = "";
my $job_id_to_log            = "";
my $server_slot              = 1;

my $outdir = $CONFIG{'ROOT_DIR'}."tmp";
my $gvm_log_file = "/var/log/ossim/nessus_cron.log"; #Redirect output to the log file

my $no_results = FALSE;
my $max_targets = 0;
my $gvm_scan_timeout = FALSE;

my $delete_task = TRUE; # Delete task after scan to check configs in use
my $delete_target = TRUE;
my $delete_credentials = TRUE;

my @vuln_plugins_settings;
my $gvm_host = "";

my ($outfile, $targetfile);
my $txt_meth_wcheck = "";
my $txt_unresolved_names = "";

$outfile = "${outdir}/nessus_s$$.out";
$targetfile = "${outdir}/target_s$$";
my $nbe_path = "/usr/share/ossim/uploads/nbe/";

my %asset_data=();
my @asset_to_scan=();

# Read arguments from command-line
my %options=();
getopts("cdh?",\%options);

# Clean old omp files with xml results
clean_old_gvm_files();
main();
exit;

sub main {
    #ENABLE DEBUGGING
    if($options{d}) {
        use warnings;
        print "Debugging mode\n";
        $debug = 1;
        $log_level = 5;
    }

    #CHECK / RUN A QUEUED SCAN
    if($options{c}) {
        print "Client mode\n";

        open(LOG,">>$gvm_log_file") or die "Failed to create $gvm_log_file: $!\n";
        *STDERR=*LOG;
        *STDOUT=*LOG;

        #Genereate Random Seed
        srand(time() ^($$ + ($$ <<15)));

        #Connect to database
        $dbh = conn_db();

        #This function is used to apply database patches
        fix_vulns_tables();

        # set failed hung jobs
        check_running_scans();

        #Check for jobs past due for next run and schedule
        check_schedule();

        #Use Front-end variables
        load_db_configs();

        #Proceed with job selection
        maintenance();
        select_job();

        disconn_db($dbh);

        #end of main
        exit;
    } else {        #DISPLAY USAGE
        print "\tUSAGE: ./nessus_jobs.pl [-cdh?]\n\n";
        print "          -d :: Enable Full Debug to Log and leave temp files in place after scan\n";
        print "          -c :: Process work in queue via GVM Client Mode\n";
        print "          -h :: Get this help\n\n";
        print "        Nessus Cron is the backend interface to managing scans. Nessus cron will provide scanner functions.\n";
        print "        By default, this should be implemented in client mode in the following format:\n";
        print "        */1 * * * * /usr/bin/perl /usr/share/ossim/scripts/vulnmeter/nessus_jobs.pl -c > /dev/null 2>&1\n\n";
        exit;
    }

    exit;        #PROGRAM NORMAL END
}

sub get_gvm_vars{
    my ($db, $sth_sel, $sql);
    my %gvm_config_vars = ();
    $db = conn_db();
    $sql = qq{select conf, value, AES_DECRYPT(value,'$uuid') as dvalue from config where conf like 'gvm%' or conf = 'vulnerability_incident_threshold' or conf = 'close_vuln_tickets_automatically'};
    $sth_sel=$db->prepare($sql);
    $sth_sel->execute;

    while (my ($conf, $value, $dvalue) = $sth_sel->fetchrow_array) {
        if(!defined($dvalue)) {
            $dvalue = "";
        }

        $gvm_config_vars{$conf} = ($dvalue ne "") ? $dvalue : $value;
    }
    $sth_sel->finish;
    disconn_db($db);

    return %gvm_config_vars;
}

sub get_delayed_scan_date {
    my ($sql, $sth_sel);

    $sql = qq{SELECT UTC_TIMESTAMP() + INTERVAL 15 Minute AS NEXT_SCAN};

    $sth_sel = $dbh->prepare($sql);
    $sth_sel->execute();
    my ($next_run) = $sth_sel->fetchrow_array();
    $sth_sel->finish;

    $next_run  =~ s/://g;
    $next_run  =~ s/-//g;
    $next_run  =~ s/\s//g;

    return $next_run;
}

#check job queue and select job based on criteria if free scan slots and sensor is ready
sub select_job {
    logwriter("Checking scan queue ...", 4);
    my ($sql, $sth_sel);
    my $now = getCurrentDateTime();

    #Get the list of all scanners
    #Select server attributes
    $sql = "SELECT id, hostname FROM vuln_nessus_servers vns, sensor_properties sp
            WHERE sp.sensor_id = UNHEX(vns.hostname) AND sp.has_vuln_scanner = 1;";

    $sth_sel = $dbh->prepare($sql);
    $sth_sel->execute();

    my $vuln_nessus_servers = $sth_sel->fetchall_hashref('hostname'); # load the sensor id
    $sth_sel->finish;

    foreach my $sensor_id (sort(keys(%{$vuln_nessus_servers}))) {

        #Setting available scan slots by sensor
        set_current_scan_lots($sensor_id);

        my @server_data = ();

        # SORTED BY PRIORITY -> JOB TYPE -> ASSIGNED
        # PRIORITY 1 FIRST
        # 1) REQUESTS 2) MANUAL 3) CRON
        # ASSIGNED JOBS OVER UNASSIGNED JOBS

        $sql = "SELECT t1.id, t1.send_email, t1.name, t1.job_TYPE, t1.meth_TARGET, t1.scan_PRIORITY, failed_attempts
                FROM vuln_jobs t1
                WHERE t1.status IN ('S', 'D')
                AND (t1.notify='$sensor_id')
                AND (t1.scan_NEXT IS NULL OR t1.scan_NEXT <= '$now')
                ORDER BY t1.scan_PRIORITY, t1.job_TYPE DESC, t1.scan_ASSIGNED DESC LIMIT 1;";

        $sth_sel = $dbh->prepare($sql);
        $sth_sel->execute();

        my ($job_id, $send_email, $job_name, $job_type, $job_targets, $job_priority, $times_failed) = $sth_sel->fetchrow_array();
        $sth_sel->finish;

        if(no_empty($job_id)) {
            my ($s_is_ready, $s_message) = is_sensor_ready($sensor_id);

            if ($s_is_ready == FALSE){
                my $next_run = get_delayed_scan_date();

                logwriter("\t$s_message - Scan delayed: $next_run", 4);

                $sql = qq{UPDATE vuln_jobs SET status="D",
                          scan_NEXT='$next_run',
                          meth_Wcheck='$s_message<br />'
                          WHERE id='$job_id'};

                safe_db_write($sql, 3);
            } else {
                # Dee the free slots
                my ($max_scans, $current_scans) = get_scan_slots($sensor_id);
                my $free_slots = $max_scans-$current_scans;

                if($free_slots < $server_slot) {
                    my $next_run = get_delayed_scan_date();

                    logwriter("\tNo available scan slots - Next scan: $next_run", 4);

                    $sql = qq{UPDATE vuln_jobs SET status="D",
                                               scan_NEXT='$next_run',
                                               meth_Wcheck=CONCAT(REPLACE(IFNULL(meth_Wcheck,''), 'Not available scan slots<br />',''), 'Not available scan slots<br />')
                                               WHERE id='$job_id'};
                    safe_db_write($sql, 3);
                }
                else {
                    $semail = ($send_email eq "1") ? "1" : "";

                    logwriter("Job $job_name ($job_id) selected ...", 4);
                    logwriter("\tJob Type: $job_type", 4);
                    logwriter("\tTargets: $job_targets", 4);
                    logwriter("\tJob Priority: $job_priority", 4);

                    #Job failed too many times
                    my $retries_allowed = 3;
                    if ($CONFIG{'failedRetries'}) {
                        $retries_allowed = $CONFIG{'failedRetries'};
                    }

                    if ($times_failed ge $retries_allowed) {
                        if($semail eq "1") {
                            send_error_notifications_by_email($job_id, "Job failed more than $retries_allowed times");
                        }

                        logwriter("Job $job_name ($job_id) failed more than $retries_allowed times ...", 4);
                        $sql = qq{UPDATE vuln_jobs SET status='F', scan_END=UTC_TIMESTAMP(), scan_NEXT=NULL WHERE id='$job_id'};
                        safe_db_write($sql, 1);
                    } else {
                        if($sensor_id ne ""){
                            # Get sensor data
                            @server_data = get_server_data($sensor_id);
                            $CONFIG{'GVM_HOST'} = $server_data[0] if (no_empty($server_data[0]));
                        }

                        #RUN ONE JOB THEN QUIT
                        run_job ($job_id);

                        system("/usr/bin/php /usr/share/ossim/scripts/vulnmeter/util.php update_vuln_jobs_assets insert 0 0");
                    }
                    return;
                }
            }
        }
    }

    logwriter("No work in scan queues to process", 4);
}

sub run_job {
    my ($job_id) = @_;

    my ($sql, $sth_sel);

    my $start_date = getCurrentDateTime();

    $sql = qq{SELECT fk_name, name, username, notify, job_TYPE, meth_TARGET,
        profile_id, meth_TIMEOUT, only_alive_hosts, authorized, resolve_names, exclude_ports
        FROM vuln_jobs WHERE id='$job_id' LIMIT 1};

    logwriter($sql, 5);
    $sth_sel = $dbh->prepare($sql);
    $sth_sel->execute();

    my ($creator, $Jname, $juser, $sensor_id, $Jtype, $host_list, $profile_id, $jtimeout, $only_alive_hosts, $scan_locally, $resolve_names, $exclude_ports) = $sth_sel->fetchrow_array();

    $only_alive_hosts = "0" if empty($only_alive_hosts);

    get_asset_data($host_list, $job_id);

    $host_list = join("\n", @asset_to_scan);

    $sth_sel->finish;

    # Set Profile ID
    $sql = qq{UPDATE vuln_jobs SET profile_id='$profile_id' WHERE id='$job_id'};
    safe_db_write($sql, 5);


    if ($host_list eq "") {
        logwriter("Invalid Scan Config: NO hosts found ... ", 2);
        $sql = qq{UPDATE vuln_jobs SET status='H' WHERE id='$job_id'};
        safe_db_write($sql, 2);
        return;
    }

    logwriter("Scan #$job_id will begin shortly for the selected host:", 4);

    #It's very important to set the scan_PID before starting to avoid concurrency issues
    $sql = qq{UPDATE vuln_jobs SET status='R', scan_START='$start_date', scan_PID='$$' WHERE id='$job_id'};
    safe_db_write($sql, 5);

    logwriter("Begin Script Execution: setup_scan", 5);
    setup_scan($job_id, $Jname, $juser, $Jtype, $host_list, $profile_id, $jtimeout, $sensor_id, $only_alive_hosts, $scan_locally, $resolve_names, $creator, $exclude_ports);
    logwriter("End Script Execution", 5);
}

sub filter_assets {
    my ($sql, $sthse, $target, $targets, $ftargets, $job_id);
    my @filters=();
    my @result=();

    $targets = $_[0];
    $job_id  = $_[1];

    $sql = qq{SELECT meth_TARGET FROM vuln_jobs WHERE id='$job_id'};
    $sthse = $dbh->prepare($sql);
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

    foreach $target (@test_targets){
        if(!in_array(\@filters,$target)) {
            push(@result, $target);
        }
    }

    $sthse->finish;

    return join("\n",@result);
}

sub setup_scan {
    logwriter("Configuring targets ... ", 4);

    my ($job_id, $Jname, $juser, $Jtype, $target, $profile_id, $timeout, $sensor_id, $only_alive_hosts, $scan_locally, $resolve_names, $creator, $exclude_ports) = @_;
    my ($targetinfo, $results, $job_title, $gvm_ok, $scantime, $already_marked, $status, $progress, $job_status, $timeout_field);
    my ($sql, $sth_sel);

    $already_marked = FALSE;
    $job_title = "$job_id - $Jname";

    $target =~ s/\n/\r/g;
    $target =~ s/\r\r/\r/g;

    #ATTEMPT TO GET IP'S IN CASE USERS SUPPLIED HOSTNAMES
    my @tmp_hostarr = split /\r/, $target;
    foreach my $line (@tmp_hostarr) {
        if($resolve_names eq "0") { # do not resolve names
            $targetinfo .= "$line\r";
        }
        elsif($line !~ m/^\!/) {    #
            my $isIP = FALSE;
            my $hostip = "";
            #VALID IP OR ATTEMPT REVERSE NAME TO IP
            if ($line =~ m/^(\d\d?\d?)\.(\d\d?\d?)\.(\d\d?\d?)\.(\d\d?\d?)/){
                if($1 <= 255 && $2 <= 255 && $3 <= 255 && $4 <= 255) {
                    $hostip=$line;
                    $isIP = TRUE;
                }
            }

            # DO THE ATTEMPT TO REVERSE NAME
            if (!$isIP) {
                my $resolved_ip = resolve_name2ip ($line);
                if ($resolved_ip eq "") { $resolved_ip = $line; }
                logwriter("translated NAME=[$line] to [$resolved_ip]", 5);
                $hostip = $resolved_ip;            #EITHER WAY WE AT LEAST TRIED
            }
            $targetinfo .= "$hostip\r";
        }
    }

    #Check unresolved targets names
    if($txt_unresolved_names ne "") {
        $txt_unresolved_names = "Unresolved names:\n".$txt_unresolved_names;

        $sql = qq{UPDATE vuln_jobs SET meth_Wcheck=CONCAT(IFNULL(meth_Wcheck,''), '$txt_unresolved_names<br />') WHERE id='$job_id'};
        safe_db_write($sql, 3);
    }
    else {
        $sql = qq{UPDATE vuln_jobs SET meth_Wcheck='' WHERE id='$job_id'};
        safe_db_write($sql, 5);
    }

    if ($targetinfo eq "") {
        $targetinfo = "$target";
    }


    #MERGE/FILTER Potential \r\n as \r to ensure proper split
    $targetinfo =~ s/\n/\r/g;
    $targetinfo =~ s/\r\r/\r/g;

    my @hostarr = split /\r/, $targetinfo;
    @vuln_plugins_settings = get_plugins_settings($profile_id);

    ($results, $status, $progress) = run_scan($timeout, $job_title, \@hostarr, $profile_id, $job_id, $sensor_id, $only_alive_hosts, $scan_locally, $resolve_names, $creator, $exclude_ports);

    if($gvm_scan_timeout == TRUE){
        $job_status = 'T';
    } elsif ($status eq 'Done') {
        $job_status = 'C';
    } elsif ($status eq 'Stopped' || $status eq 'Stop Requested') {
        $job_status = 'I';
    } else {
        $job_status = 'F';
    }

    $scantime = getCurrentDateTime();

    $timeout_field = ($gvm_scan_timeout == TRUE) ? ", meth_Wcheck=CONCAT(IFNULL(meth_Wcheck,''), 'Timeout expired<br />')" : '';

    #GVM Scan finished successfully
    if ($no_results == FALSE) {
        logwriter("[$job_title] Begin SQL Import", 5);

        my %hostHash = pop_hosthash(\@$results);

        #FREE RESOURCES FROM ORIGINAL RESULTS ARRAY
        undef ($results);

        if (process_results(\%hostHash, $job_id, $job_title, $Jtype, $juser, $profile_id, $scantime)){
            logwriter("[$job_title] [ $job_id ] Completed SQL Import, scan_PID=$$", 5);

            $sql = qq{UPDATE vuln_jobs SET
                                 status='$job_status',
                                 scan_PID=$$,
                                 scan_END=UTC_TIMESTAMP(),
                                 scan_NEXT=NULL
                                 $timeout_field
                          WHERE id='$job_id'};

            safe_db_write($sql, 5);
            $already_marked = TRUE;
            $gvm_ok = TRUE;
        } else {
            logwriter("[$job_title] Failed SQL Import", 5);
            $gvm_ok = FALSE;
        }
    }
    elsif($no_results == TRUE && $txt_meth_wcheck eq "") {
        #No results found
        $sql = qq{UPDATE vuln_jobs SET status='$job_status', scan_PID='$$', scan_END=UTC_TIMESTAMP(), scan_NEXT=NULL $timeout_field WHERE id='$job_id'};
        safe_db_write($sql, 5);

        #Show warning message if scan finished without results and very quick
        if ($gvm_scan_timeout == FALSE) {
            my $diff = get_scan_time($job_id);
            if ($status eq 'Done' && $diff <= 120) {
                $sql = qq{UPDATE vuln_jobs SET meth_Wcheck = 'No results found, you should review targets and scan preferences' WHERE id='$job_id'};
                safe_db_write($sql, 5);
            }
        }

        $already_marked = TRUE;
    }

    #GVM Scan finished with errors
    if ($no_results == TRUE && $max_targets>=$CONFIG{'MAX_HOSTS'} && $txt_meth_wcheck ne "") {
        #Max number of hosts exceeded
        $sql = qq{UPDATE vuln_jobs SET status='$job_status', meth_Wcheck=CONCAT(IFNULL(meth_Wcheck,''), '$txt_meth_wcheck'), scan_END=UTC_TIMESTAMP(), scan_NEXT=NULL WHERE id='$job_id'};
        safe_db_write($sql, 1);
    }
    elsif (!$gvm_ok && $already_marked == FALSE) {
        my $retries_allowed = 0;
        if ($CONFIG{'failedRetries'}) {
            $retries_allowed = $CONFIG{'failedRetries'};
        }

        if ($retries_allowed eq "0") {
            # MARK SCAN AS FAILED
            $sql = qq{UPDATE vuln_jobs SET status='$job_status', scan_END=UTC_TIMESTAMP(), scan_NEXT=NULL WHERE id='$job_id'};
            safe_db_write($sql, 1);
        } else {
            $sql = qq{SELECT failed_attempts FROM vuln_jobs WHERE id='$job_id' LIMIT 1};
            logwriter($sql, 5);

            $sth_sel = $dbh->prepare($sql);
            $sth_sel->execute();
            my ($failed_count) = $sth_sel->fetchrow_array();
            my $tmp_status = "S";

            if ($failed_count ge $retries_allowed || $txt_meth_wcheck =~ /Nmap: No targets found/){
                $tmp_status = "F";
            }

            if ($tmp_status eq "F") {
                $txt_meth_wcheck =~ s/\'/\\'/g;
                if($semail eq "1") {
                    send_error_notifications_by_email($job_id, $txt_meth_wcheck);
                }

                $sql = qq{UPDATE vuln_jobs SET status='F', meth_Wcheck=CONCAT(IFNULL(meth_Wcheck,''), '$txt_meth_wcheck'), scan_END=UTC_TIMESTAMP(), scan_NEXT=NULL WHERE id='$job_id'};
                safe_db_write($sql, 1);
                logwriter("Job $job_title marked as failed", 2);
            } else {
                $failed_count += 1;
                logwriter("Attempt [ $failed_count ] to handle failed scans", 1);

                #Reschedule job
                reschedule_scan ($job_id);

                if (defined($txt_meth_wcheck) && $txt_meth_wcheck ne "") {
                    $txt_meth_wcheck =~ s/\'/\\'/g;
                    $sql = qq{UPDATE vuln_jobs SET meth_Wcheck=CONCAT(IFNULL(meth_Wcheck,''), '$txt_meth_wcheck') WHERE id='$job_id'};
                    safe_db_write($sql, 1);
                }
            }
        }
    }

    if (!$debug) {
        unlink $targetfile if -e $targetfile;
    }

    #Free scan slot
    free_scan_slot($sensor_id);

    # Delete targets
    if ($job_status ne 'I'){
        delete_scan_items($sensor_id, $job_id);
    }

    return $scantime;
}


#run scan return issues to setup scan
sub run_scan {
    my ($timeout)          = $_[0];
    my ($jobname)          = $_[1];
    my (@hosts)            = @{$_[2]};
    my ($sid)              = $_[3];
    my ($job_id)           = $_[4];
    my ($sensor_id)        = $_[5];
    my ($only_alive_hosts) = $_[6];
    my ($scan_locally)     = $_[7];
    my ($resolve_names)    = $_[8];
    my ($creator)          = $_[9];
    my ($exclude_ports)    = $_[10];

    logwriter("Running scan job $job_id ... ", 4);

    my ($target_id, $config_id, $task_id, $info_status, $status, $progress, @arr_status, @issues, $tsleep, $start_time, $current_time, $endScan, %credentials, $sensor_ip);
    my $targets = join("\n",@hosts);
    my ($sql, $sth_sel);

    # Update server counter of running scans
    book_scan_slot($sensor_id);

    $gvm_host = $CONFIG{'GVM_HOST'};
    $sensor_ip = get_gvm_sensor_ip($sensor_id, $job_id);
    $gvm_host = $sensor_ip;

    logwriter("Creator: $creator", 4);
    logwriter("Sensor ID: $sensor_id", 4);
    logwriter("Sensor IP: $sensor_ip", 4);
    print_scan_slots($sensor_id);
    logwriter("Targets: $targets", 4);
    logwriter("Resolve Names: $resolve_names", 4);
    logwriter("Only Alive Hosts: $only_alive_hosts", 4);
    logwriter("Scan Locally: $scan_locally", 4);

    #Disconneting DB ...
    disconn_db($dbh);

    if ($only_alive_hosts eq "1") {
        if ($scan_locally eq "1") {
            $targets = scan_discover($targets, $creator, 'local');
        } elsif ($sensor_id ne "") {
            $targets = scan_discover($targets, $creator, $sensor_id);
        }
    }
    $dbh = conn_db();
    $targets = filter_assets($targets, $job_id);

    unlink $targetfile if -e $targetfile;
    open(TARGET,">>$targetfile") or die "Failed to create $targetfile: $!\n";
    print TARGET "$targets";
    close TARGET;
    logwriter("Filtered Targets: $targets", 4);

    if($sensor_ip =~ m/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/) {
        $sql = qq{UPDATE vuln_jobs SET meth_Wcheck=CONCAT(IFNULL(meth_Wcheck,''), 'Scan Server Selected: $sensor_ip<br />') WHERE id='$job_id'};
        safe_db_write($sql, 5);
    }

    #Set default status
    $status = "New";
    $progress = "0";

    $max_targets = int(`cat "$targetfile" | wc -l`);
    if ($max_targets>=$CONFIG{'MAX_HOSTS'}) {
        $txt_meth_wcheck = "The selected target exceeds $CONFIG{'MAX_HOSTS'} alive hosts. Please select a different target.";
        @issues = ();
        $no_results = TRUE;
    }
    elsif($targets ne "") {
        $job_id_to_log = "$job_id";

        $sql = qq{SELECT task_id FROM vuln_jobs WHERE id='$job_id'};
        logwriter($sql, 5);

        $sth_sel=$dbh->prepare($sql);
        $sth_sel->execute;

        ($task_id) = $sth_sel->fetchrow_array;
        $sth_sel->finish;

        $config_id = get_config_id($sid, $job_id);

        #Task was stopped and then resumed
        if (no_empty($task_id)){
            system("/usr/bin/php /usr/share/ossim/scripts/vulnmeter/util.php update_vuln_jobs_assets insert $job_id 1");

            logwriter("Resuming Scan Task: $task_id", 4);
            resume_task($sensor_id, $task_id, $job_id);
        }
        else {
            %credentials = generate_credentials($job_id, $sensor_id);
            $target_id = generate_target_id($targets, \%credentials, $job_id, $exclude_ports, $sensor_id);

            logwriter("Creating Scan Task: $jobname, $config_id, $target_id", 4);
            $task_id = create_task($jobname, $config_id, $target_id, $job_id, $sensor_id);

            $sql = qq{UPDATE vuln_jobs SET task_id='$task_id' WHERE id='$job_id'};
            safe_db_write($sql, 5);

            system("/usr/bin/php /usr/share/ossim/scripts/vulnmeter/util.php update_vuln_jobs_assets insert $job_id 1");

            logwriter("Executing Scan Task: $task_id", 4);
            play_task($sensor_id, $task_id, $job_id);
        }

        $tsleep = 20;
        $endScan = 0;
        $start_time = time;

        do {
            sleep($tsleep);
            $info_status = get_task_status($task_id, $sensor_id, $job_id);
            @arr_status = split /\|/, $info_status;
            $status = shift(@arr_status);
            $progress = shift(@arr_status);
            $progress =~ s/\n|\t|\r|\s+//g;
            $progress = int($progress);
            logwriter("task id='$task_id' $status ($progress%)", 4);

            $current_time = time;
            if($current_time-$start_time >= $timeout) {
                $gvm_scan_timeout = TRUE;
                logwriter("Job ID $job_id expired by timeout", 4);
                stop_task($sensor_id, $task_id, $job_id);
            }

        } while (($status eq "Queued" || $status eq "New" || $status eq "Running" || $status eq "Requested") && $gvm_scan_timeout == FALSE);

        system("/usr/bin/php /usr/share/ossim/scripts/vulnmeter/util.php update_vuln_jobs_assets delete $job_id 1");

        #Conneting DB after scan ...
        $dbh = conn_db();

        #Reloading config in case of change
        load_db_configs();

        #Get results from GVM
        @issues = get_results_from_xml($job_id, $task_id, $sensor_id);
    }
    else {
        $txt_meth_wcheck = "Nmap: No targets found";
        @issues = ();
        $no_results = TRUE;
    }

    return (\@issues, $status, $progress);
}

#called by load_results to populate stats for report
sub update_stats {
    my ($sth_sel, $sql);
    my ($job_id, $job_title, $report_id, $scantime) = @_;

    my %RISKS = (1, 0, 2, 0, 3, 0, 4, 0, 5, 0, 6, 0, 7, 0);
    my $hostcnt = 0;
    my $runtime = 0;

    if (empty($report_id)) {
        logwriter("UPDATE STATS: failed to lookup report for scan $scantime", 2);
        return;
    }

    $sql = qq{SELECT scan_START, scan_END FROM vuln_jobs WHERE id = '$job_id'};
    logwriter($sql, 5);
    $sth_sel = $dbh->prepare($sql);
    $sth_sel->execute;
    my ($start_dttm, $end_dttm) = $sth_sel->fetchrow_array;

    $sql = qq{SELECT count(risk) as count, risk FROM vuln_nessus_results
              WHERE report_id='$report_id' AND falsepositive <> 'Y' GROUP BY risk};
    logwriter($sql, 5);
    $sth_sel = $dbh->prepare($sql);
    $sth_sel->execute;

    while(my ($count, $risk)=$sth_sel->fetchrow_array) {
        $RISKS{"$risk"} = $count;
    }

    $sql = qq{SELECT count(distinct hostIP) as count FROM vuln_nessus_results
              WHERE report_id='$report_id' AND falsepositive <> 'Y'};
    logwriter($sql, 5);
    $sth_sel = $dbh->prepare($sql);
    $sth_sel->execute;
    ($hostcnt) = $sth_sel->fetchrow_array;

    $sth_sel->finish;

    $runtime = datediff($start_dttm, $end_dttm, "M");

    my $update_time = getCurrentDateTime();

    $sql = "INSERT INTO vuln_nessus_report_stats (report_id, name, iHostCnt, dtLastScanned, iScantime,
        vCritical, vHigh, vMed, vMedLow, vLowMed, vLow, vInfo, trend, dtLastUpdated) VALUES (
        '$report_id', '$job_title', '$hostcnt', '$scantime', '$runtime', '$RISKS{1}', '$RISKS{2}',
        '$RISKS{3}', '$RISKS{4}', '$RISKS{5}', '$RISKS{6}', '$RISKS{7}', '0', '$update_time'); ";

    safe_db_write($sql, 5);
}

#Handle failed scans according to settings
sub reschedule_scan {
    my ($job_id) = @_;

    my ($sql, $sth_sel, $now);

    $now = getCurrentDateTime();

    my $year  = substr($now,0,4);
    my $month = substr($now,4,2);
    my $day   = substr($now,6,2);

    my $h     = substr($now,8,2);
    my $m     = substr($now,10,2);
    my $s     = substr($now,12,2);

    $sql = qq{SELECT DATE_ADD('$year-$month-$day $h:$m:$s', INTERVAL 1 HOUR)};

    $sth_sel = $dbh->prepare($sql);
    $sth_sel->execute();
    my ($next_run) = $sth_sel->fetchrow_array();

    $next_run  =~ s/://g;
    $next_run  =~ s/-//g;
    $next_run  =~ s/\s//g;

    logwriter("\tNext Scan: $next_run", 4);

    $sql = qq{UPDATE vuln_jobs SET status="S", scan_START=NULL, scan_END=NULL, scan_NEXT='$next_run',
            scan_PID='0', report_id='0', failed_attempts=failed_attempts+1 WHERE id='$job_id'};

    safe_db_write($sql, 1);
}

#prep host list move cidr based lists to ip list to check for exceptions
sub build_hostlist {
    my ($CIDR) = @_;

    my $block = new Net::Netmask ($CIDR);

    return $block->enumerate();
}

#pop hosthash will process the results to make the most of the data.  This will improve reporting / tracking of scanned hosts
sub pop_hosthash {
    my @issues = @{$_[0]};
    my %hostHash;

    if ($no_results) {
        logwriter("NO Results found or host is offline", 2);
        return FALSE;
    }


    logwriter("Populating Host Info ...", 4);
    logwriter("Number of results: ".$#issues, 4);

    my $ih = 0;

    foreach(@issues) {
        my $issue = $_;
        my ($scanid, $host, $hostname, $hostip, $service, $app, $port, $proto, $desc, $record_type, $risk) = " ";

        $host = $issue->{Host};
        $app = $issue->{App};
        $port = $issue->{Port};
        $proto = $issue->{Proto};
        $scanid = $issue->{ScanID};
        $desc = $issue->{Description};
        $service = $issue->{Service};
        $risk = $issue->{Risk};
        $record_type = $issue->{RecordType};

        if (!$hostHash{$host}{'mac'}) {
            $hostHash{$host}{'mac'} = "unknown";
        }

        if (!exists($hostHash{$host}{'dns'})) {
            #DETERMINE IF IT IS AN IP (CRITICAL STEP AS SCAN RETURNS EITHER HOSTIP/HOSTNAME)
            my $isIP = FALSE;
            if ($host =~ m/^(\d\d?\d?)\.(\d\d?\d?)\.(\d\d?\d?)\.(\d\d?\d?)/){
                if($1 <= 255 && $2 <= 255 && $3 <= 255 && $4 <= 255) {
                    $hostip=$host;
                    $isIP = TRUE;
                }
            }

            if ($isIP == FALSE) {
                #LETS AT LEAST SET NAME IN CASE ALL ELSE FAILS
                $hostname=$host;
                my $tmp_hostip = resolve_name2ip($host);
                if (defined($tmp_hostip) && $tmp_hostip ne "") {
                    $hostip = $tmp_hostip;
                }
            }

            if (defined($hostip) && $hostip ne "") {
                #Attempt to consult various DNS servers in case than scan returned an IP
                disconn_db($dbh);
                my $tmp_hostname = resolve_host($hostip);

                $dbh = conn_db();
                if (defined($tmp_hostname) && $tmp_hostname ne "") {
                    $hostname = $tmp_hostname;
                }
            }

            $hostHash{$host}{'ip'} = $hostip;
            if(defined($hostname) && $hostname ne "") {
                $hostHash{$host}{'fqdn'} = $hostname;
                #INDICATE RESOLVED BY NAME WAS SUCCESS
                $hostHash{$host}{'dns'} = "1";
                logwriter("Vuln scan: successfully looked up name [$host]", 5);
            } else {
                #INDICATE RESOLVED BY NAME FAILED
                $hostHash{$host}{'dns'} = "-1";
            }
        }

        #NBTSCAN PLUGIN
        if ($scanid eq "10150") {
            my %hostinfo = extract_hostinfo($desc);
            $hostHash{$host}{'mac'} = $hostinfo{'mac'};

            if ($hostHash{$host}{'dns'} eq "-1" && $hostinfo{'dns'} eq "1") { #ONLY UPDATE NAME FROM 10150 WHEN DNS FAILS
                $hostHash{$host}{'fqdn'} = $hostinfo{'hostname'};
                $hostHash{$host}{'dns'} = '1';
                logwriter("Vuln scan: Success plugin 10150 to look up name [" . $hostinfo{'hostname'} . "]", 5);
            }
        }

        my $key = $ih;
        $hostHash{$host}{'results'}{$key} = {
            'scanid'  => $scanid,
            'port'    => $port,
            'app'     => $app,
            'service' => $service,
            'proto'   => $proto,
            'risk'    => $risk,
            'record'  => $record_type,
            'desc'    => $desc
        };

        $ih++;
    }

    logwriter("Populating process $ih finished", 4);

    return (%hostHash);
}

sub create_report {
    my ($job_title) = $_[0];
    my ($scantype)  = $_[1];
    my ($username)  = $_[2];
    my ($sid)       = $_[3];
    my ($scantime)  = $_[4];

    my ($report_id, $report_key);
    my ($sql, $sth_sel);

    #Build a report_key value to secure reports.
    my @arr = split(/\./, rand());
    if ($arr[1] && is_number($arr[1])) {
        $report_key = $arr[1];
    } else {
        logwriter("Failed Report Key generation", 2);
    }

    $sql = qq{INSERT INTO vuln_nessus_reports (username, name, fk_name, sid, scantime, report_type, scantype, report_key)
              VALUES ('$username', '$job_title', NULL, '$sid', '$scantime', 'N', '$scantype', '$report_key');};
    safe_db_write($sql, 5);

    $sql = qq{SELECT report_id FROM vuln_nessus_reports WHERE scantime='$scantime' AND report_key='$report_key'
               ORDER BY scantime DESC LIMIT 1};
    logwriter($sql, 5);

    $sth_sel=$dbh->prepare($sql);
    $sth_sel->execute;
    $report_id = $sth_sel->fetchrow_array();
    $sth_sel->finish;

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

    my ($sql, $sth_sel, $sql2, $sql_insert, $sql_insert2, $report_id);
    my ($sth_update, $sql_update, $sth_del, $sql_delete);
    my ($rpt_key, $sqli, $sth_ins);
    my ($fp_sel, $fp_service, $fp);

    my $close_vuln_tickets_automatically = int($gvm_vars{'close_vuln_tickets_automatically'});

    #Job was resumed, we have to delete previous report
    $sql = qq{SELECT report_id FROM vuln_jobs WHERE id='$job_id'};
    $sth_sel = $dbh->prepare($sql);
    $sth_sel->execute;
    $report_id = $sth_sel->fetchrow_array();
    $report_id = int($report_id);

    if (no_empty($report_id) && $report_id > 0){
        logwriter("Deleting previous Report ID: $report_id", 4);

        $sql_delete = qq{DELETE FROM vuln_nessus_results WHERE report_id = '$report_id'};
        safe_db_write($sql, 5);

        $sql_delete = qq{DELETE FROM vuln_nessus_reports WHERE report_id = '$report_id'};
        safe_db_write($sql, 5);

        $sql = qq{UPDATE vuln_jobs SET report_id='' WHERE id='$job_id'};
        safe_db_write($sql, 5);
    }

    #Extract networks from targets
    my $targets = "";
    $sql = qq{SELECT meth_TARGET FROM vuln_jobs WHERE id='$job_id'};
    $sth_sel = $dbh->prepare($sql);
    $sth_sel->execute;
    $targets = $sth_sel->fetchrow_array();

    $report_id = create_report ($job_title, $scantype, $username, $sid, $scantime);

    logwriter("Report ID: $report_id", 4);
    if (!$report_id) {
        logwriter("Failed to lookup report after insert for scan $scantime", 4);
        return 0;
    }

    $sth_sel->finish;

    if (-e $outfile) {
        # save the raw nbe
        system("mkdir -p /usr/share/ossim/uploads/nbe;chown www-data:www-data /usr/share/ossim/uploads/nbe;mv '$outfile' '$nbe_path".$report_id.".nbe';chmod 644 '$nbe_path".$report_id.".nbe'");
    }

    #UPDATE ASSOCIATED REPORT_ID FIELDS
    $sql = qq{UPDATE vuln_jobs SET report_id='$report_id', scan_END='$scantime' WHERE id='$job_id' LIMIT 1};
    safe_db_write($sql, 5);

    logwriter("Processing Results ...", 4);
    $sql_insert = "";
    my $i = 0;

    foreach my $host (sort keys %hostHash) {
        my ($hostip, $ctx, $hid, $hostname, $mac_address, $open_issues) = "";

        my $host_id = "0";

        if ($hostHash{$host}{'ip'}) {
            $hostip = $hostHash{$host}{'ip'};
        }

        my $ip_hex = get_varhex_from_ip($hostip);

        # get host ctx
        $ctx = get_default_ctx();

        if(defined($asset_data{$hostip}{'ctx'})) {
            $ctx = $asset_data{$hostip}{'ctx'};
        }
        else{
            foreach my $cidr (keys %asset_data) { # check if the host in a net
                if($cidr=~/.*\/.*/ && ip_in_net($hostip, $cidr)) {
                    $ctx = $asset_data{$cidr}{'ctx'};
                }
            }
        }

        # Get host id
        $hid = '';

        if(defined($asset_data{$hostip}{'id'})) {
            $hid = $asset_data{$hostip}{'id'};
        }
        else # we need to insert the new host
        {
            $hostname = $hostip;
            $hostname =~ s/\./\-/g;

            my $host_data = trim(encode_base64("$hostip|$ctx|Host-$hostname|"));
            $host_data =~ s/\n//g;

            if (no_empty($hostip) && no_empty($hostname) && no_empty($ctx)) {
                my $result = `/usr/bin/php /usr/share/ossim/scripts/vulnmeter/util.php insert_host $host_data`;
                if ($result =~ m/.*Host\s*ID:\s*([a-f\d]{32})\s*$/i)
                {
                    logwriter("New host -> Hostname: $hostname, ID: $1", 4);
                    $hid = $1;
                }
            }
        }

        if ($hostHash{$host}{'fqdn'}) {
            $hostname = $hostHash{$host}{'fqdn'};
        }

        if ($hostHash{$host}{'mac'}) {
            $mac_address = $hostHash{$host}{'mac'};
        }

        #before delete extract data
        my $sql_extract_data = qq{SELECT count(risk) as count, risk FROM vuln_nessus_latest_results
                                            WHERE hostIP = '$hostip' and username = '$username' and sid = '$sid'
                                            AND ctx = UNHEX ('$ctx') AND falsepositive='N' GROUP BY risk};
        logwriter($sql_extract_data, 5);

        my $sth_extract=$dbh->prepare($sql_extract_data);
        $sth_extract->execute;

        my @risks_stats = ("0","0","0","0","0");

        while (my ($risk_count, $risk)=$sth_extract->fetchrow_array) {
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
        $sql_delete = qq{DELETE FROM vuln_nessus_latest_results
                         WHERE hostIP = '$hostip' and username = '$username' and ctx = UNHEX ('$ctx') and sid = '$sid'};


        logwriter($sql_delete, 5);
        $sth_del = $dbh->prepare($sql_delete);
        $sth_del->execute;

        $hostname = trim($hostname);
        #LOOKUP HOSTID

        if (defined ($hostname) && $hostname ne "") {
            $host_id = get_host_record($hostname, $hostip);
        }

        # load fps
        my %host_fp = ();
        $sql = qq{SELECT scriptid,service FROM vuln_nessus_latest_results
                  WHERE hostIP='$hostip' and ctx = UNHEX ('$ctx') and falsepositive = 'Y'
                  UNION
                  SELECT scriptid,service FROM vuln_nessus_results
                  WHERE hostIP='$hostip' and ctx = UNHEX ('$ctx') and falsepositive = 'Y'};


        $fp_sel = $dbh->prepare($sql);
        $fp_sel->execute;
        while ((my $fp_scriptid,$fp_service) = $fp_sel->fetchrow_array) {
            $host_fp{$fp_scriptid}{$fp_service} = 1;
        }

        my %recordshash = %{$hostHash{$host}{'results'}};
        my %vuln_resume = ();

        foreach my $record (sort keys %recordshash) {
            my ($scanid, $service, $app, $port, $proto, $risk, $domain, $record_type, $desc) = " ";

            $scanid = $hostHash{$host}{'results'}{$record}{'scanid'};
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

            logwriter("Record => $record 'Scan ID' => [$scanid], 'Port' => [$port], 'Record type' => [$record_type], 'Service' => [$service],"
                ." 'Protocol' => [$proto], 'Risk' => [$risk], 'Desc' => [$desc]\n", 5);

            if (!defined($sql_insert) || $sql_insert eq "") {
                #FIRST ITERATION OR RESET VARIABLE AFTER IMPORTING 100 RECORDS
                $sql_insert = "INSERT INTO vuln_nessus_results (report_id, scantime, hostip, ctx, hostname, record_type, service, port, protocol , app, scriptid, risk, msg, falsepositive)\nVALUES\n";
                $sql_insert2 = "INSERT INTO vuln_nessus_latest_results (username, sid, scantime, hostip, ctx, hostname, record_type, service, port, protocol , app, scriptid, risk, msg, falsepositive)\nVALUES\n";

                #delete host_plugin_sid results
                $sql_delete = qq{DELETE FROM host_plugin_sid WHERE host_ip = UNHEX('$ip_hex') and ctx=UNHEX('$ctx') and plugin_id = 3001};
                logwriter($sql_delete, 5);
                $sth_del = $dbh->prepare($sql_delete);
                $sth_del->execute;

                #delete host_plugin_sid results
                my @arr = split(/\./, rand());
                if ($arr[1] && is_number($arr[1])) { $rpt_key = $arr[1]; }
                else { $rpt_key = 0; }
                $sqli = qq{INSERT INTO vuln_nessus_latest_reports (hostIP, ctx, username, fk_name, sid, scantime, report_type, scantype, report_key, note, failed)
                VALUES ('$hostip', UNHEX('$ctx'), '$username', NULL, '$sid', '$scantime', 'N', '$scantype', '$rpt_key', '0;0;0;0;0','0') ON DUPLICATE KEY UPDATE scantime='$scantime', failed=results_sent, note='$last_string'};
                logwriter($sqli, 5);
                $sth_ins = $dbh->prepare($sqli);
                $sth_ins->execute;
                $i=0;
            }
            $i += 1;
            $fp = (defined($host_fp{$scanid}{$service}) && $host_fp{$scanid}{$service} == 1) ? 'Y' : 'N';
            $sql_insert .= " ('$report_id', '$scantime', '$hostip', UNHEX('$ctx'), '$hostname', '$record_type', '$service', '$port', '$proto', '$app', '$scanid', '$risk', '$desc', '$fp'),\n";
            $sql_insert2 .= " ('$username', '$sid', '$scantime', '$hostip', UNHEX('$ctx'), '$hostname', '$record_type', '$service', '$port', '$proto', '$app', '$scanid', '$risk', '$desc', '$fp'),\n";

            if ($i >= 100) {
                chop($sql_insert);
                chop($sql_insert);
                chop($sql_insert2);
                chop($sql_insert2);
                $sql_insert .= ";";
                $sql_insert2 .= ";";
                #CONNECT DB AND INSERT 100 RECORDS
                safe_db_write($sql_insert, 5);
                logwriter("[$job_title] - inserted $i results records", 4);
                safe_db_write($sql_insert2, 5);
                logwriter("[$job_title] - inserted $i latest_results records", 4);

                $sql_insert = "";
                $sql_insert2 = "";
                $i = 0;
            }

            if(!defined($vuln_resume{"$hostip;$ctx"})) {
                $vuln_resume{"$hostip;$ctx;$hid"} = $risk;
            }
            elsif($risk < $vuln_resume{"$hostip;$ctx"}) {
                $vuln_resume{"$hostip;$ctx;$hid"} = $risk;
            }

            #Incidents
            update_ossim_incidents($hostip, $ctx, $port, $risk, $desc, $scanid, $username, $sid);
        }

        #CHECK FOR RECORDS WHICH REMAIN NOT INSERTED FOR HOST
        if (defined($sql_insert) && $sql_insert ne "") {
            chop($sql_insert);
            chop($sql_insert);
            chop($sql_insert2);
            chop($sql_insert2);
            $sql_insert .= ";";
            $sql_insert2 .= ";";
            #CONNECT DB AND INSERT REMAINDER OF RECORDS
            safe_db_write($sql_insert, 5);
            logwriter("[$job_title] - inserted $i results records", 4);
            safe_db_write($sql_insert2, 5);
            logwriter("[$job_title] - inserted $i latest_results records", 4);
            $sql_insert = "";
            $sql_insert2 = "";
        }

        my $max_risk = 0;

        foreach my $data (keys %vuln_resume) {
            #Max_risk is the field risk in vuln_nessus_results table
            my ($hip, $ctx, $hid) = split(/;/, $data);

            $max_risk = $vuln_resume{$data};

            if($max_risk <= 2) {
                $max_risk = 10;
            }
            elsif ($max_risk <= 6) {
                $max_risk = 7;
            }
            else {
                $max_risk = 3;
            }

            $sql = qq{SELECT scriptid FROM vuln_nessus_latest_results WHERE hostIP='$hip' AND ctx=UNHEX('$ctx')};
            logwriter($sql, 5);
            $sth_sel = $dbh->prepare($sql);
            $sth_sel->execute;

            while ((my $scanid) = $sth_sel->fetchrow_array) {
                my $ip_hex = get_varhex_from_ip($hip);

                $sql_update = qq{INSERT IGNORE INTO host_plugin_sid (host_ip, ctx, plugin_id, plugin_sid) VALUES (UNHEX('$ip_hex'), UNHEX('$ctx'), 3001, $scanid)};
                logwriter($sql_update, 5);
                $sth_update = $dbh->prepare($sql_update);
                $sth_update->execute;
                $sth_update->finish;
            }

            #host_vulnerability
            if(defined($hid) && $hid ne "") {
                $sql_update = qq{DELETE FROM host_vulnerability WHERE host_id=UNHEX('$hid')};
                logwriter($sql_update, 5);
                $sth_update = $dbh->prepare($sql_update);
                $sth_update->execute;
                $sth_update->finish;

                $sql_update = qq{INSERT INTO host_vulnerability VALUES (UNHEX('$hid'), '$scantime', $max_risk) ON DUPLICATE KEY UPDATE vulnerability='$max_risk'};
                logwriter($sql_update, 5);
                $sth_update = $dbh->prepare($sql_update);
                $sth_update->execute;
                $sth_update->finish;
            }

            #vulnerabilities
            $sql_update = qq{SELECT count(*) AS vulnerability FROM (SELECT DISTINCT hostip, port, protocol, app, scriptid, msg, risk
                        FROM vuln_nessus_latest_results WHERE hostIP='$hip' AND ctx=UNHEX('$ctx') AND falsepositive='N') AS t GROUP BY hostip};
            logwriter($sql_update, 5);
            $sth_update=$dbh->prepare($sql_update);
            $sth_update->execute;
            my ($vuln_host) = $sth_update->fetchrow_array;
            $vuln_host = 0 if (!defined $vuln_host);

            # update vulns into vuln_nessus_latest_reports - sort facility
            $sql_update = qq{UPDATE vuln_nessus_latest_reports SET results_sent='$vuln_host' WHERE hostIP='$hip' AND ctx=UNHEX('$ctx') AND username='$username'};
            logwriter($sql_update, 5);
            $sth_update = $dbh->prepare($sql_update);
            $sth_update->execute;
        }

        $sql_update = qq{TRUNCATE TABLE net_vulnerability};
        logwriter($sql_update, 5);
        $sth_update = $dbh->prepare($sql_update);
        $sth_update->execute;

        $sql_update = qq{INSERT INTO net_vulnerability SELECT net_id, '$scantime', max(v.vulnerability) FROM host_net_reference r,host_vulnerability v,net n WHERE r.host_id=v.host_id AND n.id=r.net_id group by net_id};
        logwriter($sql_update, 5);
        $sth_update = $dbh->prepare($sql_update);
        $sth_update->execute;
        $sth_update->finish;

        #UPDATE CLOSED INCIDENTS AS RESOLVED (per previously update_incidents)
        if ($close_vuln_tickets_automatically == 1 && defined($hostip) && defined($ctx)) {
            my $profile_name = get_config_name($sid);

            $sql = qq{SELECT distinct i.id,iv.nessus_id from
                            incident_vulns iv join incident i on i.id=iv.incident_id
                            where iv.ip='$hostip' and iv.ctx = UNHEX('$ctx') AND i.status='Open' AND (iv.description like '%Profile Name: $profile_name' OR iv.description like '%SID:$sid')};
            logwriter($sql, 5);
            $sth_sel = $dbh->prepare($sql);
            $sth_sel->execute;

            while(my ($incident_id, $scriptid)=$sth_sel->fetchrow_array) {
                #FAIL SAFE DO MARK ANY PLUGINS NOT TESTED AS RESOLVED
                if (grep { $_ eq $scriptid } @vuln_plugins_settings) {
                    logwriter("Checking incident [$incident_id] against Script ID $scriptid", 4);
                    if ($open_issues =~ /$scriptid/) {
                        #CURRENTLY NOT RESOLVED
                    } else {
                        #CURRENTLY CREDENTIALS VS NO /CREDENTIALS WILL CAUSE AN INVALID CLEANUP STATE TO BE SET
                        $sql2 = qq{UPDATE incident SET status='Closed', last_update='$scantime' WHERE id='$incident_id'};
                        safe_db_write($sql2, 5);
                    }
                } else {
                    logwriter("PLUGIN $scriptid apparently was not tested", 3);
                }
            }

            $sth_sel->finish;
        }
    }


    logwriter("SQL Import completed", 4);
    update_stats ($job_id, $job_title, $report_id, $scantime);

    if($semail eq "1") {
        logwriter("Sending email notification ...", 4);
        my $cmde = qq{/usr/bin/php /usr/share/ossim/scripts/vulnmeter/send_notification.php '$job_id'};
        logwriter("Send email for job_id: $job_id ...", 5);
        open(EMAIL,"$cmde 2>&1 |") or die "failed to fork :$!\n";
        while(<EMAIL>){
            chomp;
            logwriter("Email Notification Output: $_", 5);
        }
        close EMAIL;
    }

    return TRUE;
}

# extract host info <- assuming msg from plugin #10150 is supplied
sub extract_hostinfo {
    logwriter("Extracting Host Info ...", 4);

    my ($txt_msg) = @_;

    my ($hostname, $mac_address) = "";

    logwriter("Plugin 10150 Data: [[$txt_msg]]", 5);
    my @arrMSG = split /\\n\\n|\n\n|\r/, $txt_msg;
    foreach my $line (@arrMSG) {
        $line =~ s/\#.*$//;
        chomp($line);
        $line =~ s/\s+$//;
        $line =~ s/^\s+//;

        logwriter("LINE=[$line]", 5);

        if ($line =~ /computer\sname/i) {
            my @temp=split(/=/,$line,2);
            $temp[0] =~ s/\s+$//;
            $temp[0] =~ s/^\s+//;
            $hostname = lc($temp[0]);
            logwriter("Hostname=[$hostname]", 5);
        } elsif ($line =~ /^([0-9a-fA-F][0-9a-fA-F]:){5}([0-9a-fA-F][0-9a-fA-F])$/) {
            $mac_address = uc($line);
            $mac_address =~ s/[:-]//g;
        }

        next;
    }

    if (!$mac_address) {
        $mac_address = "unknown";
    }

    if ($hostname =~ /Synopsis:/i) {
        $hostname = "";
    }

    if (defined ($hostname) && $hostname ne "") {
        logwriter ("my %hostinfo = ('dns' => '1', 'hostname' => '$hostname', 'mac' => '$mac_address');\n", 5);
        my %hostinfo = ('dns' => '1', 'hostname' => $hostname, 'mac' => $mac_address);
        return %hostinfo;
    } else {
        logwriter ("my %hostinfo = ('dns' => '-1', 'mac' => $mac_address);\n", 5);
        my %hostinfo = ('dns' => '-1', 'mac' => $mac_address);
        return %hostinfo;
    }
}

#needed for host updated / load results
sub resolve_host {
    my ($hostip) = @_;

    if (!defined ($hostip) || $hostip eq "") {
        return "";
    }

    # ATTEMPT GET HOST BY ADDRESS WILL CHECK FILES/DNS PRIMARY
    my $iaddr = inet_aton($hostip); # or whatever address
    my $namer = gethostbyaddr($iaddr, AF_INET);

    if (defined($namer)) {
        my $thost = lc ($namer);
        return $thost;
    }

    logwriter("Reverse IP [$hostip] to name failed\n", 3);

    return "";
}

#ENSURE IP'S ARE IN THE BUILDLIST Otherwise Hostnames are returned instead IP's
#THEY GET STUFFED IN THE HOSTIP Field as a result
sub resolve_name2ip {
    my ($hostname) = @_;
    if (! defined ($hostname) || $hostname eq "") {
        return "";
    }

    my $ip = get_asset_by_name($hostname);

    if ($ip ne "") {
        return $ip;
    }

    disconn_db($dbh);

    # ATTEMPT GET HOST BY ADDRESS WILL CHECK FILES/DNS PRIMARY
    my $packed_ip = gethostbyname($hostname);

    if (defined($packed_ip)) {
        my $c_ip = inet_ntoa($packed_ip);
        $dbh = conn_db();

        return $c_ip;
    }

    logwriter("Resolve [$hostname] to IP failed\n", 3);

    return "";
}

# Get the job schedule table to setup recurring jobs
sub check_schedule {
    logwriter("Checking pending scan jobs ...", 4);
    my ($sql, $sth_sel, $now);

    $now = getCurrentDateTime();

    $sql = qq{SELECT id, name, username, fk_name, job_TYPE, schedule_type, only_alive_hosts, profile_id, meth_TIMEOUT,
        next_CHECK, meth_Wcheck, scan_locally, resolve_names, IP_ctx, send_email, credentials, exclude_ports, ssh_credential_port
        FROM vuln_job_schedule WHERE enabled != '0' and next_check <= '$now'};

    logwriter($sql, 5);

    $sth_sel=$dbh->prepare($sql);
    $sth_sel->execute;

    while (my($jid, $name, $username, $fk_name, $jobTYPE, $schedule_type, $only_alive_hosts, $profile_id, $meth_TIMEOUT,
        $next_check, $vuln_job_id, $scan_locally, $resolve_names, $IP_ctx, $send_email,
        $credentials, $exclude_ports, $ssh_credential_port) = $sth_sel->fetchrow_array)
    {
        if (no_empty($vuln_job_id))
        {
            $sql = qq{SELECT count(*) FROM vuln_jobs WHERE id='$vuln_job_id' AND status='R'};
            logwriter($sql, 5);

            $sth_sel=$dbh->prepare($sql);
            $sth_sel->execute;

            my $count = $sth_sel->fetchrow_array;

            if ($count ne 0)
            {
                gen_sched_next_scan ($jid, $schedule_type);
                next;
            }
        }

        my $diff = (datediff($now , $next_check , 'H')) * 1.0;

        logwriter("Different between next_check and now is: $diff hours", 4);

        if ($diff > 1.0)
        {
            gen_sched_next_scan($jid, $schedule_type);
        }
        else
        {
            $scan_locally = 0 if ($scan_locally eq "" || !$scan_locally);

            if ($fk_name eq "") {
                $fk_name = "NULL";
            } else {
                $fk_name = "'".$fk_name."'";
            }

            # split the scheduled jobs
            my $get_jobs_file = "/usr/share/ossim/www/tmp/get_jobs_$$";

            system("/usr/bin/php /usr/share/ossim/scripts/vulnmeter/get_jobs.php '$jid' > $get_jobs_file ");

            open(SCHEDULED_JOBS, $get_jobs_file) or die "failed to fork :$!\n";
            while(<SCHEDULED_JOBS>){
                chomp;

                if (m/^([a-f0-9]{32})\|(.*)/i)
                {
                    logwriter("Adding 'SCHEDULED - $name' to the scan queue ...", 4);

                    my $job_targets = $2;
                    my $sensor_id   = $1;

                    $job_targets =~ s|;|\n|g;
                    $sql = qq{INSERT INTO vuln_jobs (name, username, fk_name, job_TYPE, meth_TARGET, only_alive_hosts,
                                                      profile_id, meth_TIMEOUT, scan_ASSIGNED, scan_SUBMIT, scan_NEXT,
                                                      notify, tracker_id, authorized, resolve_names, IP_ctx,
                                                      send_email, credentials, exclude_ports, ssh_credential_port)
                                      VALUES ('SCHEDULED - $name', '$username', $fk_name, '$jobTYPE', '$job_targets',
                                              '$only_alive_hosts', '$profile_id', '$meth_TIMEOUT', '$sensor_id', '$now',
                                              '$next_check', '$sensor_id', '$jid', '$scan_locally', $resolve_names,
                                              '$IP_ctx', $send_email, '$credentials', '$exclude_ports', '$ssh_credential_port')};
                    safe_db_write($sql, 5);

                    $sql = qq{UPDATE vuln_job_schedule SET meth_Wcheck=LAST_INSERT_ID() WHERE id='$jid'};
                    safe_db_write($sql, 5);
                }
            }
            close SCHEDULED_JOBS;

            unlink $get_jobs_file if -e $get_jobs_file;

            gen_sched_next_scan ($jid, $schedule_type);
        }
    }

    $sth_sel->finish;
}

#called to update host tracker, most recent scan info for hosts
sub get_host_record {
    my ($hostname, $hostip) = @_;

    my ($sql, $sth_sel);
    my ($host_id) = "0";

    my $now = getCurrentDateTime();

    $sql = qq{SELECT id FROM vuln_hosts WHERE hostname = '$hostname' LIMIT 1};
    logwriter($sql, 5);
    $sth_sel = $dbh->prepare($sql);
    $sth_sel->execute;
    ($host_id) = $sth_sel->fetchrow_array;

    if (!defined($host_id)) {
        $sql = "INSERT INTO vuln_hosts (hostip, hostname, status, lastscandate, createdate) VALUES (
            '$hostip', '$hostname', 'Production', '$now', '$now');";
        safe_db_write($sql, 5);

        $sql = qq{SELECT id FROM vuln_hosts WHERE hostname = '$hostname' LIMIT 1};
        logwriter($sql, 5);
        $sth_sel = $dbh->prepare($sql);
        $sth_sel->execute;
        $host_id = $sth_sel->fetchrow_array;
    }

    $sth_sel->finish;
    return $host_id;
}

# returns an array of the plugins enabled for this scan id
sub get_plugins_settings {
    my ($sid) = @_;
    my @plugins = ();

    my ($sql, $sth_sel, $sth_sel2, $gvm_plugin_id, $gvm_plugin_value);

    #SCANNER SET
    $sql = qq{SELECT t1.id, t1.enabled from vuln_nessus_settings_plugins t1
	LEFT JOIN vuln_nessus_category t2 ON t1.category=t2.id
        where t2.name ='scanner' and t1.sid='$sid' order by t1.id};
    $sth_sel=$dbh->prepare($sql);
    $sth_sel->execute;

    while (($gvm_plugin_id, $gvm_plugin_value) = $sth_sel->fetchrow_array) {
        if ($gvm_plugin_value ne "N") {
            push(@plugins,"$gvm_plugin_id");
        }
    }

    $sql = qq{SELECT t1.fid FROM vuln_nessus_settings_family t1
        LEFT JOIN vuln_nessus_family t2 ON t2.id=t1.fid
     WHERE t2.name<>'Port scanners' AND t1.sid='$sid' ORDER BY t2.id};

    $sth_sel=$dbh->prepare($sql);
    $sth_sel->execute;

    while (my ($fam_id)=$sth_sel->fetchrow_array) {
        $sql = qq{SELECT id, enabled FROM vuln_nessus_settings_plugins
           WHERE sid='$sid' AND family='$fam_id' ORDER BY id};
        $sth_sel2=$dbh->prepare($sql);
        $sth_sel2->execute;

        while (($gvm_plugin_id, $gvm_plugin_value)=$sth_sel2->fetchrow_array) {
            if ($gvm_plugin_value ne "N") {
                push(@plugins,$gvm_plugin_id);
            }
        }
    }

    $sth_sel->finish;

    return(@plugins);
}

#setup next scan field based on job schedule input
sub gen_sched_next_scan {
    my ($schedid, $schedule_type) = @_;
    my $sql;

    if ($schedule_type eq "O") {
        $sql = qq{DELETE FROM vuln_job_schedule WHERE id='$schedid'};
        safe_db_write($sql, 5);
    } elsif ($schedule_type eq "NW") {
        my @reschedule_args = ("/usr/share/ossim/scripts/vulnmeter/nessus_job_reschedule.pl", "$schedid");
        system(@reschedule_args) == 0 or die "ERROR: failed to re-schedule job with ID $schedid!";
    } else {
        #select time_interval to skip some days or weeks
        my $interval = "";
        if ($schedule_type eq "D") {
            $interval = "time_interval DAY";
        } elsif ($schedule_type eq "W") {
            $interval = "time_interval*7 DAY";
        } elsif ($schedule_type eq "M") {
            $interval = "1 MONTH";
        }
        $sql = qq{UPDATE vuln_job_schedule
                  SET next_CHECK=REPLACE(REPLACE(REPLACE(next_CHECK+INTERVAL $interval,"-",""),":","")," ","")
                  WHERE id='$schedid'};
        safe_db_write($sql, 5);
    }

    return;
}

#get current date/time
sub getCurrentDateTime {
    return strftime "%Y%m%d%H%M%S", gmtime;
}

#needed for report stats
sub datediff {
    my ($start_date, $end_date, $unit) = @_;
    my (%start, %end);

    if (!defined($start_date) || !defined($end_date)) {
        return  -1;
    }

    $start_date =~ s/\///g;
    $start_date =~ s/\s//g;
    $start_date =~ s/-//g;
    $start_date =~ s/://g;

    $start{YEAR} = substr($start_date, 0,4);
    $start{MO} = substr($start_date, 4,2);
    $start{D} = substr($start_date, 6,2);
    $start{H} = substr($start_date, 8,2);
    $start{M} = substr($start_date, 10,2);
    $start{S} = substr($start_date, 12,2);

    $end_date =~ s/\///g;
    $end_date =~ s/\s//g;
    $end_date =~ s/-//g;
    $end_date =~ s/://g;

    $end{YEAR} = substr($end_date, 0,4);
    $end{MO} = substr($end_date, 4,2);
    $end{D} = substr($end_date, 6,2);
    $end{H} = substr($end_date, 8,2);
    $end{M} = substr($end_date, 10,2);
    $end{S} = substr($end_date, 12,2);

    my ($Dd,$Dh,$Dm,$Ds) = Delta_DHMS($start{YEAR},$start{MO},$start{D}, $start{H},$start{M},$start{S},
        $end{YEAR},$end{MO},$end{D}, $end{H},$end{M},$end{S});

    my $diff = 0;

    if ($Dd) {
        $diff += ($Dd * 216000);
    }

    if ($Dh) {
        $diff += ($Dh * 3600);
    }

    if ($Dm) {
        $diff += ($Dm * 60);
    }

    if ($Ds) {
        $diff += $Ds
    }

    if ($unit eq "D") {
        $diff = ($diff / 216000);
    } elsif ($unit eq "H") {
        $diff = ($diff / 3600);
    } elsif ($unit eq "M") {
        $diff = ($diff / 60);
    } else {
        # Already seconds do not convert;
    }

    $diff = sprintf("%.2f", $diff);

    return $diff;
}

#is this a num
sub is_number {
    my($n)= @_;

    if ($n) {
        return ($n=~/^\d+$/);
    } else {
        return;
    }
}

#filter html special characters
sub htmlspecialchars {
    my $tmpSTRmsg = $_[0];
    $tmpSTRmsg =~ s/&/&amp;/g;
    $tmpSTRmsg =~ s/\'/&#039;/g;
    $tmpSTRmsg =~ s/\"/&quot;/g;
    $tmpSTRmsg =~ s/</&lt;/g;
    $tmpSTRmsg =~ s/>/&gt;/g;
    return $tmpSTRmsg;
}

sub trim {
    my ($string) = @_;

    if (defined ($string) && $string ne "") {
        $string =~ s/^\s+//;
        $string =~ s/\s+$//;
        return $string;
    } else {
        return "";
    }
}

sub load_db_configs {
    logwriter("Loading settings ...", 4);

    my $sth_sel;
    my $sql = qq{SELECT settingName, settingValue FROM vuln_settings};
    logwriter($sql, 5);
    $sth_sel=$dbh->prepare($sql);
    $sth_sel->execute;

    while (my ($name,$value) = $sth_sel->fetchrow_array) {
        if ($name eq "mailSignature") {
            $value =~ s/&lt;br&gt;/\n/g;
        }

        if ($name ne "") {
            $CONFIG{$name}=$value;
        }
    }

    $sth_sel->finish;
}

#safe write code to help prevent complete job failure
sub safe_db_write {
    my ($sql_insert, $specified_level) = @_;

    logwriter($sql_insert, $specified_level);

    eval {
        $dbh->do($sql_insert);
    };
    warn "[$$] FAILED - $sql_insert\n" . $dbh->errstr . "\n\n" if ($@);

    if ($@) {
        return 0;
    }
}


#routine to do log writing
sub logwriter {
    my ($message, $specified_level) = @_;

    if (!defined($specified_level) || $specified_level eq "") {
        $specified_level = 5;
    }

    my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
    $year+=1900;
    $mon++;
    my $now = sprintf("%04d-%02d-%02d %02d:%02d:%02d",$year, $mon, $mday, $hour, $min, $sec);

    $message = "$now [$$] $loginfo{$specified_level} $message";

    if(!defined($log_level)) {
        $log_level = 0;
    }

    if ($debug || $log_level ge $specified_level)  {
        print $message ."\n";
    }
}

#connect to db
sub conn_db {
    $dbh = DBI->connect("$CONFIG{'DATABASEDSN'}:$CONFIG{'DATABASENAME'}:$CONFIG{'DATABASEHOST'}",
        "$CONFIG{'DATABASEUSER'}","$CONFIG{'DATABASEPASSWORD'}", {
            PrintError => 0,
            RaiseError => 1,
            AutoCommit => 1 }) or die("Failed to connect : $DBI::errstr\n");

    my $sql = qq{SET SESSION time_zone='+00:00'};

    safe_db_write($sql, 5);
    return $dbh;
}

#disconnect from db
sub disconn_db {
    my ($dbh) = @_;
    $dbh->disconnect or die("Failed to disconnect : $DBI::errstr\n");
}

sub maintenance {
    my ($sth_sel, $sql, $sth_seli, $sqli);
    my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
    $year+=1900;
    $mon++;

    if ($min==0 && $hour==4) {
        # maintenance jobs
        logwriter("Maintenance Jobs", 4);
        $sql = qq{SELECT id, status, scan_NEXT, scan_START FROM vuln_jobs};
        $sth_sel = $dbh->prepare($sql);
        $sth_sel->execute;
        while(my ($job_id, $status, $scan_next, $scan_start)=$sth_sel->fetchrow_array) {
            if($status eq "R" && !$scan_next && datediff($scan_start, $year."-".$mon."-".$mday." ".$hour.":".$min.":".$sec, 'H')>=48){
                $sqli = qq{UPDATE vuln_jobs SET status='T' WHERE id='$job_id'};
                logwriter($sql, 5);
                $sth_seli = $dbh->prepare($sqli);
                $sth_seli->execute();
            }
        }

        $sth_sel->finish;

        update_scan_status();
        remove_dup_hosts();
    }
}

sub update_scan_status {
    logwriter("Begin - Update Scan Status", 4);

    my $sql;
    my $time_start = time();
    my $max_age = "-1";

    if (is_number($CONFIG{'maxScanAge'}) && $CONFIG{'maxScanAge'} >= 7) {
        $max_age = $CONFIG{'maxScanAge'};
        logwriter("MAX_AGE=$max_age", 5);
    }

    if ($max_age > 0) {
        my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(($time_start - (86400 * $max_age)));
        $year+=1900;
        $mon++;

        my $maxage_date = sprintf("%04d%02d%02d%02d%02d%02d",$year, $mon, $mday, $hour, $min, $sec);
        logwriter("Marking hosts pending scan with no recent scan as of date $maxage_date", 4);

        $sql = qq{UPDATE vuln_hosts SET scanstate='Pending Scan' WHERE lastscandate <= '$maxage_date' AND inactive=0};
        safe_db_write($sql, 5);
    }

    my $time_run = time() - $time_start;
    logwriter("Finish - Update Scan Status [ Process took $time_run seconds ]", 4);
}

sub remove_dup_hosts {
    my ($sql, $sth_sel, $sth_sel2);
    logwriter("Removing duplicate hosts ...", 4);
    my $time_start = time();

    $sql = qq{SELECT id, hostname FROM vuln_hosts WHERE inactive = '0'};

    $sth_sel = $dbh->prepare($sql);
    $sth_sel->execute;

    while(my($host_id, $hostname) = $sth_sel->fetchrow_array) {
        if ($hostname =~ /\./) {
            #print "valid fqdn [$hostname]\n";
        } else {
            $sql = qq{SELECT count(hostname) FROM vuln_hosts WHERE hostname LIKE '%$hostname%' AND inactive = '0'};
            $sth_sel2 = $dbh->prepare($sql);
            $sth_sel2->execute;
            my ($count) = $sth_sel2->fetchrow_array ();

            if ($hostname ne "" && $count > 1) {
                logwriter("Removing duplicate host: [ $hostname ]", 4);

                $sql = qq{DELETE FROM vuln_hosts WHERE id='$host_id'};
                safe_db_write($sql, 5);
            }

            $sth_sel2->finish;
        }
    }

    $sth_sel->finish;

    my $time_run = time() - $time_start;
    logwriter("Duplicate hosts removed [ Process took $time_run seconds ]", 4);
}

sub ip_in_net {
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

sub calc_priority {
    my $desc = shift;
    my $priority = 1;

    if($desc =~ m/cvss base score\s*:\s*(\d+\.\d+)/i) {
        $priority = int($1);

        $priority = sprintf('%.1f', $1);
        #Round priority
        $priority = int($priority + .5 * ($priority <=> 0));
    }

    $priority = 1 if ($priority<1);

    return $priority;
}

sub update_ossim_incidents {
    my $host_ip  = shift;
    my $ctx      = shift;
    my $port     = shift;
    my $risk     = shift;
    my $desc     = shift;
    my $scan_id  = shift;
    my $username = shift;
    my $sid      = shift;

    my ($sql_inc, $sth_inc, $sql_email, $sql_ticket);
    my $id_pending = 65001;
    my $id_false_positive = 65002;
    my $vuln_incident_threshold = $gvm_vars{'vulnerability_incident_threshold'};
    my $incident_id = 0;
    my $status = '';
    my $i_descr = '';
    my $ticket_id = 0;
    my $priority = 1;
    my $is_false_positive = FALSE;
    my $email_nt = 'CREATE_INCIDENT';
    my $user_in_charge = $username;

    logwriter("Checking incident ticket ...", 4);

    #The incidents created will be equal or greater than the risk indicated
    $risk = 9 - int($risk); # convert into ossim risk

    logwriter("\tIncident Threshold: $vuln_incident_threshold", 4);
    logwriter("\tVulnerability Risk: $risk", 4);

    return if ($vuln_incident_threshold >= $risk);

    #Check if the vulnerability is already created
    $sql_inc = qq{SELECT iv.incident_id, iv.description, i.status, i.priority, i.in_charge FROM incident_vulns iv, incident i
        WHERE iv.incident_id = i.id
        AND iv.ip = '$host_ip' AND iv.ctx = UNHEX('$ctx') AND iv.port = '$port' AND iv.nessus_id = '$scan_id'};

    $sth_inc = $dbh->prepare($sql_inc);
    $sth_inc->execute();
    ($incident_id, $i_descr, $status, $priority, $user_in_charge) = $sth_inc->fetchrow_array;
    $sth_inc->finish;

    #Get latest ticket id
    $ticket_id = genID("incident_ticket_seq");

    #Sanity check
    $desc =~ s/\"/\'/g;
    $desc =~ s/^ *| *$//g;
    $desc =~ s/^[\n\r\t]*//g;
    $desc = quotemeta($desc);

    my $profile_name = get_config_name($sid);
    $desc .= "\n\nProfile Name: $profile_name";

    if (no_empty($incident_id) && $status ne 'Closed') {
        logwriter("Updating incident ticket $incident_id ...", 4);

        $sql_inc = qq{UPDATE incident SET last_update = UTC_TIMESTAMP() WHERE id = '$incident_id'};
        safe_db_write($sql_inc, 5);

        $sql_inc = qq{SELECT incident_id FROM incident_tag WHERE incident_tag.incident_id = '$incident_id' AND incident_tag.tag_id = '$id_false_positive'};
        logwriter($sql_inc, 5);

        $sth_inc = $dbh->prepare($sql_inc);
        $sth_inc->execute();
        my ($hash_false_incident) = $sth_inc->fetchrow_array;
        $sth_inc->finish;

        if (empty($hash_false_incident)) {
            # Compare old user in charge with the new one
            if ($user_in_charge ne $username) {
                $email_nt = 'UPDATE_INCIDENT';
            }

            #Description has been changed ...
            if ($i_descr !~ /$desc/){
                $email_nt = 'UPDATE_TICKET';

                $sql_inc = qq{UPDATE incident_vulns SET description = \"$desc\", risk = '$risk' WHERE incident_id = '$incident_id'};
                safe_db_write($sql_inc, 5);

                #Create incident ticket
                $desc = "Description updated automatically:\n\n $desc";
                $desc =~ s/\n/\n<br>/g;

                $sql_ticket = qq { INSERT INTO incident_ticket (id, incident_id, date, status, priority, users, in_charge, description) values ('$ticket_id', '$incident_id', UTC_TIMESTAMP(), '$status', '$priority', '$username', '$username', \"$desc\")};
                safe_db_write($sql_ticket, 5);
            }
        } else {
            $is_false_positive = TRUE;
        }
    }
    else {
        logwriter("Creating new incident ticket ...", 4);
        $sql_inc = qq{SELECT name FROM plugin_sid where plugin_id = 3001 and sid = '$scan_id'};
        $sth_inc = $dbh->prepare($sql_inc);
        $sth_inc->execute();
        my $name_psid = $sth_inc->fetchrow_array;
        $sth_inc->finish;

        my $vuln_name = "";
        if (no_empty($name_psid)) {
            $vuln_name = $name_psid;
            $vuln_name =~ s/^nessus\s*:\s*/Vulnerability - /g;
        }
        else{
            $vuln_name = "Vulnerability - Unknown detail";
        }

        $priority = calc_priority($desc);

        $sql_inc = qq{INSERT INTO incident(uuid, ctx, title, date, ref, type_id, priority, status, last_update, in_charge, submitter, event_start, event_end)
                        VALUES(UNHEX(REPLACE(UUID(), '-', '')), UNHEX('$ctx'), "$vuln_name", UTC_TIMESTAMP(), 'Vulnerability', 'Vulnerability', '$priority', 'Open', UTC_TIMESTAMP(), 'admin', 'gvm', '0000-00-00 00:00:00', '0000-00-00 00:00:00')};
        safe_db_write($sql_inc, 5);
        $sql_inc = qq{SELECT MAX(id) id from incident};
        $sth_inc = $dbh->prepare($sql_inc);
        $sth_inc->execute();
        $incident_id = $sth_inc->fetchrow_array;
        $sth_inc->finish;

        $sql_inc = qq{INSERT INTO incident_vulns(id, incident_id, ip, ctx, port, nessus_id, risk, description) VALUES('$ticket_id', '$incident_id', '$host_ip', UNHEX('$ctx'), '$port', '$scan_id', '$risk', \"$desc\")};
        safe_db_write($sql_inc, 5);

        $sql_inc = qq{INSERT INTO incident_tag(tag_id, incident_id) VALUES($id_pending, '$incident_id')};
        safe_db_write($sql_inc, 5);

        #Create incident ticket
        $sql_ticket = qq { INSERT INTO incident_ticket (id, incident_id, date, status, priority, users, in_charge, description) values ('$ticket_id', '$incident_id', UTC_TIMESTAMP(), 'Open', '$priority', 'admin', 'admin', 'Incident opened automatically')};
        safe_db_write($sql_ticket, 5);
    }

    if ($is_false_positive == FALSE) {
        # Email notification
        $sql_email = qq {REPLACE INTO incident_subscrip (login, incident_id) VALUES ('$username', '$incident_id')};
        safe_db_write($sql_email, 5);

        $sql_email = qq {REPLACE INTO incident_tmp_email (incident_id, ticket_id, type, subscribers) VALUES ('$incident_id', '$ticket_id', '$email_nt', '$username')};
        safe_db_write($sql_email, 5);
    }
}

sub genID {
    my $table = shift;
    my $sth_lastid;

    my $sql_genID = qq {UPDATE $table SET id=LAST_INSERT_ID(id+1) };
    safe_db_write($sql_genID, 5);

    my $last_id_query = qq{SELECT LAST_INSERT_ID() as lastid};
    $sth_lastid = $dbh->prepare($last_id_query);
    $sth_lastid->execute;
    my ($last_id) = $sth_lastid->fetchrow_array;
    $sth_lastid->finish;
    return $last_id;
}

sub get_server_data {
    my $sensor_id = shift;
    my $sql = qq{SELECT inet6_ntoa(s.ip)
    			 FROM vuln_nessus_servers vns, sensor s
    			 WHERE vns.hostname='$sensor_id' AND HEX(s.id) = UPPER(vns.hostname)};
    my $sthss=$dbh->prepare($sql);
    $sthss->execute;
    my @datass=$sthss->fetchrow_array;

    if (scalar(@datass) == 0) {
        @datass = ('', '', '', '', '');
    }
    $sthss->finish;

    return @datass;
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
    my $user    = shift;
    my $sensor = shift;
    my @test_hosts = split /\n/, $targets;
    my $result = "";
    my @hosts_alive=();
    $hosts_alive[0] = "";
    my $hn = "";

    $hn = join (" ",@test_hosts);

    my $cmd = qq{/usr/bin/php /usr/share/ossim/scripts/vulnmeter/remote_nmap.php '$hn' '$sensor' '$user' 'ping' '1'};
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

sub DottedQuadToLong {
    return unpack('N', (pack 'C4', split(/\./, shift)));
}

sub LongToDottedQuad {
    return join('.', unpack('C4', pack('N', shift)));
}

sub generate_target_id {
    my $input = $_[0];
    my (%credentials) = %{$_[1]};
    my $job_id = $_[2];
    my ($port_excludes)  = $_[3];
    my $sensor_id =  $_[4];

    my @sorted_hosts = ();
    my ($xml);

    my @value = sort(split(/\n/, $input));
    my @chunk = (0,0);
    my $val;
    my $long;
    my $x;
    my $y;

    foreach $val (@value) {
        if ($val =~ m/^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$/i) {
            $long =  DottedQuadToLong($val);
            if ($chunk[0] == 0) {
                @chunk[0] = $long;
                next;
            }

            if ($long == $chunk[0]+1 || $long == $chunk[1]+1) {
                @chunk[1] = $long;
                next;
            }

            if ($chunk[0] != 0) {
                $x = LongToDottedQuad($chunk[0]);

                if ($chunk[1] != 0) {
                    $y = LongToDottedQuad($chunk[1]);
                    push(@sorted_hosts,"$x-$y");
                } else {
                    push(@sorted_hosts,"$x");
                }

                @chunk[0] = $long;
                @chunk[1] = 0;
            }
        } else {
            push(@sorted_hosts,$val);
        }
    }

    if ($chunk[0] != 0) {
        $x = LongToDottedQuad($chunk[0]);

        if ($chunk[1] != 0) {
            $y = LongToDottedQuad($chunk[1]);
            push(@sorted_hosts,"$x-$y");
        } else {
            push(@sorted_hosts,"$x");
        }
        @chunk[0] = $long;
        @chunk[1] = 0;
    }

    my $ls_credentials = "";

    if(no_empty($credentials{'smb_credential'}) && $credentials{'smb_credential'} =~ /^\w+\-\w+\-\w+\-\w+\-\w+$/) {
        $ls_credentials .= "<smb_credential id='".$credentials{'smb_credential'}."'/>";
    }

    if(no_empty($credentials{'ssh_credential'}) && $credentials{'ssh_credential'} =~ /^\w+\-\w+\-\w+\-\w+\-\w+$/) {
        $ls_credentials .= "<ssh_credential id='".$credentials{'ssh_credential'}."'> <port>".$credentials{'ssh_credential_port'}."</port> </ssh_credential>";
    }
    logwriter($ls_credentials, 4);

    #All ports will be scanned by default
    my $ports ="<port_range>1-65535</port_range>";
    if ($port_excludes ne ""){
        $ports = "<port_range>".invert_port_ranges($port_excludes)."</port_range>";
    }

    logwriter("<create_target><name>target$$</name><hosts>".join(",", @sorted_hosts)."</hosts>".$ls_credentials.$ports."</create_target>", 4);
    $xml = execute_gvm_command(
        "<create_target><name>target$$</name><hosts>".join(",", @sorted_hosts)."</hosts>".$ls_credentials.$ports."</create_target>"
        , $sensor_id, $job_id);
    return $xml->{'id'};
}

sub get_gvm_credentials {
    my $sensor_ip = shift;
    my $target_id = shift;

    my ($xml, %credentials, $status, $xml_output);

    %credentials = ();

    if (no_empty($target_id)) {
        ($status, $xml, $xml_output) = process_gvm_request_by_sensor("<get_targets target_id='$target_id'/>", $sensor_ip);

        if (no_empty($xml) && $xml->{'status_text'} =~ /OK/) {
            if (no_empty($xml->{'target'}->{'ssh_credential'}->{'id'})){
                $credentials{'ssh_credential'} = $xml->{'target'}->{'ssh_credential'}->{'id'};
            }

            if (no_empty($xml->{'target'}->{'smb_credential'}->{'id'})){
                $credentials{'smb_credential'} = $xml->{'target'}->{'smb_credential'}->{'id'};
            }
        } else {
            logwriter("Unable to retrieve credentials for target $target_id", 4);
        }
    }

    return (%credentials);
}

sub get_target_id{
    my $sensor_ip = shift;
    my $task_id = shift;

    my ($xml, $target_id, $status, $xml_output);

    $target_id = '';

    if (no_empty($task_id)) {
        ($status, $xml, $xml_output) = process_gvm_request_by_sensor("<get_tasks task_id='$task_id'/>", $sensor_ip);

        if (no_empty($xml) && $xml->{'status_text'} =~ /OK/) {
            if (no_empty($xml->{'task'}->{'target'}->{'id'})){
                $target_id = $xml->{'task'}->{'target'}->{'id'};
            }
        } else {
            logwriter("Unable to retrieve target ID for task $task_id", 3);
        }
    }

    return $target_id;
}

sub get_task_id{
    my $job_id = shift;
    my ($sql, $sthse, $task_id);

    $sql = qq{SELECT task_id FROM vuln_jobs WHERE id='$job_id'};
    $sthse = $dbh->prepare($sql);
    $sthse->execute;

    $task_id = $sthse->fetchrow_array;
    $sthse->finish;

    return $task_id;
}

sub get_scan_time {
    my $job_id = shift;
    my ($sql, $sthse, $diff);

    $sql = qq{SELECT TIME_TO_SEC(TIMEDIFF(scan_END, scan_START)) as diff from vuln_jobs where id='$job_id'};
    $sthse = $dbh->prepare($sql);
    $sthse->execute;

    $diff = $sthse->fetchrow_array;
    $sthse->finish;

    return int($diff);
}

sub delete_scan_items {
    my $sensor_id = shift;
    my $job_id = shift;
    my ($task_id, $target_id, %credentials, $sensor_ip, $xml, $status, $xml_output);

    $sensor_ip = get_gvm_sensor_ip($sensor_id, $job_id);

    $task_id = get_task_id($job_id);
    $target_id = get_target_id($sensor_ip, $task_id);
    %credentials = get_gvm_credentials($sensor_ip, $target_id);

    # Deletion order is very important

    if ($delete_task == TRUE && no_empty($task_id)) {
        ($status, $xml, $xml_output) = process_gvm_request_by_sensor("<delete_task task_id='$task_id' />", $sensor_ip);

        if (no_empty($xml) && $xml->{'status_text'} =~ /OK/) {
            logwriter("Deleting task $task_id from sensor $sensor_ip", 4);
        } else {
            logwriter("Unable to delete task $task_id from sensor $sensor_ip", 3);
        }
    }

    if ($delete_target == TRUE && no_empty($target_id)) {
        ($status, $xml, $xml_output) = process_gvm_request_by_sensor("<delete_target target_id='$target_id' />", $sensor_ip);

        if (no_empty($xml) && $xml->{'status_text'} =~ /OK/) {
            logwriter("Deleting target $target_id from sensor $sensor_ip", 4);
        } else {
            logwriter("Unable to delete target $target_id from sensor $sensor_ip", 3);
        }
    }

    if ($delete_credentials == TRUE && no_empty(%credentials)) {
        foreach my $tcred (keys %credentials) {
            ($status, $xml, $xml_output) = process_gvm_request_by_sensor("<delete_credential credential_id='".$credentials{$tcred}."'/>", $sensor_ip);

            if (no_empty($xml) && $xml->{'status_text'} =~ /OK/) {
                logwriter("Deleting credential ".$credentials{$tcred}." from sensor $sensor_ip", 4);
            } else {
                logwriter("Unable to delete credential ".$credentials{$tcred}." from sensor $sensor_ip", 3);
            }
        }
    }
}

sub invert_port_ranges {
    my $str = shift;
    my @ports;
    $ports[0][0] = 1;
    $ports[0][1] = 65535;
    if ($str) {
        my @ports_exclude = split /,/, $str;
        foreach my $port_exclude (@ports_exclude) {
            my @val = ($port_exclude,$port_exclude);
            if (index($port_exclude, "-") != -1) {
                @val = split /-/, $port_exclude;
            }

            foreach my $index (0 .. $#ports) {
                if ($val[0] <= $ports[$index][0] && $val[1] >= $ports[$index][1]) {
                    delete $ports[$index];
                    next;
                }
                if ($val[0] <= $ports[$index][0] && $val[1] >= $ports[$index][0]) {
                    $ports[$index][0] = $val[1]+1;
                }
                if ($val[0] <= $ports[$index][1] && $val[1] >= $ports[$index][1]) {
                    $ports[$index][1] = $val[0]-1;
                }
                if ($val[0] >= $ports[$index][0] && $val[1] <= $ports[$index][1]) {
                    my $len = $#ports+1;
                    $ports[$len][0] = $val[1]+1;
                    $ports[$len][1] = $ports[$index][1];
                    $ports[$index][1] = $val[0]-1;
                }
            }
        }
    }
    my @result = ();
    foreach my $index (0 .. $#ports) {
        my $val;
        if ($ports[$index][0] == $ports[$index][1]) {
            $val = $ports[$index][0];
        } else {
            $val = "$ports[$index][0]-$ports[$index][1]";
        }
        push @result, $val;
    }
    return  join ',', @result;
}

sub get_config_id {
    my $sid = shift;
    my $job_id = shift;

    my $sql = qq{
        SELECT sensor_gvm_config_id
        FROM vuln_nessus_settings_sensor vnss
            INNER JOIN vuln_jobs vj on vj.notify = HEX(vnss.sensor_id)
        WHERE vj.id='$job_id' and vns_id='$sid'};
    my $sthse = $dbh->prepare($sql);
    $sthse->execute;

    my ($sensor_gvm_config_id) = $sthse->fetchrow_array;
    $sthse->finish;

    if(!defined($sensor_gvm_config_id)) {
        logwriter("No configs ($sid) in sensor set for the job($job_id) found ... Searching for 'Full and fast ultimate'", 4);
        my $sql = qq{
            SELECT sensor_gvm_config_id
            FROM vuln_nessus_settings_sensor vnss
                INNER JOIN vuln_nessus_settings vns ON vnss.vns_id = vns.id
            WHERE name='Full and fast ultimate'};
        my $sthse = $dbh->prepare($sql);
        $sthse->execute;
        $sensor_gvm_config_id = $sthse->fetchrow_array;
    }

    if(!defined($sensor_gvm_config_id)) {
        logwriter("No configs ($sid or 'Full and fast ultimate') found in sensor  set for the job($job_id)", 2);
    }

    return $sensor_gvm_config_id;
}

sub get_config_name {
    my $sid = shift;

    my $sql = qq{SELECT name FROM vuln_nessus_settings WHERE id='$sid'};
    my $sthse = $dbh->prepare($sql);
    $sthse->execute;

    my $profile_name = $sthse->fetchrow_array;
    $sthse->finish;

    if (empty($profile_name)) {
        $profile_name = 'Unknown';
    }

    return $profile_name;
}

sub create_task {
    my $jobname = shift;
    my $config_id = shift;
    my $target_id = shift;
    my $job_id = shift;
    my $sensor_id = shift;

    $jobname = trim(encode_base64($jobname));

    my $xml = execute_gvm_command("<create_task><name>$jobname</name><config id='$config_id'></config><target id='$target_id'></target></create_task>", $sensor_id, $job_id);

    return $xml->{'id'};
}

sub play_task {
    my $sensor_id = shift;
    my $task_id = shift;
    my $job_id = shift;

    execute_gvm_command("<start_task task_id='$task_id' />", $sensor_id, $job_id);
}

sub resume_task {
    my $sensor_id = shift;
    my $task_id = shift;
    my $job_id = shift;

    execute_gvm_command("<resume_task task_id='$task_id' />", $sensor_id, $job_id);
}

sub stop_task {
    my $sensor_id = shift;
    my $task_id = shift;
    my $job_id = shift;
    my ($status, $info_status, @arr_status, $sensor_ip, $retry, $max_retries, $sleep);

    $retry = 0;
    $max_retries = 5;
    $sleep = 10;

    $sensor_ip = get_gvm_sensor_ip($sensor_id, $job_id);
    $info_status = get_task_status($task_id, $sensor_id, $job_id);
    @arr_status = split /\|/, $info_status;
    $status = shift(@arr_status);

    if ($status eq 'Running'){
        process_gvm_request_by_sensor("<stop_task task_id='$task_id' />", $sensor_ip);
        sleep($sleep);

        do {
            $info_status = get_task_status($task_id, $sensor_id, $job_id);
            @arr_status = split /\|/, $info_status;
            $status = shift(@arr_status);

            if ($status ne 'Stopped') {
                $retry += 1;
                sleep($sleep);
            }
        } while ($status ne 'Stopped' && $retry < $max_retries);

        if ($status ne 'Stopped') {
            logwriter("Task $task_id cannot be stopped properly", 3);
        }
    }
}

sub get_task_status {
    my ($task_id, $sensor_id, $job_id) = @_;
    my($xml, @items, $sensor_ip, $status, $xml_output);

    $sensor_ip = get_gvm_sensor_ip($sensor_id, $job_id);
    ($status, $xml, $xml_output) = process_gvm_request_by_sensor("<get_tasks task_id='$task_id'/>", $sensor_ip);

    if (no_empty($xml)) {
        if ($xml->{'status_text'} =~ /Failed to find task/) {
            return "Not Found|-1"
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
    } else {
        return "Not Found|-1";
    }
}

sub print_scan_slots {
    my ($sensor_id) = @_;
    my($max_scans, $current_scans, $remaining_scans);

    ($max_scans, $current_scans) = get_scan_slots($sensor_id);
    $remaining_scans = $max_scans - $current_scans;

    logwriter("\tMax scans: $max_scans", 4);
    logwriter("\tCurrent scans: $current_scans", 4);
    logwriter("\tRemaining scans: $remaining_scans", 4);
}

sub get_scan_slots{
    my ($sensor_id) = @_;

    my($sql, $sth_sel, $max_scans, $current_scans);

    $sql = qq{SELECT max_scans, current_scans FROM vuln_nessus_servers WHERE hostname='$sensor_id'};
    $sth_sel = $dbh->prepare($sql);
    $sth_sel->execute;
    logwriter($sql, 5);

    ($max_scans, $current_scans) = $sth_sel->fetchrow_array;

    return ($max_scans, $current_scans);
}

sub free_scan_slot{
    my $sensor_id = shift;

    my($sql, $max_scans, $current_scans, $remaining_scans);

    $sql = qq{UPDATE vuln_nessus_servers SET current_scans=current_scans-$server_slot WHERE hostname='$sensor_id' AND current_scans>=$server_slot};
    safe_db_write($sql, 4);

    ($max_scans, $current_scans) = get_scan_slots($sensor_id);
    $remaining_scans = $max_scans - $current_scans;

    logwriter("Freeing slot for sensor '$sensor_id' ... Available slots: $remaining_scans", 4);
}

sub book_scan_slot{
    my $sensor_id = shift;

    my($sql, $max_scans, $current_scans, $remaining_scans);

    $sql = qq{UPDATE vuln_nessus_servers SET current_scans=current_scans+$server_slot WHERE hostname='$sensor_id'};
    safe_db_write($sql, 4);

    ($max_scans, $current_scans) = get_scan_slots($sensor_id);
    $remaining_scans = $max_scans - $current_scans;

    logwriter("Booking slot for sensor '$sensor_id' ... Available slots: $remaining_scans", 4);
}

sub set_current_scan_lots{
    my ($sensor_id) = @_;
    my($sql, $sth_sel, $used_slots, $max_scans, $current_scans, $remaining_scans);

    $sql = qq{SELECT count(*) FROM vuln_jobs WHERE status = 'R' and notify = '$sensor_id'};
    $sth_sel = $dbh->prepare($sql);
    $sth_sel->execute;
    ($used_slots) = $sth_sel->fetchrow_array;
    logwriter($sql, 5);

    $sql = qq{UPDATE vuln_nessus_servers SET current_scans=$used_slots WHERE hostname='$sensor_id'};
    safe_db_write($sql, 4);

    ($max_scans, $current_scans) = get_scan_slots($sensor_id);
    $remaining_scans = $max_scans - $current_scans;

    logwriter("Setting scan slots for sensor '$sensor_id' ... Available slots: $remaining_scans", 4);
}

sub is_sensor_ready {
    my ($sensor_id, $job_id) = @_;
    my $is_ready = TRUE;

    my ($sql, $sth_sel, $output, $exit_code, $xml_output, $sensor_ip, $message, $oid);

    #Select a random plugin
    $sql = qq{SELECT oid FROM vuln_nessus_plugins LIMIT 1;};
    $sth_sel = $dbh->prepare($sql);
    $sth_sel->execute;
    $oid = $sth_sel->fetchrow_array;
    $sth_sel->finish;

    my $gvm_cmd = "<get_nvts nvt_oid='$oid'/>";
    my $update_command = "ps -feaww | grep 'python /usr/bin/alienvault58-update.py' | egrep -v 'grep|ps' | wc -l";
    my $ospd_command = "sudo /etc/init.d/ospd-openvas status";

    # Get Sensor IP
    $sensor_ip = get_gvm_sensor_ip($sensor_id, $job_id);

    # Set default message and status
    $message = "Sensor $sensor_ip is ready";
    $output = "";

    logwriter("Checking status for sensor $sensor_ip ...", 4);

    # Check if a system update is running
    ($exit_code, $output) = process_system_command_by_sensor($update_command, $sensor_ip);

    if ($exit_code ne 0 || (no_empty($output) && int($output) > 0)) {
        $is_ready = FALSE;
        $message = "Sensor $sensor_ip is not available. Update in progress ...";
    } else {
        # Check if OSPD-OpenVAS is running
        ($exit_code, $output) = process_system_command_by_sensor($ospd_command, $sensor_ip);

        if ($exit_code ne 0 || (no_empty($output) && $output !~ /is running/)) {
            $is_ready = FALSE;
            $message = "Sensor $sensor_ip is not available. GVM services are not running ...";
        } else {
            # Check if GVM is ready (NVTs have been properly loaded)
            ($exit_code, $output, $xml_output) = process_gvm_request_by_sensor($gvm_cmd, $sensor_ip);

            if (no_empty($exit_code) || (no_empty($output) && $output->{'status'} !~ /20\d/)) {
                $is_ready = FALSE;
                $message = "Sensor $sensor_ip is not available. Loading plugins ...";
            }

            unlink $xml_output if -e $xml_output;
        }
    }

    my $l_level = ($is_ready == FALSE) ? 3 : 4;
    logwriter($message, $l_level);

    return ($is_ready, $message);
}

sub process_system_command_by_sensor {
    my ($cmd, $sensor_ip) = @_;

    my $output = "";
    my $exit_code = "";
    my $ssh_parameters = "-q -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -i $cert_file";
    my $ssh_command = "ssh $ssh_parameters "."avapi@"."$sensor_ip $cmd";

    logwriter($ssh_command, 4);

    $output = `$ssh_command`;
    $exit_code = $?;

    return ($exit_code, $output);
}

#Execute a request to GVM API in a given sensor
sub process_gvm_request_by_sensor {
    my ($cmd, $sensor_ip) = @_;
    my ($res, $status, $output);

    my $tmp_val = time();

    my $xml_output = $CONFIG{'ROOT_DIR'}."tmp/tmp_gvm_jobs_$$"."_$tmp_val.xml";
    my $gvm_manager_common = "export PYTHONIOENCODING=utf-8; runuser -u _gvm -- $CONFIG{'GVM_PATH'} tls --hostname $sensor_ip  --certfile $cert_file -X \"${cmd}\" 2>&1";

    logwriter($gvm_manager_common, 4);
    $output = `$gvm_manager_common`;

    open(my $fh, '>', $xml_output) or die "Could not open file '$xml_output' $!";
    print $fh $output;
    close $fh;

    if ($debug_gvm_commands ne "") {
        system ("echo '$gvm_manager_common' >> '$debug_gvm_commands'");
        system ("cat '$xml_output' >> '$debug_gvm_commands'");
        system ("echo '' >> '$debug_gvm_commands'");
    }

    if (-e '/tmp/debug_gvm' && $cmd =~ /get_reports/) {
        system("cp $xml_output /tmp/latest_gvm_results.xml");
    }

    try {
        $res = XMLin($xml_output, keyattr => []);
        $status = "";
    } catch {
        $res = ();
        $status = "1";
    };

    return ($status, $res, $xml_output);
}

sub get_gvm_sensor_ip {
    my ($sensor_id, $job_id) = @_;
    my($sql, $sth_sel);
    my $sensor_ip = "";

    if (no_empty($sensor_id)) {
        $sensor_ip = get_sensor_ip_by_id($sensor_id);
    } else {
        $sensor_ip = $gvm_host;
    }

    if(empty($sensor_ip) && no_empty($job_id)) {
        $sql = qq{SELECT hostname
                  FROM vuln_nessus_servers t1 LEFT JOIN vuln_jobs t2 on t1.hostname = t2.notify
                  WHERE t2.id = '$job_id'};

        $sth_sel = $dbh->prepare($sql);
        $sth_sel->execute;
        $sensor_id = $sth_sel->fetchrow_array;
        $sth_sel->finish;
        $sensor_ip = get_sensor_ip_by_id($sensor_id);
    }

    return $sensor_ip;
}

#Execute a request to GVM API
#Sensor is calculated automatically if empty
#Request is executed several times if failed and error logged into the database
sub execute_gvm_command {
    my ($cmd, $sensor_id, $job_id) = @_;

    my ($sql, $xml_output, $xml, $status, $meth_Wcheck);
    my $retry = 0;
    my $max_retries = 3;
    my $sleep = 20;

    my $sensor_ip = get_gvm_sensor_ip($sensor_id, $job_id);

    #No sensor found
    if (empty($sensor_ip)) {
        $sql = qq{UPDATE vuln_jobs SET meth_Wcheck='No Server found<br />' WHERE id='$job_id'};
        safe_db_write($sql, 5);
    }
    else {
        do {
            ($status, $xml, $xml_output) = process_gvm_request_by_sensor($cmd, $sensor_ip);

            if (no_empty($status)) {
                $retry += 1;
                logwriter("Failure on GVM request: Sleeping $sleep seconds before retrying ...", 1);
                # Update job_id
                if ($job_id > 0) {
                    my $remain = int($max_retries - $retry);
                    if ($remain == 0){
                        $meth_Wcheck = "Scanner request failed<br/>";
                    } else {
                        $meth_Wcheck = "Scanner request failed, will be retried $remain times.<br/>";
                    }

                    $sql = qq{UPDATE vuln_jobs SET meth_Wcheck='$meth_Wcheck' WHERE id='$job_id'};
                    safe_db_write($sql, 2);
                }
                sleep($sleep);
            }

        } while (no_empty($status) && $retry < $max_retries);

        # Reset error failure
        if ($retry < $max_retries && $job_id > 0) {
            $sql = qq{UPDATE vuln_jobs SET meth_Wcheck='Scan Server Selected: $sensor_ip<br />' WHERE id='$job_id'};
            safe_db_write($sql, 5);
        }

        if (no_empty($status)) {
            open(INFO, $xml_output);         # Open the file
            my @log_lines = <INFO>;          # Read it into an array
            close(INFO);                     # Close the file

            my $error = join(" ", @log_lines);
            $error =~ s/\'/\\'/g;

            #Not showing all traceback if services are down
            if ($error =~ /Connection refused/) {
                $error = "GVM services are not running";
            }

            if (no_empty($job_id_to_log)) {
                if($semail eq "1") {
                    send_error_notifications_by_email($job_id_to_log, "GVM: $error");
                }

                $sql = qq{UPDATE vuln_jobs SET status='F', meth_Wcheck=CONCAT(IFNULL(meth_Wcheck,''), '$error<br/>') , scan_END=UTC_TIMESTAMP(), scan_NEXT=NULL WHERE id='$job_id_to_log'}; #MARK FAILED
                safe_db_write($sql, 1);
            }elsif (no_empty($job_id)) {
                if($semail eq "1") {
                    send_error_notifications_by_email($job_id, "GVM: $error");
                }

                $sql = qq{UPDATE vuln_jobs SET status='F', meth_Wcheck='$error Retried $max_retries times.<br/>', scan_END=UTC_TIMESTAMP(), scan_NEXT=NULL WHERE id='$job_id'}; #MARK FAILED
                safe_db_write($sql, 1);
            }

            delete_scan_items($sensor_id, $job_id);
            unlink $xml_output if -e $xml_output;
            die "Can't read XML $xml_output: $error";
        }
        elsif (no_empty($xml) && $xml->{'status'} !~ /20\d/) {
            my $status = $xml->{'status'};
            my $status_text = $xml->{'status_text'};
            $status_text =~ s/\'/\\'/g;

            if (no_empty($job_id_to_log)) {
                if($semail eq "1") {
                    send_error_notifications_by_email($job_id_to_log, "GVM: $status_text");
                }

                $sql = qq{UPDATE vuln_jobs SET status='F', meth_Wcheck=CONCAT(IFNULL(meth_Wcheck,''), '$status_text<br />'), scan_END=UTC_TIMESTAMP(), scan_NEXT=NULL WHERE id='$job_id_to_log'}; #MARK FAILED
                safe_db_write($sql, 1);

                delete_scan_items($sensor_id, $job_id);
                unlink $xml_output if -e $xml_output;
                die "Error: status = $status, status_text = '$status_text'";
            }
        }
    }

    unlink $xml_output if -e $xml_output;
    return $xml;
}

sub get_results_from_xml {
    my $job_id      = $_[0];
    my $task_id     = $_[1];
    my $sensor_id   = $_[2];

    my $total_records = 0;
    my (@items, $host, $service, $oid, $plugin_id, $description, $app, $proto, $port, $risk, @issues, %hostHash, %resultHash, $report_id, $xml, %apps_by_port_protocol, $plugins_info);

    %hostHash = ();
    %resultHash = ();

    %apps_by_port_protocol = load_apps();
    $plugins_info          = load_plugins_info();

    $xml = execute_gvm_command("<get_tasks task_id='$task_id' details='1'/>", $sensor_id, 0);

    # get latest report id
    $report_id = '';

    if (ref($xml->{'task'}{'last_report'})) {
        push(@items,$xml->{'task'}{'last_report'}{'report'});
    } elsif (ref($xml->{'task'}{'current_report'})) {
        push(@items,$xml->{'task'}{'current_report'}{'report'});
    }

    foreach my $report (@items) {
        $report_id = $report->{'id'};
    }

    if (no_empty($report_id)){
        logwriter("Get reports for report_id: $report_id",4);
        $xml = execute_gvm_command("<get_reports report_id='$report_id' details='1' ignore_pagination='1'/>", $sensor_id, $job_id);

        @items = ();

        if (ref($xml->{'report'}{'report'}{'results'}->{'result'}) eq 'ARRAY') {
            @items = @{$xml->{'report'}{'report'}{'results'}->{'result'}};
        } elsif(defined($xml->{'report'}{'report'}{'results'}->{'result'})) {
            push(@items,$xml->{'report'}{'report'}{'results'}->{'result'});
        }

        foreach my $result (@items) {
            if (ref($result->{"host"}) eq 'HASH') {
                $host = trim($result->{"host"}->{"content"});
            } else {
                $host = $result->{"host"};
            }

            logwriter("Save results for $host", 4);

            #Service Info
            $service = $result->{"port"};

            if ($service =~ /general/) {
                my @temp = split /\//, $service;
                $app = "general";
                $proto = $temp[1];
                $port = "";
            }
            elsif ($service =~ /^\d+\//)
            {
                my @temp2 = split /\//, $service;
                $app = (defined($apps_by_port_protocol{$service}) ? $apps_by_port_protocol{$service} : 'unknown');
                $port = $temp2[0];
                $proto = $temp2[1];
            }
            else {
                my @temp = split /\s/, $service;
                $app = $temp[0];
                $temp[1] =~ s/\(//;
                $temp[1] =~ s/\)//;
                my @temp2 = split /\//, $temp[1];
                $port = $temp2[0];
                $proto = $temp2[1];
            }

            #Get service string (app, port and protocol)
            if (empty($port) && empty($proto)) {
                $service = $app;
            }
            elsif (empty($port)) {
                $service = "$app ($proto)";
            }
            elsif (empty($proto)){
                $service = "$app ($port)";
            } else {
                $service = "$app ($port/$proto)";
            }

            $service =~ s/(\\n)+$//;
            $service = htmlspecialchars($service);

            $oid = $result->{"nvt"}->{"oid"};
            $plugin_id = $plugins_info->{$oid}->{'id'};

            $risk = $plugins_info->{$oid}->{'risk'};

            $description = get_description_from_xml($result->{"nvt"}->{"tags"}, $result->{"description"}, $plugins_info->{$oid});

            if ($description) {
                my $temp = {
                    Host        => $host,
                    Description => $description,
                    Service     => $service,
                    App         => $app,
                    Port        => $port,
                    Proto       => $proto,
                    ScanID      => $plugin_id,
                    Risk        => $risk,
                    RecordType  => 'N'
                };

                logwriter ("my temp = { Host=>$host, Description=>$description, Service=>$service, App=>$app, Port=>$port, Proto=>$proto, ScanID=>$plugin_id, Risk=>$risk};\n", 5);

                if (!exists($resultHash{$port}{$host}{lc $description}{lc $app}{lc $proto}{$plugin_id}{lc $risk}))
                {
                    push (@issues, $temp);
                    $resultHash{$port}{$host}{lc $description}{lc $app}{lc $proto}{$plugin_id}{lc $risk}++;
                    $total_records += 1;
                }
            }
        }
    }

    if ($total_records eq 0) {
        $no_results = TRUE;
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

sub get_asset_by_name {
    my ($hostname) = @_;
    my ($sql, $sth_sel, $cmd);
    my ($ip) = "";

    my $resolv = TRUE;

    $sql = qq{SELECT inet6_ntoa(hip.ip) AS ip
                        FROM host h, host_ip hip, vuln_nessus_latest_reports vnlr
                        WHERE h.id = hip.host_id
                        AND h.hostname = '$hostname'
                        AND vnlr.hostIP = inet6_ntoa(hip.ip)};

    $sth_sel = $dbh->prepare($sql);
    $sth_sel->execute;
    $ip = $sth_sel->fetchrow_array;

    if(!defined($ip)) {
        $sql = qq{SELECT inet6_ntoa(hip.ip) as ip
                            FROM host h, host_ip hip
                            WHERE h.id=hip.host_id
                            and h.hostname = '$hostname'};

        $sth_sel = $dbh->prepare($sql);
        $sth_sel->execute;
        $ip = $sth_sel->fetchrow_array;
    }
    $sth_sel->finish;

    if(!defined($ip)) {
        $ip = "";
    }

    if ($ip ne "") {
        return $ip;
    }

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
    logwriter("Checking previous scans ...", 4);
    my ($sql, $sthse, $running, $completed, $status, $now, $info_status, @arr_status, $scantime, $cpid, $wait, $job_status);

    $now = strftime "%Y-%m-%d %H:%M:%S", gmtime;

    $sql = qq{SELECT meth_TARGET, id, scan_PID, task_id, notify, name, job_TYPE, username, profile_id FROM vuln_jobs WHERE status='R' AND UNIX_TIMESTAMP('$now') - UNIX_TIMESTAMP(scan_START) > 3600};

    $sthse=$dbh->prepare($sql);
    $sthse->execute;
    while (my($targets, $job_id, $scan_pid, $task_id, $sensor_id, $job_title, $Jtype, $juser, $profile_id) = $sthse->fetchrow_array()) {
        $scantime = getCurrentDateTime();

        $running   = 0;
        $completed = 0;
        $wait      = 0;

        $info_status = get_task_status($task_id, $sensor_id, $job_id);
        @arr_status = split /\|/, $info_status;
        $status = shift(@arr_status);

        $cpid = `ps -eo pid,cmd | grep nessus_job | grep -v grep | grep $scan_pid`;

        chomp($cpid); # search job pid

        if ($status eq "New" || $status eq "Running" || $status eq "Requested" || $status eq "Queued") {
            $running = 1;
        }
        elsif ($status eq "Done" || $status eq "Stop Requested" || $status eq "Stopped" || $status eq "Interrupted" || $status eq "Not Found") {
            if($cpid eq "") {
                $completed = 1;
            }
            else {
                $wait = 1;
            }
        }


        if ($wait==0) {
            if($completed==1) {
                logwriter("Script finished unexpectedly ...", 4);

                my (@issues, %hostHash);

                # load ctx info
                get_asset_data($targets, $job_id);

                @issues = get_results_from_xml($job_id, $task_id, $sensor_id);

                #PROCESS RESULTS INTO HOST HAS ARRAY FOR IMPORT
                %hostHash = pop_hosthash(\@issues);

                #FREE RESOURCES FROM ORIGINAL RESULTS ARRAY
                undef (@issues);

                if ($status eq "Done"){
                    $job_status = 'C';
                } elsif($status eq "Stop Requested" || $status eq "Stopped"){
                    $job_status = 'I';
                } else{
                    $job_status = 'F';
                }

                if(process_results(\%hostHash, $job_id, $job_title, $Jtype, $juser, $profile_id, $scantime)) {
                    logwriter("[$job_title] [ $job_id ] Completed SQL Import, scan_PID=$$", 4);
                    $sql = qq{UPDATE vuln_jobs SET status='$job_status', scan_PID=$$, scan_END=UTC_TIMESTAMP(), scan_NEXT=NULL WHERE id='$job_id'};
                }
                else {
                    logwriter("Error when importing orphan job $task_id for server $sensor_id", 4);
                    $sql = qq{UPDATE vuln_jobs SET status='F', scan_END ='$now', meth_Wcheck=CONCAT(IFNULL(meth_Wcheck,''), 'Error when importing orphan job<br />') WHERE id='$job_id'};
                }

                safe_db_write($sql, 5);

                # Delete targets
                if($job_status ne 'I') {
                    delete_scan_items($sensor_id, $job_id);
                }
            }
            elsif($running==0) { # the job is not running in the sensor
                logwriter("Task $task_id ended incorrectly in $sensor_id", 4);
                $sql = qq{UPDATE vuln_jobs SET status='F', scan_END ='$now', meth_Wcheck=CONCAT(IFNULL(meth_Wcheck,''), 'Job task was ended incorrectly<br />') WHERE id='$job_id'};

                safe_db_write($sql, 5);

                # Delete targets
                delete_scan_items($sensor_id, $job_id);
            }
        }
    }

    $sthse->finish;
}

sub get_default_ctx {
    my ($sql, $sthse, $dctx);

    $sql   = qq{SELECT value FROM config WHERE conf='default_context_id'};
    $sthse = $dbh->prepare($sql);
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

    $sql = qq{SELECT IP_ctx FROM vuln_jobs WHERE id='$job_id'};
    $sthse = $dbh->prepare($sql);
    $sthse->execute;

    my $ctx_ip  = $sthse->fetchrow_array;

    my @lines = split(/\n/, $ctx_ip);

    foreach my $line (@lines) {
        my @data = split(/#/, $line);

        if (scalar(@data) == 2){
            $ctxs_by_ip{$data[1]} = $data[0];
        }
    }

    $sthse->finish;
    return (%ctxs_by_ip);
}

sub load_apps {
    my %apps = ();
    my $sql = qq{SELECT DISTINCT port_number, protocol_name, service FROM port};
    my $sth_sel2 = $dbh->prepare($sql);
    $sth_sel2->execute;
    while (my($port_number, $protocol_name, $service) = $sth_sel2->fetchrow_array()) {
        $apps{$port_number . '/' . $protocol_name} = $service;
    }
    $sth_sel2->finish;

    return (%apps);
}

sub load_plugins_info {
    my %plugins_info = ();
    my $sql = qq{SELECT id, oid, name, summary, cve_id, bugtraq_id, xref, enabled, created, modified,
                     category, family, risk, cvss_base_score
              FROM vuln_nessus_plugins};

    my $sth_sel2 = $dbh->prepare($sql);
    $sth_sel2->execute;
    while (my($id, $oid, $name, $summary, $cve_id, $bugtraq_id, $xref, $enabled, $created, $modified,
        $category, $family, $risk, $cvss_base_score) = $sth_sel2->fetchrow_array())
    {
        $plugins_info{$oid} = {
            "id"              => $id,
            "oid"             => $oid,
            "name"            => $name,
            "summary"         => $summary,
            "cve_id"          => $cve_id,
            "bugtraq_id"      => $bugtraq_id,
            "xref"            => $xref,
            "enabled"         => $enabled,
            "created"         => $created,
            "modified"        => $modified,
            "category"        => $category,
            "family"          => $family,
            "risk"            => $risk,
            "cvss_base_score" => $cvss_base_score
        };
    }

    $sth_sel2->finish;

    return \%plugins_info;
}

sub get_description_from_xml {
    my ($tags, $description, $plugin_info) = @_;

    my %tokens = (
        'cvss_base_vector' => 'CVSS Base Vector',
        'summary'          => 'Summary',
        'insight'          => 'Insight',
        'affected'         => 'Affected Software/OS',
        'impact'           => 'Impact',
        'solution'         => 'Solution',
        'vuldetect'        => 'Vulnerability Detection Method',
        'overview'         => 'Overview',
        'synopsis'         => 'Synopsis',
        'description'      => 'Description',
        'details'          => 'Details'
    );

    my @output = ();
    my ($cvss_base_score, $html_desc);

    #Set result description
    my @tags_lines = ();
    my @tag_data = ();

    if (ref($description) ne 'HASH' && no_empty($description))
    {
        $description =~ s/[\n\r\t]*$//g;
        push @output, "Vulnerability Detection Result:\n\n" . $description;
    }

    if (ref($tags) ne 'HASH' && no_empty($tags))
    {
        @tags_lines = split(/\|/, $tags);

        if (scalar(@tags_lines) != 0)
        {
            my ($token_key, $token);

            foreach my $tag_line (@tags_lines) {
                @tag_data = split(/=/, $tag_line);
                $token_key = $tag_data[0];

                if (no_empty($tag_data[1]) && exists $tokens{$token_key}) {
                    $token = $tokens{$token_key};

                    $tag_line =~ s/$token_key=/$token:\n\n/;
                    push @output, $tag_line;
                }
            }
        }
    }

    #Set references description
    if (no_empty($plugin_info->{'xref'}) && $plugin_info->{'xref'} ne 'NOXREF') {
        push @output, "References:";

        my @refs = split /,\s/, $plugin_info->{'xref'};

        foreach my $ref (@refs){
            push (@output, $ref);
        }
    }

    #Set CVSS Base Score
    $cvss_base_score = (no_empty($plugin_info->{'cvss_base_score'})) ? $plugin_info->{'cvss_base_score'} : '-';
    push @output, "CVSS Base Score:\n\n" . $cvss_base_score;

    if (scalar(@output) != 0)
    {
        $html_desc = join("\n\n", @output);
        return htmlspecialchars($html_desc);
    }
    else
    {
        return '';
    }
}

sub generate_credentials {
    my $job_id = shift;
    my $sensor_id = shift;

    my ($ssh_credential_id, $smb_credential_id, $sql, $sth_sel, $xml, $ssh_credential, $smb_credential , %credentials, $pid);

    $pid = $$;

    $sql = qq{SELECT credentials, ssh_credential_port FROM vuln_jobs where id='$job_id'};
    $sth_sel=$dbh->prepare($sql);
    $sth_sel->execute;

    my ($credentials, $ssh_credential_port)  = $sth_sel->fetchrow_array;

    $credentials =~ m/((.*)#(.*))?\|((.*)#(.*))?/;

    # SSH credentials ($2 = name and $3 = login)
    if(no_empty($2) && no_empty($3)) {
        $sql = qq{SELECT AES_DECRYPT(UNHEX(value),'$uuid') AS dvalue FROM user_config WHERE category='credentials' AND name='$2' AND login='$3'};
        $sth_sel = $dbh->prepare($sql);
        $sth_sel->execute;

        $ssh_credential  = $sth_sel->fetchrow_array;

        if(no_empty($ssh_credential)) { # Create ssh credentials
            $ssh_credential =~ s/\<create_lsc_credential\>/\<create_credential\>/;
            $ssh_credential =~ s/\<\/create_lsc_credential\>/\<\/create_credential\>/;
            $ssh_credential =~ s/\<name\>/\<name\>$pid/;
            $xml = execute_gvm_command($ssh_credential, $sensor_id, $job_id);

            if(no_empty($xml->{'id'})) {
                $ssh_credential_id = $xml->{'id'};
            }
        }
    }

    #SMB credentials ($5 = name and $6 = login)
    if(no_empty($5) && no_empty($6)) {
        $sql = qq{SELECT AES_DECRYPT(UNHEX(value),'$uuid') AS dvalue FROM user_config WHERE category='credentials' AND name='$5' AND login='$6'};
        $sth_sel = $dbh->prepare($sql);
        $sth_sel->execute;

        $smb_credential  = $sth_sel->fetchrow_array;

        if(no_empty($smb_credential)) { # Create ssh credentials
            $smb_credential =~ s/\<create_lsc_credential\>/\<create_credential\>/;
            $smb_credential =~ s/\<\/create_lsc_credential\>/\<\/create_credential\>/;
            $smb_credential =~ s/\<name\>/\<name\>$pid/;
            $xml = execute_gvm_command($smb_credential, $sensor_id, $job_id);
            if(no_empty($xml->{'id'})) {
                $smb_credential_id = $xml->{'id'};
            }
        }
    }

    # Return credentials
    if(no_empty($ssh_credential_id)) {
        $credentials{'ssh_credential'} = $ssh_credential_id;
        $credentials{'ssh_credential_port'} = $ssh_credential_port;
    }
    if(no_empty($smb_credential_id)) {
        $credentials{'smb_credential'} = $smb_credential_id;
    }

    $sth_sel->finish;
    return (%credentials);
}

sub send_error_notifications_by_email {
    my $job_id  = $_[0];
    my $message = $_[1];

    logwriter("Send email for job_id: $job_id ...", 5);

    my $cmde = qq{/usr/bin/php /usr/share/ossim/scripts/vulnmeter/send_notification.php '$job_id' '$message'};

    open(EMAIL,"$cmde 2>&1 |") or die "failed to fork :$!\n";
    while(<EMAIL>){
        chomp;
        logwriter("send_error_notifications output: $_", 5);
    }
    close EMAIL;
}

sub no_empty {
    my $var  = shift;
    if (defined($var) && $var ne "") {
        return 1;
    }
    else {
        return 0;
    }
}

sub empty {
    my $var  = shift;
    if (defined($var) && $var ne "") {
        return 0;
    }
    else {
        return 1;
    }
}

sub clean_old_gvm_files {
    my $command_output = `find /usr/share/ossim/www/tmp  -mtime +2 -print | grep -P '(omp|gvm)_command.{15}\.xml'`;

    my @output_lines = split(/\n/, $command_output);

    foreach my $line (@output_lines) {
        if (-e $line) {
            unlink($line);
        }
    }

    $command_output = `find /usr/share/ossim/www/tmp  -mtime +2 -print | grep -P '(omp|gvm).{15}\.xml'`;

    @output_lines = split(/\n/, $command_output);

    foreach my $line (@output_lines) {
        if (-e $line) {
            unlink($line);
        }
    }
}

sub fix_vulns_tables {
    my $sql;

    $sql = qq{UPDATE vuln_job_schedule v,sensor s SET v.email=hex(s.id) WHERE v.email=s.name};
    safe_db_write($sql, 5);

    $sql = qq{UPDATE vuln_jobs v,sensor s SET v.notify=hex(s.id) WHERE v.notify=s.name};
    safe_db_write($sql, 5);
}

sub get_asset_data {
    my $host_list = shift;
    my $job_id    = shift;

    #Hash to manage targets: Ctxs and IDs
    my @aux  = split /\n/, $host_list;
    my %ctxs = get_ctxs_by_ip($job_id);

    #Load all ctxs for all assets
    foreach my $ip_in_db (keys %ctxs) {
        $asset_data{$ip_in_db}{'ctx'} = $ctxs{$ip_in_db};
    }

    my $negation = 0;
    foreach my $idip (@aux) {
        if ($idip =~ m/^\!/) {
            $negation = 1;
            last;
        }
    }

    if ($negation) {
        my @new_aux = ();
        my $counter = @aux;
        for (my $i=0;$i<$counter;$i++) {
            my $value = $aux[$i];
            my $val = 0;
            if ($value =~ m/^(!)?([a-f\d]{32})#(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\/\d{1,2})$/i) {
                $val = "$1$3";
            } elsif ($value =~ m/^(!?\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\/\d{1,2})$/i) {
                $val = $1;
            }
            if ($val) {
                #subnet substitution
                my $neg = ((index $val, "!") == 0);
                if ($neg) {
                    $val = substr($val,1);
                }
                my @hosts = build_hostlist($val);
                if ($neg) {
                    @hosts = map { "!".$_ } @hosts;
                }
                push(@new_aux,@hosts);
            } else {
                push(@new_aux,$value);
            }
        }
        @aux = @new_aux;
    }
    my $counter = scalar @aux;
    for (my $i=0;$i<$counter;$i++) {
        my $idip = $aux[$i];

        if (($idip =~ m/^\!/) || (grep {$_ eq "!".$idip } @aux)) {
            next;
        } elsif ($idip =~ m/^([a-f\d]{32})#(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}(\/\d{1,2})?)$/i) {
            #host_id#Ip or net_id#CIDR
            $asset_data{$2}{'id'} = $1;
            push(@asset_to_scan, $2);
        }
        elsif($idip =~ m/^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}(\/\d{1,2})?)$/i) {
            push(@asset_to_scan, $idip);
        }
    }
}
