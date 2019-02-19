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

var timer = null;
var showing_box = false;

function show_backup_status()
{
    var form_data = 'action=status';
     
    $.ajax({
        type: 'GET',
        url: 'ajax/backup_actions.php',
        dataType: 'json',
        data: form_data,
        success: function(data)
        {
            if (typeof(data) != 'undefined' && typeof(data.status) != 'undefined')
            {
                show_state_box(data);
            }
            else
            {
                msg = (typeof(data.message) != 'undefined' && data.message != '') ? data.message : '<?php echo _('Error retrieving backup status')?>';
                show_notification(msg, 'backup_info', 'nf_error', 'padding: 3px; width: 90%; margin: auto; text-align: center;');
            }
        }
    });
}

/*
Note: 'delete' mode is not available anymore due to a performance problem
      'Purge' button as been removed from UI
*/
function launch_backup(mode)
{
    var combo_source = (mode == 'insert') ? 'insert_combo' : 'delete_combo';
    var dates_list   = getselectedcombovalue(combo_source);
    var token        = Token.get_token(mode + "_events");
    var filter_by    = '';
    if ($('#entity').val() != '')
    {
        filter_by = $('#entity').val();
    }
    if ($('#user').val() != '')
    {
        filter_by = $('#user').val();
    }
    
    var form_data = 'action=' + mode + '&dates_list=' + dates_list + '&filter_by=' + filter_by + '&token=' + token;
    
    $.ajax({
        type: 'GET',
        url: 'ajax/backup_actions.php',
        dataType: 'json',
        data: form_data,
        success: function(data)
        {
            if (typeof(data) != 'undefined' && typeof(data.status) != 'undefined' && data.status == 'success')
            {
                msg = (typeof(data.message) != 'undefined' && data.message != '') ? data.message : '<?php echo _('Backup action successfully done')?>';
                show_state_box(data);
            }
            else
            {
                msg = (typeof(data.message) != 'undefined' && data.message != '') ? data.message : '<?php echo _('Error performing backup action')?>';
                show_notification(msg, 'backup_info', 'nf_error', 'padding: 3px; width: 90%; margin: auto; text-align: center;');
            }
        }
    });
}

function show_state_box(data)
{
    if (data.status == 'error')
    {
        box_content = "<div class='box_title'><?php echo _('Backup Progress')?></div>" +
        "<div class='box_subtitle'><?php echo _('The backup process has failed') ?>. " + data.message + "</div>" +
        "<div class='box_single_button'><input type='button' value='<?php echo _('Finish') ?>' onclick='finished()'/></div>" +
         "</div>";

        $('#box-content').html(box_content);

        clearTimeout(timer);
    }
    else if (data.message == '')
    {
        box_content = "<div class='box_title'><?php echo _('Backup Progress')?></div>" +
        "<div class='box_subtitle'><?php echo _('The backup process has been done') ?></div>" +
        "<div class='box_single_button'><input type='button' value='<?php echo _('Finish') ?>' onclick='finished()'/></div>" +
         "</div>";

        $('#box-content').html(box_content);

        clearTimeout(timer);
    }
    else
    {
        if (!showing_box)
        {
            showing_box = true;

            box_content = "<div class='box_title'><?php echo _('Backup Progress')?></div>" +
            "<div class='box_subtitle'>" + data.message +"</div>" +
            "<div>" +
                 "<div id='backup_activity_bar'></div>" +
                 "<div id='backup_activity_bar_legend'></div>" +
             "</div>";
            
            $.fancybox({
                'modal': true,
                'width': 450,
                'height': 205,
                'autoDimensions': false,
                'centerOnScroll': true,
                'content': '<div id="box-content">' + box_content + '</div>',
                'overlayOpacity': 0.07,
                'overlayColor': '#000',
                'onComplete': function()
                {
                    $('#backup_activity_bar').AVactivity_bar();
                    update_activity_legend(data.progress);
                }
            });
        }
        else
        {
            update_activity_legend(data.progress);
        }

        timer = setTimeout('show_backup_status()', 2000);
    }
}

function update_activity_legend(num_events)
{
    if (num_events != '' && num_events > '0')
    {
        $('#backup_activity_bar_legend').html(num_events + " <?php echo _('events stored in database') ?>");
    }
    else
    {
        $('#backup_activity_bar_legend').html('');
    }
}

function finished()
{
    document.location.reload();
}

function show_notification (msg, container, nf_type, style)
{
    var nt_error_msg = (msg == '')   ? '<?php echo _('Sorry, operation was not completed due to an error when processing the request')?>' : msg;
    var style        = (style == '' ) ? 'width: 80%; text-align:center; padding: 5px 5px 5px 22px; margin: 20px auto;' : style;

    var config_nt = { content: nt_error_msg,
            options: {
                type: nf_type,
                cancel_button: true
            },
            style: style
        };

    var nt_id         = 'nt_ns';
    var nt            = new Notification(nt_id, config_nt);
    var notification  = nt.show();

    $('#'+container).html(notification);
    parent.window.scrollTo(0,0);
}

function switch_status()
{
	document.location.href="index.php?status_log=" + $('#status_log').val();
}
