<?php
/**
* get_asset_info.php
*
* File get_asset_info.php is used to:
*  - Build JSON data that will be returned in response to the Ajax request made by Asset detail (Asset data)
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

function get_asset_info($conn, $asset_id)
{
    $asset = Asset_host::get_object($conn, $asset_id);

    //Asset Type
    $asset_type    = ($asset->get_external()) ? _('External') : _('Internal');

    //Asset IPs
    $asset_ips     = $asset->get_ips();
    $ips           = $asset_ips->get_ips();

    //Asset Sensors
    $asset_sensors = $asset->get_sensors();
    $sensors       = $asset_sensors->get_sensors();

    //Asset Nets
    $networks      = $asset->get_nets($conn);

    //Asset Devices
    $asset_devices = $asset->get_devices();
    $devices       = array();
    foreach($asset_devices->get_devices() as $dt_id => $dt_data)
    {
        foreach($dt_data as $dst_id => $d_name)
        {
            $device_id  = $dt_id;
            $device_id .= ($dst_id > 0) ? ': '.$dst_id : '';

            $devices[$device_id] = $d_name;
        }
    }
    
    $os_data = $asset->get_os();

    $data = array(
        'id'            => $asset_id,
        'hostname'      => $asset->get_name(),
        'ips'           => $ips,
        'descr'         => html_entity_decode($asset->get_descr(), ENT_QUOTES, 'UTF-8'),
        'asset_type'    => $asset_type,
        'fqdn'          => $asset->get_fqdns(),
        'asset_value'   => $asset->get_asset_value(),
        'icon'          => base64_encode($asset->get_icon()),
        'os'            => $os_data['value'],
        'model'         => $asset->get_model(),
        'sensors'       => $sensors,
        'networks'      => $networks,
        'devices'       => $devices
    );

    return $data;
}


function get_group_info($conn, $group_id)
{
    $group = Asset_group::get_object($conn, $group_id);

    $data = array(
        'id'    => $group_id,
        'name'  => $group->get_name(),
        'owner' => $group->get_owner(),
        'descr' => html_entity_decode($group->get_descr(), ENT_QUOTES, 'UTF-8'),
    );

    return $data;
}


function get_network_info($conn, $net_id)
{
    $net = Asset_net::get_object($conn, $net_id);

    //Asset IPs
    $cidrs       = $net->get_ips('array');

    //Asset Sensors
    $net_sensors = $net->get_sensors();
    $sensors     = $net_sensors->get_sensors();

    $data = array(
        'id'          => $net_id,
        'name'        => $net->get_name(),
        'owner'       => $net->get_owner(),
        'descr'       => html_entity_decode($net->get_descr(), ENT_QUOTES, 'UTF-8'),
        'cidrs'       => $cidrs,
        'asset_value' => $net->get_asset_value(),
        'icon'        => base64_encode($net->get_icon()),
        'sensors'     => $sensors
    );

    return $data;
}

// Check Asset Type
$asset_types = array(
    'asset'   => 'Asset_host',
    'network' => 'Asset_net',
    'group'   => 'Asset_group'
);


if (!valid_hex32($asset_id))
{
    Util::response_bad_request(_('Sorry, asset data was not loaded due to a validation error'));
}


try
{
    $db   = new ossim_db(TRUE);
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

        //Executing Data Function
        $function = 'get_' . $asset_type . '_info';
        $data     = $function($conn, $asset_id);
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


$db->close();
echo json_encode($data);

/* End of file get_asset_info.php */
/* Location: /av_asset/common/providers/get_asset_info.php */
