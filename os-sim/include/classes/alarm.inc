<?php
/**
* alarm.inc
*
* File Alarm.inc is used to:
*   - Alarm management
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
* Alarm Class
*
* Class for Alarm management
*
* @package    ossim-framework\Alarms
* @autor      AlienVault INC
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/ Alienvault web page
*/

class Alarm {

    /**
    * Event ID (Hex format)
    * @var string
    *
    */
    private $event_id;

    /**
    * Backlog ID (Hex format)
    * @var string
    *
    */
    private $backlog_id;

    /**
    * Alarm context
    * @var string
    *
    */
    private $ctx;

    /**
    * Alarm date YYYY-MM-DD HH:MM:SS
    * @var string
    *
    */
    private $timestamp;

    /**
    * Alarm Status (Open, Closed)
    * @var string
    *
    */
    private $status;

    /**
    * First alarm event date YYYY-MM-DD HH:MM:SS
    * @var string
    *
    */
    private $since;

    /**
    * Last alarm event date YYYY-MM-DD HH:MM:SS
    * @var string
    *
    */
    private $last;

    /**
    * Plugin ID
    * @var integer
    *
    */
    private $plugin_id;

    /**
    * Plugin SID
    * @var integer
    *
    */
    private $plugin_sid;

    /**
    * Protocol ID
    * @var integer
    *
    */
    private $protocol;

    /**
    * Source IP XXX.XXX.XXX.XXX
    * @var string
    *
    */
    private $src_ip;

    /**
    * Destination IP XXX.XXX.XXX.XXX
    * @var string
    *
    */
    private $dst_ip;

    /**
    * Source Port
    * @var integer
    *
    */
    private $src_port;

    /**
    * Destination Port
    * @var integer
    *
    */
    private $dst_port;

    /**
    * Alarm Risk
    * @var integer
    *
    */
    private $risk;

    /**
    * Alarm related sensors (Hex format)
    * @var array
    *
    */
    private $sensors;

    /**
    * Alarm rule level
    * @var integer
    *
    */
    private $rule_level;

    /**
    * Event is an alarm
    * @var integer
    *
    */
    private $alarm;

    /**
    * Alarm signature name
    * @var string
    *
    */
    private $sid_name;

    /**
    * Alarm priority value
    * @var integer
    *
    */
    private $sid_priority;

    /**
    * Alarm tag list (Hex format)
    * @var array
    *
    */
    private $tags;

    /**
    * Alarm taxonomy (intent, strategy and method)
    * @var array
    *
    */
    private $taxonomy;

    /**
    * Alarm related event_id cache details
    * @var array
    *
    */
    private $event_data;

    /**
    * Alarm timezone correction
    * @var float
    *
    */
    private $tzone;

    /**
    * Alarm grouping value
    * @var string
    *
    */
    private $similar;

    /**
    * Alarm grouping counter value
    * @var integer
    *
    */
    private $csimilar;

    /**
    * Alarm related event_id record
    * @var array
    *
    */
    private $event_info;

    /**
    * Alarm correlation finished flag
    * @var integer
    *
    */
    private $removable;

    /**
    * Class constructor
    *
    * This public function sets up the class
    *
    * @param object  $conn   Database access object
    * @param boolean $cache  Use cache queries true/false
    * @param array   $data   Parameters to set private variables
    */
    public function __construct($conn, $cache, $data)
    {
    	// OLD PARAMETERS
    	// $event_id, $backlog_id, $ctx, $timestamp, $status, $since, $last, $plugin_id, $plugin_sid, $protocol, $src_ip, $dst_ip, $src_port, $dst_port, $risk, $sensors, $sid_name, $sid_priority, $rule_level = "", $alarm = 1, $tags=array(), $tzone=0, $similar="", $csimilar=0, $event_info = array(), $removable=0, $taxonomy=array()
        $this->event_id     = $data['event_id'];
        $this->backlog_id   = $data['backlog_id'];
        $this->ctx          = $data['ctx'];
        $this->timestamp    = $data['timestamp'];
        $this->status       = $data['status'];
        $this->plugin_id    = intval($data['plugin_id']);
        $this->plugin_sid   = intval($data['plugin_sid']);
        $this->protocol     = $data['protocol'];
        $this->src_ip       = $data['src_ip'];
        $this->dst_ip       = $data['dst_ip'];
        $this->src_port     = intval($data['src_port']);
        $this->dst_port     = intval($data['dst_port']);
        $this->risk         = intval($data['risk']);
        $this->rule_level   = intval($data['rule_level']);
        $this->alarm        = empty($data['alarm']) ? 1 : $data['alarm'];
        $this->tzone        = floatval($data['tzone']);
        $this->event_data   = array();
        $this->similar      = $data['similar'];
        $this->csimilar     = intval($data['csimilar']);
        $this->removable    = intval($data['removable']);
        $this->taxonomy     = empty($data['taxonomy']) ? array() : $data['taxonomy'];

        // Private calls
        list ($this->sid_name, $this->sid_priority) = $this->__get_sidname($conn, $this->plugin_id, $this->plugin_sid, $cache);
        $this->taxonomy["name"]                     = $this->sid_name;
        $this->tags                                 = Tags::get_tags_from_alarm($conn, $this->backlog_id);
        $this->event_info                           = (!empty($data['event_info']) && is_array($data['event_info'])) ? $data['event_info'] : $this->get_event($conn, $this->event_id);
        if (!empty($data['sensors']) && is_array($data['sensors']))
        {
            $this->sensors                          = $data['sensors'];
        }
        else
        {
            $this->sensors                          = (Session::is_pro()) ? $this->__get_ctxs($conn, $this->backlog_id, $cache) : $this->__get_sensors($conn, $this->event_id, $this->backlog_id, $cache);
            if (empty($this->sensors) && !empty($this->ctx))
            {
                $this->sensors[]                    = $this->ctx;
            }
        }
        if (!empty($data['since']) && !empty($data['last']))
        {
            $this->since                            = $data['since'];
            $this->last                             = $data['last'];
        }
        else
        {
            list ($this->since,$this->last)         = $this->__get_since($conn, $this->event_id, $this->backlog_id, $cache);
        }
    }


    /**
    * This function returns the event uuid
    *
    * @return string (Hex format)
    */
    public function get_event_id()
    {
        return $this->event_id;
    }


    /**
    * This function returns Context
    *
    * @return string (Hex format)
    */
    public function get_ctx()
    {
        return $this->ctx;
    }


    /**
    * This function returns similar
    *
    * @return string
    */
    public function get_similar()
    {
        return $this->similar;
    }


    /**
    * This function returns similar counter
    *
    * @return integer
    */
    public function get_csimilar()
    {
        return $this->csimilar;
    }


    /**
    * This function returns backlog uuid
    *
    * @return string (Hex format)
    */
    public function get_backlog_id()
    {
        return $this->backlog_id;
    }


    /**
    * This function returns timestamp
    *
    * @return string (Date format)
    */
    public function get_timestamp()
    {
        return $this->timestamp;
    }


    /**
    * This function returns status
    *
    * @return string
    */
    public function get_status()
    {
        return $this->status;
    }

    /**
    * This function returns first event timestamp
    *
    * @return string (Date format)
    */
    public function get_since()
    {
        return $this->since;
    }


    /**
    * This function returns last event timestamp
    *
    * @return string (Date format)
    */
    public function get_last()
    {
        return $this->last;
    }


    /**
    * This function returns plugin ID
    *
    * @return integer
    */
    public function get_plugin_id()
    {
        return $this->plugin_id;
    }


    /**
    * This function returns plugin ID
    *
    * @return integer
    */
    public function get_plugin_sid()
    {
        return $this->plugin_sid;
    }


    /**
    * This function returns plugin SID
    *
    * @return integer
    */
    public function get_sid_name()
    {
        return $this->sid_name;
    }


    /**
    * This function returns plugin SID priority
    *
    * @return integer
    */
    public function get_sid_priority()
    {
        return $this->sid_priority;
    }


    /**
    * This function returns protocol value
    *
    * @return integer
    */
    public function get_protocol()
    {
        return $this->protocol;
    }


    /**
    * This function returns source IP
    *
    * @return string (IP Format XXX.XXX.XXX.XXX)
    */
    public function get_src_ip()
    {
        return $this->src_ip;
    }


    /**
    * This function returns destination IP
    *
    * @return string (IP Format XXX.XXX.XXX.XXX)
    */
    public function get_dst_ip()
    {
        return $this->dst_ip;
    }


    /**
    * This function returns source port
    *
    * @return integer
    */
    public function get_src_port()
    {
        return $this->src_port;
    }


    /**
    * This function returns destination port
    *
    * @return integer
    */
    public function get_dst_port()
    {
        return $this->dst_port;
    }


    /**
    * This function returns risk
    *
    * @return integer
    */
    public function get_risk()
    {
        return $this->risk;
    }


    /**
    * This function returns source asset value from event data
    *
    * @return integer
    */
    public function get_asset_src()
    {
        return $this->event_info["asset_src"];
    }


    /**
    * This function returns destination asset value from event data
    *
    * @return integer
    */
    public function get_asset_dst()
    {
        return $this->event_info["asset_dst"];
    }


    /**
    * This function returns rule level
    *
    * @return integer
    */
    public function get_rule_level()
    {
        return $this->rule_level;
    }


    /**
    * This function returns sensor/context uuid array
    *
    * @return array (Hex values)
    */
    public function get_sensors()
    {
        return $this->sensors;
    }


    /**
    * This function returns alarm value
    *
    * @return integer
    */
    public function get_alarm()
    {
        return $this->alarm;
    }


    /**
    * This function returns timezone
    *
    * @return float
    */
    public function get_tzone()
    {
        return $this->tzone;
    }


    /**
    * This function returns assigned tags
    *
    * @return array
    */
    public function get_tags()
    {
        return $this->tags;
    }


    /**
    * This function returns removable value
    *
    * @return integer
    */
    public function get_removable()
    {
        return $this->removable;
    }


    /**
    * This function returns related event data
    *
    * @return array
    */
    public function get_event_info()
    {
        return $this->event_info;
    }


    /**
    * This function returns source host uuid from event data
    *
    * @return string (Hex value)
    */
    public function get_src_host()
    {
        return $this->event_info["src_host"];
    }


    /**
    * This function returns destination host uuid from event data
    *
    * @return string (Hex value)
    */
    public function get_dst_host()
    {
        return $this->event_info["dst_host"];
    }


    /**
    * This function returns source network uuid from event data
    *
    * @return string (Hex value)
    */
    public function get_src_net()
    {
        return $this->event_info["src_net"];
    }


    /**
    * This function returns destination network uuid from event data
    *
    * @return string (Hex value)
    */
    public function get_dst_net()
    {
        return $this->event_info["dst_net"];
    }


    /**
    * This function returns taxonomy data
    *
    * @return array
    */
    public function get_taxonomy()
    {
        return $this->taxonomy;
    }


