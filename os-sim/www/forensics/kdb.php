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

$db   = new ossim_db();
$conn = $db->connect();

$plugin_id  = intval(GET('plugin_id'));
$plugin_sid = intval(GET('plugin_sid'));

$vars       = $_SESSION['_kdb_vars'];
$docs       = 0;

if($plugin_id != '' && $plugin_sid != '')
{
    //Taxonomy
    $ptype   = Product_type::get_product_type_by_plugin($conn, $plugin_id);
    $cat     = Category::get_category_subcategory_by_plugin($conn, $plugin_id, $plugin_sid);
    $keyname = (empty($ptype['id'])? 0 : $ptype['id'])."##".(empty($cat['cid'])? 0 : $cat['cid'])."##".(empty($cat['scid'])? 0 : $cat['scid']);

    $repository_list['taxonomy'] = Repository::get_repository_linked($conn, $keyname, 'taxonomy');

    //Directive
    if($plugin_id == '1505')
    {
        $repository_list['directive'] = Repository::get_linked_by_directive($conn, $plugin_sid);
    }

    //Plugin SID
    $keyname = "$plugin_sid##$plugin_id";
    $repository_list['plugin_sid'] = Repository::get_repository_linked($conn, $keyname, 'plugin_sid');


    $docs = count($repository_list['directive']) + count($repository_list['plugin_sid']) + count($repository_list['taxonomy']);
}

$db->close();

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
            array('src' => 'av_common.css',                 'def_path' => TRUE),
            array('src' => 'jquery-ui.css',                 'def_path' => TRUE),
            array('src' => '/alarm/detail.css',             'def_path' => TRUE)
        );

        Util::print_include_files($_files, 'css');

        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                   'def_path' => TRUE),
            array('src' => 'jquery-ui.min.js',                'def_path' => TRUE),
            array('src' => 'greybox.js',                      'def_path' => TRUE)
        );

        Util::print_include_files($_files, 'js');

    ?>

    <style type='text/css'>

        body#body_scroll #kdb_list_container
        {
            margin: 5px 15px 25px 15px;
        }

        #kdb_container
        {
            padding: 1px;
            margin:0 auto;
            text-align:left;
            min-height: 400px;
        }

        .text_container
        {
            font-size: 11px;
            padding: 10px 15px 5px 15px;
        }

        .text_sumary
        {

            padding: 35px 15px 20px 10px;

        }

        .legend
        {
            text-align:left;
            width:100px;
        }

        .txt
        {
            font-weight: bold;
            text-align:left;
        }

    </style>

    <script type='text/javascript'>

        function toggle_arrow(section, type)
        {
            if(typeof(type) == 'undefined')
            {
                type = 0;
            }

            var img = new Array();
            img[0]  = new Array();
            img[1]  = new Array();

            img[0][0] = '/ossim/pixmaps/arrow_green.gif';
            img[0][1] = '/ossim/pixmaps/arrow_green_down.gif';

            img[1][0] = '/ossim/pixmaps/plus-small.png';
            img[1][1] = '/ossim/pixmaps/minus-small.png';

            if ($('#'+section).is(':visible')){
                $('#'+section+'_img').attr('src',img[type][0]);
            }
            else{
                $('#'+section+'_img').attr('src',img[type][1]);
            }
            $('#'+section).toggle();
        }

        $(document).ready(function()
        {
            $("#accordion").accordion(
            {
                collapsible: true,
                autoHeight: false,
                active: false
            });

            $('.lbox').on('click', function()
            {
                if (typeof parent.is_lightbox_loaded == 'function' && parent.is_lightbox_loaded(window.name))
                {
                    return true;
                }
                else
                {
                    var title = "<?php echo _('Knowledge Base') ?>";
                    GB_show(title, this.href, '70%','80%');

                    return false;
                }
            });

        });
    </script>
</head>

<body>

