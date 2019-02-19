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

if ( Session::menu_perms("report-menu", "ReportsReportServer") )
{
    include_once 'updateBd.php';
    require_once 'common.php';
    include 'general.php';

    $sql_year   = "STR_TO_DATE( CONCAT( a.year, '-', a.month, '-', a.day ) , '%Y-%m-%d' ) >= '$date_from' AND STR_TO_DATE( CONCAT( a.year, '-', a.month, '-', a.day ) , '%Y-%m-%d' ) <= '$date_to'";

    $conn->Execute('use datawarehouse');

    $sql="SELECT * FROM ( SELECT * FROM
    (select s.service as service, 'QoS-Impact' as category, sum(c.imp_qos) as volume from datawarehouse.ssi_user a,
    datawarehouse.category c, datawarehouse.ip2service s
    where c.imp_qos <> '0' and a.sid=c.sid and a.destination=s.dest_ip and ".$sql_year." AND a.user='".$user."'
    GROUP BY 2) as imp_qos
    UNION SELECT * FROM
    (select s.service as service, 'Information-Leak-Impact' as category, sum(c.imp_infleak) as volume from
    datawarehouse.ssi_user a, datawarehouse.category c, datawarehouse.ip2service s
    where c.imp_infleak <> '0' and a.sid=c.sid and a.destination=s.dest_ip and ".$sql_year." AND a.user='".$user."'
    GROUP BY 2) as imp_infleak
    UNION SELECT * FROM
    (select s.service as service, 'Lawful-Impact' as category, sum(c.imp_lawful) as volume from datawarehouse.ssi_user
    a, datawarehouse.category c, datawarehouse.ip2service s
    where c.imp_lawful <> '0' and a.sid=c.sid and a.destination=s.dest_ip and ".$sql_year." AND a.user='".$user."'
    GROUP BY 2) as imp_lawful
    UNION SELECT * FROM
    (select s.service as service, 'Enterprise-Image-Impact' as category, sum(c.imp_image) as volume from
    datawarehouse.ssi_user a, datawarehouse.category c, datawarehouse.ip2service s
    where c.imp_image <> '0' and a.sid=c.sid and a.destination=s.dest_ip and ".$sql_year." AND a.user='".$user."'
    GROUP BY 2) as imp_image
    UNION SELECT * FROM
    (select s.service as service, 'Financial-Impact' as category, sum(c.imp_financial) as volume from
    datawarehouse.ssi_user a, datawarehouse.category c, datawarehouse.ip2service s
    where c.imp_financial <> '0' and a.sid=c.sid and a.destination=s.dest_ip and ".$sql_year." AND a.user='".$user."'
    GROUP BY 2) as imp_financial
    ) AS allalarms;";

    $rs = $conn->Execute($sql);

    if (!$rs) {
        print $conn->ErrorMsg();
        return;
    }

    // test perms for source or destination ips
    $var = array();

    while ( !$rs->EOF )
    {
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

    $htmlPdfReport->pageBreak();
    $htmlPdfReport->setBookmark($title);

    $htmlPdfReport->set($htmlPdfReport->newTitle($title, "", "", null));

    if( count($var) == 0 )
    {
        $htmlPdfReport->set('<table class="w100" cellpadding="0" cellspacing="0">
            <tr><td class="w100" align="center" valign="top">'._("No data available").'</td></tr></table><br/><br/>');
        return;
    }

    $htmlPdfReport->set('
        <table align="center" width="750">
            <tr>
                <td colspan="2" style="padding-top:15px;text-align: center" valign="top" class="nobborder"><img src="'.$htmlPdfReport->newImage('/report/os_reports/BusinessAndComplianceISOPCI/BusinessPotentialImpactsRisksBar1.php?date_from='.urlencode($date_from).'&date_to='.urlencode($date_to).'&sess=1','png').'" /></td>
            </tr>
          <tr>
              <td style="padding-top:30px;width: 300px" valign="top" class="nobborder">
                    <table align="center">');

                    $c=0;

                    foreach($var as $value)
                    {
                        $htmlPdfReport->set('
                        <tr>
                            <th style="width:50mm">'.$value['var1'].'</th>
                            <td>'.$value['var2'].'</td>
                        </tr>');
                    }

                    $htmlPdfReport->set('
                    </table>
                </td>
                <td style="padding-top:15px; text-align: center" valign="top" class="nobborder">
                    <img src="'.$htmlPdfReport->newImage('/report/os_reports/BusinessAndComplianceISOPCI/BusinessPotentialImpactsRisksPie1.php?date_from='.urlencode($date_from).'&date_to='.urlencode($date_to).'&sess=1','png').'" />
                </td>
            </tr>
        </table><br /><br />');
}
?>
