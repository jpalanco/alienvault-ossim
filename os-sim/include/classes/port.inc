<?php
/**
* Port.inc
*
* File Port.inc is used to:
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
* - Port()
* - get_port_number()
* - get_protocol_name()
* - get_service()
* - get_descr()
* - get_list()
* - insert()
* - update()
* - delete()
* - port2service()
*/

require_once 'ossim_db.inc';

class Port {

    public $port_number;
    public $protocol_name;
    public $service;
    public $descr;
    public $ctx;
	public $foundrows;
	
    function __construct($port_number, $protocol_name, $service, $descr, $ctx, $foundrows=0) 
	{
        $this->port_number   = $port_number;
        $this->protocol_name = $protocol_name;
        $this->service       = $service;
        $this->descr         = $descr;
		$this->ctx           = $ctx;
        $this->foundrows     = $foundrows;
    }
	
    function get_port_number() 
	{
        return $this->port_number;
    }
	
    function get_protocol_name() 
	{
        return $this->protocol_name;
    }
	
    function get_service() 
	{
        return $this->service;
    }
	
    function get_descr() 
	{
        return $this->descr;
    }
	
	function get_ctx() 
	{
        return $this->ctx;
    }
	
	function get_foundrows() 
	{
        return $this->foundrows;
    }
	
    function get_list($conn, $where = "", $order = "ORDER BY port_number") 
	{
		$iffoundrows = (preg_match("/LIMIT/i", $order)) ? true : false;
		$select      = ($iffoundrows) ? "SQL_CALC_FOUND_ROWS *, HEX(ctx) AS ctx" : "*, HEX(ctx) AS ctx";
        $query       = ossim_query("SELECT $select FROM port WHERE 1=1 $where $order");
      
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
                $list[] = new Port($rs->fields["port_number"], $rs->fields["protocol_name"], $rs->fields["service"], $rs->fields["descr"], $rs->fields["ctx"], $foundrows);
                $rs->MoveNext();
            }
        }
		
        return $list;
    }
	
	
    function insert($conn, $port_number, $protocol_name, $service, $descr, $ctx) 
	{
        settype($port_number, "int");
        $sql = "INSERT INTO port (port_number, protocol_name, service, descr, ctx) VALUES (?, ?, ?, ?, UNHEX(?))";
        $params = array(
            $port_number,
            $protocol_name,
            $service,
            $descr,
            $ctx
        );
		
        if ($conn->Execute($sql, $params) === false) {
            print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
	
	
    function update($conn, $port_number, $protocol_name, $service, $descr, $ctx) 
	{
        settype($port_number, "int");
        $sql = "UPDATE port SET service = ?, descr = ? WHERE port_number = ? AND protocol_name = ? AND ctx = UNHEX(?)";
        $params = array(
            $service,
            $descr,
            $port_number,
            $protocol_name,
			 $ctx
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error updating: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
	
	
    function delete($conn, $port_number, $protocol_name) 
	{
        settype($port_number, "int");
		
		if($port_number == 0)
		{
			print _('You are not allow to delete this port') .'<BR>';
            exit;
		}
        $sql = "DELETE FROM port WHERE port_number = ? AND protocol_name = ?";
        $params = array(
            $port_number,
            $protocol_name
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
		
		Port_group_reference::delete_by_port($conn, $port_number, $protocol_name);
    }
	
	
    function port2service($conn, $port, $protocol = "tcp") 
	{
        if (($protocol != "tcp") and ($protocol != "udp")) return $port;
        if (isset($_SESSION['_port2service'][$port][$protocol])) return $_SESSION['_port2service'][$port][$protocol];
        /* first look at ossim.port table */
        $sql = "SELECT service FROM port WHERE port_number = ? AND protocol_name = ?";
        $params = array(
            $port,
            $protocol
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        } else {
            if (!$rs->EOF) return $rs->fields["service"];
        }
        /* there is no info about $port-$protocol in ossim.port table */
        if ($service = getservbyport($port, $protocol)) {
            $sql = "INSERT INTO port (port_number, protocol_name, service, descr) VALUES (?, ?, ?, '')";
            $params = array(
                $port,
                $protocol,
                $service
            );
            if ($conn->Execute($sql, $params) === false) {
                print 'error inserting port info: ' . $conn->ErrorMsg() . '<BR>';
            }
            $_SESSION['_port2service'][$port][$protocol] = $service;
            return $service;
        }
        /* getservbyport failed */
        $_SESSION['_port2service'][$port][$protocol] = $port;
        return $port;
    }
    
	
    function service2port($conn, $service, $protocol = "tcp") 
	{
        if (($protocol != "tcp") and ($protocol != "udp")) return $service;
        if (isset($_SESSION['_port2service'][$service][$protocol])) return $_SESSION['_port2service'][$service][$protocol];
        /* first look at ossim.port table */
        $sql = "SELECT port_number FROM port WHERE service = ? AND protocol_name = ?";
        $params = array(
            $service,
            $protocol
        );
        if (!$rs = & $conn->Execute($sql, $params)) {
            print $conn->ErrorMsg();
        } else {
            if (!$rs->EOF) return $rs->fields["port_number"];
        }
        /* there is no info about $service-$protocol in ossim.port table */
        if ($port = getservbyname($service, $protocol)) {
            $sql = "INSERT INTO port (port_number, protocol_name, service, descr) VALUES (?, ?, ?, '')";
            $params = array(
                $port,
                $protocol,
                $service
            );
            if ($conn->Execute($sql, $params) === false) {
                print 'error inserting service info: ' . $conn->ErrorMsg() . '<BR>';
            }
            $_SESSION['_port2service'][$service][$protocol] = $port;
            return $service;
        }
        /* getservbyname failed */
        $_SESSION['_port2service'][$service][$protocol] = $service;
        return $service;
    }
		
}
?>