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
require_once ('classes/Util.inc');

if((GET('proto')=='6' || GET('proto')=='17' || GET('proto')=='-1') && (GET('port_type')=='1' || GET('port_type')=='2'))
{
    $_SESSION["siem_default_group"] = "base_stat_ports.php?sort_order=occur_d&port_type=" . GET('port_type') . "&proto=" . GET('proto');
}

#$BUser = new BaseUser();
#if (($BUser->hasRole($roleneeded) == 0) && ($Use_Auth_System == 1)) base_header("Location: " . $BASE_urlpath . "/index.php");
$et = new EventTiming($debug_time_mode);
/* Connect to the Alert database */
$db = NewBASEDBConnection($DBlib_path, $DBtype);
/* FIXME: OSSIM */
/* This used to break the port filters, have to look deeply on this
maybe changing db_connect_method in base_conf.php */

$port_type = ImportHTTPVar("port_type", VAR_DIGIT);
$proto = ImportHTTPVar("proto", VAR_DIGIT | VAR_PUNC);
$export = intval(ImportHTTPVar("export", VAR_DIGIT)); // Called from report_launcher.php

$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password, 0, 1);
$cs = new CriteriaState("base_stat_ports.php", "&port_type=$port_type&proto=$proto");
$cs->ReadState();
// Check role out and redirect if needed -- Kevin
$roleneeded = 10000;
$port_proto = "TCP";
$qs = new QueryState();
$qs->AddCannedQuery("most_frequent", $freq_num_uports, gettext("Most Frequent Ports"), "occur_d");
$qs->AddCannedQuery("last_ports", $last_num_uports, gettext("Last Ports"), "last_d");
$submit = ImportHTTPVar("submit", VAR_ALPHA | VAR_SPACE, array(
    gettext("Delete Selected"),
    gettext("Delete ALL on Screen"),
    _ENTIREQUERY
));

$qs->MoveView($submit); /* increment the view if necessary */
$page_title = "";
switch ($proto) {
    case TCP:
        $page_title = gettext("Unique") . " TCP ";
        $displaytitle = ($port_type==SOURCE_PORT) ? gettext("Displaying source tcp ports %d-%d of <b>%s</b> matching your selection.") : gettext("Displaying destination tcp ports %d-%d of <b>%s</b> matching your selection.");
        break;

    case UDP:
        $page_title = gettext("Unique") . " UDP ";
        $displaytitle = ($port_type==SOURCE_PORT) ? gettext("Displaying source udp ports %d-%d of <b>%s</b> matching your selection.") : gettext("Displaying destination udp ports %d-%d of <b>%s</b> matching your selection.");
        break;

    case -1:
        $page_title = gettext("Unique") . " ";
        $displaytitle = ($port_type==SOURCE_PORT) ? gettext("Displaying source ports %d-%d of <b>%s</b> matching your selection.") : gettext("Displaying destination ports %d-%d of <b>%s</b> matching your selection.");
        break;
}
switch ($port_type) {
    case SOURCE_PORT:
        $page_title = $page_title . gettext("Source Port(s)");
        break;

    case DEST_PORT:
        $page_title = $page_title . gettext("Destination Port(s)");
        break;
}
if (!Session::am_i_admin()) $displaytitle = preg_replace("/\. <b>.*/",".",$displaytitle);

if ($event_cache_auto_update == 1) UpdateAlertCache($db);
$criteria_clauses = ProcessCriteria();

// Include base_header.php
if ($qs->isCannedQuery()) PrintBASESubHeader($page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $cs->GetBackLink() , 1);
else PrintBASESubHeader($page_title, $page_title, $cs->GetBackLink() , 1);

/* special case - erase ip_proto filter */
$criteria_clauses = preg_replace("/ AND acid_event.ip_proto= '\d+'/", "", $criteria_clauses);

$criteria = $criteria_clauses[0] . " " . $criteria_clauses[1];
// use accumulate tables only with timestamp criteria
//$use_ac = (preg_match("/AND/", preg_replace("/AND \( timestamp|AND acid_event\.ip_proto/", "", $criteria_clauses[1]))) ? false : true;
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
$qs->AddValidAction("del_alert");
//$qs->AddValidAction("email_alert");
//$qs->AddValidAction("email_alert2");
//$qs->AddValidAction("csv_alert");
//$qs->AddValidAction("archive_alert");
//$qs->AddValidAction("archive_alert2");
$qs->AddValidActionOp(gettext("Delete Selected"));
$qs->AddValidActionOp(gettext("Delete ALL on Screen"));
$et->Mark("Initialization");
$qs->RunAction($submit, PAGE_STAT_PORTS, $db);
$et->Mark("Alert Action");
switch ($proto) {
    case TCP:
        $proto_sql = " ip_proto = " . TCP;
        break;

    case UDP:
        $proto_sql = " ip_proto = " . UDP;
        break;

    default:
        $proto_sql = " ip_proto IN (" . TCP . ", " . UDP . ")";
        break;
}
if ($criteria_clauses[1] != "") $criteria_clauses[1] = $proto_sql . " AND " . $criteria_clauses[1];
else $criteria_clauses[1] = $proto_sql;
switch ($port_type) {
    case SOURCE_PORT:
        $port_type_sql = "layer4_sport";
        break;

    case DEST_PORT:
    default:
        $port_type_sql = "layer4_dport";
        break;
}
// Timezone
$tz = Util::get_timezone();

