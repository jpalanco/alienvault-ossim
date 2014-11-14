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

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

require_once 'av_init.php';
require_once 'alarm_common.php';

Session::logcheck("analysis-menu", "ControlPanelAlarms");

$backlog = GET('backlog');
ossim_valid($backlog, OSS_HEX, OSS_NULLABLE, 'illegal:' . _("Backlog")); // Maybe nullable from Logger resolves
if (ossim_error()) {
    die(ossim_error());
}

$geoloc = new Geolocation("/usr/share/geoip/GeoLiteCity.dat");

$db      = new ossim_db(TRUE);
$conn    = $db->connect();
$tz      = Util::get_timezone();

list ($alarm,$stats,$event) = Alarm::get_alarm_detail($conn, $backlog);


$timestamp_utc = Util::get_utc_unixtime(Util::timestamp2date($alarm->get_timestamp()));
$last          = gmdate("Y-m-d H:i:s",$timestamp_utc+(3600*$tz));
$alarm_time    = get_alarm_life($alarm->get_since(), $alarm->get_last());

preg_match_all("/(\d+)\s(\w+)/", strip_tags(trim($alarm_time)),  $found);

$alarm_time_number = $found[1][0];
$alarm_time_unit   = $found[2][0];

$alarm_life    = get_alarm_life($alarm->get_since(), gmdate("Y-m-d H:i:s"));

preg_match_all("/(\d+)\s(\w+)/", strip_tags(trim($alarm_life)),  $found);

$alarm_life_number = $found[1][0];
$alarm_life_unit   = $found[2][0];

$show_total        = false;
$removable         = $alarm->get_removable();

$backlog_id        = $alarm->get_backlog_id();
$event_id          = $alarm->get_event_id();

/* Buttons */
$alarm_detail_url  = (empty($stats)) ? "load_alarm_detail('$event_id', 'event')" : "load_alarm_detail('$backlog_id', 'alarm')";

$alarm_close_url   = "tray_close('$backlog_id');";

$alarm_open_url    = "open_alarm('$backlog_id');";

$alarm_delete_url  = "tray_delete('$backlog_id');";

$alarm_labels_url  = "tray_labels('$backlog_id');";

/* Source Home */
$_home_src         = Asset_host::get_extended_name($conn, $geoloc, $alarm->get_src_ip(), $ctx, $event["_SRC_HOST"], $event["_SRC_NET"]);

/* Destination Home */
$_home_dst         = Asset_host::get_extended_name($conn, $geoloc, $alarm->get_dst_ip(), $ctx, $event["_DST_HOST"], $event["_DST_NET"]);

/* Detail */
$alarm_name  = Util::translate_alarm($conn, $alarm->get_sid_name(), $alarm, 'array');
if ($alarm_name["id"] != '')
{
    $alarm_image = (file_exists("/usr/share/ossim/www/alarm/style/img/".$alarm_name["id"].".png")) ? "<img src='style/img/".$alarm_name["id"].".png' border='0' title='".$alarm_name["kingdom"]."'>" : "";

	$alarm_title = $alarm_name["kingdom"] . ": <span style='font-size:15px'>" . $alarm_name["category"]. "</span>";
}
else
{
    $alarm_image = "";
	$alarm_title = $alarm_name['name'];
}
	
$promiscous_title = _(is_promiscous(count($stats['src']['ip']), count($stats['dst']['ip']), $_home_src['is_internal'], $_home_dst['is_internal']));

