<?php
header('Content-type: text/javascript');

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


/*********************************************************************
 ************************  COMMON VARIABLES   ************************
 *********************************************************************/


<?php
//Database connection
$db    = new ossim_db();
$conn  = $db->connect();

$ports = array();

$port_list = Port::get_list($conn);

foreach($port_list as $port)
{
    $ports[$port->get_port_number()." - ".$port->get_protocol_name()] = $port->get_service();
}

$lines = Util::execute_command("egrep 'tcp|udp' /etc/services | awk '{print $1 $2 }'", FALSE, 'array');

foreach($lines as $line)
{
    preg_match('/(\D+)(\d+)\/(.+)/', $line, $regs);

    if($ports[$regs[2].' - '.$regs[3]] == '')
    {
        $ports[$regs[2].' - '.$regs[3]] = $regs[1];
    }
}
?>

var ports = new Array();
<?php
foreach($ports as $p_id => $p_name)
{
    ?>
    ports['<?php echo $p_id?>'] = '<?php echo $p_name?>';
    <?php
}
?>




/******************************************************
 *****************  HELPER FUNCTIONS  *****************
 ******************************************************/

/* Save item (Properties, Software and Services) */
function save_item(item_type, item_config, target)
{
    var f_config = {
        "property" : {
            "c_container" : "tp_av_info",
            "form"        : "properties_form",
            "loading_msg" : "<?php echo _('Saving properties')?>"
        },
        "software" : {
            "c_container" : "tsw_av_info",
            "form"        : "software_form",
            "loading_msg" :  "<?php echo _('Saving software')?>"
        },
        "service" : {
            "c_container" : "ts_av_info",
            "form"        : "services_form",
            "loading_msg" :  "<?php echo _('Saving service')?>"
        }
    };


    show_loading_box(f_config[item_type]["c_container"], f_config[item_type]["loading_msg"], '');

    //Add form information to item configuration
    item_config.form = f_config[item_type]["form"];

    //Getting and adding old property data to item configuration
    var item_id   = $('#' + item_config.form + ' input[name="item_id"]').val();
    var item_data = target.get_row_data(item_id)

    item_config.p_data = item_data['row_data'];

    save_property_in_db(item_config).done(function(data) {

        //Check expired session
        var session = new Session(data, '');

        if (session.check_session_expired() == true)
        {
            session.redirect();

            return;
        }

        //Add unregistered port automatically
        if (item_type == "service")
        {
            add_new_port();
        }

        //Setting default values
        reset_form(item_type);

        //Item saved or modified
        target.reload_list(false);

        var __success_msg = data.data;

        notify(__success_msg, 'nf_success', true);

        window.scrollTo(0,0);

    }).fail(function(xhr) {

        //Check expired session
        var session = new Session(xhr.responseText, '');

        if (session.check_session_expired() == true)
        {
            session.redirect();

            return;
        }

        var __error_msg = av_messages['unknown_error'];

        if (typeof(xhr.responseText) != 'undefined' && xhr.responseText != '')
        {
            __error_msg = xhr.responseText;
        }

        var __style = 'width: 100%; text-align:center; margin:0px auto;';
        show_notification(f_config[item_type]["c_container"], __error_msg, 'nf_error', 15000, true, __style);

        window.scrollTo(0,0);
    });
}


/* Save property (Asset property, MAC Address, Software or Service) in database */
function save_property_in_db(item_config)
{
    return $.ajax({
        type: "POST",
        url: item_config.controllers.submit_form,
        data: __get_form_data(item_config),
        dataType: 'json',
        beforeSend: function(xhr) {

            remove_previous_notifications();
        },
        error: function(data){

            hide_loading_box();
        },
        success: function(data){

            hide_loading_box();
        }
    });
}


