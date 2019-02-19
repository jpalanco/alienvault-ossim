<?php
/**
 * get_asset_tags.php
 *
 * File get_asset_tags.php is used to:
 *  - Build JSON data that will be returned in response to the Ajax request made by Asset detail (Labels)
 *
 * License:
 *
 * Copyright (c) 2003-2006 ossim.net
 * Copyright (c) 2007-2015 AlienVault
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

$asset_id   = POST('asset_id');
$asset_type = POST('asset_type');

/**********************
 ****** Log Check *****
 **********************/

Session::logcheck_by_asset_type($asset_type);

session_write_close();


/**********************
 ****** Functions *****
 **********************/

/**
 * @param $conn
 * @param $asset_id
 *
 * @return array
 */
function get_tags($conn, $asset_id)
{
    // Data array
    $data = array();

    $tags = Tag::get_tags_by_component($conn, $asset_id);

    foreach ($tags as $tag_id => $tag)
    {
        $result = array();
        $result['id'] = $tag_id;
        $result['name'] = $tag->get_name();
        $result['class'] = $tag->get_class();

        $data[$tag_id] = $result;
    }

    return $data;
}


/**
 * @param $conn
 * @param $asset_id
 *
 * @return array
 */
function get_asset_tags($conn, $asset_id)
{
    if (!Asset_host::is_allowed($conn, $asset_id))
    {
        $error = _('Asset Not Allowed');
        Util::response_bad_request($error);
    }

    return get_tags($conn, $asset_id);
}


function get_group_tags()
{
    return array();
}


function get_network_tags()
{
    return array();
}


// Check Asset Type
$asset_types = array(
    'asset'   => 'asset_host',
    'network' => 'asset_net',
    'group'   => 'asset_group'
);

if (!valid_hex32($asset_id))
{
    Util::response_bad_request(_('Sorry, asset data was not loaded due to a validation error'));
}

try
{
    $db   = new Ossim_db();
    $conn = $db->connect();

    if (isset($_POST['asset_id']) && isset($_POST['asset_type']))
    {
        if (!array_key_exists($asset_type, $asset_types))
        {
            Av_exception::throw_error(Av_exception::USER_ERROR, _('Error! Invalid Asset Type'));
        }

        $class_name = $asset_types[$_POST['asset_type']];

        // Check Asset Permission
        if (method_exists($class_name, 'is_allowed') && !$class_name::is_allowed($conn, $asset_id))
        {
            $error = sprintf(_('Error! %s is not allowed'), ucwords($asset_type));

            Av_exception::throw_error(Av_exception::USER_ERROR, $error);
        }

        $function = 'get_' . $asset_type . '_tags';
        $data     = $function($conn, $asset_id);
    }
    else
    {
        Av_exception::throw_error(Av_exception::USER_ERROR, _('Error retrieving asset information'));
    }
}
catch(Exception $e)
{
    $db->close();

    Util::response_bad_request($e->getMessage());
}


$db->close();

echo json_encode($data);

/* End of file get_asset_tags.php */
/* Location: /av_asset/common/providers/get_asset_tags.php */
