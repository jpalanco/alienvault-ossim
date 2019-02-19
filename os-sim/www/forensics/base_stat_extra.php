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
$addr_type = GET('addr_type');
if (!preg_match("/^user(name|data[0-9])$/",$addr_type)) {
    die("Injection found");
}
$_SESSION["siem_default_group"] = "base_stat_extra.php?addr_type=$addr_type&sort_order=occur_d";

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
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password, 0, 1);

$cs = new CriteriaState("base_stat_extra.php", "&amp;addr_type=$addr_type");
$cs->ReadState();
/* Dump some debugging information on the shared state */
// if ($debug_mode > 0) {
    // PrintCriteriaState();
// }

//print_r($_SESSION['ip_addr']);

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
$from = " FROM acid_event $criteria_clauses[0] JOIN extra_data ON acid_event.id = extra_data.event_id ";
$where = " WHERE " . $criteria_clauses[1];

if (preg_match("/^(.*)AND\s+\(\s+timestamp\s+[^']+'([^']+)'\s+\)\s+AND\s+\(\s+timestamp\s+[^']+'([^']+)'\s+\)(.*)$/", $where, $matches)) {
    if ($matches[2] != $matches[3]) {
        $where = $matches[1] . " AND timestamp BETWEEN('" . $matches[2] . "') AND ('" . $matches[3] . "') " . $matches[4];
    } else {
        $where = $matches[1] . " AND timestamp >= '" . $matches[2] . "' " . $matches[4];
    }
}
$qs->SetActionSQL($from . $where);
$et->Mark("Initialization");
$qs->RunAction($submit, PAGE_STAT_UADDR, $db);
$et->Mark("Alert Action");
/* Run the query to determine the number of rows (No LIMIT
$cnt_sql = "SELECT count(DISTINCT $addr_type_name) " . $from . $where;
if (!$use_ac) $qs->GetNumResultRows($cnt_sql, $db);)*/

$et->Mark("Counting Result size");
/* Setup the Query Results Table */
$qro = new QueryResultsOutput("base_stat_extra.php?caller=" . $caller . "&amp;addr_type=" . $addr_type);

//$qro->AddTitle(" ");
$qro->AddTitle($addr_type, "addr_a", " ", " ORDER BY $addr_type ASC", "addr_d", " ", " ORDER BY $addr_type DESC");
$qro->AddTitle((Session::show_entities()) ? gettext("Context") : gettext("Sensor"));
$events_title = _("Events"). "&nbsp;# <span class='idminfo' txt='".Util::timezone(Util::get_timezone())."'>(*)</span>";
$qro->AddTitle($events_title, "occur_a", " ", " ORDER BY num_events ASC", "occur_d", " ", " ORDER BY num_events DESC");
$qro->AddTitle(_("Unique Events"), "sigsrc_a", " ", " ORDER BY num_sig ASC", "sigsrc_d", " ", " ORDER BY num_sig DESC");
$qro->AddTitle(_("Unique"), "saddr_a", " ", " ORDER BY num_ip ASC", "saddr_d", " ", " ORDER BY num_ip DESC");
$sort_sql = $qro->GetSortSQL($qs->GetCurrentSort() , $qs->GetCurrentCannedQuerySort());

$ctx = "sensor_id";
if (Session::show_entities())
{
    $ctx = "ctx";
} else {
    $from .= " JOIN device ON device.id=acid_event.device_id";
}
$sql = "SELECT SQL_CALC_FOUND_ROWS $addr_type, COUNT(acid_event.id) as num_events, hex($ctx) as context, COUNT( DISTINCT acid_event.plugin_id, acid_event.plugin_sid ) as num_sig, 
        COUNT( DISTINCT ip_dst ) as num_ip {$sort_sql[0]} $from $where AND $addr_type != '' GROUP BY $addr_type,$ctx {$sort_sql[1]}";

if (file_exists('/tmp/debug_siem'))
{
    file_put_contents("/tmp/siem", "STATS IDM:$sql\n$cnt_sql\n", FILE_APPEND);
}
/* Run the Query again for the actual data (with the LIMIT) */
session_write_close();
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
echo '<FORM METHOD="post" name="PacketForm" id="PacketForm" ACTION="base_stat_extra.php">';
if ($qs->num_result_rows > 0)
{
    $qro->PrintHeader();
}
$i = 0;

while (($myrow = $result->baseFetchRow()) && ($i < $qs->GetDisplayRowCnt())) {
    $currentIP = $myrow[0];
    $ctx = $myrow[2];
    $num_events = $myrow[1];
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
    qroPrintEntry( BuildIDMLink($currentIP,$addr_type) . $currentIP .'</A>&nbsp;' ,'left','','nowrap');

    /* Print # of Occurances */
    $tmp_iplookup = 'base_qry_main.php?num_result_rows=-1' . '&submit=' . gettext("Query DB") . '&current_view=-1';
    $tmp_iplookup2 = 'base_stat_alerts.php?num_result_rows=-1' . '&submit=' . gettext("Query DB") . '&current_view=-1&sort_order=occur_d';

//    $url_criteria = BuildIDMVars($currentIP, $addr_type);
//    $url_criteria_src = BuildIDMVars($currentIP, $addr_type, "src");
//    $url_criteria_dst = BuildIDMVars($currentIP, $addr_type, "dst");
      $url_criteria = '&userdata%5B0%5D='.$addr_type.'&userdata%5B1%5D=EQ&userdata%5B2%5D='.$currentIP;

    qroPrintEntry((Session::show_entities() && !empty($entities[$ctx])) ? $entities[$ctx] : ((Session::show_entities()) ? _("Unknown") : GetSensorName($ctx, $db)),'center','middle');
    qroPrintEntry('<A HREF="' . $tmp_iplookup . $url_criteria . '">' . Util::number_format_locale($num_events,0) . '</A>','center','middle');
   // qroPrintEntry('<A HREF="' . $tmp_iplookup2 . $url_criteria_src . '">' . Util::number_format_locale($num_sig,0) . '</A>','center','middle');
    qroPrintEntry( Util::number_format_locale($num_sig,0) ,'center','middle');
    qroPrintEntry(Util::number_format_locale($num_ip,0),'center','middle');
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
