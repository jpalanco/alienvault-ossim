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

//First we check we have session active
Session::useractive();

//Then we check the permissions
if (!Session::logcheck_bool('environment-menu', 'PolicyHosts'))
{
    $response['error']  = TRUE ;
    $response['msg']    = _('You do not have permissions to see this section');

    echo json_encode($response);
    exit -1;
}


define('ITEMS_PER_PAGE', 30);

/*
*
* <------------------------   BEGINNING OF THE FUNCTIONS   ------------------------>
*
*/


/*
* Function to get the list of networks
*
* @param  $conn     object   DB Connection.
* @param  $page     integer  Current Page of the list.
* @param  $search   string   Search string.
*
* @return  array    The total of elements and the elements.
*/
function network_list($conn, $page, $search)
{
    $filters  = array();

    $filters['limit'] = get_query_limits($page);

    if ($search != '')
    {
        $search = utf8_decode($search);
        $search = escape_sql($search, $conn);

        $filters['where'] = " name LIKE '%$search%' OR ips LIKE '%$search%'";
    }

    $filters['order_by'] = 'name ASC';

    try
    {
        list($nets, $total) = Asset_net::get_list($conn, '', $filters, TRUE);
    }
    catch(Exception $e)
    {
        $return['error'] = TRUE;
        $return['msg']   = $e->getMessage();

        return $return;
    }

    //If we have at least one element...
    if ($total > 0)
    {
        //Getting the nets already selected in the filter.
        $selected = get_selected_values(7);
    }

    $list = array();

    //Going through the list to format the elements properly:
    foreach($nets as $id => $net)
    {

        $_chk = ($selected[$id] != '') ? TRUE : FALSE;

        $_net = array(
            'id'      => $id,
            'name'    => $net['name'],
            'extra'   => $net['ips'],
            'checked' => $_chk
        );

        $list[$id] = $_net;

    }

    $data['total']   = intval($total);
    $data['list']    = $list;

    $return['error'] = FALSE;
    $return['data']  = $data;

    return $return;
}


/*
* Function to get the list of sensors
*
* @param  $conn     object   DB Connection.
* @param  $page     integer  Current Page of the list.
* @param  $search   string   Search string.
*
* @return  array    The total of elements and the elements.
*/
function sensor_list($conn, $page, $search)
{
    $filters  = array();

    $filters['limit'] = get_query_limits($page);

    if ($search != '')
    {
        $search = utf8_decode($search);
        $search = escape_sql($search, $conn);

        $filters['where'] = " name LIKE '%$search%' OR inet6_ntoa(ip) LIKE '%$search%'";
    }

    $filters['order_by'] = 'name ASC';

    try
    {
        list($sensors, $total) = Av_sensor::get_list($conn, $filters, TRUE, TRUE);
    }
    catch(Exception $e)
    {
        $return['error'] = TRUE;
        $return['msg']   = $e->getMessage();

        return $return;
    }

     //If we have at least one element...
    if ($total > 0)
    {
        //Getting the nets already selected in the filter.
        $selected = get_selected_values(14);
    }

    $list = array();

    //Going through the list to format the elements properly:
    foreach($sensors as $id => $sensor)
    {
        $_chk = ($selected[$id] != '') ? TRUE : FALSE;

        $_sensor = array(
            'id'      => $id,
            'name'    => $sensor['name'],
            'extra'   => $sensor['ip'],
            'checked' => $_chk
        );

        $list[$id] = $_sensor;

    }

    $data['total']   = intval($total);
    $data['list']    = $list;

    $return['error'] = FALSE;
    $return['data']  = $data;

    return $return;
}


