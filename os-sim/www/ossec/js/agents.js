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


function GB_onclose(url, params)
{
    if (typeof params == 'undefined')
    {
        params = {};
    }

    try
    {
        var dt_table = top.frames['main'].$('#agent_table').dataTable();

        if (params.action == 'agent_deployed')
        {
            top.frames['main'].$("#c_info").html(notify_success(params.msg));
            dt_table.fnReloadAjax();
        }
        else if (params.action == 'agent_linked')
        {
            top.frames['main'].$("#c_info").html(notify_success(params.msg));

            var agent_id = '#cont_agent_'+ params.agent;

            var __agent_row  = dt_table.$(agent_id);
            var __agent_node = dt_table.$(agent_id).get(0);

            var agent_data = dt_table.fnGetData(__agent_row[0]);
            var agent_pos  = dt_table.fnGetPosition(__agent_node);

            // Update the data array with new data
            var asset_id   = params.new_asset.id;
            var asset_name = params.new_asset.name;

            agent_data['DT_RowData']['asset_id'] = asset_id;
            dt_table.fnUpdate(asset_name, agent_pos, 3, false, false);

            //Agent actions
            var asset_actions = params.new_asset.actions;

            top.frames['main'].$('td:last', __agent_node).html(asset_actions);
            top.frames['main'].$('td:last img', __agent_node).tipTip({"attribute" : "data-title", "maxWidth": "250px", "defaultPosition": "top"});

            top.frames['main'].$('td:last', __agent_node).addClass('agent_actions');
            top.frames['main'].$('td:last a', __agent_node).off('click').click(function() {

                var id = $(this).attr("id");
                get_action(id);
            });
        }
        else if (params.action == 'agent_created')
        {
            top.frames['main'].$("#c_info").html(notify_success(params.msg));
            dt_table.fnAddData(params.new_agent);
        }
    }
    catch(Err){}
}


function load_hids_trend(agent_row, agent_data)
{
    var no_trend = "<div style='color:gray; margin:15px; text-align:center;'>" + ossec_msg['no_trend_chart'] + "</div>";

    try
    {
        var agent_data = {
            'sensor_id'   : $('#sensors').val(),
            'agent_id'    : agent_data[1],
            'asset_id'    : agent_data['DT_RowData']['asset_id'],
            'agent_name'  : agent_data[2],
            'ip_cidr'     : agent_data[4],
            'agent_status': agent_data['DT_RowData']['agent_status']['id']
        }

        $.ajax({
            type: "POST",
            url: "/ossim/ossec/providers/ossec_status/load_trend.php",
            data: agent_data,
            error: function(data){

                $('td:last', agent_row).html(no_trend);
            },
            success: function(data){

                //Check expired session
                var session = new Session(data, '');

                if (session.check_session_expired() == true)
                {
                    session.redirect();
                    return;
                }

                data = (data != '') ? data : ' - ';

                $('td:last', agent_row).html(data);
            }
        });
    }
    catch(err)
    {
        $('td:last', agent_row).html(no_trend);
        //console.log(err);
    }
}



function get_idm_data(agent_row, agent_data)
{
    //AJAX data
    var idm_data = {
        "sensor_id"  : $('#sensors').val(),
        "agent_id"   : $(agent_row).find('td:nth-child(2)').text(),
        "agent_name" : $(agent_row).find('td:nth-child(3)').text(),
        "agent_ip"   : $(agent_row).find('td:nth-child(5)').text(),
        "asset_id"   : agent_data['DT_RowData']['asset_id'],
    };

    var dt_table = $('#agent_table').dataTable();

    $.ajax({
        type: "POST",
        url: "/ossim/ossec/providers/agents/agent_idm_info.php",
        data: idm_data,
        dataType: "json",
        beforeSend: function(xhr)
        {
            $('.td_c_ip', agent_row).html("<img src='/ossim/pixmaps/loading.gif' style='width: 12px; height: 12px;'/>");
            $('.td_c_ud', agent_row).html("<img src='/ossim/pixmaps/loading.gif' style='width: 12px; height: 12px;'/>");
        },
        error: function(data)
        {
            $('.td_c_ip', agent_row).html("-");
            $('.td_c_ud', agent_row).html("-");
        },
        success: function(data){

            var current_ip   = '-';
            var current_user = '-';

            if (typeof(data) != 'undefined' && data != null && data.status == 'success')
            {
                current_ip   = data.data.current_ip;
                current_user = data.data.current_user;
            }

            //Updating the html
            $('.td_c_ip', agent_row).html(current_ip);
            $('.td_c_ud', agent_row).html(current_user);

            //Updating the datatable value but do not redraw the table
            dt_table.fnUpdate(current_ip, agent_row, 5, false, false);
            dt_table.fnUpdate(current_user, agent_row, 6, false, false);
        }
    });
}


