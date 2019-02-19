<?php
/**
 * index.php
 *
 * File index.php is used to:
 * - Show the Asset Details page for a Host or a Network received by GET('id') parameter
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
 * @package    ossim-framework\Assets
 * @autor      AlienVault INC
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt
 * @copyright  2003-2006 ossim.net
 * @copyright  2007-2013 AlienVault
 * @link       https://www.alienvault.com/
 */
require_once 'av_init.php';


$id      = GET('asset_id');
$section = GET('section');

ossim_valid($id,  		 OSS_HEX,    					'illegal:' . _('Asset ID'));
ossim_valid($section,    OSS_ALPHA, OSS_NULLABLE,    	'illegal:' . _('Asset Section'));

if (ossim_error())
{
    die(ossim_error());
}

// Database Object
$db       = new ossim_db();
$conn     = $db->connect();

if (Asset_host::is_in_db($conn, $id))
{
    $asset_type   = 'asset';

    Session::logcheck('environment-menu', 'PolicyHosts');

    $breadcrumb   = array(
        'section' => _('Assets'),
        'current' => _('Asset Details')
    );

    $edit         = Asset_host::can_i_modify_ips($conn, $id);
    $delete       = Asset_host::can_delete($conn, $id);
    $local_assets = Asset_host::get_asset_by_system($conn, Util::get_system_uuid());
    $p_plugin     = Session::am_i_admin() && !$local_assets[$id];
    $deploy_agent = Session::logcheck_bool('environment-menu', 'EventsHidsConfig');
}
else if (Asset_net::is_in_db($conn, $id))
{
    $asset_type   = 'network';
    
    Session::logcheck('environment-menu', 'PolicyNetworks');
    
    $breadcrumb   = array(
        'section' => _('Networks'),
        'current' => _('Network Details')
    );
    
    $edit         = Asset_net::can_i_modify_ips($conn, $id);
    $delete       = Asset_net::can_delete($conn, $id);
    $p_plugin     = Session::am_i_admin();
    $deploy_agent = FALSE;
    
}
else if (Asset_group::is_in_db($conn, $id))
{
    $asset_type   = 'group';
    
    Session::logcheck('environment-menu', 'PolicyHosts');
    
    $breadcrumb   = array(
        'section' => _('Groups'),
        'current' => _('Group Details')
    );
    
    $gobj = new Asset_group($id);
    try
    {
        $edit = $gobj->can_i_edit($conn);
    }
    catch(Exception $err)
    {
        $edit = FALSE;
    }
    
    try
    {
        $delete = $gobj->can_delete_group($conn);
    }
    catch(Exception $err)
    {
        $delete = FALSE;
    }
    
    $p_plugin     = Session::am_i_admin();
    $deploy_agent = FALSE;
}
else
{
    $error = _('Invalid Asset ID');
    Av_exception::throw_error(Av_exception::USER_ERROR, $error);
}


$perms = array(
    'admin'           => Session::am_i_admin(),
    'delete'          => $delete,
    'edit'            => $edit,
    'vulnerabilities' => Session::logcheck_bool('environment-menu', 'EventsVulnerabilitiesScan'),
    'alarms'          => Session::logcheck_bool('analysis-menu', 'ControlPanelAlarms'),
    'events'          => Session::logcheck_bool('analysis-menu', 'EventsForensics'),
    'netflows'        => Session::logcheck_bool('environment-menu', 'MonitorsNetflows'),
    'nmap'            => Session::logcheck_bool('environment-menu', 'ToolsScan'),
    'availability'    => Session::logcheck_bool('environment-menu', 'MonitorsAvailability'),
    'hids'            => Session::logcheck_bool('environment-menu', 'EventsHids') || Session::logcheck_bool('environment-menu', 'EventsHidsConfig'),
    'deploy_agent'    => $deploy_agent,
    'plugins'         => $p_plugin
);


Filter_list::save_items($conn, $asset_type, $assets = array($id));

