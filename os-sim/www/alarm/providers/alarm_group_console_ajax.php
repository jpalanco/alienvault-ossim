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


Session::logcheck("analysis-menu", "ControlPanelAlarms");


/* DataTables Parameters */
$sec    = intval(POST('sEcho'));
$limit  = POST('iDisplayLength');
$offset = POST('iDisplayStart');

$_SESSION["per_page"] = $limit  = ($limit != '') ? intval($limit) : (isset($_SESSION["per_page"]) ? $_SESSION["per_page"] : 10);
$offset = ($offset != '') ? intval($offset) : 0;


/* Filter Parameters */
$group_type    = POST('group_type') ? POST('group_type') : "name";
$sensor_query  = POST('sensor_query');
$alarm_name    = (POST('alarm_name') != "") ? POST('alarm_name') : "";
$src_ip		   = POST('src_ip');
$dst_ip 	   = POST('dst_ip');
$asset_group   = POST('asset_group');
$date_from 	   = POST('date_from');
$date_to 	   = POST('date_to');
$intent 	   = intval(POST('intent'));
$directive_id  = POST('directive_id');
$num_events    = POST('num_events');
$num_events_op = POST('num_events_op');
$min_risk   = POST('min_risk') != "" ? POST('min_risk') : 0;
$vmax_risk   = POST('vmax_risk') != "" ? POST('vmax_risk') : 2;
$tag           = POST('tag');
$show_options  = POST('show_options');
$no_resolv 	   = intval(POST('no_resolv'));
$hide_closed   = intval(POST('hide_closed'));

