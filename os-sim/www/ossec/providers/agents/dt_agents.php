<?php
/**
 * dt_agents.php
 *
 * File dt_agents.php is used to:
 *  - Build JSON data that will be returned in response to the Ajax request made by DataTables (Agent list)
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


require_once dirname(__FILE__) . '/../../conf/config.inc';

session_write_close();

Session::logcheck('environment-menu', 'EventsHidsConfig');

$events_hids_config = Session::menu_perms('environment-menu', 'EventsHidsConfig');

try
{
    $db   = new ossim_db();
    $conn = $db->connect();

    $sensor_id = POST('sensor_id');

    ossim_valid($sensor_id, OSS_HEX, 'illegal:' . _('Sensor ID'));

    if (!ossim_error())
    {
        if (!Ossec_utilities::is_sensor_allowed($conn, $sensor_id))
        {
            $e_msg = _('Error! Sensor not allowed');

            Av_exception::throw_error(Av_exception::USER_ERROR, $e_msg);
        }
    }
    else
    {
        $e_msg = ossim_get_error_clean();

        Av_exception::throw_error(Av_exception::USER_ERROR, $e_msg);
    }


    $agents = Ossec_agent::get_list($sensor_id);
    $data   = array();

    if (is_array($agents) && !empty($agents))
    {
        foreach ($agents as $agent_id => $a_data)
        {
            if (empty($a_data))
            {
                continue;
            }

            $a_unique_id = md5($agent_id);
            $agent_actions = Ossec_agent::get_actions($agent_id, $a_data);

            if (!empty($a_data['host_id']))
            {
                $asset_name = Asset_host::get_name_by_id($conn, $a_data['host_id']);
            }
            else
            {
                $asset_name = '-';
            }

            //Normalize status description (See asset list filters)
            if ($a_data['status']['id'] == 1)
            {
                $a_data['status']['descr'] = 'Disconnected';
            }

            $t_data = array(
                "DT_RowId"   => 'cont_agent_'.$agent_id,
                "DT_RowData" => array(
                    'agent_key'    => $a_unique_id,
                    'asset_id'     => $a_data['host_id'],
                    'agent_status' => $a_data['status']
                ),
                '',
                $agent_id,
                $a_data['name'],
                $asset_name,
                $a_data['ip_cidr'],
                "-",
                "-",
                $a_data['status']['descr'],
                $agent_actions
            );

            $data[] = $t_data;
        }
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

/* End of file dt_agents.php */
/* Location: /ossec/providers/dt_agents.php */
