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
    $response['error']    = TRUE ;
    $response['critical'] = TRUE;
    $response['msg']      = _('You do not have permissions to see this section');
    
    echo json_encode($response);
    
    exit -1;
}



/*
 * <------------------------   BEGINNING OF THE FUNCTIONS   ------------------------> 
 */

function set_scan_error_message($wizard, $msg)
{
    $wizard->set_step_data('scan_step', -1); 
    
    $wizard->set_step_data('scan_error_msg', $msg);
    
    $wizard->save_status();
}


function modify_scan_networks($conn, $wizard, $data)
{
    $step = intval($wizard->get_step_data('scan_step'));
    
    if ($step == 1 || $step == 2)
    {
        $response['error'] = TRUE ;
    	$response['msg']   = _('There is a NMAP scan running, you have to wait until it completes.');
    	
    	return $response;
    }
        
    $ids = array_keys($data['nets']); 

    ossim_valid($ids,   OSS_HEX,    'illegal:' . _("Network ID"));
    
    if (ossim_error()) 
    {
        $response['error'] = TRUE ;
    	$response['msg']   = ossim_get_error();
    	
    	ossim_clean_error();
    	
    	return $response;
    }
    
    $nets     = array();
    $ip_count = 0;
    
    foreach ($ids as $id)
    {
        $cidrs = Asset_net::get_ips_by_id($conn, $id);
        
        $cidrs = preg_replace('/\s*,\s*/', ' ', $cidrs);
        
        $nets[$id] = trim($cidrs);
        
        $cidr_list = explode(' ', $cidrs);
        
        foreach ($cidr_list as $cidr)
        {
            list($dir, $mask) = explode('/', $cidr);
            
            if ($mask > 0 && $mask <= 32)
            {
                $ip_count += 1 << (32 - $mask);
            }
        }
    }
    
    $wizard->clean_step_data();
    
    $wizard->set_step_data('scan_step', 0); 
    
    $wizard->set_step_data('scan_nets', $nets);
    
    $wizard->set_step_data('scan_ips_count', $ip_count);
    
    $wizard->save_status();


    $response['error']         = FALSE ;
    $response['data']['total'] = Util::number_format_locale($ip_count);

    return $response;
}


function do_ping($wizard)
{
    $step = intval($wizard->get_step_data('scan_step'));
    
    if ($step == 0)
    {
        $nets = $wizard->get_step_data('scan_nets');
        
        if (count($nets) < 1)
        {
        	$msg = _('Invalid networks selected to scan');
        	set_scan_error_message($wizard, $msg);
        	
        	$response['error'] = TRUE;
        	
        	return $response;
            
        }

        $nets = implode(' ', $nets);

        $obj  = new Scan($nets);
        
        // ping scan
        $obj->search_hosts();
        
        $wizard->set_step_data('scan_step', 1);
        
    }
    else
    {
        $obj  = new Scan();
    }
    

    $data   = array();
     
    $status = $obj->get_status();
    

    if ($status == 'Searching Hosts')
    {
        $data['finish'] = FALSE;

    }
    elseif ($status == 'Search Finished')
    {
        
        $total = $obj->get_num_of_hosts();
        
        if ($total == 0)
        {
            $next_step = 3;
            
            $obj->delete_data();
        }
        else
        {
            $res = $obj->launch_scan();
            
            if ($res === FALSE)
            {
                $msg = _('Impossible to launch NMAP scan');
            	set_scan_error_message($wizard, $msg);
            	
            	$response['error'] = TRUE;
            	
                return $response;
            }
            
            $next_step = 2;

        }
        
        $wizard->set_step_data('scan_hosts', $total);
        $wizard->set_step_data('scan_step', $next_step);
        
        $data['finish'] = TRUE;
        
    }
    else
    {
        $msg = _("Invalid NMAP status ($status). Expecting 'Searching Hosts' or 'Search Finished'");
    	set_scan_error_message($wizard, $msg);
    	
    	$response['error'] = TRUE;
    	
        return $response;
    }
    
    $response['error'] = FALSE;
    $response['data']  = $data;
        
    $wizard->save_status();
    

    return $response;
}


