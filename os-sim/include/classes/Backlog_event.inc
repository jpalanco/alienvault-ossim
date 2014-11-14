<?php
/**
* Backlog_event.inc
*
* File Backlog_event.inc is used to:
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
* @package    ossim-framework\Alarms
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/

/**
* Function list:
* - Backlog_event()
* - get_backlog_id()
* - get_event_id()
* - get_time_out()
* - get_occurrence()
* - get_rule_level()
* - get_matched()
* - get_count()
* - get_list()
*/

require_once ("ossim_db.inc");

class Backlog_event {
    public $backlog_id;
    public $event_id;
    public $time_out;
    public $occurrence;
    public $rule_level;
    public $matched;
    function __construct($backlog_id, $event_id, $time_out, $occurrence, $rule_level, $matched) {
        $this->backlog_id = $backlog_id;
        $this->event_id = $event_id;
        $this->time_out = $time_out;
        $this->occurrence = $occurrence;
        $this->rule_level = $rule_level;
        $this->matched = $matched;
    }
    function get_backlog_id() {
        return $this->backlog_id;
    }
    function get_event_id() {
        return $this->event_id;
    }
    function get_time_out() {
        return $this->time_out;
    }
    function get_occurrence() {
        return $this->occurrence;
    }
    function get_rule_level() {
        return $this->rule_level;
    }
    function get_matched() {
        return $this->matched;
    }
    function get_count($conn) {
        $sql = "SELECT count(*) FROM backlog_event";
        if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
        } else {
            return $rs->fields["count(*)"];
        }
    }
    function get_list($conn, $args = "") {
        $query = ossim_query("SELECT *, inet_ntoa(src_ip), inet_ntoa(dst_ip) 
            FROM backlog_event $args");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                $list[] = new Backlog_event($rs->fields["backlog_id"], $rs->fields["event_id"], $rs->fields["time_out"], $rs->fields["occurrence"], $rs->fields["rule_level"], $rs->fields["matched"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
}
?>
