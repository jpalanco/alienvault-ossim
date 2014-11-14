<?php
/**
 * get_alarms.php
 * 
 * File get_alarms.php is used to:
 * - Response ajax call from index.php by dataTable jquery plugin
 * - Fill the data into asset details Alarms section
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

Session::logcheck('environment-menu', 'PolicyHosts');

$geoloc = new Geolocation('/usr/share/geoip/GeoLiteCity.dat');

$asset_id   = GET('asset_id');
$maxrows    = (POST('iDisplayLength') != '') ? POST('iDisplayLength') : 15;
$search_str = (POST('sSearch') != '') ? POST('sSearch') : '';
$from       = (POST('iDisplayStart') != '') ? POST('iDisplayStart') : 0;
$order      = (POST('iSortCol_0') != '') ? POST('iSortCol_0') : '';
$torder     = POST('sSortDir_0');
$sec        = POST('sEcho');

$no_resolv  = FALSE;

switch($order)
{
	case 1:
		$order = "ki.name $torder, ca.name $torder, a.timestamp $torder";
	break;
	
	case 2:
		$order = " ta.subcategory $torder, a.timestamp $torder";
	break;
		
	case 5:
		$order = "a.timestamp $torder";
	break;
	
	default:
		$order = "a.timestamp $torder";
	break;
}

ossim_valid($asset_id, 		OSS_HEX, 				   	                    'illegal: ' . _('Asset ID'));
ossim_valid($maxrows, 		OSS_DIGIT, 				   	                    'illegal: ' . _('Configuration Parameter 1'));
ossim_valid($search_str, 	OSS_INPUT, OSS_NULLABLE,   	                    'illegal: ' . _('Search String'));
ossim_valid($from, 			OSS_DIGIT,         			                    'illegal: ' . _('Configuration Parameter 2'));
ossim_valid($order, 		OSS_ALPHA, OSS_DOT, OSS_SCORE, OSS_SPACE, '\,', 'illegal: ' . _('Configuration Parameter 3'));
ossim_valid($torder, 		OSS_ALPHA, 				                        'illegal: ' . _('Configuration Parameter 4'));
ossim_valid($sec, 			OSS_DIGIT,  	                                'illegal: ' . _('Configuration Parameter 5'));


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

// Get object from session
$asset_object = unserialize($_SESSION['asset_detail'][$asset_id]);

if (!is_object($asset_object))
{
    throw new Exception(_('Error retrieving the asset data from memory'));
}

$class_name = get_class($asset_object);

list($alarms, $total) = $class_name::get_alarms($conn, $asset_id, $from, $maxrows, '', '', $search_str, $order);

// DATA
$data = array();

foreach ($alarms as $alarm)
{
	// Alarm description
	list($alarm_ik, $alarm_sc) = Alarm::get_alarm_name($alarm->get_taxonomy());
	
	// Src Dst
	$src_ip     = $alarm->get_src_ip();
	$dst_ip     = $alarm->get_dst_ip();
	$event_info = $alarm->get_event_info();
	$src_host   = Asset_host::get_object($conn, $event_info["src_host"]);
	$dst_host   = Asset_host::get_object($conn, $event_info["dst_host"]);
	$src_net_id = $event_info["src_host"];
	$dst_net_id = $event_info["dst_host"];
	
	// Src
	if ($no_resolv || !$src_host)
	{
		$src_name = $src_ip;
		$src_desc = '';
		$ctx_src  = $ctx;
	}
	elseif ($src_host)
	{
		$src_desc = ($src_host->get_descr() != '') ? ": ".$src_host->get_descr() : '';
		$src_name = $src_host->get_name();
		$ctx_src  = $src_host->get_ctx();
		$src_link = $refresh_url_nopage."&host_id=".$src_host->get_id();
	}
	
	// Dst
	if ($no_resolv || !$dst_host)
	{
		$dst_name = $dst_ip;
		$dst_desc = '';
		$ctx_dst  = $ctx;
	}
	elseif ($dst_host)
	{
		$dst_desc = ($dst_host->get_descr() != '') ? ": ".$dst_host->get_descr() : '';
		$dst_name = $dst_host->get_name();
		$ctx_dst  = $dst_host->get_ctx();
		$dst_link = $refresh_url_nopage."&host_id=".$dst_host->get_id();
	}
	
	// Src icon and bold
	$src_output  = Asset_host::get_extended_name($conn, $geoloc, $src_ip, $ctx_src, $event_info["src_host"], $event_info["src_net"]);
	$homelan_src = $src_output['is_internal'];
	$src_img     = preg_replace("/scriptinfo/", '', $src_output['html_icon']); // Clean icon hover tiptip
	
	// Dst icon and bold
	$dst_output  = Asset_host::get_extended_name($conn, $geoloc, $dst_ip, $ctx_dst, $event_info["dst_host"], $event_info["dst_net"]);
	$homelan_dst = $dst_output['is_internal'];
	$dst_img     = preg_replace("/scriptinfo/", '', $dst_output['html_icon']); // Clean icon hover tiptip
	
	//host report menu:
	$src_hrm = "$src_ip;$src_name;".$event_info['src_host'];
	$dst_hrm = "$dst_ip;$dst_name;".$event_info['dst_host'];
	
	$data[]= array(
		$alarm->get_timestamp(),
		$alarm->get_status(),
		str_replace("style/", "../alarm/style/", $alarm_ik),
		$alarm_sc,
		$alarm->get_risk(),
		"<div class='HostReportMenu' id='$src_hrm'>".$src_img . (($homelan_src) ? " <strong>$src_name$src_port</strong> $rep_src_icon" : " $src_name$src_port $rep_src_icon")."</div>",
		"<div class='HostReportMenu' id='$dst_hrm'>".$dst_img . (($homelan_dst) ? " <strong>$dst_name$dst_port</strong> $rep_dst_icon" : " $dst_name$dst_port $rep_dst_icon")."</div>"
	);
}								

$response['sEcho']                = $sec;
$response['iTotalRecords']        = $total;
$response['iTotalDisplayRecords'] = $total;
$response['aaData']               = $data;

echo json_encode($response);							

$db->close();
$geoloc->close();

/* End of file get_alarms.php */
/* Location: ./asset_details/ajax/get_alarms.php */