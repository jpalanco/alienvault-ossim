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

$_SESSION["siem_default_group"] = "base_stat_otx.php?sort_order=occur_d";
if ($_REQUEST['sort_order']=='') $_GET['sort_order']='occur_d';

// Geoip
$geoloc = new Geolocation("/usr/share/geoip/GeoLiteCity.dat");

//$addr_type = ImportHTTPVar("addr_type", VAR_DIGIT);
$addr_type = 1;
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
$cs = new CriteriaState("base_stat_otx.php", "");
$cs->ReadState();
/* Dump some debugging information on the shared state */
// if ($debug_mode > 0) {
    // PrintCriteriaState();
// }
$qs = new QueryState();

if ($event_cache_auto_update == 1) UpdateAlertCache($db);
$criteria_clauses = ProcessCriteria();

// Include base_header.php
if ($qs->isCannedQuery()) PrintBASESubHeader($page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $cs->GetBackLink() , 1);
else PrintBASESubHeader($page_title, $page_title, $cs->GetBackLink() , 1);

$criteria = $criteria_clauses[0] . " " . $criteria_clauses[1];

if (preg_match("/otx_data/",$criteria)) 
{
    $where  = " WHERE " . $criteria_clauses[1];
    $from   = " FROM acid_event " . $criteria_clauses[0];
}
else
{
    $where  = " WHERE " . $criteria_clauses[1] . " AND acid_event.id=otx_data.event_id";
    $from   = " FROM acid_event " . $criteria_clauses[0]. ", otx_data";
}

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
/* Setup the Query Results Table */
$qro = new QueryResultsOutput("base_stat_otx.php?caller=" . $caller);

$qro->AddTitle(_('OTX Pulse'));
$events_title = _("Events"). "&nbsp;# <span class='idminfo' txt='".Util::timezone(Util::get_timezone())."'>(*)</span>";
$qro->AddTitle("<span id='total_title'>$events_title</span>", "occur_a", " ", " ORDER BY num_events ASC, num_iocs ASC", "occur_d", " ", " ORDER BY num_events DESC, num_iocs DESC");
$qro->AddTitle(_("Indicators&nbsp;#") , "ioc_a", " ", " ORDER BY num_iocs ASC", "ioc_d", " ", " ORDER BY num_iocs DESC");
$qro->AddTitle(' ');

$sort_sql = $qro->GetSortSQL($qs->GetCurrentSort() , $qs->GetCurrentCannedQuerySort());

$sql = "SELECT SQL_CALC_FOUND_ROWS hex(otx_data.pulse_id) as pulse, COUNT(distinct otx_data.event_id) as num_events, COUNT(distinct otx_data.ioc_hash) as num_iocs ". $sort_sql[0] . $from . $where . " GROUP BY pulse_id " . $sort_sql[1];

// use accumulate tables only with timestamp criteria
if (file_exists('/tmp/debug_siem'))
{
    file_put_contents("/tmp/siem", "STATS OTX:$sql\n", FILE_APPEND);
}

/* Run the Query again for the actual data (with the LIMIT) */
session_write_close();
$result = $qs->ExecuteOutputQuery($sql, $db);
$event_cnt = $qs->GetCalcFoundRows("SELECT count(DISTINCT pulse_id) " . $from . $where . " GROUP BY pulse_id", $result->baseRecordCount(), $db);

$et->Mark("Retrieve Query Data");

$report_data = array(); // data to fill report_data

if (is_array($_SESSION["server"]) && $_SESSION["server"][0]!="")
{
    $_conn = $dbo->custom_connect($_SESSION["server"][0],$_SESSION["server"][2],$_SESSION["server"][3]);
}
else
{
    $_conn = $dbo->connect();
}

$event_pulses = array();
$pulses = GetPulses();

while (($myrow = $result->baseFetchRow()))
{
    $pulse_id   = $myrow[0];
    $num_events = $myrow[1];
    $num_iocs   = $myrow[2];

    $pulse_name = (array_key_exists($pulse_id, $pulses)) ? $pulses[$pulse_id]['name'] : $otx_unknown;;

    $event_pulses[$pulse_id] = array("name" => $pulse_name, "data" => $pulses[$pulse_id], "events" => $num_events, "iocs" => $num_iocs);
}

// Not found
if (count($event_pulses) == 0)
{
    echo "<tr><td><table class='transparent' style='width:100%'><tr><td colspan='5' style='padding:6px'><b>"._("No OTX Pulses were found in the SIEM events")."</b></td></tr></table></td></tr>\n";
}
// Results
else
{
    /* Print the current view number and # of rows */
    $displaying = gettext("Displaying OTX Pulses %d-%d of <b>%s</b> matching your selection.");
    $qs->PrintResultCnt("",array(),$displaying);
    echo '<FORM METHOD="post" NAME="PacketForm" id="PacketForm" ACTION="base_stat_otx.php">';
    $qro->PrintHeader();

    $max_cnt = 1;
    $i = 0;
    foreach ($event_pulses as $pulse_id => $otx_data)
    { 
        if ($max_cnt == 1 && $otx_data['events'] > 0) $max_cnt = $otx_data['events'];
        $entry_width = round($otx_data['events'] / $max_cnt * 100);
        $otx_link = str_replace('__PULSEID__',urlencode(strtolower($pulse_id)),$otx_pulse_url);
        $link = "base_qry_main.php?new=1&submit=" . gettext("Query DB") . "&num_result_rows=-1&otx%5B0%5D=" . urlencode($pulse_id);
        ?>
        <tr>
            <td style="padding:7px;text-align:left;font-size:10px">
                <a class="pulse_link" href="<?=$otx_link?>" target="_blank"><?=$otx_data["name"]?></a>
            </td>
            <td align="center">
                <a href="<?=$link?>"><?=Util::number_format_locale($otx_data['events'],0)?></a>
            </td>
            <td align="center"><?=Util::number_format_locale($otx_data['iocs'],0)?></td>
            <TD width="30%"><TABLE class="transparent bar" cellpadding="0" cellspacing="0" WIDTH="100%">
              <TR>
               <TD style="background-color:#84C973;width:<?php echo ($entry_width > 0) ? $entry_width."%" : "1px" ?>"><img src="../pixmaps/1x1.png"/></TD>
               <TD>&nbsp;</TD>
              </TR>
             </TABLE>
            </TD>
        </tr>
        <?
        $i++;
        
        /* report_data
        $report_data[] = array (
            $country, '',
            "$entry_width", "", "", "", "", "", "", "", "",
            $data['events'], $data['srcnum'], $data['dstnum'], $data['flagr']
        );*/
    }
    echo '</TABLE>';
}

$result->baseFreeRows();
$dbo->close($_conn);
$qro->PrintFooter();
$qs->PrintBrowseButtons();
$qs->PrintAlertActionButtons();
//$qs->SaveReportData($report_data,$unique_country_events_report_type);
$qs->SaveState();
echo "\n</FORM>\n";
ExportHTTPVar("addr_type", $addr_type);
PrintBASESubFooter();
$et->Mark("Get Query Elements");
$et->PrintTiming();
$db->baseClose();
echo "</body>\r\n</html>";
$geoloc->close();
