<?php
/**
* alarm_groups.inc
*
* File alarm_groups.inc is used to:
*   - To manage alarm groups
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
* - get_group_id()
* - get_ocurrences()
* - get_event_id()
* - get_backlog_id()
* - get_timestamp()
* - get_status()
* - get_since()
* - get_plugin_id()
* - get_plugin_sid()
* - get_protocol()
* - get_src_ip()
* - get_dst_ip()
* - get_src_port()
* - get_dst_port()
* - get_risk()
* - get_asset_src()
* - get_asset_dst()
* - get_rule_level()
* - get_sensors()
* - get_snort_sid()
* - get_snort_cid()
* - get_alarm()
* - get_owner()
* - get_descr()
* - get_count()
* - __get_max_sensor()
* - __get_sensors()
* - __get_since()
* - take_alarm()
* - close()
* - open()
* - change_descr()
* - get_group()
* - get_group_from_alarm()
* - ungroup_alarm()
* - group_alarm()
* - delete_group()
* - delete_groups()
* - update_group_timestamp()
* - create_new_group()
* - remove_alarm_from_group()
* - add_alarm2group()
* - get_list()
* - get_list_of_group()
* - get_list2()
* - get_events()
* - get_alarm_stats()
* - delete_day()
* - delete()
* - delete_from_backlog()
* - purge()
* - get_group_status()
*/

class Alarm_groups 
{
    public $group_id;
    
    public $ocurrences;
    
    public $event_id;
    
    public $backlog_id;
    
    public $timestamp;
    
    public $status;
    
    public $since;
    
    public $plugin_id;
    
    public $plugin_sid;
    
    public $protocol;
    
    public $src_ip;
    
    public $dst_ip;
    
    public $src_port;
    
    public $dst_port;
    
    public $risk;
    
    public $sensors = array();
    
    public $snort_sid;
    
    public $snort_cid;
    
    public $rule_level;
    
    public $alarm;
    
    public $owner;
    
    public $descr;
    
