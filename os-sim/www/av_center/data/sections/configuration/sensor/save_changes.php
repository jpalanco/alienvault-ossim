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
require_once (dirname(__FILE__) . '/../../../../config.inc');

session_write_close();

$validate = array (
	'sensor_networks[]'      => array('validation'=>'OSS_IP_CIDR',                            'e_message' => 'illegal:' . _('Monitored Networks')),
	'sensor_interfaces[]'    => array('validation'=>'OSS_LETTER, OSS_DIGIT, OSS_PUNC',        'e_message' => 'illegal:' . _('Listening Interfaces')),
	'sensor_detectors[]'     => array('validation'=>'OSS_LETTER, OSS_DIGIT, OSS_PUNC',        'e_message' => 'illegal:' . _('Detectors')),
	'mservers[]'             => array('validation'=>"OSS_DIGIT, OSS_DOT, '#', OSS_NULLABLE",  'e_message' => 'illegal:' . _('Mservers')),
	'm_server_ip'            => array('validation'=>'OSS_IP_ADDR',                            'e_message' => 'illegal:' . _('Server IP'))
);


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
	$validation_errors = validate_form_fields('POST', $validate);
			
	//Mservers Validation
		
	$mservers  = 'no';
	
	//Master server
	$server_ip = $_POST['m_server_ip'];
	
	//Local server 
	$l_server_ip_1 = '127.0.0.1';
	
	//Admin IP 
	$l_server_ip_2 = $_POST['admin_ip'];
	
			
	if (is_array($_POST['mservers']) && !empty($_POST['mservers']))
	{
		$aux_mservers = array();
		$size         = count($_POST['mservers']);
				
        foreach($_POST['mservers'] as $server)
        {
            $aux = explode('###', $server);
            
            $s_ip   = trim($aux[0]);
            $s_prio = trim($aux[1]);
            
            
            //Validate IP and Priority
            ossim_valid($s_ip, OSS_IP_ADDR,      'illegal:' . _('IP Address'));
            ossim_valid($s_prio, '0,1,2,3,4,5',  'illegal:' . _('Priority'));
            
            if (ossim_error())
            {
                $validation_errors['mservers'] = ossim_get_error_clean();
                
                ossim_clean_error();
                break;
            }
        
            //SERVER_IP,PORT,SEND_EVENTS(True/False),ALLOW_FRMK_DATA(True/False),PRIORITY (0-5),FRMK_IP,FRMK_PORT
            $aux_mservers[$s_ip] = $s_ip.',40001,True,True,'.$s_prio.','.$s_ip.',40003';
        }
                    
                              
        //Check if local server and admin IP have been added at same time
        $cnd_1 = (count($aux_mservers) > 1);
        $cnd_2 = (array_key_exists($l_server_ip_1, $aux_mservers) && array_key_exists($l_server_ip_2, $aux_mservers));
        
        
        if ($cnd_1 && $cnd_2)
        {
            unset($aux_mservers[$l_server_ip_1]);
            
            if (count($aux_mservers) == 1)
            {
                $aux_mservers = array();
                $server_ip    = $l_server_ip_2; 
            }
        }
              
            
        if (!empty($aux_mservers))
        {
            $mservers = implode(';', $aux_mservers);
        }
    }
    
                 		
	//Collection validation
	if(is_array($_POST['sensor_detectors']) && !empty($_POST['sensor_detectors']))
	{	
		$s_detectors = array_flip(POST('sensor_detectors'));
		
		if (array_key_exists('suricata', $s_detectors) && array_key_exists('snortunified', $s_detectors))
		{
			$validation_errors['sensor_detectors[]'] = _("You can't enable Suricata and Snort at the same time. Choose one of them.");
		}
	}
	
	if (is_array($validation_errors) && !empty($validation_errors))	
	{
		$data['status']  = 'error';
		$data['data']    = $validation_errors;
		
		echo json_encode($data);	
		exit();
	}
	elseif (POST('ajax_validation_all') == TRUE && empty($validation_errors))
	{
		$data['status'] = 'OK';
		
		echo json_encode($data);
		exit();
	}
}


//Action: Save Sensor Configuration
$action = POST('action');

if ($action == 'save_changes')
{
	$system_id = POST('system_id');
	ossim_valid($system_id, OSS_DIGIT, OSS_LETTER, '-', 'illegal:' . _('System ID'));
	
	if (ossim_error())
	{ 
		$data['status']  = 'error';
		$data['data']    = ossim_get_error();
		
		echo json_encode($data);
		exit();
	}
	
	$data = array();
	
	if(is_array($_POST['sensor_networks']) && !empty($_POST['sensor_networks']))
	{
    	$data['sensor_networks'] = implode(',', $_POST['sensor_networks']);
	}
	
	if(is_array($_POST['sensor_interfaces']) && !empty($_POST['sensor_interfaces']))
	{
    	$data['sensor_interfaces'] = implode(',', $_POST['sensor_interfaces']);
	}	
		
	if (isset($_POST['sensor_detectors']))
	{
    	$data['sensor_detectors'] = '';
    	
    	if(is_array($_POST['sensor_detectors']) && !empty($_POST['sensor_detectors']))
    	{
        	//Change deprecated plugin Ossec_av_format by ossec-single-line
        	
        	$s_detectors = array_flip($_POST['sensor_detectors']);    	    	
        	
        	if (array_key_exists('ossec_av_format', $s_detectors))
    		{
    			unset($s_detectors['ossec_av_format']);
    			unset($s_detectors['ossec-single-line']);
    			
    			$s_detectors   = array_flip($s_detectors);
    			$s_detectors[] = 'ossec-single-line';
    			$_POST['sensor_detectors'] = $s_detectors;
    		}    	
        	
        	$data['sensor_detectors'] = implode(',', $_POST['sensor_detectors']);
    	}
	}
	
			
	$data['sensor_mservers']  = $mservers;
	$data['server_server_ip'] = $server_ip;  // Server IP
	
	
	/* If server_ip is 127.0.0.1, we change local IP for real IP */
	$data['framework_framework_ip'] = ($server_ip == $l_server_ip_1) ? $l_server_ip_2 : $server_ip;
	
	$res = Av_center::set_sensor_configuration($system_id, $data);
	
	echo json_encode($res);
}
?>