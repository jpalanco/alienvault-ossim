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

Session::logcheck_ajax('environment-menu', 'EventsHidsConfig');


$db   = new ossim_db();
$conn = $db->connect();


$permitted_actions = array(
    'get_agentless_status'    => '1',
    'verify_monitoring_entry' => '1'
);


$action     = POST('action');
$sensor_id  = POST('sensor');


if (!array_key_exists($action, $permitted_actions))
{
    Util::response_bad_request(_('Action not allowed'));
}

switch ($action)
{
    case 'verify_monitoring_entry':

        $validate = array (
            'id_type'     => array('validation' => 'OSS_NOECHARS, OSS_SCORE, OSS_LETTER',   'e_message' => 'illegal:' . _('Type')),
            'frequency'   => array('validation' => 'OSS_DIGIT',                             'e_message' => 'illegal:' . _('frequency')),
            'state'       => array('validation' => 'OSS_NOECHARS, OSS_SCORE, OSS_LETTER',   'e_message' => 'illegal:' . _('State')),
            'arguments'   => array('validation' => "OSS_NOECHARS, OSS_TEXT, OSS_SPACE, OSS_AT, OSS_NULLABLE, OSS_PUNC_EXT, '\`', '\<', '\>'", 'e_message' => 'illegal:' . _('Arguments'))
        );

    break;
    
    case 'get_agentless_status':

        $validate = array(
            'sensor' => array('validation' => "OSS_HEX", 'e_message' => 'illegal:' . _('Sensor'))
        );
        
    break;
}


$validation_errors = validate_form_fields('POST', $validate);


if (is_array($validation_errors) && !empty($validation_errors))
{
    $error_message = _('The following errors occurred'). ": <br/>" . implode("<br/>", $validation_errors);

    Util::response_bad_request($error_message);
}


$data = array();
$data['status'] = 'success';

switch ($action)
{
    case 'get_agentless_status':

        if (!Ossec_utilities::is_sensor_allowed($conn, $sensor_id))
        {
            Util::response_bad_request(_('Error! Sensor not allowed'));
        }
        else
        {
            $sensor_status          = Ossec_control::execute_action($sensor_id, 'status');
            $data['data']['status'] = $sensor_status['service_status']['agentless'];
            $data['data']['reload'] = (file_exists ("/var/tmp/.reload_$sensor_id"))? 'reload_red' : 'reload';

            //Logged user
            $user = Session::get_session_user();

            //Error file
            $agenteless_error_log = "/var/tmp/_agentless_error_$user".'.log';

            if(file_exists($agenteless_error_log))
            {
                $msgs = file($agenteless_error_log);

                $data['data']['log'] = '';
                foreach($msgs as $msg)
                {
                    if(trim($msg) == '')
                    {
                         continue;
                    }

                    $data['data']['log'] .= $msg . '<br>';
                }

                @unlink($agenteless_error_log);
            }
        }

    break;
}

echo json_encode($data);

$db->close();