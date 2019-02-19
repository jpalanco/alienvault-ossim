<?php

/**
 * License:
 *
 * Copyright (c) 2003-2006 ossim.net
 * Copyright (c) 2007-2014 AlienVault
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
 */


define('MAX_EPS', 5000);


function SIEM_trend($conn)
{
    require_once '../../dashboard/sections/widgets/data/sensor_filter.php';

    $tz  = Util::get_timezone();
    $tzc = Util::get_tzc($tz);

    $data = array();

    $fringe = gmdate('U') - 86400;
    $fringe = gmdate('Y-m-d H:00:00', $fringe);


    $ctx_where = (Session::get_ctx_where() != '') ? " AND ctx IN (".Session::get_ctx_where().")" : "";
    list($join, $where) = make_asset_filter('event');


    $sql = "SELECT sum(cnt) as num_events, convert_tz(timestamp,'+00:00','$tzc') as hour
				FROM alienvault_siem.ac_acid_event acid_event $join
				WHERE 1=1 $where $ctx_where AND timestamp >= '$fringe' 
				GROUP BY hour
				ORDER BY timestamp ASC";
    $rg = $conn->Execute($sql);
    
    if (!$rg)
    {
        print $conn->ErrorMsg();
    }
    else
    {
        while (!$rg->EOF)
        {
            $data[$rg->fields['hour']] = $rg->fields['num_events'];

            $rg->MoveNext();
        }
    }

    return $data;
}


function calc_events_trend($conn)
{
    $values = SIEM_trend($conn);

    $data  = array();
    $label = array();
    $timetz = gmdate('U') - 86400 + 3600;

    for ($i = 0; $i < 24; $i++)
    {
        //Data
        $timetz += 3600;
        $h       = gmdate('Y-m-d H:00:00', $timetz);
        $data[]  = ($values[$h] != "") ? $values[$h] : 0;

        //Label
        $label[] = gmdate("Y-m-d H\h", $timetz);
    }

    return array($label, $data);
}


function calc_system_eps($conn)
{
    $perms_where = (Session::get_ctx_where() != '') ? " AND entity_id IN (".Session::get_ctx_where().")" : "";
    //
    $sql = "SELECT SUM( stat ) AS eps FROM acl_entities_stats WHERE 1 =1 $perms_where";

    $rs = $conn->Execute($sql);

    if (!$rs)
    {
        $eps = 0;
    }
    else
    {
        $eps = (empty($rs->fields['eps'])) ? 0 : $rs->fields['eps'];
    }

    return intval($eps);
}


/**
 * Returns remaining days to trial expiration or FALSE
 *
 * @return bool|int
 */
function calc_days_to_expire()
{
    $trial_days = FALSE;

    if (Session::is_pro())
    {
        $days_to_expire = Session::trial_days_to_expire();

        if ($days_to_expire <= 30)
        {
            $trial_days = (intval($days_to_expire) <= 0) ? 0 : intval($days_to_expire);
        }
    }

    return $trial_days;
}


function calc_devices_total($conn)
{
    try
    {
        $filter = array();

        //Limit one bcz we only want the count
        $filter['limit'] = '1';

        //Retrieving the total number of hosts from the system.
        list($hosts, $total) = Asset_host::get_list($conn, '', $filter);
    }
    catch (Exception $e)
    {
        $total = 0;
    }

    return intval($total);
}

/**
  * This function calculates status of systems with profile sensor enabled
  *
  * @param object $conn  DataBase access object
  *
  * @return array
  */
function calc_sensors_status($conn)
{
    // Getting system list
    $avc_list = Av_center::get_avc_list($conn);

    $total         = 0;
    $up_sensors    = array();
    $down_sensors  = array();

    // Getting DOWN systems
    $filters = array(
        'level'      => 'error',
        'message_id' => Util::uuid_format('00000000000000000000000000010011'),
    );

    $pagination = array(
        'page'       => 1,
        'page_rows'  => count($avc_list['data'])
    );

    $status = new System_notifications();
    list($notification_list, $total_notifications) = $status->get_status_messages($filters, $pagination);

    if ($total_notifications > 0)
    {
        $down_systems = array();
        foreach ($notification_list as $notification)
        {
            $down_systems[$notification['component_id']] = 1;
        }
    }

    //Calculating UP and DOWN sensors
    if (is_array($avc_list['data']) && !empty($avc_list['data']))
    {
        foreach ($avc_list['data'] as $avc_data)
        {
            if (preg_match('/sensor/i', $avc_data['profile']))
            {
                if (isset($down_systems[Util::uuid_format($avc_data['system_id'])]))
                {
                    $down_sensors[$avc_data['sensor_id']] = 1;
                }
                else
                {
                    $up_sensors[$avc_data['sensor_id']] = 1;
                }
            }
        }
    }

    $up    = count($up_sensors);
    $down  = count($down_sensors);
    $total = $up + $down;

    return array($total, $up, $down);
}

function format_notif_number($number)
{
    $formated = array();
    
	$formated['number']   = $number;
	$formated['text']     = Util::number_format_locale($number);
	$formated['readable'] = Util::number_format_readable($number);
	
	return $formated;	

}
