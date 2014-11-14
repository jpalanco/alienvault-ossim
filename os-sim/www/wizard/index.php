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

//Checking active session
Session::useractive();

//Getting config variables related to the wizard
$vars = Welcome_wizard::get_wizard_config_vars();
$op   = intval($vars['op']);


/*
If I'm not admin or the option is not valid (op < 1) let's redirect to ossim/
If we have option 2 this means thar we are coming back from ossim to wizard so we change to op=1 to always load the wizard.
*/
if (!Session::am_i_admin() || $op < 1)
{
    header("Location: /ossim/");
}
elseif($op == 2)
{
    $conf = new Config();
    $conf->update('start_welcome_wizard', 1);
}

//Retrieving the wizard object
$wizard = Welcome_wizard::get_instance(); 

//If we can get it back, we recover the previous state
if (is_object($wizard) && $wizard->get_current_step() > 0)
{
    $start_wizard  = FALSE;
    
    $step          = $wizard->get_current_step();
    $actived       = $wizard->get_last_completed_step();
    $finish_wizard = $wizard->is_wizard_finish();
}

else //If we cannot, we start the wizard
{
    $step         = 1;
    $function_js  = "start_new_wizard();";
    $start_wizard = TRUE;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <title> <?php echo _('AlienVault ' . (Session::is_pro() ? 'USM' : 'OSSIM')); ?> </title>
        <link rel="Shortcut Icon" type="image/x-icon" href="/ossim/favicon.ico">
        <meta http-equiv="Content-Type" content="text/html;charset=iso-8859-1"/>
        <meta http-equiv="Pragma" content="no-cache"/>
        
        <?php
    
            //CSS Files
            $_files = array(
                array('src' => 'av_common.css?only_common=1',           'def_path' => TRUE),
                array('src' => 'home.css',                              'def_path' => TRUE),
                array('src' => 'jquery-ui.css',                         'def_path' => TRUE),
                array('src' => 'tipTip.css',                            'def_path' => TRUE),
                array('src' => 'lightbox.css',                          'def_path' => TRUE),
                array('src' => 'flipswitch.css',                        'def_path' => TRUE),
                array('src' => 'jquery.dataTables.css',                 'def_path' => TRUE),
                array('src' => 'tree.css',                              'def_path' => TRUE),
                array('src' => 'jquery.select.css',                     'def_path' => TRUE),
                array('src' => 'jquery.vex.css',                        'def_path' => TRUE),
                array('src' => '/fancybox/jquery.fancybox-1.3.4.css',   'def_path' => TRUE),
                array('src' => '/wizard/wizard.css',                    'def_path' => TRUE)
            );

            Util::print_include_files($_files, 'css');


            //JS Files
            $_files = array(
                array('src' => 'jquery.min.js',                                 'def_path' => TRUE),
                array('src' => 'jquery-ui.min.js',                              'def_path' => TRUE),
                array('src' => 'utils.js',                                      'def_path' => TRUE),
                array('src' => 'notification.js',                               'def_path' => TRUE),
                array('src' => 'token.js',                                      'def_path' => TRUE),
                array('src' => 'jquery.tipTip.js',                              'def_path' => TRUE),
                array('src' => 'jquery.placeholder.js',                         'def_path' => TRUE),
                array('src' => 'jquery.dataTables.js',                          'def_path' => TRUE),
                array('src' => 'jquery.dataTables.plugins.js',                  'def_path' => TRUE),
                array('src' => 'jquery.dynatree.js',                            'def_path' => TRUE),
                array('src' => 'lightbox.js',                                   'def_path' => TRUE),
                array('src' => 'jquery.select.js',                              'def_path' => TRUE),
                array('src' => 'jquery.vex.js.php',                             'def_path' => TRUE),
                array('src' => 'av_plugin_select.js.php',                       'def_path' => TRUE),
                array('src' => '/fancybox/jquery.fancybox-1.3.4.pack.js',       'def_path' => TRUE),
                array('src' => '/wizard/js/wizard.js.php',                      'def_path' => FALSE),
                array('src' => '/wizard/js/wizard_actions.js.php',              'def_path' => FALSE)
            );
    
            Util::print_include_files($_files, 'js');
    
        ?>

        <script type="text/javascript" src="https://www.alienvault.com/product/help/ping.js"></script>
    
        <script type='text/javascript'>
            
            var __timeout      = null;
            var __current_step = <?php echo intval($step) ?>;
            
            $(document).ready(function() 
            {
                <?php 
                //Start a new wizard
                if ($start_wizard)
                {
                ?>
                    start_new_wizard();
                 
                <?php   
                }
                //Finish screen wizard when there are no alarms
                elseif ($finish_wizard)
                {
                ?>
                    finish_wizard();
                 
                <?php 
                }
                //Resume wizard
                else
                {
                ?>                    
                    resume_wizard(__current_step, <?php echo intval($actived) ?>);
                 
                <?php   
                }
            	?>
            	
            	//Next button handler
            	$('#next_step').off('click');
            	
            	$(document).on('click', '#next_step', function()
                {
                    if (!$(this).hasClass('wizard_off'))
                    {
                        next_step();
                    }
                    
                });
                
                //Prev button handler
                $('#prev_step').off('click');
                
                $(document).on('click', '#prev_step', function()
                {
                    prev_step();
                });
                
                
                //Finish button handler
                $('#wizard_exit').off('click');
                
                $(document).on('click', '#wizard_exit', function()
                {
                    exit_wizard(0, false);
                });
                
                $(window).on('resize', function()
                {
                    adjust_container_height();    
                });
                
                $('.step_name').on('click', function()
                {
                    if ($(this).hasClass('av_l_disabled'))
                    {
                        return false;
                    }
                    
                    var step = $(this).parents('li').first().attr('id');
                    
                    change_step(step);
                    
                });
                         	
            });
            // End of document.ready
            
        </script>
        
    </head>
    <body>
    
        <div id='c_header'>
            <div id='header'>
                <div id='header_logo'>            
                    <img src="<?php echo AV_PIXMAPS_DIR?>/logo/<?php echo (Session::is_pro()) ? 'av_contrast_logo' : 'ossim_contrast_logo' ?>.png"/>
                </div>
     
                <div id='header_options'>
                    <span id='welcome'><?php echo _('Welcome')?> <span id='login_name'><?php echo Session::get_session_user()?></span></span>
                    |<a href='<?php echo  AV_MAIN_PATH ?>/session/login.php?action=logout'><?php echo _('Logout') ?></a>
                </div>
            </div>
        </div>
        
        <div id='c_wh_title' class='w_bg_body'>
            <div id='wh_title'>
                <span id='w_wtext'>
                    <?php echo _('Welcome to AlienVault ') . (Session::is_pro() ? ' USM' : ' OSSIM') ?>
                </span>
    
                <div id='wizard_notif'></div>
            </div>
        
        </div>
               
        <div id='c_content'>  

            
            <!-- Wizard Left Menu -->
            <div id='wizard_options'>

                <div id='wizard_title'>
                    <?php echo _("Let's Get Started") ?>
                    
                </div>
                
                <!-- Wizard path option will be load here -->
                <ul id='wizard_path_container'>
                    
                    <li id="1">
                        <div class="wizard_number">1</div>
                        <div class='step_name av_l_disabled av_link'>
                            <?php echo _('Network Interfaces') ?>
                        </div>
                    </li>
                    <li id="2">
                        <div class="wizard_number">2</div>
                        <div class='step_name av_l_disabled av_link'>
                            <?php echo _('Asset Discovery') ?>
                        </div>
                    </li>
                    <li id="3">
                        <div class="wizard_number">3</div>
                        <div class='step_name av_l_disabled av_link'>
                            <?php echo _('Deploy HIDS') ?>
                        </div>
                    </li>
                    <li id="4">
                        <div class="wizard_number">4</div>
                        <div class='step_name av_l_disabled av_link'>
                            <?php echo _('Log Management') ?>
                        </div>
                    </li>
                    <li id="5">
                        <div class="wizard_number">5</div>
                        <div class='step_name av_l_disabled av_link'>
                            <?php echo _('Join OTX') ?>
                        </div>
                    </li>
                </ul>



                <a id='wizard_exit' class='wizard_exit av_l_main' href='javascript:;'>
                    <?php echo _('Skip AlienVault Wizard') ?>
                </a>


            </div>

            <!-- Wizard content -->
            <div id='wizard_wrapper'>
                                    
                <div class='wrap'>
                          
                    <div id='wizard_loading'>
                        <div id='av_main_loader'></div>
                    </div>
                    
                    <!-- Wizard content will be load here -->
                    <div id='wizard_step'></div>
                
                </div>
                
            </div>

            <div class='clear_layer'></div>

        </div>
        
        <a id="start_wizard"  class="fancybox" href="/ossim/wizard/steps/first_step.php"></a>
        <a id="finish_wizard" class="fancybox" href="/ossim/wizard/steps/finish_wizard.php"></a>
        
    </body>
</html>