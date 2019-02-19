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

var __flag_bg        = false;
var __dt_galarm      = null;
var __timeout_search = false;
var __interval       = null;
var __open_groups    = {};
var __ajax_url       = '<?php echo AV_MAIN_PATH ?>/alarm/controllers/alarm_group_actions.php';
var __alarm_url      = <?php echo json_encode(Alarm::get_alarm_path()) ?>;
var groupID = '';

/*  Local Storage Keys  */
var __local_storage_keys =
{
    "filters": "alienvault_<?php echo Session::get_session_user() ?>_show_alarm_group_filters"
};

/************************************************************************************/
/*************************        DOCUMENT READY       ******************************/
/************************************************************************************/


function setGroupID(id) {
    groupID = id ;
}

/*
 *    Document Ready for alarm group console
 */
function load_alarm_list()
{
	check_background_tasks(0);

    display_filters();
    
    $('.date_filter').datepicker(
    {
        showOn: "both",
        buttonText: "",
        dateFormat: "yy-mm-dd",
        buttonImage: "/ossim/pixmaps/calendar.png",
        onClose: function(selectedDate)
        {
            if ($(this).attr('id') == 'date_from')
            {
                $('#date_to').datepicker('option', 'minDate', selectedDate );
            }
            else
            {
                $('#date_from').datepicker('option', 'maxDate', selectedDate );
            }
        }
    });

    //Autocomplete
    var hosts = [<?php echo $hosts_str ?>];

    $("#src_ip").autocomplete(hosts, 
    {
        minChars: 0,
        width: 225,
        matchContains: "word",
        autoFill: true,
        formatItem: function(row, i, max) 
        {
            return row.txt;
        }
    }).result(function(event, item) 
    {
        $("#src_ip").val(item.ip);
    });

    $("#dst_ip").autocomplete(hosts, 
    {
        minChars: 0,
        width: 225,
        matchContains: "word",
        autoFill: true,
        formatItem: function(row, i, max) 
        {
            return row.txt;
        }
    }).result(function(event, item) 
    {
        $("#dst_ip").val(item.ip);
    });

    
    $('#b_close_selected, #b_delete_selected').on('click', function()
    {
        var that     = $(this)
        var selected = []
        $('.ag_check:checked').each(function()
        {
            selected.push($(this).val())
        })
        
        if (selected.length < 1)
        {
            return false
        }
        
        var keys = {"yes": "<?php echo _('Yes') ?>","no": "<?php echo _('No') ?>"};
        
        if ($(this).attr('id') == 'b_delete_selected')
        {
            var msg  = '<?php echo Util::js_entities(_("You are about to delete the selected alarm group(s). This action cannot be undone. Would you like to continue?"))?>';
            
            av_confirm(msg, keys).done(function()
            {
                that.addClass('av_b_processing')
                delete_group(selected)
            });
        }
        else
        {
            var msg  = '<?php echo Util::js_entities(_("You are going to close the selected alarm group(s). Would you like to continue?"))?>';

            av_confirm(msg, keys).done(function()
            {
                that.addClass('av_b_processing')
                change_group_status_action('close', selected)
            });
        }
    });
    
    
    $('#allcheck').on('change', function()
    {
        var chk = $(this).prop('checked')
        
		$('.ag_check:enabled').each(function()
		{
    		$(this).prop('checked', chk)
		});
		
		modify_action_buttons();
	})


    //Tiptip
    $('.tip').tipTip({maxWidth:'300px'});
    
    
    // Data table
    load_alarm_group_dt()
    
    
    $('#alarm_group_params input.ag_param').on('input', function()
    {
        clearTimeout(__timeout_search)
        
        __timeout_search = setTimeout(function()
        {
            reload_alarm_groups();
            
        }, 600);
                
    });
    
    
    $('#alarm_group_params select.ag_param, #alarm_group_params input.ag_param').on('change', function()
    {
        clearTimeout(__timeout_search)
        
        __timeout_search = setTimeout(function()
        {
            reload_alarm_groups();
            
        }, 500);
                
    });
    
    
    $('#refresh_now').on('click', function()
    {
         refresh_alarm_groups();
    });
    
    
    $('#refresh_time').on('change', function()
    {
         var time = $(this).val();
         
         if (time < 1)
         {
             clearInterval(__interval);
         }
         else
         {
             __interval = setInterval(refresh_alarm_groups, time);
         }

    });
    
      
    $('#expandcollapse').on('click', opencloseAll);
    
    
    $("#body_ga").on('click', 'a.greybox', function()
    {
        var t = this.title || $(this).text() || this.href;
        
        GB_show(t, this.href, 600, '50%');
        
        return false;
    });
    
    
    $("#body_ga").on('click', 'a.greybox2', function()
    {
        var t = this.title || $(this).text() || this.href;

        GB_show(t,this.href, 750,'85%');

        return false;
    });
    
    
    $('#group_type').on('change', function()
    {
        __open_groups = {};
    });
}




