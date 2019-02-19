<?php
/**
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

Session::logcheck_ajax('environment-menu', 'PolicyHosts');

session_write_close();

$validate = array(
    'id'            =>  array('validation' => 'OSS_HEX',                                    'e_message'  =>  'illegal:' . _('Asset ID')),
    'ctx'           =>  array('validation' => 'OSS_HEX',                                    'e_message'  =>  'illegal:' . _('Context')),
    'is_in_db'      =>  array('validation' => 'OSS_BINARY',                                 'e_message'  =>  'illegal:' . _('Persistent')),
    'is_editable'   =>  array('validation' => 'OSS_LETTER, OSS_SCORE',                      'e_message'  =>  'illegal:' . _('Edit permission')),
    'ips[]'         =>  array('validation' => 'OSS_NULLABLE, OSS_SEVERAL_IP_ADDRCIDR_0',    'e_message'  =>  'illegal:' . _('IP addresses'))
);


$id                = POST('id');
$ctx               = POST('ctx');
$ips               = POST('ips');
$is_in_db          = POST('is_in_db');
$is_editable       = POST('is_editable');

$validation_errors = validate_form_fields('POST', $validate);


if (!empty($validation_errors))
{    
    Util::response_bad_request(_('Sorry, asset data was not loaded due to a validation error'));
}


$db   = new ossim_db();
$conn = $db->connect();

$asset = new Asset_host($conn, $id);

if ($is_in_db == 1)
{
    $asset->load_from_db($conn);
}
else
{
    //Set IP address and CTX for a new asset (Right menu from SIEM, Alarms, ...)
    if (!empty($ctx) && !empty($ips))
    {
        $asset->set_ctx($ctx);

        $ext_ips[$_ip] = array(
            'ip'   =>  $ips[0],
            'mac'  =>  NULL
        );

        $asset->set_ips($ext_ips);
    }
}

/*
echo '<pre>';
    print_r($asset);
echo '</pre>';
*/


//Getting asset data
$id          = $asset->get_id();
$name        = $asset->get_name();
$_ips        = $asset->get_ips();
$ips         = $_ips->get_ips();
$ips         = array_keys($ips);
$descr       = html_entity_decode($asset->get_descr(), ENT_QUOTES, 'UTF-8');
$fqdns       = $asset->get_fqdns();
$external    = $asset->get_external();
$location    = $asset->get_location();
$asset_value = $asset->get_asset_value();
$os_data     = $asset->get_os();
$os          = $os_data['value'];
$model       = $asset->get_model();


//CTX name
$ctx_name = (empty($ctx)) ? _('None') : Session::get_entity_name($conn, $ctx);
$ctx_name = Util::utf8_encode2($ctx_name);


//Icon
$icon = $asset->get_icon();
$icon = (!empty($icon)) ? 'data:image/png;base64,'.base64_encode($icon) : '';

//Server related to CTX
$server_obj = Server::get_server_by_ctx($conn, $ctx);

$s_name = '';
$s_ip   = '';

if ($server_obj)
{
    $s_name = $server_obj->get_name();
    $s_ip   = $server_obj->get_ip();
}

//Asset Sensors
$asset_sensors = $asset->get_sensors();
$sensors       = $asset_sensors->get_sensors();

//Asset Devices
$asset_devices = $asset->get_devices();
$_devices      = $asset_devices->get_devices();

$devices = array();

foreach($_devices as $dt_id => $dt_data)
{
    foreach($dt_data as $dst_id => $d_name)
    {
        $device_id  = $dt_id;
        $device_id .= ($dst_id > 0) ? ':'.$dst_id : '';

        $devices[$device_id] = $d_name;
    }
}

$data['status'] = 'OK';
$data['data']   = array(
    'is_in_db'      => $is_in_db,
    'is_editable'   => $is_editable,
    'id'            => $id,
    'ctx'           => array(
        'id'   => $ctx,
        'name' => $ctx_name,
        'related_server' => array(
            'ip'   => $s_ip,
            'name' => $s_name
        )
    ),
    'name'          => $name,
    'ip'            => implode(',', $ips),
    'descr'         => $descr,
    'external'      => $external,
    'fqdns'         => $fqdns,
    'asset_value'   => $asset_value,
    'location'      => $location,
    'icon'          => $icon,
    'os'            => $os,
    'model'         => $model,
    'sensors'       => $sensors,
    'devices'       => $devices    
);

$db->close();
echo json_encode($data);
