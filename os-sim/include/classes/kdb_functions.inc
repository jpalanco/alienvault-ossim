<?php
/**
* KDB_Functions.inc
*
* File KDB_Functions.inc is used to:
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
* @package    ossim-framework\KDB
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


/**
* Function list:
* - __construct
* - __destruct
* - is_in_inventory
* - not_in_inventory
* - is_in_hostgroup
* - not_in_hostgroup
* - is_in_netgroup
* - not_in_netgroup
* - empty
* - not_empty
* - contains
* - not_contains
* - is
* - is_not
*/


$aliases = array(
	"low"    => 1,
	"medium" => 4,
	"hight"  => 7			
);


class KDB_Functions
{

	private $db;


	private $conn;
		
	
	public function __construct()
	{        
		/* Connect to db */
		$this->db   = new Ossim_db();
		$this->conn = $this->db->connect();		
	}
	
	public function __destruct()
    {		
		$this->db->close();		
    }
	
	
	public function is_in_inventory($elem)
	{
        if (self::is_in_hosts($elem))
        {
            return TRUE;
        }
        elseif (self::is_in_networks($elem))
        {
            return TRUE;
        }
        elseif (self::is_in_hostgroups($elem))
        {
            return TRUE;
        }
        elseif (self::is_in_netgroups($elem))
        {
            return TRUE;
        }
        
        return FALSE;
    }
	

	public function not_in_inventory($elem)
	{	
		$result = self::is_in_inventory($elem);
		
		return !$result;	
	}
	
	
	public function is_in_hosts($elem)
	{	
        $elem = str_replace('$', '_', $elem);
        $elem = $_SESSION['_kdb'][$elem];
        
        // Check if $elem is not empty
        if (empty($elem)) 
        {
            return FALSE;
        }
        
        $ids = array();
        
        if (is_ip($elem))  // IP
        {
            $ids = Asset_host::get_id_by_ips($this->conn, $elem);
            
        }
        else  //Hostname
        {
            $ids = Asset_host::get_id_by_name($this->conn, $elem);
        }
        
        return (count($ids) > 0);
	}
	

	public function not_in_hosts($elem)
	{	
		$result = self::is_in_hosts($elem);
		
		return !$result;	
	}
	
	
	public function is_in_networks($elem)
	{
        $elem = str_replace('$', '_', $elem);
        $elem = $_SESSION['_kdb'][$elem];
        
        // Check if $elem is not empty
        if (empty($elem)) 
        {
            return FALSE;
        }
        
		$ids = array();
		
		
        if (is_ip($elem) || is_cidr($elem))  // IP or CIDR
        { 
            if (is_ip($elem))
            {
                $elem = $elem . '/32';
            }
            
            $ids = Asset_net::is_cidr_in_my_nets($this->conn, $elem);
        }
        else  // Net name
        {  
            $ids = Asset_net::get_id_by_name($this->conn, $elem);
        }

        return (count($ids) > 0);

	}
	

	public function not_in_networks($elem)
	{	
		$result = self::is_in_networks($elem);
		
		return !$result;	
	}
	
	
	public function is_in_hostgroup($elem, $hg)
	{
        $elem = str_replace('$', '_', $elem);
        $host = $_SESSION['_kdb'][$elem];
        
        // Check if $hg and $host are not empty
        
        if (empty($host) || empty($hg)) 
        {
            return FALSE;
        }
        
        if (is_ip($host))  // IP
        {
            $h_ids = Asset_host::get_id_by_ips($this->conn, $elem);
            
        }
        else  //Hostname
        {
            $h_ids = Asset_host::get_id_by_name($this->conn, $elem);
        }
        
        $h_id   = key($h_ids);
        
        $hg_ids = Asset_group::get_id_by_name($this->conn, $hg);
        
        $hg_id = key($hg_ids);
        
        if (empty($h_id) || empty($hg_id))
        {
            return FALSE;
        }
        
        // Check if $host belongs to $hg hostgroup
        if (Asset_group::is_host_in_group($this->conn, $h_id, $hg_id)) 
        {
            return TRUE;
        }
        
        return FALSE;

	}
	

	public function not_in_hostgroup($elem, $hg)
	{
	
		$result = self::is_in_hostgroup($elem, $hg);
		
		return !$result;	
	}
	

    public function is_in_netgroup($elem, $ng)
    {
        $elem = str_replace('$', '_', $elem);
        $net  = $_SESSION['_kdb'][$elem];
        
        // Check if $ng and $net are not empty
        
        if (empty($ng) || empty($net)) 
        {
            return FALSE;
        }
        
        
        // Check if $net belongs to $ng netgroup
        if (Net_group::isNetInGroup($this->conn, $ng, $net)) 
        {
            return TRUE;
        }
        
        return FALSE;
    }
	

	public function not_in_netgroup($elem, $ng)
	{	
		$result = self::is_in_netgroup($elem, $ng);
		
		return !$result;	
	}
	
	
	public function is_in_hostgroups($elem)
	{
        $elem = str_replace('$', '_', $elem);
        $host = $_SESSION['_kdb'][$elem];
        
        // Check if $host is not empty
        
        if (empty($host)) 
        {
            return FALSE;
        }
        
		if (is_ip($host)) //IP
		{
			$ctx = Session::get_default_ctx();
            $ids = Asset_host::get_id_by_ips($this->conn, $host, $ctx);
        }
		else
		{
			$ids = Asset_host::get_id_by_name($this->conn, $host);
		}
		
		$id = key($ids);
		
				       
        if (empty($id))
        {
            return FALSE;
        }
		
		// Check if $host belongs to $hg hostgroup
        if (Asset_group::is_host_in_group($this->conn, $id)) 
        {
            return TRUE;
        }
        
        return FALSE;
	}
	

