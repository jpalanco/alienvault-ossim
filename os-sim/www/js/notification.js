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


function Notification(new_wrapper_id, new_config)
{
    var config        = '';
    var wrapper_id    = '';

    var wrapper_style = 'width: 300px;' +
                        'font-family:Arial, Helvetica, sans-serif;' +
                        'font-size:12px;' +
                        'text-align: left;' +
                        'position: relative;' +
                        'border: 1px solid;' +
                        'border-radius: 5px;' +
                        '-moz-border-radius: 5px;' +
                        '-webkit-border-radius: 5px;' +
                        'box-shadow: 5px 5px 5px rgba(0, 0, 0, 0.1);' +
                        '-webkit-box-shadow: 5px 5px rgba(0, 0, 0, 0.1);' +
                        '-moz-box-shadow: 5px 5px rgba(0, 0, 0, 0.1);';

    var nf_images = {
            "info" : "data:image/gif;base64,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",
            "warning" : "data:image/gif;base64,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",
            "error" : "data:image/png;base64,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",
            "success" : "data:image/gif;base64,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",
            "close" : "data:image/gif;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAALEgAACxIB0t1+/AAAABx0RVh0U29mdHdhcmUAQWRvYmUgRmlyZXdvcmtzIENTM5jWRgMAAAAVdEVYdENyZWF0aW9uIFRpbWUAMi8xNy8wOCCcqlgAAAQRdEVYdFhNTDpjb20uYWRvYmUueG1wADw/eHBhY2tldCBiZWdpbj0iICAgIiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+Cjx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDQuMS1jMDM0IDQ2LjI3Mjk3NiwgU2F0IEphbiAyNyAyMDA3IDIyOjExOjQxICAgICAgICAiPgogICA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPgogICAgICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgICAgICAgICB4bWxuczp4YXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iPgogICAgICAgICA8eGFwOkNyZWF0b3JUb29sPkFkb2JlIEZpcmV3b3JrcyBDUzM8L3hhcDpDcmVhdG9yVG9vbD4KICAgICAgICAgPHhhcDpDcmVhdGVEYXRlPjIwMDgtMDItMTdUMDI6MzY6NDVaPC94YXA6Q3JlYXRlRGF0ZT4KICAgICAgICAgPHhhcDpNb2RpZnlEYXRlPjIwMDgtMDMtMjRUMTk6MDA6NDJaPC94YXA6TW9kaWZ5RGF0ZT4KICAgICAgPC9yZGY6RGVzY3JpcHRpb24+CiAgICAgIDxyZGY6RGVzY3JpcHRpb24gcmRmOmFib3V0PSIiCiAgICAgICAgICAgIHhtbG5zOmRjPSJodHRwOi8vcHVybC5vcmcvZGMvZWxlbWVudHMvMS4xLyI+CiAgICAgICAgIDxkYzpmb3JtYXQ+aW1hZ2UvcG5nPC9kYzpmb3JtYXQ+CiAgICAgIDwvcmRmOkRlc2NyaXB0aW9uPgogICA8L3JkZjpSREY+CjwveDp4bXBtZXRhPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDUdUmQAAAB9SURBVDiN1VNBDoAgDCuGB+GL9hT2lb1IfzQvSgCZYPBiEw5raLM24FQVM1im1F8Y+Hxg5gBg62hWZt7TpKrpxBi1h/NO0jQjiMgQBxgdEFEhEBEQUdPAN9nKxBKbG7yBaXCtXccZMqgzP5mYJY5wwL3EUDySNkI+uP9/pgNQQGCwjv058wAAAABJRU5ErkJggg=="
    }


    function set_c(new_config)
    {
        config = new_config;

        if (typeof(config.content) == 'undefined')
        {
            config.content= "";
        }

        if (typeof(config.style) == 'undefined')
        {
            config.style= "";
        }

        if (typeof(config.options.cancel_button) == 'undefined')
        {
            config.options.cancel_button = false;
        }
    };


    function set_wp_id(new_wrapper_id)
    {
        wrapper_id = (new_wrapper_id != '')  ? new_wrapper_id : "wrapper_nt";
    };


    this.get_wrapper_id = function()
    {
        //console.log(wrapper_id);
        return wrapper_id;
    };


    this.get_config = function()
    {
        //console.log(config);
        return config;
    };


    this.set_wrapper_id = function (new_wrapper_id)
    {
        set_wp_id(new_wrapper_id);
    };


    this.set_config = function (new_config)
    {
        set_c(new_config);
    };


    this.hide = function()
    {
        $("#"+wrapper_id).hide();
    };


    this.remove = function()
    {
        $("#"+wrapper_id).remove();
    };


    this.fade_out = function(duration, easing, callback)
    {
        $("#"+wrapper_id).fadeOut(duration, easing, callback);
    };


    this.fade_in = function(duration, easing, callback)
    {
        $("#"+wrapper_id).fadeIn(duration, easing, callback);
    };


    this.show = function()
    {
        var nf_style = wrapper_style;
        var img      = nf_images.error;

        switch (config.options.type){

            case 'nf_error':
                nf_style += 'color: #D8000C; background-color: #FFBABA;';
                img       = nf_images.error;
            break;

            case 'nf_info':
                nf_style += 'color: #00529B; background-color: #BDE5F8;';
                img       = nf_images.info;
            break;

            case 'nf_success':
                nf_style += 'color: #4F8A10; background-color: #DFF2BF;';
                img       = nf_images.success;
            break;

            case 'nf_warning':
                nf_style += 'color: #9F6000; background-color: #FEEFB3;';
                img       = nf_images.warning;
            break;

            default:
                nf_style += 'color: #D8000C; background-color: #FFBABA;';
                img       = nf_images.error;
        }

        nf_style += config.style;

        var cancel_button = '';
        var c_pad         = 'padding: 5px 5px 5px 25px;';

        if (config.options.cancel_button == true)
        {
            cancel_button = "<a onclick=\"$('#"+wrapper_id+"').remove()\"><img src='"+nf_images.close+"' style='position: absolute; top: 0px; right: 0px; cursor:pointer;'/></a>";
            c_pad         = 'padding: 8px 12px 8px 18px;';
        }

        var html =  "<div id='"+wrapper_id+"' style='"+ nf_style+ "'>"
                        + "<img src='"+img+"' style='position: absolute; top: -11px; left: -11px'/>"
                        + "<div style='"+c_pad+"'>"
                        + "<div class='"+config.options.type+"'>" + config.content + "</div>"
                        + "</div>"
                        + cancel_button +
                    "</div>";

        return html;
    };

    set_c(new_config);
    set_wp_id(new_wrapper_id);
};


