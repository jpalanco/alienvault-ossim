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

var ajax_url      = '/ossim/home/providers/sidebar_ajax.php';
var notifRequest  = false;
var spinner_img   = "<img class='sidebar_spinner' src='<?php echo AV_PIXMAPS_DIR ?>/loader.gif'/>";

//TIMEOUTS FOR EACH ELEM IN THE SIDE BAR
var TIMEOUT_LIST    =
{
    'alarms' :
    {
        'function' : load_unresolved_alarms,
        'timeout'  : false,
        'delay'    : 60000
    },
    'tickets' :
    {
        'function' : load_open_tickets,
        'timeout'  : false,
        'delay'    : 60000
    },
    'sensors' :
    {
        'function' : load_active_sensors,
        'timeout'  : false,
        'delay'    : 180000
    },
    'eps' :
    {
        'function' : load_system_eps,
        'timeout'  : false,
        'delay'    : 300000
    },
    'devices' :
    {
        'function' : load_monitored_devices,
        'timeout'  : false,
        'delay'    : 120000
    },
    'trend' :
    {
        'function' : load_events_trend,
        'timeout'  : false,
        'delay'    : 300000
    }
};


/* SIDE BAR FUNCTIONS */

function load_sidebar_data()
{
    load_trial_status();
    load_open_tickets();
    load_unresolved_alarms();
    load_active_sensors();
    load_system_eps();
    load_monitored_devices();
    load_events_trend();
}


function remove_schedule(type)
{
    if (TIMEOUT_LIST[type]['timeout'])
    {
        clearTimeout(TIMEOUT_LIST[type]['timeout']);
        TIMEOUT_LIST[type]['timeout'] = false;
    }
}


function schedule_method(type)
{
    remove_schedule(type);

    TIMEOUT_LIST[type]['timeout'] = setTimeout(TIMEOUT_LIST[type]['function'], TIMEOUT_LIST[type]['delay']);
}




/*******************************************/

/********* NOTIFICATION FUNCTIONS **********/

/*******************************************/

function load_trial_status()
{
    $.ajax({
        data: {"action": 'trial_status'},
        type: "POST",
        url: ajax_url,
        dataType: "json",
        beforeSend: function(data)
        {
            if(notifRequest)
            {
                // Aborting previous request
                notifRequest.abort();
            }
            notifRequest = data;
        },
        success: function(response)
        {
            var $notif_container = $('#notif_container');
            var $notif_list = $('#notif_list');

            $notif_list.empty();

            if(typeof(response) == 'undefined' || response.error  || typeof(response.output) != 'object')
            {
                $('#trial_status').remove();
            }
            else
            {
                var trial_status = response.output;

                if (typeof(trial_status) == 'object')
                {
                    var _class = trial_status.class;
                    var _msg = trial_status.msg;

                    $("<li><a href='javascript:;' class='" + _class + " av_l_main'>" + _msg + "</a></li>").appendTo($notif_list);
                }

                $notif_container.addClass('trial_active');

                $('#notif_status').hide();
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

            // This is because a notification can be aborted to avoid collisions
            if (errorThrown != 'abort')
            {
                $('#notif_status').html("<?php echo _('It was not possible to load the trial status') ?>");
            }
        }
    });
}


function load_open_tickets()
{
    remove_schedule('tickets');

    var id = '#notif_tickets';

    $.ajax({
        data: {"action": 'open_tickets', "bypassexpirationupdate": "1"},
        type: "POST",
        url: ajax_url,
        dataType: "json",
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
                var tickets = response.output

                $(id).text(tickets.text);
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

        },
        complete: function()
        { 
            schedule_method('tickets');
        }
    });
}


function load_events_trend()
{
    remove_schedule('trend');

    var id = '#notif_eps';

    $.ajax({
        data: {"action": 'events_trend', "bypassexpirationupdate": "1"},
        type: "POST",
        url: ajax_url,
        dataType: "json",
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
                    highlightLineColor: 'rgba(22, 167, 201, 1)',
                    minSpotColor: false,
                    lineWidth: 1,
                    maxSpotColor: false,
                    spotColor: false,
                    chartRangeMin:0,
                    tooltipFormatter: function(a,b,c)
                    {
                        var label = c.y + ((c.y == '1')? ' <?php echo _('Event')?>' : ' <?php echo _('Events')?>');
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

        },
        complete: function() 
        {
            schedule_method('trend');
        }
    });
}


function load_system_eps()
{
    remove_schedule('eps');

    var id = '#notif_eps';

    $.ajax({
        data: {"action": 'system_eps', "bypassexpirationupdate": "1"},
        type: "POST",
        url: ajax_url,
        dataType: "json",
        beforeSend: function()
        {
            $(id).html(spinner_img).removeClass('nl_siem');
            $('#resume_eps').removeClass('nl_siem');
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
                var eps = response.output

                $('#resume_eps').text(eps.readable).addClass('nl_siem');
                $(id).text(eps.text + ' EPS').addClass('nl_siem');
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

        },
        complete: function()
        {
            schedule_method('eps');
        }
    });
}


function load_monitored_devices()
{
    remove_schedule('devices');

    var id = '#notif_devices';

    $.ajax({
        data: {"action": 'monitored_devices', "bypassexpirationupdate": "1"},
        type: "POST",
        url: ajax_url,
        dataType: "json",
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
                var devices = response.output

                $(id).text(devices.text);
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
        },
        complete: function()
        {
            schedule_method('devices');
        }
    });
}


