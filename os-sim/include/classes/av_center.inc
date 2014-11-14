<?php
/**
 * av_center.inc
 *
 * File av_center.inc is used to:
 *   - To manage Alienvault Systems using Alienvault API
 *
 *
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
 * @package     ossim-framework\Alienvaut_Center
 * @author      AlienVault INC
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt
 * @copyright   2003-2006 ossim.net
 * @copyright   2007-2014 AlienVault
 * @link        https://www.alienvault.com/
 */

/**
 * Function list:
 *
 *   - delete_component($conn, $component_id, $component_type = 'sensor')
 *   - delete_system($system_id)
 *   - delete_orphan_systems()
 *   - add_system($system_id, $password)
 *   - get_system_id_by_component($conn, $component_id)
 *   - get_component_id_by_system($conn, $system_id, $component_type = 'sensor')
 *   - get_software_updates()
 *   - is_system_updated($system_id)
 *   - get_current_version($system_id)
 *   - get_release_info($system_id)
 *   - get_system_info_by_ip($conn, $admin_ip)
 *   - get_system_info_by_id($conn, $system_id)
 *   - get_avc_list($conn)
 *   - get_system_status($system_id, $status_type, $no_cache = FALSE)
 *   - get_config_data(system_id, $type, $cache)
 *   - set_config_data(system_id, $data, $type)
 *   - get_general_configuration(system_id)
 *   - set_general_configuration(system_id, $data)
 *   - get_network_configuration(system_id)
 *   - set_network_configuration(system_id, $data)
 *   - get_sensor_configuration(system_id)
 *   - set_sensor_configuration(system_id, $data)
 *   - get_detectors_status(system_id)
 *   - update_av_system($system_id)
 *   - update_av_feed($system_id)
 *   - get_current_task(system_id, $filter = '')
 *   - set_task_job_id($system_id, $task_type, $job_id)
 *   - get_task_job_id($system_id, $task_type)
 *   - get_task_status($system_id, $task_type)
 *   - get_update_status(system_id, $max_attempts = 10)
 *   - get_packages_installed(system_id)
 *   - get_packages_pending(system_id, $no_cache = FALSE)
 *   - get_available_logs()
 *   - get_log_file(system_id, $log_url, $num_rows)
 */


/**
 * Av_center Class
 *
 * Class for managing Alienvault Components using Alienvault Center
 *
 * @package     ossim-framework\Alienvaut_Center
 * @author      AlienVault INC
 * @copyright   2007-2014 AlienVault
 * @link        https://www.alienvault.com/ Alienvault web page
 */
class Av_center
{
    /**************
     * ATTRIBUTES *
     **************/

    public static $base_path = '/var/alienvault';


    /***********
     * METHODS *
     ***********/

    /**
     * Class constructor
     *
     * This method sets up the class
     */
    public function __construct()
    {
        ;
    }


    /**
     * This method deletes an alienvault component from system registry
     *
     * @param object $conn            Database access object
     * @param string $component_id    Component ID (Sensor ID or Server ID)
     * @param string $component_type  [Optional] Component type (server or sensor)
     *
     * @throws Exception  If a connection error occurred
     *
     * @return boolean
     */
    public static function delete_component($conn, $component_id, $component_type = 'sensor')
    {
        Ossim_db::check_connection($conn);

        if (valid_hex32($component_id, FALSE))
        {
            $params = array($component_id);

            if ($component_type == 'sensor')
            {
                $query = 'UPDATE alienvault.system SET sensor_id = NULL WHERE sensor_id = UNHEX(?)';
            }
            elseif ($component_type == 'server')
            {
                $query = 'UPDATE alienvault.system SET server_id = NULL WHERE server_id = UNHEX(?)';
            }
            else
            {
                return FALSE;
            }

            $rs = $conn->Execute($query, $params);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }

            return TRUE;
        }

