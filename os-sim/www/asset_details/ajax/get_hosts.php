<?php
/**
 * get_hosts.php
 * 
 * File get_hosts.php is used to:
 * - Response ajax call from index.php by dataTable jquery plugin
 * - Fill the data into asset details Hosts section, when Network mode of details
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

require_once 'av_init.php';
Session::logcheck("environment-menu", "PolicyHosts");

// Close session write for real background loading
session_write_close();

$group_id   = GET('group_id');
$asset_type = GET('asset_type');
$maxrows    = (POST('iDisplayLength') != '') ? POST('iDisplayLength')   : 15;
$search_str = (POST('sSearch') != '')        ? POST('sSearch')          : '';
$from       = (POST('iDisplayStart') != '')  ? POST('iDisplayStart')    : 0;
$order      = (POST('iSortCol_0') != '')     ? POST('iSortCol_0')       : '';
$torder     = POST('sSortDir_0');
$sec        = POST('sEcho');

switch($order) 
{
	case 0:
		$order = 'hostname';
	break;
	/*
	case 1:
		$order = 'ip';
		break;
	*/
	default:
		$order = 'hostname';
	break;

}
/*
if ($order == 'ip')
{
	$order = "host_ip.ip";
}
*/

$torder = (!strcasecmp($torder, 'asc')) ? 'asc' : 'desc';

ossim_valid($group_id, 		OSS_HEX, 				   	   'illegal: ' . _('Net or Group ID'));
ossim_valid($asset_type, 	OSS_ALPHA, 				   	   'illegal: ' . _('Asset Type'));
ossim_valid($maxrows, 		OSS_DIGIT, 				   	   'illegal: ' . _('Maxrows Param'));
ossim_valid($search_str, 	OSS_INPUT, OSS_NULLABLE,   	   'illegal: ' . _('Search String'));
ossim_valid($from, 			OSS_DIGIT,         			   'illegal: ' . _('From Param'));
ossim_valid($order, 		OSS_ALPHA, OSS_DOT, OSS_SCORE, 'illegal: ' . _('Order Param'));
ossim_valid($torder, 		OSS_ALPHA, 				       'illegal: ' . _('tOrder Param'));
ossim_valid($sec, 			OSS_DIGIT,				  	   'illegal: ' . _('sec Param'));

if (ossim_error()) 
{
    $response['sEcho']                = intval($sec);
	$response['iTotalRecords']        = 0;
	$response['iTotalDisplayRecords'] = 0;
	$response['aaData']               = array();
	
	echo json_encode($response);
	exit();
}

$db   = new ossim_db();
$conn = $db->connect();

$filters = array(
    'limit'    => "$from, $maxrows",
    'order_by' => "$order $torder"
);

if ($search_str != '')
{
    $filters['where'] = 'hostname LIKE "%'.$search_str.'%"';
}

// Get object from session
$asset_object = unserialize($_SESSION['asset_detail'][$group_id]);

if (!is_object($asset_object))
{
    throw new Exception(_('Error retrieving the asset data from memory'));
}

// Get the hosts from another groups
if ($asset_type == 'othergroups')
{
    $where                   = " id NOT IN (SELECT host_id FROM host_group_reference WHERE host_group_id = UNHEX('". $group_id ."')) ";
    $filters['where']        = (!empty($filters['where'])) ? $where . ' AND ' . $filters['where'] : $where;
    list($host_list, $total) = Asset_host::get_list($conn, '', $filters, $cache);
}
// Hosts from the Network / Group
else
{
    list($host_list, $total) = $asset_object->get_hosts($conn, $filters, FALSE);
}

// DATA
$data = array();

foreach ($host_list as $host_id => $host_data)
{
    $devices = Asset_host_devices::get_devices_to_string($conn, $host_id);
    
    // Asset Group details format
	if ($asset_type == 'group')
	{
	   
    	try
    	{
        	$asset_object->can_i_edit($conn);
        	
            $asset_object->can_delete_host($conn);
        	
        	$delete_link  = '<a href="javascript:;" onclick="del_asset_from_group(\''. $host_id .'\');return false">';
        	$delete_link .= '<img class="delete_small tipinfo" txt="'. _('Remove this asset from group') . '" src="/ossim/pixmaps/delete.png" border="0"/>';
        	$delete_link .= '</a>';
        	
    	}
    	catch(Exception $e)
    	{
        	$title       = $e->getMessage();
        	$delete_link = '<img class="delete_small img_disabled tipinfo" txt="'.$title.'" src="/ossim/pixmaps/delete.png" border="0"/>';
    	}
	    
	    $data[] = array(
	            $host_data['name'],
	            $host_data['ips'],
	            $host_data['fqdns'],
	            $devices,
	            Util::utf8_encode2($host_data['descr']),
	            $delete_link
	    );
	}
	// Network details format
	elseif ($asset_type == 'net')
	{
	    $data[] = array(
	            $host_data['name'],
	            $host_data['ips'],
	            $host_data['fqdns'],
	            $devices,
	            Util::utf8_encode2($host_data['descr'])
	    );
	}
	// Add Assets popup format from Group details
	elseif ($asset_type == 'othergroups')
	{
	    $checkbox = "<input type='checkbox' id='check_$host_id' class='check_host' value='1'/>";
	    
	    $data[] = array(
	            $checkbox,
	            $host_data['name'],
	            '',
	            $host_data['ips'],
	            $devices,
	            $host_data['fqdns']
	    );
	}
}								

$response['sEcho']                = $sec;
$response['iTotalRecords']        = $total;
$response['iTotalDisplayRecords'] = $total;
$response['aaData']               = $data;

echo json_encode($response);							

$db->close();

/* End of file get_hosts.php */
/* Location: ./asset_details/ajax/get_hosts.php */