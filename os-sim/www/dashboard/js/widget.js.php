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

?>

function format_dot_number(num)
{
    <?php 
    list($decimal_mark, $thousand_mark) = Util::get_number_mark();
    ?>
    
	num   = num + "";
    var i = num.length-3;
    var number_mark  = "<?php echo $thousand_mark ?>";
    
    while (i>0)
    {
        num = num.substring(0,i) + number_mark + num.substring(i);
        i  -= 3;
    }
    
    return(num);
}

function jqplot_show_tooltip(elem, content, ev, plot)
{
    elem.html(content).css(
	{
		"max-width": Math.round(plot._width/1.5) + 'px'
	}).show();
	
	var h = elem.height();
	var w = elem.width();
	
	var x = ev.pageX + 7;
	var y = ev.pageY;
	
	if (w + x > plot._width)
	{
		x = x - Math.abs(plot._width - (x + w));
	}
	
	if (h + y > plot._height)
	{
		y = y - Math.abs(plot._height - (y + h));
	}
	
	elem.css(
	{
		"left": x, 
		"top": y
	}).show();
}


