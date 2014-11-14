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


require 'general.php';

if (Session::menu_perms('analysis-menu', 'ReportsAlarmReport')) 
{              
    //Initialize var
    $num_hosts = 15;
    
    $htmlPdfReport->pageBreak();
    $htmlPdfReport->setBookmark($title);
    
    $plugin_groups = NULL;
    $assets        = array();
    $source_type   = NULL;
    $category      = NULL;
    $subcategory   = NULL;
    $sensors       = array();
            
    //Return the event with max occurrences
            
    $list = $security_report->EventsByRisk($num_hosts, $report_type, $date_from, $date_to, $assets, $source_type, $category, $subcategory, $plugin_groups, $sensors, 'DESC');
    
    $htmlPdfReport->set($htmlPdfReport->newTitle($title, $date_from, $date_to, NULL));
    
    if (count($list) == 0) 
    {
        $htmlPdfReport->set('
        <table class="w100" cellpadding="0" cellspacing="0">
            <tr>
                <td class="w100" align="center" valign="top">'._('No data available').'</td>
            </tr>
        </table><br/><br/>');
       
        return;
    }
       
    $htmlPdfReport->set('
        <table class="w100" cellpadding="0" cellspacing="0">
            <tr>
                <td style="padding:15px 0px 0px 0px;width:100%" valign="top">
                    <table class="w100">
                        <tr>');
         
                            $htmlPdfReport->set('<th>'._('Alarm').'</th>');
                            $htmlPdfReport->set('<th class="center" >'._('Risk').'</th></tr>');
           
                            $c = 0;
                            
                            foreach($list as $l) 
                            {
                                $event = $l[0];
                                $risk  = $l[1];

                                $bc = ($c++%2!=0) ? "class='par'" : "";
                                                                                                
                                $htmlPdfReport->set('<tr '.$bc.'>
                                    <td style="text-align:left;width:68%">'.Util::wordwrap(Util::htmlentities(Util::signaturefilter($event)), 70, ' ', TRUE).'</td>
                                    <td nowrap="nowrap" class="left" style="width:32%">'.echo_risk($risk,1).'</td></tr>');
                            }
                            
    $htmlPdfReport->set('</table>
                </td>
            </tr>
        </table><br/>');
} 
?>