    /*
    | id        | bigint(20)            | NO   | PRI | NULL              | auto_increment |
    | status    | enum('open','closed') | YES  |     | open              |                |
    | timestamp | timestamp             | NO   |     | CURRENT_TIMESTAMP |                |
    | owner     | varchar(64)           | YES  |     | NULL              |                |
    | descr
    */
    function __construct($group_id, $ocurrences, $event_id, $backlog_id, $timestamp, $status, $since, $plugin_id, $plugin_sid, 
        $protocol, $src_ip, $dst_ip, $src_port, $dst_port, $risk, $sensors, $snort_sid, $snort_cid, $owner, $descr, $rule_level = "", 
        $alarm = 1) 
    {
        $this->group_id   = intval($group_id);
        $this->ocurrences = intval($ocurrences);
        $this->event_id   = intval($event_id);
        $this->backlog_id = intval($backlog_id);
        $this->timestamp  = $timestamp;
        $this->status     = $status;
        $this->since      = $since;
        $this->plugin_id  = intval($plugin_id);
        $this->plugin_sid = intval($plugin_sid);
        $this->protocol   = $protocol;
        $this->src_ip     = $src_ip;
        $this->dst_ip     = $dst_ip;
        $this->src_port   = intval($src_port);
        $this->dst_port   = intval($dst_port);
        $this->risk       = intval($risk);
        $this->snort_sid  = intval($snort_sid);
        $this->snort_cid  = intval($snort_cid);
        $this->rule_level = intval($rule_level);
        $this->alarm      = $alarm;
        $this->owner      = $owner;
        $this->descr      = $descr;
        
        if (is_array($sensors)) 
        {
            $this->sensors = $sensors;
        }
        else 
        {
            $this->sensors = array($sensors);
        }
    }
    
    
    function get_group_id() 
    {
        return $this->group_id;
    }
    
    
    function get_ocurrences() 
    {
        return $this->ocurrences;
    }
    
    
    function get_event_id() 
    {
        return $this->event_id;
    }
    
    
    function get_backlog_id() 
    {
        return $this->backlog_id;
    }
    
    
    function get_timestamp() 
    {
        return $this->timestamp;
    }
    
    
    function get_status() 
    {
        return $this->status;
    }
    
    
    function get_since() 
    {
        return $this->since;
    }
    
    
    function get_plugin_id() 
    {
        return $this->plugin_id;
    }
    
    
    function get_plugin_sid() 
    {
        return $this->plugin_sid;
    }
    
    
    function get_protocol() 
    {
        return $this->protocol;
    }
    
    
    function get_src_ip() 
    {
        return $this->src_ip;
    }
    
    
    function get_dst_ip() 
    {
        return $this->dst_ip;
    }
    
    
    function get_src_port() 
    {
        return $this->src_port;
    }
    
    
    function get_dst_port() 
    {
        return $this->dst_port;
    }
    
    
    function get_risk() 
    {
        return $this->risk;
    }
    
    
    function get_asset_src() 
    {
        return $this->asset_src;
    }
    
    
    function get_asset_dst() 
    {
        return $this->asset_dst;
    }
    
    
    function get_rule_level() 
    {
        return $this->rule_level;
    }
    
    
    function get_sensors() 
    {
        return $this->sensors;
    }
    
    
    function get_snort_sid() 
    {
        return $this->snort_sid;
    }
    
    
    function get_snort_cid() 
    {
        return $this->snort_cid;
    }
    
    
    function get_alarm() 
    {
        return $this->alarm;
    }
    
    
    function get_owner() 
    {
        return $this->owner;
    }
    
    
    function get_descr() 
    {
        return $this->descr;
    }
    
    
    /* deprecated */
    //static function get_grouped_alarms($conn, $group_type, $show_options, $hide_closed, $from_date, $to_date, $ip_src="", $ip_dst="",
    //   $sensor="", $query="", $directive_id="", $intent = 0, $num_events="", $num_events_op="", $tag="", $limit="LIMIT 0,10", $cache=false)
    static function get_grouped_alarms($conn, $params, $cache = FALSE)
    {
        $group_type    = $params['group_type'];
        $show_options  = $params['show_options'];
        $hide_closed   = $params['hide_closed'];
        $from_date     = $params['from_date'];
        $to_date       = $params['to_date'];
        $ip_src        = $params['ip_src'];
        $ip_dst        = $params['ip_dst'];
        $asset_group   = $params['asset_group'];
        $sensor        = $params['sensor'];
        $query         = $params['query'];
        $directive_id  = $params['directive_id'];
        $intent        = intval($params['intent']);
        $num_events    = $params['num_events'];
        $num_events_op = $params['num_events_op'];
        $min_risk      = $params['min_risk'];
        $vmax_risk     = $params['vmax_risk'];
        $tag           = $params['tag'];
        $limit         = isset($params['limit']) ? $params['limit'] : 'LIMIT 0,10';
    
        if (intval($show_options) == 0)
        {
            $show_options = 1;
        }
        if ($hide_closed =='')
        {
            $hide_closed = 0;
        }
        
        $tz  = Util::get_timezone();
        $tzc = Util::get_tzc($tz);

        $_SESSION["_alarm_perms_where"]  = "";
        $_SESSION["_alarm_perms_from"]   = "";
        $_SESSION["_alarm_sensor_where"] = "";
        $_SESSION["_alarmgroup_hash"]    = "";
        
        $_g_join_1 = 'a.similar';
        $_g_join_2 = "sha1( IFNULL(CONCAT(ki.name,' &mdash; ',ca.name,' &mdash; ',ta.subcategory), IFNULL(p.name, '')) )";
        $_g_join_3 = "CONCAT( sha1( IFNULL(CONCAT(ki.name,' &mdash; ',ca.name,' &mdash; ',ta.subcategory), IFNULL(p.name, '')) ) , DATE( convert_tz(a.timestamp,'+00:00','$tzc') ) )";
        $_g_join_4 = "CONCAT( sha1( IFNULL(CONCAT(ki.name,' &mdash; ',ca.name,' &mdash; ',ta.subcategory), IFNULL(p.name, '')) ), CONCAT( 'S', hex(a.src_ip), 'D', hex( a.dst_ip), DATE( convert_tz(a.timestamp,'+00:00','$tzc') ) ) )";

                  
        // CRITERIAS
        // Hide Closed
        $closed_where = (intval($hide_closed)==1) ? " AND a.status='open'" : "";

        // Show Options
        // Show Options

        if ($group_type == "similar") 
        {
            $_gcond = $_g_join_1;
        }
        elseif ($group_type == "name") 
        {
            $_gcond = $_g_join_2;
        } 
        elseif ($group_type == "namedate") 
        {
            $_gcond = $_g_join_3;
        } 
        else 
        {
            $_gcond = $_g_join_4;
        }

        $_SESSION["_alarmgroup_hash"] = $_gcond;


        if ($show_options == 2) 
        {             
            $groups_join  = ", alarm_groups g";
            $groups_where = " AND $_gcond=g.group_id AND g.owner='".$_SESSION['_user']."'";
        }
        elseif ($show_options == 3) 
        {
            $groups_where = " AND $_gcond NOT IN(Select group_id FROM alarm_groups WHERE owner IS NOT NULL AND owner<>'')";
        }
        elseif ($show_options == 4) 
        {
            $groups_where = " AND $_gcond NOT IN(Select group_id FROM alarm_groups WHERE owner<>'".$_SESSION['_user']."' AND owner IS NOT NULL AND owner<>'')";   
        }

        // Date
        if ($from_date != "") 
        {
            $date_where = " AND convert_tz(a.timestamp,'+00:00','$tzc') >= '$from_date 00:00:00'";
        }
        if ($to_date != "") 
        {
            $date_where .= " AND convert_tz(a.timestamp,'+00:00','$tzc') <= '$to_date 23:59:59'";
        }
        
        // IP
        $ip_where = "";
        /* ip filter */
        if (valid_ip_2($ip_src)) $ip_src.= "/32";
        if (valid_ip_2($ip_dst)) $ip_dst.= "/32";
        
        if ($ip_src) list($source_start, $source_end) = Cidr::expand_CIDR($ip_src, "SHORT", "IP");
        if ($ip_dst) list($dest_start, $dest_end)     = Cidr::expand_CIDR($ip_dst, "SHORT", "IP");

        /* If both src and dst are equal, try to find all alarms where src OR dst are equal to the IP */
        if ($ip_src && $ip_dst)
        {
            $ip_where .= " AND ((unhex('".bin2hex(inet_pton($source_start))."') <= a.src_ip AND unhex('".bin2hex(inet_pton($source_end))."') >= a.src_ip) ".($ip_src == $ip_dst ? "OR" : "AND")."
            (unhex('".bin2hex(inet_pton($dest_start))."') <= a.dst_ip AND unhex('".bin2hex(inet_pton($dest_end))."') >= a.dst_ip))";
        }
        elseif ($ip_src)
        {
            $ip_where .= ($source_start == $source_end) ? " AND unhex('".bin2hex(inet_pton($source_start))."') = a.src_ip " : " AND  (unhex('".bin2hex(inet_pton($source_start))."') <= a.src_ip AND unhex('".bin2hex(inet_pton($source_end))."') >= a.src_ip)";
        }
        elseif ($ip_dst)
        {
            $ip_where .= ($dest_start == $dest_end) ? " AND unhex('".bin2hex(inet_pton($dest_start))."') = a.dst_ip " : " AND  (unhex('".bin2hex(inet_pton($dest_start))."') <= a.dst_ip AND unhex('".bin2hex(inet_pton($dest_end))."') >= a.dst_ip)";
        }

