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


// Check permissions
if (!Session::am_i_admin())
{
	 $config_nt = array(
		'content' => _("You do not have permission to see this section"),
		'options' => array (
			'type'          => 'nf_error',
			'cancel_button' => false
		),
		'style'   => 'width: 60%; margin: 30px auto; text-align:center;'
	);

	$nt = new Notification('nt_1', $config_nt);
	$nt->show();

	die();
}

$asset_id  = GET('asset_id');
$sensor_id = GET('sensor_id');

ossim_valid($asset_id,   OSS_HEX,                'illegal:' . _('Asset ID'));
ossim_valid($sensor_id,  OSS_HEX, OSS_NULLABLE,  'illegal:' . _('Sensor ID'));

if (ossim_error()) 
{
	die(ossim_error());
}

// Database Object
$db   = new ossim_db();
$conn = $db->connect();


//Getting the vendors
try
{
    $vendors  = Software::get_hardware_vendors($conn, TRUE);
}
catch(Exception $e)
{
    $vendors  = array();
    Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
}


$plugin_list = array();

try
{
    $related_sensors = Asset_host_sensors::get_sensors_by_id($conn, $asset_id);
    $selected_sensor = ($sensor_id != '') ? $sensor_id : key($related_sensors);
    
    $active_plugins  = Plugin::get_plugins_by_device( Util::uuid_format($selected_sensor) );
    
    $asset_plugins   = is_array($active_plugins[$asset_id]) ? $active_plugins[$asset_id] : array();
    
    foreach ($asset_plugins as $pdata)
    {        
        $model_list    = array();
        $version_list = array();
        
        list($vendor, $model, $version) = Plugin::translate_cpe_to_software($pdata['cpe']);

        if ($vendor != '')
        {
            try
            {
                $model_list = Software::get_models_by_cpe($conn, $vendor, TRUE);
        
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
                $version_list = Software::get_versions_by_cpe($conn, $model, TRUE);
            }
            catch(Exception $e)
            { 
                Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
            }
        }   
        
        $plugin_list[] = array(
            'vendor'       => $vendor,
            'model'        => $model,
            'version'      => $version,
            'model_list'   => $model_list,
            'version_list' => $version_list        
        );

    }
}
catch(Exception $e)
{
   Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
}

if (empty($plugin_list))
{
    $plugin_list[] = array(
        'vendor'       => '',
        'model'        => '',
        'version'      => '',
        'model_list'   => array(),
        'version_list' => array()        
    );
}

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
            array('src' => 'av_common.css?only_common=1&t='. Util::get_css_id(),    'def_path' => TRUE),
            array('src' => 'jquery-ui.css',                                         'def_path' => TRUE),
            array('src' => 'lightbox.css',                                          'def_path' => TRUE),
            array('src' => 'tipTip.css',                                            'def_path' => TRUE),
            array('src' => 'jquery.select.css',                                     'def_path' => TRUE),
            array('src' => 'assets/asset_details.css',                              'def_path' => TRUE)
            
        );

        Util::print_include_files($_files, 'css');


        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',             'def_path' => TRUE),
            array('src' => 'jquery-ui.min.js',          'def_path' => TRUE),
            array('src' => 'utils.js',                  'def_path' => TRUE),
            array('src' => 'notification.js',           'def_path' => TRUE),
            array('src' => 'token.js',                  'def_path' => TRUE),
            array('src' => 'jquery.tipTip.js',          'def_path' => TRUE),
            array('src' => 'jquery.select.js',          'def_path' => TRUE),
            array('src' => 'av_plugin_select.js.php',   'def_path' => TRUE)
        );

        Util::print_include_files($_files, 'js');

    ?>


	<script type="text/javascript">

    	var __vendor_list = <?php echo json_encode($vendors) ?>;
    	
    	function apply_plugin_callback(data)
    	{
        	if (typeof data != 'undefined' && data != null)
    		{
                if (data.error)
                {
                    $('#gb_b_apply').removeClass('av_b_processing');
                    show_notification('plugin_notif', data.msg, 'nf_error', 7500, true);
                }
                else
                {
                    if(typeof(top.frames['main'].force_reload) != 'undefined')
                    {
                        top.frames['main'].force_reload = 'plugins';
                    }

                    parent.GB_close();
                }
            }
            else
            {
                $('#gb_b_apply').removeClass('av_b_processing');
                show_notification('plugin_notif', "<?php echo _('An unexpected error happened. Try again later.') ?>", 'nf_error', 7500, true);
            }
    	}
    	
		$(document).ready(function()
		{

            $(document).on('change', '.select_plugin', function()
            {
                $('#gb_b_apply').prop("disabled", false);
            });

            $('#gb_b_apply').on('click', function()
            {
                if ($(this).hasClass('av_b_processing'))
                {
                    return false;
                }

                $(this).addClass('av_b_processing');

                av_apply_plugin(apply_plugin_callback);
                
            });
    
            
            $('#gb_b_cancel').on('click', function()
            {
                parent.GB_hide();
            });      
                        
            $('#add_plugin').on('click', function()
            {
                $('.plugin_list').AVplugin_select(
                {
                    "vendor_list": __vendor_list
                });
            });

            <?php
            foreach ($plugin_list as $p)
            {
                ?>
                $('.plugin_list').AVplugin_select(
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
            ?>
		});

  </script>

</head>

<body>

    <div id='plugin_notif'></div>
        
    <div id='plugin_container'>
        
        <div class='plugin_title'>
        <?php 
            echo _('Confirm the vendor, model and version of the device shown. Click the button to enable the data source plugin for this asset.');    
        ?>
        </div>

        <div class='related_sensor' style='<?php echo (count($related_sensors)<=1) ? 'display:none' : '' ?>'>
            <form onchange="this.submit()">
                <input type='hidden' name='asset_id' value='<?php echo $asset_id ?>'>
                <?php 
                    echo _('Related Sensors');    
                ?>
                <select name="sensor_id" id="default_sensor">
                    <?php
                        foreach ($related_sensors as $s_id => $s_data)
                        {
                            $sel = ($s_id == $selected_sensor) ? "selected" : "";
                            echo '<option value="'.$s_id.'" '.$sel.'>'.$s_data['name'].' ['.$s_data['ip'].']</option>';
                        }
                    ?>
                </select>
            </form>
        </div>

        <table id='net_devices_list' class='table_data'>
            <thead>
                <tr>
                    <th><?php echo _('Vendor') ?></th>
                    <th><?php echo _('Model') ?></th>
                    <th><?php echo _('Version') ?></th>
                    <!--<th><button id='add_plugin' class='av_b_secondary'>+</button></th>-->
                </tr>
            </thead>   
                     
            <tbody class='plugin_list' data-host="<?php echo $asset_id ?>"></tbody>
            
        </table>
        
        
        <div class='clear_layer'></div>
            
    </div>
   
    
    <div id='GB_action_buttons'>
        
        <button id='gb_b_cancel' class='av_b_secondary'>
            <?php echo _('Cancel') ?>
        </button>
        
        <button id='gb_b_apply' disabled>
            <?php echo _('Apply') ?>
        </button>
        
    </div>
    
</body>
</html>

<?php
$db->close();

/* End of file index.php */
/* Location: ./asset_details/index.php */
