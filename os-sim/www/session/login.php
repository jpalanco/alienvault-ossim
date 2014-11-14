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

error_reporting(0);

require_once 'av_init.php';


function bad_browser()
{
    $u_agent = $_SERVER['HTTP_USER_AGENT'];

    if(preg_match('/MSIE 6/i',$u_agent))
    {
        return 'Internet Explorer 6';
    }

    if(preg_match('/MSIE 5/i',$u_agent))
    {
        return 'Internet Explorer 5';
    }

    return '';
}

function dateDiff($startDate, $endDate)
{
    // Parse dates for conversion
    $startArry = date_parse($startDate);
    $endArry   = date_parse($endDate);

    // Convert dates to Julian Days
    $start_date = gregoriantojd($startArry['month'], $startArry['day'], $startArry['year']);
    $end_date   = gregoriantojd($endArry['month'], $endArry['day'], $endArry['year']);

    // Return difference
    return round(($end_date - $start_date), 0);
}


/* Logout */

$action = REQUEST('action');
ossim_valid($action, OSS_ALPHA, OSS_NULLABLE, 'illegal:' . _('Action'));

if ($action == 'logout')
{
    $c_user = Session::get_session_user();

    if (!empty($c_user))
    {
        $infolog = array($c_user);

        Log_action::log(2, $infolog);

        /* Logout from API
            - If there are more than one user with the same login in the system,
              we don't remove API cookie ()
        */

        //Update admin info
        list($db, $conn) = Ossim_db::get_conn_db();

        $sa_list = Session_activity::get_list($conn, "WHERE login = '$c_user'");

        $db->close();

        if (count($sa_list) == 1)
        {
            $client = new Alienvault_client();
            $client->auth()->logout();
        }
    }

    Session::logout();
    exit();
}


/****************************************************
 **************** Configuration Data ****************
 ****************************************************/

$conf = $GLOBALS['CONF'];

if (!$conf)
{
    $conf = new Ossim_conf();
    $GLOBALS['CONF'] = $conf;
}

//Google Maps Key
$map_key = $conf->get_conf('google_maps_key');

if ($map_key == '')
{
    $map_key = 'ABQIAAAAbnvDoAoYOSW2iqoXiGTpYBTIx7cuHpcaq3fYV4NM0BaZl8OxDxS9pQpgJkMv0RxjVl6cDGhDNERjaQ';
}


//If user is logged, redirect to home
if (Session::get_session_user() != '')
{
     header("Location: /ossim");
     exit();
}

$embed     = REQUEST('embed');
$user      = REQUEST('user');
$pass      = base64_decode(REQUEST('pass'));
$pass1     = base64_decode(REQUEST('pass1'));
$accepted  = POST('first_login');
$email     = REQUEST("email");
$mobile    = REQUEST("mobile");
$fullname  = REQUEST('fullname');

//Bookmark string
$bookmark  = REQUEST('bookmark_string');

$pass      = trim($pass);
$pass1     = trim($pass1);
$email     = trim($email);
$fullname  = trim($fullname);

if ($fullname == '')
{
    $fullname = 'AlienVault admin';
}

$company   = REQUEST('company');
$location  = REQUEST('search_location');
$lat       = REQUEST('latitude');
$lng       = REQUEST('longitude');
$country   = REQUEST('country');

ossim_valid($embed, 'true', OSS_NULLABLE,                         'illegal:' . _('Embed'));
ossim_valid($user, OSS_USER, OSS_NULLABLE,                        'illegal:' . _('User name'));
ossim_valid($mobile, OSS_LETTER, OSS_NULLABLE,                    'illegal:' . _('Mobile'));
ossim_valid($accepted, OSS_NULLABLE, 'yes', 'no',                 'illegal:' . _('First login'));
ossim_valid($email, OSS_MAIL_ADDR, OSS_NULLABLE,                  'illegal:' . _('E-mail'));
ossim_valid($fullname, OSS_ALPHA, OSS_PUNC, OSS_AT, OSS_NULLABLE, 'illegal:' . _('Full Name'));
ossim_valid($company, OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE,      'illegal:' . _('Company Name'));
ossim_valid($location, OSS_ALPHA, OSS_PUNC_EXT, OSS_NULLABLE,     'illegal:' . _('Location'));
ossim_valid($lat, OSS_DIGIT, OSS_DOT, OSS_SCORE, OSS_NULLABLE,    'illegal:' . _('Latitude'));
ossim_valid($lng, OSS_DIGIT, OSS_DOT, OSS_SCORE, OSS_NULLABLE,    'illegal:' . _('Longitude'));
ossim_valid($country, OSS_LETTER, OSS_NULLABLE,                   'illegal:' . _('Country'));