/************************************************************************************/
/*************************      GENERAL FUNCTIONS      ******************************/
/************************************************************************************/


/*
 *    Check wheter the action buttons have to be enabled or disabled
 */
function modify_action_buttons()
{
    var selected = $('.ag_check:checked').length
    
    if (selected > 0)
    {
        $('#b_close_selected').prop('disabled', false)
        $('#b_delete_selected').prop('disabled', false)

    }
    else
    {
        $('#b_close_selected').prop('disabled', true)
        $('#b_delete_selected').prop('disabled', true)
    }
    
    $('#b_close_selected, #b_delete_selected').removeClass('av_b_processing')
}


/*
 *    Toggle/Untoggle all the alarm groups
 */
function opencloseAll()
{
    var is_open = $(this).attr('src').match(/plus/) ? true : false
    
	if (is_open)
	{
		$('#expandcollapse').attr('src', '../pixmaps/minus.png');
	}
	else
	{
		$('#expandcollapse').attr('src', '../pixmaps/plus.png');
	}
	
	$('#t_grouped_alarms tr.g_alarm').each(function()
	{
    	var id       = $(this).attr('id')
    	var icon     = $('.toggle_group', this)
    	var row_open = icon.attr('src').match(/plus/) ? true : false
    	
    	if (is_open == row_open)
    	{
        	icon.trigger('click')
        	
    	}
    	
    	if (row_open)
    	{
        	__open_groups[id] = true
    	}
    	else
    	{
        	delete __open_groups[id]
    	}

	})
}

/*
 *    Check if there is any background operation running
 */
function check_background_tasks(times)
{
	var atoken = Token.get_token("alarm_operations");
	$.ajax(
	{
    	url      : __alarm_url['controller'] + "alarm_actions.php?token="+atoken,
    	data     : {"action": 7},
        type     : "POST",
        dataType : "json",
        success  : function(data)
        {
        	if(typeof(data) == 'undefined' || data.error == true)
           	{
           		var msg_err ="<?php echo _('Unable to check background tasks') ?>";
           		show_notification('ag_notif', msg_err, 'nf_error', 5000, true);
           	}
           	else
           	{
           		if(data.bg)
           		{
                	if(!__flag_bg)
                	{
                    	var msg = "<?php echo Util::js_entities(_('Alarm task running in background. This process could take a while.')) ?>"
                        
                        var h   = $.getDocHeight();
                            h   = (h != '') ? h+'px' : '100%';
                        
                        
                        $('#bg_container').css('height', h).show();
                        
                        show_loading_box('bg_container', msg, '');
                        
                        __flag_bg = true;
              		}
            
                	timeout = (times < 5) ? 3000 : 10000;
                	
                	setTimeout(function()
                	{
                    	check_background_tasks(times+1);
                    	
                	}, timeout);
                }
                else
                {
                	if(__flag_bg)
                	{
                    	__flag_bg = false;
                    	
                		$('#bg_container').hide();
                        
                        hide_loading_box('bg_container');
                        
                        reload_alarm_groups();
                		
                	}
                }
           	}
        },
        error: function()
        {
        	var msg_err ="<?php echo _('Unable to check background tasks') ?>";
        	
           	show_notification('ag_notif', msg_err, 'nf_error', 5000, true);
        }
    });
}


/*
 *    Toggle the alarm group filters
 */
function toggle_filters()
{
    var status = ($('#alarm_group_params').css('display') == 'none');

    if(status)
    {
        $('#search_arrow').attr('src', '/ossim/pixmaps/arrow_down.png');
        $('#alarm_group_params').slideDown();
    }
    else
    {
        $('#search_arrow').attr('src', '/ossim/pixmaps/arrow_right.png');
        $('#alarm_group_params').slideUp();
    }

    set_ls_key_status('filters', status);
}


