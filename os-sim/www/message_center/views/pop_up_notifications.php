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

$n_type = REQUEST('type');
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <title><?php echo _("OSSIM Framework");?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
        <meta name="viewport" content="initial-scale=1.0, user-scalable=no"/>
        <meta http-equiv="Pragma" content="no-cache"/>

        <?php
        //CSS Files
        $_files = array(
            array('src' => 'av_common.css?t='.Util::get_css_id(),      'def_path' => TRUE),
            array('src' => 'message_center/pop_up_notifications.css',  'def_path' => TRUE)
        );

        Util::print_include_files($_files, 'css');

        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                                            'def_path' => TRUE),
            array('src' => 'notification.js',                                          'def_path' => TRUE),
            array('src' => 'messages.php',                                             'def_path' => TRUE),
            array('src' => 'utils.js',                                                 'def_path' => TRUE),
            array('src' => 'token.js',                                                 'def_path' => TRUE),
            array('src' => '/message_center/js/pop_up_notifications.js.php',     'def_path' => FALSE)
        );

        Util::print_include_files($_files, 'js');
        ?>

        <script type='text/javascript'>

            $(document).ready(function()
            {
                <?php
                switch ($n_type)
                {
                    case 'track_usage_information':
                        ?>
                        $('[data-bind="send_tui"]').off('click').on('click', function(){

                            send_track_usage_information();

                        });
                        <?php
                    break;
                }
                ?>

                $('[data-bind="cancel"]').off('click').on('click', function(){

                    hide_lightbox();

                });
            });

        </script>
    </head>

    <body>
        <div id="c_pop_up">

            <div id='pop_up_info'></div>

            <?php
            switch ($n_type)
            {
                case 'track_usage_information':
                    include AV_MAIN_ROOT_PATH.'/message_center/templates/tpl_track_usage_information.php';
                break;

                default:
                    echo ossim_error(_('No template found'));
            }
            ?>

        </div>
    </body>
</html>