if (ossim_error())
{
    echo ossim_error();
    exit();
}


if (!preg_match("/^[A-Za-z0-9\/\-_#]*$/", $bookmark))
{
    $bookmark = '';
}

/* Version */

$version     = $conf->get_conf('ossim_server_version');

$opensource  = (!preg_match("/pro|demo/i",$version))  ? TRUE : FALSE;
$demo        = (preg_match("/.*demo.*/i",$version))   ? TRUE : FALSE;
$pro         = (preg_match("/.*pro.*/i",$version))    ? TRUE : FALSE;


/* Title */
$title = _('AlienVault '.($opensource ? 'OSSIM' : 'USM'));


/* Logo */

$logo_type = '';

if ($pro)
{
    $logo_type .= '_siem';
}
elseif ($demo)
{
    $logo_type .= '_siemdemo';
}

$logo   = 'logo'.$logo_type.'.png';
$b_logo = 'ossim'.$logo_type.'.png';



$failed       = TRUE;
$default_user = '';
$first_login  = $conf->get_conf('first_login');

if(Session::is_pro())
{
    $trial_days = Session::trial_days_to_expire();

    if($trial_days <= 0)
    {
        if(file_exists('/usr/share/ossim/www/session/trial/index.php'))
        {
            header("Location: /ossim/session/trial/index.php");
            exit();
        }
    }

}

// FIRST LOGIN

$cnd_1 = ($first_login == 'yes' && $accepted == 'yes');
$cnd_2 = ($pass != '' &&  $pass1 != '' && $pass == $pass1);
$cnd_3 = ($email != '' && $fullname != '');


if ($cnd_1 && $cnd_2 && $cnd_3)
{
    ossim_valid($pass, OSS_PASSWORD,  'illegal:' . _('Password'));
    ossim_valid($pass1, OSS_PASSWORD, 'illegal:' . _('Repeat Password'));

    if (ossim_error())
    {
        die(ossim_error());
    }

    $config      = new Config();
    $first_login = 'no';

    $config->update('first_login', 'no');

    //Update admin info
    list($db, $conn) = Ossim_db::get_conn_db();

    $local_tz = trim(`head -1 /etc/timezone`);
    Session::update_user_light($conn, AV_DEFAULT_ADMIN, 'pass', $fullname, $email, $company, '', 'en_GB', 0, 1, $local_tz);

    if ($company != '')
    {
        Session::update_default_entity_name($conn,$company);
    }

    Session::change_pass($conn, AV_DEFAULT_ADMIN, $pass);

    // Insert new location
    if ($location != '' && $lat != '' && $lng != '')
    {
        $default_ctx_id = str_replace('-', '', strtoupper($conf->get_conf('default_context_id')));
        if (empty($default_ctx_id))
        {
            $default_ctx = '00000000000000000000000000000000';
        }


        $location_name = ($company != '') ? $company.' '._('Location') : $title.' '._('Location');

        $new_location_id = Locations::insert($conn, $default_ctx_id, $location_name, '', $location, $lat, $lng, $country);
        $sensors = Av_sensor::get_basic_list($conn);

        foreach ($sensors as $sensor)
        {
            Locations::insert_related_sensor($conn, $new_location_id, $sensor['id']);
        }
    }

    $db->close();

    $default_user = AV_DEFAULT_ADMIN;
}


// LOGIN

$cnd_1 = (!empty($user) && is_string($user));
$cnd_2 = (!empty($pass) && is_string($pass));

