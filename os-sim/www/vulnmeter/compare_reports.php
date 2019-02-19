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
require_once 'functions.inc';

Session::logcheck("environment-menu", "EventsVulnerabilities");

$conf    = $GLOBALS["CONF"];

$freport = GET("freport");
$sreport = GET("sreport");
$pag     = GET("pag");

ossim_valid($freport, OSS_DIGIT, 'illegal:' . _("First report id"));
ossim_valid($sreport, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Second report id"));
ossim_valid($pag, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("pag"));

if (ossim_error()) {
    die(ossim_error());
}

$db = new ossim_db();
$dbconn = $db->connect();

$query = "SELECT report_id FROM vuln_nessus_reports where (report_id=$freport OR report_id=$sreport) ORDER BY scantime DESC";

$dbconn->SetFetchMode(ADODB_FETCH_ASSOC);

$result = $dbconn->Execute($query);
$sreport = $result->fields["report_id"];
$result->MoveNext();
$freport = $result->fields["report_id"];

// get ossim server version

$version = $conf->get_conf("ossim_server_version");

// check permissions

$reports = array();

$query_onlyuser="";
$error = "";

list($arruser, $user) = Vulnerabilities::get_users_and_entities_filter($dbconn);

if( !empty($arruser) ) {
	$query_onlyuser = " AND username in ($user)"; 
}

$query = "SELECT report_id FROM vuln_nessus_reports where (report_id= $freport OR report_id= $sreport) $query_onlyuser ORDER BY scantime DESC";

$result = $dbconn->Execute($query);

while (!$result->EOF) {
    $reports[] = $result->fields["report_id"];
    $result->MoveNext();
}


if(!in_array($freport, $reports) || !in_array($sreport, $reports)) {
    $error= _("You don't have permission to compare these reports.");
}

$perms_where    = (Session::get_ctx_where() != "") ? " AND ctx in (".Session::get_ctx_where().")" : "";

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
<head>
  <title> <?php
  echo gettext("Vulnmeter"); ?> </title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
  <META HTTP-EQUIV="Pragma" CONTENT="no-cache">
  <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
  <script type="text/javascript" src="../js/jquery.min.js"></script>
  <script type="text/javascript" src="../js/jquery.simpletip.js"></script>
  <script type="text/javascript">
      function toogle_details(id) {
        $('#tr'+id).toggle();
        if ($('#img'+id).attr('src').match(/plus/)){
            $('#img'+id).attr('src','../pixmaps/minus-small.png');
            $('#msg'+id).html('<?php echo gettext("Hide details")?>');
        }
        else {
            $('#img'+id).attr('src','../pixmaps/plus-small.png');
            $('#msg'+id).html('<?php echo gettext("Show details")?>');
        }
    }
</script>
</head>

<body>
<?php

if($error!="") {
    ?>
    <div style="margin:auto;text-align:center">
    <?php echo $error; ?>
    </div>
    </body>
    </html>
    <?php
    exit();
}


if ($pag=="" || $pag<1) $pag=1;

$maxpag = 10;

$query = "SELECT name, scantime FROM vuln_nessus_reports where report_id=".$freport;
$result=$dbconn->Execute($query);

$freport_name = preg_replace('/\d+\s-\s/', '', $result->fields["name"]);
$freport_scantime = preg_replace('/(\d\d\d\d)(\d+\d+)(\d+\d+)(\d+\d+)(\d+\d+)(\d+\d+)/i', '$1-$2-$3 $4:$5:$6', $result->fields["scantime"]);

$query = "SELECT name, scantime FROM vuln_nessus_reports where report_id=".$sreport;
$result=$dbconn->Execute($query);

$sreport_name = preg_replace('/\d+\s-\s/', '', $result->fields["name"]);
$sreport_scantime = preg_replace('/(\d\d\d\d)(\d+\d+)(\d+\d+)(\d+\d+)(\d+\d+)(\d+\d+)/i', '$1-$2-$3 $4:$5:$6', $result->fields["scantime"]);

$tz = Util::get_timezone();

?>
<br />
<table style="margin:auto;" width="95%" class="transparent" cellspacing="0" cellpadding="0">
    <tr>
        <td class="noborder" width="49%">
            <table style="margin:auto;border: 0pt none;" width="100%" cellspacing="0" cellpadding="0">
            <tr>
            <?php
            if($tz==0) {
                $flocaltime = $freport_scantime;
            }
            else {
                $flocaltime = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($freport_scantime)+3600*$tz);
            }
            ?>
                <td class="headerpr_no_bborder"><?php echo $freport_name; ?><span style="font-size : 9px;"><?php echo " (".$flocaltime.")";?></span></td>
            </tr>
            </table>
            <table style="margin:auto;background: transparent;" width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td class="noborder" style="padding-bottom:10px;"><?php   echo vulnbreakdown($dbconn, $freport, $perms_where);  ?></td>
            </tr>
            </table>
        </td>
        <td class="nobborder" width="2%">
        &nbsp;
        </td>
        <td class="noborder" width="49%">
            <table style="margin:auto;border: 0pt none;" width="100%" cellspacing="0" cellpadding="0">
            <tr>
            <?php
            if($tz==0) {
                $slocaltime = $sreport_scantime;
            }
            else {
                $slocaltime = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($sreport_scantime)+3600*$tz);
            }
            ?>
                <td class="headerpr_no_bborder"><?php echo $sreport_name; ?><span style="font-size : 9px;"><?php echo " (".$slocaltime.")";?></span></td>
            </tr>
            </table>
            <table style="margin:auto;background: transparent;" width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td class="noborder" style="padding-bottom:10px;"><?php   echo vulnbreakdown($dbconn, $sreport, $perms_where);  ?></td>
            </tr>
            </table>
        </td>
    </tr>
