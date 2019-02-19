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


require_once ('av_init.php');
if ( Session::menu_perms("report-menu", "ReportsReportServer"))
{
    include_once 'updateBd.php';
    require_once 'common.php';
    include 'general.php';
    
    /*
     * PCI Version, if 3.0 then this variable is predefined in PCI-DSS3.php
     * The code is shared with this only diference
     */
    $pci_version = ($pci_version != '') ? $pci_version : '';

    $sql_year = "STR_TO_DATE( CONCAT( a.year, '-', a.month, '-', a.day ) , '%Y-%m-%d' ) >= '$date_from' AND STR_TO_DATE( CONCAT( a.year, '-', a.month, '-', a.day ) , '%Y-%m-%d' ) <= '$date_to'";

    //create
    require_once ('ossim_db.inc');
    $db1   = new ossim_db();
    $conn1 = $db1->connect();
    
    // Check if PCI database exists
    if (!pci_database_available($conn1, "PCI$pci_version"))
    {
        $htmlPdfReport->pageBreak();
        $htmlPdfReport->setBookmark($title);
        $htmlPdfReport->set($htmlPdfReport->newTitle($title, "", "", null));
    
        $htmlPdfReport->set('<table align="center" width="750" cellpadding="0" cellspacing="0"><tr><td>'
                           ._('Database not found').': PCI'.$pci_version.'</td></tr></table><br/><br/>');
        $db1->close();
    }
    else
    {
    
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

    $htmlPdfReport->pageBreak();
    $htmlPdfReport->setBookmark($title);
    $htmlPdfReport->set($htmlPdfReport->newTitle($title, "", "", null));

    $htmlPdfReport->set('<table align="center" width="750" cellpadding="0" cellspacing="0">');

    $htmlPdfReport->set('<tr>
                            <td valign="top" class="nobborder" style="margin:0;padding:10px 0 0 0">
                                <table class="nobborder">
                                    <tr>
                                        <th style="width:190mm;text-align: left; padding: 2px 0 2px 10px">'._("Potential impacts").'</th>
                                    </tr>
                                    <tr>
                                        <td valign="top" class="nobborder" style="padding-top:15px;text-align: center"><img src="'.$htmlPdfReport->newImage('/RadarReport/radar-pci-potential.php?sess=1&pci_version='.$pci_version.'&date_from='.urlencode($date_from).'&date_to='.urlencode($date_to).'','png','root').'" width="400" /></td>
                                    </tr>
                                    <tr>
                                        <td style="padding-top:15px;text-align: center" valign="top" class="nobborder"><img src="'.$htmlPdfReport->newImage('/report/os_reports/BusinessAndComplianceISOPCI/PCI-DSSBar1.php?pci_version='.$pci_version.'&date_from='.urlencode($date_from).'&date_to='.urlencode($date_to).'&sess=1','png').'" /></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>');


    $htmlPdfReport->set('<tr>
                            <td valign="top" class="nobborder" style="margin:0;padding:0">');


    $htmlPdfReport->set('<table class="nobborder">
                                <tr style="border: solid 1px #333">
                                    <th style="width:190mm;text-align: left;padding-left: 10px">'._("Details").'</th>
                                </tr>');



    $where = "STR_TO_DATE( CONCAT( i.year, '-', i.month, '-', i.day ) , '%Y-%m-%d' ) between '$date_from' AND '$date_to' AND";

    foreach ($var_dss as $dss_req)
    {
        if ($dss_req["var3"]>0)
        {

            $query = "select i.sid, i.descr, count(*) as count, min(STR_TO_DATE( CONCAT( i.day, ',', i.month, ',', i.year, ' ', i.hour, ':', i.minute, ':00' ) , '%d,%m,%Y %H:%i:%s' )) as mindate, max(STR_TO_DATE( CONCAT( i.day, ',', i.month, ',', i.year, ' ', i.hour, ':', i.minute, ':00' ) , '%d,%m,%Y %H:%i:%s' )) as maxdate ";
            $query .= "from datawarehouse.tmp_user r, datawarehouse.ssi_user i where ".$where." r.user='$user' and section='".$dss_req["var2"]."' AND i.sid=r.sid AND i.user='$user' group by sid, descr order by count DESC ;";
            //echo("<pre>".$query."</pre>");
            if (!$rsdata = & $conn1->query($query)) {
                print $conn1->ErrorMsg();
            }

            $htmlPdfReport->set('<tr>
                                    <td valign="top" class="nobborder">
                                        <table class="nobborder w100">
                                            <tr>
                                                <th style="width:190mm;text-align: left;padding-left: 10px">'._($dss_req["var1"]).'</th>
                                            </tr>
                                            <tr>
                                                <td style="padding-top:15px;text-align: center" valign="top" class="nobborder"><img src="'.$htmlPdfReport->newImage('/report/os_reports/BusinessAndComplianceISOPCI/PCI-DSSBar.php?table='.urlencode($dss_req["var2"]).'&pci_version='.$pci_version.'&date_from='.urlencode($date_from).'&date_to='.urlencode($date_to).'&sess=1','png').'" /></td>
                                            </tr>
                                            <tr>
                                                <td style="padding-top:15px;text-align: center" valign="top" class="nobborder"><table width="100%" class="nobborder">
                                                    <tr><th>'. _("SID") .'</th><th>'. _("Description") .'</th><th>'. _("Count") .'</th><th>'. _("Start Date") .'</th><th>'. _("Finish Date") .'</th></tr>');

                                                    $rsdata->MoveFirst();

                                                    while (!$rsdata->EOF)
                                                    {
                                                        $descr = (strlen($rsdata->fields["descr"])>60) ? (substr($rsdata->fields["descr"], 0, 55)."...") : $rsdata->fields["descr"] ;
                                                        $htmlPdfReport->set('<tr><td>'.$rsdata->fields["sid"].'</td><td>'.$descr.'</td><td>'.$rsdata->fields["count"].'</td><td>'.$rsdata->fields["mindate"].'</td><td>'.$rsdata->fields["maxdate"].'</td></tr>');
                                                        $rsdata->MoveNext();
                                                    }

                                                    $htmlPdfReport->set('
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>  ');

        }
    }

    $db1->close($conn1);

    $htmlPdfReport->set('</table>');


    $htmlPdfReport->set('
                </td>
            </tr>
        </table><br/><br/>');

    }
}
?>
