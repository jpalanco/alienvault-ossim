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
require_once 'classes/Util.inc';


if ( Session::menu_perms("analysis-menu", "EventsForensics") ) 
{

    // Initialize var
    $num_hosts = 15;
    
    $htmlPdfReport->pageBreak();
    $htmlPdfReport->setBookmark($title);
    
    
    $plugin_groups = null;
    $assets        = array();
    $source_type   = null;
    $category      = null;
    $subcategory   = null;
    $sensors       = array();
    
    //Return the event with max occurrences
    $list = $security_report->Events($num_hosts, $report_type, $date_from, $date_to, $assets, $source_type, $category, $subcategory, $plugin_groups, false, $sensors, "DESC");
    
    $htmlPdfReport->set($htmlPdfReport->newTitle($title, $date_from, $date_to, null));
    
    if ( count($list) == 0 ) 
    {
        $htmlPdfReport->set('
        <table class="w100" cellpadding="0" cellspacing="0">
            <tr>
                <td class="w100" align="center" valign="top">'._("No data available").'</td>
            </tr>
        </table><br/><br/>');
       
        return;
    }
       
    $htmlPdfReport->set('
        <table class="w100" cellpadding="0" cellspacing="0">
            <tr>
                <td style="padding:15px 0px 0px 0px;width:30%" valign="top">
                    <table>
                        <tr>');
         
                            $htmlPdfReport->set('<th>'._("Event").'</th>');
                            $htmlPdfReport->set('<th class="center">'.gettext("Occurrences").'</th></tr>');
           
                            $c=0;
                            
                            $shared_file = $dDB["_shared"]->dbfile();
                            $dDB["_shared"]->put("SS_TopEvents".$runorder,$list);
                            
                            foreach($list as $l) 
                            {
                                $event       = $l[0];
                                $occurrences = number_format($l[1], 0, ",", ".");
                          
                                $link = "$acid_link/" . $acid_prefix . "_qry_main.php?new=1&" . "sig[0]==&" . "sig[1]=" . urlencode($event) . "&" . "sig[2]==&" . "submit=Query+DB&" . "num_result_rows=-1&" . "sort_order=time_d";

                               
                                $bc = ($c++%2!=0) ? "class='par'" : "";
                                
                          
                                $font_size = getFontSizeSIEM($list);
                            
                                $htmlPdfReport->set('
                                    <tr '.$bc.'>
                                        <td style="text-align:left;width:60mm;font-size:'.$font_size.'px">'.Util::wordwrap(Util::htmlentities(Util::signaturefilter($event)),30," ",true).'</td>
                                        <td style="text-align:center;width:22mm;font-size:'.$font_size.'px">'.$occurrences.'</td>
                                    </tr>');
                                
                            }

                $htmlPdfReport->set('
                        </table>
                    </td>
                <td valign="top" style="text-align:center;padding-top:15px;">');
                            

                if ($report_graph_type == "applets") {
                    jgraph_nbevents_graph();
                } 
                else{
                    $htmlPdfReport->set('<img src="'.$htmlPdfReport->newImage('/report/graphs/events_received_graph.php?shared='.urlencode($shared_file).'&hosts='.$num_hosts.'&type='.$report_type.'&date_from='.urlencode($date_from).'&date_to='.urlencode($date_to).'&runorder='.$runorder,'png').'" />');
                }

    $htmlPdfReport->set('
                </td>
            </tr>
        </table><br/><br/>');
       
} 
?>