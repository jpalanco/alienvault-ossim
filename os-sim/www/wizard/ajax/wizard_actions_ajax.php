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


//First we check we have session active
Session::useractive();

//Then we check the permissions

if (!Session::am_i_admin())
{
    $response['error']  = TRUE ;
    $response['msg']    = _('You do not have permissions to see this section');

    echo json_encode($response);

    exit -1;
}



/*
*
* <------------------------   BEGINNING OF THE FUNCTIONS   ------------------------>
*
*/

function check_ossim_error()
{
    if (ossim_error())
    {
        $error = ossim_get_error();

    	ossim_clean_error();

    	Av_exception::throw_error(Av_exception::USER_ERROR, $error);
    }
}



/*
* --------------   STEP 1 ACTIONS  --------------
*/


function get_nic_activity()
{
    $response = array();

    $_nics    = Welcome_wizard::get_traffic_stats();

    $nics     = array();

    foreach ($_nics as $id => $status)
    {
        if ($status)
        {
            $status = 'on';
        }
        else
        {
            $status = 'off';
        }

        $nics[$id] = $status;
    }

    $response['error']         = FALSE;
    $response['data']['nics']  = $nics;

    return $response;
}


function change_nic_mode($data)
{
    $response = array();

    $nic      = $data['nic'];
    $role     = $data['role'];
    $ip       = $data['ip'];
    $mask     = $data['mask'];

    /* WHOLE VALIDATION */
    ossim_valid($nic,       OSS_DIGIT, OSS_LETTER,                      'illegal:' . _("NIC"));
    ossim_valid($role,      'log_management','disabled','monitoring',   'illegal:' . _("NIC Role"));
    ossim_valid($ip,        OSS_IP_ADDR_0, OSS_NULLABLE,                'illegal:' . _("NIC IP Address"));
    ossim_valid($mask,      OSS_IP_ADDR_0, OSS_NULLABLE,                'illegal:' . _("NIC Netmask"));

    check_ossim_error();
    
    $ifaces = $_SESSION['_wizard_interfaces'];
    
    if (empty($ifaces[$nic]))
    {
        throw new Exception(_('Invalid NIC'));
    }
    
    if ($ifaces[$nic]['role'] == 'admin')
    {
        throw new Exception(_('Management NIC cannot be modified'));
    }
    
    if ($role == 'log_management')
    {
        if (empty($ip) || empty($mask))
        {
            throw new Exception(_('IP and Netmask Fields Required'));
        }
        
        foreach ($ifaces as $if)
        {
            if ($if['ip'] == $ip && $nic != $if['name'])
            {
                $msg = sprintf(_('The selected IP Address is already in use by %s'), Util::htmlentities($if['name']));
                throw new Exception($msg);
            }
        }
    }
    else
    {
        $ip = $mask = '';
    }

    
    $ifaces[$nic]['role']    = $role;
    $ifaces[$nic]['ip']      = $ip;
    $ifaces[$nic]['netmask'] = $mask;
    
        
    if ($role == 'monitoring')
    {
        Welcome_wizard::set_promisc_mode($nic, TRUE);
    }
    else
    {
        Welcome_wizard::set_promisc_mode($nic, FALSE);
    }

    $_SESSION['_wizard_interfaces'] = $ifaces;
    
    
    $response['error'] = FALSE;
    $response['data']  = array();

    return $response;
}


/*
* --------------   STEP 2 ACTIONS  --------------
*/


function insert_net($conn, $data)
{
    $cidrs = preg_replace('/\s*/', '', $data['cidr']);
    $name  = utf8_decode($data['name']);
    $descr = $data['descr'];
    
    ossim_valid($cidrs,	OSS_IP_CIDR,	                    'illegal:' . _("CIDR"));
    ossim_valid($name,	OSS_NOECHARS, OSS_NET_NAME,	        'illegal:' . _("Name"));
    ossim_valid($descr,	OSS_NULLABLE, OSS_ALL,              'illegal:' . _("Description"));

    check_ossim_error();

    $uuid      = Util::uuid();
    $net       = new Asset_net($uuid);

    $sensor_ip = Util::get_default_admin_ip();
    $sensor    = Av_sensor::get_id_by_ip($conn, $sensor_ip);

    $net->set_ips($cidrs);
    $net->set_name($name);
    $net->set_descr($descr);
    $net->set_sensors(array($sensor));

    //Insert the New Net
    $net->save_in_db($conn);

    
    $data         = array();
    $data['cidr'] = $net->get_ips();
    

    $response['error']  = FALSE;
    $response['data']   = $data;


    return $response;
}


