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
$backup_inf      = $inf = GET('inf');
$sup             = GET('sup');
$hide_closed     = GET('hide_closed');
$no_resolv       = intval(GET('no_resolv'));

$host_id         = GET('host_id');
$net_id          = GET('net_id');
$ctx             = GET('ctx');

$autorefresh     = "";
$refresh_time    = "";

if ( isset($_GET['search']) )
{
    unset($_SESSION['_alarm_autorefresh']);
    if ( isset($_GET['autorefresh']) )
    {
        $autorefresh  = ( GET('autorefresh') != '1' ) ? 0 : 1;
        $refresh_time = GET('refresh_time');
        $_SESSION['_alarm_autorefresh'] = GET('refresh_time');
    }
}
else
{
    if ( $_SESSION['_alarm_autorefresh'] != '' )
    {
        $autorefresh  = 1;
        $refresh_time = $_SESSION['_alarm_autorefresh'];
    }
}

$query            = (GET('query') != "") ? GET('query') : "";
$directive_id     = GET('directive_id');
$intent           = intval(GET('intent'));
$sensor_query     = GET('sensor_query');
$tag              = GET('tag');
$num_events       = GET('num_events');
$num_events_op    = GET('num_events_op');
$date_from        = GET('date_from');
$date_to          = GET('date_to');
$ds_id            = GET('ds_id');
$ds_name          = GET('ds_name');
$beep             = intval(GET('beep'));
$num_alarms_page  = (GET('num_alarms_page') != "") ? intval(GET('num_alarms_page')) : 20;


$tags             = Tags::get_list($conn);
$tags_html        = Tags::get_list_html($conn,"",false);


