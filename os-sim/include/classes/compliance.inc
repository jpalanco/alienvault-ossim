<?php
/**
* compliance.inc
*
* File compliance.inc is used to:
*   - Manage the data in iso and pci databases
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
* @package    ossim-framework\Compliance
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/

class Compliance 
{
    public $descr;
	
    public $plugin_name;
	
    public $sid;
	
    public $targeted;
	
    public $untargeted;
	
    public $approach;
	
    public $exploration;
	
    public $penetration;
	
    public $generalmalware;
	
    public $imp_qos;
    
    public $imp_infleak;
	
    public $imp_lawful;
	
    public $imp_image;
	
    public $imp_financial;
	
    public $D;
	
    public $I;
	
    public $C;
	
    public $net_anomaly;

    public function __construct($data) 
    {
        $this->descr          = $data['descr'];
        $this->plugin_name    = $data['plugin_name'];
        $this->sid            = $data['sid'];
        $this->targeted       = $data['targeted'];
        $this->untargeted     = $data['untargeted'];
        $this->approach       = $data['approach'];
        $this->exploration    = $data['exploration'];
        $this->penetration    = $data['penetration'];
        $this->generalmalware = $data['generalmalware'];
        $this->imp_qos        = $data['imp_qos'];
        $this->imp_infleak    = $data['imp_infleak'];
        $this->imp_lawful     = $data['imp_lawful'];
        $this->imp_image      = $data['imp_image'];
        $this->imp_financial  = $data['imp_financial'];
        $this->D              = $data['D'];
        $this->I              = $data['I'];
        $this->C              = $data['C'];
        $this->net_anomaly    = $data['net_anomaly'];
    }
	
	public function get_descr() 
    {
		return $this->descr;
	}
    
	public function get_plugin_name() 
    {
		return $this->plugin_name;
	}
    
    
	public function get_sid() 
    {
		return $this->sid;
	}
    
    
	public function get_targeted() 
    {
		return $this->targeted;
	}
    
    
	public function get_untargeted() 
    {
		return $this->untargeted;
	}
    
    
	public function get_approach() 
    {
		return $this->approach;
	}
    
    
	public function get_penetration() 
    {
		return $this->penetration;
	}
    
    
	public function get_exploration() 
    {
		return $this->exploration;
	}
    
    
	public function get_generalmalware() 
    {
		return $this->generalmalware;
	}
    
    
	public function get_imp_qos() 
    {
		return $this->imp_qos;
	}
    
    
	public function get_imp_infleak() 
    {
		return $this->imp_infleak;
	}
    
    
	public function get_imp_lawful() 
    {
		return $this->imp_lawful;
	}
    
    
	public function get_imp_image() 
    {
		return $this->imp_image;
	}
    
    
    public function get_imp_financial() 
    {
		return $this->imp_financial;
	}
    
    
	public function get_D() 
    {
		return $this->D;
	}
    
    
	public function get_I() 
    {
		return $this->I;
	}
    
    
	public function get_C() 
    {
		return $this->C;
	}
    
    
	public function get_net_anomaly() 
    {
		return $this->net_anomaly;
	}
	
	
    public static function update($conn, $data) 
    {
		Ossim_db::check_connection($conn);
        
        $query = "INSERT INTO datawarehouse.category (sid,targeted,untargeted,approach,exploration,penetration,generalmalware,imp_qos,imp_infleak,imp_lawful,imp_image,imp_financial,D,I,C,net_anomaly)
                            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) 
                            ON DUPLICATE KEY UPDATE targeted=?, untargeted=?, approach=?,
                            exploration=?, penetration=?, generalmalware=?,
                            imp_qos=?, imp_infleak=?, imp_lawful=?, imp_image=?,
                            imp_financial=?, D=?, I=?, C=?, net_anomaly=?";
		$params = array(
			$data['sid'],
			$data['targeted'],
			$data['untargeted'],
			$data['approach'],
			$data['exploration'],
			$data['penetration'],
			$data['generalmalware'],
			$data['imp_qos'],
			$data['imp_infleak'],
			$data['imp_lawful'],
			$data['imp_image'],
			$data['imp_financial'],
			$data['D'],
			$data['I'],
			$data['C'],
			$data['net_anomaly'],
		        
	        $data['targeted'],
	        $data['untargeted'],
	        $data['approach'],
	        $data['exploration'],
	        $data['penetration'],
	        $data['generalmalware'],
	        $data['imp_qos'],
	        $data['imp_infleak'],
	        $data['imp_lawful'],
	        $data['imp_image'],
	        $data['imp_financial'],
	        $data['D'],
	        $data['I'],
	        $data['C'],
	        $data['net_anomaly'],
        );
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        $infolog = array($data['sid']);
        
        Log_action::log(88, $infolog);
    }
    
    
    public static function delete($conn, $sid) 
    {
		Ossim_db::check_connection($conn);
        
        $params = array($sid);
        $query  = "DELETE FROM datawarehouse.category WHERE sid=?";
        
        $rs = $conn->Execute($query, $params);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        $infolog = array($sid);
        Log_action::log(89, $infolog);
	}
    
    
	public static function get_category($conn, $where = '', $order = '') 
    { 
        Ossim_db::check_connection($conn);
        
        $order = preg_replace("/ORDER BY sid/", "ORDER BY plugin_sid.sid", $order);
        
		$query = "SELECT SQL_CALC_FOUND_ROWS DISTINCT plugin_sid.name AS plugin_name, category.*, plugin_sid.sid AS sid
            FROM alienvault.plugin_sid 
            LEFT JOIN datawarehouse.category ON category.sid=plugin_sid.sid
            WHERE plugin_sid.plugin_id = 1505 AND plugin_sid.name <> '' $where $order";
                        
        //echo $query;
        
		$ret = array();
        
        $rs = $conn->Execute($query);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        // Total rows
        $total = $conn->Execute("SELECT FOUND_ROWS() AS counter");
        
        $num_rows = 0;
        if (!$total->EOF) 
        {
            $num_rows = $total->fields['counter'];
        }
        
        while (!$rs->EOF) 
        {
            $ret[] = new Compliance($rs->fields);
            $rs->MoveNext();
        }
        
        return array($ret, $num_rows);
    }
}

/* End of file compliance.inc */
/* Location: ../include/classes/compliance.inc */
