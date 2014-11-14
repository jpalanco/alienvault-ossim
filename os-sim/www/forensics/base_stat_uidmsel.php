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

if(GET('addr_type')=='src_hostname' || GET('addr_type')=='dst_hostname')
{ 
    $_SESSION["siem_default_group"] = "base_stat_uidmsel.php?addr_type=" . GET('addr_type') . "&sort_order=occur_d";
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

$cs = new CriteriaState("base_stat_uidmsel.php", "&amp;addr_type=$addr_type");
$cs->ReadState();
/* Dump some debugging information on the shared state */
// if ($debug_mode > 0) {
    // PrintCriteriaState();
// }

//print_r($_SESSION['ip_addr']);

if (!in_array($addr_type,array("src_userdomain","dst_userdomain","src_hostname","dst_hostname","src_domain","dst_domain"))) $addr_type = "src_userdomain";
$type_name = ucwords(str_replace("_"," ",str_replace("userdomain","user@domain",$addr_type)))."s";
list($source,$field) = explode("_",$addr_type,2);

$qs = new QueryState();
$qs->AddCannedQuery("most_frequent", $freq_num_uaddr, gettext("Most Frequent")." $type_name", "occur_d");
$qs->MoveView($submit); /* increment the view if necessary */

$page_title = gettext("Unique $type_name");
$results_title = gettext($type_name);
//$addr_type_name = "ip_dst";

if ($event_cache_auto_update == 1) UpdateAlertCache($db);
$criteria_clauses = ProcessCriteria();

// Include base_header.php
if ($qs->isCannedQuery()) PrintBASESubHeader($page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $cs->GetBackLink() , 1);
else PrintBASESubHeader($page_title, $page_title, $cs->GetBackLink() , 1);

$criteria = $criteria_clauses[0] . " " . $criteria_clauses[1];

if (preg_match("/user|domain/i",$addr_type)) {
    $from = " FROM acid_event " . $criteria_clauses[0]. ((!preg_match("/idm_data/",$criteria_clauses[0])) ? ", idm_data" : "");
    $where = " WHERE acid_event.id=idm_data.event_id AND " . $criteria_clauses[1];
} else {
    $from = " FROM acid_event " . $criteria_clauses[0];
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
/* Run the query to determine the number of rows (No LIMIT)*/
$field    = $addr_type;
$from_src = "";
if ($addr_type=="src_userdomain") {
    $field = "CONCAT(idm_data.username,'@',idm_data.domain)";
    $from_src = " AND idm_data.from_src=1";    
}
elseif ($addr_type=="dst_userdomain") {
    $field = "CONCAT(idm_data.username,'@',idm_data.domain)";
    $from_src = " AND idm_data.from_src=0";
}
$cnt_sql = "SELECT count(DISTINCT $field) " . $from . $where . " AND $field <> ''";
$qs->GetNumResultRows($cnt_sql, $db);

$et->Mark("Counting Result size");
/* Setup the Query Results Table */
$qro = new QueryResultsOutput("base_stat_uidmsel.php?caller=" . $caller . "&amp;addr_type=" . $addr_type);
//$qro->AddTitle(" ");
$qro->AddTitle($results_title, "addr_a", " ", " ORDER BY $addr_type ASC", "addr_d", " ", " ORDER BY $addr_type DESC");
if ($resolve_IP == 1) $qro->AddTitle("FQDN");
$qro->AddTitle((Session::show_entities()) ? gettext("Context") : gettext("Sensor"));
$qro->AddTitle(gettext("Total") . "&nbsp;#", "occur_a", " ", " ORDER BY num_events ASC", "occur_d", " ", " ORDER BY num_events DESC");
$qro->AddTitle(gettext("Unique&nbsp;Events"), "sig_a", " ", " ORDER BY num_sig ASC", "sig_d", " ", " ORDER BY num_sig DESC");

$displaytitle = gettext("Displaying unique ".strtolower($type_name)." %d-%d of <b>%s</b> matching your selection.");
if (!Session::am_i_admin()) $displaytitle = preg_replace("/\. <b>.*/",".",$displaytitle);
$qro->AddTitle("Unique ".gettext(ucfirst($source)."."), "saddr_a", " ", " ORDER BY num_ip ASC", "saddr_d", " ", " ORDER BY num_ip DESC");

$sort_sql = $qro->GetSortSQL($qs->GetCurrentSort() , $qs->GetCurrentCannedQuerySort());

if (Session::show_entities()) 
{
    $sql = "SELECT $field as idm, hex(ctx) as context, COUNT(acid_event.id) as num_events, COUNT( DISTINCT acid_event.plugin_id, acid_event.plugin_sid ) as num_sig, COUNT( DISTINCT ip_".$source." ) as num_ip ". $sort_sql[0] . $from . $where . " $from_src GROUP BY idm,context HAVING num_events>0 AND idm<>'' " . $sort_sql[1];
}
else
{
    $sql = "SELECT $field as idm, HEX(sensor_id) as sensor_id, COUNT(acid_event.id) as num_events, COUNT( DISTINCT acid_event.plugin_id, acid_event.plugin_sid ) as num_sig, COUNT( DISTINCT ip_".$source." ) as num_ip ". $sort_sql[0] . $from . ",device " . $where . " $from_src  AND device.id=acid_event.device_id GROUP BY idm,sensor_id HAVING num_events>0 AND idm<>'' " . $sort_sql[1];
}
//print_r($sql."-".$cnt_sql);
/* Run the Query again for the actual data (with the LIMIT) */
$result = $qs->ExecuteOutputQuery($sql, $db);

$et->Mark("Retrieve Query Data");
// if ($debug_mode == 1) {
    // $qs->PrintCannedQueryList();
    // $qs->DumpState();
    // echo "$sql<BR>";
// }
/* Print the current view number and # of rows */
$qs->PrintResultCnt("",array(),$displaytitle);
echo '<FORM METHOD="post" name="PacketForm" id="PacketForm" ACTION="base_stat_uidmsel.php">';
if ($qs->num_result_rows > 0)
{
    $qro->PrintHeader();
}
$i = 0;
	
while (($myrow = $result->baseFetchRow()) && ($i < $qs->GetDisplayRowCnt())) {
    $currentIP = $myrow[0];
    $ctx = $myrow[1];
    $num_events = $myrow[2];
    $num_sig = $myrow[3];
    $num_ip = $myrow[4];
    qroPrintEntryHeader($i);
    /* Generating checkbox value -- nikns */
    //($addr_type == SOURCE_IP) ? ($src_ip = $myrow[0]) : ($dst_ip = $myrow[0]);
    //$tmp_rowid = $src_ip . "_" . $dst_ip;
    //echo '    <TD><INPUT TYPE="checkbox" NAME="action_chk_lst[' . $i . ']" VALUE="' . $tmp_rowid . '">';
    //echo '    <INPUT TYPE="hidden" NAME="action_lst[' . $i . ']" VALUE="' . $tmp_rowid . '"></TD>';
    /* Check for a NULL IP which indicates an event (e.g. portscan)
    * which has no IP
    */
    qroPrintEntry( BuildIDMLink($currentIP,$field,$source) . $currentIP .'</A>&nbsp;' ,'center','','nowrap');
    
    /* Print # of Occurances */
    $tmp_iplookup = 'base_qry_main.php?num_result_rows=-1' . '&amp;submit=' . gettext("Query DB") . '&amp;current_view=-1';
    $tmp_iplookup2 = 'base_stat_alerts.php?num_result_rows=-1' . '&amp;submit=' . gettext("Query DB") . '&amp;current_view=-1&sort_order=occur_d';

    $url_criteria = BuildIDMVars($currentIP, $field, $source);
    
    qroPrintEntry((Session::show_entities() && !empty($entities[$ctx])) ? $entities[$ctx] : ((Session::show_entities()) ? _("Unknown") : GetSensorName($ctx, $db)));
    qroPrintEntry('<A HREF="' . $tmp_iplookup . $url_criteria . '">' . $num_events . '</A>');
    qroPrintEntry('<A HREF="' . $tmp_iplookup2 . $url_criteria . '">' . $num_sig . '</A>');
    qroPrintEntry($num_ip);
    
    qroPrintEntryFooter();
    ++$i;
    
}
$result->baseFreeRows();
$qro->PrintFooter();
$qs->PrintBrowseButtons();
$qs->PrintAlertActionButtons();
$qs->SaveState();
ExportHTTPVar("addr_type", $addr_type);
echo "\n</FORM>\n<br>";
$et->Mark("Get Query Elements");
$et->PrintTiming();
PrintBASESubFooter();
$db->baseClose();
echo "</body>\r\n</html>";
?>
