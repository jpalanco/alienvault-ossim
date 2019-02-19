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

    $sql_year  = "STR_TO_DATE( CONCAT( a.year, '-', a.month, '-', a.day ) , '%Y-%m-%d' ) >= '$date_from' AND STR_TO_DATE( CONCAT( a.year, '-', a.month, '-', a.day ) , '%Y-%m-%d' ) <= '$date_to'";

	$conn->Execute('use datawarehouse');

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

	$rs = $conn->Execute($sql);

	if (!$rs) {
		print $conn->ErrorMsg();
		return;
	}

	// test perms for source or destination ips
	$var=array();
	while (!$rs->EOF)
    {
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

	$htmlPdfReport->pageBreak();
	$htmlPdfReport->setBookmark($title);
    $htmlPdfReport->set($htmlPdfReport->newTitle($title, "", "", null));

    $htmlPdfReport->set('
        <table align="center" width="750">
            <tr>
                <td colspan="2" style="padding-top:15px;text-align: center" valign="top" class="nobborder"><img src="'.$htmlPdfReport->newImage('/report/os_reports/BusinessAndComplianceISOPCI/ThreatOverviewBar1.php?date_from='.urlencode($date_from).'&date_to='.urlencode($date_to).'&sess=1','png').'" /></td>
            </tr>
            <tr>
                <td style="padding-top:30px;width: 300px;" valign="top" class="nobborder">
                    <table align="center">
                        <tr>
                            <th style="width:33mm;text-align:center">'._("Attack").'</th>
                            <th style="width:30mm;text-align:center">'._("Category").'</th>
                            <th style="width:15mm;text-align:center">'._("Volume").'</th>
                        </tr>');


				$c=0;

                foreach($var as $value)
				{
					$bc = ($c++%2!=0) ? "class='par'" : "";

					$htmlPdfReport->set('<tr '.$bc.'>
                                            <td style="text-align:center">');
					if($value['var3']=='Internal-Attack'){ $htmlPdfReport->set(_('Internal')); }else { $htmlPdfReport->set(_('External')); }

                        $htmlPdfReport->set('</td>
                                            <td style="text-align:center">');
					if($value['var1']=='Targeted-Attack'){ $htmlPdfReport->set(_('Targeted')); }else { $htmlPdfReport->set(_('Untargeted')); }

                        $htmlPdfReport->set('</td><td style="text-align: center">'.$value['var2'].'</td></tr>');
				}

    $htmlPdfReport->set('
				</table>
			</td>
			<td style="padding-top:20px; text-align: center" valign="top" class="nobborder">
				<img src="'.$htmlPdfReport->newImage('/report/os_reports/BusinessAndComplianceISOPCI/ThreatOverviewPie1.php?date_from='.urlencode($date_from).'&date_to='.urlencode($date_to).'&sess=1','png').'" />
			</td>
		</tr>

        <tr>
			<td colspan="2" valign="top" class="nobborder">
                <table width="100%" class="nobborder">
                    <tr>
                        <th style="text-align: left;padding-left: 10px">'._("Threat geolocation").'</th>
                    </tr>
                    <tr>
                          <td valign="top" style="padding-top:15px;text-align: center" class="nobborder">
                          <img src="'.$htmlPdfReport->newImage('/report/graphs/graph_geoloc_threat.php?date_from='.urlencode($date_from).'&date_to='.urlencode($date_to),'png').'" width="720" /></td>
                    </tr>
                </table>
            </td>
        </tr>
	</table><br/><br/>');
}
?>