/*
* Function to get the list of locations
*
* @param  $conn     object   DB Connection.
* @param  $page     integer  Current Page of the list.
* @param  $search   string   Search string.
*
* @return  array    The total of elements and the elements.
*/
function location_list($conn, $page, $search)
{
    $limit = 'Limit ' . get_query_limits($page);

    if ($search == '')
    {
        $where = '';
    }
    else
    {
        $search = utf8_decode($search);
        $search = escape_sql($search, $conn);

        $where  = " AND name LIKE '%$search%'";
    }

    $loc_list = Locations::get_list($conn, " $where ORDER BY name ASC $limit");

    //If we have at least one element...
    if ($loc_list[0])
    {
        //Getting the total of elements
        $total = $loc_list[0]->get_foundrows();

        if ($total == 0)
        {
            $total = count($loc_list);
        }

        //Getting the locations already selected in the filter.
        $selected = get_selected_values(13);

    }
    else  //Otherwise the total is 0
    {
        $total = 0;
    }

    $list = array();

    //Going through the list to format the elements properly:
    foreach($loc_list as $loc)
    {
        $_chk = ($selected[$loc->get_id()] != '') ? TRUE : FALSE;

        $_loc = array(
            'id'      => $loc->get_id(),
            'name'    => Util::utf8_encode2($loc->get_name()),
            'checked' => $_chk
        );

        $list[$loc->get_id()] = $_loc;

    }

    $data['total']   = intval($total);
    $data['list']    = $list;

    $return['error'] = FALSE;
    $return['data']  = $data;


    return $return;
}


/*
* Function to get the list of software
*
* @param  $conn     object   DB Connection.
* @param  $page     integer  Current Page of the list.
* @param  $search   string   Search string.
*
* @return  array    The total of elements and the elements.
*/
function software_list($conn, $page, $search)
{
    $filters  = array();

    $filters['limit'] = get_query_limits($page);

    if ($search != '')
    {
        $search = utf8_decode($search);
        $search = escape_sql($search, $conn);

        $s_regexp = preg_replace('/\s+/', '[_:]+', $search);
        $filters['where'] = " hs.cpe REGEXP '.*$s_regexp.*' ";

    }

    try
    {
        list($softwares, $total) = Asset_host_software::get_all($conn, $filters, TRUE);
    }
    catch(Exception $e)
    {
        $return['error'] = TRUE;
        $return['msg']   = $e->getMessage();

        return $return;
    }

    if ($total > 0)
    {
        $selected = get_selected_values(9);
    }

    $list = array();

    //Going through the list to format the elements properly:
    foreach($softwares as $cpe => $software)
    {
        $_chk  = ($selected[$cpe] != '') ? TRUE : FALSE;
        $name  = empty($software['line']) ? $cpe : $software['line'];
        $_soft = array(
            'id'      => $cpe,
            'name'    => $name,
            'checked' => $_chk
        );

        $list[$cpe] = $_soft;
    }

    $data['total']   = intval($total);
    $data['list']    = $list;

    $return['error'] = FALSE;
    $return['data']  = $data;

    return $return;
}

/*
* Function to get the list of device types
*
* @param  $conn     object   DB Connection.
* @param  $page     integer  Current Page of the list.
* @param  $search   string   Search string.
*
* @return  array    The total of elements and the elements.
*/
function device_type_list($conn, $page, $search)
{
    $filters = array();

    $filters['limit'] = get_query_limits($page);

    if ($search != '')
    {
        $search = utf8_decode($search);
        $search = escape_sql($search, $conn);

        $filters['where'] = " (type_name LIKE '%$search%' OR subtype_name LIKE '%$search%') ";
    }

    try
    {
        $filters['where'] .= ($filters['where'] != '')
                           ? ' AND q.type_id = host_types.type AND q.subtype_id = host_types.subtype'
                           : 'q.type_id = host_types.type AND q.subtype_id = host_types.subtype';

        list($devices, $total) = Devices::get_all_for_filter($conn, ', host_types', $filters, TRUE);
    }
    catch(Exception $e)
    {
        $return['error'] = TRUE;
        $return['msg']   = $e->getMessage();

        return $return;
    }

    if ($total > 0)
    {
        $selected = get_selected_values(8);
    }

    $list = array();

    //Going through the list to format the elements properly:
    foreach($devices as $device)
    {
        $_dev  = array();

        $sname = ($device['subtype_name'] != '') ? '/' . $device['subtype_name'] : '';

        $id    = $device['type_id'] . ';' . $device['subtype_id'];
        $name  = $device['type_name'] . $sname;
        $md5   = md5($id);

        $_chk  = ($selected[$md5] != '') ? TRUE : FALSE;

        $_dev = array(
            'id'      => $id,
            'name'    => Util::utf8_encode2($name),
            'checked' => $_chk
        );

        $list[$md5] = $_dev;

    }

    $data['total']   = intval($total);
    $data['list']    = $list;

    $return['error'] = FALSE;
    $return['data']  = $data;

    return $return;
}


