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
$limit = GET('ports');
$type = GET('type');
$height = GET('height');
$width = GET('width');
$date_from = (GET('date_from') != "") ? GET('date_from') : strftime("%d/%m/%Y %H:%M:%S", time() - (24 * 60 * 60));
$date_to = (GET('date_to') != "") ? GET('date_to') : strftime("%d/%m/%Y %H:%M:%S", time());


ossim_valid($limit, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Limit"));
ossim_valid($height, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("height"));
ossim_valid($width, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("width"));
ossim_valid($type, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Report type"));
ossim_valid($date_from, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("from date"));
ossim_valid($date_to, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("to date"));

$runorder = intval(GET('runorder')); if ($runorder==0) $runorder="";
if (ossim_error()) {
    die(ossim_error());
}
/* ports to show */
if (empty($limit)) {
    $limit = 10;
}
if (empty($type)) {
    $type = "event";
}
$security_report = new Security_report();
$shared = new DBA_shared(GET('shared'));
$SS_UsedPorts = $shared->get("SS_UsedPorts$runorder");
$SA_UsedPorts = $shared->get("SA_UsedPorts$runorder");
if ($type == "event" && is_array($SS_UsedPorts) && count($SS_UsedPorts)>0)
	$list = $SS_UsedPorts;
elseif ($type == "alarm" && is_array($SA_UsedPorts) && count($SA_UsedPorts)>0)
	$list = $SA_UsedPorts;
else
	$list = $security_report->Ports($limit, $type, $date_from, $date_to);
$datax = $datay = array();


$gorientation="h";
    
foreach($list as $key => $l) {
    if($key>=10){
        // ponemos un limite de resultados para la grafica
        //break;
        $gorientation="v";
    }
    $datax[] = $l[0];
    $datay[] = $l[2];
}
$conf = $GLOBALS["CONF"];
$jpgraph = $conf->get_conf("jpgraph_path");
require_once "$jpgraph/jpgraph.php";
require_once "$jpgraph/jpgraph_bar.php";


$titlecolor = "darkorange";


$colors = Util::get_chart_colors();

//$color = "darkorange";
//$color2 = "lightyellow";

$background = "#f1f1f1";
$title = _("DESTINATION PORTS");
// Setup the graph.

if ($width=="") $width = 400;
if ($height=="") $height = 250;

if($gorientation=="v")
	$height = 30 + count($list)*21; 
else
	$height = 250;
    
$graph = new Graph($width, $height, "auto");
$graph->img->SetMargin(60, 20, 30, 70);
$graph->SetScale("textlin");
//$graph->SetMarginColor("$background");
$graph->SetMarginColor("#fafafa");
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
$bplot->SetFillColor($colors[0] . "@0.3");
// Set color for the frame of each bar
$bplot->SetColor($colors[0]);
$graph->Add($bplot);
// Finally send the graph to the browser
$graph->Stroke();
unset($graph);
?>
