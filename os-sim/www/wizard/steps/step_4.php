<?php
/**
 * snap_load.php
 *
 * File snap_load.php is used to:
 * - Response ajax call from index.php
 * - Fill the data into asset details Snapshot section
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

//Checking active session
Session::useractive();

//Checking permissions
if (!Session::am_i_admin())
{
    echo _('You do not have permissions to see this section');
    
    die();
}

/************************************************************************************************/
/************************************************************************************************/
/***  This file is includen in step_loader.php hence the wizard object is defined in $wizard  ***/
/***                         database connection is stored in $conn                           ***/
/************************************************************************************************/
/************************************************************************************************/

if(!$wizard instanceof Welcome_wizard)
{
    throw new Exception("There was an error, the Welcome_wizard object doesn't exist");
}

$system_list = Av_center::get_avc_list($conn);
$admin_ip    = @$system_list['data'][strtolower(Util::get_system_uuid())]['admin_ip'];

$table       = ', host_types ht, host_ip hip';
$f           = array();

$f['where']  = " host.id=ht.host_id AND ht.type=4 AND hip.host_id=host.id AND hip.ip!=inet6_aton('$admin_ip')";


try
{
    list($hosts, $total) = Asset_host::get_list($conn, $table, $f, FALSE);
    list($active_plugins, $max_allowed, $max_available) = Plugin::get_plugins_by_assets();
}
catch(Exception $e)
{
    $total = 0;
    Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
}


if ($total > 0)
{
    try
    {
        $vendors = Software::get_hardware_vendors();
    }
    catch(Exception $e)
    {
        $vendors = array();
        Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
    }
    
    $device_list = array();

    foreach ($hosts as $asset_id => $host)
    {
        $plugin_list = array();
        
        $asset_id_canonical = Util::uuid_format($asset_id);
        
        if (count($active_plugins[$asset_id_canonical]) < 1)
        {
            $plugin_list[$asset_id][] = array(
                'vendor'       => '',
                'model'        => '',
                'version'      => '',
                'model_list'   => array(),
                'version_list' => array()
            );
        }
        else
        {
            foreach ($active_plugins[$asset_id_canonical] as $pdata)
            {
                $models   = array();
                $versions = array();

                if ($pdata['vendor'] != '')
                {
                    try
                    {
                        $models = Software::get_models_by_vendor($pdata['vendor']);
                
                    }
                    catch(Exception $e)
                    {
                        Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
                    }
                
                }
                
                if ($pdata['model'] != '')
                {
                    try
                    {
                        $versions = Software::get_versions_by_model($pdata['vendor'].':'.$pdata['model']);
                    }
                    catch(Exception $e)
                    {
                        Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
                    }
                }
                
                $plugin_list[$asset_id][] = array(
                    'vendor'       => $pdata['vendor'],
                    'model'        => $pdata['vendor'].':'.$pdata['model'],
                    'version'      => $pdata['vendor'].':'.$pdata['model'].':'.$pdata['version'],
                    'model_list'   => $models,
                    'version_list' => $versions
                );
            }
        }
    
        $total_plugins_per_sensor['local'] = array (
            'total' => count($plugin_list),
            'max_allowed' => $max_allowed,
            'max_available' => $max_available,
        );
        
        $device_list[$asset_id] = array(
            "name"         => $host['name'],
            "ips"          => Asset::format_to_print($host['ips']),
            "plugins"      => $plugin_list[$asset_id]
        );
    }
}
else
{
    $empty_msg = _('There are no network devices found. Return to the asset discovery step by clicking back to either discover or add network devices.');
}


/*  Subtitle Texts */
$subtitle_1 = '';
$subtitle_2 = '';

if ($total == 1)
{
    $subtitle_1 = _('During the asset discovery scan we found 1 network device on your network');
}
else
{
    $subtitle_1 = sprintf(_("During the asset discovery scan we found %s network devices on your network"), $total);
}

$subtitle_1 .= '. ' . _('Confirm the vendor, model, and version of the device shown. Click the "Enable" button to enable the data source plugin for each device.');


$subtitle_2 = _('Plugin(s) successfully configured. Configure each asset to send logs by clicking on the instructions provided. Once the asset is configured AlienVault should detect the incoming data. When AlienVault receives data for an asset the "Receiving Data" light will turn green. Click "Next" when you have received data from at least one asset.');

