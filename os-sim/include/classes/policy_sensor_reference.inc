<?php
/**
* Policy_sensor_reference.inc
*
* File Policy_sensor_reference.inc is used to:
*   - Task 1 (TO DO)
*   - Task 2 (TO DO)
*   - Task 3 (TO DO)
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
* @package    ossim-framework\Policy&Actions
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


class Policy_sensor_reference 
{
    public $policy_id;
    

    public $sensor_id;
	
	
    public function __construct($policy_id, $sensor_id) 
    {
        $this->policy_id = $policy_id;
        $this->sensor_id = $sensor_id;
    }
	
	
    public function get_policy_id() 
    {
        return $this->policy_id;
    }
    
	
    public function get_sensor_id() 
    {
        return $this->sensor_id;
    }
	
	
    public static function get_list($conn, $policy_id)
    {
        $list   = array();

        $params = array($policy_id);
        $query  = "SELECT HEX(policy_id) AS policy_id, HEX(sensor_id) as sensor_id FROM policy_sensor_reference WHERE policy_id = UNHEX(?)";
		
		$rs = $conn->Execute($query, $params);
		
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else 
        {            
            while (!$rs->EOF) 
            {
                $list[] = new self($rs->fields["policy_id"], $rs->fields["sensor_id"]);
                
                $rs->MoveNext();
            }
        }
        
        return $list;        
    }
	
	
    public static function insert($conn, $sensor_list, $policy_id)
    {
        foreach($sensor_list as $sensor_id) 
        {
			$sensor_id = (!strcasecmp($sensor_id, 'any')) ? 0 : $sensor_id;

			$params    = array($policy_id, $sensor_id);
            $query     = "INSERT INTO policy_sensor_reference VALUES (UNHEX(?), UNHEX(?))";
                        
            if ($conn->Execute($query, $params) === FALSE) 
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }            
        }
    }
	
	
    public static function in_policy_sensor_reference($conn, $policy_id, $sensor_id) 
    {
        $params = array($policy_id, $sensor_id);
        $query  = "SELECT HEX(policy_id) AS policy_id, HEX(sensor_id) as sensor_id FROM policy_sensor_reference WHERE policy_id = UNHEX(?) AND sensor_id = UNHEX(?)";
                
        $rs = $conn->Execute($query, $params);
        if (!$rs) 
        {
             Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        return $rs->fields[0];
    }
	
	
    public static function delete($conn, $policy_id)
    {
        $params = array($policy_id);
        $query  = "DELETE FROM policy_sensor_reference WHERE policy_id = UNHEX(?)";        
        
        if ($conn->Execute($query, $params) === FALSE) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }        
    }
	
	
    public static function get_policy_by_sensor($conn, $sensor_id)
    {
        $list   = array();

        $params = array($sensor_id);
        $query  = 'SELECT HEX(policy_id) AS policy_id, HEX(sensor_id) as sensor_id FROM policy_sensor_reference WHERE sensor_id = UNHEX(?)';            
        
        $rs = $conn->Execute($query, $params);
        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }  
        else 
        {
            $list = array();
            
            while (!$rs->EOF) 
            {
                $list[] = new self($rs->fields["policy_id"], $rs->fields["sensor_id"]);
                
                $rs->MoveNext();
            }
        }
        
        return $list;        
    }
	
	
    public static function exist_sensors($conn, $sensor_id)
    {
        $list  = array();
        
        $params = array($sensor_id);
        $query  = ossim_query("SELECT * FROM sensor WHERE 1=1 AND id = UNHEX(?)");
        
        $rs = $conn->Execute($query, $params);
        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else 
        {
            $list = array();
            
            while (!$rs->EOF) 
            {
                $list[] = $rs->fields['name'];
                
                $rs->MoveNext();
            }
        }
        
        return $list;      
    }
}

/* End of file policy_sensor_reference.inc */
/* Location: ../include/classes/policy_sensor_reference.inc */
