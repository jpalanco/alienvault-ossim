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

$action = REQUEST('action');

$allowed_action = array(
    'show_unsupported'   => 1,
    'remove_unsupported' => 2,
    'deploy_all_agents'  => 3
);


if (empty($allowed_action[$action]))
{
    Util::response_bad_request(_('Error! Action not allowed'));
}

$db   = new Ossim_db();
$conn = $db->connect();

switch ($action)
{
    case 'show_unsupported':

        $data['status'] = 'success';
        $data['data']   = _('Your request has been processed');

        try
        {
            //Number of assets in the system
            list($assets, $total_assets) = Asset_host::get_list($conn, '', array('limit' => 1));

            //Number of selected assets
            $total_selected = Filter_list::get_total_selection($conn, 'asset');

            //Remove asset selection
            Filter_list::clean_selection($conn);

            //Getting the object with the filters.
            $filters = Filter_list::retrieve_filter_list_session();
            $filters->empty_filter_search($conn);

            if ($filters === FALSE)
            {
                $exp_msg = _('Sorry, operation was not completed due to an error when processing the request');

                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
            }

            if ($total_selected == $total_assets)
            {
                //All assets were selected, so we filter them by OS
                $os_filters = array(
                    'where' => '(host_properties.value NOT LIKE "windows%" AND host_properties.value NOT LIKE "microsoft%")'
                );

                list($os_list, $total_os) = Asset_host_properties::get_property_values($conn, 3, $os_filters);

                $filters->modify_filter(20, 'unknown', 0);

                foreach($os_list as $os_key => $os_value)
                {
                    $filters->modify_filter(20, $os_value, 0);
                }
            }
            else
            {
                //Not all assets were selected, so we filter by asset

                //Getting assets with unknown or Linux/UNIX Operating System
                $tables = 'LEFT JOIN host_properties hp ON hp.host_id=host.id AND hp.property_ref=3 INNER JOIN user_component_filter f ON f.asset_id = host.id';

                $os_filters = array(
                    'where' => '((hp.host_id IS NULL OR hp.value IS NULL OR hp.value LIKE "%unknown%") OR (hp.value NOT LIKE "windows%" AND hp.value NOT LIKE "microsoft%"))
                                AND f.asset_type="asset" AND f.session_id = "'.session_id().'"'
                );

                $unsupported_assets = Asset_host::get_list_tree($conn, $tables, $os_filters, FALSE, TRUE);

                foreach($unsupported_assets as $a_data)
                {
                    $filters->modify_filter(11, $a_data[2], 0);
                }
            }

            $filters->store_filter_list_session();
        }
        catch(Exception $e)
        {
            $db->close();

            $error_msg = '<div style="padding-left:5px">'._('The following errors occurred').":</div>
                <div style='padding: 5px 5px 5px 15px;'>".$e->getMessage().'</div>';

            Util::response_bad_request($error_msg);
        }

    break;

    case 'remove_unsupported':

        $data['status'] = 'success';
        $data['data']   = _('Your changes have been saved');

        try
        {
            Filter_list::clean_asset_by_criteria($conn, 'not_windows_os');
        }
        catch(Exception $e)
        {
            $db->close();

            $error_msg = '<div style="padding-left:5px">'._('The following errors occurred').":</div>
                <div style='padding: 5px 5px 5px 15px;'>".$e->getMessage().'</div>';


            Util::response_bad_request($error_msg);
        }

    break;

    case 'deploy_all_agents':

        $validation_errors = array();

        $validate = array (
            'user'       => array('validation' => 'OSS_USER',                                             'e_message' => 'illegal:' . _('User')),
            'pass'       => array('validation' => 'OSS_PASSWORD',                                         'e_message' => 'illegal:' . _('Password')),
            'domain'     => array('validation' => 'OSS_NOECHARS, OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE',  'e_message' => 'illegal:' . _('Domain'))
        );


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


        $token = POST('token');

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

        $validation_errors = validate_form_fields('POST', $validate);

        $user   = POST('user');
        $pass   = POST('pass');
        $domain = POST('domain');


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


        //Performimg action

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
            $data['data']   = _('Your request has been processed');


            //Getting assets with Windows OS
            $tables = ', host_properties hp, user_component_filter f';

            $filters = array(
                'where' => 'hp.host_id=host.id AND hp.property_ref=3 AND (hp.value LIKE "windows%" OR hp.value LIKE "microsoft%")
                            AND f.asset_id = host.id AND f.asset_type="asset" AND f.session_id = "'.session_id().'"'
            );


            list($assets_w_os, $total_windows) = Asset_host::get_list($conn, $tables, $filters, FALSE);

            $total_deployed = 0;
            $deployment_stats = array();

            if ($total_windows > 0)
            {
                //HIDS sensors
                $s_data = Ossec_utilities::get_sensors($conn);
                $hids_sensors = $s_data['sensors'];

                foreach ($assets_w_os as $asset_id => $a_data)
                {
                    $deployment_stats[$asset_id] = array(
                        'status' => 'success',
                        'data'   => '',
                    );

                    //Getting HIDS sensor and Windows IP
                    $sensor_id = NULL;

                    $hids_agents = Asset_host::get_related_hids_agents($conn, $asset_id);

                    $aux_ip_address = explode(',', $a_data['ips']);
                    $aux_ip_address = array_flip($aux_ip_address);

                    $default_ip_address = array_pop(array_keys($aux_ip_address));

                    if (is_array($hids_agents) && !empty($hids_agents))
                    {
                        //Case 1: HIDS Agents was previously deployed

                        $hids_agent = array_pop($hids_agents);

                        $sensor_id = $hids_agent['sensor_id'];
                        $agent_id  = $hids_agent['agent_id'];

                        if (Asset_host_ips::valid_ip($hids_agent['ip_cidr']) && array_key_exists($hids_agent['ip_cidr'], $aux_ip_address))
                        {
                            $ip_address = $hids_agent['ip_cidr'];
                        }
                        else
                        {
                            $ip_address = $default_ip_address;
                        }
                    }
                    else
                    {
                        //Case 2: Not HIDS Agent deployed

                        $asset_sensors = Asset_host_sensors::get_sensors_by_id($conn, $asset_id);

                        foreach($asset_sensors as $asset_sensor_id => $s_data)
                        {
                            //Checking HIDS Sensor
                            $cnd_1 = (Ossec_utilities::is_sensor_allowed($conn, $asset_sensor_id) == TRUE);

                            $cnd_2 = (!empty($asset_sensors[$asset_sensor_id]));

                            if ($cnd_1 && $cnd_2)
                            {
                                $sensor_id = $asset_sensor_id;
                                break;
                            }
                        }

                        $agent_id  = NULL;

                        $ip_address = $default_ip_address;
                    }


                    if ($sensor_id === NULL)
                    {
                        $deployment_stats[$asset_id]['status'] = 'error';
                        $deployment_stats[$asset_id]['data']   = _('Error! No HIDS sensor related to asset');

                        continue;
                    }


                    $d_data = array(
                        'asset_id'   => $asset_id,
                        'w_ip'       => $ip_address,
                        'w_user'     => $user,
                        'w_password' => $pass,
                        'w_domain'   => $domain,
                        'agent_id'   => $agent_id
                    );


                    $res = Ossec_agent::deploy_windows_agent($sensor_id, $d_data);

                    $job_id = $res['job_id'];

                    if (valid_hex32($job_id, TRUE) == FALSE)
                    {
                        $deployment_stats[$asset_id]['status'] = 'warning';
                        $deployment_stats[$asset_id]['data']   = _('Warning! Deployment job cannot be launched');
                    }
                    else
                    {
                        $total_deployed++;
                    }
                }

                if ($total_deployed == $total_windows)
                {
                    $data = array(
                        'status' => 'success',
                        'data'   => _('Deployment job/s scheduled successfully.
                            <br/>Check out the <span class="bold" id="go_to_mc">Message Center</span> for more details')
                    );
                }
                else
                {
                    if ($total_deployed == 0)
                    {
                        $data = array(
                            'status' => 'warning',
                            'data'   => _('Unable to deploy HIDS agents due to an internal error. Please try again'),
                            'stats'  => $deployment_stats
                        );
                    }
                    else
                    {
                        $total_not_deployed = $total_windows - $total_deployed;
                        $data = array(
                            'status' => 'warning',
                            'data'   => sprintf(_('Unable to deploy HIDS agents to %s assets.
                                <br/>Please check the <span class="bold" id="go_to_mc">Message Center</span> for details of other jobs'), $total_not_deployed),
                            'stats'  => $deployment_stats
                        );
                    }
                }
            }
            else
            {
                $data = array(
                    'status' => 'error',
                    'data'   => _('Unable to deploy HIDS agents due to an internal error. Please try again')
                );
            }
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
                $error_msg = '<div style="padding-left:5px">'._('The following errors occurred').":</div>
                    <div style='padding: 5px 5px 5px 15px;'>".$e->getMessage().'</div>';
            }

            Util::response_bad_request($error_msg);
        }

    break;
}

$db->close();
echo json_encode($data);
