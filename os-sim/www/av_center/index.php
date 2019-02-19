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

//Config File
require_once 'config.inc';
require_once 'data/breadcrumb.php';

$db       = new ossim_db();
$conn     = $db->connect();

$avc_list = Av_center::get_avc_list($conn);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title><?php echo _("OSSIM Framework");?></title>
    <meta http-equiv="Content-Type" content="text/html;charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>

    <?php
    //CSS Files
    $_files = array(
        array('src' => '/av_center/js/codemirror/codemirror.css',               'def_path' => FALSE),
        array('src' => 'tree.css',                                              'def_path' => TRUE),
        array('src' => 'jquery.autocomplete.css',                               'def_path' => TRUE),
        array('src' => '/js/jqplot/jquery.jqplot.css',                          'def_path' => FALSE),
        array('src' => 'xbreadcrumbs.css',                                      'def_path' => TRUE),
        array('src' => 'tipTip.css',                                            'def_path' => TRUE),
        array('src' => 'jquery.dataTables.css',                                 'def_path' => TRUE),
        array('src' => 'ui.multiselect.css',                                    'def_path' => TRUE),
        array('src' => 'jquery.contextMenu.css',                                'def_path' => TRUE),
        array('src' => 'progress.css',                                          'def_path' => TRUE),
        array('src' => 'jquery-ui-1.7.custom.css',                              'def_path' => TRUE),
        array('src' => 'av_common.css?t='.Util::get_css_id(),                   'def_path' => TRUE),
    );

    Util::print_include_files($_files, 'css');

    //JS Files
    $_files = array(
        array('src' => '/av_center/js/jquery.min.js',                            'def_path' => FALSE),
        array('src' => 'jquery-ui.min.js',                                       'def_path' => TRUE),
        array('src' => 'jquery.dynatree.js',                                     'def_path' => TRUE),
        array('src' => 'jquery.cookie.js',                                       'def_path' => TRUE),
        array('src' => 'jquery.autocomplete.pack.js',                            'def_path' => TRUE),
        array('src' => 'jquery.elastic.source.js',                               'def_path' => TRUE),
        array('src' => 'jquery.sparkline.js',                                    'def_path' => TRUE),
        array('src' => 'jqplot/jquery.jqplot.min.js',                            'def_path' => TRUE),
        array('src' => 'jqplot/plugins/jqplot.pieRenderer.js',                   'def_path' => TRUE),
        array('src' => 'jquery.dataTables.js',                                   'def_path' => TRUE),
        array('src' => 'jquery.dataTables.plugins.js',                           'def_path' => TRUE),
        array('src' => 'jquery.tmpl.1.1.1.js',                                   'def_path' => TRUE),
        array('src' => 'jquery.contextMenu.js',                                  'def_path' => TRUE),
        array('src' => 'ui.multiselect.js',                                      'def_path' => TRUE),
        array('src' => 'greybox.js',                                             'def_path' => TRUE),
        array('src' => 'notification.js',                                        'def_path' => TRUE),
        array('src' => 'ajax_validator.js',                                      'def_path' => TRUE),
        array('src' => 'messages.php',                                           'def_path' => TRUE),
        array('src' => 'utils.js',                                               'def_path' => TRUE),
        array('src' => 'token.js',                                               'def_path' => TRUE),
        array('src' => 'av_progress_bar.js.php',                                 'def_path' => TRUE),
        array('src' => '/av_center/js/codemirror/codemirror.js',                 'def_path' => FALSE),
        array('src' => '/av_center/js/codemirror/mode/xmlpure/xmlpure.js',       'def_path' => FALSE),
        array('src' => '/av_center/js/codemirror/mode/properties/properties.js', 'def_path' => FALSE),
        array('src' => '/av_center/js/codemirror/util/dialog.js',                'def_path' => FALSE),
        array('src' => '/av_center/js/codemirror/util/searchcursor.js',          'def_path' => FALSE),
        array('src' => '/av_center/js/codemirror/util/search.js',                'def_path' => FALSE),
        array('src' => '/av_center/js/config.js',                                'def_path' => FALSE),
        array('src' => '/av_center/js/xbreadcrumbs.js',                          'def_path' => FALSE),
        array('src' => '/av_center/js/jquery.tipTip.js',                         'def_path' => FALSE),
        array('src' => '/av_center/js/avc_msg.php',                              'def_path' => FALSE),
        array('src' => '/av_center/js/progress_bar.js',                          'def_path' => FALSE),
        array('src' => '/av_center/js/vprogress_bar.js',                         'def_path' => FALSE),
        array('src' => '/av_center/js/common.js',                                'def_path' => FALSE),
        array('src' => '/av_center/js/change_control.js',                        'def_path' => FALSE),
        array('src' => '/av_center/js/av_tree.js',                               'def_path' => FALSE),
        array('src' => '/av_center/js/av_center.js',                             'def_path' => FALSE)
    );

    Util::print_include_files($_files, 'js');

    ?>

    <!-- JQplot: -->
    <!--[if IE]><script language="javascript" type="text/javascript" src="../js/jqplot/excanvas.js"></script><![endif]-->

    <script type='text/javascript'>

        $(document).ready(function(){

            //JQplot
            $.jqplot.config.enablePlugins = true;

            //Ajax Request
            ajax_requests = new Ajax_Requests(20);

            //Action in progress: Saving data in forms.
            action_in_progress = false;

            $('#breadcrumbs').xBreadcrumbs();

            tree = new Tree('profile');

            if (tree.tree_status == '')
            {
                <?php
                if ($avc_list['status'] == 'error')
                {
                    ?>
                    $('.avc_hmenu').remove();
                    display_sec_errors(labels['error_ret_info']);
                    <?php
                }
                else
                {
                    ?>
                    tree.load_tree();

                    $('#avtc_container').tipTip({maxWidth: 'auto', content: labels['show_tree']});

                    $('#avtc_container').click(function() {
                        toggle_tree();
                    });

                    //Change Tree ordenation
                    $('#tree_ordenation').change(function() {
                        var type = $('#tree_ordenation').val();
                        tree.change_tree(type);
                    });

                    $('#search').click(function() {Main.pre_search_avc();});

                    <?php
                    //Alienvault Components (Autocomplete)
                    if (is_array($avc_list['data']))
                    {
                        $cont = 0;
                        foreach ($avc_list['data'] as $system_id => $data)
                        {
                            $av_components .= ($cont > 0) ? ", " : "";

                            $hostname = $data['name'];
                            $host_ip  = $data['admin_ip'];

                            $av_components .= '{"txt" : "'.$hostname.' ['.$host_ip.']", "id" :"'.$system_id.'" }';

                            $cont++;
                        }
                    }

                    ?>
                    var av_components = [ <?php echo $av_components?> ];
                    Main.autocomplete_avc(av_components);

                    $('#go').click(function() { Main.search(); });

                    Main.display_avc_info(true);
                    <?php
                }
                ?>
            }
            else
            {
                $('.avc_hmenu').remove();
                display_sec_errors(tree.tree_status);
            }
    });
    </script>
