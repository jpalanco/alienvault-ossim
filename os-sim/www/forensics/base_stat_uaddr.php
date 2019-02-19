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

if(GET('addr_type') == '1' || GET('addr_type') == '2')
{ 
    $_SESSION["siem_default_group"] = "base_stat_uaddr.php?addr_type=" . GET('addr_type') . "&sort_order=occur_d";
}

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

$cs = new CriteriaState("base_stat_uaddr.php", "&amp;addr_type=$addr_type&amp;sort_order=occur_d");
$cs->ReadState();
/* Dump some debugging information on the shared state */
// if ($debug_mode > 0) {
    // PrintCriteriaState();
// }

//print_r($_SESSION['ip_addr']);

$qs = new QueryState();
$qs->AddCannedQuery("most_frequent", $freq_num_uaddr, gettext("Most Frequent IP addresses"), "occur_d");
$qs->MoveView($submit); /* increment the view if necessary */
if ($addr_type == SOURCE_IP) {
    $page_title = gettext("Unique Source Address(es)");
    $results_title = gettext("Src IP address");
    $addr_type_name = "ip_src";
    $addr_type_id   = "HEX(src_host) AS src_host";
} else {
    if ($addr_type != DEST_IP) ErrorMessage(gettext("CRITERIA ERROR: unknown address type -- assuming Dst address"));
    $page_title = gettext("Unique Destination Address(es)");
    $results_title = gettext("Dst IP address");
    $addr_type_name = "ip_dst";
    $addr_type_id   = "HEX(dst_host) AS dst_host";
}

if ($event_cache_auto_update == 1) UpdateAlertCache($db);
$criteria_clauses = ProcessCriteria();

// Include base_header.php
if ($qs->isCannedQuery()) PrintBASESubHeader($page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $cs->GetBackLink() , 1);
else PrintBASESubHeader($page_title, $page_title, $cs->GetBackLink() , 1);

$criteria = $criteria_clauses[0] . " " . $criteria_clauses[1];
$where    = " WHERE " . $criteria_clauses[1];
$use_ac   = $criteria_clauses[3];

// Check if we can use acc table
$uevent   = "COUNT( DISTINCT acid_event.plugin_id, acid_event.plugin_sid )";
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
$qs->AddValidAction("del_alert");
//$qs->AddValidAction("email_alert");
//$qs->AddValidAction("email_alert2");
//$qs->AddValidAction("csv_alert");
//$qs->AddValidAction("archive_alert");
//$qs->AddValidAction("archive_alert2");
$qs->AddValidActionOp(gettext("Delete Selected"));
$qs->AddValidActionOp(gettext("Delete ALL on Screen"));
$qs->SetActionSQL($from . $where);
$et->Mark("Initialization");
$qs->RunAction($submit, PAGE_STAT_UADDR, $db);
$et->Mark("Alert Action");
/* Run the query to determine the number of rows (No LIMIT)*/
//$cnt_sql = "SELECT count(DISTINCT $addr_type_name) " . $from . $where;

$et->Mark("Counting Result size");
/* Setup the Query Results Table */
$qro = new QueryResultsOutput("base_stat_uaddr.php?caller=" . $caller . "&amp;addr_type=" . $addr_type);
$qro->AddTitle(" ");
$qro->AddTitle($results_title, "addr_a", " ", " ORDER BY $addr_type_name ASC", "addr_d", " ", " ORDER BY $addr_type_name DESC");
$qro->AddTitle(gettext("OTX"));
if ($resolve_IP == 1) $qro->AddTitle("FQDN");
$qro->AddTitle((Session::show_entities()) ? gettext("Context") : gettext("Sensor"));
$qro->AddTitle(gettext("Events") . "&nbsp;# <span class='idminfo' txt='".Util::timezone(Util::get_timezone())."'>(*)</span>", "occur_a", " ", " ORDER BY num_events ASC", "occur_d", " ", " ORDER BY num_events DESC");
$qro->AddTitle(gettext("Unique&nbsp;Events"), "sig_a", " ", " ORDER BY num_sig ASC", "sig_d", " ", " ORDER BY num_sig DESC");
if ($addr_type == DEST_IP) {
	$displaytitle = gettext("Displaying unique destination addresses %d-%d of <b>%s</b> matching your selection.");
    $qro->AddTitle(gettext("Unique Src. Contacted."), "saddr_a", " ", " ORDER BY num_sip ASC", "saddr_d", " ", " ORDER BY num_sip DESC");
} else {
	$displaytitle = gettext("Displaying unique source addresses %d-%d of <b>%s</b> matching your selection.");
    $qro->AddTitle(gettext("Unique Dst. Contacted"), "daddr_a", "  ", " ORDER BY num_dip ASC", "daddr_d", " ", " ORDER BY num_dip DESC");
}
if (file_exists("../kml/GoogleEarth.php")) {
	$qro->AddTitle(gettext("Geo Tools")." <a href='' onclick='window.open(\"../kml/TourConfig.php?type=$addr_type_name&ip=$currentIP\",\"IP $currentIP ".(($addr_type == 2) ? _("sources") : _("destinations"))." - Goggle Earth API\",\"width=1024,height=700,scrollbars=NO,toolbar=1\");return false'><img title='"._("Geolocation Tour")."' align='absmiddle' src='../pixmaps/google_earth_icon.png' border='0'></a>&nbsp;&nbsp;<a href='' onclick='window.open(\"../kml/IPGoogleMap.php?type=$addr_type_name&ip=$currentIP\",\"IP $currentIP ".(($addr_type == 2) ? _("sources") : _("destinations"))." - Goggle Maps API\",\"width=1024,height=700,scrollbars=NO,toolbar=1\");return false'><img title='"._("Geolocation Map")."' align='absmiddle' src='../pixmaps/google_maps_icon.png' border='0'></a>", "geotools");
}
if (!Session::am_i_admin()) $displaytitle = preg_replace("/\. <b>.*/",".",$displaytitle);
$sort_sql = $qro->GetSortSQL($qs->GetCurrentSort() , $qs->GetCurrentCannedQuerySort());

