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
var __ajax_path = "<?php echo AV_MAIN_PATH . '/wizard/ajax/' ?>";



/******************************************************************************************/
/*****************                Step Interfaces Functions                ****************/
/******************************************************************************************/


/*  Function to load the js handlers of this step  */
function load_handler_step_interfaces()
{
    //Dialog To add IP Address and Netmask
    $("#dialog_link").fancybox(
    {
    	'modal': true,
    	'width': 350,
    	'height': 225,
    	'autoDimensions': false,
    	'centerOnScroll': true,
    	'onStart': function()
    	{
        	$('#dialog_message').hide().empty();

        	$('#insert_ip').val(__n_ip);
            $('#insert_mask').val(__n_mask);
    	}

    });

    $('.select_purpose').select2(
    {
        allowClear: false
    });

    $('#ip_dialog input').placeholder();


    $('.nic_roles').on('change', function()
    {
        load_nic_data(this);

        if (__n_role == 'log_management')
        {

            $('#dialog_link').trigger('click');
        }
        else
        {
            change_interface_mode(false);
        }

    }).on("select2-selecting", function(e)
    {
        //Saving previous value to be able to set this value again in case of error
        __nic_state = $(this).val();

    });


    /* Handling dialog actions */
    $('#ip_dialog').find('#cancel, #ok').on('click', function()
    {
        if ($(this).attr('id') == 'ok')
        {
            __n_ip   = $('#insert_ip').val();
            __n_mask = $('#insert_mask').val();

            if (!valid_ip(__n_ip) || !valid_ip(__n_mask))
            {
                $('#dialog_message').text("<?php echo _('Valid IP Address and Netmask are required.') ?>").show();
            }
            else
            {
                change_interface_mode(true);
            }
        } //If we click on cancel
        else
        {
            //If we have stored a previous state, we set the previous state
            if (__nic_state)
            {
                $('.nic_roles', '#nic_'+__nic).select2("val", __nic_state);
                __nic_state = false;

                $.fancybox.close();
            } //Otherwise we set the disabled state
            else
            {
                __n_role = 'disabled'
                __n_ip   = '';
                __n_mask = '';

                $('.nic_roles', '#nic_'+__nic).select2("val", "disabled");

                //Set to false to avoid that the dialog is open forever
                change_interface_mode(false);
            }

        }


    });


    $('.edit_ip_nic').on('click', function()
    {
        load_nic_data(this);

        __nic_state = __n_role;

        $('#dialog_link').trigger('click');

    });

}

function load_nic_data(ui)
{
    var scope = $(ui).parents('tr').first();

    __nic    = scope.data('nic');
    __n_role = $('.nic_roles', scope).select2('val')
    __n_ip   = $('#nic_ip', scope).data('ip');
    __n_mask = $('#nic_ip', scope).data('mask');
}


