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
$agent_name = REQUEST('agent_name');

$_REQUEST['ip_cidr'] = strtolower(REQUEST('ip_cidr'));
$ip_cidr    = REQUEST('ip_cidr');

$validate = array(
    'sensor_id'  => array('validation' => "OSS_HEX",                                   'e_message' => 'illegal:' . _('Sensor ID')),
    'asset_id'   => array('validation' => "OSS_HEX",                                   'e_message' => 'illegal:' . _('Asset')),
    'agent_name' => array('validation' => 'OSS_SCORE, OSS_LETTER, OSS_DIGIT, OSS_DOT', 'e_message' => 'illegal:' . _('Agent Name')),
    'ip_cidr'    => array('validation' => 'OSS_IP_ADDRCIDR',                           'e_message' => 'illegal:' . _('IP/CIDR')));

if ($ip_cidr == 'any')
{
    $validate['ip_cidr'] = array('validation' => 'any',                                'e_message' => 'illegal:' . _('IP/CIDR'));
}


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
        $validation_errors['sensor_id'] = _("Unable to deploy HIDS agent. The selected sensor is not allowed. Please update the sensor in asset details and try again");
    }


    if (empty($validation_errors))
    {
        //Validate Agent Name

        try
        {
            $agents = Ossec_agent::get_list($sensor_id);

            foreach ($agents as $a_data)
            {
                if (!empty($agent_name) && $a_data['name'] == $agent_name)
                {
                    $validation_errors['agent_name'] = _("The agent name you entered already exists. Please enter a new name and try again");

                    break;
                }
            }

            if (strlen($agent_name) < 2 || strlen($agent_name) > 32)
            {
                $validation_errors['agent_name'] = _("Unable to add agent. The agent must be between 2-32 characters and contain only alphanumeric characters. Please enter a new name and try again");
            }
        }
        catch (Exception $e)
        {
            $validation_errors['add_agent'] = _('Sorry, operation was not completed due to an error when processing the request. Please try again');
        }


        //Checking if asset was linked to other HIDS Agent
        $_aux_agents = Asset_host::get_related_hids_agents($conn, $asset_id, $sensor_id);

        if (!empty($_aux_agents))
        {
            $validation_errors['asset_id'] = _("Unable to add agent. The selected asset already has a HIDS agent deployed. Please select a different asset and try again.");
        }


        //Check Token
        if (empty($validation_errors))
        {
            if (!Token::verify('tk_f_agents', POST('token')))
            {
                $validation_errors['tk_form'] = Token::create_error_message();
            }
        }
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
    $ret            = NULL;
    $data['status'] = 'success';

    try
    {
        $new_agent = Ossec_agent::create($sensor_id, $agent_name, $ip_cidr, $asset_id);

        //If ossec-remoted is not running, we have to restart Ossec Server
        $ossec_status = Ossec_control::execute_action($sensor_id, 'status');

        if ('UP' !== $ossec_status['general_status']['ossec-remoted'])
        {
            Ossec_control::execute_action($sensor_id, 'restart');
        }

        if (is_array($new_agent) && !empty($new_agent))
        {
            $agent_id = $new_agent['id'];

            $agent_info = array(
                'name'    => $new_agent['name'],
                'ip_cidr' => $new_agent['ip_cidr'],
                'status'  => $new_agent['status']
            );

            $agent_actions = Ossec_agent::get_actions($agent_id, $new_agent);

            $data['data'] = _("HIDS agent has been created. To deploy the agent, please choose one of the options under the 'Actions' column")."###".$agent_id."###";

            $a_unique_id = md5($agent_id);

            if (valid_hex32($new_agent['host_id']))
            {
                $db   = new Ossim_db();
                $conn = $db->connect();

                $asset_name = Asset_host::get_name_by_id($conn, $new_agent['host_id']);

                $db->close();
            }
            else
            {
                $asset_name = '-';
            }

            //Normalize status description (See asset list filters)
            if ($new_agent['status']['id'] == 1)
            {
                $new_agent['status']['descr'] = 'Disconnected';
            }

            $agent_elem = array(
                "DT_RowId"   => 'cont_agent_'.$agent_id,
                "DT_RowData" => array(
                    'agent_key'    => $a_unique_id,
                    'asset_id'     => $new_agent['host_id'],
                    'agent_status' => $new_agent['status']
                ),
                '',
                $agent_id,
                $new_agent['name'],
                $asset_name,
                $new_agent['ip_cidr'],
                "-",
                "-",
                $new_agent['status']['descr'],
                $agent_actions
            );

            $data['data'] .= json_encode(array($agent_elem));
        }
    }
    catch (Exception $e)
    {
        $data['status'] = 'error';
        $data['data']   = _('An unexpected error occurred. Unable to create HIDS agent. Please try again').'.<br/><br/>'.$e->getMessage();
    }
}


echo json_encode($data);
exit();
