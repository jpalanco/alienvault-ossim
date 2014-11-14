<?php
/**
* Policy_target_reference.inc
*
* File Policy_target_reference.inc is used to:
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
* - Policy_target_reference()
* - get_policy_id()
* - get_target_id()
* - get_list()
* - insert()
* - in_policy_target_reference()
* - delete()
*/

class Policy_target_reference 
{
    public $policy_id;
    
    public $target_id;
	
	
    function __construct($policy_id, $target_id) 
    {
        $this->policy_id = $policy_id;
        $this->target_id = $target_id;
    }
	
	
    function get_policy_id() 
    {
        return $this->policy_id;
    }
    
	
    function get_target_id() 
    {
        return $this->target_id;
    }
    
	
    function get_list($conn, $policy_id) 
    {
        $list   = array();
        $query  = "SELECT HEX(policy_id) AS policy_id, HEX(target_id) AS target_id FROM policy_target_reference WHERE policy_id = UNHEX(?)";
		$params = array($policy_id);
		
        if (!$rs = $conn->Execute($query, $params)) 
        {
            print $conn->ErrorMsg();
        } 
        else 
        {
            $list = array();
            
            while (!$rs->EOF) 
            {
                $list[] = new Policy_target_reference($rs->fields["policy_id"], $rs->fields["target_id"]);
                
                $rs->MoveNext();
            }
        }
        
        return $list;
        
    }
	
	
    function insert($conn, $target_list, $policy_id) 
    {
        foreach($target_list as $target_id) 
        {
			$target_id = (!strcasecmp($target_id, 'any')) ? 0 : $target_id;
            $sql       = "INSERT INTO policy_target_reference VALUES (UNHEX(?), UNHEX(?))";
            $params    = array(
                $policy_id,
                $target_id
            );
            
            if (!$conn->Execute($sql, $params)) 
            {
                die($conn->ErrorMsg());
            }
        }
    }
	
	
    function in_policy_target_reference($conn, $policy_id, $target_id) 
    {
        $sql    = "SELECT HEX(policy_id) AS policy_id, HEX(target_id) AS target_id FROM policy_target_reference WHERE policy_id = UNHEX(?) AND target_id = UNHEX(?)";
        $params = array(
            $policy_id,
            $target_id
        );
        
        if (!$rs = & $conn->Execute($sql, $params)) 
        {
            print $conn->ErrorMsg();
        }
        
        return $rs->fields[0];
    }
	
    function delete($conn, $policy_id) 
    {
        $sql    = "DELETE FROM policy_target_reference WHERE policy_id = UNHEX(?)";
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
