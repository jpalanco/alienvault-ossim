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

function load_interface(tab)
{
    var tab       = (tab == '') ? $(".active a").attr("href") : tab;
    var sensor_id = $('#sensors').val();

    $.ajax({
        type: "POST",
        url: "/ossim/ossec/views/ossec_rules/load_tabs.php",
        data: "tab="+tab+"&sensor_id="+sensor_id,
        beforeSend: function(xhr){

            $("#c_info").html('');
            $("#c_info").stop(true, true);

            clearTimeout(timer);

            $(tab).hide();

            show_loading_box('container_center', ossec_msg['loading'], '');
        },
        success: function(msg){

            hide_loading_box();

            var status = msg.split("###");

            switch(status[0])
            {
                case "1":

                    $('#oss_clcontainer').html(status[1]);

                    $('.c_filter_and_actions').css({'width':'98%', 'margin': '0px auto -10px auto'});

                    $('#rules').off('click');
                    $('#rules').change(function(){
                        load_tab1()
                    });

                    if (tab == "#tab1")
                    {
                        load_tab1();
                    }
                    else if (tab == "#tab2")
                    {
                        load_tab2('');
                    }
                break;

                case "2":

                    $('.c_filter_and_actions').css({'width':'98%', 'margin': '0px auto -10px auto'});

                    var content = "<div id='msg_init_error'><div style='margin-left: 70px; text-align:center;'>"+notify_error(status[1])+"</div></div>";

                    $(tab).html(content);

                    $('.c_filter_and_actions').css({'width':'98%', 'margin': '0px auto -10px auto'});

                    $('#sensors').removeAttr('disabled');

                break;
            }

            $(tab).show();

            show_select();

            $('#sensors').off('change');
            $('#sensors').change(function(){
                load_interface('');
            });
        }
    });
}


function fill_rules(select, file, cache)
{
    var sensor_id = $('#sensors').val();

    $.ajax({
        type: "POST",
        url: "/ossim/ossec/providers/ossec_rules/get_rule_files.php",
        data: "file="+file+"&sensor_id="+sensor_id+"&cache="+cache,
        success: function(html){
            $("#"+select).html(html);
        }
    });
}


function load_tree(encode_tree, key)
{
    var tree = "[" + Base64.decode(encode_tree) + "]";
    var lk = null;

    if (nodetree != null)
    {
        nodetree.removeChildren();
        $(layer).remove();
    }

    layer = '#srctree'+i;

    $('#tree_container_bt').append('<div id="srctree'+i+'" style="width:100%;"></div>');

    $(layer).html("<div class='reload'><img src='/ossim/pixmaps/theme/loading2.gif' border='0' align='absmiddle'/>"+ossec_msg['reloading']+"</div>");

    $(layer).dynatree({
        onActivate: function(dtnode) {

            if (dtnode.data.key != "load_error")
            {
                draw_tree(dtnode);
            }
        },
        children: eval(tree)
    });

    nodetree = $(layer).dynatree("getRoot");

    activate_node(key);

    i = i + 1;
}


function activate_node(key)
{
    if ($(layer).dynatree("getTree").getNodeByKey(key) != null)
    {
        lk = key;
    }
    else
    {
        var parent = key.substring(0, key.lastIndexOf('_'));

        if ($(layer).dynatree("getTree").getNodeByKey(parent) != null)
        {
            lk = parent;
        }
    }

    if (lk != null)
    {
        $(layer).dynatree("getTree").getNodeByKey(lk).focus();
        $(layer).dynatree("getTree").getNodeByKey(lk).expand(true);
        $(layer).dynatree("getTree").getNodeByKey(lk).activate();
    }
}


