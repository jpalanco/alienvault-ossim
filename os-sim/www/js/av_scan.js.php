<?php
header("Content-type: text/javascript");

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
?>

function show_info(type, subtype)
{         		
    var info = new Array();
        info["scan_mode"]       = new Array()
        info["timing_template"] = new Array();
    
      	info["scan_mode"]["fast"] =	"<?php echo '<strong>'._('Fast mode').'</strong> '._('will scan fewer ports than the default scan');?>";
      	info["scan_mode"]["full"] = "<?php echo '<strong>'._('Full mode').'</strong> '._('will be much slower but will include OS, services, service versions and MAC address into the inventory');?>"          
        
    
      	info["timing_template"]["-T0"] = "<?php echo '<strong>'._('Paranoid').'</strong> '._('mode is for IDS evasion');?>";
      	info["timing_template"]["-T1"] = "<?php echo '<strong>'._('Sneaky').'</strong> '._('mode is for IDS evasion');?>";
      	info["timing_template"]["-T4"] = "<?php echo '<strong>'._('Aggressive').'</strong> '._('mode speed up the scan (fast and reliable networks)');?>";
      	info["timing_template"]["-T5"] = "<?php echo '<strong>'._('Insane').'</strong> '._('mode speed up the scan (fast and reliable networks)');?>";
        	 		      		        	
    
    var show_in_tooltip = ($('.img_help_info').length > 0) ? true : false;
        
    if (typeof(info[type]) != 'undefined' && typeof(info[type][subtype]) != 'undefined')
    {                                                        
        if (show_in_tooltip == true)
        {        
            $('#'+type+'_info img').show();
            $('#'+type+'_info img').tipTip({content: info[type][subtype]});
        }
        else
        {           
            $('#'+type+'_info').html('<span class="small">'+info[type][subtype]+'</span>');
        }
    }
    else
    {                
        if (show_in_tooltip == true)
        { 
            $('#'+type+'_info img').hide();
        }
        else
        {
            $('#'+type+'_info').empty();
        }
    } 
}


function change_scan_mode() 
{ 				
    var value = $('#scan_mode').val();
    	
    if (value == "custom") 
    {
    	$('#tr_cp').show();
    } 
    else 
    {
        $('#tr_cp').hide();
    	$('#custom_ports').val('1-65535');
    }
    
    if(value == 'ping') 
    { 
        // Ping scan doesn't work with "Autodetect services and Operating System" option.
        $("#autodetect").prop('checked', false);
    }       
}


function bind_nmap_actions()
{
    // Ping scan doesn't work with "Autodetect services and Operating System" option. Force Fast scan
    $("#autodetect").on("click",  function(event)
    {
        if($("#autodetect").is(":checked") && $('#scan_mode').val() == 'ping') 
        {
            $('#scan_mode').val('fast');
        }
    });		
		
					
	$('#timing_template').on('change', function(){    				
		
		var t_value = $('#timing_template').val();
		
		show_info('timing_template', t_value);				
	});
	
	
	// Show and change scan information
	$('#scan_mode').on('change', function(){    				
		
		var s_value = $('#scan_mode').val();
		
		show_info('scan_mode', s_value);	
		
		change_scan_mode(); 				
	});
	
	
	//Custom ports
	$("#custom_ports").click(function() {
	    $("#custom_ports").removeClass('greyfont');
    });
    
	$("#custom_ports").blur(function() {
	    $("#custom_ports").addClass('greyfont');
    });

		
	//Tooltips			
	$(".info").tipTip({maxWidth: 'auto'});
	
	
	$('#scan_mode').trigger('change');
				
	$('#timing_template').trigger('change');
}


//Scan host locally with 
function scan_host(id)
{
    var url = '<?php echo Menu::get_menu_url("../netscan/index.php", 'environment', 'assets', 'asset_discovery')?>';
                
    var form = $('<form id="f_local_scan" action="' + url + '" method="POST">' +                            
        '<input type="hidden" name="action" value="custom_scan"/>' +
        '<input type="hidden" name="host_id" value="'+id+'"/>' +
        '<input type="hidden" name="sensor" value="local"/>' +
        '<input type="hidden" name="scan_mode" value="fast"/>' +
        '<input type="hidden" name="timing_template" value="-T5"/>' +
        '<input type="hidden" name="autodetected" value="1"/>' +
        '<input type="hidden" name="rdns" value="1"/>' +
        '</form>');
    
    $('body').append(form);
    
    $("#f_local_scan").submit();
}

