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

session_write_close();

$m_perms  = array ('environment-menu', 'environment-menu');
$sm_perms = array ('EventsHids', 'EventsHidsConfig');

$sensor_id   = POST('sensor_id');
$agent_id    = POST('agent_id');
$agent_ip    = POST('agent_ip');
$asset_id    = POST('asset_id');


$validate = array (
    'sensor_id'  => array('validation' => "OSS_HEX",               'e_message' => 'illegal:' . _('Sensor ID')),
    'asset_id'   => array('validation' => "OSS_HEX, OSS_NULLABLE", 'e_message' => 'illegal:' . _('Asset ID')),
    'agent_id'   => array('validation' => "OSS_DIGIT",             'e_message' => 'illegal:' . _('Agent ID')),
    'agent_ip'   => array('validation' => 'OSS_IP_ADDRCIDR',       'e_message' => 'illegal:' . _('Agent IP')));


if ($agent_ip == 'any')
{
    $validate['ip_cidr'] = array('validation' => 'any',             'e_message' => 'illegal:' . _('Agent IP'));
}

$validation_errors = validate_form_fields('POST', $validate);


$db   = new ossim_db();
$conn = $db->connect();

if (empty($validation_errors['sensor_id'])) {
    if (!Ossec_utilities::is_sensor_allowed($conn, $sensor_id)) {
        $validation_errors['sensor_id'] = sprintf(_("Sensor %s not allowed. Please check with your account admin for more information."), Av_sensor::get_name_by_id($conn, $sensor_id));
    } else {
        $agent = array(
            'host_id' => $asset_id,
            'ip_cidr' => $agent_ip
        );

        if (!Ossec_agent::is_allowed($conn, $sensor_id, $agent)){
            $validation_errors['asset_id'] = _('Not enough permissions to get the IDM information');
        }
    }
}


if (empty($validation_errors)) {
    $current_user = '-';
    $current_ip   = '-';

    //Current user
    if (valid_hex32($asset_id))
    {
        $q_filters = array(
            'limit' => "1"
        );

        list($users, $total_users) = Asset_host_properties::get_users_by_host($conn, $asset_id, $q_filters);

        if ($total_users > 0)
        {
            $_current_user = array_pop($users[$asset_id]);

            if (!empty($_current_user)) {
                $current_user  = $_current_user['user'];
                $current_user .= (!empty($_current_user['domain'])) ? '@'.$_current_user['domain'] : '';
            }
        }
    }


    //Current IP
    $agent = array(
        'ip_cidr'   => $agent_ip,
        'agent_id'  => $agent_id
    );

    $_current_ip = Ossec_agent::get_last_ip($sensor_id, $agent);

    if (Asset_host_ips::valid_ip($_current_ip)) {
        $current_ip = $_current_ip;
    }

    $agent_idm_data =  array(
        'current_ip'   => $current_ip,
        'current_user' => $current_user
    );

    $data['status'] = 'success';
    $data['data']   = $agent_idm_data;

}
else
{
    $data['status'] = 'error';
    $data['data']   = $validation_errors;
}

$db->close();

echo json_encode($data);
exit();
