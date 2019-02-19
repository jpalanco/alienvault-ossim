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
require_once 'av_init.php';
?>


//GreyBox closing function  
function GB_onhide() 
{
    delete_wizard_session();
}

function GB_onclose() 
{ 
    delete_wizard_session();    
    load_tab(panel_id);
}

function db_fullscreen()
{
    window.open ("index.php?fullscreen=1&panel_id="+panel_id, "mywindow","status=0, toolbar=0, fullscreen=1, resizable=1");  
}
       


function load_tab(id)
{
	if(httpR)
    {
        // Aborting previous request 
        httpR.abort();
    }
    window.scrollTo(0,0);

    $('#container').empty();         
    $('#db_unsave').empty();

    var style = "width: 200px; left: 50%; position: absolute; margin-left: -100px;"
    show_loading_box('container', "<?php echo _('Loading Dashboard') ?>", style);

    $('#db_tab_'+panel_id).removeClass('active');
    $('#db_tab_'+panel_id).find('a.db_tab_text').removeClass('active');
    
    $('#db_tab_'+id).addClass('active');
    $('#db_tab_'+id).find('a.db_tab_text').addClass('active');
    
    panel_id = id;

    if(!fullscreen)
    {
        draw_selected_layer(true);
    }
    else
    {
        load_dashboard_widgets();
    }
    

    return false;
}


function draw_selected_layer(load_widgets)
{
    var pos_init  = $('#db_tab_'+panel_id).position();
        pos_init  = (typeof(pos_init) == 'undefined' || pos_init == null ) ? 0 : pos_init.left; 
    
    var width     = $('#db_tab_'+panel_id).outerWidth();
     
    $('#db_tab_blob').animate(
    {
        left  : pos_init,
        width : width
    },
    {
        duration : 400,
        easing   : 'easeOutExpo',
        queue    : false,
        complete : function()
        {
            if (load_widgets)
            {
                load_dashboard_widgets();
            }
        }
    }); 
}


function load_dashboard_widgets(id)
{

    if (typeof(id) == 'undefined')
    {
        id = panel_id;
    }

    var nstyle = 'width:100%;padding:1px 0;text-align:center;';
    
    var ctoken = Token.get_token("dashboard_ajax");
    $.ajax(
    {
        data: {"action": "load_tab", "data": {"id": id}},
        type: "POST",
        url: "src/dashboard_ajax.php?&token="+ctoken, 
        beforeSend: function(data)
        {
		    //Saving the new request.
		    httpR = data;
        },
        dataType: "json",
        success: function(data)
        { 

            if(typeof(data) == 'undefined')
            {
                hide_loading_box();
                show_notification(unknown_error, 'nf_error', 'db_notif_container', 0, true, nstyle);
                return false;
            }

            if(data.error)
            {
                hide_loading_box();
                show_notification(data.msg, 'nf_error', 'db_notif_container', 5000, true, nstyle);
            } 
            else
            {
                $('#container').empty();

                var tab     = data.data.tab;
                var widgets = data.data.widgets;
                
                    locked  = (tab.locked) ? true : false;
                    layout  = (tab.layout <= 0)? 1 : tab.layout;

                $('#container').data('tab', id);
                var width   = (100.0/layout) + '%';

                load_tab_view();

                for (var i = 0; i < layout; ++i)
                {
                    $('<div>', {  
                        id      : "c"+i,  
                        style   : "width:"+ width +";",
                        "class" : 'column ui-sortable'
                    }).appendTo('#container');
                }

                $.each(widgets, function(col, widgets_col) 
                {
                    $.each(widgets_col, function(fil, w) 
                    {
                        if ( typeof(w) == 'object')
                        {
                            $('#c'+col).AVwidget(
                            {
                                id: w.id,
                                mode: edit_mode,
                                lock: tab.locked,
                                color: w.color,
                                title: w.title,
                                help: w.help,
                                height: w.height,
                                src: w.src
                            });
                        }
                    });
                });

                bind_sortable_columns();

                hide_loading_box();                         
            }
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) 
        {
            var session = new Session(XMLHttpRequest, '');
            if (session.check_session_expired() == true)
            {
                session.redirect();
                return;
            } 
            
            hide_loading_box();
            
            if(errorThrown != 'abort' && errorThrown != '')
            {
            	show_notification(errorThrown, 'nf_error', 'db_notif_container', 0, true, nstyle);
            }
        }
    });

    return false;
}

