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
require_once 'av_init.php';


require_once 'deploy_common.php';

//Checking perms
check_deploy_perms();


$location   = GET('location');
$maxrows    = (POST('iDisplayLength') != "") ? POST('iDisplayLength') : 15;
$search_str = (POST('sSearch') != "") ? POST('sSearch') : "";
$from       = (POST('iDisplayStart') != "") ? POST('iDisplayStart') : 0;

$sec        = intval(POST('sEcho'));
$total      = 0;


ossim_valid($location, 		OSS_HEX, 				   	'illegal: Location');
ossim_valid($maxrows, 		OSS_DIGIT, 				   	'illegal: Config Param');
ossim_valid($search_str, 	OSS_INPUT, OSS_NULLABLE,   	'illegal: Search String');
ossim_valid($from, 			OSS_DIGIT,         			'illegal: Config Param');
ossim_valid($sec, 			OSS_DIGIT,				  	'illegal: Config Param');


if (ossim_error()) 
{
    $response['sEcho']                = $sec;
	$response['iTotalRecords']        = 0;
	$response['iTotalDisplayRecords'] = 0;
	$response['aaData']               = '';
	
	echo json_encode($response);
	
	exit();
}

$db       = new ossim_db(TRUE);
$conn     = $db->connect();

$response = array();
$data     = array();


$filters  = array();
$tables   = ', net_sensor_reference s,location_sensor_reference l';

$filters['limit'] = $from .',' .$maxrows;
$filters['where'] = " net.id=s.net_id and s.sensor_id=l.sensor_id and l.location_id=unhex('$location') ";

if (!empty($search_str))
{
    $search_str = escape_sql($search_str, $conn);
    
    $filters['where'] .= " AND (net.ips LIKE \"%$search_str%\" OR net.name LIKE \"%$search_str%\") ";
}

try
{
    list($nets, $total) = Asset_net::get_list($conn, $tables, $filters, FALSE);
}
catch(Exception $e)
{
    $nets  = array();
    $total = 0;
}
			

foreach ($nets as $_net) 
{
	$net_name = $_net["name"] .' (' . $_net["ips"] . ')';
	$nid      = $_net["id"];
	
	$class    = colorize_nets($conn, $nid, $location);
	
	$net  = "<span class='net_item cidr_help' net='$nid' title=\"$net_name\">$net_name </span>";
	$net .= "<div class='net_mark'><img src='/ossim/pixmaps/br_next.png' height='10px'></div>";


	/*****  Document Info  *****/
	$data[] = array(					
        $net,
        'DT_RowClass' => $class
    );
    
}

				
$response['sEcho']                = $sec;
$response['iTotalRecords']        = $total;
$response['iTotalDisplayRecords'] = $total;
$response['aaData']               = $data;

echo json_encode($response);							


$db->close(); 
