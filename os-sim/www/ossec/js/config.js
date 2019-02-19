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

function load_config_tab(tab)
{
    ajax_requests.abort();

    var sensor_id = $('#sensors').val();

    xhr = $.ajax({
        type: "POST",
        url: "/ossim/ossec/providers/ossec_config/load_tabs.php",
        data: "tab="+tab+"&sensor_id="+sensor_id,
        beforeSend: function(xhr) {

            $('#c_info').html('');
            $("#c_info").stop(true, true);

            clearTimeout(timer);

            parent.$('#main').height(700);
            $(tab).hide();
            hide_select();

            show_loading_box('tabs', ossec_msg['loading'], '');
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

                        $(tab).show();

                        $(".multiselect").multiselect({
                            searchDelay: 500,
                            nodeComparator: function (node1,node2){ return 1 },
                            dividerLocation: 0.5
                        });
                    }
                    else if (tab == "#tab2")
                    {
                        $(tab).html(status[1]);

                        $('textarea').elastic();

                        $('.t_syscheck tbody tr td').removeClass('odd even');

                        $('#table_sys_directories .dir_tr:odd td').addClass('odd');
                        $('#table_sys_directories .dir_tr:even td').addClass('even');

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

                        $('#send_7').off('click');
                        $('#send_7').click(function()  { save_config_tab(); });

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

                    $('.c_filter_and_actions').css({'width':'98%', 'margin': 'auto'});
                break;
            }

            $(tab).show();

            show_select();

            $('#sensors').removeAttr('disabled');

            $('#sensors').off('change');
            $('#sensors').change(function(){
                load_config_tab(tab);
            });
        }
    });

    ajax_requests.add_request(xhr);
}


function save_config_tab()
{
    var tab        = $(".active a").attr("href");
    var sensor_id  = $('#sensors').val();

    show_loading_box('tabs', ossec_msg['loading'], '');

    var token      = Token.get_token('ossec_cnf');

    $('#c_info').html('');
    $("#c_info").stop(true, true);

    clearTimeout(timer);

    if (tab == '')
    {
        $('#c_info').html(notify_error(ossec_msg['i_action']));
        return;
    }

    var data = "tab="+tab+"&sensor_id="+sensor_id+"&token="+token;

    switch(tab){
        case "#tab1":
            data += "&"+ $('#cnf_form_rules').serialize();
        break;

        case "#tab2":
            data += "&"+ $('#form_syscheck').serialize();
        break;

        case "#tab3":
            data += "&"+"data="+Base64.encode(htmlentities(editor.getCode(), 'HTML_ENTITIES'));
        break;
    }

    $.ajax({
        type: "POST",
        url: "/ossim/ossec/controllers/ossec_config/save_tabs.php",
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
        url: "/ossim/ossec/controllers/ossec_config/actions.php",
        data: "action="+action,
        success: function(msg){

            var status = msg.split("###");

            if (status[0] != "2")
            {
                if (id.match("tbody_") != null){
                    $(id).html(status[1]);
                }
                else{
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
                $(tr+":odd td").addClass('odd');
                $(tr+":even td").addClass('even');
            }
        }
    }
}