<?php
/**
* Alienvault_client.inc
*
* File Alienvault_client.inc is used to:
*   - Use to make request to AlienVault REST API
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
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


/**
* The goal of this class is to make request to AlienVault REST API
*
* <br/>
* <pre>
* <code>
* Example usage:
*
* $client = new Alienvault_client('admin', '127.0.0.1', 40011);
*
* </code>
* </pre>
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/

//require_once __DIR__ . '/providers/provider.inc';

class Alienvault_client
{

    /**
    * Connection object
    * @access private
    * @var object
    *
    */
    private $conn;

    /**
    * providers list
    * @access private
    * @var object
    *
    */
    private $providers;

    /**
    * common url to do the request
    * @access private
    * @var string
    *
    */
    private $common_url;

    /**
    * The class constructor
    *
    * This sets up the class
    *
    * @param Object $alienvault_conn object from the Alienvault_conn class
    * @param Object $provider_registry object from the provider_registry class
    *
    */
    public function __construct($alienvault_conn, $provider_registry)
    {
        $this->providers  = $provider_registry;
        $this->conn       = $alienvault_conn;

        $this->common_url = '';
    }

    /**
     * This function creates and returns an Alienvault_client instance object
     *
     * @param Object $alienvault_conn object from the Alienvault_conn class
     * @param Object $provider_registry object from the provider_registry class
     *
     * @return Alienvault_client
     */
    public static function factory($alienvault_conn, $provider_registry)
    {
        return new Alienvault_client($alienvault_conn, $provider_registry);
    }


    /**
     * This function returns a formatted error string
     *
     * @param array  $response  JSON decoded API response
     *
     * @return string
     */
    public function get_error_message($response)
    {
        $error_msg = _('API error - Operation cannot be completed');

        if (!empty($response))
        {
            if ($response['message'] != '')
            {
                $error_msg =  _($response['message']);
            }
            elseif ($response['status_long_message'] != '')
            {
                $error_msg = $response['status_long_message'];
            }
        }

        $error_msg = nl2br($error_msg);

        return $error_msg;
    }


    /**
     * This function parse the API json response string
     *
     * @param array  Result of parsing the response: success flag and data result.
     *
     * @return string
     */
    public function check_response($response)
    {
        $response = @json_decode($response, TRUE);

        if (!$response || $response['status'] == 'error')
        {
            $success = FALSE;
            $data    = $this->get_error_message($response);
        }
        else
        {
            $success = TRUE;
            $data    = $response['data'];
        }

        return array($success, $data);
    }



    /**
     * This function returns Database access object
     *
     * @return object
     */
    public function get_conn()
    {
        return $this->conn;
    }


    /**
    * This function sets Database access object
    *
    * @param object  $conn   New Database access object
    *
    * @return void
    */
    public function set_conn($conn)
    {
        $this->conn = $conn;
    }


    /**
     * This function returns "Auth" provider
     *
     * @return "Auth" object provider
     */
    public function auth()
    {
        $this->providers->set($this->conn, 'auth', $this->common_url.'/auth');

        return $this->providers->get('auth');
    }


    /**
     * This function returns "System" provider
     *
     * @return "System" object provider
     */
    public function system($system_id = 'local')
    {
        $this->providers->set($this->conn, 'system', $this->common_url."/system/$system_id");

        return $this->providers->get('system');
    }


    /**
     * This function returns "Server" provider
     *
     * @return "System" object provider
     */
    public function server($system_id = 'local')
    {
        $this->providers->set($this->conn, 'server', $this->common_url."/server/$system_id");

        return $this->providers->get('server');
    }


    public function central_console()
    {
        $this->providers->set($this->conn, 'central_console', $this->common_url."/central_console");

        return $this->providers->get('central_console');
    }


    /**
     * This function returns "Sensor" provider
     *
     * @return "Sensor" object provider
     */
    public function sensor($sensor_id = 'local')
    {
        $this->providers->set($this->conn, 'sensor', $this->common_url."/sensor/$sensor_id");

        return $this->providers->get('sensor');
    }


    /**
     * This function returns "Data" provider
     *
     * @return "Data" object provider
     */
    public function data($context_id = '/local')
    {
        $this->providers->set($this->conn, 'data', $this->common_url."/data$context_id");

        return $this->providers->get('data');
    }


    /**
     * This function returns "Jobs" provider
     *
     * @return "Jobs" object provider
     */
    public function jobs()
    {
        $this->providers->set($this->conn, 'jobs', $this->common_url.'/job');

        return $this->providers->get('jobs');
    }


    /**
     * This function returns "Nmap" provider
     *
     * @return "Nmap" object provider
     */
    public function nmap()
    {
        $this->providers->set($this->conn, 'nmap', $this->common_url.'/nmap');

        return $this->providers->get('nmap');
    }

    /**
     * This function returns "Gvm" provider
     *
     * @return "Gvm" object provider
     */
    public function gvm()
    {
        $this->providers->set($this->conn, 'gvm', $this->common_url.'/gvm');

        return $this->providers->get('gvm');
    }


    /**
     * This function returns "Asec" provider
     *
     * @return "Asec" object provider
     */
    public function asec()
    {
        if (Session::is_pro() == FALSE)
        {
            $exp_msg = _('Error! Provider not allowed for this version');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $this->providers->set($this->conn, 'asec', $this->common_url.'/system/asec');

        return $this->providers->get('asec');
    }


    /**
     * This function returns "Plugin" provider
     *
     * @return "Plugin" object provider
     */
    public function plugin()
    {
        $this->providers->set($this->conn, 'plugin', $this->common_url.'/plugin');

        return $this->providers->get('plugin');
    }
}

/* End of file alienvault_client.inc */
/* Location: ../include/classes/alienvault_client.inc */
