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

//First we check we have an active session
Session::useractive();

//Then we check the permissions
if (!Session::logcheck_bool("analysis-menu", "ControlPanelAlarms"))
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



/*
* This function close a single alarm. 
*
* @param  $conn  object  DB Connection
* @param  $data  array   Backlog ID of the alarm to be closed
*
*/
function close_alarm($conn, $data)
{
	$id = $data['id'];
	
	//Validating ID before closing the alarm
	ossim_valid($id,   OSS_HEX,    'illegal:' . _("Backlog ID"));
	
	if (ossim_error())
	{
		$info_error = "Error: ".ossim_get_error();
		ossim_clean_error();
		$return['error'] = TRUE ;
		$return['msg']   = $info_error;
		return $return;
	}
	
	//Closing the alarm 
	Alarm::close($conn, $id);

	$return['error'] = FALSE;
	$return['msg']   = _('Alarm closed successfully');

	return $return;
	
}


/*
* This function open a single alarm. 
*
* @param  $conn  object  DB Connection
* @param  $data  array   Backlog ID of the alarm to be closed
*
*/
function open_alarm($conn, $data)
{
	$id = $data['id'];
	
	//Validating ID before closing the alarm
	ossim_valid($id,   OSS_HEX,    'illegal:' . _("Backlog ID"));
	
	if (ossim_error())
	{
		$info_error = "Error: ".ossim_get_error();
		ossim_clean_error();
		$return['error'] = TRUE ;
		$return['msg']   = $info_error;
		return $return;
	}
	
	//Opening the alarm
	Alarm::open($conn, $id);

	$return['error'] = FALSE;
	$return['msg']   = _('Alarm opened successfully');

	return $return;
	
}


/*
* This function delete all alarms. 
*
* @param  $conn  object  DB Connection
*
*/
function delete_all_alarms($conn)
{
	//Getting the user. We delete only the alarm of the current user
	$user = Session::get_session_user();
	//Getting the file with all the sql queries for deleting the alarms
	$file = Alarm::delete_all_backlog($conn);
	
	//Executing the sql for deleting the queries in background
	@system("php /usr/share/ossim/scripts/alarms/bg_alarms.php $user $file > /dev/null 2>&1 &");


	$return['error'] = FALSE;
	$return['msg']   = '';

	return $return;

}


/*
* This function close all alarms. 
*
* @param  $conn  object  DB Connection
*
*/
function close_all_alarms()
{
    //Getting the user. We delete only the alarm of the current user
	$user = Session::get_session_user();
	//Getting the file with all the sql queries for closing the alarms
	$file = Alarm::close_all();
	
	//Executing the sql for closing the queries in background
	@system("php /usr/share/ossim/scripts/alarms/bg_alarms.php $user $file > /dev/null 2>&1 &");


	$return['error'] = FALSE;
	$return['msg']   = '';

	return $return;
	
}


/*
* This function set in session the alarms checked in order to remeber the selection. 
*
* @param  $conn  object  DB Connection
* @param  $data  array   Backlog ID of the alarms selected
*
*/
function remember_alarms($data)
{
    $alarms = $data['alarms'];
    
    //Cleaning the previous selected alarms
	unset($_SESSION['_SELECTED_ALARMS']);
	
	//Going through the alarms selected
	if (is_array($alarms))
	{
    	foreach($alarms as $alarm) 
    	{
    	    //Only the alarms that matches with an UUID will be stored. Otherwise we ignore them
            if (preg_match("/^[0-9a-fA-F]+$/", $alarm)) 
            {
            	$_SESSION['_SELECTED_ALARMS'][$alarm] = 1;
            }
    	}
	}
	
	$return['error'] = FALSE;
	$return['msg']   = '';

	return $return;
	
}


/*
* This function checks if there is an alarm operation running in background. 
*
* @param  $conn  object  DB Connection
*
*/
function check_bg_tasks($conn)
{
    
	$user   = Session::get_session_user();
	$config = new User_config($conn);
	
	//Getting the pid of the operation running in background
	$pid    = $config->get($user, 'background_task', 'simple', "alarm");
	$bg     = FALSE;

	//If the pid is not empty, then we check if the process is still running
	if($pid != '')
	{
    	//Launching a ps with the pid stored
		@exec("ps $pid", $process_state);	    
	    $bg = (count($process_state) >= 2); //If the count is >= 2 then there is a process running

	    //If the process is not running any longer, then we delete the pid from db
	    if(!$bg)
	    {
	    	$config->set($user, 'background_task', '', 'simple', 'alarm');
	    }
	}

	$return['error'] = FALSE ;
	$return['msg']   = '';
	$return['bg']    = $bg;

	Util::memcacheFlush(FALSE);

	return $return;
	
}


