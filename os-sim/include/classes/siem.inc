<?php
/**
* siem.inc
*
* File siem.inc is used to:
*   - Access to alienvault_siem database siem events
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

/**
 * Function list:
 * - SIEM()
 * - set_filter()
 * - get_events()
 * 
 */

class Siem 
{	
	public $conn;
	
    public $geoloc;
	
    public $filters;
	
	/**
    * Class constructor
    *
    * This function sets up the class
    */
	function __construct()
	{
		$db            = new ossim_db();
		$this->conn    = $db->snort_connect();
		$this->av_conn = $db->connect();
		$this->geoloc  = new Geolocation('/usr/share/geoip/GeoLiteCity.dat');
		$this->filters = array();
	}
	
	/**
    * This function adds a new criteria to filters array, for example a signature search
    * Each filter component will be an AND clause into the final WHERE sentence
    * To implement the OR operator we must use this method once, with multiple fields
    *
    * @param string or array $field
    * @param string or array $value
    * @param string $operator
    */
	function add_criteria($field, $value, $operator = "=")
	{
		$filter = array();
		
		// Multiple
		if (is_array($field))
		{
			for ($i = 0; $i < count($field); $i++) {
				$_field   = $field[$i];
				$_value   = (is_array($value)) ? $value[$i] : $value;
				$_op      = (is_array($operator)) ? $operator[$i] : $operator;
				$filter[] = $this->get_where_clause($_field, $_value, $_op);
			}
		}
		// Single
		else
		{
			$filter[] = $this->get_where_clause($field, $value, $operator);
		}
		
		$this->filters[] = $filter;
	}
	
	/**
	 * This function processes a filter to get the SQL string
	 *
	 * @param string $field
	 * @param string $value
	 * @param string $operator
	 */
	function get_where_clause($field, $value, $operator = "=")
	{
		// When have to use binary conversion
		if ($this->is_binary($field)) {
			$value = "UNHEX('$value')";
		// Common string
		} else {
			$value = "\"$value\"";
		}
		return "$field $operator $value";
	}
	
