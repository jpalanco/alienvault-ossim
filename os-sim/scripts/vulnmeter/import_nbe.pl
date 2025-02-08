#!/usr/bin/perl -w
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

use strict;
use warnings;

use Data::Dumper;
use DBI;
use Date::Manip;
use MIME::Base64;
use Net::IP;
use IO::Socket;
use Date::Calc qw(Delta_DHMS Add_Delta_YMD Days_in_Month);
use Switch;
use POSIX qw(strftime);
use HTML::Entities;
use Time::ParseDate;

$| = 1;

#Declare constants
use constant TRUE => 1;
use constant FALSE => 0;

my $file            = $ARGV[0];
my $rdata           = decode_base64($ARGV[1]); # report_name;user
my $asset_insertion = $ARGV[2];
my $tz              = $ARGV[3];
my $ctx             = $ARGV[4];

#Alienvault GVM Scans
my $sid = "00000000000000000000000000000000";

my ($report_name, $user) = split(/;/,$rdata);

my %CONFIG = ();

my $dbhost = `grep ^db_ip= /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`;
chomp($dbhost);
$dbhost = "localhost" if ($dbhost eq "");
my $dbuser = `grep ^user= /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`;
chomp($dbuser);
my $dbpass = `grep ^pass= /etc/ossim/ossim_setup.conf | cut -f 2 -d "="`;
chomp($dbpass);

my $uuid = `/usr/bin/php /usr/share/ossim/scripts/vulnmeter/util.php get_system_uuid`;
chomp($uuid);

$CONFIG{'DATABASENAME'}     = "alienvault";
$CONFIG{'DATABASEHOST'}     = $dbhost;
$CONFIG{'UPDATEPLUGINS'}    = 0;
$CONFIG{'DATABASEDSN'}      = "DBI:mysql";
$CONFIG{'DATABASEUSER'}     = $dbuser;
$CONFIG{'DATABASEPASSWORD'} = $dbpass;
$CONFIG{'nameservers'}      = "";

#GLOBAL VARIABLES
my $debug = 0;
my $log_level = 3;
my $no_results = FALSE;
my $scantime = "";

#Logwriter Risk Values
my %loginfo;
$loginfo{'1'} = "FATAL";
$loginfo{'2'} = "ERROR";
$loginfo{'3'} = "WARN";
$loginfo{'4'} = "INFO";
$loginfo{'5'} = "DEBUG";

#DATABASE HANDLE TO BE USED THROUGHOUT PROGRAM
my ($dbh, $sth_sel, $sql);
my %gvm_vars = ();
$dbh = conn_db();
$sql = qq{ select *,AES_DECRYPT(value,'$uuid') as dvalue from config where conf = 'vulnerability_incident_threshold'};
$sth_sel=$dbh->prepare($sql);
$sth_sel->execute;
while (my ($conf, $value, $dvalue) = $sth_sel->fetchrow_array) {
    if(!defined($dvalue)) {
        $dvalue="";
    }

    $gvm_vars{$conf} = ($dvalue ne "") ? $dvalue : $value;
}
disconn_db($dbh);

main();

exit;

sub main {

    my $dbh = conn_db();
    my @issues = get_results_from_file($file);

    disconn_db($dbh);
    $dbh = conn_db();

    my %hostHash = pop_hosthash(\@issues);

    disconn_db($dbh);
    $dbh = conn_db();

    my $id = process_results(\%hostHash, $report_name, "M", $user, $sid, $scantime, "", $ctx);

    if($id > 0) {
        #It cannot be replaced by the logwriter function because this text is parsed in the UI
        print "Report ID: $id\n";
    }

    disconn_db($dbh);
}

