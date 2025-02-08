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

Session::logcheck("configuration-menu", "ConfigurationPlugins");

//DataTables Pagination and search Params
$maxrows = (POST('iDisplayLength') != '') ? POST('iDisplayLength') : 20;
$from    = POST('iDisplayStart');
$order   = POST('iSortCol_0');
$torder  = POST('sSortDir_0');
$search  = POST('sSearch');
$sec     = intval(POST('sEcho'));

$torder = (!strcasecmp($torder, 'asc')) ? 0 : 1;

ossim_valid($maxrows,  OSS_DIGIT,                   'illegal: iDisplayLength');
ossim_valid($from,     OSS_DIGIT,                   'illegal: iDisplayStart');
ossim_valid($order,    OSS_DIGIT,                   'illegal: sSortDir_0');
ossim_valid($torder,   OSS_DIGIT,                   'illegal: sSortDir_0');
ossim_valid($search,   OSS_INPUT, OSS_NULLABLE,     'illegal: Search String');


//Taxonomy categories parameters
$category_id = GET('category_id');
$subcategory_id = GET('subcategory_id');
$source_type = GET('sourcetype');

ossim_valid($category_id,     OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Category ID"));
ossim_valid($subcategory_id,  OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Subcategory ID"));
ossim_valid($source_type,     OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Source Type"));

if (ossim_error()) {
    $response['sEcho']                = $sec;
	$response['iTotalRecords']        = 0;
	$response['iTotalDisplayRecords'] = 0;
	$response['aaData']               = '';

	echo json_encode($response);
	exit();
}


$db   = new ossim_db();
$conn = $db->connect();

/*  ORDER  */
switch ($order)
{
    case 0:
        $order = 'id';
        break;

    case 1:
        $order = 'name';
        break;

    case 2:
        $order = 'type';
        break;

    case 3:
        $order = 'product_type';
        break;

    case 4:
        $order = 'description';
        break;

    default:
        $order = 'id';

}

$torder = ($torder == 1) ? 'ASC' : 'DESC';
$order .= ' ' . $torder;

/*  WHERE  */
$where = "WHERE id <> 1505";
$plugin_list = '';

if (!empty($source_type)) {
    $source_type = escape_sql($source_type, $conn);
    $pids_s      = Plugin_sid::get_plugins_by_type($conn, $source_type);
    $plugin_list = implode(",", $pids_s);
}

if (!empty($category_id)) {
    $category_id = escape_sql($category_id, $conn);
    $pids_c      = Plugin_sid::get_plugins_by_category($conn, $category_id, $subcategory_id);

    if (!empty($source_type)) {
        $pids_c = array_intersect($pids_s, $pids_c);
    }

    $plugin_list = implode(",", $pids_c);
}

if (!empty($plugin_list)){
    $where .= " AND id IN ($plugin_list)";
}

if (!empty($search))
{
    $search = (mb_detect_encoding($search." ", 'UTF-8,ISO-8859-1') == 'UTF-8') ? Util::utf8entities($search) : $search;
    $search = escape_sql($search, $conn);

    $where .= " AND (name like '%$search%' OR id='$search' OR description like '%$search%') ";
}

/*  LIMIT  */
$limit = "LIMIT $from, $maxrows";

$results = array();
$total = 0;

if ($plugin_list = Plugin::get_list($conn, "$where ORDER BY $order $limit"))
{
	$total = $plugin_list[0]->get_foundrows();

    if ($total == 0)
    {
		$total = count($plugin_list);
	}

	foreach($plugin_list as $plugin)
	{
    	$_res   = array();

    	$_id    = $plugin->get_id();
        $_res[] = $_id; // DATA SOURCE ID
        $_res[] = $plugin->get_name(); // NAME

        $type   = $plugin->get_type();

		if ($type == '1')
		{
            $type = "Detector ($type)";
        }
		elseif ($type == '2')
		{
            $type = "Monitor ($type)";
        }
		else
		{
            $type = "Other ($type)";
        }

        $_res[] = $type; // TYPE


		// Source Type
		$source_type = $plugin->get_sourceType();

		$url = "plugin.php?sourcetype=".$plugin->get_product_type();

		if (!empty($category_id)){
		    $url .= "&category_id=$category_id";
        }

        if (!empty($subcategory_id)){
            $url .= "&subcategory_id=$subcategory_id";
        }

		$_res[] = "<a class='av_l_main' href='".$url."'>" . $source_type . "</a>"; // PRODUCT TYPE

        $_res[] = $plugin->get_description(); // DESCRIPTION
        $_res[] = ''; // ACTIONS


        $_res['DT_RowId'] = $_id;
        $results[]        = $_res;

    }
}

// datatables response json
$response = array();

$response['sEcho']                = $sec;
$response['iTotalRecords']        = $total;
$response['iTotalDisplayRecords'] = $total;
$response['aaData']               = $results;
$response['iDisplayStart']        = 0;


echo json_encode($response);


$db->close();