</table>
<br />
<?

$vulns = get_vulns($dbconn, $freport, $sreport, $perms_where);

?>
<table style="margin:auto;border: 0pt none;" width="95%" cellspacing="0" cellpadding="0">
<tr>
    <td class="headerpr_no_bborder"><?php echo gettext("Summary of Scanned Hosts");?></span></td>
</tr>
</table>
<table style="margin:auto;" width="95%">
    <th><strong><?php echo _("Host")?></strong></th>
    <th><strong><?php echo _("Hostname")?></strong></th>
     <td width="128" style='background-color:#FFCDFF;border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px;border: 1px solid #C835ED;'>
        <?php echo _("Serious") ?>
     </td>
     <td width="128" style='background-color:#FFDBDB;border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px;border: 1px solid #FF0000;'>
        <?php echo _("High") ?>
    </td>
    <td width="128" style='background-color:#FFF283;border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px;border: 1px solid #FFA500;'>
        <?php echo _("Medium") ?>
     </td>
    <td width="128" style='background-color:#FFFFC0;border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px;border: 1px solid #FFD700;'>
        <?php echo _("Low") ?>
    </td>
    <td width="128" style='background-color:#FFFFE3;border-radius: 3px; -moz-border-radius: 3px; -webkit-border-radius: 3px;border: 1px solid #F0E68C;'>
        <?php echo _("Info") ?>
    </td></tr>
    <?php
    
    
    $tp = intval(count($vulns)/$maxpag); $tp += (count($vulns) % $maxpag == 0) ? 0 : 1;
    
    $to = $pag*$maxpag;
    $from = $to - $maxpag;
    
    $ips_to_show = array();
   
    $i=1;
    
    foreach ($vulns as $ctx_ip => $value) {

        if($i>$from && $i<=$to) {
            list($ctx, $ip) = explode("#", $ctx_ip);
            
            $host_id = key(Asset_host::get_id_by_ips($dbconn, $ip, $ctx));
            
            if(valid_hex32($host_id))
            {
                $name = Asset_host::get_name_by_id($dbconn, $host_id);
            }
            else
            {
                $name = $ip;
            }
            
            $ips_to_show[] = $ctx_ip ."|". $name;
            
            ?>
            <tr>
                <td style="text-align:center"><?php echo $ip?></td>
                <td style="text-align:center"><?php echo $name?></td>
                <?php
                $image = get_image($value[1]);
                ?>
                <td style="text-align:center"><?php echo (!is_null($value[1])) ? $value[1] : "-"; echo $image; ?></td>
                <?php
                $image = get_image($value[2]);
                ?>
                <td style="text-align:center"><?php echo (!is_null($value[2])) ? $value[2] : "-"; echo $image; ?></td>
                <?php
                $image = get_image($value[3]);
                ?>
                <td style="text-align:center"><?php echo (!is_null($value[3])) ? $value[3] : "-"; echo $image; ?></td>
                <?php
                $image = get_image($value[6]);
                ?>
                <td style="text-align:center"><?php echo (!is_null($value[6])) ? $value[6] : "-"; echo $image; ?></td>
                <?php
                $image = get_image($value[7]);
                ?>
                <td style="text-align:center"><?php echo (!is_null($value[7])) ? $value[7] : "-"; echo $image; ?></td>
            </tr>
            <?
        }
        $i++;
    }

    if( $maxpag<count($vulns) && (($pag<$tp) || ($pag>1) ) ) {
        ?>
        <tr>
        <td colspan="7" class="nobborder" style="text-align:center">
            <?php
            if ($pag>1) {?>
                <a href="compare_reports.php?freport=<?php echo $freport;?>&sreport=<?php echo $sreport;?>&pag=1" style="padding:0px 5px 0px 5px"><?php echo _("<< First");?></a>
                <a href="compare_reports.php?freport=<?php echo $freport;?>&sreport=<?php echo $sreport;?>&pag=<?php echo ($pag-1); ?>" style="padding:0px 5px 0px 5px"><?php echo _("< Previous");?></a>
            <?php
            }
            for($ipage=1;$ipage<=$tp;$ipage++) {
               ?>
                <a href="compare_reports.php?freport=<?php echo $freport;?>&sreport=<?php echo $sreport;?>&pag=<?php echo ($ipage) ?>" style="padding:0px 5px 0px 5px">
                <?php
                    if($ipage==$pag) { echo "<strong>"; }
                    echo $ipage;
                    if($ipage==$pag) { echo "</strong>"; }
                ?>
                </a>
               <?php
            }
            if ($pag<$tp) {?>
                <a href="compare_reports.php?freport=<?php echo $freport;?>&sreport=<?php echo $sreport;?>&pag=<?php echo ($pag+1) ?>" style="padding:0px 5px 0px 5px"><?php echo _("Next >");?></a>
                <a href="compare_reports.php?freport=<?php echo $freport;?>&sreport=<?php echo $sreport;?>&pag=<?php echo $tp; ?>" style="padding:0px 5px 0px 5px"><?php echo _("Last >");?></a>
            <?php
            }
            ?>
        </td>
        </tr>
    <?php
    }
    ?>
