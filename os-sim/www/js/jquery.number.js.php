<?php
header("Content-type: text/javascript");

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

list($decimal, $thousands) = Util::get_number_mark();

?>

if (typeof jQuery === 'function')
{
    $.number = function(number, decimals, dec_point, thousands_sep)
    {
    	// Set the default values here, instead so we can use them in the replace below.
    	thousands_sep = (typeof thousands_sep === 'undefined') ? ',' : '<?php echo $thousands ?>';
    	dec_point     = (typeof dec_point === 'undefined') ? '.' : '<?php echo $decimal ?>';
    	decimals      = !isFinite(+decimals) ? 0 : Math.abs(decimals);
    
    	// Work out the unicode representation for the decimal place and thousand sep.
    	var u_dec = ('\\u'+('0000'+(dec_point.charCodeAt(0).toString(16))).slice(-4));
    	var u_sep = ('\\u'+('0000'+(thousands_sep.charCodeAt(0).toString(16))).slice(-4));
    
    	// Fix the number, so that it's an actual number.
    	number = (number + '')
    		.replace('\.', dec_point) // because the number if passed in as a float (having . as decimal point per definition) we need to replace this with the passed in decimal point character
    		.replace(new RegExp(u_sep,'g'),'')
    		.replace(new RegExp(u_dec,'g'),'.')
    		.replace(new RegExp('[^0-9+\-Ee.]','g'),'');
    
    	var n = !isFinite(+number) ? 0 : +number,
    		s = '',
    		toFixedFix = function (n, decimals) 
    		{
    			var k = Math.pow(10, decimals);
    			return '' + Math.round(n * k) / k;
    		};
    
    	// Fix for IE parseFloat(0.55).toFixed(0) = 0;
    	s = (decimals ? toFixedFix(n, decimals) : '' + Math.round(n)).split('.');
    	if (s[0].length > 3) 
    	{
    		s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, thousands_sep);
    	}
    	if ((s[1] || '').length < decimals) 
    	{
    		s[1] = s[1] || '';
    		s[1] += new Array(decimals - s[1].length + 1).join('0');
    	}
    	return s.join(dec_point);
    }
    
    
    var ranges = 
    [
        {divider: 1e12 , suffix: 'T'},
        {divider: 1e9 , suffix: 'B'},
        {divider: 1e6 , suffix: 'M'},
        {divider: 1e3 , suffix: 'K'}
    ];

    $.number_readable = function(n)
    {

        for (var i = 0; i < ranges.length; i++) 
        {
            if (n >= ranges[i].divider) 
            {
                return Math.round(n / ranges[i].divider) + ranges[i].suffix;
            }
        }
    
        return n;
    }

}