function show_tree(draw_edit, lk)
{
    $("#c_info").html('');
    $("#c_info").stop(true, true);
    clearTimeout(timer);

    var tab       = $("ul.oss_tabs li:first");
    var rule_file = $('#rules').val();
    var sensor_id = $('#sensors').val();

    if (rule_file == '')
    {
        $('#tab1').html("<div id='msg_init'></div>");
        $('#msg_init').html(notify_error(ossec_msg['rule_file_not_found']));

        $('#container_code').remove();

        show_tab_content(tab);

        return false;
    }

    $.ajax({
        type: "POST",
        url: "/ossim/ossec/providers/ossec_rules/get_tree.php",
        dataType: 'json',
        data: "file="+ rule_file+"&sensor_id="+sensor_id,
        beforeSend: function(xhr){

            $('#sensors').addClass('disabled');
            $('#sensors').attr('disabled', 'disabled');

            if (draw_edit == true)
            {
                show_loading_box('c_tabs', ossec_msg['loading'], '');
            }
        },
        error: function(data){

            hide_loading_box();

            var level_key = "load_error";

            tree = "{title:'<span>"+rule_file+"</span>', icon:'/ossim/pixmaps/theme/any.png', addClass:'size12', isFolder:'true', key:'1', children:" +
                        "[{title: '<span>"+ossec_msg['no_data_tree']+"</span>', key:'"+level_key+"'}]" +
                   "}";

            tree = Base64.encode(tree);

            load_tree(tree, level_key);

            var nt = notify_error(ossec_msg['unknown_error']);

            $('#tab1').html("<div id='msg_init'>"+nt+"</div>");

            $('#container_code').remove();

            $('#sensors').removeAttr('disabled');

            show_tab_content(tab);
        },
        success: function(data){

            hide_loading_box();

            var cnd_1   = (typeof(data) == 'undefined' || data == null);
            var cnd_2   = (typeof(data) != 'undefined' && data != null && data.status == 'error');

            if (cnd_1 || cnd_2)
            {
                var level_key = "load_error";

                tree = "{title:'<span>"+rule_file+"</span>', icon:'/ossim/theme/any.png', addClass:'size12', isFolder:'true', key:'1', children:" +
                            "[{title: '<span>"+ossec_msg['no_data_tree']+"</span>', key:'"+level_key+"'}]" +
                       "}";

                tree   = Base64.encode(tree);

                var error_msg = (cnd_1 == true) ? ossec_msg['unknown_error'] : data.data
                var nt        = notify_error(error_msg);

                $('#tab1').html("<div id='msg_init'>"+nt+"</div>");
            }
            else
            {
                var status = (data.data).split("###");
                var tree   = status[1];

                level_key  = (lk == '') ? "1": lk;

                if (draw_edit == true)
                {
                    var config_nt = { content: status[0],
                        options: {
                            type:'nf_info',
                            cancel_button: false
                        },
                        style: 'width: 80%; margin: auto; padding: 10px; text-align: center; font-size: 14px; font-weight: bold;'
                    };

                    var ntf = new Notification('nt_1', config_nt);

                    $('#tab1').html("<div id='msg_init'>"+ntf.show()+"</div>");
                }
            }

            load_tree(tree, level_key);

            $('#sensors').removeAttr('disabled');

            show_tab_content(tab);
        }
    });
}


function load_tab1()
{
    $('.tab_content').hide();

    show_tree(true, '');
}


function load_tab2(file)
{
    var sensor_id = $('#sensors').val();
    var filename  = (file == '') ? $('#rules').val() : file;
    var tab       = $("#litem_tab2");

    $.ajax({
        type: "POST",
        data: "file="+ filename+"&sensor_id="+sensor_id,
        dataType: 'json',
        url:  "/ossim/ossec/providers/ossec_rules/get_xml_file.php",
        beforeSend: function(xhr){

            $("#c_info").html('');
            $("#c_info").stop(true, true);

            clearTimeout(timer);

            $('#sensors').addClass('disabled');
            $('#sensors').attr('disabled', 'disabled');

            show_loading_box('c_tabs', ossec_msg['loading'], '');

            $('.tab_content').hide();
        },
        error: function(data){

            hide_loading_box();

            var nt = notify_error(ossec_msg['unknown_error']);

            $('#tab2').html("<div id='msg_init'>"+nt+"</div>");

            editor = null;

            $('#sensors').removeAttr('disabled');

            show_tab_content(tab);
        },
        success: function(data){

            hide_loading_box();

            var cnd_1 = (typeof(data) == 'undefined' || data == null);
            var cnd_2 = (typeof(data) != 'undefined' && data != null && data.status == 'error');

            if (cnd_1 || cnd_2)
            {
                var error_msg = (cnd_1 == true) ? ossec_msg['unknown_error'] : data.data
                var nt        = notify_error(error_msg);

                $('#tab2').html("<div id='msg_init'>"+nt+"</div>");

                editor = null;
            }
            else
            {
                /*Code Mirror*/

                if (filename != '' && (filename == 'rules_config.xml' || filename == 'local_rules.xml' || filename.match(/^av_/)))
                {
                    if ($('#container_code').length < 1)
                    {
                        var content = "<div id='container_code'>" +
                                        "<textarea id='code'></textarea>" +
                                            "<div class='button_box'>" +
                                                "<div><input type='button' class='save' id='save_xml' value='"+labels['save']+"'/></div>" +
                                            "</div>"
                                        "</div>";

                        $('#tab2').html(content);
                     }

                     if ($('.button_box').length < 1)
                     {
                         var content = "<div class='button_box'>" +
                                            "<div><input type='button' class='save' id='save_xml' value='"+labels['save']+"'/></div>" +
                                       "</div>";

                        $('#container_code').append(content);
                     }

                     $('#save_xml').off('click');
                     $('#save_xml').click(function(){
                         save(editor);
                     });
                }
                else
                {
                     if ($('#container_code').length < 1)
                     {
                        var content = "<div id='container_code'>" +
                                        "<textarea id='code'></textarea>" +
                                      "</div>";

                        $('#tab2').html(content);
                     }
                     else
                     {
                         $('#save_xml').off('click');
                         $('.button_box').remove();
                     }
                }

                if (editor == null)
                {
                    editor = new CodeMirror(CodeMirror.replace("code"), {
                        parserfile: "parsexml.js",
                        stylesheet: "/ossim/style/xmlcolors.css",
                        path: "/ossim/js/codemirror/",
                        continuousScanning: 500,
                        content: data.data,
                        lineNumbers: true
                    });
                }
                else
                {
                    editor.setCode(data.data);
                }
            }

            $('#sensors').removeAttr('disabled');

            show_tab_content(tab);
        }
    });
}


