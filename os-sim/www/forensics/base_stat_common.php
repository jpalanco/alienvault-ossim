<?php
/*******************************************************************************
** OSSIM Forensics Console
** Copyright (C) 2009 OSSIM/AlienVault
** Copyright (C) 2004 BASE Project Team
** Copyright (C) 2000 Carnegie Mellon University
**
** (see the file 'base_main.php' for license details)
**
** Built upon work by Roman Danyliw <rdd@cert.org>, <roman@danyliw.com>
** Built upon work by the BASE Project Team <kjohnson@secureideas.net>
*/

/**
* Function list:
* - SensorCnt()
* - SensorTotal()
* - EventCnt()
* - UniqueCntBySensor()
* - EventCntBySensor()
* - MinDateBySensor()
* - MaxDateBySensor()
* - UniqueDestAddrCntBySensor()
* - UniqueSrcAddrCntBySensor()
* - TCPPktCnt()
* - UDPPktCnt()
* - ICMPPktCnt()
* - PortscanPktCnt()
* - UniqueSrcIPCnt()
* - UniqueDstIPCnt()
* - UniqueIPCnt()
* - StartStopTime()
* - UniqueAlertCnt()
* - UniquePortCnt()
* - UniqueTCPPortCnt()
* - UniqueUDPPortCnt()
* - UniqueLinkCnt()
* - PrintGeneralStats()
* - plot_graphic()
* - range_graphic()
* - use_ac()
*/


require_once ('av_init.php');
Session::logcheck("analysis-menu", "EventsForensics");

require_once("ossim_conf.inc");
$conf = $GLOBALS["CONF"];
$cloud_instance = ($conf->get_conf("cloud_instance", FALSE) == 1) ? true : false;
defined('_BASE_INC') or die('Accessing this file directly is not allowed.');
include_once ("$BASE_path/includes/base_constants.inc.php");


function SensorCnt($db, $join = "", $where = "") {
    if ($join == "" && $where == "") $result = $db->baseExecute("SELECT sensors FROM event_stats ORDER BY timestamp DESC LIMIT 1");
    else $result = $db->baseExecute("SELECT COUNT(DISTINCT acid_event.sid) FROM acid_event $join $where");
    $myrow = $result->baseFetchRow();
    $num = $myrow[0];
    $result->baseFreeRows();
    return $num;
}


function SensorTotal($db) 
{
    $result = $db->baseExecute("SELECT sensors_total FROM event_stats ORDER BY timestamp DESC LIMIT 1");
    $myrow = $result->baseFetchRow();
    $num = $myrow[0];
    $result->baseFreeRows();
    return $num;
}


function EventCnt($db, $join = "", $where = "", $force_query = "") 
{
    if ($force_query != "") {
        $result = $db->baseExecute($force_query);
    } else {
        if ($join == "" && $where == "") $result = $db->baseExecute("SELECT total_events FROM event_stats ORDER BY timestamp DESC LIMIT 1");
        else $result = $db->baseExecute("SELECT COUNT(acid_event.id) FROM acid_event $join $where");
    }
    $myrow = $result->baseFetchRow();
    $num = $myrow[0];
    $result->baseFreeRows();
    return $num;
}


/*
* Takes: Numeric sensor ID from the Sensor table (SID), and
*	  database connection.
*
* Returns: The number of unique alert descriptions for the
* 	    given sensor ID.
*
*/
function UniqueCntBySensor($sensorID, $db) 
{
    /* Calculate the Unique Alerts */
    $query = "SELECT COUNT(DISTINCT signature) FROM acid_event WHERE sid = '" . $sensorID . "'";
    $result = $db->baseExecute($query);
    if ($result) {
        $row = $result->baseFetchRow();
        $num = $row[0];
        $result->baseFreeRows();
    } else $num = 0;
    return $num;
}


/*
* Takes: Numeric sensor ID from the Sensor table (SID), and
*        database connection.
*
* Returns: The total number of alerts for the given sensor ID
*/
function EventCntBySensor($sensorID, $db) 
{
    $query = "SELECT count(*) FROM acid_event where sid = '" . $sensorID . "'";
    $result = $db->baseExecute($query);
    $myrow = $result->baseFetchRow();
    $num = $myrow[0];
    $result->baseFreeRows();
    return $num;
}


function MinDateBySensor($sensorID, $db) 
{
    $query = "SELECT min(timestamp) FROM acid_event WHERE sid= '" . $sensorID . "'";
    $result = $db->baseExecute($query);
    $myrow = $result->baseFetchRow();
    $num = $myrow[0];
    $result->baseFreeRows();
    return $num;
}


function MaxDateBySensor($sensorID, $db) 
{
    $query = "SELECT max(timestamp) FROM acid_event WHERE sid='" . $sensorID . "'";
    $result = $db->baseExecute($query);
    $myrow = $result->baseFetchRow();
    $num = $myrow[0];
    $result->baseFreeRows();
    return $num;
}


function UniqueDestAddrCntBySensor($sensorID, $db) 
{
    $query = "SELECT COUNT(DISTINCT ip_dst) from acid_event WHERE sid='" . $sensorID . "'";
    $result = $db->baseExecute($query);
    $row = $result->baseFetchRow();
    $num = $row[0];
    $result->baseFreeRows();
    return $num;
}


function UniqueSrcAddrCntBySensor($sensorID, $db) 
{
    $query = "SELECT COUNT(DISTINCT ip_src) from acid_event WHERE sid='" . $sensorID . "'";
    $result = $db->baseExecute($query);
    $row = $result->baseFetchRow();
    $num = $row[0];
    $result->baseFreeRows();
    return $num;
}


function TCPPktCnt($db) 
{
    $result = $db->baseExecute("SELECT tcp_events FROM event_stats ORDER BY timestamp DESC LIMIT 1");
    $myrow = $result->baseFetchRow();
    $num = $myrow[0];
    $result->baseFreeRows();
    return $num;
}


function UDPPktCnt($db) 
{
    $result = $db->baseExecute("SELECT udp_events FROM event_stats ORDER BY timestamp DESC LIMIT 1");
    $myrow = $result->baseFetchRow();
    $num = $myrow[0];
    $result->baseFreeRows();
    return $num;
}


function ICMPPktCnt($db) 
{
    $result = $db->baseExecute("SELECT icmp_events FROM event_stats ORDER BY timestamp DESC LIMIT 1");
    $myrow = $result->baseFetchRow();
    $num = $myrow[0];
    $result->baseFreeRows();
    return $num;
}


function PortscanPktCnt($db) 
{
    $result = $db->baseExecute("SELECT portscan_events FROM event_stats ORDER BY timestamp DESC LIMIT 1");
    $myrow = $result->baseFetchRow();
    $num = $myrow[0];
    $result->baseFreeRows();
    return $num;
}


