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
include_once 'nfsen_functions.php';

Session::logcheck('configuration-menu', 'PolicySensors');


function draw_nmap_form ($action = 'new', $display = FALSE, $data = array())
{
    $stype           = ($data['stype'] != '')                       ? $data['stype'] : 'normal';
    $scan_ports      = ($data['scan_ports'] != '')                  ? $data['scan_ports'] : '';
    $ttemplate       = ($data['ttemplate'] != '')                   ? $data['ttemplate'] : 'T3';
    $aggressive_scan = (array_key_exists('aggressive_scan', $data)) ? $data['aggressive_scan'] : TRUE;
    $rdns            = (array_key_exists('rdns', $data))            ? $data['rdns'] : TRUE;

    $show_form = ($display == FALSE) ? "style='display:none'" : '';
    $form_id   = $action.'_nmap_options';
    ?>

    <tr <?php echo $show_form?> id="<?php echo $form_id?>">
        <th><?php echo _("Advanced Options") ?></th>

        <td class="left">
            <table width="450" class="transparent">
                <!-- Full scan -->
                <tr>
                    <td colspan="2" style="padding:2px 0px 0px 0px">

                        <div style='float:left;text-align:left;'>
                            <div style="width:90px;float:left;"><?php echo _('Scan type')?>:&nbsp;</div>
                            <div style="float:right;">
                                <select name="scan_type" class="nmap_select vfield" onchange="change_scan_type(this.value, '<?php echo $action ?>')">
                                    <option <?php echo (($stype == "ping")   ? "selected='selected'" : '' )?> value="ping"><?php echo _("Ping")?></option>
                                    <option <?php echo (($stype == "normal") ? "selected='selected'" : '' )?> value="normal"><?php echo _("Normal")?></option>
                                    <option <?php echo (($stype == "fast")   ? "selected='selected'" : '' )?> value="fast"><?php echo _("Fast Scan")?></option>
                                    <option <?php echo (($stype == "full")   ? "selected='selected'" : '' )?> value="full"><?php echo _("Full Scan")?></option>
                                    <option <?php echo (($stype == "custom") ? "selected='selected'" : '' )?> value="custom"><?php echo _("Custom")?></option>
                                </select>
                            </div>
                        </div>

                        <div style='float:right;width:250px;text-align:left;' class='div_small'>
                            <span id="<?php echo $action ?>_full_mode" <?php echo (($stype == "full") ? '' : 'style="display:none"') ?>>
                                <strong><?php echo _("Full mode")?></strong> <?php echo _("will be much slower but will include OS, services, service versions and MAC address into the inventory")?>
                            </span>

                            <span id="<?php echo $action ?>_fast_mode" <?php echo (($stype == "fast") ? '' : 'style="display:none"') ?>>
                                <strong><?php echo _("Fast mode")?></strong> <?php echo _("will scan fewer ports than the default scan")?>
                            </span>
                        </div>

                        <div class='custom_ports <?php echo $action ?>_div_custom' <?php echo (($scan_ports == '') ? "style='display:none'" : '')?>>
                            <div style="width:90px;float:left"><?php echo _("Specify Ports")?>:</div>

                            <div style="float:left">
                                <input class="greyfont vfield" type="text" id="<?php echo $action ?>_custom_ports" name="custom_ports" value="<?php echo (($scan_ports == '') ? "1-65535" : $scan_ports)?>">
                            </div>
                        </div>
                    </td>
                </tr>

                <!-- timing template (T0-5) -->
                <tr>
                    <td colspan="2" style="padding:6px 0px 0px 0px">
                        <div style='float:left;text-align:left;'>
                            <div style="width:90px;float:left">
                                <?php echo _("Timing template")?>:&nbsp;
                            </div>
                            <div style="float:left">
                                <select name="timing_template" class="nmap_select vfield" onchange="change_timing_template(this.value,'<?php echo $action ?>')">
                                    <option <?php echo (($ttemplate == "T0") ? "selected='selected'" : '' )?> value="T0"><?php echo _("Paranoid")?></option>
                                    <option <?php echo (($ttemplate == "T1") ? "selected='selected'" : '')?> value="T1"><?php echo _("Sneaky")?></option>
                                    <option <?php echo (($ttemplate == "T2") ? "selected='selected'" : '')?> value="T2"><?php echo _("Polite")?></option>
                                    <option <?php echo (($ttemplate == "T3") ? "selected='selected'" : '')?> value="T3"><?php echo _("Normal")?></option>
                                    <option <?php echo (($ttemplate == "T4") ? "selected='selected'" : '' )?> value="T4"><?php echo _("Aggressive")?></option>
                                    <option <?php echo (($ttemplate == "T5") ? "selected='selected'" : '')?> value="T5"><?php echo _("Insane")?></option>
                                </select>
                            </div>
                        </div>
                        <div class='div_small' style='float:right;text-align:left;width:250px;'>
                            <span id="<?php echo $action ?>_paranoid" <?php echo (($ttemplate == "T0") ? '' : 'style="display:none"') ?>><strong><?php echo _("Paranoid")?></strong> <?php echo _("mode is for IDS evasion")?></span>
                            <span id="<?php echo $action ?>_sneaky" <?php echo (($ttemplate == "T1") ? '' : 'style="display:none"') ?>><strong><?php echo _("Sneaky")?></strong> <?php echo _("mode is for IDS evasion")?></span>
                            <span id="<?php echo $action ?>_polite" <?php echo (($ttemplate == "T2") ? '' : 'style="display:none"') ?>><strong><?php echo _("Polite")?></strong> <?php echo _("mode slows down the scan to use less bandwidth and target machine resources")?></span>
                            <span id="<?php echo $action ?>_aggressive" <?php echo (($ttemplate == "T4") ? '' : 'style="display:none"') ?>><strong><?php echo _("Aggressive")?></strong> <?php echo _("mode speed up the scan (fast and reliable networks)")?></span>
                            <span id="<?php echo $action ?>_insane" <?php echo (($ttemplate == "T5") ? '' : 'style="display:none"') ?>><strong><?php echo _("Insane")?></strong> <?php echo _("mode speed up the scan (fast and reliable networks)")?></span>
                        </div>
                    </td>
                </tr>
                <!-- end timing template -->

                <!-- timing template (T0-5) -->
                <tr>
                    <td colspan="2" style='text-align:left;padding:5px 0px 0px 0px'>
                        <input type="checkbox" name="autodetect" value="1"  <?php echo (($aggressive_scan) ? "checked='checked'" : '') ?> class='vfield'/> <?php echo _("Autodetect services and Operating System") ?>
                        <br /><input type="checkbox" name="rdns" value="1"  <?php echo (($rdns) ? "checked='checked'" : '') ?> class='vfield'/> <?php echo _("Enable reverse DNS Resolution") ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
<?php
}


$sensor_id        = GET('sensor_id');
$submit           = GET('submit');
$update           = GET('update');
$has_nagios       = intval(GET('has_nagios'));
$has_ntop         = intval(GET('has_ntop'));
$has_vuln_scanner = intval(GET('has_vuln_scanner'));
$has_kismet       = intval(GET('has_kismet'));
$vuln_user        = GET('vuln_user');
$vuln_pass        = GET('vuln_pass');
$vuln_port        = GET('vuln_port');
$vuln_max_scans   = GET('vuln_max_scans');
$nagios_user      = GET('nagios_user');
$nagios_pass      = GET('nagios_pass');
$task_id          = GET('task_id');
$task_name        = GET('task_name');
$task_period      = GET('task_period');
$task_type        = GET('task_type');
$task_params      = GET('task_params');

