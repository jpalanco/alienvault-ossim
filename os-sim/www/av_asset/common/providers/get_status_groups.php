<?php
/**
* get_status_assets.php
*
* File get_status_assets.php is used to:
* - Build JSON data that will be returned in response to the Ajax request made by Asset Tray (Group info)
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

$asset_id   =  POST('asset_id');
$asset_type =  POST('asset_type');

Session::logcheck_by_asset_type($asset_type);

session_write_close();

function get_asset_groups($conn, $asset_id)
{
    if (!Asset_host::is_allowed($conn, $asset_id))
    {
        $error = _('Asset Not Allowed');
        Util::response_bad_request($error);
    }

    try
    {
        $asset = Asset_host::get_object($conn, $asset_id);
        $num   = $asset->get_num_group($conn);
    }
    catch (Exception $e)
    {
        $num = '-';
    }

    return $num;
}


if (!valid_hex32($asset_id))
{
    Util::response_bad_request(_('Sorry, asset data was not loaded due to a validation error'));
}

// Check Asset Type
$asset_types = array(
    'asset'   => 'Asset_host',
    'network' => 'Asset_net',
    'group'   => 'Asset_group'
);


try
{
    $db   = new Ossim_db(TRUE);
    $conn = $db->connect();

    if ($asset_id && $asset_type)
    {
        if (!array_key_exists($asset_type, $asset_types))
        {
            Av_exception::throw_error(Av_exception::USER_ERROR, _('Error! Invalid Asset Type'));
        }

        $class_name = $asset_types[$asset_type];

        // Check Asset Permission
        if (method_exists($class_name, 'is_allowed') && !$class_name::is_allowed($conn, $asset_id))
        {
            $error = sprintf(_('Error! %s is not allowed'), ucwords($asset_type));

            Av_exception::throw_error(Av_exception::USER_ERROR, $error);
        }

        if ($asset_type != 'asset')
        {
            $total_groups = '-';
        }
        else
        {
            $total_groups = get_asset_groups($conn, $asset_id);
        }
    }
    else
    {
        Av_exception::throw_error(Av_exception::USER_ERROR, _('Error retrieving information'));
    }
}
catch(Exception $e)
{
    $db->close();

    Util::response_bad_request($e->getMessage());
}


$tooltip = sprintf(_("This asset belongs to %d group(s)"), $total_groups);


$data = array(
    'value'   => $total_groups,
    'level'   => 0,
    'tooltip' => $tooltip 
);

$db->close();
echo json_encode($data);

/* End of file get_status_groups.php */
/* Location: /av_asset/common/providers/get_status_groups.php */
