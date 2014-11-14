/*
 * jixedbar - a jQuery fixed bar plugin.
 * http://code.google.com/p/jixedbar/
 * 
 * Version 0.0.4 (Beta)
 * 
 * Copyright (c) 2009-2010 Ryan Yonzon, http://ryan.rawswift.com/
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 * 
 * Last update - August 16, 2010
 */

(function($) { // start jixedbar's anonymous function

	// jixedbar plugin
	$.fn.jixedbar = function(options) {
		var constants = { // constant variables, magic variables that'll make the bar stick on the bottom or the top portion of any browser
				constOverflow: "hidden",
				constBottom: "46px"
			};
		var defaults = { // default options
				showOnTop: false, // show bar on top, instead of default bottom
				transparent: false, // enable/disable bar's transparent effect
				opacity: 0.9, // default bar opacity
				opaqueSpeed: "fast", // default opacity speed effect
				slideSpeed: "fast", // default slide effect
				roundedCorners: true, // rounded corners only works on FF, Chrome, Latest Opera and Safari
				roundedButtons: true, // only works on FF, Chrome, Latest Opera and Safari
				menuFadeSpeed: 250, // menu fade effect
				tooltipFadeSpeed: "slow", // tooltip fade effect
				tooltipFadeOpacity: 0.8, // tooltip fade opacity effect
				diffY: 0 // diff y in pixels for tooltip (alienvault)
			};
		var options = $.extend(defaults, options); // extend options
		/* IE6 detection method */
		var ie6 = (navigator.appName == "Microsoft Internet Explorer" && parseInt(navigator.appVersion) == 4 && navigator.appVersion.indexOf("MSIE 6.0") != -1);
		/* var ie7 = window.XMLHttpRequest; // simple way to detect IE7 (see variable below) */
		var ie7 = (document.all && !window.opera && window.XMLHttpRequest); // ...but I guess this is a much more accurate method
		var button_active = false; // active button flag
		var active_button_name = ""; // name of current active button
		
		this.each(function() {
			var obj = $(this); // reference to selected element
			var screen = jQuery(this); // reference to client screen size
			var fullScreen = screen.width(); // get screen width
			var centerScreen = (fullScreen/2) * (1); // get screen center
			var hideBar = false; // default bar hide/show status

			if ($(this).checkCookie("JXID")) { // check if cookie already exists
				if ($(this).readCookie("JXHID") == "true") {
					this.hideBar = true; // hide bar
				}
			} else { // else drop cookie
				$(this).createCookie("JXID", $(this).genRandID()); // set random ID and create cookie
				$(this).createCookie("JXHID", false); // set bar hide to false then create cookie
			}
			
			// set html and body style for jixedbar to work
			if (($.browser.msie && ie6) || ($.browser.msie && ie7)) { // check if we have an IE client browser
                $("html").css({"overflow" : "hidden", "height" : "100%"});
                $("body").css({"margin": "0px", "overflow": "auto", "height": "100%"});
			} else { // else for FF, Chrome, Opera, Safari and other browser
				$("html").css({"height" : "100%"});
				$("body").css({"margin": "0px", "height": "100%"});
			}

			/* check what position method to use */
			if (($.browser.msie && ie6) || ($.browser.msie && ie7)) { // for IE browsers
				pos = "absolute";
			} else { // else for other browsers
				pos = "fixed";
			}
			pos = "absolute"; // alienvault fixed
			
			/*
			// create hide container and button
			if ($(".jx-bar-button-right", this).exists()) { // check if there are currently an item on the right side portion of the bar
				$("<ul />").attr("id", "jx-hid-con-id").insertBefore($(this).find(".jx-bar-button-right:first")); // insert hide/show button "before" the existing item and let the "float right" do its magic
			} else { // else just append it and it'll automatically set to the right side of the bar
				$("<ul />").attr("id", "jx-hid-con-id").appendTo(this);
			}
			
				if ($.browser.msie && ie6) {
					$("#jx-hid-con-id").css({"width": "1px", "float": "right"}); // fix hide container width to prevent float drop issue on IE6 (any width other than "auto" or none specified)
				} else if ($.browser.msie && ie7) {
					$("#jx-hid-con-id").css({"width": "40px", "float": "right"}); // fix hide container width to prevent float drop issue on IE7
				}
			*/

			/* check what position should be the arrow indicator will be */
			if (defaults.showOnTop) {
				hideIndicator = "jx-hide-top"; // on the top
			} else {
				hideIndicator = "jx-hide"; // on the bottom
			}
			
			// insert the hide button indicator and add appropriate CSS class
			$("#jx-hid-con-id").html('<li alt="Hide toolbar"><a id="jx-hid-btn-id" class="' + hideIndicator + '"></a></li>');
			$("#jx-hid-con-id").addClass("jx-bar-button-right");
			
			// insert hide button separator and CSS class
			$("<span />").attr("id", "jx-hid-sep-id").insertAfter("#jx-hid-con-id");
			$("#jx-hid-sep-id").addClass("jx-hide-separator");
			
			// add click event on hide button
			$("#jx-hid-btn-id").parent().click(function() {
				$("#jx-menu-con-id").fadeOut();
				$(obj).slideToggle(defaults.slideSpeed, function() {
					$(this).createCookie("JXHID", true); // set bar hide to true
					if (!$(this).checkCookie("JXID")) { // check if cookie JXID exists, if not create one
						$(this).createCookie("JXID", $(this).genRandID()); // set random ID and drop cookie
					}
					$("#jx-uhid-con-id").slideToggle(defaults.slideSpeed);
				});
				return false;
			});
			
			// initialize bar
			$(this).css({
				"overflow": constants["constOverflow"],
				"position": pos
			});
			
			// set location: top or bottom
			if (defaults.showOnTop) {
				$(this).css({
					"top": constants["constBottom"]
				});				
			} else {
				$(this).css({
					"bottom": constants["constBottom"]
				});
			}
			
			// add bar style (theme)
			$(this).addClass("jx-bar");
			
			// rounded corner style (theme)
			if (defaults.roundedCorners) {
				if (defaults.showOnTop) {
					$(this).addClass("jx-bar-rounded-bl jx-bar-rounded-br");
				} else {
					$(this).addClass("jx-bar-rounded-tl jx-bar-rounded-tr");
				}
			}

			// button style (theme)
			$(this).addClass("jx-bar-button");
			
			// rounded button corner style (theme)
			if (defaults.roundedButtons) {
				$(this).addClass("jx-bar-button-rounded");
			}

			// calculate and adjust bar to the center
			marginLeft = centerScreen-($(this).width()/2);
			$(this).css({"margin-left": marginLeft});

			// fix image vertical alignment and border
			$("img", obj).css({
				"vertical-align": "bottom",
				"border": "#fff solid 0px" // no border
			});
			
			// check for alt attribute and set it as button text
			$(this).find("img").each(function() {
				var alt = $(this).attr("alt");
				
				if ( alt != "" && typeof(alt) != 'undefined' ) 
				{ // if image's ALT attribute is not empty then do the code below
					altName = "&nbsp;" + alt; // set button text using the image's ALT attribute
					$(this).parent().append(altName); // append it
				}
			});

			// check of transparency is enabled
			if (defaults.transparent) {
				$(this).fadeTo(defaults.opaqueSpeed, defaults.opacity); // do transparent effect
			}

			// create menu container first before creating the tooltip container, so tooltip will be on foreground
			$("<div />").attr("id", "jx-menu-con-id").appendTo("body");

			// add transparency effect on menu container if "transparent" is true
			if (defaults.transparent) {
				$("#jx-menu-con-id").fadeTo(defaults.opaqueSpeed, defaults.opacity);
			}
			
			/*
			 * create show/unhide container and button
			 */
			$("<div />").attr("id", "jx-uhid-con-id").appendTo("body"); // create div element and append in html body
			$("#jx-uhid-con-id").addClass("jx-show");
			$("#jx-uhid-con-id").css({
				"overflow": constants["constOverflow"],
				"position": pos,
				"margin-left": ($(this).offset().left + $(this).width()) - $("#jx-uhid-con-id").width() // calculate the show/unhide left margin/position
			});
			
			// set show/unhide location: top or bottom
			if (defaults.showOnTop) {
				$("#jx-uhid-con-id").css({
					"top": constants["constBottom"]
				});				
			} else {
				$("#jx-uhid-con-id").css({
					"bottom": constants["constBottom"]
				});				
			}
			
			// check if we need to add transparency to menu container
			if (defaults.transparent) {
				$("#jx-uhid-con-id").fadeTo(defaults.opaqueSpeed, defaults.opacity); 
			}

			// check if we need to hide the bar (based on cookie)
			if (this.hideBar) {
				$(this).css({
					"display": "none" // do not display the main bar
				});				
			}
			
			// check if we need to hide the show/unhide button (based on cookie)
			if (!this.hideBar) {
				$("#jx-uhid-con-id").css({
					"display": "none" // do not display the show/unhide button
				});
			}
			
			// create/append the show/unhide button item
			$("<ul />").attr("id", "jx-uhid-itm-id").appendTo($("#jx-uhid-con-id"));
			if (defaults.showOnTop) { // do we need to show this on top
				unhideIndicator = "jx-show-button-top";
			} else { // or on bottom (default)
				unhideIndicator = "jx-show-button";
			}
			// add the show/unhide item ("Show toolbar" button)
			$("#jx-uhid-itm-id").html('<li alt="Show toolbar"><a id="jx-uhid-btn-id" class="' + unhideIndicator + '"></a></li>');

			// show/unhide container and button style
			if (defaults.roundedCorners) {
				if (defaults.showOnTop) { // rounded corner CSS for top positioned bar
					$("#jx-uhid-con-id").addClass("jx-bar-rounded-bl jx-bar-rounded-br");
				} else { // rounded corner CSS for bottom positioned bar
					$("#jx-uhid-con-id").addClass("jx-bar-rounded-tl jx-bar-rounded-tr");
				}
			}
			$("#jx-uhid-con-id").addClass("jx-bar-button"); // add CSS style on show/unhide button based on the current theme
			if (defaults.roundedButtons) { // additional CSS style for rounded buttons
				$("#jx-uhid-con-id").addClass("jx-bar-button-rounded");
			}
			
			// add click event on show/unhide button
			$("#jx-uhid-con-id").click(function() {
				$(this).slideToggle(defaults.slideSpeed, function() {
					$(this).createCookie("JXHID", false); // set bar hide to false
					if (!$(this).checkCookie("JXID")) { // check if cookie JXID exists, if not create one
						$(this).createCookie("JXID", $(this).genRandID()); // set random ID and drop cookie
					}
					$(obj).slideToggle(defaults.slideSpeed); // slide toggle effect
					if (active_button_name != "") { // check if we have an active button (menu button)
						$("#jx-menu-con-id").fadeIn(); // if we have then do fade in effect
					}
				});
				return false; // return false to prevent any unnecessary click action
			});

			// create tooltip container
			$("<div />").attr("id", "jx-ttip-con-id").appendTo("body"); // create div element and append in html body
			$("#jx-ttip-con-id").css({ // CSS for tooltip container (invisible to viewer(s))
				"height": "auto",
                "text-align": "center",
				"margin-left": "0px",
				"width": "100%", // use entire width
				"overflow": constants["constOverflow"],
				"position": pos
			});

			var diffY = 5;
			if (defaults.diffY) diffY = diffY + defaults.diffY; // calculate bottom margin
									
			// set tooltip container: top or bottom
			if (defaults.showOnTop) { // show on top?
				$("#jx-ttip-con-id").css({
					"margin-top": $(this).height() + diffY, // put spacing between tooltip container and fixed bar
					"top": constants["constBottom"]
				});
			} else { // else bottom
				$("#jx-ttip-con-id").css({
					"margin-bottom": $(this).height() + diffY, // put spacing between tooltip container and fixed bar
					"bottom": constants["constBottom"]
				});
			}
			
			// prevent browser from showing tooltip; replace title tag with alt tag; comply with w3c standard
			$("li", obj).each(function() { // iterate through LI element
				var _title = $(this).attr("title");
				if (_title != "") {
					$(this).removeAttr("title"); // remove TITLE attribute
					$(this).attr("alt", _title); // add (replace with) ALT attribute
				}
			});
			
			// bar container hover in and out event handler
			$("li", obj).hover(
				function () { // hover in method event
					var elemID = $(this).attr("id"); // get ID (w/ or w/o ID, get it anyway)					
					var barTooltipID = elemID + "jx-ttip-id"; // set a tooltip ID
					var tooltipTitle = $(this).attr("title");
			
					if (tooltipTitle == "") { // if no 'title' attribute then try 'alt' attribute
						tooltipTitle = $(this).attr("alt"); // this prevents IE from showing its own tooltip
					}
					
					if ( tooltipTitle != "" && typeof(tooltipTitle) != 'undefined' ) { // show a tooltip if it is not empty
						// create tooltip wrapper; fix IE6's float double-margin bug
						barTooltipWrapperID = barTooltipID + "_wrapper";
						$("<div />").attr("id", barTooltipWrapperID).appendTo("#jx-ttip-con-id");
						// create tooltip div element and put it inside the wrapper
						$("<div />").attr("id", barTooltipID).appendTo("#" + barTooltipWrapperID);
						
						// tooltip default style
						$("#" + barTooltipID).css({
							"float": "left"
						});					

						// theme for tooltip (theme)
						if ((defaults.showOnTop) && !($.browser.msie && ie6)) { // IE6 workaround; Don't add tooltip pointer if IE6
							$("<div />").addClass("jx-tool-point-dir-up").appendTo("#" + barTooltipID);
						}
																		
						$("<div />").html(tooltipTitle).addClass("jx-bar-button-tooltip").appendTo("#" + barTooltipID);
							
						if ((!defaults.showOnTop) && !($.browser.msie && ie6)) { // IE6 workaround; Don't add tooltip pointer if IE6							
							$("<div />").addClass("jx-tool-point-dir-down").appendTo("#" + barTooltipID);
						}
						
						// fix tooltip wrapper relative to the associated button
						lft_pad = parseInt($(this).css("padding-left"));
						$("#" + barTooltipWrapperID).css({
							"margin-left": ($(this).offset().left - ($("#" + barTooltipID).width() / 2)) + ($(this).width()/2) + lft_pad // calculate left margin
						});
						
						/* check for active buttons; tooltip behavior */
						if ((($(this).find("a:first").attr("name") == "") || (button_active == false))) {
							$("#" + barTooltipID).fadeTo(defaults.tooltipFadeSpeed, defaults.tooltipFadeOpacity);
						} else if (active_button_name != $(this).find("a:first").attr("name")) {
							$("#" + barTooltipID).fadeTo(defaults.tooltipFadeSpeed, defaults.tooltipFadeOpacity);
						} else { // we got an active button here! (clicked state)
							$("#" + barTooltipID).css({ // prevent the tooltip from showing; if button if currently on-clicked state
								"display": "none"
							});
						}
						
					}
				}, 
				function () { // hover out method event
					var elemID = $(this).attr("id"); // get ID (whether there is an ID or none)					
					var barTooltipID = elemID + "jx-ttip-id"; // set a tooltip ID
					var barTooltipWrapperID = barTooltipID + "_wrapper";
					$("#" + barTooltipID).remove(); // remove tooltip element
					$("#" + barTooltipWrapperID).remove(); // remove tooltip's element DIV wrapper
				}
			);
			
			// show/unhide container hover in and out event handler
			$("li", $("#jx-uhid-con-id")).hover(
				function () { // in/over event
					var elemID = $(this).attr("id"); // get ID (w/ or w/o ID, get it anyway)					
					var barTooltipID = elemID + "jx-ttip-id"; // set a tooltip ID
					var tooltipTitle = $(this).attr("title");
					
					if (tooltipTitle == "") { // if no 'title' attribute then try 'alt' attribute
						tooltipTitle = $(this).attr("alt"); // this prevents IE from showing its own tooltip
					}
					
					if ( tooltipTitle != "" && typeof(tooltipTitle) != 'undefined' ) { // show a tooltip if it is not empty
						// create tooltip wrapper; fix IE6's float double-margin bug
						barTooltipWrapperID = barTooltipID + "_wrapper";
						$("<div />").attr("id", barTooltipWrapperID).appendTo("#jx-ttip-con-id");
						// create tooltip div element and put it inside the wrapper
						$("<div />").attr("id", barTooltipID).appendTo("#" + barTooltipWrapperID);
						
						// tooltip default style
						$("#" + barTooltipID).css({
							"float": "left"
						});
						
						// theme for show/unhide tooltip
						if ((defaults.showOnTop) && !($.browser.msie && ie6)) {
							$("<div />").addClass("jx-tool-point-dir-up").appendTo("#" + barTooltipID);
						}

							$("<div />").html(tooltipTitle).addClass("jx-bar-button-tooltip").appendTo("#" + barTooltipID);
						
						if ((!defaults.showOnTop) && !($.browser.msie && ie6)) { 
							$("<div />").addClass("jx-tool-point-dir-down").appendTo("#" + barTooltipID);
						}
						
						// fix tooltip wrapper relative to the associated button
						ulft_pad = parseInt($(this).css("padding-left"));
						$("#" + barTooltipWrapperID).css({
							"margin-left": ($(this).offset().left - ($("#" + barTooltipID).width() / 2)) + ($(this).width()/2) + ulft_pad // calculate tooltip position
						});
						
						/* check for active buttons; tooltip behavior */
						if ((($(this).find("a:first").attr("name") == "") || (button_active == false))) {
							$("#" + barTooltipID).fadeTo(defaults.tooltipFadeSpeed, defaults.tooltipFadeOpacity);
						} else if (active_button_name != $(this).find("a:first").attr("name")) {
							$("#" + barTooltipID).fadeTo(defaults.tooltipFadeSpeed, defaults.tooltipFadeOpacity);
						} else {
							$("#" + barTooltipID).css({ // prevent the tooltip from showing; if button if currently on-clicked state
								"display": "none"
							});
						}
						
					}
				}, 
				function () { // out event
					var elemID = $(this).attr("id"); // get ID (whether there is an ID or none)
					var barTooltipID = elemID + "jx-ttip-id"; // set a tooltip ID
					var barTooltipWrapperID = barTooltipID + "_wrapper";
					$("#" + barTooltipID).remove(); // remove tooltip element
					$("#" + barTooltipWrapperID).remove(); // remove tooltip's element DIV wrapper
				}
			);

			// fix PNG transparency problem on IE6
			if ($.browser.msie && ie6) {
				$(this).find("li").each(function() {
					$(this).find("img").each(function() {
						imgPath = $(this).attr("src");
						altName = $(this).attr("alt");
						if (altName == "") { // workaround for IE6 bug: Menu item text does not show up on the popup menu
							altName = "&nbsp;&nbsp;" + $(this).attr("title");
						}
						srcText = $(this).parent().html();
						$(this).parent().html( // wrap with span element
							'<span style="cursor:pointer;display:inline-block;filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src=\'' + imgPath + '\');">' + srcText + '</span>&nbsp;' + altName
						);
					});
					$(this).find("img").each(function() {
						$(this).attr("style", "filter:progid:DXImageTransform.Microsoft.Alpha(opacity=0);"); // show image
					})
				});
			}
			
			// adjust bar on window resize event
			$(window).resize(
				function(){
					var screen = jQuery(this); // reference to client/viewers screen
					var screenWidth = screen.width(); // get current screen width
					var centerScreen = (screenWidth / 2) * (1); // get current screen center
					var marginLeft = centerScreen - ($(obj).width() / 2); // re-calculate and adjust bar's position
					$(obj).css({"margin-left": marginLeft}); // do it!
				}
			);
			
			/**
			 * Element click events
			 */
		
			// hide first level menu
			$("li", obj).find("ul").each(function() {
				$(this).css({"display": "none"}); // hide it! but we're listening to any click event
			});

			// create menu ID
			i = 1;
			$("li", obj).find("ul").each(function() {
				$(this).attr("id", "nav-" + i);
				$(this).parent().find("a:first").attr("href", "#"); // replace href attribute
				$(this).parent().find("a:first").attr("name", "nav" + i); // replace href attribute				

				if (defaults.showOnTop) { // check what position to use
					buttonIndicator = "jx-arrow-down"; // top
				} else {
					buttonIndicator = "jx-arrow-up"; // bottom
				}

				/* IE6/IE7 arrow indicator float drop fix: user replaced insertAfter with insertBefore */
				if (($.browser.msie && ie6) || ($.browser.msie && ie7)) {
					$("<div />").attr("class", buttonIndicator).insertBefore($(this).parent().find("a")).css({"background-position": "top"}); // IE6 and IE7 fix background position
				} else { // else any other browser
					$("<div />").attr("class", buttonIndicator).insertAfter($(this).parent().find("a")); // prevent Chrome from wrapping button text
				}
				
				// add click event (button)
				$(this).parent().find("a:first").click(function() {
					var elemID = $(this).attr("id"); // get ID (whether there is an ID or none)					
					var barTooltipID = elemID + "jx-ttip-id"; // set a tooltip ID
					var barTooltipWrapperID = barTooltipID + "_wrapper";
					
					$("#" + barTooltipID).remove(); // remove tooltip element
					$("#" + barTooltipWrapperID).remove(); // remove tooltip's element DIV wrapper

					if ((button_active) && (active_button_name == $(this).attr("name"))) { // is this an active button?
						if (defaults.showOnTop) { // check bar position
							buttonIndicator = "jx-arrow-down"; // top
						} else {
							buttonIndicator = "jx-arrow-up"; // bottom
						}
						$(this).parent().find("div").attr("class", buttonIndicator); // change button indicator
						
						$("#jx-menu-con-id").fadeOut(defaults.menuFadeSpeed); // remove/hide menu using fade effect
						$(this).parent().removeClass("jx-nav-menu-active"); // remove active state for this button (style)

						if (defaults.roundedButtons) { // remove additional CSS style if rounded corner button
							$(this).parent().removeClass("jx-nav-menu-active-rounded");
						}
						
						button_active = false; // remove button's active state
						active_button_name = "";
						$(this).blur(); // unfocus link/href
					} else {
						if (defaults.showOnTop) { // is bar's on the top position?
							buttonIndicator = "jx-arrow-up";
						} else {
							buttonIndicator = "jx-arrow-down";
						}
						$(this).parent().find("div").attr("class", buttonIndicator); // change button indicator
						
						$("#jx-menu-con-id").css({"display": "none"}); // hide menu container
						$("#jx-menu-con-id").html("<ul>" + $(this).parent().find("ul").html() + "</ul>");
						$("#jx-menu-con-id").css({
												"overflow": constants["constOverflow"],
												"position": pos,
												"margin-left": $(this).parent().offset().left // calculate menu container position by setting its left margin
											});

						var diffY = 6;
						if (defaults.diffY) diffY = diffY + defaults.diffY; // calculate bottom margin

						// set menu container location: top or bottom
						if (defaults.showOnTop) { // top
							$("#jx-menu-con-id").css({
								"top": constants["constBottom"],
								"margin-top": $(obj).height() + diffY
							});
						} else { // bottom
							$("#jx-menu-con-id").css({
								"bottom": constants["constBottom"],
								"margin-bottom": $(obj).height() + diffY
							});
						}
						
						$("#jx-menu-con-id").addClass("jx-nav-menu");

							if ($.browser.msie && ie6) {	
								$("#jx-menu-con-id ul li a").css({"width": "100%"}); // IE6 and IE7 right padding/margin fix
							}

						if (defaults.roundedButtons) { // additional CSS style for rounded corner button
							$("#jx-menu-con-id").addClass("jx-nav-menu-rounded");
						}
						
						$(this).parent().addClass("jx-nav-menu-active"); // add active state CSS style
						
						if (defaults.roundedButtons) {
							$(this).parent().addClass("jx-nav-menu-active-rounded");
						}
						
						if (active_button_name != "") { // remove/hide any active button (on-clicked state)
							$("a[name='" + active_button_name + "']").parent().removeClass("jx-nav-menu-active");
							$("a[name='" + active_button_name + "']").parent().removeClass("jx-nav-menu-active-rounded");
							
							if (defaults.showOnTop) { // change button indicator (depends on the current bar's position)
								buttonIndicator = "jx-arrow-down";
							} else {
								buttonIndicator = "jx-arrow-up";
							}
							$("a[name='" + active_button_name + "']").parent().find("div").attr("class", buttonIndicator);
						}
						
						button_active = true; // change button's active state
						active_button_name = $(this).attr("name"); // save button name for future reference (e.g. remove active state)
						$(this).blur(); // unfocus link/href
						
						$("#jx-menu-con-id").fadeIn(defaults.menuFadeSpeed); // show menu container and its item(s)
					}
					return false; // prevent normal click action
				});
				
				i = i + 1;
			});
			
			// nav items click event
           	$("li", obj).click(function (event) {
				
                if ($("ul", this).exists()) {
					$(this).find("a:first").click();
					return false;
				} else if ($(this).parent().attr("id") == "jx-hid-con-id") {
					// do nothing
                    return false;
				}
				                    
                if ($("a", this).exists()) { // check if there are A tag (href) to follow
                    var target = $(this).find("a:first").attr("target");
                    if ( target != '' && typeof(target) != 'undefined' )
                        window.parent.frames[target].location = $(this).find("a:first").attr("href"); // emulate normal click event action (e.g. follow link)
                    else
                        window.parent.frames['main'].location= $(this).find("a:first").attr("href"); // emulate normal click event action (e.g. follow link)
                        
                }
                   
                return false;
			});
			
		});
		
		return this;
		
	};
	
})(jQuery); // end of anonymous function