/* create SQL to get Unique Alerts */
$cnt_sql = "SELECT count(DISTINCT $port_type_sql) FROM acid_event " . $criteria_clauses[0] . " WHERE " . $criteria_clauses[1];
/* Run the query to determine the number of rows (No LIMIT)*/
$et->Mark("Counting Result size");
/* Setup the Query Results Table */
$qro = new QueryResultsOutput("base_stat_ports.php?caller=$caller" . "&amp;port_type=$port_type&amp;proto=$proto");
$qro->AddTitle(" ");
$qro->AddTitle(gettext("Port"), "port_a", " ", " ORDER BY $port_type_sql ASC", "port_d", " ", " ORDER BY $port_type_sql DESC");
//$qro->AddTitle(gettext("Sensor"), "sensor_a", " ", " ORDER BY num_sensors ASC", "sensor_d", " ", " ORDER BY num_sensors DESC");
$qro->AddTitle((Session::show_entities()) ? gettext("Context") : gettext("Sensor"));
$qro->AddTitle(gettext("Events") . "&nbsp;# <span class='idminfo' txt='".Util::timezone(Util::get_timezone())."'>(*)</span>", "occur_a", " ", " ORDER BY num_events ASC", "occur_d", " ", " ORDER BY num_events DESC");
$qro->AddTitle(gettext("Unique Events"), "alerts_a", " ", " ORDER BY num_sig ASC", "alerts_d", " ", " ORDER BY num_sig DESC");
$qro->AddTitle(gettext("Unique Src."));
$qro->AddTitle(gettext("Unique Dst."));
$sort_sql = $qro->GetSortSQL($qs->GetCurrentSort() , $qs->GetCurrentCannedQuerySort());
$where = " WHERE " . $criteria_clauses[1];

if (Session::show_entities())
{
    $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT $port_type_sql,  MIN(ip_proto), hex(ctx) as ctx, COUNT(acid_event.id) as num_events, COUNT( DISTINCT acid_event.plugin_id, acid_event.plugin_sid ) as num_sig " . $sort_sql[0] . " FROM acid_event " . $criteria_clauses[0] . $where . " GROUP BY " . $port_type_sql . ",ctx HAVING num_events>0 " . $sort_sql[1];
    $sqlports = "SELECT count(DISTINCT(ip_src)) as saddr_cnt, count(DISTINCT(ip_dst)) as daddr_cnt " . $sort_sql[0] . " FROM acid_event " . $criteria_clauses[0] . $where . " AND $port_type_sql=IP_PORT AND acid_event.ctx=UNHEX('DEVICEID')";
}
else
{
    $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT $port_type_sql,  MIN(ip_proto), device_id, COUNT(acid_event.id) as num_events, COUNT( DISTINCT acid_event.plugin_id, acid_event.plugin_sid ) as num_sig " . $sort_sql[0] . " FROM device,acid_event " . $criteria_clauses[0] . $where . " AND device.id=acid_event.device_id GROUP BY " . $port_type_sql . ",device_id HAVING num_events>0 " . $sort_sql[1];
    $sqlports = "SELECT count(DISTINCT(ip_src)) as saddr_cnt, count(DISTINCT(ip_dst)) as daddr_cnt " . $sort_sql[0] . " FROM acid_event " . $criteria_clauses[0] . $where . " AND $port_type_sql=IP_PORT AND acid_event.device_id=DEVICEID";
}

$_SESSION['_siem_port_query'] = $sqlports;

//echo "$sql<br>";
if (file_exists('/tmp/debug_siem'))
{
    file_put_contents("/tmp/siem", "STATS PORTS:$sql\n$sqlports\n", FILE_APPEND);
}
/* Run the Query again for the actual data (with the LIMIT) */
session_write_close();
$result = $qs->ExecuteOutputQuery($sql, $db);
$event_cnt = $qs->GetCalcFoundRows($cnt_sql, $result->baseRecordCount(), $db);

