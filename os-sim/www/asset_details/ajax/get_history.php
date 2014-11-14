<?php
/**
 * get_history.php
 * 
 * File get_history.php is used to:
 * - Response ajax call from index.php by dataTable jquery plugin
 * - Fill the data into asset details History section
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

// Close session write for real background loading
session_write_close();

$group_id   = GET('group_id');
$maxrows    = (POST('iDisplayLength') != '')    ? POST('iDisplayLength')    : 15;
$search_str = (POST('sSearch') != '')           ? POST('sSearch')           : '';
$from       = (POST('iDisplayStart') != '')     ? POST('iDisplayStart')     : 0;
$order      = (POST('iSortCol_0') != '')        ? POST('iSortCol_0')        : '';
$torder     = POST('sSortDir_0');
$sec        = POST('sEcho');

$order  = 'date';
$torder = (!strcasecmp($torder, 'asc')) ? 'asc' : 'desc';

ossim_valid($group_id,      OSS_HEX,                            'illegal: ' . _('Net or Group ID'));
ossim_valid($maxrows,       OSS_DIGIT,                          'illegal: ' . _('Configuration Parameter 1'));
ossim_valid($search_str,    OSS_INPUT, OSS_NULLABLE,            'illegal: ' . _('Search String'));
ossim_valid($from,          OSS_DIGIT,                          'illegal: ' . _('Configuration Parameter 1'));
ossim_valid($order,         OSS_ALPHA, OSS_DOT, OSS_SCORE,      'illegal: ' . _('Configuration Parameter 2'));
ossim_valid($torder,        OSS_ALPHA,                          'illegal: ' . _('Configuration Parameter 3'));
ossim_valid($sec,           OSS_DIGIT,                          'illegal: ' . _('Configuration Parameter 4'));

if (ossim_error()) 
{
    $response['sEcho']                = intval($sec);
    $response['iTotalRecords']        = 0;
    $response['iTotalDisplayRecords'] = 0;
    $response['aaData']               = array();
        
    echo json_encode($response);
    exit();
}

$db   = new ossim_db();
$conn = $db->connect();

// Get object from session
$asset_object            = unserialize($_SESSION['asset_detail'][$group_id]);

if (!is_object($asset_object))
{
    throw new Exception(_('Error retrieving the asset data from memory'));
}

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

/* End of file get_history.php */
/* Location: ./asset_details/ajax/get_history.php */