</table>
<br />

<?php

$images = array ("Serious" => "./images/risk7.gif", "High" => "./images/risk6.gif", "Medium" => "./images/risk3.gif", "Low" => "./images/risk2.gif", "Info" => "./images/risk1.gif");

$j = 0;
    
foreach($ips_to_show as $ip_name)
{
    $naip = array();
    $naip = explode("|",$ip_name);
    list($hctx, $ip)   = explode("#", $naip[0]);
    $name = $naip[1];
    
        ?>
    <table style="margin:auto;border: 0pt none;" width="95%" cellspacing="0" cellpadding="0">
        <tr>
            <td colspan="8" class="sec_title"><?php echo $ip. (($ip==$name)? "" : " - ".$name);?></span></td>
        </tr>
    </table>
    <table style="margin:auto;" width="95%">
        <tr>
            <th width="20%"><?php echo gettext("Vulname"); ?></th>
            <th width="10%"><?php echo gettext("Vuln id"); ?></th>
            <th width="10%"><?php echo gettext("Service"); ?></th>
            <th width="10%"><?php echo gettext("Severity"); ?></th>
            <th width="20%"><?php echo gettext("Vulname"); ?></th>
            <th width="10%"><?php echo gettext("Vuln id"); ?></th>
            <th width="10%"><?php echo gettext("Service"); ?></th>
            <th width="10%"><?php echo gettext("Severity"); ?></th>
        </tr>

    <?php
    
    $risks = array ("1" , "2" , "3" , "6" , "7");

    foreach ($risks as $risk_value) {
    
        $perms_where_t1    = (Session::get_ctx_where() != "") ? " AND t1.ctx in (".Session::get_ctx_where().")" : "";
    
        $report1_data = array();
        $query ="SELECT DISTINCT t1.risk, t1.hostIP, HEX(ctx) as ctx, t1.hostname, t1.port, t1.protocol, t1.app, t1.scriptid, t1.msg, t2.name FROM vuln_nessus_results as t1
                        LEFT JOIN vuln_nessus_plugins as t2 on t2.id=t1.scriptid
                        WHERE t1.report_id=$freport and t1.hostIP='$ip' and t1.ctx=UNHEX('$hctx') $perms_where_t1 and t1.falsepositive='N' and t1.risk=$risk_value";
                        
        $dbconn->SetFetchMode(ADODB_FETCH_NUM);

        $result=$dbconn->Execute($query);
        
        while (list($risk, $hostIP, $ctx, $hostname, $port, $protocol, $app, $scriptid, $msg, $plugin_name)=$result->fields) {
            if(Session::hostAllowed_by_ip_ctx($dbconn, $hostIP, $ctx)) {
                $aux = array();
                
                $aux["risk"]        = $risk;
                $aux["app"]         = $app;
                $aux["msg"]         = $msg;
                $aux["scriptid"]    = $scriptid;
                $aux["port"]        = $port;
                $aux["protocol"]    = $protocol;
                $aux["plugin_name"] = $plugin_name;
                
                $report1_data["$scriptid|$port|$protocol|$msg"] = $aux;
            }
            $result->MoveNext();
        }
        
        $report2_data = array();
        $query ="SELECT DISTINCT t1.risk, t1.hostIP, HEX(ctx) as ctx, t1.hostname, t1.port, t1.protocol, t1.app, t1.scriptid, t1.msg, t2.name FROM vuln_nessus_results as t1
                        LEFT JOIN vuln_nessus_plugins as t2 on t2.id=t1.scriptid
                        WHERE t1.report_id=$sreport and t1.hostIP='$ip' and t1.ctx=UNHEX('$hctx') $perms_where_t1 and t1.falsepositive='N' and t1.risk=$risk_value";
        $result=$dbconn->Execute($query);
        
        while (list($risk, $hostIP, $ctx, $hostname, $port, $protocol, $app, $scriptid, $msg, $plugin_name)=$result->fields) {
            if(Session::hostAllowed_by_ip_ctx($dbconn, $hostIP, $ctx)) {
                $aux = array();
                
                $aux["risk"]        = $risk;
                $aux["app"]         = $app;
                $aux["msg"]         = $msg;
                $aux["scriptid"]    = $scriptid;
                $aux["port"]        = $port;
                $aux["protocol"]    = $protocol;
                $aux["plugin_name"] = $plugin_name;
                
                $report2_data["$scriptid|$port|$protocol|$msg"] = $aux;
            }
            $result->MoveNext();
        }

        $colors = array ("1" => "#FFCDFF", "2" => "#FFDBDB", "3" => "#FFF283", "6" => "#FFFFC0", "7" => "#FFFFE3");
        
        foreach($report1_data as $key => $value) {
            $tmprisk = getrisk($value["risk"]);
            $value["msg"] = preg_replace("/^[ \t]*/","",$value["msg"]);
            $value["msg"] = preg_replace("/\n/","<br>",$value["msg"]);
            $value["msg"] = preg_replace("/^\<br\>/i","",str_replace("\\r", "", $value["msg"]));
            $value["msg"] = preg_replace("/(Solution|Overview|Synopsis|Description|See also|Plugin output|References|Vulnerability Insight|
                                            Impact|Impact Level|Affected Software\/OS|Fix|Information about this scan)\s*:/","<br /><strong>\\1:</strong><br />",$value["msg"]);

            ?>
                <tr>
                    <td colspan="4" width="50%" style="background-color:<?php echo $colors[$value["risk"]] ?>">
                        <table width="100%" class="transparent">
                            <tr>
                                <td width="40%" valign="top" style="text-align:left;" class="nobborder">
                                    <a style="padding-left:0px;" href="" onclick="toogle_details('<?php echo $j; ?>');return false;">
                                        <img id="img<?php echo $j; ?>" src="../pixmaps/plus-small.png" align="absmiddle"/></a>
                                    <strong><?php if (trim($value["plugin_name"])!="") { echo $value["plugin_name"]; } else { echo _("No plugin name"); }?></strong>
                                </td>
                                <td width="20%" valign="top" style="text-align:center;" class="nobborder"><?php echo $value["scriptid"]; ?></td>
                                <td width="20%" valign="top" style="text-align:center;" class="nobborder">
                                    <table width="100%" class="transparent" cellpadding="0" cellspacing="0">
                                        <tr><td class="nobborder" style="text-align:center"><?php echo $value["app"]; ?></td></tr>
                                        <tr><td class="nobborder" style="text-align:center">(<?php echo $value["port"]."/".$value["protocol"]; ?>)</td></tr>
                                    </table>
                                </td>
                                <td width="20%" valign="top" style="text-align:center;" class="nobborder">
                                <?php
                                    echo $tmprisk;
                                    echo "<img align='absmiddle' src='".$images[$tmprisk]."' style='border: 1px solid ;margin-left:5px;width: 25px; height: 10px;'/>";
                                ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td colspan="4" width="50%" style="background-color:<?php echo ($report2_data[$key]=="") ? "#EFFFF6" : $colors[$value["risk"]];?>" valign="top">
                        <?php 
                        if ($report2_data[$key]!="") {
                            ?>
                            <table width="100%" class="transparent">
                                <tr>
                                    <td width="40%" valign="top" style="text-align:left;" class="nobborder">
                                    <strong><?php if (trim($value["plugin_name"])!="") { echo $value["plugin_name"]; } else { echo _("No plugin name"); }?></strong></td>
                                    <td width="20%" valign="top" style="text-align:center;" class="nobborder"><?php echo $value["scriptid"]; ?></td>
                                    <td width="20%" valign="top" class="nobborder">
                                        <table width="100%" class="transparent" cellpadding="0" cellspacing="0">
                                            <tr><td class="nobborder" style="text-align:center"><?php echo $value["app"]; ?></td></tr>
                                            <tr><td class="nobborder" style="text-align:center">(<?php echo $value["port"]."/".$value["protocol"]; ?>)</td></tr>
                                        </table>
                                    <td width="20%" valign="top" style="text-align:center;" class="nobborder">
                                        <?php
                                        echo $tmprisk;
                                        echo "<img align='absmiddle' src='".$images[$tmprisk]."' style='border: 1px solid ;margin-left:5px;width: 25px; height: 10px;'/>";
                                    ?>
                                    </td>
                                </tr>
                            </table>
                            <?php
                            unset($report2_data[$key]);
                        }
                        else {
                            echo "&nbsp;";
                        }
                        ?>
                    </td>
                </tr>
                <tr style="display:none;" id="tr<?php echo $j; ?>">
                    <td colspan="8" style="text-align:left;padding:0px 10px 10px 10px;background-color:<?php echo $colors[$value["risk"]];?>">
                        <?php echo $value["msg"]; ?>
                    </td>
                </tr>
                <?php
                
            $j++;
        }
        foreach($report2_data as $key => $value) {
            $tmprisk = getrisk($value["risk"]);
            
            $value["msg"] = preg_replace("/^[ \t]*/","",$value["msg"]);
            $value["msg"] = preg_replace("/\n/","<br>",$value["msg"]);
            $value["msg"] = preg_replace("/^\<br\>/i","",str_replace("\\r", "", $value["msg"]));
            $value["msg"] = preg_replace("/(Solution|Overview|Synopsis|Description|See also|Plugin output|References|Vulnerability Insight|
                                            Impact|Impact Level|Affected Software\/OS|Fix|Information about this scan)\s*:/","<br /><strong>\\1:</strong><br />",$value["msg"]);
            ?>
                <tr>
                    <td colspan="4" width="50%" style="text-align:center;background-color:#FFEFF3;">
                        &nbsp;
                    </td>
                    <td colspan="4" width="50%" style="text-align:center;background-color:<?php echo $colors[$value["risk"]] ?>">
                        <?php 
                        if ($report2_data[$key]!="") {
                            ?>
                            <table width="100%" class="transparent">
                                <tr>
                                    <td width="40%" valign="top" class="nobborder" style="text-align:left;">
                                        <a style="padding-left:0px;" href="" onclick="toogle_details('<?php echo $j; ?>');return false;">
                                            <img id="img<?php echo $j; ?>" src="../pixmaps/plus-small.png" align="absmiddle"/></a>
                                    <strong><?php if (trim($value["plugin_name"])!="") { echo $value["plugin_name"]; } else { echo _("No plugin name"); }?></strong></td>
                                    <td width="20%" valign="top" class="nobborder" style="text-align:center;"><?php echo $value["scriptid"]; ?></td>
                                    <td width="20%" valign="top" class="nobborder" style="text-align:center;">
                                    <table width="100%" class="transparent" cellpadding="0" cellspacing="0">
                                        <tr><td class="nobborder" style="text-align:center"><?php echo $value["app"]; ?></td></tr>
                                        <tr><td class="nobborder" style="text-align:center">(<?php echo $value["port"]."/".$value["protocol"]; ?>)</td></tr>
                                    </table>
                                    </td>
                                    <td width="20%" valign="top" class="nobborder" style="text-align:center;">
                                    <?php
                                        echo $tmprisk;
                                        echo "<img align='absmiddle' src='".$images[$tmprisk]."' style='border: 1px solid ;margin-left:5px;width: 25px; height: 10px;'/>";
                                    ?>
                                    </td>
                                </tr>
                                <tr id="tr<?php echo $j; ?>" style="display:none;">
                                    <td colspan="4" valign="top" style="text-align:left;padding-left:21px;" class="nobborder"><?php
                                    echo $value["msg"]; ?>
                                    </td>
                                </tr>
                            </table>
                            <?php
                        }
                        else {
                            echo "&nbsp;";
                        }
                        ?>
                    </td>
                </tr>
            <?php
            $j++;
        }
    }
    ?>
    </table>
    <br />
    <?php
}
$dbconn->disconnect();


