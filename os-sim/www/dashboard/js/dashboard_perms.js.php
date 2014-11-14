<?php
header("Content-type: text/javascript");

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

require_once('av_init.php') 
?>

function change_visibility(item)
{
    item = item.split(';');
    //tab_hidden
    var user   = item[0];
    var id     = item[1]; 
    var ui     = $('#'+user+'\\#'+id);
    var option = ($(ui).hasClass('tab_hidden')) ? 1 : 0;

    var ctoken = Token.get_token("dashboard_perms_ajax");
    $.ajax(
    {
        data:  {"action": "change_visibility", "data": {"panel": id, "user": user}},
        type: "POST",
        url: "perms_ajax.php?&token="+ctoken,
        dataType: "json",
        success: function(data)
        {
            if(data.error)
            {
                show_notification(data.msg, 'nf_error');
            } 
            else
            {

                if(option == 1)
                {
                    $(ui).removeClass('tab_hidden');
                } 
                else 
                {
                    $(ui).addClass('tab_hidden');
                }                       
            }
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) 
        {
            //Checking expired session
    		var session = new Session(XMLHttpRequest, '');
            if (session.check_session_expired() == true)
            {
                session.redirect();
                return;
            }
            
            show_notification(textStatus, 'nf_error');
        }

    });
    
    return false;       
}

function delete_tab(params)
{
    if(confirm('<?php echo  Util::js_entities(_("This tab and all its widgets will be removed. This action can not be undone. Are you sure you want to continue?"))?>'))
    {
        var item   = params.split(';');
        var user   = item[0];
        var id     = item[1]; 

        var ctoken = Token.get_token("dashboard_perms_ajax");
        $.ajax(
        {
            data:  {"action": "delete_tab", "data": {"panel": id, "user": user}},
            type: "POST",
            url: "perms_ajax.php?&token="+ctoken,
            dataType: "json",
            success: function(data)
            {
                if(data.error)
                {
                    show_notification(data.msg, 'nf_error');                        
                } 
                else
                {
                    var tab_id = "#"+user+"\\#"+id;

                    $(tab_id).remove();                    
                }
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) 
            {
                //Checking expired session
        		var session = new Session(XMLHttpRequest, '');
                if (session.check_session_expired() == true)
                {
                    session.redirect();
                    return;
                }
                
                show_notification(textStatus, 'nf_error');
            }
        });
    }

    return false;       
}


function copy_all(params)
{
    if(confirm('<?php echo  Util::js_entities(_('Are you sure you want to copy this tab to all the users?'))?>'))
    {
        var item   = params.split(';');
        var user   = item[0];
        var id     = item[1]; 

        var ctoken = Token.get_token("dashboard_perms_ajax");
        $.ajax(
        {
            data:  {"action": "clone_tab_all", "data": {"panel": id, "user": user}},
            type: "POST",
            url: "perms_ajax.php?&token="+ctoken,
            dataType: "json",
            success: function(data)
            {
                if(data.error)
                {
                    if(data.msg == 'unique_user')
                    {
                        show_notification("<?php echo _('There are no users available to copy this tab. (Only the own user is available)') ?>", 'nf_info');
                    }
                    else
                    {
                        show_notification(data.msg, 'nf_error');
                    }
                    
                } 
                else
                {
                    document.location.href='index.php';                           
                }
            },
            error: function(XMLHttpRequest, textStatus, errorThrown) 
            {
                //Checking expired session
        		var session = new Session(XMLHttpRequest, '');
                if (session.check_session_expired() == true)
                {
                    session.redirect();
                    return;
                }
                show_notification(textStatus, 'nf_error');
            }
        });
    
    }
    
    return false;       
}

function load_tree()
{
    $('#tree').dynatree(
    {
        initAjax:
        { 
            url: "/ossim/tree.php?key=all_users" ,
            success: function(node) 
            {
                node.visit(function(n){
                    n.select(true);
                });
            }
            
        },
        clickFolderMode: 1,
        selectMode: 2,
        checkbox: true,
        onActivate: function(dtnode) 
        {
            var key = dtnode.data.key;

            if(dtnode.isSelected())
            {
                dtnode.select(false);
            }
            else
            {
                dtnode.select(true);
            }
                
            dtnode.deactivate();

        },
        onSelect: function(flag,node)
        {
            var key = node.data.key;
            if (key.match(/^u_/)) 
            {   
                key = "#col_" + key.replace("u_","");
                if(flag)
                {
                    $(key).fadeIn('slow');
                }
                else
                {
                    $(key).fadeOut('slow');
                }

            } 
        }
    });
}

function show_notification(msg, type)
{
    var config_nt = 
    { 
        content: msg, 
        options: 
        {
            type: type,
            cancel_button: false
        },
        style: 'display:none; text-align:center;margin: 0 auto;'
    };
    
    nt = new Notification('nt_1',config_nt);
    
    $('#container_info').html(nt.show());

    nt.fade_in(1000);
    
    setTimeout("nt.fade_out(2000);", 5000);
}


function draw_carrousel(n)
{
    $("#jCarouselLite").jCarouselLite(
    {
        btnNext: "#next",
        btnPrev: "#prev",
        speed: 200,
        easing: "",
        visible: n,
        scroll: 1,
        circular: false
    });     
}

function toggle_default_tabs(ui, id)
{
    //Tabs boxes
    id = ".tab"+id;
    
    if ($(id).is(':hidden')) 
    {
        $(ui).text("<?php echo Util::js_entities(_('Hide Default Tabs')) ?>");
        $(id).show();
    }
    else
    {
        $(ui).text("<?php echo Util::js_entities(_('Show Default Tabs')) ?>");
        $(id).hide();
    }
    
    return false;
}

