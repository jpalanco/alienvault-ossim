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
// $Id: threats-db.php,v 1.8 2010/04/07 16:14:41 josedejoses Exp $
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

require_once 'av_init.php';
require_once 'config.php';
require_once 'functions.inc';
require_once 'ossim_sql.inc';

Session::logcheck("environment-menu", "EventsVulnerabilities");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
  <title><?php echo gettext("Vulnmeter");?></title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META http-equiv="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
  <link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui.css"/>
  <link rel="stylesheet" type="text/css" href="../style/datepicker.css"/>
  <script type="text/javascript" src="../js/jquery.min.js"></script>
  <script type="text/javascript" src="../js/jquery-ui.min.js"></script>
  <script src="/ossim/js/jquery.tipTip-ajax.js" type="text/javascript"></script>
  <link rel="stylesheet" type="text/css" href="/ossim/style/tipTip.css"/>
  
  <?php require ("../host_report_menu.php") ?>
  <script type="text/javascript">
  
    function calendar()
	{		
		$('.date_filter').datepicker({
            showOn: "both",
            dateFormat: "yy-mm-dd",
            buttonText: "",
            buttonImage: "/ossim/pixmaps/calendar.png",
            onClose: function(selectedDate)
            {
                // End date must be greater than the start date
                
                if ($(this).attr('id') == 'date_from')
                {
                    $('#date_to').datepicker('option', 'minDate', selectedDate );
                }
                else
                {
                    $('#date_from').datepicker('option', 'maxDate', selectedDate );
                }
            }
            
		});
	}
  function postload() {
	
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
	
	$('#loading').toggle();
    calendar(); 
  }
  </script>
  
  <style type='text/css'>
    .gray_border2 {
        border-left: 1px solid #C4C0BB;
        border-right: 1px solid #C4C0BB;
        border-bottom: 1px solid #C4C0BB;
        border-top: 1px solid white;
    }
    .gray_border {
        border: 1px solid #C4C0BB;
    }
    .sb_td {
        padding: 7px 0px 7px 0px;
    }
    .t_width {
        width:100%;
    }
    .pbottom {
        padding-bottom: 6px;
    }
    #td_header {
        margin:20px auto 0px auto;
    }
    
  </style>
  
</head>

<body>
<?php

$pageTitle = _("Nessus Threats Database");

$getParams = array(  'disp', 'increment', 'page', 'kw', 'family', 'risk', 'start_date', 'end_date', 'cve', 'scve' );


$postParams = array( 'disp', 'increment', 'page', 'kw', 'family', 'risk', 'start_date', 'end_date', 'cve', 'scve' );


$schedOptions = array(      "N" => "Immediately",
                     "O" => "Run Once", 
                     "D" => "Daily", 
                     "W" => "Weekly", 
                     "M" => "Monthly" );          

switch ($_SERVER['REQUEST_METHOD'])
{
case "GET" :
   foreach ($getParams as $gp) {
      if (isset($_GET[$gp])) { 
         if(is_array($_GET[$gp])) {
            foreach ($_GET[$gp] as $i=>$tmp) {
               ${$gp}[$i] = sanitize($tmp);
            }
         } else {
            $$gp = sanitize($_GET[$gp]);
         }
      } else { 
         $$gp=""; 
      }
   }
   break;
case "POST" :
   foreach ($postParams as $pp) {
      if (isset($_POST[$pp])) { 
         if(is_array($_POST[$pp])) {
            foreach($_POST[$pp] as $i=>$tmp) {
               ${$pp}[$i] = sanitize($tmp);
            }
         } else {
            $$pp = sanitize($_POST[$pp]);
         }
      } else { 
         $$pp=""; 
      }
   }
   break;
}

