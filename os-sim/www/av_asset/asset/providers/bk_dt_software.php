<?php
/**
 * bk_dt_software.php
 *
 * File dt_software.php is used to:
 * - Response ajax call from software dataTable
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
    '1' => 'banner',      // Order by Software CPE
    '2' => 'source_name'  // Order by Source name
);


if (array_key_exists($order, $orders_by_columns))
{
    $order = $orders_by_columns[$order];
}
else
{
    $order = 'banner';
}


// Property filter
$filters = array(
    'where'    => "`cpe` LIKE 'cpe:/a%'",
    'limit'    => "$from, $maxrows",
    'order_by' => "$order $torder"
);

if ($search_str != '')
{
    $search_str        = escape_sql($search_str, $conn);
    $filters['where'] .= ' AND (banner LIKE "%'.$search_str.'%" OR cpe LIKE "%'.$search_str.'%")';
}


// Software data
$data     = array();
$sw_list  = array();
$sw_total = 0;


list($sw_list, $sw_total) = Asset_host_software::bulk_get_list($conn, $filters);

foreach ($sw_list as $sw_cpe => $sw_values)
{
    $r_key = strtolower(md5($sw_cpe));

    $sw_name    = $sw_values['banner'];
    $dt_sw_name = $sw_name;

    if (empty($sw_name))
    {
        $sw_name = Util::wordwrap($sw_cpe, 80, '<br/>');
    }

    $_sw_data = array(
        "DT_RowId"   => $r_key,
        "DT_RowData" => array(
            'p_id'      => 60,
            'sw_cpe'    => $sw_cpe,
            'sw_name'   => $dt_sw_name,
            'source_id' => $sw_values['source']['id'],
        ),
        "",
        $sw_name,
        $sw_values['source']['name'],
        ""
    );

    $data[] = $_sw_data;
}


$response['sEcho']                = $sec;
$response['iTotalRecords']        = $sw_total;
$response['iTotalDisplayRecords'] = $sw_total;
$response['aaData']               = $data;

echo json_encode($response);

$db->close();

/* End of file bk_dt_software.php */
/* Location: /av_asset/common/providers/bk_dt_software.php */
