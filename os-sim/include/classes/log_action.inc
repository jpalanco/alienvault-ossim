<?php
/**
* log_action.inc
*
* File log_action.inc is used to:
*   - Manage the log_action database table info
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
* - log_action()
* - get_login()
* - get_from()
* - get_date()
* - get_code()
* - get_info()
* - get_descr()
* - get_priority()
* - log()
* - log_action_to_db()
* - get_userip()
* - get_list()
* - get_count()
* - log_action_replace()
* - get_loginfo()
* - get_last_pass()
*/

class Log_action 
{
    /**
     * Login
     * 
     * @var string
     */
    private $login;


    /**
     * From
     * 
     * @var number
     */
    private $from;


    /**
     * Date
     * 
     * @var string
     */
    private $date;


    /**
     * Code
     * 
     * @var number
     */
    private $code;


    /**
     * Info
     * 
     * @var string
     */
    private $info;


    /**
     * Description
     * 
     * @var string
     */
    private $descr;


    /**
     * Priority
     * 
     * @var number
     */
    private $priority;


    /**
     * This function sets up the class object
     * 
     * @param string $login
     * @param number $from
     * @param string $date
     * @param number $code
     * @param string $info
     * @param string $descr
     * @param number $priority
     */
    public function __construct($login, $from, $date, $code, $info, $descr, $priority) 
    {
        $this->login    = $login;
        $this->from     = Util::htmlentities($from, ENT_COMPAT, "UTF-8");
        $this->date     = $date;
        $this->code     = $code;
        $this->info     = $info;
        $this->descr    = $descr;
        $this->priority = $priority;
    }


    /**
     * This function returns the login
     * 
     * @return string
     */
    public function get_login() 
    {
        return $this->login;
    }


    /**
     * This function returns the from
     * 
     * @return number
     */
    public function get_from() 
    {
        return $this->from;
    }


    /**
     * This function returns the date
     * 
     * @return string
     */
    public function get_date() 
    {
        return $this->date;
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
     * This function returns the info
     * 
     * @return string
     */
    public function get_info() 
    {
        return $this->info;
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
     * This function inserts a row into log_action table and a new line in syslog file
     * 
     * @param number $log_code
     * @param array $extrainfo
     * 
     * @return boolean
     */
    public static function log($log_code, $extrainfo = array()) 
    {      
        $conf = $GLOBALS["CONF"];
                									
        if (!$conf)
        {
            require_once 'ossim_conf.inc';
            
            $conf = new Ossim_conf();
            $GLOBALS["CONF"] = $conf;
        }        
        
        $logact = $conf->get_conf("user_action_log");
        
        if (!$logact)
        {
            return FALSE;
        }
        
        $db =   new ossim_db();
        $conn = $db->connect();
        
        if (!self::log_action_to_db($conn, $log_code)) 
        {
            return FALSE;
        }
        
        $user_ip = self::get_userip();
        $user    = Session::get_session_user();
        
        if ($user == "" && $extrainfo[0] != "") 
        {
            $user = $extrainfo[0];
        }
        
        $info = self::get_loginfo($conn, $log_code);
        
        $info = self::log_action_replace($info, $extrainfo);
        $log_syslog = $conf->get_conf("log_syslog");
        
        if ($log_syslog) 
        {
            $user_data = '';
            for( $count = 0; $count<5;){

                $user_data_param = ($extrainfo[$count]) ? str_replace("-","",$extrainfo[$count]) : '-';
                ++$count;
                $user_data .= 'userdata'.$count.'="'.$user_data_param.'": ' ;
            };

            //define_syslog_variables(); // This function has been DEPRECATED as of PHP 5.3.0 and REMOVED as of PHP 5.4.0
            openlog("OSSIM", LOG_PID | LOG_PERROR, LOG_AUTH);
            syslog(LOG_NOTICE, "User event sid=\"$log_code\": src_ip=\"$user_ip\":  username=\"$user \": $user_data msg=\"$info\"");
        }
        // tmp fix DK: timestamp is limited to seconds, two users can do something at the same second
        $sql = "INSERT IGNORE INTO log_action (login, ipfrom, date, code, info) VALUES (?, ?, UTC_TIMESTAMP() , ?, ?)";

        $params = array(
            $user,
            $user_ip,
            $log_code,
            $info
        );
        
        if ($conn->Execute($sql, $params) === FALSE) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        return TRUE;
    }


    /**
     * This function returns the log number for an action into database
     * 
     * @param object $conn
     * @param number $action_log
     * 
     * @return number
     */
    public static function log_action_to_db($conn, $action_log) 
    {
        Ossim_db::check_connection($conn);
        
        $sql    = "SELECT * FROM log_config where code = ?";
        $params = array(
            $action_log
        );
        
        $rs = $conn->Execute($sql, $params);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        else 
        {
            return (intval($rs->fields['log']));
        }
    }


    /**
     * This function returns the client IP address
     * 
     * @return string
     */
    public static function get_userip() 
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) != "") 
        {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        
        return $_SERVER['REMOTE_ADDR'];
    }