if ($cnd_1 && $cnd_2)
{
    $session = new Session($user, $pass, '');
    $config  = new Config();

    //Disable first_login
    if ($accepted == 'yes')
    {
        $config->update('first_login', 'no');
    }

    $is_disabled = $session->is_user_disabled();


    if ($is_disabled == FALSE)
    {
        $login_return      = $session->login();
        $first_user_login  = $session->get_first_login();
        $last_pass_change  = $session->last_pass_change();
        $login_exists      = $session->is_logged_user_in_db();
        $lockout_duration  = intval($conf->get_conf('unlock_user_interval')) * 60;


        if ($login_return != TRUE)
        {
            $_SESSION['_user'] = '';

            $infolog = array($user);
            Log_action::log(94, $infolog);

            $failed         = TRUE;
            $bad_pass       = TRUE;
            $failed_retries = $conf->get_conf('failed_retries');

            if ($login_exists && !$is_disabled && $lockout_duration > 0)
            {
                $_SESSION['bad_pass'][$user]++;

                if ($_SESSION['bad_pass'][$user] >= $failed_retries && $user != AV_DEFAULT_ADMIN)
                {
                    // Auto-disable user
                    $disabled = TRUE;
                    $session->disable_user();
                }
            }
        }
        elseif (!$is_disabled)
        {
            $_SESSION['bad_pass'] = '';

            $pass_expire_max = ($conf->get_conf('pass_expire') > 0 && $conf->get_conf('pass_expire') != 'yes' && $conf->get_conf('pass_expire') != 'no') ? $conf->get_conf('pass_expire') : 0;

            $pass_length_min = ($conf->get_conf('pass_length_min')) ? $conf->get_conf('pass_length_min') : 7;

            if ($first_login == '' || $first_login == 0 || $first_login == 'no')
            {
                $accepted = 'yes';
            }

            $failed = FALSE;

            if ($accepted == 'yes')
            {
                $first_login = 'no';

    	        $client  = new Alienvault_client($user);
    	        $client->auth()->login($user,$pass);

                $iv_size           = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB); //get vector size on ECB mode
                $iv                = mcrypt_create_iv($iv_size, MCRYPT_RAND); //Creating the vector
                $_SESSION['mdspw'] = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $conf->get_conf('md5_salt') , $pass, MCRYPT_MODE_ECB, $iv);


                $infolog = array($user);
                Log_action::log(1, $infolog);

                if ($first_user_login)
                {
                    header("Location: first_login.php");
                }
                elseif ($pass_expire_max > 0 && dateDiff($last_pass_change,date('Y-m-d H:i:s')) >= $pass_expire_max)
                {
                    header("Location: first_login.php?expired=1");
                }
                elseif ($user == AV_DEFAULT_ADMIN && $pass == 'admin')
                {
                    header("Location: first_login.php?changeadmin=1");
                }
                else
                {
                    if ($mobile != '')
                    {
                        header("Location: ../statusbar/mobile.php?login=".urlencode($_SESSION['_remote_login'])."&screen=$mobile");
                    }
                    else
                    {
                        if (Session::am_i_admin())
                        {
                            if (Welcome_wizard::show_wizard_status_bar())
                            {
                                $_SESSION['_welcome_wizard_bar'] = TRUE;
                            }
                            else
                            {
                                unset($_SESSION['_welcome_wizard_bar']);
                            }
                        }

                        header("Location: /ossim/$bookmark");
                    }
                }

                exit();
            }
        }
    }
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title><?php echo $title;?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <link rel="Shortcut Icon" type="image/x-icon" href="/ossim/favicon.ico"/>
    <link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <link rel="stylesheet" type="text/css" href="/ossim/style/fancybox/jquery.fancybox-1.3.4.css"/>
    <script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
    <script type="text/javascript" src="/ossim/js/jquery.base64.js"></script>
    <script type="text/javascript" src="/ossim/js/fancybox/jquery.fancybox-1.3.4.pack.js"></script>
    
    <?php
    // Welcome screen specific styles and javascript
    if ($first_login == 'yes')
    {
        ?>
        <link rel="stylesheet" type="text/css" href="/ossim/style/session/login_welcome.css"/>
        <link rel="stylesheet" type="text/css" href="../style/tipTip.css" />
        <script type="text/javascript" src="../js/jquery.tipTip.js"></script>
        <script type="text/javascript" src="../js/jquery.pstrength.js"></script>
        <script type="text/javascript" src="https://maps-api-ssl.google.com/maps/api/js?sensor=false"></script>
        <script type="text/javascript" src="../js/jquery.autocomplete_geomod.js"></script>
        <script type="text/javascript" src="../js/geo_autocomplete.js"></script>
        <script type="text/javascript" src="../js/notification.js"></script>
        <script type="text/javascript" src="../js/av_map.js.php"></script>
        <link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css"/>
        <?php
    }
    
    // Normal dark css login screen
    else
    {
        ?>
        <link rel="stylesheet" type="text/css" href="/ossim/style/session/login.css"/>
        <?php
    }
    
    // Add some css for mobile devices
    if (Mobile::is_mobile_device())
    {
        ?>
        <link rel="stylesheet" type="text/css" href="/ossim/style/session/login_mobile.css"/>
        <?php
    }
    ?>
    
    <style type="text/css">
        
        .pass_container
        {
            width: 203px !important;     
        }
    
        <?php
        if (!Mobile::is_mobile_device() && $embed == 'true')
        {
            ?>
            #c_login
            {
                margin: auto;
            }
            <?php
        }
        ?>
    </style>

    <script type='text/javascript'>

        var h_window;

        function show_help()
        {
             var width  = 1024;
             var height = 768;

             var left = (screen.width/2)-(width/2);
             var top  = (screen.height/2)-(height/2);

             var w_parameters = "left="+left+", top="+top+", height="+height+", width="+width+", location=no, menubar=no, resizable=yes, scrollbars=yes, status=no, titlebar=no";

             var w_url  = 'https://www.alienvault.com/help/product/user_management_guide';
             var w_name = 'User Management Guide';

             h_window = window.open(w_url, w_name, w_parameters);
             h_window.focus();
        }


        jQuery.fn.shake = function(intShakes /*Amount of shakes*/, intDistance /*Shake distance*/, intDuration /*Time duration*/) {
            this.each(function() {
                $(this).css({position:'relative'});
                for (var x=1; x<=intShakes; x++)
                {
                    $(this).animate({left:(intDistance*-1)}, (((intDuration/intShakes)/4)))
                    .animate({left:intDistance}, ((intDuration/intShakes)/2))
                    .animate({left:0}, (((intDuration/intShakes)/4)));
                }
            });

            return this;
        };


        <?php
        if ($first_login == 'yes')
        {
            ?>
            function check()
            {
                if ($('#fullname').val() =='' || $('#pass').val() =='' ||  $('#pass1').val() =='' ||  $('#email').val()=='')
                {
                    alert("<?php echo _('Please fill all fields. Thank you')?>")
                    return false;
                }

                if ($('#pass').val()!='' &&  $('#pass1').val()!='' && $('#pass').val()!=$('#pass1').val())
                {
                    alert("<?php echo _('Passwords do not match')?>")
                    return false;
                }

                var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
                if (reg.test($('#email').val()) == false)
                {
                    alert("<?php echo _('Invalid E-mail address')?>")
                    return false;
                }

                $('#pass').val($.base64.encode($('#pass').val()))
                $('#pass1').val($.base64.encode($('#pass1').val()))

                return true;
            }

            function toggle_map()
            {
                var section = '.geolocation';

                $(section).toggle();

                av_map.draw_map();

                document.getElementById('down_button').scrollIntoView();

                return false;
            }
            <?php
        }
        ?>


        $(document).ready(function()
        {
            var _hash = location.hash;
            $('#bookmark_string').val(_hash);
            if (typeof(document.f_login) != 'undefined')
            {
                 document.f_login.user.focus();
            }

        	$("#ftpass").fancybox({
        		'titlePosition'		: 'inside',
        		'transitionIn'		: 'none',
        		'transitionOut'		: 'none',
        		'showCloseButton'   : false,
        		onStart: function(){
                    $('#forgotpass').show();
                    return true;
                },
        		onClosed: function(){
                    $('#forgotpass').hide();
                    return true;
                }
        	});

            <?php
            if (isset($bad_pass) || $is_disabled  || $disabled)
            {
                ?>
                $('#loginw').shake(5, 6, 360);
                <?php
            }

            if ($first_login == 'yes')
            {
                ?>

                // Scroll down to view de submit button (small screens)
                document.getElementById('down_button').scrollIntoView();

                $('#pass').pstrength();
                $('#pass1').pstrength();

                av_map = new Av_map('c_map');

                if(Av_map.is_map_available())
                {
                    av_map.draw_map();

                    $('#search_location').geo_autocomplete(new google.maps.Geocoder, {
    					mapkey: '<?php echo $map_key?>',
    					selectFirst: true,
    					minChars: 3,
    					cacheLength: 50,
    					width: 300,
    					scroll: true,
    					scrollHeight: 330
    				}).result(function(_event, _data) {
    					if (_data)
    					{
    						if (!$('.geolocation').is(':visible'))
    						{
    						    toggle_map();
    						}

    						//Set map coordenate
                            av_map.map.fitBounds(_data.geometry.viewport);

                            var aux_lat = _data.geometry.location.lat();
                            var aux_lng = _data.geometry.location.lng();

                            //console.log(aux_lat);
                            //console.log(aux_lng);

                            av_map.set_location(aux_lat, aux_lng);

                            $('#latitude').val(av_map.get_lat());
                            $('#longitude').val(av_map.get_lng());

                            //Save address

                            av_map.set_address(_data.formatted_address);

                            // Marker (Add or update)

                            av_map.remove_all_markers();
                            av_map.add_marker(av_map.get_lat(), av_map.get_lng());
                            av_map.markers[0].setTitle('<?php echo _('Company location')?>');
                            av_map.markers[0].setMap(av_map.map);

                            av_map.map.setZoom(8);

                            //Get country

    						var country = '';
                            var i       = _data.address_components.length-1;

                            for(i; i >= 0; i--)
                            {
                                var item = _data.address_components[i];

                                if(item.types[0] == 'country')
                                {
                                    country = item.short_name;

                                    break;
                                }
                            }

                            $('#country').val(country);
    					}
    				});


    				$('#view_map').click(function(event){

                        event.preventDefault();
                        toggle_map();
                    });

    				//Search box (Handler Keyup and Blur)
    				av_map.bind_sl_actions();
                }
                else
                {
                    $(".c_location").hide();
                }


                $('#f_login').submit(function(){
                    if (!check())
                    {
                       return false;
                    }
                    $('#down_button').addClass('av_b_processing');
                });
                <?php
            }
            else
            {
                ?>
                $('#f_login').submit(function(){
                    $('#submit_button').addClass('av_b_processing');
                    $('#pass').val($.base64.encode($('#passu').val()));
                });
                <?php
            }
            ?>
        });

    </script>
