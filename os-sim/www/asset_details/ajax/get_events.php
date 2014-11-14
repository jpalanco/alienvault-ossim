<?php
/**
 * get_events.php
 * 
 * File get_events.php is used to:
 * - Response ajax call from index.php by dataTable jquery plugin
 * - Fill the data into asset details Events section
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
Session::logcheck('environment-menu', 'PolicyHosts');

$asset_id   = GET('asset_id');
$asset_type = GET('asset_type');
$maxrows    = (POST('iDisplayLength') != '') ? POST('iDisplayLength') : 15;
$search_str = (POST('sSearch') != '') ? POST('sSearch') : '';
$from       = (POST('iDisplayStart') != '') ? POST('iDisplayStart') : 0;
$order      = (POST('iSortCol_0') != '') ? POST('iSortCol_0') : '';
$torder     = POST('sSortDir_0');
$sec        = POST('sEcho');


switch($order)
{
    case 0:
        $order = 'signature';
    break;
    
    case 1:
        $order = 'datasource';
    break;
    
    case 2:
        $order = 'timestamp';
    break;
    
    default:
        $order = 'timestamp';
    break;

}

if ($order == 'signature')
{
    $order = 'alienvault.plugin_sid.name';
}

if ($order == 'datasource')
{
    $order = 'alienvault.plugin.name';
}

ossim_valid($asset_id,      OSS_HEX,                        'illegal: ' . _('Asset ID'));
ossim_valid($asset_type,    OSS_ALPHA,                      'illegal: ' . _('Asset Type'));
ossim_valid($maxrows,       OSS_DIGIT,                      'illegal: ' . _('Configuration Parameter 1'));
ossim_valid($search_str,    OSS_INPUT, OSS_NULLABLE,        'illegal: ' . _('Search String'));
ossim_valid($from,          OSS_DIGIT,                      'illegal: ' . _('Configuration Parameter 2'));
ossim_valid($order,         OSS_ALPHA, OSS_DOT, OSS_SCORE,  'illegal: ' . _('Configuration Parameter 3'));
ossim_valid($torder,        OSS_ALPHA,                      'illegal: ' . _('Configuration Parameter 4'));
ossim_valid($sec,           OSS_DIGIT,                      'illegal: ' . _('Configuration Parameter 5'));

if (ossim_error()) 
{
    $response['sEcho']                = intval($sec);
    $response['iTotalRecords']        = 0;
    $response['iTotalDisplayRecords'] = 0;
    $response['aaData']               = array();    
        
    echo json_encode($response);
    exit();
}

$db       = new ossim_db();
$conn     = $db->connect();

// Filter by asset
$siem = new SIEM();

if ($asset_type == 'net')
{
    $siem->add_criteria(array('src_net', 'dst_net'), $asset_id);
}
elseif ($asset_type == 'group')
{
    $asset_group = Asset_group::get_object($conn, $asset_id);
    $_list_data  = $asset_group->get_hosts($conn, array(), TRUE);
    $hosts       = array_keys($_list_data[0]);
    $criterias   = array();
    $values      = array();
    // Mount OR filters
    foreach ($hosts as $host_id)
    {
        $criterias[] = 'src_host';
        $criterias[] = 'dst_host';
        $values[]    = $host_id;
        $values[]    = $host_id;
    }
    
    $siem->add_criteria($criterias, $values);
}
else
{
    $siem->add_criteria(array('src_host', 'dst_host'), $asset_id);
}

// Search by signature
if ($search_str != '')
{
    $search_str = escape_sql($search_str, $conn); 
    
    $siem->add_criteria("plugin_sid.name", "%".$search_str."%", "LIKE");
}

$events = $siem->get_events_light($from, $maxrows, $order, $torder);
$data   = array();

// Total +1 to enable blind pagination
$total  = (count($events) < $maxrows) ? $from + count($events) : $from + $maxrows + 1;

foreach ($events as $event)
{
    // Case Network
    if ($asset_type == 'net')
    {
        // Incoming / Outcoming
        if ($event['src_net'] == $event['dst_net'])
        {
            $io = '-';
        }
        else
        {
            $io = ($event['dst_net'] == $asset_id) ? _('Incoming') : _('Outgoing');
        }
        
        $data[] = array(
            $event['signature'],
            $event['datasource'],
            $event['date'],
            $io,
            $event['source'],
            $event['destination'],
            $event['sensor'],
            $event['risk']
        );
    }
    // Case Host
    else
    {
        // Incoming / Outcoming
        if ($event['src_host'] == $event['dst_host'])
        {
            $io = '-';
        }
        else
        {
            $io = ($event['dst_host'] == $asset_id) ? _('Incoming') : _('Outgoing');
        }
        
        // Source or Destination depending on IO
        $srcdst = ($event['dst_host'] == $asset_id) ? $event['source'] : $event['destination'];
        
        $data[]= array(
            $event['signature'],
            $event['datasource'],
            $event['date'],
            $io,
            $srcdst,
            $event['sensor'],
            $event['risk']
        );
    }
}                               

$response['sEcho']                = $sec;
$response['iTotalRecords']        = $total;
$response['iTotalDisplayRecords'] = $total;
$response['aaData']               = $data;

echo json_encode($response);                            

$db->close(); 

/* End of file get_events.php */
/* Location: ./asset_details/ajax/get_events.php */