<?php
/**
* frameworkd_socket.inc
*
* File frameworkd_socket.inc is used to:
*   - To manage Frameworkd
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
* @package    ossim-framework\Frameworkd
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/

/**
* Example:
*
* try
* {
*     $s = new Frameworkd_socket();
*     
*     if($s->write('nagios action="add" type="host" list="192.168.1.150 appLIanCE|192.168.1.119 albertossim"'))
*         echo "OK! :) <br>";
*     else
*         echo "oops.. :( <br>";
* }
* catch(Exception $e)
* {
*     echo $e->getMessage()
* }
*
*/


/**
* Frameworkd_socket Class
*
* Class for managing Frameworkd
*
* @package    ossim-framework\Assets
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ Alienvault web page
*/

class Frameworkd_socket extends Socket 
{    
    /**
    * Class constructor
    *
    * This function sets up the class
    * 
    */    
    public function __construct() 
    {        
        $this->config();
    
        parent::__construct($this->address, $this->port);            
    }

    
    /**
    * This function sets configuration parameters
    *
    * @throws Exception  If an error occurred
    *
    * @return void      
    */
    public function config()
    {                
        $conf = $GLOBALS['CONF'];
        									
        if (!$conf)
        {
            $conf = new Ossim_conf();
            $GLOBALS['CONF'] = $conf;
        }        
        
        $this->address = $conf->get_conf('frameworkd_address');
        $this->port    = $conf->get_conf('frameworkd_port');
    }
}

/* End of file Frameword_socket.inc */
/* Location: ../classes/Frameword_socket.inc */