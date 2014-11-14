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

require_once 'classes/Util.inc';

$limit = GET('hosts');
$type = GET('type');
$date_from = (GET('date_from') != "") ? GET('date_from') : strftime("%d/%m/%Y %H:%M:%S", time() - (24 * 60 * 60));
$date_to = (GET('date_to') != "") ? GET('date_to') : strftime("%d/%m/%Y %H:%M:%S", time());
ossim_valid($limit, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Limit"));
ossim_valid($type, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _("Report type"));
ossim_valid($date_from, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("from date"));
ossim_valid($date_to, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 'illegal:' . _("to date"));
$runorder = intval(GET('runorder')); if ($runorder==0) $runorder="";
$multiple_colors = intval(GET('colors'));
if (ossim_error()) {
    die(ossim_error());
}
/* hosts to show */
if (empty($limit) || $limit<=0 || $limit>10) {
    $limit = 10;
}
if (empty($type)) {
    $type = "event";
}
$security_report = new Security_report();
$shared = new DBA_shared(GET('shared'));
$SS_TopEvents = $shared->get("SS_TopEvents$runorder");
$SA_TopAlarms = $shared->get("SA_TopAlarms$runorder");
if ($type == "event" && is_array($SS_TopEvents) && count($SS_TopEvents)>0)
	$list = $SS_TopEvents;
elseif ($type == "alarm" && is_array($SA_TopAlarms) && count($SA_TopAlarms)>0)
	$list = $SA_TopAlarms;
else 
	$list = $security_report->Events($limit, $type, $date_from, $date_to);
$data_pie = array();
$legend = $data = array();
foreach($list as $key => $l) {
    if($key>=10){
        // ponemos un límite de resultados para la gráfica
        break;
    }
    $data_pie[$l[1]] = Security_report::Truncate($l[0], 60);
    $legend[] = Util::signaturefilter(Security_report::Truncate($l[0], 60));
    $data[] = $l[1];
}

$total = array_sum($data);
$labels = array();

$tlabels = array();
$zero=$one=$two=0;

foreach($data as $value) {
    if(round($value/$total,2)*100==0) { // 0%
        $zero++;
    }
    else if(round($value/$total,2)*100==1) { // 1%
        $one++;
    }
    else if(round($value/$total,2)*100==2) { // 2%
        $two++;
    }
    $tlabels[]= round($value/$total,2)*100;
}

$iz = $io = $it = 0;

foreach ($tlabels as $label) {
    if($label == 0) {
        $iz++;
        if(floor($zero/2)==$iz || floor($zero/2)==0) { $labels[] = $label."%"; }
        else { $labels[] = ""; }
    }
    else if($label == 1) {
        $io++;
        if(floor($one/2)==$io || floor($one/2)==0) { $labels[] = $label."%"; }
        else { $labels[] = ""; }
    }
    else if($label == 2) {
        $it++;
        if(floor($two/2)==$it || floor($two/2)==0) { $labels[] = $label."%"; }
        else { $labels[] = ""; }
    }
    else {
        $labels[] = $label."%";
    }
}

$conf = $GLOBALS["CONF"];
if ($multiple_colors)
	$colors=array("#006699","#CC0000","#009900","yellow","pink","#40E0D0","#00008B","#800080","#FFA500","#A52A2A");
else
	$colors=array("#ADD8E6","#00BFFF","#4169E1","#4682B4","#0000CD","#483D8B","#00008B","#3636db","#1390fa","#6aafea");

//$colors=array("#D6302C","#3933FC","green","yellow","pink","#40E0D0","#00008B",'#800080','#FFA500','#A52A2A');

$jpgraph = $conf->get_conf("jpgraph_path");
require_once "$jpgraph/jpgraph.php";
require_once "$jpgraph/jpgraph_pie.php";
require_once "$jpgraph/jpgraph_pie3d.php";
// Setup graph
$graph = new PieGraph(400, 400, "auto");
$graph->SetAntiAliasing();
$graph->SetMarginColor('#fafafa');

//$graph->SetShadow();

$graph->title->SetFont(FF_FONT1, FS_BOLD);
// Create pie plot
$p1 = new PiePlot3d($data);
$p1->SetHeight(12);
$p1->SetSize(0.3);
if (count($labels)>1)
	$p1->SetCenter(0.5,0.25);
else
	$p1->SetCenter(0.57,0.25);

$p1->SetLabels($labels);
$p1->SetLabelPos(1);

if ($multiple_colors) {
	$p1->SetLegends($legend);
	$graph->legend->SetPos(0.5,0.95,'center','bottom');
	$graph->legend->SetShadow('#fafafa',0);
	$graph->legend->SetFrameWeight(0);
	$graph->legend->SetFillColor('#fafafa');
}

$graph->SetFrame(false);
$p1->SetSliceColors($colors);
//$p1->SetStartAngle(M_PI/8);
//$p1->ExplodeSlice(0);
$graph->Add($p1);
$graph->Stroke();
unset($graph);
?>
