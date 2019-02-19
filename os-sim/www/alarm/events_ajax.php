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


function get_first_number($from, $alarms_numbering, $event_id) 
{
	foreach ($alarms_numbering as $a_id => $pos) 
	{
		if ($a_id > $event_id)
		{
			$from--;
		}
	}
	
	return $from;
}

$geoloc = new Geolocation("/usr/share/geoip/GeoLiteCity.dat");

/*****************
Not the best place for such a definition, should come from db
*****************/
$default_asset = 2;

/****************/
$backlog_id = GET('backlog_id');
$event_id   = GET('event_id');
$show_all   = GET('show_all');
$hide       = GET('hide');
$from       = (GET('from') != "") ? GET('from') : 0;

ossim_valid($backlog_id, OSS_HEX, OSS_NULLABLE, 'illegal:' . _("backlog_id"));
ossim_valid($event_id, OSS_HEX, OSS_NULLABLE,   'illegal:' . _("Event_id"));
ossim_valid($show_all, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _("Show_all"));
ossim_valid($hide, OSS_ALPHA, OSS_NULLABLE,     'illegal:' . _("Hide"));
ossim_valid($from, OSS_DIGIT, OSS_NULLABLE,     'illegal:' . _("From"));

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
$db   = new ossim_db(TRUE);
$conn = $db->connect();


if (empty($show_all)) 
{
    $show_all = 0;
}


$master_alarm_sid = 0;
$plugin_sid_list  = array();