/*
 *    Toggle the alarm group filters
 */
function display_filters()
{
    var status = get_ls_key_status('filters');

    if(status)
    {
        $('#search_arrow').attr('src', '/ossim/pixmaps/arrow_down.png');
        $('#alarm_group_params').show();
    }
    else
    {
        $('#search_arrow').attr('src', '/ossim/pixmaps/arrow_right.png');
        $('#alarm_group_params').hide();
    }

}


/************************************************************************************/
/*************************    ALARM GROUP FUNCTIONS    ******************************/
/************************************************************************************/


/*
 *    Load the alarm groups DataTable
 */
function load_alarm_group_dt()
{
    __dt_galarm = $('#t_grouped_alarms').dataTable( 
    {
        "bProcessing"     : true,
        "bServerSide"     : true,
        "bDeferRender"    : true,
        "sAjaxSource"     : "providers/alarm_group_console_ajax.php",
        "iDisplayLength"  : <?=isset($_SESSION["per_page"]) ? $_SESSION["per_page"] : 50?>,
        "bLengthChange"   : true,
        "sPaginationType" : "full_numbers",
        "bFilter"         : false,
        "aLengthMenu"     : [10, 20, 50, 100, 250, 500],
        "bJQueryUI"       : true,
        "aoColumns"       : 
        [
            { "bSortable": false, "sClass": "center", sWidth: "20px"},
            { "bSortable": false, "sClass": "center", sWidth: "20px"},
            { "bSortable": false, "sClass": "left"},
            { "bSortable": false, "sClass": "left", "bVisible": false},
            { "bSortable": false, "sClass": "center"},
            { "bSortable": false, "sClass": "center"},
            { "bSortable": false, "sClass": "left"},
            { "bSortable": false, "sClass": "center"},
            { "bSortable": false, "sClass": "center", sWidth: "75px"}
        ],
        oLanguage : 
        {
            "sProcessing": "&nbsp;<?php echo _('Loading alarm groups') ?> <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>",
            "sLengthMenu": "&nbsp;Show _MENU_ entries",
            "sZeroRecords": "&nbsp;<?php echo _('No matching records found') ?>",
            "sEmptyTable": "&nbsp;<?php echo _('No alarm groups found in the system') ?>",
            "sLoadingRecords": "&nbsp;<?php echo _('Loading') ?>...",
            "sInfo": "&nbsp;<?php echo _('Showing _START_ to _END_ of _TOTAL_ alarm groups') ?>",
            "sInfoEmpty": "&nbsp;<?php echo _('Showing 0 to 0 of 0 alarm groups') ?>",
            "sInfoFiltered": "(<?php echo _('filtered from _MAX_ total alarm groups') ?>)",
            "sInfoPostFix": "",
            "sInfoThousands": ",",
            "sSearch": "<?php echo _('Search') ?>:",
            "sUrl": "",
            "oPaginate": 
            {
                "sFirst":    "<?php echo _('First') ?>",
                "sPrevious": "<?php echo _('Previous') ?>",
                "sNext":     "<?php echo _('Next') ?>",
                "sLast":     "<?php echo _('Last') ?>"
            }
        },
        "fnDrawCallback" : function(oSettings) 
        {
            $('.tip').tipTip({maxWidth:'300px'});        
            
            var g_type = $('#group_type').val()
            
            if (g_type == 'all' || g_type == 'namedate')
            {
                if (oSettings.aiDisplay.length == 0)
                {
                    return;
                }
                 
                var nTrs       = $('#t_grouped_alarms > tbody > tr');
                var iColspan   = $('#t_grouped_alarms > thead > tr > th').length;
                var sLastGroup = "";
                
                $(nTrs).each(function(i, row)
                {
                    var sGroup = oSettings.aoData[oSettings.aiDisplay[i]]._aData[3];
                    
                    if (sGroup != '' && sGroup != sLastGroup)
                    {
                        var nGroup      = document.createElement('tr');
                        var nCell       = document.createElement('td');
                        nCell.colSpan   = iColspan;
                        nCell.className = "sep_date";
                        nCell.innerHTML = sGroup;
                        
                        nGroup.appendChild(nCell);
                        
                        $(row).before(nGroup);
                        
                        sLastGroup = sGroup;
                    }
                });
            }
            
            load_alarm_group_handlers();
        },        
        "fnServerData": function (sSource, aoData, fnCallback, oSettings) 
        {
            var params = $('#filter_group_alarms').serializeArray();
              
            $.each(params, function (i, v)
            {
                aoData.push({name: v.name, value: v.value})
            })

            oSettings.jqXHR = $.ajax(
            {
                "url"      : sSource,
                "data"     : aoData,
                "type"     : "POST",
                "dataType" : 'json',
                "success"  : function (json) 
                {                            
                    //DataTables Stuffs
                    $(oSettings.oInstance).trigger('xhr', oSettings);
                    fnCallback(json);

                    //Select group by ID
                    if(groupID != '') {
                        $('#' + groupID + ' .toggle_group').click();
                    }
                },
                "error": function(data)
                {
                    //Check expired session
                    var session = new Session(data, '');
                    
                    if (session.check_session_expired() == true)
                    {
                        session.redirect();
                        return;
                    }
            
                    var json = $.parseJSON('{"sEcho": '+aoData[0].value+', "iTotalRecords": 0, "iTotalDisplayRecords": 0, "aaData": "" }')
                    fnCallback(json);
                }
            });
        }
    });
}


