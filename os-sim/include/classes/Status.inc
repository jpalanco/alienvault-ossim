<?php
/**
* Status.inc
*
* File Status.inc is used to:
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
* @package    ossim-framework\Assets
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/

class Status 
{
	public $snort = FALSE;
	
	public $conn  = FALSE;
	
	function __construct () 
	{
		$db = new ossim_db();
        $this->conn  = $db->connect();
        $this->snort = $db->snort_connect();
	}
	
	
    public static function get_siem_ctx_filter($ctx = '')
    {
        $where    = '';
        $ctx_list = '';
        
        if (Session::get_ctx_where() != '' || $ctx != '') 
        {
            if ($ctx != '') 
            { 
                $ctx_list = (Session::get_ctx_where() != '') ? ", UNHEX('$ctx')" : "UNHEX('$ctx')"; 
            }
            
            $where = " AND acid_event.ctx IN (".Session::get_ctx_where().$ctx_list.")";
        }
        
        return $where;
    }
    
	
	function get_siem_ip_filter($ip_src, $ip_dst) 
	{
		$ip_where = '';
		$ip_where_src = '';
		$ip_where_dst = '';
		$network = 0;
		if (preg_match("/\/\d+/",$ip_src) && preg_match("/\/\d+/",$ip_dst)) 
		{
			$network = 1;
			$exp = CIDR::expand_cidr($ip_src,"SHORT","IP");
			$src_s_range = $exp[0];
			$src_e_range = bin2hex(inet_pton(end($exp)));
			$exp = CIDR::expand_cidr($ip_dst,"SHORT","IP");
			$dst_s_range = $exp[0];
			$dst_e_range = bin2hex(inet_pton(end($exp)));
			$ip_where = " AND ((acid_event.ip_src>=UNHEX('$src_s_range') AND acid_event.ip_src<=UNHEX('$src_e_range')) OR (acid_event.ip_dst>=UNHEX('$dst_s_range') AND acid_event.ip_dst<=UNHEX('$dst_e_range')))";
			$ip_where_dst = " AND (acid_event.ip_dst>=UNHEX('$dst_s_range') AND acid_event.ip_dst<=UNHEX('$dst_e_range'))";
			$ip_where_src = " AND (acid_event.ip_src>=UNHEX('$src_s_range') AND acid_event.ip_src<=UNHEX('$src_e_range'))";
		}

		// HOST Filtering
		if ($ip_src!='' && $ip_dst!='' && !$network) 
		{
			$list_src = $list_dst = array();
			
			$ip_src_arr = explode(",", $ip_src);
			foreach ($ip_src_arr as $ip_src_aux) 
			{
				$ip_src = bin2hex(@inet_pton(trim($ip_src_aux)));
				$list_src[] = "acid_event.ip_src=UNHEX('$ip_src')";
				$list_all[] = "acid_event.ip_src=UNHEX('$ip_src')";
			}
			
			$ip_dst_arr = explode(",", $ip_dst);
			foreach ($ip_dst_arr as $ip_dst_aux) 
			{
				$ip_dst = bin2hex(@inet_pton(trim($ip_dst_aux)));
				$list_dst[] = "acid_event.ip_dst=UNHEX('$ip_dst')";
				$list_all[] = "acid_event.ip_dst=UNHEX('$ip_dst')";
			}
			
			$ip_where = " AND (".implode(" OR ", $list_all).")";
			$ip_where_dst = " AND (".implode(" OR ", $list_dst).")";
			$ip_where_src = " AND (".implode(" OR ", $list_src).")";
		}
		
		return array($ip_where,$ip_where_src,$ip_where_dst);
	}
	
	
	function get_siem_asset_filter($src_host, $dst_host, $src_net, $dst_net) 
	{		
		$asset_where = '';
		$src_where   = '';
		$dst_where   = '';
		
		$src_host_arr = ($src_host != '') ? array($src_host) : array();
		$src_net_arr  = ($src_net  != '') ? array($src_net)  : array();
        $src_hosts = Session::get_host_where_intersect($src_host_arr);
        $src_nets  = Session::get_net_where_intersect($src_net_arr); // include near nets
		$dst_host_arr = ($dst_host != '') ? array($dst_host) : array();
		$dst_net_arr  = ($dst_net  != '') ? array($dst_net)  : array();
        $dst_hosts = Session::get_host_where_intersect($dst_host_arr);
        $dst_nets  = Session::get_net_where_intersect($dst_net_arr); // include near nets
		
		if ($src_hosts != '') 
		{
			$src_where = "acid_event.src_host IN ($src_hosts)";
		}
		if ($dst_hosts != '') 
		{
			$dst_where = "acid_event.dst_host IN ($dst_hosts)";
		}
		
		if ($src_nets != '') 
		{
			if ($src_where != '') $src_where = "($src_where OR acid_event.src_net IN ($src_nets))";
			else                  $src_where = "acid_event.src_net IN ($src_nets)";
		}
		
		if ($dst_nets != '') 
		{
			if ($dst_where != '') $dst_where = "($dst_where OR acid_event.src_net IN ($dst_nets))";
			else                  $dst_where = "acid_event.src_net IN ($dst_nets)";
		}
		
		if ($src_where != '' && $dst_where == '') 
		{
			$asset_where = " AND ($src_where)";
		} 
		elseif ($dst_where != '' && $src_where == '') 
		{
			$asset_where = " AND ($dst_where)";
		} 
		elseif ($dst_where != '' && $src_where != '') 
		{
			$asset_where = "AND ($src_where OR $dst_where)";
		}
		
		if ($src_where != '') { $src_where = " AND $src_where"; }
		if ($dst_where != '') { $dst_where = " AND $dst_where"; }
	
		return array($asset_where, $src_where, $dst_where);
	}    
	
	
	public static function get_siem_resume ($date_from = '', $date_to = '')
	{        		
		$dbsock = new ossim_db();
		$db     = $dbsock->snort_connect();
		
		$foundrows = 0;
		$highrisk  = "-";
		$date      = "-";
		$risknum   = 0;
		
		
		//Date filtering
		
		if($date_from != '' && $date_to != '')
		{
			$date_from  = ( preg_match("/^\d+\-\d+\-\d+$/",$date_from) ) ?  $date_from." 00:00:00" : $date_from;
			$date_to    = ( preg_match("/^\d+\-\d+\-\d+$/",$date_to) ) ?  $date_to." 23:59:59" : $date_to;
		}
		else
		{
			$date_from = strftime("%Y-%m-%d 00:00:00", time() - (24 * 60 * 60 * 7));
			$date_to   = date("Y-m-d 23:59:59", time());
		}
		// User session filtering
		$ctx_where = "";//self::get_siem_ctx_filter($ctx);
		$where = " timestamp BETWEEN '$date_from' AND '$date_to' $ctx_where ";
		$a_unions = array();
		$a_where = array();
		//list of searched keys became static because it was never refferenced
		foreach (array("src_host","src_net","dst_host","dst_net"/*,"ip_src","ip_dst"*/) as $key) {
//			if ($$key) {
				$a_unions[] = " (SELECT id,$key as asset,ossim_risk_a FROM acid_event WHERE $where AND ossim_risk_a > 0 AND $key IS NOT NULL ) ";
				$a_where[] = $key;
				$ahex_where[] = "HEX($key) as $key";
//			}
		}
		$a_unions = implode(" UNION ",$a_unions);
		$a_where2 = $a_where;
		$a_where = implode(",",$a_where);
		$ahex_where = implode(",",$ahex_where);
