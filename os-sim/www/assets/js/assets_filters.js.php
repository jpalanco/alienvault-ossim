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
var __ajax_url = '<?php echo AV_MAIN_PATH ?>/assets/ajax/';



/**************************************************************************/
/****************************  AJAX FUNCTIONS  ****************************/
/**************************************************************************/

/*  Function to load the filters  */
function populate_filters(page)
{                
    var params = {};
    var ctoken = Token.get_token("asset_filter_list");

    params["action"]     = __filters[__filter_id]['filter'];
    params["page"]       = page;
    params["search"]     = __search || '';
    params["search_all"] = (__search_all == true) ? 1 : 0;
    
    $('#filter_list_msg').hide();
    
    clearTimeout(__timeout_fil);
    
    __timeout_fil = setTimeout(function()
    {
        $('#filter_loading_layer').show();
        
    }, 250);
    
	$.ajax(
	{
		data: params,
		type: "POST",
		url: __ajax_url + "filter_list_ajax.php?token="+ctoken,
		dataType: "json",
		success: function(data)
		{
    		clearTimeout(__timeout_fil);
    		
    		if (!data.error)
    		{
        		fill_filter_list(data.data, page);
        		$('#filter_loading_layer').hide();
        		$('#filter_paginator').show();
    		}
    		else
    		{
        		$('.filter_column').empty();
        		$('#filter_loading_layer').hide();
        		$('#filter_list_msg').text("<?php echo _('No Results Found.') ?>").show();
        		
        		$('#filter_paginator').hide();
        		set_pagination(0, 1);
        		
        		show_notification('av_notif_lb', data.msg, 'nf_error', 5000, true);
    		}
    		
            
		},
		error: function(XMLHttpRequest, textStatus, errorThrown) 
		{	
    		clearTimeout(__timeout_fil);
    		
            //Checking expired session
    		var session = new Session(XMLHttpRequest, '');
            if (session.check_session_expired() == true)
            {
                session.redirect();
                return;
            }
            
            show_notification('av_notif_lb', errorThrown, 'nf_error', 5000, true);
            
            $('.filter_column').empty();
            $('#filter_loading_layer').hide();
            $('#filter_list_msg').text("<?php echo _('No Results Found.') ?>").show();
            
            $('#filter_paginator').hide();
            set_pagination(0, 1);
		}
	});
}


/*  Function to modify a filters  */
function set_filter_value(id, value, del_value, tag_name)
{
    var params = {};
    var data   = {};
    var ctoken = Token.get_token("asset_filter_value");
    
    data["id"]       = id;
    data["filter"]   = value;
    data["delete"]   = ~~del_value;
    data["reload"]   = 0;
     
    params["action"] = "modify_filter";
    params["data"]   = data;
    
	$.ajax(
	{
		data: params,
		type: "POST",
		url: __ajax_url + "asset_filter_ajax.php?token=" + ctoken,
		dataType: "json",
		beforeSend: function()
		{
    	   deactivate_search_inputs();	
		},
		success: function(data)
		{
    		if (!data.error)
    		{
                if(!del_value && typeof top.frames['main'].create_tag == 'function')
                {   
                    __create_tags[value] = [tag_name, id, value];
                }
                else if(del_value && typeof top.frames['main'].remove_tag == 'function')
                {
                    delete(__create_tags[value]);
                    __delete_tags[value] = [id, value];
                }
    		}
    		else
    		{
        		show_notification('av_notif_lb', data.msg, 'nf_error', 5000, true);
    		}
            
            activate_search_inputs();
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
            show_notification('av_notif_lb', errorThrown, 'nf_error', 5000, true);
            activate_search_inputs();
		}
	});
}


/**************************************************************************/
/****************************  EVENT HANDLERS  ****************************/
/**************************************************************************/

