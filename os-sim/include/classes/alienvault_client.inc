<?php
/**
* Alienvault_client.inc
*
* File Alienvault_client.inc is used to:
*   - Use to make request to Alienvault REST API
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
* The goal of this class is to make request to Alienvault REST API
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

require_once 'providers/provider.inc';

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
    * @param string $username Rest API user
    * @param string $password Rest API password
    * @param string $host Rest server IP
    * @param string $port Rest server listening port
    * 
    */
    public function __construct($username = NULL, $host = NULL, $port = NULL, $log = FALSE)
    {
        $this->conn       = new Alienvault_conn($username, $host, $port, $log);
        
        $this->providers  = new Provider_registry();

        $this->common_url = '';
    }


    /**
     * This function returns a formmatted error string
     *
     * @param array  $response  JSON decoded API response
     *
     * @return string
     */
    public function get_error_message($response)
    {
        $error_msg = _('API Error: Sorry, operation was not completed due to an unknown error');

        if (!empty($response))
        {
            if ($response['message'] != '')
            {
                $error_msg = $response['message'];
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
}
?>