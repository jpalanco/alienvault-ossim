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


//Messages to show
var __messages = {
    "delete"         : "<?php echo Util::js_entities(_('Are you sure you want to delete this scan?'))?>",
    "deleting"       : "<?php echo _('Deleting scan')?> ...",
    'enable'         : "<?php echo Util::js_entities(_('Schedule scan will be enabled. Do you want to continue?'))?>",
    'enabling'       : "<?php echo _('Enabling scan')?> ...",
    'disable'        : "<?php echo Util::js_entities(_('Schedule scan will be disabled. Do you want to continue?'))?>",
    'disabling'      : "<?php echo _('Disabling scan')?> ...",
    "confirm_yes"    : "<?php echo _('Yes')?>",
    "confirm_no"     : "<?php echo _('No')?>",
    "unknown_error"  : "<?php echo _('Sorry, operation was not completed due to an error when processing the request. Please try again')?>"
};


/**************************************************************************
 ***************************  DRAW FUNCTIONS  *****************************
 **************************************************************************/

function load_schedule_scans(s_type)
{
    var aaSorting = [[0, "asc"]];

    var aoColumns = [
        { "bSortable": true,  "sClass": "left" },
        { "bSortable": true,  "sClass": "left" },
        { "bSortable": true,  "sClass": "left", "bVisible": true},
        { "bSortable": true,  "sClass": "left" },
        { "bSortable": false, "sClass": "left" },
        { "bSortable": false, "sClass": "center", "sWidth": "60px" }
    ];

    var fnServerParams = [
        { "name": "s_type", "value": s_type},
    ];

    var oLanguage = {
        "sProcessing": "<?php echo _('Loading')?>...",
        "sLengthMenu": "Show _MENU_ scan tasks",
        "sZeroRecords": "<?php echo _('No scan tasks found for this criteria')?>",
        "sEmptyTable": "<?php echo _('No scan tasks found')?>",
        "sLoadingRecords": "<?php echo _('Loading') ?>...",
        "sInfo": "<?php echo _('Showing _START_ to _END_ of _TOTAL_ scan tasks')?>",
        "sInfoEmpty": "<?php echo _('Showing 0 to 0 of 0 scan tasks')?>",
        "sInfoFiltered": "(<?php echo _('filtered from _MAX_ total scan tasks')?>)",
        "sInfoPostFix": "",
        "sInfoThousands": ",",
        "sSearch": "<?php echo _('Search')?>:",
        "sUrl": "",
        "oPaginate": {
            "sFirst":    "<?php echo _('First')?>",
            "sPrevious": "<?php echo _('Previous')?>",
            "sNext":     "<?php echo _('Next')?>",
            "sLast":     "<?php echo _('Last')?>"
        }
    };


    $('#table_data_s_scan').dataTable({
        "bProcessing": true,
        "bServerSide": false,
        "bDeferRender": false,
        "sAjaxSource": '/ossim/av_schedule_scan/providers/dt_schedule_scan.php',
        "fnServerParams": function (aoData) {
            $.each(fnServerParams, function(index, value) {
                aoData.push(value);
            });
        },
        "sServerMethod" : "POST",
        "iDisplayLength" : 8,
        "sPaginationType" : "full_numbers",
        "bLengthChange" : false,
        "bJQueryUI" : true,
        "aaSorting" : aaSorting,
        "aoColumns" : aoColumns,
        "oLanguage" : oLanguage,
        "fnInitComplete": function(oSettings)
        {
            //Dropdown actions
            $('[data-bind="new_s_scan"]').off('click').on('click', function(){

                //Params
                switch (s_type)
                {
                    case 'nmap':
                        var s_title = "<?php echo _('Schedule New Asset Scan')?>";
                    break;

                    case 'wmi':
                        var s_title = "<?php echo _('Schedule New WMI Scan')?>";
                    break;
                }

                var params = {
                    "url"   : "/ossim/av_schedule_scan/views/schedule_scan_form.php?s_type=" + s_type,
                    "title" : s_title
                };

                execute_callback('new_scan', params);
            });
        },
        "fnRowCallback" : function(nRow, aData, iDrawIndex, iDataIndex)
        {
            __create_actions(nRow, aData);
        },
        "fnServerData": function (sSource, aoData, fnCallback, oSettings)
        {
            oSettings.jqXHR = $.ajax(
            {
                "dataType": 'json',
                "type": "POST",
                "url": sSource,
                "data": aoData,
                "success": function (json)
                {
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

                    var json = $.parseJSON('{"sEcho": 0, "iTotalRecords": 0, "iTotalDisplayRecords": 0, "aaData": "" }')
                    fnCallback(json);
                }
            });
        }
    });
 }


function reload_schedule_scans()
{
    try
    {
        var dt = $('#table_data_s_scan').dataTable();

            dt.fnReloadAjax();
    }
    catch(Err){}
}



 /******************************************************
  *****************  HELPER FUNCTIONS  *****************
  ******************************************************/


