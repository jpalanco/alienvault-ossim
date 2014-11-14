<?php

/**
 * sensor.inc
 *
 * File sensor.inc is used to:
 *   - define Sensor_provider methods
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
 * @package    ossim-framework\Various
 * @autor      AlienVault INC
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt
 * @copyright  2003-2006 ossim.net
 * @copyright  2007-2014 AlienVault
 * @link       https://www.alienvault.com/
 */

require_once 'provider.inc';

/**
 * The goal of this class is to add "Sensor" providers
 *
 *
 * @package    ossim-framework\Various
 * @autor      AlienVault INC
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt
 * @copyright  2003-2006 ossim.net
 * @copyright  2007-2014 AlienVault
 * @link       https://www.alienvault.com/
 */
class Sensor_provider extends Provider
{

    /********************************************************
     ******************* OSSEC Agentless *********************
     *********************************************************/


    /**
     * This method creates a new OSSEC agent
     *
     * @param string  $host        Address IP
     * @param string  $user        SSH User
     * @param string  $password    SSH Password
     * @param string  $supassword  Superuser Password
     *
     * @return object
     */
    public function create_ossec_agentless($host, $user, $password, $supassword)
    {
        $params   = array('host'       => $host,
                          'user'       => $user,
                          'password'   => $password,
                          'supassword' => $supassword
        );

        $response = $this->conn->do_request($this->common_url . '/ossec/agentless', $params, 'PUT');

        return $response;
    }


    /**
     * This method deletes an OSSEC agentless
     *
     * @param string  $agent_ip  Agentless IP
     *
     * @return object
     */
    public function delete_ossec_agentless($agent_ip)
    {
        $params   = array('agent_ip' => $agent_ip);

        $response = $this->conn->do_request($this->common_url . "/ossec/agentless", $params, 'DELETE');

        return $response;
    }


    /**
     * This method lists all OSSEC agents
     *
     * @return object
     */
    public function list_ossec_agentless()
    {
        $params   = array();

        $response = $this->conn->do_request($this->common_url . '/ossec/agentless', $params);

        return $response;
    }


    /**
     * This method gets the .passlist content
     *
     * .passlist will be downloaded to /var/alienvault/<system_id>/ossec/agentless/.passlist
     *
     * @return object
     */
    public function get_passlist()
    {
        $params = array();

        $response = $this->conn->do_request($this->common_url . '/ossec/agentless/passlist', $params);

        return $response;
    }


    /**
     * This method sets new content for .passlist
     *
     * .passlist is readed from /var/alienvault/<system_id>/ossec/agentless/.passlist
     *
     * @return object
     */
    public function set_passlist()
    {
        $params = array();

        $response = $this->conn->do_request($this->common_url . '/ossec/agentless/passlist', $params, 'PUT');

        return $response;
    }



    /********************************************************
    ********************* OSSEC Agents **********************
    *********************************************************/


    /**
     * This method lists all OSSEC agents
     *
     * @return object
     */
    public function list_ossec_agents()
    {
        $params   = array();

        $response = $this->conn->do_request($this->common_url . '/ossec/agent', $params);

        return $response;
    }


    /**
     * This method launches a remote ossec agent deployment
     *
     * @param string  $agent_name   Agent name
     * @param string  $agent_ip     Windows ip address
     * @param string  $user         Windows username
     * @param string  $domain       Windows domain
     * @param string  $password     Windows password
     *
     * @return object
     */
    public function launch_ossec_deploy($agent_name, $agent_ip, $user, $domain, $password)
    {
        $params   = array('agent_name'       => $agent_name,
                          'windows_ip'       => $agent_ip,
                          'windows_username' => $user,
                          'windows_domain'   => $domain,
                          'windows_password' => $password);

        $response = $this->conn->do_request($this->common_url . '/ossec/deploy', $params, 'PUT');

        return $response;
    }


    /**
     * This method launches a OSSEC rootcheck
     *
     * @param string  $agent_id  Agent ID
     *
     * @return object
     */
    public function launch_ossec_rootcheck($agent_id)
    {
        $params = array();

        $response = $this->conn->do_request($this->common_url . "/ossec/agent/$agent_id/root_check", $params);

        return $response;
    }


