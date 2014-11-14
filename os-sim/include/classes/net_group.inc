<?php
/**
* net_group.inc
*
* File net_group.inc is used to:
*   - To manage network groups
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


class Net_group 
{
    public $id;
    
    public $ctx;
	
	public $name;
    
    public $threshold_c;
    
    public $threshold_a;
    
    public $rrd_profile;
    
    public $descr;
    
    public function __construct($id, $ctx, $name, $threshold_c, $threshold_a, $rrd_profile, $descr, $foundrows = 0) 
    {
        $this->id = $id;
        
        $this->ctx = $ctx;
    	
    	$this->name = stripslashes($name);
        
        $this->threshold_c = $threshold_c;
        
        $this->threshold_a = $threshold_a;
        
        $this->rrd_profile = $rrd_profile;
        
        $this->descr = $descr;
        
        $this->foundrows = $foundrows;
    }
    
    
	public function get_id() 
	{
        return $this->id;
    }
	
	
	public function get_ctx() 
	{
        return $this->ctx;
    }
    
    
    public function get_name() 
    {
        return $this->name;
    }
    
    
    public function get_threshold_c() 
    {
        return $this->threshold_c;
    }
    
    
    public function get_threshold_a() 
    {
        return $this->threshold_a;
    }
    
    
    public function get_rrd_profile() 
    {
        return $this->rrd_profile;
    }
    
    
    public function get_descr() 
    {
        return $this->descr;
    }
    
    public function get_foundrows() 
    {
        return $this->foundrows;
    }
    
    public function get_networks($conn, $id = '', $plugin = '') 
    {
        Ossim_db::check_connection($conn);
        
        if ($id != '') 
        {
            return Net_group_reference::get_list($conn, $id, $plugin);
        } 
        else 
        {
            return Net_group_reference::get_list($conn, $this->id, $plugin);
        }
    }
	
	
	public static function get_object($conn, $netgroup_id) 
	{
		Ossim_db::check_connection($conn);
		
		$object = NULL;
		
		$perms_where  = (Session::get_ctx_where() != '') ? " AND ctx IN (".Session::get_ctx_where().')' : '';
        $perms_where .= (Session::get_net_where() != '') ? " AND r.net_id IN (".Session::get_net_where().')' : '';
		
		$select = "SELECT g.*, HEX(g.id) AS id, HEX(g.ctx) AS ctx 
            FROM net_group g, net_group_reference r 
		    WHERE g.id = r.net_group_id AND g.id = UNHEX(?) $perms_where";
		
		$query = ossim_query($select);
		$rs = $conn->Execute($query, array($netgroup_id));		
		
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else 
        {
            if (!$rs->EOF) 
            {
                $object = new Net_group($rs->fields['id'], $rs->fields['ctx'], stripslashes($rs->fields['name']) , $rs->fields['threshold_c'], $rs->fields['threshold_a'], stripslashes($rs->fields['rrd_profile']) , stripslashes($rs->fields['descr']) , 1);
            }
        }
        
        return $object;
    }
    
	
	public static function get_list($conn, $where = '', $order = 'ORDER BY name', $cache = FALSE) 
	{
        Ossim_db::check_connection($conn);
        
        $list = array();
        
        $where = preg_replace("/^\s*where /i",'',$where);
        
        $perms_where  = (Session::get_ctx_where() != '') ? " AND g.ctx IN (".Session::get_ctx_where().')' : '';
        $perms_where .= (Session::get_net_where() != '') ? " AND r.net_id IN (".Session::get_net_where().')' : '';
        
		$iffoundrows = preg_match("/LIMIT/i", $order) ? TRUE : FALSE;
        
        $select = ($iffoundrows) ? "SQL_CALC_FOUND_ROWS g.*, HEX(g.id) AS id, HEX(g.ctx) AS ctx" : "g.*, HEX(g.id) AS id, HEX(g.ctx) AS ctx";
        
        $query = ossim_query("SELECT DISTINCT $select FROM net_group g, net n, net_group_reference r 
            WHERE $where".(($where != '') ? " AND" : '')." n.id=r.net_id AND g.id=r.net_group_id $perms_where $order");
      
		
		$rs = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);
        
        if (!$rs) 
		{
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());			
        } 
		else 
		{
			if ($iffoundrows) 
			{			
				$foundrows = 0;
				$rf        = ($cache) ? $conn->CacheExecute("SELECT FOUND_ROWS() AS total".md5($query)) : $conn->Execute("SELECT FOUND_ROWS() AS total");
                
                if (!$rf)
				{
					Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
				}
                else 
				{
					$foundrows = $rf->fields[0];
				}
            }            
                        
            while (!$rs->EOF) 
			{
            	$list[] = new Net_group($rs->fields['id'], $rs->fields['ctx'], stripslashes($rs->fields['name']) , $rs->fields['threshold_c'], 
                    $rs->fields['threshold_a'], stripslashes($rs->fields['rrd_profile']), stripslashes($rs->fields['descr']), $foundrows);
               
                $rs->MoveNext();
            }
        }
        
        return $list;
    }
	
	
    public function get_compromise($conn, $net_group_id) 
    {
        Ossim_db::check_connection($conn);
        
        $compromise = 0;
        $networks = self::get_networks($conn, $net_group_id);
        
        foreach($networks as $network) 
        {
            $net_id   = $network->get_net_id();
            $net_list = Asset_net_qualification::get_list($conn, "WHERE net_id = UNHEX('$net_id')");
            
            if (is_array($net_list)) 
            {
                foreach($net_list as $net) 
                {
                    $compromise+= $net->get_compromise();
                }
            }
        }
        
        return $compromise;
    }
    
    
    public function get_attack($conn, $net_group_id) 
    {
        Ossim_db::check_connection($conn);
        
        $attack = 0;
        $networks = self::get_networks($conn, $net_group_id);
        
        foreach($networks as $network) 
        {
            $net_id   = $network->get_net_id();
            $net_list = Asset_net_qualification::get_list($conn, "WHERE net_id = UNHEX('$net_id')");
            
            if (is_array($net_list)) 
            {
                foreach($net_list as $net) 
                {
                    $attack+= $net->get_attack();
                }
            }
        }
        
        return $attack;
    }
          
    
    public function get_related_sensors($conn, $net_group_id) 
    {
        Ossim_db::check_connection($conn);
        
        $sensors  = array();
        $nets     = array();
        $networks = self::get_networks($conn, $net_group_id);
        
        foreach($networks as $network) 
        {
            $nets[] = $network->get_net_id();
        }
        
        $query = "SELECT HEX(sensor.id) AS sensor FROM sensor, net_sensor_reference
                WHERE net_sensor_reference.net_id IN (UNHEX('".implode("'), UNHEX('",$nets)."')) 
                AND sensor.id = net_sensor_reference.sensor_id 
                ORDER BY sensor.priority DESC, sensor.name ASC";
        
        $rs = $conn->Execute($query);
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else 
        {
            while (!$rs->EOF) 
            {
                $sensors[] = $rs->fields['sensor'];
                $rs->MoveNext();
            }
        }
        
        return $sensors;
    }  
    
      
    public static function insert($conn, $ctx, $name, $threshold_c, $threshold_a, $rrd_profile, $nets, $descr) 
    {
        Ossim_db::check_connection($conn);
        
        settype($threshold_c, 'int');
        settype($threshold_a, 'int');
        $rrd_profile = Util::htmlentities($rrd_profile);
        $name = str_replace(' ', '_', $name);
        
        $new_id = Util::uuid();
        
        $query = "INSERT INTO net_group (id, ctx, name, threshold_c, threshold_a, rrd_profile, descr) VALUES (UNHEX(?), UNHEX(?), ?, ?, ?, ?, ?)";
        
        $params = array(
            $new_id,
            $ctx,
        	$name,
            $threshold_c,
            $threshold_a,
            $rrd_profile,
            $descr
        );
        if ($conn->Execute($query, $params) === FALSE) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        Net_group_reference::insert($conn, $new_id, $nets);
        $infolog = array($name);
        
        Log_action::log(30, $infolog);
        
        return $new_id;
    }
    
    
    public static function update($conn, $id, $ctx, $name, $threshold_c, $threshold_a, $rrd_profile, $nets, $descr) 
    {    
        Ossim_db::check_connection($conn);
        
        $query = "UPDATE net_group SET ctx = UNHEX(?), name = ?, threshold_c = ?, threshold_a = ?, rrd_profile = ?, descr = ? 
            WHERE id = UNHEX(?)";
        
        $params = array(
            $ctx,
        	$name,
            $threshold_c,
            $threshold_a,
            $rrd_profile,
            $descr,
            $id
        );
        
        if ($conn->Execute($query, $params) === FALSE) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        }
        
        Net_group_reference::delete($conn, $id);
        Net_group_reference::insert($conn, $id, $nets);
        
        $infolog = array($name);
        
        Log_action::log(31, $infolog);
        
        return TRUE;           
    }
    
    
    public static function delete($conn, $id) 
    {
        Ossim_db::check_connection($conn);
        
        $params = array($id);
        $query  = "DELETE FROM net_group WHERE id = UNHEX(?)";               
        
        if ($conn->Execute($query, $params) === FALSE) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        }
        
        //KDB Relationships
		$query = "DELETE FROM repository_relationships WHERE keyname = ?";
		
		if ($conn->Execute($query, $params) === FALSE) 
		{
			Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
		}
        
        Net_group_reference::delete($conn, $id);
       
        $infolog = array($id);
        Log_action::log(32, $infolog);
    }
    
    
	public static function can_delete($conn, $net_group_id) 
	{
		Ossim_db::check_connection($conn);
		
		$params = array($net_group_id);
		$query  = "SELECT count(*) AS num FROM policy_net_group_reference WHERE net_group_id = UNHEX(?)";
		
		$rs = $conn->Execute($query, $params);		
		
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            if ($rs->fields['num'] == '0') 
    		{
    		    return 1;
    		}            
        }
				
		return 0;
	}
	
	
    public static function netthresh_c($conn, $id) 
    {
        Ossim_db::check_connection($conn);
        
        $params = array($id);
        $query  = "SELECT * FROM net_group WHERE id = UNHEX(?)";
                
        $rs = $conn->Execute($query, $params);		
		
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            $conf = $GLOBALS['CONF'];
            
            $threshold = $conf->get_conf('threshold');            
        }
        else
        {
            if (!$rs->EOF)
            {
                $threshold = $rs->fields['threshold_c'];
            }
            else
            {
                $conf = $GLOBALS['CONF'];
            
                $threshold = $conf->get_conf('threshold');    
            }
        }
            
        return $threshold;     
    }
    
    
    public static function netthresh_a($conn, $id) 
    {
        Ossim_db::check_connection($conn);
        
        $params = array($id);
        $query  = "SELECT * FROM net_group WHERE id = UNHEX(?)";
                
        $rs = $conn->Execute($query, $params);		
		
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            $conf = $GLOBALS['CONF'];
            
            $threshold = $conf->get_conf('threshold');            
        }
        else
        {
            if (!$rs->EOF)
            {
                $threshold = $rs->fields['threshold_a'];
            }
            else
            {
                $conf = $GLOBALS['CONF'];
            
                $threshold = $conf->get_conf('threshold');    
            }
        }
            
        return $threshold;     
    }
    
    
    public static function isNetInGroup($conn, $net_group, $net) 
    {    
        Ossim_db::check_connection($conn);
        
        $perms_where  = (Session::get_ctx_where() != '') ? " AND g.ctx IN (".Session::get_ctx_where().")" : '';
        $perms_where .= (Session::get_net_where() != '') ? " AND r.net_id IN (".Session::get_net_where().")" : '';
    
        if(valid_hex32($net_group) && valid_hex32($net)) 
        {
            $query = "SELECT * FROM net_group g, net_group_reference r 
                WHERE g.id = r.net_group_id 
                AND net_group_id = UNHEX(?) 
                AND net_id = UNHEX(?) $perms_where";
        }
        else if(is_cidr($net)) 
        { 
            // $net_group is the net group name and $net is the CIDR
            $query = "SELECT * from net_group g, net_group_reference r, net_cidrs nc
                WHERE g.name = ?
                AND g.id = r.net_group_id
                AND r.net_id = nc.net_id
                AND nc.cidr = ? $perms_where";
        }
        else 
        { 
            // $net_group is the net group name and $net is the net name
            $query = "SELECT * from net_group g, net_group_reference r, net n
                WHERE g.name = ?
                AND g.id = r.net_group_id
                AND r.net_id = n.id
                AND n.name = ? $perms_where";
        }
        
        $params = array($net_group, $net);
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;            
        }               
        
        if (!$rs->EOF)
        {
            return TRUE;
        }
        
        return FALSE;
    }
        
   
    public static function isNetInAnyGroup($conn, $net_id) 
    {
        Ossim_db::check_connection($conn);
        
        $params = array($net_id);
        $query  = "SELECT * FROM net_group_reference WHERE net_id = UNHEX(?)";        
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;            
        }               
        
        if (!$rs->EOF)
        {
            return TRUE;
        }
        
        return FALSE;        
    }
    
    
    public static function check_orphan($conn) 
    {
        Ossim_db::check_connection($conn);
        
        $query = "SELECT HEX(net_id) as net_id FROM net_sensor_reference LEFT JOIN sensor ON
                 sensor.id = net_sensor_reference.sensor_id WHERE name IS NULL";
        
        $rs = $conn->Execute($query);
              
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else 
        {
            while (!$rs->EOF) 
            {
                Net_sensor_rerefence::delete($conn, $rs->fields['net_id']);
                
                $rs->MoveNext();
            }
        }
    }    
    
    
	public static function netgroup_exists($conn, $name) 
	{        
        Ossim_db::check_connection($conn);
        
        $perms_where  = (Session::get_ctx_where() != '') ? ' AND ng.ctx IN ('.Session::get_ctx_where().')' : '';
        $perms_where .= (Session::get_net_where() != '') ? ' AND ngr.net_id IN ('.Session::get_net_where().')' : '';
        
        $select = "SELECT * FROM net_group ng, net_group_reference ngr 
            WHERE name = ? AND ng.id=ngr.net_group_id $perms_where";
            
        $query = ossim_query($select);
        $rs    = $conn->Execute($query, array($name));
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return FALSE;
        } 
       
        if (!$rs->EOF) 
        {
            return TRUE;
        }
                
        return FALSE;
    }
    
    public static function get_name_by_id($conn, $id) 
    {
        Ossim_db::check_connection($conn);
        
        $params = array($id);
        $query  = "SELECT name FROM net_group WHERE id = UNHEX(?)";
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
            
            return _('Unknonwn');
        }
        
        return (empty($rs->fields["name"]) ?  _('Unknonwn') : $rs->fields['name']);        
    }	
}

/* End of file net_group.inc */
/* Location: ../include/classes/net_group.inc */ 