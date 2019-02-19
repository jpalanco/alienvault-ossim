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

Session::logcheck('configuration-menu', 'PolicyServers');


$id     = GET('id');
$update = intval(GET('update'));


ossim_valid($id,    OSS_HEX,    'illegal:' . _('Server ID'));

if (ossim_error())
{
    die(ossim_error());
}


$db       = new ossim_db();
$conn     = $db->connect();

$pro      = (Session::is_pro()) ? TRUE : FALSE;
$mssp     = intval($conf->get_conf("alienvault_mssp"));
$local_id = Util::uuid_format_nc($conf->get_conf("server_id"));


$all_rservers      = Server::get_server_hierarchy($conn, $id);
$error_forward     = FALSE;
$can_i_modify_elem = TRUE;


$server     = Server::get_object($conn, $id);
$role_list  = Role::get_list($conn, $id);

if (!empty($server) && !empty($role_list))
{
    $role            =  $role_list[0];
    $sname           =  $server->get_name();
    $ip              =  $server->get_ip();
    $port            =  $server->get_port();
    $descr           =  $server->get_descr();
    $correlate       =  $role->get_correlate();
    $cross_correlate =  $role->get_cross_correlate();
    $store           =  $role->get_store();
    $rep             =  $role->get_reputation();
    $qualify         =  $role->get_qualify();
    $resend_events   =  $role->get_resend_event();
    $resend_alarms   =  $role->get_resend_alarm();
    $sign            =  $role->get_sign();
    $sem             =  $role->get_sem();
    $sim             =  $role->get_sim();
    $alarm_to_syslog =  $role->get_alarms_to_syslog();
    $remoteadmin     =  $server->get_remoteadmin();
    $remotepass      =  Util::fake_pass($server->get_remotepass());
    $remoteurl       =  $server->get_remoteurl();
    $my_rservers     =  '';

    $rservers_list   = Server::get_my_hierarchy($conn, $id);

    foreach ($rservers_list as $sid => $sdata)
    {
        $val          = $sid."@".$sdata[2];
        $r_ip         = Server::get_server_ip($conn, $sid);
        $text         = $sdata[0]." [$r_ip] (".$sdata[2].")";
        $my_rservers .= "<option value='$val'>$text</option>\n";

        if(!$all_rservers[$sid])
        {
            $error_forward = $sdata[0];
        }
    }

    if($pro)
    {
        $_engines = Acl::get_engines_by_server($conn,$id);

        if (count($_engines) < 1)
        {
            $mssp = FALSE;
        }
    }
}
// Editting a server added from remote
elseif (!empty($server))
{
    $sname  =  $server->get_name();
    $ip     =  $server->get_ip();
}



if ($id != $local_id)
{
    $can_i_modify_elem = FALSE;
    $external_ctx      = $sname . ' (' . $ip . ')';
}


$dis_sim                = ($sim == 0) ? "disabled='disabled'" : '';
$dis_resend             = (!$pro || ($sim == 0 && $sem == 0)) ? "disabled='disabled'" : '';
$dis_opens              = (!$pro) ? "disabled='disabled'" : '';
$dis_sign               = ($sem == 0) ? "disabled='disabled'" : '';

$class_sim              = ($sim == 0) ? "class='thgray'" : '';
$class_resend           = (!$pro || ($sem == 0 && $sim == 0)) ? "class='thgray'" : '';
$class_sign             = (!$pro || ($sem == 0)) ? "class='thgray'" : '';
$class_opens            = (!$pro) ? "class='thgray'" : '';
$class_rservers         = (!$pro || ($sem == 0 && $sim == 0)) ? "class='thgray'" : '';

$chk_correlate[0]       = ($correlate == 0) ? "checked='checked' $dis_sim" : "$dis_sim";
$chk_correlate[1]       = ($correlate == 1) ? "checked='checked' $dis_sim" : "$dis_sim";

$chk_cross_correlate[0] = ($cross_correlate == 0) ? "checked='checked' $dis_sim" : "$dis_sim";
$chk_cross_correlate[1] = ($cross_correlate == 1) ? "checked='checked' $dis_sim" : "$dis_sim";

$chk_qualify[0]         = ($qualify == 0) ? "checked='checked' $dis_sim" : "$dis_sim";
$chk_qualify[1]         = ($qualify == 1) ? "checked='checked' $dis_sim" : "$dis_sim";

$chk_store[0]           = ($store == 0) ? "checked='checked' $dis_sim" : "$dis_sim";
$chk_store[1]           = ($store == 1) ? "checked='checked' $dis_sim" : "$dis_sim";

$chk_ats[0]             = ($alarm_to_syslog == 0) ? "checked='checked' $dis_sim" : "$dis_sim";
$chk_ats[1]             = ($alarm_to_syslog == 1) ? "checked='checked' $dis_sim" : "$dis_sim";

$chk_rep[0]             = ($rep == 0) ? "checked='checked' $dis_sim" : "$dis_sim";
$chk_rep[1]             = ($rep == 1) ? "checked='checked' $dis_sim" : "$dis_sim";

$chk_sem[0]             = ($sem == 0) ? "checked='checked' $dis_opens  " : "$dis_opens  ";
$chk_sem[1]             = ($sem == 1) ? "checked='checked' $dis_opens  " : "$dis_opens  ";

$chk_multi[0]           = ($sem == 0 && $sim == 0) ? "checked='checked' $dis_opens   " : "$dis_opens  ";
$chk_multi[1]           = ($sem == 1 || $sim == 1) ? "checked='checked' $dis_opens   " : "$dis_opens  ";