ossim_valid($group_type,      OSS_ALPHA,                                               'illegal:' . _("Group Type"));
ossim_valid($sensor_query,    OSS_HEX, OSS_NULLABLE,                                   'illegal:' . _("Sensor"));
ossim_valid($alarm_name,      OSS_ALPHA, OSS_PUNC_EXT, OSS_SPACE, OSS_NULLABLE, 	   'illegal:' . _("Alarm Name"));
ossim_valid($src_ip,          OSS_IP_ADDRCIDR_0, OSS_NULLABLE, 				           'illegal:' . _("Src IP"));
ossim_valid($dst_ip,          OSS_IP_ADDRCIDR_0, OSS_NULLABLE, 				           'illegal:' . _("Dst IP"));
ossim_valid($asset_group,     OSS_HEX, OSS_NULLABLE,                                    'illegal:' . _("Asset Group"));
ossim_valid($date_from,       OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 		               'illegal:' . _("Date From "));
ossim_valid($date_to,         OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 			           'illegal:' . _("Date To"));
ossim_valid($intent,          OSS_DIGIT, OSS_NULLABLE, 							       'illegal:' . _("Intent"));
ossim_valid($directive_id,    OSS_DIGIT, OSS_NULLABLE, 							       'illegal:' . _("Directive ID"));
ossim_valid($num_events,      OSS_DIGIT, OSS_NULLABLE, 								   'illegal:' . _("Num Events"));
ossim_valid($num_events_op,   OSS_ALPHA, OSS_NULLABLE, 							       'illegal:' . _("Num Events Operator"));
ossim_valid($vmax_risk,        OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Max_risk"));
ossim_valid($min_risk,        OSS_ALPHA, OSS_NULLABLE,                                      'illegal:' . _("Min_risk"));
ossim_valid($tag,             OSS_HEX, OSS_NULLABLE, 								   'illegal:' . _("Tag"));
ossim_valid($no_resolv,       OSS_DIGIT, OSS_NULLABLE, 								   'illegal:' . _("No Resolv"));
ossim_valid($hide_closed,     OSS_DIGIT, OSS_NULLABLE, 					               'illegal:' . _("Hide Closed"));
ossim_valid($show_options,    OSS_DIGIT, OSS_NULLABLE, 							       'illegal:' . _("Show Options"));


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

if (empty($show_options) || ($show_options < 1 || $show_options > 4))
{
    $show_options = 1;
}


$db   = new ossim_db(TRUE);
$conn = $db->connect();


$db_groups = Alarm_groups::get_dbgroups($conn);

$criteria = array(
    'group_type'    => $group_type,
    'show_options'  => $show_options,
    'hide_closed'   => $hide_closed,
    'from_date'     => $date_from,
    'to_date'       => $date_to,
    'ip_src'        => $src_ip,
    'ip_dst'        => $dst_ip,
    'asset_group'   => $asset_group,
    'sensor'        => $sensor_query,
    'query'         => $alarm_name,
    'directive_id'  => $directive_id,
    'intent'        => $intent,
    'num_events'    => $num_events,
    'num_events_op' => $num_events_op,
    'vmax_risk'      => $vmax_risk,
    'min_risk'      => $min_risk,
    'tag'           => $tag,
    'limit'         => "LIMIT $offset, $limit"
);

list($alarm_group, $total) = Alarm_groups::get_grouped_alarms($conn, $criteria, TRUE);

$results = array();


foreach($alarm_group as $group) 
{
    $res = array();
    
	$group_id   = $group['group_id'];
	$ocurrences = $group['group_count'];
	
	$_SESSION[$group_id] = $group['name'];

	$max_risk   = $group['max_risk'];
	$id_tag     = $group['id_tag'];

	$show_day   = 0;
	$date       = '';
	
	if ($group['date'] != $lastday)
	{
		$lastday                  = $group['date'];
		list($year, $month, $day) = explode("-", $group['date']);
		$date                     = Util::htmlentities(strftime("%A %d-%b-%Y", mktime(0, 0, 0, $month, $day, $year)));
		$show_day                 = ($group_type == "name" || $group_type == "similar") ? 0 : 1;
	}

	$descr = $db_groups[$group_id]['descr'];

    //Get group status dynamically
    if($group_type == "similar")
    {
        $st_name = $group_id;
    }
    else
    {
        $st_name = ($group['name'] == _('Unknown Directive')) ? '' : $group['name'];
    }

    if ($group_type == "name" || $group_type == "similar")
    {
        $st_df = $date_from;
        $st_dt = $date_to;
    }
    else
    {
        $timestamp = preg_replace("/\s\d\d\:\d\d\:\d\d$/","", $group['date']);

        $st_df = $timestamp." 00:00:00";
        $st_dt = $timestamp;
    }

    $status = Alarm_groups::get_group_status($conn, $sensor_query, $src_ip, $dst_ip, $st_df, $st_dt, $st_name);

	$ocurrence_text = ($ocurrences > 1) ? strtolower(_("Alarms")) : strtolower(_("Alarm"));

	
	if ($db_groups[$group_id]['owner'] == $_SESSION["_user"])
	{
		$owner_title    = _('Click here to release this alarm.');
		$owner          = '<a class="owner_action av_l_main tip" data-status="release" href="javascript:;" title="'. $owner_title .'">'. _("Release") .'</a>';
		$owner_take     = 1;
		$av_description = "";

        //Create a new ticket for Group ID
        if (Session::menu_perms("analysis-menu", "IncidentsOpen"))
        {
            $ticket_name   = preg_replace('/&mdash;/', '--', Util::signaturefilter($group['name']));
    		$_st_df_aux    = (preg_match("/^\d\d\d\d\-\d\d\-\d\d$/", $st_df)) ? $st_df." ".date("H:i:s") : $st_df;
    		$_st_dt_aux    = (preg_match("/^\d\d\d\d\-\d\d\-\d\d$/", $st_dt)) ? $st_dt." ".date("H:i:s") : $st_dt;
    		
    		$incident_link = '<a class="greybox2" href="../incidents/newincident.php?ref=Alarm&title=' . urlencode($ticket_name) . "&" . "priority=$max_risk&" . "src_ips=$src_ip&"  . "event_start=$_st_df_aux&alarm_group_id=" .$group_id . "&event_end=$_st_dt_aux&" . "src_ports=&" . "dst_ips=$dst_ip&" . "dst_ports=" . '" title="'._("New Ticket").'">' . '<img border="0" title="'._("Add new ticket").'"  src="../pixmaps/new_ticket.png" class="tip newticket" />' . '</a>';
		}
		else
		{
            $incident_link  = "<img src='../pixmaps/new_ticket.png' class='newticket disabled' border='0'/>";
        }
	}
	else
	{
		$owner_title    = _('Click here to take this alarm.');
		$owner_name     = ($db_groups[$group_id]['owner'] == '') ? _("Take") : _('Taken by') . ' ' . $db_groups[$group_id]['owner'];
		$owner          = '<a class="owner_action av_l_main tip" data-status="take" href="javascript:;" title="'. $owner_title .'">'. $owner_name .'</a>';
		$owner_take     = 0;
		$av_description = "disabled";
		$incident_link  = "<img border='0' src='../pixmaps/new_ticket.png' class='newticket disabled'/>";
		
	}

	if ($status == 'open')
	{
		$status_name  = _('Open');
		$status_data  = 'open';
		
		if ($owner_take)
		{
		    $status_title = _('Open, click to close this group');
		    $status_class = 'av_l_main';
		}
		else
		{
			$status_title = _('Open, take this group first in order to close it');
			$status_class = 'av_l_disabled';
		}
	}
	else
	{
		
		$status_name  = _('Closed');
		$status_data  = 'close';
		
		if ($owner_take)
		{
			$status_title = _('Closed, click to open this group');
			$status_class = 'av_l_main';

		}
		else
		{
		    $status_title = _('Closed, take this group first in order to open it');
		    $status_class = 'av_l_disabled';
		}
	}
	
	$close_link = "<a href='javascript:;' class='ag_status tip $status_class' data-status='$status_data' title=\"$status_title\">$status_name</a>";


    $chk_status  = (!$owner_take) ? ' disabled ' : '';
    $chk_title   = (!$owner_take) ? _('You must take ownership first') : '';


    $res[] = "<input type='checkbox' title='$chk_title' class='tip ag_check' name='group' $chk_status value='$group_id'>";
    
    

    $group_id   = $group['group_id'];
    $g_ip_src   = $group['ip_src'];
    $g_ip_dst   = $group['ip_dst'];
    $g_time     = ($group_type == "name" || $group_type == "similar") ? "" : $group['date'];
    $g_from     = '';
    $g_similar  = ($group_type == "similar") ? "1" : "";


	$res[] = "<img class='toggle_group' src='../pixmaps/plus-small.png'/>";
	
	
	$g_name = "<table class='transparent'><tr>";

	if ($id_tag != '')
	{
        $group_tag = Tag::get_object($conn, $id_tag);

        $tag_name = $group_tag->get_name();
        $tag_class = $group_tag->get_class();

    	$g_name .= "<td class='transparent'>";
		$g_name .= "<span class='$tag_class in_line_av_tag'>$tag_name</span>";
		$g_name .= "</td>";
    }
				
    $g_name .= "<td class='transparent gname'>";
    $g_name .= Util::signaturefilter(Alarm::transform_alarm_name($conn, $group['name']));
    $g_name .= "&nbsp;&nbsp;<span style='font-size:xx-small;'>($ocurrences $ocurrence_text )</span>";
	$g_name .= "</td></tr></table>";


    $res[] = $g_name;
    
    $res[] = $date;

	$res[] = $owner;
	$risk_text = Util::get_risk_rext($max_risk);
	$res[] = '<span class="risk-bar '.$risk_text.'">' . _($risk_text) . '</span>';

	
	$desc = "<input type='text' class='ag_descr' title='$descr' $av_description size='30' style='height: 16px;' value='$descr'>";
	
	$dscr_class = 'disabled';
	
	if ($owner_take)
	{
        $dscr_class = '';
    }
    
    $desc .= "<img class='save_descr $dscr_class' src='../pixmaps/disk-black.png'/>";

	$res[] = $desc;
	

    $res[] = $close_link;
    $res[] = $incident_link;

    $res['DT_RowClass']  = ($owner_take) ? 'tr_take' : 'tr_no_take';
    $res['DT_RowClass'] .= ' g_alarm';
    $res['DT_RowId']     = $group['group_id'];
    
    $res['DT_RowData']['g_ip_src']  = $g_ip_src;
    $res['DT_RowData']['g_ip_dst']  = $g_ip_dst;
    $res['DT_RowData']['g_time']    = $g_time;
    $res['DT_RowData']['g_similar'] = $g_similar;
    
    
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

