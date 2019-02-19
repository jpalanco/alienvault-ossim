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


/*  Function to load the window to select the path  */
function start_new_wizard()
{
    $('#wizard_step').empty();

    $('#wizard_loading').hide();

    $("#start_wizard").fancybox(
    {
    	'width': 655,
    	'height': 475,
    	'padding': 20,
    	'marging': 0,
    	'autoDimensions': true,
    	'centerOnScroll': true,
    	'hideOnOverlayClick': false,
    	'showCloseButton': false,
    })

    $('#start_wizard').trigger('click');
}


/*  Function to load the window to finish the wizard when there are no alarms in the system  */
function finish_wizard()
{
    $('#wizard_step').empty();

    $('#wizard_loading').hide();

    $("#finish_wizard").fancybox(
    {
    	'width': 500,
        'height': 300,
    	'padding': 0,
    	'marging': 0,
    	'autoDimensions': false,
    	'centerOnScroll': true,
    	'hideOnOverlayClick': false,
    	'showCloseButton': false,
    })

    $('#finish_wizard').trigger('click');
}


/*  Function to resume the wizard  */
function resume_wizard(step, actived)
{
    change_selected_step_option(step, actived);

    //Load the wizard content (Main Content)
    load_wizard_step();
}


/*  Function to load the content of the current wizard step  */
function load_wizard_step()
{
    var ctoken = Token.get_token("welcome_wizard");

	$.ajax(
	{
		url: __ajax_path + "step_loader.php?token="+ctoken,
		data: {},
		type: "POST",
		dataType: "html",
		beforeSend: function()
		{
    		//Cleaning the current content before load the new step content
    		$('#wixard_step').empty();
		},
		success: function(data)
		{
    		//Adding the content
		    $('#wizard_step').html(data);
		    //Hiding the loading message
            $('#wizard_loading').hide();

            //Adjusting the height of the container
            adjust_container_height();

            //loading the javascript functions from the step
            if (typeof load_js_step == 'function')
            {
                //This function is defined inside each step file and it is loaded by this ajax call
                load_js_step();
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
            //Hidding the loading to show the error notification
            $('#wizard_loading').hide();
            show_notification('wizard_notif', errorThrown, 'nf_error', 5000);
		}
	});
}


/*  Function to load the next step  */
function next_step()
{
    var step = __current_step + 1;

    change_step(step)
}


/*  Function to load the content of the previous wizard step  */
function prev_step()
{
    var step = __current_step - 1;

    change_step(step);
}



/*  Function to load the content of the previous wizard step  */
function change_step(step)
{
    //Clearing all the possible timeouts from the current step
    clearTimeout(__timeout);

    var ctoken = Token.get_token("welcome_wizard");

	$.ajax(
	{
		url: __ajax_path + "wizard_ajax.php?token="+ctoken,
		data: {"action": "change_step", "data": {"step": step}},
		type: "POST",
		dataType: "json",
		beforeSend: function()
		{
    		//Cleaning notifs and showing the loading spinner
            $('#wizard_notif').empty();
            $('#wizard_loading').show();
		},
		success: function(data)
		{
    		//Checking errors
    		if (data.error == true)
    		{
        		$('#wizard_loading').hide();
        		show_notification('wizard_notif', data.msg, 'nf_error', 5000);
    		}
    		else
    		{
        		//Getting the new step and the status
        		var finish     = data.data.finish;
        		var completed  = data.data.completed;
        		var step       = data.data.step;

        		__current_step = step;

        		//If the status is finish, the wizard is over and we go to alarms
        		if (finish)
        		{
            		finish_wizard();
        		}
        		else //Otherwise we load the next step
        		{
            		//Selecting as active the new step in the left menu
            		change_selected_step_option(step, completed);

            		//Loading the content of the step
            		load_wizard_step();
        		}

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

            show_notification('wizard_notif', errorThrown, 'nf_error', 5000);
		}
	});
}


/*  Function to exit the wizard  */
function exit_wizard(exit, see_alarms)
{
    var ctoken = Token.get_token("welcome_wizard");

	$.ajax(
	{
		url: __ajax_path + "wizard_ajax.php?token="+ctoken,
		data: {"action": "exit_wizard", "data": {"exit": exit}},
		type: "POST",
		dataType: "json",
		success: function(data)
		{
    		if (typeof see_alarms != 'undefined' && see_alarms == true)
    		{
        		//Go to alarms.
        		document.location.href= '/ossim/#analysis/alarms/alarms';
    		}
    		else
    		{
        		//Go to dashboard by default.
        		document.location.href='/ossim/';
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

            show_notification('wizard_notif', errorThrown, 'nf_error', 5000);
		}
	});
}


/*  Function to load the content of the current wizard step  */
function initialize_wizard()
{
    var ctoken = Token.get_token("welcome_wizard");

	$.ajax(
	{
		url: __ajax_path + "wizard_ajax.php?token="+ctoken,
		data: {"action": "start_wizard"},
		type: "POST",
		dataType: "json",
		success: function(data)
		{
		    $.fancybox.close();

		    //Drawing the steps of the current step
		    change_selected_step_option(1, 1);

		    __current_step = 1;

		    //Showing the loading spinner
		    $('#wizard_loading').show();

		    //Loading the first step content
		    load_wizard_step();
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

            show_notification('wizard_notif', errorThrown, 'nf_error', 5000);
		}
	});
}


/*  Function to set the height of the container  */
function adjust_container_height()
{
    var h = 0;

    try
    {
        //Windows height - 220 (Height of the headers and margins)
        h = window.innerHeight - 151;
    }
    catch(Err){}

    //The minimun height is 475
    h = (h < 475) ? 475 : h;

    $('#wizard_step').css('min-height', h + 'px');

}


/*  Function to select as active the current step in the left menu  */
function change_selected_step_option(current, last)
{
    var total_steps = $('#wizard_path_container li').length;

    for (var i = 1; i <= total_steps; i++)
    {
        if (i <= last && i != current)
        {
            $('#wizard_path_container li#'+ i + ' .step_name').removeClass('av_l_disabled');
            $('#wizard_path_container li#'+ i + ' .wizard_number').addClass('s_visited');
        }
        else
        {
            $('#wizard_path_container li#'+ i + ' .step_name').addClass('av_l_disabled');
            $('#wizard_path_container li#'+ i + ' .wizard_number').removeClass('s_visited');

            if (i == current)
            {
                $('#wizard_path_container li').removeClass('current_step');
                $('#wizard_path_container li#'+ i).addClass('current_step');
            }
        }
    }

}


/*  Function to change the status of a button given its id and the status  */
function change_button_status(id, active)
{
    if (active)
    {
        $('#'+id).prop('disabled', false);
    }
    else
    {
        $('#'+id).prop('disabled', true);
    }
}
