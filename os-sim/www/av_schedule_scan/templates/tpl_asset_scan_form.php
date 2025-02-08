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

Session::logcheck('environment-menu', 'AlienVaultInventory');

session_write_close();


/****************************************************
********************* Tooltips  *********************
****************************************************/

$title = _('You can type one unique CIDR (x.x.x.x/xx) or a CIDR list separated by commas: CIDR1, CIDR2, CIDR3...');


/****************************************************
 ***************** Advanced Options *****************
 ****************************************************/

$scan_types = array(
    'ping'   => _('Ping'),
    'fast'   => _('Fast Scan'),
    'normal' => _('Normal'),
    'full'   => _('Full Scan'),
    'custom' => _('Custom')
);

$time_templates = array(
    'T0' => _('Paranoid'),
    'T1' => _('Sneaky'),
    'T2' => _('Polite'),
    'T3' => _('Normal'),
    'T4' => _('Aggressive'),
    'T5' => _('Insane')
);


/****************************************************
 ************** Default Configuration  **************
 ****************************************************/

$scan_type         = 'fast';
$ttemplate         = 'T3';
$scan_ports        = '';
$autodetected      = 1;
$rdns              = 1;
$privileged_mode = 1;


if($params != '')
{
    $ttemplate = $nmap_params['ttemplate'] ;
    $autodetected = $nmap_params['aggressive_scan'] ;
    $rdns = $nmap_params['rdns'] ;
    $scan_type = $nmap_params['stype'] ;
    $scan_ports = $nmap_params['custom_scan_ports'] ;
    $targets = $nmap_params['targets'];
    $privileged_mode = $nmap_params['privileged_mode'] ;
}
else
{
    $targets = '';
}

?>


<table id='t_avi'>

    <tr>
        <td class="left">
            <label for="task_name"><?php echo _('Name') . required();?></label>
        </td>
    </tr>
    <tr>
        <td class="left">
            <input type='text' name='task_name' id='task_name' class='vfield' value="<?php echo $name?>"/>
        </td>
    </tr>

    <tr>
        <td class="left">
            <label for="task_sensor"><?php echo _('Sensor') . required();?></label>
        </td>
    </tr>
    <tr>
        <td class="left">
            <select name="task_sensor" id="task_sensor" class='vfield'>
                <?php
                foreach ($sensors as $s_id => $s_data)
                {
                    $selected = ($s_id == $sensor_id) ? 'selected="selected"' : '';

                    echo "<option value='$s_id' $selected>".$s_data['name']."</option>";
                }
                ?>
            </select>
        </td>
    </tr>

    <tr>
        <td class="left">
            <label for="task_params"><?php echo _('Targets to scan') . required();?></label>
        </td>
    </tr>
    <tr>
        <td class="left">
            <div class="c_loading">
                <div class="r_loading"></div>
            </div>
            <input type='text' name='task_params' id='task_params' data-title="<?php echo $title?>" class='vfield info' value="<?php echo $targets?>"/>
        </td>
    </tr>




    <tr>
        <td class="left">
            <label for="advanced_options"><?php echo _('Advanced Options') . required();?></label>
        </td>
    </tr>
    <tr>
        <td class="left">
            <table class='t_adv_options'>

                <!-- Full scan -->
                <tr>
                    <td class='td_label'>
                        <label for="scan_type"><?php echo _('Scan type')?>:</label>
                    </td>
                    <td>
                        <select id="scan_type" name="scan_type" class="vfield">
                            <?php
                            foreach ($scan_types as $st_v => $st_txt)
                            {
                                $selected = ($scan_type == $st_v) ? 'selected="selected"' : '';

                                echo "<option value='$st_v' $selected>$st_txt</option>";
                            }
                            ?>
                        </select>
                        <span id="scan_type_info"><img class='img_help_info' src="/ossim/pixmaps/helptip_icon.gif"/></span>
                    </td>
                </tr>

                <!-- Specific ports -->
                <tr id='tr_cp'>
                    <td class='td_label'>
                        <label for="custom_ports"><?php echo _('Specify Ports')?>:</label>
                    </td>
                    <td>
                        <?php
                            $scan_ports = ($scan_ports == '') ? '1-65535' : $scan_ports;
                        ?>
                        <input class="greyfont vfield" type="text" id="custom_ports" name="custom_ports" value="<?php echo $scan_ports?>"/>
                    </td>
                </tr>

                <!-- Time template -->
                <tr>
                    <td class='td_label'>
                        <label for="timing_template"><?php echo _('Timing template')?>:</label>
                    </td>
                    <td>
                        <select id="timing_template" name="timing_template" class="nmap_select vfield">
                            <?php
                            foreach ($time_templates as $tt_v => $tt_txt)
                            {
                                $selected = ($ttemplate == $tt_v) ? 'selected="selected"' : '';

                                echo "<option value='$tt_v' $selected>$tt_txt</option>";
                            }
                            ?>
                        </select>
                        <span id="timing_template_info"><img class='img_help_info' src="/ossim/pixmaps/helptip_icon.gif"/></span>
                    </td>
                </tr>

                <tr>
                    <td colspan="2">
                        <?php $ad_checked = ($autodetected == TRUE) ? 'checked="checked"' : '';?>

                        <input type="checkbox" id="autodetect" name="autodetect" class='vfield' <?php echo $ad_checked?> value="1"/>
                        <label for="autodetect"><?php echo _('Autodetect services and Operating System')?></label>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <?php $rdns_checked = ($rdns == TRUE) ? 'checked="checked"' : '';?>

                        <input type="checkbox" id="rdns" name="rdns" class='vfield' <?php echo $rdns_checked?>  value="1"/>
                        <label for="rdns"><?php echo _('Enable reverse DNS Resolutions')?></label>
                    </td>
                </tr>

                <tr>
                    <td colspan="2">
                        <?php $privileged_mode_checked = ($privileged_mode == TRUE) ? 'checked="checked"' : '';?>

                        <input type="checkbox" id="privileged_mode" name="privileged_mode" class='vfield' <?php echo $privileged_mode_checked?>  value="1"/>
                        <label for="privileged_mode"><?php echo _('Privileged Mode')?></label>
                    </td>
                </tr>

            </table>
        </td>
    </tr>

    <tr>
        <td class="left">
            <label for="task_period"><?php echo _('Frequency') . required()?></label>
        </td>
    </tr>
    <tr>
        <td class="left">
            <select name="task_period" id="task_period" class='vfield'>
                <?php
                foreach ($frequencies as $f_seconds => $f_name)
                {
                    $selected = ($period == $f_seconds) ? 'selected="selected"' : '';

                    echo "<option value='$f_seconds' $selected>$f_name</option>";
                }
                ?>
            </select>
        </td>
    </tr>

</table>