function load_filters_handlers()
{

    $('.toggle').toggles({'on': true});
    
    reset_switch_button();
    
                    
    /*  Activating the tab plugin   */
    $("#tab_filter_list").tabs(
    {
		selected: 0,
		create: function()
		{
			$('#tab_filter_list').css('visibility', 'visible');
			populate_filters(1);
		},
		select: function(event, ui)
		{
		    var tab = ui.tab;
		    
            __filter_id    = $(tab).data('filter_id');
            
            reset_switch_button();
            
            $('#filter_paginator').hide();
            set_pagination(0, 1);
            
            __total_items  = 0;
            __search       = '';
            
            $('#filter_search_input').val('');
                        
            populate_filters(1);
		}
	});
    
    
    /*  Activating the placeholder plugin   */
    $('#filter_search_input').placeholder();
	
	
	/*  Activating the paginator plugin  */
	$('#filter_paginator').pagination(
    {
        items: 0,
        itemsOnPage: 30,
        cssStyle: "av-theme",
        prevText: "<?php echo _('< Previous') ?>",
        nextText: "<?php echo _('Next >') ?>",
        onPageClick: function(pageNumber, event)
        {
            populate_filters(pageNumber);
        }
    });
    
    
     /*  Event for the search box  */
    $('#filter_search_input').on('keyup', function(e)
    {
        if (e.which == 13) 
        {
            e.preventDefault();
            
            __search = $(this).val();
        
            populate_filters(1);
        }
        
    });
    
    
     /*  Event for clean the search box  */
    $('#clear_search_input').on('click', function()
    {
        $('#filter_search_input').val('');
        
        if (__search != '')
        {
            $('#filter_search_input').val('');
            
            __search = '';
            populate_filters(1);
        }
        
    });
    
    
    /*  Event for select a filter from the list  */
    $(document).on('click', '.filter_column input', function()
    {
        var val   = $(this).data('id');
        var del   = !$(this).prop('checked');
        var name  = $(this).val();
        
        var id    = __filter_id;
        var label = __filters[__filter_id]['name'] + ": " + name;
         
         
        set_filter_value(id, val, del, label);

    });
    
    
    /*  Event for the apply button  */
    $('#f_apply').on('click', function()
    {
        $.each(__delete_tags, function(index, value) 
        {
            var id    = value[0];
            var value = value[1];
            
            top.frames['main'].remove_tag(id, value);
        });
        
        $.each(__create_tags, function(index, value) 
        {
            var tag   = value[0];
            var id    = value[1];
            var value = value[2];
                              
            top.frames['main'].create_tag(tag, id, value);
        });
        
        parent.GB_hide();
        
    });
    
    
    /*  Event for the cancel button  */
    $('#f_cancel').on('click', function()
    {
        if (typeof parent.GB_close == 'function')
        {
            parent.GB_close();
        }
        
        return false;
    });
    
        
    $('#s-toggle').on('toggle', function (e, active) 
    {
        __search_all = !active;
        
        populate_filters(1);
    });

    
}



/**************************************************************************/
/*************************  FILTER ITEM FUNCTIONS  ************************/
/**************************************************************************/
          

/*  This function set the pagination of the filter list  */
function set_pagination(items, page)
{
    items = (items < 0) ? 0 : items;

    if (items != __total_items)
    {
        __total_items = items;
        
        $('#filter_paginator').pagination('updateItems', __total_items);
        $('#filter_paginator').pagination('selectPage', 1);
    }
    
    var current_page = $('#filter_paginator').pagination('getCurrentPage');
    
    if (current_page != page)
    {
        $('#filter_paginator').pagination('selectPage', page);
    }
    
}


/*  This function fill the filter list with their items  */
function fill_filter_list(data, page)
{
    var list  = data.list;
    var total = data.total;
    
    $('.filter_column').empty();
    
    if (total == 0)
    {
        $('#filter_list_msg').text("<?php echo _('No Results Found.') ?>").show();
        
        set_pagination(0, 1);
        
        return false;
    }
    else
    {
        $('#filter_list_msg').empty().hide();
    }

    var i = 0;
    $.each(list, function(index, value) 
    {
        var col   = (i%3) + 1;
        var id    = value.id;
        var name  = value.name;
        var extra = value.extra;
        var chk   = (value.checked) ? true : false;
        var value = '';
        var input = '';
        
        value = name;
        
        if (typeof extra != 'undefined' && extra != '')
        {
            name  = name + ' <span class="item_extra">(' + extra + ')</span>';
        }
        
        

        input = $("<input>", 
        {
            type: "checkbox",
            value: value,
            "checked": chk,
            "data-id": id
        });
        
        $("<label></label>", 
        {
            "class" : "filter_list_input",
            "title" : name,
            
        }).append(input).append(name).appendTo('#column_'+col);
        
        i++;
        
    });
    
    $('.filter_list_input').tipTip();
    
    set_pagination(total, page);
    
}



/**************************************************************************/
/****************************  EXTRA FUNCTIONS  ***************************/
/**************************************************************************/

function reset_switch_button()
{
    $('#s-toggle').trigger('toggleOn');
    
    __search_all = false;
    
    if(__filters[__filter_id]['full_search'])
    {
        $('#full_search').css('visibility', 'visible');
    }
    else
    {
        $('#full_search').css('visibility', 'hidden');
    }
}

/*  Function to activate the inputs search once the ajax is done  */
function activate_search_inputs()
{
    $('.filter_list_input input').prop("disabled", false);
    $('#filter_search_input').prop("disabled", false);
    $('body').css('cursor', 'default');
    
}


/*  Function to deactivate the inputs search while ajax is ongoing  */
function deactivate_search_inputs()
{
    $('.filter_list_input input').prop("disabled", true);
    $('#filter_search_input').prop("disabled", true);
    $('body').css('cursor', 'wait');
    
}
