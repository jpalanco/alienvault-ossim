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

/* connect to db */
$db   = new ossim_db();
$conn = $db->connect();

if ($_SESSION['_user'])
{
    $user = $_SESSION['_user'];

    unset($_SESSION); // destroy session to force password change
    session_destroy();

    session_start();
    $_SESSION['_backup_user'] = $user;
}
else
{
    $user = $_SESSION['_backup_user'];
}


$conf = $GLOBALS['CONF'];

if (!$conf)
{
    $conf = new Ossim_conf();
    $GLOBALS['CONF'] = $conf;
}

$first_login = $conf->get_conf('first_login');

$cnd_1 = (!isset($user) || empty($user));
$cnd_2 = ($first_login == 'yes' || $first_login === 1);

if ($cnd_1 || $cnd_2)
{
    $ossim_link     = $conf->get_conf('ossim_link');
    $login_location = $ossim_link . '/session/login.php';

    header("Location: $login_location");
    exit();
}

$pass_1 = base64_decode(POST('pass1'));
$pass_2 = base64_decode(POST('pass2'));
$c_pass = base64_decode(POST('current_pass'));

$pass_1 = Util::utf8_encode2(trim($pass_1));
$pass_2 = Util::utf8_encode2(trim($pass_2));
$c_pass = Util::utf8_encode2(trim($c_pass));

$flag         = POST('flag');
$changeadmin  = POST('changeadmin');
$expired      = POST('expired');

ossim_valid($c_pass, OSS_PASSWORD, OSS_NULLABLE,   'illegal:' . _('Current Password'));
ossim_valid($pass_1, OSS_PASSWORD, OSS_NULLABLE,   'illegal:' . _('Password'));
ossim_valid($pass_2, OSS_PASSWORD, OSS_NULLABLE,   'illegal:' . _('Rewrite Password'));
ossim_valid($flag, OSS_DIGIT, OSS_NULLABLE,        'illegal:' . _('Flag'));
ossim_valid($changeadmin, OSS_DIGIT, OSS_NULLABLE, 'illegal:' . _('Change Admin Password'));
ossim_valid($expired, OSS_DIGIT, OSS_NULLABLE,     'illegal:' . _('Expired'));

if (ossim_error())
{
    die(ossim_error());
}


$version    = $conf->get_conf('ossim_server_version');
$opensource = (!preg_match("/.*pro.*/i", $version) && !preg_match("/.*demo.*/i", $version)) ? TRUE : FALSE;


