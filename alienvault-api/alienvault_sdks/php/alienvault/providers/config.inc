<?php
/**
* config.inc
*
* File config.inc is used to:
*   - define config providers methods
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

require_once 'provider.inc';

/**
* The goal of this class is to define the config/sensor provider methods
* 
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/
class Config_sensor extends Provider
{
    /**
    * This function get the sensors in the system. 
    *
    * @return string in json format
    */
    public function get_sensors()
    {
        $response = $this->conn->do_request($this->common_url . "/sensors");
        
        return $response;
    }
}

/**
* The goal of this class is to add "Config" providers
* 
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/
class Configuration extends Provider
{
    /**
    * This function add a sensor provider. 
    *
    * @return object
    */
    public function sensor()
    {
        if($this->sub_providers->exists("sensor")===FALSE)
        {
           $this->sub_providers->add($this->conn, "sensor", $this->common_url . "/config");
        }

       return $this->sub_providers->get("sensor");
    }
}

?>