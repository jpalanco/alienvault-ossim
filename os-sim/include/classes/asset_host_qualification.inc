<?php
/**
* asset_host_qualification.inc
*
* File asset_host_qualification.inc is used to:
*   - To manage compromise and attack metrics in hosts
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


class Asset_host_qualification 
{
    public $host_id;
    
    public $compromise;
    
    public $attack;
    
    public function __construct($host_id, $compromise, $attack) 
    {
        $this->host_id    = $host_id;
        $this->compromise = $compromise;
        $this->attack     = $attack;
    }
    
    
    public function get_host_id() 
    {
        return $this->host_id;
    }
    
    
    public function get_compromise() 
    {
        return $this->compromise;
    }
    
    
    public function get_attack() 
    {
        return $this->attack;
    }
    
    
    public function get_ip_attack($conn, $id) 
    {
        Ossim_db::check_connection($conn);
        
        $attack = 0;
        
        $params = array($id);
        $query  = "SELECT * FROM host_qualification WHERE host_id = UNHEX(?)";
        
        $rs = $conn->Execute($query, $params);        
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else 
        {
            if (!$rs->EOF) 
            {
                $attack = $rs->fields['attack'];
            }           
        }
        
        return $attack;
    }
    
    
    public function get_ip_compromise($conn, $id) 
    {
        Ossim_db::check_connection($conn);
        
        $compromise = 0;
        
        $params = array($id);
        $query  = ossim_query("SELECT * FROM host_qualification WHERE host_id = UNHEX(?)");
        
        $rs = $conn->Execute($query, $params);        
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else 
        {
            if (!$rs->EOF)
            { 
                $compromise = $rs->fields['compromise'];
            }
        }
        
        return $compromise;
    }
    
    
    private function __get_global($conn, $type) 
    {
        Ossim_db::check_connection($conn);
        
        if ($type != 'compromise' && $type != 'attack') 
        {
            return -1;
        }
        
        $global = 0;
        
        $query = "SELECT $type, HEX(host_id) AS host_id FROM host_qualification where $type > 0";         
        
        $rs = $conn->Execute($query);
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else 
        {
            while (!$rs->EOF) 
            {
                if (Asset_host::is_allowed($conn, $rs->fields['host_id'])) 
                {
                    $value  = $rs->fields[$type];
                    $object = Asset_host::get_object($conn, $rs->fields['host_id']);
                    
                    if(is_object($object) && !empty($object)) 
                    {
                        $asset  = $object->get_asset_value();
                        
                        if ($type == 'compromise') 
                        {
                            $thresh = $object->get_threshold_c();
                        }
                        else 
                        {
                            $thresh = $object->get_threshold_a();
                        }
                        
                        $real_val = ($value < ($thresh * $asset)) ? $value : ($thresh * $asset);
                        $global+= $real_val;
                    }
                }
                
                $rs->MoveNext();
            }
        }
        
        return $global;
    }
    
    
    public function get_global_compromise($conn) 
    {
        return self::__get_global($conn, 'compromise');
    }
    
    
    public function get_global_attack($conn) 
    {
        return self::__get_global($conn, 'attack');
    }
    
        
    public function get_list($conn, $where = '', $order = '') 
    {
        Ossim_db::check_connection($conn);
        
        $list = array();
        
        if($where == '') 
        {
            $where = 'WHERE 1=1';
        }
        
        // CTX filter
        $ctxs = Session::get_ctx_where();
        if ($ctxs != '') 
        {
            $where .= " AND host.ctx IN ($ctxs)";
        }
        
        // Asset filter
        $hosts = Session::get_host_where();
        if ($hosts != '') 
        {
            $where .= " AND host.id IN ($hosts)";
        }
        
        $query = ossim_query("SELECT *, HEX(host_id) AS host_id 
            FROM host_qualification, host $where AND host.id=host_qualification.host_id $order");
        
        if (!$rs = & $conn->Execute($query)) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else 
        {            
            while (!$rs->EOF) 
            {                
                $list[] = new Asset_host_qualification($rs->fields['host_id'], $rs->fields['compromise'], $rs->fields['attack']);
               
                $rs->MoveNext();
            }
        }
        
        return $list;
    }
    
    
    public function insert($conn, $host_id, $compromise, $attack) 
    {
        Ossim_db::check_connection($conn);
        
        $compromise = intval($compromise);
        $attack     = intval($attack);
        
        $params = array($host_id, $compromise, $attack);         
        $query  = "INSERT INTO host_qualification VALUES (UNHEX(?), ?, ?)";
                   
        if ($conn->Execute($query, $params) === FALSE) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    public function update($conn, $host_id, $compromise, $attack) 
    {
        Ossim_db::check_connection($conn);
        
        $compromise = intval($compromise);
        $attack     = intval($attack);
        
        $params = array($compromise, $attack, $host_id); 
        $query  = "UPDATE host_qualification SET compromise = ?, attack = ? WHERE host_id = UNHEX(?)";
        
        if ($conn->Execute($query, $params) === FALSE) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        }
        
        return TRUE;
    }
    
    
    public function delete($conn, $host_id) 
    {
        Ossim_db::check_connection($conn);
        
        $params = array($host_id);        
        $query  = "DELETE FROM host_qualification WHERE host_id = UNHEX(?)";             
        
        if ($conn->Execute($query, $params) === FALSE) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        }
        
        return TRUE;

    }
}

/* End of file asset_host_qualification.inc */
/* Location: ../include/classes/asset_host_qualification.inc */ 