ossim_valid($disp,       OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Disp option"));
ossim_valid($page,       OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Page"));
ossim_valid($family,     OSS_DIGIT, OSS_NULLABLE,  'illegal:' . _("Family"));
ossim_valid($risk,       OSS_DIGIT, OSS_NULLABLE,        'illegal:' . _("Risk"));
ossim_valid($start_date, OSS_DIGIT, '\-', OSS_NULLABLE , 'illegal:' . _("Start date"));
ossim_valid($end_date,   OSS_DIGIT, '\-', OSS_NULLABLE , 'illegal:' . _("End date"));
ossim_valid($cve,        OSS_ALPHA, '\-', OSS_NULLABLE , 'illegal:' . _("CVE"));
ossim_valid($scve,       OSS_ALPHA, '\-', OSS_NULLABLE , 'illegal:' . _("sCVE"));
ossim_valid($kw,         OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE , 'illegal:' . _("Keywords"));

if (ossim_error()) {
    die(ossim_error());
}

if (isset($_POST['decrement'])) {
     $page = $page -1;
} elseif (isset($_POST['increment'])) {
     $page = $page + 1;
}

if (!$page) { $page=1; }

function home() {
    global $dbconn, $start_date, $end_date, $kw, $scve, $risk;
    
    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);
    
    $resultcve=$dbconn->GetArray("select id, cve_id from vuln_nessus_plugins");
    foreach ($resultcve as $cve) {
        $c = explode(",",$cve['cve_id']);
        foreach ($c as $value) {
            $value = trim($value);
            if ($value!="") {
                $tmp = substr($value,0,8);
                $tmp = preg_replace("/cve\s+/i", "CVE-", $tmp);
                
                //ENG-95985 Fix openvas bug with cve typo
                if (strcasecmp($tmp,'cve-2104') == 0)
                {
                    continue;
                }
                
                $cves[$tmp] = $i;
                $i++;
            }
        }
    }
    if (is_array($cves))
        ksort($cves);


echo "<table class='transparent w100'><tr><td class=\"sec_title\">"._("Threats Filter")."</td></tr></table>";
	echo '
      <form method="POST" action="threats-db.php">
        <input type="hidden" name="disp" value="search">
        <table class="w100 nobborder transparent">
        <tr>
        <td colspan="7" class="transparent" style="padding: 0px;">
        <table class="transparent nobborder" cellpadding="0" cellspacing="0" width="100%" align="center">
        <tr>
        <th class="">' . _("Date Range") . '</th>
        <th class="">' . _("Keywords") . '</th>
        <th class="">' . _("CVE Id") . '</th>
        <th class="">' . _("Risk Factor") . '</th>
        </tr>
        <tr>
        <td style="text-align:center;" class="nobborder">';
    echo "
         <div class='datepicker_range' style='width:190px;margin:0px auto;padding-left:20px;'>
            <div class='calendar_from'>
                <div class='calendar'>
                   <input name='start_date' id='date_from' class='date_filter' type='input' value='$start_date'>
                </div>
            </div>
            <div class='calendar_separator'>
            -
            </div>
            <div class='calendar_to'>
                <div class='calendar'>
                    <input name='end_date' id='date_to' class='date_filter' type='input' value='$end_date'>
                </div>
            </div>
         </div>";
         
	echo <<<EOT
      </td>
          <td style="padding: 0 30px 0 30px;text-align:center;" class="nobborder">
EOT;
	echo <<<EOT
     <input type="text" name="kw" size="30" value="$kw"/>
      </td>
EOT;
echo "<td style=\"padding: 0 30px 0 30px;text-align:center;\" class=\"nobborder\" nowrap>";
echo "<select name=\"cve\" size=\"1\">";
echo "   <option value=\"\"></option>";

foreach ($cves as $key=>$value){
echo "   <option value='$key' ".(($key==$scve) ? "selected='selected'":"").">$key</option>";
}
echo "</select>";
echo "</td>";

	echo <<<EOT
      <td style="padding: 0 30px 0 30px;text-align:center;" class="nobborder" nowrap>
EOT;
	echo <<<EOT
     <select name="risk" size="1">
EOT;
    echo "<option value=\"\"></option>";
    echo "<option value=\"7\" ".(($risk==7) ? "selected='selected'":"").">"._("Info")."</option>";
    echo "<option value=\"6\" ".(($risk==6) ? "selected='selected'":"").">"._("Low")."</option>";
    echo "<option value=\"3\" ".(($risk==3) ? "selected='selected'":"").">"._("Medium")."</option>";
    echo "<option value=\"2\" ".(($risk==2) ? "selected='selected'":"").">"._("High")."</option>";
    echo "<option value=\"1\" ".(($risk==1) ? "selected='selected'":"").">"._("Serious")."</option>";
	echo <<<EOT
     </select>
     </td>
     </tr> 
     </table>
     
      </td>
      </tr>
      </table>
EOT;
  echo "<div class=\"transparent center w100 p_bottom\"><input type=\"submit\" value=\""._("Search")."\" class=\"av_b_main\" /></div>";
	echo <<<EOT
    <table class="table_list"><tr>
EOT;
    echo "<th sort:format=\"str\" style=\"text-align: left;width:30%\">"._("Threat Family")."</th>";
    echo "<th sort:format=\"int\" style=\"width:10%\" class=\"risk7\">"._("Info")."-7</th>";
    echo "<th sort:format=\"int\" style=\"width:10%\" class=\"risk6\">"._("Low")."-6</th>";
    echo "<th sort:format=\"int\" style=\"width:10%\" class=\"risk3\">"._("Medium")."-3</th>";
    echo "<th sort:format=\"int\" style=\"width:10%\" class=\"risk2\">"._("High")."-2</th>";
    echo "<th sort:format=\"int\" style=\"width:10%\" class=\"risk1\">"._("Serious")."-1</th>";
    echo "<th sort:format=\"int\" style=\"width:20%\">"._("Total")."</th>";
	echo <<<EOT
    </tr>

EOT;

     $query = "SELECT t2.id, t2.name, count( t1.risk = '1'OR NULL ) AS Urgent, 
          count( t1.risk = '2' OR NULL ) AS Critical, count( t1.risk = '3' OR NULL ) AS High, 
          count( t1.risk = '6' OR NULL ) AS MEDIUM , count( t1.risk = '7'OR NULL ) AS Low, 
          count( t1.risk ) AS Total 
          FROM vuln_nessus_plugins t1
          LEFT JOIN vuln_nessus_family t2 ON t1.family = t2.id
          GROUP BY t1.family";
     $result = $dbconn->execute($query);

     $http_base = "threats-db.php?disp=search";
     $color = 0;
     while (!$result->EOF) {
          list( $fam_id, $fam_name, $fam_urg, $fam_ser, $fam_high, $fam_med, $fam_low, $fam_total )
          =  $result->fields;

          echo "<tr bgcolor=".(($color % 2 ==0) ? "#EEEEEE" : "#FFFFFF")."><td style=\"text-align: left;padding:3px;\">$fam_name</td>
                      <td align=\"center\">".(($fam_low==0)? "0": "<a href=\"$http_base&family=$fam_id&risk=7\" >".Util::number_format_locale((int)$fam_low,0)."</a>")."</td>
                      <td align=\"center\">".(($fam_med==0)? "0": "<a href=\"$http_base&family=$fam_id&risk=6\" >".Util::number_format_locale((int)$fam_med,0)."</a>")."</td>
                      <td align=\"center\">".(($fam_high==0)? "0": "<a href=\"$http_base&family=$fam_id&risk=3\" >".Util::number_format_locale((int)$fam_high,0)."</a>")."</td>
                      <td align=\"center\">".(($fam_ser==0)? "0": "<a href=\"$http_base&family=$fam_id&risk=2\" >".Util::number_format_locale((int)$fam_ser,0)."</a>")."</td>
                      <td align=\"center\">".(($fam_urg==0)? "0": "<a href=\"$http_base&family=$fam_id&risk=1\" >".Util::number_format_locale((int)$fam_urg,0)."</a>")."</td>
                      <td align=\"center\">".(($fam_total==0)? "0": "<a href=\"$http_base&family=$fam_id\" >".Util::number_format_locale((int)$fam_total,0)."</a>")."</td>
          </tr>";

          $result->MoveNext();
          $color++;
     }

     $query = "SELECT count( risk = '1' OR NULL ) AS Urgent, 
          count( risk = '2' OR NULL ) AS Critical, count( risk = '3' OR NULL ) AS High, 
          count( risk = '6' OR NULL ) AS MEDIUM , count( risk = '7'OR NULL ) AS Low, 
          count( risk ) AS Total 
          FROM vuln_nessus_plugins t1";
     $result = $dbconn->execute($query);

     list( $fam_urg, $fam_ser, $fam_high, $fam_med, $fam_low, $fam_total ) 
         =  $result->fields;

     echo "<tr class=\"even\"><td class='noborder' style=\"text-align: left;padding:3px;\">"._("Total")."</td>
            <td class='noborder' align=\"center\">".(($fam_low==0)? "0" : "<a href=\"$http_base&risk=7\" >".Util::number_format_locale((int)$fam_low,0)."</a>")."</td>
          <td class='noborder' align=\"center\">".(($fam_med==0)? "0" : "<a href=\"$http_base&risk=6\" >".Util::number_format_locale((int)$fam_med,0)."</a>")."</td>
            <td class='noborder' align=\"center\">".(($fam_high==0)? "0" : "<a href=\"$http_base&risk=3\" >".Util::number_format_locale((int)$fam_high,0)."</a>")."</td>
            <td class='noborder' align=\"center\">".(($fam_ser==0)? "0" : "<a href=\"$http_base&risk=2\" >".Util::number_format_locale((int)$fam_ser,0)."</a>")."</td>
            <td class='noborder' align=\"center\">".(($fam_urg==0)? "0" : "<a href=\"$http_base&risk=1\" >".Util::number_format_locale((int)$fam_urg,0)."</a>")."</td>
            <td class='noborder' align=\"center\">".(($fam_total==0)? "0" : "<a href=\"$http_base\" >".Util::number_format_locale((int)$fam_total,0)."</a>")."</td>
          </tr></table>";

}

function search($page, $kw, $cve,$family, $risk, $start_date, $end_date) {
     global $dbconn;
     
     $dbconn->SetFetchMode(ADODB_FETCH_BOTH);

     $Limit=20;
     
     $risks = array("7" => _("Info"), "6" => _("Low"), "3" => _("Medium"), "2" => _("High"), "1" => _("Serious"));
     
     $query = "SELECT name FROM vuln_nessus_family WHERE id=$family";
     $result = $dbconn->execute($query);
        
     list( $family_name ) = $result->fields;
     
     if ( $kw == "" ) { $txt_kw = "All"; } else { $txt_kw = $kw; }
     if ( $cve == "" ) { $txt_cve = "All"; } else { $txt_cve = $cve; }
     if ( $family_name == "" ) { $txt_family = "All"; } else { $txt_family = $family_name; }
     if ( $risk == "" ) { $txt_risk = "All"; } else { $txt_risk = $risks[$risk]; }
     if ( $start_date == "" ) { $txt_start_date = "All"; } else { $txt_start_date = $start_date; }
     if ( $end_date == "" ) { $txt_end_date = "All"; } else { $txt_end_date = $end_date; }

     echo '
<table style="margin-top:10px;" class="t_width noborder">
         <tr>
        <td class="table_header">
         <div class="c_back_button">
    	      <input type="button" class="av_b_back" onclick="document.location.href=\'threats-db.php?start_date='.urlencode($start_date).'&end_date='.urlencode($end_date).'&kw='.urlencode($kw).'&risk='.urlencode($risk).'&scve='.urlencode($cve).'\';return false;"/>    	         
    	  </div>  
        <div class="sec_title">
        '._("Search results for this criteria").'
        </div>
         </td>
     </tr>
</table>

     <table cellpadding="0" cellspacing="0" class="transparent" align="center" width="100%">
          <tr><th>'.gettext("Start Date").'</th><th>'.gettext("End Date").'</th><th>'.gettext("Keywords").'</th><th>'.gettext("CVE Id").'</th><th>'.gettext("Family").'</th><th>'.gettext("Risk Factor").'</th></tr>
          <tr>
          <td class="nobborder" style="text-align:center;">'.Util::htmlentities($txt_start_date).'</td>
          <td class="nobborder" style="text-align:center;">'.Util::htmlentities($txt_end_date).'</td>
          <td class="nobborder" style="text-align:center;">'.Util::htmlentities($txt_kw).'</td>
          <td class="nobborder" style="text-align:center;">'.Util::htmlentities($txt_cve).'</td>
          <td class="nobborder" style="text-align:center;">'.Util::htmlentities($txt_family).'</td>
          <td class="nobborder" style="text-align:center;">'.Util::htmlentities($txt_risk).'</td>
          </tr>
     </table>
     <br>
     <table class="table_list">

';

     $query_filter = "WHERE 1=1 ";

     if ( $kw != "" ) 
     { 
        $skw = escape_sql($kw, $dbconn);
        $query_filter .= "AND ( t1.summary LIKE '%$skw%' OR t1.cve_id LIKE '%$skw%' OR t2.name LIKE '%$skw%' OR CONCAT(t2.name, ' - ', t1.summary) LIKE '%$skw%' )";
     }
     if ( $cve != "" ) {
        $cve2 = preg_replace("/cve-/i", "CVE ", $cve);
        $query_filter .= "AND ( t1.cve_id LIKE '%$cve%' OR t1.cve_id LIKE '%$cve2%')"; 
     }
     if ( $family != "" ) {  $query_filter .= "AND t1.family = '$family'"; }
     if ( $risk != "" ) {  $query_filter .= "AND t1.risk = '$risk'"; }
     if ( $start_date != "" ) {  $query_filter .= " AND CONVERT(t1.created,UNSIGNED) >= ".str_replace("-","",$start_date)."000000"; }
     if ( $end_date != "" ) {  $query_filter .= " AND CONVERT(t1.created,UNSIGNED) <= ".str_replace("-","",$end_date)."235959"; }

     $query_filter = ltrim($query_filter, "AND ");

     if ( $query_filter == "" ) { $query_filter = "1"; }

     if (!preg_match("/t2/",$query_filter)) {
        $query = "SELECT count( t1.id ) FROM vuln_nessus_plugins t1 $query_filter";
     }
     else {
        $query = "SELECT count( t1.id ) FROM vuln_nessus_plugins t1 LEFT JOIN vuln_nessus_family t2 ON t1.family = t2.id $query_filter";
     }

     $result = $dbconn->execute($query);

     list ( $numrec ) =  $result->fields;

     if ($numrec > 0) {
          $numpages=intval($numrec/$Limit);
     } else {
          $numpages = 1;
     }

     if ($numrec%$Limit) { $numpages++; } // add one page if remainder 
        if ($page > 0) { $previous = $page -1; } else { $previous = -1; }
        if ($numpages > $page) { $next = $page +1; } else { $next = -1;     }
        

        $offset = (($page-1) * $Limit);  

     $query = "SELECT t1.cve_id, t1.id, t1.risk, t1.created, t2.name, t1.summary 
          FROM vuln_nessus_plugins t1 LEFT JOIN vuln_nessus_family t2 on t1.family=t2.id
          $query_filter LIMIT $offset,$Limit";     
     
     //echo "query=$query<br>";
     $result = $dbconn->execute($query);
     
if (!$result->EOF) {
         echo <<<EOT
        <form action="threats-db.php" method="post">
        <INPUT TYPE=HIDDEN NAME="disp" VALUE="search">
        <INPUT TYPE=HIDDEN NAME="page" VALUE="$page">
        <INPUT TYPE=HIDDEN NAME="kw" VALUE="$kw">
        <INPUT TYPE=HIDDEN NAME="family" VALUE="$family">
        <INPUT TYPE=HIDDEN NAME="risk" VALUE="$risk">
        <INPUT TYPE=HIDDEN NAME="start_date" VALUE="$start_date">
        <INPUT TYPE=HIDDEN NAME="end_date" VALUE="$end_date">
        <INPUT TYPE=HIDDEN NAME="cve" VALUE="$cve">

        <table id="results-table" class="table_list" cellpadding="0" cellspacing="0" width="100%" align="center">
EOT;
            echo "<tr><th sort:format=\"int\" align=\"center\">".gettext("ID")."</th>";
            echo "<th sort:format=\"int\" align=\"center\">".gettext("Risk")."</th>";
            echo "<th sort:format=\"int\" align=\"center\">".gettext("Defined On")."</th>";
            echo "<th sort:format=\"str\" align=\"left\">".gettext("Threat Family &amp; Summary")."</th>";
            echo "<th>".gettext("CVE Id")."</th>";
            echo "</tr>";
         
         $color = 0;
         
         while (!$result->EOF) {
              list( $cve_id, $pid, $prisk, $pcreated, $pfamily, $psummary )
              =  $result->fields;
                       //<a href=\"lookup.php?id=$pid\" atest=\"ids\">$pid</a>
              $dt_pcreated = gen_strtotime( $pcreated, "" );
              
              echo "<tr>
                   <td sort:by=\"18606\" style=\"padding:3px\" align=\"center\" valign=\"top\">
                       <a href='javascript:;' style='text-decoration:none;' lid='".$pid."' class='scriptinfo'>".$pid."</a>
                  </td>
                     <td sort:by=\"4\" align=\"center\" valign=\"top\">
                       <img src=\"./images/risk".$prisk.".gif\" style=\"margin-top:3px;width: 25px; height: 10px; border: 1px solid\" />
                     </td>
                     <td sort:by=\"1120546800\" align=\"center\" valign=\"top\">
                       $dt_pcreated
                     </td>
                     <td style=\"text-align:left;\" sort:by=\"Gentoo Local Checks\" valign=\"top\">
                         <strong>$pfamily</strong> - $psummary
                     </td>
                     <td>";
                if($cve_id=="") {
                    echo "-"; 
                }
                else {
                    $listcves = explode(",", $cve_id); 
                    foreach($listcves as $c){
                        $c = trim($c);
                        $c = preg_replace("/cve\s+/i", "CVE-", $c);
                        echo "<a href='http://www.cvedetails.com/cve/$c/' target='_blank'>$c</a><br>";
                    }  
                }
            echo "</td></tr>";
            $result->MoveNext();
            $color++;
         }
         echo '</table>';
        
         $istatus = ($next > 0)     ? '' : 'disabled="disabled"';
         $dstatus = ($previous > 0) ? '' : 'disabled="disabled"'; 
                
         echo '<input type="submit" name="increment" value="' . _("Next >") . '" class="av_b_transparent fright" ' . $istatus . '>';

         echo '<input type="submit" name="decrement" value="' . _("< Previous") . '" class="av_b_transparent fright"' . $dstatus . '>';
                
         echo "</form>";
    }
    else {
        echo "<div class=\"center\"><a href=\"threats-db.php?start_date=$start_date&end_date=$end_date&kw=$kw&risk=$risk&scve=$cve\">"._("No results found, try to change the search parameters")."</a></div>";
    }
	echo "</td></tr></table></center>";
}

switch($disp) {

     case "search":
          search($page, $kw, $cve, $family, $risk, $start_date, $end_date);
          break;
     
     
    default:
        home( );
        break;
}
include_once('footer.php');
?>
