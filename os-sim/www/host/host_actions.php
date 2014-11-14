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

Session::logcheck('environment-menu', 'PolicyHosts');

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

if (Token::verify('tk_host_form', POST('token')) == FALSE)
{		
	$data['status']  = 'error';
	$data['data']    = Token::create_error_message();
	
	echo json_encode($data);
    exit();	
}

switch($action)
{        
    case 'add_port':         
        
        $validate = array(            
            'ctx'      =>  array('validation' => 'OSS_HEX',                  'e_message'  =>  'illegal:' . _('CTX')),
            'port'     =>  array('validation' => 'OSS_PORT',                 'e_message'  =>  'illegal:' . _('Port')),
            'protocol' =>  array('validation' => 'OSS_PROTOCOL',             'e_message'  =>  'illegal:' . _('Protocol')),
            'service'  =>  array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT',  'e_message'  =>  'illegal:' . _('Service'))
        );         
        
        
        $data['status']  = 'OK';
        $data['data']    = _('Port added successfully');

                                     
        $validation_errors = validate_form_fields('POST', $validate);
            
            
        if (is_array($validation_errors) && !empty($validation_errors))
        {
            $data['status']  = 'error';
            $data['data']    = $validation_errors;
        }
        else
        {
            try
            {            
                $port     = POST('port');
                $protocol = POST('protocol');
                $service  = POST('service');
                $ctx      = POST('ctx');
            
                $db    = new ossim_db();
                $conn  = $db->connect();
                
                $n_ports  = Port::get_list($conn, " AND port_number = $port and protocol_name = '$protocol'");
                        
                if(count($n_ports) == 0) 
                {
                    Port::insert($conn, $port, $protocol, $service, '', $ctx);
                }
                else
                {
                    $data['status']  = 'warning';
                    $data['data']    = _('Warning! Port was added previously');
                }
                
                $db->close();
            }
            catch(Exception $e)
            {
                $data['status']  = 'error';
                $data['data']    = _('Error! Port could not be added');
            }
        }
        
    break;

    case 'delete_host':
        
        $validate = array(
            'asset_id'  =>  array('validation' => 'OSS_HEX',  'e_message'  =>  'illegal:' . _('Host ID'))
        );   
                
        $host_id = POST('asset_id');
        
        $validation_errors = validate_form_fields('POST', $validate);
        
        $db    = new ossim_db();
        $conn  = $db->connect();

        $can_i_modify_ips = Asset_host::can_i_modify_ips($conn, $host_id);

        $db->close();  
            
        if ((is_array($validation_errors) && !empty($validation_errors)) || $can_i_modify_ips == FALSE)
        {
            $data['status']  = 'error';
            $data['data']    = _('Error! Host ID not allowed.  Host could not be deleted');
        }
        else
        {
            try
            {
                $db    = new ossim_db();
                $conn  = $db->connect();
            
                Asset_host::delete_from_db($conn, $host_id, TRUE);
                
                $db->close();
                                                
                $data['status']  = 'OK';
                $data['data']    = _('Host removed successfully');
            
            }
            catch(Exception $e)
            {
                $data['status']  = 'error';
                $data['data']    = _('Error! Host could not be deleted:') . ' ' . $e->getMessage();
            }
        }
         
    break;
    
    case 'remove_icon':
        
        $validate = array(
            'asset_id'  =>  array('validation' => 'OSS_HEX',  'e_message'  =>  'illegal:' . _('Host ID'))
        );   
                
        $host_id = POST('asset_id');
        
        $validation_errors = validate_form_fields('POST', $validate);
            
            
        if (is_array($validation_errors) && !empty($validation_errors))
        {
            $data['status']  = 'error';
            $data['data']    = _('Error! Host ID not allowed.  Icon could not be removed');
        }
        else
        {
            try
            {
                $db    = new ossim_db();
                $conn  = $db->connect();
            
                Asset_host::delete_icon($conn, $host_id);
                
                $db->close();
                
                $data['status']  = 'OK';
                $data['data']    = _('Host icon removed successfully');
            
            }
            catch(Exception $e)
            {
                $data['status']  = 'error';
                $data['data']    = _('Error! Host icon could not be removed');
            }
        }
         
    break;
        
    case 'toggle_a_monitoring':
        
        //Error counter
        $e_counter = 0;
                                 
        //Services        
        $services = base64_decode(POST('services'));
        $services = json_decode($services, TRUE);
        
                
        $data['status'] = 'OK';
        $data['data']   = _('Host services toggled successfully');
        $data['reload_tree'] = TRUE;
        
        $db    = new ossim_db();
        $conn  = $db->connect();
        
        if (is_array($services) && !empty($services))
        {
            foreach ($services as $s_values)
            {
                try
                {
                    //Clean last error
                    ossim_clean_error();
                    
                    //Initialize service data
                    $s_data = array();
                                        
                    //Host ID
                    $p_data['host_id'] = POST('host_id');
                                            
                    $p_id = $s_values['p_id'];                    
                
                    $validate = array(
                        'host_id'   =>  array('validation' => array(OSS_HEX),                               'e_message'  =>  'illegal:' . _('Host ID')),
                        'ip'        =>  array('validation' => array(OSS_IP_ADDR),                           'e_message'  =>  'illegal:' . _('Host IP')),
                        'port'      =>  array('validation' => array(OSS_PORT),                              'e_message'  =>  'illegal:' . _('Port')),
                        'protocol'  =>  array('validation' => array(OSS_DIGIT),                             'e_message'  =>  'illegal:' . _('Protocol')),
                        'service'   =>  array('validation' => array(OSS_ALPHA, OSS_PUNC_EXT),               'e_message'  =>  'illegal:' . _('Service')),
                        'version'   =>  array('validation' => array(OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE), 'e_message'  =>  'illegal:' . _('Version')),
                        'source_id' =>  array('validation' => array(OSS_DIGIT),                             'e_message'  =>  'illegal:' . _('Version')),
                        'nagios'    =>  array('validation' => array(OSS_BINARY),                            'e_message'  =>  'illegal:' . _('Nagios'))
                    );
                    
                    $p_data['ip']         = $s_values['p_value']['ip'];
                    $p_data['port']       = $s_values['p_value']['port'];
                    $p_data['protocol']   = $s_values['p_value']['protocol'];
                    $p_data['service']    = $s_values['p_value']['service'];
                    $p_data['version']    = $s_values['p_value']['version'];
                    $p_data['source_id']  = $s_values['p_value']['source_id'];                     
                    $p_data['nagios']     = ($s_values['p_value']['nagios'] == 1) ? 0 : 1;
                                              
                    $p_function = 'Asset_host_services::save_service_in_db';
                                    
                
                    //Validate service values
                    foreach($validate as $v_key => $v_data)
                    {                        
                        $parameters = $v_data['validation'];
                
                        array_unshift($parameters, $p_data[$v_key]);
                        array_push($parameters, $v_data['e_message']);
                         
                        call_user_func_array('ossim_valid', $parameters);
                                               
                        if (ossim_error())
                        {                            
                            $exp_msg = ossim_get_error();
            
                            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
                        }
                    }
                
                    //Update Nagios
                                        
                    $parameters = array();
                    
                    $parameters[] = $conn;
                    $parameters[] = $p_data['host_id'];                        
                    $parameters[] = $p_data;
                    
                    //Report changes
                    $parameters[] = TRUE;                         
                    
                    call_user_func_array($p_function, $parameters);                    
                }                
                catch(Exception $e)
                {
                    $data['status'] = 'error';
                    
                    //Increasing number of errors
                    $e_counter++;
                }                
            }         
        }        
              
                        
        if ($data['status'] == 'error')
        {
            $data['reload_tree'] = FALSE; 
                        
            if ($e_counter == 0)
            {
                $e_message = _('Sorry, Operation was not completed due to an unknown error');
            }
            else
            {
                $n_size = count($services);                
                               
                if ($e_counter == $n_size && $n_size == 1)
                {
                    $e_message = _('Host service could not be toggled');
                }
                elseif ($e_counter == $n_size && $n_size > 1)
                {
                    $e_message = _('Host services could not be toggled');
                }
                else
                {                  
                    $e_message = _('Some host services could not be toggled');
                    
                    //At least, one service was toggled, we have to clear the cache
                    
                    $data['reload_tree'] = TRUE;                
                }
            }            
                      
            //Formatted message
            $data['data'] = '<div>'._('We Found the following errors').":</div>
					         <div style='padding: 5px;'>".$e_message.'</div>';  
            
        }     
        
        //Enable o disable Nagios in host
        
        $filters = array(
            'where' => "h.id IN (UNHEX('".$p_data['host_id']."')) AND nagios = 1"     
        );
        
        $_host_services = Asset_host_services::get_list($conn, $filters);
        $host_services  = $_host_services[0]; 


        if (count($host_services) >= 1)
        {
            Asset_host_scan::save_plugin_in_db($conn, $p_data['host_id'], 2007);
        }
        else
        {
            Asset_host_scan::delete_plugin_from_db($conn, $p_data['host_id'], 2007);
        }
        
        $db->close();
                  
    break;
    
    case 'delete_properties':
        
        //Error counter
        $e_counter = 0;
                 
        //Properties        
        $properties = base64_decode(POST('properties'));
        $properties = json_decode($properties, TRUE);
                      
        
        $data['status'] = 'OK';
        $data['data']   = _('Host properties removed successfully');
        $data['reload_tree'] = TRUE;
        
       
        $db    = new ossim_db();
        $conn  = $db->connect();
        
        if (is_array($properties) && !empty($properties))
        {
            foreach ($properties as $p_values)
            {
                try
                {                
                    //Clean last error
                    ossim_clean_error();
                    
                    //Initialize property data
                    $p_data = array();
                                        
                    //Host ID
                    $p_data['host_id'] = POST('host_id');
                                            
                    $p_id = $p_values['p_id'];
                    
                    switch($p_id)
                    {
                        //Services
                        case '40':
                            $validate = array(
                                'host_id'  =>  array('validation' => array(OSS_HEX),      'e_message'  =>  'illegal:' . _('Host ID')),
                                'ip'       =>  array('validation' => array(OSS_IP_ADDR),  'e_message'  =>  'illegal:' . _('Host IP')),
                                'port'     =>  array('validation' => array(OSS_PORT),     'e_message'  =>  'illegal:' . _('Port')),
                                'protocol' =>  array('validation' => array(OSS_DIGIT),    'e_message'  =>  'illegal:' . _('Protocol'))
                            );
                            
                            $p_data['ip']       = $p_values['p_value']['ip'];
                            $p_data['port']     = $p_values['p_value']['port'];
                            $p_data['protocol'] = $p_values['p_value']['protocol'];
                                                      
                            $p_function = 'Asset_host_services::delete_service_from_db';
                        break;
                        
                        //MAC
                        case '50':
                            $validate = array(
                                'host_id' =>  array('validation' => array(OSS_HEX),      'e_message'  =>  'illegal:' . _('Host ID')),
                                'ip'      =>  array('validation' => array(OSS_IP_ADDR),  'e_message'  =>  'illegal:' . _('Host IP')),
                                'mac'     =>  array('validation' => array(OSS_MAC),      'e_message'  =>  'illegal:' . _('MAC Address'))
                            );
                            
                            $p_data['ip']  = $p_values['p_value']['ip'];
                            $p_data['mac'] = $p_values['p_value']['mac'];
                            
                            $p_function = 'Asset_host_ips::delete_mac_from_db';
                        break;   
                        
                        //Software
                        case '60':
                            $validate = array(
                                'host_id' =>  array('validation' => array(OSS_HEX),                          'e_message'  =>  'illegal:' . _('Host ID')),
                                'cpe'     =>  array('validation' => array(OSS_ALPHA, OSS_PUNC, OSS_BRACKET), 'e_message'  =>  'illegal:' . _('Software CPE'))                           
                            );
                            
                            $p_data['cpe'] = $p_values['p_value']['cpe'];
                            
                            $p_function = 'Asset_host_software::delete_software_from_db';
                        break;   
                        
                        //Host properties
                        default:
                            $validate = array(
                                'host_id'   =>  array('validation' => array(OSS_HEX),                 'e_message'  =>  'illegal:' . _('Host ID')),
                                'p_id'      =>  array('validation' => array(OSS_DIGIT),               'e_message'  =>  'illegal:' . _('Property ID')),  
                                'hp_value'  =>  array('validation' => array(OSS_ALPHA, OSS_PUNC_EXT), 'e_message'  =>  'illegal:' . _('Property value'))
                            );
                            
                            $p_data['p_id'] = $p_id;
                            $p_data['hp_value'] = $p_values['p_value']['hp_value'];
                            
                            $p_function = 'Asset_host_properties::delete_property_from_db'; 
                        break;
                    }
                    
                    
                    //Validate property values
                    foreach($validate as $v_key => $v_data)
                    {                        
                        $parameters = $v_data['validation'];
       
                        array_unshift($parameters, $p_data[$v_key]);
                        array_push($parameters, $v_data['e_message']);
                         
                        call_user_func_array('ossim_valid', $parameters);
                        
                        if (ossim_error())
                        {                                                       
                            $exp_msg = ossim_get_error();
            
                            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
                        }
                    }
    
                    //Delete property
                    $parameters = array_values($p_data);
                    
                    //Adding BD connection
                    array_unshift($parameters, $conn);
                    
                    //Report changes
                    $parameters[] = TRUE;                 
                    
                    call_user_func_array($p_function, $parameters);
                    
                }
                catch(Exception $e)
                {
                    $data['status'] = 'error';
                                    
                    //Increasing number of errors
                    $e_counter++;
                }             
            }  
        }
            
        $db->close();                
                        
        if ($data['status'] == 'error')
        {
            $data['reload_tree'] = FALSE;
            
            if ($e_counter == 0)
            {
                $e_message = _('Sorry, Operation was not completed due to an unknown error');
            }
            else
            {
                $n_size = count($properties);
                
                if ($e_counter == $n_size && $n_size == 1)
                {
                    $e_message = _('Host property could not be removed');
                }
                elseif ($e_counter == $n_size && $n_size > 1)
                {
                    $e_message = _('Host properties could not be removed');
                }
                else
                {                  
                    $e_message = _('Some host properties could not be removed');
                    
                    //At least, one property was deleted, we have to clear the cache
                    
                    $data['reload_tree'] = TRUE;              
                }
            }
            
            //Formatted message
            $data['data'] = '<div>'._('We Found the following errors').":</div>
					         <div style='padding: 5px;'>".$e_message.'</div>';         
        }       
               
    break;    
       
    case 'save_property':    
      
        //Clean last error
        ossim_clean_error();
        
        //Host ID
        $p_data['host_id'] = POST('host_id'); 
        
        //Property ID
        $p_id = POST('properties');
                
                      
        //Response
        $data['status'] = 'OK';
        $data['data']   = _('Host properties saved successfully');
               
        try
        {
            $db    = new ossim_db();
            $conn  = $db->connect();
                    
            switch($p_id)
            {
                //Services
                case '40':
                    $validate = array(
                        'host_id'    =>  array('validation' => 'OSS_HEX',                  'e_message'  =>  'illegal:' . _('Host ID')),
                        'service_ip' =>  array('validation' => 'OSS_IP_ADDR',              'e_message'  =>  'illegal:' . _('Host IP')),
                        'port'       =>  array('validation' => 'OSS_PORT',                 'e_message'  =>  'illegal:' . _('Port')),
                        'protocol'   =>  array('validation' => 'OSS_PROTOCOL',             'e_message'  =>  'illegal:' . _('Protocol')),
                        'service'    =>  array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT',  'e_message'  =>  'illegal:' . _('Service')),
                        'nagios'     =>  array('validation' => 'OSS_NULLABLE, OSS_BINARY', 'e_message'  =>  'illegal:' . _('Nagios'))
                    ); 
                    
                    $protocol_id = getprotobyname(POST('protocol'));
                    $protocol_id = ($protocol_id === FALSE) ? 6 : $protocol_id;
                                        
                    $p_data['s_data']['ip']        = POST('service_ip');
                    $p_data['s_data']['port']      = POST('port');
                    $p_data['s_data']['protocol']  = $protocol_id;
                    $p_data['s_data']['service']   = POST('service');
                    $p_data['s_data']['nagios']    = intval(POST('nagios'));
                    $p_data['s_data']['source_id'] = 1;
                    $p_data['s_data']['version']   = NULL;

                    $p_function = 'Asset_host_services::save_service_in_db';
                break;
                
                //MAC
                case '50':
                    $validate = array(
                        'host_id' =>  array('validation' => 'OSS_HEX',      'e_message'  =>  'illegal:' . _('Host ID')),
                        'mac_ip'  =>  array('validation' => 'OSS_IP_ADDR',  'e_message'  =>  'illegal:' . _('Host IP')),
                        'mac'     =>  array('validation' => 'OSS_MAC',      'e_message'  =>  'illegal:' . _('MAC Address'))
                    );
                    
                    $p_data['ip']  = POST('mac_ip');
                    $p_data['mac'] = POST('mac');
                    
                    $p_function = 'Asset_host_ips::save_mac_in_db';
                break;   
                
                //Software
                case '60':
                    $validate = array(
                        'host_id' =>  array('validation' => 'OSS_HEX',                          'e_message'  =>  'illegal:' . _('Host ID')),
                        'cpe'     =>  array('validation' => 'OSS_ALPHA, OSS_PUNC, OSS_BRACKET', 'e_message'  =>  'illegal:' . _('Software CPE')),
                    );
                    
                    $p_data['s_data']['cpe']       = POST('cpe');
                    $p_data['s_data']['banner']    = '';
                    $p_data['s_data']['source_id'] = 1;
                    
                    $p_function = 'Asset_host_software::save_software_in_db';
                break;   
                
                //Host properties
                default:
                    $validate = array(
                        'host_id'   =>  array('validation' => 'OSS_HEX',                  'e_message'  =>  'illegal:' . _('Host ID')),
                        'p_value'   =>  array('validation' => 'OSS_ALPHA, OSS_PUNC_EXT',  'e_message'  =>  'illegal:' . _('Property value')),
                        'p_locked'  =>  array('validation' => 'OSS_BINARY',               'e_message'  =>  'illegal:' . _('Property is locked'))
                    );

                    
                    $p_data['p_id']      = $p_id;
                    $p_data['p_value']   = POST('p_value');                    
                    $source_id           = (intval(POST('p_locked')) == 1) ? 1 : 2;                    
                    $p_data['source_id'] = $source_id;
                    
                    $p_function = 'Asset_host_properties::save_property_in_db'; 
                break;      
            }
              
            //General validation
            $validation_errors = validate_form_fields('POST', $validate);
                      
            if (is_array($validation_errors) && !empty($validation_errors))
        	{
        		$data['status'] = 'error';
        		
        		//Formatted message
        		$data['data'] = '<div>'._('We Found the following errors').":</div>
					             <div style='padding: 5px;'>".implode('<br/>', $validation_errors).'</div>'; 
        	}
        	else
        	{        	           	
            	$parameters = array_values($p_data);
                                     
                //Adding BD connection
                array_unshift($parameters, $conn);                
                                                
                //Report changes
                $parameters[] = TRUE; 
                
                call_user_func_array($p_function, $parameters);            
        	}
                
            $db->close();
        }
        catch(Exception $e)
        {
            $data['status'] = 'error'; 
            $data['error']  = _('Host property could not be saved');
        }
        
    break;
}
    

echo json_encode($data);