/**
 * Copyright 2012 Tsvetan Tsvetkov
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Author: Tsvetan Tsvetkov (tsekach@gmail.com)
 */
(function (win) {
    /*
     Safari native methods required for Notifications do NOT run in strict mode.
     */
    //"use strict";
    var PERMISSION_DEFAULT = "default",
        PERMISSION_GRANTED = "granted",
        PERMISSION_DENIED = "denied",
        PERMISSION = [PERMISSION_GRANTED, PERMISSION_DEFAULT, PERMISSION_DENIED],
        defaultSetting = {
            pageVisibility: false,
            autoClose: 0
        },
        empty = {},
        emptyString = "",
        isSupported = (function () {
            var isSupported = false;
            /*
             * Use try {} catch() {} because the check for IE may throws an exception
             * if the code is run on browser that is not Safar/Chrome/IE or
             * Firefox with html5notifications plugin.
             *
             * Also, we canNOT detect if msIsSiteMode method exists, as it is
             * a method of host object. In IE check for existing method of host
             * object returns undefined. So, we try to run it - if it runs
             * successfully - then it is IE9+, if not - an exceptions is thrown.
             */
            try {
                isSupported = !!(/* Safari, Chrome */win.Notification || /* Chrome & ff-html5notifications plugin */win.webkitNotifications || /* Firefox Mobile */navigator.mozNotification || /* IE9+ */(win.external && win.external.msIsSiteMode() !== undefined));
            } catch (e) {}
            return isSupported;
        }()),
        ieVerification = Math.floor((Math.random() * 10) + 1),
        isFunction = function (value) { return (value && (value).constructor === Function); },
        isString = function (value) {return (value && (value).constructor === String); },
        isObject = function (value) {return (value && (value).constructor === Object); },
        /**
         * Dojo Mixin
         */
        mixin = function (target, source) {
            var name, s;
            for (name in source) {
                s = source[name];
                if (!(name in target) || (target[name] !== s && (!(name in empty) || empty[name] !== s))) {
                    target[name] = s;
                }
            }
            return target; // Object
        },
        noop = function () {},
        settings = defaultSetting;
    function getNotification(title, options) {
        var notification;
        if (win.Notification) { /* Safari 6, Chrome (23+) */
            notification =  new win.Notification(title, {
                /* The notification's icon - For Chrome in Windows, Linux & Chrome OS */
                icon: isString(options.icon) ? options.icon : options.icon.x32,
                /* The notification’s subtitle. */
                body: options.body || emptyString,
                /*
                    The notification’s unique identifier.
                    This prevents duplicate entries from appearing if the user has multiple instances of your website open at once.
                */
                tag: options.tag || emptyString
            });
        } else if (win.webkitNotifications) { /* FF with html5Notifications plugin installed */
            notification = win.webkitNotifications.createNotification(options.icon, title, options.body);
            notification.show();
        } else if (navigator.mozNotification) { /* Firefox Mobile */
            notification = navigator.mozNotification.createNotification(title, options.body, options.icon);
            notification.show();
        } else if (win.external && win.external.msIsSiteMode()) { /* IE9+ */
            //Clear any previous notifications
            win.external.msSiteModeClearIconOverlay();
            win.external.msSiteModeSetIconOverlay((isString(options.icon) ? options.icon : options.icon.x16), title);
            win.external.msSiteModeActivate();
            notification = {
                "ieVerification": ieVerification + 1
            };
        }
        return notification;
    }
    function getWrapper(notification) {
        return {
            close: function () {
                if (notification) {
                    if (notification.close) {
                        //http://code.google.com/p/ff-html5notifications/issues/detail?id=58
                        notification.close();
                    }
                    else if (notification.cancel) {
                        notification.cancel();
                    } else if (win.external && win.external.msIsSiteMode()) {
                        if (notification.ieVerification === ieVerification) {
                            win.external.msSiteModeClearIconOverlay();
                        }
                    }
                }
            }
        };
    }
    function requestPermission(callback) {
        if (!isSupported) { return; }
        var callbackFunction = isFunction(callback) ? callback : noop;
        if (win.webkitNotifications && win.webkitNotifications.checkPermission) {
            /*
             * Chrome 23 supports win.Notification.requestPermission, but it
             * breaks the browsers, so use the old-webkit-prefixed
             * win.webkitNotifications.checkPermission instead.
             *
             * Firefox with html5notifications plugin supports this method
             * for requesting permissions.
             */
            win.webkitNotifications.requestPermission(callbackFunction);
        } else if (win.Notification && win.Notification.requestPermission) {
            win.Notification.requestPermission(callbackFunction);
        }
    }
    function permissionLevel() {
        var permission;
        if (!isSupported) { return; }
        if (win.Notification && win.Notification.permissionLevel) {
            //Safari 6
            permission = win.Notification.permissionLevel();
        } else if (win.webkitNotifications && win.webkitNotifications.checkPermission) {
            //Chrome & Firefox with html5-notifications plugin installed
            permission = PERMISSION[win.webkitNotifications.checkPermission()];
        } else if (win.Notification && win.Notification.permission) {
            // Firefox 23+
            permission = win.Notification.permission;
        } else if (navigator.mozNotification) {
            //Firefox Mobile
            permission = PERMISSION_GRANTED;
        //} else if (win.external && (win.external.msIsSiteMode() !== undefined)) { 
        } else if (win.external && (typeof win.external.msIsSiteMode !== 'undefined')) { /* keep last */
            //IE9+
            permission = win.external.msIsSiteMode() ? PERMISSION_GRANTED : PERMISSION_DEFAULT;
        }
        return permission;
    }
    /**
     *
     */
    function config(params) {
        if (params && isObject(params)) {
            mixin(settings, params);
        }
        return settings;
    }
    function isDocumentHidden() {
        return settings.pageVisibility ? (document.hidden || document.msHidden || document.mozHidden || document.webkitHidden) : true;
    }
    function createNotification(title, options) {
        var notification,
            notificationWrapper;
        /*
            Return undefined if notifications are not supported.

            Return undefined if no permissions for displaying notifications.

            Title and icons are required. Return undefined if not set.
         */
        if (isSupported && isDocumentHidden() && isString(title) && (options && (isString(options.icon) || isObject(options.icon))) && (permissionLevel() === PERMISSION_GRANTED)) {
            notification = getNotification(title, options);
        }
        notificationWrapper = getWrapper(notification);
        //Auto-close notification
        if (settings.autoClose && notification && !notification.ieVerification && notification.addEventListener) {
            notification.addEventListener("show", function () {
                var notification = notificationWrapper;
                win.setTimeout(function () {
                    notification.close();
                }, settings.autoClose);
            });
        }
        return notificationWrapper;
    }
    win.notify = {
        PERMISSION_DEFAULT: PERMISSION_DEFAULT,
        PERMISSION_GRANTED: PERMISSION_GRANTED,
        PERMISSION_DENIED: PERMISSION_DENIED,
        isSupported: isSupported,
        config: config,
        createNotification: createNotification,
        permissionLevel: permissionLevel,
        requestPermission: requestPermission
    };
    if (isFunction(Object.seal)) {
        Object.seal(win.notify);
    }
}(window));


