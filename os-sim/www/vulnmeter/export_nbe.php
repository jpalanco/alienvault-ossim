<?php
/**
*
* License:
*
* Copyright (c) 2003-2006 ossim.net
* Copyright (c) 2007-2013 AlienVault
* All rights reserved.
*
* This package is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; version 2 dated June, 1991.
* You may not use, modify or distribute this program under any other version
* of the GNU General Public License.
*
* This package is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this package; if not, write to the Free Software
* Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
* MA  02110-1301  USA
*
*
* On Debian GNU/Linux systems, the complete text of the GNU General
* Public License can be found in `/usr/share/common-licenses/GPL-2'.
*
* Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
*
*/


require_once 'av_init.php';

Session::logcheck("environment-menu", "EventsVulnerabilities");

$conf = $GLOBALS["CONF"];

$job_id = GET("job_id");
ossim_valid($job_id, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("job id"));

$report_id = GET("report_id");
ossim_valid($report_id, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("report id"));

if (ossim_error()) {
    die(ossim_error());
}

$db = new ossim_db();
$dbconn = $db->connect();

$version = $conf->get_conf("ossim_server_version"); 

// check username

$dbconn->SetFetchMode(ADODB_FETCH_ASSOC);

$user_name_filter = "";

list($arruser, $user) = Vulnerabilities::get_users_and_entities_filter($dbconn);

if($user!="") $user_name_filter = "and username in ($user)";

if(intval($job_id)>0) {  // export job to nbe

    $sql = "select * from vuln_jobs where id=$job_id $user_name_filter";
    
    if (!$rs = & $dbconn->Execute($sql)) {
        print _('error reading vuln_jobs information').' '.$conn->ErrorMsg() . '<BR>';
        exit;
    }

    $name       = $rs->fields["name"];
    $report_id  = $rs->fields["report_id"];
    $scan_start = $rs->fields["scan_START"];
    $scan_end   = $rs->fields["scan_END"];

}
else if(intval($report_id)>0) {
    $sql = "select * from vuln_nessus_reports where report_id=$report_id $user_name_filter";
 
    if (!$rs = & $dbconn->Execute($sql)) {
        print _('error reading vuln_nessus_reports information').' '.$conn->ErrorMsg() . '<BR>';
        exit;
    }
    $name       = $rs->fields["name"];
}