$et->Mark("Retrieve Query Data");
// if ($debug_mode == 1) {
    // $qs->PrintCannedQueryList();
    // $qs->DumpState();
    // echo "$sql<BR>";
    // echo '<HR><TABLE BORDER=1>
             // <TR><TD>port_type</TD>
                 // <TD>proto</TD></TR>
             // <TR><TD>' . $port_type . '</TD>
                 // <TD>' . $proto . '</TD></TR>
           // </TABLE>';
// }
/* Print the current view number and # of rows */
$qs->PrintResultCnt("",array(),$displaytitle);
echo '<FORM METHOD="post" name="PacketForm" id="PacketForm" ACTION="base_stat_ports.php">' . "\n";
echo "<input type='hidden' name='port_type' value='".Util::htmlentities($port_type)."'>\n";
if ($qs->num_result_rows > 0)
{
    $qro->PrintHeader();
}

$i = 0;
$report_data = array(); // data to fill report_data
while (($myrow = $result->baseFetchRow()) && ($i < $qs->GetDisplayRowCnt())) {
    $currentPort = $url_port = $myrow[0] . ' ';
    if ($port_proto == TCP) {
        $currentPort = $currentPort . '/ tcp ';
    }
    if ($port_proto == UDP) {
        $currentPort = $currentPort . '/ udp ';
    }
    $crPort = $currentPort;
    // Go here to change the format of the Port lookup stuff! -- Kevin Johnson
    $extcolors = array ("#478F23","#456C9F","#AF4200"); $jc=0;
    foreach($external_port_link as $name => $baseurl) {
        $currentPort = $currentPort . '<A HREF="' . $baseurl . $myrow[0] . '" TARGET="_ACID_PORT_"><font color="'.$extcolors[$jc++].'">[' . $name . ']</font></A> ';
    }
    $port_proto = $myrow[1];
    $ctx = $myrow[2];
    $num_events = $myrow[3];
    $num_sig = $myrow[4];
    $num_sip = $myrow[5];
    $num_dip = $myrow[6];
    if ($port_proto == TCP) {
        $url_port_type = "tcp";
        $url_layer4 = "TCP";
    }
    if ($port_proto == UDP) {
        $url_port_type = "udp";
        $url_layer4 = "UDP";
    }
    $url_param = $url_port_type . "_port%5B0%5D%5B0%5D=+" . "&amp;" . $url_port_type . "_port%5B0%5D%5B1%5D=" . $port_type_sql . "&amp;" . $url_port_type . "_port%5B0%5D%5B2%5D=%3D" . "&amp;" . $url_port_type . "_port%5B0%5D%5B3%5D=" . $url_port . "&amp;tcp_flags%5B0%5D=&amp;" . $url_port_type . "_port%5B0%5D%5B4%5D=+" . "&amp;" . $url_port_type . "_port%5B0%5D%5B5%5D=+" . "&amp;" . $url_port_type . "_port_cnt=1" . "&amp;layer4=" . $url_layer4 . "&amp;num_result_rows=-1&amp;current_view=-1";
    qroPrintEntryHeader($i);
    /* Generating checkbox value -- nikns */
    if ($proto == TCP) $tmp_rowid = TCP . "_";
    else if ($proto == UDP) $tmp_rowid = UDP . "_";
    else $tmp_rowid = - 1 . "_";
    ($port_type == SOURCE_PORT) ? ($tmp_rowid.= SOURCE_PORT) : ($tmp_rowid.= DEST_PORT);
    $tmp_rowid.= "_" . $myrow[0]. "_" . $ctx;
    echo '    <TD><INPUT TYPE="checkbox" NAME="action_chk_lst[' . $i . ']" VALUE="' . $tmp_rowid . '">';
    echo '        <INPUT TYPE="hidden" NAME="action_lst[' . $i . ']" VALUE="' . $tmp_rowid . '">';
    echo '    </TD>';
    qroPrintEntry($currentPort, 'center', 'middle');
    //qroPrintEntry('<A HREF="base_stat_sensor.php?' . $url_param . '">' . $num_sensors . '</A>');
    $sens = (Session::show_entities() && !empty($entities[$ctx])) ? $entities[$ctx] : ((Session::show_entities()) ? _("Unknown") : GetSensorName($ctx, $db));
    qroPrintEntry($sens, 'center', 'middle');
    qroPrintEntry('<A HREF="base_qry_main.php?' . $url_param . '&amp;new=1&amp;submit=' . gettext("Query DB") . '&amp;sort_order=sig_a">' . Util::number_format_locale($num_events,0) . '</A>', 'center', 'middle');
    qroPrintEntry('<A HREF="base_stat_alerts.php?' . $url_param . '&amp;&sort_order=occur_d">' . Util::number_format_locale($num_sig,0) . '</A>', 'center', 'middle');

    $pid = $myrow[0] . '-' . $ctx;
    qroPrintEntry('<div class="upr" id="us'.$pid.'">-</div>', 'center', 'middle');
    qroPrintEntry('<div id="ud'.$pid.'">-</div>', 'center', 'middle');

    qroPrintEntryFooter();
    ++$i;

    // report_data
    $report_data[] = array (
        trim($crPort), $num_sig,
        $num_sip, $num_dip, $first_time, $last_time,
        "", "", "", "", $sens,
        ($proto<0 ? 0 : ($proto==TCP ? 1 : 2)), 0, $num_events
    );
}
$result->baseFreeRows();
$qro->PrintFooter();
$qs->PrintBrowseButtons();
$qs->PrintAlertActionButtons();
$qs->SaveReportData($report_data,($port_type == SOURCE_PORT) ? $src_port_report_type : $dst_port_report_type);
$qs->SaveState();
ExportHTTPVar("port_type", $port_type);
ExportHTTPVar("proto", $proto);
echo "\n</FORM>\n";
PrintBASESubFooter();
$et->Mark("Get Query Elements");
$et->PrintTiming();
$db->baseClose();

