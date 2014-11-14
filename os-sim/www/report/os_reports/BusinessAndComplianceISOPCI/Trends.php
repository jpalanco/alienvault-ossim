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
    require_once 'common.php';
    include 'general.php';
    
    $date_from = explode('-', $date_from);
    $date_to   = explode('-', $date_to);
    $year      = $date_from[0];
    $year_to   = $date_to[0];


    // GET YEAR RANGE
    $year_range = array();
    
    foreach (range($year, $year_to) as $y) {
        $year_range[]=$y;
    }


    include_once('updateBd.php');

    $htmlPdfReport->pageBreak();
    $htmlPdfReport->setBookmark($title);
    $htmlPdfReport->set($htmlPdfReport->newTitle($title, "", "", null));
    
    $htmlPdfReport->set('<table align="center" width="750" cellpadding="0" cellspacing="0">');
        

    foreach($year_range as $year)
    { 
        $htmlPdfReport->set('
            <tr>
                <td valign="top" class="nobborder">
                    <table width="100%" class="nobborder">
                        <tr>
                            <th style="width:187mm;text-align: left;padding-left: 10px">'._("Trends impacting threat by Month").' ('.$year.') </th>
                        </tr>
                        <tr>
                              <td style="text-align:center" valign="top" class="nobborder"><img src="'.$htmlPdfReport->newImage('/report/BusinessAndComplianceISOPCI/TrendsLine2.php?sess=1&year='.$year,'png').'" /></td>
                        </tr>
                    </table>
                </td>
            </tr> 
           
            <tr>
                <td valign="top" class="nobborder">
                    <table width="100%" class="nobborder">
                        <tr>
                            <th style="width:187mm;text-align: left;padding-left: 10px">'._("Trends potential C.I.A. impacts by Month").' ('.$year.') </th>
                        </tr>
                        <tr>
                            <td style="text-align:center" valign="top" class="nobborder"><img src="'.$htmlPdfReport->newImage('/report/BusinessAndComplianceISOPCI/TrendsLine3.php?sess=1&year='.$year,'png').'" /></td>
                        </tr>
                    </table>
                </td>
            </tr>
                   
            <tr>
                <td valign="top" class="nobborder">
                    <table width="100%" class="nobborder">
                        <tr>
                            <th style="width:187mm;text-align: left;padding-left: 10px">'._("Trends threat type by Month").' ('.$year.') </th>
                        </tr>
                        <tr>
                            <td style="text-align:center" valign="top" class="nobborder"><img src="'.$htmlPdfReport->newImage('/report/BusinessAndComplianceISOPCI/TrendsLine4.php?sess=1&year='.$year,'png').'" /></td>
                        </tr>
                    </table>
                </td>
            </tr>
        ');
    }
    
    $htmlPdfReport->set('</table><br/><br/>');
  
} 

?>