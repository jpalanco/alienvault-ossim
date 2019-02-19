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
require_once 'languages.inc';


Session::useractive('../session/login.php');

$conf = $GLOBALS['CONF'];

/* Connect to db */
$db   = new ossim_db();
$conn = $db->connect();

/* Version */
$pro  = Session::is_pro();

$mode = (empty($_SESSION['user_in_db'])) ? 'insert' : 'update';

$login              = (POST('login') != '') ? POST('login') : $_SESSION['user_in_db'];
$myself             = Session::get_user_info($conn);
$am_i_admin         = Session::am_i_admin();
$am_i_proadmin      = ($pro && Acl::am_i_proadmin())   ? TRUE : FALSE;
$is_my_profile      = ($login == $myself->get_login()) ? TRUE : FALSE;


$validate = array (
    'uuid'              => array('validation' => 'OSS_HEX, OSS_NULLABLE',                              'e_message' => 'illegal:' . _('UUID')),
    'login'             => array('validation' => 'OSS_USER_2',                                         'e_message' => 'illegal:' . _('User login')),
    'user_name'         => array('validation' => 'OSS_ALPHA, OSS_PUNC, OSS_AT',                        'e_message' => 'illegal:' . _('User name')),
    'email'             => array('validation' => 'OSS_MAIL_ADDR, OSS_NULLABLE',                        'e_message' => 'illegal:' . _('User e-mail')),
    'language'          => array('validation' => 'OSS_ALPHA, OSS_PUNC, OSS_AT, OSS_NULLABLE',          'e_message' => 'illegal:' . _('Language')),
    'tzone'             => array('validation' => "OSS_ALPHA, OSS_SCORE, '\/', '\+'",                   'e_message' => 'illegal:' . _('Timezone')),
    'login_method'      => array('validation' => 'ldap, pass',                                         'e_message' => 'illegal:' . _('Login method')),
    'c_pass'            => array('validation' => 'OSS_PASSWORD',                                       'e_message' => 'illegal:' . _('Current password')),
    'pass1'             => array('validation' => 'OSS_PASSWORD',                                       'e_message' => 'illegal:' . _('Password')),
    'pass2'             => array('validation' => 'OSS_PASSWORD',                                       'e_message' => 'illegal:' . _('Retype password')),
    'last_pass_change'  => array('validation' => 'OSS_DIGIT, OSS_PUNC_EXT',                            'e_message' => 'illegal:' . _('Last pass change')),
    'is_admin'          => array('validation' => 'OSS_DIGIT, OSS_NULLABLE',                            'e_message' => 'illegal:' . _('Global admin')),
    'template_id'       => array('validation' => 'OSS_HEX',                                            'e_message' => 'illegal:' . _('Menu template')),
    'assets[]'          => array('validation' => 'OSS_HEX, OSS_NULLABLE',                              'e_message' => 'illegal:' . _('Assets')),
    'sensors[]'         => array('validation' => 'OSS_HEX, OSS_NULLABLE',                              'e_message' => 'illegal:' . _('Sensors'))
);


if ($mode == 'update')
{
    $validate['pass1']['validation']  = 'OSS_PASSWORD, OSS_NULLABLE';
    $validate['pass2']['validation']  = 'OSS_PASSWORD, OSS_NULLABLE';
}

if ($pro && !$is_my_profile)
{
    $validate['entities[]'] = array('validation' => 'OSS_HEX',                                         'e_message' => 'illegal:' . _('Entities'));
}
else
{
    $validate['company']    = array('validation' => 'OSS_ALPHA, OSS_PUNC, OSS_AT, OSS_NULLABLE',       'e_message' => 'illegal:' . _('Company'));
    $validate['department'] = array('validation' => 'OSS_ALPHA, OSS_PUNC, OSS_AT, OSS_NULLABLE',       'e_message' => 'illegal:' . _('Department'));
}


