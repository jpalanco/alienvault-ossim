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

Session::logcheck('configuration-menu', 'PolicyServers');


$back_url    = Menu::get_menu_url("/ossim/server/server.php", "configuration", "deployment", "components", "servers");
$form_action = 'newserver.php';

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title> <?php echo _('AlienVault ' . (Session::is_pro() ? 'USM' : 'OSSIM')); ?> </title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>

    <?php
        //CSS Files
        $_files = array(
            array('src' => 'av_common.css',         'def_path' => TRUE),
            array('src' => 'tipTip.css',            'def_path' => TRUE)
        );

        Util::print_include_files($_files, 'css');

        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',             'def_path' => TRUE),
            array('src' => 'jquery-ui.min.js',          'def_path' => TRUE),
            array('src' => 'utils.js',                  'def_path' => TRUE),
            array('src' => 'notification.js',           'def_path' => TRUE),
            array('src' => 'token.js',                  'def_path' => TRUE),
            array('src' => 'messages.php',              'def_path' => TRUE),
            array('src' => 'ajax_validator.js',         'def_path' => TRUE),
            array('src' => 'jquery.tipTip-ajax.js',     'def_path' => TRUE)
        );

        Util::print_include_files($_files, 'js');
    ?>


    <style type='text/css'>

        #av_info
        {
            width: 60%;
            margin: 15px auto 0px auto;
        }

        #new_server_desc
        {
            text-align: left;
            margin: 0 auto 20px auto;
        }

        img.av_tooltip
        {
            top: 0px;
        }

        #server_container
        {
            width: 45%;
            margin: 25px auto 30px auto;
            text-align: center;
            padding: 10px;
        }

        #form_new_server table
        {
            width: 100%;
        }

        #form_new_server .f_legend
        {
            width: 30%;
            text-align: left;
        }

        #form_new_server .f_field
        {
            text-align: center;
            width: 60%;
        }

        #form_new_server .f_field input
        {
            padding: 3px;
            width: 80%;
        }

        #send
        {
            margin: 35px auto 15px auto;
            text-transform: uppercase;
        }

    </style>

    <script type="text/javascript">

        function show_notification(id, msg, type)
        {
            id = '#'+id;

            var config_nt = { content: msg,
                              options: {
                                type: type,
                                cancel_button: false
                              },
                              style: 'width:75%;display:none;text-align:center;margin:10px auto;padding:0 5px;'
                            };

            nt = new Notification('nt_js',config_nt);

            $(id).find('div').html(nt.show());

            $(id).show();
            nt.fade_in(1000);

            setTimeout(function()
            {
                nt.fade_out(1000, function()
                {
                    $(id).hide();
                });

            },4000);
        }

        function passwordEncrypt(data,salt) {
            return Base64.encode(salt+data);
        }

        $(document).ready(function()
        {
            Token.add_to_forms();

            var config =
            {
                validation_type: "complete",
                errors:
                {
                    display_errors: "all",
                    display_in: "av_info"
                },
                form :
                {
                    id  : "form_new_server",
                    url : "<?php echo $form_action ?>"
                },
                actions:
                {
                    on_submit:
                    {
                        id: "send",
                        success: "<?php echo _('Save')?>",
                        checking: "<?php echo _('Saving')?>"
                    }
                }
            };


            $('#send').on('click', function()
            {
                var $parent = $(this).parent();
                var $pass = $parent.find('#password');
                var $clearPassword = $parent.find('#clear-password');
                var salt = $parent.find('#token_form_new_server').val();

                $clearPassword.prop('disabled', true);
                $pass.val(passwordEncrypt($clearPassword.val(),salt));

                ajax_validator = new Ajax_validator(config);
                ajax_validator.submit_form();
            });

            if (typeof parent.is_lightbox_loaded == 'function' && !parent.is_lightbox_loaded(window.name))
            {
                $('.c_back_button').show();
            }
            else
            {
                $('#server_container').css('margin', '10px auto 20px auto');
            }

        });
    </script>
</head>

<body>

    <div class="c_back_button">
        <input type='button' class="av_b_back" onclick='document.location.href="<?php echo $back_url?>";'/>
    </div>

    <div id='av_info'></div>

    <div id='server_container'>

        <form method="post" name='form_new_server' id='form_new_server' action="<?php echo $form_action?>">

            <div id='new_server_desc'>
                <?php echo _('In order to set up a remote server, you need to provide the Server IP and the root password to establish a connection with it.') ?>
            </div>

            <table class='transparent'>
                <tr>
                    <td class='f_legend'>
                        <label for="ip"><?php echo _('Server IP')?>:</label>
                    </td>
                    <td class='f_field'>
                        <input type="text" class='vfield' name="ip" id="ip" value=""/>
                    </td>
                </tr>
                <tr>
                    <td class='f_legend'>
                        <label for="password"><?php echo _('Password')?>:</label>
                    </td>
                    <td class='f_field'>
                        <input type="hidden" class='vfield' name="password" id="password" autocomplete="off"/>
                        <input type="password"  class='vfield' name="clear-password" id="clear-password" autocomplete="off"/>
                    </td>
                </tr>
            </table>

            <input type="button" name='send' id='send' value="<?php echo _('Save')?>"/>

        </form>
    </div>

</body>
</html>
