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
set_time_limit(300);

if (GET('sensor') != "") ossim_valid(GET('sensor'), OSS_DIGIT, 'illegal:' . _("sensor"));;

$_SESSION["siem_default_group"] = "base_stat_country.php";

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
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);
$cs = new CriteriaState("base_stat_country.php", "&amp;addr_type=1");
$cs->ReadState();
/* Dump some debugging information on the shared state */
// if ($debug_mode > 0) {
    // PrintCriteriaState();
// }
$qs = new QueryState();
$qs->AddCannedQuery("most_frequent", $freq_num_uaddr, gettext("Most Frequent IP addresses"), "occur_d");
$qs->MoveView($submit); /* increment the view if necessary */
if ($addr_type == SOURCE_IP) {
    $page_title = gettext("Unique Source Address(es)");
    $results_title = gettext("Src IP address");
    $addr_type_name = "ip_src";
} else {
    if ($addr_type != DEST_IP) ErrorMessage(gettext("CRITERIA ERROR: unknown address type -- assuming Dst address"));
    $page_title = gettext("Unique Destination Address(es)");
    $results_title = gettext("Dst IP address");
    $addr_type_name = "ip_dst";
}

if ($event_cache_auto_update == 1) UpdateAlertCache($db);
$criteria_clauses = ProcessCriteria();

// Include base_header.php
if ($qs->isCannedQuery()) PrintBASESubHeader($page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $cs->GetBackLink() , 1);
else PrintBASESubHeader($page_title, $page_title, $cs->GetBackLink() , 1);

$criteria = $criteria_clauses[0] . " " . $criteria_clauses[1];
$from = " FROM acid_event " . $criteria_clauses[0];
$where = " WHERE " . $criteria_clauses[1];
// use accumulate tables only with timestamp criteria
//$use_ac = (preg_match("/AND/", preg_replace("/AND \( timestamp/", "", $criteria_clauses[1]))) ? false : true;
//if (preg_match("/ \d\d:\d\d:\d\d/",$criteria_clauses[1])) $use_ac = false;
$use_ac = false;
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
/* Setup the Query Results Table */
$qro = new QueryResultsOutput("base_stat_uaddr.php?caller=" . $caller . "&amp;addr_type=" . $addr_type);
$qro->AddTitle(" ");
$sort_sql = $qro->GetSortSQL($qs->GetCurrentSort() , $qs->GetCurrentCannedQuerySort());
$sql = "(SELECT DISTINCT ip_src, 'S', COUNT(acid_event.id) as num_events ". $sort_sql[0] . $from . $where . " GROUP BY ip_src HAVING num_events>0 " . $sort_sql[1]. ") UNION (SELECT DISTINCT ip_dst, 'D', COUNT(acid_event.id) as num_events ". $sort_sql[0] . $from . $where . " GROUP BY ip_dst HAVING num_events>0 " . $sort_sql[1]. ")";
// use accumulate tables only with timestamp criteria
//echo $sql;
//print_r($_SESSION);
/* Run the Query again for the actual data (with the LIMIT) */
$result = $qs->ExecuteOutputQueryNoCanned($sql, $db);
//if ($use_ac) $qs->GetCalcFoundRows($cnt_sql, $db);
$et->Mark("Retrieve Query Data");
// if ($debug_mode == 1) {
    // $qs->PrintCannedQueryList();
    // $qs->DumpState();
    // echo "$sql<BR>";
// }
/* Print the current view number and # of rows */
//$qs->PrintResultCnt();

$country_acc = array();
$country_uhn = array();
$countries   = array(); // Ordered
$report_data = array(); // data to fill report_data

if (is_array($_SESSION["server"]) && $_SESSION["server"][0]!="")
{
	$_conn = $dbo->custom_connect($_SESSION["server"][0],$_SESSION["server"][2],$_SESSION["server"][3]);
}
else
{
	$_conn = $dbo->connect();
}

while (($myrow = $result->baseFetchRow()))
{
	if ($myrow[0] == NULL) continue;
    $currentIP = inet_ntop($myrow[0]);
    $ip_type = $myrow[1];
    $num_events = $myrow[2];
    $field = ($ip_type=='S') ? 'srcnum' : 'dstnum';
    
    $_country_aux = $geoloc->get_country_by_host($_conn, $currentIP);
    $country      = strtolower($_country_aux[0]);
    $country_name = $_country_aux[1];

	if ($country_name == "") $country_name = _("Unknown Country");
	//echo "IP $currentIP $country_name <br>";
	if ($country_name!=_("Unknown Country")) {
		$countries[$country_name] += $num_events;
		$country_acc[$country_name][$field]++;
		$country_acc[$country_name]['events'] += $num_events;
		$country_acc[$country_name]['flag'] = ($country_name != _("Unknown Country")) ? (($country=="local") ? "<img src=\"images/homelan.png\" border=0 title=\"$country_name\">" : " <img src=\"/ossim/pixmaps/flags/" . $country . ".png\" title=\"$country_name\">") : "";
		$country_acc[$country_name]['flagr'] = ($country_name != _("Unknown Country")) ? (($country=="local") ? $current_url."/forensics/images/homelan.png" : $current_url."/pixmaps/flags/".$country.".png") : "";
		$country_acc[$country_name]['code'] = $country;
	} else {
		$country_uhn['Unknown'] += $num_events;
		$country_uhn[$field]++;
	}
	// 
}

