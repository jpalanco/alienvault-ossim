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

$scan_types = array(
    'nmap' => 5,
    'wmi'  => 4
);


$s_type = REQUEST('s_type');
$s_type = (empty($s_type)) ? 'nmap' : $s_type;


if (!array_key_exists($s_type, $scan_types))
{
    header("Location: ".AV_MAIN_ROOT_PATH."/404.php");
    exit();
}

//Save current scan type in memory
$_SESSION['av_inventory_type'] = $s_type;

Session::logcheck('environment-menu', 'AlienVaultInventory');
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title><?php echo _('OSSIM Framework');?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <meta http-equiv="X-UA-Compatible" content="IE=7"/>

    <?php
        //CSS Files
        $_files = array(
            array('src' => 'av_common.css?t='.Util::get_css_id(),           'def_path' => TRUE),
            array('src' => 'jquery-ui.css',                                 'def_path' => TRUE),
            array('src' => 'jquery.dataTables.css',                         'def_path' => TRUE),
            array('src' => 'tipTip.css',                                    'def_path' => TRUE),
            array('src' => 'jquery.switch.css',                             'def_path' => TRUE),
            array('src' => 'jquery.dropdown.css',                           'def_path' => TRUE)
        );

        Util::print_include_files($_files, 'css');

        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                                 'def_path' => TRUE),
            array('src' => 'jquery-ui.min.js',                              'def_path' => TRUE),
            array('src' => 'jquery.elastic.source.js',                      'def_path' => TRUE),
            array('src' => 'jquery.dataTables.js',                          'def_path' => TRUE),
            array('src' => 'jquery.dataTables.plugins.js',                  'def_path' => TRUE),
            array('src' => 'jquery.switch.js',                              'def_path' => TRUE),
            array('src' => 'jquery.dropdown.js',                            'def_path' => TRUE),
            array('src' => 'greybox.js',                                    'def_path' => TRUE),
            array('src' => 'notification.js',                               'def_path' => TRUE),
            array('src' => 'ajax_validator.js',                             'def_path' => TRUE),
            array('src' => 'messages.php',                                  'def_path' => TRUE),
            array('src' => 'utils.js',                                      'def_path' => TRUE),
            array('src' => 'token.js',                                      'def_path' => TRUE),
            array('src' => 'jquery.tipTip.js',                              'def_path' => TRUE),
            array('src' => '/av_schedule_scan/js/av_schedule_scan.js.php',  'def_path' => FALSE)
        );

        Util::print_include_files($_files, 'js');
    ?>

    <script type="text/javascript">

        $(document).ready(function()
        {
            if (typeof parent.is_lightbox_loaded == 'function' && parent.is_lightbox_loaded(window.name))
            {
                $('#c_lmenu').hide();
            }

            load_schedule_scans('<?php echo $s_type?>');
        });
    </script>
</head>

<body>

    <?php
        //Local menu
        include_once AV_MAIN_ROOT_PATH.'/local_menu.php';
        session_write_close();
    ?>

    <div id='ss_container'>

        <div data-name="s_scan" data-bind="av_table_s_scan">

            <div class="c_action_buttons">
                <div class="action_buttons">                    
                     <button id='new_agent' class='new_s_scan av_b_secondary small' data-bind="new_s_scan"><?php echo _('Schedule new scan')?></button>             
                </div>
            </div>

            <table class="table_data" id="table_data_s_scan" data-bind="table_data_s_scan">
                <thead>
                    <tr>
                        <th><?php echo _('Name')?></th>
                        <th><?php echo _('Sensor')?></th>
                        <?php
                        switch ($s_type)
                        {
                            case 'nmap':
                                $colunm_name = _('Targets');
                            break;

                            case 'wmi':
                                $colunm_name = _('Credentials');
                            break;

                            default:
                                $colunm_name = _('Parameters');
                            break;
                        }
                        ?>
                        <th><?php echo $colunm_name?></th>
                        <th><?php echo _('Frequency')?></th>
                        <th><?php echo _('Enabled')?></th>
                        <th><?php echo _('Actions')?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan='6'></td>
                    </tr>
                </tbody>
            </table>

        </div>
    </div>
</body>
</html>
