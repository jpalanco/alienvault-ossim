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
include_once ("$BASE_path/base_qry_common.php");
include_once ("$BASE_path/base_stat_common.php");

$_SESSION["siem_default_group"] = "base_stat_alerts.php?sort_order=occur_d";
if ($_REQUEST['sort_order']=='') $_GET['sort_order']='occur_d';

($debug_time_mode >= 1) ? $et = new EventTiming($debug_time_mode) : '';
$cs = new CriteriaState("base_stat_alerts.php");
$submit = ImportHTTPVar("submit", VAR_ALPHA | VAR_SPACE, array(
    gettext("Delete Selected"),
    gettext("Delete ALL on Screen"),
    _ENTIREQUERY
));
$export = intval(ImportHTTPVar("export", VAR_DIGIT)); // Called from report_launcher.php
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
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password, 0, 1);

if ($event_cache_auto_update == 1) UpdateAlertCache($db);
$criteria_clauses = ProcessCriteria();

// Include base_header.php
if ($qs->isCannedQuery()) PrintBASESubHeader($page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $page_title . ": " . $qs->GetCurrentCannedQueryDesc() , $cs->GetBackLink() , 1);
else PrintBASESubHeader($page_title, $page_title, $cs->GetBackLink() , 1);

// Use accumulate tables only when timestamp criteria is not hour sensitive
$use_ac  = $criteria_clauses[3];
$nevents = " AND acid_event.plugin_id=PLUGINID AND acid_event.plugin_sid=PLUGINSID";
$where   = ($criteria_clauses[1] != "") ? " WHERE " . $criteria_clauses[1] : " ";

