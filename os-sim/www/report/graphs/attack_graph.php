<?php
/**
*
* License:
*
* Copyright (c) 2003-2006 ossim.net
* Copyright (c) 2007-2013 AlienVault
* All rights reserved.
*
* This package is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; version 2 dated June, 1991.
* You may not use, modify or distribute this program under any other version
* of the GNU General Public License.
*
* This package is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this package; if not, write to the Free Software
* Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
* MA  02110-1301  USA
*
*
* On Debian GNU/Linux systems, the complete text of the GNU General
* Public License can be found in `/usr/share/common-licenses/GPL-2'.
*
* Otherwise you can read it here: http://www.gnu.org/licenses/gpl-2.0.txt
*
*/


require_once 'av_init.php';
Session::logcheck("analysis-menu", "ReportsAlarmReport");


$limit = GET('hosts');
$target = GET('target');
$type = GET('type');
$date_from = (GET('date_from') != "") ? GET('date_from') : strftime("%d/%m/%Y %H:%M:%S", time() - (24 * 60 * 60));
$date_to = (GET('date_to') != "") ? GET('date_to') : strftime("%d/%m/%Y %H:%M:%S", time());

ossim_valid($limit, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Limit"));
ossim_valid($type, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Report type"));
ossim_valid($target, OSS_ALPHA, OSS_SPACE, OSS_SCORE, 'illegal:' . _("Target"));
ossim_valid($date_from, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("from date"));
ossim_valid($date_to, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("to date"));

$geoloc = new Geolocation("/usr/share/geoip/GeoLiteCity.dat");

$runorder = intval(GET('runorder')); if ($runorder==0) $runorder="";
if (ossim_error()) {
    die(ossim_error());
}
/* hosts to show */
if (empty($limit)) {
    $limit = 11;
}
if (empty($type)) {
    $type = "event";
}
if (!$type == "event") {
    if ($target == "ip_dst") $target = "dst_ip";
    if ($target == "ip_src") $target = "src_ip";
}
$security_report = new Security_report();
if (!strcmp($target, "ip_src") || !strcmp($target, "src_ip")) {
    $title = _("TOP ATTACKER");
    $sufix = "er";
    $color = "#D6302C";
    $color2 = "#0000CD";
    
    //$color = "navy";
    //$color2 = "lightsteelblue";
    $titlecolor = "darkblue";
} elseif (!strcmp($target, "ip_dst") || !strcmp($target, "dst_ip")) {
    $title = _("TOP ATTACKED");
    $sufix = "ed";
    $color = "#3933FC";
    $color2 = "#FF4500";
    
    //$color = "darkred";
    //$color2 = "lightred";
    $titlecolor = "darkred";
}
$shared = new DBA_shared(GET('shared'));
$SS_Attack = $shared->get("SS_Attack".$sufix."Host$runorder");
$SA_Attack = $shared->get("SA_Attack".$sufix."Host$runorder");

if ($type == "event" && is_array($SS_Attack) && count($SS_Attack)>0)
	$list = $SS_Attack;
elseif ($type == "alarm" && is_array($SA_Attack) && count($SA_Attack)>0)
	$list = $SA_Attack;
else
	$list = $security_report->AttackHost($target, $limit, $type, $date_from, $date_to);
$datax = $datay = array();


$gorientation="h";

foreach($list as $key => $l) {
    if($key>=10){
        // ponemos un límite de resultados para la gráfica
        //break;
        $gorientation="v";
    }
  
	$ip          = $l[0];
    $occurrences = number_format($l[1], 0, ",", ".");
    $id          = $l[2];
	$ctx         = $l[3];

    $hostname = (valid_hex32($id)) ? Asset_host::get_name_by_id($security_report->ossim_conn, $id) : $ip;
		
    $datax[] = $hostname;
    $datay[] = $l[1];
}
require_once ('ossim_conf.inc');
$conf = $GLOBALS["CONF"];
$jpgraph = $conf->get_conf("jpgraph_path");
require_once "$jpgraph/jpgraph.php";
require_once "$jpgraph/jpgraph_bar.php";
// Setup the graph.
if($gorientation=="v")
	$y = 30 + count($list)*21; 
else
	$y = 250;
	
$graph = new Graph(400, $y, "auto");
$graph->img->SetMargin(60, 20, 30, 100);
$graph->SetMarginColor("#fafafa");
$graph->SetScale("textlin");
//$graph->SetMarginColor("white");
//$graph->SetShadow();
// Set up the title for the graph
/*
$graph->title->Set("$title");
$graph->title->SetFont(FF_FONT1, FS_BOLD, 18);
$graph->title->SetColor("$titlecolor");
 */
// Setup font for axis
$graph->xaxis->SetFont(FF_FONT1, FS_NORMAL, 8);
$graph->yaxis->SetFont(FF_FONT1, FS_NORMAL, 11);
// Show 0 label on Y-axis (default is not to show)
$graph->yscale->ticks->SupressZeroLabel(false);
// Setup X-axis labels
$graph->xaxis->SetTickLabels($datax);
if($gorientation=="v") {
    $graph->img->SetAngle(90);
    $graph->Set90AndMargin(120,40,40,40);
}
else {
    $graph->xaxis->SetLabelAngle(90);
}
//Setup Frame
$graph->SetFrame(true, "#fafafa");
//$graph->SetFrame(false);

// Create the bar pot
$bplot = new BarPlot($datay);
$bplot->SetWidth(0.6);
// Setup color for gradient fill style
//$bplot->SetFillGradient("$color", $color2, GRAD_MIDVER);
$bplot->SetFillColor($color."@0.5");
$bplot->SetShadow($color."@0.7",5,5);
// Set color for the frame of each bar
$bplot->SetColor($color."@1");
$graph->Add($bplot);
// Finally send the graph to the browser
$graph->Stroke();
unset($graph);

$geoloc->close();
?>

