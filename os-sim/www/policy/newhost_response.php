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


Session::logcheck("environment-menu", "PolicyHosts");

$ctx = GET('ctx');
$ip  = GET('host');


ossim_valid($ip, 	OSS_IP_ADDR, 	'illegal:' . _("Host"));
ossim_valid($ctx, 	OSS_HEX, 		'illegal:' . _("Ctx"));

if (ossim_error()) 
{
    $response['error'] = true;
    $response['msg']   =  ossim_get_error();
    
	ossim_clean_error();
	
	echo json_encode($response);
   
	exit;
}

if (!Session::can_i_create_assets())
{
    $response['error'] = true;
    $response['msg']   =  _("Warning: the host inserted already exists, inventory insert skipped.");
	
	echo json_encode($response);
   
	exit;
}


$db   = new ossim_db();
$conn = $db->connect();

            
try
{         
    $uuid = Util::uuid();
    
    $name = Asset_host::get_autodetected_name($ip);
    
    //Getting the sensors for the new host
    $f          = array();
    $f['where'] = " sensor.id = acl_sensors.sensor_id AND acl_sensors.entity_id=UNHEX('$ctx') ";
    
    
    $s_list     = Av_sensor::get_basic_list($conn, $tables, $filters);
    
    $sensors    = array();
    
    foreach ($s_list as $s)
    {
        $sensors[] = $s['id'];
    }
    
    
    $h_ip[$ip] = array(
        'ip'   =>  $ip,
        'mac'  =>  NULL,
    );
    
    
    $host = new Asset_host($conn, $uuid);
    
    $host->set_ips($h_ip);
    $host->set_name($name);
    
    $host->set_sensors($sensors);
    
    $host->save_in_db($conn);
    
    Util::memcacheFlush();
    
    $response['msg']   =  _("Host ") . $name . _(" Successfully inserted into inventory with default values.");
	$response['error'] = FALSE;
	$response['id']    = strtoupper($uuid);
	$response['txt']   = $name." (".$ip.")";

}
catch(Exception $e)
{
    $response['error'] = true;
    $response['msg']   = $e->getMessage();
	
}


$db->close();

echo json_encode($response);
