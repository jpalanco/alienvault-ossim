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
Session::logcheck("analysis-menu", "EventsForensics");

$conf = $GLOBALS["CONF"];
$jpgraph = $conf->get_conf("jpgraph_path");
require ("$jpgraph/jpgraph.php");
require ("$jpgraph/jpgraph_line.php");
require ("$jpgraph/jpgraph_scatter.php");


$geo_year=GET("year");
$geo_month=GET("month");
$user=GET("user");

$shared = new DBA_shared(GET('shared'));
$runorder = intval(GET('runorder')); if ($runorder==0) $runorder="";
$ips = $shared->get("geoips".$runorder);
if (!is_array($ips)) $ips = array();

#Resolution de la database:
$xdb=620;
$ydb=310;
# Resolution de l'image
$ximg=1264;
$yimg=694;
# Rapport :
$rapport_x=$ximg/$xdb;
$rapport_y=$yimg/$ydb;


// Some data
$yadapt=310;

// A nice graph with anti-aliasing
$graph = new Graph($ximg,$yimg,"auto");
$graph->img->SetMargin(1,1,1,1);    
$graph->SetBackgroundImage("../../pixmaps/mappemonde.jpg",BGIMG_FILLFRAME);

//$graph->img->SetAntiAliasing(false);
$graph->SetScale("lin",1,$yimg,1,$ximg);
$graph->ygrid->Show(false,false);
$graph->xgrid->Show(false,false);
$graph->xaxis->Hide();
$graph->yaxis->Hide();


$pays = array();
$data = array();

// DB DATA from datawarehouse.geo
require_once ('ossim_db.inc');
$db = new ossim_db();
$conn = $db->connect();
$vol_max=0;
foreach ($ips as $country => $val) {
	$cou = explode(":",$country);
	$line = array();
	$line["volume"] = $val;
	// query geoloc coords
	$sql = "select * from datawarehouse.geo where pays like '".$cou[0]."'";
	
	$rs = $conn->Execute($sql);
	
	if (!$rs) {
		print $conn->ErrorMsg();
		return;
	}
	$line["abs"] = $line["ord"] = 0;
	if (!$rs->EOF) {
	    $line["abs"] = $rs->fields["abs"];
	    $line["ord"] = $rs->fields["ord"];
	}
	if ($line["abs"]!=0) {
		$data[]=$line;
		$vol_max=$vol_max+intval($line["volume"]);
	}
}
$db->close($conn);

$i=0;
foreach ($data as $line) {
	$x=intval($line["abs"])*$rapport_x - 45;
	$y=intval($yadapt - $line["ord"])*$rapport_y - 80;
	$abs = array($x);
	$ord = array($y);
	$pays[$i]= new ScatterPlot($ord,$abs);
	$pays[$i]->mark->SetType(MARK_FILLEDCIRCLE);
#	$pays[$i]->mark->SetColor("red@0.9");

	$volume=($line["volume"]/$vol_max)*100;

	if( ($volume > 0) && ($volume <=25)) {
		$pays[$i]->mark->SetColor("yellow@0.5");
		$pays[$i]->mark->SetFillColor("yellow@0.4");
		}
	if( ($volume > 25) && ($volume <=50)) {
		$pays[$i]->mark->SetColor("orange@0.9");
		$pays[$i]->mark->SetFillColor("orange@0.8");
		}
	if ($volume > 50) {
		$pays[$i]->mark->SetColor("red@0.9") ;
		$pays[$i]->mark->SetFillColor("red@0.8");
		}

	$pays[$i]->mark->SetSize(10);
	$graph->Add($pays[$i]);
	$i++;
}


$legende25=new ScatterPlot(array(90),array(50));
$legende25->mark->SetType(MARK_FILLEDCIRCLE);
$legende25->mark->SetColor("yellow@0.5");
$legende25->mark->SetFillColor("yellow@0.4");
$legende25->mark->SetSize(10);
$graph->Add($legende25);

$legtext25=new Text(" < 25%");
$legtext25->SetPos(70,597);
$legtext25->SetColor("black");
$legtext25->SetFont(FF_FONT1,FS_BOLD,16);
$graph->AddText($legtext25);



$legende50=new ScatterPlot(array(120),array(50));
$legende50->mark->SetType(MARK_FILLEDCIRCLE);
$legende50->mark->SetColor("orange@0.5");
$legende50->mark->SetFillColor("orange@0.4");
$legende50->mark->SetSize(10);
$graph->Add($legende50);

$legtext50=new Text(" < 50%");
$legtext50->SetPos(70,567);
$legtext50->SetColor("black");
$legtext50->SetFont(FF_FONT1,FS_BOLD,16);
$graph->AddText($legtext50);

$legende100=new ScatterPlot(array(150),array(50));
$legende100->mark->SetType(MARK_FILLEDCIRCLE);
$legende100->mark->SetColor("red@0.5");
$legende100->mark->SetFillColor("red@0.4");
$legende100->mark->SetSize(10);
$graph->Add($legende100);

$legtext100=new Text(" > 50%");
$legtext100->SetPos(70,537);
$legtext100->SetColor("black");
$legtext100->SetFont(FF_FONT1,FS_BOLD,16);
$graph->AddText($legtext100);

$graph->Stroke();



?>


