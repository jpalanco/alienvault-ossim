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
// $Id: reshtml.php,v 1.12 2010/04/26 16:08:21 josedejoses Exp $
//

/***********************************************************/
/*                 Inprotect                      */
/* --------------------------------------------------------*/
/* Copyright (C) 2006 Inprotect                      */
/*                                          */
/* This program is free software; you can redistribute it  */
/* and/or modify it under the terms of version 2 of the    */
/* GNU General Public License as published by the Free     */
/* Software Foundation.                              */
/* This program is distributed in the hope that it will be */
/* useful, but WITHOUT ANY WARRANTY; without even the         */
/* implied warranty of MERCHANTABILITY or FITNESS FOR A    */
/* PARTICULAR PURPOSE. See the GNU General Public License  */
/* for more details.                                 */
/*                                          */
/* You should have received a copy of the GNU General         */
/* Public License along with this program; if not, write   */
/* to the Free Software Foundation, Inc., 59 Temple Place, */
/* Suite 330, Boston, MA 02111-1307 USA                 */
/*                                          */
/* Contact Information:                              */
/* inprotect-devel@lists.sourceforge.net                */
/* http://inprotect.sourceforge.net/                    */
/***********************************************************/
/* See the README.txt and/or help files for more        */
/* information on how to use & config.                  */
/* See the LICENSE.txt file for more information on the    */
/* License this software is distributed under.             */
/*                                          */
/* This program is intended for use in an authorized          */
/* manner only, and the author can not be held liable for  */
/* anything done with this program, code, or items      */
/* discovered with this program's use.                  */
/***********************************************************/
ini_set("max_execution_time","720");

require_once 'av_init.php';
require_once 'config.php';
require_once 'functions.inc';
require_once 'ossim_sql.inc';

Session::logcheck('environment-menu', 'EventsVulnerabilities');

$conf = $GLOBALS['CONF'];

