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
// $Id: respdf.php,v 1.13 2010/04/26 16:08:21 josedejoses Exp $
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

ini_set("max_execution_time","720");

define('FPDF_FONTPATH','inc/font/');

require_once 'av_init.php';
require_once 'inc/pdf.php';
require_once 'config.php';
require_once 'functions.inc';
require_once 'ossim_sql.inc';

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

$critical = 0;
$arrResults = array();
$getParams = array( "scantime", "scansubmit", "key", "critical", "filterip", "summary" );

$dbconn->disconnect();
$dbconn = $db->connect();

$dbconn->SetFetchMode(ADODB_FETCH_BOTH);

$version = $conf->get_conf("ossim_server_version", FALSE);

list($arruser, $user) = Vulnerabilities::get_users_and_entities_filter($dbconn);

$post = FALSE;

switch ($_SERVER['REQUEST_METHOD'])
{
case "GET" :
   foreach($getParams as $gp) {
	   if (isset($_GET[$gp])) { 
         $$gp=Util::htmlentities(escape_sql(trim($_GET[$gp]), $dbconn));
      } else {
         $$gp = "";
      }
   }
	break;
}

ossim_valid($scantime, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Scantime"));
if (ossim_error()) {
    die(_("Invalid Scantime"));
}
ossim_set_error(false);

ossim_valid($scantype, OSS_NULLABLE, OSS_ALPHA, 'illegal:' . _("Scan Type"));
if (ossim_error()) {
    die(_("Invalid Scan Type"));
}
ossim_set_error(false);

ossim_valid($key, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Key"));
if (ossim_error()) {
    die(_("Invalid Key"));
}

$query_by_user  = (!empty($arruser))? " AND t1.username in ($user) " : "";

#FOUND NEEDED THE SESSION CODE LOCAL TO BY PASS NEED FOR AUTH TO FIX CODE TO ALLOW ACCESS FOR 30
#DAYS PER REPORT KEY
session_cache_limiter('none');
session_start();

//Seperates the parts of the date so it doesn't just display it as one big number

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

//Generated date
$gendate = gmdate("Y-m-d H:i:s",gmdate("U")+3600*$tz);

//here you can set the preference for the table colors
//$head_fill_color = array(100,149,237);

$dbconn->SetFetchMode(ADODB_FETCH_BOTH);

$links_to_vulns = array();

$query_critical="";
$query_host="";
if ( $critical ) {
    $query_critical = "AND t1.risk <= '$critical' ";
}
if ( $filterip ) {
    $query_host = "AND t1.hostIP='$filterip'";
}

if ($scansubmit!="") {
   $query = "SELECT r.report_id FROM vuln_nessus_reports t1,vuln_jobs j
           WHERE t1.report_id=j.report_id
           AND j.scan_SUBMIT='$scansubmit'
           $query_by_user";
           
   $result=$dbconn->execute($query);
   $ids = array();
   while ( !$result->EOF ) {
        $report_id = $result->fields['report_id'];
        $ids[] = $report_id;
        $result->MoveNext();
   }
   $report_id = implode(",",$ids);
}
else if (empty($report_id)) {
    $query = "SELECT report_id FROM vuln_nessus_reports t1
                WHERE t1.report_key=$key
                AND t1.scantime='$scantime'
                $query_by_user LIMIT 1";
                
    $result=$dbconn->execute($query);
    $report_id = $result->fields['report_id'];
}

if ( empty($report_id) ) 
{
    //logAccess( "ATTEMPT TO ACCESS INVALID PDF REPORT" );
	?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html>
    <head>
		<title> <?php echo gettext("Vulnmeter"); ?> </title>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
		<META http-equiv="Pragma" CONTENT="no-cache"/>
		<link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    </head>
    <body>
		<?php
        echo "<table width=\"100%\" class=\"noborder transparent\" >";
        echo "<tr><td class=\"nobborder\" heigh=\"15\">&nbsp;</td></tr>";
        echo "<tr><td class=\"nobborder\" style=\"text-align:center;\">";
        echo _("Report not found");
        echo "</td></tr>";
        echo "</table>";
		?>
    </body>
    <?php
    exit(0);
}

$query = "select count(scantime) as count from vuln_nessus_results t1
       where report_id  in ($report_id) and falsepositive='N'";

$result=$dbconn->execute($query);

$numofresults = $result->fields['count'];

if ( $numofresults < 1 ) 
{
    die(_("No vulnerabilities recorded"));
}

$chinese = false;    // the language is not chinese by default

//start pdf file, add page, set font
$pdf = new Pdf();
$pdf->AddGBFont();
$pdf->AddBig5Font();
$pdf->AddPage();

$pdf->SetFont('Helvetica', 'B', 13);
if(Session::is_pro())
{
    if ($siteLogo != '')
    {
        $pdf->Image($siteLogo,10,11,40);
    }
    else
    {
       $pdf->Image("../pixmaps/logo_siempdf.png",10,11,40);
    }
}
else {
    $pdf->Image("../pixmaps/logo_ossimpdf.png",10,11,40);
}
$pdf->Ln();
$pdf->Cell(0, 16, "    $siteBranding: I.T Security Vulnerability Report", 1, 1, 'C', 0);

$scan_time = _("Scan time");
$generated = _("Generated");

if ( preg_match('/&#(\d{4,5});/',$scan_time) )
{
    $scan_time = mb_convert_encoding($scan_time,'Big5','HTML-ENTITIES');
    $generated = mb_convert_encoding($generated,'Big5','HTML-ENTITIES');
    $pdf->SetFont('Big5', '', 9);
    $chinese = true;
}
else
{
    $scan_time = mb_convert_encoding($scan_time,'ISO-8859-1','HTML-ENTITIES');
    $generated = mb_convert_encoding($generated,'ISO-8859-1','HTML-ENTITIES');
    $pdf->SetFont('Helvetica', '', 9);
}


$job_label = _("Job Name:");


if (!empty($report_id)) {

    // Check if exists _feed tables
    $query   = "SELECT sid FROM vuln_nessus_reports WHERE report_id in ($report_id)";
    $profile = $dbconn->GetOne( $query );
    $feed    = ($profile=="-1" && exists_feed_tables($dbconn)) ? "_feed" : "";

    $query ="SELECT t1.username, t1.name as jobname, t2.name as profilename, t2.description
        FROM vuln_jobs t1
        LEFT JOIN vuln_nessus_settings t2 on t1.meth_VSET=t2.id
        WHERE t1.report_id in ($report_id) 
        $query_host 
        order by t1.SCAN_END DESC";
        
    $result=$dbconn->execute($query);

    $query_uid    = $result->fields['username'];
    $job_n        = $result->fields['jobname'];
    $profile_name = $result->fields['profilename'];
    $profile_desc = $result->fields['description'];
    
    
	//$pdf->Cell(70,6,"Owner: $query_uid",1,1,'L');
    
    if($job_n=="")
	{ 
		// imported report
       $query_imported_report   = "SELECT name FROM vuln_nessus_reports WHERE scantime='$scantime' and report_key='$key'"; 
       $result_imported_report  = $dbconn->execute($query_imported_report);
       $job_n                = $result_imported_report->fields["name"];
    }
}

$job_n     = (empty($job_n)) ? "-" : $job_n;    

if ( $chinese )
{
    $job_n     = mb_convert_encoding($job_n,'Big5','HTML-ENTITIES');
    $job_label = mb_convert_encoding($job_label,'Big5','HTML-ENTITIES');
    $pdf->SetFont('Big5', '', 9);
}
else
{
    $job_n = mb_convert_encoding($job_n,'ISO-8859-1','HTML-ENTITIES');
    $job_label = mb_convert_encoding($job_label,'ISO-8859-1','HTML-ENTITIES');
    $pdf->SetFont('Helvetica', '', 9);
}


$pdf->Cell(30,6,$job_label,1,0,'L');
$pdf->Cell(98,6,$job_n,1,0,'L');

$pdf->Cell(30,6,$scan_time.":",1,0,'L');
$pdf->Cell(32,6, "$scanyear-$scanmonth-$scanday $scanhour:$scanmin:$scansec",1,1,'L');

if (!empty($report_id)) {
    $profile_label = _("Profile");
    
    if(empty($profile_name)) $profile_name = "-";
    $profile_data  = $profile_name;
    $profile_data .= ( $profile_desc != '' ) ? " - $profile_desc" : "";
    
    $profile_data  = (strlen($profile_data) > 58) ? substr($profile_data, 0, 56)."..." : $profile_data; 

    if ( $chinese )
    {
        $profile_label = mb_convert_encoding($profile_label,'Big5','HTML-ENTITIES');
        $profile_data = mb_convert_encoding($profile_data,'Big5','HTML-ENTITIES');
        $pdf->SetFont('Big5', '', 9);
    }
    else
    {
        $profile_label = mb_convert_encoding($profile_label,'ISO-8859-1','HTML-ENTITIES');
        $profile_data = mb_convert_encoding($profile_data,'ISO-8859-1','HTML-ENTITIES');
        $pdf->SetFont('Helvetica', '', 9);
    }
	$pdf->Cell(30,6,$profile_label.":",1,0,'L');
	$pdf->Cell(98,6, $profile_data,1,0,'L');
    
	//$pdf->Cell(70,6,"Owner: $query_uid",1,1,'L');

    $pdf->Cell(30,6,$generated.":",1,0,'L');
    $pdf->Cell(32,6, "$gendate",1,1,'L');

    //$pdf->Cell(0, 6, "Subnet Description: $sub_desc", 1, 0, 'L');
#    $pdf->Ln();

    $pdf->SetFont('Helvetica', '', 10);

    //get current possition so we can put the pie chart to the side
    $valX = $pdf->GetX();
    $valY = $pdf->GetY();
    $riskarray = array(); //array of risks for pie chart
    $colorarray=array(); //array of colors for pie chart

    //$query = "SELECT DISTINCT hostIP, hostname FROM vuln_nessus_results t1
    //    where report_id='$report_id' $query_host $query_critical order BY hostIP";

    $perms_where    = (Session::get_ctx_where() != "") ? " AND t1.ctx in (".Session::get_ctx_where().")" : "";
    
    $query= "SELECT DISTINCT t1.hostip as hostIP, HEX(t1.ctx) as ctx
             FROM vuln_nessus_results t1
             where
             t1.report_id in ($report_id) 
             $query_host
             $query_critical 
             and falsepositive='N'
             $perms_where
             order BY hostIP";
    
    $result = $dbconn->execute($query);

    //initialise variable for number of hosts while loop
    $hosts = array();
    while ($row = $result->fields) {
        if(Session::hostAllowed_by_ip_ctx($dbconn, $row['hostIP'], $row['ctx'])) {
        
            $host_id = key(Asset_host::get_id_by_ips($dbconn, $row['hostIP'], $row['ctx'])); 
            
            if(valid_hex32($host_id))
            {
                $hostname = Asset_host::get_name_by_id($dbconn, $host_id);
            }
            else
            {
                $hostname = _('unknown');
            }

            $hosts[$row['hostIP']."#". $row['ctx']]=$hostname;
        }
        $result->MoveNext();
    }

    $query = "SELECT COUNT( risk ) AS count, risk, hostIP, ctx FROM 
                    (SELECT DISTINCT t1.hostIP, HEX(t1.ctx) AS ctx, t1.risk, t1.port, t1.protocol, t1.app, t1.scriptid, t1.msg
                        FROM vuln_nessus_results t1
                        WHERE report_id in ($report_id)
                        $query_host 
                        $perms_where
                        AND t1.falsepositive<>'Y'
                    ) as t GROUP BY risk";

    $ecount = 0;
    $result = $dbconn->Execute($query);

    $index = 0;
    $pdf->Ln();
    
    $total_number1 = _("Total number of vulnerabilities identified on ")." ".sizeof($hosts). _(" system(s)");
    
    if ( $chinese )
    {
        $total_number1 = mb_convert_encoding($total_number1,'Big5','HTML-ENTITIES');
        $pdf->SetFont('Big5', '', 12);
    }
    else
    {
        $total_number1 = mb_convert_encoding($total_number1,'ISO-8859-1','HTML-ENTITIES');
        $pdf->SetFont('Helvetica', 'B', 12);
    }

    $pdf->Cell(0, 6,$total_number1,0,1,'C');
    $pdf->Rect(10,44,190,52);
    $pdf->Ln();
    $pdf->SetFont('Arial', '', 10);

    $Eriskcount = 0;
    $riskArray = array();
    $colorarray = array();
    $risk_in_graph = array();

    while ($result->fields)
    {
        $riskcount = $result->fields['count'];
        $risk      = $result->fields['risk'];
        $hostIP    = $result->fields['hostIP'];
        $ctx       = $result->fields['ctx'];
    
        if(Session::hostAllowed_by_ip_ctx($dbconn, $hostIP, $ctx)) {
            //$pdf->MultiCell(70, 6, "".getrisk($risk)." : $riskcount" ,0,0,'C');
            $riskArray [getrisk($risk)] += $riskcount;
        
            if( !array_key_exists($risk, $risk_in_graph) ) {
                $colorarray[$index] = getriskcolor($risk);
                $risk_in_graph[$risk] = 1;
                 $index++;
            }

        }
        $result->MoveNext();
    }

    $pdf->Ln();
    $pdf->Ln();

    //$pdf->SetXY(85, 53); #$valY);
    $pdf->SetXY(85, 48); #$valY);
    $pdf->PieChart(140, 40, $riskArray, '%l', $colorarray);
    $pdf->SetXY($valX, $valY + 31);

    //Host-Vulnerability Summary
    $pdf->Ln();
    $pdf->Ln();
    $pdf->Ln();

   
    $total_number2 = _("Total number of vulnerabilities identified per system");
    
    if ( $chinese )
    {
        $total_number2 = mb_convert_encoding($total_number2,'Big5','HTML-ENTITIES');
        $pdf->SetFont('Big5', '', 10);
    }
    else
    {
        $total_number2 = mb_convert_encoding($total_number2,'ISO-8859-1','HTML-ENTITIES');
        $pdf->SetFont('Helvetica', 'B', 12);
    }
    
    $pdf->Cell(0, 10, $total_number2,0,1,'C');    

    $size= 12 + (6 * sizeof($hosts));
    $pdf->Rect(10,94,190,$size);
    
    $HostIP   = _("HostIP");
    $HostName = _("HostName");
    $Serious  = _("Serious");
    $High     = _("High");
    $Med      = _("Med");
    $Low      = _("Low");
    $Info     = _("Info");
    
    if ( $chinese )
    {
        $HostIP   = mb_convert_encoding($HostIP,'Big5','HTML-ENTITIES');
        $HostName = mb_convert_encoding($HostName,'Big5','HTML-ENTITIES');
        $Serious  = mb_convert_encoding($Serious,'Big5','HTML-ENTITIES');
        $High     = mb_convert_encoding($High,'Big5','HTML-ENTITIES');
        $Med      = mb_convert_encoding($Med,'Big5','HTML-ENTITIES');
        $Low      = mb_convert_encoding($Low,'Big5','HTML-ENTITIES');
        $Info     = mb_convert_encoding($Info,'Big5','HTML-ENTITIES');
        
        $pdf->SetFont('Big5', '', 10);
    }
    else
    {
        $HostIP   = mb_convert_encoding($HostIP,'ISO-8859-1','HTML-ENTITIES');
        $HostName = mb_convert_encoding($HostName,'ISO-8859-1','HTML-ENTITIES');
        $Serious  = mb_convert_encoding($Serious,'ISO-8859-1','HTML-ENTITIES');
        $High     = mb_convert_encoding($High,'ISO-8859-1','HTML-ENTITIES');
        $Med      = mb_convert_encoding($Med,'ISO-8859-1','HTML-ENTITIES');
        $Low      = mb_convert_encoding($Low,'ISO-8859-1','HTML-ENTITIES');
        $Info     = mb_convert_encoding($Info,'ISO-8859-1','HTML-ENTITIES');
        
        $pdf->SetFont('Helvetica', '', 10);
    }
        
    $pdf->SetFillColor(238, 238, 238);
    $pdf->Cell(28, 6, $HostIP,1,0,'C',1);
    $pdf->Cell(52, 6, $HostName,1,0,'C',1);
    //$pdf->Cell(20, 6, "LocalChks",1,0,'C');
    $pdf->Cell(22, 6, $Serious,1,0,'C',1);
    $pdf->Cell(22, 6, $High,1,0,'C',1);
    $pdf->Cell(22, 6, $Med,1,0,'C',1);
    $pdf->Cell(22, 6, $Low,1,0,'C',1);
    $pdf->Cell(22, 6, $Info,1,0,'C',1);
    //$pdf->Cell(20, 6, "Exceptions",1,0,'C');
    $pdf->Ln();

    foreach ($hosts as $hostIP_ctx=>$hostname) {
        list($hostIP, $hostctx) = explode("#", $hostIP_ctx);
        ${"IP_".$hostIP_ctx}=$pdf->AddLink();

        $pdf->Cell(28, 6, $hostIP, 1, 0, 'C', 0, ${"IP_".$hostIP_ctx});
        $pdf->Cell(52, 6, $hostname, 1, 0, 'C', 0, ${"IP_".$hostIP_ctx});
        //$pdf->Cell(20, 6, $check_text,1,0,'C');
        $host_risk = array ( 0,0,0,0,0,0,0,0);

        $query1 = "SELECT COUNT( risk ) AS count, risk FROM (
                        SELECT DISTINCT risk, port, protocol, app, scriptid, msg, hostIP
                        FROM vuln_nessus_results t1
                        WHERE t1.report_id in ($report_id)
                        AND t1.ctx=UNHEX('$hostctx')
                        AND t1.hostIp='$hostIP'
                        $perms_where
                        $query_critical
                        and t1.falsepositive <> 'Y') as t group by hostIP, risk";

       $ecount = 0;
       $result1 = $dbconn->Execute($query1);

       $prevrisk=0;
       $Eriskcount=0;

       while($result1->fields)
       {
          $riskcount = $result1->fields['count'];
          $risk      = $result1->fields['risk'];
           
       	  if ( $ecount > 0 ) {
             $Eriskcount += $ecount;
             $riskcount -= $ecount;
          }
          $host_risk[$risk] = $riskcount;

          $prevrisk=$risk;
          $result1->MoveNext();
       }
       $host_risk[8] = $Eriskcount;

       //$arrrisks = array( "1", "2", "3", "6", "7", "8" );
       $arrrisks = array( "1", "2", "3", "6", "7");
       $r2n      = array( "Serious" => "1", "High" => "2", "Med" => "3", "Low" => "6", "Info" => "7");
       
       foreach ( $arrrisks as $rvalue ) {
       	  $value = "--";
       	  $width = "22";
          if ( $host_risk[$rvalue] > 0  ) {
                $value = $host_risk[$rvalue];
                $links_to_vulns[$hostIP_ctx][$rvalue] = $pdf->AddLink();
          }
          if ( $rvalue == 8 ) { $width = "20"; }
          
          if ($links_to_vulns[$hostIP_ctx][$rvalue] != "") {
                $pdf->Cell( $width, 6, $value , 1, 0, 'C', 0, $links_to_vulns[$hostIP_ctx][$rvalue]);
          }
          else {
                $pdf->Cell( $width, 6, $value , 1, 0, 'C');
          }
       }
       $pdf->Ln();
   }

   $pdf->Ln();$pdf->Ln();

   if ( $summary == "1" ) {
   	   //output the pdf, now we're done$pdf-
   	   header("Cache-Control: public, must-revalidate");
   	   header("Pragma: ");
       header('Content-Type: application/pdf');
       //header("Content-disposition:  attachment; filename=scanresults-$uid-$scantime.pdf");
       $pdf->Output("scanresults-$uid-$scantime.pdf","I");
       exit;
   }

   $query = "SELECT distinct t1.hostIP, HEX(t1.ctx) as ctx, t1.service, t1.port, t1.protocol, t1.app, t1.risk, t1.scriptid, v.name, t1.msg
             FROM vuln_nessus_results t1
             LEFT JOIN vuln_nessus_plugins$feed as v ON v.id=t1.scriptid
             WHERE t1.report_id in ($report_id)
             $query_host
             $query_critical
             $perms_where
             and t1.falsepositive<>'Y'
             ORDER BY INET_ATON(t1.hostIP) ASC, t1.risk ASC";


   $eid = "";
   $result=$dbconn->Execute($query);
   
   $arrResults = array();

   while($result->fields)
   {
    $hostIP        = $result->fields['hostIP'];
    $hostctx       = $result->fields['ctx'];
    $service       = $result->fields['service'];
    $service_num   = $result->fields['port'];
    $service_proto = $result->fields['protocol'];
    $app           = $result->fields['app'];
    $risk          = $result->fields['risk'];
    $scriptid      = $result->fields['scriptid'];
    $pname         = $result->fields['name'];
    $msg           = $result->fields['msg'];
       
    if(Session::hostAllowed_by_ip_ctx($dbconn, $hostIP, $hostctx)) {
      $arrResults[$hostIP."#".$hostctx][]=array(
          'service'  => $service,
             'port'  => $service_num, 
         'protocol'  => $service_proto, 
      'application'  => $app,  
             'risk'  => $risk,
         'scriptid'  => $scriptid,
        'exception'  => $eid, 
              'msg'  => preg_replace('/(<br\\s*?\/??>)+/i', "\n", $msg),
              'pname'=> $pname);
    }
    $result->MoveNext();
   }

 //Vulnerability table configs
   $vcols = array(_("Risk"), _("Details"));
   //widths for columns
   $vwidth_array=array(20, 170); // 196 total

   $count=0;
   $oldip="";
   // iterate through the IP is the results
   
    foreach ($arrResults as $hostIP_ctx=>$scanData) {
        list($hostIP, $hostctx) = explode("#", $hostIP_ctx);
        
        $host_id = key(Asset_host::get_id_by_ips($dbconn, $hostIP, $hostctx));
        
        if(valid_hex32($host_id)) {
            $hostname = Asset_host::get_name_by_id($dbconn, $host_id);
        }
        else {
            $hostname = _('unknown');
        }
         
        $hostIP=htmlspecialchars_decode($hostIP);
        $hostname=htmlspecialchars_decode($hostname);
      
        $pdf->SetLink(${"IP_".$hostIP_ctx},$pdf->GetY());
        
        //print out the host cell

        $pdf->SetFillColor(229, 229, 229);
        $pdf->SetFont('','B',10);
        $pdf->Cell(95, 6, $hostIP,1,0,'C',1);
        $pdf->Cell(95, 6, $hostname,1,0,'C',1);
        //$pdf->Cell(105, 6, "",1,0,'C');
        $pdf->SetFont('','');
        $pdf->Ln();
            
        // now iterate through the scan results for this IP
        $all_results = array();
        foreach($scanData as $vuln) {
            $exception = ""; 
            $risk_value = $vuln['risk'];
            $actual_risk = getrisk($risk_value);

            if ( $vuln['exception'] != ""  ) { 
                $exception = "\n"._("EXCEPTION").": $vuln[exception]\n";
                $risk_value = 8;
            }

            $risk = getrisk($risk_value);

             $info = "";
             
            if ($exception!="") {
               $info  .= "\n$exception"; 
            }
            $info .= "\n".$vuln["pname"];
            $info .= "\nRisk: ". $actual_risk;
            $info .= "\nApplication: ".$vuln["application"];
            $info .= "\nPort: ".$vuln["port"];
            $info .= "\nProtocol: ".$vuln["protocol"];
            $info .= "\nScriptID: ".$vuln["scriptid"]."\n\n";

            #$info=htmlspecialchars_decode($info);
            $msg=trim($vuln['msg']);
            $msg=htmlspecialchars_decode($msg);
            $msg=preg_replace('/^\n+/','',$msg);
            $msg= str_replace("&amp;","&", $msg);
            $msg= str_replace("&#039;","'", $msg);
            $msg = str_replace("\\r", "", $msg);
            $info .= $msg;
            
            $plugin_info = $dbconn->execute("SELECT t2.name as familyname, t3.name as categoryname, t1.copyright, t1.summary, t1.version 
                    FROM vuln_nessus_plugins$feed t1
                    LEFT JOIN vuln_nessus_family$feed t2 on t1.family=t2.id
                    LEFT JOIN vuln_nessus_category$feed t3 on t1.category=t3.id
                    WHERE t1.id='".$vuln["scriptid"]."'");

            $pfamily    = $plugin_info->fields['familyname'];
            $pcategory  = $plugin_info->fields['categoryname'];
            $pcopyright = $plugin_info->fields['copyright'];
            $psummary   = $plugin_info->fields['summary'];
            $pversion   = $plugin_info->fields['version'];
            
            
            $info .= "\n";
            if ($pfamily!="")    { $info .= "\nFamily name: ".$pfamily;} 
            if ($pcategory!="")  { $info .= "\nCategory: ".$pcategory; }
            if ($pcopyright!="") { $info .= "\nCopyright: ".$pcopyright; }
            if ($psummary!="")   { $info .= "\nSummary: ".$psummary; }
            if ($pversion!="")   { $info .= "\nVersion: ".$pversion; }

            
            if ($risk=="Serious") {
                $pdf->SetFillColor(255, 205, 255);
            }
            else if($risk=="High") {
                $pdf->SetFillColor(255, 219, 219);
            }
            else if($risk=="Medium") {
                $pdf->SetFillColor(255, 242, 131);
            }
            else if($risk=="Low") {
                $pdf->SetFillColor(255, 255, 192);
            }
            else {
                $pdf->SetFillColor(255, 255, 227);
            }
            
            $pdf->SetWidths(array($vwidth_array[0]+$vwidth_array[1])); 
            
            $info = trim($info);
            $info = preg_replace("/^\s*/", "", $info);
            $info = preg_replace("/\n{2,}/","\n",$info);
            $info = preg_replace("/^/m", "\t\t\t\t\t", $info);
            
            if(!is_null($links_to_vulns[$hostIP_ctx][$r2n[$risk]])) {
                $link = $links_to_vulns[$hostIP_ctx][$r2n[$risk]]; 
                unset($links_to_vulns[$hostIP_ctx][$r2n[$risk]]);
            }
            else
                $link = null;
            
            $pdf->Row(array($risk.":\n\n".$info), $link);
        }
    }
    $pdf->Ln();
} 


header("Cache-Control: public, must-revalidate");
header("Pragma: ");
//output the pdf, now we're done$pdf-
header('Content-Type: application/pdf');
$output_name = "ScanResult_" . $scanyear . $scanmonth . $scanday . "_" . str_replace(" ","",$job_name) . ".pdf";
//header("Content-disposition:  attachment; filename=$output_name");
$pdf->Output($output_name,"I");


//matches risks number with colors
function getriskcolor($risk)
{
    switch ($risk)
    {
    case 1:
        $risk=array(200, 53, 237);
        //$risk=array(255,0,0);
        break;
    case 2:
        $risk=array(255,0,0);
        break;
    case 3:
        $risk=array(255, 165, 0);
        //$risk=array(255,255,0);
        break;
    case 4:
        $risk=array(255,255,0);
        break;
    case 5:
        $risk=array(255,255,0);
        break;
    case 6:
        $risk = array(255, 215, 0);
        //$risk=array(0,255,0);
        //$risk=array(0,139,69);
        break;
    case 7:
        $risk =array(240, 230, 140);
        //$risk=array(238, 238, 238);
        //$risk=array(100,149,237);
        break;
    case 8:
        $risk=array(255,153,0);
        break;        
    }
    return $risk;
}

$dbconn->disconnect();

?>
