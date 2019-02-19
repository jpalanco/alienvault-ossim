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
Session::logcheck('analysis-menu', 'EventsForensics');

if (empty($_GET["sort_order"]))
{
    $_GET["sort_order"] = "occur_d";
}

require 'base_conf.php';
require 'vars_session.php';
require ("$BASE_path/includes/base_constants.inc.php");
require ("$BASE_path/includes/base_include.inc.php");

include_once ("$BASE_path/base_db_common.php");
include_once ("$BASE_path/base_common.php");
include_once ("$BASE_path/base_qry_common.php");
include_once 'classes/Reputation.inc';
require_once 'classes/geolocation.inc';

$Reputation = new Reputation();

if (GET('sensor') != '')
{
    ossim_valid(GET('sensor'), OSS_DIGIT, 'illegal:' . _("sensor"));;
}

$_SESSION["siem_default_group"] = "base_stat_uaddress.php?sort_order=occur_d";

// Geoip

$geoloc = new Geolocation('/usr/share/geoip/GeoLiteCity.dat');

$addr_type = ImportHTTPVar("addr_type", VAR_DIGIT);
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

$cs = new CriteriaState("base_stat_uaddress.php", "&amp;sort_order=occur_d");
$cs->ReadState();
/* Dump some debugging information on the shared state */
// if ($debug_mode > 0) {
    // PrintCriteriaState();
// }

//print_r($_SESSION['ip_addr']);
$page_title = _("Unique Address");
$qs = new QueryState();
$qs->AddCannedQuery("most_frequent", $freq_num_uaddr, gettext("Most Frequent IP addresses"), "occur_d");
$qs->MoveView($submit); /* increment the view if necessary */

if ($event_cache_auto_update == 1) UpdateAlertCache($db);
$criteria_clauses = ProcessCriteria();

// Include base_header.php
if ($qs->isCannedQuery()) PrintBASESubHeader($page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $cs->GetBackLink() , 1);
else PrintBASESubHeader($page_title, $page_title, $cs->GetBackLink() , 1);

$criteria = $criteria_clauses[0] . " " . $criteria_clauses[1];
$where    = " WHERE " . $criteria_clauses[1];
$use_ac   = $criteria_clauses[3];

