<?php
/**
 * dt_backup.php
 * 
 * File dt_backup.php is used to:
 * - Response ajax call from backup dataTable
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


if (!Session::am_i_admin())
{
    $error = _("You do not have permission to see this section");
    
    Util::response_bad_request($error);
}

// Close session write for real background loading
session_write_close();

$maxrows    = (POST('iDisplayLength') != '') ? POST('iDisplayLength') : 10;
$from       = (POST('iDisplayStart') != '')  ? POST('iDisplayStart')  : 0;

$order      = (POST('iSortCol_0') != '')     ? POST('iSortCol_0')     : '';
$torder     =  POST('sSortDir_0');
$search_str = (POST('search') != '')         ? POST('search') : '';

$sec        =  POST('sEcho');

$system_id  =  POST('system_id');


ossim_valid($maxrows,       OSS_DIGIT,                                'illegal: iDisplayLength');
ossim_valid($from,          OSS_DIGIT,                                'illegal: iDisplayStart');
ossim_valid($order,         OSS_ALPHA,                                'illegal: iSortCol_0');
ossim_valid($torder,        OSS_LETTER,                               'illegal: sSortDir_0');
ossim_valid($search_str,    OSS_INPUT, OSS_NULLABLE,                  'illegal: sSearch');
ossim_valid($sec,           OSS_DIGIT,                                'illegal: sEcho');
ossim_valid($system_id,     OSS_UUID,                                 'illegal: System ID');

if (ossim_error())
{
    Util::response_bad_request(ossim_get_error_clean());
}


$tz = Util::get_timezone();


$backup_list = array();
$data        = array();


try
{
    $backup_object    = new Av_backup($system_id, 'configuration');
    $_backup_list_arr = $backup_object->get_backup_list();
}
catch (Exception $e)
{
    $exp_msg = $e->getMessage();
    Util::response_bad_request($exp_msg);
}


// Get and store the last backup date
$_last_date = strtotime("19700101000000");

foreach ($_backup_list_arr as $_backup_data)
{
    if ($_backup_data['date'] > $_last_date)
    {
        $_last_date = $_backup_data['date'];
    }
}

$_last_date = gmdate('U', $_last_date + (3600*$tz));

$backup_object->set_session_last_date($_last_date);



// Get total before filtering
$total = count($_backup_list_arr);

// Filter by search string
if ($search_str != '')
{
    $_backup_list_copy = array();
    
    foreach ($_backup_list_arr as $_backup_data)
    {
        $cnd1 = preg_match('/'.preg_quote($search_str, '/').'/', gmdate('Y-m-d H:i:s', $_backup_data['date'] + (3600*$tz)));
        $cnd2 = preg_match('/'.preg_quote($search_str, '/').'/', $_backup_data['admin_ip']);
        $cnd3 = preg_match('/'.preg_quote($search_str, '/').'/i', $_backup_data['system_name']);
        $cnd4 = preg_match('/'.preg_quote($search_str, '/').'/i', $_backup_data['method']);
        
        if ($cnd1 || $cnd2 || $cnd3 || $cnd4)
        {
            $_backup_list_copy[] = $_backup_data;
        }
    }
    
    $_backup_list_arr = $_backup_list_copy;
}

// Get total after filtering
$total_display = count($_backup_list_arr);



// Order by column
$orders_by_columns = array(
        '2' => 'date',  // Order by Backup Date
        '5' => 'size'   // Order by Backup Size
);

if (array_key_exists($order, $orders_by_columns))
{
    $order = $orders_by_columns[$order];
}
else
{
    $order = 'date';
}

// Apply order by hash key. The key can be 'date' or 'size'
foreach ($_backup_list_arr as $_backup_data)
{
    $_order_key               = $_backup_data[$order];
    $backup_list[$_order_key] = $_backup_data;
}

// If order is "date desc", there's no need to sort
if ($order != 'date' || $torder != 'desc')
{
    if ($torder == 'desc') // date desc
    {
        arsort($backup_list);
    }
    else // size|date asc
    {
        asort($backup_list);
    }
}


// Reactive session to store backup lengths
session_start();


// Filter by pagination and fill $data

$i = 0;

foreach ($backup_list as $_key => $backup_data)
{
    if ($i >= $from && $i < $from + $maxrows)
    {
        $_res = array();
    
        $_backup_id = $backup_data['file'];
        
        $_res['DT_RowId'] = $_backup_id;
        
        $_res[] = '';  //Checkbox
        $_res[] = $backup_data['system_name'].' ('.$backup_data['admin_ip'].')';
        $_res[] = gmdate('Y-m-d H:i:s', $backup_data['date'] + (3600*$tz));
        $_res[] = ucfirst($backup_data['type']);
        $_res[] = ucfirst($backup_data['method']);
        $_res[] = $backup_data['version'].' '.$backup_data['version_number'];
        $_res[] = Util::bytes_to_size($backup_data['size']);
        $_res[] = '<a href="javascript:;" class="download_button" data-backup_file="'.$backup_data['file'].'">
                     <img src="/ossim/pixmaps/forensic_download.png" border="0">
                   </a>';  //Download
        
        $data[] = $_res;
        
        
        // Save file size in session, using after when download
        $backup_object->set_session_file_size($backup_data['file'], $backup_data['size']);
    }
    
    $i++;
}

$response['sEcho']                = $sec;
$response['iTotalRecords']        = $total;
$response['iTotalDisplayRecords'] = $total_display;
$response['aaData']               = $data;

echo json_encode($response);



/* End of file dt_backup.php */
/* Location: /av_backup/providers/dt_backup.php */
