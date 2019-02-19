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


ob_implicit_flush();
require_once 'av_init.php';

Session::logcheck("analysis-menu", "ControlPanelAlarms");

$geoloc = new Geolocation("/usr/share/geoip/GeoIP.dat");

/****************/
$backlog_id = POST('backlog_id');
$event_id   = POST('event_id');
$show_all   = POST('show_all');
$hide       = POST('hide');
$box        = POST('box');
$from       = (POST('from') != "") ? POST('from') : 0;


ossim_valid($backlog_id, 	OSS_HEX, OSS_NULLABLE, 		'illegal:' . _("backlog_id"));
ossim_valid($event_id, 		OSS_HEX, OSS_NULLABLE, 		'illegal:' . _("Event_id"));
ossim_valid($show_all, 		OSS_DIGIT, OSS_NULLABLE, 	'illegal:' . _("Show_all"));
ossim_valid($hide, 			OSS_ALPHA, OSS_NULLABLE,    'illegal:' . _("Hide"));
ossim_valid($from, 			OSS_DIGIT, OSS_NULLABLE,    'illegal:' . _("From"));
ossim_valid($box, 			OSS_DIGIT, OSS_NULLABLE,    'illegal:' . _("From"));

if (ossim_error()) 
{
    die(ossim_error());
}

$no_resolv          = ($_SESSION["_no_resolv"]==1) ? TRUE : FALSE;
$summ_event_count   = 0;
$highest_rule_level = 0;
$max_events         = 50;
$to                 = $from + $max_events;
$conf               = $GLOBALS["CONF"];
$acid_link          = $conf->get_conf("acid_link");
$acid_prefix        = $conf->get_conf("event_viewer");



/* connect to db */
$db       = new ossim_db(TRUE);
$conn     = $db->connect();

$show_all = empty($show_all) ? 0 : $show_all;


$master_alarm_sid = 0;
$plugin_sid_list  = array();

$buffer = '';


$buffer .= "
		<table width='100%' class='table_list ajaxgreen'>
			<tr>
				<th>#</th>
				<th>". _("Event") ."</th>
				<th>". _("Risk") ."</th>
				<th>". _("Date") ."</th>
				<th>". _("Source") ."</th>
				<th>". _("Destination") ."</th>
				<th>". _("OTX") ."</th>
		";
if ($box == "1" && $show_all>0) 
{ 
	$url_asc  = "fill_table('$backlog_id', '', 3, '$hide', '', 1)";
	$url_desc = "fill_table('$backlog_id', '', 2, '$hide', '', 1)";

	$buffer .= "<th style='width:65px;white-space:normal;'>
					<div id='c_th_correlation'>
					<table class='transparent'>
						<tr>
							<td>
								<a href='javascript:;' onclick=\"$url_desc\">                           
                                    <img id='sort_desc' src='/ossim/pixmaps/data_tables/sort_desc.png' border='0' align='top'/>
                                </a>
							</td>
							<td>
								". _("Correlation Level") ."
							<td>
							<td>
								<a href='javascript:;' onclick=\"$url_asc\">
                                    <img id='sort_asc' src='/ossim/pixmaps/data_tables/sort_asc.png' border='0' align='top'/>                            
                                </a>
							</td>
						</tr>
					</table>
					</div>
				</th>";

} 
else 
{
	$buffer .= "<th style='width:60px;white-space:normal;'>". _("Correlation Level")."</th>";
} 

$buffer .= "</tr>";

// Timezone correction
$tz                              = Util::get_timezone();
$alarms_numbering                = Alarm::get_alarms_numbering($conn, $backlog_id);
list($alarm_list, $total_events) = Alarm::get_events($conn, $backlog_id, $show_all, $event_id, $from, $max_events, $alarms_numbering, true);
list ($alarm_object, $event)     = Alarm::get_alarm_detail($conn, $backlog_id);

