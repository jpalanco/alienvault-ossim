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
        
function show_agent(id)
{
    if ($("#"+id).hasClass("hide"))
    {
        $("#"+id).show();
        $("#"+id).removeClass("hide").addClass("show");
    }
    else
    {
        $("#"+id).hide();
        $("#"+id).removeClass("show").addClass("hide");
    }
}


function get_idm_data(that)
{
    //AJAX data
    var idm_data = {
            "sensor_id"  : $('#sensors').val(),
            "agent_id"   : $(that).find('td:nth-child(2)').text(),
            "agent_name" : $(that).find('td:nth-child(3)').text(),
            "agent_ip"   : $(that).find('td:nth-child(4)').text()
    };

    var dt_table = $('#agent_table').dataTable();

    $.ajax({
        type: "POST",
        url: "data/agents/ajax/agent_idm_info.php",
        data: idm_data,
        dataType: "json",
        beforeSend: function(xhr)
        {
            $('.td_c_ip', that).html("<img src='/ossim/pixmaps/loading.gif' style='width: 12px; height: 12px;'/>");
            $('.td_c_ud', that).html("<img src='/ossim/pixmaps/loading.gif' style='width: 12px; height: 12px;'/>");
        },
        error: function(data)
        {
            $('.td_c_ip', that).html("-");
            $('.td_c_ud', that).html("-");
        },
        success: function(data){

            var current_ip = '-';
            var current_user_domain = '-';

            if (typeof(data) != 'undefined' && data != null && data.status == 'success')
            {
                current_ip = data.data.ip;
                current_user_domain = data.data.userdomain;
            }

            //Updating the html
            $('.td_c_ip', that).html(current_ip);
            $('.td_c_ud', that).html(current_user_domain);

            //Updating the datatable value but do not redraw the table

            dt_table.fnUpdate(current_ip, that, 4, false, false);
            dt_table.fnUpdate(current_user_domain, that, 5, false, false);

        }
    });
}