function get_msg($dbconn, $query_msg) 
{
    $result = $dbconn->execute($query_msg);
    
    return ($result->fields['msg']);
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
    <title><?php echo gettext('Vulnmeter');?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache">
    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/greybox.js"></script>
    <script type="text/javascript" src="../js/utils.js"></script>
    <script src="/ossim/js/jquery.tipTip-ajax.js" type="text/javascript"></script>
    <script type="text/javascript" src="../js/jquery.simpletip.js"></script>
    <link rel="stylesheet" type="text/css" href="/ossim/style/tipTip.css"/>
    
    <?php require '../host_report_menu.php'?>
    
    <style type="text/css">
        .tooltip 
        {
            position: absolute;
            padding: 2px;
            z-index: 10;
            
            color: #303030;
            background-color: #f5f5b5;
            border: 1px solid #DECA7E;
            width:500px;
            
            font-family: arial;
            font-size: 11px;
        }
        .stats
        {
            border-top: 0px;
        }
    </style>
</head>

<body>
<?php
$pageTitle = gettext("HTML Results");

$getParams = array( "key", "ipl, treport", "disp", "op", "output", "scantime", "scansubmit", "scantype", "reporttype", "key", "sortby", "allres", "fp","nfp", "wh", "bg", "filterip", "critical", "increment", "pag", "chks" );
$postParams = array( "treport", "disp", "op", "output", "scantime", "scansubmit", "scantype", "fp","nfp", "filterip", "critical", "increment", "chks" );

$dbconn->close();
$dbconn = $db->connect();

switch ($_SERVER['REQUEST_METHOD'])
{
    case "GET" :
       foreach($getParams as $gp) 
       {
          if (isset($_GET[$gp])) 
          { 
             $$gp = Util::htmlentities(escape_sql(trim($_GET[$gp]), $dbconn));
          } 
          else 
          { 
             $$gp = ''; 
          }
       }
    break;
    
    case "POST" :
       foreach($postParams as $pp) 
       {
          if (isset($_POST[$pp])) 
          { 
             $$pp = Util::htmlentities(escape_sql(trim($_POST[$pp]), $dbconn));
          } 
          else 
          { 
             $$pp = ''; 
          }
       }
    break;
}

$version = $conf->get_conf('ossim_server_version');

$dbconn->SetFetchMode(ADODB_FETCH_BOTH);

if ($pag == '' || $pag < 1) 
{
    $pag = 1;
}

list($arruser, $user) = Vulnerabilities::get_users_and_entities_filter($dbconn);

$query_byuser = (empty($arruser)) ? '' : "and username in ($user)";

ossim_valid($treport, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _('Latest Report'));

if (ossim_error()) 
{
    ossim_error(_("Invalid Parameter treport"));
    exit();
}
ossim_set_error(FALSE);

ossim_valid($ipl, OSS_NULLABLE, OSS_DIGIT, OSS_ALPHA, 'illegal:' . _('IP latest'));
if (ossim_error()) 
{
    ossim_error(_("Invalid Parameter ipl"));
    exit();
}
ossim_set_error(FALSE);

ossim_valid($key, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _('Key'));
if (ossim_error()) 
{
    ossim_error(_("Invalid Parameter Key"));
    exit();
}
ossim_set_error(FALSE);

ossim_valid($disp, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _('Report Type'));
if (ossim_error()) 
{
    ossim_error(_("Invalid Report Type"));
    exit();
}
ossim_set_error(FALSE);

ossim_valid($output, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _('Output Type'));
if (ossim_error()) 
{
    ossim_error(_("Invalid Output Type"));
    exit();
}
ossim_set_error(FALSE);

ossim_valid($scantime, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _('Scantime'));
if (ossim_error()) 
{
    ossim_error(_("Invalid Scantime"));
    exit();
}
ossim_set_error(FALSE);

ossim_valid($scantype, OSS_ALPHA, 'illegal:' . _('Scan Type'));
if (ossim_error()) 
{
    ossim_error(_("Invalid Scan Type"));
    exit();
}

ossim_valid($chks, "t", "f", OSS_NULLABLE, 'illegal:' . _('Chks'));
if (ossim_error()) 
{
    ossim_error(_("Invalid Chks"));
    exit();    
}

ossim_valid($fp, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _('False positive'));
if (ossim_error()) 
{
    die(ossim_error());
}

ossim_valid($nfp, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _('No False positive'));
if (ossim_error()) 
{
    die(ossim_error());
}

?>
<script type="text/javascript">
  // GrayBox
    $(document).ready(function(){
        GB_TYPE = 'w';
        $("a.greybox").click(function(){
            var t = this.title || $(this).text() || this.href;
            GB_show(t,this.href,550,'90%');
            return false;
        });
        <?php 
        if (isset($chks)) 
        {
        // levels "Serious" => "1", "High" => "2", "Medium" => "3", "Low" => "6", "Info" => "7" 
        	  if (substr($chks,0,1)=="f") echo "$('#checkboxS').prop('checked', false); $('.risk1').hide();";
        	  if (substr($chks,1,1)=="f") echo "$('#checkboxH').prop('checked', false); $('.risk2').hide();";
        	  if (substr($chks,2,1)=="f") echo "$('#checkboxM').prop('checked', false); $('.risk3').hide();";
        	  if (substr($chks,3,1)=="f") echo "$('#checkboxL').prop('checked', false); $('.risk6').hide();";
        	  if (substr($chks,4,1)=="f") echo "$('#checkboxI').prop('checked', false); $('.risk7').hide();";
        } 
        ?> 
        // show/hide hosts
        $('.hostip').map(function(idx, element){
        	var vall = false;
        	$('tr.trsk',element).each(function(){
                //$(this).log($(this).css('display'))
                if ($(this).css('display')!='none') 
                {
                    vall = true;
                }
        	});
        	
            if (!vall)
            {    
                $(element).hide();
            }
            else
            {
                $(element).show();
            }
        });
        
        $('a.anchor_link').on('click', anchor_link);
        
    });
    
    function GB_onhide(url)
	{
		if (url.match(/newincident/))
		{
    		document.location.href="../incidents/index.php?m_opt=analysis&sm_opt=tickets&h_opt=tickets"
		}
	}
		
    function postload() 
    {      
        $('.scriptinfo').tipTip({
           defaultPosition: "top",
           delay_load: 100,
           maxWidth: "auto",
           edgeOffset: 3,
           keepAlive:true,
           content: function (e) {               
              var id = $(this).attr('lid');
          
               $.ajax({
                   type: 'GET',
                   data: 'id='+id,
                   url: 'lookup.php',
                   success: function (response) {                                                                                                                          
                       e.content.html(response); // the var e is the callback function data (see above)
                   }
               });
               return '<?php echo _("Searching")."..."?>'; // We temporary show a Please wait text until the ajax success callback is called.               
            }
         });
        
        $(".checkinfo").simpletip({
            position: 'top',
            baseClass: 'tooltip',
            onBeforeShow: function() { 
                this.update('<?=_("Click to enable/disable risk level view")?>');
            }
        });
    }


    function showFalsePositives() 
    {
        if ($('#checkboxFP').attr('checked'))
        {
            $('.fp').show();
        }
        else 
        {
            $('.fp').hide();
        }
    }

    
    function toggle_vulns(type)
    {
        if(type == "checkboxS")
        {
            if ($('#checkboxS').attr('checked')){
                $('.risk1').show();
            }
            else 
            {
                $('.risk1').hide();
            }
        }
        else if(type=="checkboxH")
        {
            if ($('#checkboxH').attr('checked'))
            {
                $('.risk2').show();
            }
            else 
            {
                $('.risk2').hide();
            }
        }
        else if(type=="checkboxM")
        {
            if ($('#checkboxM').attr('checked'))
            {
                $('.risk3').show();
            }
            else 
            {
                $('.risk3').hide();
            }
        }
        else if(type=="checkboxL")
        {
            if ($('#checkboxL').attr('checked'))
            {
                $('.risk6').show();
            }
            else 
            {
                $('.risk6').hide();
            }
        }
        else if(type=="checkboxI")
        {
            if ($('#checkboxI').attr('checked'))
            {
                $('.risk7').show();
            }
            else 
            {
                $('.risk7').hide();
            }
        }
        
        // checking false positives
        if ($('#checkboxFP').attr('checked'))
        {
            $('.fp').show();
        }
        else 
        {
            $('.fp').hide(); 
        }
        
        // show/hide hosts
        $('.hostip').map(function(idx, element){
        	var vall = false;
        	
        	$('tr.trsk',element).each(function(){
                //$(this).log($(this).css('display'))
                if ($(this).css('display')!='none')
                {
                    vall = true;
                }
        	});
        	
            if (!vall)
            {
                $(element).hide();
            }
            else
            {
                $(element).show();
            }
        });
    }
  
  
    function jumptopage(url) 
    {
        var c1 = $('#checkboxS').is(':checked') ? "t" : "f";
        var c2 = $('#checkboxH').is(':checked') ? "t" : "f";
        var c3 = $('#checkboxM').is(':checked') ? "t" : "f";
        var c4 = $('#checkboxL').is(':checked') ? "t" : "f";
        var c5 = $('#checkboxI').is(':checked') ? "t" : "f";
        
        document.location.href = url+'&chks='+c1+c2+c3+c4+c5
    }
  </script>

<?php


$fp_perms_where = (Session::get_ctx_where() != "") ? " AND vnr.ctx in (".Session::get_ctx_where().")" : "";

if($nfp != '') 
{
    $dbconn->execute("UPDATE vuln_nessus_results SET falsepositive='N' WHERE result_id=$nfp".
        ((!empty($arruser))? " AND username in ($user)":""));
        
    $query = "select vnlr.result_id, vnlr.hostIP, HEX(vnlr.ctx) as ctx from vuln_nessus_results vnr, vuln_nessus_latest_results vnlr
              where
              vnlr.service=vnr.service
              and vnlr.risk=vnr.risk
              and vnlr.scriptid=vnr.scriptid
              and vnlr.hostIP=vnr.hostIP
              and vnlr.ctx=vnr.ctx
              and vnr.result_id=$nfp".$fp_perms_where.
              ((!empty($arruser))? " AND vnlr.username in ($user) ":"");
          
    $resultfp = $dbconn->execute( $query );
              
    while($resultfp->fields) 
    {
        $result_id = $resultfp->fields['result_id'];
        $host_ip   = $resultfp->fields['hostIP'];
        $host_ctx  = $resultfp->fields['ctx'];
        
        if(!empty($result_id) && Session::hostAllowed_by_ip_ctx($dbconn, $host_ip, $host_ctx)) 
        {
            $dbconn->execute("UPDATE vuln_nessus_latest_results SET falsepositive='N' WHERE result_id ='$result_id'");
        }
        
        $resultfp->MoveNext();
    }
}

if($fp != '') 
{    
        
    $query  = "UPDATE vuln_nessus_results SET falsepositive='Y' WHERE result_id = $fp";
    $query .= (!empty($arruser)) ? " AND username in ($user)": '';
    
    $dbconn->execute($query);
                
    $query = "select vnlr.result_id, vnlr.hostIP, HEX(vnlr.ctx) as ctx from vuln_nessus_results vnr, vuln_nessus_latest_results vnlr
              where
              vnlr.service=vnr.service
              and vnlr.risk=vnr.risk
              and vnlr.scriptid=vnr.scriptid
              and vnlr.hostIP=vnr.hostIP
              and vnlr.ctx=vnr.ctx
              and vnr.result_id=$fp".$fp_perms_where.
              ((!empty($arruser))? " AND vnlr.username in ($user) ":"");
                     
    $resultfp = $dbconn->execute($query);
              
    while($resultfp->fields)
    {
        $result_id = $resultfp->fields['result_id'];
        $host_ip   = $resultfp->fields['hostIP'];
        $host_ctx  = $resultfp->fields['ctx'];
        
        if(!empty($result_id) && Session::hostAllowed_by_ip_ctx($dbconn, $host_ip, $host_ctx)) 
        {
            $dbconn->execute("UPDATE vuln_nessus_latest_results SET falsepositive='Y' WHERE result_id ='$result_id'");
        }
        
        $resultfp->MoveNext();
    }
}


function generate_results($output)
{   
    global $user, $border, $report_id, $sid, $scantime, $scansubmit, $scantype, $fp, $nfp, $output, $filterip, 
    $query_risk, $dbconn, $treport, $ipl, $key, $query_byuser, $arruser;
    
    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);
    
    
    if($report_id != '') 
    {
        $query = "SELECT sid FROM vuln_nessus_latest_reports WHERE 1=1".(($report_id!="all")? " AND report_id=$report_id":"")." $query_byuser";
        //echo $query;
        $result=$dbconn->execute($query);
        while (!$result->EOF) 
        {
            $sid    = $result->fields['sid'];
            $sids[] = $sid;
            $result->MoveNext();
        }
        
        $sid = implode(",",$sids);
    }
    else 
    {        
        if ($scansubmit != '' && $treport != "latest") 
        {
           $query = "SELECT r.report_id, r.sid FROM vuln_nessus_reports r,vuln_jobs j WHERE r.report_id=j.report_id AND j.scan_SUBMIT='$scansubmit'".((empty($arruser))? "" : " AND r.username in ($user) ");
           //print_r($arruser);
           $result = $dbconn->execute($query);
           while (!$result->EOF) 
           {
                $report_id = $result->fields['report_id'];
                $sid       = $result->fields['sid'];
                
                $ids[] = $report_id;
                $result->MoveNext();
           }
           
           $report_id = implode(",", $ids);        
        } 
        else
        {   
            $query = "SELECT report_id, sid FROM ".(($treport=="latest")? "vuln_nessus_latest_reports" : "vuln_nessus_reports")." WHERE ".(($treport=="")? "scantime='$scantime'":"report_key=$key" )."
                 AND scantype='$scantype' $query_byuser LIMIT 1";            
                       
            $result    = $dbconn->execute($query);
            $report_id = $result->fields['report_id'];
            $sid       = $result->fields['sid'];
        }
    }
         
    $ip = $_SERVER['REMOTE_ADDR'];

    switch($output) 
    {
        case "full" :
            echo reportsummary();
            echo vulnbreakdown();
            echo hostsummary();
            echo origdetails();
        break;
    
        case "detailed" :
            echo reportsummary();
        break;

        case "summary" :
            echo reportsummary();
            echo vulnbreakdown();
            echo hostsummary();
        break;


        case "printable" :
            $border=0;
            echo reportsummary();
            echo vulnbreakdown();
            echo hostsummary();
            echo vulndetails();
        break;

        case "min" :
            $query_risk = "AND risk <= '3' ";
            echo reportsummary();
            echo vulnbreakdown();
            echo hostsummary();
            echo vulndetails();
        break;

        case "optimized" :
            echo reportsummary();
            echo vulnbreakdown();
            echo hostsummary();
            echo vulndetails();
        break;

        default:
            echo reportsummary();
            echo vulnbreakdown();
            echo hostsummary();
            echo origdetails();
        break;
    }
	
	echo "";
}


