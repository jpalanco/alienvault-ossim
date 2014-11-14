<?php
/**
* auth.inc
*
* File auth.inc is used to:
*   - define Auth_provider methods
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

require_once 'provider.inc';

/**
* The goal of this class is to add "Auth" providers
* 
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/
class Auth_provider extends Provider
{
    /**
    * This function login into the api. 
    *
    * @param string  $user  Login user name
    * @param string  $pass  Login user password
    *
    * @return object
    */
    public function login($user, $pass)
    {
        // Try with sha256 encrypted password (Since 4.11)
        $params   = array("username" => $user,
                          "password" => hash('sha256', $pass));

        $response = $this->conn->do_request($this->common_url . "/login", $params);
        
        // If 401 Invalid username or password (Try with old pass encryption md5)
        $response_data = @json_decode($response, TRUE);
        
        if ($response_data['status_code'] == 401)
        {
            $params   = array("username" => $user,
                              "password" => md5($pass));
            
            $response = $this->conn->do_request($this->common_url . "/login", $params);
        }
        
        // If 401 Invalid username or password (The password can be encrypted).
        // View scripts/vulnmeter/send_notification.php, we have to login to send a email notification. There is no session initialized.
        $response_data = @json_decode($response, TRUE);
        
        if ($response_data['status_code'] == 401)
        {
            $params   = array("username" => $user,
                              "password" => $pass);
            
            $response = $this->conn->do_request($this->common_url . "/login", $params);
        }
        
        return $response;
    }

    /**
    * This function logout in the system. 
    *
    * @return string in json format
    */
    public function logout()
    {
        $response = $this->conn->do_request($this->common_url . "/logout");
        
        return $response;
    }

    /**
    * This function get certificate file
    *
    * @param string  $uuid  Local system uuid
    *
    * @return string in json format
    */
    public function set_certificate($uuid)
    {
        if (!valid_hex32($uuid, TRUE))
        {
            return FALSE;
        }
        
        $response = $this->conn->do_request($this->common_url . "/cert/$uuid");
        
        return $response;
    }    
}

?>