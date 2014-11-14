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
require_once 'alarm_common.php';

Session::logcheck("analysis-menu", "ControlPanelAlarms");

$geoloc = new Geolocation("/usr/share/geoip/GeoLiteCity.dat");

/* connect to db */
$db   = new ossim_db(TRUE);
$conn = $db->connect();

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
    $_num    = POST('iDisplayLength');
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
$hide_closed     = GET('hide_closed');
$no_resolv       = intval(GET('no_resolv'));

$host_id         = GET('host_id');
$net_id          = GET('net_id');
$ctx             = GET('ctx');

$autorefresh     = "";
$refresh_time    = "";

if (isset($_GET['search']))
{
    unset($_SESSION['_alarm_autorefresh']);
    if (isset($_GET['autorefresh']))
    {
        $autorefresh  = (GET('autorefresh') != '1') ? 0 : 1;
        $refresh_time = GET('refresh_time');
        $_SESSION['_alarm_autorefresh'] = GET('refresh_time');
    }
}
else
{
    if ($_SESSION['_alarm_autorefresh'] != '')
    {
        $autorefresh  = 1;
        $refresh_time = $_SESSION['_alarm_autorefresh'];
    }
}


$query           = (GET('query') != "") ? GET('query') : "";
$directive_id    = GET('directive_id');
$intent          = intval(GET('intent'));
$sensor_query    = GET('sensor_query');
$tag             = GET('tag');
$num_events      = GET('num_events');
$num_events_op   = GET('num_events_op');
$date_from       = GET('date_from');
$date_to         = GET('date_to');
$ds_id           = GET('ds_id');
$ds_name         = GET('ds_name');
$beep            = intval(GET('beep'));
$sec             = POST('sEcho');

//$tags            = Tags::get_list($conn);
$tags_html       = Tags::get_list_html($conn);

if (Session::is_pro() && Session::show_entities()) 
{
    list($entities, $_children, $_num_ent) = Acl::get_entities($conn, '', '', true, false);
}