</head>

<body>

    <?php

    if (Mobile::is_mobile_device())
    {
        ?>
        <div id='c_login'>
            <form method="POST" id='f_login' name='f_login' action="login.php">
                <table align="center" class='noborder'>
                    <tr>
                        <td class="noborder">
                            <table align="center" class="noborder">
                                <tr>
                                    <td class="noborder" style="text-align:center;padding:30px 20px 3px 20px">
                                        <img src="/ossim/pixmaps/<?php echo $b_logo?>" border='0'/>
                                    </td>
                                </tr>

                                <tr>
                                    <td class="noborder center" style="padding:10px">
                                        <table id='t_login' class='opacity' align="center" cellspacing='2' cellpadding='6'>
                                            <tr>
                                                <td class='td_user'> <?php echo _('Username').':'; ?> </td>
                                                <td class="left">
                                                    <input type="text" autocapitalize="off" id="user" name="user" value="<?php echo $default_user?>"/>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td class='td_pass'> <?php echo _('Password').':'; ?> </td>
                                                <td class="left">
                                                    <input type="password" id="passu" name="passu"/>
                                                    <input type="hidden" id="pass" name="pass"/>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <tr>
                                    <td class="center" style="height:24px;font-size: 12px;">
                                        <input type="checkbox" value="status" name="mobile" checked/> <?php echo _('Mobile Console') ?>
                                    </td>
                                </tr>

                                <tr>
                                    <td class="center" style="padding:10px;">
                                        <input type="submit" id="submit_button" value="<?php echo _('Login'); ?>" style="width:100%"/>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <?php
        if (isset($bad_pass))
        {
            ?>
            <p style='color:red' class='uppercase'>
                <?php echo _('Wrong user or password')?>
                <br/><br/>
                <a href="javascript:void(0);" onclick="$('#nt_pass').show();"><?php echo ('Lost Password?')?></a>
            </p>
            <?php
        }
        elseif ($is_disabled)
        {
            ?>
            <p style='color:#888' class='uppercase'>
                <?php printf(_("The User <strong> %s </strong> is <strong> disabled </strong>"), $user); ?>
                <br/>&nbsp;
                <?php echo _('Please contact the administrator')?>
            </p>
            <?php
        }

        if ($disabled)
        {
            ?>
            <p style='color:#16A7C9'>
                <?php echo _('This user has been disabled for security reasons.<br/> Please contact with the administrator')?>
            </p>
            <?php
        }
    }
    else
    {
        if ($failed && $first_login != 'yes')
        {
            if($embed != 'true')
            {
                ?>
                <script type='text/javascript'>
                    if (location.href != top.location.href)
                    {
                        top.location.href = location.href;
                    }
                </script>
                <?php
            }
            ?>

            <div id='c_login'>
                <form <?php if($embed == 'true'){ ?>target="_top" <?php } ?>name="f_login" id="f_login" method="POST" action="login.php">
                    <input type="hidden" name="embed" value="<?php echo $embed?>"/>
                    <input type="hidden" name="bookmark_string" id="bookmark_string" value=''/>

                    <table id='loginw' class='table_embed noborder'>
                        <tr>
                            <td class="noborder">
                                <table class="table_embed2 noborder">

                                    <tr>
                                        <td class="noborder" style="text-align:center;padding:20px 0px 20px 8px">
                                            <?php
                                            if (file_exists('../tmp/headers/_login_logo.png'))
                                            {
                                                ?>
                                                <img src="../tmp/headers/_login_logo.png" border='0' width="300" height="60" class="img_logo"/>
                                                <?php
                                            }
                                            else
                                            {
                                                ?>
                                                <img src="/ossim/pixmaps/<?php echo $b_logo?>"/>
                                                <?php
                                            }
                                            ?>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td class="noborder center" style="padding-top:20px">

                                            <table id='t_login' align="center" cellspacing='4' cellpadding='2'>
                                                <tr>
                                                    <td class="td_user uppercase noborder <?php if ($pro) { echo 'white'; } ?>">
                                                        <?php echo _('Username'); ?>
                                                    </td>
                                                    <td class="left noborder">
                                                        <input type="text" size='25' id='user' name="user" value="<?php echo $default_user ?>" />
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="td_pass uppercase noborder <?php if ($pro) { echo 'white'; } ?>">
                                                        <?php echo _('Password'); ?>
                                                    </td>
                                                    <td class="left noborder">
                                                        <input type="password" onfocus="$('#wup').hide(); $('#nt_1').hide(); $('#nt_pass').hide();" id="passu" size='25' name="passu"/>
                                                        <input type="hidden" id="pass" name="pass"/>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="td_pass uppercase noborder"></td>
                                                    <td class="left noborder">
                                                        <a id="ftpass" href="#forgotpass" class="link"><?php echo _('Forgot Password?')?></a>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <td class="td_pass uppercase noborder"></td>
                                                    <td class="left noborder" style="padding:15px 0px 0px 4px">
                                                        <input type="submit" class="big button" id="submit_button" value="<?php echo _('Login'); ?>"/>
                                                    </td>
                                                </tr>
                                            </table>

                                        </td>
                                    </tr>

                                    <tr>
                                        <td class="noborder" style="text-align:center" id="wup">
                                        <?php
                                            if (isset($bad_pass))
                                            {
                                                ?>
                                                <p style='color:red' class='uppercase'>
                                                    <?php echo _('Wrong user or password')?>
                                                </p>
                                                <?php
                                            }
                                            else if ($is_disabled)
                                            {
                                                ?>
                                                <p style='color:#888' class='uppercase'>
                                                    <?php
                                                    printf(_("The User <strong> %s </strong> is <strong> disabled </strong>"), $user);?>
                                                    <br/>&nbsp;
                                                    <?php echo _("Please contact the administrator")?>.
                                                </p>
                                                <?php
                                            }

                                            if ($disabled)
                                            {
                                                ?>
                                                <p style='color:#16A7C9'>
                                                    <?php echo _("This user has been disabled for security reasons.<br/> Please contact with the administrator.")?>
                                                </p>
                                                <?php
                                            }
                                        ?>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>

                </form>

                <?php
                if (($br = bad_browser()) != '')
                {
                    $txt_error = _("<strong>Warning!</strong> $br is <strong>not compatible</strong> with OSSIM.
                                    <br/> Please use Internet Explorer 7 (or newer), Firefox or Chrome");

                    $config_nt = array(
                        'content' => $txt_error,
                        'options' => array (
                            'type'          => 'nf_error',
                            'cancel_button' => TRUE
                        ),
                        'style'   => 'width: 350px; font-style: italic; font-size: 11px; margin: 20px auto; text-align: left;'
                    );

                    $nt = new Notification('nt_1', $config_nt);
                    $nt->show();
                }
                ?>


            </div>
            <?php
        }

        // first login
        if ($first_login == 'yes')
        {
            $longitude = 0;
            $latitude  = 0;
            
            // Overwrite logo, welcome uses the same as in home
            $b_logo = ($pro) ? 'av_contrast_logo.png' : 'ossim_contrast_logo.png';
            
            ?>
            
            <div id='c_login'>

                <form <?php if( $embed== 'true'){ ?>target="_top" <?php } ?>name="f_login" id="f_login" method="POST" action="login.php">

                <input type="hidden" name="first_login" value="yes"/>
                <input type="hidden" name="embed" value="<?php echo $embed?>"/>

                <div class='header_welcome'>
                    <div class='header_welcome_logo'><img src="/ossim/pixmaps/logo/<?php echo $b_logo?>"/></div>
                </div>
                
                <table align="center" class='transparent' cellspacing='0' cellpadding='0'>
                    <tr>
                        <td class="noborder">
                            <table align="center" style="padding:1px;border:none;" class='noborder'>
                                <tr>
                                    <td class="noborder">
                                        <table align="center" class="transparent">
                                            <tr>
                                                <td align="center" class="noborder">
                                                    <table class='transparent' width="1200">
                                                        <tr>
                                                            <td class="left noborder" style="padding-top:20px;padding-left:0px">
                                                                <span style="font-size:150%"><?php echo _('Welcome') ?></span>
                                                                <hr class='welcome_hr'>
                                                                <?php echo _("Congratulations on choosing AlienVault as your Unified Security Management tool. Before using your AlienVault,
                                                                              you will need to create an<br>administrator user account.<br><br>If you need more information about AlienVault, please visit")." <a href='http://www.alienvault.com' target='av'>AlienVault.com</a>."?>
                                                                              <br/><br/>
                                                                <br/>
                                                                <span style="font-size:150%"><?php echo _('Administrator Account Creation') ?></span>
                                                                <hr class='welcome_hr'>
                                                                <?php echo _("Create an account to access your AlienVault product.") ?>
                                                                <br/><br/>
                                                            </td>
                                                        </tr>

                                                        <tr><td class='left welcome_required'>* <?php echo _('Asterisks indicate required fields') ?></td></tr>
                                                        
                                                        <tr>
                                                            <td class="left noborder welcome_form_table">
                                                                <table width="100%" cellspacing="0" cellpadding="3" class="transparent">
                                                                    <tr>
                                                                        <td width="20%" class="td_user uppercase left noborder"><?php echo _('Full Name') ?> *</td>
                                                                        <td class="left noborder">
                                                                            <input type="text" name="fullname" id="fullname" maxlength="40"/>
                                                                        </td>
                                                                    </tr>

                                                                    <tr><td class="noborder"></td></tr>

                                                                    <tr>
                                                                        <td width="20%" class="td_user uppercase left noborder"><?php echo _('UserName') ?> *</td>
                                                                        <td class="left noborder grey">
                                                                            <input type="text" name="user" value="<?php echo AV_DEFAULT_ADMIN?>" style="color:#888888" disabled="disabled"/>
                                                                        </td>
                                                                    </tr>

                                                                    <tr><td class="noborder"></td></tr>

                                                                    <tr>
                                                                        <td class="td_user uppercase left noborder"><?php echo _('Password') ?> *</td>
                                                                        <td class="left noborder">
                                                                            <div class="pass_container">
                                                                                <input type="password" id="pass" name="pass"/>
                                                                            </div>
                                                                        </td>
                                                                    </tr>

                                                                    <tr><td class="noborder"></td></tr>

                                                                    <tr>
                                                                        <td class="td_user uppercase left noborder"><?php echo _('Confirm Password') ?> *</td>
                                                                        <td class="left noborder">
                                                                            <div class="pass_container">
                                                                                <input type="password" id="pass1" name="pass1"/>
                                                                            </div>
                                                                        </td>
                                                                    </tr>

                                                                    <tr><td class="noborder"></td></tr>

                                                                    <tr>
                                                                        <td class="td_user uppercase left noborder"><?php echo _('E-mail') ?> *</td>
                                                                        <td class="left noborder">
                                                                            <input type="text" name="email" id="email" maxlength="256"/>
                                                                        </td>
                                                                    </tr>

                                                                    <tr><td class="noborder"></td></tr>

                                                                    <tr>
                                                                        <td class="td_user uppercase left noborder"><?php echo _('Company Name') ?></td>
                                                                        <td class="left noborder grey">
                                                                            <input type="text" name="company" id="company" maxlength="40"/>
                                                                        </td>
                                                                    </tr>

                                                                    <tr><td class="noborder c_location"></td></tr>

                                                                    <tr class='c_location'>
                                                                        <td class="td_user uppercase left noborder">
                                                                            <?php echo _('Location') ?>
                                                                        </td>
                                                                        <td class="left noborder grey">
                                                                            <input type="text" name="search_location" id="search_location" maxlength="40"/>
                                                                            <a href='' id="view_map"><?php echo _("View Map") ?></a>
                                                                        </td>
                                                                    </tr>

                                                                    <tr class="geolocation c_location" style="display:none">
                                                                        <td colspan="2">
                                                                            <input type="hidden" name="latitude"  id="latitude"  value=''/>
                                                                            <input type="hidden" name="longitude" id="longitude" value=''/>
                                                                            <input type="hidden" name="country"   id="country"   value=''/>
                                                                            <div id='c_map'></div>
                                                                        </td>
                                                                    </tr>
                                                                    
                                                                    <tr>
                                                                        <td></td>
                                                                        <td class="left welcome_start">
                                                                            <input id="down_button" type="submit" class="button big" value="<?php echo _('Start using AlienVault'); ?>" />
                                                                        </td>
                                                                    </tr>
                                                                    
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </form>

            </div>
            <?php
        }

    }
    ?>

    <div id="forgotpass">
        <span><?php echo _("Password Reset Instructions") ?></span><br><br><br>
        To reset your password please login using ssh to your AlienVault device, when AlienVault CLI is displayed please follow these steps:<br><br>

        <ol>
            <li>Select "System Preferences" and press Enter</li>
            <li>Select "Change Password" and press Enter</li>
            <li>Select "Reset UI Admin Password" and press Enter</li>
            <li>Verify you want to change the password</li>
        </ol>
        <br><br>
        This will generate a temporary password that will allow you login into AlienVault UI. The system will ask you to change the password when you login

        <br><br>
        <center><button type="button" class="big" onclick="$.fancybox.close();"><?php echo _("Ok")?></button></center>
    </div>

</body>
</html>