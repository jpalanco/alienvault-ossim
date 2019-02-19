/**
 * Required files:
 *
 *  - graybox.js
 *  - av_tags.js.php
 */

(function ($)
{
    /**************** AV Dropdown Tag *******************/
    $.fn.av_dropdown_tag = function (o)
    {
        // Options dictionary
        o = $.extend(
            {
                'load_tags_url'          : '',
                'manage_components_url'  : '',
                'allow_edit'             : true,
                'select_component_mode'  : 'multiple',  // Single, multiple
                'select_from_filter'     : false,
                'component_id'           : '',          // Component id (single mode)
                'tag_type'               : 'alarm',
                'components_check_class' : '',          // If select_from_filter == false
                'show_tray_triangle'     : false,
                'on_save'                : function (status, data) {},
                'on_delete'              : function (status, data) {}
            }, o || {});

        // Vars
        var __self = this;
        var $dropdown_box, $tray_triangle, $list_box, $search_box, $edit_button, $dropdown_wrapper;
        var selected_components = [], tag_list = {}, search_str = '';

        this.setMCU = function(url) {
		o.manage_components_url = url;
        }

        /***************
         /** Elements **
         **************/

            // Dropdown container
        $dropdown_box = $('<div>', {
            'class': 'av_dropdown_tag_box'
        }).appendTo($(document.body));

        // Tray Triangle
        $tray_triangle = $('<div>', {
            'class': 'tray_triangle av_dropdown_tray_triangle'
        }).appendTo($dropdown_box);

        $dropdown_wrapper = $('<div>', {
            'class': 'av_dropdown_tag_wrapper'
        }).appendTo($dropdown_box);

        // Search input
        $search_box = $('<input>', {
            'type'       : 'search',
            'class'      : 'av_dropdown_tag_search_box',
            'placeholder': 'Search'
        }).appendTo($dropdown_wrapper);

        // Tag list container
        $list_box = $('<div>', {
            'class': 'av_dropdown_tag_list_box'
        }).appendTo($dropdown_wrapper);

        // If allow edit is enabled
        if (true == o.allow_edit)
        {
            // Edit button
            $edit_button = $('<a>', {
                'class': 'av_dropdown_tag_edit_button',
                'text' : 'Manage Labels'
            });

            $edit_button.on('click', function ()
            {
                edit_tags(o.tag_type);
                $dropdown_box.fadeOut(500);
            });

            $edit_button.appendTo($dropdown_wrapper);
        }


        /****************
         /** Functions **
         ****************/

        this.show_dropdown = function()
        {
            var self_width = __self.width();
            var self_height = __self.innerHeight();
            var self_top = __self.offset().top;
            var self_bottom = $(window).height() - (self_top + self_height);
            var self_left = __self.offset().left;
            var self_right = $(window).width() - self_left - self_width;

            var dropdown_width = $dropdown_wrapper.width();
            var dropdown_top = self_top + self_height;
            var dropdown_left = self_left;

            var offset_right = dropdown_width - (self_right + self_width);
            var is_on_right = false;

            if (200 > self_bottom)
            {
                $dropdown_box.css('bottom', 0);
            }
            else
            {
                $dropdown_box.css('top', dropdown_top);
            }

            if (Math.abs(offset_right) <= dropdown_width)
            {
                $dropdown_box.css('right', 0);
                is_on_right = true;
            }
            else
            {
                $dropdown_box.css('left', dropdown_left);
            }

            if (true == o.show_tray_triangle)
            {
                $tray_triangle.css('display', 'block');
                $dropdown_box.css('margin-top', '8px');

                if (true == is_on_right)
                {
                    $tray_triangle.css('left', dropdown_width - self_right);
                }
                else
                {
                    $tray_triangle.css('left', 0);
                }
            }

            $dropdown_box.toggle();

            reset_search_box();
            get_selected_components();
            load_tags();
        };

        function reset_search_box()
        {
            search_str = '';
            $search_box.val('');
        }

        function filter_list()
        {
            if ('' != search_str)
            {
                $list_box.find('span:contains(' + search_str + ')').parent().slideDown(function ()
                {
                    $list_box.find('p').remove();
                });
                $list_box.find('span:not(:contains(' + search_str + '))').parent().slideUp(function ()
                {
                    if (!$list_box.find('span').is(':visible'))
                    {
                        if ($list_box.find('p').length == 0)
                        {
                            $('<p>', {
                                'class': 'av_dropdown_tag_msg', 'text': 'NO LABELS FOUND'
                            }).appendTo($list_box);
                        }
                    }
                });
            }
            else
            {
                $list_box.find('span').parent().slideDown(function ()
                {
                    $list_box.find('p').remove();
                });
            }
        }

        // Get selected components
        function get_selected_components()
        {
            selected_components = [];

            if ('single' == o.select_component_mode)
            {
                selected_components = [o.component_id];
            }
            else
            {
                if (false == o.select_from_filter)
                {
                    $('.' + o.components_check_class + ':checked').each(function ()
                    {
                        selected_components.push($(this).attr('id').split('_').pop());
                    });
                }
            }
        }


        // Load tags
        function load_tags()
        {
            var data = {
                'tag_type'          : o.tag_type,
                'search_str'        : search_str,
                'select_from_filter': o.select_from_filter
            };

            $.ajax({
                type    : 'POST',
                url     : o.load_tags_url,
                data    : data,
                dataType: 'json'
            }).fail(function (XMLHttpRequest, textStatus, errorThrown)
            {
                //Checking expired session
                var session = new Session(XMLHttpRequest, '');

                if (session.check_session_expired() == true)
                {
                    session.redirect();
                    return;
                }

                $list_box.empty();

                $('<p>', {
                    'class': 'av_dropdown_tag_msg',
                    'text' : 'NO LABELS FOUND'
                }).appendTo($list_box);

            }).done(function (data)
            {
                var cnd_1 = (typeof(data) == 'undefined' || data == null);
                var cnd_2 = (typeof(data) != 'undefined' && data != null && data.status != 'OK');

                if (cnd_1 || cnd_2)
                {
                    $list_box.empty();

                    $('<p>',
                        {
                            'class': 'av_dropdown_tag_msg',
                            'text' : 'NO LABELS FOUND'
                        }).appendTo($list_box);
                }
                else
                {
                    tag_list = {};

                    $.each(data.data, function (index, tag)
                    {
                        tag_list[tag.id] = tag;
                    });

                    draw_list();
                }
            });
        }

        // Draws tag list
        function draw_list()
        {
            $list_box.empty();

            var $list_row, $tag, $check_box;

            $.each(tag_list, function (index, tag)
            {
                // list row
                $list_row = $('<div>', {
                    'class': 'av_dropdown_tag_list_row'
                });

                var all = true; // True if all selected components have this tag
                var some = false; // True if some selected components have this tag

                // Check box to add tag
                $check_box = $('<input>', {
                    'type': 'checkbox',
                    'value': tag.id,
                    'class': 'av_dropdown_tag_checkbox'
                });

                if (false == o.select_from_filter)
                {
                    if (0 == selected_components.length)
                    {
                        all = false;
                    }

                    $.each(selected_components, function (index, component)
                    {
                        (-1 == $.inArray(component, tag.components)) ? all = false : some = true;
                    });

                    if (true == all)
                    {
                        $check_box.prop('checked', true);
                        $check_box.prop('indeterminate', false);
                    }
                    else
                    {
                        if (true == some)
                        {
                            $check_box.prop('indeterminate', true)
                        }
                        else
                        {
                            $check_box.prop('checked', false);
                            $check_box.prop('indeterminate', false);
                        }
                    }
                }
                else
                {
                    if (0 == tag.mark_state)
                    {
                        $check_box.prop('checked', false);
                    }
                    else if (1 == tag.mark_state)
                    {
                        $check_box.prop('checked', true);
                    }
                    else if (2 == tag.mark_state)
                    {
                        $check_box.prop('indeterminate', true);
                    }
                }

                $check_box.on('change', function ()
                {
                    $(this).is(':checked')
                        ? do_components_action($(this).val(), 'add_components')
                        : do_components_action($(this).val(), 'delete_components');
                });
                $check_box.appendTo($list_row);

                // Tag
                $tag = $('<span>', {
                    'class': tag.class,
                    'text': tag.name
                }).appendTo($list_row);

                $tag.attr('title', tag.name);
                $tag.tipTip({defaultPosition: 'left'});

                // Append tag to list box
                $list_row.appendTo($list_box);
            });
        }

        // Execute action
        function do_components_action(tag_id, action)
        {
            var data = {
                'tag_id'            : tag_id,
                'action'            : action,
                'component_ids'     : selected_components,
                'select_from_filter': o.select_from_filter
            };

            data['token'] = Token.get_token('av_dropdown_tag_token');

            $.ajax({
                type    : 'POST',
                url     : o.manage_components_url,
                data    : data,
                dataType: 'json'
            }).fail(function (XMLHttpRequest, textStatus, errorThrown)
            {
                //Checking expired session
                var session = new Session(XMLHttpRequest, '');

                if (session.check_session_expired() == true)
                {
                    session.redirect();
                    return;
                }

                var msg_error = XMLHttpRequest.responseText;

                if ('add_components' == action)
                {
                    o.on_save.call(this, 'error', msg_error);
                }
                else
                {
                    o.on_delete.call(this, 'error', msg_error);
                }

            }).done(function (data)
            {
                if ('add_components' == action)
                {
                    o.on_save.call(this, data.status, data.data);
                }
                else
                {
                    o.on_delete.call(this, data.status, data.data);
                }
            });
        }

        /*************
         /** EVENTS **
         *************/

        // Control de mouseleave timeout on self mouseleave event
        var mouseout_timeout;

        // Search tags
        var search_timeout = false;
        $search_box.on('input', function ()
        {
            clearTimeout(search_timeout);

            var that = this;

            search_timeout = setTimeout(function ()
            {
                search_str = $(that).val();
                filter_list();
            }, 400);
        });

        // Hide dropdown menu
        $dropdown_wrapper.on('mouseleave', function ()
        {
            mouseout_timeout = setTimeout(function ()
            {
                if (!$dropdown_wrapper.is(':hover'))
                {
                    $dropdown_box.hide();
                }
            }, 1000);
        });

        // Show dropdown menu
        __self.on('click', function ()
        {
            clearTimeout(mouseout_timeout);

            __self.show_dropdown();
        });

        __self.on('mouseleave', function ()
        {
            mouseout_timeout = setTimeout(function ()
            {
                if (!$dropdown_wrapper.is(':hover'))
                {
                    $dropdown_box.hide();
                }
            }, 1000);
        });
        
        return this;
    }
})(jQuery);
