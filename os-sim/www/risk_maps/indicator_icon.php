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


Session::logcheck("dashboard-menu", "BusinessProcesses");

if (!Session::menu_perms("dashboard-menu", "BusinessProcessesEdit") )
{
    echo ossim_error(_("You don't have permissions to see this page"));
    exit();
}


$name = POST('name');
$name = str_replace('..', '', $name);

$allowed_formats   = array(
    IMAGETYPE_JPEG => 1,
    IMAGETYPE_GIF  => 1,
    IMAGETYPE_PNG  => 1
);

$filename          = '';
$validation_errors = array();


if (isset($_POST['upload']))
{
    $validate = array(
        'name' => array('validation' => 'OSS_ALPHA, OSS_DIGIT, OSS_SCORE, ".,%"',  'e_message'  =>  'illegal:' . _('Icon Name'))
    );

    $validation_errors = validate_form_fields('POST', $validate);

    if (!is_array($validation_errors) || empty($validation_errors))
    {
        if (is_uploaded_file($_FILES['icon_file']['tmp_name']))
        {
            if ($allowed_formats[exif_imagetype ($_FILES['icon_file']['tmp_name'])] == 1)
            {
                $size = getimagesize($_FILES['icon_file']['tmp_name']);
                if ($size[0] < 400 && $size[1] < 400)
                {
                    $filename = "pixmaps/uploaded/$name.jpg";
                    move_uploaded_file($_FILES['icon_file']['tmp_name'], $filename);
                }
                else
                {
                    $validation_errors['icon_file'] = _("Icon uploaded is too big (Max image size 400x400 px)");
                }
            }
            else
            {
                $validation_errors['icon_file'] = _("The image format should be JPG, GIF or PNG");
            }
        }
        else
        {
            switch ($_FILES['icon_file']['error'])
            {
                case UPLOAD_ERR_NO_FILE:
                    $error_msg = _('No icon file sent');
                break;

                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error_msg = _('Exceeded filesize limit');
                default:
                    $error_msg = _('An error when processing the request');
            }

            $validation_errors['icon_file'] = _('Icon could not be uploaded. Reason').': '.$error_msg;
        }
    }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title> <?php echo _("OSSIM Framework"); ?> </title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <script type="text/javascript" src="/ossim/js/jquery.min.js"></script>
    <script type="text/javascript">
        function display_icons()
        {
            var option = $('#icon_browser').val();

            if (option == 1)
            {
                $('#default_icon').show();
                $('#flag_icon').hide();
                $('#own_icon').hide();
            }
            else if (option == 2)
            {
                $('#default_icon').hide();
                $('#flag_icon').show();
                $('#own_icon').hide();

            }
            else if (option == 3)
            {
                $('#default_icon').hide();
                $('#flag_icon').hide();
                $('#own_icon').show();

            }
            else
            {
                $('#default_icon').show();
                $('#flag_icon').show();
                $('#own_icon').show();
            }
        }

        function upload(flag)
        {
            if (flag)
            {
                $('#icon_list').hide();
                $('#c_upload_form').show();
                $('#GB_window', parent.document).css({'height':'195px', 'width':'500px'});
                $('#GB_frame', parent.document).css({'height':'160px', 'width':'500px'});
            }
            else
            {
                $('#icon_list').show();
                $('#c_upload_form').hide();
                $('#GB_window', parent.document).css({'height':'435px', 'width':'500px'});
                $('#GB_frame', parent.document).css({'height':'400px', 'width':'500px'});
            }
        }

        function go_back()
        {
            $('#nt_1').remove();

            upload(false);
        }

        $(document).ready(function()
        {
            $('#lnk_go_back').click(function(){
                go_back();
            });

            $(".iconclick").click(function(e)
            {
                var icon = $(this).attr("src");

                var params = new Array();
                    params['icon'] = icon;

                parent.GB_hide(params);
            });

            <?php
            if (!empty($name) && empty($validation_errors))
            {
                ?>
                var params = new Array();
                    params['icon'] = "<?php echo $filename?>";

                    parent.GB_hide(params);
                <?php
            }

            // Show upload form again when there is an error
            if (!empty($validation_errors))
            {
                ?>
                upload(true);
                <?php
            }
            ?>
        });
    </script>

    <style type="text/css">
        #icon_list
        {
            padding: 10px 5px;
        }

        #c_actions
        {
            width: 100%;
            margin: auto;
            padding-bottom: 30px;
        }

        #c_link
        {
            float: left;
            padding-left: 5px;
        }

        #c_browser
        {
            float: right;
            padding-right: 5px;
        }

        #c_icons_by_source
        {
            clear: both;
            min-height: 590px;
        }

        #c_upload_form
        {
            display: none;
            padding: 15px 5px 5px 5px;
            min-height: 590px;
        }

        #rm_up_icon
        {
            width: 420px;
            margin:auto;
        }

        .iconclick
        {
            cursor: pointer;
            border: none;
        }

        #c_go_back
        {
            padding-top: 15px;
            margin: 0px auto;
            text-align: center;
            width: 100%;
            font-size: 12px;
        }
    </style>
</head>

<body class="transparent">
    <?php
    if (is_array($validation_errors) && !empty($validation_errors))
    {
        $txt_error = '<div>'._('The following errors occurred').":</div>
                          <div style='padding: 10px;'>".implode('<br/>', $validation_errors).'</div>';

        $config_nt = array(
            'content' => $txt_error,
            'options' => array (
                    'type'          => 'nf_error',
                    'cancel_button' => FALSE
            ),
            'style'   => 'width: 80%; margin: 20px auto;'
        );

        $nt = new Notification('nt_1', $config_nt);
        $nt->show();
    }
    ?>

    <div id='icon_list'>
        <div id='c_actions'>
            <div id='c_link'>
                <a href='javascript:;' onclick='upload(true);'><?php echo _('Upload your own icon')?></a>
            </div>

            <div id='c_browser'>
                <span><?php echo _('Browse')?>:</span>
                <select id='icon_browser' onchange="display_icons();">
                    <option value="0"><?php echo _("All")?></option>
                    <option value="1"><?php echo _("Default Icons")?></option>
                    <option value="2"><?php echo _("Country Flags")?></option>
                    <option value="3"><?php echo _("Own Uploaded")?></option>
                </select>
            </div>
        </div>

        <div id='c_icons_by_source'>
            <?php
            $pixmap_sources = array(
                'default' => array(
                    'section'      => 'default_icon',
                    'source'       => 'pixmaps/standard/',
                    'size_by_icon' => 25
                ),
                'flags' => array(
                    'section'      => 'flag_icon',
                    'source'       => 'pixmaps/flags/',
                    'size_by_icon' => 15
                ),
                'uploaded' => array(
                    'section'      => 'own_icon',
                    'source'       => 'pixmaps/uploaded/',
                    'size_by_icon' => 20
                )
            );

            $col = 10;

            foreach($pixmap_sources as $ps_key => $ps_data)
            {
                ?>
                <div id='<?php echo $ps_data['section']?>'>
                    <table class='transparent center' style="width:100%;">
                        <?php
                        $fil = 0;
                        $flag_end = 0;

                        $icons = scandir($ps_data['source']);

                        foreach ($icons as $ico)
                        {
                            if (empty($ico) || is_dir($ps_data['source'].$ico) || !getimagesize($ps_data['source'].$ico))
                            {
                                continue;
                            }

                            if ($fil % $col == 0)
                            {
                                if ($flag_end == TRUE)
                                {
                                    echo '</tr>';
                                    $flag_end = 0;
                                }
                                else
                                {
                                    $flag_end = 1;
                                }

                                echo '</tr>';
                            }

                            ?>
                            <td class='nobborder' style='text-align:center;'>
                                <img class='iconclick' src='<?php echo $ps_data['source'].$ico?>' height='<?php echo $ps_data['size_by_icon']?>'/>
                            </td>
                            <?php

                            $fil++;
                        }
                        ?>
                    </table>
                </div>
                <?php
            }
            ?>
        </div>
    </div>

    <div id='c_upload_form'>
        <form action="indicator_icon.php" method='POST' enctype='multipart/form-data'>
            <table id='rm_up_icon'>
                <tr>
                    <th><?php echo _('Icon name')?></th>
                    <td class='left'><input type='text' class='ne1' name='name' value="<?php echo Util::htmlentities($name) ?>"/></td>
                </tr>
                <tr>
                    <th><?php echo _('Icon file')?></th>
                    <td class='left'><input type='file' class='ne1' size='27' name='icon_file'/></td>
                </tr>
                <tr>
                    <td class='cont_submit noborder' colspan='2'>
                        <input type='submit' name="upload" id="upload" class="small" value="<?php echo _('Upload')?>"/>
                    </td>
                </tr>
            </table>

            <div id='c_go_back'>
                <span id="lnk_go_back">
                    <a href='javascript:;'><?php echo _('Or go back and select an existing icon')?></a>
                </span>
            </div>
        </form>
    </div>
</body>
</html>
