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
 * @link       https://www.alienvault.com/ AlienVault web page
 */

class Alarm
{
    const OTX_PULSE_SID = 29998;

    const OTX_PULSE     = 1;
    const OTX_REP       = 2;
    const OTX_PULSE_REP = 3;


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
     * Alarm tag list (Tag array: array(tag_id -> tag_object, ...))
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
     * Alarm stats
     * @var array
     *
     */
    private $stats;

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
        $this->stats        = $data['stats'];

        // Private calls
        list ($this->sid_name, $this->sid_priority) = $this->__get_sidname($conn, $this->plugin_id, $this->plugin_sid, $cache,  $this->ctx);
        $this->taxonomy["name"]                     = $this->sid_name;
        $this->tags                                 = Tag::get_tags_by_component($conn, $this->backlog_id);
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
     * This function returns the alarm stats
     *
     * @return array
     */
    public function get_stats($json = FALSE)
    {
        if ($json)
        {
            return $this->stats;
        }
        else
        {
            $_arr = json_decode($this->stats, TRUE);
            return is_array($_arr) ? $_arr : array();
        }
    }


    /**
     * This function returns wheter the alarm has reputation or not
     *
     * @return boolean
     */
    public function has_reputation()
    {
        $stats   = $this->get_stats();
        $has_rep = FALSE;

        //If we have the var rep either from src or dst then we have reputation
        if ($stats['src']['rep'] > 0 || $stats['dst']['rep'] > 0)
        {
            $has_rep = TRUE;
        }
        else
        {
            foreach(array('src', 'dst') as $origin)
            {
                if(isset($stats[$origin]['ip'])) {
                    foreach ($stats[$origin]['ip'] as $ip_data) {
                        if ($ip_data['rep'] > 0) {
                            $has_rep = TRUE;
                            break 2; //To skip both foreachs
                        }
                    }
                }
            }
        }

        unset($stats);
        return $has_rep;
    }


    /**
     * This function returns the otx flag
     *
     * @return boolean
     */
    public function has_otx()
    {
        $c_pulse = $this->plugin_id == 1505 && $this->plugin_sid == self::OTX_PULSE_SID;
        $c_rep   = $this->has_reputation();
        $c_perms = Session::logcheck_bool("dashboard-menu", "IPReputation");

        if (!$c_perms)
        {
            return FALSE;
        }
        elseif ($c_pulse && $c_rep)
        {
            return self::OTX_PULSE_REP;
        }
        elseif ($c_pulse)
        {
            return self::OTX_PULSE;
        }
        elseif ($c_rep)
        {
            return self::OTX_REP;
        }
        else
        {
            return FALSE;
        }
    }


    /**
     * This function returns the otx icon path
     *
     * @return string
     */
    public function get_otx_icon()
    {
        $icon = '';

        switch ($this->has_otx())
        {
            case self::OTX_PULSE_REP:
                $icon = AV_PIXMAPS_DIR . '/otxrep_icon.png';
                break;

            case self::OTX_PULSE:
                $icon = AV_PIXMAPS_DIR . '/otx_icon.png';
                break;

            case self::OTX_REP:
                $icon = AV_PIXMAPS_DIR . '/rep_icon.png';
                break;

            default:
                $icon = '';
        }

        return $icon;
    }