function delete_member($conn, $data)
{
    $uuid = $data['id'];
    $type = $data['type']; // Type
  
    ossim_valid($uuid,      OSS_HEX,	                 'illegal:' . _("ID"));
    ossim_valid($type,	    OSS_NULLABLE, OSS_LETTER,     'illegal:' . _("Device Type"));

    check_ossim_error();
    
    
    if ($type == 'host')
    {
        Asset_host::delete_from_db($conn, $uuid);
    }
    elseif ($type == 'net')
    {
        Asset_net::delete_from_db($conn, $uuid);
    }
    else
    {
        throw new Exception(_('Invalid Action'));
    }
    

    $response['error']  = FALSE ;
    $response['data']   = array();

    return $response;
}



/*
* --------------   STEP 3 ACTIONS  --------------
*/


function insert_host($conn, $data)
{
    $ips             = preg_replace('/\s*/', '', $data['ip']);
    $name            = utf8_decode($data['name']);
    list($os,$dtype) = explode("_",$data['type']); // Type
  
  
    ossim_valid($ips,	OSS_IP_ADDR,	             'illegal:' . _("IP"));
    ossim_valid($name,	OSS_HOST_NAME,	             'illegal:' . _("Name"));
    ossim_valid($os,	OSS_NULLABLE, OSS_ALPHA,     'illegal:' . _("OS"));
    ossim_valid($dtype,	OSS_NULLABLE, OSS_ALPHA,     'illegal:' . _("Device Type"));

    check_ossim_error();
    
    $ips = explode(',', $ips);
    
    foreach ($ips as $ip)
    {
        $h_ip[$ip] = array(
    	    'ip'   =>  $ip,
    	    'mac'  =>  NULL,
        );
    }

    //Insert the New Host
    $uuid      = Util::uuid();
    $sensor_ip = Util::get_default_admin_ip();
    $sensor    = Av_sensor::get_id_by_ip($conn, $sensor_ip);
    
    
    $host      = new Asset_host($conn, $uuid);

    $host->set_ips($h_ip);
    $host->set_name($name);
    $host->set_sensors(array($sensor));

    $host->save_in_db($conn);

    // Device Type
    if ($dtype == 'networkdevice')
    {
        Asset_host_devices::save_device_in_db($conn, $uuid, 4);
    }

    // OS
    if ($os == 'windows' || $os == 'linux')
    {
        Asset_host_properties::save_property_in_db($conn, $uuid, 3, ucfirst($os), 1, TRUE);
    }


    $response['error']  = FALSE ;
    $response['data']   = array();

    return $response;
}


function change_htype($conn, $data)
{
    $uuid             = $data['id'];
    list($os, $dtype) = explode("_", $data['type']); // Type
  
    ossim_valid($uuid,      OSS_HEX,	                 'illegal:' . _("ID"));
    ossim_valid($os,	    OSS_NULLABLE, OSS_ALPHA,     'illegal:' . _("OS"));
    ossim_valid($dtype,	    OSS_NULLABLE, OSS_ALPHA,     'illegal:' . _("Device Type"));

    check_ossim_error();  
    
    if (empty($dtype) && empty($os))
    {
        Asset_host_devices::delete_all_from_db($conn, $uuid);
        Asset_host_properties::delete_property_from_db($conn, $uuid, 3, '', TRUE);
    }
    else
    {
        // Device Type
        if ($dtype == 'networkdevice')
        {
            Asset_host_devices::save_device_in_db($conn, $uuid, 4); //Adding the device type
            Asset_host_properties::delete_property_from_db($conn, $uuid, 3, '', TRUE); //Removing the previous OS
        }
        elseif ($os == 'windows' || $os == 'linux') //OS
        {
            Asset_host_devices::delete_device_from_db($conn, $uuid, 4); //Removing device type
            Asset_host_properties::delete_property_from_db($conn, $uuid, 3); //Removing previous OS
            Asset_host_properties::save_property_in_db($conn, $uuid, 3, ucfirst($os), 1, TRUE); //Adding the new OS
        }
    }

    $response['error']  = FALSE ;
    $response['data']   = array();

    return $response;
}



/*
* --------------   STEP 4 ACTIONS  --------------
*/




/*
* --------------   STEP 5 ACTIONS  --------------
*/

function set_plugins($data)
{
    $response = array();
    
    $wizard   = Welcome_wizard::get_instance();
    
    if ($wizard === FALSE)
    {
        throw new Exception(_('Sorry, operation was not completed due to an error when processing the request. Try again later'));
    }
    
    $plugins = Plugin::resolve_plugins_by_vmv($data['plugin_list']);
    
    $task_id = Plugin::set_plugins_by_assets($plugins);

    $wizard->set_step_data('task_id', $task_id);
    $wizard->set_step_data('plugins_flag', FALSE);
    $wizard->save_status();

    $response['error'] = FALSE;
    $response['msg']   = _("Plugin successfully configured. It can take up few minutes. Please wait until green led appears");

    return $response;
}