if (GET('box') == "1")
{ 
    ?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html>
    <head>
    	<title> <?php echo _("Control Panel")?> </title>
    	<meta http-equiv="Pragma" content="no-cache"/>
        <link rel="stylesheet" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>        
        <script type="text/javascript" src="../js/jquery.min.js"></script>
        <script type="text/javascript" src="../js/jquery-ui.min.js"></script>        
        
        <!-- JQuery tipTip: -->
        <script src="/ossim/js/jquery.tipTip-ajax.js" type="text/javascript"></script>
        <link rel="stylesheet" type="text/css" href="/ossim/style/tipTip.css"/>
        
        <style type='text/css'>
            .ajaxgreen
            {
                border: none;
                border-collapse: collapse;
                margin: 10px auto;
                width: 98%;
            }
            
            .ajaxgreen th
            {
                font-family: "MuliRegular","Lucida Sans","Lucida Grande",Lucida,sans-serif,Verdana;
                font-size: 12px;
            }
            
            .ajaxgreen #c_th_correlation
            {
                position: relative;
                padding: 0px 3px;
            }
            
            .ajaxgreen #c_th_correlation a, .ajaxgreen #c_th_correlation a:hover
            {
                text-decoration: none;                
            }
            
            .ajaxgreen #sort_asc
            {
                position: relative; 
                left: 0px;
                top: 1px;
            }
                
            .ajaxgreen #sort_desc
            {
                position: relative; 
                right: 0px;
                top: -7px;
            }
                    
            .t_white
            {
        		background: transparent;
        		border: none;
    		}
    		
    		.t_white td
    		{
        		color: white !important;
        		text-align: left;
    		}
            
        </style>
        
        <script type='text/javascript'>
            
            $(document).ready(function(){
                 
                $('.td_event_name').each(function(key, value) 
                {
                    var content = $(this).find('div').html();
                    
                    if (typeof(content) != 'undefined' && content != '' && content != null)
                    {                                                                   
                        $(this).tipTip({content: content, maxWidth:'300px'});                       
                    }    
                });
                
                $('.td_date').each(function(key, value) 
                {
                    var content = $(this).find('div').html();
                                                               
                    if (typeof(content) != 'undefined' && content != '' && content != null)
                    {                                                                   
                        $(this).tipTip({content: content, maxWidth:'300px'});                       
                    }    
                });
            });   
        </script>
        
        <?php include '../host_report_menu.php'; ?>
    </head>
    
    <body>  
    <?php
} 
?>
	
    <table class="table_list">
		<tr>
			<th>#</th>
			<th><?php echo _("Alarm");?></th>
			<th><?php echo _("Risk");?></th>
			<th><?php echo _("Date");?></th>
			<th><?php echo _("Source");?></th>
			<th><?php echo _("Destination");?></th>
			<?php 
			if (GET('box') == "1" && $show_all>0) 
			{ 
				$url_asc  = "events_ajax.php?backlog_id=$backlog_id&show_all=3&box=1&hide=$hide";
				$url_desc = "events_ajax.php?backlog_id=$backlog_id&show_all=2&box=1&hide=$hide";
				?>
				<th> 
				    <div id='c_th_correlation'>
                        <a href="<?php echo $url_desc ?>">                           
                            <img id='sort_desc' src="../pixmaps/data_tables/sort_desc.png" border="0" align="top"/>
                        </a>
                        <?php echo gettext("Correlation Level"); ?>
                        <a href="<?php echo $url_asc ?>">
                            <img id='sort_asc' src="../pixmaps/data_tables/sort_asc.png" border="0" align="top"/>                            
                        </a>
                    <div>
				</th>
				<?php 
			} 
			else 
			{ 
    			?>
    			<th> <?php echo gettext("Correlation Level"); ?>
    			<?php 
    		} 
    		?>
		</tr>

		<?php
		// Timezone correction
		$tz                              = Util::get_timezone();
		$alarms_numbering                = Alarm::get_alarms_numbering($conn, $backlog_id);
		list($alarm_list, $total_events) = Alarm::get_events($conn, $backlog_id, $show_all, $event_id, $from, $max_events, $alarms_numbering, true);

    if ($total_events > 0) 
    {
    	$first_number = ($event_id != "") ? (($from > 0) ? $from - 1 : $from) : get_first_number($from, $alarms_numbering, $alarm_list[0]->get_event_id());
    	if ($first_number<0) 
    	{
    	   $first_number=0;
    	}
    	
    	$count_events = $first_number;
        $count_jump   = 0;
        
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
            $src_host     = $alarm->get_src_host();
            $dst_host     = $alarm->get_dst_host();
            $src_net      = $alarm->get_src_net();
            $dst_net      = $alarm->get_dst_net();
            $rule_level   = $alarm->get_rule_level();
            $view         = Alarm::event_allowed($conn,$ctx,$src_host,$dst_host,$src_net,$dst_net);
                       
            if ($sid_name=="")
            {
                $sid_name = "Unknown (id=$id sid=$sid)";
            }
            
            if ($sid_priority=="")       
            {
                $sid_priority = "N/A";
            }
            
            if (!$show_all || $id==1505) 
            {
                $summary = Alarm::get_alarm_resume($conn, $backlog_id, $rule_level, true);
    		}
    		?>
    		
    		<tr>
    			<?php
    			if (!$master_alarm_sid) 
    			{
    			    $master_alarm_sid = $sid;
    			}
    			
    			$name = Util::translate_alarm($conn, $sid_name, $alarm);
    			$name = "<b>$name</b>";
    			?>
    			
    			<!-- id & name event -->
    			<td>
    				<?php
    				if ($id==1505) 
    				{
    				    echo "<b>" . $alarms_numbering[$aid] . "</b>";
    				    $count_jump++;
    				}
    				else 
    				{
    				    echo ++$count_events;
    				}
    				?>
    			</td>
    			
    			<td class='td_event_name' style="text-align:left">
    				<?php 
    				$asset_src = $alarm->get_asset_src();
    				$asset_dst = $alarm->get_asset_dst();
    				if ($view && $aid) 
    				{
    					$href = str_replace("//","/","$acid_link/" . $acid_prefix . "_qry_alert.php?noback=1&submit=%230-$aid");
    				
    					if ($show_all == 2 && ($id==1505)) 
    					{
    						$img = "../pixmaps/arrow-315-small.png";
    						echo "&nbsp;<img align='absmiddle' src='$img' border='0'/>";
    					} 
    					elseif (($aid == $event_id)) 
    					{					
    						$href = "events_ajax.php?backlog_id=$backlog_id&show_all=0&box=1&hide=directive";
    						$img  = "../pixmaps/arrow-315-small.png";
    						echo "&nbsp;<a href=\"$href\"><img align='absmiddle' src='$img' border='0'/></a>";
    					} 
    					elseif ($show_all == 0 || $id==1505) 
    					{
    						//page jump
    						$jump = (int)($summary["below"] / $max_events) * $max_events;
    						$href = "events_ajax.php?backlog_id=$backlog_id&show_all=1&event_id=$event_id&box=1&hide=directive&from=$jump";
    						$img  = "../pixmaps/plus-small.png";
    						
    						echo "&nbsp;<a href='$href' class='greybox' title='"._("Alarm detail")." ID$event_id'><img align='absmiddle' src='$img' border='0'/></a>";
    					}
    					
    					$href_sim = Util::get_acid_single_event_link($aid)."&minimal_view=1&noback=1";
    					?>
    						
    					<a class="greybox trlnka" id="<?php echo $id.";".$sid ?>" title="<?=_("Event detail")?>" href="<?php echo $href_sim?>"><?php echo $name ?></a>
    						
    					<?php 
    					if ($id==1505) 
    					{ 
        					?>
                            <div style='display: none;'>
                                <table class='t_white'>                           
                                    <tr>
                                        <td><?php echo _("SRC Asset")?>:</td>
                                        <td><?php echo $asset_src?></td>
                                    </tr>
                                    
                                    <tr>
                                        <td><?php echo _("DST Asset")?>:</td>
                                        <td><?php echo $asset_dst?></td>
                                    </tr>
                                    
                                    <tr>
                                        <td><?php echo _("Priority")?>:</td>
                                        <td><?php echo $sid_priority?></td>
                                    </tr>                            
                                </table>
                            </div>
                            <?php  
       					} 
    							
        			} 
        			else 
        			{
        				$href = "";
        				echo "<span style='color:gray'>$name</span>";
        			}
        			?>
    			
    			</td>
    			<!-- end id & name event -->
    			
    			<!-- risk -->
    			<?php
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

                        $risk_text = Util::get_risk_rext($risk,0);
                        echo '<td>';
                        if ($view && $href_sim) echo "<a href='$href_sim'>";
                        echo '<span class="risk-bar '.$risk_text.'">' . _($risk_text) . '</span>';
                        if ($view && $href_sim) echo "</a>";
                        echo '</td>';
    			?>
    			<!-- end risk -->
    
    			<td class='td_date' style='white-space:nowrap;'>
    				<?php 
    				if ($view) 
    				{
    					if ($event_date==$orig_date || $event_date==$date) 
    					{ 
    						?>
    						<a href="<?php echo Util::get_acid_date_link($date, $src_ip, "ip_src") ?>"><?php echo $date ?></a>
    						<?php
    					} 
    					else 
    					{ 
    						?>
    						<a href="<?php echo Util::get_acid_date_link($date, $src_ip, "ip_src") ?>"><?php echo $date?></a>			
    						
    						<div style='display: none;'>
                                <table class='t_white'>                           
                                    <tr>
                                        <td><?php echo _("Sensor date")?>:</td>
                                        <td><?php echo $event_date?></td>
                                    </tr>
                                    
                                    <tr>
                                        <td><?php echo _("Timezone")?>:</td>
                                        <td><?php echo Util::timezone($alarm->get_tzone())?></td>
                                    </tr>
                                </table>
                            </div>				
    						<?php 
    					} 
    				} 
    				else 
    				{
    					?>
    					<span style='color:gray'><?php echo $date?></span>
    					<?php	
    				}
    				?>
    			</td>
    
    			<?php
                $default_ctx = Session::get_default_ctx();
                $event_info  = $alarm->get_event_info();
                $src_host    = Asset_host::get_object($conn, $event_info["src_host"]);
                $dst_host    = Asset_host::get_object($conn, $event_info["dst_host"]);
                $src_net_id  = $event_info["src_net"];
                $dst_net_id  = $event_info["dst_net"];

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
                $src_output  = Asset_host::get_extended_name($conn, $geoloc, $src_ip, $ctx_src, $event_info["src_host"], $event_info["src_net"]);
                $homelan_src = $src_output['is_internal'];
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
                $dst_output  = Asset_host::get_extended_name($conn, $geoloc, $dst_ip, $ctx_dst, $event_info["dst_host"], $event_info["dst_net"]);
                $homelan_dst = $dst_output['is_internal'];
                $dst_img     = $dst_output['html_icon'];

                $src_link   = Menu::get_menu_url("../forensics/base_stat_ipaddr.php?clear_allcriteria=1&ip=$src_ip&noback=1", 'analysis', 'security_events', 'security_events');
                $dst_link   = Menu::get_menu_url("../forensics/base_stat_ipaddr.php?clear_allcriteria=1&ip=$dst_ip&noback=1", 'analysis', 'security_events', 'security_events'); 

    			?>
    			<!-- src & dst hosts -->
    			<td class="left" style='white-space:nowrap;padding-left:10px'>
    				<?php
    				if (!$view)
    				{
    					echo "<span style='color:gray'>X.X.X.X</span>";
    				}
    				elseif ($homelan_src)
    				{	
    					echo $src_img . " <a href=\"$src_link\" class=\"HostReportMenu\" id2=\"$src_ip;$dst_ip\" id=\"$src_ip;$src_name;".$event_info["src_host"]."\" ctx=\"$ctx\"><strong>$src_name$src_port</strong></a>";
    				}
    				else
    				{
    					echo $src_img . " <a href=\"$src_link\" class=\"HostReportMenu\" id2=\"$src_ip;$dst_ip\" id=\"$src_ip;$src_name;".$event_info["dst_host"]."\" ctx=\"$ctx\">$src_name$src_port</a>"; 
    				}
    				?>
    			</td>
    			
    			<td style='white-space:nowrap;padding-left:10px'>
    				<?php
    				if (!$view)
    				{
    					echo "<span style='color:gray'>X.X.X.X</span>";
    				}
    				elseif ($homelan_dst)
    				{	
    					echo $dst_img . " <a href=\"$dst_link\" class=\"HostReportMenu\" id2=\"$src_ip;$dst_ip\" id=\"$dst_ip;$dst_name;".$event_info["src_host"]."\" ctx=\"$ctx\"><strong>$dst_name$dst_port</strong></a>";
    				}
    				else
    				{
    					echo $dst_img . " <a href=\"$dst_link\" class=\"HostReportMenu\" id2=\"$src_ip;$dst_ip\" id=\"$dst_ip;$dst_name;".$event_info["dst_host"]."\" ctx=\"$ctx\">$dst_name$dst_port</a>"; 
    				}
    				?>
    			</td>
    			<!-- src & dst hosts -->
    
    			<td <?php echo (!$view) ? "style='color:gray'" : "" ?>><?php echo $alarm->get_rule_level() ?></td>
    		</tr>
    
    		<?php
            if ($highest_rule_level == 0) $highest_rule_level = $alarm->get_rule_level();
            
    		// Alarm summary
            if (!$show_all || $id==1505) 
    		{
                $summ_count        = $summary["count"];
                $summ_event_count += $summ_count;
                $summ_dst_ips      = $summary["dst_ips"];
                $summ_types        = $summary["types"];
                $summ_dst_ports    = $summary["dst_ports"];
                
    			echo "
                <tr>
                	<td colspan=\"9\" style='border-bottom:1px solid #BBBBBB;padding:3px' bgcolor='#E5FFDF'>
    					<b>" . gettext("Alarm Summary") . "</b> [ ";
    					printf(gettext("Total Events: %d") , $summ_count);
    					echo "&nbsp;-&nbsp;";
    					printf(gettext("Unique Dst IPAddr: %d") , $summ_dst_ips);
    					echo "&nbsp;-&nbsp;";
    					printf(gettext("Unique Types: %d") , $summ_types);
    					echo "&nbsp;-&nbsp;";
    					printf(gettext("Unique Dst Ports: %d") , $summ_dst_ports);
    					echo " ] ";
    			echo "</td>";
                /*
                echo "
                <tr>
                <td></td>
                <td colspan=\"3\" bgcolor=\"#eeeeee\">&nbsp;</td>
                <td colspan=\"5\">
                <table width=\"100%\">
                <tr>
                <th colspan=\"8\">Alarm summary</th>
                </tr>
                <tr>
                <td>Total Events: </td>
                <td>" . $summary["count"] . "</td>
                <td>Unique Dst IPAddr: </td>
                <td>" . $summary["dst_ips"] . "</td>
                <td>Unique Types: </td>
                <td>" . $summary["types"] . "</td>
                <td>Unique Dst Ports: </td>
                <td>" . $summary["dst_ports"] . "</td>
                </tr>
                </table>
                </td>
                <td bgcolor=\"#eeeeee\">&nbsp;</td>
                </tr>
                <tr><td colspan=\"10\"></td></tr>
                ";
                */
            }
    
        } /* foreach alarm_list */
    	
    	if ( $hide == "" ) 
    	{
    		?>
    		<tr>
    			<td colspan="6" bgcolor="#eeeeee" style="text-align:left;padding-left:5px">
    				<a href='events_ajax.php?backlog_id=<?=$backlog_id?>&show_all=2&box=1&hide=directive' class="greybox trlnka" id="<?php echo $id.";".$sid ?>" title="<?=_("Events detail")?>"><img src="../pixmaps/plus-small.png" align="absmiddle"><?php echo $summary["prevrl_count"] ?></a> <?php echo _("Total events matched after highest rule level, before timeout."); ?>
    			</td>
    			<td colspan="3" bgcolor="#eeeeee">
    				
    				<?php
    				    $d_url = Menu::get_menu_url("../directives/index.php?toggled_dir=$master_alarm_sid", 'configuration', 'threat_intelligence', 'directives');
    				?>				
    				
    				<a href="<?php echo $d_url?>"><strong><?php echo _("View")."/"._("Edit")?></strong> <?php echo _("current directive definition")?></a>
    			</td>
    		</tr>
    		<?php
    	}
    
    	if ($from + $max_events < $total_events || $from > 0) 
    	{ 
    		?>
    		<tr>
    			<td class="center no_hover" colspan="9">    
    					       
                    <div class='dt_footer'>
                        <div class='t_entries'>                         
                            <?php printf("</span>"._("Showing %d to %d events")."</span>", ($first_number + 1), ($count_events+$count_jump));?>                                             
                        </div>
				       
                        <div class='t_paginate'>
                        <?php 
                        if ($from > 0) 
                        { 
                            ?>
                            <a href="javascript:void(0);" class='av_l_main' onclick="document.location.href='events_ajax.php?backlog_id=<?php echo $backlog_id ?>&event_id=<?php echo $event_id ?>&show_all=<?php echo $show_all ?>&hide=<?php echo $hide ?>&from=<?php echo $from-$max_events ?>&box=1'">&lt; <?php echo _("Previous")?><a/>                                    
                            <?php 
                        }                                    
                        else
                		{                	
                			echo "<span>&lt; "._("Previous")."</span>";                			          			
                		}      
                		
                		echo "&nbsp;&nbsp;&nbsp;&nbsp;";                                      
                                                
                        if ($from + $max_events < $total_events) 
                        { 
                            ?>
                            <a href="javascript:void(0);" class='av_l_main' onclick="document.location.href='events_ajax.php?backlog_id=<?php echo $backlog_id ?>&event_id=<?php echo $event_id ?>&show_all=<?php echo $show_all ?>&hide=<?php echo $hide ?>&from=<?php echo $from+$max_events ?>&box=1'"><?php echo _("Next") ?> &gt;<a/>
                            <?php 
                        }
                        else
                		{
                			echo "<span>"._("Next")." &gt;</span>";                	
                		}  
                        ?>
				       </div>
                    </div> 					  
    			</td>
    		</tr> 
    		<?php
    	}

    }
    else 
    { /* If alarm_list */
    	?>
    	<tr>
    		<td colspan="9" class="left">
    		<a href='events_ajax.php?backlog_id=<?=$backlog_id?>&show_all=2&box=1&hide=directive' class="greybox" title="<?=_("Events detail")?>"><img src="../pixmaps/plus-small.png" align="absmiddle"></a> <?php echo _("No alarms found, toggle this to view all events."); ?>
    		</td>
    	</tr>
    	<?php 
    } 
    ?>
</table>
<?php

if (GET('box') == "1")
{
    ?>
    </body>
    
    </html>
    <?php
} 

$db->close();
$geoloc->close();
?>