function bind_sortable_columns()
{
    var sortable_h = $('.column').css('height');
    
    $(".column").sortable(
    {
        connectWith: ".column",
        handle: ".db_w_icon_grabber",
        tolerance  : 'pointer',
        activate: function()
        {
            sortable_h = $('#container').css('height');
        },
        start: function(event, ui) 
        {                       
            $('.db_w_overlay').show(); 
            $('.column').css('min-height', sortable_h);
        },
        stop: function(event, ui) 
        {
            $('.db_w_overlay').hide(); 
            $('.column').css('min-height', '');
            sortable_h = $('.column').css('height');

            if (locked == false && edit_mode == 'edit')
            {
                if(typeof(changes_made) == 'function')
                {
                    changes_made();
                }
            }
        }

    }).disableSelection();
}

function load_tab_view()
{

    if (edit_mode == 'edit')
    {
        load_edit_mode();
    }
    else
    {
        load_view_mode();
    }
}

function load_view_mode()
{
    $('#edit_switch_view').hide();
    $('#menu_config_container').hide();
    $('#locked_edit_tab').hide();
}

function load_edit_mode()
{

    $('#edit_switch_view').show();

    var msg   = "<?php echo _("You are currently in edit mode. Click <a class='switch_view' style='color:#00529B;' href='index.php?edit=0'>here</a> to switch to view mode") ?>";
    var style = 'width:50%;margin:10px auto 0 auto;text-align:center;';
    show_notification(msg, 'nf_info', 'edit_switch_view', 0, true, style);
    
    if (!locked)
    {
        $('#locked_edit_tab').hide();
        $('#menu_config_container').show(); 
        load_slider();
    }
    else
    {
        $('#menu_config_container').hide(); 
        $('#locked_edit_tab').show();
        var msg   = "<?php echo _("This tab is included in the default AlienVault installation and therefore it can not be modified. Please create a new tab or clone one of the existing ones to make changes. You can also hide this tab") ?>";
        var style = 'width:75%;margin:10px auto 0 auto;text-align:center;';
        show_notification(msg, 'nf_warning', 'locked_edit_tab', 0, true, style);
    }

} 


function changes_made()
{
	var msg = "<?php echo _("You have made changes, click <a href='javascript:;' class='switch_view' style='color:#9F6000;' onclick='javascript:saveConfig();'>here</a> to save changes") ?>";
	
	var style = 'width:100%;padding:5px 0;text-align:center;';
    show_notification(msg, 'nf_warning', 'db_unsave', 0, false, style);
}	


function load_slider()
{
    load_slider_info(layout);

    $("#slider_layout").slider(
    {
        animate: false,
        range: "min",
        disabled: false,
        value: layout,
        min:   1,
        max:   8,
        step:  1,
        slide: function(event, ui) 
        {
            $("#span_layout").html(ui.value + " Column" + ((ui.value > 1)? "s" : ""));                      
        },
        stop: function(event, ui) 
        {     
            change_columns(ui.value);
        },
    });  
}

function load_slider_info(id)
{
	$("#slider_layout").slider('value', id);
    $("#span_layout").html(id + " " + ((id > 1)? "<?php echo _('Columns') ?>" : "<?php echo _('Column') ?>")); 
}


/****************************************************************************************************************/
/*																												*/
/*********************************************** WIDGETS FUNTIONS ***********************************************/
/*																												*/
/****************************************************************************************************************/

