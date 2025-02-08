<?php
/**
 *
 * License:
 *
 * Copyright (c) 2003-2006 ossim.net
 * Copyright (c) 2007-2015 AlienVault
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
function close_alarm($conn, $data) {
    if (!Session::menu_perms("analysis-menu", "ControlPanelAlarmsClose")) {
        $return['error'] = TRUE;
        $return['msg']   = _("Error: You don't have required permissions to close alarms");

        return $return;
    }
    else {
        return  odc_engine($conn, $data, 'close');
    }
}


/*
* This function open a single alarm.
*
* @param  $conn  object  DB Connection
* @param  $data  array   Backlog ID of the alarm to be closed
*
*/
function open_alarm($conn, $data) {
    return odc_engine($conn, $data, 'open');
}


/*
* This function open a single alarm.
*
* @param  $conn  object  DB Connection
* @param  $data  array   Backlog ID of the alarm to be closed
*
*/
function delete_alarm($conn, $data) {
    if (!Session::menu_perms("analysis-menu", "ControlPanelAlarmsDelete"))
    {
        $return['error'] = TRUE;
        $return['msg']   = _("Error: You don't have required permissions to delete alarms");

        return $return;
    }
    else{
        return odc_engine($conn, $data, 'delete');
    }
}


/*
* This function delete all alarms.
*
* @param  $conn  object  DB Connection
*
*/
function delete_all_alarms($conn)
{
    if (!Session::menu_perms("analysis-menu", "ControlPanelAlarmsDelete")) {
        $return['error'] = TRUE;
        $return['msg']   = _("Error: You don't have required permissions to delete alarms");
        $_SESSION["_delete_msg"] = $return['msg'];
    }
    else {
        //Getting the user. We delete only the alarm of the current user
        $user = Session::get_session_user();
        //Getting the file with all the sql queries for deleting the alarms
        $file = Alarm::delete_all_backlog($conn);

        //Executing the sql for deleting the queries in background
        $cmd    = 'php /usr/share/ossim/scripts/alarms/bg_alarms.php ? ? > /dev/null 2>&1 &';
        $params = array($user, $file);

        Util::execute_command($cmd, $params);

        $return['error'] = FALSE;
        $return['msg']   = '';
    }

    return $return;
}


/*
* This function close all alarms by page filter.
*
* @param  $conn  object  DB Connection
*
*/
function close_all_alarms($conn) {
    if (!Session::menu_perms("analysis-menu", "ControlPanelAlarmsClose")) {
        $return['error'] = TRUE;
        $return['msg']   = _("Error: You don't have required permissions to close alarms");
    } else {
        $alarm_ids = implode(',',get_alarm_ids($conn));
        Alarm::close_all($conn,$alarm_ids);

        $return['error'] = FALSE;
        $return['msg']   = 'successfully';
    }

    return $return;
}
/*
* This function open all  alarms by page filter.
*
* @param  $conn  object  DB Connection
*
*/
function open_all_alarms($conn) {
    $alarm_ids = implode(',',get_alarm_ids($conn));
    Alarm::open_all($conn,$alarm_ids);

    $return['error'] = FALSE;
    $return['msg']   = 'successfully';

    return $return;
}

/*
* This function delete all alarms by page filter.
*
* @param  $conn  object  DB Connection
*
*/
function delete_all_alarms_by_filter($conn){
    if (!Session::menu_perms("analysis-menu", "ControlPanelAlarmsDelete")) {
        $return['error'] = TRUE;
        $return['msg']   = _("Error: You don't have required permissions to delete alarms");
        $_SESSION["_delete_msg"] = $return['msg'];
    }
    else {
        $return['error'] = FALSE;
        $return['msg']   = 'successfully';

        $alarm_ids  = get_alarm_ids($conn,false);
        $failed_alarms = 0;
        foreach ($alarm_ids  as $id) {

            $result = Alarm::delete_backlog($conn, $id);

            if (!$result) {
                $failed_alarms ++;
            }
        }

        if ($failed_alarms > 0) {
            if ($failed_alarms == 1){
                $return['msg']  = _("You do not have enough permissions to delete 1 alarm or alarm is still being correlated");
            } else{
                $return['msg']  = _("You do not have enough permissions to delete $failed_alarms alarms or alarms are still being correlated");
            }

            $return['error'] = TRUE;
            $_SESSION["_delete_msg"] = $return['msg'];
        }
    }


    return $return;
}


