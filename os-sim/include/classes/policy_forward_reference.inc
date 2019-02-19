<?php
/**
* Policy_forward_reference.inc
*
* File Policy_forward_reference.inc is used to:
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

class Policy_forward_reference 
{ 
    public $policy_id;


    public $child_id;


	public $parent_id;


    public $priority;


    function __construct($policy_id, $child_id, $parent_id, $priority) 
    {
        $this->policy_id = $policy_id;
        $this->child_id  = $child_id;
        $this->parent_id = $parent_id;
		$this->priority  = $priority;
    }
	

    function get_policy_id() 
    {
        return $this->policy_id;
    }
	
	
    function get_child_id() 
    {
        return $this->child_id;
    }
	
	
	function get_parent_id() 
	{
        return $this->parent_id;
    }
	
	
	function get_priority()
	{
        return $this->priority;
    }
	
	
    static function get_list($conn, $args)
    {
        $list  = array();
        $query = ossim_query("SELECT HEX(policy_id) AS policy_id, HEX(child_id) AS child_id, HEX(parent_id) AS parent_id, priority from policy_forward_reference WHERE 1=1 $args");
        
        $rs = $conn->Execute($query);
        
        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else 
        {
            while (!$rs->EOF) 
            {
                $list[] = new self($rs->fields["policy_id"], $rs->fields["child_id"], $rs->fields["parent_id"], $rs->fields["priority"]);
                
                $rs->MoveNext();
            }
        }
        
        return $list;
    }
    
	
    static function insert($conn, $policy_id, $child_id, $parent_id, $priority)
    {
		$sql    = "INSERT INTO policy_forward_reference (policy_id, child_id, parent_id, priority) VALUES (UNHEX(?), UNHEX(?), UNHEX(?), ?)";
		$params = array(
			$policy_id, 
			$child_id, 
			$parent_id, 
			$priority
		);
		
		if (!$conn->Execute($sql, $params)) 
		{
			Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
		}
    }
    
	
    static function delete_by_policy($conn, $policy_id)
    {
        $sql    = "DELETE FROM policy_forward_reference WHERE policy_id = UNHEX(?)";
        $params = array(
            $policy_id
        );
        
        if ($conn->Execute($sql, $params) === false) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            exit;
        }
    }


    public static function delete_by_parent($conn, $child, $parent)
    {
        $sql    = "DELETE FROM policy_forward_reference WHERE child_id = UNHEX(?) AND parent_id = UNHEX(?)";
        $params = array(
            $child,
            $parent
        );

        if ($conn->Execute($sql, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            exit;
        }
    }
}

/* End of file policy_forward_reference.inc */
/* Location: ../include/classes/policy_forward_reference.inc */
