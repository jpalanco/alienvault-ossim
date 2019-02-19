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

/****************************************************************
 ************************  MANAGE TABS   ************************
 ****************************************************************/

function Av_tabs(tab_config)
{
    //Tab container
    this.id = 'av_tabs';

    //Tab UI object
    this.object = null;

    //Selected tab
    this.selected = -1;

    //Hide tab list
    this.hide = false;

    //Disabled Tabs
    this.disabled = [];

    this.tab = null;

    //Tab configuration
    this.tabs = {};

    if (typeof(tab_config.id) == 'string' && tab_config.id !== '')
    {
        this.id = tab_config.id;
    }

    if (typeof(tab_config.hide) == 'number' && tab_config.hide == 1)
    {
        this.hide = true;
    }

    if (typeof(tab_config.tabs) == 'object' && tab_config.tabs != '')
    {
        this.tabs = tab_config.tabs;
    }
    
    if (typeof tab_config.selected == 'number' && tab_config.selected >= 0)
    {
        this.selected = tab_config.selected;
    }
    
    this.draw_tabs = function()
    {
        var __self    = this;
        var __tab_list = $('#' + __self.id + ' ul');
            __tab_list.hide();

        var __first_valid = -1;     
        var __flag_selected_hidden = false;   
        $.each(__self.tabs, function(index, data)
        {            
            var span = "<span>" + data.name + "</span>";
            var li   = $('<li/>');

            $('<a/>',
            {
                "href"    : data.href,
                "data-id" : data.id
            }).html(span).appendTo(li);
            
            if (typeof(data.hide) != 'undefined' && data.hide == true)
            {
                li.hide();
                
                if (__self.selected == index)
                {
                    __flag_selected_hidden = true;
                }
                
                __self.disabled.push(index);
            }
            else if (__first_valid == -1)
            {
                __first_valid = index;
            }

            li.appendTo(__tab_list);
        });
        
        if (!__self.hide && __flag_selected_hidden)
        {
            __self.selected = __first_valid;
        }
        
        var __tab_data = {}
        
        if(__self.selected !== -1 && typeof(__self.tabs[__self.selected].ajax_options) != 'undefined')
        {
            __tab_data = __self.tabs[__self.selected].ajax_options.data;
        }
        
        this.object = $("#" + __self.id).tabs({
            selected: __self.selected,
            spinner: "",
            cache: true,
            disabled: __self.disabled,
            ajaxOptions: {
                type: 'POST',
                data: __tab_data,
                error: function(xhr, status, tab_index, ui_panel){

                    //Checking expired session
                    var session = new Session(xhr, '');
                    if (session.check_session_expired() == true)
                    {
                        session.redirect();
                        return;
                    }

                    var _tab_error = __format_tab_error('nt_load_error_' + tab_index);

                    $(ui_panel.hash).html(_tab_error);
                }
            },
            create: function(event, ui) {

                if (__self.hide == false)
                {
                    __tab_list.show();
                }
            },
            select: function(event, ui) {

                __self.selected = ui.index;
            },
            show: function(event, ui) {

                //If we change to another tab before it has been loaded, this tab will be loaded hidden
                if ($(ui.panel).is(":visible") == false)
                {
                    __self.show_selected_tab();
                }
            },
            load: function(event, ui) {

                //No previous error
                var cnd_1 = $(ui.panel).find('.nf_error').length == 0;

                //There is one tab selected at least and its callback function is defined
                var cnd_2 = (__self.selected != -1 && typeof(__self.tabs[__self.selected].load_callback) == 'function');

                if (cnd_1 && cnd_2)
                {
                    var __height = $(ui.panel).height();
                        __height = (__height < 400) ? 400 : __height;

                    $(ui.panel).hide();

                    var __loading = $('<div class="c_loading"/>').hide().html("<div><img src='/ossim/pixmaps/loading.gif'/></div>");
                        
                    $('#' + __self.id + ' .ui-tabs-panel:first').before(__loading);

                    __loading.find('div').css({"position": "absolute", "width" : "100%"}).height(__height);
                    __loading.find('img').css({"position": "absolute", "top" : "50%", "left" : "50%", "margin-top" : "-16px", "margin-left" : "-16px"});
                    __loading.css({"display": "none", "position": "relative", "width" : "100%", "margin" : "auto", "z-index" : "-999"}).height(__height).show();
                
                     __self.tabs[__self.selected].load_callback();
                }
                else
                {
                    __self.show_selected_tab();
                }
            }
        });
    };


    /* Function to show selected tab */
    this.show_selected_tab = function()
    {
        var __self = this;

        $('#' + __self.id + ' .c_loading').remove();
        $('#' + __self.id + ' .ui-tabs-panel:not(.ui-tabs-hide)').show();
    };


    this.open_tab = function(tab)
    {
        this.object.tabs('select', tab);
    };


    /* Function to format an error message which it will be displayed inside a tab */
    function __format_tab_error(id, e_msg, msg_type, msg_style)
    {
        var content = e_msg     || "<?php echo Util::js_entities(_('Tab could not be loaded'))?>";
        var type    = msg_type  || 'nf_error'
        var style   = msg_style || 'width: 500px; text-align:center; padding: 1px; margin: 100px auto 0px auto; z-index:10000;'

        var config_nt = { content: content,
            options: {
                type: type,
                cancel_button: false
            },
            style: style
        };

        var nt = new Notification(id, config_nt);

        return nt.show();
    }
};
