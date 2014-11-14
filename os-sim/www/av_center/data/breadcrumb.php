<?php
/**
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
*/

require_once 'av_init.php';

//Only admin can access
Avc_utilities::check_access();


$sections['alienvault_center'] = array('path' => 'index.php',                                
									   'name' => _('Alienvault Center'),  
									   'bc'   => 'alienvault_center');

$sections['home']              = array('path' => 'sections/home/home.php',                   
									   'name' => _('Home'),               
									   'bc'   => 'alienvault_center###home');									   

$sections['system_status']     = array('path' => 'sections/system_status/st_detail.php',     
									   'name' => _('System Status'),      
									   'bc'   => 'alienvault_center###home###system_status');

$sections['alienvault_status'] = array('path' => 'sections/alienvault_status/av_detail.php', 
									   'name' => _('Alienvault Status'),  
									   'bc'   => 'alienvault_center###home###alienvault_status');

$sections['network']           = array('path' => 'sections/network/network_detail.php',      
									   'name' => _('Network'),            
									   'bc'   => 'alienvault_center###home###network');

//Software
$sections['sw_pkg_installed']  = array('path' => 'sections/software/sw_pkg_installed.php',   
									   'name' => _('Software - Packages installed'),        
									   'bc'   => 'alienvault_center###home###sw_pkg_installed');
									   
$sections['sw_pkg_pending']    = array('path' => 'sections/software/sw_pkg_pending.php',     
									   'name' => _('Software - Packages pending updates'),  
									   'bc'   => 'alienvault_center###home###sw_pkg_pending');

$sections['sw_pkg_checking']   = array('path' => 'sections/software/sw_pkg_pending.php',     
									   'name' => _('Software - Packages pending updates'),  
									   'bc'   => 'alienvault_center###home###sw_pkg_pending');

$sections['sw_pkg_installing'] = array('path' => 'sections/software/sw_pkg_installing.php',  
                                       'name' => _('Software - Installing Packages'),       
									   'bc'   => 'alienvault_center###home###sw_pkg_installing');


//Configuration
$sections['cnf_general'] = array('path' => 'sections/configuration/general/index.php',     
								 'name' => _('Configuration - General'),       
								 'bc'   => 'alienvault_center###home###cnf_general');
								 
$sections['cnf_network'] = array('path' => 'sections/configuration/network/index.php',     
								 'name' => _('Configuration - Network'),       
								 'bc'   => 'alienvault_center###home###cnf_network');	

$sections['cnf_sensor']  = array('path' => 'sections/configuration/sensor/index.php',     
								 'name' => _('Configuration - Sensors'),       
								 'bc'   => 'alienvault_center###home###cnf_sensor');										 

//Logs
$sections['logs'] = array('path' => 'sections/logs/index.php',                             
						  'name' => _('Logs'),       
						  'bc'   => 'alienvault_center###home###logs');

?>