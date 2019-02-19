<?php
/**
* Port_group_reference.inc
*
* File Port_group_reference.inc is used to:
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


class Port_group_reference
{
    public $port_group_id;


    public $port_number;


    public $protocol_name;


    public $ctx;


    public function __construct($port_group_id, $port_number, $protocol_name, $ctx)
    {
        $this->port_group_id = $port_group_id;
        $this->port_number   = $port_number;
        $this->protocol_name = $protocol_name;
        $this->ctx           = $ctx;
    }


    public function get_group_id()
    {
        return $this->port_group_id;
    }


    public function get_port_number()
    {
        return $this->port_number;
    }


    public function get_protocol_name()
    {
        return $this->protocol_name;
    }


    public function ctx()
    {
        return $this->ctx;
    }


    public static function get_list($conn, $where = "", $order = "")
    {
        $list  = array();
        $query = ossim_query("SELECT *, HEX(port_ctx) AS port_ctx FROM port_group_reference $where $order");

        $rs = $conn->Execute($query);
        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF)
            {
                $list[] = new self(stripslashes($rs->fields["port_group_id"]), $rs->fields["port_number"], $rs->fields["protocol_name"], $rs->fields["port_ctx"]);

                $rs->MoveNext();
            }
        }

        return $list;
    }


    public static function insert($conn, $port_group_id, $port_list, $port_ctx)
    {
        foreach($port_list as $port)
        {
            /*
            * port_list contains an array like this
            * (80-tcp, 80-udp, 8080-tcp, 8080-udp)
            */
            $port_array = explode('-', $port);
            $port_number = trim($port_array[0]);
            $protocol_name = trim($port_array[1]);

            settype($port_number, "int");

            $query  = "INSERT INTO port_group_reference (port_group_id, port_number, protocol_name, port_ctx) VALUES (?, ?, ?, UNHEX(?))";
            $params = array(
                $port_group_id,
                $port_number,
                $protocol_name,
                $port_ctx
            );

            if ($conn->Execute($query, $params) === FALSE)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
        }
    }


    public static function delete_by_port($conn, $port_number, $protocol_name)
    {   
        $query  = "DELETE FROM port_group_reference WHERE port_number = ? AND protocol_name = ?";
        $params = array($port_number, $protocol_name);
        
        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }


    public static function delete($conn, $port_group_id)
    {
        $query  = "DELETE FROM port_group_reference WHERE port_group_id = ?";
        $params = array($port_group_id);

        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }


    public static function in_port_group_reference($conn, $port_group_id, $port_number, $protocol_name, $port_ctx)
    {
        settype($port_number, "int");

        $query  = "SELECT * FROM port_group_reference WHERE port_group_id = ? AND port_number = ? AND protocol_name = ? AND port_ctx = UNHEX(?)";
        $params = array(
            $port_group_id,
            $port_number,
            $protocol_name,
            $port_ctx
        );

        $rs = $conn->Execute($query, $params);
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        return $rs->fields[0];
    }


    public static function in_port_group_reference_for_id($conn, $port_group_id)
    {
        $list  = array();

        $params = array($port_group_id);
        $query  = "SELECT * FROM port_group_reference WHERE port_group_id = ?";

        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF)
            {
                $list[] = $rs->fields['port_number'].' - '.$rs->fields['protocol_name'];

                $rs->MoveNext();
            }
        }

        return $list;
    }
}

/* End of file port_group.inc */
/* Location: ../include/classes/port_group.inc */
