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

set_time_limit(0);

require_once 'av_init.php';
require_once 'alarm_common.php';

Session::logcheck("analysis-menu", "ControlPanelAlarms");

/* connect to db */
$db   = new ossim_db(TRUE);
$conn = $db->connect();


/* Retrieving parameters */
$delete          = GET('delete');
$close           = GET('close');
$open            = GET('open');
$delete_day      = GET('delete_day');
$order           = intval(GET('order'));
$torder          = GET('torder');
$src_ip          = GET('src_ip');
$dst_ip          = GET('dst_ip');
$asset_group     = GET('asset_group');
$backup_inf      = $inf = GET('inf');
$sup             = GET('sup');
$hide_closed     = GET('hide_closed');
$no_resolv       = intval(GET('no_resolv'));

$host_id         = GET('host_id');
$net_id          = GET('net_id');
$ctx             = GET('ctx');

//OTX
$otx_activity    = intval(GET('otx_activity'));
$pulse_id        = GET('pulse_id');

$query           = (GET('query') != "") ? GET('query') : "";
$directive_id    = GET('directive_id');
$intent          = intval(GET('intent'));
$sensor_query    = GET('sensor_query');
$tag             = GET('tag');
$num_events      = GET('num_events');
$max_risk        = GET('max_risk') != "" ? GET('max_risk') : 2;
$num_events_op   = GET('num_events_op');
$min_risk        = GET('min_risk') != "" ? GET('min_risk') : 0;
$date_from       = GET('date_from');
$date_to         = GET('date_to');
$ds_id           = GET('ds_id');
$ds_name         = GET('ds_name');
$beep            = intval(GET('beep'));
$_SESSION["per_page"] = $num_alarms_page = (GET('num_alarms_page') != "") 
	? intval(GET('num_alarms_page')) : (isset($_SESSION["per_page"]) ? $_SESSION["per_page"] : 20);
list($tags_count, $tags) = Tag::get_tags_by_type($conn, 'alarm');

$asset_sensors    = Av_sensor::get_list($conn, array(), FALSE, TRUE);
$_groups_data     = Asset_group::get_list($conn);
$asset_groups     = $_groups_data[0];

if (Session::is_pro() && Session::show_entities()) 
{
    list($entities, $_children, $_num_ent) = Acl::get_entities($conn, '', '', true, false);
}


$order  = ($order < 1 || $order > 9) ? 1 : $order;
$torder = ($torder == '' || preg_match("/desc/",$torder)) ? "desc" : "asc";

