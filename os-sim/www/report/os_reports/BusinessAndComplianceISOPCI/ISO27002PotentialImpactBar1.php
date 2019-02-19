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
$db = new ossim_db();
$conn = $db->connect();
$conn->Execute('use datawarehouse');

$sql="SELECT * FROM ( SELECT * FROM
(select 'A05_Security_Policy', count(*) as volume from datawarehouse.ssi_user a, datawarehouse.iso27001sid i
where a.sid=i.sid AND a.user = '".$user."' AND ".$sql_year." and i.ref IN
(SELECT ref from ISO27001An.A05_Security_Policy)) AS A5
UNION SELECT * FROM
(select 'A06_IS_Organization', count(*) as volume from datawarehouse.ssi_user a, datawarehouse.iso27001sid i
where a.sid=i.sid AND a.user = '".$user."' AND ".$sql_year." and i.ref IN
(SELECT ref from ISO27001An.A06_IS_Organization)) AS A6
UNION SELECT * FROM
(select 'A07_Asset_Mgnt', count(*) as volume from datawarehouse.ssi_user a, datawarehouse.iso27001sid i where
a.sid=i.sid AND a.user = '".$user."' AND ".$sql_year." and i.ref IN
(SELECT ref from ISO27001An.A07_Asset_Mgnt)) AS A7
UNION SELECT * FROM
(select 'A08_Human_Resources', count(*) as volume from datawarehouse.ssi_user a, datawarehouse.iso27001sid i
where a.sid=i.sid AND a.user = '".$user."' AND ".$sql_year." and i.ref IN
(SELECT ref from ISO27001An.A08_Human_Resources)) AS A8
UNION SELECT * FROM
(select 'A09_Physical_security', count(*) as volume from datawarehouse.ssi_user a, datawarehouse.iso27001sid i
where a.sid=i.sid AND a.user = '".$user."' AND ".$sql_year." and i.ref IN
(SELECT ref from ISO27001An.A09_Physical_security)) AS A9
UNION SELECT * FROM
(select 'A10_Com_OP_Mgnt', count(*) as volume from datawarehouse.ssi_user a, datawarehouse.iso27001sid i
where a.sid=i.sid AND a.user = '".$user."' AND ".$sql_year." and i.ref IN
(SELECT ref from ISO27001An.A10_Com_OP_Mgnt)) AS A10
UNION SELECT * FROM
(select 'A11_Acces_control', count(*) as volume from datawarehouse.ssi_user a, datawarehouse.iso27001sid i where
a.sid=i.sid AND a.user = '".$user."' AND ".$sql_year." and i.ref IN
(SELECT ref from ISO27001An.A11_Acces_control)) AS A11
UNION SELECT * FROM
(select 'A12_IS_acquisition', count(*) as volume from datawarehouse.ssi_user a, datawarehouse.iso27001sid i where
a.sid=i.sid AND a.user = '".$user."' AND ".$sql_year." and i.ref IN
(SELECT ref from ISO27001An.A12_IS_acquisition)) AS A12
UNION SELECT * FROM
(select 'A13_IS_incident_mgnt', count(*) as volume from datawarehouse.ssi_user a, datawarehouse.iso27001sid i
where a.sid=i.sid AND a.user = '".$user."' AND ".$sql_year." and i.ref IN
(SELECT ref from ISO27001An.A13_IS_incident_mgnt)) AS A13
UNION SELECT * FROM
(select 'A14_BCM', count(*) as volume from datawarehouse.ssi_user a, datawarehouse.iso27001sid i where
a.sid=i.sid AND a.user = '".$user."' AND ".$sql_year." and i.ref IN
(SELECT ref from ISO27001An.A14_BCM)) AS A14
UNION SELECT * FROM
(select 'A15_Compliance', count(*) as volume from datawarehouse.ssi_user a, datawarehouse.iso27001sid i where
a.sid=i.sid AND a.user = '".$user."' AND ".$sql_year." and i.ref IN
(SELECT ref from ISO27001An.A15_Compliance)) AS A15
) AS alliso;";

$rs = $conn->Execute($sql);

if (!$rs) {
    print $conn->ErrorMsg();
    return;
}
// test perms for source or destination ips
$var=array();
while (!$rs->EOF) {
    $var1 = $rs->fields["A05_Security_Policy"];
    $var2 = $rs->fields["volume"];
    $var[]=array(
        'var1'=>$var1,
        'var2'=>$var2
    );
    $rs->MoveNext();
}
$db->close($conn);
// creamos dos grupos y añadimos sus valores
$data=array();

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
$graph = new Graph(600, 250, "auto");
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