function save(editor)
{
    var sensor_id = $('#sensors').val();
    var data      = Base64.encode(htmlentities(editor.getCode(), 'HTML_ENTITIES'));

    show_loading_box('c_tabs', ossec_msg['saving'], '');

    var token = Token.get_token('f_rules');

    $.ajax({
        type: "POST",
        url:  "/ossim/ossec/controllers/ossec_rules/save.php",
        dataType : 'json',
        data: "data="+data+"&sensor_id="+sensor_id+"&token="+token,
        beforeSend: function(xhr){

            $("#c_info").html('');
            $("#c_info").stop(true, true);

            clearTimeout(timer);

            $("#save_xml").addClass('av_b_processing');
            $("#save_xml").val(labels['saving']);
        },
        error: function(data){

            hide_loading_box();

            var nt = notify_error(ossec_msg['unknown_error']);

            $('#c_info').html(nt);
            $("#c_info").fadeIn(2000);

            $("#save_xml").removeClass('av_b_processing');
            $("#save_xml").val(labels['save']);
        },
        success: function(data){

            hide_loading_box();

            $("#save_xml").removeClass('av_b_processing');
            $("#save_xml").val(labels['save']);

            var cnd_1   = (typeof(data) == 'undefined' || data == null);
            var cnd_2   = (typeof(data) != 'undefined' && data != null && data.status == 'error');

            if (cnd_1 || cnd_2)
            {
                var error_msg = (cnd_1 == true) ? ossec_msg['unknown_error'] : data.data
                var nt = notify_error(error_msg);

                $("#c_info").html(nt);
                $("#c_info").fadeIn(2000);
            }
            else
            {
                var c_data  = (data.data).split("###");
                var node    = $(layer).dynatree("getTree").getActiveNode();

                if (node.data.key != 'load_error')
                {
                    var key = (node != null) ? node.data.key : 1;
                    load_tree(c_data[1], key);

                    var nt = notify_success(c_data[0]);

                    $('#c_info').html(nt);
                    $("#c_info").fadeIn(2000);

                    timer = setTimeout('$("#c_info").fadeOut(4000);', 5000);
                }
                else // Go to main page
                {
                    var cont = 3;

                    var nt = "<span style='margin-left: 5px'>" + ossec_msg['reloading_in'] + " <span id='countdown'>"+cont+"</span> "+labels['seconds']+" ...</span>";


                    $('#c_info').html(notify_success(nt));
                    $("#c_info").fadeIn(2000);

                    setTimeout(function(){ countdown(cont)}, 1000);
                }
            }
        }
    });
}


function countdown(seconds)
{
    var cont = seconds - 1;

    if (cont != 0)
    {
        $("#countdown").html(cont);

        setTimeout(function(){ countdown(cont)}, 1000);
    }
    else
    {
        var sensor_id = $('#sensors').val();

        document.location.href = '/ossim/ossec/views/ossec_rules/index.php?sensor_id='+sensor_id;
    }
}


