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

    $htmlPdfReport->set('<table align="center" width="750">
        <tr style="width:187mm;">
                <td valign="top" class="nobborder">
                    <table width="100%" class="nobborder">');

    $htmlPdfReport->set('<tr>
                            <th colspan="2" style="width:187mm;text-align: left;padding-left: 10px">'._("Potential impacts - risks").'</th>
                        </tr>');

    $htmlPdfReport->set('<tr>
                            <td valign="top" style="padding-top:15px;text-align:center" valign="top" class="nobborder"><img src="'.$htmlPdfReport->newImage('/RadarReport/radar-iso27001-potential.php?date_from='.urlencode($date_from).'&date_to='.urlencode($date_to),'png','root').'" width="360" /></td>
                            <td valign="top" style="padding-top:15px;text-align:center" valign="top" class="nobborder"><img src="'.$htmlPdfReport->newImage('/RadarReport/radar-iso27001-A10Com_OP_Mgnt-pot.php?date_from='.urlencode($date_from).'&date_to='.urlencode($date_to),'png','root').'" width="360" /></td>
                        </tr>');


    $htmlPdfReport->set('<tr>
                            <td valign="top" style="padding-top:15px;text-align:center" valign="top" class="nobborder"><img src="'.$htmlPdfReport->newImage('/RadarReport/radar-iso27001-A11AccessControl-pot.php?date_from='.urlencode($date_from).'&date_to='.urlencode($date_to),'png','root').'" width="360" /></td>
                            <td valign="top" style="padding-top:15px;text-align:center" valign="top" class="nobborder"><img src="'.$htmlPdfReport->newImage('/RadarReport/radar-iso27001-A12IS_acquisition-pot.php?date_from='.urlencode($date_from).'&date_to='.urlencode($date_to),'png','root').'" width="360" /></td>
                        </tr>');

    $htmlPdfReport->set('
                </table>
            </td>
        </tr>
    </table>');

    $htmlPdfReport->pageBreak();

    $htmlPdfReport->set('<table align="center" width="750">
                            <tr style="width:190mm;">
                                <td valign="top" class="nobborder" style="width:190mm;">
                                    <table width="100%" class="nobborder">');

                    $htmlPdfReport->set('<tr>
                                            <th colspan="2" style="width:187mm; text-align: left;padding-left: 10px">'._("Potential impacts - risks").'</th>
                                        </tr>');

                    $htmlPdfReport->set('<tr>
                                            <td valign="top" style="padding-top:15px;text-align:center" valign="top" class="nobborder"><img src="'.$htmlPdfReport->newImage('/report/os_reports/BusinessAndComplianceISOPCI/ISO27002PotentialImpactBar1.php?shared='.urlencode($shared_file).'&sess=1','png').'" /></td>
                                        </tr>');


                $htmlPdfReport->set('</table>
                                </td>
                            </tr>
                        </table><br/><br/>');

}
?>
