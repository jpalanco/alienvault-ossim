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

Session::useractive();

if ($step != 3)
{
    $msg = _('An error happened, the step is wrong. Try again later');

    include 'step_error.php';

    die();
}


$total = $wizard->get_step_data('scan_hosts');
$info  = $wizard->get_step_data('scan_info');

$s     = $info['server'];
$n     = $info['net'];
$t     = $info['time'];

?>

<!-- DUE TO COMPATIBILITY ISSUES THIS STYLE HAS TO BE HERE AND NOT IN THE STYLE SHEET. -->
<style>
.select2-search
{
    display:none !important;
}
</style>

<div class='wizard_title'>
    <?php echo _('Scanning') ?>
</div>

<div id='scan_notif'></div>

<div class='wizard_subtitle'>
<?php
    if ($total == 0)
    {
        echo _("The scan has completed. We couldn't find any asset within the selected networks");
    }
    else
    {
        echo _("The scan has completed. We found $n network devices and $s servers. The scan took $t to complete.");
    }
?>
</div>

<div class='wizard_subtitle'>
<?php
    echo _('We recommend scheduling this scan to repeat periodically to discover changes in the environment.');
?>
</div>

<div id='schedule_container' class='wizard_subtitle'>
    <?php echo _('Schedule to Perform Scan') ?><br/>

    <select id='scan_scheduler' class='scheduler_opts'>
        <option value=""></option>
        <option value="1"><?php echo _('Daily') ?></option>
        <option value="2" selected="selected"><?php echo _('Weekly') ?></option>
        <option value="3"><?php echo _('Monthly') ?></option>
    </select>

</div>


<div class='box_single_button'>
    <button id='finish_scan' ><?php echo _('OK') ?></button>
</div>



