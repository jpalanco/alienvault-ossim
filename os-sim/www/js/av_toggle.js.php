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

/****************************************************************
 ************************  AV Toggle  ***************************
 ****************************************************************
 * Usage example:
 * <div class='av_toggle' data-options-title='<< Link Text >>' data-options-hidden='true'>
 * [...] << HTML to toggle >>
 * </div>
 *
 * Initialize in ready section with:
 * $('.av_toggle').av_toggle();
 *
 ****************************************************************
 */ 

(function ($)
{
    $.fn.av_toggle = function (options)
    {
        if (__css_check() == false)
        {
            console.error("<?php echo _('Unable to load CSS from av_toggle.js plugin') ?>");
            return false;
        }
        
        // Options by default
        var defaults = {
            'title'   : '<?php echo _('Show More') ?>',
            'hidden'  : 'true',                         // To show or hide the content initially
            'display' : 'down'                          // TODO (May be implemented with floating content, ajax, etc.)
        };
        
        this.each(function()
        {
            __init.call(this);

            __load_handlers.call(this);

            //return this;
        });
        
        
        /*****************
        /** Constructor **
        *****************/
        
        /*
         *  Function to create the html code
         */
        function __init()
        {
            var options = __get_options.call(this);
            
            // Transform original div element to create two childs inside (title, content)
            $(this).wrapInner("<div class='av_toggle_content'></div>");
            
            // Create Title Link
            var _link_container = $("<div class='av_toggle_title'></div>");
            var _toggle_image   = $('<span>',
            {
                'class' : 'av_toggle_arrow arrow-right',
                'id'    : 'toggle_arrow'
            }).appendTo(_link_container);
            var _toggle_text    = $('<a>',
            {
                'href' : '#',
                'id'   : 'toggle_text',
                'text' : options.title
            }).appendTo(_link_container);
            
            $(this).prepend(_link_container);
            
            // Show the main container to make the Title Link visible
            $(this).show();
            
            // Show or not the content container
            if (options.hidden == 'false')
            {
                __toggle_content.call(this);
            }
                
        }
        
        
        /****************
        /** Functions **
        ****************/

        function __css_check()
        {
            var _pass = false;
            
            $.each(document.styleSheets, function(key, val)
            {
                if (typeof val.href == 'string' && val.href.match(/av_toggle/))
                {
                    _pass = true;
                }
            });
            
            return _pass;
        }
        
        /*
         *  Function to Load the options.
         */
        function __get_options()
        {
            var _options = {};
            var _obj     = $(this);
            $.each(defaults, function(key, val)
            {
                if (typeof _obj.attr('data-options-' + key) != 'undefined' && _obj.attr('data-options-' + key) != '')
                {
                    _options[key] = _obj.attr('data-options-' + key);
                }
                else
                {
                    _options[key] = val;
                }
            });
            
            return _options;
        }
        
        /*
         *  Function to Load the handlers.
         */
        function __load_handlers()
        {
            var _that = this;
            
            $('#toggle_text', this).on('click', function()
            {
                __toggle_content.call(_that);
            });
        }
        
        /*
         *  Function to show or hide the HTML content.
         */
        function __toggle_content()
        {
            if ($('.av_toggle_content', this).is(':visible'))
            {
                $('.av_toggle_content', this).hide();
            
                $('#toggle_arrow', this).addClass('arrow-right');
            }
            else
            {
                $('.av_toggle_content', this).show();
            
                $('#toggle_arrow', this).removeClass('arrow-right');
            }
        }
    }
})(jQuery);
