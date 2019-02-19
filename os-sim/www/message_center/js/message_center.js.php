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
$todelete = $av_menu->check_perm("message_center-menu", "MessageCenterDelete");
?>


/************************
 ****** Functions *******
 ************************/

(function ($)
{
    // Notification details
    $.fn.notification_details = function ()
    {
        var $_notification_title, $_notification_date, $_notification_description, $_notification_actions,
            $_notification_alternative_actions;

        var __self = this;

        $_notification_title = $('<div>', {
            'class': 'notification_title'
        });

        $_notification_date = $('<div>', {
            'class': 'notification_date'
        });

        $_notification_description = $('<div>', {
            'class': 'notification_description'
        });

        $_notification_actions = $('<div>', {
            'class': 'notification_actions'
        });

        $_notification_alternative_actions = $('<div>', {
            'class': 'notification_alternative_actions'
        });

        $_notification_title.appendTo($(this));
        $_notification_date.appendTo($(this));
        $_notification_description.appendTo($(this));
        $_notification_actions.appendTo($(this));
        $_notification_alternative_actions.appendTo($(this));

        this.show = function (title, date, description, actions, alternative_actions)
        {
            __self.fadeOut(100, function ()
            {
                $_notification_title.html(title);
                $_notification_date.text(date);
                $_notification_description.html(description);
                $_notification_actions.html(actions);
                $_notification_alternative_actions.html(alternative_actions);

                // Get direct system notifications actions
                var direct_notifications = <?php echo json_encode(Av_routing::get_actions_by_type('direct', 'SN')); ?>;
                var direct_notifications_actions = $.map(direct_notifications, function (element, action)
                {
                    return action;
                });
                var direct_actions_pattern = new RegExp(direct_notifications_actions.join('|'));

                // Get menu system notifications actions
                var menu_notifications = <?php echo json_encode(Av_routing::get_actions_by_type('menu', 'SN')); ?>;
                var menu_notifications_actions = $.map(menu_notifications, function (element, action)
                {
                    return action;
                });
                var menu_actions_pattern = new RegExp(menu_notifications_actions.join('|'));

                $('a', __self).off('click').on('click', function (event)
                {
                    event.preventDefault();
                    event.stopPropagation();

                    if (direct_actions_pattern.test($(this).attr('href')))
                    {
                        var url   = $(this).attr('href');
                        var title = '';

                        GB_close();
                        GB_show(title, url, '850', '850');
                    }
                    else if (menu_actions_pattern.test($(this).attr('href')))
                    {
                        var go_to = function(url)
                        {
                            setTimeout("top.frames['main'].document.location.href = '" + url + "'", 200);
                        };

                        go_to($(this).attr('href'));
                    }
                    else
                    {
                        window.open($(this).attr('href'), '_blank');
                    }

                    return false;
                });

                __self.fadeIn(100);
            });
        };

        this.hide = function ()
        {
            __self.fadeOut(100);
        };
    };

    // Notifications filters
    $.fn.notifications_filters = function ()
    {
        var filter_data = {};

        var __self = this;

        $.each(__self.find('.nf_filter'), function ()
        {
            var filter_key = $(this).attr('data-filter-type');
            var filter_value = $(this).attr('value');

            // If key is not set in object, add it as array
            if (!filter_data.hasOwnProperty(filter_key))
            {
                filter_data[filter_key] = [];
            }

            // Add checked filters to object
            if ($(this).is(':checked'))
            {
                filter_data[filter_key].push(filter_value);
            }

            // Add on change event
            $(this).on('change', function ()
            {
                // If checked add to array, if not, delete from it
                $(this).is(':checked')
                    ? ('radio' == $(this).attr('type')
                        ? filter_data[filter_key] = [filter_value]
                        : filter_data[filter_key].push(filter_value))
                    : filter_data[filter_key].splice($.inArray(filter_value, filter_data[filter_key]), 1);
            });
        });

        this.get_filters = function()
        {
            return filter_data;
        }
    }
})(jQuery);


/**
 * Execute AJAX call
 *
 * @param url
 * @param data
 * @param $msg_box
 * @param callbacks
 */
