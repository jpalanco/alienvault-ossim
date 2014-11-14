<?php
/**
* remote_interface.inc
*
* File remote_interface.inc is used to:
*   - To manage remote interfaces
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
* Remote_interface Class
*
* Class for managing remote interfaces
*
* @package    ossim-framework\Alienvault_Components
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ Alienvault web page
*/

class Remote_interface 
{
    
    /**
    * Remote interface ID
    *
    * @var string
    * @access private
    */ 
    private $id;
        
    /**
    * Remote interface IP
    *
    * @var string
    * @access private
    */ 
    private $ip;
    
    /**
    * Remote interface name
    *
    * @var string
    * @access private
    */ 
    private $name;
    
    /**
    * Remote interface status
    *
    * @var integer
    * @access private
    */ 
    private $status; 
    
	
	/**
    * Class constructor
    *
    * This function sets up the class
    *
    * @param string   $id          Remote interface ID
    * @param string   $ip          Remote interface IP
    * @param string   $name        Remote interface name
    * @param integer  $status      Remote interface status (enabled or disabled)
    */
    public function __construct($id, $ip, $name, $status) 
    {
        $this->id        = $id;
        $this->ip        = $ip;
        $this->name      = $name;
        $this->status    = $status;        
    }
    
    
    /**
    * This function returns the remote interface ID
    *
    * @return string
    */  
    public function get_id() 
    {
        return $this->id;
    }
	
	
	/**
    * This function returns the remote interface IP
    *
    * @return string
    */ 
    public function get_ip() 
    {
        return $this->ip;
    }
	
	
	/**
    * This function returns the remote interface name
    *
    * @return string
    */ 
    public function get_name() 
    {
        return $this->name;
    }
	
	
	/**
    * This function returns the remote interface status
    *
    * @return integer
    */ 
    public function get_status() 
    {
        return $this->status;
    }
	
	
    /**
    * This function returns a filtered remote interface list
    *
    * @param object   $conn    Database access object
    * @param string   $args   [Optional] SQL statements  
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array      
    */ 
    public static function get_list($conn, $args = '') 
    {
        Ossim_db::check_connection($conn);
        
        $list  = array();
        $total = 0;        
        
        $iffoundrows = preg_match('/LIMIT/i', $args) ? TRUE : FALSE;
        
		$select = ($iffoundrows) ? "SQL_CALC_FOUND_ROWS *, INET6_NTOP(ip) AS ri_ip" : "*, INET6_NTOP(ip) AS ri_ip";
        $query  = ossim_query("SELECT $select FROM `web_interfaces` $args");
		
		$conn->SetFetchMode(ADODB_FETCH_ASSOC);
		$rs = $conn->Execute($query);
				
		if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        	 
        if ($iffoundrows) 
        {
            $total = Ossim_db::get_found_rows($conn, $query, FALSE);       
        } 
        else
        {
            $total = $rs->RecordCount();                        
        }       
        
        while (!$rs->EOF) 
        {
            $list[] = new Remote_interface($rs->fields['id'],
                $rs->fields['ri_ip'], 
                $rs->fields['name'], 
                $rs->fields['status']
               );
            
            $rs->MoveNext();
        }        
        
        return array($list, $total);
    }
	
    
    /**
    * This function returns a remote interface object
    *
    * @param object   $conn   Database access object
    * @param string   $id     Remote interface ID  
    *
    * @throws Exception  If a connection error occurred
    *
    * @return object      
    */ 
    public static function get_object($conn, $id)
    {        
        Ossim_db::check_connection($conn);
        
        $ri = NULL;
        
        $params = array($id);
        $query  = "SELECT *, INET6_NTOP(ip) AS ri_ip FROM `web_interfaces` WHERE id = ?";
                
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
                
        if (!$rs->EOF) 
        {      
            $ri = new Remote_interface($rs->fields['id'],
                $rs->fields['ri_ip'], 
                $rs->fields['name'], 
                $rs->fields['status']);
        }
        
        return $ri;
    }
    
    
    /**
    * This function inserts a new remote interface in database
    *
    * @param object   $conn     Database access object        
    * @param string   $ip       Remote interface IP
    * @param string   $name     Remote interface name
    * @param integer  $status   Remote interface status (enabled or disabled)
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean      
    */ 
    public static function insert($conn, $ip, $name, $status) 
    {
        Ossim_db::check_connection($conn);
                	
		$params = array($ip, $name, $status);
		$query  = "INSERT INTO `web_interfaces` (ip, name, status) VALUES (INET6_PTON(?), ?, ?)";	
		
		
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        $infolog = array($name, '['.$ip.']');
        
        Log_action::log(95, $infolog);
        
        return TRUE;
    }
	
	
	/**
    * This function updates a remote interface
    *
    * @param object   $conn     Database access object
    * @param string   $id       Remote interface ID  
    * @param string   $ip       Remote interface IP
    * @param string   $name     Remote interface name
    * @param integer  $status   Remote interface status (enabled or disabled)
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean      
    */ 
    public static function update($conn, $id, $ip, $name, $status) 
    {
        Ossim_db::check_connection($conn);
        
        $params = array($name, $ip, $status, $id);
        $query  = "UPDATE `web_interfaces` SET name = ?, ip = inet6_pton(?), status = ? WHERE id = ?";
        
		$rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        $infolog = array($name, '['.$ip.']');
        
        Log_action::log(96, $infolog);
        
        return TRUE;
    }
    
    
    /**
    * This function deletes a remote interface
    *
    * @param object   $conn   Database access object
    * @param string   $id     Remote interface ID  
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean      
    */ 
    public static function delete($conn, $id) 
    {
    	Ossim_db::check_connection($conn);
    	
    	$params = array($id);
    	$query  = "DELETE FROM `web_interfaces` WHERE id = ?";
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        $infolog = array($id);
        
        Log_action::log(97, $infolog);
    }	
}


/* End of file remote_interfaces.inc */
/* Location: ../include/classes/remote_interfaces.inc */