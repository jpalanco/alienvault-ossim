<?php
/**
* Action.inc
*
* File Action.inc is used to:
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
* - Action()
* - get_id()
* - get_action_type()
* - get_descr()
* - get_foundrows()
* - get_action_by_id()
* - get_action()
* - get_list()
* - insert()
* - insertEmail()
* - insertExec()
* - update()
* - updateEmail()
* - updateExec()
* - delete()
*/

class Action 
{
    public $id;
	public $ctx;
	public $name;
    public $action_type;
	public $cond;
	public $on_risk;
    public $descr;
	public $foundrows;
	
	
    function __construct($id, $ctx, $name, $action_type, $cond, $on_risk, $descr, $foundrows = 0) 
    {
	
        $this->id          = $id;
		$this->ctx         = $ctx;
		$this->name        = $name;
        $this->action_type = $action_type;
		$this->cond        = $cond;
		$this->on_risk     = $on_risk;
        $this->descr       = Util::htmlentities($descr);
        $this->foundrows   = $foundrows;
		
    }
	
	
    function get_id() 
    {
        return $this->id;
    }
    
	
	function get_ctx() 
	{
        return $this->ctx;
    }
	
	
	function get_name() 
	{
        return $this->name;
    }
	
	
    function get_action_type() 
    {
        return $this->action_type;
    }
	
	
	function get_cond() 
	{
		return $this->cond;
	}
	
	
	function is_on_risk() 
	{
		return $this->on_risk;
	}
	
	
    function get_descr() 
    {
        return preg_replace("/##@##.*/","",$this->descr);
    }
	
	
    function get_foundrows() 
    {
        return $this->foundrows;
    }
	
	
    function get_action_by_id($conn, $id) 
    {
        $sql = "SELECT *, HEX(id) AS id, HEX(ctx) AS ctx FROM action WHERE id = UNHEX(?)";
        $params = array(
            $id
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        } else {
            if (!$rs->EOF) {
                $action = new Action($rs->fields["id"], $rs->fields["ctx"], $rs->fields["name"], $rs->fields["action_type"], $rs->fields["cond"], $rs->fields["on_risk"], $rs->fields["descr"]);
            }
        }
        return $action;
    }
	
	
    /* get an action object of email or exec classes */
    function get_action($conn) 
    {
        $id   = $this->id;
        $type = intval($this->action_type);
		
        if ($type == 1) 
        {
            $action_list = Action_email::get_list($conn, " AND action_id = UNHEX('$id') ");
            
            if (is_array($action_list)) 
            {
                return $action_list[0];
            }
            
            return null;
			
        } 
        elseif ($type == 2) 
        {
            $action_list = Action_exec::get_list($conn, " AND action_id = UNHEX('$id') ");
            
            if (is_array($action_list))
            {
                return $action_list[0];
            }
            
            return null;
            
        } 
        elseif ($type == 3) 
        {
            return preg_replace("/.*##@##/","",$this->descr);
			
        } 
        else 
        {
            return null;
        }
    }
	
	function get_action_type_text($conn) 
	{
         $type = Action_type::get_list($conn, "AND type=".$this->action_type);
         
		 return $type[0]->get_name();
    }
	
    function get_list($conn, $args = "") 
    {
		$ctxs = Session::get_ctx_where();
		
		if ($ctxs != "") 
		{
			$ctxs = " AND ctx in ($ctxs)";
		}   
		
        $iffoundrows = preg_match("/LIMIT/i", $args) ? true : false;
		$select      = "*, HEX(id) AS id, HEX(ctx) AS ctx";
        $select      = ($iffoundrows) ? "SQL_CALC_FOUND_ROWS ". $select : $select;
        $query       = ossim_query("SELECT $select FROM action WHERE 1=1 $ctxs $args");

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
            
            $list = array();
            
            while (!$rs->EOF) 
            {
                $list[] = new Action($rs->fields["id"], $rs->fields["ctx"], $rs->fields["name"], $rs->fields["action_type"], $rs->fields["cond"], $rs->fields["on_risk"], $rs->fields["descr"], $foundrows);
                
                $rs->MoveNext();
            }
        }
		
