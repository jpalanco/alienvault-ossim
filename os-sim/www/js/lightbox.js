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
var LB_SCROLL_DIFF = (navigator.appVersion.match(/MSIE/)) ? 1 : ((navigator.appCodeName.match(/Mozilla/)) ? 17 : 17 );
var LB_HDIFF       = 5;
var CURRENT_LB     = null;
var LB_TYPE        = '';
var LB_counter     = 0;
var LB_FLAG        = false;

(function($)    // Compliant with jquery.noConflict()
{                                         
    $.fn.AV_LightBox = function(o) 
    {
        o = $.extend({
            caption: '', 
            url: '', 
            type: '',
            height: 400,
            width: 400, 
            nohide: false, 
            post: false,
        }, o || {});


        var _that, _loading, _actions, _resizing, _dragging, _head, _caption, _close, _content, _iframe, _resize_timeout;
        
        _that = this;
        
        _resize_timeout = false;

        _position();

        var _win_h = $(this).height();
        var _win_w = $(this).width();


        _head = $('<div>', {  
            id      : "LB_head"
        }).appendTo(this);


        _content = $('<div>', {  
            id      : "LB_content"
        }).appendTo(this);


        _loading = $('<div>', {  
            id      : "LB_loading",  
            style   : "height:"+ ( _win_h ) +"px;width:"+ ( _win_w ) +"px;"  
        }).appendTo(_content);

        _loading.html("<div class ='img_loading'><img src ='/ossim/pixmaps/loading.gif' height='18px'/></div>");


        _actions = $('<div>', {  
            id      : "LB_actions",  
            style   : "height:"+ ( _win_h ) +"px;width:"+ ( _win_w ) +"px;"  
        }).appendTo(_content);
        

        /*
        _resizing = $('<div>', {  
            id      : "LB_resizing"
        }).appendTo(_content);

        _resizing.html("Resizing window <img src='/ossim/pixmaps/loading_box.gif' class='LB_loadingbox' />");
        */


        _dragging = $('<div>', {  
            id      : "LB_dragging"
        }).appendTo(_content);

        _dragging.html("Dragging window <img src='/ossim/pixmaps/loading_box.gif' class='LB_loadingbox' />");


        _caption = $('<div>', {  
            id      : "LB_caption",
            text    : o.caption   
        }).appendTo(_head);


        _close = $('<div>', {  
            id      : "LB_close",
            click   : function() 
            {
                _hide();
            }
        }).appendTo(_head);

        _close.html("<div id='LB_closeimg' class='closeimg' title='Close'>&nbsp;</div>");
        


        _iframe = $('<iframe>', {  
            "class" : "LB_iframe",
            id      : "LB_frame",
            "src"   : '',
            "name"  : 'LB_frame_'+LB_counter,
            load    : function() 
            {

                if((o.post && $(this).attr('name') != '') || $(this).attr('src') != '')
                {
                    _loading.remove();

                    $(_that).css('background', '#F7F7F7');

                    $(this).contents().find('body').attr('id', 'body_scroll');

                    _adjust_height();
                    $(this).css('visibility', 'visible'); 
                }

                return false;
            }

        }).appendTo(_content);


        $(".LB_overlay").on('click', function()
        {
            _hide()
        });

        /*
        $(this).resizable(
        {
            minHeight: 250,
            minWidth: 300,
            alsoResize: "#LB_actions",
            start: function()
            {
                $(_that).css('background', 'transparent');

                $('#content_overlay').show();

                _loading.hide();
                _iframe.hide();

                _actions.show();
                _resizing.show();
            },
            stop: function()
            {    
                $(_that).css('background', '#F7F7F7');

                $('#content_overlay').hide();

                _loading.show();
                _iframe.show();

                _resizing.hide();
                _actions.hide();    
            }
        });*/

        $(this).draggable(
        {
            handle: '#LB_head',
            start: function()
            {
                _head.css('cursor', 'move');
                $(_that).css('background', 'transparent');

                _loading.hide();
                _iframe.hide();

                _actions.show();
                _dragging.show();

            },
            stop: function()
            {

                $(_that).css('background', '#F7F7F7');
    
                _loading.show();
                _iframe.show();

                _dragging.hide();
                _actions.hide();
                _head.css('cursor', 'default');
            }
        });
    


        if (o.post)
        {    

            var content = $("#main").contents().find('body');
            $(o.url, content).attr('target','LB_frame_'+LB_counter).submit();

        }
        else 
        {
            _iframe.attr('src', o.url);
        }


        function _hide(hide, params) 
        {
            clearTimeout(_resize_timeout);
            
            var _nohide;

            if(typeof(hide) != 'undefined')
            {
                _nohide = hide;
            }
            else
            {
                _nohide = o.nohide;
            }

            /*
             * window.stop() skips when params['nostop'] is defined
             * Use it when the main frame is loading something in background
             * This is needed when the parent is not completely loaded after the GB_hide call
             * For example: the risk PNGs (generated by a PHP) of SIEM Console load after the body is finished
             */
            var stop = (typeof params != "undefined" && typeof params['nostop'] != "undefined") ? 0 : 1;
            
            // stop is TRUE as default
            if (stop)
            {
	            if (typeof window.stop == 'function')
	            {
	                window.stop();
	            }
	            else if (document.execCommand == 'function') 
	            {
	                document.execCommand.execCommand('Stop');
	            }
            }


            if(_nohide)
            {
                if (top.frames["main"] != null && typeof top.frames["main"].GB_onhide == "function") 
                {
                    top.frames["main"].GB_onhide(o.url, params);
                }
                else if (typeof GB_onhide == "function") //In case we call it without iframe
                {
                    GB_onhide(o.url, params);
                }
            }
            else
            {
                if (top.frames["main"] != null && typeof top.frames["main"].GB_onclose == "function") 
                {
                    top.frames["main"].GB_onclose(o.url, params);
                }
                else if (typeof GB_onclose == "function")  //In case we call it without iframe
                {
                    GB_onclose(o.url, params);
                }
            }
            
            $(_that).remove();

            _remove_overlay();
            
        }

        function _position() 
        {

            var de = document.documentElement;

            // total document width
            var w = document.body.scrollWidth

            if (self.innerWidth > w) {
                w = self.innerWidth;
            }

            if (de && de.clientWidth > w) {
                w = de.clientWidth;
            }

            if (document.body.clientWidth > w) {
                w = document.body.clientWidth;
            }

            // total document height
            var h = document.body.scrollHeight

            if ((self.innerHeight+window.scrollMaxY) > h) {
                h = self.innerHeight+window.scrollMaxY;
            }

            if (de && de.clientHeight > h) {
                h = de.clientHeight;
            }

            if (document.body.clientHeight > h) {
                h = document.body.clientHeight;
            }

            var sy_correction = (navigator.appVersion.match(/MSIE/)) ? 30 : 0;  
            var sy = document.documentElement.scrollTop || document.body.scrollTop - sy_correction;

            var ww = (typeof(o.width) == "string" && o.width.match(/\%/)) ? o.width : o.width+"px";
            var wp = (typeof(o.width) == "string" && o.width.match(/\%/)) ? w*(o.width.replace(/\%/,''))/100 : o.width;

            var hw = (typeof(o.height) == "string" && o.height.match(/\%/)) ? o.height : (o.height- LB_HDIFF)+"px";
            var hy = (typeof(o.height) == "string" && o.height.match(/\%/)) ? (document.body.clientHeight-document.body.clientHeight*(o.height.replace(/\%/,''))/100)/2 : 32;


            $(_that).css(
            { 
                'width': ww, 
                'max-height': hw,
                'height': hw, 
                'left': ((w - wp)/2)+"px", 
                'top': (sy+hy+50)+"px" 
            });


        }

        function _set_new_height()
        {
            var _h1 = $(_iframe).contents().find('body').outerHeight(true);
            var _h2 = $(_iframe).contents().find('html').outerHeight(true);
            var h   = (_h1 > _h2)? _h1 : _h2;

            $(_that).css(
            { 
                height: h + 'px', 
            });

        }

        function _adjust_height() 
        {   
            clearTimeout(_resize_timeout);
                        
            var _elem_body = $(_iframe).contents().find('body');
           
            if(_elem_body.length > 0)
            {
                _set_new_height();
                    
                _resize_timeout = setTimeout(function() 
                {
                    _adjust_height();
                        
                }, 1000);

                /*
                if(!$.browser.opera)
                {
                    $(_elem_body).off('DOMSubtreeModified');
                    
                    $(_elem_body).on('DOMSubtreeModified', function()
                    {
                        _set_new_height();
                    });
                }
                */
            }

        }

        function _remove_overlay() 
        {
            var n = num_lightbox_opened();

            if (n == 0 || LB_FLAG)
            {
            	LB_FLAG = false;
                $(".LB_overlay").remove();
            }
            
        }

        this.LB_hide = function (params) 
        {
            _hide(true, params);
        }


        this.LB_close = function (params) 
        {
            _hide(false, params);
        }


        this.LB_adjust = function () 
        {
            _adjust_height();
        }

        return this;
    };

})(jQuery);


