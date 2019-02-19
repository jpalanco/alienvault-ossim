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
require_once dirname(__FILE__) . '/../../conf/config.inc';

Session::logcheck('environment-menu', 'EventsHidsConfig');

$db         = new ossim_db();
$conn       = $db->connect();

$sensor_id  = POST('sensor');
$token      = POST('token');

ossim_valid($sensor_id,  OSS_HEX,  'illegal:' . _('Sensor'));


if (ossim_error())
{
   $txt_error = ossim_get_error_clean();
}
else
{
    if (!Token::verify('tk_al_apply_conf', $token))
    {
        $txt_error = Token::create_error_message();
    }
    else
    {
        if (!Ossec_utilities::is_sensor_allowed($conn, $sensor_id))
        {
            $txt_error = _('Error! Sensor not allowed');
        }
    }
}


if (empty($txt_error))
{
    try
    {
        list($agentless_list, $al_total) = Ossec_agentless::get_list($conn, $sensor_id, ' AND status = 1'); 

        //If we have agentless to modify
        if ($al_total > 0)
        {
            Ossec_agentless::save_in_config($conn, $sensor_id, $agentless_list);
        }

        //Enabling agentless
        Ossec_control::execute_action($sensor_id, 'enable_al');

        //Restarting ossec
        Ossec_control::execute_action($sensor_id, 'restart');

        $data['status'] = 'success';
        $data['data']   = _('Configuration applied successfully');

        // Delete "/var/tmp/.reload_<sensor_id>" file in order to hide the "Apply Changes" button
        @unlink('/var/tmp/.reload_'.$sensor_id);
    }
    catch(Exception $e)
    {
        $data['status'] = 'error';
        $data['data']   = $e->getMessage();
    }
}
else
{
    $data['status'] = 'error';
    $data['data']   = $txt_error;
}

$db->close();

echo json_encode($data);
