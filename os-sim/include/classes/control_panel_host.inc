<?php
/**
* control_panel_host.inc
*
* File control_panel_host.inc is used to:
*   - To manage host metrics (Attack and Compromise)
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
* @package    ossim-framework\Metrics
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


class Control_panel_host 
{
    public $host_ip;
    
    public $time_range;
    
    public $max_c;
    
    public $max_a;
    
    public $max_c_date;
    
    public $max_a_date;
    
    
    public function  __construct($host_ip, $time_range, $max_c, $max_a, $max_c_date, $max_a_date) 
    {
        $this->host_ip    = $host_ip;
        $this->time_range = $time_range;
        $this->max_c      = $max_c;
        $this->max_a      = $max_a;
        $this->max_c_date = $max_c_date;
        $this->max_a_date = $max_a_date;
    }
    
    
    public function get_host_ip() 
    {
        return $this->host_ip;
    }
    
    
    public function get_time_range() 
    {
        return $this->time_range;
    }
    
    
    public function get_max_c() 
    {
        return $this->max_c;
    }
    
    
    public function get_max_a() 
    {
        return $this->max_a;
    }
    
    
    public function get_max_c_date() 
    {
        return $this->max_c_date;
    }
    
    
    public function get_max_a_date() 
    {
        return $this->max_a_date;
    }
    
    
    public static function get_list($conn, $args, $limit) 
    {
        $query = ossim_query("SELECT * FROM control_panel $args LIMIT $limit");
        
        $rs = $conn->Execute($query);
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }  
        else 
        {
            while (!$rs->EOF) 
            {
                // only insert in list if host exists in host_qualification
                $params = array($rs->fields['id']);
                $query  = ossim_query("SELECT * FROM host_qualification WHERE host_id = UNHEX(?)");
                
                $rs2 = $conn->Execute($query, $params);
                
                if (!$rs2) 
                {
                    Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                    
                    return $list;
                }
                
                
                if (!$rs2->EOF && ($count < $limit)) 
                {
                    $list[] = new Control_panel_host($rs->fields['id'], $rs->fields['time_range'], $rs->fields['max_c'], $rs->fields['max_a'], $rs->fields['max_c_date'], $rs->fields['max_a_date']);
                    
                    $count++;
                    
                }
                
                $rs->MoveNext();
            }
        }
        
        return $list;
    }
    
    
    /*
    * get a list of hosts that exceed his threshold
    * ordered by compromise or attack
    */
    public static function get_metric_list($conn, $time_range, $type, $assets_filter = '', $dates_filter = '') 
    {        
        Ossim_db::check_connection($conn);
        
        $list = array();
        
        if ($type == 'compromise') 
        {
            $params = array($time_range);
            
            $query = "SELECT * FROM control_panel 
                WHERE rrd_type = 'host' 
                AND time_range = ? AND max_c > 0 $assets_filter $dates_filter
                ORDER BY max_c_date DESC";
            
        } 
        elseif ($type == 'attack') 
        {
            $params = array($time_range);
            
            $query = "SELECT * FROM control_panel 
                WHERE rrd_type = 'host' 
                AND time_range = ? AND max_a > 0 $assets_filter $dates_filter 
                ORDER BY max_a_date DESC";        
        }
               
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else 
        {
            $params = array($rs->fields['id']);
                
            $query = "SELECT host_qualification.*, HEX(host_id) AS host_id FROM host_qualification WHERE host_id = UNHEX(?)";
            
            $rs2 = $conn->Execute($query, $params);
            
            if (!$rs2) 
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
                
                return $list;
            }
            
            while (!$rs->EOF) 
            {
                if (!empty($rs2->fields['host_id'])) 
                {
                    $host_obj = Asset_host::get_object($conn, $rs2->fields['host_id']);
                    if (is_object($host_obj) && !empty($host_obj)) 
                    {
                        $cnd_1 = Session::hostAllowed($conn, $host_obj->get_id());
                        $cnd_2 = (!strcmp($type, 'compromise') && ($rs->fields['max_c'] > $host_obj->get_threshold_c())) 
                            || (!strcmp($type, 'attack') && ($rs->fields['max_a'] > $host_obj->get_threshold_a()));                        
                                         
                        if ($cnd_1 && $cnd_2) 
				        {							
                            $list[] = new Control_panel_host($rs->fields['id'], $rs->fields['time_range'], $rs->fields['max_c'], 
                                $rs->fields['max_a'], $rs->fields['max_c_date'], $rs->fields['max_a_date']);
                        }
                    }
                }
                
                $rs->MoveNext();
            }
        }
        
        return $list;
    }
}

/* End of file control_panel_host.inc */
/* Location: ../include/classes/control_panel_host.inc */