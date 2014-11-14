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


/****************************************************************
*************************** Utilities ***************************
*****************************************************************/

String.prototype.trim = function() { return this.replace(/^\s+|\s+$/g, ""); }


String.prototype.stripTags = function() { return this.replace(/<[^>]+>/g,'');} 


String.prototype.capitalize = function() {
    return this.charAt(0).toUpperCase() + this.slice(1);
}

$.getDocHeight = function(){
    var D = document;
    return Math.max(Math.max(D.body.scrollHeight, D.documentElement.scrollHeight), 
           Math.max(D.body.offsetHeight, D.documentElement.offsetHeight), Math.max(D.body.clientHeight, D.documentElement.clientHeight));
};



/****************************************************************
************************** Loading Box **************************
*****************************************************************/

function Message(){}

Message.show_loading_box = function(id, config){
    
    var cbutton_style =  ( typeof(config.cancel_button != 'undefined') && config.cancel_button == true ) ? "" : "style='display:none;'";
            
    var cancel_button = "<a id='cancel_loading' "+cbutton_style+"><img src='/ossim/pixmaps/nf_cross.png' style='position: absolute; top: 0px; right: 0px; cursor:pointer;'/></a>";
        
    var html = "<div class='g_loading_panel' id='"+id+"' style='"+config.style+"'>" +
                    cancel_button +
                    "<div style='padding: 0px; overflow: hidden;'>" +
                        "<table>" +
                            "<tr>" +
                                "<td class='l_lp'><div id='l_lp_loading'></div></td>" +
                                "<td class='r_lp' style='width:auto;'>"+ config.content + "</td>" +
                            "</tr>" +
                        "</table>" +
                    "</div>" +
                "</div>";

    return html;
};


Message.show_loading_spinner = function(id, config){
    
    var html = "<div id='"+id+"' style='"+config.style+"'></div>";
          
    return html;
};


//Show loading box
function show_loading_box(container, content, style)
{
     
    var ch  = $('#'+container).height()+'px'; // Container height
    var st  = $(window).scrollTop(); // Scroll
    var wh  = $.getDocHeight(); // Window height
	var ci  = parseInt((wh / 6));
	var top = parseInt((wh / 2) + st - ci) +'px'; //Loading box position

    var style  = (typeof(style) == 'undefined' || style == '') ? "width: 300px; left: 50%; position: absolute; margin-left: -155px;" : style;
        style += ' top: '+ top + '; display:none;';
    
    //Get Loading box
    if ($('.av_w_overlay').length < 1)
    {
        $('#'+container).prepend('<div class="av_w_overlay" style="height:'+ ch +';"></div>');
    }
    
    var config  = {
        content: content,
        style: 'width: 100%; padding: 10px 0px;',
        cancel_button: false
    };

    var loading_box = Message.show_loading_box('s_box', config);

    if ($('.l_box').length < 1)
    {
        $('body').prepend('<div class="l_box" style="'+style+'">'+loading_box+'</div>');
    }

    //Show loading box
    $('.l_box').show();
}


//Hide loading box
function hide_loading_box()
{
    $('.av_w_overlay').remove();
    $('.l_box').remove(); 
}


function is_loading_box()
{
    return $('.av_w_overlay').length > 0;
}



/****************************************************************
**************************** Session ****************************
*****************************************************************/

function Session(data, url)
{
    this.url     = ( url == '' ) ? '/ossim/session/login.php?action=logout' : url;
    this.data    = '';
	
	if (typeof(data) == 'string' && data != '')
	{
		this.data = data;
	}
	else if (typeof(data) == 'object')
	{	
		try
		{
			this.data = data.responseText;
		}
		catch(err)
		{}
	}
	
	
    this.check_session_expired = function(){
        		
		if (typeof(this.data) == 'string' && this.data != null && this.data.match(/\<meta /im))
		{
            return true;
        }
		
		return false;
    };
	
    
    this.redirect = function(){
		
		//console.log(section.current_section)
			
		if (typeof(window.parent) != 'undefined' && window.parent != null)
		{ 
			window.parent.document.location.href = this.url;
		}
		else
		{
			document.location.href = this.url;
		}
		
        return;
    };
	
	this.set_data = function(data){
		this.data = data;
	};
}


