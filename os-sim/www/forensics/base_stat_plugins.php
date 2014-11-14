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


include_once ("base_conf.php");
require ("vars_session.php");
require ("$BASE_path/includes/base_constants.inc.php");
require ("$BASE_path/includes/base_include.inc.php");
include_once ("$BASE_path/base_db_common.php");
include_once ("$BASE_path/base_qry_common.php");
include_once ("$BASE_path/base_stat_common.php");


$_SESSION["siem_default_group"] = "base_stat_plugins.php?sort_order=occur_d";

($debug_time_mode >= 1) ? $et = new EventTiming($debug_time_mode) : '';
$cs = new CriteriaState("base_stat_plugins.php");
$submit = ImportHTTPVar("submit", VAR_ALPHA | VAR_SPACE, array(
    gettext("Delete Selected"),
    gettext("Delete ALL on Screen"),
    _ENTIREQUERY
));
$cs->ReadState();
// Check role out and redirect if needed -- Kevin
$roleneeded = 10000;
#$BUser = new BaseUser();
#if (($BUser->hasRole($roleneeded) == 0) && ($Use_Auth_System == 1)) base_header("Location: " . $BASE_urlpath . "/index.php");
$qs = new QueryState();
$qs->AddCannedQuery("most_frequent", $freq_num_alerts, gettext("Most Frequent Events"), "occur_d");
$qs->AddCannedQuery("last_alerts", $last_num_ualerts, gettext("Last Events"), "last_d");
$qs->MoveView($submit); /* increment the view if necessary */
$page_title = gettext("Event Listing");

/* Connect to the Alert database */
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);

if ($event_cache_auto_update == 1) UpdateAlertCache($db);
$criteria_clauses = ProcessCriteria();

// Include base_header.php
if ($qs->isCannedQuery()) PrintBASESubHeader($page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $cs->GetBackLink() , 1);
else PrintBASESubHeader($page_title, $page_title, $cs->GetBackLink() , 1);

// Use accumulate tables only when timestamp criteria is not hour sensitive
$use_ac = can_use_accumulated_table();

if ($use_ac) { // use ac_acid_event
	$from = " FROM ac_acid_event as acid_event, sensor " . $criteria_clauses[0];
	$fromcnt = " FROM ac_acid_event as acid_event " . $criteria_clauses[0];
	$where = " WHERE " . $criteria_clauses[4];
	$where2 = " WHERE " . $criteria_clauses[5];
	$counter = "sum(acid_event.cnt) as events";
} else {
	$from = " FROM acid_event, sensor " . $criteria_clauses[0];
	$fromcnt = " FROM acid_event " . $criteria_clauses[0];
	$where = $where2 = " WHERE ". $criteria_clauses[1];
	$counter = "count(acid_event.ctx) as events";
}
if (preg_match("/^(.*)AND\s+\(\s+timestamp\s+[^']+'([^']+)'\s+\)\s+AND\s+\(\s+timestamp\s+[^']+'([^']+)'\s+\)(.*)$/", $where, $matches)) {
    if ($matches[2] != $matches[3]) {
        $where = $matches[1] . " AND timestamp BETWEEN('" . $matches[2] . "') AND ('" . $matches[3] . "') " . $matches[4];
    } else {
        $where = $matches[1] . " AND timestamp >= '" . $matches[2] . "' " . $matches[4];
    }
}
// Timezone
$tz = Util::get_timezone();

