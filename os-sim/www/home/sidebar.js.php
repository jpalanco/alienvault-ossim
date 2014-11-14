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

var ajax_url      = '/ossim/home/sidebar_ajax.php';
var OPEN_SIDE_BAR = false;
var notifRequest  = false;
var spinner_img   = "<img class='sidebar_spinner' src='<?php echo AV_PIXMAPS_DIR ?>/loader.gif'/>";

/* SIDE BAR FUNCTIONS */

function load_sidebar_data()
{
    load_open_tickets();
    load_unresolved_alarms();
    load_active_sensors();
    load_system_eps();
    load_monitored_devices();
    load_events_trend();

    load_notifications();

    setTimeout(function()
    {
        load_sidebar_data();

    }, 300000);

    return false;
}


function bind_notif_links()
{

    $(document).on('click', '.nl_trial', function()
    {
        var url = "<?php echo AV_MAIN_PATH ?>/session/trial/trial_status.php?window=1";

        LB_show("<?php echo _('Trial Status') ?>", url, '80%', '80%', false, false);

    });


    <?php
	if (Session::am_i_admin())
	{
        ?>
        $(document).on('click', '.nl_otx', function()
        {
            var url = "<?php echo Menu::get_menu_url(AV_MAIN_PATH.'/conf/index.php?section=otx', 'configuration', 'administration', 'main');?>";
            
            av_menu.load_content(url);

	        return false;
        });

	    $(document).on('click', '.nl_updates', function()
	    {
	        var url = "<?php echo Menu::get_menu_url('/av_center/index.php?ip=$ip&section=sw_pkg_pending', 'configuration', 'deployment', 'components', 'alienVault_center'); ?>";

	        av_menu.load_content(url);

	        return false;
	    });

	    $(document).on('click', '.nl_messages', function()
	    {
	        var url = "<?php echo Menu::get_menu_url('/system/index.php', 'dashboard', 'deployment_status', 'system_status'); ?>";

	        av_menu.load_content(url);

	        return false;
	    });

	    $(document).on('click', '.nl_device_exceed', function()
	    {
	        var url = "http://www.alienvault.com/contact/license";

	        new_window = window.open(url);

	        return false;

	        if (window.focus)
            {
                new_window.focus();
            }
	    });


	    <?php
    }

	if (Session::menu_perms("configuration-menu", "PolicySensors"))
	{
	    ?>
	    $(document).on('click', '.nl_sensors', function()
	    {
	        var url = "<?php echo Menu::get_menu_url('/sensor/sensor.php', 'configuration', 'deployment', 'components', 'sensors'); ?>";

	        av_menu.load_content(url);

	        return false;
	    });
	    <?php
    }

	if (Session::menu_perms("analysis-menu", "IncidentsIncidents"))
	{
	    ?>
	    $(document).on('click', '.nl_tickets', function()
	    {
	        var url = "<?php echo Menu::get_menu_url('/incidents/index.php?status=Open', 'analysis', 'tickets'); ?>";

	        av_menu.load_content(url);

	        return false;
	    });
	    <?php
    }

    if(Session::menu_perms("environment-menu", "PolicyHosts"))
    {
    ?>

        $(document).on('click', '.nl_devices', function()
        {
            var url = "<?php echo Menu::get_menu_url('/assets/index.php', 'environment', 'assets', 'assets'); ?>";

            av_menu.load_content(url);

            return false;

        });

        <?php
    }
    ?>

}

function nl_siem(e)
{
	<?php
	if (Session::menu_perms("analysis-menu", "EventsForensics"))
	{
	    ?>
        e.stopImmediatePropagation();

        var url = "<?php echo Menu::get_menu_url('/forensics/base_qry_main.php?clear_allcriteria=1&num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d', 'analysis', 'security_events'); ?>";

        av_menu.load_content(url);

        
	    <?php
    }
    ?>
    
    return false;
}

function nl_alarms(e)
{
	<?php
	if (Session::menu_perms("analysis-menu", "ControlPanelAlarms"))
	{
	    ?>
		e.stopImmediatePropagation();

	    var url = "<?php echo Menu::get_menu_url('/alarm/alarm_console.php?hide_closed=1', 'analysis', 'alarms'); ?>";

	    av_menu.load_content(url);

	    
	    <?php
	}
	?>
	
	return false;
}

function refresh_notifications()
{
    load_notifications();
}