/* AJAX validation using GET method */
if (GET('ajax_validation') == TRUE)
{
    $data['status']    = 'OK';
    $validation_errors = validate_form_fields('GET', $validate);

    if (is_array($validation_errors) && !empty($validation_errors))
    {
        $data['status'] = 'error';
        $data['data']   = $validation_errors;
    }
    else
    {
        switch($_GET['name'])
        {
            case 'login':

                $login    = trim(GET($_GET['name']));
                $s_login  = escape_sql($login, $conn, FALSE);
                $u_list   = Session::get_list($conn, "WHERE login='".$s_login."'");

                if (count($u_list) > 0)
                {
                    $data['status']              = 'error';
                    $data['data'][$_GET['name']] = _('User login already exists').'. <br/>'._('Entered value').": '<strong>".Util::htmlentities($login)."</strong>'";
                }

            break;

            case 'pass1':
            case 'pass2':

            // Get password length
            $conf = $GLOBALS['CONF'];

            $pass_length_min = $conf->get_conf('pass_length_min');
            $pass_length_min = intval($pass_length_min);
            $pass_length_min = ($pass_length_min < 7 || $pass_length_min > 255) ? 7 : $pass_length_min;

            $pass_length_max = $conf->get_conf('pass_length_max');
            $pass_length_max = intval($pass_length_max);
            $pass_length_max = ($pass_length_max > 255 || $pass_length_max < $pass_length_min) ? 255 : $pass_length_max;

            $pass_expire_min = ($conf->get_conf('pass_expire_min')) ? $conf->get_conf('pass_expire_min') : 0;

            $pass = GET($_GET['name']);

            if (mb_strlen($pass) < $pass_length_min)
            {
                $data['status']        = 'error';
                $data['data']['pass1'] = _('Password is not long enough').' ['._('Minimum password size is').' '.$pass_length_min.']';
            }
            elseif (mb_strlen($pass) > $pass_length_max)
            {
                $data['status']         = 'error';
                $data['data']['pass1']  = _('Password is too long').' ['._('Maximum password size is').' '.$pass_length_max.']';
            }
            elseif (!Session::pass_check_complexity(utf8_decode($pass)))
            {
                $data['status']        = 'error';
                $data['data']['pass1'] = _("The password does not meet the password complexity requirements.<br/>Password should contain lowercase and uppercase letters, digits and special characters");
            }

            break;
        }
    }

    $db->close();

    echo json_encode($data);
    exit();
}

//Check Token

if (!isset($_POST['ajax_validation_all']) || POST('ajax_validation_all') == FALSE)
{
    if (!Token::verify('tk_fuser', POST('token')))
    {
        Token::show_error();
        exit();
    }
}

$greybox           = POST('greybox');
$uuid              = POST('uuid');
$login             = POST('login');
$user_name         = POST('user_name');
$email             = POST('email');
$language          = POST('language');
$tzone             = POST('tzone');
$login_method      = POST('login_method');

$c_pass            = POST('c_pass');

if ($login_method == 'pass')
{
    $pass1 = POST('pass1');
    $pass2 = POST('pass2');
}
else
{
    unset($validate['pass1']);
    unset($validate['pass2']);
}

$last_pass_change  = POST('last_pass_change');
$first_login       = POST('first_login');
$is_admin          = 0;
$template_id       = POST('template_id');

$exp_user = '';

if ($am_i_admin)
{
    if (isset($_POST['is_admin']) && $_POST['is_admin'] != '')
    {
        $is_admin = POST('is_admin');
    }
    else
    {
        if ($login == AV_DEFAULT_ADMIN)
        {
            $is_admin = 0;
        }
        elseif (Session::is_admin($conn, $login))
        {
            $is_admin = 1;
        }
    }
}


$sel_assets = POST('assets');
$sel_assets = (is_array($sel_assets) && !empty($sel_assets)) ? $sel_assets : array();

