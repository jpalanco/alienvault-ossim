<?php
/**
* load_groups_result.php
* 
* File load_groups_result.php is used to:
* - Generate the json with networks to load the dataTable
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
*
*/

set_time_limit(300);

require_once 'av_init.php';

Session::logcheck('environment-menu', 'PolicyHosts');

/* connect to db */
$db   = new ossim_db(TRUE);
$conn = $db->connect();

//DataTables Pagination and search Params
$maxrows    = (POST('iDisplayLength') != '') ? POST('iDisplayLength') : 10;
$from       = (POST('iDisplayStart') != '')  ? POST('iDisplayStart') : 0;
$order      = (POST('iSortCol_0') != '')     ? POST('iSortCol_0') : "";
$torder     = POST('sSortDir_0');
$sec        = POST('sEcho');
$search     = utf8_decode(POST('search'));
$torder     = (!strcasecmp($torder, 'asc')) ? 0 : 1;

ossim_valid($maxrows,       OSS_DIGIT, 				   	                        'illegal: iDisplayLength');
ossim_valid($from, 			OSS_DIGIT,         			                        'illegal: iDisplayStart');
ossim_valid($order, 		OSS_ALPHA,       			                        'illegal: iSortCol_0');
ossim_valid($torder, 		OSS_DIGIT, 				                            'illegal: sSortDir_0');
ossim_valid($sec, 			OSS_DIGIT,				  	                        'illegal: sEcho');
ossim_valid($search,        OSS_NOECHARS, OSS_ALPHA, OSS_PUNC, OSS_NULLABLE,    'illegal: search');

if (ossim_error()) 
{
    echo ossim_get_error();
    $response['sEcho']                = intval($sec);
	$response['iTotalRecords']        = 0;
	$response['iTotalDisplayRecords'] = 0;
	$response['aaData']               = array();
	
	echo json_encode($response);
	exit;
}


$filters = array();

// Order by column
switch($order) 
{
	case 0:
		$order = 'g.name';
	break;
		
    default:
		$order = 'g.name';
}

// Order direction
$torder  = ($torder == 1) ? 'ASC' : 'DESC';

// Limit
$maxrows = ($maxrows > 50) ? 50 : $maxrows;

// Search where
if ($search != "")
{
    $search           = escape_sql($search, $conn);
    $filters['where'] = " g.name LIKE '%$search%' OR g.owner LIKE '%$search%'";
}

$filters['order_by'] = $order . ' ' . $torder;
$filters['limit']    = $from . ', ' . $maxrows;

try
{
    // Get Groups
    list($groups, $total) = Asset_group::get_list($conn, '', $filters, TRUE);


    $detail  = "<img class='detail_img' src='". AV_PIXMAPS_DIR ."/show_details.png'/>";
    $results = array();
    
    foreach($groups as $group)
    {
        // Alarms
        $alarms      = $group->has_alarms($conn);
        $alarms_icon = ($alarms) ? "<img src='". AV_PIXMAPS_DIR ."/assets_tick_gray.png'/>" : '-';
        
        // Vulns
        $vulns       = $group->get_vulnerability_number($conn, $group->get_id());
        $vulns_icon  = ($vulns > 0) ? "<img src='". AV_PIXMAPS_DIR ."/assets_tick_gray.png'/>" : '-';
        
        // Events
        $events      = $group->has_events($conn);
        $events_icon = ($events) ? "<img src='". AV_PIXMAPS_DIR ."/assets_tick_gray.png'/>" : '-';
        
        // COLUMNS
        $_res = array();
        
        $_res['DT_RowId'] = $group->get_id();
        
        $_res[] = Util::utf8_encode2($group->get_name());
        $_res[] = Util::utf8_encode2($group->get_owner());
        $_res[] = $group->get_num_host($conn);
        $_res[] = $alarms_icon;
        $_res[] = $vulns_icon;
        $_res[] = $events_icon;
        $_res[] = $detail;
    
        $results[] = $_res;
    }
    
}
catch(Exception $e)
{
    $response = array();
    
    $response['sEcho']                = intval($sec);
    $response['iTotalRecords']        = 0;
    $response['iTotalDisplayRecords'] = 0;
    $response['aaData']               = array();
    $response['iDisplayStart']        = 0;
    
    echo json_encode($response);
    
    die();
}


// datatables response json
$response = array();

$response['sEcho']                = intval($sec);
$response['iTotalRecords']        = $total;
$response['iTotalDisplayRecords'] = $total;
$response['aaData']               = $results;
$response['iDisplayStart']        = 0;


echo json_encode($response);

$db->close();

/* End of file load_groups_result.php */
/* Location: ./group/ajax/load_groups_result.php */
