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

Session::logcheck('analysis-menu', 'IncidentsIncidents');

require_once 'incident_common.php';

session_write_close();


$allowed_actions = array (
	'newincident'     => 1,
	'editincident'    => 1,
	'delincident'     => 1,
	'newticket'       => 1,
	'delete_ticket'   => 1,
	'e_subscription'  => 1
);

$incident_id = REQUEST('incident_id');
$action      = REQUEST('action');
$ref         = REQUEST('ref');
$type        = REQUEST('type');

$edit        = (isset($_GET['edit']) || isset($_POST['edit'])) ? 1 : 0;

if (ossim_error())
{
	$error_msg = ossim_get_error_clean();
}
elseif (!array_key_exists($action, $allowed_actions))
{
	$error_msg = _("Error! Action not allowed");
}

if (!empty($error_msg))
{
	$config_nt = array(
			'content' => $error_msg,
			'options' => array (
				'type'          => 'nf_error',
				'cancel_button' => FALSE
			),
		'style'   => 'width: 80%; margin: 20px auto; text-align: left;'
	); 
					
	$nt = new Notification('c_nt_oss_error', $config_nt);
	$nt->show();
	exit();
}

$db   	= new ossim_db();
$conn   = $db->connect();

if ($action == 'newincident' || $action == 'editincident') /* Create or modify an incident */
{
	if ($action == 'newincident' && !Session::menu_perms('analysis-menu', 'IncidentsOpen'))
	{
		$error_msg = _("You don't have permission to see this page.")." [ <b>"._("Incidents -> Tickets -> Open Tickets")."</b> "._("menu permission")." ]";
		
		$config_nt = array(
			'content' => $error_msg,
			'options' => array (
				'type'          => 'nf_warning',
				'cancel_button' => FALSE
			),
			'style'   => 'width: 80%; margin: 40px auto; text-align: left; padding:5px;'
		); 
						
		$nt = new Notification('c_nt_oss_error', $config_nt);
		$nt->show();
		exit();	
	}
	elseif ($action == 'editincident' && !Incident::user_incident_perms($conn, $incident_id, $action))
	{
		$config_nt = array(
			'content' => _("You are not allowed to edit this incident because you are neither *admin* or the ticket owner"),
			'options' => array (
				'type'          => 'nf_warning',
				'cancel_button' => FALSE
			),
			'style'   => 'width: 80%; margin: 40px auto; text-align: left; padding:5px;'
		); 
						
		$nt = new Notification('c_nt_oss_error', $config_nt);
		$nt->show();
		exit();	
	}
	
	//Validation array
	$validate_1 = array (
			'title'                => array('validation' => "OSS_ALPHA, OSS_SPACE, OSS_PUNC_EXT, '\>'",              'e_message' => 'illegal:' . _('Title')),
			'priority'             => array('validation' => 'OSS_DIGIT',                                             'e_message' => 'illegal:' . _('Priority')),
			'type'                 => array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT, OSS_SPACE, OSS_SCORE',         'e_message' => 'illegal:' . _('Type')),
			'transferred_user'     => array('validation' => 'OSS_USER_2, OSS_NULLABLE',                              'e_message' => 'illegal:' . _('User')),
			'transferred_entity'   => array('validation' => 'OSS_HEX, OSS_NULLABLE',                                 'e_message' => 'illegal:' . _('Entity')),
			'submitter'            => array('validation' => 'OSS_USER, OSS_PUNC, OSS_NULLABLE',                      'e_message' => 'illegal:' . _('Submitter'))
	);	
	
	
	if ($ref == 'Alarm' || $ref == 'Event')
	{
		$validate_2 = array (
			'src_ips'         => array('validation' => 'OSS_SEVERAL_IP_ADDRCIDR_0, OSS_NULLABLE',                    'e_message' => 'illegal:' . _('Source Ips')),
            'dst_ips'         => array('validation' => 'OSS_SEVERAL_IP_ADDRCIDR_0, OSS_NULLABLE',                    'e_message' => 'illegal:' . _('Dest Ips')),
            'src_ports'       => array('validation' => 'OSS_LETTER, OSS_DIGIT, OSS_PUNC, OSS_SPACE, OSS_NULLABLE',   'e_message' => 'illegal:' . _('Source Ports')),
            'dst_ports'       => array('validation' => 'OSS_LETTER, OSS_DIGIT, OSS_PUNC, OSS_SPACE, OSS_NULLABLE',   'e_message' => 'illegal:' . _('Dest Ports')),
			'backlog_id'      => array('validation' => 'OSS_HEX, OSS_NULLABLE',                                      'e_message' => 'illegal:' . _('Backlog ID')),
            'event_id'        => array('validation' => 'OSS_HEX, OSS_NULLABLE',                                      'e_message' => 'illegal:' . _('Event ID')),
            'alarm_group_id'  => array('validation' => 'OSS_DIGIT, OSS_NULLABLE',                                    'e_message' => 'illegal:' . _('Alarm group ID')),
            'event_start'     => array('validation' => 'OSS_DATETIME, OSS_NULLABLE',                                 'e_message' => 'illegal:' . _('Event start')),
            'event_end'       => array('validation' => 'OSS_DATETIME, OSS_NULLABLE',                                 'e_message' => 'illegal:' . _('Event end'))
       );
	}
	elseif ($ref == 'Metric')
	{
		$validate_2 = array (
			'target'          => array('validation' => 'OSS_TEXT, OSS_NULLABLE',                                      'e_message' => 'illegal:' . _('Target')),
            'metric_type'     => array('validation' => 'OSS_ALPHA, OSS_SPACE, OSS_NULLABLE',                          'e_message' => 'illegal:' . _('Metric Type')),
            'metric_value'    => array('validation' => 'OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE',                'e_message' => 'illegal:' . _('Metric Value')),
            'event_start'     => array('validation' => 'OSS_DATETIME, OSS_NULLABLE',                                  'e_message' => 'illegal:' . _('Event start')),
            'event_end'       => array('validation' => 'OSS_DATETIME, OSS_NULLABLE',                                  'e_message' => 'illegal:' . _('Event end'))
       );
	}
	elseif ($ref == 'Anomaly')
	{
		$validate_2 = array (
			'anom_ip'        => array('validation' => 'OSS_FQDN_IP, OSS_NULLABLE',              'e_message' => 'illegal:' . _('Host')),
			'a_sen'          => array('validation' => 'OSS_FQDN_IP, OSS_NULLABLE',              'e_message' => 'illegal:' . _('Sensor')),
			'port'           => array('validation' => 'OSS_PORT, OSS_NULLABLE',                 'e_message' => 'illegal:' . _('Port')),
			'a_mac'          => array('validation' => 'OSS_MAC, OSS_NULLABLE',                  'e_message' => 'illegal:' . _('New MAC')),
			'a_mac_o'        => array('validation' => 'OSS_MAC, OSS_NULLABLE',                  'e_message' => 'illegal:' . _('Old MAC')),
			'a_os'           => array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE',  'e_message' => 'illegal:' . _('New OS')),
			'a_os_o'         => array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE',  'e_message' => 'illegal:' . _('Old OS')),
			'a_vend'         => array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE',  'e_message' => 'illegal:' . _('New Vendor')),
			'a_vend_o'       => array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE',  'e_message' => 'illegal:' . _('Old Vendor')),
			'a_ver'          => array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE',  'e_message' => 'illegal:' . _('New Version')),
			'a_ver_o'        => array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE',  'e_message' => 'illegal:' . _('Old Version')),
			'a_prot_o'       => array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE',  'e_message' => 'illegal:' . _('New Protocol')),
			'a_prot'         => array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE',  'e_message' => 'illegal:' . _('Old Protocol')),
			'a_date'         => array('validation' => 'OSS_DATETIME, OSS_NULLABLE',             'e_message' => 'illegal:' . _('When'))
       );
	}
	elseif ($ref == 'Vulnerability')
	{
		$validate_2 = array (
			'ip'           => array('validation' => 'OSS_IP_ADDRCIDR_0, OSS_NULLABLE',                            'e_message' => 'illegal:' . _('Host')),
			'port'         => array('validation' => 'OSS_PORT, OSS_NULLABLE',                                     'e_message' => 'illegal:' . _('Port')),
			'risk'         => array('validation' => 'OSS_LETTER, OSS_DIGIT, OSS_PUNC, OSS_SPACE, OSS_NULLABLE',   'e_message' => 'illegal:' . _('Risk')),
			'nessus_id'    => array('validation' => 'OSS_LETTER, OSS_DIGIT, OSS_PUNC, OSS_SPACE, OSS_NULLABLE',   'e_message' => 'illegal:' . _('Nessus/OpenVas ID')),
			'description'  => array('validation' => "OSS_NULLABLE, OSS_AT, OSS_TEXT, OSS_PUNC_EXT, '~'",          'e_message' => 'illegal:' . _('Description'))
       );
	}
	elseif ($ref == 'Custom')
	{
		$fields     = Incident_custom::get_custom_types($conn, $type);
		$validate_2 = array();
		
		if (is_array($fields) && !empty($fields))
		{
			foreach ($fields as $field)
			{
				$params = get_params_field($field);
				
				if (preg_match('/vfield/', $params['class']))
				{
					$validate_2[$params['name']] = array('validation' => $params['validation'], 'e_message' => 'illegal:' . _($field['name']));
				}
			}
		}
	}

	if ($action == 'editincident')
	{
		$validate_1['incident_id'] = array('validation' => 'OSS_DIGIT',  'e_message' => 'illegal:' . _('Incident ID'));
	}
		
	$validate = array_merge($validate_1, $validate_2);
	
	/*
	echo '<pre>";
		print_r($validate);
	echo "</pre>";
	*/
	
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
        //Check Token

        if (!isset($_POST['ajax_validation_all']) || POST('ajax_validation_all') == FALSE)
        {
            if (!Token::verify('tk_f_incident', POST('token')))
            {
                $config_nt = array(
                        'content' => _('Action not allowed'),
                        'options' => array (
                            'type'          => 'nf_error',
                            'cancel_button' => FALSE
                       ),
                    'style'   => 'width: 80%; margin: 20px auto; text-align: left;'
               ); 
                                
                $nt = new Notification('c_nt_oss_error', $config_nt);
                $nt->show();
                exit();
            }
        }
    
		$validation_errors = validate_form_fields('POST', $validate);
		
		if (empty($validation_errors['transferred_user']) && empty($validation_errors['transferred_entity']) )
		{
			if (POST('transferred_user') == '' &&  POST('transferred_entity') == ''){
				$validation_errors['transferred_user'] = _("Error in the 'Assign To' field (missing required field)");
			}
		}
								
		if (POST('ajax_validation_all') == TRUE)
		{
			$data['data'] = $validation_errors;    	
							
			if (is_array($validation_errors) && !empty($validation_errors))
			{
				$data['status'] = 'error';
				echo json_encode($data);
			}
			else
			{
				$data['status'] = 'OK';
				echo json_encode($data);
			}
			exit();
		}
		else
		{
			if (is_array($validation_errors) && !empty($validation_errors))
			{
				$data['status'] = 'error';
				$data['data']   = $validation_errors;
			}
			else
			{
				$data['status'] = 'OK';
				
				//Timezone
				$tz     = Util::get_timezone();
				$timetz = 3600*$tz;
				
				
				if ($ref == 'Alarm' or $ref == 'Event')
				{
					$vars = array(
						'title',
						'type',
						'submitter',
						'priority',
						'src_ips',
						'dst_ips',
						'src_ports',
						'dst_ports',
						'backlog_id',
						'event_id',
						'alarm_group_id',
						'event_start',
						'event_end',
						'transferred_user',
						'transferred_entity'
					);
					
					foreach($vars as $v) {
						$$v = POST("$v");
					}
									
					if ($action == 'newincident')
					{
						if($ref == 'Alarm')
						{
							$incident_id = Incident::insert_alarm($conn, $title, $type, $submitter, $priority, $src_ips, $dst_ips, $src_ports, $dst_ports, $event_start, $event_end, $backlog_id, $event_id, $alarm_group_id, $transferred_user, $transferred_entity);
						}
						else
						{
							$incident_id = Incident::insert_event($conn, $title, $type, $submitter, $priority, $src_ips, $dst_ips, $src_ports, $dst_ports, $event_start, $event_end, $transferred_user, $transferred_entity);
						}
					}
					elseif ($action == 'editincident')
					{
						$method = ($ref == 'Alarm') ? 'update_alarm' : 'update_event';
						Incident::$method($conn, $incident_id, $title, $type, $submitter, $priority, $src_ips, $dst_ips, $src_ports, $dst_ports, $event_start, $event_end, $transferred_user, $transferred_entity);
					}
	   		   	}
				elseif ($ref == 'Metric')
				{	
					$vars = array(
						'title',
						'type',
						'submitter',
						'priority',
						'target',
						'metric_type',
						'metric_value',
						'event_start',
						'event_end',
						'transferred_user',
						'transferred_entity'
					);
					
					foreach($vars as $v) 
					{
						$$v = POST("$v");
					}
							
					
					if ($action == 'newincident')
					{
						$incident_id = Incident::insert_metric($conn, $title, $type, $submitter, $priority, $target, $metric_type, $metric_value, $event_start, $event_end, $transferred_user, $transferred_entity);
					}
					elseif ($action == 'editincident')
					{
						Incident::update_metric($conn, $incident_id, $title, $type, $submitter, $priority, $target, $metric_type, $metric_value, $event_start, $event_end, $transferred_user, $transferred_entity);
					}
				}
				elseif ($ref == 'Anomaly')
				{
					$anom_type = POST('anom_type');
					
					if ($anom_type == 'mac') 
					{
						$vars = array(
							'title',
							'type',
							'submitter',
							'priority',
							'a_sen',
							'a_date',
							'a_mac',
							'a_mac_o',
							'a_vend',
							'a_vend_o',
							'anom_ip',
							'transferred_user',
							'transferred_entity'
						);

						foreach($vars as $v) {
							$$v = POST("$v");
						}
						
						$anom_data_orig = array(
							$a_sen,
							$a_date,
							$a_mac_o,
							$a_vend_o
						);
						
						$anom_data_new = array(
							$a_sen,
							$a_date,
							$a_mac,
							$a_vend
						);
						
						if ($action == 'newincident')
						{
							$incident_id = Incident::insert_anomaly($conn, $title, $type, $submitter, $priority, 'mac', $anom_ip, $anom_data_orig, $anom_data_new, $transferred_user, $transferred_entity);
						}
						elseif ($action == 'editincident')
						{
							Incident::update_anomaly($conn, $incident_id, $title, $type, $submitter, $priority, 'mac', $anom_ip, $anom_data_orig, $anom_data_new, $transferred_user, $transferred_entity);
						}
					} 
					elseif ($anom_type == 'service') 
					{
						$vars = array(
							'title',
							'type',
							'submitter',
							'priority',
							'a_sen',
							'a_date',
							'a_port',
							'a_prot_o',
							'a_prot',
							'anom_ip',
							'a_ver',
							'a_ver_o',
							'transferred_user',
							'transferred_entity'
						);
						
						foreach($vars as $v) 
						{
							$$v = POST("$v");
						}
						
						$anom_data_orig = array($a_sen,
							$a_date,
							$a_port,
							$a_prot_o,
							$a_ver_o
						);
						
						$anom_data_new = array(
							$a_sen,
							$a_date,
							$a_port,
							$a_prot,
							$a_ver
						);
						
						if ($action == 'newincident')
						{
							$incident_id = Incident::insert_anomaly($conn, $title, $type, $submitter, $priority, 'service', $anom_ip, $anom_data_orig, $anom_data_new, $transferred_user, $transferred_entity);
						}
						elseif ($action == 'editincident')
						{
							Incident::update_anomaly($conn, $incident_id, $title, $type, $submitter, $priority, 'service', $anom_ip, $anom_data_orig, $anom_data_new, $transferred_user, $transferred_entity);						
						}
					} 
					elseif ($anom_type == 'os') 
					{
						$vars = array(
							'title',
							'type',
							'submitter',
							'priority',
							'a_sen',
							'a_date',
							'a_os',
							'a_os_o',
							'anom_ip',
							'transferred_user',
							'transferred_entity'
						);
						
						foreach($vars as $v) {
							$$v = POST("$v");
						}
						
						$anom_data_orig = array(
							$a_sen,
							$a_date,
							$a_os_o
						);
						
						$anom_data_new = array(
							$a_sen,
							$a_date,
							$a_os
						);
						
						if ($action == 'newincident')
						{
							$incident_id = Incident::insert_anomaly($conn, $title, $type, $submitter, $priority, 'os', $anom_ip, $anom_data_orig, $anom_data_new, $transferred_user, $transferred_entity);
						}
						elseif ($action == 'editincident')
						{
							Incident::update_anomaly($conn, $incident_id, $title, $type, $submitter, $priority, 'os', $anom_ip, $anom_data_orig, $anom_data_new, $transferred_user, $transferred_entity);
						}
					} 
				}
				elseif ($ref == 'Vulnerability')
				{
					$vars = array(
						'title',
						'type',
						'submitter',
						'priority',
						'ip',
						'port',
						'nessus_id',
						'risk',
						'description',
						'transferred_user',
						'transferred_entity'
					);
					
					foreach($vars as $v) 
					{
						$$v = POST("$v");
					}
					
										
					if ($action == 'newincident')	
					{
					$incident_id = Incident::insert_vulnerability($conn, $title, $type, $submitter, $priority, $ip, $port, $nessus_id, $risk, $description, $transferred_user, $transferred_entity);
					}
					elseif ($action == 'editincident')
					{
						Incident::update_vulnerability($conn, $incident_id, $title, $type, $submitter, $priority, $ip, $port, $nessus_id, $risk, $description, $transferred_user, $transferred_entity);
					}
				}
				elseif ($ref == 'Custom')
				{
					$vars = array(
						'title',
						'type',
						'submitter',
						'priority',
						'transferred_user',
						'transferred_entity'
					);
        
					foreach($vars as $v) 
					{
						$$v = POST("$v"); 
					}

					$fields = array();
        
					foreach ($_POST as $k => $v) 
					{
						$key = $k;
						
						if (preg_match('/^custom/',$k)) 
						{
							$k           = base64_decode(str_replace('custom_', '', $k)); 
							$item        = explode('_####_', $k);
							$custom_type = (count($item) >= 2) ? $item[1] : 'Textbox';
							
							if ($custom_type == 'File' && $_POST[$key] == 1){
								$v = null;
							}
							
							$fields[$item[0]] =  array ('validate' => 1, 'name' => $item[0], 'content' => $v, 'type'=> $custom_type);
						}
						elseif (preg_match('/^del_custom/',$k) && $action == 'editincident') 
						{
							if ($_POST[$key] == 1)
							{	
								$k           = base64_decode(str_replace('del_custom_','',$k)); 
								$item        = explode('_####_', $k);
								$custom_type = $item[1]; 
								
								$v = null;
								$fields[$item[0]] =  array ('validate' => 1, 'name' => $item[0], 'content' => $v, 'type'=> $custom_type);
							}
						}
					}
		
					  
					// Uploaded "File" type
							
					foreach ($_FILES as $k => $v) 
					{
						if (preg_match('/^custom/',$k)) 
						{
							$content = $v['tmp_name'];
							$k       = base64_decode(str_replace('custom_','',$k)); 
							$item    = explode('_####_', $k);
															
							if (is_uploaded_file($v['tmp_name']) && !$v['error']){
								$content = file_get_contents($v['tmp_name']);
							}
							else
							{
								if ($v['name'] != '')
								{
									$content = _('File not uploaded. Error: '.$v['error']);
								}
							}
											
							if (!empty($content)){
								$fields[$item[0]] =  array ('validate' => 0, 'name' => $item[0], 'content' => $content, 'type'=> 'File');
							}
						}
					}
                    
					if ($action == 'newincident')
					{
						$incident_id = Incident::insert_custom($conn, $title, $type, $submitter, $priority, $transferred_user, $transferred_entity, $fields);
					}
					elseif ($action == 'editincident')
					{
						Incident::update_custom($conn, $incident_id, $title, $type, $submitter, $priority, $transferred_user, $transferred_entity, $fields);
					}
				}
																			
                $db->close();
                ?>
                <script type='text/javascript'>
                    if (typeof(parent.GB_hide) == 'function')
                    {
                        parent.GB_hide()
                    } 
                    
                    document.location.href='<?php echo Menu::get_menu_url('../incidents/index.php', 'analysis', 'tickets', 'tickets')?>';
                </script>
                <?php
                exit();
                
			}
		}
	}
}
elseif ($action == 'delincident') /* Remove an incident */
{
    if (!Token::verify('tk_delete_incident', POST('token')))
    {
        $config_nt = array(
                'content' => _('Action not allowed'),
                'options' => array (
                    'type'          => 'nf_error',
                    'cancel_button' => FALSE
               ),
            'style'   => 'width: 80%; margin: 20px auto; text-align: left;'
       ); 
                        
        $nt = new Notification('c_nt_oss_error', $config_nt);
        $nt->show();
        exit();
    }

    $incident_id = POST('incident_id');
    
	$validate = array (
		'incident_id'  => array('validation' => 'OSS_DIGIT',   'e_message' => 'illegal:' . _('Incident ID'))
	);
	
	$validation_errors = validate_form_fields('POST', $validate);
		
	// Only admin, entity admin and ticket owner
	if (!Incident::user_incident_perms($conn, $incident_id, $action)){
		$validation_errors['extended_validatation'] = _('You are not allowed to delete this incident because you are neither *admin* or the ticket owner');
	}
		
	if (is_array($validation_errors) && !empty($validation_errors))
	{
		$data['data']   = $validation_errors;    	
		$data['status'] = 'error';
		echo json_encode($data);
	}
	else
	{
		Incident::delete($conn, $incident_id);
        $db->close();
		$data['status'] = 'OK';
		echo json_encode($data);
	}
}
elseif ($action == 'newticket') /* Create a new ticket */
{
	$validate = array (
		'incident_id'          => array('validation' => 'OSS_DIGIT',                                            'e_message' => 'illegal:' . _('Incident ID')),
		'prev_prio'            => array('validation' => 'OSS_DIGIT',                                            'e_message' => 'illegal:' . _('Priority')),
		'priority'             => array('validation' => 'OSS_DIGIT',                                            'e_message' => 'illegal:' . _('Priority')),
		'prev_status'          => array('validation' => 'OSS_ALPHA',                                            'e_message' => 'illegal:' . _('Status')),
		'status'               => array('validation' => 'OSS_ALPHA',                                            'e_message' => 'illegal:' . _('Status')),
		'transferred_user'     => array('validation' => 'OSS_USER, OSS_NULLABLE',                               'e_message' => 'illegal:' . _('User')),
		'transferred_entity'   => array('validation' => 'OSS_HEX, OSS_NULLABLE',                                'e_message' => 'illegal:' . _('Entity')),
		'description'          => array('validation' => "OSS_TEXT, OSS_PUNC_EXT, '\<\>\¡\¿\~'",				    'e_message' => 'illegal:' . _('Description')),
		'action_txt'           => array('validation' => "OSS_TEXT, OSS_PUNC_EXT, '\<\>\¡\¿\~', OSS_NULLABLE", 	'e_message' => 'illegal:' . _('Action')),
		'tags[]'               => array('validation' => 'OSS_DIGIT, OSS_NULLABLE',                              'e_message' => 'illegal:' . _('Tags'))
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
		else
		{
			if ($_GET['name'] == 'status')
			{
				$status = GET($_GET['name']);
				
				if (Incident::chk_status($status) == false){
					$validation_errors[$_GET['name']] = _('Status not allowed').'. <br/>'._("Allowed values are: 'Open', 'Assigned', 'Studying', 'Waiting', 'Testing' or 'Closed'");
				}
			}
			elseif ($_GET['name'] == 'priority')
			{
				$priority = intval(GET($_GET['name']));
				
				if ($priority < 1 || $priority > 10){
					$validation_errors[$_GET['name']] = _('Priority not allowed').'. <br/>'._('Priority should be a value between 1 and 10');
				}
			}
			
			if (is_array($validation_errors) && !empty($validation_errors))	
			{
				$data['status'] = 'error';
				$data['data']   = $validation_errors;
			}
		
		}
		
		echo json_encode($data);	
		exit();
	}
	else
	{
		$_POST['incident_id'] = GET('incident_id');
				
		$login               =  Session::get_session_user();
		$incident_id         =  POST('incident_id');
		$prev_prio           =  POST('prev_prio');
		$priority            =  POST('priority');
		$prev_status         =  POST('prev_status');
		$status              =  POST('status');
		$transferred_user    =  POST('transferred_user');
		$transferred_entity  =  POST('transferred_entity');
		$tags                =  POST('tags');
		$tags		         = (empty($tags)) ? array() : $tags;
		
		
		//Cleaning the description and action fields
		$description = Util::htmlentities(POST('description'), ENT_NOQUOTES);
		$action      = Util::htmlentities(POST('action_txt'), ENT_NOQUOTES);
		
		/*			
					 
        $pattern     =  array("/&#147;|&#148;/", "/&acute;|`/");
        $replacement =  array('"', "'");
        
		$description = preg_replace($pattern, $replacement, $description);
		$action      = preg_replace($pattern, $replacement, $action);
        
		
		DEPRECATED
		$description = html_entity_decode($description, ENT_QUOTES, 'ISO-8859-1');
		$action      = html_entity_decode($action, ENT_QUOTES, 'ISO-8859-1');
		
		$description = Incident_ticket::clean_html_tags($description);
        $action      = Incident_ticket::clean_html_tags($action);
		
				           
        $description = clean_inc_ic($description, OSS_TEXT, OSS_PUNC_EXT, "\t", "\>", "\<");
        $action      = clean_inc_ic($action,      OSS_TEXT, OSS_PUNC_EXT, "\t", "\>", "\<");
        
        */
        
		$_POST['description'] = $description;
		$_POST['action_txt']  = $action;
		
		$description = (empty($description)) ? '' : '<!--wiki-->' . $description;
		$action      = (empty($action)) ? '' : '<!--wiki-->' . $action;
		
		$validation_errors = validate_form_fields('POST', $validate);
				
		if (is_array($validation_errors) && empty($validation_errors))
		{
			$transferred = ($transferred_user != '') ? $transferred_user : $transferred_entity;
						
			if (Incident::chk_status($prev_status) == FALSE || Incident::chk_status($status) == FALSE)
			{
				$validation_errors['status'] = _('Status not allowed').'. <br/>'._("Allowed values are: 'Open', 'Assigned', 'Studying', 'Waiting', 'Testing' or 'Closed'");
			}
			
			if (($priority < 1 || $priority > 10) || ($prev_prio < 1 || $prev_prio > 10)){
				$validation_errors['priority'] = _('Priority not allowed').'. <br/>'._('Priority should be a value between 1 and 10');
			}
								
			
			if ($transferred != '' && !Incident::user_incident_perms($conn, $incident_id, 'newticket'))
			{
				$validation_errors['extended_validatation'] = _('You are not allowed to transfer this incident because you are neither *admin* or the ticket owner');
			}
			
			if ($priority != $prev_prio && !Incident::user_incident_perms($conn, $incident_id, 'newticket'))
			{
				$validation_errors['extended_validatation'] = _('You are not allowed to change priority of this incident because you are neither *admin* or the ticket owner');
			}
			
			if ($status != $prev_status && !Incident::user_incident_perms($conn, $incident_id, 'newticket'))
			{
				$validation_errors['extended_validatation'] = _('You are not allowed to change status of this incident because you are neither *admin* or the ticket owner');
			}
		}
					
		if (POST('ajax_validation_all') == TRUE)
		{
			$data['data'] = $validation_errors;    	
							
			if (is_array($validation_errors) && !empty($validation_errors))
			{
				$data['status'] = 'error';
				echo json_encode($data);
			}
			else
			{
				$data['status'] = 'OK';
				echo json_encode($data);
			}
			exit();
		}
		else
		{
			if (is_array($validation_errors) && !empty($validation_errors))
			{
				$data['status'] = 'error';
				$data['data']   = $validation_errors;
			}
			else
			{
				$data['status'] = 'OK';
				
				$attachment = null;				
				if (isset($_FILES['attachment']) && $_FILES['attachment']['tmp_name'])
				{
					$attachment            = $_FILES['attachment'];
					$attachment['content'] = file_get_contents($attachment['tmp_name']);
					unlink($attachment['tmp_name']);
				} 
                
                Incident_ticket::insert($conn, $incident_id, $status, $priority, $login, $description, $action, $transferred, $tags, $attachment);
				?>
				<script type='text/javascript'>								
					//Refresh Header
					/* Deprecated header
					if (typeof(top.frames['header']) != 'undefined' && top.frames['header'] != null){ 
						top.frames['header'].document.location.reload();
					}
					*/
					document.location.href='incident.php?id=<?php echo $incident_id?>&edit=<?php echo $edit?>';
				</script>
				
				<?php
														
				$db->close();		
				exit();
			}
		}
	}
}
elseif ($action == 'delete_ticket') /* Delete a ticket */
{
	$incident_id  = $_POST['incident_id'] = GET('incident_id');
	$ticket_id    = $_POST['ticket_id']   = GET('ticket_id');

	$validate = array (
		'incident_id'  => array('validation'=>'OSS_DIGIT',  'e_message' => 'illegal:' . _('Incident ID')),
		'ticket_id'    => array('validation'=>'OSS_DIGIT',  'e_message' => 'illegal:' . _('Ticket ID'))
	);
	
	$validation_errors = validate_form_fields('POST', $validate);

	if (is_array($validation_errors) && empty($validation_errors))
	{
		if (!Incident_ticket::user_tickets_perms($conn, $ticket_id) || !Incident::user_incident_perms($conn, $incident_id, 'show'))
		{
			$validation_errors['extended_validatation'] = _('You are not allowed to delete this ticket because you are neither *admin* or the ticket owner');
		}
	}
				
	if (POST('ajax_validation_all') == TRUE)
	{
		$data['data'] = $validation_errors;    	
						
		if (is_array($validation_errors) && !empty($validation_errors))
		{
			$data['status'] = 'error';
			echo json_encode($data);
		}
		else
		{
			$data['status'] = 'OK';
			echo json_encode($data);
		}
		exit();
	}
	else
	{
		if (is_array($validation_errors) && !empty($validation_errors))
		{
			$data['status'] = 'error';
			$data['data']   = $validation_errors;
			
		}
		else
		{
			$data['status'] = 'OK';
						
			Incident_ticket::delete($conn, $ticket_id);
			
			$db->close();
			header("Location: incident.php?id=$incident_id&edit=$edit");
			exit();
		}
	}
}
elseif ($action == 'e_subscription') /* Subscriptions Management */
{
	$incident_id    = $_POST['incident_id'] = GET('incident_id');
	$login          = POST('login');
	
	
	$validate = array (
		'incident_id'  => array('validation' => 'OSS_DIGIT',   'e_message' => 'illegal:' . _('Incident ID')),
		'login'        => array('validation' => 'OSS_USER_2',  'e_message' => 'illegal:' . _('Email changes to'))
	);
	
	$validation_errors = validate_form_fields('POST', $validate);

	if (is_array($validation_errors) && empty($validation_errors))
	{
		if (!Incident::user_incident_perms($conn, $incident_id, $action)){
			$validation_errors['extended_validatation'] = _('You are not allowed to subscribe a new user because you are neither *admin* or the ticket owner');
		}
	}
			
	if (POST('ajax_validation_all') == TRUE)
	{
		$data['data'] = $validation_errors;    	
						
		if (is_array($validation_errors) && !empty($validation_errors))
		{
			$data['status'] = 'error';
			echo json_encode($data);
		}
		else
		{
			$data['status'] = 'OK';
			echo json_encode($data);
		}
		exit();
	}
	else
	{
		if (is_array($validation_errors) && !empty($validation_errors))
		{
			$data['status'] = 'error';
			$data['data']   = $validation_errors;
		}
		else
		{
			$data['status'] = 'OK';
			$action         = POST('s_action');
			
			if ($action == 'subscribe')
			{
			    Incident::insert_subscription($conn, $incident_id, $login);
			}
           	elseif ($action == 'unsubscribe')
           	{ 
			    Incident::delete_subscriptions($conn, $incident_id, $login);
		    }
			
			$db->close();
			
			header("Location: incident.php?id=$incident_id&edit=$edit");
			exit();
		}
	}
}

if (is_array($data['data']) && !empty($data['data']))
{
	$txt_error = "<div>"._('We found the following errors').":</div>
						  <div style='padding:0px 3px 3px 15px;'>".implode("<br/>", $data['data'])."</div>";				
					
	$config_nt = array(
		'content' => $txt_error,
		'options' => array (
			'type'          => 'nf_error',
			'cancel_button' => FALSE
		),
		'style'   => 'width: 80%; margin: 20px auto; text-align: left;'
	); 
					
	$nt = new Notification('nt_1', $config_nt);
	$nt->show();
}
?>