function UniqueSrcIPCnt($db, $join = "", $where = "") 
{
    if ($join == "" && $where == "") $result = $db->baseExecute("SELECT src_ips FROM event_stats ORDER BY timestamp DESC LIMIT 1");
    else $result = $db->baseExecute("SELECT COUNT(DISTINCT acid_event.ip_src) FROM acid_event $join WHERE $where"); //.
    //"WHERE acid_event.sid > 0 $where");
    $row = $result->baseFetchRow();
    $num = $row[0];
    $result->baseFreeRows();
    return $num;
}


function UniqueDstIPCnt($db, $join = "", $where = "") 
{
    if ($join == "" && $where == "") $result = $db->baseExecute("SELECT dst_ips FROM event_stats ORDER BY timestamp DESC LIMIT 1");
    else $result = $db->baseExecute("SELECT COUNT(DISTINCT acid_event.ip_dst) FROM acid_event $join WHERE $where"); //.
    //"WHERE acid_event.sid > 0 $where");
    $row = $result->baseFetchRow();
    $num = $row[0];
    $result->baseFreeRows();
    return $num;
}


function StartStopTime(&$start_time, &$stop_time, $db) 
{
    /* mstone 20050309 special case postgres */
    if ($db->DB_type != "postgres") {
        $result = $db->baseExecute("SELECT min(timestamp), max(timestamp) FROM acid_event");
    } else {
        $result = $db->baseExecute("SELECT (SELECT timestamp FROM acid_event ORDER BY timestamp ASC LIMIT 1), (SELECT timestamp FROM acid_event ORDER BY timestamp DESC LIMIT 1)");
    }
    $myrow = $result->baseFetchRow();
    $start_time = $myrow[0];
    $stop_time = $myrow[1];
    $result->baseFreeRows();
}


function UniqueAlertCnt($db, $join = "", $where = "") 
{
    if ($join == "" && $where == "") {
        $result = $db->baseExecute("SELECT uniq_events FROM event_stats ORDER BY timestamp DESC LIMIT 1");
    } else {
        $result = $db->baseExecute("SELECT COUNT(DISTINCT acid_event.signature) FROM acid_event $join " . "$where");
    }
    $row = $result->baseFetchRow();
    $num = $row[0];
    $result->baseFreeRows();
    return $num;
}


function UniquePortCnt($db, $join = "", $where = "") 
{
    if ($join == "" && $where == "") $result = $db->baseExecute("SELECT source_ports, dest_ports FROM event_stats ORDER BY timestamp DESC LIMIT 1");
    else $result = $db->baseExecute("SELECT COUNT(DISTINCT acid_event.layer4_sport),  " . "COUNT(DISTINCT acid_event.layer4_dport) FROM acid_event $join " . "$where");
    $row = $result->baseFetchRow();
    $result->baseFreeRows();
    return array(
        $row[0],
        $row[1]
    );
}


function UniqueTCPPortCnt($db, $join = "", $where = "") 
{
    if ($join == "" && $where == "") $result = $db->baseExecute("SELECT source_ports_tcp, dest_ports_tcp  FROM event_stats ORDER BY timestamp DESC LIMIT 1");
    else $result = $db->baseExecute("SELECT COUNT(DISTINCT acid_event.layer4_sport),  " . "COUNT(DISTINCT acid_event.layer4_dport) FROM acid_event $join" . " $where AND ip_proto='" . TCP . "'");
    $row = $result->baseFetchRow();
    $result->baseFreeRows();
    return array(
        $row[0],
        $row[1]
    );
}


function UniqueUDPPortCnt($db, $join = "", $where = "") 
{
    if ($join == "" && $where == "") $result = $db->baseExecute("SELECT source_ports_udp, dest_ports_udp FROM event_stats ORDER BY timestamp DESC LIMIT 1");
    else $result = $db->baseExecute("SELECT COUNT(DISTINCT acid_event.layer4_sport),  " . "COUNT(DISTINCT acid_event.layer4_dport) FROM acid_event $join" . " $where AND ip_proto='" . UDP . "'");
    $row = $result->baseFetchRow();
    $result->baseFreeRows();
    return array(
        $row[0],
        $row[1]
    );
}


function UniqueLinkCnt($db, $join = "", $where = "") 
{
    if (!stristr($where, "WHERE") && $where != "") $where = " WHERE $where ";
    if ($db->DB_type == "mysql") {
        if ($join == "" && $where == "") $result = $db->baseExecute("SELECT uniq_ip_links  FROM event_stats ORDER BY timestamp DESC LIMIT 1");
        else $result = $db->baseExecute("SELECT COUNT(DISTINCT acid_event.ip_src, acid_event.ip_dst, acid_event.ip_proto) FROM acid_event $join $where");
        $row = $result->baseFetchRow();
        $result->baseFreeRows();
    } else {
        if ($join == "" && $where == "") $result = $db->baseExecute("SELECT DISTINCT acid_event.ip_src, acid_event.ip_dst, acid_event.ip_proto FROM acid_event");
        else $result = $db->baseExecute("SELECT DISTINCT acid_event.ip_src, acid_event.ip_dst, acid_event.ip_proto FROM acid_event $join $where");
        $row[0] = $result->baseRecordCount();
        $result->baseFreeRows();
    }
    return $row[0];
}


