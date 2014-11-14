<?php
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
Session::useractive("../session/login.php");


require_once 'classes/Mobile.inc';
?>
<!DOCTYPE html>
<html class="iphone">
<head>

    <title>AVC <?=preg_replace("/\d+\.\d+\.(\d+\.\d+)/","\\1", Util::get_default_admin_ip())?></title>
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black" />  
    <meta name="viewport" content="user-scalable=no; width=device-width; initial-scale=1.0; maximum-scale=1.0;"> 
    <link rel="Shortcut Icon" type="image/x-icon" href="../favicon.ico" />
    <link rel="apple-touch-icon" href="/ossim/statusbar/app-icon.png" />
    <!-- <link rel="apple-touch-startup-image" href="/ossim/statusbar/avconsole.jpg" /> -->

    <script type="text/javascript" src="../js/mobile/jquery.js"></script>
    <script type="text/javascript" src="../js/mobile/jquery-ui.js"></script>
    <script type="text/javascript" src="../js/mobile/jquery.iphone.js"></script>
    
    <script type="text/javascript" src="../js/mobile/other/jquery.mousewheel.js"></script>
    <script type="text/javascript" src="../js/mobile/other/jquery.disable.text.select.js"></script>
    <script type="text/javascript" src="../js/mobile/other/jquery.backgroundPosition.js"></script>	
    <script type="text/javascript" src="../js/mobile/other/jquery.dPassword.js"></script>
    
    <script type="text/javascript" src="../js/mobile/ui/ui.iMenu.js"></script>
    <script type="text/javascript" src="../js/mobile/ui/ui.iMenuManager.js"></script>
    <script type="text/javascript" src="../js/mobile/ui/ui.iTabs.js"></script>
    
    <script type="text/javascript" src="../js/mobile/ui/ui.iInput.js"></script>
    <script type="text/javascript" src="../js/mobile/ui/ui.iPassword.js"></script>
    <script type="text/javascript" src="../js/mobile/ui/ui.iCheckBox.js"></script>
    <script type="text/javascript" src="../js/mobile/ui/ui.iRadioButton.js"></script>
    <script type="text/javascript" src="../js/mobile/ui/ui.iSelect.js"></script>
    
    <script type="text/javascript" src="../js/mobile/ui/ui.iGallery.js"></script>
    <script type="text/javascript" src="../js/mobile/ui/ui.iScroll.js"></script>
    
    <script>
        function hideURLbar() { window.scrollTo(0,1);}
        function setOrientation() { document.location.reload(); }        
        
        $(document).ready(function() {
            if (window.navigator.standalone) { // run fullscreen
                $('#fullsrc').show();
            } else {
                setTimeout(hideURLbar, 100);
            }
            window.addEventListener('orientationchange', setOrientation, false);
                
            defaultWidth	= 320; //pixels
            transition		= 500; //millisecond
            
            function resetMargin(width) {
                divLeftMargin	= 0;
                $('.additional-block').each(function() {
                    thisLeftMargin	= divLeftMargin + 'px';
                    $(this).css('margin-left', thisLeftMargin);
                    divLeftMargin	= divLeftMargin + width;
                });
            };
            
            resetMargin(defaultWidth);
            
            $('ul#menu li a').each(function() {
                
                thisHref	= $(this).attr('ref');
                if($(thisHref).length > 0) {
                    $(this).addClass('has-child');
                }
            });
            
            $('ul#menu li a').click(function(event) {
                
                selectedDiv			= $(this).attr('ref');
                selectedMargin		= $(selectedDiv).css('margin-left');
                selectedParent		= $(this).parents('.additional-block');
                sliderMargin		= $('.slider').css('margin-left');
                slidingMargin		= (parseInt(sliderMargin) - defaultWidth) + 'px';
                
                if((parseInt(selectedMargin) - defaultWidth) >= defaultWidth) {
                    selectedParent.after($(selectedDiv));
                    resetMargin(defaultWidth);
                    $('.slider').animate({marginLeft: slidingMargin}, transition);
                } else {
                    $('.slider').animate({marginLeft: slidingMargin}, transition);
                }
                
                $('#ajax').load('mobile_option.php?login=<?=urlencode($_REQUEST['login'])?>&screen='+selectedDiv);
                $(".back").bind('click', function () {
                    
                    //selectedParent	= document.getElementById("start")
                    selectedParent	= $(this).parents('.additional-block');
                    sliderMargin	= - (parseInt(selectedParent.css('margin-left')) - defaultWidth) + 'px';
                    $('.slider').animate({marginLeft: 0}, transition);
                    
                    
                    document.getElementById("ajax").innerHTML="<img src='../pixmaps/loading3.gif' align='absmiddle'/>&nbsp;<?=_("Loading remote content, please wait")?>";
                });
                
                
            });
            
        });
    </script>
    <style type="text/css">
    	body {
    		line-height: 1.5em;
    		font-family: Helvetica;
    	}
    	
    	ul {
    		margin: 0;
    		padding: 0;
    	}
    	
    	li {
    		list-style-type: none;
    		font-size: 14px;
    		padding: 5px 5px 5px 10px;
    		/*border-bottom: 1px solid #ccc;*/
    	}
    	
    	#container {
    		margin-left: auto;
    		margin-right: auto;
    		/*background: url('images/iPhone.png') top left no-repeat;*/
    		width: 320px;
    		height: 1024px;
    		position: relative;
    		overflow: hidden;
    	}
		
		.binder {
			background: #e8e8e8;
			float: left;
			width: 320px;
			height: 1024px;
			position: relative;
			overflow: hidden;
		}
		
		.header {
			background: -webkit-gradient(linear, 0% 0%, 0% 100%, from(#AAB8C9), to(#6E84A2), color-stop(.5,#6E84A2));
			background: -moz-linear-gradient(0% 65% 90deg,#6E84A2, #AAB8C9, #AAB8C9 100%);
			font-size: 14px;
			color: #fff;
			height: 30px;
			padding: 0 10px;
			text-shadow: 0px 1px 0px #000;
			text-align: center;
			position: relative;
		}
		
		.additional-block {
			float: left;
			width: 320px;
			/*height: 370px;*/
			position: absolute;
		}
		
		.menu li:hover {
			background: -webkit-gradient(linear, 0% 0%, 0% 100%, from(#4185F4), to(#194FDB), color-stop(.6,#194FDB));
			background: -moz-linear-gradient(0% 65% 90deg,#194FDB, #4185F4, #4185f4 100%);
			cursor: pointer;
		}	
		
		.menu li:hover a.has-child {
			background: url('images/arrow-hover.png') center right no-repeat;
		}
		
		.menu li a {
			display: block;
			width: 227px;
		}
		
		.menu li:hover a {
			color: #fff;
			text-shadow: 0px 1px 0px #000;
		}
		
		.has-child {
			background: url('images/arrow.png') center right no-repeat;
		}
		
		.has-child:hover {
			background: url('images/arrow-hover.png') center right no-repeat;
		}
		
		.biodata {
			margin-left: 10px;
			font-size: 12px;
		}
		
		.info {
			color: #fff;
			position: absolute;
			text-align: right;
			width: 400px;
			margin-top: 15%;
			font-size: 40px;
			font-weight: bold;
			line-height: .9em;
			text-shadow: 0px 2px 0px #000;
		}
		
			.info a, .info a:visited, .info a:hover {
				color: #fff;
			}
	
		
	a, a:visited, a:hover {
		text-decoration: none;
		color: #000;
	}
    
    
    /* other style */
    
    body,html {
        height:100%; 
        padding:0px; 
        margin:0px;
        -webkit-user-select: none;
    }
    html {
        background-color: rgba(0,0,0,1);
        min-height: 320px;
    }
    body {
        font: normal 12px/16px Helvetica, Geneva, sans-serif;
        text-align: center;
    }
    
    html.desktop {
        /*background: url(_/images/homepage.jpg) no-repeat center center fixed;*/
        -webkit-background-size: cover;
        -moz-background-size: cover;
        -o-background-size: cover;
        background-size: cover;
    }
    html.iphone {
        background-size: 480px 720px;
        background:#FFF; /*-webkit-gradient(linear, 0% 80%, 0% 20%, from(#ffffff), to(#d1d1d1));*/
    }
    
    @media only screen and (device-width: 768px) {
      html,body {
        height: 100%;
        width: 100%;
      }
      
      html.iphone {
        background-size: 1024px 1024px;
        background-repeat: repeat;
      }
    }

    h1#logo {
        display: block;
        height: 97px;
        width: 97px;
        /*background: transparent url(_/images/logo.png) no-repeat center top;*/
        background-size: 97px 97px;
        margin: 14px auto;
        overflow: hidden;
    }
    
    h1#logo a {
        display: block;
        padding-top: 97px;
        height: 0;
        line-height: 999px;
        border-radius: 100px;
    }

    ul#menu, ul#menu li, ul#menu li a, a.details {
        list-style: none;
        margin: 0;
        padding: 0;
    }
    
    ul#menu
    {
        display: block;
        position:relative;
        text-align: left;
    }
    
    ul#menu li {
        display: inline-block;
        position: relative;
        margin: 6px 3px;
        width: 66px;
        padding-top: 61px;
    }
    
    ul#menu li a {
        display: inline-block;
        position: absolute;
        top: 0;
        left: 50%;
        margin-left: -28px;
        background-color: transparent;
        background-position: center top;
        background-repeat: no-repeat;
        background-size: 57px 57px;
        height: 57px;
        width: 57px;
        border-radius: 12px;
        box-shadow: 0 1px 2px rgba(0,0,0,1);
    }
    ul#menu li label {
        display: block;
        text-align: center;
        overflow: hidden;
        max-width: 66px;
        text-overflow: ellipsis;
        text-align: center;
        font-weight: bold;
        text-decoration: none;
        color: #232323;
        /*text-shadow: 0 1px 1px #F2F2F2;*/
        line-height: 16px;
    }
    
    ul#menu li a.selected {
         -webkit-animation-name: pulse;
         -webkit-animation-duration: 2s;
         -webkit-animation-direction: alternate;
         -webkit-animation-timing-function: ease-in-out;
         -webkit-animation-iteration-count: infinite;
    }
    
    @-webkit-keyframes pulse {
     0% {
       box-shadow: inset 0 0 0 1px rgba(0,0,0,0.3), 0 0 0 3px #b3e955,  0 0 10px 3px #b3e955;
     }
     50% {
       box-shadow: inset 0 0 0 1px rgba(0,0,0,0.3), 0 0 0 3px #b3e955;
     }
     100% {
       box-shadow: inset 0 0 0 1px rgba(0,0,0,0.3), 0 0 0 3px #b3e955,  0 0 10px 3px #b3e955;
     }
    }
        
    a.details {
        margin: 0 auto 30px auto;
    }
    
    a.install {
        display: block;
        margin: 10px 0;
        padding: 15px 20px;
        text-shadow: 0 -1px 0 #000;
        color: #adadad;
        line-height: 14px;
        text-decoration: none;
        text-align: center;
        color: #fff;
        font-weight: bold;
        font-size: 14px;
        border: 1px solid #2c170b;
        background: -webkit-linear-gradient(top, rgba(160,100,66,0.8) 0%,rgba(70,40,22,0.8) 50%,rgba(53,29,15,0.8) 50%,rgba(78,46,26,0.8) 100%);
        background-repeat: repeat-x;
        border-radius: 8px;
        -webkit-box-shadow: inset 0 1px 1px rgba(219,140,94,0.5), 0 1px 3px rgba(0,0,0,0.75);
    }
    h1 {
        display: block;
        font-weight: bold;
        font-size: 12px;
        color: #fff;
        text-align: center;
        text-shadow: 0 1px 1px #000;
    }
    
    p {
        font-weight: bold;
        color: #fff;
        text-align: center;
        text-shadow: 0 1px 1px #000;
    }
    
    p.desc {
        font-weight: normal;
        font-style: italic;
        margin-bottom: 40px;
    }
    
    p.footer a {
        margin-top: 5px;
        font-weight: normal;
        font-style: italic;
        color: rgba(255,255,255,0.6);
        display: inline-block;
        padding: 3px 15px;
        background-color: rgba(0,0,0,0.4);
        border-radius: 12px;
        text-decoration: none;
    }
    p.footer a b {
        color: rgba(255,255,255,1);
    }
    
    html.desktop .content {
        border-radius: 12px;
        width:400px;
        position:absolute;
        padding: 250px 15px 15px 15px;
        margin-top: -147px;
        left: 8%;
        top:50%;
        box-sizing: border-box;
        /*background: transparent url(_/images/homepage-icon.png) center 40px no-repeat;*/
        background-color: rgba(0,0,0,0.2);
    }
    
    html.desktop p.footer {
        margin-bottom: 0;
    }
        
    ul#links {
        display: block;
        margin: 15px 0 10px 0;
        padding: 0;
    }
    
    ul#links li {
        display: inline-block;
        overflow: hidden;
        height: 36px;
        width: 136px;
        padding: 0;
        margin: 0;
    }
    ul#links li a {
        display: block;
        overflow: hidden;
        padding-top: 36px;
        height: 0;
        background-color: transparent;
        background-position: top center;
        background-repeat: no-repeat;
        background-size: 136px 36px;
        border-radius: 6px;
    }
    </style>
