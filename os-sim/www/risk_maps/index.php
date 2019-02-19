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


function check_writable_relative($dir)
{
    $uid         = posix_getuid();
    $gid         = posix_getgid();
    $user_info   = posix_getpwuid($uid);
    $user        = $user_info['name'];
    $group_info  = posix_getgrgid($gid);
    $group       = $group_info['name'];
    $fix_cmd     = '. '._("To fix that, execute following commands as root").':<br><br>'.
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


Session::logcheck("dashboard-menu", "BusinessProcesses");

if (!Session::menu_perms("dashboard-menu", "BusinessProcessesEdit") )
{
    Session::unallowed_section();
    exit();
}

check_writable_relative("./maps");
check_writable_relative("./pixmaps/uploaded");

/*

Requirements:
- web server readable/writable ./maps
- web server readable/writable ./pixmaps/uploaded
- standard icons at pixmaps/standard
- Special icons at docroot/ossim_icons/


TODO: Rewrite code, beutify, use ossim classes for item selection, convert operations into ossim classes

*/

$conf    = $GLOBALS['CONF'];
$version = $conf->get_conf('ossim_server_version');

$db      = new Ossim_db();
$conn    = $db->connect();

$erase_element = GET('delete');
$erase_type    = GET('delete_type');
$map           = get_current_map($conn);
$type          = (GET('type') != '') ? GET('type') : 'host';
$name          = POST('name');

ossim_valid($erase_element, OSS_SCORE, OSS_NULLABLE, OSS_ALPHA, OSS_DIGIT, ";,.", 'illegal:'._('Erase_element'));
ossim_valid($erase_type , "map", "icon", OSS_NULLABLE,                            'illegal:'._('Erase_type'));
ossim_valid($type, OSS_ALPHA, OSS_DIGIT, OSS_SCORE,                               'illegal:'._('Type'));
ossim_valid($name, OSS_ALPHA, OSS_NULLABLE, OSS_DIGIT, OSS_SCORE, ".,%",          'illegal:'._('Name'));
ossim_valid($map, OSS_HEX,                                                        'illegal:'._('Map'));


if (ossim_error())
{
    die(ossim_error());
}


if (!map_exists($map))
{
    if (!empty($map))
    {
        $error_msg = _('Warning! Map no available in the system');

        echo ossim_error($error_msg, AV_WARNING);
    }
    else
    {
        $error_msg = _('There are no maps to edit');

        echo ossim_error($error_msg, AV_INFO);
    }

    exit();
}


$_SESSION['riskmap'] = $map;

// Cleanup a bit
$name          = str_replace('..', '', $name);
$erase_element = str_replace('..', '', $erase_element);

$uploaded_icon   = FALSE;
$allowed_formats = array(IMAGETYPE_JPEG => 1, IMAGETYPE_GIF => 1, IMAGETYPE_PNG => 1);

if (is_uploaded_file($_FILES['fichero']['tmp_name']))
{
    if ($allowed_formats[exif_imagetype ($_FILES['fichero']['tmp_name'])] == 1)
    {
        $size = getimagesize($_FILES['fichero']['tmp_name']);

        if ($size[0] < 400 && $size[1] < 400)
        {
            $uploaded_icon = TRUE;
            $filename      = "pixmaps/uploaded/" . $name . ".jpg";
            move_uploaded_file($_FILES['fichero']['tmp_name'], $filename);
        }
        else
        {
            echo "<span style='color:#FF0000;'>"._("The file uploaded is too big (Max image size 400x400 px).")."</span>";
        }
    }
    else
    {
        echo  "<span style='color:#FF0000;'>"._("The image format should be JPG, GIF or PNG")."</span>";
    }
}


if (is_uploaded_file($_FILES['ficheromap']['tmp_name']))
{
    if ($allowed_formats[exif_imagetype ($_FILES['ficheromap']['tmp_name'])] == 1)
    {
        $filename = "maps/" . $name . ".jpg";

        if (getimagesize($_FILES['ficheromap']['tmp_name']))
        {
            move_uploaded_file($_FILES['ficheromap']['tmp_name'], $filename);
        }
    }
    else
    {
        echo  "<span style='color:#FF0000;'>"._("The image format should be JPG, GIF or PNG")."</span>";
    }
}

if ($erase_element != '')
{
    switch($erase_type)
    {
        case "map":
            if (getimagesize("maps/" . $erase_element))
            {
                unlink("maps/" . $erase_element);
            }
        break;

        case "icon":
            if (getimagesize("pixmaps/uploaded/" . $erase_element))
            {
                unlink("pixmaps/uploaded/" . $erase_element);
            }

        break;
    }
}


$query  = "SELECT hex(map) AS map, perm, name FROM risk_maps";
$result = $conn->Execute($query);

while (!$result->EOF)
{
    $perms[$result->fields['map']] = $result->fields['perm'];

    $result->MoveNext();
}

if (strlen($perms[$map]) > 0 && !can_i_edit_maps($conn, $perms[$map]))
{
    echo ossim_error(_("You don't have permission to edit Map $map."), AV_NOTICE);
    exit();
}


//Autocomplete - JQuery

$autocomplete_keys = array('hosts', 'nets', 'host_groups', 'net_groups', 'sensors');
$assets            = Autocomplete::get_autocomplete($conn, $autocomplete_keys);

?>

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title><?php echo  _("Risk Maps") ?>  - <?php echo  _("Edit") ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>">
    <link rel="stylesheet" type="text/css" href="../style/tree.css" />
    <link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css"/>
    <link rel="stylesheet" type="text/css" href="/ossim/style/tipTip.css"/>
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/greybox.js"></script>
    <script type="text/javascript" src="../js/jquery-ui.min.js"></script>
    <script type="text/javascript" src="../js/jquery.cookie.js"></script>
    <script type="text/javascript" src="../js/jquery.dynatree.js"></script>
    <script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>
    <script type="text/javascript" src="/ossim/js/notification.js"></script>
    <script type="text/javascript" src="/ossim/js/jquery.tipTip.js"></script>
    <script type="text/javascript" src="/ossim/js/utils.js"></script>
    <script type="text/javascript" src="/ossim/js/purl.js"></script>

    <script type='text/javascript'>

        var dobj;
        var moz        = document.getElementById && !document.all;
        var moving     = false;
        var resizing   = false;
        var ind_sel    = '';
        var background = '';
        var changed    = 0;


        function GB_onhide(url, params)
        {
            if(typeof params['icon'] != 'undefined' && params['icon'] != '')
            {
                change_indicator_tab(params['icon']);
            }
        }


        function show_notification(msg, type, fade)
        {
            if (typeof(fade) == 'undefinded')
            {
                fade = 0;
            }

            var config_nt = {
                    content: msg,
                    options: {
                        type: type,
                        cancel_button: true
                    },
                    style: 'width: 90%; margin: auto; text-align:center; position: relative; top: -5px;'
            };

            nt = new Notification('nt_1',config_nt);

            $('#changes_made').html('<div style="position: absolute; width: 100%;">' + nt.show() + '</div>');
            $('#nt_1 > div').css('padding', '4px 12px 2px 18px');

            if (fade > 0)
            {
                nt.fade_in(1000);
                setTimeout("nt.fade_out(1000);",fade);
            }
        }


        function changes_made(flag, force)
        {
            if (typeof(force) == 'undefined')
            {
                force = false;
            }
            if (ind_sel != '' || force)
            {
                if (flag)
                {
                    var msg  = "<?php echo _("You have made changes, click <a href='javascript:;' onclick='save(\\\"" . $map . "\\\");'>here</a> to save changes") ?>";
                    var type = 'nf_warning';

                    show_notification(msg, type, 0);
                    changed = 1;
                }
                else
                {
                    $('#nt_1').fadeOut(500);
                    changed = 0;
                }
            }
        }


        function loadLytebox()
        {
            var cat = document.getElementById('category').value;
            var id = cat + "-0";

            myLytebox.start(document.getElementById(id));
        }


        function toggleLayer(whichLayer)
        {
            var elem, vis;

            if (document.getElementById) // this is the way the standards work
            {
                elem = document.getElementById(whichLayer);
            }
            else if (document.all) // this is the way old msie versions work
            {
                elem = document.all[whichLayer];
            }
            else if (document.layers) // this is the way nn4 works
            {
                elem = document.layers[whichLayer];
            }

            vis = elem.style;
            // if the style.display value is blank we try to figure it out here
            if (vis.display == '' && elem.offsetWidth != undefined && elem.offsetHeight != undefined)
            {
                vis.display = (elem.offsetWidth !=0 && elem.offsetHeight != 0) ? 'block' :'none';
            }

            vis.display = (vis.display == ''|| vis.display == 'block') ? 'none' : 'block';
        }


        function findPos(obj)
        {
            var curleft = curtop = 0;
            if (obj.offsetParent)
            {
                do {
                    curleft += obj.offsetLeft;
                    curtop += obj.offsetTop;
                } while (obj = obj.offsetParent);

                return [curleft,curtop];
            }
        }


        function dragging(e)
        {
            sx = (typeof(window.scrollX) != 'undefined') ? window.scrollX : ((typeof(document.body.scrollLeft) != 'undefined') ? document.body.scrollLeft : 0);
            sy = (typeof(window.scrollY) != 'undefined') ? window.scrollY : ((typeof(document.body.scrollTop) != 'undefined') ? document.body.scrollTop : 0);

            var map_offset = $('#mapmap').offset();
            var map_width  = $('#mapmap').width()  - 15;
            var map_height = $('#mapmap').height() - 70;

            if (moving)
            {
                x  = moz ? e.clientX : event.clientX;
                y  = moz ? e.clientY : event.clientY;

                document.f.posx.value = x + sx;
                document.f.posy.value = y + sy;

                //Indicator width and height
                var ind_w = $(dobj).outerWidth(true);
                var ind_h = $(dobj).outerHeight(true);

                var pos_x = x + sx - map_offset.left - ind_w/2;
                var pos_y = y + sy - map_offset.top  - ind_h/2;


                //Indicator position
                if (pos_x < 0)
                {
                    pos_x = 0;
                }
                else if (pos_x > (map_width - ind_w))
                {
                    pos_x = map_width - ind_w;
                }

                if (pos_y < map_offset.top)
                {
                    pos_y = map_offset.top;
                }
                else if (pos_y > (map_height - ind_h))
                {
                    pos_y = map_height - ind_h;
                }

                $(dobj).css('left', pos_x);
                $(dobj).css('top',  pos_y);

                changes_made(true, true);

                return false;
            }

            if (resizing)
            {
                x = moz ? e.clientX+10+ sx : event.clientX+10+ sx;
                y = moz ? e.clientY+10+ sy : event.clientY+10+ sy;

                document.f.posx.value = x + sx;
                document.f.posy.value = y + sy;

                xx = $(dobj).position().left + 5;
                yy = $(dobj).position().top + 5;

                w = (x > xx) ? x-xx : xx
                h = (y > yy) ? y-yy : yy

                $(dobj).width(w - map_offset.left);
                $(dobj).height(h - map_offset.top);

                changes_made(true, true);

                return false;
            }
        }


        function releasing(e)
        {
            moving = false;
            resizing = false;

            if (dobj != undefined)
            {
                dobj.style.cursor = 'pointer';
            }
        }


        function reset_values()
        {
            if (ind_sel != '')
            {
                if (!$(ind_sel).closest('div').attr('id').match(/rect/))
                {
                    border = "1px solid #BBBBBB";
                }
                else
                {
                    border = "none";
                }
                $(ind_sel).css({"border": border, "background-color":background});
            }

            ind_sel    = ''
            background = 'transparent';

            $('#link_option_asset').trigger('click');
            $('#save_button').hide();

            $('#check_noname').attr('checked', false);

            if ($('#check_noicon').is(':checked'))
            {
                $('#check_noicon').trigger('click');
            }


            $('.map_list').css({"border":"none"});

            // Reset form values
            document.f.url.value        = "";
            document.f.alarm_id.value   = "";
            document.f.alarm_name.value = "";
            document.f.type.value       = "";

            document.getElementById('check_report').checked = false;
            document.getElementById('elem').value = "";
            document.getElementById('selected_msg').innerHTML = "";
            document.getElementById('chosen_icon').src = "pixmaps/standard/default.png";
            document.getElementById('iconsize').value = "0";
            document.getElementById('iconbg').value = "";
            document.getElementById('linktoreport').style.display = 'none';
        }


        function pushing(e)
        {
            var fobj = moz ? e.target : event.srcElement;

            if (typeof fobj.tagName == 'undefined')
            {
                return false;
            }

            while (fobj.tagName.toLowerCase() != "html" && fobj.className != "itcanbemoved" && fobj.className != "itcanberesized")
            {
                fobj = moz ? fobj.parentNode : fobj.parentElement;
            }

            if (fobj.className == "itcanberesized")
            {
                resizing = true;
                fobj = moz ? fobj.parentNode : fobj.parentElement;
                dobj = fobj

                return false;
            }
            else if (fobj.className == "itcanbemoved")
            {
                //fobj.style.border = "1px dotted red";
                moving = true;
                fobj.style.cursor = 'move';
                dobj = fobj

                return false;
            }
        }


        function delete_indicator(ui)
        {
            if (confirm('<?php echo _('You are going to delete an indicator. This action cannot be undone. Are you sure?') ?>'))
            {
                var fobj = $(ui).parents('div.itcanbemoved');

                $(fobj).css('visibility', 'hidden');

                reset_values();
                save('<?php echo $map ?>');
            }

            return false;
        }


        function load_indicator_info(ui)
        {
            var fobj = $(ui).parents('div.itcanbemoved');

            if (ind_sel != '')
            {
                if (!$(ind_sel).closest('div').attr('id').match(/rect/))
                {
                    border = "1px solid #BBBBBB";
                }
                else
                {
                    border = "none";
                }

                $(ind_sel).css({"border": border, "background-color":background});
            }

            ind_sel    = ''
            background = 'transparent';

            var ida  = $(fobj).attr('id').replace('indicator', '').replace('rect', '');

            if (document.getElementById('dataname'+ida))
            {
                document.getElementById('check_noname').checked = 0;

                if (document.getElementById('dataname'+ida).value.match(/#NONAME/))
                {
                    document.getElementById('check_noname').checked = 1;
                }


                if (document.getElementById('dataurl'+ida).value == "REPORT")
                {
                    //Link to Asset
                    document.getElementById('check_report').checked = 1;
                }
                else
                {
                    //Link to Map
                    var url = document.getElementById('dataurl'+ida).value;
                        url = $.url(url);

                    var id  = (typeof(url.param('map')) != 'undefined') ? url.param('map') : ''

                    if (id != '')
                    {
                        $('.map_list').css({"border":"none"});

                        $('#'+id).css({"border":"1px dashed #008D15"});
                    }

                    //document.getElementById('linktomapmaps').style.display = '';

                    document.getElementById('check_report').checked = 0;
                }

                document.f.url.value = document.getElementById('dataurl'+ida).value
                document.f.alarm_id.value = ida

                //It's an indicator
                if (!$(fobj).attr('id').match(/rect/))
                {
                    document.f.alarm_name.value = document.getElementById('dataname'+ida).value.replace(/#NONAME/,'');
                    document.f.type.value       = document.getElementById('datatype'+ida).value

                    var id_type = 'elem_'+document.getElementById('datatype'+ida).value

                    document.getElementById('elem').value      = document.getElementById('type_name'+ida).value

                    document.getElementById('elem_show').value = document.getElementById('type_name_show'+ida).value

                    if (document.getElementById('dataicon' + ida) != null)
                    {
                        document.getElementById('chosen_icon').src = document.getElementById('dataicon'+ida).value
                    }

                    if (document.getElementById('dataiconsize' + ida) != null)
                    {
                        var icon_size = $('#dataiconsize'+ida).val();

                        if (icon_size == -1)
                        {
                            $('#iconsize').val('0');

                            $('#check_noicon').trigger('click');
                        }
                        else
                        {
                            if ($('#check_noicon').is(':checked'))
                            {
                                $('#check_noicon').trigger('click');
                            }

                            icon_size = $('#dataiconsize'+ida).val();

                            if ($("#iconsize option[value="+icon_size+"]").length == 0)
                            {
                                icon_size = 0;
                            }

                            $('#iconsize').val(icon_size);
                        }
                    }

                    if (document.getElementById('dataiconbg' + ida) != null)
                    {
                        var bg = document.getElementById('dataiconbg'+ida).value;
                            bg = (bg == 'transparent') ? '' : bg;

                        document.getElementById('iconbg').value = bg;
                    }

                    var type = 'indicator';
                }
                else
                {
                    $("#link_option_rect").trigger('click');
                    document.f.alarm_name.value = '';
                    var type = 'rectangle';
                }

                change_select(type);

                $('#new_button').hide();
                $('#rect_button').hide();
                $('#save_button').show();
            }

            ind_sel    = $(fobj).find('table:first');
            background = $(ind_sel).css("background-color");

            $(ind_sel).css({"border":"1px dashed #008D15", "background-color":"rgba(140,198,63,0.5)"});
        }


        document.onmousedown = pushing;
        document.onmouseup   = releasing;
        document.onmousemove = dragging;


        function urlencode(str)
        {
            return escape(str).replace('+','%2B').replace('%20','+').replace('*','%2A').replace('/','%2F').replace('@','%40');
        }


        function drawDiv (id, name, valor, icon, url, x, y, w, h, type, type_name, size, name_show)
        {
            if (icon.match(/\#/))
            {
                var aux = icon.split(/\#/);
                var iconbg = aux[1];
                icon = aux[0];
            }
            else
            {
                var iconbg = "transparent";
            }

            var el            = document.createElement('div');
            var the_map       = document.getElementById("map_img")
            el.id             = 'indicator'+id
            el.className      = 'itcanbemoved'
            el.style.position = 'absolute';
            el.style.left     = x;
            el.style.top      = y
            el.style.width    = w
            el.style.height   = h
            el.innerHTML        = "<img src='../pixmaps/loading.gif'>";
            el.style.visibility = 'visible';

            $('#mapmap').append(el);

            document.getElementById('indnuevo').innerHTML += '<input type="hidden" name="dataname' + id + '" id="dataname' + id + '" value="' + name + '">\n';
            document.getElementById('indnuevo').innerHTML += '<input type="hidden" name="datatype' + id + '" id="datatype' + id + '" value="' + type + '">\n';
            document.getElementById('indnuevo').innerHTML += '<input type="hidden" name="type_name' + id + '" id="type_name' + id + '" value="' + type_name + '">\n';
            document.getElementById('indnuevo').innerHTML += '<input type="hidden" name="type_name_show' + id + '" id="type_name_show' + id + '" value="' + name_show + '">\n';
            document.getElementById('indnuevo').innerHTML += '<input type="hidden" name="dataurl' + id + '" id="dataurl' + id + '" value="' + url + '">\n';
            document.getElementById('indnuevo').innerHTML += '<input type="hidden" name="dataicon' + id + '" id="dataicon' + id + '" value="' + icon + '">\n';
            document.getElementById('indnuevo').innerHTML += '<input type="hidden" name="dataiconsize' + id + '" id="dataiconsize' + id + '" value="' + size + '">\n';
            document.getElementById('indnuevo').innerHTML += '<input type="hidden" name="dataiconbg' + id + '" id="dataiconbg' + id + '" value="' + iconbg + '">\n';
        }


        function initDiv()
        {
            //Initialize variables
            reset_values()

            var x   = 0;
            var y   = 0;
            var el  = $('#map_img');
            var obj = el;

            do
            {
                x += obj.offsetLeft;
                y += obj.offsetTop;
                obj = obj.offsetParent;

            } while (obj);

            var objs = document.getElementsByTagName("div");
            var txt  = '';

            for (var i=0; i < objs.length; i++)
            {
                if (objs[i].className == "itcanbemoved")
                {
                    xx = parseInt(objs[i].style.left.replace('px',''));
                    yy = parseInt(objs[i].style.top.replace('px',''));

                    objs[i].style.left       = xx + x;
                    objs[i].style.top        = yy + y;
                    objs[i].style.visibility = "visible"
                }
            }

            refresh_indicators();
        }

        var layer    = null;
        var nodetree = null;
        var suf      = "c";
        var i        = 1;


        function set_asset (asset_type, asset_id, old_asset_name, asset_name)
        {
            var style = 'text-align: left; background-color:#E5E5E5; padding:2px; border:1px dotted #cccccc; font-size:11px; width: 92%; margin:0px auto;';
            var asset_text  = asset_type + " - " + asset_name;

            if (asset_text.length > 45)
            {
                asset_text  = "<div style='padding-left: 10px;'>"+ asset_text.substring(0, 42) + "...</div>";
            }

            $('#selected_msg').html("<div style='"+style+"'><strong><?php echo _("Selected type")?></strong>: "+ asset_text+"</div>");
            $('#type').val(asset_type);
            $('#elem').val(asset_id);
            $('#elem_show').val(asset_name);

            //Change exclamation icon by default icon
            if (old_asset_name == 'Unknown' && old_asset_name != asset_name)
            {
                $('#chosen_icon').attr('src', 'pixmaps/standard/default.png');
            }
        }


        function enable_link_report()
        {
            var asset_type = $('#type').val();

            if (typeof(asset_type) == 'undefined' || asset_type == '')
            {
                $('#linktoreport').css('display', 'none');
            }
            else
            {
                $('#linktoreport').css('display', '');
            }
        }


        function load_tree()
        {
            if (nodetree!=null)
            {
                nodetree.removeChildren();
                $(layer).remove();
            }

            layer = '#srctree'+i;
            $('#tree').append('<div id="srctree'+i+'" class="tree_container"></div>');
            $(layer).dynatree({
                initAjax: { url: "../tree.php?key=assets|sensors"},
                clickFolderMode: 2,
                onActivate: function(dtnode) {
                    if (dtnode.data.key.indexOf('_') != -1)
                    {
                        dtnode.deactivate();
                        var keys = dtnode.data.key.split(/\_/);
                        var type = keys[0];
                        var id   = keys[1].replace(/;.*/,"");

                        set_asset(type, id, $('#elem_show').val(), dtnode.data.val);

                        if (keys[0] != '' && keys[0] != 'netgroup' && keys[0] != 'net_group')
                        {
                            document.getElementById('check_report').checked = true;
                        }
                        else
                        {
                            document.getElementById('check_report').checked = false;
                        }

                        enable_link_report();

                        changes_made(true);
                    }
                    else
                    {
                        dtnode.toggleExpand();
                    }

                },
                onDeactivate: function(dtnode) {},
                onLazyRead: function(dtnode){
                    dtnode.appendAjax({
                        url: "../tree.php?key=assets|sensors",
                        data: {key: dtnode.data.key, page: dtnode.data.page}
                    });
                }
            });

            nodetree = $(layer).dynatree("getRoot");

            i = i + 1
        }


        function add_new(map, type)
        {
            if (!check_form_data())
            {
                return false;
            }

            if (changed)
            {
                var keys = {"yes": "<?php echo _('Yes') ?>","no": "<?php echo _('No') ?>"};
                var msg  = "<?php echo Util::js_entities(_('You have made changes. If you continue, you will lose these changes. Would you like to continue?'))?>";

                av_confirm(msg, keys).done(function()
                {
                    save_new_indicator(map, type)
                });
            }
            else
            {
                save_new_indicator(map, type)
            }

        }


        function save_new_indicator(map, type)
        {

            $('#alarm_id').val('');

            if (type == 'alarm')
            {
                var asset_type  = $("#type").val();
                var asset_name  = $("#elem").serialize();
                var chosen_icon = $("#chosen_icon").attr("src");
                    chosen_icon = chosen_icon.replace(/.*\/ossim\/risk\_maps\//,"");
                    chosen_icon = chosen_icon.replace(/\//g,"url_slash");
                    chosen_icon = chosen_icon.replace(/\%3F/g,"url_quest");
                    chosen_icon = chosen_icon.replace(/\%3D/g,"url_equal");

                var alarm_name  = $("#alarm_name").serialize();
                var iconbg      = $("#iconbg").val();

                var iconsize;

                if ($('#check_noicon').is(':checked'))
                {
                    iconsize = -1;
                }
                else
                {
                    iconsize = $("#iconsize").val();
                }

                var name_show =  $("#elem_show").val();

                if (document.getElementById('check_report').checked == true)
                {
                    var url_data = "url=REPORT";
                }
                else
                {
                    var url_data = $("#url").serialize();
                }

                var name_aux = "&noname=";

                if (document.getElementById('check_noname').checked == true)
                {
                   name_aux = name_aux + "NONAME";
                }

                var url         = "responder.php?nolinks=1&map=" + map + "&type="+type+"&chosen_icon="+urlencode(chosen_icon)+"&asset_type="+asset_type+"&"+asset_name+"&"+alarm_name+"&"+url_data+"&iconbg="+iconbg+"&iconsize="+iconsize+name_aux+"&name_show="+urlencode(name_show);

                $.ajax({
                   type: "GET",
                   url : url,
                   success: function(msg){
                        var status = msg.split("###");
                        if (status[0] == "OK")
                        {
                            eval(status[1]);
                            show_notification("<?php echo _("New Indicator created") ?>", 'nf_success', 1500);
                            refresh_indicators();
                            reset_values();
                        }
                        else
                        {
                            msg = '<?php echo  _("Error: New Indicator has not been created") ?>';
                            show_notification(msg, 'nf_error', 1500);
                        }
                   }
                });
            }
            else
            {
                var type      = "rect";
                var url_data  =  $("#url").serialize();
                var url       = "responder.php?nolinks=1&map=" + map + "&type="+type+"&"+url_data;

                $.ajax({
                   type: "GET",
                   url: url,
                   success: function(msg){
                        var status = msg.split("###");
                        if (status[0] == "OK")
                        {
                            eval(status[1]);
                            msg = '<?php echo  _("New Rectangle created") ?>';
                            show_notification(msg, 'nf_success', 1500);
                            refresh_indicators();
                        }
                        else
                        {
                            msg = '<?php echo  _("Error: New Indicator has not been created") ?>';
                            show_notification(msg, 'nf_error', 1500);
                        }
                   }
                });
            }

            changes_made(true);
        }


        function drawRect (id,url,x,y,w,h)
        {
            var el            = document.createElement('div');
            el.id             = 'rect'+id
            el.className      = 'itcanbemoved'
            el.style.position = 'absolute';
            el.style.left     = x;
            el.style.top      = y
            el.style.width    = w
            el.style.height   = h
            el.innerHTML      = "<div style='position:absolute;bottom:0px;right:0px'><img src='../pixmaps/resize.gif' border='0'></div>";
            el.style.visibility = 'visible'

            $('#mapmap').append(el);

            document.getElementById('indnuevo').innerHTML += '<input type="hidden" name="dataname' + id + '" id="dataname' + id + '" value="rect">\n';
            document.getElementById('indnuevo').innerHTML += '<input type="hidden" name="dataurl' + id + '" id="dataurl' + id + '" value="' + url + '">\n';

            changes_made(true);
        }


        function check_form_data()
        {
            if ($('#link_option_asset').prop('checked'))
            {
                if (document.f.alarm_name.value == '')
                {
                    alert("<?php echo  Util::js_entities(_('Indicator name cannot be empty'))?>");

                    return false;
                }

                if (document.f.type.value == '')
                {
                    alert("<?php echo  Util::js_entities(_('Select an asset at least'))?>");

                    return false;
                }
            }
            else
            {
                if ($("#link_option_ind").prop('checked') && document.f.alarm_name.value == '')
                {
                    alert("<?php echo  Util::js_entities(_('Indicator name cannot be empty'))?>");

                    return false;
                }

                if ($('#url').val() == '')
                {
                    alert("<?php echo  Util::js_entities(_('Select a map at least'))?>");

                    return false;
                }
            }

            return true;
        }


        function save(map, check_data)
        {
            if (ind_sel != '' && !check_form_data())
            {
                return false;
            }

            var el  = $('#map_img');
            var x   = el.offset().left;
            var y   = el.offset().top;

            var txt = '';

            $("#mapmap div.itcanbemoved").each(function(i, v)
            {
                if ($(v).css('visibility') != "hidden")
                {
                    var v_offset = $(v).offset();

                    xx  = v_offset.left;
                    yy  = v_offset.top;
                    txt = txt + $(v).attr('id') + ',' + (xx - x) + ',' + (yy - y) + ',' + $(v).css('width') + ',' + $(v).css('height') + ';';
                }
            });

            var url_aux  = document.f.url.value;

            if (document.getElementById('check_report').checked == true)
            {
                $('#url').val("REPORT");
                url_aux = "REPORT";
            }

            var url_show = url_aux;

            var name_aux="&noname=";

            if (document.getElementById('check_noname').checked == true)
            {
                name_aux = name_aux + "NONAME";
            }

            var icon_show = document.getElementById("chosen_icon").src;

            var icon_aux  = urlencode(icon_show);
                icon_aux  = icon_show.replace(/\//g,"url_slash");
                icon_aux  = icon_aux.replace(/\%3F/g,"url_quest");
                icon_aux  = icon_aux.replace(/\%3D/g,"url_equal");

            url_aux = urlencode(url_aux);
            url_aux = url_aux.replace(/\//g,"url_slash");
            url_aux = url_aux.replace(/\%3F/g,"url_quest");
            url_aux = url_aux.replace(/\%3D/g,"url_equal");


            var type        = $("#type").serialize()
            var type_name   = $("#elem").serialize()
            var id          = document.f.alarm_id.value
            var alarm_name  = $("#alarm_name").serialize();
            var iconbg      = document.f.iconbg.value;

            var iconsize;

            if ($('#check_noicon').is(':checked'))
            {
                iconsize = -1;
            }
            else
            {
                iconsize = $("#iconsize").val();
            }

            urlsave = 'save.php?'+ type +'&'+ type_name +'&map=' + map + '&id=' + id + '&' + alarm_name + '&url=' + url_aux + '&icon=' + icon_aux + '&data=' + txt + '&iconbg=' + iconbg + '&iconsize=' + iconsize + name_aux;

            $.ajax({
                type: "GET",
                url: urlsave,
                dataType: 'json',
                error: function(data){

                    //Check expired session
                    var session = new Session(data, '');

                    if (session.check_session_expired() == true)
                    {
                        session.redirect();

                        return;
                    }

                    show_notification("<?php echo _("There was an error saving your configuration")?>", 'nf_error', 1500);

                    reset_values();
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

                    if (cnd_1 || cnd_2)
                    {
                        show_notification("<?php echo _("There was an error saving your configuration")?>", 'nf_error', 1500);
                    }
                    else
                    {
                        changes_made(false, true);

                        show_notification("<?php echo _("Configuration saved") ?>", 'nf_success', 1500)

                        eval(data.data);

                        // Indicator has not been removed

                        if (id != '')
                        {
                            var name = $("#alarm_name").val();

                            if (document.getElementById('check_noname').checked == true)
                            {
                                name += '#NONAME'
                            }

                            $('#dataname'+id).val(name);
                            $('#datatype'+id).val($("#type").val());
                            $('#type_name'+id).val($("#elem").val());
                            $('#type_name_show'+id).val($("#elem_show").val());
                            $('#dataurl'+id).val(url_show);
                            $('#dataicon'+id).val(icon_show);
                            $('#dataiconsize'+id).val(iconsize);
                            $('#dataiconbg'+id).val(iconbg);

                            if (url_show == 'REPORT')
                            {
                                $('#check_report').attr('checked', true);
                            }
                        }
                    }

                    reset_values();
                }
            });
        }


        function refresh_indicators()
        {
            $.ajax({
                type: "GET",
                dataType : 'json',
                url: "get_indicators.php?map=<?php echo $map?>&edit_mode=1",
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
                            }
                            else
                            {
                                //Indicator type has changed

                                html_id = (indicator.type == 'indicator') ? 'rect' : 'indicator';
                                html_id = html_id + indicator.id;

                                //Remove old one
                                $('#'+html_id).remove();

                                //Add new one
                                $('#mapmap').append(indicator.html);
                                html_id = (indicator.type == 'indicator') ? 'indicator' : 'rect';
                                html_id = html_id + indicator.id;
                            }

                            $('#'+html_id).css('visibility', 'visible');
                        }

                        $('.ind_help').tipTip();
                    }
                }
            });
        }


        function chk(fo)
        {
            if  (fo.name.value == '')
            {
                alert("<?php echo Util::js_entities(_("Icon requires a name!"))?>");

                return false;
            }

            return true;
        }


        function change_select(type)
        {
            if (type == 'indicator')
            {
                if (document.f.url.value.match(/view\.php/))
                {
                    document.getElementById('link_option_map').checked = true;
                    show_maplink();
                }
                else
                {
                    if (document.f.type.value != '')
                    {
                        set_asset(document.f.type.value, document.f.elem.value, document.f.elem_show.value, document.f.elem_show.value);
                    }

                    enable_link_report();

                    document.getElementById('link_option_asset').checked = true;
                    show_assetlink();
                }
            }
            else
            {
                document.getElementById('link_option_map').checked = true;

                show_maplink();
            }
        }


        function show_maplink(type)
        {
            $('#link_map').show();
            $('#link_asset').hide();
            document.getElementById('link_asset').style.display = "none";

            if (!document.f.url.value.match(/view\.php/))
            {
                document.f.url.value = '';
            }

            document.getElementById('check_report').checked = false;

            document.f.type.value = '';
            document.f.elem.value = '';

            $('#selected_msg').html('');
        }


        function show_assetlink()
        {
            $('#link_map').hide();
            $('#link_asset').show();

            if ( $('#selected_msg').html() != '')
            {
                $('#selected_msg').css('display', '');
            }

            $("#link_option_ind").trigger('click');

            $('.map_list').css({'border' : 'none'});
            document.f.url.value = '';
        }


        function indicator_type(flag)
        {
            if (flag)
            {
                $('#indicator_layout').show();
                $('#indicator_name').show();

                if (ind_sel == '')
                {
                    $('#new_button').show();
                    $('#rect_button').hide();
                }
            }
            else
            {
                //Remove indicator risk name
                document.f.alarm_name.value = '';

                $('#indicator_layout').hide();
                $('#indicator_name').hide();

                if (ind_sel == '')
                {
                    $('#new_button').hide();
                    $('#rect_button').show();
                }
            }

            changes_made(true);
        }


        function icon_settings()
        {
            if ($('#check_noicon').is(':checked'))
            {
                $('.icon_settings').hide();
            }
            else
            {
                $('.icon_settings').show();
            }

            changes_made(true);
        }


        function select_map(id)
        {
            document.f.url.value = 'view.php?map='+id;

            $('.map_list').css({"border":"none"});
            $('#'+id).css({"border":"1px dashed #008D15"});

            changes_made(true);
        }


        function change_indicator_tab(icon)
        {
            $('#chosen_icon').attr("src", icon);

            changes_made(true);
        }


        $(document).ready(function()
        {
            // Tree
            load_tree("");

            $('.itcanbemoved').live('dblclick', function(){
                var item = $(this).find('div');
                load_indicator_info(item);
            });


            $("a.iconbox").click(function(){
               var t   = this.title;
               var url = this.href;
               GB_show(t, url, 500, 600);

               return false;
            });

            // Autocomplete - JQuery
            var assets = [ <?php echo $assets; ?> ];

            $("#assets").autocomplete(assets, {
                minChars: 0,
                width: 350,
                matchContains: "word",
                multiple: false,
                autoFill: false,
                formatItem: function(row, i, max) {
                    return row.txt;
                }
            }).result(function(event, item) {

                var _type = item.type;
                var _id   = item.id;
                var _name = item.name;

                set_asset(_type, _id, $('#elem_show').val(), _name);

                enable_link_report();

                $('#assets').val('');
            });

            $('.ind_help').tipTip();

            //Greybox

            $("a.greybox").click(function(){
               var t = this.title || $(this).text() || this.href;
               var url = this.href + "?dir=" + document.getElementById('category').value;

               GB_show(t,url,420,"50%");

               return false;
            });

            $("a.greybox2").click(function(){

               var t = this.title || $(this).text() || this.href;
               var url = this.href + "&dir=" + document.getElementById('category').value;

               GB_show(t,url,200,200);

               return false;
            });
        });
    </script>
</head>

<body class='ne1' oncontextmenu="return true;">

<?php
require '../host_report_menu.php';
?>

<table class='noborder' border='0' cellpadding='0' cellspacing='0'>
    <?php
        $maps = Util::execute_command("ls -1 'maps'", FALSE, 'array');
        $i = 0;
        $n = 0;
        $linkmaps = '';

        foreach ($maps as $ico)
        {
            $ico = mb_convert_encoding($ico, 'UTF-8', 'ISO-8859-1');

            if (trim($ico) == '' || is_dir("maps/" . $ico) || !getimagesize("maps/" . $ico))
            {
                continue;
            }


            $n = str_replace("map", '', str_replace(".jpg", '', $ico));

            //Getting the permissions and the name of the map to show as tittle
            $query  = "SELECT name, perm FROM risk_maps WHERE map = ?";
            $result = $conn->Execute($query, array($n));

            $map_name = $ico;
            $map_perm = array();

            if (!$result->EOF)
            {
                $map_name   = ($result->fields['name'] == '') ? $ico : $result->fields['name'];
                $map_perm[] = ($result->fields['perm'] == '') ? ''   : $result->fields['perm'];$result->fields['perm'];
            }
            /*
            Showing the map icon for the 'Link to map' option just in case whe have permission:
                Either the SQL query is empty, this mean that the map in the directory and not in the sql, so it's a default map,
                or we have permission after checking it out in the mapAllowed function.
            */
            if (count($map_perm) == 0 || is_map_allowed($map_perm))
            {
                $linkmaps .= "<td class='noborder'><a href='javascript:;' onclick='select_map(\"$n\");'>
                                  <img src='maps/$ico' id='$n' class='map_list' border='0' width='45' height='45'  alt='$ico' title='$map_name'></a>
                              </td>";

                $i++;

                if ($i % 4 == 0)
                {
                    $linkmaps .= "</tr><tr>";
                }
            }
        }
    ?>

    <tr>
        <td valign='top' class='ne1 new_indicator_cell' nowrap='nowrap'>
        <form name="f" action="modify.php">
            <div id='changes_made' style='position: relative; margin:15px auto; white-space:normal;'></div>

            <table id="new_indicator" cellpadding="0" cellspacing="0">
                <tr>
                    <th class='headerpr'><?php echo _("New Indicator")?></th>
                </tr>
                <tr>
                    <td class='ne1'>
                        <table class="noborder" style='width:100%;text-align:center;'>
                            <tr>
                                <td>
                                    <table id='indicator_name' class='container'>
                                        <tr>
                                            <td style="font-size:12px;white-space:nowrap;"><?php echo  _("Name"); ?></td>
                                            <td class='left'><input type='text' size='30' name="alarm_name" id='alarm_name' class='ne1' onchange='changes_made(true);'/></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <tr class='t_indicator'><td style='text-align:center;' id="selected_msg"></td></tr>

                            <tr>
                                <td>
                                    <table class='container'>
                                        <tr>
                                            <td class='ne1 left'><input type="radio" onclick="show_assetlink()" name="link_option" id="link_option_asset" value="asset" checked='checked' /><?php echo _("Link to Asset") ?></td>
                                            <td class='ne1 left'><input type="radio" onclick="show_maplink()"   name="link_option" id="link_option_map"   value="map" /><?php echo _("Link to Map") ?></td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <table class='container' id="link_asset">
                                        <tr>
                                            <td class='nobborder center'>
                                                <table class='container'>
                                                    <tr>
                                                        <td style="font-size:12px;white-space:nowrap;"><?php echo  _("Search"); ?></td>
                                                        <td class='left'><input type='text' size='30' name="assets" id='assets' class='ne1' /></td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class='nobborder'>
                                                <div id="tree" style='width:100%;margin:0 auto;'></div>
                                            </td>
                                        </tr>
                                        <tr id="linktoreport" style="display:none">
                                            <td class="nobborder">
                                                <table style="border:0px">
                                                    <tr>
                                                        <td><input type="checkbox" id="check_report" name="check_report" onclick='changes_made(true);'/></td>
                                                        <td class='ne1' nowrap='nowrap'><i><?php echo  _("Link to Asset Detail"); ?></i></td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <tr>
                                <td class='center'>
                                    <table class='transparent nobborder' id="link_map" align='center' style="margin:3px auto;">
                                        <tr id="linktomapurl" style="display:none;">
                                            <td>
                                                <table width="100%" class='transparent' style="border:0px">
                                                    <tr>
                                                        <td style="font-size:12px; width:30%; white-space:nowrap;"><?php echo  _("URL"); ?></td>
                                                        <td style="width:70%;"><input type='text' size='35' name="url" id='url' class='ne1' /></td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>

                                        <tr id="linktomapmaps">
                                            <td>
                                                <table style="width:100%;border:0px;">
                                                    <tr>
                                                        <td class='ne1 bold'><i> <?php echo  _("Choose map to link") ?>: </i></td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <table class="noborder" style="text-align:center; width: 100%;">
                                                                <tr>
                                                                    <?php echo $linkmaps?>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <br><br>
                                                            <table width="100%" style="border:0px;text-align:center;">
                                                                <tr>
                                                                    <td class='ne1' style="width:50%;">
                                                                         <input type="radio" onclick="indicator_type(true)" name="ind_option" id="link_option_ind"  value="ind" checked='checked'/><?php echo _("Indicator")?>
                                                                    </td>
                                                                    <td class='ne1' style="width:50%;">
                                                                         <input type="radio" onclick="indicator_type(false)" name="ind_option" id="link_option_rect" value="rect" /><?php echo _("Rectangle") ?>
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

            <br>

            <table id='indicator_layout'>
                <tr>
                    <th colspan="2" class='headerpr'><?php echo _("Indicator Layout")?></th>
                </tr>
                <tr>
                    <td style='text-align:center;padding-left:5px;border-right:1px solid #E4E4E4;'>
                        <div style="display:none">
                            <input type='hidden' name="alarm_id" value=""/> x <input type='text' size='1' name='posx'/> y <input type='text' size='1' name='posy'/>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style='text-align:center;padding-left:5px;border-right:1px solid #E4E4E4;'>
                        <table align='center' style="border:0px;width:100%;">
                            <tr class='icon_settings'>
                                <td class='bold label'><?php echo _('Icon')?></td>
                                <td style="width:60%;">
                                    <a href='indicator_icon.php' title='<?php echo _('Choose an icon') ?>' class='iconbox'><img src="pixmaps/standard/default.png" height='30px' name="chosen_icon" id="chosen_icon"/></a>
                                </td>
                            </tr>
                            <tr class='icon_settings'>
                                <td class='bold label'><?php echo _('Size')?></td>
                                <td style="width:60%;text-align:left">
                                    <select name="iconsize" id="iconsize" style='width:150px;' onchange='changes_made(true);'>
                                        <option value="0" selected="selected"><?php echo _("Default")?></option>
                                        <option value="30"><?php echo _("Small")?></option>
                                        <option value="40"><?php echo _("Medium")?></option>
                                        <option value="50"><?php echo _("Big")?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class='bold label' onchange='changes_made(true);'><?php echo _('Background')?></td>
                                <td style="width:60%;text-align:left">
                                    <select name="iconbg" id="iconbg" style='width:150px;' onchange='changes_made(true);'>
                                        <option value=""><?php echo _("Transparent")?></option>
                                        <option value="white"><?php echo _("White")?></option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </td>

                </tr>
                <tr>
                    <td  class='left' style='padding:10px 5px;font-size:11px;border-right:1px solid #E4E4E4;'>
                        <input type="checkbox" id="check_noicon" name="check_noname" onchange='icon_settings();'/> <?php echo _("Hide Icon")?><br>
                        <input type="checkbox" id="check_noname" name="check_noname" onchange='changes_made(true);'/> <?php echo _("Hide Indicator Name")?><br>
                     </td>
                </tr>
            </table>


            <!-- types -->
            <br/>
            <input type="hidden" name="type" id="type" value=""/>
            <input type="hidden" name="elem" id="elem" value=""/>
            <input type="hidden" name="elem_show" id="elem_show" value=""/>

            <div id='indnuevo'></div>

            <table class='noborder'  width="270px;">
                <tr>
                    <td class='noborder' nowrap='nowrap' style='text-align:center;'>
                        <input id="new_button"  type='button' value="<?php echo  _("New Indicator") ?>" onclick="add_new('<?php echo $map ?>','alarm')" class="bbutton"/>
                        <input id="rect_button" type='button' style='display:none;' value="<?php echo  _("New Rectangle") ?>" onclick="add_new('<?php echo $map ?>','rect')" class="bbutton"/>
                        <input id="save_button" type='button' style='display:none;'  value="<?php echo  _("Save Changes") ?>" onclick="save('<?php echo $map?>')" class="bbutton"/>
                    </td>
                </tr>
            </table>

        </form>

        </td>

        <td valign='top' id="map">
            <div id='mapmap'>
                <?php
                // *************** Print Indicators DIVs (print_inputs = true) ******************

                $ri_indicators = get_indicators_from_map($conn, $map);

                foreach ($ri_indicators as $ri_indicator)
                {
                    echo draw_indicator($conn, $ri_indicator, TRUE);
                }

                $db->close();
                ?>
                <img src="maps/map<?php echo $map?>.jpg" id="map_img" onclick="reset_values()" onload='initDiv();' class="MapMenu" border='0'/>
            </div>
        </td>
    </tr>
</table>
</body>
</html>