/*
 *    Reload the alarm groups DataTables  --> Restart the pagination
 */
function reload_alarm_groups()
{
    try
    {
        __dt_galarm.fnReloadAjax();
    }
    catch(Err)
    {
        document.location.reload();
    }
}


/*
 *    Refresh the alarm groups DataTables
 */
function refresh_alarm_groups()
{
    try
    {
        __dt_galarm._fnAjaxUpdate();
    }
    catch(Err)
    {
        document.location.reload();
    }
}


/*
 *    Get the row container from a given element within it.
 */
function get_my_row(elem)
{
    return $(elem).parents('tr.g_alarm').first();
}


/*
 *    Open an alarm group to load its alarms
 */
function toggle_group()
{
    var g_row    = get_my_row(this)
    
    var g_data   = g_row.data();
    var group_id = g_row.attr('id');

    //Adding new row to the table            
	var row  = $('<tr class="g_alarm_list"></tr>').insertAfter($('#'+group_id));
	var cell = $('<td></td>',
	{
    	'colspan' : $('#t_grouped_alarms > thead > tr > th').length
	}).appendTo(row);
    
    //Getting the new table for the datatables
    var table_dt = $('#alarm_list_template .alarm_list').clone()
	
	cell.html(table_dt);
	
	// Data table
	$(table_dt).dataTable( 
    {
        "bProcessing": true,
        "bServerSide": true,
        "bDeferRender": true,
        "sAjaxSource": "providers/alarm_group_response.php",
        "iDisplayLength": 15,
        "bLengthChange": false,
        "sPaginationType": "full_numbers",
        "bFilter": false,
        "bJQueryUI": true,
        "aoColumns": [
            { "bSortable": false, "sClass": "center", sWidth: "20px"},
            { "bSortable": false, "sClass": "left"},
            { "bSortable": false, "sClass": "center"},
            { "bSortable": false, "sClass": "center"},
            { "bSortable": false, "sClass": "center"},
            { "bSortable": false, "sClass": "center"},
            { "bSortable": false, "sClass": "left"},
            { "bSortable": false, "sClass": "left"},
            { "bSortable": false, "sClass": "center"},
            { "bSortable": false, "sClass": "center", sWidth: "75px"}
        ],
        oLanguage : {
            "sProcessing": "&nbsp;<?php echo _('Loading alarms') ?> <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>",
            "sLengthMenu": "&nbsp;Show _MENU_ entries",
            "sZeroRecords": "&nbsp;<?php echo _('No matching records found') ?>",
            "sEmptyTable": "&nbsp;<?php echo _('No alarms found in the alarm group') ?>",
            "sLoadingRecords": "&nbsp;<?php echo _('Loading') ?>...",
            "sInfo": "&nbsp;<?php echo _('Showing _START_ to _END_ of _TOTAL_ alarms') ?>",
            "sInfoEmpty": "&nbsp;<?php echo _('Showing 0 to 0 of 0 alarms') ?>",
            "sInfoFiltered": "(<?php echo _('filtered from _MAX_ total alarms') ?>)",
            "sInfoPostFix": "",
            "sInfoThousands": ",",
            "sSearch": "<?php echo _('Search') ?>:",
            "sUrl": "",
            "oPaginate": {
                "sFirst":    "<?php echo _('First') ?>",
                "sPrevious": "<?php echo _('Previous') ?>",
                "sNext":     "<?php echo _('Next') ?>",
                "sLast":     "<?php echo _('Last') ?>"
            }
        },
        "fnDrawCallback" : function(oSettings) 
        {
            load_alarm_handler(this)
        },        
        "fnServerData": function ( sSource, aoData, fnCallback, oSettings ) 
        {
            var params = 
            {
                'name'         : group_id,
                'timestamp'    : g_data['g_time'],
                'similar'      : g_data['g_similar'],
                'tag'          : $('#tag').val(),
                'src_ip'       : (g_data['g_ip_src'] != '' && g_data['g_ip_src'] != null) ? g_data['g_ip_src'] : $('#src_ip').val(),
                'dst_ip'       : (g_data['g_ip_dst'] != '' && g_data['g_ip_dst'] != null) ? g_data['g_ip_dst'] : $('#dst_ip').val(),
                'asset_group'  : $('#asset_group').val(),
                'hide_closed'  : $('#hide_closed').prop('checked') ? 1 : 0,
                'sensor_query' : $('#sensor_query').val(),
                'date_from'    : $('#date_from').val(),
                'date_to'      : $('#date_to').val(),
                'no_resolv'    : $('#no_resolv').prop('checked') ? 1 : 0,
                'num_events'   : $('#num_events').val(),
                'num_events_op': $('#num_events_op').val(),
                'vmax_risk'     : $('#arangeB').val() ? $('#arangeB').val() : 2,
                'min_risk'     : $('#arangeA').val() ? $('#arangeA').val() : 0,
                'directive_id' : $('#directive_id').val()    
            }

            $.each(params, function (i, v)
            {
                v = (v == undefined || v == null) ? '' : v
                
                aoData.push({name: i, value: v})
            })

            oSettings.jqXHR = $.ajax(
            {
                "dataType": 'json',
                "type": "POST",
                "url": sSource,
                "data": aoData,
                "success": function (json) 
                {                            
                    //DataTables Stuffs
                    $(oSettings.oInstance).trigger('xhr', oSettings);
                    fnCallback(json);
                    
                },
                "error": function(data)
                {
                    //Check expired session
                    var session = new Session(data, '');
                    
                    if (session.check_session_expired() == true)
                    {
                        session.redirect();
                        return;
                    }
                    var json = $.parseJSON('{"sEcho": '+aoData[0].value+', "iTotalRecords": 0, "iTotalDisplayRecords": 0, "aaData": "" }')
                    fnCallback(json);
                }
            });
        }
    });
	
	//Switching plus icon to minus icon
	$(".toggle_group", g_row).attr('src', '../pixmaps/minus-small.png').off('click').on('click', untoggle_group);
	
	__open_groups[group_id] = true
	
}


