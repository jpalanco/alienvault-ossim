<?php
/**
*
* License:
*
* Copyright (c) 2003-2006 ossim.net
* Copyright (c) 2007-2015 AlienVault
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


Session::logcheck_ajax("analysis-menu", "ControlPanelAlarms");

session_write_close();

$backlog_id = POST('backlog_id');
$asset_id   = POST('asset_id');
$asset_ip   = POST('asset_ip');


$asset_id   = strtoupper(str_replace('0x', '', $asset_id));
if (!valid_hex32($asset_id))
{
    $asset_id = '';
}

ossim_valid($backlog_id,    OSS_HEX,          'illegal:' . _("Backlog ID"));
ossim_valid($asset_ip,      OSS_IP_ADDR_0,    'illegal:' . _("Asset IP"));

if (ossim_error())
{
    Util::response_bad_request(ossim_get_error_clean());
}


$response = array();
try
{
    $db   = new ossim_db(TRUE);
    $conn = $db->connect();
    $gloc = new Geolocation('/usr/share/geoip/GeoLiteCity.dat');
    
    if (Session::is_pro() && file_exists('/usr/share/geoip/GeoIPOrg.dat')) 
    { 
        $gorg = new Geolocation('/usr/share/geoip/GeoIPOrg.dat'); 
    }
    
    $asset = Asset_host::get_object($conn, $asset_id);
    
    if ($asset)
    {
        $groups = $networks = array();
        
        //Getting Groups
        list($_groups, $_t)  = $asset->get_related_groups($conn); 
        foreach ($_groups as $g_id => $g)
        {
            $groups[$g_id] = array(
                'name' => $g->get_name()
            );
        }
        
        //Getting Nets
        $_networks= $asset->get_nets($conn);
        foreach ($_networks as $n_id => $n)
        {
            $networks[$n_id] = array(
                'name' => $n['name']
            );
        }
        
        $response['id']     = $asset_id;
        $response['ip']     = $asset_ip;
        $response['name']   = $asset->get_name();
        $response['groups'] = $groups;
        $response['nets']   = $networks;
    }
    else
    {
        $response['id']     = $asset_id;
        $response['ip']     = $asset_ip;
        $response['name']   = $asset_ip;
        $response['groups'] = array();
        $response['nets']   = array();
    }
    
    $record    = $gloc->get_location_from_file($asset_ip);
    
    $flag_code = strtolower($record->country_code);
    if (file_exists("/usr/share/ossim/www/pixmaps/flags/$flag_code.png")) 
    {
        $flag = "/ossim/pixmaps/flags/$flag_code.png";  
    }
    
    if (is_object($gorg))
    {
        $org = $gorg->get_organization_by_host($ip);
    }
    							
    							
    $response['location'] = array(
        'flag'         => strval($flag),
        'country'      => strval($record->country_name),
        'organization' => strval($org)
    );
    
    
    //Get the stats for check the reputation
    $stats = $_SESSION["_alarm_stats"];
    
    $c_rep_1 = $stats['src']['ip'][$asset_ip]['rep'] > 0;
    $c_rep_2 = $stats['dst']['ip'][$asset_ip]['rep'] > 0;
    
    $response['reputation'] = ($c_rep_1 || $c_rep_2);
    
    
    $db->close();
    $gloc->close();
    if (is_object($gorg))
    {
        $gorg->close();
    }
    
} catch (Exception $e)
{
    Util::response_bad_request($e->getMessage());
}

echo json_encode($response);