//$asset_data
$asset_sensors    = Av_sensor::get_list($conn, array(), FALSE, TRUE);

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
ossim_valid($autorefresh,     OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Autorefresh"));
ossim_valid($refresh_time,    OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Refresh_time"));
ossim_valid($directive_id,    OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Directive_id"));
ossim_valid($intent,          OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Intent"));
ossim_valid($src_ip,          OSS_IP_ADDRCIDR_0, OSS_NULLABLE,                              'illegal:' . _("Src_ip"));
ossim_valid($dst_ip,          OSS_IP_ADDRCIDR_0, OSS_NULLABLE,                              'illegal:' . _("Dst_ip"));
ossim_valid($inf,             OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Inf"));
ossim_valid($sup,             OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Order"));
ossim_valid($hide_closed,     OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Hide_closed"));
ossim_valid($date_from,       OSS_DATETIME_DATE, OSS_NULLABLE,                              'illegal:' . _("From date"));
ossim_valid($date_to,         OSS_DATETIME_DATE, OSS_NULLABLE,                              'illegal:' . _("To date"));
ossim_valid($sensor_query,    OSS_HEX, OSS_NULLABLE,                                        'illegal:' . _("Sensor_query"));
ossim_valid($tag,             OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Tag"));
ossim_valid($num_events,      OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Num_events"));
ossim_valid($num_events_op,   OSS_ALPHA, OSS_NULLABLE,                                      'illegal:' . _("Num_events_op"));
ossim_valid($ds_id,           OSS_DIGIT, "-", OSS_NULLABLE,                                 'illegal:' . _("Datasource"));
ossim_valid($beep,            OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Beep"));
ossim_valid($host_id,         OSS_HEX, OSS_NULLABLE,                                        'illegal:' . _("Host ID"));
ossim_valid($net_id,          OSS_HEX, OSS_NULLABLE,                                        'illegal:' . _("Net ID"));
ossim_valid($ctx,             OSS_HEX, OSS_NULLABLE,                                        'illegal:' . _("CTX"));
ossim_valid($num_alarms_page, OSS_DIGIT, OSS_NULLABLE,                                      'illegal:' . _("Field number of alarms per page"));

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
$parameters['date_from']              = "date_from="      .urlencode($date_from);
$parameters['date_to']                = "date_to="        .urlencode($date_to);
$parameters['sensor_query']           = "sensor_query="   .$sensor_query;
$parameters['tag']                    = "tag="            .$tag;
$parameters['num_alarms_page']        = "num_alarms_page=".$num_alarms_page;
$parameters['num_events']             = "num_events="     .$num_events;
$parameters['num_events_op']          = "num_events_op="  .$num_events_op;
$parameters['refresh_time']           = "refresh_time="   .$refresh_time;
$parameters['autorefresh']            = "autorefresh="    .$autorefresh;
$parameters['ds_id']                  = "ds_id="          .$ds_id;
$parameters['ds_name']                = "ds_name="        .urlencode($ds_name);
//$parameters['bypassexpirationupdate'] = "bypassexpirationupdate=1";
$parameters['beep']                   = "beep="           .$beep;
$parameters['host_id']                = "host_id="        .$host_id;
$parameters['net_id']                 = "net_id="         .$net_id;
$parameters['ctx']                    = "ctx="            .$ctx;

if (empty($refresh_time) || ($refresh_time != 30000 && $refresh_time != 60000 && $refresh_time != 180000 && $refresh_time != 600000))
    $refresh_time = 60000;


$params_alarm = implode("&", $parameters);
$refresh_url  = "alarm_console.php?". $params_alarm;

//Autocompleted
$autocomplete_keys = array('hosts');
$hosts_str         = Autocomplete::get_autocomplete($conn, $autocomplete_keys);


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title><?php echo _("Alarm Console")?> </title>
    <meta http-equiv="Pragma" content="no-cache"/>
    
    <link rel="stylesheet" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
    <link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css">
    <?php 
    if ( $autorefresh ) 
    { 
        ?>
        <script type="text/javascript">
            setInterval("refresh_function()", <?php echo $refresh_time ?>);
            
            function refresh_function() 
            {
                if (!GB_DONE && is_toggled < 1) {
                    document.location.href='<?php echo $refresh_url ?>';
                }
            }
        </script>
        <?php 
    } 
    ?>
    <script type="text/javascript" src="../js/jquery.min.js"></script>
    <script type="text/javascript" src="../js/jquery-ui.min.js"></script>
    <script type="text/javascript" src="../js/greybox.js"></script>
    <script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>
    
    <script type='text/javascript' src='/ossim/js/notification.js'></script>
    <script type='text/javascript' src='/ossim/js/utils.js'></script>

    <!-- Hotkeys: -->
    <script type="text/javascript" src="/ossim/js/jquery.hotkeys.js"></script>

    <!-- JQuery TipTip: -->
    <link rel="stylesheet" type="text/css" href="/ossim/style/tipTip.css"/>
    <script type="text/javascript" src="/ossim/js/jquery.tipTip-ajax.js"></script>

    <!-- JQuery DataTables: -->
    <script type="text/javascript" src="/ossim/js/jquery.dataTables.js"></script>
    <script type="text/javascript" src="/ossim/js/jquery.dataTables.plugins.js"></script>
    <link rel="stylesheet" type="text/css" href="/ossim/style/jquery-ui.css"/>  
    
    <link rel="stylesheet" type="text/css" href="/ossim/style/jquery.dataTables.css"/>
    
    <!-- Token -->
	<script type="text/javascript" src="/ossim/js/utils.js"></script>
	<script type="text/javascript" src="/ossim/js/token.js"></script>

    <!-- Spark Line: -->
    <script type="text/javascript" src="/ossim/js/jquery.sparkline.js"></script>

    <script type="text/javascript" src="/ossim/js/jquery.spin.js"></script>

    <link rel="stylesheet" type="text/css" href="/ossim/style/alarm/console.css"/>
    
    <link rel="stylesheet" type="text/css" href="/ossim/style/datepicker.css"/>
        
    <?php require '../host_report_menu.php';?>
    
    <script language="javascript">
    
        var is_toggled   = 0; // Count of toggled groups (for disable autorefresh)
        var refres_time  = <?php echo $refresh_time_secs ?>;
        var time         = refres_time;
        var timeout_rfh  = false;        
        var flah_bg      = false;
        var tip_timeout  = null;
        var quicktip     = false;
        var alarm_table  = false;
        var graph_change = true;
        var graph_filter = false;
        
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
  
        function reload_alarms(){

            if(time == 0)
            {
                $('#reload').text('0');

                if(alarm_table)
                {
                    remove_tooltip();
                    if (!graph_filter)
                    {
                        $('#alarm_graph').hide();
                        $('#graph_overlay').show();                    
                    }
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
                
        function tray_labels(backlog_id)
        {
            select_tray_input(backlog_id);
            toogle_tags('tags_content_'+backlog_id,false);
            chk_actions();
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
                
        function checkall (that) 
        {
            var status = that.checked;

            $("input[type=checkbox]").each(function() 
            {
                if (this.id.match(/^check_[0-9A-Z]+/)) 
                {
                    if(!$(this).prop('disabled'))
                    {
                        $(this).prop('checked', status);
                    }
                }
            });
            
            chk_actions();
        }
        
        function toggle_filters()
        {
            if($('#searchtable').css('display') == 'none')
            {
                $('#search_arrow').attr('src', '/ossim/pixmaps/arrow_down.png');
            }
            else
            {
                $('#search_arrow').attr('src', '/ossim/pixmaps/arrow_right.png');
            }

            $('#searchtable').toggle();
    
            if (!showing_calendar) 
            {
                calendar();
            }

        }
        
        var showing_calendar = false;
        
        function calendar() {
            showing_calendar = true;
            // CALENDAR
            
            $('.date_filter').datepicker({
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

        
        function set_hand_cursor() {
            document.body.style.cursor = 'pointer';
        }
        
        function set_pointer_cursor() {
            document.body.style.cursor = 'default';
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
            
            $.ajax({
                type: "POST",
                url: "alarm_ajax.php?token="+atoken,
                async:false,
                data: {"action": 3, "data": {"alarms": checked} },
                success: function(msg){
                    
                }
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
                    GB_show_multiple('<?php echo _("Alarm Detail") ?>','alarm_detail.php?backlog=' + id,600,'80%');
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
                    GB_show_multiple('<?php echo _("Event Detail") ?>',url.replace(/EVENTID/,id),600,'80%');
                }
                else
                {
                    document.location.href = url.replace(/EVENTID/,id);
                }
            }

        }

        function bg_close() 
        {
            var close_msg = "<?php echo Util::js_entities(_("You are going to close the selected alarm(s). Would you like to continue?")) ?>";
            
            av_confirm(close_msg).done(function()
            {  
                $('#delete_data').html("<?php echo _("Closing selected alarm ...") ?>");            
                $('#info_delete').show();
                
                var params = "";
                $(".alarm_check").each(function()
                {
                    if ( $(this).is(':checked') ) {
                        params += "&"+$(this).attr('name')+"=1";
                    }
                });
                
                var atoken = Token.get_token("alarm_operations");
                
                $.ajax({
                    type: "POST",
                    url: "alarms_check_delete.php?token=" + atoken,
                    data: "background=1&only_close=1" + params,
                    success: function(msg)
                    {
                        $('#delete_data').html('<?php echo _("Reloading alarms ...") ?>');
                        document.location.href='<?php echo $refresh_url?>';
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown) 
                    {
                            $('#info_delete').hide();
                            notify(textStatus, 'nf_error');
                    }
                });
                
            });
            
        }

        function open_alarm(id) 
        {
            var open_msg = "<?php echo Util::js_entities(_("You are going to open the selected alarm. Would you like to continue?")) ?>";
            
            av_confirm(open_msg).done(function()
            {
            
                $('#delete_data').html("<?php echo _("Opening selected alarm ...") ?>");
                $('#info_delete').show();            
    
                var atoken = Token.get_token("alarm_operations");
                
                $.ajax(
                {
                    type: "POST",
                    url: "alarm_ajax.php?token="+atoken,
                    dataType: "json",
                    data: {"action": 2, "data": {"id": id} },
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
        
        function remove_alarm_label(label)
        {
            var alarm_id  = $(label).parents('tr').attr('id');
            var label_id  = $(label).data('tag');
            
            //Settting data array with params
            var data      = {};
            
            data['alarm'] = alarm_id;
            data['label'] = label_id;
            
            var atoken = Token.get_token("alarm_operations");

            $.ajax(
            {
                type: "POST",
                dataType: "json",
                url: "alarm_ajax.php?token="+atoken,
                data: {"action": 9, "data": data},
                success: function(data)
                {
                    $('#delete_data').html('');
                    $('#info_delete').hide();
                    
                    if (typeof data == 'undefined')
                    {
                        alarm_notification("<?php echo _('Unable to remove label') ?>", 'nf_error');
                        
                        return false;
                    }
                    
                    if (data.error != true)
                    {
                        $(label).parent('div').remove();
                    }
                    else
                    {
                        alarm_notification(data.msg, 'nf_error');
                    }
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) 
                {
                    alarm_notification(errorThrown, 'nf_error');
                }
            });
            
        }

        function add_alarm_label(tag)
        {
            var alarms    = get_alarms_checked();
            
            //Settting data array with params
            var data      = {};
            
            data['alarms'] = alarms;
            data['label']  = tag;
            
            var atoken = Token.get_token("alarm_operations");
            
            $.ajax(
            {
                type: "POST",
                dataType: "json",
                url: "alarm_ajax.php?token="+atoken,
                data: {"action": 8, "data": data},
                success: function(data)
                {
                    $('#delete_data').html('');
                    $('#info_delete').hide();
                    
                    
                    if (typeof data == 'undefined')
                    {
                        alarm_notification("<?php echo _('Unable to remove label') ?>", 'nf_error');
                        
                        return false;
                    }
                    
                    if (data.error != true)
                    {
                        label = data.data;
                        
                        display_datatables_column(true);
                        
                        $(".alarm_check:checked").each(function()
                        {
                            var row  = $(this).parents('tr');  
                            var cell = $('td',row).eq(3);
                            
                            if ($('div.tag_'+tag, cell).length == 0)
                            {
                                $(cell).append(label);
                            }
    
                        });
                        
                        
                        $('.remove_tag').off('click').on('click', function(e)
                        {
                            e.stopPropagation();
                            e.preventDefault();
                            
                            remove_alarm_label(this);
                
                        });
                    }
                    else
                    {
                        alarm_notification(data.msg, 'nf_error');
                    }

                },
                error: function(XMLHttpRequest, textStatus, errorThrown) 
                {
                    alarm_notification(errorThrown, 'nf_error');
                }
            });
        }

        function check_background_tasks(times){

            var bg     = false;
            var atoken = Token.get_token("alarm_operations");
            
            $.ajax({
                type: "POST",
                url: "alarm_ajax.php?token="+atoken,
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
                
                av_confirm(msg_delete).done(function()
                {
                    $('#info_delete').show();
                    
                    var params = "";
                    $(".alarm_check").each(function()
                    {
                        if ( $(this).is(':checked') ) {
                            params += "&"+$(this).attr('name')+"=1";
                        }
                    });
                    
                    var atoken = Token.get_token("alarm_operations");
                    
                    $.ajax({
                        type: "POST",
                        url: "alarms_check_delete.php?token=" + atoken,
                        data: "background=1" + params,
                        success: function(msg){
                            $('#delete_data').html('<?php echo _("Reloading alarms ...") ?>');
                            document.location.href='<?php echo $refresh_url?>';
                        },
                        error: function(XMLHttpRequest, textStatus, errorThrown) {
                                $('#info_delete').hide();
                                notify(textStatus, 'nf_error');
                        }
                    });
                    
                });
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
                        
                        $.ajax({
                            type: "POST",
                            url: "alarm_ajax.php?token="+atoken,
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
                            url: "alarm_ajax.php?token="+atoken,
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
            
            <?php
        }
        ?>
        
        function load_trend(layer,pid,sid) 
        {
            $.ajax({
                data:  { "plugin_id": $('#'+layer).attr('data-pid'), "plugin_sid": $('#'+layer).attr('data-sid') },
                type: "GET",
                url: "alarm_trend.php", 
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
                    url:  'alarm_quicklook.php?backlog=' + alarm_data[1],
            });           
        }
            
        function load_handlers()
        {
            if (graph_change )
            {        
                $('#alarm_graph').attr('src','alarm_graph.php');
            }
            else if (!graph_filter) // only if we are not filtering
            {
                graph_change = true;
            }
            // tipTip
            $('.tip').tipTip({maxWidth:'300px'});
            $('td.tipd').tipTip({defaultPosition:"right"});
            $('.scriptinfo').tipTip({
               defaultPosition: "down",
               content: function (e) {
                  
                  var ip_data = $(this).attr('data-title');                      
                  
                  $.ajax({
                      url: 'alarm_netlookup.php?ip=' + ip_data,
                      success: function (response) {
                        e.content.html(response); // the var e is the callback function data (see above)
                      }
                  });
                  return '<?php echo _("Searching")."..."?>'; // We temporary show a Please wait text until the ajax success callback is called.
               }
            });

            // Details icon
            $('.go_details').click(function(e) 
            {
                e.stopPropagation();
                
                var row = $(this).parents('tr');
                
                var alarm_type = $('td:eq(0) input',row).attr('data'); // data="alarm|event"
                var alarm_data = $('td:eq(0) input',row).attr('name').split('_'); // name="check_backlogid_eventid"
                
                load_alarm_detail(alarm_data[1], alarm_type);
                
            });
            
            // stop propagation
            $('a.stop, input.stop, td.stop').click(function(e) {
                e.stopPropagation();
            });
            
            $('.remove_tag').on('click', function()
            {
                remove_alarm_label(this);

            });          

            $('.table_data tbody tr').on('click', function () 
            {
                n_clicks++;  //count clicks
                
                var row = this;
                
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
                
            }).on('dblclick', function(event) 
            {
                event.preventDefault();
            });
             
            
            $('.HostReportMenu').on('mousedown', function(e)
            {
                if(e.which === 3)
                {
                    if(typeof quicktip.close_tiptip == 'function')
                    {
                        quicktip.close_tiptip();
                    }
                }
                
            });
            
            load_contextmenu();

            // input click
            $('input[name^="check_"]').on('click',function() {
                chk_actions();
            });
        }           
        
        function chk_actions()
        {
            if ($('input[name^="check_"]:checked').length > 0)
            {
                $('#btn_ds').removeAttr('disabled');
                $('#btn_cs').removeAttr('disabled');
                $('#btn_al').removeAttr('disabled');
            }   
            else
            {
                $('#btn_ds').attr('disabled','disabled');
                $('#btn_cs').attr('disabled','disabled');
                $('#btn_al').attr('disabled','disabled');
                
                $('.apply_label_layer').empty();
            }         
        }
        
        
        function display_datatables_column(show)
        {
            // Warning: complete length could change in the future
            var complete_length = 11;
            
            if (show)
            {
                // Prevents for adding two times
                if ($('.table_data thead th').length < complete_length)
                {
                    $('.table_data thead th:eq(2)').after('<th><?php echo _('Labels') ?></th>');
                    $('.table_data tbody tr').each(function() {
                        $('td:eq(2)',this).after('<td></td>');
                    });
                }
            }
            else
            {
                if ($('.table_data thead th').length == complete_length)
                {
                    $('.table_data thead th:eq(3)').remove();
                }
                
                $('.table_data tbody tr').each(function() {
                    if ($('td',this).length == complete_length)
                    {
                        $('td:eq(3)',this).remove();
                    }
                });
                
            }
        }
        
        function toogle_tags(layer,buttons)
        {
            
            if ( $('#'+layer).html() == '' )
            {
                $('.apply_label_layer').empty();
                
                $('#'+layer).html($('#tags').html());
                if (!buttons) // Call from a detail tray 
                {
                    $('#'+layer+' li.tagedit').hide();
                    //$('#'+layer+' ul.labels').css('background-color','#2f2f2f');
                }
            }
            else
            {
                $('#'+layer).empty(); 
                
                if (!buttons) // Call from a detail tray 
                {   
                    input = layer.replace('tags_content_', '#check_');
                    $(input).prop('checked', false);
                }                               

            }
        }

        function set_graph_height(h)
        {
               $('#alarm_graph').animate({ height: h+'px' }, 1000).show(); //height(h+'px').show();
               $('#graph_overlay').hide();
        }

        function reset_intent()
        {
            graph_filter = false;
            var newUrl = '<?php echo "alarm_console_ajax.php?" . implode("&", $parameters) ?>';
            if(alarm_table)
            {
                remove_tooltip();
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
            $url_without_intent = "alarm_console_ajax.php?" . implode("&", $parameters_i);
            ?>
            var newUrl = '<?php echo $url_without_intent ?>&intent='+intent+'&date_from='+dates[0]+'&date_to='+dates[1];
            if(alarm_table)
            {
                remove_tooltip();
                time = refres_time;
                graph_change = false;
                $('#breadcrum').html('<a href="javascript:;" onclick="reset_intent()">All Alarms</a> > '+txt).addClass('marginbottom');
                alarm_table.fnReloadAjax(newUrl);
            }            
        }

        function get_container_offset()
        {
            return $('#graph_container').offset();
        }

        var in_tooltip = false;
        function draw_tooltip(content)
        {
            $('#graph_container').append(content);
            in_tooltip = false;
            $('#graph_container').find('.alarm-info').mouseover(function(){
                in_tooltip = true;
            });
            $('#graph_container').find('.alarm-info').mouseout(function(){
                if (in_tooltip) remove_tooltip();
            });
        }
        
        function remove_tooltip()
        {
            $('#graph_container').find('.alarm-info').remove();
        }
        
        $(document).ready(function(){
        
            $('#clean_date_filter').on('click', function() {
                $('#date_from').val('');
                $('#date_to').val('');
                
                $('#queryform').submit()
                
                return false;
            });
                        
            check_background_tasks(0);  

            reload_alarms();
            
            $('#graph_overlay').spin();
            
            $("a.greybox2").click(function(e){
                e.stopPropagation();
                var t = this.title || $(this).attr('data-title') || this.href;
                GB_show(t,this.href,550,'80%');
                return false;
            });

            // Autocomplete
            var hosts = [<?php echo $hosts_str ?>];
            
            $("#src_ip").autocomplete(hosts, {
                minChars: 0,
                width: 225,
                matchContains: "word",
                autoFill: false,
                formatItem: function(row, i, max) {
                    return row.txt;
                }
            }).result(function(event, item) {
                $("#src_ip").val(item.ip);
            });
            
            $("#dst_ip").autocomplete(hosts, {
                minChars: 0,
                width: 225,
                matchContains: "word",
                autoFill: false,
                formatItem: function(row, i, max) {
                    return row.txt;
                }
            }).result(function(event, item) {
                $("#dst_ip").val(item.ip);
            });
            
            $("#ds_name").autocomplete('search_ds.php', {
                minChars: 0,
                width: 300,
                matchContains: "word",
                multiple: false,
                autoFill: false,
                formatItem: function(row, i, max, value) {
                    return (value.split('###'))[1];
                },
                formatResult: function(data, value) {
                    return (value.split('###'))[1];
                }
            }).result(function(event, item) {
                $("#ds_id").val((item[0].split('###'))[0]);
            });
            
            <?php if (GET('src_ip') != "" || GET('dst_ip') != "" || GET('host_id') != "" || GET('net_id') != "" || $query != "" || $sensor_query != "" || $directive_id != "" || $num_events > 0) { ?>
            toggle_filters();
            <?php } ?>

            $('td.tipd').click(function(e) {
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

            var label_column = false;
            
            // Data table
            alarm_table = $('.table_data').dataTable( {
                "bProcessing": true,
                "bServerSide": true,
                "bDeferRender": true,
                "sAjaxSource": "alarm_console_ajax.php?<?php echo $params_alarm ?>",
                "iDisplayLength": <?php echo ($num_alarms_page > 0) ? $num_alarms_page : 20 ?>,
                "bLengthChange": true,
                "sPaginationType": "full_numbers",
                "bFilter": false,
                "aLengthMenu": [[10, 20, 50, 100], [10, 20, 50, 100]],
                "bJQueryUI": true,
                "aaSorting": [[ <?php echo $order ?>, "<?php echo $torder ?>" ]],
                "aoColumns": [
                    { "bSortable": false, sWidth: "30px" },
                    { "bSortable": true, sWidth: "80px" },
                    { "bSortable": true, sWidth: "60px" },
                    { "bSortable": false, "sClass": "left" },
                    { "bSortable": true, "sClass": "left" },
                    { "bSortable": true, "sClass": "left" },
                    { "bSortable": true, sWidth: "50px" },
                    { "bSortable": false },
                    { "bSortable": true, "sClass": "left" },
                    { "bSortable": true, "sClass": "left" },
                    { "bSortable": false, sWidth: "30px" }
                ],
                oLanguage : {
                    "sProcessing": "&nbsp;<?php echo _('Loading alarms') ?> <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>",
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
                "fnDrawCallback" : function(oSettings) 
                {
                    // hide Label column if there are not content             
                    display_datatables_column(label_column);

                    // Load callbacks, tiptips and more
                    load_handlers();
                    
                    // No wrap for src/dst column
                    $('.table_data tbody tr').each(function() 
                    {
                        //src
                        if ($('td:eq(8)',this).text().match(/\d+\.\d+\.\d+\.\d+/))
                        {
                            $('td:eq(8)',this).attr("nowrap","nowrap");
                        }
                        else
                        {
                            $('td:eq(8)',this).removeAttr("nowrap");
                        }

                        //dst                       
                        if ($('td:eq(9)',this).text().match(/\d+\.\d+\.\d+\.\d+/))
                        {
                            $('td:eq(9)',this).attr("nowrap","nowrap");
                        }
                        else
                        {
                            $('td:eq(9)',this).removeAttr("nowrap");                    
                        }

                    });

                    
                },
                "fnInitComplete": function() {
                    // show contents
                    $('#chkall div.DataTables_sort_wrapper').css('padding-right','0px');
                    
                    <?php
                    // Delete buttons
                    if ( Session::menu_perms("analysis-menu", "ControlPanelAlarmsDelete") )
                    {                        
                    ?>                  
                    var delete_buttons = '<div style="float:right;margin:0 auto;">\
                        <div style="display:inline;position:relative">\
                            <button id="btn_al" class="button av_b_secondary" onclick="toogle_tags(\'tags_content\',true)">\
                            <span style="display:inline"><?php echo _("Apply Label")?></span>&nbsp;&nbsp;<span style="font-size:9px">&#x25BC;</span>\
                            </button>\
                            <div id="tags_content" class="apply_label_layer" style="position:absolute;z-index:99999;left:2px;top:19px"></div>\
                        </div>\
                        <button id="btn_ds" class="button av_b_secondary" onclick="bg_delete();">\
                            <img src="style/img/trash_fill.png" height="14px" align="absmiddle" style="padding-right:8px">\
                            <span style="display:inline"><?php echo _("Delete selected")?></span>\
                        </button>\
                        <button id="btn_cs" class="button av_b_secondary" onclick="bg_close();">\
                            <img src="style/img/unlock.png" height="14px" align="absmiddle" style="padding-right:8px">\
                            <span style="display:inline"><?php echo _("Close selected")?></span>\
                        </button>\
                        </div>';
                    $('div.dt_header').prepend(delete_buttons);                 
                    
                    <?php 
                    }
                    ?>
                    chk_actions();    
                    
                },
                "fnRowCallback": function( nRow, aData, iDisplayIndex, iDisplayIndexFull ) {
                        if ( aData[3] != '' )
                        {
                            label_column = true;
                        }
                        /*
                        // status
                        if ( aData[2].indexOf("open") > 0 ) {
                            $('td:eq(2)', nRow).addClass("opened");
                        } else {
                            $('td:eq(2)', nRow).addClass("closed");
                        }
                        // risk
                        if ( parseInt(aData[5]) > 7 ) {
                            $('td:eq(5)', nRow).addClass("high_risk");
                        } else if ( parseInt(aData[5]) > 4 ) {
                            $('td:eq(5)', nRow).addClass("medium_risk");
                        } else if ( parseInt(aData[5]) > 2 ) {
                            $('td:eq(5)', nRow).addClass("low_risk");
                        }
                        // dst reputation
                        if ( aData[8].indexOf("green") > 0 ) {
                            $('td:eq(8)', nRow).addClass("high_rep");
                        } else if ( aData[8].indexOf("yellow") > 0 ) {
                            $('td:eq(8)', nRow).addClass("medium_rep");
                        } else if ( aData[8].indexOf("red") > 0 ) {
                            $('td:eq(8)', nRow).addClass("low_rep");
                        }
                        // src reputation
                        if ( aData[7].indexOf("green") > 0 ) {
                            $('td:eq(7)', nRow).addClass("high_rep");
                        } else if ( aData[7].indexOf("yellow") > 0 ) {
                            $('td:eq(7)', nRow).addClass("medium_rep");
                        } else if ( aData[7].indexOf("red") > 0 ) {
                            $('td:eq(7)', nRow).addClass("low_rep");
                        }
                        */
                },              
                "fnServerData": function ( sSource, aoData, fnCallback, oSettings ) {
                    fnCallback
                    oSettings.jqXHR = $.ajax( {
                        "dataType": 'json',
                        "type": "POST",
                        "url": sSource,
                        "data": aoData,
                        "success": function (json) {
                            label_column = false;
                            $(oSettings.oInstance).trigger('xhr', oSettings);

                            //This is for keeping pagination whe the page is back from alarm detail.
                            oSettings.iInitDisplayStart = oSettings._iDisplayStart;
                            if (json.iDisplayStart !== undefined) 
                            {
                                oSettings.iInitDisplayStart = json.iDisplayStart;
                            }

                            fnCallback( json );
                        },
                        "error": function(data){
                            
                            //Check expired session
                            var session = new Session(data, '');
                            
                            if (session.check_session_expired() == true)
                            {
                                session.redirect();
                                return;
                            }
                            
                            //Empty table if error
                            label_column = false;
                            var json = $.parseJSON('{"sEcho": '+aoData[0].value+', "iTotalRecords": 0, "iTotalDisplayRecords": 0, "aaData": "" }')
                            fnCallback( json );
                        }
                    } );
                }
            });
            
            $(document).on('mouseleave', '.dataTables_wrapper', function(e)
            {
                var target = e.relatedTarget;

                if(quicktip && $(target).attr('id') != 'tiptip_content')
                {
                    quicktip.close_tiptip();
                    quicktip = false;
                }
                
                $(document).on('click', '.ip_activity_quicklook', function()
                {
                    ip  = $(this).data('ip');
                    url = "http://www.alienvault.com/apps/rep_monitor/ip/<? echo Session::is_pro() ? 'usm' : 'ossim' ?>/"+ip+"/";
                    
                    window.open(url, '_newtab');
                });
               
            });
            
        });

        var alt_pressed = false;
        $(window).bind('keydown.alt keydown.ctrl', function(event) {
            alt_pressed = true;
        }).bind('keyup.alt keyup.ctrl', function(event) {
            alt_pressed = false;
        });    
        
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

if (!isset($_GET["hide_search"])) 
{
    ?>

    <form method="GET" id="queryform" name="filters">

        <input type="hidden" name="tag" value="<?php echo $tag ?>">

        <table width="100%" align="center" class="transparent" cellpadding="0" cellspacing="0">
            <tr>
                <td class="filters">
                    <a href="javascript:;" onclick="toggle_filters()">
                        <span class='uppercase'><img id='search_arrow' src='/ossim/pixmaps/arrow_right.png' /><?php echo _("Search and filter") ?></span>
                    </a>
                </td>
                <td class='noborder left'>
                    <div id='info_delete'>
                        <img src='../pixmaps/loading3.gif' alt='<?php echo _("Deleting selected alarms")?>'/>
                        <span id='delete_data'><?php echo _("Deleting selected alarms.  Please, wait a few seconds")?>...</span>
                    </div>
                </td>
                <td class="nobborder right">
               
                    <div style="position:relative;z-index:99999"> 
                        <div id="tags" style="position:absolute;right:0;top:0;display:none">
                            <ul class="labels">                             
                                <?php if (Session::am_i_admin()) { ?>
                                <li class="tagedit" style="text-align:right"><a href="tags_edit.php" style="font-size:10px;font-weight:normal">[<?php echo _("Edit") ?>]</a></li>
                                <?php }     
                                if (count($tags) < 1) 
                                { 
                                    ?>
                                    <li><?php echo _("No tags found.") ?></li>
                                    <?php 
                                } 
                                else 
                                { 
                                    foreach ($tags as $tg) 
                                    { 
                                        ?>
                                        <li onclick="add_alarm_label('<?php echo $tg->get_id() ?>')">
                                        <?php echo $tags_html[$tg->get_id()]; ?>
                                        </li>
                                        <?php 
                                    }  
                                } 
                                ?>
                            </ul>
                        </div>
                    </div>
                    
                
                </td>
                <td class="nobborder right" style="width:60px"><a href='../report/sec_report.php?section=all&type=alarm&back=alarm' class='tip greybox2' title='<?php echo _('Alarm Report') ?>' style='text-decoration:none;'><img src='../pixmaps/pie_chart.png' class="gray_img" boder='0'/></a></td>
            </tr>
        </table>
        
        
        <table width="100%" align="center" id="searchtable" cellspacing="0" style="border:none;display:none;">
            <tr>
                <th><?php echo _("Filter") ?></th>
                <th width="300"><?php echo _("Options")?></th>
            </tr>
        	<?php
                if ($date_from != '' && $date_to != '')
                {
        	        $date_text  = '<a title="Clean date filter" href="javascript:void(0);" id="clean_date_filter" style="text-decoration: underline;font-weight: bold">' . _('Date') . '</a>';
        	    }
        	    else
        	    {
            	    $date_text  = '<strong>' . _('Date') . '</strong>';
        	    }
            ?>
            <tr>
                <td class="nobborder">
                    <table class="transparent" style='width: 100%'>
                        <?php 
                        if ($host_id != "" || $net_id != "") 
                        { 
                            ?>
                            <tr>
                                <td class='label_filter_l'><strong><?php echo _("Asset")?></strong>:</td>
                                <td class='noborder left' nowrap='nowrap'>
                                <a href="javascript:;" onclick="$('#host_id').val('');$('#net_id').val('');$('#btnsearch').click()"><?php echo $asset_filter ?></a>
                                <input type="hidden" name="host_id" id="host_id" value="<?php echo $host_id ?>">
                                <input type="hidden" name="net_id" id="net_id" value="<?php echo $net_id ?>">
                                </td>
                            </tr>
                            <?php 
                        } 
                        
                        if (Session::is_pro() && $ctx != "") 
                        { 
                            ?>
                            <tr>
                                <td class='label_filter_l'><strong><?php echo _("Context")?></strong>:</td>
                                <td class='noborder left' nowrap='nowrap'>
                                <?php
                                $entity_name = Acl::get_entity_name($conn, $ctx);
                                echo $entity_name;
                                ?>
                                <input type="hidden" name="ctx" value="<?php echo $ctx ?>">
                                </td>
                            </tr>
                            <?php 
                        } 
                        ?>
                        
                        <tr>
                            <td class='label_filter_l'><strong><?php echo _("Sensor")?></strong>:</td>
                            <td class='noborder left' nowrap='nowrap'>
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
                            </td>
                            <td class='noborder left'>
                                <div>
                                    <strong><?php echo _("Intent") ?></strong>:
                                    <select name="intent"><option value="0"></option>
                                    <?php
                                        $intents = Alarm::get_intents($conn);
                                        foreach ($intents as $kingdom_id => $kingdom_name)
                                        {
                                            $selected = ($kingdom_id==$intent) ? "selected" : "";
                                            echo '<option value="'.$kingdom_id.'" '.$selected.'>'.Util::htmlentities($kingdom_name).'</option>';
                                        }
                                    ?>
                                    </select>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class='label_filter_l'><strong><?php echo _("Alarm name")?></strong>: </td>
                            <td class='label_filter_l pl4' nowrap='nowrap'><input type="text" class='inpw_200' name="query" value="<?php echo Util::htmlentities($query) ?>"/></td>
                            <td class='noborder left'>
                                <span style='font-weight: bold;'><?php echo _("Directive ID")?>:</span>
                                <input type="text" class='inpw_200' style='margin-left: 10px;' name="directive_id" value="<?php echo $directive_id?>"/>
                            </td>
                        </tr>
                        <tr>
                            <td class='label_filter_l'><strong><?php echo _("IP Address") ?></strong>:
                            </td>
                            <td class='label_filter_l pl4' nowrap='nowrap'>
                                <div class='label_ip_s'>
                                    <div style='width: 60px; float: left;'><?php echo _("Source") ?>:</div> 
                                    <div style='float: left;'><input type="text" id="src_ip" name="src_ip" value="<?php echo $src_ip ?>"/></div> 
                                </div>
                                <div class='label_ip_d'>
                                    <div style='width: 60px; float: left;'><?php echo _("Destination") ?>:</div> 
                                    <div style='float: left;'><input type="text" id="dst_ip" name="dst_ip" value="<?php echo $dst_ip ?>"/></div> 
                                </div>
                            </td>
                            <td class='noborder left'>
                                <div style="margin-top:3px">
                                    <span style='font-weight: bold;'><?php echo _("Contains the event type")?></span>:
                                    <input type="text" class='inpw_200' style='width:160px;margin-left: 10px;' name="ds_name" id='ds_name' value="<?php echo htmlentities($ds_name)?>" onchange="if (this.value=='') $('#ds_id').val('')"/>
                                    <input type="hidden" name="ds_id" id='ds_id' value="<?php echo $ds_id?>"/>
                                </div>
                                <div style="margin-top:5px">
                                    <strong><?php echo _("Number of events in alarm") ?></strong>:
                                    <select name="num_events_op">
                                        <option value="less" <?php if ($num_events_op == "less") echo "selected='selected'"?>>&lt;=</option>
                                        <option value="more" <?php if ($num_events_op == "more") echo "selected='selected'"?>>&gt;=</option>
                                    </select>
                                    &nbsp;<input type="text" name="num_events" size='3' value="<?php echo $num_events ?>"/>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class='label_filter_l width100' nowrap='nowrap'></td>
                            <td class='label_filter_l pl4' nowrap='nowrap'></td>
                            <td class='noborder left'>
                        </td>
                        </tr>
                        <tr>
                            <td id="date_str" class='label_filter_l width100'><strong><?php echo $date_text ?></strong>:</td>
                            <td class="nobborder">
                                <div class="datepicker_range">
                                    <div class='calendar_from'>
                                        <div class='calendar'>
                                            <input name='date_from' id='date_from' class='date_filter' type="input" value="<?php echo $date_from ?>">
                                        </div>
                                    </div>
                                    <div class='calendar_separator'>
                                        -
                                    </div>
                                    <div class='calendar_to'>
                                        <div class='calendar'>
                                            <input name='date_to' id='date_to' class='date_filter' type="input" value="<?php echo $date_to ?>">
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class='noborder'>
                                <table class="transparent" width="100%" cellspacing="0" cellpadding="0">
                                    <?php
                                    if (count($tags) < 1) 
                                    { 
                                        ?>
                                        <tr>
                                            <td class="nobborder"><?php echo _("No tags found.") ?> <a href="tags_edit.php"><?php echo _("Click here to create") ?></a></td>
                                        </tr>
                                        <?php 
                                    } 
                                    else 
                                    { 
                                        ?>
                                        <tr>
                                            <td class="nobborder">
                                                <div style='text-align: left;width:100%;display:block;'>
                                                    <div style='float:left;'>
                                                        <a style='cursor:pointer' class='ndc uppercase' onclick="$('#tags_filter').toggle()">
                                                            <img src="../pixmaps/arrow_green.png" align="absmiddle" border="0"/>&nbsp;<?php echo _("Filter by label") ?>
                                                        </a>
                                                    </div>
                                                    <?php
                                                    if ( $tag != "" ) 
                                                    { 
                                                        ?>
                                                        <div style='float:left;margin-left:5px;'>
                                                            <?php echo preg_replace("/ <a(.*)<\/a>/", "", $tags_html[$tag])?>
                                                        </div>
                                                        <?php
                                                    }
                                                    ?>
                                                </div>
                                                <?php 
                                                
                                                $tag_url              = "alarm_console.php?";
                                                $p_tag                = $parameters;
                                                $p_tag['hide_closed'] = "hide_closed=".$not_hide_closed;
                                                $p_tag['tag']         = "tag=";
                                                $tag_url             .=  implode("&", $p_tag);
                                                
                                                if ($tag != "") 
                                                { 
                                                    ?>
                                                    <div style='text-align: left;float:left;margin-left:16px;display:block;width:100%'>
                                                        <a href="<?php echo $tag_url?>"><?php echo _("Remove filter")?></a>
                                                    </div>
                                                    <?php 
                                                } 
                                                ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="nobborder">
                                                <div style="position:relative; z-index: 10000000;">
                                                    <div id="tags_filter" style="display:none;border:0px;position:absolute;background-color:#f9f9f9">
                                                        <table cellpadding='0' cellspacing='0' align="center">
                                                            <tr>
                                                                <th colspan="2" valign='middle' style="border:none; padding: 2px;">
                                                                    <div style='position: relative; margin:auto;'>
                                                                        <div style='position: absolute; top: 2px; width: 90%;'><?php echo _("Labels")?></div>
                                                                    </div>
                                                                    
                                                                    <div style='float:right; width:18%; text-align: right;'>
                                                                        <a style="cursor:pointer; text-align: right;" onclick="$('#tags_filter').toggle()">
                                                                            <img src="../pixmaps/cross-circle-frame.png" alt="<?php echo _("Close"); ?>" title="<?php echo _("Close"); ?>" border="0" align='absmiddle'/>
                                                                        </a>
                                                                    </div>
                                                                </th>
                                                            </tr>
                                                            <?php       
                                                            foreach ($tags as $tg) 
                                                            { 
                                                                ?>
                                                                <tr>
                                                                    <td class="nobborder">
                                                                        <table class="transparent" cellpadding="4" cellspacing="4">
                                                                            <tr>
                                                                                <?php
                                                                                    $tag_url        = "alarm_console.php?";
                                                                                    $p_tag['tag']   = "tag=".$tg->get_id();
                                                                                    $tag_url       .=  implode("&", $p_tag);
                                                                                    
                                                                                    $style          = "border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;border:0px;";
                                                                                    $style         .= "background-color: #".$tg->get_bgcolor().";";
                                                                                    $style         .= "color: #".$tg->get_fgcolor().";";
                                                                                    $style         .= ( $tg->get_bold() )   ? "font-weight: bold;"  : "font-weight: normal;";
                                                                                    $style         .= ( $tg->get_italic() ) ? "font-style: italic;" : "font-style: none;";
                                                                                ?>
                                                                                <td onmouseover="set_hand_cursor()" onmouseout="set_pointer_cursor()" onclick="document.location='<?php echo $tag_url?>'" style="<?php echo $style?>"><?php echo $tg->get_name()?></td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                <td class="nobborder">
                                                                <?php 
                                                                if ( $tag == $tg->get_id() ) 
                                                                { 
                                                                    
                                                                    $p_tag['tag']  = "tag=";
                                                                    $tag_url      .=  implode("&", $p_tag);
                                                                                                                
                                                                    ?>
                                                                    <a href="<?php echo $tag_url?>"><img src="../pixmaps/cross-small.png" border="0" alt="<?php echo _("Remove filter") ?>" title="<?php echo _("Remove filter") ?>"/></a>
                                                                    <?php 
                                                                } 
                                                                ?>
                                                                </td>
                                                            </tr>
                                                                <?php 
                                                            } 
                                                            ?>
                                                        </table>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="nobborder"></td>
                                        </tr>
                                        <?php 
                                    } 
                                        ?>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
                
                
                    <?php
                    $hide_closed     = ( $hide_closed == 1 ) ? 1 : 0;
                    $not_hide_closed = !$hide_closed;
                    $not_no_resolv   = !$no_resolv;
                    $not_beep        = !$beep;
                    
                    $checked_resolv  = ( $no_resolv )   ? " checked='checked'" : "";
                    $checked_hclosed = ( $hide_closed ) ? " checked='checked'" : "";
                    $checked_beep    = ( $beep )        ? " checked='checked'" : "";
                    
                    $no_resolv_url =  $hclosed_url = $refresh_url = $beep_url = "alarm_console.php?";
                    
                    $p_no_resolv              = $parameters;
                    $p_no_resolv['no_resolv'] = "no_resolv=".$not_no_resolv;
                    $no_resolv_url           .=  implode("&", $p_no_resolv);
                                            
                    $p_hclosed                = $parameters;
                    $p_hclosed['hide_closed'] = "hide_closed=".$not_hide_closed;
                    $hclosed_url             .=  implode("&", $p_hclosed);

                    $p_beep                   = $parameters;
                    $p_beep['beep']           = "beep=".$not_beep;
                    $beep_url                .=  implode("&", $p_beep);
                    
                    $refresh_url             .=  implode("&", $parameters);
                    
                    $refresh_sel1 = $refresh_sel2 = $refresh_sel3 = $refresh_sel4 = "";
                    
                    if ($refresh_time == 30000)  $refresh_sel1 = 'selected="selected"';
                    if ($refresh_time == 60000)  $refresh_sel2 = 'selected="selected"';
                    if ($refresh_time == 180000) $refresh_sel3 = 'selected="selected"';
                    if ($refresh_time == 600000) $refresh_sel4 = 'selected="selected"';
                                
                    if ($autorefresh) 
                    {
                        $hide_autorefresh    = 'checked="checked"';
                        $disable_autorefresh = '';
                    }
                    else 
                    {
                        $hide_autorefresh    = '';
                        $disable_autorefresh = 'disabled="disabled"';
                    }
                    
                ?>
                
                <td class="nobborder" style="text-align:center">
                    <table class="noborder" align="center" style="width:80%;">
                        <tr>
                            <td style="text-align: left; border-width: 0px">
                                <input style="border:none" name="no_resolv" type="checkbox" value="1"  onClick="document.location='<?php echo $no_resolv_url?>'" <?php echo $checked_resolv?>><?php echo gettext("Do not resolve ip names"); ?>
                            </td>
                        </tr>       
                        <tr>
                            <td style="text-align: left; border-width: 0px">
                                <input style="border:none" name="hide_closed" type="checkbox" value="1"  onClick="document.location='<?php echo $hclosed_url?>'" <?php echo $checked_hclosed?>><?php echo gettext("Hide closed alarms"); ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align: left; border-width: 0px">
                                <input style="border:none" name="beep" type="checkbox" value="1"  onClick="document.location='<?php echo $beep_url?>'" <?php echo $checked_beep?>><?php echo gettext("Beep on new alarm"); ?>
                            </td>
                        </tr>
                        <!--<tr>
                            <td style="text-align: left; border-width: 0px">
                                <input type="checkbox" name="autorefresh" onclick="javascript:document.filters.refresh_time.disabled=!document.filters.refresh_time.disabled;" <?php echo $hide_autorefresh ?> value='1'/><?php echo gettext("Autorefresh") ?>&nbsp;
                                <select name="refresh_time" <?php echo $disable_autorefresh ?> >
                                    <option value="30000"  <?php echo $refresh_sel1 ?> ><?php echo _("30 sec") ?></option>
                                    <option value="60000"  <?php echo $refresh_sel2 ?>><?php echo _("1 min") ?></option>
                                    <option value="180000" <?php echo $refresh_sel3 ?>><?php echo _("3 min") ?></option>
                                    <option value="600000" <?php echo $refresh_sel4 ?>><?php echo _("10 min") ?></option>
                                </select>&nbsp;
                                <a href="<?php echo $refresh_url ?>">[<?php echo _("Refresh") ?>]</a>
                            </td>
                        </tr>-->
                    </table>
                </td>       
            </tr>

            <tr>
                <td colspan="4" style="padding:5px;" class='noborder'><input type="submit" name='search' id='btnsearch' value="<?php echo _("Search") ?>"/></td>
            </tr>
        </table>
    </form>
    <?php
} 
?>
     <!-- ALARM GRAPH -->
    
     <div align="center" style="margin:10px 0 0 0" id="graph_container">
         <div id="graph_overlay"></div>
         <iframe src="" name="alarm_graph" id="alarm_graph" frameborder="0" style="display:none;overflow:hidden;width:100%;height:30px"></iframe>
     </div>    

    <!-- ALARM LIST -->
    <div id='alarm_list' style='width:100%;margin:10px 0 20px 0;'>
        <table class='table_data'>
            <thead>
                <tr>
                    <th class="center" id="chkall"><input type="checkbox" name="allcheck" onclick="checkall(this)"></th>
                    
                    <th>
                        <?php echo _("Date"); ?>
                    </th>
                    
                    <th>
                        <?php echo _("Status"); ?></a>
                    </th>
                    
                    <th>
                        <?php echo _("Labels"); ?>
                    </th>

                    <th>
                        <?php echo _("Intent & Strategy"); ?>
                    </th>
                    
                    <th>
                        <?php echo _("Method");?>
                    </th>

                    <th>
                        <?php echo _("Risk")?>
                    </th>
                    
                    <th>
                        <?php echo _("Attack pattern"); ?>
                    </th>
                    
                    <th>
                        <?php echo _("Source")?>
                    </th>

                    <th>
                        <?php echo _("Destination")?>
                    </th>
                    
                    <th>
                    </th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>  
        

    <a href="javascript:;" class="fright" style="padding-bottom:15px;" onclick="delete_all_alarms();">
        <?php echo _("Delete ALL"); ?>
    </a>

                    

</body>
</html>
