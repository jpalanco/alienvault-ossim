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

Session::useractive();

$pro = Session::is_pro();

if (Session::am_i_admin() && Welcome_wizard::run_welcome_wizard())
{
    header('Location: /ossim/wizard/');
}


/* Trial */
$trial_days       = Session::trial_days_to_expire();
$flag_trial_popup = FALSE;

if($pro && ($trial_days == 7 || $trial_days == 2))
{
    $db   = new ossim_db();
    $conn = $db->connect();

    $user   = Session::get_session_user();

    $config = new User_config($conn);
    $popup  = $config->get($user, 'popup', 'simple', "trial");


    if($trial_days == 7)
    {
        if($popup != '7days')
        {
            $flag_trial_popup = TRUE;
            $config->set($user, 'popup', '7days', 'simple', 'trial');
        }
    }
    elseif($trial_days == 2)
    {
        if($popup != '2days')
        {
            $flag_trial_popup = TRUE;
            $config->set($user, 'popup', '2days', 'simple', 'trial');
        }
    }

    $db->close();
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <title> <?php echo _("AlienVault " . ($pro ? "USM" : "OSSIM")) ?> </title>
        <link rel="Shortcut Icon" type="image/x-icon" href="/ossim/favicon.ico">

        <?php
            //CSS Files
            $_files = array(
                array('src' => 'av_common.css?only_common=1',   'def_path' => TRUE),
                array('src' => 'home.css',                      'def_path' => TRUE),
                array('src' => 'flexnav.css',                   'def_path' => TRUE),
                array('src' => 'lightbox.css',                  'def_path' => TRUE),
                array('src' => 'jquery.vex.css',                'def_path' => TRUE),
            );
            Util::print_include_files($_files, 'css');

            //JS Files
            $_files = array(
                array('src' => 'jquery.min.js',             'def_path' => TRUE),
                array('src' => 'jquery-ui.min.js',          'def_path' => TRUE),
                array('src' => 'jquery.cookie.js',          'def_path' => TRUE),
                array('src' => 'jquery.json-2.2.js',        'def_path' => TRUE),
                array('src' => 'jquery.sparkline.js',       'def_path' => TRUE),
                array('src' => 'jquery.spasticNav.js',      'def_path' => TRUE),
                array('src' => 'jquery.flexnav.js',         'def_path' => TRUE),
                array('src' => 'utils.js',                  'def_path' => TRUE),
                array('src' => 'lightbox.js',               'def_path' => TRUE),
                array('src' => 'purl.js',                   'def_path' => TRUE),
                array('src' => 'jquery.vex.js.php',         'def_path' => TRUE),
                array('src' => 'av_menu.js.php',            'def_path' => TRUE),
                array('src' => '/home/sidebar.js.php',      'def_path' => FALSE),
                array('src' => '/home/home.js.php',         'def_path' => FALSE)
            );

            Util::print_include_files($_files, 'js');
        ?>

        <!-- this script is to check internet connection -->
        <script type="text/javascript" src="https://www.alienvault.com/product/help/ping.js"></script>

        <script type="text/javascript">
            
            $(document).ready(function()
    		{        		
    		        		
    		    load_menu_scripts();

                <?php
                if ($flag_trial_popup)
                {
                ?>
                    /* Trial pop-up*/
                    var url = "<?php echo  AV_MAIN_PATH ?>/session/trial/trial_status.php?window=1";

                    setTimeout(function()
                    {
                    	LB_show("<?php echo _('Trial Status') ?>", url, '80%', '80%', false, false);

                    }, 2000);

                <?php
                }
                ?>
    		});

        </script>

    </head>

    <body>

        <div id='main_container'>

            <div id='c_header'>
                <div id='header'>

                    <div id='header_logo'>
                        <img src="<?php echo AV_PIXMAPS_DIR?>/logo/<?php echo ($pro) ? 'av_contrast_logo' : 'ossim_contrast_logo' ?>.png"/>
                    </div>


                    <div id='header_options'>
                        <span id='welcome'><?php echo _('Welcome') . ' ' . Session::get_session_user()?></span>
                        <span id='sep_ri'>|</span>
                        <a id='link_settings' href='javascript:void(0);'><?php echo _('Settings') ?></a>
                        <a id='link_support' href='javascript:void(0);'><?php echo _('Support') ?></a>
                        <a href='<?php echo  AV_MAIN_PATH ?>/session/login.php?action=logout'><?php echo _('Logout') ?></a>
                    </div>

                    <?php
                    if ($_SESSION['_welcome_wizard_bar'] === TRUE)
                    {
                    ?>
                         <div id='home_wizard_wrapper'>
                            <a id='home_wizard_notif' href='/ossim/wizard'>
                                <?php echo _('Extend your visibility. Collect more data now.') ?>
                            </a>
                         </div>

                     <?php
                     }
                     ?>

                </div>
            </div>

            <div id='c_nav_container'>
                <div id='nav_container'>
                    <div id='c_menu'></div>
                </div>
            </div>

            <div id='c_content'>

                <div id='submenu_title'></div>

                <div id='c_hmenu'></div>

                <div id='content'>

                    <!-- Help -->
                    <div id='c_help'>
                        <img src='<?php echo AV_PIXMAPS_DIR?>/help_hmenu_gray.png' alt='<?php echo _('Help')?>' title='<?php echo _('Help')?>'/>
                    </div>

                    <div id='content_overlay'></div>

                    <iframe id='main' name='main' src=''></iframe>

                </div>

            </div>

            <div class='clear_layer'></div>

            <div id='footer'>
                &copy; COPYRIGHT <?php echo date("Y") ?> ALIENVAULT, INC.
                <span id='sep_ri'>|</span>
                <a href='javascript:;' id='link_copyright'>LEGAL</a>
            </div>


            <!-- Side Bar -->

            <div id='notif_container' class='side_bar_shadow'>

                <div id='notif_layer' class='notif_closed notif_border'>

                    <div class='notif_title'><?php echo _('Environment Snapshot') ?></div>

                    <div class='notif_section'>
                        <div class='notif_left'><?php echo _('Open Tickets') ?></div>
                        <div id='notif_tickets' class='notif_right'>-</div>
                        <div class='clear_layer'></div>
                    </div>

                    <div class='notif_section'>
                        <div class='notif_left'><?php echo _('Unresolved Alarms') ?></div>
                        <div id='notif_alarms' class='notif_right'>-</div>
                        <div class='clear_layer'></div>
                    </div>

                    <div class='notif_separator'></div>

                    <div class='notif_section'>

                        <div class='notif_section_title'><?php echo _('System Health') ?></div>

                        <div id='notif_sensors_chart' class='notif_left'>
                            <div id="semaphore_led1" class='semaphore'>&nbsp;</div>
                            <div id="semaphore_led2" class='semaphore'>&nbsp;</div>
                            <div id="semaphore_led3" class='semaphore'>&nbsp;</div>
                        </div>
                        <div id='notif_sensors' class='notif_right'>
                            -
                        </div>
                        <div class='clear_layer'></div>
                    </div>

                    <div class='notif_separator'></div>

                    <div class='notif_section'>
                        <div class='notif_left'><?php echo _('Latest SIEM Activity') ?></div>
                        <div id='notif_eps' class='notif_right'>-</div>
                        <div class='clear_layer'></div>
                    </div>

                    <div class='notif_section'>
                        <div class='notif_left'><?php echo _('Monitored Devices') ?></div>
                        <div id='notif_devices' class='notif_right'>-</div>
                        <div class='clear_layer'></div>
                    </div>

                    <div class='notif_sparkline'>
                        <span class="sparkline"></span>
                    </div>

                    <div id='notifications_title' class='notif_title'>
                        <?php echo _('Notifications') ?>
                    </div>

                    <div class='notif_section notifications'>
                        <ul id='notif_list'></ul>
                        <div id='notif_status'></div>
                        <div class='clear_layer'></div>
                    </div>
                </div>

                <div id="notif_resume">

                    <div id='sec_1' class='resume_sec'>
                        <img id='img_alarm' src='/ossim/pixmaps/statusbar/alarm.png'/>
                        <div id='resume_alarm_count'>-</div>
                    </div>

                    <div id='sec_2' class='resume_sec'>
                        <div id='resume_space'></div>
                        <div id='resume_eps'>-</div>
                        <div id='resume_eps_text'>EPS</div>
                    </div>

                    <div id='sec_3' class='resume_sec'>
                        <img id='img_notif' src='/ossim/pixmaps/statusbar/envelope.png' />
                        <div id='notif_buble'>0</div>
                    </div>

                    <div id='notif_bt' class='notif_closed'></div>

                </div>


            </div>

        </div>

    </body>
</html>