function __get_form_data(item_config)
{
    var form = item_config["form"];

    var normalized_f_data = {};
    var fs_data           = $('#' + form).serializeArray();

    $.each(fs_data, function(index, obj) {
        normalized_f_data[obj.name] = obj.value;
    });

    var property_id = parseInt(normalized_f_data.property_id);

    var form_data = {
        "action"        : normalized_f_data.action,
        "property_id"   : property_id,
        "token"         : Token.get_token(form)
    };

    if (item_config.type == 'single')
    {
        form_data.asset_id = normalized_f_data.asset_id;
    }

    switch(property_id)
    {
        //Services
        case 40:
            form_data.s_ip       = normalized_f_data.s_ip;
            form_data.s_port     = normalized_f_data.s_port;
            form_data.s_protocol = normalized_f_data.s_protocol;
            form_data.s_name     = normalized_f_data.s_name;

            if (typeof(item_config.p_data) != 'undefined' && item_config.p_data != null)
            {
                form_data.old_s_ip       = item_config.p_data.s_ip;
                form_data.old_s_port     = item_config.p_data.s_port;
                form_data.old_s_protocol = item_config.p_data.s_protocol;
                form_data.nagios         = item_config.p_data.nagios;
                form_data.version        = item_config.p_data.nagios;
            }
        break;

        //Macs
        case 50:
            form_data.mac_ip = normalized_f_data.mac_ip;
            form_data.mac    = normalized_f_data.mac;

            if (typeof(item_config.p_data) != 'undefined' && item_config.p_data != null)
            {
                form_data.old_mac_ip = item_config.p_data.extra;
                form_data.old_mac    = item_config.p_data.p_value;
            }
        break;

        //Software
        case 60:
            form_data.sw_cpe  = normalized_f_data.sw_cpe;
            form_data.sw_name = normalized_f_data.sw_name;

            if (typeof(item_config.p_data) != 'undefined' && item_config.p_data != null)
            {
                form_data.old_sw_cpe = item_config.p_data.sw_cpe;
            }
        break;

        //Asset properties
        default:
            form_data.p_value  = normalized_f_data.p_value;
            form_data.p_locked = normalized_f_data.p_locked;

            if (typeof(item_config.p_data) != 'undefined' && item_config.p_data != null)
            {
                form_data.old_p_value = item_config.p_data.p_value;
            }
        break;
    }

    return form_data;
}




/* Delete items (Properties, Software and Service) */
function delete_items(item_type, item_config)
{
    switch (item_type)
    {
        case "property":
            return delete_properties_from_db('delete_properties', item_config)
        break;

        case "software":
            return delete_properties_from_db('delete_software', item_config)
        break;

        case "service":
            return delete_properties_from_db('delete_services', item_config)
        break;
    }
}


/* Delete properties (Asset property, MAC Address, Software or Service) from database */
function delete_properties_from_db(action_type, item_config)
{
    var f_config = {
        'delete_properties' : {
            "form_id"            : "properties_form",
            "loading_container"  : "tp_container",
            "msg_delete"         : "<?php echo _('Deleting properties')?> ..."
        },
        "delete_software" : {
            "form_id"            : "software_form",
            "loading_container"  : "tsw_container",
            "msg_delete"         : "<?php echo _('Deleting software')?> ..."
        },
        "delete_services" : {
            "form_id"            : "services_form",
            "loading_container"  : "ts_container",
            "msg_delete"         : "<?php echo _('Deleting services')?> ..."
        }
    };


    var form_id = f_config[action_type]['form_id'];

    if (form_id == '')
    {
        return false;
    }

    //Getting properties to delete
    var token = Token.get_token(form_id);

    //AJAX data
    var item_data = {
        "action"            : action_type,
        "asset_id"          : item_config.asset_id,
        "selection_type"    : item_config.selection_type,
        "selection_filter"  : item_config.selection_filter,
        "items"             : item_config.items,
        "token"             : token
    };

    return $.ajax({
        type: "POST",
        url: item_config.controllers.submit_form,
        data: item_data,
        dataType: 'json',
        beforeSend: function(xhr) {

            remove_previous_notifications();

            show_loading_box(f_config[action_type]['loading_container'], f_config[action_type]['msg_delete'], '');
        },
        error: function(data){

            hide_loading_box();
        },
        success: function(data){

            hide_loading_box();
        }
    });
}


function edit_item(item_type, item_data)
{
    reset_form(item_type)

    switch (item_type)
    {
        case "property":

            //Hide all forms
            var fc_class  = '.td_ap_form';

            if ($(fc_class).length > 1)
            {
                $(fc_class).hide();

                //Set current form and property
                var form_type = 1;

                if (typeof(item_data.row_data) != 'undefined' && item_data.row_data.p_id == 50)
                {
                    form_type = 2;
                }

                $('#td_ap_form_' + form_type).show();
            }

            //Special case: Select last property type after clearing form
            if (typeof(item_data.row_data) != 'undefined' && item_data.row_data.p_id != '')
            {
                $('#tp_property_id').val(item_data.row_data.p_id);
            }

        break;

        case "software":
        break;

        case "service":
        break;
    }

    if (typeof(item_data.row_data) != 'undefined')
    {
        if (Object.keys(item_data.row_data).length > 1)
        {
            //Fill form with data
            fill_form(item_type, item_data);
        }
    }
}