function reportsummary()
{   
    //GENERATE REPORT SUMMARY

    global $user, $border, $report_id, $scantime, $scantype, $fp, $nfp, $output, $filterip, $query_risk, $dbconn, $pluginid;
    global $treport, $sid, $ipl;
    
    $tz = Util::get_timezone();
    
    $htmlsummary = '';
   
    $user_filter = ($user != '') ? "AND t1.username in ($user)" : "";
    
    $query = "SELECT t2.id, t1.username, t1.name as job_name, t2.name as profile_name, t2.description 
                    FROM vuln_jobs t1
                    LEFT JOIN vuln_nessus_settings t2 on t1.meth_VSET=t2.id
                    WHERE t1.report_id in ($report_id) $user_filter
                    order by t1.SCAN_END DESC";
                    
    $result=$dbconn->execute($query);
    
    $id_profile   = $result->fields['id'];
    $query_uid    = $result->fields['username'];
    $job_name     = $result->fields['jobname'];
    $profile_name = $result->fields['profile_name'];
    $profile_desc = $result->fields['description'];
    
    if($job_name == '') 
    { // imported report
       $query_imported_report = "SELECT name FROM vuln_nessus_reports WHERE scantime='$scantime'";
       $result_imported_report=$dbconn->execute($query_imported_report);
       $job_name = $result_imported_report->fields["name"];
    }
    
    if($tz == 0) 
    {
        $localtime = gen_strtotime($scantime,"");
    }
    else 
    {
        $localtime = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($scantime)+3600*$tz);
    }
    
    $htmlsummary .= "<table border=\"5\" width=\"900\" style=\"margin: 9px 0px 0px 0px;\"><tr><th class=\"noborder\" valign=\"top\" style=\"text-align:left;font-size:12px;\" nowrap>
         
         <b>"._("Scan time").":</b></th><td class=\"noborder\" style=\"text-align:left;padding-left:9px;\">". $localtime ."&nbsp;&nbsp;&nbsp;</td>";

    //Generated date
    $gendate = gmdate("Y-m-d H:i:s",gmdate("U")+3600*$tz);

    $htmlsummary .= "<th class=\"noborder\" valign=\"top\" style=\"text-align:left;font-size:12px;\" nowrap>
         <b>"._("Generated").":</b></th><td class=\"noborder\" style=\"text-align:left;padding-left:10px;\">$gendate</td></tr>";


    $htmlsummary .= "<tr><th class=\"noborder\" valign=\"top\" style=\"text-align:left;font-size:12px;\" nowrap>
                <b>"._("Profile").":</b></th><td class=\"noborder\" style=\"text-align:left;padding-left:10px;\">";
    $htmlsummary .= "$profile_name - $profile_desc&nbsp;&nbsp;&nbsp;</td>
                <th class=\"noborder\" valign=\"top\" style=\"text-align:left;font-size:12px;\" nowrap>
                <b>"._("Job Name").":</b></th><td class=\"noborder\" style=\"text-align:left;padding-left:10px;\">$job_name</td></tr>";

    $htmlsummary.= "</table>";
    
   return "<center>".$htmlsummary."</center>";
}


