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


set_time_limit(0);

require_once 'av_init.php';
require_once '../alarm_common.php';

Session::logcheck("analysis-menu", "ControlPanelAlarms");


//Might be by post or by get. 
if ($_SESSION["_alarm_keep_pagination"])
{
    $_num    = GET('num_alarms_page');
    $_inf    = GET('inf');
    $_order  = GET('order');
    $_torder = GET('torder');

    $_SESSION["_alarm_keep_pagination"] = FALSE;
}
else
{
    $_SESSION["per_page"] = $_num = POST('iDisplayLength')
        ? POST('iDisplayLength') : (isset($_SESSION["per_page"]) ? $_SESSION["per_page"] : 20);
    $_inf    = POST('iDisplayStart');
    $_order  = POST('iSortCol_0');
    $_torder = POST('sSortDir_0');
}

$num_alarms_page = ($_num != '') ? intval($_num) : 20;
$inf             = ($_inf != '') ? intval($_inf) : 0;
$order           = intval($_order);
$torder          = ($_torder == '' || preg_match("/desc/",$_torder)) ? "desc" : "asc";

/* Retrieving specific parameters --> Always get */
$delete          = GET('delete');
$close           = GET('close');
$open            = GET('open');
$delete_day      = GET('delete_day');
$src_ip          = GET('src_ip');
$dst_ip          = GET('dst_ip');
$asset_group     = GET('asset_group');
$hide_closed     = GET('hide_closed');
$no_resolv       = intval(GET('no_resolv'));

$host_id         = GET('host_id');
$net_id          = GET('net_id');
$ctx             = GET('ctx');

//OTX
$otx_activity    = intval(GET('otx_activity'));
$pulse_id        = GET('pulse_id');

$query           = (GET('query') != "") ? GET('query') : "";
$directive_id    = GET('directive_id');
$intent          = intval(GET('intent'));
$sensor_query    = GET('sensor_query');
$tag             = GET('tag');
$num_events      = GET('num_events');
$num_events_op   = GET('num_events_op');
$max_risk        = GET('max_risk') != "" ? GET('max_risk') : 2;
$min_risk        = GET('min_risk') != "" ? GET('min_risk') : 0;

$date_from       = GET('date_from');
$date_to         = GET('date_to');
$ds_id           = GET('ds_id');
$ds_name         = GET('ds_name');
$beep            = intval(GET('beep'));
$sec             = intval(POST('sEcho'));