    /**
     * This function returns the list of actions
     * 
     * @param object $conn
     * @param string $filter
     * @param string $order
     * @param number $inf
     * @param number $sup
     * 
     * @return multitype:|multitype:Log_action
     */
    public static function get_list($conn, $filter = "", $order = "", $inf = 0, $sup = 25) 
    {
        Ossim_db::check_connection($conn);

        $tz     = Util::get_timezone();

        /* number of logs per page */
        $nlogs = $sup - $inf;
        $query = ossim_query("SELECT * from log_action where 1=1 $filter $order LIMIT $nlogs OFFSET $inf");

        $codes = Log_config::get_list($conn); // Caching log codes
        
        $list = array();
        $rs   = $conn->Execute($query);
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        while (!$rs->EOF) 
        {
            $code   = $rs->fields["code"];
            $descr  = (is_object($codes[$code])) ? $codes[$code]->get_descr() : "";
            $prio   = (is_object($codes[$code])) ? $codes[$code]->get_priority() : "";
            $rs->fields['date'] = gmdate('Y-m-d H:i:s', Util::get_utc_unixtime($rs->fields['date']) + 3600*$tz);
            $list[] = new Log_action($rs->fields["login"], $rs->fields["ipfrom"], $rs->fields["date"], $code, $rs->fields["info"], $descr, $prio);
            
            $rs->MoveNext();
        }
        
        return $list;
    }


    /**
     * This function returns the last password by user
     * 
     * @param unknown_type $conn
     * @param unknown_type $user
     * @return multitype:|multitype:NULL
     */
    public static function get_last_pass($conn, $user="") 
    {
        Ossim_db::check_connection($conn);
        
        require_once 'ossim_conf.inc';
    	    	
        $conf = $GLOBALS["CONF"];
                									
        if (!$conf)
        {
            $conf = new Ossim_conf();
        } 
    	    
        $list         = array();
        $pass_history = ($conf->get_conf("pass_history")) ? $conf->get_conf("pass_history") : 0;
        $query        = "SELECT DISTINCT pass,salt FROM pass_history WHERE user='$user' ORDER BY hist_number DESC LIMIT $pass_history";
        
        $rs = $conn->Execute($query);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        while (!$rs->EOF) 
		{
            $list[] = $rs->fields;
            $rs->MoveNext();
        }
		
        return $list;
    }

    public static function recent_pass_exists($conn, $login, $pass1) {
        $recent_pass = Log_action::get_last_pass($conn, $login);
        if (count($recent_pass) > 0) {
            foreach ($recent_pass as $pass) {
                if (($pass["pass"] === Session::hash_the_pass(Session::salt_the_pass($pass["salt"],$pass1))) || ($pass["pass"] === md5($pass1))) {
                    return true;
                }
            }
        }
        return false;
    }


    /**
     * This function returns the number of log actions
     * 
     * @param object $conn
     * @param string $cfilter
     * 
     * @return number
     */
    public static function get_count($conn, $cfilter) 
    {
        Ossim_db::check_connection($conn);
        
        $query = ossim_query("SELECT count(*) AS count FROM log_action $cfilter");
        
        $rs = $conn->Execute($query);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        return $rs->fields["count"];
    }


    /**
     * This function replaces the variables into the log message string
     * 
     * @param string $infostr
     * @param array  $arrplace
     * 
     * @return string
     */
    public static function log_action_replace($infostr, $arrplace) 
    {
        if (is_array($arrplace) && count($arrplace))
        {
            $n = 1;
            foreach($arrplace as $auxrplc) 
            {
                $infostr = str_replace("%" . $n . "%", $auxrplc, $infostr);
                $n = $n + 1;
            }
        }
        
        return $infostr;
    }


    /**
     * This function returns the description of a log entry
     * 
     * @param object $conn
     * @param number $code
     * 
     * @return string
     */
    public static function get_loginfo($conn, $code) 
    {
        Ossim_db::check_connection($conn);
        
        $sql = "SELECT * from log_config WHERE code = ?";
        
        $params = array($code);
        
        $rs = $conn->Execute($sql, $params);
        
        if (!$rs) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        } 
        
        return $rs->fields["descr"];
    }


    /**
     * This function deletes a log action entry by date and info
     * 
     * @param object $conn
     * @param string $date
     * @param string $info
     */
    public static function delete_by_date_info($conn, $date, $info) 
    {
        Ossim_db::check_connection($conn);
        
        $params = array(
            $date,
            $info
        );

        $tzc = Util::get_tzc();

        $sql = "DELETE FROM log_action WHERE convert_tz(log_action.date,'+00:00','".$tzc."')=? AND info=?";
        
        if (!$conn->Execute($sql, $params)) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }


    /**
     * This function deletes a log entry by user and code
     * 
     * @param object $conn
     * @param string $user
     * @param number $code
     */
    public static function delete_by_user_code($conn, $user, $code) 
    {
        Ossim_db::check_connection($conn);
        
        $where = '';
        
        if ($user != '') 
        {
            $where.= "where login='$user'";
        }
        
        if ($code != '') 
        {
            if ($where != '')
            {
                $where.= "and code='$code'";
            }
            else
            {
                $where.= "where code='$code'";
            }
        }

        $sql = "DELETE FROM log_action $where";
        
        if (!$conn->Execute($sql)) 
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }	
}

/* End of file log_action.inc */
/* Location: ../include/classes/log_action.inc */
