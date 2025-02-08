<?php

/**
 * system.inc
 *
 * File system.inc is used to:
 *   - define System_provider methods
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
 * @package     ossim-framework\Various
 * @author      AlienVault INC
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt
 * @copyright   2003-2006 ossim.net
 * @copyright   2007-2014 AlienVault
 * @link        https://www.alienvault.com/
 */

require_once __DIR__ . '/provider.inc';

/**
 * The goal of this class is to add "System" providers
 *
 *
 * @package     ossim-framework\Various
 * @author      AlienVault INC
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt
 * @copyright   2003-2006 ossim.net
 * @copyright   2007-2014 AlienVault
 * @link        https://www.alienvault.com/
 */
class System_provider extends Provider
{

    /*****************************************************************
     ******************* NETWORK INTERFACES **************************
     *****************************************************************/


    /**
     * This method gets network interfaces
     *
     * @return object
     */
    public function get_interfaces()
    {
        $params = array();

        $response = $this->conn->do_request($this->common_url.'/network/interface', $params);

        return $response;
    }


    /**
     * This method sets promiscuous mode to a given network interface
     *
     * @param string  $iface   [Optional] Network interface
     * @param boolean $promisc [Optional] Promiscuous mode
     *
     * @return object
     */
    public function set_promisc_mode($iface = 'eth0', $promisc = FALSE)
    {
        $params = array('promisc' => ($promisc) ? 'true' : 'false');

        $response = $this->conn->do_request($this->common_url.'/network/interface/'.$iface, $params, 'PUT');

        return $response;
    }


    /**
     * This method sets promiscuous mode in network interfaces
     *
     * @param string $interfaces [Optional] Array filled with promiscuous interfaces to configure
     *
     * @return object
     */
    public function set_role_interfaces($interfaces = '')
    {
        $params = array('interfaces' => $interfaces);

        $response = $this->conn->do_request($this->common_url.'/network/interface', $params, 'PUT');

        return $response;
    }


    /**
     * This method gets network interface traffic stats
     *
     * @return object
     */
    public function get_traffic_stats()
    {
        $params = array();

        $response = $this->conn->do_request($this->common_url.'/network/traffic_stats', $params);

        return $response;
    }


    /**
     * This method gets DNS resolution
     *
     * @return object
     */
    public function get_dns_resolve()
    {
        $params = array();

        $response = $this->conn->do_request($this->common_url.'/network/resolve', $params);

        return $response;
    }

    /**
     * This method gets FQDNS
     *
     * @return object
     */
    public function get_fqdns($system_id, $host_ip)
    {
        $params = array('host_ip' => $host_ip, 'system_id' => $system_id);

        $response = $this->conn->do_request($this->common_url.'/network/fqdn', $params, 'POST');

        return $response;
    }


    /*****************************************************************
     *************************** LICENSE *****************************
     *****************************************************************/


    /**
     * This method gets the last AV version from data
     *
     * @return object
     */
    public function get_license_version()
    {
        $params = array();

        $response = $this->conn->do_request($this->common_url.'/license/version', $params);

        return $response;
    }


    /**
     * This method activates a trial using an email
     *
     * @param string $email Email to activate.
     *
     * @return string in JSON format
     */
    public function license_trial_activation($email)
    {
        //Increasing the API timeout
        $this->conn->set_timeout(300);

        $params = array('email' => $email);

        $response = $this->conn->do_request($this->common_url.'/license/trial', $params);

        return $response;
    }


    /**
     * This method activates a professional version using a key
     *
     * @param string $key Key to activate
     *
     * @return string in JSON format
     */
    public function license_key_activation($key)
    {
        //Increasing the API timeout
        $this->conn->set_timeout(300);

        $params = array('key' => $key);

        $response = $this->conn->do_request($this->common_url.'/license/pro', $params);

        return $response;
    }


    /*****************************************************************
     ************************** OTX **********************************
     *****************************************************************/


