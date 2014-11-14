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

require_once 'av_init.php';

Session::logcheck("analysis-menu", "EventsForensics");

// New GeoLoc (as used in alarms)
require_once 'classes/geolocation.inc';
$geoloc = new Geolocation('/usr/share/geoip/GeoLiteCity.dat');

global $colored_alerts, $debug_mode;

// PLOT
?>
<div id="plot_option">
<table class="transparent" cellpadding=0 cellspacing=0 width="100%">
	<tr>
		<td style="padding:5px;padding-left:0px">
			<table class="transparent" cellpadding=0 cellspacing=0>
				<tr>
					<td class='siem_title_gray' style="padding-right:5px">
					    <?php echo _("SHOW TREND GRAPH") ?>
					</td>
					<td>
					    <!-- Plot ON/OFF FlipSwitch -->
					    <div class="onoffswitch">
                            <input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox" id="myonoffswitch" onchange="trendgraph()">
                            <label class="onoffswitch-label" for="myonoffswitch">
                                <div class="onoffswitch-inner"></div>
                                <div class="onoffswitch-switch"></div>
                            </label>
                        </div>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr style="display:none;text-align:center" id="iplot">
		<td>  
		    <div style="width:100%">
		      <div id='loadingTrend' style='margin:0 auto;height:80px;width:100%;position:absolute;z-index:10000;background-color:#eee;text-align:center;opacity:0.85;filter:alpha(opacity=85);-moz-opacity:0.85;-khtml-opacity:0.85'>
		          <div style='margin:0 auto;padding-top:31px;line-height:18px;font-size:12px'>
		              <?php echo _("Loading trend graph, please wait a few seconds") ?> <img src='../pixmaps/loading3.gif'/>
		          </div>
		      </div>
		      <div style="width:100%">
			     <iframe id="processframe" src="" onLoad="$('#loadingTrend').hide()" width="100%" style="height:80px;" frameborder="0" scrolling="no"></iframe>
		      </div>
		    </div>
		</td>
	</tr>
</table>
</div>
<?php

$page = "base_qry_main.php";
$cnt_sql = "SELECT COUNT(acid_event.id) FROM acid_event " . $join_sql . $where_sql . $criteria_sql;
$tmp_page_get = "";

// Timezone
$tz = Util::get_timezone(); 


