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
Session::logcheck("report-menu", "ReportsReportServer");

$conf = $GLOBALS["CONF"];
$jpgraph = $conf->get_conf("jpgraph_path");
require ("$jpgraph/jpgraph.php");
require ("$jpgraph/jpgraph_line.php");
require ("$jpgraph/jpgraph_scatter.php");

/*********** Functions ************/
function getService($conn, $IP) {

    $service = '';
    
    $sql = "select dest_ip,service from datawarehouse.ip2service WHERE dest_ip = ?";

    $rs = $conn->Execute($sql, array($IP));
    
    if (!$rs)
    {
        Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        return("Source Inconnue");
    }

    if ($rs->fields['service'] != '')
    {
        $service = $rs->fields['service'];
    }

    return $service;

}


function getIP2Country($conn, $IP){   
    
    $sql = "select country from datawarehouse.ip2country where INET_ATON(?) >= start and INET_ATON(?) <= end;";
    
    $rs = $conn->Execute($sql, array($IP, $IP));
    
    if (!$rs)
    {
        Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        return("Source Inconnue");
    }

    $pays = $rs->fields["country"];

    if (!$pays) return("Source Inconnue");

    return($pays);

}

function whereYM($date_from, $date_to) {
    $sql_year = "STR_TO_DATE( CONCAT( year, '-', month, '-', day ) , '%Y-%m-%d' ) >= '$date_from' AND STR_TO_DATE( CONCAT( year, '-', month, '-', day ) , '%Y-%m-%d' ) <= '$date_to'";
    return $sql_year;
}


function getSourceLocalSSIYear($conn, $date_from, $date_to)
{
    $where_range = whereYM($date_from, $date_to);
    
    $user = Session::get_session_user();

    $sql = "SELECT source, count(*) as volume from datawarehouse.ssi_user WHERE ssi_user.user = ? AND $where_range Group BY source;";

    //print_r($sql);
    $result = array();
    
    $rs = $conn->Execute($sql, array($user));
    
    if (!$rs)
    {
        Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        return $result;
    }
    while (!$rs->EOF)
    {
        $result[] = $rs->fields;
        $rs->MoveNext();
    }

    return $result;
}


function getSourceRepartitionYear($conn, $date_from, $date_to) {

    $result = getSourceLocalSSIYear($conn, $date_from, $date_to);

    foreach ($result as $ligne)
           {
        $destinationnet=getService($conn, $ligne['source']);
    
            
            if (!$destinationnet) {
                if(!$tab[getIP2Country($conn,$ligne["source"])]) $tab[getIP2Country($conn,$ligne["source"])]=0;
                $tab[getIP2Country($conn,$ligne["source"])]=$tab[getIP2Country($conn,$ligne["source"])]+$ligne["volume"];
            }
            
    }

    if(is_array($tab)){

    arsort($tab);
    reset($tab);

    return $tab;
    } else {
    return NULL;
    }
}


function getSourceCoordYear($conn, $date_from="", $date_to="") {
    
    $data = array();
    if ($date_from == "" || $date_to == "") { // Last Month by default
        $date_from = strftime("%Y-%m-%d", time() - (24 * 60 * 60 * 30));
        $date_to = strftime("%Y-%m-%d", time());
    }

    $tab = getSourceRepartitionYear($conn, $date_from, $date_to);


  if(is_array($tab)){
    foreach ($tab as $pays=>$volume) {
        
        $sql = "select distinct(g.nom),g.abs,g.ord from datawarehouse.geo g, datawarehouse.ip2country i where UPPER(g.pays)=UPPER(i.a2) and i.country = ?;";
        $rs  = $conn->Execute($sql, array($pays));
        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            return $data;
        }
        
        $result = $rs->fields;
        if ($result['ord'] && $result['abs'])
        {
            array_push($data , array("nom"=>$result['nom'],"abs"=> $result['abs'],"ord"=> $result['ord'],"volume"=> $volume));
        }
    }
    }

    return($data);
}




/******************* GRAPH CODE *******************/

$date_from = ($_GET['date_from'] != "") ? $_GET['date_from'] : strftime("%Y-%m-%d", time() - (24 * 60 * 60 * 30));
$date_to = ($_GET['date_to'] != "") ? $_GET['date_to'] : strftime("%Y-%m-%d", time());

ossim_valid($date_from, OSS_DATE, 'illegal:' . _("Date From"));
ossim_valid($date_to,   OSS_DATE, 'illegal:' . _("Date To"));
if (ossim_error()) {
    die(ossim_error());
}

// Get data from 'datawarehouse' database
$db   = new ossim_db();
$conn = $db->connect();
$data = getSourceCoordYear($conn, $date_from, $date_to);
$db->close();

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

//$graph->img->SetAntiAliasing("white");
//$graph->img->SetAntiAliasing("black");
$graph->SetScale("lin",1,$yimg,1,$ximg);
$graph->ygrid->Show(false,false);
$graph->xgrid->Show(false,false);
$graph->xaxis->Hide();
$graph->yaxis->Hide();

//print_r($data);
$pays = array();

$vol_max=0;
foreach ($data as $line) {
    $vol_max=$vol_max+intval($line["volume"]);
    }


$i=0;
foreach ($data as $line) {
    $x=intval($line["abs"])*$rapport_x - 45;
    $y=intval($yadapt - $line["ord"])*$rapport_y - 80;
    $abs = array($x);
    $ord = array($y);
    $pays[$i]= new ScatterPlot($ord,$abs);
    $pays[$i]->mark->SetType(MARK_FILLEDCIRCLE);
#    $pays[$i]->mark->SetColor("red@0.9");

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

$_90 = array(90);
$_50 = array(50);
$_120 = array(120);
$_150 = array(150);

$legende25=new ScatterPlot($_90,$_50);
$legende25->mark->SetType(MARK_FILLEDCIRCLE);
$legende25->mark->SetColor("yellow@0.5");
$legende25->mark->SetFillColor("yellow@0.4");
$legende25->mark->SetSize(10);
$graph->Add($legende25);

$legtext25=new Text(" < 25%");
$legtext25->SetPos(70,597);
$legtext25->SetColor("black");
$legtext25->SetFont(FF_FONT1,FS_BOLD,15);
$graph->AddText($legtext25);

$legende50=new ScatterPlot($_120,$_50);
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

$legende100=new ScatterPlot($_150,$_50);
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