if ($flag != '')
{
    /* Connect to db */
    $db   = new ossim_db();
    $conn = $db->connect();

    $res = check_pass($conn, $user, $c_pass, $pass_1, $pass_2);

    if ($res !== TRUE)
    {
        $msg = $res;
    }
    else
    {
        $_SESSION['_user'] = $_SESSION['_backup_user'];

        unset($_SESSION['_backup_user']);

        $res = Session::change_pass($conn, $user, $pass_1, $c_pass);

        if ($res > 0)
        {
            Session::disable_first_login($conn, $user);

            //Relogin user
            $session = new Session($user, $pass_1, '');

            $is_disabled = $session->is_user_disabled();

            $login_return = FALSE;

            if ($is_disabled == FALSE)
            {
                $login_return = $session->login();
            }

            if ($login_return != TRUE)
            {
                unset($_SESSION); // destroy session to force relogin
                session_destroy();
            }

            header("location:../index.php");
        }
        else
        {
            $msg = _('Current password does not match');
        }
    }

    $db->close();
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title><?php echo _('AlienVault '.(($opensource) ? 'OSSIM' : 'USM'));?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/jquery.pstrength.js"></script>
    <script type="text/javascript" src="../js/jquery.base64.js"></script>
    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <link rel="Shortcut Icon" type="image/x-icon" href="../favicon.ico">

    <style type="text/css">

        body
        {
            background: #3C3C3C;
        }

        table
        {
            border-collapse: separate;
            border: none;
        }

        input[type="text"], input[type="password"]
        {
            border: none !important;
            background-color: rgba(150,150,150,0.1) !important;
            height: 20px !important;
            outline: none;
            color: white;
            font-size:14px;
            width: 130px;
        }

        input[type="text"]:focus, input[type="password"]:focus,
        {
            outline: none;
        }

        #t_login
        {
            width: 85%;
        }

        #t_login input[type="password"]
        {
            width: 99%;
        }

        #t_cp
        {
            margin: 130px auto 50px auto;
            border: none;
            width: 400px;
        }

        #t_info
        {
            margin: auto;
            border: none;
            background: none;
        }

        #t_info .td_info
        {
            border: none;
            padding: 20px 0px;
        }

        #t_login td
        {
            text-align: left;
        }

        .td_cpass
        {
            text-align:right !important;
            font-size: 12px;
            white-space: nowrap;
            font-family: "open_sans","Lucida Sans","Lucida Grande",Lucida,sans-serif,Verdana;
            text-transform: uppercase;
        }

        .td_npass
        {
            text-align:right !important;
            font-size: 12px;
            white-space: nowrap;
            font-family: "open_sans","Lucida Sans","Lucida Grande",Lucida,sans-serif,Verdana;
            text-transform: uppercase;
        }

        .td_rnpass
        {
            text-align:right !important;
            font-size: 12px;
            white-space: nowrap;
            font-family: "open_sans","Lucida Sans","Lucida Grande",Lucida,sans-serif,Verdana;
            text-transform: uppercase;
        }

        input.big
        {
            font-size:13px;
        }

    </style>

    <script type='text/javascript'>
        function send_p()
        {
            var pass1 = $('#pass1u').val();
                pass1 = jQuery.trim(pass1);

            var pass2 = $('#pass2u').val();
                pass2 = jQuery.trim(pass2);

            var current_pass = $('#current_passu').val();
                current_pass = jQuery.trim(current_pass);

            if (pass1 != '')
            {
                $('#pass1').val($.base64.encode(pass1));
            }

            if (pass2 != '')
            {
                $('#pass2').val($.base64.encode(pass2));
            }

            if (current_pass != '')
            {
                $('#current_pass').val($.base64.encode(current_pass));
            }

            $('#submit_button').addClass('av_b_processing');
        }
        $(document).ready(function() {
            $('#pass1u,#pass2u').focus(function() {
                $('#validation-text').css('visibility','hidden');
            });
        });
    </script>
</head>

<body onload="$('#pass1u').pstrength()">
    <form id='fnewpass' name='fnewpass' method='POST' onsubmit="send_p();">
        <input type="hidden" name="flag" value="1"/>
        <input type="hidden" name="changeadmin" value="<?php echo $changeadmin?>"/>
        <input type="hidden" name="expired" value="<?php echo $expired?>">

        <table id='t_cp'/>
            <tr>
                <td class="noborder">
                    <table id="t_info">

                        <tr>
                            <td style="padding:30px 20px 0px 20px">
                                <?php
                                $logo_url   = '/ossim/pixmaps/ossim';
                                $logo_title = _('OSSIM logo');

                                if (Session::is_pro())
                                {
                                    $logo_url  .= '_siem';
                                    $logo_title = _('Alienvault Logo');
                                }

                                $logo_url .= '.png';
                                ?>
                                <img src="<?php echo $logo_url?>" alt="<?php echo $logo_title?>" border="0"/>
                            </td>
                        </tr>

                        <?php
                        if ($changeadmin == TRUE)
                        {
                            ?>
                            <tr>
                                <td class='td_info'>
                                <?php echo _('The administrator has a <strong>vulnerable password</strong>. You must change it now')?>
                                </td>
                            </tr>
                            <?php
                        }
                        elseif ($expired == TRUE)
                        {
                            ?>
                            <tr>
                                <td class='td_info'>
                                <?php echo _('Your password has <strong>expired</strong>.<br/>Please enter your new password')?>
                                </td>
                            </tr>
                            <?php
                        }
                        else
                        {
                            ?>
                            <tr>
                                <td class='td_info'>
                                <?php echo _('For security reasons, <strong>you are required to change your password</strong>.<br/>Please enter your new password')?>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>

                        <tr>
                            <td class="noborder center">
                                <table align="center" cellspacing='4' cellpadding='2' id='t_login'>
                                    <tr>
                                        <td class='td_cpass white'> <?php echo _('Current Password');?></td>
                                        <td class="noborder">
                                            <input type="password" name="current_passu" id="current_passu" autocomplete="off"/>
                                            <input type="hidden" name="current_pass" id="current_pass"/>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td class='td_npass white'> <?php   echo _('New Password');?></td>
                                        <td class="noborder">
                                            <input type="password" name="pass1u" id="pass1u" autocomplete="off"/>
                                            <input type="hidden" name="pass1" id="pass1"/>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td class='td_rnpass white'> <?php echo _('Rewrite Password'); ?> </td>
                                        <td class="noborder">
                                            <input type="password" name="pass2u" id="pass2u" autocomplete="off"/>
                                            <input type="hidden" name="pass2" id="pass2"/>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <?php
                        if ($msg != '')
                        {
                            ?>
                            <tr><td id="validation-text" class="center noborder" style="color:red"><?php echo $msg?></td></tr>
                            <?php
                        }
                        ?>

                        <tr>
                            <td class="noborder" style="text-align:center;padding:20px">
                                <input type="submit" class="button big" id="submit_button" value="<?php echo _('Change');?>"/>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </form>
</body>
</html>
