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

(function ($)
{
    $.fn.av_suggestions = function (options)
    {
        // Error messages
        var __error_messages = {
            "no_suggestions"  : "<?php echo _('Currently no suggestions')?>",
            "unknown_error"   : "<?php echo _('Sorry, operation was not completed due to an error when processing the request')?>"
        };


        // Default options
        var defaults = {
            "asset_id"   : "",
            "asset_type" : ""
        };

        var options = $.extend(defaults, options);


        //Provider paths
        var __cfg = <?php echo Asset::get_path_url()?>;


        return this.each(function()
        {
            __draw_list.call(this);
        });


        /****************
        /** Functions **
        ****************/

        function __draw_list()
        {
            var __self = this;
            
            var $elem  = $('<div></div>',
            {
                'class': 'av_suggestions'
            }).appendTo($(this));
            
            $.ajax({
                type: "POST",
                url: __cfg.common.providers + 'get_suggestions.php',
                data: options,
                dataType: 'json',
                beforeSend: function(xhr) 
                {
                    __show_loading.call(__self);
                },
                error: function(xhr)
                {
                    var __error_msg = __error_messages.unknown_error;

                    if (typeof(xhr.responseText) != 'undefined' && xhr.responseText != '')
                    {
                        __error_msg = xhr.responseText;
                    }

                    $elem.html('<div class="no_suggetions s_error">' + __error_msg + '</div>').css('min-height', '50px');
                },
                success: function(data)
                {
                    $elem.empty();

                    if (typeof(data) != 'undefined' && Object.keys(data).length > 0)
                    {
                        $.each(data, function(index, m_data)
                        {
                            //Header
                            var level_class = "s_" + m_data.message_level.toLowerCase();

                            var m_title  = "<div class='s_title " + level_class + "'>" + m_data.message_title + "</div>";
                                m_title += "<div class='s_more_info'>" + m_data.creation_time + "</div>";

                            var header = $('<a/>',
                            {
                                "href"  : "javascript:void(0)"
                            }).html(m_title);


                            $('<h3/>', 
                            {
                                "data-message_id": m_data.id,
                                "click"          : function(e)
                                {
                                    e.stopPropagation();
                                }
                            }).html(header).appendTo($elem);


                            //Content
                            var msg_descr = m_data.message_description;

                            if (m_data.message_actions != '')
                            {
                                msg_descr += m_data.message_actions;
                            }

                            $('<div/>', 
                            {
                                "class" : "s_descr"
                            }).html(msg_descr).appendTo($('.av_suggestions', $(__self)));
                        });

                        //Show accordion
                        __show_accordion.call(__self, data);

                        __load_handlers.call(__self);
                    }
                    else
                    {
                        $elem.html('<div class="no_suggetions">' + __error_messages.no_suggestions + '</div>').css('min-height', '50px');
                    }
                }
            });
        }


        function __show_loading()
        {
            var __self = this;
            var $elem  = $('.av_suggestions', $(__self)).empty();
            
            $('<img></img>', 
            {
               'src'  : '/ossim/pixmaps/loading.gif',
               'class': 's_loading'
            }).appendTo($elem);
        }
        

        function __show_accordion()
        {
            var __self = this;

            //Show accordion
            $('.av_suggestions', $(__self)).accordion(
            {
                active     : false,
                collapsible: true,
                autoHeight : false,
                clearStyle : true,
                change     : function(event, ui) 
                {
                    var c_suggestion = $('.ui-state-active', $(__self));

                    if (c_suggestion.length > 0 && c_suggestion.hasClass('viewed') == false)
                    {
                        var message_id = $('.ui-state-active', $(__self)).attr("data-message_id");

                        __set_as_viewed.call(__self, message_id);
                    }
                }
            });
        }


        function __load_handlers()
        {
            // Actions for each suggestion

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

            $('.s_descr a', $(this)).off('click').on('click', function (event) 
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
            });
        }


        function __set_as_viewed(message_id)
        {
            var __self = this;

            var m_data = 
            {
                'message_id' : message_id
            }

            $.ajax(
            {
                type    : "POST",
                data    : m_data,
                dataType: 'json',
                url     : __cfg.common.controllers + 'set_suggestion_status.php',
                success : function(data)
                {
                    $('[data-message_id="' + message_id + '"]', $(__self)).addClass('viewed');
                }
            });
        }
    }
})(jQuery);