    /**
     * This method returns the otx configuration
     *
     * @return array with API response
     */
    public function get_otx_info()
    {
        $params   = array();
        $response = $this->conn->do_request("/system/otx/", $params);

        return $response;
    }


    /**
     * This method activates the otx account with a given token.
     *
     * @return array with API response
     */
    public function activate_otx($token)
    {
        $params   = array();
        $response = $this->conn->do_request("/system/otx/$token", $params, 'POST');

        return $response;
    }


    /**
     * This method deactivate the OTX account by removing the OTX variables from config.
     *
     * @return array with API response
     */
    public function remove_otx()
    {
        $params   = array();
        $response = $this->conn->do_request("/system/otx/", $params, 'DELETE');

        return $response;
    }


    /**
     * This method enables the var that that enables the OTX contribution.
     *
     * @return array with API response
     */
    public function enable_contribution()
    {
        $params   = array();
        $response = $this->conn->do_request("/system/otx/contribute/", $params, 'POST');

        return $response;
    }


    /**
     * This method disables the var that enables the OTX contribution.
     *
     * @return array with API response
     */
    public function disable_contribution()
    {
        $params   = array();
        $response = $this->conn->do_request("/system/otx/contribute/", $params, 'DELETE');

        return $response;
    }


    /**
     * This method returns the list of pulses the user is subscribed to.
     *
     * @return array with API response
     */
    public function get_pulse_list($filters)
    {
        $response = $this->conn->do_request("/system/otx/pulse/", $filters);

        return $response;
    }


    /**
     * This method returns the detail of a given pulse.
     *
     * @return array with API response
     */
    public function get_pulse_detail($pulse, $params = array())
    {
        $response = $this->conn->do_request("/system/otx/pulse/$pulse", $params);

        return $response;
    }


    /**
     * This method returns a summary of the otx in your system.
     *
     * @return array with API response
     */
    public function get_pulse_stats()
    {
        $params   = array();
        $response = $this->conn->do_request("/system/otx/pulse/stats/summary/", $params);

        return $response;
    }


    /**
     * This method returns the top pulses.
     *
     * @return array with API response
     */
    public function get_top_pulses($params)
    {
        $response = $this->conn->do_request("/system/otx/pulse/stats/top/", $params);

        return $response;
    }


    /**
     * This method returns the detail of a given pulse.
     *
     * @return array with API response
     */
    public function get_events_from_all_pulses($params)
    {
        $response = $this->conn->do_request("/system/otx/pulse/stats/event_trend/", $params);

        return $response;
    }


    /**
     * This method returns the detail of a given pulse.
     *
     * @return array with API response
     */
    public function get_events_from_top_pulses($params)
    {
        $response = $this->conn->do_request("/system/otx/pulse/stats/event_top/", $params);

        return $response;
    }




    /*****************************************************************
     ********************* CONFIGURATION *****************************
     *****************************************************************/


    /**
     * This method returns the general system configuration
     *
     * @return string in JSON format
     */
    public function get_config_general()
    {
        $params = array();

        $db        = new ossim_db();
        $conn      = $db->connect();

        $user_info = Session::get_user_info($conn,'admin', TRUE, FALSE);

        $db->close();

        $this->conn->do_request('/auth/login', array('username' => 'admin', 'password' => $user_info->get_pass()), 'GET', FALSE);

        $response = $this->conn->do_request($this->common_url.'/config', $params);

        return $response;
    }


    /**
     * This method sets the general system configuration
     *
     * @param array $data Array filled with data to set
     *
     * @return string in JSON format
     */
    public function set_config_general($data)
    {
        $params = $data;

        $response = $this->conn->do_request($this->common_url.'/config', $params, 'PUT');

        return $response;
    }


    /**
     * This method returns the alienvault system configuration
     *
     * @return string in JSON format
     */
    public function get_config_alienvault()
    {
        $params = array();

        $response = $this->conn->do_request($this->common_url.'/config_alienvault', $params);

        return $response;
    }