function add_at(id, type, path)
{
    var new_id = get_new_id(id);

    switch (type)
    {
        case 'ats':
            var title     = labels['attribute'];
            var t_actions = "actions_bt_at";
            var actions = "<td class='"+ t_actions +"' style='width:75px;'>"
                            + "<a onclick=\"add_at('"+new_id+"', '"+type+"', '"+path+"');\"><img src='"+path+"/add.png' alt='"+labels['add']+"' title='"+ labels['add'] +" "+ title + "'/></a>\n"
                            + "<a onclick=\"delete_at('"+new_id+"', '"+type+"', '"+path+"');\"><img src='"+path+"/delete.gif' alt='"+labels['delete']+"' title='"+ labels['delete'] +" "+ title + "'/></a>\n"
                            + "<a onclick=\"clone_at('"+new_id+"', '"+type+"', '"+path+"');\"><img src='"+path+"/clone.png' alt='"+labels['clone']+"' title='"+ labels['clone'] +" "+ title + "'/></a>\n"
                        + "</td>";
        break;

        case 'at':
            var title     = labels['attribute'];
            var t_actions = "actions_bt_at";
            var actions = "<td class='"+ t_actions +"' style='width:75px;'>"
                                + "<a onclick=\"delete_at('"+new_id+"', '"+type+"', '"+path+"');\"><img src='"+path+"/delete.gif' alt='"+labels['delete']+"' title='"+ labels['delete'] +" "+ title + "'/></a>"
                            + "</td>";
        break;

        case 'txt_node':
            var title     = labels['txt_node'];
            var t_actions = "actions_bt_tn";
            var actions = "<td class='"+ t_actions +"' style='width:75px;'>"
                                + "<a onclick=\"delete_at('"+new_id+"', '"+type+"', '"+path+"');\"><img src='"+path+"/delete.gif' alt='"+labels['delete']+"'  title='"+ labels['delete'] +" "+ title + "'/></a>"
                            + "</td>";
        break;


        case 'txt_nodes':
            var title     = labels['txt_node'];
            var t_actions = "actions_bt_tn";
            var actions = "<td class='"+ t_actions +"' style='width:95px;'>"
                            + "<a onclick=\"add_at('"+new_id+"', '"+type+"', '"+path+"');\"><img src='"+path+"/add.png' alt='"+labels['add']+"' title='"+ labels['add'] +" "+ title + "'/></a>\n"
                            + "<a onclick=\"delete_at('"+new_id+"', '"+type+"', '"+path+"');\"><img src='"+path+"/delete.gif' alt='"+labels['delete']+"' title='"+ labels['delete'] +" "+ title + "'/></a>\n"
                            + "<a onclick=\"clone_at('"+new_id+"', '"+type+"', '"+path+"');\"><img src='"+path+"/clone.png' alt='"+labels['clone']+"' title='"+ labels['clone'] +" "+ title + "'/></a>\n"
                            + "<a onclick=\"show_at('"+new_id+"');\"><img src='"+path+"/show.png' alt='"+labels['show_at']+"' title='"+ labels['show_at'] + title + "'/></a>\n"
                        + "</td>";
        break;

    }

    var element = "<tr id='"+ new_id +"'>"
        + "<td class='n_name'  id='cont_n_label-"+new_id+"'>"
            + "<input type='text' class='n_input auto_c' name='tn_label-"+new_id+"' id='tn_label-"+new_id+"'/>\n"
            + "<input type='hidden' name='n_label-"+new_id+"' id='n_label-"+new_id+"'/>\n"
        + "</td>"
        + "<td class='n_value' id='cont_n_txt-"+new_id+"'><textarea name='n_txt-"+new_id+"' id='n_txt-"+new_id+"'></textarea></td>"
        +  actions
    + "</tr>";

    $('#'+id).after(element);

    $('textarea').on('focus', function() { $(this).css('color', '#2F85CA');});
    $('textarea').on('blur',  function() { $(this).css('color', '#000000');});
    $('textarea').elastic();
    $("input[type='text']").on('focus', function() { $(this).css('color', '#2F85CA');});
    $("input[type='text']").on('blur',  function() { $(this).css('color', '#000000');});

    set_autocomplete(".auto_c");

    //Tooltips
    $('.actions_bt_at img').tipTip({maxWidth : 'auto', edgeOffset: 14, defaultPosition : 'top'});
    $('.actions_bt_tn img').tipTip({maxWidth : 'auto', edgeOffset: 14, defaultPosition : 'top'});
}


function delete_at(id, type, path)
{
    var id_txt_nodes = "#ats_"+id;
    var id           = "#"+id;
    var parent       = $(id).parent();

    $(id).remove();

    if ($(id_txt_nodes).length >=1)
    {
        $(id_txt_nodes).remove();
    }

    var children = parent.children().length;

    if (children == 2)
    {
        var last_child = $("#"+parent.attr('id')+" tr:last-child").attr("id");
        add_at(last_child, type, path);
    }
}


function clone_at(id)
{
    var new_id   = get_new_id(id);
    var reg      = new RegExp(id, "g");

    var name     = $("#n_label-"+id).val();
    var value    = $("#n_txt-"+id).val();


    var element  = $("#"+id).html();
    element      = element.replace(reg, new_id);

    element  = "<tr id='"+ new_id +"' style='display:none;'>"+element+"</tr>";

    $("#"+id).after(element);

    $("#n_label-"+new_id).val(name);
    $("#n_txt-"+new_id).val(value);
    $("#"+new_id).css('display', '');


    $('textarea').on('focus', function() { $(this).css('color', '#2F85CA');});
    $('textarea').on('blur',  function() { $(this).css('color', '#000000');});
    $('textarea').elastic();
    $("input[type='text']").on('focus', function() { $(this).css('color', '#2F85CA');});
    $("input[type='text']").on('blur',  function() { $(this).css('color', '#000000');});

    set_autocomplete(".auto_c");

    //Tooltips
    $('.actions_bt_at img').tipTip({maxWidth : 'auto', edgeOffset: 14, defaultPosition : 'top'});
}


