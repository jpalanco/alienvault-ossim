<?php
/**
* Action_exec.inc
*
* File Action_exec.inc is used to:
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
* - Action_exec()
* - get_action_id()
* - get_command()
* - get_list()
* - insert()
* - update()
*/

class Action_exec 
{
    public $action_id;
    public $command;
	
	
    function __construct($action_id, $command) 
    {
        $this->action_id = $action_id;
        $this->command  = htmlspecialchars($command);
    }
	
	
    function get_action_id() 
    {
        return $this->action_id;
    }
	
	
    function get_command() 
    {
        return $this->command;
    }
	
	
    function get_list($conn, $args = "") 
    {
        $query = ossim_query("SELECT *, HEX(action_id) as action_id FROM action_exec where 1=1 $args");
        
        if (!$rs = & $conn->Execute($query)) 
        {
            print $conn->ErrorMsg();
        } 
        else 
        {
            $list = array();
            
            while (!$rs->EOF) 
            {
                $list[] = new Action_exec($rs->fields["action_id"], $rs->fields["command"]);
                
                $rs->MoveNext();
            }
        }
        
        return $list;
    }
	
	
    function insert($conn, $id, $command) 
    {
        $sql = "INSERT INTO action_exec (action_id, command) VALUES (UNHEX(?), ?)";
        $params = array(
			$id,
            $command
        );
        
        if ($conn->Execute($sql, $params) === false) 
        {
            print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        
    }
	
	
    function update($conn, $id, $command) 
    {
        $sql = "UPDATE action_exec SET command = ? WHERE action_id = UNHEX(?)";
        $params = array(
            $command,
            $id
        );
        
        if ($conn->Execute($sql, $params) === false) 
        {
            print 'error updating: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        
    }
	
	
	function delete($conn, $id) 
	{
        $sql = "DELETE FROM action_exec WHERE action_id = UNHEX(?)";
        $params = array(
            $id
        );
        
        if ($conn->Execute($sql, $params) === false) 
        {
            print _("error deleting")." ($sql): " . $conn->ErrorMsg() . '<BR>';
            exit;
        }
		
    }
}