    /**
     * This method sets the alienvault system configuration
     *
     * @param array $data Array filled with data to set
     *
     * @return string in JSON format
     */
    public function set_config_alienvault($data)
    {
        $params = $data;

        $response = $this->conn->do_request($this->common_url.'/config_alienvault', $params, 'PUT');

        return $response;
    }


    /*****************************************************************
     ************************ COMPONENTS *****************************
     *****************************************************************/


    /**
     * This method attach a new component setting remote certificates
     *
     * @param string $ip    Component IP
     * @param string $key   ssh public key or password
     * @param string $type  'password' or 'key'
     *
     * @return object
     */
    public function set_component($ip, $key, $type='password')
    {
        //Increasing the API timeout
        $this->conn->set_timeout(300);

        $params = array(
            'system_ip' => $ip
        );

        if ($type == 'password')
        {
            $params['password'] = $key;
        }
        else
        {
            $params['ssh_key'] = $key;
        }

        $response = $this->conn->do_request('/system', $params, 'POST');

        return $response;
    }


    /**
     * This method gets system components with uuids
     *
     * @return object
     */
    public function get_components()
    {
        $params = array();

        $response = $this->conn->do_request('/system', $params);

        return $response;
    }


    /**
     * This method gets some basic info from the local system
     *
     * @return object
     */
    public function get_local_system_info()
    {
        $params = array();

        $response = $this->conn->do_request('/system/local/info', $params);

        return $response;
    }


    /*****************************************************************
     ************************ SYSTEM STATUS **************************
     *****************************************************************/


    /**
     * This method gets remote general status
     *
     * @param boolean $no_cache  [Optional] Not use cached information
     *
     * @return string JSON string
     */
    public function get_general_status($no_cache = FALSE)
    {
        //With boolean parameters, we must send a string that representing the boolean type
        $no_cache = (FALSE === $no_cache) ? 'false' : 'true';
        $params   = array('no_cache' => $no_cache);

        $response = $this->conn->do_request($this->common_url.'/status/general', $params);

        return $response;
    }


    /**
     * This method gets remote network status
     *
     * @param boolean $no_cache  [Optional] Not use cached information
     *
     * @return string JSON string
     */
    public function get_network_status($no_cache = FALSE)
    {
       //With boolean parameters, we must send a string that representing the boolean type
        $no_cache = (FALSE === $no_cache) ? 'false' : 'true';
        $params   = array('no_cache' => $no_cache);

        $response = $this->conn->do_request($this->common_url.'/status/network', $params);

        return $response;
    }


    /**
     * This method gets remote alienvault status
     *
     * @param boolean $no_cache  [Optional] Not use cached information
     *
     * @return string JSON string
     */
    public function get_alienvault_status($no_cache = FALSE)
    {
        //With boolean parameters, we must send a string that representing the boolean type
        $no_cache = (FALSE === $no_cache) ? 'false' : 'true';
        $params   = array('no_cache' => $no_cache);

        $response = $this->conn->do_request($this->common_url.'/status/alienvault', $params);

        return $response;
    }


    /**
     * This method gets remote software status
     *
     * @param boolean $no_cache  [Optional] Not use cached information
     *
     * @return string JSON string
     */
    public function get_software_status($no_cache = FALSE)
    {
        //With boolean parameters, we must send a string that representing the boolean type
        $no_cache = (FALSE === $no_cache) ? 'false' : 'true';
        $params   = array('no_cache' => $no_cache);

        $response = $this->conn->do_request($this->common_url.'/status/software', $params);

        return $response;
    }


    /**
     * This method gets all installed packages
     *
     * @return string JSON string
     */
    public function get_installed_packages()
    {
        $params = array();

        $response = $this->conn->do_request($this->common_url.'/status/installed_packages', $params);

        return $response;
    }


    /**
     * This method gets all pending packages
     *
     * @param boolean $no_cache  [Optional] Not use cached information
     *
     * @return string JSON string
     */
    public function get_pending_packages($no_cache = FALSE)
    {
        //With boolean parameters, we must send a string that representing the boolean type
        $no_cache = (FALSE === $no_cache) ? 'false' : 'true';
        $params   = array('no_cache' => $no_cache);

        $response = $this->conn->do_request($this->common_url.'/status/pending_packages', $params);

        return $response;
    }