function get_agent_info(that)
{
    $(that).tipTip({
       defaultPosition: "top",
       maxWidth: "320px",
       edgeOffset: 3,
       content: function(e){

            var sensor_id  = $('#sensors').val();
            var agent_id   = $(that).parent().find('td:nth-child(2)').text();
            var agent_name = $(that).parent().find('td:nth-child(3)').text();
            var agent_ip   = $(that).parent().find('td:nth-child(5)').text();

            var loading_content = "<table class='t_agent_mi'>" +
                            "<tr>" +
                                "<td class='td_loading'>" +
                                    "<img src='/ossim/pixmaps/loading.gif' style='width: 12px; height: 12px;'/>" +
                                "</td>" +
                                "<td style='padding-left:5px;'>"+ ossec_msg['load_agent'] + "</td>" +
                            "</tr>" +
                          "</table>";

            if ($(that).find('div').length >= 1)
            {
                loading_content = Base64.decode($(that).find('div').html());
            }
            else
            {
                if (typeof(xhr) == 'object' && xhr != 'null')
                {
                    xhr.abort();
                }

                xhr = $.ajax({
                  type: 'POST',
                  data: 'sensor_id='+sensor_id+'&agent_id='+agent_id+'&agent_name='+agent_name+'&agent_ip='+agent_ip,
                  url: '/ossim/ossec/providers/agents/agent_info.php',
                  success: function (response) {

                    var base64_c = Base64.encode(response);
                    $(that).append("<div style='display:none;'>"+base64_c+"</div>");

                    e.content.html(response); // the var e is the callback function data (see above)
                  }
                });
             }

          // We temporary show a Please wait text until the ajax success callback is called.
          return loading_content;
       }
    });
}


function manage_agent(action)
{
    var form_id = $('form[method="post"]').attr("id");
    var token   = Token.get_token('f_agents');

    var agent_data  = $('#'+form_id).serialize() + "&token=" + token;

    check_agent_ip(agent_data).done(function(data) {

        var warning_msg = data.data;

        if (warning_msg != '')
        {
            var keys = {"yes": labels['yes'], "no": labels['no']};
            var message  = warning_msg + '.  ';
                message += (action == 'new') ?  ossec_msg['add_agent'] : ossec_msg['link_asset']

            av_confirm(message, keys).done(function(){
                save_agent_info(agent_data, action);
            });
        }
        else
        {
            save_agent_info(agent_data, action);
        }
    }).fail(function(xhr) {

        //Check expired session
        var session = new Session(xhr.responseText, '');

        if (session.check_session_expired() == true)
        {
            session.redirect();
            return;
        }

        var __error_msg = ossec_msg['unknown_error'];

        if (typeof(xhr.responseText) != 'undefined' && xhr.responseText != '')
        {
            __error_msg = xhr.responseText;
        }

        $("#av_info").html(notify_error(__error_msg));
        $("#av_info").fadeIn(4000);

        window.scrollTo(0,0);
    });
}


