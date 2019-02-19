<?php

/**
 * system_notifications.inc
 *
 * File system_notifications.inc is used to:
 *   - Dashboard -> Deployment Status -> Notification Center tab
 *
 *
 * License:
 *
 * Copyright (c) 2003-2006 ossim.net
 * Copyright (c) 2007-2015 AlienVault
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
 * @package    ossim-framework\Various
 * @autor      AlienVault INC
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt
 * @copyright  2003-2006 ossim.net
 * @copyright  2007-2015 AlienVault
 * @link       https://www.alienvault.com/
 */

require_once 'av_init.php';


/**
 * System Notifications Class
 *
 * Class for managing the System Status messages
 *
 * @package    ossim-framework\Various
 * @autor      AlienVault INC
 * @copyright  2007-2015 AlienVault
 * @link       https://www.alienvault.com/ AlienVault web page
 */
class System_notifications
{
    /**
     * API pointer
     *
     * @var object
     *
     */
    private $api;


    /**
     * Class constructor
     *
     * This function sets up the class
     *
     */
    public function __construct()
    {
        $this->api = new Alienvault_client();
    }


    /**
     * This function gets all system messages
     *
     * @param  array  $filters     [Optional] Filter parameters
     * @param  array  $pagination  [Optional] Pagination parameters
     *
     * @throws Exception  If an error occurred
     *
     * @return array
     */
    public function get_status_messages($filters = array(), $pagination = array())
    {
        $response = $this->api->data('')->get_status_messages($filters, $pagination);
        $response = @json_decode($response, TRUE);
        $this->check_user_error($response);
        $messages = array();
        $total    = $response['data']['total'];

        foreach($response['data']['messages'] as $msg)
        {
            $messages[$msg['id']] = $this->format_message($msg);
        }

        return array($messages, $total);
    }


    public function check_user_error($response) {
        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $this->api->get_error_message($response);
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
        return true;
    }

    public function get_status_message($msg_id)
    {
        $response = $this->api->data('')->get_status_message($msg_id);
        $response = @json_decode($response, TRUE);
        if (!$response || $response['status'] == 'error') {
            return false;
        }
        return $this->format_message($response['data']);
    }

    public function verify_last_update_notification() {
        if (!$update_res = Util::get_last_update_log_result()) {
            return false;
        }
        $mtime = Util::get_last_update_log_time();
        $message_id = strtoupper(md5($mtime+$update_res));
        if (!Util::check_update_message_lock_present($message_id)) {
            return false;
        }
        if ($this->get_status_message($message_id)) {
            return false;
        }
        $response = $this->api->data('')->save_status_message(array(
               'message_id'	=> Util::uuid_format($message_id),
               'msg_type'	=> "Update",
               'msg_title'	=> "Update error - unable to complete for SYSTEM_NAME",
               'msg_description'=> "Message: Unable to complete update for SYSTEM_NAME. $update_res ",
               'msg_actions'	=> "Please check your network settings and try again. If you are still unable to update your system, please contact AlienVault Support.",
               'msg_source'	=> "external",
               'component_type'	=> "external",
               'created'	=> gmdate("Y-m-d H:i:s",$mtime),
               'additional_info'=> '{"system_id": "'.Util::get_system_uuid().'"}'
        ));
        $response = @json_decode($response, TRUE);
        if (!$response || $response['status'] == 'error') {
            return false;
        }
        return true;
    }


    /**
     * This function get detail for a given status UUID
     *
     * @param  string  $msg_id  Message id
     *
     * @throws Exception  If an error occurred
     *
     * @return  array
     */
    public function get_status_message_detail($msg_id)
    {
        $response = $this->api->data('')->get_status_message_detail($msg_id);
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $this->api->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return array($response['data']['messages'][0]);
    }


    /**
     * This function gets messages stats
     *
     * @param  array  $filters  [Optional] Filter parameters
     *
     * @return array
     */
    public function get_status_messages_stats($filters = array())
    {
        $response = $this->api->data('')->get_status_messages_stats($filters);
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $this->api->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return array($response['data']['stats'], $response['data']['total']);
    }


    /**
     * This function set a suppress or view flag for a status message
     *
     * @param  string  $status_message_id  Status message UUID
     * @param  array   $flags              Flags params
     *
     * @throws Exception  If an error occurred
     * @return boolean
     */
    public function set_status_message($status_message_id, $flags)
    {
        $response = $this->api->data('')->set_status_message($status_message_id, $flags);
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $this->api->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return TRUE;
    }


