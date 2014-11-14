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


require ("base_conf.php");
require ("vars_session.php");
require ("$BASE_path/includes/base_constants.inc.php");
require ("$BASE_path/includes/base_include.inc.php");
include_once ("$BASE_path/base_db_common.php");
include_once ("$BASE_path/base_common.php");
include_once ("$BASE_path/base_qry_common.php");

if (GET('sensor') != "") ossim_valid(GET('sensor'), OSS_DIGIT, 'illegal:' . _("sensor"));;

if(GET('addr_type')=='hostname' || GET('addr_type')=='userdomain')
{ 
    $_SESSION["siem_default_group"] = "base_stat_uidm.php?addr_type=" . GET('addr_type') . "&sort_order=occur_d";
}

$addr_type = Util::htmlentities(ImportHTTPVar("addr_type", VAR_DIGIT | VAR_ALPHA | VAR_USCORE));
$submit = ImportHTTPVar("submit", VAR_ALPHA | VAR_SPACE, array(
    gettext("Delete Selected"),
    gettext("Delete ALL on Screen"),
    _ENTIREQUERY
));
$dst_ip = NULL;
// Check role out and redirect if needed -- Kevin
$roleneeded = 10000;
#$BUser = new BaseUser();
#if (($BUser->hasRole($roleneeded) == 0) && ($Use_Auth_System == 1)) base_header("Location: " . $BASE_urlpath . "/index.php");
$et = new EventTiming($debug_time_mode);
// The below three lines were moved from line 87 because of the odd errors some users were having
/* Connect to the Alert database */
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);

$cs = new CriteriaState("base_stat_uidm.php", "&amp;addr_type=$addr_type");
$cs->ReadState();
/* Dump some debugging information on the shared state */
// if ($debug_mode > 0) {
    // PrintCriteriaState();
// }

//print_r($_SESSION['ip_addr']);
if (!in_array($addr_type,array("userdomain","username","hostname","domain"))) $addr_type = "userdomain";
$type_name = ucfirst(str_replace("userdomain","user@domain",$addr_type))."s";

$page_title = _("Unique")." "._($type_name);
$qs = new QueryState();
$qs->AddCannedQuery("most_frequent", $freq_num_uaddr, gettext("Most Frequent")." "._($type_name), "occur_d");
$qs->MoveView($submit); /* increment the view if necessary */

if ($event_cache_auto_update == 1) UpdateAlertCache($db);
$criteria_clauses = ProcessCriteria();

// Include base_header.php
if ($qs->isCannedQuery()) PrintBASESubHeader($page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $cs->GetBackLink() , 1);
else PrintBASESubHeader($page_title, $page_title, $cs->GetBackLink() , 1);

$criteria = $criteria_clauses[0] . " " . $criteria_clauses[1];
if (preg_match("/user|domain/i",$addr_type)) { // from idm_data
    $from = " FROM acid_event " . $criteria_clauses[0] . ((!preg_match("/idm_data/",$criteria_clauses[0])) ? ", idm_data" : "");
    $where = " WHERE acid_event.id=idm_data.event_id AND idm_data.from_src=SRC_DST AND " . $criteria_clauses[1];
} else { // from acid_event
    $from = " FROM acid_event" . $criteria_clauses[0];
    $where = " WHERE " . $criteria_clauses[1];
}

if (preg_match("/^(.*)AND\s+\(\s+timestamp\s+[^']+'([^']+)'\s+\)\s+AND\s+\(\s+timestamp\s+[^']+'([^']+)'\s+\)(.*)$/", $where, $matches)) {
    if ($matches[2] != $matches[3]) {
        $where = $matches[1] . " AND timestamp BETWEEN('" . $matches[2] . "') AND ('" . $matches[3] . "') " . $matches[4];
    } else {
        $where = $matches[1] . " AND timestamp >= '" . $matches[2] . "' " . $matches[4];
    }
}
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
$et->Mark("Initialization");
$qs->RunAction($submit, PAGE_STAT_UADDR, $db);
$et->Mark("Alert Action");
/* Run the query to determine the number of rows (No LIMIT
$cnt_sql = "SELECT count(DISTINCT $addr_type_name) " . $from . $where;
if (!$use_ac) $qs->GetNumResultRows($cnt_sql, $db);)*/

$et->Mark("Counting Result size");
/* Setup the Query Results Table */
$qro = new QueryResultsOutput("base_stat_uidm.php?caller=" . $caller . "&amp;addr_type=" . $addr_type);