        // Alarm Name
        $name_where = "";
        if ($query != "") 
        {
            $query      = escape_sql($query,$conn);            
            $name_where = ' AND (p.name LIKE "%'.$query.'%" or ki.name LIKE "%'.$query.'%" or ca.name LIKE "%'.$query.'%" or ta.subcategory LIKE "%'.$query.'%")';            
        }
        // Directive ID
        $directive_where = " AND a.backlog_id=b.id AND b.timestamp<>'1970-01-01 00:00:00'";
        $directive_join  = ",backlog b";
        
        if ($directive_id != "") 
        {
            $directive_where = " AND b.directive_id=$directive_id";
        }
        if ($intent > 0 ) 
        {
            $directive_where .= " AND ta.kingdom=".intval($intent);
        }
        // Num Events
        $ev_where = "";
        if ($num_events != "" && $num_events_op != "") 
        {
            if ($num_events_op == "less") $num_events_op = "<=";
            elseif ($num_events_op == "more") $num_events_op = ">=";
            else $num_events_op = "=";
            $ev_where = " AND a.backlog_id in (select backlog_id from (SELECT count(*) as total_events,backlog_event.backlog_id FROM backlog_event, event WHERE event.id = backlog_event.event_id AND event.plugin_id<>1505 GROUP BY backlog_id having total_events $num_events_op $num_events) as bk)";
        }
	//risk level
	$having = "";
        $having_params = array();
        if ($min_risk) {
            $min_risk++;
            $having_params[] = " max_risk >= $min_risk ";
        }
        if ($vmax_risk != "" && $vmax_risk !=2) {
            $vmax_risk++;
            $having_params[] = " max_risk <= $vmax_risk ";
        }
        if ($having_params) {
            $having = " HAVING ".implode(" AND ",$having_params);
        }
        // Tags
        if ($tag != "")
        {           
            $tag_join  = ",component_tags tg";
            $tag_where = " AND a.backlog_id=tg.id_component AND tg.id_tag=UNHEX('$tag')";
        }


        // Asset filter
        $ctx_join  = "";
        $ctx_where = "";
        $hosts     = Session::get_host_where();
        $nets      = Session::get_net_where();
        
        if ($hosts != "" && !Session::only_ff_host()) 
        {
            if ($nets != "" && !Session::only_ff_net()) 
            {
                $ctx_where .= " AND (ah.id_host in ($hosts) OR an.id_net in ($nets))";
                $ctx_join  .= " LEFT JOIN alarm_hosts ah ON a.backlog_id=ah.id_alarm LEFT JOIN alarm_nets an ON a.backlog_id=an.id_alarm";
            } 
            else 
            {
                $ctx_where .= " AND ah.id_host in ($hosts)";
                $ctx_join  .= " LEFT JOIN alarm_hosts ah ON a.backlog_id=ah.id_alarm";
            }
        }
        elseif ($nets != "" && !Session::only_ff_net()) 
        {
            $ctx_where .= " AND an.id_net in ($nets)";
            $ctx_join  .= " LEFT JOIN alarm_nets an ON a.backlog_id=an.id_alarm";
        }
        // CTX's filter
        $ctxs = Session::get_ctx_where();
        if ($ctxs != "") 
        {
            $ctx_where .= " AND a.backlog_id=ac.id_alarm AND ac.id_ctx in ($ctxs)";
            $ctx_join  .= " ,alarm_ctxs ac";
        }
        
        // Asset Group
        if ($asset_group != "" && valid_hex32($asset_group))
        {
            $ctx_where .= " AND ah.id_host in (SELECT host_id FROM host_group_reference WHERE host_group_id=UNHEX('$asset_group'))";
            if (!preg_match("/alarm_hosts/", $ctx_join))
            {
                $ctx_join = " LEFT JOIN alarm_hosts ah ON a.backlog_id=ah.id_alarm $ctx_join";
            }
        }
        
        // Sensor filter
        $sensor_join = "";
        $sensor_where = "";
        if ($sensor != "") 
        {
            $sensor_join = " ,event e, backlog_event be";
            $sensor_where .= " AND e.sensor_id=unhex('$sensor') AND be.event_id=e.id AND be.backlog_id=a.backlog_id";
            $_SESSION["_alarm_sensor_where"] = " AND event.sensor_id=unhex('$sensor') ";
        }   