function execute_ajax_call(url, data, $msg_box, callbacks)
{
    var server_params = data;

    return $.ajax({
        type    : 'POST',
        dataType: 'json',
        url     : url,
        data    : server_params

    }).fail(function (XMLHttpRequest, textStatus, errorThrown)
    {
        //Checking expired session
        var session = new Session(XMLHttpRequest, '');

        if (session.check_session_expired() == true)
        {
            session.redirect();
            return;
        }

        var error_msg = XMLHttpRequest.responseText;

        if (typeof server_params.action != 'undefined')
        {
            if (error_msg.match(/nf_warning/i))
            {
                $msg_box.html(error_msg).fadeIn(2000);
            }
            else
            {
                $msg_box.html(notify_error(error_msg)).fadeIn(2000);
            }
        }
    }).done(function (data)
    {
        var cnd_1 = (typeof(data) == 'undefined' || data == null);
        var cnd_2 = (typeof(data) != 'undefined' && data != null && data.status != 'OK');

        if (cnd_1 || cnd_2)
        {
            var error_msg = (cnd_1 == true) ? av_messages['unknown_error'] : JSON.stringify(data.data);
            error_msg = '<div style="padding-left: 10px;">' + error_msg + '</div>';

            $msg_box.html(notify_error(error_msg)).fadeIn(2000);
        }
        else
        {
            if (callbacks.length != 0)
            {
                for (var i = 0; i < callbacks.length; i++)
                {
                    if (typeof callbacks[i] == 'function')
                    {
                        callbacks[i](data);
                    }
                }
            }
        }
    });
}

/**
 * Call controller to do an action
 *
 * @param data
 * @param $msg_box
 * @param callbacks
 */
function do_action(data, $msg_box, callbacks)
{
    data['token'] = Token.get_token('notification_form');

    execute_ajax_call('../controllers/notification_actions.php', data, $msg_box, callbacks);
}


/*****************************
 ****** Document Ready  ******
 *****************************/

