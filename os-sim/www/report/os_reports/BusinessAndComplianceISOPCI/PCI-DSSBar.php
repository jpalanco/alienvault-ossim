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
require_once 'common.php';

Session::logcheck("report-menu", "ReportsReportServer");

$pci_version = GET('pci_version');
$table     =  GET('table');
$date_from = (GET('date_from') != "") ? GET('date_from') : strftime("%Y-%m-%d", time() - (24 * 60 * 60 * 30));
$date_to   = (GET('date_to') != "")   ? GET('date_to')   : strftime("%Y-%m-%d", time());

ossim_valid($pci_version, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _('PCI Version'));
ossim_valid($date_from, OSS_DATE, 'illegal:' . _('Date From'));
ossim_valid($date_to, OSS_DATE, 'illegal:' . _('Date To'));
ossim_valid($table,     OSS_ALPHA, OSS_SCORE, 'illegal:' . _('Table'));

if (ossim_error())
{
    die(ossim_error());
}

$sql_year  = "STR_TO_DATE( CONCAT( i.year, '-', i.month, '-', i.day ) , '%Y-%m-%d' ) >= '$date_from' AND STR_TO_DATE( CONCAT( i.year, '-', i.month, '-', i.day ) , '%Y-%m-%d' ) <= '$date_to'";

$user = Session::get_session_user();

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
require_once ('ossim_db.inc');
$db   = new ossim_db();
$conn = $db->connect();

$conn->Execute('use datawarehouse');
/*
$sql="select CONCAT(x1,'.',x2,'.',x3) as title, count(*) as volume from PCI.R02_Vendor_default r, datawarehouse.ssi_user i where
i.sid=r.SIDSS_Ref AND i.user='".$user."' AND i.year=".$year." GROUP BY 1";
*/

tmp_insert($conn,"PCI$pci_version.$table");

$sql="select r.req as title, count(*) as volume from datawarehouse.tmp_user r, datawarehouse.ssi_user i where r.user='$user' and section='$table' AND i.sid=r.sid AND i.user='$user' AND ".$sql_year." GROUP BY 1;";

$rs = $conn->Execute($sql);

if (!$rs) {
    print $conn->ErrorMsg();
    return;
}
// test perms for source or destination ips
$var=array();
$rows = 0;
while (!$rs->EOF) {
    $var1 = $rs->fields["title"];
    $var2 = $rs->fields["volume"];
    $var[]=array(
        'var1'=>$var1,
        'var2'=>$var2
    );
    $rs->MoveNext();
    $rows++;
}
$db->close($conn);
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
}
//
require_once ('ossim_conf.inc');
$conf = $GLOBALS["CONF"];
$jpgraph = $conf->get_conf("jpgraph_path");
require_once "$jpgraph/jpgraph.php";
require_once "$jpgraph/jpgraph_bar.php";
// Setup the graph.
if ($rows==0) $rows=1;
$graph = new Graph(600, ($rows*30)+35, "auto");
$graph->SetScale("textlin");
$graph->Set90AndMargin(150,10,20,0);

$graph->SetMarginColor("#fafafa");

$graph->xaxis->SetTickLabels($data['title']);

$graph->SetColor("#fafafa");
$graph->SetFrame(true,'#fafafa',0);

// Create the bar plots
$b1plot = new BarPlot($data['value']);
// color@transparencia
$b1plot->SetFillColor(array(COLOR1."@0.5",COLOR2."@0.5",COLOR3."@0.5",COLOR4."@0.5",COLOR5."@0.5",COLOR6."@0.5",COLOR7."@0.5",COLOR8."@0.5",COLOR9."@0.5",COLOR10."@0.5",COLOR11."@0.5",COLOR12."@0.5"));
//
$b1plot->SetShadow(array(COLOR1."@0.7",COLOR2."@0.7",COLOR3."@0.7",COLOR4."@0.7",COLOR5."@0.7",COLOR6."@0.7",COLOR7."@0.7",COLOR8."@0.7",COLOR9."@0.7",COLOR10."@0.7",COLOR11."@0.7",COLOR12."@0.7"),5,5);
$b1plot->SetColor(array(COLOR1."@1",COLOR2."@1",COLOR3."@1",COLOR4."@1",COLOR5."@1",COLOR6."@1",COLOR7."@1",COLOR8."@1",COLOR9."@1",COLOR10."@1",COLOR11."@1",COLOR12."@1"));
//
$graph->Add($b1plot);
// Finally send the graph to the browser
$graph->Stroke();
unset($graph);
?>