        $_SESSION["_alarm_perms_from"]  = "FROM alarm a LEFT JOIN plugin_sid p ON a.plugin_id = p.plugin_id AND a.plugin_sid = p.sid LEFT JOIN ( alarm_taxonomy ta LEFT JOIN alarm_kingdoms ki ON ta.kingdom=ki.id LEFT JOIN alarm_categories ca ON ta.category=ca.id ) ON a.plugin_sid=ta.sid AND a.corr_engine_ctx=ta.engine_id $ctx_join $groups_join $sensor_join $directive_join $tag_join";
        $_SESSION["_alarm_perms_where"] = "WHERE 1=1 $closed_where $date_where $ip_where $name_where $directive_where $ev_where $tag_where $groups_where $ctx_where";        
        // QUERY
        // Group by similar field
        if ($group_type == "similar") 
        {
            $query = "SELECT SQL_CALC_FOUND_ROWS distinct a.similar AS group_id, IF(a.plugin_id<>1505,p.name,IFNULL(CONCAT(ki.name,' &mdash; ',ca.name,' &mdash; ',ta.subcategory), p.name)) AS name, max(a.risk) as max_risk, count(distinct a.similar) as howmany
                        FROM alarm a 
                        LEFT JOIN plugin_sid p ON a.plugin_id = p.plugin_id AND a.plugin_sid = p.sid
                        LEFT JOIN ( alarm_taxonomy ta LEFT JOIN alarm_kingdoms ki ON ta.kingdom=ki.id LEFT JOIN alarm_categories ca ON ta.category=ca.id ) ON a.plugin_sid=ta.sid AND a.corr_engine_ctx=ta.engine_id
                        $ctx_join 
                        $sensor_join $directive_join $groups_join
                        WHERE 1=1
                        $sensor_where 
                        $ctx_where 
                        $closed_where
                        $directive_where
                        $groups_where 
                        GROUP BY a.similar 
                        $having
                        ORDER BY a.timestamp DESC
                        $limit";

        // Group by name (unique)
        }
        else if ($group_type == "name") 
        {
            $query = "SELECT SQL_CALC_FOUND_ROWS count( * ) AS howmany,
            sha1( alarm_event.name ) AS group_id,
            alarm_event.name AS name,       
            max(alarm_event.risk) as max_risk
            FROM (
            (SELECT DISTINCT a.backlog_id, IF(a.plugin_id<>1505,IFNULL(p.name, ''),IFNULL(CONCAT(ki.name,' &mdash; ',ca.name,' &mdash; ',ta.subcategory), IFNULL(p.name, ''))) AS name, a.risk FROM alarm a 
            LEFT JOIN plugin_sid p ON a.plugin_id = p.plugin_id AND a.plugin_sid = p.sid
            LEFT JOIN ( alarm_taxonomy ta LEFT JOIN alarm_kingdoms ki ON ta.kingdom=ki.id LEFT JOIN alarm_categories ca ON ta.category=ca.id ) ON a.plugin_sid=ta.sid AND a.corr_engine_ctx=ta.engine_id
            $ctx_join $sensor_join $directive_join $tag_join $groups_join
            WHERE 1=1
            $closed_where
            $date_where
            $ip_where
            $name_where
            $directive_where
            $ev_where
            $groups_where
            $sensor_where
            $ctx_where
            $tag_where)
            ) as alarm_event
            GROUP BY name
            $having
            ORDER BY howmany DESC $limit";

        // Group by name, date
        } 
        elseif ($group_type == "namedate") 
        {
            $query = "SELECT SQL_CALC_FOUND_ROWS count( * ) AS howmany,
                sha1( alarm_event.name ) AS group_id,
                alarm_event.name AS name,       
                max(alarm_event.risk) as max_risk,
                DATE( alarm_event.timestamp ) AS day
                FROM (
                (SELECT DISTINCT a.backlog_id, IF(a.plugin_id<>1505,IFNULL(p.name, ''),IFNULL(CONCAT(ki.name,' &mdash; ',ca.name,' &mdash; ',ta.subcategory), IFNULL(p.name, ''))) AS name, convert_tz(a.timestamp,'+00:00','$tzc') as timestamp,a.risk FROM alarm a 
                LEFT JOIN plugin_sid p ON a.plugin_id = p.plugin_id AND a.plugin_sid = p.sid
                LEFT JOIN ( alarm_taxonomy ta LEFT JOIN alarm_kingdoms ki ON ta.kingdom=ki.id LEFT JOIN alarm_categories ca ON ta.category=ca.id ) ON a.plugin_sid=ta.sid AND a.corr_engine_ctx=ta.engine_id
                $ctx_join $sensor_join  $directive_join $tag_join $groups_join
                WHERE 1=1
                $closed_where
                $date_where
                $ip_where
                $name_where
                $directive_where
                $ev_where
                $groups_where
                $sensor_where
                $ctx_where
                $tag_where)
                ) as alarm_event
                GROUP BY name, day
                $having
                ORDER BY alarm_event.timestamp DESC $limit";

        // Group by name, ips, date (all)
        } 
        else 
        {
            $query = "SELECT SQL_CALC_FOUND_ROWS count( * ) AS howmany,
                sha1( alarm_event.name) AS group_id,
                alarm_event.name,
                inet6_ntoa( alarm_event.src_ip ) AS src_ip,
                inet6_ntoa( alarm_event.dst_ip ) AS dst_ip,
                max(alarm_event.risk) as max_risk,
                DATE( alarm_event.timestamp ) AS day
                FROM (
                (SELECT DISTINCT a.backlog_id, IF(a.plugin_id<>1505,IFNULL(p.name, ''),IFNULL(CONCAT(ki.name,' &mdash; ',ca.name,' &mdash; ',ta.subcategory), IFNULL(p.name, ''))) AS name ,a.src_ip,a.dst_ip,convert_tz(a.timestamp,'+00:00','$tzc') as timestamp,a.risk FROM alarm a 
                LEFT JOIN plugin_sid p ON a.plugin_id = p.plugin_id AND a.plugin_sid = p.sid
                LEFT JOIN ( alarm_taxonomy ta LEFT JOIN alarm_kingdoms ki ON ta.kingdom=ki.id LEFT JOIN alarm_categories ca ON ta.category=ca.id ) ON a.plugin_sid=ta.sid AND a.corr_engine_ctx=ta.engine_id
                $ctx_join $sensor_join $directive_join $tag_join $groups_join
                WHERE 1=1
                $closed_where
                $date_where
                $ip_where
                $name_where
                $directive_where
                $ev_where
                $groups_where
                $sensor_where
                $ctx_where
                $tag_where)
                ) as alarm_event
                GROUP BY name, src_ip, dst_ip, day
                $having
                ORDER BY alarm_event.timestamp DESC $limit";
        }
        
