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
require_once '../tab_config.php';

Session::logcheck('environment-menu', 'PolicyHosts');


/* Flags */

//Asset saved in database
$is_in_db = 0;

//Asset editable
$is_editable = 'yes';

//Current Tab
$current_tab = REQUEST('c_tab');

//Hide tab list
$hide_tab_list = REQUEST('hide_tab_list');

//Form type
$edition_type = (isset($_REQUEST['edition_type'])) ? REQUEST('edition_type') : 'single';

//Asset Type
$asset_type = REQUEST('asset_type');


//Database connection
$db    = new ossim_db();
$conn  = $db->connect();

if ($edition_type == 'single')
{
    // Single edition

    $id   = GET('id');
    $_ip  = GET('ip');
    $_ctx = GET('ctx');

    //Getting host by IP and CTX
    if (empty($id) && !empty($_ctx) && !empty($_ip))
    {
        $entity_type = Session::get_entity_type($conn, $_ctx);
        $entity_type = strtolower($entity_type);

        //Sometimes CTX is an engine instead of context
        $_ctx = ($entity_type == 'context') ? $_ctx : Session::get_default_ctx();

        if (Asset_host_ips::valid_ip($_ip) && valid_hex32($_ctx))
        {
            $aux_ids = Asset_host::get_id_by_ips($conn, $_ip, $_ctx);
            $aux_id  = key($aux_ids);

            if (Asset_host::is_in_db($conn, $aux_id))
            {
                $id = $aux_id;
            }
        }
        else
        {
            unset($_ip);
            unset($_ctx);
        }
    }


    if (!empty($id) && Asset_host::is_in_db($conn, $id))
    {
        ossim_valid($id, OSS_HEX, 'illegal:' . _('Asset ID'));

        if (ossim_error())
        {
            echo ossim_error(_('Error! Asset not found'));

            exit();
        }

        $asset = new Asset_host($conn, $id);
        $asset->load_from_db($conn);

        $is_in_db    = 1;
        $is_editable = (Asset_host::can_i_modify_ips($conn, $id)) ? 'yes' : 'no_ip';
    }
    else
    {
        //New asset or asset has been deleted but there are some instances in the system (SIEM, alarms, ...)

        $id    = (valid_hex32($id)) ? $id : Util::uuid();
        $asset = new Asset_host($conn, $id);

        if (isset($_ip) && isset($_ctx))
        {
            $asset->set_ctx($_ctx);

            $ext_ips[$_ip] = array(
                'ip'   =>  $_ip,
                'mac'  =>  NULL
            );

            $asset->set_ips($ext_ips);
        }
    }


    //Getting asset data
    $id   = $asset->get_id();
    $ctx  = $asset->get_ctx();
    $_ips = $asset->get_ips();
    $ips  = $_ips->get_ips();

    if (is_array($ips) && !empty($ips))
    {
        $ips = array_keys($ips);
    }

    //Check asset context
    $ext_ctxs = Session::get_external_ctxs($conn);

    if (!empty($ext_ctxs[$ctx]))
    {
        $is_editable = 'no';
    }
}
else
{
    $edition_type = 'bulk';
}


//Getting configuration for default tab
if (empty($current_tab) || !array_key_exists($current_tab, $tab_config[$edition_type]))
{
    $current_tab = 'general';
}

//Getting tab data
$tab_data = $tab_config[$edition_type][$current_tab];


//Visualization options of tabs
if ($hide_tab_list != '')
{
    //Hide options is forced
    $hide_tab_list = intval($hide_tab_list);
}
else
{
    $hide_tab_list = ($edition_type == 'single' && $is_in_db == 0) ? 1 : $tab_data['hide_tab_list'];
}