/*
*
* Function to get the list of services
*
* @param  $conn     object   DB Connection.
* @param  $page     integer  Current Page of the list.
* @param  $search   string   Search string.
*
* @return  array    The total of elements and the elements.
*/
function service_list($conn, $page, $search)
{

    $return['error']      = TRUE;
    $return['msg']        = '';

    $filters = array();

    $filters['limit']    = get_query_limits($page);
    $filters['order_by'] = 'port';

    if ($search != '')
    {
        $search = utf8_decode($search);
        $search = escape_sql($search, $conn);

        $filters['where'] = " (s.port LIKE '%$search%'  OR s.service LIKE '%$search%'";

        //Filter by protocol name
        $protocol_list = Protocol::get_list($search);
        $protocol_list = array_keys($protocol_list);
        $protocol_list = implode(',', $protocol_list);

        if (!empty($protocol_list))
        {
            $filters['where'] .= " OR s.protocol IN ($protocol_list)";
        }

        $filters['where'] .= ")";
    }

    try
    {
        list($services, $total) = Asset_host_services::get_services_available($conn, $filters, TRUE);
    }
    catch(Exception $e)
    {
        $return['error'] = TRUE;
        $return['msg']   = $e->getMessage();

        return $return;
    }

    if ($total > 0)
    {
        $selected = get_selected_values(10);
    }

    $list = array();

    //Going through the list to format the elements properly:
    foreach($services as $service)
    {
        $_serv = array();

        $id    = $service['port'] .';' . $service['protocol'] .';' . $service['service'];
        $md5   = md5($id);
        $name  = $service['port'] .'/' . $service['prot_name'] . ' (' . $service['service'] . ')';

        $_chk  = ($selected[$md5] != '') ? TRUE : FALSE;

        $_serv = array(
            'id'      => $id,
            'name'    => Util::utf8_encode2($name),
            'checked' => $_chk
        );

        $list[$md5] = $_serv;

    }

    $data['total']   = intval($total);
    $data['list']    = $list;

    $return['error'] = FALSE;
    $return['data']  = $data;

    return $return;
}



/*
* Function to get the list of asset groups
*
* @param  $conn     object   DB Connection.
* @param  $page     integer  Current Page of the list.
* @param  $search   string   Search string.
*
* @return  array    The total of elements and the elements.
*/
function group_list($conn, $page, $search)
{
    $filters  = array();

    $filters['limit'] = get_query_limits($page);

    if ($search != '')
    {
        $search = utf8_decode($search);
        $search = escape_sql($search, $conn);

        $filters['where'] = " name LIKE '%$search%'";
    }

    $filters['order_by'] = 'name ASC';

    try
    {
        list($groups, $total) = Asset_group::get_list($conn, '', $filters, TRUE);
    }
    catch(Exception $e)
    {
        $return['error'] = TRUE;
        $return['msg']   = $e->getMessage();

        return $return;
    }

    //If we have at least one element...
    if ($total > 0)
    {
        //Getting the nets already selected in the filter.
        $selected = get_selected_values(18);
    }

    $list = array();

    //Going through the list to format the elements properly:
    foreach($groups as $id => $group)
    {

        $_chk = ($selected[$id] != '') ? TRUE : FALSE;

        $_grp = array(
            'id'      => $id,
            'name'    => $group->get_name(),
            'checked' => $_chk
        );

        $list[$id] = $_grp;

    }

    $data['total']   = intval($total);
    $data['list']    = $list;

    $return['error'] = FALSE;
    $return['data']  = $data;

    return $return;
}


/*
* Function to get the list of operating systems
*
* @param  $conn     object   DB Connection.
* @param  $page     integer  Current Page of the list.
* @param  $search   string   Search string.
*
* @return  array    The total of elements and the elements.
*/
function operating_system_list($conn, $page, $search)
{
    $filters  = array();

    $filters['limit'] = get_query_limits($page);

    if ($search != '')
    {
        $search = utf8_decode($search);
        $search = escape_sql($search, $conn);

        $filters['where'] = " host_properties.value LIKE '%$search%' ";
    }

    try
    {
        list($properties, $total) = Asset_host_properties::get_property_values($conn, 3, $filters, TRUE);
    }
    catch(Exception $e)
    {
        $return['error'] = TRUE;
        $return['msg']   = $e->getMessage();

        return $return;
    }

    if ($total > 0)
    {
        $selected = get_selected_values(20);
    }

    $list = array();

    //Going through the list to format the elements properly:
    foreach($properties as $os_id => $value)
    {
        $_chk  = ($selected[$os_id] != '') ? TRUE : FALSE;
        $_prop = array(
            'id'      => $value,
            'name'    => $value,
            'checked' => $_chk
        );

        $list[$os_id] = $_prop;
    }

    $data['total']   = intval($total);
    $data['list']    = $list;

    $return['error'] = FALSE;
    $return['data']  = $data;

    return $return;
}

