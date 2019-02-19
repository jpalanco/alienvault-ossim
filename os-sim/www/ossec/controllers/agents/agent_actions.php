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
$agents            = array();

$permitted_actions = array(
    'delete_agent'       => '1',
    'check_integrity'    => '1',
    'restart_agent'      => '1',
    'modified_files'     => '1',
    'modified_reg_files' => '1',
    'rootcheck'          => '1',
    'extract_key'        => '1'
);


$action    = POST('action');
$sensor_id = REQUEST('sensor_id');
$id        = POST('id');


$validate = array(
    'sensor_id' => array('validation' => "OSS_HEX",   'e_message' => 'illegal:' . _('Sensor ID')),
    'id'        => array('validation' => 'OSS_DIGIT', 'e_message' => 'illegal:' . _('Agent ID')));


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
    }
}


if (is_array($validation_errors) && !empty($validation_errors))
{
    $validation_errors['html_errors'] = "<div style='text-align: left;'>"._('The following errors occurred').":</div>
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
                $data['data']   = _('HIDS agent_control.  Agent')." $id "._('restarted');
            }
            catch (Exception $e)
            {
                $data['status'] = 'error';

                $msg       = explode('OSSEC HIDS agent_control:', $e->getMessage());
                $char_list = "\t\n\r\0\x0B";

                $data['data'] = trim(str_replace("**", '', $msg[0]), $char_list);
            }
        break;

        case 'check_integrity':

            try
            {
                Ossec_agent::check_integrity($sensor_id, $id);

                $data['status'] = 'success';
                $data['data']   = _('HIDS agent_control: Restarted Syscheck/Rootcheck on agent').": $id";
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