    /**
     * This method launches a OSSEC syscheck
     *
     * @param string   $agent_id         Agent ID
     * @param boolean  $only_w_registry  List modified registry entries (Windows only) or modified files for the agent
     * @return object
     */
    public function launch_ossec_syscheck($agent_id, $only_w_registry = FALSE)
    {
        $params = array();

        if ($only_w_registry == TRUE)
        {
            $response = $this->conn->do_request($this->common_url . "/ossec/agent/$agent_id/sys_check/windows_registry", $params);
        }
        else
        {
            $response = $this->conn->do_request($this->common_url . "/ossec/agent/$agent_id/sys_check", $params);
        }

        return $response;
    }


    /**
     * This method gets information about OSSEC agent
     *
     * @param string  $agent_id  Agent ID
     *
     * @return object
     */
    public function get_ossec_agent($agent_id)
    {
        $params   = array();

        $response = $this->conn->do_request($this->common_url . "/ossec/agent/$agent_id/control/detail", $params);

        return $response;
    }


    /**
     * This method creates a new OSSEC agent
     *
     * @param string  $agent_name   Agent name
     * @param string  $agent_ip     Ip address
     *
     * @return object
     */
    public function create_ossec_agent($agent_name, $agent_ip)
    {
        $params   = array('agent_name' => $agent_name,
                          'agent_ip'   => $agent_ip);

        $response = $this->conn->do_request($this->common_url . '/ossec/agent', $params, 'PUT');

        return $response;
    }


    /**
     * This method deletes an OSSEC agent
     *
     * @param string  $agent_id  Agent ID
     *
     * @return object
     */
    public function delete_ossec_agent($agent_id)
    {
        $params = array();

        $response = $this->conn->do_request($this->common_url . "/ossec/agent/$agent_id", $params, 'DELETE');

        return $response;
    }


    /**
     * This method downloads a pre-configured OSSEC Agent
     *
     * @param string  $agent_id     Agent ID
     * @param string  $agent_type   [Optional] Agent type (Agent for Windows or Linux)
     *
     * @return object
     */
    public function download_ossec_agent($agent_id, $agent_type = 'windows')
    {
        $params   = array('agent_id'   => $agent_id,
                          'agent_type' => $agent_type);

        $response = $this->conn->do_request($this->common_url . '/ossec/preconfigured_agent', $params, 'PUT');

        return $response;
    }


    /**
     * This method runs the integrity/rootkit checking on one OSSEC agent
     *
     * @param string  $agent_id  Agent ID
     *
     * @return object
     */
    public function check_ossec_agent_integrity($agent_id)
    {
        $params = array();

        $response = $this->conn->do_request($this->common_url . "/ossec/agent/$agent_id/integrity_check", $params, 'PUT');

        return $response;
    }


    /**
     * This method restarts an OSSEC agent
     *
     * @param string  $agent_id  Agent ID
     *
     * @return object
     */
    public function restart_ossec_agent($agent_id)
    {
        $params = array();

        $response = $this->conn->do_request($this->common_url . "/ossec/agent/$agent_id/restart", $params, 'PUT');

        return $response;
    }


    /**
     * This method returns the key of OSSEC agent
     *
     * @param string  $agent_id  Agent ID
     *
     * @return object
     */
    public function get_ossec_agent_key($agent_id)
    {
        $params = array();

        $response = $this->conn->do_request($this->common_url . "/ossec/agent/$agent_id/key", $params);

        return $response;
    }


    /**
     * This method returns the last IP of OSSEC agent
     *
     * @param string  $agent_name  Agent name
     *
     * @return object
     */
    public function get_last_agent_ip($agent_name)
    {
        $params = array(
            'agent_name' => $agent_name,
            'check_type' => 'lastip'
        );

        $response = $this->conn->do_request($this->common_url . '/ossec/check', $params);

        return $response;
    }
    
    
    /**
     * This method returns the last scan dates (rootcheck and syscheck) which launched an OSSEC agent
     *
     * @param string  $agent_ip  Agent IP
     *
     * @return object
     */
    public function get_last_agent_scans($agent_ip)
    {
        $params = array(
            'agent_ip'   => $agent_ip,
            'check_type' => 'lastscan'
        );

        $response = $this->conn->do_request($this->common_url . '/ossec/check', $params);

        return $response;
    }


