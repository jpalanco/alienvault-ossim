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


/**
 * Updates notification bubble text
 *
 * @param  unread   Number of unread messages
 * @param  compare  True / False
 */
function update_notification_bubble(unread, compare)
{
    var $bubble = $('#notif_bubble');

    if (0 == $bubble.length)
    {
        $bubble = $('#notif_bubble', window.parent.document);
    }
    
    $bubble.removeClass();
    
    if (0 == unread)
    {
        $bubble.hide().text('0');
    }
    else if (unread >= 100)
    {
        $bubble.text('99+').addClass('exceed_notif').show();
    }
    else
    {
        $bubble.text(unread).show();
    }

    if (true == compare)
    {
        var $main =  $('#main').contents();

        var $unread_stat = $('[data-stat="nf_unread"]', $main);
        var $search = $('#nf_search', $main);

        if (0 != $unread_stat.length)
        {
            var search_value = $search.val();
            var stat_value = parseInt($unread_stat.text().replace(/\(|\)/, ''));

            if (!isNaN(stat_value) && search_value == '' && stat_value != unread)
            {
                $('#av_info', $main)
                    .html(notify_info('You have new messages! <a class="reload">View new messages</a>'))
                    .fadeIn(1000);

                $('.reload', $main).on('click', function ()
                {
                    var win = window.frames['main'].window;

                    win.clean_read_messages();
                    win.table_data.fnDraw();

                    $('#av_info', $main).hide();
                });
            }
        }
    }
}