function save_agent_info(agent_data, action)
{
    if (action == 'new')
    {
        var a_url = "/ossim/ossec/controllers/agents/save_agent.php";
    }
    else
    {
        var a_url = "/ossim/ossec/controllers/agents/link_asset_to_agent.php";
    }


    $.ajax({
        type: "POST",
        url: a_url,
        data: agent_data,
        dataType: "json",
        beforeSend: function(xhr){

            show_loading_box('c_agent_form', ossec_msg['p_action'], '');

            $("#av_info").html('');
        },
        error: function(xhr){

            //Check expired session
            var session = new Session(xhr.responseText, '');

            if (session.check_session_expired() == true)
            {
                session.redirect();
                return;
            }

            var __error_msg = ossec_msg['unknown_error'];

            if (typeof(xhr.responseText) != 'undefined' && xhr.responseText != '')
            {
                __error_msg = xhr.responseText;
            }

            hide_loading_box();

            $("#av_info").html(notify_error(ossec_msg['unknown_error']));

            $("#av_info").fadeIn(4000);

            window.scrollTo(0,0);
        },
        success: function(data){

            hide_loading_box();

            var cnd_1  = (typeof(data) == 'undefined' || data == null);
            var cnd_2  = (typeof(data) != 'undefined' && data != null && data.status != 'success');

            if (cnd_1 || cnd_2)
            {
                var error_msg = ossec_msg['unknown_error'];

                if (cnd_2 == true)
                {
                    if (typeof(data.data.html_errors) != 'undefined')
                    {
                        error_msg = data.data.html_errors;
                    }
                    else if(typeof(data.data) != 'undefined')
                    {
                        error_msg = data.data;
                    }
                }

                error_msg = "<div style='padding-left: 10px;'>" + error_msg + "</div>";

                $("#av_info").html(notify_error(error_msg));

                var __style = 'width: 100%; text-align:center; margin:0px auto;';
                show_notification('av_info', error_msg, 'nf_error', null, true,  __style);

                window.scrollTo(0,0);
            }
            else
            {
                if (action == 'new')
                {
                    //New HIDS agent
                    var status = data.data.split("###");
                    var new_agent = eval(status[2]);

                    if (data.status == 'warning')
                    {
                        top.frames['main'].$('#agent_table').dataTable().fnAddData(new_agent);

                        $("#av_info").html(notify_warning(status[0]));
                    }
                    else
                    {
                        parent.GB_close({"action" : "agent_created", "msg" : status[0], "new_agent" : new_agent});
                    }
                }
                else
                {
                    parent.GB_close({"action" : "agent_linked", "msg" : data.data, "agent" : $('#agent_id').val(), "new_asset" : data.asset});
                }
            }
        }
    });
}


function asset_tree_by_sensor(tree_container, sensor_id)
{
    var tree_container = '#' + tree_container;
    var key_tree = 'sensor_' + sensor_id;

    $(tree_container).dynatree({
        initAjax: { url: '/ossim/tree.php?key=' + key_tree + '&extra_options=only_unlinked_to_hids_agents'},
        clickFolderMode: 2,
        onActivate: function(dtnode) {

            if (typeof(dtnode.data) != 'undefined' && dtnode.data != null)
            {
                //Getting IP address
                regexp    = /([^\s]+)\s+\((\d+\.\d+\.\d+\.\d+)\)/;
                _aux_item = regexp.exec(dtnode.data.val);

                if (typeof(_aux_item) != 'undefined' && _aux_item != null)
                {
                    var asset_id    = dtnode.data.key.replace('host_', '');
                    var asset_ip    = _aux_item[2];
                    var asset_name  = _aux_item[1];
                    var asset_descr = dtnode.data.val;

                    $('#asset').val(asset_descr);
                    $('#asset_descr').val(asset_descr);
                    $('#asset_id').val(asset_id);
                    $('#agent_name').prop('disabled', false).val(asset_name);
                    $('#ip_cidr').prop('disabled', false).val(asset_ip);
                    $('#send').prop('disabled', false);

                    if ($('#dhcp').length >= 1)
                    {
                        $('#dhcp').prop('disabled', false);
                    }
                }
            }
            else
            {
                var asset_descr = $('#asset_descr').val();
                var asset       = $('#asset').val();

                if (asset_descr != '' && asset_descr != asset)
                {
                    $('#asset_id').val('');
                    $('#agent_name').prop('disabled', true).val('');
                    $('#ip_cidr').prop('disabled', true).val('');
                    $('#send').prop('disabled', true);

                    if ($('#dhcp').length >= 1)
                    {
                        $('#dhcp').prop('disabled', true);
                    }
                }
            }

            if ($('#dhcp').length >= 1)
            {
                $('#dhcp').prop('checked', false);
            }

            dtnode.deactivate();
        },
        onDeactivate: function(dtnode) {},
        onLazyRead: function(dtnode){
            dtnode.appendAjax({
                url: '/ossim/tree.php',
                data: {key: dtnode.data.key, page: dtnode.data.page, extra_options: 'only_unlinked_to_hids_agents'}
            });
        }
    });
}


