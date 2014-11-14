<?php
/**
* asset_net_qualification.inc
*
* File asset_net_qualification.inc is used to:
*   - To manage compromise and attack metrics in nets
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


class Asset_net_qualification 
{
    public $net_id;
    
    public $compromise;
    
    public $attack;
    
    public $name;
    
    public function __construct($id, $name, $compromise, $attack) 
    {
        $this->net_id     = $id;
        $this->compromise = $compromise;
        $this->attack     = $attack;
        $this->name       = $name;
    }
    
    public function get_net_id() 
    {
        return $this->net_id;
    }
    
    
    public function get_net_name() 
    {
        return $this->name;
    }
    
    
    public function get_compromise() 
    {
        return $this->compromise;
    }
    
    
    public function get_attack() 
    {
        return $this->attack;
    }
    
    
    public function get_list($conn, $where, $order = '') 
    {
        Ossim_db::check_connection($conn);
        
        $list = array();
        
        if(empty($where)) 
        {
            $where = ' where 1=1 ';
        }
        
        $ctxs = Session::get_ctx_where();
        
        if ($ctxs != '') 
        {
            $where .= " AND net.ctx IN ($ctxs)";
        }
        
        // Asset filter
        $nets = Session::get_net_where();
        if ($nets != '') 
        {
            $where .= " AND net.id IN ($nets)";
        }
        
        if($order == 'net_name') 
        {
            $query = ossim_query("SELECT HEX(net_id) AS net_id, net_qualification.compromise, net_qualification.attack, net.name AS net_name
                        FROM net_qualification, net $where AND net_qualification.net_id = net.id ORDER BY net.name");
        }
        else 
        {
            $query = ossim_query("SELECT HEX(net_id) AS net_id , net_qualification.compromise, net_qualification.attack, net.name AS net_name
                        FROM net_qualification $where $order");
        }

        $rs = $conn->Execute($query);
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else 
        {
            while (!$rs->EOF) 
            {                
                $list[] = new Asset_net_qualification($rs->fields['net_id'], $rs->fields['net_name'], $rs->fields['compromise'], $rs->fields['attack']);
               
                $rs->MoveNext();
            }
        }
        return $list;
    }
    
    
    public function insert($conn, $net_id, $compromise, $attack) 
    {
        Ossim_db::check_connection($conn);
        
        $compromise = intval($compromise);
        $attack     = intval($attack);
        
        $params = array($net_id, $compromise, $attack);
        $query  = "INSERT INTO net_qualification VALUES (UNHEX(?), ?, ?)";
                        
        if ($conn->Execute($query, $params) === FALSE) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    public function update($conn, $net_id, $compromise, $attack) 
    {
        Ossim_db::check_connection($conn);
        
        $compromise = intval($compromise);
        $attack     = intval($attack);
        
        $params = array($compromise, $attack, $net_id);
        $query  = "UPDATE net_qualification SET compromise = ?, attack = ? WHERE net_id = UNHEX(?)";
                
        if ($conn->Execute($query, $params) === FALSE) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    public function delete($conn, $net_id) 
    {
        Ossim_db::check_connection($conn);
        
        $params = array($net_id);
        $query  = "DELETE FROM net_qualification WHERE net_id = UNHEX(?)";
                
        if ($conn->Execute($query, $params) === FALSE) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        }
        
        return TRUE;
    }
}

/* End of file asset_net_qualification.inc */
/* Location: ../include/classes/asset_net_qualification.inc */ 