function PrintGeneralStats($db) 
{
    GLOBAL $events_report_type, $sensors_report_type, $unique_events_report_type, $unique_plugins_report_type;
    GLOBAL $unique_addr_report_type, $src_port_report_type, $dst_port_report_type, $unique_iplinks_report_type;
    GLOBAL $unique_country_events_report_type;
    GLOBAL $siem_events_title, $cloud_instance;
    
    $sensor_cnt_info[0] = gettext("Sensors/Total:") . "\n";
    $sensor_cnt_info[1] = "<a style='color:black;' href=\"base_stat_sensor.php?sort_order=occur_d\">";
    $sensor_cnt_info[2] = "</a>";
    $unique_alert_cnt_info[0] = gettext("Unique Events") . ":\n";
    $unique_alert_cnt_info[1] = "<a style='color:black;' href=\"base_stat_alerts.php?sort_order=occur_d\">";
    $unique_alert_cnt_info[2] = "</a>";
	$unique_plugin_cnt_info[0] = _("Unique Data Sources")."\n";
    $unique_plugin_cnt_info[1] = "<a style='color:black;' href=\"base_stat_plugins.php?sort_order=occur_d\">";
    $unique_plugin_cnt_info[2] = "</a>";
    $event_cnt_info[0] = "<strong>" . gettext("Total Number of Events:") . "</strong>\n";
    $event_cnt_info[1] = '<a style=\'color:black;\' href="base_qry_main.php?&amp;num_result_rows=-1' . '&amp;submit=' . gettext("Query DB") . '&amp;current_view=-1">';
    $event_cnt_info[2] = "</a>";
    $unique_src_ip_cnt_info[0] = gettext("Src IP addrs:");
    $unique_src_ip_cnt_info[1] = " " . BuildUniqueAddressLink(1,"","color:black;");
    $unique_src_ip_cnt_info[2] = "</a>";
    $unique_dst_ip_cnt_info[0] = gettext("Dest. IP addrs:");
    $unique_dst_ip_cnt_info[1] = " " . BuildUniqueAddressLink(2,"","color:black;");
    $unique_dst_ip_cnt_info[2] = "</a>";
    $unique_ip_cnt_info[1] = " <a style='color:black;' href=\"base_stat_uaddress.php?sort_order=occur_d\">";
    $unique_ip_cnt_info[2] = "</a>";
    $unique_links_info[0] = gettext("Unique IP links");
    $unique_links_info[1] = " <a style='color:black;' href=\"base_stat_iplink.php?sort_order=events_d&fqdn=no\">";
    $unique_links_info[2] = "</a>";
    $unique_links_fqdn = " <a style='color:black;' href=\"base_stat_iplink.php?sort_order=events_d&fqdn=yes\">[FQDN]</a>";
    $unique_src_port_cnt_info[0] = gettext("Source Ports: ");
    $unique_src_port_cnt_info[1] = " <a style='color:black;' href=\"base_stat_ports.php?sort_order=occur_d&port_type=1&amp;proto=-1\">";
    $unique_src_port_cnt_info[2] = "</a>";
    $unique_dst_port_cnt_info[0] = gettext("Dest Ports: ");
    $unique_dst_port_cnt_info[1] = " <a style='color:black;' href=\"base_stat_ports.php?sort_order=occur_d&port_type=2&amp;proto=-1\">";
    $unique_dst_port_cnt_info[2] = "</a>";
    $unique_tcp_src_port_cnt_info[0] = "TCP (";
    $unique_tcp_src_port_cnt_info[1] = " <a style='color:black;' href=\"base_stat_ports.php?sort_order=occur_d&port_type=1&amp;proto=6\">";
    $unique_tcp_src_port_cnt_info[2] = "</a>)";
    $unique_tcp_dst_port_cnt_info[0] = "TCP (";
    $unique_tcp_dst_port_cnt_info[1] = " <a style='color:black;' href=\"base_stat_ports.php?sort_order=occur_d&port_type=2&amp;proto=6\">";
    $unique_tcp_dst_port_cnt_info[2] = "</a>)";
    $unique_udp_src_port_cnt_info[0] = "UDP (";
    $unique_udp_src_port_cnt_info[1] = " <a style='color:black;' href=\"base_stat_ports.php?sort_order=occur_d&port_type=1&amp;proto=17\">";
    $unique_udp_src_port_cnt_info[2] = "</a>)";
    $unique_udp_dst_port_cnt_info[0] = "UDP (";
    $unique_udp_dst_port_cnt_info[1] = " <a style='color:black;' href=\"base_stat_ports.php?sort_order=occur_d&port_type=2&amp;proto=17\">";
    $unique_udp_dst_port_cnt_info[2] = "</a>)";
    $unique_ptypes_info[0] =  gettext("Product Types");
    $unique_ptypes_info[1] = " <a style='color:black;' href=\"base_stat_ptypes.php?sort_order=occur_d\">";
    $unique_ptypes_info[2] = "</a>";
    $unique_categories_info[0] = gettext("Categories");;
    $unique_categories_info[1] = " <a style='color:black;' href=\"base_stat_categories.php?sort_order=occur_d\">";
    $unique_categories_info[2] = "</a>";
    
        echo "<table class='transparent' width='100%' cellpadding=0 cellspacing=0 border=0><tr><td valign='top'>";
        
?>
	  <table class="transparent" cellpadding=5 style="border-left:1px solid #C4C0BB;border-bottom:1px solid #C4C0BB;border-right:1px solid #C4C0BB" cellspacing=0 border=0 width="100%">
		<tr>
	  <?php
        //$li_style = (preg_match("/base_stat_sensor\.php/",$_SERVER['SCRIPT_NAME'])) ? " style='color:#F37914'" : "";
        $color = (preg_match("/base_qry_main\.php/", $_SERVER['SCRIPT_NAME'])) ? "th" : "";
		$fontcolor = (preg_match("/base_qry_main\.php/", $_SERVER['SCRIPT_NAME'])) ? "white" : "black";
?>
		<td nowrap align="center" style="border-right:1px solid #C4C0BB" class="<?=$color?>">
			<a style="" href='base_qry_main.php?num_result_rows=-1&submit=Query+DB&current_view=-1'>
			<?=_("Events")?></a>
                <? if ($fontcolor=="white" && !$cloud_instance) { ?>
                <?php if ($_SESSION['current_cview'] == "IDM" || $_SESSION['current_cview'] == "default") { ?><a href="javascript:;" onclick="javascript:report_launcher('Events_Report','pdf');return false"><img src="images/pdf-icon.png" border="0" align="absmiddle" title="<?=_("Launch PDF Report")?>"></a><?php } ?>
                <a href="javascript:;" onclick="javascript:report_launcher('Events_Report','<?=Util::htmlentities($events_report_type)?>');return false"><img src="images/csv-icon.png" border="0" align="absmiddle" title="<?=_("Download data in csv format")?>"></a>
                <? } ?>
		</td>
	  <?php
        //$li_style = (preg_match("/base_stat_alerts\.php/",$_SERVER['SCRIPT_NAME'])) ? " style='color:#F37914'" : "";
        $color = (preg_match("/base_stat_alerts\.php|base_stat_alerts_graph\.php/", $_SERVER['SCRIPT_NAME']) || preg_match("/base_stat_class\.php|base_stat_class_graph\.php/", $_SERVER['SCRIPT_NAME'])) ? "th" : "";
        if ($color == "th") { 
			//$unique_alert_cnt_info[1] = str_replace(":black",":white",$unique_alert_cnt_info[1]);
			//$class_cnt_info[1] = str_replace(":black",":white",$class_cnt_info[1]);
		}
		//echo "  <li$li_style>".$unique_alert_cnt_info[1].gettext("Unique Events").$unique_alert_cnt_info[2] . "</li>";
        
?>
			<td nowrap align="center" style="border-right:1px solid #C4C0BB" class="<?php echo $color
?>"><?php echo $unique_alert_cnt_info[1] . gettext("Unique Events") . $unique_alert_cnt_info[2] ?>
                <? if ($color=="th" && !$cloud_instance && preg_match("/base_stat_alerts\.php/", $_SERVER['SCRIPT_NAME'])) { ?>
                <a href="javascript:;" onclick="javascript:report_launcher('UniqueEvents_Report','pdf');return false"><img src="images/pdf-icon.png" border="0" align="absmiddle" title="<?=_("Launch PDF Report")?>"></a>
                <a href="javascript:;" onclick="javascript:report_launcher('UniqueEvents_Report','<?=Util::htmlentities($unique_events_report_type)?>');return false"><img src="images/csv-icon.png" border="0" align="absmiddle" title="<?=_("Download data in csv format")?>"></a>
                <? } ?>
				<!--<br>
				(<?php echo $class_cnt_info[1] . gettext("classifications") . $class_cnt_info[2] ?>)-->
			</td>

	  <?php
        //$li_style = (preg_match("/base_stat_sensor\.php/",$_SERVER['SCRIPT_NAME'])) ? " style='color:#F37914'" : "";
        $color = (preg_match("/base_stat_sensor\.php/", $_SERVER['SCRIPT_NAME'])) ? "th" : "";
        //if ($color == "th") $sensor_cnt_info[1] = str_replace(":black",":white",$sensor_cnt_info[1]);
		//echo "  <li$li_style>".$sensor_cnt_info[1]. gettext("Sensors") . "</a></li>";
        
?>
			<td nowrap align="center" style="border-right:1px solid #C4C0BB" class="<?php echo $color
?>"><?php echo $sensor_cnt_info[1] . gettext("Sensors") . $sensor_cnt_info[2] ?>
            <? if ($color=="th" && !$cloud_instance) { ?>
            <a href="javascript:;" onclick="javascript:report_launcher('Sensors_Report','pdf');return false"><img src="images/pdf-icon.png" border="0" align="absmiddle" title="<?=_("Launch PDF Report")?>"></a>
            <a  href="javascript:;" onclick="javascript:report_launcher('Sensors_Report','<?=Util::htmlentities($sensors_report_type)?>');return false"><img src="images/csv-icon.png" border="0" align="absmiddle" title="<?=_("Download data in csv format")?>"></a>
            <? } ?>
        </td>


	  <?php
        if ($db->baseGetDBversion() >= 103) {
            //$li_style = (preg_match("/base_stat_class\.php/",$_SERVER['SCRIPT_NAME'])) ? " style='color:#F37914'" : "";
            $color = (preg_match("/base_stat_plugins\.php/", $_SERVER['SCRIPT_NAME'])) ? "th" : "";
            //if ($color == "th") $unique_plugin_cnt_info[1] = str_replace(":black",":white",$unique_plugin_cnt_info[1]);
			//echo "<li$li_style>&nbsp;&nbsp;&nbsp;( ".$class_cnt_info[1].gettext("classifications")."</a> )</li>";
            
?>
			<td nowrap align="center" class="<?php echo $color
?>"><?php echo $unique_plugin_cnt_info[1] . gettext("Unique Data Sources") . $unique_plugin_cnt_info[2] ?>
                <? if ($color=="th" && !$cloud_instance) { ?>
                <a href="javascript:;" onclick="javascript:report_launcher('UniquePlugin_Report','pdf');return false"><img src="images/pdf-icon.png" border="0" align="absmiddle" title="<?=_("Launch PDF Report")?>"></a>
                <a href="javascript:;" onclick="javascript:report_launcher('UniquePlugin_Report','<?=Util::htmlentities($unique_plugins_report_type)?>');return false"><img src="images/csv-icon.png" border="0" align="absmiddle" title="<?=_("Download data in csv format")?>"></a>
                <? } ?>
            </td>
	  <?php
        }
?>
		</tr>
		<tr>
	  <?php
		//$src_lnk = "<a href='base_stat_uaddr.php?addr_type=".$_GET['addr_type']."&addhomeips=src' title='Add home networks IPs to current search criteria'><img src='images/homelan.png' border=0 align='absmiddle'></a>";
		//$dst_lnk = "<a href='base_stat_uaddr.php?addr_type=".$_GET['addr_type']."&addhomeips=dst' title='Add home networks IPs to current search criteria'><img src='images/homelan.png' border=0 align='absmiddle'></a>";

        //$li_style = (preg_match("/base_stat_uaddr\.php/",$_SERVER['SCRIPT_NAME'])) ? " style='color:#F37914'" : "";
        $color = (preg_match("/base_stat_uaddr|base_stat_uidm/", $_SERVER['SCRIPT_NAME'])) ? "th" : "";
        if ($color == "th") { //$unique_src_ip_cnt_info[1] = str_replace(":black",":white",$unique_src_ip_cnt_info[1]);
                                    //$unique_dst_ip_cnt_info[1] = str_replace(":black",":white",$unique_dst_ip_cnt_info[1]);
                                    //$unique_ip_cnt_info[1] = str_replace(":black",":white",$unique_ip_cnt_info[1]);
                                    if (!$cloud_instance) {
                                    	$pdf = "&nbsp;<a href=\"javascript:;\" onclick=\"javascript:report_launcher('UniqueAddress_Report".intval($_GET['addr_type'])."','pdf');return false\"><img src=\"images/pdf-icon.png\" border=\"0\" align=\"absmiddle\" title=\""._("Launch PDF Report")."\">&nbsp;";
                                    	$csv = "<a href=\"javascript:;\" onclick=\"javascript:report_launcher('UniqueAddress_Report".intval($_GET['addr_type'])."','".Util::htmlentities($unique_addr_report_type)."');return false\"><img src=\"images/csv-icon.png\" border=\"0\" align=\"absmiddle\" title=\""._("Download data in csv format")."\"></a>&nbsp;";
                                   } else  { $pdf = ""; $csv="";} 
                                   if ($_GET['addr_type'] == '1') $unique_src_ip_cnt_info[2] .= $pdf . $csv;
                                   if ($_GET['addr_type'] == '2') $unique_dst_ip_cnt_info[2] .= $pdf . $csv;                                   
                                  }
        else { $pdf = "<br>"; $csv="";}
		// echo "  <li$li_style>".gettext("Unique addresses: ").
        //       $unique_src_ip_cnt_info[1].gettext("Source").' | '.$unique_src_ip_cnt_info[2].
        //       $unique_dst_ip_cnt_info[1].gettext("Destination").$unique_dst_ip_cnt_info[2]."</li>";
        //echo "</td><td valign='top' style='padding-left:10px'>";
        $addrtype1 = ($_GET['addr_type'] == '1' || preg_match("/src_/",$_GET['addr_type'])) ? "underline" : "none";
        $addrtype2 = ($_GET['addr_type'] == '2' || preg_match("/dst_/",$_GET['addr_type'])) ? "underline" : "none";
        $report_type = ($_GET['proto'] == '6') ? 1 : (($_GET['proto'] == '17') ? 2 : 0);
        
        // IDM
        if ($_SESSION["_idm"]) {
        	$uat = "<a style='' href='javascript:;' onclick=\"$('#uniqueaddrsrc').hide();$('#uniqueaddrdst').hide();$('#uniqueaddr').toggle()\">".gettext("Unique")."</a>
        	<div style='position:relative; z-index:2; text-align:left'><div id='uniqueaddr' style='position:absolute;top:0;display:none;padding:2px 5px;margin:-21px 0px 0px 115px;background-color:#fefefe;border:1px solid #C4C0BB;white-space:nowrap;'>
        	<a style='color:black;font-weight:bold' href='base_stat_uaddress.php?sort_order=occur_d'>IP Addresses</a><br>
        	<a style='color:black;font-weight:bold' href='base_stat_uidm.php?addr_type=userdomain&sort_order=occur_d'>User@Domains</a><br>
        	<a style='color:black;font-weight:bold' href='base_stat_uidm.php?addr_type=hostname&sort_order=occur_d'>Hostnames</a><br>
        	</div></div>";
        	$uatsrc = "<a style='' href='javascript:;' onclick=\"$('#uniqueaddr').hide();$('#uniqueaddrdst').hide();$('#uniqueaddrsrc').toggle()\"><font style='text-decoration:$addrtype1'>".gettext("Source")."</font></a>".(($_GET['addr_type'] == '1' && preg_match("/base_stat_uaddr/", $_SERVER['SCRIPT_NAME'])) ? $pdf . $csv : "" )."
        	<div style='display:inline;position:relative; z-index:2; text-align:left'><div id='uniqueaddrsrc' style='position:absolute;top:0;display:none;padding:2px 5px;margin:-7px 0px 0px 1px;background-color:#fefefe;border:1px solid #C4C0BB;white-space:nowrap;'>
        	<a style='color:black;font-weight:bold' href='base_stat_uaddr.php?sort_order=occur_d&addr_type=1'>IP Addresses</a><br>
        	<a style='color:black;font-weight:bold' href='base_stat_uidmsel.php?addr_type=src_userdomain&sort_order=occur_d'>User@Domains</a><br>
        	<a style='color:black;font-weight:bold' href='base_stat_uidmsel.php?addr_type=src_hostname&sort_order=occur_d'>Hostnames</a><br>
        	</div></div>";
        	$uatdst = "<a style='' href='javascript:;' onclick=\"$('#uniqueaddr').hide();$('#uniqueaddrsrc').hide();$('#uniqueaddrdst').toggle()\"><font style='text-decoration:$addrtype2'>".gettext("Destination")."</font></a>".(($_GET['addr_type'] == '2' && preg_match("/base_stat_uaddr/", $_SERVER['SCRIPT_NAME'])) ? $pdf . $csv : "" )."
        	<div style='position:relative; z-index:2; text-align:left'><div id='uniqueaddrdst' style='position:absolute;top:0;display:none;padding:2px 5px;margin:-21px 0px 0px 150px;background-color:#fefefe;border:1px solid #C4C0BB;white-space:nowrap;'>
        	<a style='color:black;font-weight:bold' href='base_stat_uaddr.php?sort_order=occur_d&addr_type=2'>IP Addresses</a><br>
        	<a style='color:black;font-weight:bold' href='base_stat_uidmsel.php?addr_type=dst_userdomain&sort_order=occur_d'>User@Domains</a><br>
        	<a style='color:black;font-weight:bold' href='base_stat_uidmsel.php?addr_type=dst_hostname&sort_order=occur_d'>Hostnames</a><br>
        	</div></div>";
        } else {
        	$uat = $unique_ip_cnt_info[1] . gettext("Unique addresses") . $unique_ip_cnt_info[2] . ":<br>";
        	$uatsrc = $unique_src_ip_cnt_info[1] . "<font style='text-decoration:$addrtype1'>" . gettext("Source") . "</font>" . $unique_src_ip_cnt_info[2];
        	$uatdst = $unique_dst_ip_cnt_info[1] . "<font style='text-decoration:$addrtype2'>" . gettext("Destination") . "</font>" . $unique_dst_ip_cnt_info[2];
        }
?>
			<td align="center" style='border-right:1px solid #C4C0BB;border-top:1px solid #C4C0BB;<? if ($color == "th") echo "color:white" ?>' class="<?php echo $color
?>"><?php echo $uat . $uatsrc . " | " . $uatdst ?></td>

	  <?php
	    # SRC/DST PORTS
        $color = (preg_match("/base_stat_ports\.php/", $_SERVER['SCRIPT_NAME'])) ? "th" : "";
        if ($color == "th" && $_GET['port_type'] == 1) {
                                    /*
									$unique_src_port_cnt_info[1] = str_replace(":black",":white",$unique_src_port_cnt_info[1]);
									$unique_tcp_src_port_cnt_info[1] = str_replace(":black",":white",$unique_tcp_src_port_cnt_info[1]);
									$unique_udp_src_port_cnt_info[1] = str_replace(":black",":white",$unique_udp_src_port_cnt_info[1]);
									$unique_dst_port_cnt_info[1] = str_replace(":black",":white",$unique_dst_port_cnt_info[1]);
									$unique_tcp_dst_port_cnt_info[1] = str_replace(":black",":white",$unique_tcp_dst_port_cnt_info[1]);
									$unique_udp_dst_port_cnt_info[1] = str_replace(":black",":white",$unique_udp_dst_port_cnt_info[1]);
									*/
                                    if (!$cloud_instance) {
                                    	$pdfs = "<a href=\"javascript:;\" onclick=\"javascript:report_launcher('SourcePort_Report$report_type','pdf');return false\"><img src=\"images/pdf-icon.png\" border=\"0\" align=\"absmiddle\" title=\""._("Launch PDF Report")."\">";
                                    	$csvs = "<a href=\"javascript:;\" onclick=\"javascript:report_launcher('SourcePort_Report$report_type','".Util::htmlentities($src_port_report_type)."');return false\"><img src=\"images/csv-icon.png\" border=\"0\" align=\"absmiddle\" title=\""._("Download data in csv format")."\"></a> &nbsp;";
                                    } else  { $pdfs = ""; $csvs=" ";} 
       } elseif ($color == "th" && $_GET['port_type'] == 2) {
									/*
                                    $unique_src_port_cnt_info[1] = str_replace(":black",":white",$unique_src_port_cnt_info[1]);
									$unique_tcp_src_port_cnt_info[1] = str_replace(":black",":white",$unique_tcp_src_port_cnt_info[1]);
									$unique_udp_src_port_cnt_info[1] = str_replace(":black",":white",$unique_udp_src_port_cnt_info[1]);
									$unique_dst_port_cnt_info[1] = str_replace(":black",":white",$unique_dst_port_cnt_info[1]);
									$unique_tcp_dst_port_cnt_info[1] = str_replace(":black",":white",$unique_tcp_dst_port_cnt_info[1]);
									$unique_udp_dst_port_cnt_info[1] = str_replace(":black",":white",$unique_udp_dst_port_cnt_info[1]);
									*/
                                    if (!$cloud_instance) {
                                    	$pdfd = "<a href=\"javascript:;\" onclick=\"javascript:report_launcher('DestinationPort_Report$report_type','pdf');return false\"><img src=\"images/pdf-icon.png\" border=\"0\" align=\"absmiddle\" title=\""._("Launch PDF Report")."\">";
                                    	$csvd = "<a href=\"javascript:;\" onclick=\"javascript:report_launcher('DestinationPort_Report$report_type','".Util::htmlentities($dst_port_report_type)."');return false\"><img src=\"images/csv-icon.png\" border=\"0\" align=\"absmiddle\" title=\""._("Download data in csv format")."\"></a> &nbsp;";
                                    } else  { $pdfd = ""; $csvd=" ";} 
        } else { $pdfs = ""; $csvs=" "; $pdfd = ""; $csvd=" ";}

        $sprototcp = ($_GET['proto'] == '6' && $_GET['port_type'] == '1') ? "underline" : "none";
        $sprotoudp = ($_GET['proto'] == '17' && $_GET['port_type'] == '1') ? "underline" : "none";
        $dprototcp = ($_GET['proto'] == '6' && $_GET['port_type'] == '2') ? "underline" : "none";
        $dprotoudp = ($_GET['proto'] == '17' && $_GET['port_type'] == '2') ? "underline" : "none";
?>
			<td align="center" style='border-right:1px solid #C4C0BB;border-top:1px solid #C4C0BB;<? if ($color == "th") echo "color:white" ?>' class="<?php echo $color
?>"><?php echo $unique_src_port_cnt_info[1] . gettext("Source Port") . ":" . $unique_src_port_cnt_info[2] . " $pdfs $csvs" . $unique_tcp_src_port_cnt_info[1] . " <font style='text-decoration:$sprototcp'>TCP</font></a> | " . $unique_udp_src_port_cnt_info[1] . " <font style='text-decoration:$sprotoudp'>UDP</font></a>" ?>
            <br>
      <?php echo $unique_dst_port_cnt_info[1] . gettext("Destination Port") . ":" . $unique_dst_port_cnt_info[2] . " $pdfd $csvd" . $unique_tcp_dst_port_cnt_info[1] . " <font style='text-decoration:$dprototcp'>TCP</font></a> | " . $unique_udp_dst_port_cnt_info[1] . " <font style='text-decoration:$dprotoudp'>UDP</font></a>" ?>
			</td> 
<?php
	    # TAXONOMY
        $color = (preg_match("/base_stat_ptypes\.php|base_stat_categories\.php/", $_SERVER['SCRIPT_NAME'])) ? "th" : "";
        /*
        if ($color == "th") {
		    $unique_ptypes_info[1] = str_replace(":black",":white",$unique_ptypes_info[1]);
		    $unique_categories_info[1] = str_replace(":black",":white",$unique_categories_info[1]);
		}
		*/			
?>
			<td align="center" style='border-right:1px solid #C4C0BB;border-top:1px solid #C4C0BB;' class="<?php echo $color
?>"><?php echo gettext("Taxonomy") ?><br/><?php echo $unique_ptypes_info[1] . $unique_ptypes_info[0] . $unique_ptypes_info[2] . " | " . $unique_categories_info[1] . $unique_categories_info[0] . $unique_categories_info[2] ?></td>

	  <?php
	    # IP / COUNTRY
        $color = (preg_match("/base_stat_iplink\.php|base_stat_country\.php/", $_SERVER['SCRIPT_NAME'])) ? "th" : "";
        /*
        if ($color == "th") {
		    $unique_links_info[1] = str_replace(":black",":white",$unique_links_info[1]);
		    $unique_links_fqdn = str_replace(":black",":white",$unique_links_fqdn);
		}
		*/
            
?>
			<td nowrap align="center" style='border-top:1px solid #C4C0BB;' class="<?php echo $color
?>"><?php echo $unique_links_info[1] . $unique_links_info[0] . $unique_links_info[2]. $unique_links_fqdn ?>
            <? if ($color=="th" && !$cloud_instance && preg_match("/base_stat_iplink\.php/", $_SERVER['SCRIPT_NAME']) && GET('fqdn')=='no') { ?>
            <a href="javascript:;" onclick="javascript:report_launcher('UniqueIPLinks_Report','pdf');return false"><img src="images/pdf-icon.png" border="0" align="absmiddle" title="<?=_("Launch PDF Report")?>"></a>
            <a href="javascript:;" onclick="javascript:report_launcher('UniqueIPLinks_Report','<?=Util::htmlentities($unique_iplinks_report_type)?>');return false"><img src="images/csv-icon.png" border="0" align="absmiddle" title="<?=_("Download data in csv format")?>"></a>
            <? } ?>
<br><a href="base_stat_country.php"><?=_("Unique Country Events")?></a>
            <? if ($color=="th" && !$cloud_instance && preg_match("/base_stat_country\.php/", $_SERVER['SCRIPT_NAME'])) { ?>
            <a href="javascript:;" onclick="javascript:report_launcher('UniqueCountryEvents_Report','pdf');return false"><img src="images/pdf-icon.png" border="0" align="absmiddle" title="<?=_("Launch PDF Report")?>"></a>
            <a href="javascript:;" onclick="javascript:report_launcher('UniqueCountryEvents_Report','<?=Util::htmlentities($unique_country_events_report_type)?>');return false"><img src="images/csv-icon.png" border="0" align="absmiddle" title="<?=_("Download data in csv format")?>"></a>
            <? } ?>
</td>
<?php
        //echo "</td></tr></table>";
        
?>
	  </tr>
	 </table>
	  <?php
        echo "</td></tr></table>";
}