function check_agent_ip(agent_data)
{
    return $.ajax({
        type: 'POST',
        url: "/ossim/ossec/controllers/agents/check_agent_ip.php",
        data: agent_data,
        dataType: 'json'
    });
}


function show_link_asset_form(id)
{
    $('#c_info').html('');
    $("#c_info").stop(true, true);

    clearTimeout(timer);

    var sensor_id  = $('#sensors').val();
    var f_data     = id.split('_##_');
    var agent_data = f_data[1];


    var parameters = "sensor_id="+sensor_id+"&agent_data="+agent_data;
    var url        ='/ossim/ossec/views/agents/link_asset_form.php?'+parameters;

    GB_show(ossec_msg['gb_link_asset'], url, 350, 500);
}


function get_action(id)
{
    var action = null;

    if (id.match("_key_##_") != null)
    {
        send_action(id, 'extract_key');
    }
    else if (id.match("_del_##_") != null)
    {
        var keys = {"yes": labels['yes'], "no": labels['no']};
        av_confirm(ossec_msg['delete_agent'], keys).done(function(){
            send_action(id, 'delete_agent');
        });
    }
    else if (id.match("_integrity_##_") != null)
    {
        send_action(id, 'check_integrity');
    }
    else if (id.match("_file_##_") != null)
    {
        send_action(id, 'modified_files');
    }
    else if (id.match("_reg_##_") != null)
    {
        send_action(id, 'modified_reg_files');
    }
    else if (id.match("_rchk_##_") != null)
    {
        send_action(id, 'rootcheck');
    }
    else if (id.match("_restart_##_") != null)
    {
        send_action(id, 'restart_agent');
    }
    else if (id.match("_w_installer_##_") != null)
    {
        download_agent(id, 'windows');
    }
    else if (id.match("_w_deployment_##_") != null)
    {
        show_w_deployment_form(id);
    }
    else if (id.match("_link_to_asset_##_") != null)
    {
        show_link_asset_form(id);
    }
}