$et->Mark("Counting Result size");
/* Setup the Query Results Table */
$qro = new QueryResultsOutput("$page" . $qs->SaveStateGET() . $tmp_page_get);
$qro->AddTitle(qroReturnSelectALLCheck());
//$qro->AddTitle("ID");
$qro->AddTitle("SIGNATURE", "sig_a", " ", " ORDER BY plugin_id ASC,plugin_sid", "sig_d", " ", " ORDER BY plugin_id DESC,plugin_sid");
$qro->AddTitle("DATE", "time_a", " ", " ORDER BY timestamp ASC ", "time_d", " ", " ORDER BY timestamp DESC ");
$qro->AddTitle("IP_PORTSRC", "sip_a", " ", " ORDER BY ip_src ASC", "sip_d", " ", " ORDER BY ip_src DESC");
$qro->AddTitle("IP_PORTDST", "dip_a", " ", " ORDER BY ip_dst ASC", "dip_d", " ", " ORDER BY ip_dsat DESC");
//$qro->AddTitle("Asset", "oasset_d_a", " ", " ORDER BY ossim_asset_dst ASC", "oasset_d_d", " ", " ORDER BY ossim_asset_dst DESC");
//$qro->AddTitle("Asset", "oasset_s_a", " ", " ORDER BY ossim_asset_src ASC", "oasset_s_d", " ", " ORDER BY ossim_asset_src DESC", "oasset_d_a", " ", " ORDER BY ossim_asset_dst ASC", "oasset_d_d", " ", " ORDER BY ossim_asset_dst DESC");
$qro->AddTitle("ASSET");
$qro->AddTitle("PRIORITY", "oprio_a", " ", " ORDER BY ossim_priority ASC", "oprio_d", " ", " ORDER BY ossim_priority DESC");
$qro->AddTitle("RELIABILITY", "oreli_a", " ", " ORDER BY ossim_reliability ASC", "oreli_d", " ", " ORDER BY ossim_reliability DESC");
//$qro->AddTitle("Risk", "oriska_a", " ", " ORDER BY ossim_risk_a ASC", "oriska_d", " ", " ORDER BY ossim_risk_a DESC");
$qro->AddTitle("RISK", "oriska_a", " ", " ORDER BY ossim_risk_c ASC", "oriska_d", " ", " ORDER BY ossim_risk_c DESC", "oriskd_a", " ", " ORDER BY ossim_risk_a ASC", "oriskd_d", " ", " ORDER BY ossim_risk_a DESC");
//$qro->AddTitle("L4-proto", "proto_a", " ", " ORDER BY ip_proto ASC", "proto_d", " ", " ORDER BY ip_proto DESC");
$qro->AddTitle("IP_PROTO");
$qro->AddTitle("IP_SRC");
$qro->AddTitle("IP_SRC_FQDN");
$qro->AddTitle("IP_DST");
$qro->AddTitle("IP_DST_FQDN");
$qro->AddTitle("PORT_SRC");
$qro->AddTitle("PORT_DST");
$qro->AddTitle("USERDATA1");
$qro->AddTitle("USERDATA2");
$qro->AddTitle("USERDATA3");
$qro->AddTitle("USERDATA4");
$qro->AddTitle("USERDATA5");
$qro->AddTitle("USERDATA6");
$qro->AddTitle("USERDATA7");
$qro->AddTitle("USERDATA8");
$qro->AddTitle("USERDATA9");
$qro->AddTitle("USERNAME");
$qro->AddTitle("FILENAME");
$qro->AddTitle("PASSWORD");
$qro->AddTitle("PAYLOAD");
$qro->AddTitle("ENTITY");
$qro->AddTitle("PLUGIN_ID");
$qro->AddTitle("PLUGIN_SID");
$qro->AddTitle("PLUGIN_DESC");
$qro->AddTitle("PLUGIN_NAME");
$qro->AddTitle("PLUGIN_SOURCE_TYPE");
$qro->AddTitle("PLUGIN_SID_CATEGORY");
$qro->AddTitle("PLUGIN_SID_SUBCATEGORY");
$qro->AddTitle("CONTEXT");
$qro->AddTitle("SENSOR");
$qro->AddTitle("SRC_USERDOMAIN");
$qro->AddTitle("DST_USERDOMAIN");
$qro->AddTitle("SRC_HOSTNAME");
$qro->AddTitle("DST_HOSTNAME");
$qro->AddTitle("SRC_MAC");
$qro->AddTitle("DST_MAC");
$qro->AddTitle("REP_PRIO_SRC");
$qro->AddTitle("REP_PRIO_DST");
$qro->AddTitle("REP_REL_SRC");
$qro->AddTitle("REP_REL_DST");
$qro->AddTitle("REP_ACT_SRC");
$qro->AddTitle("REP_ACT_DST");
$qro->AddTitle("DEVICE");
/* Apply sort criteria */
if ($qs->isCannedQuery()) $sort_sql = " ORDER BY timestamp DESC ";
else {
	//$sort_sql = $qro->GetSortSQL($qs->GetCurrentSort() , $qs->GetCurrentCannedQuerySort());
    //  3/23/05 BDB   mods to make sort by work for Searches
    $sort_sql = "";
    if (!isset($sort_order)) {
        $sort_order = NULL;
    }
    if ($sort_order == "sip_a") {
        $sort_sql = " ORDER BY ip_src ASC,timestamp DESC";
        $criteria_sql = str_replace("1  AND ( timestamp", "ip_src >= 0 AND ( timestamp", $criteria_sql);
    } elseif ($sort_order == "sip_d") {
        $sort_sql = " ORDER BY ip_src DESC,timestamp DESC";
        $criteria_sql = preg_replace("/1  AND \( timestamp/", "ip_src >= 0 AND ( timestamp", $criteria_sql);
    } elseif ($sort_order == "dip_a") {
        $sort_sql = " ORDER BY ip_dst ASC,timestamp DESC";
        $criteria_sql = preg_replace("/1  AND \( timestamp/", "ip_dst >= 0 AND ( timestamp", $criteria_sql);
    } elseif ($sort_order == "dip_d") {
        $sort_sql = " ORDER BY ip_dst DESC,timestamp DESC";
        $criteria_sql = preg_replace("/1  AND \( timestamp/", "ip_dst >= 0 AND ( timestamp", $criteria_sql);
    } elseif ($sort_order == "sig_a") {
        $sort_sql = " ORDER BY plugin_id ASC,plugin_sid,timestamp DESC";
    } elseif ($sort_order == "sig_d") {
        $sort_sql = " ORDER BY plugin_id DESC,plugin_sid,timestamp DESC";
    } elseif ($sort_order == "time_a") {
        $sort_sql = " ORDER BY timestamp ASC";
    } elseif ($sort_order == "time_d") {
        $sort_sql = " ORDER BY timestamp DESC";
    } elseif ($sort_order == "oasset_d_a") {
        $sort_sql = " ORDER BY ossim_asset_dst ASC,timestamp DESC";
    } elseif ($sort_order == "oasset_d_d") {
        $sort_sql = " ORDER BY ossim_asset_dst DESC,timestamp DESC";
    } elseif ($sort_order == "oprio_a") {
        $sort_sql = " ORDER BY ossim_priority ASC,timestamp DESC";
    } elseif ($sort_order == "oprio_d") {
        $sort_sql = " ORDER BY ossim_priority DESC,timestamp DESC";
    } elseif ($sort_order == "oriska_a") {
        $sort_sql = " ORDER BY GREATEST(ossim_risk_c,ossim_risk_a) ASC,timestamp DESC";
    } elseif ($sort_order == "oriska_d") {
        $sort_sql = " ORDER BY GREATEST(ossim_risk_c,ossim_risk_a) DESC,timestamp DESC";
    } elseif ($sort_order == "oriskd_a") {
        $sort_sql = " ORDER BY ossim_risk_a ASC,timestamp DESC";
    } elseif ($sort_order == "oriskd_d") {
        $sort_sql = " ORDER BY ossim_risk_a DESC,timestamp DESC";
    } elseif ($sort_order == "oreli_a") {
        $sort_sql = " ORDER BY ossim_reliability ASC,timestamp DESC";
    } elseif ($sort_order == "oreli_d") {
        $sort_sql = " ORDER BY ossim_reliability DESC,timestamp DESC";
    } elseif ($sort_order == "proto_a") {
        $sort_sql = " ORDER BY ip_proto ASC,timestamp DESC";
        $criteria_sql = preg_replace("/1  AND \( timestamp/", "ip_proto > 0 AND ( timestamp", $criteria_sql);
    } elseif ($sort_order == "proto_d") {
        $sort_sql = " ORDER BY ip_proto DESC,timestamp DESC";
        $criteria_sql = preg_replace("/1  AND \( timestamp/", "ip_proto > 0 AND ( timestamp", $criteria_sql);
    }
    ExportHTTPVar("prev_sort_order", $sort_order);
}
// Choose the correct INDEX for select (timestamp force index DISABLED at 31/01/2012)
//if (preg_match("/^time/", $sort_order)) $sql.= " FORCE INDEX (timestamp)";
//elseif (preg_match("/^sip/", $sort_order)) $sql.= " FORCE INDEX (ip_src)";
//elseif (preg_match("/^dip/", $sort_order)) $sql.= " FORCE INDEX (ip_dst)";
//elseif (preg_match("/^sig/", $sort_order)) $sql.= " FORCE INDEX (sig_name)";
//elseif (preg_match("/^oasset/", $sort_order)) $sql.= " FORCE INDEX (ossim_asset_dst)";
//elseif (preg_match("/^oprio/", $sort_order)) $sql.= " FORCE INDEX (acid_event_ossim_priority)";
//elseif (preg_match("/^oriska/", $sort_order)) $sql.= " FORCE INDEX (acid_event_ossim_risk_a)";
//elseif (preg_match("/^oriskd/", $sort_order)) $sql.= " FORCE INDEX (acid_event_ossim_risk_c)";
//elseif (preg_match("/^oreli/", $sort_order)) $sql.= " FORCE INDEX (acid_event_ossim_reliability)";
//elseif (preg_match("/^proto/", $sort_order)) $sql.= " FORCE INDEX (ip_proto)";
// Make SQL string with criterias
$sql = $sql . $join_sql . $where_sql . $criteria_sql . $sort_sql;
// if ($debug_mode > 0) {
    // echo "<P>SUBMIT: ".$submit;
    // echo "<P>sort_order: ".$sort_order;
    // echo "<P>SQL (save_sql): ".$sql;
    // echo "<P>SQL (sort_sql): ".$sort_sql;
// }
/* Run the Query again for the actual data (with the LIMIT) */
//echo "$sql<br>";

$_SESSION['siem_current_query'] = $sql;

$result = $qs->ExecuteOutputQuery($sql, $db);
//echo "FR:".$result->baseRecordCount()."<br>"; print_r($criteria_clauses);
$et->Mark("Retrieve Query Data");
$qs->GetCalcRows($criteria_clauses[2], $result->baseRecordCount(), $db);
//$qs->GetCalcFoundRows($cnt_sql, $db);
/* Run the query to determine the number of rows (No LIMIT)*/
//$qs->GetNumResultRows($cnt_sql, $db);

// if ($debug_mode > 0) {
    // $qs->PrintCannedQueryList();
    // $qs->DumpState();
    // echo "$sql<BR>";
// }

/* Clear the old checked positions */
for ($i = 0; $i < $show_rows; $i++) {
    $action_lst[$i] = "";
    $action_chk_lst[$i] = "";
}

// time variables for hostreportmenu
$date_from_aux = $_SESSION["time"][0][4].'-'.$_SESSION["time"][0][2].'-'.$_SESSION["time"][0][3].' 00:00:00';
$date_to_aux = ($_SESSION["time"][1][4] != "") ? $_SESSION["time"][1][4].'-'.($_SESSION["time"][1][2]-1).'-'.$_SESSION["time"][1][3].' 23:59:59' : gmdate("Y",$timetz).'-'.gmdate("m",$timetz).'-'.gmdate("d",$timetz).' 23:59:59';

