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

Session::logcheck('analysis-menu', 'EventsForensics');

$db    = new ossim_db();
$conn  = $db->connect();

$db_id  = GET('id');
$update = intval(GET('update'));

ossim_valid($db_id, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _('Database ID'));

if (ossim_error())
{
    die(ossim_error());
}


if ($db_id != '')
{
    if ($db_list = Databases::get_list($conn, "WHERE id = '$db_id'"))
    {
        $db = array_shift($db_list);

        $db_name = $db->get_name();
        $ip      = $db->get_ip();
        $port    = $db->get_port();
        $user    = $db->get_user();
        $pass    = Util::fake_pass($db->get_pass());
        $pass2   = $pass;
        $icon    = $db->get_icon();
        $icon    = (!empty($icon)) ? 'data:image/png;base64,'.base64_encode($icon) : '';
    }
}
else
{
    $db_id   = '';
    $db_name = '';
    $ip      = '';
    $user    = '';
    $pass    = '';
    $port    = '3306';
    $icon    = '';
}


$action = ($db_id != '') ? 'modifydbs.php' : 'newdbs.php';
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title> <?php echo _('OSSIM Framework');?> </title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>

    <?php
    //CSS Files
    $_files = array(
        array('src' => 'av_common.css?t='.Util::get_css_id(), 'def_path' => TRUE),
        array('src' => 'av_icon.css',                         'def_path' => TRUE),
        array('src' => 'tipTip.css',                          'def_path' => TRUE)
    );

    Util::print_include_files($_files, 'css');

    //JS Files
    $_files = array(
        array('src' => 'jquery.min.js',                 'def_path' => TRUE),
        array('src' => 'jquery-ui.min.js',              'def_path' => TRUE),
        array('src' => 'utils.js',                      'def_path' => TRUE),
        array('src' => 'notification.js',               'def_path' => TRUE),
        array('src' => 'token.js',                      'def_path' => TRUE),
        array('src' => 'jquery.tipTip.js',              'def_path' => TRUE),
        array('src' => 'ajax_validator.js',             'def_path' => TRUE),
        array('src' => 'messages.php',                  'def_path' => TRUE),
        array('src' => 'jquery.elastic.source.js',      'def_path' => TRUE),
        array('src' => 'av_icon.js.php',                'def_path' => TRUE)

    );

    Util::print_include_files($_files, 'js');
    ?>


    <style type='text/css'>

        input[type='text'], input[type='hidden'], select
        {
            width: 98%;
            height: 18px;
        }

        input[type='file']
        {
            width: 98%;
            border: solid 1px #CCCCCC;
        }

        textarea
        {
            width: 97%;
            height: 45px;
        }

        .img_format
        {
            color: gray;
            font-style: italic;
            font-size: 10px;
        }

        #db_container
        {
            width: 680px;
            margin: 40px auto 20px auto;
            padding-bottom: 10px;
        }

        #db_container #table_form
        {
            margin: auto;
            width: 100%;
        }

        #table_form th
        {
            width: 150px;
        }

        .legend
        {
            font-style: italic;
            text-align: center;
            padding: 0px 0px 5px 0px;
            margin: auto;
            width: 400px;
        }

        .text_dbsname
        {
            cursor: default !important;
            font-style: italic !important;
            opacity: 0.5 !important;
        }

        #av_info
        {
            width: 580px;
            margin: 10px auto;
        }

    </style>


    <script type="text/javascript">
        $(document).ready(function(){

            Token.add_to_forms();

            /****************************************************
            ************************ Icon **********************
            ****************************************************/

            $('#icon').av_icon({
                icon : "<?php echo $icon?>",
                width : 32,
                height: 32
            });



            $('textarea').elastic();

            var config = {
                validation_type: 'complete', // single|complete
                errors:{
                    display_errors: 'all', //  all | summary | field-errors
                    display_in: 'av_info'
                },
                form : {
                    id  : 'db_form',
                    url : "<?php echo $action?>"
                },
                actions: {
                    on_submit:{
                        id: 'send',
                        success: '<?php echo _('Save')?>',
                        checking: '<?php echo _('Saving')?>'
                    }
                }
            };

            ajax_validator = new Ajax_validator(config);

            $('#send').click(function() {
                ajax_validator.submit_form();
            });


            $('#text_dbsname').focus(function() {
                $(this).blur();
            });


            //Greybox options
            if (!parent.is_lightbox_loaded(window.name))
            {
                $('.c_back_button').show();
            }
            else
            {
                $('#db_container').css('margin', '10px auto 20px auto');
            }
        });
    </script>