function delete_widget(id)
{
	if (confirm('<?php echo  Util::js_entities(_("This widget will be removed. This action can not be undone. Are you sure you want to continue?"))?>')) 
    { 	
    	var ctoken = Token.get_token("dashboard_ajax");
		$.ajax(
		{
			data: {"action": "delete_widget", "data":{"id":id} },
			type: "POST",
			url: src_ajax+"?&token="+ctoken,
			dataType: "json",
			success: function(data)
			{
				if(data.error)
				{
					show_notification(data.msg, 'nf_error', 'db_notif', 5000);
				} 
				else
				{
					$("#wid"+id).remove();								
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
                show_notification(errorThrown, 'nf_error', 'db_notif', 5000);
			}
		});
	}
}


//Save the order of the widget of the current panel 
function saveConfig()
{
	var list = "{";
	var index = 0;

	var list  = {};
	var index = 0;
	
	$(".column").each(function(i, j)
	{
		$(this).find(".portlet").each(function(j, z)
		{
			var params = {};

			params["id"] = $(this).attr('id').replace("wid", "");
			params["col"] = i;
			params["fil"] = j;

			list[index] = params;
			index++;

		});	
	});

	var ctoken = Token.get_token("dashboard_ajax");
	$.ajax(
	{
		data:  {"action": "save_widgets", "data": {"panel": panel_id, "widgets": list} },
		type: "POST",
		async: false,
		url: src_ajax+"?&token="+ctoken, 
		dataType: "json",
		success: function(data)
		{ 
			if(data.error)
			{
				show_notification(data.msg, 'nf_error', 'db_notif', 2500);
			}
			else
			{
				$("#db_unsave").find('div').hide();
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
            		  
			show_notification(errorThrown, 'nf_error', 'db_notif', 2500);
		}

	});
	
}

/****************************************************************************************************************/
/*																												*/
/************************************************ TABS FUNTIONS *************************************************/
/*																												*/
/****************************************************************************************************************/

function disable_tab(id)
{
	
	var selector = "#db_tab_" + id + " .db_tab_title a";
	var aux  = $(selector).hasClass('db_tab_text_disabled');	

	if(aux)
	{
		var message  = "<?php echo Util::js_entities(_("The tab is going to be enabled, Are you sure?")) ?>";	
	}
	else
	{
		var message  = "<?php echo Util::js_entities(_("The tab is going to be disabled, Are you sure?")) ?>";
	}
			
	if (confirm(message)) 
	{ 		
		var ctoken = Token.get_token("dashboard_ajax");	
		$.ajax(
		{
			data: {"action": "change_visibility", "data": {"panel": id}},
			type: "POST",
			url: src_ajax+"?&token="+ctoken,
			dataType: "json",
			success: function(data)
			{
				if(data.error)
				{
					show_notification(data.msg, 'nf_error', 'db_notif', 2500);
				} 
				else
				{
					if (aux)
					{
						$(selector).removeClass('db_tab_text_disabled');
					} 
					else 
					{
						$(selector).addClass('db_tab_text_disabled');						
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
                
    			show_notification(errorThrown, 'nf_error', 'db_notif', 2500);
			}
		});
	} 
}
	

function set_default_tab(id)
{
	if (confirm("<?php echo _("You are going to select this tab as default tab, Are you sure?") ?>")) 
	{ 		
		var ctoken = Token.get_token("dashboard_ajax");			
		$.ajax(
		{
			data: {"action": "set_default_tab", "data": {"panel": id}},
			type: "POST",
			url: src_ajax+"?&token="+ctoken,
			dataType: "json",
			success: function(data)
			{
				if(data.error)
				{
					show_notification(data.msg, 'nf_error', 'db_notif', 5000);
				} 
				else
				{
					$("#db_tab_" + id + " .db_tab_title a").removeClass('db_tab_text_disabled');
					default_tab = id;
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
                			
				show_notification(errorThrown, 'nf_error', 'db_notif', 2500);
			}
		});
	}
}
	
function delete_tab(id)
{
	if (confirm('<?php echo  Util::js_entities(_("This tab and all its widgets will be removed. This action can not be undone. Are you sure you want to continue?"))?>')) 
	{ 
		var ctoken = Token.get_token("dashboard_ajax");	
		$.ajax(
		{
			data:  {"action": "delete_tab", "data": {"panel":id}},
			type: "POST",
			url: src_ajax+"?&token="+ctoken,
			dataType: "json",
			success: function(data)
			{
				if(data.error)
				{
					show_notification(data.msg, 'nf_error', 'db_notif', 2500);
				} 
				else
				{	
					$("#db_tab_" + id).remove();	
					
					if (id == default_tab)
					{
						default_tab = 1;
						$("#db_tab_1 .db_tab_title a").removeClass('db_tab_text_disabled');
					}

					if (id == panel_id)
					{
						load_tab(default_tab);
					}
					else
					{
						load_tab(panel_id);
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
            			
				show_notification(errorThrown, 'nf_error', 'db_notif', 2500);
			}
		});
	}
}
	

function add_new_tab(title, layout)
{
	var ctoken = Token.get_token("dashboard_ajax");
	$.ajax(
	{
		data:  {"action": "add_tab", "data": {"title":title, "layout":layout}},
		type: "POST",
		url: src_ajax+"?&token="+ctoken, 
		dataType: "json",
		success: function(data)
		{
			if(data.error)
			{
				show_notification('<?php echo _('Error cloning tab') ?>: '+data.msg, 'nf_error', 'db_notif', 2500);
			} 
			else
			{
				data = data.data;
				
				var new_id    = data.new_id;
				var new_title = data.title;

				document.location.href='index.php?panel_id='+new_id;
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
            
			show_notification(errorThrown, 'nf_error', 'db_notif', 2500);
		}
	});

}

	
function clone_tab(id)
{
	if (confirm('<?php echo  Util::js_entities(_("Are you sure you want to clone this tab?"))?>')) 
	{
		var ctoken = Token.get_token("dashboard_ajax");
		$.ajax(
		{
			data:  {"action": "clone_tab", "data": {"panel":id}},
			type: "POST",
			url: src_ajax+"?&token="+ctoken, 
			dataType: "json",
			async: true,
			success: function(data)
			{ 
				if(data.error)
				{
					show_notification('<?php echo _('Error cloning tab') ?>: '+data.msg, 'nf_error', 'db_notif', 2500);
				} 
				else
				{
					data = data.data;

					var new_id    = data.new_id;
					var new_title = data.title;

					document.location.href='index.php?panel_id='+new_id;
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
            
				show_notification(errorThrown, 'nf_error', 'db_notif', 2500);
			}
		});
	
	}

}


function change_tab_title(id, title_new, title_old)
{
	var ctoken = Token.get_token("dashboard_ajax");
	$.ajax({
		data:  {"action": "change_title_tab", "data": {"panel": id, "title": title_new}},
		type: "POST",
		url: src_ajax+"?&token="+ctoken,
		dataType: "json",
		success: function(data)
		{
			if(data.error)
			{
				show_notification(data.msg, 'nf_error', 'db_notif', 2500);
				$("#db_tab_"+id).find('.db_tab_text').text(title_old);
			} 
			else
			{
				$("#db_tab_"+id).find('.db_tab_text').text(title_new);	
				draw_selected_layer(false);
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
            						
            show_notification(errorThrown, 'nf_error', 'db_notif', 2500);
            $("#db_tab_"+id).find('.db_tab_text').text(title_old);
		}
	});		

}


//Save the order of the tabs after the drag and drop
function saveTabsOrder()
{
	var index = 0;
	var list  = {};
	
	$(".db_tab_list .db_tab_tab").each(function(i, j)
	{
		var id      = $(this).data('id');
		list[index] = id;
		index++;
	});
	
	var ctoken = Token.get_token("dashboard_ajax");
	$.ajax(
	{
		data: {action: "save_tabs_order", data: list },
		type: "POST",
		url: src_ajax+"?&token="+ctoken,
		dataType: "json",
		success: function(data)
		{
			if(typeof(data) == 'undefined')
            {
                show_notification(unknown_error, 'nf_error', 'db_notif');
                return false;
            }
			if(data.error)
			{
				show_notification(data.msg, 'nf_error', 'db_notif', 2500);
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
            
			show_notification(errorThrown, 'nf_error', 'db_notif', 2500);
		}
	});
	
}


function set_column_layout(new_num_columns)
{
	var list  = {};

	saveConfig();

	list["panel"]   = panel_id;
	list["new_col"] = new_num_columns;

	var ctoken = Token.get_token("dashboard_ajax");
	$.ajax(
	{
		data:  {action: "set_layout", data: list },
		type: "POST",
		url: src_ajax+"?&token="+ctoken, 
		dataType: "json",
		success: function(data)
		{
			if(typeof(data) == 'undefined')
            {
                show_notification(unknown_error, 'nf_error', 'db_notif');
                load_slider_info(layout);
                return false;
            }

			if(data.error)
			{	
				show_notification(data.msg, 'nf_error', 'db_notif', 2500);
				load_slider_info(layout);
			} 
			else
			{
				load_slider_info(new_num_columns);
	
				load_tab(panel_id);
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
            
			show_notification(errorThrown, 'nf_error', 'db_notif', 2500);
			load_slider_info(layout);

		}

	});	

}


/********************************** EXTRA FUCNTION FOR TABS **********************************/

function change_tab_title_menu(id)
{
	$("#db_tab_" + id + " .editInPlace").trigger(
	{
		type: 'mousedown',
		which: 3
	});
}

//Change the layout of the dashboard into a new column number layout
function change_columns(new_num_columns)
{
	if(isNaN(new_num_columns) || parseInt(new_num_columns) < 1)
	{
		show_notification('<?php echo _('Illegal number of columns') ?>: ', 'nf_error', 'db_notif', 2500);
		load_slider_info(layout);	
	}
	
	if(new_num_columns < layout)
	{
		if (confirm('<?php echo  Util::js_entities(_("This action will reorder all your widgets to fit in the new layout. Are you sure you want to continue?")) ?>')) 
		{ 
			set_column_layout(new_num_columns);
		} 
		else
		{
			load_slider_info(layout);
		}
	} 
	else if(new_num_columns > layout)
	{
		set_column_layout(new_num_columns);
	}
	else 
	{
		show_notification('<?php echo _('The number of columns specified is the same') ?>', 'nf_info', 'db_notif', 2500);
	}

	return false;
}


/****************************************************************************************************************/
/*																												*/
/************************************************ EXTRA FUNTIONS ************************************************/
/*																												*/
/****************************************************************************************************************/

function show_notification(msg, type, id, fade, cancel, style)
{

	if(typeof(id) == 'undefinded')
	{
		return false;
	}
	
	if(typeof(fade) == 'undefinded' || fade == null)
	{
		fade = 0;
	}

	if(typeof(cancel) == 'undefinded' || cancel == null )
	{
		cancel = false;
	}

	if(typeof(style) == 'undefinded' || style == null )
	{
		style = 'width:100%;padding:5px 0;text-align:center;';
	}
			
	var config_nt = { 
			content: msg, 
			options: {
				type: type,
				cancel_button: cancel
			},
			style: style
	};

	nt = new Notification('nt_'+id,config_nt);

	$('#'+id).html(nt.show());
	
	if(fade > 0)
	{
		$('#nt_'+id).fadeIn(1000).delay(fade).fadeOut(2000);
	}
}
		
//Convert a color code in rgb format into a Hex format color code
function rgb2hex(rgb)
{
	rgb = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
	return "#" +
		("0" + parseInt(rgb[1],10).toString(16)).slice(-2) +
		("0" + parseInt(rgb[2],10).toString(16)).slice(-2) +
		("0" + parseInt(rgb[3],10).toString(16)).slice(-2);
}

function delete_wizard_session()
{		
	var ctoken = Token.get_token("dashboard_ajax");
	$.ajax({
		data: { "action": "delete_wizard_session" },
		type: "POST",
		url: src_ajax+"?&token="+ctoken, 
		async: false,
		dataType: "json"
	});

	return true;
}


/*
Not used yet!
*/
function draw_new_tab(id, name)
{
	var tab = $('<div>', 	
	{
		id        : id,
		"class"   : "db_tab_tab",
		"data-id" : id,
	});

	var title = $('<div>', 	
	{
		"class"   : "db_tab_title"
	}).appendTo(tab);

	$('<a>', 	
	{
		href    : "javascript:;",
		"class" : "db_tab_text active",
		text    : name,
	}).appendTo(title);

	var opts = $('<div>', 	
	{
		"class"   : "db_tab_opts"
	}).appendTo(tab);

	$('<div>', 	
	{
		"title"    : "<?php echo _("Tab Options") ?>",
		"class"    : "tab-options ui-icon ui-icon-plus",
		"data-url" : "sections/tabs/tab_menu.php?id="+id,
	}).appendTo(opts);

	tab.prependTo($(".db_tab_list"));
}
