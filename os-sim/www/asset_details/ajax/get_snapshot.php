<?php
/**
 * get_snapshot.php
 * 
 * File get_snapshot.php is used to:
 * - Response ajax call from index.php
 * - Fill the data into asset details Snapshot section
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

$asset_id   = GET('asset_id');
$asset_type = GET('asset_type');

// Inic values
$response             = array();
$response['hosts']    = 0;
$response['software'] = 0;
$response['vulns']    = 0;
$response['alarms']   = _('No');
$response['events']   = _('No');

ossim_valid($asset_id, 		OSS_HEX, 	'illegal: ' . _('Asset ID'));
ossim_valid($asset_type, 	OSS_ALPHA, 	'illegal: ' . _('Asset Type'));

if (ossim_error()) 
{    
    echo json_encode($response);
	exit();
}


// Get object from session
$asset_object = unserialize($_SESSION['asset_detail'][$asset_id]);

if (!is_object($asset_object))
{
    throw new Exception(_("Error retrieving the asset data from memory"));
}


$db   = new ossim_db();
$conn = $db->connect();

$class_name = get_class($asset_object);

// DATA


// Network or Group Hosts
if ($asset_type == 'net' || $asset_type == 'group')
{
	$asset_hosts_data  = $asset_object->get_hosts($conn, array(), TRUE);
	$response['hosts'] = $asset_hosts_data[1];
}

// Software
$services_data        = $asset_object->get_services($conn);
$response['software'] = $services_data[1];

// Users
$users_data        = $asset_object->get_users($conn);
$response['users'] = $users_data[1];

// Vulns
$vuln_count        = $class_name::get_vulnerability_number($conn, $asset_id);
$response['vulns'] = $vuln_count;

// Alarms
list($alarms, $total) = $class_name::get_alarms($conn, $asset_id, 0, 1); // Just 1 result, we need the total count
$response['alarms']   = ($total > 0) ? _('Yes') : _('No');

// Events
$has_events         = Siem::has_events($conn, $asset_type, $asset_id);
$response['events'] = ($has_events) ? _('Yes') : _('No');

echo json_encode($response);

$db->close();

/* End of file get_snapshot.php */
/* Location: ./asset_details/ajax/get_snapshot.php */