//Closing database connection
$db->close();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <title><?php echo _("OSSIM Framework");?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
        <meta name="viewport" content="initial-scale=1.0, user-scalable=no"/>
        <meta http-equiv="Pragma" content="no-cache"/>

        <?php
        //CSS Files
        $_files = array(
            array('src' => 'av_common.css?t='.Util::get_css_id(),           'def_path' => TRUE),
            array('src' => 'jquery-ui.css',                                 'def_path' => TRUE),
            array('src' => 'jquery.dataTables.css',                         'def_path' => TRUE),
            array('src' => 'jquery.autocomplete.css',                       'def_path' => TRUE),
            array('src' => 'jquery.switch.css',                             'def_path' => TRUE),
            array('src' => 'tree.css',                                      'def_path' => TRUE),
            array('src' => 'tipTip.css',                                    'def_path' => TRUE),
            array('src' => 'av_icon.css',                                   'def_path' => TRUE),
            array('src' => '/assets/asset_form.css',                        'def_path' => TRUE),
            array('src' => 'jquery.dropdown.css',                           'def_path' => TRUE)
        );

        Util::print_include_files($_files, 'css');

        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                                  'def_path' => TRUE),
            array('src' => 'jquery-ui.min.js',                               'def_path' => TRUE),
            array('src' => 'jquery.base64.js',                               'def_path' => TRUE),
            array('src' => 'jquery.cookie.js',                               'def_path' => TRUE),
            array('src' => 'jquery.tmpl.1.1.1.js',                           'def_path' => TRUE),
            array('src' => 'jquery.dynatree.js',                             'def_path' => TRUE),
            array('src' => 'jquery.elastic.source.js',                       'def_path' => TRUE),
            array('src' => 'jquery.autocomplete_geomod.js',                  'def_path' => TRUE),
            array('src' => 'jquery.dataTables.js',                           'def_path' => TRUE),
            array('src' => 'greybox.js',                                     'def_path' => TRUE),
            array('src' => 'notification.js',                                'def_path' => TRUE),
            array('src' => 'ajax_validator.js',                              'def_path' => TRUE),
            array('src' => 'messages.php',                                   'def_path' => TRUE),
            array('src' => 'geo_autocomplete.js',                            'def_path' => TRUE),
            array('src' => 'utils.js',                                       'def_path' => TRUE),
            array('src' => 'token.js',                                       'def_path' => TRUE),
            array('src' => 'jquery.tipTip.js',                               'def_path' => TRUE),
            array('src' => 'combos.js',                                      'def_path' => TRUE),
            array('src' => 'av_icon.js.php',                                 'def_path' => TRUE),
            array('src' => 'asset_context_tree.js.php',                      'def_path' => TRUE),
            array('src' => 'asset_devices.js.php',                           'def_path' => TRUE),
            array('src' => 'av_map.js.php',                                  'def_path' => TRUE),
            array('src' => 'av_tabs.js.php',                                 'def_path' => TRUE),
            array('src' => 'jquery.switch.js',                               'def_path' => TRUE),
            array('src' => 'av_storage.js.php',                              'def_path' => TRUE),
            array('src' => 'jquery.dropdown.js',                             'def_path' => TRUE),
            array('src' => '/av_asset/asset/js/asset_common.js.php',         'def_path' => FALSE),
            array('src' => '/av_asset/asset/js/asset_form.js.php',           'def_path' => FALSE),
            array('src' => '/av_asset/asset/js/asset_property_list.js.php',  'def_path' => FALSE),
            array('src' => '/av_asset/asset/js/asset_software_list.js.php',  'def_path' => FALSE),
            array('src' => '/av_asset/asset/js/asset_service_list.js.php',   'def_path' => FALSE)
        );

        Util::print_include_files($_files, 'js');
        ?>

        <script type='text/javascript'>

            $(document).ready(function()
            {
                // Load tabs
                <?php
                if ($edition_type == 'bulk')
                {
                    $js_tab_config = array(
                        'id'       => 'av_tabs',
                        'hide'     => $hide_tab_list,
                        'selected' => $tab_data['tab_index'],
                        'asset_options' => array(
                            "asset_type" => $asset_type
                        )
                    );
                    ?>

                    var _tab_config = jQuery.parseJSON('<?php echo json_encode($js_tab_config)?>');
                    var asset_tabs  = new Av_tabs_asset_bulk_edition(_tab_config);
                        asset_tabs.draw_tabs();
                    <?php
                }
                else
                {
                    $js_tab_config = array(
                        'id'            => 'av_tabs',
                        'selected'      => $tab_data['tab_index'],
                        'hide'          => $hide_tab_list,
                        'asset_options' => array(
                            "asset_type"  => "asset",
                            "id"          => $id,
                            "ips"         => $ips,
                            "ctx"         => $ctx,
                            "is_in_db"    => $is_in_db,
                            "is_editable" => $is_editable
                        )
                    );
                    ?>

                    var _tab_config = jQuery.parseJSON('<?php echo json_encode($js_tab_config)?>');
                    var asset_tabs  = new Av_tabs_asset_edition(_tab_config);
                        asset_tabs.draw_tabs();
                    <?php
                }


                if (isset($_GET['msg']) && $_GET['msg'] == 'saved')
                {
                    $_message = _('Your changes have been saved.');

                    if (GET('asset_type') == 'group')
                    {
                        $_message = _('Your changes have been applied to the assets in this group.');
                    }
                    elseif (GET('asset_type') == 'network')
                    {
                        $_message = _('Your changes have been applied to the assets in this network.');
                    }

                    unset($_GET['msg']);
                    unset($_GET['asset_type']);

                    ?>
                    if (typeof(parent) != 'undefined')
                    {
                        //Try - Catch to avoid if this launch an error, the lightbox must be closed.
                        try
                        {
                            top.frames['main'].show_notification('asset_notif', "<?php echo $_message ?>", 'nf_success', 15000, true);
                        }
                        catch(Err){}

                        var params =
                        {
                            'id': "<?php echo $id ?>"
                        }

                        parent.GB_hide(params);
                    }
                    <?php
                }
                ?>
            });
        </script>
    </head>

    <body>
        <div id="ae_container">

            <div id='av_tab_info'></div>

            <!-- Load Tab Data -->
            <div id='av_tabs'>
                <ul data-bind='av_tab_list' id='ul_av_tabs'></ul>
            </div>

        </div>
    </body>
</html>
