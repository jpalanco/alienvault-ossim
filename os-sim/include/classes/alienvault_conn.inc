<?php
/**
* Alienvault_conn.inc
*
* File Alienvault_conn.inc is used to:
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
    * Cookie file
    * @var string
    *
    */ 
    private $cookie_file;    

    /**
    * Debug file
    * @var string
    *
    */ 
    private $debug_file;    
        
    /**
    * Last error
    * @var string
    *
    */ 
    private $error;
    
    /**
    * common url
    * @var string
    *
    */ 
    private $prefix_url;

    /**
    * log into /tmp/api all api calls
    * @var boolean
    */ 
    private $log;

    /**
    * timeout in seconds
    * @var integer
    */ 
    private $timeout;
            
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
        $this->username    = ($username != '')                 ? $username : Session::get_session_user();
        $this->username    = strtolower($this->username);
        $this->host        = (Asset_host_ips::valid_ip($host)) ? $host     : '127.0.0.1';
        $this->port        = ($port>0 && $port<65535)          ? $port     : 40011;
        $this->timeout     = 60; 
        
        $this->prefix_url  = "/av/api/1.0";
        
        $this->error       = '';
        $this->log         = file_exists('/tmp/debug_api') ? TRUE : $log;
        $this->cookie_file = '/var/tmp/api_cookie_jar_'.$this->username.'.txt';
        $this->debug_file  = '/tmp/api';
    }

    /**
    * This function set timeout in seconds
    *
    * @return void
    */
    public function set_timeout($seconds)
    {
        $this->timeout = $seconds;
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
        $headers[] = 'X-Forwarded-For: '.Util::get_default_admin_ip();
        
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
        $query = '';
        
        foreach($parameters as $key => $value)
        {
            $pair   = array(urlencode($key), urlencode($value));
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
    * @param string  $url        Request url
    * @param array   $parameters Request parameters
    * @param string  $method     Request method
    * @param boolean $trylogin   TRUE|FALSE
    *
    * @return string
    */
    public function do_request($url, $parameters = array(), $method = 'GET', $trylogin = TRUE)
    {
        $handle  = curl_init();
        
        // Set options for the cURL transfer
        //curl_setopt($handle, CURLOPT_USERPWD, sprintf("%s:%s", $this->username, $this->password));
        
        // Add prefix
        $orig_url = $url;
        $url      = $this->prefix_url . $url;

        if (strtoupper($method)=='POST')
        {
            curl_setopt($handle, CURLOPT_POST, 1);
            curl_setopt($handle, CURLOPT_POSTFIELDS, $this->format_query($parameters));
        }
        else
        {
            if (strtoupper($method)!='GET')
            {
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, strtoupper($method));
            }            
            
            if(count($parameters)>0)
            {
                $url .= strpos($url, '?')? '&' : '?';
                $url .= $this->format_query($parameters);
            }
        }
        
        curl_setopt($handle, CURLOPT_URL, 'https://' . $this->host .  $url);
        
        curl_setopt($handle, CURLOPT_PORT, $this->port);
        
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, FALSE);
        
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
   
        curl_setopt($handle, CURLOPT_HTTPHEADER, $this->get_headers());
        
        curl_setopt($handle, CURLOPT_TIMEOUT, $this->timeout); //timeout in seconds

        curl_setopt($handle, CURLOPT_COOKIEJAR, $this->cookie_file);

        if (preg_match("/login/", $url) == FALSE)
        {
            curl_setopt($handle, CURLOPT_COOKIEFILE, $this->cookie_file);
        }

        // Log
        if ($this->log)
        {
            error_log(gmdate("Y-m-d H:i:s")." GMT\n".strtoupper($method)." https://".$this->host.":".$this->port.$url."\n", 3, $this->debug_file);
        }

        $response = curl_exec($handle);
        
        // Save curl error
        
        $this->error = curl_error($handle);

        // Log
        if ($this->log)
        {
            error_log($response."\n".$this->error."\n", 3, $this->debug_file);
        }
        
        // Close the cURL session
        
        curl_close($handle);
        
        // Check if a response is not 403 and relogin
        if ($trylogin)
        {
            $return = @json_decode($response, TRUE);                       
            if ($return['status'] == 'error' && $return['status_code'] == '403') // Unauthorized
            {
                return $this->try_login_redo_request($orig_url, $parameters, $method);
            }
            
        }
        
        return $response;
    }
    
    /**
    * This function try to login and do the same request if the previous one returns 403 - Unauthorized
    *
    * @param string  $url        Request url
    * @param array   $parameters Request parameters
    * @param string  $method     Request method
    *    
    * @return string
    */
    private function try_login_redo_request($url, $parameters, $method)
    {
        // re login
        $db        = new ossim_db();
        $conn      = $db->connect();

        $user_info = Session::get_user_info($conn, $this->username);

        $db->close();

        $this->do_request('/auth/login', array('username' => $this->username, 'password' => $user_info->get_pass()), 'GET', FALSE);
                
        return $this->do_request($url, $parameters, $method, FALSE);
    }    
}
?>