$(document).ready(function ()
{
    /************************
     ****** Variables  ******
     ************************/

    // Selectors
    var $nf_search = $('#nf_search');
    var $nf_unread = $('#nf_unread');
    var $av_info = $('#av_info');
    var $notifications_filters = $('#notifications_filters');
    var $notification_details = $('#notification_details');
    var $_nf_filter = $('.nf_filter');

    // Vars
    var mark_as_read_timeout;
    var mark_as_read_messages_ids = [];
    var display_start = 0;
    var selection_type =  'manual';
    var message_total_count = 0;
    var  $chk_all_rows = $('#chk-all-rows');
    var container = $("#notifications_list");
    var selector = "[name='status_message_id[]']:checkbox";
    var selector_checked = selector+":checked";
    var pull_selected_msg = [] ;


    /************************
     ****** Functions  ******
     ************************/

    window.clean_read_messages = function()
    {
        mark_as_read_messages_ids = [];
    };

    /**
     * Set stats beside inputs filters
     */
    var set_stats = function ()
    {
        var fill_stats = function (data)
        {
            if (data.data.length != 0)
            {
                var fill = function (stats)
                {
                    $.each(stats, function (key, value)
                    {
                        // If value is a object, recursively call this function
                        if ($.type(value) == 'object')
                        {
                            fill(value);
                        }
                        // Set stats
                        {
                            if (key == 'unread' && $nf_search.val() == '')
                            {
                                update_notification_bubble(value, false);
                            }

                            $('[data-stat=nf_' + key + ']').text('(' + value + ')');
                        }
                    });
                };

                fill(data.data);
            }
            else
            {
                $('[data-stat]').text('(0)');
            }
        };

        var data = {
            'search': $nf_search.val()
        };

        if ($nf_unread.is(':checked'))
        {
            data['only_unread'] = true;
        }

        execute_ajax_call('../providers/get_notifications_stats.php', data, $av_info, [fill_stats]);
    };

    // Initialize filters and details
    $notifications_filters.notifications_filters();
    $notification_details.notification_details();


    /**************************************
     ****** Datatables Configuration ******
     **************************************/

    window.table_data = $('.table_data').dataTable({
        'sDom'           : '<"top"l>rt<"bottom"p>', // '<"top"l>rt<"bottom"ip>',
        'bProcessing'    : true,
        'bServerSide'    : true,
        'sAjaxSource'    : '../providers/get_notifications.php',
        'sAjaxDataProp'  : 'data',
        'bScrollInfinite': true,
        'bScrollCollapse': true,
        'sScrollY'       : '240px',
        'bDeferRender'   : true,
        'oScroller'      : {
            'loadingIndicator': true
        },
        //'bPaginate'        : true,
        //'sPaginationType'  : 'full_numbers',
        'iDisplayLength' : 10,
        'bLengthChange'  : true,
        'bFilter'        : true,
        'bJQueryUI'      : true,
        'aaSorting'      : [[0, 'desc']],
        'aoColumns'      : [
            <?php if ($todelete) { ?>
            {'bSortable': false, sWidth: '6%'},
            <?php } ?>
            {'bSortable': true, sWidth: '15%'},
            {'bSortable': true, sWidth: '55%'},
            {'bSortable': true, sWidth: '12%'},
            {'bSortable': true, sWidth: '12%'},
        ],
        'oLanguage'      : {
            'sProcessing'    : '<?php echo _('Loading') ?>...',
            'sLengthMenu'    : 'Show _MENU_ entries',
            'sZeroRecords'   : '<?php echo _('No matching messages found') ?>',
            'sEmptyTable'    : '<?php echo _('No messages available') ?>',
            'sLoadingRecords': '<?php echo _('Loading') ?>...',
            'sInfo'          : '<?php echo _('Showing _START_ to _END_ of _TOTAL_ messages') ?>',
            'sInfoEmpty'     : '<?php echo _('Showing 0 to 0 of 0 messages') ?>',
            'sInfoFiltered'  : '(<?php echo _('filtered from _MAX_ total messages') ?>)',
            'sInfoPostFix'   : '',
            'sInfoThousands' : ',',
            'sSearch'        : '<?php echo _('Search') ?>:',
            'sUrl'           : '',
            'oPaginate'      : {
                'sFirst'   : '<?php echo _('First')?>',
                'sPrevious': '<?php echo _('Previous')?>',
                'sNext'    : '<?php echo _('Next')?>',
                'sLast'    : '<?php echo _('Last')?>'
            }
        },
        fnServerParams   : function (aoData)
        {
            // Extra filters to send to server
            var filter_data = $notifications_filters.get_filters();

            $.each(filter_data, function (filter_key, filter_value)
            {
                aoData.push({name: filter_key, value: filter_value});
            });

            var is_unread_filter = ($.inArray('unread', filter_data.nf_view) != -1);

            if (is_unread_filter)
            {
                $.each(aoData, function (index, property)
                {
                    if (property.name == 'iDisplayStart')
                    {
                        display_start = aoData[index].value - mark_as_read_messages_ids.length;

                        aoData[index].value = display_start;

                        if (display_start < 0)
                        {
                            aoData[index].value = 0;
                            display_start = 0;

                            clean_read_messages();
                        }
                    }
                });
            }
        },
        fnServerData     : function (sSource, aoData, fnCallback, oSettings)
        {
            oSettings.jqXHR = execute_ajax_call(sSource, aoData, $av_info, [set_stats, fnCallback, chk_current_messag]);
            oSettings.jqXHR.fail(function (data)
            {
                //DataTables Stuffs
                var json = $.parseJSON('{"sEcho": ' + aoData[0].value + ', "iTotalRecords": 0, "iTotalDisplayRecords": 0, "data": "" }');

                fnCallback(json);
                $('[data-stat]').text('(0)');
            });
        },
        fnPreDrawCallback : function (oSettings)
        {
            // This is to show "showing x of x" information correctly with infinite scroll enabled
            oSettings._iRecordsTotal += mark_as_read_messages_ids.length;
            oSettings._iRecordsDisplay += mark_as_read_messages_ids.length;
        },
        fnDrawCallback     : function (oSettings)
        {
            message_total_count = oSettings._iRecordsTotal;
            clearTimeout(mark_as_read_timeout);


            if (display_start == 0)
            {
                $notification_details.hide();
            }

            $($chk_all_rows).click(function() {
                rows_action();
            });

            container.on("change",":checkbox",function() {
                if (container.find(selector_checked).length) {
                    show_selected_count();
                    $("#button_action").prop('disabled', false).removeClass('disabled av_b_disabled');
                } else {
                    $("#button_action").prop('disabled', true).addClass('disabled av_b_disabled');
                }
            });
            container.find(selector).change();
            $('#delete').off('click').on('click', function ()
            {
                if(selection_type == 'all') {
                    var data = {
                        'status_message_id[]': '',
                        'action'              : 'set_suppressed',
                        'delete_all'          :  true,
                        'message_total_count' :  message_total_count

                    };
                    selection_type = 'manual';
                }else {
                    var data = {
                        'status_message_id[]': container.find(selector_checked).map(function(){
                            return $(this).val();
                        }).get(),
                        'action'             : 'set_suppressed'
                    };
                }
                var remove_row = function ()
                {
                    table_data.fnDraw();
                };
                var msg_confirm = '<?php echo _('Are you sure you would like to delete this message(s)?') ?>';
                var keys        = {
                    yes: "<?php echo _('Yes') ?>",
                    no: "<?php echo _('No') ?>"
                };

               av_confirm(msg_confirm, keys).done(function () {
                   do_action(data, $av_info, [remove_row]);
               });
            });
        },
        fnRowCallback    : function (nRow, aData)
        {
            var $row = $(nRow);
            var message_id = aData.DT_RowId;
            <?php if ($todelete) { ?>
            $('td:eq(0)', nRow).html("<input type='checkbox' name='status_message_id[]' value='"+message_id+"'/>");
            <?php } ?>
            if (false == aData.viewed)
            {
                $row.addClass('unread');
            }

            if ($.inArray($row.attr('id'), mark_as_read_messages_ids) != -1)
            {
                $row.removeClass('unread');
            }

            $('td:eq(0),td:eq(1),td:eq(2),td:eq(3)', nRow).off('click').on('click', function ()
            {
                clearTimeout(mark_as_read_timeout);

                // Mark notification as viewed after 3 seconds of select it
                if ($row.hasClass('unread'))
                {
                    mark_as_read_timeout = setTimeout(function ()
                    {
                        if ($row.hasClass('selected'))
                        {
                            var action_data = {
                                action           : 'set_viewed',
                                status_message_id: message_id
                            };

                            var mark_viewed = function()
                            {
                                $row.removeClass('unread');
                                mark_as_read_messages_ids.push(message_id);
                            };

                            do_action(action_data, $av_info, [mark_viewed, set_stats]);
                        }
                    }, 3000);
                }

                if ($row.hasClass('selected'))
                {
                    $row.removeClass('selected');
                    $notification_details.hide();
                }
                else
                {
                    table_data.$('tr.selected').removeClass('selected');
                    $row.addClass('selected');

                    $notification_details.show(aData[1], aData[0], aData['description'], aData['actions'], aData['alternative_actions']);
                }
            });
        }
    });

    function rows_action() {

        if ($($chk_all_rows).is(':checked')) {
            chk_all_messag();
            $('#selectall').css('visibility','visible');
        } else {
            $(".dataTable :checkbox").prop('checked', false);
            $('#selectall').css('visibility','hidden');
        }
        show_selected_count();

    }

    function show_selected_count() {

        pull_selected_msg = container.find(selector_checked).map(function () {
            return $(this).val();
        }).get();

        var message_current_count = pull_selected_msg.length;
        $('#selectall > span').text( message_current_count);
        $('#selectall a span').text(message_total_count);

        if(message_current_count == message_total_count) {
            $('#selectall').css('visibility','hidden');
        }
    }

    function chk_current_messag () {

        if(pull_selected_msg.length > 0){
            $("#button_action").prop('disabled', false).removeClass('disabled av_b_disabled');
        }
        if (  selection_type == 'all') {
            chk_all_messag();
            return;
        }

        $(".dataTable :checkbox" ).each(function() {
            if ($.inArray( $(this).val() , pull_selected_msg ) != -1) {
                $(this).prop('checked', true);
            }
        });

    }
    function chk_all_messag  () {
        $(".dataTable :checkbox").prop('checked', true);
    }

    $('#selectall a').on('click', function (){
        $('#selectall').css('visibility','hidden');
        selection_type = 'all';
        chk_all_messag();
        return false;
    });
    // Datatables search box
    var search_timeout = false;
    $nf_search.on('input', function ()
    {
		clearTimeout(search_timeout);
		
		var that = this;
		
		search_timeout = setTimeout(function()
		{
			table_data.fnFilter($(that).val());
		}, 400);
        
    });

    // Redraw table on new filter selection
    $_nf_filter.on('change', function ()
    {
        table_data.fnDraw();
    });

    // Disables scroll on parent window
    $('.dataTables_scrollBody').on('mouseenter', function()
    {
        $("body", parent.document).on('scroll touchmove mousewheel', function(e)
		{
			e.preventDefault();
			e.stopPropagation();
			return false;
		});
        
    }).on('mouseleave', function()
    {
        $("body", parent.document).off('scroll touchmove mousewheel');
    })

});
