<?php
/**
* Policy_role_reference.inc
*
* File Policy_role_reference.inc is used to:
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
* @package    ossim-framework\Policy&Actions
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/

/**
* Function list:
* - Policy_role_reference()
* - get_policy_id()
* - get_correlate()
* - get_cross_correlate()
* - get_store()
* - get_qualify()
* - get_resend_alarm()
* - get_resend_event()
* - get_sign()
* - get_sem()
* - get_sim()
* - get_list()
* - get_reputation()
* - insert()
* - update()
* - delete()
*/

class Policy_role_reference 
{
    public $policy_id;
    

    public $correlate;
    

    public $cross_correlate;
    

    public $store;
    

    public $qualify;
    

    public $resend_alarm;
    

    public $resend_event;
    

	public $sign;
	

	public $sem;
	

	public $sim;
	

	public $reputation;
	
	
    public function __construct($policy_id, $correlate, $cross_correlate, $store, $qualify, $resend_alarm, $resend_event, $sign, $sem, $sim, $reputation) 
    {
        $this->policy_id       = $policy_id;
        $this->correlate       = $correlate;
        $this->cross_correlate = $cross_correlate;
        $this->store           = $store;
        $this->qualify         = $qualify;
        $this->resend_alarm    = $resend_alarm;
        $this->resend_event    = $resend_event;
        $this->sign            = $sign;
        $this->sem             = $sem;
        $this->sim             = $sim;
        $this->reputation      = $reputation;
    }
    
	
    public function get_policy_id() 
    {
        return $this->policy_id;
    }
	
	
    public function get_correlate() 
    {
        return $this->correlate;
    }
	
	
    public function get_cross_correlate() 
    {
        return $this->cross_correlate;
    }
	
	
    public function get_store() 
    {
        return $this->store;
    }
	
	
    public function get_qualify() 
    {
        return $this->qualify;
    }
    
	
    public function get_resend_alarm() 
    {
        return $this->resend_alarm;
    }
	
	
    public function get_resend_event() 
    {
        return $this->resend_event;
    }
	
	
    public function get_sign() 
    {
        return $this->sign;
    }
	
	
    public function get_sem() 
    {
        return $this->sem;
    }
	
	
    public function get_sim() 
    {
        return $this->sim;
    }
	
	
	public function get_reputation() 
	{
        return $this->reputation;
    }
	
	
    public static function get_list($conn, $policy_id)
    {
        $list      = array();
        $policy_id = $policy_id;
        $query     = "SELECT *, HEX(policy_id) as policy_id FROM policy_role_reference WHERE policy_id = UNHEX(?)";
		$params    = array($policy_id);
		
        $rs = $conn->Execute($query, $params);
		
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else 
        {         
            while (!$rs->EOF)
            {
                $list[] = new self($rs->fields["policy_id"], $rs->fields["correlate"], $rs->fields["cross_correlate"], $rs->fields["store"], $rs->fields["qualify"], 
                    $rs->fields["resend_alarm"], $rs->fields["resend_event"], $rs->fields["sign"], $rs->fields["sem"], $rs->fields["sim"], $rs->fields["reputation"]);
                
                $rs->MoveNext();
            }
        }
        
        return $list;
    }


    public static function insert($conn, $correlate, $cross_correlate, $store, $qualify, $resend_alarm, $resend_event, $sign, $sem, $sim, $reputation, $policy_id)
    {    
		$policy_id       = $policy_id;
        $correlate       = intval($correlate);
        $cross_correlate = intval($cross_correlate);
        $store           = intval($store);
        $qualify         = intval($qualify);
        $resend_alarm    = intval($resend_alarm);
        $resend_event    = intval($resend_event);
        $sign            = intval($sign);
        $sem             = intval($sem);
        $sim             = intval($sim);
        $reputation      = intval($reputation);
		

        $query  = "INSERT INTO policy_role_reference (policy_id,correlate,cross_correlate,store,qualify,resend_alarm,resend_event,sign,sem,sim,reputation) VALUES (UNHEX(?), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = array(
            $policy_id,
            $correlate,
            $cross_correlate,
            $store,
            $qualify,
            $resend_alarm,
            $resend_event,
            $sign,
            $sem,
            $sim,
            $reputation
        );
        
        if (!$conn->Execute($query, $params)) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }        
    }
	
	
    public static function update($conn, $correlate, $cross_correlate, $store, $qualify, $resend_alarm, $resend_event, $policy_id, $sign, $sem, $sim, $reputation) 
    {
        $policy_id       = $policy_id;
        $correlate       = intval($correlate);
        $cross_correlate = intval($cross_correlate);
        $store           = intval($store);
        $qualify         = intval($qualify);
        $resend_alarm    = intval($resend_alarm);
        $resend_event    = intval($resend_event);
        $sign            = intval($sign);
        $sem             = intval($sem);
        $sim             = intval($sim);        
        
        $query  = "UPDATE policy_role_reference SET correlate = ?, cross_correlate = ?, store = ?, qualify = ?, resend_alarm = ?, resend_event =?, sign=?, sem=?, sim=?, reputation=? WHERE policy_id = UNHEX(?)";
        $params = array(
            $correlate,
            $cross_correlate,
            $store,
            $qualify,
            $resend_alarm,
            $resend_event,
            $sign,
            $sem,
            $sim,
            $reputation,
			$policy_id
        );
        
        if ($conn->Execute($query, $params) === FALSE) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }        
    }


    public static function set($conn, $id, $field) 
    {
        $query  = "UPDATE policy_role_reference SET ".$field."=NOT(".$field.") WHERE policy_id=UNHEX(?)";
        $params = array($id);
        
        if ($conn->Execute($query, $params) === FALSE) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }


    public static function delete($conn, $policy_id)
    {
        $query  = "DELETE FROM policy_role_reference WHERE policy_id = UNHEX(?)";
        $params = array($policy_id);
		
        if ($conn->Execute($query, $params) === FALSE) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }
}

/* End of file policy_role_reference.inc */
/* Location: ../include/classes/policy_role_reference.inc */