    /**
     * This function returns the number of IOCs
     *
     * @return string
     */
    public function get_iocs($conn, $cache = FALSE)
    {
        list($p_join, $p_where) = self::make_perms_where();
        $p_where = ($p_where) ? ' AND ' . $p_where : '';

        $query = "
        SELECT COUNT(DISTINCT e.src_ip) as total_iocs FROM event e, backlog_event be, alarm a $p_join
            WHERE a.backlog_id=UNHEX(?) AND be.backlog_id=a.backlog_id AND be.event_id=e.id AND e.rep_prio_src > 0 $p_where
        UNION ALL
        SELECT COUNT(DISTINCT e.dst_ip) as total_iocs FROM event e, backlog_event be, alarm a $p_join
            WHERE a.backlog_id=UNHEX(?) AND be.backlog_id=a.backlog_id AND be.event_id=e.id AND e.rep_prio_dst > 0 $p_where
        UNION ALL
        SELECT COUNT(DISTINCT o.ioc_hash) as total_iocs FROM event e, backlog_event be, otx_data o, alarm a $p_join
            WHERE a.backlog_id=UNHEX(?) AND be.backlog_id=a.backlog_id AND o.event_id=be.event_id AND o.pulse_id = UNHEX(LEFT(e.filename,24)) AND e.id=a.event_id $p_where";

        $params = array($this->backlog_id, $this->backlog_id, $this->backlog_id);
        $rs     = ($cache) ? $conn->CacheExecute($query, $params) : $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        $iocs = 0;
        while (!$rs->EOF)
        {
            $iocs += intval($rs->fields['total_iocs']);
            $rs->MoveNext();
        }

        return $iocs;
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
    public static function get_count($conn, $src_ip = "", $dst_ip = "", $hide_closed = 1,  $cache = FALSE)
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

        $ctx_join .= " ,backlog b";
        $where[]   = "a.backlog_id=b.id AND b.timestamp<>'1970-01-01 00:00:00'";

        $sql_where = "WHERE " . implode(" AND ", $where);

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
        $rs    = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);
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
    private function __get_sidname($conn, $pid, $psid, $cache = FALSE, $ctx)
    {
        Ossim_db::check_connection($conn);

        $sql = "SELECT REPLACE(name, 'directive_event: ', '') as sid_name,priority FROM plugin_sid WHERE plugin_id=? AND sid=? AND  hex(plugin_ctx) IN (? ,'00000000000000000000000000000000')";
        $params = array
        (
            $pid, $psid, $ctx
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

            $rsp = $conn->Execute("SELECT * FROM port");

            if (!$rsp)
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
     * @param object   $conn         Database access object
     * @param string   $type         Asset type (host, network, group)
     * @param string   $id           Asset uuid (Hex value)
     * @param boolean  $hide_closed  Only open alarms will be taken into account if parameter is set to TRUE
     *
     * @throws Exception  If a connection error occurred
     *
     * @return boolean
     */
    public static function has_alarms($conn, $type, $id, $hide_closed = TRUE)
    {
        Ossim_db::check_connection($conn);

        $param = array($id);
        $hc_where = ($hide_closed == TRUE) ? " AND a.status = 'open'" : "";

        if ($type == "host")
        {
            $sql = "SELECT EXISTS
                    (
                        SELECT 1
                        FROM alarm_hosts ah, alarm a
                        WHERE a.backlog_id=ah.id_alarm AND ah.id_host = UNHEX(?) $hc_where
                        LIMIT 1
                    ) as bool";
        }
        elseif ($type == "net")
        {
            $sql = "SELECT EXISTS
                    (
                        SELECT 1
                        FROM alarm_nets an, alarm a, net ne, net_cidrs n, net ne1, net_cidrs n1
                        WHERE ne.id = n.net_id AND ne1.id = n1.net_id
                        AND n.begin <= n1.begin AND n1.end <= n.end
                        AND ne.ctx = ne1.ctx AND n.net_id = UNHEX(?)
                        AND a.backlog_id=an.id_alarm AND n1.net_id=an.id_net $hc_where
                        LIMIT 1
                    ) as bool";
        }
        else
        {
            // Using perms
            $perms_where = Asset_host::get_perms_where("h.",TRUE);

            $sql = "SELECT EXISTS
                    (
                        SELECT 1
                        FROM alarm_hosts ah, host_group_reference r, host h, alarm a
                        WHERE a.backlog_id=ah.id_alarm AND ah.id_host = r.host_id
                            AND h.id = r.host_id AND r.host_group_id = UNHEX(?) $perms_where $hc_where
                        LIMIT 1
                    ) as bool";
        }

        $rs = $conn->Execute($sql, $param);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        // Total rows
        return ($rs->fields["bool"]) ? true : false;
    }

    /**
     * This function returns the sql query (where) that will be used to filter by a given asset
     *
     * @param object  $conn      Database access object
     * @param string  $asset_id  Asset ID to filter
     * @param string  $type      Asset type (host, net or host_group)
     * @return array
     * @throws Exception  If a connection error occurred
     */
    public static function make_asset_where($conn, $asset_id, $type) {
        $where    = "";
        $ctx_join = "";

        if ($type == 'host') {
            $where = "ah.id_host = UNHEX('$asset_id')";
            $ctx_join = " LEFT JOIN alarm_hosts ah ON a.backlog_id=ah.id_alarm";
        }
        else if ($type == 'net') {
            $subnets = Asset_net::get_subnets($conn, array($asset_id));
            $subnets = explode(',', $subnets);
            $where    = "an.id_net IN (UNHEX('".implode("'), UNHEX('",$subnets)."'))";
            $ctx_join = " LEFT JOIN alarm_nets an ON a.backlog_id=an.id_alarm";
        }

        else if ($type == 'host_group') {
            $where = "ah.id_host in (SELECT host_id FROM host_group_reference WHERE host_group_id=UNHEX('$asset_id'))";
            $ctx_join = " LEFT JOIN alarm_hosts ah ON a.backlog_id=ah.id_alarm";
        }

        return array($ctx_join, $where);
    }



    /**
     * This function returns the sql queries (join and where) that will be used to get the user permissions
     *
     * @param string $ctx  [Optional] Ctx to filter
     * @return array
     */
    public static function make_perms_where($ctx = "")
    {
        $where    = "";
        $ctx_join = "";
        $host_where = Session::get_host_where();
        $net_where  = Session::get_net_where();

        if ($host_where != "" && !Session::only_ff_host())
        {
            if ($net_where != "" && !Session::only_ff_net())
            {
                $where    .= " (ah.id_host in ($host_where) OR an.id_net in ($net_where))";
                $ctx_join .= " LEFT JOIN alarm_hosts ah ON a.backlog_id=ah.id_alarm LEFT JOIN alarm_nets an ON a.backlog_id=an.id_alarm";
            }
            else
            {
                $where    .= " ah.id_host in ($host_where)";
                $ctx_join .= " LEFT JOIN alarm_hosts ah ON a.backlog_id=ah.id_alarm";
            }
        }
        elseif ($net_where != "" && !Session::only_ff_net())
        {
            $where    .= " an.id_net in ($net_where)";
            $ctx_join .= " LEFT JOIN alarm_nets an ON a.backlog_id=an.id_alarm";
        }

        // CTX's filter
        $ctxs = Session::get_ctx_where();
        if ($ctxs != "")
        {
            $where    .= ($where=='' ? '' : ' AND ') . "a.backlog_id=ac.id_alarm AND ac.id_ctx in ($ctxs)";
            $ctx_join .= " JOIN alarm_ctxs ac";
        }

        if ($ctx != "")
        {
            $where    .= ($where=='' ? '' : ' AND ') . "(a.backlog_id=acc.id_alarm AND acc.id_ctx = UNHEX('$ctx') OR a.corr_engine_ctx = UNHEX('$ctx'))";
            $ctx_join .= " JOIN alarm_ctxs acc";
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
    public static function get_list($conn, $params, $cache = FALSE)
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
        $max_risk           = $params['max_risk'];
        $min_risk           = $params['min_risk'];
        $plugin_id          = $params['plugin_id'];
        $plugin_sid         = $params['plugin_sid'];
        $ctx                = $params['ctx'];
        $host               = $params['host'];
        $net                = $params['net'];
        $host_group         = $params['host_group'];
        $pulse_id           = $params['pulse_id'];
        $otx_activity       = $params['otx_activity'];

        $_SESSION["_alarm_perms_where"]  = "";
        $_SESSION["_alarm_perms_from"]   = "";
        $_SESSION["_alarm_sensor_where"] = "";
        $_SESSION["_alarm_from_date"]    = $from_date;
        $_SESSION["_alarm_to_date"]      = $to_date;
        $_SESSION["_alarm_count"] = 0;

        $list       = array();
        $where      = array();
        $join       = array();
        $join_alarm = [];
        $where_alarm = [];

        /* Do not show logical deleted alarms */
        $join['backlog'] = 'backlog b';
        $where[]         = "a.backlog_id=b.id AND b.timestamp<>'1970-01-01 00:00:00'";

        $ports      = self::__get_ports_cache($conn);

        //
        //Permissions
        //
        list($ctx_join, $perms_where) = self::make_perms_where($ctx);

        //Get sensors
        if (!Session::am_i_admin() && empty($sensor)) {
            $all_sensors = Av_sensor::get_all($conn);
            list($allowed_sensors) = Av_sensor::get_list($conn, array(), FALSE, TRUE);
            // If I can see all sensors, it doesn't make sense to filter by sensor
            if (count($all_sensors) != count($allowed_sensors)){
                $sensor = implode("','",array_keys($allowed_sensors));
            }
        }

        if (!empty($perms_where)) {
            $where[] = $perms_where;
        }

        //
        // CRITERIA PARSER
        //

        /* hide closed */
        if ($hide_closed == 1)
        {
            $where_alarm[] = "a.status = 'open' ";
        }
        elseif ($hide_closed == -1)
        {
            $where_alarm[] = "a.status = 'closed' ";
        }

        /* date range filter */
        if (!empty($from_date) || !empty($to_date))
        {
            $tz = Util::get_timezone();

            //$from_date is empty
            if (!empty($from_date)){
                if (!preg_match('/\s\d+:/', $from_date))
                {
                    list($y, $m, $d) = preg_split('#-#', $from_date);
                    if (!checkdate($m, $d, $y))
                    {
                        print _("Invalid date") . ": $from_date";
                        return array();
                    }
                }

                $_from_date_utc = Util::get_utc_from_date($conn, "$from_date 00:00:00", $tz);

                $where_alarm[] = " a.timestamp >= '$_from_date_utc[6]'";
            }

            //$to_date is empty
            if (!empty($to_date)){
                if (!preg_match('/\s\d+:/', $to_date))
                {
                    list($y, $m, $d) = preg_split('#-#', $to_date);

                    if (!checkdate($m, $d, $y))
                    {
                        print _("Invalid date") . ": $to_date";
                        return array();
                    }
                }

                $_to_date_utc = Util::get_utc_from_date($conn, "$to_date 23:59:59", $tz);

                $where_alarm[] = " a.timestamp <= '$_to_date_utc[6]'";
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

            $join['event'] = 'event e';
            $join['backlog_event'] = 'backlog_event be';
        }
        // Single IP
        else
        {
            if (preg_match('/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/', $src_ip)) {
                $src_ip .= "/32";
            }
            if (preg_match('/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/', $dst_ip)){
                $dst_ip.= "/32";
            }

            if (isset($src_ip)){
                list($source_start, $source_end) = Cidr::expand_CIDR($src_ip, "SHORT", "IP");
            }
            if (isset($dst_ip)) {
                list($dest_start, $dest_end)     = Cidr::expand_CIDR($dst_ip, "SHORT", "IP");
            }

            /* If both src and dst are equal, try to find all alarms where src OR dst are equal to the IP */
            if ($src_ip && $dst_ip)
            {
                $where[] = "(unhex('".bin2hex(inet_pton($source_end))."') = e.src_ip AND unhex('".bin2hex(inet_pton($dest_end))."') = e.dst_ip) ";
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
                $where[] = "be.event_id=e.id AND be.backlog_id=a.backlog_id";
                $join['event'] = 'event e';
                $join['backlog_event'] = 'backlog_event be';
            }
        }

        // Asset filter
        if (security_class::valid_hex32($host)){
            $asset_id = $host;
            $asset_type = 'host';
            $asset_table = 'alarm_hosts';
        } else if (security_class::valid_hex32($net)){
            $asset_id = $net;
            $asset_type = 'net';
            $asset_table = 'alarm_nets';
        } else if (security_class::valid_hex32($host_group)){
            $asset_id = $host_group;
            $asset_type = 'host_group';
            $asset_table = 'alarm_hosts';
        } else {
            $asset_id = '';
            $asset_type = '';
            $asset_table = '';
        }

        if (!empty($asset_id)){
            list($asset_ctx_join, $asset_where) = self::make_asset_where($conn, $asset_id, $asset_type);

            if (!preg_match("/$asset_table/", $ctx_join)) {
                $ctx_join = $asset_ctx_join." ".$ctx_join;
            }

            if (!empty($asset_where)) {
                $where[] = $asset_where;
            }
        }

        /* number of events per page */
        $limit = "";
        if ($sup !== NULL && $inf !== NULL)
        {
            $nevents = $sup - $inf;
            $limit = "LIMIT $nevents";
        }
        else{
            $nevents = -1;
        }

        /* free text query */
        if ($query != "")
        {
            $join_alarm[] = 'INNER JOIN alienvault.plugin_sid p ON a.plugin_id=p.plugin_id AND a.plugin_sid=p.sid';

            $query       = escape_sql($query,$conn);
            $where_alarm[]     = ' (p.name LIKE "%'.$query.'%"  OR ki.name LIKE "%'.$query.'%" or ca.name LIKE "%'.$query.'%" or ta.subcategory LIKE "%'.$query.'%" or a.backlog_id=unhex("'.$query.'"))';
        }

        /* directive id */
        if ($directive_id != "")
        {
            $where[] = "b.directive_id=$directive_id";
        }

        /* Intent Filter */
        if ($intent >  0)
        {
            $where_alarm[] = "ta.kingdom=".intval($intent);
        }

        /* Events contains */
        if ( $plugin_id != '' && $plugin_sid != '')
        {
            $join['event'] = 'event e';
            $join['backlog_event'] = 'backlog_event be';

            if($plugin_id == 1505)
            {
                $where[] = "be.event_id=e.id AND be.backlog_id=a.backlog_id AND a.plugin_id = $plugin_id";
                $where_alarm[] = "a.plugin_sid = $plugin_sid";
            }
            else
            {
                $where[] = "be.event_id=e.id AND be.backlog_id=a.backlog_id AND e.plugin_id = $plugin_id AND e.plugin_sid = $plugin_sid";
            }
        }

        /* Sensor filter */
        if ($sensor != "")
        {
            $join['event'] = 'event e';
            $join['backlog_event'] = 'backlog_event be';

            $where[] = "be.event_id=e.id AND be.backlog_id=a.backlog_id AND hex(e.sensor_id) IN ('$sensor')";
            $_SESSION["_alarm_sensor_where"] = " AND hex(event.sensor_id) IN ('$sensor') ";
        }
        /* Tag filter */
        if ($tag != "")
        {
            $join['component_tags'] = 'component_tags tg';
            $where[] = "a.backlog_id=tg.id_component AND tg.id_tag=UNHEX('$tag')";
        }

        /* OTX Activity */
        if ($otx_activity || $pulse_id)
        {
            //Any otx
            if ($otx_activity)
            {
                $where_alarm[] = 'a.plugin_id=1505 AND a.plugin_sid='. self::OTX_PULSE_SID;
            }

            //Specific Pulse
            if ($pulse_id)
            {
                $join['event'] = 'event e';
                $where[] = 'a.event_id=e.id AND e.filename="'. strtoupper($pulse_id) .'"';
            }
        }

        /* Number of events threshold filter */
        if ($num_events != '' && $num_events_op != '')
        {
            if ($num_events_op == 'less')
            {
                $num_events_op = '<=';
            }
            elseif ($num_events_op == 'more')
            {
                $num_events_op = '>=';
            }
            else
            {
                $num_events_op = '=';
            }

            $zero = ($num_events <= 1 || $num_events_op == "<=") ? " OR a.backlog_id not in (SELECT backlog_id FROM backlog_event)" : "";

            $where_alarm[] = " (a.backlog_id in (select backlog_id from (SELECT count(*) as total_events,backlog_event.backlog_id FROM backlog_event, event WHERE event.id = backlog_event.event_id AND event.plugin_id<>1505 GROUP BY backlog_id having total_events $num_events_op $num_events) as bk) $zero)";
        }
        /* Number of events threshold filter */
        if ($min_risk) {
            $min_risk++;
            $where_alarm[] = " a.risk >= $min_risk ";
        }
        if ($max_risk != "" && $max_risk != 2) {
            $max_risk++;
            $where_alarm[] = " a.risk <= $max_risk ";
        }
        // taxonomy left join
        $join_alarm[] = "LEFT JOIN ( alarm_taxonomy ta LEFT JOIN alarm_kingdoms ki ON ta.kingdom=ki.id LEFT JOIN alarm_categories ca ON ta.category=ca.id ) ON a.plugin_sid=ta.sid AND a.corr_engine_ctx=ta.engine_id";

        //source ip always include join and where so no need to  check
        $sql_join  = ", " . implode(" , ", $join);
        if (count($where) > 0){
            $sql_where = "WHERE ". implode(" AND ", $where);
        }
        else {
            $sql_where = "WHERE 1=1 ";
        }

        $sql_where_alarm = count($where_alarm) > 0 ? "WHERE " . implode(" AND ", $where_alarm): "";

        $join_alarm = implode($join_alarm, "\n");

        // To end properly working with chunks we required to know how many elements we should query
        $sql_total_alarms = "select count(*) as cnt from (select backlog_id from alarm group by similar) a";
        // Total rows
        $total = $conn->Execute($sql_total_alarms);

        if (!$total->EOF) {
            $total_alarms = $total->fields["cnt"];
        }
        else{
            $total_alarms = 0;
        }


        $sql = "
            SELECT DISTINCT a.*
                FROM (
                    SELECT a.*, count(backlog_id) as cntalarm, ki.id as kid, ki.name as kingdom, ca.name as category, ta.subcategory
                    FROM alarm a
                    $join_alarm
                    $sql_where_alarm
                    GROUP BY similar
                    $order
                    LIMIT CHUNK_SIZE OFFSET START_AT) a
                $ctx_join
                $sql_join
                $sql_where
                $limit
            ";

        $sql_count = "
          SELECT COUNT(*) as cnt
          FROM (
            SELECT DISTINCT a.backlog_id
            FROM (
              SELECT a.backlog_id, a.plugin_id , a.plugin_sid, ki.id as kid, ki.name as kingdom, ca.name as category, ta.subcategory, a.event_id
              FROM alarm a
              $join_alarm
              $sql_where_alarm
              GROUP BY similar
            ) a

            $ctx_join
            $sql_join
            $sql_where
          ) as tmp";

        $_SESSION["_alarm_perms_from"]  = "FROM alarm a $join_alarm $ctx_join $sql_join";

        if($sql_where_alarm != ""){
            $_SESSION["_alarm_perms_where"] = "$sql_where"." AND ".preg_replace("/^WHERE/","", $sql_where_alarm, 1);
        }
        else{
            $_SESSION["_alarm_perms_where"] = "$sql_where";
        }

        // Total rows
        $total = $conn->Execute($sql_count);

        if (!$total->EOF) {
            $num_rows = $total->fields["cnt"];
        }
        else{
            $num_rows = 0;
        }

        $_SESSION["_alarm_count"] = $num_rows;

        if($num_rows == 0){
            return array(
                $list,
                0
            );
        }

        $found_rows_accumulate = 0;
        if ($nevents > 0){
            $rows_to_return = $nevents;
        }
        else {
            $rows_to_return = $num_rows;
        }

        $chunk = 2000;

        $start_at = $inf==NULL?0:$inf;

        do {
            $perform_sql = str_replace("START_AT",$start_at, $sql );
            $perform_sql = str_replace("CHUNK_SIZE",$chunk, $perform_sql );

            if (file_exists("/tmp/debug_alarm"))
            {
                file_put_contents("/tmp/alarms", "ALARM:$perform_sql\n", FILE_APPEND);
            }

            if ($cache) { // Cache query and do not use SQL_CALC_FOUND_ROWS
                $rs = $conn->CacheExecute($perform_sql);
            }
            else { // Need exact alarm number, no use cache and use SQL_CALC_FOUND_ROWS
                $rs = $conn->Execute($perform_sql);
            }

            if (!$rs) {
                Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
            }

            $found_rows = $rs->recordCount();
            $found_rows_accumulate += $found_rows;
            $start_at += $chunk;

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
                    "csimilar"     => $rs->fields["csimilar"],
                    "removable"    => $rs->fields["removable"],
                    "stats"        => $rs->fields["stats"],
                    "taxonomy"     => ($rs->fields["plugin_id"] != 1505) ? array() : array (
                        "id"           => $rs->fields["kid"],
                        "kingdom"      => $rs->fields["kingdom"],
                        "category"     => $rs->fields["category"],
                        "subcategory"  => $rs->fields["subcategory"]
                    )
                );

                $list[] = new Alarm($conn, $cache, $new);

                $rs->MoveNext();
            }

            //looking in chunks till:
            //  * we have found all elements we have to show
            //  * we have looked into all the alarms
        }while( $found_rows_accumulate < $rows_to_return && $start_at  <= $total_alarms );

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
    public static function get_max_byfield($conn, $field, $from_date = "", $to_date = "", $host_id = "", $net_id = "", $ip_cidr = "", $ctx = "")
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
        $net_arr  = ($net_id  != "") ? explode(',',$net_id)  : array();
        $hosts    = Session::get_host_where_intersect($host_arr);
        $nets     = Session::get_net_where_intersect($net_arr);

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
        $where[]   = "a.backlog_id=b.id AND b.timestamp<>'1970-01-01 00:00:00'";
        $sql_where = "WHERE " . implode(" AND ", $where);

        $query     = ossim_query("SELECT HEX(a.backlog_id) as backlog_id,a.$field FROM alarm a $ctx_join $event_join , backlog b $sql_where ORDER BY a.$field DESC LIMIT 1");
        //echo $query;

        $rs = $conn->Execute($query);

        if (!$rs)
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
    public static function get_events($conn, $backlog_id, $show_all, $event_id, $from, $max, $alarms_numbering, $cache = FALSE)
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
    public static function get_alarms_numbering($conn, $backlog_id)
    {
        Ossim_db::check_connection($conn);

        $numbering = array();
        $count = 1;
        $query = "SELECT *,hex(backlog_event.event_id) as event_id FROM backlog_event, event
                WHERE backlog_event.backlog_id = unhex(?)
                AND event.id = backlog_event.event_id
                AND event.plugin_id = 1505 ORDER BY event.id DESC";

        $rs = $conn->Execute($query, array($backlog_id));

        if (!$rs)
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
    public static function get_total_events($conn, $backlog_id, $cache = FALSE)
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
    public static function get_intents($conn, $cache = TRUE)
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
    public static function get_strategies($conn, $cache = TRUE)
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
    public static function get_alarm_graph_by_taxonomy($conn, $cache = TRUE)
    {
        Ossim_db::check_connection($conn);

        $days = array();
        $graph = array();

        if (empty($_SESSION["_alarm_perms_from"]) || empty($_SESSION["_alarm_perms_where"])) {
            return $graph;
        }

        $tz_mysql = Util::get_tzc();
        $tz = Util::get_timezone();

        $_from_date = $_SESSION["_alarm_from_date"];
        $_to_date   = $_SESSION["_alarm_to_date"];

        if (!empty($_from_date) && !empty($_to_date)) {
            //Convert date range to UTC
            $_from_date_utc = Util::get_utc_from_date($conn, "$_from_date 00:00:00", $tz);
            $_to_date_utc = Util::get_utc_from_date($conn, "$_to_date 23:59:59", $tz);

            $days["min"] = $_from_date_utc[6];
            $days["max"] = $_to_date_utc[6];

        } elseif (!empty($_from_date)) {
            //Convert $_from_date to UTC
            $_from_date_utc = Util::get_utc_from_date($conn, "$_from_date 00:00:00", $tz);
            $days["min"] = $_from_date_utc[6];

            //Get $_to_date
            $_to_date_utc = Util::get_utc_from_date($conn, gmdate("Y-m-d 23:59:59"), $tz);
            $days["max"] = $_to_date_utc[6];

            if (!empty($days["max"])) {
                $diff = gmdate("U", strtotime($days["max"]. " GMT")) - gmdate("U", strtotime($days["min"]. " GMT"));

                // Min Date > Max Date
                if ($diff < 0){
                    $_to_date_utc = Util::get_utc_from_date($conn, "$_from_date 23:59:59", $tz);
                    $days["max"] = $_to_date_utc[6];
                }
            }
        } elseif (!empty($_to_date)) {
            //Convert $_to_date to UTC
            $_to_date_utc = Util::get_utc_from_date($conn, "$_to_date 23:59:59", $tz);
            $days["max"] = $_to_date_utc[6];

            //Get $_from_date
            $sql = "SELECT min(a.timestamp) as min ".$_SESSION['_alarm_perms_from'] . ' ' . $_SESSION['_alarm_perms_where'] . ' AND a.plugin_id=1505';
            $rs = ($cache) ? $conn->CacheExecute($sql) : $conn->Execute($sql);

            if ($rs && !$rs->EOF) {
                if (!empty($rs->fields["min"])) {
                    $days["min"] = $rs->fields["min"];
                }
            }

            $diff = gmdate("U", strtotime($days["max"]. " GMT")) - gmdate("U", strtotime($days["min"]. " GMT"));

            // Min Date > Max Date
            if ($diff < 0) {
                $_from_date_utc = Util::get_utc_from_date($conn, gmdate("Y-m-d h:i:s", strtotime("$_to_date 00:00:00 -30 days")), $tz);
                $days["min"] = $_from_date_utc[6];
            }
        }
        else{
            //Convert $_from_date and $_to_date in UTC
            $sql = "SELECT min(a.timestamp) as min, max(a.timestamp) as max ".$_SESSION['_alarm_perms_from'] . ' ' . $_SESSION['_alarm_perms_where'] . ' AND a.plugin_id=1505';

            $rs = ($cache) ? $conn->CacheExecute($sql) : $conn->Execute($sql);

            if ($rs && !$rs->EOF) {
                if (!empty($rs->fields["min"]) && !empty($rs->fields["max"])) {
                    $days["min"] = $rs->fields["min"];
                    $days["max"] = $rs->fields["max"];
                }
            }
        }

        //Set default range if max or min is empty
        if (empty($days["min"]) || empty($days["max"])) {
            $start_date = gmdate("Y-m-d 00:00:00", strtotime("-30 days"));
            $end_date = gmdate("Y-m-d 23:59:59");

            $_from_date_utc = Util::get_utc_from_date($conn, $start_date, $tz);
            $_to_date_utc = Util::get_utc_from_date($conn, $end_date, $tz);

            $days["min"] = $_from_date_utc[6];
            $days["max"] = $_to_date_utc[6];
        }

        $diff = gmdate("U", strtotime($days["max"]. " GMT")) - gmdate("U",strtotime($days["min"]. " GMT"));
        $days["diff"] = intval($diff / 86400) + (($diff % 86400 > 0) ? 1 : 0);


        //Query parameters
        $params = array($days["min"], $days["max"]);


        //Transform date range to local time
        $days['min'] = Util::get_date_from_utc($days['min']);
        $days['max'] = Util::get_date_from_utc($days['max']);


        // [0] graph data, [1] tooltip data, [2] days diff
        $graph = array(array(), array(), $days);

        // Get data
        if ($days["diff"] <= 31) {
            // Hour range - 6 points/day - every 4 hours
            $sql = "SELECT count(distinct a.backlog_id) AS occurrences,
                hour(convert_tz(a.timestamp,'+00:00','$tz_mysql')) as hour,
                date(convert_tz(a.timestamp,'+00:00','$tz_mysql')) as day,
                ta.kingdom,
                ta.category "
                . $_SESSION['_alarm_perms_from'] . ' ' . $_SESSION['_alarm_perms_where'] . "
                AND a.plugin_id=1505
                AND a.timestamp BETWEEN ? AND ?
                GROUP BY ta.kingdom,ta.category,day,hour";

            $rs = ($cache) ? $conn->CacheExecute($sql, $params) : $conn->Execute($sql, $params);

            if (!$rs) {
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
        elseif ($days["diff"] < 365) {
            // Day range - 1 points/day
            $sql = "SELECT count(distinct a.backlog_id) AS occurrences,
                date(convert_tz(a.timestamp,'+00:00','$tz_mysql')) as day,
                ta.kingdom,
                ta.category "
                .$_SESSION['_alarm_perms_from'] .' '. $_SESSION['_alarm_perms_where'] . "
                AND a.plugin_id=1505
                AND a.timestamp BETWEEN ? AND ?
                GROUP BY ta.kingdom,ta.category,day";

            $rs = ($cache) ? $conn->CacheExecute($sql, $params) : $conn->Execute($sql, $params);

            if (!$rs) {
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
        else {
            // Year range - 1 points/month
            $sql = "SELECT count(distinct a.backlog_id) AS occurrences,
                month(convert_tz(a.timestamp,'+00:00','$tz_mysql')) as m,
                year(convert_tz(a.timestamp,'+00:00','$tz_mysql')) as y,
                ta.kingdom,
                ta.category "
                .$_SESSION["_alarm_perms_from"].' '.$_SESSION["_alarm_perms_where"]."
                AND a.plugin_id=1505
                AND a.timestamp BETWEEN ? AND ?
                GROUP BY ta.kingdom,ta.category,y,m";

            $rs = ($cache) ? $conn->CacheExecute($sql, $params) : $conn->Execute($sql, $params);

            if (!$rs) {
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
    public static function get_alarm_resume($conn, $backlog_id, $clevel, $cache = FALSE)
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
    public static function open($conn, $backlog_id)
    {
        Ossim_db::check_connection($conn);

        $sql = "UPDATE alarm SET status = 'open' WHERE backlog_id = unhex(?)";
        $params = array(
            $backlog_id
        );
        if ($conn->Execute($sql, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        Util::memcacheFlush(FALSE);

        $infolog = array(
            $backlog_id
        );
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
    public static function close($conn, $backlog_id)
    {
        Ossim_db::check_connection($conn);

        $sql = "UPDATE alarm SET status = 'closed'  WHERE backlog_id = unhex(?)";
        $params = array(
            $backlog_id
        );
        if ($conn->Execute($sql, $params) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        Util::memcacheFlush(FALSE);

        $infolog = array(
            $backlog_id
        );
        Log_action::log(12, $infolog);
    }


    /**
     * This function returns a file name contains sql sentences that marks as 'closed' all alarms
     *
     * @return string
     */
    public static function close_all($conn,$alarm_backlog_ids)
    {
        Ossim_db::check_connection($conn);

        $sql = "UPDATE alarm SET status = 'closed' WHERE status = 'open' AND removable = 1 AND  hex(backlog_id) IN ($alarm_backlog_ids)";

        if ($conn->Execute($sql) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        static::log_all($alarm_backlog_ids, "close");

        Util::memcacheFlush(FALSE);

    }

    public static function open_all($conn,$alarm_backlog_ids)
    {
        Ossim_db::check_connection($conn);

        $sql = "UPDATE alarm SET status = 'open'  WHERE status = 'closed' AND  hex(backlog_id) IN ($alarm_backlog_ids)";

        if ($conn->Execute($sql) === FALSE)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        static::log_all($alarm_backlog_ids, "open");

        Util::memcacheFlush(FALSE);

    }

    /**
     * Log action over a group of alarms. It will include a timestamp
     * @param string   $alarm_backlog_ids         list of alarm_id to be deleted with format 'id_1','id_2'
     * @param string   $action   two options allowed "open" or "close"
     *
     */
    private static function log_all($alarm_backlog_ids, $action){
        $now = date("Y-m-d H:i:s");
        foreach (explode(",", $alarm_backlog_ids) as $item) {
            $alarm_id = str_replace(" ", "", str_replace("'", "", $item));
            Log_action::log(13, [ "$action ($now $alarm_id)"]);
        }
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
    static public function delete($conn, $event_id)
    {
        // DEPRECATED?
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
    public static function delete_backlog($conn, $backlog_id)
    {
        Ossim_db::check_connection($conn);

        if (!self::delete_allowed($conn, $backlog_id))
        {
            return FALSE;
        }

        /* Delete all events related with $backlog_id */
        $tmptable = Util::create_tmp_table($conn,"event_id binary(16) NOT NULL, PRIMARY KEY ( event_id )");
        // Insert event_ids with more that 1 backlog
        $conn->Execute("INSERT IGNORE INTO $tmptable SELECT event_id FROM backlog_event group by event_id having count(*) > 1");
        // Insert event_ids with backlog and alarm
        $conn->Execute("INSERT IGNORE INTO $tmptable SELECT DISTINCT a.event_id FROM alarm a,backlog_event b WHERE a.event_id=b.event_id AND b.backlog_id<>a.backlog_id");
        $sql = "DELETE e FROM event e LEFT JOIN $tmptable t ON t.event_id=e.id, backlog_event b WHERE b.backlog_id = unhex(?) AND b.event_id = e.id AND t.event_id IS NULL";
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
    public static function delete_all_backlog($conn, $conds = array())
    {
        if (Session::is_pro())
        {
            $query = Alarm_delete::delete_all_backlog($conds);
        }
        else
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
        }

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

        $rs = $conn->Execute("SELECT backlog_id FROM alarm WHERE backlog_id = unhex(?) AND removable=1",array($backlog_id));

        if ($rs)
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

        $rs = $conn->Execute("SELECT count(*) FROM event e, backlog_event b WHERE b.backlog_id = unhex(?) AND b.event_id = e.id",array($backlog_id));

        if ($rs)
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

        $rs = $conn->Execute($sql,array($backlog_id));

        if ($rs)
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
    public static function clean_orphans($conn, $exec = TRUE)
    {

        Ossim_db::check_connection($conn);

        if ($exec)
        {
            $conn->Execute("DELETE tg FROM component_tags tg LEFT JOIN alarm a ON tg.id_component = a.backlog_id, tag ta WHERE ta.id=tg.id_tag AND ta.type='alarm' AND a.backlog_id IS NULL");
            $conn->Execute("DELETE ac FROM alarm_ctxs ac LEFT JOIN alarm a ON ac.id_alarm = a.backlog_id WHERE a.backlog_id IS NULL");
            $conn->Execute("DELETE ah FROM alarm_hosts ah LEFT JOIN alarm a ON ah.id_alarm = a.backlog_id WHERE a.backlog_id IS NULL");
            $conn->Execute("DELETE an FROM alarm_nets an LEFT JOIN alarm a ON an.id_alarm = a.backlog_id WHERE a.backlog_id IS NULL");
            $conn->Execute("DELETE idm FROM idm_data idm LEFT JOIN event e ON idm.event_id = e.id WHERE e.id IS NULL");
            $conn->Execute("DELETE otx FROM otx_data otx LEFT JOIN event e ON otx.event_id = e.id WHERE e.id IS NULL");
            $conn->Execute("DELETE ed FROM extra_data ed LEFT JOIN event e ON ed.event_id = e.id WHERE e.id IS NULL");
            Util::memcacheFlush(FALSE);

            return '';
        }
        else
        {
            $query  = "\n";
            $query .= "DELETE tg FROM component_tags tg LEFT JOIN alarm a ON tg.id_component = a.backlog_id, tag ta WHERE ta.id=tg.id_tag AND ta.type='alarm' AND a.backlog_id IS NULL; \n";
            $query .= "DELETE ac FROM alarm_ctxs ac LEFT JOIN alarm a ON ac.id_alarm = a.backlog_id WHERE a.backlog_id IS NULL; \n";
            $query .= "DELETE ah FROM alarm_hosts ah LEFT JOIN alarm a ON ah.id_alarm = a.backlog_id WHERE a.backlog_id IS NULL; \n";
            $query .= "DELETE an FROM alarm_nets an LEFT JOIN alarm a ON an.id_alarm = a.backlog_id WHERE a.backlog_id IS NULL; \n";
            $query .= "DELETE idm FROM idm_data idm LEFT JOIN event e ON idm.event_id = e.id WHERE e.id IS NULL; \n";
            $query .= "DELETE otx FROM otx_data otx LEFT JOIN event e ON otx.event_id = e.id WHERE e.id IS NULL; \n";
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
    public static function event_allowed($conn, $ctx, $src_host, $dst_host, $src_net, $dst_net)
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
            $sql = "SELECT filename,username,userdata1,userdata2,userdata3,userdata4,userdata5,userdata6,userdata7,userdata8,userdata9 FROM event WHERE id=UNHEX(?)";

            $rs = $conn->Execute($sql, array($this->event_id));

            if (!$rs)
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
    public static function get_event($conn, $event_id)
    {
        Ossim_db::check_connection($conn);

        /* get main events id */
        $query = ossim_query("SELECT *, HEX(agent_ctx) AS agent_ctx, HEX(src_host) AS src_host, HEX(dst_host) AS dst_host, HEX(src_net) AS src_net, HEX(dst_net) as dst_net FROM event WHERE id=unhex('$event_id')");

        $rs = $conn->Execute($query);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        elseif (!$rs->EOF)
        {
            return $rs->fields;
        }

        return array();
    }

    public static function get_alarm_detail($conn, $backlog_id) {
        $alarm = self::get_alarm_event_detail($conn, " backlog_id = unhex(?) ", array($backlog_id));
        $event = self::get_event_alarm($conn,$alarm);
        return array($alarm,$event);
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
    private static function get_alarm_event_detail($conn, $where, $params)
    {
        Ossim_db::check_connection($conn);

        // Context filter
        $ctxs = Session::get_ctx_where();
        if ($ctxs != "")
        {
            $where .= " AND corr_engine_ctx in ($ctxs)";
        }

        $query  = "SELECT *, HEX(event_id) as event_id, HEX(backlog_id) as backlog_id, HEX(corr_engine_ctx) as corr_engine_ctx, ki.id as kid, ki.name as kingdom, ca.name as category, ta.subcategory from alarm
             LEFT JOIN ( alarm_taxonomy ta LEFT JOIN alarm_kingdoms ki ON ta.kingdom=ki.id LEFT JOIN alarm_categories ca ON ta.category=ca.id ) ON alarm.plugin_sid=ta.sid AND alarm.corr_engine_ctx=ta.engine_id
             WHERE $where";
        $rs = $conn->Execute($query, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        elseif (!$rs->EOF)
        {
            $new = array (
                "event_id"     => $rs->fields["event_id"],
                "backlog_id"   => $rs->fields["backlog_id"],
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
                "stats"        => $rs->fields["stats"],
                "taxonomy"     => ($rs->fields["plugin_id"] != 1505) ? array() : array (
                    "id"           => $rs->fields["kid"],
                    "kingdom"      => $rs->fields["kingdom"],
                    "category"     => $rs->fields["category"],
                    "subcategory"  => $rs->fields["subcategory"]
                )
            );

            $alarm = new Alarm($conn, TRUE, $new);

        }
        return $alarm;
    }

    public static function get_event_alarm($conn,$alarm) {
        $event  = array();
        $ev_id  = (is_object($alarm)) ? $alarm->get_event_id() : '';
        $query  = "SELECT *, HEX(agent_ctx) as ctx, HEX(src_host) AS src_host, HEX(dst_host)
                        AS dst_host, HEX(src_net) AS src_net, HEX(dst_net) AS dst_net
                    FROM event
                    WHERE id = UNHEX(?)
                ";
        $params = array($ev_id);

        $rs = $conn->Execute($query, $params);

        if (!$rs)
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

        return $event;

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
    public static function get_alarm_trend($conn, $plugin_id, $plugin_sid, $limit=15)
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

        $rs = $conn->Execute($sql, $params);

        if (!$rs)
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
    public static function get_alarm_name ($taxonomy, $icon = TRUE)
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
    public static function transform_alarm_name($conn, $name)
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

            $rs = $conn->Execute($sql, $params);
            if (!$rs)
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
    public static function clone_taxonomy($conn, $engine_id)
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
    public static function delete_from_taxonomy($conn, $engine_id)
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

    public static function get_highest_risk_by_asset($conn, $params)
    {
        Ossim_db::check_connection($conn);

        $hide_closed = isset($params['hide_closed']) ? intval($params['hide_closed']) : 1;
        $host        = $params['host'];
        $net         = $params['net'];
        $host_group  = $params['host_group'];
        $ctx         = $params['ctx'];

        $where  = array();

        /* hide closed */
        if ($hide_closed == 1)
        {
            $where[] = "a.status = 'open' ";
        }

        list($ctx_join, $perms_where) = self::make_perms_where($ctx);

        if (!empty($perms_where)) {
            $where[] = $perms_where;
        }
        // Asset filter
        if (security_class::valid_hex32($host)){
            $asset_id = $host;
            $asset_type = 'host';
            $asset_table = 'alarm_hosts';
        } else if (security_class::valid_hex32($net)){
            $asset_id = $net;
            $asset_type = 'net';
            $asset_table = 'alarm_nets';
        } else if (security_class::valid_hex32($host_group)){
            $asset_id = $host_group;
            $asset_type = 'host_group';
            $asset_table = 'alarm_hosts';
        } else {
            $asset_id = '';
            $asset_type = '';
            $asset_table = '';
        }

        if (!empty($asset_id)){
            list($asset_ctx_join, $asset_where) = self::make_asset_where($conn, $asset_id, $asset_type);

            if (!preg_match("/$asset_table/", $ctx_join)) {
                $ctx_join = $asset_ctx_join." ".$ctx_join;
            }

            if (!empty($asset_where)) {
                $where[] = $asset_where;
            }
        }

        $sql_where = count($where) ? "WHERE " . implode(" AND ", $where) : "WHERE 1=1";

        $sql = "SELECT max(risk) as max_risk FROM alarm a $ctx_join $sql_where";

        $rs  = $conn->Execute($sql);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        return intval ($rs->fields['max_risk']);
    }


    /**
     * This function executes the query to otx_data db table and gets unique pulse ids
     *
     * @param object   $conn        Database access object
     *
     * @return array Pulse ID list
     */
    public static function get_unique_pulses($conn)
    {
        $sql = "SELECT DISTINCT HEX(pulse_id) AS pulse FROM otx_data";

        $list = array();

        if (!$rs = $conn->Execute($sql))
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF)
            {
                $list[$rs->fields['pulse']] = $rs->fields['pulse'];
                $rs->MoveNext();
            }
        }

        return $list;
    }


    /**
     * This function gets the opened ports of an alarm for a given IP.
     *
     * @param object   $conn    Database access object
     * @param string   $params  Params to filter: Backlog_id, IP, source/dst, order and limit.
     * @param string   $cache   Wheter or not cache the query
     *
     * @throws Exception  If a connection error occurred
     *
     * @return array
     */
    public static function get_alarm_port_by_ip($conn, $params, $cache = FALSE)
    {
        $backlog_id = $params['backlog_id'];
        $ip         = $params['ip'];
        $type       = ($params['source'] == 'src') ? 'src' : 'dst';
        $limit      = $params['limit'];
        $order      = $params['order_by'];

        $extra_sql  = '';
        $r_sql      = '';

        if ($order)
        {
            $extra_sql .= " ORDER BY $order ";
        }
        if ($limit)
        {
            $r_sql      = 'SQL_CALC_FOUND_ROWS e.'. $type .'_port, ';
            $extra_sql .= " LIMIT $limit ";
        }

        $sql    = 'SELECT DISTINCT '. $r_sql .' e.'. $type .'_port AS port, p.service FROM backlog_event b, event e
                    LEFT JOIN port p on p.ctx=UNHEX(?) AND p.port_number = e.'. $type .'_port
                    WHERE b.backlog_id = UNHEX(?) AND b.event_id = e.id AND e.'. $type .'_ip = INET6_ATON(?)' . $extra_sql;
        $params = array(Session::get_default_ctx(), $backlog_id, $ip);

        $rs = ($cache) ? $conn->CacheExecute($sql, $params) : $conn->Execute($sql, $params);

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if ($limit)
        {
            $total = Ossim_db::get_found_rows($conn, $sql, $cache);
        }
        else
        {
            $total = $rs->RecordCount();
        }

        $result = array();
        while (!$rs->EOF)
        {
            $result[] = array(
                'port'    => $rs->fields["port"],
                'service' => $rs->fields["service"]
            );

            $rs->MoveNext();
        }

        return array($total, $result);
    }


    /**
     * This function gets the pulses for a given alarm.
     *
     * @param object   $conn        Database access object
     * @param string   $backlog_id  Backlog ID.
     * @param boolean  $cache       Whether or not cache the query
     *
     * @throws Exception  If a connection error occurred
     *
     * @return array
     */
    public static function get_alarm_pulses($conn, $backlog_id, $cache = FALSE)
    {
        $pulse_list = array();

        list($p_join, $p_where) = self::make_perms_where();

        $p_where = ($p_where) ? ' AND ' . $p_where : '';

        $sql = "SELECT left(filename,24) as pulse_id FROM event WHERE id = (SELECT a.event_id FROM alarm a $p_join WHERE a.backlog_id=UNHEX(?) $p_where LIMIT 1)";

        $rs  = ($cache) ? $conn->CacheExecute($sql, array($backlog_id)) : $conn->Execute($sql, array($backlog_id));
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            $p_id  = $rs->fields['pulse_id'];

            try
            {
                $otx        = Otx::factory();
                $pulse_data = $otx->get_pulse_detail($p_id, TRUE); //Set hide_iocs to true because we only want the name and descr of the pulse.
            }
            catch(Exception $e)
            {
                $rs->MoveNext();
                continue;
            }

            $pulse_list[$p_id] = array(
                'id'    => $p_id,
                'name'  => $pulse_data['name'],
                'descr' => $pulse_data['description'],
            );

            $rs->MoveNext();
        }

        return $pulse_list;
    }


    /**
     * This function gets the pulse info of a given pulse id from an alarm.
     *
     * @param object   $conn        Database access object
     * @param string   $backlog_id  Backlog ID.
     * @param string   $pulse_id    Pulse ID.
     * @param boolean  $cache       Whether or not cache the query
     *
     * @throws Exception  If a connection error occurred
     *
     * @return array
     */
    public static function get_pulse_data_from_alarm($conn, $backlog_id, $pulse_id, $cache = FALSE)
    {
        $pulse = array();

        list($p_join, $p_where) = self::make_perms_where();
        $p_where = ($p_where) ? ' AND ' . $p_where : '';

        $params  = array($backlog_id, $pulse_id);

        $sql = "SELECT DISTINCT o.ioc_hash, o.ioc_value
                FROM backlog_event be, otx_data o, alarm a $p_join
                WHERE a.backlog_id=UNHEX(?) AND a.backlog_id=be.backlog_id
                AND be.event_id=o.event_id AND o.pulse_id=UNHEX(?) $p_where ";

        $rs  = ($cache) ? $conn->CacheExecute($sql, $params) : $conn->Execute($sql, $params);
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        try
        {
            $otx    = new Otx();
            $p_data = $otx->get_pulse_detail($pulse_id);
        }
        catch(Exception $e)
        {
            return $pulse;
        }

        $indicators = array();
        while (!$rs->EOF)
        {
            $ioc_h = $rs->fields['ioc_hash'];
            $ioc_v = $rs->fields['ioc_value'];

            $indicators[$ioc_h] = array(
                'hash'  => $ioc_h,
                'type'  => $p_data['indicators'][$ioc_h]['type'],
                'value' => $ioc_v
            );

            $rs->MoveNext();
        }

        $pulse = array(
            'name'  => $p_data['name'],
            'descr' => $p_data['description'],
            'iocs'  => $indicators
        );

        return $pulse;
    }


    /**
     * This function gets the reputation for a given alarm.
     *
     * @param object   $conn        Database access object
     * @param string   $backlog_id  Backlog ID.
     * @param boolean  $cache       Whether or not cache the query
     *
     * @throws Exception  If a connection error occurred
     *
     * @return array
     */
    public static function get_alarm_reputation($conn, $backlog_id, $cache = FALSE)
    {
        $rep_list = array();

        list($p_join, $p_where) = self::make_perms_where();
        $p_where = ($p_where) ? ' AND ' . $p_where : '';

        $params  = array($backlog_id);

        $sql = "SELECT DISTINCT INET6_NTOA(e.src_ip) AS src_ip, e.rep_prio_src, e.rep_rel_src, e.rep_act_src, INET6_NTOA(e.dst_ip) AS dst_ip, e.rep_prio_dst, e.rep_rel_dst, e.rep_act_dst
            FROM alarm a
            JOIN backlog_event be ON a.backlog_id=be.backlog_id
            JOIN event e ON be.event_id = e.id
            $p_join
            WHERE a.backlog_id=UNHEX(?) AND (e.rep_prio_src > 0 OR e.rep_prio_dst > 0)
            $p_where;";

        $rs  = ($cache) ? $conn->CacheExecute($sql, $params) : $conn->Execute($sql, $params);
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        while (!$rs->EOF)
        {
            if ($rs->fields['rep_prio_src'] > 0)
            {
                $rep_list[] = array(
                    'origin'      => _('Source'),
                    'value'       => $rs->fields['src_ip'],
                    'activity'    => str_replace(';', ', ',$rs->fields['rep_act_src']),
                    'reliability' => $rs->fields['rep_rel_src'],
                    'priority'    => $rs->fields['rep_prio_src'],
                );
            }
            elseif ($rs->fields['rep_prio_dst'] > 0)
            {

                $rep_list[] = array(
                    'origin'      => _('Destination'),
                    'value'       => $rs->fields['dst_ip'],
                    'activity'    => str_replace(';', ', ',$rs->fields['rep_act_dst']),
                    'reliability' => $rs->fields['rep_rel_dst'],
                    'priority'    => $rs->fields['rep_prio_dst'],
                );
            }

            $rs->MoveNext();
        }

        return $rep_list;
    }


    /**
     * This function gets the otx icon for an event within alarm (From table alienvault.event).
     *
     * @param object   $conn      Database access object
     * @param string   $event_id  Backlog ID.
     *
     * @throws Exception  If a connection error occurred
     *
     * @return string
     */
    public static function get_alarm_event_otx_icon($conn, $event_id)
    {
        $sql = "SELECT IF(ISNULL(o.pulse_id), 'rep', 'pulse') AS otx
                FROM event e
                LEFT JOIN otx_data o ON e.id=o.event_id
                WHERE e.id=UNHEX(?) AND (e.rep_prio_src > 0 OR e.rep_prio_dst > 0 or o.pulse_id is not null);";

        $params = array($event_id);

        $rs = $conn->Execute($sql, $params);
        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }

        if ($rs->EOF)
        {
            $icon = '';
        }
        else
        {
            $type = $rs->fields['otx'];

            switch ($type)
            {
                case 'pulse':
                    $icon = AV_PIXMAPS_DIR . '/otx_icon.png';
                    break;

                case 'rep':
                    $icon = AV_PIXMAPS_DIR . '/rep_icon.png';
                    break;

                default:
                    $icon = '';
            }
        }

        return $icon;
    }


    /**
     * This function gets the path to the alarm sections (Controllers, providers, views and templates).
     *
     * @return array
     */
    public static function get_alarm_path()
    {
        return array(
            'provider'   => AV_MAIN_PATH . '/alarm/providers/',
            'controller' => AV_MAIN_PATH . '/alarm/controllers/',
            'view'       => AV_MAIN_PATH . '/alarm/views/',
            'template'   => AV_MAIN_PATH . '/alarm/templates/'
        );
    }

}