        if (file_exists("/tmp/debug_alarm"))
        {
            file_put_contents("/tmp/alarms", "ALARM GROUP:$query\n", FILE_APPEND);
        }
        
        $ret = array();
        $query = ossim_query($query);
        
        $rs = ($cache) ? $conn->CacheExecute($query) : $conn->Execute($query);
        if (!$rs) 
        {
            print $conn->ErrorMsg();
        } 
        else 
        {
            // Total rows
            $rnd = "get_grouped_alarms".md5($query);
            $total = ($cache) ? $conn->CacheExecute("SELECT FOUND_ROWS() AS $rnd") : $conn->Execute("SELECT FOUND_ROWS() AS $rnd");         
            $num_rows = 0;
            
            if (!$total->EOF) 
            {
                foreach($total->fields as $num_rows);
            }
            
            while (!$rs->EOF) 
            {
                $ret[] = array(
                    "group_id"    => $rs->fields['group_id'],
                    "group_count" => $rs->fields['howmany'],
                    "name"        => (( $rs->fields['name'] == '' ) ? _('Unknown Directive') : $rs->fields['name']),
                    "date"        => $rs->fields['day'],
                    "ip_src"      => $rs->fields['src_ip'],
                    "ip_dst"      => $rs->fields['dst_ip'],
                    "id_tag"      => $tag,
                    "max_risk"    => $rs->fields['max_risk']
                );
                $rs->MoveNext();
            }
        }
        
