/*!
 * jquery.tagcloud.js
 * A Simple Tag Cloud Plugin for JQuery
 *
 * https://github.com/addywaddy/jquery.tagcloud.js
 * created by Adam Groves
 */
(function($) {

  /*global jQuery*/
  "use strict";

  var compareWeights = function(a, b)
  {
    return a - b;
  };

  // Converts hex to an RGB array
  var toRGB = function(code) {
    if (code.length === 4) {
      code = jQuery.map(/\w+/.exec(code), function(el) {return el + el; }).join("");
    }
    var hex = /(\w{2})(\w{2})(\w{2})/.exec(code);
    return [parseInt(hex[1], 16), parseInt(hex[2], 16), parseInt(hex[3], 16)];
  };

  // Converts an RGB array to hex
  var toHex = function(ary) {
    return "#" + jQuery.map(ary, function(i) {
      var hex =  i.toString(16);
      hex = (hex.length === 1) ? "0" + hex : hex;
      return hex;
    }).join("");
  };

  var colorIncrement = function(color, range) {
    return jQuery.map(toRGB(color.end), function(n, i) {
      return (n - toRGB(color.start)[i])/range;
    });
  };

  var tagColor = function(color, increment, weighting) {
    var rgb = jQuery.map(toRGB(color.start), function(n, i) {
      var ref = Math.round(n + (increment[i] * weighting));
      if (ref > 255) {
        ref = 255;
      } else {
        if (ref < 0) {
          ref = 0;
        }
      }
      return ref;
    });
    return toHex(rgb);
  };

  $.fn.tagcloud = function(options) {

    var opts = $.extend({}, $.fn.tagcloud.defaults, options);
	
    var tagWeights = this.map(function(){
      return $(this).attr("rel");
    });
	tagWeights  = jQuery.makeArray(tagWeights);
	
    var lowest  = Math.min.apply( Math, tagWeights );
    var highest = Math.max.apply( Math, tagWeights );
    var range = highest - lowest;
    if(range === 0) {range = 1;}
    // Sizes
    var fontIncr, colorIncr;
    if (opts.size) {
      fontIncr = (opts.size.end - opts.size.start)/range;
    }
    // Colors
    if (opts.color) {
      colorIncr = colorIncrement (opts.color, range);
    }
	
	if(range == 0)
	{
		var maxPercent = 150, minPercent = 150;
	}
	else if(range < 5)
	{
		var maxPercent = 150, minPercent = 125;
	}
	else
	{
		var maxPercent = 200, minPercent = 70;
	}
	
	var multiplier = (maxPercent-minPercent)/(range);
	
    return this.each(function() {
		
      var elem = $(this).attr("rel");
	  
	  $(this).addClass("tag_cloud_elem");
	  
      if (opts.size) {
		 //var weighting = Math.round((Math.log(elem)/Math.log(highest) *(opts.size.end-opts.size.start) + opts.size.start) + 0.5);
		 var weighting = minPercent + ((highest-(highest-(elem-lowest)))*multiplier) + '%'; 
        $(this).css({"font-size": weighting});
      }
      if (opts.color) {
		var weighting = elem - lowest;
        $(this).css({"color": tagColor(opts.color, colorIncr, weighting)});
      }
    });
  };

  $.fn.tagcloud.defaults = {
    size: {start: 14, end: 18, unit: "pt"}
  };

})(jQuery);
