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

ob_implicit_flush();

require_once 'av_init.php';
require_once 'riskmaps_functions.php';

Session::logcheck('dashboard-menu', 'BusinessProcesses');


$conf     = $GLOBALS['CONF'];
$version  = $conf->get_conf('ossim_server_version');

$can_edit = FALSE;

if (Session::menu_perms('dashboard-menu', 'BusinessProcessesEdit'))
{ 
    $can_edit = TRUE;
}


function check_writable_relative($dir)
{
    $uid = posix_getuid();
    $gid = posix_getgid();

    $user_info = posix_getpwuid($uid);
    $user      = $user_info['name'];

    $group_info = posix_getgrgid($gid);
    $group      = $group_info['name'];

    $fix_cmd = '. '._("To fix that, execute following commands as root").':<br><br>'.
                       "cd " . getcwd() . "<br>".
                       "mkdir -p $dir<br>".
                       "chown $user:$group $dir<br>".
                       "chmod 0700 $dir";

    if (!is_dir($dir))
    {
        $config_nt = array(
            'content' => _("Required directory " . getcwd() . "$dir does not exist").$fix_cmd,
            'options' => array (
                'type'          => 'nf_warning',
                'cancel_button' => FALSE
            ),
            'style'   => 'width: 80%; margin: 20px auto;'
        );

        $nt = new Notification('nt_1', $config_nt);
        $nt->show();

        exit();
    }


    if (!$stat = stat($dir))
    {
        $config_nt = array(
            'content' => _("Could not stat configs dir").$fix_cmd,
            'options' => array (
                'type'          => 'nf_warning',
                'cancel_button' => FALSE
            ),
            'style'   => 'width: 80%; margin: 20px auto;'
        );

        $nt = new Notification('nt_1', $config_nt);
        $nt->show();

        exit();
    }

    // 2 -> file perms (must be 0700)
    // 4 -> uid (must be the apache uid)
    // 5 -> gid (must be the apache gid)
    if ($stat[2] != 16832 || $stat[4] !== $uid || $stat[5] !== $gid)
    {
        $config_nt = array(
            'content' => _("Invalid perms for configs dir").$fix_cmd,
            'options' => array (
                'type'          => 'nf_warning',
                'cancel_button' => FALSE
            ),
            'style'   => 'width: 80%; margin: 20px auto;'
        );

        $nt = new Notification('nt_1', $config_nt);
        $nt->show();

        exit();
    }
}

check_writable_relative("./maps");
check_writable_relative("./pixmaps/uploaded");



$db           = new Ossim_db();
$conn         = $db->connect();

$config       = new User_config($conn);
$login        = Session::get_session_user();

$default_map  = $config->get($login, "riskmap", 'simple', 'main');
$default_map  = ($default_map == '') ? "00000000000000000000000000000001" : $default_map;

$cnd_1 = (!preg_match('/view\.php/', $_SERVER['HTTP_REFERER']));
$cnd_2 = (empty($_GET['back_map']) && empty($_GET['map']));
$cnd_3 = ($_GET['map'] == $default_map || $_GET['back_map'] == $default_map);


if ($cnd_1 || $cnd_2 || $cnd_3)
{
    unset($_SESSION['default_riskmap']);
    unset($_SESSION['path_riskmaps']);
    unset($_SESSION['riskmap']);
}


$_SESSION['default_riskmap']  = $default_map;
$map                          = ($_GET['map'] != '' )      ? $_GET['map']      : $default_map;
$map                          = ($_GET['back_map'] != '' ) ? $_GET['back_map'] : $map;

if (empty($_GET['back_map']))
{
    $_SESSION['path_riskmaps'][$map] = ($_SESSION['riskmap'] == '') ? $_SESSION['default_riskmap'] : $_SESSION['riskmap'];
}

$_SESSION['riskmap'] = $map;


$hide_others = 1;

ossim_valid($map,  OSS_HEX,  'illegal:'._('Map'));

if (ossim_error())
{
    die(ossim_error());
}

$map = get_map($conn, $map);

if(empty($map))
{
    echo ossim_error(_("You do not have any available map."), AV_NOTICE);
    exit();
}


$perms = array();
$query = "SELECT HEX(map) AS map, perm FROM risk_maps";

if ($result = $conn->Execute($query))
{
    while (!$result->EOF)
    {
        $perms[$result->fields['map']][$result->fields['perm']]++;

        $result->MoveNext();
    }
}

$query = "SELECT HEX(map) AS map, perm, name FROM risk_maps";
$result = $conn->Execute($query);

while (!$result->EOF)
{
    $perms[$result->fields['map']] = $result->fields['perm'];

    $result->MoveNext();
}