$sel_sensors = POST('sensors');
$sel_sensors = (is_array($sel_sensors) && !empty($sel_sensors)) ? $sel_sensors : array();

if ($pro)
{
    $entities = POST('entities');
    $entities = (is_array($entities) && !empty($entities)) ? $entities : array();

    if ($is_my_profile)
    {
        unset($validate["entities[]"]);
    }
}
else
{
    $company    = POST('company');
    $department = POST('department');

    if ($mode == 'insert')
    {
        unset($validate["template_id"]);
    }
}

$validation_errors = validate_form_fields('POST', $validate);


//Extended validation

if (empty($validation_errors['login']))
{
    //Checking permissions to create or modify users
    if ($mode == 'insert')
    {
        if (!$am_i_admin && !$am_i_proadmin)
        {
            $validation_errors['login'] = _("You don't have permission to create users");
        }
        else
        {
            $s_login = escape_sql($login, $conn, FALSE);
            $u_list  = Session::get_list($conn, "WHERE login='".$s_login."'");

            if (count($u_list) > 0)
            {
                $validation_errors['login'] = _('User login already exists').'. <br/>'._('Entered value').": '<strong>".Util::htmlentities($login)."</strong>'";
            }
        }
    }
    else
    {
        $condition_1 = (($am_i_admin && $login != AV_DEFAULT_ADMIN) || $is_my_profile);

        $condition_2 = ($am_i_proadmin && Session::userAllowed($login) == 2);

        if (!($condition_1 || $condition_2))
        {
            $validation_errors['login'] = _("You don't have permission to modify this user");
        }
    }
}


//Checking password field requirements
if (empty($validation_errors['c_pass']) && empty($validation_errors['pass2']) && empty($validation_errors['pass1']))
{
    //Checking current password
    $admin_login_method = $myself->get_login_method();

    if (($admin_login_method != 'ldap' && !$myself->is_password_correct($c_pass)) && !Session::login_ldap($myself->get_login(), $c_pass))
    {
        $validation_errors['c_pass'] = _('Authentication failure').'. '._("Current password is not correct");
    }

    if (empty($validation_errors['pass']))
    {
        if ($login_method != 'ldap' && (!empty($pass1) || !empty($pass2)))
        {
            //Getting password length
            $conf = $GLOBALS['CONF'];

            $pass_length_min = $conf->get_conf('pass_length_min');
            $pass_length_min = intval($pass_length_min);
            $pass_length_min = ($pass_length_min < 7 || $pass_length_min > 255) ? 7 : $pass_length_min;

            $pass_length_max = $conf->get_conf('pass_length_max');
            $pass_length_max = intval($pass_length_max);
            $pass_length_max = ($pass_length_max > 255 || $pass_length_max < $pass_length_min) ? 255 : $pass_length_max;

            $pass_expire_min = ($conf->get_conf('pass_expire_min')) ? $conf->get_conf('pass_expire_min') : 0;

            if (0 != strcmp($pass1, $pass2))
            {
                $validation_errors['pass1'] = _('Authentication failure').'. '._('Passwords mismatch');
            }
            elseif (mb_strlen($pass1) < $pass_length_min)
            {
                $validation_errors['pass1'] = _('Password is not long enough').' ['._('Minimum password size is').' '.$pass_length_min.']';
            }
            elseif (mb_strlen($pass1) > $pass_length_max)
            {
                $validation_errors['pass1'] = _('Password is too long').' ['._('Maximum password size is').' '.$pass_length_max.']';
            }
            elseif (!Session::pass_check_complexity($pass1))
            {
                $validation_errors['pass1'] = _("The password does not meet the password complexity requirements.<br/>Password should contain lowercase and uppercase letters, digits and special characters");
            }
            elseif ($mode == 'update')
            {
                if ($pass_expire_min > 0 && Util::date_diff_min($last_pass_change, gmdate('Y-m-d H:i:s')) < $pass_expire_min && !Session::am_i_admin())
                {
                    $validation_errors['pass1'] = _('Password lifetime is too short to allow change. Wait a few minutes...');
                }
                elseif (Log_action::recent_pass_exists($conn, $login, $pass1)) {
                    $validation_errors['pass1'] = _('This password is recently used. Try another');
                }
            }
        }
    }
}


