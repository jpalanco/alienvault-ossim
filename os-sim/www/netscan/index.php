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


require_once 'av_init.php';

Session::logcheck('environment-menu', 'ToolsScan');


$keytree = 'assets';

$scan_types = array(
    'ping'   => _('Ping'),
    'fast'   => _('Fast Scan'),
    'normal' => _('Normal'),
    'full'   => _('Full Scan'),
    'custom' => _('Custom')
);

$time_templates = array(
    'T0' => _('Paranoid'),
    'T1' => _('Sneaky'),
    'T2' => _('Polite'),
    'T3' => _('Normal'),
    'T4' => _('Aggressive'),
    'T5' => _('Insane')
);


//Scan files

$user = Session::get_session_user();

$scan_file        = 'last_asset_object-'.md5($user);
$scan_report_file = AV_TMP_DIR.'/last_scan_report-'.md5($user);


/****************************************************
 ************ Default scan configuration ************
 ****************************************************/

$host_id      = '';
$sensor       = 'local';
$scan_type    = 'fast';
$ttemplate    = 'T3';
$scan_ports   = '1-65535';
$autodetected = 1;
$rdns         = 1;


//Database connection
$db   = new ossim_db();
$conn = $db->connect();


/****************************************************
********************* Sensors ***********************
****************************************************/

$filters = array(
    'where'    => 'sensor_properties.version <> ""',
    'order_by' => 'sensor.name, priority DESC'
);

$sensor_list = Av_sensor::get_list($conn, $filters);
$sensor_list = $sensor_list[0];



/****************************************************
******************** Search Box ********************
****************************************************/

$autocomplete_keys = array('hosts', 'nets');
$assets            = Autocomplete::get_autocomplete($conn, $autocomplete_keys);



/****************************************************
******************** Clear Scan ********************
****************************************************/


//Results will be deleted when a custom scan is executed or when an user forces it
if (intval($_REQUEST['clearscan']) == 1 || $_REQUEST['action'] == 'custom_scan')
{
    try
    {
        //Delete scan task from Redis
        $av_scan = Av_scan::get_object_from_file($scan_file);

        if (is_object($av_scan) && !empty($av_scan))
        {
            $av_scan->delete_scan();

            //Delete local scan files
            Cache_file::remove_file($scan_file);
        }

        //Delete report scan information
        @unlink($scan_report_file);
    }
    catch(Exception $e)
    {
        ;
    }
}


/*******************************************************************
***  Custom scan (From Asset Detail or from a Suggestion Link)   ***
********************************************************************/

if ($_REQUEST['action'] == 'custom_scan')
{
    if ($_GET['action'] == 'custom_scan')
    {
        //It's necessary to validate properly
        $_POST = $_GET;
        $_POST['timing_template'] = $ttemplate;
        $_POST['autodetected']    = $autodetected;
        $_POST['rdns']            = $rdns;
    }

    $validate = array (
        'host_id'         => array('validation' => 'OSS_HEX',                    'e_message' => 'illegal:' . _('Host ID')),
        'sensor'          => array('validation' => 'OSS_LETTER',                 'e_message' => 'illegal:' . _('Sensor')),
        'scan_type'       => array('validation' => 'OSS_LETTER',                 'e_message' => 'illegal:' . _('Scan Mode')),
        'timing_template' => array('validation' => 'OSS_TIMING_TEMPLATE',        'e_message' => 'illegal:' . _('Timing Template')),
        'autodetected'    => array('validation' => 'OSS_BINARY',                 'e_message' => 'illegal:' . _('Autodetected services and OS')),
        'rdns'            => array('validation' => 'OSS_BINARY',                 'e_message' => 'illegal:' . _('Reverse DNS '))
    );

    $validation_errors = validate_form_fields('POST', $validate);

    //Extra validations

    if (empty($validation_errors))
    {
        if (!array_key_exists(POST('scan_type'), $scan_types))
        {
            $validation_errors['status']    = 'error';
            $validation_errors['scan_type'] = _('Error! Scan type not allowed');
        }

        if (!array_key_exists(POST('timing_template'), $time_templates))
        {
            $validation_errors['status']          = 'error';
            $validation_errors['timing_template'] = _('Error! Timing template not allowed');
        }

        if (empty($validation_errors))
        {
            $host_id      = POST('host_id');
            $_hostname    = Asset_host::get_name_by_id($conn, $host_id);
            $_host_ips    = Asset_host_ips::get_ips_to_string($conn, $host_id);
            $sensor       = POST('sensor');
            $scan_type    = POST('scan_type');
            $ttemplate    = POST('timing_template');
            $autodetected = POST('autodetected');
            $rdns         = POST('rdns');
        }
    }
}