/*
 *    Close an alarm group to hide its alarms
 */
function untoggle_group()
{
    var g_row    = get_my_row(this)
    var group_id = g_row.attr('id');
    
    
    $(g_row).next('tr.g_alarm_list').remove();
    
	$(".toggle_group", g_row).attr('src', '../pixmaps/plus-small.png').off('click').on('click', toggle_group);
	
	delete __open_groups[group_id]
}


/*
 *    Load the alarm group event handler
 */
function load_alarm_group_handlers()
{
    
    $('.ag_descr').on('keypress', function(e)
    {
        if (e.which == 13)
        {
            var row   = get_my_row(this)
            var g_id  = row.attr('id')
            var descr = $(this).val();
            
            save_descr(g_id, descr)
        }
    });
    
    $('.save_descr').on('click', function()
    {
        var row   = get_my_row(this)
        var g_id  = row.attr('id')
        var descr = $('.ag_descr', row).val();
        
        save_descr(g_id, descr)
        
    });
    
    $('.toggle_group').on('click', toggle_group);
    
    $('.owner_action').on('click', change_group_owner);
    
    $('.ag_status.av_l_main').on('click', change_group_status);
    
    $('.ag_check').on('change', modify_action_buttons);
    
    
    
    $('#t_grouped_alarms tr.g_alarm').each(function()
	{
    	var id = $(this).attr('id')
    	
    	if (__open_groups[id])
    	{
        	var icon = $('.toggle_group', this)
        	icon.trigger('click')
    	}
	});
	
	$('#allcheck').prop('checked', false);
	modify_action_buttons();      
}


