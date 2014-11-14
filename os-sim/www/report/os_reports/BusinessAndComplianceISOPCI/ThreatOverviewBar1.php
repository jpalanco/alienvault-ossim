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

$sql_year  = "STR_TO_DATE( CONCAT( a.year, '-', a.month, '-', a.day ) , '%Y-%m-%d' ) >= '$date_from' AND STR_TO_DATE( CONCAT( a.year, '-', a.month, '-', a.day ) , '%Y-%m-%d' ) <= '$date_to'";

require_once ('ossim_db.inc');
$db   = new ossim_db();
$conn = $db->connect();
$user = Session::get_session_user();

$sql="SELECT * FROM ( SELECT * FROM
    (select 'Internal-Attack','Targeted-Attack' as category, count(c.targeted) as volume from datawarehouse.ssi_user a, datawarehouse.category c
    where ".$sql_year." AND a.user='".$user."' AND c.targeted > 0 AND a.sid=c.sid and a.source IN (select dest_ip from datawarehouse.ip2service)) as int_targeted
    UNION SELECT * FROM
    (select 'External-Attack','Targeted-Attack' as category, count(c.targeted) as volume from datawarehouse.ssi_user a, datawarehouse.category c
    where ".$sql_year." AND a.user='".$user."' AND c.targeted > 0 AND a.sid=c.sid and a.source NOT IN (select dest_ip from datawarehouse.ip2service)) as ext_targeted
    UNION SELECT * FROM
    (select 'Internal-Attack','Untargeted-Attack' as category, count(c.untargeted) as volume from datawarehouse.ssi_user a, datawarehouse.category c
    where ".$sql_year." AND a.user='".$user."' AND c.untargeted > 0 AND a.sid=c.sid and a.source IN (select dest_ip from datawarehouse.ip2service)) as int_untargeted
    UNION SELECT * FROM
    (select 'External-Attack','Untargeted-Attack' as category, count(c.untargeted) as volume from datawarehouse.ssi_user a, datawarehouse.category c
    where ".$sql_year." AND a.user='".$user."' AND c.untargeted > 0 AND a.sid=c.sid and a.source NOT IN (select dest_ip from datawarehouse.ip2service)) as ext_untargeted
) AS allalarms;";

if (!$rs = & $conn->Execute($sql)) {
    print $conn->ErrorMsg();
    return;
}
// test perms for source or destination ips
$var=array();
while (!$rs->EOF) {
    $var1 = $rs->fields["category"];
	$var2 = $rs->fields["volume"];
    $var3 = $rs->fields["Internal-Attack"];
    $var[]=array(
        'var1'=>$var1,
        'var2'=>$var2,
        'var3'=>$var3
    );
    $rs->MoveNext();
}
$db->close($conn);
$var_port = $var;

// define colors
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
//
// creamos dos grupos y añadimos sus valores
$dataValue1=array();
$dataValue2=array();
$groupTit1=_('Targeted-Attack');
$groupTit2=_('Untargeted-Attack');
$groupType1=_('Internal-Attack');
$groupType2=_('External-Attack');
foreach ($var_port as $value){
    if($value['var3']=='Internal-Attack'){
        $dataValue1[]=$value['var2'];
    }else{
        $dataValue2[]=$value['var2'];
    }
}
//
require_once ('ossim_conf.inc');
$conf = $GLOBALS["CONF"];
$jpgraph = $conf->get_conf("jpgraph_path");
require_once "$jpgraph/jpgraph.php";
require_once "$jpgraph/jpgraph_bar.php";
// Setup the graph.
$graph = new Graph(600, 250, "auto");
$graph->SetScale("textlin");
$graph->Set90AndMargin(0,10,20,0);
$graph->SetColor("#fafafa");
$graph->SetMarginColor("#fafafa");

$graph->xaxis->SetTickLabels(array($groupTit1,$groupTit2));
$graph->SetFrame(true,'#fafafa',0);
// Create the bar plots
$b1plot = new BarPlot($dataValue1);
// color@transparencia
$b1plot->SetFillColor(COLOR1."@0.5");
// Legend
$b1plot->SetLegend($groupType1);
//
$b1plot->SetShadow(COLOR1."@0.7",5,5);
$b1plot->SetColor(COLOR1."@1");


//
$b2plot = new BarPlot($dataValue2);
$b2plot->SetFillColor(COLOR2."@0.5");
// Legend
$b2plot->SetLegend($groupType2);
//
$b2plot->SetShadow(COLOR2."@0.7",5,5);
$b2plot->SetColor(COLOR2."@1");
// Legends
$graph->legend->SetPos(0.5,0.97,'center','bottom');
$graph->legend->SetShadow('#fafafa',0);
$graph->legend->SetFrameWeight(0);
$graph->legend->SetFillColor('#fafafa');
$graph->legend->SetColumns(2);
//
$graph->Add(new GroupBarPlot(array($b1plot,$b2plot)));
// Finally send the graph to the browser
$graph->Stroke();
unset($graph);
?>