function get_graph_url($index) 
{
	//var_dump($index);
	//$shortmonths = array('Jan'=>'01', 'Feb'=>'02', 'Mar'=>'03', 'Apr'=>'04', 'May'=>'05', 'Jun'=>'06', 'Jul'=>'07', 'Aug'=>'08', 'Sep'=>'09', 'Oct'=>'10', 'Nov'=>'11', 'Dec'=>'12');
	$months = array('January'=>'01', 'February'=>'02', 'March'=>'03', 'April'=>'04', 'May'=>'05', 'June'=>'06', 'July'=>'07', 'August'=>'08', 'September'=>'09', 'October'=>'10', 'November'=>'11', 'December'=>'12');
	$daysmonths = array('January'=>'31', 'February'=>'28', 'March'=>'31', 'April'=>'30', 'May'=>'31', 'June'=>'30', 'July'=>'31', 'August'=>'31', 'September'=>'30', 'October'=>'31', 'November'=>'30', 'December'=>'31');
	//$url = "new=1&submit=Query+DB&num_result_rows=-1";
	$url = "";
    
	//Today (8h)
	if (preg_match("/^(\d+) h/",$index,$found)) {
		$url .= "&time_range=".Util::htmlentities($_SESSION['time_range'])."&time[0][1]=".urlencode(">=");
		$url .= "&time[0][2]=".(($_SESSION['time'][0][2] != '') ? $_SESSION['time'][0][2] : date("m"));
		$url .= "&time[0][3]=".(($_SESSION['time'][0][3]!= '') ? $_SESSION['time'][0][3] : date("d"));
		$url .= "&time[0][4]=".(($_SESSION['time'][0][4]!= '') ? $_SESSION['time'][0][4] : date("Y"));
		$url .= "&time[0][5]=".$found[1];
		$url .= "&time[0][6]=00&time[0][7]=00";
		$url .= "&time_cnt=2";
		$url .= "&time[1][1]=".urlencode("<=");
		$url .= "&time[1][2]=".(($_SESSION['time'][1][2]!= '') ? $_SESSION['time'][1][2] : date("m"));
		$url .= "&time[1][3]=".(($_SESSION['time'][1][3]!= '') ? $_SESSION['time'][1][3] : date("d"));
		$url .= "&time[1][4]=".(($_SESSION['time'][1][4]!= '') ? $_SESSION['time'][1][4] : date("Y"));
		$url .= "&time[1][5]=".$found[1];
		$url .= "&time[1][6]=59&time[1][7]=59";
	}
	// Last 24 Hours (21 8 -> 21h 8Sep)
	elseif (preg_match("/^(\d+) (\d+)/",$index,$found)) {
		$desde= strtotime($found[2]."-".date("m")."-".date("Y")." ".$found[1].":00:00");
		$fecha_actual = strtotime(date("d-m-Y H:i:00",time()));
		if($fecha_actual<$desde) { $anio = strval((int)date("Y")-1);}
		else $anio = date("Y");
		
		$url .= "&time_range=range&time[0][1]=".urlencode(">=");
		$url .= "&time[0][2]=".date("m");
		$url .= "&time[0][3]=".$found[2];
		$url .= "&time[0][4]=".$anio;
		$url .= "&time[0][5]=".$found[1];
		$url .= "&time[0][6]=00&time[0][7]=00";
		$url .= "&time_cnt=2";
		$url .= "&time[1][1]=".urlencode("<=");
		$url .= "&time[1][2]=".date("m");
		$url .= "&time[1][3]=".$found[2];
		$url .= "&time[1][4]=".$anio;
		$url .= "&time[1][5]=".$found[1];
		$url .= "&time[1][6]=59&time[1][7]=59";
	}
	//Last Week, Last two Weeks, Last Month (5 September)
	elseif (preg_match("/^(\d+) ([A-Z].+)/",$index,$found)) {
		$desde= strtotime($found[1]."-".$months[$found[2]]."-".date("Y")." 00:00:00");
		$fecha_actual = strtotime(date("d-m-Y H:i:00",time()));
		if($fecha_actual<$desde) { $anio = strval((int)date("Y")-1);}
		else $anio = date("Y");
		
		$url .= "&time_range=range&time[0][1]=".urlencode(">=");
		$url .= "&time[0][2]=".$months[$found[2]];
		$url .= "&time[0][3]=".$found[1];
		$url .= "&time[0][4]=".$anio;
		$url .= "&time[0][5]=00";
		$url .= "&time[0][6]=00&time[0][7]=00";
		$url .= "&time_cnt=2";
		$url .= "&time[1][1]=".urlencode("<=");
		$url .= "&time[1][2]=".$months[$found[2]];
		$url .= "&time[1][3]=".$found[1];
		$url .= "&time[1][4]=".$anio;
		$url .= "&time[1][5]=23";
		$url .= "&time[1][6]=59&time[1][7]=59";
	}
	//All (October 2009)
	elseif (preg_match("/^([A-Z].+) (\d+)/",$index,$found)) {
		$url .= "&time_range=range&time[0][1]=".urlencode(">=");
		$url .= "&time[0][2]=".$months[$found[1]];
		$url .= "&time[0][3]=01";
		$url .= "&time[0][4]=".$found[2];
		$url .= "&time[0][5]=00";
		$url .= "&time[0][6]=00&time[0][7]=00";
		$url .= "&time_cnt=2";
		$url .= "&time[1][1]=".urlencode("<=");
		$url .= "&time[1][2]=".$months[$found[1]];
		$url .= "&time[1][3]=".$daysmonths[$found[1]];
		$url .= "&time[1][4]=".$found[2];
		$url .= "&time[1][5]=23";
		$url .= "&time[1][6]=59&time[1][7]=59";
	}

	return $url;
}


