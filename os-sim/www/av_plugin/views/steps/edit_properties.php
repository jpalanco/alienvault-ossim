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

if (!Session::am_i_admin())
{
    $error = _("You do not have permission to see this section");
    
    Util::response_bad_request($error);
}

$vendor  = Av_plugin::get_wizard_data('vendor');
$model   = Av_plugin::get_wizard_data('model');
$version = Av_plugin::get_wizard_data('version');
$product_type = Av_plugin::get_wizard_data('product_type');
$db    = new ossim_db();
$conn  = $db->connect();
$product_types = Product_type::get_list($conn);
?>

<script>

var ajax_validator = false;
function refresh() {};

function checkMandatoryPB() {
    if ($('#vendor').val() != '' && $('#model').val() != '' && $('#product_type :selected').val() != '')
    {
        $('#next_step').prop('disabled', false);
    }
    else
    {
        $('#next_step').prop('disabled', true);
	var sstep = $('#wizard_path_container #3,#4');
        sstep.find('.step_name.av_link').addClass('av_l_disabled');
        sstep.find('.wizard_number').removeClass('s_visited');
    }
}

function load_js_step()
{
    /****************************************************
     ************ Ajax Validator Configuration **********
     ****************************************************/
    ajax_validator = {
        check_form: function() {}
    };
    $('#vendor, #model, #product_type').on('input', checkMandatoryPB);
    checkMandatoryPB();
}

function next_allowed()
{
    return ajax_validator.check_form() == true;
}


function overrideClick() {
    var btn = $("#next_step");
    var events = btn.data("events");
    if (events == undefined) {
        setTimeout(overrideClick,500);
        return;
    }
    var click = events.click[0].handler;
    ajax_validator.check_form = function() {return false;};
    btn.off("click").click(function() {
        var ob = $('#cpe_form').serialize();
        ob += "&ajax_validation_all=true";
	$.post('<?php echo AV_MAIN_PATH . '/av_plugin/controllers/properties_validation.php' ?>',ob, function(data) {
	data = $.parseJSON(data);
        var error = $("#nt_s_error").show();
        if (data.status == 'error') {
            error = error.find('.nf_error');
            error.html('');
            for (i in data.data) {
                error.append("<div>"+data.data[i]+"</div>");
            }
            return;
        }
        error.hide();
        var ds = $("#vendor").val()+" "+$("#model").val();
        var url = "/ossim/asec/data/sections/dsa/actions.php";
        $.post(url,{"name" : ds, "action": "create_datasource"},function(data) {
              var data = $.parseJSON(data)['data'];
              if ($.isNumeric(data)) {
                  ajax_validator.check_form = function() {return true;};
                  click();
              }
        });
        });
    });
}


$(document).ready(function() {
    refresh();
    overrideClick();
});


</script>

<div class='step_container'>

    <div class='wizard_title'>
        <?php echo _('Plugin Properties') ?>
    </div>
    
    <div class='wizard_subtitle'>
        <?php echo _('Step 2: Classify your plugin by adding properties below.') ?>
        <div id='nt_s_error' class="hidden" style='width: 300px;
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
                    <img src='/ossim/pixmaps/nf_error.png' style='position: absolute; top: -11px; left: -11px'/>
                    <div style='padding: 5px 5px 5px 25px; color: #D8000C; background-color: #FFBABA;'>
                        <div class='nf_error'></div>
                    </div>
         </div>
    </div>
    
    <div class='properties_form'>
        
        <form id='cpe_form'>
        
        <div class='form_row'>
            <div class='form_label'>Vendor*</div>
            <div class='form_input'><input type='text' name='vendor' id='vendor' class='vfield' value='<?php echo $vendor ?>' /></div>
        </div>
        
        <div class='form_row'>
            <div class='form_label'>Model*</div>
            <div class='form_input'><input type='text' name='model' id='model' class='vfield' value='<?php echo $model ?>' /></div>
        </div>
        
        <div class='form_row'>
            <div class='form_label'>Version</div>
            <div class='form_input'><input type='text' name='version' id='version' class='vfield' value='<?php echo $version ?>' /></div>
        </div>
        

        <div class='form_row'>
            <div class='form_label'><?=_("Product Type")?>*</div>
	    <div class='form_input'>
		<select id="product_type" name="product_type" class='vfield'>
			<option value=""><?=_("Select Product Type")?></option>
			<?php foreach ($product_types as $v) {?>
				<option value="<?=$v->id?>" <?= $v->id == $product_type ? 'selected="selected"' : ""?>><?=$v->name?></option>
			<?php } ?>
		</select>
        </div>

        </form>
        
    </div>

</div>

