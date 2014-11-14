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
include_once ("$BASE_path/base_stat_common.php");
include_once ("$BASE_path/base_qry_common.php");
include_once ("$BASE_path/base_ag_common.php");
include_once ("geoip.inc");

$_SESSION["siem_default_group"] = "base_stat_sensor.php?sort_order=occur_d";

$geoloc = new Geolocation("/usr/share/geoip/GeoLiteCity.dat");

$et = new EventTiming($debug_time_mode);
$cs = new CriteriaState("base_stat_sensor.php");
$cs->ReadState();
$qs = new QueryState();
// Check role out and redirect if needed -- Kevin
$roleneeded = 10000;
#$BUser = new BaseUser();
#if (($BUser->hasRole($roleneeded) == 0) && ($Use_Auth_System == 1)) base_header("Location: " . $BASE_urlpath . "/index.php");
$submit = ImportHTTPVar("submit", VAR_ALPHA | VAR_SPACE, array(
    gettext("Delete Selected"),
    gettext("Delete ALL on Screen"),
    _ENTIREQUERY
));
$complete = intval(ImportHTTPVar("complete", VAR_DIGIT));
$qs->MoveView($submit); /* increment the view if necessary */
$page_title = gettext("Sensor Listing");

/* Connect to the Alert database */
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);

if ($event_cache_auto_update == 1) UpdateAlertCache($db);
$criteria_clauses = ProcessCriteria();

// Include base_header.php
PrintBASESubHeader($page_title, $page_title, $cs->GetBackLink() , 1);

$mssp = Session::show_entities(); //intval($conf->get_conf("alienvault_mssp", FALSE));

// Use accumulate tables only when timestamp criteria is not hour sensitive
$use_ac = can_use_accumulated_table();