ossim_valid($order,           OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Order"));
ossim_valid($torder,          OSS_ALPHA, OSS_NULLABLE,                                      'illegal:' . _("Order Direction"));
ossim_valid($delete,          OSS_HEX, OSS_NULLABLE,                                        'illegal:' . _("Delete"));
ossim_valid($close,           OSS_HEX, OSS_NULLABLE,                                        'illegal:' . _("Close"));
ossim_valid($open,            OSS_HEX, OSS_NULLABLE,                                        'illegal:' . _("Open"));
ossim_valid($delete_day,      OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE,                 'illegal:' . _("Delete_day"));
ossim_valid($query,           OSS_ALPHA, OSS_PUNC_EXT, OSS_SPACE, OSS_NULLABLE,             'illegal:' . _("Query"));
ossim_valid($autorefresh,     OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Autorefresh"));
ossim_valid($refresh_time,    OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Refresh_time"));
ossim_valid($directive_id,    OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Directive_id"));
ossim_valid($intent,          OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Intent"));
ossim_valid($src_ip,          OSS_IP_ADDRCIDR_0, OSS_NULLABLE,                              'illegal:' . _("Src_ip"));
ossim_valid($dst_ip,          OSS_IP_ADDRCIDR_0, OSS_NULLABLE,                              'illegal:' . _("Dst_ip"));
ossim_valid($inf,             OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Inf"));
ossim_valid($hide_closed,     OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Hide_closed"));
ossim_valid($date_from,       OSS_DATETIME_DATE, OSS_NULLABLE,                              'illegal:' . _("From date"));
ossim_valid($date_to,         OSS_DATETIME_DATE, OSS_NULLABLE,                              'illegal:' . _("To date"));
ossim_valid($num_alarms_page, OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Field number of alarms per page"));
ossim_valid($sensor_query,    OSS_HEX, OSS_NULLABLE,                                        'illegal:' . _("Sensor_query"));
ossim_valid($tag,             OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Tag"));
ossim_valid($num_events,      OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Num_events"));
ossim_valid($num_events_op,   OSS_ALPHA, OSS_NULLABLE,                                      'illegal:' . _("Num_events_op"));
ossim_valid($ds_id,           OSS_DIGIT, "-", OSS_NULLABLE,                                 'illegal:' . _("Datasource"));
ossim_valid($beep,            OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Beep"));
ossim_valid($host_id,         OSS_HEX, OSS_NULLABLE,                                        'illegal:' . _("Host ID"));
ossim_valid($net_id,          OSS_HEX, OSS_NULLABLE,                                        'illegal:' . _("Net ID"));
ossim_valid($ctx,             OSS_HEX, OSS_NULLABLE,                                        'illegal:' . _("CTX"));
ossim_valid($sec,             OSS_DIGIT, OSS_NULLABLE,                                      'illegal: sEcho');

if (ossim_error())
{ 
    die(ossim_error());
}


// Pagination
if (empty($inf)) 
{
    $inf = 0;
}

$sup = $inf+$num_alarms_page;



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
$parameters['date_from']              = "date_from="      .urlencode($date_from);
$parameters['date_to']                = "date_to="        .urlencode($date_to);
$parameters['sensor_query']           = "sensor_query="   .$sensor_query;
$parameters['tag']                    = "tag="            .$tag;
$parameters['num_events']             = "num_events="     .$num_events;
$parameters['num_events_op']          = "num_events_op="  .$num_events_op;
$parameters['refresh_time']           = "refresh_time="   .$refresh_time;
$parameters['autorefresh']            = "autorefresh="    .$autorefresh;
$parameters['ds_id']                  = "ds_id="          .$ds_id;
$parameters['ds_name']                = "ds_name="        .urlencode($ds_name);
//$parameters['bypassexpirationupdate'] = "bypassexpirationupdate=1";
$parameters['beep']                   = "beep="           .$beep;
$parameters['host_id']                = "host_id="        .$host_id;
$parameters['net_id']                 = "net_id="         .$net_id;
$parameters['ctx']                    = "ctx="            .$ctx;


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
    "plugin_id"     => $plugin_id,
    "plugin_sid"    => $plugin_sid,
    "ctx"           => "",
    "host"          => $host_id,
    "net"           => $net_id,
    "host_group"    => ''
);
list($alarm_list, $count) = Alarm::get_list($conn, $criteria, true);

/*
* Pagination
*/

$total = ($inf>0 && intval($_SESSION["_alarm_count"])>0) ? $_SESSION["_alarm_count"] : $count;


// Timezone correction
$tz = Util::get_timezone();

// 
$results = array(); 

$sound   = 0;
$cont_tr = 0;
$time_start = time();

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
        
        $res['DT_RowId']  = $alarm->get_backlog_id();

            
        $ctx        = $alarm->get_ctx();  //THIS IS THE ENGINE!!!
        $id         = $alarm->get_plugin_id();
        $sid        = $alarm->get_plugin_sid();
        $backlog_id = $alarm->get_backlog_id();
        $event_id   = $alarm->get_event_id();
        $tags       = $alarm->get_tags();
        $csimilar   = $alarm->get_csimilar();
        $similar    = $alarm->get_similar();
        $sid_name   = $alarm->get_sid_name(); // Plugin_sid table just joined
        //$alarm_name = Util::translate_alarm($conn, $sid_name, $alarm, "array");
        
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
        
        list ($_a,$_stats,$_b) = Alarm::get_alarm_detail($conn,$backlog_id);
        unset($_a); unset($_b);

        $event_count_label = "";
        if ($backlog_id) 
        {
            $event_count       = (!empty($_stats)) ? $_stats["events"] : Alarm::get_total_events($conn, $backlog_id, true);
            $event_count_label = $event_count." "._("events");
        }

        $timestamp_utc = Util::get_utc_unixtime(Util::timestamp2date($alarm->get_timestamp())); // $alarm->get_last()
        $last          = gmdate("Y-m-d",$timestamp_utc+(3600*$tz));
        $hour          = gmdate("H:i:s",$timestamp_utc+(3600*$tz));
        $today         = gmdate("Y-m-d");


        $date          = Util::timestamp2date($alarm->get_timestamp());
        $timestamp_utc = Util::get_utc_unixtime($date);
        $beep_on       = ($beep && $refresh_time_secs>0 && (gmdate("U")-$timestamp_utc<=$refresh_time_secs)) ? true : false;
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
        
        /* show alarms by days */
        $date_slices              = split(" ", $date);
        list($year, $month, $day) = split("-", $date_slices[0]);
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
        
        /*
        $res["events"] = Util::number_format_locale($event_count_label,0);

        // CTXS
        if (Session::is_pro() && Session::show_entities()) {
            foreach ($ctxs as $_ctx) 
            {
                if (count($ctxs) < 2 || isset($entities[$_ctx])) 
                {
                    $res["entities"][] = (!empty($entities[$_ctx]['name'])) ? $entities[$_ctx]['name'] : _("Unknown");                              
                }
            }
        }
        else
        {
            $res["entities"] = array();
        }*/
        
        //$res["since"] = $since." ".Util::timezone($tz);
        //$res["last"]  = $last." ".Util::timezone($tz);
        if ($alarm->get_removable()) {
            $res[] = ($last==$today) ? $hour : $last; //get_alarm_life($since, $last);
            $res[] = $alarm->get_status();
        }
        else
        {
            $now = gmdate("Y-m-d H:i:s",gmdate("U")+(3600*$tz));
            $res[] = get_alarm_life($since, $now);
            $res[] = "<img align='absmiddle' src='/ossim/alarm/style/img/correlating.gif' class='img_cor tip' title='"._("This alarm is still being correlated and therefore it can not be modified")."'>";
        }
        
        //$res["status_background_color"] = ($alarm->get_status() == "open") ? "#ECE1DC" : "#DEEBDB";
        //$res["status_border_color"]     = ($alarm->get_status() == "open") ? "#E6D8D2" : "#D6E6D2";
        
        // TAGS
        $tgs = "";
        if (count($tags) > 0) 
        {
            foreach ($tags as $id_tag) 
            {
                $tgs .= $tags_html[$id_tag]." ";
            }
        }
        
        $res[]     = $tgs;
        
        // kingdom, category and subcategory
        list($alarm_ik,$alarm_sc) = Alarm::get_alarm_name($alarm->get_taxonomy());
        $res[]                    = $alarm_ik;
        $res[]                    = $alarm_sc;

        // risk
        $res[] = $risk;
        
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
        
        // Reputation info
        $rep_src_icon     = Reputation::getrepimg($event_info["rep_prio_src"],$event_info["rep_rel_src"],$event_info["rep_act_src"],$src_ip);
        //$rep_src_bgcolor  = Reputation::getrepbgcolor($event_info["rep_prio_src"]);
        $rep_dst_icon     = Reputation::getrepimg($event_info["rep_prio_dst"],$event_info["rep_rel_dst"],$event_info["rep_act_dst"],$dst_ip);
        //$rep_dst_bgcolor  = Reputation::getrepbgcolor($event_info["rep_prio_dst"]);

        // attack pattern
        $attach_pattern = "";
        if ($homelan_src)
        {
            $attach_pattern .= "<img src='style/img/home24.png' class='home_img'/>";
        }
        
        //Promiscuous icon
        if (count($_stats['src']['ip']) > 1 || count($_stats['src']['ip']) > 1)
    	{
    		 $attach_pattern .= " <img align='absmiddle' src='style/img/promiscuous.png' border='0'/>";
    	}
    	else
    	{
    		 $attach_pattern .= " <img align='absmiddle' src='style/img/npromiscuous.png' border='0'/>";
    	}
        
        if ($homelan_dst)
        {
            $attach_pattern .= "<img src='style/img/home24.png' class='home_img'/>";
        }
        
        $res[] = $attach_pattern;
            
        // SRC
        $res[] = "<div class='HostReportMenu' id='$src_hrm' ctx='$ctx_src'>$src_img" . (($homelan_src) ? " <strong>$src_name$src_port</strong> $rep_src_icon" : " $src_name$src_port $rep_src_icon") . "</div>";

        // DST

        $res[] = "<div class='HostReportMenu fleft' id='$dst_hrm' ctx='$ctx_dst'>$dst_img" . (($homelan_dst) ? " <strong>$dst_name$dst_port</strong> $rep_dst_icon" : " $dst_name$dst_port $rep_dst_icon") . "</div>";

        
        //Detail
        $res[] = "<img class='go_details' src='../pixmaps/show_details.png' border='0'>";
        
        
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

echo json_encode($response);

$db->close();
$geoloc->close();

unset($_SESSION['_SELECTED_ALARMS']);


/* End of file alarm_console_ajax.php */
/* Location: ./alarm/alarm_console_ajax.php */