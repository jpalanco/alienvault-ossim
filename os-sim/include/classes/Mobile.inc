<?php
/**
* Mobile.inc
*
* File Mobile.inc is used to:
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
* @package    ossim-framework\Mobile
* @autor      AlienVault INC
* @license    http://www.gnu.org/licenses/gpl-2.0.txt
* @copyright  2003-2006 ossim.net
* @copyright  2007-2013 AlienVault
* @link       https://www.alienvault.com/
*/


/**
* Function list:
* - Mobile()
* - siem_unique_events()
* - get_date()
* - get_logo()
*/

class Mobile 
{
    function __construct() 
    {            
    }
    
    function get_version() 
    {
	   return trim(`dpkg -l ossim-framework | cut -f 2 -d ":"|sed 's/ .*//'|grep ^4`);
    }
    
    function is_mobile_device() 
    {
        return preg_match('/Mobile|iP(hone|od|ad)|Android|BlackBerry|IEMobile|Kindle|NetFront|Silk-Accelerated|(hpw|web)OS|Fennec|Minimo|Opera M(obi|ini)|Blazer|Dolfin|Dolphin|Skyfire|Zune/',$_SERVER['HTTP_USER_AGENT']);
    }
}

// Unique Signatures - Reports
function siem_unique_events($date_from, $date_to, $top, $dDB, $dDBdata) 
{
    
    $db = new ossim_db();
    
    $conn       = $db->snort_connect();
    $ossim_conn = $db->connect();
    
    
    $orderby = $dDBdata["orderby"];
    $events = array();
      
    // Taxonomy, Source Type filters
	$plugin_list = Plugin_sid::get_all_sids($ossim_conn, $dDBdata["plugin_id"], $dDBdata["sourcetype"], $dDBdata["category"], $dDBdata["subcategory"], $dDBdata['plugin_groups'], $dDBdata["force_sql"]);
	
    $order_by = "sig_cnt DESC";
    
    switch($orderby) 
    {
    	case "eventsdesc": $order_by = "sig_cnt DESC";    break;
    	case "eventsasc":  $order_by = "sig_cnt ASC";     break;
    	case "sensordesc": $order_by = "sid_cnt DESC";    break;
    	case "sensorasc":  $order_by = "sid_cnt ASC";     break;
    	case "srcdesc":    $order_by = "saddr_cnt DESC";  break;
    	case "srcasc":     $order_by = "saddr_cnt ASC";   break;
    	case "dstdesc":    $order_by = "daddr_cnt DESC";  break;
    	case "dstasc":     $order_by = "daddr_cnt ASC";   break;
	}
    
    if(!array_key_exists('ctx', $dDB)) 
    {
        $dDB["ctx"] = array();
    }
    
    $where = "FROM acid_event 
    		LEFT JOIN alienvault.plugin_sid ON acid_event.plugin_id=plugin_sid.plugin_id AND acid_event.plugin_sid=plugin_sid.sid
    		WHERE 1 ".Security_report::make_where($conn,$date_from,$date_to,$plugin_list,$dDB);
    //
    $query = "SELECT SQL_CALC_FOUND_ROWS DISTINCT acid_event.plugin_id, acid_event.plugin_sid, plugin_sid.name as sidname, count(acid_event.plugin_sid) as sig_cnt, min(timestamp) as first_timestamp, max(timestamp) as last_timestamp, count(DISTINCT(acid_event.id)) as sid_cnt, count(DISTINCT(ip_src)) as saddr_cnt, count(DISTINCT(ip_dst)) as daddr_cnt
    $where GROUP BY plugin_id, plugin_sid ORDER BY $order_by LIMIT $top";
    $cntsql = "SELECT count(*) $where";
	
    //echo "<b>SQL</b>: $query<br>";
    
	if (!$rs = & $conn->Execute($query)) 
	{
        print $conn->ErrorMsg();
    } 
    else 
    {
        if (!$rf = & $conn->Execute($cntsql)) 
        {
            print $conn->ErrorMsg();
        }
        else 
        {
            $event_cnt = $rf->fields[0];
        }
        
        while (!$rs->EOF) 
        {
            $myrow = $rs->fields;
            
            if ($myrow["plugin_id"] == '' || $myrow["plugin_sid"] == '') 
            { 
                $rs->MoveNext();

                continue;
            }
            
            if ($myrow['sidname'] == '')
            {
                $myrow['sidname'] = Status::get_unknown_sid_name($conn,$myrow['plugin_id'],$myrow['plugin_sid']);
            }
            
            $myrow["perc"] = round($myrow["sig_cnt"] / $event_cnt * 100);
            
            $myrow["data_source"] = GetDataSource($myrow["plugin_id"], $conn);
            $myrow["source_type"] = GetSourceType($myrow["plugin_id"], $conn);
            
            
            if (!$myrow["category"])
            {
                list($myrow["category"], $myrow["subcategory"]) = GetCategorySubCategory($myrow["plugin_id"], $myrow["plugin_sid"], $conn);
            }
            
            if ($myrow["category"] == '') 
            {
                $myrow["category"] = "-";
            }
            
            $events[] = $myrow;
            
            $rs->MoveNext();
        }
    }
    
    $db->close();
  
    return $events;
}


function GetDataSource($pid,$db) 
{
    $datasource = "";
    $temp_sql = "select name from alienvault.plugin where id=$pid";
    $tmp_result = $db->Execute($temp_sql);
    if (!$tmp_result->EOF) {
        $datasource = $tmp_result->fields["name"];
    }
    if ($tmp_result) $tmp_result->free();
    return $datasource;
}


function GetSourceType($pid,$db) 
{
    $sourcetype = "";
    
    $temp_sql = "select product_type.name as source_type,plugin.description from alienvault.plugin,alienvault.product_type where product_type.id=plugin.product_type AND plugin.id=$pid";
    
    $tmp_result = $db->Execute($temp_sql);
    
    if (!$tmp_result->EOF) 
    {
    	$myrow = $tmp_result->fields;
        $sourcetype = ($myrow["source_type"]!="") ? $myrow["source_type"] : $myrow["description"];
    }
    
    if ($tmp_result) 
    {
        $tmp_result->free();
    }
    
    return $sourcetype;
}


function GetCategorySubCategory($pid, $sid, $db) 
{
    static $cachescat;
    
    if (isset($cachescat[$pid][$sid])) 
    {
        return $cachescat[$pid][$sid];
    }
    
    $temp_sql = "SELECT c.name as cname,sc.name as scname FROM alienvault.plugin_sid p LEFT JOIN alienvault.category c ON p.category_id=c.id LEFT JOIN alienvault.subcategory sc ON p.subcategory_id=sc.id AND sc.cat_id=p.category_id WHERE p.plugin_id=$pid and p.sid=$sid";
    
    $tmp_result = $db->Execute($temp_sql);
    
    if ($myrow = $tmp_result->fields) 
    {
    	$myrow[0] = $myrow["cname"] = str_replace("_"," ",$myrow["cname"]);
    	$myrow[1] = $myrow["scname"] = str_replace("_"," ",$myrow["scname"]);
        $cachescat[$pid][$sid] = $myrow;
    } 
    else 
    {
        $cachescat[$pid][$sid] = array("","");
    }
    
    if ($tmp_result) 
    {
        $tmp_result->free();
    }
    
    return $cachescat[$pid][$sid];
}

?>