/*  Function to change the interface mode  */
function change_interface_mode(in_dialog)
{
    var data     = {};

    data['nic']  = __nic;
    data['role'] = __n_role;
    data['ip']   = __n_ip;
    data['mask'] = __n_mask;

    var ctoken = Token.get_token("welcome_wizard");

	$.ajax(
	{
		url: __ajax_path + "wizard_actions_ajax.php?token="+ctoken,
		data: {"action": "change_nic_mode", "data": data},
		type: "POST",
		dataType: "json",
		beforeSend: function()
		{
    		$('#dialog_message').empty().show();
		},
		success: function(data)
		{
    		if (typeof data != 'undefined' && data != null)
    		{
        		if (data.error)
        		{
            		if (in_dialog)
                    {
                        $('#dialog_message').text(data.msg).show();
                    }
                    else
                    {
                		show_notification('wizard_notif', data.msg, 'nf_error', 5000);

                		if (__nic_state)
                        {
                            $('.nic_roles', '#nic_'+__nic).select2("val", __nic_state);
                        }
                    }
        		}
        		else
        		{
            		var scope = $('#nic_'+__nic);

                    //IP Field
            		if (__n_role == 'log_management')
            		{
                		$('#nic_ip', scope).text(__n_ip);
                		$('#nic_ip', scope).data('ip', __n_ip);
                		$('#nic_ip', scope).data('mask', __n_mask);

                		$('.edit_ip_nic', scope).show();

            		}
            		else
            		{
                		$('#nic_ip', scope).text("<?php echo _('N/A') ?>");
                		$('#nic_ip', scope).data('ip', '');
                		$('#nic_ip', scope).data('mask', '');

                		$('.edit_ip_nic', scope).hide();
            		}

            		//LED field
            		if (__n_role == 'monitoring')
            		{
                		$('#indicator_yes', scope).show();
                		$('#indicator_no', scope).hide();
            		}
            		else
            		{
                		$('#indicator_yes', scope).hide();
                		$('#indicator_no', scope).show();
            		}

            		__nic_state = false;

            		clearTimeout(__timeout);

            		get_interfaces_activity();

                    $.fancybox.close();
        		}

            }
            else
            {
                $.fancybox.close();
                show_notification('wizard_notif', "<?php echo _('Unknown Error Found') ?>", 'nf_error', 5000);
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

            $.fancybox.close();
            show_notification('wizard_notif', errorThrown, 'nf_error', 5000);
		}
	});
}


/*  Function to get the traffic of the interfaces  */
function get_interfaces_activity()
{
    var ctoken = Token.get_token("welcome_wizard");

	$.ajax(
	{
		url: __ajax_path + "wizard_actions_ajax.php?token="+ctoken,
		data: {"action": "nic_activity"},
		type: "POST",
		dataType: "json",
		beforeSend: function()
		{
    		$('.wizard_led').removeClass('led_gray led_red led_green');
    		$('.wizard_led_loader').show();
		},
		success: function(data)
		{
    		if (typeof data != 'undefined' && data != null)
    		{
        		if (data.error)
        		{
            		led_error(false);

            		show_notification('wizard_notif', data.msg, 'nf_error', 5000);

            		return false;
        		}

        		data = data.data;

        		var active = false;
        		var nics   = data.nics;

        		$('.wizard_led_loader').hide();

                $('tr.nic_item').each( function()
                {
                    var id     = $(this).data('nic');

                    var status = nics[id];

                    var led    = $('.wizard_led', this);


                    if (status == 'on')
                    {
                        $(led).addClass('led_green');
                    }
                    else if (status == 'off')
                    {
                        $(led).addClass('led_red');
                    }
                    else
                    {
                        $(led).addClass('led_gray');
                    }

                });

        		__timeout = setTimeout(get_interfaces_activity, 30000);

            }
            else
            {
                led_error(false);
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

            led_error(false);

            show_notification('wizard_notif', errorThrown, 'nf_error', 5000);

		}
	});
}



/******************************************************************************************/
/*****************                Step Discovery Functions               ******************/
/******************************************************************************************/


/*  Function to load the js handlers of this step  */
function load_handler_step_discovery()
{

    //Dialog for the scan process
    $("#scan_url").fancybox(
    {
        'type': 'iframe',
    	'showCloseButton': false,
    	'width': 500,
    	'height': 300,
    	'autoDimensions': false,
    	'centerOnScroll': true,
    	'hideOnOverlayClick': false
    });


    //Add a new host
    $('#add_host').on('click', function()
    {
        if (!$(this).hasClass('av_b_disabled'))
        {
            var ip   = $('#host_ip').val();
            var name = $('#host_name').val();
            var type = $('#host_type').val();

            insert_new_host(ip, name, type);
        }

    });


    //Event to activate the add host button
    $('#host_ip, #host_name').on('keyup', function()
    {
       var val_1   = $('#host_ip').val();
       var val_2   = $('#host_name').val();

       if (val_1 != '' && val_2 != '')
       {
           $('#add_host').prop('disabled', false);
       }
       else
       {
           $('#add_host').prop('disabled', true);
       }

    });


    //Placeholder for the host inputs
    $('#host_inputs input').placeholder();


    //Import host CSV
    $('#launch_scan').on('click', function()
    {
        LB_show("<?php echo _('Scan Networks') ?>", '/wizard/extra/scan/select_nets.php', '700', '1000');
    });


    //Import host CSV
    $('#import_host_csv #import_csv').on('click', function()
    {
        LB_show("<?php echo _('Import Assets from CSV') ?>", '/host/import_all_hosts.php?import_type=welcome_wizard_hosts', '600', '900');
    });


    //Select plugin for the host types
    $("#host_type").select2(
    {
        allowClear: true,
        placeholder: "<?php echo _('Select an Asset Type') ?>"
    });


    //Datatables host
    __dt_hosts = $('#table_host_results').dataTable(
    {
        "bProcessing": true,
        "bServerSide": true,
        "bDeferRender": true,
        "iDisplayLength": 10,
        "sAjaxSource": "/ossim/wizard/ajax/get_host.php",
        "sServerMethod": "POST",
        "bLengthChange": false,
        "sPaginationType": "full_numbers",
        "bFilter": true,
        "bJQueryUI": true,
        "aaSorting": [[ 0, "desc" ]],
        "aoColumns": [
            { "bSortable": true,  "sClass": "left", "sWidth": "35%" },
            { "bSortable": false, "sClass": "left", "sWidth": "35%" },
            { "bSortable": false, "sClass": "center", "sWidth": "20%" },
            { "bSortable": false, "sClass": "center", "sWidth": "45px" }
        ],
        "fnCreatedRow": function( nRow, aData, iDataIndex )
        {
            var select = $('<select />',
            {
                'class'   : 'host_type',
                'data-id' : aData['DT_RowId'],
                'change'  : function()
                {
                    change_host_type(this);
                }

            }).append(
                $('<option />').text('').val(''),
                $('<option />').text("<?php echo _('Windows') ?>").val('windows_'),
                $('<option />').text("<?php echo _('Linux') ?>").val('linux_server'),
                $('<option />').text("<?php echo _('Network Device') ?>").val('_networkdevice')
            );

            if (aData[2] == '_')
            {
                select.append(
                    $('<option />').text("<?php echo _('Others') ?>").val('_')
                );
            }

            $("option[value=" + aData[2] + "]", select).prop('selected', true);

            $('td:eq(2)', nRow).html(select);


            select.select2(
            {
                allowClear: true,
                placeholder: "<?php echo _('Select an Asset Type') ?>"
            });

            var _del_icon = $('<img />',
            {
                'src'     : '/ossim/pixmaps/delete.png',
                'class'   : 'delete_small',
                'click'  : function()
                {
                    var msg = "<?php echo _('Are you sure you want to delete this asset?') ?>";

                    av_confirm(msg).done(function()
                    {
                        delete_member(aData['DT_RowId'], 'host')
                    });

                }
            });

            $('td:eq(3)', nRow).html(_del_icon);

        },
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
            "sSearch": "<?php echo _('Search') ?>",
            "sUrl": "",
            "oPaginate":
            {
                "sFirst":    "<?php echo _('First') ?>",
                "sPrevious": "<?php echo _('Previous') ?>",
                "sNext":     "<?php echo _('Next') ?>",
                "sLast":     "<?php echo _('Last') ?>"
            }
        }
    }).fnSetFilteringDelay(600);


}


function load_js_net_list_scan()
{

    $("#gb_b_apply").prop('disabled', (Object.keys(__total_nets_selected).length < 1));

    $('#gb_b_cancel').on("click", function()
    {
        parent.GB_close();

    });

    //Once we click on the scan now, we save the selected networks and the we open the dialog
    $("#gb_b_apply").on("click", function()
    {
        modify_scan_networks();

    });


    //Change the button scan now status depending on the checked networks
    $('#table_net_results').on('change', '.net_input', function()
    {
        var id = $(this).val();

        if ($(this).prop('checked'))
        {
            __total_nets_selected[id] = '1';
        }
        else
        {
            delete __total_nets_selected[id];
        }


        $("#gb_b_apply").prop('disabled', (Object.keys(__total_nets_selected).length < 1));


    });


    //Add new net
    $('#add_net').on('click', function()
    {
        if (!$(this).hasClass('av_b_disabled'))
        {
            var cidr  = $('#net_cidr').val();
            var name  = $('#net_name').val();
            var descr = $('#net_dscr').val();

            insert_new_net(cidr, name, descr);
        }

    });


    //Event to activate the add network button
    $('#net_cidr, #net_name').on('keyup', function()
    {

       var val_1   = $('#net_cidr').val();
       var val_2   = $('#net_name').val();

       if (val_1 != '' && val_2 != '')
       {
           $('#add_net').prop('disabled', false);

       }
       else
       {
           $('#add_net').prop('disabled', true);
       }

    });


    //Placeholder for the network and host inputs
    $('#net_inputs input').placeholder();


    //Datatables net
    __dt_nets = $('#table_net_results').dataTable(
    {
        "bProcessing": true,
        "bServerSide": true,
        "bDeferRender": true,
        "iDisplayLength": 5,
        "sAjaxSource": "/ossim/wizard/ajax/get_net.php",
        "sServerMethod": "POST",
        "bLengthChange": false,
        "sPaginationType": "full_numbers",
        "bFilter": true,
        "bJQueryUI": true,
        "aaSorting": [[ 0, "desc" ]],
        "aoColumns": [
            { "bSortable": false, "sClass": "center", "sWidth": "35px" },
            { "bSortable": true,  "sClass": "left", "sWidth": "25%" },
            { "bSortable": false, "sClass": "left", "sWidth": "25%" },
            { "bSortable": false, "sClass": "left", "sWidth": "20%" },
            { "bSortable": false, "sClass": "left", "sWidth": "20%" },
            { "bSortable": false, "sClass": "center", "sWidth": "45px" }
        ],
        "fnCreatedRow": function( nRow, aData, iDataIndex )
        {
            var net_id = aData['DT_RowId'];

            var _del_icon = $('<img />',
            {
                'src'     : '/ossim/pixmaps/delete.png',
                'class'   : 'delete_small',
                'click'  : function()
                {
                    var msg = "<?php echo _('Are you sure you want to delete this network?') ?>";

                    av_confirm(msg).done(function()
                    {
                        delete_member(net_id, 'net')
                    });
                }
            });

            $('td:eq(5)', nRow).html(_del_icon);

            if (__total_nets_selected[net_id] == 1)
            {
                $('.net_input', nRow).prop('checked', true);
            }


        },
        oLanguage :
        {
            "sProcessing": "&nbsp;<?php echo _('Loading Networks') ?> <img src='/ossim/pixmaps/loading3.gif' align='absmiddle'/>",
            "sLengthMenu": "&nbsp;Show _MENU_ entries",
            "sZeroRecords": "&nbsp;<?php echo _('No matching records found') ?>",
            "sEmptyTable": "&nbsp;<?php echo _('No networks found in the system') ?>",
            "sLoadingRecords": "&nbsp;<?php echo _('Loading') ?>...",
            "sInfo": "&nbsp;<?php echo _('Showing _START_ to _END_ of _TOTAL_ networks') ?>",
            "sInfoEmpty": "&nbsp;<?php echo _('Showing 0 to 0 of 0 networks') ?>",
            "sInfoFiltered": "(<?php echo _('filtered from _MAX_ total networks') ?>)",
            "sInfoPostFix": "",
            "sInfoThousands": ",",
            "sSearch": "<?php echo _('Search') ?>",
            "sUrl": "",
            "oPaginate":
            {
                "sFirst":    "<?php echo _('First') ?>",
                "sPrevious": "<?php echo _('Previous') ?>",
                "sNext":     "<?php echo _('Next') ?>",
                "sLast":     "<?php echo _('Last') ?>"
            }
        }
    }).fnSetFilteringDelay(600);


}

/*  Function to change the host type  */
function change_host_type(op)
{
    var data = {};

    data['id']    = $(op).data('id');
    data['type']  = $(op).val();

    var ctoken = Token.get_token("welcome_wizard");
	$.ajax(
	{
		url: __ajax_path + "wizard_actions_ajax.php?token="+ctoken,
		data: {"action": "change_htype", "data": data},
		type: "POST",
		dataType: "json",
		success: function(data)
		{
    		if (typeof data != 'undefined' && data != null)
    		{
        		if (data.error)
        		{
            		show_notification('wizard_notif', data.msg, 'nf_error', 5000);

            		return false;
        		}

        		$("option[value=_]", op).remove();
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

            show_notification('wizard_notif', errorThrown, 'nf_error', 5000);
		}
	});

}

/* Funtion to delete assets/nets */
function delete_member(id, type)
{
    var data   = {};

    data['id']   = id;
    data['type'] = type;

    var ctoken = Token.get_token("welcome_wizard");
	$.ajax(
	{
		url: __ajax_path + "wizard_actions_ajax.php?token="+ctoken,
		data: {"action": "delete_member", "data": data},
		type: "POST",
		dataType: "json",
		success: function(data)
		{
    		if (typeof data != 'undefined' && data != null)
    		{
        		if (data.error)
        		{
            		show_notification('wizard_notif', data.msg, 'nf_error', 5000);

            		return false;
        		}

        		if (type == 'host')
        		{
        		  __dt_hosts._fnAjaxUpdate();
        		}
        		else if (type == 'net')
        		{
        		  __dt_nets._fnAjaxUpdate();
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

            show_notification('wizard_notif', errorThrown, 'nf_error', 5000);
		}
	});
}

/*  Function to insert a new host  */
function insert_new_host(ip, name, type)
{
    if (ip == '' || name == '')
    {
        return false;
    }

    var data = {};

    data['ip']    = ip;
    data['name']  = name;
    data['type']  = type;

    var ctoken = Token.get_token("welcome_wizard");

	$.ajax(
	{
		url: __ajax_path + "wizard_actions_ajax.php?token="+ctoken,
		data: {"action": "insert_host", "data": data},
		type: "POST",
		dataType: "json",
		success: function(data)
		{
    		if (typeof data != 'undefined' && data != null)
    		{

        		if (data.error)
        		{
            		show_notification('wizard_notif', data.msg, 'nf_error', 5000);

            		return false;
        		}

        		$('#add_host').prop('disabled', false);

        		$('#host_inputs input').val('');

        		$('#host_type').select2("val", "");

        		__dt_hosts._fnAjaxUpdate();

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

            show_notification('wizard_notif', errorThrown, 'nf_error', 5000);
		}
	});
}

/*  Function to insert a new net  */
function insert_new_net(cidr, name, descr)
{
    if (cidr == '' || name == '')
    {
        return false;
    }

    var data = {};

    data['cidr']  = cidr;
    data['name']  = name;
    data['descr'] = descr;

    var ctoken = Token.get_token("welcome_wizard");

	$.ajax(
	{
		url: __ajax_path + "wizard_actions_ajax.php?token="+ctoken,
		data: {"action": "insert_net", "data": data},
		type: "POST",
		dataType: "json",
		success: function(data)
		{
    		if (typeof data != 'undefined' && data != null)
    		{
        		if (data.error)
        		{
            		show_notification('wizard_notif', data.msg, 'nf_error', 5000);

            		return false;
        		}

        		$('#net_inputs input').val('');

                $('#add_net').prop('disabled', true);

        		__dt_nets._fnAjaxUpdate();
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

            show_notification('wizard_notif', errorThrown, 'nf_error', 5000);
		}
	});
}



/*  Function to save the selected networks to scan  */
function modify_scan_networks()
{
    if (Object.keys(__total_nets_selected).length <1)
    {
        show_notification('wizard_notif', "<?php echo _('At least one network is needed.') ?>", 'nf_error', 5000);

        return false;
    }

    var data         = {};
        data['nets'] = __total_nets_selected;


    var ctoken = Token.get_token("welcome_wizard");

	$.ajax(
	{
		url: __ajax_path + "scan_ajax.php?token="+ctoken,
		data: {"action": "scan_networks", "data": data},
		type: "POST",
		dataType: "json",
		success: function(data)
		{
    		if (typeof data != 'undefined' && data != null)
    		{
        		if (data.error)
        		{
            		show_notification('wizard_notif', data.msg, 'nf_error', 5000);

            		return false;
        		}
        		else
        		{
        		    var total = 0;

            		try
            		{
                        total = data.data.total;
                        total = (total < 1) ? 0 : total ;
                    }
                    catch(Err){}

                    var msg = '';

                    if (total == 0)
                    {
                        msg = "<?php echo _('You are about to perform the scan') ?>";
                    }
                    else if (total == 1)
                    {
                        msg = "<?php echo _('The scan you are about to perform will cover 1 IP Address') ?>";
                    }
                    else
                    {
                        msg = "<?php echo _('The scan you are about to perform will cover #### IP Addresses') ?>";
                        msg = msg.replace("####", total);
                    }

                    msg += "<?php echo _(', this may take more than a few minutes. Are you sure you would like to continue?') ?>";

                    av_confirm(msg).done(function()
                    {
                        parent.GB_hide({'start_scan': 1});
                    });

                }

            }
            else
            {
                show_notification('wizard_notif', "<?php echo _('An unexpected error happened. Try again later') ?>", 'nf_error', 5000);
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

            show_notification('wizard_notif', errorThrown, 'nf_error', 5000);
		}
	});
}


function finish_scan()
{
    $.fancybox.close();

    try
    {
        __dt_hosts._fnAjaxUpdate();
    }
    catch(Err){}
}


function close_scan()
{
    $.fancybox.close();
}


function launch_scan_window()
{
    $("#scan_url").trigger('click');
}



/******************************************************************************************/
/******************                Step Deploy Functions               ********************/
/******************************************************************************************/


/*  Function to load the js handlers of this step  */
function load_handler_step_deploy()
{
    $("#tab-list").tabs({
        selected: 0,
        select: function(event, ui)
        {
            var tab_os = $(ui.tab).data('os');

            if (tab_os == 'windows')
            {
                $('#form_domain').show();
                __os = 'windows';

                $('#desc_hids_linux').hide();
                $('#desc_hids_windows').show();
            }
            else
            {
                $('#form_domain').hide();
                __os = 'linux';

                $('#desc_hids_windows').hide();
                $('#desc_hids_linux').show();
                 $('#domain').val('');
            }

            __host_selected = {};

            change_button_status('deploy', false);

            $("#tree").dynatree("destroy");

            InitTree();
        }
    });

    InitTree();

    $("#deploy_url").fancybox(
    {
        'type': 'iframe',
    	'showCloseButton': false,
    	'width': 500,
    	'height': 300,
    	'autoDimensions': false,
    	'centerOnScroll': true,
    	'hideOnOverlayClick': false
    });

    $('#password, #username').on('keyup', function()
    {
        deploy_button_status();
    });


    if (__os == 'linux')
    {
        $('#form_domain').hide();
        $('#desc_hids_linux').show();
    }
    else
    {
        $('#desc_hids_windows').show();
    }


    $('#deploy').on("click", function()
    {
        if (!$(this).hasClass('wizard_off'))
        {
            modify_deploy_hosts();
        }
    });

}


/*  Function to save the selected hosts for the deploy  */
function modify_deploy_hosts()
{
    var host_flag = (Object.keys(__host_selected).length < 1);

    var name      = $('#username').val();
    var pass      = $('#password').val();
    var domain    = $('#domain').val();

    var data      = {};

    if (host_flag || name == '' || pass == '')
    {
        if (host_flag)
        {
            var msg = "<?php echo _('To deploy HIDS, please select at least one host.') ?>";
        }
        else
        {
            var msg = "<?php echo _('To deploy HIDS, please fill Username and Password.') ?>";
        }
        
        show_notification('wizard_notif', msg, 'nf_error', 5000);

        return false;
    }


    data['os']       = __os;
    data['hosts']    = __host_selected;
    data['username'] = name;
    data['password'] = pass;
    
    if (__os == 'windows')
    {
        data['domain'] = domain;
    }
    

    var ctoken = Token.get_token("welcome_wizard");

	$.ajax(
	{
		url: __ajax_path + "deploy_ajax.php?token="+ctoken,
		data: {"action": "hosts_deploy", "data": data},
		type: "POST",
		dataType: "json",
		success: function(data)
		{
    		if (typeof data != 'undefined' && data != null)
    		{
        		if (data.error)
        		{
            		show_notification('wizard_notif', data.msg, 'nf_error', 5000);

            		return false;
        		}
        		else
        		{
                    launch_deploy_window();
                }
            }
            else
            {
                show_notification('wizard_notif', "<?php echo _('An unexpected error happened. Try again later') ?>", 'nf_error', 5000);
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

            show_notification('wizard_notif', errorThrown, 'nf_error', 5000);
		}
	});
}


/*  Function to change the status of the deploy button  */
function deploy_button_status()
{
    var pass   = ($('#password').val() != '');
    var name   = ($('#username').val() != '');
    var hosts  = (Object.keys(__host_selected).length > 0 );

    var active = (pass && name && hosts);

    change_button_status('deploy', active);

}


function launch_deploy_window()
{
    $("#deploy_url").trigger('click');
}


function finish_deploy()
{
    $.fancybox.close();
}


function close_deploy()
{
    $.fancybox.close();
}


function InitTree()
{
    $('#tree').dynatree(
    {
        initAjax:
        {
            url: "ajax/tree.php",
            data: {"action": "nets", "data": {"os" : __os}},
            type: "POST",
            complete: function()
            {
                load_tiptip();
            }
        },
        clickFolderMode: 2,
        selectMode: 2,
        checkbox: true,
        debugLevel: 0,
        noLink: true,
        onClick: function(node, event)
        {
            var target_click = node.getEventTargetType(event);

            if (target_click == 'checkbox' || target_click == "title")
            {
                if (node.data.type == 'host')
                {
                    var id = node.data.host_id;

                    if (!node.bSelected)
                    {
                        __host_selected[id] = id;
                    }
                    else
                    {
                        delete __host_selected[id];
                    }

                    deploy_button_status();
                }

                if (target_click == "title")
                {
                    node.toggleSelect();

                }
            }

        },
        onLazyRead: function(node)
        {
            node.appendAjax(
            {
                url: "ajax/tree.php",
                data: {"action": "hosts", "data": {"os" : __os, "net": node.data.net_id}},
                type: "POST",
                complete: function()
                {
                    load_tiptip();
                }
            });
        }
    });
}


function load_tiptip()
{
    $('.dynatree-title').tipTip(
    {
        delay: 600
    });
}




/******************************************************************************************/
/***************                Step Log Management Functions                *************/
/******************************************************************************************/


/*  Function to load the js handlers of this step  */
function load_handler_step_log()
{

    $('#w_apply').on('click', function()
    {
        $(this).addClass('av_b_processing');

        setTimeout(function()
        {
            av_apply_plugin(apply_plugin_callback, __ajax_path + 'wizard_actions_ajax.php');
            show_section_plugins();

        }, 250);

    });


    $('#prev_screen').on('click', function()
    {
        show_section_software();

    });


    $(document).on('change', '.select_plugin', function()
    {
        check_enable_by_vendor();
    });


    /*
    $('.add_plugin').on('click', function()
    {
        var id = $(this).parents('tr').first().data('host');

        $('#table_' + id).AVplugin_select(
        {
            "vendor_list": __vendor_list
        });

    });
    */
    
    check_enable_by_vendor();

    $('#step_log').on('click', '.view_links', function()
    {
        var id     = $(this).parents('tr').data('host');
        var vendor = $('table.table_plugin_list tbody[data-host="'+id+'"] tr select.vendor option:selected').text();
        var model  = $('table.table_plugin_list tbody[data-host="'+id+'"] tr select.model option:selected').text();
        var versn  = $('table.table_plugin_list tbody[data-host="'+id+'"] tr select.version option:selected').text();
        var cpe    = ''

        if (is_internet_available())
        {
            cpe    = $(this).parents('tr').data('cpe');
        }

        var url    = 'extra/instrucctions.php?vendor='+encodeURIComponent(vendor)+'&model='+encodeURIComponent(model)+'&version='+encodeURIComponent(versn)+'&cpe='+encodeURIComponent(cpe);

        av_window_open(url,
        {
            width: 800,
            height: 750,
            title: '<?php echo _('Instruction to forward logs')?>'
        })

        return false;
    });
}


function show_section_software()
{
    $('#prev_step').show();
    $('#prev_screen').hide();

    $('#second_screen').hide("slide", { direction: "right" }, 500, function()
    {
        $('#screen_2_subtitle').hide();
        $('#screen_1_subtitle').show();

        $('#first_screen').show();

    });

    clearTimeout(__timeout);

    check_enable_by_vendor();

    change_button_status('next_step', 0);
}


function show_section_plugins()
{
    $('#prev_step').hide();
    $('#prev_screen').show();

    draw_active_device_table();

    $('#first_screen').hide();

    $('#screen_1_subtitle').hide();
    $('#screen_2_subtitle').show();

    $('#second_screen').show("slide", { direction: "right" }, 600);

    net_devices_activity();

}

function check_enable_by_vendor()
{
    var enable = false;

    $('select.vendor').each(function()
    {
        if ($(this).val() != '')
        {
            enable = true
            return false;
        }

    });

    if (enable)
    {
        $('#w_apply').prop('disabled', false);
    }
    else
    {
        $('#w_apply').prop('disabled', true);
    }

}

function draw_active_device_table()
{

    var device_table = $('#log_devices_list tbody');
    
    device_table.empty();

    $('#net_devices_list tbody tr').each(function()
    {
        var id     = $(this).data('host');
        var name   = $(this).data('name');
        var ips    = $(this).data('ip');

        $('.plugin_list tr', this).each(function()
        {
            var vendor = $('.vendor option:selected', this).text();
            var model  = $('.model option:selected', this).text();

            var type   = $.trim(vendor + " " + model);

            if (type == '')
            {
                return true;
            }

            var cpe = get_cpe_from_software(this);
            var led = '';

            var row = $('<tr>', {
                'data-host': id,
                'data-cpe' : cpe
            }).appendTo(device_table);


            $('<td>', {
                "html": '<div class="device_name">'+ name + ' (' + ips +')</div>'
            }).appendTo(row);

            $('<td>', {
                "html": '<div class="device_type">'+ type +'</div>'
            }).appendTo(row);

            led = draw_led('plugin');

            $('<td>', {
                "html": led
            }).appendTo(row);

            led = draw_led('host');

            $('<td>', {
                "html": led
            }).appendTo(row);

            $('<td>', {
                "html": "<a href='javascript:;' target='_blank' class='view_links av_l_main'><?php echo _('Instruction to forward logs') ?></a>"
            }).appendTo(row);

        });

    });

}

/*  Function to get if the network devices are getting events  */
function net_devices_activity()
{
    var ctoken = Token.get_token("welcome_wizard");
	$.ajax(
	{
		url: __ajax_path + "wizard_actions_ajax.php?token="+ctoken,
		data: {"action": "net_activity"},
		type: "POST",
		dataType: "json",
		beforeSend: function()
		{
    		$('.wizard_led').removeClass('led_gray led_red led_green');
    		$('.wizard_led_loader').show();
		},
		success: function(data)
		{
    		if (typeof data != 'undefined' && data != null)
    		{
        		if (data.error)
        		{
        		    led_error();

            		show_notification('wizard_notif', data.msg, 'nf_error', 5000);

            		return false;
        		}

                var plugins = data.data.plugins;
                var status  = data.data.status;
                var active  = false;

                if (status == 0)
                {
                    $('led_plugin').removeClass('led_gray led_red led_green');

                    $('.loader_host').hide();
                    $('.led_host').addClass('led_gray');

                    __timeout = setTimeout("net_devices_activity()", 10000);

                    return false;
                }

                $('.wizard_led_loader').hide();

                $('#log_devices_list tbody tr').each(function()
                {
                    var id  = $(this).data('host');
                    var cpe = $(this).data('cpe');

                    $('.led_plugin',this).addClass('led_green');

                    if (typeof(plugins[id][cpe]) != 'undefined' && plugins[id][cpe] == 1)
                    {
                        $('.led_host',this).addClass('led_green');
                        active = true;
                    }
                    else
                    {
                        $('.led_host',this).addClass('led_gray');
                    }


                });

                change_button_status('next_step', active);

                __timeout = setTimeout("net_devices_activity()", 10000);

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

            led_error();

            show_notification('wizard_notif', errorThrown, 'nf_error', 5000);

		}
	});
}


/*  Function to click on apply button to apply the selected plugins selected in the combo boxes  */
function apply_plugin_callback(data)
{
    $('#w_apply').removeClass('av_b_processing');

    if (typeof data != 'undefined' && data != null)
	{
		if (data.error)
		{
    		show_notification('wizard_notif', data.msg, 'nf_error', 5000);

    		return false;
		}

    }
    else
    {
        show_notification('wizard_notif', "<?php echo _('An unexpected error happened. Try again later') ?>", 'nf_error', 5000);
    }
}

function get_cpe_from_software(elem)
{
    var vendor  = $('select.vendor',  elem).val();
    var model   = $('select.model',   elem).val();
    var version = $('select.version', elem).val();

    if (version != '' && version != undefined)
    {
        return version;
    }
    else if (model != '' && model != undefined)
    {
        return model;
    }
    else if (vendor != '' && model != undefined)
    {
        return vendor;
    }

    return '';
}


function draw_led(type)
{
    var div = $('<div>', {
        "style" : 'position:relative'
    });

    var span = $('<span>', {
        'class' : 'wizard_led led_gray led_' + type,
        'html'  : "&nbsp;"
    }).appendTo(div);

    var img = $('<img>', {
        'class' : 'wizard_led_loader loader_' + type,
        'src'   : "/ossim/pixmaps/loader.gif"
    }).appendTo(div);

    return div;

}



/******************************************************************************************/
/*********************                Step OTX Functions                *******************/
/******************************************************************************************/


/*  Function to load the js handlers of this step  */
function load_handler_step_otx()
{

    show_otx_step_1();

    $('#w_otx_token input').placeholder();

    $('#w_otx_next').on('click', function()
    {
        get_otx_user();
    });

    $('#w_otx_skip').on('click', function()
    {
        show_otx_step_2(true);
    });

    $('#otx_back').on('click', function()
    {
        show_otx_step_1();
    });

    $('#w_otx_token').on('input keyup blur', function(e)
    {
        if($(this).val() != '')
        {
            $('#w_otx_next').prop('disabled', false);
        }
        else
        {
            $('#w_otx_next').prop('disabled', true);
        }

    });

    $('#b_get_otx_token').on('click', function()
    {
        var url = "https://www.alienvault.com/my-account/customer/signup-or-thanks/?ctype=<?php echo (Session::is_pro()) ? 'usm' : 'ossim' ?>";

        av_window_open(url,
        {
            width: 800,
            height: 750,
            title: 'otxwindow'
        })
    });
    
    $('#otx_data_link').on('click', function()
    {
        LB_show("<?php echo _('Open Threat Exchange Sample Data') ?>", '/wizard/extra/otx_data.php', '500', '750'); 
    });

}


function show_otx_step_1()
{
    $('#otx_back, .finish_wizard, #w_otx_step_2').hide();

    $('#w_otx_next, #w_otx_skip, #w_otx_step_1, #prev_step').show();
}


function show_otx_step_2(skip)
{
    $('#w_otx_next, #w_otx_skip, #w_otx_step_1, #prev_step').hide();

    $('#w_otx_2_skip, #w_otx_2_register').hide();

    if (skip)
    {
        $('#w_otx_2_skip').show();
    }
    else
    {
        $('#w_otx_2_register').show();
    }

    $('#otx_back, .finish_wizard, #w_otx_step_2').show();

}

function get_otx_user()
{
    var data      = {};
    data['token'] = $('#w_otx_token').val();

    var ctoken = Token.get_token("welcome_wizard");
	$.ajax(
	{
		url: __ajax_path + "wizard_actions_ajax.php?token="+ctoken,
		data: {"action": "get_otx_user", "data": data },
		type: "POST",
		dataType: "json",
		beforeSend: function()
		{
    		$('#w_otx_next').addClass('av_b_processing');
		},
		success: function(data)
		{
    		if (typeof data != 'undefined' && data != null)
    		{
    		    $('#w_otx_next').removeClass('av_b_processing');

        		if (data.error)
        		{
            		show_notification('wizard_notif', data.msg, 'nf_error', 5000);

            		return false;
        		}

                show_otx_step_2(false);
            }

		},
		error: function(XMLHttpRequest, textStatus, errorThrown)
		{
		    $('#w_otx_next').removeClass('av_b_processing');

            //Checking expired session
    		var session = new Session(XMLHttpRequest, '');
            if (session.check_session_expired() == true)
            {
                session.redirect();
                return;
            }

            show_notification('wizard_notif', errorThrown, 'nf_error', 5000);

		}
	});
}


/******************************************************************************************/
/********************                 Common Functions                 ********************/
/******************************************************************************************/


/*  Function to change the colour of the leds  Step 1_2 && 2_4 */
function led_error(disable_next)
{
    $('.wizard_led_loader').hide();
    $('.wizard_led').addClass('led_red');

    if (typeof disable_next != 'undefined' && disable_next == true)
    {
        change_button_status('next_step', false);
    }

}