// use po_acid_event
if ($use_ac)
{
    $from    = " FROM po_acid_event as acid_event " . $criteria_clauses[0];
	$counter = "sum(acid_event.cnt) as sig_cnt";
    $from1   = " FROM acid_event  " . $criteria_clauses[0];
}
else
{
    $from = $from1 = " FROM acid_event  " . $criteria_clauses[0];
    $counter = "count(acid_event.id) as sig_cnt";
}
if (preg_match("/^(.*)AND\s+\(\s+timestamp\s+[^']+'([^']+)'\s+\)\s+AND\s+\(\s+timestamp\s+[^']+'([^']+)'\s+\)(.*)$/", $where, $matches))
{
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
$qs->AddValidActionOp(gettext("Insert into DS Group"));
$qs->AddValidActionOp(gettext("Delete Selected"));
$qs->AddValidActionOp(gettext("Delete ALL on Screen"));
$qs->SetActionSQL($from1 . $where);
($debug_time_mode >= 1) ? $et->Mark("Initialization") : '';
$qs->RunAction($submit, PAGE_STAT_ALERTS, $db);
($debug_time_mode >= 1) ? $et->Mark("Alert Action") : '';
/* Get total number of events */
/* mstone 20050309 this is expensive -- don't do it if we're avoiding count() */
/*if ($avoid_counts != 1 && !$use_ac) {
$event_cnt = EventCnt($db);
if($event_cnt == 0){
$event_cnt = 1;
}
}*/
/* create SQL to get Unique Alerts */
//$cnt_sql = "SELECT count(DISTINCT acid_event.plugin_id,acid_event.plugin_sid) " . $from . $where;
/* Run the query to determine the number of rows (No LIMIT)*/
($debug_time_mode >= 1) ? $et->Mark("Counting Result size") : '';
/* Setup the Query Results Table */
$qro = new QueryResultsOutput("base_stat_alerts.php?caller=" . $caller);
$qro->AddTitle(" ");
$qro->AddTitle(gettext("Signature"), "sig_a", " ", " ORDER BY plugin_id ASC,plugin_sid", "sig_d", " ", " ORDER BY plugin_id DESC,plugin_sid");
$events_title = _("Events"). "&nbsp;# <span class='idminfo' txt='".Util::timezone($tz)."'>(*)</span>";
$qro->AddTitle("<span id='total_title'>$events_title</span>", "occur_a", " ", " ORDER BY sig_cnt ASC", "occur_d", " ", " ORDER BY sig_cnt DESC");
$qro->AddTitle(_("Unique Src.&nbsp;#") , "saddr_a", ", count(DISTINCT ip_src) AS saddr_cnt ", " ORDER BY saddr_cnt ASC", "saddr_d", ", count(DISTINCT ip_src) AS saddr_cnt ", " ORDER BY saddr_cnt DESC");
$qro->AddTitle(_("Unique Dst.&nbsp;#") , "daddr_a", ", count(DISTINCT ip_dst) AS daddr_cnt ", " ORDER BY daddr_cnt ASC", "daddr_d", ", count(DISTINCT ip_dst) AS daddr_cnt ", " ORDER BY daddr_cnt DESC");
/*$qro->AddTitle(gettext("First"),
"first_a", ", min(timestamp) AS first_timestamp ",
" ORDER BY first_timestamp ASC",
"first_d", ", min(timestamp) AS first_timestamp ",
" ORDER BY first_timestamp DESC");

if ( $show_previous_alert == 1 )
$qro->AddTitle("Previous");

$qro->AddTitle(gettext("Last"),
"last_a", ", max(timestamp) AS last_timestamp ",
" ORDER BY last_timestamp ASC",
"last_d", ", max(timestamp) AS last_timestamp ",
" ORDER BY last_timestamp DESC");
*/
$qro->AddTitle(gettext("Latest Event") , "", "", "", "", "", "");
$qro->AddTitle(_("Graph"));
$sort_sql = $qro->GetSortSQL($qs->GetCurrentSort() , $qs->GetCurrentCannedQuerySort());
/* mstone 20050309 add sig_name to GROUP BY & query so it can be used in postgres ORDER BY */
/* mstone 20050405 add sid & ip counts */


$sql = "SELECT acid_event.plugin_id, acid_event.plugin_sid, count(DISTINCT(ip_src)) as saddr_cnt, count(DISTINCT(ip_dst)) as daddr_cnt, $counter " . $sort_sql[0] . $from . $where . " GROUP BY plugin_id, plugin_sid HAVING sig_cnt>0 " . $sort_sql[1];


$sqlips = "SELECT max(timestamp) as last " . $sort_sql[0] . $from . $where . $nevents;
$_SESSION['_siem_ip_query'] = $sqlips;

//echo $sql."<br>".$_SESSION['siem_alerts_query']."<br>";
//time selection for graph x
$tr = ($_SESSION["time_range"] != "") ? $_SESSION["time_range"] : "all";
$trdata = array(0,0,$tr);
if ($tr=="range") {
    $desde = strtotime($_SESSION["time"][0][4]."-".$_SESSION["time"][0][2]."-".$_SESSION["time"][0][3]." 00:00:00");
    $hasta = strtotime($_SESSION["time"][1][4]."-".$_SESSION["time"][1][2]."-".$_SESSION["time"][1][3]." 23:59:59");
    $diff = $hasta - $desde;
    if ($diff > 2678400) $tr = "all";
    elseif ($diff > 1296000) $tr = "month";
    elseif ($diff > 604800) $tr = "weeks";
    elseif ($diff >= 172799) $tr = "week";
    elseif ($diff <= 86400) $tr = "today";
    else $tr = "day";
    $trdata = array ($desde,$hasta,"range");
}
//list($x, $y, $xticks, $xlabels) = range_graphic($trdata);

$tzc = Util::get_tzc($tz);
switch ($tr) {
    case "today":
        $interval = "hour(convert_tz(timestamp,'+00:00','$tzc')) as intervalo, 'h' as suf";
        $grpby = " GROUP BY intervalo,suf";
        break;

    case "day2":
    case "day":
        $interval = "hour(convert_tz(timestamp,'+00:00','$tzc')) as intervalo, day(convert_tz(timestamp,'+00:00','$tzc')) as suf";
        $grpby = " GROUP BY intervalo,suf";
        break;

    case "week":
    case "weeks":
        $interval = "day(convert_tz(timestamp,'+00:00','$tzc')) as intervalo, monthname(convert_tz(timestamp,'+00:00','$tzc')) as suf";
        $grpby = "GROUP BY intervalo,suf ORDER BY suf,intervalo";
        break;

    case "month":
        $interval = "day(convert_tz(timestamp,'+00:00','$tzc')) as intervalo, monthname(convert_tz(timestamp,'+00:00','$tzc')) as suf";
        $grpby = "GROUP BY intervalo,suf ORDER BY suf,intervalo";
        break;

    default:
        $interval = "monthname(convert_tz(timestamp,'+00:00','$tzc')) as intervalo, year(convert_tz(timestamp,'+00:00','$tzc')) as suf";
        $grpby = "GROUP BY intervalo,suf ORDER BY suf,intervalo";
}
$sqlgraph = "SELECT $counter, $interval $from $where AND acid_event.plugin_id=PLUGINID AND acid_event.plugin_sid=PLUGINSID $grpby";

$_SESSION['_siem_current_query_graph'] = $sqlgraph;

//echo $sql."<br>".$sqlgraph."<br>".$interval." ".$tr;
if (file_exists('/tmp/debug_siem'))
{
    file_put_contents("/tmp/siem", "STATS UNIQUE:$sql\n$sqlalerts\n$sqlips\n$sqlgraph\n", FILE_APPEND);
}
/* Run the Query again for the actual data (with the LIMIT) */
session_write_close();
$result = $qs->ExecuteOutputQuery($sql, $db);
if ($result->baseRecordCount()==0 && $use_ac) { $result = $qs->ExecuteOutputQuery($sql, $db); }
$event_cnt = $qs->GetCalcRows($criteria_clauses[9], $result->baseRecordCount(), $db);

($debug_time_mode >= 1) ? $et->Mark("Retrieve Query Data") : '';
// if ($debug_mode == 1) {
    // $qs->PrintCannedQueryList();
    // $qs->DumpState();
    // echo "$sql<BR>";
// }
/* Print the current view number and # of rows */
$qs->PrintEstimatedResultCnt();
echo '
  <script src="../js/jquery.flot.pie.js" language="javascript" type="text/javascript"></script>
  ';
echo '<FORM METHOD="post" NAME="PacketForm" id="PacketForm" ACTION="base_stat_alerts.php">';
if ($qs->num_result_rows > 0)
{
    $qro->PrintHeader();
}
$i = 0;
// The below is due to changes in the queries...
// We need to verify that it works all the time -- Kevin
$report_data = array();
$and = (strpos($where, "WHERE") != 0) ? " AND " : " WHERE ";
while (($myrow = $result->baseFetchRow()) && ($i < $qs->GetDisplayRowCnt())) {
    if ($myrow["plugin_id"]=="" || $myrow["plugin_sid"]=="") continue;
    //
    $sig_id=$myrow["plugin_id"].";".$myrow["plugin_sid"];
    $signame = BuildSigByPlugin($myrow["plugin_id"], $myrow["plugin_sid"], $db);
    //
    /* get Total Occurrence */
    $total_occurances = $myrow["sig_cnt"];
    /* Get other data */
    $num_src_ip = ($myrow["saddr_cnt"]!="") ? $myrow["saddr_cnt"] : "-";
    $num_dst_ip = ($myrow["daddr_cnt"]!="") ? $myrow["daddr_cnt"] : "-";

    /* Print out (Colored Version) -- Alejandro */
    //qroPrintEntryHeader((($colored_alerts == 1) ? GetSignaturePriority($sig_id, $db) : $i) , $colored_alerts);
    qroPrintEntryHeader( $i , $colored_alerts);
    $tmp_rowid = $myrow["plugin_id"]." ".$myrow["plugin_sid"];
    echo '  <TD nowrap>&nbsp;&nbsp;
                 <INPUT TYPE="checkbox" class="trlnks" pid="'.$myrow['plugin_id'].'" psid="'.$myrow['plugin_sid'].'" NAME="action_chk_lst[' . $i . ']" VALUE="' . $tmp_rowid . '">
                 &nbsp;&nbsp;
                 <INPUT TYPE="hidden" NAME="action_lst[' . $i . ']" VALUE="' . $tmp_rowid . '">
             </TD>';
    $sigstr = trim(preg_replace("/.*\/\s*(.*)/","\\1",preg_replace("/^[\.\,\"\!]|[\.\,\"\!]$/","",preg_replace("/.*##/","",html_entity_decode(strip_tags($signame))))));
    $siglink = "base_qry_main.php?new=1&submit=" . gettext("Query DB") . "&num_result_rows=-1&sig_type=1&sig%5B0%5D=%3D&sig%5B1%5D=" . urlencode($sig_id);
    $tmpsig = explode("##", $signame);
    if ($tmpsig[1]!="") {
        $antes = $tmpsig[0];
        $despues = $tmpsig[1];
    } else {
        $antes = "";
        $despues = $signame;
    }
    qroPrintEntry("$antes <a href='$siglink' class='qlink'>".trim($despues)."</a>" , "left", "", "style='vertical-align:middle'");

    //qroPrintEntry(BuildSigByID($sig_id, $db),"left","middle");
    $ocurrlink = 'base_qry_main.php?new=1&amp;sig%5B0%5D=%3D&amp;sig%5B1%5D=' . urlencode($sig_id) . '&amp;sig_type=1' . '&amp;submit=' . gettext("Query DB") . '&amp;num_result_rows=-1';
    //$perc = (($avoid_counts != 1) ? ('&nbsp;(' . (round($total_occurances / $event_cnt * 100)) . '%)') : (''));

    $pid = $myrow["plugin_id"]."-".$myrow["plugin_sid"];

    qroPrintEntry('<A HREF="' . $ocurrlink . '" id="occur'.$pid.'" class="qlink">' . Util::number_format_locale($total_occurances,0) . '</A>' .
    /* mstone 20050309 lose this if we're not showing stats */
    $perc , 'center', 'middle', 'nowrap');
    if ($db->baseGetDBversion() >= 100) $addr_link = '&amp;sig_type=1&amp;sig%5B0%5D=%3D&amp;sig%5B1%5D=' . urlencode($sig_id);
    else $addr_link = '&amp;sig%5B0%5D=%3D&amp;sig%5B1%5D=' . urlencode($sigstr);

    qroPrintEntry(BuildUniqueAddressLink(1, $addr_link, '', 'qlink') .  Util::number_format_locale($num_src_ip,0) . '</A>', 'center', 'middle', 'nowrap');
    qroPrintEntry(BuildUniqueAddressLink(2, $addr_link, '', 'qlink') .  Util::number_format_locale($num_dst_ip,0) . '</A>', 'center', 'middle', 'nowrap');

    qroPrintEntry( '<div id="le'.$pid.'" style="padding:0px 4px"></div>', 'center', 'middle', 'nowrap');

    // GRAPH
    qroPrintEntry('<div id="plotarea' . $pid . '" class="plot"></div>', 'center', 'middle');

    qroPrintEntryFooter();
    $i++;
    $prev_time = null;

    // report_data
    $report_data[] = array (
        trim(html_entity_decode($despues)),
        html_entity_decode($total_occurances.$perc),
        "", "",
        "", "", "", "", "", "", "",
        0 ,$num_src_ip, $num_dst_ip
    );
}
$result->baseFreeRows();
$qro->PrintFooter();
$qs->PrintBrowseButtons();
$qs->PrintAlertActionButtons();
$qs->SaveReportData($report_data,$unique_events_report_type);
$qs->SaveState();
echo "\n</FORM>\n";
PrintBASESubFooter();
if ($debug_time_mode >= 1) {
    $et->Mark("Get Query Elements");
    $et->PrintTiming();
}
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
        var params = item.replace(/plotarea/,'?id=').replace(/-/,'&sid=');
        var pid = item.replace(/plotarea/,'');
        $.ajax({
            beforeSend: function() {
                $('#le'+pid).html(tmpimg);
            },
            type: "GET",
            url: "base_stat_alerts_graph.php"+params,
            success: function(msg) {
                var res = msg.split(/##/);
                $('#le'+pid).html(res[0]);
                if (res[1] == '-')
                {
                    $('#plotarea'+pid).html(res[1]);
                }
                else
                {
                    eval(res[1]);
                }
                setTimeout('load_content()',10);
            }
        });
    }
    $(document).ready(function() {
        $('.plot').each(function(index, item) {
            plots.push(item.id);
        });
        setTimeout('load_content()',10);
    });
</script>
<?php
}

echo "</body>\r\n</html>";
?>
