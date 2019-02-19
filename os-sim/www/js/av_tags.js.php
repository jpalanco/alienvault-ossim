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

edit_tags = function(tag_type, callback)
{
    var url   = '<?php echo AV_MAIN_PATH?>/tags/views/tag_manager.php?tag_type=' + tag_type;
    var title = '<?php echo Util::js_entities(_('Manage Labels'))?>';

    GB_show(title, url, '850', '850');
}

// Execute action
function delete_tag(tag_id, component_id, callback)
{
    var data = {
        'tag_id'            : tag_id,
        'action'            : 'delete_components',
        'component_ids'     : [component_id],
        'select_from_filter': false,
        'token'             : Token.get_token('av_dropdown_tag_token')
    };

    $.ajax({
        type    : 'POST',
        url     : '<?php echo AV_MAIN_PATH?>/tags/controllers/tag_components_actions.php',
        data    : data,
        dataType: 'json'
    }).fail(function (XMLHttpRequest, textStatus, errorThrown)
    {
        //Checking expired session
        var session = new Session(XMLHttpRequest, '');

        if (session.check_session_expired() == true)
        {
            session.redirect();
        }
    }).done(function (data)
    {
        if ('function' == typeof callback)
        {
            callback(data);
        }
    });
}

draw_tag = function(tag, component_id, callback)
{
    var $tag = $('<div>', {
        'class'            : 'tag_' + tag.id + ' transparent in_line_av_tag ' + tag.class,
        'data-tag-id'      : tag.id,
        'data-component-id': component_id,
        'title'            : tag.name
    });

    $('<span>', {
        'text': tag.name
    }).appendTo($tag);

    var $delete = $('<a>', {
        'class': 'remove_tag ' + tag.class,
        'href' : 'javascript:;',
        'text' : 'x'
    }).appendTo($tag);

    $tag.tipTip({defaultPosition: 'bottom'});

    $delete.on('click', function (e)
    {
        e.stopPropagation();

        var remove_tag = function (data)
        {
            $tag.remove();

            if ('function' == typeof callback)
            {
                callback(data);
            }
        };

        delete_tag(tag.id, component_id, remove_tag);
    });

    return $tag;
}