function vulnbreakdown()
{   
    //GENERATE CHART
    global $user, $border, $report_id, $scantime, $scantype, $fp, $nfp, $output, $filterip, $query_risk, $dbconn;
    global $treport, $sid, $ipl;

    $htmlchart  = '';
    $query_host = '';
    if ($filterip) 
    { 
        $query_host = " AND hostip='$filterip'"; 
    }
   
    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);
  
    $query = "SELECT count(risk) as count, risk, hostIP, ctx
              FROM ( SELECT DISTINCT risk, port, protocol, app, scriptid, msg, hostIP, HEX(ctx) as ctx FROM vuln_nessus_results
              WHERE report_id in ($report_id) $query_host
              AND falsepositive='N'".
              (($scantime!="")? " AND scantime=$scantime":"").") AS t GROUP BY risk, hostIP, ctx";       
    
    $result = $dbconn->Execute($query);
    
    $prevrisk = 0;
    $chartimg = "./graph1.php?graph=1";
    
    $arr_vulns = array();

    while ($result->fields)
    {
        $riskcount = $result->fields['count'];
        $risk      = $result->fields['risk'];
        $hostip    = $result->fields['hostIP'];
        $hostctx   = $result->fields['ctx'];
         
        if(Session::hostAllowed_by_ip_ctx($dbconn, $hostip, $hostctx)) 
        {
            for ($i=0;$i<$risk-$prevrisk-1;$i++) 
            {
                $missedrisk=$prevrisk+$i+1;
                $chartimg.="&amp;risk$missedrisk=0";
            }
            $prevrisk=$risk;
        
            if ($arr_vulns[$risk] == '') 
            {
                $arr_vulns[$risk] = $riskcount;
            }
            else 
            {
                $arr_vulns[$risk] += $riskcount;
            }
        }
        
        $result->MoveNext();
    }

    foreach ($arr_vulns as $avk => $avv) 
    {
        $chartimg.="&amp;risk$avk=$avv";
    }
    
    if (intval($prevrisk)!=7) 
    {
        for($i=$prevrisk+1;$i<=7;$i++) 
        {
            $chartimg.="&amp;risk$i=0";
        }
    }
   // print out the pie chart
   if($prevrisk!=0)
   {
        $htmlchart .= "<font size=\"1\"><br></font>
            <img alt=\"Chart\" src=\"$chartimg\"><br>";
   }
   else
   {
        $htmlchart = "<br><span style=\"color:red\">"._("No vulnerabilty data")."</span>";
   }
   
   return "<center>".$htmlchart."</center>";
}


