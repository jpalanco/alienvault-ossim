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


// define colors
define('COLOR1','#D6302C');
define('COLOR2','#3933FC');
define('COLOR3','green');
define('COLOR4','yellow');
define('COLOR5','pink');
define('COLOR6','#40E0D0');
define('COLOR7','#00008B');
define('COLOR8','#800080');
define('COLOR9','#FFA500');
define('COLOR10','#A52A2A');
define('COLOR11','#228B22');
define('COLOR12','#D3D3D3');

require_once ('av_init.php');
require_once ('ossim_conf.inc');

Session::logcheck("analysis-menu", "EventsForensics");

$conf     = $GLOBALS["CONF"];
$jpgraph  = $conf->get_conf("jpgraph_path");

require_once "$jpgraph/jpgraph.php";
require_once ("$jpgraph/jpgraph_line.php");




$runorder = intval(GET('runorder')); 

if ( $runorder==0 ){
	$runorder="";	
} 



$shared = new DBA_shared(GET('shared'));
$data   = $shared->get('data'.$runorder);
$leyend = $values = array();

foreach ($data as $x => $y) {
	$leyend[] = $x;
	$values[] = $y;
}

// Setup the graph.
$graph = new Graph(730, 180, "auto");
$graph->SetScale("textlin");

$graph->SetMargin(60,50,15,25);

$graph->SetMarginColor("#ffffff");

$graph->xaxis->SetTickLabels($leyend);
$graph->SetColor("#fafafa");
$graph->SetFrame(true,'#ffffff',0);

$dplot = new LinePLot($values);
$dplot->SetFillColor("#91b88e0@0.5");

// Add the plot to the graph
$graph->Add($dplot);
$graph->legend->SetPos(0.50,0.9,'center','bottom');
$graph->legend->SetShadow('#ffffff',0);
$graph->legend->SetFrameWeight(0);
$graph->legend->SetFillColor('#ffffff');
$graph->legend->SetColumns(2);

$graph->Stroke();
?>