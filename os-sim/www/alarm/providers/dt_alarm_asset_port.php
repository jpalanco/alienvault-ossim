<?php
/**
 * dt_software.php
 *
 * File dt_software.php is used to:
 *  - Build JSON data that will be returned in response to the Ajax request made by DataTables (Software list)
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

Session::logcheck_ajax("analysis-menu", "ControlPanelAlarms");

// Close session write for real background loading
session_write_close();

$backlog_id =  POST('backlog_id');
$asset_ip   =  POST('asset_ip');
$source     =  POST('source');

$maxrows    = (POST('iDisplayLength') != '') ? POST('iDisplayLength') : 8;
$from       = (POST('iDisplayStart') != '')  ? POST('iDisplayStart')  : 0;
$order      = (POST('iSortCol_0') != '')     ? POST('iSortCol_0')     : '';
$torder     =  POST('sSortDir_0');
$search_str = (POST('sSearch') != '')        ? POST('sSearch') : '';
$sec        =  intval(POST('sEcho'));


ossim_valid($backlog_id,    OSS_HEX,                    'illegal: '._('Backlog ID'));
ossim_valid($asset_ip,      OSS_IP_ADDR_0,              'illegal: '._('Asset IP'));
ossim_valid($source,        'src|dst',                  'illegal: '._('Port Origin'));
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


// Order by column
switch($order) 
{
	case 0:
		$order = 'port';  //Order by hostname
	break;
        
    case 1:
		$order = 'service';  //Order by IP
	break;

    default:
		$order = 'port';
}

$torder = (strtoupper($torder) == 'ASC') ? 'ASC' : 'DESC';

try
{
    $db   = new Ossim_db();
    $conn = $db->connect(TRUE);

    $params = array(
        'backlog_id' => $backlog_id,
        'ip'         => $asset_ip,
        'source'     => $source,
        'limit'      => "$from, $maxrows",
        'order_by'   => "$order $torder"
    );

    list($port_total, $port_list) = Alarm::get_alarm_port_by_ip($conn, $params);
}
catch(Exception $e)
{
    $db->close();

    Util::response_bad_request($e->getMessage());
}


$data = array();
foreach ($port_list as $p_data)
{
    $service = empty($p_data['service']) ? '-' : $p_data['service'];
    
    $data[] = array(
        $p_data['port'],
        $service
    );
}

$response['sEcho']                = $sec;
$response['iTotalRecords']        = $port_total;
$response['iTotalDisplayRecords'] = $port_total;
$response['aaData']               = $data;

echo json_encode($response);

$db->close();
