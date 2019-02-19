<?php
/**
 * bk_dt_properties.php
 *
 * File dt_properties.php is used to:
 * - Response ajax call from properties dataTable
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


Session::logcheck_ajax('environment-menu', 'PolicyHosts');

// Close session write for real background loading
session_write_close();


$maxrows    = (POST('iDisplayLength') != '') ? POST('iDisplayLength') : 8;
$from       = (POST('iDisplayStart') != '')  ? POST('iDisplayStart')  : 0;

$order      = (POST('iSortCol_0') != '')     ? POST('iSortCol_0')     : '';
$torder     =  POST('sSortDir_0');
$search_str = (POST('sSearch') != '')        ? POST('sSearch') : '';

$sec        =  POST('sEcho');


ossim_valid($maxrows,       OSS_DIGIT,                  'illegal: iDisplayLength');
ossim_valid($from,          OSS_DIGIT,                  'illegal: iDisplayStart');
ossim_valid($order,         OSS_ALPHA,                  'illegal: iSortCol_0');
ossim_valid($torder,        OSS_LETTER,                 'illegal: sSortDir_0');
ossim_valid($search_str,    OSS_INPUT, OSS_NULLABLE,    'illegal: sSearch');
ossim_valid($sec,           OSS_DIGIT,                  'illegal: sEcho');


if (ossim_error())
{
    Util::response_bad_request(ossim_get_error_clean());
}


$db   = new ossim_db();
$conn = $db->connect();


// Order by column
$orders_by_columns = array(
    '1' => 'property_descr', // Order by Property Type
    '2' => 'value',          // Order by Value
    '3' => 'source_name'     // Order by Source Name
);

if (array_key_exists($order, $orders_by_columns))
{
    $order = $orders_by_columns[$order];
}
else
{
    $order = 'property_descr';
}


// Property filter
$filters = array(
    'where'    => "hp.property_ref NOT IN (3, 14)",
    'limit'    => "$from, $maxrows",
    'order_by' => "$order $torder"
);

if ($search_str != '')
{
    $search_str        = escape_sql($search_str, $conn);
    $filters['where'] .= ' AND value LIKE "%'.$search_str.'%"';
}


// Properties data
$data    = array();
$p_list  = array();
$p_total = 0;

list($p_list, $p_total) = Asset_host_properties::bulk_get_list($conn, $filters);

foreach ($p_list as $p_id => $p_values)
{
    foreach ($p_values as $p_value)
    {
        $r_key    = strtolower($p_id.'_'.md5($p_value['value']));
        $p_locked = ($p_value['source']['id'] == 1) ? 1 : 0;

        $_p_data = array(
            "DT_RowId"   => $r_key,
            "DT_RowData"    => array(
                'p_id'      => $p_id,
                'p_value'   => $p_value['value'],
                'source_id' => $p_value['source']['id'],
                'locked'    => $p_locked,
            ),
            "",
            $p_value['description'],
            $p_value['value'],
            $p_value['source']['name'],
            ""
        );

        $data[] = $_p_data;
    }
}


$response['sEcho']                = $sec;
$response['iTotalRecords']        = $p_total;
$response['iTotalDisplayRecords'] = $p_total;
$response['aaData']               = $data;

echo json_encode($response);

$db->close();

/* End of file bk_dt_properties.php */
/* Location: /av_asset/common/providers/bk_dt_properties.php */