function send_action(id, action)
{
    var sensor_id = $('#sensors').val();
    var id        = id.split("_##_");

    show_loading_box('tabs', ossec_msg['p_action'], '');

    var token = Token.get_token('f_agents');

    $.ajax({
        type: "POST",
        url: "/ossim/ossec/controllers/agents/agent_actions.php",
        data: "id="+id[1]+"&sensor_id="+sensor_id+"&action="+action+"&token="+token,
        dataType: "json",
        beforeSend: function(xhr) {

            $("#changes_in_files").html('');
            $("#c_info").html('');
            $("#c_info").stop(true, true);

            clearTimeout(timer);
        },
        error: function(data){

            //Check expired session
            var session = new Session(data, '');

            if (session.check_session_expired() == true)
            {
                session.redirect();
                return;
            }

            $("#changes_in_files").html('');
            $("#c_info").html('');

            hide_loading_box();

            $("#c_info").html(notify_error(ossec_msg['unknown_error']));
            $("#c_info").fadeIn(4000);

            window.scrollTo(0,0);
        },
        success: function(data){

            hide_loading_box();

            var cnd_1  = (typeof(data) == 'undefined' || data == null);
            var cnd_2  = (typeof(data) != 'undefined' && data != null && data.status != 'success');

            if (cnd_1 || cnd_2)
            {
                if (cnd_1 == true)
                {
                    var error_msg = ossec_msg['unknown_error'];
                }
                else
                {
                    if (typeof(data.data.html_errors) != 'undefined')
                    {
                        var error_msg = data.data.html_errors;
                    }
                    else
                    {
                        var error_msg = data.data;
                    }
                }

                error_msg = "<div style='padding-left: 10px;'>"+error_msg+"</div>";

                $("#c_info").html(notify_error(error_msg));
                $("#c_info").fadeIn(4000);

                 window.scrollTo(0,0);
            }
            else
            {
                switch (action)
                {
                    case "extract_key":
                        $("#changes_in_files").html(notify_info(data.data));
                        $("#changes_in_files").fadeIn(4000);
                        $("#changes_in_files div:first").css('width', '95%');
                    break;

                    case "delete_agent":
                        var row_id = 'cont_agent_'+ id[1]
                        var tr     = document.getElementById(row_id);

                        if (tr != null)
                        {
                            $('#agent_table').dataTable().fnDeleteRow(tr, null, true);
                            $("#c_info").html(notify_success(data.data));
                            $("#c_info").fadeIn(4000);

                            window.scrollTo(0,0);
                            timer = setTimeout('$("#c_info").fadeOut(4000);', 5000);
                        }
                    break;

                    case "check_integrity":
                        $("#c_info").html(notify_success(data.data));
                        $("#c_info").fadeIn(4000);

                        window.scrollTo(0,0);
                        timer = setTimeout('$("#c_info").fadeOut(4000);', 5000);

                    break;

                    case "restart_agent":
                        $("#c_info").html(notify_success(data.data));
                        $("#c_info").fadeIn(4000);

                        window.scrollTo(0,0);
                        timer = setTimeout('$("#c_info").fadeOut(4000);', 5000);

                    break;

                    case "modified_files":
                    case "modified_reg_files":
                    case "rootcheck":
                        $("#changes_in_files").html(data.data);

                        $("#tf").tablePagination({
                            currPage : 1,
                            rowsPerPage : 25,
                            optionsForRows: [5,15,25,50],
                            firstArrow : (new Image()).src="/ossim/pixmaps/first.gif",
                            prevArrow : (new Image()).src="/ossim/pixmaps/prev.gif",
                            lastArrow : (new Image()).src="/ossim/pixmaps/last.gif",
                            nextArrow : (new Image()).src="/ossim/pixmaps/next.gif"
                        });

                        $("#changes_in_files").fadeIn(4000);
                    break;
                }
            }
        }
    });
}


function download_agent(id, os_type)
{
    $('#c_info').html('');
    $("#c_info").stop(true, true);

    clearTimeout(timer);

    var msg = (os_type == 'windows') ? ossec_msg['d_oc_action_w'] : ossec_msg['d_oc_action_u'];

    show_loading_box('tabs', msg, '');

    var sensor_id = $('#sensors').val();
    var id        = id.split('_##_');
    var agent_id  = id[1];

    var token     = Token.get_token('f_ossec_agent');

    var html_form = '<div id="c_ossec_agent" style="display:none;">' +
                        '<form name="f_ossec_agent" id="f_ossec_agent" method="POST" action="/ossim/ossec/controllers/agents/download_agent.php" target="iframeDownload">' +
                            '<input type="hidden" name="sensor_id" id="sensor_id" value="'+sensor_id+'"/>' +
                            '<input type="hidden" name="token" id="token_f_ossec_agent" value="'+token+'"/>' +
                            '<input type="hidden" name="agent_id" id="agent_id" value="'+agent_id+'"/>' +
                            '<input type="hidden" name="os_type" id="os_type" value="'+os_type+'"/>' +
                        '</form>' +

                        '<iframe name="iframeDownload" id="iframeDownload"></iframe>' +
                    '</div>';

    $('#c_ossec_agent').remove();
    $('#tabs').append(html_form);

    $('#f_ossec_agent').submit();
}


function show_w_deployment_form(id)
{
    $('#c_info').html('');
    $("#c_info").stop(true, true);

    clearTimeout(timer);

    var sensor_id  = $('#sensors').val();
    var f_data     = id.split('_##_');
    var agent_data = f_data[1];


    var parameters = "sensor_id="+sensor_id+"&agent_data="+agent_data;
    var url        ='/ossim/ossec/views/agents/a_deployment_form.php?'+parameters;

    GB_show(ossec_msg['a_deployment_w'], url, 500, 800);
}