#read in data from results file <- returns (array of hashes) $issues
sub get_results_from_file {
    my ($outfile) = @_;

    if (! -r $outfile) {
        $no_results = TRUE;
        return FALSE;
    }

    my %resultHash = ();

    # default value for scantime
    $scantime = strftime "%Y%m%d%H%M%S", gmtime;

    my @issues;
    my $total_records = 0;

    logwriter("get_results_from_file:Outfile: $outfile", 4);

    open(INPUT,"<$outfile")|| die("Can't open report file");

     while (<INPUT>) {
        # Initialize field values each time to ensure the are clear
        #
        my ($host, $domain, $scan_id, $description, $service, $app, $port, $proto, $rec_type, $risk_type) = "";
        ($rec_type, $domain, $host, $service, $scan_id, $risk_type, $description)=split(/\|/,$_);

        # Validation
        if ($rec_type ne "timestamps") {
            if (defined($domain) && $domain !~ m/^[a-z\-\_\d\.\s]+$/i) {
                next;
            }

            if (defined($host) && $host !~ m/^[a-z\-\_\d\.\s]+$/i) {
                next;
            }

            if (defined($scan_id) && $scan_id !~ m/^[\d\.\s]+$/) {
                next;
            }
        }

        if (defined($service) && $service !~ m/^[a-z\-\_\(\d\)\/\s]+$/i) {
            next;
        }

        if (defined($risk_type) && $risk_type !~ m/^[a-z\-\_\d\s]+$/i) {
            next;
        }

        # to import .nbe from GVM scans

        if(!defined($description)) {
            $description = "";
        }

        if(!defined($service)) {
            $service = "";
        }

        if(!defined($description)) {
            $description = "";
        }

        if ($rec_type =~ /results/) {

            if ($service =~ /\s/) {
                my @temp = split /\s/, $service;

                $app = $temp[0];

                $temp[1] =~ s/\(//;
                $temp[1] =~ s/\)//;
                my @temp2 = split /\//, $temp[1];

                $port = "";
                $proto = "";

                if (scalar(@temp2) == 2) {
                    $port = $temp2[0];
                    $proto = $temp2[1]
                } else {
                    if ($temp2[0] =~ /^\d+\//) {
                        $port = $temp2[0];
                        $proto = "";
                    } else {
                        $port = "";
                        $proto = $temp2[0];
                    }
                }

            }
            else{
                my @temp = split /\//, $service;
                # old format "general/tcp"
                $app = $temp[0];
                $port = "";
                $proto = $temp[1];
            }


            $service =~ s/(\\n)+$//;
            $service = htmlspecialchars($service);

            #ENSURE WE HAVE SOME DATA
            if ($description) {
                my @aliases = ();

                if($description =~/resolves as (.*)\.\\/) {
                    if($1 ne $host) {
                        push(@aliases, $1);
                    }
                }

                $description =~ s/\\/\\\\/g;    #FIX TO BACKSLASHES
                $description =~ s/\\\\n/\\n/g;  #FIX TO NEWLINE

                my $temp = {
                    Host        => $host,
                    Description => $description,
                    Service     => $service,
                    App         => $app,
                    Port        => $port,
                    Proto       => $proto,
                    ScanID      => $scan_id,
                    Aliases     => join(',', @aliases),
                };

                logwriter ("my temp = { Port=>$port, Host=>$host, Description=>$description, Service=>$service, App=>$app, Port=>$port,  roto=>$proto, ScanID=>$scan_id, Aliases=>".join(',', @aliases)." };\n", 4);

                if (!exists($resultHash{$port}{$host}{lc $description}{lc $app}{lc $proto}{$scan_id}))
                {
                    push (@issues, $temp);
                    $resultHash{$port}{$host}{lc $description}{lc $app}{lc $proto}{$scan_id}++;
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

sub load_plugins_info {
    $sql = qq{SELECT id, oid, name, summary, cve_id, bugtraq_id, xref, enabled, created, modified,
                     category, family, risk, cvss_base_score
              FROM vuln_nessus_plugins};

    my %plugins_info = ();

    my $sth_sel2 = $dbh->prepare($sql);
    $sth_sel2->execute;
    while (my($id, $oid, $name, $summary, $cve_id, $bugtraq_id, $xref, $enabled, $created, $modified,
        $category, $family, $risk, $cvss_base_score) = $sth_sel2->fetchrow_array())
    {

        $plugins_info{$id} = {
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

#pop hosthash will process the results to make the most of the data.  This will improve reporting / tracking of scanned hosts
sub pop_hosthash {
    my (@issues) = @{$_[0]};

    logwriter("Number of results: ".$#issues, 5);

    my %hostHash;

    if ($no_results) {
        die("NO results to import or Host offline");
    }

    my $ih = 0;

    my $plugins_info = load_plugins_info();

    #GET POPULATE HOSTHASH WITH HOSTNAME /DOMAIN FROM PLUGIN 10150
    logwriter("gvm_scan: Start Populating HostHash", 5);
    foreach(@issues) {
        my $issue = $_;
        my ($scan_id, $host, $hostname, $hostip, $service, $app, $port, $proto, $desc, $record_type, $aliases, $risk) = " ";

        $scan_id = $issue->{ScanID};
        $scan_id =~ s/.*\.(\d+)$/$1/g;
        $app = $issue->{App};
        $port = $issue->{Port};
        $proto = $issue->{Proto};
        $desc = $issue->{Description};
        $service = $issue->{Service};
        $host = $issue->{Host};
        $aliases = $issue->{Aliases};

        if($host eq "") {
            next;
        }

        if (!$hostHash{$host}{'mac'}) {
            $hostHash{$host}{'mac'} = "unknown";
        }

        if (!exists($hostHash{$host}{'dns'})) {
            #DETERMINE IF IT IS AN IP (CRITICAL STEP AS SCANLITE RETURNS EITHER HOSTIP/HOSTNAME)
            my $isIP = FALSE;
            if ($host =~ m/^(\d\d?\d?)\.(\d\d?\d?)\.(\d\d?\d?)\.(\d\d?\d?)$/ && $1 <= 255 && $2 <= 255 && $3 <= 255 && $4 <= 255){
                $hostip=$host;
                $isIP = TRUE;
            }

            if ($isIP == TRUE) {
                my $tmp_hostname = resolve_host($hostip);

                if (defined($tmp_hostname) && $tmp_hostname ne "") {
                    $hostname = $tmp_hostname;
                }
            }

            $hostHash{$host}{'ip'} = $host;
            if(defined($hostname) && $hostname ne "") {
                $hostHash{$host}{'fqdn'} = $hostname;
                $hostHash{$host}{'dns'} = "1";
                #INDICATE RESOLVED BY NAME WAS SUCCESS
                logwriter("gvm_scan: successfully looked up name [$host]", 5);
            } else {
                #INDICATE RESOLVED BY NAME FAILED
                $hostHash{$host}{'dns'} = "-1";
            }
        }

        #NBTSCAN PLUGIN
        if ($scan_id eq "10150") {
            my %hostinfo = extract_hostinfo($desc);
            $hostHash{$host}{'mac'} = $hostinfo{'mac'};

            if ($hostHash{$host}{'dns'} eq "-1" && $hostinfo{'dns'} eq "1") { #ONLY UPDATE NAME FROM 10150 WHEN DNS FAILS
                $hostHash{$host}{'fqdn'} = $hostinfo{'hostname'};
                $hostHash{$host}{'dns'} = '1';

                logwriter("gvm_scan: success plugin 10150 to look up name [" . $hostinfo{'hostname'} . "]", 5);
            }
        }

        if($aliases ne "") {
            if (!defined($hostHash{$host}{'aliases'})) {
                $hostHash{$host}{'aliases'} = $aliases;
            }
            else {
                $hostHash{$host}{'aliases'} .= ",$aliases";
            }
        }

        $risk = 7;
        # ENG-110102 - Vulnerability ratings updated to be compatible with CVSS v2.0
        if($desc =~ m/cvss base score\s*:[^\d]*(\d+\.\d+)/i) {
            my ($cve_id) = $plugins_info->{$scan_id}->{'cve_id'};

            if (int($1) >= 9) {
                #High
                $risk = 2;
            }
            elsif( int($1) >= 7 && int($1) < 9) {
                #High
                $risk = 2;
            }
            elsif( int($1) >= 4 && int($1) < 7) {
                #Medium
                $risk = 3;
            }
            elsif(int($1) > 0 && int($1) < 4) {
                #Low
                $risk = 6;
            }
            elsif( int($1) == 0 && $cve_id ne '' ) {
                #Low
                $risk = 6;
            }
            else {
                #Info
                $risk = 7;
            }
        }

        $service =~ s/(\\n)+$//;
        $desc =~ s/(\\n)+$//;
        $desc =~ s/\\n+$//;
        $desc =~ s/\\+$//;

        #MEANS TO TRACK FILTER ON THE REPORTS
        $record_type = "N";

        $port = htmlspecialchars($port);
        $app = htmlspecialchars($app);
        $proto = htmlspecialchars($proto);
        $service = htmlspecialchars($service);
        $desc = htmlspecialchars($desc);

        my $key = $ih;
        $hostHash{$host}{'results'}{$key} = {
            'scanid'  => $scan_id,
            'port'    => $port,
            'app'     => $app,
            'service' => $service,
            'proto'   => $proto,
            'risk'    => $risk,
            'record'  => $record_type,
            'desc'    => $desc
        };

        #logwriter("Ip: $host", 4);
        $ih++;
    }
    logwriter("Finished Populating HostHash: $ih", 5);

    return (%hostHash);
}


sub process_results {
    my (%hostHash)  = %{$_[0]};
    my ($job_title) = $_[1];
    my ($scantype)  = $_[2];
    my ($username)  = $_[3];
    my ($sid)       = $_[4];
    my ($scantime)  = $_[5];
    my ($fk_name)   = $_[6];
    my ($ctx)       = $_[7];

    my ($sth_sel, $sql, $sql_insert, $sql_insert2, $report_id);
    my ($sth_update, $sql_update, $sth_del, $sql_delete);
    my ($rpt_key, $sqli, $sth_ins);

    my ($fp_sel, $fp_service, $fp);
    my %vuln_resume = ();

    #List of sensors for insert hosts
    $sql = qq{ SELECT name FROM sensor};
    $sth_sel=$dbh->prepare($sql);
    $sth_sel->execute;

    if (!defined($fk_name) || $fk_name eq "") {
        $fk_name = "NULL";
    }

    $report_id = create_report ($job_title, $scantype, $username, $sid, $scantime);
    logwriter("Report id: $report_id", 4);
    if (!$report_id) {
        logwriter("Failed to lookup report after insert for scan $scantime", 2);
        return 0;
    }


    logwriter("gvm_scan: Start Processing Results", 5);
    $sql_insert = "";
    my $i = 0;
    my %TOTALRISKS = (1, 0, 2, 0, 3, 0, 4, 0, 5, 0, 6, 0, 7, 0);   #TRACK COUNT ALL SCANNED RISKS

    foreach my $host (sort keys %hostHash) {
        my ($hostip, $hostname, $mac_address, $open_issues, $aliases) = " ";

        if ($hostHash{$host}{'ip'}) {
            $hostip  = $hostHash{$host}{'ip'};
        }

        if ($hostHash{$host}{'fqdn'}) {
            $hostname  = $hostHash{$host}{'fqdn'};
        }

        if ($hostHash{$host}{'mac'}) {
            $mac_address  = $hostHash{$host}{'mac'};
        }

        if ($hostHash{$host}{'aliases'}) {
            $aliases = $hostHash{$host}{'aliases'};
        }

        $hostname = "";
        if ($host =~ /^(\d+)\.(\d+)\.(\d+)\.(\d+)$/) {
            $hostname = ip2hostname($host, $ctx);
        }
        else {
            $hostname = $host;
            $hostip = hostname2ip($hostname, $ctx, TRUE);

            if($hostip !~ /^(\d+)\.(\d+)\.(\d+)\.(\d+)$/) {
                #It cannot be replaced by the logwriter function because this text is parsed in the UI
                print "Skipping Host [" . $hostname . "]\n";
                next;
            }
        }
        my @hostname_and_aliases = name_and_aliases_in_host($hostip, $ctx);

        if ($asset_insertion == TRUE) {
            if (!defined($aliases)) {
                $aliases = "";
            }

            if($hostname_and_aliases[0] ne "") {
                if($aliases eq "") {
                    $aliases = $hostname;
                }
                else {
                    $aliases .= ",$hostname";
                }
            }

            if ($hostname_and_aliases[0] eq "") {
                if($hostname =~ /^(\d+)\.(\d+)\.(\d+)\.(\d+)$/) {
                    $hostname =~ s/\./\-/g;
                }

                my $host_data = trim(encode_base64($hostip."|".$ctx."|Host-".$hostname."|".$aliases));

                $host_data =~ s/\n//g;

                my $result = `/usr/bin/php /usr/share/ossim/scripts/vulnmeter/util.php insert_host $host_data`;
            }
            elsif ($aliases ne "") {
                my @ialiases = split(/,/, $hostname_and_aliases[1]); # aliases to insert
                my @taliases = split(/,/, $aliases); #aliases found in nbe file
                foreach (@taliases) {
                    if(in_array(\@ialiases, $_)==0 && $_ ne $hostname_and_aliases[0]) {
                        push(@ialiases, $_);
                    }
                 }
                my $host_data = trim(encode_base64("$hostip|$ctx|".join(",",@ialiases)));
                my $result = `/usr/bin/php /usr/share/ossim/scripts/vulnmeter/util.php update_aliases $host_data`;
            }
        }

        #before delete extract data
        my $sql_extract_data = qq{SELECT count(risk) as count, risk FROM vuln_nessus_latest_results
                                            WHERE hostIP = '$hostip' AND username = '$username' AND sid = '$sid' AND ctx = UNHEX('$ctx')
                                            AND falsepositive='N' GROUP BY risk};
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
        $sql_delete = qq{ DELETE FROM vuln_nessus_latest_results WHERE hostIP = '$hostip' and username = '$username' and sid = '$sid' and ctx = UNHEX('$ctx') };
        $sth_del = $dbh->prepare($sql_delete);
        $sth_del->execute;

        $sql_delete = qq{ DELETE FROM vuln_nessus_latest_reports WHERE hostIP = '$hostip' and username = '$username' and sid = '$sid' and ctx = UNHEX('$ctx') };
        $sth_del = $dbh->prepare($sql_delete);
        $sth_del->execute;

        $hostname = trim($hostname);      #INITIALLY SET IT TO " ";

        # load fps
        my %host_fp = ();
        $sql = qq{ SELECT scriptid,service FROM vuln_nessus_latest_results WHERE hostIP='$hostip' AND ctx=UNHEX('$ctx') and falsepositive='Y' UNION SELECT scriptid,service FROM vuln_nessus_results WHERE ctx=UNHEX('$ctx') AND hostIP='$hostip' and falsepositive='Y' };
        $fp_sel = $dbh->prepare($sql);
        $fp_sel->execute;
        while ((my $fp_scriptid,$fp_service) = $fp_sel->fetchrow_array) {
            $host_fp{$fp_scriptid}{$fp_service} = 1;
        }

        my %recordshash = %{$hostHash{$host}{'results'}};
        %vuln_resume = ();

        foreach my $record (sort keys %recordshash) {
            my ($scan_id, $service, $app, $port, $proto, $risk, $domain, $record_type, $desc) = " ";

            $scan_id = $hostHash{$host}{'results'}{$record}{'scanid'};
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
            $open_issues .= "$scan_id\n";    #USED TO TRACK ISSUES TO BE CLOSED
            $TOTALRISKS{"$risk"} += 1;      #USING ASSOC ARRAY TO TRACK SCAN RISK COUNT ON THE FLY

            logwriter("record=$record\t 'scanid' => [$scan_id], 'port' => [$port], 'record' => [$record_type], 'service' => [$service],"
                ." 'proto' => [$proto], 'risk' => [$risk], 'desc' => [$desc]\n", 4);


            if (!defined($sql_insert) || $sql_insert eq "") {

                #FIRST ITERATION OR RESET VARIABLE AFTER IMPORTING 100 RECORDS
                $sql_insert = "INSERT INTO vuln_nessus_results (report_id, scantime, hostip, ctx, hostname, record_type, service, port, protocol , app, scriptid, risk, msg, falsepositive)\nVALUES\n";
                $sql_insert2 = "INSERT INTO vuln_nessus_latest_results (username, sid, scantime, hostip, ctx, hostname, record_type, service, port, protocol , app, scriptid, risk, msg, falsepositive)\nVALUES\n";
                #delete host_plugin_sid results
                $sql_delete = qq{ DELETE FROM host_plugin_sid WHERE host_ip = inet6_aton('$hostip') and ctx=UNHEX('$ctx') and plugin_id = 3001 };
                logwriter($sql_delete, 5);
                $sth_del = $dbh->prepare($sql_delete);
                $sth_del->execute;
                #delete host_plugin_sid results
                my @arr = split(/\./, rand());
                if ($arr[1] && is_number($arr[1])) { $rpt_key = $arr[1]; }
                else { $rpt_key = 0; }
                $sqli = qq{ INSERT INTO vuln_nessus_latest_reports (hostIP, ctx, username, fk_name, sid, scantime, report_type, scantype, report_key, note, failed) VALUES ('$hostip', UNHEX('$ctx'), '$username', NULL, '$sid', '$scantime', 'N', '$scantype', '$rpt_key', '0;0;0;0;0','0') ON DUPLICATE KEY UPDATE scantime='$scantime', failed=results_sent, note='$last_string' };
                logwriter($sqli, 5);
                $sth_ins = $dbh->prepare($sqli);
                $sth_ins->execute;
                $i=0;
            }
            $i += 1;
            $fp = (defined($host_fp{$scan_id}{$service}) && $host_fp{$scan_id}{$service} == 1) ? 'Y' : 'N';
            my $descE = encode_base64($desc);
            $sql_insert .= " ('$report_id', '$scantime', '$hostip', UNHEX('$ctx'), '$hostname', '$record_type', '$service', '$port', '$proto', '$app', '$scan_id', '$risk', FROM_BASE64('$descE'), '$fp'),\n";
            $sql_insert2 .= " ('$username', '$sid', '$scantime', '$hostip', UNHEX('$ctx'), '$hostname', '$record_type', '$service', '$port', '$proto', '$app', '$scan_id', '$risk', FROM_BASE64('$descE'), '$fp'),\n";

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

            if(!defined($vuln_resume{$hostip})) {
                $vuln_resume{$hostip} = $risk;
            }
            elsif($risk < $vuln_resume{$hostip}) {
                $vuln_resume{$hostip} = $risk;
            }

            # incidents
            my $host_data = encode_base64("$hostip|$ctx");
            my $hid = `/usr/bin/php /usr/share/ossim/scripts/vulnmeter/util.php get_host_id $host_data`;

            if(!defined($hid) || $hid !~ /[\da-f]{32}/i)
            {
                $hid = "";
            }
            else
            {
                chomp($hid);
            }

            update_ossim_incidents($hostip, $ctx, $hid, $port, $risk, $desc, $scan_id, $username, $sid);
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

    } #END FOREACH HOST LOOP

    foreach my $hostip (keys %vuln_resume) {
        my $max_risk = 0;

        # max_risk is the field risk in vuln_nessus_results table
        $max_risk = $vuln_resume{$hostip};
        if($max_risk<=2) {
            $max_risk = 10;
        }
        elsif ($max_risk<=6) {
            $max_risk = 7;
        }
        else {
            $max_risk = 3;
        }
        #
        $sql = qq{ SELECT scriptid FROM vuln_nessus_latest_results WHERE hostIP='$hostip' AND ctx=UNHEX('$ctx') };
        logwriter($sql, 5);
        $sth_sel = $dbh->prepare($sql);
        $sth_sel->execute;
        while ((my $scan_id) = $sth_sel->fetchrow_array) {
            #logwriter("Scan id: $scan_id", 5);
            # plugin_sid
            $sql_update = qq{ INSERT IGNORE INTO host_plugin_sid (host_ip, ctx, plugin_id, plugin_sid) VALUES (inet6_aton('$hostip'), UNHEX('$ctx'), 3001, $scan_id) };
            logwriter($sql_update, 5);
            $sth_update = $dbh->prepare($sql_update);
            $sth_update->execute;
        }

        # host_vulnerability
        my $host_data = encode_base64("$hostip|$ctx");
        my $host_id   = `/usr/bin/php /usr/share/ossim/scripts/vulnmeter/util.php get_host_id $host_data`;

        chomp($host_id);

        if(defined($host_id) && $host_id !~ /[\da-f]{32}/i) {
            $sql_update = qq{ INSERT INTO host_vulnerability VALUES (UNHEX('$host_id'), '$scantime', $max_risk) ON DUPLICATE KEY UPDATE vulnerability=$max_risk  };
            logwriter($sql_update, 5);
            $sth_update = $dbh->prepare($sql_update);
            $sth_update->execute;
        }

        # vulnerabilities
        $sql_update = qq{SELECT count(*) AS vulnerability FROM (SELECT DISTINCT hostip, port, protocol, app, scriptid, msg, risk
                    FROM vuln_nessus_latest_results WHERE hostIP ='$hostip' AND ctx=UNHEX('$ctx') AND falsepositive='N') AS t GROUP BY hostip};
        logwriter($sql_update, 5);
        $sth_update=$dbh->prepare($sql_update);
        $sth_update->execute;
        my $vuln_host = $sth_update->fetchrow_array;

        # update vulns into vuln_nessus_latest_reports - sort facility
        $sql_update = qq{ UPDATE vuln_nessus_latest_reports SET results_sent=$vuln_host WHERE hostIP='$hostip' AND ctx=UNHEX('$ctx') AND username='$username' };
        logwriter($sql_update, 5);
        $sth_update = $dbh->prepare($sql_update);
        $sth_update->execute;
    }
    return $report_id;
}

#connect to db
sub conn_db {
    $dbh = DBI->connect("$CONFIG{'DATABASEDSN'}:$CONFIG{'DATABASENAME'}:$CONFIG{'DATABASEHOST'}",
        "$CONFIG{'DATABASEUSER'}","$CONFIG{'DATABASEPASSWORD'}", {
        PrintError => 0,
        RaiseError => 1,
        AutoCommit => 1 }) or die("Failed to connect : $DBI::errstr\n");

    $sql = qq{ SET SESSION time_zone='+00:00' };

    safe_db_write ($sql, 5);

    return $dbh;
}

#disconnect from db
sub disconn_db {
    my ($dbh) = @_;
    $dbh->disconnect or die("Failed to disconnect : $DBI::errstr\n");
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

    if ($debug || $log_level ge $specified_level) {
        print $message ."\n";
    }
}

sub create_report {
    my ($job_title) = $_[0];
    my ($scantype)  = $_[1];
    my ($username)  = $_[2];
    my ($sid)       = $_[3];
    my ($scantime)  = $_[4];

    my ($sth_sel, $sql, $report_id, $report_key, $report_type);

    #Build a report_key value to secure reports.
    my @arr = split(/\./, rand());
    if ($arr[1] && is_number($arr[1])) {
        $report_key = $arr[1];
    } else {
        logwriter("Failed Report Key generation", 3);
    }

    $report_type = "I";

    $sql = qq{ INSERT INTO vuln_nessus_reports (username, name, fk_name, sid, scantime, report_type, scantype, report_key)
    VALUES ('$username', '$job_title', NULL, '$sid', '$scantime', '$report_type', '$scantype', '$report_key'); };
    safe_db_write ($sql, 4);

    $sql = qq{ SELECT report_id FROM vuln_nessus_reports WHERE scantime='$scantime' AND report_key='$report_key' ORDER BY scantime DESC LIMIT 1 };

    logwriter($sql, 5);
    $sth_sel=$dbh->prepare($sql);
    $sth_sel->execute;
    $report_id = $sth_sel->fetchrow_array();

    return $report_id;
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

    #The incidents created will be equal or greater than the risk indicated
    $risk = 9 - int($risk); # convert into ossim risk

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

    my $profile_name = get_config_name($sid);
    $desc .= "\n\nProfile Name: $profile_name";

    if (no_empty($incident_id) && $status ne 'Closed') {
        $sql_inc = qq{UPDATE incident SET last_update = UTC_TIMESTAMP() WHERE id = '$incident_id'};
        safe_db_write($sql_inc, 5);

        $sql_inc = qq{SELECT incident_id FROM incident_tag WHERE incident_tag.incident_id = '$incident_id' AND incident_tag.tag_id = '$id_false_positive'};
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

sub empty {
    my $var  = shift;
    if (defined($var) && $var ne "") {
        return 0;
    }
    else {
        return 1;
    }
}

sub no_empty {
    my $var = shift;
    if (defined($var) && $var ne "") {
        return 1;
    }
    else {
        return 0;
    }
}

#needed for host updated / load results
sub resolve_host {
    my ($hostip) = @_;

    if (! defined ($hostip) || $hostip eq "") { return ""; }

    # ATTEMPT GET HOST BY ADDRESS WILL CHECK FILES/DNS PRIMARY
    my $iaddr = inet_aton($hostip); # or whatever address

    my $namer  = gethostbyaddr($iaddr, AF_INET);

    #print $namer;
    #print Dumper($CONFIG{'nameservers'});

    if (defined($namer)) {
        my $thost = lc ($namer);
        #logwriter($thost, 5);
        return $thost;
    } else {

        if ($CONFIG{'nameservers'} ne "") {
            my @nameservers = split /,/, $CONFIG{'nameservers'};
            foreach my $nameserver (@nameservers) {
                $nameserver =~ s/\s+//g;
                my $namer = nslookup(host => "$hostip", type => "PTR", server => "$nameserver");
                if (defined($namer) && $namer ne "") {
                    my $thost = lc ($namer);
                    return $thost;
                }
            }
        }
    }
    logwriter("REVERSE IP [$hostip] TO NAME FAILED\n", 3);
    return "";
}

#filter html special characters
sub htmlspecialchars {
    my $tmpSTRmsg = $_[0];
    return encode_entities($tmpSTRmsg);
}

#is this a num
sub is_number{
    my($n)=@_;

    if ($n) {
        return ($n=~/^\d+$/);
    } else {
        return;
    }
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

sub ip2hostname {
    my ($ip, $ctx) = @_;

    my ($sql, $sth_sel);
    my ($hostname) = "";

    $sql = qq{ SELECT h.hostname FROM host h, host_ip hip WHERE h.id=hip.host_id and hip.ip = inet6_aton('$ip') and h.ctx=UNHEX('$ctx')};
    $sth_sel = $dbh->prepare($sql);
    $sth_sel->execute;
    $hostname = $sth_sel->fetchrow_array;

    if(defined($hostname) && $hostname !~ /^(\d+)\.(\d+)\.(\d+)\.(\d+)$/ &&  $hostname ne "") {
        return $hostname;
    }
    else {
        return $ip;
    }
}

sub hostname2ip {
    my ($hostname, $ctx, $resolv) = @_;
    my ($sql, $sth_sel, $cmd);
    my ($ip) = "";

    $sql = qq{ SELECT inet6_ntoa(hip.ip) AS ip
                        FROM host h, host_ip hip, vuln_nessus_latest_reports vnlr
                        WHERE h.id = hip.host_id
                        AND h.hostname = '$hostname'
                        AND h.ctx =UNHEX('$ctx')
                        AND vnlr.hostIP = inet6_ntoa(hip.ip)
                        AND vnlr.ctx = UNHEX('$ctx') };

    $sth_sel = $dbh->prepare($sql);
    $sth_sel->execute;
    $ip = $sth_sel->fetchrow_array;

    if(!defined($ip)) {
        $sql = qq{ SELECT inet6_ntoa(hip.ip) as ip
                            FROM host h, host_ip hip
                            WHERE h.id=hip.host_id
                            and h.hostname = '$hostname'
                            and h.ctx=UNHEX('$ctx')};

        $sth_sel = $dbh->prepare($sql);
        $sth_sel->execute;
        $ip = $sth_sel->fetchrow_array;
    }

    if(!defined($ip)) { $ip = ""; }

    if ($ip ne "") { return $ip; }
    elsif ($resolv == TRUE) {

        $hostname =~ s/;//;

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

sub name_and_aliases_in_host {
    my ($ip, $ctx) = @_;
    my ($sql_in_host, $sth_sel_in_host, $hostname, $fqdns);
    my @result = ();

    $sql_in_host = qq{ SELECT h.hostname, h.fqdns FROM host h, host_ip hip WHERE h.id=hip.host_id and hip.ip = inet6_aton('$ip') and h.ctx=UNHEX('$ctx')};

    $sth_sel_in_host = $dbh->prepare($sql_in_host);
    $sth_sel_in_host->execute;
    ($hostname, $fqdns) = $sth_sel_in_host->fetchrow_array;

    if (!defined($hostname)) { $hostname = ""; }
    if (!defined($fqdns)) { $fqdns = ""; }

    push(@result, $hostname);
    push(@result, $fqdns);

    return @result;
}

sub resolve_name2ip {
    my ($hostname) = @_;
    if (! defined ($hostname) || $hostname eq "") {
        return "";
    }

    # ATTEMPT GET HOST BY ADDRESS WILL CHECK FILES/DNS PRIMARY
    my $packed_ip = gethostbyname($hostname);

    if (defined($packed_ip)) {
        my $c_ip = inet_ntoa($packed_ip);
        return $c_ip;
    } else {
        #TRY OTHER NAMES SERVERS
        if ($CONFIG{'nameservers'} ne "") {
            my @nameservers = split /,/, $CONFIG{'nameservers'};

            foreach my $nameserver (@nameservers) {
                $nameserver =~ s/\s+//g;
                my $namer = nslookup(host => "$hostname", server => "$nameserver");
                if (defined($namer) && $namer ne "") {
                    my $thost = lc ($namer);
                        return $thost;
                }
            }
        }
    }
    logwriter("RESOLVE [$hostname] TO IP FAILED\n", 3);
    return "";
}

# extract host info <- assuming msg from plugin #10150 is supplied
sub extract_hostinfo {
    my ($txt_msg) = @_;

    #changed $domain to $wgroup (did not want to confuse with domain field per nessus nbe results
    my ($hostname, $mac_address) = "";

    logwriter("gvm_scan: plugin 10150 data: [[$txt_msg]]", 5);
    my @arrMSG = split /\\n\\n|\n\n|\r/, $txt_msg;
    foreach my $line (@arrMSG) {
        $line =~ s/\#.*$//;
        chomp($line);
        $line =~ s/\s+$//;
        $line =~ s/^\s+//;
        logwriter("gvm_scan: LINE=[$line]", 5);
        if ($line =~ /computer\sname/i) {
            my @temp=split(/=/,$line,2);
            $temp[0] =~ s/\s+$//;
            $temp[0] =~ s/^\s+//;
            $hostname = lc($temp[0]);
            logwriter("gvm_scan: hostname=[$hostname]", 5);
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

sub get_tzc {
    my $tz  = $_[0];
    my $tzc = "";

    if ($tz =~ /(.*)\.(.*)/) {
        my $fnd0 = ($1 > 0) ? "+$1" : $1;
        my $fnd1 = ($2 > 9) ? $2 : $2."0";
        $tzc = "$fnd0:$fnd1";
    }
    else {
        $tzc = ($tz>=0) ? "+$tz:00" : "$tz:00";
    }
    return $tzc;
}

sub get_utc_from_date {
    my $conn = $_[0];
    my $date = $_[1];
    my $tz   = $_[2];

    $tz = get_tzc($tz);

    my $sql = qq{ select convert_tz('$date','$tz','+00:00') };
    my $sth_sel=$conn->prepare($sql);
    $sth_sel->execute;

    my $value = $sth_sel->fetchrow_array;

    return $value;
}

sub get_cve {
    my $scan_id = $_[0];
    my $sql = qq{ SELECT cve_id FROM vuln_nessus_plugins t1 WHERE oid = '$scan_id' };

    logwriter($sql, 5);
    my $sth_sel = $dbh->prepare($sql);
    $sth_sel->execute();
    my $cve_id = $sth_sel->fetchrow_array();
    if (! defined $cve_id) {
        $cve_id = '';
    }
    return $cve_id;
}