    /**
     * This method pings a system
     *
     * @param boolean $no_cache  [Optional] Not use cached information
     *
     * @return string JSON string
     */
    public function do_ping()
    {
        $params = array();

        $response = $this->conn->do_request($this->common_url.'/status/ping', $params);

        return $response;
    }

    /**
     * This function get pending jobs
     *
     * @return object
     */
    public function get_pending_jobs()
    {
        $response = $this->conn->do_request($this->common_url . '/jobs', array());

        return $response;
    }

    /*****************************************************************
     ************************ SUPPORT ********************************
     *****************************************************************/


    /**
     * This method launches the AlienVault Diagnostic Tool
     *
     * @param string $system_id     Component UUID
     * @param string $ticket_number Number of ticket
     *
     * @return string in JSON format
     */
    public function launch_support_tool($system_id, $ticket_number)
    {
        //Increasing the API timeout
        $this->conn->set_timeout(300);

        $params = array('ticket' => $ticket_number);

        $response = $this->conn->do_request('/system/'.$system_id.'/doctor/support', $params);

        return $response;
    }


    /**
     * This method get active tunnels
     *
     * @param string $system_id     Component UUID
     *
     * @return string in JSON format
     */
    public function get_tunnel($system_id)
    {
        //Increasing the API timeout
        $this->conn->set_timeout(300);

/*if ($system_id=='c4339d09-12cb-323c-8d26-0174442a2172') return '{
  "data": {
    "pids": [
      {
        "channel": "ssh",
        "pid": "6103",
        "remote_port": "5001"
      },
      {
        "channel": "http",
        "pid": "6150",
        "remote_port": "5000"
      }
    ]
  },
  "status": "success"
}';
else return '{
  "data": null,
  "status": "success"
}';*/
        $response = $this->conn->do_request('/system/'.$system_id.'/support/tunnel', array());

        return $response;
    }


    /**
     * This method get active tunnels
     *
     * @param string  $system_id     Component UUID
     * @param string  $ticket_number Number of ticket
     * @param boolean $connect       true => Connect, false => Disconnect
     *
     * @return string in JSON format
     */
    public function set_tunnel($system_id, $ticket_number, $connect)
    {
        //Increasing the API timeout
        $this->conn->set_timeout(300);

        /*return '{
  "data": null,
  "status": "success"
}';*/
        if ($connect)
        {
            $params = array('ticket' => $ticket_number);
            $response = $this->conn->do_request('/system/'.$system_id.'/support/tunnel', $params, 'POST');
        }
        else
        {
            $response = $this->conn->do_request('/system/'.$system_id.'/support/tunnel', array(), 'DELETE');
        }

        return $response;
    }

    /*****************************************************************
     ************************ MODIFY SYSTEM  *************************
     *****************************************************************/


    /**
     * This method set remote certificates from an existing system
     *
     * @param string $password System password
     *
     * @return object
     */
    public function authenticate_system($password)
    {
        $params = array('password' => $password);

        $response = $this->conn->do_request($this->common_url.'/authenticate', $params, 'PUT');

        return $response;
    }


    /**
     * This method deletes a system
     *
     * @return object
     */
    public function delete_system()
    {
        //Increasing the API timeout
        $this->conn->set_timeout(300);

        $response = $this->conn->do_request($this->common_url, array(), 'DELETE');

        return $response;
    }


    /**
     * This function returns a certain number of lines of a given log file
     *
     * @param string   $log_file  Log file ID
     * @param integer  $lines     Number of lines to display
     *
     * @return object
     */
    public function get_log_lines($log_file, $lines)
    {
        $params = array(
            'log_file' => $log_file,
            'lines'    => $lines
        );

        $response = $this->conn->do_request($this->common_url . "/log", $params, 'GET');

        return $response;
    }