function deploy_windows_agent()
{
    var form_id = $('form[method="post"]').attr("id");

    $.ajax({
        type: "POST",
        url: "/ossim/ossec/controllers/agents/a_deployment.php",
        data: $('#'+form_id).serialize(),
        dataType: "json",
        beforeSend: function(xhr) {

            show_loading_box('container_center', ossec_msg['deploying_agent']);

            $("#c_info").html('');
            $("#c_info").stop(true, true);

            $("#c_help").html('');

            clearTimeout(timer);
        },
        error: function(data){

            hide_loading_box();

            $("#c_info").html(notify_error(ossec_msg['unknown_error']));
            $("#c_info").fadeIn(4000);

            window.scrollTo(0,0);

            Token.add_to_forms();
        },
        success: function(data){

            var cnd_1  = (typeof(data) == 'undefined' || data == null);
            var cnd_2  = (typeof(data) != 'undefined' && data != null && data.status != 'success');

            if (cnd_1 || cnd_2)
            {
                hide_loading_box();

                var error_msg = (cnd_1 == true) ? ossec_msg['unknown_error'] : data.data;
                    error_msg = "<div style='padding-left:15px; text-align: left;'>" + error_msg + "</div>";

                    error_msg = "<div style='text-align: left; padding-left:5px;'>" + ossec_msg['error_header'] + "</div>" + error_msg;

                if (data.status == 'error')
                {
                    $("#c_info").html(notify_error(error_msg));
                }
                else
                {
                    $("#c_info").html(notify_warning(error_msg));
                }

                Token.add_to_forms();
            }
            else
            {
                var job_id = data.data['job_id'];

                check_deployment_status(job_id);
            }

            window.scrollTo(0,0);
        }
    });
}


function check_deployment_status(job_id)
{
     $.ajax({
        type: "POST",
        url: "/ossim/ossec/controllers/agents/a_deployment_actions.php",
        data: "job_id=" + job_id,
        dataType: "json",
        beforeSend: function(xhr) {
            clearTimeout(timer);
        },
        error: function(data){

            hide_loading_box();

            $("#c_info").html(notify_error(ossec_msg['unknown_error']));

            $("#c_info").fadeIn(4000);

            window.scrollTo(0,0);

            Token.add_to_forms();
        },
        success: function(data){

            var cnd_1  = (typeof(data) == 'undefined' || data == null);
            var cnd_2  = (typeof(data) != 'undefined' && data != null && data.status == 'error');

            if (cnd_1 || cnd_2)
            {
                hide_loading_box();

                Token.add_to_forms();

                var error_msg = (cnd_1 == true) ? ossec_msg['unknown_error'] : data.data;
                    error_msg = "<div style='padding-left:15px; text-align: left;'>" + error_msg + "</div>";

                    error_msg = "<div style='text-align: left; padding-left:5px;'>" + ossec_msg['error_header'] + "</div>" + error_msg;

                $("#c_info").html(notify_error(error_msg));

                if (typeof(data.help) != 'undefined')
                {
                    $("#c_help").html(notify_info("<div style='padding-left:5px;'>" + data.help + "</div>"));
                }
            }
            else
            {
                switch (data.status)
                {
                    case "success":
                        parent.GB_close({"action" : "agent_deployed", "msg" : data.data});
                    break;

                    case "in_progress":
                        $('.r_lp').html(data.data.txt);
                        timer = setTimeout(function(){ check_deployment_status(job_id); }, 2000);
                    break;
                }
            }
        }
    });
}