    /**
     * This method gets the agent.conf content
     *
     * agent.conf will be downloaded to /var/alienvault/<system_id>/ossec/agent.conf
     *
     * @return object
     */
    public function get_agent_config()
    {
        $params = array();

        $response = $this->conn->do_request($this->common_url . '/ossec/configuration/agent', $params);

        return $response;
    }


    /**
     * This method sets new content for ossec.conf
     *
     * agent.conf is readed from /var/alienvault/<system_id>/ossec/agent.conf
     *
     * @return object
     */
    public function set_agent_config()
    {
        $params = array();

        $response = $this->conn->do_request($this->common_url . '/ossec/configuration/agent', $params, 'PUT');

        return $response;
    }


    /**
     * This method returns all rule files used by OSSEC
     *
     * @return object
     */
    public function get_ossec_rule_files()
    {
        $params = array();

        $response = $this->conn->do_request($this->common_url . '/ossec/rules', $params);

        return $response;
    }


    /**
     * This method gets the content from any OSSEC rule file
     *
     * <rule_file>  will be downloaded to /var/alienvault/<system_id>/ossec/rules/<rule_file>
     *
     * @param string  $rule_file  Any rule file
     *
     * @return object
     */
    public function get_ossec_rule_file($rule_file)
    {
        $params = array('rule' => $rule_file);

        $response = $this->conn->do_request($this->common_url . '/ossec/configuration/rule', $params);

        return $response;
    }


    /**
     * This method sets new content for local_rules.xml or rules_config.xml
     *
     * <rule_file> is readed from /var/alienvault/<system_id>/ossec/rules/<rule_file>
     *
     * @param string $rule_file  Any rule file
     *
     * @return object
     */
    public function set_rule_file($rule_file)
    {
        $params = array('rule' => $rule_file);

        $response = $this->conn->do_request($this->common_url . '/ossec/configuration/rule', $params, 'PUT');

        return $response;
    }


    /**
     * This method gets the ossec.conf content
     *
     * ossec.conf will be downloaded to /var/alienvault/<system_id>/ossec/ossec.conf
     *
     * @return object
     */
    public function get_ossec_config()
    {
        $params = array();

        $response = $this->conn->do_request($this->common_url . '/ossec/configuration/server', $params);

        return $response;
    }


    /**
     * This method sets new content for ossec.conf
     *
     * ossec.conf is readed from /var/alienvault/<system_id>/ossec/ossec.conf
     *
     * @return object
     */
    public function set_ossec_config()
    {
        $params = array();

        $response = $this->conn->do_request($this->common_url . '/ossec/configuration/server', $params, 'PUT');

        return $response;
    }


    /**
     * This method returns information about OSSEC logs
     *
     * @param string   $log_type   Log source (ossec or alert)
     * @param integer  $num_lines  [Optional] Maximum number of lines
     *
     * @return object
     */
    public function get_ossec_logs($log_type, $num_lines = 10)
    {
        $num_lines = intval($num_lines);

        $params = array(
            'log'            => $log_type,
            'number_of_logs' => $num_lines
        );

        $response = $this->conn->do_request($this->common_url . '/ossec/logs', $params);

        return $response;
    }
    
    
    /**
     * This method executes an OSSEC action
     *
     * @param string  $action  OSSEC action to execute
     *
     * @return object
    */
    public function ossec_control($action)
    {
        //Configuration array
        $config = array(
            'status'      => array('url' => '/status',  'method' => 'GET', 'params' => array()),
            'start'       => array('url' => '/start',   'method' => 'PUT', 'params' => array()),
            'stop'        => array('url' => '/stop',    'method' => 'PUT', 'params' => array()),
            'restart'     => array('url' => '/restart', 'method' => 'PUT', 'params' => array()),
            'enable_cs'   => array('url' => '/enable',  'method' => 'PUT', 'params' => array('option' => 'client-syslog')),
            'disable_cs'  => array('url' => '/disable', 'method' => 'PUT', 'params' => array('option' => 'client-syslog')),
            'enable_al'   => array('url' => '/enable',  'method' => 'PUT', 'params' => array('option' => 'agentless')),
            'disable_al'  => array('url' => '/disable', 'method' => 'PUT', 'params' => array('option' => 'agentless')),
            'enable_dbg'  => array('url' => '/enable',  'method' => 'PUT', 'params' => array('option' => 'debug')),
            'disable_dbg' => array('url' => '/disable', 'method' => 'PUT', 'params' => array('option' => 'debug'))
        );

        $response = $this->conn->do_request($this->common_url . '/ossec/control'.$config[$action]['url'] , $config[$action]['params'], $config[$action]['method']);

        return $response;
    }



