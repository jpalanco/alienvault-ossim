<?php
/**
* log_config.inc
*
* File log_config.inc is used to:
*   - Manage the data in log_config database table
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
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


/**
* Function list:
* - Log_config()
* - get_code()
* - get_log()
* - get_descr()
* - get_priority()
* - get_list()
* - update_log()
*/



class Log_config 
{
    /**
     * Code
     * 
     * @var number
     */
	public $code;


	/**
	 * Log
	 * 
	 * @var number
	 */
    public $log;


    /**
     * Description
     * 
     * @var string
     */
    public $descr;


    /**
     * Priority
     * 
     * @var number
     */
    public $priority;


    /**
     * This function sets up the class object
     * 
     * @param number $code
     * @param number $log
     * @param string $descr
     * @param number $priority
     */
	public function __construct($code, $log, $descr, $priority) 
	{
        $this->code     = $code;
        $this->log      = $log;
        $this->descr    = $descr;
        $this->priority = $priority;
    }


	/**
	 * This function returns the code
	 * 
	 * @return number
	 */
    public function get_code() 
    {
        return $this->code;
    }


	/**
	 * This function returns the log value
	 * 
	 * @return number
	 */
    public function get_log() 
    {
        return $this->log;
    }


	/**
	 * This function returns the description
	 * 
	 * @return string
	 */
    public function get_descr() 
    {
        return $this->descr;
    }


	/**
	 * This function returns the priority
	 * 
	 * @return number
	 */
    public function get_priority() 
    {
        return $this->priority;
    }


	/**
	 * This function returns the list of the data into log_config table
	 * 
	 * @param object $conn
	 * @param string $args
	 * 
	 * @return multitype:|Log_config
	 */
    public static function get_list($conn, $args = "") 
    {
        Ossim_db::check_connection($conn);
        
        $list  = array();
        $query = ossim_query("SELECT * FROM log_config $args");
        $rs    = $conn->Execute($query);
        
        if (!$rs) 
		{
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
		
        while (!$rs->EOF) 
        {
            $list[$rs->fields["code"]] = new self($rs->fields["code"], $rs->fields["log"], $rs->fields["descr"], $rs->fields["priority"]);
            $rs->MoveNext();
        }
        
        return $list;
    }


	/**
	 * This function updates a log_config table row
	 * 
	 * @param object $conn
	 * @param number $code
	 * @param number $log
	 * 
	 * @return boolean
	 */
    public static function update_log($conn, $code, $log) 
    {
        Ossim_db::check_connection($conn);
        
        $sql    = "UPDATE log_config SET log = ? WHERE code = ?";
        $params = array(
            $log,
            $code
        );
		
        if (!$conn->Execute($sql, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
		return TRUE;
    }
}

/* End of file log_config.inc */
/* Location: ../include/classes/log_config.inc */
