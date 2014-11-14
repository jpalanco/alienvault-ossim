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


//
// $Id: rescsv.php,v 1.9 2010/04/26 16:08:21 josedejoses Exp $
//

/***********************************************************/
/*                    Inprotect                            */
/* --------------------------------------------------------*/
/* Copyright (C) 2006 Inprotect                            */
/*                                                         */
/* This program is free software; you can redistribute it  */
/* and/or modify it under the terms of version 2 of the    */
/* GNU General Public License as published by the Free     */
/* Software Foundation.                                    */
/* This program is distributed in the hope that it will be */
/* useful, but WITHOUT ANY WARRANTY; without even the      */
/* implied warranty of MERCHANTABILITY or FITNESS FOR A    */
/* PARTICULAR PURPOSE. See the GNU General Public License  */
/* for more details.                                       */
/*                                                         */
/* You should have received a copy of the GNU General      */
/* Public License along with this program; if not, write   */
/* to the Free Software Foundation, Inc., 59 Temple Place, */
/* Suite 330, Boston, MA 02111-1307 USA                    */
/*                                                         */
/* Contact Information:                                    */
/* inprotect-devel@lists.sourceforge.net                   */
/* http://inprotect.sourceforge.net/                       */
/***********************************************************/
/* See the README.txt and/or help files for more           */
/* information on how to use & config.                     */
/* See the LICENSE.txt file for more information on the    */
/* License this software is distributed under.             */
/*                                                         */
/* This program is intended for use in an authorized       */
/* manner only, and the author can not be held liable for  */
/* anything done with this program, code, or items         */
/* discovered with this program's use.                     */
/***********************************************************/
ini_set('memory_limit', '1500M');
ini_set("max_execution_time","720");

require_once 'av_init.php';
require_once 'config.php';
require_once 'functions.inc';

/** Include PHPExcel */
require_once 'classes/PHPExcel.php';

Session::logcheck("environment-menu", "EventsVulnerabilities");

$conf = $GLOBALS["CONF"];

//php4 version of htmlspecialchars_decode which is only available in php5 upwards
if (!function_exists('htmlspecialchars_decode'))
{
     function htmlspecialchars_decode($str)
     {
          $str = preg_replace("/&gt;/",">",$str);
          $str = preg_replace("/&lt;/","<",$str);
          $str = preg_replace("/&quot;/","\"",$str);
          $str = preg_replace("/&#039;/","'",$str);
          $str = preg_replace("/&amp;/","&",$str);

          return $str;
     }
}

switch ($_SERVER['REQUEST_METHOD'])
{
case "GET" :
     if (isset($_GET['scantime'])) { 
          $scantime=htmlspecialchars(mysql_real_escape_string(trim($_GET['scantime'])), ENT_QUOTES); 
     } else { $scantime=""; }
     
     if (isset($_GET['scantype'])) { 
          $scantype=htmlspecialchars(mysql_real_escape_string(trim($_GET['scantype'])), ENT_QUOTES); 
     } else { $scantype=""; }
     
     if (isset($_GET['key'])) { 
          $report_key=htmlspecialchars(mysql_real_escape_string(trim($_GET['key'])), ENT_QUOTES); 
     } else { $report_key=""; }
     
     if (isset($_GET['critical'])) { 
          $critical=htmlspecialchars(mysql_real_escape_string(trim($_GET['critical'])), ENT_QUOTES); 
     } else { $critical="0"; }
     
     if (isset($_GET['filterip'])) { 
          $filterip=htmlspecialchars(mysql_real_escape_string(trim($_GET['filterip'])), ENT_QUOTES); 
     } else { $filterip=""; }
     
     if (isset($_GET['scansubmit'])) { 
          $scansubmit=htmlspecialchars(mysql_real_escape_string(trim($_GET['scansubmit'])), ENT_QUOTES); 
     } else { $scansubmit=""; }
     
     break;
}

if ( $critical ) {
     $query_critical = "AND risk <= '$critical'";
}