function hostsummary()
{
    global $user, $border, $report_id, $scantime, $scantype, $fp, $nfp, $output, $filterip, $query_risk, $dbconn;
    global $treport, $sid, $ipl, $ips_inrange, $pag;
    
    $htmldetails = '';
    $query_host  = '';
    if ($filterip) 
    { 
        $query_host = " AND hostip='$filterip'"; 
    }

    $htmldetails .= "<br><br><font color=\"red\">
         <table cellspacing=\"0\" cellpadding=\"0\" width=\"900\"><tr><td class=\"headerpr_no_bborder\"><b>"._("Summary of Scanned Hosts")."</b></td></tr></table>
         <table class=\"stats\" summary=\""._("Summary of scanned hosts")."\" width=\"900\">";
    $htmldetails .= "<form>";

    $htmldetails .= "<tr><th width=\"128\"><b>"._("Host")."&nbsp;&nbsp;</b></th>
         <th width=\"128\"><b>"._("Hostname")."&nbsp;&nbsp;</b></th>
         <td width=\"128\" style='background-color:#FFCDFF;border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px;border: 1px solid #C835ED;'>
            <table width=\"100%\" class=\"noborder\" style=\"background:transparent\">
                <tr>
                    <td width=\"80%\" class=\"nobborder\" style=\"text-align:center;\">
                    <b>"._("Serious")."&nbsp;&nbsp;</b>
                    </td>
                    <td class=\"checkinfo nobborder\" width=\"20%\">
                    <input id=\"checkboxS\" type=\"checkbox\" onclick=\"toggle_vulns('checkboxS')\" checked>
                    </td>
                </tr>
            </table>
         </td>
         <td width=\"128\" style='background-color:#FFDBDB;border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px;border: 1px solid #FF0000;'>
            <table width=\"100%\" class=\"noborder\" style=\"background:transparent\">
                <tr>
                    <td width=\"80%\" class=\"nobborder\" style=\"text-align:center;\">
                    <b>"._("High")."&nbsp;&nbsp;</b>
                    </td>
                    <td class=\"checkinfo nobborder\" width=\"20%\">
                    <input id=\"checkboxH\" type=\"checkbox\" onclick=\"toggle_vulns('checkboxH')\" checked>
                    </td>
                </tr>
            </table>
        </td>
        <td width=\"128\" style='background-color:#FFF283;border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px;border: 1px solid #FFA500;'>
            <table width=\"100%\" class=\"noborder\" style=\"background:transparent\">
                <tr>
                    <td width=\"80%\" class=\"nobborder\" style=\"text-align:center;\">
                    <b>"._("Medium")."&nbsp;&nbsp;</b>
                    </td>                    
                    <td width=\"20%\" class=\"checkinfo nobborder\">
                    <input id=\"checkboxM\" type=\"checkbox\" onclick=\"toggle_vulns('checkboxM')\" checked>
                    </td>
                </tr>
            </table>
         </td>
        <td width=\"128\" style='background-color:#FFFFC0;border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px;border: 1px solid #FFD700;'>
            <table width=\"100%\" class=\"noborder\" style=\"background:transparent\">
                <tr>
                    <td width=\"80%\" class=\"nobborder\" style=\"text-align:center;\">
                    <b>"._("Low")."&nbsp;&nbsp;</b>
                    </td>                    
                    <td width=\"20%\" class=\"checkinfo nobborder\">
                    <input id=\"checkboxL\" type=\"checkbox\" onclick=\"toggle_vulns('checkboxL')\" checked></td>
                    </td>
                </tr>
            </table>
        </td>
        <td width=\"132\" style='background-color:#FFFFE3;border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px;border: 1px solid #F0E68C;'>
            <table width=\"100%\" class=\"noborder\" style=\"background:transparent\">
                <tr>
                    <td width=\"80%\" class=\"nobborder\" style=\"text-align:center;\">
                    <b>"._("Info")."&nbsp;&nbsp;</b>
                    </td>
                    <td width=\"20%\" class=\"checkinfo nobborder\">
                    <input id=\"checkboxI\" type=\"checkbox\" onclick=\"toggle_vulns('checkboxI')\" checked>
                    </td>
                </tr>
            </table>
        </td></tr>";
    $htmldetails .= "</form>";

   
    $query = "SELECT distinct t1.hostip, HEX(t1.ctx) as ctx 
        FROM vuln_nessus_results t1 
        WHERE report_id in ($report_id) $query_host and falsepositive='N'".
    (($scantime != '')? " and scantime = $scantime":"")." ORDER BY INET_ATON(hostip) ASC";
    
    $result=$dbconn->execute($query);

    $maxpag = 20;
    $hasta  = $pag*$maxpag;
    $desde  = $hasta - $maxpag;
    $hi = 0;
    while($result->fields) 
    {
        $hostip  = $result->fields['hostip'];
        $hostctx = $result->fields['ctx'];
        
        if(Session::hostAllowed_by_ip_ctx($dbconn, $hostip, $hostctx)) 
        {
            $host_id = key(Asset_host::get_id_by_ips($dbconn, $hostip, $hostctx));
                    
            if(valid_hex32($host_id)) 
            {
                $hostname = Asset_host::get_name_by_id($dbconn, $host_id);
            }
            else
            {
                $hostname = _('unknown');
            }
            
            if ($desde <= $hi && $hi < $hasta) 
            {
                $ips_inrange[$hostip.";".$hostctx] = $hostname;
            }
            
            $hi++;
        }
        
        $result->MoveNext();
    }
      
    foreach ($ips_inrange as $host_ip_ctx => $hostname) 
    {
    
        list($h_ip, $h_ctx) = explode(";", $host_ip_ctx);
   
        if ($output == "full") 
        {
            $tmp_host = "<a href='#$host_ip_ctx' id='$h_ip;$hostname' ctx='$h_ctx' class='anchor_link HostReportMenu'>$h_ip</a>";
        } 
        else 
        {
            $tmp_host = $h_ip;
        }

        $htmldetails .= "<tr><td>$tmp_host&nbsp;</td><td>$hostname&nbsp;</td>";
        $prevrisk = 0;
        
        $query2 = "SELECT count(risk) as count, risk
            FROM (SELECT DISTINCT risk, port, protocol, app, scriptid, msg FROM vuln_nessus_results
            WHERE report_id  in ($report_id) AND hostip='$h_ip' AND ctx=UNHEX('$h_ctx')
            AND falsepositive='N'".
            (($scantime != '')? "and scantime = $scantime":"").")as t GROUP BY risk";

        $drawtable = 0;
      
        $result2 = $dbconn->execute($query2);

        $arisk = array();
        
        while($result2->fields) 
        {
            $riskcount = $result2->fields['count'];
            $risk      = $result2->fields['risk'];
            
            if ($risk == 4) 
            {
                $arisk[3] +=  $riskcount;
            }
            else if ($risk == 5) 
            {
                $arisk[6] +=  $riskcount;
            }
            else 
            {    
                $arisk [$risk] = $riskcount;
            }
            
            $result2->MoveNext();
        }
        
        $lsrisk = array('1','2','3','6','7');

        foreach ($lsrisk as $lrisk) 
        {
            if($arisk[$lrisk] != '')
            {
                $drawtable=1;
                $htmldetails .= "<td><a class='anchor_link' href=\"#".$h_ip."_".$h_ctx."_".$lrisk."\">$arisk[$lrisk]</a></td>";
            }
            else    
            {
                $htmldetails .= "<td>-</td>";
            }
        }

        if ($drawtable == 0) 
        {
            $htmldetails .= "<td>-</td><td>-</td><td>-</td><td>-</td><td>-</td>"; 
        }
        
        $htmldetails .= "</tr>";
    }
   
    if ($hi >= $maxpag) 
    {
         // pagination
         $first    = "<font color=\"#626262\"><< "._("First")."</font>";
         $previous = "<font color=\"#626262\">< "._("Previous")."  </font>"; 
         
         $url = preg_replace("/\&pag=\d+|\&chks=[tf]+/","",$_SERVER["QUERY_STRING"]);
         
         // Prevent XSS
         $url = str_replace("'", "", $url);
         
         if ($pag > 1) 
         {
            $first = "<a href='javascript:;' onclick=\"jumptopage('?$url&pag=1')\" style='padding:0px 5px 0px 5px'>"._("<< First")."</a>";
            $previous = "<a href='javascript:;' onclick=\"jumptopage('?$url&pag=".($pag-1)."')\" style='padding:0px 5px 0px 5px'>"._("< Previous")."</a>";
        }
        
        
        $htmldetails .= "<tr><td colspan=11 class='nobborder' style='text-align:center'>";
        $tp = intval($hi/$maxpag); $tp += ($hi % $maxpag == 0) ? 0 : 1;
        $htmldetails .= $first." ".$previous;
        $pbr = 1;
        
        for ($p=1; $p<=$tp; $p++) 
        {
            $pg = ($p==$pag) ? "<b>$p</b>" : $p;
            
            $htmldetails .= "<a href='javascript:;' onclick=\"jumptopage('?$url&pag=$p')\" style='padding:0px 5px 0px 5px'>$pg</a>";
            
            if ($pbr++ % 30 == 0) 
            {
                $htmldetails .= "<br>";
            }
        }
        
        $next = "<font color=\"#626262\">  "._("Next")." ></font>";
        $last = "<font color=\"#626262\"> "._("Last")." >></font>";

        if ($pag < $tp) 
        {
            $next = "<a href='javascript:;' onclick=\"jumptopage('?$url&pag=".($pag+1)."')\" style='padding:0px 5px 0px 5px'>"._("Next >")."</a>";
            $last = "<a href='javascript:;' onclick=\"jumptopage('?$url&pag=".$tp."')\" style='padding:0px 5px 0px 5px'>"._("Last >>")."</a>";
        }
        $htmldetails .= $next." ".$last;
        $htmldetails .= "</td></tr>";
   }
   
   $htmldetails .= "</table><br>";

   return "<center>".$htmldetails."</center>";
}


function vulndetails()
{
    global $user, $border, $report_id, $scantime, $scantype, $fp, $nfp, $output, $filterip, $query_risk, $dbconn;
    global $treport, $ipl;
    
    $host_list = array();
    
    $htmldetails = '';
    $query_host  = '';
    
    if ($filterip) 
    {
        $query_host = " AND hostip='$filterip'"; 
    }
    
    if ($output == "original")
    {
        $query_order = "group by hostip order by risk";
    } 
    else 
    {
        $query_order = "order by risk,scriptid,hostip";
    }

    $query = "SELECT risk, scriptid, service, msg, hostip
         FROM ".(($treport=="latest" || $ipl!="")? "vuln_nessus_latest_results" : "vuln_nessus_results")."
         WHERE ".(($ipl!="all")?"report_id='$report_id'":"").(($treport=="latest" || $ipl!="")? " and sid in ($sid)" : " ").
         ((!empty($arruser) && ($treport=="latest" || $ipl!=""))? " AND username in ($user) " : " ").$query_risk.$query_host.$query_order;
    
    #echo "query=$query<br>";
    
    $result = $dbconn->execute($query);
    
    if ($result->RecordCount() != 0) 
    {
         $htmldetails .= "
      <br><hr><br><font color=\"red\">
      <b><big>"._("Summary of Vulnerabilities By Risk")."</big></b></font>
      <br><table width=\"100%\" summary=\"Summary of risks\" border=\"$border\"
      cellspacing=\"0\" cellpadding=\"0\" style=\"border-collapse: collapse\">";
    } 
    else 
    {
         $htmldetails .= "<br><hr><br><font color=\"red\">
      <b><big>"._("Serious Vulnerabilities Risks").":</big></b></font><br><br>
      <b>"._("NONE")."</b>";
         return $htmldetails;
    }

    $lastid   = 0;
    $scriptid = 0;

    while($result->fields) 
    {
        $risk     = $result->fields['risk'];
        $scriptid = $result->fields['scriptid'];
        $service  = $result->fields['service'];
        $msg      = $result->fields['msg'];
        $host     = $result->fields['hostip'];
        
        if ($scriptid != $lastid ) 
        {
        
            if (is_array($host_list)) 
            {
                foreach ($host_list as $key => $value) 
                {
                    $htmldetails .= "<tr><td>$key</td></tr>";
                }
                
                reset($host_list);
            }
    
            $query2 = "select note from nessus_notes where pid='$scriptid'";
            
            $result_note = $dbconn->execute($query2);
            
            if ($result_note->RecordCount() != 0) 
            {
                $msg .= "\n\n<FONT COLOR=\"#0044FF\"><B>"._("Custom Notes").":</B>";
                
                $note_num = 1;
                
                while($result_note->fields) 
                {
                   $customnote = $result_note->fields['note'];
                    
                   $msg .= "\n$note_num. $customnote";
                   $note_num++;
                   
                   $result_note->MoveNext();
                }
                
                $msg.="</FONT>";
            }
    
            $msg = preg_replace("/^[ \t]*/","",$msg);
            $msg = wordwrap(preg_replace("/\n/","<br>",$msg),100,"<br>",1);
            $msg = activateHyperlink($msg);
            
            if ($lastid != 0) 
            {
                $htmldetails .= "<tr>
                   <td>&nbsp;</td></tr>
                   <tr><td><hr></td></tr>
                   <tr><td>&nbsp;</td></tr>";
            }
    
            $htmldetails .= "<tr><td><b>"._("RISK")."</b></td></tr>
                <tr><td>".getrisk($risk)."</td></tr>
                <tr><td>&nbsp;</td></tr>
                <tr><td><b>"._("PLUGIN")."</b></td></tr>
                <tr><td>$scriptid</td></tr>
                <tr><td>&nbsp;</td></tr>
                <tr><td><b>"._("SERVICE")."</b></td></tr>
                <tr><td>$service</td></tr>
                <tr><td>&nbsp;</td></tr>
                <tr><td><b>"._("DETAILS")."</b></td></tr>
                <tr><td>$msg</td></tr>
                <tr><td>&nbsp;</td></tr>
                <tr><td><b>"._("VULNERABLE HOSTS").":</b></td></tr>";
         
            $host_list[$host] = 1;
            
            $lastid = $scriptid;
        } 
        else 
        {
            if ($scriptid != " ") 
            {
                $host_list[$host] = 1;
            }
        }
        
        $result->MoveNext();
    }

    if (!empty($host_list)) 
    {
        foreach ($host_list as $key => $value) 
        {
            $htmldetails .= "<tr><td>$key</td></tr>";
        }
      
      reset($host_list);
    }

    $htmldetails .= "</table><br>";
    
    return $htmldetails;
}



function origdetails() 
{
    global $uroles, $user, $sid, $query_risk, $border, $report_id, $scantime, $scantype, $fp, $nfp, $filterip,
    $enableFP, $enableNotes, $enableException, $output, $sortby, $dbconn;
    
    global $treport, $ipl, $ips_inrange;
    
    $enableException = 0;
    
    $colors = array ("Serious" => "#FFCDFF", "High" => "#FFDBDB", "Medium" => "#FFF283", "Low" => "#FFFFC0", "Info" => "#FFFFE3");
    $images = array ("Serious" => "./images/risk1.gif", "High" => "./images/risk2.gif", "Medium" => "./images/risk3.gif", "Low" => "./images/risk6.gif", "Info" => "./images/risk7.gif");
    $levels = array("Serious" => "1", "High" => "2", "Medium" => "3", "Low" => "6", "Info" => "7");
    
    $query_host = '';
    
    if ($filterip) 
    { 
        $query_host = " AND hostip='$filterip'"; 
    }   
    
    
    echo "<center>";
    echo "<form>";
    echo "<table width=\"900\" class=\"noborder\" style=\"background:transparent;\">";
    echo "<tr><td style=\"text-align:left;\" class=\"nobborder\">";
    echo "<input id=\"checkboxFP\" type=\"checkbox\" onclick=\"showFalsePositives()\"> <span style=\"color:black\">"._("View false positives")."</span>";
    echo "</td><td class=\"nobborder\" style=\"text-align:center;\">";
    // print the icon legend
    if ($enableFP) 
    {
        echo "<img alt='True' src='images/true.gif' border=0 align='absmiddle'> - "._("True result")."&nbsp;&nbsp;";
        echo "<img alt='False' src='images/false.png' border=0 align='absmiddle'> - "._("False positive result")."&nbsp;&nbsp;";
    }

    echo "<img alt='Info' src='images/info.png' border=0 align='absmiddle'> - "._("Additional information is available");
    echo "</td></tr></table>";
    echo "</form>";
    echo "<br>";

    // Check if exists _feed tables
    $query   = "SELECT sid FROM vuln_nessus_reports WHERE report_id in ($report_id)";
    $profile = $dbconn->GetOne( $query );
    $feed    = ($profile=="-1" && exists_feed_tables($dbconn)) ? "_feed" : "";

    $query = "SELECT distinct t1.hostip,HEX(t1.ctx) as ctx
         FROM vuln_nessus_results t1
         WHERE report_id in ($report_id) $query_host
         ORDER BY INET_ATON(hostip) ASC";

    //echo $query;
    $resultp = $dbconn->execute($query);

    $host_range = array_keys($ips_inrange);
    while($resultp->fields) 
    {   
        $hostip  = $resultp->fields['hostip'];
        $hostctx = $resultp->fields['ctx'];
           
        $host_id = key(Asset_host::get_id_by_ips($dbconn, $hostip, $hostctx));
                
        if(valid_hex32($host_id)) 
        {
            $hostname = Asset_host::get_name_by_id($dbconn, $host_id);
        }
        else
        {
            $hostname = _('unknown');
        }

        if (in_array($hostip.";".$hostctx, $host_range)) 
        {
            echo "<div class='hostip'>";
            echo "<br><font color='red'><b><a name='$hostip;$hostctx' href='javascript:;' id='$hostip;$hostname' ctx='$hostctx' class='HostReportMenu'>$hostip - $hostname</a></b></font>";
            echo "<br><br><table summary=\"$hostip - "._("Reported Ports")."\">";
            echo "<tr><th colspan=2>"._("Reported Ports")."</th></tr>";
    
            // get the "open ports" this replaced an approroacj requiring risk 7 and an empty msg cell
    
            $query = "SELECT DISTINCT port, protocol FROM vuln_nessus_results
                    WHERE report_id in ($report_id)".
                    (($scantime!="")? " AND scantime=$scantime":"").
                    " AND hostip='$hostip' AND ctx=UNHEX('$hostctx') AND port > '0' ORDER BY  port ASC";
    
            $result1=$dbconn->execute($query);

            $k = 1;
            $pos = "";
            if (!$result1->fields)
            {
                print "<tr><td>"._("No reported ports found")."</td></tr>";
            }
            else 
            {
                while($result1->fields)
                {
                    $port  = $result1->fields['port'];
                    $proto = $result1->fields['protocol'];
                    
                    if($k % 2) 
                    {
                        echo "<tr><td>$port/$proto</td>";
                        $pos = "open";
                    } 
                    else 
                    {
                        echo "<td>$port/$proto</td></tr>";
                        $pos = "closed";
                    }
                    
                    $k++;
                    $result1->MoveNext();
                } 
                // end while
                
                // close up the table
                if($pos!="closed") 
                {
                    echo "<td>&nbsp;</td></tr>";
                }
            }
    
        echo "</table><p></p>";
        
        echo "<table width='900' summary='$hostip - risks'><tr>";
        echo "<th>"._("Vuln Name")."</th>"; 
        echo "<th>"._("VulnID")."</th>";
        echo "<th>"._("Service")."</th>";
        echo "<th>"._("Severity")."</th>";
        echo "</tr>";

        $query = "select t1.result_id, t1.service, t1.risk, t1.falsepositive, t1.scriptid, v.name, t1.msg
                    FROM vuln_nessus_results t1
                    LEFT JOIN vuln_nessus_plugins$feed as v ON v.id=t1.scriptid
                    WHERE report_id in ($report_id) and hostip='$hostip' and ctx=UNHEX('$hostctx') and msg<>''".
                    (($scantime!="")? " AND scantime=$scantime":"");
    
        $query.=" group by t1.risk, t1.port, t1.protocol, t1.app, t1.scriptid, t1.msg order by t1.risk";
        
        $result1=$dbconn->execute($query);

        $arrResults = '';

        while($result1->fields) 
        {
            $result_id     = $result1->fields['result_id'];
            $service       = $result1->fields['service'];
            $risk          = $result1->fields['risk'];
            $falsepositive = $result1->fields['falsepositive'];
            $scriptid      = $result1->fields['scriptid'];
            $pname         = $result1->fields['name'];
            $msg           = $result1->fields['msg'];
            
            $tmpport1 = preg_split("/\(|\)/",$service);
            
            if (sizeof($tmpport1)==1) 
            { 
                $tmpport1[1] = $tmpport1[0]; 
            }
         
            $tmpport2      = preg_split("/\//",$tmpport1[1]);
            $service_num   = $tmpport2[0];
            $service_proto = $tmpport2[1];
            
            $arrResults[] = array($service_num, 
                $service_proto, 
                $service,
                $risk, 
                $falsepositive, 
                $result_id, 
                $msg, 
                $scriptid,
                $pname);
            
            $result1->MoveNext();
        }

        if(empty($arrResults)) 
        { 
            // empty, print out message
            echo "<td colspan='4'>"._("No vulnerability results matching this reports 
            filtering criteria were found").".</td></tr>";
        }
    
        foreach ($arrResults as $key=>$value) 
        {
            list($service_num, 
            $service_proto, 
            $service, 
            $risk, 
            $falsepositive, 
            $resid, 
            $msg, 
            $scriptid,
            $pname) = $value;

            $msg        = preg_replace("/^[ \t]*/","",$msg);
            $cves_found = "";
            
            
            
            if (preg_match_all("/CVE\-\d+\-\d+/i",$msg, $found)) 
            {
                $cves_found = implode(" ", $found[0]);
            }     
            
            $msg = preg_replace("/\n/","<br>",$msg);
            $msg = wordwrap($msg,100,"<br>",1);
            
            $tmprisk = getrisk($risk);
            
            $msg = preg_replace("/^\<br\>/i","",str_replace("\\r", "", $msg));
            $msg = preg_replace("/(Insight|CVSS Base Score|Vulnerability Detection Method|Vulnerability Detection Result|CVSS Base Vector|Solution|Summary|Details|Overview|Synopsis|Description|See also|Plugin output|References|Vulnerability Insight|Vulnerability Detection|Impact|Impact Level|Affected Software\/OS|Fix|Information about this scan)\s*:/","<b>\\1:</b>",$msg);
            $msg = str_replace("&amp;","&", $msg);

            // output the table cells
            $ancla = $hostip."_".$hostctx."_".$levels[$tmprisk];
            
            $pname = ($pname!="") ? $pname : _("No name");
            
            echo "<tr ".(($falsepositive=='Y')? "class=\"trsk risk$risk fp\"" : "class=\"trsk risk$risk\"")."style=\"background-color:".$colors[$tmprisk].(($falsepositive=='Y')? ";display:none;" : "")."\">";
                
                echo "<td width=\"50%\" style=\"padding:3px 0px 3px 0px;\"><b>".$pname."</b></td>";
            
                echo "<td id=\"plugin_$scriptid\" style=\"padding:3px 0px 3px 0px;\">$scriptid</td>";
            ?>
    
            <td style="padding:3px;" width="180"><?php echo $service; ?></td>
            <td style="text-align:center;">
                <?php echo $tmprisk; ?>&nbsp;&nbsp;<img align="absmiddle" src="<?php echo $images[$tmprisk] ?>" style="border: 1px solid ; width: 25px; height: 10px;">
            </td>
            
        </tr>
        
        <?php
        echo "<tr ".(($falsepositive=='Y')? "class=\"trsk risk$risk fp\"" : "class=\"trsk risk$risk\"")."style=\"background-color:".$colors[$tmprisk].(($falsepositive=='Y')? ";display:none;" : "")."\">";
        ?>
            <td style="padding:3px 0px 3px 6px;text-align:left;">
                <a class="msg" name="<?php echo $resid ?>"></a>
                <a name="<?php echo $ancla ?>"></a>
                <?php echo $msg; ?>
                <font size="1">
                    <br><br>
                </font>
            
                <?php 
                if ($cves_found != '') 
                {         
                    ?>
                    <a title="<?php echo _("Info from cve.mitre.org")?>" target="cve_mitre_org" href="http://cve.mitre.org/cgi-bin/cvekey.cgi?keyword=<?php echo urlencode($cves_found);?>"><img src="images/cve_mitre.png" border='0'></a>
                     <!--Add link to popup with Script Info-->
                    <?php 
                } 
                
                if($scriptid != "0") 
                {
                    ?>
                        <div lid="<?php echo $scriptid;?>" style="text-decoration:none;display:inline" class="scriptinfo"><img alt="Info" src="images/info.png" border=0></div>
                    <?php
                }
                
                $tmpu = array();
                $url = "";
                foreach ($_GET as $kget => $vget) 
                {
                    if($kget!="pluginid" && $kget!="nfp" && $kget!="fp")
                    {
                        $tmpu[] = Util::htmlentities($kget)."=".urlencode($vget);
                    }
                }
            
                $url = implode("&",$tmpu);
                
                if ($falsepositive == "Y") 
                {            
                    ?>
                    <a href='javascript:;' onclick="jumptopage('<?php echo $_SERVER['SCRIPT_NAME'].'?'.$url?>&nfp=<?php echo $resid?>')">
                        <img alt="<?php echo _("Clear false positive")?>" src='images/false.png' title='<?php echo _("Clear false positive")?>' border='0' />
                    </a>
                    <?php
                }
                else 
                {
                    ?>
                    <a href='javascript:;' onclick="jumptopage('<?php echo $_SERVER['SCRIPT_NAME'].'?'.$url?>&fp=<?php echo $resid?>')">
                        <img alt='<?php echo _("Mark as false positive")?>' src='images/true.gif' title='<?php echo _("Mark as false positive")?>' border='0' />
                    </a>
                    <?php
                }
            
                $pticket = "ref=Vulnerability&title=".urlencode($pname)."&priority=1&ip=".urlencode($hostip)."&port=".urlencode($service_num).
                "&nessus_id=".urlencode($scriptid)."&risk=".urlencode($tmprisk)."&type=".urlencode("Vulnerability");
                
                echo "<a title=\""._("New ticket")."\" class=\"greybox\" href=\"../incidents/newincident.php?$pticket\"><img style=\"padding-bottom:2px;\" src=\"../pixmaps/script--pencil.png\" border=\"0\" alt=\"i\" width=\"12\"></a>&nbsp;&nbsp;";
                ?>
            </td>
            <?php
            $plugin_info = $dbconn->execute("SELECT t2.name as pfamily, t3.name as pcategory, t1.copyright, t1.summary, t1.version 
                    FROM vuln_nessus_plugins$feed t1
                    LEFT JOIN vuln_nessus_family$feed t2 on t1.family=t2.id
                    LEFT JOIN vuln_nessus_category$feed t3 on t1.category=t3.id
                    WHERE t1.id='$scriptid'");
    
            $pfamily    = $plugin_info->fields['pfamily'];
            $pcategory  = $plugin_info->fields['pcategory'];
            $pcopyright = $plugin_info->fields['copyright'];
            $psummary   = $plugin_info->fields['summary'];
            $pversion   = $plugin_info->fields['version'];
            
            ?>
            
            <td colspan="3" valign="top" style="text-align:left;padding:3px;">
                <?php
                $plugindetails = '';
                
                if ($pfamily != '')    { $plugindetails .= '<b>Family name:</b> '.$pfamily.'<br><br>';} 
                if ($pcategory != '')  { $plugindetails .= '<b>Category:</b> '.$pcategory.'<br><br>'; }
                if ($pcopyright!= '')  { $plugindetails .= '<b>Copyright:</b> '.$pcopyright.'<br><br>'; }
                if ($psummary != '')   { $plugindetails .= '<b>Summary:</b> '.$psummary.'<br><br>'; }
                if ($pversion != '')   { $plugindetails .= '<b>Version:</b> '.$pversion.'<br><br>'; }
            
                echo $plugindetails;
                 ?>
                 </td>
                 </tr>
                 <?php
                 $result1->MoveNext();
            }
            echo "</table>";
            
            echo "</div>";
        }

        $resultp->MoveNext();
    }

    echo "</center>";
}

$ips_inrange = array();


switch($disp) 
{
   case "html":
         generate_results($output);
   break;

   default:
         generate_results($output);
   break;

}

$dbconn->close();
?>
<br/><br/><br/>
</body>
</html>