$db->close();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title><?php echo _('Asset Details') ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>

    <?php
        //CSS Files
        $_files = array(
            array('src' => 'jquery-ui.css',                         'def_path' => TRUE),
            array('src' => 'jquery.dataTables.css',                 'def_path' => TRUE),
            array('src' => 'av_common.css',                         'def_path' => TRUE),
            array('src' => 'tipTip.css',                            'def_path' => TRUE),
            array('src' => 'jquery.dropdown.css',                   'def_path' => TRUE),
            array('src' => 'av_dropdown_tag.css',                   'def_path' => TRUE),
            array('src' => 'av_show_more.css',                      'def_path' => TRUE),
            array('src' => 'av_tags.css',                           'def_path' => TRUE),
            array('src' => 'av_table.css',                          'def_path' => TRUE),
            array('src' => 'av_suggestions.css',                    'def_path' => TRUE),
            array('src' => 'assets/asset_indicators.css',           'def_path' => TRUE),
            array('src' => 'assets/asset_detail_sections.css',      'def_path' => TRUE),
            array('src' => 'assets/asset_details.css',              'def_path' => TRUE),
        );

        Util::print_include_files($_files, 'css');


        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                                         'def_path' => TRUE),
            array('src' => 'jquery-ui.min.js',                                      'def_path' => TRUE),
            array('src' => 'av_show_more.js',                                       'def_path' => TRUE),
            array('src' => 'jquery.number.js.php',                                  'def_path' => TRUE),
            array('src' => 'jquery.editinplace.js',                                 'def_path' => TRUE),
            array('src' => 'notification.js',                                       'def_path' => TRUE),
            array('src' => 'greybox.js',                                            'def_path' => TRUE),
            array('src' => 'utils.js',                                              'def_path' => TRUE),
            array('src' => 'token.js',                                              'def_path' => TRUE),
            array('src' => 'messages.php',                                          'def_path' => TRUE),
            array('src' => 'jquery.tipTip-ajax.js',                                 'def_path' => TRUE),
            array('src' => 'jquery.dataTables.js',                                  'def_path' => TRUE),
            array('src' => 'jquery.dropdown.js',                                    'def_path' => TRUE),
            array('src' => 'jquery.scroll.js',                                      'def_path' => TRUE),
            array('src' => 'av_scan.js.php',                                        'def_path' => TRUE),
            array('src' => 'av_map.js.php',                                         'def_path' => TRUE),
            array('src' => 'markerclusterer.js',                                    'def_path' => TRUE),
            array('src' => 'av_breadcrumb.js.php',                                  'def_path' => TRUE),
            array('src' => 'av_tabs.js.php',                                        'def_path' => TRUE),
            array('src' => 'av_table.js.php',                                       'def_path' => TRUE),
            array('src' => 'av_dropdown_tag.js',                                    'def_path' => TRUE),
            array('src' => 'av_tags.js.php',                                        'def_path' => TRUE),
            array('src' => 'av_storage.js.php',                                     'def_path' => TRUE),
            array('src' => 'av_suggestions.js.php',                                 'def_path' => TRUE),
            array('src' => 'av_note.js.php',                                        'def_path' => TRUE),
            array('src' => '/av_asset/common/js/asset_indicators.js.php',           'def_path' => FALSE),
            array('src' => '/av_asset/common/js/asset_detail.js.php',               'def_path' => FALSE),
            array('src' => '/av_asset/common/js/asset_detail_sections.js.php',      'def_path' => FALSE)
        );


        if ($asset_type == 'asset')
        {
            $_files[] = array('src' => '/av_asset/asset/js/detail_asset.js.php', 'def_path' => FALSE);
        }
        elseif ($asset_type == 'group')
        {
            $_files[] = array('src' => '/av_asset/group/js/detail_group.js.php', 'def_path' => FALSE);
        }
        elseif ($asset_type == 'network')
        {
            $_files[] = array('src' => '/av_asset/network/js/detail_network.js.php', 'def_path' => FALSE);
        }

        Util::print_include_files($_files, 'js');
        
    ?>


    <style type="text/css">


    </style>


    <script type='text/javascript'>

        var __asset_detail = null;


        function go_back()
        {
            __asset_detail.go_back()
        }
        
        
        function GB_onclose(url, params)
        {
            __asset_detail.manage_reload(url, params);
        }
        
        
        function GB_onhide(url, params)
        {
            __asset_detail.manage_reload(url, params);
        }


        $(document).ready(function()
        {
            if (typeof top.av_menu.set_bookmark_params == 'function')
            {
                top.av_menu.set_bookmark_params("<?php echo $id ?>");
            }

            var items = 
            {
                "all"   : {'title': "<?php echo Util::js_entities($breadcrumb['section']) ?>", 'action': go_back },
                "asset" : {'title': "<?php echo Util::js_entities($breadcrumb['current']) ?>", 'action': ''}
            };
            
            $('#asset_breadcrumb').AVbreadcrumb(
            {
                'items': items
            });


            __asset_detail = new av_<?php echo $asset_type?>_detail(
            {
	            "id"             : "<?php echo $id ?>", 
	            "section"        : "<?php echo $section ?>",
	            "scroll_section" : <?php echo ($section != '') ? 'true' : 'false' ?>,
	            "perms"          : <?php echo json_encode($perms) ?>
	        });
            
        });

    </script>
</head>

<body>


<!-- BreadCrumb -->
<div id='asset_breadcrumb'></div>

<div id="asset_notif"></div>

<div id="detail_actions">
    <a href='javascript:;' id='button_action' class='button' data-bind='button_action' data-dropdown="#dropdown-actions">
        <?php echo _('Actions') ?> &nbsp;&#x25be;
    </a>

    <img id='export_asset' data-bind='export-asset' class='img_action' src="/ossim/pixmaps/download-big.png"/>

</div>

