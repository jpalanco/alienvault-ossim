<?php

/**
 * nmap.inc
 *
 * File nmap.inc is used to:
 *   - Manage asset scan using AlienVault API
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

require_once __DIR__ . '/provider.inc';

/**
 * The goal of this class is to add "GVM" providers
 *
 *
 * @package    ossim-framework\Various
 * @autor      AlienVault INC
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt
 * @copyright  2003-2006 ossim.net
 * @copyright  2007-2014 AlienVault
 * @link       https://www.alienvault.com/
 */
class Gvm_provider extends Provider
{
    /**
     * This method execute a GVM command
     *
     * @param string   $sensor_ip     IP from the sensor where the command will be executed
     * @param string   $gvm_command   GVM to be executed
     *
     * @return object
     */
    public function execute_gvm_command($sensor_ip, $gvm_command)
    {

        $file_name = tempnam("/tmp", 'ansible_command_file_');
        chmod($file_name, "444");

        $pathinfo = pathinfo($file_name);
        file_put_contents($file_name, mb_convert_encoding($gvm_command, "UTF-8", "ISO-8859-1"));

        $params = array(
            'sensor_ip'   => $sensor_ip,
            'gvm_file' => $pathinfo["filename"]
        );

        $response = $this->conn->do_request($this->common_url . '/execute_gvm_command', $params, 'POST');

        unlink($file_name);

        return $response;
    }
}
