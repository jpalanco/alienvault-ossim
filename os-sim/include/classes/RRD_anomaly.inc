<?php
/**
* RRD_anomaly.inc
*
* File RRD_anomaly.inc is used to:
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
* Function list:
* - RRD_anomaly()
* - get_ip()
* - get_what()
* - get_count()
* - get_anomaly_time()
* - get_range()
* - get_over()
* - get_acked()
* - get_list()
* - ack()
* - delete()
* - get_list_count()
*/

require_once ("ossim_db.inc");

class RRD_anomaly {
    public $ip;
    public $what;
    public $count;
    public $anomaly_time;
    public $range;
    public $over;
    public $acked;
    function __construct($ip, $what, $count, $anomaly_time, $range, $over, $acked) {
        $this->ip = $ip;
        $this->what = $what;
        $this->count = $count;
        $this->anomaly_time = $anomaly_time;
        $this->range = $range;
        $this->over = $over;
        $this->acked = $acked;
    }
    function get_ip() {
        return $this->ip;
    }
    function get_what() {
        return $this->what;
    }
    function get_count() {
        return $this->count;
    }
    function get_anomaly_time() {
        return $this->anomaly_time;
    }
    function get_range() {
        return $this->range;
    }
    function get_over() {
        return $this->over;
    }
    function get_acked() {
        return $this->acked;
    }
    function get_list($conn, $where = "", $order = "", $inf = "0", $sup = "99999") {
        
        $nanoms = $sup - $inf;
        //echo "++++++";
        //echo "SELECT * FROM rrd_anomalies $where $order LIMIT $nanoms OFFSET $inf";
        //echo "++++++";
        $query = ossim_query("SELECT * FROM rrd_anomalies $where $order LIMIT $nanoms OFFSET $inf");
		if (!$rs = & $conn->Execute($query)) {
            print $conn->ErrorMsg();
        } else {
            $list = array();
            while (!$rs->EOF) {
                if (Session::hostAllowed($conn, $rs->fields["ip"])) {
                    $list[] = new RRD_anomaly($rs->fields["ip"], $rs->fields["what"], $rs->fields["count"], $rs->fields["anomaly_time"], $rs->fields["anomaly_range"], $rs->fields["over"], $rs->fields["acked"]);
                }
                $rs->MoveNext();
            }
        }
        return $list;
    }
    function ack($conn, $ip, $what) {
        $sql = "UPDATE rrd_anomalies SET acked = 1 where ip = ? and what = ?";
        $params = array(
            $ip,
            $what
        );
        if ($conn->Execute($sql, $params) === false) {
            print _("error acking").": " . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        $infolog = array("RRD anomaly", $what, ", ip: $ip");
        Log_action::log(69, $infolog);
    }
    function delete($conn, $ip, $what) {
        $sql = "DELETE FROM rrd_anomalies WHERE ip = ? and what = ?";
        $params = array(
            $ip,
            $what
        );
        if ($conn->Execute($sql, $params) === false) {
            print _("error deleting").": " . $conn->ErrorMsg() . '<BR>';
            exit;
        }
        $infolog = array("RRD anomaly", $what, ", ip: $ip");
        Log_action::log(70, $infolog);
    }
    function get_list_count($conn) {
        $sql = "SELECT count(*) as num FROM rrd_anomalies";
        if (!$rs = & $conn->Execute($sql)) {
            print $conn->ErrorMsg();
        } else {
            return $rs->fields['num'];
        }
    }
}
?>
