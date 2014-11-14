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


//
// $Id: graph1.php,v 1.5 2010/02/08 18:10:51 jmalbarracin Exp $
//
//
// $Id: graph1.php,v 1.5 2010/02/08 18:10:51 jmalbarracin Exp $
//
require_once 'av_init.php';
require_once 'config.php';
require_once 'functions.inc';
require_once '../graphs/jpgraph/jpgraph.php';
require_once '../graphs/jpgraph/jpgraph_pie.php';
require_once '../graphs/jpgraph/jpgraph_pie3d.php';

Session::logcheck("environment-menu", "EventsVulnerabilities");

//$getParams = array( "risk1", "risk2", "risk3", "risk4", "risk5",
//                    "risk6", "risk7" );
                    
$getParams = array( "risk1", "risk2", "risk3", "risk6", "risk7" );
                    
switch ($_SERVER['REQUEST_METHOD'])
{
case "GET" :
   foreach($getParams as $gp) {
	   if (isset($_GET[$gp])) { 
         $$gp=htmlspecialchars(mysql_real_escape_string(trim($_GET[$gp])), ENT_QUOTES); 
      } else { 
         $$gp = ""; 
      }
   }
	break;
}

$w = GET("w");
ossim_valid($w, OSS_NULLABLE, OSS_DIGIT, 'illegal:' . _("Parameter w"));
if (ossim_error()) {
    die(ossim_error());
}


//if ($risk1=="" || $risk2=="" || $risk3=="" || $risk4=="" || $risk5=="" || $risk6=="" || $risk7=="") { JpGraphError::Raise(" No vulnerability data or incomplete data to chart. "); }
if ($risk1=="" || $risk2=="" || $risk3=="" || $risk6=="" || $risk7=="") { JpGraphError::Raise(" No vulnerability data or incomplete data to chart. "); }

if (!is_numeric($risk1)) { JpGraphError::Raise(" Incorrect parameter - risk1 is not numeric. "); }
if (!is_numeric($risk2)) { JpGraphError::Raise(" Incorrect parameter - risk2 is not numeric. "); }
if (!is_numeric($risk3)) { JpGraphError::Raise(" Incorrect parameter - risk3 is not numeric. "); }
//if (!is_numeric($risk4)) { JpGraphError::Raise(" Incorrect parameter - risk4 is not numeric. "); }
//if (!is_numeric($risk5)) { JpGraphError::Raise(" Incorrect parameter - risk5 is not numeric. "); }
if (!is_numeric($risk6)) { JpGraphError::Raise(" Incorrect parameter - risk6 is not numeric. "); }
if (!is_numeric($risk7)) { JpGraphError::Raise(" Incorrect parameter - risk7 is not numeric. "); }

//$data = array($risk1,$risk2,$risk3,$risk4,$risk5,$risk6,$risk7);

if ($risk1>0) { $data[] = $risk1; $legend[] = "Serious - $risk1"; $totalvulns+=$risk1; $colors[]="#C835ED"; }
if ($risk2>0) { $data[] = $risk2; $legend[] = "High - $risk2"; $totalvulns+=$risk2; $colors[]="red"; }
if ($risk3>0) { $data[] = $risk3; $legend[] = "Medium - $risk3"; $totalvulns+=$risk3; $colors[]="orange"; }
if ($risk6>0) { $data[] = $risk6; $legend[] = "Low - $risk6"; $totalvulns+=$risk6; $colors[]="#FFD700"; }
if ($risk7>0) { $data[] = $risk7; $legend[] = "Info - $risk7"; $totalvulns+=$risk7; $colors[]="#F0E68C"; }
 
//$data = array($risk1,$risk2,$risk3,$risk6,$risk7);
//$legend=array("Serious - $risk1","High - $risk2","Medium - $risk3","Low - $risk6","Info - $risk7");
//$totalvulns=$risk1+$risk2+$risk3+$risk6+$risk7;

if ($totalvulns > 0) {
$graph = new PieGraph(450,200,"auto");
$graph->SetAntiAliasing();
//$graph->SetShadow();

$graph->title->Set("Vulnerabilities Found - $totalvulns"); 
$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->SetColor('#fbfbfb');


if(intval($w)==1) {
    $graph->SetMarginColor('#FAFAFA');
    $graph->legend->SetShadow('#fafafa',0);
    //$graph->legend->SetFillColor('#fafafa');
    $graph->legend->SetFrameWeight(0);
}
else if(intval($w)==2){
    $graph->SetMarginColor('#FFFFFF');
    $graph->legend->SetShadow('#FFFFFF',0);
    $graph->legend->SetFillColor('#FFFFFF');
    $graph->legend->SetFrameWeight(0);
}

$p1 = new PiePlot3D($data);
$graph->SetFrame(false, '#ffffff');
$p1->SetSize(0.5);
$p1->SetStartAngle(290);
$p1->SetAngle(50);
$p1->SetCenter(0.35);

$p1->SetLegends($legend);
//$colors=array("#C835ED", "red", "orange", "green", "#eeeeee");
//$colors=array("#C835ED", "red", "orange", "#FFD700", "#F0E68C");
$p1->SetSliceColors($colors);
$p1->ExplodeAll(8);
//$dplot[0]->SetFillColor("blue");
//$dplot[1]->SetFillColor("green");
//$dplot[2]->SetFillColor("navy");
//$dplot[3]->SetFillColor("orange");
//$dplot[4]->SetFillColor("magenta");
//$dplot[5]->SetFillColor("yellow");
//$dplot[6]->SetFillColor("red");

$graph->Add($p1);
$graph->Stroke();
}
?>
