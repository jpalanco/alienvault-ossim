<?php
/**
* av_exception.inc
*
* File av_exception.inc is used to:
*   - To manage exceptions (from PHP, User, Database, etc.)
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


require_once 'av_config.php';


/**
* Av_exception Class
*
* Class for managing exceptions
*
* @package    ossim-framework\Asset
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ Alienvault web page
*/

class Av_exception
{ 
    /**
    * Error types
    */
    
    const USER_ERROR = 1;  // Error throw by user
    
    const DB_ERROR   = 2;  // Database error (Queries with wrong format, DB down, etc)
    
    const PHP_ERROR  = 3;  // PHP Errors
    
    
    /**
    * Error types
    *
    * @var array
    * @access private
    */  
    private static $_error_types = array(
        '1' => array('USER ERROR',     'Operation was not completed due to an error'),
        '2' => array('DATABASE ERROR', 'Operation was not completed due to a database error'),
        '3' => array('PHP ERROR',      'Operation was not completed due to an error')
    );
  
           
        
    /**
    * Valid PHP date() format string for log timestamps
    *
    * @var string
    * @access private
    */  
    private static $_date_format = 'D M d h:i:s Y';
         
    
    /**
    * Path to the log file
    *
    * @var string
    * @access private
    */    
    private static $_log_file = '/var/log/alienvault/ui/ui.log';
    
    
    /**
    * This function writes a message to log file and throws a exception
    *
    * @param string  $exp_type      Exception Type
    * @param string  $log_message   [Optional] Message for log file
    *
    * @return void
    */
    public static function throw_error($exp_type, $log_message = '')
    {     
        if (array_key_exists($exp_type, self::$_error_types))
        {
            $log_message = (empty($log_message)) ? self::$_error_types[$exp_type][1]: $log_message;
        
            //Write message to log file
            self::write_log($exp_type, $log_message);            
            
            self::throw_exp($exp_type, $log_message);
        } 
    }
    
    
    /**
    * This function throws a formatted PHP exception
    *
    * @param string  $exp_type      Exception Type
    * @param string  $log_message   [Optional] Message for log file
    *
    * @return void
    */
    public static function throw_exp($exp_type, $log_message = '')
    { 
        //Throw exception
        if ($exp_type == self::USER_ERROR)
        {
            throw new Exception($log_message);
        }
        else
        {
            throw new Exception(self::$_error_types[$exp_type][1]);
        }        
    }
    
    
    /**
    * This function writes a message to log file 
    *
    * @param string  $exp_type      Exception Type
    * @param string  $log_message   Message for log file
    *
    * @return void
    */
    public static function write_log($exp_type, $log_message = 'Unknown error')
    {
        $date        = date(self::$_date_format);
        $error_type  = self::$_error_types[$exp_type][0];
        $e_info      = self::get_extra_info($log_message);

        // Show detailed info about the error
        if (!empty($e_info))
        {
            $log_message .= ' called in '.$_SERVER['PHP_SELF'].' and defined in '.$e_info['file'].' on line '.$e_info['line'].' ('.$e_info['function'].')';
        }
        
        $log_line = '['.$date.'] ['.$error_type.'] ['.$_SERVER['SERVER_ADDR'].'] '.$log_message."\n";
        
        if (is_readable(self::$_log_file))
        {
            error_log($log_line, 3, self::$_log_file);
        }
    }
    
    
    /**
    * This function returns the detailed information about last error occurred in the system
    *
    * @param string  $log_message  Message for log file
    *
    * @return array
    */
    private static function get_extra_info($log_message)
    {
        $extra_info = array();

        $aux_info = debug_backtrace();

        array_shift($aux_info);

        if (!empty($aux_info) && $aux_info[0]['args'][1] == $log_message)
        {
            $extra_info = array(
                'file'     => '<file>',
                'line'     => '<line>',
                'function' => '<function>'
            );

            foreach($aux_info as $t_info)
            {
                if ($t_info['function'] != 'write_log' && $t_info['function'] != 'throw_error')
                {
                    $extra_info = array(
                        'file'     => $t_info['file'],
                        'line'     => $t_info['line'],
                        'function' => $t_info['function']
                    );

                    break;
                }
            }
        }

        return $extra_info;
    }
    
    
    /**
    * This function displays a formatted exception
    *
    * @param object  $exp  Exception Object
    *
    * @return void
    */
    public static function display($exp)
    {
        $error = new Av_error($exp->getMessage());
        
        $error->display();
    }    
}

/* End of file av_exception.inc */
/* Location: ../include/classes/av_exception.inc */ 