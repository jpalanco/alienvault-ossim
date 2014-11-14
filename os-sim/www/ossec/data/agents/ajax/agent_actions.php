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

require_once dirname(__FILE__) . '/../../../conf/config.inc';

Session::logcheck('environment-menu', 'EventsHidsConfig');


$validation_errors = array();
$agents            = array();

$permitted_actions = array(
    'add_agent'          => '1',
    'delete_agent'       => '1',
    'check_agent'        => '1',
    'restart_agent'      => '1',
    'modified_files'     => '1',
    'modified_reg_files' => '1',
    'rootcheck'          => '1',
    'extract_key'        => '1'
);

$agent_name = REQUEST('agent_name');
$ip_cidr    = strtolower(REQUEST('ip_cidr'));
$sensor_id  = REQUEST('sensor_id');

$action = POST('action');
$id     = POST('id');


$validate = array(
    'sensor_id'  => array('validation' => "OSS_HEX",                                   'e_message' => 'illegal:' . _('Sensor ID')),
    'agent_name' => array('validation' => 'OSS_SCORE, OSS_LETTER, OSS_DIGIT, OSS_DOT', 'e_message' => 'illegal:' . _('Agent Name')),
    'ip_cidr'    => array('validation' => 'OSS_IP_CIDR_0',                             'e_message' => 'illegal:' . _('IP/CIDR')));

if ($ip_cidr == 'any')
{
    $validate['ip_cidr'] = array('validation' => 'any',                                'e_message' => 'illegal:' . _('IP/CIDR'));
}

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
else
{
    if (!array_key_exists($action, $permitted_actions) || $permitted_actions[$action] != 1)
    {
        $validation_errors['action_now_allowed'] = _('Action not allowed');
    }
    else
    {
        if ($action != 'add_agent' && $action != 'idm_data')
        {
            $validate = array('id' => array('validation' => 'OSS_DIGIT', 'e_message' => 'illegal:'._('Agent ID')));
        }

        $validation_errors = validate_form_fields('POST', $validate);


        if (empty($validation_errors['sensor_id']))
        {
            $db   = new ossim_db();
            $conn = $db->connect();

            if (!Ossec_utilities::is_sensor_allowed($conn, $sensor_id))
            {
                $validation_errors['sensor_id'] = _('Error! Sensor not allowed');
            }

            $db->close();
        }

        if (empty($validation_errors) && $action == 'add_agent')
        {
            try
            {
                $agents = Ossec_agent::get_list($sensor_id);

                foreach ($agents as $a_data)
                {
                    if (!empty($agent_name) && $a_data['name'] == $agent_name)
                    {
                        $validation_errors['add_agent'] = _('Name')." '$agent_name' "._('already present. Please enter a new name.');

                        break;
                    }
                }

                if (strlen($agent_name) < 2 || strlen($agent_name) > 32)
                {
                    $validation_errors['add_agent'] = _('Invalid name')." '$agent_name' "._('given.<br/> Name must contain only alphanumeric characters (min=2, max=32).');
                }
            }
            catch (Exception $e)
            {
                $validation_errors['add_agent'] = _('Error to add agent. Unable to retrieve agent list');
            }
        }

        //Check Token
        if (empty($validation_errors))
        {
            if (!Token::verify('tk_f_agents', POST('token')))
            {
                $validation_errors['add_agent'] = Token::create_error_message();
            }
        }
    }
}