//$qs->AddValidAction("ag_by_id");
//$qs->AddValidAction("ag_by_name");
//$qs->AddValidAction("add_new_ag");
//$qs->AddValidAction("del_alert");
//$qs->AddValidAction("email_alert");
//$qs->AddValidAction("email_alert2");
//$qs->AddValidAction("csv_alert");
//$qs->AddValidAction("archive_alert");
//$qs->AddValidAction("archive_alert2");
//$qs->AddValidActionOp(gettext("Delete Selected"));
//$qs->AddValidActionOp(gettext("Delete ALL on Screen"));
$qs->SetActionSQL($from . $where);
($debug_time_mode >= 1) ? $et->Mark("Initialization") : '';
$qs->RunAction($submit, PAGE_STAT_ALERTS, $db);
($debug_time_mode >= 1) ? $et->Mark("Alert Action") : '';
/* Get total number of events */
/* create SQL to get Unique Alerts */
$cnt_sql = "SELECT count(DISTINCT acid_event.plugin_id) " . $fromcnt . $where;
/* Run the query to determine the number of rows (No LIMIT)*/
$qs->GetNumResultRows($cnt_sql, $db);
($debug_time_mode >= 1) ? $et->Mark("Counting Result size") : '';
/* Setup the Query Results Table */
$qro = new QueryResultsOutput("base_stat_plugins.php?caller=" . $caller);
//$qro->AddTitle(" ");
$qro->AddTitle(_("Data Source"));
$events_title = (!$use_ac) ? _("Events"). "&nbsp;# <span class='idminfo' txt='".Util::timezone($tz)."'>(*)</span>" : _("Events")."&nbsp;# <span class='idminfo' txt='"._("Time UTC")."'>(*)</span>";
$qro->AddTitle($events_title , "occur_a", " ", " ORDER BY events ASC", "occur_d", ", ", " ORDER BY events DESC");
//$qro->AddTitle(gettext("Sensor") . "&nbsp;#", "sid_a", " ", " ORDER BY sensors ASC, events DESC", "sid_d", " ", " ORDER BY sensors DESC, events DESC");
$qro->AddTitle((Session::show_entities()) ? gettext("Context") : gettext("Sensor"));
$qro->AddTitle(gettext("Product Type"));
$qro->AddTitle(gettext("Last Event"));
$qro->AddTitle(gettext("Date")." ".Util::timezone($tz));
$sort_sql = $qro->GetSortSQL($qs->GetCurrentSort() , $qs->GetCurrentCannedQuerySort());
/* mstone 20050309 add sig_name to GROUP BY & query so it can be used in postgres ORDER BY */
/* mstone 20050405 add sid & ip counts */

if (Session::show_entities()) {
    $sql = "select acid_event.plugin_id,$counter,hex(acid_event.ctx) as ctx,product_type.name as source_type,plugin.product_type,plugin.name  " . $fromcnt  . ",alienvault.plugin LEFT JOIN alienvault.product_type ON product_type.id=plugin.product_type " . $where . " AND plugin.id=acid_event.plugin_id GROUP BY acid_event.plugin_id,acid_event.ctx " . $sort_sql[1];
    $sql2 = "select acid_event.plugin_id,$counter,hex(acid_event.ctx) as ctx,product_type.name as source_type,plugin.product_type,plugin.name  " . $fromcnt  . ",alienvault.plugin LEFT JOIN alienvault.product_type ON product_type.id=plugin.product_type " . $where2 . " AND plugin.id=acid_event.plugin_id GROUP BY acid_event.plugin_id,acid_event.ctx " . $sort_sql[1];
    
} else {
    $sql = "select acid_event.plugin_id,$counter,acid_event.device_id as ctx,product_type.name as source_type,plugin.name  " . $fromcnt  . ",device,alienvault.plugin LEFT JOIN alienvault.product_type ON product_type.id=plugin.product_type " . $where . " AND device.id=acid_event.device_id  AND plugin.id=acid_event.plugin_id GROUP BY acid_event.plugin_id,acid_event.device_id " . $sort_sql[1];    
    $sql2 = "select acid_event.plugin_id,$counter,acid_event.device_id as ctx,product_type.name as source_type,plugin.name  " . $fromcnt  . ",device,alienvault.plugin LEFT JOIN alienvault.product_type ON product_type.id=plugin.product_type " . $where2 . " AND device.id=acid_event.device_id  AND plugin.id=acid_event.plugin_id GROUP BY acid_event.plugin_id,acid_event.device_id " . $sort_sql[1];
}

//echo $sql;
//$event_cnt = EventCnt($db, "", "", $sql);
/* Run the Query again for the actual data (with the LIMIT) */
$result = $qs->ExecuteOutputQuery($sql, $db);
if ($result->baseRecordCount()==0 && $use_ac) { $result = $qs->ExecuteOutputQuery($sql2, $db); }
$event_cnt = $qs->GetCalcRows($criteria_clauses[2], $result->baseRecordCount(), $db, "select count(*) from (SELECT cnt FROM ac_acid_event as acid_event WHERE 1=1 ".$criteria_clauses[2]." GROUP BY plugin_id) as cnt");

($debug_time_mode >= 1) ? $et->Mark("Retrieve Query Data") : '';
// if ($debug_mode == 1) {
    // $qs->PrintCannedQueryList();
    // $qs->DumpState();
    // echo "$sql<BR>";