function load_agent_tab(tab)
{
    ajax_requests.abort();

    var sensor_id = $('#sensors').val();

    var query_string = "tab="+tab+"&sensor_id="+sensor_id;

    if (tab == "#tab2" && $('#ac_key').length >= 1)
    {
        query_string = query_string + "&ac_key=" + $('#ac_key').val();
    }

    xhr = $.ajax({
        type: "POST",
        url: "/ossim/ossec/providers/agents/load_tabs.php",
        data: query_string,
        beforeSend: function(xhr){

            $('#c_info').html('');
            $("#c_info").stop(true, true);

            hide_select();
            $(tab).hide();

            parent.$('#main').height(700);
            show_loading_box('tabs', ossec_msg['loading'], '');

            clearTimeout(timer);
        },
        success: function(msg){

            hide_loading_box();

            var status = msg.split("###");

            switch(status[0])
            {
                case "1":
                    if (tab == "#tab1")
                    {
                        $(tab).html(status[1]);
                    }
                    else if (tab == "#tab2")
                    {
                        $(tab).html(status[1]);

                        $('textarea').elastic();

                        $('#ac_key').off('change');
                        $('#ac_key').change(function(){
                            load_agent_tab(tab)
                        });

                        $('.t_syscheck tbody tr td').removeClass('odd even');

                        $('#table_sys_directories .dir_tr:odd td').addClass('odd');
                        $('#table_sys_directories .dir_tr:even td').addClass('even');


                        $('#table_sys_wentries .went_tr:odd td').addClass('odd');
                        $('#table_sys_wentries .went_tr:even td').addClass('even');

                        $('#table_sys_reg_ignores .regi_tr:odd td').addClass('odd');
                        $('#table_sys_reg_ignores .regi_tr:even td').addClass('even');

                        $('#table_sys_ignores .ign_tr:odd td').addClass('odd');
                        $('#table_sys_ignores .ign_tr:even td').addClass('even');
                    }
                    else if (tab == "#tab3")
                    {
                        if ($('#container_code').length < 1)
                        {
                            var content = "<div id='container_code'>" +
                                                "<textarea id='code'></textarea>" +
                                                "<div class='button_box'>" +
                                                    "<div><input type='button' class='save' id='send_7' value='"+labels['save']+"'/></div>" +
                                                "</div>" +
                                          "</div>";

                            $(tab).html(content);
                        }

                        $('#send_7').off('click')
                        $('#send_7').click(function() {
                            save_agent_conf();
                        });

                        if (typeof(editor) == 'undefined' || editor == null)
                        {
                            editor = new CodeMirror(CodeMirror.replace("code"), {
                                parserfile: "parsexml.js",
                                stylesheet: "/ossim/style/xmlcolors.css",
                                path: "/ossim/js/codemirror/",
                                continuousScanning: 500,
                                content: status[1],
                                lineNumbers: true
                            });
                        }
                        else
                        {
                            editor.setCode(status[1]);
                        }
                   }

                break;

                case "2":

                    var content = "<div id='msg_init_error'><div style='margin-left: 70px; text-align:center;'>"+notify_error(status[1])+"</div></div>";

                    $(tab).html(content);

                break;
            }

            $(tab).show();
            show_select();

            $('#sensors').removeAttr('disabled');

            $('#sensors').off('change');
            $('#sensors').change(function(){
                load_agent_tab(tab);
            });
        }
    });

    ajax_requests.add_request(xhr);
}


function save_agent_conf()
{
    var tab       = $(".active a").attr("href");
    var sensor_id = $('#sensors').val();

    show_loading_box('tabs', ossec_msg['p_action'], '');

    var token = Token.get_token('agent_cnf');

    $('#c_info').html('');
    $("#c_info").stop(true, true);

    clearTimeout(timer);

    if (tab == '')
    {
        $('#c_info').html(notify_error(ossec_msg['loading']));

        return false;
    }

    var data = "tab="+tab+"&sensor_id="+sensor_id+"&token="+token;

    if (tab == '#tab2')
    {
        data += "&"+ $('#form_syscheck').serialize();
    }
    else if (tab == '#tab3')
    {
        data += "&data="+Base64.encode(htmlentities(editor.getCode(), 'HTML_ENTITIES'));
    }

    $.ajax({
        type: "POST",
        url: "/ossim/ossec/controllers/agents/save_cnf.php",
        data: data,
        dataType: 'json',
        error: function(data){

            hide_loading_box();

            $("#c_info").html(notify_error(ossec_msg['unknown_error']));

            $("#c_info").fadeIn(4000);

            window.scrollTo(0,0);
        },
        success: function(data){

            hide_loading_box();

            var cnd_1  = (typeof(data) == 'undefined' || data == null);
            var cnd_2  = (typeof(data) != 'undefined' && data != null && data.status == 'error');

            if (cnd_1 || cnd_2)
            {
                var error_msg = (cnd_2) ? data.data : ossec_msg['unknown_error'];
                    error_msg = "<div style='padding-left: 15px; text-align:left;'>" + error_msg + "</div>";

                $('#c_info').html(notify_error(error_msg));
                $('#c_info').fadeIn(2000);
            }
            else
            {
                if (data.status == "success" || data.status == "warning")
                {
                    var _msg = "<div style='padding-left: 15px; text-align:left;'>" + data.msg + "</div>";

                    if (data.status == "success")
                    {
                        $("#c_info").html(notify_success(_msg));
                    }
                    else
                    {
                        $("#c_info").html(notify_warning(_msg));
                    }

                    $('#c_info').fadeIn(2000);

                    clearTimeout(timer);

                    if (data.status == "success")
                    {
                        timer = setTimeout('$("#c_info").fadeOut(4000);', 5000);
                    }
                    else
                    {
                        if (editor != null)
                        {
                            editor.setCode(data.data);
                        }
                    }
                }
            }

            window.scrollTo(0,0);
        }
    });
}


