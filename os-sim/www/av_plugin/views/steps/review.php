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
require_once __DIR__.'/../../../asec/data/sections/dsa/section_partials_pb.php';


if (!Session::am_i_admin())
{
    $error = _("You do not have permission to see this section");
    
    Util::response_bad_request($error);
}

$file          = Av_plugin::get_wizard_data('file');
$vendor        = Av_plugin::get_wizard_data('vendor');
$model         = Av_plugin::get_wizard_data('model');
$version       = Av_plugin::get_wizard_data('version');
$vendor_valid  = Av_plugin::get_wizard_data('vendor_valid');
$model_valid   = Av_plugin::get_wizard_data('model_valid');
$version_valid = Av_plugin::get_wizard_data('version_valid');
$product_type  = Av_plugin::get_wizard_data('product_type');
$plugin_id     = Av_plugin::get_wizard_data('data_source');
$filename = "/custom_plugin_{$plugin_id}.cfg";
$db    = new ossim_db();
$conn  = $db->connect();
$sp = new SectionPartialsPB();
$number_of_events = Av_plugin::get_wizard_data('counter');

?>

<script>

var ajax_validator = false;


function load_js_step()
{
    $('#next_step').prop('disabled', true);
    $('#prev_step').hide();
}

function next_allowed()
{
    if ($('.review_fail').length == 0)
    {
        return true;
    }
    else
    {
        return false;
    }
}

var counter = 1;

function refresh() {
    $.post("/ossim/asec/data/sections/plugins_generated/actions.php",{"action":"show_file","path":"<?=base64_encode($filename)?>"},function(data) {
        var error = $("#error");
        data = $.parseJSON(data);
        if (data['status'] == 'OK') {
            if (data['need_overwrite'] == true) {
                $("#nt_s_error div").html('<?php echo sprintf(_('A plugin for "%s %s %s" already exists on the system. Press "Finish" to overwrite or “X” to cancel. Please be aware that you will lose all of your data from the existing plugin if you choose to overwrite.'),addslashes($vendor),addslashes($model),addslashes($version))?>');
            } else {
                $("#nt_s_error").hide();
            }
            $("#plugin_file").show().removeClass('review_fail').html("<?=$filename?>");
            $('#next_step').prop('disabled', false);
        } else {
            ++counter;
            if (counter === 10) {
                $("#nt_s_error").show();
            }
            setTimeout(refresh,500);
        }
    }).fail(function(xhr, status, error) {
        ++counter;
        if (counter === 10) {
            $("#nt_s_error").show();
        }
	setTimeout(refresh,500);
    });
}

$(document).ready(function() {
    setTimeout(refresh,500);
    $("#plugin_file").show();
});
</script>

<div class='step_container'>

    <div class='wizard_title'>
        <?php echo _('Review Plugin Info') ?>
    </div>
    
    <div class='wizard_subtitle'>
    </div>
	<div id='nt_s_error' style='display:none; width: 300px;
                                    font-family:Arial, Helvetica, sans-serif;
                                    font-size:12px;
                                    text-align: left;
                                    position: relative;   
                                    border: 1px solid;
                                    border-radius: 5px;
                                    -moz-border-radius: 5px;
                                    -webkit-border-radius: 5px;
                                    box-shadow: 5px 5px 5px rgba(0, 0, 0, 0.1);
                                    -webkit-box-shadow: 5px 5px rgba(0, 0, 0, 0.1);
                                    -moz-box-shadow: 5px 5px rgba(0, 0, 0, 0.1);color: #9F6000; background-color: #FEEFB3;width: 80%; margin: 20px auto; text-align: left;'>
                    <img src='/ossim/pixmaps/nf_warning.png' style='position: absolute; top: -11px; left: -11px'/> 
                    <div style='padding: 5px 5px 5px 25px;'>
                        <div class='nf_warning'><?= _("Error! File not found or not readable")?></div>
                    </div>   
         </div>
    <div class='container_review'>
        
        <div class='review_row'>
        
            <div class='review_item <?php if ($file == '') { ?>review_fail<?php } ?>'>
                <?php echo _('Plugin File') ?>
            </div>
            
            <div class='review_value review_fail' id='plugin_file'>
		<img src='/ossim/pixmaps/loading.gif' width='12'/>
	    </div>
            
        </div>
        
        <div class='review_row'>
        
            <div class='review_item <?php if ($vendor_valid == 'error' || $vendor == '') { ?>review_fail<?php } ?>'>
                <?php echo _('Vendor') ?>
            </div>
            
            <div class='review_value'>
                <?php echo Av_plugin::get_wizard_data('vendor') ?>
            </div>
            
        </div>
        
        <div class='review_row'>
        
            <div class='review_item <?php if ($model_valid == 'error' || $model == '') { ?>review_fail<?php } ?>'>
                <?php echo _('Model') ?>
            </div>
            
            <div class='review_value'>
                <?php echo Av_plugin::get_wizard_data('model') ?>
            </div>
            
        </div>
        
        <div class='review_row'>
        
            <div class='review_item <?php if ($version_valid == 'error') { ?>review_fail<?php } ?>'>
                <?php echo _('Version') ?>
            </div>
            
            <div class='review_value'>
                <?php echo Av_plugin::get_wizard_data('version') ?>
            </div>
            
        </div>


        <div class='review_row'>

            <div class='review_item  <?php if (!$product_type) { ?>review_fail<?php } ?>'>
                <?php echo _('Product Type') ?>
            </div>

            <div class='review_value'>
                <?php echo Product_type::get_name_by_id($conn,$product_type); ?>
            </div>

        </div>
        <div class='review_row'>

            <div class='review_item <?php if ($number_of_events == 0) { ?>review_fail<?php } ?>'>
                <?php echo _('Number of Event Types') ?>
            </div>

            <div class='review_value'>
                <?php echo $number_of_events; ?>
            </div>

        </div>

    </div>

</div>