<div id='kdb_list_container'>
<?php
if ( $docs == 0 )
{
    echo "<div id='no_kdb'>" . _('No Documents Found') . "</div>";
}
else
{
    $parser = new KDB_Parser();
    $parser->load_session_vars($vars);

    if(count($repository_list['taxonomy']) > 0)
    {
        $doc = array_shift($repository_list['taxonomy']);
    }
    elseif(count($repository_list['directive']) > 0)
    {
        $doc = array_shift($repository_list['directive']);
    }
    elseif(count($repository_list['plugin_sid']) > 0)
    {
        $doc = array_shift($repository_list['plugin_sid']);
    }
?>

    <div id='kdb_container'>

        <div class='text_container'>

            <strong><?php echo $doc->get_title() ?></strong>

            <br/><br/>

            <?php
                $parser->proccess_file($doc->get_text(FALSE));

                echo $parser->print_text();
            ?>
        </div>

        <div class='text_sumary'>
            <strong>
                <img id='kdb_sumary_0_img' align="absmiddle" border="0" src="/ossim/pixmaps/arrow_green.gif">
                <a href='javascript:void(0);' onclick="toggle_arrow('kdb_sumary_0');"><?php echo _('Document Summary') ?></a>
            </strong>

            <br/>

            <table id='kdb_sumary_0' style='margin-top:3px;width:100%;display:none;'>
                <tr>
                    <td class='legend'>
                        <?php echo _('Document') . ':' ?>
                    </td>
                    <td class='txt'>
                        <a class='lbox' href="/ossim/repository/repository_document.php?id_document=<?php echo $doc->get_id() ?>">
                            <?php echo $doc->get_title() ?>
                        </a>
                    </td>
                </tr>
                <tr>
                    <td class='legend'>
                        <?php echo _('Visibility') . ':' ?>
                    </td>
                    <td class='txt'>
                        <?php echo $doc->get_visibility() ?>
                    </td>
                </tr>
                <tr>
                    <td class='legend'>
                        <?php echo _('Date') . ':' ?>
                    </td>
                    <td class='txt'>
                        <?php echo $doc->get_date() ?>
                    </td>
                </tr>
                <tr>
                    <td class='legend'>
                        <?php echo _('Attachements') . ':' ?>
                    </td>
                    <td class='txt'>
                    <?php
                        $num_attach = count($doc->get_attach());

                        if($num_attach > 0)
                        {
                        ?>
                            (<?php echo $num_attach ?>)
                            <a class='lbox' href="/ossim/repository/repository_document.php?id_document=<?php echo $doc->get_id() ?>">
                                <img src="/ossim/repository/images/attach.gif" alt="" border="0" align='absmiddle'/>
                            </a>
                        <?php
                        }
                         else
                        {
                            echo '-';
                        }
                    ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <?php
    if($docs > 1)
    {
    ?>
        <strong>
            <img id='accordion_img' align="absmiddle" border="0" src="/ossim/pixmaps/plus-small.png">
            <a href='javascript:;' onclick="toggle_arrow('accordion', 1)"><?php echo _('Read More Articles') . " (" . ($docs - 1) . ")" ?></a>
        </strong>

        <br/>

        <div id='accordion' style='display:none;padding-top:5px;'>
            <?php
            $k = 1;
            foreach($repository_list as $type => $repository_type)
            {
                switch($type)
                {
                    case 'plugin_sid':
                        $type = _('Plugin SID');
                        break;

                    case 'directive':
                        $type = _('Directive');
                        break;

                    case 'taxonomy':
                        $type = _('Taxonomy');
                        break;

                    default:
                        $type = _('Unknown');
                }

                foreach($repository_type as $doc)
                {
                ?>

                    <h3><a href='#'><strong><?php echo $doc->get_title() ?></strong> [<?php echo $type ?>]</a></h3>

                    <div>

                        <div class='text_container'>
                        <?php
                            $parser->proccess_file($doc->get_text(FALSE));

                            echo $parser->print_text();
                        ?>
                        </div>

                        <div class='text_sumary'>
                            <strong>
                                <img id='kdb_sumary_<?php echo $k ?>_img' align="absmiddle" border="0" src="/ossim/pixmaps/arrow_green.gif">
                                <a href='javascript:;' onclick="toggle_arrow('kdb_sumary_<?php echo $k ?>')"><?php echo _('Document Summary') ?></a>
                            </strong>
                            <br>
                            <table id='kdb_sumary_<?php echo $k ?>' style='margin-top:3px;width:100%;display:none;'>
                                <tr>
                                    <td class='legend'>
                                        <?php echo _('Document') . ':' ?>
                                    </td>
                                    <td class='txt'>
                                        <a class='lbox' href="/ossim/repository/repository_document.php?id_document=<?php echo $doc->get_id() ?>">
                                            <?php echo $doc->get_title() ?>
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td class='legend'>
                                        <?php echo _('Visibility') . ':' ?>
                                    </td>
                                    <td class='txt'>
                                        <?php echo $doc->get_visibility() ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class='legend'>
                                        <?php echo _('Date') . ':' ?>
                                    </td>
                                    <td class='txt'>
                                        <?php echo $doc->get_date() ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td class='legend'>
                                        <?php echo _('Attachements') . ':' ?>
                                    </td>
                                    <td class='txt'>
                                    <?php
                                        $num_attach = count($doc->get_attach());
                                        if($num_attach > 0)
                                        {
                                        ?>

                                            (<?php echo $num_attach ?>)
                                            <a class='lbox' href="/ossim/repository/repository_document.php?id_document=<?php echo $doc->get_id() ?>">
                                                <img src="/ossim/repository/images/attach.gif" alt="" border="0" align='absmiddle'/>
                                            </a>

                                        <?php
                                        }
                                        else
                                        {
                                            echo '-';
                                        }
                                    ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                <?php
                    $k++;
                }
            }
        ?>
        </div>
    <?php
    }
}
?>
</div>

</body>

</html>
