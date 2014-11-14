<?php
/**
 * get_hids.php
 * 
 * File get_hids.php is used to:
 * - Response ajax call from index.php
 * - Fill the data into asset details HIDS color led
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

// Close session write for real background loading
session_write_close();

$db   = new Ossim_db();
$conn = $db->connect();

$asset_id = GET('asset_id');

ossim_valid($asset_id, OSS_HEX, 'illegal: Asset ID');

if (ossim_error()) 
{	
	echo 'gray';
	
	$db->close();
	
	exit();
}

// Get object from session
$asset_object = unserialize($_SESSION['asset_detail'][$asset_id]);

if (!is_object($asset_object))
{
    echo 'gray';
    
    Av_exception::write_log(Av_exception::USER_ERROR, _('Error retrieving the asset data from Memory'));
}

// DATA

try
{
    $hids = $asset_object->is_hids_enabled($conn);
    $hids = intval($hids);

    if ($hids == 2)
    {
        echo 'yellow';
    }
    elseif ($hids == 1)
    {
        echo 'green';
    }
    else
    {
        echo 'red';
    }
}
catch(Exception $e)
{
    echo 'gray';
}


$db->close();

/* End of file get_hids.php */
/* Location: ./asset_details/ajax/get_hids.php */