    /**
     * This function return a formatted message
     *
     * @param  string $message Message
     *
     * @return  string
     */
    private function format_message($message)
    {
        $date            = gmdate('Y-m-d H:i:s', strtotime($message['creation_time']) + (3600 * Util::get_timezone()));
        $component_id    = strtoupper(str_replace('-', '', $message['component_id']));
        $message_id      = strtoupper(str_replace('-', '', $message['message_id']));
        $component_type  = $message['component_type'];
        $additional_info = $message['additional_info'];
        // Format creation_time
        $message['creation_time'] = $date;

        // Format title
        if (is_array($additional_info))
        {
            if (array_key_exists('00000000000000000000000000010024', $additional_info))
            {
                $target_key = $additional_info['00000000000000000000000000010024'];

                if (array_key_exists('over_assets', $target_key) && $target_key['over_assets'])
                {
                    $message['message_title'] = str_replace(array('NUM_ASSETS'), array($target_key['exceeding_assets']), $message['message_title']);
                }
            }

            //HIDS AGENTS
            if (preg_match('/^0000000000000000000000000001003[1-3]$/', $message_id))
            {
                //Agent description
                $agent_descr = _("Agent ID:"). " " . $additional_info['agent_id'];

                if (!empty($additional_info['agent_name']) && !empty($additional_info['agent_id']))
                {
                    $agent_descr = _("Agent name:") . " " . $additional_info['agent_name'] . "\n" . _("Agent ID:"). " " . $additional_info['agent_id'];
                }

                $agent_descr_key = (!empty($agent_descr)) ? 'AGENT_DESCR' : 'AGENT_DESCR ';

                //Asset description
                $asset_descr = $additional_info['asset_id'];

                if (!empty($additional_info['asset_name']))
                {
                    $asset_descr = $additional_info['asset_name'];
                }

                $asset_descr_key = (!empty($asset_descr)) ? 'ASSET_DESCR' : 'ASSET_DESCR ';

                $message['message_title'] = str_replace($asset_descr_key, $asset_descr, $message['message_title']);

                //Asset not linked
                $additional_info['not_linked_assets'] = intval($additional_info['not_linked_assets']);

                if ($additional_info['not_linked_assets'] > 0)
                {
                    $message['message_title'] = str_replace('NUM_ASSETS', $additional_info['not_linked_assets'], $message['message_title']);
                }
            }
        }

        // Format message_description
        $message['message_description'] = str_replace(array('TIMESTAMP'), array($date), $message['message_description']);


        //REBOOT NEEDED
        if ($message_id == '00000000000000000000000000010027')
        {
            $db   = new Ossim_db();
            $conn = $db->connect();

            try
            {
                $system_info = Av_center::get_system_info_by_id($conn, $component_id);

                $component_name = empty($system_info['data']['name'])       ? 'Unknown' : $system_info['data']['name'];
                $component_ip   = empty($system_info['data']['current_ip']) ? 'Unknown' : $system_info['data']['current_ip'];
            }
            catch (Exception $e)
            {
                ;
            }

            $db->close();

            $message['message_description'] = str_replace('HOSTNAME',   $component_name, $message['message_description']);
            $message['message_description'] = str_replace('IP_ADDRESS', $component_ip, $message['message_description']);
        }


        // Format based in additional info
        if (is_array($additional_info))
        {
            //HIDS AGENTS
            if (preg_match('/^0000000000000000000000000001003[1-3]$/', $message_id))
            {
                //Agent description
                if (isset($agent_descr_key))
                {
                    $message['message_description'] = str_replace($agent_descr_key, $agent_descr, $message['message_description']);
                }

                //Asset description
                if (isset($asset_descr_key))
                {
                    $message['message_description'] = str_replace($asset_descr_key, $asset_descr, $message['message_description']);
                }

                //Asset not deployed
                if (preg_match('/ERROR/', $message['message_description']) && !empty($additional_info['deploy_status']))
                {
                    $message['message_description'] = str_replace('ERROR', $additional_info['deploy_status'], $message['message_description']);
                }

                //Asset not linked
                $additional_info['not_linked_assets'] = intval($additional_info['not_linked_assets']);

                if ($additional_info['not_linked_assets'] > 0)
                {
                    $message['message_description'] = str_replace('NUM_ASSETS', $additional_info['not_linked_assets'], $message['message_description']);
                }
            }

            // PLUGINS CHANGED
            if (array_key_exists('plugins_changed', $additional_info))
            {
                $plugins_changed_names = array();

                foreach ($additional_info['plugins_changed'] as $plugin_changed)
                {
                    $plugins_changed_names[] = array_pop(explode('/', $plugin_changed));
                }

                // Show plugin names in message title and message description and plugin path in message description
                $message['message_title'] = str_replace(
                    array('PLUGINS_CHANGED'), array(implode(', ', $plugins_changed_names)), $message['message_title']);

                $message['message_description'] = str_replace(
                    array('PATH_PLUGINS_CHANGED', 'PLUGINS_CHANGED'), array(implode(', ', $additional_info['plugins_changed']), implode(', ', $plugins_changed_names)), $message['message_description']);
            }

            // PLUGINS_REMOVED
            if (array_key_exists('plugins_removed', $additional_info))
            {
                $plugins_removed_names = array();

                foreach ($additional_info['plugins_removed'] as $plugin_changed)
                {
                    $plugins_removed_names[] = array_pop(explode('/', $plugin_changed));
                }

                // Show plugin names in message title and message description and plugin path in message description
                $message['message_title'] = str_replace(
                    array('PLUGINS_REMOVED'), array(implode(', ', $plugins_removed_names)), $message['message_title']);

                $message['message_description'] = str_replace(
                    array('PATH_PLUGINS_REMOVED', 'PLUGINS_REMOVED'), array(implode(', ', $additional_info['plugins_removed']), implode(', ', $plugins_removed_names)), $message['message_description']);
            }

            // RSYSLOG_FILES_REMOVED
            if (array_key_exists('rsyslog_files_removed', $additional_info))
            {
                $rsyslog_files_removed_names = array();

                foreach ($additional_info['rsyslog_files_removed'] as $plugin_changed)
                {
                    $rsyslog_files_removed_names[] = array_pop(explode('/', $plugin_changed));
                }

                // Show plugin names in message title and message description and plugin path in message description
                $message['message_title'] = str_replace(
                    array('RSYSLOG_FILES_REMOVED'), array(implode(', ', $rsyslog_files_removed_names)), $message['message_title']);

                $message['message_description'] = str_replace(
                    array('PATH_RSYSLOG_FILES_REMOVED', 'RSYSLOG_FILES_REMOVED'), array(implode(', ', $additional_info['rsyslog_files_removed']), implode(', ', $rsyslog_files_removed_names)), $message['message_description']);
            }

            // RSYSLOG_FILES_CHANGED
            if (array_key_exists('rsyslog_files_changed', $additional_info))
            {
                $rsyslog_files_changed_names = array();

                foreach ($additional_info['rsyslog_files_changed'] as $plugin_changed)
                {
                    $rsyslog_files_changed_names[] = array_pop(explode('/', $plugin_changed));
                }

                // Show plugin names in message title and message description and plugin path in message description
                $message['message_title'] = str_replace(
                    array('RSYSLOG_FILES_CHANGED'), array(implode(', ', $rsyslog_files_changed_names)), $message['message_title']);

                $message['message_description'] = str_replace(
                    array('PATH_RSYSLOG_FILES_CHANGED', 'RSYSLOG_FILES_CHANGED'), array(implode(', ', $additional_info['rsyslog_files_changed']), implode(', ', $rsyslog_files_changed_names)), $message['message_description']);
            }

            // SYSTEM_NAME
            if (array_key_exists('system_id', $additional_info))
            {
                $db   = new Ossim_db();
                $conn = $db->connect();
                try
                {
                    $system_info = Av_center::get_system_info_by_id($conn, $additional_info['system_id']);
                    if (!empty($system_info['data']['name']))
                    {
                        $system_name = $system_info['data']['name'];
                    }
                }
                catch (Exception $e)
                {
                    ;
                }

                $db->close();
            }
        }

        $system_name = empty($system_name) ? 'Unknown' : $system_name;

        $message['message_description'] = str_replace(array('SYSTEM_NAME'), array($system_name), $message['message_description']);
        $message['message_title'] = str_replace(array('SYSTEM_NAME'), array($system_name), $message['message_title']);

        // Format message_actions
        if ($component_type == 'net')
        {
            $component_type = 'network';
        }

        if ($component_type == 'host')
        {
            $component_type = 'asset';
        }

        $message['message_actions'] = str_replace(
            array('AV_PATH', 'COMPONENT_ID', 'COMPONENT_TYPE'), array(AV_MAIN_PATH, $component_id, $component_type), $message['message_actions']);

        // Format message_alternative_actions
        $message['message_alternative_actions'] = str_replace(
            array('AV_PATH', 'COMPONENT_ID', 'COMPONENT_TYPE'), array(AV_MAIN_PATH, $component_id, $component_type), $message['message_alternative_actions']);

        return $message;
    }
}

/* End of file system_notifications.inc */
/* Location: ../include/classes/system_notifications.inc */
