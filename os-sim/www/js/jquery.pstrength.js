/* jQuery Password Strength Plugin (pstrength) - A jQuery plugin to provide accessibility functions
 * Author: Tane Piper (digitalspaghetti@gmail.com) 
 * Website: http://digitalspaghetti.me.uk
 * Licensed under the MIT License: http://www.opensource.org/licenses/mit-license.php
 * 
 * === Changelog ===
 * Version 1.1 (20/08/2007)
 * Changed code to be more jQuery-like
 * 
 * Version 1.0 (20/07/2007)
 * Initial version.
 */
(function($){
	$.fn.pstrength = function(o) {
		var o = $.extend({
			verdects: ['very weak', 'weak', 'medium', 'strong', 'very strong'],
			scores : [16,20,25,35]
		},o);		
		return this.each(function(){
			var e = $(this).attr('id');
			$(this).after('<div id="' + e + '_text"></div>');
			$(this).after('<div id="' + e + '_bar" style="font-size: 1px; height: 2px; width: 0px;"></div>');
			$(this).keyup(function(){				
				$.fn.runPassword($(this).val(), e, o);
			});
		});
	}
	$.fn.runPassword = function (p, f, o){
			// Check password
			nPerc = $.fn.checkPassword(p, o);	
	 		// Get controls
    		var ctlBar = "#" + f + "_bar"; 
    		var ctlText = "#" + f + "_text";
    		// Set new width
			var w = 0;
    		
			var nRound = Math.round(nPerc * 2.2);
			if (nRound < (p.length * 5)) 
			{ 
				nRound += p.length * 5; 
			}
			if (nRound > 100)
				nRound = 100;
				//$(ctlBar).css({width: nRound + "%"});
			// Color and text		
			if(nPerc <= o.scores[0])
			{
		   		strColor = "red";
	 			strText = o.verdects[0];
				w = 20;
			}
			else if (nPerc > o.scores[0] && nPerc <= o.scores[1])
			{
		   		strColor = "red";
	 			strText = o.verdects[1];
				w = 40;
			}
			else if (nPerc > o.scores[1] && nPerc <= o.scores[2])
			{
			   	strColor = "#ffd801";
	 			strText = o.verdects[2];
				w = 60;
			}
			else if (nPerc > o.scores[2] && nPerc <= o.scores[3])
			{
			   	strColor = "#3bce08";
	 			strText = o.verdects[3];
				w = 80;
			}
			else
			{
			   	strColor = "#3bce08";
	 			strText = o.verdects[4];
				w = 100;
			}
			$(ctlBar).css({width: w + "%"});
			$(ctlBar).css({backgroundColor: strColor});
			$(ctlText).html("<span style='color: " + strColor + ";'>" + strText + "</span>");
		}
		$.fn.checkPassword = function(p, o)
		{
			var intScore = 0;
			var strVerdict = o.verdects[0];	
			// PASSWORD LENGTH
			if (p.length<5)                         // length 4 or less
			{
				intScore = (intScore + 3)
			}
			else if (p.length>4 && p.length<8) // length between 5 and 7
			{
				intScore = (intScore+6)
			}
			else if (p.length>7 && p.length<16)// length between 8 and 15
			{
				intScore = (intScore+12)
			}
			else if (p.length>15)                    // length 16 or more
			{
				intScore = (intScore+18)
			}
			// LETTERS (Not exactly implemented as dictacted above because of my limited understanding of Regex)
			if (p.match(/[a-z]/))                              // [verified] at least one lower case letter
			{
				intScore = (intScore+1)
			}
			if (p.match(/[A-Z]/))                              // [verified] at least one upper case letter
			{
				intScore = (intScore+5)
			}
			// NUMBERS
			if (p.match(/\d+/))                                 // [verified] at least one number
			{
				intScore = (intScore+5)
			}
			if (p.match(/(.*[0-9].*[0-9].*[0-9])/))             // [verified] at least three numbers
			{
				intScore = (intScore+5)
			}
			// SPECIAL CHAR
			if (p.match(/.[!,@,#,$,%,^,&,*,?,_,~]/))            // [verified] at least one special character
			{
				intScore = (intScore+5)
			}
			// [verified] at least two special characters
			if (p.match(/(.*[!,@,#,$,%,^,&,*,?,_,~].*[!,@,#,$,%,^,&,*,?,_,~])/))
			{
				intScore = (intScore+5)
			}
			// COMBOS
			if (p.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/))        // [verified] both upper and lower case
			{
				intScore = (intScore+2)
			}
			if (p.match(/([a-zA-Z])/) && p.match(/([0-9])/)) // [verified] both letters and numbers
			{
				intScore = (intScore+2)
			}
	 		// [verified] letters, numbers, and special characters
			if (p.match(/([a-zA-Z0-9].*[!,@,#,$,%,^,&,*,?,_,~])|([!,@,#,$,%,^,&,*,?,_,~].*[a-zA-Z0-9])/))
			{
				intScore = (intScore+2)
			}
			return intScore;
		}
	
})(jQuery);