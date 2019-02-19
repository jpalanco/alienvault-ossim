<?php
/**
* ossec_agent.inc
*
* File ossec_agent.inc is used to:n
*   - To manage OSSEC agents
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
* Ossec_agent Class
*
* Class for managing OSSEC agents
*
* @package    ossim-framework\Ossec
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/

class Ossec_agent
{
    /**
    * Configuration path
    *
    */
    const CONF_PATH = 'ossec/agent.conf';


    /**
     * Class constructor
     *
     * This function sets up the class
     *
     */
    public function __construct()
    {

    }


    /**
     * This function lists all OSSEC agents
     *
     * @param string  $sensor_id   Sensor ID (Table alienvault.sensor)
     *
     * @throws Exception  If an error occurred
     *
     * @return object
     */
    public static function get_list($sensor_id)
    {
        $agents = array();

        $client   = new Alienvault_client();
        $response = $client->sensor($sensor_id)->list_ossec_agents();
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $agents = $response['data']['agents'];

        return $agents;
    }


    /**
     * This function gets information about OSSEC agent
     *
     * @param string  $sensor_id   Sensor ID (Table alienvault.sensor)
     * @param string  $agent_id    Agent ID
     *
     * @throws Exception  If an error occurred
     *
     * @return array
     */
    public static function get_info($sensor_id, $agent_id)
    {
        $info = array();

        $client   = new Alienvault_client();
        $response = $client->sensor($sensor_id)->get_ossec_agent($agent_id);
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        if (!empty($response['data']['agent_detail'][0]))
        {
            $info = explode(',', $response['data']['agent_detail'][0]);
        }

        return $info;
    }


    /**
     * This function returns the last IP of OSSEC agent
     *
     * @param string  $sensor_id   Sensor ID (Table alienvault.sensor)
     * @param array   $agent       Array with agent information
     *
     * @throws Exception  If an error occurred
     *
     * @return string
     */
    public static function get_last_ip($sensor_id, $agent)
    {
        $last_ip = (Asset_host_ips::valid_ip($agent['ip_cidr'])) ? $agent['ip_cidr'] : '';

        if (empty($last_ip))
        {
            $agent_name = trim($agent['name']);
            $agent_name = preg_replace("/[;' ]/", '', $agent_name);

            $client   = new Alienvault_client();
            $response = $client->sensor($sensor_id)->get_last_agent_ip($agent_name);
            $response = @json_decode($response, TRUE);

            if (!$response || $response['status'] == 'error')
            {
                $exp_msg = $client->get_error_message($response);

                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
            }
            else
            {
                $last_ip = $response['data']['check'];
            }
        }

        return $last_ip;
    }


    /**
     * This function returns the last scan dates (rootcheck and syscheck) which launched an OSSEC agent
     *
     * @param string   $sensor_id   Sensor ID (Table alienvault.sensor)
     * @param string   $agent_name  Agent name
     *
     * @throws Exception  If an error occurred
     *
     * @return array
     */
    public static function get_last_scans($sensor_id, $agent_name)
    {
        $date = array(
            'syscheck'  => '',
            'rootcheck' => ''
        );

        $client   = new Alienvault_client();
        $response = $client->sensor($sensor_id)->get_last_agent_scans($agent_name);
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        if (!empty($response['data']['check']['syscheck']))
        {
            $date['syscheck'] = gmdate("D M j H:i:s Y", $response['data']['check']['syscheck']);
        }

        if (!empty($response['data']['check']['rootcheck']))
        {
            $date['rootcheck'] = gmdate("D M j H:i:s Y", $response['data']['check']['rootcheck']);
        }

        return $date;
    }


    /**
     * This function gets IDM information about an OSSEC agent
     *
     * @param string   $sensor_id   Sensor ID (Table alienvault.sensor)
     * @param array    $agent_ip   Agent IP
     *
     * @throws Exception  If an error occurred
     *
     * @return array
     */
    public static function get_idm_data($sensor_id, $agent_ip)
    {
        $idm = array();

        $db      = new ossim_db();
        $conn    = $db->connect();

        $host_id = '';
        $params  = array();

        if (Asset_host_ips::valid_ip($agent_ip))
        {
            $sensor_ctxs = Av_sensor::get_ctx_by_id($conn, $sensor_id);

            if (is_array($sensor_ctxs) && !empty($sensor_ctxs))
            {
                $sensor_ctxs = array_keys($sensor_ctxs);

                foreach ($sensor_ctxs as $ctx)
                {
                    $host_ids = Asset_host::get_id_by_ips($conn, $agent_ip, $ctx);

                    if (!empty($host_ids))
                    {
                        $host_id = key($host_ids);
                        break;
                    }
                }
            }

            if (empty($host_id))
            {
                $host_ids = Asset_host::get_id_by_name($conn, $agent_ip);

                if (is_array($host_ids) && count($host_ids) == 1)
                {
                    $host_id = key($host_ids);
                }
            }

            if (!empty($host_id))
            {
                $params = array($host_id);

                $query  = "SELECT hp.value, hp.last_modified, h.hostname, HEX(hi.mac) AS mac, INET6_NTOA(hi.ip) AS ip, HEX(hi.host_id) AS host_id
                    FROM host_properties hp, host h, host_ip hi
                    WHERE h.id = hi.host_id AND hp.host_id = h.id AND hi.host_id = UNHEX(?) AND hp.property_ref = 8 ORDER BY last_modified DESC LIMIT 1";

                $rs = $conn->Execute($query, $params);

                if (!$rs)
                {
                    $db->close();

                    Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
                }

                if (!$rs->EOF)
                {
                    $idm_data = $rs->fields['value'];

                    $c_user      =  explode('@', $idm_data);
                    $user_domain = (!empty($c_user[1]) ? $c_user[0].'@'.$c_user[1] : $c_user[0]);

                    $idm = array(
                        'id'         => $rs->fields['host_id'],
                        'userdomain' => $user_domain,
                        'hostname'   => $rs->fields['hostname'],
                        'ip'         => $rs->fields['ip'],
                        'mac'        => $rs->fields['mac'],
                        'date'       => $rs->fields['last_modified']
                   );
                }
            }
        }

        return $idm;
    }


    /**
     * This function creates a new OSSEC agent
     *
     * @param string  $sensor_id    Sensor ID (Table alienvault.sensor)
     * @param string  $agent_name   Agent name
     * @param string  $agent_ip     Ip address
     * @param string  $asset_id     Asset related to agent
     *
     * @throws Exception  If an error occurred
     *
     * @return array
     */
    public static function create($sensor_id, $agent_name, $agent_ip, $asset_id)
    {
        $new_agent = array();

        $agent_ip = strtolower($agent_ip);
        $agent_ip = ($agent_ip == 'any') ? '0.0.0.0/0' : $agent_ip;

        $client   = new Alienvault_client();
        $response = $client->sensor($sensor_id)->create_ossec_agent($agent_name, $agent_ip, $asset_id);
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        self::log_OSSEC_activity('create',$agent_name,$agent_ip);

        $new_agent = $response['data']['agent_detail'];

        return $new_agent;
    }


    /**
     * This function deletes an OSSEC agent
     *
     * @param string  $sensor_id   Sensor ID (Table alienvault.sensor)
     * @param string  $agent_id    Agent ID
     *
     * @throws Exception  If an error occurred
     *
     * @return boolean
     */
    public static function delete($sensor_id, $agent_id)
    {
    	list($agent_name,$asset) =  array_slice(self::get_info($sensor_id, $agent_id),1,2);
        $client   = new Alienvault_client();
        $response = $client->sensor($sensor_id)->delete_ossec_agent($agent_id);
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        self::log_OSSEC_activity('delete',$agent_name,$asset);

        return TRUE;
    }


    /**
     * This function restarts an OSSEC agent
     *
     * @param string  $sensor_id  Sensor ID (Table alienvault.sensor)
     * @param string  $agent_id   Agent ID
     *
     * @throws Exception  If an error occurred
     *
     * @return boolean
     */
    public static function restart($sensor_id, $agent_id)
    {
        $client   = new Alienvault_client();
        $response = $client->sensor($sensor_id)->restart_ossec_agent($agent_id);
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return TRUE;
    }


    /**
     * This function runs the integrity/rootkit checking on one OSSEC agent
     *
     * @param string  $sensor_id  Sensor ID (Table alienvault.sensor)
     * @param string  $agent_id   Agent ID
     *
     * @throws Exception  If an error occurred
     *
     * @return boolean
     */
    public static function check_integrity($sensor_id, $agent_id)
    {
        $client   = new Alienvault_client();
        $response = $client->sensor($sensor_id)->check_ossec_agent_integrity($agent_id);
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return TRUE;
    }


    /**
     * This function returns the key of OSSEC agent
     *
     * @param string  $sensor_id  Sensor ID (Table alienvault.sensor)
     * @param string  $agent_id   Agent ID
     *
     * @throws Exception  If an error occurred
     *
     * @return string
     */
    public static function get_key($sensor_id, $agent_id)
    {
        $client   = new Alienvault_client();
        $response = $client->sensor($sensor_id)->get_ossec_agent_key($agent_id);
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return $response['data']['agent_key'];
    }


    /**
     * This function launches an OSSEC syscheck
     *
     * @param string   $sensor_id        Sensor ID (Table alienvault.sensor)
     * @param string   $agent_id         Agent ID
     * @param boolean  $only_w_registry  List modified registry entries (Windows only) or modified files for the agent
     *
     * @throws Exception  If an error occurred
     *
     * @return object
     */
    public static function launch_syscheck($sensor_id, $agent_id, $only_w_registry = FALSE)
    {
        $client   = new Alienvault_client();
        $response = $client->sensor($sensor_id)->launch_ossec_syscheck($agent_id, $only_w_registry);
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return $response['data']['stdout'];
    }


    /**
     * This function launches an OSSEC rootcheck
     *
     * @param string  $sensor_id   Sensor ID (Table alienvault.sensor)
     * @param string  $agent_id    Agent ID
     *
     * @throws Exception  If an error occurred
     *
     * @return array
     */
    public static function launch_rootcheck($sensor_id, $agent_id)
    {
        $client   = new Alienvault_client();
        $response = $client->sensor($sensor_id)->launch_ossec_rootcheck($agent_id);
        $response = @json_decode($response, TRUE);

        $data = array();

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $data = $response['data']['rootcheck'];

        return $data;
    }


    /**
     * This function downloads a preconfigured OSSEC Agent
     *
     * @param string  $sensor_id   Sensor ID (Table alienvault.sensor)
     * @param string  $agent_id    Agent ID
     * @param string  $agent_type  [Optional] Agent type (Agent for Windows or Linux)
     *
     * @throws Exception  If an error occurred
     *
     * @return string
     */
    public static function download_agent($sensor_id, $agent_id, $agent_type = 'windows')
    {
        $client   = new Alienvault_client();
        $response = $client->sensor($sensor_id)->download_ossec_agent($agent_id, $agent_type);
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $data = $response['data']['path'];

        return $data;
    }


    /**
     * This function deploys a Windows agent on a remote host
     *
     * @param  string  $sensor_id   Sensor ID (Table alienvault.sensor)
     * @param  array   $d_data      Deployment data  (asset_id, w_ip, w_user, w_password, w_domain, agent_id)
     *
     * @throws Exception  If there is a connection or API error
     *
     * @return  array
     */
    public static function deploy_windows_agent($sensor_id, $d_data)
    {
        $client = new Alienvault_client();

        $response = $client->sensor($sensor_id)->launch_ossec_deploy($d_data['asset_id'], $d_data['w_ip'], $d_data['w_user'], $d_data['w_password'], $d_data['w_domain'], $d_data['agent_id']);
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return $response['data'];
    }


    /**
     * This function checks deployment status (Windows agents)
     *
     * @param  string  $job_id  Job ID
     *
     * @throws Exception  If there is a connection or API error
     *
     * @return array
     */
    public static function check_deployment_status($job_id)
    {
        $client = new Alienvault_client();

        $response = $client->jobs()->get_job_status($job_id);
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        if ($response['data']['job_status'] == 'task-succeeded')
        {
            if ($response['data']['job_result'][0] === TRUE)
            {
                $data = array(
                    'status' => 'success',
                    'data'   => _('HIDS Agent has been deployed')
                );
            }
            else
            {
                preg_match('/.*reason:\s+(.*)/i', $response['data']['job_result'][1], $found);

                $data = array(
                    'status' => 'error',
                    'data'   => sprintf(_('Unable to deploy HIDS agent. %s'), $found[1])
                );
            }
        }
        elseif ($response['data']['job_status'] == 'task-failed' || $response['data']['job_status'] == 'task-revoked')
        {
            $data = array(
                'status' => 'error',
                'data'   => _('Unable to deploy HIDS agent')
            );
        }
        else
        {
            $data = array(
                'status' => 'in_progress',
                'data'   => _('HIDS deployment is running')
            );
        }


        if ($data['status'] == 'error')
        {
            $data['help'] = 'Please check the <span class="bold" id="go_to_mc">Message Center</span> for details';
        }


        return $data;
    }


    /**
     * This function binds an asset with an agent
     *
     * @param string   $sensor_id   Sensor ID (Table alienvault.sensor)
     * @param string   $agent_id    Agent ID
     * @param string   $asset_id    Asset related to agent
     *
     * @return array
     */
    public static function link_to_asset($sensor_id, $agent_id, $asset_id)
    {
        $client = new Alienvault_client();

        $response = $client->sensor($sensor_id)->link_ossec_agent_to_asset($agent_id, $asset_id);
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return $response['data'];
    }


    /**
     * This function returns all agent actions (HTML)
     *
     * @param string  $agent_id   Agent ID
     * @param array   $agent      Array with agent information
     *
     * @return string
     */
    public static function get_actions($agent_id, $agent)
    {
        $path = '/ossim/pixmaps';

        $ip_address = $agent['ip_cidr'];
        $asset_id   = $agent['host_id'];

        $is_local_agent = ($ip_address == '127.0.0.1' || $ip_address == Util::get_default_admin_ip());

        $is_windows_os = FALSE;

        if (valid_hex32($asset_id))
        {
            $db   = new ossim_db();
            $conn = $db->connect();

            $os_filters = array(
                'where' => 'h.id = UNHEX("'.$asset_id.'")
                    AND (host_properties.value LIKE "windows%" OR host_properties.value LIKE "microsoft%")'
            );

            list($os_list, $total_os) = Asset_host_properties::get_property_values($conn, 3, $os_filters);

            $is_windows_os = ($total_os > 0);

            $db->close();
        }
        $actions = array(
            'restart' => array(
                'perms' => (!$is_local_agent),
                'title' => _('Restart Agent'),
                'img'   => "clock.png",
                'id'    => "_restart_##_$agent_id"
            ),
            'integrity' => array(
                'perms' => TRUE,
                'title' => _('Integrity/rootkit checking'),
                'img'   => "tick-circle.png",
                'id'    => "_integrity_##_$agent_id"
            ),
            'extract_key' => array(
                'perms' => (!$is_local_agent),
                'title' => _('Extract Key'),
                'img'   => "key--arrow.png",
                'id'    => "_key_##_$agent_id"
            ),
            'files_modified' => array(
                'perms' => TRUE,
                'title' => _('Modified Files'),
                'img'   => "file.png",
                'id'    => "_file_##_$agent_id"
            ),
            'reg_entries' => array(
                'perms' => (!$is_local_agent && $is_windows_os),
                'title' => _('Modified registry entries'),
                'img'   => "registry.gif",
                'id'    => "_reg_##_$agent_id"
            ),
            'rootcheck' => array(
                'perms' => TRUE,
                'title' => _('Rootcheck'),
                'img'   => "radar.gif",
                'id'    => "_rchk_##_$agent_id"
            ),
            'installer' => array(
                'perms' => (!$is_local_agent && $is_windows_os),
                'title' => _('Download preconfigured agent for Windows'),
                'img'   => "download_windows.png",
                'id'    => "_w_installer_##_$agent_id"
            ),
            'deployment' => array(
                'perms' => (!$is_local_agent && $is_windows_os),
                'title' => $is_windows_os ? _('Automatic HIDS deployment for Windows') : _("Unable to deploy HIDS. Asset must have a Windows OS defined. Please update the asset details and try again."),
                'img'   => "w_installer.png",
                'id'    => "_w_deployment_##_".base64_encode($agent_id.'###'.$asset_id)
            ),
            'link' => array(
                'perms' => (!$is_local_agent),
                'title' => _('Connect agent to an asset'),
                'img'   => "link.png",
                'id'    => "_link_to_asset_##_".base64_encode($agent_id.'###'.$ip_address)
            ),
            'delete' => array(
                'perms' => ($ip_address != '127.0.0.1'),
                'title' => _('Delete Agent'),
                'img'   => "delete-big.png",
                'id'    => "_del_##_$agent_id"
            )
        );

        $action_buttons = '';

        foreach($actions as $a_data)
        {
            if ($a_data['perms'] == TRUE)
            {
                $action_buttons .= "<a id='".$a_data['id']."'><img class='action_icon w16' src='$path/".$a_data['img']."' alt=\"{$a_data['title']}\" data-title=\"{$a_data['title']}\"/></a>";
            }
            else
            {
                $action_buttons .= "<span class='unbind'><img class='action_icon w16' src='$path/".$a_data['img']."' alt=\"{$a_data['title']}\" data-title=\"{$a_data['title']}\"/></span>";
            }
        }

        return $action_buttons;
    }


    /**
     * This function gets the agent configuration (agent.conf)
     *
     * @param string   $sensor_id   Sensor ID (Table alienvault.sensor)
     *
     * @throws Exception  If an error occurred
     *
     * @return array
     */
    public static function get_configuration_file($sensor_id)
    {
        $data = array();

        $client   = new Alienvault_client();
        $response = $client->sensor($sensor_id)->get_agent_config();
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $conf_file = $response['data']['local_path'];

        $data['path'] = $conf_file;
        $data['data'] = @file_get_contents($conf_file);

        if ($data['data'] === FALSE)
        {
            $exp_msg = _('Error! Failed to get the agent configuration file. You do not have enough access privileges');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return $data;
    }


    /**
     * This function sets the new agent configuration (agent.conf)
     *
     * @param string  $sensor_id   Sensor ID (Table alienvault.sensor)
     * @param string  $new_conf    New configuration
     *
     * @throws Exception  If an error occurred
     *
     * @return array
     */
    public static function set_configuration_file($sensor_id, $new_conf)
    {
        $data['status']  = 'success';
        $data['msg']     = _('Agent configuration file updated successfully');
        $data['data']    = $new_conf;

        $conf_file = Ossec_utilities::get_real_path($sensor_id, self::CONF_PATH);

        //Special case:  Disable email notification
        $pattern = '/<\s*email_notification\s*>\s*yes\s*<\s*\/email_notification\s*>/';

        if (preg_match($pattern, $new_conf))
        {
            $new_conf = preg_replace($pattern, '<email_notification>no</email_notification>', $new_conf);

            $system_name = (Session::is_pro()) ? 'AlienVault' : 'OSSIM';
            $warning_msg = _('HIDS email notification is not supported. Please, use '.$system_name.' email notifications instead').'.';

            $data['status'] = 'warning';
            $data['msg']    = _('Agent configuration file updated with warnings:').'<br/><br/>'. $warning_msg;
            $data['data']   = $new_conf;
        }

        if (@file_put_contents($conf_file, $new_conf, LOCK_EX) === FALSE)
        {
            $exp_msg = _('Error! Failed to set Agent configuration file. You do not have enough access privileges');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }


        $client   = new Alienvault_client();
        $response = $client->sensor($sensor_id)->set_agent_config();
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return $data;
    }

    public static function log_OSSEC_activity ($action, $agent_name,$agent_ip){

        $user_name    = Session::get_session_user();
        $db      = new ossim_db();
        $conn    = $db->connect();
        $host_name = '';

        try {
            $query = " select hostname  from host where id=(select host_id  from host_ip where ip=INET6_ATON(?))";
            $rs = $conn->Execute($query, $agent_ip);
            $host_name = $rs->fields['hostname'];
        }catch (Exception $e) {
            Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
        }


        switch ($action) {
            case "create":
                Log_action::log(100,[$user_name,$agent_name,$host_name,$agent_ip]);
                break;
            case "delete":
                Log_action::log(102,[$user_name,$agent_name,$host_name,$agent_ip]);
                break;
        }
    }
}

/* End of file ossec_agent.inc */
/* Location: ../include/classes/ossec_agent.inc */
