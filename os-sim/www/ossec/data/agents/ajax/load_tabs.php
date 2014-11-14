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

require_once dirname(__FILE__) . '/../../../conf/config.inc';

Session::logcheck('environment-menu', 'EventsHidsConfig');

$events_hids_config = Session::menu_perms('environment-menu', 'EventsHidsConfig');


$tab         = POST('tab');
$sensor_id   = POST('sensor_id');

ossim_valid($tab, OSS_LETTER, OSS_DIGIT, OSS_NULLABLE, '#', 'illegal:' . _('Tab'));
ossim_valid($sensor_id, OSS_HEX,                            'illegal:' . _('Sensor ID'));

if (!ossim_error())
{
    $db    = new ossim_db();
    $conn  = $db->connect();
    
    if (!Ossec_utilities::is_sensor_allowed($conn, $sensor_id))
    {
        echo ossim_error(_('Error! Sensor not allowed'));
    }

    $db->close();
}


if (ossim_error())
{
   echo "2###"._('We found the followings errors:')."<div style='padding-left: 15px; text-align:left;'>".$e->getMessage()."</div>";
   exit();
}


//Current sensor
$_SESSION['ossec_sensor'] = $sensor_id;


if($tab == '#tab1')
{   
    $idm_enabled = (isset($_SESSION['_idm']) && !empty($_SESSION['_idm']) ) ? TRUE : FALSE;
    echo '1###';
    ?>
    <div id='c_agent_table' style='padding-top:10px;'>
            
        <div class='body_al' id='body_al_2'>
            <table class='table_data' id='agent_table'>
                <thead>
                    <tr>
                        <th class='th_mi'></th>
                        <th class='th_id'><?php echo _('ID')?></th>
                        <th class='th_name'><?php echo _('Name')?></th>
                        <th class='th_ip'><?php echo _('IP/CIDR')?></th>
                        <?php 
                        if ($idm_enabled)
                        { 
                            ?>
                            <th class='th_ci'><?php echo _('Current IP')?></th>
                            <th class='th_cu'><?php echo _('Current User@Domain')?></th>
                            <?php 
                        } 
                        ?>
                        <th class='th_status'><?php echo _('Status')?></th>
                        <th class='agent_actions'><?php echo _('Actions')?></th>
                    </tr>
                </thead>
                
                <tbody>
                    <?php
                    
                    try
                    {
                        $agents = Ossec_agent::get_list($sensor_id);

                        if (is_array($agents) && !empty($agents))
                        {
                            foreach ($agents as $agent_id => $a_data)
                            {
                                if (empty($a_data))
                                {
                                    continue;
                                }

                                $a_unique_id = md5($agent_id);
                                $_SESSION['_agent_info'][$a_unique_id] = $a_data;

                                $agent_actions = Ossec_agent::get_actions($agent_id, $a_data);
                                ?>

                                <tr id="<?php echo 'cont_agent_'.$agent_id?>">
                                    <td><img class="info" src="<?php echo OSSEC_IMG_PATH.'/information.png'?>"/></td>
                                    <td id='agent_<?php echo $a_unique_id?>'><?php echo $agent_id?></td>
                                    <td><?php echo $a_data['name']?></td>
                                    <td><?php echo $a_data['ip']?></td>
                                    <?php
                                    if ($idm_enabled)
                                    {
                                        ?>
                                        <td>
                                            <div style='text-align: center !important'> - </div>
                                        </td>

                                        <td>
                                            <div style='text-align: center !important'> - </div>
                                        </td>
                                        <?php
                                    }
                                    ?>
                                    <td><?php echo $a_data['status']?></td>
                                    <td class='agent_actions'><?php echo $agent_actions?></td>
                                </tr>
                                <?php
                            }
                        }
                    }
                    catch(Exception $e)
                    {
                        ;
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <table id='t_add_agent'>
            <tr>
                <td id='cont_commom_ac'>
                    <div class='commom_ac'>
                        <a id='show_agent'><img src='<?php echo OSSIM_IMG_PATH?>/user--plus.png' alt='Arrow' align='absmiddle'/><span><?php echo _('Add agent')?></span></a>
                    </div>
                </td>
                <td class='info'></td>
            </tr>
            
            <tr>
                <td colspan='2'>
                    <div id='cont_add_agent' class='hide'>
                        <form method='POST' name='form_agent' id='form_agent'>
                            <table id='t_new_agent'>
                                <tr>
                                    <th class='headerpr' colspan='2'><?php echo _('New Agent')?></th>
                                </tr>
                                <tr>
                                    <th><label for='agent_name'><?php echo _('Agent Name').required();?></label></th>
                                    <td class="left">
                                        <input type='text' name='agent_name' id='agent_name' class='vfield'/>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th><label for='ip_cidr'><?php echo _('IP/CIDR').required();?></label></th>
                                    <td class="left">
                                        <input type='text' name='ip_cidr' id='ip_cidr' class='vfield'/>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="cont_send" colspan='2'><input type="button" id='send_1' value="<?php echo _('Save')?>"/></td>
                                </tr>
                            </table>
                        </form>
                    </div>
                </td>
            </tr>
            
            <tr>
                <td class='left' id='changes_in_files' colspan='2'></td>
            </tr>
        </table>
    </div>

    <script type='text/javascript'>

        Token.add_to_forms();

        $('#agent_table').dataTable( {
                "iDisplayLength": 10,
                "sPaginationType": "full_numbers",
                "bPaginate": true,
                "bLengthChange": false,
                "bFilter": true,
                "bSort": true,
                "bInfo": true,
                "bJQueryUI": true,
                "aaSorting": [[ 1, "asc" ]],
                "aoColumns": [
                    { "bSortable": false },
                    { "bSortable": true },
                    { "bSortable": true },
                    <?php
                    if ($idm_enabled)
                    {
                        ?>
                        { "bSortable": false },
                        { "bSortable": false },
                        <?php
                    }
                    ?>
                    { "bSortable": true },
                    { "bSortable": true },
                    { "bSortable": false }
                ],
                oLanguage : {
                    "sProcessing": "<?php echo _('Processing') ?>...",
                    "sLengthMenu": "Show _MENU_ entries",
                    "sZeroRecords": "<?php echo _('No matching records found') ?>",
                    "sEmptyTable": "<?php echo _('No agents found') ?>",
                    "sLoadingRecords": "<?php echo _('Loading') ?>...",
                    "sInfo": "<?php echo _('Showing _START_ to _END_ of _TOTAL_ entries') ?>",
                    "sInfoEmpty": "<?php echo _('Showing 0 to 0 of 0 entries') ?>",
                    "sInfoFiltered": "(<?php echo _('filtered from _MAX_ total entries') ?>)",
                    "sInfoPostFix": "",
                    "sInfoThousands": ",",
                    "sSearch": "<?php echo _('Search') ?>",
                    "sUrl": "",
                    "oPaginate": {
                        "sFirst":    "<?php echo _('First') ?>",
                        "sPrevious": "<?php echo _('Previous') ?>",
                        "sNext":     "<?php echo _('Next') ?>",
                        "sLast":     "<?php echo _('Last') ?>"
                    }
                },
                "fnInitComplete": function(oSettings)
                {
                    var title = "<div class='dt_title' style='top:10px;'><?php echo _('Agent Information') ?></div>";

                    $('div.dt_header').append(title);
                },
                "fnRowCallback": function(nRow, aData, iDrawIndex, iDataIndex)
                {
                    //IDM data
                    if ($(nRow).find("td").length > 6)
                    {
                        if(!$(nRow).find("td:nth-child(5)").hasClass('td_c_ip') && !$(nRow).find("td:nth-child(6)").hasClass('td_c_ud'))
                        {
                            $(nRow).find("td:nth-child(5)").addClass('td_c_ip');
                            $(nRow).find("td:nth-child(6)").addClass('td_c_ud');

                            get_idm_data(nRow);
                        }
                    }

                    $(nRow).find("td:nth-child(1)").addClass('td_mi');

                    if ($(nRow).find("td:nth-child(1) img").hasClass('info'))
                    {
                        get_agent_info($(nRow).find("td:nth-child(1)"));
                    }

                    $(nRow).find('td:last').addClass('agent_actions');
                    $(nRow).find('a').off('click');
                    $(nRow).find('a').click(function() {
                        var id = $(this).attr("id");
                        get_action(id);
                    });
                }
            });
    </script>
    <?php
}
else if ($tab == '#tab2')
{   
    $ac_key = (empty($_POST['ac_key']) ) ? 0 : $_POST['ac_key'];
    
    try
    {
        // Agent.conf
        $conf_data = Ossec_agent::get_configuration_file($sensor_id);

        $xml_obj = new Xml_parser('key');
        $xml_obj->load_string($conf_data['data']);
        $array_oss_cnf = $xml_obj->xml2array();

        $agent_config = Ossec::get_nodes($array_oss_cnf, 'agent_config');

        $ac_keys[] = array();

        if (is_array($agent_config) && !empty($agent_config))
        {   
            foreach($agent_config as $k => $ac_data)
            {       
                unset($ac_data['@attributes']['key']);
                
                $keys = array_keys($ac_data['@attributes']);
                
                $ac_keys[$k] = $keys[0].' = "'.$ac_data['@attributes'][$keys[0]].'"';
            }
        }
            
        $syscheck = Ossec::get_nodes($array_oss_cnf, 'syscheck');
        
        $syscheck = $syscheck[$ac_key]; 
            
        $directories = Ossec::get_nodes($syscheck, 'directories');
        $wentries    = Ossec::get_nodes($syscheck, 'windows_registry');
        $reg_ignores = Ossec::get_nodes($syscheck, 'registry_ignore');
        $ignores     = Ossec::get_nodes($syscheck, 'ignore');
        
        
        $frequency       = Ossec::get_nodes($syscheck, 'frequency');
        $frequency       = $frequency[0][0];
        
        $scan_day        = Ossec::get_nodes($syscheck, 'scan_day');
        $scan_day        = $scan_day[0][0];
        
        
        $scan_time       = Ossec::get_nodes($syscheck, 'scan_time');
        $scan_time       = $scan_time[0][0];
        $st              = (!empty($scan_time)) ? explode(':', $scan_time) : array();
        
        $auto_ignore     = Ossec::get_nodes($syscheck, 'auto_ignore');
        $auto_ignore     = (empty($auto_ignore[0][0])) ? 'no' : $auto_ignore[0][0];
        
        $alert_new_files = Ossec::get_nodes($syscheck, 'alert_new_files');
        $alert_new_files = (empty($alert_new_files[0][0]) ) ? 'no' : $alert_new_files[0][0];
        
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
        <form name='form_syscheck' id='form_syscheck'>
            <?php
            if (count($ac_keys) > 1)
            {
                ?>
                <div class='cont_sys_ac'>
                    <label for='ac_key'><?php echo _('Select agent config block')?>:</label>
                    <select id='ac_key' name='ac_key'>
                        <?php
                        foreach ($ac_keys as $ac_index => $ac_data)
                        {
                            $selected = ($ac_index == $ac_key) ? 'selected="selected"' : '';
                            echo "<option $selected value='$ac_index'>$ac_data</option>";   
                        }
                        ?>
                    </select>
                </div>   
                <?php
            }
            ?>
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
                                                    $selected = ( $k == $scan_day ) ? "selected='selected'" : "";
                                                    echo "<option value='$k' $selected>$v</option>";
                                                }
                                            ?>
                                        </select>
                                    </td>
                                </tr>
                                
                                <tr>    
                                    <th><?php echo _('Alert new files')?></th>
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
                                    <th><?php echo _('Auto ignore')?></th>
                                    <td>
                                        <select id='auto_ignore' name='auto_ignore'>
                                            <?php 
                                            foreach ($yes_no as $k => $v)
                                            {
                                                $selected = ( $k == $auto_ignore ) ? "selected='selected'" : "";
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
                                                $selected = ( $k == $scan_on_start ) ? "selected='selected'" : "";
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
                    <input type='button' class='small' id='send_2' value='<?php echo _('Save')?>' onclick="save_agent_conf();"/>
                </div>
            </div>
            
            <div class='cont_sys'>
                <table class='t_syscheck' id='table_sys_wentries'>
                    <thead>
                        <tr>
                            <td class='sec_title' colspan='2'><?php echo _('Windows registry entries monitored (Windows system only)')?></td>
                        </tr>
                        <tr>    
                            <th class='sys_wentry'><?php echo _('Windows registry entry')?></th>
                            <th class='sys_actions'><?php echo _('Actions')?></th>
                        </tr>
                    </thead>    
                    
                    <tbody id='tbody_swe'>
                    <?php
                
                    if (empty($wentries)) 
                    {
                        $k           = 0;
                        $wentries = array(array('@attributes' => NULL, '0' => NULL));
                    }
                    
                    foreach ($wentries as $k => $v)
                    {                                                       
                        ?>
                        <tr class='went_tr' id='went_<?php echo $k?>'>
                            <td style='text-align: left;'><input type='text' class='sreg_ignore' name='<?php echo $k?>_value_went' id='<?php echo $k?>_value_went' value='<?php echo $wentries[$k][0]?>'/></td>
                            <td class='center'>
                                <a onclick='delete_row("went_<?php echo $k?>", "delete_wentry");'><img src='../vulnmeter/images/delete.gif' align='absmiddle'/></a>
                                <a onclick='add_row("went_<?php echo $k?>", "add_wentry");'><img src='images/add.png' align='absmiddle'/></a>   
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                </table>
                
                <div class='cont_savet2'>
                    <input type='button' class='small' id='send_3' value='<?php echo _('Save')?>' onclick="save_agent_conf();"/>
                </div>
            </div>
                
            
            <div class='cont_sys'>
                <table class='t_syscheck' id='table_sys_reg_ignores'>
                    <thead>
                        <tr>
                            <td class='sec_title' colspan='2'><?php echo _('Registry entries ignored')?></td>
                        </tr>
                        <tr>    
                            <th class='sys_reg_ignores'><?php echo _('Registry entry ignored')?></th>
                            <th class='sys_actions'><?php echo _('Actions')?></th>
                        </tr>
                    </thead>
                    
                    <tbody id='tbody_sri'>
                    <?php
                    
                    if (empty($reg_ignores)) 
                    {
                        $k           = 0;
                        $reg_ignores = array(array('@attributes' => NULL, '0' => NULL));
                    }
                    
                    foreach ($reg_ignores as $k => $v)
                    {
                        ?>
                        <tr class='regi_tr' id='regi_<?php echo $k?>'>
                            <td style='text-align: left;'>
                                <input type='text' class='sreg_ignore' name='<?php echo $k?>_value_regi' id='<?php echo $k?>_value_regi' value='<?php echo $reg_ignores[$k][0]?>'/>
                            </td>
                            <td class='center'>
                                <a onclick='delete_row("regi_<?php echo $k?>", "delete_reg_ignore");'><img src='../vulnmeter/images/delete.gif' align='absmiddle'/></a>
                                <a onclick='add_row("regi_<?php echo $k?>", "add_reg_ignore");'><img src='images/add.png' align='absmiddle'/></a>   
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                </table>
                
                <div class='cont_savet2'>
                    <input type='button' class='small' id='send_4' value='<?php echo _('Save')?>' onclick="save_agent_conf();"/>
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
                            $directories = array(array('@attributes' => NULL, '0' => NULL));
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
                                    $checked = ( !empty($directories[$k]['@attributes'][$j]) ) ? 'checked="checked"' : '';
                                    echo "<td style=' text-align:center;'><input type='checkbox' id='".$j.'_'.$k.'_'.$i."' name='".$j.'_'.$k.'_'.$i."' $checked/></td>";
                                }
                                ?>
                                
                                <td class='center'>
                                    <a onclick='delete_row("dir_<?php echo $k?>", "delete_directory");'><img src='../vulnmeter/images/delete.gif' align='absmiddle'/></a>
                                    <a onclick='add_row("dir_<?php echo $k?>", "add_directory");'><img src='images/add.png' align='absmiddle'/></a> 
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
                
                <div class='cont_savet2'>
                    <input type='button' class='small' id='send_5' value='<?php echo _('Save')?>' onclick="save_agent_conf();"/>
                </div>
            </div>
            
            <div class='cont_sys'>
                <?php
                if (empty($ignores))
                {
                    $k       = 0;
                    $ignores = array(array('@attributes' => NULL, '0'=> NULL));
                }
                ?>
                <table class='t_syscheck' id='table_sys_ignores'>
                    <thead>
                        <tr>
                            <td class='sec_title' colspan='3'><?php echo _('Files/Directories ignored')?></td>
                        </tr>
                        <tr>    
                            <th rowspan='2'><?php echo _('Files/Directories')?></th>
                            <th><?php echo _('Parameters')?></th>
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
                                <a onclick='delete_row("ign_<?php echo $k?>", "delete_ignore");'><img src='../vulnmeter/images/delete.gif' align='absmiddle'/></a>
                                <a onclick='add_row("ign_<?php echo $k?>", "add_ignore");'><img src='images/add.png' align='absmiddle'/></a>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                </table>

                <div class='cont_savet2'>
                    <input type='button' class='small' id='send_6' value='<?php echo _('Save')?>' onclick="save_agent_conf();"/>
                </div>
            </div>
            
        </form>
        <?php

    }
    catch(Exception $e)
    {
        echo "2###"._('We found the followings errors:')."<div style='padding-left: 15px; text-align:left;'>".$e->getMessage()."</div>";
    }
}
else if ($tab == '#tab3')
{   
    try
    {
        $conf_data = Ossec_agent::get_configuration_file($sensor_id);
        
        echo "1###".$conf_data['data'];
    }
    catch(Exception $e)
    {
        echo "2###"._('We found the followings errors:')."<div style='padding-left: 15px; text-align:left;'>".$e->getMessage()."</div>";
    }
}
else
{
    echo "2###"._('We found the followings errors').": <div style='padding-left: 15px; text-align:left;'>"._('Illegal action')."</div>";
}
?>