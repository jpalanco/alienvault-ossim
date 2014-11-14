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
    /*********   PROGRESS BAR   ********/  
    $.fn.AVprogress_bar = function(o) 
    {
        o = $.extend(
        {
            current : 0,
            total   : 0,
            elem    : '',
            time    : false
        }, o || {});
    
        
        var __legend, __stripes, __label, __current, __time = null
        var bar = $(this)
        
        
         /* Private Functions */
        function _update_bar() 
        {
        	var percent = (o.current / o.total) * 100
        	var s_width = percent * bar.width() / 100
        	        	
        	__stripes.animate({ width: s_width }, 400)
        	__label.text(percent + "%")
        }
                       
        bar.addClass('av_progressbar')
        
        __stripes = $('<div>', 
        {
            "class" : "stripes"
            
        }).appendTo(bar)
        
        __label = $('<span>', 
        {
            "class" : "bar-label"
            
        }).appendTo(bar)
        
        __legend = $('<div>', 
        {
            "id" : "progress_legend"
            
        }).appendTo(bar)
        
        
        __current = $('<span>', 
        {
            "id"   : "progress_current",
            "text" : o.current
            
        }).appendTo(__legend)
        
        __legend.append('/')
        
        $('<span>', 
        {
            "id"   : "progress_total",
            "text" : o.total
            
        }).appendTo(__legend)
        
        __legend.append(' ' + o.elem)
        
        if (o.time !== false)
        {
            __legend.append(' (')
            
            __time = $('<span>', 
            {
                "id"    : "progress_remaining",
                "text"  : o.time
                
            }).appendTo(__legend)
            
            __legend.append(')')
        
        }
        
        _update_bar();
        
        
        this.update_bar = function(val)
        { 
            if (val <= o.total)
            {
                o.current = val;
            }
            else
            {
                o.current = o.total;
            }
                        
            __current.text(o.current);
            
            _update_bar();           
        }
        
        this.update_time = function(time)
        {                  
            if (o.time !== false)
            {       
                __time.text(time);
            }
        }
        
        return this;
            
    }
    
    /*********   ACTIVITY BAR   ********/
    $.fn.AVactivity_bar = function(o) 
    {
        o = $.extend(
        {
            speed     : 1000,
            w_percent : 20
        }, o || {});
    
        
        function _animate_right()
        {
            __stripes.animate({opacity:1},{
                duration: o.speed,
                step:function(now, fn)
                {
                    fn.start = 1;
                    fn.end   = __width;
                    
                    __stripes.css({'left':now});
                },
                complete: function()
                {
                    _animate_left();
                }
            });
        }
        
        function _animate_left()
        {
            __stripes.animate({opacity:1},{
                duration: o.speed,
                step:function(now, fn)
                {
                    fn.start = __width;
                    fn.end = 1;
                    
                    __stripes.css({'left':now});
                },
                complete: function()
                {
                    _animate_right();
                }
            });
        }
        
        
        var bar       = $(this)
        var __stripes = null
        var __width   = 0
        
        bar.addClass('av_activitybar');
        
        __stripes = $('<div>', 
        {
            "class" : "stripes"
            
        }).appendTo(bar)
        
        var activityBarWidth = (o.w_percent * bar.width()) / 100;
        
        __width = bar.width() - activityBarWidth
        
        __stripes.width(activityBarWidth);

        _animate_right();
        
        return bar;      
            
    }

})(jQuery);
