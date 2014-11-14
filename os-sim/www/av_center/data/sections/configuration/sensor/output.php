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


$pro       = Session::is_pro();

$id_s_ip   = $cnf_data['server_ip']['id'];
$server_ip = $cnf_data['server_ip']['value'];

$id        = $cnf_data['mservers']['id'];

// Format: SERVER_IP,PORT,SEND_EVENTS(True/False),ALLOW_FRMK_DATA(True/False),PRIORITY (0-5),FRMK_IP,FRMK_PORT
$mservers  = $cnf_data['mservers']['value'];


$res = Av_center::get_system_info_by_id($conn, $system_id);

if ($res['status'] != 'error')
{
    $admin_ip = $res['data']['admin_ip'];
}


$servers[$server_ip] = array ('server' => $server_ip, 'priority' => '0', 'type' => _('Server, Inventory'));

if (!empty($mservers) && $mservers != 'NULL' && $mservers != 'no')
{
    $aux_servers = explode(';', $mservers);
    
    foreach ($aux_servers as $s_data)
    {       
        $aux_data  = explode(',', $mservers);
        
        if (empty($servers[$aux_data[0]]))
        {
           $servers[$aux_data[0]] = array ('server' => $aux_data[0], 'priority' => $aux_data[4], 'type' => _('Server'));
        }
    }
}
?>

<div id='c_so'>

    <?php 
    if ($pro)
    {
        ?>
        <div class='r_actions'><a id='show_form_so'><?php echo _('Add New Server')?></a></div>
        <?php
    }
    ?>
            
    <!-- New/Edit Server Form -->
    <div id='c_form_so'>
        <div id='c_form_body'>
            <div id='c_form_title'>
                <div id='l_c_form_title'><?php echo _('New Server')?></div>
                <div id='r_c_form_title'><img src='<?php echo AVC_PIXMAPS_DIR?>/cross_button.png' title='<?php echo _('Close')?>' alt='<?php echo _('Close')?>'/></div>
            </div>
            
            <table id='t_form_so'>
                <tr>
                    <td class='_data'>
                        <div style="position:relative; width:260px;"><div class="n_loading" style='top: 5px;'></div></div>
                        <span><?php echo _('Server')?>:</span>
                        <input type='text'    name='new_server' id='new_server'/>
                        <input type='hidden'  name='old_server' id='old_server'/>
                        
                        <span><?php echo _('Priority')?>:</span>
                        <select id='server_priority' name='server_priority'>
                            <?php 
                            for ($i = 0; $i < 6; $i++)
                            {
                                $prio_text = $i;
                                
                                if ($i == 0)
                                {
                                    $prio_text.= " ["._("Max")."]";
                                }
                                elseif ($i == 5)
                                {
                                    $prio_text.= " ["._("Min")."]"; 
                                }
                                
                                ?>
                                <option id='opt_<?php echo $i?>' value='<?php echo $i?>'><?php echo $prio_text?></option>
                                <?php
                            }
                            ?>
                        </select>
                        
                        <input type='button' name='add_server' class='small add' id='add_server' value='<?php echo _('Add')?>'/>
                        <input type='hidden' name='server_id' id='server_id' value=''/>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    
    <div id='c_t_so'>
        
        <input type='hidden' id='m_server_ip' class='vfield' name='m_server_ip' value='<?php echo $server_ip?>'/>
        <input type='hidden' id='m_server_id' class='vfield' name='m_server_id' value='<?php echo md5($server_ip)?>'/>
        <input type='hidden' id='admin_ip' class='vfield' name='admin_ip' value='<?php echo $admin_ip?>'/>

        <?php
        if ($pro)
        {
            ?>
            <select id='<?php echo $id?>' name='<?php echo $id.'[]'?>' class='vfield' multiple='multiple' style='display:none;'>
                <?php
                
                if (count($servers) > 1)
                {
                    foreach ($servers as $server)
                    {
                        $id_s = md5($server['server']);
                        echo "<option id='opt_".$id_s."' selected='selected' value='".$server['server']."###".$server['priority']."'>".$server['server']."###".$server['priority']."</option>";
                    }
                }
                ?>
            </select>
            <?php
        }
        ?>      
        
        <div id='server_grid'>
            <?php
            $cont = 1;
            
            foreach ($servers as $server)
            {
                $background = ($cont % 2 == 0) ? 'odd': 'even';
                $id_s       = md5($server['server']);
                                
                if ($server['server'] == $server_ip)
                {
                    $server_menu = "<img src='".AVC_PIXMAPS_DIR."/menu.png' id='cmo_".$id_s."' class='img_menu_1 master_s'/>";
                    $master_text = "<span style='font-style: italic; margin-left: 0px;'>("._("master").")</span>";
                }
                else
                {
                    $server_menu = "<img src='".AVC_PIXMAPS_DIR."/menu.png' id='cmo_".$id_s."' class='img_menu_2'/>";
                    $master_text = '';
                }
                ?>

                <div id='cs_<?php echo $id_s?>' class='c_server <?php echo $background?>'>
                    <div class='c_server_opt'><?php echo $server_menu?></div>
                    <div class='c_server_body'>
                        <table class='t_servers'>
                            <tr id='<?php echo $id_s?>'>
                                <td class='td_so_server'>
                                    <span class='bold'><?php echo _('IP')?>:</span>
                                    <span class='td_value'><?php echo $server['server']?></span><?php echo $master_text?>
                                </td>
                                <td class='td_so_type'>
                                    <span class='bold'><?php echo _('Type')?>:</span>        
                                    <span class='td_value'><?php echo $server['type']?></span>
                                </td>
                                <td class='td_so_priority'>
                                    <span class='bold'><?php echo _('Priority')?>:</span>
                                    <span class='td_value'><?php echo $server['priority']?></span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                <?php
                $cont++;
            }
            ?>
        </div>
            
    </div>
    