ossim_valid($order,           OSS_ALPHA, OSS_SPACE, OSS_SCORE, OSS_NULLABLE, '.',           'illegal:' . _("Order"));
ossim_valid($torder,          OSS_ALPHA, OSS_NULLABLE,                                      'illegal:' . _("Order Direction"));
ossim_valid($delete,          OSS_HEX, OSS_NULLABLE,                                        'illegal:' . _("Delete"));
ossim_valid($close,           OSS_HEX, OSS_NULLABLE,                                        'illegal:' . _("Close"));
ossim_valid($open,            OSS_HEX, OSS_NULLABLE,                                        'illegal:' . _("Open"));
ossim_valid($delete_day,      OSS_ALPHA, OSS_SPACE, OSS_PUNC, OSS_NULLABLE,                 'illegal:' . _("Delete_day"));
ossim_valid($query,           OSS_ALPHA, OSS_PUNC_EXT, OSS_SPACE, OSS_NULLABLE,             'illegal:' . _("Query"));
ossim_valid($directive_id,    OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Directive_id"));
ossim_valid($intent,          OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Intent"));
ossim_valid($src_ip,          OSS_IP_ADDRCIDR_0, OSS_NULLABLE,                              'illegal:' . _("Src_ip"));
ossim_valid($dst_ip,          OSS_IP_ADDRCIDR_0, OSS_NULLABLE,                              'illegal:' . _("Dst_ip"));
ossim_valid($asset_group,     OSS_HEX, OSS_NULLABLE,                                        'illegal:' . _("Asset Group"));
ossim_valid($inf,             OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Inf"));
ossim_valid($sup,             OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Order"));
ossim_valid($hide_closed,     OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Hide_closed"));
ossim_valid($date_from,       OSS_DATETIME_DATE, OSS_NULLABLE,                              'illegal:' . _("From date"));
ossim_valid($date_to,         OSS_DATETIME_DATE, OSS_NULLABLE,                              'illegal:' . _("To date"));
ossim_valid($sensor_query,    OSS_HEX, OSS_NULLABLE,                                        'illegal:' . _("Sensor_query"));
ossim_valid($tag,             OSS_HEX, OSS_NULLABLE,                                        'illegal:' . _("Tag"));
ossim_valid($num_events,      OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Num_events"));
ossim_valid($num_events_op,   OSS_ALPHA, OSS_NULLABLE,                                      'illegal:' . _("Num_events_op"));
ossim_valid($max_risk,        OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Max_risk"));
ossim_valid($min_risk,        OSS_ALPHA, OSS_NULLABLE,                                      'illegal:' . _("Min_risk"));
ossim_valid($ds_id,           OSS_DIGIT, "-", OSS_NULLABLE,                                 'illegal:' . _("Datasource"));
ossim_valid($beep,            OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Beep"));
ossim_valid($host_id,         OSS_HEX, OSS_NULLABLE,                                        'illegal:' . _("Host ID"));
ossim_valid($net_id,          OSS_HEX, OSS_NULLABLE,                                        'illegal:' . _("Net ID"));
ossim_valid($ctx,             OSS_HEX, OSS_NULLABLE,                                        'illegal:' . _("CTX"));
ossim_valid($num_alarms_page, OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Field number of alarms per page"));
ossim_valid($otx_activity,    OSS_BINARY, OSS_NULLABLE,                                     'illegal:' . _("Only OTX Pulse Activity"));
ossim_valid($pulse_id,        OSS_HEX, OSS_NULLABLE,                                        'illegal:' . _("Pulse ID"));

if ( ossim_error() ) 
{
    die(ossim_error());
}

$parameters['query']                  = "query="          .urlencode($query);
$parameters['directive_id']           = "directive_id="   .$directive_id;
$parameters['intent']                 = "intent="         .$intent;
$parameters['inf']                    = "inf="            .$inf;
$parameters['sup']                    = "sup="            .$sup;
$parameters['no_resolv']              = "no_resolv="      .$no_resolv;
$parameters['hide_closed']            = "hide_closed="    .$hide_closed;
$parameters['order']                  = "order="          .$order;
$parameters['torder']                 = "torder="         .$torder;
$parameters['src_ip']                 = "src_ip="         .$src_ip;
$parameters['dst_ip']                 = "dst_ip="         .$dst_ip;
$parameters['asset_group']            = "asset_group="    .$asset_group;
$parameters['date_from']              = "date_from="      .urlencode($date_from);
$parameters['date_to']                = "date_to="        .urlencode($date_to);
$parameters['sensor_query']           = "sensor_query="   .$sensor_query;
$parameters['tag']                    = "tag="            .$tag;
$parameters['num_alarms_page']        = "num_alarms_page=".$num_alarms_page;
$parameters['num_events']             = "num_events="     .$num_events;
$parameters['num_events_op']          = "num_events_op="  .$num_events_op;
$parameters['max_risk']               = "max_risk="       .$max_risk;
$parameters['min_risk']               = "min_risk="       .$min_risk;
$parameters['ds_id']                  = "ds_id="          .$ds_id;
$parameters['ds_name']                = "ds_name="        .urlencode($ds_name);
$parameters['beep']                   = "beep="           .$beep;
$parameters['host_id']                = "host_id="        .$host_id;
$parameters['net_id']                 = "net_id="         .$net_id;
$parameters['ctx']                    = "ctx="            .$ctx;
$parameters['otx_activity']           = "otx_activity="   .$otx_activity;
$parameters['pulse_id']               = "pulse_id="       .$pulse_id;


$params_alarm = implode("&", $parameters);
$refresh_url  = "alarm_console.php?". $params_alarm;

//Autocompleted
$autocomplete_keys = array('hosts');
$hosts_str         = Autocomplete::get_autocomplete($conn, $autocomplete_keys);

$pulse_name = '';

if ($pulse_id)
{
    try
    {
        $otx        = new Otx();
        $_p_data    = $otx->get_pulse_detail($pulse_id, TRUE);
        $pulse_name = $_p_data['name'];
    }
    catch (Exception $e) {}
}


//Cleaning the stats
unset($_SESSION["_alarm_stats"]);

//New alarm time flag for new beep alarm.
$_SESSION['_alarm_last_refresh_time'] = gmdate("U");

$refresh_time_secs = 300;

$alarm_url = Alarm::get_alarm_path();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title><?php echo _("Alarm Console")?> </title>
    <meta http-equiv="Pragma" content="no-cache"/>
    
    <?php
        //CSS Files
        $_files = array(
            array('src' => 'datepicker.css',                'def_path' => TRUE),
            array('src' => 'av_common.css',                 'def_path' => TRUE),
            array('src' => 'jquery-ui.css',                 'def_path' => TRUE),
            array('src' => 'jquery.autocomplete.css',       'def_path' => TRUE),
            array('src' => 'tipTip.css',                    'def_path' => TRUE),
            array('src' => 'jquery.dataTables.css',         'def_path' => TRUE),
            array('src' => 'jquery.dropdown.css',           'def_path' => TRUE),
            array('src' => 'jquery.switch.css',             'def_path' => TRUE),
            array('src' => '/alarm/console.css',            'def_path' => TRUE),
            array('src' => 'av_dropdown_tag.css',           'def_path' => TRUE),
            array('src' => 'av_tags.css',                   'def_path' => TRUE),
            array('src' => 'ui.slider.extras.css',          'def_path' => TRUE)
        );
        
        Util::print_include_files($_files, 'css');

        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                     'def_path' => TRUE),
            array('src' => 'jquery-ui.min.js',                  'def_path' => TRUE),
            array('src' => 'utils.js',                          'def_path' => TRUE),
            array('src' => 'notification.js',                   'def_path' => TRUE),
            array('src' => 'token.js',                          'def_path' => TRUE),
            array('src' => 'jquery.tipTip-ajax.js',             'def_path' => TRUE),
            array('src' => 'greybox.js',                        'def_path' => TRUE),
            array('src' => 'jquery.dataTables.js',              'def_path' => TRUE),
            array('src' => 'jquery.dataTables.plugins.js',      'def_path' => TRUE),
            array('src' => 'jquery.autocomplete.pack.js',       'def_path' => TRUE),
            array('src' => 'jquery.dropdown.js',                'def_path' => TRUE),
            array('src' => 'jquery.sparkline.js',               'def_path' => TRUE),
            array('src' => 'jquery.switch.js',                  'def_path' => TRUE),
            array('src' => 'jquery.hotkeys.js',                 'def_path' => TRUE),
            array('src' => 'av_tags.js.php',                    'def_path' => TRUE),
            array('src' => 'av_dropdown_tag.js',                'def_path' => TRUE),
            array('src' => '/alarm/js/alarm_console.js.php',    'def_path' => FALSE),
            array('src' => '/js/av_storage.js.php',    'def_path' => FALSE),
            array('src' => 'selectToUISlider.jQuery.js',         'def_path' => TRUE)
        );
        
        Util::print_include_files($_files, 'js');

        require '../host_report_menu.php';
        
    ?>
    
    <script language="javascript">
    
        var is_toggled   = 0; // Count of toggled groups (for disable autorefresh)
        var refres_time  = <?php echo $refresh_time_secs ?>;
        var time         = refres_time;
        var timeout_rfh  = false;        
        var flah_bg      = false;
        var tip_timeout  = null;
        var alarm_table  = false;
        var graph_change = true;
        var graph_filter = false;
        var draw_label   = false;
        var dd_alarm     = '';
        var selection_type = 'manual';
        var curent_checkbox_selected = false;
        var count_of_all_alarm = 0;
        alarm_session_db = new av_session_db('alarm_db_datatables');

        /*  Local Storage Keys  */
        var __local_storage_keys =
        {
            "graph"  : "alienvault_<?php echo Session::get_session_user() ?>_show_graph",
            "filters": "alienvault_<?php echo Session::get_session_user() ?>_show_alarm_filters"
        };
        
        //Double click issue variables
        var click_delay  = 250, n_clicks = 0, click_timer = null;

        function alarm_notification(msg, type)
        {
            $('#delete_data').html('');
            $('#info_delete').hide();
                    
            if (typeof top.scrollTo == 'function')
            {
                top.scrollTo(0,0);
            }
            
            notify(msg, type);
        }
  
        function reload_alarms()
        {
            if(time == 0)
            {
                $('#reload').text('0');

                if(alarm_table)
                {                    
                    alarm_table._fnAjaxUpdate();
                }
                if (timeout_rfh)
                {
                    clearTimeout(timeout_rfh)
                }
                //Setting up the counter again
                time = refres_time;
                reload_alarms();
            }
            else if(time == -1)
            {
                $('#reload').text('-');
            }
            else 
            {
                $('#reload').text(time);
                
                time--;
                timeout_rfh = setTimeout('reload_alarms();',1000);
            }           
        }

        function alarm_action(action_number, open_msg)  {

            av_confirm(open_msg).done(function()
            {

                $('#delete_data').html("<?php echo _("Opening selected alarm ...") ?>");
                $('#info_delete').show();

                var ids = [];
                var atoken = Token.get_token("alarm_operations");
                var get_db =  selection_type == 'all' ? '' : sessionStorage.getObj('alarm_db_datatables');
                if(get_db.length != 0) {

                    for (var id in get_db) {
                        ids.push(id);
                    }
                }
                alarm_session_db.clean_checked();
                selection_type = 'manual';

                $.ajax(
                    {
                        type: "POST",
                        url: "<?php echo $alarm_url['controller'] ?>alarm_actions.php?token="+atoken,
                        dataType: "json",
                        data: {"action": action_number, "data": {"id": ids} },
                        success: function(data)
                        {
                            if(data.error)
                            {
                                $('#info_delete').hide();
                                notify(data.msg, 'nf_error');
                            }
                            else
                            {
                                $('#delete_data').html("<?php echo _("Reloading alarms ...") ?>");
                                document.location.href='<?php echo $refresh_url?>';
                            }

                        },
                        error: function(XMLHttpRequest, textStatus, errorThrown)
                        {
                            $('#info_delete').hide();
                            notify(textStatus, 'nf_error');
                        }
                    });

            });
        }
        function tray_delete(backlog_id)
        {
            select_tray_input(backlog_id);
            bg_delete();
        }
        
        function tray_close(backlog_id)
        {
            select_tray_input(backlog_id);
            bg_close();
        }
        function tray_open(backlog_id)
        {
            select_tray_input(backlog_id);
            open_alarm();
        }

        function select_tray_input (backlog_id) 
        {
            //First we unselect every checkbox
            $("input[type=checkbox]").each(function() 
            {
                if (this.id.match(/^check_[0-9A-Z]+/)) 
                {
                    if(!$(this).prop('disabled'))
                    {
                        $(this).prop('checked', false);
                    }
                }
            });
            
            //Now we select this input
            $("#check_"+backlog_id).prop('checked', true);
            
            chk_actions();
        }

        function selectedAlarm() {


            var checkbox_count = 0 ;
            var checkbox_selected = 0;
            $("input[type=checkbox].alarm_check").each(function()
            {
                if (this.id.match(/^check_[0-9A-Z]+/))
                {

                    if(!$(this).prop('disabled'))
                    {
                        checkbox_count++;
                        if (alarm_session_db.is_checked($(this).attr('id')))
                        {
                            checkbox_selected++;
                            $(this).prop('checked', true);
                        }
                    }
                }
            });
            curent_checkbox_selected =  checkbox_count == checkbox_selected && checkbox_selected != 0;

            if (curent_checkbox_selected) {
                $('#allcheck').prop('checked', true);
                $('#selectall').show();
            }else  {
                $('#selectall').hide();
                $('#allcheck').prop('checked', false);
            }

            if( selection_type == 'all') {
                selected_checkbox( true,false);
                $('#allcheck').prop('checked', true);
            }
        }

        function checkall (that) 
        {

            if( selection_type == 'all')
            {
                selection_type = 'manual';
                selected_checkbox(false,false);

            }else{
                that.checked ? $('#selectall').show() : $('#selectall').hide();
                selected_checkbox( that.checked,true);
            }
            chk_actions();
        }

        function selected_checkbox(status, sesion_store_save) {

            $("input[type=checkbox].alarm_check").each(function() 
            {
                if (this.id.match(/^check_[0-9A-Z]+/)) 
                {
                    if(!$(this).prop('disabled'))
                    {
                        $(this).prop('checked', status);
                        if(sesion_store_save) {
                            manage_check(this);
                        }
                    }
                }
            });
        }

        function toggle_filters()
        {
            var status = ($('#alarm_params').css('display') == 'none');

            var $arrow  = $('#search_arrow');
            var $params = $('#alarm_params');

            if(status)
            {
                $arrow.attr('src', '/ossim/pixmaps/arrow_down.png');
                $params.slideDown();
            }
            else
            {
                $arrow.attr('src', '/ossim/pixmaps/arrow_right.png');
                $params.slideUp();
            }

            if (!showing_calendar)
            {
                calendar();
            }

            set_ls_key_status('filters', status);
        }

        function display_filters()
        {
            var status  = get_ls_key_status('filters');
            var $arrow  = $('#search_arrow');
            var $params = $('#alarm_params');

            if(status)
            {
                $arrow.attr('src', '/ossim/pixmaps/arrow_down.png');
                $params.show();
            }
            else
            {
                $arrow.attr('src', '/ossim/pixmaps/arrow_right.png');
                $params.hide();
            }
        }

        
        var showing_calendar = false;
        function calendar() 
        {
            showing_calendar = true;
            // CALENDAR
            
            $('.date_filter').datepicker(
            {
                showOn: "both",
                buttonText: "",
                dateFormat: "yy-mm-dd",
                buttonImage: "/ossim/pixmaps/calendar.png",
                onClose: function(selectedDate)
                {
                    // End date must be greater than the start date
                    
                    if ($(this).attr('id') == 'date_from')
                    {
                        $('#date_to').datepicker('option', 'minDate', selectedDate );
                    }
                    else
                    {
                        $('#date_from').datepicker('option', 'maxDate', selectedDate );
                    }
                }
            });
        }
        
        function get_alarms_checked()
        {
            var alarms = new Array();
            
            $(".alarm_check:checked").each(function()
            {
                var id = $(this).attr('id');   
                    id = id.replace('check_', '');
                
                alarms.push(id);
                
            });
            
            return alarms;
            
        }
        
        function save_alarm_checked()
        {
            var checked = get_alarms_checked();
            
            var atoken  = Token.get_token("alarm_operations");
            
            $.ajax(
            {
                type   : "POST",
                url    : "<?php echo $alarm_url['controller'] ?>alarm_actions.php?token="+atoken,
                async  : false,
                data   : {"action": 3, "data": {"alarms": checked} },
                success: function(msg){}
            });

            return false;
        }

        function load_alarm_detail(id, type)
        {
            save_alarm_checked();

            if(type == 'alarm')
            {
                if(alt_pressed)
                {
                    GB_show_multiple('<?php echo _("Alarm Detail") ?>', 'alarm_detail.php?backlog=' + id, 600, '80%');
                }
                else
                {
                    document.location.href = 'alarm_detail.php?backlog=' + id;
                }
            }
            else if(type == 'event')
            {
                var url = '<?php echo Util::get_acid_single_event_link("EVENTID") ?>';
                if(alt_pressed)
                {
                    GB_show_multiple('<?php echo _("Event Detail") ?>', url.replace(/EVENTID/,id), 600, '80%');
                }
                else
                {
                    document.location.href = url.replace(/EVENTID/,id);
                }
            }

        }

       <?php if ( Session::menu_perms("analysis-menu", "ControlPanelAlarmsClose") ) { ?>
        function bg_close() 
        {
            var close_msg = "<?php echo Util::js_entities(_("You are going to close the selected alarm(s). Would you like to continue?")) ?>";
            var action_number =  selection_type == 'all' ? 5 : 1;
            alarm_action(action_number,close_msg);
        }
        <?php } ?>

        function open_alarm()
        {
            var open_msg = "<?php echo Util::js_entities(_("You are going to open the selected alarm. Would you like to continue?")) ?>";
            var action_number =  selection_type == 'all' ? 8 : 2;
            alarm_action(action_number,open_msg);
            
        }

        // Remove tag from alarm datatable row
        function remove_alarm_tag(status, data)
        {
            $('#delete_data').html('');
            $('#info_delete').hide();

            if ('OK' == status)
            {
                display_datatables_column(true);

                $(".alarm_check:checked").each(function ()
                {
                    var row = $(this).parents('tr');
                    var cell = $('.a_label_container', row);

                    if ($('div.tag_' + data.id, cell).length != 0)
                    {
                        $('div.tag_' + data.id, cell).remove();
                    }
                });

                check_label_column();
            }
            else
            {
                alarm_notification(data.msg, 'nf_error');
            }
        }

        // Add tag to alarm datatable row
        function add_alarm_tag(status, data)
        {
            $('#delete_data').html('');
            $('#info_delete').hide();

            if ('OK' == status)
            {
                display_datatables_column(true);

                $(".alarm_check:checked").each(function ()
                {
                    var row = $(this).parents('tr');
                    var cell = $('.a_label_container', row);

                    if ($('div.tag_' + data.id, cell).length == 0)
                    {
                        var $tag = draw_tag(data, row.attr('id'), check_label_column);
                        $(cell).append($tag);
                    }
                });
            }
            else
            {
                alarm_notification(data, 'nf_error');
            }
        }

        function check_background_tasks(times){

            var bg     = false;
            var atoken = Token.get_token("alarm_operations");
            
            $.ajax({
                type: "POST",
                url: "<?php echo $alarm_url['controller'] ?>alarm_actions.php?token="+atoken,
                async: false,
                dataType: "json",
                data: {"action": 7 },
                success: function(data)
                {
                    if(typeof(data) == 'undefined' || data.error == true) 
                    {
                        notify('<?php echo _("Unable to check background tasks") ?>', 'nf_error');
                    }
                    else
                    {
                        if(data.bg)
                        {
                            bg = true;                      
                        }
                    }                    
                },
                error: function(){
                    notify('<?php echo _("Unable to check background tasks") ?>', 'nf_error');
                }
            });

            if(bg)
            {
                if(!flah_bg)
                {
                    var h = $.getDocHeight();
                    h     = (h != '') ? h+'px' : '100%'; 
                    var layer = "<div id='bg_container' style='width:100%;height:" + h + ";position:absolute;top:0px;left:0px;'></div>";
                    $('body').append(layer);
                    show_loading_box('bg_container', '<?php echo Util::js_entities(_("Alarm task running in background. This process could take a while.")) ?>', '');
                    refres_time = -1;   
                    flah_bg     = true;             
                }

                timeout = (times < 5) ? 2000 : 10000;
                setTimeout('check_background_tasks('+ (times+1) +');',timeout);
            }
            else
            {
                if(flah_bg)
                    document.location.href='<?php echo $refresh_url?>';
            }

        }
                            
        <?php
       if ( Session::menu_perms("analysis-menu", "ControlPanelAlarmsDelete") )
       {
            ?>
            function bg_delete()
            {
                var msg_delete = "<?php echo Util::js_entities(_("Alarms should never be deleted unless they represent a false positive. Would you like to continue?")) ?>"
                var action_number = 6;
                alarm_action(action_number,msg_delete);
            }
        
            function delete_all_alarms()
            {
                var msg_delete = "<?php echo  Util::js_entities(_("Alarms should never be deleted unless they represent a false positive. Would you like to continue?"))?>"
                
                av_confirm(msg_delete).done(function()
                {
                    var msg_close = "<?php echo  Util::js_entities(_("Would you like to close the alarm instead of deleting it?"))?>";
                    var close_opts = {"yes": "<?php echo Util::js_entities(_('Delete Alarms')) ?>","no": "<?php echo Util::js_entities(_('Close Alarms')) ?>"}           
                     
                    av_confirm(msg_close, close_opts).done(function() 
                    {
                        $('#delete_data').html('<?php echo _("Deleting ALL alarms ...") ?>');
                        $('#info_delete').show();
                        
                        var atoken = Token.get_token("alarm_operations");
                        selection_type = 'manual';

                        $.ajax({
                            type: "POST",
                            url: "<?php echo $alarm_url['controller'] ?>alarm_actions.php?token="+atoken,
                            dataType: "json",
                            data: {"action": 4 },
                            success: function(data)
                            {
                                if(data.error)
                                {
                                    $('#info_delete').hide();     
                                    notify(data.msg, 'nf_error');
                                } 
                                else
                                {
                                    $('#delete_data').html("<?php echo _("Reloading alarms ...") ?>");
                                    document.location.href='<?php echo $refresh_url?>';                             
                                }

                            },
                            error: function(XMLHttpRequest, textStatus, errorThrown) {
                                    $('#info_delete').hide();
                                    notify(textStatus, 'nf_error');
                            }

                        });

                    }).fail(function()             
                    {
                        $('#delete_data').html('<?php echo _("Closing ALL alarms ...") ?>');
                        $('#info_delete').show();
                        
                        var atoken = Token.get_token("alarm_operations");
                        
                        $.ajax({
                            type: "POST",
                            url: "<?php echo $alarm_url['controller'] ?>alarm_actions.php?token="+atoken,
                            dataType: "json",
                            data: {"action": 5},
                            success: function(data)
                            {
                                if(data.error)
                                {
                                    $('#info_delete').hide();     
                                    notify(data.msg, 'nf_error');
                                } 
                                else
                                {
                                    $('#delete_data').html("<?php echo _("Reloading alarms ...") ?>");
                                    document.location.href='<?php echo $refresh_url?>';                             
                                }

                            },
                            error: function(XMLHttpRequest, textStatus, errorThrown) {
                                    $('#info_delete').hide();
                                    notify(textStatus, 'nf_error');
                            }

                        });
                    });
                    
                });

            }  
            
            <?php } ?>
        
        function load_trend(layer,pid,sid) 
        {
            $.ajax({
                data:  { "plugin_id": $('#'+layer).attr('data-pid'), "plugin_sid": $('#'+layer).attr('data-sid') },
                type: "GET",
                url: "providers/alarm_trend.php", 
                dataType: "json",
                success: function(data)
                { 
                    var lines   = new Array();
                    var tooltip = new Array();
                    
                    for (var key in data)
                    {
                        var open     = data[key]['open']
                        var close    = data[key]['closed'];
                        
                        tooltip.push(key);
                        
                        lines.push(open + ':' + close);

                    }

                    $('#'+layer).empty();

                    //Sparkline
                    $('#'+layer).sparkline(lines, {
                        type: 'bar',
                        stackedBarColor: ['#8CC63F', '#FF0000'],
                        height: '30px',
                        disableHighlight: false,
                        highlightLighten: 1.1,
                        barWidth: 10,
                        barSpacing: 4,
                        disableTooltips: false,
                        tooltipFormatter: function(a,b,c) 
                        {
                            var open   = c[1].value + "<span style='font-weight:bold;color:" + c[1].color + "'> open</span>";
                            var closed = c[0].value + "<span style='font-weight:bold;color:" + c[0].color + "'> closed</span>";
                            var date   = tooltip[c[0].offset];
        
                            var tag   = date + '<br>' + open + ' <br>' + closed; 
                            
                            return tag;
                        }
                    });

                },
                error: function(XMLHttpRequest, textStatus, errorThrown) 
                {
                    $('#'+layer).html('');
                }
            });             
        }
        
        function tr_click_function(row)
        {
            var nTr  = $(row)[0];
            var that = $(row);
            
            if (alarm_table.fnIsOpen(nTr))
            {
                alarm_table.fnClose(nTr);
            }
            else
            {
                var data       = fnFormatDetails(nTr);
                
                var aData      = alarm_table.fnGetData(nTr);
                
                var alarm_data = aData[0].match(/check_([a-f\d]{32})_[a-f\d]{32}/i); // name="check_backlogid_eventid"
        
                //console.log('#sparktristatecols_'+alarm_data[1]);
                that.addClass('tray_wait');
                
                $.when(data).then(function(theData) 
                {
                    that.removeClass('tray_wait');
        
                    alarm_table.fnOpen(nTr, theData, 'tray_details');
                    
                    $('#sparktristatecols_'+alarm_data[1]).html('');
                    
                    load_trend('sparktristatecols_'+alarm_data[1]);
                    
                });
            }
        }
        
        function tr_dblclick_function(row)
        {
            
            var alarm_type = $('td:eq(0) input',row).attr('data'); // data="alarm|event"
            var alarm_data = $('td:eq(0) input',row).attr('name').split('_'); // name="check_backlogid_eventid"

            load_alarm_detail(alarm_data[1], alarm_type);

        }
        
        function fnFormatDetails (nTr)
        {
            var aData = alarm_table.fnGetData(nTr);
            
            var alarm_data = aData[0].match(/check_([a-f\d]{32})_[a-f\d]{32}/i); // name="check_backlogid_eventid"
            
            $('body').css('cursor','wait');
             
            return $.ajax(
            {
                type: 'GET',
                url:  'providers/alarm_tray.php?backlog=' + alarm_data[1]
            });           
        }
            
        function load_handlers()
        {
            if (graph_change)
            {        
                display_graph(true);
            }
            else if (!graph_filter) // only if we are not filtering
            {
                graph_change = true;
            }
            
            // tipTip
            $('.tip').tipTip({maxWidth:'300px'});
            $('td.tipd').tipTip({defaultPosition:"right"});
            $('.scriptinfo').tipTip(
            {
               defaultPosition: "down",
               content: function (e) 
               {
                  var ip_data = $(this).attr('data-title');                      
                  
                  $.ajax(
                  {
                      url: "<?php echo $alarm_url['provider'] ?>alarm_netlookup.php?ip=" + ip_data,
                      success: function (response) 
                      {
                        e.content.html(response); // the var e is the callback function data (see above)
                      }
                  });
                  
                  return '<?php echo _("Searching")."..."?>'; // We temporary show a Please wait text until the ajax success callback is called.
               }
            });

            // Details icon
            $('.go_details').off('click').on('click', function(e) 
            {
                e.stopPropagation();
                
                var row = $(this).parents('tr');
                
                var alarm_type = $('td:eq(0) input',row).attr('data'); // data="alarm|event"
                var alarm_data = $('td:eq(0) input',row).attr('name').split('_'); // name="check_backlogid_eventid"
                
                load_alarm_detail(alarm_data[1], alarm_type);
                
            });
            
            
            // OTX icon
            $('.otx_icon').off('click').on('click', function(e) 
            {
                e.stopPropagation();
                
                var backlog = $(this).parents('tr').attr('id');
                var title   = "<?php echo Util::js_entities(_('OTX Details')) ?>";
                var url     = "/ossim/otx/views/view_my_pulses.php?type=alarm&id=" + backlog;
                
                GB_show(title, url, 550, 700);
                
                return false;
            });
            
            
            // stop propagation
            $('a.stop, input.stop, td.stop').off('click').on('click', function(e) 
            {
                e.stopPropagation();
            });
            
                
            $('.table_data tbody tr td').not(":first-child").not(":last-child").off('click').on('click', function ()
            {
                n_clicks++;  //count clicks
                
                var row = $(this).parents('tr').first();
                
                $(this).disableTextSelect();
                
                if(n_clicks === 1) 
                {
                    click_timer = setTimeout(function() 
                    {
                        $(this).enableTextSelect();
                        
                        n_clicks = 0;             //reset counter
                        tr_click_function(row);  //perform single-click action    
            
                    }, click_delay);
                } 
                else 
                {
                    clearTimeout(click_timer);  //prevent single-click action
                    n_clicks = 0;               //reset counter
                    tr_dblclick_function(row);  //perform double-click action
                }
                
            }).off('dblclick').on('dblclick', function(event) 
            {
                event.preventDefault();
            });
            
            load_contextmenu();

            // input click
            $('input[name^="check_"]').off('click').on('click', function() {
                manage_check(this);
                chk_actions();
            });
            
            $('#allcheck').prop('disabled', ($("input[type=checkbox].alarm_check").not("[disabled]").length < 1))
        }           
        
        function chk_actions()
        {
            var count_ids = (sessionStorage.getObj('alarm_db_datatables')) ?  Object.keys(sessionStorage.getObj('alarm_db_datatables')).length : 0;

            $('#selectall > span').text(count_ids);
            $('#selectall a span').text(count_of_all_alarm);

            if (count_ids > 0 || selection_type == 'all')
            {
                $('#button_action').prop('disabled', false);
                $('#btn_al').removeClass('disabled');
            }   
            else
            {
                $('#button_action').prop('disabled', true);
                $('#btn_al').addClass('disabled');
                
                $('.apply_label_layer').empty();
            }
        }

        function manage_check (input)
        {
            var __self = this;

            if($(input).prop('checked'))
            {
                __self.alarm_session_db.save_check($(input).attr('id'));
            }
            else
            {
                __self.alarm_session_db.remove_check($(input).attr('id'));
            }

        }
        
        function display_datatables_column(show)
        {

            alarm_table.fnSetColumnVis(3, show, false);
        }


        function check_label_column()
        {
            draw_label = false;

            $(".table_data > tbody > tr").each(function ()
            {
                var labels = $('.a_label_container > div', this).length;

                if (labels > 0)
                {
                    draw_label = true;
                    return false;
                }
            });

            display_datatables_column(draw_label);
        }


        function set_graph_height(h)
        {
               $('#alarm_graph').animate({ height: h+'px' }, 1000).show();
        }


        function reset_intent()
        {
            graph_filter = false;
            var newUrl = '<?php echo "providers/alarm_console_ajax.php?" . implode("&", $parameters) ?>';
            if(alarm_table)
            {
                time = refres_time;
                graph_change = false;
                alarm_table.fnReloadAjax(newUrl);
            }            
            $('#breadcrum').removeClass('marginbottom').html('');
            $('#alarm_graph').contents().find('.bubble_on').addClass('bubble').removeClass('bubble_on');
            $('#alarm_graph').contents().find('tr').show();
            set_graph_height(430);// 5*76+50 header
        }
        
        
        function filter_by_intent(intent,txt,range)
        {
            graph_filter = true;
            var dates = range.split(';');
            <?php
            $parameters_i = $parameters;
            unset($parameters_i["intent"]);
            unset($parameters_i["date_from"]);
            unset($parameters_i["date_to"]);
            $url_without_intent = "providers/alarm_console_ajax.php?" . implode("&", $parameters_i);
            ?>
            var newUrl = '<?php echo $url_without_intent ?>&intent='+intent+'&date_from='+dates[0]+'&date_to='+dates[1];
            if(alarm_table)
            {
                time = refres_time;
                graph_change = false;
                $('#breadcrum').html('<a href="javascript:;" onclick="reset_intent()">All Alarms</a> > '+txt).addClass('marginbottom');
                alarm_table.fnReloadAjax(newUrl);
            }            
        }
        
        
        function display_graph(show)
        {
            if (show && get_ls_key_status('graph'))
            {
                $('#graph_overlay').show();
                $('#alarm_graph').hide().attr('src','views/alarm_graph.php');
            }
            else
            {
                $('#graph_overlay').hide();
                $('#alarm_graph').contents().empty();
                $('#alarm_graph').attr('src','').hide();
            }
        }
        
        function postload_graph()
        {
            var src = $('#alarm_graph').attr('src');
            
            if (src)
            {
                $('#graph_overlay').hide()
                $('#alarm_graph').show()
            }
        }

        function get_ls_key_status(k)
        {
            var key     = __local_storage_keys[k];
            var enabled = 0;

            if (key)
            {
                enabled = localStorage.getItem(key);
            }

            return enabled != 0;
        }

        function set_ls_key_status(k, status)
        {
            var key = __local_storage_keys[k];
            var val = status ? 1 : 0;

            if (key)
            {
                localStorage.setItem(key, val);
            }
        }
        
        
        var in_tooltip = false;
        function draw_tooltip(content)
        {
            $('#graph_container').append(content);
            in_tooltip = false;
            
            $('#graph_container').find('.alarm-info').mouseover(function()
            {
                in_tooltip = true;
            });
            
            $('#graph_container').find('.alarm-info').mouseout(function()
            {
                if (in_tooltip) 
                {
                    remove_tooltip();
                }
            });
        }
        
        function remove_tooltip()
        {
            $('#graph_container').find('.alarm-info').remove();
        }
        
        
        $(document).ready(function()
        {

            $('#arangeA, #arangeB').selectToUISlider({
                tooltip: false,
                labelSrc: 'text'
            });

            var i_am_admin = <?php echo (Session::am_i_admin()) ? 'true' : 'false' ?>;

            var o = 
            {
                'load_tags_url': '<?php echo AV_MAIN_PATH?>/tags/providers/get_dropdown_tags.php',
                'manage_components_url': '<?php echo AV_MAIN_PATH?>/tags/controllers/tag_components_actions.php',
                'allow_edit': i_am_admin,
                'tag_type': 'alarm',
                'components_check_class': 'alarm_check',
                'on_save': add_alarm_tag,
                'on_delete': remove_alarm_tag
            };

            var $alarm_dd = $('#btn_al').av_dropdown_tag(o);
            $alarm_dd.off('click').on('click', function()
            {
                if (!$(this).hasClass('disabled'))
                {
                    $alarm_dd.show_dropdown();
                }
            });
            
            calendar();
            
            $('.tags_edit').click(function (e)
            {
                edit_tags('alarm');
            });
            
            $('#graph_toggle').toggles(
            {
                "on"   : get_ls_key_status('graph'),
                "text" : 
                {
                    "on"  : '<?php echo _('Yes')?>',
                    "off" : '<?php echo _('No')?>'
                }
            }).on('toggle', function(e, status)
            {
                set_ls_key_status('graph', status);
                display_graph(status);
            });

            display_filters();

            $('#clean_date_filter').on('click', function()
            {
                $('#date_from').val('');
                $('#date_to').val('');
                
                $('#queryform').submit();
                
                return false;
            });

            check_background_tasks(0);  

            //Loading the alarm counter that reloads the alarms
            reload_alarms();

            $("a.greybox2").click(function(e)
            {
                e.stopPropagation();
                
                var t = this.title || $(this).attr('data-title') || this.href;
                GB_show(t, this.href, 550, '80%');
                
                return false;
            });


            // Autocomplete
            var hosts = [<?php echo $hosts_str ?>];
            
            $("#src_ip").autocomplete(hosts, 
            {
                minChars: 0,
                matchContains: "word",
                autoFill: false,
                selectFirst: false,
                formatItem: function(row, i, max) 
                {
                    return row.txt;
                }
            }).result(function(event, item) 
            {
                $("#src_ip").val(item.ip);
            });
            
            
            $("#dst_ip").autocomplete(hosts, 
            {
                minChars: 0,
                matchContains: "word",
                autoFill: false,
                selectFirst: false,
                formatItem: function(row, i, max) 
                {
                    return row.txt;
                }
            }).result(function(event, item) 
            {
                $("#dst_ip").val(item.ip);
            });
            
            $("#pulse_name").autocomplete('/ossim/otx/providers/otx_pulse_autocomplete.php?type=alarm', 
            {
                minChars: 0,
                matchContains: "word",
                multiple: false,
                autoFill: false,
                formatItem: function(row, i, max, value) 
                {
                    return (value.split('###'))[1];
                },
                formatResult: function(data, value) 
                {
                    return (value.split('###'))[1];
                }
            }).result(function(event, item) 
            {
                var pulse_id = '';
                if (typeof(item) != 'undefined' && item != null)
                {
                    var _aux_item = item[0].split('###');
                    pulse_id      = _aux_item[0];
                }
                
                $('#pulse_id').val(pulse_id);    
                $('#otx_activity').prop('checked', false);
                $('#btnsearch').trigger('click');
            })
            .on('input', function()
            {
                if ($(this).val() == '')
                {
                    $("#pulse_id").val('');
                }                
            });
            
            
            $("#ds_name").autocomplete('providers/event_type_autocomplete.php', 
            {
                minChars: 0,
                matchContains: "word",
                multiple: false,
                autoFill: false,
                formatItem: function(row, i, max, value) 
                {
                    return (value.split('###'))[1];
                },
                formatResult: function(data, value) 
                {
                    return (value.split('###'))[1];
                }
            }).result(function(event, item) 
            {
                $("#ds_id").val((item[0].split('###'))[0]);
            });


            $('td.tipd').click(function(e) 
            {
                e.stopPropagation();

                var check = $(this).find('input');

                if(!$(check).attr('disabled'))
                {
                    if( $(check).is(":checked") )
                    {
                        $(check).removeAttr("checked");
                    }
                    else
                    {
                        $(check).attr("checked","checked");
                    }
                }
            });


            // Data table
            alarm_table = $('.table_data').dataTable(
            {
                "bProcessing": true,
                "bServerSide": true,
                "bDeferRender": true,
                "sAjaxSource": "providers/alarm_console_ajax.php?<?php echo $params_alarm ?>",
                "iDisplayLength": <?php echo ($num_alarms_page > 0) ? $num_alarms_page : 20 ?>,
                "bLengthChange": true,
                "sPaginationType": "full_numbers",
                "bFilter": false,
                "aLengthMenu": [[10, 20, 50, 100, 250, 500], [10, 20, 50, 100, 250, 500]],
                "bJQueryUI": true,
                "aaSorting": [[ <?php echo $order ?>, "<?php echo $torder ?>" ]],
                "aoColumns": [
                    { "bSortable": false, sWidth: "30px" },
                    { "bSortable": true},
                    { "bSortable": true, sWidth: "60px" },
                    { "bSortable": false, "sClass": "left", "bVisible": false },
                    { "bSortable": true, "sClass": "left" },
                    { "bSortable": true, "sClass": "left" },
                    { "bSortable": true, sWidth: "50px" },
                    { "bSortable": false, "sClass": "center", sWidth: "50px" },
                    { "bSortable": true, "sClass": "left nowrap" },
                    { "bSortable": true, "sClass": "left nowrap" },
                    { "bSortable": false, sWidth: "30px" }
                ],
                oLanguage : {
                    "sProcessing": "&nbsp;<?php echo _('Loading alarms') ?> <img src='/ossim/pixmaps/loading3.gif'/>",
                    "sLengthMenu": "&nbsp;Show _MENU_ entries",
                    "sZeroRecords": "&nbsp;<?php echo _('No matching records found') ?>",
                    "sEmptyTable": "&nbsp;<?php echo _('No alarms found in the system') ?>",
                    "sLoadingRecords": "&nbsp;<?php echo _('Loading') ?>...",
                    "sInfo": "&nbsp;<?php echo _('Showing _START_ to _END_ of _TOTAL_ alarms') ?>",
                    "sInfoEmpty": "&nbsp;<?php echo _('Showing 0 to 0 of 0 alarms') ?>",
                    "sInfoFiltered": "(<?php echo _('filtered from _MAX_ total alarms') ?>)",
                    "sInfoPostFix": "",
                    "sInfoThousands": ",",
                    "sSearch": "<?php echo _('Search') ?>:",
                    "sUrl": "",
                    "oPaginate": {
                        "sFirst":    "<?php echo _('First') ?>",
                        "sPrevious": "<?php echo _('Previous') ?>",
                        "sNext":     "<?php echo _('Next') ?>",
                        "sLast":     "<?php echo _('Last') ?>"
                    }
                },
                "fnRowCallback" : function(nRow, aData)
                {
                    var component_id = aData['DT_RowId'];

                    if (draw_label)
                    {
                        $.each(aData['tags'], function(index, tag) {
                            var $tag = draw_tag(tag, component_id, check_label_column);
                            $tag.appendTo($('td:eq(3)', nRow).find('.a_label_container'));
                        });
                    }
                
                    // No wrap for src/dst column
                    var offset = ($('td',nRow).length == 11) ? 0 : 1;
                    
                    //src
                    var $src = $('td:eq('+ (8 - offset) +')',nRow);

                    if ($src.text().match(/\d+\.\d+\.\d+\.\d+/))
                    {
                        $src.attr("nowrap","nowrap");
                    }
                    else
                    {
                        $src.removeAttr("nowrap");
                    }

                    //dst                       
                    var $dst = $('td:eq('+ (9 - offset) +')',nRow);
                    if ($dst.text().match(/\d+\.\d+\.\d+\.\d+/))
                    {
                        $dst.attr("nowrap","nowrap");
                    }
                    else
                    {
                        $dst.removeAttr("nowrap");                    
                    }
                    
                    if ($('td:eq('+ (5 - offset) +')',nRow).html() == '')
                    {
                        $('td:eq('+ (4 - offset) +')',nRow).attr('colspan', 2);
                        $('td:eq('+ (5 - offset) +')',nRow).hide();
                    }
                    
                },
                "fnDrawCallback" : function(oSettings)
                {
                    // Load callbacks, tiptips and more
                    count_of_all_alarm =  oSettings._iRecordsTotal;
                    load_handlers();
                    chk_actions();
                    selectedAlarm();
                },
                "fnInitComplete": function()
                {
                    // show contents
                    $('#chkall div.DataTables_sort_wrapper').css('padding-right','0px');
                },
                "fnServerData": function ( sSource, aoData, fnCallback, oSettings ) 
                {
                    draw_label = false;
                    
                    oSettings.jqXHR = $.ajax( 
                    {
                        "dataType": 'json',
                        "type": "POST",
                        "url": sSource,
                        "data": aoData,
                        "success": function (json) 
                        {                            
                            $(oSettings.oInstance).trigger('xhr', oSettings);

                            //This is for keeping pagination whe the page is back from alarm detail.
                            oSettings.iInitDisplayStart = oSettings._iDisplayStart;
                            if (json.iDisplayStart !== undefined) 
                            {
                                oSettings.iInitDisplayStart = json.iDisplayStart;
                            }
                            draw_label = json.show_label;
                            display_datatables_column(draw_label);

                            fnCallback(json);
                        },
                        "error": function(data)
                        {
                            //Check expired session
                            var session = new Session(data, '');
                            
                            if (session.check_session_expired() == true)
                            {
                                session.redirect();
                                return;
                            }

                            var json = $.parseJSON('{"sEcho": '+aoData[0].value+', "iTotalRecords": 0, "iTotalDisplayRecords": 0, "aaData": "" }')
                            fnCallback( json );
                        }
                    } );
                }
            });

            alarm_session_db.clean_checked();

            $('#alarm_graph').on('load', postload_graph);

            $('#dropdown-2').on('show', function(event,data)
            {
                dd_alarm = data.trigger.data('backlog');
            });
            
            $('#otx_activity').on('change', function(e, val)
            {
                $("#pulse_id").val('');
                $('#btnsearch').trigger('click');
            });
            
            $('#no_resolv').on('click', function()
            {
                $('#btnsearch').trigger('click');
            });
            
            $('#hide_closed').on('click', function()
            {
                $('#btnsearch').trigger('click');
            });
            
            $('#beep').on('click', function()
            {
                $('#btnsearch').trigger('click');
            });

        });

        var alt_pressed = false;
        
        $(window).bind('keydown.alt keydown.ctrl', function(event) 
        {
            alt_pressed = true;
            
        }).bind('keyup.alt keyup.ctrl', function(event) 
        {
            alt_pressed = false;
            
        });

        // Redraw table on Mange tags lightbox close
        function GB_onclose(url)
        {
            if (url.match('/tags/'))
            {
                alarm_table.fnDraw();
            }
        }

        function GB_onhide(url)
        {
            if (url.match('/tags/'))
            {
                alarm_table.fnDraw();
            }
        }
        
    </script>
</head>

<body>

<div id='counter_container'>
    <div id='counter'>
        <?php echo _('Next refresh in') ?> <span id='reload'><?php echo $refresh_time_secs ?></span> <?php echo _('seconds') ?>.
        <a href='javascript:;' onclick='time=0;reload_alarms();'> <?php echo _('Or click here to refresh now') ?></a>
    </div>
    <div id='breadcrum'></div>
</div>



<?php

if (!empty($_SESSION["_delete_msg"])) 
{
    echo ossim_error($_SESSION["_delete_msg"], AV_WARNING);
    $_SESSION["_delete_msg"] = "";
}

if (empty($order))
{ 
    $order = " a.timestamp DESC";
}

if ((!empty($src_ip)) && (!empty($dst_ip))) 
{
    $where = "WHERE inet_ntoa(src_ip) = '$src_ip' OR inet_ntoa(dst_ip) = '$dst_ip'";
} 
elseif (!empty($src_ip)) 
{
    $where = "WHERE inet_ntoa(src_ip) = '$src_ip'";
} 
elseif (!empty($dst_ip)) 
{
    $where = "WHERE inet_ntoa(dst_ip) = '$dst_ip'";
} 
else 
{
    $where = '';
}

//Datasource filter
$plugin_id  = "";
$plugin_sid = "";

if ( !empty($ds_id) )
{
    $ds = explode("-", $ds_id);
    $plugin_id  = $ds[0];
    $plugin_sid = $ds[1];
}

// Asset filter
if ($host_id != "")
{
    if ($host_obj = Asset_host::get_object($conn, $host_id))
    {
        $asset_filter = $host_obj->get_name();    
    }
}

if ($net_id != "")
{
    if ($net_obj = Asset_net::get_object($conn, $net_id))
    {
        $asset_filter = $net_obj->get_name();    
    }
}

?>
    

<?php
if (!isset($_GET["hide_search"])) 
{
    ?>
    
    <div id='info_delete'>
        <img src='../pixmaps/loading3.gif'/>
        <span id='delete_data'><?php echo _("Deleting selected alarms.  Please, wait a few seconds")?>...</span>
    </div>
    
    <div>
        <div class="filters uppercase">
            <img id='search_arrow' src='/ossim/pixmaps/arrow_down.png' />
            <a href='javascript:;' onclick="toggle_filters()"><?php echo _('Search and filter') ?></a>
        </div>
        
        <div id='report_icon_container'>
            <div id='graph_toggle_legend'> <?php echo _('Show Alarm Graph') ?></div>
            <div id='graph_toggle' class='toggle_button'></div>
            
            <a href='../report/sec_report.php?section=all&type=alarm&back=alarm' class='tip greybox2' title='<?php echo _('Alarm Report') ?>' style='text-decoration:none;'>
                <img src='../pixmaps/menu/reports_menu.png' class="gray_img" width="15" />
            </a>
        </div>
    </div>
    
    <div class='clear_layer'></div>

    <div id='alarm_params'>

        <form method="get" id="queryform" name="filters">

            <div class='p_column'>
                
                <?php
                // Some external filters (asset, ctx)
                // **** Probably deprecated ****
                if ($host_id != "" || $net_id != "") 
                { 
                    ?>
                    <label for='asset'><?php echo _("Asset")?></label>
                    <a href="javascript:;" onclick="$('#host_id').val('');$('#net_id').val('');$('#btnsearch').click()"><?php echo $asset_filter ?></a>
                    <input type="hidden" name="host_id" id="host_id" value="<?php echo $host_id ?>" />
                    <input type="hidden" name="net_id" id="net_id" value="<?php echo $net_id ?>" />
                    <?php 
                } 
                
                // **** Probably deprecated ****
                if (Session::is_pro() && $ctx != "") 
                { 
                    ?>
                    <label for='ctx'><?php echo _("Context")?></label>
                    <?php
                    $entity_name = Acl::get_entity_name($conn, $ctx);
                    echo $entity_name;
                    ?>
                    <input type="hidden" name="ctx" value="<?php echo $ctx ?>" />
                    <?php 
                }
                ?>
                
                <label for='sensor_query'><?php echo _('Sensor')?></label>
                <select name="sensor_query" id='sensor_query'>
                    <option value=""></option>
                    <?php 
                    foreach ($asset_sensors[0] as $_sensor_id => $_sensor)
                    { 
                        $selected = ( $sensor_query == $_sensor_id ) ? "selected='selected'" : "";
                        ?>
                        <option value="<?php echo $_sensor_id ?>" <?php echo $selected?>><?php echo $_sensor["name"] ?> (<?php echo $_sensor["ip"] ?>)</option>
                        <?php 
                    } 
                    ?>
                </select>
                
                <label for='query'><?php echo _('Alarm name')?></label>
                <input type="text" name="query" value="<?php echo Util::htmlentities($query) ?>"/>
                
                <label for='src_ip'><?php echo _('Source IP Address')?></label>
                <input type="text" id="src_ip" name="src_ip" value="<?php echo $src_ip ?>"/>
                 
                <label for='dst_ip'><?php echo _('Destination IP Address')?></label> 
                <input type="text" id="dst_ip" name="dst_ip" value="<?php echo $dst_ip ?>"/> 
                
                <?php
                if ($date_from != '' && $date_to != '')
                {
                    $date_text  = '<a title="'. Util::js_entities(_('Clean date filter')) .'" href="javascript:void(0);" id="clean_date_filter">' . _('Date') . '</a>';
                }
                else
                {
                    $date_text  = _('Date');
                }
                ?>
                <label><?php echo $date_text ?></label>
                <div class="datepicker_range">
                    <div class='calendar_from'>
                        <div class='calendar'>
                            <input name='date_from' id='date_from' class='date_filter' type="input" value="<?php echo $date_from ?>" />
                        </div>
                    </div>
                    <div class='calendar_separator'>
                        -
                    </div>
                    <div class='calendar_to'>
                        <div class='calendar'>
                            <input name='date_to' id='date_to' class='date_filter' type="input" value="<?php echo $date_to ?>" />
                        </div>
                    </div>
                </div>

            </div>

            <div class='p_column'>

                <label for='asset_group'><?php echo _('Asset Group')?></label>
                <select name='asset_group' id='asset_group'>
                    <option value=''><?php echo (count($asset_groups) > 0) ? '' : '- '._('No groups found').' -' ?></option>
                    <?php
                    foreach ($asset_groups as $group_id => $group_obj)
                    {
                        $selected = ($asset_group == $group_id) ? 'selected' : '';
                        ?>
                        <option value='<?php echo $group_id ?>' <?php echo $selected ?>><?php echo $group_obj->get_name() ?></option>
                        <?php
                    }
                    ?>
                </select>

                <label for='intent'><?php echo _('Intent')?></label>
                <select name="intent" id='intent'><option value="0"></option>
                <?php
                    $intents = Alarm::get_intents($conn);
                    foreach ($intents as $kingdom_id => $kingdom_name)
                    {
                        $selected = ($kingdom_id==$intent) ? "selected" : "";
                        echo '<option value="'.$kingdom_id.'" '.$selected.'>'.Util::htmlentities($kingdom_name).'</option>';
                    }
                ?>
                </select>
                
                <label for='directive_id'><?php echo _('Directive ID')?></label>
                <input type="text" name="directive_id" value="<?php echo $directive_id?>"/>
                
                <label for='ds_name'><?php echo _('Contains the Event Type')?></label>
                <input type="text" name="ds_name" id='ds_name' value="<?php echo Util::htmlentities($ds_name)?>" onchange="if (this.value=='') $('#ds_id').val('')"/>
                <input type="hidden" name="ds_id" id='ds_id' value="<?php echo $ds_id?>"/>
                
                <label for='num_events'><?php echo _('Number of events in alarms')?></label>
                <select name="num_events_op" id='num_events_op' class="alarms_op">
                    <option value="less" <?php if ($num_events_op == "less") echo "selected='selected'"?>>&lt;=</option>
                    <option value="more" <?php if ($num_events_op == "more") echo "selected='selected'"?>>&gt;=</option>
                </select>
                &nbsp;<input type="text" name="num_events" id='num_events' size='3' value="<?php echo $num_events ?>" class="alarms_op_value"/>
                <label><?php echo _('Risk level in alarms')?></label>

                <div id="asset_value_slider" class="filter_left_slider">
                <?php
                $risks = array(
                    _("Low"),_("Medium"),_("High")
                );
                $risk_selected = function($risk,$key,$risk_selected) {
                    $selected = $key == $risk_selected ? "selected='selected'" : "";
                    echo "<option value='$key' $selected>"._($risk)."</option>";
                };
                ?>
                <select class="filter_range hidden" id="arangeA" name="min_risk">
                    <?php array_walk($risks,$risk_selected,$min_risk);?>
                </select>
                <select class="filter_range hidden" id="arangeB" name="max_risk">
                    <?php array_walk($risks,$risk_selected,$max_risk);?>
                </select>
                </div>
            </div>
            <div class='p_column'>
                
                <label for='tag'>
                    <?php echo _('Label') ?>
                    <?php
                    if (Session::am_i_admin())
                    {
                    ?>
                    <a class='tags_edit'>[<?php echo _("Manage Labels") ?>]</a>
                    <?php
                    }
                    ?>
                </label>
                <select id='tag' class='ag_param' name='tag'> 
                    <option value=''></option>
                    <?php 
                    foreach ($tags as $t)
                    {
                        $selected = ($t->get_id() == $tag) ? ' selected' : '';
                        echo '<option value="'. $t->get_id() .'" '.$selected.'>'. $t->get_name() .'</option>';
                    }
                    ?>
                </select><br/>
                                
                
                <?php
                $hide_closed     = ( $hide_closed == 1 ) ? 1 : 0;
                $not_hide_closed = !$hide_closed;
                $not_no_resolv   = !$no_resolv;
                $not_beep        = !$beep;
                $not_otx         = !$otx_activity;
                
                $checked_resolv  = ($no_resolv)    ? " checked='checked'" : "";
                $checked_hclosed = ($hide_closed)  ? " checked='checked'" : "";
                $checked_beep    = ($beep)         ? " checked='checked'" : "";
                $checked_otx     = ($otx_activity) ? " checked='checked'" : "";
                
                $refresh_url    .= implode("&", $parameters);
                ?>
                
                <label for='pulse_name'><?php echo _('OTX Pulse')?></label> 
                <input type="hidden" id="pulse_id" name="pulse_id" value="<?php echo $pulse_id ?>"/>
                <input type="text" id="pulse_name" value="<?php echo $pulse_name ?>"/> <br/><br/>
                
                <input id='otx_activity' name="otx_activity" type="checkbox" value="1" <?php echo $checked_otx ?> />
                <label class='line' for='otx_activity'><?php echo _("Only OTX Pulse Activity"); ?></label><br/><br/>
                
                <input id="no_resolv" name="no_resolv" type="checkbox" value="1" <?php echo $checked_resolv?> />
                <label class='line' for='no_resolv'><?php echo _("Do not resolve ip names"); ?></label><br/><br/>
                
                <input id="hide_closed"  name="hide_closed" type="checkbox" value="1" <?php echo $checked_hclosed?> />
                <label class='line' for='hide_closed'><?php echo _("Hide closed alarms"); ?></label><br/><br/>
                
                <input id="beep" name="beep" type="checkbox" value="1" <?php echo $checked_beep?> />
                <label class='line' for='beep'><?php echo _("Beep on new alarm"); ?></label><br/>
                
            </div>
            
            <div class='search_button_container'>
                <input type="submit" name='search' id='btnsearch' value="<?php echo _("Search") ?>"/>
            </div>
        </form>
     </div>
<?php
} 
?>
    <!-- ALARM GRAPH -->
    
    <div id="graph_container">
        <div id="graph_overlay">
            <?php echo _('Loading') ?> 
            <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>
        </div>
        <iframe src="" name="alarm_graph" id="alarm_graph" frameborder="0"></iframe>
    </div>  
     
    <div class='clear_layer'></div>

    <!-- ALARM LIST -->
    <div id='alarm_list'>
            <div id ='alarm_console_button_list'>
                
                <img id="btn_al" class="button_labels av_b_secondary" src="/ossim/pixmaps/label.png" />
                <button id='button_action' class='small' data-dropdown="#dropdown-actions">
                    <?php echo _('Actions') ?> &nbsp;&#x25be;
                </button>               
            </div>

        <div  class="hidden" id="selectall" style=" text-align: center; position: relative; top: 24px;  z-index: 100; ">
            <?=sprintf(_("You have selected %s alarms on this page."),'<span></span>')?>
            <a href="#" onclick="$('#selectall').hide(); $('#allcheck').prop('checked', true);  selection_type = 'all';  alarm_session_db.clean_checked(); return false;">
                <?=sprintf(_("Select all %s alarms."),'<span></span>')?>
            </a>
        </div>

        <table class='table_data'>
            <thead>
                <tr>
                    <th class="center" id="chkall"><input type="checkbox" id="allcheck" name="allcheck" onclick="checkall(this)"></th>
                    
                    <th>
                        <?php echo _("Date") ?>
                    </th>
                    
                    <th>
                        <?php echo _("Status") ?></a>
                    </th>
                    
                    <th>
                        <?php echo _("Labels") ?>
                    </th>

                    <th>
                        <?php echo _("Intent & Strategy") ?>
                    </th>
                    
                    <th>
                        <?php echo _("Method") ?>
                    </th>

                    <th>
                        <?php echo _("Risk")?>
                    </th>
                    
                    <th>
                        <?php echo _("OTX") ?>
                    </th>
                    
                    <th>
                        <?php echo _("Source") ?>
                    </th>

                    <th>
                        <?php echo _("Destination") ?>
                    </th>
                    
                    <th>
                    </th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>

    <?php if ( Session::menu_perms("analysis-menu", "ControlPanelAlarmsDelete") ) { ?>
    <a href="javascript:;" class="fright" style="padding-bottom:15px;" onclick="delete_all_alarms();">
        <?php echo _("Delete ALL"); ?>
    </a>
    <?php } ?>
    
    
    <div id="dropdown-actions"  class="dropdown dropdown-close dropdown-tip dropdown-anchor-right">
        <ul class="dropdown-menu">
            <li><a href="#1" id="btn_ds" onclick="open_alarm();"><?php echo _('Open Alarm') ?></a></li>
            <?php if ( Session::menu_perms("analysis-menu", "ControlPanelAlarmsClose") ) {?>
                <li><a href="#2" id="btn_cs" onclick="bg_close();"><?php echo _('Close Alarm') ?></a></li>
            <?php }
            if ( Session::menu_perms("analysis-menu", "ControlPanelAlarmsDelete") ) { ?>
                <li><a href="#3" id="btn_ds" onclick="selection_type == 'all' ?  delete_all_alarms() : bg_delete();"><?php echo _('Delete Alarm') ?></a></li>
            <?php } ?>
        </ul>
    </div>

</body>
</html>

<?php
$db->close();
