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
    $.fn.av_icon = function (options)
    {
        var max_allowed_size = {
            "width"  : 400,
            "height" : 400
        };

        // Error messages
        var __error_messages = {
            "size_not_allowed"  : "<?php echo _('Error in the icon field (Image size not allowed)')?>",
            "file_not_allowed"  : "<?php echo _('Error in the icon field (Invalid image)')?>",
            "file_not_uploaded" : "<?php echo _('Error in the icon field (Image not uploaded)')?>",
            "unknown_error"     : "<?php echo _('Error in the icon field (Sorry, operation was not completed due to an error when processing the request)')?>"
        };

        // Default options
        var defaults = {
            "icon"         : "",
            "width"        : 16,
            "height"       : 16,
            "show_actions" : true,
            "error"        : function (error_type, error_descr){},
            "success"      : function (icon) {}
        };

        var options = $.extend(defaults, options);

        /*
            <div class='c_av_icon'>
                <div class='av_icon_preview'></div>
                <div class='av_icon_actions'>
                    <input type="hidden" class="vfield" name="h_icon" id="h_icon"/>

                    <span class='c_remove_action'>
                        <a class='remove_icon av_l_main' href="javascript:void(0)">Remove icon</a>
                        <span>or</span>
                    </span>

                    <span class="custom_input_file">
                        <a clas='choose_icon av_l_main' href="javascript:void(0)">Choose file ...</a>
                        <input type="file" class="vfield" name="icon" id="icon"/>
                    </span>
                </div>
                <div class='av_icon_error'></div>
            </div>
        */

        return this.each(function(){

            var __self = {
                'container' : '#c_av_icon_' + $(this).attr('id'),
                'h_input'   : '#h_' + $(this).attr('id'),
                'input'     : this
            }

            __create_containers.call(__self);

            __show_icon.call(__self);

            __show_actions.call(__self);

            __load_handlers.call(__self);
        });


        /****************
        /** Functions **
        ****************/

        function __create_containers()
        {
            var h_input_id   = this.h_input.replace('#', '');
            var container_id = this.container.replace('#', '');

            $(this.input).wrap('<div class="c_av_icon" id="' + container_id + '"></div>');

            var av_container = $(this.container);

            $(this.input).before('<div class="av_icon_preview"></div>');
            $(this.input).after('<div class="av_icon_error"></div>');
            $(this.input).wrap('<div class="av_icon_actions"></div>');

            $('.av_icon_actions', av_container).append('<input type="hidden" class="vfield" name="' + h_input_id + '" id="' + h_input_id + '"/>');
        }


        function __show_actions()
        {
            var av_container = $(this.container);

            $('.av_icon_actions', av_container).css('height', options.height);

            if (options.show_actions == true)
            {
                if ($('.custom_input_file', av_container).length < 1)
                {
                    $(this.input).wrap('<span class="custom_input_file"></span>');
                    $('.custom_input_file', av_container).css('line-height', options.height + 'px');

                    $('<a/>',
                    {
                        "class" : "choose_icon av_l_main",
                        "href"  : "javascript:void(0)"
                    }).html("<?php echo _('Choose icon')?> ...").appendTo($('.custom_input_file', av_container));
                }

                //Add remove link

                if (__is_valid_icon() && $('.c_remove_action', av_container).length < 1)
                {
                    $('.custom_input_file', av_container).before('<span class="c_remove_action"></span>');
                }

                if ($('.c_remove_action', av_container).is(':empty'))
                {
                    $('<a/>', {
                        "class" : 'remove_icon av_l_main',
                        "href"  : 'javascript:void(0)'
                    }).html("<?php echo _('Remove icon')?>").appendTo($('.c_remove_action', av_container));

                    $('<span/>').html('<?php echo _('or')?>').appendTo($('.c_remove_action', av_container));

                    $('.remove_icon', av_container).css('line-height', options.height + 'px');
                }
            }
        }


        function __load_handlers()
        {
            var __self = this;

            var av_container = $(__self.container);

            $('.choose_icon', av_container).off('click').on('click', function(event){
                event.stopPropagation();
                $(__self.input).trigger('click');
            });

            $(__self.input).off('click').on('change', function(event){
                event.stopPropagation();
                __upload_icon(__self);
            });

            $('.remove_icon', av_container).off('click').on('click', function(event){
                event.stopPropagation();
                __remove_icon(__self);
            });
        }


        function __upload_icon(self)
        {
            __delete_previous_error.call(self);

            var filename = $(self.input).val();

            if (typeof(filename) != 'undefined' && filename != '')
            {
                //Checking FileReader support
                if (window.FileReader && typeof(self.input.files[0]) == 'object')
                {
                    var file = self.input.files[0];

                    if (file.type.match('image.*'))
                    {
                        var reader = new FileReader();

                        reader.onload = function(e) {

                            var new_icon = __create_icon(e.target.result);

                            new_icon.on('load', function(){

                                var i_width  = this.width;
                                var i_height = this.height;

                                if (i_width > max_allowed_size.width || i_height > max_allowed_size.height)
                                {
                                    __throw_error.call(self, 'size_not_allowed');
                                }
                                else
                                {
                                    options.icon = new_icon.attr('src');

                                    __show_icon.call(self);
                                    __show_actions.call(self);
                                    __load_handlers.call(self);

                                    options.success.call(self, new_icon);
                                }
                            });
                        }

                        reader.readAsDataURL(file);
                    }
                    else
                    {
                        __throw_error.call(self, 'file_not_allowed');
                    }
                }
                else
                {
                    // No FileReader support, no show preview image

                    if (filename.match(/(png|jpeg|jpg|gif)$/i))
                    {
                        options.icon = filename.replace(/\\/g,'/').replace(/.*\//, '');

                        __show_text.call(self);
                        __show_actions.call(self);
                        __load_handlers.call(self);

                        options.success.call(self, null);
                    }
                    else
                    {
                        __throw_error.call(self, 'file_not_allowed');
                    }
                }
            }
            else
            {
                __throw_error.call(self, 'file_not_uploaded');
            }
        }


        function __throw_error(e_type)
        {
            var av_container = $(this.container);
            var e_message    = "<div class='small icon_error'>" + __error_messages[e_type] + "</div>";

            $('.av_icon_error', av_container).html(e_message);

            $(this.input).val('');

            options.error.call(self, e_type, __error_messages[e_type]);
        }


        function __delete_previous_error()
        {
            var av_container = $(this.container);

            $('.av_icon_error', av_container).empty();
        }


        function __is_valid_icon()
        {
            return (typeof(options.icon) != 'undefined' && options.icon != '' && options.icon != null);
        }


        function __show_icon()
        {
            var av_container = $(this.container);

            if (__is_valid_icon())
            {
                var icon = __create_icon(options.icon);

                $('.av_icon_preview', av_container).html(icon);

                $(this.h_input).val("1");
            }

            __set_preview_size.call(this, 'image');
        }


        function __show_text()
        {
            $('.av_icon_preview', $(this.container)).html(options.icon);

            $(this.h_input).val("1");

            __set_preview_size.call(this, 'text');
        }


        function __remove_icon(self)
        {
            var av_container = $(self.container);

            __delete_previous_error.call(self);

            $(self.input).val('');
            $(self.h_input).val('');

            $('.c_icon_error', av_container).empty();

            $('.c_remove_action', av_container).empty();

            $('.av_icon_preview', av_container).empty();

            __set_preview_size.call(self, 'image');
        }


        function __create_icon(src)
        {
            var icon = new Image();
                icon.src = src;

            var i_height = (icon.height > options.height) ? options.height : icon.height;
            var i_width  = (icon.width > options.width) ? options.width : icon.width;

            //Create preview icon
            return $('<img/>', {
                'height' : i_width,
                'width'  : i_height,
                'src'    : src
            });
        }


        function __set_preview_size(type)
        {
            var av_container = $(this.container);

            if (type == 'image')
            {
                $('.av_icon_preview', av_container).css({
                    'height' : options.height,
                    'width'  : options.width
                });

                //Center icon
                var i_height = parseInt($('.av_icon_preview img', av_container).height())/2;
                var m_top    = (i_height > 0) ? (options.height/2) - i_height : 0;

                $('.av_icon_preview img', av_container).css({
                    'margin-top' : m_top
                });
            }
            else
            {
                $('.av_icon_preview', av_container).css('width', 'auto');
            }
        }
    }
})(jQuery);