$chk_sim[0]             = ($sim == 0) ? "checked='checked'" : "";
$chk_sim[1]             = ($sim == 1) ? "checked='checked'" : "";

$chk_sign[0]            = ($sign == 0) ? "checked='checked' $dis_sign" : "$dis_sign";
$chk_sign[1]            = ($sign == 1) ? "checked='checked' $dis_sign" : "$dis_sign";

$chk_resend_events[0]   = ($resend_events == 0) ? "checked='checked' $dis_resend" : "$dis_resend";
$chk_resend_events[1]   = ($resend_events == 1) ? "checked='checked' $dis_resend" : "$dis_resend";

$chk_resend_alarms[0]   = ($resend_alarms == 0) ? "checked='checked' $dis_resend" : "$dis_resend";
$chk_resend_alarms[1]   = ($resend_alarms == 1) ? "checked='checked' $dis_resend" : "$dis_resend";


$action   = 'modifyserver.php';
$back_url = Menu::get_menu_url("/ossim/server/server.php", "configuration", "deployment", "components", "servers");

$readonly = (!$can_i_modify_elem) ? "readonly='readonly' disabled='disabled'" : '';

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>

    <title> <?php echo _('AlienVault ' . (Session::is_pro() ? 'USM' : 'OSSIM')); ?> </title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
    <meta http-equiv="Pragma" content="no-cache"/>

    <?php
        //CSS Files
        $_files = array(
            array('src' => 'av_common.css',         'def_path' => TRUE),
            array('src' => 'tree.css',              'def_path' => TRUE),
            array('src' => 'tipTip.css',            'def_path' => TRUE)
        );

        Util::print_include_files($_files, 'css');

        //JS Files
        $_files = array(
            array('src' => 'jquery.min.js',                 'def_path' => TRUE),
            array('src' => 'jquery-ui.min.js',              'def_path' => TRUE),
            array('src' => 'utils.js',                      'def_path' => TRUE),
            array('src' => 'notification.js',               'def_path' => TRUE),
            array('src' => 'token.js',                      'def_path' => TRUE),
            array('src' => 'jquery.elastic.source.js',      'def_path' => TRUE),
            array('src' => 'combos.js',                     'def_path' => TRUE),
            array('src' => 'jquery.dynatree.js',            'def_path' => TRUE),
            array('src' => 'messages.php',                  'def_path' => TRUE),
            array('src' => 'ajax_validator.js',             'def_path' => TRUE),
            array('src' => 'jquery.tipTip-ajax.js',         'def_path' => TRUE)
        );

        Util::print_include_files($_files, 'js');
    ?>


    <style type='text/css'>
        ul
        {
            margin-top:5px;
        }

        ul.tip
        {
            padding-left:15px;
        }

        input[type='text'], input[type='hidden'], select
        {
            width: 98%;
            height: 18px;
        }

        textarea
        {
            width: 97%; height: 45px;
        }

        .cont_radio
        {
            width: 98%;
            float: left;
        }

        .val_error
        {
            width: 270px;
        }

        .del_eng
        {
            cursor:pointer;
            color:darkred;
            font-style:italic;
        }

        #server_container
        {
            margin: 40px auto 20px auto;
            width: 500px;
            padding-bottom: 10px;
        }

        #server_container label, #server_container .s_label
        {
            font-size: 12px;
        }

        #server_container label.y_n
        {
            font-size: 11px !important;
        }

        #server_container #table_form
        {
            margin: auto;
            width: 100%;
        }

        #table_form th
        {
            width: 150px;
            text-align: left;
        }

        #engine_notification
        {
            display:none;
        }

        #key_notification
        {
            display:none;
        }

        #c_fserver
        {
            margin-top: 5px;
            text-align: right;
            width: 98%;
        }

        #av_info
        {
            width: 530px;
            margin: 10px auto;
        }

        .text_ip, .text_name
        {
            cursor: default !important;
            font-style: italic !important;
            opacity: 0.5 !important;
        }
    </style>

    <script type="text/javascript">

        var valsim = <?php echo (isset($sim)) ? $sim : 0 ?>;
        var valsem = <?php echo (isset($sem)) ? $sem : 0 ?>;


        function show_notification(id, msg, type)
        {
            var id = '#' + id;

            var config_nt = {
                "content" : msg,
                "options" : {
                    "type" : type,
                    "cancel_button" : false
                },
                "style" : 'width:85%; display:none; text-align:center; margin:10px auto; padding:0px 5px;'
                };

            nt = new Notification('nt_js',config_nt);

            $(id).find('div').html(nt.show());

            $(id).show();
            nt.fade_in(1000);

            setTimeout(function() {
                nt.fade_out(1000, function() {
                    $(id).hide();
                });
            }, 4000);
        }


        function send_server()
        {
            selectall('rservers');
            ajax_validator.submit_form();
        }


        function disen(element,text)
        {
            if (element.is(':disabled') == true)
            {
                element.removeAttr('disabled');
                text.removeClass("thgray");
            }
            else
            {
                element.attr('disabled', 'disabled');
                text.addClass("thgray");
            }
        }


        function dis(element,text)
        {
            element.attr('disabled', 'disabled');
            text.addClass("thgray");
        }


        function en(element,text)
        {
            element.removeAttr('disabled');
            text.removeClass("thgray");
        }


        // show/hide some options
        function tsim(val)
        {
            valsim = val;

            if (val == 1)
            {
                en($('input[name=correlate]'),$('#correlate_text'));
                en($('input[name=cross_correlate]'),$('#cross_correlate_text'));
                en($('input[name=store]'),$('#store_text'));
                en($('input[name=alarm_to_syslog]'),$('#ats_text'));
                en($('input[name=qualify]'),$('#qualify_text'));
            }
            else
            {
                dis($('input[name=correlate]'),$('#correlate_text'));
                dis($('input[name=cross_correlate]'),$('#cross_correlate_text'));
                dis($('input[name=store]'),$('#store_text'));
                dis($('input[name=alarm_to_syslog]'),$('#ats_text'));
                dis($('input[name=qualify]'),$('#qualify_text'));
            }


            if (valsim==0 && valsem==0)
            {
                dis($('input[name=resend_alarms]'),$('#ralarms_text'));
                dis($('input[name=resend_events]'),$('#revents_text'));
                dis($('select[id=rservers]'),$('#rservers_text'));

                $('input[name=resend_alarms]')[1].checked = true;
                $('input[name=resend_events]')[1].checked = true;
                $('input[name=multi]')[1].checked = true;
            }
            else
            {
                en($('input[name=resend_alarms]'),$('#ralarms_text'));
                en($('input[name=resend_events]'),$('#revents_text'));
                en($('select[id=rservers]'),$('#rservers_text'));
                $('input[name=multi]')[0].checked = true;
            }
        }


        function tsem(val)
        {
            valsem = val;

            if (valsem==0)
            {

                dis($('input[name=sign]'),$('#sign_text'));

                document.form_server.remoteadmin.value = "";
                document.form_server.remotepass.value = "";
                document.form_server.remoteurl.value = "";
                $('.remoteinput').hide();
            }
            else
            {
                en($('input[name=sign]'),$('#sign_text'));
            }


            if (valsim==0 && valsem==0)
            {
                dis($('input[name=resend_alarms]'),$('#ralarms_text'));
                dis($('input[name=resend_events]'),$('#revents_text'));
                dis($('select[id=rservers]'),$('#rservers_text'));

                $('input[name=resend_alarms]')[1].checked = true;
                $('input[name=resend_events]')[1].checked = true;
                $('input[name=multi]')[1].checked = true;
            }
            else
            {
                en($('input[name=resend_alarms]'),$('#ralarms_text'));
                en($('input[name=resend_events]'),$('#revents_text'));
                en($('select[id=rservers]'),$('#rservers_text'));

                $('input[name=multi]')[0].checked = true;
            }
        }


        function tmulti(val)
        {
            if (val == 1)
            {
                en($('input[name=resend_alarms]'),$('#ralarms_text'));
                en($('input[name=resend_events]'),$('#revents_text'));
                en($('select[id=rservers]'),$('#rservers_text'));
            }
            else
            {
                dis($('input[name=resend_alarms]'),$('#ralarms_text'));
                dis($('input[name=resend_events]'),$('#revents_text'));
                dis($('select[id=rservers]'),$('#rservers_text'));
            }
        }


        function autofillurl()
        {
            if (document.getElementById('remoteurl_input').value == "")
            {
                var aux_ip = (document.getElementById('ip').value != "") ? document.getElementById('ip').value : "IP_ADDRESS";
                document.getElementById('remoteurl_input').value = "https://"+aux_ip+"/ossim";
            }
        }


        function trim(myString)
        {
            return myString.replace(/^\s+/g,'').replace(/\s+$/g,'').replace(/@/g,'');
        }


        function delete_frw_server()
        {

            var s_rserver    = $('#rservers').val();
            var flag_confirm = false;

            if(typeof(s_rserver) != 'undefined' && s_rserver != null && s_rserver.length > 0)
            {
                $.ajax({
                    type: "POST",
                    url: "forward_server.php",
                    async: false,
                    dataType: "json",
                    data: {"source": '<?php echo $id ?>' , "dests": s_rserver},
                    success: function(data) {
                        if(!data.error)
                        {
                            if(data.count > 0)
                            {
                                flag_confirm = true;
                            }
                        }
                    }
                });
            }

            if(flag_confirm)
            {
                av_confirm('<?php echo  Util::js_entities(_('This server is related to a Policy. If you delete this forward option, the policy will be affected. Are you sure you want to continue?'))?>').done(function()
                {
                    deletefrom('rservers');
                });
            }
            else
            {
                deletefrom('rservers');
            }
        }


        function add_frw_server()
        {
            var server_id  = trim($('#frw_ser').val());
            var server_txt = trim($('#frw_ser option:selected').text());
            var prio       = trim($('#frw_prio').val());

            if(server_id != '' && !check_exist('rservers', server_id))
            {
                var filter_id  = server_id + '@' + prio;
                var filter_txt = server_txt + ' (' + prio + ')';
                addto('rservers',filter_txt,filter_id);
                selectall('rservers');
                $('#frw_ser').val(0);
                $('#frw_prio').val(0);
            }
            return false;
        }


        function check_exist(selector, sid)
        {
            var exist = false;
            $('#'+selector + ' option').each(function(){
                var id = $(this).val().split('@');
                id = id[0];
                if(id == sid)
                    exist = true;
            });
            return exist;
        }


        function editNode(node)
        {
            var prevTitle = node.data.title;
            var tree      = node.tree;
            var engine    = node.data.key;
            // Disable dynatree mouse- and key handling
            tree.$widget.unbind();

            // Replace node with <input>
            $(".dynatree-title", node.span).html("<input id='editNode' value='" + prevTitle + "'>");

            // Focus <input> and bind keyboard handler
            $("input#editNode").off('blur focus keydown');
            $("input#editNode")
                .focus()
                .keydown(function(event){
                    switch(event.which) {
                        case 27: // [esc]
                            // discard changes on [esc]
                            $("input#editNode").val(prevTitle);
                            $(this).blur();
                            break;
                        case 13: // [enter]
                            // simulate blur to accept new value
                            $(this).blur();
                            break;
                    }
                }).blur(function(event){
                    // Accept new value, when user leaves <input>
                    var title = $("input#editNode").val();

                    if(prevTitle != title)
                    {
                        $.ajax({
                            type: "POST",
                            url: "engine_ajax.php",
                            data: {"action": 2, "data": {"engine": engine, "name":title}},
                            dataType: "json",
                            async:false,
                            success: function(data) {
                                if(!data.error)
                                {
                                    prevTitle = title;
                                }
                                else
                                {
                                    show_notification('engine_notification', data.msg, 'nf_error');
                                }
                            },
                            error: function(XMLHttpRequest, textStatus, errorThrown) {
                                show_notification('engine_notification', errorThrown, 'nf_error');
                            }
                        });
                    }

                    // Re-enable mouse and keyboard handlling
                    node.setTitle(prevTitle);
                    tree.$widget.bind();
                    node.focus();
                });
        }


        function reorder_ctx(ctx, old_engine, new_engine)
        {
            var flag = false;

            $.ajax({
                type: "POST",
                url: "engine_ajax.php",
                data: {"action": 1, "data": {"ctx":ctx, "old_engine": old_engine, "new_engine": new_engine}},
                dataType: "json",
                async:false,
                success: function(data) {
                    if(!data.error)
                    {
                        flag = true;
                    }
                    else
                    {
                        show_notification('engine_notification', data.msg, 'nf_error');
                    }
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    show_notification('engine_notification', errorThrown, 'nf_error');
                }
            });

            return flag;
        }


        function add_engine()
        {
            var name = $('#eng_name').val();

            $.ajax({
                type: "POST",
                url: "engine_ajax.php",
                data: {"action": 3, "data": {"server": "<?php echo $id ?>", "name": name}},
                dataType: "json",
                async:false,
                success: function(data) {
                    if(!data.error)
                    {
                        var tree = $("#tree").dynatree("getTree");
                        tree.reload();
                    }
                    else
                    {
                        show_notification('engine_notification', data.msg, 'nf_error');
                    }
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    show_notification('engine_notification', errorThrown, 'nf_error');
                }
            });

            $('#eng_name').val('');

            return false;
        }

        <?php
        if (!$can_i_modify_elem)
        {
            ?>
            // Set remote key for logger
            function setkey()
            {
                var remoteadmin = $('#remoteadmin').val();
                var remotepass  = $('#remotepass').val();
                var remoteurl   = $('#remoteurl_input').val();
                var action      = ($('#sem1').is(':checked')) ? 'remove' : 'set';

                if (action == 'set' && (remoteadmin == '' || remotepass == '' || remoteurl == ''))
                {
                    av_alert('<?php echo _('Remote admin user, password and URL are required. Please, fill all required fields')?>');
                }
                else
                {
                    $.ajax({
                        type: "POST",
                        url: "ajax/set_remote_key.php",
                        data: {"action": action, "remoteadmin": remoteadmin, "remotepass": remotepass, "remoteurl": remoteurl, "server_id": "<?php echo $id ?>"},
                        dataType: "json",
                        success: function(data)
                        {
                            if (data.error)
                            {
                                show_notification('key_notification', data.msg, 'nf_error');
                            }
                            else
                            {
                                show_notification('key_notification', data.msg, 'nf_success');

                                if (action == 'set')
                                {
                                    $('#setkey_button').val('Disable remote logger');
                                    $('#sem1').attr('checked', true);
                                }
                                else
                                {
                                    $('#setkey_button').val('Set remote key');
                                    $('#sem2').attr('checked', true);
                                }
                            }
                        },
                        error: function(XMLHttpRequest, textStatus, errorThrown)
                        {
                            show_notification('key_notification',errorThrown,'nf_error');
                        }
                    });
                }
            }
            <?php
        }
        ?>

        $(document).ready(function(){

            Token.add_to_forms();

            $('textarea').elastic();

            var config = {
                validation_type: 'complete', // single|complete
                errors:{
                    display_errors: 'all', //  all | summary | field-errors
                    display_in: 'av_info'
                },
                form : {
                    id  : 'form_server',
                    url : '<?php echo $action?>'
                },
                actions: {
                    on_submit:{
                        id: 'send',
                        success: '<?php echo _('SAVE')?>',
                        checking: '<?php echo _('Updating')?>'
                    }
                }
            };


            ajax_validator = new Ajax_validator(config);

            $('#send').on('click', function()
            {
                send_server();
            });

            selectall('rservers');

            <?php

            if ($error_forward)
            {
                echo "av_alert('". Util::js_entities("Forward servers: $error_forward - " ._('This server is configured for forwarding but it is not allowed for this task. Please check this configuration.'))."');";
            }

            if ($mssp && $pro)
            {
                if ($can_i_modify_elem)
                {
                    ?>
                    $("#tree").dynatree({
                        initAjax: { url: "../tree.php?key=engineservers_<?php echo $id ?>" },
                        clickFolderMode: 2,
                        onRightClick: function(node, event)
                        {
                            if(node.data.isEngine)
                            {
                                editNode(node);
                            }
                            return false;
                        },
                        dnd:
                        {
                            onDragStart: function(node)
                            {
                                /** This function MUST be defined to enable dragging for the tree.
                                 *  Return false to cancel dragging of node.
                                 */
                                if(node.data.isContext)
                                {
                                    return true;
                                }
                                return false;
                            },
                            autoExpandMS: 1000,
                            preventVoidMoves: true, // Prevent dropping nodes 'before self', etc.
                            onDragEnter: function(node, sourceNode) {
                                return true;
                            },
                            onDragOver: function(node, sourceNode, hitMode) {
                                /** Return false to disallow dropping this node.
                                 *
                                 */
                                if(!node.data.isEngine)
                                {
                                    return false;
                                }
                            },
                            onDrop: function(node, sourceNode, hitMode, ui, draggable) {
                                /** This function MUST be defined to enable dropping of items on
                                 * the tree.
                                 */
                                if(node.data.isEngine)
                                {
                                    //sourceNode.move(node, 'child');
                                    if(reorder_ctx(sourceNode.data.key, sourceNode.parent.data.key, node.data.key))
                                        node.tree.reload();
                                }
                            }
                        }
                    });

                    $(document).on('click', '.del_eng', function(){

                        if (confirm('<?php echo  Util::js_entities(_('You are going to delete a correlation engine. This action can not be undone. Are you sure you want to continue?'))?>'))
                        {
                            var engine = $(this).data('id');

                            $.ajax({
                                type: "POST",
                                url: "engine_ajax.php",
                                data: {"action": 4, "data": {"engine": engine}},
                                dataType: "json",
                                async:false,
                                success: function(data) {
                                    if(!data.error)
                                    {
                                        var tree = $("#tree").dynatree("getTree");
                                        tree.reload();
                                    }
                                    else
                                    {
                                        show_notification('engine_notification',data.msg,'nf_error');
                                    }
                                },
                                error: function(XMLHttpRequest, textStatus, errorThrown) {
                                    show_notification('engine_notification',errorThrown,'nf_error');
                                }
                            });
                        }
                    });

                    <?php
                }
                else
                {
                    ?>
                    $("#tree").dynatree({
                        initAjax: { url: "../tree.php?key=engineservers_<?php echo $id ?>" },
                        clickFolderMode: 2,
                        onActivate: function(dtnode, event) {
                            return false;
                        },
                        onDeactivate: function(dtnode) {},
                        onLazyRead: function(dtnode){},
                        onRightClick: function(node, event) {}
                    });
                    <?php
                }
                ?>

                $('.tiptip').tipTip();
                <?php
            }
            ?>

            //Greybox options

            if (!parent.is_lightbox_loaded(window.name))
            {
                $('.c_back_button').show();

            }
            else
            {
                $('#server_container').css('margin', '10px auto 20px auto');
            }

            <?php
            if (!$can_i_modify_elem)
            {
                ?>
                $("#setkey_button").click(function()
                {
                    setkey();
                });
                <?php
            }
            ?>
        });
    </script>
