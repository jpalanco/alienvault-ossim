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

var __path_ossim = "<?php echo AV_MAIN_PATH ?>";
var ajax_url     = __path_ossim + '/assets/ajax/asset_filter_ajax.php';


/**************************************************************************/
/****************************  AJAX FUNCTIONS  ****************************/
/**************************************************************************/

/*  Function to modifiy the filters  */
function set_filter_value(id, value, del_value, tag_label)
{
    var params = {};
    var data   = {};

    data["id"]       = id;
    data["filter"]   = value;
    data["delete"]   = ~~del_value;
    data["reload"]   = 1;

    params["action"] = "modify_filter";
    params["data"]   = data;


    var ctoken = Token.get_token("asset_filter_value");
	$.ajax(
	{
		data: params,
		type: "POST",
		url: ajax_url+"?token="+ctoken,
		dataType: "json",
		beforeSend: function()
		{
    	   deactivate_search_inputs();
    	   search_loading(true);
		},
		success: function(data)
		{
    		if (!data.error)
    		{
        		//if the datatables is defined we reload it
                if (check_datatables())
                {
                    datatables_assets.fnDraw();
                }
                else
                {
                    activate_search_inputs();
                    search_loading(false);
                }


                if (del_value)
                {
                   remove_tag(id, value);
                }
                else
                {
                    if (typeof tag_label != 'undefined' && tag_label != '' )
                    {
                        create_tag(tag_label, id, value);
                    }
                }

    		}
    		else
    		{
        		show_notification('asset_notif', data.msg, 'nf_error', 5000, true);

        		activate_search_inputs();
        		search_loading(false);
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

            show_notification('asset_notif', errorThrown, 'nf_error', 5000, true);

            activate_search_inputs();

            search_loading(false);
		}
	});
}


/*  Function to apply the filters and reload the datatables  */
function reload_assets_group(force)
{
    if (typeof force == 'undefined')
    {
        force = false;
    }

    var data      = {};

    data["force"] = ~~force;

	var ctoken = Token.get_token("asset_filter_value");
	$.ajax(
	{
		data: {"action":"reload_group", "data": data },
		type: "POST",
		url: ajax_url+"?token="+ctoken,
		dataType: "json",
		beforeSend: function()
		{
    	   deactivate_search_inputs();
		},
		success: function(data)
		{
            //if the datatables is defined we reload it
            if(check_datatables())
            {
                datatables_assets.fnDraw();
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

            show_notification('asset_notif', errorThrown, 'nf_error', 5000, true);

            activate_search_inputs();
		}
	});
}


/*  Function to restore the search --> It deletes the filter_list object  */
function restart_search()
{
    var ctoken = Token.get_token("asset_filter_value");
    $.ajax(
	{
		data: {"action":"restart_search"},
		type: "POST",
		url: ajax_url+"?token="+ctoken,
		dataType: "json",
		success: function(data)
		{
    		try
    		{
                if (data.error)
                {
                    document.location.reload();
                }

                remove_all_filters();

                datatables_assets.fnDraw();

            }
            catch(Err)
            {
                document.location.reload();
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

            show_notification('asset_notif', errorThrown, 'nf_error', 5000, true);
		}
	});
}

/*  Function to restore the filter_list object if we cancel the filters in the extra_filter Lightbox  */
function restore_filter_list()
{
    var ctoken = Token.get_token("asset_filter_value");
    $.ajax(
	{
		data: {"action": "cancel_filter"},
		type: "POST",
		url: ajax_url+"?token="+ctoken,
		dataType: "json",
		success: function(data)
		{
            return false;
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
		}
	});
}



/**************************************************************************/
/****************************  EVENT HANDLERS  ****************************/
/**************************************************************************/

function load_search_handlers()
{

    /*  Adding click event on go to detail icon  */
    $(document).on('click', '.detail_img', function(e)
    {
        e.preventDefault();
        e.stopPropagation();

        var id = $(this).parents('tr').attr('id');

        link_to(id);
    });


    /*  Adding click event on export host icon  */
    $('#export_host').on('click', function()
    {
        export_host();
    });

    /*  Adding click event on delete_all_hosts icon  */
    $('#delete_all_hosts').on('click', function()
    {
        if ($('#delete_all_hosts').hasClass("disabled"))
        {
             return false;
        }
        
        var msg = '<?php echo Util::js_entities(_("You are about to delete ### asset(s). This action cannot be undone. Are you sure you would like to delete these assets?"))?>';
        
        var n_assets = $('#num_assets').text();
        
        msg = msg.replace('###', n_assets);
        
        var keys = {"yes": "<?php echo _('Yes') ?>","no": "<?php echo _('No') ?>"};
        av_confirm(msg, keys).done(delete_all_hosts);

    });


    /*  Adding click event on save group button  */
    $('#save_button a.button').on('click', function()
    {
        if (!$(this).hasClass('av_b_disabled'))
        {
            save_search();
        }
    });

    /*  Adding click event on clear all filters link  */
    $('#restart_search a').on('click', function()
    {
        restart_search();
    });


    /*  Activating PlaceHolders in input search box  */
    $("#search_filter").placeholder();


    /* SEARCH BOX FILTER */

    $("#search_filter").on('keyup', function(e)
    {
        if(e.keyCode == 13)
        {
            var value = $(this).val();

            if (value == '')
            {
                return false;
            }

            var label = '';

            if (is_ip_cidr(value))
            {
                var label = "<?php echo _('IP & CIDR:') ?> " + value;
                set_filter_value(11, value, 0, label);

            }
            else
            {
                var label = "<?php echo _('Hostname & FQDN:') ?> " + value;
                set_filter_value(12, value, 0, label);

            }

            $("#search_filter").val('');

            return false;
        }

    });


    /*  TAGS FUNCTIONS  */

    $('#tags_filters').tagit(
    {
        onlyAllowDelete: true,
        beforeTagRemoved: function(event, ui)
        {
            return false;
        }
    });


    $(document).on('click', '#tags_filters .ui-icon-close', function(e)
    {
        e.preventDefault();
        e.stopImmediatePropagation();

        var info   = $(this).parents('li.tagit-choice').data('info').split('###');

        var type   = info[0];
        var value  = info[1];

        set_filter_value(type, value, 1);

        return false;
    });


    /* ALARMS & EVENTS FILTERS */

    $('.value_filter').on('change', function()
    {

        var del   = $(this).prop('checked') ? 0 : 1;
        var id    = $(this).data('id');
        var label = '';

        if (id == 3)
        {
            label = "<?php echo _('Has Alarms') ?>";
        }
        else if (id == 4)
        {
            label = "<?php echo _('Has Events') ?>";
        }

        set_filter_value(id, id, del, label);

    });


    /* ASSET VALUE FILTER */

    //Slider
    $('#arangeA, #arangeB').selectToUISlider(
    {
        tooltip: false,
        labelSrc: 'text',
        labels: 5,
        sliderOptions:
        {
            stop: function(event, ui)
            {
                var val1  = $('#arangeA').val();
                var val2  = $('#arangeB').val();

                var value = val1 + ';' + val2;

                var label = "<?php echo _('Asset Value:') ?> " + val1 + ' - ' + val2;

                $('#tags_filters li.filter_6').remove();

                set_filter_value(6, value, 0, label);
            }
        }
    });


    //Checkbox to enable/disable slider
    $('#filter_6').on('change', function()
    {
        if ($(this).prop('checked'))
        {
            var v1    = $('#arangeA').val();
            var v2    = $('#arangeB').val();

            var value = v1 + ';' + v2;

            var label = "<?php echo _('Asset Value:') ?> " + v1 + ' - ' + v2;

            $('#asset_value_slider .ui-slider').slider('enable');

            set_filter_value(6, value, 0, label);

        }
        else
        {
            //Removing tag
            $('#tags_filters li.filter_6').remove();

            //Setting filter value in object
            set_filter_value(6, '', 1);

        }
    });


    /* VULNERABILITIES FILTER */

    //Slider
    $('#vrangeA, #vrangeB').selectToUISlider(
    {
        tooltip: false,
        labelSrc: 'text',
        sliderOptions:
        {
            stop: function( event, ui )
            {
                var val1  = $('#vrangeB').val();
                var val2  = $('#vrangeA').val();
                var text1 = '';
                var text2 = '';

                var value = val1 + ';' + val2;

                $('#tags_filters li.filter_5').remove();

                text1 = $('#vrangeA option:selected').text();
                text2 = $('#vrangeB option:selected').text();

                var label = "<?php echo _('Vulnerabilities:') ?> " + text1 + ' - ' + text2;

                set_filter_value(5, value, 0, label);
            }
        }
    });


    //Checkbox to enable/disable slider
    $('#filter_5').on('change', function()
    {
        if ($(this).prop('checked'))
        {
            var v1    = $('#vrangeB').val();
            var v2    = $('#vrangeA').val();
            var t1    = '';
            var t2    = '';

            var value = v1 + ';' + v2;

            $('#vulns_slider .ui-slider').slider('enable');

            t1 = $('#vrangeA option:selected').text();
            t2 = $('#vrangeB option:selected').text();

            var label = "<?php echo _('Vulnerabilities:') ?> " + t1 + ' - ' + t2;

            set_filter_value(5, value, 0, label);

        }
        else
        {
            //Removing tag
            $('#tags_filters li.filter_5').remove();

            //Setting filter value in object
            set_filter_value(5, '', 1);
        }
    });


    /* DATE FILTERS */

    $('.asset_date_input input[type=radio]').on('change', function()
    {
        var scope  = $(this).parents(".asset_date_input");
        var filter = $(scope).data('filter');
        var type   = $(this).val();
        var label  = '';
        var l_txt  = $(this).next('span').text();

        var value  = '';
        var from   = '';
        var to     = '';
        var del    = 0;

        if (type == 'range')
        {
            $('.asset_date_range', scope).show();

            from  = $('#date_from_'+ filter).val('');
            to    = $('#date_to_'+ filter).val('');

            value = type + ';' + from + ';' + to;

        }
        else
        {
            $('.asset_date_range', scope).hide();

            $('.calendar input', scope).val('');

        }

        value = type;

        if (filter == 1)
        {
            label = "<?php echo _('Assets Added:') ?> " + l_txt;
        }
        else if (filter == 2)
        {
            label = "<?php echo _('Last Updated:') ?> " + l_txt;
        }

        $('#tags_filters li.filter_'+filter).remove();

        set_filter_value(filter, value, del, label);

    });


    /*  CALENDAR PLUGIN  */
    $('.date_filter').datepicker(
    {
        showOn: "both",
        dateFormat: "yy-mm-dd",
        buttonImage: "/ossim/pixmaps/calendar.png",
        onSelect: function(date, ui)
        {
            var that   = ui.input;

            modify_search_filter(that);

        },
        onClose: function(selectedDate, ui)
        {
            var dir    = ui.id.match(/date_from_\d/);
            var filter = $(ui.input).data('filter');

            if (dir)
            {
                var dp = '#date_to_' + filter;

                $(dp).datepicker( "option", "minDate", selectedDate);
            }
            else
            {
                var dp = '#date_from_' + filter;

                $(dp).datepicker( "option", "maxDate", selectedDate);
            }

        }
    });

    $('.date_filter').on('keyup', function(e)
    {
        if (e.which == 13)
        {
            modify_search_filter(this);
        }
    });


    /* DATA TABLES */
    datatables_assets = $('.table_data').dataTable(
    {
        "bProcessing": true,
        "bServerSide": true,
        "bDeferRender": true,
        "sAjaxSource": "/ossim/assets/ajax/load_assets_result.php",
        "iDisplayLength": 20,
        "bLengthChange": true,
        "sPaginationType": "full_numbers",
        "bFilter": false,
        "aLengthMenu": [[10, 20, 50], [10, 20, 50]],
        "bJQueryUI": true,
        "aaSorting": [[ 0, "desc" ]],
        "aoColumns": [
            { "bSortable": true,  "sClass": "left" },
            { "bSortable": false, "sClass": "left", "sWidth": "150px"},
            { "bSortable": false, "sClass": "left"},
            { "bSortable": false, "sClass": "center"},
            { "bSortable": false, "sClass": "center"},
            { "bSortable": false, "sClass": "center"},
            { "bSortable": false, "sClass": "center", "sWidth": "80px"}
        ],
        oLanguage :
        {
            "sProcessing": "&nbsp;<?php echo _('Loading Assets') ?> <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>",
            "sLengthMenu": "&nbsp;Show _MENU_ entries",
            "sZeroRecords": "&nbsp;<?php echo _('No matching records found') ?>",
            "sEmptyTable": "&nbsp;<?php echo _('No assets found in the system') ?>",
            "sLoadingRecords": "&nbsp;<?php echo _('Loading') ?>...",
            "sInfo": "&nbsp;<?php echo _('Showing _START_ to _END_ of _TOTAL_ assets') ?>",
            "sInfoEmpty": "&nbsp;<?php echo _('Showing 0 to 0 of 0 assets') ?>",
            "sInfoFiltered": "(<?php echo _('filtered from _MAX_ total assets') ?>)",
            "sInfoPostFix": "",
            "sInfoThousands": ",",
            "sSearch": "<?php echo _('Search') ?>:",
            "sUrl": "",
            "oPaginate":
            {
                "sFirst":    "",
                "sPrevious": "&lt; <?php echo _('Previous') ?>",
                "sNext":     "<?php echo _('Next') ?> &gt;",
                "sLast":     ""
            }
        },
        "fnServerData": function ( sSource, aoData, fnCallback, oSettings )
        {
            oSettings.jqXHR = $.ajax(
            {
                "dataType": 'json',
                "type": "POST",
                "url": sSource,
                "data": aoData,
                "beforeSend": function()
                {
                    //Showing 'loading' message.
                    search_loading(true);
                },
                "success": function (json)
                {
                    //Hidding 'loading' message.
                    search_loading(false);

                    //DataTables Stuffs
                    $(oSettings.oInstance).trigger('xhr', oSettings);
                    fnCallback(json);


                    //Displaying num assets in its box.
                    $('#num_assets').text(json.iTotalDisplayRecords);

                    //Modifying the 'Save Group' button status
                    modify_save_button_status();
                    
                    //Modifying the 'Delete' button status
                    modify_delete_button_status();

                    //Activating search inputs again
                    activate_search_inputs();
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

                    //Hidding 'loading' message
                    search_loading(false);

                    //DataTables Stuffs
                    var json = $.parseJSON('{"sEcho": '+aoData[0].value+', "iTotalRecords": 0, "iTotalDisplayRecords": 0, "aaData": "" }');
                    fnCallback( json );

                    //Updating num assets to '-' bcz of the error.
                    $('#num_assets').text('-');

                    //Activating search inputs again
                    activate_search_inputs();
                }
            });
        }
    });
}




/**************************************************************************/
/***************************  LIGHTBOX EVENTS  ****************************/
/**************************************************************************/

function GB_onclose(url)
{
    var cond1 = url.match(/save_search/);
    var cond2 = url.match(/filter_list/);

    //If we cancel the extra filter Lightbox, we restore the filter object
    if (cond2)
    {
        restore_filter_list();
    }
    //If we don't come from canceling the save group, we reload the assets
    else if (!cond1)
    {
        //datatables_assets.fnDraw();
        reload_assets_group(true);
    }


}


function GB_onhide(url, params)
{
    //If we are closing the save group lightbox, we load the group detail
    if (url.match(/save_search/))
    {
        var id = params['id'];
        view_asset_group(id);

    }
    //If we are closing the host importation lightbox or a new host is saved, we reload the host table
    else if (url.match(/import_all_hosts/) || url.match(/host_form/))
    {
        if (check_datatables())
        {
            reload_assets_group(true);
        }

    }
    //If we are closing the extra filter lightbox, we apply the filter selection
    else if (url.match(/filter_list/))
    {
        reload_assets_group();
    }

}



/**************************************************************************/
/***************************  LINKS FUNCTIONS  ****************************/
/**************************************************************************/

/*  Function to open the host detail */
function link_to(id)
{
    if (typeof id != 'undefined' && id != '')
    {
        if (typeof top.av_menu.load_content  == 'function' && typeof top.av_menu.get_menu_url  == 'function')
    	{
    	    var url = '/asset_details/index.php?id='+ urlencode(id);
    	        url = top.av_menu.get_menu_url(url, 'environment', 'assets', 'assets');

    	    top.av_menu.load_content(url);
        }
        else
    	{
    	    document.location.href = __path_ossim + '/asset_details/index.php?id='+urlencode(id);
        }
    }
}

/*  Function to load the group detail once it is created  */
function view_asset_group(id)
{
    if (id != '')
    {
        var url = '/asset_details/index.php?id='+id;
    }
    else
    {
        var url = '/assets/list_view.php?type=group';
    }

    if (typeof(top.av_menu.load_content) == 'function')
	{

	    url = top.av_menu.get_menu_url(url, 'environment', 'assets_groups', 'host_groups');

	    top.av_menu.load_content(url);
    }
    else
	{
	    document.location.href = __path_ossim + url;
    }

}

/*  Function to open extra filters lightbox  */
function show_more_filters()
{
    GB_show("<?php echo _('More Filters') ?>", __path_ossim + '/assets/filter_list.php', '650', '850');
}

<?php
if (Session::can_i_create_assets() == TRUE)
{
	?>
	/*  Function to open new host form lightbox  */
    function add_host()
    {
        GB_show("<?php echo _('New Host')?>", __path_ossim + '/host/host_form.php','600','720');
    }

    /*  Function to open import from siem lightbox  */
    function import_siem()
    {
        GB_show("<?php echo _('Import Hosts from SIEM Events')?>", __path_ossim + '/host/import_all_hosts_from_siem.php', '200', '600');
    }
    <?php
}
?>


/* Function to open export hosts page  */
function export_host()
{
    document.location.href = __path_ossim + '/host/export_all_hosts.php';
}


/* Function to delete all hosts which match with filter criteria */
function delete_all_hosts()
{
    if (datatables_assets.fnSettings().aoData.length === 0)
    {
        av_alert('<?php echo Util::js_entities(_("No assets to delete with this filter criteria"))?>');

        return false;
    }

    //Notification style
    style = 'width: 600px; top: -2px; text-align:center ;margin:0px auto;';

    //AJAX data

    var h_data = {
        "token" : Token.get_token("delete_all_hosts")
    };

    $.ajax(
    {
        type: "POST",
        url: __path_ossim + "/host/ajax/delete_all.php",
        data: h_data,
        dataType: "json",
        beforeSend: function()
        {
            $('#asset_notif').empty();

            var _msg = '<?php echo _("Deleting assets ..., please wait")?>';

			show_loading_box('main_container', _msg , '');
        },
        success: function(data)
        {
            //Check expired session
			var session = new Session(data, '');

			if (session.check_session_expired() == true)
			{
				session.redirect();
				return;
			}

			hide_loading_box();

			var cnd_1  = (typeof(data) == 'undefined' || data == null);
			var cnd_2  = (typeof(data) != 'undefined' && data != null && data.status != 'OK');

			//There is an unknown error
			if (cnd_1 || cnd_2)
			{
				var _msg  = (cnd_1 == true) ? "<?php echo _("Sorry, operation was not completed due to an unexpected error")?>" : data.data;
				var _type = (_msg.match(/policy/)) ? 'nf_warning' : 'nf_error';

			    show_notification('asset_notif', _msg, _type, 15000, true, style);
			    
			    datatables_assets.fnDraw();

            }
			else
			{
    			show_notification('asset_notif', data.data, 'nf_success', 15000, true);
    			restart_search();
			}

        },
        error: function(data)
        {
            //Check expired session
            var session = new Session(data, '');

            if (session.check_session_expired() == true)
            {
                session.redirect();
                return;
            }

            hide_loading_box();

            var _msg = "<?php echo _("Sorry, operation was not completed due to an unknown error")?>";

            show_notification('asset_notif', _msg, 'nf_error', 15000, true, style);
        }
    });
}


/*  Function to open import from csv lightbox  */
function import_csv()
{
    GB_show("<?php echo _('Import Hosts from CSV') ?>", __path_ossim + '/host/import_all_hosts.php', '600', '1000');
}


/*  Function to open save group form lightbox  */
function save_search()
{
    GB_show("<?php echo _('Save Asset Group') ?>", __path_ossim + '/assets/save_search.php', '300', '360');
}


/**************************************************************************/
/****************************  TAGS FUNCTIONS  ****************************/
/**************************************************************************/

/*  Function to create a tag filter  */
function create_tag(label, filter, value)
{
    var tag_info  = filter + '###' + value;
    var tag_class = $.md5('label_'+tag_info) + ' filter_' + filter;

    $('#tags_filters').tagit('createTag', label, tag_class, tag_info);
}


/*  Function to delete a tag filter  */
function remove_tag(filter, value)
{
    var tag_info  = filter + '###' + value;
    var tag_class = $.md5('label_'+tag_info);

    //Removing the tag
    $('#tags_filters li.'+tag_class).remove();

    //Deselecting the checkboxes
    $('#filter_'+filter).prop('checked', false);

    deactivate_range_selector(filter);

    //Deselecting the date radios
    $('#filter_'+filter + ' input').prop('checked', false);

    //Hidding date inputs
    $('#filter_'+filter + ' .asset_date_range').hide();

    //Removing the content from the dates selected
    $('#filter_'+filter + ' .date_filter').val('');
    $('#filter_'+filter + ' .date_filter').datepicker("option", {minDate: null, maxDate: null});
}



/**************************************************************************/
/****************************  EXTRA FUNCTIONS  ***************************/
/**************************************************************************/

function modify_search_filter(that)
{
    var filter = $(that).data('filter');

    var from   = $('#date_from_'+filter).val();
    var to     = $('#date_to_'+filter).val();

    var value  = 'range;' + from + ';' + to;

    set_filter_value(filter, value, 0);

    return false;
}

function deactivate_range_selector(filter)
{
    //Vulns Range
    if (filter == 5 || filter == 'all')
    {
        //Disabling Slider
        $('#vulns_slider .ui-slider').slider('disable');
        //Restoring default value
        $('#vulns_slider .ui-slider').slider('values', [0,4]);
        $('#vrangeA option:eq(0)').prop('selected', true);
        $('#vrangeB option:eq(4)').prop('selected', true);
    }
    //Asset value range
    else if (filter == 6 || filter == 'all')
    {
        //Disabling Slider
        $('#asset_value_slider .ui-slider').slider('disable');
        //Restoring default value
        $('#asset_value_slider .ui-slider').slider('values', [0,5]);
        $('#arangeA option:eq(0)').prop('selected', true);
        $('#arangeB option:eq(5)').prop('selected', true);
    }
}

/*  Function to retrieve tray data  */
function get_tray_data(nTr)
{
    var id  = $(nTr).attr('id');

    return $.ajax(
    {
        type: 'GET',
        url:  __path_ossim + '/assets/ajax/asset_tray.php?id=' + id,
    });
}


/*  Function to activate the inputs search once the ajax is done  */
function activate_search_inputs()
{
    $('.input_search_filter').prop("disabled", false);
    $('.calendar input').datepicker('enable');


    if ($('#filter_6').prop('checked'))
    {
        $('#asset_value_slider .ui-slider').slider('enable');
    }

    if ($('#filter_5').prop('checked'))
    {
        $('#vulns_slider .ui-slider').slider('enable');
    }

    $('body').css('cursor', 'default');
}


/*  Function to deactivate the inputs search while ajax is ongoing  */
function deactivate_search_inputs()
{
    $('.input_search_filter').prop('disabled', true);
    $('.calendar input').datepicker('disable');
    $('.ui-slider').slider('disable');
    $('body').css('cursor', 'wait');
}


/*  Function to unmark all the filters  */
function remove_all_filters()
{
    //Uncheck checkboxes and radio
    $('.input_search_filter').prop('checked', false);

    //Restart range selectors
    deactivate_range_selector('all');

    //Removing filter tags
    $("#tags_filters .tagit-choice").remove();

    //Hidding date inputs
    $('.asset_date_range').hide();

    //Empty the date picker
    $('.date_filter').val('');
    $('.date_filter').datepicker("option", {minDate: null, maxDate: null});

}


/*  Function to modify the 'Save Group' button status  */
function modify_save_button_status()
{
    var num_tags  = $('#tags_filters li').length;
    var num_hosts = $('#num_assets').text();

    if (num_tags < 2 || num_hosts == 0)
    {
        $('#save_button a.button').addClass('av_b_disabled');
    }
    else
    {
        $('#save_button a.button').removeClass('av_b_disabled');
    }
}

/*  Function to modify the 'Delete' button status  */
function modify_delete_button_status()
{
    <?php
    // This option will be disable if the user has host or net permissions
    $host_perm_where = Asset_host::get_perms_where();
    $net_perm_where  = Asset_net::get_perms_where();
    
    if (empty($host_perm_where) && empty($net_perm_where))
    {
    ?>
    
    var num_hosts = $('#num_assets').text();

    if (num_hosts == 0)
    {
        $('#delete_all_hosts').addClass('disabled');
    }
    else
    {
        $('#delete_all_hosts').removeClass('disabled');
    }
    
    <?php
    }
    ?>
}

/*  Function to show loading message while datatables is loading  */
function search_loading(loading)
{
    if (loading)
    {
        $('.table_data').css('min-height', '200px');
        $('.dataTables_processing').css('visibility', 'visible');
    }
    else
    {
        $('.table_data').css('min-height', '0');
        $('.dataTables_processing').css('visibility', 'hidden');
    }
}


/*  Check if datatables exists  */
function check_datatables()
{
    var cond1 = datatables_assets != null;
    var cond2 = typeof datatables_assets != 'undefined';
    var cond3 = typeof datatables_assets.fnDraw == 'function';

    if (cond1 && cond2 && cond3)
    {
        return true;
    }

    return false;
}


/*  Validation Filter for Autocomplete  */
function is_ip_cidr(val)
{
    var pattern = /^(([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\/([1-9]|[1-2][0-9]|3[0-2]))?$/ ;

    if (val.match(pattern))
    {
        return true;
    }
    else
    {
        return false;
    }
}
