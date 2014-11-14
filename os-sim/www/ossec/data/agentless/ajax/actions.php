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


$db   = new ossim_db();
$conn = $db->connect();


$permitted_actions = array(
	'add_monitoring_entry'     => '1',
	'delete_monitoring_entry'  => '1',
	'modify_monitoring_entry'  => '1',
	'get_agentless_status'     => '1',
	'modify_host_data'         => '1'
);
   
			   
$sensor_id 	= POST('sensor');
$ip         = POST('ip');
$id         = POST('id');
$action     = POST('action');
$type    	= POST('type');
$frequency 	= POST('frequency');
$state    	= POST('state');
$arguments 	= POST('arguments');
$token 	    = POST('token');


if (!array_key_exists($action, $permitted_actions))
{
	echo 'error###'._('Action not allowed');
	exit();
}

switch ($action)
{
	case 'add_monitoring_entry':
	
		$validate = array (
			'ip'          => array('validation' => 'OSS_IP_ADDR',                           'e_message' => 'illegal:' . _('IP')),
			'sensor'      => array('validation' => "OSS_HEX",                               'e_message' => 'illegal:' . _('Sensor')),
			'type'        => array('validation' => 'OSS_NOECHARS, OSS_SCORE, OSS_LETTER',   'e_message' => 'illegal:' . _('Type')),
			'frequency'   => array('validation' => 'OSS_DIGIT',                             'e_message' => 'illegal:' . _('frequency')),
			'state'       => array('validation' => 'OSS_NOECHARS, OSS_SCORE, OSS_LETTER',   'e_message' => 'illegal:' . _('State')),
			'arguments'   => array('validation' => "OSS_NOECHARS, OSS_TEXT, OSS_SPACE, OSS_AT, OSS_NULLABLE, OSS_PUNC_EXT, '\`', '\<', '\>'", 'e_message' => 'illegal:' . _('Arguments'))
		);
		
	break;
	
	case 'delete_monitoring_entry':

		$validate = array(
			'id'   => array('validation' => 'OSS_DIGIT', 'e_message' => 'illegal:' . _('Id'))
		);

	break;
	
	case 'modify_monitoring_entry':

		$validate = array (
			'id'   		  => array('validation' => 'OSS_DIGIT',                               'e_message' => 'illegal:' . _('Id')),
			'type'        => array('validation' => 'OSS_NOECHARS, OSS_SCORE, OSS_LETTER',     'e_message' => 'illegal:' . _('Type')),
			'frequency'   => array('validation' => 'OSS_DIGIT',                               'e_message' => 'illegal:' . _('frequency')),	
			'state'       => array('validation' => 'OSS_NOECHARS, OSS_SCORE, OSS_LETTER',     'e_message' => 'illegal:' . _('State')),
			'arguments'   => array('validation' => "OSS_NOECHARS, OSS_TEXT, OSS_SPACE, OSS_AT, OSS_NULLABLE, OSS_PUNC_EXT, '\`', '\<', '\>'", 'e_message' => 'illegal:' . _('Arguments'))
		);

	break;
	
	case 'modify_host_data':

		$validate = array (
			'hostname'    => array('validation' => 'OSS_NOECHARS, OSS_SCORE, OSS_LETTER, OSS_DIGIT, OSS_DOT', 	'e_message' => 'illegal:' . _('Hostname')),
			'ip'          => array('validation' => 'OSS_IP_ADDR',                                             	'e_message' => 'illegal:' . _('IP')),
			'sensor'      => array('validation' => "OSS_HEX", 												    'e_message' => 'illegal:' . _('Sensor')),
			'user'        => array('validation' => 'OSS_NOECHARS, OSS_ALPHA, OSS_PUNC_EXT',                   	'e_message' => 'illegal:' . _('User')),
			'descr'       => array('validation' => 'OSS_NOECHARS, OSS_TEXT, OSS_SPACE, OSS_AT, OSS_NULLABLE', 	'e_message' => 'illegal:' . _('Description')),
			'pass'        => array('validation' => 'OSS_PASSWORD',                   							'e_message' => 'illegal:' . _('Password')),
			'passc'       => array('validation' => 'OSS_PASSWORD',                   							'e_message' => 'illegal:' . _('Pass confirm')),
			'ppass'       => array('validation' => 'OSS_PASSWORD, OSS_NULLABLE',     							'e_message' => 'illegal:' . _('Priv. Password')),
			'ppassc'      => array('validation' => 'OSS_PASSWORD, OSS_NULLABLE',     							'e_message' => 'illegal:' . _('Priv. Pass confirm')),
			'use_su'      => array('validation' => 'OSS_BINARY, OSS_NULLABLE',     								'e_message' => 'illegal:' . _('Option use_su'))
		);

	break;

	case 'get_agentless_status':

		$validate = array (
			'sensor'    => array('validation' => "OSS_HEX", 'e_message' => 'illegal:' . _('Sensor'))
		);

	break;	
}
		