if ($use_ac) { // use ac_acid_event
    $from    = " FROM ac_acid_event as acid_event " . $criteria_clauses[0].", device LEFT JOIN alienvault.sensor ON sensor.id=device.sensor_id";
    $where   = ($criteria_clauses[4] != "") ? " WHERE " . $criteria_clauses[4] : " ";
    $where2  = ($criteria_clauses[5] != "") ? " WHERE " . $criteria_clauses[5] : " ";
	$counter = "sum(acid_event.cnt) as event_cnt";
    $from1   = " FROM acid_event " . $criteria_clauses[0].", device LEFT JOIN alienvault.sensor ON sensor.id=device.sensor_id";
    $where1  = ($criteria_clauses[1] != "") ? " WHERE " . $criteria_clauses[1] : " ";	
} else {
    $from = $from1 = " FROM acid_event " . $criteria_clauses[0].", device LEFT JOIN alienvault.sensor ON sensor.id=device.sensor_id";
    $where = $where1 = $where2 = ($criteria_clauses[1] != "") ? " WHERE " . $criteria_clauses[1] : " ";
	$counter = "count(acid_event.id) as event_cnt";
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
$qs->AddValidAction("del_alert");
//$qs->AddValidAction("email_alert");
//$qs->AddValidAction("email_alert2");
//$qs->AddValidAction("csv_alert");
//$qs->AddValidAction("archive_alert");
//$qs->AddValidAction("archive_alert2");
$qs->AddValidActionOp(gettext("Delete Selected"));
$qs->AddValidActionOp(gettext("Delete ALL on Screen"));
$qs->SetActionSQL($from1 . $where1);
$et->Mark("Initialization");
$qs->RunAction($submit, PAGE_STAT_SENSOR, $db);
$et->Mark("Alert Action");
/* create SQL to get Unique Alerts */
$cnt_sql = "SELECT count(DISTINCT acid_event.device_id) " . $from . $where;
/* Run the query to determine the number of rows (No LIMIT)*/

if (!$use_ac) $qs->GetNumResultRows($cnt_sql, $db);
$et->Mark("Counting Result size");
/* Setup the Query Results Table */
$qro = new QueryResultsOutput("base_stat_sensor.php?caller=" . $caller);
$qro->AddTitle(" ");
$qro->AddTitle(gettext("Sensor"), "sid_a", " ", " ORDER BY acid_event.device_id ASC", "sid_d", " ", " ORDER BY acid_event.device_id DESC");
$qro->AddTitle(gettext("Name"), "", " ", " ", "", " ", " ");
$qro->AddTitle(gettext("Device IP"), "", " ", " ", "", " ", " ");
$events_title = (!$use_ac) ? _("Events"). "&nbsp;# <span class='idminfo' txt='".Util::timezone($tz)."'>(*)</span>" : _("Events")."&nbsp;# <span class='idminfo' txt='"._("Time UTC")."'>(*)</span>";
$qro->AddTitle($events_title, "occur_a", " ", "  ORDER BY event_cnt ASC", "occur_d", " ", "  ORDER BY event_cnt DESC");
$qro->AddTitle(gettext("Unique Events") , "", "", "", "", "", "");
$qro->AddTitle(gettext("Unique Src.") , "", "", "", "", "", "");
$qro->AddTitle(gettext("Unique Dst.") , "", "", "", "", "", "");
/*
$qro->AddTitle(gettext("Unique Events"), "sig_a", "", " ORDER BY sig_cnt ASC", "sig_d", "", " ORDER BY sig_cnt DESC");
$qro->AddTitle(gettext("Unique Src."), "saddr_a", "", " ORDER BY saddr_cnt ASC", "saddr_d", "", " ORDER BY saddr_cnt DESC");
$qro->AddTitle(gettext("Unique Dst."), "daddr_a", "", " ORDER BY daddr_cnt ASC", "daddr_d", "", " ORDER BY daddr_cnt DESC");
*/
$sort_sql = $qro->GetSortSQL($qs->GetCurrentSort() , "");

if ($complete) { // incude all fields for pdf/csv reports
	$sql2 = $sql = "SELECT acid_event.device_id, HEX(device.sensor_id) AS sensor_id, ifnull(sensor.name,'Unknown') AS name, inet6_ntop(sensor.ip) AS sensor_ip, inet6_ntop(device.device_ip) AS device_ip, device.interface, count(acid_event.id) as event_cnt, count(distinct acid_event.plugin_id, acid_event.plugin_sid) as sig_cnt, count(distinct(acid_event.ip_src)) as saddr_cnt, count(distinct(acid_event.ip_dst)) as daddr_cnt" . $sort_sql[0] . $from1 . $where1 . " AND device.id=acid_event.device_id GROUP BY acid_event.device_id" . $sort_sql[1];
} else {
	$sql  = "SELECT acid_event.device_id, HEX(device.sensor_id) AS sensor_id, ifnull(sensor.name,'Unknown') AS name, inet6_ntop(sensor.ip) AS sensor_ip, inet6_ntop(device.device_ip) AS device_ip, device.interface, $counter " . $sort_sql[0] . $from . $where . " AND device.id=acid_event.device_id GROUP BY acid_event.device_id HAVING event_cnt>0 " . $sort_sql[1];
	$sql2 = "SELECT acid_event.device_id, HEX(device.sensor_id) AS sensor_id, ifnull(sensor.name,'Unknown') AS name, inet6_ntop(sensor.ip) AS sensor_ip, inet6_ntop(device.device_ip) AS device_ip, device.interface, $counter " . $sort_sql[0] . $from . $where2 . " AND device.id=acid_event.device_id GROUP BY acid_event.device_id HAVING event_cnt>0 " . $sort_sql[1];
}

$_SESSION['siem_sensor_query'] = "SELECT count(distinct acid_event.plugin_id, acid_event.plugin_sid) as sig_cnt, count(distinct(acid_event.ip_src)) as saddr_cnt, count(distinct(acid_event.ip_dst)) as daddr_cnt" . $sort_sql[0] . $from1 . $where1 . " AND acid_event.device_id=DEVICEID";

//echo $sql."<br>";
/* Run the Query again for the actual data (with the LIMIT) */
$result = $qs->ExecuteOutputQuery($sql, $db);
if ($result->baseRecordCount()==0 && $use_ac) { $result = $qs->ExecuteOutputQuery($sql2, $db); }
$qs->num_result_rows = $result->baseRecordCount();

$et->Mark("Retrieve Query Data");
// if ($debug_mode == 1) {
    // $qs->PrintCannedQueryList();
    // $qs->DumpState();
    // echo "$sql<BR>";
// }
/* Print the current view number and # of rows */
$displaying = gettext("Displaying sensors %d-%d of <b>%s</b> matching your selection.");
$qs->PrintResultCnt("",array(),$displaying);
echo '<FORM METHOD="post" NAME="PacketForm" id="PacketForm" ACTION="base_stat_sensor.php">';
if ($qs->num_result_rows > 0)
{
    $qro->PrintHeader();
}
$i = 0;
$sensorips = GetSensorSidsNames($db);
$report_data = array(); // data to fill report_data 

if (is_array($_SESSION["server"]) && $_SESSION["server"][0]!="")
	$_conn = $dbo->custom_connect($_SESSION["server"][0],$_SESSION["server"][2],$_SESSION["server"][3]);
else
	$_conn = $dbo->connect();
	
while (($myrow = $result->baseFetchRow()) && ($i < $qs->GetDisplayRowCnt())) {
    $device_id = $myrow['device_id'];
    $sensor_ip = ($myrow['name'] == _('Unknown')) ? 'N/A' : $myrow['sensor_ip'];
    $device_ip = ($myrow['device_ip'] != '') ? $myrow['device_ip'].($myrow['interface'] != '' ? ':'.$myrow['interface'] : '') : '-';
    $sname     = $myrow['name'];
    
    $event_cnt = $myrow['event_cnt'];
    $unique_event_cnt = ($myrow['sig_cnt']!="") ? $myrow['sig_cnt'] : "-";
    $num_src_ip = ($myrow['saddr_cnt']!="") ? $myrow['saddr_cnt'] : "-";
    $num_dst_ip = ($myrow['daddr_cnt']!="") ? $myrow['daddr_cnt'] : "-";
	
	$_country_aux = $geoloc->get_country_by_host($conn, $sensor_ip);
	$country      = strtolower($_country_aux[0]);
	$country_name = $_country_aux[1];
	
	$homelan = "";
	if ($country) {
		$country_img = " <img src=\"/ossim/pixmaps/flags/" . $country . ".png\" alt=\"$country_name\" title=\"$country_name\">";
        $slnk = $current_url."/pixmaps/flags/".$country.".png";
	} else {
		$country_img = "";
		$slnk = "";
	}
    /* Print out */
    qroPrintEntryHeader($i);
    $tmp_rowid = $device_id;
    echo '    <TD><INPUT TYPE="checkbox" NAME="action_chk_lst[' . $i . ']" VALUE="' . $tmp_rowid . '">';
    echo '        <INPUT TYPE="hidden" NAME="action_lst[' . $i . ']" VALUE="' . $tmp_rowid . '"></TD>';
    qroPrintEntry($sensor_ip);
    qroPrintEntry($sname.$country_img.$homelan);
    qroPrintEntry($device_ip);
    qroPrintEntry('<A HREF="base_qry_main.php?new=1&amp;sensor=' . $device_id . '&amp;num_result_rows=-1&amp;submit=' . gettext("Query DB") . '">' . $event_cnt . '</A>');
    
    qroPrintEntry( '<div id="ua'.$device_id.'" class="sens">'.$unique_event_cnt.'</div>', 'center', 'middle', 'nowrap');
    qroPrintEntry( '<div id="sa'.$device_id.'">'.$num_src_ip.'</div>', 'center', 'middle', 'nowrap');    
    qroPrintEntry( '<div id="da'.$device_id.'">'.$num_dst_ip.'</div>', 'center', 'middle', 'nowrap');    
    
    /*qroPrintEntry(BuildUniqueAlertLink("?sensor=" . $device_id) . $unique_event_cnt . '</A>');
    qroPrintEntry(BuildUniqueAddressLink(1, "&amp;sensor=" . $device_id) . $num_src_ip . '</A>');
    qroPrintEntry(BuildUniqueAddressLink(2, "&amp;sensor=" . $device_id) . $num_dst_ip . '</A>');*/
    qroPrintEntryFooter();
    $i++;
    
    // report_data
    $report_data[] = array (
        $sname, $slnk,
        $num_src_ip, $num_dst_ip, "", "",
        $sensor_ip, "", "", "", "", 0, 
        $event_cnt, $unique_event_cnt
    );
}
$result->baseFreeRows();
$dbo->close($_conn);
$qro->PrintFooter();
$qs->PrintBrowseButtons();
$qs->PrintAlertActionButtons();
$qs->SaveReportData($report_data,$sensors_report_type);
$qs->SaveState();
echo "\n</FORM>\n";
PrintBASESubFooter();
$et->Mark("Get Query Elements");
$et->PrintTiming();
$db->baseClose();
if (!$complete) {
?>
<script>
	var tmpimg = '<img alt="" src="data:image/gif;base64,R0lGODlhEAALAPQAAOPj4wAAAMLCwrm5udHR0QUFBQAAACkpKXR0dFVVVaamph4eHkJCQnt7e1lZWampqSEhIQMDA0VFRc3NzcHBwdra2jIyMsTExNjY2KKioo6OjrS0tNTU1AAAAAAAAAAAACH/C05FVFNDQVBFMi4wAwEAAAAh/hpDcmVhdGVkIHdpdGggYWpheGxvYWQuaW5mbwAh+QQJCwAAACwAAAAAEAALAAAFLSAgjmRpnqSgCuLKAq5AEIM4zDVw03ve27ifDgfkEYe04kDIDC5zrtYKRa2WQgAh+QQJCwAAACwAAAAAEAALAAAFJGBhGAVgnqhpHIeRvsDawqns0qeN5+y967tYLyicBYE7EYkYAgAh+QQJCwAAACwAAAAAEAALAAAFNiAgjothLOOIJAkiGgxjpGKiKMkbz7SN6zIawJcDwIK9W/HISxGBzdHTuBNOmcJVCyoUlk7CEAAh+QQJCwAAACwAAAAAEAALAAAFNSAgjqQIRRFUAo3jNGIkSdHqPI8Tz3V55zuaDacDyIQ+YrBH+hWPzJFzOQQaeavWi7oqnVIhACH5BAkLAAAALAAAAAAQAAsAAAUyICCOZGme1rJY5kRRk7hI0mJSVUXJtF3iOl7tltsBZsNfUegjAY3I5sgFY55KqdX1GgIAIfkECQsAAAAsAAAAABAACwAABTcgII5kaZ4kcV2EqLJipmnZhWGXaOOitm2aXQ4g7P2Ct2ER4AMul00kj5g0Al8tADY2y6C+4FIIACH5BAkLAAAALAAAAAAQAAsAAAUvICCOZGme5ERRk6iy7qpyHCVStA3gNa/7txxwlwv2isSacYUc+l4tADQGQ1mvpBAAIfkECQsAAAAsAAAAABAACwAABS8gII5kaZ7kRFGTqLLuqnIcJVK0DeA1r/u3HHCXC/aKxJpxhRz6Xi0ANAZDWa+kEAA7AAAAAAAAAAAA" />';
    var sens=new Array();
    var pi = 0;
    function load_content() {
        if (pi>=sens.length) return;
        var item = sens[pi]; pi++;
        var params = item.replace(/ua/,'?id=');
        var pid = item.replace(/ua/,'');
        $.ajax({
            beforeSend: function() {
                $('#ua'+pid).html(tmpimg);
				$('#sa'+pid).html(tmpimg);
				$('#da'+pid).html(tmpimg);  
            },
    		type: "GET",
    		url: "base_stat_sensor_data.php"+params,
    		success: function(msg) {
    			var res = msg.split(/##/);
    			$('#ua'+pid).html(res[0]);
    			$('#sa'+pid).html(res[1]);
    			$('#da'+pid).html(res[2]);
    			setTimeout('load_content()',10);
    		}
    	});
    } 
    $(document).ready(function() {
        $('.sens').each(function(index, item) {
            sens.push(item.id);
        });
        setTimeout('load_content()',10);
    });
</script>
<?php
}
echo "</body>\r\n</html>";
?>