//Checking entities field requirements
if (empty($validation_errors['entities[]']))
{
    //Check allowed entities
    if ($pro && !$is_my_profile)
    {
        foreach ($entities as $ent_id)
        {
            if (!Acl::entityAllowed($ent_id))
            {
                $validation_errors['entities[]'] = _("You don't have permission to create users at this level");
                break;
            }
        }
    }
}


if (is_array($validation_errors) && !empty($validation_errors))
{
    $data['status'] = 'error';
    $data['data']   = $validation_errors;
}
else
{
    $data['status'] = 'OK';
    $data['data']   = $validation_errors;
}


if (POST('ajax_validation_all') == TRUE)
{
    echo json_encode($data);
    exit();
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html>
    <head>
        <title> <?php echo _('OSSIM Framework'); ?> </title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
        <meta http-equiv="Pragma" content="no-cache">
        <link type="text/css" rel="stylesheet" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    </head>

    <body>

    <?php

    $msg = NULL;

    if (empty($data['data']['login']))
    {
        if ($data['status'] == 'error')
        {
            $txt_error = "<div>"._('The following errors occurred').":</div>
                          <div style='padding:10px;'>".implode('<br/>', $validation_errors).'</div>';

            $config_nt = array(
                'content' => $txt_error,
                'options' => array (
                    'type'          => 'nf_error',
                    'cancel_button' => FALSE
                ),
                'style'   => 'width: 80%; margin: 20px auto; text-align: left;'
            );

            $nt = new Notification('nt_1', $config_nt);
            $nt->show();

            Util::make_form('POST', "user_form.php?login=$login");
            exit();
        }
        else
        {
            //Create or update user

            $insert_menu      = FALSE;
            $language_changed = FALSE;
            $tzone_changed    = FALSE;

            // MENUS
            if (!$pro && $am_i_admin && !$is_my_profile)
            {
                $insert_menu = TRUE;
                $perms       = array();

                list($menu_perms, $perms_check) = Session::get_menu_perms($conn);

                foreach($menu_perms as $mainmenu => $menus)
                {
                    foreach($menus as $key => $menu)
                    {
                        $cond_1 = (POST("menu_perm$key") == 'on');
                        $cond_2 = ($perms_check[$mainmenu][$key] == TRUE);

                        if ($cond_1 && $cond_2)
                        {
                            $perms[$key] = TRUE;
                        }
                    }
                }
            }

            if ($mode == 'insert')
            {
                $msg = 'created';

                if ($insert_menu == TRUE)
                {
                    //New template
                    $template_id = Session::update_template($conn, $login."_perms", $perms);
                }

                Session::insert($conn, $login, $login_method, $pass1, $user_name, $email, $template_id, $entities, $sel_sensors, $sel_assets,
                    $company, $department, $language, $first_login, $tzone, $is_admin);

                User_config::copy_panel($conn, $login);

                $_SESSION['_user_vision'] = ($pro) ? Acl::get_user_vision($conn) : Session::get_user_vision($conn);

                Util::memcacheFlush();

                Session::log_pass_history($login);
            }
            else
            {
                $msg = 'updated';

                if ($insert_menu == TRUE)
                {
                    Session::update_template($conn, $login.'_perms', $perms, $template_id);
                }

                $error = 0;

                if (($am_i_admin || $am_i_proadmin) && !$is_my_profile)
                {
                    Session::update($conn, $login, $login_method, $user_name, $email, $template_id, $entities, $sel_sensors, $sel_assets,
                       $company, $department, $language, $first_login, $tzone, $is_admin);

                    Util::memcacheFlush();
                }
                else
                {
                    $error = Session::update_user_light($conn, $login, $login_method, $user_name, $email, $company, $department, $language,
                       $first_login, $is_admin, $tzone);

                    if ($error == 0)
                    {
                        Util::memcacheFlush();

                        if ($is_my_profile && $language != $_SESSION['_user_language'])
                        {
                            $_SESSION['_user_language'] = $language;
                            ossim_set_lang($language);

                            $language_changed = TRUE;
                        }

                        $tzone_diff = Session::get_timezone($tzone);

                        if ($is_my_profile && $_SESSION['_timezone'] != $tzone_diff)
                        {
                            $_SESSION['_timezone'] = $tzone_diff;

                            $tzone_changed         = TRUE;
                        }

                        Session_activity::force_user_logout($conn, $login);
                    }
                    else
                    {
                        $msg = 'unknown_error';
                    }
                }

                // Change Pass
                if ($error == 0 && $login_method != 'ldap' && !empty($pass1) && !empty($pass2))
                {
                    //Set new pass
                    Session::change_pass($conn, $login, $pass1, NULL);

                    Session::log_pass_history($login);

                    // Note: session_start will show an alert here. Calling to expire when back to users.php
                    if (method_exists('Session_activity', 'expire_my_others_sessions'))
                    {
                        $exp_user = $login;
                    }
                }
                // Special case LDAP
                if ($error == 0 && $login_method == 'ldap')
                {
                    Session::change_pass($conn, $login, $login, NULL, FALSE);
                }
            }

            if ($language_changed)
            {
                $av_menu = new Menu($conn);
                $db->close();

                $av_menu->set_menu_option('configuration', 'administration');
                $av_menu->set_hmenu_option('users');

                $_SESSION['av_menu'] = serialize($av_menu);

                //To display update message
                $_SESSION['msg'] = $msg;

                ?>
                <script type="text/javascript">
                    top.parent.document.location.href = '/ossim/home/index.php';
                </script>
                <?php
                exit();
            }

            $db->close();

            /*
            if ($tzone_changed)
            {
                ?>
                <script type="text/javascript">top.topmenu.refresh_hour();</script>
                <?php
            }
            */

            if ($greybox)
            {
                ?>
                <script type="text/javascript">parent.GB_hide();</script>
                <?php
            }


            if ($is_my_profile)
            {
                $url  = "user_form.php?login=$login";
                $url .= ($msg != NULL) ? "&msg=$msg" : '';

                if ($exp_user != '')
                {
                    $url .= "&action=expire_session&token=".Token::generate('tk_f_users');
                }
            }
            else
            {
                $url = 'users.php';
                $url .= ($msg != NULL) ? "?msg=$msg" : '';

                if ($exp_user != '')
                {
                    $url .= ($msg != NULL) ? '&' : '?';
                    $url .= "action=expire_session&user_id=$exp_user&token=".Token::generate('tk_f_users');
                }
            }

            ?>
            <script type='text/javascript'>document.location.href="<?php echo $url;?>"</script>
            <?php
        }
    }
    else
    {
        $db->close();

        if ($greybox)
        {
            $config_nt = array(
                'content' => _('Invalid action - Operation cannot be completed'),
                'options' => array (
                    'type'          => 'nf_error',
                    'cancel_button' => FALSE
                ),
                'style'   => 'width: 80%; margin: 20px auto; text-align: left;'
            );

            $nt = new Notification('nt_1', $config_nt);
            $nt->show();
        }
        else
        {
            $url = Menu::get_menu_url('users.php?msg=unknown_error', 'configuration', 'administration', 'users');
            ?>
            <script type='text/javascript'>document.location.href="<?php echo $url?>";</script>
            <?php
        }
    }
    ?>
    </body>
</html>
