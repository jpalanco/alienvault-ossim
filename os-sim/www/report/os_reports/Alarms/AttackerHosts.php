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
require 'general.php';

//Initialize var
$target = "src_ip";

$geoloc = new Geolocation('/usr/share/geoip/GeoLiteCity.dat');

if (Session::menu_perms('analysis-menu', 'ReportsAlarmReport')) 
{
    $htmlPdfReport->pageBreak();
    $htmlPdfReport->setBookmark($title);

    /*
    * return the list of host with max occurrences
    * as dest or source
    * pre: type is "ip_src" or "ip_dst"
    */
    $plugin_groups = NULL;
    $assets        = array();
    $source_type   = NULL;
    $category      = NULL;
    $subcategory   = NULL;
    $sensors       = array();
    
	$list = $security_report->AttackHost($target, $num_hosts, $report_type, $date_from, $date_to, $assets, $source_type, $category, $subcategory, $plugin_groups, $sensors);
    
    $htmlPdfReport->set($htmlPdfReport->newTitle($title, $date_from, $date_to, NULL));
    
    if (count($list) == 0) 
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
        <table class="w100" cellpadding="0" cellspacing="0" align="center">
            <tr>
                <td style="width:80mm;" valign="top">
                    <table style="width:80mm; padding-top: 10px; padding-bottom: 10px;">
                        <tr>
                            <th>'._('Host').'</th>
                            <th class="center">'._('Occurrences').'</th>
                        </tr>');
        
 
                        $c = 0;
                        
                        $shared_file = $dDB['_shared']->dbfile();
                        $dDB['_shared']->put('SA_AttackedHost'.$runorder, $list);                        
                       

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
                            $ip          = $l[0];
                            $occurrences = number_format($l[1], 0, ',', '.');
                            $host_id     = $l[2];
                            $ctx         = ($l[3] != '') ? $l[3] : Session::get_default_ctx();
                            
                            $host_output   = Asset_host::get_extended_name($security_report->ossim_conn, $geoloc, $ip, $ctx, $host_id);
                            
                            $os_pixmap     = ($host_id != '') ? Asset_host_properties::get_os_by_host($security_report->ossim_conn, $host_id) : '';
                            $hostname      = ($host_id != '') ? $host_output['name'] : $ip;
                            $icon          = $host_output['html_icon'];

                            $link = "$ossim_link/alarm/alarm_console.php?src_ip=".$ip;
                           
                            $bc = ($c++%2!=0) ? "class='par'" : '';
                        
                            $htmlPdfReport->set('
                                <tr '.$bc.'>
                                    <td style="width:55mm;font-size:'.$font_size.'px">'.$icon.' '.Util::wordwrap($hostname, 21, ' ', TRUE).' '.$os_pixmap.'</td>
                                    <td style="width:22mm;text-align:center;font-size:'.$font_size.'px">'.$occurrences.'</td>
                                </tr>');                                
                        }

    $htmlPdfReport->set('
                    </table>
                </td>
            <td valign="top" style="padding-top:15px; width:98mm;">');
          
                if ($report_graph_type == 'applets') 
                {
                    jgraph_attack_graph($target, $num_hosts);
                } 
                else 
                {
                    $htmlPdfReport->set('<img src="'.$htmlPdfReport->newImage('/report/graphs/attack_graph.php?shared='.urlencode($shared_file).'&target='.$target.'&hosts='.$num_hosts.'&type='.$report_type.'&date_from='.urlencode($date_from).'&date_to='.urlencode($date_to).'&runorder='.$runorder,'png').'" />');
                }

    $htmlPdfReport->set('
            </td>
        </tr>
    </table><br/><br />');          
}

$geoloc->close();
?>