        return array($ret, $num_rows);
    }
    
        
    // $query parameter can be similar field or alarm name
    //static function get_alarms ($conn, $sensor, $src_ip, $dst_ip, $hide_closed, $order, $inf = null, $sup = null, $from_date = null,
    //$to_date = null, $query = "", $group_id = "", $directive_id ="", $tag = null, $cache=false, $num_events="", $num_events_op="")
    static function get_alarms ($conn, $params, $cache = FALSE)
    {
        $sensor        = $params['sensor'];
        $src_ip        = $params['src_ip'];
        $dst_ip        = $params['dst_ip'];
        $asset_group   = $params['asset_group'];
        $hide_closed   = $params['hide_closed'];
        $order         = $params['order'];
        $inf           = isset($params['inf']) ? $params['inf'] : NULL;
        $sup           = isset($params['sup']) ? $params['sup'] : NULL;
        $from_date     = isset($params['from_date']) ? $params['from_date'] : NULL;
        $to_date       = isset($params['to_date']) ? $params['to_date'] : NULL;
        $query         = $params['query'];
        $group_id      = $params['group_id'];
        $directive_id  = $params['directive_id'];
        $tag           = isset($params['tag']) ? $params['tag'] : NULL;
        $num_events    = $params['num_events'];
        $num_events_op = $params['num_events_op'];
        
        
        if ($hide_closed =='')
        {
            $hide_closed = 0;
        }   
            
        $tz  = Util::get_timezone();
        $tzc = Util::get_tzc($tz);
        
        $list = array();
        $where = array();
        // Pre-Selected Ports (Granada 27 de mayo 2009)
        $ports = array();
        
        $rsp = $conn->Execute("SELECT * FROM port");
        
        if (!$rsp)
        {
            print $conn->ErrorMsg();
            return array(array(),0);
        }
                
        while (!$rsp->EOF) 
        {
            $ports[$rsp->fields["protocol_name"]][$rsp->fields["port_number"]] = $rsp->fields["service"];
            $rsp->MoveNext();
        }
        
        /* ip filter */
        if (valid_ip_2($src_ip)) $src_ip.= "/32";
        if (valid_ip_2($dst_ip)) $dst_ip.= "/32";
        
        if ($src_ip) list($source_start, $source_end) = CIDR::expand_CIDR($src_ip, "SHORT", "IP");
        if ($dst_ip) list($dest_start, $dest_end)     = CIDR::expand_CIDR($dst_ip, "SHORT", "IP");
        
        /* If both src and dst are equal, try to find all alarms where src OR dst are equal to the IP */
        if ($src_ip && $dst_ip) 
        {
            $where[] = "((unhex('".bin2hex(inet_pton($source_start))."') <= a.src_ip AND unhex('".bin2hex(inet_pton($source_end))."') >= a.src_ip)
                        ".($src_ip == $dst_ip ? "OR" : "AND")."
            (unhex('".bin2hex(inet_pton($dest_start))."') <= a.dst_ip AND unhex('".bin2hex(inet_pton($dest_end))."') >= a.dst_ip))";
        } 
        elseif ($src_ip) 
        {
            $where[] = ($source_start == $source_end) ? " unhex('".bin2hex(inet_pton($source_start))."') = a.src_ip " : "(unhex('".bin2hex(inet_pton($source_start))."') <= a.src_ip AND unhex('".bin2hex(inet_pton($source_end))."') >= a.src_ip)";
        } 
        elseif ($dst_ip) 
        {
            $where[] = ($dest_start == $dest_end) ? " unhex('".bin2hex(inet_pton($dest_start))."') = a.dst_ip " : "(unhex('".bin2hex(inet_pton($dest_start))."') <= a.dst_ip AND unhex('".bin2hex(inet_pton($dest_end))."') >= a.dst_ip)";
        }        
        
        
        /* hide closed */
        if ($hide_closed == 1) 
        {
            $where[] = "a.status = 'open' ";
        }
        
        /* date range filter */
        if ($from_date) 
        {
            list($y, $m, $d) = preg_split('#-#', $from_date);
            
            if (!checkdate($m, $d, $y)) 
            {
                print _("Invalid date") . ": $from_date";
                return array(array(),0);
            }
            
            $where[] = "(convert_tz(a.timestamp,'+00:00','$tzc') >= '$from_date 00:00:00')";
        }
        
        if ($to_date) 
        {
            list($y, $m, $d) = preg_split('#-#', $to_date);
            
            if (!checkdate($m, $d, $y)) 
            {
                print _("Invalid date") . ": $to_date";
                return array(array(),0);
            }
            
            $where[] = "(convert_tz(a.timestamp,'+00:00','$tzc') <= '$to_date 23:59:59')";
        }
        /* number of events per page */
        $limit = "";
        
        if ($sup !== null && $inf !== null) 
        {
            $nevents = $sup - $inf;
            $limit = "LIMIT $nevents OFFSET $inf";
        }
        
        if ($query != "") 
        {
            if (preg_match("/[0-9a-f]{40}/i", $query)) // similar sha1
            {
                $where[] = '(a.similar = "'.escape_sql($query,$conn).'")';                
            }
            elseif (preg_match("/mdash/",$query)) // taxonomy
            {
                $tax     = explode(" &mdash; ",$query);
                $where[] = '(ki.name = "'.escape_sql($tax[0],$conn).'" and ca.name = "'.escape_sql($tax[1],$conn).'" and ta.subcategory = "'.escape_sql($tax[2],$conn, FALSE).'")';                
            }
            else // sid name
            {
                $where[] = '(p.name LIKE "%'.escape_sql($query,$conn).'%")';                
            }
        }
        else
        {
            $where[] = "(p.name IS NULL)";
        }

        // Asset filter
        $ctx_join = "";
        $hosts    = Session::get_host_where();
        $nets     = Session::get_net_where();
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
        if ($ctxs != "") {
            $where[]   = " a.backlog_id=ac.id_alarm AND ac.id_ctx in ($ctxs)";
            $ctx_join .= " ,alarm_ctxs ac";
        }   
        
        // Asset Group
        if ($asset_group != "" && valid_hex32($asset_group))
        {
            $where[] = " ah.id_host in (SELECT host_id FROM host_group_reference WHERE host_group_id=UNHEX('$asset_group'))";
            if (!preg_match("/alarm_hosts/", $ctx_join))
            {
                $ctx_join = " LEFT JOIN alarm_hosts ah ON a.backlog_id=ah.id_alarm $ctx_join";
            }
        }
        
        $sql_where = count($where) ? 'WHERE 1=1 AND ' . implode(" AND ", $where) : "WHERE 1=1";
        
        // Sensor filter
        
        $sensor_join = "";
        if ($sensor != "") 
        {
            $sensor_join = " ,event e, backlog_event be";
            $sql_where .= " AND e.sensor_id=unhex('$sensor') AND be.event_id=e.id AND be.backlog_id=a.backlog_id";
        } 
                    
        // Group id filter
        if( $group_id !="") 
        {
            $sql_where .=  " AND sha1(p.name) = '$group_id'";
        }

        // Do not show logical deleted alarms
        $directive_join = ",backlog b";
        $sql_where .= " AND a.backlog_id=b.id AND b.timestamp<>'1970-01-01 00:00:00'";

        // Directive ID
        if ($directive_id != "") 
        {
            $sql_where .= " AND b.directive_id=$directive_id";
        }
        
        // Tags
        $tag_join = "";
        if ($tag != "") 
        {
            $tag_join = ",component_tags tg";
            $sql_where .= " AND a.backlog_id=tg.id_component AND tg.id_tag=UNHEX('$tag')";
        }

        // Num Events        
        if ($num_events != "" && $num_events_op != "") 
        {
            if ($num_events_op == "less") $num_events_op = "<=";
            elseif ($num_events_op == "more") $num_events_op = ">=";
            else $num_events_op = "=";
            $sql_where .= " AND a.backlog_id in (select backlog_id from (SELECT count(*) as total_events,backlog_event.backlog_id FROM backlog_event, event WHERE event.id = backlog_event.event_id AND event.plugin_id<>1505 GROUP BY backlog_id having total_events $num_events_op $num_events) as bk)";
        }
        
        
        // ... ", a.timestamp + 0 AS timestamp" what is this?
        $sql = "SELECT SQL_CALC_FOUND_ROWS distinct a.*, p.name as sid_name, ki.id as kid, ki.name as kingdom, ca.name as category, ta.subcategory
                FROM alarm a
                LEFT JOIN plugin_sid p ON a.plugin_id = p.plugin_id AND a.plugin_sid = p.sid 
                LEFT JOIN ( alarm_taxonomy ta LEFT JOIN alarm_kingdoms ki ON ta.kingdom=ki.id LEFT JOIN alarm_categories ca ON ta.category=ca.id ) ON a.plugin_sid=ta.sid AND a.corr_engine_ctx=ta.engine_id
                $ctx_join $sensor_join $directive_join $tag_join $sql_where $order $limit";   // remove of select for get time in GMT (, convert_tz(a.timestamp,'+00:00','$tzc') as timestamp)
        //echo "SQL1: ".$sql."<br>";
        
        $rs = ($cache) ? $conn->CacheExecute($sql) : $conn->Execute($sql);
        if (!$rs)
        {
            print $conn->ErrorMsg();
            return array();
        }
        /*var_dump($sql);
        echo "<br/>";*/
                  
        // Total rows
        $rnd = "get_alarms".md5($sql);
        $total = ($cache) ? $conn->CacheExecute("SELECT FOUND_ROWS() AS $rnd") : $conn->Execute("SELECT FOUND_ROWS() AS $rnd");        
        $num_rows = 0;
        if (!$total->EOF) 
        {
            foreach($total->fields as $num_rows);
        }
        
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
                "tzone"        => 0,
                "similar"      => $rs->fields["similar"],
                "stats"        => $rs->fields["stats"],
                "csimilar"     => 1,
                "removable"    => $rs->fields["removable"],
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

        $total_alarms_sql  = " SELECT COUNT(*) FROM alarm a,plugin_sid p";
        $total_alarms_sql .= " WHERE a.plugin_id=p.plugin_id AND a.plugin_sid=p.sid AND sha1(p.name) = '$group_id'";
        $total_alarms      = $conn->GetOne($total_alarms_sql);
        
        if(intval($total_alarms)==0 && $group_id!="") 
        {
            self::delete_group ($conn, $group_id, $_SESSION["_user"]);
        }
     
        return array(
            $list,
            $num_rows
        );
    }


    static function delete_alarms_from_groups($conn, $data = array())
    {
        if (Session::is_pro())
        {
            $query = Alarm_delete::delete_alarms_from_groups($data);
        }
        else
        {
            Ossim_db::check_connection($conn);

            $from_alarm   = $_SESSION["_alarm_perms_from"];
    
            $from_alarm1  = $from_alarm;
            if (!preg_match("/backlog_event/",$from_alarm)) $from_alarm1 .= ",backlog_event be";
    
            $group_list   = ( empty( $data['groups'] ) )? "''" : $data['groups'];
    
            $_hash = $_SESSION["_alarmgroup_hash"];
    
            $where_alarm  = $_SESSION["_alarm_perms_where"] . " AND a.removable=1 AND $_hash IN($group_list)";
            $sensor_where = $_SESSION["_alarm_sensor_where"];
            $user         = Session::get_session_user();
    
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
    
            $query .= "DELETE b.* FROM $backlog_ids b,backlog_event be WHERE b.id = be.backlog_id AND be.event_id NOT IN (SELECT id FROM $event_ids) ; \n";
            
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
    
            $query .= "DELETE FROM alarm_groups WHERE group_id IN($group_list) AND owner='$user' ; \n";
    
            $query .= "DROP TABLE $tmptable ; \n";
            $query .= "DROP TABLE $backlog_ids ; \n";
            $query .= "DROP TABLE $event_ids ; \n";
    
            //Deleting orphans alarms
            $orphans = Alarm::clean_orphans($conn, false);
            $query  .= $orphans;
        }

        $dir    = "/tmp";
        $prefix = "delete_alarmgroup_" . Session::get_session_user() . "_";

        $file   = Util::generate_tmp_file($dir, $prefix, $query);

        $infolog = array(
            "deleted"
        );
        Log_action::log(13, $infolog);
        
        return $file;
    }
    
    
    //To do: build up this function from a scratch.
    static function get_group_status ($conn, $sensor="", $src_ip="", $dst_ip="", $from_date = null, $to_date = null, $name = "")
    {
        $tz  = Util::get_timezone();
        $tzc = Util::get_tzc($tz);
        
        $where = array();
        $st    = array();
        
        /* ip filter */
        if (valid_ip_2($src_ip)) $src_ip.= "/32";
        if (valid_ip_2($dst_ip)) $dst_ip.= "/32";
        
        if ($src_ip) list($source_start, $source_end) = CIDR::expand_CIDR($src_ip, "SHORT", "IP");
        if ($dst_ip) list($dest_start, $dest_end)     = CIDR::expand_CIDR($dst_ip, "SHORT", "IP");
        
        /* If both src and dst are equal, try to find all alarms where src OR dst are equal to the IP */
        if ($src_ip && $dst_ip) 
        {
            $where[] = "((unhex('".bin2hex(inet_pton($source_start))."') <= a.src_ip AND unhex('".bin2hex(inet_pton($source_end))."') >= a.src_ip)
                        ".($src_ip == $dst_ip ? "OR" : "AND")."
            (unhex('".bin2hex(inet_pton($dest_start))."') <= a.dst_ip AND unhex('".bin2hex(inet_pton($dest_end))."') >= a.dst_ip))";
        } 
        elseif ($src_ip) 
        {
            $where[] = ($source_start == $source_end) ? " unhex('".bin2hex(inet_pton($source_start))."') = a.src_ip " : "(unhex('".bin2hex(inet_pton($source_start))."') <= a.src_ip AND unhex('".bin2hex(inet_pton($source_end))."') >= a.src_ip)";
        } 
        elseif ($dst_ip) 
        {
            $where[] = ($dest_start == $dest_end) ? " unhex('".bin2hex(inet_pton($dest_start))."') = a.dst_ip " : "(unhex('".bin2hex(inet_pton($dest_start))."') <= a.dst_ip AND unhex('".bin2hex(inet_pton($dest_end))."') >= a.dst_ip)";
        }  
                
        /* date range filter */
        if ($from_date) 
        {
            list($y, $m, $d) = preg_split('#-#', $from_date);
            if (!checkdate($m, $d, $y)) {
                print _("Invalid date") . ": $from_date";
                return array(array(),0);
            }
            $where[] = "(convert_tz(a.timestamp,'+00:00','$tzc') >= '$from_date 00:00:00')";
        }
        
        if ($to_date) 
        {
            list($y, $m, $d) = preg_split('#-#', $to_date);
            if (!checkdate($m, $d, $y)) {
                print _("Invalid date") . ": $to_date";
                return array(array(),0);
            }
            $where[] = "(convert_tz(a.timestamp,'+00:00','$tzc') <= '$to_date 23:59:59')";
        }
        
        if ( $name != "" )
        {
            if (preg_match("/[0-9a-f]{40}/i", $name)) // similar sha1
            {
                $where[] = '(a.similar = "'.escape_sql($name,$conn).'")';
            }
            elseif (preg_match("/mdash/",$name)) // taxonomy
            {
                $tax     = explode(" &mdash; ",$name);
                $where[] = '(ki.name = "'.escape_sql($tax[0],$conn).'" and ca.name = "'.escape_sql($tax[1],$conn).'" and ta.subcategory = "'.escape_sql($tax[2],$conn).'")';                
            }
            else // sid name
            {
                $where[] = '(p.name LIKE "%'.escape_sql($name,$conn).'%")';                
            }
        }
        else
        {
            $where[] = "(p.name IS NULL)";
        }

        // Context filter
        $ctxs = Session::get_ctx_where();
        if ($ctxs != "") {
            $where[] = " a.corr_engine_ctx in ($ctxs)";
        } 
        
        $sql_where = count($where) ? 'WHERE 1=1 AND ' . implode(" AND ", $where) : "WHERE 1=1";
        
        // Sensor filter
        
        $sensor_join = "";
        if ($sensor != "") 
        {
            $sensor_join = " ,event e, backlog_event be";
            $sql_where .= " AND e.sensor_id=unhex('$sensor') AND be.event_id=e.id AND be.backlog_id=a.backlog_id";
        } 
        
        $sql = "SELECT DISTINCT a.status AS status, count(a.status) AS cont_status FROM alarm a LEFT JOIN plugin_sid p ON a.plugin_id = p.plugin_id AND a.plugin_sid = p.sid LEFT JOIN ( alarm_taxonomy ta LEFT JOIN alarm_kingdoms ki ON ta.kingdom=ki.id LEFT JOIN alarm_categories ca ON ta.category=ca.id ) ON a.plugin_sid=ta.sid AND a.corr_engine_ctx=ta.engine_id $sensor_join $sql_where GROUP BY a.status";
        
        $rs = $conn->Execute($sql);
        if (!$rs) 
        {
            print $conn->ErrorMsg();
            return "";
        }
        else
        {
            while (!$rs->EOF) 
            {
                $st[] = array("status" => $rs->fields["status"], "num_rows" => $rs->fields["cont_status"]);
                $rs->MoveNext();
            }         
            
            if (count($st) >= 2 || count($st) == 0)
            {
                return "open";
            }
            else
            {
                return $st[0]["status"];
            }
        }
    }
    
    
    static function get_dbgroups ($conn) {
        $sql = "SELECT * FROM alarm_groups";
        $ret = array();
        
        $rs = $conn->Execute($sql);
        
        if (!$rs) 
        {
            print $conn->ErrorMsg();
            return array();
        }
        else 
        {
            while (!$rs->EOF)
            {
                $ret[$rs->fields['group_id']] = array("owner"=>$rs->fields['owner'],"status"=>$rs->fields['status'],"descr"=>$rs->fields['description']);
                
                $rs->MoveNext();
            }
        }
        
        return $ret;
    }
    
    
    static function take_group ($conn, $group_id, $user)
    {
        $sql = "INSERT INTO alarm_groups (group_id,owner,description) VALUES (?,?,'') ON DUPLICATE KEY UPDATE owner=?";
        
        if (!$conn->Execute($sql,array($group_id,$user,$user))) 
        {
            print $conn->ErrorMsg();
        }
    }
    
    
    static function release_group ($conn, $group_id)
    {
        $sql = "UPDATE alarm_groups SET owner='' WHERE group_id=?";
        
        if (!$conn->Execute($sql,array($group_id))) 
        {
            print $conn->ErrorMsg();
        }
    }
    
    
    static function delete_group ($conn, $group_id, $user)
    {
        $sql = "DELETE FROM alarm_groups WHERE group_id=? AND owner=?";
        
        if (!$conn->Execute($sql,array($group_id,$user))) 
        {
            print $conn->ErrorMsg();
        }
    }
    
    
    static function change_descr ($conn, $descr, $group_id)
    {
        $sql = "UPDATE alarm_groups SET description=? WHERE group_id=?";
        
        if (!$conn->Execute($sql,array($descr,$group_id))) 
        {
            print $conn->ErrorMsg();
        }
    }
    
    
    static function change_status($groups, $status)
    {
        $groups     = (empty($groups))? "''" : $groups;
        
        $from_alarm = preg_replace("/^\s*from/i", '', $_SESSION["_alarm_perms_from"]);
        
        $_hash = $_SESSION["_alarmgroup_hash"];

        $where_alarm = preg_replace("/AND a.status='open'/", '', $_SESSION["_alarm_perms_where"]);
        $where_alarm = $_SESSION["_alarm_perms_where"] . " AND a.removable=1 AND $_hash IN ($groups)";

        $sql         = "UPDATE $from_alarm SET a.status='$status' $where_alarm ; \n";

        $groups_list = explode(',', $groups);

        foreach($groups_list as $g)
        {
            $sql .= "UPDATE alarm_groups SET status='$status' WHERE group_id=$g; \n";
        }

        $dir    = "/tmp";
        $prefix = "change_alarmgroup_state_" . Session::get_session_user() . "_";

        $file   = Util::generate_tmp_file($dir, $prefix, $sql);

        $infolog = array(
            ($status=='open') ? "opened" : "closed"
        );
        Log_action::log(13, $infolog);
        
        return $file;
    }
}

/* End of file alarm_groups.inc */
/* Location: /usr/share/ossim/include/classes/alarm_groups.inc */