    /**
     * This function updates AlienVault
     *
     * @return string in JSON format
     */
    public function update_av_system()
    {
        $response = $this->conn->do_request($this->common_url . '/update', array(), 'PUT');

        return $response;
    }


    /**
     * This function updates the AlienVault Feed
     *
     * @return string in JSON format
     */
    public function update_av_feed()
    {
        $response = $this->conn->do_request($this->common_url . '/update/feed', array(), 'PUT');

        return $response;
    }


    /**
     * This method set automatic feed_update task with new values.
     *
     * @param boolean  $enabled         Task status (1 -> Enabled, 0 -> Disabled)
     * @param integer  $scheduled_hour  Scheduled hour
     *
     * @return object
     */
    public function set_auto_update($enabled = FALSE, $scheduled_hour = 0)
    {
        $params = array(
            "enabled" => ($enabled) ? 1 : 0,
            "scheduled_hour" => $scheduled_hour,
        );

        $response = $this->conn->do_request('/system/auto_update', $params, 'PUT');

        return $response;
    }

    /**
     * This method sets how often the agent information is updated into the database
     *
     * @param integer  $refresh_rate  Refresh rate (in minutes)
     *
     * @return object
     */
    public function set_hids_update_rate($refresh_rate)
    {
        $params = array(
            'refresh_rate' => $refresh_rate
        );

        $response = $this->conn->do_request('/system/hids_update_rate', $params, 'PUT');

        return $response;
    }


    /**
     * This function check if there is an AlienVault Update/Reconfig running
     *
     * @return string in JSON format
     */
    public function get_tasks()
    {
        $response = $this->conn->do_request($this->common_url . '/tasks', array(), 'GET');

        return $response;
    }

    /*****************************************************************
     ********************** CONFIGURATION BACKUP *********************
    *****************************************************************/

    /**
     * This method gets the backup list
     *
     * @param string   $system_id    System ID
     * @param string   $type         Backup Type
     *
     * @return object
     */
    public function get_backup_list($system_id, $type)
    {
        $params = array('type' => $type);

        $response = $this->conn->do_request('/system/'.$system_id.'/backup', $params, 'GET');

        return $response;
    }

    /**
     * This method runs a backup
     *
     * @param string   $system_id    System ID
     * @param string   $type         Backup Type
     *
     * @return object
     */
    public function run_backup($system_id, $type)
    {
        $params = array('type' => $type);

        $response = $this->conn->do_request('/system/'.$system_id.'/backup', $params, 'POST');

        return $response;
    }

    /**
     * This method deletes some backups by file
     *
     * @param string   $system_id     System ID
     * @param string   $type          Backup Type
     * @param array    $backup_files  Backup Files
     *
     * @return object
     */
    public function delete_backup($system_id, $type, $backup_files)
    {
        $files_string = @json_encode($backup_files);

        $params = array(
                'type'    => $type,
                'backups' => $files_string
        );

        $response = $this->conn->do_request('/system/'.$system_id.'/backup', $params, 'DELETE');

        return $response;
    }

    /**
     * This method download a backup by file
     *
     * @param string   $system_id     System ID
     * @param string   $type          Backup Type
     * @param array    $backup_file   Backup File
     *
     * @return object
     */
    public function download_backup($system_id, $type, $backup_file)
    {
        $params = array(
                'type'    => $type
        );

        $response = $this->conn->do_request('/system/'.$system_id.'/backup/'. $backup_file, $params, 'GET');

        return $response;
    }

    /**
     * This method set the given content
     *
     * @param string   $keyfile
     * @param array    $content
     *
     * @return object
     */
    public function set_system_certificates($crt, $pem, $ca)
    {
        $params = array(
                'crt' => $crt,
                'pem' => $pem,
                'ca' => $ca
        );

        $response = $this->conn->do_request('/system/local/certificate', $params, 'POST');

        return $response;
    }

    public function get_update_running_status($system_id)
    {
        $response = $this->conn->do_request("/system/$system_id/status/ready_for_update", array(), 'GET');
        return $response;
    }

}

/* End of file system.inc */
/* Location: ../include/classes/providers/system.inc */
