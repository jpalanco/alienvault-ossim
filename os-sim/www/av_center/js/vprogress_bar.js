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


(function( $ ){
	$.fn.animateVProgress = function(progress, duration, callback) {    
		return this.each(function() {
		$(this).animate({
			height: progress+'%'
		}, 
		{
			duration: ( duration == '' ) ? 1000 : duration, 
			
			// swing or linear
			easing: 'linear',

		// this gets called every step of the animation, and updates the label
			step: function( progress ){
				var labelEl = $('.vp-ui-title', this);
								  
				if (Math.ceil(progress) == 100) 
				{
					labelEl.text('100 %');
					setTimeout(function() {
						labelEl.fadeOut();
					}, 1000);
				}else{
					labelEl.text(Math.ceil(progress) + '%');
				}
			},
		
			complete: function(scope, i, elem) {
			  if (callback) {
				callback.call(this, i, elem );
			  };
			}
		});
	});
};
})( jQuery );