function load_unresolved_alarms()
{
    remove_schedule('alarms');

    var id = '#notif_alarms';

    $.ajax({
        data: {"action": 'unresolved_alarms', "bypassexpirationupdate": "1"},
        type: "POST",
        url: ajax_url,
        dataType: "json",
        beforeSend: function()
        {
            $(id).html(spinner_img).removeClass('nl_alarms');
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

                    $('#resume_alarm_count').text(data.alarms.readable).addClass('nl_alarms');

                    $(id).text(data.alarms.text).addClass('nl_alarms');

                    // New Alarms
                    if (data.new_alarms > 0 && notify.isSupported)
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

        },
        complete: function()
        {
            schedule_method('alarms');
        }
    });
}


function load_active_sensors()
{
    remove_schedule('sensors');

    $.ajax({
        data: {"action": 'sensor_status', "bypassexpirationupdate": "1"},
        type: "POST",
        url: ajax_url,
        dataType: "json",
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
                var sensors = response.output
                var s_label = sensors.active.text + '/' + sensors.total.text;

                $('#notif_sensors').text(s_label + ' ' + "<?php echo _('Sensors Active') ?>");
                $('#notif_sensors').addClass('nl_sensors');

                if (sensors.color == "green")
                {
                    $('#semaphore_led1').css('background-color', '#94cf05');
                    $('#semaphore_led2').css('background-color', '#e3e3e3');
                    $('#semaphore_led3').css('background-color', '#e3e3e3');
                }
                else if (sensors.color == "yellow")
                {
                    $('#semaphore_led1').css('background-color', '#fac800');
                    $('#semaphore_led2').css('background-color', '#fac800');
                    $('#semaphore_led3').css('background-color', '#e3e3e3');
                }
                else if (sensors.color == "red")
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
        },
        complete: function()
        {
            schedule_method('sensors');
        }
    });
}



/*******************************************/

/********* BIND NOTIFICATION LINKS *********/

/*******************************************/


function bind_notif_links()
{
    $('#notif_container').on('click', '.nl_trial', function()
    {
        var url = "<?php echo AV_MAIN_PATH ?>/session/trial/trial_status.php?window=1";

        params = {
            caption       : "<?php echo _('Trial Status') ?>",
            url           : url,
            height        : '80%',
            width         : '80%',
            close_overlay : false
        };

        LB_show(params);
        $('#notif_bt').trigger('click');
    });


    <?php
    if (Session::menu_perms("configuration-menu", "PolicySensors"))
    {
        ?>
        $('#notif_container').on('click', '.nl_sensors', function()
        {
            var url = "<?php echo Menu::get_menu_url('/sensor/sensor.php', 'configuration', 'deployment', 'components', 'sensors'); ?>";

            av_menu.load_content(url);
            $('#notif_bt').trigger('click');

            return false;
        });
        <?php
    }

    if (Session::menu_perms("analysis-menu", "IncidentsIncidents"))
    {
        ?>
        $('#notif_container').on('click', '.nl_tickets', function()
        {
            var url = "<?php echo Menu::get_menu_url('/incidents/index.php?status=Open', 'analysis', 'tickets'); ?>";


            av_menu.load_content(url);
            $('#notif_bt').trigger('click');

            return false;
        });
        <?php
    }

    if(Session::menu_perms("environment-menu", "PolicyHosts"))
    {
        ?>
        $('#notif_container').on('click', '.nl_devices', function()
        {
            var url = "<?php echo Menu::get_menu_url('/av_asset/asset/index.php', 'environment', 'assets', 'assets'); ?>";

            av_menu.load_content(url);
            $('#notif_bt').trigger('click');

            return false;
        });
        <?php
    }


    if (Session::menu_perms("analysis-menu", "ControlPanelAlarms"))
    {
        ?>
        $('#notif_container').on('click', '.nl_alarms', function()
        {
            var url = "<?php echo Menu::get_menu_url('/alarm/alarm_console.php?hide_closed=1', 'analysis', 'alarms'); ?>";

            av_menu.load_content(url);
            
            if ($(this).attr('id') != 'resume_alarm_count')
            {
                $('#notif_bt').trigger('click');
            }

            return false;
        });
        <?php
    }


    if (Session::menu_perms("analysis-menu", "EventsForensics"))
    {
        ?>
        
        $('#notif_container').on('click', '.nl_siem', function(e)
        {            
            console.log($(this));
            var url = "<?php echo Menu::get_menu_url('/forensics/base_qry_main.php?clear_allcriteria=1&num_result_rows=-1&submit=Query+DB&current_view=-1&sort_order=time_d', 'analysis', 'security_events'); ?>";

            av_menu.load_content(url);
            
            if ($(this).attr('id') != 'resume_eps')
            {
                $('#notif_bt').trigger('click');
            }
            
            return false;
        });
        <?php
    }
    ?>
}


/*******************************************/

/******* html5 desktop notifications *******/

/*******************************************/


function av_notification (title, txt, delay4)
{
    if (notify.permissionLevel() == notify.PERMISSION_GRANTED)
    {
        var popup = notify.createNotification(title, { body: txt, icon:'<?php echo AV_PIXMAPS_DIR ?>/statusbar/logo_siem_small.png' });

        var delay = 5000 * delay4;

        setTimeout (function()
        {
            popup.close();

        }, delay);
    }
}
