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

?>

(function($)
{
    /*********   Breadcrumb   ********/
    $.fn.AVbreadcrumb = function(o)
    {
        o = $.extend(
        {
            with_menu : true,
            items     : {}
        }, o || {});
    
        
        var __bc  = $(this);
        var steps = Object.keys(o.items).length;
        

        if (steps < 2)
        {
            return false;
        }
        
        __bc.addClass('av_breadcrumb');
        
        if (o.with_menu)
        {
            __bc.addClass('with_menu');
        }
        
        var i = 1;
        $.each( o.items, function(k, item) 
        {
            //Encoding title as HTML entities
            var title = $('<div>').html(item.title).text();

            if (i != steps)
            {
                $('<div>', 
                {
                    "class" : "av_breadcrumb_item av_link",
                    "text"  : title,
                    "click" : function ()
                    {
                        if (typeof item.action == 'function')
                        {
                            item.action();
                        }
                        else if (item.action != '')
                        {
                            document.location.href = item.action
                        }
                        else
                        {
                            return false;
                        }
                    }
                }).appendTo(__bc);
                
                $('<div>', 
                {
                    "class" : "av_breadcrumb_separator"
                }).appendTo(__bc);
            }
            else
            {
                $('<div>',
                {
                    "class" : "av_breadcrumb_item last",
                    "text"  : title
                }).appendTo(__bc);
            }
            
            i++;
        });
        
        $('<div>', 
        {
            "class": "clear_layer"
        }).appendTo(__bc);


        return this;
    }

})(jQuery);
