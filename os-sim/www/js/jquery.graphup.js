/*
 * GraphUp jQuery Plugin
 * Copyright 2010, Geert De Deckere <geert@idoe.be>
 */

 /*
 Modified 2013-08
 * Add default with 1x1 pixels
 * Force 1x1 pixels for values equal to 0
 */
(function($) {

	// GraphUp version
	var version = '1.0';

	// Find the largest or smallest number in an array
	// http://ejohn.org/blog/fast-javascript-maxmin/
	var arrayMax = function(array) {
		return Math.max.apply(Math, array);
	};
	var arrayMin = function(array) {
		return Math.min.apply(Math, array);
	};

	// Returns a RGB color array out of a colorMap based on a given percentage
	var colorPicker = function(percent, colorMap) {
		// If the color map contains only one color, it's easy
		// Also map values under 0% to the first color
		if (colorMap.length == 1 || percent <= 0) {
			var color = colorMap[0];
		}
		// Map values above 100% to the last color
		else if (percent >= 100) {
			var color = colorMap[colorMap.length - 1];
		}
		// Map values to the color map
		else {
			// Search for the color segment the percentage falls in between
			var step = 100 / (colorMap.length - 1);
			for (i = 0, j = 0; i < 100; i += step, j++) {
				if (percent >= i && percent <= i + step)
					break;
			}

			// Blend two colors
			var colorStart = colorMap[j];
			var colorEnd = colorMap[j + 1];
			var colorPercent = (percent - i) / step;
			var color = [
				Math.max(Math.min(parseInt((colorPercent * (colorEnd[0] - colorStart[0])) + colorStart[0]), 255), 0),
				Math.max(Math.min(parseInt((colorPercent * (colorEnd[1] - colorStart[1])) + colorStart[1]), 255), 0),
				Math.max(Math.min(parseInt((colorPercent * (colorEnd[2] - colorStart[2])) + colorStart[2]), 255), 0)
			];
		}

		return color;
	};

	$.fn.graphup = function(options) {
		// Merge all the options
		var o = $.extend({}, $.fn.graphup.defaults, options);

		// Load the cleaner and loader function
		var cleaner = $.fn.graphup.cleaners[o.cleaner];
		var painter = $.fn.graphup.painters[o.painter];

		// Invalid function? Get out of here.
		if ( ! $.isFunction(cleaner) || ! $.isFunction(painter))
			return this;

		// Load a predefined color map
		if (o.colorMap.constructor != Array) {
			o.colorMap = $.fn.graphup.colorMaps[o.colorMap];
		}

		// All cell values
		// Only used to find min and max values later on
		var values = [];

		// Loop over each table cell to load and clean all cell values
		this.each(function() {
			// Cache selector operations
			var $cell = $(this);

			// Load cell value with HTML stripped, and apply cleaning
			var value = cleaner($cell.text(), o);

			// Convert invalid values to the default value
			if (isNaN(value) && o.defaultValue !== null) {
				value = Number(o.defaultValue);
			}

			// Store the cleaned value in the cell's data
			$cell.data('value', value);

			// Also append value to array with all cell values
			// Note: only valid numeric values are added because arrayMax() and arrayMin() choke on anything else
			if ( ! isNaN(value)) {
				values.push(value);
			}
		});

		// The largest value, equals 100%
		var max = (o.max === null) ? arrayMax(values) : Number(o.max);

		// The smallest value, equals 0%
		var min = (o.min === null) ? arrayMin(values) : Number(o.min);

		// Get rid of large values array
		values = null;

		// Loop over each table cell again to calculate percentages and paint the graphs
		this.each(function() {
			// Cache selector operations
			var $cell = $(this);
			var value = $cell.data('value');

			// Ignore cells without a valid numeric value
			if (isNaN(value))
				return true;

			// Calculate percentage
			var percent = (value - min) / (max - min) * 100;

			// Store the percentage in the cell's data
			$cell.data('percent', percent);

			// User-defined callback, executed right before painting
			// At this point the cell value is cleaned and the percentage is calculated
			if ($.isFunction(o.callBeforePaint)) {
				o.callBeforePaint.call($cell);
			}

			// The actual cleaning
			painter($cell, o);
		});

		// Make chainable
		return this;
	};

	// Methods used for cleaning cell values in order to extract valid numeric data
	// The first argument contains the cell value (string), the second argument contains options
	$.fn.graphup.cleaners = {};

	// No cleaning is done expect for converting from string to number
	// Note: leading and trailing whitespace will be trimmed
	// https://developer.mozilla.org/en/Core_JavaScript_1.5_Reference/Global_Functions/parseFloat
	$.fn.graphup.cleaners.basic = function(value, o) {
		// parseFloat assumes dots are used as decimal points, convert them first if needed
		if (o.decimalPoint == ',') {
			value = value.replace(/,/g, '.');
		}

		return parseFloat(value);
	};

	// A cleaning function that simply strips out all non-numeric characters
	// This is quite aggressive behavior that may result in unexpected values for strings like "5m2"
	$.fn.graphup.cleaners.strip = function(value, o) {
		// Delete all non-numeric characters
		// Note: we're avoiding "new RegExp" for better performance
		value = value.replace(o.decimalPoint == '.' ? /[^-\d.]+/g : /[^-\d,]+/g, '');

		// Convert to number
		return $.fn.graphup.cleaners.basic(value, o);
	};

	// Reads time values like "hh:mm:ss" or "mm:ss", optionally followed by milliseconds
	// Converts them to a number (seconds) in order to make the values comparable to each other
	$.fn.graphup.cleaners.seconds = function(value, o) {
		// Note: we're avoiding "new RegExp" for better performance
		var regex = (o.decimalPoint == '.')
			? /\b(?:(\d+):)?(\d\d?):(\d\d(?:\.\d+)?)\b/
			: /\b(?:(\d+):)?(\d\d?):(\d\d(?:,\d+)?)\b/;

		// Look for a valid time format
		if ( ! (result = regex.exec(value)))
			return NaN;

		// Hours are optional
		if (result[1] === undefined) {
			result[1] = 0;
		}

		// Convert to one numeric value in seconds
		return parseInt(result[1]) * 3600 + parseInt(result[2]) * 60 + parseFloat(result[3]);
	};

	// Reads time values like "hh:mm", without a part for seconds
	// Converts them to a number (seconds) in order to make the values comparable to each other
	$.fn.graphup.cleaners.minutes = function(value, o) {
		// Look for a valid time format
		if ( ! (result = /\b(\d+):(\d\d)\b/.exec(value)))
			return NaN;

		// Convert to one numeric value in seconds
		return parseInt(result[1]) * 3600 + parseInt(result[2]) * 60;
	};

	// Methods used for visualizing cell values
	// An argument containing the cell (jQuery object) is given, the second argument contains options
	$.fn.graphup.painters = {};

	// Creates a bar chart in each cell
	$.fn.graphup.painters.bars = function($cell, o) {
		var percent = $cell.data('percent');
		var color = colorPicker(percent, o.colorMap);

		// Bar alignment setup; generates a piece of CSS
		switch (o.barsAlign) {
			case 'hcenter':
				var position = 'top:0; bottom:0; left:' + (100 - percent) / 2 + '%; width:' + percent + '%;';
				break;
			case 'vcenter':
				var position = 'top:' + (100 - percent) / 2 + '%; right:0; left:0; height:' + percent + '%;';
				break;
			case 'top':
				var position = 'top:0; right:0; left:0; height:' + percent + '%;';
				break;
			case 'right':
				var position = 'top:0; right:0; bottom:0; width:' + percent + '%;';
				break;
			case 'bottom':
				var position = 'right:0; bottom:0; left:0; height:' + percent + '%;';
				break;
			default: // left
				var position = 'top:0; bottom:0; left:0; width:' + percent + '%;';
		}

		// Wraps the contents of a table cell in a relatively positioned <div>
		// Table cells behave buggy when positioned relatively, that's why we need an inner <div>
		// Note: the padding of the cell is transfered to the <div>
		// Just using html() instead of the more verbose wrapInner() appears to be about 2x faster
		// Optimizing this part is important because we may have to deal with a lot of cells
		$cell.html(
			'<div style="position:relative; padding:' + $cell.css('padding-top') + ' ' + $cell.css('padding-right') + ' ' + $cell.css('padding-bottom') + ' ' + $cell.css('padding-left') + '; height:' + $cell.height() + 'px;">' +
				'<div class="' + o.classBar + '" style="position:absolute; ' + position + ' background-color:rgb(' + color + '); z-index:1;"></div>' +
				'<div style="position:relative; z-index:2;">' + $cell.html() + '</div>' +
			'</div>'
		).css('padding', 0);
	};

	// Creates a bubble in each cell
	$.fn.graphup.painters.bubbles = function($cell, o) {
		var percent = $cell.data('percent');
		// Begin * We need data at least to draw bubble
		if ($cell.data('stats')=='')
		{
			percent = 0;
		}
		// End
		var color = "rgb('" + colorPicker(percent, o.colorMap) + "')";

		// Default bubble diameter is the double of the cell's height
		// Note: this default behavior assumes equally high cells
		var diameter = (o.bubblesDiameter === null) ? $cell.innerHeight() * 2 : Number(o.bubblesDiameter);

		// For more info, see comments in the bars painter
		// Begin * At least 1x1 pixel bubbles
		var wh = percent / 100 * diameter;
		
		//
		// End
		
		if (typeof o.bubbleCellSize != 'undefined' && o.bubbleCellSize > 0)
		{
    		var cell_width = o.bubbleCellSize;
		}
		else
		{
    		var cell_width = $cell.innerWidth();
		}
		
		if (wh == 0)
		{
    		if ($cell.css('display') != 'inline-block')
    		{
    		    $cell.html("&nbsp;").css('padding', 0);
            }
		}
		else
		{
		    var min_percent = o.bubbleMinSize / o.bubblesDiameter * 100;
		    if (percent < min_percent)
		    {
    		    percent = min_percent;
    		    wh      = percent / 100 * diameter;
		    }
		    
		    $cell.html(
				'<div style="position:relative; padding:' + $cell.css('padding-top') + ' ' + $cell.css('padding-right') + ' ' + $cell.css('padding-bottom') + ' ' + $cell.css('padding-left') + '; height:' + $cell.height() + 'px;">' +
					'<div class="' + o.classBubble + '" style="position:absolute; top:' + ($cell.innerHeight() / 2 - percent / 100 * diameter / 2) + 'px; left:' + (cell_width / 2 - percent / 100 * diameter / 2) + 'px; width:' + wh + 'px; height:' + wh + 'px; background:'+ color + '; opacity:0.85; z-index:' + Math.round(percent) + '; border-radius:999px; -moz-border-radius:999px; -webkit-border-radius:999px;"></div>' +
					'<div style="position:relative; z-index:102;">' + $cell.html() + '</div>' +
				'</div>'
			).css('padding', 0);
		}
		
		
	};

	// Changes the background color of each cell
	$.fn.graphup.painters.fill = function($cell, o) {
		var percent = $cell.data('percent');
		var color = colorPicker(percent, o.colorMap);

		// The color array transforms to a comma separated string
		$cell.css('background-color', 'rgb(' + color + ')');
	};

	// Returns GraphUp version
	$.fn.graphup.version = function() {
		return version;
	};

	// Predefined color maps
	// Each map consists out of an array of RGB color values that will form an evenly spaced out gradient
	$.fn.graphup.colorMaps = {
		burn:        [[246,233,24],  [203,19,32]],
		grayPower:   [[229,229,299], [26,26,26]],
		greenPower:  [[245,248,221], [198,224,142], [64,175,94],   [9,74,36]],
		heatmap:     [[143,217,16],  [246,233,24],  [203,19,32]],
		thermometer: [[23,54,125],   [121,190,213], [214,228,231], [242,146,133], [203,19,32]]
	};

	// The complete list of default options
	$.fn.graphup.defaults = {
		barsAlign:       'left',
		bubblesDiameter: null,
		bubbleMinSize:   0, // Modified to Add this value to draw a minimun bubble size
		callBeforePaint: null,
		classBar:        'bar',
		classBubble:     'bubble',
		cleaner:         'basic',
		colorMap:        'heatmap',
		decimalPoint:    '.', // or ','
		defaultValue:    null,
		max:             null,
		min:             null,
		painter:         'fill'
	};

})(jQuery);