$dbconn->disconnect();
$dbconn = $db->connect();

$dbconn->SetFetchMode(ADODB_FETCH_BOTH);

$version = $conf->get_conf("ossim_server_version");

list($arruser, $user) = Vulnerabilities::get_users_and_entities_filter($dbconn);

$ipl = $_GET['ipl'];
$treport = $_GET['treport'];
$key = $_GET['key'];

ossim_valid($scantime, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Scantime"));
if (ossim_error()) {
    die(_("Invalid Scantime"));
}
ossim_set_error(false);

ossim_valid($scantype, OSS_ALPHA, 'illegal:' . _("Scan Type"));
if (ossim_error()) {
    die(_("Invalid Scan Type"));
}
ossim_set_error(false);

ossim_valid($key, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Key"));
if (ossim_error()) {
    die(_("Invalid Key"));
}

// Check if exists _feed tables
$query   = "SELECT sid FROM vuln_nessus_reports WHERE report_id in ($report_id)";
$profile = $dbconn->GetOne( $query );
$feed    = ($profile=="-1" && exists_feed_tables($dbconn)) ? "_feed" : "";
    
$perms_where = (Session::get_ctx_where() != "") ? " AND ctx in (".Session::get_ctx_where().")" : "";

$dbconn->SetFetchMode(ADODB_FETCH_BOTH);

if ($scansubmit!="") {
    $query = "SELECT r.report_id FROM vuln_nessus_reports r,vuln_jobs j 
              WHERE r.report_id=j.report_id AND j.scan_SUBMIT='$scansubmit'
              AND scantype='$scantype'".((empty($arruser)) ? "":" AND r.username in ($user)");
    $result=$dbconn->execute($query);
    while ( !$result->EOF ) {
        list( $report_id ) = $result->fields;
        $ids[] = $report_id;
        $result->MoveNext();
    }
       $report_id = implode(",",$ids);
}
else {
    $query = "SELECT report_id FROM vuln_nessus_reports WHERE report_key='$key' AND scantime='$scantime'
            AND scantype='$scantype' ".((empty($arruser))? "":" AND username in ($user)")." LIMIT 1";

    $result=$dbconn->execute($query);
    list ( $report_id ) = $result->fields;
}

//Generated date
$gendate = gmdate("Y-m-d H:i:s",gmdate("U")+3600*$tz);


if ( ! $report_id ) {
    //logAccess( "ATTEMPT TO ACCESS INVALID NESSUS REPORT" );
    die(_("Report not found"));
}

$query = "select count(scantime) from vuln_nessus_results where report_id in ($report_id) and falsepositive='N'";
       
$result=$dbconn->execute($query);
list ( $numofresults ) = $result->fields;

if ($numofresults<1) {
    //logAccess( "NESSUS REPORT [ $report_id / NO RESULTS ] ACCESSED" );
    die(_("No vulnerabilities recorded"));
}

$tz = Util::get_timezone();

if($tz==0) {
    $localtime = $scantime;
}
else {
    $localtime = gmdate("YmdHis",Util::get_utc_unixtime($scantime)+3600*$tz);
}

$scanyear  = substr($localtime, 0, 4);
$scanmonth = substr($localtime, 4, 2);
$scanday   = substr($localtime, 6, 2);
$scanhour  = substr($localtime, 8, 2);
$scanmin   = substr($localtime, 10, 2);
$scansec   = substr($localtime, 12);

$query = "select distinct t1.username, t3.name, t2.name, t2.description
    FROM vuln_nessus_reports t1
    LEFT JOIN vuln_nessus_settings t2 on t1.sid=t2.id
    LEFT JOIN vuln_jobs t3 on t3.report_id = t1.report_id
    where t1.report_id in ($report_id) $query_host  ".((empty($arruser))? "":" AND t1.username in ($user)")."
    order by scantime DESC";

$result = $dbconn->execute($query);
    
list($query_uid, $job_name, $profile_name, $profile_desc) = $result->fields;
if($job_name=="") { // imported report
   $query_imported_report = "SELECT name FROM vuln_nessus_reports WHERE scantime='$scantime' and report_key='$key'"; 
   $result_imported_report=$dbconn->execute($query_imported_report);
   $job_name = $result_imported_report->fields["name"];
}
  
// Create new PHPExcel object
$objPHPExcel = new PHPExcel();

$dataArray   = array();

// Set document properties
$objPHPExcel->getProperties()->setCreator("AlienVault");

// Default font size and style alignment
$objPHPExcel->getDefaultStyle()->getFont()->setSize(14);
$objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);

$titles = array(
    'font'  => array(
        'bold'  => true

    )
);

// Report details

$objPHPExcel->setActiveSheetIndex(0)->mergeCells("A1:M1")->setCellValue('A1', $siteBranding . ': I.T Security Vulnerabiliy Report')
    ->getStyle('A1')->applyFromArray($titles);

$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A2', 'Scan time')
    ->getStyle('A2')->applyFromArray($titles);

$objPHPExcel->setActiveSheetIndex(0)->mergeCells("B2:M2")->setCellValue('B2', $scanyear . '-' . $scanmonth . '-' . $scanday . ' ' . $scanhour . ':' .$scanmin . ':' . $scansec);            

$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A3', 'Generated')
    ->getStyle('A3')->applyFromArray($titles);

$objPHPExcel->setActiveSheetIndex(0)->mergeCells("B3:M3")->setCellValue('B3', $gendate);

$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A4', 'Profile')
    ->getStyle('A4')->applyFromArray($titles);

$objPHPExcel->setActiveSheetIndex(0)->mergeCells("B4:M4")->setCellValue('B4', $profile_name . ' - ' . $profile_desc);

$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A5', 'Job Name')
            ->getStyle('A5')->applyFromArray($titles);

$objPHPExcel->setActiveSheetIndex(0)->mergeCells("B5:M5")->setCellValue('B5', $job_name);

// Headers

$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A6', 'Hostname')
    ->getStyle('A6')->applyFromArray($titles)
    ->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('A')->setWidth(25); 

$objPHPExcel->setActiveSheetIndex(0)->setCellValue('B6', 'Host IP')
    ->getStyle('B6')->applyFromArray($titles)
    ->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('B')->setWidth(25); 

$objPHPExcel->setActiveSheetIndex(0)->setCellValue('C6', 'Service')
    ->getStyle('C6')->applyFromArray($titles)
    ->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('C')->setWidth(20);

$objPHPExcel->setActiveSheetIndex(0)->setCellValue('D6', 'Vuln ID')
    ->getStyle('D6')->applyFromArray($titles)
    ->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->setActiveSheetIndex(0)->setCellValue('E6', 'CVSS')
    ->getStyle('E6')->applyFromArray($titles)
    ->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->setActiveSheetIndex(0)->setCellValue('F6', 'CVEs')
    ->getStyle('F6')->applyFromArray($titles)
    ->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->setActiveSheetIndex(0)->setCellValue('G6', 'Risk Level')
    ->getStyle('G6')->applyFromArray($titles)
    ->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->setActiveSheetIndex(0)->setCellValue('H6', 'Vulnerability')
    ->getStyle('H6')->applyFromArray($titles)
    ->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('H')->setWidth(40);

$objPHPExcel->setActiveSheetIndex(0)->setCellValue('I6', 'Observation')
    ->getStyle('I6')->applyFromArray($titles)
    ->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->setActiveSheetIndex(0)->setCellValue('J6', 'Remedation')
    ->getStyle('J6')->applyFromArray($titles)
    ->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('J')->setWidth(35);

$objPHPExcel->setActiveSheetIndex(0)->setCellValue('K6', 'Consequences')
    ->getStyle('K6')->applyFromArray($titles)
    ->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('K')->setWidth(25);

$objPHPExcel->setActiveSheetIndex(0)->setCellValue('L6', 'Test Output')
    ->getStyle('L6')->applyFromArray($titles)
    ->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->setActiveSheetIndex(0)->setCellValue('M6', 'Operating System/Software')
    ->getStyle('M6')->applyFromArray($titles)
    ->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('M')->setWidth(25);


    $query = "select distinct hostip, HEX(ctx) as ctx from vuln_nessus_results where report_id in ($report_id) $query_host and falsepositive='N' $perms_where order by INET_ATON(hostip) ASC";
    
    $result = $dbconn->execute($query);

    while ( list($hostip, $ctx) = $result->fields ) {

        $query1 = "select distinct t1.hostIP, HEX(t1.ctx) AS ctx, t1.service, t1.risk, t1.falsepositive, t1.scriptid, v.name, t1.msg, v.cve_id FROM vuln_nessus_results t1
                 LEFT JOIN vuln_nessus_plugins$feed as v ON v.id=t1.scriptid
                 WHERE 1=1 AND report_id in ($report_id) and t1.hostip='$hostip' and t1.ctx=UNHEX('$ctx') $perms_where and t1.msg<>'' and t1.falsepositive<>'Y'
                 order by t1.risk ASC, t1.risk ASC";

        
        $result1 = $dbconn->execute($query1);
        $arrResults="";

        while ( list( $hostip, $hostctx, $service, $risk, $falsepositive, $scriptid, $pname, $msg, $cve_id) = $result1->fields ){
        
            $row = array();
          
            if(Session::hostAllowed_by_ip_ctx($dbconn, $hostip, $hostctx)) {
                  
                $host_id = key(Asset_host::get_id_by_ips($dbconn, $hostip, $hostctx));
                
                if(valid_hex32($host_id)) {
                    $hostname = Asset_host::get_name_by_id($dbconn, $host_id);
                }
                else
                {
                    $hostname = _('unknown');
                }
                                
                // get CVSS
                if( preg_match("/cvss base score\s*:\s(\d+\.?\d*)/i",$msg,$found) ) {
                    $cvss  = $found[1];
                }
                else {
                    $cvss  = "-";
                }
             
                // get CVEs
                if ($cve_id != '')
                {
                    $cves = $cve_id;
                }
                elseif (preg_match("/cve : (.*)\n?/i",$msg,$found))
                {
                    $cves  = str_replace(", ","<br />",$found[1]);
                }
                else
                {
                    $cves  = "-";
                }

                if($hostname=="") $hostname = "unknown";
                $tmpport1=preg_split("/\(|\)/",$service);
                if (sizeof($tmpport1)==1) { $tmpport1[1]=$tmpport1[0]; }
                $tmpport2=preg_split("/\//",$tmpport1[1]);
                $service_num=$tmpport2[0];
                $service_proto=$tmpport2[1];
                $risk_txt = getrisk($risk);
                
                $row[] = $hostname;
                $row[] = $hostip;
                $row[] = $service;

                $row[] = $scriptid;
             
                $row[] = $cvss;
                $row[] = $cves;
             
                $row[] = $risk_txt;
             
                $plugin_info = $dbconn->execute("SELECT t2.name, t3.name, t1.copyright, t1.summary, t1.version 
                                    FROM vuln_nessus_plugins$feed t1
                                    LEFT JOIN vuln_nessus_family$feed t2 on t1.family=t2.id
                                    LEFT JOIN vuln_nessus_category$feed t3 on t1.category=t3.id
                                    WHERE t1.id='$scriptid'"); 

                list($pfamily, $pcategory, $pcopyright, $psummary, $pversion) = $plugin_info->fields; 

                $pinfo = array();
                if ($pfamily!="")    { $pinfo[] = 'Family name: '.trim(strip_tags($pfamily));} 
                if ($pcategory!="")  { $pinfo[] = 'Category: '.trim(strip_tags($pcategory)); }
                if ($pcopyright!="") { $pinfo[] = 'Copyright: '.trim(strip_tags($pcopyright)); }
                if ($psummary!="")   { $pinfo[] = 'Summary: '.trim(strip_tags($psummary)); }
                if ($pversion!="")   { $pinfo[] = 'Version: '.trim(strip_tags($pversion)); }
             
                // Vulnerability
             
                if(count($pinfo)==0) {
                    $row[] =  "n/a";
                }
                else {
                    $row[] =  $pname."\n".implode("\n", $pinfo);
                }

                $dfields = extract_fields($msg);
                
                $row[] = (empty($dfields['Overview'])) ? 'n/a' : $dfields['Overview'];
             
                $row[] = (empty($dfields['Fix'])) ?      'n/a' : $dfields['Fix'];
                
                // Consequences
                
                $consequences = array(); 
                    
                $consequences[] = (empty($dfields['Description'])) ? '' : $dfields['Description'];
                $consequences[] = (empty($dfields['Vulnerability Insight'])) ? '' : $dfields['Vulnerability Insight'];
                $consequences[] = (empty($dfields['Impact'])) ? '' : _('Impact') . ': ' . $dfields['Impact'];
                $consequences[] = (empty($dfields['Impact Level'])) ? '' : _('Impact Level') . ': ' . $dfields['Impact Level'];
                $consequences[] = (empty($dfields['References'])) ? '' : _('Refererences') . ': ' . $dfields['References'];
                $consequences[] = (empty($dfields['See also'])) ? '' : _('See also') . ': ' . $dfields['See also'];
                
                $row[] = (trim(implode("\n", $consequences))== '' ) ? "n/a" : trim(implode("\n", $consequences));
                
                // Test output
   
                $test_output = array();

                $test_output[] = (empty($dfields['Plugin output'])) ? '' : $dfields['Plugin output'];
                
                $test_output[] = (empty($dfields['Information about this scan'])) ? '' : $dfields['Information about this scan'];

                $row[] = (trim(implode("\n", $test_output))== '' ) ? "n/a" : trim(implode("\n", $test_output));
                
                // Affected Software/OS

                $row[] = (empty($dfields['Affected Software/OS'])) ? 'n/a' : $dfields['Affected Software/OS'];
                
                $dataArray[] = $row;
             
            }
             $result1->MoveNext();
        }
        $result->MoveNext();
    }


$output_name = "ScanResult_" . $scanyear . $scanmonth . $scanday . "_" . str_replace(" ","",$job_name) . ".xls";
$output_name = preg_replace( '/-_/', "", $output_name);

$objPHPExcel->setActiveSheetIndex(0)->getStyle('H')->getAlignment()->setWrapText(TRUE);
$objPHPExcel->setActiveSheetIndex(0)->getStyle('I')->getAlignment()->setWrapText(TRUE);
$objPHPExcel->setActiveSheetIndex(0)->getStyle('J')->getAlignment()->setWrapText(TRUE);
$objPHPExcel->setActiveSheetIndex(0)->getStyle('K')->getAlignment()->setWrapText(TRUE);
$objPHPExcel->setActiveSheetIndex(0)->getStyle('L')->getAlignment()->setWrapText(TRUE);
$objPHPExcel->setActiveSheetIndex(0)->getStyle('M')->getAlignment()->setWrapText(TRUE);

$objPHPExcel->setActiveSheetIndex(0)->fromArray($dataArray, NULL, 'A7');

// Rename worksheet
$objPHPExcel->getActiveSheet()->setTitle('Vulnerability Report');


// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$objPHPExcel->setActiveSheetIndex(0);


// Redirect output to a client web browser (Excel5)
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="'.$output_name.'"');
header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
header ('Pragma: public'); // HTTP/1.0

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');

$dbconn->disconnect();

exit;

function get_msg($dbconn,$query_msg) {
    $result=$dbconn->execute($query_msg);
    return ($result->fields["msg"]);
}
?>
