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
require_once '../alarm_common.php';

set_time_limit(0);

Session::logcheck('analysis-menu', 'ControlPanelAlarms');


/* DataTables Parameters */
$sec    = intval(POST('sEcho'));
$limit  = POST('iDisplayLength');
$offset = POST('iDisplayStart');

$limit  = ($limit != '') ? intval($limit) : 15;
$offset = ($offset != '') ? intval($offset) : 0;

$inf    = $offset;
$sup    = $offset + $limit;

/* Filter Parameters */

$sensor_query  = POST('sensor_query');
$src_ip        = POST('src_ip');
$dst_ip        = POST('dst_ip');
$asset_group   = POST('asset_group');
$hide_closed   = POST('hide_closed');
$from_date     = POST('date_from');
$to_date       = POST('date_to');
$similar       = intval(POST('similar'));
$directive_id  = POST('directive_id');
$tag           = POST('tag');
$num_events    = POST('num_events');
$num_events_op = POST('num_events_op');
$no_resolv     = intval(POST('no_resolv'));
$timestamp     = POST('timestamp');
$group_id      = POST('name');

if($similar != 1)
{
    $name = $_SESSION[$group_id];
}
else
{
    $name = $group_id;
}


$timestamp = preg_replace("/\s\d\d\:\d\d\:\d\d$/","",$timestamp);

ossim_valid($group_id,       OSS_HEX,                                                     'illegal:' . _("Group ID"));
ossim_valid($sensor_query,   OSS_HEX, OSS_NULLABLE,                                       'illegal:' . _("Sensor_query"));
ossim_valid($src_ip,         OSS_IP_ADDRCIDR_0, OSS_NULLABLE,                             'illegal:' . _("Src_ip"));
ossim_valid($dst_ip,         OSS_IP_ADDRCIDR_0, OSS_NULLABLE,                             'illegal:' . _("Dst_ip"));
ossim_valid($asset_group,    OSS_HEX, OSS_NULLABLE,                                       'illegal:' . _("Asset Group"));
ossim_valid($hide_closed,    OSS_DIGIT, OSS_NULLABLE,                                     'illegal:' . _("Hide Close"));
ossim_valid($from_date,      OSS_DIGIT, OSS_SCORE, OSS_NULLABLE,                          'illegal:' . _("From_date"));
ossim_valid($to_date,        OSS_DIGIT, OSS_SCORE, OSS_NULLABLE,                          'illegal:' . _("To_date"));
ossim_valid($name,           OSS_DIGIT, OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE, '\>\<',    'illegal:' . _("Name"));
ossim_valid($directive_id,   OSS_DIGIT, OSS_NULLABLE,                                     'illegal:' . _("Directive_id"));
ossim_valid($tag,            OSS_HEX, OSS_NULLABLE,                                       'illegal:' . _("Label id"));
ossim_valid($num_events,     OSS_DIGIT, OSS_NULLABLE,                                     'illegal:' . _("Num_events"));
ossim_valid($num_events_op,  OSS_ALPHA, OSS_NULLABLE,                                     'illegal:' . _("Num_events_op"));
ossim_valid($no_resolv,      OSS_DIGIT, OSS_NULLABLE,                                     'illegal:' . _("No_resolv"));
ossim_valid($timestamp,      OSS_DIGIT, OSS_SCORE, OSS_NULLABLE,                          'illegal:' . _("Timestamp"));

if (ossim_error())
{
    $response['sEcho']                = $sec;
    $response['iTotalRecords']        = 0;
    $response['iTotalDisplayRecords'] = 0;
    $response['aaData']               = '';

    $error = ossim_get_error();
    ossim_clean_error();
    Av_exception::write_log(Av_exception::USER_ERROR, $error);

    echo json_encode($response);
    exit;
}



$db     = new ossim_db(TRUE);
$conn   = $db->connect();

//Getting Alarm groups
$db_groups      = Alarm_groups::get_dbgroups($conn);
$is_group_taken = ($db_groups[$group_id]['owner'] == Session::get_session_user());


$geoloc = new Geolocation("/usr/share/geoip/GeoLiteCity.dat");

if ($timestamp != "")
{
    $from_date = ($timestamp!="") ? $timestamp." 00:00:00" : null;
    $to_date   = ($timestamp!="") ? $timestamp : null;
}


