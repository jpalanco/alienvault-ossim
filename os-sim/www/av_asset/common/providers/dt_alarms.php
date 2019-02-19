<?php
/**
 * dt_alarms.php
 *
 * File dt_alarms.php is used to:
 *  - Build JSON data that will be returned in response to the Ajax request made by DataTables (Alarm list)
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

$asset_id   =  POST('asset_id');
$asset_type =  POST('asset_type');

$maxrows    = (POST('iDisplayLength') != '') ? POST('iDisplayLength') : 8;
$from       = (POST('iDisplayStart') != '')  ? POST('iDisplayStart')  : 0;

$order      = (POST('iSortCol_0') != '')     ? POST('iSortCol_0')     : '';
$torder     =  POST('sSortDir_0');
$search_str = (POST('sSearch') != '')        ? POST('sSearch') : '';

$sec        =  POST('sEcho');


Session::logcheck_by_asset_type($asset_type);
Session::logcheck_ajax('analysis-menu', 'ControlPanelAlarms');

// Close session write for real background loading
session_write_close();

ossim_valid($asset_id,      OSS_HEX,                                  'illegal: '._('Asset ID'));
ossim_valid($asset_type,    OSS_LETTER, OSS_SCORE, OSS_NULLABLE,      'illegal: '._('Asset Type'));
ossim_valid($maxrows,       OSS_DIGIT,                                'illegal: iDisplayLength');
ossim_valid($from,          OSS_DIGIT,                                'illegal: iDisplayStart');
ossim_valid($order,         OSS_ALPHA,                                'illegal: iSortCol_0');
ossim_valid($torder,        OSS_LETTER,                               'illegal: sSortDir_0');
ossim_valid($search_str,    OSS_INPUT, OSS_NULLABLE,                  'illegal: sSearch');
ossim_valid($sec,           OSS_DIGIT,                                'illegal: sEcho');


if (ossim_error())
{
    Util::response_bad_request(ossim_get_error_clean());
}

// Check Asset Type
$asset_types = array(
    'asset'   => 'asset_host',
    'network' => 'asset_net',
    'group'   => 'asset_group'
);


// Order by column
$orders_by_columns = array(
    '0' => "a.timestamp $torder",                                   // Order by Date
    '2' => "ki.name $torder, ca.name $torder, a.timestamp $torder", // Order by Category
    '3' => "ta.subcategory $torder, a.timestamp $torder",           // Order by Subcategory
    '4' => "a.risk $torder, a.timestamp $torder",           // Order by Subcategory
);


try
{
    $db   = new ossim_db();
    $conn = $db->connect();

    $geoloc = new Geolocation('/usr/share/geoip/GeoLiteCity.dat');

    $no_resolv = FALSE;

    if (isset($_POST['asset_id']) && isset($_POST['asset_type']))
    {
        if (!array_key_exists($asset_type, $asset_types))
        {
            Av_exception::throw_error(Av_exception::USER_ERROR, _('Error! Invalid Asset Type'));
        }

        $class_name = $asset_types[$_POST['asset_type']];

        // Check Asset Permission
        if (method_exists($class_name, 'is_allowed') && !$class_name::is_allowed($conn, $asset_id))
        {
            $error = sprintf(_('Error! %s is not allowed'), ucwords($asset_type));

            Av_exception::throw_error(Av_exception::USER_ERROR, $error);
        }

        $asset_object = $class_name::get_object($conn, $asset_id);


        //Alarm filters
        if (array_key_exists($order, $orders_by_columns))
        {
            $order = $orders_by_columns[$order];
        }
        else
        {
            $order = "a.timestamp $torder";
        }

        if ($search_str != '')
        {
            $search_str = escape_sql($search_str, $conn);
        }

        list($alarms, $total) = $asset_object::get_alarms($conn, $asset_id, $from, $maxrows, '', '', $search_str, $order);
    }
    else
    {
        Av_exception::throw_error(Av_exception::USER_ERROR, _('Error retrieving information'));
    }
}
catch(Exception $e)
{
    $geoloc->close();

    $db->close();

    Util::response_bad_request($e->getMessage());
}

$tz = Util::get_timezone();

// DATA
$data = array();

foreach ($alarms as $alarm)
{
    // kingdom, category and subcategory
    $a_taxonomy = $alarm->get_taxonomy();
    if ($a_taxonomy['id'])
    {
        list($alarm_ik, $alarm_sc) = Alarm::get_alarm_name($a_taxonomy);
        $alarm_ik   = str_replace("style/", AV_MAIN_PATH."/alarm/style/", $alarm_ik);
        $alarm_tr   = Util::translate_alarm($conn, $alarm_sc, $alarm, "array");
        $alarm_sc   = $alarm_tr['name'];
    }
    else
    {
        $alarm_name = Util::translate_alarm($conn, $alarm->get_sid_name(), $alarm, "array");
        $alarm_ik   = $alarm_name['name'];
        $alarm_sc   = '';
    }

    // Date
    $timestamp_utc = Util::get_utc_unixtime(Util::timestamp2date($alarm->get_timestamp())); // $alarm->get_last()
    $alarm_date    = gmdate("Y-m-d H:i:s",$timestamp_utc+(3600*$tz));

    // Src Dst
    $src_ip     = $alarm->get_src_ip();
    $dst_ip     = $alarm->get_dst_ip();
    $src_port   = $alarm->get_src_port();
    $dst_port   = $alarm->get_dst_port();
    $event_info = $alarm->get_event_info();
    $src_host   = Asset_host::get_object($conn, $event_info["src_host"]);
    $dst_host   = Asset_host::get_object($conn, $event_info["dst_host"]);
    $src_net_id = $event_info["src_host"];
    $dst_net_id = $event_info["dst_host"];

    // Src
    if ($no_resolv || !$src_host)
    {
        $src_name = $src_ip;
        $src_desc = '';
        $ctx_src  = $event_info["agent_ctx"];
    }
    elseif ($src_host)
    {
        $src_desc = ($src_host->get_descr() != '') ? ": ".$src_host->get_descr() : '';
        $src_name = $src_host->get_name();
        $ctx_src  = $src_host->get_ctx();
    }

    // Dst
    if ($no_resolv || !$dst_host)
    {
        $dst_name = $dst_ip;
        $dst_desc = '';
        $ctx_dst  = $event_info["agent_ctx"];
    }
    elseif ($dst_host)
    {
        $dst_desc = ($dst_host->get_descr() != '') ? ": ".$dst_host->get_descr() : '';
        $dst_name = $dst_host->get_name();
        $ctx_dst  = $dst_host->get_ctx();
    }

    // Src icon and bold
    $src_output   = Asset_host::get_extended_name($conn, $geoloc, $src_ip, $ctx_src, $event_info["src_host"], $event_info["src_net"]);
    $homelan_src  = $src_output['is_internal'];
    $src_img      = preg_replace("/scriptinfo/", '', $src_output['html_icon']); // Clean icon hover tiptip

    // Dst icon and bold
    $dst_output   = Asset_host::get_extended_name($conn, $geoloc, $dst_ip, $ctx_dst, $event_info["dst_host"], $event_info["dst_net"]);
    $homelan_dst  = $dst_output['is_internal'];
    $dst_img      = preg_replace("/scriptinfo/", '', $dst_output['html_icon']); // Clean icon hover tiptip

    //host report menu:
    $src_hrm = "$src_ip;$src_name;".$event_info['src_host'];
    $dst_hrm = "$dst_ip;$dst_name;".$event_info['dst_host'];

    //Port Check
    $src_name .= ($src_port) ? ':' . $src_port : '';
    $dst_name .= ($dst_port) ? ':' . $dst_port : '';

    //Wrapping Text
    $src_name = Util::wordwrap($src_name, 30, '<br/>');
    $dst_name = Util::wordwrap($dst_name, 30, '<br/>');

    //Homeland Check
    $src_name = ($homelan_src) ? " <strong>$src_name</strong>" : " $src_name";
    $dst_name = ($homelan_dst) ? " <strong>$dst_name</strong>" : " $dst_name";

    $alarm_otx = $alarm->get_otx_icon();

    // COLUMNS
    $_res = array();

    $_res['DT_RowId']  = $alarm->get_backlog_id();

    $_res[] = $alarm_date;
    $_res[] = $alarm->get_status();
    $_res[] = $alarm_ik;
    $_res[] = $alarm_sc;
    $_res[] = $alarm->get_risk();
    $_res[] = $alarm_otx;
    $_res[] = "<div class='HostReportMenu' id='$src_hrm'>".$src_img . $src_name ."</div>";
    $_res[] = "<div class='HostReportMenu' id='$dst_hrm'>".$dst_img . $dst_name ."</div>";
    $_res[] = '';

    $data[] = $_res;
}

$response['sEcho']                = $sec;
$response['iTotalRecords']        = $total;
$response['iTotalDisplayRecords'] = $total;
$response['aaData']               = $data;

echo json_encode($response);

$db->close();

$geoloc->close();

/* End of file dt_alarms.php */
/* Location: /av_asset/common/providers/dt_alarms.php */
