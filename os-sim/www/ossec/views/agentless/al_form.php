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


$sensor_id = GET('sensor');
$ip        = GET('ip');

ossim_valid($sensor_id, OSS_HEX,                        'illegal:' . _('Sensor'));
ossim_valid($ip,        OSS_IP_ADDR, OSS_NULLABLE,      'illegal:' . _('IP Address'));


$db   = new ossim_db();
$conn = $db->connect();

if (!ossim_error())
{
    if (!Ossec_utilities::is_sensor_allowed($conn, $sensor_id))
    {
        ossim_set_error(_('Error! Sensor not allowed'));
    }
}

if (ossim_error())
{
    $critical_error = ossim_get_error();
    ossim_clean_error();
}
else
{
    if ($ip != '' && $sensor_id != '')
    {
        $edit_mode = TRUE;
        $agentless = Ossec_agentless::get_object($conn, $sensor_id, $ip);
    
        if (is_object($agentless) && !empty($agentless))
        {
            $ip          = $agentless->get_ip();
            $hostname    = $agentless->get_hostname();
            $user        = $agentless->get_user();
            $descr       = $agentless->get_descr(FALSE);
            $use_su      = $agentless->get_use_su();
    
            $error_m_entries = '';
            try
            {
                $monitoring_entries = Ossec_agentless::get_list_m_entries($conn, $sensor_id, " AND ip = '$ip'");
            }
            catch(Exception $e)
            {
                $error_m_entries = $e->getMessage();
            }
        }
        else
        {
            $critical_error = _('No agentless found');
        }
    }
    else
    {
        $edit_mode          = FALSE;
        $monitoring_entries = array();
    }
    
    $sensor_name = Av_sensor::get_name_by_id($conn, $sensor_id);
}

$array_types = array ('ssh_integrity_check_bsd'     => 'Integrity Check BSD',
                      'ssh_integrity_check_linux'   => 'Integrity Check Linux',
                      'ssh_generic_diff'            => 'Generic Command Diff',
                      'ssh_pixconfig_diff'          => 'Cisco Config Check',
                      'ssh_foundry_diff'            => 'Foundry Config Check',
                      'ssh_asa-fwsmconfig_diff'     => 'ASA FWSMconfig Check');

$db->close();

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title><?php echo _('OSSIM Framework');?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    
    <?php
        //CSS Files
        $_files = array(
            array('src' => 'jquery-ui.css',                         'def_path' => TRUE),
            array('src' => 'jquery.dataTables.css',                 'def_path' => TRUE),
            array('src' => 'av_common.css',                         'def_path' => TRUE),
            array('src' => 'tipTip.css',                            'def_path' => TRUE)
        );

        Util::print_include_files($_files, 'css');

        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                 'def_path' => TRUE),
            array('src' => 'jquery-ui.min.js',              'def_path' => TRUE),
            array('src' => 'jquery.elastic.source.js',      'def_path' => TRUE),
            array('src' => 'jquery.tipTip.js',              'def_path' => TRUE),
            array('src' => 'utils.js',                      'def_path' => TRUE),
            array('src' => 'token.js',                      'def_path' => TRUE),
            array('src' => 'notification.js',               'def_path' => TRUE),
            array('src' => 'ajax_validator.js',             'def_path' => TRUE),
            array('src' => 'messages.php',                  'def_path' => TRUE),
            array('src' => 'jquery.dataTables.js',          'def_path' => TRUE),
            array('src' => '/ossec/js/agentless.js.php',    'def_path' => FALSE)
        );
        
        Util::print_include_files($_files, 'js');
        
    ?>
    <script type="text/javascript">
        
        $(document).ready(function()
        {
            var data = 
            {
                'entries': <?php echo json_encode($monitoring_entries) ?>
            }
            init_agentless_form(data);
        });

    </script>
</head>

<body>

<?php
    //Local menu
    include_once AV_MAIN_ROOT_PATH.'/local_menu.php';