/*
* Function to get the list of labels
*
* @param  $conn     object   DB Connection.
* @param  $page     integer  Current Page of the list.
* @param  $search   string   Search string.
*
* @return  array    The total of elements and the elements.
*/
function label_list($conn, $page, $search)
{
    $filters  = array();

    $filters['limit'] = get_query_limits($page);

    $filters['where'] = " type='asset' AND id = component_tags.id_tag";

    if ($search != '')
    {
        $search = utf8_decode($search);
        $search = escape_sql($search, $conn);

        $filters['where'] .= " AND name LIKE '%$search%'";
    }

    $filters['order_by'] = 'name ASC';

    try
    {
        list($total, $labels) = Tag::get_list($conn, ', component_tags', $filters);
    }
    catch(Exception $e)
    {
        $return['error'] = TRUE;
        $return['msg']   = $e->getMessage();

        return $return;
    }

    //If we have at least one element...
    if ($total > 0)
    {
        //Getting the nets already selected in the filter.
        $selected = get_selected_values(19);
    }

    $list = array();

    //Going through the list to format the elements properly:
    foreach($labels as $id => $label)
    {

        $_chk = ($selected[$id] != '') ? TRUE : FALSE;

        $_tag = array(
            'id'      => $id,
            'name'    => $label->get_name(),
            'class'   => $label->get_class(),
            'checked' => $_chk
        );

        $list[$id] = $_tag;

    }

    $data['total']   = intval($total);
    $data['list']    = $list;

    $return['error'] = FALSE;
    $return['data']  = $data;

    return $return;
}



/*
* Function to get the list of models
*
* @param  $conn     object   DB Connection.
* @param  $page     integer  Current Page of the list.
* @param  $search   string   Search string.
*
* @return  array    The total of elements and the elements.
*/
function model_list($conn, $page, $search)
{
    $filters  = array();

    $filters['limit'] = get_query_limits($page);

    if ($search != '')
    {
        $search = utf8_decode($search);
        $search = escape_sql($search, $conn);

        $filters['where'] = " host_properties.value LIKE '%$search%' ";
    }

    try
    {
        list($properties, $total) = Asset_host_properties::get_property_values($conn, 14, $filters, TRUE);
    }
    catch(Exception $e)
    {
        $return['error'] = TRUE;
        $return['msg']   = $e->getMessage();

        return $return;
    }

    if ($total > 0)
    {
        $selected = get_selected_values(21);
    }

    $list = array();

    //Going through the list to format the elements properly:
    foreach($properties as $os_id => $value)
    {
        $_chk  = ($selected[$os_id] != '') ? TRUE : FALSE;
        $_prop = array(
            'id'      => $value,
            'name'    => $value,
            'checked' => $_chk
        );

        $list[$os_id] = $_prop;
    }

    $data['total']   = intval($total);
    $data['list']    = $list;

    $return['error'] = FALSE;
    $return['data']  = $data;

    return $return;
}



