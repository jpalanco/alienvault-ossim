/* Greybox Redux
 * Required: http://jquery.com/
 * Written by: John Resig
 * Based on code by: 4mir Salihefendic (http://amix.dk)
 * License: LGPL (read more in LGPL.txt)
 * 2009-05-1 modified by jmalbarracin. Added GB_TYPE. Fixed  total document width/height
 * 2009-06-4 modified by jmalbarracin. Support of width %, height %
 * 2009-09-19 Added maximized window
 */

var GB_DONE   = false;
var GB_TYPE   = ''; // empty or "w"
var GB_HEIGHT = 400;
var GB_WIDTH  = 400;
//var GB_SCROLL_DIFF = (navigator.appVersion.match(/MSIE/)) ? 1 : ((navigator.appCodeName.match(/Mozilla/)) ? 17 : 17 );
var GB_HDIFF = (navigator.appVersion.match(/MSIE/)) ? 12 : ((navigator.appCodeName.match(/Mozilla/)) ? 42 : 18 );
var GB_SCROLL_DIFF = 0;
//var GB_HDIFF = 5;
var GB_SLEEP = (navigator.appVersion.match(/MSIE/)) ? 1000 : 0;
var GB_URL_AUX = "";
var GB_top = 0;


function GB_show(caption, url, height, width, top) {
  GB_HEIGHT = height || 400;
  GB_WIDTH = width || 400;
  GB_URL_AUX = url;
  GB_top = top || 0;

  if(!GB_DONE) {
	$(document.body).append("<div id='GB_overlay" + GB_TYPE + "'></div><div id='GB_window'><div id='GB_head'><div id='GB_caption'></div><div id='GB_table'><table><tr><td><img src='/ossim/pixmaps/theme/close.png' id='GB_closeimg' alt='Close' title='Close'></td></tr></table></div></div></div>");
	$("#GB_closeimg").click(GB_hide);
	$("#GB_maximg").click(GB_maximize);
	$("#GB_overlay" + GB_TYPE).click(GB_hide);
	$(window).resize(GB_position);
	GB_DONE = true;
  }
  $("#GB_frame").remove();
  $("#GB_caption").html(caption);
  $("#GB_overlay" + GB_TYPE).show();
  GB_position();

  $("#GB_window").show();

  if (GB_SLEEP>0) sleep(GB_SLEEP);
  $("#GB_window").append("<iframe id='GB_frame' name='GB_frame' src='"+url+"' frameborder='0'></iframe>");
}

function GB_show_nohide(caption, url, height, width) {
  GB_HEIGHT = height || 400;
  GB_WIDTH = width || 400;
  GB_URL_AUX = url;

  if(!GB_DONE) {
	$(document.body).append("<div id='GB_overlay" + GB_TYPE + "'></div><div id='GB_window'><div id='GB_head'><div id='GB_caption'></div><div id='GB_table'><table><tr><td><img src='/ossim/pixmaps/theme/close.png' id='GB_closeimg' alt='Close' title='Close'></td></tr></table></div></div></div>");
	$("#GB_overlay" + GB_TYPE).click(GB_onlyhide);
	$("#GB_closeimg").click(GB_onlyhide);
	$(window).resize(GB_position);
	GB_DONE = true;
  }
  $("#GB_frame").remove();
  $("#GB_caption").html(caption);
  $("#GB_overlay" + GB_TYPE).show();
  GB_position();

  $("#GB_window").show();

  if (GB_SLEEP>0) sleep(GB_SLEEP);
  $("#GB_window").append("<iframe id='GB_frame' name='GB_frame' src='"+url+"' frameborder='0'></iframe>");
}

function sleep(milliseconds) {
  var start = new Date().getTime();
  for (var i = 0; i < 1e7; i++) {
    if ((new Date().getTime() - start) > milliseconds){
      break;
    }
  }
}

function GB_onlyhide() {
  $("#GB_window,#GB_overlay" + GB_TYPE).hide();
  if (typeof(GB_onclose) == "function") GB_onclose(GB_URL_AUX);
}

function GB_hide() {
  $("#GB_window,#GB_overlay" + GB_TYPE).hide();
  if (typeof(GB_onclose) == "function") GB_onclose(GB_URL_AUX);
}

function GB_maximize() {
  $("#GB_window,#GB_overlay" + GB_TYPE).hide();
  if (typeof(GB_onclose) == "function") GB_onclose(GB_URL_AUX);
  window.open(GB_URL_AUX, '', 'fullscreen=yes,scrollbars=yes');
}

function GB_position() {
  var de = document.documentElement;
  // total document width
  var w = document.body.scrollWidth
  if (self.innerWidth > w) w = self.innerWidth;
  if (de && de.clientWidth > w) w = de.clientWidth;
  if (document.body.clientWidth > w) w = document.body.clientWidth;
  
  w = w - GB_SCROLL_DIFF; 
    
  // total document height
  var h = document.body.scrollHeight
  if ((self.innerHeight+window.scrollMaxY) > h) h = self.innerHeight+window.scrollMaxY;
  if (de && de.clientHeight > h) h = de.clientHeight;
  if (document.body.clientHeight > h) h = document.body.clientHeight;
  
  $("#GB_overlay" + GB_TYPE).css({width:(w)+"px",height:(h)+"px"});
   
  var sy_correction = (navigator.appVersion.match(/MSIE/)) ? 30 : 0;  
  var sy = document.documentElement.scrollTop || document.body.scrollTop - sy_correction;
  var ww = (typeof(GB_WIDTH) == "string" && GB_WIDTH.match(/\%/)) ? GB_WIDTH : GB_WIDTH+"px";
  var wp = (typeof(GB_WIDTH) == "string" && GB_WIDTH.match(/\%/)) ? w*(GB_WIDTH.replace(/\%/,''))/100 : GB_WIDTH;
  
  var hw = (typeof(GB_HEIGHT) == "string" && GB_HEIGHT.match(/\%/)) ? GB_HEIGHT- GB_HDIFF : (GB_HEIGHT- GB_HDIFF)+"px";
  var hy = (typeof(GB_HEIGHT) == "string" && GB_HEIGHT.match(/\%/)) ? (document.body.clientHeight-document.body.clientHeight*(GB_HEIGHT.replace(/\%/,''))/100)/2 : 32;
  
  $("#GB_window").css({ width: ww, height: hw, left: ((w - wp)/2)+"px", top: (GB_top+sy+hy)+"px" });
  $("#GB_frame").css("height",hw);
}

function GB_changetitle(caption){
    $("#GB_caption").html(caption);
}
