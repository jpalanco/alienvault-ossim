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

Session::logcheck('environment-menu', 'PolicyHosts');


//Database connection
$db    = new ossim_db();
$conn  = $db->connect();


$id  = GET('id');

ossim_valid($id, OSS_HEX, 'illegal:' . _('Host ID'));

if (ossim_error())
{ 
    echo ossim_error(_('Error! Host not found'));
    
    exit();
}

/****************************************************
 ******************** Host data *********************
 ****************************************************/

//Properties
$p_obj      = new Properties($conn);
$properties = $p_obj->get_properties();


$host = new Asset_host($conn, $id);
$host->load_from_db($conn);

//CTX
$ctx = $host->get_ctx();

//Host Ips
$host_ips = $host->get_ips();
$ips      = $host_ips->get_ips();
$ips      = array_keys($ips);


$is_nagios_enabled = $host->is_nagios_enabled($conn); 

$is_ext_ctx  = FALSE;
$ext_ctxs    = Session::get_external_ctxs($conn);

if (!empty($ext_ctxs[$ctx]))  
{
    $is_ext_ctx = TRUE;
}


/****************************************************
 ******************* Other data *********************
 ****************************************************/

//Ports 

$ports = array();

$port_list = Port::get_list($conn);

foreach($port_list as $port)
{ 
    $ports[$port->get_port_number()." - ".$port->get_protocol_name()] = $port->get_service();
}


$_services = shell_exec("egrep 'tcp|udp' /etc/services | awk '{print $1 $2 }'");
$lines     = split("[\n\r]", $_services);

foreach($lines as $line)
{
    preg_match('/(\D+)(\d+)\/(.+)/', $line, $regs);
    
    if($ports[$regs[2].' - '.$regs[3]] == '') 
    {
        $ports[$regs[2].' - '.$regs[3]] = $regs[1];
    }
}