        return $list;
    }
	
    function insert($conn, $ctx, $name, $action_type, $cond, $on_risk, $descr) 
    {
    
		$id  = Util::uuid();
        $sql = "INSERT INTO action (id, ctx, name, action_type, cond, on_risk, descr) VALUES (UNHEX(?),UNHEX(?), ?, ?, ?, ?, ?)";
        
        $params = array(
			$id,
			$ctx,
			$name,
            $action_type,
			$cond,
			$on_risk,
            $descr
        );
        
        if ($conn->Execute($sql, $params) === false) 
        {
            print _('error inserting:').' '.$conn->ErrorMsg() . '<BR>';
            exit;
        }
        
        $infolog = array(
            $action_type,
            "(" . $descr . ")"
        );
        
        Log_action::log(82, $infolog);
		
		return $id;
    
    }
	
    function insertEmail($conn, $ctx, $name, $action_type, $cond, $on_risk, $descr, $from, $to, $subject, $message) 
    {
        $id = Action::insert($conn, $ctx, $name, $action_type, $cond, $on_risk, $descr);
        
        Action_email::insert($conn, $id, $from, $to, $subject, $message);
    }
	
    function insertExec($conn, $ctx, $name, $action_type, $cond, $on_risk, $descr, $command) 
    {
        $id = Action::insert($conn, $ctx, $name, $action_type, $cond, $on_risk, $descr);
        
        Action_exec::insert($conn, $id, $command);
    }
	
    function update($conn, $id, $ctx, $name, $action_type, $cond, $on_risk, $descr) 
    {
        $sql = "UPDATE action SET ctx = UNHEX(?), name = ?, action_type = ?, cond = ?, on_risk = ?, descr = ? WHERE id = UNHEX(?)";
        
        $params = array(
			$ctx,
			$name,
            $action_type,
			$cond,
			$on_risk,
            $descr,
            $id
        );
        
        if ($conn->Execute($sql, $params) === false) 
        {
            print _('error updating: '). $conn->ErrorMsg() . '<BR>';
            exit;
        }
        
        $infolog = array(
            $action_type,
            "(" . $descr . ")"
        );
        
        Log_action::log(83, $infolog);
    }
	
    function updateEmail($conn, $id, $ctx, $name, $action_type, $cond, $on_risk, $descr, $from, $to, $subject, $message) 
    {
    
        Action::update($conn, $id, $ctx, $name, $action_type, $cond, $on_risk, $descr);
        
        Action_email::update($conn, $id, $from, $to, $subject, $message);
    }
	
    function updateExec($conn, $id, $ctx, $name, $action_type, $cond, $on_risk, $descr, $command) 
    {
        Action::update($conn, $id, $ctx, $name, $action_type, $cond, $on_risk, $descr);
        
        Action_exec::update($conn, $id, $command);
    }
	
    function delete($conn, $id) 
    {
        $desc = $conn->GetOne("SELECT descr FROM action WHERE id=UNHEX('$id')");
        
        $Action = Action::get_action_by_id($conn, $id);
        $type   = $Action->get_action_type();
        $sql    = "DELETE FROM action WHERE id = UNHEX(?)";
		
        $params = array(
            $id
        );
        
        if ($conn->Execute($sql, $params) === false) 
        {
            print _("error deleting")." ($sql): " . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        
        switch(intval($type)) 
        {
            case 1:
				Action_email::delete($conn, $id);
				break;
			
			case 2:
				Action_exec::delete($conn, $id);
				break;
			
			/*case 4:
				Action_risk::delete($conn, $id);
				break;*/
			
        }
	
		Policy_action::delete_by_action($conn, $id);
		
        $infolog = array($desc);
        
        Log_action::log(84, $infolog);
        
    }
	
	function check_duplicated($conn, $name) 
	{
		$ctxs = Session::get_ctx_where();
		
		if ($ctxs != "") 
		{
			$ctxs = " AND ctx in ($ctxs)";
		}   
		
		$query = ossim_query("SELECT count(*) as names FROM action WHERE 1=1 $ctxs AND UPPER(TRIM(?)) = UPPER(TRIM(name))");

        if (!$rs = & $conn->Execute($query, array($name))) 
        {
            return false;
        } 
        else 
        {
            if ($rs->fields["names"] == 0)
            {
				return true;
			}
        }
		
        return false;

	}
	
}

