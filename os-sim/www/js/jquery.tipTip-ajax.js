/*
* TipTip
* Copyright 2010 Drew Wilson
* www.drewwilson.com
* code.drewwilson.com/entry/tiptip-jquery-plugin
*
* Version 1.3   -   Updated: Mar. 23, 2010
*
* This Plug-In will create a custom tooltip to replace the default
* browser tooltip. It is extremely lightweight and very smart in
* that it detects the edges of the browser window and will make sure
* the tooltip stays within the current window size. As a result the
* tooltip will adjust itself to be displayed above, below, to the left 
* or to the right depending on what is necessary to stay within the
* browser window. It is completely customizable as well via CSS.
*
* This TipTip jQuery plug-in is dual licensed under the MIT and GPL licenses:
*   http://www.opensource.org/licenses/mit-license.php
*   http://www.gnu.org/licenses/gpl.html
*/
(function ($) {
    $.fn.tipTip = function (options) {
        var defaults = {
            activation: "hover", // How to show (and hide) the tooltip. Can be: hover, focus, click and manual.
            keepAlive: false, // When true the tooltip won't disapper when the mouse moves away from the element. Instead it will be hidden when it leaves the tooltip.
            getMouseXPosition: false,
            getMouseYPosition: false,
            getMouseXMargin: 25,
            getMouseYMargin: 10,
            maxWidth: "200px", // The max-width to set on the tooltip. You may also use the option cssClass to set this.
            edgeOffset: 6, // The offset between the tooltip arrow edge and the element that has the tooltip.
            defaultPosition: "bottom", // The position of the tooltip. Can be: top, right, bottom and left.
            delay: 200, // The delay in msec to show a tooltip.
            delay_load: 0, // The delay in msec to show a tooltip.
            fadeIn: 100, // The length in msec of the fade in.
            fadeOut: 100, // The length in msec of the fade out.
            attribute: "title", // The attribute to fetch the tooltip text if the option content is false.
            content: false, // HTML or String or Function (that returns HTML or String) to fill TipTIp with
            enter: function () { }, // Callback function before a tooltip is shown.
            afterEnter: function () { }, // Callback function after a tooltip is shown.
            exit: function () { }, // Callback function before a tooltip is hidden.
            afterExit: function () { }, // Callback function after a tooltip is hidden.
            cssClass: '' // CSS class that will be applied on the tooltip before showing only for this instance of tooltip.
        };

        // Setup tip tip elements and render them to the DOM
        if ($("#tiptip_holder").length <= 0) {
            var tiptip_holder = $('<div id="tiptip_holder"></div>');
            var tiptip_content = $('<div id="tiptip_content" class="tiptip_content_right"></div>');
            var tiptip_arrow = $('<div id="tiptip_arrow"></div>');
            $("body").append(tiptip_holder.html(tiptip_content).prepend(tiptip_arrow.html('<div id="tiptip_arrow_inner"></div>')));
        } else {
            var tiptip_holder = $("#tiptip_holder");
            var tiptip_content = $("#tiptip_content");
            var tiptip_arrow = $("#tiptip_arrow");
        }

        var timeout         = false;
        var timeout_content = false;
        var last_event      = null;

        return this.each(function () {
            var org_elem = $(this),
                data = org_elem.data("tipTip"),
                opts = data && data.options || $.extend(defaults, options),
                callback_data = { holder: tiptip_holder, content: tiptip_content, arrow: tiptip_arrow, options: opts };

            org_elem.set_position = function(w) {  _set_position_tiptip(w) }
            org_elem.close_tiptip = function() {  deactive_tiptip() }

            if (data) {
                switch (options) {
                    case "show":
                        load_tiptip(e);
                        break;
                    case "hide":
                        deactive_tiptip();
                        break;
                    case "destroy":
                        org_elem.unbind(".tipTip").removeData("tipTip");
                        break;
                }
            } else {
                org_elem.data("tipTip", { options: opts });

                if (opts.activation == "hover") {
                    org_elem.bind("mouseenter.tipTip", function (e) {
                        last_event = e;
                        load_tiptip(e);
                    }).bind("mouseleave.tipTip", function () {
                        if (!opts.keepAlive) {
                            deactive_tiptip();
                        } else {
                            if (timeout_content) { clearTimeout(timeout_content); }
                            if (timeout) { clearTimeout(timeout); }
                            tiptip_holder.one("mouseleave.tipTip", function () {
                                deactive_tiptip();
                            });
                        }
                    });
                } else if (opts.activation == "focus") {
                    org_elem.bind("focus.tipTip", function (e) {
                        load_tiptip(e);
                    }).bind("blur.tipTip", function () {
                        deactive_tiptip();
                    });
                } else if (opts.activation == "click") {
                    org_elem.bind("click.tipTip", function (e) {
                        e.preventDefault();
                        load_tiptip(e);
                        return false;
                    }).bind("mouseleave.tipTip", function () {
                        if (!opts.keepAlive) {
                            deactive_tiptip();
                        } else {
                            tiptip_holder.one("mouseleave.tipTip", function () {
                                deactive_tiptip();
                            });
                        }
                    });
                } else if (opts.activation == "manual") {
                    // Nothing to register actually. We decide when to show or hide.
                }
            }

            function load_tiptip(event)
            {
                if(opts.delay_load > 0)
                {
                    if (timeout_content) 
                    { 
                        clearTimeout(timeout_content); 
                    }
                    timeout_content = setTimeout(function () { active_tiptip(event)  }, opts.delay_load);
                }
                else
                {
                    active_tiptip(event);
                }
                
            }

            function active_tiptip(event) 
            {
                if (opts.enter.call(org_elem, callback_data) === false) {
                    return;
                }

                var org_title;
                if (opts.content) 
                {
                    if ($.isFunction(opts.content))
                    {
                         org_title = opts.content.call(org_elem, callback_data);
                    }
                    else
                    {
                        org_title = opts.content;
                    }
                } 
                else
                {
                    if (opts.attribute == 'title') {
                        // Hide tipical yellow tooltip 
                        if (typeof (org_elem.attr('data-title')) == 'undefined') {
                            org_elem.attr('data-title',org_elem.attr(opts.attribute));
                            org_elem.removeAttr(opts.attribute);
                        }
                        org_title = org_elem.attr('data-title');
                    } else {
                        org_title = org_elem.attr(opts.attribute);
                    }
                }
                if (!org_title) {
                    return; // don't show tip when no content.
                }

                tiptip_content.html(org_title);

                position_tiptip(event);
                
                if (timeout) { clearTimeout(timeout); }
                timeout = setTimeout(function () { tiptip_holder.stop(true, true).fadeIn(opts.fadeIn); }, opts.delay);

                opts.afterEnter.call(org_elem, callback_data);
            }

            function position_tiptip(event)
            {
                if (typeof(event) == 'undefined') { 
                    event = last_event;
                }
                tiptip_holder.hide().removeAttr("class").css({ "margin": "0px", "max-width": opts.maxWidth });
                if (opts.cssClass) {
                    tiptip_holder.addClass(opts.cssClass);
                }
                tiptip_arrow.removeAttr("style");

                var top = (opts.getMouseYPosition) ? (event.pageY || event.clientY) : parseInt(org_elem.offset()['top']),
                    left = (opts.getMouseXPosition) ? (event.pageX || event.clientX) : parseInt(org_elem.offset()['left']),
                    org_width = parseInt(org_elem.outerWidth()),
                    org_height = parseInt(org_elem.outerHeight()),
                    tip_w = tiptip_holder.outerWidth(),
                    tip_h = tiptip_holder.outerHeight(),
                    w_compare = Math.round((org_width - tip_w) / 2),
                    h_compare = Math.round((org_height - tip_h) / 2),
                    marg_left = (opts.getMouseXPosition) ? left+opts.getMouseXMargin : Math.round(left + w_compare),
                    marg_top = (opts.getMouseYPosition) ? top+opts.getMouseYMargin : Math.round(top + org_height + opts.edgeOffset),
                    t_class = "",
                    arrow_top = "",
                    arrow_left = Math.round(tip_w - 12) / 2;
                    
                if (opts.defaultPosition == "bottom") {
                    t_class = "_bottom";
                } else if (opts.defaultPosition == "top") {
                    t_class = "_top";
                } else if (opts.defaultPosition == "left") {
                    t_class = "_left";
                } else if (opts.defaultPosition == "right") {
                    t_class = "_right";
                }

                var right_compare = (w_compare + left) < parseInt($(window).scrollLeft()),
                    left_compare = (tip_w + left) > parseInt($(window).width());

                if ((right_compare && w_compare < 0) || (t_class == "_right" && !left_compare) || (t_class == "_left" && left < (tip_w + opts.edgeOffset + 5))) {
                    t_class = "_right";
                    arrow_top = Math.round(tip_h - 13) / 2;
                    arrow_left = -12;
                    marg_left = Math.round(left + org_width + opts.edgeOffset);
                    marg_top = Math.round(top + h_compare);
                } else if ((left_compare && w_compare < 0) || (t_class == "_left" && !right_compare)) {
                    t_class = "_left";
                    arrow_top = Math.round(tip_h - 13) / 2;
                    arrow_left = Math.round(tip_w);
                    marg_left = Math.round(left - (tip_w + opts.edgeOffset + 5));
                    marg_top = Math.round(top + h_compare);
                }

                var top_compare = (top + org_height + opts.edgeOffset + tip_h + 8) > parseInt($(window).height() + $(window).scrollTop()),
                    bottom_compare = ((top + org_height) - (opts.edgeOffset + tip_h + 8)) < 0;

                if (top_compare || (t_class == "_bottom" && top_compare) || (t_class == "_top" && !bottom_compare)) {
                    if (t_class == "_top" || t_class == "_bottom") {
                        t_class = "_top";
                    } else {
                        t_class = t_class + "_top";
                    }
                    arrow_top = tip_h;
                    marg_top = Math.round(top - (tip_h + 5 + opts.edgeOffset));
                } else if (bottom_compare | (t_class == "_top" && bottom_compare) || (t_class == "_bottom" && !top_compare)) {
                    if (t_class == "_top" || t_class == "_bottom") {
                        t_class = "_bottom";
                    } else {
                        t_class = t_class + "_bottom";
                    }
                    arrow_top = -12;
                    marg_top = Math.round(top + org_height + opts.edgeOffset);
                }

                if (t_class == "_right_top" || t_class == "_left_top") {
                    marg_top = marg_top + 5;
                } else if (t_class == "_right_bottom" || t_class == "_left_bottom") {
                    marg_top = marg_top - 5;
                }
                if (t_class == "_left_top" || t_class == "_left_bottom") {
                    marg_left = marg_left + 5;
                }

                tiptip_arrow.css({ "margin-left": arrow_left + "px", "margin-top": arrow_top + "px" });
                tiptip_holder.css({ "margin-left": marg_left + "px", "margin-top": marg_top + "px" }).addClass("tip" + t_class);  
                tiptip_content.removeClass().addClass('tiptip_content_right');           
            }

            function _set_position_tiptip(fix_width)
            {
                if(last_event) 
                {
                    var left      = last_event.pageX,
                        org_width = fix_width + 35,
                        win_w     = $(window).width();

                    if(left + org_width > win_w)
                    {
                        tiptip_holder.css({ "margin-left": (left - org_width) + "px"}); 
                        tiptip_content.removeClass('tiptip_content_right').addClass('tiptip_content_left');
                    }  
                }          
            }
            
            function deactive_tiptip() 
            {                
                if (opts.exit.call(org_elem, callback_data) === false) {
                    return;
                }

                if (timeout) { clearTimeout(timeout); }
                if (timeout_content) { clearTimeout(timeout_content); }
                tiptip_holder.fadeOut(opts.fadeOut);

                opts.afterExit.call(org_elem, callback_data);
            }
        });
    }
})(jQuery);