function load_notifications()
{
    $.ajax({
        data: {"action": 'notifications', "bypassexpirationupdate": "1"},
        type: "POST",
        url: ajax_url,
        dataType: "json",
        async: true,
        beforeSend: function(data)
        {
            if(notifRequest)
            {
                // Aborting previous request
                notifRequest.abort();
            }
            notifRequest = data;

            $('#notif_list').empty().hide();
            $('#notif_status').html(spinner_img).show();
            $('#notifications_title').text("<?php echo _('Notifications') ?>");
        },
        success: function(response)
        {

            if(typeof(response) == 'undefined' || response.error  || typeof(response.output) != 'object')
            {
                $('#notif_status').html("<?php echo _('It was not possible to load the notifications') ?>");
                $('#notif_buble').text('0').hide();
            }
            else
            {
                var notif_list = response.output;

                if ($(notif_list).length > 0)
                {
                    //Retrieving notification info from cookie
                    var num_notif     = 0;



                    $('#notif_list').empty();

                    $.each(notif_list, function(key, notif)
                    {
                        if(typeof(notif) == 'object')
                        {
                            var _class = notif.class;
                            var _msg   = notif.msg;

                            $("<li><a href='javascript:;' class='"+_class+" av_l_main'>"+_msg+"</a></li>").appendTo($('#notif_list'));

                            num_notif++;
                        }
                    });

                    $('#notifications_title').html("<?php echo _('Notifications') ?> <span>("+ num_notif +")</span>")

                    $('#notif_buble').text(num_notif).show();

                    $('#notif_status').empty().hide();
                    $('#notif_list').show();
                }
                else
                {
                    $('#notif_status').empty().text("<?php echo _('There are no new notifications') ?>");
                    $('#notif_buble').text('0').hide();
                }
            }

        },
        error: function(data, textStatus, errorThrown)
        {

            //Check expired session
            var session = new Session(data, '');

            if ( session.check_session_expired() == true )
            {
                session.redirect();
                return;
            }

            $('#notif_buble').text('0').hide();
            //This is because a notification can be aborted to avoid collisions
            if (errorThrown != 'abort')
            {
                $('#notif_status').html("<?php echo _('It was not possible to load the notifications') ?>");
            }

        }
    });
}


function load_open_tickets()
{
    var id = '#notif_tickets';

    $.ajax({
        data: {"action": 'open_tickets', "bypassexpirationupdate": "1"},
        type: "POST",
        url: ajax_url,
        dataType: "json",
        async: true,
        beforeSend: function()
        {
            $(id).html(spinner_img);
            $(id).removeClass('nl_tickets');
        },
        success: function(response)
        {

            if(typeof(response) == 'undefined' || response.error)
            {

                $(id).text('-');
            }
            else
            {
                $(id).text(format_dot_number(response.output));
                $(id).addClass('nl_tickets');
            }

        },
        error: function(data)
        {
            //Check expired session
            var session = new Session(data, '');

            if ( session.check_session_expired() == true )
            {
                session.redirect();
                return;
            }

            $(id).text('-');

        }
    });

}


function load_events_trend()
{
    var id = '#notif_eps';

    $.ajax({
        data: {"action": 'events_trend', "bypassexpirationupdate": "1"},
        type: "POST",
        url: ajax_url,
        dataType: "json",
        async: true,
        beforeSend: function()
        {
            $(id).html(spinner_img);
        },
        success: function(response)
        {

            if(typeof(response) == 'undefined' || response.error)
            {

                $(id).text('-');
            }
            else
            {
                var labels = response.output.labels;

                $('.sparkline').attr('values', response.output.events);

                $('.sparkline').sparkline('html',{
                    width: 320,
                    height: 50,
                    lineColor: 'rgba(148, 207, 5, 1)',
                    fillColor: 'rgba(237, 237, 237, 1)',
                    minSpotColor: false,
                    lineWidth: 1,
                    maxSpotColor: false,
                    spotColor: false,
                    chartRangeMin:0,
                    tooltipFormatter: function(a,b,c) {

                        var label = c.y + ((c.y == 1)? ' <?php echo _('Event')?>' : ' <?php echo _('Events')?>');
                        if(typeof(labels[c.x]) != 'undefined')
                        {
                            label = labels[c.x] + '<br>' + label;
                        }

                         return label;
                    }
                });

            }

        },
        error: function(data)
        {
            //Check expired session
            var session = new Session(data, '');

            if ( session.check_session_expired() == true )
            {
                session.redirect();
                return;
            }

            $(id).text('-');

        }
    });

}

function load_system_eps()
{
    var id = '#notif_eps';

    $.ajax({
        data: {"action": 'system_eps', "bypassexpirationupdate": "1"},
        type: "POST",
        url: ajax_url,
        dataType: "json",
        async: true,
        beforeSend: function()
        {
            $(id).html(spinner_img).removeClass('nl_siem');
            $('#resume_eps').removeClass('nl_siem');
            $('.nl_siem').off('click');

        },
        success: function(response)
        {
            if(typeof(response) == 'undefined' || response.error)
            {
                $('#resume_eps').text('-');
                $(id).text('-');
            }
            else
            {
                $('#resume_eps').text(number_readable(response.output)).addClass('nl_siem');
                $(id).text(format_dot_number(response.output) + ' EPS').addClass('nl_siem');
                
                $('.nl_siem').on('click', nl_siem);
            }

        },
        error: function(data)
        {
            //Check expired session
            var session = new Session(data, '');

            if ( session.check_session_expired() == true )
            {
                session.redirect();
                return;
            }

            $('#resume_eps').text('-').removeClass('nl_siem');
            $(id).text('-');

        }
    });

}


