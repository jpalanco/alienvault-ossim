<?php
/**
* av_component.inc
*
* File av_component.inc is used to:
*   - To manage generic alienvault components
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
* @package    ossim-framework\Alienvault_Components
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


/**
* Av_component Class
*
* Class for managing generic alienvault components
*
* @package    ossim-framework\Alienvault_Components
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ Alienvault web page
*/

abstract class Av_component
{
    /**
    * Av_component ID
    *
    * @var string
    * @access protected
    */ 
    protected $id;
    
    /**
    * Av_component context
    *
    * @var string
    * @access protected
    */ 
    protected $ctx;
    
    /**
    * Av_component name
    *
    * @var string
    * @access protected
    */ 
    protected $name;
    
    /**
    * Av_component IP
    *
    * @var string
    * @access protected
    */ 
    protected $ip;     
               
    /**
    * Av_component description
    *
    * @var string
    * @access protected
    */ 
    protected $descr;
            
    /**
    * Av_component icon
    *
    * @var string
    * @access protected
    */ 
    protected $icon;
    
    /**
    * Av_component port
    *
    * @var integer
    * @access protected
    */ 
    protected $port;    
    
    /**
    * This function returns the av_component ID
    *
    * @return string
    */      
    public function get_id()
    {
        return $this->id;
    }
    
    
    /**
    * This function returns the av_component context
    *
    * @return string
    */      
    public function get_ctx()
    {
        return $this->ctx;
    }
    
    
    /**
    * This function returns the av_component name
    *
    * @return string
    */      
    public function get_name()
    {
        return $this->name;
    }
    
    
    /**
    * This function returns the av_component IP
    *
    * @return string
    */      
    public function get_ip()
    {
        return $this->ip;
    }
             
                
    /**
    * This function returns the av_component description
    *
    * @return string
    */      
    public function get_descr()
    {
        return $this->descr;
    }
      
        
    /**
    * This function returns the av_component icon (BLOB)
    *
    * @return string
    */      
    public function get_icon()
    {
        if ($this->icon == '')
        {
            return '';
        }
        
        return $this->icon;
    }
    
    
    /**
    * This function returns the av_component icon (html version)
    *
    * @return string
    */    
    public function get_html_icon() 
    {
        $html_icon = '';        
                
        if ($this->get_icon() != '')
        {
            $html_icon = "<img id='asset_icon' src='data:image/png;base64,".base64_encode($this->get_icon())."' border='0' align='absbottom'/>";
        }
        
        return $html_icon;
    }
        
    
    /**
    * This function returns the av_component port
    *
    * @return integer
    */      
    public function get_port()
    {
        return $this->port;
    }
    
        
    /**
    * This function returns the av_component type
    *
    * @return string
    */ 
    abstract public function get_av_type();
    
    
    /**
    * This function sets the av_component ID
    *
    * @param string $id  Av_component ID
    *
    * @throws Exception  If Av_component ID is not valid
    *
    * @return void
    */      
    public function set_id($id)
    {
        if (!empty($id) && valid_hex32($id))
        {
            $this->id = strtoupper($id);
        }
        else
        {                        
            $exp_msg = _('Error! Alienvault Component ID is not valid');
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
    }
    
    
    /**
    * This function sets the av_component context
    *
    * @param string $ctx  Av_component context
    *
    * @return void
    */      
    abstract public function set_ctx($ctx);
    
    
    /**
    * This function sets the av_component name
    *
    * @param string $name  Av_component name
    *
    * @return void
    */      
    public function set_name($name)
    {
        if ($name == '')
        {                        
            $exp_msg = _('Error! Alienvault Component name is empty');
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
        
        $this->name = Util::htmlentities($name);
    }
    
    
    /**
    * This function sets the sensor IP
    *
    * @param string $ip  Sensor IP
    *
    * @return void
    */      
    public function set_ip($ip)
    {
        if (!Asset_host_ips::valid_ip($ip))
        {            
            $exp_msg = _('Error! Alienvault Component IP is not valid');
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
        
        $this->ip = $ip;
    }
    
              
    /**
    * This function sets the av_component description
    *
    * @param string $descr  Av_component descripcion
    *
    * @return void
    */      
    public function set_descr($descr)
    {
        $this->descr = $descr;
    }
   
        
    /**
    * This function sets the av_component icon
    *
    * @param string $icon  Av_component icon
    *
    * @return void
    */      
    public function set_icon($icon)
    {        
        $this->icon = $icon;      
    }
    
    
    /**
    * This function sets the av_component port
    *
    * @param integer $port  Av_component port
    *
    * @return void
    */      
    public function set_port($port)
    {        
        settype($port, 'integer');

        if (!is_int($port) || $port <= 0 || $port > 65535)
        {           
            $exp_msg = _('Error! Alienvault Component port is not valid');
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
        
        $this->port = $port;
    }
    
        
    /**
    * This function sets the av_component data from database
    *
    * @param object  $conn   Database access object     
    * @param boolean $cache  [Optional] Use cached information
    *
    * @throws Exception  If av_component ID doesn't exists in the System or there is a connection error    
    *
    * @return void
    */ 
    abstract public function load_from_db($conn, $cache = FALSE);
    
    
    /**
    * This function saves av_component into database
    *
    * @param object  $conn   Database access object
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean      
    */ 
    abstract public function save_in_db($conn);
    
    
    
    
    /*************************************************
     **************** Static functions ***************
     *************************************************/


    /**
    * This function reports components changes to other components (Server, Sensor, ...)
    *
    * @param string  $asset_type   sensors or servers
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean
    */
    public static function report_changes($asset_type)
    {
        // Refresh cache
        Util::memcacheFlush();
        Util::resend_asset_dump($asset_type);


        $conf = $GLOBALS['CONF'];
        
        if (!$conf)
        {
            $conf = new Ossim_conf();
            $GLOBALS['CONF'] = $conf;
        }
        //Getting port and IP address from server
        
        $address = $conf->get_conf('server_address');
        $port    = $conf->get_conf('server_port');


        /********************************
         ************ Server ************
         ********************************/

        //Creating socket
        $socket = @socket_create(AF_INET, SOCK_STREAM, 0);
        
        if ($socket === FALSE)
        {        
            $exp_msg = _('Warning! Unable to create a connection with Server');
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        //Connection
        socket_set_block($socket);
        socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO, array('sec' => 10, 'usec' => 0));
        socket_set_option($socket,SOL_SOCKET,SO_SNDTIMEO, array('sec' => 5,  'usec' => 0));

        $result = @socket_connect($socket, $address, $port);

        if ($result === FALSE)
        {         
            $last_socket_error = socket_strerror(socket_last_error($socket));
            
            $exp_msg  = _("Warning! Can't connect with Server")." ($address:$port).";
            $exp_msg .= ' '._('Reason').': '.$last_socket_error;
                        
            socket_close($socket);
                       
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $cmd    = 'connect id="1" type="web"' . "\n";
        $result = @socket_write($socket, $cmd, strlen($cmd));

        if ($result === FALSE)
        {        
            $last_socket_error = socket_strerror(socket_last_error($socket));
            
            $exp_msg  = _('Warning! Failed to send data to Server')." ($address:$port).";
            $exp_msg .= ' '._('Reason').': '.$last_socket_error;
                        
            socket_close($socket);
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
               
        $out = @socket_read($socket, 2048);
        
        //Testing connection        
        if (strncmp($out, 'ok id="1"', 9) != 0) 
        {                       
            $exp_msg  = _('Error connecting to server').' ...';
            $exp_msg .= ' '._('Socket error').": ". _('Is OSSIM server running at')." $address:$port?";
                  
            socket_close($socket);

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }


        if ($asset_type == 'sensors' || $asset_type == 'servers')
        {            
            //Reloading assets
            $cmd  = 'reload-'.$asset_type.' id="2"' . "\n";
            $out = '';
        
            @socket_write($socket, $cmd, strlen($cmd));
            $out = @socket_read($socket, 2048);
        
            if (strncmp($out, 'ok id="2"', 9) != 0) 
            {           
                $exp_msg  = _('Bad response from Server').' ...';
                $exp_msg .= ' '._('Socket error').": ". _('Is OSSIM server running at')." $address:$port?";
                      
                socket_close($socket);
                
                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
            }
        }
        
        @socket_close($socket);
        
        if ($asset_type == 'sensors')
        {
            if (Session::show_entities())
            {
                // Restart ossim-server
                exec('sudo /etc/init.d/ossim-server restart > /dev/null 2>&1 &');
            }
            
            
            /********************************
             *********** Frameworkd *********
             ********************************/
                
            try
            {
                $frcon = new Frameworkd_socket();
                $frcon->write("refresh_sensor_list");
            }
            catch(Exception $e)
            {
                $exp_msg = _('An error occurred while updating Agent cache...');

                Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
            }
        }
        elseif ($asset_type == 'servers')
        {
            // Restart ossim-server
            exec('sudo /etc/init.d/ossim-server restart > /dev/null 2>&1 &');
        }	
    }

  
    /**
    * This function checks if the av_component could be deleted
    *
    * @param object  $conn   Database access object
    * @param string  $id     Av_component ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean      
    */   
    abstract protected static function can_delete($conn, $id); 
          
    
    /**
    * This function returns the av_component object with Av_component ID equal to $id
    *    
    * @param object  $conn   Database access object
    * @param string  $id     Av_component ID
    * @param boolean $cache  [Optional] Use cached information
    *
    * @throws Exception  If a connection error occurred
    *
    * @return object      
    */   
    abstract public static function get_object($conn, $id, $cache = FALSE);
    
    
    /**
    * This function checks if av_component exists into database
    *
    * @param object  $conn  Database access object
    * @param string  $id    Av_component ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean      
    */ 
    abstract public static function is_in_db($conn, $id);
    
    
    /**
    * This function checks if the av_component exists and it is allowed
    *
    * @param object  $conn  Database access object
    * @param string  $id    Av_component ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean      
    */
    abstract public static function is_allowed($conn, $id); 
    
 
    /**
    * This function deletes an av_component from database
    *
    * @param object   $conn  Database access object
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean      
    */ 
    abstract public function delete_from_db($conn); 
      
    
    /**
    * This function returns a filtered av_component list
    *
    * @param object   $conn     Database access object
    * @param array    $filters  [Optional] SQL statements (WHERE, LIMIT, ORDER BY ...)
    * @param boolean  $cache    [Optional] Use cached information
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array      
    */ 
    abstract public static function get_list($conn, $filters = array(), $cache = FALSE);
}

/* End of file av_component.inc */
/* Location: ../include/classes/av_component.inc */ 