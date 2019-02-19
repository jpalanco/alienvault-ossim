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


function get_vendor_list($data)
{
    $response = array();
    $sensor   = $data['sensor'];
    
    ossim_valid($sensor,   OSS_ALPHA, OSS_HEX,                        'illegal:' . _("Sensor"));
    
    check_ossim_error();
    
    $items = Software::get_hardware_vendors($sensor);
    
    $response['error'] = FALSE;
    $response['data']['items'] = $items;
    
    return $response;
}


function get_model_list($data)
{
    $response = array();
    $vendor   = $data['vendor'];
    $sensor   = $data['sensor'];

    ossim_valid($vendor,   OSS_NULLABLE, OSS_ALPHA, OSS_PUNC_EXT,     'illegal:' . _("Vendor"));
    ossim_valid($sensor,   OSS_ALPHA, OSS_HEX,                        'illegal:' . _("Sensor"));

    check_ossim_error();
    
    if (empty($vendor))
    {
        $items = array();
    }
    else
    {
        $items  = Software::get_models_by_vendor($vendor, $sensor);
    }
    $response['error'] = FALSE;
    $response['data']['items'] = $items;

    return $response;

}


function get_version_list($data)
{
    $response = array();
    $model    = $data['model'];
    $sensor   = $data['sensor'];

    ossim_valid($model,    OSS_NULLABLE, OSS_ALPHA, OSS_PUNC_EXT,     'illegal:' . _("Model"));
    ossim_valid($sensor,   OSS_ALPHA, OSS_HEX,                        'illegal:' . _("Sensor"));

    check_ossim_error();
    
    if (empty($model))
    {
        $items = array();
    }
    else
    {
        $items  = Software::get_versions_by_model($model, $sensor);
    }
    
    $response['error'] = FALSE;
    $response['data']['items'] = $items;

    return $response;
}



function set_plugins($data)
{
    $sensor = $data['sensor'];
    
    ossim_valid($sensor,  OSS_HEX,   'illegal:' . _("Sensor ID"));
    
    check_ossim_error();
    
    
    $response = array();
    
    
    $plugins = Plugin::resolve_plugins_by_vmv($data['plugin_list'], $sensor);
    
        
    Plugin::set_plugins_by_assets($plugins, Util::uuid_format($sensor));
    
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


ossim_valid($action,    OSS_INPUT,    'illegal:' . _("Action"));

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
$response['msg']   = _('Error');

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
            'set_plugins'     => array('name' => 'set_plugins',              'params' => array('data')),
            'vendor_list'     => array('name' => 'get_vendor_list',          'params' => array('data')),
            'model_list'      => array('name' => 'get_model_list',           'params' => array('data')),
            'version_list'    => array('name' => 'get_version_list',         'params' => array('data'))
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
