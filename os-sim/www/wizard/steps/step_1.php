<?php
/**
 * snap_load.php
 * 
 * File snap_load.php is used to:
 * - Response ajax call from index.php
 * - Fill the data into asset details Snapshot section
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
 * @package    ossim-framework\Assets
 * @autor      AlienVault INC
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt
 * @copyright  2003-2006 ossim.net
 * @copyright  2007-2013 AlienVault
 * @link       https://www.alienvault.com/
 */
 
require_once 'av_init.php';

//Checking active session
Session::useractive();

//Checking permissions
if (!Session::am_i_admin())
{
    echo _('You do not have permissions to see this section');
    
    die();
}

/************************************************************************************************/
/************************************************************************************************/
/***  This file is includen in step_loader.php hence the wizard object is defined in $wizard  ***/
/***                         database connection is stored in $conn                           ***/
/************************************************************************************************/
/************************************************************************************************/

if(!$wizard instanceof Welcome_wizard)
{
    throw new Exception("There was an error, the Welcome_wizard object doesn't exist");
}

$interfaces = array();

try
{
    $interfaces = Welcome_wizard::get_interfaces();
}
catch(Exception $e)
{
    $config_nt = array(
        'content' => $e->getMessage(),
        'options' => array (
            'type'          => 'nf_error',
            'cancel_button' => true
        ),
        'style'   => 'margin:10px auto;width:50%;text-align:center;padding:0 10px;z-index:999'
    );
    
    $nt = new Notification('nt_notif', $config_nt);
    $nt->show();
}

$v_short    = (Session::is_pro() ? "USM" : "OSSIM");

$text_descr = _("The network interfaces in AlienVault %s can be configured to run Network Monitoring or as Log Collection & Scanning. Once you've configured the interfaces you'll need to ensure that the networking is configured appropriately for each interface so that AlienVault %s is either receiving data passively or has the ability to reach out to the desired network.");

$text_descr = sprintf($text_descr, $v_short, $v_short); 

?>

<script type='text/javascript'>
    
    var __nic ,__n_role ,__n_ip, __n_mask = null;
    var __nic_state = false;
    
    function load_js_step()
    {
        load_handler_step_interfaces();   
        
        <?php 
        if (count($interfaces) > 0)
        {
        ?>      
            get_interfaces_activity();
            
        <?php
        }
        ?>
    }
    
</script>

