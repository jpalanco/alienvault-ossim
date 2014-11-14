<?php
/**
* nagios_adm.inc
*
* File nagios_adm.inc is used to:
*   - Admin Nagios config for assets
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
* @package    ossim-framework\Availability
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/

/**
*
* Example:
*
* $q=new NagiosAdm();
* $q->addHost(new NagiosHost("192.168.1.150","appliance"));
* $k[0]=new NagiosHost("192.168.10.1","host1");
* $k[2]=new NagiosHost("192.168.10.2","host2");
* $k[3]=new NagiosHost("192.168.10.3","host3");
* $k[4]=new NagiosHost("192.168.10.4","host4");
* $q->addHosts($k);
* $q->close();
*
*/

class Nagios_adm 
{
    public $frcon;
    
    
    public function __construct() 
    {
        $this->frcon = new Frameworkd_socket();
    }
    
        
    public function addHost($host) 
    {
        $this->frcon->write('nagios action="add" type="host" list="' . $host->toList() . "\"\n");
	}
	
	
    public function reload() 
    {        
        $this->frcon->write('nagios action="restart"');
    }
    
    
    public function delHost($host) 
    {      
        $this->frcon->write('nagios action="del" type="host" list="' . $host->toList() . "\"\n");
    }
    
    
    public function addHosts($list) 
    {     
        // Converting to string
        foreach($list as $k => $host) 
        {
            $strings[$k] = $host->toList();
        }
        $list = implode($strings, "|");
        
        $this->frcon->write('nagios action="add" type="host" list="' . $list . "\"\n");
    }
        
        
    public function close() 
    {
        $this->frcon->close();
    }
    
    
    public function addNagiosHostGroup($hg) 
    {       
        $this->frcon->write($hg->toAdd());
    }
    
    
    public function delNagiosHostGroup($hg) 
    {        
        $this->frcon->write($hg->toDel());
    }
}

/* End of file Nagios_adm.inc */
/* Location: ../classes/Nagios_adm.inc */