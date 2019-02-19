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

Session::logcheck('environment-menu', 'ToolsScan');

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


//Database connection
$db   = new ossim_db();
$conn = $db->connect();


/****************************************************
 ************ Default scan configuration ************
 ****************************************************/

$sensor            = 'local';
$scan_type         = 'fast';
$ttemplate         = 'T3';
$scan_ports        = '1-65535';
$autodetected      = TRUE;
$rdns              = TRUE;
$disabled          = '';
$validation_errors = '';
$asset_type        = (GET('type') == 'group') ? 'group' : ((GET('type') == 'network') ? 'network' : 'asset');

$disable_scan = FALSE;

try
{
    $explain_scan = Av_scan::explain_scan($conn, $asset_type);

    $close = FALSE;

    if (GET('action') == 'scan')
    {
        $scan_type       = GET('scan_type');
        $timing_template = GET('timing_template');
        $custom_ports    = GET('custom_ports');
        $autodetect      = (GET('autodetect') == 1) ? 'true' : 'false';
        $rdns            = (GET('rdns') == 1)       ? 'true' : 'false';
        $custom_ports    = str_replace(' ', '', $custom_ports);

        ossim_valid($scan_type,       OSS_ALPHA, OSS_SCORE, OSS_NULLABLE,                 'illegal:' . _('Full scan'));
        ossim_valid($timing_template, OSS_TIMING_TEMPLATE,                                'illegal:' . _('Timing_template'));
        ossim_valid($custom_ports,    OSS_DIGIT, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, ',', 'illegal:' . _('Custom Ports'));

        if (ossim_error())
        {
            $e_msg = ossim_get_error_clean();
        }
        else
        {
            // Run remote nmap scans
            $targets = array();

            foreach ($explain_scan as $sensor_id => $s_data)
            {
                //Sensor status: Idle(0), Running (1) or Down(2)
                $code = $s_data['status']['code'];

                if ($code == 0)
                {
                    foreach ($s_data['assets'] as $assets)
                    {
                        $targets[] = $assets['ip'];
                    }

                    $targets = implode(' ',$targets);

                    $scan_options = array(
                        'scan_type'       => $scan_type,
                        'timing_template' => $timing_template,
                        'autodetect_os'   => $autodetect,
                        'reverse_dns'     => $rdns,
                        'ports'           => $custom_ports,
                        'idm'             => 'true'
                    );

                    $av_scan = new Av_scan($targets, $sensor_id, $scan_options);

                    $res = $av_scan->run();

                    $close = TRUE;

                    unset($av_scan);

                    $explain_scan[$sensor_id]['status'] = array(
                        'code'  => 1,
                        'descr' => _('Running')
                    );
                }
            }
        }
    }
}
catch(Exception $e)
{
    $e_msg = $e->getMessage();
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title><?php echo _('OSSIM Framework');?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>

    <?php
    //CSS Files
    $_files = array(
        array('src' => 'av_common.css?t='.Util::get_css_id(),           'def_path' => TRUE),
        array('src' => 'tipTip.css',                                    'def_path' => TRUE),
        array('src' => '/environment/assets/asset_discovery.css',       'def_path' => TRUE)
    );

    Util::print_include_files($_files, 'css');

    //JS Files
    $_files = array(
        array('src' => 'jquery.min.js',                                  'def_path' => TRUE),
        array('src' => 'notification.js',                                'def_path' => TRUE),
        array('src' => 'utils.js',                                       'def_path' => TRUE),
        array('src' => 'jquery.tipTip.js',                               'def_path' => TRUE),
        array('src' => 'av_scan.js.php',                                 'def_path' => TRUE)
    );

    Util::print_include_files($_files, 'js');
    ?>

    <style type="text/css">
        .c_actions
        {
            padding: 20px;
            text-align: center;
        }

        .t_adv_options
        {
            padding:7px 0px 0px 10px;
        }
    </style>


    <script type='text/javascript'>

        function close_window()
        {
            if (typeof parent.GB_close == 'function')
            {
                parent.GB_close();
            }

            return false;
        }

        function hide_window(msg, type)
        {
            if (typeof parent.GB_hide == 'function')
            {
                top.frames['main'].show_notification('asset_notif', msg, type, 15000, true);
                parent.GB_hide();
            }

            return false;
        }

        $(document).ready(function()
        {
            $('#scan_button').click(function(event)
            {
                $(this).addClass('av_b_processing');
            });

            $('#close_button').click(function(event)
            {
                event.preventDefault();
                close_window(false);
            });

            $("#assets_form").on( "keypress", function(e)
            {
                if (e.which == 13 )
                {
                    return false;
                }
            });

            /****************************************************
             ********************* Tooltips *********************
             ****************************************************/

            if ($(".more_info").length >= 1)
            {
                $(".more_info").tipTip({maxWidth: "auto", attribute: 'data-title'});
            }

            bind_nmap_actions();

            <?php
            if ($close)
            {
                $msg = sprintf(_('Asset scan in progress for %s assets'), count($targets));
                echo 'hide_window("'. Util::js_entities($msg) .'", "nf_success");';
            }
            ?>
        });
    </script>

</head>

<body>

<!-- Asset form -->

<div id='c_info'>
    <?php
    if (!empty($e_msg))
    {
        $disable_scan = TRUE;

        $txt_error = "<div>"._('The following errors occurred').":</div>
                      <div style='padding: 10px;'>".$e_msg."</div>";

        $config_nt = array(
            'content' => $txt_error,
            'options' => array (
                'type'          =>  'nf_error',
                'cancel_button' =>  FALSE
            ),
            'style' => 'width: 80%; margin: 20px auto; text-align: left;'
        );

        $nt = new Notification('nt_1', $config_nt);
        $nt->show();
    }
    elseif (GET('action') == 'scan')
    {
        $config_nt = array(
            'content' => '<div>'._('Asset Scan successfully launched in background').'</div>',
            'options' => array (
                'type'          =>  'nf_success',
                'cancel_button' =>  TRUE
            ),
            'style' =>  'width: 80%; margin: 20px auto; text-align: left;'
        );

        $nt = new Notification('nt_1', $config_nt);
        $nt->show();
    }
    ?>
</div>

<div id='c_asset_discovery'>

    <form name="assets_form" id="assets_form">
        <input type="hidden" name="action" value="scan"/>
        <input type="hidden" name="type" value="<?php echo $asset_type?>"/>

        <table align="center" id='t_ad'>

            <tbody>
                <tr>
                    <th colspan="2"><?php echo _('Target selection') ?></th>
                </tr>

                <tr>
                    <td>
                        <span> <?php echo _('List of selected assets to scan:');?></span>
                    </td>
                </tr>

                <tr>
                    <td class='container nobborder'>
                        <?php
                        if (!empty($explain_scan))
                        {
                            ?>
                            <table class="sensors">
                                <thead>
                                    <tr>
                                        <th><?php echo _('Assets')?></th>
                                        <th><?php echo _('Sensor')?></th>
                                        <th><?php echo _('Status')?></th>
                                    </tr>
                                </thead>

                                <tbody>

                                <?php
                                foreach ($explain_scan as $sensor_id => $s_data)
                                {
                                    $first = $s_data['assets'][0];
                                    $last  = $s_data['assets'][count($s_data['assets'])-1];

                                    if (count($s_data['assets'])-1 == 0)
                                    {
                                        $asset = $first['ip'];
                                    }
                                    else
                                    {
                                        $asset = $first['ip']." ... ".$last['ip'];
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo $asset?></td>
                                        <td><?php echo $s_data['name'].' ['.$s_data['ip'].']'?></td>
                                        <td>
                                            <?php
                                            $code = $s_data['status']['code'];

                                            switch ($code)
                                            {
                                                //Idle
                                                case 0:
                                                    $icon         = "/ossim/pixmaps/tick.png";
                                                    $disable_scan = FALSE;
                                                break;

                                                //Running
                                                case 1:
                                                    $icon         = "../pixmaps/running.gif";
                                                    $disable_scan = TRUE;
                                                break;

                                                //Down
                                                case 2:
                                                    $icon         = "/ossim/pixmaps/cross.png";
                                                    $disable_scan = TRUE;
                                                break;
                                            }

                                            echo "<img src='$icon' class='more_info' data-title='".$s_data['status']['descr']."'/>";
                                            ?>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                        <?php
                    }
                    ?>
                    </td>
                </tr>

                <tr>
                    <th colspan="2"><?php echo _('Advanced Options')?></th>
                </tr>

                <!-- Full scan -->
                <tr>
                    <td colspan="2">

                        <table id='t_adv_options'>
                            <!-- Full scan -->
                            <tr>
                                <td class='td_label'>
                                    <label for="scan_type"><?php echo _('Scan type')?>:</label>
                                </td>
                                <td>
                                    <select id="scan_type" name="scan_type" class="nmap_select vfield">
                                        <?php
                                        foreach ($scan_types as $st_v => $st_txt)
                                        {
                                            $selected = ($scan_type == $st_v) ? 'selected="selected"' : '';

                                            echo "<option value='$st_v' $selected>$st_txt</option>";
                                        }
                                        ?>
                                    </select>
                                </td>
                                <td style='padding-left: 20px;'>
                                    <span id="scan_type_info"></span>
                                </td>
                            </tr>

                            <!-- Specific ports -->
                            <tr id='tr_cp'>
                                <td class='td_label'>
                                    <label for="custom_ports"><?php echo _('Specify Ports')?>:</label>
                                </td>
                                <td colspan="2">
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
                                        foreach ($time_templates as $ttv => $tt_txt)
                                        {
                                            $selected = ($ttemplate == $ttv) ? 'selected="selected"' : '';

                                            echo "<option value='$ttv' $selected>$tt_txt</option>";
                                        }
                                        ?>
                                    </select>
                                </td>
                                <td style='padding-left: 20px;'>
                                    <span id="timing_template_info"></span>
                                </td>
                            </tr>

                            <tr>
                                <td colspan="3">

                                    <?php $ad_checked = ($autodetected == TRUE) ? 'checked="checked"' : '';?>

                                    <input type="checkbox" id="autodetect" name="autodetect" class='vfield' <?php echo $ad_checked?> value="1"/>
                                    <label for="autodetect"><?php echo _('Autodetect services and Operating System')?></label>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3">

                                    <?php $rdns_checked = ($rdns == TRUE) ? 'checked="checked"' : '';?>

                                    <input type="checkbox" id="rdns" name="rdns" class='vfield' <?php echo $rdns_checked?> value="1"/>
                                    <label for="rdns"><?php echo _('Enable DNS Resolution')?></label>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="c_actions">
            <?php
            if (GET('action') != 'scan' || !empty($e_msg))
            {
                $disabled = ($disable_scan == TRUE) ? "disabled='disabled'" : '';
                ?>
                <input type="button" class="av_b_secondary" id="close_button" value="<?php echo _('Cancel') ?>"/>
                <input type="submit" id="scan_button" <?php echo $disabled?> value="<?php echo _('Start Scan') ?>"/>
                <?php
            }
            else
            {
                ?>
                <input type="button" class="av_b_secondary" id="close_button" value="<?php echo _('Close') ?>"/>
                <?php
            }
            ?>
        </div>

    </form>
</div>

<?php
//Close DB connection
$db->close();
?>
</body>
</html>