//-------------------------------------------------------------------------
// ***********************          Base 64         ***********************
//-------------------------------------------------------------------------

var Base64 = {
 
	// private property
	_keyStr : "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",
 
	// public method for encoding
	encode : function (input) {
		var output = "";
		var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
		var i = 0;
 
		input = Base64._utf8_encode(input);
 
		while (i < input.length) {
 
			chr1 = input.charCodeAt(i++);
			chr2 = input.charCodeAt(i++);
			chr3 = input.charCodeAt(i++);
 
			enc1 = chr1 >> 2;
			enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
			enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
			enc4 = chr3 & 63;
 
			if (isNaN(chr2)) {
				enc3 = enc4 = 64;
			} else if (isNaN(chr3)) {
				enc4 = 64;
			}
 
			output = output +
			this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) +
			this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);
 
		}
 
		return output;
	},
 
	// public method for decoding
	decode : function (input) {
		var output = "";
		var chr1, chr2, chr3;
		var enc1, enc2, enc3, enc4;
		var i = 0;
 
		input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");
 
		while (i < input.length) {
 
			enc1 = this._keyStr.indexOf(input.charAt(i++));
			enc2 = this._keyStr.indexOf(input.charAt(i++));
			enc3 = this._keyStr.indexOf(input.charAt(i++));
			enc4 = this._keyStr.indexOf(input.charAt(i++));
 
			chr1 = (enc1 << 2) | (enc2 >> 4);
			chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
			chr3 = ((enc3 & 3) << 6) | enc4;
 
			output = output + String.fromCharCode(chr1);
 
			if (enc3 != 64) {
				output = output + String.fromCharCode(chr2);
			}
			if (enc4 != 64) {
				output = output + String.fromCharCode(chr3);
			}
 
		}
 
		output = Base64._utf8_decode(output);
 
		return output;
 
	},
 
	// private method for UTF-8 encoding
	_utf8_encode : function (string) {
		
		string = (string == null) ? '' : string;
		string = string.replace(/\r\n/g,"\n");
		var utftext = "";
 
		for (var n = 0; n < string.length; n++) {
 
			var c = string.charCodeAt(n);
 
			if (c < 128) {
				utftext += String.fromCharCode(c);
			}
			else if((c > 127) && (c < 2048)) {
				utftext += String.fromCharCode((c >> 6) | 192);
				utftext += String.fromCharCode((c & 63) | 128);
			}
			else {
				utftext += String.fromCharCode((c >> 12) | 224);
				utftext += String.fromCharCode(((c >> 6) & 63) | 128);
				utftext += String.fromCharCode((c & 63) | 128);
			}
 
		}
 
		return utftext;
	},
 
	// private method for UTF-8 decoding
	_utf8_decode : function (utftext) {
		var string = "";
		var i = 0;
		var c = c1 = c2 = 0;
 
		while ( i < utftext.length ) {
 
			c = utftext.charCodeAt(i);
 
			if (c < 128) {
				string += String.fromCharCode(c);
				i++;
			}
			else if((c > 191) && (c < 224)) {
				c2 = utftext.charCodeAt(i+1);
				string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
				i += 2;
			}
			else {
				c2 = utftext.charCodeAt(i+1);
				c3 = utftext.charCodeAt(i+2);
				string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
				i += 3;
			}
 
		}
 
		return string;
	}
 }