</div>


<script type='text/javascript'>

    function show_cmenus()
    {
        //Context Menu (Master Server)
        if ($('#avc_cmenu_1').length < 1)
        {
            var context_menu_1 = "<ul id='avc_cmenu_1' class='contextMenu' style='width:150px'>\n" +
                                    "<li class='edit'><a href='#edit'><?php echo _("Edit")?></a></li>\n" +
                                    "<li class='delete disabled'><a href='#delete'><?php echo _("Delete")?></a></li>\n" +
                                 "</ul>\n";
                                 
            $('body').append(context_menu_1);
        }
        
        
        // Show menu when menu icon is clicked (Server IP)
        $(".img_menu_1").contextMenu({
            menu: 'avc_cmenu_1',
            leftButton: true
            },
            
            function(action, el, pos) 
            {
                    
                if (action == 'edit')
                {
                    var id     = $(el).attr('id').replace('cmo_', '');

                    edit_server(id);

                    $('#server_priority').attr('disabled', 'disabled');
                }
        });
            
        <?php 
        if ($pro)
        {
            ?>
            //Context Menu (MultiServers)
            if ($('#avc_cmenu_2').length < 1)
            {
                var context_menu_2 = "<ul id='avc_cmenu_2' class='contextMenu' style='width:150px'>\n" +
                                        "<li class='edit'><a href='#edit'><?php echo _("Edit")?></a></li>\n" +
                                        "<li class='delete'><a href='#delete'><?php echo _("Delete")?></a></li>\n" +
                                     "</ul>\n";
                                     
                $('body').append(context_menu_2)
            }


            // Show menu when menu icon is clicked
            $(".img_menu_2").contextMenu({
                menu: 'avc_cmenu_2',
                leftButton: true
            },
                function(action, el, pos) {
                
                    var id = $(el).attr('id').replace('cmo_', '');
                                
                    if (action == 'edit')
                    {
                        $('#server_priority').removeAttr('disabled');
                        
                        edit_server(id);
                    }
                    else if (action == 'delete')
                    {
                        delete_server(id);
                    }
            });
            <?php
        }
        ?>
    }
    
            
    function reset_form_type(type)
    {
        if (type == 'edit')
        {
            $('#l_c_form_title').text('<?php echo _('Edit Server')?>');
            $('#add_server').val('<?php echo _('Update')?>');
            $('#add_server').removeClass('add').addClass('update');
        }
        else
        {
            $('#l_c_form_title').text('<?php echo _('New Server')?>');
            $('#add_server').val('<?php echo _('Add')?>');
            $('#add_server').removeClass('update').addClass('add');
            $('#new_server').val('');
            $('#old_server').val('');
            $('#server_id').val('');
            $('#server_priority').removeAttr('disabled');
        }
    }
    
    function show_s_form(type)
    {
        reset_form_type(type);
        $("#c_form_so").show();
        $('.r_actions').hide();
    }
            
    
    function hide_s_form()
    {
        $('.r_actions').show();
        $("#c_form_so").hide();
    }


    $('#show_form_so').click (function(){
        show_s_form('add');
    });
    
    $('#r_c_form_title img').click (function(){
        hide_s_form();
    });


    //Show Context Menus
    show_cmenus();


    //Add server
    $('#add_server').click(function(){ 
        
        var new_server   = $('#new_server').val();
        var old_server   = $('#old_server').val();
        
        
        //Server Priority
        var priority = $('#server_priority').val();
        
        //Current Server ID
        var server_id = $('#server_id').val(); 
        
        //Master Server IP
        var m_server_ip = $('#m_server_ip').val();
        
        //Master Server ID
        var m_server_id = $('#m_server_id').val();


        //Local Server IDs
        var l_server_id_1 = '<?php echo md5('127.0.0.1')?>';
        var l_server_id_2 = '<?php echo md5($admin_ip)?>';   

        var trigger_change = false;
        var server_exists  = false;


        if (typeof(new_server) != 'string' || new_server == '')
        {
            return false;
        }

        $.ajax({
            type: "POST",
            url:  AVC_PATH+"/data/sections/configuration/sensor/sensor_actions.php",
            cache: false,
            data: "action=check_server&new_server="+new_server+ "&priority=" + priority+"&old_server="+old_server+"&system_id=" + section.system_id,
            dataType: 'json',
            beforeSend: function(xhr) {
                $('.n_loading').html('<img src="'+AV_PIXMAPS_DIR+'/loading.gif" align="absmiddle" width="13" alt="'+labels['loading']+'">');
            },
            error: function (data){
                
                var session = new Session(data, '');
                
                session.check_session_expired();
                if (session.expired == true)
                {
                    session.redirect();
                    return;
                }   
            },
            success: function(data){
                
                $('.n_loading').html('');
                
                if (typeof(data) != 'undefined' && data.status == 'error')
                {
                    var content = '<div>'+labels['error_found']+'</div><div style="padding-left: 10px;">'+data.data+'</div>';
                    
                    var config_nt = { 
                        content: content, 
                        options: {
                            type: 'nf_error',
                            cancel_button: false
                        },
                        style: 'width: 80%; white-space:nowrap; margin: auto;'
                    };

                    nt = new Notification('nt_1',config_nt);
                    
                    $('#sc_info').html(nt.show());
                    
                    window.scrollTo(0,0);
                    setTimeout('nt.fade_out(4000);', 5000);
                }
                else if (typeof(data) != 'undefined' && data.status == 'success')
                {                   
                    var s_data = data.data;
                    
                    if (s_data.is_master == true)
                    {
                        var server_menu = "<img src='"+AVC_PIXMAPS_DIR+"/menu.png' id='cmo_"+s_data.id+"' class='img_menu_1 master_s'/>";
                        var master_text = '<span style="margin-left:0px; font-style: italic;">(<?php echo _('master')?>)</span>';
                    }
                    else
                    {
                        var server_menu = "<img src='"+AVC_PIXMAPS_DIR+"/menu.png' id='cmo_"+s_data.id+"' class='img_menu_2'/>";
                        var master_text = '';
                    }


                    //Server data
                    var row =  "<div id='cs_"+s_data.id+"' class='c_server'>" +
                                    "<div class='c_server_opt'>"+server_menu+"</div>" +
                                    "<div class='c_server_body'>" +
                                        "<table class='t_servers'>" +
                                            "<tr id='"+s_data.id+"'>" +
                                                "<td class='td_so_server'>" +
                                                    "<span class='bold'><?php echo _('IP')?>:</span>" + 
                                                    "<span class='td_value'>"+new_server+"</span> "+ master_text +
                                                "</td>" +
                                                "<td class='td_so_type'>" + 
                                                    "<span class='bold'><?php echo _('Type')?>:</span>" +
                                                    "<span class='td_value'>"+s_data.server_type+"</span>" + 
                                                "</td>" +
                                                "<td class='td_so_priority'>" + 
                                                    "<span class='bold'><?php echo _('Priority')?>:</span>" +
                                                    "<span class='td_value'>"+priority+"</span>" + 
                                                "</td>" +
                                            "</tr>" +
                                        "</table>" +
                                    "</div>" +
                                "</div>";


                    if (server_id != '' && $('#add_server').hasClass('update')) //Update server
                    {
                        var cnd_1 = (s_data.id != server_id && $('#server_grid #'+s_data.id).length > 0);
                        var cnd_2 = (s_data.id == l_server_id_1 && $('#server_grid #'+l_server_id_2).length > 0 && server_id != l_server_id_2);
                        var cnd_3 = (s_data.id == l_server_id_2 && $('#server_grid #'+l_server_id_1).length > 0 && server_id != l_server_id_1);
                                                
                        
                        if(cnd_1 || cnd_2 || cnd_3)
                        { 
                             server_exists = true;
                        }
                        else
                        {
                            //Update new server layout
                            $('#server_grid #cs_'+server_id).replaceWith(row);
                                                        
                            //Update mservers when there are 2 o more servers 
                            if ($('#<?php echo $id?> option').length > 1 && $('#<?php echo $id?> #opt_'+server_id).length > 0)
                            {
                                 $('#<?php echo $id?> #opt_'+server_id).remove();
                                 $('#<?php echo $id?>').append("<option id='opt_"+s_data.id+"' selected='selected' value='"+new_server+"###"+priority+"'>"+new_server+"###"+priority+"</option>");
                            }

                            //Update server data with new current data
                            $('#server_id').val(s_data.id);
                            $('#old_server').val(new_server);
                            
                            //Add blink effect
                            $('#server_grid #cs_'+s_data.id).css({'text-decoration': 'blink', 'background-color': '#C7C7C7'}); 
                            setTimeout('$("#server_grid #cs_'+s_data.id+'").css({"text-decoration": "none", "background-color": ""})', 2000);
                                                        
                            //Changes in master server
                            if (s_data.is_master == true)
                            {
                                $('#m_server_ip').val(new_server);
                                $('#m_server_id').val(s_data.id);
                                $('#m_server_ip').trigger('change');
                            }
                            else
                            {
                                //Trigger Change Event (Change Control)
                                trigger_change = true;
                            }
                        }
                    }
                    else if (server_id == '' && $('#add_server').hasClass('add')) //Add new server
                    {
                        //Check if server already exists
                        var cnd_1 = (s_data.id != '' && $('#server_grid #'+s_data.id).length > 0);
                        var cnd_2 = (s_data.id == l_server_id_2 && $('#server_grid #'+l_server_id_1).length > 0);
                        var cnd_3 = (s_data.id == l_server_id_1 && $('#server_grid #'+l_server_id_2).length > 0);


                        if (cnd_1 || cnd_2 || cnd_3)
                        {
                            server_exists = true;
                        }
                        else
                        {
                            //Update server data (Hidden HTML elements)
                            $('#new_server').val('');
                            $('#old_server').val('');

                            
                            //Add server data (Layout)
                            $('#server_grid .c_server:first').after(row);
                            
                            //Add blink effect
                            $('#server_grid #cs_'+s_data.id).css({'text-decoration': 'blink', 'background-color': '#C7C7C7'}); 
                            setTimeout('$("#server_grid #cs_'+s_data.id+'").css({"text-decoration": "none", "background-color": ""})', 2000);
                            
                            //Trigger Change Event (Change Control)
                            trigger_change = true;
                            
                            $('#<?php echo $id?>').append("<option id='opt_"+s_data.id+"' selected='selected' value='"+new_server+"###"+priority+"'>"+new_server+"###"+priority+"</option>");


                            ///Add master server to mservers when other servers is added
                            if ($('#<?php echo $id?> #opt_'+m_server_id).length <= 0)
                            {
                                 $('#<?php echo $id?>').append("<option id='opt_"+m_server_id+"' selected='selected' value='"+m_server_ip+"###0'>"+m_server_ip+"###0</option>");
                            }
                        }
                    }
                    
                    show_cmenus();
                    
                    if (server_exists == true)
                    {
                        var config_nt = { 
                                content: labels['server_found'], 
                                options: {
                                    type: 'nf_warning',
                                    cancel_button: false
                                },
                                style: 'width: auto; white-space:nowrap;'
                            };

                        nt = new Notification('nt_1',config_nt);
                        
                        window.scrollTo(0,0);
                        $('#sc_info').html(nt.show());
                        setTimeout('nt.fade_out(4000)', 5000);
                    }
                    
                    //Trigger Change Event (Change Control)
                    if (trigger_change == true){
                        $('#<?php echo $id?>').trigger('change');
                    }

                    $('.c_server').removeClass('odd even');
                    $('.c_server:odd').addClass('odd');
                    $('.c_server:even').addClass('even');
                }
            }
        });
    });
    
    
    //Edit server
    function edit_server(id)
    {
        var priority     = $('#'+id + ' .td_so_priority .td_value').text();
        var opt_priority = '#opt_'+priority;
        var server       = $('#'+id + ' .td_so_server .td_value').text();
        
        var server_id  = $('#server_id').val(id);
                
        $('#new_server').val(server);
        $('#old_server').val(server);
        
        $('#server_priority '+ opt_priority).attr('selected', 'selected');
        
        show_s_form('edit');
    }


    //Delete server
    function delete_server(id)
    {
        var txt = '<?php echo _('You are going to delete this server. Are you sure?')?>';
        
        if (!confirm(txt)){
            return false;
        }

        $('#cs_'+id).remove();
        $('#opt_'+id).remove();
        
        
        //There is one server  --> Delete all mservers
        if ($('#<?php echo $id?> option').length == 1)
        {
            $('#<?php echo $id?> option').remove();
        }


        $('.c_server').removeClass('odd even');
        $('.c_server:odd').addClass('odd');
        $('.c_server:even').addClass('even');
        
        hide_s_form();

        //Trigger Change Event (Change Control)
        $('#<?php echo $id?>').trigger('change');
    }
</script>