jQuery.fn.exists = function(){return jQuery(this).length>0;};

/**
 * Create a cookie
 */
jQuery.fn.createCookie = function(cookie_name, value) {
	var expiry_date = new Date(2037, 01, 01); // virtually, never expire!
	document.cookie = cookie_name + "=" + escape(value) + ";expires=" + expiry_date.toUTCString();
};

/**
 * Check cookie
 */
jQuery.fn.checkCookie = function(cookie_name) {
	if (document.cookie.length > 0) {
  		cookie_start = document.cookie.indexOf(cookie_name + "=");
  			if (cookie_start != -1) {
    			cookie_start = cookie_start + cookie_name.length + 1;
    			cookie_end = document.cookie.indexOf(";", cookie_start);
    			if (cookie_end == -1) cookie_end = document.cookie.length
    				return true;
			}
  	}
	return false;
}

/**
 * Extract cookie value
 */
jQuery.fn.extractCookieValue = function(value) {
	  if ((endOfCookie = document.cookie.indexOf(";", value)) == -1) {
	     endOfCookie = document.cookie.length;
	  }
	  return unescape(document.cookie.substring(value, endOfCookie));
}

/**
 * Read cookie
 */
jQuery.fn.readCookie = function(cookie_name) {
	  var numOfCookies = document.cookie.length;
	  var nameOfCookie = cookie_name + "=";
	  var cookieLen = nameOfCookie.length;
	  var x = 0;
	  while (x <= numOfCookies) {
	        var y = (x + cookieLen);
	        if (document.cookie.substring(x, y) == nameOfCookie)
	           return (this.extractCookieValue(y));
	           x = document.cookie.indexOf(" ", x) + 1;
	           if (x == 0){
	              break;
	           }
	  }
	  return (null);
}	

/**
 * Generate random ID
 */
jQuery.fn.genRandID = function() {
	var id = "";
	var str = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
	for(var i=0; i < 24; i++) {
		id += str.charAt(Math.floor(Math.random() * str.length));
	}
    return id;
}

// end jixedbar package