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

$date_from = (GET('date_from') != "") ? GET('date_from') : strftime("%Y-%m-%d", time() - (24 * 60 * 60 * 30));
$date_to   = (GET('date_to') != "")   ? GET('date_to')   : strftime("%Y-%m-%d", time());

ossim_valid($date_from, OSS_DATE, 'illegal:' . _('Date From'));
ossim_valid($date_to, OSS_DATE, 'illegal:' . _('Date To'));
if (ossim_error())
{
    die(ossim_error());
}

$sql_year = "STR_TO_DATE( CONCAT( a.year, '-', a.month, '-', a.day ) , '%Y-%m-%d' ) >= '$date_from' AND STR_TO_DATE( CONCAT( a.year, '-', a.month, '-', a.day ) , '%Y-%m-%d' ) <= '$date_to'";

require_once ('ossim_db.inc');
$db   = new ossim_db();
$conn = $db->connect();
$user = Session::get_session_user();
$conn->Execute('use datawarehouse');
$sql="SELECT * FROM ( SELECT * FROM
(select s.service as service, 'Availability' as category, sum(c.D) as volume from datawarehouse.ssi_user a,
datawarehouse.category c, datawarehouse.ip2service s
where c.D <> '0' and a.sid=c.sid and a.destination=s.dest_ip and ".$sql_year." AND a.user='".$user."' GROUP BY
2) as imp_D
UNION SELECT * FROM
(select s.service as service, 'Integrity' as category, sum(c.I) as volume from datawarehouse.ssi_user a,
datawarehouse.category c, datawarehouse.ip2service s
where c.I <> '0' and a.sid=c.sid and a.destination=s.dest_ip and ".$sql_year." AND a.user='".$user."' GROUP BY 2)
as imp_I
UNION SELECT * FROM
(select s.service as service, 'Confidentiality' as category, sum(c.C) as volume from datawarehouse.ssi_user a,
datawarehouse.category c, datawarehouse.ip2service s
where c.C <> '0' and a.sid=c.sid and a.destination=s.dest_ip and ".$sql_year." AND a.user='".$user."' GROUP BY
2) as imp_C
) AS allalarms;";

$rs = $conn->Execute($sql);

if (!$rs) {
    print $conn->ErrorMsg();
    return;
}
// test perms for source or destination ips
$var=array();
while (!$rs->EOF) {
    $var1 = $rs->fields["category"];
    $var2 = $rs->fields["volume"];
    $var3 = $rs->fields["service"];
    $var[]=array(
        'var1'=>$var1,
        'var2'=>$var2,
        'var3'=>$var3
    );
    $rs->MoveNext();
}
$db->close($conn);
// define colors
define('COLORPIE1','#ea9896');
define('COLORPIE2','#9c99fd');
define('COLORPIE3','#7dfc7d');
define('COLORPIE4','#fcfc7d');
define('COLORPIE5','#bd7dbd');
define('COLORPIE6','#fcdde2');
define('COLORPIE7','#9dede5');
define('COLORPIE8','#7d7dc2');
//
// creamos dos grupos y añadimos sus valores
$data=array();
if(count($var)==0){
    $var[]=array(
        'var1'=>'',
        'var2'=>0
    );
}
foreach ($var as $value){
    $data['title'][]=$value['var1'];
    $data['value'][]=$value['var2'];
    if($value['var2']!=0){
        // si todos los valores están a 0, el piegraph da el siguiente error 'Illegal pie plot. Sum of all data is zero for pie plot'
        $temp_activado=true;
    }
}
//
require_once ('ossim_conf.inc');
$conf = $GLOBALS["CONF"];
$jpgraph = $conf->get_conf("jpgraph_path");
require_once "$jpgraph/jpgraph.php";
require_once "$jpgraph/jpgraph_pie.php";
require_once "$jpgraph/jpgraph_pie3d.php";
// Setup the graph.
$graph = new PieGraph(400, 150, "auto");
$graph->SetAntiAliasing();

$graph->SetColor("#fafafa");
$graph->SetFrame(true,'#fafafa',0);


if(isset($temp_activado)){
    // Create the bar plots
    $piePlot3d = new PiePlot3D($data['value']);
    $piePlot3d->SetSliceColors(array(COLORPIE1,COLORPIE2,COLORPIE3,COLORPIE4,COLORPIE5,COLORPIE6,COLORPIE7,COLORPIE8));
    //$piePlot3d->SetAngle(30);
    $piePlot3d->SetHeight(12);
    $piePlot3d->SetSize(0.5);
    $piePlot3d->SetCenter(0.26,0.40);
    // Labels
    //$piePlot3d->SetLabels($data['title'],1);
    $piePlot3d->SetLegends($data['title']);
    $graph->Add($piePlot3d);
    $graph->legend->SetPos(0.01,0.6,'right','bottom');
}
// Finally send the graph to the browser
$graph->Stroke();
unset($graph);
?>