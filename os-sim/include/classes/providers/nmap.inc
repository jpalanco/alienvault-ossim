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

require_once 'provider.inc';

/**
 * The goal of this class is to add "NMAP" providers
 *
 *
 * @package    ossim-framework\Various
 * @autor      AlienVault INC
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt
 * @copyright  2003-2006 ossim.net
 * @copyright  2007-2014 AlienVault
 * @link       https://www.alienvault.com/
 */
class Nmap_provider extends Provider
{
    /**
     * This method creates a new NMAP scan
     *
     * @param string   $sensor_id     Sensor UUID
     * @param array    $targets       Target IP/CIDR list
     * @param array    Scan options   (Scan type, Timing template, ...)
     *
     * @return object
     */
    public function new_scan($sensor_id, $targets, $scan_options)
    {
        /*
            Scan Options:
              - scan_type: ping, normal, fast, ...
              - scan_timing: T1-T5
              - autodetect_os: true | false
              - reverse_dns: true | false
              - ports: 1-65535
              - idm:  true | false
        */

        $params = array(
            'sensor_id'   => Util::uuid_format($sensor_id),
            'target'      => $targets,
            'scan_type'   => $scan_options['scan_type'],
            'scan_ports'  => $scan_options['scan_ports'],
            'scan_timing' => $scan_options['scan_timing'],
            'rdns'        => $scan_options['reverse_dns'],
            'autodetect'  => $scan_options['autodetect_os'],
            'idm'         => $scan_options['idm'],
            'excludes'    => $scan_options['excludes']
        );
        $response = $this->conn->do_request($this->common_url . '/', $params, 'POST');

        return $response;
    }


    /**
     * This method gets archive NMAP scans
     *
     * @return object
     */
    public function get_list()
    {
        $response = $this->conn->do_request($this->common_url . '/', array(), 'GET');

        return $response;

        /*return '{
          "data": {
            "nmap_scan_list": [
              {
                "job_id": "3028639f-7906-4746-99b7-d19cf276dc74",
                "idm": false,
                "scan_params ": {
                  "rdns": false,
                  "scan_ports": null,
                  "scan_timing": "T3",
                  "scan_type": "ping",
                  "target": "192.168.7.0/27"
                },
                "sensor_id": "4a2fc21d-ddfb-11e4-afb0-000c29467c0c"
              },
              {
                "job_id": "6d9a4d83-9e8b-4e90-bad1-5ebe7d42cf35",
                "idm": false,
                "scan_params ": {
                  "rdns": false,
                  "scan_ports": null,
                  "scan_timing": "T3",
                  "scan_type": "ping",
                  "target": "192.168.7.0/27"
                },
                "sensor_id": "4a2fc21d-ddfb-11e4-afb0-000c29467c0c"
              }
            ]
          },
        "status": "success"
        }';
        */
    }


    /**
     * This method retrieves result from a NMAP scan
     *
     * @param string   $sensor_id   Sensor UUID
     * @param string   $task_id     Task UUID (Canonical)
     *
     * @return object
     */
    public function get_scan($sensor_id, $task_id)
    {
        $params = array(
            'sensor_id' => Util::uuid_format($sensor_id)
        );

        $response = $this->conn->do_request($this->common_url . '/' . Util::uuid_format($task_id), $params, 'GET');

        return $response;
    }


    /**
     * This method retrieves information about a NMAP scan progress
     *
     * @param string   $task_id     Task UUID (Canonical)
     *
     * @return object
     */
    public function get_scan_status($task_id)
    {
        $response = $this->conn->do_request($this->common_url . '/' . Util::uuid_format($task_id) . '/status', array(), 'GET');

        return $response;
    }


    /**
     * This method deletes a NMAP scan
     *
     * @param string   $sensor_id   Sensor UUID
     * @param string   $task_id     Task UUID (Canonical)
     *
     * @return object
     */
    public function delete_scan($sensor_id, $task_id)
    {
        $params = array(
            'sensor_id' => Util::uuid_format($sensor_id)
        );

        $response = $this->conn->do_request($this->common_url . '/' . Util::uuid_format($task_id), $params, 'DELETE');

        return $response;
    }


    /**
     * This method stops a NMAP scan
     *
     * @param string   $task_id     Task UUID (Canonical)
     *
     * @return object
     */
    public function stop_scan($task_id)
    {
        $params = array();

        $response = $this->conn->do_request($this->common_url . '/' . Util::uuid_format($task_id) . '/stop', $params, 'GET');

        return $response;
    }
}
