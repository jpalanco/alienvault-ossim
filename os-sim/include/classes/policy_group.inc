<?php
/**
* Policy_group.inc
*
* File Policy_group.inc is used to:
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
* - Policy_group()
* - get_group_id()
* - get_name()
* - get_descr()
* - get_order()
* - get_foundrows()
* - get_list()
* - insert()
* - update()
* - delete()
* - swap_orders()
*/

class Policy_group 
{
    public $group_id;
	public $ctx;
    public $name;
    public $descr;
    public $order;
	public $permissions;
    public $foundrows;
	
	
    function __construct($group_id, $ctx, $name, $descr, $order, $permissions = 0, $foundrows = 0) 
    {
        $this->group_id    = $group_id;
		$this->ctx         = $ctx;
        $this->name        = htmlentities($name);
        $this->descr       = htmlentities($descr);
        $this->order       = $order;
		$this->permissions = $permissions;
        $this->foundrows   = $foundrows;
    }
	
	
    function get_group_id() 
    {
        return $this->group_id;
    }
	
	
    function get_ctx() 
    {
        return $this->ctx;
    }
	
	
	function get_name() 
	{
        return $this->name;
    }
	
	
    function get_descr() 
    {
        return $this->descr;
    }
	
	
    function get_order() 
    {
        return $this->order;
    }
	
	
	function get_permissions() 
	{
        return $this->permissions;
    }
	
	
    function get_foundrows() 
    {
        return $this->foundrows;
    }
	
	
    function get_list($conn, $ctx='', $args = "", $engine=false) 
    {
		//Gettings our ctxs
		$ctxs = Session::get_ctx_where();
		
		if ($ctxs != "") 
		{
			$ctxs = " AND ctx in ($ctxs)";
		}
		
		$ctx_filter  = (empty($ctx))? '' : "AND ctx=UNHEX('$ctx')" ;
		
		if($ctx_filter != '')
		{
			$query = ossim_query("SELECT count(*) as total FROM policy_group where 1=1 $ctx_filter $ctxs AND id = UNHEX('00000000000000000000000000000000')");
			
			$rs = & $conn->Execute($query);

			if($rs->fields["total"] == 0)
			{
				Policy_group::insert_default($conn, $ctx, $engine);			
			}
			
		}
		
        $list        = array();
        $iffoundrows = preg_match("/LIMIT/i", $args) ? true : false;
		$select      = "HEX(id) as id, HEX(ctx) as ctx, name, descr, `order`, HEX(permissions) as permissions";
        $select      = ($iffoundrows) ? "SQL_CALC_FOUND_ROWS " . $select : $select;
        $query       = ossim_query("SELECT $select FROM policy_group where 1=1 $ctx_filter $ctxs $args");
	
        if (!$rs = & $conn->Execute($query)) 
        {
            print $conn->ErrorMsg();
			
        } 
        else 
        {
		
            $foundrows = 0;
            
            if ($iffoundrows) 
            {
                if (!$rf = & $conn->Execute("SELECT FOUND_ROWS() as total")) 
                {
					print $conn->ErrorMsg();
				
				} 
				else 
				{
					$foundrows = $rf->fields["total"];
				}
            }

			while (!$rs->EOF) 
			{
                $list[] = new Policy_group($rs->fields["id"], $rs->fields["ctx"], $rs->fields["name"], $rs->fields["descr"], $rs->fields["order"], $rs->fields["permissions"], $foundrows);
                
                $rs->MoveNext();
            }
			
        }

        return $list;
    }
	
	
	function get_new_order($conn, $ctx)
	{
		//Gettings our ctxs
		$ctxs = Session::get_ctx_where();
		
		if ($ctxs != "") 
		{
			$ctxs = " AND ctx in ($ctxs)";
		}
		
		
		$query = ossim_query("SELECT max(policy_group.order) as max FROM policy_group where 1=1 $ctxs and ctx=UNHEX('$ctx')");
        
        if (!$rs = $conn->Execute($query)) 
        {
            print $conn->ErrorMsg();
        } 
        else 
        {
            $order = $rs->fields["max"] + 1;
        }
	
		return $order;
	}
	
	
    function insert($conn, $ctx, $name, $descr, $permissions = 0) 
    {
		
        $descr = htmlentities($descr);

		$id    = Util::uuid();
		$order = Policy_group::get_new_order($conn, $ctx);
				
        $sql = "INSERT INTO policy_group (`id`, `ctx`, `name`, `descr`, `order`, `permissions`) VALUES (UNHEX(?), UNHEX(?), ?, ?, ?, UNHEX(?))";
		
        $params = array(
			$id,
			$ctx,
            $name,
            $descr,
            $order,
			$permissions			
        );
        
        if (!$conn->Execute($sql, $params)) 
        {
            die($conn->ErrorMsg());
        }
		
		
        $infolog = array(
            $name,
            "(" . $descr . ")"
        );
        
        Log_action::log(46, $infolog);
		
		return $id;
    }
	
	
	function insert_default($conn, $ctx, $engine=false) 
	{
		
        $sql = "REPLACE INTO policy_group (`id`, `ctx`, `name`, `descr`, `order`, `permissions`) VALUES (UNHEX(?), UNHEX(?), ?, ?, ?, UNHEX(?))";
		
		if(!$engine)
		{
	        $params = array(
				'00000000000000000000000000000000',
				$ctx,
				"Default Policy Group",
				"Default Group Policy objects",
				0,
				0						
	        );
	    }
	    else
	    {
	        $params = array(
				'00000000000000000000000000000000',
				$ctx,
				"Engine Policies",
				"Engine Policies objects",
				0,
				0						
	        );
	    }
	    
        if (!$conn->Execute($sql, $params)) 
        {
            die($conn->ErrorMsg());
        }
		
    }
	
	
    function update($conn, $id, $name, $descr, $permissions = 0) 
    {
        $descr  = htmlentities($descr);
		$name   = htmlentities($name);
		
        $sql    = "UPDATE policy_group SET name=?, descr=?, permissions=UNHEX(?) WHERE id=UNHEX(?)";
        $params = array(
            $name,
            $descr,
			$permissions,
            $id
        );
		
        if (!$conn->Execute($sql, $params)) 
        {
            die($conn->ErrorMsg());
        }
        
        $infolog = array(
            $id,
            "($name - $descr)"
        );
        
        Log_action::log(48, $infolog);
    }
	
	
    function delete($conn, $id) 
    {

        $policygrp = Policy_group::get_list($conn, '', " AND id=UNHEX('$id')");
	
        if (count($policygrp) != 1) 
        {
            return;
        }
		
		$ctx = $policygrp[0]->get_ctx();
		
        $params = array(
            $id
        );
		
        $sql = "DELETE FROM policy_group WHERE id = UNHEX(?)";
        $conn->Execute($sql, $params);
		
        $policy_list = Policy::get_list($conn, " AND policy.group=UNHEX('$id')");
		
        foreach($policy_list as $policy) 
        {
            Policy::delete($conn, $policy->get_id());
        }
        
        $reorderpolicygrps = Policy_group::get_list($conn, $ctx, " AND policy_group.order >= 0 ORDER BY policy_group.order");
        $neworder = 0;
        
        foreach($reorderpolicygrps as $policy) 
        {
			$conn->Execute("UPDATE policy_group SET policy_group.order=$neworder WHERE id=UNHEX(?)", array($policy->get_group_id()));
			$neworder++;
        }
        
        $infolog = array(
            $id,
            "(" . $policygrp[0]->get_name() . ")"
        );
        
        Log_action::log(47, $infolog);
    }
	
