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

$error     = '';
$save_data = array();

try
{
    $av_plugin = new Av_plugin();
    $save_data = $av_plugin->save_plugin(true);
}
catch(Exception $e)
{
    // API Fail
    $error = $e->getMessage();
    $error = str_replace("\n", '', $error);
    $error = str_replace("\r", '', $error);
}

// API Returns 'success' but with errors in plugin file
if ($save_data['error_count'] > 0 && is_array($save_data['errors']))
{
    $error = implode('<br/>', $save_data['errors']);
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
            array('src' => '/wizard/wizard.css',                    'def_path' => TRUE)
        );
        
        Util::print_include_files($_files, 'css');


        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                         'def_path' => TRUE),
            array('src' => 'utils.js',                              'def_path' => TRUE),
            array('src' => 'notification.js',                       'def_path' => TRUE)
        );
        
        Util::print_include_files($_files, 'js');

    ?>

    <style>
    
    </style>
    
    <script type='text/javascript'>
        
        $(document).ready(function() 
        {
            <?php
            if ($error != '')
            {
            ?>
            show_notification('upload_notif', "<?php echo addslashes($error) ?>", 'nf_error');
            <?php
            }
            ?>

            <?php
            if ($save_data['id'] > 0)
            {
            ?>
            $('[data-bind="configure-sids"]').click(function()
            {
                var params =
                {
                    'plugin_id': "<?php echo $save_data['id'] ?>"
                }

                parent.GB_hide(params);
            });
            <?php
            }
            ?>
        });
        
    </script>
    

</head>

<body>

<div class='finish_container'>

<div id="upload_notif"></div>

<?php
// API Error
if ($error != '')
{
?>
    
    <div class='finish_configure'>
        <input type='button' id='LB_close' value='<?php echo _('Close') ?>' />
    </div>
    
<?php
}
else
{
?>

    <div class='wizard_title'>
        <?php echo _('Success') ?>!
    </div>
    
    <div class='wizard_subtitle'>
        <?php echo _('Your plugin is now available to be enabled on any asset in your system!') ?>
    </div>
<?php
}
?>

</div>

</body>

</html>
