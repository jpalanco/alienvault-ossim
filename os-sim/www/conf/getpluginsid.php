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
require_once ('av_init.php');



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
$plugin_id      = GET('plugin_id');
$category_id    = GET('category_id');
$subcategory_id = GET('subcategory_id');

ossim_valid($plugin_id,         OSS_ALPHA, OSS_PUNC, OSS_NULLABLE,      'illegal:' . _("Plugin ID"));
ossim_valid($category_id,       OSS_DIGIT, OSS_NULLABLE,                'illegal:' . _("Category Id"));
ossim_valid($subcategory_id,    OSS_DIGIT, OSS_NULLABLE,                'illegal:' . _("Subcategory Id"));

if (ossim_error()) 
{
    $response['sEcho']                = $sec;
	$response['iTotalRecords']        = 0;
	$response['iTotalDisplayRecords'] = 0;
	$response['aaData']               = '';
	
	echo json_encode($response);
	
	exit;
}


$db    = new ossim_db();
$conn  = $db->connect();

/*  ORDER  */
switch ($order)
{
    case 1:
        $order = 'sid';
        break;
    
    case 5:
        $order = 'name';
        break;
    
    case 6:
        $order = 'priority';
        break;
    
    case 7:
        $order = 'reliability';
        break;
        
    default:
        $order = 'sid';
    
}

$torder = ($torder == 1) ? 'ASC' : 'DESC';
$order .= ' ' . $torder;


/*  WHERE  */
$where = "WHERE sid <> 20000000 AND sid <> 2000000000 AND plugin_id = $plugin_id";


if ($category_id != '')
{
    $where .= " AND category_id='$category_id'";
}

if ($subcategory_id != '')
{
    $where .= " AND subcategory_id='$subcategory_id'";
}

if (!empty($search))
{
    $search = (mb_detect_encoding($search." ", 'UTF-8,ISO-8859-1') == 'UTF-8') ? Util::utf8entities($search) : $search;
    $search = escape_sql($search, $conn);
    
    $pids   = Plugin_sid::get_sids_by_category($conn,$plugin_id,$search,$subcategory_id);
    $p_list = implode(",",$pids);
    $p_list = empty($p_list) ? "''" : $p_list;
    
    $where .= " AND (name like '%$search%' OR sid='$search' OR category_id in ($p_list)) ";
    
}

/*  LIMIT  */
$limit = "LIMIT $from, $maxrows";

$results = array();

if ($plugin_list = Plugin_sid::get_list($conn, "$where ORDER BY $order $limit")) 
{
    $total = $plugin_list[0]->get_foundrows();
    if ($total == 0) 
    {
		$total = count($plugin_list);
	}
    
	foreach($plugin_list as $plugin) 
	{
    	$_res   = array();
    	
        $plugin_id = $plugin->get_plugin_id();
        $sid       = $plugin->get_sid();
		$lnk_sid   = (empty($sid)) ? _("Unknown") : "<a class='av_l_main' href='modifypluginsidform.php?plugin_id=$plugin_id&sid=$sid'>$sid</a>";
		
		$name = $plugin->get_name();
				
        
        $_res[] = $plugin_id; // data source id
        $_res[] = $lnk_sid; //event type id

		//Translate category id
        $c_name = '';
        $c_id   = $plugin->get_category_id();
        
		if ($c_id != '') 
		{
            if ($category_list = Category::get_list($conn, "WHERE id = '$c_id'")) 
            {
                $c_url  = "pluginsid.php?plugin_id=$plugin_id&category_id=$c_id";
                $c_name = "<a class='av_l_main' href='$c_url'>". $category_list[0]->get_name() ."</a>";
            }
        }
	
		$category = ($c_name != '') ? $c_name : '-';
		$_res[]   = $category; // category
		
		//Subcategory
		$sc_name = '';
		$sc_id   = $plugin->get_subcategory_id();
		
		if ($sc_id != '')
		{
			if ($subcategory_list = Subcategory::get_list($conn, "WHERE id = '$sc_id'")) 
			{
    			$sc_url  = "pluginsid.php?plugin_id=$plugin_id&category_id=$c_id&subcategory_id=$sc_id";
                $sc_name = "<a class='av_l_main' href='$sc_url'>". $subcategory_list[0]->get_name() ."</a>";
            }
		}
		$subcategory = ($sc_name != '') ? $sc_name : "-";
		$_res[]      = $subcategory; // subcategory
        
		//Translate class id
        if ($class_id = $plugin->get_class_id()) {
            if ($class_list = Classification::get_list($conn, "WHERE id = '$class_id'")) 
            {
                $class_name = $class_list[0]->get_name();
            }
        }
        
        $class = (!empty($class_name)) ? $class_name : "-";
        
        $_res[] = $class; // class
        $_res[] = $name; // name
        
        $_res[] = $plugin->get_priority(); // priority
        $_res[] = $plugin->get_reliability(); // reliability
        
        $_res[] = '';
        
        $_res['DT_RowId'] = $sid;
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
