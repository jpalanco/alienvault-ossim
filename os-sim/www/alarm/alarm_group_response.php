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
require_once 'alarm_common.php';

ini_set('memory_limit', '1024M');
set_time_limit(0);

Session::logcheck('analysis-menu', 'ControlPanelAlarms');

function retrieve_groups($num)
{
    $g_list = array();
    for ($i = 1; $i <= $num; $i++) 
    {
        $aux = explode("_",GET('group'.$i));
        
        if (ossim_valid($aux[0], OSS_HEX, 'illegal:' . _("Group ID")))
        {
            $g_list[] = "'". $aux[0] ."'";
        }
    }

    return implode(',', $g_list);
}



$src_ip       = GET('ip_src');
$dst_ip       = GET('ip_dst');
$timestamp    = GET('timestamp');
$from_date    = GET('date_from');
$to_date      = GET('date_to');
$sensor_query = GET('sensor_query');

if(intval(GET('similar'))!=1)
{
    $name = $_SESSION[GET('name')];
}
else
{
    $name = GET('name');
}
    
$directive_id  = GET('directive_id');
$alarm_name    = GET('alarm_name');
$tag           = GET('tag');
$group_id      = GET('group_id');
$hide_closed   = GET('hide_closed');
$delete_all    = GET('delete_all'); // Number of groups to delete
$only_delete   = GET('only_delete'); // Number of groups to delete
$only_close    = GET('only_close'); // Number of groups to close
$only_open     = GET('only_open'); // Number of groups to open
$unique_id     = GET('unique_id');
$num_events    = GET('num_events');
$num_events_op = GET('num_events_op');
$no_resolv     = intval(GET('no_resolv'));
$top           = (GET('top') != "") ? GET('top') : 100;
$from          = (GET('from') != "") ? GET('from') : 0;
$top          += $from;

$timestamp = preg_replace("/\s\d\d\:\d\d\:\d\d$/","",$timestamp);

ossim_valid($src_ip,         OSS_IP_ADDRCIDR_0, OSS_NULLABLE,                             'illegal:' . _("Src_ip"));
ossim_valid($dst_ip,         OSS_IP_ADDRCIDR_0, OSS_NULLABLE,                             'illegal:' . _("Dst_ip"));
ossim_valid($timestamp,      OSS_DIGIT, OSS_SCORE, OSS_NULLABLE,                          'illegal:' . _("Timestamp"));
ossim_valid($from_date,      OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 				          'illegal:' . _("From_date"));
ossim_valid($to_date,        OSS_DIGIT, OSS_SCORE, OSS_NULLABLE,                          'illegal:' . _("To_date"));
ossim_valid($name,           OSS_DIGIT, OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE, '\>\<',    'illegal:' . _("Name"));
ossim_valid($alarm_name,     OSS_ALPHA, OSS_PUNC_EXT, OSS_SPACE, OSS_NULLABLE, 			  'illegal:' . _("Alarm_name"));
ossim_valid($tag, OSS_DIGIT, OSS_NULLABLE,                                                'illegal:' . _("Label id"));
ossim_valid($hide_closed,    OSS_DIGIT, OSS_NULLABLE,                     		          'illegal:' . _("Hide_closed"));
ossim_valid($only_delete,    OSS_DIGIT, OSS_NULLABLE,                     		          'illegal:' . _("Only_delete"));
ossim_valid($only_close,     OSS_DIGIT, OSS_NULLABLE,                      		          'illegal:' . _("Only_close"));
ossim_valid($only_open,      OSS_DIGIT, OSS_NULLABLE,                       		      'illegal:' . _("Only_open"));
ossim_valid($unique_id,      OSS_ALPHA, OSS_DIGIT, OSS_SCORE, OSS_NULLABLE, 		      'illegal:' . _("Unique id"));
ossim_valid($group_id,       OSS_DIGIT, OSS_ALPHA, OSS_NULLABLE, OSS_SCORE,  		      'illegal:' . _("Group_id"));
ossim_valid($num_events,     OSS_DIGIT, OSS_NULLABLE,                                     'illegal:' . _("Num_events"));
ossim_valid($num_events_op,  OSS_ALPHA, OSS_NULLABLE,                                     'illegal:' . _("Num_events_op"));
ossim_valid($no_resolv,      OSS_DIGIT, OSS_NULLABLE,                                     'illegal:' . _("No_resolv"));
ossim_valid($sensor_query,   OSS_HEX, OSS_NULLABLE,                                       'illegal:' . _("Sensor_query"));
ossim_valid($directive_id,   OSS_DIGIT, OSS_NULLABLE, 							          'illegal:' . _("Directive_id"));
ossim_valid($from,           OSS_DIGIT, OSS_NULLABLE,                     		          'illegal:' . _("From"));
ossim_valid($top,            OSS_DIGIT, OSS_NULLABLE,                     		          'illegal:' . _("Top"));

