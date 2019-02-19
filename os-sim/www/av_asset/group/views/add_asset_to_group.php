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

Session::logcheck('environment-menu', 'PolicyHosts');

$group_id = GET('group_id');

ossim_valid($group_id,    OSS_HEX,    'illegal:' . _('Asset ID'));

if (ossim_error())
{
    die(ossim_error());
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title><?php echo _('AlienVault ' . (Session::is_pro() ? 'USM' : 'OSSIM')) ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>

    <?php
        //CSS Files
        $_files = array(
            array('src' => 'av_common.css',                 'def_path' => TRUE),
            array('src' => 'jquery-ui.css',                 'def_path' => TRUE),
            array('src' => 'jquery.dataTables.css',         'def_path' => TRUE),
            array('src' => 'lightbox.css',                  'def_path' => TRUE),
            array('src' => 'av_table.css',                  'def_path' => TRUE),
            array('src' => 'assets/asset_details.css',      'def_path' => TRUE)
        );

        Util::print_include_files($_files, 'css');

        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                  'def_path' => TRUE),
            array('src' => 'jquery.number.js.php',           'def_path' => TRUE),
            array('src' => 'utils.js',                       'def_path' => TRUE),
            array('src' => 'token.js',                       'def_path' => TRUE),
            array('src' => 'jquery.dataTables.js',           'def_path' => TRUE),
            array('src' => 'av_storage.js.php',              'def_path' => TRUE),
            array('src' => 'av_table.js.php',                'def_path' => TRUE)
        );

        Util::print_include_files($_files, 'js');
    ?>

    <script type='text/javascript'>

        var __cfg = <?php echo Asset::get_path_url() ?>;
        var __dt  = null;

        var __gid = "<?php echo $group_id ?>";

        $(document).ready(function()
        {
            __dt = $("[data-bind='av_table_assets']").AV_table(
            {
                "ajax_url"   : __cfg.group.providers + "dt_asset_group.php",
                "load_params":
                [
                    { "name": "group_id", "value": __gid}
                ],
                "language": "assets",
                "dt_params"  :
                {
                    "bFilter": true,
                    "aoColumns":
                    [
                        { "bSortable": false, "sClass": "center", "sWidth": "30px", "bVisible": false},
                        { "bSortable": true,  "sClass": "left"},
                        { "bSortable": true,  "sClass": "left"},
                        { "bSortable": false, "sClass": "left dt_force_wrap"},
                        { "bSortable": true,  "sClass": "left dt_force_wrap"},
                        { "bSortable": true,  "sClass": "center dt_force_wrap"},
                        { "bSortable": true,  "sClass": "center dt_force_wrap"},
                        { "bSortable": true,  "sClass": "center dt_force_wrap"},
                        { "bSortable": false, "sClass": "center", "sWidth": "50px"}
                    ]
                },
                "on_draw_row": function(ui, nRow, aData, iDrawIndex, iDataIndex)
                {
                    var id = aData['DT_RowId'];

                    $('<button></button>',
                    {
                        "class" : "av_b_secondary small",
                        "text"   : " + ",
                        "click" : function(e)
                        {
                            e.preventDefault();
                            e.stopPropagation();

                            add_asset(id);
                        }
                    }).appendTo($("td:last-child", nRow));
                }
            });

        });


        function save_selection(id)
        {
            var data   =
            {
                "asset_type": 'asset',
                "all"       : 0,
                "assets"    : [id]
            };

            var token = Token.get_token("save_selection");
            return $.ajax(
            {
                type: "POST",
                url: __cfg.common.controllers  + "save_selection.php",
                data: {"action": "save_list_selection", "token": token, "data": data},
                dataType: "json"
            }).fail(function(obj)
            {
                //Checking expired session
                var session = new Session(obj, '');
                if (session.check_session_expired() == true)
                {
                    session.redirect();
                    return;
                }
                notify(obj.responseText, 'nf_error', true);
            });
        }


        function add_asset(id)
        {
            save_selection(id).done(function()
            {
                var data   =
                {
                    "action"  : "add_new_assets",
                    "asset_id": __gid,
                    "token"   : Token.get_token("ag_form")
                };

                $.ajax(
                {
                    type: "POST",
                    url: __cfg.group.controllers  + "group_actions.php",
                    data: data,
                    dataType: "json"
                }).fail(function(obj)
                {
                    //Checking expired session
                    var session = new Session(obj, '');
                    if (session.check_session_expired() == true)
                    {
                        session.redirect();
                        return;
                    }
                    notify(obj.responseText, 'nf_error', true);

                }).done(function()
                {
                    __dt.reload_ajax();
                });
            });
        }

    </script>
</head>

<body>

    <div class='table_lb_wrapper'>
        <!-- GROUP LIST -->
        <?php
            include AV_MAIN_ROOT_PATH.'/av_asset/common/templates/tpl_dt_assets.php';
        ?>
    </div>

</body>

</html>
