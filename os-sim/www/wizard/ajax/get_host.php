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

if (!Session::am_i_admin())
{
    $response['error']  = TRUE ;
    $response['msg']    = _('You do not have permissions to see this section');

    echo json_encode($response);

    exit -1;
}


//DataTables Pagination and search Params
$maxrows = (POST('iDisplayLength') != '') ? POST('iDisplayLength') : 9;
$from    = POST('iDisplayStart');
$torder  = POST('sSortDir_0');
$search  = POST('sSearch');
$sec     = intval(POST('sEcho'));

$torder = (!strcasecmp($torder, 'asc')) ? 0 : 1;

ossim_valid($maxrows,  OSS_DIGIT,                   'illegal: iDisplayLength');
ossim_valid($from,     OSS_DIGIT,                   'illegal: iDisplayStart');
ossim_valid($torder,   OSS_DIGIT,                   'illegal: sSortDir_0');
ossim_valid($search,   OSS_INPUT, OSS_NULLABLE,     'illegal: Search String');


if (ossim_error()) 
{
    $response['sEcho']                = $sec;
	$response['iTotalRecords']        = 0;
	$response['iTotalDisplayRecords'] = 0;
	$response['aaData']               = '';
	
	echo json_encode($response);
	
	exit;
}


/* connect to db */
$db       = new ossim_db(TRUE);
$conn     = $db->connect();

$order    = 'host.hostname';

$maxrows  = ($maxrows > 50) ? 50 : $maxrows;

$torder   = ($torder == 1) ? 'ASC' : 'DESC';

$to       = $maxrows;

$user     = Session::get_session_user();

$filters  = array();
$tables   = '';

$filters['order_by'] = $order . ' ' . $torder;
$filters['limit']    = $from . ', ' . $to;


if ($search != '')
{
    if (is_ip($search) || is_cidr($search))
    {
        $cidr  = (preg_match('/\/[0-9]+/', $search)) ? $search : $search . '/32'; //If it is an ip, we add '/32'
                    
        list($from, $to) = CIDR::expand_CIDR($cidr, 'SHORT', 'IP');
        
        $tables = ', host_ip hi ';
        $filters['where'] = "host.id=hi.host_id AND hi.ip BETWEEN INET6_PTON('$from') AND INET6_PTON('$to') ";

    }
    else
    {
        $search = utf8_decode($search);
        $search = escape_sql($search, $conn);
    
        $filters['where'] = 'host.hostname LIKE "%'. $search .'%"';
    }

    
}

try
{
    list($assets, $total) = Asset_host::get_list($conn, $tables, $filters, TRUE);
}
catch(Exception $e)
{
    $assets = array();
    $total  = 0;
}

$results = array();

foreach($assets as $_id => $asset_data)
{
    $_res = array();

    // Get OS
    $os    = implode(' ', Asset_host_properties::get_property_from_db($conn, $_id, 3));
    
    $_os   = '';
    $_dev  = '';
    

    if (preg_match("/windows/i", $os))
    {
        $_os = 'Windows';
    }
    elseif (preg_match("/linux/i", $os))
    {
        $_os = 'Linux';
    }

    // Get device types
    $_devices = new Asset_host_devices($conn, $_id);
    $_devices->load_from_db($conn);
    
    $devices  = $_devices->get_devices();
    
   
    if ($_os == 'Windows')
    {
        $_dev = 'windows_';
    }
    elseif (is_array($devices[1]) && !empty($devices[1]))
    {
        if ($_os == 'Linux')
        {
            $_dev = 'linux_server';
        }
        else
        {
            $_dev = '_';
        }
    }
    elseif (is_array($devices[4]) && !empty($devices[4]))
    {
        $_dev = '_networkdevice';
    }
    elseif (count($devices) > 0)
    {
        $_dev = '_';
    }
    
    
    $_res[] = Util::utf8_encode2($asset_data['name']);
    $_res[] = Asset::format_to_print($asset_data['ips']);
    $_res[] = $_dev;
    $_res[] = '';
    
    $_res['DT_RowId'] = $_id;

    $results[] = $_res;
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
