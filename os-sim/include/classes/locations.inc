<?php
/**
* locations.inc
*
* File locations.inc is used to:
*   - To manage sensor locations
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


class Locations 
{
    public $id;
    
    public $ctx;
    
    public $name;
    
    public $desc;
    
    public $location;
    
    public $lat;
    
    public $lon;
    
	public $cou;
    
	public $checks;
    
    public $foundrows;
  
    public function __construct($id, $ctx, $name, $desc, $location, $lat, $lon, $cou, $checks, $foundrows = 0) 
    {
        $this->id        = $id;
        $this->ctx       = $ctx;
        $this->name      = $name;
        $this->desc      = $desc;
        $this->location  = $location;
        $this->lat       = $lat;
        $this->lon       = $lon;
        $this->cou       = $cou;
		$this->checks    = $checks;
        $this->foundrows = $foundrows;
    }
    
    public function get_id() 
    {
        return $this->id;
    }

    public function get_ctx() 
    {
        return $this->ctx;
    }
	
    public function get_desc() 
    {
        return $this->desc;
    }
	
    public function get_name() 
    {
        return $this->name;
    }

    public function get_location() 
    {
        return $this->location;
    }

    public function get_lat() 
    {
        return $this->lat;
    }
	
    public function get_lon() 
    {
        return $this->lon;
    }

    public function get_country() 
    {
        return $this->cou;
    }
	
	public function get_checks() 
    {
        return $this->checks;
    }
	
    public function get_foundrows() 
    {
        return $this->foundrows;
    }
	
    public static function get_list($conn, $args = '') 
    {
        Ossim_db::check_connection($conn);
        
        $list = array();
        $iffoundrows = preg_match("/LIMIT/i", $args) ? TRUE : FALSE;
		$select      = ($iffoundrows) ? "SQL_CALC_FOUND_ROWS *, HEX(id) AS locid,HEX(ctx) AS ctx" : "*, HEX(id) AS locid, HEX(ctx) AS ctx";
        $query       = ossim_query("SELECT $select FROM `locations` WHERE 1=1 $args");
        
        if (Session::get_ctx_where() != '') 
        {
            $query = ossim_query("SELECT $select FROM `locations` WHERE ctx IN (".Session::get_ctx_where().") $args");
        }
		if (!$rs = & $conn->Execute($query)) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
		else 
		{
            $foundrows = 0;
            
            if ($iffoundrows) 
            {
                if (!$rf = & $conn->Execute("SELECT FOUND_ROWS() AS total"))
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
                $list[] = new Locations($rs->fields['locid'], $rs->fields['ctx'], $rs->fields['name'], $rs->fields['desc'],
                                        $rs->fields['location'], $rs->fields['lat'], $rs->fields['lon'], 
                                        $rs->fields['country'], $rs->fields['checks'], $foundrows);
                $rs->MoveNext();
            }
        }
        
        return $list;
    }
    
    public static function insert($conn, $ctx, $name, $desc, $location, $lat, $lon, $cou) 
    {        
        Ossim_db::check_connection($conn);
        
        $id = Util::uuid();
        
        $params = array(
            $id,
            $ctx,
            $name,
            $desc,
            $location,
            floatval($lat),
            floatval($lon),
            strtolower($cou),
            '111111'
        );
        
        
        $query    = "INSERT INTO `locations` (id, ctx, name, `desc`, location, lat, lon, country, checks) 
            VALUES (UNHEX(?), UNHEX(?), ?, ?, ?, ?, ?, ?, UNHEX(?))";
				
        if (!$conn->Execute($query, $params)) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
       
        return $id;
    }
	
    public static function update($conn, $id, $name, $desc, $location, $lat, $lon, $cou) 
    {        
        Ossim_db::check_connection($conn);
        
        $params   = array(
            $name,
            $desc,
            $location,
            floatval($lat),
            floatval($lon),
            strtolower($cou),
            $id
        );		
        $query    = "UPDATE `locations` SET name=?,`desc`=?, location=?, lat=?, lon=?, country=? WHERE id = UNHEX(?)";
				
        if (!$conn->Execute($query, $params)) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }      
    }
    
    public static function delete($conn, $id) 
    {
    	Ossim_db::check_connection($conn);
        
        $query = "DELETE FROM locations WHERE id = UNHEX(?)";
        if (!$conn->Execute($query, array($id))) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
    	$query = "DELETE FROM location_sensor_reference WHERE location_id = UNHEX(?)";        
        if (!$conn->Execute($query, array($id))) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }        
    }

    public static function get_related_sensors($conn, $id) 
    {
        Ossim_db::check_connection($conn);
        
        $list  = array();
        
		$query = ossim_query("SELECT s.name, INET6_NTOP(s.ip) AS ip, HEX(s.id) AS id 
            FROM sensor s, location_sensor_reference l WHERE s.id = l.sensor_id 
            AND l.location_id = UNHEX(?)");
		
        if (!$rs = & $conn->Execute($query, array($id))) 
        {
            return array();
        } 
		else 
		{
            while (!$rs->EOF) 
            {
                $list[] = array($rs->fields['id'], $rs->fields['name'], $rs->fields['ip']);
                
                $rs->MoveNext();
            }
        }
        return $list;
    }	
    
    public static function insert_related_sensor($conn, $id, $sensor_id) 
    {        
        Ossim_db::check_connection($conn);
        
        $params = array($id, $sensor_id);
        $query  = "REPLACE INTO `location_sensor_reference` (location_id,sensor_id) VALUES (UNHEX(?), UNHEX(?))";
				
        if (!$conn->Execute($query, $params)) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }     
    }    

    public static function delete_related_sensor($conn, $id, $sensor_id) 
    {
    	Ossim_db::check_connection($conn);
        
        $query = "DELETE FROM `location_sensor_reference` WHERE location_id=UNHEX(?) AND sensor_id = UNHEX(?)";
        
        if (!$conn->Execute($query, array($id, $sensor_id))) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }  
    } 

	public static function count_related_networks($conn, $id)
	{	
		Ossim_db::check_connection($conn);
        
        $params = array($id);        
        $query  = "SELECT count(s.net_id) AS total 
            FROM net_sensor_reference s, location_sensor_reference l 
            WHERE s.sensor_id = l.sensor_id 
            AND l.location_id = UNHEX(?);";
            
						
		if (!$rs = & $conn->Execute($query, $params)) 
		{
			Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return 0;
		}
		else
		{
			return $rs->fields['total'];
		}
	}
	
	public static function count_related_sensors($conn, $id)
	{	
		Ossim_db::check_connection($conn);
        
        $params = array($id);
        $query  = "SELECT count(l.sensor_id) AS total 
            FROM location_sensor_reference l 
            WHERE l.location_id = UNHEX(?) 
            AND l.sensor_id <> UNHEX('00000000000000000000000000000000');";
		
				
		if (!$rs = & $conn->Execute($query, $params)) 
		{
			Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return 0;
		}
		else
		{
			return $rs->fields['total'];
		}
	}
	
	public static function get_location_checks($conn, $id)
	{
		Ossim_db::check_connection($conn);
        
        $params = array($id);
        $query  = "SELECT HEX(l.checks) FROM locations l where id = UNHEX(?)";
						
		if (!$rs = & $conn->Execute($query, $params)) 
		{
			Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return 0;			
		}
		else
		{
			return substr($rs->fields[0], 0, 5);
		}	
	}
	
	public static function save_location_checks($conn, $id, $val)
	{		
		Ossim_db::check_connection($conn);
        
        $val .= "1"; //we need to fill the string with 1
	
        $params = array($val, $id);
		$query  = "UPDATE locations set checks = UNHEX(?) WHERE id = UNHEX(?)";
						
		if (!$conn->Execute($query, $params)) 
		{
			Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;			
		}
		else
		{
			return TRUE;
		}	
	}
	
    
	public static function get_name_by_id($conn, $id)
    {       
        Ossim_db::check_connection($conn);        
        
        $name = '';
        
        $params = array($id);        
        $query  = 'SELECT name FROM locations WHERE id = UNHEX(?)';
         
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        
        if ($rs->fields['name'] != '') 
        {
            $name = $rs->fields['name'];
        }
     
        return $name;   
    }
}

/* End of file locations.inc */
/* Location: ../include/classes/locations.inc */