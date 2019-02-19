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

require_once dirname(__FILE__) . '/../../conf/config.inc';

Session::logcheck('environment-menu', 'EventsHidsConfig');


$tab        = POST('tab');
$sensor_id  = POST('sensor_id');


ossim_valid($tab, OSS_LETTER, OSS_DIGIT, OSS_NULLABLE, '#', 'illegal:' . _('Tab'));
ossim_valid($sensor_id, OSS_HEX,                            'illegal:' . _('Sensor ID'));


if (!ossim_error())
{
    $db    = new ossim_db();
    $conn  = $db->connect();

    if (!Ossec_utilities::is_sensor_allowed($conn, $sensor_id))
    {
        ossim_set_error(_('Error! Sensor not allowed'));
    }

    $db->close();
}


if (ossim_error())
{
    echo '2###'._('We found the followings errors').": <div style='padding-left: 15px; text-align:left;'>".ossim_get_error_clean().'</div>';
    exit();
}


//Current sensor
$_SESSION['ossec_sensor'] = $sensor_id;

if($tab == '#tab1')
{
    try
    {
        $conf_data = Ossec::get_configuration_file($sensor_id);

        $command =  'egrep "<[[:space:]]*include[[:space:]]*>.*xml<[[:space:]]*/[[:space:]]*include[[:space:]]*>" ?';
        $output  = Util::execute_command($command, array($conf_data['path']), 'array');

        $rules_enabled = array();


        foreach($output as $k => $v)
        {
            if (preg_match("/^<\s*include\s*>(.*)<\s*\/include\s*>/", trim($v), $match))
            {   
                //Get only rulename from path to show in the UI
                $rule_path = explode('/', rtrim($match[1], '/'));
                $rule_name = array_pop($rule_path);
                $rules_enabled[] = $rule_name;
            }
        }

        sort($rules_enabled);


        $all_rules = Ossec::get_rule_files($sensor_id);

        $no_added_rules = array_diff($all_rules, $rules_enabled);

        echo "1###";
        ?>
        <div id='cnf_rules_cont'>
            <table class='cnf_rules_table'>
                <tr>
                    <td style='padding: 8px 0px 6px 0px;'><?php echo '(*) '._('Drag & Drop the file you want to add/remove or use [+] and [-] links')?></td>
                </tr>

                <tr>
                    <td class='sec_title'>
                        <div style='float: left; width: 48%'><?php echo _('Enabled Rules')?></div>
                        <div style='float: right; width: 48%'><?php echo _('Disabled Rules')?></div>
                    </td>
                </tr>

                <tr>
                    <td>
                        <div id='ms_body'>
                            <div id='load_ms'><img src='/ossim/pixmaps/loading.gif'/></div>
                            <form name='cnf_form_rules' id='cnf_form_rules' method='POST'>
                                <select id='rules_added' class='multiselect' multiple='multiple' name='rules_added[]'>
                                <?php
                                foreach($rules_enabled as $k => $v)
                                {
                                    //Special case: Rules_config.xml is special file to configure rule (It will be skipped)
                                    if ($v != 'rules_config.xml')
                                    {
                                        echo "<option value='$v' selected='selected'>$v</option>";
                                    }
                                }

                                foreach($no_added_rules as $k => $v)
                                {
                                    //Special case: Rules_config.xml is special file to configure rule (It will be skipped)
                                    if ($v != 'rules_config.xml')
                                    {
                                        echo "<option value='$v'>$v</option>";
                                    }
                                }
                                ?>
                                </select>
                            </form>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style='padding:10px 0px;'>
                        <input type='button' id='send_1' value='<?php echo _('Save')?>' onclick="save_config_tab();"/>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
    catch(Exception $e)
    {
        echo "2###"._('We found the followings errors:')."<div style='padding-left: 15px; text-align:left;'>".$e->getMessage()."</div>";
    }
}
else if ($tab == '#tab2')
{
    try
    {
        $conf_data = Ossec::get_configuration_file($sensor_id);

        $xml_obj = new Xml_parser('key');
        $xml_obj->load_string($conf_data['data']);
        $array_oss_cnf = $xml_obj->xml2array();


        $syscheck = Ossec::get_nodes($array_oss_cnf, 'syscheck');

        $directories = Ossec::get_nodes($syscheck, 'directories');
        $ignores     = Ossec::get_nodes($syscheck, 'ignore');

        $frequency       = Ossec::get_nodes($syscheck, 'frequency');
        $frequency       = $frequency[0][0];

        $scan_day        = Ossec::get_nodes($syscheck, 'scan_day');
        $scan_day        = $scan_day[0][0];


        $scan_time       = Ossec::get_nodes($syscheck, 'scan_time');
        $scan_time       = $scan_time[0][0];
        $st              = (!empty($scan_time)) ? explode(":", $scan_time) : array();

        $auto_ignore     = Ossec::get_nodes($syscheck, 'auto_ignore');
        $auto_ignore     = (empty($auto_ignore[0][0])) ? 'no' : $auto_ignore[0][0];

        $alert_new_files = Ossec::get_nodes($syscheck, 'alert_new_files');
        $alert_new_files = (empty($alert_new_files[0][0])) ? 'no' : $alert_new_files[0][0];

        $scan_on_start   = Ossec::get_nodes($syscheck, 'scan_on_start');
        $scan_on_start   = (empty($scan_on_start[0][0])) ? 'yes' : $scan_on_start[0][0];

        $directory_checks = array(
                'realtime'       => 'Realtime',
                'report_changes' => 'Report changes',
                'check_all'      => 'Chk all',
                'check_sum'      => 'Chk sum',
                'check_sha1sum'  => 'Chk sha1sum',
                'check_size'     => 'Chk size',
                'check_owner'    => 'Chk owner',
                'check_group'    => 'Chk group',
                'check_perm'     => 'Chk perm'
        );

        $week_days = array(
                ''             => '-- Select a day --',
                'monday'       => 'Monday',
                'tuesday'      => 'Tuesday',
                'wednesday'    => 'Wednesday',
                'thursday'     => 'Thursday',
                'friday'       => 'Friday',
                'saturday'     => 'Saturday',
                'sunday'       => 'Sunday'
        );

        $yes_no = array(
                'yes'     => 'Yes',
                'no'      => 'No'
        );

        echo '1###';
        ?>
	<script>
	$(document).ready(function() {
		$('.info').tipTip();
	});
	</script>
        <form name='form_syscheck' id='form_syscheck'>

            <div class='cont_sys'>

                <table class='t_syscheck' id='table_sys_parameters'>
                    <tr><td class='sec_title' colspan='2'><?php echo _('Configuration parameters')?></td></tr>

                    <tr>
                        <td style='width: 50%'>
                            <table>
                                <tr>
                                    <th><?php echo _('Frequency')?></th>
                                    <td><input type='text' id='frequency' name='frequency' value='<?php echo $frequency?>'/></td>
                                </tr>

                                <tr>
                                    <th><?php echo _('Scan_day')?></th>
                                    <td>
                                        <select id='scan_day' name='scan_day'>
                                            <?php
                                                foreach ($week_days as $k => $v)
                                                {
                                                    $selected = ($k == $scan_day) ? "selected='selected'" : "";
                                                    echo "<option value='$k' $selected>$v</option>";
                                                }
                                            ?>
                                        </select>
                                    </td>
                                </tr>

                                <tr>
                                    <th>
                                        <?php echo _('Alert new files')?>
                                        <img class="info" src="/ossim/ossec/images/information.png" title="<?php echo _("Please be aware that these settings will be applied to all agents")?>">
                                    </th>
                                    <td>
                                        <select id='alert_new_files' name='alert_new_files'>
                                            <?php
                                            foreach ($yes_no as $k => $v)
                                            {
                                                $selected = ($k == $alert_new_files) ? "selected='selected'" : "";
                                                echo "<option value='$k' $selected>$v</option>";
                                            }
                                            ?>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </td>

                        <td style='width: 50%'>
                            <table>
                                <tr>
                                    <th><?php echo _('Scan time')?></th>
                                    <td>
                                        <input type='text' class='time' maxlength='2' id='scan_time_h' name='scan_time_h' value='<?php echo $st[0]?>'/>
                                        <span style='margin: 0px 2px'>:</span>
                                        <input type='text' class='time' maxlength='2' id='scan_time_m' name='scan_time_m' value='<?php echo $st[1]?>'/>
                                    </td>
                                </tr>

                                <tr>
                                    <th>
                                        <?php echo _('Auto ignore')?>
                                        <img class="info" src="/ossim/ossec/images/information.png" title="<?php echo _("Please be aware that these settings will be applied to all agents")?>">
                                    </th>
                                    <td>
                                        <select id='auto_ignore' name='auto_ignore'>
                                            <?php
                                            foreach ($yes_no as $k => $v)
                                            {
                                                $selected = ($k == $auto_ignore) ? "selected='selected'" : "";
                                                echo "<option value='$k' $selected>$v</option>";
                                            }
                                            ?>
                                        </select>
                                    </td>
                                </tr>

                                <tr>
                                    <th class='sys_parameter'><?php echo _('Scan on start')?></th>
                                    <td class='sys_value'>
                                        <select id='scan_on_start' name='scan_on_start'>
                                            <?php
                                            foreach ($yes_no as $k => $v)
                                            {
                                                $selected = ($k == $scan_on_start) ? "selected='selected'" : "";
                                                echo "<option value='$k' $selected>$v</option>";
                                            }
                                            ?>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <div class='cont_savet2'>
                    <input type='button' class='small' id='send_2' value='<?php echo _('Save')?>' onclick="save_config_tab();"/>
                </div>
            </div>


            <div class='cont_sys'>
                <table class='t_syscheck' id='table_sys_directories'>
                    <thead>
                        <tr>
                            <td class='sec_title' colspan='<?php echo count($directory_checks)+2?>'><?php echo _('Files/Directories monitored')?></td>
                        </tr>

                        <tr>
                            <th class='w250' rowspan='2'><?php echo _('Files/Directories')?></th>
                            <th colspan='<?php echo count($directory_checks)?>'><?php echo _('Parameters')?></th>
                            <th rowspan='2' class='sys_actions'><?php echo _('Actions')?></th>
                        </tr>

                        <tr>
                            <?php
                            foreach ($directory_checks as $k => $v)
                            {
                                echo "<th style='white-space: normal;'>$v</th>";
                            }
                            ?>
                        </tr>
                    </thead>

                    <tbody id='tbody_sd'>
                        <?php
                        if (empty($directories))
                        {
                            $k           = 0;
                            $directories = array(array('@attributes' => NULL, "0" => NULL));
                        }

                        foreach ($directories as $k => $v)
                        {
                            ?>
                            <tr class='dir_tr' id='dir_<?php echo $k?>'>
                                <td style='text-align: left;'>
                                    <textarea name='<?php echo $k?>_value_dir' id='<?php echo $k?>_value_dir'><?php echo $directories[$k][0]?></textarea>
                                </td>
                                <?php
                                $i = 0;
                                foreach ($directory_checks as $j => $value)
                                {
                                    $i++;
                                    $checked = (!empty($directories[$k]['@attributes'][$j])) ? 'checked="checked"' : '';
                                    echo "<td style=' text-align:center;'><input type='checkbox' id='".$j."_".$k."_".$i."' name='".$j."_".$k."_".$i."' $checked/></td>";
                                }
                                ?>

                                <td class='center'>
                                    <a onclick='delete_row("dir_<?php echo $k?>", "delete_directory");'><img src='/ossim/vulnmeter/images/delete.gif' align='absmiddle'/></a>
                                    <a onclick='add_row("dir_<?php echo $k?>", "add_directory");'><img src='/ossim/ossec/images/add.png' align='absmiddle'/></a>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>

                <div class='cont_savet2'>
                    <input type='button' class='small' id='send_5' value='<?php echo _('Save')?>' onclick="save_config_tab();"/>
                </div>
            </div>

            <div class='cont_sys'>
                <?php
                if (empty($ignores))
                {
                    $k       = 0;
                    $ignores = array(array('@attributes' => NULL, '0' => NULL));
                }
                ?>
                <table class='t_syscheck' id='table_sys_ignores'>
                    <thead>
                        <tr>
                            <td class='sec_title' colspan='3'><?php echo _('Files/Directories ignored')?></td>
                        </tr>
                        <tr>
                            <th rowspan='2'><?php echo _('Files/Directories')?></th>
                            <th><?php echo _("Parameters")?></th>
                            <th class='sys_actions' rowspan='2'><?php echo _('Actions')?></th>
                        </tr>
                        <tr>
                            <th style='text-align:center;'><?php echo _('Sregex')?></th>
                        </tr>
                    </thead>

                    <tbody id='tbody_si'>
                    <?php

                    foreach ($ignores as $k => $v)
                    {
                        $checked = (!empty($ignores[$k]['@attributes']['type'])) ? 'checked="checked"' : '';
                        ?>
                        <tr class='ign_tr' id='ign_<?php echo $k?>'>
                            <td style='text-align: left;'><textarea name='<?php echo $k?>_value_ign' id='<?php echo $k?>_value_ign'><?php echo $ignores[$k][0]?></textarea></td>
                            <td style='text-align: center;'><input type='checkbox' name='<?php echo $k?>_type' id='<?php echo $k?>_type' <?php echo $checked?>/></td>
                            <td class='center'>
                                <a onclick='delete_row("ign_<?php echo $k?>", "delete_ignore");'><img src='/ossim/vulnmeter/images/delete.gif' align='absmiddle'/></a>
                                <a onclick='add_row("ign_<?php echo $k?>", "add_ignore");'><img src='/ossim/ossec/images/add.png' align='absmiddle'/></a>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                </table>

                <div class='cont_savet2'>
                    <input type='button' class='small' id='send_6' value='<?php echo _('Save')?>' onclick="save_config_tab();"/>
                </div>
            </div>

        </form>
        <?php
    }
    catch(Exception $e)
    {
        echo "2###"._('We found the followings errors:')."<div style='padding-left: 15px; text-align:left;'>".$e->getMessage().'</div>';
    }
}
elseif ($tab == '#tab3')
{
    try
    {
        $conf_data = Ossec::get_configuration_file($sensor_id);

        echo "1###".$conf_data['data'];
    }
    catch(Exception $e)
    {
        echo "2###"._('We found the followings errors:')."<div style='padding-left: 15px; text-align:left;'>".$e->getMessage().'</div>';
    }
}
else
{
    echo '2###'._('We found the followings errors').": <div style='padding-left: 15px; text-align:left;'>"._('Illegal action').'</div>';
}
?>
