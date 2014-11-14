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

(function($) {                                          // Compliant with jquery.noConflict()
$.fn.AVwidget = function(o) {
    o = $.extend({
        id: 0,
        mode: 'view',
        lock: true,
        title: 'Default Widget',
        help: 'Default Widget Help',
        height: 200,
        src: ''
    }, o || {});

    return this.each(function() {               

        var header, title, icons, container, content, loading, _loading, overlay, iframe, edit, help, remove, collapse, locked;

        /* Overlay Layer */
        container = $('<div>', 
        {  
            id      : "wid" + o.id,  
            "class" : "portlet ui-widget ui-helper-clearfix"
        }).appendTo(this);


        header = $('<div>', 
        {   
            "class" : "portlet-header"
        }).appendTo(container);
        
        title = $('<div>', 
        {   
            "class" : "portlet-title",
            text:  o.title
        }).appendTo(header);
        
        /* HEADER ICONS */
        
        $('<img>', 
        {   
            "class" : "db_w_icon_grabber",
            "src" : "/ossim/pixmaps/grabber.png" 
        }).appendTo(header);
        
        
        icons = $('<div>', 
        {   
            "class" : "db_right_icons"
        }).appendTo(header);

        
        /* HEADER ICONS */

        if (o.mode == 'edit')
        {
            if (o.lock == false)
            {
                remove = $('<div>', {   
                    "class" : "ui-remove ui-icon ui-icon ui-icon-trash"
                }).appendTo(icons);

                edit = $('<div>', {   
                    "class" : "ui-edit ui-icon ui-icon ui-icon-wrench"
                }).appendTo(icons);
            }
            else
            {
                locked = $('<div>', {   
                    "class" : "ui-icon ui-icon ui-icon-locked",
                    title  : "Predefined Widget"
                }).appendTo(icons);
                
                title.addClass('blocked');
                
            }
        }
        else
        {
            collapse = $('<div>', 
            {   
                "class" : "db_w_icon db_w_icon_collapse",
                "html": '&#8722;'
            }).appendTo(icons);
            
            help = $('<div>', 
            {   
                "class" : "db_w_icon db_w_icon_help",
                "text": '?',
                title  : o.help
            }).appendTo(icons);
        }
    
    
    
        /* CONTENT */
        content =  $('<div>', {  
            "class" : "portlet-content db_w_content",
             style  : 'height:'+o.height+'px;'
        }).appendTo(container);


        iframe = $('<iframe>', {  
            "class" : "db_w_iframe",
            "style" : "opacity:0;",
            load: function()
            {
                var that = this;
                
                $(that).contents().find('body').attr('id', 'body_scroll');
                
                $(loading).hide();
                
                $(that).animate(
                {
                    opacity: 1,
                    queue: false
                    
                }, 500);

            }
        }).appendTo(content);


        /* Overlay Layer */
        overlay = $('<div>', {  
            id      : "db_w_overlay" + o.id,  
            "class" : "db_w_overlay"
        }).appendTo(content);


        /* Loading Layer */
        loading = $('<div>', {  
            id      : "db_w_loading" + o.id,  
            "class" : "db_w_loading"
        }).appendTo(content);

        _loading = " <div class='db_w_loading_content'> \
                        <img src='/ossim/pixmaps/loading.gif' height='18px'><br> \
                        Loading Widget \
                    </div>";

        $(loading).html(_loading);
       

        $(help, locked).tipTip();


        $(collapse).click(function() 
        {
            if ($(this).html() == '+')
            {
                $(this).html('&#8722;');
            }
            else
            {
                $(this).html('+');
            }
            
            $(content).slideToggle(400,function()
            {
                if($( this ).css('display') == 'block')
                {    
                    $(iframe).attr("src", $(iframe).attr("src"));
                }
                else
                {
                    $(loading).show();
                    $(iframe).css('opacity', '0');
                }
            });
            
        });
        
        
        //Edit the widget: A graybox with the wizard will be open           
        $(edit).click(function() 
        {
            var url = "/ossim/dashboard/sections/wizard/wizard.php?modify=1&id="+o.id;
            var t   = "Widget Wizard";

            if(typeof(GB_show) == 'function')
            {
                GB_show(t,url,510,1000);
            }
            
            return false;

        });
        
        //Remove a widget
        $(remove).click(function() 
        { 
            if(typeof(delete_widget) == 'function')
            {
                delete_widget(o.id);
            }
                
        }); 

        setTimeout(function()
        {
            $(iframe).attr('src', o.src);

        }, 250);
        

    });

};

})(jQuery);