function load_monitored_devices()
{
    var id = '#notif_devices';

    $.ajax({
        data: {"action": 'monitored_devices', "bypassexpirationupdate": "1"},
        type: "POST",
        url: ajax_url,
        dataType: "json",
        async: true,
        beforeSend: function()
        {
            $(id).html(spinner_img);
            $(id).removeClass('nl_devices');
        },
        success: function(response)
        {

            if(typeof(response) == 'undefined' || response.error)
            {

                $(id).text('-');
            }
            else
            {
                $(id).text(format_dot_number(response.output));
                $(id).addClass('nl_devices');
            }

        },
        error: function(data)
        {
            //Check expired session
            var session = new Session(data, '');

            if ( session.check_session_expired() == true )
            {
                session.redirect();
                return;
            }

            $(id).text('-');
        }
    });

}


function load_unresolved_alarms()
{
    var id = '#notif_alarms';

    $.ajax({
        data: {"action": 'unresolved_alarms', "bypassexpirationupdate": "1"},
        type: "POST",
        url: ajax_url,
        dataType: "json",
        async: true,
        beforeSend: function()
        {
            $(id).html(spinner_img).removeClass('nl_alarms');
            $('.nl_alarms').off('click');
        },
        success: function(response)
        {

            if(typeof(response) == 'undefined' || response.error)
            {

                $('#resume_alarm_count').text('-').removeClass('nl_alarms');
                $(id).text('-');
            }
            else
            {
            	if(typeof(response.output) == 'object')
            	{
            		var data = response.output;

	                $('#resume_alarm_count').text(number_readable(data.alarms)).addClass('nl_alarms');
	                
	                $(id).text(format_dot_number(data.alarms)).addClass('nl_alarms');
	                
	                $('.nl_alarms').on('click', nl_alarms);

	                // New Alarms
				    if (data.new_alarms > 0 && window.webkitNotifications)
				    {
						var new_alarms_desc = data.new_alarms_desc.split("|");
						for (var i = 0; i < new_alarms_desc.length; i++)
						{
							av_notification ('<?php echo Util::js_entities(_("New alarm")) ?>', new_alarms_desc[i], (i % 4) + 1);
						}
				    }
			    }
            }

        },
        error: function(data)
        {
            //Check expired session
            var session = new Session(data, '');

            if ( session.check_session_expired() == true )
            {
                session.redirect();
                return;
            }

            $('#resume_alarm_count').text('-').removeClass('nl_alarms');
            $(id).text('-');

        }
    });
}


function load_active_sensors()
{
    $.ajax({
        data: {"action": 'sensor_status', "bypassexpirationupdate": "1"},
        type: "POST",
        url: ajax_url,
        dataType: "json",
        async: true,
        beforeSend: function()
        {
            $('#notif_sensors').html(spinner_img);
            $('.semaphore').css('background-color', '');
            $('#notif_sensors').removeClass('nl_sensors');
        },
        success: function(response)
        {

            if(typeof(response) == 'undefined' || response.error)
            {

                $('#notif_sensors').text('- ' + "<?php echo _('Sensors Active') ?>");
            }
            else
            {
                var sensors = response.output.active + '/' + response.output.total;
                $('#notif_sensors').text(sensors + ' ' + "<?php echo _('Sensors Active') ?>");
                $('#notif_sensors').addClass('nl_sensors');

                if (response.output.color == "green")
                {
                    $('#semaphore_led1').css('background-color', '#94cf05');
                    $('#semaphore_led2').css('background-color', '#e3e3e3');
                    $('#semaphore_led3').css('background-color', '#e3e3e3');
                }
                else if (response.output.color == "yellow")
                {
                    $('#semaphore_led1').css('background-color', '#fac800');
                    $('#semaphore_led2').css('background-color', '#fac800');
                    $('#semaphore_led3').css('background-color', '#e3e3e3');
                }
                else if (response.output.color == "red")
                {
                    $('#semaphore_led1').css('background-color', '#fa0000');
                    $('#semaphore_led2').css('background-color', '#fa0000');
                    $('#semaphore_led3').css('background-color', '#fa0000');
                }
                else
                {
                    $('#semaphore_led1').css('background-color', '');
                    $('#semaphore_led2').css('background-color', '');
                    $('#semaphore_led3').css('background-color', '');
                }
            }

        },
        error: function(data)
        {
            //Check expired session
            var session = new Session(data, '');

            if ( session.check_session_expired() == true )
            {
                session.redirect();
                return;
            }

            $('#notif_sensors').text('-');
        }
    });

}



/*******************************************/

/******* html5 desktop notifications *******/

/*******************************************/


function RequestPermission (callback)
{
    window.webkitNotifications.requestPermission(callback);
}


function av_notification (title, body, delay4)
{
    if (window.webkitNotifications.checkPermission() > 0)
    {
        RequestPermission(av_notification);
    }
    else
    {
        var popup = window.webkitNotifications.createNotification('<?php echo AV_PIXMAPS_DIR ?>/statusbar/logo_siem_small.png', title, body);
        popup.show();

        var delay = 5000 * delay4;

        setTimeout (function()
        {
            popup.cancel();

        }, delay);
    }
}
