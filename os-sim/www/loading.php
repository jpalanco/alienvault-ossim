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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title><?php echo gettext("OSSIM Framework"); ?> </title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
	<meta http-equiv="Pragma" content="no-cache"/>
	<link rel="stylesheet" type="text/css" href="/ossim/style/av_common.css?t=<?php echo Util::get_css_id() ?>"/>
	
	<script type="text/javascript" src="/ossim/js/jquery.min.js"></script>	
    <script type="text/javascript" src="/ossim/js/utils.js"></script>
    
    <style type='text/css'>
        #av_main_loader {
            display: block;
            position: fixed;
            z-index: 600000;
            top: 40%;
            left: 50%;
            width: 100px;
            padding-top: 100px;
            margin-left: -50px;
            background: url(/ossim/pixmaps/av_main_spinner.png) no-repeat left top;
            color: rgba(0,0,0,.5);
            font-weight: bold;
            text-align: center;
            text-shadow: 0 1px white;    
            animation: loader 1s infinite steps(7);
            -webkit-animation: loader 1s infinite steps(7);
        }
        
        @keyframes loader {
            from {
                background-position: 0 0;
            }
            
            to {
                background-position: -700px 0;
            }
        }
        
        @-webkit-keyframes loader {
            from {
                background-position: 0 0;
            }
            
            to {
                background-position: -700px 0;
            }
        }
    
    </style>	
	
    <script type="text/javascript">	
    		
        $(document).ready(function(){			
            
            
            /* Showing loading box */

            var top_l   = ($(window).height() / 2 ) - 100;
                                        
                top_l   = (top_l > 0) ? top_l : '250';
    
            var config  = {
                style: 'top: '+ top_l +'px;'
            };  
    
    
            var loading_box = Message.show_loading_spinner('av_main_loader', config); 
            
        });
    	
    </script>     

</head>

<body>
    <div id ='av_main_loader'></div>
</body>
</html>
