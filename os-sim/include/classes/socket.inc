<?php
/**
* socket.inc
*
* File socket.inc is used to:
*   - To manage PHP socket
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
* @package    ossim-framework\Frameworkd
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/

require_once 'av_config.php';


/**
* Example:
*
* try
* {
*     $s = new Socket();
*     
*     if($s->write('nagios action="add" type="host" list="192.168.1.150 appLIanCE|192.168.1.119 albertossim"'))
*         echo "OK! :) <br>";
*     else
*         echo "oops.. :( <br>";
* }
* catch(Exception $e)
* {
*     echo $e->getMessage()
* }
*
*/


/**
* Socket Class
*
* Class for managing PHP socket
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/

class Socket
{
    /**
    * Socket
    *
    * @var object
    * @access public
    */ 
    public $socket;    
    
    
    /**
    * Host IP
    *
    * @var string
    * @access public
    */ 
    public $address;
    
    
    /**
    * Port to connect
    *
    * @var string
    * @access public
    */ 
    public $port; 
    
    
    /**
    * Last message received
    *
    * @var string
    * @access public
    */     
    public $msg_r;
    
    
    /**
    * Last message sent
    *
    * @var string
    * @access public
    */ 
    public $msg_w;
    
    
    /**
    * Class constructor
    *
    * This function sets up the class
    *
    * @param string  $address  Host IP
    * @param string  $port     Port
    *
    * @throws Exception  If an error occurred
    * 
    */   
    public function __construct($address, $port)
    {
        $port = intval($port);
               
        if (Asset_host_ips::valid_ip($address))
        {
            $this->address = $address;
            $this->port    = $port;
            
            $this->connect();
        }
        else
        {
            $exp_msg = _('Error! Host address is not valid');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }
    }

    
    /**
    * This function sets connection
    *
    * @throws Exception  If an error occurred
    *
    * @return void      
    */ 
    public function connect()
    {
        $this->socket = @socket_create(AF_INET, SOCK_STREAM, 0);
                        
        if ($this->socket === FALSE) 
        {         
            $exp_msg = _('Warning! Unable to create a connection with socket');
                    
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        @socket_set_block($this->socket);
        @socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 5, 'usec' => 0));
        @socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 5, 'usec' => 0));

        $result = @socket_connect($this->socket, $this->address, $this->port);

        if ($result === FALSE)
        {
            $last_socket_error = socket_strerror(socket_last_error($this->socket));
            
            $exp_msg  = _("Warning! Can't connect with socket").' ('.$this->address.':'.$this->port.').';
            $exp_msg .= ' '._('Reason').': '.$last_socket_error;

            $this->close();
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }       
    }
    
    
    /**
    * This function sends a message
    *
    * @param string  $msg  Message to send
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean      
    */ 
    public function write($msg)
    {
        $len_w = @socket_write($this->socket, $msg, strlen($msg));

        if ($len_w === FALSE) 
        {
            $last_socket_error = socket_strerror(socket_last_error($this->socket));
            
            $exp_msg  = _('Warning! Failed to send data to socket').' ('.$this->address.':'.$this->port.').';
            $exp_msg .= ' '._('Reason').': '.$last_socket_error;

            $this->close();

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $this->msg_w = $msg;
        
        if (strlen($msg) == $len_w)
        {
            return TRUE;   
        }

        return FALSE;
    }
    
    
    /**
    * This function reads a message from socket
    *
    * @param integer  $bytes  [Optional] Number of bytes to red
    *
    * @throws Exception  If an error occurred
    *
    * @return boolean      
    */ 
    public function read($bytes = 512)
    {
        $msg = @socket_read($this->socket, $bytes, PHP_BINARY_READ);        
        
        if ($this->msg_r === FALSE) 
        {        
            $last_socket_error = socket_strerror(socket_last_error($this->socket));
            
            $exp_msg  = _('Warning! Failed to read data from socket').' ('.$this->address.':'.$this->port.').';
            $exp_msg .= ' '._('Reason').': '.$last_socket_error;
                        
            $this->close();
            
            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $this->msg_r = $msg;       
        
        if (strlen($msg) > 0)
        {         
            return TRUE;
        }
        
        return FALSE;
    }


    /**
    * This function closes the socket
    *
    * @return void      
    */ 
    public function close()
    {
        @socket_close($this->socket);
    }
}

/* End of file socket.inc */
/* Location: ../include/classes/socket.inc */ 