// Queries
if (Session::show_entities())
{
    $sql = "SELECT SQL_CALC_FOUND_ROWS $addr_type_name, hex(ctx) as ctx, $nevents as num_events, $uevent as num_sig, ";
    if ($addr_type == DEST_IP) $sql = $sql . " COUNT( DISTINCT ip_src ) as num_sip ";
    else                       $sql = $sql . " COUNT( DISTINCT ip_dst ) as num_dip ";
    $sql .= ", $addr_type_id, hex(ctx) as id";
    $sql = $sql . $sort_sql[0] . $from . $where . " GROUP BY $addr_type_name, ctx HAVING num_events>0 " . $sort_sql[1];
}
else
{
    $sql = "SELECT SQL_CALC_FOUND_ROWS $addr_type_name, device_id, $nevents as num_events, $uevent as num_sig, ";
    if ($addr_type == DEST_IP) $sql = $sql . " COUNT( DISTINCT ip_src ) as num_sip ";
    else                       $sql = $sql . " COUNT( DISTINCT ip_dst ) as num_dip ";
    $from  .= ', device ';
    $where .= ' AND device.id=acid_event.device_id';
    $sql .= ", $addr_type_id, hex(sensor_id) as id";
    $sql = $sql . $sort_sql[0] . $from . $where . " GROUP BY $addr_type_name, device.sensor_id HAVING num_events>0 " . $sort_sql[1];
}

// Save WHERE in session for Mapping 
$_SESSION['_siem_mapping_from']  = $from;
$_SESSION['_siem_mapping_where'] = preg_replace("/\s+WHERE\s+1/","",$where);

if (file_exists('/tmp/debug_siem'))
{
    file_put_contents("/tmp/siem", "STATS IP:$sql\n", FILE_APPEND);
}
/* Run the Query again for the actual data (with the LIMIT) */
session_write_close();
$result = $qs->ExecuteOutputQuery($sql, $db);
//$qs->GetNumResultRows($cnt_sql, $db);
$event_cnt = $qs->GetCalcFoundRows($cnt_sql, $result->baseRecordCount(), $db);
if ($event_cnt == 0) $event_cnt = 1;

$et->Mark("Retrieve Query Data");
// if ($debug_mode == 1) {
    // $qs->PrintCannedQueryList();
    // $qs->DumpState();
    // echo "$sql<BR>";
