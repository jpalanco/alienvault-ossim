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

//Check active session
Session::useractive();

require_once 'sidebar_functions.php';

session_write_close();


function get_open_tickets($conn) 
{
	Incident::search($conn, array('status' => 'not_closed'), 'life_time', 'ASC', 1, 10);
    $tickets = Incident::search_count($conn);
	
	$return['error']  = FALSE ;
	$return['output'] = format_notif_number(intval($tickets));

	return  $return;
}


function get_unresolved_alarms($conn) 
{
	$alarms      = intval(Alarm::get_count($conn, '', '', 1, TRUE));
	$alarms_prev = intval($_SESSION['_unresolved_alarms']);

	if ($alarms != $alarms_prev && $alarms_prev > 0) 
	{
		$new_alarms = $alarms - $alarms_prev;
	} 
	else 
	{
		$new_alarms = 0;
	}
    
    session_start();
	$_SESSION['_unresolved_alarms'] = $alarms;	
	session_write_close();
	
	$data['alarms']          = format_notif_number($alarms);
	$data['new_alarms']      = $new_alarms;
	$data['new_alarms_desc'] = '';
	

	if ($new_alarms > 0) 
	{
	    $criteria = array(
            'src_ip'        => '',
            'dst_ip'        => '',
            'hide_closed'   => 1,
            'order'         => 'ORDER BY a.timestamp DESC',
            'inf'           => 0,
            'sup'           => $new_alarms,
            'date_from'     => '',
            'date_to'       => '',
            'query'         => '',
            'directive_id'  => '',
            'intent'        => 0,
            'sensor'        => '',
            'tag'           => '',
            'num_events'    => '',
            'num_events_op' => 0,
            'plugin_id'     => '',
            'plugin_sid'    => '',
            'ctx'           => '',
            'host'          => '',
            'net'           => '',
            'host_group'    => ''            
        );
        
		list($alarm_list, $count) = Alarm::get_list($conn, $criteria);		
		
		$alarm_string = '';

		foreach ($alarm_list as $alarm) 
		{
			$desc_alarm = Util::translate_alarm($conn, $alarm->get_sid_name(), $alarm);

			$desc_alarm = html_entity_decode(str_replace("'","\'", $desc_alarm));
			$desc_alarm = str_replace('"', "&quot;", $desc_alarm);
			$desc_alarm = str_replace('&mdash;', "-", $desc_alarm);
			$desc_alarm = Util::js_entities($desc_alarm);

			
			if ($alarm_string != '') 
			{ 
				$alarm_string .= '|'; 
			}

			$alarm_string .= $desc_alarm;
		}

		$data['new_alarms_desc'] = $alarm_string;
	}
	
	$return['error']  = FALSE;
	$return['output'] = $data;

	return  $return;
}


function get_sensor_status($conn) 
{	
	list($sensors_total, $sensors_up, $sensor_down) = calc_sensors_status($conn);
		
	if ($sensors_total === 0)
	{
		$sensors_color = 'off';
	}
	elseif ($sensors_up == $sensors_total)
	{
		$sensors_color = 'green';
	}
	elseif ($sensor_down  > 1 || ($sensor_down == 1 && $sensors_total == 1))
	{
		$sensors_color = 'red';
	}
	elseif ($sensor_down === 1 && $sensors_total > 1)
	{
		$sensors_color = 'yellow';
	}
	else
	{
		$sensors_color = 'off';
	}

	$data['total']  = format_notif_number($sensors_total);
	$data['active'] = format_notif_number($sensors_up);
	$data['color']  = $sensors_color;

	$return['error']  = FALSE ;
	$return['output'] = $data;

	return  $return;
}


function get_monitored_devices($conn) 
{
	$devices          = calc_devices_total($conn);
	
	$return['error']  = FALSE ;
	$return['output'] = format_notif_number($devices);

	return  $return;
}


function get_system_eps($conn) 
{
	$sys_eps          = calc_system_eps($conn);
	
	$return['error']  = FALSE ;
	$return['output'] = format_notif_number($sys_eps);

	return  $return;
}


function get_events_trend($conn)
{
	//Events Sparkline
	list($labels, $events) = calc_events_trend($conn);

	$data['labels'] = $labels;
	$data['events'] = implode(',', $events);

	$return['error']  = FALSE;
	$return['output'] = $data;

	return  $return;
}


function get_trial_status()
{
    $trial_days = calc_days_to_expire();

    $response = array();

    $response['error'] = TRUE;

    if ($trial_days !== FALSE)
    {
        $trial_status['msg']   = ($trial_days == 0) ? _('Trial Version expired') : $trial_days.' '._('Days Left of Free Trial');
        $trial_status['class'] = 'nl_trial';

        $response['output'] = $trial_status;
        $response['error']  = FALSE;
    }

    return $response;
}


$action = POST('action');

ossim_valid($action, OSS_TEXT, 'illegal:' . _('Action'));

if (ossim_error()) 
{
    $response['error']  = TRUE ;
	$response['output'] = ossim_error();

	echo json_encode($response);
		
	exit();
}

$db     = new ossim_db();
$conn   = $db->connect();

if($action != '' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
{	
	$check_perms = array(
        'open_tickets'      => array('func' => 'Session::menu_perms',  'parameters' => array('analysis-menu', 'IncidentsOpen')),
        'unresolved_alarms' => array('func' => 'Session::menu_perms',  'parameters' => array('analysis-menu', 'ControlPanelAlarms')),
        'sensor_status'     => array('func' => 'Session::menu_perms',  'parameters' => array('configuration-menu', 'PolicySensors')),
        'system_eps'        => array('func' => 'Session::menu_perms',  'parameters' => array('analysis-menu', 'EventsForensics')),
        'monitored_devices' => array('func' => 'Session::am_i_admin',  'parameters' => array()),
        'events_trend'      => array('func' => 'Session::menu_perms',  'parameters' => array('analysis-menu', 'EventsForensics')),
    );
    
    
    if (array_key_exists($action, $check_perms) && !call_user_func_array($check_perms[$action]['func'], $check_perms[$action]['parameters']))
    {        
        $response['error']  = TRUE ;
        $response['output'] = _("You don't have permissions to see this section");
        
        echo json_encode($response);
        		
		$db->close();
		exit();
    }


    switch($action)
    {
        case 'trial_status':
            $response = get_trial_status();
        break;

        case 'open_tickets':
            $response = get_open_tickets($conn);
        break;

        case 'unresolved_alarms':
            $response = get_unresolved_alarms($conn);
        break;

        case 'sensor_status':
            $response = get_sensor_status($conn);
        break;

        case 'system_eps':
            $response = get_system_eps($conn);
        break;

        case 'monitored_devices':
            $response = get_monitored_devices($conn);
        break;

        case 'events_trend':
            $response = get_events_trend($conn);
        break;

        default:
            $response['error']  = TRUE ;
            $response['output'] = _('Wrong Option Chosen');
    }

    echo json_encode($response);
}

$db->close();
?>