	/**
	 * This function returns if an acid_event field is a binary(16)
	 *
	 * @param string $field
	 *
	 * @return bool
	 */
	function is_binary($field)
	{
		if ($field == "src_host"
		||  $field == "dst_host"
		||  $field == "src_net"
		||  $field == "dst_net"
		||  $field == "ctx")
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * This function processes all filters (AND/OR) to get the SQL where sentence string
	 *
	 * @return string
	 */
	function get_filters_where()
	{
		$ors = array();
		for ($i = 0; $i < count($this->filters); $i++) {
			$ors[] = "(".implode(" OR ", $this->filters[$i]).")";
		}
		return (count($ors) > 0) ? " AND ".implode(" AND ", $ors) : "";
	}

    /**
    * This function returns join and where with a user perms
    *
    * @return string
    */
    public static function make_perms_where()
    {
        $where  = "";
        $domain = Session::get_ctx_where();
        // Asset filter
        if ($domain != "") {
            $where .= " AND acid_event.ctx in ($domain)";
        }
        // Asset filter
        $host_perms = Session::get_host_where();
        $net_perms = Session::get_net_where();
        
        if ($host_perms != "") {
            $where .= " AND (acid_event.src_host in ($host_perms) OR acid_event.dst_host in ($host_perms)";
            if ($net_perms != "") $where .= " OR acid_event.src_net in ($net_perms) OR acid_event.dst_net in ($net_perms))";
            else                  $where .= ")";
        }
        elseif ($net_perms != "") {
            $where .= " AND (acid_event.src_net in ($net_perms) OR acid_event.dst_net in ($net_perms))";
        }
        
        return $where;
    }
    
    /**
    * This function returns true if current asset type has events
    *
    * @param object   $conn     Database access object
    * @param string   $type     Asset type (host, network, group)
    * @param string   $id       Host uuid (Hex value)
    *
    * @throws Exception  If a connection error occurred
    *
    * @return boolean
    */
    public static function has_events($conn, $type, $id)
    {
        Ossim_db::check_connection($conn);
        
        if ($type == "host")
        {
            $sql = "SELECT EXISTS(SELECT src_host as id FROM alienvault_siem.ac_acid_event WHERE src_host=UNHEX(?) AND cnt>0 UNION SELECT dst_host as id FROM alienvault_siem.ac_acid_event WHERE dst_host=UNHEX(?) AND cnt>0) as bool";
        }
        elseif ($type == "net")
        {
            $sql = "SELECT EXISTS(SELECT ac.src_host as id FROM alienvault_siem.ac_acid_event ac, alienvault.host_net_reference r WHERE ac.src_host=r.host_id AND r.net_id = UNHEX(?) AND ac.cnt>0 UNION SELECT ac.dst_host as id FROM alienvault_siem.ac_acid_event ac, alienvault.host_net_reference r WHERE ac.dst_host=r.host_id AND r.net_id = UNHEX(?) AND ac.cnt>0) as bool";
        }
        elseif ($type == "group")
        {
            // Using perms
            $where = self::make_perms_where();
            $sql   = "SELECT EXISTS(SELECT acid_event.src_host as id FROM alienvault_siem.ac_acid_event acid_event, alienvault.host_group_reference r WHERE acid_event.src_host=r.host_id AND r.host_group_id = UNHEX(?) AND acid_event.cnt>0 $where UNION SELECT acid_event.dst_host as id FROM alienvault_siem.ac_acid_event acid_event, alienvault.host_group_reference r WHERE acid_event.dst_host=r.host_id AND r.host_group_id = UNHEX(?) AND acid_event.cnt>0 $where) as bool";
        }

        $rs = $conn->Execute($sql, array($id, $id));

        if (!$rs)
        {
            Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
        }
        // Total rows
        return ($rs->fields["bool"]) ? TRUE : FALSE;
    }
    	
	/**
	 * This function executes the query to acid_event DB table and gets the events array and the total count
	 *
	 * @param int $from
	 * @param int $max
	 * 
	 * @return array(array, int)
	 */
	function get_events($from = 0, $max = 50, $order = "timestamp", $torder = "DESC")
	{
		$where = $this->get_filters_where();
		
		$sql = "SELECT SQL_CALC_FOUND_ROWS * 
		FROM acid_event  
		LEFT JOIN alienvault.plugin_sid 
		ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid
		LEFT JOIN idm_data
		ON idm_data.event_id=acid_event.id 
		LEFT JOIN alienvault.plugin
		ON plugin.id = acid_event.plugin_id
		WHERE 1 $where ORDER BY $order $torder LIMIT $from, $max";
		
		//echo "<br>QUERY: <b>$sql</b><br>";
		
		$foundrows = 0;
		$list      = array();
		
		if (!$rs = $this->conn->Execute($sql))
		{
			Av_exception::write_log(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
		}
		else
		{
			if (!$rf = $this->conn->Execute("SELECT FOUND_ROWS() as total"))
				Av_exception::write_log(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
			else
				$foundrows = $rf->fields["total"];
				
			while (!$rs->EOF)
			{
				$list[] = $this->get_event_fields($rs->fields);
				$rs->MoveNext();
			}
		}
		
		return array($list, $foundrows);
	}
	
	/**
	 * This function executes the query to acid_event DB table and gets the events array
	 *
	 * @param int $from
	 * @param int $max
	 *
	 * @return array
	 */
	function get_events_light($from = 0, $max = 50, $order = "timestamp", $torder = "DESC")
	{
		$where = $this->get_filters_where();
	
		$sql = "SELECT *
    		FROM acid_event
    		LEFT JOIN alienvault.plugin_sid
    		ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid
    		LEFT JOIN idm_data
    		ON idm_data.event_id=acid_event.id
    		LEFT JOIN alienvault.plugin
    		ON plugin.id = acid_event.plugin_id
    		WHERE 1 $where ORDER BY $order $torder LIMIT $from, $max";
	
		//echo "<br>QUERY: <b>$sql</b><br>";
	
		$list = array();
	
		if (!$rs = $this->conn->Execute($sql))
		{
			Av_exception::write_log(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
		}
		else
		{
			while (!$rs->EOF)
			{
				$list[] = $this->get_event_fields($rs->fields);

				$rs->MoveNext();
			}
		}

		return $list;
	}
	
	/**
	 * This function executes the query to acid_event DB table and gets the events array and the total count [grouped by a field]
	 *
	 * @param string $groupby
	 * @param int $from
	 * @param int $max
	 *
	 * @return array(array, int)
	 */
	function get_events_grouped($groupby = "signature", $from = 0, $max = 50)
	{
		$where = $this->get_filters_where();
	
		$sql = "SELECT SQL_CALC_FOUND_ROWS $groupby, count(*)
		FROM acid_event FORCE INDEX (timestamp)
		LEFT JOIN alienvault.plugin_sid
		ON plugin_sid.plugin_id=acid_event.plugin_id AND plugin_sid.sid=acid_event.plugin_sid
		LEFT JOIN idm_data
		ON idm_data.event_id=acid_event.id
		WHERE 1 $where GROUP BY $groupby ORDER BY timestamp DESC LIMIT $from, $max";
	
		//echo "<br>QUERY: <b>$sql</b><br>";
	
		$foundrows = 0;
		$list      = array();
	
		if (!$rs = $this->conn->Execute($sql))
		{
			Av_exception::write_log(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
		}
		else
		{
			if (!$rf = $this->conn->Execute("SELECT FOUND_ROWS() AS total"))
			{
				Av_exception::write_log(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
			}
			else
			{
				$foundrows = $rf->fields["total"];
			}
	
			while (!$rs->EOF)
			{
				$list[] = $rs->fields;
				$rs->MoveNext();
			}
		}
	
		return array($list, $foundrows);
	}
	
	/**
	 * This function detect the current timestamp filter and gets the date_from and date_to separated values
	 * Used internally from self::get_graph_date, to know the range interval to use in the plot
	 * 
	 * @return Array: [$date_from, $date_to]
	 */
	function get_dates_by_filter()
	{
	    // Default values
	    $date_from = '1970-01-01';
	    $date_to   = date("Y-m-d");
	    
	    foreach ($this->filters as $filter)
	    {
	        foreach ($filter as $criteria)
	        {
	            // Found Date To
	            if (preg_match('/timestamp \<\=? "([\d\s\:\-]+)"/', $criteria, $matches))
	            {
	                $date_to   = $matches[1];
	            }
	            
	            // Found Date From
	            if (preg_match('/timestamp \>\=? "([\d\s\:\-]+)"/', $criteria, $matches))
	            {
	                $date_from = $matches[1];
	            }
	        }
	    }
	    
	    return array($date_from, $date_to);
	}
	
	/**
	 * This function gets the data for plot graphic in siem console and reports
	 * Group automatically by the timestamp filter in the Siem object (by hours OR by days OR by months)
	 *
	 * @return Array plot graph data ('0h' => n, '1h' => n, ...) OR ('2 May' => n, '3 May' => n, ...) OR ...
	 * 
	 * WARNING: $tmpconn and $where parameters must be removed
	 */
	function get_graph_data($tmpconn, $where)
	{
	    // Get the current timestamp filter in the Siem object
	    list($date_from, $date_to) = $this->get_dates_by_filter();
	    
	    // Timezone
	    $tz  = Util::get_timezone();
	    $tzc = Util::get_tzc($tz);
	    
	    // Ignore future date_to limit, force to current time
	    if (strtotime($date_to) > time())
	    {
	        $date_to = date("Y-m-d H:i:s", time() - (3600*$tz));
	    }
	    
	    /** Take this where in the future
	     *  By now will use the Security_report::make_where
	     */
	    //$where = $this->get_filters_where();
	    
	    // Get the labels
	    $diff = strtotime($date_to) - strtotime($date_from);
	    
	    // Left join needed for taxonomy filters
	    $tax_join = (preg_match('/plugin_sid\./', $where)) ? 'LEFT JOIN alienvault.plugin_sid 
	            ON acid_event.plugin_id=plugin_sid.plugin_id 
	            AND acid_event.plugin_sid=plugin_sid.sid' : '';
	    
	    // Join needed for reputation filters
	    $rep_join = (preg_match('/reputation\_data\./', $where)) ? ', alienvault_siem.reputation_data' : '';
	    
	    // Join needed for extra_data filters
	    $extra_join = (preg_match('/extra\_data\./', $where)) ? ', alienvault_siem.extra_data' : '';
	    
	    // Database selector to fix the 'device' filter
	    $where = preg_replace('/FROM device/', 'FROM alienvault_siem.device', $where);
	    
	    // WARNING: remove alienvault_siem database selection from query when be posible
	    // more than 1 month
	    if ($diff > 2678400)
	    {
	        $jump = 0;
	        $key  = "M Y";
	        
	        $sqlgraph = "SELECT COUNT(acid_event.id) as num_events,
	        DATE_FORMAT(convert_tz(timestamp,'+00:00','$tzc'), '%b %Y') as label
	        FROM alienvault_siem.acid_event $tax_join $rep_join $extra_join WHERE 1 $where GROUP BY label";
	    }
	    // more than 1 day
	    elseif ($diff >= 86400)
	    {
	        $jump = 86400;
	        $key  = "d M";
	        
	        $sqlgraph = "SELECT COUNT(acid_event.id) as num_events,
	        DATE_FORMAT(convert_tz(timestamp,'+00:00','$tzc'), '%d %b') as label
	        FROM alienvault_siem.acid_event $tax_join $rep_join $extra_join WHERE 1 $where GROUP BY label";
	    }
	    // less than 1 day
	    elseif ($diff < 86400)
	    {
	        $jump = 3600;
	        $key  = "G\h";
	        
	        $sqlgraph = "SELECT COUNT(acid_event.id) as num_events,
	        CONCAT(DATE_FORMAT(convert_tz(timestamp,'+00:00','$tzc'), '%k'), 'h') as label 
	        FROM alienvault_siem.acid_event $tax_join $rep_join $extra_join WHERE 1 $where GROUP BY label";
	    }
	    
	    // Initialize the graph data
	    $data   = array();
	    $d      = strtotime($date_from) + (3600*$tz);
	    while ($d <= strtotime($date_to) + (3600*$tz))
	    {
	        // Initially all values to zero
	        $now            = gmdate($key, $d + (3600*$tz));
	        $data["$now"]   = 0;
	        
	        // Next tick label
	        if ($jump == 0)
	        {
	            $d += (date("t", $d) * 86400); // case year, get the current month days
	        }
	        else
	        {
	            $d += $jump; // next date
	        }
	    }
	    
	    //echo "<br>QUERY: <b>$sqlgraph</b><br>";
	
	    $rs = $tmpconn->Execute($sqlgraph);
	    
	    if (!$rs)
	    {
	        Av_exception::throw_error(Av_exception::DB_ERROR, $tmpconn->ErrorMsg());
	    }
	    else
	    {
	        while (!$rs->EOF)
	        {
	            $data[$rs->fields['label']] = $rs->fields['num_events'];
	            $rs->MoveNext();
	        }
	    }
	    
	    return $data;
	}
	
	function get_event_fields($row)
	{
		$fields = array();
		
		// Signature
		$signature = _("Unknown Event");
		if ($row['plugin_id'] != "" && $row['plugin_sid'] != "")
		{
			$sql = "SELECT name FROM alienvault.plugin_sid WHERE plugin_id=".$row['plugin_id']." AND sid=".$row['plugin_sid'];
			$rs  = $this->conn->Execute($sql); 
			if (!$rs)
			{
				Av_exception::throw_error(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
			}
			else
			{
				if (!$rs->EOF)
				{
					$signature = $rs->fields['name'];
				}
			}
		}
		$fields['signature'] = $signature;
		
		// Data Source
		$ds = _("Unknown DS");
		if ($row['plugin_id'] != "")
		{
			$sql = "SELECT name FROM alienvault.plugin WHERE id=".$row['plugin_id'];
			$rs  = $this->conn->Execute($sql);
			if (!$rs)
			{
				Av_exception::throw_error(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
			}
			else
			{
				if (!$rs->EOF)
				{
					$ds = $rs->fields['name'];
				}
			}
		}
		$fields['datasource'] = $ds;
		
		// Sensor
		$name     = "Unknown";
		$sid      = $row['device_id'];
		$multiple = (preg_match("/\,/", $sid)) ? TRUE : FALSE;
		if ($multiple)
		{
		    $sid = preg_replace("/\s*\,.*/", "", $sid);
		}
		$sql = "SELECT ase.name FROM alienvault_siem.device avs LEFT JOIN alienvault.sensor ase ON avs.sensor_id=ase.id WHERE avs.id = ?";
		$rs  = $this->conn->Execute($sql, array($sid));
		if (!$rs)
		{
			Av_exception::throw_error(Av_exception::DB_ERROR, $this->conn->ErrorMsg());
		}
		else
		{
			if (!$rs->EOF)
			{
				$name = ($rs->fields['name'] != "") ? $rs->fields['name'] : "N/A";
			}
		}
		$fields['sensor'] = $name;
		
		// Date
		$fields['date'] = $row['timestamp'];
		
		// Context
		$ctx               = strtoupper(bin2hex($row['ctx']));
		$ctx_name          = (Session::is_pro()) ? Acl::get_entity_name($this->av_conn, $ctx, TRUE) : "-";
		$fields['context'] = ($ctx_name != "") ? $ctx_name : _("Unknown");
		
		// Source
		$fields['src_ip']   = inet_ntop($row["ip_src"]);
		$fields['src_host'] = strtoupper(bin2hex($row['src_host']));
		$fields['src_net']  = strtoupper(bin2hex($row['src_net']));
		
		$src_output         = Asset_host::get_extended_name($this->av_conn, $this->geoloc, $fields['src_ip'], $ctx, $fields['src_host'], $fields['src_net']);
		$fields['src_name'] = $src_output['name'];
		$src_img            = $src_output['html_icon'];
		
		if ($row["src_userdomain"] != "") // IDM
		{
			$source = $row['src_userdomain'];
		}
		else // Hostname/IP
		{
			$source = $fields['src_name'];
		}
		$fields['source']   = "<div class='HostReportMenu' id='".$fields['src_ip'].";".$fields['src_name'].";".$fields['src_host']."' style='".(($src_output['is_internal']) ? "font-weight:bold" : "")."'>"
							.(($src_img != "") ? $src_img." ".$source : $source)."</div>";
		
		// Destination
		$fields['dst_ip']      = inet_ntop($row["ip_dst"]);
		$fields['dst_host']    = strtoupper(bin2hex($row['dst_host']));
		$fields['dst_net']     = strtoupper(bin2hex($row['dst_net']));
		
		$dst_output            = Asset_host::get_extended_name($this->av_conn, $this->geoloc, $fields['dst_ip'], $ctx, $fields['dst_host'], $fields['dst_net']);
		$fields['dst_name']    = $dst_output['name'];
		$dst_img               = $dst_output['html_icon'];
		
		if ($row["dst_userdomain"] != "") // IDM
		{
			$destination = $row['dst_userdomain'];
		}
		else // Hostname/IP
		{
			$destination = $fields['dst_name'];
		}
		$fields['destination'] = "<div class='HostReportMenu' id='".$fields['dst_ip'].";".$fields['dst_name'].";".$fields['dst_host']."' style='".(($dst_output['is_internal']) ? "font-weight:bold" : "")."'>"
								.(($dst_img != "") ? $dst_img." ".$destination : $destination)."</div>";		
		
		// Risk
		$maxrisk = ($row['ossim_risk_a'] > $row['ossim_risk_c']) ? $row['ossim_risk_a'] : $row['ossim_risk_c'];
		
		if ($maxrisk > 5)
		{
		    $risk = "H";
		}
		elseif ($maxrisk > 3)
		{
		    $risk = "M";
		}
		else
		{
		    $risk = "L";
		}
		
		$fields['risk'] = $risk;
		
		// Username@domain
		$fields['username'] = $row['idm_data.username'];
		$fields['domain']   = $row['idm_data.domain'];
		
		return $fields;
	}
	
	
}

/* End of file siem.inc */
/* Location: ../include/classes/siem.inc */