    /********************************************************
    ********************** Interfaces ***********************
    *********************************************************/

    /**
     * This method sets promiscuous mode in network interfaces
     *
     * @param array  $interfaces  Array filled with promiscuous interfaces to configure
     *
     * @return object
     */
    public function set_promisc_mode($interfaces = array())
    {
        if (empty($interfaces))
        {
            return FALSE;
        }

        $params   = array('ifaces' => implode(',', $interfaces));

        $response = $this->conn->do_request($this->common_url . '/interface', $params, 'PUT');

        return $response;
    }



    /********************************************************
    *********************** Registry ************************
    *********************************************************/

    /**
     * This method set remote agent ctx
     *
     * @param string  $ctx  Context to set [Cannonical format]
     *
     * @return object
     */
    public function set_ctx($ctx)
    {
        $params   = array('ctx' => $ctx);

        $response = $this->conn->do_request($this->common_url, $params, 'PUT');

        return $response;
    }



    /********************************************************
    ************************ Plugins ************************
    *********************************************************/

    /**
     * This method returns all detector plugins (CFG files) from a sensor
     *
     * @return object
     */
    public function get_detector_list()
    {
        $params = array();

        return $this->conn->do_request($this->common_url . '/detector/all', $params);
    }


    /**
     * This method gets enabled plugins by sensor
     *
     * @return object
     */
    public function get_plugins()
    {
        $params = array();

        return $this->conn->do_request($this->common_url . '/detector', $params);
    }


    /**
     * This method gets enabled plugins by assets
     *
     * @return object
     */
    public function get_plugins_by_assets()
    {
        $params = array();

        return $this->conn->do_request($this->common_url . '/plugins', $params);
    }


    /**
     * This method enables plugins by sensor and launch a reconfig
     *
     * @param array  $plugins  Array filled with plugins to activate
     *
     * @return object
     */
    public function set_plugins($plugins = array())
    {
        if (empty($plugins))
        {
            return FALSE;
        }

        // Get current plugins
        $response = $this->get_plugins();
        $return   = @json_decode($response, TRUE);

        if (!$return || $return['status'] == 'error')
        {
            return $response;
        }

        $actived = $return['data']['plugins']['sensor_detectors'];
        $plugins = array_unique(array_merge($actived, array_keys($plugins)));

        if (count(array_diff($plugins, $actived)) > 0)
        {
            $params = array('plugins' => implode(',', $plugins));

            $response = $this->conn->do_request($this->common_url.'/detector', $params, 'PUT');
        }
        else
        {
            $response = json_encode(array('data' => array('job_id_reconfig' => 'ffffffff-ffff-ffff-ffff-ffffffffffff'), 'status' => 'success'));
        }

        return $response;
    }


    /**
     * This method enables plugins by assets and launch a reconfig
     *
     * @param array  $plugins  Array filled with plugins to activate
     *
     * @return object
     */
    public function set_plugins_by_assets($plugins = array())
    {
        /*
        Example request JSON:

        {
            "host_id": {
                "plugin_1" : "cpe_1",
                "plugin_2" : "cpe_2"
            }
        }
        */

        // Get current plugins first
        $response = $this->get_plugins_by_assets();
        $return   = @json_decode($response, TRUE);

        if (!$return || $return['status'] == 'error')
        {
            return $response;
        }

        $current_plugins = array();

        if (is_array($return['data']['plugins']))
        {
            $current_plugins = $return['data']['plugins'];
        }

        // Add new ones
        foreach ($plugins as $asset_id => $plgs)
        {
            $current_plugins[$asset_id] = $plgs;
        }

        $current_plugins = @json_encode($current_plugins, JSON_FORCE_OBJECT);
        $params          = array('plugins' => $current_plugins);

        $response = $this->conn->do_request($this->common_url.'/plugins', $params, 'POST');

        return $response;
    }


    /*********************************************************
     ******************** REMOTE SERVICE *********************
     *********************************************************/


    /**
     * This method returns remote system status
     *
     * @return string in JSON format
     */
    public function get_service_status()
    {
        $params = array();

        $response = $this->conn->do_request($this->common_url.'/service_status', $params);

        return $response;
    }
}
