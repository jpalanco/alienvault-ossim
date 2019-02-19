<?php
/**
* Event.inc
*
* File Event.inc is used to:
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
* @package    ossim-framework\Events
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


/**
* Function list:
* - Event()
* - get_id()
* - get_timestamp()
* - get_sensor()
* - get_interface()
* - get_type()
* - get_plugin_id()
* - get_plugin_sid()
* - get_protocol()
* - get_src_ip()
* - get_dst_ip()
* - get_src_port()
* - get_dst_port()
* - get_condition()
* - get_value()
* - get_time_interval()
* - get_absolute()
* - get_priority()
* - get_reliability()
* - get_risk_c()
* - get_risk_a()
* - get_asset_src()
* - get_asset_dst()
* - get_snort_sid()
* - get_snort_cid()
* - alarm()
* - get_count()
* - get_list()
* - delete()
*/


class Event
{
    public $id;

    public $timestamp;

    public $sensor;

    public $interface;

    public $type;

    public $plugin_id;

    public $plugin_sid;

    public $protocol;

    public $src_ip;

    public $dst_ip;

    public $src_port;

    public $dst_port;

    public $condition;

    public $value;

    public $time_interval;

    public $absolute;

    public $priority;

    public $reliability;

    public $asset_src;

    public $asset_dst;

    public $risk_c;

    public $risk_a;

    public $snort_sid;

    public $snort_cid;

    public $alarm;

    public function __construct($id, $timestamp, $sensor, $interface, $type, $plugin_id, $plugin_sid, $protocol, $src_ip, $dst_ip, $src_port, $dst_port, $condition, $value, $time_interval, $absolute, $priority, $reliability, $risk_c, $risk_a, $asset_src, $asset_dst, $snort_sid, $snort_cid, $alarm) {
        $this->id            = $id;
        $this->timestamp     = $timestamp;
        $this->sensor        = $sensor;
        $this->interface     = $interface;
        $this->type          = $type;
        $this->plugin_id     = $plugin_id;
        $this->plugin_sid    = $plugin_sid;
        $this->protocol      = $protocol;
        $this->src_ip        = $src_ip;
        $this->dst_ip        = $dst_ip;
        $this->src_port      = $src_port;
        $this->dst_port      = $dst_port;
        $this->condition     = $condition;
        $this->value         = $value;
        $this->time_interval = $time_interval;
        $this->absolute      = $absolute;
        $this->priority      = $priority;
        $this->reliability   = $reliability;
        $this->risk_c        = $risk_c;
        $this->risk_a        = $risk_a;
        $this->asset_src     = $asset_src;
        $this->asset_dst     = $asset_dst;
        $this->snort_sid     = $snort_sid;
        $this->snort_cid     = $snort_cid;
        $this->alarm         = $alarm;
    }

    public function get_id()
    {
        return $this->id;
    }


    public function get_timestamp()
    {
        return $this->timestamp;
    }


    public function get_sensor()
    {
        return $this->sensor;
    }


    public function get_interface()
    {
        return $this->interface;
    }


    public function get_type()
    {
        return $this->type;
    }


    public function get_plugin_id()
    {
        return $this->plugin_id;
    }


    public function get_plugin_sid()
    {
        return $this->plugin_sid;
    }


    public function get_protocol()
    {
        return $this->protocol;
    }


    public function get_src_ip()
    {
        return $this->src_ip;
    }


    public function get_dst_ip()
    {
        return $this->dst_ip;
    }


    public function get_src_port()
    {
        return $this->src_port;
    }


    public function get_dst_port()
    {
        return $this->dst_port;
    }

    public function get_condition()
    {
        return $this->condition;
    }


    public function get_value()
    {
        return $this->value;
    }


    public function get_time_interval()
    {
        return $this->time_interval;
    }


    public function get_absolute()
    {
        return $this->absolute;
    }

    public function get_priority()
    {
        return $this->priority;
    }


    public function get_reliability()
    {
        return $this->reliability;
    }


    public function get_risk_c()
    {
        return $this->risk_c;
    }


    public function get_risk_a()
    {
        return $this->risk_a;
    }


    public function get_asset_src()
    {
        return $this->asset_src;
    }


    public function get_asset_dst()
    {
        return $this->asset_dst;
    }


    public function get_snort_sid()
    {
        return $this->snort_sid;
    }


    public function get_snort_cid()
    {
        return $this->snort_cid;
    }


    public function alarm()
    {
        return $this->alarm;
    }


    public function get_count($conn, $where = '')
    {
        $query = ossim_query("SELECT count(*) FROM event $where");
        
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


    public static function get_list($conn, $args = "", $inf = 0, $sup = 25) 
    {
        $i = 0;

        $query = ossim_query("SELECT *, inet_ntoa(src_ip), inet_ntoa(dst_ip) FROM event $args");
        $rs    = $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            $list = array();

            while (!$rs->EOF)
            {
                if (($i++ >= $inf) && ($inf++ <= $sup))
                {
                    $list[] = new self($rs->fields["id"], $rs->fields["timestamp"], $rs->fields["sensor"], $rs->fields["interface"], $rs->fields["type"], $rs->fields["plugin_id"], $rs->fields["plugin_sid"], $rs->fields["protocol"], $rs->fields["inet_ntoa(src_ip)"], $rs->fields["inet_ntoa(dst_ip)"], $rs->fields["src_port"], $rs->fields["dst_port"], $rs->fields["condition"], $rs->fields["value"], $rs->fields["time_interval"], $rs->fields["absolute"], $rs->fields["priority"], $rs->fields["reliability"], $rs->fields["risk_c"], $rs->fields["risk_a"], $rs->fields["asset_src"], $rs->fields["asset_dst"], $rs->fields["snort_sid"], $rs->fields["snort_cid"], $rs->fields["alarm"]);
                }
                elseif ($inf > $sup)
                {
                    break;
                }

                $rs->MoveNext();
            }
        }

        return $list;
    }


    public static function delete($conn, $id)
    {
        if (!strcmp($id, "all"))
        {
            $sql    = "DELETE FROM event";
            $params = array();
        }
        else
        {
            $sql    = "DELETE FROM event WHERE id = ?";
            $params = array($id);
        }

        if ($conn->Execute($sql, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
    }
}

/* End of file event.inc */
/* Location: ../include/classes/event.inc */
