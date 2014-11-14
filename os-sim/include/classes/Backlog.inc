<?php
/**
* Backlog.inc
*
* File Backlog.inc is used to:
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
* - Backlog()
* - get_id()
* - get_directive_id()
* - get_timestamp()
* - get_matched()
* - get_count()
* - get_list()
* - delete()
*/

require_once ("ossim_db.inc");
class Backlog {
    public $id;
    public $directive_id;
    public $timestamp;
    public $matched;
    function __construct($id, $directive_id, $timestamp, $matched) {
        $this->id = $id;
        $this->directive_id = $directive_id;
        $this->timestamp = $timestamp;
        $this->matched = $matched;
    }
    function get_id() {
        return $this->id;
    }
    function get_directive_id() {
        return $this->directive_id;
    }
    function get_timestamp() {
        return $this->timestamp;
    }
    function get_matched() {
        return $this->matched;
    }
    function get_count($conn) {
        $sql = "SELECT count(*) FROM backlog";
        if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
        } else {
            return $rs->fields["count(*)"];
        }
    }
    function get_list($conn, $args = "", $inf = 0, $sup = 25) {
        $i = 0;
        $query = ossim_query("SELECT * FROM backlog $args");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                if (($i++ >= $inf) && ($inf++ <= $sup)) {
                    $list[] = new Backlog($rs->fields["id"], $rs->fields["directive_id"], $rs->fields["timestamp"], $rs->fields["matched"]);
                } elseif ($inf > $sup) {
                    break;
                }
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function delete($conn, $id) {
        if (!strcmp($id, "all")) {
            $sql = "DELETE FROM backlog";
            $params = array();
        } else {
            $sql = "DELETE FROM backlog WHERE id = ?";
            $params = array(
                $id
            );
        }
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        $infolog = array(
            $id
        );
        Log_action::log(42, $infolog);
    }
}
?>
