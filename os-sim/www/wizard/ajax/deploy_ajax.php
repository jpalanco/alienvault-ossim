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
    $response['error']    = TRUE;
    $response['critical'] = TRUE;
    $response['msg']      = _('You do not have permissions to see this section');
    
    echo json_encode($response);
    
    exit -1;
}


/*
 * <------------------------   BEGINNING OF THE FUNCTIONS   ------------------------> 
 */


/*
* This function set the host to deploy. 
*
* @param  $wizard  object  Wizard Object
* @param  $data    array   Data from hosts to deploy.
*
* @return array
*
*/
function modify_deploy_hosts($wizard, $data)
{
    $os       = $data['os'];
    $hosts    = $data['hosts'];
    $username = $data['username'];
    $password = $data['password'];
    $domain   = $data['domain'];
    
    ossim_valid($os,        "windows|linux",                                       'illegal:' . _('Deploy Option'));
    ossim_valid($hosts,	    OSS_HEX,	                                           'illegal:' . _('Host'));
    ossim_valid($username,	OSS_USER_2,	                                           'illegal:' . _('Username'));
    ossim_valid($password,  OSS_PASSWORD,	                                       'illegal:' . _('Password'));
    ossim_valid($domain,	OSS_NOECHARS, OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE,   'illegal:' . _('Domain'));
    
    if (ossim_error()) 
    {
        $response['error'] = TRUE ;
    	$response['msg']   = ossim_get_error();
    	
    	ossim_clean_error();
    	
    	return $response;
    }
    
    $domain = ($os == 'windows') ? $domain : '';
    
    //Encrypting password to save it in the object
    $pass_c = Util::encrypt($password, Util::get_system_uuid());
    
    //First we clean the deploy info stored in the object
    $wizard->clean_step_data();
    
    //Saving the info to achieve the deploy
    $wizard->set_step_data('deploy_os', $os);
    $wizard->set_step_data('deploy_username', $username);
    $wizard->set_step_data('deploy_password', $pass_c);
    $wizard->set_step_data('deploy_domain', $domain);
    $wizard->set_step_data('deploy_hosts', $hosts);
    
    //Setting the deploy step to 1 (Inicialized)
    $wizard->set_step_data('deploy_step', 1);
    
    //Saving wizard status
    $wizard->save_status();
    
    $response['error'] = FALSE;

    return $response;
}


/*
* This function set the host to deploy. 
*
* @param  $conn    object  DB Connection
* @param  $wizard  object  Wizard Object
*
* @return array
*
*/
function deploy_agents($conn, $wizard)
{
    //Aux variable that is returned
    $data    = array();
    
    //If we have already initialized the deploy, we return true to check the status
    $started = $wizard->get_step_data('deploy_initialized');
    
    if ($started === TRUE)
    {
        $response['error'] = FALSE;
        $response['data']  = $data;

        return $response;
    }
    
    //Retrieving the params
    $os       = $wizard->get_step_data('deploy_os');
    $username = $wizard->get_step_data('deploy_username');
    $domain   = $wizard->get_step_data('deploy_domain');
    //Getting the array of hosts
    $hosts    = $wizard->get_step_data('deploy_hosts');
    $hosts    = is_array($hosts) ? $hosts : array();
    //Getting the password and decrypting
    $password = $wizard->get_step_data('deploy_password');
    $password = Util::decrypt($password, Util::get_system_uuid());
    
    $total_ip = 0;
    
    //Performing linux deployment --> Agentless
    if ($os == 'linux')
    {
        $sensor_id = get_sensor_id();
        $deploy    = 0; //Num of successful deployments --> Initially 0
        
        //Arguments for the agentless entries
        $arguments = '/etc /usr/bin /usr/sbin /bin /sbin';
        
        foreach ($hosts as $h)
        {
            $ips      = Asset_host_ips::get_ips_to_string($conn, $h);
            $ips      = explode(',', $ips);
            $hostname = Asset_host::get_name_by_id($conn, $h);
            
            
            foreach ($ips as $ip)
            {
                try
                {
                    //Adding Aggentless
                    Ossec_agentless::save_in_db($conn, $ip, $sensor_id, $hostname, $username, $password, '', FALSE, '');
                    
                    //Adding Aggentless Entries
                    Ossec_agentless::add_monitoring_entry($conn, $ip, $sensor_id, 'ssh_integrity_check_bsd', 3600, 'periodic', $arguments);
                    Ossec_agentless::add_monitoring_entry($conn, $ip, $sensor_id, 'ssh_integrity_check_linux', 3600, 'periodic', $arguments);
                    
                    $deploy++;
                }
                catch(Exception $e)
                {
                    Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
                }
                
                $total_ip++;
            }
           
        }
        
        //Saving the number of the successful deployments
        $wizard->set_step_data('deploy_success', $deploy);
        
    }
    //Performing windows deployment --> OSSEC
    elseif ($os == 'windows')
    {
        $jobs = array();
        
        foreach ($hosts as $h)
        {
            $ips = Asset_host_ips::get_ips_to_string($conn, $h);
            $ips = explode(',', $ips);
            
            
            foreach ($ips as $ip)
            {
                try
                {
                    //Adding job to deploy ossec.
                    $name       = 'Windows-' . str_replace('.', '-', $ip);
                    $job        = Welcome_wizard::launch_ossec_deploy($name, $ip, $username, $domain, $password);
                
                    $jid        = md5($h . $ip);
                    $jobs[$jid] = array(
                        'job_id' => $job['job_id'],
                        'agent'  => $name . '('. $ip .')'
                    );
            
                }
                catch(Exception $e)
                {
                     Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
                }
                
                $total_ip++;
            }
        }
        
        //Saving the jobs IDs in the wizard object
        $wizard->set_step_data('deploy_jobs', $jobs);
    }
    
    $total_ip = ($total_ip > count($hosts)) ? $total_ip : count($hosts);
    
    $data['total_ips'] = $total_ip;
    
    //Setting the total of ips.
    $wizard->set_step_data('deploy_total_ips', $total_ip);
    
    //Setting to true the flag that warns that the deploy has been already initialized.
    $wizard->set_step_data('deploy_initialized', TRUE);
    
    //Saving the wizard status
    $wizard->save_status();
    
    $response['error'] = FALSE;
    $response['data']  = $data;

    return $response;
}