        return FALSE;
    }


    /**
     * This method deletes an alienvault system
     *
     * @param string $system_id  System ID
     *
     * @throws Exception  If an error occurred
     *
     * @return boolean
     */
    public static function delete_system($system_id)
    {
        $client = new Alienvault_client();

        //Change timeout
        $client_conn = $client->get_conn();
        $client_conn->set_timeout(300);
        $client->set_conn($client_conn);

        $response = $client->system($system_id)->delete_system();
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return $response['data']['message'];
    }


    /**
     * This method deletes all systems without sensor or server associated
     *
     * @param object $conn  Database access object
     *
     * @throws Exception  If an error occurred
     *
     * @return void
     */
    public static function delete_orphan_systems($conn)
    {
        Ossim_db::check_connection($conn);

        $query = 'SELECT HEX(id) AS system_id FROM alienvault.system WHERE server_id is NULL AND sensor_id is NULL';

        $rs = $conn->Execute($query);

        if (is_object($rs) && $rs != NULL)
        {
            while (!$rs->EOF)
            {
                try
                {
                    // Delete the whole system
                    $system_id = $rs->fields['system_id'];

                    self::delete_system($system_id);
                }
                catch (Exception $e)
                {
                    ;
                }

                $rs->MoveNext();
            }
        }
    }


    /**
     * This method adds an alienvault system
     *
     * @param string $system_id  System ID
     * @param string $password   System password
     *
     * @throws Exception  If an error occurred
     *
     * @return boolean
     */
    public static function add_system($system_id, $password)
    {
        $client   = new Alienvault_client();
        $response = $client->system($system_id)->authenticate_system($password);
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return $response['data'];
    }


    /**
     * This method returns System ID (canonical and non-canonical) from a component (Sensor or Server)
     *
     * @param object $conn          Database access object
     * @param string $component_id  Component ID (Sensor or Server ID)
     *
     * @throws Exception  If a connection error occurred
     *
     * @return array
     */
    public static function get_system_id_by_component($conn, $component_id)
    {
        Ossim_db::check_connection($conn);

        $ids = array();

        $params = array($component_id, $component_id);

        $query = "SELECT HEX(id) AS system_id FROM alienvault.system WHERE sensor_id = UNHEX(?) OR server_id = UNHEX(?)";

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
            $ids = array(
                'non-canonical' => $rs->fields['system_id'],
                'canonical'     => Util::uuid_format($rs->fields['system_id'])
            );
        }

        return $ids;
    }


    /**
     * This method returns Component ID (canonical and non-canonical) from a system
     *
     * @param object $conn            Database access object
     * @param string $system_id       System ID
     * @param string $component_type  [Optional] Component type ID (server or sensor) to return
     *
     * @throws Exception  If a connection error occurred
     *
     * @return array
     */
    public static function get_component_id_by_system($conn, $system_id, $component_type = 'sensor')
    {
        Ossim_db::check_connection($conn);

        $ids       = array();
        $system_id = str_replace('-', '', $system_id);

        $params = array($system_id);

        if ($component_type == 'sensor')
        {
            $query = 'SELECT HEX(sensor_id) AS system_id FROM alienvault.system WHERE id = UNHEX(?)';
        }
        elseif ($component_type == 'server')
        {
            $query = 'SELECT HEX(server_id) AS system_id FROM alienvault.system WHERE id = UNHEX(?)';
        }
        else
        {
            return $ids;
        }

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if (!$rs->EOF)
        {
            $ids = array(
                'non-canonical' => $rs->fields['system_id'],
                'canonical'     => Util::uuid_format($rs->fields['system_id'])
            );
        }

        return $ids;
    }


    /**
     * This method returns true if there is some upgrade pending (Check all systems)
     *
     * @return boolean
     */
    public static function get_software_updates()
    {
        $upgrade_pending = FALSE;

        try
        {
            $filters = array(
                'level'      => 'notification',
                'message_id' => 10,
            );

            $status = new System_status();

            list($message_list, $total_messages) = $status->get_status_messages($filters);

            if ($total_messages > 0)
            {
                $upgrade_pending = TRUE;
            }
        }
        catch(Exception $e)
        {
            ;
        }

        return $upgrade_pending;
    }


    /**
     * This method returns whether system is updated or not
     *
     * @param string $system_id  System ID
     *
     * @return boolean|NULL Returns True if the system is updated, otherwise it returns FALSE or NULL if there is an error
     */
    public static function is_system_updated($system_id)
    {
        try
        {
            $st = self::get_system_status($system_id, 'software');

            $is_updated = $st['packages']['pending_updates'];
        }
        catch(Exception $e)
        {
            $is_updated = NULL;
        }
        
        return $is_updated;
     }
     
     
    /**
     * This method returns current software version
     *
     * @param string $system_id  System ID
     *
     * @return string|NULL
     */
    public static function get_current_version($system_id)
    {
        try
        {
            $st = self::get_system_status($system_id, 'software');

            $current_version = $st['current_version'];
        }
        catch(Exception $e)
        {
            $current_version = NULL;
        }

        return $current_version;
     }



    /**
     * This method returns information about release notes
     *
     * @param string $system_id  System ID
     *
     * @return array
     */
    public static function get_release_info($system_id)
    {
        $res = NULL;

        $path = self::$base_path."/release_info";

        if (file_exists($path))
        {
            $info = file($path);

            if (count($info) > 3)
            {
                //Current version
                $c_version = self::get_current_version($system_id);

                //New version
                $new_version = array_shift($info);
                $new_version = preg_replace('/version:\s*/i', '', $new_version);
                $new_version = trim($new_version);

                // Test if current version will be upgraded
                if ($c_version != '' && $new_version != '' && Util::compare_av_version($c_version, $new_version) == -1)
                {
                    $res['version']     = $new_version;
                    $res['type']        = array_shift($info);
                    $res['type']        = (preg_match('/minor/i', $res['type'])) ? _('Patch') : _('Upgrade');
                    $res['description'] = $info;
                }
            }
        }

        return $res;
    }


    /**
     * This method returns the system information by System ID
     *
     * @param object $conn       Database access object
     * @param string $system_id  System ID
     *
     * @throws Exception  If a connection error occurred
     *
     * @return array
     */
    public static function get_system_info_by_id($conn, $system_id)
    {
        Ossim_db::check_connection($conn);

        $res['status'] = 'error';
        $res['data']   = '';

        $query = "SELECT hex(id) AS system_id, HEX(sensor_id) AS sensor_id, HEX(server_id) AS server_id,
            INET6_NTOP(admin_ip) AS admin_ip, ifnull(INET6_NTOP(vpn_ip),'') AS vpn_ip, name, profile
            FROM alienvault.system 
            WHERE id = UNHEX(?)";

        $conn->SetFetchMode(ADODB_FETCH_ASSOC);

        $rs = $conn->Execute($query, str_replace('-', '', $system_id));

        if (!$rs)
        {
            $res['data'] = $conn->ErrorMsg();
        }
        else
        {
            if (is_array($rs->fields) && !empty($rs->fields))
            {
                $rs->fields['system_id'] = Util::uuid_format($rs->fields['system_id']);
                $rs->fields['profile']   = strtolower($rs->fields['profile']);
                $res['status']           = 'success';
                $res['data']             = $rs->fields;
            }
        }

        return $res;
    }


    /**
     * This method returns the system information by admin IP
     *
     * @param object $conn      Database access object
     * @param string $admin_ip  Admin ip
     *
     * @throws Exception  If a connection error occurred
     *
     * @return array
     */
    public static function get_system_info_by_ip($conn, $admin_ip)
    {
        Ossim_db::check_connection($conn);

        $res['status'] = 'error';
        $res['data']   = FALSE;

        $query = "SELECT hex(id) AS system_id, HEX(sensor_id) AS sensor_id, HEX(server_id) AS server_id,
            INET6_NTOP(admin_ip) AS admin_ip, ifnull(INET6_NTOP(vpn_ip),'') AS vpn_ip, name, profile
            FROM alienvault.system
            WHERE admin_ip = INET6_PTON(?) OR vpn_ip = INET6_PTON(?)";


        $conn->SetFetchMode(ADODB_FETCH_ASSOC);

        $rs = $conn->Execute($query, array($admin_ip, $admin_ip));

        if (!$rs)
        {
            $res['data'] = $conn->ErrorMsg();
        }
        else
        {
            if (is_array($rs->fields) && !empty($rs->fields))
            {
                $rs->fields['system_id'] = Util::uuid_format($rs->fields['system_id']);
                $rs->fields['profile']   = strtolower($rs->fields['profile']);
                $res['status']           = 'success';
                $res['data']             = $rs->fields;
            }
        }

        return $res;
    }


    /**
     * This method returns the avc list
     *
     * @param object $conn  Database access object
     *
     * @throws Exception  If a connection error occurred
     *
     * @return array
     */
    public static function get_avc_list($conn)
    {
        Ossim_db::check_connection($conn);

        $res['status'] = 'error';
        $res['data']   = NULL;


        $query = "SELECT HEX(id) AS system_id, HEX(sensor_id) AS sensor_id, HEX(server_id) AS server_id, INET6_NTOP(admin_ip) AS admin_ip, 
            ifnull(INET6_NTOP(vpn_ip),'') AS vpn_ip, name, profile
            FROM alienvault.system ORDER BY name";

        $conn->SetFetchMode(ADODB_FETCH_ASSOC);

        $rs = $conn->Execute($query);

        if (!$rs)
        {
            $res['data'] = Util::htmlentities($conn->ErrorMsg(), ENT_QUOTES);
        }
        else
        {
            $res['status'] = 'success';

            while (!$rs->EOF)
            {
                $rs->fields['system_id']               = Util::uuid_format($rs->fields['system_id']);
                $rs->fields['profile']                 = strtolower($rs->fields['profile']);
                $res['data'][$rs->fields['system_id']] = $rs->fields;

                $rs->MoveNext();
            }
        }

        return $res;
    }


    /**
     * This method returns a JSON object with setup data from a given system (Alienvault Component)
     *
     * @param string  $system_id  System UUID
     * @param string  $type       [Optional] Configuration Type (general or alienvault)
     * @param boolean $cache      [Optional] Use cached information
     *
     * @throws Exception  If an error occurred
     *
     * @return array
     */
    public static function get_config_data($system_id, $type = 'general', $cache = TRUE)
    {
        if ($type != 'general' && $type != 'alienvault')
        {
            $res['status'] = 'error';
            $res['data']   = _('Configuration Type not allowed');

            return $res;
        }

        $client = new Alienvault_client();

        if ($type == 'general')
        {
            $response = $client->system($system_id)->get_config_general();
        }
        else
        {
            $response = $client->system($system_id)->get_config_alienvault();
        }

        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $res['status'] = 'error';
            $res['data']   = $client->get_error_message($response);
        }
        else
        {
            $res['status'] = 'success';
            $res['data']   = $response['data'];
        }

        return $res;
    }


    /**
     * This method sets with setup data from a given system (Alienvault Component)
     *
     * @param string $system_id  System ID
     * @param array  $data       Data to set
     * @param string $type       [Optional] Configuration Type (general or alienvault)
     *
     * @throws Exception  If an error occurred
     *
     * @return array
     */
    public static function set_config_data($system_id, $data, $type = 'general')
    {
        if ($type != 'general' && $type != 'alienvault')
        {
            $res['status'] = 'error';
            $res['data']   = _('Configuration Type not allowed');

            return $res;
        }

        $client = new Alienvault_client();

        if ($type == 'general')
        {
            $response = $client->system($system_id)->set_config_general($data);
        }
        else
        {
            $response = $client->system($system_id)->set_config_alienvault($data);
        }

        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $res['status'] = 'error';
            $res['data']   = sprintf(_('System error! Configuration not applied [%s]'), $client->get_error_message($response));
        }
        else
        {
            $res['status'] = 'success';
            $res['data']   = _('Configuration updated successfully');
        }

        return $res;
    }


    /**
     * This method returns the system status
     *
     * @param string  $system_id    System ID
     * @param string  $status_type  Status type in ('general', 'network', 'alienvault', 'software')
     * @param boolean $no_cache     [Optional] Not use cached information
     *
     * @throws Exception  If an error occurred
     *
     * @return array Empty if not correct status type
     */
    public static function get_system_status($system_id, $status_type, $no_cache = FALSE)
    {
        $response = array();

        switch ($status_type)
        {
            case 'general':
                $call = 'get_general_status';
            break;

            case 'network':
                $call = 'get_network_status';
            break;

            case 'alienvault':
                $call = 'get_alienvault_status';
            break;

            case 'software':
                $call = 'get_software_status';
            break;
            
            default:
                return $response;
        }
        
        $client   = new Alienvault_client();

        $response = $client->system($system_id)->{$call}($no_cache);
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return $response['data'];
    }


    /**
     * This method gets the system general configuration
     *
     * @param string $system_id  System ID
     *
     * @throws Exception  If an error occurred
     *
     * @return array
     */
    public static function get_general_configuration($system_id)
    {
        $c_data = self::get_config_data($system_id);

        if ($c_data['status'] == 'error')
        {
            return $c_data;
        }

        $hostname = $c_data['data']['general_hostname'];
        $admin_ip = $c_data['data']['general_admin_ip'];

        if ($c_data['data']['general_ntp_server'] == 'no')
        {
            $yn_ntp_server = 'no';
            $ntp_server    = '';
        }
        else
        {
            $yn_ntp_server = 'yes';
            $ntp_server    = $c_data['data']['general_ntp_server'];
        }

        $yn_ms_relay     = ($c_data['data']['general_mailserver_relay'] == 'no') ? 'no' : 'yes';
        $ms_relay        = $c_data['data']['general_mailserver_relay'];
        $ms_relay_passwd = $c_data['data']['general_mailserver_relay_passwd'];
        $ms_relay_port   = $c_data['data']['general_mailserver_relay_port'];
        $ms_relay_user   = $c_data['data']['general_mailserver_relay_user'];


        $res['status'] = 'success';
        $res['data']   = array(
            'hostname'                 => array('id' => 'hostname',                 'name' => _('Hostname'),          'value' => $hostname),
            'admin_ip'                 => array('id' => 'admin_ip',                 'name' => _('Admin IP'),          'value' => $admin_ip),
            'yn_ntp_server'            => array('id' => 'yn_ntp_server',            'name' => _('NTP Server'),        'value' => $yn_ntp_server),
            'ntp_server'               => array('id' => 'ntp_server',               'name' => _('NTP Server IP'),     'value' => $ntp_server),
            'yn_mailserver_relay'      => array('id' => 'yn_mailserver_relay',      'name' => _('Mail Server Relay'), 'value' => $yn_ms_relay),
            'mailserver_relay'         => array('id' => 'mailserver_relay',         'name' => _('Server IP'),         'value' => $ms_relay),
            'mailserver_relay_passwd'  => array('id' => 'mailserver_relay_passwd',  'name' => _('Pass'),              'value' => $ms_relay_passwd),
            'mailserver_relay_passwd2' => array('id' => 'mailserver_relay_passwd2', 'name' => _('Confirm Pass'),      'value' => $ms_relay_passwd),
            'mailserver_relay_port'    => array('id' => 'mailserver_relay_port',    'name' => _('Port'),              'value' => $ms_relay_port),
            'mailserver_relay_user'    => array('id' => 'mailserver_relay_user',    'name' => _('User'),              'value' => $ms_relay_user)
        );

        return $res;
    }


    /**
     * This method sets new system general configuration
     *
     * @param string $system_id  System ID
     * @param array  $data       New configuration data
     *
     * @throws Exception  If an error occurred
     *
     * @return array
     */
    public static function set_general_configuration($system_id, $data)
    {
        $res['status'] = 'error';
        $res['data']   = _('System error! Configuration not applied [Failed to execute Alienvault Setup]');

        //Check Reconfig status
        $reconfig = self::get_task_status($system_id, 'alienvault-reconfig');

        if ($reconfig['status'] == 'running')
        {
            $res['status'] = 'executing_reconfig';
            $res['data']   = _('Reconfig was executed previously, wait for a few minutes and try again');

            return $res;
        }

        //Get System IP
        $db   = new ossim_db();
        $conn = $db->connect();

        $system_data = self::get_system_info_by_id($conn, $system_id);

        $db->close();

        if ($system_data['status'] == 'success')
        {
            $system_ip = (!empty($system_data['data']['vpn_ip'])) ? $system_data['data']['vpn_ip'] : $system_data['data']['admin_ip'];
        }

        $res = self::set_config_data($system_id, $data);

        return $res;
    }


    /**
     * This method gets the system network configuration
     *
     * @param string $system_id  System ID
     *
     * @throws Exception  If an error occurred
     *
     * @return array
     */
    public static function get_network_configuration($system_id)
    {
        $c_data = self::get_config_data($system_id);

        if ($c_data['status'] == 'error')
        {
            return $c_data;
        }

        $hostname         = $c_data['data']['general_hostname'];
        $admin_ip         = $c_data['data']['general_admin_ip'];
        $admin_dns        = $c_data['data']['general_admin_dns'];
        $firewall_active  = $c_data['data']['firewall_active'];
        $interface_active = $c_data['data']['general_interface'];
        $admin_gateway    = $c_data['data']['general_admin_gateway'];
        $admin_netmask    = $c_data['data']['general_admin_netmask'];

        $res['status'] = 'success';
        $res['data']   = array(
            'admin_dns'       => array('id' => 'admin_dns',       'name' => _('DNS Servers'), 'value' => $admin_dns),
            'firewall_active' => array('id' => 'firewall_active', 'name' => _('Firewall'),    'value' => $firewall_active),
            'interface'       => array('id' => 'interface',       'name' => _('Interface'),   'value' => $interface_active),
            'hostname'        => array('id' => 'hostname',        'name' => _('Hostname'),    'value' => $hostname),
            'admin_ip'        => array('id' => 'admin_ip',        'name' => _('IP'),          'value' => $admin_ip),
            'admin_gateway'   => array('id' => 'admin_gateway',   'name' => _('Gateway'),     'value' => $admin_gateway),
            'admin_netmask'   => array('id' => 'admin_netmask',   'name' => _('Netmask'),     'value' => $admin_netmask)
        );

        return $res;
    }


    /**
     * This method sets new system network configuration
     *
     * @param string $system_id  System ID
     * @param array  $data       New configuration data
     *
     * @throws Exception  If an error occurred
     *
     * @return array
     */
    public static function set_network_configuration($system_id, $data)
    {
        $res['status'] = 'error';
        $res['data']   = _('System error! Configuration not applied [Failed to execute Alienvault Setup]');

        //Check Reconfig status
        $reconfig = self::get_task_status($system_id, 'alienvault-reconfig');

        if ($reconfig['status'] == 'running')
        {
            $res['status'] = 'executing_reconfig';
            $res['data']   = _('Reconfig was executed previously, wait for a few minutes and try again');

            return $res;
        }

        //Get System IP
        $db   = new ossim_db();
        $conn = $db->connect();

        $system_data = self::get_system_info_by_id($conn, $system_id);

        $db->close();

        if ($system_data['status'] == 'success')
        {
            $system_ip = (!empty($system_data['data']['vpn_ip'])) ? $system_data['data']['vpn_ip'] : $system_data['data']['admin_ip'];
        }

        $res = self::set_config_data($system_id, $data);

        return $res;
    }


    /**
     * This method gets the sensor configuration
     *
     * @param string $system_id  System ID
     *
     * @throws Exception  If an error occurred
     *
     * @return array
     */
    public static function get_sensor_configuration($system_id)
    {
        $c_data = self::get_config_data($system_id, 'alienvault');

        if ($c_data['status'] == 'error')
        {
            return $c_data;
        }

        $s_networks   = $c_data['data']['sensor_networks'];
        $s_interfaces = $c_data['data']['sensor_interfaces'];
        $s_detectors  = $c_data['data']['sensor_detectors'];
        $server_ip    = $c_data['data']['server_server_ip'];
        $mservers     = $c_data['data']['sensor_mservers'];

        $res['status'] = 'success';
        $res['data']   = array(
            'sensor_networks'   => array('section' => 'detection',  'id' => 'sensor_networks',   'name' => _('Monitored Networks'),   'value' => $s_networks),
            'sensor_interfaces' => array('section' => 'detection',  'id' => 'sensor_interfaces', 'name' => _('Listening Interfaces'), 'value' => $s_interfaces),
            'sensor_detectors'  => array('section' => 'collection', 'id' => 'sensor_detectors',  'name' => _('Detectors'),            'value' => $s_detectors),
            'server_ip'         => array('section' => 'output',     'id' => 'server_ip',         'name' => _('Server IP'),            'value' => $server_ip),
            'mservers'          => array('section' => 'output',     'id' => 'mservers',          'name' => _('MultiServers'),         'value' => $mservers)
        );

        return $res;
    }


    /**
     * This method sets the sensor configuration
     *
     * @param string $system_id  System ID
     * @param array  $data       New configuration data
     *
     * @throws Exception  If an error occurred
     *
     * @return array
     */
    public static function set_sensor_configuration($system_id, $data)
    {
        $res['status'] = 'error';
        $res['data']   = _('System error! Configuration not applied [Failed to execute Alienvault Setup]');

        //Check Reconfig status
        $reconfig = self::get_task_status($system_id, 'alienvault-reconfig');

        if ($reconfig['status'] == 'running')
        {
            $res['status'] = 'executing_reconfig';
            $res['data']   = _('Reconfig was executed previously, wait for a few minutes and try again');

            return $res;
        }

        $res = self::set_config_data($system_id, $data, 'alienvault');

        return $res;
    }


    /**
     * This method returns system detectors status
     *
     * @param string $sensor_id  Sensor ID
     *
     * @throws Exception  If an error occurred
     *
     * @return array
     */
    public static function get_detectors_status($sensor_id)
    {
        $client   = new Alienvault_client();

        $response = $client->sensor($sensor_id)->get_service_status();
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return $response['data'];
    }


    /**
     * This method upgrades the system
     *
     * @param string $system_id  System ID
     *
     * @return array
     */
    public static function update_av_system($system_id)
    {
        $client   = new Alienvault_client();

        $response = $client->system($system_id)->update_av_system($data);
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $res['status'] = 'error';
            $res['data']   = _("Update process couldn't be executed: ") . $client->get_error_message($response);
        }
        else
        {
            $res['status'] = 'success';
            $res['data']   = _('Update Process Launched Successfully');
        }

        return $res;
    }


    /**
     * This method upgrades the Alienvault Feed
     *
     * @param string $system_id  System ID
     *
     * @return array
     */
    public static function update_av_feed($system_id)
    {
        $client   = new Alienvault_client();

        $response = $client->system($system_id)->update_av_feed($data);
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $res['status'] = 'error';
            $res['data']   = _("Update process couldn't be executed: ") . $client->get_error_message($response);
        }
        else
        {
            $res['status'] = 'success';
            $res['data']   = _('Update Process Launched Successfully');
        }

        return $res;
    }


    /**
     * This method gets the current task
     *
     * @param string $system_id  System ID
     * @param string $filter     [Optional] Task filter
     *
     * @return array
     */
    public static function get_current_task($system_id, $filter = '')
    {
        $tasks    = array();

        $client   = new Alienvault_client();
        $response = $client->system($system_id)->get_tasks();

        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $status  = 'error';
            $tasks   = sprintf(_('There was a problem checking the running tasks of the system: %s'), $client->get_error_message($response));

            Av_exception::write_log(Av_exception::USER_ERROR, $tasks);
        }
        else
        {
            $status = 'success';
            $tasks  = $response['data']['tasks'];
        }
        
        if ($status == 'success' && $filter != '')
        {
            $tasks = is_array($tasks[$filter]) ? $tasks[$filter] : array();
        }

        return array('status' => $status, 'data' => $tasks);
    }


    /**
     * This method store job ID in the current PHP session
     *
     * @param string $system_id  System ID
     * @param string $task_type  Task type (alienvault-update, alienvault-reconfig, ...)
     * @param string $job_id     Job ID
     *
     * @return void
     */
    public static function set_task_job_id($system_id, $task_type, $job_id)
    {
        $token  = '_job_id_' . $task_type;

        $_SESSION[$system_id][$token] = $job_id;
    }

    
    /**
     * This method gets a stored job ID related a task from current PHP session
     *
     * @param string $system_id  System ID
     * @param string $task_type  Task type (alienvault-update, alienvault-reconfig, ...)
     *
     * @return string
     */
    public static function get_task_job_id($system_id, $task_type)
    {
        $token  = '_job_id_' . $task_type;

        return $_SESSION[$system_id][$token];
    }


   /**
     * This method gets the current task status
     *
     * @param string $system_id  System ID
     * @param string $task_type  Task type (alienvault-update, alienvault-reconfig, ...)
     *
     * @return array
     */
    public static function get_task_status($system_id, $task_type)
    {
        $return = array();

        //The only supported tasks are alienvault-update and alienvault-reconfig
        if ($task_type != 'alienvault-reconfig' && $task_type != 'alienvault-update')
        {
            $return['status'] = 'fail';
            $return['msg']    = _('Invalid Task Type. Valid tasks are "alienvault-update" and "alienvault-reconfig".');

            return $return;
        }

        //Trying to retrieve the job id from session
        $job_id = self::get_task_job_id($system_id, $task_type);

        //If there is not a job_id stored, then we try to find it
        if (empty($job_id))
        {
            //Check Update/Reconfig status
            $task = self::get_current_task($system_id, $task_type);

            if ($task['status'] == 'error')
            {
                $return['status'] = 'error';
                $return['msg']    = $task['data'];

                return $return;
            }

            //If there is a process running we try to store it in session
            if ($task['data']['job_status'] == 'running' || $task['data']['job_status'] == 'pending')
            {
                //We need to check if it is a job_id. It might be a PID
                if (valid_hex32($task['data']['job_id'], TRUE))
                {
                    $job_id = $task['data']['job_id'];

                    //Saving the new job ID
                    self::set_task_job_id($system_id, $task_type, $job_id);
                }
                else //If it is a PID, we can only check that the process is running
                {
                    $return['status'] = 'running';

                    return $return;
                }
            }
            else
            {
                $return['status'] = 'done';

                return $return;
            }
        }

        //If we find a job id, then we try to retrieve the status of the job
        $client = new Alienvault_client();

        $response = $client->jobs()->get_job_status($job_id);
        $response = @json_decode($response, TRUE);

        //Comunication problem with the API. Error
        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::write_log(Av_exception::USER_ERROR, $exp_msg);

            self::set_task_job_id($system_id, $task_type, '');

            $return['status'] = 'error';
            $return['msg']    = $exp_msg;
        }

        //The job has finished successfully. Done
        if ($response['data']['job_status'] == 'task-succeeded' && $response['data']['job_result'] == TRUE)
        {
            //Cleaning the job stored in session
            self::set_task_job_id($system_id, $task_type, '');

            $return['status'] = 'done';
            $return['log']    = $response['data']['job_log'];
        }
        //The job is still pending. Running
        elseif ($response['data']['job_status'] == 'task-pending' || $response['data']['job_status'] == 'task-started')
        {
            $return['status'] = 'running';
        }
        //The job failed. Fail
        else
        {
            //Cleaning the job stored in session
            self::set_task_job_id($system_id, $task_type, '');

            $return['status'] = 'fail';
            $return['msg']    = $response['data']['job_message'];
            $return['log']    = $response['data']['job_log'];
        }

        return $return;
    }


    /**
     * This method gets status for current update task
     *
     * @param string  $system_id    System ID
     * @param integer $max_attempts [Optional] Max number of attempts
     *
     * @return array
     */
    public static function get_update_status($system_id, $max_attempts = 10)
    {
        $res['status'] = 'error';
        $res['msg']    = _('System error! Unable to connect to Server. Please, try again later');

        $update = self::get_task_status($system_id, 'alienvault-update');

        if ($update['status'] == 'running')
        {
            $res['status'] = 'updating';
            $res['msg']    =  '';
        }
        elseif ($update['status'] == 'done')
        {
            $res['status'] = 'finished';
            $res['msg']    =  _('System updated successfully');
            $res['log']    = $update['log'];
        }
        elseif ($update['status'] == 'fail')
        {
            $res['status'] = 'fail';
            $res['msg']    =  _('There was a problem updating the system.');
            $res['log']    = $update['log'];
        }
        else
        {
            if ($max_attempts > 0)
            {
                $w_constant = (1 / $max_attempts) * 60;
                $w_constant = ceil($w_constant);

                sleep($w_constant);
                $max_attempts = intval($max_attempts) - 1;
                $res          = self::get_update_status($system_id, $max_attempts);
            }
            else
            {
                $res['status'] = 'error';
                $res['msg']    = _('System timeout - Unable to connect to Server. Please, try again later');
            }
        }

        return $res;
    }


    /**
     * This method gets information about installed system packages
     *
     * @param string  $system_id  System ID
     *
     * @throws Exception  If an error occurred
     *
     * @return array
     */
    public static function get_packages_installed($system_id)
    {
        $client   = new Alienvault_client();

        $response = $client->system($system_id)->get_installed_packages();
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return $response['data'];
    }


    /**
     * This method returns information from pending packages to install
     *
     * @param string  $system_id  System ID
     * @param boolean $no_cache   [Optional] Not use cached information
     *
     * @return array
     */
    public static function get_packages_pending($system_id, $no_cache = FALSE)
    {
        $client   = new Alienvault_client();

        $response = $client->system($system_id)->get_pending_packages($no_cache);
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $exp_msg = $client->get_error_message($response);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        return $response['data']['available_updates'];
    }


    /**
     * This method returns information from available system logs
     *
     * @return array
     */
    public static function get_available_logs()
    {
        //System
        $log_files['kern'] = array(
            'section' => 'system',
            'name'    => 'kern.log',
            'url'     => '/var/log/kern.log',
            'desc'    => _('Provides a detailed log of messages from the Debian Linux Kernel')
        );

        $log_files['auth'] = array(
            'section' => 'system',
            'name'    => 'auth.log',
            'url'     => '/var/log/auth.log',
            'desc'    => _('The Authorization Log tracks usage of authorization systems')
        );

        $log_files['daemon'] = array(
            'section' => 'system',
            'name'    => 'daemon.log',
            'url'     => '/var/log/daemon.log',
            'desc'    => _('Provides information about running system and application daemons')
        );

        $log_files['messages'] = array(
            'section' => 'system',
            'name'    => 'messages',
            'url'     => '/var/log/messages',
            'desc'    => _('Provides informational messages from applications and system facilities')
        );

        $log_files['syslog'] = array(
            'section' => 'system',
            'name'    => 'syslog',
            'url'     => '/var/log/syslog',
            'desc'    => _('The system log contains the greatest deal of information by default about your AlienVault system')
        );

        //AlienVault Sensor
        $log_files['agent_stats'] = array(
            'section' => 'sensor',
            'name'    => 'agent_stats.log',
            'url'     => '/var/log/ossim/agent_stats.log',
            'desc'    => _('Contains detailed information on the collection process')
        );

        $log_files['agent'] = array(
            'section' => 'sensor',
            'name'    => 'agent.log',
            'url'     => '/var/log/alienvault/agent/agent.log',
            'desc'    => _('Collection statistics of the AlienVault Sensor')
        );

        //AlienVault Server
        $log_files['server'] = array(
            'section' => 'server',
            'name'    => 'server.log',
            'url'     => '/var/log/alienvault/server/server.log',
            'desc'    => _('Informational messages from the AlienVault Server')
        );

        $log_files['reputation'] = array(
            'section' => 'server',
            'name'    => 'reputation.log',
            'url'     => '/var/log/ossim/reputation.log',
            'desc'    => _('Provides information about the AlienVault IP Reputation System')
        );

        //AlienVault Web
        $log_files['apache_access'] = array(
            'section' => 'framework',
            'name'    => 'access.log',
            'url'     => '/var/log/apache2/access.log',
            'desc'    => _('Provides records of every page served and every file loaded by the web server')
        );

        $log_files['apache_error'] = array(
            'section' => 'framework',
            'name'    => 'error.log',
            'url'     => '/var/log/apache2/error.log',
            'desc'    => _('Provides records of all error conditions reported by the HTTP server')
        );

        $log_files['frameworkd'] = array(
            'section' => 'framework',
            'id'      => 'frameworkd',
            'name'    => 'frameworkd.log',
            'url'     => '/var/log/ossim/frameworkd.log',
            'desc'    => _('Informational messages from the AlienVault Frameworkd')
        );

        return $log_files;
    }


    /**
     * This method returns information from a log file
     *
     * @param string  $system_id  System ID
     * @param string  $log_url    Log path
     * @param integer $num_rows   Number of rows to show
     *
     * @return array
     */
    public static function get_log_file($system_id, $log_url, $num_rows)
    {
        $res['status'] = 'success';
        $res['data']   = '';

        $client   = new Alienvault_client();

        $response = $client->system($system_id)->get_log_lines($log_url, intval($num_rows));
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $res['status'] = 'error';
            $res['data']   = $client->get_error_message($response);
        }
        else
        {
            $log = is_array($response['data']['lines']) ? $response['data']['lines'] : array();
            $res['data'] = implode("\n", $log);
        }

        return $res;
    }
}

/* End of file av_center.inc */
/* Location: ../include/classes/av_center.inc */