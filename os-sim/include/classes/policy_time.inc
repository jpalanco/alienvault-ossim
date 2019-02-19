<?php
/**
* Policy_time.inc
*
* File Policy_time.inc is used to:
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


class Policy_time 
{
	public $id;
	
	
	public $policy_id;
	
	
	public $minute_start;
	
	
	public $minute_end;
	
	
	public $hour_start;
	
	
	public $hour_end;
	
	
	public $week_day_start;
	
	
	public $week_day_end;
	
	
	public $month_day_start;
	
	
	public $month_day_end;
	
	
	public $month_start;
	
	
	public $month_end;
	
	
	public $tzone;
	
    
    public function __construct($id, $policy_id, $tzone, $minute_start, $minute_end, $hour_start, $hour_end, $week_day_start, $week_day_end, $month_day_start, $month_day_end, $month_start, $month_end) 
    {	
        $this->id              = $id;
		$this->policy_id       = $policy_id;
		$this->minute_start    = $minute_start;
		$this->minute_end      = $minute_end;
		$this->hour_start      = $hour_start;
		$this->hour_end        = $hour_end;
		$this->week_day_start  = $week_day_start;
		$this->week_day_end    = $week_day_end;
		$this->month_day_start = $month_day_start;
		$this->month_day_end   = $month_day_end;
		$this->month_start     = $month_start;
		$this->month_end       = $month_end	;
		$this->timezone        = $tzone;		
    }
	
	
	public function get_id() 
	{
        return $this->id;
    }
	
	
    public function get_policy_id() 
    {
        return $this->policy_id;
    }
	
	
    public function get_minute_start() 
    {
        return $this->minute_start;
    }
	
	
	public function get_minute_end() 
	{
        return $this->minute_end;
    }
	
	
	public function get_hour_start() 
	{
        return $this->hour_start;
    }
	
	
	public function get_hour_end() 
	{
        return $this->hour_end;
    }
	
	
	public function get_week_day_start() 
	{
        return $this->week_day_start;
    }
	
	
	public function get_week_day_end() 
	{
        return $this->week_day_end;
    }
	
	
	public function get_month_day_start() 
	{
        return $this->month_day_start;
    }
	
	
	public function get_month_day_end() 
	{
        return $this->month_day_end;
    }
	
	
	public function get_month_start()
	{
        return $this->month_start;
    }
	
	
	public function get_month_end() 
	{
        return $this->month_end;
    }
	
	
	public function get_timezone() 
	{
        return $this->timezone;
    }
	
	
    public static function get_list($conn, $policy_id)
    {
        $list      = array();
        $query     = "SELECT *, HEX(policy_id) AS policy_id FROM policy_time_reference WHERE policy_id = UNHEX(?)";
		$params    = array($policy_id);
		
		$rs = $conn->Execute($query, $params);
        if (!$rs) 
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else 
        {
            $list = array();
            
            while (!$rs->EOF) 
            {
				$list = new self($rs->fields["id"], $rs->fields["policy_id"], $rs->fields["timezone"], $rs->fields["minute_start"], $rs->fields["minute_end"], $rs->fields["hour_start"], $rs->fields["hour_end"], $rs->fields["week_day_start"], $rs->fields["week_day_end"], $rs->fields["month_day_start"], $rs->fields["month_day_end"], $rs->fields["month_start"], $rs->fields["month_end"]);
                
                $rs->MoveNext();
            }
        }
        
        return $list;        
    }
	
	
    public static function insert($conn, $policy_id, $tzone, $minute_start, $minute_end, $hour_start, $hour_end, $week_day_start, $week_day_end, $month_day_start, $month_day_end, $month_start, $month_end)
    {
		//Patch to avoid failures if the timezone column is not inserted:
		$rs = $conn->Execute("Select *, count(*) from policy_time_reference");
		if (!$rs) 
		{
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else 
        {
			if(!array_key_exists("timezone", $rs->fields))
			{
    			$rs = $conn->Execute("ALTER TABLE `policy_time_reference` ADD `timezone` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'UTC'");
    			
				if (!$rs) 
				{
					Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
				}
			}
		}

		$query = "INSERT INTO policy_time_reference (policy_id, timezone, minute_start, minute_end, hour_start, hour_end, week_day_start, week_day_end, month_day_start, month_day_end, month_start, month_end)  VALUES (UNHEX(?), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = array(
            $policy_id, 
			$tzone,
			$minute_start, 
			$minute_end, 
			$hour_start, 
			$hour_end, 
			$week_day_start, 
			$week_day_end, 
			$month_day_start, 
			$month_day_end, 
			$month_start, 
			$month_end
        );
		
        if (!$conn->Execute($query, $params)) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }     
    }
	
	
    public static function delete($conn, $policy_id)
    {
        $query  = "DELETE FROM policy_time_reference WHERE policy_id = UNHEX(?)";
        $params = array($policy_id);
        
        if ($conn->Execute($query, $params) === FALSE) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }
}

/* End of file policy_time.inc */
/* Location: ../include/classes/policy_time.inc */