// time selection for graph x
$tr = ($_SESSION["time_range"] != "") ? $_SESSION["time_range"] : "all";
if ($tr == "range")
{
    $desde = strtotime($_SESSION["time"][0][4]."-".$_SESSION["time"][0][2]."-".$_SESSION["time"][0][3].' '.$_SESSION['time'][0][5].':'.$_SESSION['time'][0][6].':'.$_SESSION['time'][0][7]);
    $hasta = strtotime($_SESSION["time"][1][4]."-".$_SESSION["time"][1][2]."-".$_SESSION["time"][1][3].' '.$_SESSION['time'][1][5].':'.$_SESSION['time'][1][6].':'.$_SESSION['time'][1][7]);
    $diff  = $hasta - $desde;
    if ($diff > 2678400) $tr = "all";
    elseif ($diff > 1296000) $tr = "month";
    elseif ($diff > 604800) $tr = "weeks";
    elseif ($diff >= 86400) $tr = "week"; // More than 1 day
    else $tr = "today";
}
$tzc = Util::get_tzc($tz);

switch ($tr) {
    case "today":
        $interval = "hour(convert_tz(timestamp,'+00:00','$tzc')) as intervalo, 'h' as suf";
        $grpby = " GROUP BY intervalo,suf";
        break;

    case "day2":
    case "day":
        $interval = "hour(convert_tz(timestamp,'+00:00','$tzc')) as intervalo, day(convert_tz(timestamp,'+00:00','$tzc')) as suf";
        $grpby = " GROUP BY intervalo,suf";
        break;

    case "week":
    case "weeks":
        $interval = "day(convert_tz(timestamp,'+00:00','$tzc')) as intervalo, monthname(convert_tz(timestamp,'+00:00','$tzc')) as suf";
        $grpby = " GROUP BY intervalo,suf ORDER BY suf,intervalo";
        break;

    case "month":
        $interval = "day(convert_tz(timestamp,'+00:00','$tzc')) as intervalo, monthname(convert_tz(timestamp,'+00:00','$tzc')) as suf";
        $grpby = " GROUP BY intervalo,suf ORDER BY suf,intervalo";
        break;

    default:
        $interval = "monthname(convert_tz(timestamp,'+00:00','$tzc')) as intervalo, year(convert_tz(timestamp,'+00:00','$tzc')) as suf";
        $grpby = " GROUP BY intervalo,suf ORDER BY suf,intervalo";
}
//$sqlgraph = "SELECT COUNT(acid_event.cid) as num_events, $interval FROM acid_event " . $join_sql . $where_sql . $criteria_sql . $grpby;
$sqlgraph = "SELECT COUNT(acid_event.id) as num_events, $interval FROM acid_event " . $join_sql . $where_sql . $criteria_sql . $grpby;
//echo $sqlgraph."<br>";
/* Print the current view number and # of rows */

$_SESSION['siem_current_query_graph'] = $sqlgraph;

// do we need load extradata?
$need_extradata = 0;
foreach ($_SESSION['views'][$_SESSION['current_cview']]['cols'] as $field) {
	if (preg_match("/^(USERDATA|USERNAME|FILENAME|PASSWORD|PAYLOAD)/i",$field))
		$need_extradata=1;
}
$qs->PrintEstimatedResultCnt(); //base_state_query.inc.php => $sqlgraph
// COLUMNS of Events Table (with ORDER links)
//$htmlPdfReport->set('<table cellpadding=2 cellspacing=0 class="w100">');
if ($qs->num_result_rows > 0)
{
    $qro->PrintHeader('',1);
}
$i = 0;
$report_data = array(); // data to fill report_data 
$sensorips = GetSensorSidsNames($db);
$deviceips = GetDeviceIPs($db);

if (is_array($_SESSION["server"]) && $_SESSION["server"][0]!="")
	$_conn = $dbo->custom_connect($_SESSION["server"][0],$_SESSION["server"][2],$_SESSION["server"][3]);
else
	$_conn = $dbo->connect();
    
