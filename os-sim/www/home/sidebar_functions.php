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
    require_once '../dashboard/sections/widgets/data/sensor_filter.php';

    $tz  = Util::get_timezone();
    $tzc = Util::get_tzc($tz);

    $data = array();

    $fringe = gmdate('U') - (3600);
    $fringe = gmdate('Y-m-d H:i:s', $fringe);


    $ctx_where = (Session::get_ctx_where() != '') ? " AND ctx IN (".Session::get_ctx_where().")" : "";
    list($join, $where) = make_asset_filter('event');


    $sql = "SELECT COUNT(id) as num_events, minute(convert_tz(timestamp,'+00:00','$tzc')) as minute
				FROM alienvault_siem.acid_event $join
				WHERE 1=1 $where $ctx_where AND timestamp >= '$fringe' 
				GROUP BY minute
				ORDER BY timestamp ASC";


    if (!$rg = & $conn->Execute($sql))
    {
        print $conn->ErrorMsg();
    }
    else
    {
        while (!$rg->EOF)
        {
            $data[$rg->fields['minute']] = $rg->fields['num_events'];

            $rg->MoveNext();
        }
    }

    return $data;
}


function calc_events_trend($conn)
{
    $tz     = Util::get_timezone();
    $timetz = gmdate("U") + (3600 * $tz) - 3600;

    $values = SIEM_trend($conn);

    $data  = array();
    $label = array();

    for ($i = 0; $i < 60; $i++)
    {
        //Data
        $h      = gmdate("i", $timetz + (60 * $i));
        $h      = preg_replace("/^0/", '', $h);
        $data[] = ($values[$h] != "") ? $values[$h] : 0;

        //Label
        $label[] = gmdate("Y-m-d  H:i", $timetz + (60 * $i));
    }

    return array($label, $data);
}


function calc_system_eps($conn)
{

    $perms_where = (Session::get_ctx_where() != '') ? " AND entity_id IN (".Session::get_ctx_where().")" : "";
    //
    $sql = "SELECT SUM( stat ) AS eps FROM acl_entities_stats WHERE 1 =1 $perms_where";

    if (!$rs = & $conn->Execute($sql))
    {
        $eps = 0;
    }
    else
    {
        $eps = (empty($rs->fields['eps'])) ? 0 : $rs->fields['eps'];
    }

    return number_format($eps, 0);
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


function calc_days_to_expire()
{
    $trial_days = Session::trial_days_to_expire();

    if (!Session::is_pro())
    {
        return FALSE;
    }

    if ($trial_days <= 30)
    {
        $trial_days = (intval($trial_days) <= 0) ? 0 : intval($trial_days);

        return $trial_days;
    }

    return FALSE;
}


function calc_otx_notif()
{
    $conf      = $GLOBALS['CONF'];
    $check_otx = $conf->get_conf('open_threat_exchange');

    if (Session::am_i_admin() && $check_otx == '')
    {
        return TRUE;
    }

    return FALSE;
}


function get_status_messages()
{
    try
    {
        $status = new System_status();
        list($message_list, $total_messages) = $status->get_status_messages();
    }
    catch (Exception $e)
    {
        $total_messages = 0;
    }

    if ($total_messages)
    {
        foreach ($message_list as $msg)
        {
            if ($msg['viewed'] == FALSE)
            {
                return TRUE;
            }
        }
    }

    return FALSE;
}


/**
 * @param object $conn  DataBase access object
 *
 * @return array
 */
function calc_sensors_status($conn)
{
    // Get component list
    $avc_list = Av_center::get_avc_list($conn);

    $total = count($avc_list['data']);
    $up    = $total;
    $down  = 0;

    // Get notifications list
    $filters = array(
        'level'      => 'notification',
        'message_id' => 11,
    );

    $pagination = array(
        'page'       => 1,
        'page_rows'  => $total
    );

    $status = new System_status();
    list($notification_list, $total_notifications) = $status->get_status_messages($filters, $pagination);

    if ($total_notifications > 0)
    {
        $notification_components = array();
        foreach ($notification_list as $notification)
        {
            $notification_components[$notification['component_id']] = 1;
        }

        foreach ($avc_list['data'] as $avc_data)
        {
            if (preg_match('/sensor/i', $avc_data['profile']))
            {
                if (isset($notification_components[Util::uuid_format($avc_data['system_id'])]))
                {
                    $down++;
                    $up--;
                }
            }
        }
    }

    return array($total, $up, $down);
}