if (ossim_error()) 
{
    die(ossim_error());
}


$db   = new ossim_db(TRUE);
$conn = $db->connect();
$geoloc = new Geolocation("/usr/share/geoip/GeoLiteCity.dat");

$conf = $GLOBALS["CONF"];
$mssp = Session::show_entities(); //($conf->get_conf("alienvault_mssp", FALSE) == 1) ? true : false;

$user = Session::get_session_user();

if ($timestamp != "") 
{
	$from_date = ($timestamp!="") ? $timestamp." 00:00:00" : null;
	$to_date   = ($timestamp!="") ? $timestamp : null;
}

// Delete ALL
if ($delete_all) 
{
    if (!Session::menu_perms("analysis-menu", "ControlPanelAlarmsDelete"))
    {
        die(ossim_error("You don't have required permissions to delete Alarms"));
    }
    
    $data['extra'] = "DELETE FROM alarm_groups WHERE owner='$user'";
    $file          = Alarm::delete_all_backlog($conn, $data);
    @system("php /usr/share/ossim/scripts/alarms/bg_alarms.php $user $file > /dev/null 2>&1 &");

    $db->close();
    exit();
}

// Delete selected
if ($only_delete) 
{

    // Check required permissions
    if (!Session::menu_perms("analysis-menu", "ControlPanelAlarmsDelete"))
    {
        die(ossim_error("You don't have required permissions to delete Alarms"));
    }
    
    $data['groups'] = retrieve_groups($only_delete);

    $file = Alarm_groups::delete_alarms_from_groups($conn, $data);
    @system("php /usr/share/ossim/scripts/alarms/bg_alarms.php $user $file > /dev/null 2>&1 &");

	$db->close();
	exit();
}

// Close selected
if ($only_close) 
{
    $groups = retrieve_groups($only_close);

    $file = Alarm_groups::change_status($groups, "closed");
    @system("php /usr/share/ossim/scripts/alarms/bg_alarms.php $user $file > /dev/null 2>&1 &");

	$db->close();
	exit();
}

