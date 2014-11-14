<?php
/**
* alienvault_client.inc
*
* File alienvault_client.inc is used to:
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
* $client = new Alienvault_client("user", "password", "192.168.254.2", 7000);
* 
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

require_once 'alienvault_conn.inc';
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
    public function __construct($username = NULL, $password = NULL, $host = NULL, $port = NULL)
    {
        $this->conn       = new Alienvault_conn($username, $password, $host, $port);
        
        $this->providers  = new Provider_registry();

        $this->common_url = "/av/api/1.0";
    }
    
    /**
    * This function returns "Config" provider 
    *
    * @return "Config" object provider 
    */
    public function config()
    {
    	if($this->providers->exists("Config")===FALSE)
        {
            $this->providers->add($this->conn, "Config", $this->common_url);
        }

        return $this->providers->get("Config");
    }

    /**
    * This function returns "Apps" provider 
    *
    * @return "Apps" object provider
    */
    public function apps()
    {
    	if($this->providers->exists("Apps")===FALSE)
        {
            $this->providers->add($this->conn, "Apps", $this->common_url);
        }

        return $this->providers->get("Apps");
    }    
}
?>