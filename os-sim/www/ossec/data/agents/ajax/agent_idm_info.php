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


require_once dirname(__FILE__) . '/../../../conf/config.inc';

session_write_close();

$m_perms  = array ('environment-menu', 'environment-menu');
$sm_perms = array ('EventsHids', 'EventsHidsConfig');

$sensor_id    = POST('sensor_id');
$agent_id     = POST('agent_id');
$agent_ip     = POST('agent_ip');
$agent_name   = POST('agent_name');


$validate = array (
    'sensor_id'  => array('validation' => "OSS_HEX",                                                        'e_message' => 'illegal:' . _('Sensor ID')),
    'agent_id'   => array('validation' => "OSS_DIGIT",                                                      'e_message' => 'illegal:' . _('Agent ID')),
    'agent_name' => array('validation' => 'OSS_SCORE, OSS_LETTER, OSS_DIGIT, OSS_DOT, OSS_SPACE, "(", ")"', 'e_message' => 'illegal:' . _('Agent Name')),
    'agent_ip'   => array('validation' => 'OSS_IP_CIDR_0',                                                  'e_message' => 'illegal:' . _('Agent IP')));


if ($agent_ip == 'any')
{
    $validate['ip_cidr'] = array('validation' => 'any',                                                     'e_message' => 'illegal:' . _('Agent IP'));
}

$validation_errors = validate_form_fields('POST', $validate);


if (empty($validation_errors['sensor_id']))
{
    $db    = new ossim_db();
    $conn  = $db->connect();

    if (!Ossec_utilities::is_sensor_allowed($conn, $sensor_id))
    {
        $validation_errors['sensor_id'] = _('Error! Sensor not allowed');
    } 
    
    $db->close();
}


if (empty($validation_errors))
{
    //IDM Info
    $agent_idm_data = Ossec_agent::get_idm_data($sensor_id, $agent_ip);
    
    if (empty($agent_idm_data))
    {
        $agent = array(
            'ip'   => $agent_ip,
            'name' => $agent_name
        );

        $last_ip = Ossec_agent::get_last_ip($sensor_id, $agent);
       
        if (Asset_host_ips::valid_ip($last_ip))
        {
            $agent_idm_data =  array('userdomain' => '-', 
                                     'ip'         => $last_ip);
        }
        else
        {
            $agent_idm_data = array('userdomain' => '-', 
                                    'ip'         => '-');
        }
    }

    $data['status'] = 'success';
    $data['data']   = $agent_idm_data;

}
else
{
    $data['status'] = 'error';
    $data['data']   = $validation_errors;
}

echo json_encode($data);
exit();