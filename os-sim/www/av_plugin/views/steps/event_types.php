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
$filename = Av_plugin::get_wizard_data('fbase');
?>

<script>
var hash = "";

function refresh() {
    $.post("/ossim/asec/data/section.php",{"section_id":"dsa_light","extra_data":"<?=$filename;?>"},function(data) {
	if (hash != data) {
		hash = data;
	        $("#step3_content").html(data);
		$('#next_step').prop('disabled', !next_allowed());
	}
	setTimeout(refresh,3000);
    });
}

function next_allowed()
{
    return $('.td_dsa').length !== 0 && $('.td_dsa:contains(TBD)').length === 0;
}
function load_js_step() {
    if (!next_allowed()) {
        var sstep = $('#wizard_path_container #4');
        sstep.find('.step_name.av_link').addClass('av_l_disabled');
        sstep.find('.wizard_number').removeClass('s_visited');
    }
}


//override on button next using all handler
function overrideClick() {
    var btn = $("#next_step");
    var events = btn.data("events");
    if (events == undefined) {
        setTimeout(overrideClick,500);
        return;
    }
    var click = events.click[0].handler;
    btn.off("click").click(function() {
        var trs = $("#t_all_patterns tbody tr");
        var cnt = trs.length;
        var url = "/ossim/asec/data/sections/dsa/actions.php";
        $.post(url,{"counter" : cnt, "action": "set_number_of_events"});
        trs.each(function() {
            var id = $(this).attr("id").replace("row_p_pattern_","");
            $.post(url,{"pattern_id" : id, "action": "accept"},function(data) {
                cnt--;
                if (cnt == 0) {
                    var data = $.parseJSON(data);
                    sg_id = data.data.split("###")[1];
                    $.post(url,{"sg_id" : sg_id, "action": "get_status"},function(data) {
                        var file = prompt(asec_msg['real_log_path'],'<?=base64_decode(json_decode(base64_decode($filename))->filename)?>');
                        if (file) {
                            $.post(url,{"sg_id" : sg_id, "action": "set_real_path", "path": file},function(data) {
                                $.post(url,{"ids" : [sg_id], "action": "refresh_framework"},function(data) {
                                   click();
                                });
                            });
                        }
                    });
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
        <?php echo _('Event Types') ?>
    </div>
    
    <div class='wizard_subtitle'>
        <?php echo _('Step 3: Patterns were normalized for the following events. All events must be categorized for the SIEM using the "Edit" icon. Event properties can also be reviewed and adjusted if necessary. Any event that you would like for Plugin Builder to ignore should be deleted using the "Trash" icon before creating the plugin.') ?>
    </div>
    
    <div id='step3_content'>
    </div>
    
    <div id='validate_notif'></div>

</div>