function htmlentities (string, quote_style)
{
    // Convert all applicable characters to HTML entities  
    // 
    // version: 1008.1718
    // discuss at: http://phpjs.org/functions/htmlentities    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: nobbler
    // +    tweaked by: Jack
    // +   bugfixed by: Onno Marsman    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +    bugfixed by: Brett Zamir (http://brett-zamir.me)
    // +      input by: Ratheous
    // -    depends on: get_html_translation_table
    // *     example 1: htmlentities('Kevin & van Zonneveld');    // *     returns 1: 'Kevin &amp; van Zonneveld'
    // *     example 2: htmlentities("foo'bar","ENT_QUOTES");
    // *     returns 2: 'foo&#039;bar'
    var hash_map = {}, symbol = '', tmp_str = '', entity = '';
    tmp_str = string.toString();    
    
	if (false === (hash_map = this.get_html_translation_table('HTML_ENTITIES', quote_style))) 
	{
        return false;
    }
    
	hash_map["'"] = '&#039;';
	
	//Fix problem with &
	delete hash_map['&'];
	tmp_str = tmp_str.replace(/\&/g, "&amp;");
		
    for (symbol in hash_map){
        entity = hash_map[symbol];
        tmp_str = tmp_str.split(symbol).join(entity);
    }
    
	//Hack Chinese Characters
	tmp_str = tmp_str.replace(/&amp;#(\d{4,5});/g, "&#$1;");
	
	return tmp_str;
}


function html_entity_decode(string, quote_style)
{
    // Convert all HTML entities to their applicable characters  
    // 
    // version: 1009.2513
    // discuss at: http://phpjs.org/functions/html_entity_decode    // +   original by: john (http://www.jd-tech.net)
    // +      input by: ger
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Onno Marsman    // +   improved by: marc andreu
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: Ratheous
    // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
    // +      input by: Nick Kolosov (http://sammy.ru)    // +   bugfixed by: Fox
    // -    depends on: get_html_translation_table
    // *     example 1: html_entity_decode('Kevin &amp; van Zonneveld');
    // *     returns 1: 'Kevin & van Zonneveld'
    // *     example 2: html_entity_decode('&amp;lt;');    // *     returns 2: '&lt;'
    var hash_map = {}, symbol = '', tmp_str = '', entity = '';
    tmp_str = string.toString();
    
    if (false === (hash_map = this.get_html_translation_table('HTML_ENTITIES', quote_style))) {        return false;
    }
 
    // fix &amp; problem
    // http://phpjs.org/functions/get_html_translation_table:416#comment_97660    delete(hash_map['&']);
    hash_map['&'] = '&amp;';
 
    for (symbol in hash_map) {
        entity = hash_map[symbol];
        tmp_str = tmp_str.split(entity).join(symbol);
    }
    tmp_str = tmp_str.split('&#039;').join("'");
	
	return tmp_str;
}


function get_html_translation_table (table, quote_style)
{
    // http://kevin.vanzonneveld.net
    // +   original by: Philip Peterson
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: noname
    // +   bugfixed by: Alex
    // +   bugfixed by: Marco
    // +   bugfixed by: madipta
    // +   improved by: KELAN
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
    // +      input by: Frank Forte
    // +   bugfixed by: T.Wild
    // +      input by: Ratheous
    // %          note: It has been decided that we're not going to add global
    // %          note: dependencies to php.js, meaning the constants are not
    // %          note: real constants, but strings instead. Integers are also supported if someone
    // %          note: chooses to create the constants themselves.
    // *     example 1: get_html_translation_table('HTML_SPECIALCHARS');
    // *     returns 1: {'"': '&quot;', '&': '&amp;', '<': '&lt;', '>': '&gt;'}
    
    var entities = {}, hash_map = {}, decimal = 0, symbol = '';
    var constMappingTable = {}, constMappingQuoteStyle = {};
    var useTable = {}, useQuoteStyle = {};
    
    // Translate arguments
    constMappingTable[0]      = 'HTML_SPECIALCHARS';
    constMappingTable[1]      = 'HTML_ENTITIES';
    constMappingQuoteStyle[0] = 'ENT_NOQUOTES';
    constMappingQuoteStyle[2] = 'ENT_COMPAT';
    constMappingQuoteStyle[3] = 'ENT_QUOTES';

    useTable       = !isNaN(table) ? constMappingTable[table] : table ? table.toUpperCase() : 'HTML_SPECIALCHARS';
    useQuoteStyle = !isNaN(quote_style) ? constMappingQuoteStyle[quote_style] : quote_style ? quote_style.toUpperCase() : 'ENT_COMPAT';

    if (useTable !== 'HTML_SPECIALCHARS' && useTable !== 'HTML_ENTITIES') {
        throw new Error("Table: "+useTable+' not supported');
        // return false;
    }

    entities['38'] = '&amp;';
    if (useTable === 'HTML_ENTITIES') {
        entities['160'] = '&nbsp;';
        entities['161'] = '&iexcl;';
        entities['162'] = '&cent;';
        entities['163'] = '&pound;';
        entities['164'] = '&curren;';
        entities['165'] = '&yen;';
        entities['166'] = '&brvbar;';
        entities['167'] = '&sect;';
        entities['168'] = '&uml;';
        entities['169'] = '&copy;';
        entities['170'] = '&ordf;';
        entities['171'] = '&laquo;';
        entities['172'] = '&not;';
        entities['173'] = '&shy;';
        entities['174'] = '&reg;';
        entities['175'] = '&macr;';
        entities['176'] = '&deg;';
        entities['177'] = '&plusmn;';
        entities['178'] = '&sup2;';
        entities['179'] = '&sup3;';
        entities['180'] = '&acute;';
        entities['181'] = '&micro;';
        entities['182'] = '&para;';
        entities['183'] = '&middot;';
        entities['184'] = '&cedil;';
        entities['185'] = '&sup1;';
        entities['186'] = '&ordm;';
        entities['187'] = '&raquo;';
        entities['188'] = '&frac14;';
        entities['189'] = '&frac12;';
        entities['190'] = '&frac34;';
        entities['191'] = '&iquest;';
        entities['192'] = '&Agrave;';
        entities['193'] = '&Aacute;';
        entities['194'] = '&Acirc;';
        entities['195'] = '&Atilde;';
        entities['196'] = '&Auml;';
        entities['197'] = '&Aring;';
        entities['198'] = '&AElig;';
        entities['199'] = '&Ccedil;';
        entities['200'] = '&Egrave;';
        entities['201'] = '&Eacute;';
        entities['202'] = '&Ecirc;';
        entities['203'] = '&Euml;';
        entities['204'] = '&Igrave;';
        entities['205'] = '&Iacute;';
        entities['206'] = '&Icirc;';
        entities['207'] = '&Iuml;';
        entities['208'] = '&ETH;';
        entities['209'] = '&Ntilde;';
        entities['210'] = '&Ograve;';
        entities['211'] = '&Oacute;';
        entities['212'] = '&Ocirc;';
        entities['213'] = '&Otilde;';
        entities['214'] = '&Ouml;';
        entities['215'] = '&times;';
        entities['216'] = '&Oslash;';
        entities['217'] = '&Ugrave;';
        entities['218'] = '&Uacute;';
        entities['219'] = '&Ucirc;';
        entities['220'] = '&Uuml;';
        entities['221'] = '&Yacute;';
        entities['222'] = '&THORN;';
        entities['223'] = '&szlig;';
        entities['224'] = '&agrave;';
        entities['225'] = '&aacute;';
        entities['226'] = '&acirc;';
        entities['227'] = '&atilde;';
        entities['228'] = '&auml;';
        entities['229'] = '&aring;';
        entities['230'] = '&aelig;';
        entities['231'] = '&ccedil;';
        entities['232'] = '&egrave;';
        entities['233'] = '&eacute;';
        entities['234'] = '&ecirc;';
        entities['235'] = '&euml;';
        entities['236'] = '&igrave;';
        entities['237'] = '&iacute;';
        entities['238'] = '&icirc;';
        entities['239'] = '&iuml;';
        entities['240'] = '&eth;';
        entities['241'] = '&ntilde;';
        entities['242'] = '&ograve;';
        entities['243'] = '&oacute;';
        entities['244'] = '&ocirc;';
        entities['245'] = '&otilde;';
        entities['246'] = '&ouml;';
        entities['247'] = '&divide;';
        entities['248'] = '&oslash;';
        entities['249'] = '&ugrave;';
        entities['250'] = '&uacute;';
        entities['251'] = '&ucirc;';
        entities['252'] = '&uuml;';
        entities['253'] = '&yacute;';
        entities['254'] = '&thorn;';
        entities['255'] = '&yuml;';
    }

    if (useQuoteStyle !== 'ENT_NOQUOTES') {
        entities['34'] = '&quot;';
    }
    if (useQuoteStyle === 'ENT_QUOTES') {
        entities['39'] = '&#39;';
    }
    entities['60'] = '&lt;';
    entities['62'] = '&gt;';


    // ascii decimals to real symbols
    for (decimal in entities) {
        symbol = String.fromCharCode(decimal);
        hash_map[symbol] = entities[decimal];
    }
    
    return hash_map;
}


function valid_ip(ip)
{
    ip    = (typeof ip == 'undefined') ? '' : ip;
    
    regex = /^(([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])$/;
    
    if(ip.match(regex))
    {
        return true;
    }
    else
    {
        return false;
    }
}


function uniqid()
{
    var newDate = new Date;
    return newDate.getTime();
}


function urlencode(textoAcodificar)
{
	var nocodificar = "0123456789"+"ABCDEFGHIJKLMNOPQRSTUVWXYZ"+"abcdefghijklmnopqrstuvwxyz" +"-_.!~*'()";
	var HEX = "0123456789ABCDEF";
	var codificado = "";
	if (typeof(textoAcodificar) != 'undefined')
		for (var i = 0; i < textoAcodificar.length; i++ ) {
			var ch = textoAcodificar.charAt(i);
		    if (ch == " ") {
			    codificado += "+";
			} else if (nocodificar.indexOf(ch) != -1) {
			    codificado += ch;
			} else {
			    var charCode = ch.charCodeAt(0);
				
				if (charCode > 255) {
				   /* alert( "Caracter Unicode '"+ch+"' no puede ser codificado utilizando la codificación URL estandar.\n" +
					          "(sólo soporta caracteres de 8-bit.)\n" +
							  "Será sustituido por un símbolo de suma (+)." ); */
					codificado += "+";
				} else {
					codificado += "%";
					codificado += HEX.charAt((charCode >> 4) & 0xF);
					codificado += HEX.charAt(charCode & 0xF);
				}
			}
		}
	return codificado;
}


function urldecode(codificado){
   var HEXCHARS = "0123456789ABCDEFabcdef"; 
   var textoAcodificar = "";
   var i = 0;
   if (typeof(codificado) != 'undefined')
		while (i < codificado.length) {
			var ch = codificado.charAt(i);
			if (ch == "+") {
				textoAcodificar += " ";
				i++;
			} else if (ch == "%") {
				if (i < (codificado.length-2) 
						&& HEXCHARS.indexOf(codificado.charAt(i+1)) != -1 
						&& HEXCHARS.indexOf(codificado.charAt(i+2)) != -1 ) {
					textoAcodificar += unescape( codificado.substr(i,3) );
					i += 3;
				} else {
					//alert( 'Bad escape combination near ...' + codificado.substr(i) );
					textoAcodificar += "%[ERROR]";
					i++;
				}
			} else {
				textoAcodificar += ch;
				i++;
			}
		}
   return textoAcodificar;
}


function av_window_open(url, o)
{
    o = $.extend(
    {
        width      : 1024,
        height     : 768,
        location   : 'no',
        menubar    : 'no',
        resizable  : 'yes',
        scrollbars : 'yes',
        status     : 'no',
        titlebar   : 'no',
        title      : 'AlienVault'
    }, o || {});


    //Window will appear centered
    var left = (screen.width / 2) - (o.width / 2);
    var top  = (screen.height / 2) - (o.height / 2);

    var w_parameters  = "left=" + left + ",";
        w_parameters  += "top=" + top + ",";
        w_parameters  += "height=" + o.height + ",";
        w_parameters  += "width=" + o.width + ",";
        w_parameters  += "location=" + o.location + ",";
        w_parameters  += "menubar=" + o.menubar + ",";
        w_parameters  += "resizable=" + o.resizable + ",";
        w_parameters  += "scrollbars=" + o.scrollbars + ",";
        w_parameters  += "status=" + o.status + ",";
        w_parameters  += "titlebar=" + o.titlebar;


    try
    {
        //Initialize window with loading url (Same domain that our page)
        h_window = window.open('/ossim/loading.php', o.title, w_parameters);
        
        //Set focus
        h_window.focus();
        
        //After opening the new window, change window url
        h_window.location.href = url;
    }
    catch(Err){}
    
    return h_window;
}

function av_alert(msg)
{
    if (typeof vex != 'undefined')
    {
        vex.dialog.alert(msg);
    }
    else if  (typeof top.vex != 'undefined')
    {
        top.vex.dialog.alert(msg);
    }
    else
    {
        alert(msg);
    }
}


function av_confirm(msg, opts)
{
    var def  = $.Deferred();
    var _vex = false;

    if (typeof vex != 'undefined')
    {
        _vex = vex;
    }
    else if  (typeof top.vex != 'undefined')
    {
        _vex = top.vex;
    }
    else
    {
        if(confirm(msg))
        {
            def.resolve();
        }
        else
        {
            def.reject();
        }
        
        return def.promise();
    }

    /*  If we arrive here is because we could detect the vex plugin  */

    //Saving the default options
    var backup = $.extend(true, {}, _vex.dialog.buttons);

    //Default options
    var defaults = {
        yes : "",
        no  : ""
    };

    opts = $.extend(defaults, opts)


    if (opts['yes'].length > 0)
    {
        _vex.dialog.buttons.YES.text = opts['yes'];
    }
    if (opts['no'].length > 0)
    {
        _vex.dialog.buttons.NO.text  = opts['no'];
    }

    _vex.dialog.confirm(
    {
        message: msg,
        callback: function(value)
        {
            if (value)
            {
                def.resolve();
            }
            else
            {
                def.reject();
            }
        }
    });

    //Restoring the default options
    _vex.dialog.buttons = $.extend(true, _vex.dialog.buttons, backup);

    return def.promise();
}


/* 
    The variable internet is a variable loaded in a remote script.
    It is loaded in /home/index.php
    <script type="text/javascript" src="https://www.alienvault.com/product/help/ping.js"></script>
*/
function is_internet_available()
{
    var cond1 = typeof __internet != 'undefined' && __internet == true;
    var cond2 = typeof top.__internet != 'undefined' && top.__internet == true;
    
    if (cond1 || cond2)
    {
        return true;
    }

    return false;

}


function sleep(milliseconds)
{
    var start = new Date().getTime();
    
    while ((new Date().getTime() - start) < milliseconds) {}
}


function format_dot_number(num)
{
    num = parseFloat(num);
    
    if (typeof num != 'number' || isNaN(num))
    {
        return 0;
    }

    return num.toLocaleString();
}


function bytes_to_size(bytes, precision)
{
    precision = ('undefined' === typeof precision) ? 2 : precision;

    if (bytes == 0)
    {
        return '0 B';
    }

    var sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

    var k = 1024;
    var i = Math.floor(Math.log(bytes) / Math.log(k));

    return (bytes / Math.pow(k, i)).toFixed(precision) + ' ' + sizes[i];
}


function number_readable(num)
{
    num = parseInt(num);

    if (typeof num != 'number' || isNaN(num) || num < 1)
    {
        return 0;
    }

    var power = 1000; // 1024
    var unit  = ['','K+','M+','G+','T+','P+','E+'];

    var i     = Math.floor(Math.log(num) / Math.log(power));
    var val   = Math.pow(power, i);

    val = Math.floor(num / val);
    val = val + unit[i];

    return val;
}