if ($addr_type=="userdomain") {
    $src_field = "CONCAT(idm_data.username,'@',idm_data.domain)";
    $dst_field = "CONCAT(idm_data.username,'@',idm_data.domain)";
} else {
    $src_field = "src_".$addr_type;
    $dst_field = "dst_".$addr_type;
}

//$qro->AddTitle(" ");
$qro->AddTitle($type_name, "addr_a", " ", " ORDER BY ip ASC", "addr_d", " ", " ORDER BY ip DESC");
$qro->AddTitle((Session::show_entities()) ? gettext("Context") : gettext("Sensor"));
$qro->AddTitle(gettext("Total") . "&nbsp;#", "occur_a", " ", " ORDER BY num_events ASC", "occur_d", " ", " ORDER BY num_events DESC");
$qro->AddTitle(_("Unique Events Src"), "sigsrc_a", " ", " ORDER BY num_sig_src ASC", "sigsrc_d", " ", " ORDER BY num_sig_src DESC");
$qro->AddTitle(_("Unique Events Dst"), "sigdst_a", " ", " ORDER BY num_sig_dst ASC", "sigdst_d", " ", " ORDER BY num_sig_dst DESC");
$qro->AddTitle(_("Unique Src."), "saddr_a", " ", " ORDER BY num_sip ASC", "saddr_d", " ", " ORDER BY num_sip DESC");
$qro->AddTitle(_("Unique Dst."), "daddr_a", "  ", " ORDER BY num_dip ASC", "daddr_d", " ", " ORDER BY num_dip DESC");
$sort_sql = $qro->GetSortSQL($qs->GetCurrentSort() , $qs->GetCurrentCannedQuerySort());

if (Session::show_entities()) 
{
    $src_sql = "SELECT $src_field as ip, COUNT(acid_event.id) as num_events, hex(ctx) as ctx, COUNT( DISTINCT acid_event.plugin_id, acid_event.plugin_sid ) as num_sig_src, 0 as num_sig_dst, 0 as num_sip, COUNT( DISTINCT ip_dst ) as num_dip " . $sort_sql[0] . $from . str_replace("SRC_DST","1",$where) . " GROUP BY ip,acid_event.ctx HAVING num_events>0 AND ip<>'' "; // . $sort_sql[1];

    $dst_sql = "SELECT $dst_field as ip, COUNT(acid_event.id) as num_events, hex(ctx) as ctx, 0 as num_sig_src, COUNT( DISTINCT acid_event.plugin_id, acid_event.plugin_sid ) as num_sig_dst, COUNT( DISTINCT ip_src ) as num_sip, 0 as num_dip " . $sort_sql[0] . $from . str_replace("SRC_DST","0",$where) . " GROUP BY ip,acid_event.ctx HAVING num_events>0 AND ip<>'' "; // . $sort_sql[1];

    $sql = "SELECT SQL_CALC_FOUND_ROWS ip,ctx as context,sum(num_events) as num_events,sum(num_sig_src) as num_sig_src, sum(num_sig_dst) as num_sig_dst, sum(num_sip) as num_sip,sum(num_dip) as num_dip
            FROM (($src_sql) UNION ($dst_sql)) as u WHERE ip is not NULL GROUP BY ip,context " . $sort_sql[1];
     
} 
else 
{
    $src_sql = "SELECT $src_field as ip, COUNT(acid_event.id) as num_events, sensor_id, COUNT( DISTINCT acid_event.plugin_id, acid_event.plugin_sid ) as num_sig_src, 0 as num_sig_dst, 0 as num_sip, COUNT( DISTINCT ip_dst ) as num_dip " . $sort_sql[0] . $from . ",device " . str_replace("SRC_DST","1",$where) . " AND device.id=acid_event.device_id GROUP BY ip,sensor_id HAVING num_events>0 AND ip<>'' "; // . $sort_sql[1];

    $dst_sql = "SELECT $dst_field as ip, COUNT(acid_event.id) as num_events, sensor_id, 0 as num_sig_src, COUNT( DISTINCT acid_event.plugin_id, acid_event.plugin_sid ) as num_sig_dst, COUNT( DISTINCT ip_src ) as num_sip, 0 as num_dip " . $sort_sql[0] . $from . ",device ". str_replace("SRC_DST","0",$where) . " AND device.id=acid_event.device_id GROUP BY ip,sensor_id HAVING num_events>0 AND ip<>'' "; // . $sort_sql[1];

    $sql = "SELECT SQL_CALC_FOUND_ROWS ip,HEX(sensor_id) as context,sum(num_events) as num_events,sum(num_sig_src) as num_sig_src, sum(num_sig_dst) as num_sig_dst, sum(num_sip) as num_sip,sum(num_dip) as num_dip
            FROM (($src_sql) UNION ($dst_sql)) as u WHERE ip is not NULL GROUP BY ip,sensor_id " . $sort_sql[1];
        
}

