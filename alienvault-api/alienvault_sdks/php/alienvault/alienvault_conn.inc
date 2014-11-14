<?php
/**
* alienvault_conn.inc
*
* File alienvault_conn.inc is used to:
*   - Interact with the Alienvault REST API
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
* The goal of this class is to interact with the Alienvault REST API
*
* <br/>
* <pre>
* <code>
* Example usage:
* 
* $conn = new Alienvault_conn("user", "password", "192.168.254.2", 7000);
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

class Alienvault_conn {

    /**
    * Rest API user
    * @var string
    *
    */ 
    private $username;
    
    /**
    * Rest API password
    * @var string
    *
    */
    private $password;
    
    /**
    * Rest server IP
    * @var string
    *
    */ 
    private $host;
    
    /**
    * Rest server listening port
    * @var int
    *
    */ 
    private $port;
    
    
    /**
    * Last error
    * @var string
    *
    */ 
    private $error;
    
    
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
        $this->username = $username;
        $this->password = $password;  
        $this->host     = $host;
        $this->port     = $port;
        
        $this->error    = '';
    }
    
    /**
    * This function returns the headers for Rest API Request
    *
    * @return array
    */
    private function get_headers()
    {
        $headers   = array();
        
        $headers[] = 'Content-type: application/x-www-form-urlencoded';
        $headers[] = 'Accept: application/json';
        $headers[] = 'User-Agent: AlienvaultClient';
        
        return $headers;
    }
    
    /**
    * This function returns a string with the parameters
    *
    * @param array $parameters  Request parameters
    * @param string $primary    Separator between variable and value
    * @param string $secundary  Parameters separator    
    *
    * @return string
    */
    private function format_query($parameters, $primary='=', $secondary='&')
    {
        $query = "";
        
        foreach($parameters as $key => $value){
            $pair = array(urlencode($key), urlencode($value));
            $query .= implode($primary, $pair) . $secondary;
        }
        return rtrim($query, $secondary);
    }
    
    /**
    * This function returns the error of the last request
    *
    * @return string
    */
    public function get_last_error()
    {
        return($this->error);
    }
    
    /**
    * This function return the server response
    *
    * @param string $method    Request method
    * @param array $parameters Request parameters
    *
    * @return string
    */
    public function do_request($url, $parameters = array(), $method = 'GET')
    {
        $handle  = curl_init();
        
        // Set options for the cURL transfer
        //
        // http://php.net/manual/en/function.curl-setopt.php
        
        curl_setopt($handle, CURLOPT_USERPWD, sprintf("%s:%s", $this->username, $this->password));

        if(strtoupper($method)=='POST')
        {
            $curlopt[CURLOPT_POST] = TRUE;
            $curlopt[CURLOPT_POSTFIELDS] = $this->format_query($parameters);
        }
        else if(count($parameters)>0)
        {
            $url .= strpos($url, '?')? '&' : '?';
            $url .= $this->format_query($parameters);
        }
        
        curl_setopt($handle, CURLOPT_URL,   'https://' . $this->host .  $url);
        
        curl_setopt($handle, CURLOPT_PORT ,   $this->port);
        
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, FALSE);
        
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
   
        curl_setopt($handle, CURLOPT_HTTPHEADER, $this->get_headers());

        $response = curl_exec($handle);
        
        // Save curl error
        
        $this->error = curl_error($handle);
        
        // Close the cURL session
        
        curl_close($handle);
        
        return $response;
    }
}
?>