function add_row(id, action)
{
    $.ajax({
        type: "POST",
        url: "/ossim/ossec/controllers/agents/syscheck_actions.php",
        data: "action="+action,
        success: function(msg){

            var status = msg.split("###");

            if (status[0] != "2")
            {
                if (id.match("tbody_") != null)
                {
                    $(id).html(status[1]);
                }
                else
                {
                    $('#'+id).after(status[1]);
                }

                $('textarea').elastic();

                switch (action)
                {
                    case "add_directory":
                        $('#table_sys_directories table').css('background', 'transparent');
                        $('#table_sys_directories .dir_tr td').removeClass('odd even');
                        $('#table_sys_directories .dir_tr:odd td').addClass('odd');
                        $('#table_sys_directories .dir_tr:even td').addClass('even');
                    break;

                    case "add_wentry":
                        $('#table_sys_wentries table').css('background', 'transparent');
                        $('#table_sys_wentries .went_tr').removeClass('odd even');
                        $('#table_sys_wentries .went_tr:odd td').addClass('odd');
                        $('#table_sys_wentries .went_tr:even td').addClass('even');
                    break;

                    case "add_reg_ignore":
                        $('#table_sys_reg_ignores table').css('background', 'transparent');
                        $('#table_sys_reg_ignores .regi_tr td').removeClass('odd even');
                        $('#table_sys_reg_ignores .regi_tr:odd td').addClass('odd');
                        $('#table_sys_reg_ignores .regi_tr:even td').addClass('even');
                    break;

                    case "add_ignore":
                        $('#table_sys_ignores table').css('background', 'transparent');
                        $('#table_sys_ignores .ign_tr td').removeClass('odd even');
                        $('#table_sys_ignores .ign_tr:odd td').addClass('odd');
                        $('#table_sys_ignores .ign_tr:even td').addClass('even');
                    break;
                }

            }
        }
    });
}


function delete_row(id, action)
{
    if (confirm (ossec_msg['delete_row']))
    {
        if ($('#'+id).length >= 1)
        {
            $('#'+id).remove();

            switch (action)
            {
                case "delete_directory":
                    var tbody      = "#tbody_sd";
                    var table      = "#table_sys_directories table";
                    var tr         = "#table_sys_directories .dir_tr";
                    var add_action = "add_directory";
                break;

                case "delete_wentry":
                    var tbody      = "#tbody_swe";
                    var table      = "#table_sys_wentries table";
                    var tr         = "#table_sys_wentries .went_tr";
                    var add_action = "add_wentry";
                break;

                case "delete_reg_ignore":
                    var tbody      = "#tbody_sri";
                    var table      = "#table_sys_reg_ignores table";
                    var tr         = "#table_sys_reg_ignores .regi_tr";
                    var add_action = "add_reg_ignore";
                break;

                case "delete_ignore":
                    var tbody      = "#tbody_si";
                    var table      = "#table_sys_ignores table";
                    var tr         = "#table_sys_ignores .ign_tr";
                    var add_action = "add_ignore";
                break;
            }

            if ($(tbody + " tr").length <= 0)
            {
                add_row(tbody, add_action);
            }
            else
            {
                $('textarea').elastic();
                $(table).css('background', 'transparent');

                $(tr).removeClass('odd even');
                $(tr +":odd td").addClass('odd');
                $(tr +":even td").addClass('even');
            }
        }
    }
}
