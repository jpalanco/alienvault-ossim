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


//Config File
require_once 'av_init.php';

Session::logcheck('configuration-menu', 'PolicySensors');

session_write_close();

//Validate action type

$action  = POST('action');

ossim_valid($action, OSS_LETTER, '_',   'illegal:' . _('Action'));

if (ossim_error())
{
    $data['status']  = 'error';
	$data['data']    = ossim_get_error_clean();

	echo json_encode($data);
    exit();
}


//Validate Form token

$token = POST('token');

if (Token::verify('tk_sensor_form', POST('token')) == FALSE)
{
	$data['status']  = 'error';
	$data['data']    = Token::create_error_message();

	echo json_encode($data);
    exit();
}


$data['status']  = 'error';
$data['data']    = _('Action not allowed');


switch($action)
{
    case 'delete_sensor':

        $validate = array(
            'id'             =>  array('validation' => 'OSS_HEX',   'e_message'  =>  'illegal:' . _('Sensor ID')),
            'check_policies' =>  array('validation' => 'OSS_DIGIT', 'e_message'  =>  'illegal:' . _('Check policies'))
        );

        $id             = POST('id');
        $check_policies = intval(POST('check_policies'));

        $validation_errors = validate_form_fields('POST', $validate);
        
        if (is_array($validation_errors) && !empty($validation_errors))
        {
            $data['status']  = 'error';
            $data['data']    = _('Error! Sensor ID not allowed.  Sensor could not be removed');
        }
        else
        {
            try
            {
                $db    = new ossim_db();
                $conn  = $db->connect();
                
                $sensor_policies = 0;
                
                if ($check_policies == 1)
                {
                    $sensor_policies = Policy_sensor_reference::get_policy_by_sensor($conn, $id);
                    
                    if (count($sensor_policies) > 0)
                    {
                        $data['status']  = 'warning';
                        $data['data']    = _('This sensor belongs to a policy');
                    }
                }                
                
                if ($check_policies == 0 || count($sensor_policies) == 0)
                {
                     $sensor = new Av_sensor($id);
                     $sensor->delete_from_db($conn);
                    
                     $data['status']  = 'success';
                     $data['data']    = _('Sensor removed successfully');
                     
                     //Remove sensor list from Session
                     unset($_SESSION['_sensor_list']);
                }                
          
                $db->close();            
            }
            catch(Exception $e)
            {
                $data['status']  = 'error';
                $data['data']    = _( $e->getMessage() );
            }
        }

    break;
    
    case 'stats':
        
        require_once 'get_sensors.php';        
        
        try
        {
            $total_sensors  = 0;
            $active_sensors = 0;
            
            list($sensor_list, $err) = server_get_sensors();
                           
            if (!empty($sensor_list)) 
            {                
                foreach($sensor_list as $sensor => $info) 
                {                                               
                    $sensor_stack[$sensor] = 1;
                }
            }                
            
            $db    = new ossim_db();
            $conn  = $db->connect();
            
            $sensor_list = Av_sensor::get_all($conn);
            
            $db->close();

            if (is_array($sensor_list) && !empty($sensor_list)) 
            {
                $total_sensors = count($sensor_list);
                
                foreach($sensor_list as $s_data) 
                {
                    if ($sensor_stack[$s_data['ip']] == 1) 
                    {
                        $active_sensors++;                        
                    }
                }
            }
            
            $data['status']  = 'success';
            $data['data']    = array(
                'total'   => $total_sensors,
                'actives' => $active_sensors
            );                
        }
        catch(Exception $e)
        {
            $data['status']  = 'error';
            $data['data']    = _('Sorry, operation was not completed due to an unknown error');
        }        
    
    break;
}

echo json_encode($data);