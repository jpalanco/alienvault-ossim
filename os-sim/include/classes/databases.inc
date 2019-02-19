<?php
/**
* databases.inc
*
* File Databases.inc is used to:
*   - To manage databases
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
* @package    ossim-framework\Databases
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/



class Databases 
{    
    public $id;
    
    public $name;
    
    public $ip;
    
    public $port;
    
    public $user;
    
    public $pass;
    
    public $icon;
	
    public function __construct($id, $name, $ip, $port, $user, $pass, $icon, $foundrows = 0) 
    {
        $this->id   = $id;
        $this->name = $name;
        $this->ip   = $ip;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
        $this->icon = $icon;
        $this->foundrows = $foundrows;
    }
    
	
    public function get_id() 
    {
        return $this->id;
    }
    
    
    public function get_name() 
    {
        return $this->name;
    }
    
	
    public function get_ip() 
    {
        return $this->ip;
    }
	
	
    public function get_port() 
    {
        return $this->port;
    }
    
	
    public function get_user() 
    {
        return $this->user;
    }
    
	
    public function get_pass() 
    {
        return $this->pass;
    }
	
	
    public function get_icon() 
    {
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
            $html_icon = "<img class='asset_icon w32' src='data:image/png;base64,".base64_encode($this->get_icon())."'/>";
        }
        
        return $html_icon;
    }
    
	
    public function get_foundrows() 
    {
        return $this->foundrows;
    }
	
    
    public static function get_list($conn, $args = '') 
    {
        $list = array();
        $iffoundrows = preg_match("/LIMIT/i", $args) ? TRUE : FALSE;
        $uuid        = Util::get_encryption_key();
		$select      = ($iffoundrows) ? "SQL_CALC_FOUND_ROWS * , HEX(ip) AS ip, AES_DECRYPT(pass,'$uuid') AS pass_decrypt" : "*, HEX(ip) AS ip, AES_DECRYPT(pass,'$uuid') AS pass_decrypt";
        
		$query = ossim_query("SELECT $select FROM `databases` $args");        
        $rs    = $conn->Execute($query);
        
		if (!$rs) 
		{
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
		else 
		{
            $foundrows = 0;
            if ($iffoundrows) 
            {
                $rf = $conn->Execute("SELECT FOUND_ROWS() AS total");
                
                if (!$rf) 
                {
                    Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                }
                else 
                {
                    $foundrows = $rf->fields['total'];
                }
            }
            
            while (!$rs->EOF) 
            {
                $list[] = new self(
                    $rs->fields["id"],
                    $rs->fields["name"], 
    				inet_ntop (pack("H*", $rs->fields["ip"])),
    				$rs->fields["port"], 
    				$rs->fields["user"], 
    				(empty($rs->fields['pass_decrypt'])) ? $rs->fields['pass'] : $rs->fields['pass_decrypt'], 
    				$rs->fields["icon"], 
    				$foundrows
    			);
    				
                $rs->MoveNext();
            }
        }
        
        return $list;
    }
	
    
    public static function insert($conn, $name, $ip, $port, $user, $pass, $icon) 
    {        
		$uuid     = Util::get_encryption_key();
		$q_pass   = (!empty($pass)) ? "AES_ENCRYPT(?,'$uuid')" : "?";		
		
		$params   = array(
            $name,
            $ip,
            $port,
            $user,
            $pass,
            $icon
        );
		
		$query = "INSERT INTO `databases` (name, ip, port, user, pass, icon) VALUES (?, inet6_aton(?), ?, ?, ".$q_pass.", ?)";
		
        if (!$conn->Execute($query, $params)) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        $infolog = array($name, '['.$ip.']');
        
        Log_action::log(79, $infolog);
    }
	
	
    public static function update($conn, $db_id, $name, $ip, $port, $user, $pass, $icon) 
    {
        if (Util::is_fake_pass($pass))
		{
			$params = array(				
				$ip,
				$port,
				$user			
			);
			
			$query = "UPDATE `databases` SET ip = INET6_ATON(?), port = ?, user = ?";
			
		}
		else
		{
			$uuid   = Util::get_encryption_key();
			$q_pass = (!empty($pass)) ? " AES_ENCRYPT(?,'$uuid')" : "?";
			
			$params  = array(                
                $ip,
                $port,
                $user,
                $pass              
            );
			
			$query = "UPDATE `databases` SET ip = INET6_ATON(?), port = ?, user = ?, pass = ".$q_pass;						
		}
		
		
		if (!empty($icon))
		{
    		$params[] = $icon;
    		$query .= ", icon = ? ";
		}
		
		
		$params[] = $db_id;
		$query .= "WHERE id = ?";
		
		
        
		if (!$conn->Execute($query, $params)) 
		{
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        $infolog = array($name, '['.$ip.']');
        
        Log_action::log(80, $infolog);
    }
    
    
    /**
    * This function deletes external database from database
    *
    * @param object   $conn   Database access object
    * @param integer  $id     Database ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean      
    */ 
    public static function delete($conn, $id) 
    { 	
    	Ossim_db::check_connection($conn);
    	         	
    	if ($db_list = self::get_list($conn, "WHERE id = '$id'"))
    	{
    		$db   = array_shift($db_list);    		
    		$name = $db->get_name();    	
    	}
    	
    	$query  = "DELETE FROM `databases` WHERE id = ?";
                
        $params = array($id);
          
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
                
        
        if (!empty($name))
        {
            if (preg_match("/snort\_restore/", $name)) 
            {
                $conn->Execute("DROP database $name");
            }
            
            
            $infolog = array($name);
            
            Log_action::log(81, $infolog);
        }
                
        return TRUE;
    }
    
    
    /**
    * This function deletes database icon from database
    *
    * @param object   $conn   Database access object
    * @param integer  $id     Database ID
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean      
    */ 
    public static function delete_icon($conn, $id)
    {       
        Ossim_db::check_connection($conn);
                
        $query  = 'UPDATE `databases` SET icon = NULL WHERE id = ?';
        $params = array($id);
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
                        
        return TRUE;    
    }	
}

/* End of file databases.inc */
/* Location: ../include/classes/databases.inc */
