<?php
/**
* dba_shared.inc
*
* File dba_shared.inc is used to:
*   - Share variables between PHP files using database files
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
* @package    ossim-framework\Session
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/

/**
* DBA_shared Class
*
* This class is used to share variables between PHP files using database files
*
*
* @package    ossim-framework\Session
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ AlienVault web page
*/
class DBA_shared 
{        
    /**#@+
    * Attributes
    */
    
    /**
    * @var string  Database file
    * @access private 
    */
    private $dbfile;
        
    
    /**
    * Class constructor
    *
    * This function sets up the class
    *
    * @param string $dbfile  [Optional] Database file
    */
    public function __construct($dbfile = '')
    {
        
        // Creates a file with a unique filename, with access permission set to 0600, in the specified directory
        $this->dbfile = ($dbfile!='') ? $dbfile : tempnam("/var/tmp", "report_");
        
        if (!preg_match("/^\/var\/tmp/",$this->dbfile))
        {
            $this->dbfile = "/var/tmp/report_".$this->dbfile.".db";
        }
    }
    
    /**
    * This function truncates the created database
    *
    */  
    public function truncate()
    {        
        if ($f = @dba_open($this->dbfile, "n", "db4")) // Create empty db
        {
            dba_insert("create",date("Y-m-d H:i:s"),$f);
            dba_close($f);
        }
    }
       
    /**
    * This function gets the value of key $key from database
    *
    * @param string $key  Access key
    *
    * @return string
    */  
    public function get($key)
    {
        $value = '';
        
        if ($f = @dba_open($this->dbfile, "rl", "db4")) 
        {
            if (dba_exists($key,$f)) 
            {
                $value = dba_fetch($key,$f);
                $aux   = @unserialize($value);
                return ($aux == FALSE) ? $value : unserialize($value);
            }
            
            dba_close($f);
        }
        
        return $value;
    }
    
    /**
    * This function updates the value of key $key into the database
    *
    * @param string $key    Access key
    * @param string $value  New value
    *
    * @return boolean
    */  
    public function put($key, $value)
    {
        if ($f = @dba_open($this->dbfile, "wl", "db4")) 
        {
            if (is_array($value))
            {
                $value = serialize($value);
            }
            
            dba_insert($key,$value,$f);
            dba_close($f);
            
            return TRUE;
        }
        
        return FALSE;
    }

    
    /**
    * This function gets temporary filename used to create the database
    *
    * @return string
    */  
    public function dbfile()
    {
        return $this->dbfile;
    }
}

/* End of file dba_shared.inc */
/* Location: ../include/classes/dba_shared.inc */
