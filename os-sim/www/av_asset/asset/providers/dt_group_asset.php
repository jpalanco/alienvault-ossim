<?php
/**
* dt_group_asset.php
*
* File dt_group_asset.php is used to:
* - Response ajax call from details assets dataTable
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
*
*/

require_once 'av_init.php';


session_write_close();

Session::logcheck_ajax('environment-menu', 'PolicyHosts');

$asset_id = POST('asset_id');


/* Connect to db */
$db   = new ossim_db(TRUE);
$conn = $db->connect();

//DataTables Pagination and search Params
$maxrows    = (POST('iDisplayLength') != '') ? POST('iDisplayLength') : 10;
$from       = (POST('iDisplayStart') != '')  ? POST('iDisplayStart') : 0;
$order      = (POST('iSortCol_0') != '')     ? POST('iSortCol_0') : "";
$torder     = POST('sSortDir_0');
$search_str = (POST('sSearch') != '')        ? POST('sSearch') : '';
$sec        = POST('sEcho');

ossim_valid($asset_id,      OSS_HEX, OSS_NULLABLE,         'illegal: '._('Asset ID'));
ossim_valid($maxrows,       OSS_DIGIT, 				   	  'illegal: iDisplayLength');
ossim_valid($from, 			OSS_DIGIT,         			  'illegal: iDisplayStart');
ossim_valid($order, 		OSS_ALPHA,       			  'illegal: iSortCol_0');
ossim_valid($torder, 		OSS_LETTER, 				  'illegal: sSortDir_0');
ossim_valid($search_str,    OSS_INPUT, OSS_NULLABLE,      'illegal: sSearch');
ossim_valid($sec, 			OSS_DIGIT,				  	  'illegal: sEcho');

if (ossim_error())
{
    Util::response_bad_request(ossim_get_error_clean());
}

// Order by column
switch($order)
{
	case '0':
		$order = 'g.name';
	break;

    default:
		$order = 'g.name';
}


try
{
    //Filters
    $filters  = array();
    $where    = array();

    $filters['order_by'] = $order . ' ' . $torder;
    $filters['limit']    = $from . ', ' . $maxrows;
    
    if (valid_hex32($asset_id))
    {
        $where[] = 'g.id NOT IN (SELECT host_group_id FROM host_group_reference where host_id = UNHEX("' . $asset_id . '"))';
    }

    if ($search_str != '')
    {
        $search_str = escape_sql($search_str, $conn);
        $where[]    = 'g.name LIKE "%'.$search_str.'%"';
    }
    
    $filters['where'] = (count($where) > 0) ? implode(' AND ', $where) : '';

    // Get Groups
    list($groups, $total) = Asset_group::get_list($conn, '', $filters);

    $results = array();

    foreach($groups as $group)
    {
        // COLUMNS
        $_res = array();

        $_res['DT_RowId'] = $group->get_id();
        
        try
        {
            $_can_edit = $group->can_i_edit($conn);
        }
        catch (Exception $e)
        {
            $_can_edit = FALSE;
        }
        
        $_res['DT_RowData']['editable'] = $_can_edit;

        $_res[] = Util::utf8_encode2($group->get_name());
        $_res[] = '';

        $results[] = $_res;
    }

}
catch (Exception $e)
{
    $db->close();

    Util::response_bad_request($e->getMessage());
}


// datatables response json
$response['sEcho']                = intval($sec);
$response['iTotalRecords']        = $total;
$response['iTotalDisplayRecords'] = $total;
$response['aaData']               = $results;

echo json_encode($response);

$db->close();

/* End of file dt_groups_asset.php */
/* Location: /av_asset/asset/providers/dt_group_asset.php */
