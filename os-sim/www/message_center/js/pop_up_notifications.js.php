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


function send_track_usage_information()
{
    var track_usage_information = ($('[data-bind="chk_tui"]').prop('checked') == true) ? 1 : 0;

    var p_data = {
        "action"  : 'track_usage_information',
        "token"   : Token.get_token('tui'),
        "tui"     : track_usage_information
    };

    $.ajax({
        type: "POST",
        url: "/ossim/message_center/controllers/pop_up_actions.php",
        data: p_data,
        dataType: 'json',
        beforeSend: function(xhr) {

            $('#pop_up_info').empty();

            show_loading_box('c_pop_up', '<?php echo _("Sending information")?>...', '');
        },
        error: function(xhr){

            hide_loading_box();

            //Check expired session
            var session = new Session(xhr.responseText, '');

            if (session.check_session_expired() == true)
            {
                session.redirect();
                return;
            }

            var __error_msg = av_messages['unknown_error'];

            if (typeof(xhr.responseText) != 'undefined' && xhr.responseText != '')
            {
                __error_msg = xhr.responseText;
            }

            var __style = 'width: 100%; text-align:center; margin:0px auto;';
            show_notification('pop_up_info', __error_msg, 'nf_error', 15000, true, __style);
        },
        success: function(data){

            if (typeof(parent) != 'undefined')
            {
                parent.GB_hide();
            }
            else
            {
                var __style = 'width: 100%; text-align:center; margin:0px auto;';
                show_notification('pop_up_info', data.data, 'nf_success', 15000, true, __style);
            }
        }
    });
}


function hide_lightbox()
{
    if (typeof(parent) != 'undefined')
    {
        parent.GB_hide();
    }
}