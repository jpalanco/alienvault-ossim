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


function get_vendor_list($conn)
{
    $response = array();
    
    $items = Software::get_hardware_vendors($conn, TRUE);
    
    $response['error'] = FALSE;
    $response['data']['items'] = $items;
    
    return $response;
}


function get_model_list($conn, $data)
{
    $response = array();

    $vendor   = $data['vendor'];

    ossim_valid($vendor,   OSS_NULLABLE, OSS_ALPHA, OSS_PUNC_EXT,     'illegal:' . _("Vendor"));

    check_ossim_error();
    
    if (empty($vendor))
    {
        $items = array();
    }
    else
    {
        $vendor = escape_sql($vendor, $conn);
        $items  = Software::get_models_by_cpe($conn, $vendor, TRUE);
    }
    $response['error'] = FALSE;
    $response['data']['items'] = $items;

    return $response;

}


function get_version_list($conn, $data)
{
    $response = array();

    $model    = $data['model'];

    ossim_valid($model,    OSS_NULLABLE, OSS_ALPHA, OSS_PUNC_EXT,     'illegal:' . _("Model"));

    check_ossim_error();
    
    if (empty($model))
    {
        $items = array();
    }
    else
    {
        $model  = escape_sql($model, $conn);

        $items  = Software::get_versions_by_cpe($conn, $model, TRUE);
    }
    
    $response['error'] = FALSE;
    $response['data']['items'] = $items;

    return $response;
}


function plugin_activity($conn, $data)
{
    $asset_id = $data['asset'];

    ossim_valid($asset_id,    OSS_HEX,     'illegal:' . _("ASSET"));

    check_ossim_error();
    
    $active_plugin  = array();
    $total_plugins  = 0;
    
    try
    {    

        $sensors = Asset_host_sensors::get_sensors_by_id($conn, $asset_id);
        
        $client  = new Alienvault_client();  
     
        foreach ($sensors as $sensor_id => $s_data)
        {
        
            $plugins  = $client->sensor(Util::uuid_format($sensor_id))->get_plugins_by_assets();
            $plugins  = @json_decode($plugins, TRUE);
                                                    
                         
            if ($plugins['status'] == 'success')
            {
                           
                if (array_key_exists($asset_id, $plugins['data']['plugins']))
                {
                    $plugins = $plugins['data']['plugins'][$asset_id];                                             
                
                    foreach ($plugins as $pdata)
                    {
                        $active = Asset_host_devices::check_device_connectivity($conn, $asset_id, $pdata['plugin_id'], $sensor_id, TRUE);
                        
                        if ($active)
                        {
                            $row_id = md5($asset_id . $pdata['cpe'] . $sensor_id);
                            $active_plugin[$row_id] = TRUE;
                        }         
                        
                        $total_plugins ++;                                                                                                       
                    }              
                }              
            }
        }     
    }
    catch(Exception $e)
    {
       //nothing here
    }


    $response['error']           = FALSE;
    $response['data']['plugins'] = $active_plugin;
    $response['data']['total_p'] = $total_plugins;

    return $response;
}


function set_plugins($conn, $data)
{
    $response = array();
    
    $plugins  = array();

    foreach ($data['plugin_list'] as $id => $list_cpe)
    {
        ossim_valid($id,      OSS_HEX,    'illegal:' . _("Host ID"));
        
        $list_cpe = (is_array($list_cpe)) ? $list_cpe : array();
        
        foreach ($list_cpe as $p)
        {
            $cpe = '';
            
            if ($p['version'] != '')
            {
                $cpe = $p['version'];
            }
            elseif($p['model'] != '')
            {
                $cpe = $p['model'];
            }
            elseif($p['vendor'] != '')
            {
                $cpe = $p['vendor'];
            }
            
            ossim_valid($cpe,    OSS_NULLABLE, OSS_ALPHA, OSS_PUNC_EXT,     'illegal:' . _("CPE"));

            $plugins[$id][] = $cpe;            

        }

    }
    
    $sensor = $data['sensor'];
    
    ossim_valid($sensor,  'a-fA-F0-9\-',   'illegal:' . _("Sensor ID"));
    
    check_ossim_error();
        
    Plugin::set_plugins_by_device_cpe($conn, $plugins, Util::uuid_format($sensor));
    
    $response['error']   = FALSE;
    $response['msg']     = _("Plugin successfully configured.");
    

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
$response['msg']   = _('Unknown Error');

//checking if it is an ajax request
if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
{
    //Checking token
	if ( !Token::verify('tk_plugin_select', GET('token')) )
	{
		$response['error'] = TRUE ;
		$response['msg']   = _('Invalid Action');
	}
	else
	{
        //List of all the possibles functions
        $function_list = array
        (
            'set_plugins'     => array('name' => 'set_plugins',              'params' => array('conn', 'data')),
            'vendor_list'     => array('name' => 'get_vendor_list',          'params' => array('conn')),
            'model_list'      => array('name' => 'get_model_list',           'params' => array('conn', 'data')),
            'version_list'    => array('name' => 'get_version_list',         'params' => array('conn', 'data')),
            'plugin_activity' => array('name' => 'plugin_activity',          'params' => array('conn', 'data'))
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
                    throw new Exception(_('An unexpected error happened. Try again later'));
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