// functions

function vulnbreakdown($dbconn, $report, $perms_where){   //GENERATE CHARTS
    $query = "SELECT count(risk) as count, risk, hostIP, HEX(ctx) as ctx
                     FROM (SELECT DISTINCT risk, hostIP, ctx, hostname, port, protocol, app, scriptid, msg FROM vuln_nessus_results
                     WHERE report_id=$report and falsepositive='N' $perms_where) as t GROUP BY risk";
                     
    $dbconn->SetFetchMode(ADODB_FETCH_BOTH);
    
    $result=$dbconn->Execute($query);

    $prevrisk=0;
    $chartimg="./graph1.php?graph=1";

    while (list($riskcount, $risk)=$result->fields) {
        if(Session::hostAllowed_by_ip_ctx($dbconn, $result->fields["hostIP"], $result->fields["ctx"])) {
            for ($i=0;$i<$risk-$prevrisk-1;$i++) {
                $missedrisk=$prevrisk+$i+1;
                $chartimg.="&amp;risk$missedrisk=0";
            }
            $prevrisk=$risk;
            $chartimg.="&amp;risk$risk=$riskcount";
        }
        $result->MoveNext();
    }
   if (intval($prevrisk)!=7) {
        for($i=$prevrisk+1;$i<=7;$i++) {
            $chartimg.="&amp;risk$i=0";
        }
   }
   // print out the pie chart
   if($prevrisk!=0)
        $htmlchart .= "<font size=\"1\"><br></font>
            <img alt=\"Chart\" src=\"$chartimg\"><br>";
   else
        $htmlchart = "<br><span style=\"color:red\">"._("No vulnerabilty data")."</span>";
        
       
   return $htmlchart;
}

