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


require_once 'classes/Security.inc';

Session::logcheck('report-menu', 'ReportsReportServer');

$db      = new ossim_db();
$conn    = $db->connect();

$user    = Session::get_session_user();
$inserts = array();

$ips_filter     = '';
$ips_filter_tmp = '';

$param['nets'] = get_allowed_nets($conn);


if(count($param['nets']) > 0)
{
    $tmp_filter = array();
    foreach($param['nets'] as $net_data)
	{             
        $e_cidrs = Asset_net::expand_cidr($net_data['ips'], 'SHORT', 'LONG');
        
        foreach($e_cidrs as $long_ips)
        {
            $tmp_filter[] = "((INET_ATON(source) >= ".$long_ips[0]." AND INET_ATON(source) <= ".$long_ips[1].") 
                OR (INET_ATON(destination) >= ".$long_ips[0]." AND INET_ATON(destination) <= ".$long_ips[1]."))";
        }       
        
    }
	
    $ips_filter_tmp = implode(' OR ', $tmp_filter);
}

if($ips_filter_tmp != '')
{ 
	$ips_filter = 'AND '.$ips_filter_tmp;
}

//$date_filter = ($year_to!=$year) ? "($year,$year_to)" : "($year)";
// Updated: Date filter disabled. Already filtering by date_from-date_to RANGE each query

if (!Session::am_i_admin()) 
{
    $srcs = array('0.0.0.0');
    $dsts = array('0.0.0.0');

     // SSI
    $sql = "SELECT source, destination FROM datawarehouse.ssi WHERE 1 $ips_filter";

    //echo $sql;

    if (!$rs = & $conn->Execute($sql)) 
	{
        Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
    }
    
    
    // test perms for source or destination ips
    while (!$rs->EOF) 
	{
        $ip_src = $rs->fields['source'];
        $ip_dst = $rs->fields['destination'];
        
        if (isset($srcs[$ip_src]) || Asset_host::is_allowed($conn, $ip_src))
        { 
            $srcs[$ip_src]++;
        }
        
        if (isset($dsts[$ip_dst]) || Asset_host::is_allowed($conn, $ip_dst)) 
        {    
            $dsts[$ip_dst]++;
        }
        
        $rs->MoveNext();
    }
    
    $inserts[] = "REPLACE INTO datawarehouse.ssi_user SELECT *,'$user' FROM datawarehouse.ssi 
        WHERE 1 AND (source IN ('".implode("','", array_keys($srcs))."') OR destination IN ('".implode("','", array_keys($dsts))."'))";
		
	$sql = "SELECT source, destination FROM datawarehouse.incidents_ssi WHERE 1 $ips_filter"; // AND month=$month
    //echo $sql; 

    // INCIDENTS_SSI
    if (!$rs = & $conn->Execute($sql)) 
    {
        Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
    }
    
    // test perms for source or destination ips
    while (!$rs->EOF) 
	{
        $ip_src = $rs->fields['source'];
        $ip_dst = $rs->fields['destination'];
        
        if (isset($srcs[$ip_src]) || Asset_host::is_allowed($conn, $ip_src)) 
        {
            $srcs[$ip_src]++;
        }
        
        if (isset($dsts[$ip_dst]) || Asset_host::is_allowed($conn, $ip_dst)) 
        {
            $dsts[$ip_dst]++;
        }
        
        $rs->MoveNext();
    }
    
    $inserts[] = "REPLACE INTO datawarehouse.incidents_ssi_user SELECT *,'$user' FROM datawarehouse.incidents_ssi 
        WHERE 1 AND (source IN ('".implode("','",array_keys($srcs))."') OR destination IN ('".implode("','",array_keys($dsts))."'))";
        
} 
else 
{
	$inserts[] = "REPLACE INTO datawarehouse.ssi_user SELECT *,'$user' FROM datawarehouse.ssi WHERE 1 $ips_filter";
	$inserts[] = "REPLACE INTO datawarehouse.incidents_ssi_user SELECT *,'$user' FROM datawarehouse.incidents_ssi WHERE 1 $ips_filter";    
}

// Delete first and fill only with permited data
$conn->Execute("DELETE FROM datawarehouse.ssi_user WHERE user = '$user'");
$conn->Execute("DELETE FROM datawarehouse.incidents_ssi_user WHERE user = '$user'");

foreach($inserts as $insert) 
{   
    if (!$conn->Execute($insert)) 
	{
        Av_exception::throw_error(Av_exception::DB_ERROR, $conn->ErrorMsg());
    }
}

$db->close();
?>