/*
* This function set the host to deploy. 
*
* @param  $conn    object  DB Connection
* @param  $wizard  object  Wizard Object
*
* @return array
*
*/
function check_deploy_status($conn, $wizard)
{
    $data = array();
    $os   = $wizard->get_step_data('deploy_os');
    
    //Linux Deployment Status --> Apply Configuration
    if ($os == 'linux')
    {
        $sensor_id   = get_sensor_id();
        $error_apply = FALSE;
        
        try
    	{
    	    list($agentless_list, $al_total) = Ossec_agentless::get_list($conn, $sensor_id, ' AND status = 1');
    	   
            if ($al_total > 0)
            {
                Ossec_agentless::save_in_config($conn, $sensor_id, $agentless_list);
            }    	   
    	          
        	//Enabling agentless
        	Ossec_control::execute_action($sensor_id, 'enable_al');
        	
        	//Restarting ossec
        	Ossec_control::execute_action($sensor_id, 'restart');

            // Delete "/var/tmp/.reload_<sensor_id>" file in order to hide the "Apply Changes" button
            @unlink('/var/tmp/.reload_'.$sensor_id);
    	}
    	catch(Exception $e)
    	{
        	$error_apply = $e->getMessage();
    	}
    	
    	//If there was an error applying the configuration we show the error
    	if ($error_apply !== FALSE)
    	{
        	$error_apply_msg = _('Error Applying Agentless Configuration');
        	
        	set_scan_error_message($wizard, $error_apply_msg);
        	
        	Av_exception::write_log(Av_exception::USER_ERROR, $error_apply);
    
            $response['error'] = TRUE;
            
            return $response;
    	}
    	
    	//If everything was right, the percent is 100% and the remaining is 0
    	$data['finish']    = TRUE;
    	$data['percent']   = 100;
    	$data['remaining'] = 0;
    	
    	//Setting the deployment status to 3 --> Finished
    	$wizard->set_step_data('deploy_step', 3);
    	
    }
    elseif ($os == 'windows')
    {
        $jobs = $wizard->get_step_data('deploy_jobs');
        
        //If the array of jobs IDs is empty, we are finished
        if (!is_array($jobs) || count($jobs) == 0)
        {
            $data['finish']    = TRUE;
            $data['percent']   = 100;
            $data['remaining'] = 0;
            
            //Setting the deployment status to 3 --> Finished
            $wizard->set_step_data('deploy_step', 3);
        }
        else
        {
            $succes = 0;
            
            //Going through the jobs
            foreach ($jobs as $id => $job)
            {

                try
                {
                    //Getting the status of the job
                    $state = Welcome_wizard::current_jobs($job['job_id']);
                    
                    if ($state['job_status'] == 'task-succeeded')
                    {
                        //If it is success, we count it and we delete it from the jobs array
                        if ($state['job_result'][0] === TRUE)
                        {
                            unset($jobs[$id]);
                            $succes ++;
                        }
                        //If it is not success, we just delete from the array
                        elseif($state['job_result'][0] === FALSE)
                        {
                            unset($jobs[$id]);
                            Av_exception::write_log(Av_exception::USER_ERROR, $job['agent'] . ': ' . $state['job_result'][1]);
                        }
                    }
                    elseif($state['job_status'] == 'task-failed' || $state['job_status'] == 'task-revoked')
                    {
                        unset($jobs[$id]);
                        
                        $_msg = $job['agent'] . ': ' .  _("Couldn't complete windows OSSEC agent deploy: ") . $state['job_status'];
                        Av_exception::write_log(Av_exception::USER_ERROR, $_msg);
                    }
                }
                catch(Exception $e)
                {
                    //In case of critical error we delete from the array to avoid loops
                    unset($jobs[$id]);
                    Av_exception::write_log(Av_exception::USER_ERROR, $job['agent'] . ': ' . $e->getMessage());
                }
                
            }
            
            //IF after checking the status, the array is empty, we are finished
            if (!is_array($jobs) || count($jobs) == 0)
            {
                $data['finish']    = TRUE;
                $data['percent']   = 100;
                $data['remaining'] = 0;
                
                //Setting the deployment status to 3 --> Finished
                $wizard->set_step_data('deploy_step', 3);
            }
            //Otherwise we get the percent and the remaining
            else
            {
                //Total number of host that were selected to be deployed
                $total   = $wizard->get_step_data('deploy_total_ips');
                $total   = ($total < 1) ? 1 : $total;
                
                //Number of host left to be deployed --> Pending jobs
                $current = count($jobs);

                
                //Percentage of the remaining hosts
                $pending = $total - $current;
                $percent = round(100*($pending/$total));
                
                $data['finish']    = FALSE;
                $data['percent']   = $percent;
                $data['remaining'] = $current;
            }
            
            //Updating the number of host successfully deployed
            $deployed  = $wizard->get_step_data('deploy_success');
            $deployed += $succes;
            
            $wizard->set_step_data('deploy_success', $deployed);
            
            //Updating the array of jobs left
            $wizard->set_step_data('deploy_jobs', $jobs);
            
        }
        
    }
	
	//Saving wizard status
    $wizard->save_status();
	
    $response['error'] = FALSE;
    $response['data']  = $data;

    return $response;
}


