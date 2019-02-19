<?php
/**
* RRD_anomaly_global.inc
*
* File RRD_anomaly_global.inc is used to:
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

class RRD_anomaly_global
{
    public $what;


    public $count;


    public $anomaly_time;


    public $range;


    public $over;


    public $acked;


    public function __construct($what, $count, $anomaly_time, $range, $over, $acked)
    {
        $this->what         = $what;
        $this->count        = $count;
        $this->anomaly_time = $anomaly_time;
        $this->range        = $range;
        $this->over         = $over;
        $this->acked        = $acked;
    }


    public function get_what()
    {
        return $this->what;
    }


    public function get_count()
    {
        return $this->count;
    }


    public function get_anomaly_time()
    {
        return $this->anomaly_time;
    }


    public function get_range()
    {
        return $this->range;
    }


    public function get_over()
    {
        return $this->over;
    }


    public function get_acked()
    {
        return $this->acked;
    }


    public static function get_list($conn, $where = "", $order = "", $inf = "0", $sup = "99999")
    {
        $list   = array();
        $nanoms = $sup - $inf;

        $query = ossim_query("SELECT * FROM rrd_anomalies_global $where $order LIMIT $nanoms OFFSET $inf");

        $rs = $conn->Execute($query);
        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF)
            {
                $list[] = new self($rs->fields["what"], $rs->fields["count"], $rs->fields["anomaly_time"], $rs->fields["anomaly_range"], $rs->fields["over"], $rs->fields["acked"]);

                $rs->MoveNext();
            }
        }

        return $list;
    }


    public static function ack($conn, $what)
    {
        $query  = "UPDATE rrd_anomalies_global SET acked = 1 where what = ?";
        $params = array($what);

        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $infolog = array("RRD global anomalie", $what, "");
        Log_action::log(69, $infolog);
    }


    public static function delete($conn, $what)
    {
        $query  = "DELETE FROM rrd_anomalies_global WHERE what = ?";
        $params = array($what);

        if ($conn->Execute($query, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $infolog = array("RRD global anomalie", $what, "");
        Log_action::log(70, $infolog);
    }


    public static function get_list_count($conn)
    {
        $query = "SELECT count(*) FROM rrd_anomalies_global";

        $rs = $conn->Execute($query);
        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            return $rs->fields["count(*)"];
        }
    }
}

/* End of file rrd_anomaly_global.inc */
/* Location: ../include/classes/rrd_anomaly_global.inc */
