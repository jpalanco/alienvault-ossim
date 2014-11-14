<?php
/**
* Policy_risk_reference.inc
*
* File Policy_risk_reference.inc is used to:
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


/**
* Function list:
* - Policy_risk_reference()
* - get_id()
* - get_policy_id()
* - get_direction()
* - get_policy_id()
* - get_policy_id()
* - get_policy_id()
* - get_list()
* - insert()
* - delete()
*/

class Policy_risk_reference 
{
	public $id;
	
    public $policy_id;
    
	public $priority;
	
	public $reliability;
	
	
    function __construct($id, $policy_id, $priority, $reliability) 
    {	
		$this->id          = $id;		
        $this->policy_id   = $policy_id;
		$this->priority    = $priority;
		$this->reliability = $reliability;     
    }
    
		
    function get_id() 
    {
        return $this->id;
    }
	
	
    function get_policy_id() 
    {
        return $this->policy_id;
    }
	
	
	function get_priority() 
	{
        return $this->priority;
    }
	
	
	function get_reliability() 
	{
        return $this->reliability;
    }	
	

    function get_list($conn, $args) 
    {
        $list  = array();
        $query = ossim_query("SELECT *, HEX(policy_id) AS policy_id from policy_risk_reference WHERE 1=1 $args");

        if (!$rs = $conn->Execute($query)) 
        {
            print $conn->ErrorMsg();
        } 
        else 
        {
            while (!$rs->EOF) 
            {
                $list[] = new Policy_risk_reference($rs->fields["id"], $rs->fields["policy_id"], $rs->fields["priority"], $rs->fields["reliability"]);
                
                $rs->MoveNext();
            }
        }
        
        return $list;
    }
	
	
    function insert($conn, $policy_id, $priority, $reliability) 
    {
		$sql    = "INSERT INTO policy_risk_reference (policy_id, priority, reliability) VALUES (UNHEX(?), ?, ?)";
		$params = array(
			$policy_id,
			$priority,
			$reliability
		);
		
		if (!$conn->Execute($sql, $params)) 
		{
			die($conn->ErrorMsg());
		}

    }


	function delete($conn, $id) 
	{
        $sql    = "DELETE FROM policy_risk_reference WHERE id = ?";
        $params = array(
            $id
        );
        
        if ($conn->Execute($sql, $params) === false) 
        {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        
    }
	
	
    function delete_by_policy($conn, $policy_id)
     {
        $sql    = "DELETE FROM policy_risk_reference WHERE policy_id = UNHEX(?)";
        $params = array(
            $policy_id
        );
        
        if ($conn->Execute($sql, $params) === false) 
        {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        
    }
}