// Check if we can use acc table
$uevent    = "COUNT( DISTINCT acid_event.plugin_id, acid_event.plugin_sid )";
if ($use_ac)
{
    $from    = " FROM po_acid_event as acid_event " . $criteria_clauses[0];
    $nevents = "SUM(acid_event.cnt)";
}
else
{
    $from    = " FROM acid_event " . $criteria_clauses[0];
    $nevents = "COUNT(acid_event.id)";
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
*/

$et->Mark("Counting Result size");
/* Setup the Query Results Table */
$qro = new QueryResultsOutput("base_stat_uaddress.php?caller=" . $caller . "&amp;addr_type=" . $addr_type);
$qro->AddTitle(_("IP address"), "addr_a", " ", " ORDER BY ip ASC", "addr_d", " ", " ORDER BY ip DESC");
$qro->AddTitle(gettext("OTX"));
if ($resolve_IP == 1) $qro->AddTitle("FQDN");
$qro->AddTitle((Session::show_entities()) ? gettext("Context") : gettext("Sensor"));
$qro->AddTitle(gettext("Events Src.") . "&nbsp;# <span class='idminfo' txt='".Util::timezone(Util::get_timezone())."'>(*)</span>", "occur_a", " ", " ORDER BY src_num_events ASC", "occur_d", " ", " ORDER BY src_num_events DESC");
$qro->AddTitle(_("Unique Events Src"), "sigsrc_a", " ", " ORDER BY num_sig_src ASC", "sigsrc_d", " ", " ORDER BY num_sig_src DESC");
$qro->AddTitle(_("Unique Src. Contacted"), "saddr_a", " ", " ORDER BY num_sip ASC", "saddr_d", " ", " ORDER BY num_sip DESC");
$qro->AddTitle(gettext("Events Dst.") . "&nbsp;# <span class='idminfo' txt='".Util::timezone(Util::get_timezone())."'>(*)</span>", "occur_ad", " ", " ORDER BY dst_num_events ASC", "occur_dd", " ", " ORDER BY dst_num_events DESC");
$qro->AddTitle(_("Unique Events Dst"), "sigdst_a", " ", " ORDER BY num_sig_dst ASC", "sigdst_d", " ", " ORDER BY num_sig_dst DESC");
$qro->AddTitle(_("Unique Dest. Contacted"), "daddr_a", "  ", " ORDER BY num_dip ASC", "daddr_d", " ", " ORDER BY num_dip DESC");
$sort_sql = $qro->GetSortSQL($qs->GetCurrentSort() , $qs->GetCurrentCannedQuerySort());

// Queries
if (Session::show_entities())
{
    $src_sql = "SELECT ip_src as ip, HEX(src_host) AS host_id, ctx, $nevents as src_num_events, 0 as dst_num_events, 0 as num_sip, COUNT( DISTINCT ip_dst ) as num_dip, $uevent as num_sig_src, 0 as num_sig_dst " . $sort_sql[0] . $from . $where . " GROUP BY ip_src,ctx HAVING src_num_events>0 " . $sort_sql[1];

    $dst_sql = "SELECT ip_dst as ip, HEX(dst_host) AS host_id, ctx, 0 as src_num_events, $nevents as dst_num_events, COUNT( DISTINCT ip_src ) as num_sip, 0 as num_dip, 0 as num_sig_src, $uevent as num_sig_dst " . $sort_sql[0] . $from . $where . " GROUP BY ip_dst,ctx HAVING dst_num_events>0 " . $sort_sql[1];

    $sql = "SELECT SQL_CALC_FOUND_ROWS ip, hex(ctx) as ctx, sum(src_num_events) as src_num_events, sum(dst_num_events) as dst_num_events, sum(num_sig_src) as num_sig_src, sum(num_sig_dst) as num_sig_dst, sum(num_sip) as num_sip,sum(num_dip) as num_dip, host_id
            FROM (($src_sql) UNION ($dst_sql)) as u GROUP BY ip,ctx " . $sort_sql[1];
}
else
{
    $src_sql = "SELECT ip_src as ip, HEX(src_host) AS host_id, sensor_id, $nevents as src_num_events, 0 as dst_num_events, 0 as num_sip, COUNT( DISTINCT ip_dst ) as num_dip, $uevent as num_sig_src, 0 as num_sig_dst " . $sort_sql[0] . $from . ",device " . $where . " AND device.id=acid_event.device_id GROUP BY ip_src,device.sensor_id HAVING src_num_events>0 " . $sort_sql[1];

    $dst_sql = "SELECT ip_dst as ip, HEX(dst_host) AS host_id, sensor_id, 0 as src_num_events, $nevents as dst_num_events, COUNT( DISTINCT ip_src ) as num_sip, 0 as num_dip, 0 as num_sig_src, $uevent as num_sig_dst " . $sort_sql[0] . $from . ",device " . $where . " AND device.id=acid_event.device_id GROUP BY ip_dst,device.sensor_id HAVING dst_num_events>0 " . $sort_sql[1];

    $sql = "SELECT SQL_CALC_FOUND_ROWS ip, HEX(sensor_id) as sensor_id, sum(src_num_events) as src_num_events, sum(dst_num_events) as dst_num_events, sum(num_sig_src) as num_sig_src, sum(num_sig_dst) as num_sig_dst, sum(num_sip) as num_sip,sum(num_dip) as num_dip, host_id
            FROM (($src_sql) UNION ($dst_sql)) as u GROUP BY ip,sensor_id " . $sort_sql[1];
}

if (file_exists('/tmp/debug_siem'))
{
    file_put_contents("/tmp/siem", "STATS IP:$sql\n", FILE_APPEND);
}

/* Run the Query again for the actual data (with the LIMIT) */
session_write_close();
$result = $qs->ExecuteOutputQuery($sql, $db);
//$qs->GetNumResultRows($cnt_sql, $db);)
$event_cnt = $qs->GetCalcFoundRows($cnt_sql, $result->baseRecordCount(), $db);

$et->Mark("Retrieve Query Data");
// if ($debug_mode == 1) {
    // $qs->PrintCannedQueryList();
    // $qs->DumpState();
    // echo "$sql<BR>";
// }
/* Print the current view number and # of rows */
$displaying = gettext("Displaying unique addresses %d-%d of <b>%s</b> matching your selection.");
$qs->PrintResultCnt("",array(),$displaying);
echo '<FORM METHOD="post" name="PacketForm" id="PacketForm" ACTION="base_stat_uaddress.php">';
if ($qs->num_result_rows > 0)
{
    $qro->PrintHeader();
}
$i = 0;
$report_data = array(); // data to fill report_data

if (is_array($_SESSION["server"]) && $_SESSION["server"][0]!="")
    $_conn = $dbo->custom_connect($_SESSION["server"][0],$_SESSION["server"][2],$_SESSION["server"][3]);
else
    $_conn = $dbo->connect();

while (($myrow = $result->baseFetchRow()) && ($i < $qs->GetDisplayRowCnt())) {
    $currentIP = inet_ntop($myrow[0]);
    $host_id   = $myrow[8];
    $ctx = $myrow[1]; // ctx OR sensor_id
    list($prio,$rel,$act) = $Reputation->get_data_by_ip($currentIP);
    $src_num_events = $myrow[2];
    $dst_num_events = $myrow[3];
    $num_sig_src = $myrow[4];
    $num_sig_dst = $myrow[5];
    $num_sip = $myrow[6];
    $num_dip = $myrow[7];
    if ($myrow[0] == NULL) $no_ip = true;
    else $no_ip = false;
    qroPrintEntryHeader($i);
    /* Generating checkbox value -- nikns
    ($addr_type == SOURCE_IP) ? ($src_ip = $currentIP) : ($dst_ip = $currentIP);
    $tmp_rowid = $src_ip . "_" . $dst_ip . "_" . $ctx;
    echo '    <TD><INPUT TYPE="checkbox" NAME="action_chk_lst[' . $i . ']" VALUE="' . $tmp_rowid . '">';
    echo '    <INPUT TYPE="hidden" NAME="action_lst[' . $i . ']" VALUE="' . $tmp_rowid . '"></TD>';
    /* Check for a NULL IP which indicates an event (e.g. portscan)
    * which has no IP
    */
    if ($no_ip)
    {
        qroPrintEntry(gettext("unknown"));
        qroPrintEntry(gettext("N/A"), "center", "middle");
    }
    else
    {
        $geo_info = Asset_host::get_extended_location($_conn, $geoloc, $currentIP);
        if ($geo_info['html_icon'] != '')
        {
            $country_img = $geo_info['html_icon'].' ';
            $slnk        = $current_url.preg_replace("/.*src\='\/ossim([^']+)'.*/", "\\1", $country_img);
        }
        else
        {
            $country_img = "";
            $slnk        = "";
        }

        $div = '<div id="'.$currentIP.';'.$currentIP.';'.$host_id.'" ctx="'.((Session::show_entities()) ? $ctx : Session::get_default_ctx()).'" class="HostReportMenu" style="padding:0px 0px 0px 25px">'; // '.getrepbgcolor($prio,1).'
        $bdiv = '</div>';
        qroPrintEntry( $div . $country_img . '&nbsp;' . BuildAddressLink($currentIP, 32) . $currentIP . '</A>&nbsp;' . $bdiv,'left','','nowrap');
        qroPrintEntry(getrepimg($prio,$rel,$act,$currentIP), "center", "middle");
    }

    if ($resolve_IP == 1) qroPrintEntry('&nbsp;&nbsp;' . baseGetHostByAddr($currentIP, $ctx, $db) . '&nbsp;&nbsp;');
    /* Print # of Occurances */
    $tmp_iplookup = 'base_qry_main.php?num_result_rows=-1' . '&amp;submit=' . gettext("Query DB") . '&amp;current_view=-1';
    $tmp_iplookup2 = 'base_stat_alerts.php?num_result_rows=-1' . '&amp;submit=' . gettext("Query DB") . '&amp;current_view=-1&sort_order=occur_d';

    if ($no_ip) $url_criteria_src = BuildSrcIPFormVars(NULL_IP);
    else $url_criteria_src = BuildSrcIPFormVars($currentIP);

    if ($no_ip) $url_criteria_dst = BuildDstIpFormVars(NULL_IP);
    else $url_criteria_dst = BuildDstIPFormVars($currentIP);

    qroPrintEntry((Session::show_entities() && !empty($entities[$ctx])) ? $entities[$ctx] : ((Session::show_entities()) ? _("Unknown") : GetSensorName($ctx, $db)), "center", "middle");
    qroPrintEntry('<A HREF="' . $tmp_iplookup . $url_criteria_src . '">' . Util::number_format_locale($src_num_events,0) . '</A>', "center", "middle");
    qroPrintEntry('<A HREF="' . $tmp_iplookup2 . $url_criteria_src . '">' . Util::number_format_locale($num_sig_src,0) . '</A>', "center", "middle");
    qroPrintEntry(Util::number_format_locale($num_sip,0), "center", "middle");
    qroPrintEntry('<A HREF="' . $tmp_iplookup . $url_criteria_dst . '">' . Util::number_format_locale($dst_num_events,0) . '</A>', "center", "middle");
    qroPrintEntry('<A HREF="' . $tmp_iplookup2 . $url_criteria_dst . '">' . Util::number_format_locale($num_sig_dst,0) . '</A>', "center", "middle");
    qroPrintEntry(Util::number_format_locale($num_dip,0), "center", "middle");
    qroPrintEntryFooter();
    ++$i;

    /* report_data
    $report_data[] = array (
        $currentIP, $slnk,
        $num_sig, $num_ip,
        "", "", "", "", "", "", "",
        intval($_GET['addr_type']), $num_sensors , $num_events
    );*/
}
$result->baseFreeRows();
$dbo->close($_conn);
$qro->PrintFooter();
$qs->PrintBrowseButtons();
$qs->PrintAlertActionButtons();
$qs->SaveState();
ExportHTTPVar("addr_type", $addr_type);
echo "\n</FORM>\n";
PrintBASESubFooter();
if ($debug_time_mode >= 1) {
    $et->Mark("Get Query Elements");
    $et->PrintTiming();
}
$db->baseClose();
echo "</body>\r\n</html>";
