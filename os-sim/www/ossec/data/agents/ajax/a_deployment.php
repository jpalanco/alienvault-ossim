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

require_once dirname(__FILE__) . '/../../../conf/config.inc';

Session::logcheck('environment-menu', 'EventsHidsConfig');


$validation_errors = array();
$os_type           = $_SESSION['_ossec_os_type'];

$validate = array (
    'sensor_ip' => array('validation' => 'OSS_IP_CIDR_0',  'e_message' => 'illegal:' . _('Sensor IP')),
    'agent_ip'  => array('validation' => 'OSS_IP_CIDR_0',  'e_message' => 'illegal:' . _('Agent IP'))
);

if ($os_type == 'windows')
{ 
    $validate['domain'] = array('validation' => 'OSS_NOECHARS, OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE',  'e_message' => 'illegal:' . _('Domain'));
    $validate['user']   = array('validation' => 'OSS_USER_2',                                           'e_message' => 'illegal:' . _('User'));
    $validate['pass']   = array('validation' => 'OSS_PASSWORD',                                         'e_message' => 'illegal:' . _('Password'));
}


if (GET('ajax_validation') == TRUE)
{
    $data['status'] = 'OK';
    
    $validation_errors = validate_form_fields('GET', $validate);
    if (is_array($validation_errors) && !empty($validation_errors))    
    {
        $data['status'] = 'error';
        $data['data']   = $validation_errors;
    }
    
    echo json_encode($data);    
    exit();
}
else
{
    
    //Checking form token

    if (!isset($_POST['ajax_validation_all']) || POST('ajax_validation_all') == FALSE)
    {
    	if (Token::verify('tk_form_a_deployment', POST('token')) == FALSE)
    	{
    		Token::show_error();
    		
    		exit();
    	}
    }
    
    $validation_errors = validate_form_fields('POST', $validate);
                  
    //Check Token
    if (empty($validation_errors))
    {
        $db    = new ossim_db();
        $conn  = $db->connect();        
        
        $res = Av_center::get_system_info_by_ip($conn, POST('sensor_ip'));    
         
        if ($res['status'] == 'success')
        {
            $sensor_id       = $res['data']['sensor_id'];
            $ossec_server_ip = $res['data']['admin_ip'];
            
            if (!Ossec_utilities::is_sensor_allowed($conn, $sensor_id))
            {         	 
            	 $validation_errors['sensor_ip'] = _('Error! Sensor not allowed');            
            }        
        }
        else
        {
             $validation_errors['sensor_ip'] = _('Error! Unable to validate sensor IP');
        }    
        
        $db->close();    	
    }    
}


if (is_array($validation_errors) && !empty($validation_errors))
{         
    $data['status'] = 'error';       
    
    if (POST('ajax_validation_all') == TRUE)
    {    	
        $data['data'] = $validation_errors;    
    }
    else
    {    
        $data['data'] = '<div>'._('We Found the following errors').":</div>
            <div style='padding: 10px;'>".implode('<br/>', $validation_errors).'</div>';    
    }
} 
else
{    
    if (POST('ajax_validation_all') == TRUE)
    { 
        $data['status'] = 'OK';
        $data['data']   = _('Automatic deployment data checked successfully');  
    }
    else
    {
        $d_data = array(
            'ossec_server_ip' => $ossec_server_ip,
            'sensor_ip'       => POST('sensor_ip'),
            'agent_ip'        => POST('agent_ip')
        );
            
        if ($os_type == 'windows')
        { 
            $d_data['domain']   = POST('domain');       
            $d_data['user']     = POST('user');
            $d_data['password'] = POST('pass');
        }         
        
        try
        {
            $data['status'] = 'success';
            $data['data']   = Ossec_agent::execute_deployment_action($d_data, 'deploy', $os_type);
        }
        catch(Exception $e)
        {
            $data['status'] = 'warning';
            $data['data']   = $e->getMessage();
        }         
    }   
}

echo json_encode($data);
exit();
?>