// Do not load javascript if we are exporting with report_launcher.php
if (!$export)
{
?>
<script>
    var tmpimg = '<img alt="" src="data:image/gif;base64,R0lGODlhEAALAPQAAOPj4wAAAMLCwrm5udHR0QUFBQAAACkpKXR0dFVVVaamph4eHkJCQnt7e1lZWampqSEhIQMDA0VFRc3NzcHBwdra2jIyMsTExNjY2KKioo6OjrS0tNTU1AAAAAAAAAAAACH/C05FVFNDQVBFMi4wAwEAAAAh/hpDcmVhdGVkIHdpdGggYWpheGxvYWQuaW5mbwAh+QQJCwAAACwAAAAAEAALAAAFLSAgjmRpnqSgCuLKAq5AEIM4zDVw03ve27ifDgfkEYe04kDIDC5zrtYKRa2WQgAh+QQJCwAAACwAAAAAEAALAAAFJGBhGAVgnqhpHIeRvsDawqns0qeN5+y967tYLyicBYE7EYkYAgAh+QQJCwAAACwAAAAAEAALAAAFNiAgjothLOOIJAkiGgxjpGKiKMkbz7SN6zIawJcDwIK9W/HISxGBzdHTuBNOmcJVCyoUlk7CEAAh+QQJCwAAACwAAAAAEAALAAAFNSAgjqQIRRFUAo3jNGIkSdHqPI8Tz3V55zuaDacDyIQ+YrBH+hWPzJFzOQQaeavWi7oqnVIhACH5BAkLAAAALAAAAAAQAAsAAAUyICCOZGme1rJY5kRRk7hI0mJSVUXJtF3iOl7tltsBZsNfUegjAY3I5sgFY55KqdX1GgIAIfkECQsAAAAsAAAAABAACwAABTcgII5kaZ4kcV2EqLJipmnZhWGXaOOitm2aXQ4g7P2Ct2ER4AMul00kj5g0Al8tADY2y6C+4FIIACH5BAkLAAAALAAAAAAQAAsAAAUvICCOZGme5ERRk6iy7qpyHCVStA3gNa/7txxwlwv2isSacYUc+l4tADQGQ1mvpBAAIfkECQsAAAAsAAAAABAACwAABS8gII5kaZ7kRFGTqLLuqnIcJVK0DeA1r/u3HHCXC/aKxJpxhRz6Xi0ANAZDWa+kEAA7AAAAAAAAAAAA" />';
    var plots=new Array();
    var pi = 0;
    function load_content() {
        if (pi>=plots.length) return;
        var item = plots[pi]; pi++;
        var params = item.replace(/us/,'?port=').replace(/-/,'&id=');
        var pid = item.replace(/us/,'');
        $.ajax({
            beforeSend: function() {
                $('#us'+pid).html(tmpimg);
                $('#ud'+pid).html(tmpimg);
            },
            type: "GET",
            url: "base_stat_ports_data.php"+params,
            success: function(msg) {
                var res = msg.split(/##/);
                $('#us'+pid).html(res[0]);
                $('#ud'+pid).html(res[1]);
                setTimeout('load_content()',10);
            }
        });
    }
    $(document).ready(function() {
        $('.upr').each(function(index, item) {
            plots.push(item.id);
        });
        setTimeout('load_content()',10);
    });
</script>
<?php
}

echo "</body>\r\n</html>";