if($task_type == 5)
{
    $task_params  = str_replace(' ', '', $task_params);
    $task_params  = str_replace(' ','', $task_params);
    $task_params  = str_replace("\n", ' ', $task_params);
    $task_params  = str_replace(',', ' ', $task_params);

    $task_nets    = $task_params;
}

ossim_valid($submit,         OSS_ALPHA, OSS_SPACE, OSS_SLASH, OSS_NULLABLE, 'illegal:' . _('Submit action'));
ossim_valid($update,         OSS_ALPHA, OSS_SPACE, OSS_SLASH, OSS_NULLABLE, 'illegal:' . _('Update action'));
ossim_valid($sensor_id,      OSS_HEX,                                       'illegal:' . _('Sensor ID'));
ossim_valid($vuln_user,      OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_NULLABLE,  'illegal:' . _('Vuln User'));
ossim_valid($vuln_pass,      OSS_PASSWORD, OSS_NULLABLE,                    'illegal:' . _('Vuln Password'));
ossim_valid($vuln_port,      OSS_DIGIT, OSS_NULLABLE,                       'illegal:' . _('Vuln port'));
ossim_valid($vuln_max_scans, OSS_DIGIT, OSS_NULLABLE,                       'illegal:' . _('Vuln Max Simultaneous Scans'));
ossim_valid($nagios_user,    OSS_ALPHA, OSS_PUNC, OSS_SPACE, OSS_NULLABLE,  'illegal:' . _('Availability Monitoring User'));
ossim_valid($nagios_pass,    OSS_PASSWORD, OSS_NULLABLE,                    'illegal:' . _('Availability Monitoring Password'));
ossim_valid($task_id,        OSS_DIGIT, OSS_NULLABLE,                       'illegal:' . _('Task ID'));
ossim_valid($task_name,      OSS_ALPHA, OSS_SPACE, OSS_NULLABLE, OSS_SCORE, 'illegal:' . _('Task Name'));
ossim_valid($task_type,      OSS_DIGIT, OSS_NULLABLE,                       'illegal:' . _('Task Type'));
ossim_valid($task_period,    OSS_DIGIT, OSS_NULLABLE,                       'illegal:' . _('Task Period'));

if (ossim_error())
{
    die(ossim_error());
}

$db            = new ossim_db();
$conn          = $db->connect();

$nfsen_sensors = get_nfsen_sensors();
$sensor_obj = Av_sensor::get_object($conn, $sensor_id);

$nfsen_id      = $sensor_obj->get_nfsen_channel_id($conn);

$base_port     = ($nfsen_sensors[$nfsen_id] != '') ? $nfsen_sensors[$nfsen_id]['port']  : get_nfsen_baseport($nfsen_sensors);
$base_type     = ($nfsen_sensors[$nfsen_id] != '') ? $nfsen_sensors[$nfsen_id]['type']  : 'netflow';
$base_color    = ($nfsen_sensors[$nfsen_id] != '') ? $nfsen_sensors[$nfsen_id]['color'] : '#0000ff';

$frequency_arr = array(
    'Hourly'  => 3600,
    'Daily'   => 86400,
    'Weekly'  => 604800,
    'Monthly' => 2419200
);



// Execute actions

if (!empty($submit))
{
    if ($submit == _('New Task') || $submit == _('Save Task') || $submit == _('Enable / Disable'))
    {
        if ($task_type == 5)
        {
             // Nmap
            $nmap_options     = array();

            $scan_type        = GET('scan_type');
            $timing_template  = GET('timing_template');
            $custom_ports     = GET('custom_ports');
            $rdns             = (GET('rdns') == '1') ? 1 : 0;
            $autodetect       = (GET('autodetect') == '1') ? 1 : 0;

            $nmap_options[]   = '-'.$timing_template;

            // Append Autodetect
            if ($autodetect)
            {
                $nmap_options[] = "-A";
            }
            // Append RDNS
            if (!$rdns)
            {
                $nmap_options[] = "-n";
            }

            if ($scan_type == "fast")
            {
                $nmap_options[] = "-sL -sn -PE";
            }
            elseif ($scan_type == "custom")
            {
                $nmap_options[] = "-sS -p $custom_ports";
            }
            elseif ($scan_type == "normal")
            {
                $nmap_options[] = "-sS";
            }
            elseif ($scan_type == "full")
            {
                $nmap_options[] = "-sS -p 1-65535";
            }
            else
            {
                $nmap_options[] = "-sn -PE";
            }
        }
        else if ($task_type == 4)
        {
            //WMI
            preg_match("/wmipass:(.*)/", $task_params, $found);

            if ($found[1] != '' && preg_match("/^\*+$/", $found[1]) && $_SESSION['wmi_pass'.$task_id] != '')
            {
                $task_params = preg_replace("/wmipass:(.*)/", '', $task_params);
                $task_params = $task_params . "wmipass:" . $_SESSION["wmi_pass".$task_id];
            }
        }
    }

    if ($submit == _('New Task'))
    {
        // 3  OCS
        // 4  WMI
        // 5  NMAP

        $params_condition_1 = ($task_type == 3) ? TRUE : FALSE;
        $params_condition_2 = (($task_type == 4 || $task_type == 5) && $task_params != '') ? TRUE : FALSE;

        if ($params_condition_1 || $params_condition_2)
        {
           $check_param = TRUE;
        }
        else
        {
           $check_param = FALSE;
        }

        if ($sensor_id != '' && $task_name != '' && $task_type != '' && $task_period != '' && $check_param == TRUE)
        {
            // Validate parameters for each type case
            // NMAP: Network
            
            if ($task_type == 5)
            {
                ossim_valid($task_params,      OSS_IP_CIDR,                                        'illegal:' . _('Task Network CIDR'));
                ossim_valid($scan_type,        OSS_ALPHA, OSS_SCORE, OSS_NULLABLE,                 'illegal:' . _('Scan type'));
                ossim_valid($timing_template,  OSS_TIMING_TEMPLATE,                                'illegal:' . _('Timing template'));
                ossim_valid($custom_ports,     OSS_DIGIT, OSS_SPACE, OSS_SCORE, OSS_NULLABLE,      'illegal:' . _('Custom Ports'));
                ossim_valid($rdns,             OSS_DIGIT, OSS_NULLABLE,                            'illegal:' . _('Reverse DNS resolution option'));
                ossim_valid($autodetect,       OSS_DIGIT, OSS_NULLABLE,                            'illegal:' . _('Autodetect services and OS option'));

                if(is_array($nmap_options) && count($nmap_options) > 0)
                {
                    $task_params = Util::nmap_with_excludes(explode(" ",$task_params),$nmap_options);
                }
            // ELSE: Text
            }
            else if ($task_type == 4)
            {
                ossim_valid($task_params, OSS_ALPHA, ';', ':', '\.', '\*', 'illegal:' . _('Task Params'));
            }
            else
            {
                ossim_valid($task_params, OSS_NULLABLE, 'illegal:' . _('Task Params'));
            }

            if (ossim_error() || !Inventory::insert($conn, $sensor_id, $task_name, $task_type, $task_period, $task_params, $task_nets))
            {
                $config_nt['options']['type'] = 'nf_error';
                $config_nt['content']         = _('Error! Inventory task could not be inserted.  Some of mandatory fields are not correct');
            }
            else
            {
                $config_nt['options']['type'] = 'nf_success';
                $config_nt['content']         = _('Inventory task inserted successfully');
            }
        }
        else
        {
            $config_nt['options']['type'] = 'nf_error';
            $config_nt['content'] = _("Error: Cannot insert a new inventory task. Some of mandatory fields are not correct");
        }
    }
    elseif ($submit == _('Delete Task'))
    {
        if (!Inventory::delete($conn, $task_id))
        {
            $config_nt['options']['type'] = 'nf_error';
            $config_nt['content']         = _('Error! Inventory task could not be deleted');
        }
        else
        {
            $config_nt['options']['type'] = 'nf_success';
            $config_nt['content']         = _('Inventory task deleted successfully');
        }
    }
    elseif ($submit == _('Save Task'))
    {
        if(is_array($nmap_options) && count($nmap_options) > 0)
        {
            $task_params = Utill::nmap_with_excludes(explode(" ",$task_params),$nmap_options);
        }

        // Clean $task_params for OCS tasks

        if ($task_type == 3)
        {
            $task_params = '';
        }

        if (!Inventory::modify($conn, $task_id, $sensor_id, $task_name, $task_type, $task_period, $task_params, $task_nets))
        {
            $config_nt['options']['type'] = 'nf_error';
            $config_nt['content']         = _('Error! Inventory task could not be updated');
        }
        else
        {
            $config_nt['options']['type'] = 'nf_success';
            $config_nt['content']         = _('Inventory task updated successfully');
        }
    }
    elseif ($submit == _('Enable / Disable'))
    {

        if (!Inventory::toggle_scan($conn, $task_id))
        {
            $config_nt['options']['type'] = 'nf_error';
            $config_nt['content']         = _('Error! Inventory task could not be updated');
        }
        else
        {
            $config_nt['options']['type'] = 'nf_success';
            $config_nt['content']         = _('Inventory task updated successfully');
        }
    }
}