while (($myrow = $result->baseFetchRow()) && ($i < $qs->GetDisplayRowCnt())) {
    unset($cell_data);
    unset($cell_more);
    unset($cell_pdfdata);
    unset($cell_align);
    unset($cell_tooltip);
    $ctx = $myrow["ctx"];
    $eid = strtoupper(bin2hex($myrow["id"]));
    $current_sig = BuildSigByPlugin($myrow["plugin_id"], $myrow["plugin_sid"], $db);
    if (preg_match("/FILENAME|USERNAME|PASSWORD|PAYLOAD|USERDATA\d+/",$current_sig)) $need_extradata = 1;
    //
    // Load extra data if neccesary
    //
    if ($need_extradata && !array_key_exists("username",$myrow)) {
		$rs_ed = $qs->ExecuteOutputQueryNoCanned("SELECT * FROM extra_data WHERE event_id=unhex('".$eid."')", $db);
	    while ($row_ed = $rs_ed->baseFetchRow()) {
	    	foreach ($row_ed as $k => $v) $myrow[$k] = $v;
	    }
	    $rs_ed->baseFreeRows();
	}
    //
    // Reputation info
    //
	$rs_rep = $qs->ExecuteOutputQueryNoCanned("SELECT * FROM reputation_data WHERE event_id=unhex('".$eid."')", $db);
    while ($row_rep = $rs_rep->baseFetchRow()) {
    	foreach ($row_rep as $k => $v) $myrow[strtoupper($k)] = Util::htmlentities($v);
    }
    $rs_rep->baseFreeRows();
    //
    // IDM
    //
    $srcud = $dstud = array();
	$rs_id = $qs->ExecuteOutputQueryNoCanned("SELECT * FROM idm_data WHERE event_id=unhex('".$eid."')", $db);
   
    while ( $row_id = $rs_id->baseFetchRow() ) {
        $row_id["username"] = trim($row_id["username"]);
        $row_id["domain"]   = trim($row_id["domain"]);
        
		if ( !empty($row_id["username"]) ) 
		{
        	$idm_u = $row_id["username"];
			$idm_d = ( $row_id["domain"] != '' ) ? "@".$row_id["domain"] : '';
									
			if ( intval($row_id["from_src"]) ) {
				$srcud[] = Util::htmlentities($idm_u.$idm_d);
			}
        	else{
				$dstud[] = Util::htmlentities($idm_u.$idm_d);
			}
        }
    }
    $myrow["src_userdomain"] = implode(", ",$srcud);
    $myrow["dst_userdomain"] = implode(", ",$dstud);
    $rs_id->baseFreeRows();
    $myrow["src_mac"] = formatMAC($myrow["src_mac"]);
    $myrow["dst_mac"] = formatMAC($myrow["dst_mac"]);
    //
    // SID, CID, PLUGIN_*
    $cell_data['ID'] = $eid;
    $cell_align['ID'] = "center";
    
    $sensor_name = GetSensorName($myrow["device_id"], $db);
    if ($sensor_name == 'Unknown' || $sensor_name == 'N/A') 
    {
        $sensor_msg             = _("Directive events are generated in servers, not in sensors");
        $cell_data['SENSOR']    = '<A class="trlnk" alt="'.$sensor_msg.'" title="'.$sensor_msg.'" HREF="#">'._("N/A").'</A>';
        $cell_pdfdata['SENSOR'] = _("N/A");
    } 
    else 
    {
        $sensor_msg             = $sensorips[$myrow["device_id"]];
        
        $s_url = Menu::get_menu_url("base_qry_main.php?new=2&num_result_rows=-1&submit=Query+DB&current_view=-1&sensor=".$myrow["device_id"], 'analysis', 'security_events', 'security_events');
        
        $cell_data['SENSOR']    = '<a class="trlnk" alt="'.Util::htmlentities($sensor_msg).'" title="'.Util::htmlentities($sensor_msg).'" href="'.$s_url.'">'.Util::htmlentities($sensor_name).'</a>';
        $cell_pdfdata['SENSOR'] = Util::htmlentities($sensor_name);
    }

    $cell_align['SENSOR'] = "center";

    $e_url = Menu::get_menu_url("base_qry_main.php?new=2&num_result_rows=-1&submit=Query+DB&current_view=-1&ctx=$ctx", 'analysis', 'security_events', 'security_events');

    $cell_data['ENTITY']  = '<a class="trlnk" href="'.$e_url.'">'.Util::htmlentities((!empty($entities[$ctx])) ? $entities[$ctx] : _("Unknown")).'</a>';
    $cell_align['ENTITY'] = "center";
    $cell_data['ENTITY']  = Util::htmlentities((!empty($entities[$ctx])) ? $entities[$ctx] : _("Unknown"));
        
    $cell_data['PLUGIN_ID'] = $myrow["plugin_id"];
    $cell_align['PLUGIN_ID'] = "center";
    
    $cell_data['PLUGIN_SID'] = $myrow["plugin_sid"];
    $cell_align['PLUGIN_SID'] = "center";
    if (in_array("PLUGIN_NAME",$_SESSION['views'][$_SESSION['current_cview']]['cols']) || in_array("PLUGIN_DESC",$_SESSION['views'][$_SESSION['current_cview']]['cols'])) {
        list($cell_data['PLUGIN_NAME'],$cell_data['PLUGIN_DESC']) = GetPluginNameDesc($myrow["plugin_id"], $db);
        $cell_align['PLUGIN_NAME'] = $cell_align['PLUGIN_DESC'] = "left";
    }
    if (in_array("PLUGIN_SOURCE_TYPE",$_SESSION['views'][$_SESSION['current_cview']]['cols'])) {
        $cell_data['PLUGIN_SOURCE_TYPE'] = GetSourceTypeFromPluginID($myrow["plugin_id"],$db);
        $cell_align['PLUGIN_SOURCE_TYPE'] = "center";
    }
    if (in_array("PLUGIN_SID_CATEGORY",$_SESSION['views'][$_SESSION['current_cview']]['cols']) || in_array("PLUGIN_SID_SUBCATEGORY",$_SESSION['views'][$_SESSION['current_cview']]['cols'])) {
        list($cell_data['PLUGIN_SID_CATEGORY'],$cell_data['PLUGIN_SID_SUBCATEGORY']) = GetCategorySubCategory($myrow["plugin_id"],$myrow["plugin_sid"],$db);
        $cell_align['PLUGIN_SID_CATEGORY'] = $cell_align['PLUGIN_SID_SUBCATEGORY'] = "center";
    }
    
    $cell_data['DEVICE']  = ($deviceips[$myrow["device_id"]] != "") ? $deviceips[$myrow["device_id"]] : "-";
    $cell_align['DEVICE'] = "center";
    
    // Source Host
    $current_src_host = $myrow["src_host"];
    $current_sip32    = $myrow["ip_src"];
    $current_sip      = inet_ntop($current_sip32);
    
    // Destination Host
    $current_dst_host = $myrow["dst_host"];
    $current_dip32    = $myrow["ip_dst"];
    $current_dip      = inet_ntop($current_dip32);
    
    // Port / Protocol
    $current_proto = $myrow["ip_proto"];
    $current_sport = $current_dport = "";
    if ($myrow["layer4_sport"] != 0) $current_sport = ":" . $myrow["layer4_sport"];
    if ($myrow["layer4_dport"] != 0) $current_dport = ":" . $myrow["layer4_dport"];
    // if ($debug_mode > 1) {
        // SQLTraceLog("\n\n");
        // SQLTraceLog(__FILE__ . ":" . __LINE__ . ":\n############## <calls to BuildSigByID> ##################");
    // }
    // SIGNATURE
    $current_sig = TranslateSignature($current_sig,$myrow);
    $current_sig_txt = trim(html_entity_decode(strip_tags($current_sig)));
    //$current_sig_txt = BuildSigByID($myrow[2], $myrow["sid"], $myrow["cid"], $db, 2);
    // if ($debug_mode > 1) {
        // SQLTraceLog(__FILE__ . ":" . __LINE__ . ":\n################ </calls to BuildSigByID> ###############");
        // SQLTraceLog("\n\n");
    // }
    $current_otype = $myrow["ossim_type"];
    $current_oprio = $myrow["ossim_priority"];
    $current_oreli = $myrow["ossim_reliability"];
    $current_oasset_s = $myrow["ossim_asset_src"];
    $current_oasset_d = $myrow["ossim_asset_dst"];
    $current_oriskc = $myrow["ossim_risk_c"];
    $current_oriska = $myrow["ossim_risk_a"];

    //$current_sig = GetTagTriger($current_sig, $db, $myrow[0], $myrow[1]);
    // ********************** EVENTS TABLE **********************
    // <TR>
    //qroPrintEntryHeader((($colored_alerts == 1) ? GetSignaturePriority($myrow[2], $db) : $i) , $colored_alerts);
    
    $rowid = ($qs->GetCurrentView() * $show_rows) + $i;
    $tmp_rowid = "#" . $rowid . "-" . $eid ;
    
    //OnClick event on tr cell
    $onclick = 'onclick="if (!nogb) GB_show_nohide(\''._("Event details").'\',\'/forensics/base_qry_alert.php?submit='.rawurlencode($tmp_rowid).'&minimal_view=1&noback=1\',500,\'85%\');"';
    
    qroPrintEntryHeader($i , $colored_alerts, $onclick, '', 'trcellclk');
    
    // <TD>
    // Signature
    $tmpsig = explode("##", $current_sig);
	if ($tmpsig[1]!="") {
		$antes = $tmpsig[0];
		$despues = $tmpsig[1];
	} else {
		$antes = "";
		$despues = $current_sig;
	}
    // Solera DeepSee API
    $solera = "";
    if ($_SESSION["_solera"]) {
        $solera = "<a class='trlnk' href=\"javascript:;\" onclick=\"solera_deepsee('".$myrow['timestamp']."','".$myrow['timestamp']."','$current_sip','".$myrow["layer4_sport"]."','$current_dip','".$myrow["layer4_dport"]."','".strtolower(IPProto2str($current_proto))."')\"><img src='../pixmaps/solera.png' border='0' align='absmiddle'></a>";
    }
    
    $nidespues = str_replace("<", "[", $despues);
    $nidespues = str_replace(">", "]", $nidespues);
    
    $link_incident = "";
    if ( Session::menu_perms("analysis-menu", "IncidentsOpen") )
    {        
        $link_incident = "<a class='trlnk greybox' title='"._('New Event ticket')."' href=\"../incidents/newincident.php?ref=Event&" . "title=" . urlencode($nidespues) . "&" . "priority=1&" . "src_ips=".$current_sip."&" . "event_start=".$myrow['timestamp']."&" . "event_end=".$myrow['timestamp']."&" . "src_ports=".$myrow["layer4_sport"]."&" . "dst_ips=".$current_dip."&" . "dst_ports=".$myrow["layer4_dport"]."\"><img style='vertical-align:text-top;' src='../pixmaps/script--pencil.png' width='12' alt='"._('New Event ticket')."' border='0'/></a>";
    }
    
    // 1- Checkbox
    $temp  = $solera.' '.$link_incident.' <INPUT class="trlnks" TYPE="checkbox" pid="'.$myrow['plugin_id'].'" psid="'.$myrow['plugin_sid'].'" NAME="action_chk_lst[' . $i . ']" VALUE="' . htmlspecialchars($tmp_rowid) . '">';
    $temp .= '    <INPUT TYPE="hidden" NAME="action_lst['.$i.']" VALUE="'.Util::htmlentities($tmp_rowid).'">'; 
    qroPrintEntry($temp,"","","","style='text-align:center;' nowrap");
    
    // 2- Signature    
    $temp = "$antes <A class='trlnka' id='".$myrow["plugin_id"].";".$myrow["plugin_sid"]."' HREF='base_qry_alert.php?noheader=true&submit=" . rawurlencode($tmp_rowid) . "&amp;sort_order=";
    $temp.= ($qs->isCannedQuery()) ? $qs->getCurrentCannedQuerySort() : $qs->getCurrentSort();
    $temp.= "'>" . $despues . "</a>"; // $tmpsig[1]
    $cell_data['SIGNATURE'] = $temp;
    $cell_pdfdata['SIGNATURE'] = html_entity_decode($despues);
	$cell_align['SIGNATURE'] = "left";
    if ($_SESSION['current_cview']=="default") $cell_more['SIGNATURE'] = "width='25%'"; // only in default view
    $temp = "";
    // 4- Timestamp
    //qroPrintEntry($myrow["timestamp"], "center");

    $tzone = $myrow['tzone'];
    $event_date = $myrow['timestamp'];
    $tzdate = $event_date;
    $event_date_uut = get_utc_unixtime($db,$event_date);
    // Event date timezone
	if ($tzone!=0) $event_date = gmdate("Y-m-d H:i:s",$event_date_uut+(3600*$tzone));    
    // Apply user timezone
	if ($tz!=0) $tzdate = gmdate("Y-m-d H:i:s",$event_date_uut+(3600*$tz));
		    
	$cell_data['DATE'] = $tzdate;
	$cell_tooltip['DATE'] = ($event_date==$myrow['timestamp'] || $event_date==$tzdate) ? "" : _("Event date").": ".htmlspecialchars("<b>".$event_date."</b><br>"._("Timezone").": <b>".Util::timezone($tzone)."</b>");
	$cell_pdfdata['DATE'] = str_replace(" ","<br>",$tzdate);
	$cell_align['DATE'] = "center";
	$cell_more['DATE'] = "nowrap";

    // 5- Source IP Address
    if ($current_sip32 != "") 
    {	
	    	// Src Data
        $src_output   = Asset_host::get_extended_name($_conn, $geoloc, $current_sip, $ctx, $current_src_host, $myrow["src_net"]);
	    $src_name     = $src_output['name'];
	    $homelan_src  = $src_output['is_internal'];
	    $src_img      = $src_output['html_icon'];
	    
	    $rep_src_icon = getrepimg($myrow["REP_PRIO_SRC"],$myrow["REP_REL_SRC"],$myrow["REP_ACT_SRC"],$current_sip);
	    
	    	// Div for right click menu
	    // Warning: ctx attribute could be src_ctx
        $div = '<div id="'.$current_sip.';'.$src_name.';'.$current_src_host.'" date_from="'.$date_from_aux.'" date_to="'.$date_to_aux.'" id2="'.$current_sip.';'.$current_dip.'" ctx="'.$ctx.'" class="HostReportMenu">';
		$bdiv = '</div>';
        
		// IDM: User, Domain, and more data
        if ( $idm_enabled && ($myrow["src_userdomain"] != "") ) 
		{
	        	$idmtxt = _("IDM Username@domain").": <b>".$myrow["src_userdomain"]."</b><br/>".
						  _("IDM Hostname").": <b>".$myrow["src_hostname"]."</b><br/>".
						  _("IDM MAC").": <b>".$myrow["src_mac"]."</b><br/>".
						  _("IDM IP").": <b>$current_sip</b>";
	        	
	        	$sip_aux = explode(", ",$myrow["src_userdomain"]);
	        	$sip_lnk = "";
	        	foreach ($sip_aux as $userdomain) 
	        	{
	        	    list ($myrow["src_username"],$myrow["src_domain"]) = explode("@",$userdomain);
				
	        	    $sip_where = '&idm_username%5B1%5D=both&idm_username%5B0%5D='.urlencode($myrow["src_username"]).'&idm_domain%5B1%5D=both&idm_domain%5B0%5D='.urlencode($myrow["src_domain"]);
				    
				    
				    $f_url = Menu::get_menu_url('base_qry_main.php?new=2&num_result_rows=-1&submit=Query+DB&current_view=-1'.$sip_where, 'analysis', 'security_events', 'security_events');
				    
				    
				    $sip_lnk  .= ($sip_lnk!="" ? ", " : "").'<a class="trlnk idminfo" txt="'.Util::htmlentities($idmtxt,ENT_QUOTES).'" style="color:navy;'.(($homelan_src) ? "font-weight:bold;" : "").'text-decoration:none" href="'.$f_url.'">'.$userdomain.'</a>';
	        	}
	        	
			$sip_lnk = preg_replace('/\,$/','<FONT SIZE="-1">' . $current_sport . '</FONT>',$sip_lnk);
        }
        // Normal IP Address / Hostname
		else
		{
			$style_aux = ($homelan_src) ? 'style="font-weight:bold"' : "";
			$bold_aux1 = ($homelan_src) ? '<b>' : "";
			$bold_aux2 = ($homelan_src) ? '<b>' : "";
			if ($src_name != $current_sip)
			{
			     
			    $f_url = Menu::get_menu_url('base_qry_main.php?new=2&num_result_rows=-1&submit=Query+DB&current_view=-1&ip_addr_cnt=1&sort_order=time_d&search_str='.urlencode($src_name).'&submit=Src+Host', 'analysis', 'security_events', 'security_events'); 
			
				$sip_lnk = '<a class="trlnk" '.$style_aux.' alt="'.$current_sip.'" title="'.$current_sip.'" href="'.$f_url.'">'.$src_name.'</a>' . $bold_aux1 . $current_sport . $bold_aux2;
			}
		    else
		    {
				
			    $f_url = Menu::get_menu_url('base_qry_main.php?new=2&num_result_rows=-1&submit=Query+DB&current_view=-1&ip_addr_cnt=1&sort_order=time_d&ip_addr%5B0%5D%5B0%5D=+&ip_addr%5B0%5D%5B1%5D=ip_src&ip_addr%5B0%5D%5B2%5D=%3D&ip_addr%5B0%5D%5B3%5D='.$current_sip.'&ip_addr%5B0%5D%5B8%5D=+', 'analysis', 'security_events', 'security_events'); 

				
				$sip_lnk = '<a class="trlnk" '.$style_aux.' alt="'.$current_sip.'" title="'.$current_sip.'" href="'.$f_url.'">'.$src_name.'</a>' . $bold_aux1 . $current_sport . $bold_aux2;
		    }
        }
		
		$cell_data['IP_PORTSRC']    = $div . $src_img . " " . $sip_lnk . " " . $rep_src_icon . $bdiv;
		$cell_pdfdata['IP_PORTSRC'] = $src_name.$current_sport;
		$cell_more['IP_PORTSRC']    = (preg_match("/\d+\.\d+\.\d+\.\d+/",$cell_data['IP_PORTSRC'])) ? "nowrap" : "";
		$cell_data['IP_SRC']        = $src_img . " " . (($homelan_src) ? "<b>$current_sip</b>" : $current_sip) . " " . $rep_src_icon;
		$cell_pdfdata['IP_SRC']     = $current_sip;
		$cell_data['PORT_SRC']      = str_replace(":","",$current_sport);
    }
    else 
	{
        /* if no IP address was found check if this is a spp_portscan message
        * and try to extract a source IP
        * - contrib: Michael Bell <michael.bell@web.de>
        */
        if (stristr($current_sig_txt, "portscan"))
        {
            $line = split(" ", $current_sig_txt);
            foreach($line as $ps_element)
            {
                if (ereg("[0-9]*\.[0-9]*\.[0-9]*\.[0-9]", $ps_element))
                {
                    $ps_element = ereg_replace(":", "", $ps_element);
                    $div = '<div id="'.$ps_element.';'.$ps_element.'" class="HostReportMenu">';
					$bdiv = "</div>";
					
					$f_url = Menu::get_menu_url('base_qry_main.php?new=2&num_result_rows=-1&submit=Query+DB&current_view=-1&ip_addr_cnt=1&sort_order=time_d&ip_addr%5B0%5D%5B0%5D=+&ip_addr%5B0%5D%5B1%5D=ip_src&ip_addr%5B0%5D%5B2%5D=%3D&ip_addr%5B0%5D%5B3%5D=$ps_element&ip_addr%5B0%5D%5B8%5D=+', 'analysis', 'security_events', 'security_events'); 
					
					
					$cell_data['IP_PORTSRC'] = "$div<a class='trlnk' href=\"$f_url\">" . $ps_element . "</a>$bdiv";
                }
            }
        }
        else
        {
			$cell_data['IP_PORTSRC'] = gettext("unknown");
		}
		
		$cell_data['IP_SRC'] = gettext("unknown");
		$cell_data['PORT_SRC'] = gettext("unknown");
    }
    
    $cell_align['IP_PORTSRC'] = "left";
    $cell_align['IP_SRC'] = "left";
    $cell_align['PORT_SRC'] = "center";
	if (in_array("IP_SRC_FQDN",$_SESSION['views'][$_SESSION['current_cview']]['cols']))
	{
		$cell_data['IP_SRC_FQDN'] = baseGetHostByAddr($current_sip, $ctx, $db);
		$cell_align['IP_SRC_FQDN'] = "center";		
	}
        
    // 6- Destination IP Address
    if ($current_dip32 != "")
    {
        
    		// Dst Data
        $dst_output  = Asset_host::get_extended_name($_conn, $geoloc, $current_dip, $ctx, $current_dst_host, $myrow["dst_net"]);
        $dst_name    = $dst_output['name'];
        $homelan_dst = $dst_output['is_internal'];
        $dst_img     = $dst_output['html_icon'];
        
        $rep_dst_icon = getrepimg($myrow["REP_PRIO_DST"],$myrow["REP_REL_DST"],$myrow["REP_ACT_DST"],$current_dip);
        
	    	// Div for right click menu
        // Warning: ctx could be ctx_dst
        $div = '<div id="'.$current_dip.';'.$dst_name.';'.$current_dst_host.'" date_from="'.$date_from_aux.'" date_to="'.$date_to_aux.'" id2="'.$current_sip.';'.$current_dip.'" ctx="'.$ctx.'" class="HostReportMenu">';
		$bdiv = '</div>';
        
		// IDM: User, Domain, and more data
        if ( $idm_enabled && ($myrow["dst_userdomain"] != "") ) 
		{
	        	$idmtxt = _("IDM Username@domain").": <b>".$myrow["dst_userdomain"]."</b><br>".
						  _("IDM Hostname").": <b>".$myrow["dst_hostname"]."</b><br>".
						  _("IDM MAC").": <b>".$myrow["dst_mac"]."</b><br>".
						  _("IDM IP").": <b>$current_dip</b>";
	        	
	        	$dip_aux = explode(", ",$myrow["dst_userdomain"]);
	        	$dip_lnk = "";
	        	foreach ($dip_aux as $userdomain) 
	        	{
	        	    list ($myrow["dst_username"],$myrow["dst_domain"]) = explode("@",$userdomain);
	        	    
	        	    $dip_where = '&idm_username%5B1%5D=both&idm_username%5B0%5D='.urlencode($myrow["dst_username"]).'&idm_domain%5B1%5D=both&idm_domain%5B0%5D='.urlencode($myrow["dst_domain"]);
	        	    
	        	    
	        	    $f_url = Menu::get_menu_url('base_qry_main.php?new=2&num_result_rows=-1&submit=Query+DB&current_view=-1'.$dip_where, 'analysis', 'security_events', 'security_events'); 
	        	    
	        	    
	        	    $dip_lnk  .= ($dip_lnk!="" ? ", " : "").'<a class="trlnk idminfo" txt="'.Util::htmlentities($idmtxt,ENT_QUOTES).'" style="color:navy;'.(($homelan_dst) ? "font-weight:bold;" : "").'text-decoration:none" href="'.$f_url.'">'.$userdomain.'</a>';
	        	}
	        	
	        	$dip_lnk = preg_replace('/\,$/','<FONT SIZE="-1">' . $current_dport . '</FONT>',$dip_lnk);
        } 
		else
		{
			$style_aux = ($homelan_dst) ? 'style="font-weight:bold"' : "";
			$bold_aux1 = ($homelan_dst) ? '<b>' : "";
			$bold_aux2 = ($homelan_dst) ? '<b>' : "";
			if ($dst_name != $current_dip)
			{
				$f_url = Menu::get_menu_url('base_qry_main.php?new=2&num_result_rows=-1&submit=Query+DB&current_view=-1&ip_addr_cnt=1&sort_order=time_d&search_str='.urlencode($dst_name).'&submit=Dst+Host', 'analysis', 'security_events', 'security_events'); 
				
				$dip_lnk = '<a class="trlnk" '.$style_aux.' alt="'.$current_dip.'" title="'.$current_dip.'" href="'.$f_url.'">'.$dst_name.'</a>' . $bold_aux1 . $current_dport . $bold_aux2;
			}
		    else 
		    {		
				$f_url = Menu::get_menu_url('base_qry_main.php?new=2&num_result_rows=-1&submit=Query+DB&current_view=-1&ip_addr_cnt=1&sort_order=time_d&ip_addr%5B0%5D%5B0%5D=+&ip_addr%5B0%5D%5B1%5D=ip_dst&ip_addr%5B0%5D%5B2%5D=%3D&ip_addr%5B0%5D%5B3%5D='.$current_dip.'&ip_addr%5B0%5D%5B8%5D=+', 'analysis', 'security_events', 'security_events'); 
				
				$dip_lnk = '<a class="trlnk" '.$style_aux.' alt="'.$current_dip.'" title="'.$current_dip.'" href="'.$f_url.'">'.$dst_name.'</a>' . $bold_aux1 . $current_dport . $bold_aux2;
		    }
        }  		
		
		$cell_data['IP_PORTDST']    = $div . $dst_img . " " . $dip_lnk . " " . $rep_dst_icon . $bdiv;
		$cell_pdfdata['IP_PORTDST'] = $dst_name.$current_dport;
		$cell_more['IP_PORTDST']    = (preg_match("/\d+\.\d+\.\d+\.\d+/",$cell_data['IP_PORTSRC'])) ? "nowrap" : "";
		$cell_data['IP_DST']        = $dst_img . " " . (($homelan_dst) ? "<b>$current_dip</b>" : $current_dip) . " " . $rep_dst_icon;
		$cell_pdfdata['IP_DST']     = $current_dip;
		$cell_data['PORT_DST']      = str_replace(":","",$current_dport);
    }
    else
    {
		$cell_data['IP_PORTDST'] = gettext("unknown");
		$cell_data['IP_DST'] = gettext("unknown");
		$cell_data['PORT_DST'] = gettext("unknown");
	}
    $cell_align['IP_PORTDST'] = "left";
    $cell_align['IP_DST'] = "left";
    $cell_align['PORT_DST'] = "center";
	if (in_array("IP_DST_FQDN",$_SESSION['views'][$_SESSION['current_cview']]['cols']))
	{
		$cell_data['IP_DST_FQDN'] = baseGetHostByAddr($current_dip, $ctx, $db);
		$cell_align['IP_DST_FQDN'] = "center";
	}
    
    // 7- Asset
    //qroPrintEntry("<img src=\"bar2.php?value=" . $current_oasset_s . "&value2=" . $current_oasset_d . "&max=5\" border='0' align='absmiddle' title='$current_oasset_s -> $current_oasset_d'>&nbsp;");
	$cell_data['ASSET'] = "<img src=\"bar2.php?value=" . $current_oasset_s . "&value2=" . $current_oasset_d . "&max=5\" border='0' align='absmiddle' title='$current_oasset_s -> $current_oasset_d'>";
	$cell_pdfdata['ASSET'] = "<img src='".$current_url."/forensics/bar2.php?value=" . $current_oasset_s . "&value2=" . $current_oasset_d . "&max=5' border='0' align='absmiddle' style='width:10mm'>";
    $cell_align['ASSET'] = "center";

    $current_orisk = ($current_dip != "255.255.255.255") ? $current_oriska : $current_oriskc;
    
   /*if ($current_dip != "255.255.255.255") {
        qroPrintEntry("<img src=\"bar.php?value=" . $current_oasset_d . "&max=5\" border='0' align='absmiddle'>&nbsp;");
        $current_orisk = $current_oriska;
    } else {
        qroPrintEntry("<img src=\"bar.php?value=" . $current_oasset_s . "&max=5\" border='0' align='absmiddle'>&nbsp;");
        $current_orisk = $current_oriskc;
    }*/

    // 8- Priority
    //qroPrintEntry("<img src=\"bar2.php?value=" . $current_oprio . "&max=5\" border='0' align='absmiddle' title='$current_oprio'>&nbsp;");
	$cell_data['PRIORITY'] = '<a href="javascript:;" onclick="nogb=true;GB_show_nohide(\''._("Modify Rel/Prio").'\',\'/forensics/modify_relprio.php?id='.$myrow["plugin_id"].'&sid='.$myrow["plugin_sid"].'\',300,400)">'."<img src=\"bar2.php?value=" . $current_oprio . "&max=5\" border='0' align='absmiddle' title='$current_oprio'></a>";
	$cell_pdfdata['PRIORITY'] = "<img src='".$current_url."/forensics/bar2.php?value=" . $current_oprio . "&max=5' border='0' align='absmiddle' style='width:10mm'>";
    $cell_align['PRIORITY'] = "center";
    //if ($current_oprio != "")
    //	qroPrintEntry($current_oprio);
    //else
    //	qroPrintEntry("--");

    // 10- Rel
    //qroPrintEntry("<img src=\"bar2.php?value=" . $current_oreli . "&max=9\" border='0' align='absmiddle' title='$current_oreli'>&nbsp;");
	$cell_data['RELIABILITY'] = '<a href="javascript:;" onclick="nogb=true;GB_show_nohide(\''._("Modify Rel/Prio").'\',\'/forensics/modify_relprio.php?id='.$myrow["plugin_id"].'&sid='.$myrow["plugin_sid"].'\',300,400)">'."<img src=\"bar2.php?value=" . $current_oreli . "&max=9\" border='0' align='absmiddle' title='$current_oreli'></a>";
	$cell_pdfdata['RELIABILITY'] = "<img src='".$current_url."/forensics/bar2.php?value=" . $current_oreli . "&max=9' border='0' align='absmiddle' style='width:10mm'>";
    $cell_align['RELIABILITY'] = "center";
    //if ($current_oreli != "")
    //	qroPrintEntry($current_oreli);
    //else
    //	qroPrintEntry("--");

    // 9- Risk
    //qroPrintEntry("<img src=\"bar2.php?value=" . $current_oriskc . "&value2=" . $current_oriska . "&max=9&range=1\" border='0' align='absmiddle' title='$current_oriskc -> $current_oriska'>&nbsp;");
	
	$current_maxrisk = ($current_oriska > $current_oriskc) ? $current_oriska : $current_oriskc;
	$risk_detail = str_replace("10mm","14mm","<div style='text-align:right'>Asset S->D: ".$cell_pdfdata['ASSET']."<br>Priority: ".$cell_pdfdata['PRIORITY']."<br>Reliability: ".$cell_pdfdata['RELIABILITY']."<br>Risk A->C: <img src='".$current_url."/forensics/bar2.php?value=" . $current_oriskc . "&value2=" . $current_oriska . "&max=9&range=1' border='0' align='absmiddle' style='width:10mm'></div>");
	$cell_data['RISK'] = "<a href='javascript:;' class='riskinfo' style='text-decoration:none' txt='".Util::htmlentities($risk_detail,ENT_QUOTES)."'><img src=\"bar2.php?value=" . $current_maxrisk . "&max=9&range=1\" border='0' align='absmiddle'></a>";
	$cell_pdfdata['RISK'] = "<img src='".$current_url."/forensics/bar2.php?value=" . $current_maxrisk . "&max=9&range=1' border='0' align='absmiddle' style='width:10mm'>";
	
	//$cell_data['RISK'] = "<img src=\"bar2.php?value=" . $current_oriskc . "&value2=" . $current_oriska . "&max=9&range=1\" border='0' align='absmiddle' title='$current_oriskc -> $current_oriska'>";
	//$cell_pdfdata['RISK'] = "<img src='".$current_url."/forensics/bar2.php?value=" . $current_oriskc . "&value2=" . $current_oriska . "&max=9&range=1' border='0' align='absmiddle' style='width:10mm'>";
		
    $cell_align['RISK'] = "center";

    
    /* 10 - Context
    switch(intval($myrow["context"])) {
    	case 3:
    		$context = '<a href="javascript:;" title="'._("Event prioritized, as target is vulnerable to the attack").'"><img src="images/marker_red.png" border="0"></a>';
    		break;
    	
    	case 2:
    		$context = '<a href="javascript:;" title="'._("Event deprioritized, as target inventory didn't match the list of affected systems").'"><img src="images/marker_green.png" border="0"></a>';
    		break;
    	
    	case 1:
    		$context = '<a href="javascript:;" title="'._("Event prioritized, as target inventory matched the list of affected systems").'"><img src="images/marker_yellow.png" border="0"></a>';
    		break;
    	
    	case 0:
    		$context = '<a href="javascript:;" title="'._("No action related to the context analysis").'"><img src="images/marker_grey.png" border="0"></a>';
    		break;
    }
	$cell_data['CONTEXT'] = $context;
	$cell_align['CONTEXT'] = "center";
    $cell_more['CONTEXT'] = "nowrap";*/
    
    // 11- Protocol
    //qroPrintEntry('<FONT>' . IPProto2str($current_proto) . '</FONT>');
	$cell_data['IP_PROTO'] = IPProto2str($current_proto);
	$cell_align['IP_PROTO'] = "center";
	
	// X- ExtraData
	$cell_data['USERNAME'] = Util::htmlentities(Util::wordwrap($myrow['username'],25," ",true));
	$cell_data['PASSWORD'] = Util::htmlentities(Util::wordwrap($myrow['password'],25," ",true));
	$cell_data['FILENAME'] = Util::htmlentities(Util::wordwrap($myrow['filename'],25," ",true));
	$cell_data['PAYLOAD'] = Util::htmlentities(Util::wordwrap($myrow['data_payload'],25," ",true));
	for ($u = 1; $u < 10; $u++)
		$cell_data['USERDATA'.$u] = ($i<9) ? Util::htmlentities(Util::wordwrap($myrow['userdata'.$u],25," ",true)) : Util::htmlentities($myrow['userdata'.$u]);

	// IDM-Reputation Data
	$cell_data['SRC_USERDOMAIN'] = Util::htmlentities($myrow['src_userdomain']);
	$cell_align['SRC_USERDOMAIN'] = "center";
	$cell_data['DST_USERDOMAIN'] = Util::htmlentities($myrow['dst_userdomain']);
	$cell_align['DST_USERDOMAIN'] = "center";
	$cell_data['SRC_HOSTNAME'] = Util::htmlentities($myrow['src_hostname']);
	$cell_align['SRC_HOSTNAME'] = "center";
	$cell_data['DST_HOSTNAME'] = Util::htmlentities($myrow['dst_hostname']);
	$cell_align['DST_HOSTNAME'] = "center";
	$cell_data['SRC_MAC'] = Util::htmlentities($myrow['src_mac']);
	$cell_align['SRC_MAC'] = "center";
	$cell_data['DST_MAC'] = Util::htmlentities($myrow['dst_mac']);
	$cell_align['DST_MAC'] = "center";
	$cell_data['REP_PRIO_SRC'] = Util::htmlentities($myrow['REP_PRIO_SRC']);
	$cell_align['REP_PRIO_SRC'] = "center";
	$cell_data['REP_PRIO_DST'] = Util::htmlentities($myrow['REP_PRIO_DST']);
	$cell_align['REP_PRIO_DST'] = "center";
	$cell_data['REP_REL_SRC'] = Util::htmlentities($myrow['REP_REL_SRC']);
	$cell_align['REP_REL_SRC'] = "center";
	$cell_data['REP_REL_DST'] = Util::htmlentities($myrow['REP_REL_DST']);
	$cell_align['REP_REL_DST'] = "center";
	$cell_data['REP_ACT_SRC'] = str_replace("&amp;","&",Util::htmlentities($myrow['REP_ACT_SRC']));
	$cell_align['REP_ACT_SRC'] = "center";
	$cell_data['REP_ACT_DST'] = str_replace("&amp;","&",Util::htmlentities($myrow['REP_ACT_DST']));
	$cell_align['REP_ACT_DST'] = "center";

    // Print Columns
    foreach ($_SESSION['views'][$_SESSION['current_cview']]['cols'] as $colname) {
        if ($cell_data[$colname] == "") $cell_data[$colname] = "<font style='color:gray'><i>Empty</i></font>";
        if ($cell_tooltip[$colname]!="") 
        	qroPrintEntryTooltip($cell_data[$colname], $cell_align[$colname],"",$cell_more[$colname],$cell_tooltip[$colname]);
        else
        	qroPrintEntry($cell_data[$colname], $cell_align[$colname],"",$cell_more[$colname]);        	
        //$w = ($current_cols_widths[$colname]!="") ? "style='width:".$current_cols_widths[$colname]."'" : "";
        //$htmlPdfReport->set("<td class='siem' $w align='".$cell_align[$colname]."'>".($cell_pdfdata[$colname]!="" ? $cell_pdfdata[$colname] : $cell_data[$colname])."</td>\n");
    }
    //$htmlPdfReport->set("</tr>\n");
    
    // Empty cell for custom views th header
    qroPrintEntry("");

    qroPrintEntryFooter();
    $i++;
    /*if ( ($current_proto == 6) || ($current_proto == 17) )
    {
    $result4->baseFreeRows();
    $myrow4[0] = $myrow4[1] = "";
    }*/
    
    /* report_data */
    foreach ($cell_data as $key => $cdata) {
    	$cell_csvdata[$key] = ($cell_pdfdata[$key] != "") ? $cell_pdfdata[$key] : str_replace("<font style='color:gray'><i>Empty</i></font>", "Empty", $cell_data[$key]);
    }
    $report_cell_data = json_encode($cell_csvdata);
    $report_data[] = array (
        trim(mb_convert_encoding($despues,"UTF-8","HTML-ENTITIES")),
        $tzdate,
        $src_name.$current_sport, "",
        $dst_name.$current_dport, "",
        $current_url."/forensics/bar2.php?value=" . $current_oasset_s . "&value2=" . $current_oasset_d . "&max=5",
        $current_url."/forensics/bar2.php?value=" . $current_oprio . "&max=5",
        $current_url."/forensics/bar2.php?value=" . $current_oreli . "&max=9",
        $current_url."/forensics/bar2.php?value=" . $current_maxrisk . "&max=9&range=1",
        (Session::is_pro() ? $cell_data['ENTITY'] : ($sensor_name!="" ? $sensor_name : _("N/A"))),0,0,0,
        $report_cell_data
    );
}
$result->baseFreeRows();
$dbo->close($_conn);
$qro->PrintFooter();
$qs->PrintBrowseButtons();
$qs->PrintAlertActionButtons();
$qs->SaveReportData($report_data,$events_report_type);
$et->PrintForensicsTiming();
$db->baseClose();
//echo memory_get_peak_usage();
?>