/*
 *    Save the description of an alarm group
 */
function save_descr(group, descr)
{
    var icon   = $('.save_descr', $('#'+group))
    var params = {};
    var data   = {};

    data["group_id"] = group;
    data["descr"]    = descr;

    params["action"] = "save_descr";
    params["data"]   = data;

    icon.attr('src', '../pixmaps/loading.gif');
        
    var ctoken = Token.get_token("grouped_alarm_actions");
	$.ajax(
	{
		url      : __ajax_url+"?token="+ctoken,
		data     : params,
		type     : "POST",
		dataType : "json",
		success  : function(data)
		{
    		if (data.error)
    		{
    		  show_notification('ag_notif', data.msg, 'nf_error', 5000, true);
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

            show_notification('ag_notif', errorThrown, 'nf_error', 5000, true);
		},
		complete: function()
		{
    		icon.attr('src', '../pixmaps/disk-black.png');
		}
	});
	
}


/*
 *    Change the owner of an alarm group
 */
function change_group_owner()
{
    var elem   = this
    var row    = get_my_row(elem)
    var status = $(elem).data('status')
    var group  = row.attr('id');
    
    var params = {};
    var data   = {};

    data["group_id"] = group;

    params["action"] = status + "_group";
    params["data"]   = data;
    
    $(elem).html("<img class='loading_action' src='/ossim/pixmaps/loading.gif'>")

    var ctoken = Token.get_token("grouped_alarm_actions");
	$.ajax(
	{
		url      : __ajax_url+"?token="+ctoken,
		data     : params,
		type     : "POST",
		dataType : "json",
		success  : function(data)
		{
    		if (data.error)
    		{
    		  show_notification('ag_notif', data.msg, 'nf_error', 5000, true);
    		}
    		else
    		{        		
        		refresh_alarm_groups();
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

            show_notification('ag_notif', errorThrown, 'nf_error', 5000, true);

		}
	});
    
    
    
}


/*
 *    Change the status of an alarm group (Open/Close)
 */
function change_group_status()
{
    var elem   = this
    var row    = get_my_row(elem)
    var status = $(elem).data('status')
    var group  = row.attr('id');
    
    var groups = []
        groups.push(group)
        
    var new_status = (status == 'open') ? 'close' : 'open';
    
    if (new_status == 'open')
    {
        var msg  = '<?php echo Util::js_entities(_("You are going to open the selected alarm group. Would you like to continue?"))?>';
    }
    else
    {
        var msg  = '<?php echo Util::js_entities(_("You are going to close the selected alarm group. Would you like to continue?"))?>';
    }
    
    var keys = {"yes": "<?php echo _('Yes') ?>","no": "<?php echo _('No') ?>"};
    
    
    av_confirm(msg, keys).done(function()
    {
        $(elem).html("<img class='loading_action' src='/ossim/pixmaps/loading.gif'>")
        
        change_group_status_action(new_status, groups);
    });
}


/*
 *    Change the status of an alarm group (AJAX Call)
 */
function change_group_status_action(status, groups)
{
    var params = {};
    var data   = {};

    data["groups"]   = groups;

    params["action"] = status + "_group";
    params["data"]   = data;

    var ctoken = Token.get_token("grouped_alarm_actions");
	$.ajax(
	{
		url      : __ajax_url+"?token="+ctoken,
		data     : params,
		type     : "POST",
		dataType : "json",
		success  : function(data)
		{
    		if (data.error)
    		{
    		  show_notification('ag_notif', data.msg, 'nf_error', 5000, true);
    		}
    		else
    		{
        		check_background_tasks(0);
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

            show_notification('ag_notif', errorThrown, 'nf_error', 5000, true);

		}
	}); 
}



<?php
if (Session::menu_perms("analysis-menu", "ControlPanelAlarmsDelete"))
{
    ?>
    
    /*
     *    Delete an alarm group
     */
    function delete_group(groups) 
    {
        
        var params = {};
        var data   = {};
    
        data["groups"]   = groups;
    
        params["action"] = "delete_group";
        params["data"]   = data;
    
        var ctoken = Token.get_token("grouped_alarm_actions");
    	$.ajax(
    	{
    		url      : __ajax_url+"?token="+ctoken,
    		data     : params,
    		type     : "POST",
    		dataType : "json",
    		success  : function(data)
    		{
        		if (data.error)
        		{
        		  show_notification('ag_notif', data.msg, 'nf_error', 5000, true);
        		}
        		else
        		{
            		check_background_tasks(0);
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
    
                show_notification('ag_notif', errorThrown, 'nf_error', 5000, true);
    
    		}
    	}); 
    }
    
    
    /*
     *    Delete all the alarm groups
     */
    function delete_all_groups()
    {
        if ($('#no_groups').length >= 1)
        {
            return false;
        }
        
        var msg  = '<?php echo Util::js_entities(_("You are about to delete all the alarm groups. This action cannot be undone. Would you like to continue?"))?>';
        var keys = {"yes": "<?php echo _('Yes') ?>","no": "<?php echo _('No') ?>"};
        
        av_confirm(msg, keys).done(function()
        {
            var params       = {};
            params["action"] = "delete_all";

            var ctoken = Token.get_token("grouped_alarm_actions");
        	$.ajax(
        	{
        		url      : __ajax_url+"?token="+ctoken,
        		data     : params,
        		type     : "POST",
        		dataType : "json",
        		success  : function(data)
        		{
            		if (data.error)
            		{
            		  show_notification('ag_notif', data.msg, 'nf_error', 5000, true);
            		}
            		else
            		{
                		check_background_tasks(0);
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
        
                    show_notification('ag_notif', errorThrown, 'nf_error', 5000, true);
        
        		}
        	}); 
            
        });
    }
    <?php
}
?>




/************************************************************************************/
/*************************       ALARM FUNCTIONS       ******************************/
/************************************************************************************/


/*
 *    Load the alarm event handler
 */
function load_alarm_handler(table)
{
	$('.tip', table).tipTip({maxWidth:'300px'});
	
	$('.repinfo', table).tipTip({attribute:"txt", defaultPosition: "left"});

	$('.aname', table).each(function(key, value)
    {
        var content = $(this).next('div').html();

        $(this).tipTip({content: content, maxWidth:'300px'});
        
    });    
    
    if (typeof(load_contextmenu) != "undefined")
    {
        load_contextmenu();
    }
    
    $('.a_status', table).on('click', change_alarm_status);
    
    $('.alarm_expand', table).on('click', toggle_alarm);
    
    // OTX icon
    $('.otx_icon').off('click').on('click', function(e) 
    {
        e.stopPropagation();
        
        var backlog = $(this).parents('tr').attr('id');
        var title   = "<?php echo Util::js_entities(_('OTX Details')) ?>";
        var url     = "/ossim/otx/views/view_my_pulses.php?type=alarm&id=" + backlog;
        
        GB_show(title, url, 600, '65%');
        
        return false;
    });
}


/*
 *    Refresh alarm DataTable
 */
function refresh_alarms(table)
{
    var dt = table.dataTable()
    
    try
    {
        dt._fnAjaxUpdate();
    }
    catch(Err)
    {
        document.location.reload();
    }
}


/*
 *    Get the table container from a given element within it.
 */
function get_my_alarm_table(elem)
{
    return $(elem).parents('table.alarm_list').first();
}


/*
 *    Get the row container from a given element within it.
 */
function get_my_alarm_row(elem)
{
    return $(elem).parents('tr.alarm_dt').first();
}


/*
 *    Open an alarm to load its events
 */
function toggle_alarm()
{
    var icon = $(this)
    
	icon.attr('src', '../pixmaps/minus-small.png');
	icon.off('click').on('click', untoggle_alarm);
	
	var alarm_row = get_my_alarm_row(this);
    var alarm_id  = alarm_row.attr('id');
    var alarm_t   = get_my_alarm_table(this);
    
    //Adding new row to the table            
	var row  = $('<tr class="a_event_list"></tr>').insertAfter(alarm_row);
	var cell = $('<td></td>',
	{
    	'colspan' : $('thead > tr > th', alarm_t).length
	}).appendTo(row);

	
	$.ajax(
	{
		type: "GET",
		url: "events_ajax.php?backlog_id="+alarm_id,
		data: "",
		success: function(data)
		{
    		cell.html(data)
    		
			$('.td_event_name', cell).each(function(key, value)
            {
                var content = $(this).find('div').html();

                if (typeof(content) != 'undefined' && content != '' && content != null)
                {
                    $(this).tipTip({content: content, maxWidth:'300px'});
                }
            });

            $('.td_date', cell).each(function(key, value)
            {
                var content = $(this).find('div').html();

                if (typeof(content) != 'undefined' && content != '' && content != null)
                {
                    $(this).tipTip({content: content, maxWidth:'300px'});
                }
            });

			load_contextmenu();
		}
	});
}


/*
 *    Close an alarm to hide its events
 */
function untoggle_alarm(backlog_id,event_id)
{
	var icon = $(this)
	
	icon.attr('src', '../pixmaps/plus-small.png');
	icon.off('click').on('click', toggle_alarm);
	
	var alarm_row = get_my_alarm_row(this);
	
	$(alarm_row).next('tr.a_event_list').remove();

}


/*
 *    Change the alarm status --> Open, Closed
 */
function change_alarm_status()
{
    var alarm_row = get_my_alarm_row(this);
    var alarm_id  = alarm_row.attr('id');
    var alarm_t   = get_my_alarm_table(this);
    var is_closed = $(this).hasClass('a_closed');
    
    if (is_closed)
    {
        var msg  = '<?php echo Util::js_entities(_("You are going to open the selected alarm. Would you like to continue?"))?>';
    }
    else
    {
        var msg  = '<?php echo Util::js_entities(_("You are going to close the selected alarm. Would you like to continue?"))?>';
    }
    
    var keys = {"yes": "<?php echo _('Yes') ?>","no": "<?php echo _('No') ?>"};
    
    av_confirm(msg, keys).done(function()
    {        
        var data   = {};
        var params = {};
        
        //Setting Data Params
        data["id"] = alarm_id;
    
        //Setting AJAX params
        params["action"] = (is_closed) ? 2 : 1; //2 - open alarm, 1 - close alarm
        params["data"]   = data;
        

        var atoken = Token.get_token("alarm_operations");    
		$.ajax(
		{
    		url: __alarm_url['controller'] + "alarm_actions.php?token="+atoken,
			data:  params,
			type: "POST",
			dataType: "json",
			success: function(data)
			{
				if(data.error)
				{
					show_notification('ag_notif', data.msg, 'nf_error', 5000, true);
				}
				else
				{
					refresh_alarms(alarm_t)
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
    
                show_notification('ag_notif', errorThrown, 'nf_error', 5000, true);
			}
		});

    });

}


/************************************************************************************/
/**********************      LOCAL STORAGE FUNCTIONS       **************************/
/************************************************************************************/

function get_ls_key_status(k)
{
    var key     = __local_storage_keys[k];
    var enabled = 0;

    if (key)
    {
        enabled = localStorage.getItem(key);
    }

    return enabled != 0;
}

function set_ls_key_status(k, status)
{
    var key = __local_storage_keys[k];
    var val = status ? 1 : 0;

    if (key)
    {
        localStorage.setItem(key, val);
    }
}


/************************************************************************************/
/*************************      GREYBOX FUNCTIONS      ******************************/
/************************************************************************************/


function GB_onhide(url,params)
{
	if (url.match(/newincident/))
	{
		document.location.href="../incidents/index.php?m_opt=analysis&sm_opt=tickets&h_opt=tickets"
	}
	else if (typeof(params) == 'object' && typeof params['url_detail'] != 'undefined')
	{
		if (typeof(parent.show_overlay_spinner)=='function') parent.show_overlay_spinner(true);
		$(".closeimg",window.parent.document).click();
		document.location.href = params['url_detail'];
       }
}


function GB_onclose(url)
{ 
    if (url.match(/alarm_detail/))
	{
    	var backlog = url.replace(/.*backlog=/, '')
    	var table = get_my_alarm_table($('#'+backlog))
    	
    	refresh_alarms(table);
	}
}