?>
<div id="tray_container">
    <div class="tray_triangle"></div>
    
    <table id="tray_table" class=''>
    <tr>
        <td style="width:60px">
            <!--<div>
                <?php echo (!$removable) ? '<img align="absmiddle" src="/ossim/alarm/style/img/correlating.gif" class="img_cor_d">' : '' ?>
            </div>-->
            <div>
                <?php echo $alarm_image; ?>
            </div>        
        </td>
        <td style="width:370px;" class="left tray_font_medium">
            <div>
                <?php echo $alarm_title ?>
            </div>
            <div class="tray_gray" style="margin-top:3px;font-size:15px">
                <?php echo _("Attack Pattern").": ".$promiscous_title ?>
            </div>
        </td>
        <td style="width:170px">
            <?php
            if (!empty($event['_SRCREPACTIVITY']) || !empty($event['_DSTREPACTIVITY']))
            {
            ?>
            <div class="padding" style="padding-bottom:10px"><?php echo _("OPEN & CLOSED ALARMS"); ?></div>
            <?php
            }
            else
            {
            ?>
            <div class="padding" style="padding-bottom:10px;padding-top:10px"><?php echo _("OPEN & CLOSED ALARMS"); ?></div>
            <?php
            }
            ?>
            <div class="tray_font_big second-div">
                <span id="sparktristatecols_<?php echo $backlog?>" style="margin:auto;" data-pid="<?php echo $alarm->get_plugin_id() ?>" data-sid="<?php echo $alarm->get_plugin_sid() ?>"></span>
            </div>
            <div class="padding-top">
            <?php
            if (!empty($event['_SRCREPACTIVITY']) || !empty($event['_DSTREPACTIVITY']))
            {
                $prefix = (!empty($event['_SRCREPACTIVITY'])) ? '_SRC' : '_DST';
                $_act   = $event[$prefix . "REPACTIVITY"];
                $_rel   = $event[$prefix . "REPRELIABILITY"];
                $_crit  = $event[$prefix . "CRITICALITY"];
    
                if(is_object($alarm))
                {
                    $_ip = ($prefix == '_SRC')? $alarm->get_src_ip() : $alarm->get_dst_ip();
                }
    
                $rep_src_icon = Reputation::getrepimg($_crit,$_rel,$_act,"");
            ?>
                <div class="tray_gray left" data-ip="<?php echo $_ip ?>">
                    <?php echo $rep_src_icon." "._("IP Activity").":" ?></b>
                </div>
                <div class="left" style="padding-left:25px;font-weight:bold">
                    <?php echo str_replace(";","<br>",$_act) ?></b>
                </div>
            <?php
            }
            else {
                echo "&nbsp;";
            }
            ?>
            </div>
        </td>
        <td style="width:150px;">
            <div class="padding right-border left-border"><?php echo _("TOTAL EVENTS"); ?></div>
            <div class="tray_font_big right-border left-border second-div"><?php echo $stats['events'] ?></div>
            <div class="padding-top right-border left-border"><?php echo $last ?></div>
        </td>
        <td style="width:90px;">
            <div class="padding right-border"><?php echo _("DURATION"); ?></div>
            <div class="tray_font_big right-border second-div"><?php echo $alarm_time_number ?></div>
            <div class="padding-top right-border"><?php echo  strtoupper($alarm_time_unit) ?></div>
        </td>
        <td style="width:95px;">
            <div class="padding"><?php echo _("ELAPSED TIME"); ?></div>
            <div class="tray_font_big second-div"><?php echo $alarm_life_number ?></div>
            <div class="padding-top"><?php echo  strtoupper($alarm_life_unit) ?></div>
        </td>
        <td style="width:120px;">
            <div class="padding-right">
                <input type="button" class="testbutton" value="<?php echo _("View Details")?>" onclick="<?php echo $alarm_detail_url ?>">
            </div>
            <?php 
            if (!$removable)
            {
            ?>
            <div class="padding-right padding-top">
                <input type="button" class="button_dissabled testbutton av_b_secondary" value="<?php echo _("Close")?>" disabled="disabled">
            </div>
            <div class="padding-right padding-top">
                <input type="button" class="button_dissabled testbutton av_b_secondary" value="<?php echo _("Delete")?>" disabled="disabled">
            </div>
             <div class="padding-right padding-top" style="position:relative">
                <input type="button" class="button_dissabled testbutton av_b_secondary" value="<?php echo _("Apply Label") ?>" disabled="disabled">
            </div>
            <?php
            }
            else
            {
                if ($alarm->get_status() == 'open')
                {
                ?>
                    <div class="padding-right padding-top">
                        <input type="button" class="testbutton av_b_secondary" value="<?php echo _("Close")?>" onclick="<?php echo $alarm_close_url ?>">
                    </div>
                <?php  
                }
                else
                {
                ?>
                    <div class="padding-right padding-top">
                        <input type="button" class="testbutton av_b_secondary" value="<?php echo _("Open")?>" onclick="<?php echo $alarm_open_url ?>">
                    </div>
                <?php      
                }
            ?>
            
            <div class="padding-right padding-top">
                <input type="button" class="testbutton av_b_secondary" value="<?php echo _("Delete")?>" onclick="<?php echo $alarm_delete_url ?>">
            </div>
            <div class="padding-right padding-top" style="position:relative">
                <input type="button" class="testbutton av_b_secondary" value="<?php echo _("Apply Label")." &#x25BC;"?>" onclick="<?php echo $alarm_labels_url ?>">
                <div id="tags_content_<?php echo $backlog_id ?>" class='apply_label_layer' style="position:absolute;z-index:99999;left:2px;top:32px"></div>
            </div> 
            <?php            
            }
            ?>
        </td>
    </tr>
    </table>
</div>
<?php

$db->close();
$geoloc->close();