function show_at(id)
{
    var display = $("#"+id).css('display');

    if (display == 'none')
    {
        $("#"+id).show();
    }
    else
    {
        hide_at(id)
    }
}


function hide_at(id)
{
    $("#"+id).hide();
}


function add_node(id, type, path)
{
    var new_id = uniqid();
    var id     = '#'+id;

    var title = labels['txt_node'];
    var actions = "<td class='actions_bt_tn' style='width:95px;'>"
                    + "<a onclick=\"add_node('"+new_id+"', '"+type+"', '"+path+"');\"><img src='"+path+"/add.png' alt='"+labels['add']+"' title='"+ labels['add'] +" "+ title + "'/></a>\n"
                    + "<a onclick=\"delete_at('"+new_id+"', '"+type+"', '"+path+"');\"><img src='"+path+"/delete.gif' alt='"+labels['delete']+"' title='"+ labels['delete'] +" "+ title + "'/></a>\n"
                    + "<a onclick=\"clone_node('"+new_id+"', '"+type+"', '"+path+"');\"><img src='"+path+"/clone.png' alt='"+labels['clone']+"' title='"+ labels['clone'] +" "+ title + "'/></a>\n"
                    + "<a onclick=\"show_at('ats_"+new_id+"');\"><img src='"+path+"/show.png' alt='"+labels['show_at']+"' title='"+ labels['show_at'] + title + "'/></a>\n"
                + "</td>\n";



    var element =
        "<tr id='"+ new_id +"'>"
            + "<td class='n_name' id='cont_n_label-"+new_id+"'>"
                 + "<input type='text' class='n_input auto_c' name='tn_label-"+new_id+"' id='tn_label-"+new_id+"' value=''/>\n"
                 + "<input type='hidden' name='n_label-"+new_id+"' id='n_label-"+new_id+"' value=''/>\n"
            +"</td>"
            + "<td class='n_value' id='cont_n_txt-"+new_id+"'><textarea name='n_txt-"+new_id+"' id='n_txt-"+new_id+"'></textarea></td>"
            +  actions
        + "</tr>\n"
        + "<tr id='ats_"+ new_id +"' style='display: none;'>\n"
            + "<td colspan='3'>\n"
                + "<div class='cont_ats_txt_node'>\n"
                    + "<table class='er_container'>\n"
                    + "<tbody id='erb_"+ new_id +"'>\n"
                        + "<tr id='subheader_"+ new_id +"'>\n"
                            + "<th class='txt_node_header' colspan='3'>\n"
                                + "<div class='fleft'><span>"+labels['txt_node_at']+"</span></div>\n"
                                + "<div class='fright'><a style='float: right' onclick=\"hide_at('ats_"+ new_id +"');\"><img src='"+ path +"/arrow-up.png' alt='"+labels['arrow']+"' title='"+ labels['hide_at'] + "' align='absmiddle'/></a></div>\n"
                            + "</th>\n"
                        + "</tr>\n"
                        + "<tr id='subheader2_"+ new_id +"'>\n"
                            + "<th class='r_subheader'>"+labels['name']+"</th>\n"
                            + "<th class='r_subheader'>"+labels['value']+"</th>\n"
                            + "<th class='r_subheader actions_at'>"+labels['actions']+"</th>\n"
                        + "</tr>\n"
                        + "<tr id='"+ new_id +"_at1'>\n"
                            + "<td class='n_name' id='cont_n_label-"+ new_id +"_at1'>"
                            +    "<input type='text' class='n_input auto_c' name='tn_label-"+ new_id +"_at1' id='tn_label-"+ new_id +"_at1' value=''/>\n"
                            +    "<input type='hidden' name='n_label-"+ new_id +"_at1' id='n_label-"+ new_id +"_at1' value=''/>"
                            + "</td>\n"
                            + "<td class='n_value' id='cont_n_txt-"+ new_id +"_at1'><textarea name='n_txt-"+ new_id +"_at1' id='n_txt-"+ new_id +"_at1'></textarea></td>\n"
                            + "<td class='actions_bt_at'>\n"
                                + "<a onclick=\"add_at('"+ new_id +"_at1', 'ats', '"+ path +"');\"><img src='"+ path +"/add.png' alt='"+labels['add']+"' title='"+ labels['add'] +" "+ title + "'/></a>\n"
                                + "<a onclick=\"delete_at('"+ new_id +"_at1','ats', '"+ path +"');\"><img src='"+ path +"/delete.gif' alt='"+labels['delete']+"' title='"+ labels['delete'] +" "+ title + "'/></a>\n"
                                + "<a onclick=\"clone_at('"+ new_id +"_at1');\"><img src='"+ path +"/clone.png' alt='"+labels['clone']+"' title='"+ labels['clone'] +" "+ title + "'/></a>\n"
                            + "</td>\n"
                        + "</tr>\n"
                    + "</tbody>\n"
                    + "</table>\n"
                + "</div>\n"
            + "</td>\n"
        + "</tr>";


    $(id).after(element);

    $('textarea').on('focus', function() { $(this).css('color', '#2F85CA');});
    $('textarea').on('blur',  function() { $(this).css('color', '#000000');});
    $('textarea').elastic();
    $("input[type='text']").on('focus', function() { $(this).css('color', '#2F85CA');});
    $("input[type='text']").on('blur',  function() { $(this).css('color', '#000000');});


    //Tooltips
    $('.actions_bt_at img').tipTip({maxWidth : 'auto', edgeOffset: 14, defaultPosition : 'top'});
    $('.actions_bt_tn img').tipTip({maxWidth : 'auto', edgeOffset: 14, defaultPosition : 'top'});

    set_autocomplete(".auto_c");
}


