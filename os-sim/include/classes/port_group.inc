<?php
/**
* Port_group.inc
*
* File Port_group.inc is used to:
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
* @package    ossim-framework\Assets
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


/**
* Function list:
* - Port_group()
* - get_name()
* - get_ip()
* - get_descr()
* - get_foundrows()
* - get_reference_ports()
* - get_list()
* - insert()
* - update()
* - delete()
*/

require_once ("ossim_db.inc");

class Port_group {
    public $id;
	public $name;
    public $descr;
    public $ctx;
    function __construct($id, $name, $descr, $ctx, $foundrows = 0) {
        $this->id = $id;
    	$this->name = $name;
        $this->descr = $descr;
        $this->ctx = $ctx;
        $this->foundrows = $foundrows;
    }
	function get_id() {
        return $this->id;
    }
    function get_name() {
        return $this->name;
    }
    function get_ip() {
        return $this->ip;
    }
    function get_descr() {
        return $this->descr;
    }
	function get_ctx() {
        return $this->ctx;
    }
    function get_foundrows() {
        return $this->foundrows;
    }
    function get_reference_ports($conn, $port_group_id) {
        return Port_group_reference::get_list($conn, "WHERE port_group_id = $port_group_id");
    }
    function get_list($conn, $args = "") {
        $iffoundrows = preg_match("/LIMIT/i", $args) ? true : false;
        $select = ($iffoundrows) ? "SQL_CALC_FOUND_ROWS *, HEX(ctx) AS ctx" : "*, HEX(ctx) AS ctx";
        $query = ossim_query("SELECT $select FROM port_group $args");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $foundrows = 0;
            if ($iffoundrows) {
                if (!$rf = & $conn->Execute("SELECT FOUND_ROWS() as total")) print $conn->ErrorMsg();
                else $foundrows = $rf->fields["total"];
            }
            $list = array();
            while (!$rs->EOF) {
                $list[] = new Port_group($rs->fields["id"], $rs->fields["name"], $rs->fields["descr"], $rs->fields["ctx"], $foundrows);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $name, $port_list, $descr, $ctx) {
        $sql = "INSERT INTO port_group (name, descr, ctx) VALUES (?, ?, UNHEX(?))";
        
		$params = array(
            $name,
            $descr,
            $ctx
        );
        
		if ($conn->Execute($sql, $params) === false) {
            print 'Error inserting: ' . $conn->ErrorMsg() . '<br/>';
            exit;
        }
        
        $query = ossim_query("SELECT LAST_INSERT_ID() as newid");
        if ( !$rs = & $conn->Execute($query) ) 
        {
        	print $conn->ErrorMsg();
        	exit;
        } 
        else {
        	$new_id = $rs->fields['newid'];
        }
        
        Port_group_reference::insert($conn, $new_id, $port_list, $ctx);
        $infolog = array(
            $name,
            "(" . $descr . ")"
        );
        
        Log_action::log(39, $infolog);
        
        return $new_id;
    }
	
    function update($conn, $id, $name, $port_list, $descr, $ctx) {
    	
    	$sql = "UPDATE port_group SET name = ?, descr = ?, ctx = UNHEX(?) WHERE id = ?";
        
		$params = array(
            $name,
            $descr,
            $ctx,
            $id
        );
        
		if ($conn->Execute($sql, $params) === false) {
            print 'Error updating: ' . $conn->ErrorMsg() . '<br/>';
            exit;
        }
        
        Port_group_reference::delete($conn, $id);
        Port_group_reference::insert($conn, $id, $port_list, $ctx);
		
        $infolog = array(
            $name,
            "(" . $descr . ")"
        );
		
		Log_action::log(40, $infolog);
    }
	
    function delete($conn, $id) {
        $sql = "DELETE FROM port_group WHERE id = ?";
        $params = array(
            $id
        );
		
        if ($conn->Execute($sql, $params) === false) {
            print 'Error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
		
        Port_group_reference::delete($conn, $id);
        $infolog = array(
            $id
        );
        Log_action::log(41, $infolog);
    }
	function can_delete($conn, $id) {
		$sql = "SELECT count(*) as num FROM policy_port_reference WHERE port_group_id = ?";
		$params = array(
            $id
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
		if ($rs->fields['num'] == '0') return 1;
		else return 0;
	}
	
	function get_name_by_id($conn, $id) {
        $sql    = "SELECT name FROM port_group WHERE id = ?";
        $params = array(
            $id
        );

        if (!$rs = $conn->Execute($sql, $params)) 
        {
            return _('Unknonwn');
        }
        else 
        {
            return (empty($rs->fields["name"]) ?  _('Unknonwn') : $rs->fields["name"]);
        }
        
    }
    
    public static function expand_ports($actives_ports)
    { 
        $aux = array();   
    
        foreach($actives_ports as $active_port)
        {
            // expand port range (100-102 - tcp) to port list (100 - tcp, 101 -tcp, 102 - tcp)
            if (preg_match('/(\d+)-(\d+)\s*\-\s*(\w+)/', $active_port, $found))
            {
                $ports = range($found[1], $found[2]);
                array_walk($ports, 'append_protocol', $found[3]);
                
                // delete element in the array
                $key = array_search($active_port, $actives_ports);
                
                unset($actives_ports[$key]);
                
                // append the returned ports
                
                $aux = array_merge($aux, $ports);
            }
        }
        
        $actives_ports = array_merge($actives_ports, $aux);
        $actives_ports = array_unique($actives_ports);
    
        return $actives_ports;
    }
    
    public static function group_ports($actives_ports)
    {
        $tcp_list = array();
	    $udp_list = array();
	
		foreach($actives_ports as $v)
		{
		    // group port list (100 - tcp, 101 -tcp, 102 - tcp) to port group (100-102 - tcp)
		    
		    $delete_item = FALSE;

		    if ( preg_match('/(\d+)\s*\-\s*tcp/i', trim($v)) )
		    {
			    $tcp_list[] = preg_replace('/[^\d]/', '', $v);
			    $delete_item = TRUE;
		    }
		    else if ( preg_match('/(\d+)\s*\-\s*udp/i', trim($v)) )
		    {
			    $udp_list[] = preg_replace('/[^\d]/', '', $v);
			    $delete_item = TRUE;
		    }
		    
		    if ($delete_item === TRUE)
		    {
			    $key = array_search($v, $actives_ports);
			    unset($actives_ports[$key]);
		    }
		}
		
		if (!empty($tcp_list))
		{
		    sort($tcp_list ,SORT_NUMERIC);
		    $tcp_list = Util::group_nums($tcp_list);                        					
            array_walk($tcp_list, 'append_protocol', 'tcp');
        }
        
		if (!empty($udp_list))
		{
		    sort($udp_list ,SORT_NUMERIC);
		    $udp_list = Util::group_nums($udp_list);                        					
            array_walk($udp_list, 'append_protocol', 'udp');
        }
		
	    $actives_ports = array_merge($actives_ports, $tcp_list, $udp_list);
	    
	    return $actives_ports;
    }
}

/* End of file port_group.inc */
/* Location: ../include/classes/port_group.inc */