// plot graph
function plot_graphic($id, $height, $width, $xaxis, $yaxis, $xticks, $xlabel, $display = false, $lnk = "", $script = true) 
{
	//var_dump($xlabel);
	//var_dump($xticks);
    $urls="";
    $plot = ($script) ? '<script language="javascript" type="text/javascript">' : '';
   // $plot.= '$(document).ready( function() {';
    $plot.= 'var options = { ';
    $plot.= 'lines: { show:true, labelHeight:0, lineWidth: 0.7},';
    $plot.= 'points: { show:false, radius: 2 }, legend: { show: false },';
    $plot.= 'yaxis: { ticks:[] }, xaxis: { tickDecimals:0, ticks: [';
    if (sizeof($xticks) > 0) {
        foreach($xticks as $k => $v) {
            $plot.= '[' . $v . ',"' . $xlabel[$k] . '"],';
			//echo "[".$k."] ";
			$urls .= "url['".$yaxis[$k]."-".$v."'] = '".($lnk=="" ? "?" : $lnk).get_graph_url($k)."';\n";
        }
        $plot = preg_replace("/\,$/", "", $plot);
    }
    $plot.= ']},';
    $plot.= 'grid: { color: "#AAAAAA", labelMargin:0, background: "transparent", tickColor: "#D2D2D2", hoverable:true, clickable:true}';
    $plot.= ', shadowSize:1 };';
    $plot.= 'var data = [{';
    //$plot.= 'color: "rgb(18,55,95)", label: "Events", ';
	$plot.= 'color: "rgba(140,198,63,0.5)", label: "Events", ';
    $plot.= 'lines: { show: true, fill: true},'; //$plot .= 'label: "Day",';
    $plot.= 'data:[';
	foreach($xaxis as $k => $v) {
        $plot.= '[' . $v . ',' . $yaxis[$k] . '],';
    }
    $plot = preg_replace("/\,$/", "]", $plot);
    $plot.= ' }];';
    $plot.= 'var plotarea = $("#' . $id . '");';
    if ($display == true) {
        $plot.= 'plotarea.css("display", "");';
        $width = '((window.innerWidth || document.body.clientWidth)/1.5)';
    }
    $plot.= 'plotarea.css("height", ' . $height . ');';
    $plot.= 'plotarea.css("width", ' . $width . ');';
    $plot.= '$.plot( plotarea , data, options );';
    //if ($display==true) {
    $plot.= 'var previousPoint = null;
			$("#' . $id . '").bind("plothover", function (event, pos, item) {
				if (item) {
					if (previousPoint != item.datapoint) {
						previousPoint = item.datapoint;
						$("#tooltip").remove();
						var x = item.datapoint[0].toFixed(0), y = formatNmb(item.datapoint[1].toFixed(0));
						showTooltip(item.pageX, item.pageY, y + " " + item.series.label,y+"-"+x);
					}
				}
				else {
					$("#tooltip").remove();
					previousPoint = null;
				}
			});';
    //}
	/*$plot.= '$("#plotareaglobal").bind("plotclick", function (event, pos, item) {
			if (item) {
				var x = item.datapoint[0].toFixed(0), y = formatNmb(item.datapoint[1].toFixed(0));
				var link = y+"-"+x;
				link = link.replace(".","");
                link = link.replace(",","");
				if (typeof(url[link]) != "undefined") document.location.href=url[link]+"&submit=Query DB";
				else alert("URL not found for "+link);
            }
		});';
    $plot.= "});\n";*/
    $plot.= $urls.(($script) ? '</script>' : '');
    return $plot;
}


