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

$asset_type = (GET('type') == 'group') ? 'group' : ((GET('type') == 'network') ? 'network' : 'asset');

Session::logcheck_by_asset_type($asset_type);

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
                array('src' => 'jquery-ui.css',                 'def_path' => TRUE),
                array('src' => 'lightbox.css',                  'def_path' => TRUE),
                array('src' => '/assets/asset_list_view.css',   'def_path' => TRUE)
            );

            Util::print_include_files($_files, 'css');


            //JS Files
            $_files = array(
                array('src' => 'jquery.min.js',             'def_path' => TRUE),
                array('src' => 'jquery-ui.min.js',          'def_path' => TRUE),
                array('src' => 'ajax_validator.js',         'def_path' => TRUE),
                array('src' => 'messages.php',              'def_path' => TRUE),
                array('src' => 'utils.js',                  'def_path' => TRUE),
                array('src' => 'notification.js',           'def_path' => TRUE),
                array('src' => 'token.js',                  'def_path' => TRUE)
            );

            Util::print_include_files($_files, 'js');
        ?>


        <script type='text/javascript'>

            var __cfg = <?php echo Asset::get_path_url() ?>;

            function close_window()
            {
                if (typeof parent.GB_close == 'function')
                {                   
                    parent.GB_close();
                }

                return false;
            }

            function save_note()
            {
                var params =
                {
                    'type'  : $('#an_type').val(),
                    'note'  : $('#an_txt').val(),
                    'token' : Token.get_token('save_bulk_note')
                };

                $.ajax(
                {
                    data: params,
                    type: "POST",
                    url: __cfg.common.controllers + 'bk_save_note.php',
                    dataType: "json",
                    success: function(data)
                    {
                        if (typeof parent.GB_hide == 'function')
                        {
                            parent.GB_hide();
                        }
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown)
                    {
                        //Checking expired session
                        var session = new Session(XMLHttpRequest, '');
                        if (session.check_session_expired() == true)
                        {
                            session.redirect();

                            return;
                        }

                        var __error_msg = av_messages['unknown_error'];

                        if (typeof(XMLHttpRequest.responseText) != 'undefined' && XMLHttpRequest.responseText != '')
                        {
                            __error_msg = XMLHttpRequest.responseText;
                        }

                        show_notification('av_info', __error_msg, 'nf_error', 5000, true);
                    }
                });
            }


            /* Token */

            Token.add_to_forms();


            /* AJAX Validator */

            var av_config = {
               validation_type: 'complete', // single|complete
               errors: {
                   display_errors: 'summary', //  all | summary | field-errors
                   display_in: 'av_info'
               },
               form: {
                   id: 'f_add_note',
                   url: __cfg.common.controllers + 'bk_save_note.php'
               },
               actions: {
                   on_submit: {
                       id: 'save',
                       success: '<?php echo _('Save')?>',
                       checking: '<?php echo _('Saving')?>'
                   }
               }
            };

            ajax_validator = new Ajax_validator(av_config);

            $(document).ready(function()
            {
                $('#save').click(function(event)
                {
                    event.preventDefault();

                    $('#f_add_note').attr('action', __cfg.common.controllers + 'bk_save_note.php');

                    if (ajax_validator.check_form() == true)
                    {
                        save_note();
                    }
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
        
        <div id="av_info"></div>
        <div id='bn_container'>

            <form method="POST" name='f_add_note' id='f_add_note'>

                <input type="hidden" name='type' class="vfield" id='an_type' value='<?php echo $asset_type?>'/>
                <textarea name='note' id='an_txt' class="vfield"></textarea>

            </form>
            
        </div>
        
        <div id='GB_action_buttons'>
            <button id='cancel' name='cancel' class='av_b_secondary'><?php echo _('Cancel');?></button>
            <button id='save' name='save'><?php echo _('Save');?></button>
        </div>
    </body>
</html>
