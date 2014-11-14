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


require_once 'deploy_common.php';

//Checking perms
check_deploy_perms();


function get_network_status($conn, $data)
{
	$id       = $data['id'];
	$location = $data['location'];

	
	ossim_valid($id,		OSS_HEX,	'illegal:' . _("Network ID"));
	ossim_valid($location,	OSS_HEX,	'illegal:' . _("Location ID"));

	if ( ossim_error() )
	{
		$info_error = "Error: ".ossim_get_error();
		ossim_clean_error();
		$return['error'] = true ;
		$return['msg']   = $info_error;
		return $return;
	}
	
	//aux variables
	$answ['0'] = 'error';
	$answ['1'] = 'ok';
	$answ['2'] = 'info';
	$types     = array('ids', 'vulns', 'passive', 'active', 'netflow');
	
	//net info
	$net                 = Asset_net::get_object($conn, $id);	
	if ($net != NULL)
	{
    	$cidr                = $net->get_ips();
    	$data['net_name']    = $net->get_name() . " (" . $cidr . ")";
    	$data['net_owner']   = Session::get_entity_name($conn,$net->get_ctx(), true);
    	$data['net_descr']   = $net->get_descr();
	}
	else
	{
    	$data['net_name']    = _('Unknown');
    	$data['net_owner']   = _('Unknown');
    	$data['net_descr']   = _('Unknown');
	}
	
	$checks = Locations::get_location_checks($conn, $location);
	
	foreach($types as $t_pos =>$t)
	{
		if (strlen($checks) == 5 && $checks[$t_pos] == 0)
		{
			$data[$t] = 'info';
		}
		else
		{
			$options  = array("type" => $t, "percent" => false, "network" => $id);
			$var      = get_network_visibility($conn, $options);
			$data[$t] = $answ[$var];
		}
		
	}
	
	$options             = array("type" => 'asset_network', "network" => $id);
	$data['net_devices'] = get_asset_visibility($conn, $options);
	
	$options             = array("type" => 'asset_server', "network" => $id);
	$data['servers']     = get_asset_visibility($conn, $options);
	

	$return['error'] = false ;
	$return['data']  = $data;
	
	return $return;
	
}



function modify_location_services($conn, $data)
{
	$id      = $data['id'];
	$service = $data['service'];
	$value   = $data['value'];

	
	ossim_valid($id,		OSS_HEX,	'illegal:' . _("Network ID"));
	ossim_valid($service,	OSS_ALPHA,	'illegal:' . _("Service "));
	ossim_valid($value,		OSS_DIGIT,	'illegal:' . _("Value"));

	if (ossim_error())
	{
		$info_error = "Error: ".ossim_get_error();
		
		ossim_clean_error();
		
		$return['error'] = true ;
		$return['msg']   = $info_error;
		
		return $return;
	}
	
	$checks = Locations::get_location_checks($conn, $id);
	
	$checks[$service] = $value;

	Locations::save_location_checks($conn, $id, $checks);
	
	Util::memcacheFlush(false);
	
	$return['error'] = false ;
	$return['data']  = _('Service Modified Successfully');
	
	return $return;
	
}

function get_assets_visibility($conn)
{
	//Networks Devices
	$options   = array("type" => 'asset_network');
	$_networks = get_asset_visibility($conn, $options);

	$data['network']['count']   = $_networks[1];
	$data['network']['total']   = $_networks[2];
	$data['network']['percent'] = $_networks[0];


	//Servers Devices
	$options  = array("type" => 'asset_server');
	$_servers = get_asset_visibility($conn, $options);
	
	$data['server']['count']   = $_servers[1];
	$data['server']['total']   = $_servers[2];
	$data['server']['percent'] = $_servers[0];


	$return['error'] = false ;
	$return['data']  = $data;
	
	return $return;
	
}

function modify_device_host($conn, $data)
{
	$id      = $data['id'];
	$type    = $data['type'];
	$subtype = $data['subtype'];
	
	ossim_valid($id,		OSS_HEX,					'illegal:' . _("Host ID"));
	ossim_valid($type,		OSS_DIGIT, OSS_NULLABLE,	'illegal:' . _("Device Type"));
	ossim_valid($subtype,	OSS_DIGIT, OSS_NULLABLE,	'illegal:' . _("Device Subtype"));

	if ( ossim_error() )
	{
		$info_error = "Error: ".ossim_get_error();
		ossim_clean_error();
		$return['error'] = true ;
		$return['msg']   = $info_error;
		return $return;
	}

	if ( empty($type) ) 
	{
		$sql    = "DELETE FROM host_types WHERE host_id = UNHEX(?)";
		$params = array($id);
	}
	else
	{
		$sql    = "DELETE FROM host_types WHERE host_id = UNHEX(?)";
		$params = array($id);
		$conn->Execute($sql, $params);

		$sql    = "REPLACE INTO host_types (host_id, type, subtype) VALUES (UNHEX(?), ?, ?)";
		$params = array(
			$id,
			$type,
			$subtype
		);
	}

	if ( $conn->Execute($sql, $params) === false ) 
	{	
		$return['error'] = true ;
		$return['msg']   = $conn->ErrorMsg();
	}
	else
	{
		Util::memcacheFlush(false);
	
		$return['error'] = false ;
		$return['data']  = _('Device Property Modified Successfully');
	}

	return $return;
	
}



$action = POST("action");
$data   = POST("data");

ossim_valid($action,	OSS_DIGIT,	'illegal:' . _("Action"));

if (ossim_error()) 
{
    die(ossim_error());
}

$db     = new ossim_db(TRUE);
$conn   = $db->connect();

if ($action != '' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
{

	if (!Token::verify('tk_deploy_ajax', GET('token')))
	{
		$response['error'] = true ;
		$response['msg']   = 'Invalid Action';
		
		echo json_encode($response);
		
		$db->close();
		exit();
	}
	
	switch ($action)
	{
		case 1:			
			$response = get_network_status($conn, $data);			
			break;
			
		case 2:			
			$response = modify_location_services($conn, $data);			
			break;

		case 3:			
			$response = get_assets_visibility($conn);			
			break;

		case 4:			
			$response = modify_device_host($conn, $data);			
			break;
				
		
		default:
			$response['error'] = true ;
			$response['msg']   = 'Wrong Option Chosen';
	}
	
	echo json_encode($response);

}

$db->close();