/* Reset form (Properties, Software and Service) */
function reset_form(item_type)
{
    var f_config = {
        "property" : {
            "prefix"         : "tp",
            "form"           : "properties_form",
            "form_title"     :  "<?php echo _('Add New Property')?>",
            "default_action" : "new_property"
        },
        "software" : {
            "prefix"         : "tsw",
            "form"           : "software_form",
            "form_title"     :  "<?php echo _('Add New Software')?>",
            "default_action" : "new_software"
        },
        "service" : {
            "prefix"         : "ts",
            "form"           : "services_form",
            "form_title"     :  "<?php echo _('Add New Services')?>",
            "default_action" : "new_service"
        }
    };

    if (typeof(f_config[item_type]) == 'object')
    {
        var c_form        = "#" + f_config[item_type]["prefix"] + "_container form";
        var c_action      = "#" + f_config[item_type]["prefix"] + "_current_action";
        var cancel_button = "#" + f_config[item_type]["prefix"] + "_cancel";

        //Set default title
        $(c_form + ' legend').html(f_config[item_type]["form_title"]);

        //Reset form
        $(c_form).get(0).reset();

        //Action to execute
        $(c_action).val(f_config[item_type]["default_action"]);

        //Hide cancel button
        $(cancel_button).hide();

        switch (item_type)
        {
            case "property":
                //Set default form
                $(".td_ap_form").hide();
                $("#td_ap_form_1").show();
                $("#" + f_config[item_type]["prefix"] + "_property_id").val('');
            break;

            case "software":
                //Remove CPE results
                $("#" + f_config[item_type]["prefix"] + "_container .cpe_results div").empty();
                $("#" + f_config[item_type]["prefix"] + "_property_id").val(60);
            break;

            case "services":
                $("#" + f_config[item_type]["prefix"] + "_property_id").val(40);
            break;
        }
    }
}


/* Fill form with item data(Properties, Software and Service) */
function fill_form(item_type, item_data)
{
    var f_config = {
        "property" : {
            "prefix"         : "tp",
            "form"           : "properties_form",
            "form_title"     :  "<?php echo _('Edit Property')?>",
            "default_action" : "edit_property"
        },
        "software" : {
            "prefix"         : "tsw",
            "form"           : "software_form",
            "form_title"     :  "<?php echo _('Edit Software')?>",
            "default_action" : "edit_software"
        },
        "service" : {
            "prefix"         : "ts",
            "form"           : "services_form",
            "form_title"     :  "<?php echo _('Edit Services')?>",
            "default_action" : "edit_service"
        }
    };


    if (typeof(f_config[item_type]) == 'object')
    {
        var c_form        = "#" + f_config[item_type]["prefix"] + "_container form";
        var c_action      = "#" + f_config[item_type]["prefix"] + "_current_action";
        var cancel_button = "#" + f_config[item_type]["prefix"] + "_cancel";

        //Set default title
        $(c_form + ' legend').html(f_config[item_type]["form_title"]);

        //Action to execute
        $(c_action).val(f_config[item_type]["default_action"]);

        //Show cancel button
        $(cancel_button).show();


        var __item_data = item_data.row_data;
        var __item_id   = item_data.row_id;

        switch(__item_data.p_id)
        {
            //Services
            case 40:
                $('#ts_item_id').val(__item_id);
                $('#ts_property_id').val(__item_data.p_id);

                $('#s_ip').val(__item_data.s_ip);
                $('#s_port').val(__item_data.s_port);
                $('#s_name').val(__item_data.s_name);
                $('#s_protocol').val(__item_data.s_protocol);
                $('#nagios').val(__item_data.nagios);
            break;

            //Macs
            case 50:
                $('#tp_item_id').val(__item_id);
                $('#tp_property_id').val(__item_data.p_id);

                $('#mac_ip').val(__item_data.extra);
                $('#mac').val(__item_data.p_value);
            break;

            //Software
            case 60:
                $('#tsw_item_id').val(__item_id);
                $('#tsw_property_id').val(__item_data.p_id);
                $('#sw_cpe').val(__item_data.sw_cpe);
                $('#sw_name').val(__item_data.sw_name);
            break;

            //Asset properties
            default:
                $('#tp_item_id').val(__item_id);
                $('#tp_property_id').val(__item_data.p_id);


                $('#p_value').val(__item_data.p_value);
                $('#p_locked').prop('checked', __item_data.locked);
            break;
        }
    }
}