if($name!="" && intval($job_id)>0) {

    $oids  = array();
    $risks = array( "1" => "Serious", "2" => "Security Hole", "3" => "Security Warning", "6" => "Security Note", "7" => "Log Message" );
    
    $dest = $conf->get_conf("nessus_rpt_path")."/tmp/nessus_s".$report_id.".nbe";
    
    if(file_exists($dest)) {
        unlink($dest);
    }
    
    // write data into file
    
    $fh = fopen($dest, "w");
    
    if($fh==false) {
        echo _("Unable to create file")."<br />";
    
    }
    fputs ($fh, "timestamps|||scan_start|".date("D M d H:i:s Y", strtotime($scan_start))."|\n");
    
    $sql = "SELECT *, HEX(ctx) as hctx from vuln_nessus_results WHERE report_id = ".$rs->fields["report_id"]." ORDER BY hostIP DESC";
    
    if (!$rs = & $dbconn->Execute($sql)) {
        print _('error reading vuln_nessus_results information').' '.$dbconn->ErrorMsg() . '<BR>';
        exit;
    }
    
    $hostIP = "";
    while (!$rs->EOF) {
        if(Session::hostAllowed_by_ip_ctx($dbconn, $rs->fields["hostIP"], $rs->fields["hctx"])) {
            // get oid
            if($oids[$rs->fields["scriptid"]] == "") {
                $oid = $dbconn->GetOne("SELECT oid FROM vuln_nessus_plugins WHERE id=".$rs->fields["scriptid"]);
                
                if( $oid == "" )
                    $oid = $rs->fields["scriptid"];
                
                $oids[$rs->fields["scriptid"]] = $oid; // save to cache
            }
            else {
                $oid = $oids[$rs->fields["scriptid"]];
            }
        
            // to display host_start y host_end for each host
            
            if ($rs->fields["hostIP"]!=$hostIP) {
                fputs ($fh, "timestamps||".$rs->fields["hostIP"]."|host_start|".date("D M d H:i:s Y", strtotime($scan_start))."|\n");
                if( $hostIP !="" ) {
                    fputs ($fh, "timestamps||".$hostIP."|host_end|".date("D M d H:i:s Y", strtotime($scan_end))."|\n");
                }
                $hostIP = $rs->fields["hostIP"];
            }
        
            preg_match("/(\d+\.\d+\.\d+)\.\d+/", $rs->fields["hostIP"], $found);
            
            
            $rs->fields["msg"] = preg_replace ( "/Risk [fF]actor\s*:\s*(\\n)*Serious((\\n)+| \/ |$)/", "", $rs->fields["msg"]);
            $rs->fields["msg"] = preg_replace ( "/Risk [fF]actor\s*:\s*(\\n)*Critical((\\n)+| \/ |$)/", "", $rs->fields["msg"]);
            $rs->fields["msg"] = preg_replace ( "/Risk [fF]actor\s*:\s*(\\n)*High((\\n)+| \/ |$)/", "", $rs->fields["msg"]);
            $rs->fields["msg"] = preg_replace ( "/Risk [fF]actor\s*:\s*(\\n)*Medium((\\n)+| \/ |$)/", "", $rs->fields["msg"]);
            $rs->fields["msg"] = preg_replace ( "/Risk [fF]actor\s*:\s*(\\n)*Medium\/Low((\\n)+| \/ |$)/", "", $rs->fields["msg"]);
            $rs->fields["msg"] = preg_replace ( "/Risk [fF]actor\s*:\s*(\\n)*Low\/Medium((\\n)+| \/ |$)/", "", $rs->fields["msg"]);
            $rs->fields["msg"] = preg_replace ( "/Risk [fF]actor\s*:\s*(\\n)*Low((\\n)+| \/ |$)/", "", $rs->fields["msg"]);
            $rs->fields["msg"] = preg_replace ( "/Risk [fF]actor\s*:\s*(\\n)*Info((\\n)+| \/ |$)/", "", $rs->fields["msg"]);
            $rs->fields["msg"] = preg_replace ( "/Risk [fF]actor\s*:\s*(\\n)*[nN]one to High((\\n)+|(\s)+| \/ |$)/", "", $rs->fields["msg"]);
            $rs->fields["msg"] = preg_replace ( "/Risk [fF]actor\s*:\s*(\\n)*[nN]one((\\n)+| \/ |$)/", "", $rs->fields["msg"]);
            $rs->fields["msg"] = preg_replace ( "/Risk [fF]actor\s*:\s*(\\n)*Passed((\\n)+| \/ |$)/", "", $rs->fields["msg"]);
            $rs->fields["msg"] = preg_replace ( "/Risk [fF]actor\s*:\s*(\\n)*Unknown((\\n)+| \/ |$)/", "", $rs->fields["msg"]);
            $rs->fields["msg"] = preg_replace ( "/Risk [fF]actor\s*:\s*(\\n)*Failed((\\n)+| \/ |$)/", "", $rs->fields["msg"]);
            
            $line  = "results|".$found[1]."|".$rs->fields["hostIP"]."|".$rs->fields["service"]."|".$oid."|".$risks[$rs->fields["risk"]]."|".preg_replace ( "/\n/" , '\n' , $rs->fields["msg"] ); 
            fputs ($fh, $line."\n");
            
            $last_ip = $rs->fields["hostIP"]; // last ip
        }
        $rs->MoveNext();
    }
    fputs ($fh, "timestamps||".$last_ip."|host_end|".date("D M d H:i:s Y", strtotime($scan_end))."|\n");
    fputs ($fh, "timestamps|||scan_end|".date("D M d H:i:s Y", strtotime($scan_end))."|\n");
    
    fclose ($fh);
    
    // download .nbe
     
    $file_name = "results_".$name;
    
    $file_name = preg_replace("/:|\\|\'|\"|\s+|\t|\-/", "_", $file_name);

    header("Content-type: application/unknown");
    header('Content-Disposition: attachment; filename='.$file_name.'.nbe');
   
    readfile($dest);
    
    if(file_exists($dest)) {
        unlink($dest);
    }
}
else if($name!="" && intval($report_id)>0) {
    $file_name = "results_".$name;
    
    $file_name = preg_replace("/:|\\|\'|\"|\s+|\t|\-/", "_", $file_name);

    header("Content-type: application/unknown");
    header('Content-Disposition: attachment; filename='.$file_name.'.nbe');
   
    readfile("/usr/share/ossim/uploads/nbe/".$report_id.".nbe");
}
else {
    echo _("You don't have permission to see these results");
}

$dbconn->disconnect();
?>