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

var __ajax_path = "<?php echo AV_MAIN_PATH . '/wizard/ajax/' ?>";
    
function deploy_step_1()
{
    $('#cancel_deploy').on('click', function()
    {
        close_deploy_window(); 
    });
    
    $('#start_deploy').on('click', function()
    {
        document.location.href = 'deploy.php?confirm=1';
        
    }); 
}


function deploy_step_2()
{ 
    progressBar(0, $('#progressbar'));
    
    $('#progress_legend').show();

    start_deploy();
    
    
    return false;
    
}


function deploy_step_3()
{          
    $('#finish_deploy').on('click', function()
    {
        parent.finish_deploy();
        
    });
}


function deploy_step_error()
{
    $('#cancel_deploy').on('click', function()
    {
        close_deploy_window(); 
    });
}


function check_critical_error(data)
{
    try
    {
        if (data.critical)
        {
            return true;
        }
    }
    catch(Err)
    {
        return true;
    }
    
    return false;
}


function js_error()
{
    document.location.href = "deploy.php?error=1";
}


function reload_step()
{
    document.location.href = "deploy.php";
}


function start_deploy()
{
    var ctoken = Token.get_token("welcome_wizard");
    
    $.ajax(
    {
    	url: __ajax_path + "deploy_ajax.php?token="+ctoken,
    	data: {"action": "deploy_agents"},
    	type: "POST",
    	dataType: "json",
    	beforeSend: function()
    	{
        	if (__os == 'linux')
        	{
            	$('#progress_legend').html("<?php echo _('Adding Agents') ?>");
        	}
        	else
        	{
            	$('#progress_legend').hide();
        	}
    	},
    	success: function(data)
    	{
        	if (check_critical_error(data))
        	{
            	js_error();
            	
            	return false;
        	}
        	
    	    if (data.error)
    	    {
                reload_step();

    	    }
    	    else
    	    {
            	
            	if (__os == 'linux')
            	{
                	progressBar(50, $('#progressbar'));
                	
                	$('#progress_legend').html("<?php echo _('Applying Agentless Configuration') ?>");
            	}
            	else
            	{
                	try
                	{
                    	var total_ips = data.data.total_ips
                	}
                	catch(Err)
                	{
                    	var total_ips = '-'
                	}
                	
                	$('#progress_legend').show();
                	$('#progress_current').text(total_ips);
            	}
            	
            	check_deploy_status();
            	
            }

    	},
    	error: function(XMLHttpRequest, textStatus, errorThrown) 
    	{	
            //Checking expired session
    		var session = new Session(XMLHttpRequest, '');
            if (session.check_session_expired() == true)
            {
                session.redirect();
                return;
            }
            
            js_error();
    	}
    });
}

function check_deploy_status()
{
    var ctoken = Token.get_token("welcome_wizard");
    
    $.ajax(
    {
    	url: __ajax_path + "deploy_ajax.php?token="+ctoken,
    	data: {"action": "check_deploy"},
    	type: "POST",
    	dataType: "json",
    	success: function(data)
    	{
        	if (check_critical_error(data))
        	{
            	js_error();
            	
            	return false;
        	}
        	
    	    if (data.error || data.data.finish)
    	    {
        	    progressBar(100, $('#progressbar'));
        	    
        	    reload_step();
    	    }
    	    else
    	    {
        	    
            	var percent   = data.data.percent;
            	var remaining = data.data.remaining;

            	$('#progress_current').text(remaining);

            	progressBar(percent, $('#progressbar'));
            	
            	setTimeout(function()
            	{
            	   check_deploy_status();
            	}, 5000);

            }

    	},
    	error: function(XMLHttpRequest, textStatus, errorThrown) 
    	{	
            //Checking expired session
    		var session = new Session(XMLHttpRequest, '');
            if (session.check_session_expired() == true)
            {
                session.redirect();
                return;
            }
            
            js_error();
    	}
    });
}


function close_deploy_window()
{
    var ctoken = Token.get_token("welcome_wizard");
    
    $.ajax(
    {
    	url: __ajax_path + "deploy_ajax.php?token="+ctoken,
    	data: {"action": "cancel_deploy"},
    	type: "POST",
    	dataType: "json",
    	success: function()
    	{
    	   parent.close_deploy();
    	   
    	},
    	error: function(XMLHttpRequest, textStatus, errorThrown) 
    	{	
            //Checking expired session
    		var session = new Session(XMLHttpRequest, '');
            if (session.check_session_expired() == true)
            {
                session.redirect();
                return;
            }
            
            parent.close_deploy();
    	}
    });

}


function progressBar(percent, element) 
{
	var progressBarWidth = percent * element.width() / 100;
	
	$('.stripes', element).animate({ width: progressBarWidth }, 400);
	$('.bar-label', element).text(percent + "%");
}