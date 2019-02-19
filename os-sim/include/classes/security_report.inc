<?php
/**
* security_report.inc
*
* File security_report.inc is used to:
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
* @package    ossim-framework\Reports
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


class Security_report
{
    public $ossim_conn;


    public $snort_conn;


    public $dbc;


    public function __construct()
    {
        $this->dbc = new Ossim_db();
        $this->ossim_conn = $this->dbc->connect();
        $this->snort_conn = $this->dbc->snort_connect();
    }


    public function close_conns()
    {
        if (is_object($this->dbc) && is_object($this->ossim_conn))
        {
            $this->dbc->close($this->ossim_conn);
        }

        if (is_object($this->dbc) && is_object($this->snort_conn))
        {
            $this->dbc->close($this->snort_conn);
        }
    }


    public function __destruct()
    {
        $this->close_conns();
    }


    public static function normalize_date($from_date, $to_date)
    {
        // format correction
        $from_date = preg_replace ("/(\d\d)\/(\d\d)\/(\d\d\d\d)/", "\\3-\\2-\\1", $from_date);
        $to_date   = preg_replace ("/(\d\d)\/(\d\d)\/(\d\d\d\d)/", "\\3-\\2-\\1", $to_date);

        // timezone correction

        $tz = Util::get_timezone();

        if ($tz != 0)
        {
            $from_date = gmdate("Y-m-d H:i:s", Util::get_utc_unixtime("$from_date 00:00:00") + (-3600 * $tz));
            $to_date   = gmdate("Y-m-d H:i:s", Util::get_utc_unixtime("$to_date 23:59:59") + (-3600 * $tz));
        }

        if (!preg_match("/\d+\:\d+:\d+/", $from_date))
        {
            $from_date .= " 00:00:00";
        }

        if (!preg_match("/\d+\:\d+:\d+/", $to_date))
        {
            $to_date   .= " 23:59:59";
        }

        return array($from_date, $to_date);
    }


    public function AttackHost($ip_target, $limit, $type, $from_date, $to_date, $assets = array(), $sourcetype = '', $category = 0, $subcategory = 0, $plugin_groups = NULL, $sensors = array(), $order_type = 'DESC', $plugin_id = '')
    {
        if ($order_type == '')
        {
            $order_type = 'DESC';
        }

        if ($limit == '')
        {
            $limit = 10;
        }

        if ($type == '')
        {
            $type = 'event';
        }

        $limit++;

        $list      = array();

        list ($from_date,$to_date) = $this->normalize_date($from_date, $to_date);


        // Taxonomy filters
        $plugin_list = Plugin_sid::get_all_sids($this->ossim_conn, $plugin_id, $sourcetype, $category, $subcategory, $plugin_groups);

        // Make sql query and get data
        if ($type == 'event')
        {
            $where = self::make_where($this->ossim_conn, $from_date, $to_date, $plugin_list, array('assets' => $assets, 'ctxs'=>$sensors));
        }
        else
        {
            list($ajoin,$where) = self::make_where_alarm($this->ossim_conn, $from_date, $to_date, $plugin_list, array('assets' => $assets, 'ctxs' => $sensors));
        }

        switch ($type)
        {
            case 'alarm':
                if (!strcmp($ip_target, 'ip_src'))
                {
                    $ip_target = 'src_ip';
                }

                if (!strcmp($ip_target, 'ip_dst'))
                {
                    $ip_target = 'dst_ip';
                }

                $host_target = (preg_match("/src/",$ip_target) ? "src" : "dst")."_host";

                $query = ossim_query("SELECT count(backlog_id) AS occurrences, ip, id FROM
                    ( SELECT distinct a.backlog_id, a.$ip_target as ip, hex(e.$host_target) as id
                        FROM alarm a $ajoin , event e
                        WHERE e.id=a.event_id $where
                    ) AS al
                    GROUP BY ip, id
                    HAVING occurrences > 0
                    ORDER BY occurrences $order_type
                    LIMIT $limit"
                );

            break;

            case 'event':

            default:
                //
                $host_target = (preg_match('/src/',$ip_target) ? 'src' : 'dst').'_host';
                
                $ejoin = (preg_match('/plist_[a-z]+/',$where)) ? preg_replace('/.*(plist_[a-z]+)\.id .*/',',\\1',$where) : '';
                $join  = (count($plugin_list) > 0) ? "LEFT JOIN alienvault.plugin_sid ON plugin_sid.plugin_id = acid_event.plugin_id AND plugin_sid.sid = acid_event.plugin_sid$ejoin" : '';
                
                if ($join != '' || preg_match("/idm_data/", $where))
                {
                    $query = ossim_query("SELECT count($ip_target) AS occurrences, $ip_target as ip, hex($host_target) AS id, hex(ctx) AS ctx FROM alienvault_siem.acid_event $join where 1 $where GROUP BY ip,$host_target,ctx ORDER BY occurrences $order_type LIMIT $limit");
                }
                else
                {
                    // Use po_acid_event acc table
                    $query = ossim_query("SELECT sum(cnt) AS occurrences, $ip_target as ip, hex($host_target) AS id, hex(ctx) AS ctx FROM alienvault_siem.po_acid_event as acid_event where $ip_target <> UNHEX('00000000') AND $ip_target <> UNHEX('00000000000000000000000000000000') $where GROUP BY ip,$host_target,ctx ORDER BY occurrences $order_type LIMIT $limit");
                }

        }

        $limit--;

        if (file_exists('/tmp/debug_reports'))
        {
            file_put_contents("/tmp/reports", "TOP $ip_target $type:$query\n", FILE_APPEND);
        }
        
        //echo "<b>SQL</b>: $query<br>";

        $rs = $this->ossim_conn->Execute($query);

        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $this->ossim_conn->ErrorMsg());
        }
        else
        {
            $i = 0;
            
            while (!$rs->EOF)
            {
                $ip_address = inet_ntop($rs->fields['ip']);

                if ($ip_address == '253.253.253.253')
                {
                    $rs->MoveNext();

                    continue;
                }

                $tmp_array = array(
                    $ip_address,
                    $rs->fields['occurrences'],
                    $rs->fields['id'],
                    $rs->fields['ctx']
                );

                array_push($list, $tmp_array);

                $i+= 1;
                $rs->MoveNext();

                /* check limit */
                if ($i >= $limit)
                {
                    $rs->EOF = TRUE;
                }
            }
        }

        return $list;
    }


    public function Events($limit, $type, $from_date, $to_date, $assets = array(), $sourcetype='', $category = 0, $subcategory = 0, $plugin_groups = NULL, $ips = FALSE, $sensors = array(), $order_type = 'DESC', $idm_user='', $idm_domain='')
    {
        if ($limit == '')
        {
            $limit = 10;
        }
        if ($type == '')
        {
            $type = 'event';
        }

        $list      = array();

        list ($from_date,$to_date) = $this->normalize_date($from_date,$to_date);
        
        if ($order_type == '') 
        {
            $order_type = 'DESC';
        }

        // Taxonomy filters
        $plugin_list = Plugin_sid::get_all_sids($this->ossim_conn, NULL, $sourcetype, $category, $subcategory, $plugin_groups);
        // Make sql query and get data 
        
        if ($type == 'event' || $type=='idmuser' || $type=='idmdomain') 
        {
            $where = self::make_where($this->ossim_conn, $from_date, $to_date, $plugin_list, array('assets' => $assets, 'ctxs' => $sensors), $idm_user, $idm_domain);
        } 
        else 
        {
            list($ajoin, $where) = self::make_where_alarm($this->ossim_conn, $from_date, $to_date, $plugin_list, array('assets' => $assets, 'ctxs' => $sensors));
        }
        
        switch ($type)
        {
            case 'alarm':
                $sensor_join = (preg_match("/e\.sensor/", $where)) ? ', event e' : '';

                $query = ossim_query("SELECT count(backlog_id) AS occurrences, sig_name, plugin_id, plugin_sid  FROM
                    (SELECT distinct a.backlog_id, IFNULL(CONCAT(ki.name,' &mdash; ',ca.name,' &mdash; ',ta.subcategory), plugin_sid.name) AS sig_name, a.plugin_id, a.plugin_sid
                            FROM alarm a LEFT JOIN ( alarm_taxonomy ta LEFT JOIN alarm_kingdoms ki ON ta.kingdom=ki.id LEFT JOIN alarm_categories ca ON ta.category=ca.id ) ON a.plugin_sid=ta.sid AND a.corr_engine_ctx=ta.engine_id $ajoin, plugin_sid $sensor_join 
                            WHERE plugin_sid.plugin_id = a.plugin_id AND plugin_sid.sid = a.plugin_sid $where
                    ) as al 
                    GROUP BY sig_name
                    HAVING occurrences > 0 
                    ORDER BY occurrences $order_type LIMIT $limit");

            break;

            case 'idmuser':
                $query = "SELECT SQL_CALC_FOUND_ROWS sig_name,sum(num_events) as occurrences FROM (SELECT CONCAT(idm_data.username,'@',idm_data.domain) as sig_name, COUNT(acid_event.id) as num_events FROM alienvault_siem.acid_event, alienvault_siem.idm_data WHERE acid_event.id = idm_data.event_id $where GROUP BY sig_name HAVING num_events>0 AND sig_name<>'') as u WHERE sig_name!='@' GROUP BY sig_name ORDER BY num_events $order_type LIMIT $limit";
            break;

            case 'idmdomain':
                $query = "SELECT SQL_CALC_FOUND_ROWS sig_name,sum(num_events) as occurrences FROM (SELECT idm_data.domain as sig_name, COUNT(acid_event.id) as num_events FROM alienvault_siem.acid_event, alienvault_siem.idm_data WHERE acid_event.id = idm_data.event_id $where GROUP BY sig_name HAVING num_events>0 AND sig_name<>'') as u GROUP BY sig_name ORDER BY num_events $order_type LIMIT $limit";
            break;

            case 'event':
            
            default:

                $idm_join = ($idm_user != '' || $idm_domain != '') ? 'idm_data, ' : '';
                
                $ejoin = (preg_match('/plist_[a-z]+/',$where)) ? preg_replace('/.*(plist_[a-z]+)\.id .*/',',\\1',$where) : '';
                
                if ($ips)
                {
                    $query = ($idm_join!='') ? 
                                "SELECT count(plugin_sid) AS occurrences, plugin_sid.name as sig_name, acid_event.plugin_id, acid_event.plugin_sid, ip_src, ip_dst, hex(ctx) as ctx, hex(src_host) as src_host, hex(dst_host) as dst_host 
                                FROM $idm_join alienvault_siem.acid_event 
                                LEFT JOIN alienvault.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid
                                $ejoin 
                                WHERE 1=1 AND acid_event.plugin_id<>1505 $where 
                                GROUP BY acid_event.plugin_id, acid_event.plugin_sid, ip_src, ip_dst, ctx, src_host, dst_host
                                ORDER BY occurrences
                                $order_type LIMIT $limit"
                                : 
                                "SELECT sum(acid_event.cnt) AS occurrences, plugin_sid.name as sig_name, acid_event.plugin_id, acid_event.plugin_sid, ip_src, ip_dst, hex(ctx) as ctx, hex(src_host) as src_host, hex(dst_host) as dst_host
                                FROM alienvault_siem.po_acid_event as acid_event
                                LEFT JOIN alienvault.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid
                                $ejoin
                                WHERE 1=1 AND acid_event.plugin_id <> 1505 $where 
                                GROUP BY plugin_id, plugin_sid, ip_src, ip_dst, ctx, src_host, dst_host
                                ORDER BY occurrences
                                $order_type LIMIT $limit";
                }
                else 
                {
                    $query = ($idm_join!='' ? "SELECT count(plugin_sid) AS occurrences, plugin_sid.name as sig_name, acid_event.plugin_id, acid_event.plugin_sid FROM $idm_join alienvault_siem.acid_event $ejoin" : "SELECT sum(acid_event.cnt) AS occurrences, plugin_sid.name as sig_name, acid_event.plugin_id, acid_event.plugin_sid FROM alienvault_siem.ac_acid_event as acid_event") . " LEFT JOIN alienvault.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid $ejoin 
                                WHERE 1=1 AND acid_event.plugin_id<>1505 $where GROUP BY acid_event.plugin_id, acid_event.plugin_sid
                                ORDER BY occurrences
                                $order_type LIMIT $limit";
                }
                
        }

        if (file_exists('/tmp/debug_reports'))
        {
            file_put_contents("/tmp/reports", "TOP $type:$query\n", FILE_APPEND);
        }

        $rs = $this->ossim_conn->Execute($query);

        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $this->ossim_conn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF)
            {
                $rs->fields['sig_name'] = trim($rs->fields['sig_name']);

                if ($rs->fields['plugin_id'] == '' && $rs->fields['plugin_sid'] == '' && $rs->fields['event'] != '')
                {
                    list($rs->fields['plugin_id'],$rs->fields['plugin_sid']) = explode(",", $rs->fields['event']);
                }
                
                if ($rs->fields['sig_name'] == '')
                {
                    $rs->fields['sig_name'] = Status::get_unknown_sid_name($this->ossim_conn, $rs->fields['plugin_id'], $rs->fields['plugin_sid']);
                }

                if($ips)
                {
                    $tmp_array = array(
                        $rs->fields['sig_name'],
                        $rs->fields['occurrences'],
                        inet_ntop($rs->fields['ip_src']),
                        inet_ntop($rs->fields['ip_dst']),
                        $rs->fields['ctx'],
                        $rs->fields['src_host'],
                        $rs->fields['dst_host']
                    );
                }
                else
                {
                    $tmp_array = array(
                        $rs->fields['sig_name'],
                        $rs->fields['occurrences']
                    );
                }

                array_push($list, $tmp_array);

                $rs->MoveNext();
            }
        }
        
        return $list;
    }


    public function EventsByIp($ip, $id, $ctx, $ip_type, $limit, $type, $from_date, $to_date, $assets = array(), $sourcetype = '', $category = 0, $subcategory = 0, $plugin_groups = NULL, $ips = FALSE, $sensors = array())
    {
        if ($limit == '')
        {
            $limit = 10;
        }
        if ($type == '')
        {
            $type = 'event';
        }
        $list = array();
        
        $h_query   = ($id  == '') ? " AND acid_event.".$ip_type."_host is NULL" : " AND acid_event.".$ip_type."_host=UNHEX('$id')";
        $h_query  .= ($ctx == '') ? " AND acid_event.ctx is NULL"               : " AND acid_event.ctx=UNHEX('$ctx')";
        
        list ($from_date,$to_date) = $this->normalize_date($from_date,$to_date);
        
        // Taxonomy filters
        $plugin_list = Plugin_sid::get_all_sids($this->ossim_conn, NULL, $sourcetype, $category, $subcategory, $plugin_groups);
        
        // Make sql query and get data
        if ($type == 'event')
        {
            $where = self::make_where($this->ossim_conn, $from_date, $to_date, $plugin_list, array('assets'=>$assets, 'ctxs' => $sensors));
        } 
        else
        {
            list($ajoin,$where) = self::make_where_alarm($this->ossim_conn, $from_date, $to_date, $plugin_list, array('assets'=>$assets, 'ctxs'=>$sensors));
        }

        
        switch ($type)
        {
            case 'alarm':
                $sensor_join = (preg_match("/e\.sensor/", $where)) ? ', event e' : '';

                $query = ossim_query("SELECT count(backlog_id) AS occurrences, sig_name, plugin_id, plugin_sid  FROM
                    (SELECT distinct a.backlog_id, IFNULL(CONCAT(ki.name,' &mdash; ',ca.name,' &mdash; ',ta.subcategory), plugin_sid.name) AS sig_name, a.plugin_id, a.plugin_sid
                            FROM alarm a LEFT JOIN ( alarm_taxonomy ta LEFT JOIN alarm_kingdoms ki ON ta.kingdom=ki.id LEFT JOIN alarm_categories ca ON ta.category=ca.id ) ON a.plugin_sid=ta.sid AND a.corr_engine_ctx=ta.engine_id $ajoin $sensor_join 
                            WHERE plugin_sid.plugin_id = a.plugin_id AND plugin_sid.sid = a.plugin_sid $where 
                    ) as al
                    GROUP BY sig_name
                    HAVING occurrences > 0 
                    ORDER BY occurrences LIMIT $limit");

            break;

            case 'event':
            default:
                
                $ejoin = (preg_match('/plist_[a-z]+/',$where)) ? preg_replace('/.*(plist_[a-z]+)\.id .*/',',\\1',$where) : '';
                
                $query = "SELECT sum(cnt) AS occurrences, plugin_sid.name as sig_name, acid_event.plugin_id, acid_event.plugin_sid
                            FROM alienvault_siem.po_acid_event AS acid_event LEFT JOIN alienvault.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid 
                            $ejoin
                            WHERE 1 $h_query AND acid_event.ip_$ip_type = unhex('".bin2hex(inet_pton($ip))."') $where 
                            GROUP BY acid_event.plugin_id, acid_event.plugin_sid
                            ORDER BY occurrences
                            DESC LIMIT $limit";
        }

        
        if (file_exists('/tmp/debug_reports'))
        {
            file_put_contents("/tmp/reports", ucfirst($type)."s by IP:$query\n", FILE_APPEND);
        }
        
        
        $rs = $this->ossim_conn->Execute($query);

        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $this->ossim_conn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF)
            {
                $rs->fields['sig_name'] = trim($rs->fields['sig_name']);

                if ($rs->fields['sig_name'] == '')
                {
                    $rs->fields['sig_name'] = Status::get_unknown_sid_name($this->ossim_conn, $rs->fields['plugin_id'], $rs->fields['plugin_sid']);
                }

                if($ips)
                {
                    $tmp_array = array(
                        $rs->fields['sig_name'],
                        $rs->fields['occurrences'],
                        inet_ntop($rs->fields['nip_src']),
                        inet_ntop($rs->fields['nip_dst'])
                    );
                }
                else
                {
                    $tmp_array = array(
                        $rs->fields['sig_name'],
                        $rs->fields['occurrences']
                    );
                }
                array_push($list, $tmp_array);

                $rs->MoveNext();
            }
        }

        return $list;
    }


    public function AlarmsResume($top, $from_date, $to_date, $assets = array(), $sourcetype = '', $category = 0, $subcategory = 0, $plugin_groups = NULL, $plugin_id = NULL, $sensors = array(), $type = 1) 
    {
        $list = array();
        
        list ($from_date,$to_date) = $this->normalize_date($from_date,$to_date);
        
        // Taxonomy filters
        $plugin_list = Plugin_sid::get_all_sids($this->ossim_conn, $plugin_id, $sourcetype, $category, $subcategory, $plugin_groups);
        list($ajoin,$where) = self::make_where_alarm($this->ossim_conn, $from_date, $to_date, $plugin_list, array('assets' => $assets, 'ctxs' => $sensors));

        if ($type == 1)
        {
           // List
           $query = "SELECT count(*) AS occurrences, IFNULL(CONCAT(ki.name,' &mdash; ',ca.name,' &mdash; ',ta.subcategory), plugin_sid.name) AS sig_name, a.plugin_id, a.plugin_sid, hex(a.event_id) as event_id, a.src_ip, a.dst_ip, hex(e.src_host) as src_host, hex(e.dst_host) as dst_host
                    FROM alarm a LEFT JOIN ( alarm_taxonomy ta LEFT JOIN alarm_kingdoms ki ON ta.kingdom=ki.id LEFT JOIN alarm_categories ca ON ta.category=ca.id ) ON a.plugin_sid=ta.sid AND a.corr_engine_ctx=ta.engine_id LEFT JOIN alienvault.plugin_sid ON plugin_sid.plugin_id=a.plugin_id AND plugin_sid.sid=a.plugin_sid $ajoin, event e
                    WHERE a.event_id=e.id $where 
                    GROUP BY sig_name, a.src_ip, a.dst_ip
                    ORDER BY occurrences DESC, a.plugin_id, a.plugin_sid, a.src_ip";
        }
        elseif ($type == 2)
        {
           // Pie (only occurrences by sid)
           $query = "SELECT count(*) AS occurrences, IFNULL(CONCAT(ki.name,' &mdash; ',ca.name,' &mdash; ',ta.subcategory), plugin_sid.name) AS sig_name, a.plugin_id, a.plugin_sid
                    FROM alarm a LEFT JOIN ( alarm_taxonomy ta LEFT JOIN alarm_kingdoms ki ON ta.kingdom=ki.id LEFT JOIN alarm_categories ca ON ta.category=ca.id ) ON a.plugin_sid=ta.sid AND a.corr_engine_ctx=ta.engine_id LEFT JOIN alienvault.plugin_sid ON plugin_sid.plugin_id=a.plugin_id AND plugin_sid.sid=a.plugin_sid $ajoin, event e
                    WHERE a.event_id=e.id $where 
                    GROUP BY sig_name
                    ORDER BY occurrences DESC LIMIT $top";
        }
        elseif ($type == 3)
        {
           // Map (only dst_ip)
           $query = "SELECT distinct(a.dst_ip) FROM alarm a $ajoin, event e WHERE a.event_id=e.id $where"; 
        }


        $rs = $this->ossim_conn->Execute($query);

        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $this->ossim_conn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF)
            {
                $rs->fields['sig_name'] = trim($rs->fields['sig_name']);

                if ($rs->fields['sig_name'] == '' && $type < 3)
                {
                    $rs->fields['sig_name'] = Status::get_unknown_sid_name($this->ossim_conn, $rs->fields['plugin_id'], $rs->fields['plugin_sid']);
                }

                $tmp_array = array(
                    $rs->fields['sig_name'],
                    $rs->fields['occurrences'],
                    (empty($rs->fields['src_ip']))? '' : inet_ntop($rs->fields['src_ip']),
                    (empty($rs->fields['dst_ip']))? '' : inet_ntop($rs->fields['dst_ip']),
                    $rs->fields['event_id'],
                    $rs->fields['src_host'],
                    $rs->fields['dst_host']
                );

                array_push($list, $tmp_array);

                $rs->MoveNext();
            }
        }

        return $list;
    }


    public function Ports($limit, $type, $from_date, $to_date, $assets = array(), $sourcetype ='', $category = 0, $subcategory = 0, $plugin_groups = NULL, $ips = FALSE, $sensors = array(), $order_type = 'DESC') 
    {
        if ($limit == '')
        {
            $limit = 10;
        }
        if ($type == '')
        {
            $type = 'event';
        }
        $list = array();
        
        list ($from_date,$to_date) = $this->normalize_date($from_date,$to_date);
        
        if ($order_type == '')
        {
            $order_type = 'DESC';
        }
        
        // Taxonomy filters
        $plugin_list = Plugin_sid::get_all_sids($this->ossim_conn, NULL, $sourcetype, $category, $subcategory, $plugin_groups);
        // Make sql query and get data

        if ($type == 'event')
        {
            $where = self::make_where($this->ossim_conn,$from_date,$to_date,$plugin_list,array('assets' => $assets, 'ctxs' => $sensors));
        }
        else
        {
            list($ajoin,$where) = self::make_where_alarm($this->ossim_conn, $from_date, $to_date, $plugin_list, array('assets' => $assets, 'ctxs' => $sensors));
        }

        
        switch ($type)
        {
            case 'alarm':
                $sensor_join = (preg_match("/e\.sensor/", $where)) ? ', event e' : '';

                $query = ossim_query("SELECT count(backlog_id) AS occurrences, layer4_dport FROM
                    (SELECT distinct a.backlog_id, a.dst_port as layer4_dport
                        FROM alarm a $ajoin $sensor_join
                        WHERE a.dst_port<>0 $where
                    ) as al 
                    GROUP BY layer4_dport
                    HAVING occurrences > 0 
                    ORDER BY occurrences $order_type LIMIT $limit");

            break;

            case 'event':
            default:

                $join = (count($plugin_list) > 0) ? "LEFT JOIN alienvault.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid" : '';

                $ejoin = (preg_match('/plist_[a-z]+/',$where)) ? preg_replace('/.*(plist_[a-z]+)\.id .*/',',\\1',$where) : '';
                
                if ($ips)
                {
                    $query = ossim_query("SELECT count(layer4_dport) AS occurrences, layer4_dport, ip_src, ip_dst, hex(ctx) as ctx, hex(src_host) as src_host, hex(dst_host) as dst_host
                      FROM alienvault_siem.acid_event $join $ejoin
                      WHERE 1=1 AND layer4_dport<>0 $where 
                      GROUP BY layer4_dport,ip_src,ip_dst,ctx,src_host,dst_host
                      ORDER BY occurrences $order_type LIMIT $limit");
                }
                else
                {
                    $query = ossim_query("SELECT count(layer4_dport) AS occurrences, layer4_dport
                      FROM alienvault_siem.acid_event $join $ejoin
                      WHERE 1=1 AND layer4_dport<>0 $where 
                      GROUP BY layer4_dport
                      ORDER BY occurrences $order_type LIMIT $limit");
                }
                
                
        }
        
        
        if (file_exists('/tmp/debug_reports'))
        {
            $log_message = (($order_type == "ASC") ? _("Last") : _("Top"))." $type Ports:$query\n";
            file_put_contents("/tmp/reports", $log_message, FILE_APPEND);
        }
        

        $rs = $this->ossim_conn->Execute($query);

        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $this->ossim_conn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF)
            {
                $port = $rs->fields['layer4_dport'];
                $service = 'unknown';
                $occurrences = $rs->fields['occurrences'];

                if ($port != 'NULL' and $occurrences != 0) 
                {
                    if ($port_list = Port::get_list($this->ossim_conn, "AND port_number = $port")) 
                    {
                        $service = $port_list[0]->get_service();
                    }
                    
                    if ($ips)
                    { 
                        $tmp_array = array(
                            $port,
                            $service,
                            $occurrences,
                            inet_ntop($rs->fields['ip_src']),
                            inet_ntop($rs->fields['ip_dst']),
                            $rs->fields['ctx'],
                            $rs->fields['src_host'],
                            $rs->fields['dst_host']
                        );
                    }
                    else
                    {
                        $tmp_array = array(
                            $port,
                            $service,
                            $occurrences
                        );
                    }
                    array_push($list, $tmp_array);
                }

                $rs->MoveNext();
            }
        }
        
        return $list;
    }


    // Deprecated for $type = "event"
    public function EventsByRisk($limit, $type, $from_date, $to_date, $assets = array(), $sourcetype='', $category = 0, $subcategory = 0, $plugin_groups = NULL, $sensors = array(), $order_type = 'DESC')
    {
        if ($limit == '')
        {
            $limit = 10;
        }
        
        if ($type == '')
        {
            $type = 'event';
        }   

        $list = array();

        list ($from_date,$to_date) = $this->normalize_date($from_date,$to_date);

        if ($order_type == '')
        {
            $order_type = 'DESC';
        }
        
        // Taxonomy filters
        $plugin_list = Plugin_sid::get_all_sids($this->ossim_conn, NULL, $sourcetype, $category, $subcategory, $plugin_groups);

        // Make sql query and get data
        if ($type == 'event') 
        {
            $where = self::make_where($this->ossim_conn, $from_date, $to_date, $plugin_list, array('assets' => $assets, 'ctxs' => $sensors));
        } 
        else 
        {
            list($ajoin,$where) = self::make_where_alarm($this->ossim_conn, $from_date, $to_date, $plugin_list, array('assets' => $assets, 'ctxs' => $sensors));
        }
        
        switch ($type)
        {
            case 'alarm':
                $sensor_join = (preg_match("/e\.sensor/", $where)) ? ", event e" : '';
                $query = ossim_query("SELECT a.plugin_id, a.plugin_sid, IFNULL(CONCAT(ki.name,' &mdash; ',ca.name,' &mdash; ',ta.subcategory), plugin_sid.name) AS sig_name, max(a.risk) as ossim_risk_a FROM alarm a 
                   LEFT JOIN ( alarm_taxonomy ta LEFT JOIN alarm_kingdoms ki ON ta.kingdom=ki.id LEFT JOIN alarm_categories ca ON ta.category=ca.id ) ON a.plugin_sid=ta.sid AND a.corr_engine_ctx=ta.engine_id
                  $ajoin, plugin_sid $sensor_join
                  WHERE plugin_sid.plugin_id = a.plugin_id AND plugin_sid.sid = a.plugin_sid $where 
                  GROUP BY sig_name ORDER BY ossim_risk_a $order_type LIMIT $limit");

            break;

            case 'event':
            default:
                
                $ejoin = (preg_match('/plist_[a-z]+/',$where)) ? preg_replace('/.*(plist_[a-z]+)\.id .*/',',\\1',$where) : '';
                
                $query = ossim_query("SELECT plugin_sid.name as sig_name, ossim_risk_a, acid_event.plugin_id, acid_event.plugin_sid
                  FROM alienvault_siem.acid_event LEFT JOIN alienvault.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid
                  $ejoin 
                  WHERE 1=1 $where 
                  GROUP BY acid_event.plugin_id, acid_event.plugin_sid
                  ORDER BY ossim_risk_a $order_type LIMIT $limit");
        }


        if (file_exists('/tmp/debug_reports'))
        {
            $log_message = (($order_type == "ASC") ? _("Last") : _("Top"))." ".$type."s by Risk:$query\n";
            file_put_contents("/tmp/reports", $log_message, FILE_APPEND);
        }
        
        
        $rs = $this->ossim_conn->Execute($query);

        if (!$rs)
        {
            Av_exception::write_log(Av_exception::DB_ERROR, $this->ossim_conn->ErrorMsg());
        }
        else
        {
            while (!$rs->EOF)
            {
                $rs->fields['sig_name'] = trim($rs->fields['sig_name']);
                if ($rs->fields['sig_name'] == '')
                {
                    $rs->fields['sig_name'] = Status::get_unknown_sid_name($this->ossim_conn,$rs->fields['plugin_id'],$rs->fields['plugin_sid']);
                }

                $tmp_array = array(
                    $rs->fields['sig_name'],
                    $rs->fields['ossim_risk_a']
                );

                array_push($list, $tmp_array);

                $rs->MoveNext();
            }
        }

        return $list;
    }


    public function EventsByPrio($limit, $type, $from_date, $to_date, $assets = array(), $sourcetype='', $category = 0, $subcategory = 0, $plugin_groups = NULL, $sensors = array(), $order_type = 'DESC') 
    {
        if ($limit == '')
        {
            $limit = 10;
        }

        if ($type == '')
        {
            $type = 'event';
        }

        $list = array();

        list ($from_date,$to_date) = $this->normalize_date($from_date,$to_date);
        
        if ($order_type == '') 
        {
            $order_type = 'DESC';
        }

        // Taxonomy filters
        $plugin_list = Plugin_sid::get_all_sids($this->ossim_conn, NULL, $sourcetype, $category, $subcategory, $plugin_groups);

        // Make sql query and get data
        if ($type == 'event')
        {
            $where = self::make_where($this->ossim_conn, $from_date, $to_date, $plugin_list, array('assets' => $assets, 'ctxs' => $sensors));
        }
        else
        {
            list($ajoin, $where) = self::make_where_alarm($this->ossim_conn, $from_date, $to_date, $plugin_list, array('assets' => $assets, 'ctxs' => $sensors));
        }
        
        
        if ($type == 'event')
        {
            $ejoin = (preg_match('/plist_[a-z]+/',$where)) ? preg_replace('/.*(plist_[a-z]+)\.id .*/',',\\1',$where) : '';
            
            $query = ossim_query("SELECT plugin_sid.name as sig_name, ossim_priority, acid_event.plugin_id, acid_event.plugin_sid
                FROM alienvault_siem.acid_event LEFT JOIN alienvault.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid
                $ejoin
                WHERE 1=1 $where
                GROUP BY acid_event.plugin_id, acid_event.plugin_sid
                ORDER BY ossim_priority $order_type LIMIT $limit");

            
            if (file_exists('/tmp/debug_reports'))
            {
                $log_message = (($order_type == "ASC") ? _("Last") : _("Top"))." ".$type."s by Prio:$query\n";
                file_put_contents("/tmp/reports", $log_message, FILE_APPEND);
            }
            
            
            $rs = $this->ossim_conn->Execute($query);

            if (!$rs)
            {
                Av_exception::write_log(Av_exception::DB_ERROR, $this->ossim_conn->ErrorMsg());
            }
            else
            {
                while (!$rs->EOF)
                {
                    $rs->fields['sig_name'] = trim($rs->fields['sig_name']);
                    
                    if ($rs->fields['sig_name'] == '')
                    {
                        $rs->fields['sig_name'] = Status::get_unknown_sid_name($this->ossim_conn, $rs->fields['plugin_id'], $rs->fields['plugin_sid']);
                    }

                    $tmp_array = array(
                        $rs->fields['sig_name'],
                        $rs->fields['ossim_priority']
                    );

                    array_push($list, $tmp_array);

                    $rs->MoveNext();
                }
            }
        }

        return $list;
    }


    public function PromiscuousHost($limit, $from_date, $to_date, $assets = array(), $sourcetype = '', $category = 0, $subcategory = 0, $plugin_groups = NULL, $sensors = array(), $order_type = 'DESC')
    {
        if ($limit == '')
        {
            $limit = 10;
        }
        $list = array();

        list ($from_date,$to_date) = $this->normalize_date($from_date,$to_date);

        if ($order_type == '')
        {
            $order_type = 'DESC';
        }

        // Taxonomy filters
        $plugin_list = Plugin_sid::get_all_sids($this->ossim_conn, NULL, $sourcetype, $category, $subcategory, $plugin_groups);
        // Make sql query and get data 
        $where = self::make_where($this->ossim_conn, $from_date, $to_date, $plugin_list, array('assets' => $assets, 'ctxs' => $sensors));

        $join = (count($plugin_list) > 0) ? "LEFT JOIN alienvault.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid" : '';
        
        $ejoin = (preg_match('/plist_[a-z]+/',$where)) ? preg_replace('/.*(plist_[a-z]+)\.id .*/',',\\1',$where) : '';

        $query = ossim_query("SELECT COUNT(DISTINCT(ip_dst)) AS occurrences, ip_src AS ip, HEX(ctx) AS ctx, HEX(src_host) AS src_host
            FROM alienvault_siem.po_acid_event as acid_event $join $ejoin
            WHERE 1=1 $where
            GROUP BY ip, ctx, src_host
            ORDER BY occurrences $order_type LIMIT $limit");

        
        if (file_exists('/tmp/debug_reports'))
        {
            $log_message = (($order_type == "ASC") ? _("Last") : _("Top"))." Promiscuous Host:$query\n";
            file_put_contents("/tmp/reports", $log_message, FILE_APPEND);
        }
        
        
        $rs = $this->ossim_conn->Execute($query);

        if (is_object($rs) && $rs != NULL)
        {
            $i = 0;

            while (!$rs->EOF)
            {
                // sensor filter in query

                $ip_address = inet_ntop($rs->fields['ip']);
                $tmp_array = array(
                    $ip_address,
                    $rs->fields['occurrences'],
                    $rs->fields['src_host'],
                    $rs->fields['ctx']
                );

                array_push($list, $tmp_array);

                $i += 1;

                $rs->MoveNext();

                /* check limit */
                if ($i >= $limit)
                {
                    $rs->EOF = TRUE;
                }
            }
        }

        return $list;
    }


    public function MultipleEventsHost($limit, $from_date, $to_date, $assets = array(), $sourcetype='', $category = 0, $subcategory = 0, $plugin_groups = NULL, $sensors = array(), $order_type = 'DESC')
    {
        if ($limit == '')
        {
            $limit = 10;
        }

        $list = array();

        list ($from_date,$to_date) = $this->normalize_date($from_date, $to_date);

        if ($order_type == '')
        {
            $order_type = 'DESC';
        }

        // Taxonomy filters
        $plugin_list = Plugin_sid::get_all_sids($this->ossim_conn, NULL, $sourcetype, $category, $subcategory, $plugin_groups);

        // Make sql query and get data
        $where = self::make_where($this->ossim_conn, $from_date, $to_date, $plugin_list, array('assets' => $assets, 'ctxs' => $sensors));

        $join = (count($plugin_list) > 0) ? "LEFT JOIN alienvault.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid" : '';
        
        $ejoin = (preg_match('/plist_[a-z]+/',$where)) ? preg_replace('/.*(plist_[a-z]+)\.id .*/',',\\1',$where) : '';
        
        $query = ossim_query("select count(distinct acid_event.plugin_id,acid_event.plugin_sid) as occurrences,ip_src as ip,hex(ctx) as ctx, hex(src_host) as src_host FROM alienvault_siem.po_acid_event as acid_event $join $ejoin
            WHERE 1=1 $where
            GROUP BY ip,ctx, src_host 
            ORDER BY occurrences $order_type
            LIMIT $limit");

        
        if (file_exists('/tmp/debug_reports'))
        {
            $log_message = (($order_type == "ASC") ? _("Last") : _("Top"))." Multiple Events Hosts:$query\n";
            file_put_contents("/tmp/reports", $log_message, FILE_APPEND);
        }
        
        
        $rs = $this->ossim_conn->Execute($query);

        if (is_object($rs) && $rs != NULL)
        {
            $i = 0;
            
            while (!$rs->EOF)
            {
                // sensor filter in query

                $ip_address = inet_ntop($rs->fields['ip']);
                
                $tmp_array = array(
                    $ip_address,
                    $rs->fields['occurrences'],
                    $rs->fields['src_host'],
                    $rs->fields['ctx']
                );

                array_push($list, $tmp_array);

                $i += 1;

                $rs->MoveNext();

                /* check limit */
                if ($i >= $limit)
                {
                    $rs->EOF = TRUE;
                }
            }
        }

        return $list;
    }


    public function AttackerCountries($geoloc, $limit, $from_date, $to_date, $ip, $id, $assets = array(), $sourcetype='', $category = 0, $subcategory = 0, $plugin_groups = NULL, $ctxs = array()) 
    {
        if ($limit == '')
        {
            $limit = 10;
        }

        // Taxonomy filters
        $plugin_list = Plugin_sid::get_all_sids($this->ossim_conn, NULL, $sourcetype, $category, $subcategory, $plugin_groups);

        // Make sql query and get data
        $where       = self::make_where($this->ossim_conn, $from_date, $to_date, $plugin_list, array('assets' => $assets, 'ctxs' => $ctxs));

        $total     = 0;
        $list      = array();
        $list_ret  = array();
        $cnames    = array();
        $counts    = array();

        $join      = (count($plugin_list) > 0) ? "LEFT JOIN alienvault.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid" : '';
        $ejoin     = (preg_match('/plist_[a-z]+/',$where)) ? preg_replace('/.*(plist_[a-z]+)\.id .*/',',\\1',$where) : '';
        
        $h_query   = ($id == '' || $id == '00000000000000000000000000000000') ? " AND acid_event.dst_host=0x00000000000000000000000000000000" : " AND acid_event.dst_host=UNHEX('$id')";
        $query     = "SELECT ip_src, sum(cnt) as occurrences 
            FROM alienvault_siem.po_acid_event as acid_event $join $ejoin
            where 1=1 $h_query AND ip_dst=unhex('".bin2hex(inet_pton($ip))."') $where 
            GROUP BY ip_src 
            ORDER BY occurrences DESC";

        
        if (file_exists('/tmp/debug_reports'))
        {
            file_put_contents("/tmp/reports", "Top Attacker Countries:$query\n", FILE_APPEND);
        }
        
        
        $rs = $this->ossim_conn->Execute($query);

        if ($rs)
        {
            while (!$rs->EOF && count($list) < $limit)
            {
                $ip_src         = inet_ntop($rs->fields['ip_src']);
                $country        = $geoloc->get_country_by_host($this->ossim_conn, $ip_src);
                $s_country      = strtolower($country[0]);
                $s_country_name = $country[1];

                $list[$s_country]  += $rs->fields['occurrences'];
                $cnames[$s_country] = ($s_country_name != '') ? $s_country_name : _('Unknown Country');
                $counts[$s_country]++;

                $total += $rs->fields['occurrences'];

                $rs->MoveNext();
            }
        }

        arsort($list);

        // Percents
        foreach ($list as $c => $val)
        {
            $list_ret[$c]['perc']  = number_format($val*100/$total,2);
            $list_ret[$c]['val']   = $val;
            $list_ret[$c]['name']  = $cnames[$c];
            $list_ret[$c]['ips']   = $counts[$c];
            $list_ret[$c]['width'] = intval(($val*150)/$total)+1;
        }

        return $list_ret;
    }


    public static function Truncate($str, $length = 10, $trailing = '...')
    {
        // take off chars for the trailing
        $length-= strlen($trailing);
        if (strlen($str) > $length) 
        {
            // string exceeded length, truncate and add trailing dots
            return substr($str, 0, $length) . $trailing;
        } 
        else 
        {
            // string was already short enough, return the string
            $res = $str;
        }
        
        return $res;
    }


    // make common where clause
    // Called from SIEM/List.php SIEM/TopEvents.php Various/EventsBy.php Various/DataSourceEvents.php Various/Geographic.php
    // Calls are in Various/general.php lib, in all methods of security_report.inc
    public static function make_where($conn, $date_from, $date_to, $plugin_list, $dDB, $idm_user = '', $idm_domain = '', $withdate = TRUE)
    {
        $hosts = (is_array($dDB['assets']['host'])) ? array_keys($dDB['assets']['host']) : array();
        $nets  = (is_array($dDB['assets']['net']))  ? array_keys($dDB['assets']['net'])  : array();

        $ctxs  = array();

        if (is_array($dDB['ctxs']) )
        {
            $ctxs = array_keys($dDB['ctxs']);
        }
        elseif (is_array($dDB['ctx']))
        {
            $ctxs = array_keys($dDB['ctx']); // compatible with ctxs and ctx keys
        }

        // make siem query
        $ctx_where    = '';
        $plugins      = array();
        $sids         = FALSE;

        // Plugin_sid::get_all_sids array structure
        $tmpname = 'plist_'.str_shuffle('abcdefghijklmnopqrstuvwxyz');
        $conn->Execute("CREATE TEMPORARY TABLE $tmpname (id int(11) NOT NULL, sid int(11) NOT NULL, PRIMARY KEY (`id`,`sid`)) ENGINE=MEMORY");

        foreach ($plugin_list as $plugin_id => $sid_list)
        {
            $sid_arr = array();

            foreach ($sid_list as $sid => $val)
            {
                $sid_arr[] = $sid;
            }

            if (count($sid_arr) == 1 && $sid_arr[0] == 0)
            {
                $plugins[] = "acid_event.plugin_id=".$plugin_id;
            }
            else
            {
                foreach ($sid_arr as $sid)
                {
                    $conn->Execute("REPLACE INTO $tmpname VALUES (?,?)",array($plugin_id,$sid));
                    $sids = TRUE;
                }
            }
        }

        if ($sids)
        {
            //$plugins[] = "((acid_event.plugin_id,acid_event.plugin_sid) in (select id,sid from $tmpname))";
            $plugins[] = "(acid_event.plugin_id=$tmpname.id AND acid_event.plugin_sid=$tmpname.sid)";
        }

        $plugin_where = (count($plugins)>0) ? 'AND ('.implode(' OR ',$plugins).')' : '';

        // CTX's filter
        $ctxs      = Session::get_ctx_where_intersect($ctxs);
        $ctx_where = '';

        if ($ctxs != '')
        {
            $ctx_where = " AND acid_event.ctx in ($ctxs)";
        }

        // Asset filter
        $hosts = Session::get_host_where_intersect($hosts);
        $nets  = Session::get_net_where_intersect($nets);
        
        if ($hosts != '')
        {
            $ctx_where .= " AND (acid_event.src_host in ($hosts) OR acid_event.dst_host in ($hosts)";

            if ($nets != '')
            {
                $ctx_where .= " OR acid_event.src_net in ($nets) OR acid_event.dst_net in ($nets))";
            }
            else
            {
                $ctx_where .= ')';
            }
        }
        elseif ($nets != '')
        {
            $ctx_where .= " AND (acid_event.src_net in ($nets) OR acid_event.dst_net in ($nets))";
        }

        // date filter
        if (!preg_match("/\d+\:\d+:\d+/",$date_from) && !preg_match("/\d+\:\d+:\d+/",$date_to)) 
        {
            list ($date_from,$date_to) = self::normalize_date($date_from,$date_to);
        }

        // idm filter
        $idm_filter = '';

        if ($idm_user != '')
        {
            $idm_filter .= " AND acid_event.id = idm_data.event_id AND idm_data.username='".str_replace('\\','\\\\',str_replace("'","\'",$idm_user))."'";
        }
        
        if ($idm_domain != '') 
        {
            $idm_filter .= " AND acid_event.id = idm_data.event_id AND idm_data.domain='".str_replace('\\','\\\\',str_replace("'","\'",$idm_domain))."'";
        }

        if($withdate)
        {
            $where = "$plugin_where $ctx_where $idm_filter AND (timestamp BETWEEN '".escape_sql($date_from, $conn)."' AND '".escape_sql($date_to, $conn)."')";
        }
        else
        {
            $where = "$plugin_where $ctx_where $idm_filter ";
        }

        return $where;
    }

    // Check where and query first to ac_acid_event
    // Called from SIEM/List.php
    public static function check_where($conn, &$where, $ejoin)
    {
        if (preg_match("/idm_data/", $where))
        {
            return FALSE;
        }
        
        $sql = "SELECT min(timestamp) as mindate, max(timestamp) as maxdate FROM alienvault_siem.ac_acid_event acid_event $ejoin WHERE 1=1 $where HAVING mindate is not null and maxdate is not null";

        if (file_exists('/tmp/debug_siem'))
        {
            file_put_contents("/tmp/siem", "CHKWHERE:$sql\n", FILE_APPEND);
        }

        $rs  = $conn->Execute($sql);
        if (!$rs)
        {
            print $conn->ErrorMsg();
        } 
        else 
        {
            if (!$rs->EOF) 
            {
                $dates = $rs->fields;
                $where = preg_replace("/BETWEEN '\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d'/", "BETWEEN '".$dates['mindate']."'", $where);
                $where = preg_replace("/AND '\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d'/", "AND '".preg_replace("/:\d\d:\d\d$/",':59:59',$dates['maxdate'])."'", $where);
                return TRUE;
            }
        }
        return FALSE;
    }

    // Same function for alarms queries (Events, Attack... with $type='alarm')
    // a is alarm table, e is event table
    public static function make_where_alarm($conn, $date_from, $date_to, $plugin_list, $dDB) 
    {
        $hosts = (is_array($dDB['assets']['host'])) ? array_keys($dDB['assets']['host']) : array();
        $nets  = (is_array($dDB['assets']['net']))  ? array_keys($dDB['assets']['net'])  : array();
        
        $ctxs  = array();
        
        if (is_array($dDB['ctxs']))
        {
            $ctxs = array_keys($dDB['ctxs']);
        }
        elseif (is_array($dDB['ctx']))
        {
            $ctxs = array_keys($dDB['ctx']); // compatible with ctxs and ctx keys
        }

        // make siem query
        $plugins      = array();
        $sids         = FALSE;

        // Plugin_sid::get_all_sids array structure
        $tmpname = 'plist_'.str_shuffle('abcdefghijklmnopqrstuvwxyz');
        $conn->Execute("CREATE TEMPORARY TABLE $tmpname (id int(11) NOT NULL, sid int(11) NOT NULL, PRIMARY KEY (`id`,`sid`)) ENGINE=MEMORY");

        foreach ($plugin_list as $plugin_id=>$sid_list)
        {
            $sid_arr = array();

            foreach ($sid_list as $sid => $val)
            {
                $sid_arr[] = $sid;
            }
            
            if (count($sid_arr) == 1 && $sid_arr[0] == 0)
            {
                $plugins[] = "a.plugin_id=".$plugin_id;
            }
            else
            {
                foreach ($sid_arr as $sid)
                {
                    $conn->Execute("REPLACE INTO $tmpname VALUES (?,?)", array($plugin_id,$sid));
                    $sids = TRUE;
                }
            }
        }

        if ($sids)
        {
            $plugins[] = "((a.plugin_id, a.plugin_sid) in (select id, sid from $tmpname))";
        }

        $plugin_where = (count($plugins)>0) ? 'AND ('.implode(' OR ',$plugins).')' : '';

        // Asset filter
        $ctx_where = '';
        $join      = '';
        $hosts     = Session::get_host_where_intersect($hosts);
        $nets      = Session::get_net_where_intersect($nets);

        if ($hosts != '' && !Session::only_ff_host())
        {
            if ($nets != '' && !Session::only_ff_net())
            {
                $ctx_where .= " AND (ah.id_host in ($hosts) OR an.id_net in ($nets))";
                $join      .= " LEFT JOIN alarm_hosts ah ON a.backlog_id=ah.id_alarm LEFT JOIN alarm_nets an ON a.backlog_id=an.id_alarm";
            }
            else
            {
                $ctx_where .= " AND ah.id_host in ($hosts)";
                $join      .= " LEFT JOIN alarm_hosts ah ON a.backlog_id=ah.id_alarm";
            }
        }
        elseif ($nets != '' && !Session::only_ff_net())
        {
            $ctx_where .= " AND an.id_net in ($nets)";
            $join      .= " LEFT JOIN alarm_nets an ON a.backlog_id=an.id_alarm";
        }

        // CTX's filter
        $ctxs = Session::get_ctx_where_intersect($ctxs);
        if ($ctxs != '')
        {
            $ctx_where .= " AND a.backlog_id=ac.id_alarm AND ac.id_ctx in ($ctxs)";
            $join      .= " ,alarm_ctxs ac";
        }
        
        $join .= ', backlog b';

        // date filter
        if (!preg_match("/\d+\:\d+:\d+/",$date_from) && !preg_match("/\d+\:\d+:\d+/",$date_to)) 
        {
            list ($date_from,$date_to) = self::normalize_date($date_from,$date_to);
        }

        $where = " AND a.backlog_id=b.id AND b.timestamp<>'1970-01-01 00:00:00' $ctx_where $plugin_where AND (a.timestamp BETWEEN '".escape_sql($date_from, $conn)."' AND '".escape_sql($date_to, $conn)."')";

        return array($join, $where);
    }
}

/* End of file security_report.inc */
/* Location: ../include/classes/security_report.inc */
