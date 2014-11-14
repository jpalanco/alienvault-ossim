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


set_time_limit(1800);

require_once 'av_init.php';


$path = '/usr/share/ossim/www/report/os_reports/';
set_include_path(get_include_path() . PATH_SEPARATOR . $path);

require_once 'Common/functions.php';
require_once 'Various/general.php';

// DB
$db   = new ossim_db();
$conn = $db->connect();

$geoloc = new Geolocation('/usr/share/geoip/GeoLiteCity.dat');

//Initialize var

$report_name    = $report_data['report_name'];
$subreport_name = $report_data['subreports'][$subreport_id]['name'];

$date_from      = POST($report_data['parameters'][0]['date_from_id']);
$date_to        = POST($report_data['parameters'][0]['date_to_id']);

ossim_valid($date_from, OSS_DATE, OSS_NULLABLE, 'illegal:' . _('Date From'));
ossim_valid($date_to,   OSS_DATE, OSS_NULLABLE, 'illegal:' . _('Date To'));

if (ossim_error())
{
    echo 'error###'.ossim_get_error_clean();
    exit;
}

$query_temp = array();
//
// select src_ip from alarm table and not defined into nets
//
$ips = array();

$plugin_id     = NULL;
$plugin_groups = NULL;
$source_type   = NULL;
$category      = NULL;
$subcategory   = NULL;
$limit         = 20;


// Taxonomy filters
$plugin_list = Plugin_sid::get_all_sids($conn, $plugin_id, $source_type, $category, $subcategory, $plugin_groups);

// Data Source events or Source Type events
$selected = "";

// src_ips from acid_event
$where = Security_report::make_where($conn,$date_from,$date_to,$plugin_list,$dDB);

$query = "SELECT DISTINCT ip_src AS ip FROM alienvault_siem.acid_event WHERE 1=1 $where 
    UNION SELECT DISTINCT ip_dst as ip FROM alienvault_siem.acid_event WHERE 1=1 $where";


if (!$rs = & $conn->Execute($query)) 
{
	error_log($conn->ErrorMsg(), 0);
	return;
}

$already = array();

while (!$rs->EOF) 
{    
    $ip = inet_ntop($rs->fields['ip']);
    
    if (!isset($already[$ip])) 
    { 
        //Session::hostAllowed($conn,$ip) => not necessary here?
        $already[$ip]++;
        
        if (!Asset_host::is_ip_in_cache_cidr($conn, $ip)) 
        {
            // geoip
            $_country_aux   = $geoloc->get_country_by_host($conn, $ip);
            $s_country      = strtolower($_country_aux[0]);
            $s_country_name = $_country_aux[1];
            
            if ($s_country == '')
            {
                $ips[':Unknown']++;
            }
            else
            {
                $ips["$s_country:$s_country_name"]++;
            }
        } 
       
    }
    
    $rs->MoveNext();
}
//
arsort($ips);

$ips        = array_slice($ips, 0, $limit);
$totalValue = array_sum($ips);


// Set session var for graph
$dDB['_shared']->put('geoips', $ips);
$shared_file = $dDB['_shared']->dbfile();


$title = $report_name._(' - Top').' '.$limit.' '._('Attacker Countries');

//pdf
$htmlPdfReport->pageBreak();
$htmlPdfReport->setBookmark($title);

if (Session::menu_perms('analysis-menu', 'EventsForensics')) 
{

    if (count($ips) == 0) 
    {
        $htmlPdfReport->set($htmlPdfReport->newTitle($title, $date_from, $date_to, NULL).'
        <table class="w100" cellpadding="0" cellspacing="0">
            <tr><td class="w100" align="center" valign="top">'._('No data available').'</td></tr>
        </table><br/><br/> ');
       
        return;
    }

    $htmlPdfReport->set($htmlPdfReport->newTitle($title, $date_from, $date_to, NULL).'
    <table class="w100" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width:95mm;" valign="top">
                <table style="width:95mm; padding-top: 10px; padding-bottom: 10px;">
                  <tr>
                    <th>'._('Country').'</th>
                    <th style="text-align:center">'._('Attacks').'</th>
                    <th style="text-align:center">'._('%').'</th>
                  </tr>
                ');

            $c = 0;
            
            $conf = $GLOBALS['CONF'];
            
            foreach ($ips as $country => $val) 
            {
                // type=6 Top Attackers from Country
                $cou = explode(':',$country);
                if($cou[0] == '')
                {
                    $flag = $flag1 = '';
                }
                else
                {
                    if($cou[0] == 'me'||$cou[0] == 'eu' || $cou[0] == 'ap')
                    {
                        $flag = $flag1 = '';
                    }
                    elseif ($cou[0] == 'local') 
                    {
                        $flag  = getProtocol().'//'.Util::get_default_admin_ip().'/ossim/forensics/images/homelan.png';
                        $flag1 = '../forensics/images/homelan.png';
                    }
                    else
                    {
                        $flag  = getProtocol().'//'.Util::get_default_admin_ip().'/ossim/pixmaps/flags/'.$cou[0].'.png';
                        $flag1 = '../pixmaps/flags/'.$cou[0].'.png';
                    }
                }
                
                $porcent = round($val*100/$totalValue,1);
                
        
                $bc = ($c++%2 != 0) ? "class='par'" : "";
                /**/
                $htmlPdfReport->set('
                  <tr '.$bc.'>
                    <td style="width:50mm;font-size:11px">'.($flag != '' ? "<img src='$flag' border='0' align='absmiddle' style='width:4mm'>" : "").' '.$cou[1].'</td>
                    <td style="width:18mm;text-align:center;font-size:11px">'.$val.'</td>
                    <td style="width:12mm;text-align:center;font-size:11px">'.$porcent.'%</td>
                  </tr>
                ');
            }

    $db->close();
    
    $htmlPdfReport->set('
                </table>
            </td>
            <td valign="top" style="padding-top:10px; width:93mm;">
                <img src="'.$htmlPdfReport->newImage('/report/graphs/geographic_graph.php?shared='.urlencode($shared_file),'png').'" border="0">
            </td>
        </tr>
        <tr>
            <td colspan="2" align="center"><br><img src="'.$htmlPdfReport->newImage('/report/graphs/graph_geoloc.php?shared='.urlencode($shared_file),'png').'" style="width:100%" border="0"></td>
        </tr>
    </table>
    <br/><br/>');

}

$geoloc->close();

?>