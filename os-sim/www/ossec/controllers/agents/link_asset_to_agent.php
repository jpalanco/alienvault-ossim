<?php

/**
 *
 * License:
 *
 * Copyright (c) 2003-2006 ossim.net
 * Copyright (c) 2007-2014 AlienVault
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

session_write_close();

$validation_errors = array();

$sensor_id  = REQUEST('sensor_id');
$asset_id   = REQUEST('asset_id');
$agent_id   = REQUEST('agent_id');

$validate = array(
    'sensor_id' => array('validation' => "OSS_HEX",     'e_message' => 'illegal:' . _('Sensor ID')),
    'asset_id'  => array('validation' => "OSS_HEX",     'e_message' => 'illegal:' . _('Asset ID')),
    'agent_id'  => array('validation' => 'OSS_DIGIT',   'e_message' => 'illegal:' . _('Agent ID'))
);


$validation_errors = array();

if (GET('ajax_validation') == TRUE)
{
    $data['status'] = 'OK';

    $validation_errors = validate_form_fields('GET', $validate);
    if (is_array($validation_errors) && !empty($validation_errors))
    {
        $data['status'] = 'error';
        $data['data']   = $validation_errors;
    }
}
else
{
    $db   = new ossim_db();
    $conn = $db->connect();

    $validation_errors = validate_form_fields('POST', $validate);

    //Extra validations

    if (empty($validation_errors['sensor_id']) && !Ossec_utilities::is_sensor_allowed($conn, $sensor_id))
    {
        $validation_errors['sensor_id'] = _("Unable to connect asset to HIDS agent. The selected sensor is not allowed. Please update the sensor in asset details and try again");
    }


    //Checking if asset was linked to other HIDS Agent
    $_aux_agents = Asset_host::get_related_hids_agents($conn, $asset_id, $sensor_id);

    $agent_key = md5(strtoupper($sensor_id).'#'.$agent_id);
    unset($_aux_agents[$agent_key]);

    if (!empty($_aux_agents))
    {

        $validation_errors['asset_id'] = sprintf(_("Unable to connect HIDS agent to '%s'. This asset already has an agent deployed. If you want to deploy a new agent, please review <a class=\"bold_red\" href=\"https://www.alienvault.com/help/redirect/usm/connect_agent\" target=\"_blank\">how to manage agent connections</a> and try again"), Asset_host::get_name_by_id($conn, $asset_id));
    }

    $db->close();
}


if (is_array($validation_errors) && !empty($validation_errors))
{
    $validation_errors['html_errors'] = "<div style='text-align: left;'>"._('The following errors occurred').":</div>
                                         <div style='padding-left:15px; text-align: left;'>".implode('<br/>', $validation_errors)."</div>";

    $data['status'] = 'error';
    $data['data']   = $validation_errors;
}
else
{
    $data['status'] = 'success';
    $data['data']   = _('Your changes have been saved');

    try
    {
        Ossec_agent::link_to_asset($sensor_id, $agent_id, $asset_id);

        $db   = new ossim_db();
        $conn = $db->connect();


        $agents    = Asset_host::get_related_hids_agents($conn, $asset_id, $sensor_id);
        $agent_key = md5(strtoupper($sensor_id).'#'.$agent_id);

        $agent_info = array(
            'ip_cidr' => $agents[$agent_key]['ip_cidr'],
            'host_id' => $asset_id
        );

        $data['asset']  = array(
            'id'      => $asset_id,
            'name'    => Asset_host::get_name_by_id($conn, $asset_id),
            'actions' => Ossec_agent::get_actions($agent_id, $agent_info)
        );

        $db->close();
    }
    catch (Exception $e)
    {
        $data['status'] = 'error';
        $data['data']   = _('An unexpected error occurred. Unable to connect asset to HIDS agent. Please try again').'.<br/><br/>'.sprintf(_('Reason: %s'), $e->getMessage());
    }
}


echo json_encode($data);
exit();
