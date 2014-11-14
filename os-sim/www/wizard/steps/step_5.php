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
    throw new Exception('There was an unexpected error');
}

$config  = new Config();
$otx_key = $config->get_conf("open_threat_exchange_key");

$v_tag   = (Session::is_pro() ? "USM" : "OSSIM");
?>

<script type='text/javascript'>

    function load_js_step()
    {
    
        load_handler_step_otx();        

    }

</script>

<div id='step_6' class='step_container'>


    <div id='w_otx_step_1'>
        
        <div class='wizard_title' style='padding-left:2px;'>
            <?php echo _('Join the Open Threat Exchange - Security for You, Powered by All') ?>
        </div>
            
        <div id='left_column'>
            
            <span class='bold'>
                <?php echo _('What is OTX?') ?>
            </span>
            
            <p>
                <?php echo _('AlienVault Open Threat Exchange (OTX&trade;) is an open threat information sharing and analysis network, created to put effective security measures within the reach of all organizations. Unlike invitation-only threat sharing networks, OTX provides real-time, actionable information to all who want to participate.') ?>
            </p>
            
            
            <span class='bold'>
                <?php echo _('Why should I join?') ?>
            </span>
            <p>
                <?php echo _('With AlienVault OTX, you can achieve a preventative response to changes in the threat landscape by learning how attackers are targeting others. Armed with real-time, detailed security event information, you can update your defenses to avoid becoming a victim yourself.') ?>
            </p>

            
            <span class='bold'>
                <?php echo _('How does it work?') ?>
            </span>
            <p>
            <?php 
                $_txt = _('Enabling OTX in your %s installation will allow you to automatically share anonymized threat information with the OTX community. In return, you will receive threat updates every 30 minutes.');
                
                echo sprintf($_txt, $v_tag);
                    
            ?> 
                
                <a id='otx_data_link' href='javascript:;'><?php echo _('See what data is being sent to OTX.') ?></a>
            </p>
            
                       
            <p id='otx_enable_p'>
                <?php echo _('To enable OTX in your installation, sign up for an AlienVault OTX community account. You will receive a token to link your installation to OTX.') ?>
            </p>
    
            <button id='b_get_otx_token' class='av_b_secondary'><?php echo _('Sign up Now') ?></button>
        
            <p>
                <?php echo _("Enter your token below to automatically share anonymous details about threats discovered on your network.") ?>
            </p>
        
            <input type='text' id='w_otx_token' placeholder="<?php echo _("Enter Token") ?>" value="<?php echo $otx_key ?>">
        
        </div>
        
        <div id='right_column'>
            <img id='otx_register_img' src='img/otx_register.png' />
        </div>
        
        
        <div class='clear_layer'> </div>

    
    </div>
    
    <div id='w_otx_step_2'>
        
        <div id='w_otx_2_skip'>
        
            <div class='wizard_title'>
                <?php echo _("Don't Worry, You Can Join the AlienVault Open Threat Exchange at Any Time!") ?>
            </div>
            
            <div class='wizard_subtitle'>
            <?php 
                $_txt = _("You've chosen not to join the Open Threat Exchange at this time. You can join the AlienVault OTX community through your AlienVault %s web interface at any time.");
                
                echo sprintf($_txt, $v_tag);
            ?>
            </div>
            
            <br/><br/>
            
        </div>
        
        <div id='w_otx_2_register'>
        
            <div class='wizard_title'>
                <?php echo _('Thank you for joining the Open Threat Exchange!') ?>
            </div>
            
        </div>
        
        <div class='wizard_subtitle'>
            <?php echo sprintf(_('Click "Finish" to start using AlienVault %s'), $v_tag) ?>
        </div>
    
    </div>

</div>


<!-- THE BUTTONS HERE -->
<div class='wizard_button_list'>

    <a href='javascript:;' id='prev_step' class='av_l_main'><?php echo _('Back') ?></a>
    <a href='javascript:;' id='otx_back'  class='av_l_main'><?php echo _('Back') ?></a>

    <button id='next_step'  class="fright finish_wizard"><?php echo _('Finish') ?></button>
    
    <button id='w_otx_next' class="fright" <?php echo ($otx_key != '') ? '' : 'disabled' ?> ><?php echo _('Next') ?></button>
    <button id='w_otx_skip' class="fright av_b_secondary" data-type='skip'><?php echo _('Skip this Step') ?></button>

</div>