if (strlen($perms[$map]) > 0 && !is_map_allowed($perms[$map]))
{
    echo ossim_error(_("You don't have permission to see Map $map."), AV_NOTICE);
    exit();
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title><?php echo  _('Risk Maps') ?> - <?php echo _('View')?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>">
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/greybox.js"></script>
    <script type="text/javascript" src="../js/utils.js"></script>
    <script type="text/javascript">

        function refresh_indicators()
        {
            $.ajax({
                type: "GET",
                dataType : 'json',
                url: "get_indicators.php?map=<?php echo $map?>&edit_mode=0",
                error: function(data){

                    //Check expired session
                    var session = new Session(data, '');

                    if (session.check_session_expired() == true)
                    {
                        session.redirect();

                        return;
                    }
                },
                success: function(data){

                    //Check expired session
                    var session = new Session(data, '');

                    if (session.check_session_expired() == true)
                    {
                        session.redirect();

                        return;
                    }

                    var cnd_1  = (typeof(data) == 'undefined' || data == null);
                    var cnd_2  = (typeof(data) != 'undefined' && data != null && data.status != 'success');

                    if (!cnd_1 && !cnd_2)
                    {
                        var indicators = data.data;

                        for (i in indicators)
                        {
                            var indicator = indicators[i];

                            var html_id = (indicator.type == 'indicator') ? 'indicator' : 'rect';
                                html_id = html_id + indicator.id;

                            if ($('#'+html_id).length > 0)
                            {
                                $('#'+html_id).replaceWith(indicator.html);
                                $('#'+html_id).css('visibility', 'visible');
                            }
                        }
                    }
                }
            });
        }


        function initDiv()
        {
            var map_w = $('#map_img').width();

            $('#mapmap').width(map_w)
            $('#map_img').css('visibility','visible');

            var x   = 0;
            var y   = 0;
            var obj = $('#map_img');

            do {
                if ( typeof(obj.offsetLeft) != 'undefined')
                {
                    x += obj.offsetLeft;
                }
                
                if (typeof(obj.offsetTop) != 'undefined')
                {
                    y  += obj.offsetTop;
                }

                obj = obj.offsetParent;
            } while (obj);

            var objs = document.getElementsByTagName("div");
            var txt = ''
            for (var i=0; i < objs.length; i++)
            {
                if (objs[i].className == "itcanbemoved")
                {
                    xx = parseInt(objs[i].style.left.replace('px',''));
                    objs[i].style.left = xx + x
                    yy = parseInt(objs[i].style.top.replace('px',''));
                    objs[i].style.top = yy + y;
                    objs[i].style.visibility = "visible"
                }
            }

            refresh_indicators()
            setInterval(refresh_indicators, 5000);
        }
    </script>

    <style type="text/css">
        body
        {
            width:100%;
            margin:0;
            padding:0;
        }

        a
        {
            cursor: pointer;
        }

        img
        {
            border: none;
        }

        .itcanbemoved
        {
            position:absolute;
        }

        .rb_right
        {
            float: right;
            width: 20px;
            padding-right:2px;
            text-align: right;
        }

        #cont_options
        {
            position:absolute;
            top: 12px;
            right: 0px;
            z-index: 10000;
        }

        .bt_opacity
        {
            filter:alpha(opacity=50);
            -moz-opacity:0.5;
            -khtml-opacity: 0.5;
            opacity: 0.5; 
        }

        #mapmap
        {
            position:relative;
            margin:0 auto 20px auto;
            width:auto;
        }

        #map_img
        {
            visibility: hidden;
        }
        </style>
</head>

<body class='ne1'>
    <?php
        //Local menu
        include_once '../local_menu.php';
    ?>
    <div id='mapmap'>
        <?php
        $ri_indicators = get_indicators_from_map($conn, $map);

        foreach ($ri_indicators as $ri_indicator)
        {
            echo draw_indicator($conn, $ri_indicator);
        }
        ?>
        <img id='map_img' onload='initDiv();' src='maps/map<?php echo Util::htmlentities($map)?>.jpg'/>
    </div>

    <div id='cont_options'>
        <div class='rb_right btn_info'>
            <a href='<?php echo Menu::get_menu_url('view.php?map='.$_SESSION['default_riskmap'], 'dashboard', 'riskmaps', 'overview')?>'>
                <img src='../pixmaps/risk_home.png' alt='<?php echo _('Home')?>' title='<?php echo _("Go to default map")?>'/>
            </a>
        </div>

        <div class='rb_right btn_info'>
            <?php
            if (!empty($_SESSION['path_riskmaps'][$map]) && $_SESSION['path_riskmaps'][$map] != $map && preg_match('/view\.php/', $_SERVER['HTTP_REFERER']))
            {
                ?>
                <a href='<?php echo Menu::get_menu_url('view.php?back_map='.$_SESSION['path_riskmaps'][$map], 'dashboard', 'riskmaps', 'overview')?>'>
                    <img src='../pixmaps/risk_back.png' alt='<?php echo _('Previous')?>' title='<?php echo _('Previous map')?>'/>
                </a>
                <?php
            }
            else
            {
                ?>
                <img src='../pixmaps/risk_back.png' class='bt_opacity' alt='<?php echo _('Previous')?>' title='<?php echo _('Previous map')?>'/>
                <?php
            }
            ?>
        </div>
    </div>

</body>
</html>

<?php
$db->close();