/*
* This function delete a label from a single alarm. 
*
* @param  $conn  object  DB Connection
* @param  $data  array   Backlog ID of the alarm and Label ID
*
*/
function delete_alarm_label($conn, $data)
{
    $alarm  = $data['alarm']; //Alarm ID
    $label  = intval($data['label']); //Label ID
    
    //Validating parameters
    ossim_valid($alarm,   OSS_HEX,      'illegal:' . _("Backlog ID"));
    ossim_valid($label,   OSS_DIGIT,    'illegal:' . _("Label ID"));
	
	if (ossim_error())
	{
		$info_error = "Error: ".ossim_get_error();
		
		ossim_clean_error();
		
		$return['error'] = TRUE ;
		$return['msg']   = $info_error;
		
		return $return;
	}
    
    //As we are going to delete the label, we need the label id with negative sign. e.g label id 2 has to be -2
    $label  = -1 * abs($label);

    //Deleting the label
    Tags::del_alarm_tag($conn, $alarm, $label);
    
	$return['error'] = FALSE ;
	$return['msg']   = '';

	return $return;
}


/*
* This function applies a label to a set of alarms. 
*
* @param  $conn  object  DB Connection
* @param  $data  array   Backlog IDs of the alarms and Label ID
*
*/
function add_alarm_label($conn, $data)
{
    $alarms = $data['alarms']; //Set of alarms
    $label  = $data['label']; //Label ID
    
    //Validating parameters
    ossim_valid($alarms,  OSS_HEX,      'illegal:' . _("Backlog ID"));
    ossim_valid($label,   OSS_DIGIT,    'illegal:' . _("Label ID"));
	
	if (ossim_error())
	{
		$info_error = "Error: ".ossim_get_error();
		
		ossim_clean_error();
		
		$return['error'] = TRUE ;
		$return['msg']   = $info_error;
		
		return $return;
	}
    
    $alarms = (is_array($alarms)) ? $alarms : array();
    
    //Going through the set of alarms for applying the label
    foreach ($alarms as $alarm)
    {
        Tags::set_alarm_tag($conn, $alarm, $label); //Applying the label
    }
    
    //Returning the html of the label we are applying
    $tags_html = Tags::get_list_html($conn," WHERE id=$label", true);
    
    
	$return['error'] = FALSE;
	$return['data']  = $tags_html[$label];

	return $return;
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


$action = POST("action");
$data   = POST("data");

//Validating the action
ossim_valid($action,	OSS_DIGIT,	'illegal:' . _("Action"));

if (ossim_error()) 
{
    $info_error = "Error: ".ossim_get_error();
    
	ossim_clean_error();
	
	$response['error'] = TRUE ;
	$response['msg']   = $info_error;
	
	echo json_encode($response);
	die();
}

//Verifying the token
if (!Token::verify('tk_alarm_operations', GET('token')))
{		
	$response['error'] = TRUE ;
	$response['msg']   = _('Invalid Action');
	
	echo json_encode($response);
	die();
}

//Verifying it is an ajax request
if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
{
    //List of all the possibles functions
    $function_list = array
    (
        1 => array('name' => 'close_alarm',        'params' => array('conn', 'data')),
        2 => array('name' => 'open_alarm',         'params' => array('conn', 'data')),
        3 => array('name' => 'remember_alarms',    'params' => array('data')),
        4 => array('name' => 'delete_all_alarms',  'params' => array('conn')),
        5 => array('name' => 'close_all_alarms',   'params' => array()),
        7 => array('name' => 'check_bg_tasks',     'params' => array('conn')),
        8 => array('name' => 'add_alarm_label',    'params' => array('conn', 'data')),
        9 => array('name' => 'delete_alarm_label', 'params' => array('conn', 'data'))
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
        
        //Calling to the function 
        $return = call_user_func_array($_function['name'], $params);
        
        if ($return === FALSE)
        {
            $response['error'] = TRUE ;
            $response['msg']   = _('Invalid Action');
        }
        else
        {
            $response = $return;
        }

        $db->close($conn);
        
    }
    else
    {
       $response['error'] = TRUE ;
       $response['msg']   = _('Wrong Option Chosen'); 
    }
}
else
{
    $response['error'] = TRUE ;
    $response['msg']   = _('Invalid Action');
}

echo json_encode($response);

?>