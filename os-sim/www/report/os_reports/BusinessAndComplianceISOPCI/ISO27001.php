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

    $htmlPdfReport->pageBreak();
    $htmlPdfReport->setBookmark($title);
    $htmlPdfReport->set($htmlPdfReport->newTitle($title, "", "", null));

    $htmlPdfReport->set('<table align="center" width="750">');

    $htmlPdfReport->set('<tr>
                            <td valign="top" class="nobborder">
                                <table width="100%" class="nobborder">
                                    <tr>
                                        <th style="width:187mm;text-align: left;padding-left: 10px">'._("Details - A10 Communications and Operations Management").'</th>
                                    </tr>
                                    <tr>
                                        <td valign="top" style="padding-top:15px;text-align:center" valign="top" class="nobborder"><img src="'.$htmlPdfReport->newImage('/report/os_reports/BusinessAndComplianceISOPCI/ISO27001Bar1.php?date_from='.urlencode($date_from).'&date_to='.urlencode($date_to).'&sess=1','png').'" /></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>');


    $htmlPdfReport->set('<tr>
                            <td valign="top" class="nobborder">
                                <table width="100%" class="nobborder">
                                    <tr>
                                        <th style="width:187mm;text-align: left;padding-left: 10px">'._("Details - A11 Access Control").'</th>
                                    </tr>
                                    <tr>
                                        <td valign="top" style="padding-top:15px;text-align:center" valign="top" class="nobborder"><img src="'.$htmlPdfReport->newImage('/report/os_reports/BusinessAndComplianceISOPCI/ISO27001Bar2.php?date_from='.urlencode($date_from).'&date_to='.urlencode($date_to).'&sess=1','png').'" /></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>');

    $htmlPdfReport->set('<tr>
                            <td valign="top" class="nobborder">
                                <table width="100%" class="nobborder">
                                    <tr>
                                        <th style="width:187mm;text-align: left;padding-left: 10px">'._("Details - A12 Information System Acquisition").'</th>
                                    </tr>
                                    <tr>
                                        <td valign="top" style="padding-top:15px;text-align:center" valign="top" class="nobborder"><img src="'.$htmlPdfReport->newImage('/report/os_reports/BusinessAndComplianceISOPCI/ISO27001Bar3.php?date_from='.urlencode($date_from).'&date_to='.urlencode($date_to).'&sess=1','png').'" /></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>');

    $htmlPdfReport->set('</table><br /><br />');
}
?>
