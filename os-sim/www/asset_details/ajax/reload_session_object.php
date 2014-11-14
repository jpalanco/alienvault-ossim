<?php
/**
 * reload_session_object.php
 *
 * File reload_session_object.php is used to:
 * - Response ajax call from index.php
 * - Reload the session object used in the other ajax calls
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


$asset_id    = GET('asset_id');
$asset_type  = GET('asset_type');

$response['session_updated'] = FALSE;
$reloaded                    = FALSE;

ossim_valid($asset_id, 		OSS_HEX, 	'illegal: ' . _('Asset ID'));
ossim_valid($asset_type, 	OSS_ALPHA, 	'illegal: ' . _('Asset Type'));

if (ossim_error())
{      
    echo json_encode($response);
    exit();
}


$db   = new ossim_db();
$conn = $db->connect();


// Load the current asset object in session same as in index.php

// Host
if (Asset_host::is_in_db($conn, $asset_id))
{
    if (Asset_host::is_allowed($conn, $asset_id))
    {
        $asset_object = Asset_host::get_object($conn, $asset_id);
        $reloaded = TRUE;
    }
}
// Network
elseif (Asset_net::is_in_db($conn, $asset_id))
{
    if (Asset_net::is_allowed($conn, $asset_id))
    {
        $asset_object = Asset_net::get_object($conn, $asset_id);
        $reloaded = TRUE;
    }
}
// Asset Group
elseif (Asset_group::is_in_db($conn, $asset_id))
{
    $asset_object = Asset_group::get_object($conn, $asset_id);
    $reloaded = TRUE;
}

// Save session object only if is already saved (it means the user has perms)
if (!empty($_SESSION['asset_detail'][$asset_id]) && $reloaded)
{
    $_SESSION['asset_detail'][$asset_id] = serialize($asset_object);
    $response['session_updated'] = TRUE;
}
else
{
    $response['session_updated'] = FALSE;
}

echo json_encode($response);

$db->close();

/* End of file reload_session_object.php */
/* Location: ./asset_details/ajax/reload_session_object.php */