// show fancybox to diplay the scan state

function show_state_box(state, message, progress)
{
    var box_content = '';
    var action      = null;
    var stop_div    = '<div class="box_single_button"><input type="button" id="stop_scan" class="small" onclick="stop_nmap()" value="<?php echo _('Stop Scan') ?>"></div>';
    
    
    if(state == 'launching_local_scan')
    {
        box_content = "<div class='box_title'></div>" +
                      "<div class='box_subtitle'><?php echo _('Launching local scan...')?></div>";
        action = 'insert';
    }
    else if ((state == 'local_search_in_progress' || state == 'remote_scan_in_progress') && $('#activitybar').length == 0)
    {
        var box_title = (state == 'local_search_in_progress') ? '<?php echo _('Searching Hosts') ?>' : '<?php echo _('Scanning Hosts') ?>';

        box_content = "<div class='box_title'>" + box_title + "</div>" +
                      "<div class='box_subtitle'>" + message +"</div>" +
                      "<div id='activitybar' class='av_activitybar'>" +
                           "<div class='stripes'></div>" +
                      "</div>";
        
        // we can stop the local search
        
        if (state == 'local_search_in_progress')
        {
            box_content = box_content + stop_div;
        }              
        
        action = 'insert';
     }
    else if (state =='local_scan_in_progress' && $('#progressbar').length == 0)
    {
        box_content = "<div class='box_title'><?php echo _('Scanning Hosts')?></div>" +
                      "<div class='box_subtitle'>" + message +"</div>" +
                      "<div id='progressbar' class='av_progressbar'>" +
                           "<div class='stripes'></div>" +
                              "<span class='bar-label'>" + progress.percent + "%</span>" +
                           "<div id='progress_legend'>" +
                           "<span id='progress_current'>" + progress.current + "</span>/<span id='progress_total'>" + progress.total +  "</span> <?php echo _('Hosts') ?>" +
                          " (<span id='progress_remaining'>" + progress.time + "</span>)" +
                          "</div>" +
                       "</div>" +
                       stop_div;
                  
        action = 'insert';                                     
    }
    else if (typeof(progress) != 'undefined' && progress !=null)
    {
        action = 'update';    
    }
    
    if (action == 'insert')
    {
        if($('#box-content').length == 0)
        {
    		$.fancybox({
            	'modal': true,
            	'width': 450,
            	'height': 205,
            	'autoDimensions': false,
            	'centerOnScroll': true,
            	'content': '<div id="box-content">' + box_content + '</div>',
            	'overlayOpacity': 0.07,
                'overlayColor': '#000'
            });
        }
        else
        {
            $('#box-content').html(box_content);
        }
        
        if (state == 'local_search_in_progress' || state == 'remote_scan_in_progress')
        {
            activityBar();
        }
        
    }
    else if (action == 'update')
    {
        $('#progress_current').html(progress.current);
        $('#progress_total').html(progress.total);
        $('#progress_remaining').html(progress.time);        
    }
    
    // update progress bar percent
    
    if (state == 'local_scan_in_progress')
    {
        progressBar(progress.percent, $('#progressbar'));
    }
}

var __width = 0;

function activityBar()
{
    var activityBarWidth = 20 * $('#activitybar').width() / 100;
    __width = $('#activitybar').width() - activityBarWidth;
	$('.stripes', $('#activitybar')).animate({ width: activityBarWidth }, 400);
    animate_right($('.stripes', $('#activitybar')));
}

function animate_right(elem)
{
    $(elem).animate({opacity:1},{
        duration: 1000,
        step:function(now, fn)
        {
            fn.start = 1;
            fn.end = __width;
            
            $(elem).css({'left':now});
        },
        complete: function()
        {
            animate_left(elem);
        }
    });
}

function animate_left(elem)
{
    $(elem).animate({opacity:1},{
        duration: 1000,
        step:function(now, fn)
        {
            fn.start = __width;
            fn.end = 1;
            
            $(elem).css({'left':now});
        },
        complete: function()
        {
            animate_right(elem);
        }
    });
}

function progressBar(percent, element) 
{
	var progressBarWidth = percent * element.width() / 100;
	
	$('.stripes', element).animate({ width: progressBarWidth }, 400);
	$('.bar-label', element).text(percent + "%");
}
