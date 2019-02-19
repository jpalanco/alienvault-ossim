<?php
/**
* net_group_scan.inc
*
* File net_group_scan.inc is used to:
*   - To manage plugins that belong to network group
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


class Net_group_scan 
{
    public $net_group_id;


    public $plugin_id;


    public $plugin_sid;


    public function __construct($net_group_id, $plugin_id, $plugin_sid) 
    {
        $this->net_group_id = $net_group_id;
        $this->plugin_id    = $plugin_id;
        $this->plugin_sid   = $plugin_sid;
    }


    public function get_net_group_id() 
    {
        return $this->net_group_id;
    }


    public function get_plugin_id() 
    {
        return $this->plugin_id;
    }


    public function get_plugin_sid() 
    {
        return $this->plugin_sid;
    }


    public function get_list($conn, $args = '') 
    {
        Ossim_db::check_connection($conn);
        
        $list = array();
        
        $query = ossim_query("SELECT *, HEX(net_group_id) AS net_group_id FROM net_group_scan $args");
        $rs    = $conn->Execute($query);
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else 
        {
            while (!$rs->EOF) 
            {
				$list[] = new self($rs->fields['net_group_id'], $rs->fields['plugin_id'], $rs->fields['plugin_sid']);
                
                $rs->MoveNext();
            }
        }
        
        return $list;
    }


    public function insert($conn, $net_group_id, $plugin_id, $plugin_sid = 0) 
    {
        Ossim_db::check_connection($conn);
        
        settype($plugin_id, 'int');
        settype($plugin_sid, 'int');
        
        $params = array($net_group_id, $plugin_id, $plugin_sid);
        $query  = 'INSERT INTO net_group_scan VALUES (UNHEX(?), ?, ?)';
                
        if ($conn->Execute($query, $params) === FALSE) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        }
        
        return TRUE;
    }


    public function update($conn, $net_group_id, $plugin_id, $plugin_sid = 0) 
    {
        Ossim_db::check_connection($conn);
        
        settype($plugin_id, 'int');
        settype($plugin_sid, 'int');
        
        $params = array($net_group_id, $plugin_id, $plugin_sid);
        $query = 'UPDATE net_group_scan SET net_group_id = UNHEX(?), plugin_id = ?, plugin_sid = ?';
        
        if ($conn->Execute($query, $params) === FALSE) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        }
        
        return TRUE;
    }


    public static function delete($conn, $net_group_id, $plugin_id)
    {
        Ossim_db::check_connection($conn);
        
        $params = array($net_group_id, $plugin_id);
        $query  = 'DELETE FROM net_group_scan WHERE net_group_id = UNHEX(?) AND plugin_id = ?';
        
        if ($conn->Execute($query, $params) === FALSE) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        }
        
        return TRUE;
    }


    public function in_net_group_scan($conn, $net_group_id, $plugin_id) 
    {
        Ossim_db::check_connection($conn);
        
        $params = array($net_group_id, $plugin_id);
        $query  = 'SELECT * FROM net_group_scan WHERE net_group_id = UNHEX(?) AND plugin_id = ?';
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        }
        
        return $rs->fields['net_group_id'];
    }
}

/* End of file net_group_scan.inc */
/* Location: ../include/classes/net_group_scan.inc */
