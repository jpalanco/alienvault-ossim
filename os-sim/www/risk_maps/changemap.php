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
require_once 'riskmaps_functions.php';

if (!Session::menu_perms('dashboard-menu', 'BusinessProcessesEdit'))
{
    echo ossim_error(_("You don't have permissions to change maps"));
    exit();
}

$conf        = $GLOBALS['CONF'];
$version     = $conf->get_conf('ossim_server_version');

$db          = new ossim_db();
$conn        = $db->connect();

$config      = new User_config($conn);
$login       = Session::get_session_user();
$default_map = $config->get($login, "riskmap", 'simple', 'main');

$perms     = array();
$map_names = array();

$query  = "SELECT hex(map) AS map, perm, name FROM risk_maps";
$result = $conn->Execute($query);

while (!$result->EOF)
{
    $perms[$result->fields['map']]     = $result->fields['perm'];
    $map_names[$result->fields['map']] = $result->fields['name'];
    $result->MoveNext();
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title><?php echo  _('Risk Maps - Manage Maps')?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
        <link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>">
        <link href="/ossim/style/jquery.contextMenu.css" rel="stylesheet" type="text/css"/>
        <script type="text/javascript" src="../js/jquery.min.js"></script>
        <script type="text/javascript" src="../js/greybox.js"></script>
        <script type="text/javascript" src="/ossim/js/jquery.contextMenu.js"></script>
        <script type="text/javascript" src="../js/jquery.editinplace.js"></script>
        <script type="text/javascript" src="/ossim/js/notification.js"></script>
        <script type="text/javascript">

            function show_notification(msg, type, fade)
            {
                if(typeof(fade) == 'undefinded')
                {
                    fade = 0;
                }

                var config_nt = {
                        content: msg,
                        options: {
                            type: type,
                            cancel_button: false
                        },
                        style: 'width: 50%; margin: auto; text-align:center;'
                };

                nt = new Notification('nt_2',config_nt);

                $('#notifications').html(nt.show());

                if(fade > 0)
                {
                    nt.fade_in(1000);
                    setTimeout("nt.fade_out(2000);",fade);
                }
            }


            function load_contextmenu()
            {
                $('.menumaps').contextMenu({
                    menu: 'myMapMenu',
                    leftButton:true
                },
                    function(action, el, pos) {
                        var aux = $(el).closest('table').attr('id');

                        if(action == "edit")
                        {
                            var url = 'index.php?map='+aux;

                            if (typeof top.av_menu.get_menu_url == 'function')
                            {
                                url = top.av_menu.get_menu_url(url, 'dashboard', 'riskmaps', 'overview');
                            }

                            document.location.href = url;

                            return false;
                        }
                        else if(action == "default")
                        {
                            set_default_map(aux);

                            return false;
                        }
                        else if(action == "perms")
                        {
                            var url   = "change_user.php?map="+aux;
                            var title = "<?php echo Util::js_entities(_("Map Permissions"))?>";

                            GB_show(title, url, 250, 370);

                            return false;
                        }
                        else if(action == "name")
                        {
                            $(el).closest('table').find('.editInPlace').trigger('click');

                            return false;
                        }
                        else if(action == "delete")
                        {
                            if (confirm('<?php echo  Util::js_entities(_("This map will be removed. This action can not be undone. Are you sure you want to continue?"))?>'))
                            {
                                delete_map(aux);
                            }

                            return false;
                        }
                    }
                );
            }


            function change_map_title(id, title)
            {
                var flag_change = false;

                $.ajax({
                    data:  {action: 3, data: {id: id, title: title}},
                    type: "POST",
                    url: "map_options.php",
                    dataType: "json",
                    async: false,
                    success: function(data){

                        if(data.error)
                        {
                            show_notification('<?php echo _('Error Changing map title') ?>: '+data.msg, 'nf_error', 2500);
                        }
                        else
                        {
                            show_notification(data.msg, 'nf_success', 2000);

                            flag_change = true;
                        }
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown) {
                        show_notification('<?php echo _('There was an error') ?>: '+ textStatus, 'nf_error', 2500);
                    }
                });

                return flag_change;
            }

            function delete_map(id)
            {
                $.ajax({
                    data:  {action: 1, data: id},
                    type: "POST",
                    url: "map_options.php",
                    dataType: "json",
                    success: function(data){
                        if(data.error)
                        {
                            show_notification('<?php echo _('Error deleting map') ?>: '+data.msg, 'nf_error', 2500);
                        }
                        else
                        {
                            show_notification(data.msg, 'nf_success', 2000);

                            $('#cmap_'+id).remove();

                            setTimeout(function(){document.location.reload()}, 500);
                        }
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown) {
                        show_notification('<?php echo _('There was an error') ?>: '+textStatus, 'nf_error', 2500);
                    }
                });
            }


            function set_default_map(id)
            {
                $.ajax({
                    data:  {action: 2, data: id},
                    type: "POST",
                    url: "map_options.php",
                    dataType: "json",
                    success: function(data){
                        if(data.error)
                        {
                            show_notification('<?php echo _('Error changing default map') ?>: '+data.msg, 'nf_error', 2500);
                        }
                        else
                        {
                            show_notification(data.msg, 'nf_success', 1000);

                            setTimeout(function(){document.location.reload()}, 500);
                        }
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown) {
                        show_notification('<?php echo _('There was an error') ?>: '+textStatus, 'nf_error', 2500);
                    }
                });
            }

            //Reloading the page after closing the greybox
            function GB_onhide()
            {
                document.location.reload();
            }

            // GreyBox
            $(document).ready(function(){
                GB_TYPE = 'w';
                $("a.gb_map").click(function(){
                    var t = this.title || $(this).text() || this.href;

                    GB_show(t,this.href, 300, 500);

                    return false;
                });

                load_contextmenu();

                //Edit in place: It chandes directly the title of the tab
                $(".editInPlace").editInPlace({
                    callback: function(unused, enteredText, prevtxt) {
                        var id  = $(this).closest("table").attr('id');

                        if(change_map_title(id, enteredText))
                        {
                            return enteredText;
                        }
                        else
                        {
                            return prevtxt;
                        }
                    },
                    text_size: 14,
                    bg_over: '#ffc'
                });
            });
        </script>

    </head>
    <body>
        <ul id="myMapMenu" class="contextMenu">
            <li class="edit"><a href="#edit"><?php echo _("Edit")?></a></li>
            <li class="toggle"><a href="#default"><?php echo _("Set as default")?></a></li>
            <li class="addAll"><a href="#name"><?php echo _("Name")?></a></li>
            <li class="addEntity"><a href="#perms"><?php echo _("Change Owner")?></a></li>
            <li class="delete"><a href="#delete"><?php echo _("Delete")?></a></li>
        </ul>

        <?php
            //Local menu
            include_once '../local_menu.php';
        ?>

        <!-- NOTIFICATIONS!!! -->
        <div style='margin:10px auto 0 auto;' id='notifications'>
            <?php
            if(isset($_SESSION['map_new']))
            {
                $config_nt = array(
                    'content' => $_SESSION['map_new']['msg'],
                    'options' => array (
                        'type'          => ($_SESSION['map_new']['error']) ? 'nf_error' : 'nf_success',
                        'cancel_button' => TRUE
                    ),
                    'style'   => 'width: 50%; margin: auto; text-align:center;'
                );

                unset($_SESSION['map_new']);

                $nt = new Notification('nt_1', $config_nt);
                $nt->show();
                ?>

                <script type="text/javascript">
                    setTimeout("$('#nt_1').fadeOut(2000);",2000);
                </script>
                <?php
            }
            ?>
        </div>

        <table align="center" style="border:0px; margin-top: 10px">
            <tr>
                <td>
                    <table style="border:0px;margin-top: 20px">
                        <tr>
                        <?php
                        $i       = 0;
                        $n       = 0;
                        $txtmaps = '';

                        $maps = Util::execute_command("ls -tr 'maps' | grep -v CVS", FALSE, 'array');

                        foreach ($maps as $ico)
                        {
                            if(trim($ico) == '' || !getimagesize("maps/" . $ico))
                            {
                                continue;
                            }

                            $n = strtoupper(str_replace('map', '', str_replace('.jpg', '', $ico)));

                            //Checking the permission to see a map: If we have permissions to see it, the map will appear, otherwise it will be skipped.
                            if (strlen($perms[$n]) == 0 || !is_map_allowed($perms[$n]))
                            {
                                continue;
                            }


                            $headerclass = ($n == $default_map) ? " class='headerpr' " : " class='u_headerpr'";

                            //Shorting the name of the map to avoid too big names. In the title will appear the whole name.
                            $map_name  = (strlen($map_names[$n]) > 20) ? substr($map_names[$n], 0, 17).'...' : $map_names[$n];

                            $map_title = $map_names[$n] . (($n == $default_map) ? ' - '. _('DEFAULT MAP') : '');
                            ?>
                            <td>
                                <table id='cmap_<?php echo $n?>'>
                                    <tr>
                                        <td>
                                            <table class="map_header" id='<?php echo $n?>'>
                                                <tr>
                                                    <th style="text-align:left; padding-left:3px;" align="left" <?php echo $headerclass ?>>
                                                        <span title='<?php echo $map_title;?>' <?php echo (can_i_edit_maps($conn,$perms[$n])) ? "class='editInPlace'" : ""; ?>><?php echo $map_name;?></span>
                                                    </th>
                                                    <?php
                                                    //Checking the permission to edit a map: If we have permissions to edit it, an icon will be shown to edit the map.
                                                    if (can_i_edit_maps($conn, $perms[$n]))
                                                    {
                                                        ?>
                                                        <td style="text-align:right" align="right" <?php echo $headerclass?>>
                                                            <a href='javascript:;' title='<?php echo _("Map Options") ?>' class='menumaps'><img src='images/edit.png' height='18px' border=0></a>
                                                        </td>
                                                        <?php
                                                    }
                                                    ?>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <a href='<?php echo Menu::get_menu_url('view.php?map='.$n, 'dashboard', 'riskmaps', 'overview')?>'>
                                                 <img src='maps/<?php echo $ico?>' border='<?php echo (($default_map == $n) ? "1" : "0")?>' width="150" height="150"/>
                                             </a>
                                        </td>
                                    </tr>

                                </table>
                            </td>
                            <?php
                            $i++;
                            if ($i % 5 == 0)
                            {
                                ?>
                                </tr><tr>
                                <?php
                            }
                        }
                        ?>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td align='right'>
                    <div style='padding-bottom:15px;'>
                        <a href='upload_map.php' title='Upload a Map' class='gb_map uppercase'><?php echo _('Upload a new Map')?></a>
                    </div>
                </td>
            </tr>
        </table>
    </body>
</html>
