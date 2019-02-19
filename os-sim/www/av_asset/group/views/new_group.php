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

Session::logcheck('environment-menu', 'PolicyHosts');


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
                array('src' => 'av_common.css',                 'def_path' => TRUE),
                array('src' => '/assets/asset_list_view.css',   'def_path' => TRUE)
            );

            Util::print_include_files($_files, 'css');


            //JS Files
            $_files = array(
                array('src' => 'jquery.min.js',                          'def_path' => TRUE),
                array('src' => 'jquery-ui.min.js',                       'def_path' => TRUE),
                array('src' => 'utils.js',                               'def_path' => TRUE),
                array('src' => 'notification.js',                        'def_path' => TRUE),
                array('src' => 'token.js',                               'def_path' => TRUE),
                array('src' => '/av_asset/group/js/group_common.js.php', 'def_path' => FALSE)
            );

            Util::print_include_files($_files, 'js');
        ?>


        <script type='text/javascript'>

            function close_window()
            {
                if (typeof parent.GB_close == 'function')
                {
                    parent.GB_close();
                }

                return false;
            }


            $(document).ready(function()
            {
                $('#save').click(function(event)
                {
                    event.preventDefault();
                    create_group($('#ag_name').val(), $('#ag_descr').val(), '1');
                });

                $('#cancel').click(function(event)
                {
                    event.preventDefault();
                    close_window();
                });
                
            });

        </script>

    </head>

    <body>
        <div id="save_ag_notif"></div>
        <div id='ag_save_container'>
            <form name='f_save_group' id='f_save_group'>
                <!-- Asset Group Name -->
                <div class='field_title'><?php echo _('Asset Group Name');?></div>
                
                <input class='field_input' type="text" name='ag_name' id='ag_name' value=''/>

                <div class='field_separator'></div>

                <!-- Asset Group Description -->
                <div class='field_title'><?php echo _('Description');?></div>

                <textarea class='field_input' name='ag_descr' id='ag_descr'></textarea>

                <div id='save_button_set'>
                    <button id='cancel' name='cancel' class='av_b_secondary'><?php echo _('Cancel'); ?></button>
                    <button id='save' name='save'><?php echo _('Save'); ?></button>
                </div>
            </form>
        </div>
    </body>
</html>