</head>

<body>

    <?php

    $db->close();

    //Local menu
    include_once '../local_menu.php';
    session_write_close();
    ?>
    <div id='main'>

        <div class='c_back_button'>
            <input type='button' class="av_b_back" id='lnk_go_back'/>
        </div>

        <div id='container_center'>
            <div id="avc_actions"></div>

            <table id='container_bc'>
                <tr>
                    <td id='bc_data'>
                        <ul class="xbreadcrumbs" id="breadcrumbs">
                            <li class='current'><a href='index.php' class="home"><?php echo _('AlienVault Center')?></a></li>
                        </ul>
                    </td>
                </tr>
            </table>

            <table id='section_container'>
                <tr class='avc_hmenu'>
                    <td id='avc_clcontainer'>
                        <div id='search_container'>

                            <div id='l_sc'>
                                <label id='lbl_search' for='search'><?php echo _('Search')?>:</label>
                                <input type='text' id='search' name='search' value='<?php echo _('Search by hostname or IP')?>'/>
                                <input type='hidden' id='h_search' name='h_search'/>
                                <input type='button' id='go' name='go' class='small' value='<?php echo _('Go')?>'/>

                                <div id='search_results'>
                                    <div></div>
                                </div>
                            </div>
                            <div id='r_sc'>
                                <label id='lbl_to' for='tree_ordenation'><?php echo _('Order By')?>:</label>
                                <select id='tree_ordenation' name='tree_ordenation'>
                                    <option value='profile' selected='selected'><?php echo _('profile')?></option>
                                    <option value='hostname'><?php echo _('hostname')?></option>
                                </select>
                            </div>
                        </div>
                        <div id='tree_container_top'>
                        </div>
                        <div id='tree_container_bt'></div>
                    </td>
                </tr>
                <tr class='avc_hmenu'>
                    <td id='avc_cmcontainer'>
                        <div id='avtc_container'>
                            <div id='avc_arrow' class='arrow_bottom'></div>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td id='avc_crcontainer'>
                        <div class="avc_content">
                            <div id="avc_data">
                                <div id='load_avc_data'></div>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>

</html>
