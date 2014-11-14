<?php
/**
* RRD_config.inc
*
* File RRD_config.inc is used to:
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
* @package    ossim-framework\Metrics
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


/**
* Class and Function List:
* Function list:
* - RRD_config()
* - get_profile()
* - get_rrd_attrib()
* - get_threshold()
* - get_priority()
* - get_alpha()
* - get_beta()
* - get_persistence()
* - get_enable()
* - get_description()
* - get_list()
* - get_profile_list()
* - insert()
* - update()
* - delete()
* Classes list:
* - RRD_config
*/
require_once 'ossim_db.inc';
require_once 'classes/Util.inc';

class RRD_config {
    public $profile;
    public $rrd_attrib;
    public $threshold;
    public $priority;
    public $alpha;
    public $beta;
    public $persistence;
    public $enable;
    public $description;
    function __construct($profile, $rrd_attrib, $threshold, $priority, $alpha, $beta, $persistence, $enable, $description) {
        $this->profile = $profile;
        $this->rrd_attrib = $rrd_attrib;
        $this->threshold = $threshold;
        $this->priority = $priority;
        $this->alpha = $alpha;
        $this->beta = $beta;
        $this->persistence = $persistence;
        $this->enable = $enable;
        $this->description = $description;
    }
    function get_profile() {
        return $this->profile;
    }
    function get_rrd_attrib() {
        return $this->rrd_attrib;
    }
    function get_threshold() {
        return $this->threshold;
    }
    function get_priority() {
        return $this->priority;
    }
    function get_alpha() {
        return $this->alpha;
    }
    function get_beta() {
        return $this->beta;
    }
    function get_persistence() {
        return $this->persistence;
    }
    function get_enable() {
        return $this->enable;
    }
    function get_description() {
        return $this->description;
    }
    function get_list($conn, $args = "") {
        $query = ossim_query("SELECT * FROM rrd_config $args");
        if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                $list[] = new RRD_config($rs->fields["profile"], $rs->fields["rrd_attrib"], $rs->fields["threshold"], $rs->fields["priority"], $rs->fields["alpha"], $rs->fields["beta"], $rs->fields["persistence"], $rs->fields["enable"], $rs->fields["description"]);
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function get_profile_list($conn) {
        $sql = "SELECT DISTINCT profile FROM rrd_config";
        if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                $list[] = $rs->fields["profile"];
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function insert($conn, $profile, $rrd_attrib, $threshold, $priority, $alpha, $beta, $persistence, $enable, $description = "") {
        $profile = Util::htmlentities($profile, ENT_COMPAT, "UTF-8");
        $rrd_attrib = Util::htmlentities($rrd_attrib, ENT_COMPAT, "UTF-8");
        $description = Util::htmlentities($description, ENT_COMPAT, "UTF-8");
        $enable = Util::htmlentities($enable, ENT_COMPAT, "UTF-8");
        $threshold = intval($threshold);
        $priority = intval($priority);
        $alpha = intval($alpha);
        $beta = intval($beta);
        $persistence = intval($persistence);
        $sql = "INSERT INTO rrd_config (profile, rrd_attrib, threshold, priority, alpha, beta, persistence, enable, description)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = array(
            $profile,
            $rrd_attrib,
            $threshold,
            $priority,
            $alpha,
            $beta,
            $persistence,
            $enable,
            $description
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error inserting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        Log_action::log(9, NULL);
    }
    function update($conn, $profile, $rrd_attrib, $threshold, $priority, $alpha, $beta, $persistence, $enable) {
        $sql = "UPDATE rrd_config SET
                      threshold     = ?,
                      priority      = ?,
                      alpha         = ?,
                      beta          = ?,
                      persistence   = ?,
                      enable        = ?
                  WHERE profile = ? AND rrd_attrib = ?";
        $params = array(
            $threshold,
            $priority,
            $alpha,
            $beta,
            $persistence,
            $enable,
            $profile,
            $rrd_attrib
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error updating: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
    function delete($conn, $profile) {
        $sql = "DELETE FROM rrd_config WHERE profile = ?";
        $params = array(
            $profile
        );
        if ($conn->Execute($sql, $params) === false) {
            print 'error deleting: ' . $conn->ErrorMsg() . '<BR>';
            exit;
        }
    }
}
?>