$subtitle_2_empty = _('You have not configured any plugin yet. In order to complete successfully the step, you need to activate at least one plugin in your network devices to start receiving data from it.');

?>

<script type='text/javascript'>
    
    var av_plugin_obj = false;
    
    function load_js_step()
    {
        av_plugin_obj = new AVplugin_select();
        
        <?php
        if ($total > 0)
        {
            foreach ($device_list as $d_id => $dev)
            {
                ?>
                var _options = <?php echo json_encode($dev['plugins']) ?>;
                var _total_plugins_per_sensor = <?php echo json_encode($total_plugins_per_sensor) ?>;
            
                av_plugin_obj.create('#table_<?php echo $d_id ?>', _options, _total_plugins_per_sensor)
                <?php
            }
        }
        ?>
        
        load_handler_step_log();
        

    }

</script>

<div id='step_log' class='step_container'>

    <div class='wizard_title'>
        <?php echo _('Set up Log Management') ?>
    </div>
    
    <div class='wizard_subtitle'>
        <span id='screen_1_subtitle'><?php echo $subtitle_1 ?></span>
        <span id='screen_2_subtitle'><?php echo $subtitle_2 ?></span>
        <span id='screen_2_subtitle_empty'><?php echo $subtitle_2_empty ?></span>
    </div>

    <?php
    if ($total > 0)
    {
    ?>
        <div id='net_devices_container'>
            
            <div id='first_screen'>
            
                <table id='net_devices_list' class='wizard_table table_data'>
                    <thead>
                    <tr>
                        <th><?php echo _('Asset') ?></th>
                        <th class='net_devices_col_box'><?php echo _('Vendor') ?></th>
                        <th class='net_devices_col_box'><?php echo _('Model') ?></th>
                        <th class='net_devices_col_box'><?php echo _('Version') ?></th>
                        <th class='net_devices_col_add'></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($device_list as $d_id => $dev)
                    {
                    ?>
                    <tr data-host="<?php echo $d_id ?>" data-name="<?php echo $dev['name'] ?>" data-ip="<?php echo $dev['ips'] ?>">
        
                        <td>
                            <!--<button class='add_plugin av_b_secondary'>+</button>-->
                            <div class='device_name'>
                                <?php echo $dev['name'].' ('.$dev['ips'].')' ?>
                            </div>
                            
                        </td>
        
                        <td colspan="4">
                            <table class='table_data table_plugin_list' data-asset_id="<?php echo $d_id ?>">
                                <tbody id='table_<?php echo $d_id ?>' class='plugin_list' data-host="<?php echo $d_id ?>"></tbody>
                            </table>
                        </td>
        
                    </tr>
                    <?php
                    }
                    ?>
                    </tbody>
                </table>
                
                <button id='w_apply' class='small'><?php echo _('Enable') ?></button>
                
                <div class='clear_layer'></div>
                
            </div>
            
            <div id='second_screen' style="display:none;">
            
                <table id='log_devices_list' class='wizard_table table_data'>
                    <thead>
                    <tr>
                        <th><?php echo _('Asset') ?></th>
                        <th><?php echo _('Type') ?></th>
                        <th><?php echo _('Plugin Enabled') ?></th>
                       <!-- <th><?php /*echo _('Receiving Data') */?></th>-->
                        <th><?php echo _('Instructions') ?></th>
                    </tr>
                    </thead>
                    
                    <tbody></tbody>
                    
                </table>
                
                <div class='clear_layer'></div>
            
            </div>
            
        </div>

    <?php
    }
    else
    {
    ?>
        <div id='empty_devices'>
            <?php echo $empty_msg ?>
        </div>
    <?php
    }
    ?>

</div>


<!-- THE BUTTONS HERE -->
<div class='wizard_button_list'>

    <a href='javascript:;' id='prev_step'   class='av_l_main'><?php echo _('Back') ?></a>
    <a href='javascript:;' id='prev_screen' class='av_l_main'><?php echo _('Back') ?></a>


    <button id='next_step' disabled class="fright"><?php echo _('Next') ?></button>
    <button id='next_step' class="fright av_b_secondary"><?php echo _('Skip this Step') ?></button>

</div>