// This function creates a temporary floating message
function notify(msg_text, msg_type, b_cancel)
{
    if (typeof b_cancel == 'undefined' && b_cancel != true)
    {
        b_cancel = false
    }

    var config_nt = { content: msg_text,
                      options: {
                         type: msg_type,
                         cancel_button: b_cancel
                      },
                      style: 'text-align:center; width:100%; margin: 15px auto 0px auto;'
                    };

    nt  = new Notification('nt_short', config_nt);

    var notification = nt.show();

    if ($('#av_msg_info').length >= 1)
    {
        $('#av_msg_info').html(notification);
    }
    else
    {
        var content = '<div id="av_msg_container" style="margin: auto; position: relative; height: 1px; width: 380px;">' +
                          '<div id="av_msg_info" style="position:absolute; z-index:999; left: 0px; right: 0px; top: 0px; width:100%;">' +
                          notification +
                      '</div>';

        $('body').prepend(content);
    }

   setTimeout('nt.fade_out(1000);', 10000); //Delete at 10 seconds
}


function show_notification(id, msg, type, fade, cancel, style)
{
    if(typeof(id) == 'undefinded')
    {
        return false;
    }

    if(typeof(fade) == 'undefinded' || fade == null)
    {
        fade = 0;
    }

    if(typeof(cancel) == 'undefinded' || cancel == null )
    {
        cancel = false;
    }

    if(typeof(style) == 'undefinded' || style == null )
    {
        style = 'width: 60%;text-align:center;margin:0 auto;';
    }

    var config_nt =
    {
        content: msg,
        options:
        {
            type: type,
            cancel_button: cancel
        },
        style: style
    };

    nt = new Notification('nt_'+id,config_nt);

    $('#'+id).html(nt.show());

    if(fade > 0)
    {
        $('#nt_'+id).fadeIn(1000).delay(fade).fadeOut(2000);
    }
}
    