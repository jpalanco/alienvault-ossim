<?php
/**
 * dt_properties.php
 *
 * File dt_properties.php is used to:
 *  - Build JSON data that will be returned in response to the Ajax request made by DataTables (Property list)
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
    'asset'   => 'Asset_host',
    'network' => 'Asset_net',
    'group'   => 'Asset_group'
);

// Order by column
$orders_by_columns = array(
    '2' => 'property_descr', // Order by Property
    '3' => 'value',          // Order by Value
    '4' => 'date',           // Order by Date
    '5' => 'source_name'     // Order by Source Name
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


        // Property filter
        if (array_key_exists($order, $orders_by_columns))
        {
            $order = $orders_by_columns[$order];
        }
        else
        {
            $order = 'date';
        }

        $filters = array(
            'limit'    => "$from, $maxrows",
            'order_by' => "$order $torder"
        );
        
        $filters['response_type'] = 'by_ref';

        if ($search_str != '')
        {
            $search_str       = escape_sql($search_str, $conn);
            $filters['where'] = 'value LIKE "%'.$search_str.'%"';
        }

        list($p_list, $p_total) = $asset_object->get_properties($conn, $filters);
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

//Distinct Host IDs with properties
$assets_with_properties = array();

// Properties data
$data = array();

foreach ($p_list as $p_id => $prop_data)
{
    foreach ($prop_data as $_asset_id => $p_values)
    {
        if (array_key_exists($_asset_id, $assets_with_properties))
        {
            $h_display = $assets_with_properties[$_asset_id];
        }
        else
        {
            $_host     = Asset_host::get_object($conn, $_asset_id);
            $h_display = array($_host->get_name(), $_host->get_ips()->get_ips('string'));
            
            $assets_with_properties[$_asset_id] = $h_display;
        }
        
        foreach ($p_values as $p_value)
        {
            $r_key    = strtolower($_asset_id.'_'.$p_id.'_'.md5($p_value['value']));
            $p_locked = ($p_value['source']['id'] == 1) ? 1 : 0;
            $ip_value = ($p_id == 50) ? $h_display[0] . ' ('.$p_value['extra'].')' : $h_display[0] . ' (' . $h_display[1] . ')';

            $_p_data = array(
                "DT_RowId"   => $r_key,
                "DT_RowData" => array(
                    'p_id'      => $p_id,
                    'p_value'   => $p_value['value'],
                    'source_id' => $p_value['source']['id'],
                    'locked'    => $p_locked,
                    'extra'     => $p_value['extra']
                ),
                "",
                $ip_value,
                $p_value['description'],
                $p_value['value'],
                $p_value['date'],
                _(ucfirst(strtolower(($p_value['source']['name'])))),
                ""
            );

            $data[] = $_p_data;
        }
    }
}


$response['sEcho']                = $sec;
$response['iTotalRecords']        = $p_total;
$response['iTotalDisplayRecords'] = $p_total;
$response['aaData']               = $data;

echo json_encode($response);

$db->close();

/* End of file dt_properties.php */
/* Location: /av_asset/common/providers/dt_properties.php */
