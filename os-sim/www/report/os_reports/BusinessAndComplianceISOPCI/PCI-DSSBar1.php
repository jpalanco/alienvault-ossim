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
$date_from = (GET('date_from') != "") ? GET('date_from') : strftime("%Y-%m-%d", time() - (24 * 60 * 60 * 30));
$date_to = (GET('date_to') != "") ? GET('date_to') : strftime("%Y-%m-%d", time());

ossim_valid($pci_version, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _('PCI Version'));
ossim_valid($date_from, OSS_DATE, 'illegal:' . _('Date From'));
ossim_valid($date_to, OSS_DATE, 'illegal:' . _('Date To'));
if (ossim_error())
{
    die(ossim_error());
}

$sql_year = "STR_TO_DATE( CONCAT( a.year, '-', a.month, '-', a.day ) , '%Y-%m-%d' ) >= '$date_from' AND STR_TO_DATE( CONCAT( a.year, '-', a.month, '-', a.day ) , '%Y-%m-%d' ) <= '$date_to'";
$user = Session::get_session_user();

require_once ('ossim_db.inc');
$db1 = new ossim_db();
$conn1 = $db1->connect();
tmp_insert($conn1,"PCI$pci_version.R01_FW_Config");
tmp_insert($conn1,"PCI$pci_version.R02_Vendor_default");
tmp_insert($conn1,"PCI$pci_version.R03_Stored_cardholder");
tmp_insert($conn1,"PCI$pci_version.R04_Data_encryption");
tmp_insert($conn1,"PCI$pci_version.R05_Antivirus");
tmp_insert($conn1,"PCI$pci_version.R06_System_app");
tmp_insert($conn1,"PCI$pci_version.R07_Access_control");
tmp_insert($conn1,"PCI$pci_version.R08_UniqueID");
tmp_insert($conn1,"PCI$pci_version.R09_Physical_Access");
tmp_insert($conn1,"PCI$pci_version.R10_Monitoring");
tmp_insert($conn1,"PCI$pci_version.R11_Security_test");
tmp_insert($conn1,"PCI$pci_version.R12_IS_Policy");
$sql="SELECT * FROM ( SELECT * FROM
(select 'R1 Firewall Config','R01_FW_Config', count(*) as volume from datawarehouse.ssi_user a where
a.sid in (SELECT sid from datawarehouse.tmp_user WHERE user='$user' and section='R01_FW_Config') AND a.user='$user' AND ".$sql_year." ) AS A5
UNION SELECT * FROM
(select 'R2 Vendor Default','R02_Vendor_default', count(*) as volume from datawarehouse.ssi_user a where
a.sid in (SELECT sid from datawarehouse.tmp_user WHERE user='$user' and section='R02_Vendor_default') AND a.user='$user' AND ".$sql_year." ) AS A6
UNION SELECT * FROM
(select 'R3 Stored Cardholder','R03_Stored_cardholder', count(*) as volume from datawarehouse.ssi_user a where
a.sid in (SELECT sid from datawarehouse.tmp_user WHERE user='$user' and section='R03_Stored_cardholder') AND a.user='$user' AND ".$sql_year." ) AS A7
UNION SELECT * FROM
(select 'R4 Data Encryption','R04_Data_encryption', count(*) as volume from datawarehouse.ssi_user a where
a.sid in (SELECT sid from datawarehouse.tmp_user WHERE user='$user' and section='R04_Data_encryption') AND a.user='$user' AND ".$sql_year." ) AS A8
UNION SELECT * FROM
(select 'R5 Antivirus','R05_Antivirus', count(*) as volume from datawarehouse.ssi_user a where
a.sid in (SELECT sid from datawarehouse.tmp_user WHERE user='$user' and section='R05_Antivirus') AND a.user='$user' AND ".$sql_year." ) AS A9
UNION SELECT * FROM
(select 'R6 System Appplication','R06_System_app', count(*) as volume from datawarehouse.ssi_user a where
a.sid in (SELECT sid from datawarehouse.tmp_user WHERE user='$user' and section='R06_System_app') AND a.user='$user' AND ".$sql_year." ) AS A10
UNION SELECT * FROM
(select 'R7 Access Control','R07_Access_control', count(*) as volume from datawarehouse.ssi_user a where
a.sid in (SELECT sid from datawarehouse.tmp_user WHERE user='$user' and section='R07_Access_control') AND a.user='$user' AND ".$sql_year." ) AS A11
UNION SELECT * FROM
(select 'R8 Unique ID','R08_UniqueID', count(*) as volume from datawarehouse.ssi_user a where
a.sid in (SELECT sid from datawarehouse.tmp_user WHERE user='$user' and section='R08_UniqueID') AND a.user='$user' AND ".$sql_year." ) AS A12
UNION SELECT * FROM
(select 'R9 Physical Access','R09_Physical_Access', count(*) as volume from datawarehouse.ssi_user a where
a.sid in (SELECT sid from datawarehouse.tmp_user WHERE user='$user' and section='R09_Physical_Access') AND a.user='$user' AND ".$sql_year." ) AS A13
UNION SELECT * FROM
(select 'R10 Monitoring','R10_Monitoring', count(*) as volume from datawarehouse.ssi_user a where
a.sid in (SELECT sid from datawarehouse.tmp_user WHERE user='$user' and section='R10_Monitoring') AND a.user='$user' AND ".$sql_year." ) AS A14
UNION SELECT * FROM
(select 'R11 Security Tests','R11_Security_test', count(*) as volume from datawarehouse.ssi_user a where
a.sid in (SELECT sid from datawarehouse.tmp_user WHERE user='$user' and section='R11_Security_test') AND a.user='$user' AND ".$sql_year." ) AS A15
UNION SELECT * FROM
(select 'R12 IS Policy','R12_IS_Policy', count(*) as volume from datawarehouse.ssi_user a where
a.sid in (SELECT sid from datawarehouse.tmp_user WHERE user='$user' and section='R12_IS_Policy') AND a.user='$user' AND ".$sql_year." ) AS A15
) AS alliso;";