ossim_valid($order,           OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Order"));
ossim_valid($torder,          OSS_ALPHA, OSS_NULLABLE,                                      'illegal:' . _("Order Direction"));
ossim_valid($delete,          OSS_HEX, OSS_NULLABLE,                                        'illegal:' . _("Delete"));
ossim_valid($close,           OSS_HEX, OSS_NULLABLE,                                        'illegal:' . _("Close"));
ossim_valid($open,            OSS_HEX, OSS_NULLABLE,                                        'illegal:' . _("Open"));
ossim_valid($delete_day,      OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE,                 'illegal:' . _("Delete_day"));
ossim_valid($query,           OSS_ALPHA, OSS_PUNC_EXT, OSS_SPACE, OSS_NULLABLE,             'illegal:' . _("Query"));
ossim_valid($directive_id,    OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Directive_id"));
ossim_valid($intent,          OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Intent"));
ossim_valid($src_ip,          OSS_IP_ADDRCIDR_0, OSS_NULLABLE,                              'illegal:' . _("Src_ip"));
ossim_valid($dst_ip,          OSS_IP_ADDRCIDR_0, OSS_NULLABLE,                              'illegal:' . _("Dst_ip"));
ossim_valid($asset_group,     OSS_HEX, OSS_NULLABLE,                                        'illegal:' . _("Asset Group"));
ossim_valid($inf,             OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Inf"));
ossim_valid($hide_closed,     OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Hide_closed"));
ossim_valid($date_from,       OSS_DATETIME_DATE, OSS_NULLABLE,                              'illegal:' . _("From date"));
ossim_valid($date_to,         OSS_DATETIME_DATE, OSS_NULLABLE,                              'illegal:' . _("To date"));
ossim_valid($num_alarms_page, OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Field number of alarms per page"));
ossim_valid($sensor_query,    OSS_HEX, OSS_NULLABLE,                                        'illegal:' . _("Sensor_query"));
ossim_valid($tag,             OSS_HEX, OSS_NULLABLE,                                        'illegal:' . _("Tag"));
ossim_valid($num_events,      OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Num_events"));
ossim_valid($num_events_op,   OSS_ALPHA, OSS_NULLABLE,                                      'illegal:' . _("Num_events_op"));
ossim_valid($max_risk,        OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Max_risk"));
ossim_valid($min_risk,        OSS_ALPHA, OSS_NULLABLE,                                      'illegal:' . _("Min_risk"));
ossim_valid($datasource,      OSS_DIGIT, "-", OSS_NULLABLE,                                 'illegal:' . _("Datasource"));
ossim_valid($beep,            OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Beep"));
ossim_valid($host_id,         OSS_HEX, OSS_NULLABLE,                                        'illegal:' . _("Host ID"));
ossim_valid($net_id,          OSS_HEX, OSS_NULLABLE,                                        'illegal:' . _("Net ID"));
ossim_valid($ctx,             OSS_HEX, OSS_NULLABLE,                                        'illegal:' . _("CTX"));
ossim_valid($otx_activity,    OSS_BINARY, OSS_NULLABLE,                                     'illegal:' . _("OTX Activity"));
ossim_valid($pulse_id,        OSS_HEX, OSS_NULLABLE,                                        'illegal:' . _("Pulse ID"));
ossim_valid($sec,             OSS_DIGIT, OSS_NULLABLE,                                      'illegal: sEcho');

if (ossim_error())
{ 
    die(ossim_error());
}


/* connect to db */
$db   = new ossim_db(TRUE);
$conn = $db->connect();

// Timezone correction
$tz  = Util::get_timezone();

//Geoloc library
$geoloc = new Geolocation("/usr/share/geoip/GeoLiteCity.dat");


// Pagination
if (empty($inf)) 
{
    $inf = 0;
}

$sup = $inf + $num_alarms_page;


$parameters['query']                  = "query="          .urlencode($query);
$parameters['directive_id']           = "directive_id="   .$directive_id;
$parameters['intent']                 = "intent="         .$intent;
$parameters['inf']                    = "inf="            .$inf;
$parameters['num_alarms_page']        = "num_alarms_page=".$num_alarms_page;
$parameters['order']                  = "order="          .$order;
$parameters['torder']                 = "torder="         .$torder;
$parameters['no_resolv']              = "no_resolv="      .$no_resolv;
$parameters['hide_closed']            = "hide_closed="    .$hide_closed;
$parameters['order']                  = "order="          .$order;
$parameters['src_ip']                 = "src_ip="         .$src_ip;
$parameters['dst_ip']                 = "dst_ip="         .$dst_ip;
$parameters['asset_group']            = "asset_group="    .$asset_group;
$parameters['date_from']              = "date_from="      .urlencode($date_from);
$parameters['date_to']                = "date_to="        .urlencode($date_to);
$parameters['sensor_query']           = "sensor_query="   .$sensor_query;
$parameters['tag']                    = "tag="            .$tag;
$parameters['num_events']             = "num_events="     .$num_events;
$parameters['num_events_op']          = "num_events_op="  .$num_events_op;
$parameters['min_risk']               = "min_risk="       .$min_risk;
$parameters['max_risk']               = "max_risk="       .$max_risk;
$parameters['ds_id']                  = "ds_id="          .$ds_id;
$parameters['ds_name']                = "ds_name="        .urlencode($ds_name);
//$parameters['bypassexpirationupdate'] = "bypassexpirationupdate=1";
$parameters['beep']                   = "beep="           .$beep;
$parameters['host_id']                = "host_id="        .$host_id;
$parameters['net_id']                 = "net_id="         .$net_id;
$parameters['ctx']                    = "ctx="            .$ctx;
$parameters['otx_activity']           = "otx_activity="   .$otx_activity;
$parameters['pulse_id']               = "pulse_id="       .$pulse_id;


if (!empty($_SESSION["_delete_msg"])) 
{       
    echo ossim_error($_SESSION["_delete_msg"], AV_WARNING);
    $_SESSION["_delete_msg"] = "";
}

$_SESSION["_no_resolv"]      = $no_resolv;
$_SESSION["_alarm_criteria"] = implode("&", $parameters);


// Order by
switch ($order) 
{
    case '9':  
        $order = " a.dst_ip $torder, a.timestamp $torder";
        break;
    
    case '8':  
        $order = " a.src_ip $torder, a.timestamp $torder";
        break;
    
    case '6':  
        $order = " a.risk $torder, a.timestamp $torder";
        break;

    case '5':  
        $order = " ta.subcategory $torder, a.timestamp $torder";
        break;
    
    case '4':  
        $order = " ki.name $torder, ca.name $torder, a.timestamp $torder";
        break;
    
    case '2':  
        $order = " a.status $torder, a.timestamp $torder";
        break;
    
    case '1':  
        $order = " a.timestamp $torder";               
        break;
    
    default:   
        $order = " a.timestamp $torder";
        break;
}

if ((!empty($src_ip)) && (!empty($dst_ip))) 
{
    $where = "WHERE inet_ntoa(src_ip) = '$src_ip' OR inet_ntoa(dst_ip) = '$dst_ip'";
} 
elseif (!empty($src_ip)) 
{
    $where = "WHERE inet_ntoa(src_ip) = '$src_ip'";
} 
elseif (!empty($dst_ip)) 
{
    $where = "WHERE inet_ntoa(dst_ip) = '$dst_ip'";
} 
else 
{
    $where = '';
}


//Datasource filter
$plugin_id  = "";
$plugin_sid = "";

if (!empty($ds_id))
{
    $ds = explode("-", $ds_id);
    $plugin_id  = $ds[0];
    $plugin_sid = $ds[1];
}

// Improved efficiency get_list
$criteria = array(
    "src_ip"        => $src_ip,
    "dst_ip"        => $dst_ip,
    "hide_closed"   => $hide_closed,
    "order"         => "ORDER BY $order",
    "inf"           => $inf,
    "sup"           => $sup,
    "date_from"     => $date_from,
    "date_to"       => $date_to,
    "query"         => $query,
    "directive_id"  => $directive_id,
    "intent"        => $intent,
    "sensor"        => $sensor_query,
    "tag"           => $tag,
    "num_events"    => $num_events,
    "num_events_op" => $num_events_op,
    "min_risk"      => $min_risk,
    "max_risk"      => $max_risk,
    "plugin_id"     => $plugin_id,
    "plugin_sid"    => $plugin_sid,
    "ctx"           => "",
    "host"          => $host_id,
    "net"           => $net_id,
    "host_group"    => $asset_group,
    "otx_activity"  => $otx_activity,
    "pulse_id"      => $pulse_id
);

list($alarm_list, $count) = Alarm::get_list($conn, $criteria, true);


/*
* Pagination
*/

$total = ($inf>0 && intval($_SESSION["_alarm_count"])>0) ? $_SESSION["_alarm_count"] : $count;


$results = array(); 

$sound      = 0;
$cont_tr    = 0;
$time_start = time();
$show_label = FALSE;
if ($count > 0) 
{
    foreach($alarm_list as $alarm) 
    {
        /* hide closed alarmas */
        if (($alarm->get_status() == "closed") and ($hide_closed == 1))
        { 
            continue;
        }
        
        $res = array();

            
        $ctx        = $alarm->get_ctx();  //THIS IS THE ENGINE!!!
        $id         = $alarm->get_plugin_id();
        $sid        = $alarm->get_plugin_sid();
        $backlog_id = $alarm->get_backlog_id();
        $event_id   = $alarm->get_event_id();
        $tags       = $alarm->get_tags();
        $csimilar   = $alarm->get_csimilar();
        $similar    = $alarm->get_similar();
        $sid_name   = $alarm->get_sid_name(); // Plugin_sid table just joined
                
        $src_ip     = $alarm->get_src_ip();
        $dst_ip     = $alarm->get_dst_ip();
        $src_port   = $alarm->get_src_port();
        $dst_port   = $alarm->get_dst_port();

        $event_info = $alarm->get_event_info();
        $src_host   = Asset_host::get_object($conn, $event_info["src_host"]);
        $dst_host   = Asset_host::get_object($conn, $event_info["dst_host"]);

        $src_net_id = $event_info["src_net"];
        $dst_net_id = $event_info["dst_net"];

        $src_port   = ($src_port != 0) ? ":".Port::port2service($conn, $src_port) : "";
        $dst_port   = ($dst_port != 0) ? ":".Port::port2service($conn, $dst_port) : "";
        $ctxs       = $alarm->get_sensors(); // Incongruent code
        $risk       = $alarm->get_risk();

        if ($plugin_id!="" && $plugin_sid!="")
        { 
            $csimilar=0;  //Change similar when we search by data source
        }
        
        // Stats
        $_stats = $alarm->get_stats();

        $event_count_label = "";
        if ($backlog_id) 
        {
            $event_count       = (!empty($_stats)) ? $_stats["events"] : Alarm::get_total_events($conn, $backlog_id, true);
            $event_count_label = $event_count." "._("events");
        }

        $timestamp_utc = Util::get_utc_unixtime(Util::timestamp2date($alarm->get_timestamp())); // $alarm->get_last()
        $alarm_date    = gmdate("Y-m-d H:i:s",$timestamp_utc+(3600*$tz));

        $date          = Util::timestamp2date($alarm->get_timestamp());
        $timestamp_utc = Util::get_utc_unixtime($date);

        $date          = gmdate("Y-m-d H:i:s",$timestamp_utc+(3600*$tz));

        if ($backlog_id && $id==1505 && $event_count > 0) 
        {
            $since = Util::timestamp2date($alarm->get_since());
            $since = gmdate("Y-m-d H:i:s",Util::get_utc_unixtime($since)+(3600*$tz));            
        }
        else
        { 
            $since = $date;
        }
        
        /* Alarm Beep */
        $beep_on = FALSE;
        
        if ($beep && !$beep_on)
        {
            $last_refresh = $_SESSION['_alarm_last_refresh_time'];
            
            if ($timestamp_utc >= $last_refresh)
            {
                $beep_on = TRUE;
                
                $_SESSION['_alarm_last_refresh_time'] = gmdate("U");
            }

        }
          
        /* show alarms by days */
        $date_slices              = preg_split('/\s/', $date);
        list($year, $month, $day) = preg_split('/\-/', $date_slices[0]);
        $date_unformated          = $year.$month.$day;
        $date_formatted           = Util::htmlentities(strftime("%A %d-%b-%Y", mktime(0, 0, 0, $month, $day, $year)));

        // INPUT
        $chk = '';
        if ($alarm->get_removable() && $_SESSION['_SELECTED_ALARMS'][$backlog_id])
        {
            $chk = ' checked="checked" ';
        }
        
        $input = '<input style="border:none" type="checkbox" '. $chk .' name="check_'.$backlog_id.'_'.$event_id.'" id="check_'.$backlog_id.'" class="alarm_check stop" datecheck="'.$date_unformated.'" value="1" '.($alarm->get_removable() ? "" : " disabled='disabled'").' data="'.(empty($_stats) ? 'event' : 'alarm').'"/>';

        if (!$sound && $beep_on)
        {
            $sound = 1;
            if (strpos($_SERVER['HTTP_USER_AGENT'], "Firefox") !== false)
            {
                $input .= '<object type="video/x-ms-wmv" width="0" height="0"><param name="filename" value ="../sounds/alarm.wav" /></object>';
            }
            else
            {
                $input .= '<audio controls="controls" style="display:none" autoplay="autoplay"><source src="../sounds/alarm.wav" type="audio/mpeg" /><bgsound style="display:none" src="../sounds/alarm.wav" /></audio>';
            }
        }
        
        $res[] = $input;
               
        if ($alarm->get_removable()) {
            $res[] = $alarm_date;
            $res[] = $alarm->get_status();
        }
        else
        {
            $now = gmdate("Y-m-d H:i:s",gmdate("U")+(3600*$tz));
            $res[] = get_alarm_life($since, $now);
            $res[] = "<img align='absmiddle' src='/ossim/alarm/style/img/correlating.gif' class='img_cor tip' title='"._("This alarm is still being correlated and therefore it can not be modified")."'>";
        }
        
        // TAGS
        $tgs = "<div class='a_label_container'></div>";
        $tgs_array = array();

        if (count($tags) > 0) 
        {
            foreach ($tags as $id_tag => $tag)
            {
                $tgs_array[] = array(
                    'id' => $tag->get_id(),
                    'class' => $tag->get_class(),
                    'name' => $tag->get_name()
                );
            }
            
            $show_label = TRUE;
        }
        
        $res[]       = $tgs;
        $res['tags'] = $tgs_array;
        
        // kingdom, category and subcategory
        $a_taxonomy = $alarm->get_taxonomy();
        if ($a_taxonomy['id'])
        {
            list($alarm_ik, $alarm_sc) = Alarm::get_alarm_name($a_taxonomy);
            $res[]                     = $alarm_ik;
            $alarm_tr                  = Util::translate_alarm($conn, $alarm_sc, $alarm, "array");
            $res[]                     = $alarm_tr['name'];
        }
        else
        {
            $alarm_name = Util::translate_alarm($conn, $sid_name, $alarm, "array");
            $res[]      = $alarm_name['name'];
            $res[]      = '';
        }

        // risk
        $risk_text = Util::get_risk_rext($risk);
        $res[] = "<span class='risk-bar $risk_text'>"._($risk_text)."</span>";
        
        // OTX
        $otx_icon  = $alarm->get_otx_icon();
        if ($otx_icon)
        {
            $alarm_otx = "<img src='$otx_icon' class='otx_icon'/>";
        }
        else
        {
            $alarm_otx = _('N/A');
        }
        
        $res[] = $alarm_otx;
        
        
        // src and dst
        $src_link         = $refresh_url_nopage."&src_ip=".$src_ip;
        $dst_link         = $refresh_url_nopage."&dst_ip=".$dst_ip;
        $default_ctx      = Session::get_default_ctx();

        // Src
        if ($no_resolv || !$src_host) 
        {
            $src_name   = $src_ip;
            $src_desc   = "";
            $ctx_src    = $event_info["agent_ctx"];
        } 
        elseif ($src_host) 
        {
            $src_desc   = ($src_host->get_descr()!="") ? ": ".$src_host->get_descr() : "";
            $src_name   = $src_host->get_name();
            $ctx_src    = $src_host->get_ctx();
            $src_link   = $refresh_url_nopage."&host_id=".$src_host->get_id();
        }
        
        // Src icon and bold
        $src_output  = Asset_host::get_extended_name($conn, $geoloc, $src_ip, $ctx_src, $event_info["src_host"], $src_net_id);
        $homelan_src = $src_output['is_internal'];
        $src_img     = preg_replace("/scriptinfo/", "", $src_output['html_icon']); // Clean icon hover tiptip

        // Dst
        if ($no_resolv || !$dst_host) 
        {
            $dst_name   = $dst_ip;
            $dst_desc   = "";
            $ctx_dst    = $event_info["agent_ctx"];
        } 
        elseif ($dst_host) 
        {
            $dst_desc   = ($dst_host->get_descr()!="") ? ": ".$dst_host->get_descr() : "";
            $dst_name   = $dst_host->get_name();
            $ctx_dst    = $dst_host->get_ctx();
            $dst_link   = $refresh_url_nopage."&host_id=".$dst_host->get_id();
        }
        
        // Dst icon and bold
        $dst_output  = Asset_host::get_extended_name($conn, $geoloc, $dst_ip, $ctx_dst, $event_info["dst_host"], $dst_net_id);
        $homelan_dst = $dst_output['is_internal'];
        $dst_img     = preg_replace("/scriptinfo/", "", $dst_output['html_icon']); // Clean icon hover tiptip
        
        //host report menu:
        $src_hrm          = "$src_ip;$src_name;".$event_info["src_host"];
        $dst_hrm          = "$dst_ip;$dst_name;".$event_info["dst_host"];
        
  
        // SRC
        $res[] = "<div class='HostReportMenu' id='$src_hrm' ctx='$ctx_src'>$src_img" . (($homelan_src) ? " <strong>$src_name$src_port</strong>" : " $src_name$src_port") . "</div>";

        // DST
        $res[] = "<div class='HostReportMenu fleft' id='$dst_hrm' ctx='$ctx_dst'>$dst_img" . (($homelan_dst) ? " <strong>$dst_name$dst_port</strong>" : " $dst_name$dst_port") . "</div>";

        
        //Detail
        $res[] = "<img class='go_details' src='/ossim/pixmaps/show_details.png'>";
        
        
        
        $res['DT_RowId']  = $alarm->get_backlog_id();
        
        $results[] = $res;
    }
}

// datatables response json
$response= array();
$response['sEcho']                = $sec;
$response['iTotalRecords']        = $total;
$response['iTotalDisplayRecords'] = $total;
$response['aaData']               = $results;
$response['iDisplayStart']        = $inf;

$response['show_label']           = $show_label;

echo json_encode($response);

$db->close();
$geoloc->close();

unset($_SESSION['_SELECTED_ALARMS']);
