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
require_once 'classes/DateDiff.inc';

Session::logcheck('configuration-menu', 'PolicySensors');


$plugin_id = GET('pid');
$ip        = GET('sensor');

ossim_valid($ip,         OSS_IP_ADDR, 'illegal:' . _('Sensor IP'));
ossim_valid($plugin_id,  OSS_DIGIT,   'illegal:' . _('Plugin name'));

if (ossim_error())
{
    die(ossim_error());
}

/* connect to db */
$conf           = $GLOBALS['CONF'];
$acid_link      = $conf->get_conf('acid_link');
$acid_prefix    = $conf->get_conf('event_viewer');
$acid_main_link = str_replace('//', '/', $conf->get_conf('acid_link').'/'.$acid_prefix.'_qry_main.php?clear_allcriteria=1&search=1&bsf=Query+DB&ossim_risk_a=+');


$db         = new ossim_db();
$conn_snort = $db->snort_connect();
$events     = Plugin::get_latest_SIM_Event_by_plugin_id($conn_snort, $plugin_id, $ip);
?>
<table class="transparent" style="width:100%;">
    <?php
    if (count($events) == 0)
    {
        ?>
        <tr>
            <td><strong><?php echo _('No events found')?></strong></td>
        </tr>
        <?php
    }
    else
    {
        ?>
        <tr>
            <th>&nbsp;</th>
            <th><?php echo _('Device')?></th>
            <th><?php echo _('Date')?></th>
            <th><?php echo _('Last Security Event')?></th>
        </tr>
        <?php
    }

    foreach ($events as $event)
    {
        $sensor = ($event['sensor_name'] != '') ? $event['ip'].' ['.$event['sensor_name'].']' : '-';
        $ago    = TimeAgo(strtotime($event['event_date']), time());
        ?>
        <tr class="trc" txt="<?php echo strtotime($event['event_date'])?>">
            <td class="small nobborder center" width="16px">
                <img src="" border="0"/>
            </td>

            <td class="small nobborder">
                <b><?php echo $sensor?></b>&nbsp;
            </td>

            <td class="small nobborder center">
                <?php echo $event['event_date']?>&nbsp;&nbsp;(<?php echo $ago?>)
            </td>

            <td class="small nobborder">
                <a href="<?php echo Menu::get_menu_url($acid_main_link."&plugin=".urlencode($plugin_id), 'analysis', 'security_events', 'security_events');?>"><b><?php echo ($event['sig_name'] != '') ? $event['sig_name'] : '-'?></b></a>
            </td>
        </tr>
        <?php
    }
    ?>
</table>

<?php $db->close(); ?>
