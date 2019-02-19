<?php
/**
 * dt_history.php
 *
 * File dt_history.php is used to:
 *  - Build JSON data that will be returned in response to the Ajax request made by DataTables (Group history)
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
    'group' => 'asset_group'
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


        //History filters
        $order  = 'date';
        $torder = (!strcasecmp($torder, 'asc')) ? 'asc' : 'desc';

        $filters = array(
            'limit'    => "$from, $maxrows",
            'order_by' => "$order $torder"
        );

        if ($search_str != '')
        {
            $search_str = escape_sql($search_str, $conn);

            $filters['where'] = 'action LIKE "%'.$search_str.'%"';
        }

        list($history_events, $total) = $asset_object->get_history($conn, $filters);
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


// DATA
$data = array();

foreach ($history_events as $event)
{
    $data[] = array(
        $event['date'],
        $event['login'],
        $event['action']
    );
}

$response['sEcho']                = $sec;
$response['iTotalRecords']        = $total;
$response['iTotalDisplayRecords'] = $total;
$response['aaData']               = $data;

echo json_encode($response);

$db->close();

/* End of file dt_history.php */
/* Location: /av_asset/common/providers/dt_history.php */