function get_vulns($dbconn, $freport, $sreport, $perms_where) {
    
    // first report
    $vulns = array();
    $query = "SELECT count(risk) as count, risk, hostIP, HEX(ctx) as ctx
                     FROM (SELECT DISTINCT risk, hostIP, ctx, port, protocol, app, scriptid, msg FROM vuln_nessus_results
                     WHERE report_id=$freport and falsepositive='N' $perms_where) as t GROUP BY risk, hostIP";
    
    $dbconn->SetFetchMode(ADODB_FETCH_ASSOC);
    
    $result=$dbconn->Execute($query);

    while (!$result->EOF) {
        if(Session::hostAllowed_by_ip_ctx($dbconn, $result->fields["hostIP"], $result->fields["ctx"])) {
            $asset_key = $result->fields["ctx"]."#".$result->fields["hostIP"];
            $vulns[$asset_key][$result->fields["risk"]] = $result->fields["count"]."/0";
        }
        $result->MoveNext();
    }
    
    // second report
    $query = "SELECT count(risk) as count, risk, hostIP, HEX(ctx) as ctx
                 FROM (SELECT DISTINCT risk, hostIP, ctx, port, protocol, app, scriptid, msg FROM vuln_nessus_results
                 WHERE report_id=$sreport and falsepositive='N' $perms_where) as t GROUP BY risk, hostIP";

    $result=$dbconn->Execute($query);

    while (!$result->EOF) {
        if(Session::hostAllowed_by_ip_ctx($dbconn, $result->fields["hostIP"], $result->fields["ctx"])) {
            $asset_key = $result->fields["ctx"]."#".$result->fields["hostIP"];
            
            if($vulns[$asset_key][$result->fields["risk"]]!= "") {
                $vulns[$asset_key][$result->fields["risk"]] = $vulns[$asset_key][$result->fields["risk"]]."/".$result->fields["count"];
                $vulns[$asset_key][$result->fields["risk"]] = preg_replace('/(\d+)\/0\/(\d+)/i', '$1/$2', $vulns[$asset_key][$result->fields["risk"]]);
                }
            else {
                $vulns[$asset_key][$result->fields["risk"]] = "0/".$result->fields["count"];
            }
        }
        $result->MoveNext();
    }

    asort($vulns,SORT_NUMERIC);

    return $vulns;
}
function get_image($value) {

    $image = "";

    if(!is_null($value) && preg_match("/(\d+)\/(\d+)/",$value,$found)) {
        if($found[1]==$found[2]) {
            $image = "<img src='../pixmaps/equal.png' align='absmiddle' border='0' title='equal' alt='equal' />";
        }
        else if (intval($found[1]) > intval($found[2])) {
            $image = " <img src='../pixmaps/green-arrow.png' align='absmiddle' border='0' title='".(intval($found[2]) - intval($found[1]))."' alt='".(intval($found[2]) - intval($found[1]))."' />";
        }
        else {
            $image = " <img src='../pixmaps/red-arrow.png' align='absmiddle' border='0' title='+".(intval($found[2]) - intval($found[1]))."' alt='+".(intval($found[2]) - intval($found[1]))."' />";
        }
    }

    return $image;
}
