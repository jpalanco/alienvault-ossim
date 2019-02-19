<?php
/**
 * dt_schedule_scan.php
 *
 * File dt_schedule_scan.php is used to:
 *  - Build JSON data that will be returned in response to the Ajax request made by DataTables (Task list)
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

$scan_types = array(
    'nmap' => 5,
    'wmi'  => 4
);

$frequencies = array(
    '3600'    => 'Hourly',
    '86400'   => 'Daily',
    '604800'  => 'Weekly',
    '2419200' => 'Monthly'
);

$s_type = POST('s_type');
$s_type = (empty($s_type)) ? $_SESSION['av_inventory_type'] : $s_type;

session_write_close();


if (!array_key_exists($s_type, $scan_types))
{
    $e_message = _('You do not have the correct permissions to see this page. Please contact system administrator with any questions');

    Util::response_bad_request($e_message);
}

Session::logcheck_ajax('environment-menu', 'AlienVaultInventory');

$data = array();

try
{
    $db   = new ossim_db();
    $conn = $db->connect();

    $task_list = Inventory::get_list($conn, '', $scan_types[$s_type]);

    foreach($task_list as $task)
    {
        $sensor_name = Av_sensor::get_name_by_id($conn, $task['task_sensor']);

        if ($sensor_name == '')
        {
            $sensor_name = _('Unknown');
        }

        if ($s_type == 'wmi')
        {
            preg_match('/wmipass:(.*)/', $task['task_params'], $matches);

            if ($matches[1] != '')
            {
                $task['task_params'] = preg_replace('/wmipass:(.*)/', '', $task['task_params']);
                $task['task_params'] = $task['task_params'] . 'wmipass:' . preg_replace('/./', '*', $matches[1]);
            }
        }
        elseif ($s_type == 'nmap')
        {
            list($tp) = Util::nmap_without_excludes($task['task_params']);
            $task['task_params'] = implode(", ",$tp);
        }

        $s_data = array(
            "DT_RowId" => $task['task_id'],
            "DT_RowData" => array(
                's_type'     => $s_type,
                'sensor_id'  => $task['task_sensor'],
                'params'     => $task['task_params'],
                'frecuency'  => $task['task_period'],
                'enabled'    => $task['task_enable']
            ),
            $task['task_name'],
            $sensor_name,
            $task['task_params'],
            $frequencies[$task['task_period']],
            $task['task_enable'],
            '',
        );

        $data[] = $s_data;
    }
}
catch(Exception $e)
{
    $db->close();

    Util::response_bad_request($e->getMessage());
}

$response = array('aaData' => $data);

$db->close();

echo json_encode($response);

/* End of file dt_schedule_scan.php */
/* Location: /av_schedule_scan/providers/dt_schedule_scan.php */
