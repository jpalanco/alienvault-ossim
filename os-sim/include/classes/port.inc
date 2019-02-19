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


class Port
{
    public $port_number;


    public $protocol_name;


    public $service;


    public $descr;


    public $ctx;


    public $foundrows;


    public function __construct($port_number, $protocol_name, $service, $descr, $ctx, $foundrows = 0)
    {
        $this->port_number   = $port_number;
        $this->protocol_name = $protocol_name;
        $this->service       = $service;
        $this->descr         = $descr;
        $this->ctx           = $ctx;
        $this->foundrows     = $foundrows;
    }


    public function get_port_number() 
    {
        return $this->port_number;
    }


    public function get_protocol_name() 
    {
        return $this->protocol_name;
    }
    


    public function get_service()
    {
        return $this->service;
    }
    


    public function get_descr($entities = TRUE)
    {    
        if ($entities)
        {
            return Util::htmlentities($this->descr);
        }
        else
        {
            return $this->descr;
        }
    }
    


    public function get_ctx()
    {
        return $this->ctx;
    }


    public function get_foundrows()
    {
        return $this->foundrows;
    }
    
    public static function get_list($conn, $where = "", $order = "ORDER BY port_number")
    {
        $list        = array();

        $iffoundrows = (preg_match("/LIMIT/i", $order)) ? TRUE : FALSE;
        $select      = ($iffoundrows) ? "SQL_CALC_FOUND_ROWS *, HEX(ctx) AS ctx" : "*, HEX(ctx) AS ctx";
        $query       = ossim_query("SELECT $select FROM port WHERE 1=1 $where $order");

        $rs = $conn->Execute($query);
        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            $foundrows = 0;

            if ($iffoundrows)
            {
                $rf = $conn->Execute("SELECT FOUND_ROWS() as total");

                if (!$rf)
                {
                    Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                }
                else
                {
                    $foundrows = $rf->fields["total"];
                }
            }

            while (!$rs->EOF)
            {
                $list[] = new self($rs->fields["port_number"], $rs->fields["protocol_name"], $rs->fields["service"], $rs->fields["descr"], $rs->fields["ctx"], $foundrows);

                $rs->MoveNext();
            }
        }

        return $list;
    }


    public static function insert($conn, $port_number, $protocol_name, $service, $descr, $ctx)
    {
        settype($port_number, "int");

        $query  = "INSERT INTO port (port_number, protocol_name, service, descr, ctx) VALUES (?, ?, ?, ?, UNHEX(?))";
        $params = array(
            $port_number,
            $protocol_name,
            $service,
            $descr,
            $ctx
        );

        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }


    public static function update($conn, $port_number, $protocol_name, $service, $descr, $ctx)
    {
        settype($port_number, "int");

        $query  = "UPDATE port SET service = ?, descr = ? WHERE port_number = ? AND protocol_name = ? AND ctx = UNHEX(?)";
        $params = array(
            $service,
            $descr,
            $port_number,
            $protocol_name,
             $ctx
        );

        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }


    public static function delete($conn, $port_number, $protocol_name)
    {
        settype($port_number, "int");

        if($port_number == 0)
        {
            $exp_msg = _('You are not allow to delete this port');

            Av_exception::throw_error(Av_exception::USER_ERROR, $exp_msg);
        }

        $query  = "DELETE FROM port WHERE port_number = ? AND protocol_name = ?";
        $params = array($port_number, $protocol_name);

        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        Port_group_reference::delete_by_port($conn, $port_number, $protocol_name);
    }


    public static function port2service($conn, $port, $protocol = "tcp")
    {
        if (($protocol != "tcp") and ($protocol != "udp")) 
        {
            return $port;
        }

        if (isset($_SESSION['_port2service'][$port][$protocol])) 
        {
            return $_SESSION['_port2service'][$port][$protocol];
        }

        $query  = "SELECT service FROM port WHERE port_number = ? AND protocol_name = ?";
        $params = array($port, $protocol);

        $rs = $conn->Execute($query, $params);
        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            if (!$rs->EOF)
            {
                return $rs->fields["service"];
            }
        }
        
        /* there is no info about $port-$protocol in ossim.port table */
        if ($service = getservbyport($port, $protocol))
        {
            $query = "INSERT INTO port (port_number, protocol_name, service, descr) VALUES (?, ?, ?, '')";
            $params = array($port, $protocol, $service);
            
            if ($conn->Execute($query, $params) === FALSE)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
            
            $_SESSION['_port2service'][$port][$protocol] = $service;
            
            return $service;
        }

        /* getservbyport failed */
        $_SESSION['_port2service'][$port][$protocol] = $port;

        return $port;
    }


    public static function service2port($conn, $service, $protocol = "tcp")
    {
        if (($protocol != "tcp") and ($protocol != "udp"))
        {
            return $service;
        }

        if (isset($_SESSION['_port2service'][$service][$protocol])) 
        {
            return $_SESSION['_port2service'][$service][$protocol];
        }

        $query  = "SELECT port_number FROM port WHERE service = ? AND protocol_name = ?";
        $params = array($service, $protocol);

        $rs = $conn->Execute($query, $params);
        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            if (!$rs->EOF)
            {
                return $rs->fields["port_number"];
            }
        }
        
        /* there is no info about $service-$protocol in ossim.port table */
        if ($port = getservbyname($service, $protocol))
        {
            $query  = "INSERT INTO port (port_number, protocol_name, service, descr) VALUES (?, ?, ?, '')";
            $params = array($port, $protocol, $service);
            
            if ($conn->Execute($query, $params) === FALSE)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }

            $_SESSION['_port2service'][$service][$protocol] = $port;

            return $service;
        }

        /* getservbyname failed */
        $_SESSION['_port2service'][$service][$protocol] = $service;

        return $service;
    }
}

/* End of file port.inc */
/* Location: ../include/classes/port.inc */
