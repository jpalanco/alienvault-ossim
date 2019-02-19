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

$asset_id   = GET('asset_id');
$asset_type = GET('asset_type');
$sensor_id  = GET('sensor_id');

ossim_valid($asset_id,   OSS_HEX,                'illegal:' . _('Asset ID'));
ossim_valid($asset_type, OSS_LETTER,             'illegal:' . _('Asset Type'));
ossim_valid($sensor_id,  OSS_HEX, OSS_NULLABLE,  'illegal:' . _('Sensor ID'));

if (ossim_error()) 
{
    die(ossim_error());
}

// Database Object
$db   = new ossim_db();
$conn = $db->connect();


// Check Asset Type
$asset_types = array(
    'asset'   => 'Asset_host',
    'network' => 'Asset_net',
    'group'   => 'Asset_group'
);


$not_allowed = FALSE;


//Getting the asset info
try
{
    if (isset($_GET['asset_id']) && isset($_GET['asset_type']))
    {
        if (!array_key_exists($asset_type, $asset_types))
        {            
            Av_exception::throw_error(Av_exception::USER_ERROR, _('Error! Invalid Asset Type'));
        }

        $class_name = $asset_types[$_GET['asset_type']];

        // Check Asset Permission
        if (method_exists($class_name, 'is_allowed') && !$class_name::is_allowed($conn, $asset_id))
        {
            Av_exception::throw_error(Av_exception::USER_ERROR, _('Error! Asset is not allowed'));
        }        
        
        $asset_object = $class_name::get_object($conn, $asset_id);
        
        
        
        // Check if it is the local host
        if ($asset_type == 'asset')
        {
            $local_assets = Asset_host::get_asset_by_system($conn, Util::get_system_uuid());
        }
        else
        {
            $local_assets = array();
        }
        
        if ($local_assets[$asset_id])
        {
            $not_allowed = TRUE;
            $allowed_msg = _('Local asset is not allowed to be configured here');
        }
        else
        {
            if ($asset_type == 'group')
            {
                $related_sensors = $asset_object->get_sensors($conn);
            }
            else
            {
                $related_sensors = $asset_object->get_sensors()->get_sensors();
            }
            
            $selected_sensor = ($sensor_id != '') ? $sensor_id : key($related_sensors);
            
            if (empty($related_sensors) && $selected_sensor == '')
            {
                $not_allowed = TRUE;
                $allowed_msg = _("No available sensors found for this").' '.$asset_type;
            }
        }
        
    }
    else
    {
        Av_exception::throw_error(Av_exception::USER_ERROR, _('Error retrieving asset information'));
    }
    
    
}
catch(Exception $e)
{
    Av_exception::write_log(Av_exception::USER_ERROR, $e->getMessage());
}


