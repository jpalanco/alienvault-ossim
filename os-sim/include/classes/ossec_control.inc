<?php
/**
* ossec_control.inc
*
* File ossec_control.inc is used to:
*   - To manage OSSEC
*
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
* @package    ossim-framework\Ossec
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/

/**
* Ossec_control Class
*
* Class for managing OSSEC
*
* @package    ossim-framework\Ossec
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/

class Ossec_control
{
    /**
     * Class constructor
     *
     * This function sets up the class
     */
    public function __construct()
    {

    }


    /**
     * This function executes an OSSEC action
     *
     * @param string   $sensor_id   Sensor ID (Table alienvault.sensor)
     * @param string   $action      OSSEC action to execute
     *
     * @throws Exception  If an error occurred
     *
     * @return array
     */
    public static function execute_action($sensor_id, $action)
    {
        $client = new Alienvault_client();

        $response = $client->sensor($sensor_id)->ossec_control($action);
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return $response['data'];
    }


    /**
     * This function returns OSSEC control buttons and status (HTML)
     *
     * @return array
     */
    public static function get_html_status($status_data)
    {
        $system_action    = "<span class='not_available'>"._('HIDS Service Information not available')."</span><br/><br/>
                             <input type='button' id='system_start' class='small' value='"._('Start')."'/>";
        $syslog_action    = "<span class='not_available'>"._('Syslog Information')."<br/>"._('not available')."</span><br/><br/>";
        $agentless_action = "<span class='not_available'>"._('Agentless Information')."<br/>"._('not available')."</span><br/><br/>";
        $debug_action     = "<span class='not_available'>"._('Debug Information')."<br/>"._('not available')."</span><br/><br/>";

        $response = array(
            'stdout'  => "<div style='margin: 20px auto; text-align:center;'>"._('Information not available')."</div>",
            'buttons' => array(
                'system'    => $system_action,
                'syslog'    => $syslog_action,
                'agentless' => $agentless_action,
                'debug'     => $debug_action
             )
        );


        if (!empty($status_data['raw_output_status']))
        {
            $pattern     = array('/is running/', '/already running/', '/Completed/', '/Started/', '/not running/', '/Killing/', '/Stopped/');

            $replacement = array('<span style="font-weight: bold; color:#15B103;">is running</span>', 
                                 '<span style="font-weight: bold; color:#15B103;">already running</span>',
                                 '<span style="font-weight: bold; color:#15B103;">Completed</span>',
                                 '<span style="font-weight: bold; color:#555555;">Started</span>',
                                 '<span style="font-weight: bold; color:#E54D4D;">not running</span>',
                                 '<span style="font-weight: bold; color:#555555;">Killing</span>',
                                 '<span style="font-weight: bold; color:#E54D4D;">Stopped</span>');

            $st_output          = str_replace("\n", '<br/>', $status_data['raw_output_status']);

            $response['stdout'] = preg_replace($pattern, $replacement, $st_output);
        }

        //Ossec Status by module
        switch ($status_data['service_status']['service'])
        {
            case 'UP':
                $system_action   = "<span class='running'>"._('HIDS service is UP')."</span><br/><br/>
                                    <input type='button' id='system_stop' class='av_b_secondary small' value='"._('Stop')."'/>";
            break;

            case 'DOWN':
                $system_action    = "<span class='not_running'>"._('HIDS service is DOWN')."</span><br/><br/>
                                     <input type='button' id='system_start' class='small' value='"._('Start')."'/>";
            break;
        }


        $system_action .= "<input type='button' id='system_restart' style='margin-left: 5px;' class='small' value='"._('Restart')."'/>";

        switch ($status_data['service_status']['syslog'])
        {
            case 'UP':
                $syslog_action   = "<span class='running'>Client-syslog "._('is running')."</span><br/><br/>
                                    <input type='button' id='cs_disable' class='av_b_secondary small' value='"._('Disable')."'/>";
            break;

            case 'DOWN':
                $syslog_action   = "<span class='not_running'>Client-syslog "._('is NOT running')."</span><br/><br/>
                                    <input type='button' id='cs_enable' class='small' value='"._('Enable')."'/>"; 
            break;
        }


        switch ($status_data['service_status']['agentless'])
        {
            case 'UP':
                $agentless_action   = "<span class='running'>Agentless "._('is running')."</span><br/><br/>
                                       <input type='button' id='al_disable' class='av_b_secondary small' value='"._('Disable')."'/>";
            break;

            case 'DOWN':
                $agentless_action   = "<span class='not_running'>Agentless "._('is NOT running')."</span><br/><br/>
                                       <input type='button' id='al_enable' class='small' value='"._('Enable')."''/>";
            break;
        }


        switch ($status_data['service_status']['debug'])
        {
            case 'UP':
                $debug_action   = "<span class='running'>Debug "._('is enabled')."</span><br/><br/>
                                   <input type='button' id='dbg_disable' class='av_b_secondary small' value='"._('Disable')."'/>";
            break;

            case 'DOWN':
                $debug_action   = "<span class='not_running'>Debug "._('is disabled')."</span><br/><br/>
                                   <input type='button' id='dbg_enable' class='small' value='"._('Enable')."''/>";
            break;
        }

        $response['buttons'] = array(
            'system'    => $system_action,
            'syslog'    => $syslog_action,
            'agentless' => $agentless_action,
            'debug'     => $debug_action
        );

        return $response;
    }


    /**
     * This function returns information about OSSEC logs
     *
     * @param string   $sensor_id   Sensor ID (Table alienvault.sensor)
     * @param string   $log_type    Log source (ossec or alert)
     * @param integer  $num_lines  [Optional] Maximum number of lines
     *
     * @throws Exception  If an error occurred
     *
     * @return string
     */
    public static function get_logs($sensor_id, $log_type, $num_lines = 50)
    {
        $client   = new Alienvault_client();
        $response = $client->sensor($sensor_id)->get_ossec_logs($log_type, $num_lines);
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return implode('<br/>', $response['data']['lines']);
    }
}

/* End of file ossec_control.inc */
/* Location: ../include/classes/ossec_control.inc */