	public function not_in_hostgroups($elem)
	{	
		$result = self::is_in_hostgroups($elem);
		
		return !$result;	
	}

	
    public function is_in_netgroups($elem)
    {
        $elem = str_replace('$', '_', $elem);
        $net  = $_SESSION['_kdb'][$elem];
        
        // Check if $net is not empty
        
        if ( empty($net)) 
        {
            return FALSE;
        }
        
        
        if (is_cidr($net))  // CIDR
        { 
            $ctx = Session::get_default_ctx();
            $ids = Asset_net::get_id_by_ips($this->conn, $net, $ctx);
        }
		else
		{
			$ids = Asset_net::get_id_by_name($this->conn, $net);
		}
		
		$id = key($ids);
      
		if (empty($id))
		{
    		return FALSE;
		} 
		
        // Check if $net belongs to any netgroup        
        if (Net_group::isNetInAnyGroup($this->conn, $id)) 
        {
            return TRUE;
        }
        
        return FALSE;
    }

	
	public function not_in_netgroups($elem)
	{	
		$result = self::is_in_netgroups($elem);
		
		return !$result;	
	}
	
	
	public function is_in_assetos($elem)
	{
        $elem = str_replace('$', '_', $elem);
        $elem = $_SESSION['_kdb'][$elem];
        
        if (empty($elem)) 
		{
            return FALSE;
        }
		
		$sql    = "SELECT COUNT(*) as total FROM host_properties WHERE host_properties.value LIKE ?";
		$params = array ("%$elem%");
        
        $rs = $this->conn->Execute ($sql, $params);
        
		if (!$rs) 
		{
			return FALSE;
		}

		return ($rs->fields ['total'] > 0) ? TRUE : FALSE;
	}
	

	public function not_in_assetos($elem)
	{	
		$result = self::is_in_assetos($elem);
		
		return !$result;	
	}
	
	
	public function is_in_assetservices($elem)
	{
        $elem = str_replace('$', '_', $elem);
        $elem = $_SESSION['_kdb'][$elem];
        
        if (empty($elem)) 
		{
            return FALSE;
        }

		if (is_numeric($elem))
		{
			$sql    = "SELECT COUNT(*) as total FROM host_services WHERE host_services.port = ?";
			$params = array ($elem);
		}
		elseif (is_string($elem))
		{
			$sql    = "SELECT COUNT(*) as total FROM host_services WHERE host_services.service LIKE ?";
			$params = array ("$elem");
		}
		else
		{
			return FALSE;
		}
		
		$rs = $this->conn->Execute ($sql, $params);
		
		if (!$rs)
		{
			return FALSE;
		}

		return ($rs->fields ['total'] > 0) ? TRUE : FALSE;
	}

	
	public function not_in_assetservices($elem)
	{	
		$result = self::is_in_assetservices($elem);
		
		return !$result;	
	}
	
	
	public function is_empty($elem)
	{		
		$elem = str_replace('$', '_', $elem);
        $elem = $_SESSION['_kdb'][$elem];
		
		if ($elem == '')
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}	
	}
	
	
	public function not_empty($elem)
	{	
		$result = self::is_empty($elem);
		
		return !$result;	
	}
	
	
	public function contains($elem, $val)
	{	
		$elem = str_replace('$', '_', $elem);
        $elem = $_SESSION['_kdb'][$elem];

		$elem = strtoupper($elem);
		$val  = strtoupper($val);
		
		if (strpos($elem,$val) === FALSE)
		{
			return FALSE;
		}

		return TRUE;	
	}
	
	
	public function not_contains($elem, $val)
	{	
		$result = self::contains($elem, $val);
		
		return !$result;	
	}
	
	
	public function more_than($elem, $val)
	{		
		if (is_string($val))
		{
			global $aliases;
			$val = $aliases[$val];
		}
			
		$elem = str_replace('$', '_', $elem);
        $elem = $_SESSION['_kdb'][$elem];
		
		if (trim($elem) > trim($val))
		{
			return TRUE;
		}
		
		return FALSE;	
	}
	
	
	public function less_than($elem, $val)
	{	
		if (is_string($val))
		{
			global $aliases;
			$val = $aliases[$val];
		}
		
		$elem = str_replace('$', '_', $elem);
        $elem = $_SESSION['_kdb'][$elem];
				
		if (trim($elem) < trim($val))
		{
			return TRUE;
		}
		
		return FALSE;	
	}
	
	
	public function is($elem, $val)
	{	
		$elem = str_replace('$', '_', $elem);
        $elem = $_SESSION['_kdb'][$elem];
		
		if (strcasecmp(trim($elem),trim($val)) === 0)
		{
			return TRUE;
		}
		
		return FALSE;	
	}
	
	
	public function is_not($elem, $val)
	{	
		$result = self::is_not($elem, $val);
		
		return !$result;	
	}	
}

/* End of file kdb_functions.inc */
/* Location: ../include/classes/kdb_functions.inc */