/*
 * Function to get the list of plugins
*
* @param  $conn     object   DB Connection.
* @param  $page     integer  Current Page of the list.
* @param  $search   string   Search string.
*
* @return  array    The total of elements and the elements.
*/
function plugin_list($conn, $page, $search)
{
    $filters  = array();

    $filters['limit'] = get_query_limits($page);

    if ($search != '')
    {
        $search = utf8_decode($search);
        $search = escape_sql($search, $conn);
        
        $filters['where'] = " (plugin.name LIKE '%$search%' OR plugin.description LIKE '%$search%')";
    }

    try
    {
        list($plugins, $total) = Asset_host_scan::get_all_plugins($conn, '', $filters, TRUE);
    }
    catch(Exception $e)
    {
        $return['error'] = TRUE;
        $return['msg']   = $e->getMessage();

        return $return;
    }

    if ($total > 0)
    {
        $selected = get_selected_values(25);
    }

    $list = array();

    // Special filter "No Plugin Enabled" PID = 0
    if (count($plugins) > 0 && $search == '')
    {
        $_chk    = ($selected[0] != '') ? TRUE : FALSE;
        $_plugin = array(
                'id'      => 0,
                'name'    => _('No Plugin Enabled'),
                'class'   => 'italic exclusive',
                'checked' => $_chk
        );
        
        $list[] = $_plugin; 
    }
    
    //Going through the list to format the elements properly:
    foreach($plugins as $p_id => $p_data)
    {
        $_chk    = ($selected[$p_id] != '') ? TRUE : FALSE;
        $_plugin = array(
                'id'      => $p_id,
                'name'    => ucwords($p_data['name']),
                'title'   => $p_data['description'],
                'checked' => $_chk
        );
    
        $list[] = $_plugin;
    }

    $data['total']   = intval($total);
    $data['list']    = $list;

    $return['error'] = FALSE;
    $return['data']  = $data;

    return $return;
}



/******************************  AUX FUNCTIONS  ******************************/

/*
* Function to get an array with the items that have been already selected.
*
* @param  $id       object  Current Filter ID.
*
* @return  array    The total of elements and the elements.
*/
function get_selected_values($id)
{
    //Getting the object with the filters.
    $filters  = Filter_list::retrieve_filter_list_session();

    //If the filters object is not an object, returns empty
    if ($filters === FALSE)
    {
        return array();
    }

    $filter = $filters->get_filter($id);

    //If the concrete filter is not an object, returns empty.
    if (!is_object($filter))
    {
        return array();
    }

    //Returns the selected values
    return $filter->get_values();

}


/*
* Function to get sql limit sentence from a given page number
*
* @param  $page     object  Current Page of the list.
*
* @return  string    The total of elements and the elements.
*/
function get_query_limits($page)
{
    $start = (($page - 1) * ITEMS_PER_PAGE);

    //The minimun posible value has to be 0
    $start = ($start < 0) ? 0 : $start;

    $limit = "$start, ". ITEMS_PER_PAGE;

    return $limit;
}


/*
*
* <------------------------   END OF THE FUNCTIONS   ------------------------>
*
*/




/*
*
* <-------------------------   BODY OF THE SCRIPT   ------------------------->
*
*/

$action     = POST('action');   //Action to perform.
$page       = POST('page');     //Page Number.
$search     = POST('search');   //Search option.


ossim_valid($action,     OSS_INPUT,                     'illegal:' . _('Action'));
ossim_valid($page,       OSS_DIGIT, OSS_NULLABLE,       'illegal:' . _('Page'));
ossim_valid($search,     OSS_INPUT, OSS_NULLABLE,       'illegal:' . _('Search String'));

if (ossim_error())
{
    $response['error'] = TRUE ;
    $response['msg']   = ossim_get_error();
    ossim_clean_error();

    echo json_encode($response);

    die();
}


$db   = new ossim_db(TRUE);
$conn = $db->connect();


//Default values for the response.
$response['error'] = TRUE ;
$response['msg']   = _('Error');


//checking if it is an ajax request
if($action != '' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
{
    //Checking token
    if ( !Token::verify('tk_asset_filter_list', GET('token')) )
    {
        $response['error'] = TRUE ;
        $response['msg']   = _('Invalid Action');
    }
    else
    {
        $function_list = array
        (
           'network'          => 'network_list',
           'software'         => 'software_list',
           'sensor'           => 'sensor_list',
           'device_type'      => 'device_type_list',
           'service'          => 'service_list',
           'location'         => 'location_list',
           'operating_system' => 'operating_system_list',
           'group'            => 'group_list',
           'model'            => 'model_list',
           'label'            => 'label_list',
           'plugin'           => 'plugin_list'
        );

        try
        {
            $func_name = $function_list[$action];

            if (function_exists($func_name))
            {
                $response = $func_name($conn, $page, $search);
            }
            else
            {
                $response['error'] = TRUE ;
                $response['msg']   = _('Wrong Option Chosen');
            }
        }
        catch(Exception $e)
        {
            $response['error'] = TRUE ;
            $response['msg']   = $e->getMessage();
        }

    }
}

//Returning the response to the AJAX call.
echo json_encode($response);

$db->close();
