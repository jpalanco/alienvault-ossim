<?php
/**
* net_group_reference.inc
*
* File net_group_reference.inc is used to:
*   - To manage networks that belong to network group
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


class Net_group_reference 
{
    public $net_group_id;


    public $net_id;


    public function __construct($net_group_id, $net_id) 
    {
        $this->net_group_id = $net_group_id;
        $this->net_id       = $net_id;
    }


    public function get_net_group_id() 
    {
        return $this->net_group_id;
    }


    public function get_net_id()
    {
        return $this->net_id;
    }


    public function get_net_ips($conn) 
    {
        Ossim_db::check_connection($conn);
        
        $ips   = '';
        
        $params = array($this->net_id);
        $query  = "SELECT ips FROM net WHERE id = UNHEX(?)";
                
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            $ips = $rs->fields[0];
        }
        
        return $ips;
    }

    /**
     * Function get_list
     *
     * This function will return all Net_group_reference matching with the provided $net_group_id and with the session
     * ctx and net.
     * Param $plugin remove since never it is used and increase and the objetive has provide net_work with related net_scan
     * so it was not a identificative name
     *
     * @param object $conn Database access object
     * @param string $net_group_id   ID from a net_group
     *
     * @access public
     *
     * @throws Exception If a connection error occurred
     *
     * @return array  Emtpy array if no match with the conditions or array with the net_group_reference objects
     */
    public static function get_list($conn, $net_group_id)
    {            
        Ossim_db::check_connection($conn);
        
        $list = array();

        $get_ctx_where = Session::get_ctx_where();
        $get_net_where = Session::get_net_where();

        $perms_where  = ($get_ctx_where != '') ? " AND net.ctx in (".$get_ctx_where.')' : '';
        $perms_where .= ($get_net_where != '') ? " AND net_group_reference.net_id in (".$get_net_where.')' : '';

        $query = "SELECT HEX(net_group_id) AS net_group_id, HEX(net_group_reference.net_id) AS net_id
            FROM net_group_reference, net 
            WHERE net_group_id = UNHEX(?) 
            AND net.id = net_group_reference.net_id $perms_where";

        $rs = $conn->Execute($query, array($net_group_id));
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else 
        {
            while (!$rs->EOF) 
            {
                $list[] = new self($rs->fields['net_group_id'], $rs->fields['net_id']);
                
                $rs->MoveNext();
            }
        }

        return $list;
    }


    public function get_related_nets($conn, $net_group_id) 
    {
        Ossim_db::check_connection($conn);
        
        $list = array();

        $get_net_where = Session::get_net_where();
        $perms_where = ($get_net_where != '') ? " AND net_id in (".$get_net_where.')' : '';
        $query = "SELECT HEX(net_id) AS net_id FROM net_group_reference 
            WHERE net_group_id = UNHEX(?) $perms_where";
        
        $rs = $conn->Execute($query, array($net_group_id));
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else 
        {
            while (!$rs->EOF) 
            {
                $list[] = $rs->fields['net_id'];
               
                $rs->MoveNext();
            }
        }
        
        return $list;
    }
       
       
    public static function insert($conn, $net_group_id, $nets) 
    {
        Ossim_db::check_connection($conn);
        
        foreach($nets as $net_id) 
        {
            $params = array($net_group_id, $net_id);
            
            $query  = 'INSERT INTO net_group_reference VALUES (UNHEX(?), UNHEX(?))';

            if ($conn->Execute($query, $params) === FALSE) 
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                
                return FALSE;
            }
        }
        
        return TRUE;
    }
    
    
    public static function delete($conn, $net_group_id, $net_id = '') 
    {
        Ossim_db::check_connection($conn);
        
        $params = array($net_group_id);
        $query  = 'DELETE FROM net_group_reference WHERE net_group_id = UNHEX(?)';
                
        if ($net_id != '') 
        {
        	$query   .= ' AND net_id = UNHEX(?)';
        	$params[] = $net_id;
        }
        
        if ($conn->Execute($query, $params) === FALSE) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                
            return FALSE;
        }

        return FALSE;
    }
}

/* End of file net_group_reference.inc */
/* Location: ../include/classes/net_group_reference.inc */ 
