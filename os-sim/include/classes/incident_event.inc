<?php
/**
* Incident_event.inc
*
* File Incident_event.inc is used to:
*   - Task 1 (TO DO)
*   - Task 2 (TO DO)
*   - Task 3 (TO DO)
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
* @package    ossim-framework\Incidents
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/

/**
* Function list:
* - Incident_event()
* - get_id()
* - get_incident_id()
* - get_src_ips()
* - get_dst_ips()
* - get_src_ports()
* - get_dst_ports()
* - get_list()
*/

class Incident_event 
{
    public $id;
    public $incident_id;
    public $src_ips;
    public $dst_ips;
    public $src_ports;
    public $dst_ports;
    function __construct($id, $incident_id, $src_ips, $dst_ips, $src_ports, $dst_ports) {
        $this->id = intval($id);
        $this->incident_id = intval($incident_id);
        $this->src_ips = $src_ips;
        $this->dst_ips = $dst_ips;
        $this->src_ports = $src_ports;
        $this->dst_ports = $dst_ports;
    }
    function get_id() {
        return $this->id;
    }
    function get_incident_id() {
        return $this->incident_id;
    }
    function get_src_ips() {
        return $this->src_ips;
    }
    function get_dst_ips() {
        return $this->dst_ips;
    }
    function get_src_ports() {
        return $this->src_ports;
    }
    function get_dst_ports() {
        return $this->dst_ports;
    }
    function get_list($conn, $args) {
        $list = array();
        $query = ossim_query("SELECT * FROM incident_event $args");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                $list[] = new Incident_event($rs->fields["id"], $rs->fields["incident_id"], $rs->fields["src_ips"], $rs->fields["dst_ips"], $rs->fields["src_ports"], $rs->fields["dst_ports"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
}
?>
