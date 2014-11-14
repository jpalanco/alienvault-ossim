<?php
/**
 * remove_group_asset.php
 * 
 * File remove_group_asset.php is used to:
 * - Response ajax call from index.php
 * - Unlink a host from the Asset Group
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


$group_id = GET('group_id');
$asset_id = GET('asset_id');


$response = array();
$response['success'] = FALSE;
$response['msg']     = '';

ossim_valid($group_id, OSS_HEX, 'illegal: ' . _('Group ID'));
ossim_valid($asset_id, OSS_HEX, 'illegal: ' . _('Asset ID'));

if (ossim_error())
{
    $response['msg'] = ossim_get_error();
    
    ossim_clean_error();
    
	echo json_encode($response);
	
	exit();
}

$db   = new ossim_db();
$conn = $db->connect();

// Get object from session
$asset_object = unserialize($_SESSION['asset_detail'][$group_id]);

if (!is_object($asset_object))
{
    $response['msg'] = _("Error loading the group from memory");
    
    echo json_encode($response);
    
    exit();
}

try
{
    $asset_object->can_i_edit($conn);
    
    $asset_object->delete_host($conn, $asset_id);
    
    $response['success'] = TRUE;
}
catch(Exception $e)
{
    $response['msg'] = $e->getMessage();    
}

echo json_encode($response);

$db->close();

/* End of file remove_group_asset.php */
/* Location: ./asset_details/ajax/remove_group_asset.php */