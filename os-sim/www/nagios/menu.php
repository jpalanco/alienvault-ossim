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

Session::logcheck('environment-menu', 'MonitorsAvailability');

$conf            = $GLOBALS['CONF'];
$nagios_default  = parse_url($conf->get_conf('nagios_link'));

$filters = array(
    'order_by' => 'priority desc'
);


$scheme         = isset($nagios_default['scheme']) ? $nagios_default['scheme'] : 'http';
$path           = isset($nagios_default['path']) ? $nagios_default['path'] : '/nagios3/';
$path           = str_replace('//', '/', $path);
$port           = isset($nagios_default['port']) ? ':' . $nagios_default['port'] : '';

$flag_login     = FALSE;

if ($path[0] != '/') 
{
    $path = '/' . $path;
}

$sensors_nagios[] = array(
    'id'       => '',
    'ip'       => '',
    'name'     => _('default'),
    'selected' => '0'
);

$nagios = $path;
?>
<div id='c_nagios'>
    <div id='nagios_opt_links' class='c_nagios_right'>
        <?php
        if ($opc == '')
        {
            ?>
              <a href="<?php echo "$nagios/cgi-bin/status.cgi?host=all"?>" target="nagios"><?php echo _('Service Detail')?></a> |
              <a href="<?php echo "$nagios/cgi-bin/status.cgi?hostgroup=all&style=hostdetail"?>" target="nagios"><?php echo _('Host Detail')?></a> |
              <a href="<?php echo "$nagios/cgi-bin/status.cgi?hostgroup=all"?>" target="nagios"><?php echo _('Status Overview')?></a> |
              <a href="<?php echo "$nagios/cgi-bin/status.cgi?hostgroup=all&style=grid"?>" target="nagios"><?php echo _('Status Grid')?></a> |
              <a href="<?php echo "$nagios/cgi-bin/statusmap.cgi?host=all"?>" target="nagios"><?php echo _('Status Map')?></a> |
              <a href="<?php echo "$nagios/cgi-bin/status.cgi?host=all&servicestatustypes=248"?>" target="nagios"><?php echo _('Service Problems')?></a> |
              <a href="<?php echo "$nagios/cgi-bin/status.cgi?hostgroup=all&style=hostdetail&hoststatustypes=12"?>" target="nagios"><?php echo _('Host Problems')?></a><br/>
              <a href="<?php echo "$nagios/cgi-bin/outages.cgi"?>" target="nagios"><?php echo _('Network Outages')?></a> |
              <a href="<?php echo "$nagios/cgi-bin/extinfo.cgi?&type=3"?>" target="nagios"><?php echo _('Comments')?></a> |
              <a href="<?php echo "$nagios/cgi-bin/extinfo.cgi?&type=6"?>" target="nagios"><?php echo _('Downtime')?></a> |
              <a href="<?php echo "$nagios/cgi-bin/extinfo.cgi?&type=0"?>" target="nagios"><?php echo _('Process Info')?></a> |
              <a href="<?php echo "$nagios/cgi-bin/extinfo.cgi?&type=4"?>" target="nagios"><?php echo _('Performance Info')?></a> |
              <a href="<?php echo "$nagios/cgi-bin/extinfo.cgi?&type=7"?>" target="nagios"><?php echo _('Scheduling Queue')?></a>
            <?php
        }

        if ($opc == 'reporting')
        {
            ?>
              <a href="<?php echo "$nagios/cgi-bin/trends.cgi"?>" target="nagios"><?php  echo _('Trends')?></a> |
              <a href="<?php echo "$nagios/cgi-bin/avail.cgi"?>"  target="nagios"><?php echo _('Availability')?></a> |
              <a href="<?php echo "$nagios/cgi-bin/histogram.cgi"?>" target="nagios"><?php echo _('Event Histogram')?></a> |
              <a href="<?php echo "$nagios/cgi-bin/history.cgi?host=all"?>" target="nagios"><?php echo _('Event History')?></a> |
              <a href="<?php echo "$nagios/cgi-bin/summary.cgi"?>" target="nagios"><?php echo _('Event Summary')?></a> |
              <a href="<?php echo "$nagios/cgi-bin/notifications.cgi?contact=all"?>" target="nagios"><?php echo _('Notifications')?></a> |
              <a href="<?php echo "$nagios/cgi-bin/showlog.cgi"?>" target="nagios"><?php echo _('Performance Info')?></a>
            <?php
        }

        ?>
    </div>

    <div class='clear_layer'></div>
</div>

<script type='text/javascript'>

    $(document).ready(function()
    {
        <?php
        $_nagios_path = ($nagios_link != '') ? $nagios_link : $nagios.'/cgi-bin/status.cgi?hostgroup=all';
        ?>

        var nagios_url = '<?php echo $_nagios_path;?>';
        var iframe = document.getElementById('nagios_fr');

        $(iframe).attr('src', nagios_url);
    });
</script>