//Closing database connection
$db->close();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <title><?php echo _("OSSIM Framework");?></title>
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
        <meta name="viewport" content="initial-scale=1.0, user-scalable=no"/>
        <meta http-equiv="Pragma" content="no-cache"/>
        
        <script type="text/javascript" src="../js/jquery.min.js"></script>
        <script type="text/javascript" src="../js/jquery-ui.min.js"></script>
        <script type="text/javascript" src="../js/notification.js"></script>
        <script type="text/javascript" src="../js/messages.php"></script>
        <script type="text/javascript" src="../js/jquery.elastic.source.js" charset="utf-8"></script>
        <script type="text/javascript" src="../js/utils.js"></script>       
                
        <!-- Dynatree libraries: -->
        <script type="text/javascript" src="../js/jquery.cookie.js"></script>
        <script type="text/javascript" src="../js/jquery.tmpl.1.1.1.js"></script>
        <script type="text/javascript" src="../js/jquery.dynatree.js"></script>
        <script type="text/javascript" src="../js/jquery.tipTip.js"></script>
        <script type="text/javascript" src="../js/token.js"></script>
        <script type="text/javascript" src="../js/jquery.base64.js"></script>    
        <script type="text/javascript" src="../js/jquery.autocomplete.pack.js"></script>  
    
        <link rel="stylesheet" type="text/css" href="../style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
        <link rel="stylesheet" type="text/css" href="../style/jquery.autocomplete.css" />
        <link rel="stylesheet" type="text/css" href="../style/tree.css"/>
        <link rel="stylesheet" type="text/css" href="../style/tipTip.css"/>
        
        <style type='text/css'>
            
            input[type="text"], input[type="hidden"], select 
            {
                width: 98%; 
                height: 18px;
            }
          
            textarea 
            {
                width: 98%; 
                height: 65px;
            }
            
            table
            { 
                border-collapse: collapse;
                border-spacing: 0px;
                border: none;
                background: none;
                margin: auto;
                width: 100%;
            }
            
            #host_container 
            {
                width: 600px;
                margin: 20px auto 125px auto;
                border: none;
            }
            
            #host_container #t_container
            { 
                border-collapse: collapse;
                border: none;
                background: none;
                margin: auto;
                width: 100%;
            } 
            
            #host_container #t_container
            { 
                border-collapse: collapse;
                border: none;
                background: none;
                margin: auto;
                width: 100%;
                min-height: 370px;
                min-width: 600px;
            }

            #t_container td 
            {
                text-align: left;
                vertical-align: top;
            }
            
            #td_actions
            {
                height: 5px;
                border: none;
                text-align: right !important;
            }
            
            #c_actions
            {
                position: relative; 
                width: 100%; 
            }
            
            #c_actions div
            {
                position: absolute; 
                right: 5px;
                top: -10px; 
            }
            
            .t_hp_form
            {
                border-collapse: separate !important;
                border: dotted 1px #888888;
                margin: 10px auto 0px auto;
                width: 100%;
                border-spacing: 2px;
            }
            
            .td_hp_form
            {
                display: none;
            }
            
            .td_hp_form th
            {
                width: 150px;
                white-space: nowrap;
            }
            
            #f_action
            {
                text-align: center !important;
                padding: 15px 0px;
            }           
            
            #delete_selected
            {
                display: none;
            }          
            
            #properties
            {
                width: 250px;
                margin-left: 5px;
            }
                      
            #mac_ip, #mac
            {
                width: 250px;
            }
            
            #p_locked
            {
                width: 60px;
            }
            
            #service_ip, #service
            {
                width: 250px;
            }
            
            #protocol, #port
            {
                width: 60px;
            }
            
            #search_results 
            {
                width: 500px;
                position: relative;
                height: 8px;
                color: #D8000C;
            }
            
            #search_results div
            {
                position: absolute;
                top: 1px;
            }         
                  
            #av_info 
            {
                width: 85%;
                margin: 10px auto;
            }   
        
        </style>
        
        <script type='text/javascript'>
                
            <?php
            if ($is_ext_ctx == FALSE)
            {
                ?>
                            
                /****************************************************
                 ****************** Form functions ******************
                 ****************************************************/                
                
                //Generic functions 
                                
                function show_form(p_data)
                {  
                    //Hide all forms
                    var form_container_class = '.td_hp_form';
                    
                    $(form_container_class).hide();
                    
                    //Clear all inputs
                    clear_all_inputs();
                    
                    if (typeof(p_data) != 'undefined' && p_data.p_id != '')
                    {
                        //Set current form and property
                        var form_type = '';
                                              
                        switch(p_data.p_id)
                        {
                            //Services
                            case 40:
                                form_type = 2;
                            break;
                            
                            //Macs
                            case 50:
                                form_type = 3;
                            break;
                            
                            //Software
                            case 60:
                                form_type = 4;
                            break;
                            
                            //Host properties
                            default:
                                form_type = 1;
                            break;
                        }

                        //Set current form and property 
                        $('#current_form').val(form_type);

                        //Show current form
                        var form_container_id = '#td_hp_form_'+form_type;
                        $(form_container_id).show();
                        
                        var form_button_pad   = '#td_hp_save';
                        $(form_button_pad).show();
                        
                        //Change current property
                        $('#properties').val(p_data.p_id);
                        
                        if (typeof(p_data.p_value) == 'object')
                        {
                            //Fill form with data

                            fill_form(p_data);
                        }
                    }
                }
                
                
                //Reset property form
                function clear_all_inputs()
                {
                    $('#host_form').get(0).reset();
                    
                    $('#search_results div').empty();              
                }
                
                
                //Fill form with property data
                function fill_form(form_data)
                {
                    switch(form_data.p_id)
                    {
                        //Services
                        case 40:
                            $('#service_ip').val(form_data.p_value.ip);
                            $('#port').val(form_data.p_value.port);
                            $('#service').val(form_data.p_value.service);
                            $('#protocol').val(form_data.p_value.protocol);
                            
                            if (form_data.p_value.nagios == "1")
                            {
                               $('#nagios').attr('checked', 'checked');
                            }
                        break;
                        
                        //Macs
                        case 50:                            
                            $('#mac_ip').val(form_data.p_value.ip);
                            $('#mac').val(form_data.p_value.mac);
                        break;
                        
                        //Software
                        case 60:
                            $('#s_name').val(form_data.p_value.cpe);
                            $('#cpe').val(form_data.p_value.cpe);                         
                        break;
                        
                        //Host properties
                        default:
                            
                            var hp_value = form_data.p_value.hp_value;
                            
                            //Special case: Users logged
                            if (form_data.p_id == 8)
                            {
                                var u_value = hp_value.split('|');
                                
                                var hp_value = u_value[0];
                                
                                if (u_value[1] != '')
                                {
                                    hp_value += '@'+u_value[1];
                                }
                            }
                            
                            $('#p_value').val(hp_value);
                            

                            if (form_data.p_value.source_id == "1")
                            {
                                $('#p_locked').val(1);
                            }
                            else
                            {
                                $('#p_locked').val(0);
                            }
                        break;
                    }
                }            
                
                
                function bind_form_actions()
                {
                    //Sofware
                    bind_software_actions();
                    
                    //Service
                    bind_service_actions();
                                                  
                    //Add properties
                    
                    $('#properties').on('change', function() {
                                        
                        var p_data = {
                            "p_id"    : parseInt($('#properties').val()),
                            "host_id" : "<?php echo $id?>"
                        };
                        
                        show_form(p_data);
                    });
                    
                    //Delete properties
                    $('#save').on('click', function() {

                        save_property();
                    });
                    
                    //Delete properties
                    $('#delete_selected').on('click', function() {

                        delete_properties();
                    });
                }
                
                
                //Software functions
                
                function bind_software_actions()
                {                                    
                    $("#s_name").autocomplete('search_cpe.php', {
                        minChars: 0,
                        width: 400,
                        matchContains: false,
                        multiple: false,
                        autoFill: false,
                        mustMatch: true,
                        scroll: true,
                        scrollHeight: 150,
                        formatItem: function(row, i, max, value) {
                            return (value.split('###'))[1];
                        },
                        formatResult: function(data, value) {
                            return (value.split('###'))[1];
                        }
                    }).result(function(event, item) {
                        
                        if (typeof(item) != 'undefined' && item != null)
                        {
                            $("#cpe").val((item[0].split('###'))[0]);
                            
                            $('#search_results div').empty();                            
                        }
                        else
                        {
                            $("#cpe").val('');                            
                            
                            var s_cpe_not_found = '<span class="small"><?php echo _('Error! Software CPE not found.  Please, you must use a registered Software CPE')?></span>';
                                            
                            $('#search_results div').html(s_cpe_not_found);                                        
                        }                     
                    });
                }
                
                
                //Services functions
                
                var ports = new Array();
                
                function fill_service()
                {
                    var port     = $('#port').val();
                    var protocol = $('#protocol').val();
                    
                    var key =  port + ' - ' + protocol;
                    
                    //Reset service
                    $('#service').val('');
                    
                    if(typeof(ports) == 'object')
                    {
                        if (typeof(ports[key]) !== 'undefined')
                        {
                            $('#service').val(ports[key]);
                        }
                    }
                }
                
                
                function add_new_service()
                {
                    var token = Token.get_token("host_form");
                    
                    var port     = $('#port').val();
                    var protocol = $('#protocol').val();
                    var service  = $('#service').val();
                      
                    //AJAX data
                    var s_data = {
                            "action"   : "add_port",
                            "ctx"      : "<?php echo $ctx?>",
                            "port"     : port,
                            "protocol" : protocol,
                            "service"  : service,
                            "token"    : token
                    };
                    
                    $.ajax({
                        type: "POST",
                        url: 'host_actions.php',
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

                            if (typeof(data) != 'undefined' && data != null && data.status == 'OK')
                            {
                                
                                var s_key  = port + ' - ' + protocol;

                                ports[s_key] = service;
                            }
                        }
                    });
                }
                
                
                function bind_service_actions()
                {
                    <?php
                    foreach($ports as $k => $v) 
                    {
                        echo "ports['$k'] = '$v';\n";
                    }
                    ?> 
            
                    $('#protocol').on('change', function() {
                    
                        fill_service();
                    });
                    
                    $('#port').on('keyup', function() {
                        
                        fill_service();
                    });
                }
                
                
                //Property functions
                
                function delete_properties()
                {                 
                    if (!confirm("<?php echo  Util::js_entities(_('Are you sure to delete this properties'))?>?"))
                    {
                        return false;
                    }

                    //Getting properties to delete
                    var root = $(layer).dynatree("getRoot");
                            
                    var nodes = $.map(root.tree.getSelectedNodes(), function(node){
                        return node.data.filters;
                    }); 
                    
                    var properties = null;
                    
                    if (typeof(nodes) == 'object')
                    {
                        properties = Base64.encode(JSON.stringify(nodes));
                    }
                    
                    var token = Token.get_token("host_form");
                    
                    //AJAX data
                    var p_data = {
                            "action"     : "delete_properties",
                            "host_id"    : "<?php echo $id?>",
                            "properties" : properties,
                            "token"      : token
                    };  

                    
                    $.ajax({
                        type: "POST",
                        url: 'host_actions.php',
                        data: p_data,
                        dataType: 'json',
                        beforeSend: function(xhr){
                            
                            $('#av_info').html('');
                            
                            show_loading_box('host_container', '<?php echo _('Deleting properties')?>...', '');
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
                                                
                            var config_nt = { content: av_messages['unknown_error'],
                                options: {
                                    type:'nf_error',
                                    cancel_button: false
                                },
                                style: 'width: 100%; margin: 10px auto; padding: 5px 0px; font-size: 12px; text-align: left;'
                            };
                            
                            var nt = new Notification('nt_error', config_nt);

                            $('#av_info').html(nt.show());
                            
                            window.scrollTo(0,0);
                        },
                        success: function(data){
                            
                            hide_loading_box();

                            //Check expired session
                            var session = new Session(data, '');
                            
                            if (session.check_session_expired() == true)
                            {
                                session.redirect();
                                
                                return;
                            } 

                            var cnd_1  = (typeof(data) == 'undefined' || data == null);
                            var cnd_2  = (typeof(data) != 'undefined' && data != null && data.status != 'OK');
                                                
                            if (!cnd_1 && !cnd_2)
                            {                            
                                //Hide all forms
                                var form_container_class = '.td_hp_form';
                                
                                $(form_container_class).hide();
                                
                                //Clear all inputs
                                clear_all_inputs();
                                
                                //Hide delete button
                                $('#delete_selected').hide();
                                
                                var config_nt = { 
                                    content: data.data, 
                                    options: {
                                        type:'nf_success',
                                        cancel_button: false
                                    },
                                    style: 'width: 100%; margin: 10px auto; padding: 5px 0px; font-size: 12px; text-align: left;'
                                };
                            
                                var nt = new Notification('nt_success', config_nt);
                                                                
                                $('#av_info').html(nt.show());
                                                                
                                setTimeout("$('#nt_success').fadeOut(4000);", 10000);
                                
                                window.scrollTo(0,0);

                                if(typeof(top.frames['main'].force_reload) != 'undefined')
                                {
                                    top.frames['main'].force_reload = 'info';
                                }
                            }
                            else
                            {
                                var config_nt = { 
                                    content: data.data, 
                                    options: {
                                        type:'nf_error',
                                        cancel_button: false
                                    },
                                    style: 'width: 100%; margin: 10px auto; padding: 5px 0px; font-size: 12px; text-align: left;'
                                };
                            
                                var nt = new Notification('nt_error', config_nt);
                                
                                window.scrollTo(0,0);  
                                
                                $('#av_info').html(nt.show());
                            }
                            
                            //Reload tree
                            if (typeof(data) != 'undefined' && typeof(data.reload_tree) != 'undefined' && data.reload_tree == true)
                            {
                                load_tree();
                            }
                        }
                    });
                }
                
                function save_property()
                {
                    var token     = Token.get_token("host_form");
                    var form_data = $('#host_form').serialize();
                    
                    //AJAX data
                    var p_data = "action=save_property&host_id=<?php echo $id?>&"+form_data+"&token="+token;
                    
                    $.ajax({
                        type: "POST",
                        url: 'host_actions.php',
                        data: p_data,
                        dataType: 'json',
                        beforeSend: function(xhr){
                            
                            $('#av_info').html('');
                            
                            show_loading_box('host_container', '<?php echo _('Saving property')?>...', '');
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
                                                
                            var config_nt = { content: av_messages['unknown_error'],
                                options: {
                                    type:'nf_error',
                                    cancel_button: false
                                },
                                style: 'width: 100%; margin: 10px auto; padding: 5px 0px; font-size: 12px; text-align: left;'
                            };
                            
                            var nt = new Notification('nt_error', config_nt);
                                                            
                            $('#av_info').html(nt.show());
                            
                            window.scrollTo(0,0);
                        },
                        success: function(data){
                            
                            hide_loading_box();

                            //Check expired session
                            var session = new Session(data, '');
                            
                            if (session.check_session_expired() == true)
                            {
                                session.redirect();
                                
                                return;
                            } 

                            var cnd_1  = (typeof(data) == 'undefined' || data == null);
                            var cnd_2  = (typeof(data) != 'undefined' && data != null && data.status != 'OK');
                                                
                            if (!cnd_1 && !cnd_2)
                            {
                                //Special case: Add unregistered service automatically
                                var form_type = $('#current_form').val();

                                if (form_type == "2")
                                {
                                    add_new_service();
                                }
                                
                                //Hide all forms
                                var form_container_class = '.td_hp_form';
                                
                                $(form_container_class).hide();
                                
                                //Clear all inputs
                                clear_all_inputs();
                                
                                //Hide delete button
                                $('#delete_selected').hide();
                                
                                var config_nt = { 
                                    content: data.data, 
                                    options: {
                                        type:'nf_success',
                                        cancel_button: false
                                    },
                                    style: 'width: 100%; margin: 10px auto; padding: 5px 0px; font-size: 12px; text-align: left;'
                                };
                            
                                var nt = new Notification('nt_success', config_nt);
                                                                
                                $('#av_info').html(nt.show());
                                                                
                                setTimeout("$('#nt_success').fadeOut(4000);", 10000);
                                
                                window.scrollTo(0,0); 
                                
                                load_tree();

                                if(typeof(top.frames['main'].force_reload) != 'undefined')
                                {
                                    top.frames['main'].force_reload = 'info';
                                }
                            }
                            else
                            {
                                var config_nt = { 
                                    content: data.data, 
                                    options: {
                                        type:'nf_error',
                                        cancel_button: false
                                    },
                                    style: 'width: 100%; margin: 10px auto; padding: 5px 0px; font-size: 12px; text-align: left;'
                                };
                            
                                var nt = new Notification('nt_error', config_nt);
                                
                                window.scrollTo(0,0);  
                                
                                $('#av_info').html(nt.show());
                            }
                        }
                    }); 
                }
                <?php
            }
            ?>      
            
            
            /****************************************************
             ****************** Tree functions ******************
             ****************************************************/
            
            var reloading_text  = '<div class="reload"><img src="../pixmaps/theme/loading2.gif" border="0" align="absmiddle"/>'+messages[6]+'</div>';
            
            var layer    = null;
            var nodetree = null;
            var i        =  1;
                     
            function load_tree()
            {
                if (nodetree != null)
                {
                    nodetree.removeChildren();
                    $(layer).remove();
                }
                
                layer = '#srctree1_'+i;
                
                $('#tree_container').append('<div id="srctree1_'+i+'" style="width:100%"></div>');
                
                $(layer).html(reloading_text);
                                             
                //Dynatree

                $(layer).dynatree({
                    initAjax:{
                        url: '../av_tree.php',
                        data: {
                           key: "property_tree",
                           filters: {host_id: "<?php echo $id?>"},
                           max_text_length: "50"
                        },
                    },
                    minExpandLevel: 2,
                    clickFolderMode: 1,
                    <?php
                    if ($is_ext_ctx == FALSE) 
                    {                   
                        ?>
                        checkbox: true,
                        <?php
                    }
                    ?>
                    cookieId: "dynatree_hp",
                    <?php
                    if ($is_ext_ctx == FALSE)
                    {
                        ?> 
                        onClick: function(node, event) 
                        {
                           //No show property form when checkbox is clicked 
                           if (node.getEventTargetType(event) != 'checkbox')
                           {
                                var p_data = node.data.filters;

                                //console.log(p_data);
                                
                                show_form(p_data);
                           }
                        },
                        <?php 
                    } 
                    ?>          
                    onSelect: function(select, node) {
                        
                        // Get a list of all selected nodes, and convert to a key array:
                        var selKeys = $.map(node.tree.getSelectedNodes(), function(node){
                            return node.data.key;
                        });
                        
                        var form_container_class = '.td_hp_form';
                        
                        if (selKeys.length > 0)
                        {
                            $("#delete_selected").show();
                            
                            //Hide form
                            $(form_container_class).hide();
                        }
                        else
                        {
                            $("#delete_selected").hide();
                            
                            //Show last form
                            var form_container_id = '#td_hp_form_'+ $('#current_form').val();                                                        
                            $(form_container_id).show();
                            
                            var form_button_pad   = '#td_hp_save';
                            $(form_button_pad).show();
                        }
                    }   
                });

                
                nodetree = $(layer).dynatree("getRoot");
                        
                i = i + 1;
            } 
                      
                        
            $(document).ready(function(){
               
                /****************************************************
                ********************** Tooltips ********************
                ****************************************************/
                
    			$(".info").tipTip({maxWidth: '380px'});	
                
                /**************************************************
                *********************** Tree **********************
                ***************************************************/
                
                load_tree();            
                
                /**************************************************
                ********************** Forms **********************
                ***************************************************/
                
                <?php
                if ($is_ext_ctx == FALSE)
                {
                    ?>              
                                        
                    //Bind actions
                    bind_form_actions();
                    <?php
                    
                    
                    if (Session::menu_perms('environment-menu', 'ToolsScan'))
                    {
                        ?>
                        //Local scan                 
                        $('#local_scan').click(function() {                                            
                            
                            top.frames['main'].local_scan();                           
                                                                                                
                        });
                        <?php
                    }                    
                }
                ?>
            });

        </script>
    </head>
    
    <body>
        
        <div id="av_info">
            <?php
            if ($is_ext_ctx == TRUE)
            {
                $config_nt = array(
                    'content' => _('The properties of this asset can only be modified at the USM:').' <strong>'.$external_ctxs[$ctx].'</strong>',
                    'options' => array (
                        'type'          => 'nf_warning',
                        'cancel_button' => TRUE
                    ),
                    'style'   => 'width: 80%; margin: auto; text-align:center;'
                ); 
                
                $nt = new Notification('nt_1', $config_nt);
                $nt->show();             
            }
            ?>
        </div>
        
        <div id="host_container">

            <table id="t_container">
                
                <?php
                $title = _('Inventory');
                
                if ($is_ext_ctx == FALSE && Session::menu_perms('environment-menu', 'ToolsScan'))
                {
                    $t_local_scan  = _('The scan will run in frameworkd machine');
                    $ls_title      = _('Local Scan now');
                    
                    $title .= ' [ <a class="info" id ="local_scan" title="'.$t_local_scan.'">'.$ls_title.'</a> ]'; 
                }                   
                ?>
                
                <!-- Title -->
                <thead>
                    <tr>
                        <th class="headerpr"><?php echo $title?></th>
                    </tr>
                </thead>
                
                <tbody>
                
                    <!-- Tree -->
                    <tr>
                        <td>
                            <div id="tree_container"></div>
                        </td>
                    </tr>                    
                
                    <?php
                    if ($is_ext_ctx == FALSE) 
                    {
                        ?>
                        <!-- Property form -->
                        <tr>
                            <td id="td_actions">
                                <div id="c_actions">
                                    <div>
                                       <input type="button" class="small av_b_secondary" id="delete_selected" value="<?php echo _('Delete Selected')?>"/>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    
                        <tr>
                            <td>
                                <form method="POST" name="host_form" id="host_form">
                                    
                                    <input type="hidden" name="current_form" id="current_form"/>
                                    
                                    <table>
                                        
                                        <!--  Adding new property -->
                                        <tr>
                                            <td>
                                                <label id='l_properties' for="properties"><?php echo _('Add new property')?>:</label>
                                                
                                                <select name="properties" id="properties">
                                                    <option value='0'>-- <?php echo _('Select a property type')?> --</option>
                                                    <?php
                                                    foreach ($properties as $p_id => $p_data)
                                                    {
                                                        ?>
                                                        <option value="<?php echo $p_id?>"><?php echo $p_data['description']?></option>
                                                        <?php
                                                    }
                                                    ?>
                                                    <option value="50"><?php echo _("MAC Address") ?></option>
                                                    <option value="40"><?php echo _("Service") ?></option>
                                                    <option value="60"><?php echo _("Software") ?></option>
                                                </select>
                                            </td>
                                        </tr>
                                        
                                        <!--  Standard Host Properties Form -->
                                        <tr class="td_hp_form" id="td_hp_form_1">
                                            <td>
                                                <table class="t_hp_form">
                                                    <tr>
                                                        <th><label for="p_value"><?php echo _('Value')?></label></th>
                                                        <td>
                                                            <input type="text" name="p_value" id="p_value"/>
                                                        </td>    
                                                    </tr>
                                                    <tr>
                                                        <th><label for="p_locked"><?php echo _('Property is locked')?></label></th>
                                                        <td>
                                                            <select id="p_locked" name="p_locked">
                                                                <option value="0"><?php echo _('No')?></option>
                                                                <option value="1"><?php echo _('Yes')?></option>
                                                            </select>   
                                                        </td> 
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                        
                                        <!--  Service Form -->
                                        <tr class="td_hp_form" id="td_hp_form_2">
                                            <td>
                                                <table class="t_hp_form">
                                                    <tr>
                                                        <th><label for="service_ip"><?php echo _('IP')?></label></th>
                                                        <td>
                                                        <?php
                                                        if (count($ips) > 1) 
                                                        {
                                                            ?>
                                                            <select name="service_ip" id="service_ip">
                                                            <?php 
                                                            foreach ($ips as $ip_aux) 
                                                            { 
                                                                ?>
                                                                <option value="<?php echo $ip_aux?>"><?php echo $ip_aux?></option>
                                                                <?php 
                                                            } 
                                                            ?>
                                                            </select>
                                                            <?php
                                                        }
                                                        else
                                                        {
                                                            ?>
                                                            <input type="hidden" name="service_ip" id="service_ip" value="<?php echo $ips[0]?>"/>
                                                            <span><?php echo $ips[0]?></span>
                                                            <?php
                                                        }
                                                        ?>   
                                                        </td>    
                                                    </tr>

                                                    <tr>
                                                        <th><label for="port"><?php echo _('Port Number')?></label></th>
                                                        <td>
                                                            <input type="text" name="port" id="port"/>
                                                        </td> 
                                                    </tr>
                                                    
                                                    <tr>
                                                        <th><label for="protocol"><?php echo _('Protocol')?></label></th>
                                                        <td>
                                                            <select id="protocol" name="protocol">
                                                                <option value="tcp">TCP</option>
                                                                <option value="udp">UDP</option>
                                                            </select>   
                                                        </td> 
                                                    </tr>
                                                    
                                                    <tr>
                                                        <th><label for="service"><?php echo _('Service')?></label></th>
                                                        <td>
                                                            <input type="text" name="service" id="service"/>   
                                                        </td> 
                                                    </tr>

                                                    <tr>
                                                        <th><label for="nagios"><?php echo _('Nagios')?></label></th>
                                                        <td>
                                                            <input type='checkbox' name="nagios" id="nagios" value="1"/>
                                                        </td> 
                                                    </tr>
                                                    
                                                </table>
                                            </td>
                                        </tr>
                                        
                                        <!--  MAC Form -->
                                        <tr class="td_hp_form" id="td_hp_form_3">
                                            <td>
                                                <table class="t_hp_form">
                                                    <tr>
                                                        <th><label for="mac_ip"><?php echo _('IP')?></label></th>
                                                        <td>
                                                        <?php
                                                        if (count($ips) > 1) 
                                                        {
                                                            ?>
                                                            <select name="mac_ip" id="mac_ip">
                                                            <?php 
                                                            foreach ($ips as $ip_aux) 
                                                            { 
                                                                ?>
                                                                <option value="<?php echo $ip_aux?>"><?php echo $ip_aux?></option>
                                                                <?php 
                                                            } 
                                                            ?>
                                                            </select>
                                                            <?php
                                                        }
                                                        else
                                                        {
                                                            ?>
                                                            <input type="hidden" name="mac_ip" id="mac_ip" value="<?php echo $ips[0]?>"/>
                                                            <span><?php echo $ips[0]?></span>
                                                            <?php
                                                        }
                                                        ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th><label for="mac"><?php echo _('MAC')?></label></th>
                                                        <td>
                                                            <input type="text" name="mac" id="mac"/>
                                                        </td> 
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>

                                        <!--  Software Form -->
                                        <tr class="td_hp_form" id="td_hp_form_4">
                                            <td>
                                                <table class="t_hp_form">
                                                    <tr>
                                                        <th><label for="cpe"><?php echo _('Software CPE')?></label></th>
                                                        <td>
                                                            <input type="text" name="s_name" id="s_name"/>
                                                            <input type="hidden" name="cpe" id="cpe"/>
                                                            
                                                            <div id='search_results'><div></div></div>                                                            
                                                        </td> 
                                                    </tr>                                                  
                                                </table>
                                            </td>
                                        </tr>
                                        
                                        <!--  Button pad -->
                                        <tr class="td_hp_form" id="td_hp_save">
                                            <td id="f_action">  
                                                <input type="button" id="save" name="save" class="small" value="<?php echo _("Save")?>"/>
                                            </td>
                                        </tr>
                                    </table>

                                </form>   
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
                
            </table>
        </div>
    
    </body>
</html>