    function swap_orders($conn, $ctx, $id1, $order1, $id2, $order2) 
    {
        $policy_list1 = Policy::get_list($conn, "AND policy.ctx=UNHEX('$ctx') AND policy.group=UNHEX('$id1') order by policy.order");
        $policy_list2 = Policy::get_list($conn, "AND policy.ctx=UNHEX('$ctx') AND policy.group=UNHEX('$id2') order by policy.order");
		
        if ($policy_list1[0] && $policy_list2[0])  // al least one policy per group
        { 
            // swap policy orders
            $minorder = $policy_list1[0]->get_order();
            foreach($policy_list2 as $policy) 
            {
                $sql    = "UPDATE policy SET policy.order=? WHERE id=UNHEX(?)";
                $params = array(
                    $minorder,
                    $policy->get_id()
                );
                
                if (!$conn->Execute($sql, $params))
                {
                    die($conn->ErrorMsg());
                }
                
                $minorder++;
            }
            
            foreach($policy_list1 as $policy) 
            {
                $sql    = "UPDATE policy SET policy.order=? WHERE id=UNHEX(?)";
                $params = array(
                    $minorder,
                    $policy->get_id()
                );
                
                if (!$conn->Execute($sql, $params))
                {
                    die($conn->ErrorMsg());
                }
                
                $minorder++;
            }
        }
        // swap policy group orders
        $sql = "UPDATE policy_group SET policy_group.order=? WHERE id=UNHEX(?)";
        $params = array(
            $order2,
            $id1
        );
        
        if (!$conn->Execute($sql, $params))
        {
            die($conn->ErrorMsg());
        }
        
        $sql    = "UPDATE policy_group SET policy_group.order=? WHERE id=UNHEX(?)";
        $params = array(
            $order1,
            $id2
        );
        
        if (!$conn->Execute($sql, $params))
        {
            die($conn->ErrorMsg());
        }
        
    }
	
	function reassing_orders($conn, $ctx) 
	{
		$query  = "SELECT HEX(id) as id FROM policy_group WHERE ctx=UNHEX(?) ORDER BY policy_group.order";
		$params = array($ctx);
		
		if (!$rs = & $conn->Execute($query, $params)) 
		{
            print $conn->ErrorMsg();
        } 
		else 
		{
			$order = 0;
            while (!$rs->EOF) 
			{
                $id = $rs->fields["id"];
                
				$params = array($order, $id);
				$conn->Execute("UPDATE policy_group SET policy_group.order=? WHERE id=UNHEX(?)", $params);
				
				$order++;
				$rs->MoveNext();
            }
        }

    }
    

    function get_min_order_by_ctx($conn, $ctx){
    
        $query  = "SELECT min(policy_group.order) as pgorder FROM policy_group where ctx = UNHEX(?)";
        $params = array($ctx);

        if (!$rs = $conn->Execute($query, $params))
        {
            print $conn->ErrorMsg();
        }
        else
        {
            $order = $rs->fields["pgorder"];
        }

        return (empty($order) ? 0 : $order);
    
    }
	
	
	function get_name_by_id($conn, $id)
	{
		$query  = "SELECT name FROM policy_group where id = UNHEX(?)";
		$params = array($id);
		
        if (!$rs = & $conn->Execute($query, $params)) 
        {
            print $conn->ErrorMsg();
        } 
        else 
        {
            $name = $rs->fields["name"];
        }
	
		return $name;
	}
	
}