function GB_onclose(url, params)
{
    if (typeof params != 'undefined')
    {
        notify(params.msg, 'nf_success', true);
    }

    $('#avi_container').html('<img class="img_loading" src="/ossim/pixmaps/loading.gif"/>');
    $('.img_loading').css({"display" : "block", "margin" : "150px auto"});

    reload_schedule_scans();
}


function __create_actions(nRow, aData)
{
    //Enable/Disable scan
    $("td:nth-last-child(2)", nRow).empty();

    var scan_enabled = (aData['DT_RowData']['enabled'] == 0) ? false : true;

    var div = $('<div>',
    {
        'class' : 'rs_t_scan toggle-modern',
        'id'    : 'rs_' + aData['DT_RowId']
    }).appendTo($("td:nth-last-child(2)", nRow));

    $('.rs_t_scan', nRow).toggles({
        "text" : {
            "on"  : '<?php echo _('Yes')?>',
            "off" : '<?php echo _('No')?>'
        },
        "on" : scan_enabled,
        "width" : 50, // width used if not set in css
        "height" : 18, // height if not set in css
    });

    $('.rs_t_scan', nRow).on('toggle', function (e, scan_enabled) {

        var __action = (scan_enabled == true) ? 'enable_scan' : 'disable_scan';

        //Params
        var params = {
            "s_type"  : aData['DT_RowData']['s_type'],
            "task_id" : aData['DT_RowId']
        };

        execute_callback(__action, params);
    });


    $("td:last-child", nRow).empty();

    //Edit scan
    $('<img></img>',
    {
        "class" : "img_action",
        "src"   : "/ossim/pixmaps/edit.png",
        'click'  : function(e)
        {
            e.stopPropagation();

            //Params
            switch (aData['DT_RowData']['s_type'])
            {
                case 'nmap':
                    var s_title = "<?php echo _('Edit Asset Scan')?>";
                break;

                case 'wmi':
                    var s_title = "<?php echo _('Edit WMI Scan')?>";
                break;
            }

            var params = {
                "s_type" : aData['DT_RowData']['s_type'],
                "url"    : "/ossim/av_schedule_scan/views/schedule_scan_form.php?task_id=" +  aData['DT_RowId'] + "&s_type=" + aData['DT_RowData']['s_type'],
                "title"  : s_title
            };

            execute_callback('edit_scan', params);
        }
    }).appendTo($("td:last-child", nRow));


    //Delete scan
    $('<img></img>',
    {
        "class" : "img_action",
        "src"   : "/ossim/pixmaps/delete-big.png",
        'click'  : function(e)
        {
            e.stopPropagation();

            //Params
            var params = {
                "s_type"  : aData['DT_RowData']['s_type'],
                "task_id" : aData['DT_RowId']
            };

            execute_callback('delete_scan', params);
        }
    }).appendTo($("td:last-child", nRow));
}



/**************************************************************************
 *************************  ACTION FUNCTION   *****************************
 **************************************************************************/

function execute_callback(action, params)
{
    if (action == 'new_scan' || action == 'edit_scan')
    {
        GB_show(params['title'], params['url'], '550', '570');
    }
    else
    {
        switch (action)
        {
            case 'enable_scan':
                var confirm_msg = __messages.enable;
            break;

            case 'disable_scan':
                var confirm_msg = __messages.disable;
            break;

            case 'delete_scan':
                var confirm_msg = __messages.delete;
            break;
        }

        if (action == 'delete_scan')
        {
            var confirm_keys = { "yes" : __messages.confirm_yes, "no" : __messages.confirm_no};

            av_confirm(confirm_msg, confirm_keys).done(function(){

                do_action(action, params);
            });
        }
        else
        {
            do_action(action, params);
        }
    }
}



function do_action(action, params)
{
    //AJAX data
    var a_data = params;
        a_data["action"] = action;
        a_data["token"]  = Token.get_token('ss_form');

    switch (action)
    {
        case 'enable_scan':
            var msg_action = __messages.enabling;
        break;

        case 'disable_scan':
            var msg_action = __messages.disabling;
        break;

        case 'delete_scan':
            var msg_action = __messages.deleting;
        break;
    }


    $.ajax({
        type: "POST",
        url: "/ossim/av_schedule_scan/controllers/schedule_actions.php",
        data: a_data,
        dataType: 'json',
        beforeSend: function(xhr) {

            $('#av_msg_info').remove();

            show_loading_box('ss_container', msg_action, '');
        },
        error: function(data){

            hide_loading_box();

            //Check expired session
            var session = new Session(xhr.responseText, '');

            if (session.check_session_expired() == true)
            {
                session.redirect();

                return;
            }

            var __error_msg = __messages.unknown_error;

            if (typeof(xhr.responseText) != 'undefined' && xhr.responseText != '')
            {
                __error_msg = xhr.responseText;
            }

            var __style = 'width: 70%; text-align:center; margin:0px auto;';
            show_notification(__msg_container, __error_msg, 'nf_error', 15000, true, __style);

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

            //Reload list
            if (action == 'delete_scan')
            {
                reload_schedule_scans();
            }

            var __success_msg = data.data;
            notify(__success_msg, 'nf_success', true);

            window.scrollTo(0,0);
        }
    });
}