</head>
<body >
    <div id="container">
        <div class="slider">
            <div id="start" class="additional-block">
                <img src="../pixmaps/top/logo_siem.png" border="0" style="margin:10px 0">
                <table align="center" cellspacing='0' cellpadding='0' style="width:300px;background-color:#eeeeee;border-color:#dedede;opacity:0.5;border-radius: 8px;-moz-border-radius: 8px;-webkit-border-radius: 8px;">
                <tr id="fullsrc" style="display:none"><td><img src="../pixmaps/1x1.png" height="22px" border="0"></td><tr>
                <tr><td>
                    <ul id="menu">
                        <li class="button1"><a href="#" ref="status" style="background-image: url('../pixmaps/mobile/icon-status.png');"></a><label>Status</label></li>
                        <li class="button2"><a href="#" ref="alarms" style="background-image: url('../pixmaps/mobile/icon-alarm.png');"></a><label>Alarms</label></li>
                        <li class="button3"><a href="#" ref="tickets" style="background-image: url('../pixmaps/mobile/icon-ticket.png');"></a><label>Tickets</label></li>
                        <li class="button5"><a href="#" ref="unique_siem" style="background-image: url('../pixmaps/mobile/icon-siem_events.png');"></a><label>SIEM</label></li>
                        <li class="button6"><a href="#" ref="logout" style="background-image: url('../pixmaps/mobile/icon-exit.png');"></a><label>Logout</label></li>
                    </ul>
                </td></tr>
                </table>
                <div style='border-top: 1px solid #8CC12D; margin: 130px 10px 0px 10px;'>
                    <span style="margin: 2px 0; float: left; font-size: 10px; color: #CCCCCC;">&copy; Copyright 2012 AlienVault, Inc. - Schema Version <?php echo Mobile::get_version() ?></span>
                </div>
            </div>
            <div id="main" class="additional-block">
                
                <div id="ajax">
                    <div style="padding-top:10px"><img src='../pixmaps/loading3.gif' align="absmiddle"/>&nbsp;<?=_("Loading remote content, please wait")?></div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
