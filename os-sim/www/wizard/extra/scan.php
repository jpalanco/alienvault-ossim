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


//Check the permissions
if (!Session::am_i_admin())
{
    $error = TRUE;
    $msg   = _('You do not have permissions to see this section');
    $step  = -1;
}
else
{
    $error   = intval(GET('error'));

    $wizard  = Welcome_wizard::get_instance(); 
    
    if (!$error && is_object($wizard))
    {
        //Getting the networks
        $nets = $wizard->get_step_data('scan_nets');
        
        //Getting the step
        $step = intval($wizard->get_step_data('scan_step'));
        $step = (empty($step)) ? 1 : $step;
        
        if (count($nets) < 1)
        {
            $step = -1;
            $msg  = _('You must select at least one network to scan'); 
        }
        else
        {
            if ($step == -1)
            {
                $msg = $wizard->get_step_data('scan_error_msg');
            }
        }
    }
    else
    {
        $step = -1;
        $msg  = _('An unexpected error happened. Try again later');
    }

}


$script_function = 'scan_step_error()';

if ($step > 0 && $step < 4)
{
    $script_function = 'scan_step_'. $step .'()';
}
else
{
    $step = 'error';
    
    if ($msg == '')
    {
        $msg = _('An unexpected error happened. Try again later');
    }
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <title> <?php echo _("AlienVault " . (Session::is_pro() ? "USM" : "OSSIM")); ?> </title>
        <link rel="Shortcut Icon" type="image/x-icon" href="/ossim/favicon.ico">
        <meta http-equiv="Content-Type" content="text/html;charset=iso-8859-1"/>
        <meta http-equiv="Pragma" content="no-cache"/>
    
        <?php
    
            //CSS Files
            $_files = array(
                array('src' => 'av_common.css?only_common=1',           'def_path' => TRUE),
                array('src' => 'jquery-ui.css',                         'def_path' => TRUE),
                array('src' => 'progress.css',                          'def_path' => TRUE),
                array('src' => 'jquery.select.css',                     'def_path' => TRUE),
                array('src' => '/wizard/wizard.css',                    'def_path' => TRUE)
            );

            Util::print_include_files($_files, 'css');


            //JS Files
            $_files = array(
                array('src' => 'jquery.min.js',                         'def_path' => TRUE),
                array('src' => 'jquery-ui.min.js',                      'def_path' => TRUE),
                array('src' => 'utils.js',                              'def_path' => TRUE),
                array('src' => 'jquery.select.js',                      'def_path' => TRUE),
                array('src' => 'notification.js',                       'def_path' => TRUE),
                array('src' => 'token.js',                              'def_path' => TRUE),
                array('src' => '/wizard/js/scan.js.php',                'def_path' => FALSE)
            );
    
            Util::print_include_files($_files, 'js');
    
        ?>
    
        <script type='text/javascript'>
            
            var __ajax_request  = null;
            var __check_timeout = false;
            var __cancel_lock   = false;
            
            $(document).ready(function() 
            {
                <?php echo $script_function; ?>
                
            });
            
        </script>
        
    </head>
    <body class='b_white'>
        
        <div id='scan_container'>
        <?php 
            
            require 'scan/step_'. $step . '.php';
            
        ?>
        </div>
    </body> 
</html>