function clone_node(id)
{
    var new_id   = uniqid();
    var reg      = new RegExp(id, "g");

    var element =
        "<tr id='"+ new_id +"' style='display:none;'>"+$("#"+id).html()+"</tr>" +
        "<tr id='ats_"+ new_id +"' style='display:none;'>"+$("#ats_"+id).html()+"</tr>";

    element = element.replace(reg, new_id);

    $("#ats_"+id).after(element);

    var name  = $("#n_label-"+id).val();
    var value = $("#n_txt-"+id).val();

    $("#n_label-"+new_id).val(name);
    $("#n_txt-"+new_id).val(value);

    var inputs          = $("#ats_"+ id + " input[type='hidden']");
    var textareas       = $("#ats_"+ id + " textarea");
    var inputs_clone    = $("#ats_"+ new_id + " input");
    var textareas_clone = $("#ats_"+ new_id + " textarea");

    for (var i=0; i<inputs.length; i++)
    {
        var name  = $("#"+inputs[i].id).val();
        var value = $("#"+textareas[i].id).val();

        $("#"+inputs_clone[i].id).val(name);
        $("#"+textareas_clone[i].id).val(value);
    }


    $("#"+new_id).show();

    $('textarea').on('focus', function() { $(this).css('color', '#2F85CA');});
    $('textarea').on('blur',  function() { $(this).css('color', '#000000');});
    $('textarea').elastic();

    $("input[type='text']").on('focus', function() { $(this).css('color', '#2F85CA');});
    $("input[type='text']").on('blur',  function() { $(this).css('color', '#000000');});

    set_autocomplete(".auto_c");

    //Tooltips
    $('.actions_bt_at img').tipTip({maxWidth : 'auto', edgeOffset: 14, defaultPosition : 'top'});
    $('.actions_bt_node img').tipTip({maxWidth : 'auto', edgeOffset: 14, defaultPosition : 'top'});
    $('.actions_bt_tn img').tipTip({maxWidth : 'auto', edgeOffset: 14, defaultPosition : 'top'});
}


function delete_child(id, path)
{
    var parent   = $('#'+id).parent();
    var children = parent.children().length;

    if (children > 4)
    {
        $('#'+id).remove();
        $('#node_xml-'+id).remove();

        children = parent.children().length;

        if (children <= 4)
        {
            $(".delete_c").addClass("unbind");
        }
    }
    else
    {
        $('#c_info').html(notify_error(ossec_msg['no_rules']));
        $("#c_info").fadeIn(2000);
    }
}


function clone_child(id)
{
    var key_parent = '';
    var kp         = $("#"+id).attr("class").split("-###");

    key_parent = (kp[1] == '') ? id : kp[1];

    var aux_id = id.replace("_clone", "");
    var new_id = get_new_id(aux_id)+"_clone";
    var reg    = new RegExp(id, "g");

    var element  = $("#"+id).html();
        element  = element.replace(reg, new_id);
        element  = "<tr id='"+ new_id +"' style='display:none;' class='__lk-###"+key_parent+"'>"+element+"</tr>";

    $("#"+id).after(element);

    var id     = "#"+id;
    var parent = $(id).parent();

    var children = parent.children().length;

    if (children > 4)
    {
        $('.delete_c').removeClass("unbind");
    }

    set_autocomplete(".auto_c");

    $("#"+new_id+" .clone_c").prop("onclick", null);
    $("#"+new_id+" .clone_c").addClass("unbind");

    $("#"+new_id+" .show_c").prop("onclick", null);
    $("#"+new_id+" .show_c").addClass("unbind");

    $("#"+new_id).show();

    //Tooltips
    $("#"+new_id+' img').tipTip({content: $(this).attr('alt'), maxWidth: 'auto', edgeOffset: 14, defaultPosition : 'top'});
}


