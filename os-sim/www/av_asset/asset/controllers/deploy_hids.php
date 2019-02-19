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

Session::logcheck_by_asset_type('asset');
Session::logcheck('environment-menu', 'EventsHidsConfig');

$validation_errors = array();

$action = REQUEST('action');

$allowed_action = array(
    'select_os'        => 1,
    'deploy_agent'     => 2,
    'deploy_agentless' => 3
);


if (empty($allowed_action[$action]))
{
    Util::response_bad_request(_('Error! Action not allowed'));
}


switch ($action)
{
    case 'select_os':

        $validate = array (
            'asset_id'   => array('validation' => 'OSS_HEX',    'e_message' => 'illegal:' . _('Asset ID')),
            'os_windows' => array('validation' => 'OSS_BINARY', 'e_message' => 'illegal:' . _('Operating System'))
        );

    break;

    case 'deploy_agent':

        $validate = array (
            'asset_id'   => array('validation' => 'OSS_HEX',                                              'e_message' => 'illegal:' . _('Asset ID')),
            'sensor_id'  => array('validation' => 'OSS_HEX',                                              'e_message' => 'illegal:' . _('Sensor ID')),
            'ip_address' => array('validation' => 'OSS_IP_ADDR',                                          'e_message' => 'illegal:' . _('IP Address')),
            'user'       => array('validation' => 'OSS_USER',                                             'e_message' => 'illegal:' . _('User')),
            'pass'       => array('validation' => 'OSS_PASSWORD',                                         'e_message' => 'illegal:' . _('Password')),
            'domain'     => array('validation' => 'OSS_NOECHARS, OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE',  'e_message' => 'illegal:' . _('Domain'))
        );

    break;

    case 'deploy_agentless':

        /************************************************
         *******************   TO DO   ******************
         ************************************************/

    break;
}


//Validate parameters

if (GET('ajax_validation') == TRUE)
{
    $data['status'] = 'OK';

    $validation_errors = validate_form_fields('GET', $validate);
    if (is_array($validation_errors) && !empty($validation_errors))
    {
        $data['status'] = 'error';
        $data['data']   = $validation_errors;
    }

    echo json_encode($data);
    exit();
}


$asset_id = POST('asset_id');
$token    = POST('token');

//Checking form token
if (!isset($_POST['ajax_validation_all']) || POST('ajax_validation_all') == FALSE)
{
    if (Token::verify('tk_deploy_form', $token) == FALSE)
    {
        $data['status'] = 'error';
        $data['data']['tk_form'] = Token::create_error_message();

        echo json_encode($data);
        exit();
    }
}

$db   = new ossim_db();
$conn = $db->connect();

$validation_errors = validate_form_fields('POST', $validate);

switch ($action)
{
    case 'select_os':

        $os_windows  = POST('os_windows');
    break;

    case 'deploy_agent':

        $sensor_id  = POST('sensor_id');
        $ip_address = POST('ip_address');
        $user       = POST('user');
        $pass       = POST('pass');
        $domain     = POST('domain');

        //Extra validations

        if (empty($validation_errors))
        {
            //Checking Asset ID

            if (Asset_host::is_allowed($conn, $asset_id) == FALSE)
            {
                $validation_errors['asset_id'] = _('You do not have permission to deploy HIDS agent to this asset. Please check with your account admin for more information');
            }

            //Checking HIDS Sensor
            $cnd_1 = (Ossec_utilities::is_sensor_allowed($conn, $sensor_id) == FALSE);

            $asset_sensors = Asset_host_sensors::get_sensors_by_id($conn, $asset_id);

            $cnd_2 = (empty($asset_sensors[$sensor_id]));

            if ($cnd_1 || $cnd_2)
            {
                $validation_errors['sensor_id'] = sprintf(_("Sensor %s not allowed. Please check with your account admin for more information"), Av_sensor::get_name_by_id($conn, $sensor_id));
            }

            //Checking IP Address
            $aux_asset_ips = Asset_host_ips::get_ips_to_string($conn, $asset_id);

            if (preg_match('/'.$ip_address.'/', $aux_asset_ips) == FALSE)
            {
                $validation_errors['ip_address'] = _("The IP address you enter is not valid. Please check your asset and network settings and try again");
            }
        }

    break;

    case 'deploy_agentless':


        /***********************************************
         *******************   TO DO   ******************
         ************************************************/

    break;
}


