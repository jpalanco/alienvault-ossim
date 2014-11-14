/* Greybox Redux
 * Required: http://jquery.com/
 * Written by: John Resig
 * Based on code by: 4mir Salihefendic (http://amix.dk)
 * License: LGPL (read more in LGPL.txt)
 * 2009-05-1 modified by jmalbarracin. Fixed  total document width/height
 * 2009-06-4 modified by jmalbarracin. Support of width %, height %
 * 2009-09-19 Added maximized window
 */

var CB_HEIGHT = 400;
var CB_WIDTH  = 400;
var CB_SCROLL_DIFF = (navigator.appVersion.match(/MSIE/)) ? 1 : ((navigator.appCodeName.match(/Mozilla/)) ? 17 : 17 );

var CB_HDIFF = 5;
var CB_URL_AUX = "";

//MOVE AND RESIZE VARIABLES
var CB_MOVE = false;
var CB_RESIZE = false;
var CB_X=0, CB_Y=0, CB_OFFSET = false;
var CB_RWIDTH=0, CB_RHEIGHT=0, CB_IWIDTH=0, CB_IHEIGHT=0;
var CB_MIN_HEIGHT =150;
var CB_MIN_WIDTH = 150;


function CB_show(pos, caption, url, height, width) 
{

    //Loading jquery-ui in case it is not loaded previously
    if (typeof $.ui == 'undefined') 
    {
        $.ajaxSetup({async: false});

        $.getScript("/ossim/js/jquery-ui.min.js");

        $.ajaxSetup({async: true});
    }

  $("#CB_window").remove();

  
    CB_HEIGHT  = height || 400;
    CB_WIDTH   = width || 400;
    CB_URL_AUX = url;


    var _window  = "<div id='CB_window' width='"+CB_WIDTH+"' height='"+CB_HEIGHT+"'><div id='CB_arrow' class='arrow-up-black'></div></div>";

    $(document.body).append(_window);

    if(pos != false)
      CB_position(pos);
    else
      CB_position_centered();




    var _win_h    = $("#CB_window").height();
    var _win_w    = $("#CB_window").width();


    var _loading  = "<div id='CB_loading' style='height:"+ ( _win_h ) +"px;width:"+ ( _win_w ) +"px;'> \
                        <div class ='img_loading'> \
                            <img src ='/ossim/pixmaps/loading.gif' height='18px'/> \
                        </div> \
                    </div> \
                    ";

    var _actions  = "<div id='CB_actions' > \
                    </div> \
                    ";

    var _opts_aux = "<div id='CB_layer_aux' > \
                    </div> \
                    ";
            
    var _head     = "<div id='CB_head'> \
                        <div id='CB_caption'>"+caption+"</div> \
                        <div id='CB_close' class='ui-icon ui-icon-close'></div> \
                    </div> \
                    ";

    var _content  = "<div id='CB_content'></div>";


    var _iframe   = "<iframe onload='CB_set_iframe();' id='CB_frame' name='CB_frame' src='"+url+"' style='background: transparent;' scrolling='auto' frameborder='0'></iframe>";


    $("#CB_window").append(_head);


    $("#CB_close").on('click', CB_hide);

    $("#CB_window").append( _actions );
    $("#CB_window").append( _content );
    $("#CB_window").append( _opts_aux );
    $("#CB_content").append( _loading );
    $("#CB_content").append( _iframe );


    $( "#CB_close" ).hover(function() 
    {
        $(this).removeClass('ui-icon-close');
        $(this).addClass('ui-icon-circle-close');    

    }).mouseleave(function()
    {
        $(this).removeClass('ui-icon-circle-close');
        $(this).addClass('ui-icon-close');
    });

    $("#CB_window").resizable(
    {
        minHeight: 75,
        minWidth: 75,
        alsoResize: "#CB_actions",
        start: function()
        {
            $('#CB_layer_aux').show();
        },
        stop: function()
        {    
            $('#CB_layer_aux').hide();
        }
    });
    
}


function CB_set_iframe() 
{
    $("#CB_frame").css('visibility', 'visible');
    $("#CB_loading").remove();
    adjust_height();
}


function CB_hide()
{
    $("#CB_frame").remove();
    $("#CB_window").remove();
    $('body').css('cursor','default'); 
    if (typeof(CB_onclose) == "function") CB_onclose(CB_URL_AUX);
}


function CB_position(pos) 
{  
    pageX = $(pos).offset().left + ($(pos).width()/2);
    pageY = $(pos).offset().top + $(pos).height() ;

    var de = document.documentElement;
    // total document width
    var w = document.body.scrollWidth
    if (self.innerWidth > w) w = self.innerWidth;
    if (de && de.clientWidth > w) w = de.clientWidth;
    if (document.body.clientWidth > w) w = document.body.clientWidth;

    if(pageX + CB_WIDTH < w)
    {
        var pos_x = pageX - 20;
        var pos_y = pageY + 10;
    }
    else
    {   
        var pos_x = pageX - CB_WIDTH + 20;
        var pos_y = pageY + 10;
    }
    var ww = (typeof(CB_WIDTH) == "string" && CB_WIDTH.match(/\%/)) ? CB_WIDTH : CB_WIDTH+"px";  
    var hw = (typeof(CB_HEIGHT) == "string" && CB_HEIGHT.match(/\%/)) ? CB_HEIGHT : (CB_HEIGHT- CB_HDIFF)+"px";

    $("#CB_window").css({ width: ww, height: hw, left: pos_x+"px", top: pos_y+"px" });
    $("#CB_frame").css("height",hw); 
}



function CB_position_centered() 
{
  
    var de = document.documentElement;
    // total document width
    var w = document.body.scrollWidth
    if (self.innerWidth > w) w = self.innerWidth;
    if (de && de.clientWidth > w) w = de.clientWidth;
    if (document.body.clientWidth > w) w = document.body.clientWidth;
      
    // total document height
    var h = document.body.scrollHeight
    if ((self.innerHeight+window.scrollMaxY) > h) h = self.innerHeight+window.scrollMaxY;
    if (de && de.clientHeight > h) h = de.clientHeight;
    if (document.body.clientHeight > h) h = document.body.clientHeight;

    var sy_correction = (navigator.appVersion.match(/MSIE/)) ? 30 : 0;  
    var sy = document.documentElement.scrollTop || document.body.scrollTop - sy_correction;
    var ww = (typeof(CB_WIDTH) == "string" && CB_WIDTH.match(/\%/)) ? CB_WIDTH : CB_WIDTH+"px";
    var wp = (typeof(CB_WIDTH) == "string" && CB_WIDTH.match(/\%/)) ? w*(CB_WIDTH.replace(/\%/,''))/100 : CB_WIDTH;

    var hw = (typeof(CB_HEIGHT) == "string" && CB_HEIGHT.match(/\%/)) ? CB_HEIGHT- CB_HDIFF : (CB_HEIGHT- CB_HDIFF)+"px";
    var hy = (typeof(CB_HEIGHT) == "string" && CB_HEIGHT.match(/\%/)) ? (document.body.clientHeight-document.body.clientHeight*(CB_HEIGHT.replace(/\%/,''))/100)/2 : CB_HEIGHT;

    $("#CB_window").css({ width: ww, height: hw, left: ((w - wp)/2)+"px", top: ((h-(sy+hy+100))/2)+"px" });
    $("#CB_frame").css("height",hw);

}

function adjust_height()
{
    var h = $("#CB_frame").contents().find('html').height() + 10;
    $("#CB_window").css({ height: h +'px'});
}