function modify(lk_value)
{
    var sensor_id = $('#sensors').val();

    show_loading_box('c_tabs', ossec_msg['loading'], '');

    var token = Token.get_token('f_rules');

    $.ajax({
        type: "POST",
        url:  "/ossim/ossec/controllers/ossec_rules/modify_rule.php",
        data: $('form').serialize() + "&sensor_id=" + sensor_id + "&token="+token,
        dataType: 'json',
        beforeSend: function(xhr){

            $("#c_info").html('');
            $("#c_info").stop(true, true);

            clearTimeout(timer);

            $("#send").addClass('av_b_processing');
            $("#send").val(labels['saving']);
        },
        error: function(data){

            hide_loading_box();

            $("#send").removeClass('av_b_processing');
            $("#send").val(labels['save']);

            var nt = notify_error(ossec_msg['unknown_error']);

            $('#c_info').html(nt);
            $('#c_info').fadeIn(2000);

            window.parent.scrollTo(0, 250);
        },
        success: function(data){

            hide_loading_box();

            $("#send").removeClass('av_b_processing');
            $("#send").val(labels['save']);

            var cnd_1 = (typeof(data) == 'undefined' || data == null);
            var cnd_2 = (typeof(data) != 'undefined' && data != null && data.status == 'error');

            if (cnd_1 || cnd_2)
            {
                var error_msg = (cnd_1 == true) ? ossec_msg['unknown_error'] : data.data
                var nt = notify_error(error_msg);

                $('#c_info').fadeIn(2000);
                $('#c_info').html(nt);

                window.parent.scrollTo(0, 250);
            }
            else
            {
                var c_data  = (data.data).split("###");
                load_tree(c_data[1], lk_value);

                var nt = notify_success(c_data[0]);

                $('#c_info').fadeIn(2000);
                $('#c_info').html(nt);

                window.parent.scrollTo(0, 250);

                timer = setTimeout('$("#c_info").fadeOut(4000);', 5000);
            }
        }
    });
}


function modify_node(lk_value)
{
    var sensor_id = $('#sensors').val();
    var nodes     = $("tr[class|=__lk]");
    var id        = ''
    var parent    = '';
    var key       = '';


    show_loading_box('c_tabs', ossec_msg['loading'], '');

    var token = Token.get_token('f_rules');
    var data  = $('form').serialize() + "&sensor_id=" + sensor_id + "&token="+token;

    for (var i=0; i<nodes.length; i++)
    {
        data += "&key"+i+"=";

        id = nodes[i].id;
        if (id.match("_clone") == null)
        {
            data += id;
        }
        else
        {
            parent = $("#"+nodes[i].id).attr("class");
            key = parent.split("-###")
            data += "clone###"+key[1];
        }
    }

    $.ajax({
        type: "POST",
        url:  "/ossim/ossec/controllers/ossec_rules/modify_rule.php",
        data: data,
        dataType: 'json',
        beforeSend: function(xhr){

            $("#c_info").html('');
            $("#c_info").stop(true, true);

            clearTimeout(timer);
            $("#send").addClass('av_b_processing');
            $("#send").val(labels['saving']);
        },
        error: function(data){

            hide_loading_box();

            $("#send").removeClass('av_b_processing');
            $("#send").val(labels['save']);

            var nt = notify_error(ossec_msg['unknown_error']);

            $('#c_info').fadeIn(2000);
            $('#c_info').html(nt);

            window.parent.scrollTo(0, 250);
        },
        success: function(data){

            hide_loading_box();

            $("#send").removeClass('av_b_processing');
            $("#send").val(labels['save']);

            var cnd_1 = (typeof(data) == 'undefined' || data == null);
            var cnd_2 = (typeof(data) != 'undefined' && data != null && data.status == 'error');

            if (cnd_1 || cnd_2)
            {
                var error_msg = (cnd_1 == true) ? ossec_msg['unknown_error'] : data.data
                var nt        = notify_error(error_msg);

                $('#c_info').fadeIn(2000);
                $('#c_info').html(nt);

                window.parent.scrollTo(0, 250);
            }
            else
            {
                var c_data  = (data.data).split("###");
                load_tree(c_data[1], lk_value);

                var nt = notify_success(c_data[0]);

                $('#c_info').fadeIn(2000);
                $('#c_info').html(nt);

                window.parent.scrollTo(0, 250);

                timer = setTimeout('$("#c_info").fadeOut(4000);', 5000);

                //Reload right tab if changes have been saved
                if ($(".edit_c").hasClass('unbind'))
                {
                    load_tab1();
                }
            }
        }
    });
}