$rs = $conn1->Execute($sql);

if (!$rs) {
    print $conn1->ErrorMsg();
}
$var_dss=array();
while (!$rs->EOF) {
    $var1 = $rs->fields[0];
    $var2 = $rs->fields[1];
    $var3 = $rs->fields["volume"];
    $var_dss[]=array(
        'var1'=>$var1,
        'var2'=>$var2,
        'var3'=>$var3
    );
    $rs->MoveNext();
}
$db1->close($conn1);

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

$data=array();

foreach ($var_dss as $value){
    $data['title'][]=$value['var1'];
    $data['value'][]=$value['var3'];
}
//
require_once ('ossim_conf.inc');
$conf = $GLOBALS["CONF"];
$jpgraph = $conf->get_conf("jpgraph_path");
require_once "$jpgraph/jpgraph.php";
require_once "$jpgraph/jpgraph_bar.php";
// Setup the graph.
$graph = new Graph(640, 250, "auto");
$graph->SetScale("textlin");
$graph->Set90AndMargin(150,10,20,0);

$graph->xaxis->SetTickLabels($data['title']);
$graph->SetColor("#fafafa");
$graph->SetMarginColor("#fafafa");
$graph->SetFrame(true,'#fafafa',0);

// Create the bar plots
$b1plot = new BarPlot($data['value']);
// color@transparencia
$b1plot->SetFillColor(array(COLOR1."@0.5",COLOR2."@0.5",COLOR3."@0.5",COLOR4."@0.5",COLOR5."@0.5",COLOR6."@0.5",COLOR7."@0.5",COLOR8."@0.5",COLOR9."@0.5",COLOR10."@0.5",COLOR11."@0.5",COLOR12."@0.5"));
//
$b1plot->SetShadow(array(COLOR1."@0.7",COLOR2."@0.7",COLOR3."@0.7",COLOR4."@0.7",COLOR5."@0.7",COLOR6."@0.7",COLOR7."@0.7",COLOR8."@0.7",COLOR9."@0.7",COLOR10."@0.7",COLOR11."@0.7",COLOR12."@0.7"),3,5);
$b1plot->SetColor(array(COLOR1."@1",COLOR2."@1",COLOR3."@1",COLOR4."@1",COLOR5."@1",COLOR6."@1",COLOR7."@1",COLOR8."@1",COLOR9."@1",COLOR10."@1",COLOR11."@1",COLOR12."@1"));
//
$graph->Add($b1plot);
// Finally send the graph to the browser
$graph->Stroke();
unset($graph);
?>
