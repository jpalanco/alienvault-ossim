<?php

/**
 * plugin.inc
 *
 * File plugin.inc is used to:
 *   - Manage custom plugin using AlienVault API
 *
 *
 * License:
 *
 * Copyright (c) 2003-2006 ossim.net
 * Copyright (c) 2007-2014 AlienVault
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
 * @copyright  2007-2014 AlienVault
 * @link       https://www.alienvault.com/
 */

require_once 'provider.inc';

/**
 * The goal of this class is to add Custom Plugin providers
 *
 *
 * @package    ossim-framework\Various
 * @autor      AlienVault INC
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt
 * @copyright  2003-2006 ossim.net
 * @copyright  2007-2014 AlienVault
 * @link       https://www.alienvault.com/
 */
class Plugin_provider extends Provider
{
    /**
     * This method gets the plugin list
     *
     * @return object
     */
    public function get_plugin_list()
    {
        $response = $this->conn->do_request($this->common_url , array(), 'GET');
        return $response;
    }
    
    
    /**
     * This method downloads a plugin by name
     *
     * @param  string   $plugin_name
     *
     * @return object
     */
    public function download_plugin($plugin_name)
    {
        $params   = array('plugin_file' => $plugin_name);
        $response = $this->conn->do_request($this->common_url.'/download' , $params, 'POST');
    
        return $response;
    }
    
    
    /**
     * This method deletes plugin by name
     *
     * @param  array   $plugin_name
     *
     * @return object
     */
    public function delete_plugin($plugin_name)
    {
        $params   = array('plugin_file' => $plugin_name);
        $response = $this->conn->do_request($this->common_url.'/remove' , $params, 'DELETE');
    
        return $response;
    }
    
    
    /**
     * This method checks a plugin file
     *
     * @param  string  $file
     *
     * @return object
     */
    public function check_plugin_file($file)
    {
        $params   = array('plugin_file' => $file);
        $response = $this->conn->do_request($this->common_url.'/verify/header' , $params, 'POST');
    
        return $response;
    }
    
    
    /**
     * This method finishes the upload plugin wizard
     *
     * @param  string  $file
     * @param  string  $vendor
     * @param  string  $model
     * @param  string  $version
     * @param  boolean $overwrite
     *
     * @return object
     */
    public function save_plugin($file, $vendor, $model, $version, $product_type, $overwrite)
    {
        $params = array(
            'plugin_file' => $file,
            'vendor'      => $vendor,
            'model'       => $model,
            'version'     => $version,
            'product_type'=> $product_type,
            'overwrite'   => ($overwrite) ? 'true' : ''
        );
        
        $response = $this->conn->do_request($this->common_url.'/upload' , $params, 'POST');
    
        return $response;
    }
}
