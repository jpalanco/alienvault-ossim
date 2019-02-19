<?php
/**
* get_asset_environment.php
*
* File get_asset_environment.php is used to:
*  - Build JSON data that will be returned in response to the Ajax request made by Asset detail (Environment Status)
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

$asset_id   = POST('asset_id');
$asset_type = POST('asset_type');

Session::logcheck_by_asset_type($asset_type);

session_write_close();

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
    $db   = new Ossim_db(TRUE);
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

        $asset_object = $class_name::get_object($conn, $asset_id);
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

$data = array();


/*
 *  Getting Auto Detected Info
 */
try
{
    $is_autodetected = intval($asset_object->is_autodetected($conn));

    switch ($is_autodetected)
    {
        case 0:
            $a_level = 'red';
        break;

        case 1:
            $a_level = 'green';
        break;

        case 2:
            $a_level = 'yellow';
        break;

        default;
            $a_level = 'gray';
    }
}
catch (Exception $e)
{
    $a_level = 'gray';
}

$autodetected = array(
    'level' => $a_level,
    'link'  => array(
        AV_MAIN_PATH . '/av_schedule_scan/views/list.php?s_type=nmap',
        'environment',
        'assets',
        'scheduler'
    )
);


/*
 *  Getting HIDS Info
 */
try
{
    $is_hids = $asset_object->get_hids_status($conn);

    switch (intval($is_hids))
    {
        case 0:
            $h_level = 'gray';
        break;

        case 1:
            $h_level = 'red';
        break;

        case 2:
            $h_level = 'yellow';
        break;

        case 3:
            $h_level = 'green';
        break;

        default;
            $h_level = 'gray';
    }
}
catch (Exception $e)
{
    $h_level = 'gray';
}

//Setting default sensor
if ($asset_type == 'group')
{
    $sensors = $asset_object->get_sensors($conn);
}
else
{
    $sensors = $asset_object->get_sensors()->get_sensors();
}

$sensors = array_keys($sensors);
$default_sensor = $sensors[0];

$hids = array(
    'level' => $h_level,
    'link'  => array(
        AV_MAIN_PATH . '/ossec/views/ossec_status/status.php?sensor_id='.$default_sensor,
        'environment',
        'detection',
        'hids'
    )
);


/*
 *  Getting Vulnerabilities Info
 */
try
{
    $is_vulns = Vulnerabilities::is_scheduled($conn, $asset_id);
    $v_level  = ($is_vulns) ? 'green' : 'red';
}
catch (Exception $e)
{
    $vulnerabilities = 'gray';
}

$vulnerabilities = array(
    'level' => $v_level,
    'link'  => array(
        AV_MAIN_PATH . '/vulnmeter/manage_jobs.php',
        'environment',
        'vulnerabilities',
        'scan_jobs'
    )
);


$data = array(
    'nmap'            => $autodetected,
    'hids'            => $hids,
    'vulnerabilities' => $vulnerabilities
);


$db->close();

echo json_encode($data);


/* End of file get_asset_environment.php */
