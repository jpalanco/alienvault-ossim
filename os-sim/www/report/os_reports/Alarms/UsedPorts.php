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
    $htmlPdfReport->pageBreak();
	$htmlPdfReport->setBookmark($title);

    // Return the list of ports with max occurrences
    $plugin_groups = NULL;
    $assets        = array();
    $source_type   = NULL;
    $category      = NULL;
    $subcategory   = NULL;
    $sensors       = array();
        
	$list = $security_report->Ports($num_hosts, $report_type, $date_from, $date_to, $assets, $source_type, $category, $subcategory, $plugin_groups, FALSE, $sensors, 'DESC');
    
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
        <table class="w100" cellpadding="0" cellspacing="0" align="center">
            <tr>
                <td style="width:80mm;" valign="top">
                    <table style="width:80mm; padding-top: 10px; padding-bottom: 10px;">
                        <tr>
                            <th>'._('Port').'</th>
                            <th>'._('Service').'</th>
                            <th class="center">'._('Occurrences').'</th>
                        </tr>');
    
                        $c = 0;
                        
                        $shared_file = $dDB['_shared']->dbfile();
                        $dDB['_shared']->put('SA_UsedPorts'.$runorder, $list);                     
                        
                        if(count($list) <= 20)        
                        {
                            $font_size = 14;
                        }
                        else if(count($list) <= 30)   
                        {
                            $font_size = 12;
                        }
                        else
                        {                     
                            $font_size = 8;
                        }                       

                        foreach($list as $l) 
                        {
                            $port        = $l[0];
                            $service     = $l[1];
                            $occurrences = number_format($l[2], 0, ',', '.');

                            $bc = ($c++%2 != 0) ? "class='par'" : '';
                            
                            $htmlPdfReport->set('
                                <tr '.$bc.'>
                                    <td style="width:12mm;font-size:'.$font_size.'px">'.$port.'</td>
                                    <td style="width:38mm;font-size:'.$font_size.'px">'.Util::wordwrap($service, 21, ' ', TRUE).'</td>
                                    <td style="width:22mm;text-align:center;font-size:'.$font_size.'px">'.$occurrences.'</td>
                                </tr>');
                        }
                        
            $htmlPdfReport->set('
                    </table>
                </td>
                
                <td valign="top" style="padding-top:15px; width:98mm;">');
                
                if ($report_graph_type == 'applets')
                {
                    jgraph_ports_graph();
                } 
                else
                {
                    $htmlPdfReport->set('<img src="'.$htmlPdfReport->newImage('/report/graphs/ports_graph.php?shared='.urlencode($shared_file).'&ports='.$NUM_HOSTS.'&type='.$report_type.'&date_from='.urlencode($date_from).'&date_to='.urlencode($date_to).'&runorder='.$runorder,'png').'" />');
                }

    $htmlPdfReport->set('
                </td>
            </tr>
        </table><br/><br/>');
} 
?>
