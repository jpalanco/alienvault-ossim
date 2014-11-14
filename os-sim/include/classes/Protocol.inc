<?php
/**
* Protocol.inc
*
* File Protocol.inc is used to:
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
* @package    ossim-framework\Various
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


/**
* Function list:
* - Protocol()
* - get_id()
* - get_name()
* - get_alias()
* - get_descr()
* - get_list()
* - insert()
* - update()
* - delete()
*/

require_once ("ossim_db.inc");

class Protocol {
    public $id;
    public $name;
    public $alias;
    public $descr;
    function __construct($id, $name, $alias, $descr) {
        $this->id = $id;
        $this->name = $name;
        $this->alias = $alias;
        $this->descr = $descr;
    }
    function get_id() {
        return $this->id;
    }
    function get_name() {
        return $this->name;
    }
    function get_alias() {
        return $this->alias;
    }
    function get_descr() {
        return $this->descr;
    }
    function get_list($conn, $where = "", $order = "") {
        $query = ossim_query("SELECT * FROM protocol $where $order");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                $list[] = new protocol($rs->fields["id"], $rs->fields["name"], $rs->fields["alias"], $rs->fields["descr"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $id, $name, $alias, $descr) {
        settype($id, "int");
        $sql = "INSERT INTO protocol VALUES (NULL, ?, ?, ?, ?)";
        $params = array(
            $id,
            $name,
            $alias,
            $descr
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function update($conn, $id, $name, $alias, $descr) {
        settype($id, "int");
        $sql = "UPDATE protocol SET id = ?, name = ?, alias = ?, descr = ? WHERE id = ?";
        $params = array(
            $id,
            $name,
            $alias,
            $descr,
            $id
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error updating: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function delete($conn, $id) {
        settype($id, "int");
        $sql = "DELETE FROM protocol WHERE id = ?";
        $params = array(
            $id
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
}
?>