//Close DB connection
$db->close();

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title><?php echo _('OSSIM Framework');?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>

    <?php
    //CSS Files
    $_files = array(
        array('src' => 'av_common.css?t='.Util::get_css_id(),           'def_path' => TRUE),
        array('src' => 'jquery-ui-1.7.custom.css',                      'def_path' => TRUE),
        array('src' => 'jquery.autocomplete.css',                       'def_path' => TRUE),
        array('src' => 'tree.css',                                      'def_path' => TRUE),
        array('src' => 'progress.css',                                  'def_path' => TRUE),
        array('src' => 'tipTip.css',                                    'def_path' => TRUE),
        array('src' => 'fancybox/jquery.fancybox-1.3.4.css',            'def_path' => TRUE),
        array('src' => '/environment/assets/asset_discovery.css',       'def_path' => TRUE)
    );

    Util::print_include_files($_files, 'css');

    //JS Files
    $_files = array(

        array('src' => 'jquery.min.js',                                  'def_path' => TRUE),
        array('src' => 'jquery-ui.min.js',                               'def_path' => TRUE),
        array('src' => 'combos.js',                                      'def_path' => TRUE),
        array('src' => 'notification.js',                                'def_path' => TRUE),
        array('src' => 'messages.php',                                   'def_path' => TRUE),
        array('src' => 'jquery.cookie.js',                               'def_path' => TRUE),
        array('src' => 'jquery.dynatree.js',                             'def_path' => TRUE),
        array('src' => 'jquery.autocomplete.pack.js',                    'def_path' => TRUE),
        array('src' => 'token.js',                                       'def_path' => TRUE),
        array('src' => 'jquery.tipTip.js',                               'def_path' => TRUE),
        array('src' => 'utils.js',                                       'def_path' => TRUE),
        array('src' => 'av_scan.js.php',                                 'def_path' => TRUE),
        array('src' => 'fancybox/jquery.fancybox-1.3.4.pack.js',         'def_path' => TRUE)
    );

    Util::print_include_files($_files, 'js');
    ?>


    <script type='text/javascript'>

        var timer = null;

        function show_notification (msg, container, nf_type, style)
        {
            var nt_error_msg = (msg == '')   ? '<?php echo _('Sorry, operation was not completed due to an error when processing the request')?>' : msg;
            var style        = (style == '') ? 'width: 80%; text-align:center; padding: 5px 5px 5px 22px; margin: 20px auto;' : style;

            var config_nt = {
                content: nt_error_msg,
                options: {
                    type: nf_type,
                    cancel_button: true
                },
                style: style
            };

            var nt_id         = 'nt_ns';
            var nt            = new Notification(nt_id, config_nt);
            var notification  = nt.show();

            $('#'+container).html(notification);
            parent.window.scrollTo(0,0);
        }


        //Check if API session ends or no permissions.
        function no_api_permissions(xhr_obj)
        {
            var no_api_permissions = false;
            if (xhr_obj.status == 400)
            {
                console.log(xhr_obj.responseText);
                no_api_permissions = (xhr_obj.responseText.indexOf("No permission") > -1 );
                console.log(no_api_permissions);
            }
            return no_api_permissions;
        }


        /****************************************************
         ****************** Scan functions ******************
         ****************************************************/

        function check_target_number()
        {
            if(getcombotext("assets").length < 1)
            {
                av_alert('<?php echo Util::js_entities(_('You must choose at least one asset'))?>');

                return false;
            }

            var num_targets = 0;

            selectall("assets");

            var targets = $('#assets').val();

            for (i = 0; i < targets.length; i++)
            {
                if (targets[i].match(/#/))
                {
                    var ip_cidr = targets[i].split('#')
                        ip_cidr = ip_cidr[1];
                }
                else
                {
                    var ip_cidr = targets[i];
                }

                if (ip_cidr.match(/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])(\/(\d|[1-2]\d|3[0-2]))$/))
                {
                    var res = ip_cidr.split('/');
                    num_targets += 1 << (32 - res[1]);
                }
                else
                {
                    num_targets++;
                }
            }

            if (num_targets > 256)
            {
                var msg_confirm = '<?php echo Util::js_entities(_("You are about to scan a big number of assets (#TARGETS# assets). This scan could take a long time depending on your network and the number of assets that are up, are you sure you want to continue?"))?>';

                msg_confirm = msg_confirm.replace("#TARGETS#", num_targets);

                var keys = {"yes": "<?php echo _('Yes') ?>","no": "<?php echo _('No') ?>"};

                av_confirm(msg_confirm, keys).fail(function(){
                    return false;
                }).done(function(){
                    run_scan();
                });
            }
            else
            {
                run_scan();
            }
        }


        function check_scan_status()
        {
            var scan_data = {
               "token"  : Token.get_token("assets_form"),
               "action" : "scan_status"
            }

            return $.ajax({
                type: 'POST',
                url: 'scan_actions.php',
                data: scan_data,
                dataType: 'json'
            });
        }


        function delete_scan()
        {
            var scan_data = {
               "token"  : Token.get_token("assets_form"),
               "action" : "delete_scan"
            }

            return $.ajax({
                type: 'POST',
                url: 'scan_actions.php',
                data: scan_data,
                dataType: 'json'
            });
        }


        function show_progress_box()
        {
            check_scan_status().done(function(data) {

                var allowed_status = new Array();
                    allowed_status[1] = '<?php echo _('Searching assets')?>';
                    allowed_status[2] = '<?php echo _('Search finished')?>';
                    allowed_status[3] = '<?php echo _('Scanning assets')?>';
                    allowed_status[4] = '<?php echo _('Scan finished')?>';
                    allowed_status[5] = '<?php echo _('Failed')?>';

                try
                {
                    var scan_status   = parseInt(data.data.status.code);
                    var scan_info     = data.data.message;
                    var scan_progress = data.data.progress;

                    //Asset scan is running or it has finished
                    if (scan_status > 0 && scan_status < 5)
                    {
                        //Scan has finished
                        if (scan_status == 4)
                        {
                            $('#scan_button').removeClass('av_b_processing').prop('disabled', false);

                            clearTimeout(timer);

                            get_scan_report();
                        }
                        else
                        {
                            $('#scan_button').addClass('av_b_processing').prop('disabled', true);

                            show_state_box(scan_status, scan_info, scan_progress);
                            time = (scan_status == 1) ? 4000 :  6000;

                            timer = setTimeout(function(){
                                show_progress_box();
                            }, time);
                        }
                    }
                }
                catch(Err)
                {
                    $('#scan_button').removeClass('av_b_processing').prop('disabled', false);

                    var __style = 'padding: 3px; width: 90%; margin: auto; text-align: left;';
                    show_notification(av_messages['unknown_error'], 'c_info', 'nf_error', __style);

                    clearTimeout(timer);
                    $.fancybox.close();
                }
            }).fail(function(xhr) {

                //Check expired session
                var session = new Session(xhr.responseText, '');

                if (session.check_session_expired() == true || no_api_permissions(xhr))
                {
                    session.redirect();
                    return;
                }

                $('#scan_button').removeClass('av_b_processing').prop('disabled', false);
                try
                {
                    // try to stop scan first to store its report instead of deleting it.
                    stop_scan();
                }
                catch(Err)
                {
                    delete_scan();
                }

                var __error_msg = av_messages['unknown_error'];

                if (typeof(xhr.responseText) != 'undefined' && xhr.responseText != '')
                {
                    __error_msg = xhr.responseText;
                }

                var __style = 'padding: 3px; width: 90%; margin: auto; text-align: left;';
                show_notification(__error_msg, 'c_info', 'nf_error', __style);

                clearTimeout(timer);
                $.fancybox.close();
            });
        }


        function run_scan()
        {
            parent.window.scrollTo(0, 0);

            selectall("assets");

            var scan_data  = $('#t_ad .vfield').serialize();
                scan_data += '&action=run_scan&token=' + Token.get_token("assets_form");

            $.ajax({
                type: "POST",
                url: 'scan_actions.php',
                data: scan_data,
                dataType: 'json',
                beforeSend: function(xhr) {

                    $('#c_info').html('');
                    $('#scan_result').html('');

                    $('#scan_button').addClass('av_b_processing').prop('disabled', true);
                },
                error: function(xhr){

                    //Check expired session
                    var session = new Session(xhr.responseText, '');

                    if (session.check_session_expired() == true || no_api_permissions(xhr))
                    {
                        session.redirect();
                        return;
                    }

                    var __error_msg = av_messages['unknown_error'];

                    if (typeof(xhr.responseText) != 'undefined' && xhr.responseText != '')
                    {
                        __error_msg = xhr.responseText;
                    }

                    var __nf_class = 'nf_error';
                    if (__error_msg.match(/^Warning/))
                    {
                        var __nf_class = 'nf_warning';
                    }

                    $('#scan_button').removeClass('av_b_processing').prop('disabled', false);

                    var __style = 'padding: 3px; width: 90%; margin: auto; text-align: left;';
                    show_notification(__error_msg, 'c_info', __nf_class, __style);
                },
                success: function(data){

                    var scan_status   = 0;
                    var scan_info     = '<?php echo _('Requesting data, please wait')?> ...';
                    var scan_progress = null;

                    show_state_box(scan_status, scan_info, scan_progress);

                    timer = setTimeout(function(){
                        //hide_loading_box();
                        show_progress_box();
                    }, 3000);
                }
            });
        }


        function stop_scan()
        {
            var scan_data = {
               "token"  : Token.get_token("assets_form"),
               "action" : "stop_scan"
            }

            $.ajax({
                type: "POST",
                url: 'scan_actions.php',
                data: scan_data,
                dataType: 'json',
                beforeSend: function(xhr) {

                    $('#scan_button').removeClass('av_b_processing').prop('disabled', false);
                    $('#stop_scan').addClass('av_b_processing').prop('disabled', true);
                },
                error: function(xhr){

                    //Check expired session
                    var session = new Session(xhr.responseText, '');

                    if (session.check_session_expired() == true || no_api_permissions(xhr))
                    {
                        session.redirect();
                        return;
                    }

                    var __error_msg = av_messages['unknown_error']

                    if (typeof(xhr.responseText) != 'undefined' && xhr.responseText != '')
                    {
                        __error_msg = xhr.responseText;
                    }

                    var __style = 'padding: 3px; width: 90%; margin: auto; text-align: left;';
                    show_notification(__error_msg, 'c_info', 'nf_error', __style);

                    clearTimeout(timer);
                    $.fancybox.close();
                },
                success: function(msg){

                    $('#c_info').html('');

                    clearTimeout(timer);

                    get_scan_report();

                    $.fancybox.close();
                }
            });
        }


        function get_scan_report()
        {
            var scan_data = {
               "token"  : Token.get_token("assets_form"),
               "action" : "download_scan_report",
               "rdns" : $("#rdns").attr("checked") ? "1": "0"
            }

            $.ajax({
                type: 'POST',
                url: 'scan_actions.php',
                data: scan_data,
                dataType: 'json',
                error: function(xhr){

                    //Check expired session
                    var session = new Session(xhr.responseText, '');

                    if (session.check_session_expired() == true || no_api_permissions(xhr))
                    {
                        session.redirect();
                        return;
                    }

                    var __error_msg = av_messages['unknown_error'];

                    if (typeof(xhr.responseText) != 'undefined' && xhr.responseText != '')
                    {
                        __error_msg = xhr.responseText;
                    }

                    var __nf_class = 'nf_error';
                    if (__error_msg.match(/^Warning/))
                    {
                        var __nf_class = 'nf_warning';
                    }

                    var __style = 'padding: 3px; width: 90%; margin: auto; text-align: left;';
                    show_notification(__error_msg, 'c_info', __nf_class, __style);

                    $.fancybox.close();
                },
                success: function(data)
                {
                    show_scan_report();
                }
           });
        }


        function show_scan_report()
        {
            var scan_data = {
               "token"  : Token.get_token("assets_form"),
               "action" : "show_scan_report"
            }

            $.ajax({
                type: 'POST',
                url: 'scan_actions.php',
                data: scan_data,
                dataType: 'json',
                error: function(xhr){

                    //Check expired session
                    var session = new Session(xhr.responseText, '');

                    if (session.check_session_expired() == true || no_api_permissions(xhr))
                    {
                        session.redirect();
                        return;
                    }

                    var __error_msg = av_messages['unknown_error'];

                    if (typeof(xhr.responseText) != 'undefined' && xhr.responseText != '')
                    {
                        __error_msg = xhr.responseText;
                    }

                    var __nf_class = 'nf_error';
                    if (__error_msg.match(/^Warning/))
                    {
                        var __nf_class = 'nf_warning';
                    }
                    else if (__error_msg.match(/finish/))
                    {
                        var __nf_class = 'nf_info';
                    }

                    var __style = 'padding: 3px; width: 90%; margin: auto; text-align: left;';
                    show_notification(__error_msg, 'c_info', __nf_class, __style);

                    $.fancybox.close();
                },
                success: function(data)
                {
                    $.fancybox.close();

                    if (data != null && typeof(data.data) != 'undefined' && data.data != '')
                    {
                        $('#scan_result').html(data.data);
                    }

                    var offset = $("#scan_result").offset();
                    parent.window.scrollTo(0, offset.top);
                }
           });
        }


        /****************************************************
         *************** Searchbox functions ****************
         ****************************************************/

        function add_asset(assets_to_scan)
        {
            if (typeof(assets_to_scan) != 'undefined' && typeof(assets_to_scan) == 'object')
            {
                var size = assets_to_scan.length;

                for (var i = 0; i < size; i++)
                {
                    var asset_value = '';

                    if (typeof(assets_to_scan[i].id) != 'undefined' && assets_to_scan[i].id != '')
                    {
                        asset_value = assets_to_scan[i].id + '#';
                    }

                    asset_value += (assets_to_scan[i].ip_cidr.match(/\//)) ? assets_to_scan[i].ip_cidr : assets_to_scan[i].ip_cidr + '/32';

                    var asset_text = assets_to_scan[i].ip_cidr;

                    if (typeof(assets_to_scan[i].name) != 'undefined' && assets_to_scan[i].name != '')
                    {
                        asset_text += ' ('+ assets_to_scan[i].name + ')';
                    }

                    if ($('#assets option[value $="'+ asset_value +'"]').length == 0)
                    {
                        addto ("assets", asset_text, asset_value);
                    }
                }
            }

            $("#searchbox").val('');
        }


        $(document).ready(function(){

            /****************************************************
             *********************** Tree  **********************
             ****************************************************/

            $("#atree").dynatree({
                initAjax: { url: "../tree.php?key=<?php echo $keytree ?>" },
                clickFolderMode: 2,
                onActivate: function(dtnode) {

                    if(dtnode.data.url != '' && typeof(dtnode.data.url) != 'undefined')
                    {
                        var Regexp   = /.*_(\w+)/;
                        var match    = Regexp.exec(dtnode.data.key);
                        var asset_id = match[1];

                        // Click on Asset Group
                        if (dtnode.data.key.match(/hostgroup_/))
                        {
                            $.ajax({
                                type: 'GET',
                                url: "../tree.php",
                                data: 'key=' + dtnode.data.key,
                                dataType: 'json',
                                success: function(data)
                                {
                                    if (data.length < 1)
                                    {
                                        show_notification('<?php echo _('Unable to fetch the asset group members')?>', 'c_info', 'nf_error', 'padding: 3px; width: 90%; margin: auto; text-align: center;');
                                    }
                                    else
                                    {
                                        // Group reached the 200 top of page: show warning
                                        var last_element = data[data.length - 1].key;

                                        if (last_element.match(/hostgroup_/))
                                        {
                                            show_notification('<?php echo _('This asset group has more than 200 assets, please try again with a smaller group')?>', 'c_info', 'nf_warning', 'padding: 3px; width: 90%; margin: auto; text-align: center;');
                                        }
                                        else
                                        {
                                            jQuery.each(data, function(i, group_member)
                                            {
                                                // Split for multiple IP
                                                var member_keys = group_member.val.split(",");
                                                var member_id   = group_member.key.replace("host_","");

                                                for (k = 0; k < member_keys.length; k++)
                                                {
                                                    var item = member_keys[k];

                                                    var asset_text  = ''
                                                    var asset_value = '';

                                                    if (item.match(/\d+\.\d+\.\d+\.\d+/) !== null)
                                                    {
                                                        //IP
                                                        Regexp = /(\d+\.\d+\.\d+\.\d+)/;
                                                        match  = Regexp.exec(item);

                                                        if (group_member.val.match(/,/) !== null)
                                                        {
                                                            Regexp_name = /\((.+)\)/;
                                                            name_match  = Regexp_name.exec(group_member.tooltip);
                                                            asset_text  = match[1] + ' (' + name_match[1] + ')';
                                                        }
                                                        else
                                                        {
                                                            asset_text  = group_member.tooltip;
                                                        }
                                                        asset_value = member_id + '#' + match[1] + '/32';
                                                    }

                                                    if(asset_value != '' && asset_text != '')
                                                    {
                                                        addto ("assets", asset_text, asset_value);
                                                    }
                                                }
                                            });
                                        }
                                    }
                                }
                            });
                        }
                        // Click on Host or Network
                        else
                        {
                            // Split for multiple IP/CIDR
                            var keys = dtnode.data.val.split(",");

                            for (var i = 0; i < keys.length; i++)
                            {
                                var item = keys[i];

                                var asset_text  = ''
                                var asset_value = '';

                                if (item.match(/\d+\.\d+\.\d+\.\d+\/\d+/) !== null)
                                {
                                    //CIDR
                                    Regexp = /(\d+\.\d+\.\d+\.\d+\/\d+)/;
                                    match  = Regexp.exec(item);

                                    asset_text  = dtnode.data.val;
                                    asset_value = asset_id + '#' + match[1];
                                }
                                else if (item.match(/\d+\.\d+\.\d+\.\d+/) !== null)
                                {
                                    //IP
                                    Regexp = /(\d+\.\d+\.\d+\.\d+)/;
                                    match  = Regexp.exec(item);

                                    asset_text  = dtnode.data.tooltip;
                                    asset_value = asset_id + '#' + match[1] + '/32';
                                }

                                if(asset_value != '' && asset_text != '')
                                {
                                    addto ("assets", asset_text, asset_value);
                                }
                            }
                        }
                    }

                    dtnode.deactivate();
                },
                onDeactivate: function(dtnode) {},
                onLazyRead: function(dtnode){
                    dtnode.appendAjax({
                        url: "../tree.php",
                        data: {key: dtnode.data.key, page: dtnode.data.page}
                    });
                }
            });



            /****************************************************
             ******************** Search Box ********************
             ****************************************************/

            $("#assets_form").keypress(function(e) {
                if (e.which == 13 )
                {
                    return false;
                }
            });

            $("#delete_all").click(function() {
                selectall('assets');
                deletefrom('assets');
            });


            $("#delete").click(function() {
                deletefrom('assets');
            });


            $("#lnk_ss").click(function() {

                $('#td_sensor').toggle();

                if($('#td_sensor').is(':visible'))
                {
                    $('#sensors_arrow').attr('src','../pixmaps/arrow_green_down.gif');
                }
                else
                {
                    $('#sensors_arrow').attr('src','../pixmaps/arrow_green.gif');
                }

                return false;
            });


            /**************************************************
             *** Note: Handlers must be bound in this order ***
             **************************************************/


            // Autocomplete assets
            var assets = [ <?php echo preg_replace("/,$/","",$assets); ?> ];

            $("#searchbox").autocomplete(assets, {
                minChars: 0,
                width: 300,
                max: 100,
                matchContains: true,
                autoFill: false,
                selectFirst: false,
                formatItem: function(row, i, max) {
                    return row.txt;
                }
            }).result(function(event, item) {

                var _asset = {
                    'ip_cidr': item.ip,
                    'id'     : item.id,
                    'name'   : item.name
                };

                var assets_to_scan = new Array();
                    assets_to_scan.push(_asset);

                add_asset(assets_to_scan);
            });


            $("#searchbox").click(function() {
                $("#searchbox").removeClass('greyfont');
                $("#searchbox").val('');
            });


            $("#searchbox").blur(function() {
                $("#searchbox").addClass('greyfont');
                $("#searchbox").val('<?php echo _('Type here to search assets')?>');
            });


            $('#searchbox').keydown(function(event) {

                if (event.which == 13)
                {
                    var ip_cidr = $("#searchbox").val();

                    targetRegex = /^!?(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])(\/(\d|[1-2]\d|3[0-2]))?$/;

                    if(ip_cidr.match(targetRegex))
                    {
                        var _asset = {
                            'ip_cidr' : ip_cidr,
                            'id'      : '',
                            'name'    : ''
                        };

                        var assets_to_scan = new Array();
                        assets_to_scan.push(_asset);

                        add_asset(assets_to_scan);
                    }
                }
            });



            /****************************************************
             ********************* Tooltips *********************
             ****************************************************/

            if ($(".more_info").length >= 1)
            {
                $(".more_info").tipTip({maxWidth: "auto"});
            }



            /****************************************************
             ****************** Scan functions  *****************
             ****************************************************/

            bind_nmap_actions();

            <?php
            //Adding custom assets

            if ($_REQUEST['action'] == 'custom_scan' && empty($validation_errors))
            {
                ?>
                var assets_to_scan = new Array();

                <?php
                $aux_h_ips = explode(',', $_host_ips);

                foreach($aux_h_ips as $_h_ip)
                {
                    ?>
                    var _asset = {
                        'ip_cidr' : '<?php echo $_h_ip?>',
                        'id'      : '<?php echo $host_id?>',
                        'name'    : '<?php echo $_hostname?>'
                    };

                    assets_to_scan.push(_asset);
                    <?php
                }

                ?>
                add_asset(assets_to_scan);
                <?php
            }


            if (file_exists($scan_report_file))
            {
                ?>
                show_scan_report();
                <?php
            }

            ?>
            show_progress_box();
        });
    </script>

</head>

<body>

<!-- Asset form -->

<div id='c_info'>
    <?php
    if (is_array($validation_errors) && !empty($validation_errors))
    {
        $txt_error = '<div>'._('The following errors occurred').":</div>
                      <div style='padding: 10px;'>".implode('<br/>', $validation_errors).'</div>';

        $config_nt = array(
            'content' => $txt_error,
            'options' => array (
                'type'          =>  'nf_error',
                'cancel_button' =>  FALSE
            ),
            'style' =>  'width: 80%; margin: 20px auto; text-align: left;'
        );

        $nt = new Notification('nt_1', $config_nt);
        $nt->show();
    }
    ?>
</div>

<div id='c_asset_discovery'>

    <form name="assets_form" id="assets_form">

        <div class='scan_title'><?php echo _('Scan for New Assets') ?></div>

        <table align="center" id='t_ad'>

            <tbody>
                <tr>
                    <th colspan="2"><?php echo _('Target selection') ?></th>
                </tr>

                <tr>
                    <td>
                        <span> <?php echo _('Please, select the assets you want to scan:');?></span>
                    </td>
                </tr>

                <tr>
                    <td class='container nobborder'>
                        <table class="transparent" cellspacing="0">
                            <tr>
                                <td class="nobborder" style="vertical-align:top" class="nobborder">
                                    <table class="transparent" cellspacing="0">
                                        <tr>
                                            <td class="nobborder">
                                                <select id="assets" class="vfield" name="assets[]" multiple="multiple"></select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="nobborder" style="text-align:right;padding-top:5px;">
                                                <input type="button" name='deletel' id='delete' class="small av_b_secondary" value=" [X] "/>
                                                <input type="button" name='delete_all' id='delete_all' class="small av_b_secondary" style="margin-right:0px;" value="<?php echo _('Delete all')?>"/>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td class="nobborder" width="300px;" style="vertical-align: top;padding-left:15px;">
                                    <input class="greyfont" type="text" name="searchbox" id="searchbox" value="<?php echo _('Type here to search assets'); ?>" />
                                    <div id="atree"></div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <th colspan="2"> <?php echo _('Sensor selection')?></th>
                </tr>

                <tr>
                    <td class="nobborder">
                        <table class="transparent">
                            <tr>
                                <td class="nobborder">
                                    <?php
                                    $sl_checked = ($sensor == 'local' || empty($sensor_list)) ? 'checked="checked"' : '';
                                    ?>
                                    <input class="vfield" type="radio" name="sensor" id="lsensor" <?php echo $sl_checked?> value="local"/>
                                    <label for="lsensor">
                                        <span><span class="bold"><?php echo _('Local')?></span> <?php echo _('sensor')?></span>
                                        <span class="small"> <?php echo _('Launch scan from the local sensor')?></span>
                                    </label>
                                </td>
                            </tr>

                            <?php
                            if (is_array($sensor_list) && !empty($sensor_list))
                            {
                                ?>
                                <tr>
                                    <td class="nobborder">
                                        <?php
                                        $sl_checked = ($sensor == 'automatic') ? 'checked="checked"' : '';
                                        ?>

                                        <input type="radio" class="vfield" name="sensor" id="asensor" <?php echo $sl_checked?> value="auto"/>
                                        <label for="asensor">
                                            <span><span class="bold"><?php echo _('Automatic')?></span> <?php echo _('sensor')?></span>
                                            <span class="small"> <?php echo _('Launch scan from the first available sensor')?></span>
                                        </label>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                        </table>
                    </td>
                </tr>

                <?php
                if (is_array($sensor_list) && !empty($sensor_list))
                {
                    ?>
                    <tr>
                        <td style="text-align: left; border:none; padding:3px 0px 3px 8px">
                            <a href="javascript:void(0);" id='lnk_ss'>
                                <img id="sensors_arrow" border="0" align="absmiddle" src="../pixmaps/arrow_green.gif"/>
                                <span><?php echo _('<strong>Select a</strong> specific sensor')?></span>
                            </a>
                        </td>
                    </tr>

                    <tr id="td_sensor">
                        <td style="padding-left:30px;">
                            <table class="transparent">
                                <?php
                                $sensor_id = 0;

                                foreach ($sensor_list as $_sensor_id => $sensor)
                                {
                                    ?>
                                    <tr>
                                        <td class="nobborder">
                                            <input type="radio" class="vfield" name="sensor" id="sensor<?php echo $sensor_id;?>" value="<?php echo $_sensor_id ?>">
                                            <label for="sensor<?php echo $sensor_id;?>"><?php echo $sensor['name'] . " [" . $sensor['ip'] . "]"?></label>
                                        </td>
                                    </tr>
                                    <?php
                                    $sensor_id++;
                                }
                                ?>
                            </table>
                        </td>
                    </tr>
                    <?php
                }
                ?>

                <tr>
                    <th colspan="2"><?php echo _('Advanced Options')?></th>
                </tr>

                <!-- Full scan -->
                <tr>
                    <td colspan="2" style="padding:7px 0px 0px 10px">

                        <table id='t_adv_options'>
                            <!-- Full scan -->
                            <tr>
                                <td class='td_label'>
                                    <label for="scan_type"><?php echo _('Scan type')?>:</label>
                                </td>
                                <td>
                                    <select id="scan_type" name="scan_type" class="nmap_select vfield">
                                        <?php
                                        foreach ($scan_types as $st_v => $st_txt)
                                        {
                                            $selected = ($scan_type == $st_v) ? 'selected="selected"' : '';

                                            echo "<option value='$st_v' $selected>$st_txt</option>";
                                        }
                                        ?>
                                    </select>
                                </td>
                                <td style='padding-left: 20px;'>
                                    <span id="scan_type_info"></span>
                                </td>
                            </tr>

                            <!-- Specific ports -->
                            <tr id='tr_cp'>
                                <td class='td_label'>
                                    <label for="custom_ports"><?php echo _('Specify Ports')?>:</label>
                                </td>
                                <td colspan="2">
                                    <?php
                                        $scan_ports = ($scan_ports == '') ? '1-65535' : $scan_ports;
                                    ?>
                                    <input class="greyfont vfield" type="text" id="custom_ports" name="custom_ports" value="<?php echo $scan_ports?>"/>
                                </td>
                            </tr>

                            <!-- Time template -->
                            <tr>
                                <td class='td_label'>
                                    <label for="timing_template"><?php echo _('Timing template')?>:</label>
                                </td>
                                <td>
                                    <select id="timing_template" name="timing_template" class="nmap_select vfield">
                                        <?php
                                        foreach ($time_templates as $ttv => $tt_txt)
                                        {
                                            $selected = ($ttemplate == $ttv) ? 'selected="selected"' : '';

                                            echo "<option value='$ttv' $selected>$tt_txt</option>";
                                        }
                                        ?>
                                    </select>
                                </td>
                                <td style='padding-left: 20px;'>
                                    <span id="timing_template_info"></span>
                                </td>
                            </tr>

                            <tr>
                                <td colspan="3">

                                    <?php $ad_checked = ($autodetected == 1) ? 'checked="checked"' : '';?>

                                    <input type="checkbox" id="autodetect" name="autodetect" class='vfield' <?php echo $ad_checked?> value="1"/>
                                    <label for="autodetect"><?php echo _('Autodetect services and Operating System')?></label>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3">

                                    <?php $rdns_checked = ($rdns == 1) ? 'checked="checked"' : '';?>

                                    <input type="checkbox" id="rdns" name="rdns" class='vfield' <?php echo $rdns_checked?> value="1" />
                                    <label for="rdns"><?php echo _('Enable DNS Resolution')?></label>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Do scan -->
                <tr>
                    <td colspan="2" class="nobborder center" style='padding: 10px;'>
                        <input type="button" id="scan_button" onclick="check_target_number();" value="<?php echo _('Start Scan') ?>"/>
                    </td>
                </tr>
            </tbody>
        </table>

        <br/>

        <div id='scan_result'></div>

        <br/>

    </form>
</div>

<!-- end of Asset form -->
</body>
</html>