function check_scan_progress($conn, $wizard)
{   
    $data   = array();
    
    $obj    = new Scan();

    $status = $obj->get_status();
    
    //Get status
    if ($status == 'Scan Finished') // If nmap is done
    {
        $info   = array();
        
        $result = $obj->get_results();

        $obj->delete_data();
        
        
        $info = Welcome_wizard::format_result_scan($conn, $result);
        
        
        $wizard->set_step_data('scan_step', 3);
        $wizard->set_step_data('scan_info', $info);
        
        $data['finish'] = TRUE;
        
    }        
    elseif ($status == 'Scanning Hosts')
    {
        $progress = $obj->get_progress();
        
        $percent  = ($progress['hosts_scanned'] / $progress['total_hosts']) * 100;
        
                    
        $data['finish']  = FALSE;
        $data['percent'] = round($percent);
        $data['current'] = $progress['hosts_scanned'];
        $data['total']   = $progress['total_hosts'];
        
        if ($progress['remaining'] == -1)
        {
            $data['time'] = _('Calculating Remaining Time');
        }
        else
        {
            $data['time'] = Welcome_wizard::format_time($progress['remaining']) . ' ' . _('remaining');
        }
           
    }
    else
    {
        $msg = _("Invalid NMAP status ($status). Expecting 'Scanning Hosts' or 'Scan Finished'");
    	set_scan_error_message($wizard, $msg);
    	
    	$response['error'] = TRUE;
    	
        return $response;
    }
    
    $response['error'] = FALSE;
    $response['data']  = $data;
    
    $wizard->save_status();
         
    
    return $response;
    
}


function cancel_scan($wizard)
{
    $step = intval($wizard->get_step_data('scan_step'));
    
    if ($step == 1 || $step ==2)
    {
        $obj = new Scan();
        
        $obj->stop(); 
        
        $obj->delete_data();

    }
    
    $wizard->clean_step_data();
    
    $wizard->save_status();
    
    $response['error'] = FALSE ;

    return $response;
    
}


function schedule_scan($conn, $wizard, $data)
{
    $step = intval($wizard->get_step_data('scan_step'));
    $nets = $wizard->get_step_data('scan_nets');
    
    if ($step != 3 || count($nets) < 1)
    {
        $response['error'] = TRUE ;
    	$response['msg']   = _('NMAP Scan not valid to schedule');
    	
    	return $response;
    }
    
    $sched = $data['sch_opt']; 

    ossim_valid($sched,   OSS_DIGIT,    'illegal:' . _("Schedule Option"));
    
    if (ossim_error()) 
    {
        $response['error'] = TRUE ;
    	$response['msg']   = ossim_get_error();
    	
    	ossim_clean_error();
    	
    	$wizard->set_step_data('scan_nets', -1);
    	
    	return $response;
    }   
    
    if ($sched == 1) //Daily
    {
        $period = 86400;
    } 
    elseif ($sched == 2) //Weekly
    {
        $period = 604800;
    }
    else  //Monthly
    {
        $period = 2419200;
    }
    
    $sensor_ip = Util::get_default_admin_ip();
    $sensor_id = Av_sensor::get_id_by_ip($conn, $sensor_ip);
    $name      = _('Default_wizard_scan');
    $type      = 5;
    $enable    = 1;
    
    $targets   = array();

    foreach ($nets as $cidrs)
    {
        $cidrs = explode(' ', $cidrs);
        
        foreach ($cidrs as $cidr)
        {
            $targets[$cidr] = $cidr;
        }
        
    }
    
    $targets   = implode(' ', $targets);
    $params    = $targets .'#-T5 -A -sS -F';
    
    Inventory::insert($conn, $sensor_id, $name, $type, $period, $params, $enable, $targets);
    
    $response['error'] = FALSE;
    $response['data']  = array();
    
    return $response;
    
}


/*
 * <------------------------   END OF THE FUNCTIONS   ------------------------> 
 */





/*
 * <-------------------------   BODY OF THE SCRIPT   -------------------------> 
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
	if ( !Token::verify('tk_welcome_wizard', GET('token')) )
	{		
		$response['error'] = TRUE ;
		$response['msg']   = _('Invalid Action');
	}
	else
	{
        //List of all the possibles functions
        $function_list = array
        (
            'scan_networks'   => array('name' => 'modify_scan_networks',  'params' => array('conn', 'wizard', 'data')),
            'scan_progress'   => array('name' => 'check_scan_progress',   'params' => array('conn', 'wizard')),
            'do_ping'         => array('name' => 'do_ping',               'params' => array('wizard')),
            'cancel_scan'     => array('name' => 'cancel_scan',           'params' => array('wizard')),
            'schedule_scan'   => array('name' => 'schedule_scan',         'params' => array('conn', 'wizard', 'data'))
        );
    
        $_function = $function_list[$action];
        
        //Checking we have a function associated to the action given
        if (is_array($_function) && function_exists($_function['name']))
        {
            $wizard = Welcome_wizard::get_instance(); 

            if (is_object($wizard))
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
                    $response['error']    = TRUE;
                    $response['critical'] = TRUE;
                    $response['msg']      = $e->getMessage();
                }
                
                $db->close($conn);
                
            }
            else
            {
                $response['error']    = TRUE;
                $response['critical'] = TRUE;
                $response['msg']      = _('An unexpected error happened. Try again later');
            }
            
        }
        else
        {
           $response['error']    = TRUE;
           $response['critical'] = TRUE;
           $response['msg']      = _('Wrong Option Chosen'); 
        }
	}
}

//Returning the response to the AJAX call.
echo json_encode($response);