function get_agent_info(that)
{
    var sensor_id  = $('#sensors').val();
    var agent_id   = $(that).parent().find('td:nth-child(2)').text();
    var agent_ip   = '';
    
    if ($('.th_ci').length > 0)
    {
        ip       = $(that).parent().find('td:nth-child(5) div').text();
        agent_ip = (ip.match(/-/)) ? '' : ip;
    }
    
    //If current IP is empty, we use the agent IP
    if (agent_ip == '')
    {
        agent_ip = $(that).parent().find('td:nth-child(4)').text();
    }

    $(that).tipTip({
       defaultPosition: "top",
       maxWidth: "auto",
       edgeOffset: 3,
       content: function(e){
          
            var loading_content = "<table class='t_agent_mi'>" +
                            "<tr>" +
                                "<td class='td_loading'>" +
                                    "<img src='/ossim/pixmaps/loading.gif' style='width: 12px; height: 12px;'/>" +
                                "</td>" +
                                "<td style='padding-left:5px;'>"+ossec_msg['load_agent']+"</td>" +
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
                  data: 'sensor_id='+sensor_id+'&agent_id='+agent_id+'&agent_ip='+agent_ip,
                  url: 'data/agents/ajax/agent_info.php',
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


function add_agent()
{
    var form_id   = $('form[method="post"]').attr("id");
    var sensor_id = $('#sensors').val();
        
    show_loading_box('tabs', ossec_msg['add_agent'], '');
    
    var token = Token.get_token('f_agents');
                
    $.ajax({
        type: "POST",
        url: "data/agents/ajax/agent_actions.php",
        data: $('#'+form_id).serialize()+"&sensor_id="+sensor_id+"&action=add_agent&token="+token,
        dataType: "json",
        beforeSend: function(xhr){
            
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

                $("#c_info").html(notify_error(error_msg));
                $("#c_info").fadeIn(4000);

                window.scrollTo(0,0);
            }
            else
            {
                var status = data.data.split("###");

                var new_row = eval(status[2]);

                $('#agent_table').dataTable().fnAddData(new_row);

                if (data.status == 'warning')
                {
                    $("#c_info").html(notify_warning(status[0]));
                }
                else
                {
                    $("#c_info").html(notify_success(status[0]));
                }

                $("#c_info").fadeIn(4000);

                window.scrollTo(0,0);

                timer = setTimeout('$("#c_info").fadeOut(4000);', 5000);
            }
        }
    });
}   


function get_action(id)
{
    var action = null;

    if ($("#cont_add_agent").hasClass("show"))
    {
        $("#cont_add_agent").hide();
        $("#cont_add_agent").removeClass("show").addClass("hide");
    }
        
    if (id.match("_key##") != null)
    {
        send_action(id, 'extract_key');
    }
    else if (id.match("_del##") != null)
    {
        send_action(id, 'delete_agent');
    }
    else if (id.match("_check##") != null)
    {
        send_action(id, 'check_agent'); 
    }
    else if (id.match("_file##") != null)
    {
        send_action(id, 'modified_files');
    }
    else if (id.match("_reg##") != null)
    {
        send_action(id, 'modified_reg_files');
    }
    else if (id.match("_rchk##") != null)
    {
        send_action(id, 'rootcheck');
    }
    else if (id.match("_restart##") != null)
    {
        send_action(id, 'restart_agent');
    }
    else if (id.match("_w_installer_##") != null)
    {
        download_agent(id, 'windows');
    }
    else if (id.match("_w_deployment_##") != null)
    {
        show_deployment_form(id, 'windows');
    }
    else if (id.match("_u_installer_##") != null)
    {
        download_agent(id, 'unix');
    }
}


function send_action(id, action)
{   
    var sensor_id = $('#sensors').val();    
    var id        = id.split("##");
     
    show_loading_box('tabs', ossec_msg['p_action'], '');
    
    var token = Token.get_token('f_agents');
        
    $.ajax({
        type: "POST",
        url: "data/agents/ajax/agent_actions.php",
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
            
            var nt = notify_error(ossec_msg['unknown_error']);
            
            $("#c_info").html(notify_success(status[0]));
            $("#c_info").fadeIn(4000);
                    
            window.scrollTo(0,0);
        },
        success: function(data){

            hide_loading_box();

            var cnd_1  = (typeof(data) == 'undefined' || data == null);
            var cnd_2  = (typeof(data) != 'undefined' && data != null && data.status != 'success');
                
            if (cnd_1 || cnd_2)
            {
                var error_msg = (cnd_1 == true) ? ossec_msg['unknown_error'] : data.data;
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

                    case "check_agent":
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
    var id        = id.split('##');
    var agent_id  = id[1];
            
    var token     = Token.get_token('f_ossec_agent');
    
    var html_form = '<div id="c_ossec_agent" style="display:none;">' +
                        '<form name="f_ossec_agent" id="f_ossec_agent" method="POST" action="data/agents/download_agent.php" target="iframeDownload">' +
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


function show_deployment_form(id, os_type)
{        
    $('#c_info').html('');
    $("#c_info").stop(true, true);
    
    clearTimeout(timer);
            
    var sensor_id = $('#sensors').val();
    var id        = id.split('##');
    var agent_ip  = id[1];
    
    
    var parameters = "sensor_id="+sensor_id+"&os_type="+os_type+"&agent_ip="+agent_ip;
    var url        ='data/agents/a_deployment_form.php?'+parameters;
       
    GB_show(ossec_msg['a_deployment_w'], url, 500, "95%");
}


function deployment_agent()
{   
    var form_id = $('form[method="post"]').attr("id");
                
    $.ajax({
        type: "POST",
        url: "ajax/a_deployment.php",
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
                    error_msg = "<div style='padding-left: 10px;'>"+error_msg+"</div>";
                    
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
                var sensor_ip = $('#sensor_ip').val();
                var work_id   = data.data;
                
                check_deployment_status(sensor_ip, work_id);
            }
            
            window.scrollTo(0,0);
        }
    });
}


function check_deployment_status(sensor_ip, work_id)
{       
     $.ajax({
        type: "POST",
        url: "ajax/a_deployment_actions.php",
        data: "sensor_ip="+sensor_ip+"&work_id="+work_id+"&order=status",
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
            var cnd_2  = (typeof(data) != 'undefined' && data != null && data.status != 'success');
                
            if (cnd_1 || cnd_2)
            {
                hide_loading_box();
                
                if (cnd_1 == true)
                {
                    var error_msg = "<div style='padding-left: 10px;'>"+ossec_msg['unknown_error']+"</div>";
                }
                else
                {
                    if (typeof(data.data.txt) != 'undefined')
                    {
                        var error_msg = "<div style='padding-left: 10px;'>"+data.data.txt+"</div>";
                    }
                    else
                    {
                        var error_msg = "<div style='padding-left: 10px;'>"+data.data+"</div>";
                    }
                }

                if (data.status == 'error')
                {
                    $("#c_info").html(notify_error(error_msg));
                    
                    if (typeof(data.data.help) != 'undefined')
                    {
                        $("#c_help").html(notify_info(data.data.help));
                    }
                    
                }
                else
                {
                    $("#c_info").html(notify_warning(error_msg));
                }
            }
            else
            {
                var code = String(data.data.code);
                
                switch (code)
                {
                    case "-1":
                        hide_loading_box();

                        $("#c_info").html(notify_error(data.data.txt));

                        purge_deployments(sensor_ip, false);

                        if (typeof(data.data.help) != 'undefined')
                        {
                            $("#c_help").html(notify_info(data.data.help));
                        }

                        Token.add_to_forms();
                    break;

                    case "0":
                        hide_loading_box();
                        
                        $("#c_info").html(notify_success(data.data.txt));
                        
                        purge_deployments(sensor_ip, false);
                    break;

                    case "1":
                    case "2":
                    case "3":
                    case "4":
                    case "5":
                        $('.r_lp').html(data.data.txt);
                        timer = setTimeout("check_deployment_status('"+sensor_ip+"', '"+work_id+"');", 2000); 
                    break;
                    
                    default:
                        hide_loading_box();
                        
                        $("#c_info").html(notify_success(data.data.txt));
                        
                        purge_deployments(sensor_ip, false);
                }
            }

            Token.add_to_forms();
            
            window.scrollTo(0,0);
        }
    });
}


function purge_deployments(sensor_ip, show_notification)
{
     $.ajax({
        type: "POST",
        url: "ajax/a_deployment_actions.php",
        data: "sensor_ip="+sensor_ip+"&order=purge",
        dataType: "json",
        beforeSend: function(xhr){
            clearTimeout(timer);
        },
        error: function(data){
            
            hide_loading_box(); 
                    
            $("#c_info").html(notify_error(ossec_msg['unknown_error']));
                    
            $("#c_info").fadeIn(4000);
                    
            window.scrollTo(0,0);
        },
        success: function(data){

            if (show_notification == true)
            {
                var cnd_1  = (typeof(data) == 'undefined' || data == null);
                var cnd_2  = (typeof(data) != 'undefined' && data != null && data.status != 'success');
                
                hide_loading_box();

                if (cnd_1 || cnd_2)
                {
                    var error_msg = (cnd_1 == true) ? ossec_msg['unknown_error'] : data.data;
                        error_msg = "<div style='padding-left: 10px;'>"+error_msg+"</div>";

                    if (data.status == 'error'){
                        $("#c_info").html(notify_error(error_msg));
                    }
                    else
                    {
                        $("#c_info").html(notify_warning(error_msg));
                    }
                }
                else
                {
                    $("#c_info").html(notify_success(data.data));
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
        url: "data/agents/ajax/load_tabs.php",
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

                        $('#show_agent').off('click');
                        $('#show_agent').click(function(){
                            show_agent("cont_add_agent")
                        });
                        
                        $('#send_1').off('click');
                        $('#send_1').click(function(){ 
                            add_agent()
                        });

                        var config = {
                            validation_type: 'complete', // single|complete
                            errors:{
                                display_errors: 'all', //  all | summary | field-errors
                                display_in: 'info'
                            },
                            form : {
                                id  : 'form_agent',
                                url : 'data/agents/ajax/agent_actions.php'
                            },
                            actions: {
                                on_submit:{
                                    id: 'send_1',
                                    success:  $('#send_1').val(),
                                    checking: av_messages['submit_text']
                                }
                            }
                        };

                        ajax_validator = new Ajax_validator(config);
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

                        if (editor == null)
                        {
                            editor = new CodeMirror(CodeMirror.replace("code"), {
                                parserfile: "parsexml.js",
                                stylesheet: "../style/xmlcolors.css",
                                path: "../js/codemirror/",
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
        url: "data/agents/ajax/save_cnf.php",
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
        url: "data/agents/ajax/syscheck_actions.php",
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