if (is_array($validation_errors) && !empty($validation_errors))
{
    $validation_errors['html_errors'] = "<div style='text-align: left;'>"._('We found the following errors').":</div>
                                         <div style='padding-left:15px; text-align: left;'>".implode('<br/>', $validation_errors)."</div>";

    $data['status'] = 'error';
    $data['data']   = $validation_errors;

    echo json_encode($data);
    exit();
}
else
{
    $ret            = NULL;
    $data['status'] = 'success';

    switch ($action)
    {
        case 'add_agent':

            try
            {
                $idm_enabled = (isset($_SESSION['_idm']) && !empty($_SESSION['_idm'])) ? TRUE : FALSE;
                session_write_close();

                $new_agent = Ossec_agent::create($sensor_id, $agent_name, $ip_cidr);

                //If ossec-remoted is not running, we have to restart Ossec Server
                $ossec_status = Ossec_control::execute_action($sensor_id, 'status');

                if ('UP' !== $ossec_status['general_status']['ossec-remoted'])
                {
                    Ossec_control::execute_action($sensor_id, 'restart');
                }

                if (is_array($new_agent) && !empty($new_agent))
                {
                    $agent_id = $new_agent[0];

                    $new_agent = array(
                        'name'   => $new_agent[1],
                        'ip'     => $new_agent[2],
                        'status' => $new_agent[3]
                    );

                    $agent_actions = Ossec_agent::get_actions($agent_id, $new_agent);

                    $data['data'] = _('Agent added successfully')."###".$agent_id."###";

                    $data['data'] .= '[{
                        "DT_RowId": "cont_agent_'.$agent_id.'",
                        "0": "'."<img class='info' src='".OSSEC_IMG_PATH."/information.png'/>".'",
                        "1": "'.$agent_id.'",
                        "2": "'.$new_agent['name'].'",
                        "3": "'.$new_agent['ip'].'",';

                    if ($idm_enabled == TRUE)
                    {
                        $data['data'] .= '
                            "4": "'."<div style='text-align: center !important'> - </div>".'",
                            "5": "'."<div style='text-align: center !important'> - </div>".'",';
                    }

                    $data['data'] .= '
                        "6": "'.$new_agent['status'].'",
                        "7": "'.$agent_actions.'"
                    }]';
                }
            }
            catch (Exception $e)
            {
                $data['status'] = 'error';
                $data['data']   = _('Agent not added successfully').'.<br/><br/>'.$e->getMessage();
            }

        break;


        case 'delete_agent':

            try
            {
                Ossec_agent::delete($sensor_id, $id);

                $data['status'] = 'success';
                $data['data']   = _('Agent deleted successfully');
            }
            catch(Exception $e)
            {
                $data['status'] = 'error';
                $data['data']   = _('Error! Agent not deleted successfully');
            }

        break;

        case 'restart_agent':

            try
            {
                Ossec_agent::restart($sensor_id, $id);

                $data['status'] = 'success';
                $data['data']   = _('OSSEC HIDS agent_control.  Agent')." $id "._('restarted');
            }
            catch (Exception $e)
            {
                $data['status'] = 'error';

                $msg       = explode('OSSEC HIDS agent_control:', $e->getMessage());
                $char_list = "\t\n\r\0\x0B";

                $data['data'] = trim(str_replace("**", '', $msg[0]), $char_list);
            }  
        break;

        case 'check_agent':

            try
            {
                Ossec_agent::check_integrity($sensor_id, $id);

                $data['status'] = 'success';
                $data['data']   = _('OSSEC HIDS agent_control: Restarted Syscheck/Rootcheck on agent').": $id";
            }
            catch (Exception $e)
            {
                $data['status'] = 'error';

                $msg       = explode('OSSEC HIDS agent_control:', $e->getMessage());
                $char_list = "\t\n\r\0\x0B";

                $data['data'] = trim(str_replace("**", '', $msg[0]), $char_list);
            }

        break;

        case 'extract_key':

            try
            {
                $agent_key = Ossec_agent::get_key($sensor_id, $id);

                if (!empty($agent_key))
                {
                    $data['status'] = 'success';
                    $data['data']   = "Agent key information for '$id' is:";
                    $data['data'] .= "<div class='agent_key'>".$agent_key."<br/></div>";
                }
                else
                {
                    $data['status'] = 'error';
                    $data['data']   = _('Error! Agent key not found');
                }
            }
            catch (Exception $e)
            {
                $data['status'] = 'error';
                $data['data']   = _('Error! Agent key could not be extracted');
            }

        break;

        case 'modified_files':

            try
            {
                $data['status'] = 'success';

                $res = Ossec_agent::launch_syscheck($sensor_id, $id);

                $header = array_shift($res);

                if (count($res) > 0)
                {
                    $data['data'] = "<div style='font-weight: bold; font-size: 11px; padding: 10px 0px;'>"._($header)."</div>
                                        <table class='table_files table_data' id='tf'>
                                            <thead>
                                                <tr>
                                                    <th class='cf_date'>"._('Date')."</th>
                                                    <th>"._('File')."</th>
                                                    <th class='cf_ocurrences'>#</th>
                                                </tr>
                                            </thead>

                                            <tbody>";

                    $days  = 0;
                    $dates = array();

                    foreach ($res as $line)
                    {
                        $r_data = explode(',', $line);

                        if (!empty($r_data))
                        {
                            if (empty($dates[$r_data[0]]))
                            {
                                $dates[$r_data[0]] = $r_data[0];
                                $days              = $days + 1;
                                $color             = ($days % 2 == 0) ? 'class="odd"' : 'class="even"';
                            }

                            $data['data'] .= "<tr $color>
                                                <td class='cf_date'>".$r_data[0]."</td>
                                                <td class='cf_path'>".$r_data[1]."</td>
                                                <td class='cf_ocurrences'>".$r_data[2]."</td>
                                              </tr>";
                        }
                    }

                    $data['data'] .= "</tbody>
                                </table>";
                }
                else
                {
                    $config_nt = array(
                        'content' => _($header)." <span style='font-weight: bold;'>"._('No results')."</span>",
                        'options' => array(
                            'type'          => 'nf_info',
                            'cancel_button' => FALSE
                        ),
                        'style'   => 'width: 80%; margin: 20px auto; text-align: left;'
                    );

                    $nt = new Notification('nt_1', $config_nt);

                    $data['data'] = $nt->show(FALSE);
                }
            }
            catch (Exception $e)
            {
                $data['status'] = 'error';
                $data['data']   = _('Error! Modified files could not be extracted for this agent');
            }

        break;

        case 'modified_reg_files':

            try
            {
                $data['status'] = 'success';

                $res = Ossec_agent::launch_syscheck($sensor_id, $id, TRUE);

                $header = array_shift($res);

                if (count($res) > 0)
                {
                    $data['data'] = "<div style='font-weight: bold; font-size: 11px; padding: 10px 0px;'>"._($header)."</div>
                                        <table class='table_files table_data' id='tf'>
                                            <thead>
                                                <tr>
                                                    <th class='cf_date'>"._('Date')."</th>
                                                    <th>"._('File')."</th>
                                                    <th class='cf_ocurrences'>#</th>
                                                </tr>
                                            </thead>
                                            
                                            <tbody>";

                    $days  = 0;
                    $dates = array();

                    foreach ($res as $line)
                    {
                        $r_data = explode(',', $line);

                        if (!empty($r_data))
                        {
                            if (empty($dates[$r_data[0]]))
                            {
                                $dates[$r_data[0]] = $r_data[0];
                                $days              = $days + 1;
                                $color             = ($days % 2 == 0) ? 'class="odd"' : 'class="even"';
                            }

                            $data['data'] .= "<tr $color>
                                                <td class='cf_date'>".$r_data[0]."</td>
                                                <td class='cf_path'>".$r_data[1]."</td>
                                                <td class='cf_ocurrences'>".$r_data[2]."</td>
                                             </tr>";
                        }
                    }

                    $data['data'] .= "</tbody>
                                </table>";
                }
                else
                {
                    $config_nt = array(
                        'content' => _($header)." <span style='font-weight: bold;'>"._('No results')."</span>",
                        'options' => array(
                            'type'          => 'nf_info',
                            'cancel_button' => FALSE
                        ),
                        'style'   => 'width: 80%; margin: 20px auto; text-align: left;'
                    );

                    $nt = new Notification('nt_1', $config_nt);

                    $data['data'] = $nt->show(FALSE);
                }
            }
            catch (Exception $e)
            {
                $data['status'] = 'error';
                $data['data']   = _('Error! Modified registry files could not be extracted for this agent');
            }

        break;

        case 'rootcheck':

            try
            {
                $data['status'] = 'success';

                $res = Ossec_agent::launch_rootcheck($sensor_id, $id);

                $header = _('Policy and auditing database');

                if (count($res) > 0)
                {
                    $data['data'] = "<div style='font-weight: bold; font-size: 11px; padding: 10px 0px;'>"._($header).":</div>
                                        <table class='table_files table_data' id='tf'>
                                            <thead>
                                                <tr>
                                                    <th class='cf_type'>"._('Type')."</th>
                                                    <th class='cf_date'>"._('Last Date')."</th>
                                                    <th class='cf_date'>"._('First Date')."</th>
                                                    <th>"._('Event')."</th>
                                                </tr>
                                            </thead>
                                            
                                            <tbody>";

                    $days  = 0;
                    $dates = array();

                    foreach ($res as $line)
                    {
                        $r_data = explode(',', $line);

                        if (!empty($r_data))
                        {
                            if (empty($dates[$r_data[1]]))
                            {
                                $dates[$data[1]] = $r_data[1];
                                $days            = $days + 1;
                                $color           = ($days % 2 == 0) ? 'class="odd"' : 'class="even"';
                            }

                            $data['data'] .= "<tr $color>
                                                    <td class='cf_type'>".$r_data[0]."</td>
                                                    <td class='cf_date'>".$r_data[1]."</td>
                                                    <td class='cf_date'>".$r_data[2]."</td>
                                                    <td class='cf_path'>".$r_data[3]."</td>
                                                </tr>";
                        }
                    }

                    $data['data'] .= "</tbody>
                                </table>";
                }
                else
                {
                    $config_nt = array(
                        'content' => $header.": <span style='font-weight: bold;'>"._('No results')."</span>",
                        'options' => array(
                            'type'          => 'nf_info',
                            'cancel_button' => FALSE
                        ),
                        'style'   => 'width: 80%; margin: 20px auto; text-align: left;'
                    );

                    $nt = new Notification('nt_1', $config_nt);

                    $data['data'] = $nt->show(FALSE);
                }
            }
            catch (Exception $e)
            {
                $data['status'] = 'error';
                $data['data']   = _('Error! Policy and auditing database could not be extracted for this agent');
            }     
            
        break;
    }

    echo json_encode($data);
    exit();
}