/*
* This function cancels the deploy. 
*
* @param  $wizard  object  Wizard Object
*
* @return array
*
*/
function cancel_deploy($wizard)
{
    //Deleting the deploy info from the wizard object
    $wizard->clean_step_data();
    
    //Saving the status
    $wizard->save_status();
    
    $response['error'] = FALSE ;

    return $response;
}



/******    Extra Functions    ******/


/*
* This function set the deploy status to error and save the error message. 
*
* @param  $wizard  object  Wizard Object
* @param  $msg     msg     Error message.
*
* @return void
*
*/
function set_scan_error_message($wizard, $msg)
{
    $wizard->set_step_data('deploy_step', -1); 
    
    $wizard->set_step_data('deploy_error_msg', $msg);
    
    $wizard->save_status();
}


/*
* This function get the AlienVault Center Sensor ID. 
*
* @return string
*
*/
function get_sensor_id()
{
    return Ossec_utilities::get_default_sensor_id();
}


/*
 * <------------------------   END OF THE FUNCTIONS   ------------------------> 
 */
 
 

/*
 * <-------------------------   BODY OF THE SCRIPT   -------------------------> 
 */

$action = POST("action");   //Action to perform.
$data   = POST("data");     //Data related to the action.


ossim_valid($action,    OSS_INPUT,     'illegal:' . _("Action"));

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
            'hosts_deploy'    => array('name' => 'modify_deploy_hosts',  'params' => array('wizard', 'data')),
            'cancel_deploy'   => array('name' => 'cancel_deploy',        'params' => array('wizard')),
            'deploy_agents'   => array('name' => 'deploy_agents',        'params' => array('conn', 'wizard')),
            'check_deploy'    => array('name' => 'check_deploy_status',  'params' => array('conn', 'wizard'))
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
                $response['error']    = TRUE ;
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