// }
/* Print the current view number and # of rows */
$qs->PrintResultCnt("",array(),$displaytitle);
echo '<FORM METHOD="post" name="PacketForm" id="PacketForm" ACTION="base_stat_uaddr.php">';
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
    $ctx = $myrow[1]; // ctx OR device_id
    list($prio,$rel,$act) = $Reputation->get_data_by_ip($currentIP);
    $num_events = $myrow[2];
    $num_sig = $myrow[3];
    $num_ip = $myrow[4];
    $host_id = $myrow[5];
    $id = $myrow[6];
    if ($myrow[0] == NULL) $no_ip = true;
    else $no_ip = false;
    qroPrintEntryHeader($i);
    /* Generating checkbox value -- nikns */
    ($addr_type == SOURCE_IP) ? ($src_ip = $currentIP) : ($dst_ip = $currentIP);
    $tmp_rowid = $src_ip . "_" . $dst_ip. "_" . $ctx;
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
        $geo_info = Asset_host::get_extended_location($_conn, $geoloc , $currentIP);
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
        
        $div = '<div id="'.$currentIP.';'.$currentIP.';'.$host_id.'" ctx="'.((Session::show_entities()) ? $ctx : Session::get_default_ctx()).'" class="HostReportMenu" style="padding:0px 0px 0px 25px">'; //'.getrepbgcolor($prio,1).'
		$bdiv = '</div>';        
        qroPrintEntry($div . $country_img . "&nbsp;" . BuildAddressLink($currentIP, 32) . $currentIP . '</A>&nbsp;' . $bdiv, 'left','','nowrap');
        qroPrintEntry(getrepimg($prio,$rel,$act,$currentIP), "center", "middle");
    }
    if ($resolve_IP == 1) qroPrintEntry('&nbsp;&nbsp;' . baseGetHostByAddr($currentIP, $ctx, $db) . '&nbsp;&nbsp;');
    /* Print # of Occurances */
    $tmp_iplookup = 'base_qry_main.php?num_result_rows=-1' . '&amp;submit=' . gettext("Query DB") . '&amp;current_view=-1';
    $tmp_iplookup2 = 'base_stat_alerts.php?num_result_rows=-1' . '&amp;submit=' . gettext("Query DB") . '&amp;current_view=-1&sort_order=occur_d';
    if ($addr_type == 1) {
        if ($no_ip) $url_criteria = BuildSrcIPFormVars(NULL_IP);
        else $url_criteria = BuildSrcIPFormVars($currentIP);
    } else if ($addr_type == 2) {
        if ($no_ip) $url_criteria = BuildDstIpFormVars(NULL_IP);
        else $url_criteria = BuildDstIPFormVars($currentIP);
    }
    $sens = (Session::show_entities() && !empty($entities[$ctx])) ? $entities[$ctx] : ((Session::show_entities()) ? _("Unknown") : GetSensorName($ctx, $db));
    qroPrintEntry($sens, "center", "middle");
    qroPrintEntry('<A HREF="' . $tmp_iplookup . $url_criteria . '">' . Util::number_format_locale($num_events,0) . '</A>', "center", "middle");
    qroPrintEntry('<A HREF="' . $tmp_iplookup2 . $url_criteria . '">' . Util::number_format_locale($num_sig,0) . '</A>', "center", "middle");
    qroPrintEntry(Util::number_format_locale($num_ip,0), "center", "middle");
    
    if (file_exists("../kml/GoogleEarth.php") && $currentIP != "0.0.0.0" && $currentIP != "::")
    {
        	qroPrintEntry("<a href='' onclick='window.open(\"../kml/TourConfig.php?type=$addr_type_name&ip=$currentIP\",\"IP $currentIP ".(($addr_type == 2) ? _("sources") : _("destinations"))." - Goggle Earth API\",\"width=1024,height=700,scrollbars=NO,toolbar=1\");return false'><img align='absmiddle' title='"._("Geolocation Tour")."' src='../pixmaps/google_earth_icon.png' border='0'></a>&nbsp;&nbsp;<a href='' onclick='window.open(\"../kml/IPGoogleMap.php?type=$addr_type_name&ip=$currentIP\",\"IP $currentIP ".(($addr_type == 2) ? _("sources") : _("destinations"))." - Goggle Maps API\",\"width=1024,height=700,scrollbars=NO,toolbar=1\");return false'><img title='"._("Geolocation Map")."' align='absmiddle' src='../pixmaps/google_maps_icon.png' border='0'></a>");
    }
    else
    {
        qroPrintEntry('');
    }
    
    qroPrintEntryFooter();
    ++$i;
    
    // report_data
    $report_data[] = array (
        $currentIP, '',
        $num_sig, $num_ip,
        "", "", "", "", "", "", $sens,
        intval($_GET['addr_type']), 0 , $num_events, $country_img
    );
}
$result->baseFreeRows();
$dbo->close($_conn);
$qro->PrintFooter();
$qs->PrintBrowseButtons();
$qs->PrintAlertActionButtons();
$qs->SaveReportData($report_data,$unique_addr_report_type);
$qs->SaveState();
ExportHTTPVar("addr_type", $addr_type);
echo "\n</FORM>\n";
$et->Mark("Get Query Elements");
$et->PrintTiming();
PrintBASESubFooter();
$db->baseClose();
echo "</body>\r\n</html>";