arsort($countries);

// Not found
if (count($countries) == 0)
{
    echo "<tr><td><table class='transparent' style='width:100%'><tr><td colspan='5' style='padding:6px'><b>"._("No external IP addresses were found in the SIEM events")."</b></td></tr></table></td></tr>\n";
}
// Results
else
{
echo '<br/><TABLE class="table_list">';
echo      '<tr><th style="text-align:left" width="25%">Country</th>
               <th width="15%">' . gettext("Total #") . '</th>
               <th width="10%">' . gettext("Unique Src #") . '</th>
               <th width="10%">' . gettext("Unique Dst #") . '</th>
			   <th>Events</th></TR>';
 
$max_cnt = 1;
$i = 0;
foreach ($countries as $country=>$num) { 
	if ($max_cnt == 1 && $num > 0) $max_cnt = $num;
	$data = $country_acc[$country];
	if ($data['srcnum']+$data['dstnum'] == 0) $entry_width = 0;
    else $entry_width = round($data['events'] / $max_cnt * 100);
	if ($data['code']=="") $data['code']="unknown";
	?>
	<tr>
		<td style="padding:7px;text-align:left"><?=$data['flag']." ".$country?></td>
		<td align="center"><a href="base_stat_country_alerts.php?cc=<?=$data['code']?>&location=alerts"><?=Util::number_format_locale($data['events'],0)?></a></td>
		<td align="center">
			<? if ($data['srcnum']>0) { ?><a href="base_stat_country_alerts.php?cc=<?=$data['code']?>&location=srcaddress"><?=Util::number_format_locale($data['srcnum'],0)?></a></td>
			<? } else echo "0" ?>
		<td align="center">
			<? if ($data['dstnum']>0) { ?><a href="base_stat_country_alerts.php?cc=<?=$data['code']?>&location=dstaddress"><?=Util::number_format_locale($data['dstnum'],0)?></a>
			<? } else echo "0" ?>
			</td>
		<TD><TABLE class="transparent" cellpadding="0" cellspacing="0" WIDTH="100%">
		  <TR>
		   <TD style="background-color:#84C973;height:14px;width:<?php echo ($entry_width > 0) ? $entry_width."%" : "1px" ?>"><img src="../pixmaps/1x1.png"/></TD>
		   <TD>&nbsp;</TD>
		  </TR>
		 </TABLE>
		</TD>
	</tr>
	<?
	$i++;
    
    // report_data
    $report_data[] = array (
        $country, '',
        "$entry_width", "", "", "", "", "", "", "", "",
        $data['events'], $data['srcnum'], $data['dstnum'], $data['flagr']
    );
}

if ($country_uhn['Unknown']>0 && count($countries)>0) {
	$country = _("Unresolved Country or Local IPs");
?>
	<tr>
		<td style="padding:7px;text-align:left"><?=$country?></td>
		<td align="center"><?=Util::number_format_locale($country_uhn['Unknown'],0)?></td>
		<td align="center">
			<? if ($country_uhn['srcnum']>0) { ?><?=Util::number_format_locale($country_uhn['srcnum'],0)?>
			<? } else echo "0" ?>
			</td>
		<td align="center">
			<? if ($country_uhn['dstnum']>0) { ?><?=Util::number_format_locale($country_uhn['dstnum'],0)?>
			<? } else echo "0" ?>
			</td>
		<td></td>
		  </TR>
		 </TABLE>
		</TD>
	</tr>
<?
    $report_data[] = array (
        $country, "", "", "", "", "", "", "", "", "", "",
        $country_uhn['Unknown'], $country_uhn['srcnum'], $country_uhn['dstnum'], ""
    );
}

echo '</TABLE>';

}

$result->baseFreeRows();
$dbo->close($_conn);
$qro->PrintFooter();
//$qs->PrintBrowseButtons();
$qs->PrintAlertActionButtons();
$qs->SaveReportData($report_data,$unique_country_events_report_type);
$qs->SaveState();
ExportHTTPVar("addr_type", $addr_type);
PrintBASESubFooter();
$et->Mark("Get Query Elements");
$et->PrintTiming();
$db->baseClose();
echo "</body>\r\n</html>";
$geoloc->close();
?>