?>

<div class='c_back_button' style='display:block;'>
     <input type='button' class="av_b_back" onclick="document.location.href='/ossim/ossec/views/agentless/agentless.php';return false;"/>
</div>

<?php

if (!empty($critical_error))
{
    Util::print_error($critical_error);
    Util::make_form('POST', '/ossim/ossec/agentless.php');
}
else
{
?>
    <div class="legend">
        <?php echo _('Values marked with (*) are mandatory');?>
    </div>
    
    <div id='al_notif'></div>
    
    <form method="POST" name="al_save_form" id="al_save_form" action="/ossim/ossec/controllers/agentless/al_save.php">
    <table id='table_form'>
        <tr>
            <td class='subsection_1'>

                <table width='100%'>
            
                    <tr>
                        <td colspan='2' class='headerpr'><span><?php echo _('Agentless Data Configuration')?></span></td>
                    </tr>

                    <tr>
                        <th>
                            <label for='hostname'><?php echo _('Hostname') . required();?></label>
                        </th>
                        <td class="left">
                            <input type="text" class='vfield' name="hostname" id="hostname" value="<?php echo $hostname ?>"/>
                        </td>
                    </tr>

                    <tr>
                        <th>
                            <label for='ip'><?php echo _('IP');?></label>
                        </th>
                        <td class="left">
                            <input type="text" class='vfield' name="ip" id="ip" <?php echo ($edit_mode) ? 'disabled readonly' : '' ?>  value="<?php echo $ip ?>"/>
                        </td>
                    </tr>

                    <tr>
                        <th>
                            <label for='sensor'><?php echo _('Sensor'); ?></label>
                        </th>
                        <td class="left">
                            <div id="sensor_back" class='bold'><?php echo $sensor_name ?></div>
                            <input type="hidden" class='vfield' name="sensor" id="sensor" value="<?php echo $sensor_id ?>"/>
                        </td>
                    </tr>

                    <tr>
                        <th>
                            <label for='user'><?php echo _('SSH Username') . required() ?></label>
                        </th>
                        <td class="left">
                            <input type="text" class='vfield' name="user" id="user" value="<?php echo $user ?>"/>
                        </td>
                    </tr>

                    <tr>
                        <th>
                            <label for='pass'><?php echo _('SSH Password') . required() ?></label>
                        </th>
                        <td class="left">
                            <input type="password" class='vfield' name="pass" id="pass" value="" autocomplete="off"/>
                        </td>
                    </tr>

                    <tr>
                        <th>
                            <label for='passc'><?php echo _('Confirm SSH Password') . required() ?></label>
                        </th>
                        <td class="left">
                            <input type="password" class='vfield' name="passc" id="passc" value="" autocomplete="off"/>
                            <div class='al_advice'>
                                <?php echo _('(*) If you want to use public key authentication instead of passwords, you need to provide NOPASS as Normal Password')?>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th>
                            <label for='ppass'><?php echo _('Privileged Password')  ?></label>
                        </th>
                        <td class="left">
                            <input type="password" class='vfield' name="ppass" id="ppass" value="" autocomplete="off"/>
                        </td>
                    </tr>

                    <tr>
                        <th>
                            <label for='ppassc'><?php echo _('Confirm Privileged Password') ?></label>
                        </th>
                        <td class="left">
                            <input type="password" class='vfield' name="ppassc" id="ppassc" value="" autocomplete="off"/>
                            <div class='al_advice'>
                                <?php echo _("(*) If you want to add support for \"su\", you need to provide Privileged Password")?>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <th>
                            <label for='use_su'><?php echo _('Enable use_su option') ?></label>
                        </th>
                        <td class="left">
                            <input type="checkbox" class='vfield' name="use_su" id="use_su" value="1" <?php echo ($use_su) ? "checked" : "" ?>/>
                        </td>
                    </tr>

                    <tr>
                        <th>
                            <label for='descr'><?php echo _('Description') ?></label>
                        </th>
                        <td class="left nobborder">
                            <textarea name="descr" id="descr" class='vfield'><?php echo $descr ?></textarea>
                        </td>
                    </tr>
                    
                </table>
                
            </td>
            
            <td class='subsection_2'>
                <table width='100%'>
                    <tr>
                        <td colspan='2' class='headerpr'><span><?php echo _("Monitoring Entries Options")?></span></td>
                    </tr>

                    <tr>
                        <th>
                            <label for='type'><?php echo _('Type'). required();?></label>
                        </th>
                        <td class="left">
                            <select name="type" id="type">
                            <?php
                            foreach ($array_types as $k => $v)
                            {
                                echo "<option value='$k'>$v</option>";
                            }   
                            ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th>
                            <label for='frequency'><?php echo _('Frequency') . required();?></label>
                        </th>
                        <td class="left">
                            <input type="text" name="frequency" id="frequency" value="86400"/>
                        </td>
                    </tr>

                    <tr>
                        <th>
                            <label for='state'><?php echo _('State'); ?></label>
                        </th>
                        <td class="left">
                            <div id="state_txt" class='bold'><?php echo _('Periodic')?></div>
                            <input type="hidden" class="state" id='state' name='state' value="periodic"/>
                        </td>
                    </tr>

                    <tr>
                        <th>
                            <label for='arguments'><?php echo _('Arguments');?></label>
                        </th>
                        <td class="ct_mandatory nobborder left">
                            <?php
                            $arg_info = "<table class='ct_opt_format' border='1'>
                                            <tbody>
                                                <tr>
                                                    <td class='ct_bold noborder center'><span class='ct_title'>"._('Please Note').":</span></td>
                                                </tr>
                                                <tr>
                                                    <td class='noborder'>
                                                        <div class='ct_opt_subcont'>
                                                            <img src='".OSSIM_IMG_PATH."/bulb.png' align='absmiddle' alt='Bulb'/>
                                                            <span class='ct_bold'>"._("If type value is Generic Command Diff").":</span>
                                                            <div class='ct_pad5'>
                                                                <span>". _("Ex.: ls -la /etc; cat /etc/passwd")."</span>
                                                            </div>
                                                        </div>
                                                        <br/>
                                                        <div class='ct_opt_subcont'>
                                                            <img src='".OSSIM_IMG_PATH."/bulb.png' align='absmiddle' alt='Bulb'/>
                                                            <span class='ct_bold'>". _("Other cases").":</span>
                                                            <div class='ct_pad5'><span>"._("Ex.: bin /etc /sbin")."</span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>";
                            ?>
                            <textarea name="arguments" id="arguments" title="<?php echo $arg_info?>">/bin /etc /sbin</textarea>
                        </td>
                    </tr>

                    <tr>
                        <td colspan='2' style='padding:5px 5px 5px 0px;' class='right nobborder'>
                            <input type="button" class="small av_b_secondary add" name='add' id='add' value="<?php echo _('Add')?>"/>
                        </td>
                    </tr>

                    <tr>
                        <td colspan='2'>
                            <table id='monitoring_table' class='table_data'>
                                <thead class='center'>
                                    <tr>
                                        <th colspan='5' class='headerpr center' style='padding: 3px 0px;'><?php echo _('Monitoring entries added')?></th>
                                    </tr>
                                    <tr>
                                        <th class="al_type"><?php echo _('Type')?></th>
                                        <th class="al_frequency"><?php echo _('Frequency')?></th>
                                        <th class="al_state"><?php echo _('State')?></th>
                                        <th class="al_arguments"><?php echo _('Arguments')?></th>
                                        <th class="al_actions"><?php echo _('Actions')?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>

        <tr>
            <td colspan="2">
                <input type="button" class="update" id='send' value="<?php echo _('Update')?>"/>
            </td>
        </tr>
        
    </table>
    </form>
    <?php
    }
    ?>
</body>
</html>