$validation_errors = validate_form_fields('POST', $validate);

if($action == 'modify_host_data' )
{
	if (!empty($_POST['pass']) && POST('pass') != POST('passc'))
	{
		$validation_errors['pass']  = _('Password fields are different');
	}
				
	if (!empty($_POST['ppass']) && POST('ppass') != POST('ppassc') )
	{
		$validation_errors['ppass'] = _('Privileged Password fields are different');
	}
}


//Check token

if ($action == 'modify_host_data' )
{
	if (!Token::verify('tk_al_entries', $token) )
	{
    	$validation_errors['token'] = Token::create_error_message();
    }
}
elseif ($action != 'get_agentless_status' )
{
    if (!Token::verify('tk_al_entries', $token) )
    {
    	$validation_errors['token'] = Token::create_error_message();
    }        
}
	
if (is_array($validation_errors) && !empty($validation_errors))
{
	$validation_errors['html_errors'] = "<div>"._('We found the following errors').":</div><div style='padding:5px;'>".implode("<br/>", $validation_errors)."</div>";
	
	$data['status'] = 'error';
	$data['data']   = $validation_errors;
			
	echo json_encode($data);
	exit();
}
	
$data['status'] = 'success';

switch ($action)
{
	case 'add_monitoring_entry':				
		
		if (!Ossec_utilities::is_sensor_allowed($conn, $sensor_id))
        {
        	$data['status'] = 'error';
			$data['data']   = _('Error! Sensor not allowed');            	
        }
        else
        {
            try
            {
                $id = Ossec_agentless::add_monitoring_entry($conn, $ip, $sensor_id, $type, $frequency, $state, $arguments);
                
                if ($id !== FALSE)
    			{												
    				$data['data'] =	"<tr id='m_entry_$id'>
    									<td class='nobborder center' id='al_type_$id'>$type</td>
    									<td class='nobborder center' id='al_frequency_$id'>$frequency</td>
    									<td class='nobborder center' id='al_state_$id'>$state</td>
    									<td class='nobborder left' id='al_arguments_$id'>".Util::htmlentities($arguments)."</td>
    									<td class='center nobborder'>
    										<a onclick=\"add_values('$id')\"><img src='".OSSIM_IMG_PATH."/pencil.png' align='absmiddle' alt='"._('Modify monitoring entry')."' title='"._('Modify monitoring entry')."'/></a>
    										<a onclick=\"delete_monitoring('$id')\" style='margin-right:5px;'><img src='".OSSIM_IMG_PATH."/delete.gif' align='absmiddle' alt='"._('Delete monitoring entry')."' title='"._('Delete monitoring entry')."'/></a>
    									</td>
    								</tr>";
    			}	
    			else
    			{
    				$data['status'] = 'error';
    				$data['data']   = _('Error to Add Monitoring Entry');
    			}                 
          	}
          	catch(Exception $e)
          	{
              	$data['status'] = 'error';
    			$data['data']   = $e->getMessage();
          	} 			          
        }
	break;
		
	case 'delete_monitoring_entry':

        try
        {			
            Ossec_agentless::delete_monitoring_entry($conn, $id);
            
            $data['data'] = _('Monitoring Entry deleted');
        }
        catch(Exception $e)
        {
            $data['status'] = 'error';
			$data['data']   = $e->getMessage();
        }

	break;
						
	case 'modify_monitoring_entry':

		try
        {			
            Ossec_agentless::modify_monitoring_entry($conn, $type, $frequency, $state, $arguments, $id);
            
            $data['data'] =	"<td class='nobborder center' id='al_type_$id'>".Ossec_agentless::get_type($type)."</td>
							 <td class='nobborder center' id='al_frequency_$id'>$frequency</td>
							 <td class='nobborder center' id='al_state_$id'>$state</td>
							 <td class='nobborder left' id='al_arguments_$id'>".Util::htmlentities($arguments)."</td>
							 <td class='center nobborder'>
								<a onclick=\"add_values('$id')\"><img src='".OSSIM_IMG_PATH."/pencil.png' align='absmiddle' alt='"._('Modify monitoring entry')."' title='"._('Modify monitoring entry')."'/></a>
								<a onclick=\"delete_monitoring('$id')\" style='margin-right:5px;'><img src='".OSSIM_IMG_PATH."/delete.gif' align='absmiddle' alt='"._('Delete monitoring entry')."' title='"._('Delete monitoring entry')."'/></a>
							 </td>";

        }
        catch(Exception $e)
        {
            $data['status'] = 'error';
			$data['data']   = $e->getMessage();
        }
	
	break;
		
	case 'modify_host_data':		
		
		if (!Ossec_utilities::is_sensor_allowed($conn, $sensor_id))
        {
        	$data['status'] = 'error';
			$data['data']   = _('Error! Sensor not allowed');            	
        }
        else
        {
            $agentless = Ossec_agentless::get_object($conn, $sensor_id, $ip);                
            
            if (is_object($agentless) && !empty($agentless))
            {
                $status = ($agentless->get_status() != 0)? 1 : 0;
                
                try
                {
                    Ossec_agentless::save_in_db($conn, $ip, $sensor_id, POST('hostname'), POST('user'), POST('pass'), POST('ppass'), POST('use_su'), POST('descr'), $status);
                    
                    $data['data'] = _('Host Successfully updated');
                }
                catch(Exception $e)
                {
                    $data['status'] = 'error';
    				$data['data']   = $e->getMessage();
                }     
            }
            else
            {                   
                $data['status'] = 'error';
				$data['data']   = _('Error! Agentless not found');
            }                   
        }
        							
	break;

	case 'get_agentless_status':
		
		if (!Ossec_utilities::is_sensor_allowed($conn, $sensor_id))
        {
        	$data['status'] = 'error';
			$data['data']   = _('Error! Sensor not allowed');            	
        }
        else
        {
            $sensor_status          = Ossec_control::execute_action($sensor_id, 'status');
            $data['data']['status'] = $sensor_status['service_status']['agentless'];
            $data['data']['reload'] = (file_exists ("/var/tmp/.reload_$sensor_id"))? 'reload_red' : 'reload';
                            
            //Logged user
            $user = Session::get_session_user();                
            
            //Error file
            $agenteless_error_log = "/tmp/_agentless_error_$user".'.log';
                            
            if(file_exists($agenteless_error_log))
            {                                     
                $msgs = file($agenteless_error_log);
                
                $data['data']['log'] = '';
                foreach($msgs as $msg)
                {
                	if(trim($msg) == '')
                	{
                	     continue;
                	}
                	
                	$data['data']['log'] .= $msg . '<br>';
                }
                
                @unlink($agenteless_error_log);
            }                
        }	
		
	break;
}	
	
echo json_encode($data);

$db->close();
?>