function num_lightbox_opened() 
{
    return $('.LB_window').length;
}

function LB_show(caption, url, height, width, nohide, post) 
{
    if (typeof(nohide)=='undefined') 
    {
        nohide = false;
    }

    if (typeof(post)=='undefined') 
    {
        post = false;
    }
    
    LB_counter++;   

    var multiple = 'multiple';
        
    if (!LB_FLAG)
    {
	    if ($('.LB_overlay').length == 0)
	    {
	        var _overlay = "<div id='LB_overlay" + LB_TYPE + "' class='LB_overlay'></div>";
	        $(document.body).append(_overlay);
	    }
	    
	    var _overlay_height = $(document).height();
	    
	    $('.LB_overlay').css({'min-height':_overlay_height, 'height': '100%'});

        multiple = '';
    }

    var _window  = "<div id='LB_window_" + LB_counter + "' class='LB_window "+multiple+"' width='"+width+"' height='"+height+"'></div>";   
    $(document.body).append(_window);


    if (!post)
    {
        url = url.replace(/.*\/ossim/, '/ossim');

        if (!url.match(/^\/ossim/))
        {   
            if(url.match(/^\//))
            {
                url = '/ossim' + url;
            }
            else
            {
                var content_url = $("#main").attr('src').replace(/\/[\w\-]+.php(.*)?/, '/');
                url = content_url + url;
            }
            
        }
    }

    CURRENT_LB = $("#LB_window_" + LB_counter ).AV_LightBox({
        caption: caption, 
        url: url, 
        type: '',
        height: height,
        width: width, 
        nohide: nohide, 
        post: post
    });

}

function GB_hide(params) 
{
    if(CURRENT_LB)
    {
        CURRENT_LB.LB_hide(params);
    }
    
}

function GB_close(params) 
{
    if(CURRENT_LB)
    {
        CURRENT_LB.LB_close(params);
    }
    
}

function is_lightbox_loaded(w_name)
{
    if (w_name.match(/^LB_frame/))
    {
        return true;
    }
    else
    {
        return false;
    }
}

$(document).ready(function()
{

    $(document).on('click', '.LB_window', function()
    {
        $('.LB_window').css('z-index', '30000');
        $(this).css('z-index', '40000');
    });

});
