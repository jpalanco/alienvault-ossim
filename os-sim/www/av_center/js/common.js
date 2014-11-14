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


/***********************************************************
*********************** Miscellaneous **********************
************************************************************/

function get_system_info(system_id)
{
    var ret = false;
    
    $.ajax({
        url: "data/sections/common/common_actions.php",
        global: false,
        type: "POST",
        data: "action=get_system_info&system_id="+system_id,
        dataType: "json",
        async:false,
        success: function(data){
            ret = data;
        }
    });    
    
    return ret;
}


function before_unload() 
{	
	var condition_1 = (typeof(section)            == 'undefined' || section == null);
	var condition_2 = (typeof(change_control)     == 'undefined' || change_control == null);
	var condition_3 = (typeof(ajax_validator)     == 'undefined' || ajax_validator == null);
	var condition_4	= (typeof(action_in_progress) == 'undefined' || action_in_progress == true);
			
	if ( condition_1 || condition_2 || condition_3 || condition_4){
		return false;
	}
	
	var validate_all = ajax_validator.validate_all_fields();
	
	if (typeof(validate_all) == 'object' && validate_all.status == 'OK')
	{
		var changes = change_control.get_changes();
		
		if (changes[0] > 0)
		{
			switch(section.current_section)
			{
				case 'cnf_general':
					General_cnf.save_cnf_sync('f_gc');
				break;
				
				case 'cnf_network':
					Network_cnf.save_cnf_sync('f_nc');
				break;
				
				case 'cnf_sensor':
					Sensor_cnf.save_cnf_sync('f_sc');
				break;
			}
						
			change_control = null;
		}
	}
}


function checkall(name)
{
    if ($('input[name="'+name+'"]').attr('checked'))
    { 
        $('input[type="checkbox"]').attr('checked', true);
    }
    else
    {
        $('input[type="checkbox"]').attr('checked', false);
    }
}

/*                
function get_chk_selected(){
    var size = $("input[type='checkbox']:checked").length;
    
    if ( size > 0 )
    {
        var selected = new Array();            
        $("input[type='checkbox']:checked").each(function (index) {
            
            var data = $(this).val().split("_");
            var id   = parseInt(data[0]);
            
            if ( !isNaN(id) )
                selected[selected.length] = id;         
        });
                             
        return selected;
    }
}
*/

function display_sec_errors(error_msg) 
{
	var config_nt = { content: error_msg, 
					  options: {
						  type:'nf_error',
					 	  cancel_button: false
					  },
					  style: 'width: 60%; margin: 200px auto; text-align:center;'
					};
					
	nt = new Notification('nt_1', config_nt);
	
	notification = nt.show();
	
	$('#avc_data').html(notification);
}


function toggle_tree()
{
    $("#avc_clcontainer").toggle();
    
    if ($("#avc_arrow").hasClass('arrow_bottom'))
    {        
        $('#avc_arrow').removeClass().addClass('arrow_top');
        $('#avc_cmcontainer').css('border-bottom', 'solid 1px rgba(150,150,150,0.2)');    
        
        $('#avc_cmcontainer').tipTip({maxWidth: 'auto', content: labels['hide_tree']});     
    }
    else
    {
        $('#avc_arrow').removeClass().addClass('arrow_bottom');
        $('#avc_cmcontainer').tipTip({maxWidth: 'auto', content: labels['show_tree']});
        
        $('#avc_cmcontainer').css('border-bottom', 'none');
    }
}


function show_system_down()
{
	if ($(".w_overlay").length < 1)
	{
		var height   = $.getDocHeight();
		$('#avc_data').css('position', 'relative')
		$('#avc_data').append('<div class="w_overlay" style="height:'+height+'px;"></div>');
		$(".w_overlay").addClass('opacity_7');
		
		var id_nt     = 'nt_cached';
		var config_nt = { content: labels['cached_info'], 
						  options: {
							type:'nf_error',
							cancel_button: false
						  },
						  style: 'padding: 5px 0px; width: 1180px; margin: 28px auto; text-align:center;'
						};
		
		nt            = new Notification(id_nt, config_nt);
		notification  = nt.show();
		
		$('#avc_data').before("<div id='panel_info'></div>");
		$('#panel_info').html(notification);
	}
}

function show_system_up()
{
	$('#avc_data').css('position', 'static')
	$('#panel_info').remove();
	$('.w_overlay').remove();
}


/***********************************************************
*********************** Progress Bar ***********************
************************************************************/

function Progress_bar(){} 

Progress_bar.create = function(id_wrapper, id, title, width, progress, style){
    var ret = $.ajax({
		url: "data/sections/common/common_actions.php",
		global: false,
		type: "POST",
		data: "action=create_pbar" + "&id=" + id + "&title=" + title + "&width=" + width + "&progress=" + progress + "&style=" + style,
		dataType: "text",
		async:false
		}
	).responseText;
    
    $(id_wrapper).html(ret);
   
};


Progress_bar.update = function(id, progress, duration){
    $('#'+ id + ' .ui-progress').animateProgress(progress, duration, function(){ $('#' + id + ' .value').html(progress + " %")});
};


Progress_bar.complete = function(id, callback, duration){
       
	$('#'+ id + ' .ui-progress').animateProgress(100, duration, function() {
		$('#' + id + ' .value').html("100 %");
		setTimeout(function(){callback()}, duration);
	});
};



/***********************************************************
****************** Vertical Progress Bar *******************
************************************************************/

function VProgress_bar(){} 

VProgress_bar.create = function(id_wrapper, id, title, width, height, progress, style){
	var ret = $.ajax({
		url: "data/sections/common/common_actions.php",
		global: false,
		type: "POST",
		data: "action=create_vpbar" + "&id=" + id + "&title=" + title + "&width=" + width + "&height=" + height + "&progress=" + progress + "&style=" + style,
		dataType: "text",
		async:false
		}
	).responseText;
	
	$(id_wrapper).html(ret);
   
};


VProgress_bar.update = function(id, progress, duration){
	$('#'+ id + ' .ui-vprogress-container').animateVProgress(progress, duration, function(){ $('#' + id + ' .vp-ui-title').html(progress + " %")});
};


VProgress_bar.complete = function(id, callback, duration){
	   
	setTimeout(function() {
		$('#'+ id + ' .ui-vprogress-container').animateVProgress(100, duration, function() {
			$('#' + id + ' .vp-ui-title').html("100 %");
			setTimeout(function(){callback()}, duration);
		});
	  }, 500);
};



/*******************************************************
********************** Messages ************************
********************************************************/


function Js_tooltip(){}

Js_tooltip.show = function(id, config){
		
	var maxWidth        = (typeof(config.maxWidth) == 'undefined') ? 'auto' : config.maxWidth;
		
	var defaultPosition = (typeof(config.defaultPosition) == 'undefined') ? 'top' : config.defaultPosition;
	var content         = (typeof(config.content) == 'undefined') ? '' : config.content;
						
	$(function(){
		$(id).tipTip({maxWidth: maxWidth, defaultPosition : defaultPosition, content: content});
	});
}

Js_tooltip.remove_all = function(){
	$('.tip_top').remove();
};
