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

ossim_valid($job_id, OSS_DIGIT, 'illegal:' . _("job id"));

if (ossim_error()) {
    die(ossim_error());
}

$db     = new ossim_db();
$dbconn = $db->connect();

$version = $conf->get_conf("ossim_server_version"); 

// check username

$user_name_filter = "";

list($arruser, $user) = Vulnerabilities::get_users_and_entities_filter($dbconn);

if( $user!= "" ) $user_name_filter = "and username in ($user)";

$dbconn->SetFetchMode(ADODB_FETCH_ASSOC);

$result = $dbconn->Execute("select name, scan_PID from vuln_jobs where id=$job_id $user_name_filter");

$name = "";
$name = $result->fields["name"];

$scan_PID = "";
$scan_PID = $result->fields["scan_PID"];

if($name!="") {
 
    $dest = $GLOBALS["CONF"]->db_conf["nessus_rpt_path"]."/tmp/nessus_s".$scan_PID.".out";
    $file_name = "results_".$name;
    
    $file_name = preg_replace("/:|\\|\'|\"|\s+|\t|\-/", "_", $file_name);

    header("Content-type: application/unknown");
    header('Content-Disposition: attachment; filename='.$file_name.'.nbe');
   
    readfile($dest);
}
else {
    echo _("You don't have permission to see these results");
}

$dbconn->disconnect();
?>