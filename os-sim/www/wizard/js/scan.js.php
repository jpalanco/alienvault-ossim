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

function scan_step_1()
{
    stop_scan_handler();
    
    activityBar();
    
    do_ping();
}


function scan_step_2()
{
    stop_scan_handler();

    progressBar(0, $('#progressbar'));
    
    check_scan_progress();
}


function scan_step_3()
{
    $("#scan_scheduler").select2(
    {
        allowClear: true,
        placeholder: "<?php echo _('Select a Schedule Option') ?>",
    });
        
    $('#finish_scan').on('click', function()
    {
        var sch_opt = $('#scan_scheduler').val();
        
        if (sch_opt == '')
        {
            close_scan_window(true);
        }
        else
        {
            schedule_scan(sch_opt);
        }        
    });
}


function scan_step_error()
{
    
    $('#cancel_scan').on('click', function()
    {
        close_scan_window(false);
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
    document.location.href = "scan.php?error=1";
}


function do_ping()
{
    var ctoken = Token.get_token("welcome_wizard");
    
    __ajax_request = $.ajax(
    {
    	url: __ajax_path + "scan_ajax.php?token="+ctoken,
    	data: {"action": "do_ping"},
    	type: "POST",
    	dataType: "json",
    	success: function(data)
    	{
        	if(__cancel_lock)
        	{
            	return false;
        	}
        	
        	if (check_critical_error(data))
        	{
            	js_error();
            	
            	return false;
        	}
        	
    	    if (data.error || data.data.finish)
    	    {
        	    document.location.reload();
    	    }
    	    else
    	    {
                __check_timeout = setTimeout("do_ping();", 5000);
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
            if(!__cancel_lock)
        	{
                js_error();
            }
    	}
    });
}


function check_scan_progress()
{
    
    var ctoken = Token.get_token("welcome_wizard");
    
    __ajax_request = $.ajax(
    {
    	url: __ajax_path + "scan_ajax.php?token="+ctoken,
    	data: {"action": "scan_progress"},
    	type: "POST",
    	dataType: "json",
    	success: function(data)
    	{
        	if(__cancel_lock)
        	{
            	return false;
        	}
        	
        	if (check_critical_error(data))
        	{
            	js_error();
            	
            	return false;
        	}
        	
    	    if (data.error || data.data.finish)
    	    {
        	    document.location.reload();
    	    }
    	    else
    	    {
            	var data = data.data;
            	
            	var percent = data.percent;
            	var time    = data.time;
            	var total   = data.total;
            	var current = data.current;
            	
            	progressBar(percent, $('#progressbar'));
            	
            	$('#progress_current').text(current);
            	$('#progress_total').text(total);
            	$('#progress_remaining').text(time);
            	
            	__check_timeout = setTimeout("check_scan_progress();", 5000);

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
            
            if(!__cancel_lock)
        	{
            	js_error();
        	}
            
    	}
    });
}


function close_scan_window(reload)
{
    var ctoken = Token.get_token("welcome_wizard");
    
    $.ajax(
    {
    	url: __ajax_path + "scan_ajax.php?token="+ctoken,
    	data: {"action": "cancel_scan"},
    	type: "POST",
    	dataType: "json",
    	success: function()
    	{
    	   if (reload)
    	   {
        	   parent.finish_scan();
    	   }
    	   else
    	   {
    	       parent.close_scan();
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
            
            if (reload)
            {
               parent.finish_scan();
            }
            else
            {
               parent.close_scan();
            }
    	}
    });

}


function stop_scan_handler()
{
    $('#cancel_scan').off('click');
    
    $('#cancel_scan').on('click', function()
    {
        var msg_stop = "<?php echo _('Stopping the scan now will discard all the assets discovered so far. Are you sure you want to continue?') ?>";
        
        if (confirm(msg_stop))
        {
            __cancel_lock = true;
            
            if(__ajax_request)
            {
                // Aborting previous request 
                __ajax_request.abort();
            }
            
            if(__check_timeout)
            {
                // clearing timeouts 
                clearTimeout(__check_timeout);
            }
                                
            close_scan_window(false);
        }
        
        return false;
        
    });
    
}


function schedule_scan(opt)
{
    var ctoken = Token.get_token("welcome_wizard");
    
    $.ajax(
    {
    	url: __ajax_path + "scan_ajax.php?token="+ctoken,
    	data: {"action": "schedule_scan", "data": {"sch_opt": opt}},
    	type: "POST",
    	dataType: "json",
    	success: function(data)
    	{
        	if (check_critical_error(data))
        	{
            	js_error();
            	
            	return false;
        	}
        	
        	if (data.error)
        	{
            	show_notification('scan_notif', data.msg, 'nf_error', 5000);
            	setTimeout(function()
                {
                    close_scan_window(true);
                    
                }, 2000);
        	}
        	else
        	{
            	close_scan_window(true);
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
            
            show_notification('scan_notif', errorThrown, 'nf_error', 5000);
            
            setTimeout(function()
            {
                close_scan_window(true);
                
            }, 2000);
    	}
    });

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
