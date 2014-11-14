/*
 * jQuery geo_autocomplete plugin 1.0
 *
 * Copyright (c) 2009 Bob Hitching
 *
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 *
 * Requires jQuery Autocomplete plugin by J�rn Zaefferer - see http://bassistance.de/jquery-plugins/jquery-plugin-autocomplete/
 * jquery.autocomplete.js requires a minor modification for geo_autocomplete to work, as shown in /lib/jquery.autocomplete_geomod.js
 * 
 */
;(function($) {

$.fn.extend({
	geo_autocomplete: function(_geocoder, _options) {
		options = $.extend({}, $.Autocompleter.defaults, {
			geocoder: _geocoder,
			mapwidth: 100,
			mapheight: 100,
			maptype: 'terrain',
			mapkey: 'ABQIAAAAbnvDoAoYOSW2iqoXiGTpYBT2yXp_ZAY8_ufC3CFXhHIE1NvwkxQNumU68AwGqjbSNF9YO8NokKst8w', // localhost
			mapsensor: false,
			parse: function(_results, _status, _query) {
				var _parsed = [];
				if (_results && _status && _status == 'OK') {
					$.each(_results, function(_key, _result) {
						if (_result.geometry && _result.geometry.viewport) 
						{														
							// place is first matching segment, or first segment
							var _place_parts = _result.formatted_address.split(',');
							var _place = _place_parts[0];
							$.each(_place_parts, function(_key, _part) {
								if (_part.toLowerCase().indexOf(_query.toLowerCase()) != -1) 
								{
									_place = $.trim(_part);
									return false; // break
								}
							});
							_parsed.push({
								data: _result,
								value: _place,
								result: _result.formatted_address
							});
						}
					});
				}
				return _parsed;
			},
			formatItem: function(_data, _i, _n, _value) {
				var _src = 'https://maps-api-ssl.google.com/maps/api/staticmap?visible=' + _data.geometry.viewport.getSouthWest().toUrlValue() + '|' + _data.geometry.viewport.getNorthEast().toUrlValue() + '&size=' + options.mapwidth + 'x' + options.mapheight + '&maptype=' + options.maptype + '&key=' + options.mapkey + '&sensor=' + (options.mapsensor ? 'true' : 'false');
                //var _src = 'http://maps.google.com/maps/api/staticmap?visible=' + _data.geometry.viewport.getSouthWest().toUrlValue() + '|' + _data.geometry.viewport.getNorthEast().toUrlValue() + '&size=' + options.mapwidth + 'x' + options.mapheight + '&maptype=' + options.maptype + '&key=' + options.mapkey + '&sensor=' + (options.mapsensor ? 'true' : 'false');
				//var _place = _data.formatted_address.replace(/,/gi, ',<br/>');
				var _place = _data.formatted_address;
												
				return '<img src="' + _src + '" width="' + options.mapwidth + '" height="' + options.mapheight + '" /> ' + _place + '<br clear="both"/>';
			}
		}, _options);
		
		// if highlight is set to false, replace it with a do-nothing function
		options.highlight = options.highlight || function(value) { return value; };
		
		// if the formatMatch option is not specified, then use formatItem for backwards compatibility
		options.formatMatch = options.formatMatch || options.formatItem;

		return this.each(function() {
			new $.Autocompleter(this, options);
		});
	}
});

})(jQuery);