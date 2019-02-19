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
* This function change the description of an alarm group. 
*
* @param  $conn  object  DB Connection
* @param  $data  array   Group ID and new description
*
*/
function save_descr($conn, $data)
{
    $group_id = $data['group_id']; 
    $descr    = $data['descr'];
    
    //Validating parameters
    ossim_valid($group_id,  OSS_INPUT,                    'illegal:' . _("Alarm Group ID"));
    ossim_valid($descr,     OSS_INPUT, OSS_NULLABLE,      'illegal:' . _("Description"));
	
	if (ossim_error())
	{
		$info_error = "Error: ".ossim_get_error();
		
		ossim_clean_error();
		
		$return['error'] = TRUE ;
		$return['msg']   = $info_error;
		
		return $return;
	}
	
	Alarm_groups::change_descr($conn, $descr, $group_id);
    
	$return['error'] = FALSE;
	$return['data']  = '';

	return $return;
}


/*
* This function change the description of an alarm group. 
*
* @param  $conn  object  DB Connection
* @param  $data  array   Group ID and new description
*
*/
function take_group($conn, $data)
{
    $group_id = $data['group_id']; 
    
    //Validating parameters
    ossim_valid($group_id,      OSS_INPUT,      'illegal:' . _("Alarm Group ID"));
    	
	if (ossim_error())
	{
		$info_error = "Error: ".ossim_get_error();
		
		ossim_clean_error();
		
		$return['error'] = TRUE ;
		$return['msg']   = $info_error;
		
		return $return;
	}
	
	$user = Session::get_session_user();
	
	Alarm_groups::take_group($conn, $group_id , $user);
	
	Util::memcacheFlush(FALSE);
	
    
	$return['error'] = FALSE;
	$return['data']  = '';

	return $return;
}


/*
* This function change the description of an alarm group. 
*
* @param  $conn  object  DB Connection
* @param  $data  array   Group ID and new description
*
*/
function release_group($conn, $data)
{
    $group_id = $data['group_id']; 
    
    //Validating parameters
    ossim_valid($group_id,      OSS_INPUT,      'illegal:' . _("Alarm Group ID"));
    	
	if (ossim_error())
	{
		$info_error = "Error: ".ossim_get_error();
		
		ossim_clean_error();
		
		$return['error'] = TRUE ;
		$return['msg']   = $info_error;
		
		return $return;
	}
	
	$user = Session::get_session_user();
	
	Alarm_groups::release_group($conn, $group_id , $user);
	
	Util::memcacheFlush(FALSE);
	
    
	$return['error'] = FALSE;
	$return['data']  = '';

	return $return;
}


/*
* This function change the description of an alarm group. 
*
* @param  $conn  object  DB Connection
* @param  $data  array   Group ID and new description
*
*/
function close_group($data)
{
    $groups = $data['groups']; 
    
    //Validating parameters
    ossim_valid($groups,    OSS_INPUT,      'illegal:' . _("Alarm Group IDs"));

	if (ossim_error())
	{
		$info_error = "Error: ".ossim_get_error();
		
		ossim_clean_error();
		
		$return['error'] = TRUE ;
		$return['msg']   = $info_error;
		
		return $return;
	}
	
	$user   = Session::get_session_user();
	$groups = "'" . implode("','", $groups) . "'";
    $file   = Alarm_groups::change_status($groups, "closed");
    
    $cmd    = 'php /usr/share/ossim/scripts/alarms/bg_alarms.php ? ? > /dev/null 2>&1 &';
    $params = array($user, $file);
    
    Util::execute_command($cmd, $params);
    
	$return['error'] = FALSE;
	$return['data']  = '';

	return $return;
}


/*
* This function change the description of an alarm group. 
*
* @param  $conn  object  DB Connection
* @param  $data  array   Group ID and new description
*
*/
function open_group($data)
{
    $groups = $data['groups']; 
    
    //Validating parameters
    ossim_valid($groups,    OSS_INPUT,      'illegal:' . _("Alarm Group IDs"));

	if (ossim_error())
	{
		$info_error = "Error: ".ossim_get_error();
		
		ossim_clean_error();
		
		$return['error'] = TRUE ;
		$return['msg']   = $info_error;
		
		return $return;
	}
	
	$user   = Session::get_session_user();
	$groups = "'" . implode("','", $groups) . "'";
	
    $file   = Alarm_groups::change_status($groups, "open");
    
    $cmd    = 'php /usr/share/ossim/scripts/alarms/bg_alarms.php ? ? > /dev/null 2>&1 &';
    $params = array($user, $file);
    
    Util::execute_command($cmd, $params);
    
	$return['error'] = FALSE;
	$return['data']  = '';

	return $return;
}


/*
* This function change the description of an alarm group. 
*
* @param  $conn  object  DB Connection
* @param  $data  array   Group ID and new description
*
*/
function delete_group($conn, $data)
{
    $groups = $data['groups']; 
    
    //Validating parameters
    ossim_valid($groups,    OSS_INPUT,      'illegal:' . _("Alarm Group IDs"));
	
	if (ossim_error())
	{
		$info_error = "Error: ".ossim_get_error();
		
		ossim_clean_error();
		
		$return['error'] = TRUE ;
		$return['msg']   = $info_error;
		
		return $return;
	}

	$params = array();
	$params['groups'] = "'" . implode("','", $groups) . "'";

	$user   = Session::get_session_user();
    $file   = Alarm_groups::delete_alarms_from_groups($conn, $params);
	
    $cmd    = 'php /usr/share/ossim/scripts/alarms/bg_alarms.php ? ? > /dev/null 2>&1 &';
    $params = array($user, $file);
    
    Util::execute_command($cmd, $params);
    
	$return['error'] = FALSE;
	$return['data']  = '';

	return $return;
}


/*
* This function change the description of an alarm group. 
*
* @param  $conn  object  DB Connection
* @param  $data  array   Group ID and new description
*
*/
function delete_all($conn)
{
	$user   = Session::get_session_user();
	
	$params = array();
	$params['extra'] = "DELETE FROM alarm_groups WHERE owner='$user'";
	
    $file   = Alarm::delete_all_backlog($conn, $params);
    
    $cmd    = 'php /usr/share/ossim/scripts/alarms/bg_alarms.php ? ? > /dev/null 2>&1 &';
    $params = array($user, $file);
    
    Util::execute_command($cmd, $params);
    
	$return['error'] = FALSE;
	$return['data']  = '';

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
ossim_valid($action,	OSS_ALPHA, OSS_SCORE,	'illegal:' . _("Action"));

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
if (!Token::verify('tk_grouped_alarm_actions', GET('token')))
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
        'save_descr'    => array('name' => 'save_descr',    'params' => array('conn', 'data')),
        'take_group'    => array('name' => 'take_group',    'params' => array('conn', 'data')),
        'release_group' => array('name' => 'release_group', 'params' => array('conn', 'data')),
        'delete_group'  => array('name' => 'delete_group',  'params' => array('conn', 'data')),
        'open_group'    => array('name' => 'open_group',    'params' => array('data')),
        'close_group'   => array('name' => 'close_group',   'params' => array('data')),
        'delete_all'    => array('name' => 'delete_all',    'params' => array('conn')),
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
