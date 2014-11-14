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


Session::logcheck('dashboard-menu', 'ControlPanelMetrics');

// Initialize var

$month          = 2592000;
$num_hosts      = 10;
$date_from      = date('Y-m-d', strtotime('-365 days')).' 00:00:00';
$date_to        = date("Y-m-d").' 23:59:59';

$report_type    = "Metrics";

unset($general['Metrics']);
//
$general['Metrics']['date_from']      = $date_from;
$general['Metrics']['date_from_unix'] = explode('-', $general['Metrics']['date_from']);
$general['Metrics']['date_from_unix'] = mktime(0, 0, 0, $general['Metrics']['date_from_unix'][1], $general['Metrics']['date_from_unix'][2], $general['Metrics']['date_from_unix'][0]);

$general['Metrics']['date_to']        = $date_to;
$general['Metrics']['date_to_unix']   = explode('-', $general['Metrics']['date_to']);
$general['Metrics']['date_to_unix']   = mktime(0, 0, 0, $general['Metrics']['date_to_unix'][1], $general['Metrics']['date_to_unix'][2], $general['Metrics']['date_to_unix'][0]);



$conf = $GLOBALS['CONF'];


$db         = new ossim_db();
$conn       = $db->connect();
$query_temp = array();
$allowed    = array();



$date_from_sql = "'".$date_from ."'";
$date_to_sql   = "'".$date_to ."'";

$dates_filter               = array();
$dates_filter['max_c_date'] = "AND (max_c_date BETWEEN $date_from_sql AND $date_to_sql)";
$dates_filter['max_a_date'] = "AND (max_a_date BETWEEN $date_from_sql AND $date_to_sql)";

// Join with control_panel. Prevent too long lists
$host_filters['where'] = 'control_panel.id = HEX(host.id)';
$nets_filters['where'] = 'control_panel.id = HEX(net.id)';

$param['hosts']  = get_allowed_hosts($conn, ', control_panel', $host_filters);
$param['nets']   = get_allowed_nets($conn, ', control_panel', $nets_filters);
$param['user']   = Session::get_session_user();

/*
echo "<pre>";
    print_r($param);
echo "</pre>";
exit;
*/



$dateDiff = strtotime($date_to) - strtotime($date_from);
$ddiff    = floor($dateDiff/(60*60*24));

function extractArray($array,$num_elem = NULL)
{
    $num_elem  = ($num_elem == NULL) ? 10 : $num_elem;
    $arrayTemp = (count($array)>$num_elem) ? array_splice($array,0,$num_elem) : $array;
      
    return $arrayTemp;
}

function createTable($data, &$htmlPdfReport)
{
	if (count($data['data']) <= 1) 
	{
		$htmlPdfReport->set('
					<tr style="text-align:center">
						<th style="width:40mm">'.$data['data'][0][0].'</th>
						<th style="width:105mm">'.$data['data'][0][1].'</th>
						<th style="width:40mm">'.$data['data'][0][2].'</th>
					</tr>
					<tr style="text-align:center">
						<td style="width:40mm"  class="center">'.str_replace(',', '<br/>', $data['asset']).'</td>
						<td style="width:105mm" class="center">'._('No data available').'</td>
						<td style="width:40mm"  class="center">'.$data['date'].'</td>
					</tr>
					');
	}
	else
	{
		foreach($data['data'] as $key => $value)
		{
			if($key == 0)
			{
				$c = 0;
				
				$htmlPdfReport->set('
						<tr style="text-align:center">
							<th style="width:40mm">'.$value[0].'</th>
							<th style="width:105mm">'.$value[1].'</th>
							<th style="width:40mm">'.$value[2].'</th>
						</tr>
					');
            }
			else
			{
				
				$bc = ($c++%2 != 0) ? "class='par'" : '';
				
				$htmlPdfReport->set('
					<tr '.$bc.' style="text-align:center">
							<td style="width:40mm">'.str_replace(',', '<br/>', $value[0]).'</td>
							<td style="width:105mm">'.$value[1].'</td>
							<td style="width:40mm">'.$value[2].'</td>
						</tr>
					');
				
			}
		}
	}
}
?>