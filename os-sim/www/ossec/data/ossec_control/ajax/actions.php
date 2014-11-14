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

Session::logcheck('environment-menu', 'EventsHidsConfig');


session_write_close();

$allowed_act = array('start',
                     'stop',
                     'restart',
                     'status',
                     'ossec_log',
                     'alerts_log',
                     'enable_cs',
                     'disable_cs',
                     'enable_al',
                     'disable_al',
                     'enable_dbg',
                     'disable_dbg');


$sensor_id = POST('sensor_id');
$token     = POST('token');
$action    = strtolower(POST('action'));

ossim_valid($sensor_id, OSS_HEX, 'illegal:' . _('Sensor ID'));

if (!in_array($action, $allowed_act))
{
    $data['status'] = 'error';
    $data['data']   =  _('Action not allowed');

    echo json_encode($data);
    exit();
}

if (ossim_error())
{
   $data['status'] = 'error';
   $data['data']   = ossim_get_error_clean();
}
else
{
    if (!Token::verify('tk_f_ossec_control', $token))
    {
        $data['status'] = 'error';
        $data['data']   = _("A Cross-Site Request Forgery attempt has been detected or the token has expired");
    }
    else
    {
        $db    = new ossim_db();
        $conn  = $db->connect();
        
        if (!Ossec_utilities::is_sensor_allowed($conn, $sensor_id))
        {
            $data['status'] = 'error';
            $data['data']   = _("Error! Sensor not allowed");
        }

        $db->close();
    }
}


if ($data['status'] == 'error')
{   
    $data['status'] = 'error';
    $data['data']   = _('We found the followings errors:')."<div style='padding-left: 15px; text-align:left;'>".$data['data']."</div>";

    echo json_encode($data);
    exit();
}


//Current sensor
$_SESSION['ossec_sensor'] = $sensor_id;


$data['status'] = 'success';
$data['data']   =  NULL;


try
{
    switch($action)
    {
        case 'ossec_log':
            $num_lines = POST('num_lines');
            $num_lines = intval($num_lines);

            $log_data  = Ossec_control::get_logs($sensor_id, 'ossec', $num_lines);

            $log_data = str_replace('INFO', "<span style='font-weight: bold; color:#15B103;'>INFO</span>", $log_data);
            $data['data'] = str_replace('ERROR', "<span style='font-weight: bold; color:#E54D4d;'>ERROR</span>", $log_data);
        break;
        
        case 'alerts_log':
            $num_lines = POST('num_lines');
            $num_lines = intval($num_lines);

            $log_data = Ossec_control::get_logs($sensor_id, 'alert', $num_lines);

            //Ossec 2.6
            $pattern    = array();
            $pattern[0] = '/\*\* Alert ([0-9]+\.[0-9]+)/';

            //Ossec 2.7
            $pattern[1] = '/AV - Alert - "[0-9]+"/';
            $pattern[2] = '/(RID:|RL:|RG:|RC:|USER:|SRCIP:|HOSTNAME:|LOCATION:|EVENT:)\s/';

            //Ossec 2.6
            $replacement    = array();
            $replacement[0] = "<span style='font-weight: bold; color:#E54D4d;'>$0</span>";

            //Ossec 2.7
            $replacement[1] = "<br/><span style='font-weight: bold; color:#E54D4d;'>$0</span>";
            $replacement[2] = "<span style='color:gray;'>$0 </span>";

            //XSS
            $pattern[3]     = '/\<script\>/';
            $replacement[3] = '&lt;script&gt;';
            $pattern[4]     = '/\<\/script\>/';
            $replacement[4] = '&lt;/script&gt;';

            $data['data'] = preg_replace($pattern, $replacement, $log_data);
        break;

        default:

            try
            {
                $response = Ossec_control::execute_action($sensor_id, $action);

                //Wait until OSSEC is UP
                if ($action == 'enable_dbg' || $action == 'disable_dbg')
                {
                    $response = Ossec_control::execute_action($sensor_id, 'restart');
                }

                sleep(1);
            }
            catch(Exception $e)
            {
                 Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
            }

            $response = Ossec_control::execute_action($sensor_id, 'status');
            $response = Ossec_control::get_html_status($response);

            $data['data'] .= $response['stdout']."###
                                <td id='cont_cs_action'>".$response['buttons']['syslog']."</td>
                                <td id='cont_al_action'>".$response['buttons']['agentless']."</td>
                                <td id='cont_dbg_action'>".$response['buttons']['debug']."</td>
                                <td id='cont_system_action'>".$response['buttons']['system']."</td>";

        break;
    }
    
}
catch(Exception $e)
{
    $data['status'] = 'error';
    $data['data']   = $e->getMessage();

    if (empty($data['data']))
    {
        $data['data'] = _('Sorry, operation was not completed due to an unknown error');
    }
}

echo json_encode($data);
exit();
?>