//AJAX validator: Return validation results

if (POST('ajax_validation_all') == TRUE)
{
    if (is_array($validation_errors) && !empty($validation_errors))
    {
        $data['status'] = 'error';
        $data['data']   = $validation_errors;
    }
    else
    {
        $data['status'] = 'OK';
        $data['data']   = '';
    }

    $db->close();

    echo json_encode($data);
    exit();
}


//Perform action

if (is_array($validation_errors) && !empty($validation_errors))
{
    $error_msg = '<div style="padding-left:5px">'._('The following errors occurred').":</div>
        <div style='padding: 5px 5px 5px 15px;'>".implode('<br/>', $validation_errors).'</div>';

    $db->close();

    Util::response_bad_request($error_msg);
}

try
{
    $data['status'] = 'success';
    $data['data']   = _('Your changes have been saved');

    switch ($action)
    {
        case 'select_os':

            //Select Operating System
            if ($os_windows == 1)
            {
                Asset_host_properties::delete_property_from_db($conn, $asset_id, 3);
                Asset_host_properties::save_property_in_db($conn, $asset_id, 3, 'Microsoft Windows', 1);
            }
            else
            {
                $data['status'] = 'warning';
                $data['data']   = _("Unable to deploy HIDS agent. Automatic deployment is only available for Windows operating systems. Please go to <a href='javascript:parent.GB_close({\"action\": \"go_to_hids\"});' class='bold_yellow'>HIDS page</a> for more options");
            }

        break;

        case 'deploy_agent':

            //Deploy HIDS Agent
            $db   = new Ossim_db();
            $conn = $db->connect();


            $d_data = array(
                'asset_id'   => $asset_id,
                'w_ip'       => $ip_address,
                'w_user'     => $user,
                'w_password' => $pass,
                'w_domain'   => $domain
            );

            $hids_agents = Asset_host::get_related_hids_agents($conn, $asset_id, $sensor_id);
            $num_agents  = count($hids_agents);

            if ($num_agents >= 1)
            {
                if ($num_agents == 1)
                {
                    $agent = array_pop($hids_agents);
                    $d_data['agent_id'] = $agent['agent_id'];
                }
                else
                {
                    $e_msg = _('Unable to deploy HIDS agent. This asset already has an agent deployed. If you want to deploy a new agent, please review <a class="bold_red" href="https://www.alienvault.com/help/redirect/usm/connect_agent" target="_blank">how to manage agent connections</a> and try again');

                    Av_exception::throw_error(Av_exception::USER_ERROR, $e_msg);
                }
            }

            $res = Ossec_agent::deploy_windows_agent($sensor_id, $d_data);

            $job_id = $res['job_id'];

            $attempts = 0;
            $max_attempts = 80;

            $data = Ossec_agent::check_deployment_status($job_id);

            while ($data['status'] == 'in_progress' && $attempts < $max_attempts)
            {
                sleep(3);

                $data = Ossec_agent::check_deployment_status($job_id);

                $attempts++;
            }


            if ($attempts >= $max_attempts)
            {
                $e_msg = _('Connection has timed out. Please deploy the HIDS agent again');

                Av_exception::throw_error(Av_exception::USER_ERROR, $e_msg);
            }
            else
            {
                if ($data['status'] != 'success')
                {
                    $e_msg = $data['data']."<br/><br/>".$data['help'];

                    Av_exception::throw_error(Av_exception::USER_ERROR, $e_msg);
                }
            }

        break;


        case 3:
            //Deploy Agentless

            /************************************************
             *******************   TO DO   ******************
             ************************************************/

        break;
    }

    echo json_encode($data);
}
catch(Exception $e)
{
    $db->close();

    if (preg_match('/^Warning!/', $e->getMessage()))
    {
        $error_msg = '<div style="padding-left:10px">'.$e->getMessage().'</div>';
    }
    else
    {
        $error_msg = "<div style='padding: 5px 5px 5px 15px;'>".$e->getMessage().'</div>';
    }

    Util::response_bad_request($error_msg);
}