if(!empty($update))
{
    $properties = $sensor_obj->get_properties();

    $properties['has_nagios']       = $has_nagios;
    $properties['has_ntop']         = $has_ntop;
    $properties['has_vuln_scanner'] = $has_vuln_scanner;
    $properties['has_kismet']       = $has_kismet;

    $sensor_obj->set_properties($properties);

    $sensor_obj->save_in_db($conn);

    //Update vulnerabilities data
    $vuln_max_scans = ($vuln_max_scans > 5) ? 5 : $vuln_max_scans;

    $v_data = array(
        'name'           => $sensor_obj->get_name(),
        'user'           => $vuln_user,
        'password'       => $vuln_pass,
        'port'           => $vuln_port,
        'vuln_max_scans' => $vuln_max_scans,
        'enabled'        => 1
    );

    $sensor_obj->save_vs_credentials($conn, $v_data);


    //Update Nagios data
    $n_data = array(
        'user'     => $nagios_user,
        'password' => $nagios_pass
    );


    $sensor_obj->save_nagios_credentials($conn, $n_data);


    $sensor_obj->save_in_db($conn);
}




// Check if asset properties can be modified
$can_i_modify_elem = TRUE;

$external_ctxs = Session::get_external_ctxs($conn);

$sensor_ctxs = $sensor_obj->get_ctx();

