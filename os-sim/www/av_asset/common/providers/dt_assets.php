<?php
/**
 * dt_assets.php
 *
 * File dt_assets.php is used to:
 *  - Build JSON data that will be returned in response to the Ajax request made by DataTables (Asset list)
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

$asset_id   =  POST('asset_id');
$asset_type =  POST('asset_type');

$maxrows    = (POST('iDisplayLength') != '') ? POST('iDisplayLength') : 8;
$from       = (POST('iDisplayStart') != '')  ? POST('iDisplayStart')  : 0;

$order      = (POST('iSortCol_0') != '')     ? POST('iSortCol_0')     : '';
$torder     =  POST('sSortDir_0');
$search_str = (POST('sSearch') != '')        ? POST('sSearch') : '';

$sec        =  POST('sEcho');

Session::logcheck_by_asset_type($asset_type);

// Close session write for real background loading
session_write_close();

ossim_valid($asset_id,      OSS_HEX,                                  'illegal: '._('Asset ID'));
ossim_valid($asset_type,    OSS_LETTER, OSS_SCORE, OSS_NULLABLE,      'illegal: '._('Asset Type'));
ossim_valid($maxrows,       OSS_DIGIT,                                'illegal: iDisplayLength');
ossim_valid($from,          OSS_DIGIT,                                'illegal: iDisplayStart');
ossim_valid($order,         OSS_ALPHA,                                'illegal: iSortCol_0');
ossim_valid($torder,        OSS_LETTER,                               'illegal: sSortDir_0');
ossim_valid($search_str,    OSS_INPUT, OSS_NULLABLE,                  'illegal: sSearch');
ossim_valid($sec,           OSS_DIGIT,                                'illegal: sEcho');


if (ossim_error())
{
    Util::response_bad_request(ossim_get_error_clean());
}


// Check Asset Type
$asset_types = array(
    'network' => 'Asset_net',
    'group'   => 'Asset_group'
);


// Order by column
$orders_by_columns = array(
    '1' => 'host.hostname', // Order by Hostname
    '2' => 'hi.ip',         // Order by IP
    '4' => 'os',            // Order by Operating System
    '5' => 'host.asset',    // Order by Asset Value
    '6' => 'vuln',          // Order by Vulnerabilities
    '7' => 'hids'           // Order by HIDS
);


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

        $asset_object = $class_name::get_object($conn, $asset_id);

        if (array_key_exists($order, $orders_by_columns))
        {
            $order = $orders_by_columns[$order];
        }
        else
        {
            $order = 'host.hostname';
        }

        // Property filter
        $filters = array(
            'limit'    => "$from, $maxrows",
            'order_by' => "$order $torder"
        );

        if ($search_str != '')
        {
            $search_str       = escape_sql($search_str, $conn);
            $filters['where'] = 'host.hostname LIKE "%'.$search_str.'%"';
        }

        list($asset_list, $total) = $asset_object->get_hosts($conn, '', $filters, FALSE);
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

foreach ($asset_list as $_id => $asset_data)
{
    $d_types = Asset_host_devices::get_devices_to_string($conn, $_id);
    if (preg_match('/<br\/>/', $d_types))
    {
        $d_types = preg_replace('/<br\/>.*/', '', $d_types) . '...';
    }

    // COLUMNS
    $_res = array();

    $_res['DT_RowId']  = $_id;

    $_res[] = '';  //Checkbox
    $_res[] = Util::htmlentities($asset_data['name']);  //Hostname
    $_res[] = Util::htmlentities(Asset::format_to_print($asset_data['ips']));  //IP
    $_res[] = $d_types;  //Device Type
    $_res[] = $asset_data['os'];           //OS
    $_res[] = $asset_data['asset_value'];  //Asset Value
    $_res[] = $asset_data['vuln_scan'];    //Vulnerability Scan
    $_res[] = $asset_data['hids']; // HIDS
    $_res[] = ''; //Action

    $data[] = $_res;
}



$response['sEcho']                = $sec;
$response['iTotalRecords']        = $total;
$response['iTotalDisplayRecords'] = $total;
$response['aaData']               = $data;

echo json_encode($response);

$db->close();

/* End of file dt_assets.php */
/* Location: /av_asset/common/providers/dt_assets.php */
