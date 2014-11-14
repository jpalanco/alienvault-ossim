<?php
/**
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
*/



// Timezone correction
$tz     = Util::get_timezone();
$timetz = gmdate("U") + (3600*$tz); // time to generate dates with timezone correction


// Sensor sid allowed (snort bbdd)
function GetSnortSensorSids($conn2) 
{
	$query = "SELECT * FROM alienvault_siem.sensor";
	
	if (!$rs = & $conn2->Execute($query)) 
	{
		Av_exception::throw_error(Av_exception::DB_ERROR, $conn2->ErrorMsg());
	}
	
	while (!$rs->EOF) 
	{
		$sname = ($rs->fields['sensor'] != '') ? $rs->fields['sensor'] : preg_replace("/-.*/", '', preg_replace("/.*\]\s*/", '', $rs->fields['hostname']));
		
		$ret[$sname][] = $rs->fields['sid'];
		
		$rs->MoveNext();
	}
	
	return $ret;
}


// Context allowed filter
function make_ctx_filter($alias = 'acid_event') 
{
    $where = '';
    $ctxs  = Session::get_ctx_where();
    
    if ($ctxs != "") 
    {
        $where .= " AND $alias.ctx in ($ctxs)";
    }   
    
	return $where;
}


// Asset filter
function make_asset_filter($type = 'event', $alias = 'acid_event') 
{
    $where = '';
    $join  = '';
    $hosts = Session::get_host_where();
    $nets  = Session::get_net_where();
   
    if ($hosts != '') 
    {
        if ($type == 'event') 
        {
            $where = " AND ($alias.src_host in ($hosts) OR $alias.dst_host in ($hosts)";
            
            if ($nets != '') 
            {
                $where .= " OR $alias.src_net in ($nets) OR $alias.dst_net in ($nets))";
            }
            else
            {             
                $where .= ')';
            }
        } 
        else 
        {
            $where = " alarm.backlog_id=alarm_hosts.id_alarm";
            
            if ($nets != '') 
            {
                $where .= " AND alarm.backlog_id=alarm_hosts.id_alarm AND (alarm_hosts.id_host in ($hosts) OR alarm_nets.id_net in ($nets))";
                $join   = ",alarm_hosts, alarm_nets ";
            } 
            else 
            {
                $where .= " AND alarm_hosts.id_host in ($hosts)";
                $join   = ",alarm_hosts ";
            }
        }
    }
    elseif ($nets != '') 
    {
        if ($type == 'event') 
        {
            $where = " AND ($alias.src_net in ($nets) OR $alias.dst_net in ($nets))";
        } 
        else 
        {
            $where = " alarm.backlog_id=alarm_nets.id_alarm AND alarm_nets.id_net in ($nets)";
            $join  = ",alarm_nets ";
        }    
    }       
	return array($join, $where);
}


// Taxonomy filter
function make_where($conn, $arr) 
{
	include_once('../report/plugin_filters.php');
	
	$w = '';
	
	foreach ($arr as $cat => $scs) 
	{
		$id  = GetPluginCategoryID($cat, $conn);
		$w  .= "(c.cat_id=$id"; 
		$ids = array();
		
		foreach ($scs as $scat) 
		{
			$ids[] = GetPluginSubCategoryID($scat, $id, $conn);
		}
		
		if (count($ids) > 0 ) 
		{
		    $w .= " AND c.id in (".implode(",",$ids).")";
		}
		
		$w .= ') OR ';
	}
	
	return ($w != '') ? "AND (".preg_replace("/ OR $/", '', $w).')' : '';
}


// SID filter from alienvault_siem.sensor
function make_sid_filter($conn,$ip) 
{
	$sids = array();
	
	if (preg_match("/\d+\/\d+/",$ip)) 
	{		
		$aux = Cidr::expand_cidr($ip, 'SHORT', 'IP');
		
        if ($aux[0] == 'I' && $aux[1] == 'P')  
        { 
            $aux[0] = '0x0'; 
            $aux[1] = '0x0';
        } 
        else 
        {        
            $aux[0] = bin2hex(inet_pton($aux[0]));
            $aux[1] = bin2hex(inet_pton($aux[1]));
		}
		
		$query = "SELECT d.id FROM alienvault_siem.device d, alienvault.sensor s 
		          WHERE d.sensor_id=s.id 
		          AND ( (s.ip >= UNHEX('".$aux[0]."') AND s.ip <= UNHEX('".$aux[1]."')) 
		          OR (d.device_ip>=UNHEX('".$aux[0]."') AND d.device_ip <= UNHEX('".$aux[1]."')) )";
	} 
	else 
	{
	    $ip = bin2hex(@inet_pton($ip));
		
		$query = "SELECT d.id FROM alienvault_siem.device d, alienvault.sensor s 
		          WHERE d.sensor_id = s.id AND ( s.ip = UNHEX('$ip') OR d.device_ip = UNHEX('$ip') )";
	}
	
	//echo $query;
	
	if (!$rs = & $conn->Execute($query)) 
	{
		Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
	}
	
	while (!$rs->EOF) 
	{
		$sids[] = $rs->fields['id'];
		
		$rs->MoveNext();
	}
	
	return implode(',', $sids);
}
?>