/* Handlers only for services */


function fill_ports()
{
    var port     = $('#s_port').val();
    var protocol = $('#s_protocol option:selected').text();

    var key = port + ' - ' + protocol;
        key = key.toLowerCase();

    //Reset service
    $('#s_name').val('');

    if(typeof(ports) == 'object')
    {
        if (typeof(ports[key]) !== 'undefined')
        {
            $('#s_name').val(ports[key]);
        }
    }
}


/* Add new port to database */
function add_new_port()
{
    var __cfg = <?php echo Asset::get_path_url()?>;

    var token = Token.get_token("services_form");

    var asset_id = $('#ts_asset_id').val();
    var port     = $('#s_port').val();
    var protocol = $('#s_protocol').val();
    var s_name   = $('#s_name').val();

    //AJAX data
    var s_data = {
        "asset_id"   : asset_id,
        "s_port"     : port,
        "s_protocol" : protocol,
        "s_name"     : s_name,
        "action"     : "add_port",
        "token"      : token
    };

    $.ajax({
        type: "POST",
        url: __cfg.asset.controllers + 'asset_actions.php',
        data: s_data,
        dataType: 'json',
        success: function(data){

            //Check expired session
            var session = new Session(data, '');

            if (session.check_session_expired() == true)
            {
                session.redirect();

                return;
            }

            if (typeof(data) != 'undefined' && data != null && data.status == 'success')
            {
                var s_key    = port + ' - ' + protocol;
                ports[s_key] = s_name;
            }
        }
    });
}


/* Enable/Disable Nagios by service */
function toggle_monitoring(item_type, s_config)
{
    var __cfg = <?php echo Asset::get_path_url()?>;

    //Getting services to delete
    var token = Token.get_token('services_form');

    //AJAX data
    var s_data = {
        "action"            : s_config.action,
        "asset_id"          : s_config.asset_id,
        "selection_type"    : s_config.selection_type,
        "selection_filter"  : s_config.selection_filter,
        "items"             : s_config.items,
        "token"             : token
    };

    if (s_config.action == 'enable_monitoring')
    {
        var action_msg = "<?php echo _('Monitoring services')?> ...";
    }
    else
    {
        var action_msg = "<?php echo _('Unmonitoring services')?> ...";
    }

    return $.ajax({
        type: "POST",
        url: s_config.controllers.submit_form,
        data: s_data,
        dataType: 'json',
        beforeSend: function(xhr) {
            $('#ts_av_info').html('');
            $('[data-bind="ts_m-actions"]').val('');
            show_loading_box('ts_container', action_msg, '');
        },
        error: function(data){

            hide_loading_box();
        },
        success: function(data){

            hide_loading_box();
        }
    });
}


/* Autocomplete for Software CPE */
function bind_cpe_actions(cpe_config)
{
    $("#" + cpe_config.widget_id).autocomplete(cpe_config.data_provider,
    {
        minChars: cpe_config.minChars,
        width: cpe_config.width,
        matchContains: cpe_config.matchContains,
        multiple: cpe_config.multiple,
        autoFill: cpe_config.autoFill,
        mustMatch: cpe_config.mustMatch,
        scroll: cpe_config.scroll,
        scrollHeight: cpe_config.scrollHeight,
        extraParams: cpe_config.extraParams,
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
        if (typeof(item) != 'undefined' && item != null)
        {
            $("#" + cpe_config.input_id).val((item[0].split('###'))[0]);

            $('#' + cpe_config.widget_id + '_sr div').empty();
        }
        else
        {
            $("#" + cpe_config.input_id).val('');

            var sw_name = $("#" + cpe_config.widget_id).val();

            if (sw_name != '' && cpe_config.mustMatch == true)
            {
                var __error_msg = '<?php echo _('Error! CPE not found.  Please, you must use a registered CPE')?>';

                if (typeof(cpe_config.error_msg) != 'undefined' && cpe_config.error_msg !== '')
                {
                    __error_msg = cpe_config.error_msg
                }

                $('#' + cpe_config.widget_id + '_sr div').html('<span class="small">' + __error_msg + '</span>');
            }
        }
    });
}


/* Format functions */

function remove_previous_notifications()
{
    //Success notifications
    $('#av_msg_info').remove();

    //Tab notifications
    $("div[id$='_av_info']").empty();
}