// Open selected
if ($only_open) 
{
	$groups = retrieve_groups($only_open);

    $file = Alarm_groups::change_status($groups, "open");
    @system("php /usr/share/ossim/scripts/alarms/bg_alarms.php $user $file > /dev/null 2>&1 &");

	$db->close();
	exit();
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
$name = ($name == _('Unknown Directive')) ? '' : $name;
list ($list,$num_rows) = Alarm_groups::get_alarms ($conn, $sensor_query, $src_ip, $dst_ip, $hide_closed, "", $from, $top, $from_date, $to_date, $name,"",$directive_id,$tag, TRUE, $num_events, $num_events_op);

$tz = Util::get_timezone();

$colspan = (Session::is_pro() && $mssp) ? 11 : 10;
?>

<table class='t_agr table_list'>	
    <tr>
        <th></th>
        <th></th>
        <th><?php echo _("Alarm Name")?></th>
        <th><?php echo _("Events")?></th>
        <th><?php echo _("Risk")?></th>
        <?php 
        if (Session::is_pro() && $mssp) 
        { 
            ?>
            <th><?php echo _("Context")?></th>
            <?php 
        } 
        ?>
        <th><?php echo _("Duration")?></th>
        <th><?php echo _("Source")?></th>
        <th><?php echo _("Destination")?></th>
        <th><?php echo _("Status")?></th>
        <th><?php echo _("Action")?></th>
    </tr>
     
    <?php
    if (empty($list))
    {        
        ?>
        <tr>
            <td class="no_alarms" colspan='<?php echo $colspan?>'><?php echo _("No alarm with these criteria")?></td>
        </tr>
        <?php
    }
    else
    {     
        foreach ($list as $s_alarm) 
        {            
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
           				                        
        	// Reputation info
        	$rep_src_icon     = Reputation::getrepimg($event_info["rep_prio_src"],$event_info["rep_rel_src"],$event_info["rep_act_src"],$s_src_ip);
        	//$rep_src_bgcolor  = Reputation::getrepbgcolor($event_info["rep_prio_src"]);	
        	
        	$rep_dst_icon     = Reputation::getrepimg($event_info["rep_prio_dst"],$event_info["rep_rel_dst"],$event_info["rep_act_dst"],$s_dst_ip);
        	//$rep_dst_bgcolor  = Reputation::getrepbgcolor($event_info["rep_prio_dst"]);            
        	
            $c_src_homelan = ($homelan_src) ? 'bold alarm_netlookup' : '';
                                        
            $source_link = $src_img . " <a href='$s_src_link' class='$c_src_homelan' data-title='$s_src_ip-$ctx_src' title='$s_src_ip'>".$s_src_name.$s_src_port."</a> $rep_src_icon";
            
            $source_balloon  = "<div id='".$s_src_ip.";".$s_src_name.";".$event_info["src_host"]."' ctx='$ctx' id2='".$s_src_ip.";".$s_dst_ip."' class='HostReportMenu'>";
            $source_balloon .= $source_link;
            $source_balloon .= "</div>";
        
            $c_dst_homelan = ($homelan_dst) ? 'bold alarm_netlookup' : '';
                        
            $dest_link = $dst_img . " <a href='$s_dst_link' class='$c_dst_homelan' data-title='$s_dst_ip-$ctx_dst' title='$s_dst_ip'>".$s_dst_name.$s_dst_port."</a> $rep_dst_icon";
            
            $dest_balloon  = "<div id='".$s_dst_ip.";".$s_dst_name.";".$event_info["dst_host"]."' ctx='$ctx' id2='".$s_dst_ip.";".$s_src_ip."' class='HostReportMenu'>";
            $dest_balloon .= $dest_link;
            $dest_balloon .= "</div>";
            
            
            
            //		    $selection_array[$group_id][$child_number] = $s_backlog_id . "-" . $s_event_id;
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
            $s_alarm_name = ereg_replace("directive_event: ", "", $s_sid_name);
            $s_alarm_name = Util::translate_alarm($conn, $s_alarm_name, $s_alarm);
            
            
            $event_ocurrences = Alarm::get_total_events($conn, $s_backlog_id);
            if ($event_ocurrences != 1) 
            {
                $ocurrences_text = strtolower(gettext("Events"));
            } 
            else 
            {
                $ocurrences_text = strtolower(gettext("Event"));
            }
            
            /* Risk field */
            if ($s_risk > 7) 
            {
                $color = "red";
            } 
            elseif ($s_risk > 4) 
            {
                $color = "orange";
            } 
            elseif ($s_risk > 2) 
            {
                $color = "green";
            }
            else
            {
                $color = "black";
            }
            
            $risk_field = "<td><span class='$color'>$s_risk</span></td>";
           
            $s_delete_link = ($s_status == 'open') ? "<a href='' onclick=\"document.getElementById('action').value='close_alarm';document.getElementById('alarm').value='$s_backlog_id';form_submit();return false\" title='" . gettext("Click here to close alarm") . "'><img border=0 src='../pixmaps/cross-circle-frame.png' style='visibility: visible;'></a>" : "<img border=0 src='../pixmaps/cross-circle-frame-gray.png'>";
            
            //Create a new ticket
            if (Session::menu_perms("analysis-menu", "IncidentsOpen"))
            {
                 // clean ports name
        
                $g_src_port   = preg_replace("/^:/", "", $s_src_port);
                $g_dst_port   = preg_replace("/^:/", "", $s_dst_port);
                
                // clean ticket title
        
                $g_alarm_name = str_replace("&mdash;", "-", $s_alarm_name);
                 
                $incident_link  = '<a class="greybox2" title="'._("New alarm ticket").'" href="../incidents/newincident.php?ref=Alarm&title=' . urlencode($g_alarm_name) . '&priority='.$s_risk.'&src_ips='.$s_src_ip.'&event_start='.$s_since.'&event_end='.$s_date.'&src_ports='.$g_src_port.'&dst_ips='.$s_dst_ip.'&dst_ports='.$g_dst_port.'"><img src="../pixmaps/script--pencil.png" alt="'._("New alarm ticket").'" border="0"/></a>';
            }
            else
            {
                $incident_link  = "<span class='disabled'><img src='../pixmaps/script--pencil.png' alt='"._("New alarm ticket")."' title='"._("New alarm ticket")."' border='0'/></span>";
             
            } 
                        
            /* Checkbox */
            if ($owner == $_SESSION["_user"] || $owner == "") 
            {
                $checkbox = "<input type='checkbox' name='check_".$s_backlog_id."_".$s_event_id."' class='alarm_check' value='1'/>";
            } 
            else 
            {
                $checkbox = "<input type='checkbox' name='alarm_checkbox' disabled='disabled' value='".$s_backlog_id."-".$s_event_id."'/>";
            }
            
            if ($s_status == 'open') 
            {
                $status_link = "<span style='color:#923E3A'>"._("Open")."</span>";                               
            } 
            else 
            {
                $status_link = "<a href='' onclick=\"document.getElementById('action').value='open_alarm';document.getElementById('alarm').value='$s_backlog_id';form_submit();return false\" title='" . _("Click here to open alarm") . "'><span style='color:#4C7F41'>" . _("Closed") . "</span></a>";
                
                $checkbox = "<input type='checkbox' name='alarm_checkbox' disabled='disabled' value='".$s_backlog_id."-".$s_event_id."'/>";
            }
            
            if (!$s_alarm->get_removable() && !preg_match("/disabled/",$checkbox))
            {
            	$checkbox = str_replace(">"," disabled='true'>",$checkbox);
            }
            
            /* Expand button */
            if ($s_backlog_id && $s_id==1505 && $event_ocurrences > 0 && $s_alarm->get_removable())
            {
                $expand_button = "<a href='' onclick=\"toggle_alarm('$s_backlog_id','$s_event_id');return false;\"><img src='../pixmaps/plus-small.png' border='0' alt='plus'></img></a>";
            }
            else
            {
                $expand_button = "<img src='../pixmaps/plus-small-gray.png' border='0' alt='plus'>";
                $events_count = "";
            }
            
            $href_sim = Util::get_acid_single_event_link ($s_event_id)."&minimal_view=1&noback=1";
             
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
            ?>
        
            <tr>
                <td style='padding-left:30px; width: 3%;' id="eventplus<?php echo $s_backlog_id."-".$s_event_id?>"><?php echo $expand_button ?></td>
                <td><?php echo $checkbox?></td>
                <td class="td_alarm_name" style='padding-left:10px; width: 30%;'>
                    <a href="<?php echo $s_alarm_link?>" class="greybox2" title="<?php echo _("Alarm detail")?>"><?php echo $s_alarm_name.$events_count?></a>
                    <?php echo $tooltip?>
                </td>
        		<td class="nobborder center">
        			<?php 
        				echo Util::number_format_locale($event_ocurrences,0); 
        			?>
        		</td>
                <?php 
                echo $risk_field;
                
                if (Session::is_pro() && $mssp) 
                { 
                    ?>                    
        			<!-- entity -->
        			<td class="nobborder" style="text-align:center;">
        				<?php
        		        foreach ($ctxs as $_ctx) 
        		        {
        		            if (count($ctxs) < 2 || $entity_types[$_ctx] != 'engine') 
        		            {
        		                echo ((!empty($entities[$_ctx])) ? $entities[$_ctx] : _("Unknown"))."<br/>";    				            
        		            }
        		        }					
        				?>
        			</td>
        			<!-- end entity -->
        			<?php 
                } 
        
        		if ($s_alarm->get_removable()) 
        		{   				
        		    ?>
        			<td class="nobborder" style='width: 12%; text-align:center'>
        				<?php
        				$ago       = get_alarm_life($s_since, $s_last);
        				$acid_link = Util::get_acid_events_link($s_since, $s_date, "time_a");
        				echo "<a href=\"$acid_link\" class='stop'><span style='color:black' class='tip' title='"._("First").": $s_since ".Util::timezone($tz)."<br>"._("Last").":  $s_last ".Util::timezone($tz)."'>".$ago."</span></a>";
        				?>
        			</td>    				
        			<?php
        		} 
        		else 
        		{
        		    ?>
        			<td class="nobborder" style='<?php echo $bgcolor ?>text-align: center' width='12%'>
        				<?php
        				$now = gmdate("Y-m-d H:i:s",gmdate("U")+(3600*$tz));
        				$ago = get_alarm_life($s_since, $now);
        				$acid_link = Util::get_acid_events_link($s_since, $now, "time_a");
        				echo "<a href=\"$acid_link\" class='stop'>
        				        <span style='color:black' class='tip' title='"._("First").": $s_since ".Util::timezone($tz)."'>".$ago."</span>
        				      </a>
        				      <img src='/ossim/alarm/style/img/correlating.gif' class='img_cor tip' title='"._("This alarm is still being correlated and therefore it can not be modified")."'/>";
        				?>
        			</td>    				
        			<?php 
        		}						
        		?>
        		
                <td class="left" style="padding-left:10px"><?php echo $source_balloon?></td>
                
                <td class="left" style="padding-left:10px"><?php echo $dest_balloon?></td>
                
                <td><?php echo $status_link?></td>
        
                <td><?php echo $s_delete_link." ".$incident_link?></td>
            </tr>
            
            <tr><td class='hidden_row' colspan='10'></td></tr>
            <tr>
                <td class="noheight_row"></td>
                <td class="noheight_row" colspan='9' name='eventbox<?php echo $s_backlog_id."-".$s_event_id?>' id='eventbox<?php echo $s_backlog_id."-".$s_event_id?>'></td>
            </tr>
            <?php 
        } 
    }
    ?>
</table>
    
<?php

if (!empty($list) && $top < $num_rows) 
{
    ?>
    <div class='dt_footer'>
        <div class='t_paginate'>
            <a href="javascript:void(0)" class='av_l_main' onclick="toggle_group('<?=$group_id ?>','<?php echo $src_ip ?>','<?php echo $dst_ip ?>','', <?php echo $top ?>,'<?php echo intval(GET('similar'))?>');return false;"><?php echo _("NEXT >") ?>
            </a>
        </div>           
    </div>        
              
    <div id="<?php echo $group_id.$top?>"></div>        
    <?php 
}
    
            
$db->close();    
$geoloc->close();