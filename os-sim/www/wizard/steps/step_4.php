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
    throw new Exception('There was an unexpected error');
}

$system_list = Av_center::get_avc_list($conn);
$admin_ip    = @$system_list['data'][strtolower(Util::get_system_uuid())]['admin_ip'];

$table       = ', host_types ht, host_ip hip';
$f           = array();

$f['where']  = " host.id=ht.host_id AND ht.type=4 AND hip.host_id=host.id AND hip.ip!=inet6_pton('$admin_ip')";


try
{
    list($hosts, $total) = Asset_host::get_list($conn, $table, $f, FALSE);
    $active_plugins      = Plugin::get_plugins_by_device();    
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
        $vendors = Software::get_hardware_vendors($conn, TRUE);
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
                
        if (count($active_plugins[$asset_id]) < 1)
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
            foreach ($active_plugins[$asset_id] as $pdata)
            {        
                $models   = array();
                $versions = array();
                
                list($vendor, $model, $version) = Plugin::translate_cpe_to_software($pdata['cpe']);

                if ($vendor != '')
                {
                    try
                    {
                        $models = Software::get_models_by_cpe($conn, $vendor, TRUE);
                
                    }
                    catch(Exception $e)
                    { 
                        Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage()); 
                    }
                
                }
                
                if ($model != '')
                {
                    try
                    {
                        $versions = Software::get_versions_by_cpe($conn, $model, TRUE);
                    }
                    catch(Exception $e)
                    { 
                        Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
                    }
                }   
                
                $plugin_list[$asset_id][] = array(
                    'vendor'       => $vendor,
                    'model'        => $model,
                    'version'      => $version,
                    'model_list'   => $models,
                    'version_list' => $versions
                );                    
            }
        }
        
                
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


$subtitle_2 = _('Plugin(s) successfully configured. Configure each asset to send logs by clicking on the instructions provided. Once the asset is configured AlienVault should detect the incoming data. When AlienVault receives data for a asset the "Receiving Data" light will turn green. Click "Finish" when you have received data from at least one asset.');

?>

<script type='text/javascript'>
    
    var __vendor_list = <?php echo json_encode($vendors) ?>;
    
    
    function load_js_step()
    {
        
        <?php
        if ($total > 0)
        {
            foreach ($device_list as $d_id => $dev)
            {                
                foreach ($dev['plugins'] as $p)
                {
            ?>
                    $('#table_<?php echo $d_id ?>').AVplugin_select(
                    {
                        "vendor"       : "<?php echo $p['vendor'] ?>",
                        "model"        : "<?php echo $p['model'] ?>",
                        "version"      : "<?php echo $p['version'] ?>",
                        "vendor_list"  : __vendor_list,
                        "model_list"   : <?php echo json_encode($p['model_list']) ?>,
                        "version_list" : <?php echo json_encode($p['version_list']) ?>
                    });
            <?php
                }
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
                        <th><?php echo _('Vendor') ?></th>
                        <th><?php echo _('Model') ?></th>
                        <th><?php echo _('Version') ?></th>
                        <!--<th></th>-->
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
        
                        <td colspan="3">
                            <table class='table_data table_plugin_list'>
                                <tbody id='table_<?php echo $d_id ?>' class='plugin_list' data-host="<?php echo $d_id ?>"></tbody>
                            </table>
                        </td>
        
                    </tr>
                    <?php
                    }
                    ?>
                    </tbody>
                </table>
                
                <button id='w_apply' disabled class='small'><?php echo _('Enable') ?></button>
                
                <div class='clear_layer'></div>
                
            </div>
                   
            <div id='second_screen' style="display:none;">
            
                <table id='log_devices_list' class='wizard_table table_data'>
                    <thead>
                    <tr>
                        <th><?php echo _('Asset') ?></th>
                        <th><?php echo _('Type') ?></th>
                        <th><?php echo _('Plugin Enabled') ?></th>
                        <th><?php echo _('Receiving Data') ?></th>
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