<div id='step_interfaces' class='step_container'>

    <div class='wizard_title'>
        <?php echo _("Configure Network Interfaces") ?>
    </div>
    
    <div class='wizard_subtitle'>
        <?php echo $text_descr ?>
    </div>
    
    
    <table class='wizard_table table_data'>
        <tr>
            <th class='left'><?php echo _('NIC') ?></th>
            <th><?php echo _('Purpose') ?></th>
            <th><?php echo _('IP Address') ?></th>
            <th><?php echo _('Status') ?></th>
        </tr>
        
        <?php
        foreach ($interfaces as $id => $interface)
        {
            $ip     = ($interface['ip'] == '') ? _('N/A') : $interface['ip'];
            $role   = $interface['role'];
            
            //Classes
            $pencil = ''; //Show the pencil
            $led_y  = 'hide'; //Hide Led
            $led_n  = ''; //Show a dash instead of a Led
            
            if ($role != 'log_management')
            {
                $interface['ip']      = '';
                $interface['netmask'] = '';
                $pencil = 'hide';  //Hide pencil if it is not Log collection
            }
            
            if ($role == 'monitoring')
            {
                $led_y  = ''; //Only show led when it is promisc interface
                $led_n  = 'hide';
            }
        ?>
        
            <tr id='nic_<?php echo $id ?>' class='nic_item' data-nic='<?php echo $id ?>'>
            
                <td class='left'> 
                    <?php echo $id ?>
                </td>
                
                <td>
                
                <?php
                if ($role == 'admin')
                {
                ?>
                    <select class="select_purpose" disabled="disabled">
                        <option value=""><?php echo _('Management') ?></option>
                    </select>
                    
                <?php
                }
                else
                {
                ?>
                    <select class="nic_roles select_purpose">
                        <option <?php echo ($role == 'disabled') ? 'selected' : '' ?> value="disabled">
                            <?php echo _('Not in Use') ?>
                        </option>
                        <option <?php echo ($role == 'monitoring') ? 'selected' : '' ?> value="monitoring">
                            <?php echo _('Network Monitoring') ?>
                        </option>
                        <option <?php echo ($role == 'log_management') ? 'selected' : '' ?> value="log_management">
                            <?php echo _('Log Collection & Scanning') ?>
                        </option>
                    </select>
                    
                <?php } ?> 
                
                </td>
                
                <td style='position:relative;'>
                
                    <span id='nic_ip' data-ip='<?php echo $interface['ip'] ?>' data-mask='<?php echo $interface['netmask'] ?>'>
                        <?php echo $ip ?>
                    </span>
                    
                    <img class="edit_ip_nic <?php echo $pencil ?>" src="/ossim/dashboard/pixmaps/edit.png" />
                    
                </td>
                
                <td>
                    <div id='indicator_yes' class="<?php echo $led_y ?>">
                        <span class='led_container'>
                            <span class='wizard_led led_gray'>&nbsp;</span>
                            <img  class='wizard_led_loader' src='/ossim/pixmaps/loader.gif'/>
                        </span>
                    </div>
                    <div id='indicator_no' class="<?php echo $led_n ?>">
                        -
                    </div>
                </td>
                
            </tr>
            
        <?php
        }
        ?>
        
    </table>
    
    <div id='nic_legend'>
        
        <div id='legend_title'><?php echo _('Information') ?></div>
        <ul>
            
            <li>
                <strong><?php echo _('Management:') ?> </strong>
                <?php
                    $text = _('The Management interface was configured on the %s Console and allows you to connect to the web UI. This interface cannot be changed from the web UI.');
                    
                    $text = sprintf($text, $v_short);
                    
                    echo $text;

                 ?>
            </li>
            
            <li>
                <strong><?php echo _('Network Monitoring:') ?> </strong>
                <?php 
                    $msg = _('Passively listen for network traffic. Interface will be set to promiscuous mode. Requires a network tap or span.%s See Instructions %s on how to setup a network tap or span.');
                    
                    $s_1 = "<a href='https://www.alienvault.com/help/product/gsw' class='av_l_main' target='_blank'>";
                    $s_2 = "</a>";
                    
                    echo sprintf($msg, $s_1, $s_2);
                ?>
                
            </li>
            
            <li>
                <strong><?php echo _('Log Collection & Scanning:') ?> </strong>
                <?php echo _('Collect or receive logs from your assets, run an asset scan, or deploy the HIDS agent. Requires routable access to your networks.') ?>
            </li>
            
            <li>
                <strong><?php echo _('Not in Use:') ?> </strong>
                <?php echo _('Use this option if you do not want to use one of the network interfaces.') ?>
            </li>
            
        </ul>

    </div>
    
    
<div class='clear_layer'></div>

</div>

<a id='dialog_link' href='#ip_dialog'></a>
<div id='ip_dialog_wrapper'>
    <div id='ip_dialog'>
    
        <div id='dialog_title' class='wizard_title'>
            <?php echo _('IP Address & Netmask') ?>
        </div>
        
        <div id='dialog_message'></div>
        <input id='insert_ip' type='text' value='' placeholder="<?php echo _('IP Address') ?>">
        <br/>
        <input id='insert_mask' type='text' value='' placeholder="<?php echo _('Netmask') ?>">

        <div id='opts'> 
            <input id='cancel' class='av_b_secondary' type='button' value="<?php echo _('Cancel') ?>">
            <input id='ok'type='button' value="<?php echo _('Ok') ?>" >
        </div>
        
    </div>
</div>


<div class='wizard_button_list'>

    <button id='next_step' class="fright"><?php echo _('Next') ?></button>

</div>
