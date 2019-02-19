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

if (!Session::am_i_admin())
{
    $config_nt = array(
            'content' => _("You do not have permission to see this section"),
            'options' => array (
                    'type'          => 'nf_error',
                    'cancel_button' => false
            ),
            'style'   => 'width: 60%; margin: 30px auto; text-align:center;'
    );
    
    $nt = new Notification('nt_1', $config_nt);
    $nt->show();

    die();
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title><?php echo _('AlienVault ' . (Session::is_pro() ? 'USM' : 'OSSIM')) ?></title>
    <meta http-equiv="Content-Type" content="text/html;charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>

    <?php

        //CSS Files
        $_files = array(
            array('src' => 'av_common.css',                         'def_path' => TRUE),
            array('src' => '/wizard/wizard.css',                    'def_path' => TRUE),
            array('src' => '/style_usm/configuration/deployment/fileuploader.css', 'def_path' => FALSE),
            array('src' => '/style_usm/configuration/deployment/smart_event_collection.css', 'def_path' => FALSE)
        );
        
        Util::print_include_files($_files, 'css');

        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                         'def_path' => TRUE),
            array('src' => 'jquery.tipTip.js',                      'def_path' => TRUE),
            array('src' => 'token.js',                              'def_path' => TRUE),
            array('src' => 'utils.js',                              'def_path' => TRUE),
            array('src' => 'notification.js',                       'def_path' => TRUE),
            array('src' => 'messages.php',                          'def_path' => TRUE),
            array('src' => 'ajax_validator.js',                     'def_path' => TRUE),
            array('src' => '/js/av_wizard.js.php',                  'def_path' => FALSE),
            array('src' => '/asec/js/asec_msg.php',                 'def_path' => FALSE),
            array('src' => '/asec/js/config.js',                    'def_path' => FALSE),
            array('src' => '/asec/js/asec.js',                      'def_path' => FALSE),
            array('src' => '/asec/js/fileuploader.js',              'def_path' => FALSE),
            array('src' => 'greybox.js',                            'def_path' => TRUE),
        );
        
        Util::print_include_files($_files, 'js');

    ?>
    
    <script type='text/javascript'>
	asec_history = new Object();
	var show_extra_info = true;

        $(document).ready(function() 
        {
            var opt = {'steps': 
                [
                 {'title': '<?=_("Upload Log")?>',
                  'src': '<?php echo AV_MAIN_PATH . '/av_plugin/views/steps/upload_plugin.php' ?>'},
                 {'title': '<?=_("Properties")?>',
                  'src': '<?php echo AV_MAIN_PATH . '/av_plugin/views/steps/edit_properties.php' ?>'},
                 {'title': '<?=_("Event Types")?>',
                  'src': '<?php echo AV_MAIN_PATH . '/av_plugin/views/steps/event_types.php' ?>',
                 },
                 {'title': '<?=_("Review")?>', 
                  'src': '<?php echo AV_MAIN_PATH . '/av_plugin/views/steps/review.php' ?>'}
                ],
                'finish': function()
                {
                    document.location.href = '<?php echo AV_MAIN_PATH . '/av_plugin/views/steps/finish.php' ?>';
                }
            };
            
            $('#wizard_container').av_wizard(opt);
            
        });
        // End of document.ready




    </script>
    

</head>

<body>

<div id='wizard_container'></div>

</body>

</html>
