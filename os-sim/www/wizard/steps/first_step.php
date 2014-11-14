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

//Checking permissions
if (!Session::am_i_admin())
{
    echo _('You do not have permissions to see this section');
    
    die();
}

$version = (Session::is_pro() ? "USM" : "OSSIM");

$title   = _('Welcome to the AlienVault %s Getting Started Wizard');
$text    = _('You are about to use this wizard to configure the critical security capabilities provided by AlienVault %s.');
$text_2  = _('Once done you\'ll be ready to use AlienVault %s. Now, go forth!');
$h1      = _('Configure interfaces and monitor<br/>network traffic for threats');
$h2      = _('%s will perform a discovery scan<br/>to detect assets');
$h3      = _('Monitor asset logs and alarm on<br/>suspicious activity');

$title  = sprintf($title, $version);
$text   = sprintf($text, $version);
$text_2 = sprintf($text_2, $version);
$h1     = sprintf($h1);
$h2     = sprintf($h2, $version);
$h3     = sprintf($h3);

?>
<script type='text/javascript'>

    $(document).ready(function()
    {      
        $('#w_start_wizard').on('click', function()
        {   
            initialize_wizard();
            
        });
        
        $(window).on('resize', function()
        {
            adjust_container_height();    
        });
        
    });

</script>

<div id='first_step' class='wz_body'>

    <div class='logo'>
        <img src='/ossim/pixmaps/top/logo<?php echo Session::is_pro() ? '_siem' : '' ?>.png'/>
    </div>
    
    <div class='title'>
        <?php echo $title ?>
    </div>
    
    <div class='subtitle'>
        <?php echo $text ?>
    </div>
    
    <div class='image_content'>
        <div class='headers'>
            <div class='step' id='step1'>
                <div class='step_number'>1</div>
                <div class='step_text'><strong><?php echo _('Monitor Network') ?></strong><br/>
                <?php echo $h1 ?>
                </div>
            </div>
            <div class='step' id='step2'>
                <div class='step_number'>2</div>
                <div class='step_text'><strong><?php echo _('Discover Assets') ?></strong><br/>
                <?php echo $h2 ?>
                </div>
            </div>
            <div class='step' id='step3'>
                <div class='step_number'>3</div>
                <div class='step_text'><strong><?php echo _('Collect Logs & Monitor Assets') ?></strong><br/>
                <?php echo $h3 ?>
                </div>
            </div>
        </div>
        <div class='image'>
            <img src='/ossim/wizard/img/first_step_image.png' />
        </div>
    </div>
    
    <div class='subtitle_2'>
        <?php echo $text_2 ?>
    </div>   

</div>


<div id='first_step_buttons' class='wizard_button_list'>
    
    <a href='javascript:;' id='wizard_exit' class="fleft exit_first"><?php echo _('Skip AlienVault Wizard') ?></a>
    
    <button id='w_start_wizard'  class="fright"><?php echo _('Start') ?></button>

</div>