//print_r($sql);
/* Run the Query again for the actual data (with the LIMIT) */
$result = $qs->ExecuteOutputQuery($sql, $db);
$qs->GetCalcFoundRows("", $result->baseRecordCount(), $db);

$et->Mark("Retrieve Query Data");
// if ($debug_mode == 1) {
    // $qs->PrintCannedQueryList();
    // $qs->DumpState();
    // echo "$sql<BR>";
// }
/* Print the current view number and # of rows */
$displaying = gettext("Displaying unique ".$addr_type."s %d-%d of <b>%s</b> matching your selection.");
$qs->PrintResultCnt("",array(),$displaying);
echo '<FORM METHOD="post" name="PacketForm" id="PacketForm" ACTION="base_stat_uidm.php">';
if ($qs->num_result_rows > 0)
{
    $qro->PrintHeader();
}
$i = 0;

while (($myrow = $result->baseFetchRow()) && ($i < $qs->GetDisplayRowCnt())) {
    $currentIP = $myrow[0];
    $ctx = $myrow[1];
    $num_events = $myrow[2];
    $num_sig_src = $myrow[3];
    $num_sig_dst = $myrow[4];
    $num_sip = $myrow[5];
    $num_dip = $myrow[6];
    qroPrintEntryHeader($i);
    /* Generating checkbox value -- nikns */
    //($addr_type == SOURCE_IP) ? ($src_ip = $myrow[0]) : ($dst_ip = $myrow[0]);
    //$tmp_rowid = $src_ip . "_" . $dst_ip;
    //echo '    <TD><INPUT TYPE="checkbox" NAME="action_chk_lst[' . $i . ']" VALUE="' . $tmp_rowid . '">';
    //echo '    <INPUT TYPE="hidden" NAME="action_lst[' . $i . ']" VALUE="' . $tmp_rowid . '"></TD>';
    /* Check for a NULL IP which indicates an event (e.g. portscan)
    * which has no IP
    */ 
    qroPrintEntry( BuildIDMLink($currentIP,$addr_type) . $currentIP .'</A>&nbsp;' ,'center','','nowrap');
    
    /* Print # of Occurances */
    $tmp_iplookup = 'base_qry_main.php?num_result_rows=-1' . '&submit=' . gettext("Query DB") . '&current_view=-1';
    $tmp_iplookup2 = 'base_stat_alerts.php?num_result_rows=-1' . '&submit=' . gettext("Query DB") . '&current_view=-1&sort_order=occur_d';

    $url_criteria = BuildIDMVars($currentIP, $addr_type);
    $url_criteria_src = BuildIDMVars($currentIP, $addr_type, "src");
    $url_criteria_dst = BuildIDMVars($currentIP, $addr_type, "dst");
    
    qroPrintEntry((Session::show_entities() && !empty($entities[$ctx])) ? $entities[$ctx] : ((Session::show_entities()) ? _("Unknown") : GetSensorName($ctx, $db)));
    qroPrintEntry('<A HREF="' . $tmp_iplookup . $url_criteria . '">' . $num_events . '</A>');
    qroPrintEntry('<A HREF="' . $tmp_iplookup2 . $url_criteria_src . '">' . $num_sig_src . '</A>');
    qroPrintEntry('<A HREF="' . $tmp_iplookup2 . $url_criteria_dst . '">' . $num_sig_dst . '</A>');
    qroPrintEntry($num_sip);
    qroPrintEntry($num_dip);
    qroPrintEntryFooter();
    ++$i;
    
}
$result->baseFreeRows();
$qro->PrintFooter();
$qs->PrintBrowseButtons();
$qs->PrintAlertActionButtons();
$qs->SaveState();
ExportHTTPVar("addr_type", $addr_type);
echo "\n</FORM><br>\n";
$et->Mark("Get Query Elements");
$et->PrintTiming();
PrintBASESubFooter();
$db->baseClose();
echo "</body>\r\n</html>";
?>