function net_devices_activity($conn)
{
    $response = array();

    $wizard   = Welcome_wizard::get_instance();

    if ($wizard === FALSE)
    { 
        throw new Exception(_("There was an error, the Welcome_wizard object doesn't exist. Try again later"));
    }
    
    $plugins  = array();
    $flag_end = FALSE;
    
    $task_id  = $wizard->get_step_data('task_id');
    
    if ($task_id == 'ffffffff-ffff-ffff-ffff-ffffffffffff')
    {
        $status = 1;
    }
    else
    {
        $status = Welcome_wizard::current_jobs($task_id);
        $status = ( in_array($status['job_status'], array('task-failed','task-succeeded','task-revoked')) ) ? 1 : 0;
    }
    
    if ($status == 1)
    {
        $devices = Plugin::get_plugins_by_assets();
        
        foreach ($devices as $h_id => $p_data)
        {
            $h_id = Util::uuid_format_nc($h_id);
            
            $p_data = is_array($p_data) ? $p_data : array();
            
            foreach ($p_data as $pkey => $pdata)
            {
                $active = Asset_host_devices::check_device_connectivity($conn, $h_id, $pdata['plugin_id'], '', TRUE);
                
                $plugins[$h_id][$pkey] = $active;
                
                if ($flag_end)
                {
                    $flag_end = TRUE;
                }
            }
        }
        
    }

    $wizard->set_step_data('net_devices_data', $flag_end);
    $wizard->save_status();

    $response['error']           = FALSE;
    $response['data']['plugins'] = $plugins;
    $response['data']['status']  = $status;

    return $response;
}


function get_otx_user ($data)
{
    $response = array();

    $token    = $data['token'];
    
    /* VALIDATION */
    ossim_valid($token, OSS_ALPHA, 'illegal:' . _("OTX auth-token"));

    check_ossim_error();
    
    /* The try-catch check is done when the function is called in the main */
    
    try
    {
        $otx = new Otx();
        $otx->register_token($token);
        
        $response['error'] = FALSE;
        $response['msg']   = $otx->get_username();
    }
    catch(Exception $e)
    {
        $response['error'] = TRUE;
        $response['msg']   = $e->getMessage();
    }

    return $response;
}


/*
*
* <------------------------   END OF THE FUNCTIONS   ------------------------>
*
*/






/*
*
* <-------------------------   BODY OF THE SCRIPT   ------------------------->
*
*/

$action = POST("action");   //Action to perform.
$data   = POST("data");     //Data related to the action.


ossim_valid($action,	OSS_INPUT,	'illegal:' . _("Action"));

if (ossim_error())
{
    $response['error'] = TRUE ;
	$response['msg']   = ossim_get_error();
	ossim_clean_error();

	echo json_encode($response);

	die();
}

//Default values for the response.
$response['error'] = TRUE ;
$response['msg']   = _('Error when processing the request');

//checking if it is an ajax request
if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
{
    //Checking token: it could come from wizard or plugin
	if ( !Token::verify('tk_welcome_wizard', GET('token')) && !Token::verify('tk_plugin_select', GET('token')) )
	{
		$response['error'] = TRUE ;
		$response['msg']   = _('Invalid Action');
	}
	else
	{
        //List of all the possibles functions
        $function_list = array
        (
            'insert_net'        => array('name' => 'insert_net',               'params' => array('conn', 'data')),
            'insert_host'       => array('name' => 'insert_host',              'params' => array('conn', 'data')),
            'change_htype'      => array('name' => 'change_htype',             'params' => array('conn', 'data')),
            'delete_member'     => array('name' => 'delete_member',            'params' => array('conn', 'data')),
            'net_activity'      => array('name' => 'net_devices_activity',     'params' => array('conn')),
            'nic_activity'      => array('name' => 'get_nic_activity',         'params' => array('')),
            'get_otx_user'      => array('name' => 'get_otx_user',             'params' => array('data')),
            'change_nic_mode'   => array('name' => 'change_nic_mode',          'params' => array('data')),
            'set_plugins'       => array('name' => 'set_plugins',              'params' => array('data'))
        );

        $_function = $function_list[$action];

        //Checking we have a function associated to the action given
        if (is_array($_function) && function_exists($_function['name']))
        {
            $db     = new ossim_db();
            $conn   = $db->connect();

            //Now we translate the params list to a real array with the real parameters
            $params = array();

            foreach($_function['params'] as $p)
            {
                $params[] = $$p;
            }

            try
            {
                //Calling to the function
                $response = call_user_func_array($_function['name'], $params);

                if ($response === FALSE)
                {
                    throw new Exception(_('Sorry, operation was not completed due to an error when processing the request. Try again later'));
                }
            }
            catch(Exception $e)
            {
                $response['error'] = TRUE ;
                $response['msg']   = $e->getMessage();
            }

            $db->close($conn);

        }
        else
        {
           $response['error'] = TRUE ;
           $response['msg']   = _('Wrong Option Chosen');
        }
	}
}

//Returning the response to the AJAX call.
echo json_encode($response);
