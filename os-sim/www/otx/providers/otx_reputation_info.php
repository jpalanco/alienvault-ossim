<?php
/**
 * dt_software.php
 *
 * File dt_software.php is used to:
 *  - Build JSON data that will be returned in response to the Ajax request made by DataTables (Software list)
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
 * @package    ossim-framework\Assets
 * @autor      AlienVault INC
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt
 * @copyright  2003-2006 ossim.net
 * @copyright  2007-2013 AlienVault
 * @link       https://www.alienvault.com/
 */

require_once 'av_init.php';

Session::logcheck_ajax("dashboard-menu", "IPReputation");

// Close session write for real background loading
session_write_close();


function get_pulse_detail_from_id($conn)
{
    $type  = POST('type');
    $pulse = POST('pulse');
    $id    = POST('id');
    
    ossim_valid($type,      'alarm|event|alarm_event',  'illegal:' . _('Type'));
    ossim_valid($pulse,     OSS_HEX,        'illegal:' . _('Pulse'));
    ossim_valid($id,        OSS_HEX,        'illegal:' . _('ID'));
    
    if (ossim_error())
    {
        Av_exception::throw_error(Av_exception::USER_ERROR, ossim_get_error_clean());
    }
    
    
    
    if ($type == 'alarm')
    {
        $pulse = Alarm::get_pulse_data_from_alarm($conn, $id, $pulse, TRUE);
    }
    elseif ($type == 'event')
    {
        $pulse = Siem::get_pulse_data_from_event($conn, $id, $pulse, FALSE, TRUE);
    }
    elseif ($type == 'alarm_event')
    {
        $pulse = Siem::get_pulse_data_from_event($conn, $id, $pulse, TRUE, TRUE);
    }
    
    
    return array(
        'name'  => $pulse['name'],
        'descr' => $pulse['descr'],
        'iocs'  => array_values($pulse['iocs'])
    );    
}

$action   = POST('action');
$response = array();

try
{
    $db   = new ossim_db(TRUE);
    $conn = $db->connect();

    switch ($action)
    {
        case 'pulse_info':
            $response = get_pulse_detail_from_id($conn);
            break;
        
        default:
            Av_exception::throw_error(Av_exception::USER_ERROR, _('Invalid Action.'));
    }
    
    $db->close();
    
}
catch (Exception $e)
{
    $db->close();
    Util::response_bad_request($e->getMessage());
}


echo json_encode($response);