</head>

<body>

    <div class="c_back_button">
        <input type='button' class="av_b_back" onclick='document.location.href="<?php echo $back_url ?>";return false;'/>
    </div>

    <div id='av_info'>
        <?php
        if ($update == 1)
        {
            $config_nt = array(
                'content' => _('Server successfully saved'),
                'options' => array (
                    'type'          => 'nf_success',
                    'cancel_button' => true
                ),
                'style'   => 'width: 100%; margin: auto; text-align:center;'
            );

            $nt = new Notification('nt_1', $config_nt);
            $nt->show();
        }

        if (!$can_i_modify_elem)
        {
            $config_nt = array(
                'content' => _('The properties of this asset can only be modified at the USM:')." <strong>".$external_ctx."</strong>",
                'options' => array (
                    'type'          => 'nf_warning',
                    'cancel_button' => TRUE
               ),
                'style'   => 'width: 100%; margin: auto; text-align:center;'
           );

            $nt = new Notification('nt_1', $config_nt);
            $nt->show();
        }
        ?>

    </div>

    <div id='server_container'>
        <div class='legend'>
            <?php echo _('Values marked with (*) are mandatory');?>
        </div>

        <form method="post" name='form_server' id='form_server' action="modifyserver.php">

        <table align="center" id='table_form' width='60%'>

            <input type="hidden" name="insert" value="insert"/>

            <input type="hidden" name="setssh" value=""/>


            <tr>
                <th>
                    <label for='sname'><?php echo _('Name') . required();?></label>
                </th>
                <td class="left">
                    <input type='text' class='vfield' name='sname' id='sname' value="<?php echo $sname?>" <?php echo $readonly ?>/>
                </td>
            </tr>

            <tr>
                <th>
                    <label for='ip'><?php echo _('IP') . required();?></label>
                </th>
                <td class="left">
                    <?php
                    if ( preg_match("/modify/",$action) )
                    {
                        ?>
                        <input type="text" class='text_ip' name="text_ip" id="text_ip" value="<?php echo $ip?>" readonly='readonly' disabled='disabled'/>
                        <input type="hidden" class='vfield' name="ip" id="ip" value="<?php echo $ip ?>"/>
                        <?php
                    }
                    else
                    {
                        ?>
                        <input type="text" class='vfield' name="ip" id="ip" value="<?php echo $ip ?>"/>
                        <?php
                    }
                    ?>
                </td>
            </tr>

            <tr>
                <th>
                    <label for='port'><?php echo _('Port') . required();?></label>
                </th>
                <td class="left">
                    <input type="text" class='vfield' name="port" id="port" value="<?php echo (!(empty($port))) ? $port : 40001;?>" <?php echo $readonly ?>/>
                </td>
            </tr>

            <tr>
                <th>
                    <span class="s_label" id="sim" style="text-decoration:underline"><?php echo _('Security Events') . required()?></span>
                </th>
                <td class="left">
                    <div class='cont_radio'>
                        <input type="radio" name="sim" class='vfield' id='sim1' value="1" onclick="tsim(1)" <?php echo $chk_sim[1];?> <?php echo $readonly ?>/>
                        <label class="y_n" for="sim1"><?php echo _('Yes');?></label>
                        <input type="radio" name='sim' class='vfield' id="sim2" value="0" onclick="tsim(0)" <?php echo $chk_sim[0];?> <?php echo $readonly ?>/>
                        <label class="y_n" for="sim2"><?php echo _('No');?></label>
                    </div>
                </td>
            </tr>

            <tr>
                <th id="qualify_text" style="padding-left:25px" <?php echo $class_sim?>>
                    <span class="s_label" id="qualify"><?php echo _('Qualify events') . required()?></span>
                </th>
                <td class="left">
                    <div class='cont_radio'>
                        <input type="radio" name="qualify" class='vfield' id="qualify1" value="1" <?php echo $chk_qualify[1];?>/>
                        <label class="y_n" for="qualify1"><?php echo _('Yes');?></label>
                        <input type="radio" name="qualify" class='vfield' id="qualify2" value="0" <?php echo $chk_qualify[0];?>/>
                        <label class="y_n" for="qualify2"><?php echo _('No');?></label>
                    </div>
                </td>
            </tr>

            <tr>
                <th id="correlate_text" style="padding-left:25px" <?php echo $class_sim?>>
                    <span class="s_label" id="correlate"><?php echo _('Correlate events') . required()?></span>
                </th>
                <td class="left">
                    <div class='cont_radio'>
                        <input type="radio" name="correlate" class='vfield' id="correlate1" value="1" <?php echo $chk_correlate[1];?>/>
                        <label class="y_n" for="correlate1"><?php echo _('Yes');?></label>
                        <input type="radio" name="correlate" class='vfield' id="correlate2" value="0" <?php echo $chk_correlate[0];?>/>
                        <label class="y_n" for="correlate2"><?php echo _('No');?></label>
                    </div>
                </td>
            </tr>

            <tr>
                <th id="cross_correlate_text" style="padding-left:25px" <?php echo $class_sim?>>
                    <span class="s_label" id="cross_correlate"><?php echo _('Cross Correlate events') . required()?></span>
                </th>
                <td class="left">
                    <div class='cont_radio'>
                        <input type="radio" name="cross_correlate" class='vfield' id="cross_correlate1" value="1" <?php echo $chk_cross_correlate[1];?>/>
                        <label class="y_n" for="cross_correlate1"><?php echo _('Yes');?></label>
                        <input type="radio" name="cross_correlate" class='vfield' id="cross_correlate2" value="0" <?php echo $chk_cross_correlate[0];?>/>
                        <label class="y_n" for="cross_correlate2"><?php echo _('No');?></label>
                    </div>
                </td>
            </tr>

            <tr>
                <th id="store_text" style="padding-left:25px" <?php echo $class_sim?>>
                    <span class="s_label" id="store"><?php echo _('Store events') . required()?></span>
                </th>
                <td class="left">
                    <div class='cont_radio'>
                        <input type="radio" name="store" class='vfield' id="store1" value="1" <?php echo $chk_store[1];?>/>
                        <label class="y_n" for="store1"><?php echo _('Yes');?></label>
                        <input type="radio" name="store" class='vfield' id="store2" value="0" <?php echo $chk_store[0];?>/>
                        <label class="y_n" for="store2"><?php echo _('No');?></label>
                    </div>
                </td>
            </tr>

            <tr>
                <th id="ats_text" style="padding-left:25px" <?php echo $class_sim?>>
                    <span class="s_label" id="alarm_to_syslog"><?php echo _('Alarms to Syslog') . required()?></span>
                </th>
                <td class="left">
                    <div class='cont_radio'>
                        <input type="radio" name="alarm_to_syslog" class='vfield' id="ats1" value="1" <?php echo $chk_ats[1];?>/>
                        <label class="y_n" for="ats1"><?php echo _('Yes');?></label>
                        <input type="radio" name="alarm_to_syslog" class='vfield' id="ats2" value="0" <?php echo $chk_ats[0];?>/>
                        <label class="y_n" for="ats2"><?php echo _('No');?></label>
                    </div>
                </td>
            </tr>

            <tr>
                <th id="reputation_text" style="padding-left:25px" <?php echo $class_sim?>>
                    <span class="s_label" id="reputation"><?php echo _('IP Reputation') . required()?></span>
                </th>
                <td class="left">
                    <div class='cont_radio'>
                        <input type="radio" name="reputation" class='vfield' id="reputation1" value="1" <?php echo $chk_rep[1];?>/>
                        <label class="y_n" for="reputation1"><?php echo _('Yes');?></label>
                        <input type="radio" name="reputation" class='vfield' id="reputation2" value="0" <?php echo $chk_rep[0];?>/>
                        <label class="y_n" for="reputation2"><?php echo _('No');?></label>
                    </div>
                </td>
            </tr>

            <tr>
                <th <?php echo $class_opens?>>
                    <span class="s_label" id="sem" style="text-decoration:underline"><?php echo _('Log') . required()?></span>&nbsp;
                    <?php
                    if ($pro)
                    {
                        ?>
                        <a class='ndc' onclick="$('.remoteinput').toggle()">
                            <img src="../pixmaps/arrow_green.gif" align="absmiddle" border="0"/>&nbsp;<?php echo _('Credentials') ?>
                        </a>
                        <?php
                    }
                    ?>
                </th>
                <td class="left">
                    <div class='cont_radio'>
                        <input type="radio" name="sem" class='vfield' id="sem1" value="1" <?php echo $chk_sem[1];?> onclick="tsem(1)" <?php echo $readonly ?> />
                        <label class="y_n" for="sem1"><?php echo _('Yes');?></label>
                        <input type="radio" name="sem" class='vfield' id="sem2" value="0" <?php echo $chk_sem[0];?> onclick="tsem(0)" <?php echo $readonly ?> />
                        <label class="y_n" for="sem2"><?php echo _('No');?></label>
                    </div>
                </td>
            </tr>

            <tr class="remoteinput" style="display:<?php ($can_i_modify_elem) ? 'none' : 'block' ?>">
                <th style="padding-left:25px">
                    <label for='remoteadmin'><?php echo _('Remote admin user')?></label>
                </th>
                <td class="left">
                    <input type="text" class='vfield' onfocus="document.form_server.setssh.value='1'" id="remoteadmin" name="remoteadmin" value="<?php echo $remoteadmin ?>" style="width:120px;height:17px">
                </td>
            </tr>
            <tr class="remoteinput" style="display:<?php ($can_i_modify_elem) ? 'none' : 'block' ?>">
                <th style="padding-left:25px">
                     <label for='remoteadmin'><?php echo _('Remote password')?></label>
                </th>
                <td class="left">
                     <input type="password" class='vfield' onfocus="document.form_server.setssh.value='1'" id="remotepass" name="remotepass" value="<?php echo $remotepass ?>" style="width:120px;height:17px" autocomplete="off">
                </td>
            </tr>
            <tr class="remoteinput" style="display:<?php ($can_i_modify_elem) ? 'none' : 'block' ?>">
                <th style="padding-left:25px">
                     <label for='remoteadmin'><?php echo _('Remote URL')?></label>
                </th>
                <td class="left">
                     <input type="text" name="remoteurl" class='vfield' id="remoteurl_input" name="remoteurl_input" value="<?php echo $remoteurl?>" onfocus="autofillurl();document.form_server.setssh.value='1'">
                </td>
            </tr>
            <?php
            // Button to call set_remote_ssh_key by ajax
            if (!$can_i_modify_elem)
            {
                ?>
                <tr class="remoteinput">
                    <th style="padding-left:25px"></th>

                    <td class="left">
                        <input type="button" id="setkey_button" value="<?php echo ($sem) ? _('Disable remote logger') : _('Set remote key') ?>">
                    </td>
                </tr>
                <tr id='key_notification'>
                    <td colspan=2 class="noborder">
                        <div></div>
                    </td>
                </tr>
                <?php
            }
            ?>
            <tr>
                <th id="sign_text" style="padding-left:25px" <?php echo $class_sign?>>
                    <span class="s_label" id="sign"><?php echo _('Sign') . required()?></span>
                </th>
                <td class="left">
                    <div class='cont_radio'>
                        <input type="radio" name="sign" class='vfield' id="sign1" value="1" <?php echo $chk_sign[1];?> <?php echo $readonly ?>/>
                        <label class="y_n" for='sign1'><?php echo _("Line");?></label>
                        <input type="radio" name="sign" class='vfield' id="sign2" value="0" <?php echo $chk_sign[0];?> <?php echo $readonly ?>/>
                        <label class="y_n" for='sign2'><?php echo _("Block");?></label>
                    </div>
                </td>
            </tr>

            <tr id="rtitle">
                <th style="text-decoration:underline" <?php echo $class_opens?>>
                     <span class="s_label" id="multi"><?php echo _('Multilevel')?></span>
                </th>
                <td class="left">
                    <input type="radio" name="multi" id="multi1" value="1" class='vfield' onclick="tmulti(1)"<?php echo $chk_multi[1];?> <?php echo $readonly ?>/>
                    <label class="y_n" for="multi1"><?php echo _('Yes');?></label>
                    <input type="radio" name="multi" id="multi2" value="0" class='vfield' onclick="tmulti(0)"<?php echo $chk_multi[0];?> <?php echo $readonly ?>/>
                    <label class="y_n" for="multi2"><?php echo _('No');?></label>
                </td>
            </tr>

            <tr>
                <th id="ralarms_text" style="padding-left:25px" <?php echo $class_resend?>>
                    <span class="s_label" id="resend_alarms"><?php echo _('Forward alarms') . required()?></span>
                </th>
                <td class="left">
                    <div class='cont_radio'>
                        <input type="radio" name="resend_alarms" class='vfield' id="resend_alarms1" value="1" <?php echo $chk_resend_alarms[1];?> <?php echo $readonly ?>/>
                        <label class="y_n" for="resend_alarms1"><?php echo _('Yes');?></label>
                        <input type="radio" name="resend_alarms" class='vfield' id="resend_alarms2" value="0" <?php echo $chk_resend_alarms[0];?> <?php echo $readonly ?>/>
                        <label class="y_n" for="resend_alarms2"><?php echo _('No');?></label>
                    </div>
                </td>
            </tr>

            <tr>
                <th id="revents_text" style="padding-left:25px" <?php echo $class_resend?>>
                    <span class="s_label" id="resend_events"><?php echo _('Forward events') . required()?></span>
                </th>
                <td class="left">
                    <div class='cont_radio'>
                        <input type="radio" name="resend_events" class='vfield' id="resend_events1" value="1" <?php echo $chk_resend_events[1];?> <?php echo $readonly ?>/>
                        <label class="y_n" for="resend_events1"><?php echo _('Yes');?></label>
                        <input type="radio" name="resend_events" class='vfield' id="resend_events2" value="0" <?php echo $chk_resend_events[0];?> <?php echo $readonly ?>/>
                        <label class="y_n" for="resend_events2"><?php echo _('No');?></label>
                    </div>

                </td>
            </tr>

            <tr>
                <th id="rservers_text" style="padding-left:25px" <?php echo $class_rservers?>>
                    <label for='rservers'><?php echo _('Forward servers')?></label>
                    <?php
                    if($pro)
                    {
                        ?>
                        <br>
                        <div style='padding:3px 0 0 10px'>
                            <a href='javascript:;' style='text-decoration:none;font-size:10px;'onclick="$('#fwr_servers').toggle();">
                                <img src="../pixmaps/arrow_green.gif" align="absmiddle" border="0"/>&nbsp;<?php echo _('Add Server') ?>
                            </a>
                        </div>
                        <?php
                    }
                    ?>
                </th>
                <td class="left noborder">
                    <select multiple='multiple' size="4" class='vfield' style="height:70px;" <?php if (!$pro || !$can_i_modify_elem) echo "disabled='disabled'";?> name="rservers[]" id="rservers">
                        <?php echo $my_rservers;?>
                    </select>
                    <?php
                    if($pro && $can_i_modify_elem)
                    {

                        ?>
                        <div id='c_fserver'>
                           <a href='javascript:;' class='button av_b_secondary small' onclick="delete_frw_server();" >[X]</a>
                        <div>
                        <?php
                    }
                    ?>
                </td>
            </tr>

            <?php
            if($pro && $can_i_modify_elem)
            {
                ?>
                <tr id='fwr_servers' style='display:none;'>
                    <td colspan='2' class="left nobborder" style='padding:10px 0px;'>
                        <table style='width:100%;' class="noborder">
                            <tr>
                                <td>
                                    <label for='frw_ser'><?php echo _('Server')?>:</label>
                                </td>
                                <td>
                                    <select id="frw_ser" name="frw_ser">
                                        <option value=''><?php echo _('Select a Server') ?>&nbsp;</option>
                                        <?php
                                        foreach($all_rservers as $s)
                                        {
                                            if (!empty($ip) && $s['ip'] != $ip)
                                            {
                                                echo "<option value='".$s['id']."'>".$s['name']." [". $s['ip'] ."]</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </td>
                                <td>
                                    <label for='frw_prio'><?php echo _('Priority')?>:</label>
                                </td>
                                <td>
                                    <select id="frw_prio" name="frw_prio" >
                                        <?php
                                        for($i=1; $i <= 99; $i++)
                                        {
                                            echo "<option value=$i>$i</option>\n";
                                        }
                                        ?>
                                    </select>
                                </td>
                                <td class='right'>
                                    <input type="button" class="av_b_secondary small" value="<?php echo _('Add New') ?>" onclick="javascript:add_frw_server();return false;"/>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <?php
            }
            ?>

            <?php
            if($mssp && $pro && (Session::am_i_admin() || Acl::am_i_proadmin()))
            {
                $tooltip = "<ul class='tip'>
                                <li>"._('Right-click on engine nodes to change its name')."</li>
                                <li>"._('Drag and drop contexts between engines')."</li>
                            </ul>";
                ?>
                <tr id='engine_notification'>
                    <td colspan=2 class="noborder">
                        <div></div>
                    </td>
                </tr>
                <tr id='engines_server' >
                    <th style="text-decoration:underline">
                        <label for='eng_name'><?php echo _('Correlation Options');?></label>
                        <a href='javascript:;' class='tiptip' title="<?php echo $tooltip ?>">
                             <img src="/ossim/vulnmeter/images/info.png" align="absline" height="14" border="0"/>
                        </a>
                    </th>
                    <td class="left nobborder" style='padding:5px 5px <?php echo ($can_i_modify_elem) ? "10" : "0" ?>px 6px;vertical-align:top'>
                        <div id="tree"></div>
                        <?php
                        if ($can_i_modify_elem)
                        {
                            ?>
                            <div style='margin:15px auto'>
                                <input type='text' id='eng_name' style='width:200px;float:left;height:15px;'>
                                <a href='javascript:;' class="button small" onclick="add_engine();" style='float:right;'>
                                    <?php echo _('New Engine') ?>
                                </a>
                            </div>
                            <?php
                        }
                        ?>
                    </td>
                </tr>
                <?php
            }
            ?>

            <tr>
                <th><label for='descr'><?php echo _('Description');?></label></th>
                <td class="left noborder"><textarea name="descr" id='descr' class='vfield' <?php echo $readonly ?>><?php echo $descr;?></textarea></td>
            </tr>


            <input type="hidden" class="vfield" name="id" id="id" value="<?php echo $id ?>"/>


            <tr>
                <td colspan="2" align="center" style="padding: 20px 10px 10px 10px;">
                    <?php
                    if($can_i_modify_elem)
                    {
                        ?>
                        <input type="button" name='send' id='send' value="<?php echo _('SAVE')?>"/>
                        <?php
                    }
                    ?>
                </td>
            </tr>
        </table>

        </form>
    </div>

</body>
</html>