/*
* This function set in session the alarms checked in order to remember the selection.
*
* @param  $conn  object  DB Connection
* @param  $data  array   Backlog ID of the alarms selected
*
*/
function remember_alarms($data) {
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
    return $return;
}


/*
* This function checks if there is an alarm operation running in background.
*
* @param  $conn  object  DB Connection
*
*/
function check_bg_tasks($conn) {
    $user   = Session::get_session_user();
    $config = new User_config($conn);

    //Getting the pid of the operation running in background
    $pid = $config->get($user, 'background_task', 'simple', "alarm");
    $bg = FALSE;

    //If the pid is not empty, then we check if the process is still running
    if($pid != '')
    {
        //Launching a ps with the pid stored
        $process_state = Util::execute_command('ps ?', array(intval($pid)), 'array');

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

function validatingID($id) {
    ossim_valid($id, OSS_HEX, 'illegal:' . _("Backlog ID"));

    $return['error'] = FALSE;
    $return['msg'] = '';

    if (ossim_error()) {
        $info_error = "Error: " . ossim_get_error();
        ossim_clean_error();
        $return['error'] = TRUE;
        $return['msg'] = $info_error;
        return $return;
    }

    return $return;
}

function odc_engine ($conn, $data, $action) {
    if(!is_array($data['id'])) {
        $data['id'] = array($data['id']);
    }

    foreach ($data['id'] as $encryptedId) {
        $id = (preg_match("/check_([0-9a-fA-F]+)/", $encryptedId, $foundId)) ? $foundId[1] : $encryptedId;

        //Validating ID before closing the alarm
        $return = validatingID($id);
        if($return['error']) {
            return $return;
        }

        if($action == 'close') {
            //Closing the alarm
            Alarm::close($conn, $id);
        }
        if($action == 'open') {
            //Opening the alarm
            Alarm::open($conn, $id);
        }
        if($action == 'delete') {
            //Deleting the alarm
            $result = Alarm::delete_backlog($conn, $id);
            if (!$result) {
                $_SESSION["_delete_msg"] = _("You do not have enough permissions to delete this alarm or alarm is still being correlated");
                $return['error'] = TRUE;
                return $return;
            }
        }
    }

    $return['msg']  = _('Alarm '. $action . ' successfully');
    return $return;
}


function get_alarm_ids($conn, $is_wrap = TRUE) {
    $wrap = $is_wrap ? "'" : '';
    $alarm_ids = array();
    parse_str($_SESSION["_alarm_criteria"], $criteria);

    if (!empty($criteria['ds_id']))
    {
        $ds = explode("-", $criteria['ds_id']);
        $criteria['plugin_id'] = $ds[0];
        $criteria['plugin_sid'] = $ds[1];

        unset($criteria['ds_id']);
    }

    unset($criteria['order']);
    list($alarm_list, $count) = Alarm::get_list($conn, $criteria);


    if ($count > 0) {
        foreach ($alarm_list as $alarm) {
            array_push($alarm_ids, $wrap . $alarm->get_backlog_id() . $wrap);
        }
    }

    return $alarm_ids;
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
ossim_valid($action,    OSS_DIGIT,  'illegal:' . _("Action"));

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
        1 => array('name' => 'close_alarm',                  'params' => array('conn', 'data')),
        2 => array('name' => 'open_alarm',                   'params' => array('conn', 'data')),
        3 => array('name' => 'remember_alarms',              'params' => array('data')),
        4 => array('name' => 'delete_all_alarms_by_filter',  'params' => array('conn')),
        5 => array('name' => 'close_all_alarms',             'params' => array('conn')),
        6 => array('name' => 'delete_alarm',                 'params' => array('conn', 'data')),
        7 => array('name' => 'check_bg_tasks',               'params' => array('conn')),
        8 => array('name' => 'open_all_alarms',              'params' => array('conn')),
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
            $response['error'] = TRUE;
            $response['msg']   = _('Invalid Action');
        }
        else
        {
            $response = $return;
        }

        $db->close();
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