//highest risk format
		$sql = "SELECT HEX(c.asset) as asset,highrisk,count(*) as risk_num FROM
			(SELECT asset,max(ossim_risk_a) as highrisk
			FROM (
			SELECT asset,ossim_risk_a FROM (
				$a_unions
			) as a GROUP BY id,asset) as b
			GROUP BY asset) as c 
			JOIN acid_event as d ON d.ossim_risk_a = c.highrisk AND c.asset IN ($a_where) AND $where
			GROUP BY c.asset
		";
		$rs  = $db->Execute($sql);
		$result2 = array();
		while (!$rs->EOF) {
			$result2[$rs->fields["asset"]] = array(
				"highrisk" => $rs->fields["highrisk"],
				"risk_num" => $rs->fields["risk_num"]
			);
			$rs->MoveNext();
		}
//total
                $sql = "select count(*) as total, max(timestamp) as date, $ahex_where from acid_event
                        WHERE  $where GROUP BY $a_where";

                $rs  = $db->Execute($sql);
		$result = array();
		if ($rs) {
		        while (!$rs->EOF) {
//blocker avoids counting the same event in the same asset list twice
	                        $blocker = array();
				foreach ($a_where2 as $key) {
	                                $key = $rs->fields[$key];
        	                        if (!$key || ($key && in_array($key,$blocker))) continue;
	                                $blocker[] = $key;
					if (!isset($result[$key])) {
						$result[$key] = array(
							"total"		=> $rs->fields["total"],
							"date"		=> $rs->fields["date"],
							"highrisk"	=> isset($result2[$key]) ? $result2[$key]["highrisk"] : 0,
							"risk_num"	=> isset($result2[$key]) ? $result2[$key]["risk_num"] : 0
						);
					} else {
						$result[$key]["total"] += $rs->fields["total"];
						if ($rs->fields["date"] > $result[$key]["date"]) {
							$result[$key]["date"] = $rs->fields["date"];
						}
					}
				}
				$rs->MoveNext();
	                }
		}
		foreach ($result as $key => $value) {
			$result[$key]["date"] = preg_replace('/:00:00$/', 'H', $value["date"]);
		}
		return $result;
	}
	
	
	public static function get_siem_clouds ($date_range = NULL)
	{				
		$dbsock = new ossim_db();
		$db     = $dbsock->snort_connect();
		
		// For custom
		if($date_range != NULL)
		{
			$date_from = $date_range['date_from'];
			$date_to   = $date_range['date_to'];
			
			$date_from  = (preg_match("/^\d+\-\d+\-\d+$/",$date_from)) ?  $date_from." 00:00:00" : $date_from;
			$date_to    = (preg_match("/^\d+\-\d+\-\d+$/",$date_to)) ?  $date_to." 23:59:59" : $date_to;
						
		}
		else
		{
			$date_from = strftime("%Y-%m-%d 00:00:00", time() - (24 * 60 * 60 * 7));
			$date_to   = date("Y-m-d 23:59:59", time());
		}
		
		
		// User session filtering
		$ctx_where = "";//self::get_siem_ctx_filter($ctx);
		$where = " timestamp BETWEEN '$date_from' AND '$date_to' $ctx_where ";


		$keys = array("src_host","src_net","dst_host","dst_net","ip_src","ip_dst");
                foreach ($keys as $key) {
                        $ahex_where[] = "HEX($key) as $key";
                }
		$a_where = implode(",",$keys);
		$ahex_where = implode(",",$ahex_where);
		$query = "SELECT layer4_dport, COUNT(acid_event.id) as count_num, $ahex_where FROM acid_event 
			WHERE $where GROUP BY layer4_dport, $a_where";
		$rs = $db->Execute($query);
		$result = array();
		$keys = array_slice($keys,0,4);
		$source_arr = array("ip_src" => array(), "ip_dst" => array(), "layer4_dport" => array());
		$source_keys = array_keys($source_arr);
		while (!$rs->EOF) {
//blocker avoids counting the same event in the same asset list twice
			$blocker = array();
			foreach ($keys as $key) {
				$key = $rs->fields[$key];
				if (!$key || ($key && in_array($key,$blocker))) continue;
				$blocker[] = $key;
				if (!isset($result[$key])) {
					$result[$key] = $source_arr;
				}
				foreach ($source_keys as $key2) {
					$key2_val = $rs->fields[$key2];
					if (!isset($result[$key][$key2][$key2_val])) {
						$result[$key][$key2][$key2_val] = 0;
					}
					$result[$key][$key2][$key2_val] += $rs->fields["count_num"];
				}
			}
			$rs->MoveNext();
		}
        foreach ($result as $i => $j) {
			$result[$i]["ip_src"] = self::IPkeysToNtop($j["ip_src"]);
			$result[$i]["ip_dst"] = self::IPkeysToNtop($j["ip_dst"]);
		}
		return $result;
	}
	
	private static function IPkeysToNtop($arr) {
		if (!$arr) return;
		$res = array();
		foreach ($arr as $key => $value) {
			$res[inet_ntop(pack("H*", ($key)))] = $value;
		}
		return $res;
	}
	
	
	static function get_unknown_sid_name($db, $plugin_id, $plugin_sid)
	{
		$plugin_name = '';
		
		if (!$rs = $db->Execute("SELECT name FROM alienvault.plugin WHERE id=$plugin_id")) 
		{
            print $db->ErrorMsg();
        }
        elseif (!$rs->EOF) 
        {
        	$plugin_name = $rs->fields['name'];
        }
        
        if ($plugin_name != '' && $plugin_sid == 2000000000)
        {
            return "$plugin_name: " . _('Generic event');
        }
        elseif ($plugin_name != '' && $plugin_sid != 2000000000)
        {    
            return "$plugin_name: " . _('Unknown event');
        }
        else
        {
            return _('SigName unknown');
        }
	}
	
	
	public static  function get_siem_unique ($ip_src, $ip_dst, $date_from = '',$date_to = '',$limit = 5, $ctx = '',$host_src = '',$host_dst = '',$net_src = '',$net_dst = '')
	{		
		$dbsock = new ossim_db();
		$db     = $dbsock->snort_connect();
		$numevents = 0;
		
		//Date filtering
		
		// For custom
			
		if($date_from !='' && $date_to != '')
		{
			$date_from  = $date_range['date_from'] = ( preg_match("/^\d+\-\d+\-\d+$/",$date_from) ) ?  $date_from." 00:00:00" : $date_from;
			$date_to    = $date_range['date_to']   = ( preg_match("/^\d+\-\d+\-\d+$/",$date_to) ) ?  $date_to." 23:59:59" : $date_to;
			list($x, $y, $xticks, $xlabels, $tr, $interval)   = self::range_graphic("custom", $date_range);
		}
		else
		{
			$date_from = $date_range['date_from'] = strftime("%Y-%m-%d 00:00:00", time() - (24 * 60 * 60 * 7));
			$date_to   = $date_range['date_to']   = date("Y-m-d 23:59:59", time());
			list($x, $y, $xticks, $xlabels, $tr, $interval)  = self::range_graphic("week");
		}
        
		// User session filtering
		$ctx_where = self::get_siem_ctx_filter($ctx);
		
		// IP Filtering
		list($ip_where, $ip_where_src, $ip_where_dst) = self::get_siem_ip_filter($ip_src,$ip_dst);
		
		// Asset ID Filtering
		list($asset_where, $asset_where_src, $asset_where_dst) = self::get_siem_asset_filter($host_src,$host_dst,$net_src,$net_dst);
		
		// Unique Events
		$sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT HEX(acid_event.id) AS id,acid_event.plugin_sid,acid_event.plugin_id,acid_event.plugin_sid, count(plugin_sid) as sig_cnt, min(timestamp), max(timestamp), plugin_sid.name as sig_name, count(DISTINCT(acid_event.plugin_sid)), count(DISTINCT(ip_src)), count(DISTINCT(ip_dst)), ip_src, ip_dst FROM acid_event LEFT JOIN alienvault.plugin_sid ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid WHERE 1 $ctx_where $ip_where $asset_where AND (timestamp BETWEEN '$date_from' AND '$date_to') GROUP BY acid_event.plugin_id, acid_event.plugin_sid ORDER BY sig_cnt DESC LIMIT $limit";
		
		$events = array();
		
		if (!$rs = $db->Execute($sql))
		{  
            print $db->ErrorMsg();
        }
        else
		{
            if (!$rf = $db->Execute("SELECT FOUND_ROWS() AS total"))
            {
                print $db->ErrorMsg();
            }
            else
            { 
                $numevents = $rf->fields['total'];
            }
                
        	while (!$rs->EOF) 
			{
				$rs->fields['sig_name'] = trim($rs->fields['sig_name']);
                if ($rs->fields['sig_name']=='')
                {
                	 $rs->fields['sig_name'] = self::get_unknown_sid_name($db,$rs->fields['plugin_id'],$rs->fields['plugin_sid']);
                }
                	 
				$events[] = array("id"=>$rs->fields['id'],"plugin_id"=>$rs->fields['plugin_id'],"plugin_sid"=>$rs->fields['plugin_sid'],"sig_cnt"=>$rs->fields['sig_cnt'],"min(timestamp)"=>$rs->fields['min(timestamp)'],"max(timestamp)"=>$rs->fields['max(timestamp)'],"sig_name"=>$rs->fields['sig_name'],"ip_src"=>$rs->fields['count(DISTINCT(ip_src))'],"ip_dst"=>$rs->fields['count(DISTINCT(ip_dst))'],"num_sensors"=>$rs->fields['count(DISTINCT(acid_event.plugin_sid))'],"ip_s"=>$rs->fields['ip_src'],"ip_d"=>$rs->fields['ip_dst'],"sid"=>$rs->fields['sid'],"cid"=>$rs->fields['cid']);
				$rs->MoveNext();
            }
        }
        
		$where    = "WHERE (timestamp BETWEEN '$date_from' AND '$date_to') $ip_where AND plugin_id=PLUGINID AND plugin_sid=PLUGINSID";
		
		$sqlgraph = self::get_sql_graphic($tr, $where);
       	
		$i        = 0;
		$plots    = array();
		
		foreach ($events as $ev) 
		{
			if ($i > $limit) 
			{
				continue;
			}				
			
			$plugin_id  = $ev['plugin_id'];
			$plugin_sid = $ev['plugin_sid'];
			$sqlgr      = str_replace("PLUGINID", $plugin_id, $sqlgraph);
			$sqlgr      = str_replace("PLUGINSID", $plugin_sid, $sqlgr);
			
            //echo $sqlgr."<br/><br/>";            
            
			if (!$rs = $db->Execute($sqlgr))
			{
				print $db->ErrorMsg();
			}
			
			else 
			{                               
                foreach ($xlabels as $v)
                {
                    $data[$v] = 0;
                }
                               
                while (!$rs->EOF) 
				{
					$label = trim($rs->fields['intervalo'] . ' ' . $rs->fields['suf']);
					
					if (!isset($data[$xlabels[$label]]) || $data[$xlabels[$label]] == 0) 
					{
                        $data[$xlabels[$label]] = $rs->fields['sig_cnt'];
					}
					
					$rs->MoveNext();
				}
                                            
				$plots[$i] = array($data, $interval);
                          
				$i++;
			}
		}
        
        return array($events, $plots, $numevents);
	}
	
	
	public static function get_siem_plot($ip_src = '',$ip_dst = '',$date_from = '',$date_to = '',$ctx='',$host_src = '',$host_dst = '',$net_src = '',$net_dst = '')
	{		
		$dbsock = new ossim_db();
		$db     = $dbsock->snort_connect();

		if($date_from != '' && $date_to != '')
		{
			$date_from  = $date_range['date_from']  = ( preg_match("/^\d+\-\d+\-\d+$/",$date_from) ) ?  $date_from." 00:00:00" : $date_from;
			$date_to    = $date_range['date_to']    = ( preg_match("/^\d+\-\d+\-\d+$/",$date_to) ) ?  $date_to." 23:59:59" : $date_to;
			list($x, $y, $xticks, $xlabels, $tr, $interval) = self::range_graphic("custom", $date_range);
						
		}
		else
		{
			$date_from = $date_range['date_from'] = strftime("%Y-%m-%d 00:00:00", time() - (24 * 60 * 60 * 7));
			$date_to   = $date_range['date_to'] = date("Y-m-d 23:59:59", time());
			list($x, $y, $xticks, $xlabels, $tr, $interval) = self::range_graphic("week");
		}
          
					
		// User CTX filtering
		$ctx_where = '';
		$ctx_list = Session::get_ctx_where();
		
		if ($ctx != '') 
		{ 
		    $ctx_list .= ($ctx_list != '') ? ", UNHEX('$ctx')" : "UNHEX('$ctx')"; 
		}
		
		if ($ctx_list != '') 
		{
			$ctx_where = " AND acid_event.ctx in (" . $ctx_list . ")";
		}
		
		// IP Filtering
		list($ip_where,$ip_where_src,$ip_where_dst) = self::get_siem_ip_filter($ip_src,$ip_dst);
		
		// Asset ID Filtering
		list($asset_where,$asset_where_src,$asset_where_dst) = self::get_siem_asset_filter($host_src,$host_dst,$net_src,$net_dst);
		
		// General Graph
		$where    = "WHERE (timestamp BETWEEN '".$date_range['date_from']."' AND '".$date_range['date_to']."') $ctx_where $ip_where $asset_where";
		$sqlgraph = self::get_sql_graphic($tr, $where);
        	
		$gplot     = array();
		$numevents = 0;
		
        //echo $sqlgraph;
        
		if (!$rs = $db->Execute($sqlgraph))
		{ 
			print $db->ErrorMsg();
		}
		else 
		{                      
            foreach ($xlabels as $v)
            {
                $data[$v] = 0;
            }
            
            while (!$rs->EOF) 
			{
				$label = trim($rs->fields['intervalo'] . " " . $rs->fields['suf']);
				
                if (!isset($data[$xlabels[$label]]) || $data[$xlabels[$label]] == 0) 
                {
					$data[$xlabels[$label]] = $rs->fields['sig_cnt'];
				}
				
                $numevents += $rs->fields['sig_cnt'];
				$rs->MoveNext();
            }
			
			$gplot = array($data, $interval);
		}
        
		return $gplot;
	}
	
	
	function get_sql_graphic($tr, $where) 
	{		
		$tz  = Util::get_timezone();
		$tzc = Util::get_tzc($tz);
		
		switch ($tr) 
		{
			case "today":
				$interval = "hour(convert_tz(timestamp,'+00:00','$tzc')) as intervalo, 'h' as suf";
				$grpby    = " GROUP BY intervalo,suf";
			break;

			case "day2":
			case "day":
				$interval = "hour(convert_tz(timestamp,'+00:00','$tzc')) as intervalo, day(convert_tz(timestamp,'+00:00','$tzc')) as suf";
				$grpby    = " GROUP BY intervalo,suf";
			break;

			case "week":
			case "weeks":
			case "custom1":
				$interval = "day(convert_tz(timestamp,'+00:00','$tzc')) as intervalo, monthname(convert_tz(timestamp,'+00:00','$tzc')) as suf";
				$grpby    = " GROUP BY intervalo,suf ORDER BY suf,intervalo";
			break;

			case "month":
			case "custom2":
				$interval = "monthname(convert_tz(timestamp,'+00:00','$tzc')) as intervalo,  year(convert_tz(timestamp,'+00:00','$tzc')) as suf";
				$grpby    = " GROUP BY intervalo,suf ORDER BY suf,intervalo";
			break;
		}
		
		return "SELECT COUNT(plugin_sid) as sig_cnt, $interval FROM acid_event $where $grpby";
	}	
      
	
	public static function get_vul_events($conn, $host, $date_from = '', $date_to = '', $truncate = 1, $ctx = '')
	{		
		if( $host != 'any' && $host != '' )
		{
			if (preg_match("/\/(\d+)/",$host)) 
			{
				$cidrs = explode(",", $host);
				$cidr_where = array();
				foreach ($cidrs as $cidr) 
				{
					$exp = CIDR::expand_cidr($cidr,"SHORT","LONG");
					$host_s_range = $exp[0];
					$host_e_range = end($exp);
					$cidr_where[] = "(INET_ATON(hostIP) >= '$host_s_range' AND INET_ATON(hostIP) <= '$host_e_range')";
				}
				if (count($cidr_where) > 0) 
				{
					$ip_where = "AND (".implode(" OR ", $cidr_where).")";
				}
			}
			else 
			{
				$ip_where = "AND hostIP IN ('".str_replace(",", "','", $host)."')";
			}
		}
		else
		{
			$ip_where='';
		}
		
		if ($date_from != '' && $date_to != '') 
		{
			$date_from   = ( preg_match("/^\d+\-\d+\-\d+$/",$date_from) ) ?  $date_from." 000000" : $date_from;
			$date_to     = ( preg_match("/^\d+\-\d+\-\d+$/",$date_to) ) ?  $date_to." 235959" : $date_to;
			$date_filter = "WHERE scantime >= '".preg_replace("/(\s|\-|\:)/",'',$date_from)."' AND scantime <= '".preg_replace("/(\s|\-|\:)/",'',$date_to)."'";
		} 
		else 
		{
			$date_filter = "WHERE 1=1";
		}
		
		// User CTX filtering
		$ctx_where = '';
		$ctx_list = Session::get_ctx_where();
		
		if ($ctx != '') 
		{ 
		    $ctx_list .= ($ctx_list != '') ? ", UNHEX('$ctx')" : "UNHEX('$ctx')"; 
		}
		
		if ($ctx_list != '') 
		{
			$ctx_where = " AND ctx in (" . $ctx_list . ")";
		}
			
        if(!Session::am_i_admin()) 
        {
            $entities_users = array();
            
            $entities_to_assign = (Session::is_pro()) ? Acl::get_entities_to_assign($conn) : array();
            $users_to_assign    = Session::get_users_to_assign($conn);
            
            foreach($users_to_assign as $v) 
            {
                $entities_users[] = $v->get_login();
            }
            
            $username_where = " AND username IN ('".implode("', '",array_merge($entities_users, array_keys($entities_to_assign)))."')";
        }
        else 
        {
            $username_where = '';
        }
		if( $date_filter == '' && $ip_where == '' && $username_where == '' && $ctx_where == '')
		{
			$sql = "SELECT msg as name,risk,hostIP as ip,scantime FROM vuln_nessus_latest_results ORDER BY risk ASC,scantime";
		}
		else
		{
			$sql = "SELECT msg as name,risk,hostIP as ip,scantime FROM vuln_nessus_latest_results $date_filter $ip_where $ctx_where $username_where ORDER BY risk ASC,scantime";
		}
						
		//echo $sql;
       
		$highrisk   = "-";
		$lastdate   = "-";
		$list       = array();
		$risks      = array();
		$risks["-"] = 0;
        $foundrows  = 0;
        
        $rs = $conn->Execute($sql);

		if (!$rs) 
		{
            print $conn->ErrorMsg();
        }
        else 
		{
			while (!$rs->EOF) 
			{
                $foundrows++;
                
                if ($truncate) 
                {    
                    $list[] = array("name" => substr(trim($rs->fields['name']), 0, 120)." [...]", "risk" => $rs->fields['risk'], "ip" => $rs->fields['ip']);
                }
                else
                { 
                    $list[] = array("name" => trim($rs->fields['name']),"risk" => $rs->fields['risk'],"ip" => $rs->fields['ip']);
                }
                
                $risks[$rs->fields['risk']]++;
                
                if ($rs->fields['risk'] < $highrisk || $highrisk == "-") 
                {    
                    $highrisk = $rs->fields['risk'];
                }
                
                if ($lastdate == "-")
                { 
                    $lastdate = preg_replace("/(\d\d\d\d)(\d\d)(\d\d)(\d\d)(\d\d)(\d\d)/","\\1-\\2-\\3 \\4:\\5:\\6",$rs->fields['scantime']);
                }
                
				$rs->MoveNext();
			}
		}
        if ($risks[$highrisk] == '') 
		{ 
			$risknum = 0; 
			$highrisk = "-"; 
		}
		else
		{ 
			$risknum = $risks[$highrisk];
		}
		
		return array($list, $foundrows, $highrisk, $risknum, $lastdate);
	}		
	
	
	function range_graphic($timerange, $date_range = NULL) 
	{
		// For custom
		
		if( $date_range != NULL && $timerange == 'custom' )
		{
			$date_range['date_from']  = ( preg_match("/^\d+\-\d+\-\d+$/",$date_range['date_from']) ) ?  $date_range['date_from']." 00:00:00" : $date_range['date_from'];
			$date_range['date_to']    = ( preg_match("/^\d+\-\d+\-\d+$/",$date_range['date_to']) )   ?  $date_range['date_to']." 23:59:59"   : $date_range['date_to'];
			
			$from = strtotime($date_range['date_from']);
			$to   = strtotime($date_range['date_to']);
			
			$diff = $to - $from;
			
			$limit_1 = 86400 + 86400;
			$limit_2 = 86400 + 604800;
			$limit_3 = 86400 + 1296000;
			$limit_4 = 86400 + 2678400;
								
            if ($diff <= $limit_1)
            {				
                $timerange = 'day';
            }
            elseif ($diff > $limit_1 && $diff <= $limit_2)
            {
                $timerange = "week";
            }
            elseif ($diff > $limit_2 && $diff <= $limit_3)
            {
                $timerange = 'weeks';
            }
            elseif ($diff > $limit_3 && $diff <= $limit_4)
            {
                $timerange = 'custom1';
            }
            elseif($diff > $limit_4)
            {
                $timerange = 'custom2';
            }
			/*
			echo "Time range: ".$timerange."<br/>";
			echo "Date from: ".$date_range['date_from']." - Date to: ".$date_range['date_to']."<br/>";
			echo "Diff: ".$diff."<br/>";
			*/
		}
		
		
		switch ($timerange) 
		{
			case "today":
				$desde    = strtotime(date("Y-m-d 00:00:00"));
				$suf      = "h";
				$jump     = 3600;
				$interval = "G";
				$key      = "G";
                $noprint  = 2;
				$hasta    = time();
			break;

			case "day":
				if($date_range != NULL)
				{
					$desde = strtotime($date_range['date_from']);
					$hasta = strtotime($date_range['date_to']);
				}
				else
				{
					$desde = strtotime("-23 hour");
					$hasta = time() + $jump;
				}
				
				$suf      = '';
				$jump     = 7200;
				$interval = "G\h j M";
				$key      = "G j";
                $noprint  = 3;
			break;

			case "week":
				if($date_range != NULL)
				{
					$desde = strtotime($date_range['date_from']);
					$hasta = strtotime($date_range['date_to']);
				}
				else
				{
					$desde = strtotime("-1 week");
					$hasta = time();
				}
				
				$suf      = '';
				$jump     = 86400;
				$interval = "j M";
				$key      = "j F";
                $noprint  = 1;
				break;

				case "weeks":
					if($date_range != NULL)
					{
						$desde = strtotime($date_range['date_from']);
						$hasta = strtotime($date_range['date_to']);
					}
					else
					{
						$desde = strtotime("-2 week");
						$hasta = time();
					}
					
					$suf      = '';
					$jump     = 86400;
					$noprint  = 3;
					$interval = "j M";
					$key      = "j F";
				break;

				case "month":
					if($date_range != NULL)
					{
						$desde = strtotime($date_range['date_from']);
						$hasta = strtotime($date_range['date_to']);
					}
					else
					{
						$desde = strtotime("-1 month");
						$hasta = time();
					}
					$suf      = '';
					$jump     = 86400;
					$interval = "j M";
					$key      = "j F";
                    $noprint  = 3;
				break;
					
				case "custom1": // Months
					$desde    = strtotime($date_range['date_from']);
					$hasta    = strtotime($date_range['date_to']);
					$suf      = '';
					$jump     = 86400;
					$interval = "j M";
					$key      = "j F";
                    $noprint  = 3;
				break;
			
			case "custom2": // Year
				$desde    = strtotime($date_range['date_from']);
				$hasta    = strtotime($date_range['date_to']);
				$suf      = '';
				$jump     = 0;
				$interval = "M-Y";
				$key      = "F Y";
                $noprint  = 2;  
			break;
				
			default:
				$desde    = strtotime("-11 month");
				$suf      = '';
				$jump     = 0;
				$interval = "M-Y";
				$key      = "F Y";
				$hasta    = time();
                $noprint  = 2;  
		}
	
	
		$x  = $y = $ticks = $labels = array();
		$d  = $desde;
		$xx = 1;
								
		while ($d <= $hasta) 
		{
			$now            = trim(date($key, $d) . " " . $suf);
			$x["$now"]      = $ticks["$now"] = $xx;
            $y["$now"]      = 0; // default value 0
			
            $labels["$now"] = date($interval, $d) . $suf;
			
            if ($jump == 0)
            { 
				$d+= (date("t", $d) * 86400); // case year
			}
			else
			{ 
				$d+= $jump; // Next date
            }
                
            $xx++;
		}
		
		return array(
			$x,
			$y,
			$ticks,
			$labels,
			$timerange,
            $noprint
        );
	}	
}
?>