<div id='detail_container'>

    <div class="column_1">

        <div id="detail_title">
            <div class='info_asset'>
                <img class="asset_icon" data-bind="asset_icon" src="" />
                <span class="asset_title" data-bind="asset_title"></span>
    
                <div id="ip_list"  data-bind="asset_ip"></div>
                <div id="fqdn" class='asset_host' data-bind="asset_fqdn"></div>
                <div id="operating_system" class='asset_host' data-bind="asset_os"></div>
            </div>
            
            <div class='info_group'>
                <img class="asset_icon" data-bind="group_icon" src="" />
                <span class="asset_title" data-bind="group_title"></span>
            </div>
    
            <div class='info_network'>
                <img class="asset_icon" data-bind="network_icon" src="" />
                <span class="asset_title" data-bind="network_title"></span>
    
                <div id="cidr_list"  data-bind="network_cidr"></div>
            </div>
        </div>

        <div id="detail_labels" class='info_asset' data-bind="detail_labels">
            <img id='label_selection' class='img_action' src="/ossim/pixmaps/label.png"/>
            <div data-bind="detail_label_container"></div>
        </div>


        <div id="detail_info">
            <div class='info_asset' data-bind="detail_info">
                <div class='colum_info_small'>
                    <div class='column_block'>
                        <span><?php echo _('Asset Value') ?></span>
                        <div class='asset_value' data-bind="asset_value"></div>
                    </div>
                    <div class='column_block'>
                        <span><?php echo _('Model') ?></span>
                        <div data-bind="asset_model"></div>
                    </div>
                    <div class='column_block'>
                        <span><?php echo _('Asset Type') ?></span>
                        <div data-bind="asset_type"></div>
                    </div>
                </div>
                <div class='colum_info_small'>
                    <div class='column_block'>
                        <span><?php echo _('Device Type') ?></span>
                        <div data-bind="asset_devices"></div>
                    </div>
                </div>
                <div class='colum_info_small'>
                    <div class='column_block'>
                        <span><?php echo _('Networks') ?></span>
                        <div data-bind="asset_networks"></div>
                    </div>
                </div>
                <div class='colum_info_small'>
                    <div class='column_block'>
                        <span><?php echo _('Sensors') ?></span>
                        <div data-bind="asset_sensors"></div>
                    </div>
                </div>
    
                <div class="clear_layer"></div>
            </div>
        
        
            <div class='info_group' data-bind="detail_info">
                <div class='column_block'>
                    <span><?php echo _('Owner') ?></span>
                    <div data-bind="group_owner"></div>
                </div>
    
                <div class="clear_layer"></div>
            </div>
    
    
            <div id="detail_info" class='info_network' data-bind="detail_info">
                <div class='colum_info_medium'>
                    <div class='column_block'>
                        <span><?php echo _('Asset Value') ?></span>
                        <div class='asset_value' data-bind="network_value"></div>
                    </div>
                </div>
                <div class='colum_info_medium'>
                    <div class='column_block'>
                        <span><?php echo _('Owner') ?></span>
                        <div data-bind="network_owner"></div>
                    </div>
                </div>
                <div class='colum_info_medium'>
                    <div class='column_block'>
                        <span><?php echo _('Sensors') ?></span>
                        <div data-bind="networ_sensors"></div>
                    </div>
                </div>
    
                <div class="clear_layer"></div>
            </div>
            
        </div>
        

        <div id="detail_indicators" data-bind="detail_indicators"></div>

        <div id="detail_description" data-bind="detail_description">
            <div class='column_block'>
                <span><?php echo _('Description') ?></span>
                <div id="asset_descr" data-bind="asset_descr"></div>
            </div>
        </div>

        <div id="detail_sections" data-bind="detail_sections">
            <ul></ul>
        </div>

        <div id="detail_notes" data-bind="detail_notes"></div>

        <div class="clear_layer"></div>

    </div>

    <div class="column_2">
        
        <div id="detail_map" data-bind="detail_map" class='block_section'>
        
            <span class='b_title'><?php echo _('ASSET LOCATION') ?></span>
            
            <div id="asset_map" class='b_content'></div>
            
        </div>


        <div id="detail_snapshot" data-bind="detail_snapshot" class='block_section'>

            <span class='b_title'><?php echo _('ENVIRONMENT STATUS') ?></span>

            <div class='b_content'>
                <div class="detail_led" data-bind='led_hids'><?php echo _('HIDS')?></div>
                <div class="detail_led" data-bind='led_nmap'><?php echo _('Automatic Asset Discovery')?></div>
                <div class="detail_led" data-bind='led_vulnerabilities'><?php echo _('Vuln Scan Scheduled')?></div>
            </div>

            <div id='netflows_link' class='av_link' data-bind='netflows_link'>
                <?php echo _('See Network Activity') ?>
            </div>

        </div>


        <div id="detail_suggestions" data-bind="detail_suggestions" class='block_section'>
            <span class='b_title'><?php echo _('SUGGESTIONS') ?></span>
            <div data-bind="suggestion_list" class='b_content'></div>
        </div>

        <div class="clear_layer"></div>

    </div>

</div>


<div id="dropdown-actions" data-bind="dropdown-actions" class="dropdown dropdown-close dropdown-tip dropdown-anchor-right">
	<ul class="dropdown-menu"></ul>
</div>

</body>
</html>