$entities = array();

if (Session::is_pro())
{
    $_entities = Acl::get_entities($conn);

    foreach ($_entities[0] as $e_id => $e)
    {
        $entities[$e_id] = Util::utf8_encode2($e['name']);
    }
}

$entity_types = Session::get_entity_types($conn, TRUE);
$name         = ($name == _('Unknown Directive')) ? '' : $name;

$criteria = array(
    "sensor"        => $sensor_query,
    "src_ip"        => $src_ip,
    "dst_ip"        => $dst_ip,
    "asset_group"   => $asset_group,
    "hide_closed"   => $hide_closed,
    "order"         => "",
    "inf"           => $inf,
    "sup"           => $sup,
    "from_date"     => $from_date,
    "to_date"       => $to_date,
    "query"         => $name,
    "group_id"      => "",
    "directive_id"  => $directive_id,
    "tag"           => $tag,
    "num_events"    => $num_events,
    "num_events_op" => $num_events_op
);


list($list, $total) = Alarm_groups::get_alarms($conn, $criteria, TRUE);


$tz = Util::get_timezone();


$results = array();

foreach ($list as $s_alarm)
{
    $res = array();

    $s_id         = $s_alarm->get_plugin_id();
    $s_sid        = $s_alarm->get_plugin_sid();
    $s_backlog_id = $s_alarm->get_backlog_id();
    $s_event_id   = $s_alarm->get_event_id();
    $ctx          = $s_alarm->get_ctx();
    $s_src_ip     = $s_alarm->get_src_ip();
    $s_src_port   = $s_alarm->get_src_port();
    $s_dst_port   = $s_alarm->get_dst_port();
    $s_dst_ip     = $s_alarm->get_dst_ip();
    $s_status     = $s_alarm->get_status();
    $ctxs         = $s_alarm->get_sensors();
    $event_info   = $s_alarm->get_event_info();
    $src_host     = Asset_host::get_object($conn, $event_info["src_host"]);
    $dst_host     = Asset_host::get_object($conn, $event_info["dst_host"]);
    $s_net_id     = $event_info["src_net"];
    $d_net_id     = $event_info["dst_net"];

    $s_asset_src  = $s_alarm->get_asset_src();
    $s_asset_dst  = $s_alarm->get_asset_dst();

    // Src
    if ($no_resolv || !$src_host)
    {
        $s_src_name = $s_src_ip;
        $ctx_src    = $ctx;

    }
    elseif ($src_host)
    {
        $s_src_name = $src_host->get_name();
        $ctx_src    = $src_host->get_ctx();
    }
    // Src icon and bold
    $src_output  = Asset_host::get_extended_name($conn, $geoloc, $s_src_ip, $ctx_src, $event_info["src_host"], $event_info["src_net"]);
    $homelan_src = $src_output['is_internal'];
    $src_img     = $src_output['html_icon'];

    // Dst
    if ($no_resolv || !$dst_host)
    {
        $s_dst_name = $s_dst_ip;
        $ctx_dst    = $ctx;
    }
    elseif ($dst_host)
    {
        $s_dst_name = $dst_host->get_name();
        $ctx_dst    = $dst_host->get_ctx();
    }
    // Dst icon and bold
    $dst_output  = Asset_host::get_extended_name($conn, $geoloc, $s_dst_ip, $ctx_dst, $event_info["dst_host"], $event_info["dst_net"]);
    $homelan_dst = $dst_output['is_internal'];
    $dst_img     = $dst_output['html_icon']; // Clean icon hover tiptip

    $s_src_link = Menu::get_menu_url("../forensics/base_stat_ipaddr.php?clear_allcriteria=1&ip=$s_src_ip", 'analysis', 'security_events', 'security_events');

    $s_dst_link =  Menu::get_menu_url("../forensics/base_stat_ipaddr.php?clear_allcriteria=1&ip=$s_dst_ip", 'analysis', 'security_events', 'security_events');

    $s_src_port = ($s_src_port != 0) ? ":".Port::port2service($conn, $s_src_port) : "";
    $s_dst_port = ($s_dst_port != 0) ? ":".Port::port2service($conn, $s_dst_port) : "";

    $c_src_homelan = ($homelan_src) ? 'bold alarm_netlookup' : '';

    $source_link = $src_img . " <a href='$s_src_link' class='$c_src_homelan' data-title='$s_src_ip-$ctx_src' title='$s_src_ip'>".$s_src_name.$s_src_port."</a>";

    $source_balloon  = "<div id='".$s_src_ip.";".$s_src_name.";".$event_info["src_host"]."' ctx='$ctx' id2='".$s_src_ip.";".$s_dst_ip."' class='HostReportMenu'>";
    $source_balloon .= $source_link;
    $source_balloon .= "</div>";

    $c_dst_homelan = ($homelan_dst) ? 'bold alarm_netlookup' : '';

    $dest_link = $dst_img . " <a href='$s_dst_link' class='$c_dst_homelan' data-title='$s_dst_ip-$ctx_dst' title='$s_dst_ip'>".$s_dst_name.$s_dst_port."</a>";

    $dest_balloon  = "<div id='".$s_dst_ip.";".$s_dst_name.";".$event_info["dst_host"]."' ctx='$ctx' id2='".$s_dst_ip.";".$s_src_ip."' class='HostReportMenu'>";
    $dest_balloon .= $dest_link;
    $dest_balloon .= "</div>";



    $s_sid_name = "";
    if ($s_plugin_sid_list = Plugin_sid::get_list($conn, "WHERE plugin_id = $s_id AND sid = $s_sid"))
    {
        $s_sid_name     = $s_plugin_sid_list[0]->get_name();
        $s_sid_priority = $s_plugin_sid_list[0]->get_priority();
    }
    else
    {
        $s_sid_name = "Unknown (id=$s_id sid=$s_sid)";
        $s_sid_priority = "N/A";
    }

    $s_last        = Util::timestamp2date($s_alarm->get_last());
    $timestamp_utc = Util::get_utc_unixtime($s_last);
    $s_last        = gmdate("Y-m-d H:i:s",$timestamp_utc+(3600*$tz));

    $s_event_count = Alarm::get_total_events($conn, $s_backlog_id);
    $aux_date      = Util::timestamp2date($s_alarm->get_timestamp());
    $timestamp_utc = Util::get_utc_unixtime($s_alarm->get_timestamp());
    $s_date        = gmdate("Y-m-d H:i:s",$timestamp_utc+(3600*$tz));


    if ($s_backlog_id && $s_id==1505 && $s_event_count>0)
    {
        $aux_date      = Util::timestamp2date($s_alarm->get_since());
        $timestamp_utc = Util::get_utc_unixtime($aux_date);
        $s_since       = gmdate("Y-m-d H:i:s",$timestamp_utc+(3600*$tz));
    }
    else
    {
        $s_since       = $s_date;
    }

    $s_risk = $s_alarm->get_risk();


    $s_alarm_link = "alarm_detail.php?backlog=" . $s_backlog_id;

    /* Alarm name */
    $s_alarm_name = preg_replace('/directive_event:\s/', '', $s_sid_name);
    $s_alarm_name = Util::translate_alarm($conn, $s_alarm_name, $s_alarm);


    $event_ocurrences = Alarm::get_total_events($conn, $s_backlog_id);

    $risk_text = Util::get_risk_rext($s_risk);
    $risk_field = '<td><span class="risk-bar '.$risk_text.'">' . _($risk_text) . '</span></td>';


    //OTX
    $otx_icon  = $s_alarm->get_otx_icon();
    if ($otx_icon)
    {
        $otx = "<img src='$otx_icon' class='otx_icon'/>";
    }
    else
    {
        $otx = _('N/A');
    }


    //Create a new ticket
    if (Session::menu_perms("analysis-menu", "IncidentsOpen") && $is_group_taken == TRUE)
    {
         // clean ports name
        $g_src_port   = preg_replace("/^:/", "", $s_src_port);
        $g_dst_port   = preg_replace("/^:/", "", $s_dst_port);

        // clean ticket title
        $g_alarm_name = str_replace("&mdash;", "-", $s_alarm_name);

        $incident_link  = '<a class="greybox2" title="'._("New Ticket").'" href="../incidents/newincident.php?ref=Alarm&title=' . urlencode($g_alarm_name) . '&priority='.$s_risk.'&src_ips='.$s_src_ip.'&event_start='.$s_since.'&event_end='.$s_date.'&src_ports='.$g_src_port.'&dst_ips='.$s_dst_ip.'&dst_ports='.$g_dst_port.'"><img src="../pixmaps/new_ticket.png" class="newticket" alt="'._("New Ticket").'" border="0"/></a>';
    }
    else
    {
        $incident_link  = "<img src='../pixmaps/new_ticket.png' class='newticket disabled' alt='"._("Take the group first in order to perform this action")."' title='"._("Take the group first in order to perform this action")."' border='0'/>";
    }


    if ($s_status == 'open')
    {
        $st_name  = _('Open');
        $st_title = _('Click here to close the alarm');
        $st_class = 'a_open';
    }
    else
    {
        $st_name  = _('Closed');
        $st_title = _('Click here to open the alarm');
        $st_class = 'a_closed';
    }


    if ($s_alarm->get_removable())
    {
        if($is_group_taken == TRUE)
        {
            $st_class .= ' a_status av_l_main';
        }
        else
        {
            $st_class .= ' av_l_disabled';

            if ($s_status == 'open')
            {
                $st_title = _('Open, take this group first in order to close it');
            }
            else
            {
                $st_title = _('Close, take this group first in order to open it');
            }
        }
    }
    else
    {
        $st_class .= ' av_l_disabled';
        $st_title = _("This alarm is still being correlated and therefore it can not be modified");
    }

    $status_link = "<a href='javascript:;' class='tip $st_class' title=\"$st_title\">$st_name</a>";


    /* Expand button */
    if ($s_backlog_id && $s_id==1505 && $event_ocurrences > 0 && $s_alarm->get_removable())
    {
        $expand_button = "<img class='alarm_expand' src='../pixmaps/plus-small.png'/>";
    }
    else
    {
        $expand_button = "<img src='../pixmaps/plus-small-gray.png'>";
    }


    if ($s_alarm->get_removable())
    {
        $ago       = get_alarm_life($s_since, $s_last);
        $acid_link = Util::get_acid_events_link($s_since, $s_date, "time_a");
        $duration  = "<a href=\"$acid_link\" class='stop'><span style='color:black' class='tip' title='"._("First").": $s_since ". Util::timezone($tz) ."<br>". _("Last") .":  $s_last " . Util::timezone($tz) . "'>$ago</span></a>";

    }
    else
    {
        $now       = gmdate("Y-m-d H:i:s",gmdate("U")+(3600*$tz));
        $ago       = get_alarm_life($s_since, $now);
        $acid_link = Util::get_acid_events_link($s_since, $now, "time_a");
        $duration  = "<a href=\"$acid_link\" class='stop'>
                <span style='color:black' class='tip' title='"._("First").": $s_since ".Util::timezone($tz)."'>".$ago."</span>
              </a>
              <img src='/ossim/alarm/style/img/correlating.gif' class='img_cor tip' title='"._("This alarm is still being correlated and therefore it can not be modified")."'/>";

    }

    $tooltip = "
            <div style='display: none;'>
                <table class='t_white'>
                    <tr>
                        <td>"._("SRC Asset").":</td>
                        <td>$s_asset_src</td>
                    </tr>

                    <tr>
                        <td>"._("DST Asset").":</td>
                        <td>$s_asset_dst</td>
                    </tr>

                    <tr>
                        <td>"._("Priority").":</td>
                        <td>$s_sid_priority</td>
                    </tr>
                </table>
            </div>";


    $res[] = $expand_button;
    $res[] = "<a href='$s_alarm_link' class='aname greybox2'>$s_alarm_name</a>$tooltip";
    $res[] = Util::number_format_locale($event_ocurrences,0);
    $res[] = $risk_field;
    $res[] = $duration;
    $res[] = $otx;
    $res[] = $source_balloon;
    $res[] = $dest_balloon;
    $res[] = $status_link;
    $res[] = $incident_link;

    $res['DT_RowId']    = $s_backlog_id;
    $res['DT_RowClass'] = 'alarm_dt';


    $results[] = $res;
}

// datatables response json
$response = array();
$response['sEcho']                = $sec;
$response['iTotalRecords']        = $total;
$response['iTotalDisplayRecords'] = $total;
$response['aaData']               = $results;
$response['iDisplayStart']        = 0;

echo json_encode($response);



$db->close();
$geoloc->close();


