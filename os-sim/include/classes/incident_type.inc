<?php
/**
* incident_type.inc
*
* File incident_type.inc is used to:
*   - Manage the data in incident_type database table
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
* - get_id()
* - get_descr()
* - get_list()
* - insert()
* - update()
* - delete()
*/

class Incident_type 
{
    public $id;
    public $descr;
    function __construct($id, $descr, $keywords="") {
        $this->id = $id;
        $this->descr = $descr;
        $this->keywords = $keywords;
    }
    function get_id() {
        return $this->id;
    }
    function get_descr() {
        return $this->descr;
    }
    function get_keywords() {
        return $this->keywords;
    }
    function get_list($conn, $args = "") {
        $list = array();
        $query = ossim_query("SELECT * FROM incident_type $args");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            while (!$rs->EOF) {
                $list[] = new Incident_type($rs->fields["id"], $rs->fields["descr"], $rs->fields["keywords"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    
	function insert($conn, $id, $descr, $keywords="") {
        $sql = "INSERT into incident_type ( id, descr, keywords) VALUES   (?, ?, ?)";
        $params = array(
            $id,
            $descr,
            $keywords
        );
        if ($conn->Execute($sql, $params) == false) {
            return 'Error inserting: ' . $conn->ErrorMsg();
        }
        $infolog = array($id);
        Log_action::log(53, $infolog);
		
		return true;
    }
	
    function update($conn, $id, $descr, $keywords="") {
    	if ($keywords=="custom") {
	        $sql = "UPDATE incident_type SET descr= ?,keywords=TRIM(CONCAT(keywords,' custom')) WHERE id = ?";
	        $params = array(
	            $descr,
	            $id
	        );
	    } else {
	        $sql = "UPDATE incident_type SET descr= ?,keywords=TRIM(REPLACE(keywords,'custom','')) WHERE id = ?";
	        $params = array(
	            $descr,
	            $id
	        );
	    }
        if ($conn->Execute($sql, $params) == false) {
            print 'Error updating: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        $infolog = array($id);
        Log_action::log(52, $infolog);
    }
    function delete($conn, $id) {
        $sql = "DELETE FROM incident_type WHERE id = ?";
        $params = array(
            $id
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        $sql = "UPDATE incident SET type_id = 'Generic' WHERE type_id = ?";
        $params = array(
            $id
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        $infolog = array($id);
        Log_action::log(54, $infolog);
    }
}

/* End of file incident_type.inc */
/* Location: ../include/classes/incident_type.inc */