if ($total_events > 0) 
{        
	$first_number = ($event_id != '') ? (($from > 0) ? $from - 1 : $from) : $from;	
	
	if ($first_number<0) $first_number=0;
	$count_events = $first_number;
    $count_jump = 0;
    
	foreach($alarm_list as $alarm) 
	{
    	$id           = $alarm->get_plugin_id();
        $sid          = $alarm->get_plugin_sid();
        $backlog_id   = $alarm->get_backlog_id();
        $risk         = $alarm->get_risk();
    	$ctx          = $alarm->get_ctx();
        $aid          = $alarm->get_event_id();
        $sid_name     = $alarm->get_sid_name();
        $sid_priority = $alarm->get_sid_priority();
        $rule_level   = $alarm->get_rule_level();
        $view         = Alarm::event_allowed($conn,$ctx,$alarm->get_src_host(),$alarm->get_dst_host(),$alarm->get_src_net(),$alarm->get_dst_net());
        $td_class     = ($id==1505) ? "td_directive_event" : "transparent";
        if ($sid_name=="")           $sid_name = "Unknown (id=$id sid=$sid)";
        if ($sid_priority=="")       $sid_priority = "N/A";
        if (!$show_all || $id==1505) $summary = Alarm::get_alarm_resume($conn, $backlog_id, $rule_level, true);
		
			
		
		$buffer .= "<tr class='".$td_class."'> <td>";
		
		if (!$master_alarm_sid) $master_alarm_sid = $sid;
		
		if ($id==1505)
		{
		    $name = Util::translate_alarm($conn, $sid_name, $alarm);
		}
		elseif ($id == Siem::OTX_PULSE_ID)
		{
    		$event_name = Util::translate_alarm($conn, $sid_name, $alarm_object, 'array');
    		$name       = ($event_name['name']) ? $event_name['name'] : $sid_name;
		}
		else
		{
    		$name = $sid_name;
		}

		$name = "<b>$name</b>";

		if ($id==1505)
		{
			$buffer .= "<b>" . $alarms_numbering[$aid] . "</b>";
			$count_jump++;
		}
		else 
		{	
			$buffer .= ++$count_events;
		}
				
		$buffer .= "</td><td class='td_event_name' style='text-align:left'>";
		
		$asset_src = $alarm->get_asset_src();
		$asset_dst = $alarm->get_asset_dst();
		$href_sim  = '';
		
		if ($view && $aid) 
		{
			$href = str_replace("//","/","$acid_link/" . $acid_prefix . "_qry_alert.php?submit=%230-$aid");
								
			if ($id == 1505) 
			{
				$img     = "/ossim/pixmaps/arrow-315-small.png";
				$buffer .=  "&nbsp;<img align='absmiddle' src='$img' border='0'/>";
			}
			
			$href_sim = Util::get_acid_single_event_link($aid)."&minimal_view=1&noback=1";
			
			
			$buffer .= "<a class='greybox trlnka' id='$id;$sid' title='". _("Event detail") ."' href='$href_sim". (($box == "") ? "&noback=1" : "") ."'>$name</a>";
				
				 
			if ($id == 1505) 
			{ 
				$buffer .= "<div style='display: none;'>
                                 <table class='t_white'>
                                    <tr>
									   <td>"._("Src Asset").":</td>
									   <td>".$asset_src."</td>
									</tr>
									<tr>
									   <td>"._("Dst Asset").":</td>
									   <td>".$asset_dst."</td>
									</tr>
									<tr>
									   <td>"._("Priority").":</td>
									   <td>".$sid_priority."</td>
									</tr>
								</table>
							</div>";							
			}
		} 
		else 
		{
			$href    = "";
			$buffer .= "<span style='color:gray'>$name</span>";
		}

			
		$buffer .= "</td>";

		$orig_date      = $alarm->get_timestamp();
		$date           = Util::timestamp2date($orig_date);
		$orig_date      = $date;
		$event_date     = $date;
		$event_date_uut = Util::get_utc_unixtime($event_date);
		$date           = gmdate("Y-m-d H:i:s",$event_date_uut+(3600*$tz));        
		$event_date     = gmdate("Y-m-d H:i:s",$event_date_uut+(3600*$alarm->get_tzone()));
		
		$src_ip   = $alarm->get_src_ip();
		$dst_ip   = $alarm->get_dst_ip();
		$src_port = $alarm->get_src_port();
		$dst_port = $alarm->get_dst_port();
		
		$src_port = ($src_port != 0) ? ":".Port::port2service($conn, $src_port) : "";
		$dst_port = ($dst_port != 0) ? ":".Port::port2service($conn, $dst_port) : "";

		$event_info = Alarm::get_event ($conn, $aid);
		$src_host   = Asset_host::get_object($conn, $event_info["src_host"]);
		$dst_host   = Asset_host::get_object($conn, $event_info["dst_host"]);
		$src_net_id = $event_info["src_net"];
        $dst_net_id = $event_info["dst_net"];
							
		if ($risk > 7) 
		{
			$buffer .= "<td bgcolor='#FA0000'><b>";
			if ($view && $href_sim) $buffer .= "<a class='greybox' href='$href_sim'>";
			$buffer .= "<font color='white'>$risk</font>";
			if ($view && $href_sim) $buffer .= "</a>";
			$buffer .= "</b></td>";
		} 
		elseif ($risk > 4) 
		{
			$buffer .= "<td bgcolor='#FF8A00'><b>";
			if ($view && $href_sim) $buffer .= "<a class='greybox' href='$href_sim'>";
			$buffer .= "<font color='black'>$risk</font>";
			if ($view && $href_sim) $buffer .= "</a>";
			$buffer .= "</b></td>";
		} 
		elseif ($risk > 2) 
		{
			$buffer .= "<td bgcolor='#94CF05'><b>";
			if ($view && $href_sim) $buffer .= "<a class='greybox' href='$href_sim'>";
			$buffer .= "<font color='white'>$risk</font>";
			if ($view && $href_sim) $buffer .= "</a>";
			$buffer .= "</b></td>";
		} 
		else 
		{
			$buffer .= "<td><b>";
			if ($view && $href_sim) $buffer .= "<a class='greybox' href='$href_sim'>";
			$buffer .= "$risk";
			if ($view && $href_sim) $buffer .= "</a>";
			$buffer .= "</b></td>";
		}


		$buffer .= "<td class='td_date' nowrap='nowrap'>";

		if ($view) 
		{
			if ($event_date==$orig_date || $event_date==$date) 
			{ 
				$buffer .= "<a class='greybox' href='$href_sim'><font color='black'>$date</font></a>";
			} 
			else 
			{ 
				$buffer .= "

							<a class='greybox' href='$href_sim'>
                                <font color='black'>$date</font>
							</a>
							<div style='display: none;'>
                                <table class='t_white'>                           
                                    <tr>
                                        <td>". _('Sensor date') .":</td>
                                        <td>$event_date</td>
                                    </tr>
                                    
                                    <tr>
                                        <td>". _("Timezone") .":</td>
                                        <td>". Util::timezone($alarm->get_tzone()) ."</td>
                                    </tr>
                                </table>
                            </div>		
				";
			} 
		} 
		else 
		{
			$buffer .= "<span style='color:gray'>$date</span>";
		}
			
		$buffer .= "</td>";
		
		// Src
		if ($no_resolv || !$src_host) 
		{
			$src_name   = $src_ip;
			$ctx_src    = $ctx;
		} 
		elseif ($src_host) 
		{
			$src_name   = $src_host->get_name();
			$ctx_src    = $src_host->get_ctx();
		}
		// Src icon and bold
		$src_output  = Asset_host::get_extended_name($conn, $geoloc, $src_ip, $ctx_src, $event_info["src_host"], $event_info["src_net"]);
		$homelan_src = ($src_output['is_internal']) ? "bold" : "";
		$src_img     = $src_output['html_icon'];
		
		// Dst
		if ($no_resolv || !$dst_host) 
		{
			$dst_name   = $dst_ip;
			$ctx_dst    = $ctx;
		} 
		elseif ($dst_host) 
		{
			$dst_name   = $dst_host->get_name();
			$ctx_dst    = $dst_host->get_ctx();
		}
		// Dst icon and bold
		$dst_output  = Asset_host::get_extended_name($conn, $geoloc, $dst_ip, $ctx_dst, $event_info["dst_host"], $event_info["dst_net"]);
		$homelan_dst = ($dst_output['is_internal']) ? "bold" : "";
		$dst_img     = $dst_output['html_icon'];
				
		$src_title  = _("Src Asset").": <b>$asset_src</b><br>"._("IP").": <b>$src_ip</b>";
		$dst_title  = _("Dst Asset").": <b>$asset_dst</b><br>"._("IP").": <b>$dst_ip</b>";

		$buffer .= "<td nowrap='nowrap'>";      

		if (!$view)
		{
			$buffer .= "<span style='color:gray'>X.X.X.X</span>";
		}
		else
		{
			$buffer .= "$src_img <a href='$href_sim' class='HostReportMenu greybox $homelan_src' id2='$src_ip;$dst_ip' id='$src_ip;$src_name;".$event_info["src_host"]."' ctx='$ctx'>$src_name$src_port</a>";
		} 
		
		$buffer .= "</td>";
		
			
		$buffer .=	"<td nowrap='nowrap'>";

		if (!$view)
		{
			$buffer .= "<span style='color:gray'>X.X.X.X</span>";
		}
		else
		{
			$buffer .= "$dst_img <a href='$href_sim' class='HostReportMenu greybox $homelan_dst' id2='$dst_ip;$src_ip' id='$dst_ip;$dst_name;".$event_info["dst_host"]."' ctx='$ctx'>$dst_name$dst_port</a>";
		}

		$buffer .=	"</td>";
        
        $otx_icon  = Alarm::get_alarm_event_otx_icon($conn, $aid);
        if ($otx_icon)
        {
            $alarm_otx = "<img src='$otx_icon' class='otx_icon pointer' data-event='$aid'/>";
        }
        else
        {
            $alarm_otx = _('N/A');
        }
        
        $buffer .= '<td>'. $alarm_otx .'</td>';   

		$buffer .=	"<td ". ((!$view) ? "style='color:gray'" : "") .">". $alarm->get_rule_level() ."</td>";
		$buffer .= "</tr>";
	

        if ($highest_rule_level == 0) $highest_rule_level = $alarm->get_rule_level();
        
		// Alarm summary
        if (!$show_all || $id==1505) 
		{
            $summ_count        = $summary["count"];
            $summ_event_count += $summ_count;
            $summ_dst_ips      = $summary["dst_ips"];
            $summ_types        = $summary["types"];
            $summ_dst_ports    = $summary["dst_ports"];
            
			$buffer .= "
            <tr>
            	<td colspan='9' style='border-bottom:1px solid #CCCCCC;padding:3px; background: #F6FFF4;'>
					<b>" . _("Alarm Summary") . "</b> [ 
					". _("Total events matched with high rule level:") ." <b>".$summary["prevrl_count"]."</b>
					&nbsp;-&nbsp;
					". _("Total Events:") ." <b>$summ_count</b>
					&nbsp;-&nbsp;
					". _("Unique Dst IPAddr:") ." <b>$summ_dst_ips</b>
					&nbsp;-&nbsp;
					". _("Unique Types:") ." <b>$summ_types</b>
					&nbsp;-&nbsp;
					". _("Unique Dst Ports:") ." <b>$summ_dst_ports</b>
					]
			</td>";

        }

    } /* foreach alarm_list */
	
	
	if ( $hide == "" ) 
	{
		$href    = "fill_table('$backlog_id', '', 2, 'directive', '', 1)";
		$buffer .= "
			<tr>
				<td colspan='6' bgcolor='#eeeeee' style='text-align:left;padding-left:5px'>
					<a href='javascript:;' onclick=\"$href\" class='trlnka' id='$id;$sid' title='"._("Events detail") ."'><img src='/ossim/pixmaps/plus-small.png' align='absmiddle'>". $summary["total_count"] - $summ_event_count ."</a>". _("Total events matched after highest rule level, before timeout.") ."
				</td>
				<td colspan='3' bgcolor='#eeeeee'>
					<a href='/ossim/directives/index.php?toggled_dir=$master_alarm_sid&hmenu=Directives&smenu=Directives' class=''><b>". _("View") ."</b>/<b>" . _("Edit") ."</b>". _("current directive definition") ."</a>
				</td>
			</tr>
		";
	}

	if ($from + $max_events < $total_events || $from > 0) 
	{ 
		$buffer .= "
			<tr>
				<td class='no_hover noborder' colspan='9'>
					<div class='dt_footer'>
					   
					   <div class='t_entries'>                         
                            ".sprintf("</span>"._("Showing %d to %d events")."</span>", ($first_number + 1), ($count_events+$count_jump))."                                            
                        </div>";

		
		$buffer .= "<div class='t_paginate'>";	
					
		if ($from > 0) 
		{
			$func    = "fill_table('$backlog_id', '$event_id', '$show_all', '$hide', '". ($from-$max_events) ."', 1)";			
			$buffer .= "<a href='javascript:void(0);' class='av_l_main' onclick=\"$func\">&lt;". _("Previous")."</a>";
		}
		else
		{
    		$buffer .= "<span>&lt; "._("Previous")."</span>";
		}
		 
		
		$buffer .= "&nbsp;&nbsp;&nbsp;&nbsp;";
		
		if ($from + $max_events < $total_events) 
		{
			$func    = "fill_table('$backlog_id', '$event_id', '$show_all', '$hide', '". ($from+$max_events) ."', 1)";
			$buffer .= "<a href='javascript:void(0);' class='av_l_main' onclick=\"$func\" >". _("Next")." &gt;</a>";
		}
		else
		{
    		$buffer .=  "<span>"._("Next")." &gt;</span>";
		} 
		
		$buffer .= "    </div>
					</div>	
				</td>
			</tr>"; 
		

	}

}
else /* if alarm_list */
{ 
	$href    = "fill_table('$backlog_id', '', 2, 'directive', '', 1)";
	$buffer .= "
			<tr>
				<td colspan='9' class='left'>
				<a href='javascript:;' onclick=\"$href\" class='greybox' title='". _("Events detail") ."'><img src='/ossim/pixmaps/plus-small.png' align='absmiddle'></a>". _("No alarms found, toggle this to view all events.") ."
				</td>
			</tr>
	";
} 

$buffer .= "</table>";

echo $buffer;
	
$db->close();
$geoloc->close();
?>