function draw_tree(dtnode)
{
   var key = dtnode.data.key;
   var tab = $("ul.oss_tabs li:first");

   if (key != 1 && key != 'load_error')
   {
        var data = "node="+ dtnode.data.title +"&lk_value="+ key;

        $.ajax({
            type: "POST",
            url:  "/ossim/ossec/providers/ossec_rules/get_interfaces.php",
            data: data,
            beforeSend: function(xhr){

                $("#c_info").html('');
                $("#c_info").stop(true, true);

                clearTimeout(timer);

                $('#msg_init').remove();

                show_loading_box('c_tabs', ossec_msg['loading'], '');
            },
            success: function(data){

                hide_loading_box();

                var status = data.split("###");

                if (status[0] == 'error')
                {
                    $('#c_info').html(notify_error(status[1]));
                    $('#c_info').fadeIn(2000);
                }
                else
                {
                    var params = data.split("##__##")
                    $("#tab1").html(params[2]);

                    $('textarea').on('focus', function() { $(this).css('color', '#2F85CA');});
                    $('textarea').on('blur',  function() { $(this).css('color', '#000000');});

                    $('textarea').elastic();

                    $("input[type='text']").on('focus', function() { $(this).css('color', '#2F85CA');});
                    $("input[type='text']").on('blur',  function() { $(this).css('color', '#000000');});


                    set_autocomplete(".auto_c");

                    //Tooltips
                    $('.actions_bt_at img').tipTip({maxWidth : 'auto', edgeOffset: 14, defaultPosition : 'top'});
                    $('.actions_bt_node img').tipTip({maxWidth : 'auto', edgeOffset: 14, defaultPosition : 'top'});
                    $('.actions_bt_tn img').tipTip({maxWidth : 'auto', edgeOffset: 14, defaultPosition : 'top'});
                }

                show_tab_content(tab);
            }
        });
    }
}


function edit_child(level_key)
{
    var key = $(layer).dynatree("getTree").getNodeByKey(level_key);

    if (key != null)
    {
        $(layer).dynatree("getTree").getNodeByKey(level_key).activate();
    }
    else
    {
        $('#c_info').html(notify_error(ossec_msg['f_save_changes']));
        $('#c_info').fadeIn(2000);

        window.parent.scrollTo(0, 250);
    }
}


function set_autocomplete(id)
{
    if ($(id).length >= 1)
    {
        $(id).autocomplete(content_ac, {
            minChars: 0,
            width: 250,
            max: 100,
            mustMatch: true,
            autoFill: true
        }).result(function(event, item) {

            var t_id = $(this).attr('id');
            var id   = t_id.replace('tn_', 'n_');

            if (typeof(item) != 'undefined' && item != null)
            {
                $('#'+id).val(item);
            }
            else
            {
                $('#'+id).val('');
                $('#'+t_id).val('');
            }
        });
    }
}


function get_new_id(id)
{
    var new_id = null;
    var aux_id = null;

    if (id.match("_clone") == null)
    {
        if (id.match("-") == null)
        {
            new_id = uniqid()+"-"+id;
        }
        else
        {
            aux_id = id.split("-");
            new_id = uniqid()+"-"+aux_id[aux_id.length-1];
        }
    }
    else
    {
        aux_id = id.split("_clone");
        new_id = uniqid()+"_clone-"+aux_id[1];
    }

    return new_id;
}


function show_node_xml(lk_value)
{
    var cont_id    = '#node_xml-'+lk_value;
    var content_id = '#cont_node_xml-'+lk_value;

    if ($(cont_id).hasClass('oss_show'))
    {
        $(cont_id).removeClass();
        $(cont_id).addClass('oss_hide');
        $(cont_id).hide();
    }
    else
    {
        $.ajax({
            type: "POST",
            url: "/ossim/ossec/providers/ossec_rules/get_xml_node.php",
            data: "lk_value=" + lk_value,
            dataType: 'json',
            beforeSend: function(xhr){

                $("#c_info").html('');
                $("#c_info").stop(true, true);

                clearTimeout(timer);
            },
            error: function(data){

                $('#c_info').html(notify_error(ossec_msg['unknown_error']));
                $("#c_info").fadeIn(2000);

                window.parent.scrollTo(0, 250);
            },
            success: function(data){

                var cnd_1 = (typeof(data) == 'undefined' || data == null);
                var cnd_2 = (typeof(data) != 'undefined' && data != null && data.status == 'error');

                if (cnd_1 || cnd_2)
                {
                    var error_msg = (cnd_1 == true) ? ossec_msg['unknown_error'] : data.data;
                    var nt = notify_error(error_msg);

                    $('#c_info').html(nt);
                    $("#c_info").fadeIn(2000);

                    window.parent.scrollTo(0, 250);
                }
                else
                {
                    if ($("#txt_rule-"+lk_value).html() == '')
                    {
                        var editor_rule = new CodeMirror(CodeMirror.replace("txt_rule-"+lk_value), {
                            parserfile: "parsexml.js",
                            stylesheet: "/ossim/style/xmlcolors.css",
                            path: "/ossim/js/codemirror/",
                            continuousScanning: false,
                            content: data.data,
                            height: "110px",
                            lineNumbers: true,
                            readOnly: true
                        });
                    }

                    $(cont_id).removeClass();
                    $(cont_id).addClass('oss_show');
                    $(cont_id).show();
                }
            }
        });
    }
}