// Check available sensors
if ($not_allowed)
{
    $config_nt = array(
            'content' => $allowed_msg,
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
            array('src' => 'jquery.dataTables.css',                                 'def_path' => TRUE),
            array('src' => 'assets/asset_details.css',                              'def_path' => TRUE)
            
        );

        Util::print_include_files($_files, 'css');


        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                                    'def_path' => TRUE),
            array('src' => 'jquery-ui.min.js',                                 'def_path' => TRUE),
            array('src' => 'utils.js',                                         'def_path' => TRUE),
            array('src' => 'notification.js',                                  'def_path' => TRUE),
            array('src' => 'token.js',                                         'def_path' => TRUE),
            array('src' => 'jquery.tipTip.js',                                 'def_path' => TRUE),
            array('src' => 'jquery.select.js',                                 'def_path' => TRUE),
            array('src' => 'jquery.dataTables.js',                             'def_path' => TRUE),
            array('src' => 'av_plugin_select.js.php?sensor='.$selected_sensor, 'def_path' => TRUE),
            array('src' => '/av_asset/common/js/asset_plugin_list.js.php',     'def_path' => FALSE),
        );

        Util::print_include_files($_files, 'js');

    ?>

    <script type="text/javascript">

    /* This function saves the changes and closes de lightbox
     *
     * @param   data    Response from selectors jQuery plugin to know if there was some error
     */
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
            show_notification('plugin_notif', "<?php echo _('Sorry, operation was not completed due to an error when processing the request. Try again later.') ?>", 'nf_error', 7500, true);
        }
    }



    /*************** Initialization ******************/
    $(document).ready(function()
    {

        // DataTable for asset plugin list
        var __p_config = {
            'maxrows'    : 8,
            'edit_mode'  : 1,
            'sensor_id'  : '<?php echo $selected_sensor ?>',
            'asset_data' : {
                "asset_id"   : '<?php echo $asset_id ?>',
                "asset_type" : '<?php echo $asset_type ?>'
            }
        }

        var av_plugin_list = new Av_plugin_list(__p_config);
        av_plugin_list.draw();

        
        // Button handlers
        $(document).on('change', '.select_plugin', function()
        {
            $('#gb_b_apply').prop("disabled", false);
        });

        $(document).on('click', '.select2-remove-button', function()
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

            av_plugin_list.apply_changes(apply_plugin_callback);
            
        });
        
        $('#gb_b_cancel').on('click', function()
        {
            parent.GB_hide();
        }); 


        // Sensor selector
        var _prev_sensor = false;

        $('#default_sensor').on('focus', function ()
        {
            _prev_sensor = this.value;

        }).on('change', function()
        {
            // Some changes are made for the selected sensor: Must Apply first
            if ($('#gb_b_apply').prop("disabled") == false)
            {
                var _cur_sensor = this.value;
                this.value = _prev_sensor;
                var keys = {"yes": "<?php echo Util::js_entities(_('Yes')) ?>","no": "<?php echo Util::js_entities(_('No')) ?>"};
                var msg  = "<?php echo _('The changes made to the selected sensor will be lost. Are you sure to change the sensor?') ?>";
                
                av_confirm(msg, keys).done(function()
                {
                    $('#default_sensor').val(_cur_sensor);
                    presaved_data = {};
                    $('#gb_b_apply').prop("disabled", true);
                    av_plugin_list.change_sensor($('#default_sensor').val());
                });
            }
            else
            {
                av_plugin_list.change_sensor($('#default_sensor').val());
            }
        });
    });

  </script>

</head>

<body>

    <div id='plugin_notif'></div>
        
    <div id='plugin_container'>
        <div><?php echo _("Please select your device from the list below. </br>Note: Some plugins do not require you to select a version.")?></div>

        <div class='related_sensor' style='<?php echo (count($related_sensors)<=1) ? 'display:none' : '' ?>'>
        <form>
        <?php
        if (count($related_sensors)<=1)
        {
        ?>
            <input type='hidden' name='sensor_id' id='default_sensor' value='<?php echo $selected_sensor ?>'>
        <?php
        }
        else
        {
            echo _('Related Sensors');    
            ?>
            <select name="sensor_id" id="default_sensor">
                <?php
                    foreach ($related_sensors as $s_id => $s_data)
                    {
                        $_name = $s_data['name'].' ['.$s_data['ip'].']';
                        $_sel  = ($s_id == $selected_sensor) ? "selected" : "";
                        
                        echo '<option value="'.$s_id.'" '.$_sel.'>'.$_name.'</option>';
                    }
                ?>
            </select>
        <?php
        }
        ?>
        </form>
        </div>

        <table id='plugin_dataTable' class='table_data'>
            <thead>
                <tr>
                    <th><?php echo _('Asset') ?></th>
                    <th><?php echo _('Vendor') ?></th>
                    <th><?php echo _('Model') ?></th>
                    <th><?php echo _('Version') ?></th>
                    <th class='plugin_column_add'></th>
                </tr>
            </thead>   
                     
            
            
        </table>
        
        
        <div class='clear_layer'></div>
            
    </div>
   
    
    <div id='GB_action_buttons'>
        
        <button id='gb_b_cancel' class='av_b_secondary'>
            <?php echo _('Cancel') ?>
        </button>
        
        <button id='gb_b_apply' disabled>
            <?php echo _('Save') ?>
        </button>
        
    </div>
    
</body>
</html>

<?php
$db->close();

/* End of file edit_plugins.php */
/* Location: ./av_asset/common/views/edit_plugins.php */