// return arrays complete for time range
function range_graphic($trdata) 
{
    require_once("classes/Util.inc");
    $tz = Util::get_timezone();
    $timerange = $trdata[2];
    
    switch ($timerange) {
        case "today":
            $desde = strtotime(gmdate("Y-m-d 00:00:00")." GMT");
            $suf = "h";
            $jump = 3600;
            $noprint = 2;
            $interval = "G";
            $key = "G";
            $hasta = gmdate("U")+(3600*$tz); // time to generate dates with timezone correction

            break;

        case "day":
            $desde = gmdate("U") + (3600*$tz) - 24*3600;
            $suf = "";
            $jump = 3600;
            $noprint = 3;
            $interval = "G\h jM";
            $key = "G j";
            $hasta = gmdate("U") + (3600*$tz); 
            break;

        case "day2":
            $desde = gmdate("U") + (3600*$tz) - 48*3600;
            $suf = "";
            $jump = 3600;
            $noprint = 6;
            $interval = "G\h jM";
            $key = "G j";
            $hasta = gmdate("U") + (3600*$tz); 
            break;

        case "week":
            $desde = gmdate("U") + (3600*$tz) - 7*24*3600;
            $suf = "";
            $jump = 86400;
            $noprint = 1;
            $interval = "j M";
            $key = "j F";
            $hasta = gmdate("U") + (3600*$tz); 
            break;

        case "weeks":
            $desde = gmdate("U") + (3600*$tz) - 2*7*24*3600;
            $suf = "";
            $jump = 86400;
            $noprint = 3;
            $interval = "j M";
            $key = "j F";
            $hasta = gmdate("U") + (3600*$tz); 
            break;

        case "month":
            $desde = gmdate("U") + (3600*$tz) - 31*24*3600;
            $suf = "";
            $jump = 86400;
            $noprint = 3;
            $interval = "j M";
            $key = "j F";
            $hasta = gmdate("U") + (3600*$tz); 
            break;

        case "range":
            $desde = $trdata[0];
            $hasta = $trdata[1];
            // time_range calc
            $diff = $hasta - $desde; 
            if ($diff > 2678400) { // more than 1 month
                $suf = "";
                $jump = 0;
                $noprint = 2;
                $interval = "M-Y";
                $key = "F Y";
            } elseif ($diff > 1296000) { // more than 7 days
                $suf = "";
                $jump = 86400;
                $noprint = 3;
                $interval = "j M";
                $key = "j F";
            } elseif ($diff > 604800) { // more than 7 days
                $suf = "";
                $jump = 86400;
                $noprint = 2;
                $interval = "j M";
                $key = "j F";
            } elseif ($diff >= 86400) { // more than 1 day
                $suf = "";
                $jump = 86400;
                $noprint = 1;
                $interval = "j M";
                $key = "j F";
            } elseif ($diff < 86400) {
	            $suf = "h";
	            $jump = 3600;
	            $noprint = 2;
	            $interval = "G";
	            $key = "G";
            } else {
                $suf = "";
                $jump = 3600;
                $noprint = 3;
                $interval = "G\h jM";
                $key = "G j";
            }
            break;

        default:
            $desde = gmdate("U") + (3600*$tz) - 365*24*3600;
            $suf = "";
            $jump = 0;
            $noprint = 2;
            $interval = "M-Y";
            $key = "F Y";
            $hasta = gmdate("U") + (3600*$tz) + 28*24*3600; 
    }
    //
    $x = $y = $ticks = $labels = array();
    $d = $desde;
    $xx = 0;
    while ($d <= $hasta) {
        $now = trim(gmdate($key, $d + (3600*$tz)) . " " . $suf);
        $x["$now"] = $ticks["$now"] = $xx++;
        $y["$now"] = 0; // default value 0
        $labels["$now"] = ($xx % $noprint == 0) ? gmdate($interval, $d + (3600*$tz)) . $suf : "";
        if ($jump == 0) $d+= (date("t", $d) * 86400); // case year
        else $d+= $jump; // next date
        
    }
    //var_dump($x);
    //var_dump($labels);

    return array(
        $x,
        $y,
        $ticks,
        $labels
    );
}

/**
 * This function decides if the Grouped view can use the ac_acid_event table instead of acid_event
 * We can only use it when the timestamp criteria is by entire days, or if it's not present at all
 * 
 * @return boolean
 */
function can_use_accumulated_table()
{
    $use_ac = TRUE;
    
    if (is_array($_SESSION['time']))
    {
        foreach ($_SESSION['time'] as $time_criteria)
        {
            $operator = $time_criteria[1];
            $hour     = $time_criteria[5];
            $minute   = $time_criteria[6];
            $second   = $time_criteria[7];
            
            if (($operator == '>' || $operator == '>=' || $operator == '=' || $operator == '!=')
            && ($hour > 0 || $minute > 0 || $second > 0))
            {
                $use_ac = FALSE;
            }
            
            if (($operator == '<' || $operator == '<=')
                    && ($hour != 23 || $minute != 59 || $second != 59))
            {
                $use_ac = FALSE;
            }
        }
    }
    
    return $use_ac;
}
?>