</head>
<body>

<div class="c_back_button">
    <input type='button' class="av_b_back" onclick="document.location.href='dbs.php';return false;"/>
</div>

<div id='av_info'>
    <?php
    if ($update == 1)
    {
        $config_nt = array(
            'content' => _('Your changes have been saved'),
            'options' => array (
                'type'          => 'nf_success',
                'cancel_button' => TRUE
            ),
            'style' => 'width: 100%; margin: auto; text-align:center;'
        );

        $nt = new Notification('nt_1', $config_nt);

        $nt->show();
    }
    ?>
</div>

<div id='db_container'>
    <div class='legend'>
         <?php echo _('Values marked with (*) are mandatory');?>
    </div>

    <form name='db_form' id='db_form' method="POST" action="<?php echo $action?>" enctype="multipart/form-data">

        <table align="center" id='table_form'>

            <tr>
                <th>
                    <label for='db_name'><?php echo _('Name') . required();?></label>
                </th>
                <td class="left">
                    <?php
                    if (!empty($db_name))
                    {
                        ?>
                        <input type='text' class='text_dbsname' name='text_dbsname' id='text_dbsname' readonly="readonly" value='<?php echo $db_name?>'/>
                        <input type='hidden' class='vfield' name='db_name' id='db_name' value='<?php echo $db_name?>'/>
                        <input type='hidden' class='vfield' name='db_id' id='db_id' value='<?php echo $db_id?>'/>
                        <?php
                    }
                    else
                    {
                        ?>
                        <input type='text' class='vfield' name='db_name' id='db_name' value='<?php echo $db_name?>'/>
                        <?php
                    }
                    ?>
                </td>
            </tr>

            <tr>
                <th>
                    <label for='ip'><?php echo _('IP') . required();?></label>
                </th>
                <td class="left">
                    <input type="text" class='vfield' name="ip" id="ip" value="<?php echo $ip?>"/>
                </td>
            </tr>

            <tr>
                <th>
                    <label for='port'><?php echo _('Port') . required();?></label>
                </th>
                <td class="left">
                    <input type="text" class='vfield' name="port" id="port" value="<?php echo (!empty($port)) ? $port : "3306";?>"/>
                </td>
            </tr>

            <tr>
                <th>
                    <label for='user'><?php echo _('User') . required();?></label>
                </th>
                <td class="left">
                    <input type="text" class='vfield' name="user" id="user" value="<?php echo $user;?>"/>
                </td>
            </tr>

            <tr>
                <th>
                    <label for='pass'><?php echo _('Password') . required();?></label>
                </th>
                <td class="left">
                    <input type="password" class='vfield' name="pass" id="pass" value="<?php echo $pass;?>" autocomplete="off"/>
                </td>
            </tr>

            <tr>
                <th>
                    <label for='pass2'><?php echo _('Repeat Password') . required();?></label>
                </th>
                <td class="left">
                    <input type="password" class='vfield' name="pass2" id="pass2" value="<?php echo $pass2;?>" autocomplete="off"/>
                </td>
            </tr>

            <tr>
                <th>
                    <label for='icon'><?php echo _('Icon');?></label>
                </th>

                <td class="left">
                    <input type="file" class="vfield" name="icon" id="icon"/>
                </td>
            </tr>

            <tr>
                <td colspan="2" align="center" style="border-bottom: none; padding: 10px;">
                    <input type="button" id="send" name="send" value="<?php echo _('Save')?>"/>
                </td>
            </tr>

        </table>
    </form>
</div>

</body>
</html>
