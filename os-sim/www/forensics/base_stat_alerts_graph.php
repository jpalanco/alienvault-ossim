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

if ($_SESSION['_siem_current_query_graph']=="" || $_SESSION['_siem_ip_query']=="" || $_SESSION["deletetask"] != "") {
    echo "-##-##-";
    die();
}

$tz = Util::get_timezone(); 

$plugin_id  = ImportHTTPVar("id", VAR_DIGIT);
$plugin_sid = ImportHTTPVar("sid", VAR_DIGIT);
$sqlgraph   = str_replace("PLUGINSID", $plugin_sid, str_replace("PLUGINID", $plugin_id, $_SESSION['_siem_current_query_graph']));
$sqlunique  = str_replace("PLUGINSID", $plugin_sid, str_replace("PLUGINID", $plugin_id, $_SESSION['_siem_ip_query']));

session_write_close();

$qs = new QueryState();
$db = NewBASEDBConnection($DBlib_path, $DBtype);
$db->baseDBConnect($db_connect_method, $alert_dbname, $alert_host, $alert_port, $alert_user, $alert_password);

// Unique
$rs = $qs->ExecuteOutputQueryNoCanned($sqlunique, $db);
if ($row = $rs->baseFetchRow())
{
    $last = ($tz!=0) ?  gmdate("Y-m-d H:i:s",get_utc_unixtime($db,$row[0])+(3600*$tz)) : get_utc_unixtime($db,$row[0]);
    if (preg_match("/_acid_event/",$sqlunique))
    {
        $last  = str_replace(":00:00","H",$last);
    }
}
$rs->baseFreeRows();

//file_put_contents("/tmp/graph", "$sql\n$sqlunique\n$sqlgraph\n", FILE_APPEND);

echo "$last##";

// Graph
$tr = ($_SESSION["time_range"] != "") ? $_SESSION["time_range"] : "all";
$trdata = array(0,0,$tr);
if ($tr=="range") {
    // Using offset date("Z") to fix the gmdate conversion into range_graphic(): Line 886
    $desde = strtotime($_SESSION["time"][0][4]."-".$_SESSION["time"][0][2]."-".$_SESSION["time"][0][3]) + date("Z");
    $hasta = strtotime($_SESSION["time"][1][4]."-".$_SESSION["time"][1][2]."-".$_SESSION["time"][1][3]) + date("Z");
    $diff = $hasta - $desde;
    if ($diff > 2678400) $tr = "all";
    elseif ($diff > 1296000) $tr = "month";
    elseif ($diff > 604800) $tr = "weeks";
    elseif ($diff >= 86400) $tr = "week";
    else
    {
        $tr = "day";
        $desde = strtotime($_SESSION["time"][0][4]."-".$_SESSION["time"][0][2]."-".$_SESSION["time"][0][3]." ".$_SESSION["time"][0][5].":".$_SESSION["time"][0][6].":".$_SESSION["time"][0][7]) + date("Z");
        $hasta = strtotime($_SESSION["time"][1][4]."-".$_SESSION["time"][1][2]."-".$_SESSION["time"][1][3]." ".$_SESSION["time"][1][5].":".$_SESSION["time"][1][6].":".$_SESSION["time"][1][7]) + date("Z");
    }
    $trdata = array ($desde,$hasta,"range");
}
list($x, $y, $xticks, $xlabels) = range_graphic($trdata);

if (count($y) > 1)
{
    //echo $sqlgraph."<br>";
    $rgraph = $qs->ExecuteOutputQueryNoCanned($sqlgraph, $db);
    $yy = $y;
    while ($rowgr = $rgraph->baseFetchRow()) {
        $label = trim($rowgr[1] . " " . $rowgr[2]);
        if (isset($yy[$label]) && $yy[$label] == 0) $yy[$label] = $rowgr[0];
    }
    $rgraph->baseFreeRows();
    
    $plot = plot_graphic("plotarea".$plugin_id."-".$plugin_sid, 45, 320, $x, $yy, $xticks, $xlabels, false, 'base_qry_main.php?new=1&amp;sig%5B0%5D=%3D&amp;sig%5B1%5D=' . urlencode($plugin_id.";".$plugin_sid) . '&amp;sig_type=1' . '&amp;submit=' . gettext("Query DB") . '&amp;num_result_rows=-1', "", false);
    echo $plot;
}
else
{
    ?>$('#plotarea<?php echo $plugin_id."-".$plugin_sid ?>').html("<?php echo _('Trend graph is not available with this date range'); ?>");<?php
}
?>
