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

Session::logcheck("dashboard-menu", "IPReputation");

$perms = array(
    'admin'  => Session::am_i_admin()
);

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title><?php echo _('Open Threat Exchange Configuration') ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>

    <?php
        //CSS Files
        $_files = array(
            array('src' => 'jquery-ui.css',             'def_path' => TRUE),
            array('src' => 'jquery.dataTables.css',     'def_path' => TRUE),
            array('src' => 'av_common.css',             'def_path' => TRUE),
            array('src' => 'jquery.switch.css',         'def_path' => TRUE),
            array('src' => 'tipTip.css',                'def_path' => TRUE),
            array('src' => 'jquery.dropdown.css',       'def_path' => TRUE),
            array('src' => 'av_table.css',              'def_path' => TRUE),
            array('src' => 'otx/av_pulse.css',          'def_path' => TRUE)
        );

        Util::print_include_files($_files, 'css');


        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                 'def_path' => TRUE),
            array('src' => 'jquery-ui.min.js',              'def_path' => TRUE),
            array('src' => 'jquery.number.js.php',          'def_path' => TRUE),
            array('src' => 'notification.js',               'def_path' => TRUE),
            array('src' => 'greybox.js',                    'def_path' => TRUE),
            array('src' => 'utils.js',                      'def_path' => TRUE),
            array('src' => 'token.js',                      'def_path' => TRUE),
            array('src' => 'jquery.tipTip-ajax.js',         'def_path' => TRUE),
            array('src' => 'jquery.dataTables.js',          'def_path' => TRUE),
            array('src' => 'jquery.dropdown.js',            'def_path' => TRUE),
            array('src' => 'jquery.switch.js',              'def_path' => TRUE),
            array('src' => '/otx/js/av_otx_config.js.php',  'def_path' => FALSE),
            array('src' => '/otx/js/av_otx_pulse.js.php',   'def_path' => FALSE),
        );

        Util::print_include_files($_files, 'js');  
    ?>

    <style type="text/css">
        
        

    </style>


    <script type='text/javascript'>
        
        $(document).on('ready', function()
        {
            var otx_config = new AV_otx_config(<?php echo json_encode($perms) ?>);
        });
        
    </script>
</head>

<body>
    
    <div id='otx_key_warning'></div>
    
    <div id='otx_wrapper'>
        
        <div id='otx_notif'></div>
        
        <div class='header_title otx_admin'>
            <?php echo _('OTX Account') ?>
            
            <button id='button_action' class='button otx_action' data-otx='account-actions' data-dropdown="#dropdown-actions">
                <?php echo _('Actions') ?> &nbsp;&#x25be;
            </button>
            
        </div>
        
        <div id='otx_config_section' class='section otx_admin'>
            
            <div id='otx_loading'>
                <img src='<?php echo AV_PIXMAPS_DIR ?>/loading.gif'/>
            </div>
        
            <div id='token_text' data-otx='text-token'>
                <?php 
                    $msg = _('Connect your OTX account to %s by adding your <a href="javascript:;" data-otx="get-token-login">OTX key</a> in the space below. If you do not have an OTX key, <a href="javascript:;" data-otx="get-token">sign up</a> for an OTX account now!');
                    
                    echo sprintf($msg, (Session::is_pro() ? 'USM' : 'OSSIM' ));
                ?>

            </div>
        
            <div class='col_60'>
                <div class='field'>
                    <label class='field_label col_25'><?php echo _('OTX Key:') ?></label>
                    <div class='field_edit col_75' data-otx="token"></div>
                </div>
                    
                <div class='field'>
                    <label class='field_label col_25'><?php echo _('OTX Username:') ?></label>
                    <div class='field_edit col_75' data-otx="username"></div>
                </div>
                    
            </div>
            
            <div class='col_40'>
                <div class='field'>
                    <label class='field_label col_35'><?php echo _('Contribute to OTX:') ?></label>
                    <div class='toggle_button col_65' data-otx="account-status">
                        
                    </div>
                </div>
                <div class='field'>
                    <label class='field_label col_35'><?php echo _('Last Updated:') ?></label>
                    <div class='field_edit col_65' data-otx="latest-update"></div>
                </div>
            </div>
            
            <div class='clear_layer'></div>
            
            <div id='field_actions' data-otx='actions'>
                <input type="button" class='av_b_secondary otx_action' data-otx="cancel-edition" value="<?php echo _('Cancel') ?>">
                <input type="button" class='otx_action' data-otx="connect-account" value="<?php echo _('Connect OTX account') ?>">
            </div>
            
        </div>
        
        <div id='pulse_list' data-pulse-list='wrapper'></div>
        
    </div>
    
    
    <div id="dropdown-actions" class="dropdown dropdown-close dropdown-tip dropdown-anchor-right">
    	<ul class="dropdown-menu">
        	<li><a href='#0' data-otx='edit-account'><?php echo _('Edit OTX Key') ?></a></li>
        	<li><a href='#0' data-otx='remove-account'><?php echo _('Remove OTX Key') ?></a></li>
        	<li><a href='#0' data-otx='view-account'><?php echo _('View Account Details') ?></a></li>
    	</ul>
    </div>
    
</body>
</html>