function load_contextmenu() 
{
    $('.menuPerms').contextMenu(
    {
        menu: 'myMenuTab',
        leftButton:true
    },
    function(action, el, pos) 
    {                         
        var aux = $(el).attr('id');      
        if(action == "copyuser")
        {
            var url = "show_users.php?data="+aux;
            var title = "<?php echo _("Select an User") ?>";
        
            if (typeof(CB_show) == "undefined") 
            {
                document.location.href = url;
            } 
            else 
            {
                CB_show(false,title,url,265,230);
            }
            
        }
        <?php 
        if (Session::is_pro()) 
        {
        ?>
            else if(action == "copyentity")
            {
                var url = "show_entities.php?data="+aux;
                var title = "<?php echo _("Select an Entity") ?>";
                if (typeof(CB_show) == "undefined")
                {
                    document.location.href = url;
                } 
                else 
                {
                    CB_show(false,title,url,265,230);
                }
            }
        <?php 
        } 
        ?>

        else if(action == "copyall")
        {
            if (typeof(copy_all) == "function") 
            {
                copy_all(aux);
            }
            return false;
        
        } 
        else if(action == "delete")
        {
            if (typeof(delete_tab) == "function") 
            {
                delete_tab(aux);
            }
            return false;
            
        } 
        else if(action == "toggle")
        {
            if (typeof(change_visibility) == "function") 
            {
                change_visibility(aux);
            }
            return false;
        }
    });
    
    $('.menuPermsProtected').contextMenu(
    {
        menu: 'myMenuTabProtected',
        leftButton:true
    },
    function(action, el, pos) 
    {
            
        var aux = $(el).attr('id');
        if(action == "copyuser")
        {
            var url = "show_users.php?data="+aux;
            var title = "<?php echo _("Select an User") ?>";
        
            if (typeof(CB_show) == "undefined")
            {
                document.location.href = url;
            } 
            else 
            {
                CB_show(false,title,url,250,230);
            }
            
        } 
        <?php 
        if (Session::is_pro()) 
        {
        ?>
        else if(action == "copyentity")
        {
            var url = "show_entities.php?data="+aux;
            var title = "<?php echo _("Select an Entity") ?>";
            if (typeof(CB_show) == "undefined")
            {
                document.location.href = url;
            }
            else 
            {
                CB_show(false,title,url,250,230);
            }

        } 
        <?php 
        } 
        ?>
        else if(action == "copyall")
        {
            if (typeof(copy_all) == "function")
            {
                copy_all(aux);
            }

            return false;
        } 
        else if(action == "toggle")
        {
            if (typeof(change_visibility) == "function")
            {
                change_visibility(aux);
            }
            return false;
        }

    });

    <?php 
    if(!Session::is_pro()) 
    { 
    ?>
    
        $('#myMenuTabProtected').disableContextMenuItems('#copyentity');
        $('#myMenuTab').disableContextMenuItems('#copyentity');
    
    <?php 
    }
    ?>
    
}


function load_dashboard_perms_scripts()
{
    load_tree();
    
    $(document).on('click', '.vis_change', function(event) 
    {             
        change_visibility(this);
    });

    $("#btnSelectAll").on("click", function()
    {
        $('#visibility').val(1);
        $('#tree').dynatree("getRoot").visit(function(node)
        {
            node.select(true);
        });

        return false;
    });

    $("#btnDeselectAll").on("click", function()
    {
        $('#visibility').val(0);
        $('#tree').dynatree("getRoot").visit(function(node)
        {
            node.select(false);
        });

        return false;
    });
    
    var inicio;

    $(".tab_list").sortable(
    {
        items: '.tab_unprotected',
        tolerance: 'pointer',
        connectWith: '.tab_list',
        start: function(event, ui) 
        {
            inicio = event.currentTarget;
        },
        stop: function(event, ui)
        {                   
            if (ui.item.context.parentElement != inicio)
            {
                var tab = ui.item;
                var w   = $(tab).clone();

                $(inicio).prepend(w);
                
                var from  = $(inicio).attr('id').replace("col_","");
                var to    = $(ui.item.context.parentElement).attr('id').replace("col_","");                     
                var panel = $(tab).attr('tab_id');
                
                var ctoken = Token.get_token("dashboard_perms_ajax");
                $.ajax(
                {
                    data:  {"action": "clone_tab", "data":  {"from": from, "to": to, "panel": panel}},
                    type: "POST",
                    url: "perms_ajax.php?&token="+ctoken,
                    dataType: "json",
                    success: function(data)
                    {
                        if(data.error)
                        {                                       
                            show_notification(data.msg,'nf_error'); 
                            $(tab).remove();
                        } 
                        else
                        {
                            data   = data.data;

                            var id = data.user+"#"+data.id;
                            
                            $(tab).attr('id', id);
                            $(tab).attr('tab_id', data.id);    

                            $(tab).find('.menuPerms').attr('id', data.user+";"+data.id);
                            
                            var title = data.title;  
                                                  
                            title = (title.length > 20) ? title.substring(0, 17) + "..." : title;
                            $(tab).find('.db_perm_tab_title').html(title);
                        }

                        load_contextmenu();
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown) 
                    {
                        //Checking expired session
                		var session = new Session(XMLHttpRequest, '');
                        if (session.check_session_expired() == true)
                        {
                            session.redirect();
                            return;
                        }
            
                        show_notification(textStatus,'nf_error');  
                         
                        $(tab).remove();
                        
                        load_contextmenu();
                    }
                });
            }
        }

    }).disableSelection();
    
                
    
    $('#button_tree').on("click", function()
    {               
        $('#tree_container').slideToggle(300);          
    });
            
    $('.tooltip').tipTip();
    
    draw_carrousel(5);
    
    load_contextmenu();
}