    /**
    * This function returns how many alarms are in the system
    *
    * @use-by get_unresolved_alarms & mobile_option
    *
    * @param object  $conn         Database access object
    * @param string  $src_ip       [Optional] Source IP
    * @param string  $dst_ip       [Optional] Destination IP
    * @param integer $hide_closed  [Optional] Include hidden alarms
    * @param boolean $cache        [Optional] Use cache queries true/false
    *
    * @throws Exception  If a connection error occurred
    *
    * @return integer
    */
    public function get_count($conn, $src_ip = "", $dst_ip = "", $hide_closed = 1,  $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $where = array();

        // Asset filter permissions
        $ctx_join = "";
        $hosts = Session::get_host_where();
        $nets  = Session::get_net_where();
        if ($hosts != "" && !Session::only_ff_host())
        {
            $w = "";
            if ($nets != "" && !Session::only_ff_net())
            {
                $w        .= " (ah.id_host in ($hosts) OR an.id_net in ($nets))";
                $ctx_join .= " LEFT JOIN alarm_hosts ah ON a.backlog_id=ah.id_alarm LEFT JOIN alarm_nets an ON a.backlog_id=an.id_alarm";
            }
            else
            {
                $w        .= " ah.id_host in ($hosts)";
                $ctx_join .= " LEFT JOIN alarm_hosts ah ON a.backlog_id=ah.id_alarm";
            }
            $where[] = $w;
        }
        elseif ($nets != "" && !Session::only_ff_net())
        {
            $where[]   = " an.id_net in ($nets)";
            $ctx_join .= " LEFT JOIN alarm_nets an ON a.backlog_id=an.id_alarm";
        }
        // CTX's filter
        $ctxs = Session::get_ctx_where();
        if ($ctxs != "")
        {
            $where[]   = " a.backlog_id=ac.id_alarm AND ac.id_ctx in ($ctxs)";
            $ctx_join .= " ,alarm_ctxs ac";
        }

        $sql_where = count($where) ? "WHERE " . implode(" AND ", $where) : "WHERE 1=1";

        if ( $src_ip && $dst_ip )
        {
            $sql_where .= " AND (a.src_ip = inet_aton('$src_ip') OR a.dst_ip = inet_aton('$dst_ip'))";
        }
        elseif ( $src_ip )
        {
            $sql_where .= " AND a.src_ip = inet_aton('$src_ip') ";
        }
        elseif ( $dst_ip )
        {
            $sql_where .= " AND a.dst_ip = inet_aton('$dst_ip') ";
        }

        if ( $hide_closed == 1) {
            $sql_where .= " AND a.status = 'open' ";
        }

        $sql_where .= " GROUP BY a.similar";

        $query = ossim_query("SELECT count(a.similar) AS unresolved_alarms FROM alarm a $ctx_join $sql_where");
        //echo $query;

        $count = 0;
        $rs = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);
        if ( !$rs )
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            $count = $rs->RecordCount();
        }

        return $count;
    }


    /**
    * This function returns plugin SID name
    *
    * @param object  $conn   Database access object
    * @param integer $pid    Plugin ID
    * @param integer $psid   Plugin SID
    * @param boolean $cache  [Optional] Use cache queries true/false
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    private function __get_sidname($conn, $pid, $psid, $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $sql = "SELECT REPLACE(name, 'directive_event: ', '') as sid_name,priority FROM plugin_sid WHERE plugin_id=? AND sid=?";
        $params = array
        (
            $pid, $psid
        );
        $rs = ($cache) ? $conn->CacheExecute($sql, $params) : $conn->Execute($sql, $params);
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        if (!$rs->EOF)
        {
            return array($rs->fields["sid_name"], $rs->fields["priority"]);
        }

        return array("Unknown directive","");
    }


    /**
    * This function returns an array of alarm related sensor uuids
    *
    * @param object  $conn        Database access object
    * @param string  $event_id    Event uuid
    * @param string  $backlog_id  Backlog uuid
    * @param boolean $cache       [Optional] Use cache queries true/false
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    private function __get_sensors($conn, $event_id, $backlog_id, $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $sensor = array();

        $sql = "SELECT distinct hex(event.sensor_id) as sensor FROM event, backlog_event
                WHERE backlog_event.backlog_id = unhex(?) AND backlog_event.event_id = event.id AND sensor_id IS NOT NULL"; // AND event.id = unhex(?)
        $params = array(
            $backlog_id //, $event_id
        );
        $rs = ($cache) ? $conn->CacheExecute($sql, $params) : $conn->Execute($sql, $params);
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        while (!$rs->EOF)
        {
            $sensor[] = $rs->fields["sensor"];
            $rs->MoveNext();
        }
        if (!count($sensor))
        {
            $sql = "SELECT hex(sensor_id) as sensor FROM event WHERE id = unhex(?) AND sensor_id IS NOT NULL";
            $params = array(
                $event_id
            );
            $rs = ($cache) ? $conn->CacheExecute($sql, $params) : $conn->Execute($sql, $params);
            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
            elseif (!$rs->EOF)
            {
                $sensor[] = $rs->fields["sensor"];
            }
        }

        return $sensor;
    }


    /**
    * This function returns an array of alarm related context uuids
    *
    * @param object  $conn        Database access object
    * @param string  $backlog_id  Backlog uuid
    * @param boolean $cache       [Optional] Use cache queries true/false
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    private function __get_ctxs($conn, $backlog_id, $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $ctxs = array();

        $sql = "SELECT hex(id_ctx) FROM alarm_ctxs WHERE id_alarm = unhex(?)";
        $params = array(
            $backlog_id
        );
        $rs = ($cache) ? $conn->CacheExecute($sql, $params) : $conn->Execute($sql, $params);
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        while (!$rs->EOF)
        {
            $ctxs[] = $rs->fields[0];
            $rs->MoveNext();
        }

        return $ctxs;
    }


    /**
    * This function returns an array with first and last related alarm event timestamps
    *
    * @param object  $conn        Database access object
    * @param string  $event_id    Event uuid
    * @param string  $backlog_id  Backlog uuid
    * @param boolean $cache       [Optional] Use cache queries true/false
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    private function __get_since($conn, $event_id, $backlog_id, $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $since = "-";
        $last  = "-";
        /*$sql = "SELECT min(event.timestamp) as since FROM event ,backlog_event
                WHERE backlog_event.backlog_id = unhex(?) AND backlog_event.event_id = event.id
                GROUP BY backlog_event.backlog_id"; */
        $sql = "SELECT b.timestamp as first, IFNULL(b.last, a.timestamp) as last from backlog b, alarm a WHERE a.backlog_id = b.id AND a.backlog_id = unhex(?)";

        $params = array(
            $backlog_id
        );

        $rs = ($cache) ? $conn->CacheExecute($sql, $params) : $conn->Execute($sql, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        elseif (!$rs->EOF)
        {
            $since = $rs->fields["first"];
            $last  = $rs->fields["last"];
        }
        else
        {
            $sql = "SELECT timestamp FROM event WHERE id = unhex(?)";
            $params = array(
                $event_id
            );
            $rs = ($cache) ? $conn->CacheExecute($sql, $params) : $conn->Execute($sql, $params);
            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
            elseif (!$rs->EOF)
            {
                $since = $last = $rs->fields["timestamp"];
            }
        }
        return array($since,$last);
    }

    /**
    * This function returns an array with protocol/port and service assignment
    * @return array
    */
    private function __get_ports_cache($conn)
    {
        if ($_SESSION["ports_cache"]=="")
        {
            $ports = array();
            if (!$rsp = & $conn->Execute("SELECT * FROM port"))
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
            while (!$rsp->EOF)
            {
                $ports[$rsp->fields["protocol_name"]][$rsp->fields["port_number"]] = $rsp->fields["service"];
                $rsp->MoveNext();
            }
            $_SESSION["ports_cache"] = $ports;
        }
        else
        {
            $ports = $_SESSION["ports_cache"];
        }
        
        return $ports;
    }

    /**
    * This function returns true if current asset type has alarms
    *
    * @param object   $conn     Database access object
    * @param string   $type     Asset type (host, network, group)
    * @param string   $id       Host uuid (Hex value)
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    public function has_alarms($conn, $type, $id)
    {
        Ossim_db::check_connection($conn);
        
        if ($type == "host")
        {
            $sql = "SELECT EXISTS(SELECT ah.id_host FROM alarm_hosts ah, alarm a WHERE a.backlog_id=ah.id_alarm AND ah.id_host = UNHEX(?)) as bool";
        }
        elseif ($type == "net")
        {
            $sql = "SELECT EXISTS(SELECT ah.id_host FROM alarm_hosts ah, alarm a, host_net_reference r WHERE a.backlog_id=ah.id_alarm AND ah.id_host = r.host_id AND r.net_id = UNHEX(?)) as bool";
        }
        elseif ($type == "group")
        {
            // Using perms
            $perms_where = Asset_host::get_perms_where("h.",TRUE);

            $sql = "SELECT EXISTS
                    (
                        SELECT ah.id_host 
                        FROM alarm_hosts ah, host_group_reference r, host h, alarm a 
                        WHERE a.backlog_id=ah.id_alarm AND ah.id_host = r.host_id 
                            AND h.id = r.host_id AND r.host_group_id = UNHEX(?) $perms_where
                    ) as bool";     
        }
        
        $rs = $conn->Execute($sql, array($id));
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        // Total rows
        return ($rs->fields["bool"]) ? true : false;
    }

    /**
    * This function returns join and where with a user perms
    *
    * @param string $ctx   [Optional] ctx to filter
    * @param string $host  [Optional] host id to filter
    * @param string $net   [Optional] net id to filter
    *
    * @return array
    */
    public static function make_perms_where($ctx = "", $host = "", $net = "")
    {
        $where    = "";
        $ctx_join = "";
        // Asset filter
        $host_arr = ($host != "") ? array($host) : array();
        $net_arr  = ($net  != "") ? array($net)  : array();
        $hosts    = Session::get_host_where_intersect($host_arr);
        $nets     = Session::get_net_where_intersect($net_arr); // include near nets
        if ($hosts != "" && !Session::only_ff_host())
        {
            if ($nets != "" && !Session::only_ff_net())
            {
                $where    .= " (ah.id_host in ($hosts) OR an.id_net in ($nets))";
                $ctx_join .= " LEFT JOIN alarm_hosts ah ON a.backlog_id=ah.id_alarm LEFT JOIN alarm_nets an ON a.backlog_id=an.id_alarm";
            }
            else
            {
                $where    .= " ah.id_host in ($hosts)";
                $ctx_join .= " LEFT JOIN alarm_hosts ah ON a.backlog_id=ah.id_alarm";
            }
        }
        elseif ($nets != "" && !Session::only_ff_net())
        {
            $where    .= " an.id_net in ($nets)";
            $ctx_join .= " LEFT JOIN alarm_nets an ON a.backlog_id=an.id_alarm";
        }
        // CTX's filter
        $ctxs = Session::get_ctx_where();
        if ($ctxs != "")
        {
            $where    .= ($where=='' ? '' : ' AND ') . "a.backlog_id=ac.id_alarm AND ac.id_ctx in ($ctxs)";
            $ctx_join .= " ,alarm_ctxs ac";
        }
        if ($ctx != "")
        {
            $where    .= ($where=='' ? '' : ' AND ') . "(a.backlog_id=acc.id_alarm AND acc.id_ctx = UNHEX('$ctx') OR a.corr_engine_ctx = UNHEX('$ctx'))";
            $ctx_join .= " ,alarm_ctxs acc";
        }        
        
        return array($ctx_join, $where);
    }        
    
    /**
    * This function returns an array of alarms matches criteria
    *
    * @param object  $conn    Database access object
    * @param array   $params  Hash contains alarm filters
    * @param boolean $cache   [Optional] Use cache queries true/false
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public function get_list($conn, $params, $cache = FALSE)
    {
        // OLD PARAMETERS
        // $src_ip, $dst_ip, $hide_closed = 1, $order, $inf = NULL, $sup = NULL, $from_date = NULL, $to_date = NULL, $query = "", $directive_id = "", $intent = 0, $sensor = "", $tag = "", $num_events = "", $num_events_op = "", $recursive = 0, $plugin_id = '', $plugin_sid = '', $ctx = "", $host = "", $net = "", $cache = FALSE

        Ossim_db::check_connection($conn);

        $src_ip             = $params['src_ip'];
        $dst_ip             = $params['dst_ip'];
        $hide_closed        = isset($params['hide_closed']) ? intval($params['hide_closed']) : 1;
        $order              = $params['order'];
        $inf                = isset($params['inf']) ? $params['inf'] : NULL;
        $sup                = isset($params['sup']) ? $params['sup'] : NULL;
        $from_date          = isset($params['date_from']) ? $params['date_from'] : NULL;
        $to_date            = isset($params['date_to']) ? $params['date_to'] : NULL;
        $query              = $params['query'];
        $directive_id       = $params['directive_id'];
        $intent             = intval($params['intent']);
        $sensor             = $params['sensor'];
        $tag                = $params['tag'];
        $num_events         = $params['num_events'];
        $num_events_op      = $params['num_events_op'];
        $plugin_id          = $params['plugin_id'];
        $plugin_sid         = $params['plugin_sid'];
        $ctx                = $params['ctx'];
        $host               = $params['host'];
        $net                = $params['net'];
        $host_group         = $params['host_group'];

        $_SESSION["_alarm_perms_where"]  = "";
        $_SESSION["_alarm_perms_from"]   = "";
        $_SESSION["_alarm_sensor_where"] = "";
        $_SESSION["_alarm_from_date"]    = $from_date;
        $_SESSION["_alarm_to_date"]      = $to_date;

        $list       = array();
        $where      = array();
        $event_join = "";
        $ports      = self::__get_ports_cache($conn);

        //
        // CRITERIA PARSER
        //
        /* hide closed */
        if ($hide_closed == 1)
        {
            $where[] = "a.status = 'open' ";
        }
        elseif ($hide_closed == -1)
        {
            $where[] = "a.status = 'closed' ";
        }
        /* date range filter */
        if ($from_date && $to_date)
        {
            $tzc = Util::get_tzc();

            if (preg_match('/\s\d+:/', $from_date) && preg_match('/\s\d+:/', $to_date)) // With hour is UTC
            {
                $where[] = " (convert_tz(a.timestamp,'+00:00','$tzc') BETWEEN '$from_date' AND '$to_date')";
            }
            else
            {
                list($y, $m, $d) = preg_split('#-#', $from_date);
                if (!checkdate($m, $d, $y))
                {
                    print _("Invalid date") . ": $from_date";
                    return array();
                }
                list($y, $m, $d) = preg_split('#-#', $to_date);
                if (!checkdate($m, $d, $y))
                {
                    print _("Invalid date") . ": $to_date";
                    return array();
                }
                if ($from_date == $to_date)
                {
                    $where[] = " (convert_tz(a.timestamp,'+00:00','$tzc') BETWEEN '$from_date 00:00:00' AND '$to_date 23:59:59')";
                }
                else
                {
                    $where[] = " (convert_tz(a.timestamp,'+00:00','$tzc') BETWEEN '$from_date 00:00:00' AND '$to_date 23:59:59')";
                }
            }
        }

        /* ip filter */
        // Multiple IP
        if (preg_match("/,/", $src_ip) && preg_match("/,/", $dst_ip))
        {
            $where_aux = array();
            $src_list = explode(",", $src_ip);
            foreach ($src_list as $ip_aux)
            {
                $ip_aux = trim(str_replace("/\/\d+/","",$ip_aux));
                $where_aux[] = " unhex('".bin2hex(inet_pton(trim($ip_aux)))."') = e.src_ip ";
            }
            $dst_list = explode(",", $dst_ip);
            foreach ($dst_list as $ip_aux)
            {
                $ip_aux = trim(str_replace("/\/\d+/","",$ip_aux));
                $where_aux[] = " unhex('".bin2hex(inet_pton(trim($ip_aux)))."') = e.dst_ip ";
            }
            $where[] = "(".implode("OR", $where_aux).") AND be.event_id=e.id AND be.backlog_id=a.backlog_id";
            $event_join = " ,event e ,backlog_event be";
        // Single IP
        }
        else
        {
            if (preg_match('/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/', $src_ip)) $src_ip.= "/32";
            if (preg_match('/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/', $dst_ip)) $dst_ip.= "/32";

            if (isset($src_ip)) list($source_start, $source_end) = Cidr::expand_CIDR($src_ip, "SHORT", "IP");
            if (isset($dst_ip)) list($dest_start, $dest_end)     = Cidr::expand_CIDR($dst_ip, "SHORT", "IP");

            /* If both src and dst are equal, try to find all alarms where src OR dst are equal to the IP */
            if ($src_ip && $dst_ip)
            {
                $where[] = "((unhex('".bin2hex(inet_pton($source_start))."') <= e.src_ip AND unhex('".bin2hex(inet_pton($source_end))."') >= e.src_ip)
                            ".($src_ip == $dst_ip ? "OR" : "AND")."
                (unhex('".bin2hex(inet_pton($dest_start))."') <= e.dst_ip AND unhex('".bin2hex(inet_pton($dest_end))."') >= e.dst_ip))";
            }
            elseif ($src_ip)
            {
                $where[] = ($source_start == $source_end) ? " unhex('".bin2hex(inet_pton($source_start))."') = e.src_ip " : "(unhex('".bin2hex(inet_pton($source_start))."') <= e.src_ip AND unhex('".bin2hex(inet_pton($source_end))."') >= e.src_ip)";
            }
            elseif ($dst_ip)
            {
                $where[] = ($dest_start == $dest_end) ? " unhex('".bin2hex(inet_pton($dest_start))."') = e.dst_ip " : "(unhex('".bin2hex(inet_pton($dest_start))."') <= e.dst_ip AND unhex('".bin2hex(inet_pton($dest_end))."') >= e.dst_ip)";
            }
            if ($src_ip || $dst_ip)
            {
                $event_join = " ,event e ,backlog_event be";
                $where[] = "be.event_id=e.id AND be.backlog_id=a.backlog_id";
            }
        }

        list($ctx_join,$perms_where) = self::make_perms_where($ctx, $host, $net);
        if (!empty($perms_where))
        {
            $where[] = $perms_where;
        }

        // Asset group filter
        if ($host_group != "" && valid_hex32($host_group)) 
        {
            $where[] = " ah.id_host in (SELECT host_id FROM host_group_reference WHERE host_group_id=UNHEX('$host_group'))";
            if (!preg_match("/alarm_hosts/", $ctx_join))
            {
                $ctx_join = " LEFT JOIN alarm_hosts ah ON a.backlog_id=ah.id_alarm $ctx_join";
            }
        }

        /* number of events per page */
        $limit = "";
        if ($sup !== NULL && $inf !== NULL)
        {
            $nevents = $sup - $inf;
            //if ($cache) $nevents++;
            $limit = "LIMIT $nevents OFFSET $inf";
        }

        /* free text query */
        if ($query != "")
        {
            $event_join .= ", alienvault.plugin_sid p";
            $query       = escape_sql($query,$conn);
            $where[]     = ' a.plugin_id=p.plugin_id AND a.plugin_sid=p.sid AND (p.name LIKE "%'.$query.'%" or ki.name LIKE "%'.$query.'%" or ca.name LIKE "%'.$query.'%" or ta.subcategory LIKE "%'.$query.'%")';
        }

        /* directive id */
        $directive_join = "";
        if ($directive_id != "")
        {
            $where[]        = "a.backlog_id=b.id AND b.directive_id=$directive_id";
            $directive_join = ",backlog b";
        }
        if ($intent>0) {
            $where[]        = "ta.kingdom=".intval($intent);
        }

        $sql_where = count($where) ? "WHERE " . implode(" AND ", $where) : "WHERE 1=1";

        //Events contains
        if ( $plugin_id != '' && $plugin_sid != '')
        {
            $event_join  = (preg_match("/backlog_event/",$sensor_join)) ? $event_join : " ,event e ,backlog_event be";
            if($plugin_id == 1505)
            {
                $sql_where  .= " AND a.plugin_id = $plugin_id AND a.plugin_sid = $plugin_sid".(($event_join!="") ? " AND be.event_id=e.id AND be.backlog_id=a.backlog_id" : "");
            }
            else
            {
                $sql_where  .= " AND e.plugin_id = $plugin_id AND e.plugin_sid = $plugin_sid".(($event_join!="") ? " AND be.event_id=e.id AND be.backlog_id=a.backlog_id" : "");
            }
        }

        // Sensor filter
        $sensor_join = "";
        if ($sensor != "") {
            $sensor_join = (preg_match("/backlog_event/",$event_join)) ? "" : " ,event e, backlog_event be";
            $sql_where .= " AND e.sensor_id=unhex('$sensor')".(($sensor_join!="") ? " AND be.event_id=e.id AND be.backlog_id=a.backlog_id" : "");
            $_SESSION["_alarm_sensor_where"] = " AND event.sensor_id=unhex('$sensor') ";
        }

        // Tag filter
        if ($tag != "") {
            $tag_left_join = "";
            $tag_join = ",alarm_tags tg";
            $sql_where .= " AND a.backlog_id=tg.id_alarm AND tg.id_tag=$tag";
        }

        // Number of events threshold filter
        if ($num_events != "" && $num_events_op != "")
        {
            if ($num_events_op == "less") $num_events_op = "<=";
            elseif ($num_events_op == "more") $num_events_op = ">=";
            else $num_events_op = "=";
            $zero = ($num_events <= 1 || $num_events_op == "<=") ? " OR a.backlog_id not in (SELECT backlog_id FROM backlog_event)" : "";
            $sql_where .= " AND (a.backlog_id in (select backlog_id from (SELECT count(*) as total_events,backlog_event.backlog_id FROM backlog_event, event WHERE event.id = backlog_event.event_id AND event.plugin_id<>1505 GROUP BY backlog_id having total_events $num_events_op $num_events) as bk) $zero)";
        }
        // taxonomy left join
        $tax_left_join = "LEFT JOIN ( alarm_taxonomy ta LEFT JOIN alarm_kingdoms ki ON ta.kingdom=ki.id LEFT JOIN alarm_categories ca ON ta.category=ca.id ) ON a.plugin_sid=ta.sid AND a.corr_engine_ctx=ta.engine_id";

        $sql = "SELECT ".($cache ? "" : "SQL_CALC_FOUND_ROWS ")."DISTINCT a.*, a.plugin_id as plid, a.plugin_sid as plsid, count(distinct a.similar) as csimilar, ki.id as kid, ki.name as kingdom, ca.name as category, ta.subcategory FROM alarm a $tax_left_join $ctx_join $tag_left_join $event_join $sensor_join $directive_join $tag_join $sql_where GROUP BY a.similar $order $limit";
        $sql_count = "SELECT COUNT(*) FROM (SELECT DISTINCT a.* FROM alarm a $tax_left_join $ctx_join $tag_left_join $event_join $sensor_join $directive_join $tag_join $sql_where GROUP BY a.similar) as tmp";

        $_SESSION["_alarm_perms_from"]  = "FROM alarm a $tax_left_join $ctx_join $tag_left_join $event_join $sensor_join $directive_join $tag_join";
        $_SESSION["_alarm_perms_where"] = "$sql_where";

        //echo $sql; //var_export($_SESSION["_user_vision"]);

        $num_rows = 0;
        if ($cache)
        { // Cache query and do not use SQL_CALC_FOUND_ROWS
            $rs = $conn->CacheExecute($sql);
            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
            // Total rows
            if (!$inf) // First page calc count
            {
                $total = $conn->Execute($sql_count);
                if (!$total->EOF) $_SESSION["_alarm_count"] = $num_rows = $total->fields[0];
            }
            else
            {
                $num_rows = $rs->RecordCount();
            }
        }
        else
        { // Need exact alarm number, no use cache and use SQL_CALC_FOUND_ROWS
            $rs = $conn->Execute($sql);
            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
            // Total rows
            $total = $conn->Execute("SELECT FOUND_ROWS() as contador");
            if (!$total->EOF) $num_rows = $total->fields["contador"];
        }
        //
        while (!$rs->EOF)
        {

            $event_id               = strtoupper(bin2hex($rs->fields["event_id"]));
            $backlog_id             = strtoupper(bin2hex($rs->fields["backlog_id"]));
            $ctx                    = strtoupper(bin2hex($rs->fields["corr_engine_ctx"]));
            $rs->fields["src_port"] = ($ports[$rs->fields["protocol"]][$rs->fields["src_port"]] != "") ? $ports[$rs->fields["protocol"]][$rs->fields["src_port"]] : $rs->fields["src_port"];
            $rs->fields["dst_port"] = ($ports[$rs->fields["protocol"]][$rs->fields["dst_port"]] != "") ? $ports[$rs->fields["protocol"]][$rs->fields["dst_port"]] : $rs->fields["dst_port"];
            $new                    = array (
            	"event_id"     => $event_id,
                "backlog_id"   => $backlog_id,
                "ctx"          => $ctx,
                "timestamp"    => $rs->fields["timestamp"],
                "status"       => $rs->fields["status"],
                "plugin_id"    => $rs->fields["plid"],
                "plugin_sid"   => $rs->fields["plsid"],
                "protocol"     => $rs->fields["protocol"],
                "src_ip"       => @inet_ntop($rs->fields["src_ip"]),
                "dst_ip"       => @inet_ntop($rs->fields["dst_ip"]),
                "src_port"     => $rs->fields["src_port"],
                "dst_port"     => $rs->fields["dst_port"],
                "risk"         => $rs->fields["risk"],
                "rule_level"   => "",
                "alarm"        => 1,
                "tzone"        => $rs->fields["tzone"],
                "similar"      => $rs->fields["similar"],
                "csimilar"     => $rs->fields["csimilar"],
                "removable"    => $rs->fields["removable"],
                "taxonomy"     => array (
					                "id"           => $rs->fields["kid"],
					                "kingdom"      => $rs->fields["kingdom"],
					                "category"     => $rs->fields["category"],
					                "subcategory"  => $rs->fields["subcategory"]
					              )
            );

            $list[] = new Alarm($conn, $cache, $new);

            $rs->MoveNext();
        }

        return array(
            $list,
            $num_rows
        );
    }


    /**
    * This function returns the max value for a field
    *
    * @param object  $conn       Database access object
    * @param string  $field      Field value to calc max
    * @param string  $from_date  [Optional] Date from YYYY-MM-DD
    * @param string  $to_date    [Optional] Date to YYYY-MM-DD
    * @param string  $host_id    [Optional] Host uuid
    * @param string  $net_id     [Optional] Network uuid
    * @param string  $ip_cidr    [Optional] IP/CIDR XXX.XXX.XXX.XXX/XX
    * @param string  $ctx        [Optional] Context uuid
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public function get_max_byfield($conn, $field, $from_date = "", $to_date = "", $host_id = "", $net_id = "", $ip_cidr = "", $ctx = "")
    {
        Ossim_db::check_connection($conn);

        $where = array();
        /* date range filter */
        if ($from_date && $to_date)
        {
            $tzc = Util::get_tzc();
            list($y, $m, $d) = preg_split('#-#', $from_date);
            if (!checkdate($m, $d, $y))
            {
                print _("Invalid date") . ": $from_date";
                return array();
            }
            list($y, $m, $d) = preg_split('#-#', $to_date);
            if (!checkdate($m, $d, $y))
            {
                print _("Invalid date") . ": $to_date";
                return array();
            }
            if ($from_date == $to_date)
            {
                $where[] = " (convert_tz(a.timestamp,'+00:00','$tzc') BETWEEN '$from_date 00:00:00' AND '$to_date 23:59:59')";
            }
            else
            {
                $where[] = " (convert_tz(a.timestamp,'+00:00','$tzc') BETWEEN '$from_date 00:00:00' AND '$to_date 23:59:59')";
            }
        }
        // Asset filter
        $ctx_join = "";
        $host_arr = ($host_id != "") ? array($host_id) : array();
        $net_arr  = ($net_id  != "") ? array($net_id)  : array();
        $hosts    = Session::get_host_where_intersect($host_arr);
        $nets     = Session::get_net_where_intersect($net_arr); // include near nets
        if ($hosts != "" && !Session::only_ff_host())
        {
            $w = "";
            if ($nets != "" && !Session::only_ff_net())
            {
                $w        .= " (ah.id_host in ($hosts) OR an.id_net in ($nets))";
                $ctx_join .= " LEFT JOIN alarm_hosts ah ON a.backlog_id=ah.id_alarm LEFT JOIN alarm_nets an ON a.backlog_id=an.id_alarm";
            }
            else
            {
                $w        .= " ah.id_host in ($hosts)";
                $ctx_join .= " LEFT JOIN alarm_hosts ah ON a.backlog_id=ah.id_alarm";
            }
            $where[] = $w;
        }
        elseif ($nets != "" && !Session::only_ff_net())
        {
            $where[]   = " an.id_net in ($nets)";
            $ctx_join .= " LEFT JOIN alarm_nets an ON a.backlog_id=an.id_alarm";
        }
        // CTX's filter
        $ctxs = Session::get_ctx_where();
        if ($ctxs != "")
        {
            $where[]   = " a.backlog_id=ac.id_alarm AND ac.id_ctx in ($ctxs)";
            $ctx_join .= " ,alarm_ctxs ac";
        }
        if ($ctx != "")
        {
            $where[]   = " (a.backlog_id=acc.id_alarm AND acc.id_ctx = UNHEX('$ctx') OR a.corr_engine_ctx = UNHEX('$ctx'))";
            $ctx_join .= " ,alarm_ctxs acc";
        }
        // IP or CIDR filter
        if ($ip_cidr != "")
        {
            if (valid_ip_2($ip_cidr)) $ip_cidr .= "/32";
            if (isset($ip_cidr))
            {
                list($ip_start, $ip_end) = Cidr::expand_CIDR($ip_cidr, "SHORT", "IP");
                $where[] = "((unhex('".bin2hex(inet_pton($ip_start))."') <= e.src_ip AND unhex('".bin2hex(inet_pton($ip_end))."') >= e.src_ip)
                            OR
                (unhex('".bin2hex(inet_pton($ip_start))."') <= e.dst_ip AND unhex('".bin2hex(inet_pton($ip_end))."') >= e.dst_ip))";

                $event_join = " ,event e ,backlog_event be";
                $where[] = "be.event_id=e.id AND be.backlog_id=a.backlog_id";
            }
        }

        $sql_where = count($where) ? "WHERE " . implode(" AND ", $where) : "WHERE 1=1";
        $query = ossim_query("SELECT HEX(a.backlog_id) as backlog_id,a.$field FROM alarm a $ctx_join $event_join $sql_where ORDER BY a.$field DESC LIMIT 1");
        //echo $query;
        if (!$rs = & $conn->Execute($query))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            if ($rw = $rs->fetchRow())
            {
                return array(
                    $rw[$field],
                    $rw['backlog_id']
                );
            }
            else
            {
                return array();
            }
        }
    }


    /**
    * This function returns the event list of an alarm
    *
    * @param object   $conn              Database access object
    * @param string   $backlog_id        Backlog uuid
    * @param integer  $show_all          Show only directive events (0) or all events (1)
    * @param string   $event_id          Event uuid (alarm to expand)
    * @param integer  $from              from limit (0)
    * @param integer  $max               Max results (50)
    * @param integer  $alarms_numbering  Array with event list from self::get_alarms_numbering
    * @param boolean  $cache             [Optional] Use cache queries true/false
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public function get_events($conn, $backlog_id, $show_all, $event_id, $from, $max, $alarms_numbering, $cache = FALSE)
    {
        Ossim_db::check_connection($conn);
        
        $num_rows = 0;
        $query = "SELECT SQL_CALC_FOUND_ROWS hex(backlog_event.event_id) AS event_id,
                         hex(backlog_event.backlog_id) AS backlog_id,
                         hex(event.agent_ctx) AS ctx,
                         event.timestamp AS backlog_time,
                         event.timestamp AS timestamp,
                         event.plugin_id AS plugin_id,
                         event.plugin_sid AS plugin_sid,
                         event.protocol AS protocol,
                         event.src_port AS src_port,
                         event.dst_port AS dst_port,
                         event.src_ip,
                         event.dst_ip,
                         hex(event.src_host) as src_host,
                         hex(event.dst_host) as dst_host,
                         hex(event.src_net) as src_net,
                         hex(event.dst_net) as dst_net,
                         event.risk_c AS risk_c,
                         event.risk_a AS risk_a,
                         event.asset_src AS asset_src,
                         event.asset_dst AS asset_dst,
                         hex(event.sensor_id) AS sensor,
                         event.alarm AS alarm,
                         event.tzone AS tzone,
                         backlog_event.rule_level AS rule_level,
                         case when event.plugin_id = 1505 THEN 0 ELSE 1 END AS dir_event
            FROM backlog_event, event
            WHERE backlog_event.event_id = event.id AND
                backlog_event.backlog_id = unhex('$backlog_id')";
        // Get Alarm1 ...events... Alarm2 RANGE
        if ($event_id != "")
        {
            $next_a_id = 0;
            foreach ($alarms_numbering as $a_id => $pos)
            {
                if ($a_id < $event_id && !$next_a_id)
                {
                    $next_a_id = $a_id;
                }
            }
            $query .= " AND (backlog_event.event_id <= unhex('$event_id') && backlog_event.event_id > unhex('$next_a_id')) ";
        }
        
        if (!$show_all)
        {
            $query .= " HAVING dir_event = 0 "; // $query.= " AND event.alarm = 1 ";
            $query .= " ORDER BY rule_level ASC, dir_event ASC, event.timestamp DESC";
        }
        elseif ($show_all==3)
        {
            $query .= " ORDER BY rule_level ASC, event.timestamp ASC, dir_event DESC";
        }
        else
        {
            $query .= " ORDER BY rule_level DESC, dir_event ASC, event.timestamp DESC";
        }
        
        if ($show_all || $event_id != "") 
        {
            $query .= " LIMIT $from,$max";
        }
        
        $query = ossim_query($query);

        //echo Util::print_error($query);
        $rs = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);
        
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            // Total rows
            $rnd = "get_events".md5($query);
            $total = ($cache) ? $conn->CacheExecute("SELECT FOUND_ROWS() as $rnd") : $conn->Execute("SELECT FOUND_ROWS() as $rnd");
            if (!$total->EOF) foreach($total->fields as $num_rows);

            $list = array();

            while (!$rs->EOF)
            {
                $maxrisk = ($rs->fields["risk_c"] > $rs->fields["risk_a"]) ? $rs->fields["risk_c"] : $rs->fields["risk_a"];

                $new    = array (
                	"event_id"     => $rs->fields["event_id"],
                    "backlog_id"   => $rs->fields["backlog_id"],
                    "ctx"          => $rs->fields["ctx"],
                    "timestamp"    => $rs->fields["timestamp"],
                    "status"       => '',
                    "since"        => $rs->fields["backlog_time"],
                    "last"         => $rs->fields["timestamp"],
                    "plugin_id"    => $rs->fields["plugin_id"],
                    "plugin_sid"   => $rs->fields["plugin_sid"],
                    "protocol"     => $rs->fields["protocol"],
                    "src_ip"       => @inet_ntop($rs->fields["src_ip"]),
                    "dst_ip"       => @inet_ntop($rs->fields["dst_ip"]),
                    "src_port"     => $rs->fields["src_port"],
                    "dst_port"     => $rs->fields["dst_port"],
                    "risk"         => $maxrisk,
                    "rule_level"   => $rs->fields["rule_level"],
                    "alarm"        => $rs->fields["alarm"],
                    "tzone"        => $rs->fields["tzone"],
                    "sensors"      => array($rs->fields["sensor"]),
                    "event_info"   => array("src_host"=>$rs->fields["src_host"], 
                    "dst_host"     => $rs->fields["dst_host"], 
                    "src_net"      => $rs->fields["src_net"], 
                    "dst_net"      => $rs->fields["dst_net"], 
                    "asset_src"    => $rs->fields["asset_src"], 
                    "asset_dst"    => $rs->fields["asset_dst"]),
                    "similar"      => '',
                    "csimilar"     => 0,
                    "removable"    => $rs->fields["removable"],
                    "taxonomy"     => array ()
                );

                $list[] = new Alarm($conn, $cache, $new);

                $rs->MoveNext();
            }
        }
        
        return array($list, $num_rows);
    }


    /**
    * This function returns the event secuence of an alarm
    *
    * @param object   $conn         Database access object
    * @param string   $backlog_id   Backlog uuid
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public function get_alarms_numbering($conn, $backlog_id)
    {
        Ossim_db::check_connection($conn);

        $numbering = array();
        $count = 1;
        $query = "SELECT *,hex(backlog_event.event_id) as event_id FROM backlog_event, event
                WHERE backlog_event.backlog_id = unhex(?)
                AND event.id = backlog_event.event_id
                AND event.plugin_id = 1505 ORDER BY event.id DESC";

        if (!$rs = & $conn->Execute($query, array($backlog_id)))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF)
            {
                 $numbering[$rs->fields['event_id']] = $count;
                 $count++;
                 $rs->MoveNext();
            }
        }

        return $numbering;
    }


    /**
    * This function returns total number of alarm events
    *
    * @param object   $conn         Database access object
    * @param string   $backlog_id   Backlog uuid
    * @param boolean  $cache        [Optional] Use cache queries true/false
    *
    * @throws Exception  If a connection error occurred
    *
    * @return integer
    */
    public function get_total_events($conn, $backlog_id, $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $total_count = 0;
        $sql = "SELECT count(*) as total_events
                FROM backlog_event, event
                WHERE backlog_event.backlog_id = unhex(?)
                AND event.id = backlog_event.event_id AND event.plugin_id<>1505";
        $rs = ($cache) ? $conn->CacheExecute($sql, array($backlog_id)) : $conn->Execute($sql, array($backlog_id));
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        elseif (!$rs->EOF)
        {
            $total_count = $rs->fields["total_events"];
        }

        return $total_count;

    }


    /**
    * This function returns the intent array
    *
    * @param object   $conn   Database access object
    * @param boolean  $cache  [Optional] Use cache queries true/false
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public function get_intents($conn, $cache = TRUE)
    {
        Ossim_db::check_connection($conn);

        $intents = array();
        $sql = "SELECT id,name FROM alarm_kingdoms";
        $rs = ($cache) ? $conn->CacheExecute($sql) : $conn->Execute($sql);
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        elseif (!$rs->EOF)
        {
            while (!$rs->EOF)
            {
                $intents[$rs->fields["id"]] = $rs->fields["name"];
                $rs->MoveNext();
            }
        }

        return $intents;
    }


    /**
    * This function returns the strategies array
    *
    * @param object   $conn   Database access object
    * @param boolean  $cache  [Optional] Use cache queries true/false
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public function get_strategies($conn, $cache = TRUE)
    {
        Ossim_db::check_connection($conn);

        $strategies = array();
        $sql = "SELECT id,name FROM alarm_categories";
        $rs = ($cache) ? $conn->CacheExecute($sql) : $conn->Execute($sql);
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        elseif (!$rs->EOF)
        {
            while (!$rs->EOF)
            {
                $strategies[$rs->fields["id"]] = $rs->fields["name"];;
                $rs->MoveNext();
            }
        }

        return $strategies;
    }


    /**
    * This function returns alarm trend graph values
    *
    * @param object   $conn   Database access object
    * @param boolean  $cache  [Optional] Use cache queries true/false
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public function get_alarm_graph_by_taxonomy($conn, $cache = TRUE)
    {
        Ossim_db::check_connection($conn);

        $days    = array();

        if ( empty($_SESSION["_alarm_perms_from"]) || empty($_SESSION["_alarm_perms_where"]) )
        {
            return $graph;
        }

        $tzc = Util::get_tzc();

        // Date range calculation
        if ($_SESSION["_alarm_from_date"]!='' && $_SESSION["_alarm_to_date"]!='')
        {
            // Calc min and max dates
            $sql = 'SELECT convert_tz(\''.$_SESSION["_alarm_from_date"].' 00:00:00\',\''.$tzc.'\',\'+00:00\') as min, convert_tz(\''.$_SESSION["_alarm_to_date"].' 23:59:59\',\''.$tzc.'\',\'+00:00\') as max';
            $rs = ($cache) ? $conn->CacheExecute($sql) : $conn->Execute($sql);

            if ($rs && !$rs->EOF)
            {
                if (!empty($rs->fields["min"]) && !empty($rs->fields["max"]))
                {
                    $days["min"]  = $rs->fields["min"];
                    $days["max"]  = $rs->fields["max"];
                }
            }

            if (!$days["min"] || !$days["max"])
            {
                $days["min"]  = gmdate("Y-m-d H:i:s",strtotime($_SESSION["_alarm_from_date"].' 00:00:00 '.$tzc));
                $days["max"]  = gmdate("Y-m-d H:i:s",strtotime($_SESSION["_alarm_to_date"].' 23:59:59 '.$tzc));                
            }

        }
        else
        {
            // Calc min and max dates
            $sql = 'SELECT min(convert_tz(a.timestamp,\'+00:00\',\''.$tzc.'\')) as min, max(convert_tz(a.timestamp,\'+00:00\',\''.$tzc.'\')) as max '.$_SESSION["_alarm_perms_from"].' '.$_SESSION["_alarm_perms_where"].' AND a.plugin_id=1505';
            $rs = ($cache) ? $conn->CacheExecute($sql) : $conn->Execute($sql);

            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
            elseif (!$rs->EOF)
            {
                if (!empty($rs->fields["min"]) && !empty($rs->fields["max"]))
                {
                    $days["min"]  = $rs->fields["min"];
                    $days["max"]  = $rs->fields["max"];
                }
                else
                {
                    $days["min"]   = gmdate("Y-m-d H:i:s",strtotime("6 months ago"));
                    $days["max"]   = gmdate("Y-m-d H:i:s");
                    $days["range"] = "month"; // Force a month range
                }
            }
            else
            {
                // Default date range
                $days["min"]   = gmdate("Y-m-d H:i:s",strtotime("3 months ago"));
                $days["max"]   = gmdate("Y-m-d H:i:s");
                $days["range"] = "month"; // Force a month range
            }
        }
        $diff         = gmdate("U",strtotime($days["max"]. " GMT")) - gmdate("U",strtotime($days["min"]. " GMT"));
        $days["diff"] = intval($diff / 86400) + (($diff % 86400 > 0) ? 1 : 0);

        // [0] graph data, [1] tooltip data, [2] days diff        
        $graph        = array(array(), array(), $days); 

        // Get data
        if ( $days["diff"] <= 31 )
        {
            // Hour range - 6 points/day - every 4 hours
            $sql = 'SELECT count(distinct a.backlog_id) AS occurrences, hour(convert_tz(a.timestamp,\'+00:00\',\''.$tzc.'\')) as hour, date(convert_tz(a.timestamp,\'+00:00\',\''.$tzc.'\')) as day, ta.kingdom, ta.category '.$_SESSION["_alarm_perms_from"].' '.$_SESSION["_alarm_perms_where"].' AND a.plugin_id=1505 GROUP BY ta.kingdom,ta.category,day,hour';

            $rs = ($cache) ? $conn->CacheExecute($sql) : $conn->Execute($sql);
            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
            else
            {
                while (!$rs->EOF)
                {
                	$h = floor($rs->fields["hour"] / 4);
                    $graph[0][$rs->fields["kingdom"]][$rs->fields["day"]][$h]                          += $rs->fields["occurrences"];
                    $graph[1][$rs->fields["kingdom"]][$rs->fields["day"]][$h][$rs->fields["category"]] += $rs->fields["occurrences"];

                    $rs->MoveNext();
                }
                $days["range"] = "hours";
                $graph[2]      = $days;
            }

        }
        elseif ( $days["diff"] < 365 )
        {

            // Day range - 1 points/day
            $sql = 'SELECT count(distinct a.backlog_id) AS occurrences, date(convert_tz(a.timestamp,\'+00:00\',\''.$tzc.'\')) as day, ta.kingdom, ta.category '.$_SESSION["_alarm_perms_from"].' '.$_SESSION["_alarm_perms_where"].' AND a.plugin_id=1505 GROUP BY ta.kingdom,ta.category,day';

            $rs = ($cache) ? $conn->CacheExecute($sql) : $conn->Execute($sql);
            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
            else
            {
                while (!$rs->EOF)
                {
                    $graph[0][$rs->fields["kingdom"]][$rs->fields["day"]]                          += $rs->fields["occurrences"];
                    $graph[1][$rs->fields["kingdom"]][$rs->fields["day"]][$rs->fields["category"]] += $rs->fields["occurrences"];

                    $rs->MoveNext();
                }
                $days["range"] = "days";
                $graph[2]      = $days;
            }
        }
        else
        {
            // Year range - 1 points/month
            $sql = 'SELECT count(distinct a.backlog_id) AS occurrences, month(convert_tz(a.timestamp,\'+00:00\',\''.$tzc.'\')) as m, year(convert_tz(a.timestamp,\'+00:00\',\''.$tzc.'\')) as y, ta.kingdom, ta.category '.$_SESSION["_alarm_perms_from"].' '.$_SESSION["_alarm_perms_where"].' AND a.plugin_id=1505 GROUP BY ta.kingdom,ta.category,y,m';

            $rs = ($cache) ? $conn->CacheExecute($sql) : $conn->Execute($sql);
            if (!$rs)
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
            else
            {
                while (!$rs->EOF)
                {
                    $graph[0][$rs->fields["kingdom"]][$rs->fields["y"]][$rs->fields["m"]-1]                          += $rs->fields["occurrences"];
                    $graph[1][$rs->fields["kingdom"]][$rs->fields["y"]][$rs->fields["m"]-1][$rs->fields["category"]] += $rs->fields["occurrences"];

                    $rs->MoveNext();
                }
                $days["range"] = "month";
                $graph[2]      = $days;
            }
        }
        //
        return $graph;
    }


    /**
    * This function returns some stats info from an alarm
    *
    * @param object   $conn        Database access object
    * @param string   $backlog_id  Backlog uuid
    * @param integer  $clevel      Rule level value
    * @param boolean  $cache       [Optional] Use cache queries true/false
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public function get_alarm_resume($conn, $backlog_id, $clevel, $cache = FALSE)
    {
        Ossim_db::check_connection($conn);

        $data = array("prevrl_count"=>0,"count"=>0,"dst_ips"=>0,"types"=>0,"dst_ports"=>0);
        $sql = "SELECT count(event.id) as events FROM backlog_event, event
                WHERE event.id = backlog_event.event_id AND backlog_event.backlog_id = unhex(?) AND backlog_event.rule_level=?
                AND event.plugin_id <> 1505";
        $params = array(
            $backlog_id,
            ($clevel+1)
        );
        //echo $sql."<br>";
        $rs = ($cache) ? $conn->CacheExecute($sql, $params) : $conn->Execute($sql, $params);
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            if (!$rs->EOF)
            {
                $data["prevrl_count"] = $rs->fields["events"];
            }
        }
        $sql = "SELECT count(event.id) as events, count(distinct(event.dst_ip)) as ips,
                count(distinct event.plugin_id, event.plugin_sid) as types,
                count(distinct event.dst_port) as ports
                FROM backlog_event, event
                WHERE event.id = backlog_event.event_id AND backlog_event.backlog_id = unhex(?) AND backlog_event.rule_level=?
                AND event.plugin_id <> 1505";
        $params = array(
            $backlog_id,
            $clevel
        );
        //echo $sql."<br>";
        $rs = ($cache) ? $conn->CacheExecute($sql, $params) : $conn->Execute($sql, $params);
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            if (!$rs->EOF) {
                $data["count"]     = $rs->fields["events"];
                $data["dst_ips"]   = $rs->fields["ips"];
                $data["types"]     = $rs->fields["types"];
                $data["dst_ports"] = $rs->fields["ports"];
            }
        }

        return $data;

    }


    /**
    * This function mark an alarm as 'open' status
    *
    * @param object   $conn         Database access object
    * @param string   $backlog_id   Backlog uuid
    *
    * @throws Exception  If a connection error occurred
    *
    * @return void
    */
    public function open($conn, $backlog_id)
    {
        Ossim_db::check_connection($conn);

        $sql = "UPDATE alarm SET status = 'open', timestamp = timestamp WHERE backlog_id = unhex(?)";
        $params = array(
            $backlog_id
        );
        if ($conn->Execute($sql, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        $infolog = array(
            $backlog_id
        );
        Util::memcacheFlush(FALSE);
        Log_action::log(64, $infolog);
    }


    /**
    * This function mark an alarm as 'closed' status
    *
    * @param object   $conn         Database access object
    * @param string   $backlog_id   Backlog uuid
    *
    * @throws Exception  If a connection error occurred
    *
    * @return void
    */
    public function close($conn, $backlog_id)
    {
        Ossim_db::check_connection($conn);

        $sql = "UPDATE alarm SET status = 'closed', timestamp = timestamp WHERE backlog_id = unhex(?)";
        $params = array(
            $backlog_id
        );

        if ($conn->Execute($sql, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $infolog = array(
            $backlog_id
        );
        Util::memcacheFlush(FALSE);

        Log_action::log(12, $infolog);
    }


    /**
    * This function returns a file name contains sql sentences that marks as 'closed' all alarms
    *
    * @return string
    */
    public function close_all()
    {
        $from_alarm  = $_SESSION["_alarm_perms_from"];
        $where_alarm = $_SESSION["_alarm_perms_where"];

        //Excluding alarms correlating
        $where_alarm .= ' AND a.removable=1 ';

        list($backlog_ids,$sql_tmp) = Util::create_sql_tmp_table("id binary(16) NOT NULL, PRIMARY KEY ( id )");

        // backlog Table only select
        $query   = $sql_tmp . "; \n";
        $query  .= "REPLACE INTO $backlog_ids SELECT a.backlog_id as id $from_alarm $where_alarm; \n";

        $query  .= "UPDATE alarm SET status = 'closed' where backlog_id IN (SELECT id FROM $backlog_ids); \n";

        $query  .= "DROP TABLE $backlog_ids ; \n";

        $dir     = "/tmp";
        $prefix  = "close_alarm_" . Session::get_session_user() . "_";

        $file    = Util::generate_tmp_file($dir, $prefix, $query);

        $infolog = array(
            "closed"
        );
        Log_action::log(13, $infolog);

        return $file;

    }


    /**
    * This function delete an alarm
    *
    * @param object   $conn       Database access object
    * @param string   $event_id   Event uuid
    *
    * @throws Exception  If a connection error occurred
    *
    * @return void
    */
    public function delete($conn, $event_id)
    {
        Ossim_db::check_connection($conn);

        $sql    = "DELETE FROM alarm WHERE event_id = unhex(?) AND removable=1"; // only if removable=1
        $sql2   = "DELETE e FROM event e,alarm a WHERE a.event_id=e.id AND a.removable=1 AND e.id = unhex(?)"; // only if related alarm has removable=1

        $params = array(
            $event_id
        );

        if (($conn->Execute($sql, $params) === FALSE) or ($conn->Execute($sql2, $params) === FALSE))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $infolog = array(
            $event_id
        );

        self::clean_orphans($conn);

        Util::memcacheFlush(FALSE);

        Log_action::log(11, $infolog);
    }


    /**
    * This function delete complete backlog (alarm and related events)
    *
    * @param object   $conn        Database access object
    * @param string   $backlog_id  Event uuid
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    public function delete_backlog($conn, $backlog_id)
    {

        Ossim_db::check_connection($conn);

        if (!self::delete_allowed($conn, $backlog_id))
        {
            return FALSE;
        }

        /* Delete all events related with $backlog_id */
        $tmptable = Util::create_tmp_table($conn,"event_id binary(16) NOT NULL, PRIMARY KEY ( event_id )");
        // Insert event_ids with more that 1 backlog
        $conn->Execute("REPLACE INTO $tmptable SELECT event_id FROM backlog_event group by event_id having count(*) > 1");
        // Insert event_ids with backlog and alarm
        $conn->Execute("REPLACE INTO $tmptable SELECT DISTINCT a.event_id FROM alarm a,backlog_event b WHERE a.event_id=b.event_id AND b.backlog_id<>a.backlog_id");
        $sql = "DELETE e FROM event e, backlog_event b WHERE b.backlog_id = unhex(?) AND b.event_id = e.id AND e.id NOT IN (SELECT event_id FROM $tmptable)";
        $params = array(
            $backlog_id
        );

        $sql1 = "DELETE FROM backlog_event WHERE backlog_id = unhex(?)";
        $params1 = array(
            $backlog_id
        );

        $sql2 = "DELETE FROM backlog WHERE id = unhex(?)";
        $params2 = array(
            $backlog_id
        );

        $sql3 = "DELETE FROM alarm WHERE backlog_id = unhex(?)";
        $params3 = array(
            $backlog_id
        );

        if (($conn->Execute($sql, $params) === FALSE) or ($conn->Execute($sql1, $params1) === FALSE) or ($conn->Execute($sql2, $params2) === FALSE) or ($conn->Execute($sql3, $params3) === FALSE))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $conn->Execute("DROP TABLE $tmptable");

        self::clean_orphans($conn);

        Util::memcacheFlush(FALSE);
        
        $infolog = array(
            "deleted"
        );
        Log_action::log(13, $infolog);

        return TRUE;
    }


    /**
    * This function returns a file name contains sql sentences, that delete alarms and related events, for a given criteria
    *
    * @param object   $conn    Database access object
    * @param array    $conds   [Optional] Criteria
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string
    */
    public function delete_all_backlog($conn, $conds = array())
    {
        Ossim_db::check_connection($conn);

        $from_alarm   = $_SESSION["_alarm_perms_from"];
        $from_alarm1  = $from_alarm;
        if (!preg_match("/backlog_event/",$from_alarm)) $from_alarm1 .= ",backlog_event be";
        $where_alarm  = $_SESSION["_alarm_perms_where"] . " AND a.removable=1 ";
        $sensor_where = $_SESSION["_alarm_sensor_where"];

        if($conds['day'] != '')
        {
            $where_alarm .= " " . $conds['day'];
        }

        //temporary tables
        list($event_ids,  $sql_tmp1) = Util::create_sql_tmp_table("id binary(16) NOT NULL, PRIMARY KEY ( id )");
        list($backlog_ids,$sql_tmp2) = Util::create_sql_tmp_table("id binary(16) NOT NULL, PRIMARY KEY ( id )");
        list($tmptable,   $sql_tmp3) = Util::create_sql_tmp_table("id binary(16) NOT NULL, PRIMARY KEY ( id )");

        $query = "$sql_tmp1 ;\n$sql_tmp2 ;\n$sql_tmp3 ;\n";

        // event Table only select
        $query .= "REPLACE INTO $event_ids SELECT id FROM event WHERE id in (SELECT be.event_id as id $from_alarm1 $where_alarm AND a.backlog_id = be.backlog_id ) $sensor_where ; \n";
        $query .= "REPLACE INTO $event_ids SELECT id FROM event WHERE id in (SELECT a.event_id as id $from_alarm $where_alarm) $sensor_where; \n";


        // backlog Table only select
        $query .= "REPLACE INTO $backlog_ids SELECT a.backlog_id as id $from_alarm $where_alarm; \n";
        $query .= "DELETE b.* FROM $backlog_ids b,backlog_event be,event e WHERE b.id = be.backlog_id AND be.event_id = e.id AND e.id NOT IN (SELECT id FROM $event_ids) ; \n";

        // backlog_event Table delete
        $query .= "DELETE FROM backlog_event WHERE backlog_id in (SELECT id FROM $backlog_ids) ; \n";

        // backlog Table delete
        $query .= "DELETE FROM backlog WHERE id in (SELECT id FROM $backlog_ids) ; \n";

        // alarm Table delete
        $query .= "DELETE FROM alarm WHERE backlog_id in (SELECT id FROM $backlog_ids) ; \n";

        // event Table delete

        // Insert event_ids with more that 1 backlog
        $query .= "REPLACE INTO $tmptable SELECT event_id FROM backlog_event WHERE event_id in (SELECT id FROM $event_ids) ; \n"; // Detect events in more backlogs
        $query .= "REPLACE INTO $tmptable SELECT event_id FROM alarm WHERE event_id in (SELECT id FROM $event_ids) ; \n"; // Detect events in more backlogs
        $query .= "DELETE FROM event WHERE id IN (SELECT id FROM $event_ids) AND id NOT IN (SELECT id FROM $tmptable) ; \n";

        $query .= "DROP TABLE $tmptable ; \n";
        $query .= "DROP TABLE $backlog_ids ; \n";
        $query .= "DROP TABLE $event_ids ; \n";


        if($conds['extra'] != '')
        {
            $query .= $conds['extra'] . "; \n";
        }

        //Deleting orphans alarms
        $orphans = self::clean_orphans($conn, FALSE);
        $query  .= $orphans;

        $dir    = "/tmp";
        $prefix = "delete_alarm_" . Session::get_session_user() . "_";

        $file   = Util::generate_tmp_file($dir, $prefix, $query);

        $infolog = array(
            "deleted"
        );
        Log_action::log(13, $infolog);
        
        return $file;

    }


    /**
    * This function returns a file name contains sql sentences, that delete alarms and related events, for a given day
    *
    * @param object   $conn  Database access object
    * @param string   $day   Date from
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string
    */
    public function delete_day($conn, $day)
    {
        Ossim_db::check_connection($conn);

        $cond['day'] = " AND to_days(a.timestamp) = to_days('$day')";

        $file = self::delete_all_backlog($conn, $cond);

        $infolog = array(
            "deleted (hole day $day)"
        );
        Log_action::log(13, $infolog);

        return $file;
    }


    /**
    * This function check if a current user can delete/modify an alarm
    *
    * @param object   $conn        Database access object
    * @param string   $backlog_id  Event uuid
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    public function delete_allowed($conn, $backlog_id)
    {
        Ossim_db::check_connection($conn);

        if ($rs = &$conn->Execute("SELECT backlog_id FROM alarm WHERE backlog_id = unhex(?) AND removable=1",array($backlog_id)))
        {
            if ($rs->EOF)
            {
                return FALSE; // not available for delete
            }
        }

        if (Session::am_i_admin())
        {
            return TRUE;
        }

        if ($rs = &$conn->Execute("SELECT count(*) FROM event e, backlog_event b WHERE b.backlog_id = unhex(?) AND b.event_id = e.id",array($backlog_id)))
        {
            if (!$rs->EOF)
            {
                $total = $rs->fields[0]; // without filters
            }
        }

        $where  = "";
        $ctxs   = Session::get_ctx_where();

        if ($ctxs != "")
        {
            $where .= " AND e.agent_ctx in ($ctxs)";
        }

        // Asset filter
        $hosts  = Session::get_host_where();
        $nets   = Session::get_net_where();

        if ($hosts != "")
        {
            $where .= ($nets != "") ? " AND (e.src_host in ($hosts) OR e.dst_host in ($hosts) OR e.src_net in ($nets) OR e.dst_net in ($nets))" : " AND (e.src_host in ($hosts) OR e.dst_host in ($hosts))";
        }
        elseif ($nets != "")
        {
            $where .= " AND (e.src_net in ($nets) OR e.dst_net in ($nets))";
        }

        $sql = "SELECT count(*) FROM event e, backlog_event b WHERE b.backlog_id = unhex(?) AND b.event_id = e.id $where";

        if ($rs = &$conn->Execute($sql,array($backlog_id)))
        {
            if (!$rs->EOF)
            {
                $filtered = $rs->fields[0]; // with filters
            }
        }

        return ($total==$filtered);
    }


    /**
    * This function return a string with current clean queries for orphan records in related alarm tables or execute them
    *
    * @param object   $conn   Database access object
    * @param boolean  $exec   [Optional] true / false
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string
    */
    public function clean_orphans($conn, $exec = TRUE)
    {

        Ossim_db::check_connection($conn);

        if ($exec)
        {
            $conn->Execute("DELETE tg FROM alarm_tags tg LEFT JOIN alarm a ON tg.id_alarm = a.backlog_id WHERE a.backlog_id IS NULL");
            $conn->Execute("DELETE ac FROM alarm_ctxs ac LEFT JOIN alarm a ON ac.id_alarm = a.backlog_id WHERE a.backlog_id IS NULL");
            $conn->Execute("DELETE ah FROM alarm_hosts ah LEFT JOIN alarm a ON ah.id_alarm = a.backlog_id WHERE a.backlog_id IS NULL");
            $conn->Execute("DELETE an FROM alarm_nets an LEFT JOIN alarm a ON an.id_alarm = a.backlog_id WHERE a.backlog_id IS NULL");
            $conn->Execute("DELETE idm FROM idm_data idm LEFT JOIN event e ON idm.event_id = e.id WHERE e.id IS NULL");
            $conn->Execute("DELETE ed FROM extra_data ed LEFT JOIN event e ON ed.event_id = e.id WHERE e.id IS NULL");
            Util::memcacheFlush(FALSE);

            return '';
        }
        else
        {
            $query  = "\n";
            $query .= "DELETE tg FROM alarm_tags tg LEFT JOIN alarm a ON tg.id_alarm = a.backlog_id WHERE a.backlog_id IS NULL; \n";
            $query .= "DELETE ac FROM alarm_ctxs ac LEFT JOIN alarm a ON ac.id_alarm = a.backlog_id WHERE a.backlog_id IS NULL; \n";
            $query .= "DELETE ah FROM alarm_hosts ah LEFT JOIN alarm a ON ah.id_alarm = a.backlog_id WHERE a.backlog_id IS NULL; \n";
            $query .= "DELETE an FROM alarm_nets an LEFT JOIN alarm a ON an.id_alarm = a.backlog_id WHERE a.backlog_id IS NULL; \n";
            $query .= "DELETE idm FROM idm_data idm LEFT JOIN event e ON idm.event_id = e.id WHERE e.id IS NULL; \n";
            $query .= "DELETE ed FROM extra_data ed LEFT JOIN event e ON ed.event_id = e.id WHERE e.id IS NULL; \n";

            return $query;
        }
    }


    /**
    * This function return true if current user has enough permission to view an alarm event, false if not.
    *
    * @param object   $conn      Database access object
    * @param string   $ctx       Context uuid
    * @param string   $src_host  Source host uuid
    * @param string   $dst_host  Destination host uuid
    * @param string   $src_net   Source network uuid
    * @param string   $dst_net   Destination network uuid
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string
    */
    public function event_allowed($conn, $ctx, $src_host, $dst_host, $src_net, $dst_net)
    {
        Ossim_db::check_connection($conn);

        if (Session::am_i_admin())
        {
            return TRUE;
        }
        // host && nets
        if (($src_net || $dst_net) && $_SESSION["_user_vision"]["net_where"])
        {
            if (!Session::netAllowed($conn, $src_net) && !Session::netAllowed($conn, $dst_net))
            {
                return FALSE;
            }
        }
        if (($src_host || $dst_host) && $_SESSION["_user_vision"]["host_where"])
        {
            if (!Session::hostAllowed($conn, $src_host) && !Session::hostAllowed($conn, $dst_host))
            {
                return FALSE;
            }
        }
        // ctx
        return ($_SESSION["_user_vision"]["entity"][$ctx]>=1);
    }


    /**
    * This function return alarm event value for a given field name
    *
    * @use-by Util::translate_alarm
    *
    * @param object   $conn    Database access object
    * @param string   $key    [Optional] Field name
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string
    */
    public function get_event_data($conn, $key="username")
    {
        Ossim_db::check_connection($conn);

        if (empty($this->event_data))
        {
            $sql = "SELECT filename,username,userdata1,userdata2,userdata3,userdata4,userdata5,userdata6,userdata7,userdata8,userdata9 FROM alienvault_siem.extra_data WHERE event_id=unhex('".$this->event_id."')";
            if (!$rs = & $conn->Execute($sql))
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
            if (!$rs->EOF)
            {
                $this->event_data = $rs->fields;
            }
        }

        return Util::htmlentities($this->event_data[$key],ENT_QUOTES);
    }


    /**
    * This function return event defail
    *
    * @param object   $conn       Database access object
    * @param string   $event_id   Event uuid
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public function get_event ($conn, $event_id)
    {
        Ossim_db::check_connection($conn);

        /* get main events id */
        $query = ossim_query("SELECT *, HEX(agent_ctx) AS agent_ctx, HEX(src_host) AS src_host, HEX(dst_host) AS dst_host, HEX(src_net) AS src_net, HEX(dst_net) as dst_net FROM event WHERE id=unhex('$event_id')");

        if (!$rs = & $conn->Execute($query))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        elseif (!$rs->EOF)
        {
            return $rs->fields;
        }

        return array();
    }


    /**
    * This function return array with event detail and stats info of an alarm
    *
    * @param object   $conn        Database access object
    * @param string   $backlog_id  Backlog uuid
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public function get_alarm_detail($conn, $backlog_id)
    {
        Ossim_db::check_connection($conn);

        // Context filter
        $ctxs = Session::get_ctx_where();
        if ($ctxs != "")
        {
            $where = " AND corr_engine_ctx in ($ctxs)";
        }

        $query  = "SELECT *, HEX(event_id) as event_id, HEX(corr_engine_ctx) as corr_engine_ctx, ki.id as kid, ki.name as kingdom, ca.name as category, ta.subcategory from alarm LEFT JOIN ( alarm_taxonomy ta LEFT JOIN alarm_kingdoms ki ON ta.kingdom=ki.id LEFT JOIN alarm_categories ca ON ta.category=ca.id ) ON alarm.plugin_sid=ta.sid AND alarm.corr_engine_ctx=ta.engine_id WHERE 1=1 $where AND backlog_id=unhex(?)";
        $params = array($backlog_id);

        if (!$rs = & $conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        elseif (!$rs->EOF)
        {
            $stats  = json_decode($rs->fields['stats'], TRUE);
            $new    = array (
            	"event_id"     => $rs->fields["event_id"],
                "backlog_id"   => $backlog_id,
                "ctx"          => $rs->fields["corr_engine_ctx"],
                "timestamp"    => $rs->fields["timestamp"],
                "status"       => $rs->fields["status"],
                "plugin_id"    => $rs->fields["plugin_id"],
                "plugin_sid"   => $rs->fields["plugin_sid"],
                "protocol"     => $rs->fields["protocol"],
                "src_ip"       => @inet_ntop($rs->fields["src_ip"]),
                "dst_ip"       => @inet_ntop($rs->fields["dst_ip"]),
                "src_port"     => $rs->fields["src_port"],
                "dst_port"     => $rs->fields["dst_port"],
                "risk"         => $rs->fields["risk"],
                "rule_level"   => "",
                "alarm"        => 1,
                "tzone"        => $rs->fields["tzone"],
                "similar"      => $rs->fields["similar"],
                "csimilar"     => 0,
                "removable"    => $rs->fields["removable"],
                "taxonomy"     => array (
					                "id"           => $rs->fields["kid"],
					                "kingdom"      => $rs->fields["kingdom"],
					                "category"     => $rs->fields["category"],
					                "subcategory"  => $rs->fields["subcategory"]
					              )
            );

            $alarm = new Alarm($conn, TRUE, $new);

        }

        $event  = array();
        $ev_id  = ( is_object( $alarm ) ) ? $alarm->get_event_id() : '';
        $query  = "SELECT *, HEX(agent_ctx) as ctx, HEX(src_host) AS src_host, HEX(dst_host)
                        AS dst_host, HEX(src_net) AS src_net, HEX(dst_net) AS dst_net
                    FROM event
                    WHERE id = UNHEX(?)
                ";
        $params = array($ev_id);

        if (!$rs = & $conn->Execute($query, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        elseif (!$rs->EOF)
        {
            $sensor                      = Av_sensor::get_object($conn, $rs->fields['sensor_id']);
            $event['_SENSOR']            = ($sensor != NULL) ? $sensor->get_name() : '';
            $event['_SRCIP']             = @inet_ntop($rs->fields["src_ip"]);
            $event['_SRCMAC']            = preg_replace("/(..)(..)(..)(..)(..)(..)/", "\\1:\\2:\\3:\\4:\\5:\\6", $rs->fields['src_mac']);
            $event['_DSTIP']             = @inet_ntop($rs->fields["dst_ip"]);
            $event['_DSTMAC']            = preg_replace("/(..)(..)(..)(..)(..)(..)/", "\\1:\\2:\\3:\\4:\\5:\\6", $rs->fields['dst_mac']);
            $event['_SRCPORT']           = $rs->fields['src_port'];
            $event['_DSTPORT']           = $rs->fields['dst_port'];
            $event['_SRCUSER']           = $rs->fields['username'];
            $event['_FILENAME']          = $rs->fields['filename'];
            $event['_USERDATA1']         = $rs->fields['userdata1'];
            $event['_USERDATA2']         = $rs->fields['userdata2'];
            $event['_USERDATA3']         = $rs->fields['userdata3'];
            $event['_USERDATA4']         = $rs->fields['userdata4'];
            $event['_USERDATA5']         = $rs->fields['userdata5'];
            $event['_USERDATA6']         = $rs->fields['userdata6'];
            $event['_USERDATA7']         = $rs->fields['userdata7'];
            $event['_USERDATA8']         = $rs->fields['userdata8'];
            $event['_USERDATA9']         = $rs->fields['userdata9'];
            $event['_PRIORITY']          = $rs->fields['priority'];
            $event['_RELIABILITY']       = $rs->fields['reliability'];
            $event['_SRCCRITICALITY']    = $rs->fields['rep_prio_src'];
            $event['_DSTCRITICALITY']    = $rs->fields['rep_prio_dst'];
            $event['_SRCREPACTIVITY']    = $rs->fields['rep_act_src'];
            $event['_DSTREPACTIVITY']    = $rs->fields['rep_act_dst'];
            $event['_SRCREPRELIABILITY'] = $rs->fields['rep_rel_src'];
            $event['_DSTREPRELIABILITY'] = $rs->fields['rep_rel_dst'];
            $event['_CTX']               = $rs->fields['ctx'];
            $event['_SRC_HOST']          = $rs->fields['src_host'];
            $event['_DST_HOST']          = $rs->fields['dst_host'];
            $event['_SRC_NET']           = $rs->fields['src_net'];
            $event['_DST_NET']           = $rs->fields['dst_net'];
        }

        return array($alarm, $stats, $event);

    }


    /**
    * This function return array with similar alarm count values grouped by days
    *
    * @param object   $conn        Database access object
    * @param integer  $plugin_id   Plugin ID
    * @param integer  $plugin_sid  Plugin SID
    * @param integer  $limit       [Optional] Days
    *
    * @throws Exception  If a connection error occurred
    *
    * @return array
    */
    public function get_alarm_trend ($conn, $plugin_id, $plugin_sid, $limit=15)
    {
        Ossim_db::check_connection($conn);

        // Get alarm counter value per day and state
        $days  = array();       

        for($i = 0; $i < $limit; $i++)
        {
            $day                  = date('Y-m-d', strtotime("-$i day"));
            $days[$day]['open']   = 0;
            $days[$day]['closed'] = 0;
        }

        $sql = "SELECT count(backlog_id) as counter,status,date(timestamp) as day FROM alarm WHERE plugin_id=? AND plugin_sid=? AND timestamp >= ? GROUP BY day,status ORDER BY day DESC";
        $params = array(
            $plugin_id,
            $plugin_sid,
            $day ." 00:00:00"
        );
        if (!$rs = & $conn->Execute($sql, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF)
            {
                $day = $rs->fields["day"];

                $days[$day][$rs->fields["status"]] = $rs->fields["counter"];

                $rs->MoveNext();
            }
        }

        return $days;
    }


    /**
    * This function return array with intent, strategy and method alarm name
    *
    * @param array    $taxonomy  Taxonomy array with "kingdom", "category" and "subcategory" keys
    * @param boolean  $icon      [Optional] true / false
    *
    * @return array
    */
    public function get_alarm_name ($taxonomy, $icon = TRUE)
    {
        // Get alarm name with intent-strategy-method if exists or single alarm name
        if ($taxonomy["kingdom"] != "" && $taxonomy["category"] != "")
        {
            $intent = ($icon && file_exists("/usr/share/ossim/www/alarm/style/img/".$taxonomy["id"].".png")) ? "<img src='style/img/".$taxonomy["id"].".png' border='0' class='img_intent' title='".$taxonomy["kingdom"]."' align='absmiddle'>" : $taxonomy["kingdom"]." &mdash;";

            return array ($intent."&nbsp;".$taxonomy["category"], $taxonomy["subcategory"]);
        }
        else
        {
            return array ($taxonomy["name"], "");
        }
    }


    /**
    * This function return string with alarm name depends on taxonomy value
    *
    * @param object   $conn   Database access object
    * @param string   $name   Alarm name
    *
    * @throws Exception  If a connection error occurred
    *
    * @return string
    */
    public function transform_alarm_name ($conn, $name)
    {
        Ossim_db::check_connection($conn);

        // Get intent icon from alarm description
        $alarm_tax = explode(" &mdash; ",$name);
        if ( count($alarm_tax) == 3 && $alarm_tax[0] != "")
        {
            $sql = "SELECT id FROM alarm_kingdoms WHERE name=?";
            $params = array(
                $alarm_tax[0]
            );
            if (!$rs = & $conn->Execute($sql, $params))
            {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }
            elseif (!$rs->EOF)
            {
                $id = $rs->fields["id"];
                if (!file_exists("/usr/share/ossim/www/alarm/style/img/".$id.".png"))
                {
                    return $name;
                }
                return "<img src='style/img/".$id.".png' border='0' class='img_intent' title='".$alarm_tax[0]."' align='absmiddle'>&nbsp;".$alarm_tax[1]."  &mdash; ".$alarm_tax[2];
            }
        }

        return $name;
    }

    /**
    * This function clone alarm taxonomy for engine_id
    *
    * @param object   $conn        Database access object
    * @param string   $engine_id   Engine UUID (Hex value)
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    public function clone_taxonomy ($conn, $engine_id)
    {
        Ossim_db::check_connection($conn);

        $params = array($engine_id);         
        $sql    = "REPLACE INTO alarm_taxonomy SELECT sid,unhex(?),kingdom,category,subcategory FROM alarm_taxonomy 
            WHERE engine_id = UNHEX('00000000000000000000000000000000')";
                
        if (!$conn->Execute($sql, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        return TRUE;
    }

    /**
    * This function delete alarm taxonomy related to engine_id
    *
    * @param object   $conn        Database access object
    * @param string   $engine_id   Engine UUID (Hex value)
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    public function delete_from_taxonomy ($conn, $engine_id)
    {
        Ossim_db::check_connection($conn);

        $params = array($engine_id);
        $sql    = "DELETE FROM alarm_taxonomy WHERE engine_id=unhex(?)";
        
        if (!$conn->Execute($sql, $params))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        
        return TRUE;
    }

}

/* End of file alarm.inc */
/* Location: ../include/classes/alarm.inc */