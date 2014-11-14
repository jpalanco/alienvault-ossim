<?php
/**
* apps.inc
*
* File apps.inc is used to:
*   - define apps providers methods
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
* The goal of this class is to define the apps/doctor provider methods
* 
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/
class Apps_doctor extends Provider
{
    /**
    * This function launches the AlienVault Support Tool
    *
    * @param string  $components    Component uuid
    * @param string  $pass          Output file prefix
    * @param string  $output_type   [Optional] Output type
    *
    * @return string in json format
    */
    public function launch_av_support_tool($components, $output_file_prefix, $output_type = "support")
    {
        $params   = array("components"         => $components,
                          "output_file_prefix" => $output_file_prefix,
                          "output_type"        => $output_type);
        
        $response = $this->conn->do_request($this->common_url . "/doctor", $params);
        
        return $response;
    }
}

/**
* The goal of this class is to add "Apps" providers
* 
*
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/
class Apps extends Provider
{
    /**
    * This function add a doctor provider.
    *
    * @return object
    */
    public function doctor()
    {
        if($this->sub_providers->exists("doctor")===FALSE)
        {
           $this->sub_providers->add($this->conn, "doctor", $this->common_url . "/apps");
        }

       return $this->sub_providers->get("doctor");
    }
}

?>