/**
*
* AlienVault Browser notification unobstructive functions 
*
**/

function Noti(new_wrapper_id, new_config) 
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
						'box-shadow: 5px 5px 5px rgba(0, 0, 0, 0.1);';
	
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
		var img      = 'nf_error.png';
		 
		switch (config.options.type){
				
            case 'nf_error':
                nf_style += 'color: #D8000C; background-color: #FFBABA;';
                img       = 'nf_error.png';
            break;
            
            case 'nf_info':
                nf_style += 'color: #00529B; background-color: #BDE5F8;';
                img       = 'nf_info.png';
            break;
            
            case 'nf_success':
                nf_style += 'color: #4F8A10; background-color: #DFF2BF;';
                img       = 'nf_success.png';
            break;
            
            case 'nf_warning':
                nf_style += 'color: #9F6000; background-color: #FEEFB3;';
                img       = 'nf_warning.png';
            break;
            
            default:
                nf_style += 'color: #D8000C; background-color: #FFBABA;';
                img       = 'nf_error.png';
		} 
		
		nf_style += config.style;
		
		var cancel_button = '';
		var c_pad         = 'padding: 5px 5px 5px 25px;';
		
		if (config.options.cancel_button == true)
		{
			cancel_button = "<a onclick=\"$('#"+wrapper_id+"').remove()\"><img src='/ossim/pixmaps/nf_cross.png' style='position: absolute; top: 0px; right: 0px; cursor:pointer;'/></a>";
			c_pad         = 'padding: 8px 12px 8px 18px;';
		}    
		
		var html =  "<div id='"+wrapper_id+"' style='"+ nf_style+ "'>"
                       	+ "<img src='/ossim/pixmaps/"+img+"' style='position: absolute; top: -11px; left: -11px'/>"
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


function show_notif(id, msg, type, fade, cancel, style)
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

	nt = new Noti('nt_'+id,config_nt);

	$('#'+id).html(nt.show());
	
	if(fade > 0)
	{
		$('#nt_'+id).fadeIn(1000).delay(fade).fadeOut(2000);
	}
}

	