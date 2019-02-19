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

$_SESSION["per_page"] = $to = (POST('iDisplayLength') != '')
        ? POST('iDisplayLength') : (isset($_SESSION["per_page"]) ? $_SESSION["per_page"] : 10);
session_write_close();

Session::logcheck('environment-menu', 'PolicyHosts');


/* connect to db */
$db   = new ossim_db(TRUE);
$conn = $db->connect();
//DataTables Pagination and search Params
$from       = (POST('iDisplayStart') != '')  ? POST('iDisplayStart') : 0;
$order      = (POST('iSortCol_0') != '')     ? POST('iSortCol_0') : '';
$torder     = POST('sSortDir_0');
$sec        = POST('sEcho');


$torder = (!strcasecmp($torder, 'asc')) ? 0 : 1;

ossim_valid($to,  OSS_DIGIT, 'illegal: iDisplayLength');
ossim_valid($from,     OSS_DIGIT, 'illegal: iDisplayStart');
ossim_valid($order,    OSS_ALPHA, 'illegal: iSortCol_0');
ossim_valid($torder,   OSS_DIGIT, 'illegal: sSortDir_0');
ossim_valid($sec,      OSS_DIGIT, 'illegal: sEcho');

if (ossim_error()) 
{
    $response['sEcho']                = 1;
	$response['iTotalRecords']        = 0;
	$response['iTotalDisplayRecords'] = 0;
	$response['aaData']               = '';
	
	echo json_encode($response);
	exit;
}

/*
If $all_list = TRUE, then the asset list will be load from host because there are no filters to be applied
If $all_list = FALSE, then the asset list will be load from user_host_filter because there are already some filters applied
*/
$filters = Filter_list::retrieve_filter_list_session();
    	
if ($filters === FALSE )
{
	$all_list = TRUE;
}
else
{
    $cont     = $filters->get_num_filter_added();
    $all_list = ($cont > 0) ? FALSE : TRUE;
}


// Order by column
switch($order) 
{
	case 1:
		$order = 'host.hostname';  //Order by hostname
	break;
        
    case 2:
		$order = 'hi.ip';  //Order by IP
	break;
		
    case 4:
		$order = 'os';  //Order by OS
	break;
		
    case 5:
		$order = 'host.asset';  //Order by Asset Value
	break;
		
    case 6:
		$order = 'vuln';  //Order by Vuln Scan
	break;
		
    case 7:
		$order = 'hids';  //Order by HIDS Status
	break;

    default:
		$order = 'host.hostname';
}

$torder   = ($torder == 1) ? 'ASC' : 'DESC';
$session  = session_id();

//Get list params
$filters  = array();
$tables   = '';

$filters['order_by'] = $order . ' ' . $torder;
$filters['limit']    = $from . ', ' . $to;
$filters['where']    = '';

if (!$all_list)
{
    $tables = ', user_host_filter hf';
    $filters['where'] = "hf.asset_id=host.id AND hf.session_id='$session'";
}

try
{
    list($assets, $total) = Asset_host::get_full_list($conn, $tables, $filters);
    
}
catch(Exception $e)
{
    $response = array();
    
    $response['sEcho']                = $sec;
    $response['iTotalRecords']        = 0;
    $response['iTotalDisplayRecords'] = 0;
    $response['aaData']               = array();
    $response['iDisplayStart']        = 0;
    
    echo json_encode($response);    
    exit();
}

$detail  = '';
$results = array();

foreach($assets as $_id => $asset_data)
{
    $d_types = Asset_host_devices::get_devices_to_string($conn, $_id);
    if (preg_match('/<br\/>/', $d_types))
    {
        $d_types = preg_replace('/<br\/>.*/', '', $d_types) . '...';
    }
    
    // COLUMNS
    $_res = array();
    
    $_res['DT_RowId']  = $_id;
    
    $_res[] = '';  //Checkbox
    $_res[] = Util::htmlentities($asset_data['name']);  //Hostname
    $_res[] = Util::htmlentities(Asset::format_to_print($asset_data['ips']));  //IP
    $_res[] = $d_types;  //Device Type 
    $_res[] = $asset_data['os'];           //OS
    $_res[] = $asset_data['asset_value'];  //Asset Value
    $_res[] = $asset_data['vuln_scan'];    //Vulnerability Scan
    $_res[] = $asset_data['hids']; // HIDS status
    $_res[] = '';  //Detail


    $results[] = $_res;
}

// datatables response json
$response = array();

$response['sEcho']                = $sec;
$response['iTotalRecords']        = $total;
$response['iTotalDisplayRecords'] = $total;
$response['aaData']               = $results;
$response['iDisplayStart']        = 0;


echo json_encode($response);