// }
/* Print the current view number and # of rows */
$displaying = gettext("Displaying unique data sources %d-%d of <b>%s</b> matching your selection.");
$qs->PrintEstimatedResultCnt($displaying);
echo '<FORM METHOD="post" name="PacketForm" id="PacketForm" ACTION="base_stat_plugins.php">';
if ($qs->num_result_rows > 0)
{
    $qro->PrintHeader();
}
$i = 0;
// The below is due to changes in the queries...
// We need to verify that it works all the time -- Kevin
$and = (strpos($where, "WHERE") != 0) ? " AND " : " WHERE ";
$i = 0;
$report_data = array(); // data to fill report_data 
if (is_array($_SESSION["server"]) && $_SESSION["server"][0]!="")
	$_conn = $dbo->custom_connect($_SESSION["server"][0],$_SESSION["server"][2],$_SESSION["server"][3]);
else
	$_conn = $dbo->connect();
while (($myrow = $result->baseFetchRow()) && ($i < $qs->GetDisplayRowCnt())) {
	$plugin_id = $myrow["plugin_id"];
    $plugin_name = $myrow["name"];
    $product_type = $myrow["source_type"];
    if ($product_type == "") $product_type = _("Unknown type");
	$total_occurances = $myrow["events"];
	$ctx = $myrow["ctx"];

	$temp = "SELECT hex(acid_event.id) as id,plugin_sid.name as sig_name,acid_event.timestamp FROM acid_event LEFT JOIN alienvault.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid WHERE acid_event.plugin_id=$plugin_id ORDER BY timestamp DESC LIMIT 1";
	$result2 = $db->baseExecute($temp);
	$last = $result2->baseFetchRow();
	$result2->baseFreeRows();
	$last_signature = $last['sig_name'];
    $sig_id = $last['id'];
    $timestamp = $last["timestamp"];
    if ($tz!=0) $timestamp = gmdate("Y-m-d H:i:s",get_utc_unixtime($db,$timestamp)+(3600*$tz));
	$submit = "#" . (($qs->GetCurrentView() * $show_rows) + $i) . "-" . $sig_id;
	
    $tmp_rowid = rawurlencode($sig_id);
    /*echo '  <TD nowrap '.$bgcolor.'>&nbsp;&nbsp;
                 <INPUT TYPE="checkbox" NAME="action_chk_lst[' . $i . ']" VALUE="' . $tmp_rowid . '">
                 &nbsp;&nbsp;
             </TD>';
    echo '      <INPUT TYPE="hidden" NAME="action_lst[' . $i . ']" VALUE="' . $tmp_rowid . '">';*/
    $urlp = "base_qry_main.php?search=1&sensor=&bsf=Query+DB&search_str=&sip=&ossim_risk_a=+&plugin=$plugin_id";
    qroPrintEntryHeader($i);
    qroPrintEntry('&nbsp;&nbsp;&nbsp;<a href="'.$urlp.'">' . $plugin_name . '</a>','left',"","nowrap",$bgcolor);
	qroPrintEntry('&nbsp;<a href="'.$urlp.'">' . $total_occurances . '</a>',"center","","",$bgcolor);
	$sens = (Session::show_entities() && !empty($entities[$ctx])) ? $entities[$ctx] : ((Session::show_entities()) ? _("Unknown") : GetSensorName($ctx, $db));
    qroPrintEntry($sens,"center","","",$bgcolor);
	qroPrintEntry("&nbsp;&nbsp;&nbsp;$product_type" ,"left","","",$bgcolor);
    //qroPrintEntry('<FONT>' . '' . $sid_id . '' . (($avoid_counts != 1) ? ('(' . (round($total_occurances / $event_cnt * 100)) . '%)') : ('')) . '</FONT>', 'center', 'top', 'nowrap', $bgcolor);
	//qroPrintEntry("<A HREF='base_qry_alert.php?submit=" . rawurlencode($submit) . "&amp;sort_order='>".$last_signature."</a>","left","","",$bgcolor);
	qroPrintEntry("&nbsp;<A HREF='$urlp'>".$last_signature."</a>","left","","",$bgcolor);	
    qroPrintEntry($timestamp,"center","","nowrap",$bgcolor);
    qroPrintEntryFooter();
    $i++;
    $prev_time = null;
    
    // report_data
    $report_data[] = array (
        $plugin_name, $last_signature, 
        "", "", "", "", $timestamp,
        "", "", "", $sens,
        $total_occurances, 0 , 0
    );
}
$result->baseFreeRows();
$dbo->close($_conn);
$qro->PrintFooter();
$qs->PrintBrowseButtons();
$qs->PrintAlertActionButtons();
$qs->SaveReportData($report_data,$unique_plugins_report_type);
$qs->SaveState();
echo "\n</FORM>\n";
PrintBASESubFooter();
if ($debug_time_mode >= 1) {
    $et->Mark("Get Query Elements");
    $et->PrintTiming();
}
$db->baseClose();
echo "</body>\r\n</html>";
?>