foreach ($sensor_ctxs as $e_id => $e_name)
{
    if (!empty($external_ctxs[$e_id]))
    {
        $can_i_modify_elem = FALSE;
    }
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title> <?php echo _("OSSIM Framework"); ?> </title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" CONTENT="no-cache"/>

    <?php
    //CSS Files
    $_files = array();

    $_files[] = array('src' => 'av_common.css',       'def_path' => TRUE);
    $_files[] = array('src' => 'os_report.css',       'def_path' => TRUE);
    $_files[] = array('src' => 'colorpicker.css',     'def_path' => TRUE);
    $_files[] = array('src' => 'jslider.css',         'def_path' => TRUE);

    Util::print_include_files($_files, 'css');


    //JS Files
    $_files = array();

    $_files[] = array('src' => 'jquery.min.js',                             'def_path' => TRUE);
    $_files[] = array('src' => 'jquery-ui.min.js',                          'def_path' => TRUE);
    $_files[] = array('src' => 'greybox.js',                                'def_path' => TRUE);
    $_files[] = array('src' => 'jquery.colorpicker.js',                     'def_path' => TRUE);
    $_files[] = array('src' => 'jquery.elastic.source.js',                  'def_path' => TRUE);
    $_files[] = array('src' => 'autoHeight.js',                             'def_path' => TRUE);
    $_files[] = array('src' => 'utils.js',                                  'def_path' => TRUE);
    $_files[] = array('src' => 'notification.js',                           'def_path' => TRUE);

    $_files[] = array('src' => 'jslider/jshashtable-2.1_src.js',            'def_path' => TRUE);
    $_files[] = array('src' => 'jslider/jquery.numberformatter-1.2.3.js',   'def_path' => TRUE);
    $_files[] = array('src' => 'jslider/tmpl.js',                           'def_path' => TRUE);
    $_files[] = array('src' => 'jslider/jquery.dependClass-0.1.js',         'def_path' => TRUE);
    $_files[] = array('src' => 'jslider/draggable-0.1.js',                  'def_path' => TRUE);
    $_files[] = array('src' => 'jslider/jquery.slider.js',                  'def_path' => TRUE);

    Util::print_include_files($_files, 'js');

    ?>

    <script type="text/javascript">

        function toggle_vuln_scanner_options()
        {
            $("#vuln_scanner_option").slideToggle("slow");
        }


        function toggle_nagios_options()
        {
            $("#nagios_option").slideToggle("slow");
        }


        function netflow_notification(msg, type, fade, cancel)
        {
            show_notification('netflow_notification', msg, type, fade, cancel, "width: 100%;text-align:center;");
        }


        function hide_notification()
        {
            $('#netflow_notification div').fadeOut(500, function()
            {
                $('#netflow_notification').empty();
            });
        }


        function colorize_flows(active)
        {
            if(active)
            {
                set_flow_action("del");

                $('#netflow_hdr').html("<?php echo "<span class='bold' style='color:green'>"._('is running')."</span>"?>");
                $('#nfsen_port').attr('disabled', 'disabled').css('color', 'gray');
                $('#nfsen_type').attr('disabled', 'disabled').css('color', 'gray');
                $(".color_label").css("color", "gray");

                $('#backgroundTitle1').addClass('colorpicker_disabled').css('cursor', 'default');

                remove_color_picker();
            }
            else
            {
                set_flow_action("conf");

                $('#netflow_hdr').html("<?php echo "<span class='bold' style='color:red'>"._("is not configured")."</span>"?>");
                $('#nfsen_port').removeAttr('disabled').css('color', 'black');
                $('#nfsen_type').removeAttr('disabled').css('color', 'black');
                $(".color_label").css("color", "black");

                $('#backgroundTitle1').removeClass('colorpicker_disabled').css('cursor', 'pointer');;

                color_picker();
            }
        }


        function color_picker()
        {
            $('#backgroundTitle1').ColorPicker(
            {
                color: '<?php echo $base_color?>',
                onShow: function (colpkr)
                {
                    $(colpkr).fadeIn(500);

                    return false;
                },
                onHide: function (colpkr)
                {
                    $(colpkr).fadeOut(500);

                    return false;
                },
                onChange: function (hsb, hex, rgb)
                {
                    $('#backgroundTitle1 div').css('backgroundColor', '#' + hex);
                    $('#backgroundTitle1 div input').attr('value','#' + hex);
                }
            });
        }


        function remove_color_picker()
        {
            $('.colorpicker').remove();
            $('#backgroundTitle1').data('colorpickerId', '').off();
        }


        function set_flow_action(action)
        {
            if(action == "del")
            {
                $('#netflow_button').val("<?php echo _('Stop and Remove') ?>");
                $('#netflow_button').off('click');
                $('#netflow_button').on('click', function()
                {
                    del_nfsen();
                });
            }
            else if(action == "conf")
            {
                $('#netflow_button').val("<?php echo _('Configure and Run') ?>");
                $('#netflow_button').off('click');
                $('#netflow_button').on('click', function()
                {
                    nfsen_config();
                });
            }
            else if(action == "rest")
            {
                $('#netflow_button').val("<?php echo _('Restart NetFlow') ?>");
                $('#netflow_button').off('click');
                $('#netflow_button').on('click', function()
                {
                    nfsen_restart();
                });
            }

            $('#netflow_button').prop('disabled', false);

            return false;
        }


        function nfsen_config()
        {
            var msg_confirm  = '<?php echo Util::js_entities(_('Sensor will be configured as a flow collector.'))?>' + "<br/><br/>";
                msg_confirm += '<?php echo Util::js_entities(_('In order to apply these settings, we will need to reconfigure the Netflow service. Do you want to continue?'))?>';

            var keys         = {"yes": "<?php echo _('Yes') ?>", "no": "<?php echo _('No') ?>"};


            av_confirm(msg_confirm, keys).done(function()
            {

                var port  = $('#nfsen_port').val();
                var type  = $('#nfsen_type').val();
                var color = $('#backgroundTitle').val();
                    color = color.replace('#', '');

                var status_info = $('#netflow_hdr').html();

                $.ajax(
                {
                    type: "POST",
                    url: "nfsen_config.php",
                    data: {
                        "sensor_id" : "<?php echo $nfsen_id?>",
                        "action"    : "configure",
                        "port"      : port,
                        "type"      : type,
                        "color"     : color
                    },
                    dataType: 'json',
                    beforeSend: function()
                    {
                        if ($('#s_box').length > 0)
                        {
                            $('#s_box .r_lp').html("<?php echo _('Configuring sensor as a flow collector ...')?>")
                        }
                        else
                        {
                            show_loading_box('container_si', "<?php echo _('Configuring sensor as a flow collector ...')?>", '');
                        }

                        $('#netflow_button').off('click').prop('disabled', true);
                    },
                    error: function(data)
                    {
                        //Check expired session
                        var session = new Session(data, '');

                        if (session.check_session_expired() == true)
                        {
                            session.redirect();
                            return;
                        }

                        hide_loading_box();

                        colorize_flows(false);
                    },
                    success: function(data)
                    {
                        var cnd_1 = (typeof(data) == 'undefined' || data == null);
                        var cnd_2 = (typeof(data) != 'undefined' && data != null && data.status != 'success');

                        if (cnd_1 || cnd_2)
                        {
                            var error_msg = (cnd_1 == true) ? '<?php echo _('Sorry, operation was not completed due to an error when processing the request')?>' : data.data

                            netflow_notification(error_msg, 'nf_error', 10000, true);

                            $('#netflow_hdr').html(status_info);

                            hide_loading_box();
                        }
                        else
                        {
                            nfsen_reconfig("del");

                            colorize_flows(true);
                        }
                    }
                });
            });
        }


        function del_nfsen()
        {
            var status_info = $('#netflow_hdr').html();

            var msg_confirm  = '<?php echo Util::js_entities(_('Sensor will be removed as a flow collector.'))?>' + "<br/><br/>";
                msg_confirm += '<?php echo Util::js_entities(_('In order to apply these settings, we will need to reconfigure the Netflow service. Do you want to continue?'))?>';

            var keys         = {"yes": "<?php echo _('Yes') ?>", "no": "<?php echo _('No') ?>"};


            av_confirm(msg_confirm, keys).done(function()
            {
                $.ajax(
                {
                    type: "POST",
                    url: "nfsen_config.php",
                    data:
                    {
                        "sensor_id" : "<?php echo $nfsen_id?>",
                        "action"    : "delete"
                    },
                    dataType: 'json',
                    beforeSend: function()
                    {
                        if ($('#s_box').length > 0)
                        {
                            $('#s_box .r_lp').html("<?php echo _('Removing sensor as a flow collector ...')?>")
                        }
                        else
                        {
                            show_loading_box('container_si', "<?php echo _('Removing sensor as a flow collector ...')?>", '');
                        }

                        $('#netflow_button').off('click').prop('disabled', true);
                    },
                    error: function(data)
                    {
                        //Check expired session
                        var session = new Session(data, '');

                        if (session.check_session_expired() == true)
                        {
                            session.redirect();
                            return;
                        }

                        hide_loading_box();

                        colorize_flows(true);
                    },
                    success: function(data)
                    {
                        var cnd_1 = (typeof(data) == 'undefined' || data == null);
                        var cnd_2 = (typeof(data) != 'undefined' && data != null && data.status != 'success');

                        if (cnd_1 || cnd_2)
                        {
                            $('#netflow_hdr').html(status_info);

                            hide_loading_box();

                            var error_msg = (cnd_1 == true) ? '<?php echo _('Sorry, operation was not completed due to an error when processing the request')?>' : data.data
                            netflow_notification(error_msg, 'nf_error', 10000, true);
                        }
                        else
                        {
                            nfsen_reconfig("conf");

                            colorize_flows(false);
                        }
                    }
                });
            });
        }


        function nfsen_restart()
        {
            $.ajax(
            {
                type: "POST",
                url: "nfsen_config.php",
                data: {"action": "restart"},
                dataType: 'json',
                beforeSend: function()
                {
                    if ($('#s_box').length > 0)
                    {
                        $('#s_box .r_lp').html("<?php echo _('Restarting Netflow ...')?>")
                    }
                    else
                    {
                        show_loading_box('container_si', "<?php echo _('Restarting Netflow ...')?>", '');
                    }

                    $('#netflow_button').off('click').prop('disabled', true);
                },
                error: function(data)
                {
                    //Check expired session
                    var session = new Session(data, '');

                    if (session.check_session_expired() == true)
                    {
                        session.redirect();
                        return;
                    }

                    hide_loading_box();
                },
                success: function(data)
                {
                    hide_loading_box();

                    var cnd_1 = (typeof(data) == 'undefined' || data == null);
                    var cnd_2 = (typeof(data) != 'undefined' && data != null && data.status != 'success');

                    if (cnd_1 || cnd_2)
                    {
                        var error_msg = (cnd_1 == true) ? '<?php echo _('Sorry, operation was not completed due to an error when processing the request')?>' : data.data

                        netflow_notification(error_msg, 'nf_error', 10000, true);
                    }
                    else
                    {
                        netflow_notification(data.data, 'nf_success', 10000, true);
                    }

                    colorize_flows(true);
                }
            });
        }


        function nfsen_reconfig(act)
        {
            $.ajax(
            {
                type: "POST",
                url: "nfsen_config.php",
                data: {
                    "action" : "reconfig"
                },
                dataType: 'json',
                beforeSend: function()
                {
                    $('#netflow_button').off('click').prop('disabled', true);

                    if ($('#s_box').length > 0)
                    {
                        $('#s_box .r_lp').html("<?php echo _('Executing Netflow reconfig...')?>")
                    }
                    else
                    {
                        show_loading_box('container_si', "<?php echo _('Executing Netflow reconfig...')?>", '');
                    }
                },
                error: function()
                {
                    hide_loading_box();

                    netflow_notification("<?php echo _('Impossible to apply Netflow Configuration')?>", 'nf_error', 0, true);

                    set_flow_action(act);
                },
                success: function(data)
                {
                    var cnd_1 = (typeof(data) == 'undefined' || data == null);
                    var cnd_2 = (typeof(data) != 'undefined' && data != null && data.status == 'error');

                    if (cnd_1 || cnd_2)
                    {
                        netflow_notification("<?php echo _('Impossible to apply Netflow Configuration')?>", 'nf_error', 0, true);
                    }

                    hide_loading_box();

                    set_flow_action(act);
                }
            });
        }

        function is_running()
        {
            $.ajax({
                type: "POST",
                url: "nfsen_config.php",
                data: {
                    "sensor_id" : "<?php echo $nfsen_id?>",
                    "action" : "nfsen_status"
                },
                dataType: 'json',
                success: function(data)
                {
                    colorize_flows(true);

                    if (typeof(data) != 'undefined' && data != null && data.status == 'success')
                    {
                        if (data.data.match(/is running/))
                        {
                            $('#netflow_hdr').html("<?php echo "<span class='bold' style='color:green'>"._('is running')."</span>"?>");
                        }
                        else
                        {
                            if (data.data != '')
                            {
                                netflow_notification(data.data, 'nf_warning', 7000, true);
                            }

                            $('#netflow_hdr').html("<?php echo "<span class='bold' style='color:red'>"._('is not running')."</span>"?>");

                            set_flow_action("rest");
                        }
                    }
                }
            });
        }


        function test_nagios()
        {
            var user = $('#nagios_user').val();
            var pass = $('#nagios_pass').val();

            $.ajax(
            {
                type: "POST",
                url: "test_nagios.php",
                data: "",
                data: {
                    "sensor_id" : "<?php echo $sensor_id?>",
                    "user"      : user,
                    "pass"      : pass
                },
                beforeSend: function()
                {
                    $('#test_nagios_result').html("<img src='../pixmaps/loading.gif' width='15'/>");
                },
                error: function(data)
                {
                    //Check expired session
                    var session = new Session(data, '');

                    if (session.check_session_expired() == true)
                    {
                        session.redirect();
                        return;
                    }

                    $('#test_nagios_result').html('');
                },
                success: function(msg)
                {
                    $('#test_nagios_result').html(msg);
                }
            });
        }


        function change_scan_type(val, form)
        {
            if (val == "custom")
            {
                $('.'+form+'_div_custom').show();
            }
            else
            {
                $('.'+form+'_div_custom').hide();
                $('#'+form+'_custom_ports').val('1-65535');
            }

            $('#'+form+'_fast_mode').hide();
            $('#'+form+'_full_mode').hide();

            if(val == 'fast')
            {
                $('#'+form+'_fast_mode').show();
            }

            if(val == 'full')
            {
                $('#'+form+'_full_mode').show();
            }
        }


        function change_task_type(val, form)
        {
            if (val == "5")
            {
                $('#'+form+'_nmap_options').show();
            }
            else
            {
                $('#'+form+'_nmap_options').hide();
            }

            if (val == "3")
            {
                $('#'+form+'_params').hide();
            }
            else
            {
                $('#'+form+'_params').show();
            }
        }

        // This function is used for task creation and modification

        function change_timing_template(val, form)
        {
            $('#'+form+'_paranoid').hide();
            $('#'+form+'_sneaky').hide();
            $('#'+form+'_polite').hide();
            $('#'+form+'_aggressive').hide();
            $('#'+form+'_insane').hide();

            if(val == 'T0')
            {
                $('#'+form+'_paranoid').show();
            }
            if(val == 'T1')
            {
                $('#'+form+'_sneaky').show();
            }
            if(val == 'T2')
            {
                $('#'+form+'_polite').show();
            }
            if(val == 'T4')
            {
                $('#'+form+'_aggressive').show();
            }
            if(val == 'T5')
            {
                $('#'+form+'_insane').show();
            }
        }

        $(document).ready(function()
        {
            $('#sensor_f').before('<img id="loading_si" src="<?php echo AV_PIXMAPS_DIR?>/loading.gif" alt="<?php echo _("Loading")?>..."/>');

            $('#sensor_f').on('load', function(){

                try
                {
                    var width_si =  $('#sensor_f').contents().find('#sensor_container').width();
                    var width_so =  $('#table_so').width();

                    var width = (width_si>width_so) ? width_si : width_so;

                    $('#sensor_c').contents().find('#sensor_container').css('width', width);
                    $('#table_so').css('width', width);
                }
                catch(err)
                {

                }

                $('#loading_si').remove();
                $('#sensor_f').show();

                if (!top.is_lightbox_loaded(window.name))
                {
                    $('#sensor_f').contents().find('.c_back_button').off();
                    $('#sensor_f').contents().find('.c_back_button').click(function(){

                        var url = '<?php echo Menu::get_menu_url("/ossim/sensor/sensor.php", "configuration", "deployment", "components", "sensors");?>';
                        top.frames["main"].document.location.href = url;
                        return false;
                    })

                    $('#sensor_f').contents().find('.c_back_button').show();
                }
            });


            /***************************************************
            ********************* Services *********************
            *****************************************************/

            $('#has_nagios').click(function(){
                toggle_nagios_options();
            });

            $('#has_vuln_scanner').click(function(){
                toggle_vuln_scanner_options();
            });

            $("#msscans").slider({
                from: 1,
                to: 5,
                limits: false,
                step: 1,
                dimension: '',
                skin: "blue"
            });

            $("a.greybox").click(function()
            {
                var t = this.title || $(this).text() || this.href;
                GB_show(t,this.href,400,'90%');
                return false;
            });


            /***************************************************
            ********************** NFSEN ***********************
            *****************************************************/

            <?php
            if ($nfsen_sensors[$nfsen_id] != '')
            {
                ?>
                is_running();
                <?php
            }
            else
            {
                ?>
                colorize_flows(false);
                <?php
            }
            ?>

            $('textarea').elastic();
        });

    </script>


    <style type='text/css'>

        body
        {
            margin-bottom: 10px;
        }

        input[type='text'], input[type='password'], select, textarea
        {
           width: 90%;
           height: 18px;
        }

        textarea
        {
           height: 45px;
        }

        label
        {
           border: none;
           cursor: default;
        }

        .sec_title
        {
            padding-top: 10px;
            padding-bottom: 0px;
        }

        div.bold
        {
           line-height: 18px;
        }

        .custom_ports
        {
            text-align:left;
            float:left;
            clear:both;
            padding-top:10px;
            padding-bottom:5px
        }

        .nmap_select
        {
            width: 90px;
        }

        #table_is
        {
           margin: auto;
           width: 100%;
           border: none;
        }

        #table_so
        {
           margin: auto;
           border: none;
        }

        #c_sensor_f
        {
            min-height: 230px;
        }

        #sensor_f
        {
            display: none;
        }

        #loading_si
        {
            position: relative;
            margin: auto;
            top: 110px;
        }

        .colorpicker_disabled
        {
            filter: alpha(opacity=10);
            -moz-opacity: 0.1;
            -khtml-opacity: 0.1;
            opacity: 0.1;
        }

        #netflow_notification
        {
            text-align: center;
            width:90%;
            margin:0 auto;
        }

        #nfsen_type
        {
            width: 80px;
        }
    </style>
</head>

<body>

    <div id='container_si'>

        <table id='table_is'>
            <tr>
                <td id='td_is' class="center noborder">
                    <div id='c_sensor_f'>
                        <iframe src="newsensorform.php?id=<?php echo $sensor_id?>" scrolling="auto" id='sensor_f' class='autoHeight' width="100%" frameborder="0"></iframe>
                    </div>
                </td>
            </tr>
        </table>

        <?php
        if($can_i_modify_elem)
        {
            ?>
            <table id='table_so'>
                <?php
                if (!empty($config_nt['content']))
                {
                    $config_nt = array(
                        'content' => $config_nt['content'],
                        'options' => array (
                            'type'          => $config_nt['options']['type'],
                            'cancel_button' => FALSE
                        ),
                        'style'   => 'width: 100%; margin: auto; text-align:center;'
                    );

                    $nt = new Notification('nt_1', $config_nt);

                    ?>
                    <tr><td class='noborder center' style="padding:0px 0px 9px 0px;"><?php echo $nt->show();?></td></tr>
                    <?php
                }
                ?>


                <!-- Inventory Tasks -->
                <tr>
                    <td class="sec_title"><?php echo _("Inventory Task")?></td>
                </tr>

                <tr>
                    <td class="noborder" valign="top">
                        <table align="center" width="100%">
                            <?php

                            $task_count = 0;
                            $task_list  = Inventory::get_list($conn, $sensor_id);

                            if (is_array($task_list) && !empty($task_list))
                            {
                                foreach ($task_list as $task)
                                {
                                    ?>
                                    <form method="GET" action="interfaces.php">
                                        <input type="hidden" name="sensor_id" value="<?php echo $sensor_id;?>"/>
                                        <input type="hidden" name="task_id" value="<?php echo $task['task_id'];?>"/>

                                        <tr>
                                            <th><?php echo _('Name');?></th>
                                            <th><?php echo _('Task Type');?></th>
                                            <th><?php echo _('Frequency');?></th>
                                            <th><?php echo _('Action');?></th>
                                        </tr>

                                        <tr>
                                            <td class="noborder">
                                                <input type="text" name="task_name" value="<?php echo $task['task_name']?>"/>
                                            </td>

                                            <td class="noborder">

                                                <?php
                                                $task_types = array(
                                                    '4'  => _('WMI Scan'),
                                                    '5'  => _('Asset Discovery Scan')
                                                );
                                                ?>

                                                <select name="task_type" onchange="change_task_type(this.value, 'update<?php echo $task_count;?>')">
                                                    <?php
                                                    foreach ($task_types as $tt_key => $tt_value)
                                                    {
                                                        $selected = ($task['task_type'] == $tt_key) ? 'selected="selected"' : '';
                                                        ?>
                                                        <option value="<?php echo $tt_key?>" <?php echo $selected?>><?php echo $tt_value?></option>
                                                        <?php
                                                    }
                                                    ?>
                                                </select>
                                            </td>

                                            <td class="noborder">
                                                <select name="task_period" id="task_period" style="width:90px">
                                                    <?php
                                                    foreach ($frequency_arr as $fname => $fseconds)
                                                    {
                                                        ?>
                                                        <option value="<?php echo $fseconds?>" <?php if ($task['task_period'] == $fseconds) echo "selected='selected'"?>><?php echo $fname?></option>
                                                        <?php
                                                    }
                                                    ?>
                                                </select>
                                            </td>

                                            <td class="center noborder">
                                                <table class="transparent">
                                                    <tr>
                                                        <td class="noborder">
                                                            <input type="submit" name="submit" class="av_b_secondary small" value="<?php echo _('Delete Task')?>"/>
                                                        </td>

                                                        <td class="noborder">
                                                            <input type="submit" name="submit" class="av_b_secondary small" value="<?php echo _('Enable / Disable')?>" id="toggle_task_<?php echo $task['task_id']?>"/>
                                                        </td>

                                                        <td class="noborder">
                                                            <input type="submit" name="submit" class="av_b_secondary small" value="<?php echo _('Save Task')?>"/>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <?php
                                        $param_style = ($task['task_type'] == 3) ? 'style="display:none;"' : '';
                                        ?>
                                        <tr id="update<?php echo $task_count;?>_params" <?php echo $param_style; ?>>
                                            <td class="noborder" colspan="5" style="padding:0px">
                                                <table class="transparent" width="100%">
                                                    <?php
                                                    if ($task['task_type'] == 5)
                                                    {
                                                        list($tp,$nmap_params) = Util::nmap_without_excludes($task['task_params']);
                                                        $task['task_params'] = implode(", ",$tp);
                                                    }
                                                    elseif ($task['task_type'] == 4)
                                                    {
                                                       preg_match("/wmipass:(.*)/", $task['task_params'], $found);

                                                       if ($found[1] != '')
                                                       {
                                                           $task['task_params']                    = preg_replace("/wmipass:(.*)/", "", $task['task_params']);
                                                           $_SESSION["wmi_pass".$task['task_id']]  = $found[1];
                                                           $task['task_params']                    = $task['task_params'] . "wmipass:" . preg_replace("/./", "*", $found[1]);
                                                       }
                                                    }
                                                    ?>

                                                    <tr>
                                                        <td colspan="2" class="noborder" style="padding-right:6px">
                                                            <textarea name="task_params" style="width:100%"><?php echo $task['task_params']?></textarea>
                                                        </td>
                                                    </tr>
                                                    <?php

                                                    if ($task['task_type'] == 5)
                                                    {
                                                        // Default values

                                                        $ttemplate        = 'T3';
                                                        $aggressive_scan  = TRUE;
                                                        $rdns             = TRUE;
                                                        $stype            = 'normal';
                                                        $scan_ports       = '';

                                                        if($nmap_params != '')
                                                        {
                                                            // Get timing template
                                                            preg_match("/\-(T[0-5])/", $nmap_params, $found);

                                                            $ttemplate = ($found[1] != '') ? $found[1]: '';

                                                            // Aggresive scan
                                                            preg_match("/\s(\-A)\s/", $nmap_params, $found);

                                                            $aggressive_scan = ($found[1] != '') ? TRUE : FALSE;

                                                            // Reverse DNS resolution
                                                            preg_match("/\s(\-n)\s/", $nmap_params, $found);

                                                            $rdns = ($found[1] != '') ? FALSE : TRUE;

                                                            // Scan type

                                                            if(preg_match("/-sS -F/", $nmap_params))
                                                            {
                                                                $stype = "fast";
                                                            }
                                                            elseif (preg_match("/\-sS \-p 1\-65535/", $nmap_params))
                                                            {
                                                                $stype =  "full";
                                                            }
                                                            elseif (preg_match("/\-sS \-p (\d+\-\d+)/", $nmap_params, $found))
                                                            {
                                                                $stype      = "custom";
                                                                $scan_ports = $found[1];
                                                            }
                                                            elseif (preg_match("/\-sS/", $nmap_params))
                                                            {
                                                                $stype = "normal";
                                                            }
                                                            else
                                                            {
                                                                $stype = "ping";
                                                            }
                                                        }

                                                        $nmap_draw_options = array(
                                                            'ttemplate'       => $ttemplate,
                                                            'aggressive_scan' => $aggressive_scan,
                                                            'rdns'            => $rdns,
                                                            'stype'           => $stype,
                                                            'scan_ports'      => $scan_ports
                                                        );

                                                        draw_nmap_form('update'.$task_count, TRUE, $nmap_draw_options);
                                                    }
                                                    else
                                                    {
                                                        draw_nmap_form('update'.$task_count, FALSE);
                                                    }
                                                    ?>
                                                </table>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="noborder" colspan="5" style="padding-left:4px">
                                                <strong><?php echo _('Status')?>:</strong>
                                                <?php
                                                if ($task['task_enable'])
                                                {
                                                    echo _('This task is enabled');
                                                    ?>
                                                    <a href ='javascript:void(0)' onclick="$('#toggle_task_<?php echo $task['task_id']?>').trigger('click');return false">
                                                        <img align='absmiddle' src='../pixmaps/tables/tick.png'/>
                                                    </a>
                                                    <?php
                                                }
                                                else
                                                {
                                                    echo _('This task is disabled');
                                                    ?>
                                                    <a href ='javascript:void(0)' onclick="$('#toggle_task_<?php echo $task['task_id']?>').trigger('click');return false">
                                                        <img align='absmiddle' src='../pixmaps/tables/cross.png'/>
                                                    </a>
                                                    <?php
                                                }
                                                ?>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td class="noborder" colspan="5"><hr/></td>
                                        </tr>
                                    </form>
                                    <?php

                                    $task_count++;
                                }
                            }
                            ?>

                            <form method="GET" action="interfaces.php">
                                <input type="hidden" name="sensor_id" value="<?php echo $sensor_id; ?>"/>
                                <tr>
                                    <th><?php echo _('Name');?></th>
                                    <th><?php echo _('Task Type');?></th>
                                    <th><?php echo _('Frequency');?></th>
                                    <th><?php echo _('Action');?></th>
                                </tr>
                                <tr>
                                    <td class="noborder"><input type="text" name="task_name"/></td>
                                    <td class="noborder">
                                        <select name="task_type" onchange="change_task_type(this.value, 'new')">
                                            <option value="5"><?php echo _("Asset Discovery Scan") ?></option>
                                            <option value="4"><?php echo _("WMI Scan") ?></option>
                                        </select>
                                    </td>
                                    <td class="noborder">
                                        <select name="task_period" id="task_period" style="width:90px">
                                            <?php
                                            foreach ($frequency_arr as $fname => $fseconds)
                                            {
                                                ?>
                                                <option value="<?php echo $fseconds?>" <?php if ($task['task_period'] == $fseconds) echo "selected='selected'"?>><?php echo $fname?></option>
                                                <?php
                                            }
                                            ?>
                                        </select>
                                    </td>
                                    <td class="center noborder">
                                        <input type="submit" class="av_b_secondary small" name="submit" value="<?php echo _('New Task')?>"/>
                                    </td>
                                </tr>
                                <tr id="new_params" style="display:none">
                                    <td class="noborder" colspan="5">
                                        <table class="transparent" width="100%">
                                            <tr><th colspan="2"><?php echo _("Parameters") ?></th></tr>
                                            <tr><td colspan="2" class="noborder" style="padding-right:6px"><textarea name="task_params" style="width:100%"></textarea></td></tr>
                                            <?php
                                                draw_nmap_form("new", FALSE);
                                            ?>
                                        </table>
                                    </td>
                                </tr>
                            </form>
                        </table>
                    </td>
                </tr>


                <!-- Services -->
                <tr>
                    <td class="sec_title"><?php echo _('Services')?></td>
                </tr>

                <tr>
                    <td class="noborder">
                        <form method="GET" action="interfaces.php" name="finterfaces">
                            <input type="hidden" name="sensor_id" value="<?php echo $sensor_id; ?>">

                            <table align="center" width="100%">
                                <tr>
                                    <th style="white-space: nowrap;"><?php echo _("Availability Monitoring"); ?></th>
                                    <th style="white-space: nowrap;"><?php echo _("Vulnerability Assessment"); ?> </th>
                                    <th style="white-space: nowrap;"><?php echo _("Wireless IDS"); ?></th>
                                    <th><?php echo _("Action"); ?></th>
                                </tr>

                                <tr>
                                    <?php
                                    $properties = $sensor_obj->get_properties();

                                    $chk_nagios = ($properties['has_nagios'] == '1')       ? ' checked="checked"' : '';
                                    $chk_vulns  = ($properties['has_vuln_scanner'] == '1') ? ' checked="checked"' : '';
                                    $chk_ntop   = ($properties['has_ntop'] == '1')         ? ' checked="checked"' : '';
                                    $chk_kismet = ($properties['has_kismet'] == '1')       ? ' checked="checked"' : '';

                                    // Force disable Nagios option for remote OSSIM sensors
                                    $usm_sensor = (empty($properties['version'])) ? FALSE : TRUE;

                                    try
                                    {
                                        $local_system_id = Util::get_system_uuid();
                                        $system_ids      = Av_center::get_component_id_by_system($conn, $local_system_id, 'sensor');
                                        $local_sensor_id = $system_ids['non-canonical'];
                                    }
                                    catch(Exception $e)
                                    {
                                        $local_sensor_id = NULL;
                                    }

                                    if ($usm_sensor == TRUE && $sensor_id != $local_sensor_id)
                                    {
                                        $chk_nagios               .= ' disabled';
                                        $properties['has_nagios']  = '0';
                                    }
                                    ?>

                                    <td class="center noborder">
                                        <input type="checkbox" id="has_nagios" name="has_nagios" value="1"<?php echo $chk_nagios?>/>
                                        <input type="hidden" name="has_ntop" value="1"<?php echo $chk_ntop?>/>
                                    </td>

                                    <td class="center noborder">
                                        <input type="checkbox" id="has_vuln_scanner" name="has_vuln_scanner" value="1"<?php echo $chk_vulns?>/>
                                    </td>

                                    <td class="center noborder">
                                        <input type="checkbox" name="has_kismet" value="1"<?php echo $chk_kismet?>/>
                                    </td>

                                    <td class="center noborder">
                                        <input type="submit" name="update" class="av_b_secondary small" value="<?php echo _('Update')?>"/>
                                    </td>
                                </tr>

                                <?php
                                $show_vulns_options = ($properties['has_vuln_scanner'] == '1') ? '' : 'style="display:none;"';

                                $vuln_scanner_options              = $sensor_obj->get_vs_credentials($conn);
                                $vuln_scanner_options['password']  = Util::fake_pass($vuln_scanner_options['password']);
                                $vuln_scanner_options['max_scans'] = ($vuln_scanner_options['max_scans'] != '') ? $vuln_scanner_options['max_scans'] : '5';
                                ?>

                                <tr>
                                    <td colspan="5" style="text-align:center;" class="noborder">
                                        <div id="vuln_scanner_option" <?php echo $show_vulns_options?>>
                                            <table width="65%" align='center' style='margin:10px auto;'>
                                                <tr>
                                                    <th><?php echo _('Vulnerability Assessment Options')?></th>
                                                </tr>

                                                <tr>
                                                    <td class="noborder">
                                                        <table width="95%" class="noborder" cellspacing="8">
                                                            <tr>
                                                                <td class="noborder" style="text-align:right;padding-right:10px;">
                                                                <?php echo _('User');?>:
                                                                </td>

                                                                <td class="noborder">
                                                                    <input type="text" name="vuln_user" value="<?php echo $vuln_scanner_options['user']?>"/>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="noborder" style="text-align:right;padding-right:10px;">
                                                                    <?php echo _('Password');?>:
                                                                </td>

                                                                <td class="noborder">
                                                                    <input type="password" name="vuln_pass" value="<?php echo $vuln_scanner_options['password']?>" autocomplete="off"/>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="noborder" style="text-align:right;padding-right:10px;">
                                                                    <?php echo _('Port');?>:
                                                                </td>

                                                                <td class="noborder">
                                                                    <input type="text" name="vuln_port" value="<?php echo $vuln_scanner_options['port']?>"/>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="noborder" style="text-align:right;padding-right:10px;">
                                                                    <?php echo _('Max Simultaneous Scans');?>:
                                                                </td>

                                                                <td class="noborder">
                                                                    <span style="display: inline-block; width: 100px; padding: 0 5px;">
                                                                        <input type="slider" id="msscans" name="vuln_max_scans" value="<?php echo $vuln_scanner_options['max_scans']?>"/>
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </td>
                                </tr>

                                <?php
                                $show_nagios_options = ($properties['has_nagios'] == '1') ? '' : 'style="display:none;"';
                                $nagios_options      = $sensor_obj->get_nagios_credentials($conn);

                                $nagios_options['password'] = Util::fake_pass($nagios_options['password']);
                                ?>

                                <tr>
                                    <td colspan="5" style="text-align:center;" class="noborder">
                                        <div id="nagios_option" <?php echo $show_nagios_options?>>
                                            <table width="65%" align='center' style='margin:10px auto;'>
                                                <tr>
                                                    <th><?php echo _('Availability Monitoring Options')?></th>
                                                </tr>

                                                <tr>
                                                    <td class="noborder">
                                                        <table width="95%" class="noborder" cellspacing="8">
                                                            <tr>
                                                                <td class="noborder" style="text-align:right;padding-right:10px;">
                                                                    <?php echo _('User');?>:
                                                                </td>
                                                                <td class="noborder">
                                                                    <input type="text" id="nagios_user" name="nagios_user" value="<?php echo $nagios_options['user']?>"/>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="noborder" style="text-align:right;padding-right:10px;">
                                                                    <?php echo _('Password');?>:
                                                                </td>

                                                                <td class="noborder">
                                                                    <input type="password" id="nagios_pass" name="nagios_pass" value="<?php echo $nagios_options['password']?>" autocomplete="off"/>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="noborder left">
                                                                    <input type="button" class="small av_b_secondary" onclick="test_nagios()" value="<?php echo _('Test Credentials') ?>"/>
                                                                </td>

                                                                <td class="noborder" id="test_nagios_result"></td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </form>
                    </td>
                </tr>


                <!-- Flows -->
                <tr>
                    <td class="sec_title"><?php echo _('Flows')?></td>
                </tr>

                <tr>
                    <td class="noborder">

                        <div id='netflow_notification'></div>

                        <form name="nfsenform">
                            <table width="100%">
                                <tr id="nfsen_form">
                                    <td class="noborder" colspan="6">
                                        <table class="transparent" align="center" width="100%">
                                            <tr>
                                                <th><?php echo _('Netflow Collection Configuration')?></th>
                                                <th><?php echo _('Action')?></th>
                                            </tr>

                                            <tr>
                                                <td class="noborder">
                                                    <table class="transparent">
                                                        <tr>
                                                            <td class="right noborder color_label" width="30"><?php echo _('Port')?>:</td>
                                                            <td class="left noborder">
                                                                <input type="text" name="nfsen_port" id="nfsen_port" value="<?php echo $base_port?>" style="width:50px"/>
                                                            </td>
                                                            <td class="right noborder color_label" width="30"><?php echo _('Color') ?>:</td>
                                                            <td class="left noborder">
                                                                <div id="backgroundTitle1" class="colorSelector">
                                                                    <div style="background-color: <?php echo $base_color?>;">
                                                                        <input type="hidden" id="backgroundTitle" name="backgroundTitle" value="#0000ff"/>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="right noborder color_label" width="30" style="padding-left:20px"><?php echo _('Type')?>:</td>
                                                            <td class="left noborder">
                                                                <select name="nfsen_type" id="nfsen_type">
                                                                    <option value="netflow" <?php if ($base_type == "netflow") echo "selected='selected'"?>><?php echo _('netflow')?>
                                                                    <option value="sflow" <?php if ($base_type == "sflow") echo "selected='selected'"?>><?php echo _('sflow')?>
                                                                </select>
                                                            </td>

                                                            <td class="noborder" style="padding-left:10px"><?php echo _('Status')?>:</td>
                                                            <td class="noborder" id="netflow_hdr" style="white-space: nowrap;"><?php if ($nfsen_sensors[$nfsen_id] != '') echo "<font style='color:red'><b>"._("is configured")."</b></font>"; else echo "<font style='color:red'><b>"._('is not configured')."</b></font>"?></td>
                                                        </tr>
                                                    </table>
                                                </td>

                                                <td class="center noborder" style="padding-left:20px;padding-right:20px">
                                                    <table class="transparent" style='margin:auto;'>
                                                        <tr>
                                                            <td class="center noborder">
                                                                <input type="button" class="av_b_secondary small" id="netflow_button" value=''/>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="white-space: nowrap;" class="center noborder" style="padding-top:10px">
                                                                <a href="../nfsen/helpflows.php" class="greybox"><b><?php echo _('Configuration help')?> ?</b></a>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </form>
                    </td>
                </tr>
            </table>
            <?php
        }
        ?>
    </div>
</body>
</html>

<?php
$db->close();
?>
