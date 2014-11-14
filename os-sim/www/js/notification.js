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


function Notification(new_wrapper_id, new_config) 
{	
	var config        = '';
	var wrapper_id    = '';
						
	var wrapper_style = 'width: 300px;' +
						'font-family:Arial, Helvetica, sans-serif;' + 
						'font-size:12px;' +
						'text-align: left;' +
						'position: relative;' +   
						'border: 1px solid;' +
						'border-radius: 5px;' +
						'-moz-border-radius: 5px;' +
						'-webkit-border-radius: 5px;' +
						'box-shadow: 5px 5px 5px rgba(0, 0, 0, 0.1);' +
						'-webkit-box-shadow: 5px 5px rgba(0, 0, 0, 0.1);' +
						'-moz-box-shadow: 5px 5px rgba(0, 0, 0, 0.1);';
	
	function set_c(new_config)
	{
		config = new_config;
		
		if (typeof(config.content) == 'undefined')
		{
			config.content= "";
		}
		
		if (typeof(config.style) == 'undefined')
		{
			config.style= "";
		}
		
		if (typeof(config.options.cancel_button) == 'undefined')
		{
			config.options.cancel_button = false;
		}
	};
	
	function set_wp_id(new_wrapper_id)
	{
		wrapper_id = (new_wrapper_id != '')  ? new_wrapper_id : "wrapper_nt";
	};
	
	this.get_wrapper_id = function() 
	{
		//console.log(wrapper_id);
		return wrapper_id;
	};
	
	this.get_config = function() 
	{
		//console.log(config);
		return config;
	};
			
	this.set_wrapper_id = function (new_wrapper_id) 
	{
		set_wp_id(new_wrapper_id);
	};
	
	this.set_config = function (new_config) 
	{
		set_c(new_config);
	};
	
					
	this.hide = function()
	{
		$("#"+wrapper_id).hide();
	};
	
	this.remove = function()
	{
		$("#"+wrapper_id).remove();
	};
	
	this.fade_out = function(duration, easing, callback)
	{
		$("#"+wrapper_id).fadeOut(duration, easing, callback);
	};
	
	this.fade_in = function(duration, easing, callback)
	{
		$("#"+wrapper_id).fadeIn(duration, easing, callback);
	};
	
	this.show = function()
	{
		var nf_style = wrapper_style;
		var img      = 'nf_error.png';
		 
		switch (config.options.type){
				
            case 'nf_error':
                nf_style += 'color: #D8000C; background-color: #FFBABA;';
                img       = 'nf_error.png';
            break;
            
            case 'nf_info':
                nf_style += 'color: #00529B; background-color: #BDE5F8;';
                img       = 'nf_info.png';
            break;
            
            case 'nf_success':
                nf_style += 'color: #4F8A10; background-color: #DFF2BF;';
                img       = 'nf_success.png';
            break;
            
            case 'nf_warning':
                nf_style += 'color: #9F6000; background-color: #FEEFB3;';
                img       = 'nf_warning.png';
            break;
            
            default:
                nf_style += 'color: #D8000C; background-color: #FFBABA;';
                img       = 'nf_error.png';
		} 
		
		nf_style += config.style;
		
		var cancel_button = '';
		var c_pad         = 'padding: 5px 5px 5px 25px;';
		
		if (config.options.cancel_button == true)
		{
			cancel_button = "<a onclick=\"$('#"+wrapper_id+"').remove()\"><img src='/ossim/pixmaps/nf_cross.png' style='position: absolute; top: 0px; right: 0px; cursor:pointer;'/></a>";
			c_pad         = 'padding: 8px 12px 8px 18px;';
		}    
		
		var html =  "<div id='"+wrapper_id+"' style='"+ nf_style+ "'>"
                       	+ "<img src='/ossim/pixmaps/"+img+"' style='position: absolute; top: -11px; left: -11px'/>"
                        + "<div style='"+c_pad+"'>"                        
						+ "<div class='"+config.options.type+"'>" + config.content + "</div>"
                        + "</div>"
						+ cancel_button +
					"</div>";	
		
		return html;
	};
	
	set_c(new_config);
	set_wp_id(new_wrapper_id);
};

// This function creates a temporary floating message
function notify(msg_text, msg_type, b_cancel) 
{	
	if (typeof b_cancel == 'undefined' && b_cancel != true)
	{
        b_cancel = false	
	}
	
	var config_nt = { content: msg_text, 
					  options: {
						 type: msg_type,  
						 cancel_button: b_cancel
					  },
					  style: 'text-align:center; width:100%; margin: 15px auto 0px auto;'
					};
				
    nt  = new Notification('nt_short', config_nt);
    
    var notification = nt.show();
		
	if ($('#av_msg_info').length >= 1)
	{
		$('#av_msg_info').html(notification);
	}
    else
	{
		var content = '<div id="av_msg_container" style="margin: auto; position: relative; height: 1px; width: 380px;">' + 
					      '<div id="av_msg_info" style="position:absolute; z-index:999; left: 0px; right: 0px; top: 0px; width:100%;">' + 
					      notification +
				      '</div>';
		
		$('body').prepend(content);
	}
   
   setTimeout('nt.fade_out(1000);', 10000); //Delete at 10 seconds
}


function show_notification(id, msg, type, fade, cancel, style)
{
	if(typeof(id) == 'undefinded')
	{
		return false;
	}
	
	if(typeof(fade) == 'undefinded' || fade == null)
	{
		fade = 0;
	}

	if(typeof(cancel) == 'undefinded' || cancel == null )
	{
		cancel = false;
	}

	if(typeof(style) == 'undefinded' || style == null )
	{
		style = 'width: 60%;text-align:center;margin:0 auto;';
	}
			
	var config_nt = 
	{ 
		content: msg, 
		options: 
		{
			type: type,
			cancel_button: cancel
		},
		style: style
	};

	nt = new Notification('nt_'+id,config_nt);

	$('#'+id).html(nt.show());
	
	if(fade > 0)
	{
		$('#nt_'+id).fadeIn(1000).delay(fade).fadeOut(2000);
	}
}
	